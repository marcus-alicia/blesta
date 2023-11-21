<?php

use Blesta\Core\Util\Validate\Server;

/**
 * APNSCP Module.
 *
 * @package blesta
 * @subpackage blesta.components.modules.apnscp
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Apnscp extends Module
{
    /**
     * Initializes the module.
     */
    public function __construct()
    {
        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('apnscp', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load configuration required by this module
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
    }

    /**
     * Returns all tabs to display to an admin when managing a service whose
     * package uses this module.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title.
     *  Example: array('methodName' => "Title", 'methodName2' => "Title2")
     */
    public function getAdminTabs($package)
    {
        return [
            'tabStats' => Language::_('Apnscp.tab_stats', true)
        ];
    }

    /**
     * Returns all tabs to display to a client when managing a service whose
     * package uses this module.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title.
     *  Example: array('methodName' => "Title", 'methodName2' => "Title2")
     */
    public function getClientTabs($package)
    {
        return [
            'tabClientActions' => Language::_('Apnscp.tab_client_actions', true),
            'tabClientStats' => Language::_('Apnscp.tab_client_stats', true)
        ];
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
            'roundrobin' => Language::_('Apnscp.order_options.roundrobin', true),
            'first' => Language::_('Apnscp.order_options.first', true)
        ];
    }

    /**
     * Returns all fields used when adding/editing a package, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields to
     *  render as well as any additional HTML markup to include
     */
    public function getPackageFields($vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Fetch all packages available for the given server or server group
        $module_row = null;
        if (isset($vars->module_group) && $vars->module_group == '' || $vars->module_group == 'select') {
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

        // Get the APNSCP packages and options
        $packages = [];
        if ($module_row) {
            $packages = $this->getApnscpPackages($module_row);
        }

        // Set the APNSCP package as a selectable option
        $package = $fields->label(Language::_('Apnscp.package_fields.package', true), 'apnscp_package');
        $package->attach(
            $fields->fieldSelect(
                'meta[package]',
                $packages,
                (isset($vars->meta['package']) ? $vars->meta['package'] : null),
                ['id' => 'apnscp_package']
            )
        );
        $fields->setField($package);

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
     * Returns the rendered view of the manage module page.
     *
     * @param mixed $module A stdClass object representing the module and its rows
     * @param array $vars An array of post data submitted to or on the manager module
     *  page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the manager module page
     */
    public function manageModule($module, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'apnscp' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set('module', $module);

        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the add module row page.
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'apnscp' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        // Set unspecified checkboxes
        if (!empty($vars)) {
            if (empty($vars['use_ssl'])) {
                $vars['use_ssl'] = 'false';
            }
        }

        $this->view->set('vars', (object) $vars);

        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the edit module row page.
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'apnscp' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        if (empty($vars)) {
            $vars = $module_row->meta;
        } else {
            // Set unspecified checkboxes
            if (empty($vars['use_ssl'])) {
                $vars['use_ssl'] = 'false';
            }
        }

        $this->view->set('vars', (object) $vars);

        return $this->view->fetch();
    }

    /**
     * Adds the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being added. Returns a set of data, which may be
     * a subset of $vars, that is stored for this module row.
     *
     * @param array $vars An array of module info to add
     * @return array A numerically indexed array of meta fields for the module row containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function addModuleRow(array &$vars)
    {
        $meta_fields = ['server_name', 'host_name', 'port', 'api_key',
            'use_ssl', 'account_limit', 'name_servers', 'notes'];
        $encrypted_fields = ['user_name', 'password'];

        // Set unspecified checkboxes
        if (empty($vars['use_ssl'])) {
            $vars['use_ssl'] = 'false';
        }

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
     * preventing the row from being updated. Returns a set of data, which may be
     * a subset of $vars, that is stored for this module row.
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
        $meta_fields = ['server_name', 'host_name', 'port', 'api_key',
            'use_ssl', 'account_limit', 'account_count', 'name_servers', 'notes'];
        $encrypted_fields = ['user_name', 'password'];

        // Set unspecified checkboxes
        if (empty($vars['use_ssl'])) {
            $vars['use_ssl'] = 'false';
        }

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
     * Returns all fields to display to an admin attempting to add a service with the module.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getAdminAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Create domain label
        $domain = $fields->label(Language::_('Apnscp.service_field.domain', true), 'apnscp_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText(
                'apnscp_domain',
                (isset($vars->apnscp_domain) ? $vars->apnscp_domain : null),
                ['id' => 'apnscp_domain']
            )
        );
        // Set the label as a field
        $fields->setField($domain);

        // Create username label
        $username = $fields->label(Language::_('Apnscp.service_field.username', true), 'apnscp_username');
        // Create username field and attach to username label
        $username->attach(
            $fields->fieldText(
                'apnscp_username',
                (isset($vars->apnscp_username) ? $vars->apnscp_username : null),
                ['id' => 'apnscp_username']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Apnscp.service_field.tooltip.username', true));
        $username->attach($tooltip);
        // Set the label as a field
        $fields->setField($username);

        // Create password label
        $password = $fields->label(Language::_('Apnscp.service_field.password', true), 'apnscp_password');
        // Create password field and attach to password label
        $password->attach(
            $fields->fieldPassword(
                'apnscp_password',
                ['id' => 'apnscp_password', 'value' => (isset($vars->apnscp_password) ? $vars->apnscp_password : null)]
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Apnscp.service_field.tooltip.password', true));
        $password->attach($tooltip);
        // Set the label as a field
        $fields->setField($password);

        return $fields;
    }

    /**
     * Returns all fields to display to a client attempting to add a service with the module.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields to render as well
     *  as any additional HTML markup to include
     */
    public function getClientAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Create domain label
        $domain = $fields->label(Language::_('Apnscp.service_field.domain', true), 'apnscp_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText(
                'apnscp_domain',
                (isset($vars->apnscp_domain) ? $vars->apnscp_domain : ($vars->domain ?? null)),
                ['id' => 'apnscp_domain']
            )
        );
        // Set the label as a field
        $fields->setField($domain);

        return $fields;
    }

    /**
     * Returns all fields to display to an admin attempting to edit a service with the module.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields to render as
     *  well as any additional HTML markup to include
     */
    public function getAdminEditFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Create domain label
        $domain = $fields->label(Language::_('Apnscp.service_field.domain', true), 'apnscp_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText(
                'apnscp_domain',
                (isset($vars->apnscp_domain) ? $vars->apnscp_domain : null),
                ['id' => 'apnscp_domain']
            )
        );
        // Set the label as a field
        $fields->setField($domain);

        // Create username label
        $username = $fields->label(Language::_('Apnscp.service_field.username', true), 'apnscp_username');
        // Create username field and attach to username label
        $username->attach(
            $fields->fieldText(
                'apnscp_username',
                (isset($vars->apnscp_username) ? $vars->apnscp_username : null),
                ['id' => 'apnscp_username']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Apnscp.service_field.tooltip.username', true));
        $username->attach($tooltip);
        // Set the label as a field
        $fields->setField($username);

        // Create password label
        $password = $fields->label(Language::_('Apnscp.service_field.password', true), 'apnscp_password');
        // Create password field and attach to password label
        $password->attach(
            $fields->fieldPassword(
                'apnscp_password',
                ['id' => 'apnscp_password', 'value' => (isset($vars->apnscp_password) ? $vars->apnscp_password : null)]
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Apnscp.service_field.tooltip.password', true));
        $password->attach($tooltip);
        // Set the label as a field
        $fields->setField($password);

        return $fields;
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
        $this->Input->setRules($this->getServiceRules($vars));

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
        $this->Input->setRules($this->getServiceRules($vars, true));
        return $this->Input->validates($vars);
    }

    /**
     * Returns the rule set for adding/editing a service
     *
     * @param array $vars A list of input vars
     * @param bool $edit True to get the edit rules, false for the add rules
     * @return array Service rules
     */
    private function getServiceRules(array $vars = null, $edit = false)
    {
        $rules = [
            'apnscp_domain' => [
                'format' => [
                    'rule' => function ($host_name) {
                        return $this->validateHostName($host_name);
                    },
                    'message' => Language::_('Apnscp.!error.apnscp_domain.format', true)
                ]
            ],
            'apnscp_username' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[a-z]([a-z0-9])*$/i'],
                    'message' => Language::_('Apnscp.!error.apnscp_username.format', true)
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['betweenLength', 1, 16],
                    'message' => Language::_('Apnscp.!error.apnscp_username.length', true)
                ]
            ],
            'apnscp_password' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['isPassword', 8],
                    'message' => Language::_('Apnscp.!error.apnscp_password.valid', true),
                    'last' => true
                ],
            ]
        ];

        // Set the values that may be empty
        $empty_values = ['apnscp_username', 'apnscp_password'];

        if ($edit) {
            // If this is an edit and no password given then don't evaluate password
            // since it won't be updated
            if (!array_key_exists('apnscp_password', $vars) || $vars['apnscp_password'] == '') {
                unset($rules['apnscp_password']);
            }
        }

        // Remove rules on empty fields
        foreach ($empty_values as $value) {
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
        $row = $this->getModuleRow();

        if (!$row) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Apnscp.!error.module_row.missing', true)]]
            );

            return;
        }

        $api = $this->getApi(
            $row->meta->host_name,
            $row->meta->api_key,
            $row->meta->use_ssl,
            $row->meta->port
        );

        // Generate username/password
        if (array_key_exists('apnscp_domain', $vars)) {
            Loader::loadModels($this, ['Clients']);

            // Force domain to lower case
            $vars['apnscp_domain'] = strtolower($vars['apnscp_domain']);

            // Generate a username
            if (empty($vars['apnscp_username'])) {
                $vars['apnscp_username'] = $this->generateUsername($vars['apnscp_domain']);
            }

            // Generate a password
            if (empty($vars['apnscp_password'])) {
                $vars['apnscp_password'] = $this->generatePassword();
            }

            // Get client's contact information
            if (isset($vars['client_id']) && ($client = $this->Clients->get($vars['client_id'], false))) {
                $vars['apnscp_email'] = $client->email;
            }
        }

        $params = $this->getFieldsFromInput((array) $vars, $package);

        $this->validateService($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            // Create account on remote server
            $masked_params = $params;
            $masked_params['password'] = '***';
            $this->log($row->meta->host_name . '|admin_add_site', serialize($masked_params), 'input', true);
            unset($masked_params);

            $this->parseResponse($api->createAccount($params));

            if ($this->Input->errors()) {
                return;
            }

            // Update the number of accounts on the server
            $this->updateAccountCount($row);
        }

        // Return service fields
        return [
            [
                'key' => 'apnscp_domain',
                'value' => $vars['apnscp_domain'],
                'encrypted' => 0
            ],
            [
                'key' => 'apnscp_username',
                'value' => $vars['apnscp_username'],
                'encrypted' => 0
            ],
            [
                'key' => 'apnscp_password',
                'value' => $vars['apnscp_password'],
                'encrypted' => 1
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
    public function editService($package, $service, array $vars = null, $parent_package = null, $parent_service = null)
    {
        $row = $this->getModuleRow();
        $api = $this->getApi(
            $row->meta->host_name,
            $row->meta->api_key,
            $row->meta->use_ssl,
            $row->meta->port
        );

        $this->validateServiceEdit($service, $vars);

        if ($this->Input->errors()) {
            return;
        }

        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Remove password if not being updated
        if (isset($vars['apnscp_password']) && $vars['apnscp_password'] == '') {
            unset($vars['apnscp_password']);
        }

        // Only update the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            // Check for fields that changed
            $delta = [];
            foreach ($vars as $key => $value) {
                if (!array_key_exists($key, $service_fields) || $vars[$key] != $service_fields->$key) {
                    $delta[$key] = $value;
                }
            }

            // Update password (if changed)
            if (isset($delta['apnscp_password'])) {
                $params = [
                    'domain' => $service_fields->apnscp_domain,
                    'username' => $service_fields->apnscp_username,
                    'password' => $delta['apnscp_password']
                ];
                $masked_params = $params;
                $masked_params['password'] = '***';
                $this->log($row->meta->host_name . '|auth_change_password', serialize($masked_params), 'input', true);
                unset($masked_params);

                $this->parseResponse($api->updateAccountPassword($params));
            }
        }

        // Set fields to update locally
        $fields = ['apnscp_domain', 'apnscp_username', 'apnscp_password'];
        foreach ($fields as $field) {
            if (property_exists($service_fields, $field) && isset($vars[$field])) {
                $service_fields->{$field} = $vars[$field];
            }
        }

        // Return all the service fields
        $fields = [];
        $encrypted_fields = ['apnscp_password'];
        foreach ($service_fields as $key => $value) {
            $fields[] = ['key' => $key, 'value' => $value, 'encrypted' => (in_array($key, $encrypted_fields) ? 1 : 0)];
        }

        return $fields;
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
        if (($row = $this->getModuleRow())) {
            $api = $this->getApi(
                $row->meta->host_name,
                $row->meta->api_key,
                $row->meta->use_ssl,
                $row->meta->port
            );

            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Delete account
            $this->log(
                $row->meta->host_name . '|admin_deactivate_site',
                serialize($service_fields->apnscp_domain),
                'input',
                true
            );
            $this->parseResponse($api->suspendAccount($service_fields->apnscp_domain));
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
        if (($row = $this->getModuleRow())) {
            $api = $this->getApi(
                $row->meta->host_name,
                $row->meta->api_key,
                $row->meta->use_ssl,
                $row->meta->port
            );

            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Delete account
            $this->log(
                $row->meta->host_name . '|admin_activate_site',
                serialize($service_fields->apnscp_domain),
                'input',
                true
            );
            $this->parseResponse($api->unsuspendAccount($service_fields->apnscp_domain));
        }

        return null;
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
            $api = $this->getApi(
                $row->meta->host_name,
                $row->meta->api_key,
                $row->meta->use_ssl,
                $row->meta->port
            );

            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Delete account
            $this->log(
                $row->meta->host_name . '|admin_delete_site',
                serialize($service_fields->apnscp_domain),
                'input',
                true
            );
            $this->parseResponse($api->deleteAccount($service_fields->apnscp_domain));

            // Update the number of accounts on the server
            $this->updateAccountCount($row);
        }

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
        if (($row = $this->getModuleRow())) {
            $api = $this->getApi(
                $row->meta->host_name,
                $row->meta->api_key,
                $row->meta->use_ssl,
                $row->meta->port
            );

            // Only request a package change if it has changed
            if ($package_from->meta->package != $package_to->meta->package) {
                $service_fields = $this->serviceFieldsToObject($service->fields);

                // Change service package
                $this->log(
                    $row->meta->host_name . '|admin_edit_site',
                    serialize([$service_fields->apnscp_domain, $package_to->meta->package]),
                    'input',
                    true
                );
                $this->parseResponse(
                    $api->updateAccountPlan(
                        [
                            'domain' => $service_fields->apnscp_domain,
                            'plan' => $package_to->meta->package
                        ]
                    )
                );
            }
        }

        return null;
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'apnscp' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Retrieve a single sign-on session for the user to log in with
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $session = $this->getUserSession(
            $row,
            (isset($service_fields->apnscp_domain) ? $service_fields->apnscp_domain : null),
            (isset($service_fields->apnscp_username) ? $service_fields->apnscp_username : null)
        );

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $this->serviceFieldsToObject($service->fields));
        $this->view->set('login_url', (isset($session) ? $session : ''));

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'apnscp' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Retrieve a single sign-on session for the user to log in with
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $session = $this->getUserSession(
            $row,
            (isset($service_fields->apnscp_domain) ? $service_fields->apnscp_domain : null),
            (isset($service_fields->apnscp_username) ? $service_fields->apnscp_username : null)
        );

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $this->serviceFieldsToObject($service->fields));
        $this->view->set('login_url', (isset($session) ? $session : ''));

        return $this->view->fetch();
    }

    /**
     * Statistics tab (bandwidth/disk usage).
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

        // Get service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        $stats = $this->getStats($package, $service);

        $this->view->set('stats', $stats);
        $this->view->set('service_fields', $service_fields);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'apnscp' . DS);

        return $this->view->fetch();
    }

    /**
     * Client Statistics tab (bandwidth/disk usage).
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

        // Get service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        $stats = $this->getStats($package, $service);

        $this->view->set('stats', $stats);
        $this->view->set('service_fields', $service_fields);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'apnscp' . DS);

        return $this->view->fetch();
    }

    /**
     * Fetches all account stats.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @return stdClass A stdClass object representing all of the stats for the account
     */
    private function getStats($package, $service)
    {
        $row = $this->getModuleRow();
        $api = $this->getApi(
            $row->meta->host_name,
            $row->meta->api_key,
            $row->meta->use_ssl,
            $row->meta->port
        );

        // Get service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Get statistics
        $this->log(
            $row->meta->host_name . '|admin_hijack',
            serialize($service_fields->apnscp_domain),
            'input',
            true
        );
        $stats = $this->parseResponse($api->getAccount($service_fields->apnscp_domain));

        return $stats;
    }

    /**
     * Client Actions (reset password).
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

        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Perform the password reset
        if (!empty($post)) {
            Loader::loadModels($this, ['Services']);
            $data = array_merge((array) $service_fields, [
                'apnscp_password' => (isset($post['apnscp_password']) ? $post['apnscp_password'] : null)
            ]);

            $this->Services->edit($service->id, $data);

            if ($this->Services->errors()) {
                $this->Input->setErrors($this->Services->errors());
            }

            $vars = (object) $post;
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('vars', (isset($vars) ? $vars : new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'apnscp' . DS);

        return $this->view->fetch();
    }

    /**
     * Validates that the given hostname is valid.
     *
     * @param string $host_name The host name to validate
     * @return bool True if the hostname is valid, false otherwise
     */
    private function validateHostName($host_name)
    {
        $validator = new Server();
        return $validator->isDomain($host_name) || $validator->isIp($host_name);
    }

    /**
     * Validates that the nameservers given are formatted correctly.
     *
     * @param array $name_servers An array of name servers
     * @return bool True if every name server is formatted correctly, false otherwise
     */
    public function validateNameServers($name_servers)
    {
        if (is_array($name_servers)) {
            foreach ($name_servers as $name_server) {
                if (!$this->validateHostName($name_server)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Retrieves the accounts on the server.
     *
     * @param stdClass $api The APNSCP API
     * @return mixed The number of APNSCP accounts on the server, or false on error
     */
    private function getAccountCount($api)
    {
        $accounts = false;

        try {
            $output = $api->getAllAccounts();

            if (is_array($output)) {
                $accounts = count($output);
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
            $module_row->meta->api_key,
            $module_row->meta->use_ssl,
            $module_row->meta->port
        );

        // Get the number of accounts on the server
        if (($count = $this->getAccountCount($api)) !== false) {
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

    /**
     * Validates whether or not the connection details are valid by attempting to fetch
     * the number of accounts that currently reside on the server.
     *
     * @param mixed $api_key The API key
     * @param mixed $hostname The remote server hostname
     * @param mixed $use_ssl True to use SSL, false otherwise
     * @param mixed $account_count The account count number on the remote server
     * @param mixed $port The remote server port
     * @return bool True if the connection is valid, false otherwise
     */
    public function validateConnection($api_key, $hostname, $use_ssl, &$account_count, $port = '2083')
    {
        try {
            $api = $this->getApi($hostname, $api_key, $use_ssl, $port);

            $count = $this->getAccountCount($api);
            if ($count !== false) {
                $account_count = $count;

                return true;
            }
        } catch (Exception $e) {
            // Trap any errors encountered, could not validate connection
        }

        return false;
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

        $username = substr($username, 0, min($length, 9));

        // Check for an existing user account
        $row = $this->getModuleRow();

        if ($row) {
            $api = $this->getApi(
                $row->meta->host_name,
                $row->meta->api_key,
                $row->meta->use_ssl,
                $row->meta->port
            );
        }

        $account = $api->apiRequest('admin_hijack', [$username]);

        $account_matching_characters = 3;

        // Username exists, create another instead
        if (!empty($account) && !isset($account->error)) {
            for ($i = 0; $i < (int) str_repeat(9, $account_matching_characters); $i++) {
                $new_username = substr($username, 0, -$account_matching_characters) . $i;
                if (!empty($account) && isset($account->error)) {
                    $username = $new_username;
                    break;
                }
            }
        }

        return $username;
    }

    /**
     * Generates a password.
     *
     * @param int $min_length The minimum character length for the password (5 or larger)
     * @param int $max_length The maximum character length for the password (14 or fewer)
     * @return string The generated password
     */
    private function generatePassword($min_length = 10, $max_length = 14)
    {
        $pool = 'abcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
        $pool_size = strlen($pool);
        $length = mt_rand(max($min_length, 5), min($max_length, 14));
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= substr($pool, mt_rand(0, $pool_size - 1), 1);
        }

        return $password;
    }

    /**
     * Returns an array of service field to set for the service using the given input.
     *
     * @param array $vars An array of key/value input pairs
     * @param stdClass $package A stdClass object representing the package for the service
     * @return array An array of key/value pairs representing service fields
     */
    private function getFieldsFromInput(array $vars, $package)
    {
        $fields = [
            'domain' => isset($vars['apnscp_domain']) ? $vars['apnscp_domain'] : null,
            'username' => isset($vars['apnscp_username']) ? $vars['apnscp_username'] : null,
            'password' => isset($vars['apnscp_password']) ? $vars['apnscp_password'] : null,
            'email' => isset($vars['apnscp_email']) ? $vars['apnscp_email'] : null,
            'plan' => $package->meta->package
        ];

        return $fields;
    }

    /**
     * Parses the response from the API into a stdClass object.
     *
     * @param mixed $response The response from the API
     * @return mixed The response, void if the response was an error
     */
    private function parseResponse($response)
    {
        $row = $this->getModuleRow();

        $success = true;

        // Set internal error
        if (!$response) {
            $this->Input->setErrors(['api' => ['internal' => Language::_('Apnscp.!error.api.internal', true)]]);
            $success = false;
        }

        // Only some API requests return status, so only use it if its available
        if (isset($response->error)) {
            $this->Input->setErrors(['api' => ['result' => $response->error]]);
            $success = false;
        }

        // Log the response
        $this->log(
            $row->meta->host_name,
            serialize($response),
            'output',
            $success
        );

        // Return if any errors encountered
        if (!$success) {
            return;
        }

        return $response;
    }

    /**
     * Initializes the ApnscpApi and returns an instance of that object.
     *
     * @param string $hostname The host to the APNSCP server
     * @param string $api_key The remote API Key
     * @param mixed $use_ssl True to use SSL, false otherwise
     * @return ApnscpApi The ApnscpApi instance
     */
    private function getApi($hostname, $api_key, $use_ssl = true, $port = '2083')
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'apnscp_api.php');

        $api = new ApnscpApi($hostname, $api_key, $use_ssl, $port);

        return $api;
    }

    /**
     * Fetches a listing of all packages configured in APNSCP for the given server.
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @return array An array of packages in key/value pair
     */
    private function getApnscpPackages($module_row)
    {
        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->api_key,
            $module_row->meta->use_ssl,
            $module_row->meta->port
        );
        $packages = [];

        try {
            $this->log($module_row->meta->host_name . '|admin_list_plans', null, 'input', true);
            $packages = $api->getHostingPlans();

            $success = false;

            if (!empty($packages)) {
                $success = true;
            }

            $this->log($module_row->meta->host_name, serialize($packages), 'output', $success);
        } catch (Exception $e) {
            // API request failed
        }

        return $packages;
    }

    /**
     * Creates a new user session with APNSCP for the given user
     *
     * @param stdClass $module_row The module row
     * @param string $domain The APNSCP domain to authenticate with
     * @param string $username The APNSCP username to authenticate with
     * @return string The SSO Link
     */
    private function getUserSession($module_row, $domain, $username)
    {
        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->api_key,
            $module_row->meta->use_ssl,
            $module_row->meta->port
        );

        $this->log($module_row->meta->host_name . '|admin_hijack', serialize([$domain, $username]), 'input', true);
        $response = $this->parseResponse($api->getSsoLink($domain, $username));

        if (isset($response->error)) {
            return null;
        }

        return $response;
    }

    /**
     * Builds and returns the rules required to add/edit a module row (e.g. server).
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getRowRules(&$vars)
    {
        $rules = [
            'server_name' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Apnscp.!error.server_name_valid', true)
                ]
            ],
            'host_name' => [
                'valid' => [
                    'rule' => function ($host_name) {
                        return $this->validateHostName($host_name);
                    },
                    'message' => Language::_('Apnscp.!error.host_name_valid', true)
                ]
            ],
            'port' => [
                'format' => [
                    'rule' => 'is_numeric',
                    'message' => Language::_('Apnscp.!error.port_format', true)
                ]
            ],
            'api_key' => [
                'valid' => [
                    'last' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Apnscp.!error.api_key_valid', true)
                ],
                'valid_connection' => [
                    'rule' => [
                        [$this, 'validateConnection'],
                        $vars['host_name'],
                        $vars['use_ssl'],
                        &$vars['account_count'],
                        $vars['port']
                    ],
                    'message' => Language::_('Apnscp.!error.api_key_valid_connection', true)
                ]
            ],
            'account_limit' => [
                'valid' => [
                    'rule' => ['matches', '/^([0-9]+)?$/'],
                    'message' => Language::_('Apnscp.!error.account_limit_valid', true)
                ]
            ],
            'name_servers' => [
                'count' => [
                    'rule' => function ($name_servers) {
                        return (is_array($name_servers) && count($name_servers) >= 2);
                    },
                    'message' => Language::_('Apnscp.!error.name_servers_count', true)
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
                    'message' => Language::_('Apnscp.!error.name_servers_valid', true)
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Builds and returns rules required to be validated when adding/editing a package.
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getPackageRules(array $vars)
    {
        $rules = [
            'meta[package]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Apnscp.!error.meta[package].empty', true) // package must be given
                ]
            ]
        ];

        return $rules;
    }
}
