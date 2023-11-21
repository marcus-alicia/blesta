<?php
use Blesta\Core\Util\Validate\Server;
/**
 * Centovacast Module.
 *
 * @package blesta
 * @subpackage blesta.components.modules.centovacast
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Centovacast extends Module
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
        Language::loadLang('centovacast', null, dirname(__FILE__) . DS . 'language' . DS);
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
            'tabActions' => Language::_('Centovacast.tab_actions', true),
            'tabStats' => Language::_('Centovacast.tab_stats', true)
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
            'tabClientActions' => Language::_('Centovacast.tab_client_actions', true),
            'tabClientStats' => Language::_('Centovacast.tab_client_stats', true)
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
            'roundrobin' => Language::_('Centovacast.order_options.roundrobin', true),
            'first' => Language::_('Centovacast.order_options.first', true)
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

        // Set the CentovaCast server type as a selectable option
        $servertype = $fields->label(
            Language::_('Centovacast.package_fields.servertype', true),
            'centovacast_servertype'
        );
        $servertype->attach(
            $fields->fieldSelect(
                'meta[servertype]',
                $this->getServerTypes(),
                (isset($vars->meta['servertype']) ? $vars->meta['servertype'] : null),
                ['id' => 'centovacast_servertype']
            )
        );
        $fields->setField($servertype);

        // Set the CentovaCast Auto DJ type as a selectable option
        $apptypes = $fields->label(
            Language::_('Centovacast.package_fields.apptypes', true),
            'centovacast_apptypes'
        );
        $apptypes->attach(
            $fields->fieldSelect(
                'meta[apptypes]',
                $this->getAppTypes(),
                (isset($vars->meta['apptypes']) ? $vars->meta['apptypes'] : null),
                ['id' => 'centovacast_apptypes']
            )
        );
        $fields->setField($apptypes);

        // Set the CentovaCast Auto DJ capabilities as a selectable option
        $usesource = $fields->label(
            Language::_('Centovacast.package_fields.usesource', true),
            'centovacast_usesource'
        );
        $usesource->attach(
            $fields->fieldSelect(
                'meta[usesource]',
                $this->getSourceCapabilities(),
                (isset($vars->meta['usesource']) ? $vars->meta['usesource'] : null),
                ['id' => 'centovacast_usesource']
            )
        );
        $fields->setField($usesource);

        // Create maxclients label
        $maxclients = $fields->label(
            Language::_('Centovacast.package_fields.maxclients', true),
            'centovacast_maxclients'
        );
        // Create maxclients field and attach to maxclients label
        $maxclients->attach(
            $fields->fieldText(
                'meta[maxclients]',
                (isset($vars->meta['maxclients']) ? $vars->meta['maxclients'] : null),
                ['id' => 'centovacast_maxclients']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Centovacast.package_fields.tooltip.maxclients', true));
        $maxclients->attach($tooltip);
        // Set the label as a field
        $fields->setField($maxclients);

        // Create maxbitrate label
        $maxbitrate = $fields->label(
            Language::_('Centovacast.package_fields.maxbitrate', true),
            'centovacast_maxbitrate'
        );
        // Create maxbitrate field and attach to maxbitrate label
        $maxbitrate->attach(
            $fields->fieldText(
                'meta[maxbitrate]',
                (isset($vars->meta['maxbitrate']) ? $vars->meta['maxbitrate'] : null),
                ['id' => 'centovacast_maxbitrate']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Centovacast.package_fields.tooltip.maxbitrate', true));
        $maxbitrate->attach($tooltip);
        // Set the label as a field
        $fields->setField($maxbitrate);

        // Create transferlimit label
        $transferlimit = $fields->label(
            Language::_('Centovacast.package_fields.transferlimit', true),
            'centovacast_transferlimit'
        );
        // Create transferlimit field and attach to transferlimit label
        $transferlimit->attach(
            $fields->fieldText(
                'meta[transferlimit]',
                (isset($vars->meta['transferlimit']) ? $vars->meta['transferlimit'] : null),
                ['id' => 'centovacast_transferlimit']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Centovacast.package_fields.tooltip.transferlimit', true));
        $transferlimit->attach($tooltip);
        // Set the label as a field
        $fields->setField($transferlimit);

        // Create diskquota label
        $diskquota = $fields->label(
            Language::_('Centovacast.package_fields.diskquota', true),
            'centovacast_diskquota'
        );
        // Create diskquota field and attach to diskquota label
        $diskquota->attach(
            $fields->fieldText(
                'meta[diskquota]',
                (isset($vars->meta['diskquota']) ? $vars->meta['diskquota'] : null),
                ['id' => 'centovacast_diskquota']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Centovacast.package_fields.tooltip.diskquota', true));
        $diskquota->attach($tooltip);
        // Set the label as a field
        $fields->setField($diskquota);

        return $fields;
    }

    /**
     * Gets a list of available server types
     *
     * @return array A key/value array of available server types and their languages
     */
    private function getServerTypes()
    {
        return [
            'IceCast' => 'IceCast',
            'ShoutCast2' => 'ShoutCast DNAS v2',
            'ShoutCast' => 'ShoutCast DNAS v1'
        ];
    }

    /**
     * Gets a list of available app types
     *
     * @return array A key/value array of available app types and their languages
     */
    private function getAppTypes()
    {
        return [
            '' => 'None',
            'icescc' => 'IceCast CC',
            'sctrans' => 'ShoutCast Trans',
            'sctrans2' => 'ShoutCast Trans 2',
            'liquidsoap' => 'Liquidsoap'
        ];
    }

    /**
     * Gets a list of autoDJ capabilities options
     *
     * @return array A key/value array of available options and their languages
     */
    private function getSourceCapabilities()
    {
        return [
            '0' => 'Permitted, but disabled by default',
            '1' => 'Permitted, and enabled by default',
            '2' => 'Prohibited'
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'centovacast' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'centovacast' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'centovacast' . DS);

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
        $meta_fields = ['server_name', 'hostname', 'ipaddress', 'port', 'username',
            'password', 'use_ssl', 'account_limit', 'notes', 'account_count'];
        $encrypted_fields = ['username', 'password'];

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
        $meta_fields = ['server_name', 'hostname', 'ipaddress', 'port', 'username',
            'password', 'use_ssl', 'account_limit', 'notes', 'account_count'];
        $encrypted_fields = ['username', 'password'];

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
        $fields = $this->getClientAddFields($package, $vars);

        // Create ipaddress label
        $ipaddress = $fields->label(Language::_('Centovacast.service_field.ipaddress', true), 'centovacast_ipaddress');
        // Create ipaddress field and attach to ipaddress label
        $ipaddress->attach(
            $fields->fieldText(
                'centovacast_ipaddress',
                (isset($vars->centovacast_ipaddress) ? $vars->centovacast_ipaddress : ($vars->ipaddress ?? null)),
                ['id' => 'centovacast_ipaddress']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Centovacast.service_field.tooltip.ipaddress', true));
        $ipaddress->attach($tooltip);
        // Set the label as a field
        $fields->setField($ipaddress);

        // Create port label
        $port = $fields->label(Language::_('Centovacast.service_field.port', true), 'centovacast_port');
        // Create port field and attach to port label
        $port->attach(
            $fields->fieldText(
                'centovacast_port',
                (isset($vars->centovacast_port) ? $vars->centovacast_port : ($vars->port ?? null)),
                ['id' => 'centovacast_port']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Centovacast.service_field.tooltip.port', true));
        $port->attach($tooltip);
        // Set the label as a field
        $fields->setField($port);

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

        // Create hostname label
        $hostname = $fields->label(Language::_('Centovacast.service_field.hostname', true), 'centovacast_hostname');
        // Create hostname field and attach to hostname label
        $hostname->attach(
            $fields->fieldText(
                'centovacast_hostname',
                (isset($vars->centovacast_hostname) ? $vars->centovacast_hostname : ($vars->hostname ?? null)),
                ['id' => 'centovacast_hostname']
            )
        );
        // Set the label as a field
        $fields->setField($hostname);

        // Create title label
        $title = $fields->label(Language::_('Centovacast.service_field.title', true), 'centovacast_title');
        // Create title field and attach to title label
        $title->attach(
            $fields->fieldText(
                'centovacast_title',
                (isset($vars->centovacast_title) ? $vars->centovacast_title : ($vars->title ?? null)),
                ['id' => 'centovacast_title']
            )
        );
        // Set the label as a field
        $fields->setField($title);

        // Create genre label
        $genre = $fields->label(Language::_('Centovacast.service_field.genre', true), 'centovacast_genre');
        // Create genre field and attach to genre label
        $genre->attach(
            $fields->fieldText(
                'centovacast_genre',
                (isset($vars->centovacast_genre) ? $vars->centovacast_genre : ($vars->genre ?? null)),
                ['id' => 'centovacast_genre']
            )
        );
        // Set the label as a field
        $fields->setField($genre);

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

        // Create hostname label
        $hostname = $fields->label(Language::_('Centovacast.service_field.hostname', true), 'centovacast_hostname');
        // Create hostname field and attach to hostname label
        $hostname->attach(
            $fields->fieldText(
                'centovacast_hostname',
                (isset($vars->hostname) ? $vars->hostname : $vars->centovacast_hostname),
                ['id' => 'centovacast_hostname']
            )
        );
        // Set the label as a field
        $fields->setField($hostname);

        // Create password label
        $password = $fields->label(
            Language::_('Centovacast.service_field.password', true),
            'centovacast_adminpassword'
        );
        // Create password field and attach to password label
        $password->attach(
            $fields->fieldPassword(
                'centovacast_adminpassword',
                ['id' => 'centovacast_adminpassword']
            )
        );
        // Set the label as a field
        $fields->setField($password);

        // Create title label
        $title = $fields->label(Language::_('Centovacast.service_field.title', true), 'centovacast_title');
        // Create title field and attach to title label
        $title->attach(
            $fields->fieldText(
                'centovacast_title',
                (isset($vars->title) ? $vars->title : $vars->centovacast_title),
                ['id' => 'centovacast_title']
            )
        );
        // Set the label as a field
        $fields->setField($title);

        // Create genre label
        $genre = $fields->label(Language::_('Centovacast.service_field.genre', true), 'centovacast_genre');
        // Create genre field and attach to genre label
        $genre->attach(
            $fields->fieldText(
                'centovacast_genre',
                (isset($vars->genre) ? $vars->genre : $vars->centovacast_genre),
                ['id' => 'centovacast_genre']
            )
        );
        // Set the label as a field
        $fields->setField($genre);

        // Create ipaddress label
        $ipaddress = $fields->label(Language::_('Centovacast.service_field.ipaddress', true), 'centovacast_ipaddress');
        // Create ipaddress field and attach to ipaddress label
        $ipaddress->attach(
            $fields->fieldText(
                'centovacast_ipaddress',
                (isset($vars->ipaddress) ? $vars->ipaddress : $vars->centovacast_ipaddress),
                ['id' => 'centovacast_ipaddress']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Centovacast.service_field.tooltip.ipaddress', true));
        $ipaddress->attach($tooltip);
        // Set the label as a field
        $fields->setField($ipaddress);

        // Create port label
        $port = $fields->label(Language::_('Centovacast.service_field.port', true), 'centovacast_port');
        // Create port field and attach to port label
        $port->attach(
            $fields->fieldText(
                'centovacast_port',
                (isset($vars->port) ? $vars->port : $vars->centovacast_port),
                ['id' => 'centovacast_port']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Centovacast.service_field.tooltip.port', true));
        $port->attach($tooltip);
        // Set the label as a field
        $fields->setField($port);

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
            'centovacast_hostname' => [
                'format' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Centovacast.!error.centovacast_hostname.format', true)
                ]
            ]
        ];

        if ($edit) {
            // If this is an edit and password given then evaluate password
            if (!empty($vars['centovacast_adminpassword'])) {
                $rules['centovacast_adminpassword'] = [
                    'valid' => [
                        'if_set' => true,
                        'rule' => ['isPassword', 5],
                        'message' => Language::_('Centovacast.!error.centovacast_adminpassword.valid', true),
                        'last' => true
                    ]
                ];
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
                ['module_row' => ['missing' => Language::_('Centovacast.!error.module_row.missing', true)]]
            );

            return;
        }

        // Use client's email address
        Loader::loadModels($this, ['Clients']);

        if (isset($vars['client_id']) && ($client = $this->Clients->get($vars['client_id'], false))) {
            $vars['centovacast_email'] = $client->email;
        }

        // Get service parameters
        $params = $this->getFieldsFromInput((array) $vars, $package);

        // Validate service
        $this->validateService($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            $masked_params = $params;
            $masked_params['adminpassword'] = '***';
            $this->log($row->meta->hostname . '|createaccount', serialize($masked_params), 'input', true);
            unset($masked_params);

            try {
                // Initialize API
                $api = $this->getApi(
                    $row->meta->hostname,
                    $row->meta->username,
                    $row->meta->password,
                    $row->meta->port,
                    $row->meta->use_ssl
                );

                // Select random hosting server
                $servers = $this->parseResponse($api->listServers());
                $params['rpchostid'] = $servers[array_rand($servers)]->id;

                $result = $this->parseResponse($api->createAccount($params));
            } catch (Exception $e) {
                $this->Input->setErrors(
                    ['api' => ['internal' => Language::_('Centovacast.!error.api.internal', true)]]
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
                'key' => 'centovacast_hostname',
                'value' => $params['hostname'],
                'encrypted' => 0
            ],
            [
                'key' => 'centovacast_username',
                'value' => $params['username'],
                'encrypted' => 0
            ],
            [
                'key' => 'centovacast_adminpassword',
                'value' => $params['adminpassword'],
                'encrypted' => 1
            ],
            [
                'key' => 'centovacast_title',
                'value' => $params['title'],
                'encrypted' => 0
            ],
            [
                'key' => 'centovacast_genre',
                'value' => $params['genre'],
                'encrypted' => 0
            ],
            [
                'key' => 'centovacast_ipaddress',
                'value' => $params['ipaddress'],
                'encrypted' => 0
            ],
            [
                'key' => 'centovacast_port',
                'value' => $params['port'],
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
                ['module_row' => ['missing' => Language::_('Centovacast.!error.module_row.missing', true)]]
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

        // Force domain to lower case
        $vars['centovacast_hostname'] = strtolower($vars['centovacast_hostname']);


        // Remove password if not being updated
        if (isset($vars['centovacast_adminpassword']) && $vars['centovacast_adminpassword'] == '') {
            unset($vars['centovacast_adminpassword']);
        }

        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            // Initialize API
            $api = $this->getApi(
                $row->meta->hostname,
                $row->meta->username,
                $row->meta->password,
                $row->meta->port,
                $row->meta->use_ssl
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
            $account_fields = ['hostname', 'adminpassword', 'title', 'genre', 'ipaddress', 'port'];
            foreach ($account_fields as $account_field) {
                if (isset($delta['centovacast_' . $account_field])) {
                    $params[$account_field] = $delta['centovacast_' . $account_field];
                }
            }

            // Update altered fields
            if (!empty($params)) {
                $this->log($row->meta->hostname . '|editaccount', serialize($params), 'input', true);
                $result = $this->parseResponse($api->editAccount($service_fields->centovacast_username, $params));
            }
        }

        // Set fields to update locally
        $fields = [
            'centovacast_hostname',
            'centovacast_adminpassword',
            'centovacast_title',
            'centovacast_genre',
            'centovacast_ipaddress',
            'centovacast_port'
        ];
        foreach ($fields as $field) {
            if (property_exists($service_fields, $field) && isset($vars[$field])) {
                $service_fields->{$field} = $vars[$field];
            }
        }

        // Return all the service fields
        $fields = [];
        $encrypted_fields = ['centovacast_adminpassword'];
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
                $row->meta->port,
                $row->meta->use_ssl
            );

            $service_fields = $this->serviceFieldsToObject($service->fields);

            $this->log(
                $row->meta->hostname . '|suspendaccount',
                serialize($service_fields->centovacast_username),
                'input',
                true
            );
            $this->parseResponse($api->suspendAccount($service_fields->centovacast_username));
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
                $row->meta->port,
                $row->meta->use_ssl
            );

            $service_fields = $this->serviceFieldsToObject($service->fields);

            $this->log(
                $row->meta->hostname . '|unsuspendaccount',
                serialize($service_fields->centovacast_username),
                'input',
                true
            );
            $this->parseResponse($api->unsuspendAccount($service_fields->centovacast_username));
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
                $row->meta->port,
                $row->meta->use_ssl
            );

            $service_fields = $this->serviceFieldsToObject($service->fields);

            $this->log(
                $row->meta->hostname . '|terminateaccount',
                serialize($service_fields->centovacast_username),
                'input',
                true
            );
            $this->parseResponse($api->terminateAccount($service_fields->centovacast_username));

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'centovacast' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'centovacast' . DS);

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
        // Get module row
        $row = $this->getModuleRow();

        $this->view = new View('tab_stats', 'default');

        // Initialize API
        $api = $this->getApi(
            $row->meta->hostname,
            $row->meta->username,
            $row->meta->password,
            $row->meta->port,
            $row->meta->use_ssl
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Get account information
        $account_info = $this->parseResponse($api->getAccount($service_fields->centovacast_username))->account;

        $this->view->set('account_info', $account_info);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'centovacast' . DS);

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
            $row->meta->port,
            $row->meta->use_ssl
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Perform actions
        if (!empty($post)) {
            switch ($post['action']) {
                case 'restart':
                    $api->restartStream($service_fields->centovacast_username);
                    break;
                case 'stop':
                    $api->stopStream($service_fields->centovacast_username);
                    break;
                case 'start':
                    $api->startStream($service_fields->centovacast_username);
                    break;
                default:
                    break;
            }
        }

        // Get account information
        $account_info = $this->parseResponse($api->getAccount($service_fields->centovacast_username))->account;

        $mount_name = 'all';
        switch ($account_info->servertype) {
            case 'ShoutCast2':
                $mount_name = $account_info->mountpoints[0]->streampath;
                break;
            case 'IceCast':
                $mount_name = $account_info->mountpoints[0]->mountname;
                break;
        }
        $stream_info = $this->parseResponse(
            $api->getStream($service_fields->centovacast_username, $mount_name)
        )->status;

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('account_info', $account_info);
        $this->view->set('stream_info', $stream_info);
        $this->view->set('vars', (isset($vars) ? $vars : new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'centovacast' . DS);

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
        // Get module row
        $row = $this->getModuleRow();

        $this->view = new View('tab_client_stats', 'default');

        // Initialize API
        $api = $this->getApi(
            $row->meta->hostname,
            $row->meta->username,
            $row->meta->password,
            $row->meta->port,
            $row->meta->use_ssl
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Get account information
        $account_info = $this->parseResponse($api->getAccount($service_fields->centovacast_username))->account;

        $this->view->set('account_info', $account_info);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'centovacast' . DS);

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
            $row->meta->port,
            $row->meta->use_ssl
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Perform actions
        if (!empty($post)) {
            switch ($post['action']) {
                case 'restart':
                    $api->restartStream($service_fields->centovacast_username);
                    break;
                case 'stop':
                    $api->stopStream($service_fields->centovacast_username);
                    break;
                case 'start':
                    $api->startStream($service_fields->centovacast_username);
                    break;
                case 'genre':
                    // Update the service genre
                    Loader::loadModels($this, ['Services']);
                    $this->Services->editField($service->id, ['key' => 'centovacast_genre', 'value' => $post['genre']]);

                    if (($errors = $this->Services->errors())) {
                        $this->Input->setErrors($errors);
                    }

                    $api->editAccount($service_fields->centovacast_username, ['genre' => $post['genre']]);
                    break;
                case 'radio_title':
                    // Update the service title
                    Loader::loadModels($this, ['Services']);
                    $this->Services->editField(
                        $service->id,
                        ['key' => 'centovacast_title', 'value' => $post['radio_title']]
                    );

                    if (($errors = $this->Services->errors())) {
                        $this->Input->setErrors($errors);
                    }

                    $api->editAccount($service_fields->centovacast_username, ['title' => $post['radio_title']]);
                    break;
                case 'password':
                    if (!empty($post)) {
                        $rules = [
                            'password' => [
                                'length' => [
                                    'rule' => ['isPassword', 5],
                                    'message' => Language::_('Centovacast.!error.centovacast_adminpassword.valid', true)
                                ]
                            ]
                        ];

                        // Validate the password and update it
                        $this->Input->setRules($rules);
                        if ($this->Input->validates($post)) {
                            // Update the service password
                            Loader::loadModels($this, ['Services']);
                            $this->Services->editField(
                                $service->id,
                                [
                                    'key' => 'centovacast_adminpassword',
                                    'value' => $post['password'],
                                    'encrypted' => true
                                ]
                            );

                            if (($errors = $this->Services->errors())) {
                                $this->Input->setErrors($errors);
                            }

                            $api->editAccount(
                                $service_fields->centovacast_username,
                                ['adminpassword' => $post['password']]
                            );
                        }

                        $vars = $post;
                    }
                    break;
                default:
                    break;
            }
        }

        // Get account information
        $account_info = $this->parseResponse($api->getAccount($service_fields->centovacast_username))->account;

        $mount_name = 'all';
        switch ($account_info->servertype) {
            case 'ShoutCast2':
                $mount_name = $account_info->mountpoints[0]->streampath;
                break;
            case 'IceCast':
                $mount_name = $account_info->mountpoints[0]->mountname;
                break;
        }
        $stream_info = $this->parseResponse(
            $api->getStream($service_fields->centovacast_username, $mount_name)
        )->status;

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('account_info', $account_info);
        $this->view->set('stream_info', $stream_info);
        $this->view->set('vars', (isset($vars) ? (object)$vars : new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'centovacast' . DS);

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
     * Retrieves the accounts on the server.
     *
     * @param stdClass $api The CentovaCast API
     * @return mixed The number of accounts on the server, or false on error
     */
    private function getAccountCount($api)
    {
        $accounts = false;

        try {
            $output = $this->parseResponse($api->listAccounts());
            $accounts = count($output);
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
    private function updateAccountCount($module_row, $increase = true)
    {
        // Initialize API
        $api = $this->getApi(
            $module_row->meta->hostname,
            $module_row->meta->username,
            $module_row->meta->password,
            $module_row->meta->port,
            $module_row->meta->use_ssl
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
     * @return bool True if the connection is valid, false otherwise
     */
    public function validateConnection($password, $hostname, $username, $port, $use_ssl)
    {
        try {
            $api = $this->getApi($hostname, $username, $password, $port, $use_ssl);

            return $this->parseResponse($api->listAccounts());
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

        // Check for existing user accounts
        $row = $this->getModuleRow();
        $api = $this->getApi(
            $row->meta->hostname,
            $row->meta->username,
            $row->meta->password,
            $row->meta->port,
            $row->meta->use_ssl
        );

        $accounts_usernames = $api->listUsernames();

        if (in_array($username, $accounts_usernames)) {
            $username = substr($username . md5(rand(0, 100)), 0, min($length, 8));
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
    private function generatePassword($min_length = 5, $max_length = 10)
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
            'hostname' => !empty($vars['centovacast_hostname']) ? strtolower($vars['centovacast_hostname']) : null,
            'username' => $this->generateUsername($vars['centovacast_hostname']),
            'adminpassword' => $this->generatePassword(),
            'sourcepassword' => $this->generatePassword(),
            'title' => !empty($vars['centovacast_title']) ? $vars['centovacast_title'] : null,
            'genre' => !empty($vars['centovacast_genre']) ? $vars['centovacast_genre'] : null,
            'ipaddress' => !empty($vars['centovacast_ipaddress'])
                ? $vars['centovacast_ipaddress']
                : $row->meta->ipaddress,
            'port' => !empty($vars['centovacast_port']) ? $vars['centovacast_port'] : 'auto',
            'email' => !empty($vars['centovacast_email']) ? $vars['centovacast_email'] : null,
            'url' => !empty($vars['centovacast_hostname'])
                ? 'http://' . strtolower($vars['centovacast_hostname'])
                : null,
            'autostart' => 1
        ];

        $fields = array_merge((array) $package->meta, $fields);

        if ($fields['diskquota'] == 0) {
            $fields['diskquota'] = 'unlimited';
        }
        if ($fields['transferlimit'] == 0) {
            $fields['transferlimit'] = 'unlimited';
        }

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

        if ($response->type == 'error') {
            $this->Input->setErrors(['api' => ['error' => $response->response->message]]);
            $success = false;
        }

        // Set internal error
        if (empty($response)) {
            $this->Input->setErrors(['api' => ['internal' => Language::_('Centovacast.!error.api.internal', true)]]);
            $success = false;
        }

        if ($row) {
            // Log the response
            $this->log($row->meta->hostname, serialize($response), 'output', $success);
        }

        // Return if any errors encountered
        if (!$success) {
            return;
        }

        return $response->response->data;
    }

    /**
     * Initializes the CentovacastApi and returns an instance of that object with the given $host, $user, and $pass set.
     *
     * @param string $host The host to the CentovaCast server
     * @param string $user The user to connect as
     * @param string $pass The hash-pased password to authenticate with
     * @return CentovacastApi The CentovacastApi instance
     */
    private function getApi($hostname, $username, $password, $port = 2199, $use_ssl = false)
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'centovacast_api.php');

        $api = new CentovacastApi($hostname, $username, $password, $port, $use_ssl);

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
                    'message' => Language::_('Centovacast.!error.server_name_valid', true)
                ]
            ],
            'hostname' => [
                'valid' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Centovacast.!error.host_name_valid', true)
                ]
            ],
            'username' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Centovacast.!error.user_name_valid', true)
                ]
            ],
            'password' => [
                'valid' => [
                    'last' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Centovacast.!error.password_valid', true)
                ],
                'valid_connection' => [
                    'rule' => [
                        [$this, 'validateConnection'],
                        $vars['hostname'],
                        $vars['username'],
                        $vars['port'],
                        $vars['use_ssl']
                    ],
                    'message' => Language::_('Centovacast.!error.password_valid_connection', true)
                ]
            ],
            'account_limit' => [
                'valid' => [
                    'rule' => ['matches', '/^([0-9]+)?$/'],
                    'message' => Language::_('Centovacast.!error.account_limit_valid', true)
                ]
            ],
            'port' => [
                'valid' => [
                    'rule' => ['matches', '/^([0-9]+)$/'],
                    'message' => Language::_('Centovacast.!error.port_valid', true)
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
            'meta[servertype]' => [
                'valid' => [
                    'rule' => ['in_array', array_keys($this->getServerTypes())],
                    'message' => Language::_('Centovacast.!error.meta[servertype].valid', true),
                ]
            ],
            'meta[apptypes]' => [
                'valid' => [
                    'rule' => ['in_array', array_keys($this->getAppTypes())],
                    'message' => Language::_('Centovacast.!error.meta[apptypes].valid', true),
                ]
            ],
            'meta[usesource]' => [
                'valid' => [
                    'rule' => ['in_array', array_keys($this->getSourceCapabilities())],
                    'message' => Language::_('Centovacast.!error.meta[usesource].valid', true),
                ]
            ],
            'meta[maxclients]' => [
                'valid' => [
                    'rule' => ['matches', '/^([0-9]+)$/'],
                    'message' => Language::_('Centovacast.!error.meta[maxclients].valid', true),
                ]
            ],
            'meta[maxbitrate]' => [
                'valid' => [
                    'rule' => ['matches', '/^([0-9]+)$/'],
                    'message' => Language::_('Centovacast.!error.meta[maxbitrate].valid', true),
                ]
            ],
            'meta[transferlimit]' => [
                'valid' => [
                    'rule' => ['matches', '/^([0-9]+)$/'],
                    'message' => Language::_('Centovacast.!error.meta[transferlimit].valid', true),
                ]
            ],
            'meta[diskquota]' => [
                'valid' => [
                    'rule' => ['matches', '/^([0-9]+)$/'],
                    'message' => Language::_('Centovacast.!error.meta[diskquota].valid', true),
                ]
            ]
        ];

        return $rules;
    }
}
