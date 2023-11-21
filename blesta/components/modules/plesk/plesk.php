<?php
use Blesta\Core\Util\Validate\Server;
/**
 * Plesk Module
 *
 * @package blesta
 * @subpackage blesta.components.modules.plesk
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Plesk extends Module
{
    /**
     * @var array A list of Plesk panel versions
     */
    private $panel_versions = [];

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
        Language::loadLang('plesk', null, dirname(__FILE__) . DS . 'language' . DS);

        // Setup panel versions
        $this->init();
    }

    /**
     * Initializes the panel versions
     */
    private function init()
    {
        $windows = Language::_('Plesk.panel_version.windows', true);
        $linux = Language::_('Plesk.panel_version.linux', true);

        $versions = [
            '7.5.4' => [
                'name' => Language::_('Plesk.panel_version.plesk_type', true, '7.5.4', $linux),
                'api_version' => '1.3.5.1',
                'supported' => false
            ],
            '7.5.6' => [
                'name' => Language::_('Plesk.panel_version.plesk_type', true, '7.5.6', $windows),
                'api_version' => '1.4.0.0',
                'supported' => false
            ],
            '7.6' => [
                'name' => Language::_('Plesk.panel_version.plesk_type', true, '7.6', $windows),
                'api_version' => '1.4.0.0',
                'supported' => false
            ],
            '7.6.1' => [
                'name' => Language::_('Plesk.panel_version.plesk_type', true, '7.6.1', $windows),
                'api_version' => '1.4.1.1',
                'supported' => false
            ],
            '8.0' => [
                'name' => Language::_('Plesk.panel_version.plesk_type', true, '8.0', $linux),
                'api_version' => '1.4.0.0',
                'supported' => false
            ],
            '8.0.1' => [
                'name' => Language::_('Plesk.panel_version.plesk_type', true, '8.0.1', $linux),
                'api_version' => '1.4.1.2',
                'supported' => false
            ],
            '8.1.0' => [
                'name' => Language::_('Plesk.panel_version.plesk', true, '8.1.0'),
                'api_version' => '1.4.2.0',
                'supported' => false
            ],
            '8.1.1' => [
                'name' => Language::_('Plesk.panel_version.plesk', true, '8.1.1'),
                'api_version' => '1.5.0.0',
                'supported' => false
            ],
            '8.2' => [
                'name' => Language::_('Plesk.panel_version.plesk', true, '8.2'),
                'api_version' => '1.5.1.0',
                'supported' => false
            ],
            '8.3' => [
                'name' => Language::_('Plesk.panel_version.plesk', true, '8.3'),
                'api_version' => '1.5.2.0',
                'supported' => false
            ],
            '8.4' => [
                'name' => Language::_('Plesk.panel_version.plesk', true, '8.4'),
                'api_version' => '1.5.2.1',
                'supported' => false
            ],
            '8.6' => [
                'name' => Language::_('Plesk.panel_version.plesk', true, '8.6'),
                'api_version' => '1.5.2.1',
                'supported' => false
            ],
            '9.0.0' => [
                'name' => Language::_('Plesk.panel_version.parallels', true, '9.0.0'),
                'api_version' => '1.6.0.0',
                'supported' => false
            ],
            '9.0.1' => [
                'name' => Language::_('Plesk.panel_version.parallels', true, '9.0.1'),
                'api_version' => '1.6.0.1',
                'supported' => false
            ],
            '9.0.2' => [
                'name' => Language::_('Plesk.panel_version.parallels', true, '9.0.2'),
                'api_version' => '1.6.0.2',
                'supported' => false
            ],
            '10.0' => [
                'name' => Language::_('Plesk.panel_version.parallels', true, '10.0'),
                'api_version' => '1.6.3.0',
                'supported' => true
            ],
            '10.1' => [
                'name' => Language::_('Plesk.panel_version.parallels', true, '10.1'),
                'api_version' => '1.6.3.1',
                'supported' => true
            ],
            '10.2' => [
                'name' => Language::_('Plesk.panel_version.parallels', true, '10.2'),
                'api_version' => '1.6.3.2',
                'supported' => true
            ],
            '10.3' => [
                'name' => Language::_('Plesk.panel_version.parallels', true, '10.3'),
                'api_version' => '1.6.3.3',
                'supported' => true
            ],
            '10.4' => [
                'name' => Language::_('Plesk.panel_version.parallels', true, '10.4'),
                'api_version' => '1.6.3.4',
                'supported' => true
            ],
            '11.0' => [
                'name' => Language::_('Plesk.panel_version.parallels', true, '11.0'),
                'api_version' => '1.6.3.5',
                'supported' => true
            ],
            '11.1.0' => [
                'name' => Language::_('Plesk.panel_version.parallels', true, '11.1.0'),
                'api_version' => '1.6.4.0',
                'supported' => true
            ],
            '11.5' => [
                'name' => Language::_('Plesk.panel_version.parallels', true, '11.5'),
                'api_version' => '1.6.5.0',
                'supported' => true
            ],
            '12.0' => [
                'name' => Language::_('Plesk.panel_version.parallels', true, '12.0'),
                'api_version' => '1.6.6.0',
                'supported' => true
            ],
            '12.5' => [
                'name' => Language::_('Plesk.panel_version.parallels', true, '12.5'),
                'api_version' => '1.6.7.0',
                'supported' => true
            ]
        ];

        $this->panel_versions = array_reverse($versions);
    }

    /**
     * Retrieves the API version based on the panel version in use
     *
     * @param string $panel_version The version number of the panel
     * @return string The API version to use for this panel
     */
    private function getApiVersion($panel_version)
    {
        return (empty($panel_version) ? '' : $this->panel_versions[$panel_version]['api_version']);
    }

    /**
     * Retrieves Plesk panel versions that are supported by this module
     *
     * @param bool $format True to format the versions as name/value pairs, false for the entire array
     * @return array A list of supported versions
     */
    private function getSupportedPanelVersions($format = false)
    {
        $versions = ['' => Language::_('Plesk.panel_version.latest', true)];
        foreach ($this->panel_versions as $panel_version => $panel) {
            if ($panel['supported']) {
                if ($format) {
                    $versions[$panel_version] = $panel['name'];
                } else {
                    $versions[$panel_version] = $panel;
                }
            }
        }
        return $versions;
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
            'tabStats' => Language::_('Plesk.tab_stats', true)
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
            'tabClientStats' => Language::_('Plesk.tab_client_stats', true)
        ];
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
            $errors['simplexml']['required'] = Language::_('Plesk.!error.simplexml_required', true);
        }

        if (!empty($errors)) {
            $this->Input->setErrors($errors);
            return;
        }
    }

    /**
     * Performs migration of data from $current_version (the current installed version)
     * to the given file set version. Sets Input errors on failure, preventing
     * the module from being upgraded.
     *
     * @param string $current_version The current installed version of this module
     */
    public function upgrade($current_version)
    {
        if (version_compare($current_version, '2.9.1', '<')) {
            if (!isset($this->ModuleManager)) {
                Loader::loadModels($this, ['ModuleManager']);
            }

            // Update all module rows to update hostname to match the IP if not set
            $modules = $this->ModuleManager->getByClass('plesk');
            foreach ($modules as $module) {
                $rows = $this->ModuleManager->getRows($module->id);
                foreach ($rows as $row) {
                    $meta = (array)$row->meta;
                    if (empty($meta['host_name'])) {
                        $meta['host_name'] = $meta['ip_address'];
                        $this->ModuleManager->editRow($row->id, $meta);
                    }
                }
            }
        }

        if (version_compare($current_version, '2.14.1', '<')) {
            if (!isset($this->ModuleManager)) {
                Loader::loadModels($this, ['ModuleManager']);
            }

            // Update all module rows to set limits
            $modules = $this->ModuleManager->getByClass('plesk');
            foreach ($modules as $module) {
                $rows = $this->ModuleManager->getRows($module->id);
                foreach ($rows as $row) {
                    $meta = (array)$row->meta;
                    if (empty($meta[$this->module_row_field_limit])) {
                        $meta[$this->module_row_field_limit] = '';
                        $meta[$this->module_row_field_total] = 0;
                        $this->ModuleManager->editRow($row->id, $meta);
                    }
                }
            }
        }
    }

    /**
     * Checks whether the given webspace ID exists in Plesk
     *
     * @param int $webspace_id The subscription webspace ID to check
     * @param stdClass $package An stdClass object representing the package
     * @return bool True if the webspace exists, false otherwise
     */
    public function validateWebspaceExists($webspace_id, $package)
    {
        // Get module row and API
        $module_row = $this->getModuleRowByServer(
            (isset($package->module_row) ? $package->module_row : 0),
            (isset($package->module_group) ? $package->module_group : '')
        );

        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->username,
            $module_row->meta->password,
            $module_row->meta->port
        );
        $api_version = $this->getApiVersion($module_row->meta->panel_version);

        // Fetch the webspace/domain
        try {
            $subscription = $api->loadCommand('plesk_subscriptions', [$api_version]);

            $data = ['id' => $webspace_id];

            $this->log($module_row->meta->host_name . '|webspace:get', serialize($data), 'input', true);
            $response = $this->parseResponse($subscription->get($data), $module_row, true);

            if ($response && $response->result->status == 'ok') {
                return true;
            }
        } catch (Exception $e) {
            // API request failed
            $this->Input->setErrors(['api' => ['internal' => Language::_('Plesk.!error.api.internal', true)]]);
        }

        return false;
    }

    /**
     * Checks whether the given plan ID exists in Plesk
     *
     * @param int $plan_id The service plan ID
     * @param stdClass $package An stdClass object representing the package
     * @param bool $reseller True if the plan is a reseller plan, false for a hosting plan (optional, default false)
     * @return bool True if the plan exists, false otherwise
     */
    public function validatePlanExists($plan_id, $package, $reseller = false)
    {
        // Get module row and API
        $module_row = $this->getModuleRowByServer(
            (isset($package->module_row) ? $package->module_row : 0),
            (isset($package->module_group) ? $package->module_group : '')
        );

        // Fetch the plans
        $plans = $this->getPleskPlans($module_row, $reseller);

        return (isset($plans[$plan_id]));
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
        $this->Input->setRules($this->getServiceRules($vars, $package));
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
        $package = isset($service->package) ? $service->package : null;

        $this->Input->setRules($this->getServiceRules($vars, $package, true));
        return $this->Input->validates($vars);
    }

    /**
     * Returns the rule set for adding/editing a service
     *
     * @param array $vars A list of input vars
     * @param stdClass $package A stdClass object representing the selected package
     * @param bool $edit True to get the edit rules, false for the add rules
     * @return array Service rules
     */
    private function getServiceRules(array $vars = null, $package = null, $edit = false)
    {
        // Set rules
        $rules = [
            'plesk_domain' => [
                'format' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Plesk.!error.plesk_domain.format', true)
                ]
            ],
            'plesk_username' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['betweenLength', 1, 32],
                    'message' => Language::_('Plesk.!error.plesk_username.length', true)
                ]
            ],
            'plesk_password' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['betweenLength', 16, 20],
                    'message' => Language::_('Plesk.!error.plesk_password.length', true)
                ]
            ],
            'plesk_confirm_password' => [
                'matches' => [
                    'if_set' => true,
                    'rule' => ['compares', '==', (isset($vars['plesk_password']) ? $vars['plesk_password'] : '')],
                    'message' => Language::_('Plesk.!error.plesk_confirm_password.matches', true)
                ]
            ],
            'plesk_webspace_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateWebspaceExists'], $package],
                    'message' => Language::_('Plesk.!error.plesk_webspace_id.exists', true)
                ]
            ]
        ];

        // Set the values that may be empty
        $empty_values = ['plesk_username', 'plesk_password', 'plesk_confirm_password'];
        if (!$edit) {
            $empty_values[] = 'plesk_webspace_id';
        } else {
            // On edit, domain is optional
            $rules['plesk_domain']['format']['if_set'] = true;
        }

        // Remove rules on empty fields
        foreach ($empty_values as $value) {
            // Confirm password must be given if password is too
            if ($value == 'plesk_confirm_password' && !empty($vars['plesk_password'])) {
                continue;
            }

            if (empty($vars[$value])) {
                unset($rules[$value]);
            }
        }

        return $rules;
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
    public function addService(
        $package,
        array $vars = null,
        $parent_package = null,
        $parent_service = null,
        $status = 'pending'
    ) {
        // Get module row and API
        $module_row = $this->getModuleRow();
        if (!$module_row) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Plesk.!error.module_row.missing', true)]]
            );
            return;
        }

        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->username,
            $module_row->meta->password,
            $module_row->meta->port
        );
        $client_id = $vars['client_id'];

        // If no username or password given, generate them
        if (empty($vars['plesk_username'])) {
            $vars['plesk_username'] = $this->generateUsername(
                (isset($vars['plesk_domain']) ? $vars['plesk_domain'] : '')
            );
        }
        $vars['plesk_username'] = strtolower($vars['plesk_username']);

        if (empty($vars['plesk_password'])) {
            $vars['plesk_password'] = $this->generatePassword();
            $vars['plesk_confirm_password'] = $vars['plesk_password'];
        }

        $params = $this->getFieldsFromInput((array)$vars, $package);

        $this->validateService($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            $api_version = $this->getApiVersion($module_row->meta->panel_version);

            // Create a reseller account
            if ($package->meta->type == 'reseller') {
                $response = $this->createResellerAccount($module_row, $package, $client_id, $params);
            } else {
                // Create a user account
                $response = $this->createCustomerAccount($module_row, $package, $client_id, $params);
            }

            if ($this->Input->errors()) {
                return;
            }

            // Create the webspace/domain subscription service
            try {
                $subscription = $api->loadCommand('plesk_subscriptions', [$api_version]);
                $plan = ['id' => $package->meta->plan];

                $data = [
                    'general' => [
                        'name' => $params['domain'],
                        'ip_address' => $module_row->meta->ip_address,
                        'owner_login' => $params['username'],
                        'htype' => 'vrt_hst',
                        'status' => '0'
                    ],
                    'hosting' => [
                        'properties' => [
                            'ftp_login' => $params['username'],
                            'ftp_password' => $params['password']
                        ],
                        'ipv4' => $module_row->meta->ip_address
                    ]
                ];

                // Set the plan on the subscription only for non-resellers;
                // The reseller has the plan associated with their account
                if ($package->meta->type != 'reseller') {
                    $data['plan'] = $plan;
                }

                $masked_data = $data;
                $masked_data['hosting']['properties']['ftp_password'] = '***';

                $this->log($module_row->meta->host_name . '|webspace:add', serialize($masked_data), 'input', true);
                $response = $this->parseResponse($subscription->add($data), $module_row);

                // Set the webspace ID
                if (property_exists($response->result, 'id')) {
                    $params['webspace_id'] = $response->result->id;
                }

                // Update the number of accounts on the server
                $this->updateAccountCount($module_row);
            } catch (Exception $e) {
                // API request failed
                $this->Input->setErrors(['api' => ['internal' => Language::_('Plesk.!error.api.internal', true)]]);
                return;
            }
        }

        // Return service fields
        return [
            [
                'key' => 'plesk_domain',
                'value' => $params['domain'],
                'encrypted' => 0
            ],
            [
                'key' => 'plesk_username',
                'value' => $params['username'],
                'encrypted' => 0
            ],
            [
                'key' => 'plesk_password',
                'value' => $params['password'],
                'encrypted' => 1
            ],
            [
                'key' => 'plesk_webspace_id',
                'value' => (isset($response) && property_exists($response->result, 'id')
                    ? $response->result->id
                    : null
                ),
                'encrypted' => 0
            ]
        ];
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
    public function editService(
        $package,
        $service,
        array $vars = null,
        $parent_package = null,
        $parent_service = null
    ) {
        // Get module row and API
        $module_row = $this->getModuleRow();
        if (!$module_row) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Plesk.!error.module_row.missing', true)]]
            );
            return;
        }

        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->username,
            $module_row->meta->password,
            $module_row->meta->port
        );
        $client_id = $service->client_id;

        // If no username or password given, generate them
        if (isset($vars['plesk_username']) && $vars['plesk_username'] == '') {
            $vars['plesk_username'] = $this->generateUsername(
                (isset($vars['plesk_domain']) ? $vars['plesk_domain'] : '')
            );
        }
        $vars['plesk_username'] = strtolower($vars['plesk_username']);

        if (isset($vars['plesk_password']) && $vars['plesk_password'] == '') {
            $vars['plesk_password'] = $this->generatePassword();
            $vars['plesk_confirm_password'] = $vars['plesk_password'];
        }

        $params = $this->getFieldsFromInput((array)$vars, $package);

        $this->validateServiceEdit($service, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Only use the module to update the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            $api_version = $this->getApiVersion($module_row->meta->panel_version);

            // Update the reseller account
            if ($package->meta->type == 'reseller') {
                $response = $this->updateResellerAccount($module_row, $service_fields, $params);
            } else {
                // Update the user account
                $response = $this->updateCustomerAccount($module_row, $service_fields, $params);
            }

            if ($this->Input->errors()) {
                return;
            }

            // Set updated fields
            if ($response && $response->result->status == 'ok') {
                $service_fields->plesk_username = $params['username'];
                $service_fields->plesk_password = $params['password'];
            }

            // Update the webspace/domain
            try {
                $subscription = $api->loadCommand('plesk_subscriptions', [$api_version]);

                // Set the information to update
                $data = [
                    'filter' => [],
                    'general' => ['name' => $params['domain']]
                ];

                // Identify the subscription to change by name (domain), subscription ID, or by the customer login user
                if (!empty($service_fields->plesk_domain)) {
                    $data['filter']['name'] = $service_fields->plesk_domain;
                } elseif (!empty($service_fields->plesk_webspace_id)) {
                    $data['filter']['id'] = $service_fields->plesk_webspace_id;
                } elseif (!empty($service_fields->plesk_username)) {
                    $data['filter']['owner_login'] = $service_fields->plesk_username;
                }

                $this->log($module_row->meta->host_name . '|webspace:set', serialize($data), 'input', true);
                $response = $this->parseResponse($subscription->set($data), $module_row);

                // Set updated fields
                if ($response && $response->result->status == 'ok') {
                    $service_fields->plesk_domain = $params['domain'];
                }
            } catch (Exception $e) {
                // API request failed
                $this->Input->setErrors(['api' => ['internal' => Language::_('Plesk.!error.api.internal', true)]]);
            }

            if ($this->Input->errors()) {
                return;
            }
        }

        // Set fields to update locally
        $fields = ['plesk_username', 'plesk_password', 'plesk_domain', 'plesk_webspace_id'];
        foreach ($fields as $field) {
            if (property_exists($service_fields, $field) && isset($vars[$field])) {
                $service_fields->{$field} = $vars[$field];
            }
        }
        $service_fields->plesk_domain = $params['domain'];

        // Return all the service fields
        $fields = [];
        $encrypted_fields = ['plesk_password'];
        foreach ($service_fields as $key => $value) {
            $fields[] = ['key' => $key, 'value' => $value, 'encrypted' => (in_array($key, $encrypted_fields) ? 1 : 0)];
        }

        return $fields;
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
        if (($module_row = $this->getModuleRow())) {
            $api = $this->getApi(
                $module_row->meta->host_name,
                $module_row->meta->username,
                $module_row->meta->password,
                $module_row->meta->port
            );
            $api_version = $this->getApiVersion($module_row->meta->panel_version);
            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Cancel (delete) the service (webspace subscription)
            try {
                $subscription = $api->loadCommand('plesk_subscriptions', [$api_version]);

                // Identify the subscription by name (domain) or by the subscription webspace ID
                $data = [];
                if (!empty($service_fields->plesk_domain)) {
                    $data['names'] = [$service_fields->plesk_domain];
                } elseif (!empty($service_fields->plesk_webspace_id)) {
                    $data['ids'] = [$service_fields->plesk_webspace_id];
                }


                // Some filter options must be set to avoid Plesk deleting everything
                if (empty($data['names']) && empty($data['ids'])) {
                    $this->Input->setErrors(
                        [
                            'api' =>
                            ['filter-missing' => Language::_('Plesk.!error.api.webspace_delete_filter_missing', true)]
                        ]
                    );
                    return;
                }

                $this->log($module_row->meta->host_name . '|webspace:del', serialize($data), 'input', true);
                $response = $this->parseResponse($subscription->delete($data), $module_row);
            } catch (Exception $e) {
                // API request failed
                $this->Input->setErrors(['api' => ['internal' => Language::_('Plesk.!error.api.internal', true)]]);
            }

            if ($this->Input->errors()) {
                return;
            }

            try {
                // Check for existing subscriptions under this user account
                $subscription = $api->loadCommand('plesk_subscriptions', [$api_version]);
                $subscription_response = $this->parseResponse(
                    $subscription->get(['owner_login' => $service_fields->plesk_username])
                );
            } catch (Exception $e) {
                // API request failed
                $this->Input->setErrors(['api' => ['internal' => Language::_('Plesk.!error.api.internal', true)]]);
            }

            if (isset($subscription_response->result)
                && !is_array($subscription_response->result)
                && !isset($subscription_response->result->data)
            ) {
                // Delete the customer/reseller account if there are no more subscriptions on the account
                if ($package->meta->type == 'reseller') {
                    $this->deleteResellerAccount($module_row, $service_fields);
                } else {
                    $this->deleteCustomerAccount($module_row, $service_fields);
                }
            }

            // Update the number of accounts on the server
            $this->updateAccountCount($module_row);
        } else {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Plesk.!error.module_row.missing', true)]]
            );
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
        // Suspend the subscription
        $this->changeSubscriptionStatus($package, $service, $parent_package, $parent_service, true);

        if ($this->Input->errors()) {
            return;
        }

        // Suspend the customer/reseller account
        $this->changeAccountStatus($package, $service, $parent_package, $parent_service, true);
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
        // Unsuspend the subscription
        $this->changeSubscriptionStatus($package, $service, $parent_package, $parent_service, false);

        if ($this->Input->errors()) {
            return;
        }

        // Unsuspends the customer/reseller account
        $this->changeAccountStatus($package, $service, $parent_package, $parent_service, false);
        return null;
    }

    /**
     * Suspends or unsuspends a subscription. Sets Input errors on failure,
     * preventing the service from being (un)suspended.
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
     * @param $suspend True to suspend,  false to unsuspend (optional, default true)
     * @see Plesk::suspendService(), Plesk::unsuspendService()
     */
    private function changeSubscriptionStatus(
        $package,
        $service,
        $parent_package = null,
        $parent_service = null,
        $suspend = true
    ) {
        if (($module_row = $this->getModuleRow())) {
            $api = $this->getApi(
                $module_row->meta->host_name,
                $module_row->meta->username,
                $module_row->meta->password,
                $module_row->meta->port
            );
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $reseller = (isset($module_row->meta->reseller) && $module_row->meta->reseller == 'true');

            // Suspend/unsuspend the service (webspace subscription)
            try {
                $subscription = $api->loadCommand(
                    'plesk_subscriptions',
                    [$this->getApiVersion($module_row->meta->panel_version)]
                );

                // Change the general information status
                $data = ['filter' => [], 'general' => ['status' => ($suspend ? ($reseller ? '32' : '16') : '0')]];

                // Identify the subscription to update by name (domain), subscription ID, or by the customer login user
                if (!empty($service_fields->plesk_domain)) {
                    $data['filter']['name'] = $service_fields->plesk_domain;
                } elseif (!empty($service_fields->plesk_webspace_id)) {
                    $data['filter']['id'] = $service_fields->plesk_webspace_id;
                } elseif (!empty($service_fields->plesk_username)) {
                    $data['filter']['owner_login'] = $service_fields->plesk_username;
                }

                $this->log($module_row->meta->host_name . '|webspace:set', serialize($data), 'input', true);
                $response = $this->parseResponse($subscription->set($data), $module_row);
            } catch (Exception $e) {
                // API request failed
                $this->Input->setErrors(['api' => ['internal' => Language::_('Plesk.!error.api.internal', true)]]);
            }
        } else {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Plesk.!error.module_row.missing', true)]]
            );
            return;
        }
    }

    /**
     * Suspends or unsuspends a customer. Sets Input errors on failure,
     * preventing the service from being (un)suspended.
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
     * @param $suspend True to suspend,  false to unsuspend (optional, default true)
     * @see Plesk::suspendService(), Plesk::unsuspendService()
     */
    private function changeAccountStatus(
        $package,
        $service,
        $parent_package = null,
        $parent_service = null,
        $suspend = true
    ) {
        if (($module_row = $this->getModuleRow())) {
            $api = $this->getApi(
                $module_row->meta->host_name,
                $module_row->meta->username,
                $module_row->meta->password,
                $module_row->meta->port
            );
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $reseller_account = (isset($module_row->meta->reseller) && $module_row->meta->reseller == 'true');

            // Suspend/unsuspend the account
            try {
                if ($package->meta->type == 'reseller') {
                    // Update reseller account
                    $reseller = $api->loadCommand(
                        'plesk_reseller_accounts',
                        [$this->getApiVersion($module_row->meta->panel_version)]
                    );

                    $data = [
                        'filter' => ['login' => $service_fields->plesk_username],
                        'general' => ['status' => ($suspend ? ($reseller_account ? '32' : '16') : '0')]
                    ];

                    $this->log($module_row->meta->host_name . '|reseller:set', serialize($data), 'input', true);
                    $response = $this->parseResponse($reseller->set($data), $module_row, true);
                } else {
                    // Update customer account
                    $customer = $api->loadCommand(
                        'plesk_customer_accounts',
                        [$this->getApiVersion($module_row->meta->panel_version)]
                    );

                    $data = [
                        'filter' => [
                            'login' => $service_fields->plesk_username],
                            'general' => ['status' => ($suspend ? ($reseller_account ? '32' : '16') : '0')]
                        ];

                    $this->log($module_row->meta->host_name . '|customer:set', serialize($data), 'input', true);
                    $response = $this->parseResponse($customer->set($data), $module_row, true);
                }
            } catch (Exception $e) {
                // API request failed
                $this->Input->setErrors(['api' => ['internal' => Language::_('Plesk.!error.api.internal', true)]]);
            }
        } else {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Plesk.!error.module_row.missing', true)]]
            );
            return;
        }
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
        if (($module_row = $this->getModuleRow())) {
            if (!isset($this->DataStructure)) {
                Loader::loadHelpers($this, ['DataStructure']);
            }
            if (!isset($this->ArrayHelper)) {
                $this->ArrayHelper = $this->DataStructure->create('Array');
            }

            $api = $this->getApi(
                $module_row->meta->host_name,
                $module_row->meta->username,
                $module_row->meta->password,
                $module_row->meta->port
            );

            // Set the plan/type to update
            $update_plan = [
                'reseller' => false,
                'to_plan' => $package_to->meta->plan,
                'from_plan' => $package_from->meta->plan
            ];

            // Set whether a reseller plan is being changed
            $from_reseller_plan = (isset($package_from->meta->reseller_plan)
                ? $package_from->meta->reseller_plan
                : null
            );
            $to_reseller_plan = (isset($package_to->meta->reseller_plan)
                ? $package_to->meta->reseller_plan
                : null
            );

            // Reseller plan changed, upgrade the customer and set the reseller plan to update
            if ($from_reseller_plan != $to_reseller_plan) {
                // Changing reseller plans
                $update_plan['reseller'] = true;
                $update_plan['to_plan'] = $to_reseller_plan;
                $update_plan['from_plan'] = $from_reseller_plan;

                // Cannot downgrade from reseller account to customer account
                if (!empty($from_reseller_plan) && empty($to_reseller_plan)) {
                    $this->Input->setErrors(
                        ['downgrade' => ['unsupported' => Language::_('Plesk.!error.downgrade.unsupported', true)]]
                    );
                } elseif (empty($from_reseller_plan) && !empty($to_reseller_plan)) {
                    // Upgrade the customer account to a reseller account
                    $this->upgradeCustomerToReseller($module_row, $service);
                }
            }

            // Do not continue if there are errors
            if ($this->Input->errors()) {
                return;
            }

            // Only change a plan change if it has changed; a customer account plan or a reseller plan
            if ($update_plan['from_plan'] != $update_plan['to_plan']) {
                $service_fields = $this->serviceFieldsToObject($service->fields);

                // Fetch all of the plans
                $plans = $this->getPleskPlans($module_row, $update_plan['reseller'], false);

                // Determine the plan's GUID based on the plan ID we currently have
                $plans = $this->ArrayHelper->numericToKey($plans, 'id', 'guid');
                $plan_guid = '';
                if (isset($plans[$update_plan['to_plan']])) {
                    $plan_guid = $plans[$update_plan['to_plan']];
                }

                $api_version = $this->getApiVersion($module_row->meta->panel_version);

                // Switch reseller plan
                if ($update_plan['reseller']) {
                    try {
                        // Change customer account subscription plan
                        $reseller = $api->loadCommand('plesk_reseller_accounts', [$api_version]);

                        // Set the new plan to switch to using the plan's GUID
                        $data = [
                            'filter' => ['login' => $service_fields->plesk_username],
                            'plan' => ['guid' => $plan_guid]
                        ];

                        $this->log(
                            $module_row->meta->host_name . '|reseller:switch-subscription',
                            serialize($data),
                            'input',
                            true
                        );
                        $response = $this->parseResponse($reseller->changePlan($data), $module_row);
                    } catch (Exception $e) {
                        // API request failed
                        $this->Input->setErrors(
                            ['api' => ['internal' => Language::_('Plesk.!error.api.internal', true)]]
                        );
                    }
                }

                if ($this->Input->errors()) {
                    return;
                }

                // Also switch the subscription plan if it has changed
                if ($package_from->meta->plan != $package_to->meta->plan) {
                    // Since the reseller plan was update, we also now need to fetch the subscription plans
                    if ($update_plan['reseller']) {
                        // Fetch subscription plans
                        $plans = $this->getPleskPlans($module_row, false, false);

                        // Determine the plan's GUID based on the plan ID we currently have
                        $plans = $this->ArrayHelper->numericToKey($plans, 'id', 'guid');
                        $plan_guid = '';
                        if (isset($plans[$update_plan['to_plan']])) {
                            $plan_guid = $plans[$update_plan['to_plan']];
                        }
                    }

                    try {
                        // Change customer account subscription plan
                        $subscription = $api->loadCommand('plesk_subscriptions', [$api_version]);

                        // Set the new plan to switch to using the plan's GUID
                        $data = ['filter' => [], 'plan' => ['guid' => $plan_guid]];

                        // Identify the subscription to update by name (domain),
                        // subscription ID, or by the customer login user
                        if (!empty($service_fields->plesk_domain)) {
                            $data['filter']['name'] = $service_fields->plesk_domain;
                        } elseif (!empty($service_fields->plesk_webspace_id)) {
                            $data['filter']['id'] = $service_fields->plesk_webspace_id;
                        } elseif (!empty($service_fields->plesk_username)) {
                            $data['filter']['owner_login'] = $service_fields->plesk_username;
                        }

                        $this->log(
                            $module_row->meta->host_name . '|webspace:switch-subscription',
                            serialize($data),
                            'input',
                            true
                        );
                        $response = $this->parseResponse($subscription->changePlan($data), $module_row);
                    } catch (Exception $e) {
                        // API request failed
                        $this->Input->setErrors(
                            ['api' => ['internal' => Language::_('Plesk.!error.api.internal', true)]]
                        );
                    }
                }
            }
        } else {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Plesk.!error.module_row.missing', true)]]
            );
        }

        // Nothing to do
        return null;
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
        // Set rules to validate input data
        $this->Input->setRules($this->getPackageRules($vars));

        // Build meta data to return
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
        // Set rules to validate input data
        $this->Input->setRules($this->getPackageRules($vars));

        // Build meta data to return
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
     * Returns the rendered view of the manage module page
     *
     * @param mixed $module A stdClass object representing the module and its rows
     * @param array $vars An array of post data submitted to or on the manage module
     *  page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the manager module page
     */
    public function manageModule($module, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'plesk' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set('module', $module);

        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the add module row page
     *
     * @param array $vars An array of post data submitted to or on the add module
     *  row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the add module row page
     */
    public function manageAddRow(array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('add_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'plesk' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        // Set default port
        if (empty($vars)) {
            $vars['port'] = '8443';
        }

        $this->view->set('vars', (object)$vars);
        $this->view->set('panel_versions', $this->getSupportedPanelVersions(true));
        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the edit module row page
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of post data submitted to or on the edit module
     *  row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the edit module row page
     */
    public function manageEditRow($module_row, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('edit_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'plesk' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        if (empty($vars)) {
            $vars = $module_row->meta;
        }

        $this->view->set('vars', (object)$vars);
        $this->view->set('panel_versions', $this->getSupportedPanelVersions(true));
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
        $meta_fields = ['server_name', 'host_name', 'ip_address', 'port',
            'username', 'password', 'panel_version', 'reseller',
            'account_limit', 'account_count', 'name_servers'
        ];
        $encrypted_fields = ['username', 'password'];

        // Set checkbox value for whether this user is a reseller
        $vars['reseller'] = (isset($vars['reseller']) && $vars['reseller'] == 'true' ? 'true' : 'false');

        $this->Input->setRules($this->getRowRules($vars));

        // Validate module row
        if ($this->Input->validates($vars)) {
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
        // Nothing to do
        return null;
    }

    /**
     * Returns an array of available service delegation order methods. The module
     * will determine how each method is defined. For example, the method "first"
     * may be implemented such that it returns the module row with the least number
     * of services assigned to it.
     *
     * @return array An array of order methods in key/value pairs where the key is
     *  the type to be stored for the group and value is the name for that option
     * @see Module::selectModuleRow()
     */
    public function getGroupOrderOptions()
    {
        return [
            'roundrobin' => Language::_('Plesk.order_options.roundrobin', true),
            'first' => Language::_('Plesk.order_options.first', true)
        ];
    }

    /**
     * Returns all fields used when adding/editing a package, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getPackageFields($vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();
        $fields->setHtml("
			<script type=\"text/javascript\">
				$(document).ready(function() {
					$('input[name=\"meta[type]\"]').change(function() {
						fetchModuleOptions();
					});
				});
			</script>
		");

        // Fetch all packages available for the given server or server group
        $module_row = null;
        if (isset($vars->module_group) && $vars->module_group == '') {
            if (isset($vars->module_row) && $vars->module_row > 0) {
                $module_row = $this->getModuleRow($vars->module_row);
            } else {
                $rows = $this->getModuleRows();
                if (isset($rows[0])) {
                    $module_row = $rows[0];
                }
                unset($rows);
            }
        } else {
            // Fetch the 1st server from the list of servers in the selected group
            $rows = $this->getModuleRows($vars->module_group);

            if (isset($rows[0])) {
                $module_row = $rows[0];
            }
            unset($rows);
        }

        // Fetch plans
        $plans = ['' => Language::_('Plesk.please_select', true)];
        if ($module_row) {
            $plans += $this->getPleskPlans($module_row);
        }

        // Set the type of account (standard or reseller)
        $type = $fields->label(Language::_('Plesk.package_fields.type', true), 'plesk_type');
        $type_standard = $fields->label(Language::_('Plesk.package_fields.type_standard', true), 'plesk_type_standard');
        $type_reseller = $fields->label(Language::_('Plesk.package_fields.type_reseller', true), 'plesk_type_reseller');
        $type->attach(
            $fields->fieldRadio(
                'meta[type]',
                'standard',
                (isset($vars->meta['type']) ? $vars->meta['type'] : 'standard') == 'standard',
                ['id' => 'plesk_type_standard'],
                $type_standard
            )
        );
        $type->attach(
            $fields->fieldRadio(
                'meta[type]',
                'reseller',
                (isset($vars->meta['type']) ? $vars->meta['type'] : null) == 'reseller',
                ['id' => 'plesk_type_reseller'],
                $type_reseller
            )
        );
        $fields->setField($type);

        // Set the Plesk plans as selectable options
        $package = $fields->label(Language::_('Plesk.package_fields.plan', true), 'plesk_plan');
        $package->attach(
            $fields->fieldSelect(
                'meta[plan]',
                $plans,
                (isset($vars->meta['plan']) ? $vars->meta['plan'] : null),
                ['id' => 'plesk_plan']
            )
        );
        $fields->setField($package);

        // Set the reseller account plan
        if (isset($vars->meta['type']) && $vars->meta['type'] == 'reseller') {
            // Fetch the reseller plans
            $reseller_plans = ['' => Language::_('Plesk.please_select', true)];
            $reseller_plans += $this->getPleskPlans($module_row, true);

            // Set the Plesk reseller account plans as selectable options
            $package = $fields->label(Language::_('Plesk.package_fields.reseller_plan', true), 'plesk_reseller_plan');
            $package->attach(
                $fields->fieldSelect(
                    'meta[reseller_plan]',
                    $reseller_plans,
                    (isset($vars->meta['reseller_plan']) ? $vars->meta['reseller_plan'] : null),
                    ['id' => 'plesk_reseller_plan']
                )
            );
            $fields->setField($package);
        }

        return $fields;
    }

    /**
     * Returns all fields to display to an admin attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getAdminAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        // Get the fields shared between adding and editing a service
        $fields = $this->getAdminServiceFields($vars);

        $webspace_id = $fields->label(Language::_('Plesk.service_field.webspace_id', true), 'plesk_webspace_id');
        // Create confirm password field and attach to password label
        $webspace_id->attach(
            $fields->fieldText(
                'plesk_webspace_id',
                (isset($vars->plesk_webspace_id) ? $vars->plesk_webspace_id : null),
                ['id' => 'plesk_webspace_id']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Plesk.service_field.tooltip.webspace_id', true));
        $webspace_id->attach($tooltip);
        // Set the label as a field
        $fields->setField($webspace_id);

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

        // Create domain label
        $domain = $fields->label(Language::_('Plesk.service_field.domain', true), 'plesk_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText(
                'plesk_domain',
                (isset($vars->plesk_domain) ? $vars->plesk_domain : ($vars->domain ?? null)),
                ['id' => 'plesk_domain']
            )
        );
        // Set the label as a field
        $fields->setField($domain);

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

        // Get the fields shared between adding and editing a service
        $fields = $this->getAdminServiceFields($vars);

        $webspace_id = $fields->label(Language::_('Plesk.service_field.webspace_id', true), 'plesk_webspace_id');
        // Create confirm password field and attach to password label
        $webspace_id->attach(
            $fields->fieldText(
                'plesk_webspace_id',
                (isset($vars->plesk_webspace_id) ? $vars->plesk_webspace_id : null),
                ['id' => 'plesk_webspace_id']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Plesk.service_field.tooltip.webspace_id_edit', true));
        $webspace_id->attach($tooltip);
        // Set the label as a field
        $fields->setField($webspace_id);

        return $fields;
    }


    /**
     * Returns all fields to display to an admin attempting to add or edit a service with the module
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    private function getAdminServiceFields($vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        // Load product configuration required by this module
        Configure::load('plesk', dirname(__FILE__) . DS . 'config' . DS);

        $password_requirements = Configure::get('Plesk.password_requirements');
        $password_options = [];
        foreach ($password_requirements as $password_requirement) {
            foreach ($password_requirement as &$characters) {
                $parts = explode('-', $characters);
                if (count($parts) > 1 && strlen($characters) > 1) {
                    $characters = [$parts[0], $parts[1]];
                }
            }
            $password_options[] = (object)['chars' => $password_requirement, 'min' => 2];
        }
        $password_options = json_encode((object)['include' => $password_options]);

        $fields = new ModuleFields();

        // Create domain label
        $domain = $fields->label(Language::_('Plesk.service_field.domain', true), 'plesk_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText('plesk_domain', (isset($vars->plesk_domain) ? $vars->plesk_domain : null), ['id'=>'plesk_domain'])
        );
        // Set the label as a field
        $fields->setField($domain);

        // Create username label
        $username = $fields->label(Language::_('Plesk.service_field.username', true), 'plesk_username');
        // Create username field and attach to username label
        $username->attach(
            $fields->fieldText('plesk_username', (isset($vars->plesk_username) ? $vars->plesk_username : null), ['id'=>'plesk_username'])
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Plesk.service_field.tooltip.username', true));
        $username->attach($tooltip);
        // Set the label as a field
        $fields->setField($username);

        // Create password label
        $password = $fields->label(Language::_('Plesk.service_field.password', true), 'plesk_password');
        $fields->setHtml('<a class="generate-password"
                href="#" data-options="' . $this->Html->safe($password_options) . '" data-length="16"
                data-base-url="' . $this->base_uri . '" data-for-class="plesk_password">
            <i class="fas fa-sync-alt"></i> ' . Language::_('Plesk.service_field.text_generate_password', true) .
        '</a>
        <script type="text/javascript">
            $(document).ready(function () {
                $("#plesk_password").parent().append($(".generate-password"));
            });
        </script>
        ');
        // Create password field and attach to password label
        $password->attach(
            $fields->fieldPassword(
                'plesk_password',
                [
                    'id' => 'plesk_password',
                    'class' => 'plesk_password',
                    'value' => (isset($vars->plesk_password) ? $vars->plesk_password : null)
                ]
            )
        );
        // Set the label as a field
        $fields->setField($password);

        // Confirm password label
        $confirm_password = $fields->label(
            Language::_('Plesk.service_field.confirm_password', true),
            'plesk_confirm_password'
        );
        // Create confirm password field and attach to password label
        $confirm_password->attach(
            $fields->fieldPassword(
                'plesk_confirm_password',
                [
                    'id' => 'plesk_confirm_password',
                    'class' => 'plesk_password',
                    'value' => (isset($vars->plesk_password) ? $vars->plesk_password : null)
                ]
            )
        );
        // Set the label as a field
        $fields->setField($confirm_password);

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
        if (!$row) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Plesk.!error.module_row.missing', true)]]
            );
            return;
        }

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('admin_service_info', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'plesk' . DS);

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
        if (!$row) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Plesk.!error.module_row.missing', true)]]
            );
            return;
        }

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('client_service_info', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'plesk' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $this->serviceFieldsToObject($service->fields));

        return $this->view->fetch();
    }

    /**
     * Statistics tab
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
        $this->view = new View('tab_stats', 'default');
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $stats = $this->getStats($package, $service);

        $this->view->set('stats', $stats);
        #$this->view->set("user_type", $package->meta->type);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'plesk' . DS);
        return $this->view->fetch();
    }

    /**
     * Client Statistics tab
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
        $this->view = new View('tab_client_stats', 'default');
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $stats = $this->getStats($package, $service);

        $this->view->set('stats', $stats);
        #$this->view->set("user_type", $package->meta->type);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'plesk' . DS);
        return $this->view->fetch();
    }

    /**
     * Fetches all status for a given subscription service
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @return stdClass A stdClass object representing all of the stats for the account
     */
    private function getStats($package, $service)
    {
        $module_row = $this->getModuleRow();
        if (!$module_row) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Plesk.!error.module_row.missing', true)]]
            );
            return;
        }

        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->username,
            $module_row->meta->password,
            $module_row->meta->port
        );

        $service_fields = $this->serviceFieldsToObject($service->fields);

        $stats = new stdClass();
        $stats->account_info = [
            'domain' => $service_fields->plesk_domain,
            'ip_address' => $module_row->meta->host_name
        ];
        $stats->disk_usage = [
            'used' => null,
            'used_formatted' => null,
            'limit' => null,
            'limit_formatted' => null,
            'unused' => null,
            'unused_formatted' => Language::_('Plesk.stats.unlimited', true)
        ];
        $stats->bandwidth_usage = [
            'used' => null,
            'used_formatted' => null,
            'limit' => null,
            'limit_formatted' => null,
            'unused' => null,
            'unused_formatted' => Language::_('Plesk.stats.unlimited', true)
        ];

        $response = false;
        try {
            $subscription = $api->loadCommand(
                'plesk_subscriptions',
                [$this->getApiVersion($module_row->meta->panel_version)]
            );

            // Fetch these stats
            $options = ['gen_info', 'hosting', 'limits', 'stat', 'prefs', 'disk_usage',
                'performance', 'subscriptions', 'permissions', 'plan-items', 'php-settings'];

            $data = [
                'id' => $service_fields->plesk_webspace_id,
                'settings' => []
            ];

            // Set the stats we want to fetch
            foreach ($options as $option) {
                $data['settings'][$option] = true;
            }

            $this->log($module_row->meta->host_name . '|webspace:get', serialize($data), 'input', true);
            $response = $this->parseResponse($subscription->get($data), $module_row);
        } catch (Exception $e) {
            // API request failed
            $this->Input->setErrors(['api' => ['internal' => Language::_('Plesk.!error.api.internal', true)]]);
        }

        // Format the results for the stats we will display
        if ($response && isset($response->result->data)) {
            $data = $response->result->data;

            // Set account info
            $stats->account_info['domain'] = $data->gen_info->name;
            $stats->account_info['ip_address'] = $data->gen_info->dns_ip_address;

            // Fetch account limits
            $totals = [];
            foreach ($data->limits->limit as $item) {
                $totals[$item->name] = $item->value;
            }

            // Set bandwidth usage
            $stats->bandwidth_usage['used'] = $data->stat->traffic;
            $stats->bandwidth_usage['limit'] = (isset($totals['max_traffic']) ? $totals['max_traffic'] : null);

            // Set disk usage
            $stats->disk_usage['limit'] = (isset($totals['disk_space']) ? $totals['disk_space'] : null);
            $total_disk_usage = 0;

            $disk_usage_options = ['httpdocs', 'httpsdocs', 'subdomains', 'web_users',
                'anonftp', 'logs', 'dbases', 'mailboxes',
                'webapps', 'maillists', 'domaindumps', 'configs', 'chroot'
            ];
            foreach ($disk_usage_options as $option) {
                if (property_exists($data->disk_usage, $option)) {
                    $total_disk_usage += $data->disk_usage->{$option};
                }
            }
            $stats->disk_usage['used'] = $total_disk_usage;

            // Format the values
            if ($stats->disk_usage['limit'] == '-1') {
                $stats->disk_usage['limit_formatted'] = Language::_('Plesk.stats.unlimited', true);
            } else {
                $stats->disk_usage['limit_formatted'] = $this->convertBytesToString($stats->disk_usage['limit']);

                // Set unused
                $stats->disk_usage['unused'] = abs($stats->disk_usage['limit']-$stats->disk_usage['used']);
                $stats->disk_usage['unused_formatted'] = $this->convertBytesToString($stats->disk_usage['unused']);
            }

            if ($stats->bandwidth_usage['limit'] == '-1') {
                $stats->bandwidth_usage['limit_formatted'] = Language::_('Plesk.stats.unlimited', true);
            } else {
                $stats->bandwidth_usage['limit_formatted'] = $this->convertBytesToString(
                    $stats->bandwidth_usage['limit']
                );

                // Set unused
                $stats->bandwidth_usage['unused'] = abs(
                    $stats->bandwidth_usage['limit'] - $stats->bandwidth_usage['used']
                );
                $stats->bandwidth_usage['unused_formatted'] = $this->convertBytesToString(
                    $stats->bandwidth_usage['unused']
                );
            }

            $stats->disk_usage['used_formatted'] = $this->convertBytesToString($stats->disk_usage['used']);
            $stats->bandwidth_usage['used_formatted'] = $this->convertBytesToString($stats->bandwidth_usage['used']);
        }

        return $stats;
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

        return Language::_('Plesk.!bytes.value', true, $value, $unit);
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
        $fields = [
            'domain' => isset($vars['plesk_domain']) ? strtolower($vars['plesk_domain']) : null,
            'username' => isset($vars['plesk_username']) ? $vars['plesk_username']: null,
            'password' => isset($vars['plesk_password']) ? $vars['plesk_password'] : null,
            'webspace_id' => !empty($vars['plesk_webspace_id']) ? $vars['plesk_webspace_id'] : null
        ];

        return $fields;
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
     * Fetches a listing of all service plans configured in Plesk for the given server
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @param bool $reseller True to fetch reseller plans, false for user/hosting plans (optional, default false)
     * @param bool $format True to format the response as a key/value pair
     *  (id => name), false to fetch all data (optional, default true)
     * @return array An array of packages in key/value pairs
     */
    private function getPleskPlans($module_row, $reseller = false, $format = true)
    {
        if (!isset($this->DataStructure)) {
            Loader::loadHelpers($this, ['DataStructure']);
        }
        if (!isset($this->ArrayHelper)) {
            $this->ArrayHelper = $this->DataStructure->create('Array');
        }

        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->username,
            $module_row->meta->password,
            $module_row->meta->port
        );

        // Fetch the plans
        try {
            $api_version = $this->getApiVersion($module_row->meta->panel_version);

            // Fetch reseller plans
            if ($reseller) {
                $service_plans = $api->loadCommand('plesk_reseller_plans', [$api_version]);

                // Fetch all reseller plans
                $data = ['filter' => ['all' => true]];

                $this->log($module_row->meta->host_name . '|reseller-plan:get', serialize($data), 'input', true);
                $response = $this->parseResponse($service_plans->get($data), $module_row);
            } else {
                // Fetch user/hosting plans
                $service_plans = $api->loadCommand('plesk_service_plans', [$api_version]);

                // Fetch all reseller plans
                $data = ['filter' => []];

                $this->log($module_row->meta->host_name . '|service-plan:get', serialize($data), 'input', true);
                $response = $this->parseResponse($service_plans->get($data), $module_row);
            }

            // Response is only an array if there is more than 1 result returned
            if (is_array($response->result)) {
                $result = $response->result;
                if ($format) {
                    $result = $this->ArrayHelper->numericToKey($response->result, 'id', 'name');
                }
            } else {
                // Only 1 result
                $result = [$response->result];
                if ($format) {
                    $result = [];
                    if (property_exists($response->result, 'id') && property_exists($response->result, 'name')) {
                        $result = [$response->result->id => $response->result->name];
                    }
                }
            }

            return $result;
        } catch (Exception $e) {
            // API request failed
            $this->Input->setErrors(['api' => ['internal' => Language::_('Plesk.!error.api.internal', true)]]);
        }

        return [];
    }

    /**
     * Upgrades a customer account to a reseller account. Sets Input errors on failure
     *
     * @param stdClass $module_row An stdClass object representing a single server
     * @param stdClass $service An stdClass object representing the service to upgrade
     */
    private function upgradeCustomerToReseller($module_row, $service)
    {
        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->username,
            $module_row->meta->password,
            $module_row->meta->port
        );

        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Upgrade the account
        try {
            $customer = $api->loadCommand(
                'plesk_customer_accounts',
                [$this->getApiVersion($module_row->meta->panel_version)]
            );

            // Upgrade this customer account
            $data = ['filter' => ['login' => $service_fields->plesk_username]];

            $this->log(
                $module_row->meta->host_name . '|customer:convert-to-reseller',
                serialize($data),
                'input',
                true
            );
            $response = $this->parseResponse($customer->upgrade($data), $module_row);
        } catch (Exception $e) {
            // API request failed
            $this->Input->setErrors(['api' => ['internal' => Language::_('Plesk.!error.api.internal', true)]]);
        }
    }

    /**
     * Creates a reseller account. Sets Input errors on failure
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @param stdClass $package A stdClass object representing the selected package
     * @param int $client_id The ID of the client this customer account is being created on behalf of
     * @param array $params A list of data to pass into the reseller account
     *  - username The account username
     *  - password The account password
     * @return stdClass An stdClass object representing the response
     */
    private function createResellerAccount($module_row, $package, $client_id, $params)
    {
        // Fetch the client fields
        $client_params = $this->getClientAccountFields($client_id);

        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->username,
            $module_row->meta->password,
            $module_row->meta->port
        );

        try {
            $reseller = $api->loadCommand(
                'plesk_reseller_accounts',
                [$this->getApiVersion($module_row->meta->panel_version)]
            );

            // Create the customer account
            $data = array_merge(
                $client_params,
                [
                    'login' => $params['username'],
                    'password' => $params['password'],
                    'plan' => ['id' => $package->meta->reseller_plan]
                ]
            );
            $masked_data = $data;
            $masked_data['password'] = '***';
            $this->log($module_row->meta->host_name . '|reseller:add', serialize($masked_data), 'input', true);
            $response = $this->parseResponse($reseller->add($data), $module_row);
        } catch (Exception $e) {
            // API request failed
            $this->Input->setErrors(['api' => ['internal' => Language::_('Plesk.!error.api.internal', true)]]);
        }

        return (isset($response) ? $response : new stdClass());
    }

    /**
     * Updates a reseller account. Sets Input errors on failure
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @param stdClass $service_fields An stdClass object representing the service fields
     * @param array $params A list of data to pass into the reseller account
     *  - username The account username
     *  - password The account password
     * @return stdClass An stdClass object representing the response
     */
    private function updateResellerAccount($module_row, $service_fields, $params)
    {
        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->username,
            $module_row->meta->password,
            $module_row->meta->port
        );

        // Update the customer
        try {
            $reseller = $api->loadCommand(
                'plesk_reseller_accounts',
                [$this->getApiVersion($module_row->meta->panel_version)]
            );

            // Set the information to update
            $data = [
                // Update this user
                'filter' => ['login' => $service_fields->plesk_username],
                // with this information
                'general' => [
                    'login' => $params['username'],
                    'password' => $params['password']
                ]
            ];

            // Mask sensitive data
            $masked_data = $data;
            $masked_data['general']['password'] = '***';

            $this->log($module_row->meta->host_name . '|reseller:set', serialize($masked_data), 'input', true);
            $response = $this->parseResponse($reseller->set($data), $module_row);
        } catch (Exception $e) {
            // API request failed
            $this->Input->setErrors(['api' => ['internal' => Language::_('Plesk.!error.api.internal', true)]]);
        }

        return (isset($response) ? $response : new stdClass());
    }

    /**
     * Deletes a reseller account. Sets Input errors on failure
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @param stdClass $service_fields An stdClass object representing the service fields
     * @return stdClass An stdClass object representing the response
     */
    private function deleteResellerAccount($module_row, $service_fields)
    {
        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->username,
            $module_row->meta->password,
            $module_row->meta->port
        );

        // Delete the reseller account
        try {
            $reseller = $api->loadCommand(
                'plesk_reseller_accounts',
                [$this->getApiVersion($module_row->meta->panel_version)]
            );

            // Delete the account
            $data = ['filter' => ['login' => $service_fields->plesk_username]];

            // Some filter options must be set to avoid Plesk deleting everything
            if (empty($data['filter']['login'])) {
                $this->Input->setErrors(
                    [
                        'api' => [
                            'filter-missing' => Language::_('Plesk.!error.api.reseller_delete_filter_missing', true)
                        ]
                    ]
                );
                return;
            }

            $this->log($module_row->meta->host_name . '|reseller:del', serialize($data), 'input', true);
            $response = $this->parseResponse($reseller->delete($data), $module_row);
        } catch (Exception $e) {
            // API request failed
            $this->Input->setErrors(['api' => ['internal' => Language::_('Plesk.!error.api.internal', true)]]);
        }

        return (isset($response) ? $response : new stdClass());
    }

    /**
     * Creates a customer account. Sets Input errors on failure
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @param stdClass $package A stdClass object representing the selected package
     * @param int $client_id The ID of the client this customer account is being created on behalf of
     * @param array $params A list of data to pass into the customer account
     *  - username The account username
     *  - password The account password
     * @return stdClass An stdClass object representing the response
     */
    private function createCustomerAccount($module_row, $package, $client_id, $params)
    {
        // Fetch the client fields
        $client_params = $this->getClientAccountFields($client_id);

        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->username,
            $module_row->meta->password,
            $module_row->meta->port
        );

        try {
            $customer_accounts = $api->loadCommand(
                'plesk_customer_accounts',
                [$this->getApiVersion($module_row->meta->panel_version)]
            );

            // Create the customer account
            $data = array_merge($client_params, ['login' => $params['username'], 'password' => $params['password']]);
            $masked_data = $data;
            $masked_data['password'] = '***';
            $this->log($module_row->meta->host_name . '|customer:add', serialize($masked_data), 'input', true);
            $response = $this->parseResponse($customer_accounts->add($data), $module_row);
        } catch (Exception $e) {
            // API request failed
            $this->Input->setErrors(['api' => ['internal' => Language::_('Plesk.!error.api.internal', true)]]);
        }

        return (isset($response) ? $response : new stdClass());
    }

    /**
     * Updates a customer account. Sets Input errors on failure
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @param stdClass $service_fields An stdClass object representing the service fields
     * @param array $params A list of data to pass into the customer account
     *  - username The account username
     *  - password The account password
     * @return stdClass An stdClass object representing the response
     */
    private function updateCustomerAccount($module_row, $service_fields, $params)
    {
        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->username,
            $module_row->meta->password,
            $module_row->meta->port
        );

        // Update the customer
        try {
            $customer = $api->loadCommand(
                'plesk_customer_accounts',
                [$this->getApiVersion($module_row->meta->panel_version)]
            );

            // Set the information to update
            $data = [
                // Update this user
                'filter' => ['login' => $service_fields->plesk_username],
                // with this information
                'general' => [
                    'login' => $params['username'],
                    'password' => $params['password']
                ]
            ];

            // Mask sensitive data
            $masked_data = $data;
            $masked_data['general']['password'] = '***';

            $this->log($module_row->meta->host_name . '|customer:set', serialize($masked_data), 'input', true);
            $response = $this->parseResponse($customer->set($data), $module_row);
        } catch (Exception $e) {
            // API request failed
            $this->Input->setErrors(['api' => ['internal' => Language::_('Plesk.!error.api.internal', true)]]);
        }

        return (isset($response) ? $response : new stdClass());
    }

    /**
     * Deletes a customer account. Sets Input errors on failure
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @param stdClass $service_fields An stdClass object representing the service fields
     * @return stdClass An stdClass object representing the response
     */
    private function deleteCustomerAccount($module_row, $service_fields)
    {
        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->username,
            $module_row->meta->password,
            $module_row->meta->port
        );

        // Delete the customer account
        try {
            $customer_accounts = $api->loadCommand(
                'plesk_customer_accounts',
                [$this->getApiVersion($module_row->meta->panel_version)]
            );

            // Delete the account
            $data = ['filter' => ['login' => $service_fields->plesk_username]];

            // Some filter options must be set to avoid Plesk deleting everything
            if (empty($data['filter']['login'])) {
                $this->Input->setErrors(
                    [
                        'api' => [
                            'filter-missing' => Language::_('Plesk.!error.api.reseller_delete_filter_missing', true)
                        ]
                    ]
                );
                return;
            }

            $this->log($module_row->meta->host_name . '|customer:del', serialize($data), 'input', true);
            $response = $this->parseResponse($customer_accounts->delete($data), $module_row);
        } catch (Exception $e) {
            // API request failed
            $this->Input->setErrors(['api' => ['internal' => Language::_('Plesk.!error.api.internal', true)]]);
        }

        return (isset($response) ? $response : new stdClass());
    }

    /**
     * Retrieves a list of fields for creating a customer/reseller account
     *
     * @param int $client_id The ID of the client whose fields to fetch
     * @return array An array of client fields
     * @see Plesk::createCustomerAccount(), Plesk::createResellerAccount()
     */
    private function getClientAccountFields($client_id)
    {
        // Fetch the client to set additional client fields
        Loader::loadModels($this, ['Clients']);
        $client_params = [];
        if (($client = $this->Clients->get($client_id, false))) {
            $country = (!empty($client->country) ? $client->country : null);
            $client_params = [
                'name' => $client->first_name . ' ' . $client->last_name,
                'email' => $client->email,
                'company' => (!empty($client->company) ? $client->company : null),
                'status' => '0',
                'address' => (empty($client->address1)
                    ? null
                    : ($client->address1 . (!empty($client->address2) ? ' ' . $client->address2 : ''))
                ),
                'city' => (!empty($client->city) ? $client->city : null),
                'state' => (!empty($client->state) && $country == 'US' ? $client->state : null),
                'country' => $country,
                'zipcode' => (!empty($client->zip) && $country == 'US' ? $client->zip : null)
            ];
        }

        return $client_params;
    }

    /**
     * Parses the response from SolusVM into an stdClass object
     *
     * @param SolusvmResponse $response The response from the API
     * @param string $xml_container_path The path to the XML container where the results reside
     * @param stdClass $module_row A stdClass object representing a
     *  single server (optional, required when Module::getModuleRow() is unavailable)
     * @param bool $ignore_error Ignores any response error and returns the response anyway;
     *  useful when a response is expected to fail (e.g. check client exists) (optional, default false)
     * @return stdClass A stdClass object representing the response, void if the response was an error
     */
    private function parseResponse(PleskResponse $response, $module_row = null, $ignore_error = false)
    {
        Loader::loadHelpers($this, ['Html']);

        // Set the module row
        if (!$module_row) {
            $module_row = $this->getModuleRow();
        }

        $success = false;
        switch ($response->status()) {
            case 'ok':
                $success = true;
                break;
            case 'error':
                $success = false;

                // Ignore generating the error
                if ($ignore_error) {
                    break;
                }

                // Set errors
                $errors = $response->errors();
                $error = '';

                if (isset($errors->errcode) && isset($errors->errtext)) {
                    $error = $errors->errcode . ' ' . $errors->errtext;
                }

                $this->Input->setErrors(['api' => ['response' => $this->Html->safe($error)]]);
                break;
            default:
                // Invalid response
                $success = false;

                // Ignore generating the error
                if ($ignore_error) {
                    break;
                }

                $this->Input->setErrors(['api' => ['internal' => Language::_('Plesk.!error.api.internal', true)]]);
                break;
        }

        // Replace sensitive fields
        $masked_params = [];
        $output = $response->response();
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
        $this->log($module_row->meta->host_name, $raw_output, 'output', $success);

        if (!$success && !$ignore_error) {
            return;
        }

        return $output;
    }

    /**
     * Initializes the CpanelApi and returns an instance of that object with the given $host, $user, and $pass set
     *
     * @param string $host The host to the Plesk server
     * @param string $user The user to connect as
     * @param string $pass The password to authenticate with
     * @param string $port The port on the host to connect on
     * @return PleskApi The PleskApi instance
     */
    private function getApi($host, $user, $pass, $port)
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'plesk_api.php');

        return new PleskApi($user, $pass, $host, $port);
    }

    /**
     * Generates a username from the given host name.
     *
     * @param string $host_name The host name to use to generate the username
     * @return string The username generated from the given hostname
     */
    private function generateUsername($host_name)
    {
        // Remove everything except letters and numbers from the domain
        // ensure no number appears in the beginning
        $username = ltrim(preg_replace('/[^a-z0-9]/i', '', $host_name), '0123456789');

        $length = strlen($username);
        $pool = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $pool_size = strlen($pool);

        if ($length < 5) {
            for ($i = $length; $i < 9; $i++) {
                $username .= substr($pool, mt_rand(0, $pool_size - 1), 1);
            }
            $length = strlen($username);
        }

        $username = strtolower(substr($username, 0, min($length, 8)));

        // Check for an existing user account
        $row = $this->getModuleRow();

        if ($row) {
            $api = $this->getApi(
                $row->meta->host_name,
                $row->meta->username,
                $row->meta->password,
                $row->meta->port
            );
        }

        $account_matching_characters = 2;
        try {
            $customer_accounts = $api->loadCommand(
                'plesk_customer_accounts',
                [$this->getApiVersion($row->meta->panel_version)]
            );
            $user = $this->parseResponse($customer_accounts->get(['login' => $username]), $row);

            // Username exists, create another instead
            if ($user) {
                for ($i = 0; $i < (int) str_repeat(9, $account_matching_characters); $i++) {
                    $new_username = substr($username, 0, -strlen($i)) . $i;
                    $customer_accounts = $api->loadCommand(
                        'plesk_customer_accounts',
                        [$this->getApiVersion($row->meta->panel_version)]
                    );
                    $user = $this->parseResponse($customer_accounts->get(['login' => $new_username]), $row);
                    if (empty($user)) {
                        $this->Input->setErrors([]);
                        $username = $new_username;
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            return '';
        }

        return $username;
    }

    /**
     * Generates a password
     *
     * @return string The generated password
     */
    private function generatePassword()
    {
        // Load product configuration required by this module
        Configure::load('plesk', dirname(__FILE__) . DS . 'config' . DS);
        Loader::loadHelpers($this, ['DataStructure']);
        $this->DataStructureString = $this->DataStructure->create('String');

        // Fetch and format password requirements
        $password_length = Configure::get('Plesk.password_length');
        $password_requirements = Configure::get('Plesk.password_requirements');
        $minimum_characters_per_pool = Configure::get('Plesk.password_minimum_characters_per_pool');

        $character_pools = [];
        foreach ($password_requirements as $password_requirement) {
            $characters = $password_requirement;
            if (count($characters) == 1) {
                foreach ($password_requirement as $character_pool) {
                    $range_ends = explode('-', $character_pool);
                    if (count($range_ends) > 1) {
                        $characters = range($range_ends[0], $range_ends[1]);
                    }
                }
            }
            $character_pools[] = implode('', $characters);
        }

        // Randomly choose the characters for the password
        $password = '';
        foreach ($character_pools as $pool) {
            // Randomly select characters from the current pool
            $password .= $this->DataStructureString->random($minimum_characters_per_pool, $pool);
        }

        // Select remaining characters from all the pools combined
        $password .= $this->DataStructureString->random(
            $password_length - strlen($password),
            implode('', $character_pools)
        );

        // Shuffle up all the characters so they don't just appear in the order of the pools
        return str_shuffle($password);
    }

    /**
     * Builds and returns the rules required to add/edit a module row (e.g. server)
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getRowRules(&$vars)
    {
        return [
            'server_name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Plesk.!error.server_name.empty', true)
                ]
            ],
            'host_name' => [
                'valid' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Plesk.!error.host_name.valid', true)
                ]
            ],
            'ip_address' => [
                'valid' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Plesk.!error.ip_address.valid', true)
                ]
            ],
            'port' => [
                'format' => [
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => Language::_('Plesk.!error.port.format', true)
                ]
            ],
            'username' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Plesk.!error.username.empty', true)
                ]
            ],
            'password' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Plesk.!error.password.empty', true)
                ]
            ],
            'panel_version' => [
                'valid' => [
                    'rule' => [[$this, 'validatePanelVersions']],
                    'message' => Language::_('Plesk.!error.panel_version.valid', true)
                ]
            ],
            'reseller' => [
                'valid' => [
                    'rule' => ['in_array', ['true', 'false']],
                    'message' => Language::_('Plesk.!error.reseller.valid', true)
                ]
            ],
            'account_limit' => [
                'valid' => [
                    'rule' => ['matches', '/^([0-9]+)?$/'],
                    'message' => Language::_('Plesk.!error.account_limit_valid', true)
                ]
            ],
            'name_servers' => [
                'count' => [
                    'rule' => function ($name_servers) {
                        if (is_array($name_servers) && count($name_servers) >= 2) {
                            return true;
                        }
                        return false;
                    },
                    'message' => Language::_('Plesk.!error.name_servers.count', true)
                ],
                'valid' => [
                    'rule' => function ($name_servers) {
                        if (is_array($name_servers)) {
                            foreach ($name_servers as $name_server) {
                                if (!$this->validateHostName($name_server)) {
                                    return false;
                                }
                            }
                        }
                        return true;
                    },
                    'message' => Language::_('Plesk.!error.name_servers.valid', true)
                ]
            ]
        ];
    }

    /**
     * Builds and returns rules required to be validated when adding/editing a package
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getPackageRules($vars)
    {
        $rules = [
            'meta[type]' => [
                'valid' => [
                    'rule' => ['matches', '/^(standard|reseller)$/'],
                    // type must be standard or reseller
                    'message' => Language::_('Plesk.!error.meta[type].valid', true),
                ]
            ],
            'meta[plan]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Plesk.!error.meta[plan].empty', true)
                ]
            ],
            'meta[reseller_plan]' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Plesk.!error.meta[reseller_plan].empty', true)
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Validates that the given hostname is valid
     *
     * @param string $host_name The host name to validate
     * @return bool True if the hostname is valid, false otherwise
     */
    public function validateHostName($host_name)
    {
        $validator = new Server();
        return $validator->isDomain($host_name) || $validator->isIp($host_name);
    }

    /**
     * Validates that the given panel version is valid
     *
     * @param string $version The version to validate
     * @return bool True if the version validates, false otherwise
     */
    public function validatePanelVersions($version)
    {
        return array_key_exists($version, $this->panel_versions) || empty($version);
    }

    /**
     * Retrieves the accounts on the server.
     *
     * @param stdClass $api The Plesk API
     * @return mixed The number of Plesk accounts on the server, or false on error
     */
    private function getAccountCount($api, $module_row)
    {
        $accounts = false;

        try {
            $customer_accounts = $api->loadCommand(
                'plesk_customer_accounts',
                [$this->getApiVersion($module_row->meta->panel_version)]
            );
            $response = $this->parseResponse($customer_accounts->get([]), $module_row);

            if ($response && !empty($response->result)) {
                $accounts = count($response->result);
            }
        } catch (Exception $e) {
            // Nothing to do
        }

        return $accounts;
    }

    /**
     * Updates the module row meta number of accounts.
     *
     * @param stdClass $module_row A stdClass object representing a single server
     */
    private function updateAccountCount($module_row)
    {
        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->username,
            $module_row->meta->password,
            $module_row->meta->port
        );

        // Get the number of accounts on the server
        if (($count = $this->getAccountCount($api, $module_row)) !== false) {
            // Update the module row account list
            Loader::loadModels($this, ['ModuleManager']);
            $vars = $this->ModuleManager->getRowMeta($module_row->id);

            if ($vars) {
                $vars->account_count = $count;
                $vars = (array) $vars;

                $this->ModuleManager->editRow($module_row->id, $vars);
            }
        }
    }
}
