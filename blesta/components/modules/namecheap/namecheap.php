<?php
/**
 * Namecheap Module
 *
 * @package blesta
 * @subpackage blesta.components.modules.namecheap
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Namecheap extends RegistrarModule
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
        Language::loadLang('namecheap', null, dirname(__FILE__) . DS . 'language' . DS);

        Configure::load('namecheap', dirname(__FILE__) . DS . 'config' . DS);
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
        // Upgrade to 2.11.0
        if (version_compare($current_version, '2.11.0', '<')) {
            Cache::clearCache(
                'tlds_prices',
                Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'namecheap' . DS
            );
        }
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
        // Validate .fr TLD rules
        $fr_fields = Configure::get('Namecheap.domain_fields.fr');
        $rules = [
            'FRLegalType' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', array_keys($fr_fields['FRLegalType']['options'])],
                    'message' => Language::_('Namecheap.!error.FRLegalType.format', true)
                ]
            ],
            'FRRegistrantBirthDate' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isDate',
                    'message' => Language::_('Namecheap.!error.FRRegistrantBirthDate.format', true)
                ]
            ],
            'FRRegistrantBirthplace' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Namecheap.!error.FRRegistrantBirthplace.format', true)
                ]
            ],
            'FRRegistrantLegalId' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Namecheap.!error.FRRegistrantLegalId.format', true)
                ]
            ],
            'FRRegistrantTradeNumber' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Namecheap.!error.FRRegistrantTradeNumber.format', true)
                ]
            ],
            'FRRegistrantDunsNumber' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Namecheap.!error.FRRegistrantDunsNumber.format', true)
                ]
            ],
            'FRRegistrantLocalId' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Namecheap.!error.FRRegistrantLocalId.format', true)
                ]
            ],
            'FRRegistrantJoDateDec' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isDate',
                    'negate' => true,
                    'message' => Language::_('Namecheap.!error.FRRegistrantJoDateDec.format', true)
                ]
            ],
            'FRRegistrantJoDatePub' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isDate',
                    'message' => Language::_('Namecheap.!error.FRRegistrantJoDatePub.format', true)
                ]
            ],
            'FRRegistrantJoNumber' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Namecheap.!error.FRRegistrantJoNumber.format', true)
                ]
            ],
            'FRRegistrantJoPage' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Namecheap.!error.FRRegistrantJoPage.format', true)
                ]
            ]
        ];

        // Set required fields based on the selected .fr TLD legal type
        if (isset($vars['FRLegalType'])) {
            $individual_fields = ['FRRegistrantBirthDate', 'FRRegistrantBirthplace'];
            $company_fields = ['FRRegistrantLegalId', 'FRRegistrantTradeNumber', 'FRRegistrantDunsNumber',
                'FRRegistrantLocalId', 'FRRegistrantJoDateDec', 'FRRegistrantJoDatePub',
                'FRRegistrantJoNumber', 'FRRegistrantJoPage'
            ];

            if ($vars['FRLegalType'] == 'Individual') {
                foreach ($individual_fields as $field) {
                    unset($rules[$field]['format']['if_set']);
                }
                foreach ($company_fields as $field) {
                    unset($rules[$field]);
                }
            } elseif ($vars['FRLegalType'] == 'Company') {
                foreach ($company_fields as $field) {
                    unset($rules[$field]['format']['if_set']);
                }
                foreach ($individual_fields as $field) {
                    unset($rules[$field]);
                }
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
     *  and parent service has already been provisioned)
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

        if ($package->meta->type == 'domain') {
            if (array_key_exists('EPPCode', $vars)) {
                $input_fields = array_merge(Configure::get('Namecheap.transfer_fields'), ['Years' => true]);
            } else {
                if (isset($vars['DomainName'])) {
                    $tld = $this->getTld($vars['DomainName']);
                }

                $whois_fields = Configure::get('Namecheap.whois_fields');
                $input_fields = array_merge(
                    Configure::get('Namecheap.domain_fields'),
                    Configure::get('Namecheap.nameserver_fields'),
                    $whois_fields,
                    (array)Configure::get('Namecheap.domain_fields' . $tld),
                    ['Years' => true, 'Nameservers' => true]
                );
            }
        }

        if (isset($vars['use_module']) && $vars['use_module'] == 'true') {
            if ($package->meta->type == 'domain') {
                $vars['Years'] = 1;

                foreach ($package->pricing as $pricing) {
                    if ($pricing->id == $vars['pricing_id']) {
                        $vars['Years'] = $pricing->term;
                        break;
                    }
                }

                // Handle transfer
                if (isset($vars['transfer']) || isset($vars['EPPCode'])) {
                    $fields = array_intersect_key($vars, $input_fields);

                    $transfer = new NamecheapDomainsTransfer($api);
                    $response = $transfer->create($fields);
                    $this->processResponse($api, $response);

                    if ($this->Input->errors()) {
                        return;
                    }

                    return [
                        ['key' => 'DomainName', 'value' => $fields['DomainName'], 'encrypted' => 0],
                        ['key' => 'domain', 'value' => $fields['DomainName'], 'encrypted' => 0],
                    ];
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
                    if ($client) {
                        $contact_numbers = $this->Contacts->getNumbers($client->contact_id);
                    }

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
                                isset($contact_numbers[0]) ? $contact_numbers[0]->number : null,
                                $client->country
                            );
                        } elseif (strpos($key, 'EmailAddress') !== false) {
                            $vars[$key] = $client->email;
                        }
                    }

                    // Set custom nameservers as CSV
                    $nameservers = '';
                    for ($i = 1; $i <= 5; $i++) {
                        if (isset($vars['ns' . $i]) && $vars['ns' . $i] != '') {
                            $nameservers .= (empty($nameservers) ? '' : ',')  . $vars['ns' . $i];
                        }
                        unset($vars['ns' . $i]);
                    }

                    if (!empty($nameservers)) {
                        $vars['Nameservers'] = $nameservers;
                    }

                    if ($tld = '.asia') {
                        $vars['ASIACCLocality'] = $client->country;
                    }

                    $fields = array_intersect_key($vars, $input_fields);

                    $domains = new NamecheapDomains($api);
                    $response = $domains->create($fields);
                    $this->processResponse($api, $response);

                    if ($this->Input->errors()) {
                        return;
                    }

                    return [
                        ['key' => 'DomainName', 'value' => $vars['DomainName'], 'encrypted' => 0],
                        ['key' => 'domain', 'value' => $vars['DomainName'], 'encrypted' => 0]
                    ];
                }
            } else {
                #
                # TODO: Create SSL cert (NamecheapSsl->create())
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

            $vars = [
                'DomainName' => $fields->DomainName,
                'Years' => 1
            ];

            foreach ($package->pricing as $pricing) {
                if ($pricing->id == $service->pricing_id) {
                    $vars['Years'] = $pricing->term;
                    break;
                }
            }

            $domains = new NamecheapDomains($api);
            $response = $domains->renew($vars);
            $this->processResponse($api, $response);
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
     * @param array $vars An array of post data submitted to or on the manage module page
     *  (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the manager module page
     */
    public function manageModule($module, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'namecheap' . DS);

        #
        #
        # TODO: add tab to check status of all transfers: NamecheapDomainsTransfer->getList()
        #
        #

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set('module', $module);

        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the add module row page
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'namecheap' . DS);

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
     * @param array $vars An array of post data submitted to or on the edit module
     *  row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the edit module row page
     */
    public function manageEditRow($module_row, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('edit_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'namecheap' . DS);

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
            'domain' => Language::_('Namecheap.package_fields.type_domain', true),
            //'ssl' => Language::_("Namecheap.package_fields.type_ssl", true)
        ];

        // Set type of package
        $type = $fields->label(Language::_('Namecheap.package_fields.type', true), 'namecheap_type');
        $type->attach(
            $fields->fieldSelect(
                'meta[type]',
                $types,
                (isset($vars->meta['type']) ? $vars->meta['type'] : null),
                ['id' => 'namecheap_type']
            )
        );
        $fields->setField($type);

        // Set all TLD checkboxes
        $tld_options = $fields->label(Language::_('Namecheap.package_fields.tld_options', true));

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
            $type = $fields->label(Language::_('Namecheap.package_fields.ns' . $i, true), 'namecheap_ns' . $i);
            $type->attach(
                $fields->fieldText(
                    'meta[ns][]',
                    (isset($vars->meta['ns'][$i-1]) ? $vars->meta['ns'][$i-1] : null),
                    ['id' => 'namecheap_ns' . $i]
                )
            );
            $fields->setField($type);
        }

        $fields->setHtml("
			<script type=\"text/javascript\">
				$(document).ready(function() {
					toggleTldOptions($('#namecheap_type').val());

					// Re-fetch module options to pull cPanel packages and ACLs
					$('#namecheap_type').change(function() {
						toggleTldOptions($(this).val());
					});

					function toggleTldOptions(type) {
						if (type == 'ssl')
							$('.namecheap_tlds').hide();
						else
							$('.namecheap_tlds').show();
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
     * @return ModuleFields A ModuleFields object, containg the fields to render as well as
     *  any additional HTML markup to include
     */
    public function getAdminAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Handle universal domain name
        if (isset($vars->domain)) {
            $vars->DomainName = $vars->domain;
        }

        if ($package->meta->type == 'domain') {
            // Set default name servers
            if (!isset($vars->ns1) && isset($package->meta->ns)) {
                $i=1;
                foreach ($package->meta->ns as $ns) {
                    $vars->{'ns' . $i++} = $ns;
                }
            }

            // Handle transfer request
            if (isset($vars->transfer) || isset($vars->EPPCode)) {
                return $this->arrayToModuleFields(Configure::get('Namecheap.transfer_fields'), null, $vars);
            } else {
                // Handle domain registration
                #
                # TODO: Select TLD, then display additional fields
                #

                $module_fields = $this->arrayToModuleFields(
                    array_merge(
                        Configure::get('Namecheap.domain_fields'),
                        Configure::get('Namecheap.nameserver_fields')
                    ),
                    null,
                    $vars
                );

                // Build the domain fields
                $fields = $this->buildDomainModuleFields($vars);
                if ($fields) {
                    $module_fields = $fields;
                }
            }
        }

        return (isset($module_fields) ? $module_fields : new ModuleFields());
    }

    /**
     * Returns all fields to display to a client attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as well
     *  as any additional HTML markup to include
     */
    public function getClientAddFields($package, $vars = null)
    {

        // Handle universal domain name
        if (isset($vars->domain)) {
            $vars->DomainName = $vars->domain;
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
            if (isset($vars->transfer) || isset($vars->EPPCode)) {
                $fields = Configure::get('Namecheap.transfer_fields');

                // We should already have the domain name don't make editable
                $fields['DomainName']['type'] = 'hidden';
                $fields['DomainName']['label'] = null;

                return $this->arrayToModuleFields($fields, null, $vars);
            } else {
                // Handle domain registration
                $fields = array_merge(
                    Configure::get('Namecheap.nameserver_fields'),
                    Configure::get('Namecheap.domain_fields')
                );

                // We should already have the domain name don't make editable
                $fields['DomainName']['type'] = 'hidden';
                $fields['DomainName']['label'] = null;

                $module_fields = $this->arrayToModuleFields($fields, null, $vars);

                // Build the domain fields
                $domain_fields = $this->buildDomainModuleFields($vars, true);
                if ($domain_fields) {
                    $module_fields = $domain_fields;
                }
            }
        }

        // Determine whether this is an AJAX request
        return (isset($module_fields) ? $module_fields : new ModuleFields());
    }

    /**
     * Builds and returns the module fields for domain registration
     *
     * @param stdClass $vars An stdClass object representing the input vars
     * @param $client True if rendering the client view, or false for the admin (optional, default false)
     * return mixed The module fields for this service, or false if none could be created
     */
    private function buildDomainModuleFields($vars, $client = false)
    {
        if (isset($vars->DomainName)) {
            $tld = $this->getTld($vars->DomainName);

            $extension_fields = Configure::get('Namecheap.domain_fields' . $tld);
            if ($extension_fields) {
                // Set the fields
                if ($client) {
                    $fields = array_merge(
                        Configure::get('Namecheap.nameserver_fields'),
                        Configure::get('Namecheap.domain_fields'),
                        $extension_fields
                    );
                } else {
                    $fields = array_merge(
                        Configure::get('Namecheap.domain_fields'),
                        Configure::get('Namecheap.nameserver_fields'),
                        $extension_fields
                    );
                }

                if ($client) {
                    // We should already have the domain name don't make editable
                    $fields['DomainName']['type'] = 'hidden';
                    $fields['DomainName']['label'] = null;
                }

                // Build the module fields
                $module_fields = new ModuleFields();

                // Allow AJAX requests
                $ajax = $module_fields->fieldHidden('allow_ajax', 'true', ['id'=>'namecheap_allow_ajax']);
                $module_fields->setField($ajax);
                $please_select = ['' => Language::_('AppController.select.please', true)];

                foreach ($fields as $key => $field) {
                    // Build the field
                    $label = $module_fields->label((isset($field['label']) ? $field['label'] : ''), $key);

                    $type = null;
                    if ($field['type'] == 'text') {
                        $type = $module_fields->fieldText(
                            $key,
                            (isset($vars->{$key}) ? $vars->{$key} : ''),
                            ['id' => $key]
                        );
                    } elseif ($field['type'] == 'select') {
                        $type = $module_fields->fieldSelect(
                            $key,
                            (isset($field['options']) ? $please_select + $field['options'] : $please_select),
                            (isset($vars->{$key}) ? $vars->{$key} : ''),
                            ['id' => $key]
                        );
                    } elseif ($field['type'] == 'hidden') {
                        $type = $module_fields->fieldHidden(
                            $key,
                            (isset($vars->{$key}) ? $vars->{$key} : ''),
                            ['id' => $key]
                        );
                    }

                    // Include a tooltip if set
                    if (!empty($field['tooltip'])) {
                        $label->attach($module_fields->tooltip($field['tooltip']));
                    }

                    if ($type) {
                        $label->attach($type);
                        $module_fields->setField($label);
                    }
                }
            }
        }

        return (isset($module_fields) ? $module_fields : false);
    }

    /**
     * Returns all fields to display to an admin attempting to edit a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as well
     *  as any additional HTML markup to include
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
                'tabWhois' => Language::_('Namecheap.tab_whois.title', true),
                'tabNameservers' => Language::_('Namecheap.tab_nameservers.title', true),
                'tabSettings' => Language::_('Namecheap.tab_settings.title', true)
            ];
        } else {
            #
            # TODO: Activate (NamecheapSsl->active()) & uploads CSR, set field data, etc.
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
                'tabClientWhois' => Language::_('Namecheap.tab_whois.title', true),
                'tabClientNameservers' => Language::_('Namecheap.tab_nameservers.title', true),
                'tabClientSettings' => Language::_('Namecheap.tab_settings.title', true)
            ];
        } else {
            #
            # TODO: Activate (NamecheapSsl->active()) & uploads CSR, set field data, etc.
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
        $domains = new NamecheapDomains($api);

        $vars = new stdClass();

        $whois_fields = Configure::get('Namecheap.whois_fields');
        $fields = $this->serviceFieldsToObject($service->fields);

        if (!empty($post)) {
            $post = array_merge(['DomainName' => $fields->DomainName], array_intersect_key($post, $whois_fields));

            $response = $domains->setContacts($post);
            $this->processResponse($api, $response);

            $vars = (object)$post;
        } else {
            $response = $domains->getContacts(['DomainName' => $fields->DomainName]);

            if ($response->status() == 'OK') {
                $data = $response->response()->DomainContactsResult;

                // Format fields
                foreach ($data as $section => $element) {
                    foreach ($element as $name => $value) {
                        // Value must be a string
                        if (!is_scalar($value)) {
                            $value = '';
                        }
                        $vars->{$section.$name} = $value;
                    }
                }
            }
        }

        $this->view->set('vars', $vars);
        $this->view->set('fields', $this->arrayToModuleFields($whois_fields, null, $vars)->getFields());
        $this->view->set('sections', ['Registrant', 'Admin', 'Tech', 'AuxBilling']);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'namecheap' . DS);
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
        $dns = new NamecheapDomainsDns($api);

        $fields = $this->serviceFieldsToObject($service->fields);

        $tld = $this->getTld($fields->DomainName);
        $sld = substr($fields->DomainName, 0, -strlen($tld));

        if (!empty($post)) {
            $response = $dns->setCustom(
                ['SLD' => $sld, 'TLD' => ltrim($tld, '.'), 'Nameservers' => implode(',', $post['ns'])]
            );
            $this->processResponse($api, $response);

            $vars = (object)$post;
        } else {
            $response = $dns->getList(['SLD' => $sld, 'TLD' => ltrim($tld, '.')])->response();

            if (isset($response->DomainDNSGetListResult)) {
                $vars->ns = [];
                foreach ($response->DomainDNSGetListResult->Nameserver as $ns) {
                    $vars->ns[] = $ns;
                }
            }
        }

        $this->view->set('vars', $vars);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'namecheap' . DS);
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
        $domains = new NamecheapDomains($api);
        $transfer = new NamecheapDomainsTransfer($api);

        $fields = $this->serviceFieldsToObject($service->fields);

        if (!empty($post)) {
            if (isset($post['registrar_lock'])) {
                $response = $domains->setRegistrarLock([
                    'DomainName' => $fields->DomainName,
                    'LockAction' => $post['registrar_lock'] == 'true' ? 'LOCK' : 'UNLOCK'
                ]);
                $this->processResponse($api, $response);
            }

            if (isset($post['request_epp'])) {
                $response = $transfer->getEpp(['DomainName' => $fields->DomainName]);
                $this->processResponse($api, $response);
            }

            $vars = (object)$post;
        } else {
            $response = $domains->getRegistrarLock(['DomainName' => $fields->DomainName])->response();

            if (isset($response->DomainGetRegistrarLockResult)) {
                $vars->registrar_lock = $response->DomainGetRegistrarLockResult->{'@attributes'}->RegistrarLockStatus;
            }
        }

        $this->view->set('vars', $vars);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'namecheap' . DS);
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

        $domains = new NamecheapDomains($api);
        $result = $domains->check(['DomainList' => $domain]);

        if ($result->status() != 'OK') {
            return false;
        }
        $response = $result->response();

        return strtolower($response->DomainCheckResult->{'@attributes'}->Available) == 'true';
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

        $domains = new NamecheapDomains($api);
        $result = $domains->getInfo(['DomainName' => $domain]);
        $this->processResponse($api, $result);

        if ($result->status() != 'OK') {
            return false;
        }
        $response = $result->response();

        return isset($response->DomainGetInfoResult->DomainDetails->ExpiredDate)
            ? $this->Date->format(
                $format,
                $response->DomainGetInfoResult->DomainDetails->ExpiredDate
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
                if ($service_field->key == 'DomainName') {
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
        // Fetch the TLDs results from the cache, if they exist
        $cache = Cache::fetchCache(
            'tlds',
            Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'namecheap' . DS
        );

        if ($cache) {
            return unserialize(base64_decode($cache));
        }

        $tlds = [];
        try {
            $response = $this->getRawTldData($module_row_id);
            $categories = ['renew', 'register', 'transfer'];
            foreach ($response->UserGetPricingResult->ProductType->ProductCategory as $productCategory) {
                $category = $productCategory->{'@attributes'}->Name;

                // Skip if this is not a registration, transfer, or renewal price
                if (!in_array($category, $categories)) {
                    continue;
                }

                foreach ($productCategory->Product as $product) {
                    // Get the TLD
                    $tld = '.' . $product->{'@attributes'}->Name;
                    if (!isset($tlds[$tld])) {
                        $tlds[] = $tld;
                    }
                }
            }

            if (count($tlds) > 0 && Configure::get('Caching.on') && is_writable(CACHEDIR)) {
                try {
                    Cache::writeCache(
                        'tlds',
                        base64_encode(serialize($tlds)),
                        strtotime(Configure::get('Blesta.cache_length')) - time(),
                        Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'namecheap' . DS
                    );
                } catch (Exception $e) {
                    // Write to cache failed, so disable caching
                    Configure::set('Caching.on', false);
                }
            }
        } catch (Exception $e) {
            // Do nothing
        }

        if (empty($tlds)) {
            $tlds = Configure::get('Namecheap.tlds');
        }

        return $tlds;
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
     */
    public function getFilteredTldPricing($module_row_id = null, $filters = [])
    {
        $response = $this->getRawTldData($module_row_id);

        Loader::loadModels($this, ['Currencies']);

        // Get all currencies
        $currencies = [];
        $company_currencies = $this->Currencies->getAll(Configure::get('Blesta.company_id'));
        foreach ($company_currencies as $currency) {
            $currencies[$currency->code] = $currency;
        }

        // Set the prices For each yearly term and currency
        $tld_yearly_prices = [];
        $categories = ['renew', 'register', 'transfer'];
        foreach ($response->UserGetPricingResult->ProductType->ProductCategory as $productCategory) {
            $category = $productCategory->{'@attributes'}->Name;

            // Skip if this is not a registration, transfer, or renewal price
            if (!in_array($category, $categories)) {
                continue;
            }

            foreach ($productCategory->Product as $product) {
                // Get the TLD
                $tld = '.' . $product->{'@attributes'}->Name;
                if (!isset($tld_yearly_prices[$tld])) {
                    $tld_yearly_prices[$tld] = [];
                }

                // Filter by 'tlds'
                if (isset($filters['tlds']) && !in_array($tld, $filters['tlds'])) {
                    continue;
                }

                foreach ($product->Price as $price) {
                    $price_attributes = $price->{'@attributes'} ?? $price;
                    $currency = $price_attributes->Currency ?? 'USD';
                    $duration = $price_attributes->Duration;
                    if ($price_attributes->DurationType != 'YEAR' || $price_attributes->Currency != "USD") {
                        continue;
                    }

                    // Filter by 'terms'
                    if (isset($filters['terms']) && !in_array($duration, $filters['terms'])) {
                        continue;
                    }

                    // Validate if the reseller currency exists in the company
                    if (!isset($currencies[$currency])) {
                        $this->Input->setErrors(
                            [
                                'currency' => [
                                    'not_exists' => Language::_('Namecheap.!error.currency.not_exists', true)
                                ]
                            ]
                        );

                        return;
                    }

                    foreach ($currencies as $currency) {
                        // Filter by 'currencies'
                        if (isset($filters['currencies']) && !in_array($currency->code, $filters['currencies'])) {
                            continue;
                        }

                        if (!isset($tld_yearly_prices[$tld][$currency->code])) {
                            $tld_yearly_prices[$tld][$currency->code] = [];
                        }

                        if (!isset($tld_yearly_prices[$tld][$currency->code][$duration])) {
                            $tld_yearly_prices[$tld][$currency->code][$duration] = [
                                'register' => null,
                                'transfer' => null,
                                'renew' => null
                            ];
                        }

                        // Set the price for this currency, period, and category
                        $tld_yearly_prices[$tld][$currency->code][$duration][$category] = $this->Currencies->convert(
                            $price_attributes->Price * $duration,
                            $price_attributes->Currency ?? 'USD',
                            $currency->code,
                            Configure::get('Blesta.company_id')
                        );
                    }
                }
            }
        }

        return $tld_yearly_prices;
    }

    /**
     * Get a list of raw TLD pricing data
     *
     * @param int $module_row_id The ID of the module row to fetch for the current module
     *
     * @return stdClass The response from the cache or API
     */
    private function getRawTldData($module_row_id = null)
    {
        // Fetch the TLDs results from the cache, if they exist
        $cache = Cache::fetchCache(
            'tlds_prices',
            Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'namecheap' . DS
        );

        if ($cache) {
            $response = unserialize(base64_decode($cache));
        }

        if (!isset($response)) {
            // Load the API
            $row = $this->getModuleRow($module_row_id);
            $row = !empty($row) ? $row : $this->getModuleRows()[0];
            if (!$row) {
                return [];
            }

            $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');

            // Load the Users API command group
            $users = new NamecheapUsers($api);

            // Get the TLD pricing from NameCheap
            $result = $users->getPricing(['ProductType' => 'DOMAIN', 'ProductCategory' => 'DOMAINS']);
            $this->processResponse($api, $result);

            $response = $result->response();

            // Save pricing in cache
            if (Configure::get('Caching.on') && is_writable(CACHEDIR)) {
                try {
                    Cache::writeCache(
                        'tlds_prices',
                        base64_encode(serialize($response)),
                        strtotime(Configure::get('Blesta.cache_length')) - time(),
                        Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'namecheap' . DS
                    );
                } catch (Exception $e) {
                    // Write to cache failed, so disable caching
                    Configure::set('Caching.on', false);
                }
            }
        }

        return $response;
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
                    'message' => Language::_('Namecheap.!error.user.valid', true)
                ]
            ],
            'key' => [
                'valid' => [
                    'last' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Namecheap.!error.key.valid', true)
                ],
                'valid_connection' => [
                    'rule' => [
                        [$this, 'validateConnection'],
                        $vars['user'],
                        isset($vars['sandbox']) ? $vars['sandbox'] : 'false'
                    ],
                    'message' => Language::_('Namecheap.!error.key.valid_connection', true)
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
        $domains = new NamecheapDomains($api);
        return $domains->check(['DomainList' => 'namecheap.com'])->status() == 'OK';
    }

    /**
     * Initializes the NamecheapApi and returns an instance of that object
     *
     * @param string $user The user to connect as
     * @param string $key The key to use when connecting
     * @param bool $sandbox Whether or not to process in sandbox mode (for testing)
     * @param string $username The username to execute an API command using
     * @return NamecheapApi The NamecheapApi instance
     */
    private function getApi($user, $key, $sandbox, $username = null)
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'namecheap_api.php');

        return new NamecheapApi($user, $key, $sandbox, $username);
    }

    /**
     * Process API response, setting an errors, and logging the request
     *
     * @param NamecheapApi $api The namecheap API object
     * @param NamecheapResponse $response The namecheap API response object
     */
    private function processResponse(NamecheapApi $api, NamecheapResponse $response)
    {
        $this->logRequest($api, $response);

        // Set errors, if any
        if ($response->status() != 'OK') {
            $errors = isset($response->errors()->Error) ? $response->errors()->Error : [];
            $this->Input->setErrors(['errors' => (array)$errors]);
        }
    }

    /**
     * Logs the API request
     *
     * @param NamecheapApi $api The namecheap API object
     * @param NamecheapResponse $response The namecheap API response object
     */
    private function logRequest(NamecheapApi $api, NamecheapResponse $response)
    {
        #
        # TODO: Filter out user/key
        #

        $last_request = $api->lastRequest();
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
