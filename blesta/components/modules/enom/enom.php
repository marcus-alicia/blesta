<?php
/**
 * Enom Module
 *
 * @package blesta
 * @subpackage blesta.components.modules.enom
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Enom extends RegistrarModule
{
    /**
     * Initializes the module
     */
    public function __construct()
    {
        // Load configuration required by this module
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('enom', null, dirname(__FILE__) . DS . 'language' . DS);

        Configure::load('enom', dirname(__FILE__) . DS . 'config' . DS);
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
        // Upgrade to 2.10.0 or 2.11.2
        if (version_compare($current_version, '2.10.0', '<') || version_compare($current_version, '2.11.2', '<')) {
            Cache::clearCache(
                'tlds_prices',
                Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'enom' . DS
            );
        }
    }

    /**
     * Get a list of the TLD prices
     *
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return array A list of all TLDs and their pricing
     *    [tld => [currency => [year# => ['register' => price, 'transfer' => price, 'renew' => price]]]]
     */
    public function getTldPricing($module_row_id = null)
    {
        return $this->getFilteredTldPricing($module_row_id);
    }

    /**
     * Get a filtered list of the TLD prices
     *
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @param array $filters A list of criteria by which to filter fetched pricings including but not limited to:
     *
     *  - tlds A list of tlds for which to fetch pricings
     *  - currencies A list of currencies for which to fetch pricings
     *  - terms A list of terms for which to fetch pricings
     * @return array A list of all TLDs and their pricing
     *    [tld => [currency => [year# => ['register' => price, 'transfer' => price, 'renew' => price]]]]
     * @see https://api.enom.com/docs/pe_getproductprice
     */
    public function getFilteredTldPricing($module_row_id = null, $filters = [])
    {
        Loader::loadModels($this, ['Currencies']);

        // Get all TLDs
        $tlds = $this->getTlds();

        // Filter TLDs
        if (!empty($filters['tlds']) && is_array($filters['tlds'])) {
            $tlds = [];
            foreach ($filters['tlds'] as $tld) {
                $tlds[] = '.' . ltrim($tld, '.');
            }
            unset($tld);
        }

        // Fetch the TLDs results from the cache, if they exist
        $cache = Cache::fetchCache(
            'tlds_prices',
            Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'enom' . DS
        );

        if ($cache) {
            $pricing = unserialize(base64_decode($cache));
        } else {
            $pricing = [];
        }


        // Fetch pricing from the registrar
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');
        $all = new EnomAll($api);

        // Get pricing
        foreach ($tlds as $tld) {
            if (isset($pricing[$tld])) {
                continue;
            }

            $pricing[$tld] = [];

            foreach (range(1, 10) as $years) {
                $register_response = $all->PE_GetProductPrice(['tld' => trim($tld, '.'), 'ProductType' => 10, 'Years' => $years]);
                $this->logRequest($api, $register_response);
                if ($register_response->status() != 'OK') {
                    continue;
                }
                $pricing[$tld][$years]['register'] = $register_response->response();

                $renew_response = $all->PE_GetProductPrice(['tld' => trim($tld, '.'), 'ProductType' => 16, 'Years' => $years]);
                if ($renew_response->status() != 'OK') {
                    continue;
                }
                $pricing[$tld][$years]['renew'] = $renew_response->response();

                $transfer_response = $all->PE_GetProductPrice(['tld' => trim($tld, '.'), 'ProductType' => 19, 'Years' => $years]);
                if ($transfer_response->status() != 'OK') {
                    continue;
                }
                $pricing[$tld][$years]['transfer'] = $transfer_response->response();
            }
        }
        unset($tld);

        // Save pricing in cache
        if (Configure::get('Caching.on') && is_writable(CACHEDIR)) {
            try {
                Cache::writeCache(
                    'tlds_prices',
                    base64_encode(serialize($pricing)),
                    strtotime(Configure::get('Blesta.cache_length')) - time(),
                    Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'enom' . DS
                );
            } catch (Exception $e) {
                // Write to cache failed, so disable caching
                Configure::set('Caching.on', false);
            }
        }

        // Save the TLDs results to the cache
        $tld_yearly_prices = [];
        if (!empty($pricing)) {
            $currencies = $this->Currencies->getAll(Configure::get('Blesta.company_id'));

            foreach ($pricing as $tld => $tld_data) {
                // Filter by 'tlds'
                if (isset($filters['tlds']) && !in_array($tld, $filters['tlds'])) {
                    continue;
                }

                foreach ($currencies as $currency) {
                    // Filter by 'currencies'
                    if (isset($filters['currencies']) && !in_array($currency->code, $filters['currencies'])) {
                        continue;
                    }

                    foreach (range(1, 10) as $years) {
                        // Filter by 'terms'
                        if (isset($filters['terms']) && !in_array($years, $filters['terms'])) {
                            continue;
                        }

                        $register_price = $this->Currencies->convert(
                            ($tld_data[$years]['register']->productprice->price ?? 0) * $years,
                            'USD',
                            $currency->code,
                            Configure::get('Blesta.company_id')
                        );
                        $transfer_price = $this->Currencies->convert(
                            ($tld_data[$years]['transfer']->productprice->price ?? 0) * $years,
                            'USD',
                            $currency->code,
                            Configure::get('Blesta.company_id')
                        );
                        $renewal_price = $this->Currencies->convert(
                            ($tld_data[$years]['renew']->productprice->price ?? 0) * $years,
                            'USD',
                            $currency->code,
                            Configure::get('Blesta.company_id')
                        );

                        $tld_yearly_prices[$tld][$currency->code][$years] = [
                            'register' => $register_price,
                            'transfer' => $transfer_price,
                            'renew' => $renewal_price,
                        ];
                    }
                }
            }
        }

        return $tld_yearly_prices;
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
        return true;
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
        $row = $this->getModuleRow($package->module_row);
        $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');

        #
        # TODO: Handle validation checks
        #

        $tld = null;
        $input_fields = [];

        if (isset($vars['domain'])) {
            $tld = $this->getTld($vars['domain']);
        }

        if ($package->meta->type == 'domain') {
            if (array_key_exists('transfer_key', $vars)) {
                $input_fields = array_merge(
                    Configure::get('Enom.transfer_fields'),
                    [
                        'OrderType' => true, 'UseContacts' => true, 'DomainCount' => true,
                        'NumYears' => true, 'SLD1' => true, 'TLD1' => true,
                        'AuthInfo1' => true, 'UseDNS' => true
                    ]
                );
            } else {
                $whois_fields = Configure::get('Enom.whois_fields');
                $input_fields = array_merge(
                    Configure::get('Enom.domain_fields'),
                    $whois_fields,
                    (array)Configure::get('Enom.domain_fields' . $tld),
                    [
                        'NumYears' => true,
                        'SLD' => true,
                        'TLD' => true,
                        'SLD1' => true,
                        'TLD1' => true,
                        'DomainCount' => 1,
                        'UseDNS' => true
                    ],
                    (array)Configure::get('Enom.nameserver_fields')
                );
            }
        }

        if (isset($vars['use_module']) && $vars['use_module'] == 'true') {
            if ($package->meta->type == 'domain') {
                $vars['NumYears'] = 1;
                $vars['SLD'] = substr($vars['domain'], 0, -strlen($tld));
                $vars['TLD'] = ltrim($tld, '.');

                foreach ($package->pricing as $pricing) {
                    if ($pricing->id == $vars['pricing_id']) {
                        $vars['NumYears'] = $pricing->term;
                        break;
                    }
                }

                // Handle transfer
                if (isset($vars['transfer_key'])) {
                    $vars['OrderType'] = 'Autoverification';
                    $vars['DomainCount'] = '1';
                    $vars['UseContacts'] = '1';
                    $vars['SLD1'] = $vars['SLD'];
                    $vars['TLD1'] = $vars['TLD'];
                    $vars['AuthInfo1'] = $vars['transfer_key'];

                    $fields = array_intersect_key($vars, $input_fields);

                    $command = new EnomAll($api);
                    $response = $command->TP_CreateOrder($fields);
                    $this->processResponse($api, $response);

                    if ($this->Input->errors()) {
                        return;
                    }

                    return [['key' => 'domain', 'value' => $fields['domain'], 'encrypted' => 0]];
                } else {
                    // Handle registration
                    // Set all whois info from client ($vars['client_id'])
                    if (!isset($this->Clients)) {
                        Loader::loadModels($this, ['Clients']);
                    }
                    if (!isset($this->Contacts)) {
                        Loader::loadModels($this, ['Contacts']);
                    }

                    $client = $this->Clients->get($vars['client_id']);
                    $numbers = $this->Contacts->getNumbers($client->contact_id, 'phone');

                    foreach ($whois_fields as $key => $value) {
                        if (strpos($key, 'FirstName') !== false) {
                            $vars[$key] = $client->first_name;
                        } elseif (strpos($key, 'LastName') !== false) {
                            $vars[$key] = $client->last_name;
                        } elseif (strpos($key, 'Address1') !== false) {
                            $vars[$key] = $client->address1;
                        } elseif (strpos($key, 'Address2') !== false) {
                            $vars[$key] = $client->address2;
                        } elseif (strpos($key, 'City') !== false) {
                            $vars[$key] = $client->city;
                        } elseif (strpos($key, 'StateProvince') !== false) {
                            $vars[$key] = $client->state;
                        } elseif (strpos($key, 'PostalCode') !== false) {
                            $vars[$key] = $client->zip;
                        } elseif (strpos($key, 'Country') !== false) {
                            $vars[$key] = $client->country;
                        } elseif (strpos($key, 'Phone') !== false) {
                            $vars[$key] = $this->formatPhone(
                                isset($numbers[0]) ? $numbers[0]->number : null,
                                $client->country
                            );
                        } elseif (strpos($key, 'EmailAddress') !== false) {
                            $vars[$key] = $client->email;
                        }
                    }

                    $vars['UseDNS'] = 'default';
                    for ($i=1; $i<=5; $i++) {
                        if (!isset($vars['ns' . $i]) || $vars['ns' . $i] == '') {
                            unset($vars['ns' . $i]);
                        } else {
                            unset($vars['UseDNS']);
                        }
                    }

                    if ($tld = '.asia') {
                        $vars['asia_cclocality'] = $client->country;
                    }

                    $fields = array_intersect_key($vars, $input_fields);

                    $command = new EnomAll($api);
                    $response = $command->Purchase($fields);
                    $this->processResponse($api, $response);

                    if ($this->Input->errors()) {
                        return;
                    }

                    return [['key' => 'domain', 'value' => $vars['domain'], 'encrypted' => 0]];
                }
            } else {
                #
                # TODO: Create SSL cert
                #
            }
        }

        $meta = [];
        $fields = array_intersect_key($vars, $input_fields);
        foreach ($fields as $key => $value) {
            $meta[] = [
                'key' => $key,
                'value' => $value,
                'encrypted' => 0
            ];
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
    public function editService($package, $service, array $vars = [], $parent_package = null, $parent_service = null)
    {
        return null; // All this handled by admin/client tabs instead
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
        return null; // Nothing to do
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
        return null; // Nothing to do
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
        return null; // Nothing to do
    }

    /**
     * Allows the module to perform an action when the service is ready to renew.
     * Sets Input errors on failure, preventing the service from renewing.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being renewed (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function renewService($package, $service, $parent_package = null, $parent_service = null)
    {
        $row = $this->getModuleRow($package->module_row);
        $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');

        // Renew domain
        if ($package->meta->type == 'domain') {
            $fields = $this->serviceFieldsToObject($service->fields);

            $tld = trim($this->getTld($fields->domain), '.');
            $sld = trim(substr($fields->domain, 0, -strlen($tld)), '.');

            $vars = [
                'NumYears' => 1,
                'tld' => $tld,
                'sld' => $sld
            ];

            foreach ($package->pricing as $pricing) {
                if ($pricing->id == $service->pricing_id) {
                    $vars['NumYears'] = $pricing->term;
                    break;
                }
            }

            // Renew the domain
            $command = new EnomAll($api);
            $response = $command->Extend($vars);
            $this->processResponse($api, $response);

            // If the domain renewal failed, it may be expired, so attempt to re-activate and renew it instead
            if ($this->Input->errors()) {
                $vars = [
                    'NumYears' => $vars['NumYears'],
                    'DomainName' => $fields->domain
                ];
                $response = $command->UpdateExpiredDomains($vars);
                $this->processResponse($api, $response);
            }
        } else {
            #
            # TODO: SSL Cert: Set cancelation date of service?
            #
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
        return null; // Nothing to do
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
        $meta = [];
        if (isset($vars['meta']) && is_array($vars['meta'])) {
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
        $meta = [];
        if (isset($vars['meta']) && is_array($vars['meta'])) {
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
     * @param array $vars An array of post data submitted to or on the manage
     *  module page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the manager module page
     */
    public function manageModule($module, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'enom' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'enom' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        // Set unspecified checkboxes
        if (!empty($vars)) {
            if (empty($vars['sandbox'])) {
                $vars['sandbox'] = 'false';
            }
        }

        $this->view->set('vars', (object)$vars);
        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the edit module row page
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'enom' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        if (empty($vars)) {
            $vars = $module_row->meta;
        } else {
            // Set unspecified checkboxes
            if (empty($vars['sandbox'])) {
                $vars['sandbox'] = 'false';
            }
        }

        $this->view->set('vars', (object)$vars);
        return $this->view->fetch();
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
        $meta_fields = ['user', 'key', 'sandbox'];
        $encrypted_fields = ['key'];

        // Set unspecified checkboxes
        if (empty($vars['sandbox'])) {
            $vars['sandbox'] = 'false';
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
     * Deletes the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being deleted.
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     */
    public function deleteModuleRow($module_row)
    {
    }

    /**
     * Returns all fields used when adding/editing a package, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getPackageFields($vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        $types = [
            'domain' => Language::_('Enom.package_fields.type_domain', true),
            #
            # TODO: Add support for SSL certs
            #'ssl' => Language::_("Enom.package_fields.type_ssl", true)
            #
        ];

        // Set type of package
        $type = $fields->label(Language::_('Enom.package_fields.type', true), 'enom_type');
        $type->attach(
            $fields->fieldSelect(
                'meta[type]',
                $types,
                (isset($vars->meta['type']) ? $vars->meta['type'] : null),
                ['id' => 'enom_type']
            )
        );
        $fields->setField($type);

        // Set all TLD checkboxes
        $tld_options = $fields->label(Language::_('Enom.package_fields.tld_options', true));

        $tlds = $this->getTlds();
        sort($tlds);
        foreach ($tlds as $tld) {
            $tld_label = $fields->label($tld, 'tld_' . $tld);
            $tld_options->attach(
                $fields->fieldCheckbox(
                    'meta[tlds][]',
                    $tld,
                    (isset($vars->meta['tlds']) && in_array($tld, $vars->meta['tlds'])),
                    ['id' => 'tld_' . $tld],
                    $tld_label
                )
            );
        }
        $fields->setField($tld_options);

        // Set nameservers
        for ($i=1; $i<=5; $i++) {
            $type = $fields->label(Language::_('Enom.package_fields.ns' . $i, true), 'enom_ns' . $i);
            $type->attach(
                $fields->fieldText(
                    'meta[ns][]',
                    (isset($vars->meta['ns'][$i-1]) ? $vars->meta['ns'][$i-1] : null),
                    ['id' => 'enom_ns' . $i]
                )
            );
            $fields->setField($type);
        }

        $fields->setHtml("
			<script type=\"text/javascript\">
				$(document).ready(function() {
					toggleTldOptions($('#enom_type').val());

					// Re-fetch module options to pull cPanel packages and ACLs
					$('#enom_type').change(function() {
						toggleTldOptions($(this).val());
					});

					function toggleTldOptions(type) {
						if (type == 'ssl')
							$('.enom_tlds').hide();
						else
							$('.enom_tlds').show();
					}
				});
			</script>
		");

        return $fields;
    }

    /**
     * Returns all fields to display to an admin attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as
     *  well as any additional HTML markup to include
     */
    public function getAdminAddFields($package, $vars = null)
    {
        if ($package->meta->type == 'domain') {
            // Set default name servers
            if (!isset($vars->ns1) && isset($package->meta->ns)) {
                $i=1;
                foreach ($package->meta->ns as $ns) {
                    $vars->{'ns' . $i++} = $ns;
                }
            }

            // Handle transfer request
            if (isset($vars->transfer) || isset($vars->transfer_key)) {
                return $this->arrayToModuleFields(Configure::get('Enom.transfer_fields'), null, $vars);
            } else {
                // Handle domain registration
                #
                # TODO: Select TLD, then display additional fields
                #

                $module_fields = $this->arrayToModuleFields(
                    array_merge(Configure::get('Enom.domain_fields'), Configure::get('Enom.nameserver_fields')),
                    null,
                    $vars
                );

                if (isset($vars->domain)) {
                    $tld = $this->getTld($vars->domain);

                    $extension_fields = Configure::get('Enom.domain_fields' . $tld);
                    if ($extension_fields) {
                        $module_fields = $this->arrayToModuleFields($extension_fields, $module_fields, $vars);
                    }
                }

                return $module_fields;
            }
        } else {
            return new ModuleFields();
        }
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

        // Handle universal domain name
        if (isset($vars->domain)) {
            $vars->domain = $vars->domain;
        }

        if ($package->meta->type == 'domain') {
            // Set default name servers
            if (!isset($vars->ns) && isset($package->meta->ns)) {
                $i=1;
                foreach ($package->meta->ns as $ns) {
                    $vars->{'ns' . $i++} = $ns;
                }
            }

            // Handle transfer request
            if (isset($vars->transfer) || isset($vars->transfer_key)) {
                $fields = Configure::get('Enom.transfer_fields');

                // We should already have the domain name don't make editable
                $fields['domain']['type'] = 'hidden';
                $fields['domain']['label'] = null;

                return $this->arrayToModuleFields($fields, null, $vars);
            } else {
                // Handle domain registration
                $fields = array_merge(Configure::get('Enom.nameserver_fields'), Configure::get('Enom.domain_fields'));

                // We should already have the domain name don't make editable
                $fields['domain']['type'] = 'hidden';
                $fields['domain']['label'] = null;

                $module_fields = $this->arrayToModuleFields($fields, null, $vars);

                if (isset($vars->domain)) {
                    $tld = $this->getTld($vars->domain);

                    $extension_fields = Configure::get('Enom.domain_fields' . $tld);
                    if ($extension_fields) {
                        $module_fields = $this->arrayToModuleFields($extension_fields, $module_fields, $vars);
                    }
                }

                return $module_fields;
            }
        } else {
            return new ModuleFields();
        }
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
        if ($package->meta->type == 'domain') {
            return new ModuleFields();
        } else {
            return new ModuleFields();
        }
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
        return '';
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
        return '';
    }

    /**
     * Returns all tabs to display to an admin when managing a service whose
     * package uses this module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title.
     *  Example: array('methodName' => "Title", 'methodName2' => "Title2")
     */
    public function getAdminTabs($package)
    {
        if ($package->meta->type == 'domain') {
            return [
                'tabWhois' => Language::_('Enom.tab_whois.title', true),
                'tabNameservers' => Language::_('Enom.tab_nameservers.title', true),
                'tabSettings' => Language::_('Enom.tab_settings.title', true)
            ];
        } else {
            #
            # TODO: Handle SSL certs
            #
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
        if ($package->meta->type == 'domain') {
            return [
                'tabClientWhois' => Language::_('Enom.tab_whois.title', true),
                'tabClientNameservers' => Language::_('Enom.tab_nameservers.title', true),
                'tabClientSettings' => Language::_('Enom.tab_settings.title', true)
            ];
        } else {
            #
            # TODO: Handle SSL certs
            #
        }
    }

    /**
     * Admin Whois tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabWhois($package, $service, array $get = null, array $post = null, array $files = null)
    {
        return $this->manageWhois('tab_whois', $package, $service, $get, $post, $files);
    }

    /**
     * Client Whois tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientWhois($package, $service, array $get = null, array $post = null, array $files = null)
    {
        return $this->manageWhois('tab_client_whois', $package, $service, $get, $post, $files);
    }

    /**
     * Admin Nameservers tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabNameservers($package, $service, array $get = null, array $post = null, array $files = null)
    {
        return $this->manageNameservers('tab_nameservers', $package, $service, $get, $post, $files);
    }

    /**
     * Admin Nameservers tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientNameservers($package, $service, array $get = null, array $post = null, array $files = null)
    {
        return $this->manageNameservers('tab_client_nameservers', $package, $service, $get, $post, $files);
    }

    /**
     * Admin Settings tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabSettings($package, $service, array $get = null, array $post = null, array $files = null)
    {
        return $this->manageSettings('tab_settings', $package, $service, $get, $post, $files);
    }

    /**
     * Client Settings tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientSettings($package, $service, array $get = null, array $post = null, array $files = null)
    {
        return $this->manageSettings('tab_client_settings', $package, $service, $get, $post, $files);
    }

    /**
     * Handle updating whois information
     *
     * @param string $view The view to use
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    private function manageWhois($view, $package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View($view, 'default');
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $row = $this->getModuleRow($package->module_row);
        $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');
        $command = new EnomAll($api);

        $vars = new stdClass();

        $whois_fields = Configure::get('Enom.whois_fields');
        $fields = $this->serviceFieldsToObject($service->fields);
        $whois_sections = Configure::get('Enom.whois_sections');

        $tld = trim($this->getTld($fields->domain), '.');
        $sld = trim(substr($fields->domain, 0, -strlen($tld)), '.');

        if (!empty($post)) {
            $post = array_merge(['sld' => $sld, 'tld' => $tld], array_intersect_key($post, $whois_fields));
            $response = $command->contacts($post);
            $this->processResponse($api, $response);

            $vars = (object)$post;
        } else {
            $response = $command->getContacts(['sld' => $sld, 'tld' => $tld]);
            $this->processResponse($api, $response);

            if ($response->status() == 'OK') {
                $data = $response->response()->GetContacts;

                foreach ($whois_sections as $section) {
                    if (isset($data->{$section})) {
                        foreach ($data->{$section} as $name => $value) {
                            $vars->{$name} = (is_scalar($value) ? $value : '');
                        }
                    }
                }
            }
        }

        $this->view->set('vars', $vars);
        $this->view->set('fields', $this->arrayToModuleFields($whois_fields, null, $vars)->getFields());
        $this->view->set('sections', ['Registrant', 'Admin', 'Tech', 'AuxBilling']);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'enom' . DS);
        return $this->view->fetch();
    }

    /**
     * Handle updating nameserver information
     *
     * @param string $view The view to use
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    private function manageNameservers(
        $view,
        $package,
        $service,
        array $get = null,
        array $post = null,
        array $files = null
    ) {
        $this->view = new View($view, 'default');
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $vars = new stdClass();

        $row = $this->getModuleRow($package->module_row);
        $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');
        $command = new EnomAll($api);

        $fields = $this->serviceFieldsToObject($service->fields);

        $tld = trim($this->getTld($fields->domain), '.');
        $sld = trim(substr($fields->domain, 0, -strlen($tld)), '.');

        if (!empty($post)) {
            $vars = $post;
            // Default to using default nameservers
            $vars['usedns'] = 'Default';
            foreach ($vars['ns'] as $i => $ns) {
                if ($ns != '') {
                    $vars['ns' . ($i+1)] = $ns;
                    unset($vars['usedns']);
                }
            }
            unset($vars['ns']);

            $response = $command->modifyNs(array_merge(['sld' => $sld, 'tld' => $tld], $vars));
            $this->processResponse($api, $response);

            $vars = (object)$post;
        } else {
            $response = $command->getDns(['sld' => $sld, 'tld' => $tld]);
            $this->processResponse($api, $response);

            if ($response->status() == 'OK') {
                $data = $response->response();
                if (isset($data->dns)) {
                    foreach ((array)$data->dns as $ns) {
                        $vars->ns[] = $ns;
                    }
                }
            }
        }

        $this->view->set('vars', $vars);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'enom' . DS);
        return $this->view->fetch();
    }

    /**
     * Handle updating settings
     *
     * @param string $view The view to use
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    private function manageSettings(
        $view,
        $package,
        $service,
        array $get = null,
        array $post = null,
        array $files = null
    ) {
        $this->view = new View($view, 'default');
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $vars = new stdClass();

        $row = $this->getModuleRow($package->module_row);
        $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');
        $command = new EnomAll($api);

        $fields = $this->serviceFieldsToObject($service->fields);
        $tld = trim($this->getTld($fields->domain), '.');
        $sld = trim(substr($fields->domain, 0, -strlen($tld)), '.');

        if (!empty($post)) {
            if (isset($post['registrar_lock'])) {
                $response = $command->setRegLock([
                    'sld' => $sld,
                    'tld' => $tld,
                    'UnlockRegistrar' => $post['registrar_lock'] == 'true' ? '0' : '1'
                ]);
                $this->processResponse($api, $response);
            }

            if (isset($post['request_epp'])) {
                $response = $command->synchAuthInfo([
                    'sld' => $sld,
                    'tld' => $tld,
                    'EmailEPP' => 'true',
                    'RunSynchAutoInfo' => 'true'
                ]);
                $this->processResponse($api, $response);
            }

            $vars = (object)$post;
        } else {
            $response = $command->getRegLock(['sld' => $sld, 'tld' => $tld]);
            $this->processResponse($api, $response);

            if ($response->status() == 'OK') {
                $data = $response->response();
                $vars->registrar_lock = $data->{'reg-lock'} == '1' ? 'true' : 'false';
            }
        }

        $this->view->set('vars', $vars);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'enom' . DS);
        return $this->view->fetch();
    }

    /**
     * Verifies that the provided domain name is available
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the domain is available, false otherwise
     */
    public function checkAvailability($domain, $module_row_id = null)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');
        $all = new EnomAll($api);

        $tld = trim($this->getTld($domain), '.');
        $sld = trim(substr($domain, 0, -strlen($tld)), '.');

        $response = $all->check(['sld' => $sld, 'tld' => $tld]);

        if ($response->status() != 'OK') {
            return false;
        }
        $response = $response->response();

        return $response->RRPCode == 210;
    }

    /**
     * Gets the domain expiration date
     *
     * @param stdClass $service The service belonging to the domain to lookup
     * @param string $format The format to return the expiration date in
     * @return string The domain expiration date in UTC time in the given format
     * @see Services::get()
     */
    public function getExpirationDate($service, $format = 'Y-m-d H:i:s')
    {
        Loader::loadHelpers($this, ['Date']);

        $domain = $this->getServiceDomain($service);
        $module_row_id = $service->module_row_id ?? null;

        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');
        $all = new EnomAll($api);

        $tld = trim($this->getTld($domain), '.');
        $sld = trim(substr($domain, 0, -strlen($tld)), '.');

        $response = $all->getDomainInfo(['sld' => $sld, 'tld' => $tld]);

        if ($response->status() != 'OK') {
            return false;
        }

        $this->processResponse($api, $response);

        $response = $response->response();

        return isset($response->GetDomainInfo->status->expiration)
            ? $this->Date->format(
                $format,
                $response->GetDomainInfo->status->expiration
            )
            : false;
    }

    /**
     * Gets the domain name from the given service
     *
     * @param stdClass $service The service from which to extract the domain name
     * @return string The domain name associated with the service
     */
    public function getServiceDomain($service)
    {
        if (isset($service->fields)) {
            foreach ($service->fields as $service_field) {
                if ($service_field->key == 'domain') {
                    return $service_field->value;
                }
            }
        }

        return $this->getServiceName($service);
    }

    /**
     * Get a list of the TLDs supported by the registrar module
     *
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return array A list of all TLDs supported by the registrar module
     */
    public function getTlds($module_row_id = null)
    {
        return Configure::get('Enom.tlds');
    }

    /**
     * Builds and returns the rules required to add/edit a module row
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getRowRules(&$vars)
    {
        return [
            'user' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Enom.!error.user.valid', true)
                ]
            ],
            'key' => [
                'valid' => [
                    'last' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Enom.!error.key.valid', true)
                ],
                'valid_connection' => [
                    'rule' => [
                        [$this, 'validateConnection'],
                        $vars['user'],
                        isset($vars['sandbox']) ? $vars['sandbox'] : 'false'
                    ],
                    'message' => Language::_('Enom.!error.key.valid_connection', true)
                ]
            ]
        ];
    }

    /**
     * Validates that the given connection details are correct by attempting to check the availability of a domain
     *
     * @param string $key The API key
     * @param string $user The API user
     * @param string $sandbox "true" if this is a sandbox account, false otherwise
     * @return bool True if the connection details are valid, false otherwise
     */
    public function validateConnection($key, $user, $sandbox)
    {
        $api = $this->getApi($user, $key, $sandbox == 'true');
        $all = new EnomAll($api);

        return $all->check(['domainlist' => 'enom.com'])->status() == 'OK';
    }

    /**
     * Initializes the EnomApi and returns an instance of that object
     *
     * @param string $user The user to connect as
     * @param string $key The key to use when connecting
     * @param bool $sandbox Whether or not to process in sandbox mode (for testing)
     * @return EnomApi The EnomApi instance
     */
    private function getApi($user, $key, $sandbox)
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'enom_api.php');

        return new EnomApi($user, $key, $sandbox);
    }

    /**
     * Process API response, setting an errors, and logging the request
     *
     * @param EnomApi $api The enom API object
     * @param EnomResponse $response The enom API response object
     */
    private function processResponse(EnomApi $api, EnomResponse $response)
    {
        $this->logRequest($api, $response);

        // Set errors, if any
        if ($response->status() != 'OK') {
            $errors = $response->errors() ? $response->errors() : [];
            $this->Input->setErrors(['errors' => $errors]);
        }
    }

    /**
     * Logs the API request
     *
     * @param EnomApi $api The enom API object
     * @param EnomResponse $response The enom API response object
     */
    private function logRequest(EnomApi $api, EnomResponse $response)
    {
        $last_request = $api->lastRequest();
        $last_request['args']['pw'] = 'xxxx';

        $this->log($last_request['url'], serialize($last_request['args']), 'input', true);
        $this->log($last_request['url'], $response->raw(), 'output', $response->status() == 'OK');
    }

    /**
     * Returns the TLD of the given domain
     *
     * @param string $domain The domain to return the TLD from
     * @return string The TLD of the domain
     */
    private function getTld($domain)
    {
        $tlds = $this->getTlds();

        $domain = strtolower($domain);

        foreach ($tlds as $tld) {
            if (substr($domain, -strlen($tld)) == $tld) {
                return $tld;
            }
        }
        return strstr($domain, '.');
    }

    /**
     * Formats a phone number into +NNN.NNNNNNNNNN
     *
     * @param string $number The phone number
     * @param string $country The ISO 3166-1 alpha2 country code
     * @return string The number in +NNN.NNNNNNNNNN
     */
    private function formatPhone($number, $country)
    {
        if (!isset($this->Contacts)) {
            Loader::loadModels($this, ['Contacts']);
        }

        return $this->Contacts->intlNumber($number, $country, '.');
    }
}
