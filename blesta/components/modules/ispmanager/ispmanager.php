<?php
use Blesta\Core\Util\Validate\Server;
/**
 * ISPmanager Module.
 *
 * @package blesta
 * @subpackage blesta.components.modules.ispmanager
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Ispmanager extends Module
{
    /**
     * Initializes the module.
     */
    public function __construct()
    {
        // Load configuration required by this module
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('ispmanager', null, dirname(__FILE__) . DS . 'language' . DS);
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
            'tabClientActions' => Language::_('Ispmanager.tab_client_actions', true)
        ];
    }

    /**
     * Returns an array of available service deligation order methods. The module
     * will determine how each method is defined. For example, the method "first"
     * may be implemented such that it returns the module row with the least number
     * of services assigned to it.
     *
     * @return array An array of order methods in key/value paris where the key is
     *  the type to be stored for the group and value is the name for that option
     * @see Module::selectModuleRow()
     */
    public function getGroupOrderOptions()
    {
        return [
            'roundrobin' => Language::_('Ispmanager.order_options.roundrobin', true),
            'first' => Language::_('Ispmanager.order_options.first', true)
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

        // Fetch all templates available for the given server or server group
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

        $templates = [];

        if ($module_row) {
            $templates = $this->getTemplates($module_row);
        }

        // Create template label
        $template = $fields->label(Language::_('Ispmanager.package_fields.template', true), 'ispmanager_template');
        // Create template field and attach to template label
        $template->attach(
            $fields->fieldSelect(
                'meta[template]',
                $templates,
                (isset($vars->meta['template']) ? $vars->meta['template'] : null),
                ['id' => 'ispmanager_template']
            )
        );
        // Set the label as a field
        $fields->setField($template);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ispmanager' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ispmanager' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ispmanager' . DS);

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
        $meta_fields = ['server_name', 'host_name', 'user_name', 'password',
            'use_ssl', 'account_limit', 'account_count', 'name_servers', 'notes'];
        $encrypted_fields = ['password'];

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
        $meta_fields = ['server_name', 'host_name', 'user_name', 'password',
            'use_ssl', 'account_limit', 'account_count', 'name_servers', 'notes'];
        $encrypted_fields = ['password'];

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
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getAdminAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Create domain label
        $domain = $fields->label(Language::_('Ispmanager.service_field.domain', true), 'ispmanager_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText(
                'ispmanager_domain',
                (isset($vars->ispmanager_domain) ? $vars->ispmanager_domain : null),
                ['id' => 'ispmanager_domain']
            )
        );
        // Set the label as a field
        $fields->setField($domain);

        // Create username label
        $username = $fields->label(
            Language::_('Ispmanager.service_field.username', true),
            'ispmanager_username'
        );
        // Create username field and attach to username label
        $username->attach(
            $fields->fieldText(
                'ispmanager_username',
                (isset($vars->ispmanager_username) ? $vars->ispmanager_username : null),
                ['id' => 'ispmanager_username']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Ispmanager.service_field.tooltip.username', true));
        $username->attach($tooltip);
        // Set the label as a field
        $fields->setField($username);

        // Create password label
        $password = $fields->label(
            Language::_('Ispmanager.service_field.password', true),
            'ispmanager_password'
        );
        // Create password field and attach to password label
        $password->attach(
            $fields->fieldPassword(
                'ispmanager_password',
                ['id' => 'ispmanager_password', 'value' => (isset($vars->ispmanager_password) ? $vars->ispmanager_password : null)]
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Ispmanager.service_field.tooltip.password', true));
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
     * @return ModuleFields A ModuleFields object, containg the fields to render as well
     *  as any additional HTML markup to include
     */
    public function getClientAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Create domain label
        $domain = $fields->label(Language::_('Ispmanager.service_field.domain', true), 'ispmanager_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText(
                'ispmanager_domain',
                (isset($vars->ispmanager_domain) ? $vars->ispmanager_domain : ($vars->domain ?? null)),
                ['id' => 'ispmanager_domain']
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
     * @return ModuleFields A ModuleFields object, containg the fields to render as
     *  well as any additional HTML markup to include
     */
    public function getAdminEditFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Create password label
        $password = $fields->label(
            Language::_('Ispmanager.service_field.password', true),
            'ispmanager_password'
        );
        // Create password field and attach to password label
        $password->attach(
            $fields->fieldPassword(
                'ispmanager_password',
                ['id' => 'ispmanager_password', 'value' => (isset($vars->ispmanager_password) ? $vars->ispmanager_password : null)]
            )
        );
        // Set the label as a field
        $fields->setField($password);

        return $fields;
    }

    /**
     * Attempts to validate service info. This is the top-level error checking method. Sets Input errors on failure.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @param bool $edit True if this is an edit, false otherwise
     * @return bool True if the service validates, false otherwise. Sets Input errors when false.
     */
    public function validateService($package, array $vars = null, $edit = false)
    {
        $rules = [
            'ispmanager_domain' => [
                'format' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Ispmanager.!error.ispmanager_domain.format', true)
                ]
            ],
            'ispmanager_username' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^([a-z0-9])+$/i'],
                    'message' => Language::_('Ispmanager.!error.ispmanager_username.format', true)
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['betweenLength', 1, 16],
                    'message' => Language::_('Ispmanager.!error.ispmanager_username.length', true)
                ]
            ],
            'ispmanager_password' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['isPassword', 8],
                    'message' => Language::_('Ispmanager.!error.ispmanager_password.valid', true),
                    'last' => true
                ],
            ]
        ];

        // Set the values that may be empty
        $empty_values = ['ispmanager_username', 'ispmanager_password'];

        if ($edit) {
            // If this is an edit and no password given then don't evaluate password
            // since it won't be updated
            if (!array_key_exists('ispmanager_password', $vars) || $vars['ispmanager_password'] == '') {
                unset($rules['ispmanager_password']);
            }

            unset($rules['ispmanager_domain']);
            unset($rules['ispmanager_username']);
        }

        // Remove rules on empty fields
        foreach ($empty_values as $value) {
            if (empty($vars[$value])) {
                unset($rules[$value]);
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
                ['module_row' => ['missing' => Language::_('Ispmanager.!error.module_row.missing', true)]]
            );

            return;
        }

        $api = $this->getApi($row->meta->host_name, $row->meta->user_name, $row->meta->password, $row->meta->use_ssl);

        // Generate username/password
        if (array_key_exists('ispmanager_domain', $vars)) {
            Loader::loadModels($this, ['Clients']);

            // Force domain to lower case
            $vars['ispmanager_domain'] = strtolower($vars['ispmanager_domain']);

            // Generate a username
            if (empty($vars['ispmanager_username'])) {
                $vars['ispmanager_username'] = $this->generateUsername($vars['ispmanager_domain']);
            }

            // Generate a password
            if (empty($vars['ispmanager_password'])) {
                $vars['ispmanager_password'] = $this->generatePassword();
            }

            // Get client's contact email address
            if (isset($vars['client_id']) && ($client = $this->Clients->get($vars['client_id'], false))) {
                $vars['ispmanager_full_name'] = $client->first_name . ' ' . $client->last_name;
            }
        }

        $params = $this->getFieldsFromInput((array) $vars, $package);

        $this->validateService($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            $masked_params = $params;
            $masked_params['passwd'] = '***';
            $this->log($row->meta->host_name . '|user.add', serialize($masked_params), 'input', true);
            unset($masked_params);

            // Create account
            $response = $this->parseResponse($api->createAccount($params));

            if ($this->Input->errors()) {
                return;
            }

            // Update the number of accounts on the server
            $this->updateAccountCount($row);
        }

        // Return service fields
        return [
            [
                'key' => 'ispmanager_domain',
                'value' => $vars['ispmanager_domain'],
                'encrypted' => 0
            ],
            [
                'key' => 'ispmanager_username',
                'value' => $vars['ispmanager_username'],
                'encrypted' => 0
            ],
            [
                'key' => 'ispmanager_password',
                'value' => $vars['ispmanager_password'],
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
        $api = $this->getApi($row->meta->host_name, $row->meta->user_name, $row->meta->password, $row->meta->use_ssl);

        $params = $this->getFieldsFromInput((array) $vars, $package, true);
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Default fields using service fields
        if (!isset($params['passwd'])) {
            $params['passwd'] = $service_fields->ispmanager_password;
        }

        // The domain and username can't be updated
        $params['webdomain_name'] = $service_fields->ispmanager_domain;
        $params['emaildomain_name'] = $service_fields->ispmanager_domain;
        $params['name'] = $service_fields->ispmanager_username;

        $this->validateService($package, $vars, true);

        if ($this->Input->errors()) {
            return;
        }

        // Remove password if not being updated
        if (isset($params['passwd']) && $params['passwd'] == '') {
            unset($params['passwd']);
        }

        // Only update the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            // Update ISPmanager account
            $masked_params = $params;
            $masked_params['passwd'] = '***';

            // Edit account
            $params['elid'] = $service_fields->ispmanager_username;
            $this->log($row->meta->host_name . '|user.edit', serialize($masked_params), 'input', true);
            $response = $this->parseResponse($api->updateAccount($params));

            if ($this->Input->errors()) {
                return;
            }
        }

        // Set fields to update locally
        $field_mappings = [
            'passwd' => 'ispmanager_password'
        ];
        foreach ($field_mappings as $field => $field_mapping) {
            if (property_exists($service_fields, $field_mapping) && isset($params[$field])) {
                $service_fields->{$field_mapping} = $params[$field];
            }
        }

        // Return all the service fields
        $fields = [];
        $encrypted_fields = ['ispmanager_password'];
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
                $row->meta->user_name,
                $row->meta->password,
                $row->meta->use_ssl
            );

            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Suspend ISPmanager account
            $this->log(
                $row->meta->host_name . '|user.suspend',
                serialize($service_fields->ispmanager_username),
                'input',
                true
            );

            $response = $this->parseResponse($api->suspendAccount($service_fields->ispmanager_username));

            if ($this->Input->errors()) {
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
        if (($row = $this->getModuleRow())) {
            $api = $this->getApi(
                $row->meta->host_name,
                $row->meta->user_name,
                $row->meta->password,
                $row->meta->use_ssl
            );

            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Unsuspend ISPmanager account
            $this->log(
                $row->meta->host_name . '|user.suspend',
                serialize($service_fields->ispmanager_username),
                'input',
                true
            );

            $response = $this->parseResponse($api->unsuspendAccount($service_fields->ispmanager_username));

            if ($this->Input->errors()) {
                return;
            }
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
                $row->meta->user_name,
                $row->meta->password,
                $row->meta->use_ssl
            );

            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Delete ISPmanager account
            $this->log(
                $row->meta->host_name . '|user.delete',
                serialize($service_fields->ispmanager_username),
                'input',
                true
            );
            $response = $this->parseResponse($api->removeAccount($service_fields->ispmanager_username));

            if ($this->Input->errors()) {
                return;
            }

            // Update the number of accounts on the server
            $this->updateAccountCount($row, false);
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
                $row->meta->user_name,
                $row->meta->password,
                $row->meta->use_ssl
            );

            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Update account package
            $this->log(
                $row->meta->host_name . '|user.edit',
                serialize($service_fields->ispmanager_username),
                'input',
                true
            );

            $params = [
                'elid' => $service_fields->ispmanager_username,
                'preset' => $package_to->meta->template
            ];
            $response = $this->parseResponse($api->updateAccount($params));

            if ($this->Input->errors()) {
                return;
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ispmanager' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ispmanager' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $this->serviceFieldsToObject($service->fields));

        return $this->view->fetch();
    }

    /**
     * Client Actions (reset password)
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

            if (
                ($post['ispmanager_password'] == $post['ispmanager_confirm_password'])
                && !empty($post['ispmanager_password'])
            ) {
                $data = [
                    'ispmanager_password' => (isset($post['ispmanager_password']) ? $post['ispmanager_password'] : null)
                ];
                $this->Services->edit($service->id, $data);
            } else {
                $this->Input->setErrors([
                    'ispmanager_password' => [
                        'matches' => Language::_('Ispmanager.!error.ispmanager_password.matches', true)
                    ]
                ]);
            }

            if ($this->Services->errors()) {
                $this->Input->setErrors($this->Services->errors());
            }

            $vars = (object)$post;
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('vars', (isset($vars) ? $vars : new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ispmanager' . DS);
        return $this->view->fetch();
    }

    /**
     * Validates that the given hostname is valid.
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
     * Validates that at least 2 name servers are set in the given array of name servers.
     *
     * @param array $name_servers An array of name servers
     * @return bool True if the array count is >= 2, false otherwise
     */
    public function validateNameServerCount($name_servers)
    {
        if (is_array($name_servers) && count($name_servers) >= 2) {
            return true;
        }

        return false;
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
     * Updates the module row meta number of accounts.
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @param bool $increase Increments the account count when true, decrements when false
     */
    private function updateAccountCount($module_row, $increase = true)
    {
        // Get module row meta
        $vars = $this->ModuleManager->getRowMeta($module_row->id);

        // Update account count
        $count = (int) $vars->account_count;

        if ($increase) {
            $vars->account_count = $count + 1;
        } else {
            $vars->account_count = $count - 1;
        }

        if ($vars->account_count < 0) {
            $vars->account_count = 0;
        }

        // Update the module row account list
        $vars = (array) $vars;
        $this->ModuleManager->editRow($module_row->id, $vars);
    }

    /**
     * Validates whether or not the connection details are valid by attempting to fetch
     * the number of accounts that currently reside on the server.
     *
     * @param string $password The ISPmanager server password
     * @param string $hostname The ISPmanager server hostname
     * @param string $user_name The ISPmanager server user name
     * @param mixed $use_ssl Whether or not to use SSL
     * @param int $account_count The number of existing accounts on the server
     * @return bool True if the connection is valid, false otherwise
     */
    public function validateConnection($password, $hostname, $user_name, $use_ssl, &$account_count)
    {
        try {
            $api = $this->getApi($hostname, $user_name, $password, $use_ssl);

            $params = compact('hostname', 'user_name', 'password', 'use_ssl');
            $masked_params = $params;
            $masked_params['user_name'] = '***';
            $masked_params['password'] = '***';

            $this->log($hostname . '|user', serialize($masked_params), 'input', true);

            $response = $api->getAccounts();

            $success = false;
            if (!isset($response->error)) {
                $account_count = isset($response->response) ? count($response->response) : 0;
                $success = true;
            }

            $this->log($hostname . '|user', serialize($response), 'output', $success);

            if ($success) {
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
            for ($i = $length; $i < 8; $i++) {
                $username .= substr($pool, mt_rand(0, $pool_size - 1), 1);
            }
            $length = strlen($username);
        }

        $username = substr($username, 0, min($length, 8));

        // Check for an existing user account
        $row = $this->getModuleRow();

        if ($row) {
            $api = $this->getApi($row->meta->host_name, $row->meta->user_name, $row->meta->password, $row->meta->use_ssl);

            // Username exists, create another instead
            if ($api->accountExists($username)) {
                for ($i = 0; strlen((string)$i) < 8; $i++) {
                    $new_username = substr($username, 0, -strlen((string)$i)) . $i;
                    if (!$api->accountExists($new_username)) {
                        $username = $new_username;
                        break;
                    }
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
            'name' => isset($vars['ispmanager_username']) ? $vars['ispmanager_username'] : null,
            'fullname' => isset($vars['ispmanager_full_name']) ? $vars['ispmanager_full_name'] : null,
            'passwd' => isset($vars['ispmanager_password']) ? $vars['ispmanager_password'] : null,
            'confirm' => isset($vars['ispmanager_password']) ? $vars['ispmanager_password'] : null,
            'webdomain_name' => isset($vars['ispmanager_domain']) ? $vars['ispmanager_domain'] : null,
            'emaildomain_name' => isset($vars['ispmanager_domain']) ? $vars['ispmanager_domain'] : null,
            'preset' => $package->meta->template
        ];

        return $fields;
    }

    /**
     * Parses the response from the API into a stdClass object.
     *
     * @param string $response The response from the API
     * @return stdClass A stdClass object representing the response, void if the response was an error
     */
    private function parseResponse($response)
    {
        $row = $this->getModuleRow();

        $success = true;

        // Set internal error
        if (!$response) {
            $this->Input->setErrors(['api' => ['internal' => Language::_('Ispmanager.!error.api.internal', true)]]);
            $success = false;
        }

        // Only some API requests return status, so only use it if its available
        if (isset($response->error)) {
            $this->Input->setErrors(['api' => ['result' => $response->error]]);
            $success = false;
        }

        // Log the response
        $this->log($row->meta->host_name, serialize($response), 'output', $success);

        // Return if any errors encountered
        if (!$success) {
            return;
        }

        return isset($response->response) ? $response->response : $response;
    }

    /**
     * Initializes the IspmanagerApi and returns an instance of that object.
     *
     * @param string $hostname The ISPmanager server hostname
     * @param string $user_name The ISPmanager server user name
     * @param string $password The ISPmanager server password
     * @param mixed $use_ssl Whether or not to use SSL
     * @return IspmanagerApi The IspmanagerApi instance
     */
    private function getApi($hostname, $user_name, $password, $use_ssl = 'true')
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'ispmanager_api.php');

        $api = new IspmanagerApi($hostname, $user_name, $password, $use_ssl === 'true');

        return $api;
    }

    /**
     * Fetches a listing of all templates configured in ISPmanager for the given server
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @return array An array of packages in key/value pair
     */
    private function getTemplates($module_row)
    {
        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->user_name,
            $module_row->meta->password,
            $module_row->meta->use_ssl
        );
        $packages = [];

        try {
            $this->log($module_row->meta->host_name . '|preset', null, 'input', true);
            $package_list = $api->getTemplates();

            $success = false;
            if (!isset($package_list->error)) {
                foreach ($package_list->response as $package) {
                    $packages[$package->name] = $package->name;
                }
                $success = true;
            }

            $this->log($module_row->meta->host_name, serialize($package_list), 'output', $success);
        } catch (Exception $e) {
            // API request failed
        }

        return $packages;
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
                    'message' => Language::_('Ispmanager.!error.server_name_valid', true)
                ]
            ],
            'host_name' => [
                'valid' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Ispmanager.!error.host_name_valid', true)
                ]
            ],
            'user_name' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Ispmanager.!error.user_name_valid', true)
                ]
            ],
            'password' => [
                'valid' => [
                    'last' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Ispmanager.!error.password_valid', true)
                ],
                'valid_connection' => [
                    'rule' => [
                        [$this, 'validateConnection'],
                        $vars['host_name'],
                        $vars['user_name'],
                        $vars['use_ssl'],
                        &$vars['account_count']
                    ],
                    'message' => Language::_('Ispmanager.!error.password_valid_connection', true)
                ]
            ],
            'account_limit' => [
                'valid' => [
                    'rule' => ['matches', '/^([0-9]+)?$/'],
                    'message' => Language::_('Ispmanager.!error.account_limit_valid', true)
                ]
            ],
            'name_servers' => [
                'count' => [
                    'rule' => [[$this, 'validateNameServerCount']],
                    'message' => Language::_('Ispmanager.!error.name_servers_count', true)
                ],
                'valid' => [
                    'rule' => [[$this, 'validateNameServers']],
                    'message' => Language::_('Ispmanager.!error.name_servers_valid', true)
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
            'meta[template]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Ispmanager.!error.meta[template].empty', true) // package must be given
                ]
            ]
        ];

        return $rules;
    }
}
