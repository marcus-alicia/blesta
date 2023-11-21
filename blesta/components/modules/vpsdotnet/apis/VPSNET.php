<?php
use Blesta\Core\Util\Common\Traits\Container;

/**
 * API for vps.net
 *
 * This API provides an interface to vps.net allowing common virtual machine and account management tasks
 * @package VPSNET
 * @version 1.1.6.1
 *
 * Known Issues:
 * - Your PHP user will need access to /tmp so it can write cookies. Some PHP configurations may not allow this.
 *
 * Changelog:
 * 2012-03-13 Added RAM nodes support
 * 2011=06-06 Tickets
 * 2011-03-11 VirtualMachine->remove($remove_nodes=false)
 * 2011-02-04 fixed template name in loadFully() for disabled clouds
 * 2011-01-31 cloud_label to getVirtualMachines()
 * 2010-12-29 filter to getAllTemplates all/free/paid, default - all
 * 2010-12-21 VirtualMachine->getIpAddresses(),VirtualMachine->deleteIP($ipID);
 * 2010-12-13 Power actions was fixed
 * 2010-12-13 getCpanel()
 * 2010-12-09 loadFully() defines system_template_name
 * 2010-12-02 DNS functionality
 * 2010-11-15 getAvailableClouds return non fusion clouds by default
 * 2010-11-15 API URL to constructor (optional)
 * 2010-11-15 changed getTemplatesGroups to return only groups that has templates (by cloud id)
 * 2010-11-12 getAvailableClouds getTemplatesGroups getAllTemplates
 * 2010-11-03 Solution for Onapp CPUs graphs
 * 2010-11-03 Solution for Onapp bandwidth graphs
 * 2010-11-02 Onapp console support, reinstall
 * 2010-11-01 Storage nodes, Rsync, R1Soft, Control Panels (Cpanel/ISPManager ) support
 * 2009-06-24 Corrected wrong variable virtual_machine_id in removing virtual machines (should just be id) and error in createVirtualMachine
 * 2009-06-10 Corrected error in sendGETRequest and sendPUTRequest
 * 2009-06-09 Added proxy support, fixed incorrect parameters (hostname+domain_name) passed in create virtual machine - now uses fqdn
 * 2009-06-02 Fixed showConsole function
 * 2009-06-02 Fixed graph function
 * 2009-05-31 Fixed CURL_USERAGENT to CURLOPT_USERAGENT
 * 2009-05-29 Added changelog, fixed API resource for available clouds.
 */
require("pData.class.php");
require("pChart.class.php");

class VPSNET
{
    // Load traits
    use Container;

    /**
     * This contains the API URL for accessing vps.net. You should not
     * need to change this unless asked to do so by vps.net support.
     * @var string
     *
     */
    protected $_apiUrl = 'https://api.vps.net';

    /**
     * This contains the API version and is sent as part of server
     * requests.
     * @var string
     */
    protected $_apiVersion = 'api10json';

    /**
     * @var string
     */
    protected $_apiUserAgent = "VPSNET_API_10_JSON/PHP";

    /**
     * @var string
     */
    protected $_session_cookie;

    /**
     * @var string
     */
    protected $_auth_name = '';

    /**
     * @var string
     */
    protected $_auth_api_key = '';

    /**
     * @var string
     */
    protected $_proxy = '';

    /**
     * @var null
     */
    private $ch = null;

    /**
     * @var null
     */
    public $last_errors = null;

    /**
     * @var
     */
    protected static $instance;

    /**
     * VPSNET constructor.
     */
    private function __construct()
    {
        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * VPSNET destructor.
     */
    public function __destruct()
    {
        if (!is_null($this->ch)) {
            curl_close($this->ch);
        }
    }

    /**
     * Returns true if the API instance has authentication information set.
     * If not, you can call getInstance() with credentials.
     * @return boolean
     */
    public function isAuthenticationInfoSet()
    {
        return (strlen($this->_auth_name) > 0 && strlen($this->_auth_api_key) > 0);
    }

    /**
     * Returns the instance of the API.
     * @return VPSNET
     */
    public static function getInstance($username = '', $_auth_api_key = '', $proxy = '', $_apiUrl = null)
    {
        // Initialize logger
        $logger = (new VPSNET())->getFromContainer('logger');

        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
            self::$instance->logger = $logger;
            self::$instance->_auth_name = $username;
            self::$instance->_auth_api_key = $_auth_api_key;
            if ($_apiUrl) {
                self::$instance->_apiUrl = $_apiUrl;
            }
            if (strlen($proxy) > 0) {
                self::$instance->_proxy = $proxy;
            }
            if ((strlen($username) == 0 || strlen($_auth_api_key) == 0)) {
                trigger_error('Singleton has been called for the first time and a username or API Key has not been set.', E_USER_ERROR);
            }
            self::$instance->_initCurl();
        }

        return self::$instance;
    }

    /**
     * VPSNET clone.
     */
    public function __clone()
    {
        trigger_error('Clone is not permitted. This class is a singleton.', E_USER_ERROR);
    }

