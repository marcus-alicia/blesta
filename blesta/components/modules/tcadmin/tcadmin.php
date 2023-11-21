<?php
use Blesta\Core\Util\Validate\Server;

/**
 * Tcadmin Module
 *
 * @package blesta
 * @subpackage blesta.components.modules.tcadmin
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Tcadmin extends Module
{
    /**
     * Initializes the module
     */
    public function __construct()
    {
        // Load configuration required by this module
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this module
        Loader::loadComponents($this, ['Input', 'Net']);
        $this->Http = $this->Net->create('Http');

        // Load the language required by this module
        Language::loadLang('tcadmin', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * Returns all tabs to display to an admin when managing a service whose
     * package uses this module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title.
     *  Example: ['methodName' => 'Title', 'methodName2' => 'Title2']
     */
    public function getAdminTabs($package)
    {
        return [];
    }

    /**
     * Returns all tabs to display to a client when managing a service whose
     * package uses this module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title.
     *  Example: ['methodName' => 'Title', 'methodName2' => 'Title2']
     */
    public function getClientTabs($package)
    {
        return [];
    }

    /**
     * Returns an array of available service deligation order methods. The module
     * will determine how each method is defined. For example, the method 'first'
     * may be implemented such that it returns the module row with the least number
     * of services assigned to it.
     *
     * @return array An array of order methods in key/value paris where the key is the type to be stored for
     *  the group and value is the name for that option
     * @see Module::selectModuleRow()
     */
    public function getGroupOrderOptions()
    {
        return ['first' => Language::_('Tcadmin.order_options.first', true)];
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

        if (!empty($group->rows)) {
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
     * @return ModuleFields A ModuleFields object, containing the fields to render as well as any additional
     *  HTML markup to include
     */
    public function getPackageFields($vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Fetch all packages available for the given server or server group
        $module_row = null;
        if (isset($vars->module_group)) {
            if ($vars->module_group == '') {
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
        }

        $configured_servers = [];

        if ($module_row) {
            $configured_servers = $this->getConfiguredServers(
                $module_row,
                (isset($vars->meta['server_type']) ? $vars->meta['server_type'] : 'game')
            );
        }

        $server_type = $fields->label(Language::_('Tcadmin.package_fields.server_type', true), 'server_type');
        $server_type->attach(
            $fields->fieldSelect(
                'meta[server_type]',
                [
                    'game' => Language::_('Tcadmin.package_fields.game_server', true),
                    'voice' => Language::_('Tcadmin.package_fields.voice_server', true),
                ],
                (isset($vars->meta['server_type']) ? $vars->meta['server_type'] : null),
                ['id' => 'server_type', 'onChange' => 'fetchModuleOptions()']
            )
        );
        $fields->setField($server_type);

        $supported_servers = $fields->label(
            Language::_('Tcadmin.package_fields.supported_servers', true),
            'supported_servers'
        );
        $supported_servers->attach(
            $fields->fieldSelect(
                'meta[supported_servers]',
                $configured_servers,
                (isset($vars->meta['supported_servers']) ? $vars->meta['supported_servers'] : null),
                ['id' => 'supported_servers']
            )
        );
        $fields->setField($supported_servers);

        $start = $fields->label(Language::_('Tcadmin.package_fields.start', true), 'start');
        $start->attach(
            $fields->fieldSelect(
                'meta[start]', [
                    '1' => Language::_('Tcadmin.package_fields.yes', true),
                    '0' => Language::_('Tcadmin.package_fields.no', true),
                ],
                (isset($vars->meta['start']) ? $vars->meta['start'] : null),
                ['id' => 'start']
            )
        );
        $fields->setField($start);


        $priority = $fields->label(Language::_('Tcadmin.package_fields.priority', true), 'priority');
        $priority->attach(
            $fields->fieldSelect(
                'meta[priority]',
                [
                    'Normal' => Language::_('Tcadmin.package_fields.priority.normal', true),
                    'AboveNormal' => Language::_('Tcadmin.package_fields.priority.abovenormal', true),
                    'BelowNormal' => Language::_('Tcadmin.package_fields.priority.belownormal', true),
                    'High' => Language::_('Tcadmin.package_fields.priority.high', true),
                    'Idle' => Language::_('Tcadmin.package_fields.priority.idle', true),
                    'RealTime' => Language::_('Tcadmin.package_fields.priority.realtime', true),
                ],
                (isset($vars->meta['priority']) ? $vars->meta['priority'] : null),
                ['id' => 'priority']
            )
        );
        $fields->setField($priority);

        $startup = $fields->label(Language::_('Tcadmin.package_fields.startup', true), 'startup');
        $startup->attach(
            $fields->fieldSelect(
                'meta[startup]',
                [
                    'Automatic' => Language::_('Tcadmin.package_fields.startup.automatic', true),
                    'Manual' => Language::_('Tcadmin.package_fields.startup.manual', true),
                    'Disabled' => Language::_('Tcadmin.package_fields.startup.disabled', true),
                ],
                (isset($vars->meta['startup']) ? $vars->meta['startup'] : null),
                ['id' => 'startup']
            )
        );
        $fields->setField($startup);

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
     *    - key The key for this meta field
     *    - value The value for this key
     *    - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
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
     *    - key The key for this meta field
     *    - value The value for this key
     *    - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
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
     * @param array $vars An array of post data submitted to or on the manager module page (used to repopulate fields
     *  after an error)
     * @return string HTML content containing information to display when viewing the manager module page
     */
    public function manageModule($module, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'tcadmin' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set('module', $module);

        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the add module row page
     *
     * @param array $vars An array of post data submitted to or on the add module row page (used to repopulate fields
     *  after an error)
     * @return string HTML content containing information to display when viewing the add module row page
     */
    public function manageAddRow(array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('add_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'tcadmin' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        // Set unspecified checkboxes
        if (!empty($vars)) {
            if (empty($vars['use_ssl'])) {
                $vars['use_ssl'] = 'false';
            }
        }

        $this->view->set('vars', (object)$vars);
        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the edit module row page
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of post data submitted to or on the edit module row page (used to repopulate
     *  fields after an error)
     * @return string HTML content containing information to display when viewing the edit module row page
     */
    public function manageEditRow($module_row, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('edit_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'tcadmin' . DS);

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

        $this->view->set('vars', (object)$vars);
        return $this->view->fetch();
    }

    /**
     * Adds the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being added. Returns a set of data, which may be
     * a subset of $vars, that is stored for this module row
     *
     * @param array $vars An array of module info to add
     * @return array A numerically indexed array of meta fields for the module row containing:
     *    - key The key for this meta field
     *    - value The value for this key
     *    - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function addModuleRow(array &$vars)
    {
        $meta_fields = ['server_name', 'host_name', 'user_name', 'port', 'use_ssl', 'password'];
        $encrypted_fields = ['user_name', 'port', 'password'];

        // Set unspecified checkboxes
        if (empty($vars['use_ssl'])) {
            $vars['use_ssl'] = 'false';
        }

        $this->Input->setRules($this->getRowRules($vars));

        // Validate module row
        if ($this->Input->validates($vars)) {
            $vars['host_name'] = strtolower($vars['host_name']);

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
     * a subset of $vars, that is stored for this module row
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of module info to update
     * @return array A numerically indexed array of meta fields for the module row containing:
     *    - key The key for this meta field
     *    - value The value for this key
     *    - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function editModuleRow($module_row, array &$vars)
    {
        $meta_fields = ['server_name', 'host_name', 'user_name', 'port', 'use_ssl', 'password'];
        $encrypted_fields = ['user_name', 'port', 'password'];

        // Set unspecified checkboxes
        if (empty($vars['use_ssl'])) {
            $vars['use_ssl'] = 'false';
        }

        $this->Input->setRules($this->getRowRules($vars));

        // Validate module row
        if ($this->Input->validates($vars)) {
            $vars['host_name'] = strtolower($vars['host_name']);

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

    }

    /**
     * Returns all fields to display to an admin attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional HTML
     *  markup to include
     */
    public function getAdminAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        $hostname = $fields->label(Language::_('Tcadmin.service_field.hostname', true), 'hostname');
        $hostname->attach(
            $fields->fieldText(
                'hostname',
                (isset($vars->hostname) ? $vars->hostname : ($vars->hostname ?? null)),
                ['id' => 'hostname']
            )
        );
        $fields->setField($hostname);

        $rcon_password = $fields->label(Language::_('Tcadmin.service_field.rcon_password', true), 'rcon_password');
        $rcon_password->attach($fields->fieldPassword('rcon_password', ['id' => 'rcon_password']));
        $fields->setField($rcon_password);

        $private_password = $fields->label(
            Language::_('Tcadmin.service_field.private_password', true),
            'private_password'
        );
        $private_password->attach($fields->fieldPassword('private_password', ['id' => 'private_password']));
        $fields->setField($private_password);

        return $fields;
    }

    /**
     * Returns all fields to display to a client attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional HTML
     *  markup to include
     */
    public function getClientAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        $hostname = $fields->label(Language::_('Tcadmin.service_field.hostname', true), 'hostname');
        $hostname->attach(
            $fields->fieldText(
                'hostname',
                (isset($vars->hostname) ? $vars->hostname : ($vars->hostname ?? null)),
                ['id' => 'hostname']
            )
        );
        $fields->setField($hostname);

        $rcon_password = $fields->label(Language::_('Tcadmin.service_field.rcon_password', true), 'rcon_password');
        $rcon_password->attach($fields->fieldPassword('rcon_password', ['id' => 'rcon_password']));
        $fields->setField($rcon_password);

        $private_password = $fields->label(
            Language::_('Tcadmin.service_field.private_password', true),
            'private_password'
        );
        $private_password->attach($fields->fieldPassword('private_password', ['id' => 'private_password']));
        $fields->setField($private_password);

        return $fields;
    }

    /**
     * Returns all fields to display to an admin attempting to edit a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional HTML
     *  markup to include
     */
    public function getAdminEditFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        $hostname = $fields->label(Language::_('Tcadmin.service_field.hostname', true), 'hostname');
        $hostname->attach(
            $fields->fieldText(
                'hostname',
                (isset($vars->hostname) ? $vars->hostname : ($vars->hostname ?? null)),
                ['id' => 'hostname']
            )
        );
        $hostname_tooltip = $fields->tooltip(Language::_('Tcadmin.stored_locally_only', true));
        $hostname->attach($hostname_tooltip);
        $fields->setField($hostname);

        $user_name = $fields->label(Language::_('Tcadmin.service_field.user_name', true), 'user_name');
        $user_name->attach(
            $fields->fieldText(
                'user_name',
                (isset($vars->user_name) ? $vars->user_name : ($vars->user_name ?? null)),
                ['id' => 'user_name']
            )
        );
        $user_name_tooltip = $fields->tooltip(Language::_('Tcadmin.stored_locally_only', true));
        $user_name->attach($user_name_tooltip);
        $fields->setField($user_name);

        $password = $fields->label(Language::_('Tcadmin.service_field.user_password', true), 'user_password');
        $password->attach($fields->fieldPassword('user_password', ['id' => 'user_password']));
        $password_tooltip = $fields->tooltip(Language::_('Tcadmin.stored_locally_only', true));
        $password->attach($password_tooltip);
        $fields->setField($password);

        $rcon_password = $fields->label(Language::_('Tcadmin.service_field.rcon_password', true), 'rcon_password');
        $rcon_password->attach($fields->fieldPassword('rcon_password', ['id' => 'rcon_password']));
        $rcon_password_tooltip = $fields->tooltip(Language::_('Tcadmin.stored_locally_only', true));
        $rcon_password->attach($rcon_password_tooltip);
        $fields->setField($rcon_password);

        $private_password = $fields->label(
            Language::_('Tcadmin.service_field.private_password', true),
            'private_password'
        );
        $private_password->attach($fields->fieldPassword('private_password', ['id' => 'private_password']));
        $private_password_tooltip = $fields->tooltip(Language::_('Tcadmin.stored_locally_only', true));
        $private_password->attach($private_password_tooltip);
        $fields->setField($private_password);

        return $fields;
    }

    /**
     * Attempts to validate service info. This is the top-level error checking method. Sets Input errors on failure.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @return boolean True if the service validates, false otherwise. Sets Input errors when false.
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
            'hostname' => [
                'format' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Tcadmin.!error.hostname.format', true)
                ],
                'test' => [
                    'rule' => function ($str) {
                        return substr_compare('test', $str, 0, 4, true);
                    },
                    'message' => Language::_('Tcadmin.!error.hostname.test', true)
                ]
            ],
            'user_name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Tcadmin.!error.user_name.empty', true)
                ]
            ],
            'user_password' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['isPassword', 8],
                    'message' => Language::_('Tcadmin.!error.user_password.valid', true),
                    'last' => true
                ]
            ],
            'rcon_password' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['isPassword', 8],
                    'message' => Language::_('Tcadmin.!error.rcon_password.valid', true),
                    'last' => true
                ],
            ],
            'private_password' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['isPassword', 8],
                    'message' => Language::_('Tcadmin.!error.private_password.valid', true),
                    'last' => true
                ],
            ],
        ];

        // Set the values that may be empty
        if ($edit) {
            if (!array_key_exists('hostname', $vars) || $vars['hostname'] == '') {
                unset($rules['hostname']);
            }

            if (!array_key_exists('user_password', $vars) || $vars['user_password'] == '') {
                unset($rules['user_password']);
            }

            if (!array_key_exists('rcon_password', $vars) || $vars['rcon_password'] == '') {
                unset($rules['rcon_password']);
            }

            if (!array_key_exists('private_password', $vars) || $vars['private_password'] == '') {
                unset($rules['private_password']);
            }
        } else {
            unset($rules['user_name']);
            unset($rules['user_password']);
        }

        return $rules;
    }

    /**
     * Adds the service to the remote server. Sets Input errors on failure,
     * preventing the service from being added.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the
     *  current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service of the service being added (if
     *  the current service is an addon service service and parent service has already been provisioned)
     * @param string $status The status of the service being added. These include:
     *    - active
     *    - canceled
     *    - pending
     *    - suspended
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *    - key The key for this meta field
     *    - value The value for this key
     *    - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
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
                ['module_row' => ['missing' => Language::_('Tcadmin.!error.module_row.missing', true)]]
            );
            return;
        }

        Loader::loadModels($this, ['Clients']);

        if (isset($vars['client_id']) && ($client = $this->Clients->get($vars['client_id'], false))) {
            $vars['client_id'] = $vars['client_id'];
            $vars['client_email'] = $client->email;
            $vars['client_firstname'] = $client->first_name;
            $vars['client_lastname'] = $client->last_name;
            $vars['client_address1'] = $client->address1;
            $vars['client_address2'] = $client->address2;
            $vars['client_city'] = $client->city;
            $vars['client_state'] = $client->state;
            $vars['client_zip'] = $client->zip;
            $vars['client_country'] = $client->country;

            $vars['client_package_id'] = $this->generateServiceId($vars['client_id']);

            $vars['user_name'] = $this->generateUsername($client->first_name, $client->last_name);
        }

        $vars['user_password'] = $this->generatePassword(10, 14);

        $params = $this->getFieldsFromInput((array)$vars, $package);

        $this->validateService($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            $tcadmin = $this->getApi(
                $row->meta->host_name,
                $row->meta->user_name,
                $row->meta->port,
                $row->meta->password,
                $row->meta->use_ssl
            );

            $masked_params = $params;
            if (isset($masked_params['user_password'])) {
                $masked_params['user_password'] = '****';
            }

            if (isset($masked_params['voice_rcon_password'])) {
                $masked_params['voice_rcon_password'] = '****';
            }

            if (isset($masked_params['voice_private_password'])) {
                $masked_params['voice_private_password'] = '****';
            }

            if (isset($masked_params['game_rcon_password'])) {
                $masked_params['game_rcon_password'] = '****';
            }

            if (isset($masked_params['game_private_password'])) {
                $masked_params['game_private_password'] = '****';
            }

            $this->log($row->meta->host_name . '|AddPendingSetup', serialize($masked_params), 'input', true);

            $response = $tcadmin->createAccount($params);

            if (!isset($response['results']['errorcode']) || $response['results']['errorcode'] != '0') {
                $this->Input->setErrors(
                    ['api_response' => ['missing' => Language::_('Tcadmin.!error.api.internal', true)]]
                );
                $this->log($row->meta->host_name . '|AddPendingSetup', serialize($response), 'output', false);
                return;
            }

            $this->log($row->meta->host_name . '|AddPendingSetup', serialize($response), 'output', true);


            if ($this->Input->errors()) {
                return;
            }
        }

        // Return service fields
        return [
            [
                'key' => 'client_package_id',
                'value' => (isset($vars['client_package_id']) ? $vars['client_package_id'] : ''),
                'encrypted' => 1
            ],
            [
                'key' => 'hostname',
                'value' => (isset($vars['hostname']) ? $vars['hostname'] : ''),
                'encrypted' => 1
            ],
            [
                'key' => 'user_name',
                'value' => (isset($vars['user_name']) ? $vars['user_name'] : ''),
                'encrypted' => 1
            ],
            [
                'key' => 'user_password',
                'value' => (isset($vars['user_password']) ? $vars['user_password'] : ''),
                'encrypted' => 1
            ],
            [
                'key' => 'rcon_password',
                'value' => (isset($vars['rcon_password']) ? $vars['rcon_password'] : ''),
                'encrypted' => 1
            ],
            [
                'key' => 'private_password',
                'value' => (isset($vars['private_password']) ? $vars['private_password'] : ''),
                'encrypted' => 1
            ],
        ];
    }

    /**
     * Edits the service on the remote server. Sets Input errors on failure,
     * preventing the service from being edited.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the
     *  current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service of the service being
     *  edited (if the current service is an addon service)
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *    - key The key for this meta field
     *    - value The value for this key
     *    - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function editService($package, $service, array $vars = null, $parent_package = null, $parent_service = null)
    {
        $row = $this->getModuleRow();

        $this->validateServiceEdit($service, $vars);

        if ($this->Input->errors()) {
            return;
        }

        $service_fields = $this->serviceFieldsToObject($service->fields);

        if (empty($vars['client_package_id'])) {
            $vars['client_package_id'] = $service_fields->client_package_id;
        }
        if (empty($vars['hostname'])) {
            $vars['hostname'] = $service_fields->hostname;
        }
        if (empty($vars['user_name'])) {
            $vars['user_name'] = $service_fields->user_name;
        }
        if (empty($vars['user_password'])) {
            $vars['user_password'] = $service_fields->user_password;
        }
        if (empty($vars['rcon_password'])) {
            $vars['rcon_password'] = $service_fields->rcon_password;
        }
        if (empty($vars['private_password'])) {
            $vars['private_password'] = $service_fields->private_password;
        }
        // Only update the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            $tcadmin = $this->getApi(
                $row->meta->host_name,
                $row->meta->user_name,
                $row->meta->port,
                $row->meta->password,
                $row->meta->use_ssl
            );
            $fields = [
                'client_package_id' => $vars['client_package_id'],
            ];
            if ($package->meta->server_type === 'game') {
                $fields['game_private'] = isset($vars['configoptions']['game_private'])
                    ? $vars['configoptions']['game_private']
                    : '';
                $fields['game_slots'] = isset($vars['configoptions']['game_slots'])
                    ? $vars['configoptions']['game_slots']
                    : '';
                $fields['game_branded'] = isset($vars['configoptions']['game_branded'])
                    ? $vars['configoptions']['game_branded']
                    : '';
                //      $fields['game_priority'] = $package->meta->priority;
            } elseif ($package->meta->server_type === 'voice') {
                $fields['voice_private'] = isset($vars['configoptions']['voice_private'])
                    ? $vars['configoptions']['voice_private']
                    : '';
                $fields['voice_slots'] = isset($vars['configoptions']['voice_slots'])
                    ? $vars['configoptions']['voice_slots']
                    : '';
                $fields['voice_branded'] = isset($vars['configoptions']['voice_branded'])
                    ? $vars['configoptions']['voice_branded']
                    : '';
                //      $fields['voice_priority'] = $package->meta->priority;
                $fields['voice_upload_quota'] = isset($vars['configoptions']['voice_upload_quota'])
                    ? $vars['configoptions']['voice_upload_quota']
                    : '';
                $fields['voice_download_quota'] = isset($vars['configoptions']['voice_download_quota'])
                    ? $vars['configoptions']['voice_download_quota']
                    : '';
            }

            $this->log($row->meta->host_name . '|UpdateSettings', serialize($fields), 'input', true);

            $response = $tcadmin->updateUserSettings($fields);

            if (!isset($response['results']['errorcode'])
                || (isset($response['results']['errorcode']) && $response['results']['errorcode'] != '0')
            ) {
                $this->Input->setErrors(
                    ['api_response' => ['missing' => Language::_('Tcadmin.!error.api.internal', true)]]
                );
                $this->log($row->meta->host_name . '|UpdateSettings', serialize($response), 'output', false);
                return;
            }
            $this->log($row->meta->host_name . '|UpdateSettings', serialize($response), 'output', true);
        }

        // Return service fields
        return [
            [
                'key' => 'client_package_id',
                'value' => $vars['client_package_id'],
                'encrypted' => 1
            ],
            [
                'key' => 'hostname',
                'value' => $vars['hostname'],
                'encrypted' => 1
            ],
            [
                'key' => 'user_name',
                'value' => $vars['user_name'],
                'encrypted' => 1
            ],
            [
                'key' => 'user_password',
                'value' => $vars['user_password'],
                'encrypted' => 1
            ],
            [
                'key' => 'rcon_password',
                'value' => $vars['rcon_password'],
                'encrypted' => 1
            ],
            [
                'key' => 'private_password',
                'value' => $vars['private_password'],
                'encrypted' => 1
            ],
        ];
    }

    /**
     * Suspends the service on the remote server. Sets Input errors on failure,
     * preventing the service from being suspended.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the
     *  current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service of the service being
     *  suspended (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically indexed array of meta fields to be
     *  stored for this service containing:
     *    - key The key for this meta field
     *    - value The value for this key
     *    - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function suspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        if (($row = $this->getModuleRow())) {
            $service_fields = $this->serviceFieldsToObject($service->fields);

            $tcadmin = $this->getApi(
                $row->meta->host_name,
                $row->meta->user_name,
                $row->meta->port,
                $row->meta->password,
                $row->meta->use_ssl
            );

            $this->log(
                $row->meta->host_name . '|SuspendGameAndVoiceByBillingID',
                serialize($service_fields->client_package_id),
                'input',
                true
            );

            $response = $tcadmin->suspendServer($service_fields->client_package_id);

            if (!isset($response['results']['errorcode'])
                || (isset($response['results']['errorcode']) && $response['results']['errorcode'] != '0')
            ) {
                $this->Input->setErrors(
                    ['api_response' => ['missing' => Language::_('Tcadmin.!error.api.internal', true)]]
                );

                $this->log(
                    $row->meta->host_name . '|SuspendGameAndVoiceByBillingID',
                    serialize($response),
                    'output',
                    false
                );

                return;
            }

            $this->log($row->meta->host_name . '|SuspendGameAndVoiceByBillingID', serialize($response), 'output', true);
        }
        return null;
    }

    /**
     * Unsuspends the service on the remote server. Sets Input errors on failure,
     * preventing the service from being unsuspended.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the
     *  current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service of the service being
     *  unsuspended (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically indexed array of meta fields to be
     *  stored for this service containing:
     *    - key The key for this meta field
     *    - value The value for this key
     *    - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function unsuspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        if (($row = $this->getModuleRow())) {
            $service_fields = $this->serviceFieldsToObject($service->fields);

            $tcadmin = $this->getApi(
                $row->meta->host_name,
                $row->meta->user_name,
                $row->meta->port,
                $row->meta->password,
                $row->meta->use_ssl
            );


            $this->log(
                $row->meta->host_name . '|UnSuspendGameAndVoiceByBillingID',
                serialize($service_fields->client_package_id),
                'input',
                true
            );

            $response = $tcadmin->unSuspendServer($service_fields->client_package_id);

            if (!isset($response['results']['errorcode'])
                || (isset($response['results']['errorcode']) && $response['results']['errorcode'] != '0')
            ) {
                $this->Input->setErrors(
                    ['api_response' => ['missing' => Language::_('Tcadmin.!error.api.internal', true)]]
                );

                $this->log(
                    $row->meta->host_name . '|UnSuspendGameAndVoiceByBillingID',
                    serialize($response),
                    'output',
                    false
                );

                return;
            }

            $this->log(
                $row->meta->host_name . '|UnSuspendGameAndVoiceByBillingID',
                serialize($response),
                'output',
                true
            );
        }
        return null;
    }

    /**
     * Cancels the service on the remote server. Sets Input errors on failure,
     * preventing the service from being canceled.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the
     *  current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service of the service being
     *  canceled (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically indexed array of meta fields to be
     *  stored for this service containing:
     *    - key The key for this meta field
     *    - value The value for this key
     *    - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function cancelService($package, $service, $parent_package = null, $parent_service = null)
    {

        if (($row = $this->getModuleRow())) {
            $service_fields = $this->serviceFieldsToObject($service->fields);

            $tcadmin = $this->getApi(
                $row->meta->host_name,
                $row->meta->user_name,
                $row->meta->port,
                $row->meta->password,
                $row->meta->use_ssl
            );

            $this->log(
                $row->meta->host_name . '|DeleteGameAndVoiceByBillingID',
                serialize($service_fields->client_package_id),
                'input',
                true
            );

            $response = $tcadmin->deleteServer($service_fields->client_package_id);

            if (!isset($response['results']['errorcode'])
                || (isset($response['results']['errorcode']) && $response['results']['errorcode'] != '0')
            ) {
                $this->Input->setErrors(
                    ['api_response' => ['missing' => Language::_('Tcadmin.!error.api.internal', true)]]
                );

                $this->log(
                    $row->meta->host_name . '|DeleteGameAndVoiceByBillingID',
                    serialize($response),
                    'output',
                    false
                );

                return;
            }

            $this->log($row->meta->host_name . '|DeleteGameAndVoiceByBillingID', serialize($response), 'output', true);
        }
        return null;
    }

    /**
     * Not Supported
     */
    public function changeServicePackage(
        $package_from,
        $package_to,
        $service,
        $parent_package = null,
        $parent_service = null
    ) {
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'tcadmin' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'tcadmin' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $this->serviceFieldsToObject($service->fields));

        return $this->view->fetch();
    }

    /**
     * Validates that the given hostname is valid
     *
     * @param string $host_name The host name to validate
     * @return boolean True if the hostname is valid, false otherwise
     */
    public function validateHostName($host_name)
    {
        $validator = new Server();
        return $validator->isDomain($host_name) || $validator->isIp($host_name);
    }

    /**
     * Generates a service id to use inside Tcadmin
     *
     * @param int $min_length The minimum character length for the password (5 or larger)
     * @param int $max_length The maximum character length for the password (14 or fewer)
     * @return string The generated password
     */
    private function generateServiceId($client_id, $min_length = 10, $max_length = 14)
    {
        $pool = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $pool_size = strlen($pool);
        $length = mt_rand(max($min_length, 5), min($max_length, 14));
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= substr($pool, mt_rand(0, $pool_size - 1), 1);
        }

        return $client_id . '_' . $password;
    }

    /**
     * Generates a password
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
     * Generates a username from the given client's first and last name
     *
     * @param string $first_name The first name of the client to use to generate the username
     * @param string $last_name The last name of the client to use to generate the username
     * @return string The username generated from the given hostname
     */
    private function generateUsername($first_name, $last_name)
    {
        // Remove everything except letters and numbers from the domain
        // ensure no number appears in the beginning
        $username = ltrim(preg_replace('/[^a-z0-9]/i', '', $first_name . $last_name), '0123456789');

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
        return $username;
    }

    /**
     * Returns an array of service field to set for the service using the given input
     *
     * @param array $vars An array of key/value input pairs
     * @param stdClass $package A stdClass object representing the package for the service
     * @return array An array of key/value pairs representing service fields
     */
    private function getFieldsFromInput(array $vars, $package)
    {

        $fields['client_id'] = isset($vars['client_id']) ? $vars['client_id'] : null;
        $fields['user_email'] = isset($vars['client_email']) ? $vars['client_email'] : null;
        $fields['user_fname'] = isset($vars['client_firstname']) ? $vars['client_firstname'] : null;
        $fields['user_lname'] = isset($vars['client_lastname']) ? $vars['client_lastname'] : null;
        $fields['user_address1'] = isset($vars['client_address1']) ? $vars['client_address1'] : null;
        $fields['user_address2'] = isset($vars['client_address2']) ? $vars['client_address2'] : null;
        $fields['user_city'] = isset($vars['client_city']) ? $vars['client_city'] : null;
        $fields['user_state'] = isset($vars['client_state']) ? $vars['client_state'] : null;
        $fields['user_zip'] = isset($vars['client_zip']) ? $vars['client_zip'] : null;
        $fields['user_country'] = isset($vars['client_country']) ? $vars['client_country'] : null;

        $fields['user_name'] = isset($vars['user_name']) ? $vars['user_name'] : null;
        $fields['user_password'] = isset($vars['user_password']) ? $vars['user_password'] : null;


        if ($package->meta->server_type === 'game') {
            $fields['game_package_id'] = isset($vars['client_package_id']) ? $vars['client_package_id'] : null;

            $fields['game_id'] = $package->meta->supported_servers;
            $fields['game_slots'] = isset($vars['configoptions']['game_slots'])
                ? $vars['configoptions']['game_slots']
                : null;
            $fields['game_private'] = isset($vars['configoptions']['game_private'])
                ? $vars['configoptions']['game_private']
                : null;
            //      $fields['game_additional_slots'] = isset($vars['configoptions']['game_additional_slots'])
            //           ? $vars['configoptions']['game_additional_slots']
            //           : null;
            $fields['game_branded'] = isset($vars['configoptions']['game_branded'])
                ? $vars['configoptions']['game_branded']
                : null;
            $fields['game_start'] = $package->meta->start;
            $fields['game_priority'] = $package->meta->priority;
            $fields['game_startup'] = $package->meta->startup;

            $fields['game_datacenter'] = isset($vars['configoptions']['game_datacenter'])
                ? $vars['configoptions']['game_datacenter']
                : null;
            $fields['game_hostname'] = isset($vars['hostname']) ? $vars['hostname'] : null;
            $fields['game_rcon_password'] = isset($vars['rcon_password']) ? $vars['rcon_password'] : null;
            $fields['game_private_password'] = isset($vars['private_password']) ? $vars['private_password'] : null;
        } elseif ($package->meta->server_type === 'voice') {
            $fields['voice_package_id'] = isset($vars['client_package_id']) ? $vars['client_package_id'] : null;
            $fields['voice_id'] = $package->meta->supported_servers;
            $fields['voice_slots'] = isset($vars['configoptions']['voice_slots'])
                ? $vars['configoptions']['voice_slots']
                : null;
            $fields['voice_private'] = isset($vars['configoptions']['voice_private'])
                ? $vars['configoptions']['voice_private']
                : null;
            //      $fields['voice_additional_slots'] = isset($vars['configoptions']['game_additional_slots'])
            //          ? $vars['configoptions']['game_additional_slots']
            //          : null;
            $fields['voice_upload_quota'] = isset($vars['configoptions']['voice_upload_quota'])
                ? $vars['configoptions']['voice_upload_quota']
                : null;
            $fields['voice_download_quota'] = isset($vars['configoptions']['voice_download_quota'])
                ? $vars['configoptions']['voice_download_quota']
                : null;
            $fields['voice_priority'] = $package->meta->priority;
            $fields['voice_startup'] = $package->meta->startup;

            $fields['voice_datacenter'] = isset($vars['configoptions']['voice_datacenter'])
                ? $vars['configoptions']['voice_datacenter']
                : null;
            $fields['voice_hostname'] = isset($vars['hostname']) ? $vars['hostname'] : null;
            $fields['voice_rcon_password'] = isset($vars['rcon_password']) ? $vars['rcon_password'] : null;
            $fields['voice_private_password'] = isset($vars['private_password']) ? $vars['private_password'] : null;
        }

        return $fields;
    }

    /**
     * Initialize the API library
     *
     * @param string $user_name The tcadmin username
     * @param string $password The tcadmin password
     * @param string $host_name The hostname of the server
     * @param string $port The port of the tcadmin server
     * @param boolean $use_ssl Whether to use https or http
     * @return Tcadminapi the Tcadminapi instance, or false if the loader fails to load the file
     */
    private function getApi($host_name, $user_name, $port, $password, $use_ssl)
    {
        Loader::load(dirname(__FILE__) . DS . 'api' . DS . 'tcadminapi.php');
        return new Tcadminapi($user_name, $password, $host_name, $port, $use_ssl);
    }

    /**
     * Gets the configured servers in tcadmin
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @param string $server_type the type of the servers to be listed (game, voice)
     * @return array An array of packages in key/value pair
     */
    private function getConfiguredServers($module_row, $server_type)
    {
        $servers = [];
        $tcadmin = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->user_name,
            $module_row->meta->port,
            $module_row->meta->password,
            $module_row->meta->use_ssl
        );

        $response = [];

        if ($server_type === 'game') {
            $response = $tcadmin->getGameServers();
        } elseif ($server_type === 'voice') {
            $response = $tcadmin->getVoiceServers();
        }

        if (isset($response['results']['game'])) {
            foreach ($response['results']['game'] as $key => $value) {
                $servers[$response['results']['game'][$key]['gameid']] = $response['results']['game'][$key]['name'];
            }
        }

        if (!isset($response['results']['errorcode'])
            || (isset($response['results']['errorcode']) && $response['results']['errorcode'] != '0')
        ) {
            $this->Input->setErrors(
                ['api_response' => ['missing' => Language::_('Tcadmin.!error.api.internal', true)]]
            );

            $this->log(
                $module_row->meta->host_name . '|get supported ' . $server_type . ' servers',
                serialize($response),
                'output',
                false
            );

            return $servers;
        }

        $this->log(
            $module_row->meta->host_name . '|get supported ' . $server_type . ' servers',
            serialize($response),
            'output',
            true
        );

        return $servers;
    }


    /**
     * Builds and returns the rules required to add/edit a module row (e.g. server)
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getRowRules(array $vars)
    {
        $rules = [
            'server_name' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Tcadmin.!error.server_name_valid', true)
                ]
            ],
            'host_name' => [
                'valid' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Tcadmin.!error.host_name_valid', true)
                ]
            ],
            'user_name' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Tcadmin.!error.user_name_valid', true)
                ]
            ],
            'port' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Tcadmin.!error.port_valid', true)
                ]
            ],
            'password' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Tcadmin.!error.password_valid', true)
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Builds and returns rules required to be validated when adding/editing a package
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getPackageRules(array $vars)
    {
        $rules = [
            'meta[server_type]' => [
                'valid' => [
                    'rule' => ['matches', '/^(voice|game)$/'],
                    'message' => Language::_('Tcadmin.!error.meta[server_type].valid', true)
                ]
            ],
            'meta[supported_servers]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Tcadmin.!error.meta[supported_servers].empty', true)
                ]
            ],
            'meta[start]' => [
                'valid' => [
                    'rule' => ['matches', '/^(1|0)$/'],
                    'message' => Language::_('Tcadmin.!error.meta[start].valid', true)
                ]
            ],
            'meta[priority]' => [
                'valid' => [
                    'rule' => ['matches', '/^(AboveNormal|BelowNormal|Normal|High|Idle|RealTime)$/'],
                    'message' => Language::_('Tcadmin.!error.meta[priority].valid', true)
                ]
            ],
            'meta[startup]' => [
                'valid' => [
                    'rule' => ['matches', '/^(Automatic|Manual|Disabled)$/'],
                    'message' => Language::_('Tcadmin.!error.meta[startup].valid', true)
                ]
            ],
        ];

        return $rules;
    }
}
