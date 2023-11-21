<?php

/**
 * Gateway manager. Handles installing/uninstalling and configuring payment
 * gateways.
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class GatewayManager extends AppModel
{
    /**
     * Initialize GatewayManager
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['gateway_manager']);
    }

    /**
     * Lists all installed gateways
     *
     * @param int $company_id The company ID
     * @param string $type The type of gateways to fetch ("merchant", "nonmerchant") set to null for both
     * @param string $order A key/value array where each key is a field and each value is the direction to sort
     * @param string $currency The ISO 4217 currency code
     * @return array An array of stdClass objects representing installed gateways
     */
    public function getAll($company_id, $type = null, array $order = ['gateways.name' => 'ASC'], $currency = null)
    {
        $fields = ['gateways.id', 'gateways.company_id', 'gateways.name',
            'gateways.class', 'gateways.type', 'gateways.version'];

        $this->Record->select($fields)->from('gateways')->
            where('company_id', '=', $company_id);

        if ($type != null) {
            $this->Record->where('gateways.type', '=', $type);
        }

        if ($currency != null) {
            $this->Record->on('gateway_currencies.currency', '=', $currency)->
                innerJoin('gateway_currencies', 'gateways.id', '=', 'gateway_currencies.gateway_id', false);
        }

        $gateways = $this->Record->order($order)->fetchAll();

        $num_gateways = count($gateways);
        // Load each installed gateway to fetch gateway info
        for ($i = 0; $i < $num_gateways; $i++) {
            // Set the installed version of the plugin
            $gateways[$i]->installed_version = $gateways[$i]->version;

            // Set the gateway fields from the gateway class
            $gateway = $this->setGatewayFields($gateways[$i], $company_id);
            if ($gateway) {
                $gateways[$i] = $gateway;
            }
        }

        // Separate gateways by type
        if ($type == null) {
            $gways = [];
            for ($i = 0; $i < $num_gateways; $i++) {
                $gways[$gateways[$i]->type][] = $gateways[$i];
            }

            $gateways = $gways;
        }

        return $gateways;
    }

    /**
     * Fetches a single installed merchant gateway including meta data and currency association. Fetches
     * based on $company_id and optionally by specifying a specific gateway ID.
     *
     * @param int $company_id The ID of the company in use
     * @param int $currency The ISO 4217 currency code to process
     * @param int $gateway_id The gateway ID to verify is installed and active for the given currency
     * @param string $gateway_name The installed gateway's file name excluding any extension
     * @return mixed A stdClass object representing the installed gateway,
     *  false if no such gateway exists or is not installed
     */
    public function getInstalledMerchant($company_id, $currency = null, $gateway_id = null, $gateway_name = null)
    {
        $fields = [
            'gateways.id', 'gateways.company_id', 'gateways.name',
            'gateways.class', 'gateways.type', 'gateways.version'
        ];
        $gw = $this->Record->select($fields)->from('gateways')->
            where('gateways.type', '=', 'merchant')->
            where('gateways.company_id', '=', $company_id);

        if ($currency) {
            $this->Record->innerJoin('gateway_currencies', 'gateways.id', '=', 'gateway_currencies.gateway_id', false)->
                where('gateway_currencies.currency', '=', $currency);
        }
        if ($gateway_name) {
            $this->Record->where('gateways.class', '=', $gateway_name);
        }
        if ($gateway_id) {
            $this->Record->where('gateways.id', '=', $gateway_id);
        }

        $gw = $this->Record->fetch();

        if ($gw) {
            // Set gateway info
            $gw = $this->setGatewayInfo($gw, $company_id);
        }

        return $gw;
    }

    /**
     * Fetches the requested installed nonmerchant gateway including meta data and currency association. Fetches
     * based on company_id and gateway name.
     *
     * @param int $company_id The ID of the company in use
     * @param string $gateway_name The installed gateway's file name excluding any extension
     * @param int $gateway_id The gateway ID to verify is installed and active for the given currency
     * @param int $currency The ISO 4217 currency code to process
     * @return mixed A stdClass object representing the installed gateway,
     *  false if no such gateway exists or is not installed
     */
    public function getInstalledNonmerchant(
        $company_id,
        $gateway_name = null,
        $gateway_id = null,
        $currency = null
    ) {
        $fields = [
            'gateways.id', 'gateways.company_id', 'gateways.name',
            'gateways.class', 'gateways.type', 'gateways.version'
        ];
        $this->Record->select($fields)->from('gateways')->
            where('gateways.type', '=', 'nonmerchant')->
            where('gateways.company_id', '=', $company_id);

        if ($gateway_name) {
            $this->Record->where('gateways.class', '=', $gateway_name);
        }
        if ($gateway_id) {
            $this->Record->where('gateways.id', '=', $gateway_id);
        }
        if ($currency != null) {
            $this->Record->on('gateway_currencies.currency', '=', $currency)->
                innerJoin('gateway_currencies', 'gateways.id', '=', 'gateway_currencies.gateway_id', false);
        }

        $gw = $this->Record->fetch();
        if ($gw) {
            // Set gateway info
            $gw = $this->setGatewayInfo($gw, $company_id);
        }

        return $gw;
    }

    /**
     * Fetches all requested installed nonmerchant gateways including meta data
     * and currency association for each. Fetches based on company_id and currency.
     *
     * @param int $company_id The ID of the company in use
     * @param int $currency The ISO 4217 currency code to process
     * @return array An array of stdClass objects, each representing an installed gateway
     */
    public function getAllInstalledNonmerchant($company_id, $currency = null)
    {
        $fields = [
            'gateways.id', 'gateways.company_id', 'gateways.name',
            'gateways.class', 'gateways.type', 'gateways.version'
        ];
        $this->Record->select($fields)->from('gateways')->
            where('gateways.type', '=', 'nonmerchant')->
            where('gateways.company_id', '=', $company_id);

        if ($currency != null) {
            $this->Record->on('gateway_currencies.currency', '=', $currency)->
                innerJoin('gateway_currencies', 'gateways.id', '=', 'gateway_currencies.gateway_id', false);
        }

        $gateways = $this->Record->fetchAll();

        foreach ($gateways as &$gw) {
            // Set gateway info
            $gw = $this->setGatewayInfo($gw, $company_id);
        }

        return $gateways;
    }

    /**
     * Fetches all gateways installed in the system
     *
     * @return array An array of stdClass objects, each representing an installed gateway record
     */
    public function getInstalled()
    {
        $fields = ['id', 'company_id', 'name', 'class', 'version', 'type'];

        return $this->Record->select($fields)->from('gateways')->fetchAll();
    }

    /**
     * Fetches a single installed gateway including meta data and currency association
     *
     * @param int $gateway_id The ID of the gateway to fetch
     * @return mixed A stdClass object representing the installed gateway, false if no such gateway exists
     */
    public function get($gateway_id)
    {
        $fields = ['id', 'company_id', 'name', 'class', 'type', 'version'];
        $gw = $this->Record->select($fields)->from('gateways')->
            where('id', '=', $gateway_id)->fetch();

        if ($gw) {
            // Set gateway info
            $gw = $this->setGatewayInfo($gw, $gw->company_id);
        }

        return $gw;
    }

    /**
     * Fetches a gateway for a given company, or all gateways installed in the system for the given gateway class
     *
     * @param string $class The class name (in file_case)
     * @param int $company_id The ID of the company to fetch gateways for (optional, default null for all)
     * @return array An array of stdClass objects, each representing an installed gateway record
     */
    public function getByClass($class, $company_id = null)
    {
        $this->Record->select(['gateways.*'])
            ->from('gateways')
            ->where('class', '=', $class);

        if ($company_id !== null) {
            $this->Record->where('company_id', '=', $company_id);
        }

        $gateways = $this->Record->fetchAll();
        foreach ($gateways as $gateway) {
            // Set gateway info
            $gateway = $this->setGatewayInfo($gateway, $company_id);
        }

        return $gateways;
    }

    /**
     * Lists all available gateways (those that exist on the file system)
     *
     * @param string $type The type of gateways to look for ("merchant", "nonmerchant"). Set to null for both
     * @param int $company_id The ID of the company to check availability status for
     * @return array An array of stdClass objects representing available gateways
     */
    public function getAvailable($type = null, $company_id = null)
    {
        $gateways = [];

        $types = ['merchant', 'nonmerchant'];

        // Get the files/directories (default alphabetical order)
        $files = scandir(COMPONENTDIR . 'gateways');
        foreach ($files as $file) {
            // If the file is not a hidden file, and is a directory, accept it
            if (substr($file, 0, 1) != '.'
                && is_dir(COMPONENTDIR . 'gateways' . DS . $file)
                && in_array($file, $types)
            ) {
                // If type is specified only fetch gateways of that type
                if ($type != null && $file != $type) {
                    continue;
                }

                // Select only merchant or nonmerchant gateways
                $gateways[$file] = [];

                $i = 0;
                $gateway_files = scandir(COMPONENTDIR . 'gateways' . DS . $file);
                foreach ($gateway_files as $gateway_file) {
                    // If the file is not a hidden file, and is a directory, accept it
                    if (substr($gateway_file, 0, 1) != '.'
                        && is_dir(COMPONENTDIR . 'gateways' . DS . $file . DS . $gateway_file)
                    ) {
                        // Set the gateway fields from the gateway class
                        $gateway = $this->setGatewayFields(
                            (object)['class' => $gateway_file, 'type' => $file],
                            $company_id
                        );
                        if ($gateway) {
                            $gateways[$file][$i] = $gateway;
                            $i++;
                        }
                    }
                }
            }
        }

        if ($type == null) {
            ksort($gateways);
            return $gateways;
        }
        return (isset($gateways[$type]) ? $gateways[$type] : []);
    }

    /**
     * Checks whether the given gateway is installed for the specificed company
     *
     * @param string $class The gateway class (in file_case)
     * @param string $type The type of gateway (merchant, nonmerchant)
     * @param string $company_id The ID of the company to fetch for
     * @return bool True if the gateway is installed, false otherwise
     */
    public function isInstalled($class, $type, $company_id = null)
    {
        $this->Record->select(['gateways.id'])->from('gateways')->
            where('class', '=', $class)->where('type', '=', $type);

        if ($company_id) {
            $this->Record->where('company_id', '=', $company_id);
        }

        return (boolean) $this->Record->fetch();
    }

    /**
     * Adds the gateway to the system
     *
     * @param array $vars An array of gateway data including:
     *  - company_id The ID of the company the gateway belongs to
     *  - class The class name for this gateway (in /components/gateways/)
     *  - type The type of gateway ('merchant', 'nonmerchant', 'hybrid')
     * @return int The ID of the gateway installed, void on error
     */
    public function add(array $vars)
    {
        // Trigger the GatewayManager.addBefore event
        extract($this->executeAndParseEvent('GatewayManager.addBefore', ['vars' => $vars]));

        $gw = $this->loadGateway($vars['class'], $vars['type']);

        // Run the installation
        $gw->install();

        // Check for errors installing
        if (($errors = $gw->errors())) {
            $this->Input->setErrors($errors);
            return;
        }

        $vars['version'] = $gw->getVersion();
        $vars['name'] = $gw->getName();

        $rules = [
            'company_id' => [
                'valid' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('GatewayManager.!error.company_id.valid')
                ]
            ],
            'class' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('GatewayManager.!error.class.valid')
                ]
            ],
            'name' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('GatewayManager.!error.name.valid')
                ]
            ],
            'version' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('GatewayManager.!error.version.valid')
                ]
            ]
        ];

        if ($vars['type'] == 'merchant') {
            $rules = array_merge($rules, $this->getCurrencyRules($gw));
        }

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            $fields = ['company_id', 'name', 'class', 'version', 'type'];
            $this->Record->insert('gateways', $vars, $fields);

            $gateway_id = $this->Record->lastInsertId();

            // Trigger the GatewayManager.addAfter event
            $this->executeAndParseEvent('GatewayManager.addAfter', ['gateway_id' => $gateway_id, 'vars' => $vars]);

            return $gateway_id;
        }
    }

    /**
     * Updates the gateway installed on the system
     *
     * @param int $gateway_id The ID of the gateway to update
     * @param array $vars An array of gateway data including:
     *  - name The name of the gateway - optional
     *  - class The class name for this gateway (in /components/gateways/) - optional
     *  - type The type of gateway ('merchant', 'nonmerchant', 'hybrid') - optional
     *  - currencies A numerically indexed array of accepted currencies codes (ISO 4217) - optional
     *  - meta A key/value array of meta data
     */
    public function edit($gateway_id, array $vars)
    {
        // Trigger the GatewayManager.editBefore event
        extract($this->executeAndParseEvent(
            'GatewayManager.editBefore', ['gateway_id' => $gateway_id, 'vars' => $vars]
        ));

        $gateway = $this->get($gateway_id);

        // Load the gateway and edit the settings with the gateway
        $gw = $this->loadGateway($gateway->class, $gateway->type);

        $errors = false;

        // Verify the update with the gateway
        if (isset($vars['meta'])) {
            $vars['meta'] = $gw->editSettings($vars['meta']);

            // Convert meta data into a format used by GatewayManager::setMeta()
            // So, from key/value pairs to numerically indexed array with 'encrypted' field
            $meta = [];
            $encrypt_fields = $gw->encryptableFields();
            foreach ($vars['meta'] as $key => $value) {
                $encrypted = false;
                if (in_array($key, $encrypt_fields)) {
                    $encrypted = true;
                }

                $meta[] = ['key' => $key, 'value' => $value, 'encrypted' => ($encrypted ? 1 : 0)];
            }
            $vars['meta'] = $meta;
            unset($meta);

            // If any errors editing the settings with the gateway, set the error
            if (($errors = $gw->errors())) {
                $this->Input->setErrors($errors);
            }
        }

        // Update the gateway internally
        if (!$errors) {
            if ($gateway->type == 'merchant') {
                $this->Input->setRules($this->getCurrencyRules($gw, $gateway_id));
            }

            if ($this->Input->validates($vars)) {
                // Update the meta information
                $this->setMeta($gateway_id, $vars['meta']);

                // Update the currencies
                if (isset($vars['currencies'])) {
                    $this->setCurrencies($gateway_id, $vars['currencies']);
                }

                // Trigger the GatewayManager.editAfter event
                $this->executeAndParseEvent(
                    'GatewayManager.editAfter',
                    ['gateway_id' => $gateway_id, 'vars' => $vars, 'old_gateway' => $gateway]
                );
            }
        }
    }

    /**
     * Runs the gateway's upgrade method to upgrade the gateway to match that of the gateway's file version.
     * Sets errors in GatewayManager::errors() if any errors are set by the gateway's upgrade method.
     *
     * @param int $gateway_id The ID of the gateway to upgrade
     */
    public function upgrade($gateway_id)
    {
        $installed_gateway = $this->get($gateway_id);

        if (!$installed_gateway) {
            return;
        }

        $gateway = $this->loadGateway($installed_gateway->class, $installed_gateway->type);
        $file_version = $gateway->getVersion();

        // Execute the upgrade if the installed version doesn't match the file version
        if (version_compare($file_version, $installed_gateway->version, '!=')) {
            $gateway->upgrade($installed_gateway->version);

            if (($errors = $gateway->errors())) {
                $this->Input->setErrors($errors);
            } else {
                // Update all installed gateways to the given version
                $this->setVersion($installed_gateway->class, $file_version);
            }
        }
    }

    /**
     * Permanently and completely removes the gateway specified by $gateway_id
     *
     * @param int $gateway_id The ID of the gateway to permanently remove
     */
    public function delete($gateway_id)
    {
        // Trigger the GatewayManager.deleteBefore event
        extract($this->executeAndParseEvent('GatewayManager.deleteBefore', ['gateway_id' => $gateway_id]));

        $installed_gateway = $this->get($gateway_id);

        $this->Record->from('gateways')->where('id', '=', $gateway_id)->delete();
        $this->Record->from('gateway_currencies')->where('gateway_id', '=', $gateway_id)->delete();
        $this->Record->from('gateway_meta')->where('gateway_id', '=', $gateway_id)->delete();

        if ($installed_gateway) {
            // It's the responsibility of the gateway to remove any other tables or entries
            // it has created that are no longer relevant
            $gateway = $this->loadGateway($installed_gateway->class, $installed_gateway->type);

            $gateway->uninstall($gateway_id, !$this->isInstalled($installed_gateway->class, $installed_gateway->type));

            if (($errors = $gateway->errors())) {
                $this->Input->setErrors($errors);
            }

            // Trigger the GatewayManager.deleteAfter event
            $this->executeAndParseEvent(
                'GatewayManager.deleteAfter',
                ['gateway_id' => $gateway_id, 'old_gateway' => $installed_gateway]
            );
        }
    }

    /**
     * Verifies whether or not the given currency is already in use by a merchant gateway for this company
     *
     * @param string $currency The ISO 4217 currency code
     * @param int $company_id The company ID
     * @param int $gateway_id The gateway ID to exclude from this verification check (optional)
     * @return bool True if the currency is in use by a gateway, false otherwise
     */
    public function verifyCurrency($currency, $company_id, $gateway_id = null)
    {
        $this->Record->select('gateway_currencies.gateway_id')->from('gateways')->
            innerJoin('gateway_currencies', 'gateway_currencies.gateway_id', '=', 'gateways.id', false)->
            where('gateways.company_id', '=', $company_id)->
            where('gateway_currencies.currency', '=', $currency)->
            where('gateways.type', '=', 'merchant');

        // Exclude a gateway from the check
        if ($gateway_id != null) {
            $this->Record->where('gateways.id', '!=', $gateway_id);
        }

        $results = $this->Record->numResults();

        if ($results > 0) {
            return true;
        }
        return false;
    }

    /**
     * Verifies that the currency given exists for this gateway
     *
     * @param string $currency The currency code to verify
     * @param mixed $gateway A reference to the gateway object to verify the currency against
     * @return bool True if the currency exists for the gateway, false otherwise
     */
    public function currencyExists($currency, $gateway)
    {
        return (($currencies = $gateway->getCurrencies()) && in_array($currency, $currencies));
    }

    /**
     * Fetches all meta data associated with the given gateway
     *
     * @param int $gateway_id The ID of the gateway to fetch meta info for
     * @return array An array of stdClass objects representing meta data
     */
    private function getMeta($gateway_id)
    {
        $meta = $this->Record->select()->from('gateway_meta')->
            where('gateway_id', '=', $gateway_id)->fetchAll();

        // Decrypt values where necessary
        for ($i = 0, $total = count($meta); $i < $total; $i++) {
            if ($meta[$i]->encrypted) {
                $meta[$i]->value = $this->systemDecrypt($meta[$i]->value);
            }
        }

        return $meta;
    }

    /**
     * Sets meta data for the given gateway, overwriting all existing meta data
     *
     * @param int $gateway_id The ID of the gateway to store meta info for
     * @param array $meta A numerically indexed array of meta data containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    private function setMeta($gateway_id, array $meta)
    {
        // Remove existing meta data
        $this->Record->from('gateway_meta')->where('gateway_id', '=', $gateway_id)->delete();

        // Add new meta data
        $fields = ['gateway_id', 'key', 'value', 'encrypted'];
        for ($i = 0, $total = count($meta); $i < $total; $i++) {
            $vars = $meta[$i];
            $vars['gateway_id'] = $gateway_id;

            // Encrypt the value if set to be encrypted
            if (isset($vars['encrypted']) && $vars['encrypted'] == '1') {
                $vars['value'] = $this->systemEncrypt($vars['value']);
            }

            $this->Record->insert('gateway_meta', $vars, $fields);
        }
    }

    /**
     * Fetches all currencies configured to be accepted by the given gateway
     *
     * @param int $gateway_id The ID of the gateway to fetch currencies for
     * @return array An array of stdClass objects representing the currencies accepted for this gateway
     */
    private function getCurrencies($gateway_id)
    {
        return $this->Record->select(['gateway_id', 'currency'])->
            from('gateway_currencies')->where('gateway_id', '=', $gateway_id)->fetchAll();
    }

    /**
     * Sets accepted currencies for the given gateway
     *
     * @param int $gateway_id The ID of the gateway to set currencies for
     * @param array $currencies A numerically indexed array of accepted currencies for this gateway
     */
    private function setCurrencies($gateway_id, array $currencies)
    {

        // Remove all currencies for this gateway
        $this->Record->from('gateway_currencies')->where('gateway_id', '=', $gateway_id)->delete();

        // Re-add all the currencies
        $fields = ['gateway_id', 'currency'];
        for ($i = 0, $total = count($currencies); $i < $total; $i++) {
            $vars = ['gateway_id' => $gateway_id, 'currency' => $currencies[$i]];
            $this->Record->insert('gateway_currencies', $vars, $fields);
        }
    }

    /**
     * Updates all installed gateways with the version given
     *
     * @param string $class The class name of the gateway to update
     * @param string $version The version number to set for each gateway instance
     */
    private function setVersion($class, $version)
    {
        $this->Record->where('class', '=', $class)->update('gateways', ['version' => $version]);
    }

    /**
     * Instantiates the given gateway and returns its instance
     *
     * @param string $class The name of the class in file_case to load
     * @param string $type The type of gateway to load ("merchant" or "nonmerchant")
     * @return An instance of the gateway specified
     */
    private function loadGateway($class, $type)
    {

        // Load the gateway factory if not already loaded
        if (!isset($this->Gateways)) {
            Loader::loadComponents($this, ['Gateways']);
        }

        // Instantiate the gateway and return the instance
        return $this->Gateways->create($class, $type);
    }

    /**
     * Fetch information about the given gateway object
     *
     * @param object $gateway The gateway object to fetch info on
     * @param string $type The type of gateway ('merchant', 'nonmerchant')
     * @param int $company_id The ID of the company to fetch gateway info for
     */
    private function getGatewayInfo($gateway, $type, $company_id)
    {
        // Fetch supported interfaces
        $reflect = new ReflectionClass($gateway);
        $interfaces = array_keys($reflect->getInterfaces());
        $class = Loader::fromCamelCase($reflect->getName());

        $dirname = dirname($_SERVER['SCRIPT_NAME']);
        $info = [
            'type' => $type,
            'class' => $class,
            'name' => $gateway->getName(),
            'version' => $gateway->getVersion(),
            'authors' => $gateway->getAuthors(),
            'signup_url' => $gateway->getSignupUrl(),
            'interfaces' => $interfaces,
            'logo' => Router::makeURI(($dirname == DS ? '' : $dirname) . DS
                . str_replace(ROOTWEBDIR, '', COMPONENTDIR . 'gateways' . DS
                . $type . DS . $class . DS . $gateway->getLogo())),
            'installed' => $this->isInstalled($class, $type, $company_id),
            'currencies' => $gateway->getCurrencies(),
            'description' => $gateway->getDescription()
        ];

        unset($reflect);

        return $info;
    }

    /**
     * Sets additional info on the given gateway object
     *
     * @param stdClass $gateway An object representing the gateway from the database
     * @param int $company_id The ID of the company to fetch info from
     * @return stdClass The updated gateway object
     */
    private function setGatewayInfo(stdClass $gateway, $company_id)
    {
        // Fetch all meta data for this gateway
        $gateway->meta = $this->getMeta($gateway->id);
        // Fetch all currencies for this gateway
        $gateway->currencies = $this->getCurrencies($gateway->id);

        try {
            // Set gateway info
            $gw = $this->loadGateway($gateway->class, $gateway->type);
            $gateway->info = $this->getGatewayInfo($gw, $gateway->type, $company_id);
            $gateway->name = $gateway->info['name'];
        } catch (Exception $e) {
            // Do nothing
        }

        return $gateway;
    }

    /**
     * Sets additional info on the given gateway object
     *
     * @param stdClass $gateway An object representing the gateway from the database
     * @param int $company_id The ID of the company to fetch info from
     * @return stdClass The updated gateway object
     */
    private function setGatewayFields(stdClass $gateway, $company_id)
    {
        try {
            // Set gateway info
            $gw = $this->loadGateway($gateway->class, $gateway->type);
        } catch (Exception $e) {
            return false;
        }

        $info = $this->getGatewayInfo($gw, $gateway->type, $company_id);
        foreach ((array) $info as $key => $value) {
            $gateway->$key = $value;
        }

        return $gateway;
    }

    /**
     * Fetches the rules for adding/editing currencies for a gateway
     *
     * @param object $gw The gateway object
     * @param int $gateway_id The ID of the gateway (optional)
     * @return array The currency rules
     */
    private function getCurrencyRules($gw, $gateway_id = null)
    {
        $rules = [
            'currencies[]' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'currencyExists'], $gw],
                    'message' => $this->_('GatewayManager.!error.currencies[].exists')
                ],
                'in_use' => [
                    'if_set' => true,
                    'rule' => [[$this, 'verifyCurrency'], Configure::get('Blesta.company_id'), $gateway_id],
                    'negate' => true,
                    'message' => $this->_('GatewayManager.!error.currencies[].in_use')
                ]
            ]
        ];
        return $rules;
    }
}
