<?php
use Blesta\Core\Util\Common\Traits\Container;

/**
 * The Migrator. Facilitates migration between one remote system and the local system
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.components.migrators
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class Migrator
{
    // Load traits
    use Container;

    /**
     * @var Monolog\Logger An instance of the logger
     */
    protected $logger;

    /**
     * @var Record The database connection object to the local server
     */
    protected $local;

    /**
     * @var Record The database connection object to the remote server
     */
    protected $remote;

    /**
     * @var array A multi-dimensional array, each defined as a key (e.g. 'clients')
     *  that represents a key/value pair array of remote IDs with local IDs
     */
    protected $mappings = [];

    /**
     * @var bool Enable/disable debugging
     */
    protected $enable_debug = false;

    /**
     * Runs the import, sets any Input errors encountered
     */
    abstract public function import();

    /**
     * Construct
     *
     * @param Record $local The database connection object to the local server
     */
    public function __construct(Record $local)
    {
        Loader::loadComponents($this, ['Input']);
        $this->local = $local;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Processes settings (validating input). Sets any necessary input errors
     *
     * @param array $vars An array of key/value input pairs
     */
    public function processSettings(array $vars = null)
    {
    }

    /**
     * Processes configuration (validating input). Sets any necessary input errors
     *
     * @param array $vars An array of key/value input pairs
     */
    public function processConfiguration(array $vars = null)
    {
    }

    /**
     * Returns a view to handle settings
     *
     * @param array $vars An array of input key/value pairs
     * @return string The HTML used to request input settings
     */
    public function getSettings(array $vars)
    {
    }

    /**
     * Returns a view to configuration run after settings but before import
     *
     * @param array $vars An array of input key/value pairs
     * @return string The HTML used to request input settings, return null to bypass
     */
    public function getConfiguration(array $vars)
    {
        return null;
    }

    /**
     * Add staff
     *
     * @param array An array of key/value pairs
     * @param mixed $remote_id The ID of this items on the remote server
     * @return mixed
     */
    public function addStaff(array $vars, $remote_id = null)
    {
        if (!isset($this->Staff)) {
            Loader::loadModels($this, ['Staff']);
        }

        $result = $this->Staff->add($vars);
        if (($errors = $this->Staff->errors())) {
            $this->Input->setErrors($errors);
        }

        if ($remote_id !== null && $result) {
            $this->mappings['staff'][$remote_id] = $result;
        }

        return $result;
    }

    /**
     * Add client
     *
     * @param array An array of key/value pairs
     * @param mixed $remote_id The ID of this items on the remote server
     * @return mixed
     */
    public function addClient(array $vars, $remote_id = null)
    {
        if (!isset($this->Clients)) {
            Loader::loadModels($this, ['Clients']);
        }

        $result = $this->Clients->add($vars);
        if (($errors = $this->Clients->errors())) {
            $this->Input->setErrors($errors);
        }

        if ($remote_id !== null && $result) {
            $this->mappings['clients'][$remote_id] = $result;
        }

        return $result;
    }

    /**
     * Add contact
     *
     * @param array An array of key/value pairs
     * @param mixed $remote_id The ID of this items on the remote server
     * @return mixed
     */
    public function addContact(array $vars, $remote_id = null)
    {
        if (!isset($this->Contacts)) {
            Loader::loadModels($this, ['Contacts']);
        }

        $result = $this->Contacts->add($vars);
        if (($errors = $this->Contacts->errors())) {
            $this->Input->setErrors($errors);
        }

        if ($remote_id !== null && $result) {
            $this->mappings['contacts'][$remote_id] = $result;
        }

        return $result;
    }

    /**
     * Add tax
     *
     * @param array An array of key/value pairs
     * @param mixed $remote_id The ID of this items on the remote server
     * @return mixed
     */
    public function addTax(array $vars, $remote_id = null)
    {
        if (!isset($this->Taxes)) {
            Loader::loadModels($this, ['Taxes']);
        }

        $result = $this->Taxes->add($vars);
        if (($errors = $this->Taxes->errors())) {
            $this->Input->setErrors($errors);
        }

        if ($remote_id !== null && $result) {
            $this->mappings['taxes'][$remote_id] = $result;
        }

        return $result;
    }

    /**
     * Add currency
     *
     * @param array An array of key/value pairs
     * @param mixed $remote_id The ID of this items on the remote server
     * @return mixed
     */
    public function addCurrency(array $vars, $remote_id = null)
    {
        if (!isset($this->Currencies)) {
            Loader::loadModels($this, ['Currencies']);
        }

        $result = $this->Currencies->add($vars);
        if (($errors = $this->Currencies->errors())) {
            $this->Input->setErrors($errors);
        }

        if ($remote_id !== null && $result) {
            $this->mappings['currencies'][$remote_id] = $result;
        }

        return $result;
    }

    /**
     * Add invoice
     *
     * @param array An array of key/value pairs
     * @param mixed $remote_id The ID of this items on the remote server
     * @return mixed
     */
    public function addInvoice(array $vars, $remote_id = null)
    {
        if (!isset($this->Invoices)) {
            Loader::loadModels($this, ['Invoices']);
        }

        $result = $this->Invoices->add($vars);
        if (($errors = $this->Invoices->errors())) {
            $this->Input->setErrors($errors);
        }

        if ($remote_id !== null && $result) {
            $this->mappings['invoices'][$remote_id] = $result;
        }

        return $result;
    }

    /**
     * Add transaction
     *
     * @param array An array of key/value pairs
     * @param mixed $remote_id The ID of this items on the remote server
     * @return mixed
     */
    public function addTransaction(array $vars, $remote_id = null)
    {
        if (!isset($this->Transactions)) {
            Loader::loadModels($this, ['Transactions']);
        }

        $result = $this->Transactions->add($vars);
        if (($errors = $this->Transactions->errors())) {
            $this->Input->setErrors($errors);
        }

        if ($remote_id !== null && $result) {
            $this->mappings['transactions'][$remote_id] = $result;
        }

        return $result;
    }

    /**
     * Returns the transaction type ID
     *
     * @param string $type The version 2 transaction type
     * @return string The transaction type ID
     */
    protected function getTransactionTypeId($type)
    {
        static $trans_types = null;

        if (!isset($this->Transactions)) {
            Loader::loadModels($this, ['Transactions']);
        }

        if ($trans_types == null) {
            $trans_types = $this->Transactions->getTypes();
        }

        switch ($type) {
            default:
            case 'other':
            case 'credit':
                return null;
            case 'cash':
                // Fall through
            case 'check':
                foreach ($trans_types as $trans_type) {
                    if ($trans_type->name == $type) {
                        return $trans_type->id;
                    }
                }
                // Fall through
            case 'inhousecredit':
            case 'in_house_credit':
                foreach ($trans_types as $trans_type) {
                    if ($trans_type->name == 'in_house_credit') {
                        return $trans_type->id;
                    }
                }
                // Fall through
            case 'moneyorder':
            case 'money_order':
                foreach ($trans_types as $trans_type) {
                    if ($trans_type->name == 'money_order') {
                        return $trans_type->id;
                    }
                }
        }
        return null;
    }

    /**
     * Add package
     *
     * @param array An array of key/value pairs
     * @param mixed $remote_id The ID of this items on the remote server
     * @return mixed
     */
    public function addPackage(array $vars, $remote_id = null)
    {
        if (!isset($this->Packages)) {
            Loader::loadModels($this, ['Packages']);
        }

        $result = $this->Packages->add($vars);
        if (($errors = $this->Packages->errors())) {
            $this->Input->setErrors($errors);
        }

        if ($remote_id !== null && $result) {
            $this->mappings['packages'][$remote_id] = $result;
        }

        return $result;
    }

    /**
     * Adds package pricing.
     *
     * @param array $pricing A numerically indexed array of pricing info including:
     *  - term
     *  - period
     *  - price
     *  - setup_fee
     *  - cancel_fee
     *  - currency
     * @param string $package_id The Blesta package ID to add pricing to
     */
    protected function addPackagePricing($pricing, $package_id)
    {
        // Add package pricing
        $pricing_id = null;
        foreach ($pricing as $price) {
            if (version_compare(BLESTA_VERSION, '3.1.0-b1', '>=')) {
                $vars = $price;
                $vars['company_id'] = Configure::get('Blesta.company_id');

                $this->local->insert('pricings', $vars);
                $pricing_id = $this->local->lastInsertId();

                $this->local->insert(
                    'package_pricing',
                    [
                        'package_id' => $package_id,
                        'pricing_id' => $pricing_id
                    ]
                );
                $pricing_id = $this->local->lastInsertId();
            } else {
                $vars = $price;
                $vars['package_id'] = $package_id;
                $this->local->insert('package_pricing', $vars);
                $pricing_id = $this->local->lastInsertId();
            }
        }

        return $pricing_id;
    }

    /**
     * Adds package meta for the given package.
     *
     * @param array $package An array of package info including:
     *  - id The ID of the package in WHMCS
     *  - * misc package fields
     * @param array $mapping An array of module mapping config settings
     */
    protected function addPackageMeta($package, $mapping)
    {
        // Add package meta
        if (isset($mapping['package_meta'])) {
            foreach ($mapping['package_meta'] as $meta) {
                $vars = (array) $meta;
                $vars['package_id'] = $this->mappings['packages'][$package['id']];
                $vars['value'] = null;

                if (is_object($meta->value)) {
                    if (isset($meta->value->package)) {
                        $meta_key = strtolower($meta->value->package);
                        if (array_key_exists($meta_key, $package)) {
                            $vars['value'] = $package[$meta_key];
                            if ($meta_key == 'password') {
                                $vars['value'] = $this->decryptData($package[$meta_key]);
                            }
                        }
                    }
                } else {
                    $vars['value'] = $meta->value;
                }

                if (isset($meta->callback)) {
                    $vars['value'] = call_user_func_array($meta->callback, [$vars['value'], (array)$package]);
                }

                if ($vars['serialized'] == 1) {
                    $vars['value'] = serialize($vars['value']);
                }
                if ($vars['encrypted'] == 1) {
                    $vars['value'] = $this->ModuleManager->systemEncrypt($vars['value']);
                }

                $this->local->insert('package_meta', $vars, ['package_id', 'key', 'value', 'serialized', 'encrypted']);
            }
        }
    }

    /**
     * Add coupon
     *
     * @param array An array of key/value pairs
     * @param mixed $remote_id The ID of this items on the remote server
     * @return mixed
     */
    public function addCoupon(array $vars, $remote_id = null)
    {
        if (!isset($this->Coupons)) {
            Loader::loadModels($this, ['Coupons']);
        }

        $result = $this->Coupons->add($vars);
        if (($errors = $this->Coupons->errors())) {
            $this->Input->setErrors($errors);
        }

        if ($remote_id !== null && $result) {
            $this->mappings['coupons'][$remote_id] = $result;
        }

        return $result;
    }

    /**
     * Add service
     *
     * @param array An array of key/value pairs
     * @param mixed $remote_id The ID of this items on the remote server
     * @return mixed
     */
    public function addService(array $vars, $remote_id = null)
    {
        if (!isset($this->Services)) {
            Loader::loadModels($this, ['Services']);
        }

        $result = $this->Services->add($vars);
        if (($errors = $this->Services->errors())) {
            $this->Input->setErrors($errors);
        }

        if ($remote_id !== null && $result) {
            $this->mappings['services'][$remote_id] = $result;
        }

        return $result;
    }

    /**
     * Add support department
     *
     * @param array An array of key/value pairs
     * @param mixed $remote_id The ID of this items on the remote server
     * @return mixed
     */
    public function addSupportDepartment(array $vars, $remote_id = null)
    {
        if (!isset($this->SupportManagerDepartments)) {
            Loader::loadModels($this, ['SupportManager.SupportManagerDepartments']);
        }

        $result = $this->SupportManagerDepartments->add($vars);
        if (($errors = $this->SupportManagerDepartments->errors())) {
            $this->Input->setErrors($errors);
        }

        if ($remote_id !== null && $result) {
            $this->mappings['support_departments'][$remote_id] = $result->id;
        }

        return $result;
    }

    /**
     * Add support ticket
     *
     * @param array An array of key/value pairs
     * @param mixed $remote_id The ID of this items on the remote server
     * @return mixed
     */
    public function addSupportTicket(array $vars, $remote_id = null)
    {
        if (!isset($this->SupportManagerTickets)) {
            Loader::loadModels($this, ['SupportManager.SupportManagerTickets']);
        }

        $result = $this->SupportManagerTickets->add($vars);
        if (($errors = $this->SupportManagerTickets->errors())) {
            $this->Input->setErrors($errors);
        }

        if ($remote_id !== null && $result) {
            $this->mappings['support_tickets'][$remote_id] = $result;
        }

        return $result;
    }

    /**
     * Returns the module mapping file for the given module, or for the none module if module does not exist.
     *
     * @param string $module The module
     * @param string $module_type The module type ('server' or 'registrar')
     * @return array An array of mapping data
     */
    protected function getModuleMapping($module, $module_type = 'server')
    {
        return array('module' => '');
    }

    /**
     * Returns the gateway mapping file for the given gateway, or null if gateway does not exist.
     *
     * @param string $gateway The gateway
     * @param string $gateway_type The gateway type ('merchant' or 'nonmerchant')
     * @return array An array of mapping data
     */
    protected function getGatewayMapping($gateway, $gateway_type = 'nonmerchant')
    {
        return null;
    }

    /**
     * Installs the module row.
     *
     * @param array $row An array of key/value pairs, including but not limited to:
     *  - type The module name in the system being imported from
     *  - id The row ID in the system being imported from
     * @param string $module_type The type of module ('server' or 'registrar')
     * @return int The module row ID installed (also saved in mappings['modules'] property)
     */
    protected function installModuleRow($row, $module_type = 'server')
    {
        // Load required models
        if (!isset($this->ModuleManager)) {
            Loader::loadModels($this, ['ModuleManager']);
        }

        // Get module mapping
        $mapping = $this->getModuleMapping($row['type'], $module_type);

        // Install module
        $module_id = $this->installModule($row['type'], $mapping);

        if (!$module_id) {
            return null;
        }

        // Install the module row
        $this->local->insert('module_rows', ['module_id' => $module_id]);
        $module_row_id = $this->local->lastInsertId();

        $this->mappings['module_rows'][$row['type']][$row['id']] = $module_row_id;

        // Install the module row meta fields
        if (isset($mapping['module_row_meta'])) {
            foreach ($mapping['module_row_meta'] as $meta_row) {
                $vars = (array) $meta_row;
                $vars['value'] = $this->parseModuleRowValue($meta_row->value, $row);
                $vars['module_row_id'] = $module_row_id;

                if (empty($vars['value']) && isset($meta_row->alternate_value)) {
                    $vars['value'] = $this->parseModuleRowValue($meta_row->alternate_value, $row);
                }

                // Meta row callback
                if (isset($meta_row->callback)) {
                    $vars['value'] = call_user_func_array($meta_row->callback, [$vars['value'], (array)$row]);
                }

                // Serialize value
                if ($vars['serialized'] == 1 || is_array($vars['value'])) {
                    $vars['value'] = serialize($vars['value']);
                }

                // Encrypt value
                if ($vars['encrypted'] == 1) {
                    $vars['value'] = $this->ModuleManager->systemEncrypt($vars['value']);
                }

                $this->local->insert(
                    'module_row_meta',
                    $vars,
                    ['module_row_id', 'key', 'value', 'serialized', 'encrypted']
                );
            }
        }

        return $module_row_id;
    }

    /**
     * Uses a meta row value to pull out the appropriate value from a module row
     *
     * @param mixed $meta_row_value The meta row value containing the field to check
     * @param array $row The module row from which to pull a value
     * @return string The value from the module row
     */
    private function parseModuleRowValue($meta_row_value, $row)
    {
        $parsed_value = null;
        if (is_array($meta_row_value)) {
            $parsed_value = [];
            foreach ($meta_row_value as $value) {
                // Get value
                if (is_object($value)) {
                    if (isset($value->module) && array_key_exists(strtolower($value->module), $row)) {
                        $parsed_value[] = $row[strtolower($value->module)];
                    }
                } else {
                    $parsed_value[] = $value;
                }
            }
        } else {
            // Get value
            if (is_object($meta_row_value)) {
                if (isset($meta_row_value->module)
                    && array_key_exists(strtolower($meta_row_value->module), $row)
                ) {
                    $parsed_value = $row[strtolower($meta_row_value->module)];
                }
            } else {
                $parsed_value = $meta_row_value;
            }
        }

        return $parsed_value;
    }

    /**
     * Installs the given module and returns the module ID, if already installed simply returns the module ID.
     *
     * @param string $module The module name in the system being imported from
     * @param array An array of mapping fields for this particular module
     *  (optional, will automatically load if not given)
     * @param null|mixed $mapping
     * @return int The ID of the module in Blesta
     */
    protected function installModule($module, $mapping = null)
    {
        // Load required models
        if (!isset($this->ModuleManager)) {
            Loader::loadModels($this, ['ModuleManager']);
        }

        if ($mapping == null) {
            $mapping = $this->getModuleMapping($module);
        }

        // Return module if already mapped
        if (isset($this->mappings['modules'][$module])) {
            return $this->mappings['modules'][$module];
        }

        // Check if module is already installed
        $mod = $this->local->select(['id'])->from('modules')->
            where('company_id', '=', Configure::get('Blesta.company_id'))->
            where('class', '=', $mapping['module'])->fetch();

        if ($mod) {
            $this->mappings['modules'][$module] = $mod->id;

            return $mod->id;
        }

        // Install the module since it does not already exist
        $module_id = null;
        try {
            $vars = [
                'company_id' => Configure::get('Blesta.company_id'),
                'class' => $mapping['module']
            ];
            $module_id = $this->ModuleManager->add($vars);
        } catch (Exception $e) {
            // Module couldn't be added
        }
        $this->mappings['modules'][$module] = (int) $module_id;

        return $module_id;
    }

    /**
     * Returns any input errors encountered
     *
     * @return array An array of input errors
     */
    public function errors()
    {
        return $this->Input->errors();
    }

    /**
     * Initializes a View object and returns it
     *
     * @param string $file The view file to load
     * @param string $view The view directory name to find the view file
     * @param string $view_path The path to the $view relative to the root web directory
     * @return View An instance of the View object
     */
    protected function makeView($file, $view = 'default', $view_path = null)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $view = new View($file, $view);

        if ($view_path !== null) {
            $view->setDefaultView($view_path);
        }
        return $view;
    }

    /**
     * Set debug data
     *
     * @param string $str The debug data
     */
    protected function debug($str)
    {
        static $set_buffering = false;

        if ($this->enable_debug) {
            if (!$set_buffering) {
                ini_set('output_buffering', 'off');
                ini_set('zlib.output_compression', false);
                @ob_end_flush();

                ini_set('implicit_flush', true);
                ob_implicit_flush(true);

                header('Content-type: text/plain');
                header('Cache-Control: no-cache');
                $set_buffering = true;
            }

            echo $str . "\n";

            @ob_flush();
            flush();
        }
    }

    /**
     * Start a timer for the given task
     *
     * @param string $task
     */
    protected function startTimer($task)
    {
        $this->task[$task] = ['start' => microtime(true), 'end' => 0, 'total' => 0];
    }

    /**
     * Pause a timer for the given task
     *
     * @param string $task
     */
    protected function pauseTimer($task)
    {
        $this->task[$task]['end'] = microtime(true);
        $this->task[$task]['total'] += ($this->task[$task]['end'] - $this->task[$task]['start']);
    }

    /**
     * Unpause a timer for the given task
     *
     * @param string $task
     */
    protected function unpauseTimer($task)
    {
        $this->task[$task]['start'] = microtime(true);
    }

    /**
     * End a timer for the given task, output to debug
     *
     * @param string $task
     */
    protected function endTimer($task)
    {
        if ($this->task[$task]['start'] > $this->task[$task]['end']) {
            $this->pauseTimer($task);
        }

        if ($this->enable_debug) {
            $this->debug($task . ' took: ' . round($this->task[$task]['total'], 4) . ' seconds');
        }
    }

    /**
     * Logs an exception trough Monolog and resets the local database connection
     *
     * @param Throwable $e The exception to log
     */
    protected function logException(Throwable $e)
    {
        if (isset($this->local)) {
            try {
                $this->local->reset();
            } catch (Throwable $s) {
                // Nothing to do
            }
        }

        if (method_exists($this, 'debug')) {
            $this->debug($e->getMessage());
        }

        $this->logger->error($e);
    }
}
