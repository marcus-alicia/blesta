<?php
use Blesta\Core\Util\Validate\Server;

/**
 * Vesta Module
 *
 * @package blesta
 * @subpackage blesta.components.modules.vesta
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Vesta extends Module
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
        Language::loadLang('vesta', null, dirname(__FILE__) . DS . 'language' . DS);
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
            'tabStats' => Language::_('Vesta.tab_stats', true)
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
            'tabClientActions' => Language::_('Vesta.tab_client_actions', true),
            'tabClientStats' => Language::_('Vesta.tab_stats', true)
        ];
    }

    /**
     * Returns an array of available service deligation order methods. The module
     * will determine how each method is defined. For example, the method 'first'
     * may be implemented such that it returns the module row with the least number
     * of services assigned to it.
     *
     * @return array An array of order methods in key/value paris where the key is
     *  the type to be stored for the group and value is the name for that option
     * @see Module::selectModuleRow()
     */
    public function getGroupOrderOptions()
    {
        return ['first' => Language::_('Vesta.order_options.first', true)];
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
     * @return ModuleFields A ModuleFields object, containing the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getPackageFields($vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Fetch all packages available for the given server or server group
        $module_row = null;
        if (isset($vars->module_group)) {
            if ($vars->module_group == '' && isset($vars->module_row) && $vars->module_row > 0) {
                $module_row = $this->getModuleRow($vars->module_row);
            } else {
                // Fetch the 1st server from the list of servers in the selected group
                $rows = $this->getModuleRows($vars->module_group);
                if (isset($rows[0])) {
                    $module_row = $rows[0];
                }

                unset($rows);
            }
        }


        $hostname = $fields->label(Language::_('Vesta.package_fields.package_name', true), 'package_name');
        $hostname->attach(
            $fields->fieldText(
                'meta[package_name]',
                (isset($vars->meta['package_name'])
                    ? $vars->meta['package_name']
                    : (isset($vars->meta['package_name']) ? $vars->meta['package_name'] : null)
                ),
                ['id' => 'package_name']
            )
        );
        $hostname_tooltip = $fields->tooltip(Language::_('Vesta.package_fields.package_name_how_to', true));
        $hostname->attach($hostname_tooltip);
        $fields->setField($hostname);

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
     * @param array $vars An array of post data submitted to or on the manager module
     *  page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the manager module page
     */
    public function manageModule($module, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'vesta' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set('module', $module);

        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the add module row page
     *
     * @param array $vars An array of post data submitted to or on the add
     *  module row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the add module row page
     */
    public function manageAddRow(array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('add_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'vesta' . DS);

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
     * @param array $vars An array of post data submitted to or on the edit module
     *  row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the edit module row page
     */
    public function manageEditRow($module_row, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('edit_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'vesta' . DS);

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
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getAdminAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        $domain = $fields->label(Language::_('Vesta.service_field.domain', true), 'domain');
        $domain->attach(
            $fields->fieldText(
                'domain',
                (isset($vars->domain) ? $vars->domain : ($vars->domain ?? null)),
                ['id' => 'domain']
            )
        );
        $fields->setField($domain);

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

        $domain = $fields->label(Language::_('Vesta.service_field.domain', true), 'domain');
        $domain->attach(
            $fields->fieldText(
                'domain',
                (isset($vars->domain) ? $vars->domain : ($vars->domain ?? null)),
                ['id' => 'domain']
            )
        );
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

        $fields = new ModuleFields();

        $domain = $fields->label(Language::_('Vesta.service_field.domain', true), 'domain');
        $domain->attach(
            $fields->fieldText(
                'domain',
                (isset($vars->domain) ? $vars->domain : ($vars->domain ?? null)),
                ['id' => 'domain']
            )
        );
        $domain_tooltip = $fields->tooltip(Language::_('Vesta.stored_locally_only', true));
        $domain->attach($domain_tooltip);
        $fields->setField($domain);

        $username = $fields->label(Language::_('Vesta.service_field.username', true), 'username');
        $username->attach(
            $fields->fieldText(
                'username',
                (isset($vars->username) ? $vars->username : ($vars->username ?? null)),
                ['id' => 'username']
            )
        );
        $username_tooltip = $fields->tooltip(Language::_('Vesta.stored_locally_only', true));
        $username->attach($username_tooltip);
        $fields->setField($username);

        $password = $fields->label(Language::_('Vesta.service_field.password', true), 'password');
        $password->attach($fields->fieldPassword('password', ['id' => 'password']));
        $fields->setField($password);

        return $fields;
    }

    /**
     * Attempts to validate service info. This is the top-level error checking method. Sets Input errors on failure.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @param boolean $edit True if this is an edit, false otherwise
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
            'domain' => [
                'format' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Vesta.!error.domain.format', true)
                ],
                'test' => [
                    'rule' => ['substr_compare', 'test', 0, 4, true],
                    'message' => Language::_('Vesta.!error.domain.test', true)
                ]
            ],
            'username' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Vesta.!error.user_name.empty', true)
                ]
            ],
            'password' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['isPassword', 8],
                    'message' => Language::_('Vesta.!error.password.valid', true),
                    'last' => true
                ]
            ],
        ];


        // Set the values that may be empty
        if ($edit) {
            if (!array_key_exists('domain', $vars) || $vars['domain'] == '') {
                unset($rules['domain']);
            }

            if (!array_key_exists('username', $vars) || $vars['username'] == '') {
                unset($rules['username']);
            }

            if (!array_key_exists('password', $vars) || $vars['password'] == '') {
                unset($rules['password']);
            }
        } else {
            unset($rules['username']);
            unset($rules['password']);
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
     *  service of the service being added (if the current service is an addon
     *  service service and parent service has already been provisioned)
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
                ['module_row' => ['missing' => Language::_('Vesta.!error.module_row.missing', true)]]
            );

            return;
        }

        Loader::loadModels($this, ['Clients']);

        if (isset($vars['client_id']) && ($client = $this->Clients->get($vars['client_id'], false))) {
            $params['email'] = $client->email;
            $params['first_name'] = $client->first_name;
            $params['last_name'] = $client->last_name;
        }
        $params['package_name'] = $package->meta->package_name;
        $params['password'] = $this->generatePassword(10, 14);
        $params['domain'] = $vars['domain'];
        $params['username'] = $this->generateUsername($vars['domain']);
        $params['ssh_access'] = isset($vars['configoptions']['ssh_access'])
            ? $vars['configoptions']['ssh_access']
            : 'disable';

        $this->validateService($package, $params);


        if ($this->Input->errors()) {
            return;
        }

        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            $vesta = $this->getApi(
                $row->meta->host_name,
                $row->meta->user_name,
                $row->meta->port,
                $row->meta->password,
                $row->meta->use_ssl
            );

            // First create a user account
            $account_response = $vesta->createUserAccount($params);
            $this->log(
                $row->meta->host_name . '|v-add-user',
                serialize($account_response),
                'input',
                $account_response['status']
            );

            // If fails then set an error
            if ($account_response['status'] == 'false') {
                $this->Input->setErrors(
                    ['api_response' => ['missing' => Language::_('Vesta.!error.api.internal', true)]]
                );

                return;
            } else {
                // Second add a domain to that account
                $domain_response = $vesta->addDomain($params['username'], $params['domain']);
                $this->log(
                    $row->meta->host_name . '|v-add-domain',
                    serialize($domain_response),
                    'input',
                    $domain_response['status']
                );

                // if fails then set an error
                if ($domain_response['status'] == 'false') {
                    $this->Input->setErrors(
                        ['api_response' => ['missing' => Language::_('Vesta.!error.api.internal', true)]]
                    );

                    return;
                }

                // If config option is setup, and is set to enable, then enable the ssh access
                if ($params['ssh_access'] == 'enable') {
                    // Enable ssh access for the account
                    $ssh_response = $vesta->sshAccess($params['username'], 'enable');
                    $this->log(
                        $row->meta->host_name . '|v-change-user-shell',
                        serialize($ssh_response),
                        'input',
                        $ssh_response['status']
                    );

                    // if fails then set an error
                    if ($ssh_response['status'] == 'false') {
                        $this->Input->setErrors(
                            ['api_response' => ['missing' => Language::_('Vesta.!error.api.internal', true)]]
                        );

                        return;
                    }
                }
            }


            if ($this->Input->errors()) {
                return;
            }
        }

        // Return service fields
        return [
            [
                'key' => 'domain',
                'value' => $params['domain'],
                'encrypted' => 0
            ],
            [
                'key' => 'username',
                'value' => $params['username'],
                'encrypted' => 0
            ],
            [
                'key' => 'password',
                'value' => $params['password'],
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
     * @param stdClass $parent_package A stdClass object representing the parent service's
     *  selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service
     * of the service being edited (if the current service is an addon service)
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


        if (empty($vars['domain'])) {
            $vars['domain'] = $service_fields->domain;
        }

        if (empty($vars['username'])) {
            $vars['username'] = $service_fields->username;
        }

        if (empty($vars['password'])) {
            $vars['password'] = $service_fields->password;
        }

        // Only update the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            $vesta = $this->getApi(
                $row->meta->host_name,
                $row->meta->user_name,
                $row->meta->port,
                $row->meta->password,
                $row->meta->use_ssl
            );

            if ($service_fields->password != $vars['password']) {
                $response = $vesta->changeAccountPassword($vars['username'], $vars['password']);

                $this->log(
                    $row->meta->host_name . '|v-change-user-password',
                    serialize($response),
                    'input',
                    $response['status']
                );

                // if fails then set an error
                if ($response['status'] == 'false') {
                    $this->Input->setErrors(
                        ['api_response' => ['missing' => Language::_('Vesta.!error.api.internal', true)]]
                    );

                    return;
                }
            }

            // Check if the SSH Access is already enabled for the service
            $service_ssh_access = '';
            foreach ($service->options as $key => $value) {
                if (isset($service->options[$key]->option_name)) {
                    $service_ssh_access = $service->options[$key]->option_value;
                    break;
                }
            }

            // If the ssh access option is unchecked and the service already has the ssh access enabled then
            // disable the ssh access, but if the ssh access option is checked
            // and the service did not have the ssh access before then enable.
            if ((!isset($vars['configoptions']['ssh_access']) && $service_ssh_access == 'enable')
                || (isset($vars['configoptions']['ssh_access']) && $service_ssh_access == '')
            ) {
                $ssh_response = $vesta->sshAccess(
                    $vars['username'],
                    isset($vars['configoptions']['ssh_access']) ? $vars['configoptions']['ssh_access'] : 'disable'
                );
                $this->log(
                    $row->meta->host_name . '|v-change-user-shell',
                    serialize($ssh_response),
                    'input',
                    $ssh_response['status']
                );

                // if fails then set an error
                if ($ssh_response['status'] == 'false') {
                    $this->Input->setErrors(
                        ['api_response' => ['missing' => Language::_('Vesta.!error.api.internal', true)]]
                    );

                    return;
                }
            }
        }

        // Return service fields
        return [
            [
                'key' => 'domain',
                'value' => $vars['domain'],
                'encrypted' => 0
            ],
            [
                'key' => 'username',
                'value' => $vars['username'],
                'encrypted' => 0
            ],
            [
                'key' => 'password',
                'value' => $vars['password'],
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
     * @param stdClass $parent_package A stdClass object representing the parent service's
     *  selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service of
     *  the service being suspended (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically indexed
     *  array of meta fields to be stored for this service containing:
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

            $vesta = $this->getApi(
                $row->meta->host_name,
                $row->meta->user_name,
                $row->meta->port,
                $row->meta->password,
                $row->meta->use_ssl
            );

            $response = $vesta->suspendUserAccount($service_fields->username);

            $this->log($row->meta->host_name . '|v-suspend-user', serialize($response), 'input', $response['status']);

            // if fails then set an error
            if ($response['status'] == 'false') {
                $this->Input->setErrors(
                    ['api_response' => ['missing' => Language::_('Vesta.!error.api.internal', true)]]
                );

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

            $vesta = $this->getApi(
                $row->meta->host_name,
                $row->meta->user_name,
                $row->meta->port,
                $row->meta->password,
                $row->meta->use_ssl
            );

            $response = $vesta->unSuspendUserAccount($service_fields->username);

            $this->log($row->meta->host_name . '|v-unsuspend-user', serialize($response), 'input', $response['status']);

            // if fails then set an error
            if ($response['status'] == 'false') {
                $this->Input->setErrors(
                    ['api_response' => ['missing' => Language::_('Vesta.!error.api.internal', true)]]
                );

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
     * @param stdClass $parent_package A stdClass object representing the parent service's
     *  selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service of
     *  the service being canceled (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically indexed array
     *  of meta fields to be stored for this service containing:
     *    - key The key for this meta field
     *    - value The value for this key
     *    - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function cancelService(
        $package,
        $service,
        $parent_package = null,
        $parent_service = null
    ) {

        if (($row = $this->getModuleRow())) {
            $service_fields = $this->serviceFieldsToObject($service->fields);

            $vesta = $this->getApi(
                $row->meta->host_name,
                $row->meta->user_name,
                $row->meta->port,
                $row->meta->password,
                $row->meta->use_ssl
            );

            $response = $vesta->deleteUserAccount($service_fields->username);

            $this->log($row->meta->host_name . '|v-delete-user', serialize($response), 'input', $response['status']);

            // if fails then set an error
            if ($response['status'] == 'false') {
                $this->Input->setErrors(
                    ['api_response' => ['missing' => Language::_('Vesta.!error.api.internal', true)]]
                );

                return;
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
     * @param stdClass $parent_package A stdClass object representing the parent service's
     *  selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service of
     *  the service being changed (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically indexed array
     *  of meta fields to be stored for this service containing:
     *    - key The key for this meta field
     *    - value The value for this key
     *    - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
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
            $service_fields = $this->serviceFieldsToObject($service->fields);

            $vesta = $this->getApi(
                $row->meta->host_name,
                $row->meta->user_name,
                $row->meta->port,
                $row->meta->password,
                $row->meta->use_ssl
            );

            $response = $vesta->changeUserPackage($service_fields->username, $package_to->meta->package_name);

            $this->log(
                $row->meta->host_name . '|v-change-user-package',
                serialize($response),
                'input',
                $response['status']
            );

            // if fails then set an error
            if ($response['status'] == 'false') {
                $this->Input->setErrors(
                    ['api_response' => ['missing' => Language::_('Vesta.!error.api.internal', true)]]
                );

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'vesta' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'vesta' . DS);

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
        if (!empty($post['password']) && !empty($post['confirm_password'])) {
            Loader::loadModels($this, ['Services']);
            $data = [
                'password' => (isset($post['password']) ? $post['password'] : null),
                'confirm_password' => (isset($post['confirm_password']) ? $post['confirm_password'] : null)
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

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'vesta' . DS);
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
        $row = $this->getModuleRow();
        $this->view = new View('tab_client_stats', 'default');
        $this->view->base_uri = $this->base_uri;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $service_fields = $this->serviceFieldsToObject($service->fields);

        $vesta = $this->getApi(
            $row->meta->host_name,
            $row->meta->user_name,
            $row->meta->port,
            $row->meta->password,
            $row->meta->use_ssl
        );

        $response = $vesta->getAccountsUsage($service_fields->username);

        $this->log($row->meta->host_name . '|v-list-user', serialize($response), 'input', $response['status']);


        $this->view->set(
            'stats',
            isset($response[$service_fields->username]) ? $response[$service_fields->username] : null
        );
        $this->view->set('service_fields', $service_fields);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'vesta' . DS);
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
        $row = $this->getModuleRow();
        $this->view = new View('tab_stats', 'default');
        $this->view->base_uri = $this->base_uri;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $service_fields = $this->serviceFieldsToObject($service->fields);

        $vesta = $this->getApi(
            $row->meta->host_name,
            $row->meta->user_name,
            $row->meta->port,
            $row->meta->password,
            $row->meta->use_ssl
        );

        $response = $vesta->getAccountsUsage($service_fields->username);

        if ($response) {
            $this->log($row->meta->host_name . '|v-list-user', serialize($response), 'output', true);


            $this->view->set(
                'stats',
                isset($response[$service_fields->username]) ? $response[$service_fields->username] : null
            );
            $this->view->set('service_fields', $service_fields);

            $this->view->setDefaultView('components' . DS . 'modules' . DS . 'vesta' . DS);
            return $this->view->fetch();
        }

        $this->log($row->meta->host_name . '|v-list-user', serialize($response), 'output', false);
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
     * Generates a username from the given domain name
     *
     * @param string $domain The domain name to use to generate the username
     * @return string The username generated from the given hostname
     */
    private function generateUsername($domain)
    {
        // Remove everything except letters and numbers from the domain
        // ensure no number appears in the beginning
        $username = ltrim(preg_replace('/[^a-z0-9]/i', '', $domain), '0123456789');

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

        $vesta = null;
        if ($row) {
            $vesta = $this->getApi(
                $row->meta->host_name,
                $row->meta->user_name,
                $row->meta->port,
                $row->meta->password,
                $row->meta->use_ssl
            );
        }

        $account_matching_characters = 1;
        $user = $vesta->getAccountsUsage($username);

        // Username exists, create another instead
        if (isset($user['status']) && $user['status'] == 'true') {
            for ($i = 0; $i < (int) str_repeat(9, $account_matching_characters); $i++) {
                $new_username = substr($username, 0, -strlen($i)) . $i;

                $user = $vesta->getAccountsUsage($new_username);
                if (isset($user['status']) && $user['status'] == 'false') {
                    $username = $new_username;
                    break;
                }
            }
        }

        return $username;
    }

    /**
     * Initialize the API library
     *
     * @param string $user_name The vesta username
     * @param string $password The vesta password
     * @param string $host_name The hostname of the server
     * @param string $port The port of the vesta server
     * @param boolean $use_ssl Whether to use https or http
     * @return Vestaapi the Vestaapi instance, or false if the loader fails to load the file
     */
    private function getApi($host_name, $user_name, $port, $password, $use_ssl)
    {
        Loader::load(dirname(__FILE__) . DS . 'api' . DS . 'vesta_api.php');
        return new VestaApi($user_name, $password, $host_name, $port, $use_ssl);
    }


    /**
     * Builds and returns the rules required to add/edit a module row (e.g. server)
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
                    'message' => Language::_('Vesta.!error.server_name_valid', true)
                ]
            ],
            'host_name' => [
                'valid' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Vesta.!error.host_name_valid', true)
                ]
            ],
            'user_name' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Vesta.!error.user_name_valid', true)
                ]
            ],
            'port' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Vesta.!error.port_valid', true)
                ]
            ],
            'password' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Vesta.!error.password_valid', true)
                ]
            ],
        ];

        return $rules;
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
            'meta[package_name]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Vesta.!error.meta[package_name].empty', true)
                ]
            ],
        ];

        return $rules;
    }
}