    /**
     *
     */
    protected function _initCurl()
    {
        $this->ch = curl_init();

        if (strlen($this->_proxy) > 0) {
            curl_setopt($this->ch, CURLOPT_PROXY, $this->_proxy);
        }

        curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->_apiUserAgent);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_USERPWD, $this->_auth_name . ':' . $this->_auth_api_key);

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
        }
    }

    /**
     * @param $resource
     * @param bool $append_api_version
     * @param string $queryString
     */
    public function setAPIResource($resource, $append_api_version = true, $queryString = '')
    {
        if ($append_api_version) {
            if ($queryString) {
                curl_setopt($this->ch, CURLOPT_URL, sprintf('%1$s/%2$s.%3$s?%4$s', $this->_apiUrl, $resource, $this->_apiVersion, $queryString));
            } else {
                curl_setopt($this->ch, CURLOPT_URL, sprintf('%1$s/%2$s.%3$s', $this->_apiUrl, $resource, $this->_apiVersion));
            }
        } else {
            if ($queryString) {
                curl_setopt($this->ch, CURLOPT_URL, sprintf('%1$s/%2$s?%3$s', $this->_apiUrl, $resource, $queryString));
            } else {
                curl_setopt($this->ch, CURLOPT_URL, sprintf('%1$s/%2$s', $this->_apiUrl, $resource));
            }
        }
    }

    /**
     * @return array
     */
    public function sendGETRequest()
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($this->ch, CURLOPT_HTTPGET, true);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        return $this->sendRequest();
    }

    /**
     * @param null $data
     * @param bool $encodeasjson
     * @return array
     */
    public function sendPOSTRequest($data = null, $encodeasjson = true)
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
        if ($encodeasjson) {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($this->ch, CURLOPT_POST, true);
        $rtn = $this->sendRequest();

        return $rtn;
    }

    /**
     * @param $data
     * @return array
     */
    public function sendPUTRequest($data)
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        $json_data = json_encode($data);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
            'Content-Length: ' . strlen($json_data),
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        return $this->sendRequest();
    }

    /**
     * @return array
     */
    public function sendDELETERequest()
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        return $this->sendRequest();
    }

    /**
     * @param null $data
     * @return array
     */
    protected function sendRequest($data = null)
    {
        $rtn = [];
        $rtn['response_body'] = curl_exec($this->ch);
        $rtn['info'] = curl_getinfo($this->ch);

        if ($rtn['response_body'] == false) {
            $this->logger->error(curl_error($this->ch));
        }

        if (isset($rtn['info']['content_type']) && $rtn['info']['content_type'] == 'application/json; charset=utf-8') {
            if ($rtn['info']['http_code'] == 200) {
                $rtn['response'] = json_decode($rtn['response_body']);
            } else if ($rtn['info']['http_code'] == 422) {
                $rtn['errors'] = json_decode($rtn['response_body']);
            }
        }

        return $rtn;
    }

    /**
     * Returns Nodes from your account.
     * @param int $consumer_id (Optional) Consumer Id to filter results by
     * @return array An array of Nodes instances
     */
    public function getStorageNodes($consumer_id = 0)
    {
        return $this->getNodes($consumer_id = 0, 'storage');
    }

    /**
     * @param int $consumer_id
     * @param string $type
     * @return array
     */
    public function getNodes($consumer_id = 0, $type = 'vps')
    {
        if ($consumer_id > 0) {
            $this->setAPIResource('nodes', true, 'consumer_id=' . $consumer_id . '&type=' . $type);
        } else {
            $this->setAPIResource('nodes', true, 'type=' . $type);
        }
        $result = $this->sendGETRequest();
        $return = [];
        if ($result['info']['http_code'] == 422) {
        } else if ($result['response']) {
            $response = $result['response'];
            for ($x = 0; $x < count($response); $x++) {
                if ($response[$x]->slice->id) {
                    $return[$x] = new Node($response[$x]->slice->id, $response[$x]->slice->virtual_machine_id, $response[$x]->slice->consumer_id, 'vps', (bool) $response[$x]->slice->free_node, (bool) $response[$x]->slice->discount_node);
                } else if ($response[$x]->storage_node->id) {
                    $return[$x] = new Node($response[$x]->storage_node->id, $response[$x]->storage_node->virtual_machine_id, $response[$x]->storage_node->consumer_id, 'storage', (bool) $response[$x]->slice->free_node, (bool) $response[$x]->slice->discount_node);
                } else if ($response[$x]->ram_node->id) {
                    $return[$x] = new Node($response[$x]->ram_node->id, $response[$x]->ram_node->virtual_machine_id, $response[$x]->ram_node->consumer_id, 'ram', (bool) $response[$x]->slice->free_node, (bool) $response[$x]->slice->discount_node);
                }
            }
        }

        return $return;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getCloud($id)
    {
        $this->setAPIResource('clouds/' . $id);
        $result = $this->sendGETRequest();

        return $result['response'];
    }

    /**
     * @return mixed
     */
    public function getProfile()
    {
        $this->setAPIResource('profile');
        $result = $this->sendGETRequest();

        return $result['response']->user;
    }

    /**
     * Returns IP addresses from your account.
     * @param int $consumer_id (Optional) Consumer Id to filter results by
     * @return array An array of IPAddress instances
     */
    public function getIPAddresses($consumer_id = 0)
    {
        if ($consumer_id > 0) {
            $this->setAPIResource('ips', true, 'consumer_id=' . $consumer_id);
            //$this->setAPIResource('ip_address_assignments', true, 'consumer_id=' . $consumer_id);
        } else {
            $this->setAPIResource('ips');
            //$this->setAPIResource('ip_address_assignments');
        }
        $result = $this->sendGETRequest();
        $return = [];
        if ($result['info']['http_code'] == 422) {
        } else if ($result['response']) {
            $response = $result['response'];
            //                        print_r($result);
            for ($x = 0; $x < count($response); $x++) {
                $return[$x] = $this->_castObjectToClass('IPAddress', $response[$x]->ip_address);
            }
        }

        return $return;
    }

    /**
     * Returns Virtual Machines from your account.
     * @param int $consumer_id (Optional) Consumer Id to filter results by
     * @return array An array of VirtualMachine instances
     */
    public function getVirtualMachines($consumer_id = 0, $deletedAlso = false)
    {
        if ($consumer_id > 0) {
            $this->setAPIResource('virtual_machines', true, 'consumer_id=' . $consumer_id);
        } else {
            $this->setAPIResource('virtual_machines', true, 'basic');
        }
        $result = $this->sendGETRequest();
        if ($deletedAlso) {
            $this->setAPIResource('virtual_machines/recoverable');
            $dresult = $this->sendGETRequest();
        }
        $return = [];
        $names = [];
        if ($result['info']['http_code'] == 422) {
        } else if ($result['response']) {
            if ($dresult['response']) {
                foreach ($dresult['response'] as $k => $vm) {
                    $dresult['response'][$k]->virtual_machine->isDeleted = true;
                }
            } else {
                $dresult['response'] = [];
            }
            //$response = array_merge($dresult['response'], $result['response']);
            $response = $result['response'];
            for ($x = 0; $x < count($response); $x++) {
                $return[$x] = $this->_castObjectToClass('VirtualMachine', $response[$x]->virtual_machine);
            }
        }

        return $return;
    }

    /**
     * @param int $consumer_id
     * @return array
     */
    public function getRecoverableVirtualMachines($consumer_id = 0)
    {
        $this->setAPIResource('virtual_machines/recoverable');
        $dresult = $this->sendGETRequest();
        $names = [];
        $response = false;
        if ($result['info']['http_code'] == 422) {
        } else if ($result['response']) {
            if ($dresult['response']) {
                foreach ($dresult['response'] as $k => $vm) {
                    if ($consumer_id > 0) {
                        if ($vm->virtual_machine->consumer_id != $consumer_id) {
                            continue;
                        }
                    }
                    $dresult['response']->virtual_machine->isDeleted = true;
                    $response[] = $vm;
                }
            }
            if ($response) {
                for ($x = 0; $x < count($response); $x++) {
                    $return[$x] = $this->_castObjectToClass('VirtualMachine', $response[$x]->virtual_machine);
                }
            }
        }

        return $return;
    }

    /**
     * @param false $fusion
     * @return array
     */
    public function getAvailableClouds($fusion = false)
    {
        $this->setAPIResource('available_clouds');
        $result = $this->sendGETRequest();
        $clouds = [];
        if ($result['info']['http_code'] == 422) {
        } else if ($result['response']) {
            $clouds = $result['response'];
        }
        $return = [];
        foreach ($clouds as $cloud) {
            if ($fusion) {
                if ($cloud->cloud->available) {
                    if (strpos($cloud->cloud->label, 'usion')) {
                        $return[] = [
                            'id' => $cloud->cloud->id,
                            'text' => $cloud->cloud->label
                        ];
                    }
                }
            } else {
                if ($cloud->cloud->available) {
                    if (!strpos($cloud->cloud->label, 'usion')) {
                        $return[] = [
                            'id' => $cloud->cloud->id,
                            'text' => $cloud->cloud->label
                        ];
                    }
                }
            }
        }

        return $return;
    }

    /**
     * @param int $cloud_id
     * @return array
     */
    public function getTemplatesGroups($cloud_id = 0)
    {
        $this->setAPIResource('available_templates');
        $result = $this->sendGETRequest();
        $this->AllTemplatesInfo = [];
        $return = [];
        foreach ($result['response']->template_groups as $template_group) {
            if ($cloud_id == 0) {
                $return[] = $template_group->name;
            } else {
                $insert = false;
                foreach ($template_group->templates as $template) {
                    foreach ($template->clouds as $claud) {
                        if ($claud->id == $cloud_id) {
                            $insert = true;
                        }
                    }
                }
                if ($insert) {
                    $return[] = $template_group->name;
                }
            }
        }

        return $return;
    }

    /**
     * @param $cloud_id
     * @param $tid
     * @return mixed
     */
    public function getTemplateInfo($cloud_id, $tid)
    {
        $this->setAPIResource('clouds/' . $cloud_id . '/system_templates/' . $tid);
        $result = $this->sendGETRequest();

        return $result['response']->system_template;
    }

    /**
     * @param false $group
     * @param string $filter
     * @param int $cloud
     * @return array
     */
    public function getAllTemplates($group = false, $filter = 'all', $cloud = 0)
    {
        $this->setAPIResource('available_templates');
        $result = $this->sendGETRequest();
        $this->AllTemplatesInfo = [];
        foreach ($result['response']->template_groups as $template_group) {
            foreach ($template_group->templates as $tempalte) {
                if ($group) {
                    if ($group == $template_group->name) {
                        if (!isset($this->AllTemplatesInfo[$tempalte->clouds[0]->system_template_id])) {
                            $tinfo = $this->getTemplateInfo($tempalte->clouds[0]->id, $tempalte->clouds[0]->system_template_id);
                            $exist = false;
                            foreach ($tempalte->clouds as $tc) {
                                if ($tc->id == $cloud) {
                                    $exist = true;
                                }
                            }
                            if (($exist) || ($cloud == 0)) switch ($filter) {
                                case 'free':
                                    if ($tinfo->applicable_price->USD == 0) {
                                        $this->AllTemplatesInfo[$tempalte->clouds[0]->system_template_id] = $tinfo;
                                    }
                                    break;
                                case 'paid':
                                    if ($tinfo->applicable_price->USD > 0) {
                                        $this->AllTemplatesInfo[$tempalte->clouds[0]->system_template_id] = $tinfo;
                                    }
                                    break;
                                default:
                                    $this->AllTemplatesInfo[$tempalte->clouds[0]->system_template_id] = $tinfo;
                                    break;
                            }
                        }
                    }
                } else {
                    if (!isset($this->AllTemplatesInfo[$tempalte->clouds[0]->system_template_id])) {
                        $tinfo = $this->getTemplateInfo($tempalte->clouds[0]->id, $tempalte->clouds[0]->system_template_id);
                        $exist = false;
                        foreach ($tempalte->clouds as $tc) {
                            if ($tc->id == $cloud) {
                                $exist = true;
                            }
                        }
                        if (($exist) || ($cloud == 0)) switch ($filter) {
                            case 'free':
                                if ($tinfo->applicable_price->USD == 0) {
                                    $this->AllTemplatesInfo[$tempalte->clouds[0]->system_template_id] = $tinfo;
                                }
                                break;
                            case 'paid':
                                if ($tinfo->applicable_price->USD > 0) {
                                    $this->AllTemplatesInfo[$tempalte->clouds[0]->system_template_id] = $tinfo;
                                }
                                break;
                            default:
                                $this->AllTemplatesInfo[$tempalte->clouds[0]->system_template_id] = $tinfo;
                                break;
                        }
                    }
                }
            }
        }

        return $this->AllTemplatesInfo;
    }

    /**
     * Returns available Clouds and Virtual Machine templates.
     * @return array
     */
    public function getAvailableCloudsAndTemplates()
    {
        $this->setAPIResource('available_clouds');
        $result = $this->sendGETRequest();
        $return = null;
        if ($result['info']['http_code'] == 422) {
        } else if ($result['response']) {
            $return = $result['response'];
        }

        return $return;
    }

    /**
     * Adds internal IP addresses to your account.
     * @param int $quantity Number of IPs to add
     * @param int $consumer_id (Optional) Consumer Id to tag the IP Address with
     * @return IPAddress An instance of the IP address that was assigned
     */
    public function addInternalIPAddresses($quantity, $consumer_id = 0)
    {
        if ($quantity < 1) {
            trigger_error("To call VPSNET::addInternalIPAddress() you must provide a quantity greater than 0", E_USER_ERROR);

            return false;
        }
        $this->setAPIResource('ip_address_assignments');
        $json_request['ip_address_assignment']->quantity = $quantity;
        $json_request['ip_address_assignment']->type = 'internal';
        if ($consumer_id > 0) {
            $json_request['ip_address_assignment']->consumer_id = $consumer_id;
        }
        $result = $this->sendPOSTRequest($json_request);
        $return = null;
        if ($result['response']) {
            $return = $result['response'];
        }

        return $return;
    }

    /**
     * Adds external IP addresses to your account.
     * @param int $quantity Number of IPs to add
     * @param int $cloud_id Id of the cluster on which to add the IP Address
     * @param int $consumer_id (Optional) Consumer Id to tag the IP Address with
     * @return IPAddress An instance of the IP address that was assigned
     */
    public function addExternalIPAddresses($quantity, $cloud_id, $consumer_id = 0)
    {
        if ($quantity < 1 || $cloud_id < 1) {
            trigger_error("To call VPSNET::addExternalIPAddresses() you must provide a quantity greater than 0 and a cluster_id", E_USER_ERROR);

            return false;
        }
        $this->setAPIResource('ip_address_assignments');
        $json_request['ip_address_assignment']->quantity = $quantity;
        $json_request['ip_address_assignment']->cloud_id = $cloud_id;
        $json_request['ip_address_assignment']->type = 'external';
        if ($consumer_id > 0) {
            $json_request['ip_address_assignment']->consumer_id = $consumer_id;
        }
        $result = $this->sendPOSTRequest($json_request);
        $return = null;
        if ($result['response']) {
            $return = $result['response'];
        }

        return $return;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function deleteIP($id)
    {
        $this->setAPIResource('ips/' . $id);
        $result = $this->sendDELETERequest();

        return $result['response'];
    }

    /**
     * @param $ipid
     * @param $value
     * @return mixed
     */
    public function editIP($ipid, $value)
    {
        $this->setAPIResource('ips/' . $ipid);
        $json_request = [
            'ip_address' => [
                'notes' => $value
            ]
        ];
        $return = $this->sendPUTRequest($json_request);

        return $return['response'];
    }

    /**
     * Adds IP addresses to your account.
     * @return IPAddress An instance of the IP address that was assigned
     */
    public function addIPAddress($virtual_machine_id, $type)
    {
        $this->setAPIResource('ips');
        $requestdata = [];
        $requestdata['type'] = $type;
        $requestdata['quantity'] = 1;
        $requestdata['virtual_machine_id'] = $virtual_machine_id;
        $result = $this->sendPOSTRequest($requestdata);
        $ip = $result['response'][0]->ip_address;
        $requestdata = [];
        if ($ip) {
            $this->setAPIResource('ips/' . $result['response'][0]->ip_address->id);
            $requestdata['ip_address'] = [
                'notes' => 'for virtual machine #' . $virtual_machine_id
            ];
            $result = $this->sendPUTRequest($requestdata);

            return json_encode([
                'message' => [
                    'responseText' => 'IP successfully added'
                ],
                'ip' => $ip
            ]);
        }

        return $result['response_body'];
    }

    /**
     * Creates a new Virtual Machine account.
     * @param VirtualMachine $virtualmachine Instance of VirtualMachine containing new virtual machine properties
     * @return VirtualMachine|object An instance of the created VirtualMachine that was assigned or an Object of errors
     */
    public function createVirtualMachine($virtualmachine)
    {
        $this->setAPIResource('virtual_machines');
        $requestdata['label'] = $virtualmachine->label;
        $requestdata['fqdn'] = $virtualmachine->hostname;
        $requestdata['slices_required'] = $virtualmachine->slices_required;
        $requestdata['system_template_id'] = $virtualmachine->system_template_id;
        $requestdata['cloud_id'] = $virtualmachine->cloud_id;
        $requestdata['backups_enabled'] = (int) $virtualmachine->backups_enabled;
        $requestdata['rsync_backups_enabled'] = (int) $virtualmachine->rsync_backups_enabled;

        if (property_exists($virtualmachine, "storage_nodes_required")) {
            $requestdata['storage_nodes_required'] = $virtualmachine->storage_nodes_required;
        }
        if (property_exists($virtualmachine, "ram_nodes_required")) {
            $requestdata['ram_nodes_required'] = $virtualmachine->ram_nodes_required;
        }
        if (property_exists($virtualmachine, "r1_soft_backups_enabled")) {
            $requestdata['r1_soft_backups_enabled'] = (int) $virtualmachine->r1_soft_backups_enabled;
        }
        if (property_exists($virtualmachine, "consumer_id")) {
            $requestdata['consumer_id'] = $virtualmachine->consumer_id;
        }
        if (property_exists($virtualmachine, "licence") && is_array($virtualmachine->licence)) {
            $requestdata['licenses'] = $virtualmachine->licence;
        }

        $json_request['virtual_machine'] = $requestdata;
        $result = $this->sendPOSTRequest($json_request);
        $return = null;
        if (isset($result['response'])) {
            $return = $this->_castObjectToClass('VirtualMachine', $result['response']);

            return $result['response'];
        } else {
            if (isset($result['errors']) && isset($result['errors']->errors) && count($result['errors']->errors) > 0) {
                $errors = [];
                foreach ($result['errors']->errors as $error) {
                    $errors[] = "{$error[0]} {$error[1]}";
                }
                $errors = implode(", ", $errors);
                throw new Exception($errors);
            }
            throw new Exception("Unknown error");
        }
    }

    /**
     * Adds Nodes to your account.
     * @param int $quantity Number of Nodes to add
     * @param int $consumer_id (Optional) Consumer Id to tag the IP Address with
     * @return boolean true if nodes were added succesfully, false otherwise
     */
    public function addNodes($quantity, $consumer_id = 0, $type = 'vps')
    {
        $this->setAPIResource('nodes');
        $json_request['quantity'] = $quantity;
        if ($type == 'storage' || $type == 'ram') {
            $json_request['upgrade_option'] = $type;
        }
        if ($consumer_id > 0) {
            $json_request['consumer_id'] = $consumer_id;
        }
        $result = $this->sendPOSTRequest($json_request);

        return ($result['info']['http_code'] == 200);
    }

    /**
     * @param $classname
     * @param $object
     * @return mixed
     */
    public function _castObjectToClass($classname, $object)
    {
        return unserialize(preg_replace('/^O:\d+:"[^"]++"/', 'O:' . strlen($classname) . ':"' . $classname . '"', serialize($object)));
    }

    /**
     * Create domain
     */
    public function createDomain($domain, $ip = '')
    {
        if (!$ip) {
            $ip = '127.0.0.1';
        }
        $json_request = [
            'domain' => [
                'name' => $domain,
                'custom_template' => '',
                'ip_address' => trim($ip)
            ]
        ];
        $this->setAPIResource('domains');
        $result = $this->sendPOSTRequest($json_request);
        if ($result['response'] == 'false') {
            return false;
        }

        return (int) $result['response']->domain->id;
    }

    /**
     * @return mixed
     */
    public function getDomains()
    {
        $this->setAPIResource('domains');
        $return = $this->sendGETRequest();

        return $return['response'];
    }

    /**
     * @param $did
     * @return mixed
     */
    public function getDomainRecords($did)
    {
        $this->setAPIResource('domains/' . $did . '/records');
        $return = $this->sendGETRequest();

        return $return['response'];
    }

    /**
     * @param $did
     * @param $rid
     * @return mixed
     */
    public function getDomainRecord($did, $rid)
    {
        $this->setAPIResource('domains/' . $did . '/records/' . $rid, true, 'new');
        $return = $this->sendGETRequest();

        return $return['response'];
    }

    /**
     * @param $did
     * @param $rid
     * @param $type
     * @param $value
     * @return mixed
     */
    public function UpdateRecord($did, $rid, $type, $value)
    {
        $this->setAPIResource('domains/' . $did . '/records/' . $rid);
        $json_request = [
            'domain_record' => [
                $type => $value
            ]
        ];
        $return = $this->sendPUTRequest($json_request);

        return $return['response'];
    }

    /**
     * @param $did
     * @param $name
     * @param $ip
     * @param $ttl
     * @return mixed
     */
    public function addArecord($did, $name, $ip, $ttl)
    {
        $json_request = [
            'domain_record' => [
                'ttl' => $ttl,
                'data' => $ip,
                'type' => 'a',
                'host' => $name
            ]
        ];
        $this->setAPIResource('domains/' . $did . '/records');
        $return = $this->sendPOSTRequest($json_request);

        return $return['response'];
    }

    /**
     * @param $did
     * @param $name
     * @param $ip
     * @param $ttl
     * @return mixed
     */
    public function addNSrecord($did, $name, $ip, $ttl)
    {
        $json_request = [
            'domain_record' => [
                'ttl' => $ttl,
                'data' => $ip,
                'type' => 'ns',
                'host' => $name
            ]
        ];
        $this->setAPIResource('domains/' . $did . '/records');
        $return = $this->sendPOSTRequest($json_request);

        return $return['response'];
    }

    /**
     * @param $did
     * @param $name
     * @param $ip
     * @param $ips
     * @return mixed
     */
    public function addFailoverrecord($did, $name, $ip, $ips)
    {
        $json_request = [
            'domain_record' => [
                'data' => $ips,
                'type' => 'failover',
                'host' => $name,
                'primary_data' => $ip
            ]
        ];
        $this->setAPIResource('domains/' . $did . '/records');
        $return = $this->sendPOSTRequest($json_request);

        return $return['response'];
    }

    /**
     * @param $did
     * @param $name
     * @param $ip
     * @param $ttl
     * @return mixed
     */
    public function addAAAArecord($did, $name, $ip, $ttl)
    {
        $json_request = [
            'domain_record' => [
                'ttl' => $ttl,
                'data' => $ip,
                'type' => 'aaaa',
                'host' => $name
            ]
        ];
        $this->setAPIResource('domains/' . $did . '/records');
        $return = $this->sendPOSTRequest($json_request);

        return $return['response'];
    }

    /**
     * @param $did
     * @param $name
     * @param $ip
     * @param $ttl
     * @return mixed
     */
    public function addTXTrecord($did, $name, $ip, $ttl)
    {
        $json_request = [
            'domain_record' => [
                'ttl' => $ttl,
                'data' => $ip,
                'type' => 'txt',
                'host' => $name
            ]
        ];
        $this->setAPIResource('domains/' . $did . '/records');
        $return = $this->sendPOSTRequest($json_request);

        return $return['response'];
    }

    /**
     * @param $did
     * @param $name
     * @param $ip
     * @param $ttl
     * @return mixed
     */
    public function addCNAMErecord($did, $name, $ip, $ttl)
    {
        $json_request = [
            'domain_record' => [
                'ttl' => $ttl,
                'data' => $ip,
                'type' => 'cname',
                'host' => $name
            ]
        ];
        $this->setAPIResource('domains/' . $did . '/records');
        $return = $this->sendPOSTRequest($json_request);

        return $return['response'];
    }

    /**
     * @param $did
     * @param $name
     * @param $ip
     * @param $ttl
     * @param $prior
     * @return mixed
     */
    public function addMXrecord($did, $name, $ip, $ttl, $prior)
    {
        $json_request = [
            'domain_record' => [
                'ttl' => $ttl,
                'data' => $ip,
                'type' => 'mx',
                'host' => $name,
                'mx_priority' => $prior
            ]
        ];
        $this->setAPIResource('domains/' . $did . '/records');
        $return = $this->sendPOSTRequest($json_request);

        return $return['response'];
    }

    /**
     * @param $did
     * @param $rid
     * @return mixed
     */
    public function deleteDomainRecord($did, $rid)
    {
        $this->setAPIResource('domains/' . $did . '/records/' . $rid);
        $return = $this->sendDELETERequest();

        return $return['response'];
    }

    /**
     * @param $did
     * @return mixed
     */
    public function deleteDomain($did)
    {
        $this->setAPIResource('domains/' . $did);
        $return = $this->sendDELETERequest();

        return $return['response'];
    }

    /**
     * @param $domainname
     * @return int|mixed
     */
    public function getDomainID($domainname)
    {
        $tld = substr(strstr($domainname, '.'), 1);
        $sld = substr($domainname, 0, strpos($domainname, '.'));
        $this->setAPIResource('domains/' . $sld, true, 'tld=' . $tld);
        $return = $this->sendGETRequest();
        if (isset($return['response']->domain->id)) {
            return $return['response']->domain->id;
        }

        return 0;
    }

    /**
     * @return mixed
     */
    public function getTickets()
    {
        $this->setAPIResource('tickets');
        $return = $this->sendGETRequest();

        return $return['response'];
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getTicket($id)
    {
        $this->setAPIResource('tickets/' . $id);
        $return = $this->sendGETRequest();

        return $return['response'];
    }

    /**
     * @param $subject
     * @param $body
     * @param int $department
     * @return mixed
     */
    public function openTicket($subject, $body, $department = 1)
    {
        $this->setAPIResource('tickets');
        $json_request = [
            'subject' => $subject,
            'body' => $body,
            'department' => $department
        ];
        $return = $this->sendPOSTRequest($json_request);

        return $return['response'];
    }

    /**
     * @param $id
     * @param $body
     * @return mixed
     */
    public function replyTicket($id, $body)
    {
        $this->setAPIResource('tickets/' . $id . '/ticket_replies');
        $json_request = [
            'body' => $body
        ];
        $return = $this->sendPOSTRequest($json_request);

        return $return['response'];
    }

    /**
     * @param $id
     * @return mixed
     */
    public function closeTicket($id)
    {
        $this->setAPIResource('tickets/' . $id);
        $return = $this->sendDELETERequest();

        return $return['response'];
    }
}

/**
 * Node class
 *
 * Allows management of Nodes
 */
class Node
{
    /**
     * @var int
     */
    public $virtual_machine_id = 0;

    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $consumer_id = 0;

    /**
     * @var int
     */
    public $deleted = 0;

    /**
     * @var bool
     */
    public $free = false;

    /**
     * @var bool
     */
    public $discount = false;

    /**
     * @var string
     */
    public $type = 'vps';

    /**
     * Node constructor.
     *
     * @param int $id
     * @param int $virtual_machine_id
     * @param int $consumer_id
     * @param string $type
     * @param false $free
     * @param false $discount
     */
    public function __construct($id = 0, $virtual_machine_id = 0, $consumer_id = 0, $type = 'vps', $free = false, $discount = false)
    {
        $this->id = $id;
        $this->virtual_machine_id = $virtual_machine_id;
        $this->consumer_id = $consumer_id;
        $this->type = $type;
        $this->free = $free;
        $this->discount = $discount;
    }

    /**
     * @param $consumer_id
     * @return false
     */
    public function update($consumer_id)
    {
        $api = VPSNET::getInstance();
        if ($this->id < 1) {
            trigger_error("To call Node::remove() you must set its id", E_USER_ERROR);

            return false;
        }
        switch ($this->type) {
            case 'storage':
                $api->setAPIResource('nodes/' . $this->id . '/update_storage_node');
                break;
            case 'ram':
                $api->setAPIResource('nodes/' . $this->id . '/update_ram_node');
                break;
            default:
                $api->setAPIResource('nodes/' . $this->id);
                break;
        }
        $json_request['consumer_id'] = $consumer_id;
        $result = $api->sendPUTRequest($json_request);
    }

    /**
     * Removes Node from your account
     * @return boolean true if Node was deleted succesfully, false otherwise
     */
    public function remove()
    {
        $api = VPSNET::getInstance();
        if ($this->id < 1) {
            trigger_error("To call Node::remove() you must set its id", E_USER_ERROR);

            return false;
        }
        if ($this->virtual_machine_id > 0) {
            trigger_error("You cannot call Node::remove() with a node assigned to a virtual machine. Instead use VirtualMachine::update()", E_USER_ERROR);

            return false;
        }
        switch ($this->type) {
            case 'storage':
                $api->setAPIResource('nodes/' . $this->id . '/remove_storage_node');
                break;
            case 'ram':
                $api->setAPIResource('nodes/' . $this->id . '/remove_ram_node');
                break;
            default:
                $api->setAPIResource('nodes/' . $this->id);
                break;
        }
        $result = $api->sendDELETERequest();
        //                print_r($result);
        $this->deleted = ($result['info']['http_code'] == 200);

        return $this->deleted;
    }
}

/**
 * IP Address class
 *
 * Allows management of IP addresses
 */
class IPAddress
{
    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var string
     */
    public $netmask = '';

    /**
     * @var string
     */
    public $network = '';

    /**
     * @var int
     */
    public $cloud_id = 0;

    /**
     * @var string
     */
    public $ip_address = '';

    /**
     * @var int
     */
    public $consumer_id = 0;

    /**
     * @var bool
     */
    public $deleted = false;

    /**
     * IPAddress constructor.
     *
     * @param $id
     */
    private function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Use to find out if an IP address is Internal
     * @return boolean true if IP address is Internal, false otherwise
     */
    public function isInternal()
    {
        return ($cloud_id == 0);
    }

    /**
     * Use to find out if an IP address is External
     * @return boolean true if IP address is External, false otherwise
     */
    public function isExternal()
    {
        return ($cloud_id > 0);
    }

    /**
     * Removes IP address from your account
     * @return boolean true if IP address was deleted succesfully, false otherwise
     */
    public function remove()
    {
        $api = VPSNET::getInstance();
        if ($this->id < 1) {
            trigger_error("To call IPAddress::remove() you must set id", E_USER_ERROR);

            return false;
        }
        $api->setAPIResource('ip_address_assignments/' . $this->id);
        $result = $api->sendDELETERequest();
        $this->deleted = ($result['info']['http_code'] == 200);

        return $this->deleted;
    }
}

/**
 * Backups class
 *
 * Allows management of Backups
 */
class Backup
{
    /**
     * @var int
     */
    public $virtual_machine_id = 0;

    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var string
     */
    public $label = '';

    /**
     * @var
     */
    public $auto_backup_type;

    /**
     * @var bool
     */
    public $deleted = false;

    /**
     * Backup constructor.
     * @param int $id
     * @param int $virtual_machine_id
     */
    public function __construct($id = 0, $virtual_machine_id = 0)
    {
        $this->id = $id;
        $this->virtual_machine_id = $virtual_machine_id;
    }

    /**
     * Restores a backup
     * @return boolean true if backup restore request was succesful, false otherwise
     */
    public function restore()
    {
        $api = VPSNET::getInstance();
        if ($this->id < 1 || $this->virtual_machine_id < 1) {
            trigger_error("To call Backup::restore() you must set id and virtual_machine_id", E_USER_ERROR);

            return false;
        }
        $api->setAPIResource('virtual_machines/' . $this->virtual_machine_id . '/backups/' . $this->id . '/restore');
        $result = $api->sendPOSTRequest();

        return ($result['info']['http_code'] == 200);
    }

    /**
     * Removes a backup
     * @return boolean true if backup was removed, false otherwise
     */
    public function remove()
    {
        $api = VPSNET::getInstance();
        if ($this->id < 1 || $this->virtual_machine_id < 1) {
            trigger_error("To call Backup::remove() you must set id and virtual_machine_id", E_USER_ERROR);

            return false;
        }
        $api->setAPIResource('virtual_machines/' . $this->virtual_machine_id . '/backups/' . $this->id);
        $result = $api->sendDELETERequest();
        $this->deleted = ($result['info']['http_code'] == 200);

        return $this->deleted;
    }
}

/**
 * Upgrade Schedule class
 *
 * Allows management of Scheduled Upgrades
 */
class UpgradeSchedule
{
    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var string
     */
    public $label = '';

    /**
     * @var int
     */
    public $extra_slices = 0;

    /**
     * @var bool
     */
    public $temporary = false;

    /**
     * @var false|string
     */
    public $run_at;

    /**
     * @var int
     */
    public $days;

    /**
     * UpgradeSchedule constructor.
     *
     * @param $label
     * @param $extra_slices
     * @param $run_at
     * @param int $days
     */
    public function __construct($label, $extra_slices, $run_at, $days = 0)
    {
        $this->temporary = ($days > 0);
        $this->label = $label;
        $this->extra_slices = $extra_slices;
        $this->run_at = date_format('c', $run_at);

        if ($days > 0) {
            $this->days = $days;
        }
    }
}

/**
 * Virtual Machines class
 *
 * Allows management of Virtual Machines
 */
class VirtualMachine
{
    /**
     * @var string
     */
    public $label = '';

    /**
     * @var string
     */
    public $hostname = '';

    /**
     * @var string
     */
    public $domain_name = '';

    /**
     * @var int
     */
    public $slices_count = 0;

    /**
     * @var int|string
     */
    public $slices_required = 0;

    /**
     * @var int
     */
    public $ram_nodes_required = 0;

    /**
     * @var int|string
     */
    public $backups_enabled = 0;

    /**
     * @var int|string
     */
    public $system_template_id = 0;

    /**
     * @var int|string
     */
    public $cloud_id = 0;

    /**
     * @var
     */
    public $id;

    /**
     * @var int
     */
    public $consumer_id = 0;

    /**
     * @var string
     */
    public $created_at = null; // "2009-05-12T09:33:05-04:00"

    /**
     * @var string
     */
    public $updated_at = null; // "2009-05-12T09:33:05-04:00"

    /**
     * @var string
     */
    public $password = '';

    /**
     * @var array
     */
    public $backups = []; // call fullyLoad() to retrieve this

    /**
     * @var array
     */
    public $upgrade_schedules = []; // call fullyLoad() to retrieve this

    /**
     * @var bool
     */
    public $deleted = false;

    /**
     * @var null
     */
    public $system_template_name = null;

    /**
     * @var null
     */
    public $backup_licenses = null;

    /**
     * VirtualMachine constructor.
     *
     * @param string $label
     * @param string $hostname
     * @param string $slices_required
     * @param string $backups_enabled
     * @param string $cloud_id
     * @param string $system_template_id
     * @param int $consumer_id
     * @param int $storage_nodes_required
     * @param string $rsync_backups_enabled
     * @param string $r1_soft_backups_enabled
     * @param null $licence
     * @param int $ram_nodes_required
     */
    public function __construct($label = '', $hostname = '', $slices_required = '', $backups_enabled = '', $cloud_id = '', $system_template_id = '', $consumer_id = 0, $storage_nodes_required = 0, $rsync_backups_enabled = '', $r1_soft_backups_enabled = '', $licence = null, $ram_nodes_required = 0)
    {
        $this->label = $label;
        $this->hostname = $hostname;
        $this->slices_required = $slices_required;
        $this->storage_nodes_required = $storage_nodes_required;
        $this->ram_nodes_required = $ram_nodes_required;
        $this->backups_enabled = $backups_enabled;
        $this->rsync_backups_enabled = $rsync_backups_enabled;
        $this->r1_soft_backups_enabled = $r1_soft_backups_enabled;
        $this->cloud_id = $cloud_id;
        $this->system_template_id = $system_template_id;
        $this->consumer_id = $consumer_id;
        $this->licence = $licence;
    }

    /**
     * @param $action
     * @return $this
     * @throws Exception
     */
    private function _doAction($action)
    {
        $api = VPSNET::getInstance();
        $api->setAPIResource('virtual_machines/' . $this->id . '/' . $action);
        $result = $api->sendPOSTRequest();
        //		print_r($result);
        if ($result['info']['http_code'] != 200) {
            throw new Exception("Error performing action");
        } else if (isset($result['response'])) {
            foreach ($result['response']->virtual_machine as $key => $value) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    /**
     * Powers on a virtual machine
     * @return VirtualMachine Virtual Machine instance
     */
    public function powerOn()
    {
        return $this->_doAction('power_on');
    }

    /**
     * Powers off a virtual machine
     * @return VirtualMachine Virtual Machine instance
     */
    public function powerOff()
    {
        return $this->_doAction('power_off');
    }

    /**
     * Gracefully shuts down a virtual machine
     * @return VirtualMachine Virtual Machine instance
     */
    public function shutdown()
    {
        return $this->_doAction('shutdown');
    }

    /**
     * Reboots a virtual machine
     * @return VirtualMachine Virtual Machine instance
     */
    public function reboot()
    {
        return $this->_doAction('reboot');
    }

    /**
     * Resets the virtual machine password
     * @return VirtualMachine Virtual Machine instance
     */
    public function resetPassword()
    {
        return $this->_doAction('reset_password');
    }

    /**
     * Creates a backup
     * @param string $label Name of backup
     * @return Backup Backup instance
     */
    public function createBackup($label)
    {
        if (!is_string($label) || strlen($label) < 0) {
            trigger_error("To call VirtualMachine::createBackup() you must specify a label", E_USER_ERROR);

            return false;
        }
        $api = VPSNET::getInstance();
        $api->setAPIResource('virtual_machines/' . $this->id . '/backups');
        $json_request['backup']->label = $label;
        $result = $api->sendPOSTRequest($json_request);
        $return = null;
        if ($result['info']['http_code'] == 422) {
            // Do some error handling
        } else {
            $this->backups[] = $api->_castObjectToClass('Backup', $result['response']);
        }

        return $result['response_body'];
    }

    /**
     * Creates a temporary upgrade schedule
     * @param string $label Name of upgrade schedule
     * @param int $extra_slices Number of new nodes
     * @param date $run_at Date to run upgrade schedule
     * @param int $days Number of days to run upgrade schedule for
     * @return UpgradeSchedule instance
     */
    public function createTemporaryUpgradeSchedule($label, $extra_slices, $run_at, $days)
    {
        $bInputErrors = false;
        if (!is_string($label) || strlen($label) < 0) {
            trigger_error("To call VirtualMachine::createTemporaryUpgradeSchedule() you must specify a label", E_USER_ERROR);
            $bInputErrors = true;
        }
        if (!is_int($extra_slices)) {
            trigger_error("To call VirtualMachine::createTemporaryUpgradeSchedule() you must specify extra_slices as a number", E_USER_ERROR);
            $bInputErrors = true;
        }
        if (!is_int($days) || $days < 1) {
            trigger_error("To call VirtualMachine::createTemporaryUpgradeSchedule() you must specify days as a number greater than 0", E_USER_ERROR);
            $bInputErrors = true;
        }
        if ($bInputErrors) {
            return false;
        }
        $api = VPSNET::getInstance();
        $api->setAPIResource('virtual_machines/' . $this->id . '/backups');
        $json_request['backup']->label = $label;
        $result = $api->sendPOSTRequest($json_request);
        $return = null;
        if ($result['info']['http_code'] == 422) {
            // Do some error handling
        } else {
            $this->backups[] = $api->_castObjectToClass('Backup', $result['response']);
        }

        return $result['response'];
    }

    /**
     * Outputs a bandwidth usage graph to output stream
     * @param string $period Period of usage ('hourly', 'daily', 'weekly', 'monthly')
     */
    public function showNetworkGraph($period)
    {
        if (!in_array($period, [
            'hourly',
            'daily',
            'weekly',
            'monthly'
        ])) {
            trigger_error("To call VirtualMachine::getNetworkGraph() you must specify a period of hourly, daily, weekly or monthly", E_USER_ERROR);

            return false;
        }
        if ($this->cloud_info->cloud_version == 2) {
            $this->composeNetworkGraph($period);
            header('Content-type: Content-Type: image/png');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (2 * 3600)) . ' GMT');
            header('Cache-Control: max-age=3600, public');
            echo file_get_contents(dirname(__FILE__) . '/charts/' . $this->id . '-' . $period . '_usage.png');
        } else {
            return $this->showGraph($period, 'network');
        }
    }

    /**
     * @param string $period
     */
    public function composeNetworkGraph($period = 'hourly')
    {
        $iName = $this->id . '-' . $period . '_usage.png';
        $iPath = dirname(__FILE__) . '/charts/';
        if (file_exists($iPath . $iName)) {
            switch ($period) {
                default:
                    if ((time() - filemtime($iPath . $iName)) / 60 <= 5) {
                        return;
                    }
                    break;
                case 'daily':
                    if ((time() - filemtime($iPath . $iName)) / 60 <= 60) {
                        return;
                    }
                    break;
                case 'weekly':
                    if ((time() - filemtime($iPath . $iName)) / 60 <= 12 * 60) {
                        return;
                    }
                    break;
                case 'monthly':
                    if ((time() - filemtime($iPath . $iName)) / 60 <= 24 * 60) {
                        return;
                    }
                    break;
            }
        }
        $nu = $this->getNetworkUtilisation();
        $DataSet = new pData;
        $received = 0;
        $sent = 0;
        $name = "";
        switch ($period) {
            case 'hourly':
                $name = "Hourly Usage";
                $start = count($nu) - 24;
                if ($start < 0) {
                    $start = 0;
                }
                for ($i = $start; $i < count($nu); $i++) {
                    $data = $nu[$i];
                    $dsc = date('H:i', strtotime($data->created_at));
                    $DataSet->AddPoint((int) ($data->data_received / 1024), "Serie1", $dsc);
                    $DataSet->AddPoint((int) ($data->data_sent / 1024), "Serie2", $dsc);
                    $received += $data->data_received;
                    $sent += $data->data_sent;
                }
                break;
            case 'weekly':
                $name = "Weekly Usage";
                for ($i = -12; $i <= 0; $i++) {
                    $start = mktime(0, 0, 0, date('m'), date('d') + $i, date('Y'));
                    $end = mktime(23, 59, 59, date('m'), date('d') + $i, date('Y'));
                    $dsc = date('j D', $start);
                    $total_received = 0;
                    $total_sent = 0;
                    foreach ($nu as $data) {
                        $cur = strtotime($data->created_at);
                        if (($cur >= $start) && ($cur <= $end)) {
                            $total_received = $total_received + $data->data_received;
                            $total_sent = $total_sent + $data->data_sent;
                            $received += $data->data_received;
                            $sent += $data->data_sent;
                        }
                    }
                    $DataSet->AddPoint((int) ($total_received / 1024), "Serie1", $dsc);
                    $DataSet->AddPoint((int) ($total_sent / 1024), "Serie2", $dsc);
                }
                break;
            case 'daily':
                $name = "Daily Usage";
                for ($i = -6; $i <= 0; $i++) {
                    $start = mktime(0, 0, 0, date('m'), date('d') + $i, date('Y'));
                    $end = mktime(23, 59, 59, date('m'), date('d') + $i, date('Y'));
                    $dsc = date('j D', $start);
                    $total_received = 0;
                    $total_sent = 0;
                    foreach ($nu as $data) {
                        $cur = strtotime($data->created_at);
                        if (($cur >= $start) && ($cur <= $end)) {
                            $total_received = $total_received + $data->data_received;
                            $total_sent = $total_sent + $data->data_sent;
                            $received += $data->data_received;
                            $sent += $data->data_sent;
                        }
                    }
                    $DataSet->AddPoint((int) ($total_received / 1024), "Serie1", $dsc);
                    $DataSet->AddPoint((int) ($total_sent / 1024), "Serie2", $dsc);
                }
                break;
            case 'monthly':
                $name = "Monthly Usage";
                for ($i = -3; $i <= 0; $i++) {
                    $start = mktime(0, 0, 0, date('m') + $i, 1, date('Y'));
                    $end = mktime(0, 0, -1, date('m') + $i + 1, 1, date('Y'));
                    $dsc = date('M', $start);
                    $total_received = 0;
                    $total_sent = 0;
                    foreach ($nu as $data) {
                        $cur = strtotime($data->created_at);
                        if (($cur >= $start) && ($cur <= $end)) {
                            $total_received = $total_received + $data->data_received;
                            $total_sent = $total_sent + $data->data_sent;
                            $received += $data->data_received;
                            $sent += $data->data_sent;
                        }
                    }
                    $DataSet->AddPoint((int) ($total_received / 1024), "Serie1", $dsc);
                    $DataSet->AddPoint((int) ($total_sent / 1024), "Serie2", $dsc);
                }
                break;
        }
        $DataSet->AddAllSeries();
        $DataSet->SetAbsciseLabelSerie();
        $DataSet->SetYAxisUnit(' M');
        $DataSet->SetSerieName($this->formatBytes($received * 1024) . " received", "Serie1");
        $DataSet->SetSerieName($this->formatBytes($sent * 1024) . " sent", "Serie2");
        // Initialise the graph
        $Test = new pChart(650, 250);
        $Test->setFontProperties(dirname(__FILE__) . "/assets/fonts/tahoma.ttf", 8);
        $Test->setGraphArea(55, 30, 540, 200);
        $Test->drawScale($DataSet->GetData(), $DataSet->GetDataDescription(), SCALE_NORMAL, 150, 150, 150, true, 90, 0);
        $Test->drawGrid(4, true, 230, 230, 230, 50); //reshotka
        // Draw the line graph
        $Test->drawLineGraph($DataSet->GetData(), $DataSet->GetDataDescription());
        $Test->drawPlotGraph($DataSet->GetData(), $DataSet->GetDataDescription(), 3, 2, 255, 255, 255);
        // Finish the graph
        $Test->setFontProperties(dirname(__FILE__) . "/assets/fonts/tahoma.ttf", 8); // Description
        $Test->drawLegend(542, 30, $DataSet->GetDataDescription(), 255, 255, 255);
        $Test->setFontProperties(dirname(__FILE__) . "/assets/fonts/tahoma.ttf", 10);
        $Test->drawTitle(50, 22, $name, 50, 50, 50, 555);
        $Test->Render($iPath . $iName);
    }

    /**
     * @param string $from
     * @param string $to
     * @return mixed
     */
    public function getNetworkUtilisation($from = '', $to = '')
    {
        if (isset($this->NetworkUtilisation)) {
            return $this->NetworkUtilisation;
        }
        $gfrom = (!empty($from) ? 'from=' . $from : '');
        $gto = (!empty($to) ? '&to=' . $to : '');
        $api = VPSNET::getInstance();
        $api->setAPIResource('virtual_machines/' . $this->id . '/network_utilisation', true, $gfrom . $gto);
        $result = $api->sendGETRequest();
        $response_body = $result['response'];
        $this->NetworkUtilisation = $response_body;

        return $response_body;
    }

    /**
     * @return mixed
     */
    public function getCPUUsage()
    {
        if (isset($this->CPUUsage)) {
            return $this->CPUUsage;
        }
        $api = VPSNET::getInstance();
        if (!empty($from)) {
        }
        $gfrom = (!empty($from) ? 'from=' . $from : '');
        $gto = (!empty($to) ? '&to=' . $to : '');
        $api->setAPIResource('virtual_machines/' . $this->id . '/cpu_usage', true, $gfrom . $gto);
        $result = $api->sendGETRequest();
        $response_body = $result['response'];
        $this->CPUUsage = $response_body;

        return $response_body;
        //         /virtual_machines/xx/network_utilisation.api10json
    }

    /**
     * @param string $period
     */
    public function composeCPUUsageGraph($period = 'hourly')
    {
        $iPath = dirname(__FILE__) . '/charts/';
        $iName = $this->id . '-' . $period . '_cpu_usage.png';
        if (file_exists($iPath . $iName)) {
            switch ($period) {
                default:
                    if ((time() - filemtime($iPath . $iName)) / 60 <= 5) {
                        return;
                    }
                    break;
                case 'daily':
                    if ((time() - filemtime($iPath . $iName)) / 60 <= 60) {
                        return;
                    }
                    break;
                case 'weekly':
                    if ((time() - filemtime($iPath . $iName)) / 60 <= 12 * 60) {
                        return;
                    }
                    break;
                case 'monthly':
                    if ((time() - filemtime($iPath . $iName)) / 60 <= 24 * 60) {
                        return;
                    }
                    break;
            }
        }
        $cu = $this->getCPUUsage();
        $name = "CPU Usage";
        $DataSet = new pData;
        //            foreach ($nu as $data) {
        switch ($period) {
            default:
                $start = count($cu) - 24;
                $SkipLabels = 1;
                break;
            case 'daily':
                $start = count($cu) - 24 * 4;
                $SkipLabels = 4;
                break;
            case 'weekly':
                $start = count($cu) - 24 * 14;
                $SkipLabels = 12;
                break;
            case 'monthly':
                $start = count($cu) - 24 * 31 * 3;
                $SkipLabels = 24;
                break;
        }
        //$start=count($cu)-24*7;
        if ($start < 0) {
            $start = 0;
        }
        for ($i = $start; $i < count($cu); $i++) {
            $data = $cu[$i];
            //print_r($i);
            $dsc = date('H:i', strtotime($data->created_at));
            if (($dsc == '00:00') || ($i == $start)) {
                $dsc = date('D H:i', strtotime($data->created_at));
            }
            if ($period == 'daily') {
                $dsc = date('D H:i', strtotime($data->created_at));
            }
            if ($period == 'weekly') {
                $dsc = date('d H:i', strtotime($data->created_at));
            }
            if ($period == 'monthly') {
                $dsc = date('d M', strtotime($data->created_at));
            }
            $DataSet->AddPoint((($data->cpu_time / $data->elapsed_time) * 10), "Serie1", $dsc);
        }
        $DataSet->AddAllSeries();
        $DataSet->SetAbsciseLabelSerie();
        $DataSet->SetYAxisUnit(' %');
        $DataSet->SetSerieName($this->formatBytes($received * 1024) . " received", "Serie1");
        // Initialise the graph
        $Test = new pChart(650, 280);
        $Test->setFontProperties(dirname(__FILE__) . "/assets/fonts/tahoma.ttf", 8);
        $Test->setGraphArea(40, 30, 640, 200, true);
        $Test->drawScale($DataSet->GetData(), $DataSet->GetDataDescription(), SCALE_NORMAL, 150, 150, 150, true, 90, 0, false, $SkipLabels);
        // Draw the line graph
        $Test->drawLineGraph($DataSet->GetData(), $DataSet->GetDataDescription());
        // Finish the graph
        $Test->setFontProperties(dirname(__FILE__) . "/assets/fonts/tahoma.ttf", 10);
        $Test->drawTitle(50, 22, $name, 50, 50, 50, 585);
        $Test->Render(dirname(__FILE__) . '/charts/' . $iName);
    }

    /**
     * Outputs a CPU usage graph to output stream
     * @param string $period Period of usage ('hourly', 'daily', 'weekly', 'monthly')
     */
    public function showCPUGraph($period)
    {
        if (!in_array($period, [
            'hourly',
            'daily',
            'weekly',
            'monthly'
        ])) {
            trigger_error("To call VirtualMachine::getCPUGraph() you must specify a period of hourly, daily, weekly or monthly", E_USER_ERROR);

            return false;
        }
        if ($this->cloud_info->cloud_version == 2) {
            $ipath = dirname(__FILE__) . '/charts/' . $this->id . '-' . $period . '_cpu_usage.png';
            //exit;
            $this->composeCPUUsageGraph($period);
            header('Content-type: Content-Type: image/png');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (2 * 3600)) . ' GMT');
            header('Cache-Control: max-age=3600, public');
            echo file_get_contents($ipath);
        } else {
            return $this->showGraph($period, 'cpu');
        }
    }

    /**
     * @param $period
     * @param $type
     * @return array
     */
    protected function showGraph($period, $type)
    {
        $api = VPSNET::getInstance();
        $api->setAPIResource('virtual_machines/' . $this->id . '/' . $type . '_graph', false, 'period=' . $period);
        $result = $api->sendGETRequest();
        $response_body = $result['response_body'];
        header('Content-type: ' . $result['info']['content_type']);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (2 * 3600)) . ' GMT');
        header('Cache-Control: max-age=3600, public');
        echo($result['response_body']);

        return $result;
    }

    /**
     * @param int $w
     * @param int $h
     * @param int $s
     */
    public function getVNCConsole($w = 810, $h = 630, $s = 100)
    {
        $api = VPSNET::getInstance();
        $api->setAPIResource('virtual_machines/' . $this->id . '/console');
        $result = $api->sendGETRequest();
        $console = $result['response']->session;
        echo "
                    <div class='onapp_console'>
                      <applet archive='https://www.vps.net/vnc.jar' code='VncViewer.class' codebase=\"https://www.vps.net/vnc/\" height='$h' width='$w'>
                        <param name='PORT' value='" . $console->port . "' />
                        <param name='PASSWORD' value='" . $console->password . "' />
                            <PARAM NAME=\"Scaling factor\" VALUE=$s>
                      </applet>
                    </div>";
    }

    /**
     * @return false|mixed
     */
    public function getConsole()
    {
        $api = VPSNET::getInstance();
        $api->setAPIResource('virtual_machines/' . $this->id . '/console');
        $result = $api->sendGETRequest();
        if (is_object($result['response'])) {
            return $result['response']->session;
        } else {
            return false;
        }
    }

    /**
     * Outputs a Console to output stream
     */
    public function showConsole()
    {
        $api = VPSNET::getInstance();
        if ($this->cloud_info->cloud_version == 2) {
            echo '<html><body>';
            $this->getVNCConsole();
            echo '</body></html>';

            return;
        }
        $urlpath = substr($_SERVER['PATH_INFO'], 1);
        $api->setAPIResource('virtual_machines/' . $this->id . '/console_proxy/' . $urlpath, false);
        $response_body = $result['response_body'];
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $requestdata = 'k=' . urlencode($_POST['k']) . '&';
            $requestdata .= 'w=' . urlencode($_POST['w']) . '&';
            $requestdata .= 'c=' . urlencode($_POST['c']) . '&';
            $requestdata .= 'h=' . urlencode($_POST['h']) . '&';
            $requestdata .= 's=' . urlencode($_POST['s']) . '&';
            $result = $api->sendPOSTRequest($requestdata, false);
            header('Content-type: ' . $result['info']['content_type']);
            echo($result['response_body']);
        } else {
            $result = $api->sendGETRequest();
            if (strpos($urlpath, '.css')) {
                header('Content-type: text/css');
            } else {
                header('Content-type: ' . $result['info']['content_type']);
            }
            echo($result['response_body']);
        }

        return $result;
    }

    /**
     * Retrieves a list of backups and adds it to backups property of current instance
     * @return array Array of Backups instances
     */
    public function loadBackups()
    {
        $api = VPSNET::getInstance();
        $api->setAPIResource('virtual_machines/' . $this->id . '/backups');
        $result = $api->sendGETRequest();
        if ($result['info']['http_code'] == 422) {
            // Do some error handling
        } else {
            $this->backups = [];
            $response = $result['response'];
            for ($x = 0; $x < count($response); $x++) {
                $this->backups[$x] = $api->_castObjectToClass('Backup', $response[$x]);
            }
        }

        return $this->backups;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function loadFully($id = 0)
    {
        if ($id) {
            $this->id = $id;
        }
        $api = VPSNET::getInstance();
        $api->setAPIResource('virtual_machines/' . $this->id);
        $result = $api->sendGETRequest();
        if ($result['info']['http_code'] == 422) {
            // Do some error handling
        } else {
            if (is_object($result['response']->virtual_machine)) {
                foreach ($result['response']->virtual_machine as $key => $value) {
                    $this->$key = $value;
                }
            }
        }
        if ($this->cloud_id) {
            $this->cloud_info = $api->getCloud($this->cloud_id);
        }
        if (@is_array($this->cloud_info->system_templates)) {
            foreach ($this->cloud_info->system_templates as $template) {
                if ($template->id == $this->system_template_id) {
                    $this->system_template_name = $template->label;
                }
            }
        }
        if (!$this->system_template_name) {
            $tinfo = $api->getTemplateInfo($this->cloud_id, $this->system_template_id);
            $this->system_template_name = $tinfo->label;
        }
        $this->rsync_backups_enabled = ($this->backup_licenses && property_exists($this->backup_licenses, "rsync_license") && $this->backup_licenses->rsync_license ? true : false);
        $this->r1_soft_backups_enabled = ($this->backup_licenses && property_exists($this->backup_licenses, "r1soft_license") && $this->backup_licenses->r1soft_license ? true : false);
        $this->any_backup_enabled = $this->backups_enabled || $this->rsync_backups_enabled || $this->r1_soft_backups_enabled;

        return $this;
    }

    /**
     * Updates virtual machine
     * @return boolean True if update succeeded, false otherwise
     */
    public function update()
    {
        $api = VPSNET::getInstance();
        if ($this->id < 1) {
            throw new Exception("To call VirtualMachine::update() you must set it's id first");
        }
        $api->setAPIResource('virtual_machines/' . $this->id);
        $requestdata['label'] = $this->label;
        $requestdata['slices_required'] = $this->slices_required ? $this->slices_required : $this->slices_count;
        $requestdata['consumer_id'] = $this->consumer_id;
        $requestdata['backups_enabled'] = (int) $this->backups_enabled;
        $requestdata['rsync_backups_enabled'] = (int) $this->rsync_backups_enabled;
        $requestdata['r1_soft_backups_enabled'] = (int) $this->r1_soft_backups_enabled;
        $requestdata['storage_nodes_required'] = isset($this->storage_nodes_required) ? (int) $this->storage_nodes_required : (int) $this->storage_nodes_count;
        $requestdata['ram_nodes_required'] = isset($this->ram_nodes_required) ? (int) $this->ram_nodes_required : (int) $this->ram_nodes_count;

        if (!empty($this->hostname)) {
            $requestdata['fqdn'] = $this->hostname;
            $requestdata['public_fqdn'] = $this->hostname;
        }

        if (is_array($this->licence)) {
            $requestdata['licenses'] = $this->licence;
        }
        $json_request['virtual_machine'] = $requestdata;
        $result = $api->sendPUTRequest($json_request);

        if ($result['info']['http_code'] == 200) {
            return true;
        }
        if (isset($result['errors']) && isset($result['errors']->errors) && count($result['errors']->errors) > 0) {
            $errors = [];
            foreach ($result['errors']->errors as $error) {
                $errors[] = "{$error[0]}: {$error[1]}, ";
            }
            $errors = implode(", ", $errors);
            throw new Exception($errors);
        }
        throw new Exception("Unknown error");
    }

    /**
     * Removes a virtual machine
     * @return boolean true if virtual machine was removed, false otherwise
     */
    public function remove($remove_nodes = false)
    {
        if ($this->running) {
            $this->powerOff();
        }
        do {
            usleep(2000000);
            $this->loadFully();
        } while ($this->running);
        $dellus = [];
        $api = VPSNET::getInstance();
        if ($remove_nodes) {
            $nodes = $api->getNodes($consumer_id = 0, $type = 'vps');
            foreach ($nodes as $node) {
                if ($node->virtual_machine_id == $this->id) {
                    $dellus[] = $node;
                }
            }
            $nodes = $api->getNodes($consumer_id = 0, $type = 'storage');
            foreach ($nodes as $node) {
                if ($node->virtual_machine_id == $this->id) {
                    $dellus[] = $node;
                }
            }
            $nodes = $api->getNodes($consumer_id = 0, $type = 'ram');
            foreach ($nodes as $node) {
                if ($node->virtual_machine_id == $this->id) {
                    $dellus[] = $node;
                }
            }
        }
        if ($this->id < 1) {
            trigger_error("To call VirtualMachine::remove() you must set its id", E_USER_ERROR);

            return false;
        }
        $api->setAPIResource('virtual_machines/' . $this->id);
        $result = $api->sendDELETERequest();
        $this->deleted = ($result['info']['http_code'] == 200);
        foreach ($dellus as $node) {
            $node->virtual_machine_id = 0;
            $node->remove(true);
        }

        return $this->deleted;
    }

    /**
     * @return bool
     */
    public function recover()
    {
        if ($this->id < 1) {
            trigger_error("To call VirtualMachine::restore() you must set its id", E_USER_ERROR);

            return false;
        }
        $api = VPSNET::getInstance();
        $api->setAPIResource('virtual_machines/' . $this->id . '/recover');
        $result = $api->sendPOSTRequest();

        return ($result['info']['http_code'] == 200);
    }

    /**
     * @param $system_template_id
     * @return bool
     * @throws Exception
     */
    public function reinstall($system_template_id)
    {
        $api = VPSNET::getInstance();
        if ($this->id < 1) {
            throw new Exception("To call VirtualMachine::update() you must set it's id first");
        }
        if ($this->running) {
            $this->powerOff();
        }
        do {
            usleep(2000000);
            $this->loadFully();
        } while ($this->running);
        $api->setAPIResource('virtual_machines/' . $this->id . '/reinstall');
        $requestdata['system_template_id'] = $system_template_id;
        $json_request['virtual_machine'] = $requestdata;
        $result = $api->sendPUTRequest($json_request);

        return ($result['info']['http_code'] == 200 ? true : false);
    }

    /**
     * @param $consumer_id
     * @return bool
     * @throws Exception
     */
    public function setConsumer($consumer_id)
    {
        $api = VPSNET::getInstance();
        if ($this->id < 1) {
            throw new Exception("To call VirtualMachine::update() you must set it's id first");
        }
        $this->consumer_id = $consumer_id;
        foreach ($api->getNodes(0, 'vps') as $node) {
            if ($node->virtual_machine_id == $this->id) {
                $node->update($consumer_id);
            }
        }
        foreach ($api->getNodes(0, 'storage') as $node) {
            if ($node->virtual_machine_id == $this->id) {
                $node->update($consumer_id);
            }
        }
        foreach ($api->getNodes(0, 'ram') as $node) {
            if ($node->virtual_machine_id == $this->id) {
                $node->update($consumer_id);
            }
        }

        return $this->update();
    }

    /**
     * @return string
     */
    public function getCpanel()
    {
        $cpanel = 'none';
        if (is_array($this->virtual_machine_licenses)) {
            foreach ($this->virtual_machine_licenses as $linfo) {
                if ($linfo->virtual_machine_license->license_id == 1) {
                    $cpanel = 'cpanel';
                }
                if ($linfo->virtual_machine_license->license_id == 6) {
                    $cpanel = 'isp manager';
                }
            }
        }

        return $cpanel;
    }

    /**
     * @return array
     */
    public function getIpAddresses()
    {
        $api = VPSNET::getInstance();
        $ips = $api->getIpAddresses();
        $return = [];
        $return['external'] = [];
        $return['internal'] = [];
        foreach ($ips as $ip) {
            if (($ip->virtual_machine_id == $this->id) || ($ip->description == 'for virtual machine #' . $this->id)) {
                if ($ip->cloud_id) {
                    $return['external'][] = $ip;
                } else {
                    $return['internal'][] = $ip;
                }
            }
        }

        return $return;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function deleteIP($id)
    {
        $api = VPSNET::getInstance();
        $ips = $this->getIpAddresses();
        $ips = array_merge($ips['external'], $ips['internal']);
        foreach ($ips as $ip) {
            if ($id == $ip->id) {
                return $api->deleteIP($id);
            }
        }
    }

    /**
     * @param $bytes
     * @param int $precision
     * @return string
     */
    public function formatBytes($bytes, $precision = 1)
    {
        $kilobyte = 1024;
        $megabyte = $kilobyte * 1024;
        $gigabyte = $megabyte * 1024;
        $terabyte = $gigabyte * 1024;
        if (($bytes >= 0) && ($bytes < $kilobyte)) {
            return $bytes . ' B';
        } else if (($bytes >= $kilobyte) && ($bytes < $megabyte)) {
            return round($bytes / $kilobyte, $precision) . ' KB';
        } else if (($bytes >= $megabyte) && ($bytes < $gigabyte)) {
            return round($bytes / $megabyte, $precision) . ' MB';
        } else if (($bytes >= $gigabyte) && ($bytes < $terabyte)) {
            return round($bytes / $gigabyte, $precision) . ' GB';
        } else if ($bytes >= $terabyte) {
            return round($bytes / $gigabyte, $precision) . ' TB';
        } else {
            return $bytes . ' B';
        }
    }
}

class CustomTemplate
{
    /**
     * @var int
     */
    public $template_id;

    /**
     * @var string
     */
    public $label;

    /**
     * @var string
     */
    public $description;

    /**
     * @var int
     */
    public $vmid;

    /**
     * @var int
     */
    public $backup_id;

    /**
     * @var int
     */
    public $user_id;

    /**
     * @var bool
     */
    public $reseller;

    /**
     * CustomTemplate constructor.
     *
     * @param null $template_id
     * @param string $label
     * @param string $description
     * @param int $vmid
     */
    public function __construct($template_id = null, $label = '', $description = '', $vmid = 0)
    {
        $this->template_id = $template_id;
        $this->label = $label;
        $this->description = $description;
        $this->vmid = $vmid;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function create()
    {
        if ($this->vmid < 1) {
            throw new Exception("To call CustomTemplate::create() you must set it's id first");
        }
        $api = VPSNET::getInstance();
        $api->setAPIResource('virtual_machines/' . $this->vmid . '/backups/' . $this->backup_id . '/convert');
        $res = $api->sendPOSTRequest();
        if ($res['info']['http_code'] === 200) {
            return true;
        }

        return false;
    }
}
