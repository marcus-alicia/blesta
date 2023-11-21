<?php
use Blesta\Core\Util\Validate\Server;
/**
 * CyberPanel Module.
 *
 * @package blesta
 * @subpackage blesta.components.modules.cyberpanel
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Cyberpanel extends Module
{
    /**
     * Initializes the module.
     */
    public function __construct()
    {
        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('cyberpanel', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load module config
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
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
            'tabClientActions' => Language::_('Cyberpanel.tab_client_actions', true)
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
            'roundrobin' => Language::_('Cyberpanel.order_options.roundrobin', true),
            'first' => Language::_('Cyberpanel.order_options.first', true)
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

        // Create package label
        $package = $fields->label(Language::_('Cyberpanel.package_fields.package', true), 'cyberpanel_package');
        // Create package field and attach to package label
        $package->attach(
            $fields->fieldText(
                'meta[package]',
                (isset($vars->meta['package']) ? $vars->meta['package'] : null),
                ['id' => 'cyberpanel_package']
            )
        );
        // Set the label as a field
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'cyberpanel' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'cyberpanel' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'cyberpanel' . DS);

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
        $meta_fields = ['server_name', 'host_name', 'admin_username', 'admin_password',
            'use_ssl',  'name_servers', 'notes'];
        $encrypted_fields = ['admin_password'];

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
        $meta_fields = ['server_name', 'host_name', 'admin_username', 'admin_password',
            'use_ssl', 'name_servers', 'notes'];
        $encrypted_fields = ['admin_password'];

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
     * Returns the value used to identify a particular service.
     *
     * @param stdClass $service A stdClass object representing the service
     * @return string A value used to identify this service amongst other similar services
     */
    public function getServiceName($service)
    {
        foreach ($service->fields as $field) {
            if ($field->key == 'cyberpanel_domain') {
                return $field->value;
            }
        }

        return null;
    }

    /**
     * Returns the value used to identify a particular package service which has
     * not yet been made into a service. This may be used to uniquely identify
     * an uncreated services of the same package (i.e. in an order form checkout).
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @return string The value used to identify this package service
     * @see Module::getServiceName()
     */
    public function getPackageServiceName($package, array $vars = null)
    {
        if (isset($vars['cyberpanel_domain'])) {
            return $vars['cyberpanel_domain'];
        }

        return null;
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
        $domain = $fields->label(Language::_('Cyberpanel.service_field.domain', true), 'cyberpanel_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText(
                'cyberpanel_domain',
                (isset($vars->cyberpanel_domain) ? $vars->cyberpanel_domain : null),
                ['id' => 'cyberpanel_domain']
            )
        );
        // Set the label as a field
        $fields->setField($domain);

        // Create username label
        $username = $fields->label(Language::_('Cyberpanel.service_field.username', true), 'cyberpanel_username');
        // Create username field and attach to username label
        $username->attach(
            $fields->fieldText(
                'cyberpanel_username',
                (isset($vars->cyberpanel_username) ? $vars->cyberpanel_username : null),
                ['id' => 'cyberpanel_username']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Cyberpanel.service_field.tooltip.username', true));
        $username->attach($tooltip);
        // Set the label as a field
        $fields->setField($username);

        // Create password label
        $password = $fields->label(Language::_('Cyberpanel.service_field.password', true), 'cyberpanel_password');
        // Create password field and attach to password label
        $password->attach(
            $fields->fieldPassword(
                'cyberpanel_password',
                ['id' => 'cyberpanel_password', 'value' => (isset($vars->cyberpanel_password) ? $vars->cyberpanel_password : null)]
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Cyberpanel.service_field.tooltip.password', true));
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
        $domain = $fields->label(Language::_('Cyberpanel.service_field.domain', true), 'cyberpanel_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText(
                'cyberpanel_domain',
                (isset($vars->cyberpanel_domain) ? $vars->cyberpanel_domain : ($vars->domain ?? null)),
                ['id' => 'cyberpanel_domain']
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

        // Create domain label
        $domain = $fields->label(Language::_('Cyberpanel.service_field.domain', true), 'cyberpanel_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText(
                'cyberpanel_domain',
                (isset($vars->cyberpanel_domain) ? $vars->cyberpanel_domain : null),
                ['id' => 'cyberpanel_domain']
            )
        );
        // Set the label as a field
        $fields->setField($domain);

        // Create username label
        $username = $fields->label(Language::_('Cyberpanel.service_field.username', true), 'cyberpanel_username');
        // Create username field and attach to username label
        $username->attach(
            $fields->fieldText(
                'cyberpanel_username',
                (isset($vars->cyberpanel_username) ? $vars->cyberpanel_username : null),
                ['id' => 'cyberpanel_username']
            )
        );
        // Set the label as a field
        $fields->setField($username);

        // Create password label
        $password = $fields->label(Language::_('Cyberpanel.service_field.password', true), 'cyberpanel_password');
        // Create password field and attach to password label
        $password->attach(
            $fields->fieldPassword(
                'cyberpanel_password',
                ['id' => 'cyberpanel_password', 'value' => (isset($vars->cyberpanel_password) ? $vars->cyberpanel_password : null)]
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
     * Returns the rule set for adding/editing a service.
     *
     * @param array $vars A list of input vars
     * @param bool $edit True to get the edit rules, false for the add rules
     * @return array Service rules
     */
    private function getServiceRules(array $vars = null, $edit = false)
    {
        $rules = [
            'cyberpanel_domain' => [
                'format' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Cyberpanel.!error.cyberpanel_domain.format', true)
                ]
            ],
            'cyberpanel_username' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[a-z]([a-z0-9])*$/i'],
                    'message' => Language::_('Cyberpanel.!error.cyberpanel_username.format', true)
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['betweenLength', 1, 16],
                    'message' => Language::_('Cyberpanel.!error.cyberpanel_username.length', true)
                ]
            ],
            'cyberpanel_password' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['isPassword', 8],
                    'message' => Language::_('Cyberpanel.!error.cyberpanel_password.valid', true),
                    'last' => true
                ],
            ]
        ];

        // Set the values that may be empty
        $empty_values = ['cyberpanel_username', 'cyberpanel_password'];

        if ($edit) {
            // If this is an edit and no password given then don't evaluate password
            // since it won't be updated
            if (!array_key_exists('cyberpanel_password', $vars) || $vars['cyberpanel_password'] == '') {
                unset($rules['cyberpanel_password']);
            }

            // Validate domain if given
            $rules['cyberpanel_domain']['format']['if_set'] = true;
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
                ['module_row' => ['missing' => Language::_('Cyberpanel.!error.module_row.missing', true)]]
            );

            return;
        }

        $api = $this->getApi(
            $row->meta->host_name,
            $row->meta->admin_username,
            $row->meta->admin_password,
            $row->meta->use_ssl
        );

        // Generate username/password
        if (array_key_exists('cyberpanel_domain', $vars)) {
            Loader::loadModels($this, ['Clients']);

            // Strip "www." from beginning of domain if present
            $vars['cyberpanel_domain'] = $this->formatDomain($vars['cyberpanel_domain']);

            // Generate a username
            if (empty($vars['cyberpanel_username'])) {
                $vars['cyberpanel_username'] = $this->generateUsername($vars['cyberpanel_domain']);
            }

            // Generate a password
            if (empty($vars['cyberpanel_password'])) {
                $vars['cyberpanel_password'] = $this->generatePassword();
            }

            // Get client's contact email address
            if (isset($vars['client_id']) && ($client = $this->Clients->get($vars['client_id'], false))) {
                $vars['cyberpanel_email'] = $client->email;
            }
        }

        $params = $this->getFieldsFromInput((array) $vars, $package);

        $this->validateService($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            // Create CyberPanel account
            $masked_params = $params;
            $masked_params['password'] = '***';
            $this->log($row->meta->host_name . '|createWebsite', serialize($masked_params), 'input', true);
            unset($masked_params);
            $this->parseResponse($api->createAccount($params));

            if ($this->Input->errors()) {
                return;
            }
        }

        // Return service fields
        return [
            [
                'key' => 'cyberpanel_domain',
                'value' => $vars['cyberpanel_domain'],
                'encrypted' => 0
            ],
            [
                'key' => 'cyberpanel_username',
                'value' => $vars['cyberpanel_username'],
                'encrypted' => 0
            ],
            [
                'key' => 'cyberpanel_password',
                'value' => $vars['cyberpanel_password'],
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
            $row->meta->admin_username,
            $row->meta->admin_password,
            $row->meta->use_ssl
        );

        $this->validateServiceEdit($service, $vars);

        // Strip "www." from beginning of domain if present
        if (isset($vars['cyberpanel_domain'])) {
            $vars['cyberpanel_domain'] = $this->formatDomain($vars['cyberpanel_domain']);
        }

        if ($this->Input->errors()) {
            return;
        }

        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Remove password if not being updated
        if (isset($vars['cyberpanel_password']) && $vars['cyberpanel_password'] == '') {
            unset($vars['cyberpanel_password']);
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
            if (isset($delta['cyberpanel_password'])) {
                $this->log($row->meta->host_name . '|changeUserPassAPI', '***', 'input', true);
                $this->parseResponse(
                    $api->updateAccountPassword($service_fields->cyberpanel_username, $delta['cyberpanel_password'])
                );
            }
        }

        // Set fields to update locally
        $fields = ['cyberpanel_domain', 'cyberpanel_username', 'cyberpanel_password'];
        foreach ($fields as $field) {
            if (property_exists($service_fields, $field) && isset($vars[$field])) {
                $service_fields->{$field} = $vars[$field];
            }
        }

        // Return all the service fields
        $fields = [];
        $encrypted_fields = ['cyberpanel_password'];
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
                $row->meta->admin_username,
                $row->meta->admin_password,
                $row->meta->use_ssl
            );

            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Suspend CyberPanel account
            $this->log(
                $row->meta->host_name . '|submitWebsiteStatus',
                serialize($service_fields->cyberpanel_domain),
                'input',
                true
            );
            $this->parseResponse($api->suspendAccount($service_fields->cyberpanel_domain));
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
                $row->meta->admin_username,
                $row->meta->admin_password,
                $row->meta->use_ssl
            );

            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Unsuspend CyberPanel account
            $this->log(
                $row->meta->host_name . '|submitWebsiteStatus',
                serialize($service_fields->cyberpanel_domain),
                'input',
                true
            );
            $this->parseResponse($api->unsuspendAccount($service_fields->cyberpanel_domain));
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
                $row->meta->admin_username,
                $row->meta->admin_password,
                $row->meta->use_ssl
            );

            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Delete CyberPanel account
            $this->log(
                $row->meta->host_name . '|deleteWebsite',
                serialize($service_fields->cyberpanel_domain),
                'input',
                true
            );
            $this->parseResponse($api->deleteAccount($service_fields->cyberpanel_domain));
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
                $row->meta->admin_username,
                $row->meta->admin_password,
                $row->meta->use_ssl
            );

            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Update the CyberPanel account
            $this->log(
                $row->meta->host_name . '|changePackageAPI',
                serialize([$service_fields->cyberpanel_domain, $package_to->meta->package]),
                'input',
                true
            );
            $this->parseResponse(
                $api->updateAccountPackage($service_fields->cyberpanel_domain, $package_to->meta->package)
            );
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'cyberpanel' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'cyberpanel' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $this->serviceFieldsToObject($service->fields));

        return $this->view->fetch();
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

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Perform the password reset
        if (!empty($post)) {
            if ($post['cyberpanel_password'] == $post['cyberpanel_confirm_password']) {
                Loader::loadModels($this, ['Services']);
                $data = [
                    'cyberpanel_password' => (isset($post['cyberpanel_password']) ? $post['cyberpanel_password'] : null)
                ];
                $this->Services->edit($service->id, $data);

                if ($this->Services->errors()) {
                    $this->Input->setErrors($this->Services->errors());
                }

                $vars = (object) $post;
            }
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('vars', (isset($vars) ? $vars : new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'cyberpanel' . DS);

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
     * Validates whether or not the connection details are valid by attempting to fetch
     * the number of accounts that currently reside on the server.
     *
     * @param string $admin_password The server admin password
     * @param string $hostname The server hostname
     * @param string $admin_username The server admin username
     * @param bool $use_ssl True to connect to the api using SSL
     * @return bool True if the connection is valid, false otherwise
     */
    public function validateConnection($admin_password, $hostname, $admin_username, $use_ssl)
    {
        try {
            $api = $this->getApi($hostname, $admin_username, $admin_password, $use_ssl);
            $response = $api->apiRequest('verifyConn');

            if (!empty($response)) {
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
            $api = $this->getApi($row->meta->host_name, $row->meta->admin_username, $row->meta->admin_password);
        }

        // Username exists, create another instead
        if ($this->parseResponse($api->getUserInfo($username))) {
            for ($i = 0; $i < (int) str_repeat(9, 4); $i++) {
                $new_username = substr($username, 0, -4) . $i;
                if (!$this->parseResponse($api->getUserInfo($new_username))) {
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
            'username' => isset($vars['cyberpanel_username']) ? $vars['cyberpanel_username'] : null,
            'password' => isset($vars['cyberpanel_password']) ? $vars['cyberpanel_password'] : null,
            'email' => isset($vars['cyberpanel_email']) ? $vars['cyberpanel_email'] : null,
            'domain' => isset($vars['cyberpanel_domain']) ? $vars['cyberpanel_domain'] : null,
            'package' => $package->meta->package
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
            $this->Input->setErrors(['api' => ['internal' => Language::_('Cyberpanel.!error.api.internal', true)]]);
            $success = false;
        } elseif ($response->error_message !== 'None') {
            // Only some API requests return status, so only use it if its available
            $this->Input->setErrors(['api' => ['result' => $response->error_message]]);
            $success = false;
        }

        // Log the response
        $this->log($row->meta->host_name, serialize($response), 'output', $success);

        // Return if any errors encountered
        if (!$success) {
            return;
        }

        return $response;
    }

    /**
     * Initializes the CyberpanelApi and returns an instance of that object.
     *
     * @param string $hostname The host to the CyberPanel server
     * @param string $admin_username The CyberPanel admin username
     * @param string $admin_password The CyberPanel admin password
     * @param bool $use_ssl True to connect to the api using SSL
     * @return CyberpanelApi The CyberpanelApi instance
     */
    private function getApi($hostname, $admin_username, $admin_password, $use_ssl = true)
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'cyberpanel_api.php');

        $api = new CyberpanelApi($hostname, $admin_username, $admin_password, $use_ssl);

        return $api;
    }

    /**
     * Removes the www. from a domain name.
     *
     * @param string $domain A domain name
     * @return string The domain name after the www. has been removed
     */
    private function formatDomain($domain)
    {
        return strtolower(preg_replace('/^\s*www\./i', '', $domain));
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
                    'message' => Language::_('Cyberpanel.!error.server_name_valid', true)
                ]
            ],
            'host_name' => [
                'valid' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Cyberpanel.!error.host_name_valid', true)
                ]
            ],
            'admin_username' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Cyberpanel.!error.remote_admin_username_valid', true)
                ]
            ],
            'admin_password' => [
                'valid' => [
                    'last' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Cyberpanel.!error.remote_admin_password_valid', true)
                ],
                'valid_connection' => [
                    'rule' => [
                        [$this, 'validateConnection'],
                        $vars['host_name'],
                        $vars['admin_username'],
                        $vars['use_ssl']
                    ],
                    'message' => Language::_('Cyberpanel.!error.remote_admin_password_valid_connection', true)
                ]
            ],
            'name_servers' => [
                'count' => [
                    'rule' => [[$this, 'validateNameServerCount']],
                    'message' => Language::_('Cyberpanel.!error.name_servers_count', true)
                ],
                'valid' => [
                    'rule' => [[$this, 'validateNameServers']],
                    'message' => Language::_('Cyberpanel.!error.name_servers_valid', true)
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
    private function getPackageRules($vars)
    {
        $rules = [
            'meta[package]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Cyberpanel.!error.meta[package].empty', true) // package must be given
                ]
            ]
        ];

        return $rules;
    }
}
