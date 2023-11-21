<?php

/**
 * TheSslStore Module
 *
 */
class ThesslstoreModule extends Module {

    /**
     * @var string The version of this module
     */
    private static $version = "1.7.0";

    /**
     * @var string The name of this module
     */
    private static $name = "TheSSLStore Module";

    /**
     * @var string API Partner Code
     */

    private $api_partner_code = '';
    /**
     * @var string The authors of this module
     */
    private static $authors = array(
        array('name' => "The SSL Store", 'url' => "https://www.thesslstore.com")
    );

    /**
     * Initializes the module
     */
    public function __construct() {
        // Load components required by this module
        Loader::loadComponents($this, array("Input"));

        // Load the language required by this module
        Language::loadLang("thesslstore_module", null, dirname(__FILE__) . DS . "language" . DS);
    }

    /**
     * Returns the name of this module
     *
     * @return string The common name of this module
     */
    public function getName() {
        return self::$name;
    }

    /**
     * Returns the version of this module
     *
     * @return string The current version of this module
     */
    public function getVersion() {
        return self::$version;
    }

    /**
     * Returns the name and URL for the authors of this module
     *
     * @return array A numerically indexed array that contains an array with key/value pairs for 'name' and 'url', representing the name and URL of the authors of this module
     */
    public function getAuthors() {
        return self::$authors;
    }

    /**
     * Performs any necessary bootstraping actions
     */
    public function install()
    {
        // Add cron tasks for this module
        $this->addCronTasks($this->getCronTasks());
    }

    /**
     * Performs any necessary cleanup actions
     *
     * @param int $module_id The ID of the module being uninstalled
     * @param boolean $last_instance True if $module_id is the last instance across
     *  all companies for this module, false otherwise
     */
    public function uninstall($module_id, $last_instance)
    {
        if (!isset($this->Record)) {
            Loader::loadComponents($this, ['Record']);
        }
        Loader::loadModels($this, ['CronTasks']);

        $cron_tasks = $this->getCronTasks();

        if ($last_instance) {
            // Remove the cron tasks
            foreach ($cron_tasks as $task) {
                $cron_task = $this->CronTasks->getByKey($task['key'], $task['dir'], $task['task_type']);
                if ($cron_task) {
                    $this->CronTasks->deleteTask($cron_task->id, $task['task_type'], $task['dir']);
                }
            }
        }

        // Remove individual cron task runs
        foreach ($cron_tasks as $task) {
            $cron_task_run = $this->CronTasks
                ->getTaskRunByKey($task['key'], $task['dir'], false, $task['task_type']);
            if ($cron_task_run) {
                $this->CronTasks->deleteTaskRun($cron_task_run->task_run_id);
            }
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
        // Upgrade if possible
        if (version_compare($current_version, '1.7.0', '<')) {
            $this->addCronTasks($this->getCronTasks());
        }
    }

    /**
     * Runs the cron task identified by the key used to create the cron task
     *
     * @param string $key The key used to create the cron task
     * @see CronTasks::add()
     */
    public function cron($key)
    {
        if ($key == 'tss_order_sync') {
            $this->orderSynchronization();
        }
    }

    /**
     * Retrieves cron tasks available to this module along with their default values
     *
     * @return array A list of cron tasks
     */
    private function getCronTasks()
    {
        return [
            [
                'key' => 'tss_order_sync',
                'task_type' => 'module',
                'dir' => 'thesslstore_module',
                'name' => Language::_('ThesslstoreModule.getCronTasks.tss_order_sync_name', true),
                'description' => Language::_('ThesslstoreModule.getCronTasks.tss_order_sync_desc', true),
                'type' => 'time',
                'type_value' => '00:00:00',
                'enabled' => 1
            ]
        ];
    }

    /**
     * Attempts to add new cron tasks for this module
     *
     * @param array $tasks A list of cron tasks to add
     */
    private function addCronTasks(array $tasks)
    {
        Loader::loadModels($this, ['CronTasks']);
        foreach ($tasks as $task) {
            $task_id = $this->CronTasks->add($task);

            if (!$task_id) {
                $cron_task = $this->CronTasks->getByKey($task['key'], $task['dir'], $task['task_type']);
                if ($cron_task) {
                    $task_id = $cron_task->id;
                }
            }

            if ($task_id) {
                $task_vars = ['enabled' => $task['enabled']];
                if ($task['type'] === 'time') {
                    $task_vars['time'] = $task['type_value'];
                } else {
                    $task_vars['interval'] = $task['type_value'];
                }

                $this->CronTasks->addTaskRun($task_id, $task_vars);
            }
        }
    }

    /**
     * Synchronization order data
     */
    private function orderSynchronization()
    {
        Loader::loadModels($this, ['Services']);
        Loader::loadHelpers($this, ['Date']);
        $this->Date->setTimezone('UTC', 'UTC');

        // Get module row id
        $module_row_id = 0;
        $api_partner_code = '';
        $api_auth_token = '';
        $api_mode = '';

        $rows = $this->getModuleRows();
        foreach ($rows as $row) {
            if (isset($row->meta->thesslstore_reseller_name)) {
                $module_row_id = $row->id;
                $api_mode = $row->meta->api_mode;
                if ($api_mode == 'TEST') {
                    $api_partner_code = $row->meta->api_partner_code_test;
                    $api_auth_token = $row->meta->api_auth_token_test;
                } elseif ($api_mode == 'LIVE') {
                    $api_partner_code = $row->meta->api_partner_code_live;
                    $api_auth_token = $row->meta->api_auth_token_live;
                }
                break;
            }
        }

        $api = $this->getApi($api_partner_code, $api_auth_token, $api_mode);

        $two_month_before_date = strtotime('-2 Months') * 1000; // Convert into milliseconds
        $today_date = strtotime('now') * 1000; // Convert into milliseconds

        $order_query_request = new order_query_request();
        $order_query_request->StartDate = '/Date(' . $two_month_before_date . ')/';
        $order_query_request->EndDate = '/Date(' . $today_date . ')/';

        $order_query_resp = $api->order_query($order_query_request);

        // Cannot continue without an order query
        if (empty($order_query_resp) || !is_array($order_query_resp)) {
            return;
        }

        // Fetch all SSL Store module active/suspended services to sync
        $services = $this->getAllServiceIds();

        // Sync the renew date and FQDN of all SSL Store services
        foreach ($services as $service) {
            // Fetch the service
            if (!($service_obj = $this->Services->get($service->id))) {
                continue;
            }

            $fields = $this->serviceFieldsToObject($service_obj->fields);

            // Require the SSL Store order ID field be available
            if (!isset($fields->thesslstore_order_id)) {
                continue;
            }

            foreach ($order_query_resp as $order) {
                // Skip orders that don't match the service field's order ID
                if ($order->TheSSLStoreOrderID != $fields->thesslstore_order_id) {
                    continue;
                }

                // Update renewal date
                if (!empty($order->CertificateEndDateInUTC)) {
                    // Get the date 30 days before the certificate expires
                    $end_date = $this->Date->modify(
                        strtotime($order->CertificateEndDateInUTC),
                        '-30 days',
                        'Y-m-d H:i:s',
                        'UTC'
                    );

                    if ($end_date != $service_obj->date_renews) {
                        $vars['date_renews'] = $end_date . 'Z';
                        $this->Services->edit($service_obj->id, $vars, $bypass_module = true);
                    }
                }

                // Update domain name(fqdn)
                if (!empty($order->CommonName)) {
                    if (isset($fields->thesslstore_fqdn)) {
                        if ($fields->thesslstore_fqdn != $order->CommonName) {
                            // Update
                            $this->Services->editField($service_obj->id, [
                                'key' => 'thesslstore_fqdn',
                                'value' => $order->CommonName,
                                'encrypted' => 0
                            ]);
                        }
                    } else {
                        // Add
                        $this->Services->addField($service_obj->id, [
                            'key' => 'thesslstore_fqdn',
                            'value' => $order->CommonName,
                            'encrypted' => 0
                        ]);
                    }
                }
                break;
            }
        }
    }

    /**
     * Retrieves a list of all service IDs representing active/suspended SSL Store module services for this company
     *
     * @param array $filters An array of filter options including:
     *  - renew_start_date The service's renew date to search from
     *  - renew_end_date The service's renew date to search to
     * @return array A list of stdClass objects containing:
     *  - id The ID of the service
     */
    private function getAllServiceIds(array $filters = [])
    {
        Loader::loadComponents($this, ['Record']);

        $this->Record->select(['services.id'])
            ->from('services')
                ->on('service_fields.key', '=', 'thesslstore_order_id')
            ->innerJoin('service_fields', 'service_fields.service_id', '=', 'services.id', false)
            ->innerJoin('clients', 'clients.id', '=', 'services.client_id', false)
            ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)
            ->where('services.status', 'in', ['active', 'suspended'])
            ->where('client_groups.company_id', '=', Configure::get('Blesta.company_id'));

        if (!empty($filters['renew_start_date'])) {
            $this->Record->where('services.date_renews', '>=', $filters['renew_start_date']);
        }

        if (!empty($filters['renew_end_date'])) {
            $this->Record->where('services.date_renews', '<=', $filters['renew_end_date']);
        }

        return $this->Record->group(['services.id'])
            ->fetchAll();
    }

    /**
     * Returns the value used to identify a particular service
     *
     * @param stdClass $service A stdClass object representing the service
     * @return string A value used to identify this service amongst other similar services
     */
    public function getServiceName($service) {
        foreach ($service->fields as $field) {
            if ($field->key == "thesslstore_fqdn")
                return $field->value;
        }
        return "New";
    }

    /**
     * Returns a noun used to refer to a module row (e.g. "Server", "VPS", "Reseller Account", etc.)
     *
     * @return string The noun used to refer to a module row
     */
    public function moduleRowName() {
        return Language::_("ThesslstoreModule.module_row", true);
    }

    /**
     * Returns a noun used to refer to a module row in plural form (e.g. "Servers", "VPSs", "Reseller Accounts", etc.)
     *
     * @return string The noun used to refer to a module row in plural form
     */
    public function moduleRowNamePlural() {
        return Language::_("ThesslstoreModule.module_row_plural", true);
    }

    /**
     * Returns a noun used to refer to a module group (e.g. "Server Group", "Cloud", etc.)
     *
     * @return string The noun used to refer to a module group
     */
    public function moduleGroupName() {
        return null;
    }

    /**
     * Returns the key used to identify the primary field from the set of module row meta fields.
     * This value can be any of the module row meta fields.
     *
     * @return string The key used to identify the primary field from the set of module row meta fields
     */
    public function moduleRowMetaKey() {
        return "thesslstore_reseller_name";
    }

    /**
     * Returns the value used to identify a particular package service which has
     * not yet been made into a service. This may be used to uniquely identify
     * an uncreated service of the same package (i.e. in an order form checkout)
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @return string The value used to identify this package service
     * @see Module::getServiceName()
     */
    public function getPackageServiceName($packages, array $vars=null) {
        if (isset($vars['thesslstore_reseller_name']))
            return $vars['thesslstore_reseller_name'];
        return null;
    }

    /**
     * Fetches the HTML content to display when viewing the service info in the
     * client interface.
     *
     * @param stdClass $service A stdClass object representing the service
     * @param stdClass $package A stdClass object representing the service's package
     * @return string HTML content containing information to display when viewing the service info
     */
    public function getClientServiceInfo($service, $package) {

        if($service->status == 'active') {
            // Load the view into this object, so helpers can be automatically added to the view
            $this->view = new View("client_service_info", "default");
            $this->view->base_uri = $this->base_uri;
            $this->view->setDefaultView("components" . DS . "modules" . DS . "thesslstore_module" . DS);

            // Load the helpers required for this view
            Loader::loadHelpers($this, array("Form", "Html"));

            // Get the service fields
            $service_fields = $this->serviceFieldsToObject($service->fields);

            $order_resp = $this->getSSLOrderStatus($service_fields->thesslstore_order_id);
            if($order_resp != NULL && $order_resp->AuthResponse->isError == false) {

                $store_order_id = $order_resp->TheSSLStoreOrderID;
                $vendor_order_id = $order_resp->VendorOrderID;
                $major_status = $order_resp->OrderStatus->MajorStatus;
                $minor_status = $order_resp->OrderStatus->MinorStatus;

                $this->view->set("store_order_id", $store_order_id);
                $this->view->set("vendor_order_id", $vendor_order_id);
                $this->view->set("major_status", $major_status);
                $this->view->set("minor_status", $minor_status);


                return $this->view->fetch();
            }
        }
        return "";
    }

    /**
     * Initializes the API and returns an instance of that object with the given $partner_code, and $auth_token set
     *
     * @param string $partner_code The TheSSLStore partner Code
     * @param string $auth_token The Auth token to the TheSSLStore server
     * @param string $sandbox Whether sandbox or not
     * @param stdClass $row A stdClass object representing a single reseller
     * @return TheSSLStoreApi The TheSSLStoreApi instance
     */
    public function getApi($api_partner_code = null, $api_auth_token = null, $api_mode = 'TEST', $IsUsedForTokenSystem = false, $token= '' ) {

        Loader::load(dirname(__FILE__) . DS . "api" . DS . "thesslstoreApi.php");

        if($api_partner_code == null) {

            $module_rows = $this->getModuleRows();

            //get api data using module manager because in "manageAddRow" module data is not initiated
            if(!$module_rows) {

                $company_id = Configure::get("Blesta.company_id");
                //Load Model
                Loader::loadModels($this, array("ModuleManager"));
                $modules = $this->ModuleManager->getInstalled();

                foreach ($modules as $module) {
                    $module_data = $this->ModuleManager->get($module->id);
                    foreach ($module_data->rows as $row) {

                        if (isset($row->meta->thesslstore_reseller_name)) {

                            $api_mode = $row->meta->api_mode;
                            $module_rows = $module_data->rows;
                            $this->setModule($module);
                            $this->setModuleRow($module_rows);
                            break 2;

                        }

                    }
                }
            }


            foreach ($module_rows as $row) {
                if (isset($row->meta->api_mode)) {
                    $api_mode = $row->meta->api_mode;
                    if($api_mode == 'LIVE'){
                        $api_partner_code = $row->meta->api_partner_code_live;
                        $api_auth_token = $row->meta->api_auth_token_live;
                    }
                    else{
                        $api_partner_code = $row->meta->api_partner_code_test;
                        $api_auth_token = $row->meta->api_auth_token_test;
                    }
                    break;
                }
            }
        }

        $this->api_partner_code = $api_partner_code;

        $api = new thesslstoreApi($api_partner_code, $api_auth_token, $token, $tokenID = '', $tokenCode = '', $IsUsedForTokenSystem, $api_mode);

        return $api;
    }

    /**
     * Validates whether or not the connection details are valid by attempting to fetch
     * the number of accounts that currently reside on the server
     *
     * @param string $api_username The reseller API username
     * @param array $vars A list of other module row fields including:
     * 	- api_token The API token
     * 	- sandbox "true" or "false" as to whether sandbox is enabled
     * @return boolean True if the connection is valid, false otherwise
     */
    public function validateCredential($api_partner_code,$vars,$api_mode='TEST') {
        try {

            $api_partner_code = "";
            $api_auth_token = "";
            if($api_mode == "LIVE"){
                $api_partner_code = (isset($vars['api_partner_code_live']) ? $vars['api_partner_code_live'] : "");
                $api_auth_token = (isset($vars['api_auth_token_live']) ? $vars['api_auth_token_live'] : "");
            }
            elseif($api_mode == "TEST"){
                $api_partner_code = (isset($vars['api_partner_code_test']) ? $vars['api_partner_code_test'] : "");
                $api_auth_token = (isset($vars['api_auth_token_test']) ? $vars['api_auth_token_test'] : "");
            }

            $module_row = (object)array('meta' => (object)$vars);

            $api = $this->getApi($api_partner_code, $api_auth_token, $api_mode);

            $health_validate_request = new health_validate_request();

            $response = $api->health_validate($health_validate_request);

            if($response->isError == true)
            {
                // Log the response
                $this->log($api_partner_code, serialize($response), "output", false);
                return false;
            }
            else
            {
                // Log the response
                $this->log($api_partner_code, serialize($response), "output", true);
                return true;
            }

        }
        catch (Exception $e) {
            return false;
            // Trap any errors encountered, could not validate connection
        }
        return false;
    }

