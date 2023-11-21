<?php
use Blesta\Core\Util\Validate\Server;
/**
 * SolusVM Module
 *
 * @package blesta
 * @subpackage blesta.components.modules.solusvm
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Solusvm extends Module
{
    /**
     * @var array Encrypted service field names
     */
    private $encrypted_fields = [
        'solusvm_console_password', 'solusvm_vnc_password', 'solusvm_root_password', 'solusvm_password'
    ];

    /**
     * Initializes the module
     */
    public function __construct()
    {
        // Load configuration required by this module
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('solusvm', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load config
        Configure::load('solusvm', dirname(__FILE__) . DS . 'config' . DS);
    }

    /**
     * Performs any necessary bootstraping actions. Sets Input errors on
     * failure, preventing the module from being added.
     *
     * @return array A numerically indexed array of meta data containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function install()
    {
        $errors = [];
        // Ensure the the system meets the requirements for this module
        if (!extension_loaded('simplexml')) {
            $errors['simplexml'] = ['required' => Language::_('SolusVM.!error.simplexml_required', true)];
        }

        if (!empty($errors)) {
            $this->Input->setErrors($errors);
            return;
        }
    }

    /**
     * Attempts to validate service info. This is the top-level error checking method. Sets Input errors on failure.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @return bool True if the service validates, false otherwise. Sets Input errors when false.
     */
    public function validateService($package, array $vars = null)
    {
        // Set rules
        $rules = [
            'solusvm_hostname' => [
                'format' => [
                    'pre_format' => [[$this, 'replaceText'], '', "/^\s*www\./i"],
                    'rule' => [[$this, 'validateHostName'], true],
                    'message' => Language::_('Solusvm.!error.solusvm_hostname.format', true)
                ]
            ],
            'solusvm_vserver_id' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => Language::_('Solusvm.!error.solusvm_vserver_id.format', true)
                ]
            ]
        ];

        // Template must be given if it can be set by the client
        if (isset($package->meta->set_template) && $package->meta->set_template == 'client' &&
            isset($package->meta->type)) {
            $rules['solusvm_template'] = [
                'valid' => [
                    'rule' => [
                        [$this, 'validateTemplate'],
                        $package->meta->type,
                        $package->module_row,
                        $package->module_group
                    ],
                    'message' => Language::_('Solusvm.!error.solusvm_template.valid', true)
                ]
            ];
        }

        // Virtual Server ID is not required on add
        if (empty($vars['solusvm_vserver_id'])) {
            unset($rules['solusvm_vserver_id']);
        }

        $this->Input->setRules($rules);
        return $this->Input->validates($vars);
    }

    /**
     * Attempts to validate an existing service against a set of service info updates. Sets Input errors on failure.
     *
     * @param stdClass $service A stdClass object representing the service to validate for editing
     * @param array $vars An array of user-supplied info to satisfy the request
     * @return bool True if the service update validates or false otherwise. Sets Input errors when false.
     */
    public function validateServiceEdit($service, array $vars = null)
    {
        // Set rules
        $rules = [
            'solusvm_hostname' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'replaceText'], '', "/^\s*www\./i"],
                    'rule' => [[$this, 'validateHostName'], true],
                    'message' => Language::_('Solusvm.!error.solusvm_hostname.format', true)
                ]
            ],
            'solusvm_vserver_id' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => Language::_('Solusvm.!error.solusvm_vserver_id.format', true)
                ]
            ]
        ];

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Template must be given if it can be set by the client
        if (isset($service_fields->solusvm_template)
            && isset($vars['solusvm_template'])
            && $service_fields->solusvm_template != $vars['solusvm_template']
            && isset($service_fields->solusvm_type)
        ) {
            $rules['solusvm_template'] = [
                'valid' => [
                    'if_set' => true,
                    'rule' => [
                        [$this, 'validateTemplate'],
                        $service_fields->solusvm_type,
                        $service->package->module_row,
                        $service->package->module_group
                    ],
                    'message' => Language::_('Solusvm.!error.solusvm_template.valid', true)
                ]
            ];
        }

        if (isset($vars['use_module']) && $vars['use_module'] == 'true' && $service_fields) {
            // Require valid IP addresses to remove be given if decreasing the custom extra IPs
            if (!empty($service_fields->solusvm_extra_ip_addresses)) {
                $extra_ips = $this->csvToArray($service_fields->solusvm_extra_ip_addresses, true);
                $remove_ips = (isset($vars['solusvm_remove_extra_ips'])
                    ? (array)$vars['solusvm_remove_extra_ips']
                    : []
                );

                $rules['configoptions[customextraip]'] = [
                    'valid' => [
                        'if_set' => true,
                        'rule' => [[$this, 'validateRemovingExtraIps'], $extra_ips, $remove_ips],
                        'message' => Language::_('Solusvm.!error.configoptions[customextraip].valid', true)
                    ]
                ];
            }
        }

        $this->Input->setRules($rules);
        return $this->Input->validates($vars);
    }

    /**
     * Adds the service to the remote server. Sets Input errors on failure,
     * preventing the service from being added.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being added (if the current service is an addon
     *  service and parent service has already been provisioned)
     * @param string $status The status of the service being added. These include:
     *  - active
     *  - canceled
     *  - pending
     *  - suspended
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function addService(
        $package,
        array $vars = null,
        $parent_package = null,
        $parent_service = null,
        $status = 'pending'
    ) {
        // Load the API
        $row = $this->getModuleRow();
        $api = $this->getApi($row->meta->user_id, $row->meta->key, $row->meta->host, $row->meta->port);

        // Get the fields for the service
        $params = $this->getFieldsFromInput($vars, $package);

        // Validate the service-specific fields
        $this->validateService($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Since validating the service rules does not update data in pre/post formatting,
        // re-apply the formatting changes manually
        if (isset($vars['solusvm_hostname'])) {
            $vars['solusvm_hostname'] = strtolower($this->replaceText($vars['solusvm_hostname'], '', "/^\s*www\./i"));
            $params['hostname'] = $vars['solusvm_hostname'];
        }

        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            $client_id = (isset($vars['client_id']) ? $vars['client_id'] : '');

            // Create a new client (if one does not already exist)
            $client = $this->createClient($client_id, $params['username'], $row);

            if ($this->Input->errors()) {
                return;
            }

            $api_fields = ['type', 'node', 'nodegroup', 'hostname', 'password', 'username', 'plan', 'template',
                'ips', 'randomipv4', 'hvmt', 'custommemory', 'customdiskspace', 'custombandwidth', 'customcpu',
                'customextraip', 'issuelicense', 'internalip', 'rdtype'];

            // Attempt to create the virtual server
            $api->loadCommand('solusvm_vserver');
            try {
                // Load up the Virtual Server API
                $vserver_api = new SolusvmVserver($api);
                $filtered_params = array_intersect_key($params, array_flip($api_fields));
                $masked_params = $filtered_params;
                $masked_params['password'] = '***';

                // Create the Virtual Server
                $this->log($row->meta->host . '|vserver-create', serialize($masked_params), 'input', true);
                $response = $this->parseResponse($vserver_api->create($filtered_params), $row);
            } catch (Exception $e) {
                // Internal Error
                $this->Input->setErrors(['api' => ['internal' => Language::_('Solusvm.!error.api.internal', true)]]);
            }

            if ($this->Input->errors()) {
                return;
            }
        }

        // Retrieve the extra IPs to use as extra base IPs
        $extra_ips = isset($response->extraipaddress) ? $response->extraipaddress : null;
        // Subtract the 1 used as the main IP address
        $ips = $this->splitExtraIps($extra_ips, ($params['ips'] - 1));

        // Return service fields
        $fields = [
            [
                'key' => 'solusvm_vserver_id',
                'value' => (isset($response->vserverid)
                    ? $response->vserverid
                    : (!empty($vars['solusvm_vserver_id']) ? $vars['solusvm_vserver_id'] : null)
                ),
                'encrypted' => 0
            ],
            [
                'key' => 'solusvm_main_ip_address',
                'value' => isset($response->mainipaddress) ? $response->mainipaddress : null,
                'encrypted' => 0
            ],
            [
                'key' => 'solusvm_extra_ip_addresses',
                'value' => $this->arrayToCsv($ips['extra']),
                'encrypted' => 0
            ],
            [
                'key' => 'solusvm_base_ip_addresses',
                'value' => $this->arrayToCsv($ips['base']),
                'encrypted' => 0
            ],
            [
                'key' => 'solusvm_console_user',
                'value' => isset($response->consoleuser) ? $response->consoleuser : null,
                'encrypted' => 0
            ],
            [
                'key' => 'solusvm_console_password',
                'value' => isset($response->consolepassword) ? $response->consolepassword : null,
                'encrypted' => 1
            ],
            [
                'key' => 'solusvm_virt_id',
                'value' => isset($response->virtid) ? $response->virtid : null,
                'encrypted' => 0
            ],
            [
                'key' => 'solusvm_internal_ip',
                'value' => isset($response->internalip) ? $response->internalip : null,
                'encrypted' => 0
            ],
            [
                'key' => 'solusvm_vnc_ip',
                'value' => isset($response->vncip) ? $response->vncip : null,
                'encrypted' => 0
            ],
            [
                'key' => 'solusvm_vnc_port',
                'value' => isset($response->vncport) ? $response->vncport : null,
                'encrypted' => 0
            ],
            [
                'key' => 'solusvm_vnc_password',
                'value' => isset($response->vncpassword) ? $response->vncpassword : null,
                'encrypted' => 1
            ],
            [
                'key' => 'solusvm_root_password',
                'value' => isset($response->rootpassword) ? $response->rootpassword : $params['password'],
                'encrypted' => 1
            ],
            [
                'key' => 'solusvm_hostname',
                'value' => $params['hostname'],
                'encrypted' => 0
            ],
            [
                'key' => 'solusvm_node',
                'value' => (isset($response->nodeid) ? $response->nodeid : null),
                'encrypted' => 0
            ],
            [
                'key' => 'solusvm_type',
                'value' => $params['type'],
                'encrypted' => 0
            ],
            [
                'key' => 'solusvm_username',
                'value' => $params['username'],
                'encrypted' => 0
            ],
            [
                'key' => 'solusvm_password',
                'value' => (isset($client['password']) ? $client['password'] : null),
                'encrypted' => 1
            ],
            [
                'key' => 'solusvm_plan',
                'value' => $params['plan'],
                'encrypted' => 0
            ],
            [
                'key' => 'solusvm_template',
                'value' => $params['template'],
                'encrypted' => 0
            ]
        ];

        // Ensure all available encrypted fields are set to be encrypted
        foreach ($fields as &$field) {
            if (in_array($field['key'], $this->encrypted_fields)) {
                $field['encrypted'] = 1;
            }
        }

        return $fields;
    }

    /**
     * Edits the service on the remote server. Sets Input errors on failure,
     * preventing the service from being edited.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being edited (if the current service is an addon service)
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function editService($package, $service, array $vars = [], $parent_package = null, $parent_service = null)
    {
        // Load the API
        $row = $this->getModuleRow();
        $api = $this->getApi($row->meta->user_id, $row->meta->key, $row->meta->host, $row->meta->port);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Fetch the current service plan
        $plan = null;
        $type = (isset($service_fields->solusvm_type) ? $service_fields->solusvm_type : '');
        if (isset($service_fields->solusvm_plan)) {
            $plan = $this->getPlan($type, $service_fields->solusvm_plan, $row);
        }

        // Set config option totals from the ServiceOptions
        $vars['configoptions'] = (isset($vars['configoptions']) ? (array)$vars['configoptions'] : []);
        $ServiceOptions = $this->loadServiceOptions($type);
        $vars['configoptions'] = array_merge(
            $vars['configoptions'],
            $ServiceOptions->getAll($vars['configoptions'], $plan)
        );

        // Validate the service-specific fields
        $this->validateServiceEdit($service, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Since validating the service rules does not update data in pre/post formatting,
        // re-apply the formatting changes manually
        if (isset($vars['solusvm_hostname'])) {
            $vars['solusvm_hostname'] = strtolower($this->replaceText($vars['solusvm_hostname'], '', "/^\s*www\./i"));
        }

        // Check for fields that changed
        $delta = [];
        foreach ($vars as $key => $value) {
            if (!array_key_exists($key, $service_fields) || $vars[$key] != $service_fields->$key) {
                $delta[$key] = $value;
            }
        }

        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            // Reinstall template
            if (isset($vars['solusvm_template']) && isset($vars['confirm_reinstall']) && $vars['confirm_reinstall']) {
                $data = ['template' => $vars['solusvm_template']];
                if (!$this->performAction('rebuild', $service_fields->solusvm_vserver_id, $row, $data)) {
                    $this->Input->setErrors(
                        ['api' => ['internal' => Language::_('Solusvm.!error.api.internal', true)]]
                    );
                } else {
                    $service_fields->solusvm_template = $vars['solusvm_template'];
                }
            }

            // Update hostname (if changed)
            if (isset($delta['solusvm_hostname'])) {
                $data = ['hostname' => $delta['solusvm_hostname']];
                if (!$this->performAction('hostname', $service_fields->solusvm_vserver_id, $row, $data)) {
                    $this->Input->setErrors(
                        ['api' => ['internal' => Language::_('Solusvm.!error.api.internal', true)]]
                    );
                } else {
                    $service_fields->solusvm_hostname = $delta['solusvm_hostname'];
                }
            }

            // Update root password (if changed)
            if (isset($delta['solusvm_root_password'])) {
                $data = ['rootpassword' => $delta['solusvm_root_password']];
                if (!$this->performAction('rootpassword', $service_fields->solusvm_vserver_id, $row, $data)) {
                    $this->Input->setErrors(
                        ['api' => ['internal' => Language::_('Solusvm.!error.api.internal', true)]]
                    );
                } else {
                    $service_fields->solusvm_root_password = $delta['solusvm_root_password'];
                }
            }

            // Update config option fields
            if (isset($vars['configoptions'])) {
                $this->updateServiceOptions($vars, $service_fields, $plan, $row);

                // Re-fetch all of the extra IP addresses
                $service_fields->solusvm_extra_ip_addresses = $this->arrayToCsv(
                    $this->getExtraIps($service_fields, $row)
                );
            }
        }

        // Set virtual server ID if changed
        if (array_key_exists('solusvm_vserver_id', $delta)) {
            $service_fields->solusvm_vserver_id = $delta['solusvm_vserver_id'];
        }

        // Return all the service fields
        $fields = [];
        foreach ($service_fields as $key => $value) {
            $fields[] = [
                'key' => $key,
                'value' => $value,
                'encrypted' => (in_array($key, $this->encrypted_fields) ? 1 : 0)
            ];
        }

        return $fields;
    }

    /**
     * Updates a service to change config options fields on the server
     *
     * @param array $vars An array of input data, including:
     *  - configoptions An array of key/value pairs
     *  - * any other fields
     * @param stdClass $service_options An stdClass object representing the service's fields
     * @param stdClass $plan The plan details from the service
     * @param stdClass $module_row An stdClass object representing a single server
     */
    private function updateServiceOptions(array $vars, $service_fields, $plan, $module_row)
    {
        if (!isset($vars['configoptions'])) {
            return;
        }

        // Set any config options
        $config_options = (array)$vars['configoptions'];
        $ServiceOptions = $this->loadServiceOptions($service_fields->solusvm_type);
        $config_fields = $ServiceOptions->getAll($config_options, $plan);

        // Update server limits from config options
        $this->updateServerLimits($config_fields, $service_fields->solusvm_vserver_id, $module_row);

        // Update the custom extra IP field
        $this->updateExtraIps($vars, $service_fields, $module_row);
    }

    /**
     * Updates server setting limits for bandwidth, memory, hard disk size, and CPU count
     *
     * @param array $fields An array of service option data
     * @param int $server_id The ID of the virtual server
     * @param stdClass $module_row An stdClass object representing a single server
     */
    private function updateServerLimits(array $fields, $server_id, $module_row)
    {
        // Update bandwidth
        if (isset($fields['custombandwidth'])) {
            $this->updateBandwidth($fields['custombandwidth'], $server_id, $module_row);
        }

        // Update memory
        if (isset($fields['custommemory'])) {
            // Memory field must delimit swap/burst memory by vertical bar
            $memory = str_replace(':', '|', $fields['custommemory']);
            $this->updateMemory($memory, $server_id, $module_row);
        }

        // Update hard disk
        if (isset($fields['customdiskspace'])) {
            $this->updateHardDisk($fields['customdiskspace'], $server_id, $module_row);
        }

        // Update CPUs
        if (isset($fields['customcpu'])) {
            $this->updateCpu($fields['customcpu'], $server_id, $module_row);
        }
    }

    /**
     * Updates a server's bandwidth limit
     *
     * @param int $bandwidth The total bandwidth in GB to set for the server
     * @param int $server_id The ID of the virtual server
     * @param stdClass $module_row An stdClass object representing a single server
     */
    private function updateBandwidth($bandwidth, $server_id, $module_row)
    {
        $data = ['limit' => $bandwidth];
        if (!$this->performAction('changeBandwidth', $server_id, $module_row, $data)) {
            $this->Input->setErrors(['api' => ['internal' => Language::_('Solusvm.!error.api.internal', true)]]);
        }
    }

    /**
     * Updates a server's memory limit
     *
     * @param mixed $memory The memory in MB to set for the server. May also contain burst/swap memory
     * @param int $server_id The ID of the virtual server
     * @param stdClass $module_row An stdClass object representing a single server
     */
    private function updateMemory($memory, $server_id, $module_row)
    {
        $data = ['memory' => $memory];
        if (!$this->performAction('changeMemory', $server_id, $module_row, $data)) {
            $this->Input->setErrors(['api' => ['internal' => Language::_('Solusvm.!error.api.internal', true)]]);
        }
    }

    /**
     * Updates a server's hard disk space limit
     *
     * @param int $disk_space The disk space in GB to set for the server
     * @param int $server_id The ID of the virtual server
     * @param stdClass $module_row An stdClass object representing a single server
     */
    private function updateHardDisk($disk_space, $server_id, $module_row)
    {
        $data = ['hdd' => $disk_space];
        if (!$this->performAction('changeHdd', $server_id, $module_row, $data)) {
            $this->Input->setErrors(['api' => ['internal' => Language::_('Solusvm.!error.api.internal', true)]]);
        }
    }

    /**
     * Updates a server's usable CPU limit
     *
     * @param int $cpus The number of CPUs set for the server
     * @param int $server_id The ID of the virtual server
     * @param stdClass $module_row An stdClass object representing a single server
     */
    private function updateCpu($cpus, $server_id, $module_row)
    {
        $data = ['cpu' => $cpus];
        if (!$this->performAction('changeCpu', $server_id, $module_row, $data)) {
            $this->Input->setErrors(['api' => ['internal' => Language::_('Solusvm.!error.api.internal', true)]]);
        }
    }

    /**
     * Updates a service to change its extra IPs on the server
     *
     * @param array $vars An array of input data, including:
     *  - configoptions An array of key/value pairs
     *  - * any other fields
     * @param stdClass $service_fields An stdClass object representing the service's fields
     * @param stdClass $module_row An stdClass object representing a single server
     */
    private function updateExtraIps(array $vars, $service_fields, $module_row)
    {
        // Update the custom extra IP field
        if (isset($vars['configoptions']['customextraip'])) {
            // Add any additional extra IPs
            $extra_ips = $this->getExtraIps($service_fields, $module_row);
            $num_extra_ips = count($extra_ips);
            $num_selected_extra_ips = (int)$vars['configoptions']['customextraip'];

            for ($i=0; $i<($num_selected_extra_ips - $num_extra_ips); $i++) {
                $this->addExtraIp($service_fields->solusvm_vserver_id, $module_row);
            }

            // Remove any IPs set to be removed
            if (!empty($vars['solusvm_remove_extra_ips']) && is_array($vars['solusvm_remove_extra_ips']) &&
                !empty($extra_ips)) {
                // Filter out the IPs being removed
                foreach ($extra_ips as $index => $ip) {
                    if (in_array($ip, $vars['solusvm_remove_extra_ips'])) {
                        $this->removeExtraIp($ip, $service_fields->solusvm_vserver_id, $module_row);
                    }
                }
            }
        }
    }

    /**
     * Adds an extra IP address to the server
     *
     * @param int $server_id The ID of the virtual server
     * @param stdClass $module_row An stdClass object representing a single server
     */
    private function addExtraIp($server_id, $module_row)
    {
        if (!$this->performAction('addIp', $server_id, $module_row)) {
            $this->Input->setErrors(['api' => ['internal' => Language::_('Solusvm.!error.api.internal', true)]]);
        }
    }

    /**
     * Removes an extra IP address from the server
     *
     * @param string $ip The IP address to remove
     * @param int $server_id The ID of the virtual server
     * @param stdClass $module_row An stdClass object representing a single server
     */
    private function removeExtraIp($ip, $server_id, $module_row)
    {
        $data = ['ipaddr' => $ip];
        if (!$this->performAction('deleteIp', $server_id, $module_row, $data)) {
            $this->Input->setErrors(['api' => ['internal' => Language::_('Solusvm.!error.api.internal', true)]]);
        }
    }

    /**
     * Cancels the service on the remote server. Sets Input errors on failure,
     * preventing the service from being canceled.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being canceled (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function cancelService($package, $service, $parent_package = null, $parent_service = null)
    {
        if (($row = $this->getModuleRow())) {
            $api = $this->getApi($row->meta->user_id, $row->meta->key, $row->meta->host, $row->meta->port);
            $api->loadCommand('solusvm_vserver');

            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Attempt to terminate the virtual server
            try {
                // Load up the Virtual Server API
                $vserver_api = new SolusvmVserver($api);
                $params = ['vserverid' => $service_fields->solusvm_vserver_id, 'deleteclient' => 'false'];

                // Terminate the Virtual Server
                $this->log($row->meta->host . '|vserver-terminate', serialize($params), 'input', true);
                $response = $this->parseResponse($vserver_api->terminate($params), $row);
            } catch (Exception $e) {
                // Internal Error
                $this->Input->setErrors(['api' => ['internal' => Language::_('Solusvm.!error.api.internal', true)]]);
                return;
            }
        }
        return null;
    }

    /**
     * Suspends the service on the remote server. Sets Input errors on failure,
     * preventing the service from being suspended.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being suspended (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function suspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        // Suspend the service by shutting the server down
        $response = null;

        if (($row = $this->getModuleRow())) {
            $api = $this->getApi($row->meta->user_id, $row->meta->key, $row->meta->host, $row->meta->port);

            // Get the service fields
            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Load the virtual server API
            $api->loadCommand('solusvm_vserver');

            try {
                $server_api = new SolusvmVserver($api);
                $params = ['vserverid' => $service_fields->solusvm_vserver_id];

                $this->log($row->meta->host . '|vserver-suspend', serialize($params), 'input', true);
                $response = $this->parseResponse($server_api->suspend($params), $row);
            } catch (Exception $e) {
                // Nothing to do
                return;
            }
        }

        return null;
    }

    /**
     * Unsuspends the service on the remote server. Sets Input errors on failure,
     * preventing the service from being unsuspended.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being unsuspended (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function unsuspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        // Unsuspend the service by booting it up and releasing the suspension lock
        $response = null;

        if (($row = $this->getModuleRow())) {
            $api = $this->getApi($row->meta->user_id, $row->meta->key, $row->meta->host, $row->meta->port);

            // Get the service fields
            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Load the virtual server API
            $api->loadCommand('solusvm_vserver');

            try {
                $server_api = new SolusvmVserver($api);
                $params = ['vserverid' => $service_fields->solusvm_vserver_id];

                $this->log($row->meta->host . '|vserver-unsuspend', serialize($params), 'input', true);
                $response = $this->parseResponse($server_api->unsuspend($params), $row);
            } catch (Exception $e) {
                // Nothing to do
                return;
            }
        }

        return null;
    }

    /**
     * Allows the module to perform an action when the service is ready to renew.
     * Sets Input errors on failure, preventing the service from renewing.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being renewed (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function renewService($package, $service, $parent_package = null, $parent_service = null)
    {
        // Nothing to do
        return null;
    }

    /**
     * Updates the package for the service on the remote server. Sets Input
     * errors on failure, preventing the service's package from being changed.
     *
     * @param stdClass $package_from A stdClass object representing the current package
     * @param stdClass $package_to A stdClass object representing the new package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being changed (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function changeServicePackage(
        $package_from,
        $package_to,
        $service,
        $parent_package = null,
        $parent_service = null
    ) {
        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $new_plan = null;

        if (($row = $this->getModuleRow())) {
            // Include the virtual server API
            $api = $this->getApi($row->meta->user_id, $row->meta->key, $row->meta->host, $row->meta->port);
            $api->loadCommand('solusvm_vserver');

            // Only request a package change if it has changed
            if ($package_from->meta->plan != $package_to->meta->plan) {
                // Attempt to change the virtual server plan
                try {
                    // Load up the Virtual Server API
                    $vserver_api = new SolusvmVserver($api);
                    $plan = $package_to->meta->plan;
                    $params = ['vserverid' => $service_fields->solusvm_vserver_id, 'plan' => $plan];

                    // Change the Virtual Server Plan
                    $this->log($row->meta->host . '|vserver-change', serialize($params), 'input', true);
                    $response = $this->parseResponse($vserver_api->change($params), $row);

                    if ($response && $response->status == 'success') {
                        $new_plan = $plan;
                    }
                } catch (Exception $e) {
                    // Internal Error
                    $this->Input->setErrors(
                        ['api' => ['internal' => Language::_('Solusvm.!error.api.internal', true)]]
                    );
                    return;
                }
            }
        }

        // Return all the service fields
        $fields = [];
        foreach ($service_fields as $key => $value) {
            // Set the value of the new plan
            if ($key == 'solusvm_plan' && $new_plan) {
                $value = $new_plan;
            }

            $fields[] = [
                'key' => $key,
                'value' => $value,
                'encrypted' => (in_array($key, $this->encrypted_fields) ? 1 : 0)
            ];
        }

        return $fields;
    }

    /**
     * Validates input data when attempting to add a package, returns the meta
     * data to save when adding a package. Performs any action required to add
     * the package on the remote server. Sets Input errors on failure,
     * preventing the package from being added.
     *
     * @param array An array of key/value pairs used to add the package
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function addPackage(array $vars = null)
    {
        // Allow only nodes or a node group to be set
        if (isset($vars['meta']['set_node'])) {
            if ($vars['meta']['set_node'] == '0') {
                unset($vars['meta']['nodes']);
            } else {
                unset($vars['meta']['node_group']);
            }

            unset($vars['meta']['set_node']);
        }

        $this->Input->setRules($this->getPackageRules($vars));

        $meta = [];
        if ($this->Input->validates($vars)) {
            // Return all package meta fields
            foreach ($vars['meta'] as $key => $value) {
                $meta[] = [
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                ];
            }
        }

        return $meta;
    }

    /**
     * Validates input data when attempting to edit a package, returns the meta
     * data to save when editing a package. Performs any action required to edit
     * the package on the remote server. Sets Input errors on failure,
     * preventing the package from being edited.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array An array of key/value pairs used to edit the package
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function editPackage($package, array $vars = null)
    {
        // Allow only nodes or a node group to be set
        if (isset($vars['meta']['set_node'])) {
            if ($vars['meta']['set_node'] == '0') {
                unset($vars['meta']['nodes']);
            } else {
                unset($vars['meta']['node_group']);
            }

            unset($vars['meta']['set_node']);
        }

        $this->Input->setRules($this->getPackageRules($vars));

        $meta = [];
        if ($this->Input->validates($vars)) {
            // Return all package meta fields
            foreach ($vars['meta'] as $key => $value) {
                $meta[] = [
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                ];
            }
        }

        return $meta;
    }

    /**
     * Deletes the package on the remote server. Sets Input errors on failure,
     * preventing the package from being deleted.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function deletePackage($package)
    {
        // Nothing to do
        return null;
    }

    /**
     * Returns the rendered view of the manage module page
     *
     * @param mixed $module A stdClass object representing the module and its rows
     * @param array $vars An array of post data submitted to or on the manage
     *  module page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the manager module page
     */
    public function manageModule($module, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'solusvm' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set('module', $module);

        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the add module row page
     *
     * @param array $vars An array of post data submitted to or on the add
     *  module row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the add module row page
     */
    public function manageAddRow(array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('add_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'solusvm' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set('vars', (object)$vars);
        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the edit module row page
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of post data submitted to or on the edit
     *  module row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the edit module row page
     */
    public function manageEditRow($module_row, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('edit_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'solusvm' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        if (empty($vars)) {
            $vars = $module_row->meta;
        }

        $this->view->set('vars', (object)$vars);
        return $this->view->fetch();
    }

    /**
     * Adds the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being added.
     *
     * @param array $vars An array of module info to add
     * @return array A numerically indexed array of meta fields for the module row containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function addModuleRow(array &$vars)
    {
        $meta_fields = ['server_name', 'user_id', 'key', 'host', 'port'];
        $encrypted_fields = ['user_id', 'key'];

        $this->Input->setRules($this->getRowRules($vars));

        // Validate module row
        if ($this->Input->validates($vars)) {
            $vars['host'] = strtolower($vars['host']);

            // Build the meta data for this row
            $meta = [];
            foreach ($vars as $key => $value) {
                if (in_array($key, $meta_fields)) {
                    $meta[] = [
                        'key' => $key,
                        'value' => $value,
                        'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                    ];
                }
            }

            return $meta;
        }
    }

    /**
     * Edits the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being updated.
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of module info to update
     * @return array A numerically indexed array of meta fields for the module row containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function editModuleRow($module_row, array &$vars)
    {
        // Same as adding
        return $this->addModuleRow($vars);
    }

    /**
     * Deletes the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being deleted.
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     */
    public function deleteModuleRow($module_row)
    {
        return null; // Nothing to do
    }

    /**
     * Returns an array of available service delegation order methods. The module
     * will determine how each method is defined. For example, the method "first"
     * may be implemented such that it returns the module row with the least number
     * of services assigned to it.
     *
     * @return array An array of order methods in key/value pairs where the
     *  key is the type to be stored for the group and value is the name for that option
     * @see Module::selectModuleRow()
     */
    public function getGroupOrderOptions()
    {
        return ['first'=>Language::_('Solusvm.order_options.first', true)];
    }

    /**
     * Determines which module row should be attempted when a service is provisioned
     * for the given group based upon the order method set for that group.
     *
     * @return int The module row ID to attempt to add the service with
     * @see Module::getGroupOrderOptions()
     */
    public function selectModuleRow($module_group_id)
    {
        if (!isset($this->ModuleManager)) {
            Loader::loadModels($this, ['ModuleManager']);
        }

        $group = $this->ModuleManager->getGroup($module_group_id);

        if ($group) {
            switch ($group->add_order) {
                default:
                case 'first':

                    foreach ($group->rows as $row) {
                        return $row->id;
                    }

                    break;
            }
        }
        return 0;
    }

    /**
     * Returns all fields used when adding/editing a package, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getPackageFields($vars = null)
    {
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Fetch all packages available for the given server or server group
        $module_row = $this->getModuleRowByServer(
            (isset($vars->module_row) ? $vars->module_row : 0),
            (isset($vars->module_group) ? $vars->module_group : '')
        );

        $templates = [];
        $nodes = [];
        $node_groups = [];
        $plans = [];

        // Load more server info when the type is set
        if ($module_row && !empty($vars->meta['type'])) {
            // Load nodes and plans
            $plans = $this->getPlans($vars->meta['type'], $module_row);
            $nodes = $this->getNodes($vars->meta['type'], $module_row);
            $node_groups = $this->getNodeGroups($vars->meta['type'], $module_row);

            // Load templates
            if (isset($vars->meta['set_template']) && $vars->meta['set_template'] == 'admin') {
                $templates = $this->getTemplates($vars->meta['type'], $module_row);
            }
        }

        // Remove nodes from 'available' if they are currently 'assigned'
        if (isset($vars->meta['nodes'])) {
            $this->assignGroups($nodes, $vars->meta['nodes']);

            // Set the node value as the node key
            $temp = [];
            foreach ($vars->meta['nodes'] as $key => $value) {
                $temp[$value] = $value;
            }
            $vars->meta['nodes'] = $temp;
            unset($temp, $key, $value);

            // Individual nodes are assigned
            if (!empty($vars->meta['nodes'])) {
                $vars->meta['set_node'] = 1;
            }
        }

        $fields = new ModuleFields();

        // Show nodes, and set javascript field toggles
        $this->Form->setOutput(true);
        $fields->setHtml('
			<table>
				<tr>
					<td>' . Language::_('Solusvm.package_fields.assigned_nodes', true) . '</td>
					<td></td>
					<td>' . Language::_('Solusvm.package_fields.available_nodes', true) . '</td>
				</tr>
				<tr>
					<td>
						'
                    . $this->Form->fieldMultiSelect(
                        'meta[nodes][]',
                        (isset($vars->meta['nodes']) ? $vars->meta['nodes'] : []),
                        [],
                        ['id' => 'assigned_nodes']
                    )
                    . '
					</td>
					<td><a href="#" class="move_left">&nbsp;</a> &nbsp; <a href="#" class="move_right">&nbsp;</a></td>
					<td>
						'
                    . $this->Form->fieldMultiSelect(
                        'available_nodes[]',
                        (isset($nodes) ? $nodes : []),
                        [],
                        ['id' => 'available_nodes']
                    )
                    . "
					</td>
				</tr>
			</table>

			<script type=\"text/javascript\">
				$(document).ready(function() {
					toggleSolusvmFields();

					$('.solusvm_chosen_template').change(function() {
						toggleSolusvmTemplates();
						selectAssignedNodes();
						fetchModuleOptions();
					});

					$('#solusvm_type, .solusvm_set_node').change(function() {
						fetchModuleOptions();
					});

					// Select all assigned groups on submit
					$('#assigned_nodes').closest('form').submit(function() {
						selectAssignedNodes();
					});

					// Move nodes from right to left
					$('.move_left').click(function() {
						$('#available_nodes option:selected').appendTo($('#assigned_nodes'));
						return false;
					});
					// Move nodes from left to right
					$('.move_right').click(function() {
						$('#assigned_nodes option:selected').appendTo($('#available_nodes'));
						return false;
					});
				});

				function selectAssignedNodes() {
					$('#assigned_nodes option').attr('selected', 'selected');
				}

				function toggleSolusvmFields() {
					// Hide fields dependent on this value
					if ($('#solusvm_type').val() == '') {
						$('#solusvm_client_set_template').parent('li').hide();
						$('#solusvm_template').parent('li').hide();
						$('#assigned_nodes').closest('table').hide();
						$('#solusvm_plan').parent('li').hide();
					}
					// Show fields dependent on this value
					else {
						toggleSolusvmTemplates();
						toggleSolusvmNodes();
						$('#solusvm_client_set_template').parent('li').show();
						$('#solusvm_plan').parent('li').show();
					}
				}

				function toggleSolusvmTemplates() {
					if ($('input[name=\"meta[set_template]\"]:checked').val() == 'admin')
						$('#solusvm_template').parent('li').show();
					else
						$('#solusvm_template').parent('li').hide();
				}

				function toggleSolusvmNodes() {
					var set_node = $('input[name=\"meta[set_node]\"]:checked');
					if (typeof set_node === 'undefined')
						return;

					if ($(set_node).val() == '0') {
						$('#assigned_nodes').closest('table').hide();
						$('#solusvm_node_group').parent('li').show();
					}
					else {
						$('#assigned_nodes').closest('table').show();
						$('#solusvm_node_group').parent('li').hide();
					}
				}
			</script>
		");

        // Set base IP address count
        $total_base_ip_addresses = $fields->label(
            Language::_('Solusvm.package_fields.total_base_ip_addresses', true),
            'total_base_ip_addresses'
        );
        $total_base_ip_addresses->attach(
            $fields->fieldText(
                'meta[total_base_ip_addresses]',
                (isset($vars->meta['total_base_ip_addresses']) ? $vars->meta['total_base_ip_addresses'] : 1),
                ['id' => 'solusvm_total_base_ip_addresses']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Solusvm.package_fields.tooltip.total_base_ip_addresses', true));
        $total_base_ip_addresses->attach($tooltip);
        $fields->setField($total_base_ip_addresses);

        // Set the SolusVM type as a selectable option
        $types = ['' => Language::_('Solusvm.please_select', true)] + $this->getTypes();
        $type = $fields->label(Language::_('Solusvm.package_fields.type', true), 'solusvm_type');
        $type->attach(
            $fields->fieldSelect(
                'meta[type]',
                $types,
                (isset($vars->meta['type']) ? $vars->meta['type'] : null),
                ['id' => 'solusvm_type']
            )
        );
        $fields->setField($type);
        unset($type);

        // Set field whether client or admin may choose template
        $set_template = $fields->label(
            Language::_('Solusvm.package_fields.set_template', true),
            'solusvm_client_set_template'
        );
        $admin_set_template = $fields->label(
            Language::_('Solusvm.package_fields.admin_set_template', true),
            'solusvm_admin_set_template'
        );
        $client_set_template = $fields->label(
            Language::_('Solusvm.package_fields.client_set_template', true),
            'solusvm_client_set_template'
        );
        $set_template->attach(
            $fields->fieldRadio(
                'meta[set_template]',
                'client',
                (isset($vars->meta['set_template']) ? $vars->meta['set_template'] : 'client') == 'client',
                ['id' => 'solusvm_client_set_template', 'class' => 'solusvm_chosen_template'],
                $client_set_template
            )
        );
        $set_template->attach(
            $fields->fieldRadio(
                'meta[set_template]',
                'admin',
                (isset($vars->meta['set_template']) ? $vars->meta['set_template'] : null) == 'admin',
                ['id' => 'solusvm_admin_set_template', 'class' => 'solusvm_chosen_template'],
                $admin_set_template
            )
        );
        $fields->setField($set_template);

        // Set templates that admin may choose from
        $template = $fields->label(Language::_('Solusvm.package_fields.template', true), 'solusvm_template');
        $template->attach(
            $fields->fieldSelect(
                'meta[template]',
                $templates,
                (isset($vars->meta['template']) ? $vars->meta['template'] : null),
                ['id' => 'solusvm_template']
            )
        );
        $fields->setField($template);

        // Set plan
        $plan = $fields->label(Language::_('Solusvm.package_fields.plan', true), 'solusvm_plan');
        $plan->attach(
            $fields->fieldSelect(
                'meta[plan]',
                $plans,
                (isset($vars->meta['plan']) ? $vars->meta['plan'] : null),
                ['id' => 'solusvm_plan']
            )
        );
        $fields->setField($plan);

        // Set field whether to use nodes or node groups
        $set_node = $fields->label('', '');
        $node = $fields->label(Language::_('Solusvm.package_fields.set_node', true), 'set_node');
        $node_group = $fields->label(Language::_('Solusvm.package_fields.set_node_group', true), 'set_node_group');
        $set_node->attach(
            $fields->fieldRadio(
                'meta[set_node]',
                '0',
                (isset($vars->meta['set_node']) ? $vars->meta['set_node'] : '0') == '0',
                ['id' => 'set_node_group', 'class' => 'solusvm_set_node'],
                $node_group
            )
        );
        $set_node->attach(
            $fields->fieldRadio(
                'meta[set_node]',
                '1',
                (isset($vars->meta['set_node']) ? $vars->meta['set_node'] : null) == '1',
                ['id' => 'set_node', 'class' => 'solusvm_set_node'],
                $node
            )
        );
        $fields->setField($set_node);

        // Set field for node groups
        $groups = ['' => Language::_('Solusvm.please_select', true)] + $node_groups;
        $node_group = $fields->label(Language::_('Solusvm.package_fields.node_group', true), 'node_group');
        $node_group->attach(
            $fields->fieldSelect(
                'meta[node_group]',
                $groups,
                (isset($vars->meta['node_group']) ? $vars->meta['node_group'] : null),
                ['id' => 'solusvm_node_group']
            )
        );
        $fields->setField($node_group);

        return $fields;
    }

    /**
     * Returns all fields to display to an admin attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as well
     *  as any additional HTML markup to include
     */
    public function getAdminAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        // Fetch the module row available for this package
        $module_row = $this->getModuleRowByServer(
            (isset($package->module_row) ? $package->module_row : 0),
            (isset($package->module_group) ? $package->module_group : '')
        );

        $fields = new ModuleFields();

        // Create hostname label
        $host_name = $fields->label(Language::_('Solusvm.service_field.solusvm_hostname', true), 'solusvm_hostname');
        // Create hostname field and attach to hostname label
        $host_name->attach(
            $fields->fieldText(
                'solusvm_hostname',
                (isset($vars->solusvm_hostname) ? $vars->solusvm_hostname : null),
                ['id' => 'solusvm_hostname']
            )
        );
        // Set the label as a field
        $fields->setField($host_name);

        // Set the template if it can be set by the client
        if (isset($package->meta->set_template) && isset($package->meta->type)
            && $package->meta->set_template == 'client') {
            // Fetch the templates available
            $templates = $this->getTemplates($package->meta->type, $module_row);

            // Create template label
            $template = $fields->label(Language::_('Solusvm.service_field.solusvm_template', true), 'solusvm_template');
            // Create template field and attach to template label
            $template->attach(
                $fields->fieldSelect(
                    'solusvm_template',
                    $templates,
                    (isset($vars->solusvm_template) ? $vars->solusvm_template : null),
                    ['id' => 'solusvm_template']
                )
            );
            // Set the label as a field
            $fields->setField($template);
        }

        // Create virtual server label
        $vserver_id = $fields->label(
            Language::_('Solusvm.service_field.solusvm_vserver_id', true),
            'solusvm_vserver_id'
        );
        // Create virtual server field and attach to virtual server label
        $vserver_id->attach(
            $fields->fieldText(
                'solusvm_vserver_id',
                (isset($vars->solusvm_vserver_id) ? $vars->solusvm_vserver_id : null),
                ['id' => 'solusvm_vserver_id']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Solusvm.service_field.tooltip.solusvm_vserver_id', true));
        $vserver_id->attach($tooltip);
        // Set the label as a field
        $fields->setField($vserver_id);

        return $fields;
    }

    /**
     * Returns all fields to display to a client attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getClientAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        // Fetch the module row available for this package
        $module_row = $this->getModuleRowByServer(
            (isset($package->module_row) ? $package->module_row : 0),
            (isset($package->module_group) ? $package->module_group : '')
        );

        $fields = new ModuleFields();

        // Create hostname label
        $host_name = $fields->label(Language::_('Solusvm.service_field.solusvm_hostname', true), 'solusvm_hostname');
        // Create hostname field and attach to hostname label
        $host_name->attach(
            $fields->fieldText(
                'solusvm_hostname',
                (isset($vars->solusvm_hostname) ? $vars->solusvm_hostname : ($vars->domain ?? null)),
                ['id' => 'solusvm_hostname']
            )
        );
        // Set the label as a field
        $fields->setField($host_name);

        // Set the template if it can be set by the client
        if (isset($package->meta->set_template) && isset($package->meta->type)
            && $package->meta->set_template == 'client') {
            // Fetch the templates available
            $templates = $this->getTemplates($package->meta->type, $module_row);

            // Create template label
            $template = $fields->label(Language::_('Solusvm.service_field.solusvm_template', true), 'solusvm_template');
            // Create template field and attach to template label
            $template->attach(
                $fields->fieldSelect(
                    'solusvm_template',
                    $templates,
                    (isset($vars->solusvm_template) ? $vars->solusvm_template : null),
                    ['id' => 'solusvm_template']
                )
            );
            // Set the label as a field
            $fields->setField($template);
        }

        return $fields;
    }

    /**
     * Returns all fields to display to an admin attempting to edit a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getAdminEditFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Create virtual server label
        $vserver_id = $fields->label(
            Language::_('Solusvm.service_field.solusvm_vserver_id', true),
            'solusvm_vserver_id'
        );
        // Create virtual server field and attach to virtual server label
        $vserver_id->attach(
            $fields->fieldText(
                'solusvm_vserver_id',
                (isset($vars->solusvm_vserver_id) ? $vars->solusvm_vserver_id : null),
                ['id' => 'solusvm_vserver_id']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Solusvm.service_field.tooltip.solusvm_vserver_id', true));
        $vserver_id->attach($tooltip);
        // Set the label as a field
        $fields->setField($vserver_id);

        return $fields;
    }

    /**
     * Fetches the HTML content to display when viewing the service info in the
     * admin interface.
     *
     * @param stdClass $service A stdClass object representing the service
     * @param stdClass $package A stdClass object representing the service's package
     * @return string HTML content containing information to display when viewing the service info
     */
    public function getAdminServiceInfo($service, $package)
    {
        $row = $this->getModuleRow();

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('admin_service_info', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'solusvm' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $this->serviceFieldsToObject($service->fields));

        return $this->view->fetch();
    }

    /**
     * Fetches the HTML content to display when viewing the service info in the
     * client interface.
     *
     * @param stdClass $service A stdClass object representing the service
     * @param stdClass $package A stdClass object representing the service's package
     * @return string HTML content containing information to display when viewing the service info
     */
    public function getClientServiceInfo($service, $package)
    {
        $row = $this->getModuleRow();

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('client_service_info', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'solusvm' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $this->serviceFieldsToObject($service->fields));

        return $this->view->fetch();
    }


    /**
     * Returns all tabs to display to an admin when managing a service whose
     * package uses this module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title.
     *  Example: array('methodName' => "Title", 'methodName2' => "Title2")
     */
    public function getAdminTabs($package)
    {
        return [
            'tabActions' => Language::_('Solusvm.tab_actions', true),
            'tabStats' => Language::_('Solusvm.tab_stats', true),
            'tabConsole' => Language::_('Solusvm.tab_console', true),
            'tabIps' => Language::_('Solusvm.tab_ips', true)
        ];
    }

    /**
     * Returns all tabs to display to a client when managing a service whose
     * package uses this module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title.
     *  Example: array('methodName' => "Title", 'methodName2' => "Title2")
     */
    public function getClientTabs($package)
    {
        return [
            'tabClientActions' => ['name' => Language::_('Solusvm.tab_actions', true), 'icon' => 'fas fa-cogs'],
            'tabClientStats' => ['name' => Language::_('Solusvm.tab_stats', true), 'icon' => 'fas fa-chart-bar'],
            'tabClientConsole' => ['name' => Language::_('Solusvm.tab_console', true), 'icon' => 'fas fa-terminal'],
            'tabClientIps' => ['name' => Language::_('Solusvm.tab_ips', true), 'icon' => 'fas fa-cog']
        ];
    }

    /**
     * IPs tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabIps($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_ips', 'default');
        $this->view->base_uri = $this->base_uri;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Fetch the IPs set for the service and remove any set to be removed
        $ips = $this->ipsTab($package, $service, false, $get, $post);

        $this->view->set('ips', (object)$ips);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('service_id', $service->id);

        $this->view->set('view', $this->view->view);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'solusvm' . DS);
        return $this->view->fetch();
    }

    /**
     * Client IPs tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientIps($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_client_ips', 'default');
        $this->view->base_uri = $this->base_uri;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Fetch the IPs set for the service and remove any set to be removed
        $ips = $this->ipsTab($package, $service, true, $get, $post);

        $this->view->set('ips', (object)$ips);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('service_id', $service->id);

        $this->view->set('view', $this->view->view);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'solusvm' . DS);
        return $this->view->fetch();
    }

    /**
     * Handles data for the IPs tab in the client and admin interfaces
     * @see Solusvm::tabIPs() and Solusvm::tabClientIPs()
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param bool $client True if the action is being performed by the client, false otherwise
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @return array An array of vars for the template
     */
    private function ipsTab($package, $service, $client = false, array $get = null, array $post = null)
    {
        Loader::loadModels($this, ['Services']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);

        // Set the current main IP
        $main_ip = '';
        if (!empty($service_fields->solusvm_main_ip_address)) {
            $main_ip = $service_fields->solusvm_main_ip_address;
        }

        // Set base IPs
        $base_ips = [];
        if (!empty($service_fields->solusvm_base_ip_addresses)) {
            $base_ips = $this->csvToArray($service_fields->solusvm_base_ip_addresses, true);
        }

        // Set extra IPs
        $extra_ips = [];
        if (!empty($service_fields->solusvm_extra_ip_addresses)) {
            $extra_ips = $this->csvToArray($service_fields->solusvm_extra_ip_addresses, true);
        }

        // Determine whether the service option for custom IPs is editable by the client
        $option_editable = !$client;
        if ($client) {
            foreach ($service->options as $option) {
                if ($option->option_name == 'extra_ips') {
                    $option_editable = ($option->option_editable == 1);
                    break;
                }
            }
        }

        // Remove an extra IP address
        if (!empty($post['ip_address']) && in_array($post['ip_address'], $extra_ips) && $option_editable) {
            // Set the IP address to be removed
            $vars = ['solusvm_remove_extra_ips' => [$post['ip_address']]];

            // Remove the IP from being shown on the tab
            foreach ($extra_ips as $index => $ip) {
                if ($ip == $post['ip_address']) {
                    unset($extra_ips[$index]);
                    break;
                }
            }

            // Include the current service module fields (to pass module error checking)
            foreach ($service->fields as $field) {
                $vars[$field->key] = $field->value;
            }

            // Fetch and re-set all current service config options
            $options = [];
            foreach ($service->options as $option) {
                // Quantity options use the qty field as the value
                if ($option->option_type == 'quantity') {
                    $option->option_value = $option->qty;
                }

                // Set the extra IPs to the count of them
                if ($option->option_name == 'extra_ips') {
                    $option->option_value = max(0, count($extra_ips));
                }

                // Set the value of each option
                $options[$option->option_id] = $option->option_value;
            }

            // Update the config options
            $this->Services->edit($service->id, array_merge($vars, ['configoptions' => $options, 'prorate' => true]));

            if ($this->Services->errors()) {
                $this->Input->setErrors($this->Services->errors());
            }
        }

        return [
            'main' => $main_ip,
            'base' => $base_ips,
            'extra' => array_values($extra_ips),
            'editable' => $option_editable
        ];
    }

    /**
     * Actions tab (boot, reboot, shutdown, etc.)
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabActions($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_actions', 'default');
        $this->view->base_uri = $this->base_uri;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);

        // Get templates
        $templates = $this->getTemplates($service_fields->solusvm_type, $module_row);

        // Perform the actions
        $vars = $this->actionsTab($package, $service, $templates, false, $get, $post);

        // Set default vars
        if (empty($vars)) {
            $vars = ['template' => $service_fields->solusvm_template, 'hostname' => $service_fields->solusvm_hostname];
        }

        // Set options for password generation
        $this->setPasswordGenerationOptions();

        // Fetch the server status and templates
        $this->view->set('server', $this->getServerState($service_fields->solusvm_vserver_id, $module_row));
        $this->view->set('templates', $templates);

        $this->view->set('vars', (object)$vars);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('service_id', $service->id);
        $this->view->set('type', $service_fields->solusvm_type);

        $this->view->set('view', $this->view->view);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'solusvm' . DS);
        return $this->view->fetch();
    }

    /**
     * Client Actions tab (boot, reboot, shutdown, etc.)
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientActions($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_client_actions', 'default');
        $this->view->base_uri = $this->base_uri;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);

        // Get templates
        $templates = $this->getTemplates($service_fields->solusvm_type, $module_row);

        // Perform the actions
        $vars = $this->actionsTab($package, $service, $templates, true, $get, $post);

        // Set default vars
        if (empty($vars)) {
            $vars = ['template' => $service_fields->solusvm_template, 'hostname' => $service_fields->solusvm_hostname];
        }

        // Set options for password generation
        $this->setPasswordGenerationOptions();

        // Fetch the server status and templates
        $this->view->set('server', $this->getServerState($service_fields->solusvm_vserver_id, $module_row));
        $this->view->set('templates', $templates);

        $this->view->set('vars', (object)$vars);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('service_id', $service->id);
        $this->view->set('type', $service_fields->solusvm_type);

        $this->view->set('view', $this->view->view);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'solusvm' . DS);
        return $this->view->fetch();
    }

    /**
     * Sets options for SolusVM password generation
     */
    private function setPasswordGenerationOptions()
    {
        // Set JSON-encoded password generator character options
        // to include alphanumeric characters
        $this->view->set(
            'password_options',
            json_encode([
                'include' => [(object)['chars' => [['A', 'Z'], ['a', 'z'], ['0', '9']]]]
            ])
        );
    }

    /**
     * Handles data for the actions tab in the client and admin interfaces
     * @see Solusvm::tabActions() and Solusvm::tabClientActions()
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $templates An array of SolusVM templates
     * @param bool $client True if the action is being performed by the client, false otherwise
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return array An array of vars for the template
     */
    private function actionsTab($package, $service, $templates, $client = false, array $get = null, array $post = null)
    {
        $vars = [];

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);

        $get_key = '3';
        if ($client) {
            $get_key = '2';
        }

        // Perform actions
        if (array_key_exists($get_key, (array)$get)) {
            switch ($get[$get_key]) {
                case 'boot':
                    if (!$this->performAction('boot', $service_fields->solusvm_vserver_id, $module_row)) {
                        $this->Input->setErrors(
                            ['api' => ['internal' => Language::_('Solusvm.!error.api.internal', true)]]
                        );
                    } else {
                        $this->setMessage('success', Language::_('Solusvm.!success.boot', true));
                    }
                    break;
                case 'reboot':
                    if (!$this->performAction('reboot', $service_fields->solusvm_vserver_id, $module_row)) {
                        $this->Input->setErrors(
                            ['api' => ['internal' => Language::_('Solusvm.!error.api.internal', true)]]
                        );
                    } else {
                        $this->setMessage('success', Language::_('Solusvm.!success.reboot', true));
                    }
                    break;
                case 'shutdown':
                    if (!$this->performAction('shutdown', $service_fields->solusvm_vserver_id, $module_row)) {
                        $this->Input->setErrors(
                            ['api' => ['internal' => Language::_('Solusvm.!error.api.internal', true)]]
                        );
                    } else {
                        $this->setMessage('success', Language::_('Solusvm.!success.shutdown', true));
                    }
                    break;
                case 'password':
                    // Show the root password section
                    $this->view->set('password', true);

                    if (!empty($post)) {
                        $rules = [
                            'password' => [
                                'length' => [
                                    'rule' => ['minLength', 6],
                                    'message' => Language::_('Solusvm.!error.solusvm_root_password.length', true)
                                ],
                                'matches' => [
                                    'rule' => [
                                        'compares',
                                        '==',
                                        (isset($post['confirm_password']) ? $post['confirm_password'] : null)
                                    ],
                                    'message' => Language::_('Solusvm.!error.solusvm_root_password.matches', true)
                                ]
                            ]
                        ];

                        // Validate the password and update it
                        $this->Input->setRules($rules);
                        if ($this->Input->validates($post)) {
                            // Update the service hostname
                            Loader::loadModels($this, ['Services']);
                            $this->Services->edit($service->id, ['solusvm_root_password' => $post['password']]);

                            if (($errors = $this->Services->errors())) {
                                $this->Input->setErrors($errors);
                            } else {
                                $this->setMessage('success', Language::_('Solusvm.!success.password', true));
                            }

                            // Do not show the hostname section again
                            $this->view->set('password', false);
                        }

                        $vars = $post;
                    }
                    break;
                case 'hostname':
                    // Show the hostname section
                    $this->view->set('hostname', true);

                    if (!empty($post)) {
                        $rules = [
                            'hostname' => [
                                'format' => [
                                    'pre_format' => [[$this, 'replaceText'], '', "/^\s*www\./i"],
                                    'rule' => [[$this, 'validateHostName'], true],
                                    'message' => Language::_('Solusvm.!error.solusvm_hostname.format', true)
                                ]
                            ]
                        ];

                        // Validate the hostname and update it
                        $this->Input->setRules($rules);
                        if ($this->Input->validates($post)) {
                            // Update the service hostname
                            Loader::loadModels($this, ['Services']);
                            $this->Services->edit($service->id, ['solusvm_hostname' => strtolower($post['hostname'])]);

                            if (($errors = $this->Services->errors())) {
                                $this->Input->setErrors($errors);
                            } else {
                                $this->setMessage('success', Language::_('Solusvm.!success.hostname', true));
                            }

                            // Do not show the hostname section again
                            $this->view->set('hostname', false);
                        }

                        $vars = $post;
                    }
                    break;
                case 'reinstall':
                    // Show the reinstall section
                    $this->view->set('reinstall', true);

                    if (!empty($post)) {
                        $rules = [
                            'template' => [
                                'valid' => [
                                    'rule' => ['array_key_exists', $templates],
                                    'message' => Language::_('Solusvm.!error.api.template.valid', true)
                                ]
                            ],
                            'confirm' => [
                                'valid' => [
                                    'rule' => ['compares', '==', '1'],
                                    'message' => Language::_('Solusvm.!error.api.confirm.valid', true)
                                ]
                            ]
                        ];

                        // Validate the template and perform the reinstallation
                        $this->Input->setRules($rules);
                        if ($this->Input->validates($post)) {
                            // Update the service template
                            Loader::loadModels($this, ['Services']);
                            $this->Services->edit(
                                $service->id,
                                ['solusvm_template' => $post['template'], 'confirm_reinstall' => true]
                            );

                            if (($errors = $this->Services->errors())) {
                                $this->Input->setErrors($errors);
                            } else {
                                $this->setMessage('success', Language::_('Solusvm.!success.reinstall', true));
                            }

                            // Do not show the reinstall section again
                            $this->view->set('reinstall', false);
                        }

                        $vars = $post;
                    }
                    break;
                default:
                    break;
            }
        }

        return $vars;
    }

    /**
     * Performs an action on the virtual server.
     *
     * @param string $action The action to perform (i.e. "boot", "reboot", "shutdown")
     * @param int $server_id The virtual server ID
     * @param stdClass $module_row An stdClass object representing a single server
     * @param array $data A key=>value list of data parameters to include with the action
     * @return bool True if the action was performed successfully, false otherwise
     */
    private function performAction($action, $server_id, $module_row, array $data = [])
    {
        $api = $this->getApi(
            $module_row->meta->user_id,
            $module_row->meta->key,
            $module_row->meta->host,
            $module_row->meta->port
        );

        // Load the vserver API
        $api->loadCommand('solusvm_vserver');
        $result = false;

        try {
            $server_api = new SolusvmVserver($api);
            $params = array_merge($data, ['vserverid' => $server_id]);
            $masked_params = $params;
            $mask_keys = ['password', 'rootpassword', 'vncpassword', 'consolepassword'];

            foreach ($mask_keys as $mask_key) {
                if (array_key_exists($mask_key, $masked_params)) {
                    $masked_params[$mask_key] = '***';
                }
            }

            $this->log($module_row->meta->host . '|vserver-' . $action, serialize($masked_params), 'input', true);
            $response = $this->parseResponse(call_user_func_array([$server_api, $action], [$params]), $module_row);

            if ($response && $response->status == 'success') {
                return true;
            }
        } catch (Exception $e) {
            // Nothing to do
        }

        return $result;
    }

    /**
     * Statistics tab (bandwidth/disk usage)
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabStats($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $view = $this->statsTab($package, $service);
        return $view->fetch();
    }

    /**
     * Client Statistics tab (bandwidth/disk usage)
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientStats($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $view = $this->statsTab($package, $service, true);
        return $view->fetch();
    }

    /**
     * Builds the data for the admin/client stats tabs
     * @see Solusvm::tabStats() and Solusvm::tabClientStats()
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @return View A template view to be rendered
     */
    private function statsTab($package, $service, $client = false)
    {
        $template = ($client ? 'tab_client_stats' : 'tab_stats');

        $this->view = new View($template, 'default');
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);

        $this->view->set('server', $this->getServerState($service_fields->solusvm_vserver_id, $module_row, true));
        $this->view->set(
            'module_hostname',
            (isset($module_row->meta->host) && isset($module_row->meta->port)
                ? 'https://' . $module_row->meta->host . ':' . $module_row->meta->port
                : ''
            )
        );

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'solusvm' . DS);
        return $this->view;
    }

    /**
     * Console tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabConsole($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $view = $this->consoleTab($package, $service);
        return $view->fetch();
    }

    /**
     * Client Console tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientConsole($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $view = $this->consoleTab($package, $service, true);
        return $view->fetch();
    }

    /**
     * Builds the data for the admin/client console tabs
     * @see Solusvm::tabConsole() and Solusvm::tabClientConsole()
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @return View A template view to be rendered
     */
    private function consoleTab($package, $service, $client = false)
    {
        $template = ($client ? 'tab_client_console' : 'tab_console');
        $this->view = new View($template, 'default');
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        Loader::loadModels($this, ['Companies']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);
        $type = 'console';

        // Determine whether to use the console or VNC
        if (in_array(strtolower($service_fields->solusvm_type), ['xen', 'openvz'])) {
            // Console for OpenVZ/XEN
            $session = array_merge(
                $this->getVncInfo($service, $module_row),
                $this->setupConsoleSession($service, $module_row)
            );
        } else {
            // VNC for HVM/KVM
            $type = 'vnc';
            $session = $this->getVncInfo($service, $module_row);

            // Check whether the VNC vendor code is available
            $this->view->set('vnc_applet_available', is_dir(VENDORDIR . 'vnc'));
        }

        // Get the company settings
        $company = $this->Companies->get(Configure::get('Blesta.company_id'));

        $this->view->set('console', (object)$session);
        $this->view->set('type', $type);
        $this->view->set('company', $company);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'solusvm' . DS);
        return $this->view;
    }

    /**
     * Converts bytes to a string representation including the type
     *
     * @param int $bytes The number of bytes
     * @return string A formatted amount including the type (B, KB, MB, GB)
     */
    private function convertBytesToString($bytes)
    {
        $step = 1024;
        $unit = 'B';

        if (($value = number_format($bytes/($step*$step*$step), 2)) >= 1) {
            $unit = 'GB';
        } elseif (($value = number_format($bytes/($step*$step), 2)) >= 1) {
            $unit = 'MB';
        } elseif (($value = number_format($bytes/($step), 2)) >= 1) {
            $unit = 'KB';
        } else {
            $value = $bytes;
        }

        return Language::_('Solusvm.!bytes.value', true, $value, $unit);
    }

    /**
     * Initializes the API and returns an instance of that object with the given $host, $user, and $pass set
     *
     * @param string $user_id The ID of the SolusVM user
     * @param string $key The key to the SolusVM server
     * @param string $host The host to the SolusVM server
     * @param string $port The SolusVM server port number
     * @return SolusvmApi The SolusvmApi instance
     */
    private function getApi($user_id, $key, $host, $port)
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'solusvm_api.php');

        return new SolusvmApi($user_id, $key, $host, $port);
    }

    /**
     * Gets VNC connection details the virtual server
     *
     * @param stdClass $service An stdClass object representing the service from which to fetch vnc details
     * @param stdClass $module_row An stdClass object representing the module row
     * @return array An array containing the VNC and WebSocket connection details
     */
    private function getVncInfo($service, $module_row)
    {
        $api = $this->getApi(
            $module_row->meta->user_id,
            $module_row->meta->key,
            $module_row->meta->host,
            $module_row->meta->port
        );

        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Load the server API
        $api->loadCommand('solusvm_vserver');
        $response = null;

        // Fetch vnc details
        try {
            $server_api = new SolusvmVserver($api);
            $params = [
                'vserverid' => $service_fields->solusvm_vserver_id,
            ];

            $this->log($module_row->meta->host . '|vserver-vnc', serialize($params), 'input', true);
            $response = $this->parseResponse($server_api->vnc($params), $module_row);
        } catch (Exception $e) {
            // Nothing to do
        }

        $session = [
            'vncip' => '', 'vncport' => '', 'vncpassword' => '',
            'sockethost' => '', 'socketport' => '', 'socketpassword' => '', 'sockethash' => ''
        ];

        // Return the VNC and WebSocket details
        if ($response && $response->status == 'success') {
            $session['vncip'] = (property_exists($response, 'vncip') ? $response->vncip : '');
            $session['vncport'] = (property_exists($response, 'vncport') ? $response->vncport : '');
            $session['vncpassword'] = (property_exists($response, 'vncpassword') ? $response->vncpassword : '');
            $session['sockethost'] = (property_exists($response, 'sockethost') ? $response->sockethost : '');
            $session['socketport'] = (property_exists($response, 'socketport') ? $response->socketport : '');
            $session['socketpassword'] = (property_exists($response, 'socketpassword')
                ? $response->socketpassword
                : '');
            $session['sockethash'] = (property_exists($response, 'sockethash') ? $response->sockethash : '');
        }

        return $session;
    }
    /**
     * Sets up a new console session with the virtual server
     *
     * @param stdClass $service An stdClass object representing the service from which to start a console session
     * @param stdClass $module_row An stdClass object representing the module row
     * @param int $length The length of time (in hours) the session should be active for. Must be between 1 and 8.
     * @return array An array containing the console username and password
     */
    private function setupConsoleSession($service, $module_row, $length = 1)
    {
        $api = $this->getApi(
            $module_row->meta->user_id,
            $module_row->meta->key,
            $module_row->meta->host,
            $module_row->meta->port
        );

        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Load the server API
        $api->loadCommand('solusvm_vserver');
        $response = null;

        // Enable a new console session
        try {
            $server_api = new SolusvmVserver($api);
            $params = [
                'vserverid' => $service_fields->solusvm_vserver_id,
                'access' => 'enable',
                'time' => (int)$length
            ];

            $this->log($module_row->meta->host . '|vserver-console', serialize($params), 'input', true);
            $response = $this->parseResponse($server_api->console($params), $module_row);
        } catch (Exception $e) {
            // Nothing to do
        }

        $session = ['username' => '', 'password' => '', 'ip' => '', 'port' => ''];

        // Return the console user
        if ($response && $response->status == 'success') {
            $session['username'] = (property_exists($response, 'consoleusername') ? $response->consoleusername : '');
            $session['password'] = (property_exists($response, 'consolepassword') ? $response->consolepassword : '');
            $session['ip'] = (property_exists($response, 'consoleip') ? $response->consoleip : '');
            $session['port'] = (property_exists($response, 'consoleport') ? $response->consoleport : '');
        }

        return $session;
    }

    /**
     * Retrieves a CSV array of all extra IP addresses for the virtual server,
     * excluding all extra IP addresses used as base IP addresses for the service
     *
     * @param stdClass $service_fields An stdClass object representing the service fields
     * @param stdClass $module_row An stdClass object representing a single server
     * @return array An array containing each extra IP address on the virtual server
     */
    private function getExtraIps($service_fields, $module_row)
    {
        $state = $this->getServerState($service_fields->solusvm_vserver_id, $module_row);

        $ips = $this->csvToArray($service_fields->solusvm_extra_ip_addresses);
        $base_ips = $this->csvToArray(
            isset($service_fields->solusvm_base_ip_addresses)
            ? $service_fields->solusvm_base_ip_addresses
            : ''
        );

        if (isset($state->ipaddresses)) {
            $all_ips = $this->csvToArray($state->ipaddresses);

            // Remove the main IP address from the list
            if (isset($state->mainipaddress) && array_key_exists($state->mainipaddress, $all_ips)) {
                unset($all_ips[$state->mainipaddress]);
            }

            // Remove all base IPs from the list
            foreach ($base_ips as $base_ip) {
                if (array_key_exists($base_ip, $all_ips)) {
                    unset($all_ips[$base_ip]);
                }
            }

            $ips = array_values($all_ips);
        }

        return $ips;
    }

    /**
     * Splits the set of extra IPs into a set of reserved base IPs and remaining extra IPs
     *
     * @param string $ips A CSV list of IPs
     * @param int $total_reserved The total number of reserved IPs to use as base IPs
     * @return A set of IP addresses:
     *  - base An array of base IP addresses
     *  - extra An array of extra IP addresses
     */
    private function splitExtraIps($ips, $total_reserved)
    {
        $ip_addresses = [
            'base' => [],
            'extra' => []
        ];

        if (empty($ips)) {
            return $ip_addresses;
        }

        $all_ips = $this->csvToArray($ips, true);

        if ($total_reserved > 0) {
            // Set all extra IPs
            $i = 0;
            foreach ($all_ips as $extra_ip) {
                if ($i >= $total_reserved) {
                    $ip_addresses['extra'][] = $extra_ip;
                    continue;
                }

                $ip_addresses['base'][] = $extra_ip;
                $i++;
            }
        } else {
            // All IPs are extra
            $ip_addresses['extra'] = $all_ips;
        }

        return $ip_addresses;
    }

    /**
     * Retrieves a list of the virtual server state fields, e.g. bandwidth, type, graphs
     *
     * @param int $server_id The virtual server ID
     * @param stdClass $module_row A stdClass object representing a single server
     * @param bool $fetch_graphs True to fetch graphs, false otherwise
     * @return stdClass An stdClass object representing the server state fields
     */
    private function getServerState($server_id, $module_row, $fetch_graphs = false)
    {
        $api = $this->getApi(
            $module_row->meta->user_id,
            $module_row->meta->key,
            $module_row->meta->host,
            $module_row->meta->port
        );

        // Load the nodes API
        $api->loadCommand('solusvm_vserver');
        $response = null;

        try {
            $server_api = new SolusvmVserver($api);
            $params = ['vserverid' => $server_id];

            if (!$fetch_graphs) {
                $params['nographs'] = 'true';
            }

            $this->log($module_row->meta->host . '|vserver-infoall', serialize($params), 'input', true);
            $response = $this->parseResponse($server_api->infoAll($params), $module_row);
        } catch (Exception $e) {
            // Nothing to do
        }

        // Set the CSV values to an array of values
        if ($response) {
            $fields = ['hdd' => 'space', 'memory' => 'memory', 'bandwidth' => 'bandwidth'];
            foreach ($fields as $field => $name) {
                if (!property_exists($response, $field)) {
                    continue;
                }

                $values = $this->csvToArray($response->{$field}, true);
                $response->{$field} = [
                    'total_' . $name => (isset($values[0]) ? $values[0] : ''),
                    'used_' . $name => (isset($values[1]) ? $values[1] : ''),
                    'free_' . $name => (isset($values[2]) ? $values[2] : ''),
                    'percent_used_' . $name => (isset($values[3]) ? $values[3] : ''),
                    'total_' . $name . '_formatted' => $this->convertBytesToString(
                        (isset($values[0]) ? $values[0] : '')
                    ),
                    'used_' . $name . '_formatted' => $this->convertBytesToString(
                        (isset($values[1]) ? $values[1] : '')
                    ),
                    'free_' . $name . '_formatted' => $this->convertBytesToString(
                        (isset($values[2]) ? $values[2] : '')
                    ),
                    'percent_used_' . $name . '_formatted' => Language::_(
                        'Solusvm.!percent.used',
                        true,
                        (isset($values[3]) ? $values[3] : '')
                    ),
                ];
            }
        }
        return ($response ? $response : new stdClass());
    }

    /**
     * Retrieves a list of node statistics, e.g. freememory, freedisk, etc.
     *
     * @param mixed $node_id The node ID or name
     * @param stdClass $module_row A stdClass object representing a single server
     * @return stdClass An stdClass object representing the node statistics
     */
    private function getNodeStatistics($node_id, $module_row)
    {
        $api = $this->getApi(
            $module_row->meta->user_id,
            $module_row->meta->key,
            $module_row->meta->host,
            $module_row->meta->port
        );

        // Load the nodes API
        $api->loadCommand('solusvm_nodes');
        $response = null;

        try {
            $nodes_api = new SolusvmNodes($api);
            $params = ['nodeid' => $node_id];

            $this->log($module_row->meta->host . '|node-statistics', serialize($params), 'input', true);
            $response = $this->parseResponse($nodes_api->statistics($params), $module_row);
        } catch (Exception $e) {
            // Nothing to do
        }

        // Return the nodes
        if ($response && $response->status == 'success') {
            return $response;
        }

        return new stdClass();
    }

    /**
     * Fetches the nodes available for the SolusVM server of the given type
     *
     * @param string $type The type of server (i.e. openvz, xen, xen hvm, kvm)
     * @param stdClass $module_row A stdClass object representing a single server
     * @return array A list of nodes
     */
    private function getNodes($type, $module_row)
    {
        $api = $this->getApi(
            $module_row->meta->user_id,
            $module_row->meta->key,
            $module_row->meta->host,
            $module_row->meta->port
        );

        // Load the nodes API
        $api->loadCommand('solusvm_nodes');
        $response = null;

        try {
            $nodes_api = new SolusvmNodes($api);
            $params = ['type' => $type];

            $this->log($module_row->meta->host . '|listnodes', serialize($params), 'input', true);
            $response = $this->parseResponse($nodes_api->getList($params), $module_row);
        } catch (Exception $e) {
            // Nothing to do
            return [];
        }

        // Return the nodes
        if ($response && $response->status == 'success') {
            return $this->csvToArray($response->nodes);
        }

        return [];
    }

    /**
     * Fetches the node groups available for the SolusVM server of the given type
     *
     * @param string $type The type of server (i.e. xen hvm, kvm)
     * @param stdClass $module_row A stdClass object representing a single server
     * @return array A key/value list of node groups
     */
    private function getNodeGroups($type, $module_row)
    {
        $api = $this->getApi(
            $module_row->meta->user_id,
            $module_row->meta->key,
            $module_row->meta->host,
            $module_row->meta->port
        );

        // Load the nodes API
        $api->loadCommand('solusvm_nodes');
        $response = null;

        try {
            $nodes_api = new SolusvmNodes($api);
            $params = ['type' => $type];

            $this->log($module_row->meta->host . '|listnodegroups', serialize($params), 'input', true);
            $response = $this->parseResponse($nodes_api->listGroups($params), $module_row);
        } catch (Exception $e) {
            // Nothing to do
            return [];
        }

        // Return the nodes
        if ($response && $response->status == 'success') {
            // Format the groups into key/value pairs
            $groups = $this->csvToArray($response->nodegroups, true);
            $node_groups = [];

            foreach ($groups as $group) {
                $value = explode('|', $group);
                // Skip none
                if (isset($value[0]) && isset($value[1]) && $value[1] != '--none--') {
                    $node_groups[$value[0]] = $value[1];
                }
            }
            return $node_groups;
        }

        return [];
    }

    /**
     * Fetches a single plan and its details for a SolusVM server
     *
     * @param string $type The virtualization type of the server (i.e. openvs, xen, xen hvm, kvm)
     * @param string $plan_name The name of the plan to fetch
     * @param stdClass $module_row A stdClass object representing a single server
     * @return mixed An stdClass object representing the plan, or false if it could not be found
     */
    private function getPlan($type, $plan_name, $module_row)
    {
        $api = $this->getApi(
            $module_row->meta->user_id,
            $module_row->meta->key,
            $module_row->meta->host,
            $module_row->meta->port
        );

        // Load the plans API
        $api->loadCommand('solusvm_plans');
        $response = null;

        try {
            $plans_api = new SolusvmPlans($api);
            $params = ['type' => $type];

            $this->log($module_row->meta->host . '|list-plans', serialize($params), 'input', true);
            $response = $this->parseResponse($plans_api->getDetails($params), $module_row);
        } catch (Exception $e) {
            // Nothing to do
            return [];
        }

        // Return the plan
        if ($response && $response->status == 'success') {
            foreach ($response->plans as $plan) {
                if (property_exists($plan, 'name') && strtolower($plan->name) == strtolower($plan_name)) {
                    return $plan;
                }
            }
        }

        return false;
    }

    /**
     * Fetches the plans available for the SolusVM server of the given type
     *
     * @param string $type The type of server (i.e. openvz, xen, xen hvm, kvm)
     * @param stdClass $module_row A stdClass object representing a single server
     * @return array A list of plans
     */
    private function getPlans($type, $module_row)
    {
        $api = $this->getApi(
            $module_row->meta->user_id,
            $module_row->meta->key,
            $module_row->meta->host,
            $module_row->meta->port
        );

        // Load the plans API
        $api->loadCommand('solusvm_plans');
        $response = null;

        try {
            $plans_api = new SolusvmPlans($api);
            $params = ['type' => $type];

            $this->log($module_row->meta->host . '|listplans', serialize($params), 'input', true);
            $response = $this->parseResponse($plans_api->getList($params), $module_row);
        } catch (Exception $e) {
            // Nothing to do
            return [];
        }

        // Return the plans
        if ($response && $response->status == 'success') {
            return $this->csvToArray($response->plans);
        }

        return [];
    }

    /**
     * Fetches the templates available for the SolusVM server of the given type
     *
     * @param string $type The type of server (i.e. openvz, xen, xen hvm, kvm)
     * @param stdClass $module_row A stdClass object representing a single server
     * @return array A list of templates
     */
    private function getTemplates($type, $module_row)
    {
        $api = $this->getApi(
            $module_row->meta->user_id,
            $module_row->meta->key,
            $module_row->meta->host,
            $module_row->meta->port
        );

        // Load the templates API
        $api->loadCommand('solusvm_templates');
        $response = null;

        try {
            $templates_api = new SolusvmTemplates($api);
            $params = ['type' => $type, 'listpipefriendly' => 'true'];

            $this->log($module_row->meta->host . '|listtemplates', serialize($params), 'input', true);
            $response = $this->parseResponse($templates_api->getList($params), $module_row);
        } catch (Exception $e) {
            // Nothing to do
            return [];
        }

        // Return the templates
        if ($response && $response->status == 'success') {
            // Fetch the templates
            $templates = (isset($response->templates) ? $this->csvToArray($response->templates) : []);

            switch (strtolower($type)) {
                case 'kvm':
                    $templates = array_merge(
                        (!empty($response->templateskvm) ? $this->csvToArray($response->templateskvm) : []),
                        $templates
                    );
                    break;
                case 'xen hvm':
                    $templates = array_merge(
                        (!empty($response->templateshvm) ? $this->csvToArray($response->templateshvm) : []),
                        $templates
                    );
                    break;
                default:
                    break;
            }

            $formatted_templates = [];
            foreach ($templates as $template) {
                // Skip the none template
                if ($template == '--none--') {
                    continue;
                }

                // Split out the friendly names of the templates
                $temp = explode('|', $template);
                $formatted_templates[$temp[0]] = $temp[0];
                if (!empty($temp[0]) && !empty($temp[1])) {
                    $formatted_templates[$temp[0]] = $temp[1];
                }
            }

            asort($formatted_templates);

            return $formatted_templates;
        }

        return [];
    }

    /**
     * Returns an array of service fields to set for the service using the given input
     *
     * @param array $vars An array of key/value input pairs
     * @param stdClass $package A stdClass object representing the package for the service
     * @return array An array of key/value pairs representing service fields
     */
    private function getFieldsFromInput(array $vars, $package)
    {
        // Determine which node to assign the service to
        $module_row = $this->getModuleRow($package->module_row);

        // Set the template, either from the package or client level
        $template = $package->meta->set_template == 'admin'
            ? $package->meta->template
            : (isset($vars['solusvm_template']) ? $vars['solusvm_template'] : null);

        $fields = [
            'type' => $package->meta->type,
            'hostname' => isset($vars['solusvm_hostname']) ? strtolower($vars['solusvm_hostname']) : null,
            'username' => isset($vars['client_id']) ? 'vmuser' . $vars['client_id'] : null,
            'password' => $this->generatePassword(), // root password
            'plan' => $package->meta->plan,
            'template' => $template,
            'ips' => (isset($package->meta->total_base_ip_addresses)
                ? (int)$package->meta->total_base_ip_addresses
                : 1
            )
        ];

        // Set the node or node group
        if (isset($package->meta->nodes)) {
            $fields['node'] = $this->chooseNode($package->meta->nodes, $module_row);
        } elseif (isset($package->meta->node_group)) {
            $fields['nodegroup'] = $package->meta->node_group;
        }

        // Determine the selected plan details
        $plan = $this->getPlan($package->meta->type, $package->meta->plan, $module_row);

        // Set any config options
        $config_options = (isset($vars['configoptions']) ? (array)$vars['configoptions'] : []);
        $ServiceOptions = $this->loadServiceOptions($package->meta->type);
        $fields = array_merge($fields, $ServiceOptions->getAll($config_options, $plan));

        // Remove node if a node group was set
        if (isset($fields['nodegroup'])) {
            unset($fields['node']);
        }

        return $fields;
    }

    /**
     * Retrieves an instance of the SolusVM Service Options
     *
     * @param string $type The virtualization type (optional, default 'xen')
     * @return SolusVmServiceOptions An instance of the service options
     */
    private function loadServiceOptions($type = 'xen')
    {
        $this->loadLib('solusvm_service_options');
        return new SolusvmServiceOptions($type);
    }

    /**
     * Loads a library class
     *
     * @param string $command The filename of the class to load
     */
    private function loadLib($command)
    {
        Loader::load(dirname(__FILE__) . DS . 'lib' . DS . $command . '.php');
    }

    /**
     * Chooses the best node to assign a service onto based on the resources of available nodes
     *
     * @param array $nodes A list of nodes
     * @param stdClass $module_row An stdClass object representing the module row
     * @return string The name of the selected node
     */
    private function chooseNode($nodes, $module_row)
    {
        $node = '';

        if (count($nodes) == 1) {
            $node = $nodes[0];
        } else {
            $best_node = [
                'name' => '',
                'value' => 0
            ];

            // 1 MB in bytes
            $megabyte = 1048576;

            // Determine the best node
            foreach ($nodes as $node_id) {
                // Fetch node stats
                $node_stats = $this->getNodeStatistics($node_id, $module_row);

                // Use disk/memory to compare which node has the most available resources
                $disk = (property_exists($node_stats, 'freedisk') && !empty($node_stats->freedisk)
                    ? (float)$node_stats->freedisk
                    : 0
                );
                $memory = (property_exists($node_stats, 'freememory') && !empty($node_stats->freememory)
                    ? (float)$node_stats->freememory
                    : 0
                );
                $total_value = $disk + $memory;

                // If any one of the resources is too low, skip this node when we have another
                if ($best_node['value'] != 0 && ($disk <= $megabyte || $memory <= $megabyte)) {
                    continue;
                }

                // Set the best node to the one with the largest combined free resources, if available
                if ($total_value >= $best_node['value']) {
                    $best_node = ['name' => $node_id, 'value' => $total_value];
                }
            }

            $node = $best_node['name'];
        }

        return $node;
    }

    /**
     * Creates a new SolusVM Client. May set Input::errors() on error.
     *
     * @param int $client_id The client ID
     * @param string $username The client's username
     * @param stdClass $module_row The server module row
     * @return array An key/value array including the client's username and password.
     *  If the client already exists in SolusVM, then the password returned is null
     */
    private function createClient($client_id, $username, $module_row)
    {
        // Get the API
        $api = $this->getApi(
            $module_row->meta->user_id,
            $module_row->meta->key,
            $module_row->meta->host,
            $module_row->meta->port
        );
        $api->loadCommand('solusvm_client');

        $client_fields = ['username' => $username, 'password' => null];
        $response = false;

        // Check if a client exists
        try {
            // Load up the Virtual Server API
            $client_api = new SolusvmClient($api);
            $params = ['username' => $client_fields['username']];

            // Provision the Virtual Server
            $this->log($module_row->meta->host . '|client-checkexists', serialize($params), 'input', true);
            $response = $this->parseResponse($client_api->checkExists($params), $module_row, true);
        } catch (Exception $e) {
            // Internal Error
            $this->Input->setErrors(['api' => ['internal' => Language::_('Solusvm.!error.api.internal', true)]]);
            return $client_fields;
        }

        // Client does not exist, attempt to create one
        if ($response && $response->status != 'success') {
            $response = false;

            // Fetch the client to set additional client fields
            Loader::loadModels($this, ['Clients']);
            $client_params = [];
            if (($client = $this->Clients->get($client_id, false))) {
                $client_params = [
                    'email' => $client->email,
                    'company' => $client->company,
                    'firstname' => $client->first_name,
                    'lastname' => $client->last_name
                ];
            }

            try {
                // Generate a client password
                $client_fields['password'] = $this->generatePassword();

                $params = array_merge($client_fields, $client_params);
                $masked_params = $params;
                $masked_params['password'] = '***';

                // Create a client
                $this->log($module_row->meta->host . '|client-create', serialize($masked_params), 'input', true);
                $response = $this->parseResponse($client_api->create($params), $module_row);
            } catch (Exception $e) {
                // Internal Error
                $this->Input->setErrors(['api' => ['internal' => Language::_('Solusvm.!error.api.internal', true)]]);
            }

            // Error, client account could not be created
            if (!$response || $response->status != 'success') {
                $this->Input->setErrors(
                    ['create_client' => ['failed' => Language::_('Solusvm.!error.create_client.failed', true)]]
                );
            }
        }

        return $client_fields;
    }

    /**
     * Parses the response from SolusVM into an stdClass object
     *
     * @param SolusvmResponse $response The response from the API
     * @param stdClass $module_row A stdClass object representing a
     *  single server (optional, required when Module::getModuleRow() is unavailable)
     * @param bool $ignore_error Ignores any response error and returns the
     *  response anyway; useful when a response is expected to fail
     *  (e.g. check client exists) (optional, default false)
     * @return stdClass A stdClass object representing the response, void if the response was an error
     */
    private function parseResponse(SolusvmResponse $response, $module_row = null, $ignore_error = false)
    {
        Loader::loadHelpers($this, ['Html']);

        // Set the module row
        if (!$module_row) {
            $module_row = $this->getModuleRow();
        }

        $success = false;

        switch ($response->status()) {
            case 'success':
                $success = true;
                break;
            case 'error':
                $success = false;

                // Ignore generating the error
                if ($ignore_error) {
                    break;
                }

                $errors = $response->errors();
                $error = isset($errors->statusmsg) ? $errors->statusmsg : '';
                $this->Input->setErrors(['api' => ['response' => $this->Html->safe($error)]]);
                break;
            default:
                // Invalid response
                $success = false;

                // Ignore generating the error
                if ($ignore_error) {
                    break;
                }

                $this->Input->setErrors(['api' => ['internal' => Language::_('Solusvm.!error.api.internal', true)]]);
                break;
        }

        // Replace sensitive fields
        $masked_params = ['password', 'rootpassword', 'vncpassword', 'consolepassword'];
        $output = $this->formatResponse($response->response());
        $raw_output = $response->raw();

        foreach ($masked_params as $masked_param) {
            if (property_exists($output, $masked_param)) {
                $raw_output = preg_replace(
                    '/<' . $masked_param . ">(.*)<\/" . $masked_param . '>/',
                    '<' . $masked_param . '>***</' . $masked_param . '>',
                    $raw_output
                );
            }
        }

        // Log the response
        $this->log($module_row->meta->host, $raw_output, 'output', $success);

        if (!$success && !$ignore_error) {
            return;
        }

        return $output;
    }

    /**
     * Formats API response values to strings
     *
     * @param stdClass $response The API response
     */
    private function formatResponse($response)
    {
        $temp_response = (array)$response;

        // Convert empty object values to empty string values
        foreach ($temp_response as $key => $value) {
            if (is_object($value)) {
                // If plans are given, setup an array of plans
                if (property_exists($value, 'plan') && is_array($value->plan)) {
                    $plans = [];
                    foreach ($value->plan as $plan) {
                        $plans[] = $this->formatResponse($plan);
                    }
                    $response->{$key} = $plans;
                } else {
                    $response->{$key} = !empty($value->plan) ?  [$this->formatResponse($value->plan)] : '';
                }
            }
        }

        return $response;
    }

    /**
     * Converts the values in the given array to a CSV list
     *
     * @param array $array An array
     * @return string A CSV list
     */
    private function arrayToCsv(array $array)
    {
        return implode(',', $array);
    }

    /**
     * Builds a key/value array out of a CSV list
     *
     * @param string $csv A comma-separated list of strings
     * @param bool $indexed True to index the array numerically, false to set
     *  each CSV string as the key AND value; duplicates will be overwritten (optional, default false)
     */
    private function csvToArray($csv, $indexed = false)
    {
        $data = explode(',', $csv);

        // Remove any white space
        foreach ($data as &$field) {
            $field = trim($field);
        }

        // Return numerically-indexed list
        if ($indexed) {
            return $data;
        }

        // Return identical key/value pairs
        $data = array_flip($data);
        foreach ($data as $key => &$value) {
            $value = $key;
        }
        return $data;
    }

    /**
     * Sets the assigned and available groups. Manipulates the $available_groups by reference.
     *
     * @param array $available_groups A key/value list of available groups
     * @param array $assigned_groups A numerically-indexed array of assigned groups
     */
    private function assignGroups(&$available_groups, $assigned_groups)
    {
        // Remove available groups if they are assigned
        foreach ($assigned_groups as $key => $value) {
            if (isset($available_groups[$value])) {
                unset($available_groups[$value]);
            }
        }
    }

    /**
     * Retrieves the module row given the server or server group
     *
     * @param string $module_row The module row ID
     * @param string $module_group The module group (optional, default "")
     * @return mixed An stdClass object representing the module row, or null if it could not be determined
     */
    private function getModuleRowByServer($module_row, $module_group = '')
    {
        // Fetch the module row available for this package
        $row = null;
        if ($module_group == '') {
            if ($module_row > 0) {
                $row = $this->getModuleRow($module_row);
            } else {
                $rows = $this->getModuleRows();
                if (isset($rows[0])) {
                    $row = $rows[0];
                }
                unset($rows);
            }
        } else {
            // Fetch the 1st server from the list of servers in the selected group
            $rows = $this->getModuleRows($module_group);

            if (isset($rows[0])) {
                $row = $rows[0];
            }
            unset($rows);
        }

        return $row;
    }

    /**
     * Retrieves a list of server types and their language
     *
     * @return array A list of server types and their language
     */
    private function getTypes()
    {
        return [
            'openvz' => Language::_('Solusvm.types.openvz', true),
            'xen' => Language::_('Solusvm.types.xen', true),
            'xen hvm' => Language::_('Solusvm.types.xen_hvm', true),
            'kvm' => Language::_('Solusvm.types.kvm', true)
        ];
    }

    /**
     * Generates a password for SolusVM client accounts
     *
     * @param int $min_chars The minimum number of characters to generate in the password (optional, default 12)
     * @param int $max_chars The maximum number of characters to generate in the password (optional, default 12)
     * @return string A randomly-generated password
     */
    private function generatePassword($min_chars = 12, $max_chars = 12)
    {
        $password = '';
        $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        // Allow special characters
        if (Configure::get('Solusvm.password.allow_special_characters')) {
            $pool .= '!@#$%^&*()';
        }

        $pool_size = strlen($pool);
        $length = (int)abs($min_chars == $max_chars ? $min_chars : mt_rand($min_chars, $max_chars));

        for ($i=0; $i<$length; $i++) {
            $password .= substr($pool, mt_rand(0, $pool_size-1), 1);
        }

        return $password;
    }

    /**
     * Retrieves a list of rules for validating adding/editing a module row
     *
     * @param array $vars A list of input vars
     * @return array A list of rules
     */
    private function getRowRules(array &$vars)
    {
        return [
            'server_name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Solusvm.!error.server_name.empty', true)
                ]
            ],
            'user_id' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Solusvm.!error.user_id.empty', true)
                ]
            ],
            'key' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Solusvm.!error.key.empty', true)
                ]
            ],
            'host' => [
                'format' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Solusvm.!error.host.format', true)
                ]
            ],
            'port' => [
                'format' => [
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => Language::_('Solusvm.!error.port.format', true)
                ]
            ]
        ];
    }

    /**
     * Retrieves a list of rules for validating adding/editing a package
     *
     * @param array $vars A list of input vars
     * @return array A list of rules
     */
    private function getPackageRules(array $vars = null)
    {
        $rules = [
            'meta[type]' => [
                'valid' => [
                    'rule' => ['in_array', array_keys($this->getTypes())],
                    'message' => Language::_('Solusvm.!error.meta[type].valid', true)
                ]
            ],
            'meta[nodes]' => [
                'empty' => [
                    'rule' => [
                        [$this, 'validateNodeSet'],
                        (isset($vars['meta']['node_group']) ? $vars['meta']['node_group'] : null)
                    ],
                    'message' => Language::_('Solusvm.!error.meta[nodes].empty', true),
                ]
            ],
            'meta[plan]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Solusvm.!error.meta[plan].empty', true)
                ]
            ],
            'meta[set_template]' => [
                'format' => [
                    'rule' => ['in_array', ['admin', 'client']],
                    'message' => Language::_('Solusvm.!error.meta[set_template].format', true)
                ]
            ],
            'meta[total_base_ip_addresses]' => [
                'format' => [
                    'pre_format' => ['trim'],
                    'rule' => function($qty) {
                        // Only digits, and a total of 1 or more is required
                        return ((bool)preg_match('/^[0-9]+$/', $qty) && $qty > 0);
                    },
                    'message' => Language::_('Solusvm.!error.meta[total_base_ip_addresses].format', true)
                ]
            ]
        ];

        // A template must be given for this package
        if (isset($vars['meta']['set_template']) && $vars['meta']['set_template'] == 'admin') {
            $rules['meta[template]'] = [
                'empty' => [
                    'rule' => ['in_array', ['', '--none--']],
                    'negate' => true,
                    'message' => Language::_('Solusvm.!error.meta[template].empty', true)
                ]
            ];
        }

        return $rules;
    }

    /**
     * Validates that at least one node was selected when adding a package
     *
     * @param array $nodes A list of node names
     * @param string $node_groups A selected node group
     * @return bool True if at least one node was given, false otherwise
     */
    public function validateNodeSet($nodes, $node_group = null)
    {
        // Require at least one node or node group
        if ($node_group === null) {
            return (isset($nodes[0]) && !empty($nodes[0]));
        } elseif ($node_group != '') {
            return true;
        }
        return false;
    }

    /**
     * Validates that the given hostname is valid
     *
     * @param string $host_name The host name to validate
     * @param bool $require_fqdn True to require a FQDN (e.g. host.domain.com),
     *  or false for a partial name (e.g. domain.com) (optional, default false)
     * @return bool True if the hostname is valid, false otherwise
     */
    public function validateHostName($host_name, $require_fqdn = false)
    {
        if ($require_fqdn) {
            if (strlen($host_name) > 255) {
                return false;
            }

            $octet = "([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9])";
            $nested_octet = "(\." . $octet . ')';
            $hostname_regex = '/^' . $octet . $nested_octet . $nested_octet . '+$/i';

            $valid = $this->Input->matches($host_name, $hostname_regex);
        } else {
            $validator = new Server();
            $valid = $validator->isDomain($host_name) || $validator->isIp($host_name);
        }

        return $valid;
    }

    /**
     * Validates whether the given template is a valid template for this server type
     *
     * @param string $template The IOS template
     * @param string $type The type of server (i.e. "openvz", "xen hvm", "xen", "kvm")
     * @param string $module_row The server module row
     * @param string $module_group The server module group (optional, default "")
     * @return bool True if the template is valid, false otherwise
     */
    public function validateTemplate($template, $type, $module_row, $module_group = '')
    {
        // Fetch the module row
        $row = $this->getModuleRowByServer($module_row, $module_group);
        $templates = $this->getTemplates($type, $row);

        return array_key_exists($template, $templates);
    }

    /**
     * Validates whether extra IPs can be removed
     *
     * @param int $num_extra_ips The number of extra IPs
     * @param array $service_extra_ips An array of extra IP addresses set on the service
     * @param array $remove_extra_ips An array of extra IP addresses to remove
     * @return bool True if the number of IPs can be decreased, or false otherwise
     */
    public function validateRemovingExtraIps($num_extra_ips, array $service_extra_ips, array $remove_extra_ips)
    {
        // Require valid IP addresses to remove be given if decreasing the custom extra IPs
        $num_service_extra_ips = count($service_extra_ips);
        $num_extra_ips = (empty($num_extra_ips) ? 0 : (int)$num_extra_ips);

        if ($num_extra_ips < $num_service_extra_ips) {
            $total_removing = 0;

            // Validate the IP exists to be removed
            foreach ($service_extra_ips as $ip) {
                if (in_array($ip, $remove_extra_ips)) {
                    $total_removing++;
                }
            }

            // The total IPs being removed should equate to the number of current extra IPs
            if ($total_removing + $num_extra_ips != $num_service_extra_ips) {
                return false;
            }
        }

        return true;
    }

    /**
     * Performs text replacement on the given text matching the given regex
     *
     * @param string $text The string to perform replacement on
     * @param string $replacement The replacement text to use
     * @param string $regex A valid PCRE pattern
     * @return string The updated text
     */
    public function replaceText($text, $replacement, $regex)
    {
        return preg_replace($regex, $replacement, $text);
    }
}
