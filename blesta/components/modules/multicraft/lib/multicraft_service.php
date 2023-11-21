<?php
/**
 * Multicraft Server actions
 *
 * @package blesta
 * @subpackage blesta.components.modules.multicraft.lib
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class MulticraftService
{
    /**
     * @var An instance of the MulticraftApiActions
     */
    private $api;
    /**
     * @var A module row
     */
    private $module_row;
    /**
     * @var The prefix to prepend to service fields
     */
    private $service_prefix = 'multicraft_';
    /**
     * @var The default port to be used for dedicated ips
     */
    private $default_port = '25565';

    /**
     * Initialize
     *
     * @param MulticraftApi $api An instance of the Multicraft API (optional)
     * @param stdClass $module_row A module row associated with the service (optional)
     */
    public function __construct(MulticraftApiActions $api = null, stdClass $module_row = null)
    {
        // Load required components
        Loader::loadComponents($this, ['Input']);

        if ($api) {
            $this->setApi($api);
        }
        if ($module_row) {
            $this->setModuleRow($module_row);
        }
    }

    /**
     * Sets the MulticraftServerApi
     *
     * @param MulticraftApi $api A Multicraft API object
     */
    public function setApi(MulticraftApiActions $api)
    {
        $this->api = $api;
    }

    /**
     * Sets the module row in use by the associated service
     *
     * @param stdClass An stdClass object representing the module row
     */
    public function setModuleRow(stdClass $module_row)
    {
        $this->module_row = $module_row;
    }

    /**
     * Retrieves a list of Input errors, if any
     */
    public function errors()
    {
        return $this->Input->errors();
    }

    /**
     * Retrieves a list of logs performed by the last action
     *
     * @return array A nested array of log data including input and output
     */
    public function getLogs()
    {
        return $this->api->getLogs();
    }

    /**
     * Resets the logs
     */
    private function resetLogs()
    {
        $this->api->resetLogs();
    }

    /**
     * Fetches the module keys usable in email tags
     *
     * @return array A list of module email tags
     */
    public function getEmailTags()
    {
        $tags = [];

        // Set the package fields as service fields
        $meta_fields = $this->getPackageMetaFields();
        foreach ($meta_fields['all'] as $field) {
            $tags[] = $this->service_prefix . $field;
        }

        // Set service-specific fields
        $service_fields = $this->getServiceMetaFields();
        foreach ($service_fields['all'] as $field) {
            $tags[] = $this->service_prefix . $field;
        }
        foreach ($service_fields['user'] as $field) {
            $tags[] = $this->service_prefix . $field;
        }

        sort($tags);
        return $tags;
    }

    /**
     * Fetches a list of the module's dedicated IPs, if any
     *
     * @return array A list of dedicated IPs available to this module
     */
    private function getModuleDedicatedIps()
    {
        $ips = [];

        if (!empty($this->module_row->meta->daemons) && !empty($this->module_row->meta->ips)
            && !empty($this->module_row->meta->ips_in_use)
        ) {
            foreach ($this->module_row->meta->daemons as $index => $daemon_id) {
                if (isset($this->module_row->meta->ips[$index]) && isset($this->module_row->meta->ips_in_use[$index])) {
                    $ips[] = [
                        'ip' => $this->module_row->meta->ips[$index]['ip'],
                        'port' => $this->module_row->meta->ips[$index]['port'],
                        'daemon_id' => $this->module_row->meta->daemons[$index],
                        'in_use' => ($this->module_row->meta->ips_in_use[$index] == '1')
                    ];
                }
            }
        }

        return $ips;
    }

    /**
     * Fetches a list of service meta fields, not including the inherited package meta fields
     *
     * @return array An array of meta fields
     */
    private function getServiceMetaFields()
    {
        return [
            'all' => ['server_id', 'user_id'],
            'user' => ['login_username', 'login_password']
        ];
    }

    /**
     * Fetches a list of package meta fields to use as service fields
     *
     * @return array An array of meta fields
     */
    private function getPackageMetaFields()
    {
        return [
            'all' => ['daemon_id', 'server_name', 'ip', 'port', 'socket', 'players', 'memory',
                'jarfile', 'jardir', 'user_jar', 'user_name', 'user_schedule', 'user_ftp',
                'user_visibility', 'default_level', 'autostart', 'create_ftp', 'server_visibility'
            ],
            'checkboxes' => ['user_jar', 'user_name', 'user_schedule', 'user_ftp',
                'user_visibility', 'autostart', 'create_ftp'
            ]
        ];
    }

    /**
     * Fetches a list of server field mappings for service fields between what is stored by this module and
     * what the module expects in API requests
     *
     * @return array An array of fields mapped
     */
    private function getPackageFieldMapping()
    {
        return [
            'server' => ['daemon_id' => 'daemon_id', 'server_name' => 'name', 'ip' => 'ip',
                'port' => 'port', 'autostart' => 'autostart', 'default_level' => 'default_level',
                'jarfile' => 'jarfile', 'jardir' => 'jardir', 'memory' => 'memory', 'players' => 'players'
            ],
            'server_config' => ['user_ftp' => 'user_ftp', 'user_jar' => 'user_jar',
                'server_visibility' => 'visible', 'user_schedule' => 'user_schedule',
                'user_name' => 'user_name', 'user_visibility' => 'user_visibility'
            ]
        ];
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
        // Set available package meta fields
        $meta_fields = $this->getPackageMetaFields();

        $fields = [];
        foreach ($meta_fields['all'] as $field) {
            if (!array_key_exists($this->service_prefix . $field, $vars)) {
                // Default to package value
                $fields[$field] = (isset($package->meta->{$field}) ? $package->meta->{$field} : '');
            } else {
                $fields[$field] = (isset($vars[$this->service_prefix . $field])
                    ? $vars[$this->service_prefix . $field]
                    : (isset($package->meta->{$field}) ? $package->meta->{$field} : '')
                );
            }
        }

        // Overwrite any config option fields
        $config_options = ['user_players', 'players', 'jarfile', 'memory', 'daemon_id', 'dedicated_ip'];
        foreach ($config_options as $config_option) {
            if (isset($vars['configoptions'][$config_option])) {
                $fields[$config_option] = $vars['configoptions'][$config_option];
            }
        }

        return $fields;
    }

    /**
     * Retrieves a list of all daemon IDs available
     *
     * @param string $format The format to return the daemons as, one of:
     *  - csv (default) A string, CSV list
     *  - array A numerically indexed array of daemon IDs
     * @return mixed A string, or an array, of daemon IDs
     */
    private function getAllDaemons($format = 'csv')
    {
        $daemon_ids = [];
        $daemons = $this->api->listConnections();

        if (isset($daemons['success']) && $daemons['success'] == true
            && isset($daemons['data']['Daemons']) && is_array($daemons['data']['Daemons'])
        ) {
            foreach ($daemons['data']['Daemons'] as $daemon_id => $name) {
                $daemon_ids[] = $daemon_id;
            }
        }

        if ($format == 'array') {
            return $daemon_ids;
        }
        return implode(',', $daemon_ids);
    }

    /**
     * Determines which daemon is the best (i.e. has the most resources available) for new server allocations
     *
     * @param string A list of comma-separated daemon IDs (optional, defaults to choosing one from those available)
     * @param stdClass $service_fields An stdClass object representing existing service fields (optional)
     * @return int One of the given daemon IDs or 0 if none exist
     */
    private function getBestDaemon($daemon_ids = '', stdClass $service_fields = null)
    {
        // If no daemons are given, get a list of all of them
        if (empty($daemon_ids)) {
            $daemon_ids = $this->getAllDaemons();
        }

        $best_daemon = 0;
        $most_memory_available = null;
        $daemon_ids = explode(',', $daemon_ids);
        $checked = [];

        // Format the daemon IDs
        foreach ($daemon_ids as &$daemon_id) {
            $daemon_id = preg_replace('/[^0-9]/', '', $daemon_id);
        }

        // When updating a server, only change the daemon if the current one is not in the given list
        if ($service_fields && property_exists($service_fields, 'daemon_id')) {
            $current_daemon_id = $service_fields->daemon_id;

            // Don't change the daemon ID
            if (in_array($current_daemon_id, $daemon_ids)) {
                return $service_fields->daemon_id;
            }
        }

        foreach ($daemon_ids as &$daemon_id) {
            // Skip invalid daemon IDs or duplicates
            if (empty($daemon_id) || array_key_exists($daemon_id, $checked)) {
                continue;
            }

            $daemon_memory = $this->api->getConnectionMemory($daemon_id);
            $checked[$daemon_id] = ['available' => 0];

            if (isset($daemon_memory['data'])) {
                // Find the amount of memory available
                if (isset($daemon_memory['data']['total']) && isset($daemon_memory['data']['used'])) {
                    $checked[$daemon_id]['available'] = (int)$daemon_memory['data']['total']
                        - (int)$daemon_memory['data']['used'];

                    // At least select a valid daemon if one is not set
                    if ($most_memory_available === null) {
                        $best_daemon = $daemon_id;
                        $most_memory_available = 0;
                    }

                    // Check if this is the best daemon
                    if ($checked[$daemon_id]['available'] > $most_memory_available) {
                        $most_memory_available = $checked[$daemon_id]['available'];
                        $best_daemon = $daemon_id;
                    }
                }
            }
        }

        return $best_daemon;
    }

    /**
     * Updates the service config options on the server and returns the ones that have been updated
     *
     * @param int $server_id The ID of the server to update
     * @param array $vars A list of input vars
     * @param stdClass $service_fields An stdClass representing existing service fields, if any
     * @return array A key/value list of configurable option settings
     */
    private function setConfigOptions($server_id, array $vars = null, stdClass $service_fields = null)
    {
        $config_option_fields = [];

        // Add configurable options, if any
        if (!empty($vars['configoptions'])) {
            $config_fields = ['players', 'jarfile', 'memory', 'daemon_id'];
            $config_settings = [];
            foreach ($config_fields as $config_field) {
                if (array_key_exists($config_field, $vars['configoptions'])) {
                    $value = $vars['configoptions'][$config_field];
                    if ($config_field == 'jarfile') {
                        $value = ($value == 'default' ? '' : $value);
                    }

                    $config_settings[$config_field] = $value;
                }
            }
            unset($config_fields, $config_field, $value);

            // Update each config option
            if (!empty($config_settings)) {
                // Select the best daemon ID available
                if (array_key_exists('daemon_id', $config_settings)) {
                    $config_settings['daemon_id'] = $this->getBestDaemon(
                        $config_settings['daemon_id'],
                        $service_fields
                    );

                    // Default the IP/port to blank values so that they may be assigned
                    // automatically to that of the daemon
                    if (empty($vars[$this->service_prefix . 'port'])
                        && empty($vars[$this->service_prefix . 'ip'])
                    ) {
                        $config_settings['port'] = '';
                        $config_settings['ip'] = '';
                    }
                }

                // Update each config option
                $response = $this->api->updateServer(
                    $server_id,
                    array_keys($config_settings),
                    array_values($config_settings)
                );
                if (isset($response['success']) && $response['success'] == 'true') {
                    foreach ($config_settings as $key => $value) {
                        $config_option_fields[$key] = $value;
                    }
                }
                unset($key, $value);
            }

            // User can set players in their account
            if (array_key_exists('user_players', $vars['configoptions'])
                || ($service_fields && property_exists($service_fields, $this->service_prefix . 'user_players'))
            ) {
                $value = (isset($vars['configoptions']['user_players']) ? $vars['configoptions']['user_players'] : '0');
                $response = $this->api->updateServerConfig($server_id, 'user_players', $value);
                if (isset($response['success']) && $response['success'] == 'true') {
                    $config_option_fields['user_players'] = $value;
                }
            }

            // Server set to dedicated IP address
            if (array_key_exists('dedicated_ip', $vars['configoptions'])
                || ($service_fields && property_exists($service_fields, $this->service_prefix . 'dedicated_ip'))
            ) {
                $value = (isset($vars['configoptions']['dedicated_ip']) ? $vars['configoptions']['dedicated_ip'] : '0');

                // Add dedicated IP if not already set
                $dedicated_ips = $this->getModuleDedicatedIps();
                if ($value == '1') {
                    $set_ip = true;
                    $dedicated_ip_in_use = null;

                    // Determine whether a dedicated IP is already set
                    if (isset($service_fields->{$this->service_prefix . 'ip'})) {
                        foreach ($dedicated_ips as $index => $dedicated_ip) {
                            if ($dedicated_ip['ip'] == $service_fields->{$this->service_prefix . 'ip'}) {
                                if (!$dedicated_ip['in_use']) {
                                    $dedicated_ip_in_use = $index;
                                }
                                $set_ip = false;
                                break;
                            }
                        }
                    }

                    // Add a dedicated IP
                    if ($set_ip) {
                        // Get the first unused dedicated IP
                        foreach ($dedicated_ips as $index => $dedicated_ip) {
                            if (!$dedicated_ip['in_use']) {
                                $dedicated_ip_in_use = $index;
                                break;
                            }
                        }

                        if ($dedicated_ip_in_use !== null && !empty($dedicated_ips[$dedicated_ip_in_use])) {
                            // Set the IP and daemon ID
                            $ip_fields = [
                                'ip' => $dedicated_ips[$dedicated_ip_in_use]['ip'],
                                'daemon_id' => $dedicated_ips[$dedicated_ip_in_use]['daemon_id']
                            ];

                            // Set port if given
                            if ($dedicated_ips[$dedicated_ip_in_use]['port'] != '') {
                                $ip_fields['port'] = $dedicated_ips[$dedicated_ip_in_use]['port'];
                            }

                            $response = $this->api->updateServer(
                                $server_id,
                                array_keys($ip_fields),
                                array_values($ip_fields)
                            );

                            if (isset($response['success']) && $response['success'] == 'true') {
                                $config_option_fields['dedicated_ip'] = $value;
                                foreach ($ip_fields as $key => $value) {
                                    $config_option_fields[$key] = $value;
                                }
                            }
                        }
                    }

                    // Mark the dedicated IP in use by updating the module row
                    if ($dedicated_ip_in_use !== null && isset($dedicated_ips[$dedicated_ip_in_use])
                        && isset($this->module_row->id)
                    ) {
                        Loader::loadModels($this, ['ModuleManager']);
                        $module_meta = (isset($this->module_row->meta) ? (array)$this->module_row->meta : []);
                        $module_meta['ips_in_use'][$dedicated_ip_in_use] = '1';
                        $this->ModuleManager->editRow($this->module_row->id, $module_meta);
                    }
                } else {
                    $config_option_fields['dedicated_ip'] = '0';
                }
            }
        }

        return $config_option_fields;
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
     *  service of the service being added (if the current service is an addon service
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
    public function add(
        $package,
        array $vars = null,
        $parent_package = null,
        $parent_service = null,
        $status = 'pending'
    ) {
        $params = $this->getFieldsFromInput((array)$vars, $package);

        $this->validate($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            // Create a new user account
            if (!empty($vars[$this->service_prefix . 'user_id'])) {
                $user_id = $vars[$this->service_prefix . 'user_id'];
            } else {
                $user = $this->createUser($package, $vars);
                $user_id = (isset($user['id']) ? $user['id'] : null);
                if ($this->Input->errors()) {
                    return;
                }
            }

            // Create a new server
            if (!empty($vars[$this->service_prefix . 'server_id'])) {
                $server_id = $vars[$this->service_prefix . 'server_id'];
            } else {
                $server_id = $this->createServer($package, $vars);
                if ($this->Input->errors()) {
                    return;
                }
            }

            // Set this user as the server owner
            $this->api->setServerOwner($server_id, $user_id);

            // Get the mapping for the package fields to set for the server, and update them
            $field_mapping = $this->getPackageFieldMapping();
            $server_fields = [];
            foreach ($field_mapping['server'] as $service_field => $api_field) {
                if (array_key_exists($this->service_prefix . $service_field, (array)$vars)) {
                    // Skip daemon ID, IP, port, and memory updates if not set
                    if (in_array($service_field, ['daemon_id', 'ip', 'port', 'memory'])
                        && !isset($vars[$this->service_prefix . $service_field])
                    ) {
                        continue;
                    }

                    $server_fields[$api_field] = $vars[$this->service_prefix . $service_field];
                }
            }

            // Set empty values for IP and port, if not specified, so that
            // Multicraft will reset them based on the daemon
            // that it has been updated to after creation
            foreach (['ip', 'port'] as $service_field) {
                if (empty($vars[$this->service_prefix . $service_field])) {
                    $server_fields[$service_field] = '';
                }
            }

            // Choose a daemon if one is not explicitly given
            if (empty($server_fields[$field_mapping['server']['daemon_id']])
                && empty($vars[$this->service_prefix . 'daemon_id'])
            ) {
                $server_fields[$field_mapping['server']['daemon_id']] = $this->getBestDaemon();
            }

            // Update the server settings
            if (!empty($server_fields)) {
                $this->api->updateServer($server_id, array_keys($server_fields), array_values($server_fields));
            }

            // Update the server config values
            $server_config_fields = [];
            foreach ($field_mapping['server_config'] as $service_field => $api_field) {
                if (array_key_exists($this->service_prefix . $service_field, (array)$vars)) {
                    $server_config_fields[$api_field] = $vars[$this->service_prefix . $service_field];
                }
            }

            if (!empty($server_config_fields)) {
                $this->api->updateServerConfig(
                    $server_id,
                    array_keys($server_config_fields),
                    array_values($server_config_fields)
                );
            }
            unset($server_fields, $server_config_fields);

            // Create an FTP account with read/write access
            if (isset($package->meta->create_ftp) && $package->meta->create_ftp == '1') {
                $this->api->setUserFtpAccess($user_id, $server_id, 'rw');
            }

            // Add configurable options, if any
            $config_options = $this->setConfigOptions($server_id, $vars);

            // Fetch the daemon, memory and socket address
            $response = $this->api->getServer($server_id);
            if (isset($response['success']) && $response['success'] == 'true') {
                if (isset($response['data']['Server']['daemon_id'])) {
                    $params['daemon_id'] = $response['data']['Server']['daemon_id'];
                }

                if (isset($response['data']['Server']['memory'])) {
                    $params['memory'] = $response['data']['Server']['memory'];
                }

                if (isset($response['data']['Server']['ip']) && isset($response['data']['Server']['port'])) {
                    $params['ip'] = $response['data']['Server']['ip'];
                    $params['port'] = $response['data']['Server']['port'];
                    $params['socket'] = $params['ip'] . ':' . $params['port'];
                }
            }
        } else {
            // Not using the module, so set fields that may not be set otherwise
            $user_id = !empty($vars[$this->service_prefix . 'user_id'])
                ? $vars[$this->service_prefix . 'user_id']
                : '';
            $server_id = !empty($vars[$this->service_prefix . 'server_id'])
                ? $vars[$this->service_prefix . 'server_id']
                : '';
            $params['socket'] = $params['ip'] . ':' . $params['port'];
        }

        // $set the service fields
        $meta_fields = $this->getPackageMetaFields();
        $service_fields = [];

        // Merge any configurable options with the service fields
        if (isset($config_options)) {
            $params = array_merge($params, $config_options);
        }

        foreach ($meta_fields['all'] as $field) {
            $service_fields[] = [
                'key' => $this->service_prefix . $field,
                'value' => (isset($params[$field]) ? $params[$field] : ''),
                'encrypted' => 0
            ];
        }

        $service_meta_fields = $this->getServiceMetaFields();
        // Set the user and server ID response values
        foreach ($service_meta_fields['all'] as $field) {
            $value = '';
            switch ($field) {
                case 'user_id':
                    $value = (isset($user_id) ? $user_id : '');
                    break;
                case 'server_id':
                    $value = (isset($server_id) ? $server_id : '');
                    break;
            }
            $service_fields[] = [
                'key' => $this->service_prefix . $field,
                'value' => $value,
                'encrypted' => 0
            ];
        }

        // Set the username/password of the user, if available
        foreach ($service_meta_fields['user'] as $field) {
            $value = ($field == 'login_username' && isset($user['username'])
                ? $user['username']
                : ($field == 'login_password' && isset($user['password']) ? $user['password'] : '')
            );
            $service_fields[] = [
                'key' => $this->service_prefix . $field,
                'value' => $value,
                'encrypted' => ($field == 'login_password' ? 1 : 0)
            ];
        }

        return $service_fields;
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
     * @param stdClass $service_fields A set of key/value pairs representing the service fields from $service
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function edit(
        $package,
        $service,
        array $vars = null,
        $parent_package = null,
        $parent_service = null,
        $service_fields = null
    ) {
        // Validate the fields
        $this->validate($package, $vars, true);
        if ($this->Input->errors()) {
            return;
        }

        // Ensure service fields is an object
        if (!is_object($service_fields)) {
            $service_fields = new stdClass();
        }

        // Set the updated meta fields
        $params = $this->getFieldsFromInput((array)$vars, $package);
        foreach ($params as $field => $value) {
            // Skip certain fields if not set
            if (in_array($field, ['daemon_id', 'ip', 'port']) && !isset($vars[$this->service_prefix . $field])) {
                continue;
            }

            $service_fields->{$this->service_prefix . $field} = $value;
        }

        // Set specific service meta fields
        $service_meta_fields = $this->getServiceMetaFields();
        foreach ($service_meta_fields['all'] as $field) {
            // Server ID and User ID must be set to override the existing value
            if (in_array($field, ['server_id', 'user_id'])) {
                if (!empty($vars[$this->service_prefix . $field])) {
                    $service_fields->{$this->service_prefix . $field} = $vars[$this->service_prefix . $field];
                }
            } else {
                $service_fields->{$this->service_prefix . $field} = (isset($vars[$this->service_prefix . $field])
                    ? $vars[$this->service_prefix . $field]
                    : ''
                );
            }
        }

        $server_id = (isset($service_fields->{$this->service_prefix . 'server_id'})
            ? $service_fields->{$this->service_prefix . 'server_id'}
            : ''
        );
        $user_id = (isset($service_fields->{$this->service_prefix . 'user_id'})
            ? $service_fields->{$this->service_prefix . 'user_id'}
            : ''
        );

        // Only provision the service changes if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            // Set the server owner
            if (!empty($server_id) && !empty($user_id)) {
                $this->api->setServerOwner($server_id, $user_id);
            }

            // Update the server fields and config values
            $field_mapping = $this->getPackageFieldMapping();
            $server_fields = [];
            foreach ($field_mapping['server'] as $service_field => $api_field) {
                // Skip certain fields if not set
                if (in_array($service_field, ['daemon_id', 'ip', 'port'])
                    && !isset($vars[$this->service_prefix . $service_field])
                ) {
                    continue;
                }

                if (property_exists($service_fields, $this->service_prefix . $service_field)) {
                    $server_fields[$api_field] = $service_fields->{$this->service_prefix . $service_field};
                }
            }

            if (!empty($server_fields)) {
                $this->api->updateServer($server_id, array_keys($server_fields), array_values($server_fields));
            }

            $server_config_fields = [];
            foreach ($field_mapping['server_config'] as $service_field => $api_field) {
                if (property_exists($service_fields, $this->service_prefix . $service_field)) {
                    $server_config_fields[$api_field] = $service_fields->{$this->service_prefix . $service_field};
                }
            }

            if (!empty($server_config_fields)) {
                $this->api->updateServerConfig(
                    $server_id,
                    array_keys($server_config_fields),
                    array_values($server_config_fields)
                );
            }

            // Set an FTP account with read/write access for the user
            if (!empty($server_id) && !empty($user_id)
                && isset($service_fields->{$this->service_prefix . 'create_ftp'})
                && $service_fields->{$this->service_prefix . 'create_ftp'} == '1'
            ) {
                $this->api->setUserFtpAccess($user_id, $server_id, 'rw');
            }

            // Add configurable options, if any
            $config_options = $this->setConfigOptions($server_id, $vars, $service_fields);

            // Set config option values as service fields
            foreach ($config_options as $key => $value) {
                $service_fields->{$this->service_prefix . $key} = $value;
            }

            // Fetch the daemon and socket
            $response = $this->api->getServer($server_id);
            if (isset($response['success']) && $response['success'] == 'true') {
                if (isset($response['data']['Server']['daemon_id'])) {
                    $service_fields->{$this->service_prefix . 'daemon_id'} = $response['data']['Server']['daemon_id'];
                }

                if (isset($response['data']['Server']['memory'])) {
                    $service_fields->{$this->service_prefix . 'memory'} = $response['data']['Server']['memory'];
                }

                if (isset($response['data']['Server']['ip']) && isset($response['data']['Server']['port'])) {
                    $service_fields->{$this->service_prefix . 'ip'} = $response['data']['Server']['ip'];
                    $service_fields->{$this->service_prefix . 'port'} = $response['data']['Server']['port'];
                    $service_fields->{$this->service_prefix . 'socket'} = $response['data']['Server']['ip']
                        . ':' . $response['data']['Server']['port'];
                }
            }
        } else {
            // Not using the module, so set fields that may not be set otherwise
            $service_fields->{$this->service_prefix . 'memory'}
                = empty($service_fields->{$this->service_prefix . 'memory'})
                    ? '0'
                    : $service_fields->{$this->service_prefix . 'memory'};
            $service_fields->{$this->service_prefix . 'socket'} = $service_fields->{$this->service_prefix . 'ip'}
                . ':' . $service_fields->{$this->service_prefix . 'port'};
        }

        // Return all the service fields
        $fields = [];
        foreach ($service_fields as $key => $value) {
            $fields[] = [
                'key' => $key,
                'value' => $value,
                'encrypted' => ($key == $this->service_prefix . 'login_password' ? 1 : 0)
            ];
        }

        return $fields;
    }

    /**
     * Updates a server field
     *
     * @param int $server_id The ID of the server to edit
     * @param stdClass $package The associated package
     * @param int $service_id The ID of the service in Blesta
     * @param array $vars An array of key/value pairs representing the field and its value
     * @param bool $use_module True to use the module, false otherwise (optional, default true)
     */
    public function editServer($server_id, $package, $service_id, array $vars = [], $use_module = true)
    {
        if ($this->validate($package, $vars, true)) {
            Loader::loadModels($this, ['Services']);

            $temp_fields = [];
            $package_meta_fields = $this->getPackageMetaFields();
            $package_meta_mapping = $this->getPackageFieldMapping();

            // Update server fields
            foreach ($vars as $key => $value) {
                $temp_key = str_replace($this->service_prefix, '', $key);

                if (in_array($temp_key, $package_meta_fields['all'])
                    && isset($package_meta_mapping['server'][$temp_key])
                ) {
                    $this->Services->editField($service_id, ['key' => $key, 'value' => $value, 'encrypted' => 0]);

                    if ($use_module) {
                        $response = $this->api->updateServer(
                            $server_id,
                            $package_meta_mapping['server'][$temp_key],
                            $value
                        );
                    }
                }
            }
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
     * @param stdClass $service_fields A set of key/value pairs representing the service fields from $service
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function cancel($package, $service, $parent_package = null, $parent_service = null, $service_fields = null)
    {
        // Fetch the server ID
        $server_id_field = $this->service_prefix . 'server_id';
        $server_id = (property_exists($service_fields, $server_id_field) ? $service_fields->{$server_id_field} : '');

        // Delete the server, and set logs
        $response = $this->api->deleteServer($server_id);

        // Set errors, if any
        $success = (isset($response['success']) && $response['success']);
        if (!$success) {
            $this->Input->setErrors(
                ['errors' => ['internal' => Language::_('MulticraftService.!error.internal', true)]]
            );
        } else {
            // Add dedicated IP back to pool
            Loader::loadModels($this, ['ModuleManager']);
            $dedicated_ips = $this->getModuleDedicatedIps();
            $module_meta = (isset($this->module_row->meta) ? (array)$this->module_row->meta : []);
            $server_ip = (isset($service_fields->{$this->service_prefix . 'ip'})
                ? $service_fields->{$this->service_prefix . 'ip'}
                : null
            );
            $port = (isset($service_fields->{$this->service_prefix . 'port'})
                ? $service_fields->{$this->service_prefix . 'port'}
                : null
            );
            $daemon_id = (isset($service_fields->{$this->service_prefix . 'daemon_id'})
                ? $service_fields->{$this->service_prefix . 'daemon_id'}
                : null
            );

            // Find the dedicated IP that this service is using
            // Check if port is empty, which is default, to maintain backwards compatability
            foreach ($dedicated_ips as $dedicated_ip) {
                if ($dedicated_ip['ip'] == $server_ip
                    && $dedicated_ip['daemon_id'] == $daemon_id
                    && ($dedicated_ip['port'] == $port || $dedicated_ip['port'] == '')
                ) {
                    if (isset($module_meta['ips'])) {
                        // Update the dedicated IP to no longer be in use
                        foreach ($module_meta['ips'] as $index => $value) {
                            if ($value['ip'] == $dedicated_ip['ip']) {
                                $module_meta['ips_in_use'][$index] = '0';
                                $this->ModuleManager->editRow($this->module_row->id, $module_meta);
                                break 2;
                            }
                        }
                    }
                }
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
     * @param stdClass $service_fields A set of key/value pairs representing the service fields from $service
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function suspend($package, $service, $parent_package = null, $parent_service = null, $service_fields = null)
    {
        // Fetch the server ID
        $server_id_field = $this->service_prefix . 'server_id';
        $server_id = (property_exists($service_fields, $server_id_field) ? $service_fields->{$server_id_field} : '');

        // Suspend the server
        $response = $this->api->suspendServer($server_id);

        // Set errors, if any
        $success = (isset($response['success']) && $response['success']);
        if (!$success) {
            $this->Input->setErrors(
                ['errors' => ['internal' => Language::_('MulticraftService.!error.internal', true)]]
            );
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
     * @param stdClass $service_fields A set of key/value pairs representing the service fields from $service
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function unsuspend(
        $package,
        $service,
        $parent_package = null,
        $parent_service = null,
        $service_fields = null
    ) {
        // Fetch the server ID
        $server_id_field = $this->service_prefix . 'server_id';
        $server_id = (property_exists($service_fields, $server_id_field) ? $service_fields->{$server_id_field} : '');

        // Unsuspend the server
        $response = $this->api->resumeServer($server_id);

        // Set errors, if any
        $success = (isset($response['success']) && $response['success']);
        if (!$success) {
            $this->Input->setErrors(
                ['errors' => ['internal' => Language::_('MulticraftService.!error.internal', true)]]
            );
        }

        return null;
    }

    /**
     * Returns all fields to display to an admin attempting to edit a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return array An array containing a list of service fields
     */
    public function getAdminAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Set the server name
        $server_name = $fields->label(
            Language::_('MulticraftPackage.package_fields.server_name', true),
            $this->service_prefix . 'server_name'
        );
        $server_name->attach(
            $fields->fieldText(
                $this->service_prefix . 'server_name',
                (isset($vars->{$this->service_prefix . 'server_name'})
                    ? $vars->{$this->service_prefix . 'server_name'}
                    : (isset($package->meta->server_name) ? $package->meta->server_name : null)
                ),
                ['id' => $this->service_prefix . 'server_name']
            )
        );
        $fields->setField($server_name);

        // Set the server ID
        $server_id = $fields->label(
            Language::_('MulticraftService.service_fields.server_id', true),
            $this->service_prefix . 'server_id'
        );
        $server_id->attach(
            $fields->fieldText(
                $this->service_prefix . 'server_id',
                (isset($vars->{$this->service_prefix . 'server_id'}) ? $vars->{$this->service_prefix . 'server_id'} : null),
                ['id' => $this->service_prefix . 'server_id']
            )
        );
        $tooltip = $fields->tooltip(Language::_('MulticraftService.service_fields.tooltip.server_id', true));
        $server_id->attach($tooltip);
        $fields->setField($server_id);

        // Set the user ID
        $user_id = $fields->label(
            Language::_('MulticraftService.service_fields.user_id', true),
            $this->service_prefix . 'user_id'
        );
        $user_id->attach(
            $fields->fieldText(
                $this->service_prefix . 'user_id',
                (isset($vars->{$this->service_prefix . 'user_id'}) ? $vars->{$this->service_prefix . 'user_id'} : null),
                ['id'=>$this->service_prefix . 'user_id']
            )
        );
        $tooltip = $fields->tooltip(Language::_('MulticraftService.service_fields.tooltip.user_id', true));
        $user_id->attach($tooltip);
        $fields->setField($user_id);

        // Set the user ID
        $daemon_id = $fields->label(
            Language::_('MulticraftService.service_fields.daemon_id', true),
            $this->service_prefix . 'daemon_id'
        );
        $daemon_id->attach(
            $fields->fieldText(
                $this->service_prefix . 'daemon_id',
                (isset($vars->{$this->service_prefix . 'daemon_id'}) ? $vars->{$this->service_prefix . 'daemon_id'} : null),
                ['id' => $this->service_prefix . 'daemon_id']
            )
        );
        $tooltip = $fields->tooltip(Language::_('MulticraftService.service_fields.tooltip.daemon_id', true));
        $daemon_id->attach($tooltip);
        $fields->setField($daemon_id);

        // Set the server IP
        $ip = $fields->label(Language::_('MulticraftService.service_fields.ip', true), $this->service_prefix . 'ip');
        $ip->attach(
            $fields->fieldText(
                $this->service_prefix . 'ip',
                (isset($vars->{$this->service_prefix . 'ip'}) ? $vars->{$this->service_prefix . 'ip'} : null),
                ['id' => $this->service_prefix . 'ip']
            )
        );
        $tooltip = $fields->tooltip(Language::_('MulticraftService.service_fields.tooltip.ip', true));
        $ip->attach($tooltip);
        $fields->setField($ip);

        // Set the server port
        $port = $fields->label(
            Language::_('MulticraftService.service_fields.port', true),
            $this->service_prefix . 'port'
        );
        $port->attach(
            $fields->fieldText(
                $this->service_prefix . 'port',
                (isset($vars->{$this->service_prefix . 'port'})
                    ? $vars->{$this->service_prefix . 'port'}
                    : ($package->meta->port ?? null)
                ),
                ['id' => $this->service_prefix . 'port']
            )
        );
        $tooltip = $fields->tooltip(Language::_('MulticraftService.service_fields.tooltip.port', true));
        $port->attach($tooltip);
        $fields->setField($port);

        // Set the player slots
        $players = $fields->label(
            Language::_('MulticraftPackage.package_fields.players', true),
            $this->service_prefix . 'players'
        );
        $players->attach(
            $fields->fieldText(
                $this->service_prefix . 'players',
                (isset($vars->{$this->service_prefix . 'players'})
                    ? $vars->{$this->service_prefix . 'players'}
                    : (isset($package->meta->players) ? $package->meta->players : null)
                ),
                ['id' => $this->service_prefix . 'players']
            )
        );
        $fields->setField($players);

        // Set the memory (in MB)
        $memory = $fields->label(
            Language::_('MulticraftPackage.package_fields.memory', true),
            $this->service_prefix . 'memory'
        );
        $memory->attach(
            $fields->fieldText(
                $this->service_prefix . 'memory',
                (isset($vars->{$this->service_prefix . 'memory'})
                    ? $vars->{$this->service_prefix . 'memory'}
                    : (isset($package->meta->memory) ? $package->meta->memory : null)
                ),
                ['id' => $this->service_prefix . 'memory']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.memory', true));
        $memory->attach($tooltip);
        $fields->setField($memory);

        // Set the JAR file to use
        $jar = $fields->label(
            Language::_('MulticraftPackage.package_fields.jarfile', true),
            $this->service_prefix . 'jarfile'
        );
        $jar->attach(
            $fields->fieldText(
                $this->service_prefix . 'jarfile',
                (isset($vars->{$this->service_prefix . 'jarfile'})
                    ? $vars->{$this->service_prefix . 'jarfile'}
                    : (isset($package->meta->jarfile) ? $package->meta->jarfile : null)
                ),
                ['id' => $this->service_prefix . 'jarfile']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.jarfile', true));
        $jar->attach($tooltip);
        $fields->setField($jar);

        // Set the JAR file to use
        $jardir = $fields->label(
            Language::_('MulticraftPackage.package_fields.jardir', true),
            $this->service_prefix . 'jardir'
        );
        $jardir->attach(
            $fields->fieldSelect(
                $this->service_prefix . 'jardir',
                $this->getJarDirectories(),
                (isset($vars->{$this->service_prefix . 'jardir'})
                    ? $vars->{$this->service_prefix . 'jardir'}
                    : (isset($package->meta->jardir) ? $package->meta->jardir : null)
                ),
                ['id' => $this->service_prefix . 'jardir']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.jardir', true));
        $jardir->attach($tooltip);
        $fields->setField($jardir);

        // Set the JAR owner
        $label = $fields->label(
            Language::_('MulticraftPackage.package_fields.user_jar', true),
            $this->service_prefix . 'user_jar'
        );
        $label->attach(
            $fields->fieldSelect(
                $this->service_prefix . 'user_jar',
                $this->getBooleanFieldOptions(),
                (isset($vars->{$this->service_prefix . 'user_jar'})
                    ? $vars->{$this->service_prefix . 'user_jar'}
                    : (isset($package->meta->user_jar) ? $package->meta->user_jar : null)
                ),
                ['id' => $this->service_prefix . 'user_jar']
            )
        );
        $fields->setField($label);

        // Set whether the owner can set the name
        $label = $fields->label(
            Language::_('MulticraftPackage.package_fields.user_name', true),
            $this->service_prefix . 'user_name'
        );
        $label->attach(
            $fields->fieldSelect(
                $this->service_prefix . 'user_name',
                $this->getBooleanFieldOptions(),
                (isset($vars->{$this->service_prefix . 'user_name'})
                    ? $vars->{$this->service_prefix . 'user_name'}
                    : (isset($package->meta->user_name) ? $package->meta->user_name : null)
                ),
                ['id' => $this->service_prefix . 'user_name']
            )
        );
        $fields->setField($label);

        // Set whether the owner can schedule tasks
        $label = $fields->label(
            Language::_('MulticraftPackage.package_fields.user_schedule', true),
            $this->service_prefix . 'user_schedule'
        );
        $label->attach(
            $fields->fieldSelect(
                $this->service_prefix . 'user_schedule',
                $this->getBooleanFieldOptions(),
                (isset($vars->{$this->service_prefix . 'user_schedule'})
                    ? $vars->{$this->service_prefix . 'user_schedule'}
                    : (isset($package->meta->user_schedule) ? $package->meta->user_schedule : null)
                ),
                ['id' => $this->service_prefix . 'user_schedule']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.user_schedule', true));
        $label->attach($tooltip);
        $fields->setField($label);

        // Set whether the owner can give others FTP access
        $label = $fields->label(
            Language::_('MulticraftPackage.package_fields.user_ftp', true),
            $this->service_prefix . 'user_ftp'
        );
        $label->attach(
            $fields->fieldSelect(
                $this->service_prefix . 'user_ftp',
                $this->getBooleanFieldOptions(),
                (isset($vars->{$this->service_prefix . 'user_ftp'})
                    ? $vars->{$this->service_prefix . 'user_ftp'}
                    : (isset($package->meta->user_ftp) ? $package->meta->user_ftp : null)
                ),
                ['id' => $this->service_prefix . 'user_ftp']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.user_ftp', true));
        $label->attach($tooltip);
        $fields->setField($label);

        // Set whether the owner can set visibility
        $label = $fields->label(
            Language::_('MulticraftPackage.package_fields.user_visibility', true),
            $this->service_prefix . 'user_visibility'
        );
        $label->attach(
            $fields->fieldSelect(
                $this->service_prefix . 'user_visibility',
                $this->getBooleanFieldOptions(),
                (isset($vars->{$this->service_prefix . 'user_visibility'})
                    ? $vars->{$this->service_prefix . 'user_visibility'}
                    : (isset($package->meta->user_visibility) ? $package->meta->user_visibility : null)
                ),
                ['id' => $this->service_prefix . 'user_visibility']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.user_visibility', true));
        $label->attach($tooltip);
        $fields->setField($label);

        // Set the Default Role to use
        $default_level = $fields->label(
            Language::_('MulticraftPackage.package_fields.default_level', true),
            $this->service_prefix . 'default_level'
        );
        $default_level->attach(
            $fields->fieldSelect(
                $this->service_prefix . 'default_level',
                $this->getDefaultRoles(),
                (isset($vars->{$this->service_prefix . 'default_level'})
                    ? $vars->{$this->service_prefix . 'default_level'}
                    : (isset($package->meta->default_level) ? $package->meta->default_level : null)
                ),
                ['id' => $this->service_prefix . 'default_level']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.default_level', true));
        $default_level->attach($tooltip);
        $fields->setField($default_level);

        // Set whether the server autostarts
        $label = $fields->label(
            Language::_('MulticraftPackage.package_fields.autostart', true),
            $this->service_prefix . 'autostart'
        );
        $label->attach(
            $fields->fieldSelect(
                $this->service_prefix . 'autostart',
                $this->getBooleanFieldOptions(),
                (isset($vars->{$this->service_prefix . 'autostart'})
                    ? $vars->{$this->service_prefix . 'autostart'}
                    : (isset($package->meta->autostart) ? $package->meta->autostart : null)
                ),
                ['id' => $this->service_prefix . 'autostart']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.autostart', true));
        $label->attach($tooltip);
        $fields->setField($label);

        // Set whether the server autostarts
        $label = $fields->label(
            Language::_('MulticraftPackage.package_fields.create_ftp', true),
            $this->service_prefix . 'create_ftp'
        );
        $label->attach(
            $fields->fieldSelect(
                $this->service_prefix . 'create_ftp',
                $this->getBooleanFieldOptions(),
                (isset($vars->{$this->service_prefix . 'create_ftp'})
                    ? $vars->{$this->service_prefix . 'create_ftp'}
                    : (isset($package->meta->create_ftp) ? $package->meta->create_ftp : null)
                ),
                ['id' => $this->service_prefix . 'create_ftp']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.create_ftp', true));
        $label->attach($tooltip);
        $fields->setField($label);

        // Set the server visibility
        $server_visibility = $fields->label(
            Language::_('MulticraftPackage.package_fields.server_visibility', true),
            $this->service_prefix . 'server_visibility'
        );
        $server_visibility->attach(
            $fields->fieldSelect(
                $this->service_prefix . 'server_visibility',
                $this->getServerVisibilityOptions(),
                (isset($vars->{$this->service_prefix . 'server_visibility'})
                    ? $vars->{$this->service_prefix . 'server_visibility'}
                    : (isset($package->meta->server_visibility) ? $package->meta->server_visibility : null)
                ),
                ['id' => $this->service_prefix . 'server_visibility']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.server_visibility', true));
        $server_visibility->attach($tooltip);
        $fields->setField($server_visibility);

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

        $fields = new ModuleFields();

        // Set the server name iff the setting to let the user set one is allowed
        if (isset($package->meta->user_name) && $package->meta->user_name == '1') {
            $server_name = $fields->label(
                Language::_('MulticraftPackage.package_fields.server_name', true),
                $this->service_prefix . 'server_name'
            );
            $server_name->attach(
                $fields->fieldText(
                    $this->service_prefix . 'server_name',
                    (isset($vars->{$this->service_prefix . 'server_name'})
                        ? $vars->{$this->service_prefix . 'server_name'}
                        : (isset($package->meta->server_name) ? $package->meta->server_name : null)
                    ),
                    ['id' => $this->service_prefix . 'server_name']
                )
            );
            $fields->setField($server_name);
        }

        return $fields;
    }

    /**
     * Attempts to validate service info. This is the top-level error checking method. Sets Input errors on failure.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @param bool $edit True to validate an edit update, or false to validate a new service (optional, default false)
     * @return bool True if the service validates, false otherwise. Sets Input errors when false.
     */
    public function validate($package, array $vars = null, $edit = false)
    {
        $range = '(?:25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9][0-9]|[0-9])';
        $ip_address_rule = '(?:' . $range . "\." . $range . "\." . $range . "\." . $range . ')';

        $rules = [
            // Service information
            'server_id' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[0-9]*$/'],
                    'message' => Language::_('MulticraftService.!error.server_id.format', true)
                ]
            ],
            'user_id' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[0-9]*$/'],
                    'message' => Language::_('MulticraftService.!error.user_id.format', true)
                ]
            ],
            'daemon_id' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^([1-9][0-9]*)*$/'],
                    'message' => Language::_('MulticraftService.!error.daemon_id.format', true)
                ]
            ],
            'ip' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^' . $ip_address_rule . '{0,1}$/'],
                    'message' => Language::_('MulticraftService.!error.ip.format', true)
                ]
            ],
            'port' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[0-9]*$/'],
                    'message' => Language::_('MulticraftService.!error.port.format', true)
                ]
            ],

            // Additional fields that can be used as config options
            'configoptions[user_players]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1']],
                    'message' => Language::_('MulticraftService.!error.configoptions[user_players].format', true)
                ]
            ],
            'configoptions[players]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[1-9][0-9]*$/'],
                    'message' => Language::_('MulticraftService.!error.configoptions[players].format', true)
                ]
            ],
            'configoptions[memory]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => Language::_('MulticraftService.!error.configoptions[memory].format', true)
                ]
            ],
            'configoptions[daemon_id]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', "/^(([1-9][0-9]*)[\,]*)+$/"],
                    'message' => Language::_('MulticraftService.!error.configoptions[daemon_id].format', true)
                ]
            ],
            'configoptions[dedicated_ip]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1']],
                    'message' => Language::_('MulticraftService.!error.configoptions[dedicated_ip].format', true)
                ]
            ],

            // Package information used as service
            'server_name' => [
                'format' => [
                    'pre_format' => function ($server_name) use ($package) {
                        // If the package is configured to not allow the owner to set
                        // the server name, we must set the server name automatically
                        if ($server_name === null && isset($package->meta)
                            && isset($package->meta->user_name) && $package->meta->user_name == '0'
                        ) {
                            $server_name = (isset($package->meta->server_name)
                                ? $package->meta->server_name
                                : ''
                            );
                        }

                        return $server_name;
                    },
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('MulticraftPackage.!error.meta[server_name].format', true)
                ]
            ],
            'players' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => Language::_('MulticraftPackage.!error.meta[players].format', true)
                ]
            ],
            'memory' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[0-9]*$/'],
                    'message' => Language::_('MulticraftPackage.!error.meta[memory].format', true)
                ]
            ],
            'jardir' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', array_keys($this->getJarDirectories())],
                    'message' => Language::_('MulticraftPackage.!error.meta[jardir].format', true)
                ]
            ],
            'user_jar' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1']],
                    'message' => Language::_('MulticraftPackage.!error.meta[user_jar].format', true)
                ]
            ],
            'user_name' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1']],
                    'message' => Language::_('MulticraftPackage.!error.meta[user_name].format', true)
                ]
            ],
            'user_schedule' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1']],
                    'message' => Language::_('MulticraftPackage.!error.meta[user_schedule].format', true)
                ]
            ],
            'user_ftp' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1']],
                    'message' => Language::_('MulticraftPackage.!error.meta[user_ftp].format', true)
                ]
            ],
            'user_visibility' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1']],
                    'message' => Language::_('MulticraftPackage.!error.meta[user_visibility].format', true)
                ]
            ],
            'default_level' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', array_keys($this->getDefaultRoles())],
                    'message' => Language::_('MulticraftPackage.!error.meta[default_level].format', true)
                ]
            ],
            'autostart' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1']],
                    'message' => Language::_('MulticraftPackage.!error.meta[autostart].format', true)
                ]
            ],
            'create_ftp' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1']],
                    'message' => Language::_('MulticraftPackage.!error.meta[create_ftp].format', true)
                ]
            ],
            'server_visibility' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', array_keys($this->getServerVisibilityOptions())],
                    'message' => Language::_('MulticraftPackage.!error.meta[server_visibility].format', true)
                ]
            ]
        ];

        // Include the service prefix in the name of the field to validate
        foreach ($rules as $key => $value) {
            $rules[$this->service_prefix . $key] = $value;
            unset($rules[$key]);
        }

        if ($edit) {
        }

        $this->Input->setRules($rules);
        return $this->Input->validates($vars);
    }

    /**
     * Creates a new user, or uses an existing one and returns the matching user ID
     *
     * @param stdClass An stdClass object representing the package
     * @param array $vars A list of input vars
     * return array An array containing the user ID, and the
     *  username/password combination, if available; or null on failure
     */
    private function createUser($package, $vars)
    {
        Loader::loadModels($this, ['Clients']);
        $client = $this->Clients->get((isset($vars['client_id']) ? $vars['client_id'] : 0), false);

        if ($client) {
            // Attempt to find and re-use an existing user
            $user_id = $this->findUser($client->email);
            if ($user_id) {
                return ['id' => $user_id];
            }

            // Create a new user
            $password = $this->generatePassword();
            $username = $client->email;
            $response = $this->api->createUser($username, $username, $password);

            // Set errors, if any
            $success = (isset($response['success']) && $response['success']);
            if (!$success) {
                $this->Input->setErrors(
                    ['errors' => ['internal' => Language::_('MulticraftService.!error.internal', true)]]
                );
            }

            return (isset($response['data']['id']))
                ? ['id' => $response['data']['id'], 'username' => $username, 'password' => $password]
                : null;
        } else {
            // Missing client
            $this->Input->setErrors(['errors' => ['client' => Language::_('MulticraftService.!error.client', true)]]);
        }

        return null;
    }

    /**
     * Attempts to fetch a user by username
     *
     * @param string $username The user's username
     * @return mixed The ID of the user if one exists, or false otherwise
     */
    private function findUser($username)
    {
        // Find a user
        $response = $this->api->findUsers('name', $username);

        // Set errors, if any
        $success = (isset($response['success']) && $response['success']);
        if (!$success) {
            $this->Input->setErrors(
                ['errors' => ['internal' => Language::_('MulticraftService.!error.internal', true)]]
            );
        }

        if (isset($response) && isset($response['data']) && isset($response['data']['Users'])) {
            foreach ($response['data']['Users'] as $user_id => $name) {
                if ($name == $username) {
                    return $user_id;
                }
            }
        }

        return false;
    }

    /**
     * Creates a new server
     *
     * @param stdClass An stdClass object representing the package
     * @param array $vars A list of input vars
     * return mixed $server_id The ID of the server, or null on failure
     */
    private function createServer($package, $vars)
    {
        // Create server
        $players = (isset($vars[$this->service_prefix . 'players'])
            ? $vars[$this->service_prefix . 'players']
            : (isset($package->meta->players) ? $package->meta->players : 1)
        );
        $data = ['name' => '', 'port' => '', 'base' => '', 'players' => $players];
        $response = $this->api->createServer($data['name'], $data['port'], $data['base'], $data['players']);

        // Set errors, if any
        $success = (isset($response['success']) && $response['success']);
        if (!$success) {
            $this->Input->setErrors(
                ['errors' => ['internal' => Language::_('MulticraftService.!error.internal', true)]]
            );
        }

        return (isset($response['data']['id']) ? $response['data']['id'] : null);
    }

    /**
     * Retrieves a list of JAR directories
     *
     * @return array A key/value array of JAR directories and their names
     */
    public function getJarDirectories()
    {
        return [
            'daemon' => Language::_('MulticraftPackage.package_fields.jardir_daemon', true),
            'server' => Language::_('MulticraftPackage.package_fields.jardir_server', true),
            'server_base' => Language::_('MulticraftPackage.package_fields.jardir_server_base', true)
        ];
    }

    /**
     * Retrieves a list of boolean fields useful for service field drop-downs
     *
     * @return array A key/value array of boolean values and their names
     */
    public function getBooleanFieldOptions()
    {
        return [
            '1' => Language::_('MulticraftService.service_fields.yes', true),
            '0' => Language::_('MulticraftService.service_fields.no', true)
        ];
    }

    /**
     * Retrieves a list of default roles
     *
     * @return array A key/value array of default roles and their names
     */
    public function getDefaultRoles()
    {
        return [
            '0' => Language::_('MulticraftPackage.package_fields.default_level_0', true),
            '10' => Language::_('MulticraftPackage.package_fields.default_level_10', true),
            '20' => Language::_('MulticraftPackage.package_fields.default_level_20', true),
            '30' => Language::_('MulticraftPackage.package_fields.default_level_30', true)
        ];
    }

    /**
     * Gets the default port for dedicated ips
     *
     * @return string The default port number for dedicated ips
     */
    public function getDefaultPort()
    {
        return $this->default_port;
    }

    /**
     * Retrieves a list of server visibility options
     *
     * @param array A key/value array of visibility options and their names
     */
    public function getServerVisibilityOptions()
    {
        return [
            '0' => Language::_('MulticraftPackage.package_fields.server_visibility_0', true),
            '1' => Language::_('MulticraftPackage.package_fields.server_visibility_1', true),
            '2' => Language::_('MulticraftPackage.package_fields.server_visibility_2', true)
        ];
    }

    /**
     * Generates a password
     *
     * @param int $min_chars The minimum number of characters to generate in the password (optional, default 12)
     * @param int $max_chars The maximum number of characters to generate in the password (optional, default 12)
     * @return string A randomly-generated password
     */
    private function generatePassword($min_chars = 12, $max_chars = 12)
    {
        $password = '';
        $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';

        $pool_size = strlen($pool);
        $length = (int)abs($min_chars == $max_chars ? $min_chars : mt_rand($min_chars, $max_chars));

        for ($i=0; $i<$length; $i++) {
            $password .= substr($pool, mt_rand(0, $pool_size-1), 1);
        }

        return $password;
    }
}
