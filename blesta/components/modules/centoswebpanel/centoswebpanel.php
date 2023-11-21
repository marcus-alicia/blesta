<?php
use Blesta\Core\Util\Validate\Server;
/**
 * CentOS WebPanel Module.
 *
 * @package blesta
 * @subpackage blesta.components.modules.centoswebpanel
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Centoswebpanel extends Module
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
        Language::loadLang('centoswebpanel', null, dirname(__FILE__) . DS . 'language' . DS);
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
        if (version_compare($current_version, '2.0.0', '<')) {
            if (!isset($this->ModuleManager)) {
                Loader::loadModels($this, ['ModuleManager']);
            }

            // Update all module rows to have a port of 2304
            $modules = $this->ModuleManager->getByClass('centoswebpanel');
            foreach ($modules as $module) {
                $rows = $this->ModuleManager->getRows($module->id);
                foreach ($rows as $row) {
                    $meta = (array)$row->meta;
                    $meta['port'] = '2304';
                    $this->ModuleManager->editRow($row->id, $meta);
                }
            }
        }

        if (version_compare($current_version, '2.1.0', '<')) {
            if (!isset($this->ModuleManager)) {
                Loader::loadModels($this, ['ModuleManager']);
            }

            // Update all module rows to have a login port of 2031
            $modules = $this->ModuleManager->getByClass('centoswebpanel');
            foreach ($modules as $module) {
                $rows = $this->ModuleManager->getRows($module->id);
                foreach ($rows as $row) {
                    $meta = (array)$row->meta;
                    $meta['login_port'] = '2031';
                    $this->ModuleManager->editRow($row->id, $meta);
                }
            }
        }
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
            'roundrobin' => Language::_('Centoswebpanel.order_options.roundrobin', true),
            'first' => Language::_('Centoswebpanel.order_options.first', true)
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

        // Create package label
        $package = $fields->label(Language::_('Centoswebpanel.package_fields.package', true), 'centoswebpanel_package');
        // Create package field and attach to package label
        $package->attach(
            $fields->fieldText(
                'meta[package]',
                (isset($vars->meta['package']) ? $vars->meta['package'] : null),
                ['id' => 'centoswebpanel_package']
            )
        );
        // Set the label as a field
        $fields->setField($package);

        // Create inode label
        $inode = $fields->label(Language::_('Centoswebpanel.package_fields.inode', true), 'centoswebpanel_inode');
        // Create inode field and attach to inode label
        $inode->attach(
            $fields->fieldText(
                'meta[inode]',
                (isset($vars->meta['inode']) ? $vars->meta['inode'] : null),
                ['id' => 'centoswebpanel_inode']
            )
        );
        // Set the label as a field
        $fields->setField($inode);

        // Create nofile label
        $nofile = $fields->label(Language::_('Centoswebpanel.package_fields.nofile', true), 'centoswebpanel_nofile');
        // Create nofile field and attach to nofile label
        $nofile->attach(
            $fields->fieldText(
                'meta[nofile]',
                (isset($vars->meta['nofile']) ? $vars->meta['nofile'] : null),
                ['id' => 'centoswebpanel_nofile']
            )
        );
        // Set the label as a field
        $fields->setField($nofile);

        // Create nproc label
        $nproc = $fields->label(Language::_('Centoswebpanel.package_fields.nproc', true), 'centoswebpanel_nproc');
        // Create nproc field and attach to nproc label
        $nproc->attach(
            $fields->fieldText(
                'meta[nproc]',
                (isset($vars->meta['nproc']) ? $vars->meta['nproc'] : null),
                ['id' => 'centoswebpanel_nproc']
            )
        );
        // Set the label as a field
        $fields->setField($nproc);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'centoswebpanel' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'centoswebpanel' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'centoswebpanel' . DS);

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
        $meta_fields = ['server_name', 'host_name', 'login_port', 'port', 'api_key',
            'use_ssl', 'account_limit', 'name_servers', 'notes'];
        $encrypted_fields = ['api_key'];

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
        $meta_fields = ['server_name', 'host_name', 'login_port', 'port', 'api_key',
            'use_ssl', 'account_limit', 'account_count', 'name_servers', 'notes'];
        $encrypted_fields = ['api_key'];

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
     * Deletes the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being deleted.
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     */
    public function deleteModuleRow($module_row)
    {
        // Nothing to do
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
        $domain = $fields->label(Language::_('Centoswebpanel.service_field.domain', true), 'centoswebpanel_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText(
                'centoswebpanel_domain',
                (isset($vars->centoswebpanel_domain) ? $vars->centoswebpanel_domain : null),
                ['id' => 'centoswebpanel_domain']
            )
        );
        // Set the label as a field
        $fields->setField($domain);

        // Create username label
        $username = $fields->label(
            Language::_('Centoswebpanel.service_field.username', true),
            'centoswebpanel_username'
        );
        // Create username field and attach to username label
        $username->attach(
            $fields->fieldText(
                'centoswebpanel_username',
                (isset($vars->centoswebpanel_username) ? $vars->centoswebpanel_username : null),
                ['id' => 'centoswebpanel_username']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Centoswebpanel.service_field.tooltip.username', true));
        $username->attach($tooltip);
        // Set the label as a field
        $fields->setField($username);

        // Create password label
        $password = $fields->label(
            Language::_('Centoswebpanel.service_field.password', true),
            'centoswebpanel_password'
        );
        // Create password field and attach to password label
        $password->attach(
            $fields->fieldPassword(
                'centoswebpanel_password',
                ['id' => 'centoswebpanel_password', 'value' => (isset($vars->centoswebpanel_password) ? $vars->centoswebpanel_password : null)]
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Centoswebpanel.service_field.tooltip.password', true));
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
        $domain = $fields->label(Language::_('Centoswebpanel.service_field.domain', true), 'centoswebpanel_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText(
                'centoswebpanel_domain',
                (isset($vars->centoswebpanel_domain) ? $vars->centoswebpanel_domain : ($vars->domain ?? null)),
                ['id' => 'centoswebpanel_domain']
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
        $domain = $fields->label(Language::_('Centoswebpanel.service_field.domain', true), 'centoswebpanel_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText(
                'centoswebpanel_domain',
                (isset($vars->centoswebpanel_domain) ? $vars->centoswebpanel_domain : null),
                ['id' => 'centoswebpanel_domain']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Centoswebpanel.service_field.tooltip.domain_edit', true));
        $domain->attach($tooltip);
        // Set the label as a field
        $fields->setField($domain);

        // Create username label
        $username = $fields->label(
            Language::_('Centoswebpanel.service_field.username', true),
            'centoswebpanel_username'
        );
        // Create username field and attach to username label
        $username->attach(
            $fields->fieldText(
                'centoswebpanel_username',
                (isset($vars->centoswebpanel_username) ? $vars->centoswebpanel_username : null),
                ['id' => 'centoswebpanel_username']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Centoswebpanel.service_field.tooltip.username_edit', true));
        $username->attach($tooltip);
        // Set the label as a field
        $fields->setField($username);

        // Create password label
        $password = $fields->label(
            Language::_('Centoswebpanel.service_field.password', true),
            'centoswebpanel_password'
        );
        // Create password field and attach to password label
        $password->attach(
            $fields->fieldPassword(
                'centoswebpanel_password',
                ['id' => 'centoswebpanel_password', 'value' => (isset($vars->centoswebpanel_password) ? $vars->centoswebpanel_password : null)]
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
            'centoswebpanel_domain' => [
                'format' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Centoswebpanel.!error.centoswebpanel_domain.format', true)
                ]
            ],
            'centoswebpanel_username' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[a-z]([a-z0-9])*$/i'],
                    'message' => Language::_('Centoswebpanel.!error.centoswebpanel_username.format', true)
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['betweenLength', 1, 16],
                    'message' => Language::_('Centoswebpanel.!error.centoswebpanel_username.length', true)
                ]
            ],
            'centoswebpanel_password' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['isPassword', 8],
                    'message' => Language::_('Centoswebpanel.!error.centoswebpanel_password.valid', true),
                    'last' => true
                ],
            ]
        ];

        // Set the values that may be empty
        $empty_values = ['centoswebpanel_username', 'centoswebpanel_password'];

        if ($edit) {
            // If this is an edit and no password given then don't evaluate password
            // since it won't be updated
            if (!array_key_exists('centoswebpanel_password', $vars) || $vars['centoswebpanel_password'] == '') {
                unset($rules['centoswebpanel_password']);
            }
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
                ['module_row' => ['missing' => Language::_('Centoswebpanel.!error.module_row.missing', true)]]
            );

            return;
        }

        $api = $this->getApi($row->meta->host_name, $row->meta->port, $row->meta->api_key, $row->meta->use_ssl);

        // Generate username/password
        if (array_key_exists('centoswebpanel_domain', $vars)) {
            Loader::loadModels($this, ['Clients']);

            // Force domain to lower case
            $vars['centoswebpanel_domain'] = strtolower($vars['centoswebpanel_domain']);

            // Generate a username
            if (empty($vars['centoswebpanel_username'])) {
                $vars['centoswebpanel_username'] = $this->generateUsername($vars['centoswebpanel_domain']);
            }

            // Generate a password
            if (empty($vars['centoswebpanel_password'])) {
                $vars['centoswebpanel_password'] = $this->generatePassword();
            }

            // Get client's contact email address
            if (isset($vars['client_id']) && ($client = $this->Clients->get($vars['client_id'], false))) {
                $vars['centoswebpanel_email'] = $client->email;
            }
        }

        $params = $this->getFieldsFromInput((array) $vars, $package);
        $params['server_ips'] = $row->meta->host_name;

        $this->validateService($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            // Create CentOS WebPanel account
            $masked_params = $params;
            $masked_params['pass'] = '***';
            $this->log($row->meta->host_name . '|account_new', serialize($masked_params), 'input', true);
            unset($masked_params);

            $user_response = $api->createAccount($params);
            $errors = $user_response->errors();
            $success = $user_response->status() == 200 && empty($errors);
            $this->log($row->meta->host_name . '|account_new', $user_response->raw(), 'output', $success);

            if (!$success) {
                $this->Input->setErrors([
                    'account' => [
                        'account' => empty($errors) ? Language::_('Centoswebpanel.!error.api', true) : $errors
                    ]
                ]);
                return;
            }

            // Update the number of accounts on the server
            $this->updateAccountCount($row);
        }

        // Return service fields
        return [
            [
                'key' => 'centoswebpanel_domain',
                'value' => $vars['centoswebpanel_domain'],
                'encrypted' => 0
            ],
            [
                'key' => 'centoswebpanel_username',
                'value' => $vars['centoswebpanel_username'],
                'encrypted' => 0
            ],
            [
                'key' => 'centoswebpanel_password',
                'value' => $vars['centoswebpanel_password'],
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
        $api = $this->getApi($row->meta->host_name, $row->meta->port, $row->meta->api_key, $row->meta->use_ssl);

        $params = $this->getFieldsFromInput((array) $vars, $package, true);
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $params['server_ips'] = $row->meta->host_name;

        // Default fields using service fields
        if (!isset($params['domain'])) {
            $params['domain'] = $service_fields->centoswebpanel_domain;
        }

        if (!isset($params['pass'])) {
            $params['pass'] = $service_fields->centoswebpanel_password;
        }

        if (!isset($params['user'])) {
            $params['user'] = $service_fields->centoswebpanel_username;
        }

        $this->validateService($package, $vars, true);

        if ($this->Input->errors()) {
            return;
        }

        if (isset($params['domain'])) {
            // Force domain to lower case
            $params['domain'] = strtolower($params['domain']);
        }

        // Remove password if not being updated
        if (isset($params['pass']) && $params['pass'] == '') {
            unset($params['pass']);
        }

        // Only update the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            // Update CentOS WebPanel account
            $masked_params = $params;
            $masked_params['pass'] = '***';
            $host_name = $row->meta->host_name;

            // Attempt account edit (this seems to have no effect)
            $this->log($host_name . '|account_edit', serialize($masked_params), 'input', true);
            $user_response = $api->updateAccount($params);
            $user_errors = $user_response->errors();
            $user_success = $user_response->status() == 200 && empty($user_errors);
            $this->log($host_name . '|account_edit', $user_response->raw(), 'output', $user_success);

            if (!$user_success) {
                $this->Input->setErrors([
                    'account' => [
                        'account' => empty($user_errors) ? Language::_('Centoswebpanel.!error.api', true) : $user_errors
                    ]
                ]);
                return;
            }

            // Attempt account password change
            $password_params = ['user' => $params['user'], 'pass' => $params['pass']];
            $this->log($host_name . '|account_changepass', serialize($password_params), 'input', true);
            $password_response = $api->updatePassword($password_params);
            $password_errors = $password_response->errors();
            $password_success = $password_response->status() == 200 && empty($password_errors);
            $this->log($host_name . '|account_changepass', $password_response->raw(), 'output', $password_success);

            // Attempt account package change (this seems to have no effect)
            $package_params = ['user' => $params['user'], 'package' => $params['package']];
            $this->log($host_name . '|account_changepack', serialize($package_params), 'input', true);
            $package_response = $api->updatePackage($package_params);
            $package_errors = $package_response->errors();
            $package_success = $package_response->status() == 200 && empty($package_errors);
            $this->log($host_name . '|account_changepack', $package_response->raw(), 'output', $package_success);
        }

        // Set fields to update locally
        $field_mappings = [
            'domain' => 'centoswebpanel_domain',
            'user' => 'centoswebpanel_username',
            'pass' => 'centoswebpanel_password'
        ];
        foreach ($field_mappings as $field => $field_mapping) {
            if (property_exists($service_fields, $field_mapping) && isset($params[$field])) {
                $service_fields->{$field_mapping} = $params[$field];
            }
        }

        // Return all the service fields
        $fields = [];
        $encrypted_fields = ['centoswebpanel_password'];
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
            $api = $this->getApi($row->meta->host_name, $row->meta->port, $row->meta->api_key, $row->meta->use_ssl);

            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Suspend CentOS WebPanel account
            $this->log(
                $row->meta->host_name . '|account_suspend',
                serialize($service_fields->centoswebpanel_username),
                'input',
                true
            );

            $user_response = $api->suspendAccount($service_fields->centoswebpanel_username);
            $errors = $user_response->errors();
            $success = $user_response->status() == 200 && empty($errors);
            $this->log($row->meta->host_name . '|account_suspend', $user_response->raw(), 'output', $success);

            if (!$success) {
                $this->Input->setErrors([
                    'account' => [
                        'account' => empty($errors) ? Language::_('Centoswebpanel.!error.api', true) : $errors
                    ]
                ]);
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
            $api = $this->getApi($row->meta->host_name, $row->meta->port, $row->meta->api_key, $row->meta->use_ssl);

            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Unsuspend CentOS WebPanel account
            $this->log(
                $row->meta->host_name . '|account_unsuspend',
                serialize($service_fields->centoswebpanel_username),
                'input',
                true
            );

            $user_response = $api->unsuspendAccount($service_fields->centoswebpanel_username);
            $errors = $user_response->errors();
            $success = $user_response->status() == 200 && empty($errors);
            $this->log($row->meta->host_name . '|account_unsuspend', $user_response->raw(), 'output', $success);

            if (!$success) {
                $this->Input->setErrors([
                    'account' => [
                        'account' => empty($errors) ? Language::_('Centoswebpanel.!error.api', true) : $errors
                    ]
                ]);
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
        Loader::loadModels($this, ['Clients']);
        if (($row = $this->getModuleRow())) {
            $client = $this->Clients->get($service->client_id);
            $api = $this->getApi($row->meta->host_name, $row->meta->port, $row->meta->api_key, $row->meta->use_ssl);

            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Delete CentOS WebPanel account
            $this->log(
                $row->meta->host_name . '|account_remove',
                serialize($service_fields->centoswebpanel_username),
                'input',
                true
            );
            $user_response = $api->removeAccount($service_fields->centoswebpanel_username, $client->email);
            $errors = $user_response->errors();
            $success = $user_response->status() == 200 && empty($errors);
            $this->log($row->meta->host_name . '|account_remove', $user_response->raw(), 'output', $success);

            if (!$success) {
                $this->Input->setErrors([
                    'account' => [
                        'account' => empty($errors) ? Language::_('Centoswebpanel.!error.api', true) : $errors
                    ]
                ]);
            } else {
                // Update the number of accounts on the server
                $this->updateAccountCount($row, false);
            }
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
        // Nothing to do
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'centoswebpanel' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'centoswebpanel' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $this->serviceFieldsToObject($service->fields));

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
     * @param mixed $api_key
     * @param mixed $hostname
     * @param int port
     * @param mixed $use_ssl
     * @return bool True if the connection is valid, false otherwise
     */
    public function validateConnection($api_key, $hostname, $port, $use_ssl)
    {
        try {
            $api = $this->getApi($hostname, $port, $api_key, $use_ssl);
            $this->log(
                $hostname . '|validate_connection/packages_get',
                serialize(['hostname' => $hostname, 'port' => $port, 'api_key' => $api_key, 'use_ssl' => $use_ssl]),
                'input',
                true
            );
            $response = $api->getPackages();

            $errors = $response->errors();
            $success = $response->status() == 200 && empty($errors);
            $this->log($hostname . '|validate_connection/packages_get', $response->raw(), 'output', $success);
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
            $api = $this->getApi($row->meta->host_name, $row->meta->port, $row->meta->api_key, $row->meta->use_ssl);

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
            'domain' => isset($vars['centoswebpanel_domain']) ? $vars['centoswebpanel_domain'] : null,
            'user' => isset($vars['centoswebpanel_username']) ? $vars['centoswebpanel_username'] : null,
            'pass' => isset($vars['centoswebpanel_password']) ? $vars['centoswebpanel_password'] : null,
            'email' => isset($vars['centoswebpanel_email']) ? $vars['centoswebpanel_email'] : null,
            'package' => $package->meta->package,
            'inode' => $package->meta->inode,
            'limit_nofile' => $package->meta->nofile,
            'limit_nproc' => $package->meta->nproc,
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
            $this->Input->setErrors(['api' => ['internal' => Language::_('Centoswebpanel.!error.api.internal', true)]]);
            $success = false;
        }

        // Only some API requests return status, so only use it if its available
        if (!$response['success']) {
            $this->Input->setErrors(['api' => ['result' => $response['message']]]);
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
     * Initializes the CentoswebpanelApi and returns an instance of that object.
     *
     * @param string $hostname The host to the CentOS WebPanel server
     * @param int $port The port on which to connect to the API
     * @param string $api_key The remote api key
     * @param mixed $use_ssl
     * @return CentoswebpanelApi The CentoswebpanelApi instance
     */
    private function getApi($hostname, $port, $api_key, $use_ssl = 'true')
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'centoswebpanel_api.php');

        $api = new CentoswebpanelApi($hostname, $port, $api_key, $use_ssl === 'true');

        return $api;
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
                    'message' => Language::_('Centoswebpanel.!error.server_name_valid', true)
                ]
            ],
            'host_name' => [
                'valid' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Centoswebpanel.!error.host_name_valid', true)
                ]
            ],
            'login_port' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Centoswebpanel.!error.login_port_valid', true)
                ]
            ],
            'port' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Centoswebpanel.!error.port_valid', true)
                ]
            ],
            'api_key' => [
                'valid' => [
                    'last' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Centoswebpanel.!error.remote_api_key_valid', true)
                ],
                'valid_connection' => [
                    'rule' => [
                        [$this, 'validateConnection'],
                        isset($vars['host_name']) ? $vars['host_name'] : '',
                        isset($vars['port']) ? $vars['port'] : '',
                        isset($vars['use_ssl']) ? $vars['use_ssl'] : true,
                    ],
                    'message' => Language::_('Centoswebpanel.!error.remote_api_key_valid_connection', true)
                ]
            ],
            'account_limit' => [
                'valid' => [
                    'rule' => ['matches', '/^([0-9]+)?$/'],
                    'message' => Language::_('Centoswebpanel.!error.account_limit_valid', true)
                ]
            ],
            'name_servers' => [
                'count' => [
                    'rule' => [[$this, 'validateNameServerCount']],
                    'message' => Language::_('Centoswebpanel.!error.name_servers_count', true)
                ],
                'valid' => [
                    'rule' => [[$this, 'validateNameServers']],
                    'message' => Language::_('Centoswebpanel.!error.name_servers_valid', true)
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
                    'message' => Language::_('Centoswebpanel.!error.meta[package].empty', true) // package must be given
                ]
            ],
            'meta[inode]' => [
                'valid' => [
                    'rule' => ['matches', '/^([0-9]+)$/'],
                    'message' => Language::_('Centoswebpanel.!error.meta[inode].valid', true),
                ]
            ],
            'meta[nofile]' => [
                'valid' => [
                    'rule' => ['matches', '/^([0-9]+)$/'],
                    'message' => Language::_('Centoswebpanel.!error.meta[nofile].valid', true),
                ]
            ],
            'meta[nproc]' => [
                'valid' => [
                    'rule' => ['matches', '/^([0-9]+)$/'],
                    'message' => Language::_('Centoswebpanel.!error.meta[nproc].valid', true),
                ]
            ]
        ];

        return $rules;
    }
}
