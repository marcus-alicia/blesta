<?php
use Blesta\Core\Util\Validate\Server;
/**
 * Ispconfig Module.
 *
 * @package blesta
 * @subpackage blesta.components.modules.ispconfig
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Ispconfig extends Module
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
        Language::loadLang('ispconfig', null, dirname(__FILE__) . DS . 'language' . DS);
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
        if (version_compare($current_version, '1.3.0', '<')) {
            if (!isset($this->ModuleManager)) {
                Loader::loadModels($this, ['ModuleManager']);
            }

            // Update all module rows to have a port of 8080
            $modules = $this->ModuleManager->getByClass('ispconfig');
            foreach ($modules as $module) {
                $rows = $this->ModuleManager->getRows($module->id);
                foreach ($rows as $row) {
                    $meta = (array)$row->meta;
                    $meta['port'] = '8080';
                    $this->ModuleManager->editRow($row->id, $meta);
                }
            }
        }
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
            'tabStats' => Language::_('Ispconfig.tab_stats', true)
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
            'tabClientActions' => Language::_('Ispconfig.tab_client_actions', true),
            'tabClientStats' => Language::_('Ispconfig.tab_client_stats', true)
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
            'roundrobin' => Language::_('Ispconfig.order_options.roundrobin', true),
            'first' => Language::_('Ispconfig.order_options.first', true)
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
        $fields->setHtml("
			<script type=\"text/javascript\">
				$(document).ready(function() {
					// Set whether to show or hide the php and ssh options
					if ($('#ispconfig_package').val() !== '0') {
                            $('.ispconfig_option').hide();
                            $('.ispconfig_option_label').hide();
                    }

					$('#ispconfig_package').change(function() {
						if ($(this).val() === '0') {
                            $('.ispconfig_option').show();
                            $('.ispconfig_option_label').show();
						} else {
                            $('.ispconfig_option').hide();
                            $('.ispconfig_option_label').hide();
                        }
					});
				});
			</script>
		");

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

        // Get the ISPConfig packages and options
        $packages = [];
        $php_options = [];
        $ssh_options = [];
        if ($module_row) {
            $packages = $this->getIspconfigPackages($module_row);
            $php_options = $this->getIspconfigPhpOptions($module_row);
            $ssh_options = $this->getIspconfigSshOptions($module_row);
        }

        // Set the ISPConfig package as a selectable option
        $package = $fields->label(Language::_('Ispconfig.package_fields.package', true), 'ispconfig_package');
        $package->attach(
            $fields->fieldSelect(
                'meta[package]',
                $packages,
                (isset($vars->meta['package']) ? $vars->meta['package'] : null),
                ['id' => 'ispconfig_package']
            )
        );
        $fields->setField($package);

        // Set the PHP options as a multiple selectable option
        if (!empty($php_options)) {
            $php = $fields->label(
                Language::_('Ispconfig.package_fields.php_options', true),
                'ispconfig_php_options',
                ['class' => 'ispconfig_option_label']
            );

            foreach ($php_options as $key => $value) {
                $php->attach(
                    $fields->fieldCheckbox(
                        'meta[php_options][' . $key . ']',
                        $key,
                        (isset($vars->meta['php_options'][$key]) ? $vars->meta['php_options'][$key] : null),
                        ['class' => 'ispconfig_option'],
                        $fields->label($value, 'meta[php_options][' . $key . ']', ['class' => 'ispconfig_option_label'])
                    )
                );
            }

            $fields->setField($php);
        }

        // Set the SSH options as a multiple selectable option
        if (!empty($ssh_options)) {
            $ssh = $fields->label(
                Language::_('Ispconfig.package_fields.ssh_options', true),
                'ispconfig_ssh_options',
                ['class' => 'ispconfig_option_label']
            );

            foreach ($ssh_options as $key => $value) {
                $ssh->attach(
                    $fields->fieldCheckbox(
                        'meta[ssh_options][' . $key . ']',
                        $key,
                        (isset($vars->meta['ssh_options'][$key]) ? $vars->meta['ssh_options'][$key] : null),
                        ['class' => 'ispconfig_option'],
                        $fields->label($value, 'meta[ssh_options][' . $key . ']', ['class' => 'ispconfig_option_label'])
                    )
                );
            }

            $fields->setField($ssh);
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ispconfig' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ispconfig' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ispconfig' . DS);

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
        $meta_fields = ['server_name', 'host_name', 'port', 'user_name', 'password',
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
        $meta_fields = ['server_name', 'host_name', 'port', 'user_name', 'password',
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
     * @return ModuleFields A ModuleFields object, containing the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getAdminAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Create domain label
        $domain = $fields->label(Language::_('Ispconfig.service_field.domain', true), 'ispconfig_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText(
                'ispconfig_domain',
                (isset($vars->ispconfig_domain) ? $vars->ispconfig_domain : null),
                ['id' => 'ispconfig_domain']
            )
        );
        // Set the label as a field
        $fields->setField($domain);

        // Create username label
        $username = $fields->label(Language::_('Ispconfig.service_field.username', true), 'ispconfig_username');
        // Create username field and attach to username label
        $username->attach(
            $fields->fieldText(
                'ispconfig_username',
                (isset($vars->ispconfig_username) ? $vars->ispconfig_username : null),
                ['id' => 'ispconfig_username']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Ispconfig.service_field.tooltip.username', true));
        $username->attach($tooltip);
        // Set the label as a field
        $fields->setField($username);

        // Create password label
        $password = $fields->label(Language::_('Ispconfig.service_field.password', true), 'ispconfig_password');
        // Create password field and attach to password label
        $password->attach(
            $fields->fieldPassword(
                'ispconfig_password',
                ['id' => 'ispconfig_password', 'value' => (isset($vars->ispconfig_password) ? $vars->ispconfig_password : null)]
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Ispconfig.service_field.tooltip.password', true));
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
        $domain = $fields->label(Language::_('Ispconfig.service_field.domain', true), 'ispconfig_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText(
                'ispconfig_domain',
                (isset($vars->ispconfig_domain) ? $vars->ispconfig_domain : ($vars->domain ?? null)),
                ['id' => 'ispconfig_domain']
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

        // The module does not currently support updating the username or domain though it should.  For now these
        // fields should be commented out
        /*
        // Create domain label
        $domain = $fields->label(Language::_('Ispconfig.service_field.domain', true), 'ispconfig_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText(
                'ispconfig_domain',
                (isset($vars->ispconfig_domain) ? $vars->ispconfig_domain : null),
                ['id' => 'ispconfig_domain']
            )
        );
        // Set the label as a field
        $fields->setField($domain);

        // Create username label
        $username = $fields->label(Language::_('Ispconfig.service_field.username', true), 'ispconfig_username');
        // Create username field and attach to username label
        $username->attach(
            $fields->fieldText(
                'ispconfig_username',
                (isset($vars->ispconfig_username) ? $vars->ispconfig_username : null),
                ['id' => 'ispconfig_username']
            )
        );
        // Set the label as a field
        $fields->setField($username);*/

        // Create password label
        $password = $fields->label(Language::_('Ispconfig.service_field.password', true), 'ispconfig_password');
        // Create password field and attach to password label
        $password->attach(
            $fields->fieldPassword(
                'ispconfig_password',
                ['id' => 'ispconfig_password', 'value' => (isset($vars->ispconfig_password) ? $vars->ispconfig_password : null)]
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
     * Returns the rule set for adding/editing a service
     *
     * @param array $vars A list of input vars
     * @param bool $edit True to get the edit rules, false for the add rules
     * @return array Service rules
     */
    private function getServiceRules(array $vars = null, $edit = false)
    {
        $rules = [
            'ispconfig_domain' => [
                'format' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Ispconfig.!error.ispconfig_domain.format', true)
                ]
            ],
            'ispconfig_username' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[a-z]([a-z0-9])*$/i'],
                    'message' => Language::_('Ispconfig.!error.ispconfig_username.format', true)
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['betweenLength', 1, 16],
                    'message' => Language::_('Ispconfig.!error.ispconfig_username.length', true)
                ]
            ],
            'ispconfig_password' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['isPassword', 8],
                    'message' => Language::_('Ispconfig.!error.ispconfig_password.valid', true),
                    'last' => true
                ],
            ]
        ];

        // Set the values that may be empty
        $empty_values = ['ispconfig_username', 'ispconfig_password'];

        if ($edit) {
            // If this is an edit and no password given then don't evaluate password
            // since it won't be updated
            if (!array_key_exists('ispconfig_password', $vars) || $vars['ispconfig_password'] == '') {
                unset($rules['ispconfig_password']);
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
                ['module_row' => ['missing' => Language::_('Ispconfig.!error.module_row.missing', true)]]
            );

            return;
        }

        $api = $this->getApi(
            $row->meta->host_name,
            $row->meta->user_name,
            $row->meta->password,
            $row->meta->use_ssl,
            $row->meta->port
        );

        // Generate username/password
        if (array_key_exists('ispconfig_domain', $vars)) {
            Loader::loadModels($this, ['Clients']);

            // Force domain to lower case
            $vars['ispconfig_domain'] = strtolower($vars['ispconfig_domain']);

            // Generate a username
            if (empty($vars['ispconfig_username'])) {
                $vars['ispconfig_username'] = $this->generateUsername($vars['ispconfig_domain']);
            }

            // Generate a password
            if (empty($vars['ispconfig_password'])) {
                $vars['ispconfig_password'] = $this->generatePassword();
            }

            // Get client's contact information
            if (isset($vars['client_id']) && ($client = $this->Clients->get($vars['client_id'], false))) {
                $vars['ispconfig_name'] = $client->first_name . ' ' . $client->last_name;
                $vars['ispconfig_company'] = empty($client->company) ? 'NA' : $client->company;
                $vars['ispconfig_email'] = $client->email;
                $vars['ispconfig_address'] = $client->address1;
                $vars['ispconfig_city'] = $client->city;
                $vars['ispconfig_state'] = $client->state;
                $vars['ispconfig_zip'] = $client->zip;
                $vars['ispconfig_country'] = $client->country;
            }
        }

        $params = $this->getFieldsFromInput((array) $vars, $package);

        $this->validateService($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            // Create ISPConfig client
            $masked_params = $params;
            $masked_params['password'] = '***';
            $this->log($row->meta->host_name . '|client_add', serialize($masked_params), 'input', true);
            unset($masked_params);
            $client_id = $this->parseResponse($api->createClient($params));

            // Create website in the ISPConfig client
            $this->log($row->meta->host_name . '|sites_web_domain_add', null, 'input', true);
            $this->parseResponse($api->addSite($client_id, $vars['ispconfig_domain']));

            if ($this->Input->errors()) {
                return;
            }

            // Update the number of accounts on the server
            $this->updateAccountCount($row);
        }

        // Return service fields
        return [
            [
                'key' => 'ispconfig_domain',
                'value' => $vars['ispconfig_domain'],
                'encrypted' => 0
            ],
            [
                'key' => 'ispconfig_username',
                'value' => $vars['ispconfig_username'],
                'encrypted' => 0
            ],
            [
                'key' => 'ispconfig_password',
                'value' => $vars['ispconfig_password'],
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
            $row->meta->user_name,
            $row->meta->password,
            $row->meta->use_ssl,
            $row->meta->port
        );

        $this->validateServiceEdit($service, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Force domain to lower case
        $vars['ispconfig_domain'] = strtolower($vars['ispconfig_domain']);

        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Remove password if not being updated
        if (isset($vars['ispconfig_password']) && $vars['ispconfig_password'] == '') {
            unset($vars['ispconfig_password']);
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

            // Get ISPConfig client ID
            $client_id = $api->getClientIdByUsername($service_fields->ispconfig_username);

            // Update password (if changed)
            if (isset($delta['ispconfig_password'])) {
                $this->log($row->meta->host_name . '|client_update', '***', 'input', true);
                $this->parseResponse($api->updateClientPassword($client_id, $delta['ispconfig_password']));
            }
        }

        // Set fields to update locally
        $fields = ['ispconfig_password'];
        foreach ($fields as $field) {
            if (property_exists($service_fields, $field) && isset($vars[$field])) {
                $service_fields->{$field} = $vars[$field];
            }
        }

        // Return all the service fields
        $fields = [];
        $encrypted_fields = ['ispconfig_password'];
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
        // Nothing to do
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
        // Nothing to do
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
                $row->meta->use_ssl,
                $row->meta->port
            );

            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Get ISPConfig client ID
            $client_id = $api->getClientIdByUsername($service_fields->ispconfig_username);

            // Delete ISPConfig client account
            $this->log(
                $row->meta->host_name . '|client_delete_everything',
                serialize($service_fields->ispconfig_username),
                'input',
                true
            );
            $this->parseResponse($api->deleteClient($client_id));

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
                $row->meta->user_name,
                $row->meta->password,
                $row->meta->use_ssl,
                $row->meta->port
            );

            // Only request a package change if it has changed
            if ($package_from->meta->package != $package_to->meta->package) {
                $service_fields = $this->serviceFieldsToObject($service->fields);

                // Get ISPConfig client ID
                $client_id = $api->getClientIdByUsername($service_fields->ispconfig_username);

                // Change service package
                $this->log(
                    $row->meta->host_name . '|client_update',
                    serialize([$service_fields->ispconfig_username,
                    $package_to->meta->package]),
                    'input',
                    true
                );
                $this->parseResponse(
                    $api->updateClient(
                        $client_id,
                        [
                            'template_master' => $package_to->meta->package,
                            'password' => $service_fields->ispconfig_password
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ispconfig' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ispconfig' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $this->serviceFieldsToObject($service->fields));

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

        $stats = $this->getStats($package, $service);

        $this->view->set('stats', $stats);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ispconfig' . DS);

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

        $stats = $this->getStats($package, $service);

        $this->view->set('stats', $stats);
        $this->view->set('user_type', $package->meta->type);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ispconfig' . DS);

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
            $row->meta->user_name,
            $row->meta->password,
            $row->meta->use_ssl,
            $row->meta->port
        );

        $stats = new stdClass();
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Get ISPConfig client ID
        $client_id = $api->getClientIdByUsername($service_fields->ispconfig_username);

        // Fetch account info
        $this->log(
            $row->meta->host_name . '|client_get',
            serialize($service_fields->ispconfig_username),
            'input',
            true
        );
        $stats->account_info = $this->parseResponse($api->getClient($client_id));

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
                'ispconfig_password' => (isset($post['ispconfig_password']) ? $post['ispconfig_password'] : null)
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

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ispconfig' . DS);

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
     * Retrieves the accounts on the server.
     *
     * @param stdClass $api The ISPConfig API
     * @return mixed The number of ISPConfig accounts on the server, or false on error
     */
    private function getAccountCount($api)
    {
        $accounts = false;

        try {
            $output = $api->getAllClients();

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
            $module_row->meta->user_name,
            $module_row->meta->password,
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
     * @param mixed $password
     * @param mixed $hostname
     * @param mixed $username
     * @param mixed $use_ssl
     * @param mixed $port
     * @return bool True if the connection is valid, false otherwise
     */
    public function validateConnection($password, $hostname, $username, $use_ssl, &$account_count, $port = '8080')
    {
        try {
            $api = $this->getApi($hostname, $username, $password, $use_ssl, $port);

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
                $row->meta->user_name,
                $row->meta->password,
                $row->meta->use_ssl,
                $row->meta->port
            );
        }

        $account_matching_characters = 3;
        // Username exists, create another instead
        if ((bool) $api->getClientIdByUsername($username)) {
            for ($i = 0; $i < (int) str_repeat(9, $account_matching_characters); $i++) {
                $new_username = substr($username, 0, -$account_matching_characters) . $i;
                if (!(bool) $api->getClientIdByUsername($new_username)) {
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
            'contact_name' => isset($vars['ispconfig_name']) ? $vars['ispconfig_name'] : null,
            'username' => isset($vars['ispconfig_username']) ? $vars['ispconfig_username'] : null,
            'password' => isset($vars['ispconfig_password']) ? $vars['ispconfig_password'] : null,
            'email' => isset($vars['ispconfig_email']) ? $vars['ispconfig_email'] : null,
            'company_name' => isset($vars['ispconfig_company']) ? $vars['ispconfig_company'] : null,
            'street' => isset($vars['ispconfig_address']) ? $vars['ispconfig_address'] : null,
            'city' => isset($vars['ispconfig_city']) ? $vars['ispconfig_city'] : null,
            'zip' => isset($vars['ispconfig_zip']) ? $vars['ispconfig_zip'] : null,
            'state' => isset($vars['ispconfig_state']) ? $vars['ispconfig_state'] : null,
            'country' => isset($vars['ispconfig_country']) ? $vars['ispconfig_country'] : null,
            'template_master' => $package->meta->package,
            'web_php_options' => implode(',', $package->meta->php_options),
            'ssh_chroot' => implode(',', $package->meta->ssh_options)
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
            $this->Input->setErrors(['api' => ['internal' => Language::_('Ispconfig.!error.api.internal', true)]]);
            $success = false;
        }

        // Only some API requests return status, so only use it if its available
        if (isset($response['error'])) {
            $this->Input->setErrors(['api' => ['result' => $response['error']]]);
            $success = false;
        }

        // Log the response
        $this->log(
            $row->meta->host_name,
            is_array($response) ? serialize($response) : (string) $response,
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
     * Initializes the IspconfigApi and returns an instance of that object.
     *
     * @param string $hostname The host to the ISPConfig server
     * @param string $username The remote username
     * @param string $password The remote password
     * @param mixed $use_ssl
     * @return IspconfigApi The IspconfigApi instance
     */
    private function getApi($hostname, $username, $password, $use_ssl = true, $port = '8080')
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'ispconfig_api.php');

        $api = new IspconfigApi($hostname, $username, $password, $use_ssl, $port);

        return $api;
    }

    /**
     * Fetches a listing of all packages configured in ISPConfig for the given server.
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @return array An array of packages in key/value pair
     */
    private function getIspconfigPackages($module_row)
    {
        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->user_name,
            $module_row->meta->password,
            $module_row->meta->use_ssl,
            $module_row->meta->port
        );
        $packages = [];

        try {
            $this->log($module_row->meta->host_name . '|client_templates_get_all', null, 'input', true);
            $packages = $api->getAllLimitsTemplates();

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
     * Fetches a listing of all php options available in ISPConfig for the given server.
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @return array An array of packages in key/value pair
     */
    private function getIspconfigPhpOptions($module_row)
    {
        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->user_name,
            $module_row->meta->password,
            $module_row->meta->use_ssl,
            $module_row->meta->port
        );
        $options = [];

        try {
            $this->log($module_row->meta->host_name . '|get_php_options', null, 'input', true);
            $options = $api->getPhpOptions();

            $success = false;

            if (!empty($options)) {
                $success = true;
            }

            $this->log($module_row->meta->host_name, serialize($options), 'output', $success);
        } catch (Exception $e) {
            // API request failed
        }

        return $options;
    }

    /**
     * Fetches a listing of all ssh options available in ISPConfig for the given server.
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @return array An array of packages in key/value pair
     */
    private function getIspconfigSshOptions($module_row)
    {
        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->user_name,
            $module_row->meta->password,
            $module_row->meta->use_ssl,
            $module_row->meta->port
        );
        $options = [];

        try {
            $this->log($module_row->meta->host_name . '|get_ssh_options', null, 'input', true);
            $options = $api->getSshOptions();

            $success = false;

            if (!empty($options)) {
                $success = true;
            }

            $this->log($module_row->meta->host_name, serialize($options), 'output', $success);
        } catch (Exception $e) {
            // API request failed
        }

        return $options;
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
                    'message' => Language::_('Ispconfig.!error.server_name_valid', true)
                ]
            ],
            'host_name' => [
                'valid' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Ispconfig.!error.host_name_valid', true)
                ]
            ],
            'port' => [
                'format' => [
                    'rule' => 'is_numeric',
                    'message' => Language::_('Ispconfig.!error.port_format', true)
                ]
            ],
            'user_name' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Ispconfig.!error.user_name_valid', true)
                ]
            ],
            'password' => [
                'valid' => [
                    'last' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Ispconfig.!error.remote_password_valid', true)
                ],
                'valid_connection' => [
                    'rule' => [
                        [$this, 'validateConnection'],
                        $vars['host_name'],
                        $vars['user_name'],
                        $vars['use_ssl'],
                        &$vars['account_count'],
                        $vars['port']
                    ],
                    'message' => Language::_('Ispconfig.!error.remote_password_valid_connection', true)
                ]
            ],
            'account_limit' => [
                'valid' => [
                    'rule' => ['matches', '/^([0-9]+)?$/'],
                    'message' => Language::_('Ispconfig.!error.account_limit_valid', true)
                ]
            ],
            'name_servers' => [
                'count' => [
                    'rule' => [[$this, 'validateNameServerCount']],
                    'message' => Language::_('Ispconfig.!error.name_servers_count', true)
                ],
                'valid' => [
                    'rule' => [[$this, 'validateNameServers']],
                    'message' => Language::_('Ispconfig.!error.name_servers_valid', true)
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
                    'message' => Language::_('Ispconfig.!error.meta[package].empty', true) // package must be given
                ]
            ]
        ];

        return $rules;
    }
}
