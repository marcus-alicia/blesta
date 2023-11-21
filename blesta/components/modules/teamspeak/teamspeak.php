<?php
use Blesta\Core\Util\Validate\Server;
/**
 * TeamSpeak Module.
 *
 * @package blesta
 * @subpackage blesta.components.modules.teamspeak
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Teamspeak extends Module
{
    /**
     * Initializes the module.
     */
    public function __construct()
    {
        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('teamspeak', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load module config
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
            'tabActions' => Language::_('Teamspeak.tab_actions', true),
            'tabClients' => Language::_('Teamspeak.tab_clients', true),
            'tabBans' => Language::_('Teamspeak.tab_bans', true),
            'tabTokens' => Language::_('Teamspeak.tab_tokens', true),
            'tabLogs' => Language::_('Teamspeak.tab_logs', true)
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
            'tabClientActions' => ['name' => Language::_('Teamspeak.tab_client_actions', true), 'icon' => 'fas fa-cog'],
            'tabClientClients' => [
                'name' => Language::_('Teamspeak.tab_client_clients', true), 'icon' => 'fas fa-users'
            ],
            'tabClientBans' => ['name' => Language::_('Teamspeak.tab_client_bans', true), 'icon' => 'fas fa-ban'],
            'tabClientTokens' => ['name' => Language::_('Teamspeak.tab_client_tokens', true), 'icon' => 'fas fa-key'],
            'tabClientLogs' => ['name' => Language::_('Teamspeak.tab_client_logs', true), 'icon' => 'fas fa-chart-bar']
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
            'roundrobin' => Language::_('Teamspeak.order_options.roundrobin', true),
            'first' => Language::_('Teamspeak.order_options.first', true)
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

        // Create maxclients label
        $maxclients = $fields->label(
            Language::_('Teamspeak.package_fields.maxclients', true),
            'teamspeak_maxclients'
        );
        // Create maxclients field and attach to maxclients label
        $maxclients->attach(
            $fields->fieldText(
                'meta[maxclients]',
                (isset($vars->meta['maxclients']) ? $vars->meta['maxclients'] : null),
                ['id' => 'teamspeak_maxclients']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Teamspeak.package_fields.tooltip.maxclients', true));
        $maxclients->attach($tooltip);
        // Set the label as a field
        $fields->setField($maxclients);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'teamspeak' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'teamspeak' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'teamspeak' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        if (empty($vars)) {
            $vars = $module_row->meta;
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
        $meta_fields = ['server_name', 'hostname', 'port', 'username',
            'password', 'account_limit', 'notes', 'account_count'];
        $encrypted_fields = ['username', 'password'];

        $this->Input->setRules($this->getRowRules($vars));

        // Validate module row
        if ($this->Input->validates($vars)) {
            $vars['hostname'] = strtolower($vars['hostname']);

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
        $meta_fields = ['server_name', 'hostname', 'port', 'username',
            'password', 'account_limit', 'notes', 'account_count'];
        $encrypted_fields = ['username', 'password'];

        $this->Input->setRules($this->getRowRules($vars));

        // Validate module row
        if ($this->Input->validates($vars)) {
            $vars['hostname'] = strtolower($vars['hostname']);

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
        $fields = $this->getClientAddFields($package, $vars);

        // Create port label
        $port = $fields->label(Language::_('Teamspeak.service_field.port', true), 'teamspeak_port');
        // Create port field and attach to port label
        $port->attach(
            $fields->fieldText(
                'teamspeak_port',
                (isset($vars->teamspeak_port) ? $vars->teamspeak_port : ($vars->port ?? null)),
                ['id' => 'teamspeak_port']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Teamspeak.service_field.tooltip.port', true));
        $port->attach($tooltip);
        // Set the label as a field
        $fields->setField($port);

        // Create sid label
        $sid = $fields->label(Language::_('Teamspeak.service_field.sid', true), 'teamspeak_sid');
        // Create port field and attach to port label
        $sid->attach(
            $fields->fieldText(
                'teamspeak_sid',
                (isset($vars->teamspeak_sid) ? $vars->teamspeak_sid : ($vars->sid ?? null)),
                ['id' => 'teamspeak_sid']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Teamspeak.service_field.tooltip.sid', true));
        $sid->attach($tooltip);
        // Set the label as a field
        $fields->setField($sid);

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

        // Create name label
        $name = $fields->label(Language::_('Teamspeak.service_field.name', true), 'teamspeak_name');
        // Create name field and attach to name label
        $name->attach(
            $fields->fieldText(
                'teamspeak_name',
                (isset($vars->teamspeak_name) ? $vars->teamspeak_name : ($vars->name ?? null)),
                ['id' => 'teamspeak_name']
            )
        );
        // Set the label as a field
        $fields->setField($name);

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

        // Create name label
        $name = $fields->label(Language::_('Teamspeak.service_field.name', true), 'teamspeak_name');
        // Create name field and attach to name label
        $name->attach(
            $fields->fieldText(
                'teamspeak_name',
                (isset($vars->teamspeak_name) ? $vars->teamspeak_name : ($vars->name ?? null)),
                ['id' => 'teamspeak_name']
            )
        );
        // Set the label as a field
        $fields->setField($name);

        // Create port label
        $port = $fields->label(Language::_('Teamspeak.service_field.port', true), 'teamspeak_port');
        // Create port field and attach to port label
        $port->attach(
            $fields->fieldText(
                'teamspeak_port',
                (isset($vars->teamspeak_port) ? $vars->teamspeak_port : ($vars->port ?? null)),
                ['id' => 'teamspeak_port']
            )
        );
        // Set the label as a field
        $fields->setField($port);

        // Create sid label
        $sid = $fields->label(Language::_('Teamspeak.service_field.sid', true), 'teamspeak_sid');
        // Create port field and attach to port label
        $sid->attach(
            $fields->fieldText(
                'teamspeak_sid',
                (isset($vars->teamspeak_sid) ? $vars->teamspeak_sid : ($vars->sid ?? null)),
                ['id' => 'teamspeak_sid']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Teamspeak.service_field.tooltip.sid', true));
        $sid->attach($tooltip);
        // Set the label as a field
        $fields->setField($sid);

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
            'teamspeak_name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Teamspeak.!error.teamspeak_name.empty', true)
                ]
            ]
        ];

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
                ['module_row' => ['missing' => Language::_('Teamspeak.!error.module_row.missing', true)]]
            );

            return;
        }

        // Get service parameters
        $params = $this->getFieldsFromInput((array) $vars, $package);

        // Validate service
        $this->validateService($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Only provision the service if 'use_module' is true
        $result = null;
        if ($vars['use_module'] == 'true') {
            $this->log($row->meta->hostname . '|servercreate', serialize($params), 'input', true);

            try {
                // Initialize API
                $api = $this->getApi(
                    $row->meta->hostname,
                    $row->meta->username,
                    $row->meta->password,
                    $row->meta->port
                );

                // Create virtual server
                $result = $this->parseResponse($api->createServer($params));
            } catch (Exception $e) {
                $this->Input->setErrors(
                    ['api' => ['internal' => Language::_('Teamspeak.!error.api.internal', true)]]
                );
            }

            if ($this->Input->errors()) {
                return;
            }

            // Update the number of accounts on the server
            $this->updateAccountCount($row);
        }

        // Return service fields
        return [
            [
                'key' => 'teamspeak_sid',
                'value' => $result ? $result->sid : '',
                'encrypted' => 0
            ],
            [
                'key' => 'teamspeak_token',
                'value' => $result ? $result->token : '',
                'encrypted' => 1
            ],
            [
                'key' => 'teamspeak_name',
                'value' => $params['name'],
                'encrypted' => 0
            ],
            [
                'key' => 'teamspeak_port',
                'value' => $result ? $result->virtualserver_port : '',
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
    public function editService($package, $service, array $vars = null, $parent_package = null, $parent_service = null)
    {
        $row = $this->getModuleRow();

        if (!$row) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Teamspeak.!error.module_row.missing', true)]]
            );

            return;
        }

        // Get service parameters
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Validate service
        $this->validateServiceEdit($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            // Initialize API
            $api = $this->getApi(
                $row->meta->hostname,
                $row->meta->username,
                $row->meta->password,
                $row->meta->port
            );

            // Check for fields that changed
            $delta = [];
            foreach ($vars as $key => $value) {
                if (!array_key_exists($key, $service_fields) || $vars[$key] != $service_fields->$key) {
                    $delta[$key] = $value;
                }
            }

            // Get a list of altered fields
            $params = [];
            $account_fields = ['sid', 'token', 'name', 'port'];
            foreach ($account_fields as $account_field) {
                if (isset($delta['teamspeak_' . $account_field])) {
                    $params[$account_field] = $delta['teamspeak_' . $account_field];
                }
            }

            // Update altered fields
            if (!empty($params)) {
                $this->log($row->meta->hostname . '|serveredit', serialize($params), 'input', true);
                $result = $this->parseResponse($api->editServer($service_fields->teamspeak_sid, $params));
            }
        }

        // Set fields to update locally
        $fields = [
            'teamspeak_sid',
            'teamspeak_token',
            'teamspeak_name',
            'teamspeak_port'
        ];
        foreach ($fields as $field) {
            if (property_exists($service_fields, $field) && isset($vars[$field])) {
                $service_fields->{$field} = $vars[$field];
            }
        }

        // Return all the service fields
        $fields = [];
        $encrypted_fields = ['teamspeak_token'];
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
        $row = $this->getModuleRow();

        if ($row) {
            $api = $this->getApi(
                $row->meta->hostname,
                $row->meta->username,
                $row->meta->password,
                $row->meta->port
            );

            $service_fields = $this->serviceFieldsToObject($service->fields);

            $this->log(
                $row->meta->hostname . '|modify',
                serialize($service_fields->teamspeak_sid),
                'input',
                true
            );
            $this->parseResponse($api->suspendServer($service_fields->teamspeak_sid));
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
        $row = $this->getModuleRow();

        if ($row) {
            $api = $this->getApi(
                $row->meta->hostname,
                $row->meta->username,
                $row->meta->password,
                $row->meta->port
            );

            $service_fields = $this->serviceFieldsToObject($service->fields);

            $this->log(
                $row->meta->hostname . '|modify',
                serialize($service_fields->teamspeak_sid),
                'input',
                true
            );
            $this->parseResponse($api->unsuspendServer($service_fields->teamspeak_sid));
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
        $row = $this->getModuleRow();

        if ($row) {
            $api = $this->getApi(
                $row->meta->hostname,
                $row->meta->username,
                $row->meta->password,
                $row->meta->port
            );

            $service_fields = $this->serviceFieldsToObject($service->fields);

            $this->log(
                $row->meta->hostname . '|serverdelete',
                serialize($service_fields->teamspeak_sid),
                'input',
                true
            );
            $this->parseResponse($api->deleteServer($service_fields->teamspeak_sid));

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
        $row = $this->getModuleRow();

        if ($row) {
            $api = $this->getApi(
                $row->meta->hostname,
                $row->meta->username,
                $row->meta->password,
                $row->meta->port
            );

            $service_fields = $this->serviceFieldsToObject($service->fields);

            $params = [
                'name' => $service_fields->teamspeak_name,
                'maxclients' => $package_to->meta->maxclients,
                'port' => $service_fields->teamspeak_port
            ];
            $this->log($row->meta->hostname . '|serveredit', serialize($params), 'input', true);
            $this->parseResponse($api->editServer($service_fields->teamspeak_sid, $params));

            // Update the number of accounts on the server
            $this->updateAccountCount($row);
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'teamspeak' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'teamspeak' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $this->serviceFieldsToObject($service->fields));

        return $this->view->fetch();
    }

    /**
     * Actions tab.
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
        // Get module row
        $row = $this->getModuleRow();

        $this->view = new View('tab_actions', 'default');
        $this->view->base_uri = $this->base_uri;

        // Initialize API
        $api = $this->getApi(
            $row->meta->hostname,
            $row->meta->username,
            $row->meta->password,
            $row->meta->port
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Perform actions
        if (!empty($post)) {
            switch ($post['action']) {
                case 'restart':
                    $this->parseResponse($api->restartServer($service_fields->teamspeak_sid));
                    break;
                case 'stop':
                    $this->parseResponse($api->stopServer($service_fields->teamspeak_sid));
                    break;
                case 'start':
                    $this->parseResponse($api->startServer($service_fields->teamspeak_sid));
                    break;
                case 'change_name':
                    // Update the service name
                    Loader::loadModels($this, ['Services']);
                    $this->Services->editField(
                        $service->id,
                        ['key' => 'teamspeak_name', 'value' => (isset($post['name']) ? $post['name'] : null)]
                    );

                    if (($errors = $this->Services->errors())) {
                        $this->Input->setErrors($errors);
                    }

                    $params = [
                        'name' => (isset($post['name']) ? $post['name'] : null),
                        'maxclients' => $package->meta->maxclients,
                        'port' => $service_fields->teamspeak_port
                    ];
                    $this->parseResponse($api->editServer($service_fields->teamspeak_sid, $params));
                    break;
                case 'remove_ban':
                    $this->parseResponse($api->deleteAllBans($service_fields->teamspeak_sid));
                    break;
                case 'reset_permissions':
                    $this->parseResponse($api->resetPermissions($service_fields->teamspeak_sid));
                    break;
                default:
                    break;
            }
        }

        // Get virtual server information
        $server_info = $this->parseResponse($api->getServerState($service_fields->teamspeak_sid));

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('server_info', $server_info);
        $this->view->set('vars', (isset($vars) ? $vars : new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'teamspeak' . DS);

        return $this->view->fetch();
    }

    /**
     * Server Clients tab.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClients($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Get module row
        $row = $this->getModuleRow();

        $this->view = new View('tab_clients', 'default');
        $this->view->base_uri = $this->base_uri;

        // Initialize API
        $api = $this->getApi(
            $row->meta->hostname,
            $row->meta->username,
            $row->meta->password,
            $row->meta->port
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Perform actions
        if (!empty($post)) {
            switch ($post['action']) {
                case 'kick_client':
                    $this->parseResponse(
                        $api->kickClient($service_fields->teamspeak_sid, (isset($post['clid']) ? $post['clid'] : null))
                    );
                    break;
                default:
                    break;
            }
        }

        // Get virtual server clients
        $server_clients = $api->listClients($service_fields->teamspeak_sid);

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('server_clients', $server_clients);
        $this->view->set('vars', (isset($vars) ? (object) $vars : new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'teamspeak' . DS);

        return $this->view->fetch();
    }

    /**
     * Server Bans tab.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabBans($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Get module row
        $row = $this->getModuleRow();

        $this->view = new View('tab_bans', 'default');
        $this->view->base_uri = $this->base_uri;

        // Initialize API
        $api = $this->getApi(
            $row->meta->hostname,
            $row->meta->username,
            $row->meta->password,
            $row->meta->port
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Perform actions
        if (!empty($post)) {
            switch ($post['action']) {
                case 'unban_client':
                    $this->parseResponse(
                        $api->deleteBan($service_fields->teamspeak_sid, (isset($post['banid']) ? $post['banid'] : null))
                    );
                    break;
                case 'create_ban':
                    $this->parseResponse(
                        $api->addBan(
                            $service_fields->teamspeak_sid,
                            (isset($post['ip_address']) ? $post['ip_address'] : null),
                            (isset($post['reason']) ? $post['reason'] : null)
                        )
                    );
                    break;
                default:
                    break;
            }
        }

        // Get virtual server bans
        $server_bans = $api->listBans($service_fields->teamspeak_sid);

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('server_bans', $server_bans);
        $this->view->set('vars', (isset($vars) ? (object) $vars : new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'teamspeak' . DS);

        return $this->view->fetch();
    }

    /**
     * Server Tokens tab.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabTokens($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Get module row
        $row = $this->getModuleRow();

        $this->view = new View('tab_tokens', 'default');
        $this->view->base_uri = $this->base_uri;

        // Initialize API
        $api = $this->getApi(
            $row->meta->hostname,
            $row->meta->username,
            $row->meta->password,
            $row->meta->port
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Perform actions
        if (!empty($post)) {
            switch ($post['action']) {
                case 'create_token':
                    $this->parseResponse(
                        $api->createPrivilegeKey(
                            $service_fields->teamspeak_sid,
                            (isset($post['sgid']) ? $post['sgid'] : null),
                            (isset($post['description']) ? $post['description'] : null)
                        )
                    );
                    break;
                case 'delete_token':
                    $this->parseResponse(
                        $api->deletePrivilegeKey($service_fields->teamspeak_sid, (isset($post['token']) ? $post['token'] : null))
                    );
                    break;
                default:
                    break;
            }
        }

        // Get virtual server tokens
        $tokens = $api->listPrivilegeKeys($service_fields->teamspeak_sid);

        // Get server groups
        $server_groups = $api->listServerGroups($service_fields->teamspeak_sid);

        $server_groups_options = [];
        if (isset($server_groups->server_groups)) {
            foreach ($server_groups->server_groups as $server_group) {
                $server_groups_options[$server_group->sgid] = $server_group->name;
            }
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('tokens', $tokens);
        $this->view->set('server_groups_options', $server_groups_options);
        $this->view->set('vars', (isset($vars) ? (object) $vars : new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'teamspeak' . DS);

        return $this->view->fetch();
    }

    /**
     * Server Logs tab.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabLogs($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Get module row
        $row = $this->getModuleRow();

        $this->view = new View('tab_logs', 'default');
        $this->view->base_uri = $this->base_uri;

        // Initialize API
        $api = $this->getApi(
            $row->meta->hostname,
            $row->meta->username,
            $row->meta->password,
            $row->meta->port
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Get virtual server log
        $log = $api->getLog($service_fields->teamspeak_sid);

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('log', $log);
        $this->view->set('vars', (isset($vars) ? (object) $vars : new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'teamspeak' . DS);

        return $this->view->fetch();
    }

    /**
     * Client Actions.
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
        // Get module row
        $row = $this->getModuleRow();

        $this->view = new View('tab_client_actions', 'default');
        $this->view->base_uri = $this->base_uri;

        // Initialize API
        $api = $this->getApi(
            $row->meta->hostname,
            $row->meta->username,
            $row->meta->password,
            $row->meta->port
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Perform actions
        if (!empty($post)) {
            switch ($post['action']) {
                case 'restart':
                    $this->parseResponse($api->restartServer($service_fields->teamspeak_sid));
                    break;
                case 'stop':
                    $this->parseResponse($api->stopServer($service_fields->teamspeak_sid));
                    break;
                case 'start':
                    $this->parseResponse($api->startServer($service_fields->teamspeak_sid));
                    break;
                case 'change_name':
                    // Update the service name
                    Loader::loadModels($this, ['Services']);
                    $this->Services->editField(
                        $service->id,
                        ['key' => 'teamspeak_name', 'value' => (isset($post['name']) ? $post['name'] : null)]
                    );

                    if (($errors = $this->Services->errors())) {
                        $this->Input->setErrors($errors);
                    }

                    $params = [
                        'name' => (isset($post['name']) ? $post['name'] : null),
                        'maxclients' => $package->meta->maxclients,
                        'port' => $service_fields->teamspeak_port
                    ];
                    $this->parseResponse($api->editServer($service_fields->teamspeak_sid, $params));
                    break;
                case 'remove_ban':
                    $this->parseResponse($api->deleteAllBans($service_fields->teamspeak_sid));
                    break;
                case 'reset_permissions':
                    $this->parseResponse($api->resetPermissions($service_fields->teamspeak_sid));
                    break;
                default:
                    break;
            }
        }

        // Get virtual server information
        $server_info = $this->parseResponse($api->getServerState($service_fields->teamspeak_sid));

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('server_info', $server_info);
        $this->view->set('vars', (isset($vars) ? (object) $vars : new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'teamspeak' . DS);

        return $this->view->fetch();
    }

    /**
     * Client Server Clients.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientClients($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Get module row
        $row = $this->getModuleRow();

        $this->view = new View('tab_client_clients', 'default');
        $this->view->base_uri = $this->base_uri;

        // Initialize API
        $api = $this->getApi(
            $row->meta->hostname,
            $row->meta->username,
            $row->meta->password,
            $row->meta->port
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Perform actions
        if (!empty($post)) {
            switch ($post['action']) {
                case 'kick_client':
                    $this->parseResponse(
                        $api->kickClient($service_fields->teamspeak_sid, (isset($post['clid']) ? $post['clid'] : null))
                    );
                    break;
                default:
                    break;
            }
        }

        // Get virtual server clients
        $server_clients = $api->listClients($service_fields->teamspeak_sid);

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('server_clients', $server_clients);
        $this->view->set('vars', (isset($vars) ? (object) $vars : new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'teamspeak' . DS);

        return $this->view->fetch();
    }

    /**
     * Client Server Bans.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientBans($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Get module row
        $row = $this->getModuleRow();

        $this->view = new View('tab_client_bans', 'default');
        $this->view->base_uri = $this->base_uri;

        // Initialize API
        $api = $this->getApi(
            $row->meta->hostname,
            $row->meta->username,
            $row->meta->password,
            $row->meta->port
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Perform actions
        if (!empty($post)) {
            switch ($post['action']) {
                case 'unban_client':
                    $this->parseResponse(
                        $api->deleteBan($service_fields->teamspeak_sid, (isset($post['banid']) ? $post['banid'] : null))
                    );
                    break;
                case 'create_ban':
                    $this->parseResponse(
                        $api->addBan(
                            $service_fields->teamspeak_sid,
                            (isset($post['ip_address']) ? $post['ip_address'] : null),
                            (isset($post['reason']) ? $post['reason'] : null)
                        )
                    );
                    break;
                default:
                    break;
            }
        }

        // Get virtual server bans
        $server_bans = $api->listBans($service_fields->teamspeak_sid);

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('server_bans', $server_bans);
        $this->view->set('vars', (isset($vars) ? (object) $vars : new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'teamspeak' . DS);

        return $this->view->fetch();
    }

    /**
     * Client Server Tokens.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientTokens($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Get module row
        $row = $this->getModuleRow();

        $this->view = new View('tab_client_tokens', 'default');
        $this->view->base_uri = $this->base_uri;

        // Initialize API
        $api = $this->getApi(
            $row->meta->hostname,
            $row->meta->username,
            $row->meta->password,
            $row->meta->port
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Perform actions
        if (!empty($post)) {
            switch ($post['action']) {
                case 'create_token':
                    $this->parseResponse(
                        $api->createPrivilegeKey(
                            $service_fields->teamspeak_sid,
                            (isset($post['sgid']) ? $post['sgid'] : null),
                            (isset($post['description']) ? $post['description'] : null)
                        )
                    );
                    break;
                case 'delete_token':
                    $this->parseResponse(
                        $api->deletePrivilegeKey($service_fields->teamspeak_sid, (isset($post['token']) ? $post['token'] : null))
                    );
                    break;
                default:
                    break;
            }
        }

        // Get virtual server tokens
        $tokens = $api->listPrivilegeKeys($service_fields->teamspeak_sid);

        // Get server groups
        $server_groups = $api->listServerGroups($service_fields->teamspeak_sid);

        $server_groups_options = [];
        if (isset($server_groups->server_groups)) {
            foreach ($server_groups->server_groups as $server_group) {
                $server_groups_options[$server_group->sgid] = $server_group->name;
            }
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('tokens', $tokens);
        $this->view->set('server_groups_options', $server_groups_options);
        $this->view->set('vars', (isset($vars) ? (object) $vars : new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'teamspeak' . DS);

        return $this->view->fetch();
    }

    /**
     * Client Server Logs.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientLogs($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Get module row
        $row = $this->getModuleRow();

        $this->view = new View('tab_client_logs', 'default');
        $this->view->base_uri = $this->base_uri;

        // Initialize API
        $api = $this->getApi(
            $row->meta->hostname,
            $row->meta->username,
            $row->meta->password,
            $row->meta->port
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Get virtual server log
        $log = $api->getLog($service_fields->teamspeak_sid);

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('log', $log);
        $this->view->set('vars', (isset($vars) ? (object) $vars : new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'teamspeak' . DS);

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
        if (strlen($host_name) > 255) {
            return false;
        }

        return $this->Input->matches(
            $host_name,
            '/^([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9])(\.([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9]))+$/i'
        );
    }

    /**
     * Retrieves the accounts on the server.
     *
     * @param stdClass $api The TeamSpeak API
     * @return mixed The number of accounts on the server, or false on error
     */
    private function getAccountCount($api)
    {
        $accounts = false;

        try {
            $output = $api->listServers();

            if ($output->status) {
                $accounts = count($output->servers);
            } elseif (isset($output->code) && $output->code == 1281) {
                $accounts = 0;
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
     * @param mixed $increase
     */
    private function updateAccountCount($module_row, $increase = true)
    {
        // Initialize API
        $api = $this->getApi(
            $module_row->meta->hostname,
            $module_row->meta->username,
            $module_row->meta->password,
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
     * @param mixed $port
     * @return bool True if the connection is valid, false otherwise
     */
    public function validateConnection($password, $hostname, $username, $port)
    {
        try {
            $api = $this->getApi($hostname, $username, $password, $port);
            $servers = $api->listServers();

            return is_object($servers);
        } catch (Exception $e) {
            // Trap any errors encountered, could not validate connection
        }

        return false;
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
        $row = $this->getModuleRow();

        $fields = [
            'name' => !empty($vars['teamspeak_name']) ? $vars['teamspeak_name'] : null,
            'port' => !empty($vars['teamspeak_port']) ? $vars['teamspeak_port'] : null
        ];

        $fields = array_merge((array) $package->meta, $fields);

        return $fields;
    }

    /**
     * Parses the response from the API into a stdClass object.
     *
     * @param stdClass $response The response from the API
     * @return stdClass A stdClass object representing the response, void if the response was an error
     */
    private function parseResponse($response)
    {
        $row = $this->getModuleRow();
        $success = true;

        if (!$response->status) {
            $this->Input->setErrors(['api' => ['error' => $response->error]]);
            $success = false;
        }

        // Set internal error
        if (empty($response)) {
            $this->Input->setErrors(['api' => ['internal' => Language::_('Teamspeak.!error.api.internal', true)]]);
            $success = false;
        }

        // Log the response
        $this->log($row->meta->hostname, serialize($response), 'output', $success);

        // Return if any errors encountered
        if (!$success) {
            return;
        }

        return $response;
    }

    /**
     * Initializes the TeamspeakApi and returns an instance of that object with the given $host, $user, and $pass set.
     *
     * @param string $host The host to the TeamSpeak server
     * @param string $user The user to connect as
     * @param string $pass The hash-pased password to authenticate with
     * @param mixed $hostname
     * @param mixed $username
     * @param mixed $password
     * @param mixed $port
     * @return TeamspeakApi The TeamspeakApi instance
     */
    private function getApi($hostname, $username, $password, $port = 10011)
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'teamspeak_api.php');

        $api = new TeamspeakApi($hostname, $username, $password, $port);

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
                    'message' => Language::_('Teamspeak.!error.server_name_valid', true)
                ]
            ],
            'hostname' => [
                'valid' => [
                    'rule' => function ($host_name) {
                        $validator = new Server();
                        return $validator->isDomain($host_name) || $validator->isIp($host_name);
                    },
                    'message' => Language::_('Teamspeak.!error.host_name_valid', true)
                ]
            ],
            'username' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Teamspeak.!error.user_name_valid', true)
                ]
            ],
            'password' => [
                'valid' => [
                    'last' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Teamspeak.!error.password_valid', true)
                ],
                'valid_connection' => [
                    'rule' => [
                        [$this, 'validateConnection'],
                        $vars['hostname'],
                        $vars['username'],
                        $vars['port']
                    ],
                    'message' => Language::_('Teamspeak.!error.password_valid_connection', true)
                ]
            ],
            'account_limit' => [
                'valid' => [
                    'rule' => ['matches', '/^([0-9]+)?$/'],
                    'message' => Language::_('Teamspeak.!error.account_limit_valid', true)
                ]
            ],
            'port' => [
                'valid' => [
                    'rule' => ['matches', '/^([0-9]+)$/'],
                    'message' => Language::_('Teamspeak.!error.port_valid', true)
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
    private function getPackageRules()
    {
        $rules = [
            'meta[maxclients]' => [
                'valid' => [
                    'rule' => ['matches', '/^([0-9]+)$/'],
                    'message' => Language::_('Teamspeak.!error.meta[maxclients].valid', true),
                ]
            ]
        ];

        return $rules;
    }
}
