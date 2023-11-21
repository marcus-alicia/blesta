<?php
use Blesta\Core\Util\Validate\Server;
/**
 * Whmsonic Module.
 *
 * @package blesta
 * @subpackage blesta.components.modules.whmsonic
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @see http://www.blesta.com/ Blesta
 */
class Whmsonic extends Module
{
    /**
     * Initializes the module.
     */
    public function __construct()
    {
        // Load configuration required by this module
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this module
        Loader::loadComponents($this, ['Input', 'Net']);
        $this->Http = $this->Net->create('Http');

        // Load the language required by this module
        Language::loadLang('whmsonic', null, dirname(__FILE__) . DS . 'language' . DS);
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
        return [
            'tabStats' => Language::_('Whmsonic.tab_stats', true)
        ];
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
        return [
            'tabClientActions' => Language::_('Whmsonic.tab_client_actions', true),
            'tabClientStats' => Language::_('Whmsonic.tab_stats', true)
        ];
    }

    /**
     * Returns an array of available service deligation order methods. The module
     * will determine how each method is defined. For example, the method "first"
     * may be implemented such that it returns the module row with the least number
     * of services assigned to it.
     *
     * @return array An array of order methods in key/value paris where the key is the type
     *  to be stored for the group and value is the name for that option
     * @see Module::selectModuleRow()
     */
    public function getGroupOrderOptions()
    {
        return ['first' => Language::_('Whmsonic.order_options.first', true)];
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
     * @return ModuleFields A ModuleFields object, containing the fields to render as
     *  well as any additional HTML markup to include
     */
    public function getPackageFields($vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

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

        $client_type = $fields->label(Language::_('Whmsonic.package_fields.client_type', true), 'client_type');
        $client_type->attach(
            $fields->fieldSelect(
                'meta[client_type]',
                $this->getClientTypes(),
                (isset($vars->meta['client_type']) ? $vars->meta['client_type'] : null)
            ),
            ['id' => 'client_type']
        );
        $fields->setField($client_type);

        $bitrate = $fields->label(Language::_('Whmsonic.package_fields.bitrate', true), 'bitrate');
        $bitrate->attach(
            $fields->fieldSelect(
                'meta[bitrate]',
                $this->getBitRates(),
                (isset($vars->meta['bitrate']) ? $vars->meta['bitrate'] : null)
            ),
            ['id' => 'bitrate']
        );
        $fields->setField($bitrate);

        $hspace = $fields->label(Language::_('Whmsonic.package_fields.hspace', true), 'hspace');
        $hspace->attach(
            $fields->fieldText('meta[hspace]', (isset($vars->meta['hspace']) ? $vars->meta['hspace'] : null)),
            ['id' => 'hspace']
        );
        $fields->setField($hspace);

        $bandwidth = $fields->label(Language::_('Whmsonic.package_fields.bandwidth', true), 'bandwidth');
        $bandwidth->attach(
            $fields->fieldText('meta[bandwidth]', (isset($vars->meta['bandwidth']) ? $vars->meta['bandwidth'] : null)),
            ['id' => 'bandwidth']
        );
        $fields->setField($bandwidth);

        $listeners = $fields->label(Language::_('Whmsonic.package_fields.listeners', true), 'listeners');
        $listeners->attach(
            $fields->fieldText('meta[listeners]', (isset($vars->meta['listeners']) ? $vars->meta['listeners'] : null)),
            ['id' => 'listeners']
        );
        $fields->setField($listeners);

        $autodj = $fields->label(Language::_('Whmsonic.package_fields.autodj', true), 'autodj');
        $autodj->attach(
            $fields->fieldSelect(
                'meta[autodj]',
                $this->getAutoDJAccessOptions(),
                (isset($vars->meta['autodj']) ? $vars->meta['autodj'] : null)
            ),
            ['id' => 'autodj']
        );
        $fields->setField($autodj);

        return $fields;
    }

    /**
     * Retrieves a list of client types and language
     *
     * @return array A key/value array of client types and their language
     */
    private function getClientTypes()
    {
        return [
            'External' => Language::_('Whmsonic.package_fields.client_type.external', true),
            'internal' => Language::_('Whmsonic.package_fields.client_type.internal', true),
        ];
    }

    /**
     * Retrieves a list of bit rates and language
     *
     * @return array A key/value array of bit rates and their language
     */
    private function getBitRates()
    {
        return [
            '32' => '32',
            '64' => '64',
            '128' => '128',
        ];
    }

    /**
     * Retrieves a list of AutoDJ options and language
     *
     * @return array A key/value array of options and their language
     */
    private function getAutoDJAccessOptions()
    {
        return [
            'yes' => Language::_('Whmsonic.package_fields.autodj.yes', true),
            'no' => Language::_('Whmsonic.package_fields.autodj.no', true),
        ];
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'whmsonic' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'whmsonic' . DS);

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
     * @param array $vars An array of post data submitted to or on the edit module
     *  row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the edit module row page
     */
    public function manageEditRow($module_row, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('edit_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'whmsonic' . DS);
        $this->Input->setRules($this->getRowRules($vars));

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
     *    - key The key for this meta field
     *    - value The value for this key
     *    - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function addModuleRow(array &$vars)
    {
        $meta_fields = ['server_name', 'ip_address', 'use_ssl', 'password'];
        $encrypted_fields = ['password'];

        // Set unspecified checkboxes
        if (empty($vars['use_ssl'])) {
            $vars['use_ssl'] = 'false';
        }

        $this->Input->setRules($this->getRowRules($vars));

        // Validate module row
        if ($this->Input->validates($vars)) {
            $vars['ip_address'] = strtolower($vars['ip_address']);

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
     *    - key The key for this meta field
     *    - value The value for this key
     *    - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function editModuleRow($module_row, array &$vars)
    {
        $meta_fields = ['server_name', 'ip_address', 'use_ssl', 'password'];
        $encrypted_fields = ['password'];

        // Set unspecified checkboxes
        if (empty($vars['use_ssl'])) {
            $vars['use_ssl'] = 'false';
        }

        $this->Input->setRules($this->getRowRules($vars));

        // Validate module row
        if ($this->Input->validates($vars)) {
            $vars['ip_address'] = strtolower($vars['ip_address']);

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
     * @return ModuleFields A ModuleFields object, containg the fields to render as well
     *  as any additional HTML markup to include
     */
    public function getAdminAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        if ($package->meta->client_type == 'internal') {
            $username = $fields->label(Language::_('Whmsonic.service_field.cpanel_username', true), 'username');
            $username->attach(
                $fields->fieldText(
                    'username',
                    (isset($vars->username) ? $vars->username : null),
                    ['id' => 'username']
                )
            );
            $fields->setField($username);

            $password = $fields->label(Language::_('Whmsonic.service_field.password', true), 'password');
            $password->attach($fields->fieldPassword('password', ['id' => 'password']));
            $fields->setField($password);
        }

        return $fields;
    }

    /**
     * Returns all fields to display to a client attempting to add a service with the module.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as well as
     *  any additional HTML markup to include
     */
    public function getClientAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        if ($package->meta->client_type == 'internal') {
            $username = $fields->label(Language::_('Whmsonic.service_field.cpanel_username', true), 'username');
            $username->attach(
                $fields->fieldText(
                    'username',
                    (isset($vars->username) ? $vars->username : null),
                    ['id' => 'username']
                )
            );
            $fields->setField($username);

            $password = $fields->label(Language::_('Whmsonic.service_field.password', true), 'password');
            $password->attach($fields->fieldPassword('password', ['id' => 'password']));
            $fields->setField($password);
        }

        return $fields;
    }

    /**
     * Returns all fields to display to an admin attempting to edit a service with the module.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as well
     *  as any additional HTML markup to include
     */
    public function getAdminEditFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        $username = $fields->label(Language::_('Whmsonic.service_field.username', true), 'username');
        $username->attach(
            $fields->fieldText(
                'username',
                (isset($vars->username) ? $vars->username : null),
                ['id' => 'username']
            )
        );
        $username->attach($fields->tooltip(Language::_('Whmsonic.service_field.username.tooltip', true)));
        $fields->setField($username);

        $radio_password = $fields->label(
            Language::_('Whmsonic.service_field.radio_password', true),
            'radio_password'
        );
        $radio_password->attach($fields->fieldPassword('radio_password', ['id' => 'radio_password']));
        $radio_password->attach($fields->tooltip(Language::_('Whmsonic.service_field.password.tooltip', true)));
        $fields->setField($radio_password);

        $password = $fields->label(Language::_('Whmsonic.service_field.password', true), 'password');
        $password->attach($fields->fieldPassword('password', ['id' => 'password']));
        $password->attach($fields->tooltip(Language::_('Whmsonic.service_field.password.tooltip', true)));
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
            'username' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Whmsonic.!error.user_name.empty', true)
                ]
            ],
        ];

        // Set the values that may be empty
        if ($edit) {
            if (!array_key_exists('username', $vars) || $vars['username'] == '') {
                unset($rules['username']);
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
     * @param stdClass $parent_package A stdClass object representing the parent service's
     *  selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service of
     *  the service being added (if the current service is an addon service service and parent
     *  service has already been provisioned)
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
        $params = [];

        if (!$row) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Whmsonic.!error.module_row.missing', true)]]
            );

            return;
        }

        Loader::loadModels($this, ['Clients']);

        if (isset($vars['client_id']) && ($client = $this->Clients->get($vars['client_id'], false))) {
            $params['client_email'] = $client->email;
            $params['client_name'] = $client->first_name . ' ' . $client->last_name;
        }

        if ($package->meta->client_type == 'internal') {
            $params['username'] = !empty($vars['username']) ? $vars['username'] : null;
        } else {
            $params['username'] = $this->generateUsername($client->first_name . $client->last_name);
        }
        $params['radio_password'] = $this->generatePassword(10, 14);

        $params['bitrate'] = $package->meta->bitrate;
        $params['hspace'] = $package->meta->hspace;
        $params['autodj'] = $package->meta->autodj;
        $params['bandwidth'] = $package->meta->bandwidth;
        $params['listeners'] = $package->meta->listeners;

        $this->validateService($package, $params);

        if ($this->Input->errors()) {
            return;
        }

        $api = $this->getApi($row->meta->password, $row->meta->ip_address, $row->meta->use_ssl);
        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {

            $masked_params = $params;
            $masked_params['radio_password'] = '***';

            $this->log($row->meta->ip_address . '|create', serialize($masked_params), 'input', true);
            $response = $this->parseResponse($api->createRadio($params, $package->meta->client_type));

            if ($this->Input->errors()) {
                return;
            }
        }

        // Return service fields
        return [
            [
                'key' => 'whmsonic_username',
                'value' => $params['username'],
                'encrypted' => 0
            ],
            [
                'key' => 'whmsonic_password',
                'value' => !empty($vars['password']) ? $vars['password'] : $params['radio_password'],
                'encrypted' => 1
            ],
            [
                'key' => 'whmsonic_ip_address',
                'value' => $row->meta->ip_address,
                'encrypted' => 0
            ],
            [
                'key' => 'whmsonic_ftp',
                'value' => $api->ftpAccountPermissions(
                    $row->meta->ip_address,
                    $params['username'],
                    !empty($vars['password']) ? $vars['password'] : $params['radio_password']
                ),
                'encrypted' => 0
            ],
            [
                'key' => 'whmsonic_radio_ip',
                'value' => $row->meta->ip_address,
                'encrypted' => 1
            ],
            [
                'key' => 'whmsonic_radio_password',
                'value' => $params['radio_password'],
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
     * @param stdClass $parent_package A stdClass object representing the parent service's selected
     *  package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service of the
     *  service being edited (if the current service is an addon service)
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *    - key The key for this meta field
     *    - value The value for this key
     *    - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function editService($package, $service, array $vars = null, $parent_package = null, $parent_service = null)
    {
        $this->validateServiceEdit($service, $vars);

        if ($this->Input->errors()) {
            return;
        }

        $service_fields = $this->serviceFieldsToObject($service->fields);

        $vars['bitrate'] = $package->meta->bitrate;
        $vars['hspace'] = $package->meta->hspace;
        $vars['autodj'] = $package->meta->autodj;
        $vars['bandwidth'] = $package->meta->bandwidth;
        $vars['listeners'] = $package->meta->listeners;

        if (empty($vars['username'])) {
            $vars['username'] = $service_fields->whmsonic_username;
        }

        if (empty($vars['password'])) {
            $vars['password'] = $service_fields->whmsonic_password;
        }

        if (empty($vars['radio_password'])) {
            $vars['radio_password'] = $service_fields->whmsonic_radio_password;
        }

        // Return service fields
        return [
            [
                'key' => 'whmsonic_username',
                'value' => $vars['username'],
                'encrypted' => 0
            ],
            [
                'key' => 'whmsonic_password',
                'value' => $vars['password'],
                'encrypted' => 1
            ],
            [
                'key' => 'whmsonic_ip_address',
                'value' => $service_fields->whmsonic_ip_address,
                'encrypted' => 0
            ],
            [
                'key' => 'whmsonic_ftp',
                'value' => $service_fields->whmsonic_ftp,
                'encrypted' => 0
            ],
            [
                'key' => 'whmsonic_radio_ip',
                'value' => $service_fields->whmsonic_radio_ip,
                'encrypted' => 1
            ],
            [
                'key' => 'whmsonic_radio_password',
                'value' => $vars['radio_password'],
                'encrypted' => 1
            ]
        ];
    }

    /**
     * Suspends the service on the remote server. Sets Input errors on failure,
     * preventing the service from being suspended.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent service's
     *  selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service of
     *  the service being suspended (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically indexed array of
     *  meta fields to be stored for this service containing:
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

            $api = $this->getApi($row->meta->password, $row->meta->ip_address, $row->meta->use_ssl);

            $this->log(
                $row->meta->ip_address . '|suspend',
                serialize($service_fields->whmsonic_username),
                'input',
                true
            );
            $response = $api->suspendRadio($service_fields->whmsonic_username);
            $this->log($row->meta->ip_address . '|suspend', serialize($response), 'output', $response['status']);

            // If the action fails then set an error
            if (!$response['status']) {
                $this->Input->setErrors(['api' => ['error' => Language::_('Whmsonic.!error.api.internal', true)]]);
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
     * @param stdClass $parent_package A stdClass object representing the parent service's
     *  selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service of
     *  the service being unsuspended (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically indexed array
     *  of meta fields to be stored for this service containing:
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

            $api = $this->getApi($row->meta->password, $row->meta->ip_address, $row->meta->use_ssl);

            $this->log(
                $row->meta->ip_address . '|unsuspend',
                serialize($service_fields->whmsonic_username),
                'output',
                $response['status']
            );
            $response = $api->unSuspendRadio($service_fields->whmsonic_username);
            $this->log($row->meta->ip_address . '|unsuspend', serialize($response), 'output', $response['status']);

            // If the action fails then set an error
            if (!$response['status']) {
                $this->Input->setErrors(['api' => ['error' => Language::_('Whmsonic.!error.api.internal', true)]]);
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
     * @param stdClass $parent_package A stdClass object representing the parent service's
     *  selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service of
     *  the service being canceled (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically indexed
     *  array of meta fields to be stored for this service containing:
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

            $api = $this->getApi($row->meta->password, $row->meta->ip_address, $row->meta->use_ssl);

            $this->log(
                $row->meta->ip_address . '|terminate',
                serialize([$service_fields->whmsonic_username]),
                'input',
                true
            );
            $response = $api->terminateRadio($service_fields->whmsonic_username);
            $this->log($row->meta->ip_address . '|terminate', serialize($response), 'output', $response['status']);

            // If the action fails then set an error
            if (!$response['status']) {
                $this->Input->setErrors(['api' => ['error' => Language::_('Whmsonic.!error.api.internal', true)]]);
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'whmsonic' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'whmsonic' . DS);

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
            $data = [
                'password' => (isset($post['password']) ? $post['password'] : null),
                'radio_password' => (isset($post['radio_password']) ? $post['radio_password'] : null)
            ];
            $this->Services->edit($service->id, $data);

            if ($this->Services->errors()) {
                $this->Input->setErrors($this->Services->errors());
            }

            $vars = (object)$post;
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('vars', (isset($vars) ? $vars : new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'whmsonic' . DS);
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
        $this->view->base_uri = $this->base_uri;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $service_fields = $this->serviceFieldsToObject($service->fields);


        $display_fields = [
            'bitrate' => $this->getBitRates(),
            'autodj' => $this->getAutoDJAccessOptions(),
            'client_type' => $this->getClientTypes()
        ];

        foreach ($display_fields as $display_field => $options) {
            if (isset($package->meta->{$display_field}) && isset($options[$package->meta->{$display_field}])) {
                $package->meta->{$display_field} = $options[$package->meta->{$display_field}];
            }
        }

        $this->view->set('package_fields', $package->meta);
        $this->view->set('service_fields', $service_fields);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'whmsonic' . DS);
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
        $this->view->base_uri = $this->base_uri;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $service_fields = $this->serviceFieldsToObject($service->fields);


        $display_fields = [
            'bitrate' => $this->getBitRates(),
            'autodj' => $this->getAutoDJAccessOptions(),
            'client_type' => $this->getClientTypes()
        ];

        foreach ($display_fields as $display_field => $options) {
            if (isset($package->meta->{$display_field}) && isset($options[$package->meta->{$display_field}])) {
                $package->meta->{$display_field} = $options[$package->meta->{$display_field}];
            }
        }

        $this->view->set('package_fields', $package->meta);
        $this->view->set('service_fields', $service_fields);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'whmsonic' . DS);
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
     * Generates a password.
     *
     * @param int $min_length The minimum character length for the password (5 or larger)
     * @param int $max_length The maximum character length for the password (14 or fewer)
     * @return string The generated password
     */
    private function generatePassword($min_length = 10, $max_length = 14)
    {
        $pool = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $pool_size = strlen($pool);
        $length = mt_rand(max($min_length, 5), min($max_length, 14));
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= substr($pool, mt_rand(0, $pool_size - 1), 1);
        }

        return $password;
    }

    /**
     * Generates mostly random username partially based on the client's name.
     *
     * @param mixed $name The client's name
     * @return string The username generated from the given client name
     */
    private function generateUsername($name)
    {
        // Use the first two characters if the name
        $username = substr(str_replace(' ', '', strtolower($name)), 0, 2);
        $length = strlen($username);

        $pool = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $pool_size = strlen($pool);

        for ($i = $length; $i < 8; $i++) {
            $username .= substr($pool, mt_rand(0, $pool_size - 1), 1);
        }

        return 'sc_' . $username;
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

        if ($response['status'] == false) {
            $this->Input->setErrors(['api' => ['error' => Language::_('Whmsonic.!error.api.internal', true)]]);
            $success = false;
        }

        // Log the response
        $this->log($row->meta->ip_address, serialize($response), 'output', $success);

        // Return if any errors encountered
        if (!$success) {
            return;
        }

        return $response;
    }

    /**
     * Initialize the API library.
     *
     * @param string $password The whmsonic password
     * @param string $ip_address The ip address of the server
     * @param bool $use_ssl Whether to use https or http
     * @return WhmsonicApi the WhmsonicApi instance, or false if the loader fails to load the file
     */
    private function getApi($password, $ip_address, $use_ssl)
    {
        Loader::load(dirname(__FILE__) . DS . 'api' . DS . 'whmcsonic_api.php');

        return new WhmsonicApi($password, $ip_address, $use_ssl);
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
                    'message' => Language::_('Whmsonic.!error.server_name_valid', true)
                ]
            ],
            'ip_address' => [
                'valid' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Whmsonic.!error.ip_address_valid', true)
                ]
            ],
            'password' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Whmsonic.!error.password_valid', true)
                ],
                'valid_connection' => [
                    'rule' => [
                        [$this, 'validateConnection'],
                        isset($vars['ip_address']) ? $vars['ip_address'] : '',
                        isset($vars['use_ssl']) ? $vars['use_ssl'] : 'false'
                    ],
                    'message' => Language::_('Whmsonic.!error.api.internal', true)
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Builds and returns rules required to be validated when adding/editing a package.
     *
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getPackageRules()
    {
        $rules = [
            'meta[client_type]' => [
                'valid' => [
                    'rule' => ['in_array', array_keys($this->getClientTypes())],
                    'message' => Language::_('Whmsonic.!error.meta[client_type].valid', true)
                ]
            ],
            'meta[bitrate]' => [
                'valid' => [
                    'rule' => ['in_array', array_keys($this->getBitRates())],
                    'message' => Language::_('Whmsonic.!error.meta[bitrate].valid', true)
                ]
            ],
            'meta[hspace]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Whmsonic.!error.meta[hspace].empty', true)
                ]
            ],
            'meta[bandwidth]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Whmsonic.!error.meta[bandwidth].empty', true)
                ]
            ],
            'meta[listeners]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Whmsonic.!error.meta[listeners].empty', true)
                ]
            ],
            'meta[autodj]' => [
                'valid' => [
                    'rule' => ['in_array', array_keys($this->getAutoDJAccessOptions())],
                    'message' => Language::_('Whmsonic.!error.meta[autodj].valid', true)
                ]
            ],
        ];

        return $rules;
    }

    /**
     * Validates whether or not the connection details are valid by attempting to terminate a non-existent radio
     *
     * @return bool True if the connection is valid, false otherwise
     */
    public function validateConnection($password, $ip_address, $use_ssl)
    {
        try {
            $api = $this->getApi($password, $ip_address, $use_ssl);

            // Test connection by terminating a non-existent radio
            $result = $api->terminateRadio(['_#$%^3456#$%^#456[{g']);

            // Log the response
            $success = (isset($result['status']) && $result['status'] == true);
            $this->log($ip_address, serialize($result), 'output', $success);

            return $success;
        } catch (Exception $e) {
            // Trap any errors encountered, could not validate connection
        }
        return false;
    }
}
