<?php
/**
 * Multicraft Module
 *
 * @package blesta
 * @subpackage blesta.components.modules.multicraft
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Multicraft extends Module
{
    /**
     * Initializes the module
     */
    public function __construct()
    {
        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('multicraft', null, dirname(__FILE__) . DS . 'language' . DS);
        Language::loadLang('multicraft_module', null, dirname(__FILE__) . DS . 'language' . DS);
        Language::loadLang('multicraft_package', null, dirname(__FILE__) . DS . 'language' . DS);
        Language::loadLang('multicraft_service', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load module config
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load additional config settings
        Configure::load('multicraft', dirname(__FILE__) . DS . 'config' . DS);
    }

    /**
     * Initializes the MulticraftApiActions and returns an instance of that object
     *
     * @param string $hostname The multicraft hostname
     * @param string $user The user to connect as
     * @param string $key The key to use when connecting
     * @return MulticraftApi The MulticraftApiActions instance
     */
    private function getApi($hostname, $user, $key)
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'multicraft_api.php');
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'multicraft_api_actions.php');
        $api = new MulticraftApi($hostname, $user, $key);
        return new MulticraftApiActions($api, $hostname);
    }

    /**
     * Loads a library class
     *
     * @param string $command The filename of the class to load
     */
    private function loadLib($command)
    {
        Loader::load(dirname(__FILE__) . DS . 'lib' . DS . $command . '.php');
    }

    /**
     * Retrieves a list of email tags available for modules
     *
     * @return array A list of tags
     */
    public function getEmailTags()
    {
        $this->loadLib('multicraft_module');
        $module = new MulticraftModule();
        $this->loadLib('multicraft_package');
        $package = new MulticraftPackage();
        $this->loadLib('multicraft_service');
        $service = new MulticraftService();

        return [
            'module' => $module->getEmailTags(),
            'package' => $package->getEmailTags(),
            'service' => $service->getEmailTags()
        ];
    }

    /**
     * Performs any necessary bootstraping actions. Sets Input errors on
     * failure, preventing the module from being added.
     *
     * @return array A numerically indexed array of meta data containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function install()
    {
        // Perform installation checks
        $this->loadLib('multicraft_module');
        $module = new MulticraftModule();
        $meta = $module->install();

        if (($errors = $module->errors())) {
            $this->Input->setErrors($errors);
        } else {
            return $meta;
        }
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
        // Perform installation checks
        $this->loadLib('multicraft_module');
        $module = new MulticraftModule();
        $module->setConfig($this->config);
        $module->upgrade($current_version);

        if (($errors = $module->errors())) {
            $this->Input->setErrors($errors);
        }
    }

    /**
     * Returns the rendered view of the manage module page
     *
     * @param mixed $module A stdClass object representing the module and its rows
     * @param array $vars An array of post data submitted to or on the manage
     *  module page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the manager module page
     */
    public function manageModule($module, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'multicraft' . DS);

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
        Loader::loadHelpers($this, ['DataStructure']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('add_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'multicraft' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->loadLib('multicraft_service');
        $service = new MulticraftService();

        // Format IP post data on submission error
        if (!empty($vars) && !empty($vars['ips']) && is_array($vars['ips'])) {
            $vars['ips'] = $this->ArrayHelper->keyToNumeric($vars['ips']);
        }

        $this->view->set('default_port', $service->getDefaultPort());
        $this->view->set('vars', (object)$vars);
        $this->view->set('ips_in_use', $this->getIpsInUseFields());
        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the edit module row page
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of post data submitted to or on the
     *  edit module row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the edit module row page
     */
    public function manageEditRow($module_row, array &$vars)
    {
        Loader::loadHelpers($this, ['DataStructure']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('edit_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'multicraft' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        if (empty($vars)) {
            $vars = $module_row->meta;
        } else {
            // Format IP post data on submission error
            if (!empty($vars['ips']) && is_array($vars['ips'])) {
                $vars['ips'] = $this->ArrayHelper->keyToNumeric($vars['ips']);
            }
        }

        $this->loadLib('multicraft_service');
        $service = new MulticraftService();

        $this->view->set('default_port', $service->getDefaultPort());
        $this->view->set('vars', (object)$vars);
        $this->view->set('ips_in_use', $this->getIpsInUseFields());
        return $this->view->fetch();
    }

    /**
     * Retrieves a list of IPs in use fields
     *
     * @return array A list of key/value pairs representing the value and the language
     */
    private function getIpsInUseFields()
    {
        return [
            '0' => Language::_('Multicraft.manage.module_rows_in_use_0', true),
            '1' => Language::_('Multicraft.manage.module_rows_in_use_1', true)
        ];
    }

    /**
     * Adds the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being added.
     *
     * @param array $vars An array of module info to add
     * @return array A numerically indexed array of meta fields for the module row containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function addModuleRow(array &$vars)
    {
        Loader::loadHelpers($this, ['DataStructure']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Add the module row
        $this->loadLib('multicraft_module');
        $module = new MulticraftModule();

        // Index the ips and ports to match the deamons and ips_in_use.
        if (isset($vars['ips']) && isset($vars['ips']['ip'])) {
            $vars['ips'] = $this->ArrayHelper->keyToNumeric($vars['ips']);
            unset($vars['ips']['port'], $vars['ips']['ip']);
        }

        $meta = $module->addRow($vars);

        if (($errors = $module->errors())) {
            $this->Input->setErrors($errors);
        } else {
            return $meta;
        }
    }

    /**
     * Edits the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being updated.
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
        // Same as adding
        return $this->addModuleRow($vars);
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
        // Add a package
        $this->loadLib('multicraft_package');
        $package = new MulticraftPackage();
        $meta = $package->add($vars);

        if (($errors = $package->errors())) {
            $this->Input->setErrors($errors);
        } else {
            return $meta;
        }
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
        // Same as adding
        return $this->addPackage($vars);
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
        // Fetch the package fields
        $this->loadLib('multicraft_package');
        $package = new MulticraftPackage();
        return $package->getFields($vars);
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
        // Fetch the module row
        $row = $this->getModuleRow();

        if (!$row) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Multicraft.!error.module_row.missing', true)]]
            );
            return;
        }

        // Get the API
        $api_url = (isset($row->meta->panel_api_url) ? $row->meta->panel_api_url : '');
        $api = $this->getApi(
            $api_url,
            (isset($row->meta->username) ? $row->meta->username : ''),
            (isset($row->meta->key) ? $row->meta->key : '')
        );
        $this->loadLib('multicraft_service');
        $service = new MulticraftService($api, $row);

        // Add the service
        $meta = $service->add($package, $vars, $parent_package, $parent_service, $status);

        // Log the requests
        $this->logResponses($service->getLogs());

        if (($errors = $service->errors())) {
            $this->Input->setErrors($errors);
        }

        return $meta;
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
        // Fetch the module row
        $row = $this->getModuleRow();

        if (!$row) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Multicraft.!error.module_row.missing', true)]]
            );
            return;
        }

        // Get the API
        $api_url = (isset($row->meta->panel_api_url) ? $row->meta->panel_api_url : '');
        $api = $this->getApi(
            $api_url,
            (isset($row->meta->username) ? $row->meta->username : ''),
            (isset($row->meta->key) ? $row->meta->key : '')
        );
        $this->loadLib('multicraft_service');
        $service_api = new MulticraftService($api, $row);

        // Update the service
        $meta = $service_api->edit(
            $package,
            $service,
            $vars,
            $parent_package,
            $parent_service,
            $this->serviceFieldsToObject($service->fields)
        );

        // Log the API calls
        $this->logResponses($service_api->getLogs());

        if (($errors = $service_api->errors())) {
            $this->Input->setErrors($errors);
        }

        return $meta;
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
        // Fetch the module row
        $row = $this->getModuleRow();
        if (!$row) {
            return;
        }

        // Get the API
        $api_url = (isset($row->meta->panel_api_url) ? $row->meta->panel_api_url : '');
        $api = $this->getApi(
            $api_url,
            (isset($row->meta->username) ? $row->meta->username : ''),
            (isset($row->meta->key) ? $row->meta->key : '')
        );
        $this->loadLib('multicraft_service');
        $service_api = new MulticraftService($api, $row);

        // Cancel the service
        $service_api->cancel(
            $package,
            $service,
            $parent_package,
            $parent_service,
            $this->serviceFieldsToObject($service->fields)
        );

        // Log the API calls
        $this->logResponses($service_api->getLogs());

        if (($errors = $service_api->errors())) {
            $this->Input->setErrors($errors);
        }

        return null;
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
        // Fetch the module row
        $row = $this->getModuleRow();
        if (!$row) {
            return;
        }

        // Get the API
        $api_url = (isset($row->meta->panel_api_url) ? $row->meta->panel_api_url : '');
        $api = $this->getApi(
            $api_url,
            (isset($row->meta->username) ? $row->meta->username : ''),
            (isset($row->meta->key) ? $row->meta->key : '')
        );
        $this->loadLib('multicraft_service');
        $service_api = new MulticraftService($api, $row);

        // Suspend the service
        $service_api->suspend(
            $package,
            $service,
            $parent_package,
            $parent_service,
            $this->serviceFieldsToObject($service->fields)
        );

        // Log the API calls
        $this->logResponses($service_api->getLogs());

        // Set any errors
        if (($errors = $service_api->errors())) {
            $this->Input->setErrors($errors);
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
        // Fetch the module row
        $row = $this->getModuleRow();
        if (!$row) {
            return;
        }

        // Get the API
        $api_url = (isset($row->meta->panel_api_url) ? $row->meta->panel_api_url : '');
        $api = $this->getApi(
            $api_url,
            (isset($row->meta->username) ? $row->meta->username : ''),
            (isset($row->meta->key) ? $row->meta->key : '')
        );
        $this->loadLib('multicraft_service');
        $service_api = new MulticraftService($api, $row);

        // Unsuspend the service
        $service_api->unsuspend(
            $package,
            $service,
            $parent_package,
            $parent_service,
            $this->serviceFieldsToObject($service->fields)
        );

        // Log the API calls
        $this->logResponses($service_api->getLogs());

        // Set any errors
        if (($errors = $service_api->errors())) {
            $this->Input->setErrors($errors);
        }

        return null;
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
        // Suspend the service
        $this->loadLib('multicraft_service');
        $service = new MulticraftService();
        $valid = $service->validate($package, $vars);

        if (($errors = $service->errors())) {
            $this->Input->setErrors($errors);
        }
        return $valid;
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
        $this->loadLib('multicraft_service');
        $multicraft_service = new MulticraftService();
        $valid = $multicraft_service->validate($service->package, $vars, true);

        if (($errors = $multicraft_service->errors())) {
            $this->Input->setErrors($errors);
        }
        return $valid;
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
        // Fetch the admin add fields
        $this->loadLib('multicraft_service');
        $service = new MulticraftService();
        return $service->getAdminAddFields($package, $vars);
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
        // Same as adding
        return $this->getAdminAddFields($package, $vars);
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
        // Fetch the client add fields
        $this->loadLib('multicraft_service');
        $service = new MulticraftService();
        return $service->getClientAddFields($package, $vars);
    }

    /**
     * Logs a set of input/output responses
     *
     * @param array $logs An array of logs, each containing an array keyed by direction (input/output), i.e.:
     *  - input/output
     *      url The URL of the request
     *      data The serialized data to be logged
     *      success True or false, whether the request was successful
     */
    private function logResponses(array $logs = [])
    {
        // Log the requests
        foreach ($logs as $log) {
            foreach ($log as $direction => $log_info) {
                if (empty($log_info)) {
                    continue;
                }

                $this->log($log_info['url'], $log_info['data'], $direction, $log_info['success']);
            }
        }
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
            'actions' => ['name' => Language::_('Multicraft.tab_client_actions', true), 'icon' => 'fas fa-cogs'],
            'players' => ['name' => Language::_('Multicraft.tab_client_players', true), 'icon' => 'fas fa-users'],
            'console' => ['name' => Language::_('Multicraft.tab_client_console', true), 'icon' => 'fas fa-terminal']
        ];
    }

    /**
     * The console tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function console($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $service_fields = $this->serviceFieldsToObject($service->fields);

        $this->view = new View('tab_client_console', 'default');
        $this->view->base_uri = $this->base_uri;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Date', 'Form', 'Html']);
        Loader::loadModels($this, ['Companies']);

        // Configure the date formats
        $this->Date->setTimezone('UTC', Configure::get('Blesta.company_timezone'));
        $this->Date->setFormats([
            'date'=>$this->Companies->getSetting(Configure::get('Blesta.company_id'), 'date_format')->value,
            'date_time'=>$this->Companies->getSetting(Configure::get('Blesta.company_id'), 'datetime_format')->value
        ]);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $row = $this->getModuleRow($package->module_row);

        // Fetch the server info
        if ($row) {
            $api_url = (isset($row->meta->panel_api_url) ? $row->meta->panel_api_url : '');
            $api = $this->getApi(
                $api_url,
                (isset($row->meta->username) ? $row->meta->username : ''),
                (isset($row->meta->key) ? $row->meta->key : '')
            );
            $server_id = (isset($service_fields->multicraft_server_id) ? $service_fields->multicraft_server_id : '');

            // Run a command or clear the log
            if (!empty($post)) {
                // Clear the server log
                if (isset($post['clear_console_log']) && $post['clear_console_log'] == 'true') {
                    $api->clearServerLog($server_id);
                } elseif (isset($post['clear_chat_log']) && $post['clear_chat_log'] == 'true') {
                    $api->clearServerChat($server_id);
                } elseif (isset($post['command']) && !empty($post['command'])) {
                    // Run the given command
                    $api->sendConsoleCommand($server_id, $post['command']);
                }

                // Log the API calls if set to log basic
                if (isset($row->meta->log_all) && $row->meta->log_all == '0') {
                    $this->logResponses($api->getLogs());
                }
            }

            // Fetch the logs
            $response = $api->getServerLog($server_id);
            $this->view->set('console_logs', (isset($response['data']) ? array_reverse($response['data']) : []));
            $response = $api->getServerChat($server_id);
            $this->view->set('chat_logs', (isset($response['data']) ? array_reverse($response['data']) : []));

            // Log the API calls if set to log everything
            if (isset($row->meta->log_all) && $row->meta->log_all == '1') {
                $this->logResponses($api->getLogs());
            }
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('server_status', $this->serverStatus($package, $service, $get, $post, $files, true));
        $this->view->set('view', $this->view->view);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'multicraft' . DS);
        return $this->view->fetch();
    }

    /**
     * The players listing tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function players($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_client_players', 'default');
        $this->view->base_uri = $this->base_uri;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $row = $this->getModuleRow($package->module_row);

        // Fetch the server info
        if ($row) {
            // Kick a player
            if (!empty($post['action']) && $post['action'] == 'kick' && !empty($post['player'])) {
                $api_url = (isset($row->meta->panel_api_url) ? $row->meta->panel_api_url : '');
                $api = $this->getApi(
                    $api_url,
                    (isset($row->meta->username) ? $row->meta->username : ''),
                    (isset($row->meta->key) ? $row->meta->key : '')
                );

                // Kick the player
                $server_id = (isset($service_fields->multicraft_server_id)
                    ? $service_fields->multicraft_server_id
                    : ''
                );
                $command = 'kick ' . $post['player'];
                $api->sendConsoleCommand($server_id, $command);

                // Log the API calls
                $this->logResponses($api->getLogs());
            }
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('server_status', $this->serverStatus($package, $service, $get, $post, $files, true));
        $this->view->set('view', $this->view->view);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'multicraft' . DS);
        return $this->view->fetch();
    }

    /**
     * The actions tab (e.g. start, stop, etc.)
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function actions($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_client_actions', 'default');
        $this->view->base_uri = $this->base_uri;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $row = $this->getModuleRow($package->module_row);

        // Fetch the server info
        if ($row) {
            $api_url = (isset($row->meta->panel_api_url) ? $row->meta->panel_api_url : '');
            $api = $this->getApi(
                $api_url,
                (isset($row->meta->username) ? $row->meta->username : ''),
                (isset($row->meta->key) ? $row->meta->key : '')
            );

            // Get the server info
            $server_id = (isset($service_fields->multicraft_server_id) ? $service_fields->multicraft_server_id : '');
            $server = $api->getServer($server_id);
            $server = (isset($server['data']['Server']) ? $server['data']['Server'] : []);
            $this->view->set('server', $server);

            // Perform an action, iff server is not suspended
            if (array_key_exists('suspended', $server) && $server['suspended'] == '0' && !empty($post)) {
                // Server actions
                if (!empty($post['submit'])
                    && in_array($post['submit'], ['start', 'restart', 'stop', 'set_daytime', 'set_nighttime'])
                ) {
                    if (in_array($post['submit'], ['set_daytime', 'set_nighttime'])) {
                        // Change game time
                        $time = ($post['submit'] == 'set_daytime') ? '0' : '14000';
                        $api->sendConsoleCommand($server_id, 'time set ' . $time);
                    } else {
                        // Perform server action
                        $action = $post['submit'] . 'Server';
                        $api->{$action}($server_id);
                    }
                } elseif (isset($post['server_name']) && isset($service_fields->multicraft_user_name)
                    && $service_fields->multicraft_user_name == '1'
                ) {
                    // Update server name if allowed to change it
                    // Update the server name
                    $this->loadLib('multicraft_service');
                    $mc_service = new MulticraftService($api);
                    $mc_service->editServer(
                        $server_id,
                        $package,
                        $service->id,
                        ['multicraft_server_name' => $post['server_name']]
                    );

                    $vars = $post;
                }

                // Log this API call if set to log only basic
                if (isset($row->meta->log_all) && $row->meta->log_all == '0') {
                    $this->logResponses($api->getLogs());
                }
            }

            // Log the API calls if set to log everything
            if (isset($row->meta->log_all) && $row->meta->log_all == '1') {
                $this->logResponses($api->getLogs());
            }
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('server_status', $this->serverStatus($package, $service, $get, $post, $files, true));
        $this->view->set('view', $this->view->view);
        $this->view->set('vars', (isset($vars) ? (object)$vars : new stdClass()));
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'multicraft' . DS);
        return $this->view->fetch();
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'multicraft' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'multicraft' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $this->serviceFieldsToObject($service->fields));

        return $this->view->fetch();
    }

    /**
     * AJAX Fetches the server status
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @param bool $return True to return the status, or false to output it (optional, default false)
     * @return mixed An array if $return is true,
     */
    public function serverStatus(
        $package,
        $service,
        array $get = null,
        array $post = null,
        array $files = null,
        $return = false
    ) {
        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $row = $this->getModuleRow($package->module_row);

        // Fetch the server info
        if ($row) {
            $api_url = (isset($row->meta->panel_api_url) ? $row->meta->panel_api_url : '');
            $api = $this->getApi(
                $api_url,
                (isset($row->meta->username) ? $row->meta->username : ''),
                (isset($row->meta->key) ? $row->meta->key : '')
            );

            // Get the server info
            $server_id = (isset($service_fields->multicraft_server_id) ? $service_fields->multicraft_server_id : '');
            $status = $api->getServerStatus($server_id, true);
            $status = (isset($status['data']) ? $status['data'] : []);

            // Log the API calls if set to log everything
            if (isset($row->meta->log_all) && $row->meta->log_all == '1') {
                $this->logResponses($api->getLogs());
            }
        }

        $status = (isset($status) ? $status : []);
        if ($return) {
            return $status;
        }
        echo json_encode($status);
        die;
    }
}