    /**
     * Retrieves a list of rules for validating adding/editing a module row
     *
     * @param array $vars A list of input vars
     * @return array A list of rules
     */
    private function getCredentialRules(array &$vars) {

        return array(
            'thesslstore_reseller_name' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_reseller_name.empty", true)
                )
            ),
            'api_partner_code_live' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.api_partner_code_live.empty", true)
                )
            ),
            'api_auth_token_live' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.api_auth_token_live.empty", true)
                ),
                'valid' => array(
                    'rule' => array(array($this, "validateCredential"), $vars, "LIVE"),
                    'message' => Language::_("ThesslstoreModule.!error.api_partner_code_live.valid", true)
                )
            ),
            'api_partner_code_test' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.api_partner_code_test.empty", true)
                )
            ),
            'api_auth_token_test' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.api_auth_token_test.empty", true)
                ),
                'valid' => array(
                    'rule' => array(array($this, "validateCredential"), $vars, "TEST"),
                    'message' => Language::_("ThesslstoreModule.!error.api_partner_code_test.valid", true)
                )
            ),
            'api_mode' => array(
            )

        );
    }

    /**
     * Retrieves a list of rules for validating adding/editing a profit margin row
     *
     * @param array $vars A list of input vars
     * @return array A list of rules
     */
    private function getImportPackageRules(array &$vars) {
        return array(
            'profit_margin' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.profit_margin.empty", true)
                ),
                'valid' => array(
                    'rule' => array("isPassword", 1, "num"),
                    'message' => Language::_("ThesslstoreModule.!error.profit_margin.valid", true)
                )
            )

        );
    }

    /**
     * Validates input data when attempting to add a package, returns the meta
     * data to save when adding a package. Performs any action required to add
     * the package on the remote server. Sets Input errors on failure,
     * preventing the package from being added.
     *
     * @param array An array of key/value pairs used to add the package
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     * 	- key The key for this meta field
     * 	- value The value for this key
     * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function addPackage(array $vars=null) {
        $this->Input->setRules($this->getPackageRules($vars));

        $meta = array();
        if ($this->Input->validates($vars)) {
            // Return all package meta fields
            foreach ($vars['meta'] as $key => $value) {
                $meta[] = array(
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                );
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
     * 	- key The key for this meta field
     * 	- value The value for this key
     * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function editPackage($package, array $vars=null) {
        $this->Input->setRules($this->getPackageRules($vars));

        $meta = array();
        if ($this->Input->validates($vars)) {
            // Return all package meta fields
            foreach ($vars['meta'] as $key => $value) {
                $meta[] = array(
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                );
            }
        }

        return $meta;
    }

    /**
     * Retrieves a list of rules for validating adding/editing a package
     *
     * @param array $vars A list of input vars
     * @return array A list of rules
     */
    private function getPackageRules(array $vars = null) {
        $rules = array(
            'meta[thesslstore_product_code]' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("TheSSLStore.!error.meta[thesslstore_product_code].valid", true)
                )
            ),
            'meta[thesslstore_vendor_name]' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("TheSSLStore.!error.meta[thesslstore_vendor_name].valid", true)
                )
            ),
            'meta[thesslstore_is_code_signing]' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("TheSSLStore.!error.meta[thesslstore_is_code_signing].valid", true)
                )
            ),
            'meta[thesslstore_min_san]' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("TheSSLStore.!error.meta[thesslstore_min_san].valid", true)
                )
            ),
            'meta[thesslstore_is_scan_product]' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("TheSSLStore.!error.meta[thesslstore_is_scan_product].valid", true)
                )
            ),
            'meta[thesslstore_validation_type]' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("TheSSLStore.!error.meta[thesslstore_validation_type].valid", true)
                )
            )


        );
        return $rules;
    }

    /**
     * Returns the rendered view of the manage module page
     *
     * @param mixed $module A stdClass object representing the module and its rows
     * @param array $vars An array of post data submitted to or on the manage module page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the manager module page
     */
    public function manageModule($module, array &$vars) {

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View("manage", "default");
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView("components" . DS . "modules" . DS . "thesslstore_module" . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, array("Form", "Html", "Widget"));

        $link_buttons = array();
        $credential_added = false;

        foreach($module->rows as $row){
            if(isset($row->meta->thesslstore_reseller_name)){

                $credential_added = true;
                $link_buttons[] = array('name'=>Language::_("ThesslstoreModule.replacement_order_row",true),'attributes'=>array('href'=>array('href'=>$this->base_uri . "settings/company/modules/addrow/" . $module->id."?scr=replacementorder")));
                $link_buttons[] = array('name'=>Language::_("ThesslstoreModule.edit_credential_row", true), 'attributes'=>array('href'=>$this->base_uri . "settings/company/modules/addrow/" . $module->id."?scr=editcredential"));
                $link_buttons[] = array('name'=>Language::_("ThesslstoreModule.import_product_row",true),'attributes'=>array('href'=>array('href'=>$this->base_uri . "settings/company/modules/addrow/" . $module->id."?scr=importpackage")));
                $link_buttons[] = array('name'=>Language::_("ThesslstoreModule.setup_price_row",true),'attributes'=>array('href'=>array('href'=>$this->base_uri . "settings/company/modules/addrow/" . $module->id."?scr=setupprice")));
                break;
            }
        }
        if($credential_added == false){
            $link_buttons = array(
                array('name'=>Language::_("ThesslstoreModule.add_credential_row", true), 'attributes'=>array('href'=>$this->base_uri . "settings/company/modules/addrow/" . $module->id."?scr=addcredential"))
            );
        }

        $this->view->set("module", $module);
        $this->view->set("link_buttons",$link_buttons);



        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the add module row page
     *
     * @param array $vars An array of post data submitted to or on the add module row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the add module row page
     */
    public function manageAddRow(array &$vars) {
        // Load the view into this object, so helpers can be automatically added to the view
        $scr = isset($_GET['scr']) ? $_GET['scr'] : '';
        if($scr == 'addcredential') {

            $this->view = new View("add_credential", "default");
            $this->view->base_uri = $this->base_uri;
            $this->view->setDefaultView("components" . DS . "modules" . DS . "thesslstore_module" . DS);

            // Load the helpers required for this view
            Loader::loadHelpers($this, array("Form", "Html", "Widget"));

            $this->view->set("vars", (object)$vars);
            return $this->view->fetch();
        }
        elseif($scr == "editcredential"){

            //This function is just called to set row meta because in current method row meta is not set by blesta.
            $this->getApi();

            $this->view = new View("add_credential", "default");
            $this->view->base_uri = $this->base_uri;
            $this->view->setDefaultView("components" . DS . "modules" . DS . "thesslstore_module" . DS);

            // Load the helpers required for this view
            Loader::loadHelpers($this, array("Form", "Html", "Widget"));

            $module_rows = $this->getModuleRows();
            $vars = array();
            foreach($module_rows as $row){
                if(isset($row->meta->thesslstore_reseller_name)){
                    $vars = $row->meta;
                    $vars->module_row_id = $row->id;
                    break;
                }
            }
            $this->view->set("vars",$vars);
            return $this->view->fetch();
        }
        elseif($scr == 'importpackage'){
            $this->view = new View("import_packages", "default");
            $this->view->base_uri = $this->base_uri;
            $this->view->setDefaultView("components" . DS . "modules" . DS . "thesslstore_module" . DS);

            // Load the helpers required for this view
            Loader::loadHelpers($this, array("Form", "Html", "Widget"));

            /* Retrieve the company ID */
            $companyID=Configure::get("Blesta.company_id");
            // Load the Loader to fetch Package Groups to assign to the packages
            Loader::loadModels($this, array("PackageGroups"));
            $packageGroupsArray=$this->PackageGroups->getAll($companyID);
            foreach ($packageGroupsArray as $key => $value) {
                $packageGroups[$value->id] = $value->name;
            }
            if (!empty($packageGroupsArray)) {
                $vars['packageGroups']=$packageGroups;
                $vars['packageGroupsArray'] = "true";
            }
            else
            {
                $vars['packageGroupsArray'] = "false";
            }
            // Set unspecified checkboxes

            $this->view->set("vars", (object)$vars);
            return $this->view->fetch();
        }
        elseif($scr == 'setupprice'){
            $this->view = new View("setup_price", "default");
            $this->view->base_uri = $this->base_uri;
            $this->view->setDefaultView("components" . DS . "modules" . DS . "thesslstore_module" . DS);

            // Load the helpers required for this view
            Loader::loadHelpers($this, array("Form", "Html", "Widget"));

            //Get current company ID
            $company_id = Configure::get("Blesta.company_id");

           //Load Packages Model
            Loader::loadModels($this, array("Packages","PackageOptions"));

            $package_data = array();
            $packages = $this->Packages->getAll($company_id, $order=array('id'=>"ASC"), $status='active');
            foreach($packages as $pack){

                $package = $this->Packages->get($pack->id);

                if(isset($package->meta->thesslstore_product_code)) {

                    $package_data[$pack->id]['name'] = $package->name;
                    $package_data[$pack->id]['group_name'] = isset($package->groups[0]->name) ? $package->groups[0]->name : '';
                    $package_data[$pack->id]['product_code'] =  $package->meta->thesslstore_product_code;

                    foreach($package->pricing as $pricing){
                        if(($pricing->term == 12 && $pricing->period == 'month') || ($pricing->term == 1 && $pricing->period == 'year') && $pricing->currency == 'USD'){
                            $package_data[$pack->id]['pricing']['1year']['pricing_id'] = $pricing->pricing_id;
                            $package_data[$pack->id]['pricing']['1year']['price'] = $pricing->price;
                        }
                        elseif(($pricing->term == 24 && $pricing->period == 'month') || ($pricing->term == 2 && $pricing->period == 'year') && $pricing->currency == 'USD'){
                            $package_data[$pack->id]['pricing']['2year']['pricing_id'] = $pricing->pricing_id;
                            $package_data[$pack->id]['pricing']['2year']['price'] = $pricing->price;
                        }
                        elseif(($pricing->term == 36 && $pricing->period == 'month') || ($pricing->term == 3 && $pricing->period == 'year') && $pricing->currency == 'USD'){
                            $package_data[$pack->id]['pricing']['3year']['pricing_id'] = $pricing->pricing_id;
                            $package_data[$pack->id]['pricing']['3year']['price'] = $pricing->price;
                        }
                    }

                    //Get Options Price
                    $package_data[$pack->id]['has_additional_san'] = false;
                    $package_data[$pack->id]['has_additional_server'] = false;
                    $options = $this->PackageOptions->getByPackageId($pack->id);
                    foreach($options as $option){
                        if($option->name == 'additional_san' || $option->name == 'additional_server'){
                            if($option->name == 'additional_san'){
                                $key = 'san';
                                $package_data[$pack->id]['has_additional_san'] = true;
                            }

                            if($option->name == 'additional_server'){
                                $key = 'server';
                                $package_data[$pack->id]['has_additional_server'] = true;
                            }

                            if(isset($option->values[0]->pricing)){
                                foreach($option->values[0]->pricing as $pricing){
                                    if(($pricing->term == 12 && $pricing->period == 'month') || ($pricing->term == 1 && $pricing->period == 'year') && $pricing->currency == 'USD'){
                                        $package_data[$pack->id][$key.'_pricing']['1year']['pricing_id'] = $pricing->pricing_id;
                                        $package_data[$pack->id][$key.'_pricing']['1year']['price'] = $pricing->price;
                                    }
                                    elseif(($pricing->term == 24 && $pricing->period == 'month') || ($pricing->term == 2 && $pricing->period == 'year' ) && $pricing->currency == 'USD'){
                                        $package_data[$pack->id][$key.'_pricing']['2year']['pricing_id'] = $pricing->pricing_id;
                                        $package_data[$pack->id][$key.'_pricing']['2year']['price'] = $pricing->price;
                                    }
                                    elseif(($pricing->term == 36 && $pricing->period == 'month') || ($pricing->term == 3 && $pricing->period == 'year') && $pricing->currency == 'USD'){
                                        $package_data[$pack->id][$key.'_pricing']['3year']['pricing_id'] = $pricing->pricing_id;
                                        $package_data[$pack->id][$key.'_pricing']['3year']['price'] = $pricing->price;
                                    }

                                }
                            }
                        }
                    }
                }
            }
            $reseller_price_link = explode("?",$_SERVER['REQUEST_URI']);
            $reseller_price_link = $reseller_price_link[0]."?scr=resellerprice";
            $this->view->set("package_data", $package_data);
            $this->view->set("vars",(object)$vars);
            $this->view->set("reseller_price_link",$reseller_price_link);
            return $this->view->fetch();
        }
        elseif($scr == 'resellerprice'){
            $this->view = new View("reseller_price", "default");
            $this->view->base_uri = $this->base_uri;
            $this->view->setDefaultView("components" . DS . "modules" . DS . "thesslstore_module" . DS);

            // Load the helpers required for this view
            Loader::loadHelpers($this, array("Html", "Widget"));

            $products = $this->getProducts();
            $module_rows = $this->getModuleRows();
            $api_mode = '';
            foreach($module_rows as $row){
                if(isset($row->meta->api_mode)){
                    $api_mode = $row->meta->api_mode;
                    break;
                }
            }


            $reseller_pricing = array();
            foreach($products as $product){
                $reseller_pricing[$product->ProductCode]['has_additional_san'] = false;
                $reseller_pricing[$product->ProductCode]['has_additional_server'] = false;
                $has_additonal_san = false;
                $has_additonal_server = false;


                if($product->MaxSan - $product->MinSan > 0) {
                    $reseller_pricing[$product->ProductCode]['has_additional_san'] = true;
                    $has_additonal_san = true;
                }

                if($product->isNoOfServerFree == false && $product->isCodeSigning == false && $product->isScanProduct == false){
                    $reseller_pricing[$product->ProductCode]['has_additional_server'] = true;
                    $has_additonal_server = true;
                }





                $reseller_pricing[$product->ProductCode]['name'] = $product->ProductName;
                foreach($product->PricingInfo as $pricing_info){
                    if($pricing_info->NumberOfMonths == 12){
                        $reseller_pricing[$product->ProductCode]['1year_price'] = number_format($pricing_info->Price, 2, '.','');
                        if($has_additonal_san){
                            $reseller_pricing[$product->ProductCode]['1year_san_price'] = number_format($pricing_info->PricePerAdditionalSAN, 2, '.', '') ;
                        }
                        if($has_additonal_server){
                            $reseller_pricing[$product->ProductCode]['1year_server_price'] = number_format($pricing_info->PricePerAdditionalServer, 2, '.', '');
                        }
                    }
                    elseif($pricing_info->NumberOfMonths == 24){
                        $reseller_pricing[$product->ProductCode]['2year_price'] = number_format($pricing_info->Price, 2, '.', '');
                        if($has_additonal_san){
                            $reseller_pricing[$product->ProductCode]['2year_san_price'] = number_format($pricing_info->PricePerAdditionalSAN, 2, '.', '');
                        }
                        if($has_additonal_server){
                            $reseller_pricing[$product->ProductCode]['2year_server_price'] = number_format($pricing_info->PricePerAdditionalServer, 2, '.', '');
                        }
                    }
                    elseif($pricing_info->NumberOfMonths == 36){
                        $reseller_pricing[$product->ProductCode]['3year_price'] = number_format($pricing_info->Price, 2, '.', '');
                        if($has_additonal_san){
                            $reseller_pricing[$product->ProductCode]['3year_san_price'] = number_format($pricing_info->PricePerAdditionalSAN, 2, '.', '');
                        }
                        if($has_additonal_server){
                            $reseller_pricing[$product->ProductCode]['3year_server_price'] = number_format($pricing_info->PricePerAdditionalServer, 2, '.', '');
                        }
                    }

                }

            }

            $this->view->set("vars", (object)$vars);
            $this->view->set("api_mode",$api_mode);
            $this->view->set("reseller_pricing",$reseller_pricing);
            return $this->view->fetch();
        }elseif($scr == "replacementorder"){
            //This function is just called to set row meta because in current method row meta is not set by blesta.
            $this->getApi();

            $this->view = new View("replacement_orders", "default");
            $this->view->base_uri = $this->base_uri;
            $this->view->setDefaultView("components" . DS . "modules" . DS . "thesslstore_module" . DS);

            // Load the helpers required for this view
            Loader::loadHelpers($this, array("Form", "Html", "Widget"));

            $api = $this->getApi();
            $orders=array();
            $orderReplacementRequest = new order_replacement_request();
            if(isset($_REQUEST['date'])) {
                $replace_by_date = $_REQUEST['date'];
                $replace_by_date = strtotime($replace_by_date); //return timestamp in second.
                $replacebydate = $replace_by_date*1000; //convert into millisecond
                $orderReplacementRequest->ReplaceByDate = "/Date($replacebydate)/";
            }
            $this->log($this->api_partner_code . "|ssl-products", serialize($orderReplacementRequest), "input", true);
            if($api->order_replacement($orderReplacementRequest)->AuthResponse->isError==false)
            {
                $orders = $api->order_replacement($orderReplacementRequest)->Orders;
            }
            $export_to_csv_link = explode("?",$_SERVER['REQUEST_URI']);
            $export_to_csv_link = $export_to_csv_link[0]."?scr=exportcsv";
            $this->view->set("vars", (object)$vars);
            $this->view->set("orders",$orders);
            $this->view->set("export_to_csv_link",$export_to_csv_link);
            return $this->view->fetch();
        }elseif($scr == "exportcsv"){

            //This function is just called to set row meta because in current method row meta is not set by blesta.
            $this->getApi();

            $api = $this->getApi();

            $orderReplacementRequest = new order_replacement_request();

            $this->log($this->api_partner_code . "|ssl-products", serialize($orderReplacementRequest), "input", true);
            $orders = $api->order_replacement($orderReplacementRequest)->Orders;

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=symantecreplacementorders.csv');

            // create a file pointer connected to the output stream
            $output = fopen('php://output', 'w');
            $delimiter = ",";
            $enclosure = '"';
            $heading = array('Date', 'TheSSLStore Order ID','Vendor ID', 'Product Name', 'Common Name','Issued Date', 'Expire Date','Status','Action', 'Replace By Date');

            // output the column headings
            fputcsv($output, $heading, $delimiter, $enclosure);


            // fetch the data
            foreach ($orders as $row) {
                $line = array();
                $line[] = $row->PurchaseDate;
                $line[] = $row->TheSSLStoreOrderID;
                $line[] = $row->VendorOrderID;
                $line[] = $row->ProductName;
                $line[] = $row->CommonName;
                $line[] = $row->CertificateStartDate;
                $line[] = $row->CertificateEndDate;
                $line[] = $row->Status;
                $line[] = $row->Action;
                $line[] = $row->ReplaceByDate;

                fputcsv($output, $line, $delimiter, $enclosure);
            }

            fclose($output);
            exit;
        }
        else{
            $this->view = new View("invalid_action", "default");
            $this->view->base_uri = $this->base_uri;
            $this->view->setDefaultView("components" . DS . "modules" . DS . "thesslstore_module" . DS);

            // Load the helpers required for this view
            Loader::loadHelpers($this, array("Form", "Html", "Widget"));

            $this->view->set("vars", (object)$vars);
            return $this->view->fetch();
        }
    }

    /**
     * Adds the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being added.
     *
     * @param array $vars An array of module info to add
     * @return array A numerically indexed array of meta fields for the module row containing:
     * 	- key The key for this meta field
     * 	- value The value for this key
     * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function addModuleRow(array &$vars) {
        $scr = isset($_GET['scr']) ? $_GET['scr'] : '';
        if($scr == 'addcredential') {

            foreach($this->getModuleRows() as $row){
                if(isset($row->meta->api_partner_code_live)){
                    $this->Input->setErrors(array('invalid_action' => array('internal' => Language::_("ThesslstoreModule.!error.credential_already_exist", true))));
                    return false;
                }
            }
            $meta_fields = array("thesslstore_reseller_name","api_partner_code_live", "api_auth_token_live", "api_partner_code_test",
                "api_auth_token_test", "api_mode", "hide_changeapprover_option");
            $encrypted_fields = array("api_partner_code_live", "api_auth_token_live", "api_partner_code_test", "api_auth_token_test");


            $this->Input->setRules($this->getCredentialRules($vars));

            // Validate module row
            if ($this->Input->validates($vars)) {
                // Build the meta data for this row
                $meta = array();
                foreach ($vars as $key => $value) {

                    if (in_array($key, $meta_fields)) {
                        $meta[] = array(
                            'key' => $key,
                            'value' => $value,
                            'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                        );
                    }
                }

                return $meta;
            }
        }
        elseif($scr == 'editcredential'){

            $old_api_mode = '';
            foreach($this->getModuleRows() as $row){
                if(isset($row->meta->thesslstore_reseller_name)){
                    $old_api_mode = $row->meta->api_mode;
                    break;
                }
            }
            $this->Input->setRules($this->getCredentialRules($vars));
            if(!empty($vars['hide_changeapprover_option'])){
                $hide_changeapprover_option=$vars['hide_changeapprover_option'];
            }
            else{
                $hide_changeapprover_option='NO';
            }
            if ($this->Input->validates($vars)) {
                $meta['thesslstore_reseller_name'] = $vars['thesslstore_reseller_name'];
                $meta['api_partner_code_live'] = $vars['api_partner_code_live'];
                $meta['api_auth_token_live'] = $vars['api_auth_token_live'];
                $meta['api_partner_code_test'] = $vars['api_partner_code_test'];
                $meta['api_auth_token_test'] = $vars['api_auth_token_test'];
                $meta['api_mode'] = $vars['api_mode'];
                $meta['hide_changeapprover_option'] = $hide_changeapprover_option;

                Loader::loadModels($this, array("ModuleManager"));
                $this->ModuleManager->editRow($vars['module_row_id'], $meta);

                if($old_api_mode == 'TEST' && $vars['api_mode'] == 'LIVE'){
                    //Redirect with success message
                    $url = explode("?",$_SERVER['REQUEST_URI']);
                    header('Location:' . $url[0].'?scr=setupprice&msg=modeupdated');
                    exit();
                }

                //Redirect with success message
                $url = explode("?",$_SERVER['REQUEST_URI']);
                header('Location:' . $url[0].'?scr=editcredential&msg=success');
                exit();


            }

        }
        elseif($scr == 'importpackage') {
            $this->Input->setRules($this->getImportPackageRules($vars));
            if ($this->Input->validates($vars)) {

                /* Retrieve the module row ID */
                $module_rows = $this->getModuleRows();
                foreach ($module_rows as $row) {
                    if (isset($row->meta->api_partner_code_live)) {
                        $moduleRowID = $row->id;
                        break;
                    }
                }
                /* Get the products array */
                $products = $this->getProducts();

                /* Call the function to get the module name */
                $moduleName = $this->getName();

                /* Load the Loader to fetch info of All the installed Module */
                Loader::loadModels($this, array("ModuleManager"));
                $moduleArray = $this->ModuleManager->getInstalled();
                /* Retrieve the Company ID which was assigned to our module */
                $moduleIDObject = null;
                foreach ($moduleArray as $info) {
                    if ($moduleName == $info->name) {
                        $moduleIDObject = $info;
                        break;
                    }
                }
                $moduleID = $moduleIDObject->id;


                /* Retrieve the existing packages array for the product group */
                Loader::loadModels($this, array("Packages"));
                $packagesByGroupArray = $this->Packages->getAllPackagesByGroup($vars['product_group']);

                $already_added_packages = array();

                foreach($packagesByGroupArray as $pack_data){
                    if ($pack_data->module_id == $moduleID) {
                        $package = $this->Packages->get($pack_data->id);
                        $already_added_packages[] = $package->meta->thesslstore_product_code;
                    }
                }

                //get Email content
                $email_content = $this->emailContent();

                $packageArray = array();
                /* Set the import package count default ZERO */
                $countOfImportPackages = 0;
                foreach ($products as $key => $value) {
                    if(!in_array($value->ProductCode, $already_added_packages)){

                        $packageArray['names'] = [['lang' => 'en_us', 'name' => $value->ProductName]];
                        $packageArray['status'] = 'active';
                        $packageArray['qty_unlimited'] = 'true';
                        $ProductDescription = $value->ProductDescription;
                        $sslfeaturelink = $value->ProductSlug;
                        $viewmorelink = "<a class='mod_view_more' href='javascript&#58;void(0);' onclick=\"window.open('$sslfeaturelink','null','location=no,toolbar=no,menubar=no,scrollbars=yes,resizable=yes,addressbar=0,titlebar=no,directories=no,channelmode=no,status=no');\"> View Full Product Details</a>";
                        $packageArray['descriptions'] = [
                            ['lang' => 'en_us', 'text' => '', 'html' => $ProductDescription . $viewmorelink]
                        ];
                        $profitmargin = $vars['profit_margin'];


                        /* Create Option group for Additional SAN / Additional Server products */
                        $packageArray['option_groups'] = array();
                        if(($value->IsSanEnable == 'true' && $value->ProductCode != 'quicksslpremiummd') || ($value->isNoOfServerFree == false && $value->isCodeSigning == false && $value->isScanProduct == false)) {

                            /* Load the Loader to get Package Group Name for given ID */
                            Loader::loadModels($this, array("PackageGroups"));
                            $packageGroupArray = $this->PackageGroups->get($vars['product_group']);
                            $packageGroupName = $packageGroupArray->name;
                            $optionGroupValue = array('name' => $packageGroupName . '#' . $value->ProductName, 'description' => '', 'company_id' => Configure::get("Blesta.company_id"));
                            /* Load the Loader to add option group for respective products */
                            Loader::loadModels($this, array("PackageOptionGroups"));
                            $optionGroupId = $this->PackageOptionGroups->add($optionGroupValue);


                            /* Create Configuration options of Additional SAN */
                            if ($value->IsSanEnable == 'true' && $value->ProductCode != 'quicksslpremiummd') {
                                $TotalMaxSan = $value->MaxSan - $value->MinSan;
                                $PackageOptions['label'] = 'Additional SAN (' . $value->ProductName . ')';
                                $PackageOptions['name'] = 'additional_san';
                                $PackageOptions['type'] = 'quantity';
                                $PackageOptions['addable'] = 1;
                                /* Call setupPrice function to calculate the price based on the desired profit margin */
                                $sanPricingArray = array();

                                //setup SAN price
                                foreach($value->PricingInfo as $price_info){
                                    $san_price = $this->setupPrice($price_info->PricePerAdditionalSAN, $profitmargin);
                                    $sanPricingArray[] = array('term' => $price_info->NumberOfMonths, 'period' => 'month', 'currency' => 'USD', 'price' => $san_price, 'setup_fee' => '', 'cancel_fee' => '');
                                }


                                if ($value->MinSan != 0) {
                                    $PackageOptions['values'][0] = array('name' => 'Additional SAN (' . $value->MinSan . ' domains are included by default)', 'value' => '', 'min' => '0', 'max' => $TotalMaxSan, 'step' => 1, 'pricing' => $sanPricingArray);
                                } else {
                                    $PackageOptions['values'][0] = array('name' => 'Additional SAN', 'value' => '', 'min' => '0', 'max' => $TotalMaxSan, 'step' => 1, 'pricing' => $sanPricingArray);
                                }
                                $PackageOptions['groups'][0] = $optionGroupId;
                                $PackageOptions['company_id'] = Configure::get("Blesta.company_id");
                                $PackageOptions['editable'] = 0;
                                /* Load the Loader to add options for respective option group */
                                Loader::loadModels($this, array("PackageOptions"));
                                $additionalSanOptionId = $this->PackageOptions->add($PackageOptions);
                            }


                            /* Create Configuration options of Additional SERVER */
                            if ( $value->isNoOfServerFree == false && $value->isCodeSigning == false && $value->isScanProduct == false) {
                                $TotalMaxServer = '9';
                                $PackageOptions['label'] = 'Additional SERVER (' . $value->ProductName . ')';
                                $PackageOptions['name'] = 'additional_server';
                                $PackageOptions['type'] = 'quantity';
                                $PackageOptions['addable'] = 1;
                                /* Call setupPrice function to calculate the price based on the desired profit margin */
                                $serverPricingArray = array();
                                //Setup Server Price
                                foreach($value->PricingInfo as $price_info){
                                    $server_price = $this->setupPrice($price_info->PricePerAdditionalServer, $profitmargin);
                                    $serverPricingArray[] = array('term' => $price_info->NumberOfMonths, 'period' => 'month', 'currency' => 'USD', 'price' => $server_price, 'setup_fee' => '', 'cancel_fee' => '');
                                }
                                $PackageOptions['values'][0] = array('name' => 'Additional SERVER', 'value' => '', 'min' => '0', 'max' => $TotalMaxServer, 'step' => 1, 'pricing' => $serverPricingArray);
                                $PackageOptions['groups'][0] = $optionGroupId;
                                $PackageOptions['company_id'] = Configure::get("Blesta.company_id");
                                $PackageOptions['editable'] = 0;
                                /* Load the Loader to add options for respective option group */
                                Loader::loadModels($this, array("PackageOptions"));
                                $additionalServerOptionId = $this->PackageOptions->add($PackageOptions);
                            }
                            $packageArray['option_groups'][0] = $optionGroupId;
                        }
                        $packageArray['module_id'] = $moduleID;
                        $packageArray['module_row'] = $moduleRowID;
                        /* Set the product type */
                        $productValidationType = 'N/A';
                        if($value->isDVProduct == 'true'){
                            $productValidationType='DV';
                        }
                        elseif($value->isOVProduct == 'true')
                        {
                            $productValidationType='OV';
                        }
                        elseif($value->isEVProduct == 'true')
                        {
                            $productValidationType='EV';
                        }
                        $isScanProduct='n';
                        if($value->isScanProduct == 'true')
                        {
                            $isScanProduct='y';
                        }
                        $isCodeSigning='n';
                        if($value->isCodeSigning == 'true')
                        {
                            $isCodeSigning='y';
                        }
                        $packageArray['meta'] = array(
                            'thesslstore_product_code' => $value->ProductCode,
                            'thesslstore_min_san' => $value->MinSan,
                            'thesslstore_vendor_name' => $value->VendorName,
                            'thesslstore_validation_type' => $productValidationType,
                            'thesslstore_is_scan_product' => $isScanProduct,
                            'thesslstore_is_code_signing' => $isCodeSigning
                        );
                        /* Get the profit margin % from the vars */
                        $packageArray['pricing'] = array();

                        //Setup Price
                        foreach($value->PricingInfo as $price_info){
                            $final_price = $this->setupPrice($price_info->Price, $profitmargin);
                            $packageArray['pricing'][] = array('term' => $price_info->NumberOfMonths, 'period' => 'month', 'currency' => 'USD', 'price' => $final_price, 'setup_fee' => '', 'cancel_fee' => '');
                        }

                        $packageArray['email_content'][0] = array('lang' => 'en_us','html' => $email_content);
                        $packageArray['select_group_type'] = 'existing';
                        $packageArray['groups'][0] = $vars['product_group'];
                        $packageArray['group_name'] = '';
                        $packageArray['company_id'] = Configure::get("Blesta.company_id");
                        $packageArray['taxable'] = 0;
                        $packageArray['single_term'] = 0;
                        /* Load the Loader to add options for respective option group */
                        Loader::loadModels($this, array("Packages"));
                        $packageId = $this->Packages->add($packageArray);
                        $countOfImportPackages++;
                    }
                }
                $urlRedirect = explode("&", $_SERVER['REQUEST_URI']);
                if ($countOfImportPackages == 0) {
                    header('Location:' . $urlRedirect[0] . '&error=true'); /* Redirect browser */
                } else {
                    header('Location:' . $urlRedirect[0] . '&error=false&count=' . $countOfImportPackages); /* Redirect browser */
                }
                exit();
            }
        }
        elseif($scr == 'setupprice'){

            //Load Pricing Model
            Loader::loadModels($this, array("Packages","PackageOptions","Pricings"));

            if(isset($vars['thesslstore_apply_margin']) && $vars['thesslstore_apply_margin'] == 'yes'){
                $rules = array(
                    'thesslstore_margin_percentage' => array(
                        'empty' => array(
                            'rule' => "isEmpty",
                            'negate' => true,
                            'message' => Language::_("ThesslstoreModule.!error.profit_margin.empty", true)
                            ),
                        'valid' => array(
                            'rule' => array("isPassword", 1, "num"),
                            'message' => Language::_("ThesslstoreModule.!error.profit_margin.valid", true)
                            )
                        )
                    );
                //Set rules to validate fields
                $this->Input->setRules($rules);
                if ($this->Input->validates($vars)) {
                    $margin_percentage = $vars['thesslstore_margin_percentage'];
                    //Get product pricing from API
                    $api_products = $this->getProducts();
                    $products = array();
                    foreach($api_products as $product){
                        $products[$product->ProductCode] = $product->PricingInfo;
                    }

                    $packages_id = isset($vars['packages_id']) ? $vars['packages_id']: array();
                    $packages_pricing = array();

                    //Get Package data
                    foreach($packages_id as $package_id){
                        //Update package Price
                        $package = $this->Packages->get($package_id);
                        $packages_pricing[$package_id]['code'] = $package->meta->thesslstore_product_code;
                        foreach($package->pricing as $pricing){
                            $packages_pricing[$package_id][$pricing->term]['price_id'] = $pricing->pricing_id;

                            if(($pricing->term == 12 && $pricing->period == 'month') || ($pricing->term == 1 && $pricing->period == 'year') && $pricing->currency == 'USD'){
                                $packages_pricing[$package_id][12]['price_id'] = $pricing->pricing_id;
                            }
                            elseif(($pricing->term == 24 && $pricing->period == 'month') || ($pricing->term == 2 && $pricing->period == 'year' ) && $pricing->currency == 'USD'){
                                $packages_pricing[$package_id][24]['price_id'] = $pricing->pricing_id;
                            }
                            elseif(($pricing->term == 36 && $pricing->period == 'month') || ($pricing->term == 3 && $pricing->period == 'year') && $pricing->currency == 'USD'){
                                $packages_pricing[$package_id][36]['price_id'] = $pricing->pricing_id;
                            }
                            else{
                                $packages_pricing[$package_id][$pricing->term]['price_id'] = $pricing->pricing_id;
                            }
                        }

                        //Get options price
                        $options = $this->PackageOptions->getByPackageId($package_id);

                        foreach($options as $option){
                            $key = '';
                            if($option->name == 'additional_san'){
                                $key = 'san_price_id';
                            }
                            elseif($option->name == 'additional_server'){
                                $key = 'server_price_id';
                            }
                            if(isset($option->values[0]->pricing)){
                                foreach($option->values[0]->pricing as $pricing){
                                    if(($pricing->term == 12 && $pricing->period == 'month') || ($pricing->term == 1 && $pricing->period == 'year') && $pricing->currency == 'USD'){
                                        $packages_pricing[$package_id][12][$key] = $pricing->pricing_id;
                                    }
                                    elseif(($pricing->term == 24 && $pricing->period == 'month') || ($pricing->term == 2 && $pricing->period == 'year' ) && $pricing->currency == 'USD'){
                                        $packages_pricing[$package_id][24][$key] = $pricing->pricing_id;
                                    }
                                    elseif(($pricing->term == 36 && $pricing->period == 'month') || ($pricing->term == 3 && $pricing->period == 'year') && $pricing->currency == 'USD'){
                                        $packages_pricing[$package_id][36][$key] = $pricing->pricing_id;
                                    }
                                    else{
                                        $packages_pricing[$package_id][$pricing->term][$key] = $pricing->pricing_id;
                                    }

                                }
                            }
                        }
                    }

                    //Update Price in Pricing table
                    foreach($packages_pricing as $package_pricing) {
                        if (isset($products[$package_pricing['code']])) {
                            foreach($products[$package_pricing['code']] as $pricing_info){
                                //update package price
                                if(isset($package_pricing[$pricing_info->NumberOfMonths]['price_id'])){
                                    //get pricing info by id
                                    $pricing_id = $package_pricing[$pricing_info->NumberOfMonths]['price_id'];
                                    $info = $this->Pricings->get($pricing_id);

                                    //Update with new price
                                    $data['term'] = $info->term;
                                    $data['period'] = $info->period;
                                    $data['price'] = $this->setupPrice($pricing_info->Price,$margin_percentage);
                                    $data['setup_fee'] = $info->setup_fee;
                                    $data['cancel_fee'] = $info->cancel_fee;
                                    $data['currency'] = $info->currency;
                                    $this->Pricings->edit($pricing_id, $data);
                                }
                                //update SAN price
                                if(isset($package_pricing[$pricing_info->NumberOfMonths]['san_price_id'])){
                                    //get pricing info by id
                                    $pricing_id = $package_pricing[$pricing_info->NumberOfMonths]['san_price_id'];
                                    $info = $this->Pricings->get($pricing_id);

                                    //Update with new price
                                    $data['term'] = $info->term;
                                    $data['period'] = $info->period;
                                    $data['price'] = $this->setupPrice($pricing_info->PricePerAdditionalSAN, $margin_percentage);
                                    $data['setup_fee'] = $info->setup_fee;
                                    $data['cancel_fee'] = $info->cancel_fee;
                                    $data['currency'] = $info->currency;
                                    $this->Pricings->edit($pricing_id, $data);
                                }

                                //update Server price
                                if(isset($package_pricing[$pricing_info->NumberOfMonths]['server_price_id'])){
                                    //get pricing info by id
                                    $pricing_id = $package_pricing[$pricing_info->NumberOfMonths]['server_price_id'];
                                    $info = $this->Pricings->get($pricing_id);

                                    //Update with new price
                                    $data['term'] = $info->term;
                                    $data['period'] = $info->period;
                                    $data['price'] = $this->setupPrice($pricing_info->PricePerAdditionalServer, $margin_percentage);
                                    $data['setup_fee'] = $info->setup_fee;
                                    $data['cancel_fee'] = $info->cancel_fee;
                                    $data['currency'] = $info->currency;
                                    $this->Pricings->edit($pricing_id, $data);
                                }
                            }
                        }
                    }

                    //Redirect with success message
                    $url = explode("?",$_SERVER['REQUEST_URI']);
                    header('Location:' . $url[0].'?scr=setupprice&msg=success');
                    exit();
                }


            }
            else{
                //Update value based on textboxes

                $company_id = Configure::get("Blesta.company_id");
                $pricings = $this->Pricings->getAll($company_id);
                $new_price = $vars['price'];
                foreach($pricings as $pricing){
                    $pricing_id = $pricing->id;
                    if(isset($new_price[$pricing_id])){
                        if($new_price[$pricing_id] != $pricing->price){
                            //Update with new price
                            $data['term'] = $pricing->term;
                            $data['period'] = $pricing->period;
                            $data['price'] = $new_price[$pricing_id];
                            $data['setup_fee'] = $pricing->setup_fee;
                            $data['cancel_fee'] = $pricing->cancel_fee;
                            $data['currency'] = $pricing->currency;

                            $this->Pricings->edit($pricing_id, $data);
                        }
                    }
                }

                $url = explode("?",$_SERVER['REQUEST_URI']);
                header('Location:' . $url[0].'?scr=setupprice&msg=success');
                exit();

            }

        }elseif($scr == 'replacementorder'){
            $replace_by_date = $vars['replace_by_date'];
            $url = explode("?",$_SERVER['REQUEST_URI']);
            header('Location:' . $url[0].'?scr=replacementorder&date='.$replace_by_date);
            exit();
        }

    }

    /**
     * Use this function to set up product pricing with the desired margin
     */
    private function setupPrice($price,$margin){
        $givenPrice = ($price+$price*$margin/100);
        $finalPrice = number_format($givenPrice,2, '.', '');

        return $finalPrice;
    }

    /**
     * Edits the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being updated.
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of module info to update
     * @return array A numerically indexed array of meta fields for the module row containing:
     * 	- key The key for this meta field
     * 	- value The value for this key
     * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function editModuleRow($module_row, array &$vars) {
        $scr = isset($_GET['scr']) ? $_GET['scr'] : '';
        if($scr == 'editcredential') {
            $meta_fields = array("thesslstore_reseller_name","api_partner_code_live", "api_auth_token_live", "api_partner_code_test",
                "api_auth_token_test", "api_mode", "hide_changeapprover_option");
            $encrypted_fields = array("api_partner_code_live", "api_auth_token_live", "api_partner_code_test", "api_auth_token_test");

            // Validate module row
            if ($this->Input->validates($vars)) {
                // Build the meta data for this row
                $meta = array();
                foreach ($vars as $key => $value) {
                    if (in_array($key, $meta_fields)) {
                        $meta[] = array(
                            'key' => $key,
                            'value' => $value,
                            'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                        );
                    }
                }

                return $meta;
            }
        }
    }
    /**
     * Returns all fields used when adding/editing a package, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional HTML markup to include
     */
    public function getPackageFields($vars=null) {

        Loader::loadHelpers($this, array("Form", "Html"));

        $fields = new ModuleFields();

        $product_data = $this->getProducts();

        $product_codes[''] = Language::_("ThesslstoreModule.please_select", true);
        $products = array();
            foreach($product_data as $product){
                $product_codes[$product->ProductCode] = $product->ProductName;

                $data = array();
                $data['thesslstore_product_code'] = $product->ProductCode;
                $data['thesslstore_vendor_name'] = $product->VendorName;
                $data['thesslstore_is_code_signing'] = ($product->isCodeSigning == true) ? 'y' : 'n';
                $data['thesslstore_min_san'] = $product->MinSan;
                $data['thesslstore_is_scan_product'] = ($product->isScanProduct == true) ? 'y' : 'n';

                $validation_type = "N/A";
                if($product->isDVProduct == true)
                    $validation_type = 'DV';
                elseif($product->isOVProduct == true)
                    $validation_type = 'OV';
                elseif($product->isEVProduct == true)
                    $validation_type = 'EV';

                $data['thesslstore_validation_type'] = $validation_type;
                $products[] = $data;
            }

        $products = json_encode($products);

        // Show nodes, and set javascript field toggles
        $this->Form->setOutput(true);

        // Set the product as a selectable option
        $thesslstore_product_code = $fields->label(Language::_("ThesslstoreModule.package_fields.product_code", true), "thesslstore_product_code");
        $thesslstore_product_code->attach($fields->fieldSelect("meta[thesslstore_product_code]", $product_codes,
            (isset($vars->meta['thesslstore_product_code']) ? $vars->meta['thesslstore_product_code'] : null), array('id' => "thesslstore_product_code","onchange" => "javascript:get_ssl_meta(this.value)")));
        $fields->setField($thesslstore_product_code);
        unset($thesslstore_product_code);

        $field_thesslstore_vendor_name = $fields->fieldHidden( "meta[thesslstore_vendor_name]",(isset($vars->meta['thesslstore_vendor_name']) ? $vars->meta['thesslstore_vendor_name'] : null),array('id' => "thesslstore_vendor_name") );
        $fields->setField($field_thesslstore_vendor_name);
        unset($field_thesslstore_vendor_name);

        $field_thesslstore_is_code_signing = $fields->fieldHidden( "meta[thesslstore_is_code_signing]",(isset($vars->meta['thesslstore_is_code_signing']) ? $vars->meta['thesslstore_is_code_signing'] : null),array('id' => "thesslstore_is_code_signing") );
        $fields->setField($field_thesslstore_is_code_signing);
        unset($field_thesslstore_is_code_signing);

        $field_thesslstore_min_san = $fields->fieldHidden( "meta[thesslstore_min_san]",(isset($vars->meta['thesslstore_min_san']) ? $vars->meta['thesslstore_min_san'] : null),array('id' => "thesslstore_min_san") );
        $fields->setField($field_thesslstore_min_san);
        unset($field_thesslstore_min_san);

        $field_thesslstore_is_scan_product = $fields->fieldHidden( "meta[thesslstore_is_scan_product]",(isset($vars->meta['thesslstore_is_scan_product']) ? $vars->meta['thesslstore_is_scan_product'] : null),array('id' => "thesslstore_is_scan_product") );
        $fields->setField($field_thesslstore_is_scan_product);
        unset($field_thesslstore_is_scan_product);

        $field_thesslstore_validation_type = $fields->fieldHidden( "meta[thesslstore_validation_type]",(isset($vars->meta['thesslstore_validation_type']) ? $vars->meta['thesslstore_validation_type'] : null),array('id' => "thesslstore_validation_type") );
        $fields->setField($field_thesslstore_validation_type);
        unset($field_thesslstore_validation_type);

        $fields->setHtml("
            <script type=\"text/javascript\">
                function get_ssl_meta(code){
                   var ssl_product = {$products};
                   for(var i=0;i<ssl_product.length;i++){
                        if(ssl_product[i].thesslstore_product_code == code){
                            $('input#thesslstore_vendor_name').val(ssl_product[i].thesslstore_vendor_name);
                            $('input#thesslstore_is_code_signing').val(ssl_product[i].thesslstore_is_code_signing);
                            $('input#thesslstore_min_san').val(ssl_product[i].thesslstore_min_san);
                            $('input#thesslstore_is_scan_product').val(ssl_product[i].thesslstore_is_scan_product);
                            $('input#thesslstore_validation_type').val(ssl_product[i].thesslstore_validation_type);
                            break;
                        }
                    }
                }
            </script>
        ");

        return $fields;
    }

    /**
     * Adds the service to the remote server. Sets Input errors on failure,
     * preventing the service from being added.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service of the service being added (if the current service is an addon service and parent service has already been provisioned)
     * @param string $status The status of the service being added. These include:
     * 	- active
     * 	- canceled
     * 	- pending
     * 	- suspended
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     * 	- key The key for this meta field
     * 	- value The value for this key
     * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function addService($package, array $vars=null, $parent_package=null, $parent_service=null, $status="pending") {
        $thesslstore_order_id = '';
        $token = '';


        if($vars["use_module"] == "true") {

            $api = $this->getApi();

            $invite_order_req = new order_inviteorder_request();
            $invite_order_req->AddInstallationSupport = false;
            $invite_order_req->CustomOrderID = uniqid('TinyOrder-');
            $invite_order_req->EmailLanguageCode = 'EN';
            $invite_order_req->PreferVendorLink = false;
            $invite_order_req->ProductCode = $package->meta->thesslstore_product_code;

            $additional_server = (isset($vars['configoptions']['additional_server']) ? $vars['configoptions']['additional_server'] : 0);

            $invite_order_req->ServerCount = 1 + $additional_server;
            $invite_order_req->ValidityPeriod = 12; //Months

            foreach($package->pricing as $pricing) {
                if ($pricing->id == $vars['pricing_id']) {
                    if($pricing->period == 'month')
                        $invite_order_req->ValidityPeriod = $pricing->term;
                    elseif($pricing->period == 'year')
                        $invite_order_req->ValidityPeriod = $pricing->term * 12;
                    break;
                }
            }
            $additional_san = (isset($vars['configoptions']['additional_san']) ? $vars['configoptions']['additional_san'] : 0);
            $invite_order_req->ExtraSAN = $package->meta->thesslstore_min_san + $additional_san;


            $this->log($this->api_partner_code . "|ssl-invite-order", serialize($invite_order_req), "input", true);
            $result = $this->parseResponse($api->order_inviteorder($invite_order_req));

            if(empty($result)) {
                return;
            }

            if(!empty($result->TheSSLStoreOrderID)) {
                $thesslstore_order_id = $result->TheSSLStoreOrderID;
                $token = $result->Token;

            }
            else {
                $this->Input->setErrors(array('api' => array('internal' => 'No OrderID')));
                return;
            }
        }

        // Return service fields
        return array(
            array(
                'key' => "thesslstore_order_id",
                'value' => $thesslstore_order_id,
                'encrypted' => 0
            ),
            array(
                'key' => "thesslstore_token",
                'value' => $token,
                'encrypted' => 0
            )
        );
    }

    /**
     * Allows the module to perform an action when the service is ready to renew.
     * Sets Input errors on failure, preventing the service from renewing.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service of the service being renewed (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically indexed array of meta fields to be stored for this service containing:
     * 	- key The key for this meta field
     * 	- value The value for this key
     * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function renewService($package, $service, $parent_package=null, $parent_service=null) {

        $thesslstore_order_id = '';
        $thesslstore_token = '';

        $service_fields = $this->serviceFieldsToObject($service->fields);

        $service_meta = array();

        $old_thesslstore_order_id = $service_fields->thesslstore_order_id;

        $order_status_response = $this->getSSLOrderStatus($old_thesslstore_order_id);

        //Placed Invite order first
        $api = $this->getApi();


        $invite_order_req = new order_inviteorder_request();
        $invite_order_req->AddInstallationSupport = false;
        $invite_order_req->CustomOrderID = uniqid('TinyOrder-');
        $invite_order_req->EmailLanguageCode = 'EN';
        $invite_order_req->PreferVendorLink = false;
        $invite_order_req->ProductCode = $package->meta->thesslstore_product_code;


        //get additional server and san value
        $additional_san = 0;
        $additional_server = 0;
        foreach($service->options as $option){
            if($option->option_name == 'additional_san'){
                $additonal_san = $option->qty;
            }
            if($option->option_name == 'additional_server'){
                $additonal_server = $option->qty;
            }
        }

        $server_count = 1 + $additional_server;
        $invite_order_req->ServerCount = $server_count;
        $validity_period = 12; //Months

        foreach($package->pricing as $pricing) {
            if ($pricing->id == $service->pricing_id) {
                if($pricing->period == 'month')
                    $validity_period = $pricing->term;
                elseif($pricing->period == 'year')
                    $validity_period = $pricing->term * 12;
                break;
            }
        }

        $invite_order_req->ValidityPeriod = $validity_period;
        $invite_order_req->ExtraSAN = $package->meta->thesslstore_min_san + $additional_san;


        $this->log($this->api_partner_code . "|ssl-invite-renew-order", serialize($invite_order_req), "input", true);
        $result = $this->parseResponse($api->order_inviteorder($invite_order_req));

        if(empty($result)) {
            return null;
        }

        if(!empty($result->TheSSLStoreOrderID)) {
            $thesslstore_order_id = $result->TheSSLStoreOrderID;
            $thesslstore_token = $result->Token;

            $service_meta[] = array(
                'key' => "thesslstore_order_id",
                'value' => $thesslstore_order_id,
                'encrypted' => 0
            );
            $service_meta[] = array(
                'key' => "thesslstore_token",
                'value' => $thesslstore_token,
                'encrypted' => 0
            );
            $send_invite_order_email = true;

            //if CSR is found then place full order.
            if ($order_status_response
                && isset($service_fields->thesslstore_csr)
                && !empty($service_fields->thesslstore_csr)
            ) {

                $contact = new contact();
                $contact->AddressLine1 = $order_status_response->AdminContact->AddressLine1;
                $contact->AddressLine2 = $order_status_response->AdminContact->AddressLine2;
                $contact->City = $order_status_response->AdminContact->City;
                $contact->Region = $order_status_response->AdminContact->Region;
                $contact->Country = $order_status_response->AdminContact->Country;
                $contact->Email = $order_status_response->AdminContact->Email;
                $contact->Fax = $order_status_response->AdminContact->Fax;
                $contact->FirstName = $order_status_response->AdminContact->FirstName;
                $contact->LastName = $order_status_response->AdminContact->LastName;
                $contact->OrganizationName = $order_status_response->AdminContact->OrganizationName;
                $contact->Phone = $order_status_response->AdminContact->Phone;
                $contact->PostalCode = $order_status_response->AdminContact->PostalCode;
                $contact->Title = $order_status_response->AdminContact->Title;

                $tech_contact = new contact();
                $tech_contact->AddressLine1 = $order_status_response->TechnicalContact->AddressLine1;
                $tech_contact->AddressLine2 = $order_status_response->TechnicalContact->AddressLine2;
                $tech_contact->City = $order_status_response->TechnicalContact->City;
                $tech_contact->Region = $order_status_response->TechnicalContact->Region;
                $tech_contact->Country = $order_status_response->TechnicalContact->Country;
                $tech_contact->Email = $order_status_response->TechnicalContact->Email;
                $tech_contact->Fax = $order_status_response->TechnicalContact->Fax;
                $tech_contact->FirstName = $order_status_response->TechnicalContact->FirstName;
                $tech_contact->LastName = $order_status_response->TechnicalContact->LastName;
                $tech_contact->OrganizationName = $order_status_response->TechnicalContact->OrganizationName;
                $tech_contact->Phone = $order_status_response->TechnicalContact->Phone;
                $tech_contact->PostalCode = $order_status_response->TechnicalContact->PostalCode;
                $tech_contact->Title =$order_status_response->TechnicalContact->Title;

                $org_country = $order_status_response->Country;

                //get organisation country
                /*For the comodo product ,if user do not pass org country name in new order
                then API take country name from CSR which is always full name
                while in renewal it will take the org country name from order status that will give an error
                " ErrorCode:-9009|Message:Vendor returns error:the value of the 'apprepcountryname' argument is invalid!"
                */

                foreach($this->getCountryList() as $code => $name){
                    if($org_country == $code || $org_country == $name){
                        $org_country = $code;
                        break;
                    }
                }

                $new_order = new order_neworder_request();


                $new_order->AddInstallationSupport = false;
                $new_order->AdminContact = $contact;
                $new_order->CSR = $service_fields->thesslstore_csr;
                $new_order->DomainName = '';
                $new_order->CustomOrderID = uniqid('BlestaFullOrder-');
                $new_order->RelatedTheSSLStoreOrderID = '';
                $new_order->DNSNames = explode(',',$order_status_response->DNSNames);
                $new_order->EmailLanguageCode = 'EN';
                $new_order->ExtraProductCodes = '';
                $new_order->OrganizationInfo->DUNS = $order_status_response->DUNS;
                $new_order->OrganizationInfo->Division = $order_status_response->OrganizationalUnit;
                $new_order->OrganizationInfo->IncorporatingAgency = '';
                $new_order->OrganizationInfo->JurisdictionCity = $order_status_response->Locality;
                $new_order->OrganizationInfo->JurisdictionCountry = $org_country;
                $new_order->OrganizationInfo->JurisdictionRegion = $order_status_response->State;
                $new_order->OrganizationInfo->OrganizationName = $order_status_response->Organization;
                $new_order->OrganizationInfo->RegistrationNumber = '';
                $new_order->OrganizationInfo->OrganizationAddress->AddressLine1 = $order_status_response->OrganizationAddress;
                $new_order->OrganizationInfo->OrganizationAddress->AddressLine2 = '';
                $new_order->OrganizationInfo->OrganizationAddress->AddressLine3 = '';
                $new_order->OrganizationInfo->OrganizationAddress->City = $order_status_response->Locality;
                $new_order->OrganizationInfo->OrganizationAddress->Country = $org_country;
                $new_order->OrganizationInfo->OrganizationAddress->Fax = '';
                $new_order->OrganizationInfo->OrganizationAddress->LocalityName = '';
                $new_order->OrganizationInfo->OrganizationAddress->Phone = $order_status_response->OrganizationPhone;
                $new_order->OrganizationInfo->OrganizationAddress->PostalCode = $order_status_response->OrganizationPostalcode;
                $new_order->OrganizationInfo->OrganizationAddress->Region = $order_status_response->State;
                $new_order->ProductCode = $package->meta->thesslstore_product_code;
                $new_order->ReserveSANCount = $additional_san;
                $new_order->ServerCount = $server_count;
                $new_order->SpecialInstructions = '';
                $new_order->TechnicalContact = $tech_contact;
                $new_order->ValidityPeriod = $validity_period; //number of months
                $new_order->WebServerType = $order_status_response->WebServerType;
                $new_order->isCUOrder = false;
                $new_order->isRenewalOrder = true;
                $new_order->isTrialOrder = false;
                $new_order->SignatureHashAlgorithm = $order_status_response->SignatureHashAlgorithm;

                if($order_status_response->AuthFileName != ''){
                    $new_order->FileAuthDVIndicator = true;
                    $new_order->HTTPSFileAuthDVIndicator = false;
                    $new_order->ApproverEmail = $order_status_response->ApproverEmail;
                    if($order_status_response->VendorName == 'COMODO'){
                        $new_order->CSRUniqueValue = date('YmdHisa');
                    }

                }
                else{
                    $new_order->FileAuthDVIndicator = false;
                    $new_order->HTTPSFileAuthDVIndicator = false;
                    $new_order->ApproverEmail = $order_status_response->ApproverEmail;
                }

                //Place full order
                $api_with_token = $this->getApi(null,null,'',$IsUsedForTokenSystem = true, $thesslstore_token);
                $this->log($this->api_partner_code . "|ssl-full-renew-order", serialize($new_order), "input", true);
                $new_order_resp = $this->parseResponse($api_with_token->order_neworder($new_order),$ignore_error = true);
                if ($new_order_resp
                    && isset($new_order_resp->AuthResponse->isError)
                    && $new_order_resp->AuthResponse->isError == false
                ) {
                    $send_invite_order_email = false;
                }
            }

            //send email to customer when only invite order is placed.
            if($send_invite_order_email){

                //get client data to prefiiled data
                $this->sendInviteOrderEmail($service, $package, $service_meta);
            }

        }
        else {
            $this->Input->setErrors(array('api' => array('internal' => 'No OrderID')));
            return null;
        }

        // Return service fields
       if(!empty($service_meta)){

           //added old meta like csr and fqdn etc.
           foreach($service->fields as $field){
               $already_added = false;
               foreach($service_meta as $meta){
                   if($field->key == $meta['key']){
                       $already_added = true;
                       break;
                   }

               }
               if($already_added == false) {
                   $service_meta[] = array(
                       'key' => $field->key,
                       'value' => $field->value,
                       'encrypted' => $field->encrypted,
                       'serialized' => $field->serialized
                   );
               }
           }

           return $service_meta;
       }
       return null;
    }

    /**
     * Cancels the service on the remote server. Sets Input errors on failure,
     * preventing the service from being canceled.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service of the service being canceled (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically indexed array of meta fields to be stored for this service containing:
     * 	- key The key for this meta field
     * 	- value The value for this key
     * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function cancelService($package, $service, $parent_package=null, $parent_service=null) {
        if($service->status == 'active') {
            // Get the service fields
            $service_fields = $this->serviceFieldsToObject($service->fields);
            // Get the Order ID
            $orderID=$service_fields->thesslstore_order_id;
            //Raise Refund Request
            $api = $this->getApi();
            $refundReq = new order_refundrequest_request();
            $refundReq->RefundReason = 'Requested by User!';
            $refundReq->TheSSLStoreOrderID =$orderID;

            $this->log($this->api_partner_code . "|ssl-refund-request", serialize($refundReq), "input", true);
            $refundRes = $api->order_refundrequest($refundReq);
            if(!$refundRes->AuthResponse->Message[0])
            {
                $errorMessage=$refundRes->AuthResponse->Message;
            }
            else{
                $errorMessage=$refundRes->AuthResponse->Message[0];
            }
            if($refundRes != NULL && $refundRes->AuthResponse->isError == false){
                $this->log($this->api_partner_code."|ssl-refund-response", serialize($refundRes), "output", true);
                return null;
            }
            else {
                $this->log($this->api_partner_code."|ssl-refund-response", serialize($refundRes), "output", false);
                $this->Input->setErrors(array('invalid_action' => array('internal' => $errorMessage)));
                return;
            }
        }return null;
    }

    /**
     * Retrieves a list of products with all the information
     *
     * @param TheSSLStoreApi $api the API to use
     * @param stdClass $row A stdClass object representing a single reseller (optional, required when Module::getModuleRow() is unavailable)
     * @return array A list of products
     */
    private function getProducts() {

        $api = $this->getApi();

        $product_query_request = new product_query_request();
        $product_query_request->ProductType = 0;
        $product_query_request->NeedSortedList = true;

        $this->log($this->api_partner_code . "|ssl-products", serialize($product_query_request), "input", true);
        $productsArray = $this->parseResponse($api->product_query($product_query_request));

        return (!empty($productsArray) && is_array($productsArray)) ? $productsArray : [];
    }

    /**
     * Returns all tabs to display to a client when managing a service whose
     * package uses this module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title. Example: array('methodName' => "Title", 'methodName2' => "Title2")
     */
    public function getClientTabs($package) {

        return array(
            'tabClientCertDetails' => array('name' => Language::_("ThesslstoreModule.tab_CertDetails", true),'icon' => 'fas fa-bars'),
            'tabClientGenerateCert' => array('name' => Language::_("ThesslstoreModule.tab_GenerateCert", true),'icon' => 'fas fa-cogs'),
            'tabClientDownloadCertificate' =>array('name' => Language::_("ThesslstoreModule.tab_DownloadCertificate", true), 'icon' => 'fas fa-certificate'),
            'tabClientDownloadAuthFile' => array('name' => Language::_("ThesslstoreModule.tab_DownloadAuthFile", true),'icon' => 'fas fa-file'),
            'tabClientChangeApproverEmail' => array('name' => Language::_("ThesslstoreModule.tab_ChangeApproverEmail", true),'icon' => 'fas fa-exchange-alt'),
            'tabClientResendApproverEmail' => array('name' => Language::_("ThesslstoreModule.tab_ResendApproverEmail",true),'icon' => 'fas fa-sync-alt'),
            'tabClientReissueCert' => array('name' => Language::_("ThesslstoreModule.tab_ReissueCert",true),'icon' => 'fas fa-redo-alt')
        );
    }

    /**
     * Returns all tabs to display to an admin when managing a service whose
     * package uses this module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title. Example: array('methodName' => "Title", 'methodName2' => "Title2")
     */
    public function getAdminTabs($package) {
        return array(
            'tabAdminManagementAction' => Language::_("ThesslstoreModule.tab_AdminManagementAction",true),
        );
    }

    /**
     * Client Certificate Details tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientCertDetails($package, $service, array $get=null, array $post=null, array $files=null) {

        if($service->status == 'active') {
            $this->view = new View("tab_client_cert_details", "default");
            $this->view->base_uri = $this->base_uri;
            $this->view->setDefaultView("components" . DS . "modules" . DS . "thesslstore_module" . DS);

            // Load the helpers required for this view
            Loader::loadHelpers($this, array("Form", "Html", "Widget"));

            // Get the service fields
            $service_fields = $this->serviceFieldsToObject($service->fields);

            $order_resp = $this->getSSLOrderStatus($service_fields->thesslstore_order_id);

            if($order_resp) {
                $vendor_name = $package->meta->thesslstore_vendor_name;
                $is_code_signing = $package->meta->thesslstore_is_code_signing;
                $is_scan_product = $package->meta->thesslstore_is_scan_product;

                //Certificate Details
                $certificate['order_status'] = $order_resp->OrderStatus->MajorStatus;
                $certificate['store_order_id'] = $service_fields->thesslstore_order_id;
                $certificate['vendor_order_id'] = $order_resp->VendorOrderID;
                $certificate['vendor_status'] = $order_resp->OrderStatus->MinorStatus;
                $certificate['token'] = $order_resp->Token;
                $certificate['ssl_start_date'] = $this->getFormattedDate($order_resp->CertificateStartDateInUTC);
                $certificate['ssl_end_date'] = $this->getFormattedDate($order_resp->CertificateEndDateInUTC);
                $certificate['domains'] = (!empty($order_resp->CommonName) ? $order_resp->CommonName : '-' );
                $certificate['additional_domains'] = $order_resp->DNSNames;
                $certificate['siteseal_url'] = $order_resp->SiteSealurl;
                $certificate['verification_email'] = $order_resp->ApproverEmail;
                $certificate['verification_type'] = '';
                if($order_resp->AuthFileName != '' && $order_resp->AuthFileContent != ''){
                    $certificate['verification_type'] = 'file';
                }
                elseif($order_resp->ApproverEmail != ''){
                    $certificate['verification_type'] = 'email';
                }


                //Certificate Admin Details
                $certificate['admin_title'] = (!empty($order_resp->AdminContact->Title) ? $order_resp->AdminContact->Title : '-');
                $certificate['admin_first_name'] = $order_resp->AdminContact->FirstName;
                $certificate['admin_last_name'] = $order_resp->AdminContact->LastName;
                $certificate['admin_email'] = $order_resp->AdminContact->Email;
                $certificate['admin_phone'] = $order_resp->AdminContact->Phone;

                //Certificate Technical Details
                $certificate['tech_title'] = (!empty($order_resp->TechnicalContact->Title) ? $order_resp->TechnicalContact->Title : '-');
                $certificate['tech_first_name'] = $order_resp->TechnicalContact->FirstName;
                $certificate['tech_last_name'] = $order_resp->TechnicalContact->LastName;
                $certificate['tech_email'] = $order_resp->TechnicalContact->Email;
                $certificate['tech_phone'] = $order_resp->TechnicalContact->Phone;

                $certificate['generation_link'] = $this->base_uri . "services/manage/" . ($service->id) . "/tabClientGenerateCert/";
                /* Provide central API link for CERTUM products*/
                if ($vendor_name == 'CERTUM' || $is_scan_product == 'y' || $is_code_signing == 'y') {
                    $certificate['generation_link'] = $order_resp->TinyOrderLink;
                }
                //Display Replacement order alert message for symantec products
                $sslConfigCompleteDetails = '';
                if ($order_resp->VendorName == 'SYMANTEC' && $order_resp->OrderStatus->MajorStatus== 'Active') {
                    $api = $this->getApi();
                    $orderReplacementRequest = new order_replacement_request();
                    $orderReplacementRequest->TheSSLStoreOrderID = $order_resp->TheSSLStoreOrderID;
                    $orderReplacementResp=$api->order_replacement($orderReplacementRequest);
                    if ($orderReplacementResp->AuthResponse->isError == false && $orderReplacementResp->Orders != NULL) {
                        $sslConfigCompleteDetails = "Alert! Due to DigiCert’s acquisition of Symantec, you must ".$orderReplacementResp->Orders[0]->Action. " your certificate before ".$orderReplacementResp->Orders[0]->ReplaceByDate;
                    }
                }
                $this->view->set("service", $service);
                $this->view->set("certificate", (object)$certificate);
                $this->view->set("replacementdetails", $sslConfigCompleteDetails);
                return $this->view->fetch();
            }

        }
        else {
            $this->Input->setErrors(array('invalid_action' => array('internal' => Language::_("ThesslstoreModule.!error.invalid_service_status", true))));
            return;
        }
    }
    private function validateGenerateCertStep1($package,$vars,$san_count)
    {
        // Set rules
        $rules = array(
            'thesslstore_csr' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_csr.empty", true)
                ),
                'valid' => array(
                    'rule' => array(array($this, "validateCSR"), $package->meta->thesslstore_product_code, true),
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_csr.valid", true)
                )
            ),
            'thesslstore_webserver_type' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_webserver_type.empty", true)
                )
            ),
            'thesslstore_auth_method' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_auth_method.empty", true)
                )
            ),
            'thesslstore_signature_algorithm' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_signature_algorithm.empty", true)
                )
            ),
            'thesslstore_admin_first_name' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_admin_first_name.empty", true)
                )
            ),
            'thesslstore_admin_last_name' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_admin_last_name.empty", true)
                )
            ),
            'thesslstore_admin_email' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_admin_email.empty", true)
                )
            ),
            'thesslstore_admin_phone' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_admin_phone.empty", true)
                )
            ),
            'thesslstore_org_name' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_org_name.empty", true)
                )
            ),
            'thesslstore_org_division' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_org_division.empty", true)
                )
            ),
            'thesslstore_admin_address1' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_admin_address1.empty", true)
                )
            ),
            'thesslstore_admin_city' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_admin_city.empty", true)
                )
            ),
            'thesslstore_admin_state' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_admin_state.empty", true)
                )
            ),
            'thesslstore_admin_country' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_admin_country.empty", true)
                )
            ),
            'thesslstore_admin_zip' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_admin_zip.empty", true)
                )
            ),
            'thesslstore_tech_first_name' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_tech_first_name.empty", true)
                )
            ),
            'thesslstore_tech_last_name' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_tech_last_name.empty", true)
                )
            ),
            'thesslstore_tech_email' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_tech_email.empty", true)
                )
            ),
            'thesslstore_tech_phone' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_tech_phone.empty", true)
                )
            ),

        );
        if($san_count > 0){
            /*validate Additional SAN (Minimum 1 Additional SAN should be passed for SAN Enabled Product*/
            $rules['thesslstore_additional_san'] = array(
                'valid' => array(
                    'rule' => array(array($this, "validateAdditionalSAN")),
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_additional_san.empty", true)
                )
            );
        }

        $this->Input->setRules($rules);
        return $this->Input->validates($vars);
    }

    private function validateReissueCert($package,$vars,$san_count){
        // Set rules
        $rules = array(
            'thesslstore_csr' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_csr.empty", true)
                ),
                'valid' => array(
                    'rule' => array(array($this, "validateCSR"), $package->meta->thesslstore_product_code, true),
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_csr.valid", true)
                )
            ),
            'thesslstore_webserver_type' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_webserver_type.empty", true)
                )
            ),
            'thesslstore_signature_algorithm' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_signature_algorithm.empty", true)
                )
            ),
            'thesslstore_auth_method' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_auth_method.empty", true)
                )
            )
        );
        if($san_count > 0){
            /*validate Additional SAN (Minimum 1 Additional SAN should be passed for SAN Enabled Product*/
            $rules['thesslstore_additional_san'] = array(
                'valid' => array(
                    'rule' => array(array($this, "validateAdditionalSAN")),
                    'message' => Language::_("ThesslstoreModule.!error.thesslstore_additional_san.empty", true)
                )
            );
        }

        $this->Input->setRules($rules);
        return $this->Input->validates($vars);
    }

    /**
     * Client Generate Certificate tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */

    public function tabClientGenerateCert($package, $service, array $get=null, array $post=null, array $files=null){

        if($service->status == 'active') {


            $service_fields = $this->serviceFieldsToObject($service->fields);
            $thesslstore_order_id = $service_fields->thesslstore_order_id;
            $product_code = $package->meta->thesslstore_product_code;
            $vendor_name = $package->meta->thesslstore_vendor_name;
            $product_validation_type = $package->meta->thesslstore_validation_type;
            $is_code_signing = $package->meta->thesslstore_is_code_signing;
            $is_scan_product = $package->meta->thesslstore_is_scan_product;
            $order_resp = $this->getSSLOrderStatus($thesslstore_order_id);
            $use_central_api = false;
            $cert_generation_link = "";
            if ($vendor_name == 'CERTUM' || $is_code_signing == 'y' || $is_scan_product == 'y') {
                $cert_generation_link = $order_resp->TinyOrderLink;
                $use_central_api = true;
            }

            if ($order_resp && $order_resp->OrderStatus->MajorStatus == 'Initial') {

                $contact_number = '';
                //get client data to prefiiled data
                Loader::loadModels($this, array("Clients"));
                $client_data = $this->Clients->get($service->client_id, $get_settings = false);

                //get client contact no
                if ($client_data->contact_id && $client_data->contact_id > 0) {
                    Loader::loadModels($this, array("Contacts"));
                    $client_contact_data = $this->Contacts->getNumbers($client_data->contact_id);
                    if (!empty($client_contact_data)) {
                        $contact_number = $client_contact_data[0]->number;
                    }
                }

                //Pre-filled data
                $auth_method = isset($post['thesslstore_auth_method']) ? $post['thesslstore_auth_method'] : 'EMAIL';
                $admin_first_name = isset($post['thesslstore_admin_first_name']) ? $post['thesslstore_admin_first_name'] : $client_data->first_name;
                $admin_last_name = isset($post['thesslstore_admin_last_name']) ? $post['thesslstore_admin_last_name'] : $client_data->last_name;
                $admin_title = isset($post['thesslstore_admin_title']) ? $post['thesslstore_admin_title'] : '';
                $admin_email = isset($post['thesslstore_admin_email']) ? $post['thesslstore_admin_email'] : $client_data->email;
                $admin_phone = isset($post['thesslstore_admin_phone']) ? $post['thesslstore_admin_phone'] : $contact_number;

                $admin_org_name = isset($post['thesslstore_org_name']) ? $post['thesslstore_org_name'] : $client_data->company;
                $admin_org_division = isset($post['thesslstore_org_division']) ? $post['thesslstore_org_division'] : '';
                $admin_address1 = isset($post['thesslstore_admin_address1']) ? $post['thesslstore_admin_address1'] : $client_data->address1;
                $admin_address2 = isset($post['thesslstore_admin_address2']) ? $post['thesslstore_admin_address2'] : $client_data->address2;
                $admin_city = isset($post['thesslstore_admin_city']) ? $post['thesslstore_admin_city'] : $client_data->city;
                $admin_state = isset($post['thesslstore_admin_state']) ? $post['thesslstore_admin_state'] : $client_data->state;
                $admin_country = isset($post['thesslstore_admin_country']) ? $post['thesslstore_admin_country'] : $client_data->country;
                $admin_zip_code = isset($post['thesslstore_admin_zip']) ? $post['thesslstore_admin_zip'] : $client_data->zip;

                $tech_first_name = isset($post['thesslstore_tech_first_name']) ? $post['thesslstore_tech_first_name'] : $client_data->first_name;
                $tech_last_name = isset($post['thesslstore_tech_last_name']) ? $post['thesslstore_tech_last_name'] : $client_data->last_name;
                $tech_title = isset($post['thesslstore_tech_title']) ? $post['thesslstore_tech_title'] : '';
                $tech_email = isset($post['thesslstore_tech_email']) ? $post['thesslstore_tech_email'] : $client_data->email;
                $tech_phone = isset($post['thesslstore_tech_phone']) ? $post['thesslstore_tech_phone'] : $contact_number;


                $step = 1;
                $posted_from_step = 0;
                $posted = false;
                if (isset($post['thesslstore_gen_step'])) {
                    $step = $post['thesslstore_gen_step'];
                    $posted = true;
                    $posted_from_step = $post['thesslstore_gen_step'];
                }

                // Get the service fields
                $san_count = $order_resp->SANCount;
                $approver_email_list = array();

                //Authentication Method
                $auth_methods = array();
                if ($vendor_name == 'COMODO' || $product_validation_type == 'DV') {
                    $auth_methods['EMAIL'] = 'E-Mail';
                    $auth_methods['HTTP'] = 'HTTP File Based';

                    if ($vendor_name == 'COMODO')
                        $auth_methods['HTTPS'] = 'HTTPS File Based';
                }

                if ($step == 1) {
                    $this->view = new View("tab_client_generate_cert_step1", "default");
                    $this->view->base_uri = $this->base_uri;
                    $this->view->setDefaultView("components" . DS . "modules" . DS . "thesslstore_module" . DS);

                    // Load the helpers required for this view
                    Loader::loadHelpers($this, array("Form", "Html"));

                    // Validate the service-specific fields
                    if ($posted) {
                        $this->validateGenerateCertStep1($package, $post, $san_count);
                        if (!$this->Input->errors()) {
                            $step = 2;
                            if ($post['thesslstore_auth_method'] == 'EMAIL') {
                                //get main domain name from the CSR
                                $validate_csr_resp = $this->validateCSR($post['thesslstore_csr'], $product_code);
                                $common_name = $validate_csr_resp ? $validate_csr_resp->DomainName : '';

                                //get Approver Email list
                                $approver_email_list_resp = $this->getApproverEmailsList($product_code, $common_name);
                                $approver_email_list[$common_name] = $approver_email_list_resp;
                                /*
                                 * quicksslpremiummd has additional san but it allows only subdomain of main domain
                                 * as additional san and allows only main domain's approval email.
                                 */
                                if (isset($post['thesslstore_additional_san']) && $vendor_name == 'COMODO' ) {
                                    foreach ($post['thesslstore_additional_san'] as $san) {
                                        if (trim($san) != '') {
                                            // $domains[] = $san;

                                            //get Approver Email list
                                            $approver_email_list_resp = $this->getApproverEmailsList($product_code, $san);
                                            $approver_email_list[$san] = $approver_email_list_resp;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($step == 1) {


                        $this->view->set("thesslstore_webserver_types", $this->getWebserverTypes());
                        $this->view->set("thesslstore_countries", $this->getCountryList());
                        $this->view->set("thesslstore_signature_algorithms", array('SHA2-256' => 'SHA-2', 'SHA1' => 'SHA-1'));
                        $this->view->set("thesslstore_auth_methods", $auth_methods);

                        $this->view->set("vars", (object)$post);
                        $this->view->set("auth_method", $auth_method);

                        //admin contact
                        $this->view->set("thesslstore_admin_first_name", $admin_first_name);
                        $this->view->set("thesslstore_admin_last_name", $admin_last_name);
                        $this->view->set("thesslstore_admin_title", $admin_title);
                        $this->view->set("thesslstore_admin_email", $admin_email);
                        $this->view->set("thesslstore_admin_phone", $admin_phone);
                        $this->view->set("thesslstore_org_name", $admin_org_name);
                        $this->view->set("thesslstore_org_division", $admin_org_division);
                        $this->view->set("thesslstore_admin_address1", $admin_address1);
                        $this->view->set("thesslstore_admin_address2", $admin_address2);
                        $this->view->set("thesslstore_admin_city", $admin_city);
                        $this->view->set("thesslstore_admin_state", $admin_state);
                        $this->view->set("thesslstore_admin_country", $admin_country);
                        $this->view->set("thesslstore_admin_zip", $admin_zip_code);

                        //Technical Contact
                        $this->view->set("thesslstore_tech_first_name", $tech_first_name);
                        $this->view->set("thesslstore_tech_last_name", $tech_last_name);
                        $this->view->set("thesslstore_tech_title", $tech_title);
                        $this->view->set("thesslstore_tech_email", $tech_email);
                        $this->view->set("thesslstore_tech_phone", $tech_phone);

                        $this->view->set("service", $service);
                        $this->view->set("san_count", $san_count);
                        $this->view->set("service_id", $service->id);
                        $this->view->set("use_central_api", $use_central_api);
                        $this->view->set("cert_generation_link", $cert_generation_link);
                        $this->view->set("step", 1);

                        return $this->view->fetch();
                    }

                }
                //Successfully passed step 1
                if ($step == 2) {
                    if ($auth_method == 'EMAIL') {
                        if ($posted_from_step == 1) {

                            $this->view = new View("tab_client_generate_cert_step2", "default");
                            $this->view->base_uri = $this->base_uri;
                            $this->view->setDefaultView("components" . DS . "modules" . DS . "thesslstore_module" . DS);

                            Loader::loadHelpers($this, array("Form", "Html"));

                            $this->view->set("vars", (object)$post);
                            $this->view->set("step", 2);
                            $this->view->set("service_id", $service->id);
                            $this->view->set("approver_email_list", $approver_email_list);

                            return $this->view->fetch();
                        } elseif ($posted_from_step == 2) {
                            $success = $this->placeFullOrder($package, $service, $post, $order_resp);
                            if ($success) {
                                $step = 3;
                            } else {
                                $step = 1;
                            }
                        }


                    } elseif ($auth_method != 'EMAIL') {
                        $success = $this->placeFullOrder($package, $service, $post, $order_resp);
                        if ($success) {
                            $step = 3;
                        } else {
                            $step = 1;
                        }
                    }

                    if ($step == 1) {
                        //Display error with step 1
                        $this->view = new View("tab_client_generate_cert_step1", "default");
                        $this->view->base_uri = $this->base_uri;
                        $this->view->setDefaultView("components" . DS . "modules" . DS . "thesslstore_module" . DS);

                        Loader::loadHelpers($this, array("Form", "Html"));

                        $this->view->set("thesslstore_webserver_types", $this->getWebserverTypes());
                        $this->view->set("thesslstore_countries", $this->getCountryList());
                        $this->view->set("thesslstore_signature_algorithms", array('SHA2-256' => 'SHA-2', 'SHA1' => 'SHA-1'));
                        $this->view->set("thesslstore_auth_methods", $auth_methods);
                        $this->view->set("vars", (object)$post);
                        $this->view->set("auth_method", $auth_method);

                        //admin contact
                        $this->view->set("thesslstore_admin_first_name", $admin_first_name);
                        $this->view->set("thesslstore_admin_last_name", $admin_last_name);
                        $this->view->set("thesslstore_admin_title", $admin_title);
                        $this->view->set("thesslstore_admin_email", $admin_email);
                        $this->view->set("thesslstore_admin_phone", $admin_phone);
                        $this->view->set("thesslstore_org_name", $admin_org_name);
                        $this->view->set("thesslstore_org_division", $admin_org_division);
                        $this->view->set("thesslstore_admin_address1", $admin_address1);
                        $this->view->set("thesslstore_admin_address2", $admin_address2);
                        $this->view->set("thesslstore_admin_city", $admin_city);
                        $this->view->set("thesslstore_admin_state", $admin_state);
                        $this->view->set("thesslstore_admin_country", $admin_country);
                        $this->view->set("thesslstore_admin_zip", $admin_zip_code);

                        //Technical Contact
                        $this->view->set("thesslstore_tech_first_name", $tech_first_name);
                        $this->view->set("thesslstore_tech_last_name", $tech_last_name);
                        $this->view->set("thesslstore_tech_title", $tech_title);
                        $this->view->set("thesslstore_tech_email", $tech_email);
                        $this->view->set("thesslstore_tech_phone", $tech_phone);


                        $this->view->set("service", $service);
                        $this->view->set("san_count", $san_count);
                        $this->view->set("service_id", $service->id);
                        $this->view->set("step", 1);
                        $this->view->set("use_central_api", $use_central_api);
                        $this->view->set("cert_generation_link", $cert_generation_link);

                        return $this->view->fetch();
                    }
                }

                if ($step == 3) {
                    $this->view = new View("tab_client_generate_cert_step3", "default");
                    $this->view->base_uri = $this->base_uri;
                    $this->view->setDefaultView("components" . DS . "modules" . DS . "thesslstore_module" . DS);

                    Loader::loadHelpers($this, array("Html"));

                    $this->view->set("vars", (object)$post);
                    $download_auth_file_link = $this->base_uri . "services/manage/" . ($service->id) . "/tabClientDownloadAuthFile/";
                    $auth_file_name = '';
                    $auth_file_content = '';
                    if ($auth_method != 'EMAIL') {
                        $order_status = $this->getSSLOrderStatus($thesslstore_order_id);
                        $auth_file_name = $order_status ? $order_status->AuthFileName : '';
                        $auth_file_content = $order_status ? $order_status->AuthFileContent : '';
                    }
                    $this->view->set("auth_file_name", $auth_file_name);
                    $this->view->set("auth_file_content", $auth_file_content);
                    $this->view->set("download_auth_file_link", $download_auth_file_link);


                    return $this->view->fetch();
                }
            } else {
                $this->Input->setErrors(array('invalid_action' => array('internal' => Language::_("ThesslstoreModule.!error.generate_cert_invalid_certificate_status", true))));
                return;
            }
        }
        else {
            $this->Input->setErrors(array('invalid_action' => array('internal' => Language::_("ThesslstoreModule.!error.invalid_service_status", true))));
            return;
        }

    }

    /**
     * Client Resend Approver Email for Pending order tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientResendApproverEmail($package, $service, array $get=null, array $post=null, array $files=null) {

        if($service->status == 'active') {
            Loader::loadHelpers($this, array("Html"));

            $api = $this->getApi();

            // Get the service fields
            $service_fields = $this->serviceFieldsToObject($service->fields);

            $order_resp = $this->getSSLOrderStatus($service_fields->thesslstore_order_id);
            if($order_resp != NULL && $order_resp->AuthResponse->isError == false){
                $auth_file_name = $order_resp->AuthFileName;
                $auth_file_content = $order_resp->AuthFileContent;

                if (($order_resp->OrderStatus->MajorStatus == "Pending" || $order_resp->OrderStatus->MinorStatus == "PENDING_REISSUE") && $auth_file_name == '' && $auth_file_content == '') {
                    $order_resend_req = new order_resend_request();
                    $order_resend_req->TheSSLStoreOrderID = $service_fields->thesslstore_order_id;
                    $order_resend_req->ResendEmailType = 'ApproverEmail';

                    $this->log($this->api_partner_code . "|resend-approver-email", serialize($order_resend_req), "input", true);
                    $results = $this->parseResponse($api->order_resend($order_resend_req));

                    if ($results)
                        return '<section class="error_section"><article class="error_box"><div class="alert alert-success alert-dismissable">
                                    <p style="padding: 0 0 0 20px;">' . Language::_("ThesslstoreModule.success.resend_approver_email", true) . '</p>
                                </div></article></section>';
                } else {
                    $this->Input->setErrors(array('invalid_action' => array('internal' => Language::_("ThesslstoreModule.!error.resend_invalid_status", true))));
                    return;
                }
            }
            else{
                return;
            }
        }
        else {
            $this->Input->setErrors(array('invalid_action' => array('internal' => Language::_("ThesslstoreModule.!error.invalid_service_status", true))));
            return;
        }

    }
    /**
     * placed full order for certificate generation process
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $post Any POST parameters
     * @param stdClass $order_status_resp A stdClass object
     * @return boolean true|false based on success
     */
    private function placeFullOrder($package,$service,$post,$order_status_resp){
        $vars = (object)$post;
        //$api = $this->getApi();

        $service_fields = $this->serviceFieldsToObject($service->fields);

        $san_count = $order_status_resp->SANCount;
        $server_count = $order_status_resp->ServerCount;
        $validity = $order_status_resp->Validity;
        $token = $order_status_resp->Token;

        $product_code = $package->meta->thesslstore_product_code;
        $vendor_name = $package->meta->thesslstore_vendor_name;

        $thesslstore_order_id = $service_fields->thesslstore_order_id;


        $csr = isset($vars->thesslstore_csr) ? $vars->thesslstore_csr : '';
        $web_server_type = isset($vars->thesslstore_webserver_type) ? $vars->thesslstore_webserver_type : 'Other';
        $signature_algorithm = isset($vars->thesslstore_signature_algorithm) ? $vars->thesslstore_signature_algorithm : '';
        if($vendor_name == 'COMODO'){
            if($signature_algorithm == 'SHA2-256'){
                $signature_algorithm = 'PREFER_SHA2';
            }
            elseif($signature_algorithm == 'SHA1'){
                $signature_algorithm = 'PREFER_SHA1';
            }
        }

        $auth_method = isset($vars->thesslstore_auth_method) ? $vars->thesslstore_auth_method : '';
        $admin_first_name = isset($vars->thesslstore_admin_first_name) ? $vars->thesslstore_admin_first_name : '';
        $admin_last_name = isset($vars->thesslstore_admin_last_name) ? $vars->thesslstore_admin_last_name : '';
        $admin_title = isset($vars->thesslstore_admin_title) ? $vars->thesslstore_admin_title : '';
        $admin_email = isset($vars->thesslstore_admin_email) ? $vars->thesslstore_admin_email : '';
        $admin_phone = isset($vars->thesslstore_admin_phone) ? $vars->thesslstore_admin_phone : '';
        $org_name = isset($vars->thesslstore_org_name) ? $vars->thesslstore_org_name : '';
        $org_division = isset($vars->thesslstore_org_division) ? $vars->thesslstore_org_division : '';
        $admin_address1 = isset($vars->thesslstore_admin_address1) ? $vars->thesslstore_admin_address1 : '';
        $admin_address2 = isset($vars->thesslstore_admin_address2) ? $vars->thesslstore_admin_address2 : '';
        $admin_city = isset($vars->thesslstore_admin_city) ? $vars->thesslstore_admin_city : '';
        $admin_state = isset($vars->thesslstore_admin_state) ? $vars->thesslstore_admin_state : '';
        $admin_country = isset($vars->thesslstore_admin_country) ? $vars->thesslstore_admin_country : '';
        $admin_zip = isset($vars->thesslstore_admin_zip) ? $vars->thesslstore_admin_zip : '';
        $tech_first_name = isset($vars->thesslstore_tech_first_name) ? $vars->thesslstore_tech_first_name : '';
        $tech_last_name = isset($vars->thesslstore_tech_last_name) ? $vars->thesslstore_tech_last_name : '';
        $tech_title = isset($vars->thesslstore_tech_title) ? $vars->thesslstore_tech_title : '';
        $tech_email = isset($vars->thesslstore_tech_email) ? $vars->thesslstore_tech_email : '';
        $tech_phone = isset($vars->thesslstore_tech_phone) ? $vars->thesslstore_tech_phone : '';

        $approver_email = isset($vars->thesslstore_approver_emails) ? implode(',',$vars->thesslstore_approver_emails) : '';


        $sans = array();
        if(isset($vars->thesslstore_additional_san)){
            foreach($vars->thesslstore_additional_san as $san){
                if(trim($san)!= '')
                    $sans[] = $san;
            }
        }

        //call validate CSR to get domain name
        $validate_csr_resp = $this->validateCSR($csr, $product_code);

        $domain_name = $validate_csr_resp ? $validate_csr_resp->DomainName : '';
        $clean_domain_name = str_replace(array('www.','*.'),"",$domain_name);


        $contact = new contact();
        $contact->AddressLine1 = html_entity_decode($admin_address1);
        $contact->AddressLine2 = html_entity_decode($admin_address2);
        $contact->City = html_entity_decode($admin_city);
        $contact->Region = html_entity_decode($admin_state);
        $contact->Country = html_entity_decode($admin_country);
        $contact->Email = html_entity_decode($admin_email);
        $contact->Fax = '';
        $contact->FirstName = html_entity_decode($admin_first_name);
        $contact->LastName = html_entity_decode($admin_last_name);
        $contact->OrganizationName = html_entity_decode($org_name);
        $contact->Phone = html_entity_decode($admin_phone);
        $contact->PostalCode = html_entity_decode($admin_zip);
        $contact->Title = html_entity_decode($admin_title);

        $tech_contact = new contact();
        $tech_contact->AddressLine1 = '';
        $tech_contact->AddressLine2 = '';
        $tech_contact->City = '';
        $tech_contact->Region = '';
        $tech_contact->Country = '';
        $tech_contact->Email = html_entity_decode($tech_email);
        $tech_contact->Fax = '';
        $tech_contact->FirstName = html_entity_decode($tech_first_name);
        $tech_contact->LastName = html_entity_decode($tech_last_name);
        $tech_contact->OrganizationName = '';
        $tech_contact->Phone = html_entity_decode($tech_phone);
        $tech_contact->PostalCode = '';
        $tech_contact->Title = html_entity_decode($tech_title);

        $new_order = new order_neworder_request();


        $new_order->AddInstallationSupport = false;
        $new_order->AdminContact = $contact;
        $new_order->CSR = $csr;
        $new_order->DomainName = '';
        $new_order->CustomOrderID = uniqid('BlestaFullOrder-');
        $new_order->RelatedTheSSLStoreOrderID = '';
        $new_order->DNSNames = $sans;
        $new_order->EmailLanguageCode = 'EN';
        $new_order->ExtraProductCodes = '';
        $new_order->OrganizationInfo->DUNS = '';
        $new_order->OrganizationInfo->Division = html_entity_decode($org_division);
        $new_order->OrganizationInfo->IncorporatingAgency = '';
        $new_order->OrganizationInfo->JurisdictionCity = $contact->City;
        $new_order->OrganizationInfo->JurisdictionCountry = $contact->Country;
        $new_order->OrganizationInfo->JurisdictionRegion = $contact->Region;
        $new_order->OrganizationInfo->OrganizationName = html_entity_decode($org_name);
        $new_order->OrganizationInfo->RegistrationNumber = '';
        $new_order->OrganizationInfo->OrganizationAddress->AddressLine1 = $contact->AddressLine1;
        $new_order->OrganizationInfo->OrganizationAddress->AddressLine2 = $contact->AddressLine2;
        $new_order->OrganizationInfo->OrganizationAddress->AddressLine3 = '';
        $new_order->OrganizationInfo->OrganizationAddress->City = $contact->City;
        $new_order->OrganizationInfo->OrganizationAddress->Country = $contact->Country;
        $new_order->OrganizationInfo->OrganizationAddress->Fax = $contact->Fax;
        $new_order->OrganizationInfo->OrganizationAddress->LocalityName = '';
        $new_order->OrganizationInfo->OrganizationAddress->Phone = html_entity_decode($admin_phone);
        $new_order->OrganizationInfo->OrganizationAddress->PostalCode = html_entity_decode($admin_zip);
        $new_order->OrganizationInfo->OrganizationAddress->Region = html_entity_decode($admin_state);
        $new_order->ProductCode = $product_code;
        $new_order->ReserveSANCount = $san_count;
        $new_order->ServerCount = $server_count;
        $new_order->SpecialInstructions = '';
        $new_order->TechnicalContact = $tech_contact;
        $new_order->ValidityPeriod = $validity; //number of months
        $new_order->WebServerType = $web_server_type;
        $new_order->isCUOrder = false;
        $new_order->isRenewalOrder = true;
        $new_order->isTrialOrder = false;
        $new_order->SignatureHashAlgorithm = $signature_algorithm;

        if($auth_method == 'HTTP' || $auth_method == 'HTTPS') {
            $approver_list = $this->getApproverEmailsList($product_code, $domain_name);
            $approver_email = $approver_list[0];
            foreach($approver_list as $apEmail){
                if(strpos($apEmail, 'admin@') === 0 || strpos($apEmail, 'administrator@') === 0){
                    $approver_email = $apEmail;
                    break;
                }
            }
        }
        if($auth_method == 'HTTP'){
            $new_order->FileAuthDVIndicator = true;
            $new_order->HTTPSFileAuthDVIndicator = false;
            $new_order->ApproverEmail = $approver_email;
            if($vendor_name == 'COMODO'){
                $new_order->CSRUniqueValue = date('YmdHisa');
            }
        }
        elseif($auth_method == 'HTTPS'){
            $new_order->FileAuthDVIndicator = false;
            $new_order->HTTPSFileAuthDVIndicator = true;
            $new_order->ApproverEmail = $approver_email;
            if($vendor_name == 'COMODO'){
                $new_order->CSRUniqueValue = date('YmdHisa');
            }
        }
        else{
            $new_order->FileAuthDVIndicator = false;
            $new_order->HTTPSFileAuthDVIndicator = false;
            $new_order->ApproverEmail = $approver_email;
        }

        $api = $this->getApi(null,null,'',$IsUsedForTokenSystem = true,$token);


        $this->log($this->api_partner_code . "|ssl-new-order", serialize($new_order), "input", true);
        $results = $this->parseResponse($api->order_neworder($new_order));

        if($results != NULL && $results->AuthResponse->isError == false){
            //call order status after placed full order to get common name
            $order_status = $this->getSSLOrderStatus($thesslstore_order_id);
            $fqdn_name = $order_status ? $order_status->CommonName : '';

            //store service fields
            Loader::loadModels($this, array("Services"));
            //store CSR
            if(isset($service_fields->thesslstore_csr)){
                $this->Services->editField($service->id, array(
                    'key' => "thesslstore_csr",
                    'value' => $csr,
                    'encrypted' => 1
                ));
            }
            else {
                $this->Services->addField($service->id, array(
                    'key' => "thesslstore_csr",
                    'value' => $csr,
                    'encrypted' => 1
                ));
            }
            //store domain name
            if(isset($service_fields->thesslstore_fqdn)){
                $this->Services->editField($service->id, array(
                    'key' => "thesslstore_fqdn",
                    'value' => $fqdn_name,
                    'encrypted' => 0
                ));
            }
            else {
                $this->Services->addField($service->id, array(
                    'key' => "thesslstore_fqdn",
                    'value' => $fqdn_name,
                    'encrypted' => 0
                ));
            }

            return true;
        }

        return false;

    }

    /**
     * Client Reissue Certificate for Active order tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientReissueCert($package, $service, array $get=null, array $post=null, array $files=null){
        if($service->status == 'active'){
            $api = $this->getApi();

            // Get the service fields
            $service_fields = $this->serviceFieldsToObject($service->fields);

            $order_resp = $this->getSSLOrderStatus($service_fields->thesslstore_order_id);
            if($order_resp != NULL && $order_resp->AuthResponse->isError == false){
                if ($order_resp->OrderStatus->MajorStatus == 'Active' && $order_resp->OrderStatus->MinorStatus != 'PENDING_REISSUE') {

                    $this->view = new View("tab_client_reissue_cert", "default");
                    $this->view->base_uri = $this->base_uri;
                    $this->view->setDefaultView("components" . DS . "modules" . DS . "thesslstore_module" . DS);

                    // Load the helpers required for this view
                    Loader::loadHelpers($this, array("Form", "Html"));

                    $use_central_api = false;
                    $vendor_name = $package->meta->thesslstore_vendor_name;
                    $is_code_signing = $package->meta->thesslstore_is_code_signing;
                    $is_scan_product = $package->meta->thesslstore_is_scan_product;
                    $product_code = $package->meta->thesslstore_product_code;
                    $approver_email_list = array();
                    $selected_emails = array();

                    $step = isset($post['thesslstore_reissue_step']) ? $post['thesslstore_reissue_step'] : 1;

                    if ($vendor_name == 'CERTUM' || $is_code_signing == 'y' || $is_scan_product == 'y') {
                        //Generate central api link for CERTUM and Code signing products
                        $use_central_api = true;

                        $order_reissue_req = new order_reissue_request();
                        $order_reissue_req->TheSSLStoreOrderID = $service_fields->thesslstore_order_id;
                        $order_reissue_req->WebServerType = $order_resp->WebServerType;
                        $order_reissue_req->PreferEnrollmentLink = true;
                        $order_reissue_req->isWildCard = false;

                        $this->log($this->api_partner_code . "|ssl-reissue-central-api", serialize($order_reissue_req), "input", true);
                        $results = $this->parseResponse($api->order_reissue($order_reissue_req));

                        if ($results != NULL && $results->AuthResponse->isError == false) {
                            $central_api_link = $results->TinyOrderLink;

                            $this->view->set("use_central_api", $use_central_api);
                            $this->view->set("central_api_link", $central_api_link);
                            return $this->view->fetch();
                        }


                    } else {
                        $thesslstore_csr = (isset($service_fields->thesslstore_csr) ? $service_fields->thesslstore_csr : '');
                        $thesslstore_webserver_type = $order_resp->WebServerType;
                        $thesslstore_signature_algorithm = $order_resp->SignatureHashAlgorithm;
                        $auth_file_name = $order_resp->AuthFileName;
                        $auth_file_content = $order_resp->AuthFileContent;
                        if ($vendor_name == 'COMODO') {
                            if ($thesslstore_signature_algorithm == 'PREFER_SHA2') {
                                $thesslstore_signature_algorithm = 'SHA2-256';
                            } elseif ($thesslstore_signature_algorithm == 'PREFER_SHA1') {
                                $thesslstore_signature_algorithm == 'SHA1';
                            }
                        }
                        $san_count = $order_resp->SANCount;
                        $thesslstore_additional_san = explode(",", $order_resp->DNSNames);
                        $thesslstore_auth_method = 'EMAIL';
                        if ($order_resp->AuthFileName != '') {
                            $thesslstore_auth_method = 'HTTP';
                        }

                        //to display selected approver email
                        $emails = explode(",", $order_resp->ApproverEmail);
                        $domains = explode(",", $order_resp->DNSNames);
                        //for common name
                        if (isset($emails[0])) {
                            $selected_emails[$order_resp->CommonName] = $emails[0];
                        }
                        //for additional san
                        $i = 1;
                        foreach ($domains as $dm) {
                            if (isset($emails[$i])) {
                                $selected_emails[$dm] = $emails[$i];
                            }
                            $i++;
                        }


                        if (isset($post['thesslstore_reissue_submit'])) {
                            $thesslstore_csr = $post['thesslstore_csr'];
                            $thesslstore_webserver_type = $post['thesslstore_webserver_type'];
                            $thesslstore_signature_algorithm = $post['thesslstore_signature_algorithm'];
                            $thesslstore_additional_san = isset($post['thesslstore_additional_san']) ? $post['thesslstore_additional_san'] : array();
                            $thesslstore_auth_method = $post['thesslstore_auth_method'];
                            $this->validateReissueCert($package, $post, $san_count);
                            if (!$this->Input->errors()) {
                                //get approver email list for email based authentication
                                if ($step == 1) {
                                    //Symantec not allowed change approver email or auth type in reissue
                                    if ($thesslstore_auth_method == 'EMAIL' && $vendor_name == 'COMODO') {
                                        $approver_email_list_resp = $this->getApproverEmailsList($product_code, $order_resp->CommonName);
                                        $approver_email_list[$order_resp->CommonName] = $approver_email_list_resp;

                                        if (isset($post['thesslstore_additional_san']) && $vendor_name == 'COMODO') {
                                            foreach ($post['thesslstore_additional_san'] as $san) {
                                                if (trim($san) != '') {

                                                    //get Approver Email list
                                                    $approver_email_list_resp = $this->getApproverEmailsList($product_code, $san);
                                                    $approver_email_list[$san] = $approver_email_list_resp;
                                                }
                                            }
                                        }
                                        $step = 2;
                                    } else {
                                        $success = $this->reIssueCertificate($package, $service, $post, $order_resp);
                                        //if any error then diplay step 1 with error
                                        if (!$success) {
                                            $step = 1;
                                        } else {
                                            $step = 3; //display step 3 when successfully reissue
                                            $new_order_resp = $this->getSSLOrderStatus($service_fields->thesslstore_order_id);
                                            $auth_file_name = $new_order_resp ? $new_order_resp->AuthFileName : '';
                                            $auth_file_content = $new_order_resp
                                                ? $new_order_resp->AuthFileContent
                                                : '';
                                        }
                                    }
                                } elseif ($step == 2) {
                                    $success = $this->reIssueCertificate($package, $service, $post, $order_resp);
                                    //if any error then diplay step 1 with error
                                    if (!$success) {
                                        $step = 1;
                                    } else {
                                        $step = 3; //display step 3 when successfully reissue
                                        $new_order_resp = $this->getSSLOrderStatus($service_fields->thesslstore_order_id);
                                        $auth_file_name = $new_order_resp ? $new_order_resp->AuthFileName : '';
                                        $auth_file_content = $new_order_resp ? $new_order_resp->AuthFileContent : '';
                                    }
                                }
                            } else {
                                //When any error display step1
                                $step = 1;
                            }
                        }

                        $this->view->set("service_id", $service->id);
                        $this->view->set("step", $step);
                        $this->view->set("thesslstore_csr", $thesslstore_csr);
                        $this->view->set("thesslstore_webserver_types", $this->getWebserverTypes());
                        $this->view->set("thesslstore_webserver_type", $thesslstore_webserver_type);
                        $this->view->set("thesslstore_signature_algorithms", array('SHA2-256' => 'SHA-2', 'SHA1' => 'SHA-1'));
                        $this->view->set("thesslstore_signature_algorithm", $thesslstore_signature_algorithm);
                        $this->view->set("san_count", $san_count);
                        $this->view->set("thesslstore_additional_san", $thesslstore_additional_san);
                        $this->view->set("vendor_name", $vendor_name);
                        $this->view->set("thesslstore_auth_method", $thesslstore_auth_method);
                        $this->view->set("use_central_api", $use_central_api);
                        $this->view->set("approver_email_list", $approver_email_list);
                        $this->view->set("auth_file_name", $auth_file_name);
                        $this->view->set("auth_file_content", $auth_file_content);
                        $this->view->set("selected_emails", $selected_emails);

                        $download_auth_file_link = $this->base_uri . "services/manage/" . ($service->id) . "/tabClientDownloadAuthFile/";
                        $this->view->set("download_auth_file_link", $download_auth_file_link);

                        return $this->view->fetch();
                    }
                }
                else {
                    $this->Input->setErrors(array('invalid_action' => array('internal' => Language::_("ThesslstoreModule.!error.reissue_cert_invalid_certificate_status", true))));
                    return;
                }
            }

        }
        else {
            $this->Input->setErrors(array('invalid_action' => array('internal' => Language::_("ThesslstoreModule.!error.invalid_service_status", true))));
            return;
        }
    }

    /**
     * Re-Issue Certificate Call
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $post Any POST parameters
     * @param stdClass $order_status_resp A stdClass object
     * @return boolean true|false based on success
     */
    private function reIssueCertificate($package,$service,$post,$order_status_resp){
        $vars = (object)$post;
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $api = $this->getApi();
        /*$file_based_auth = false;
        if($order_status_resp->AuthFileName != '' && $order_status_resp->AuthFileContent != ''){
            $file_based_auth = true;
        }*/
        $vendor_name = $package->meta->thesslstore_vendor_name;
        $signature_algorithm = $vars->thesslstore_signature_algorithm;
        $additional_san = isset($vars->thesslstore_additional_san) ? $vars->thesslstore_additional_san : array();
        $dns_names = explode(',', $order_status_resp->DNSNames);
        $add_san_old_new_pair = array();
        $delete_san_old_new_pair = array();
        $csr = $vars->thesslstore_csr;

        //New Added SAN
        $added_san = array_diff($additional_san,$dns_names);
        foreach($added_san as $san){
            $pair = new oldNewPair();
            $pair->OldValue = '';
            $pair->NewValue = $san;
            $add_san_old_new_pair[] = $pair;
        }


        //Deleted SAN
        $deleted_san = array_diff($dns_names,$additional_san);
        foreach($deleted_san as $san){
            $pair = new oldNewPair();
            $pair->OldValue = $san;
            $pair->NewValue = '';
            $delete_san_old_new_pair[] = $pair;
        }

        if($vendor_name == 'COMODO'){
            if($signature_algorithm == 'SHA2-256'){
                $signature_algorithm = 'PREFER_SHA2';
            }
            elseif($signature_algorithm == 'SHA1'){
                $signature_algorithm = 'PREFER_SHA1';
            }
        }




        $order_reissue_req = new order_reissue_request();
        $order_reissue_req->CSR = $csr;
        $order_reissue_req->TheSSLStoreOrderID = $order_status_resp->TheSSLStoreOrderID;
        $order_reissue_req->WebServerType = $vars->thesslstore_webserver_type;
        $order_reissue_req->isWildCard = false;
        $order_reissue_req->PreferEnrollmentLink = false;
        $order_reissue_req->FileAuthDVIndicator = false;
        if($vars->thesslstore_auth_method == 'HTTP') {
            $order_reissue_req->FileAuthDVIndicator = true;
            if($vendor_name == 'COMODO'){
                $order_reissue_req->CSRUniqueValue = date('YmdHisa');
            }
        }
        $order_reissue_req->HTTPSFileAuthDVIndicator = false;
        if($vars->thesslstore_auth_method == 'HTTPS'){
            $order_reissue_req->HTTPSFileAuthDVIndicator = true;
            if($vendor_name == 'COMODO'){
                $order_reissue_req->CSRUniqueValue = date('YmdHisa');
            }
        }
        $order_reissue_req->CNAMEAuthDVIndicator = false;
        $order_reissue_req->ApproverEmails = $order_status_resp->ApproverEmail;
        //Symantec not allowed to change Auth method as well as authentication email in Reissue
        if($vendor_name == 'COMODO' && $vars->thesslstore_auth_method == 'EMAIL'){
            $order_reissue_req->ApproverEmails = implode(",", $vars->thesslstore_approver_emails);
        }
        $order_reissue_req->SignatureHashAlgorithm = $signature_algorithm;
        $order_reissue_req->AddSAN = $add_san_old_new_pair;
        $order_reissue_req->DeleteSAN = $delete_san_old_new_pair;
        $order_reissue_req->ReissueEmail = $order_status_resp->AdminContact->Email;

        $this->log($this->api_partner_code . "|ssl-reissue", serialize($order_reissue_req), "input", true);
        $results = $this->parseResponse($api->order_reissue($order_reissue_req));

        //Update CSR

        if($results != NULL && $results->AuthResponse->isError == false){

            //store service fields
            Loader::loadModels($this, array("Services"));
            //store CSR
            if(isset($service_fields->thesslstore_csr)) {
                $this->Services->editField($service->id, array(
                    'key' => "thesslstore_csr",
                    'value' => $csr,
                    'encrypted' => 1
                ));
            }else{
                $this->Services->addField($service->id, array(
                    'key' => "thesslstore_csr",
                    'value' => $csr,
                    'encrypted' => 1
                ));
            }

            return true;
        }
        return false;

    }

    /**
     * Retrieves a SSL Order Status
     *
     * @param string $order_id TheSSLStore Order ID
     * @return stdClass $response A response of order request
     */
    private function getSSLOrderStatus($order_id){
        $api = $this->getApi();

        $order_status_req = new order_status_request();
        $order_status_req->TheSSLStoreOrderID = $order_id;

        $this->log($this->api_partner_code . "|ssl-order-status", serialize($order_status_req), "input", true);
        $results = $this->parseResponse($api->order_status($order_status_req));

        return $results;
    }
    /**
     * validate CSR
     *
     * @param string $csr
     * @param string $product_code SSLStore Product code
     * @param boolean $valid Is function is used for validation in Certificate generation process
     * @return stdClass $response A response of order request
     */
    public function validateCSR($csr, $product_code, $valid = false){
        $api = $this->getApi();
        $csr_req = new csr_request();
        $csr_req->CSR = $csr;
        $csr_req->ProductCode = $product_code;


        $this->log($this->api_partner_code . "|validate-csr", serialize($csr_req), "input", true);
        $results = $this->parseResponse($api->csr($csr_req));

        if($valid){
            if($results == NULL) {
                return false;
            }
            return true;
        }
        return $results;
    }

    /**
     * validate Additional SAN fields for Certificate generation Process.
     *
     * @param string $csr
     * @param string $product_code SSLStore Product code
     * @param boolean $valid Is function is used for validation in Certificate generation process
     * @return stdClass $response A response of order request
     */
    public function validateAdditionalSAN($additional_san){
        foreach($additional_san as $san){
            if(trim($san) != '')
                return true;
        }
        return false;
    }

    /**
     * Return formatted date as per blesta datetime configuration.
     *
     *@param string $utc_date Date in utc.
     *@return string $date A date as per blesta configurations
     */
    private function getFormattedDate($utc_date){

        // Load the Loader to fetch info of All the installed Module
        Loader::loadModels($this, array("Companies"));
        $date_format = $this->Companies->getSetting(Configure::get("Blesta.company_id"), "date_format")->value;
        $timezone = $this->Companies->getSetting(Configure::get("Blesta.company_id"), "timezone")->value;

        if(!empty($date_format) && !empty($timezone))
        {
            $date =  date_create($utc_date, new DateTimeZone("UTC"))
                ->setTimezone(new DateTimeZone($timezone))->format($date_format);

            if($date !== false)
                return $date;
        }
        return $utc_date;
    }

    /**
     * Management Action tab generic function for Admin
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return array The array of the approver email list
     */
    public function tabAdminManagementAction($package, $service, array $get=null, array $post=null, array $files=null) {
        if($service->status == 'active')
        {
                $this->view = new View("tab_admin_management_action", "default");

                $this->view->base_uri = $this->base_uri;
                $this->view->setDefaultView("components" . DS . "modules" . DS . "thesslstore_module" . DS);

                // Load the helpers required for this view
                Loader::loadHelpers($this, array("Form", "Html", "Widget"));
            // Get the service fields
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $orderID=$service_fields->thesslstore_order_id;
            // Gether order info using the order status request
            $order_resp = $this->getSSLOrderStatus($orderID);
            //Major Status Initial
            if ($order_resp && $order_resp->OrderStatus->MajorStatus!='Initial')
            {
                $fileName = $order_resp->AuthFileName;
                $fileContent = $order_resp->AuthFileContent;

                $this->view->set("serviceID", $service->id);
                $this->view->set("clientID", $service->client_id);
                $this->view->set("orderMajorStatus", $order_resp->OrderStatus->MajorStatus);
                $this->view->set("orderMinorStatus", $order_resp->OrderStatus->MinorStatus);
                $this->view->set("fileName", $fileName);
                $this->view->set("fileContent", $fileContent);
                $this->view->set("VendorName", $order_resp->VendorName);

                /* Retrieve the module row for change approver option */
                $module_rows = $this->getModuleRows();
                foreach ($module_rows as $row) {
                    if (isset($row->meta->hide_changeapprover_option)) {
                        $hide_changeapprover_option = $row->meta->hide_changeapprover_option;
                    }
                }
                $this->view->set("hide_changeapprover_option", $hide_changeapprover_option);

                return $this->view->fetch();
            }
            else
            {
                return '<section class="error_section">
                            <article class="error_box error">
                                <p style="padding:0 0 0 37px">'.Language::_("ThesslstoreModule.!error.initial_order_status", true).'</p>
                            </article>
                        </section>';
            }
        }
        else
        {
            return '<section class="error_section">
                        <article class="error_box error">
                            <p style="padding:0 0 0 37px">'.Language::_("ThesslstoreModule.!error.invalid_service_status", true).'</p>
                        </article>
                    </section>';
        }
    }

    /**
     * Change Approver email tab generic function
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return array The array of the approver email list
     */
    public function tabClientChangeApproverEmail($package, $service, array $get=null, array $post=null, array $files=null)
    {
        $this->view = new View("tab_client_change_approver_email", "default");
        return $this->tabChangeApproverEmailInternal($package, $service, $get, $post, $files);
    }

    /**
     * Change Approver email tab generic function for Admin
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return array The array of the approver email list
     */
    public function tabAdminChangeApproverEmail($package, $service, array $get=null, array $post=null, array $files=null)
    {
        $this->view = new View("tab_admin_change_approver_email", "default");
        return $this->tabChangeApproverEmailInternal($package, $service, $get, $post, $files);
    }

    /**
     * Change Approver email tab generic function Internal
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return array The array of the approver email list
     */
    public function tabChangeApproverEmailInternal($package, $service, array $get=null, array $post=null, array $files=null) {
        if($service->status == 'active') {
            // Get the service fields
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $orderID=$service_fields->thesslstore_order_id;
            // Gether order info using the order status request
            $order_resp = $this->getSSLOrderStatus($orderID);
            $fileName=$order_resp->AuthFileName;
            $fileContent=$order_resp->AuthFileContent;
            $VendorName=$order_resp->VendorName;
            /* Retrieve the module row for change approver option */
            $module_rows = $this->getModuleRows();
            foreach ($module_rows as $row) {
                if (isset($row->meta->hide_changeapprover_option)) {
                    $hide_changeapprover_option = $row->meta->hide_changeapprover_option;
                }
            }
            if ($order_resp
                && ($order_resp->OrderStatus->MajorStatus == 'Pending'
                    || $order_resp->OrderStatus->MinorStatus == 'PENDING_REISSUE')
                && $fileContent == ''
                && $fileName == ''
            ) {
                if($VendorName!='SYMANTEC' || ($VendorName=='SYMANTEC' && $hide_changeapprover_option != "YES" ))
                {
                    $this->view->base_uri = $this->base_uri;
                    $this->view->setDefaultView("components" . DS . "modules" . DS . "thesslstore_module" . DS);

                    // Load the helpers required for this view
                    Loader::loadHelpers($this, array("Form", "Html", "Widget"));
                    $productCode = $package->meta->thesslstore_product_code;
                    // Get the service fields
                    $service_fields = $this->serviceFieldsToObject($service->fields);
                    $orderID = $service_fields->thesslstore_order_id;
                    // Call the changeapproveremail function when the save button press
                    if (isset($_POST["save"])) {
                        $domainsArray = $_POST['domains'];
                        $emailArray = $_POST['email'];
                        $approverEmailArray = $_POST['approverEmail'];
                        $this->changeApproverEmail($approverEmailArray, $domainsArray, $emailArray, $orderID);
                        if (!$this->errors()) {
                            //Redirect to certificate details page on success
                            header('Location:' . $this->base_uri . "services/manage/" . ($service->id) . "/tabClientCertDetails/?success=change_mail");
                            exit();
                        }

                    }
                    // Gether order info using the order status request
                    $order_resp = $this->getSSLOrderStatus($orderID);
                    $domainName = $order_resp ? $order_resp->CommonName : '';
                    $approverEmail = $order_resp ? $order_resp->ApproverEmail : '';
                    $approverEmailArray = explode(',', $approverEmail);
                    $approverEmail = $approverEmailArray[0];
                    $getApproverEmailsList = $this->getApproverEmailsList($productCode, $domainName);
                    $approverEmailsListArray = array_filter(array_diff($getApproverEmailsList, array('support_preprod@geotrust.com', 'support@geotrust.com')));
                    $this->view->set("domainName", $domainName);
                    $this->view->set("approverEmail", $approverEmail);
                    $this->view->set("approverEmailsListArray", (object)$approverEmailsListArray);

                    // Check for the dnsNames for MD products
                    $dnsNames = $order_resp->DNSNames;
                    $dnsNamesArray = explode(',', $dnsNames);
                    if ($productCode == 'quicksslpremiummd' || ($dnsNamesArray[0] == '' && $dnsNamesArray[0] == NULL)) {
                        $dnsCount = 0;
                    } else {
                        $dnsCount = count($dnsNamesArray);
                    }
                    $domainNames = array();
                    $approverEmails = array();
                    $approverEmailsListArrays = array();
                    for ($i = 0; $i < $dnsCount; $i++) {
                        $j = $i + 1;
                        $domainName = $dnsNamesArray[$i];
                        $domainNames[] = $dnsNamesArray[$i];
                        $getApproverEmailsLists = $this->getApproverEmailsList($productCode, $domainName);
                        $approverEmailsListArrays[] = array_filter(array_diff($getApproverEmailsLists, array('support_preprod@geotrust.com', 'support@geotrust.com')));
                        $approverEmails[] = $approverEmailArray[$j];
                    }
                    $this->view->set("domainNames", $domainNames);
                    $this->view->set("approverEmails", $approverEmails);
                    $this->view->set("approverEmailsListArrays", $approverEmailsListArrays);

                    return $this->view->fetch();
                }
                else
                {
                    $this->Input->setErrors(array('invalid_action' => array('internal' => Language::_("ThesslstoreModule.!error.change_approver_email_not_available_for_product", true))));
                    return;
                }
            }
            else
            {
                $this->Input->setErrors(array('invalid_action' => array('internal' => Language::_("ThesslstoreModule.!error.change_approver_email_not_available_for_order", true))));
                return;
            }

        }
        else {
            $this->Input->setErrors(array('invalid_action' => array('internal' => Language::_("ThesslstoreModule.!error.invalid_service_status", true))));
            return;
        }
    }

    /**
     * Returns array of valid approver E-Mails List for domain
     *
     * @param string $productCode The ProductCode
     * @param string $domainName The domain
     * @return array E-Mails that are valid approvers for the domain
     */
    private function getApproverEmailsList($productCode,$domainName) {
        if (empty($domainName))
            return array();

        $api = $this->getApi();

        $orderApproverListReq = new order_approverlist_request();
        $orderApproverListReq->ProductCode = $productCode;
        $orderApproverListReq->DomainName = $domainName;


        $this->log("ssl-domain-emails-list", serialize($orderApproverListReq), "input", true);
        $thesslstore_approver_emails = $this->parseResponse($api->order_approverlist($orderApproverListReq));

        $emails = array();
        if ($thesslstore_approver_emails && !empty($thesslstore_approver_emails->ApproverEmailList)) {
            foreach ($thesslstore_approver_emails->ApproverEmailList as $email)
                if(!empty($email))
                    $emails[$email] = $email;
        }
        $emails = array_filter(array_diff($emails, array('support_preprod@geotrust.com','support@geotrust.com')));
        return $emails;
    }

    /**
     * Change the approver emails of respective domain
     *
     * @param array $approverEmailArray The array of the approveremails
     * @param array $domainsArray The array of the domains including the DNS names
     * @param array $emailArray The array of the approveremail list of each domain
     * @return success Change the approver email of the respective domain.
     */
    private function changeApproverEmail($approverEmailArray,$domainsArray,$emailArray,$orderID) {
        $api = $this->getApi();
        $emailscount=count($emailArray);

        for($i=0;$i<$emailscount;$i++)
        {
            $approveremails=$emailArray[$i];
            $domainname=$domainsArray[$i];
            if ($approveremails==$approverEmailArray[$i])
            {
                //Nothing to do here
            }
            else
            {
                $order_changeapproveremail_request = new order_changeapproveremail_request();
                $order_changeapproveremail_request->ResendEmail = $approveremails;
                $order_changeapproveremail_request->TheSSLStoreOrderID = $orderID;
                $order_changeapproveremail_request->DomainNames = $domainname;
                $this->log("ssl-domain-order-changeapproveremail", serialize($order_changeapproveremail_request), "input", true);
                $result = $this->parseResponse($api->order_changeapproveremail($order_changeapproveremail_request));
            }
        }
    }

    /**
     * Download AuthFile tab generic function
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return Forcessfully download the auth file.
     */
    public function tabClientDownloadAuthFile($package, $service, array $get=null, array $post=null, array $files=null) {
        if($service->status == 'active') {
            // Get the service fields
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $orderID=$service_fields->thesslstore_order_id;
            // Gether order info using the order status request
            $order_resp = $this->getSSLOrderStatus($orderID);
            $fileName = $order_resp ? $order_resp->AuthFileName : '';
            $fileContent = $order_resp ? $order_resp->AuthFileContent : '';
            if(($order_resp->OrderStatus->MajorStatus=='Pending' || $order_resp->OrderStatus->MinorStatus=='PENDING_REISSUE'))
            {
                if(!$fileName && !$fileContent)
                {
                    $this->log($this->api_partner_code."|OrderID-".$orderID . "|ssl-download-authfile", Language::_("ThesslstoreModule.!error.download_authfile_invalid_status", true), "output", false);
                    $this->Input->setErrors(array('invalid_action' => array('internal' => Language::_("ThesslstoreModule.!error.download_authfile_not_available", true))));
                    return;
                    exit;
                }
                else
                {
                    ob_end_clean();
                    header('Content-type:application/octet-stream');
                    header('Content-Disposition:attachment; filename=' . $fileName);
                    echo $fileContent;
                    $this->log($this->api_partner_code."|OrderID-".$orderID . "|ssl-download-authfile", "Auth File Download Successfully", "output", true);
                    exit;
                }

            }
            else
            {
                $this->Input->setErrors(array('invalid_action' => array('internal' => Language::_("ThesslstoreModule.!error.download_authfile_invalid_state", true))));
                return;
            }
            return 'success';
        }
        else {
            $this->Input->setErrors(array('invalid_action' => array('internal' => Language::_("ThesslstoreModule.!error.invalid_service_status", true))));
            return;
        }
    }

    /**
     * Download Certificate tab generic function
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return Forcessfully download the Certificate ZIP file.
     */
    public function tabClientDownloadCertificate($package, $service, array $get=null, array $post=null, array $files=null)
    {
        if($service->status == 'active') {
            // Get the service fields
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $orderID = $service_fields->thesslstore_order_id;

            $api = $this->getApi();
            $downloadReq = new order_download_request();
            $downloadReq->TheSSLStoreOrderID = $orderID;
            $downloadResp = $api->order_download_zip($downloadReq);
            // Gether order info using the order status request
            $order_resp = $this->getSSLOrderStatus($orderID);
            if ($order_resp && $order_resp->OrderStatus->MajorStatus == 'Active')
            {
                if (!$downloadResp->AuthResponse->isError) {
                    $certdecoded = base64_decode($downloadResp->Zip);
                    $filename = $downloadReq->TheSSLStoreOrderID . '.zip';
                    ob_end_clean();
                    header('Content-type:application/octet-stream');
                    header('Content-Disposition:attachment; filename=' . $filename);
                    echo $certdecoded;
                    $this->log($this->api_partner_code . "|OrderID-" . $orderID . "|ssl-download-certificate", "Certificate Download Successfully", "output", true);
                    exit;
                } else {
                    $errors = $downloadResp->AuthResponse->Message;
                    $this->log($this->api_partner_code . "|OrderID-" . $orderID . "|ssl-download-certificate", $errors, "output", false);
                    $this->Input->setErrors(array('invalid_action' => array('internal' => $errors)));
                    return;
                }
            }
            else
            {
                $this->Input->setErrors(array('invalid_action' => array('internal' => Language::_("ThesslstoreModule.!error.download_cert_invalid_state", true))));
                return;
            }
            return 'success';
        }
        else {
            $this->Input->setErrors(array('invalid_action' => array('internal' => Language::_("ThesslstoreModule.!error.invalid_service_status", true))));
            return;
        }
    }
    /**
     *This function is return email content for package email
     *@return string A email content
     */
    private function emailContent(){

        $client = Configure::get("Route.client");
        $WEBDIR=WEBDIR;
        $generation_link = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '') . '://'.$_SERVER['HTTP_HOST'].$WEBDIR.$client.'/'.'services/manage/{service.id}/tabClientGenerateCert/';
        return $email_content = "
            <p>You've successfully completed the purchasing process for an SSL Certificate! But wait, your SSL still requires a few more steps which can be easily done at the following URL:</p>
            <p><a href=\"{$generation_link}\">{$generation_link}</a></p>
            <p>OR</p>
            <p>If you are using AutoInstall SSL then please follow the below steps:</p>
            <p>Now that your SSL purchase is complete, it's time to set up and install your new SSL certificate automatically!</p>
            <p>To use our AutoInstall SSL technology, the fastest and easiest way to get your new SSL certificate set up, please login to your cPanel/Plesk control panel, click on the AutoInstall SSL icon. Then use the following Token for the automatic installation of Store Order ID : {service.thesslstore_order_id}.</p>
            <p>Token : {service.thesslstore_token}</p>
            <p>You'll be guided through the entire process from there, and it should only take a few minutes.</p>
            <p>If you experience any problems or have any questions throughout the process, please feel free to open a support ticket, we know all the ins and outs of SSL and can quickly help you with any issues. Thank you for trusting us with your web security needs.</p>
        ";
    }
    /**
     * Returns an array of key values for fields stored for a module, package,
     * and service under this module, used to substitute those keys with their
     * actual module, package, or service meta values in related emails.
     *
     * @return array A multi-dimensional array of key/value pairs where each key is one of 'module', 'package', or 'service' and each value is a numerically indexed array of key values that match meta fields under that category.
     * @see Modules::addModuleRow()
     * @see Modules::editModuleRow()
     * @see Modules::addPackage()
     * @see Modules::editPackage()
     * @see Modules::addService()
     * @see Modules::editService()
     */
    public function getEmailTags() {
        return array(
            'module' => array(),
            'package' => array(),
            'service' => array("thesslstore_order_id", "thesslstore_token")
        );
    }

    /**
     * Sends a invite order email when placed only invite order in renew service
     *
     * @param stdClass $service An object representing the service created
     * @param stdClass $package An object representing the package associated with the service
     * @param int $client_id The ID of the client to send the notification to
     */
    private function sendInviteOrderEmail($service, $package, $service_meta) {
        Loader::loadModels($this, array("Clients", "Contacts", "Emails", "ModuleManager"));

        //Replace old service meta beacuse email need to send latest data like new token and order id
        foreach($service_meta as $meta ){
            $meta = (object)$meta;
            foreach($service->fields as $index => $field)
                if($field->key == $meta->key) {
                    $service->fields[$index] = (object)$meta;
                    break;
                }
        }

        // Fetch the client
        $client = $this->Clients->get($service->client_id);

        // Look for the correct language of the email template to send, or default to English
        $service_email_content = null;
        foreach ($package->email_content as $index => $email) {
            // Save English so we can use it if the default language is not available
            if ($email->lang == "en_us")
                $service_email_content = $email;

            // Use the client's default language
            if ($client->settings['language'] == $email->lang) {
                $service_email_content = $email;
                break;
            }
        }

        // Set all tags for the email
        $language_code = ($service_email_content ? $service_email_content->lang : null);

        // Get the module and set the module host name
        $module = $this->ModuleManager->initModule($package->module_id, $package->company_id);
        $module_row = $this->ModuleManager->getRow($service->module_row_id);

        // Set all acceptable module meta fields
        $module_fields = array();
        if (!empty($module_row->meta)) {
            $tags = $module->getEmailTags();
            $tags = (isset($tags['module']) && is_array($tags['module']) ? $tags['module'] : array());

            if (!empty($tags)) {
                foreach ($module_row->meta as $key => $value) {
                    if (in_array($key, $tags))
                        $module_fields[$key] = $value;
                }
            }
        }
        $module = (object)$module_fields;

        // Format package pricing
        if (!empty($service->package_pricing)) {
            Loader::loadModels($this, array("Currencies", "Packages"));

            // Set pricing period to a language value
            $package_period_lang = $this->Packages->getPricingPeriods();
            if (isset($package_period_lang[$service->package_pricing->period]))
                $service->package_pricing->period = $package_period_lang[$service->package_pricing->period];
        }

        // Add each service field as a tag
        if (!empty($service->fields)) {
            $fields = array();
            foreach ($service->fields as $field)
                $fields[$field->key] = $field->value;
            $service = (object)array_merge((array)$service, $fields);
        }

        // Add each package meta field as a tag
        if (!empty($package->meta)) {
            $fields = array();
            foreach ($package->meta as $key => $value)
                $fields[$key] = $value;
            $package = (object)array_merge((array)$package, $fields);
        }

        $tags = array(
            'contact' => $this->Contacts->get($client->contact_id),
            'package' => $package,
            'pricing' => $service->package_pricing,
            'module' => $module,
            'service' => $service,
            'client' => $client,
            'package.email_html' => (isset($service_email_content->html) ? $service_email_content->html : ""),
            'package.email_text' => (isset($service_email_content->text) ? $service_email_content->text : "")
        );

        $this->Emails->send("service_creation", $package->company_id, $language_code, $client->email, $tags, null, null, null, array('to_client_id' => $client->id));
    }

    /*
     * Return Web Server List
     */

    private function getWebserverTypes() {
        return array(
            "" => Language::_("ThesslstoreModule.please_select", true),
            "aol" => "AOL",
            "apacheapachessl" => "Apache + ApacheSSL",
            "apachessl" => "Apache + MOD SSL",
            "apacheopenssl" => "Apache + OpenSSL",
            "apacheraven" => "Apache + Raven",
            "apachessleay" => "Apache + SSLeay",
            "apache2" => "Apache 2",
            "c2net" => "C2Net Stronghold",
            "cisco3000" => "Cisco 3000 Series VPN Concentrator",
            "citrix" => "Citrix",
            "cobaltseries" => "Cobalt Series",
            "covalentserver" => "Covalent Server Software",
            "cpanel" => "Cpanel",
            "ensim" => "Ensim",
            "hsphere" => "Hsphere",
            "Iplanet" => "iPlanet Server 4.1",
            "Ibmhttp" => "IBM HTTP",
            "Ibminternet" => "IBM Internet Connection Server",
            "ipswitch" => "Ipswitch",
            "tomcat" => "Jakart-Tomcat",
            "javawebserver" => "Java Web Server (Javasoft / Sun)",
            "Domino" => "Lotus Domino 4.6+",
            "Dominogo4625" => "Lotus Domino Go 4.6.2.51",
            "Dominogo4626" => "Lotus Domino Go 4.6.2.6+",
            "iis4" => "Microsoft IIS 4.0",
            "iis5" => "Microsoft IIS 5.0",
            "iis" => "Microsoft Internet Information Server",
            "Netscape" => "Netscape Enterprise/FastTrack",
            "NetscapeFastTrack" => "Netscape FastTrack",
            "website" => "O'Reilly WebSite Professional",
            "oracle" => "Oracle",
            "plesk" => "Plesk",
            "quid" => "Quid Pro Quo",
            "r3ssl" => "R3 SSL Server",
            "reven" => "Raven SSL",
            "redhat" => "RedHat Linux",
            "sapwebserver" => "SAP Web Application Server",
            "WebLogic" => "WebLogic â€“ all versions",
            "webstar" => "WebStar",
            "webten" => "WebTen (from Tenon)",
            "zeusv3" => "Zeus v3+",
            "other" => "Other"
        );
    }

    /* Return Country List*/

    private function getCountryList(){
        return array(
            '' => Language::_("ThesslstoreModule.please_select", true),
            'AF'=>'Afghanistan','AX'=>'Aland Islands','AL'=>'Albania','DZ'=>'Algeria','AS'=>'American Samoa','AD'=>'Andorra',
            'AO'=>'Angola','AI'=>'Anguilla','AQ'=>'Antarctica','AG'=>'Antigua and Barbuda','AR'=>'Argentina','AM'=>'Armenia','AW'=>'Aruba',
            'AC'=>'Ascension Island','AU'=>'Australia','AT'=>'Austria','AZ'=>'Azerbaijan','BS'=>'Bahamas','BH'=>'Bahrain','BD'=>'Bangladesh',
            'BB'=>'Barbados','BY'=>'Belarus','BE'=>'Belgium','BZ'=>'Belize','BJ'=>'Benin','BM'=>'Bermuda','BT'=>'Bhutan','BO'=>'Bolivia',
            'BQ'=>'Bonaire, Sint Eustatius, and Saba',
            'BA'=>'Bosnia and Herzegovina','BW'=>'Botswana','BV'=>'Bouvet Island','BR'=>'Brazil','IO'=>'British Indian Ocean Territory',
            'VG'=>'British Virgin Islands','BN'=>'Brunei','BG'=>'Bulgaria','BF'=>'Burkina Faso','BI'=>'Burundi','KH'=>'Cambodia','CM'=>'Cameroon',
            'CA'=>'Canada','IC'=>'Canary Islands','CV'=>'Cape Verde','KY'=>'Cayman Islands','CF'=>'Central African Republic','EA'=>'Ceuta and Melilla',
            'TD'=>'Chad','CL'=>'Chile','CN'=>'China','CX'=>'Christmas Island','CP'=>'Clipperton Island','CC'=>'Cocos [Keeling] Islands','CO'=>'Colombia',
            'KM'=>'Comoros','CG'=>'Congo - Brazzaville','CD'=>'Congo - Kinshasa','CK'=>'Cook Islands','CR'=>'Costa Rica','CI'=>'Cote D\'Ivoire','HR'=>'Croatia',
            'CU'=>'Cuba','CW'=>'Curaçao','CY'=>'Cyprus','CZ'=>'Czech Republic','DK'=>'Denmark','DG'=>'Diego Garcia','DJ'=>'Djibouti','DM'=>'Dominica',
            'DO'=>'Dominican Republic','EC'=>'Ecuador','EG'=>'Egypt','SV'=>'El Salvador','GQ'=>'Equatorial Guinea','ER'=>'Eritrea','EE'=>'Estonia',
            'ET'=>'Ethiopia','EU'=>'European Union','FK'=>'Falkland Islands','FO'=>'Faroe Islands','FJ'=>'Fiji','FI'=>'Finland','FR'=>'France',
            'GF'=>'French Guiana','PF'=>'French Polynesia','TF'=>'French Southern Territories','GA'=>'Gabon','GM'=>'Gambia','GE'=>'Georgia',
            'DE'=>'Germany','GH'=>'Ghana','GI'=>'Gibraltar','GR'=>'Greece','GL'=>'Greenland','GD'=>'Grenada','GP'=>'Guadeloupe','GU'=>'Guam',
            'GT'=>'Guatemala','GG'=>'Guernsey','GN'=>'Guinea','GW'=>'Guinea-Bissau','GY'=>'Guyana','HT'=>'Haiti','HM'=>'Heard Island and McDonald Islands',
            'HN'=>'Honduras','HK'=>'Hong Kong SAR China','HU'=>'Hungary','IS'=>'Iceland','IN'=>'India','ID'=>'Indonesia','IR'=>'Iran','IQ'=>'Iraq',
            'IE'=>'Ireland','IM'=>'Isle of Man','IL'=>'Israel','IT'=>'Italy','JM'=>'Jamaica','JP'=>'Japan','JE'=>'Jersey','JO'=>'Jordan',
            'KZ'=>'Kazakhstan','KE'=>'Kenya','KI'=>'Kiribati','KW'=>'Kuwait','KG'=>'Kyrgyzstan','LA'=>'Laos','LV'=>'Latvia','LB'=>'Lebanon',
            'LS'=>'Lesotho','LR'=>'Liberia','LY'=>'Libya','LI'=>'Liechtenstein','LT'=>'Lithuania','LU'=>'Luxembourg','MO'=>'Macau SAR China',
            'MK'=>'Macedonia','MG'=>'Madagascar','MW'=>'Malawi','MY'=>'Malaysia','MV'=>'Maldives','ML'=>'Mali','MT'=>'Malta','MH'=>'Marshall Islands',
            'MQ'=>'Martinique','MR'=>'Mauritania','MU'=>'Mauritius','YT'=>'Mayotte','MX'=>'Mexico','FM'=>'Micronesia','MD'=>'Moldova','MC'=>'Monaco',
            'MN'=>'Mongolia','ME'=>'Montenegro','MS'=>'Montserrat','MA'=>'Morocco','MZ'=>'Mozambique','MM'=>'Myanmar [Burma]','NA'=>'Namibia',
            'NR'=>'Nauru','NP'=>'Nepal','NL'=>'Netherlands','AN'=>'Netherlands Antilles','NC'=>'New Caledonia','NZ'=>'New Zealand',
            'NI'=>'Nicaragua','NE'=>'Niger','NG'=>'Nigeria','NU'=>'Niue','NF'=>'Norfolk Island','KP'=>'North Korea','MP'=>'Northern Mariana Islands',
            'NO'=>'Norway','OM'=>'Oman','QO'=>'Outlying Oceania','PK'=>'Pakistan','PW'=>'Palau','PS'=>'Palestinian Territories','PA'=>'Panama',
            'PG'=>'Papua New Guinea','PY'=>'Paraguay','PE'=>'Peru','PH'=>'Philippines','PN'=>'Pitcairn Islands','PL'=>'Poland','PT'=>'Portugal',
            'PR'=>'Puerto Rico','QA'=>'Qatar','RE'=>'Réunion','RO'=>'Romania','RU'=>'Russia','RW'=>'Rwanda','BL'=>'Saint Barthélemy',
            'SH'=>'Saint Helena','KN'=>'Saint Kitts and Nevis','LC'=>'Saint Lucia','MF'=>'Saint Martin','PM'=>'Saint Pierre and Miquelon',
            'VC'=>'Saint Vincent and the Grenadines','WS'=>'Samoa','SM'=>'San Marino','ST'=>'São Tomé and Príncipe','SA'=>'Saudi Arabia',
            'SN'=>'Senegal','RS'=>'Serbia','CS'=>'Serbia and Montenegro','SC'=>'Seychelles','SL'=>'Sierra Leone','SG'=>'Singapore','SX'=>'Sint Maarten',
            'SK'=>'Slovakia','SI'=>'Slovenia','SB'=>'Solomon Islands','SO'=>'Somalia','ZA'=>'South Africa',
            'GS'=>'South Georgia and the South Sandwich Islands','KR'=>'South Korea','SS'=>'South Sudan','ES'=>'Spain',
            'LK'=>'Sri Lanka','SD'=>'Sudan','SR'=>'Suriname','SJ'=>'Svalbard and Jan Mayen','SZ'=>'Swaziland','SE'=>'Sweden',
            'CH'=>'Switzerland','SY'=>'Syria','TW'=>'Taiwan','TJ'=>'Tajikistan','TZ'=>'Tanzania','TH'=>'Thailand','TL'=>'Timor-Leste','TG'=>'Togo',
            'TK'=>'Tokelau','TO'=>'Tonga','TT'=>'Trinidad and Tobago','TA'=>'Tristan da Cunha','TN'=>'Tunisia','TR'=>'Turkey','TM'=>'Turkmenistan',
            'TC'=>'Turks and Caicos Islands','TV'=>'Tuvalu','UM'=>'U.S. Minor Outlying Islands','VI'=>'U.S. Virgin Islands','UG'=>'Uganda',
            'UA'=>'Ukraine','AE'=>'United Arab Emirates','GB'=>'United Kingdom','US'=>'United States','UY'=>'Uruguay','UZ'=>'Uzbekistan',
            'VU'=>'Vanuatu','VA'=>'Vatican City','VE'=>'Venezuela','VN'=>'Vietnam','WF'=>'Wallis and Futuna','EH'=>'Western Sahara',
            'YE'=>'Yemen','ZM'=>'Zambia','ZW'=>'Zimbabwe'

        );
    }




    /**
     * Parses the response from TheSslStore into an stdClass object
     *
     * @param mixed $response The response from the API
     * @param stdClass $module_row A stdClass object representing a single reseller (optional, required when Module::getModuleRow() is unavailable)
     * @param boolean $ignore_error Ignores any response error and returns the response anyway; useful when a response is expected to fail (optional, default false)
     * @return stdClass A stdClass object representing the response, void if the response was an error
     */
    private function parseResponse($response, $ignore_error = false) {
        Loader::loadHelpers($this, array("Html"));

        /*echo "<pre>";
        print_r($response);*/

        $success = true;

        if(empty($response)) {
            $success = false;

            if (!$ignore_error)
                $this->Input->setErrors(array('api' => array('internal' => Language::_("ThesslstoreModule.!error.api.internal", true))));
        }
        elseif ($response) {
            $auth_response = null;
            if (is_array($response) && isset($response[0]) && $response[0] && is_object($response[0]) && property_exists($response[0], "AuthResponse"))
                $auth_response = $response[0]->AuthResponse;
            elseif (is_object($response) && $response && property_exists($response, "AuthResponse"))
                $auth_response = $response->AuthResponse;
            elseif(is_object($response) && $response && property_exists($response, "isError"))
                $auth_response = $response;

            if ($auth_response && property_exists($auth_response, "isError") && $auth_response->isError) {
                $success = false;
                $error_message = (property_exists($auth_response, "Message") && isset($auth_response->Message[0]) ? $auth_response->Message[0] : Language::_("TheSSLStore.!error.api.internal", true));

                if (!$ignore_error)
                    $this->Input->setErrors(array('api' => array('internal' => $error_message)));
            }
            elseif ($auth_response === null) {
                $success = false;

                if (!$ignore_error)
                    $this->Input->setErrors(array('api' => array('internal' => Language::_("TheSSLStore.!error.api.internal", true))));

            }
        }

        // Break the response into segments no longer than the max length that can be logged
        // (i.e. 64KB = 65535 characters)
        $responses = str_split(serialize($response), 65535);

        foreach ($responses as $log) {
            $this->log($this->api_partner_code, $log, "output", $success);
        }

        if (!$success && !$ignore_error)
            return;

        return $response;
    }
}
