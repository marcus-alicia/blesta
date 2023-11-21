<?php
/**
 * ISPConfig API.
 *
 * @package blesta
 * @subpackage blesta.components.modules.ispconfig
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class IspconfigApi
{
    /**
     * @var string The server hostname
     */
    private $hostname;

    /**
     * @var string The ISPConfig username
     */
    private $username;

    /**
     * @var string The ISPConfig password
     */
    private $password;

    /**
     * @var bool Use ssl in all the api requests
     */
    private $use_ssl;

    /**
     * @var bool The port on which to connect
     */
    private $port;

    /**
     * @var string Remote server session ID
     */
    private $session_id;

    /**
     * Initializes the class.
     *
     * @param mixed $hostname The ISPConfig hostname or IP Address
     * @param mixed $username The remote username
     * @param mixed $password The remote password
     * @param mixed $use_ssl True to connect to the api using SSL
     * @param mixed $port The ISPConfig port
     */
    public function __construct($hostname, $username, $password, $use_ssl = false, $port = '8080')
    {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->use_ssl = $use_ssl;
        $this->port = $port;
    }

    /**
     * Send a request to the ISPConfig API.
     *
     * @param string $function Specifies the api function to invoke
     * @param array $params The parameters to include in the api
     * @return stdClass An object containing the api response
     */
    public function apiRequest($function, array $params = [])
    {
        // Set API location
        $soap_location = ($this->use_ssl ? 'https' : 'http') . '://' . $this->hostname . ':' . $this->port . '/remote/index.php';
        $soap_uri = ($this->use_ssl ? 'https' : 'http') . '://' . $this->hostname . ':' . $this->port . '/remote/';

        // Create SOAP connection
        $client = new SoapClient(null, [
            'location' => $soap_location,
            'uri' => $soap_uri,
            'trace' => 1,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ])
        ]);

        try {
            // Login to the remote server
            if ($this->session_id = $client->login($this->username, $this->password)) {
                return call_user_func_array([$client, $function], array_merge([$this->session_id], $params));
            }
        } catch (SoapFault $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Creates a new client in the server.
     *
     * @param array $params An array containing the following arguments:
     *  - contact_name: Specifies the client name.
     *  - company_name: Specifies the client company. (optional)
     *  - customer_no: Specifies the client customer number. (optional)
     *  - username: Specifies the account username.
     *  - password: Specifies the password for this account.
     *  - email: Specifies the client email address.
     *  - street: Specifies the client street. (optional)
     *  - zip: Specifies the client zip. (optional)
     *  - city: Specifies the client city. (optional)
     *  - state: Specifies the client state. (optional)
     *  - country: Specifies the client country. (optional)
     *  - template_master: Specifies the limits template (a.k.a. package) for the account.
     *  - web_php_options: A numerical indexed array containing the PHP options enabled for the account.
     *  - ssh_chroot: A numerical indexed array containing the SSH options enabled for the account.
     * @param int $reseller_id Specifies the reseller id for this account
     * @return int The new client ID
     */
    public function createClient($params, $reseller_id = 0)
    {
        $limit_params = [];
        if (isset($params['template_master']) && $params['template_master'] != 0) {
            $limit_params = [
                // Web Limits
                'limit_web_domain' => -1,
                'limit_web_quota' => -1,
                'limit_traffic_quota' => -1,
                'limit_web_subdomain' => -1,
                'limit_web_aliasdomain' => -1,
                'limit_ftp_user' => -1,
                'limit_shell_user' => 0,
                'limit_webdav_user' => 0,
                // Email limits
                'limit_maildomain' => -1,
                'limit_mailbox' => -1,
                'limit_mailalias' => -1,
                'limit_mailaliasdomain' => -1,
                'limit_mailforward' => -1,
                'limit_mailcatchall' => -1,
                'limit_mailrouting' => 0,
                'limit_mailfilter' => -1,
                'limit_fetchmail' => -1,
                'limit_mailquota' => -1,
                'limit_spamfilter_wblist' => 0,
                'limit_spamfilter_user' => 0,
                'limit_spamfilter_policy' => 1,
                // Cron Job Limits
                'limit_cron' => 0,
                'limit_cron_type' => 'url',
                'limit_cron_frequency' => 5,
                // DNS Servers
                'limit_dns_zone' => -1,
                'limit_dns_slave_zone' => -1,
                'limit_dns_record' => -1,
                'limit_database' => -1,
                // Client Limits
                'limit_client' => 0,
            ];
        }

        $default_params = [
            'company_name' => '',
            'contact_name' => '',
            'customer_no' => '',
            'vat_id' => '',
            'street' => '',
            'zip' => '',
            'city' => '',
            'state' => '',
            'country' => '',
            'telephone' => '',
            'mobile' => '',
            'fax' => '',
            'email' => '',
            'internet' => '',
            'icq' => '',
            'notes' => '',
            'default_mailserver' => 1,
            'default_webserver' => 1,
            'limit_web_ip' => '',
            'web_php_options' => 'no,fast-cgi,cgi,mod,suphp',
            'ssh_chroot' => 'no,jailkit,ssh-chroot',
            'default_dnsserver' => 1,
            'default_dbserver' => 1,
            'parent_client_id' => 0,
            'username' => '',
            'password' => '',
            'language' => 'en',
            'usertheme' => 'default',
            'template_master' => 0,
            'template_additional' => '',
            'created_at' => 0
        ];

        return $this->apiRequest(
            'client_add',
            [$reseller_id, array_merge($limit_params, array_merge($default_params, $params))]
        );
    }

    /**
     * Deletes an existing client in the server.
     *
     * @param int $client_id The client id
     * @return bool True if the user has been deleted successfully, false otherwise
     */
    public function deleteClient($client_id)
    {
        return (bool) $this->apiRequest('client_delete_everything', [$client_id]);
    }

    /**
     * Gets an existing client in the server.
     *
     * @param int $client_id The client id
     * @return array An array containing all the client information
     */
    public function getClient($client_id)
    {
        return $this->apiRequest('client_get', [$client_id]);
    }

    /**
     * Get all the existing clients in the server.
     *
     * @return array An array containing all the clients
     */
    public function getAllClients()
    {
        return $this->apiRequest('client_get_all');
    }

    /**
     * Updates the information of an existing client.
     *
     * @param int $client_id The client id
     * @param array $params An array containing the following arguments:
     *  - contact_name: Specifies the client name.
     *  - company_name: Specifies the client company. (optional)
     *  - customer_no: Specifies the client customer number. (optional)
     *  - username: Specifies the account username.
     *  - password: Specifies the password for this account.
     *  - email: Specifies the client email address.
     *  - street: Specifies the client street. (optional)
     *  - zip: Specifies the client zip. (optional)
     *  - city: Specifies the client city. (optional)
     *  - state: Specifies the client state. (optional)
     *  - country: Specifies the client country. (optional)
     *  - template_master: Specifies the limits template (a.k.a. package) for the account.
     *  - web_php_options: A numerical indexed array containing the PHP options enabled for the account.
     *  - ssh_chroot: A numerical indexed array containing the SSH options enabled for the account.
     * @param int $reseller_id Specifies the reseller id for this account
     * @return bool True if the user has been updated successfully, false otherwise
     */
    public function updateClient($client_id, $params, $reseller_id = 0)
    {
        $client = $this->getClient($client_id);

        // Only works in ISPConfig 3.0.3+
        return $this->apiRequest('client_update', [$client_id, $reseller_id, array_merge($client, $params)]);
    }

    /**
     * Updates the password of an existing client.
     *
     * @param int $client_id The client id
     * @param string $password The new client password
     * @param int $reseller_id Specifies the reseller id for this account
     * @return bool True if the user has been updated successfully, false otherwise
     */
    public function updateClientPassword($client_id, $password, $reseller_id = 0)
    {
        return (bool) $this->apiRequest(
            'client_update',
            [
                $client_id,
                $reseller_id,
                [
                    'password' => $password
                ]
            ]
        );
    }

    /**
     * Get the client ID by the username.
     *
     * @param string $client_username The client username
     * @return int The client id
     */
    public function getClientIdByUsername($client_username)
    {
        $user = $this->apiRequest('client_get_by_username', [$client_username]);

        return isset($user['client_id']) ? $user['client_id'] : 0;
    }

    /**
     * Get all the available PHP options.
     *
     * @return array An array containing the available PHP options
     */
    public function getPhpOptions()
    {
        return [
            'no' => 'No PHP',
            'fast-cgi' => 'Fast-CGI',
            'cgi' => 'CGI',
            'mod' => 'Mod-PHP',
            'suphp' => 'SuPHP',
            'php-fpm' => 'PHP-FPM',
            'hhvm' => 'HHVM'
        ];
    }

    /**
     * Get all the available SSH options.
     *
     * @return array An array containing the available SSH options
     */
    public function getSshOptions()
    {
        return [
            'no' => 'No SSH',
            'jailkit' => 'Jailkit',
            'ssh-chroot' => 'SSH-chroot'
        ];
    }

    /**
     * Get all the client templates.
     *
     * @param string $type The type of the client templates, can be "main" (a.k.a packages), "addon" and "all"
     * @return array An array contaning all the requested client templates
     */
    public function getAllLimitsTemplates($type = 'main')
    {
        $client_templates = $this->apiRequest('client_templates_get_all');
        $templates_list = ['0' => 'Custom'];

        foreach ($client_templates as $template) {
            if ($type == 'main' && $template['template_type'] == 'm'
                || $type == 'addon' && $template['template_type'] == 'a'
                || $type == 'all'
            ) {
                $templates_list[$template['template_id']] = $template['template_name'];
            }
        }

        return $templates_list;
    }

    /**
     * Get the actual server ID.
     *
     * @return int The actual server id
     */
    public function getCurrentServerId()
    {
        $default_server = $this->apiRequest('server_get_all');
        $server = $this->apiRequest('server_get_serverid_by_ip', [gethostbyname($this->hostname)]);
        $server_id = isset($server[0]['server_id']) ? $server[0]['server_id'] : $default_server[0]['server_id'];

        return $server_id;
    }

    /**
     * Adds a site to a client account.
     *
     * @param int $client_id The client id
     * @param string $domain The site domain
     * @return bool True if the site has been added successfully, false otherwise
     */
    public function addSite($client_id, $domain)
    {
        $client = $this->getClient($client_id);

        return (bool) $this->apiRequest(
            'sites_web_domain_add',
            [
                $client_id,
                [
                    'server_id' => $this->getCurrentServerId(),
                    'ip_address' => '*',
                    'domain' => $domain,
                    'type' => 'vhost',
                    'cgi' => 'y',
                    'ssi' => 'y',
                    'suexec' => 'y',
                    'is_subdomainwww' => true,
                    'php' => 'y',
                    'hd_quota' => $client['limit_web_quota'],
                    'traffic_quota' => $client['limit_traffic_quota'],
                    'allow_override' => 'All',
                    'pm_process_idle_timeout' => 10000,
                    'pm_max_requests' => 100,
                    'http_port' => 80,
                    'https_port' => 443,
                    'active' => 'y'
                ]
            ]
        );
    }
}
