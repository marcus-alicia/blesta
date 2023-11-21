<?php

use Blesta\Core\Util\Validate\Server;

/**
 * OVH Domains Module
 *
 * @package blesta
 * @subpackage blesta.components.modules.ovh_domains
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OvhDomains extends RegistrarModule
{
    /**
     * @var array Supported OVH API endpoints
     */
    private $endpoints = [
        'ovh-eu' => 'https://eu.api.ovh.com/1.0',
        'ovh-ca' => 'https://ca.api.ovh.com/1.0'
    ];

    /**
     * Initializes the module
     */
    public function __construct()
    {
        // Load the language required by this module
        Language::loadLang('ovh_domains', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load module config
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        Configure::load('ovh_domains', dirname(__FILE__) . DS . 'config' . DS);
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ovh_domains' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ovh_domains' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set('endpoints', array_combine(array_keys($this->endpoints), array_keys($this->endpoints)));
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ovh_domains' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        if (empty($vars)) {
            $vars = $module_row->meta;
        }

        $this->view->set('endpoints', array_combine(array_keys($this->endpoints), array_keys($this->endpoints)));
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
        $meta_fields = ['application_key', 'secret_key', 'consumer_key', 'endpoint'];
        $encrypted_fields = [];

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
        $meta_fields = ['application_key', 'secret_key', 'consumer_key', 'endpoint'];
        $encrypted_fields = [];

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
     * Builds and returns the rules required to add/edit a module row (e.g. server).
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getRowRules(&$vars)
    {
        $rules = [
            'application_key' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('OvhDomains.!error.application_key.valid', true)
                ]
            ],
            'secret_key' => [
                'valid' => [
                    'rule' => [
                        [$this, 'validateConnection'],
                        $vars['application_key'],
                        $vars['consumer_key'],
                        $vars['endpoint']
                    ],
                    'message' => Language::_('OvhDomains.!error.secret_key.valid', true)
                ]
            ],
            'consumer_key' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('OvhDomains.!error.consumer_key.valid', true)
                ]
            ],
            'endpoint' => [
                'valid' => [
                    'rule' => ['array_key_exists', $this->endpoints ?? []],
                    'message' => Language::_('OvhDomains.!error.endpoint.valid', true)
                ]
            ]
        ];

        return $rules;
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

        return $validator->isDomain($host_name) || $validator->isIp($host_name) || empty($host_name);
    }

    /**
     * Validates that at least 2 name servers are set in the given array of name servers.
     *
     * @param array $name_servers An array of name servers
     * @return bool True if the array count is >= 2, false otherwise
     */
    public function validateNameServerCount($name_servers)
    {
        if (is_array($name_servers) && count($name_servers) >= 2) {
            return true;
        }

        return false;
    }

    /**
     * Validates that the nameservers given are formatted correctly.
     *
     * @param array $name_servers An array of name servers
     * @return bool True if every name server is formatted correctly, false otherwise
     */
    public function validateNameServers($name_servers)
    {
        if (is_array($name_servers)) {
            foreach ($name_servers as $name_server) {
                if (!$this->validateHostName($name_server)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validates whether or not the connection details are valid
     *
     * @param string $secret_key The OVH application secret key
     * @param string $application_key The OVH application key
     * @param string $consumer_key 'true' to use the Sandbox API
     * @param string $endpoint 'true' to use the Sandbox API
     * @return bool True if the connection is valid, false otherwise
     */
    public function validateConnection($secret_key, $application_key, $consumer_key, $endpoint)
    {
        $api = $this->getApi($application_key, $secret_key, $consumer_key, $endpoint);

        // Set request parameters
        $response = $this->apiRequest($api, '/me', $endpoint, [], 'get');

        return !empty($response->customerCode);
    }

    /**
     * Validates input data when attempting to add a package, returns the meta
     * data to save when adding a package. Performs any action required to add
     * the package on the remote server. Sets Input errors on failure,
     * preventing the package from being added.
     *
     * @param array An array of key/value pairs used to add the package
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *
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
            if (!isset($vars['meta'] )) {
                return [];
            }

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
     *
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
            if (!isset($vars['meta'] )) {
                return [];
            }

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
     * Builds and returns rules required to be validated when adding/editing a package.
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getPackageRules(array $vars)
    {
        // Validate the package fields
        $rules = [
            'epp_code' => [
                'valid' => [
                    'ifset' => true,
                    'rule' => ['in_array', [0, 1]],
                    'message' => Language::_('OvhDomains.!error.epp_code.valid', true)
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Returns all fields used when adding/editing a package, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to
     *  render as well as any additional HTML markup to include
     */
    public function getPackageFields($vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Set the EPP Code field
        $epp_code = $fields->label(Language::_('OvhDomains.package_fields.epp_code', true), 'ovh_domains_epp_code');
        $epp_code->attach(
            $fields->fieldCheckbox(
                'meta[epp_code]',
                'true',
                ($vars->meta['epp_code'] ?? null) == 'true',
                ['id' => 'ovh_domains_epp_code']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('OvhDomains.package_field.tooltip.epp_code', true));
        $epp_code->attach($tooltip);
        $fields->setField($epp_code);

        // Set all TLD checkboxes
        $tld_options = $fields->label(Language::_('OvhDomains.package_fields.tld_options', true));

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
        for ($i = 1; $i <= 5; $i++) {
            $type = $fields->label(Language::_('OvhDomains.package_fields.ns' . $i, true), 'ovh_ns' . $i);
            $type->attach(
                $fields->fieldText(
                    'meta[ns][]',
                    ($vars->meta['ns'][$i] ?? null),
                    ['id' => 'ovh_ns' . $i]
                )
            );
            $fields->setField($type);
        }

        return $fields;
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
        // Get module row
        $row = $this->getModuleRow();
        if (!$row) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('OvhDomains.!error.module_row.missing', true)]]
            );

            return;
        }

        // Set input fields
        if (array_key_exists('auth_info', $vars)) {
            $input_fields = array_merge(
                Configure::get('OvhDomains.transfer_fields'),
                Configure::get('OvhDomains.nameserver_fields')
            );
        } else {
            $input_fields = array_merge(
                Configure::get('OvhDomains.domain_fields'),
                Configure::get('OvhDomains.nameserver_fields')
            );
        }

        // Initialize API
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Get client and contact info
        if (!isset($this->Clients)) {
            Loader::loadModels($this, ['Clients']);
        }
        if (!isset($this->Contacts)) {
            Loader::loadModels($this, ['Contacts']);
        }

        $client = $this->Clients->get($vars['client_id'] ?? null);
        $numbers = $this->Contacts->getNumbers($client->contact_id, 'phone');

        // Build domain contacts
        $contacts = [];
        $contact = [
            'first_name' => $client->first_name ?? null,
            'last_name' => $client->last_name ?? null,
            'email' => $client->email ?? null,
            'address1' => $client->address1 ?? 'Any Street',
            'address2' => $client->address2 ?? null,
            'city' => $client->city ?? 'Miami',
            'state' => $client->state ?? 'FL',
            'zip' => $client->zip ?? '33000',
            'country' => $client->country ?? 'US',
            'phone' => $numbers[0]->number ?? '+1111111111'
        ];
        $contacts[] = array_merge($contact, ['external_id' => 'admin']);
        $contacts[] = array_merge($contact, ['external_id' => 'billing']);
        $contacts[] = array_merge($contact, ['external_id' => 'tech']);

        // Build nameservers
        $nameservers = [];
        for ($i = 1; $i <= 5; $i++) {
            if (!empty($vars['ns'][$i])) {
                $nameservers[] = $vars['ns'][$i];
            }
        }

        // Validate service fields
        $this->validateService($package, $vars);
        if ($this->Input->errors()) {
            return;
        }

        // Set registration term
        $vars['years'] = 1;
        foreach ($package->pricing as $pricing) {
            if ($pricing->id == ($vars['pricing_id'] ?? null)) {
                $vars['years'] = $pricing->term;
                break;
            }
        }

        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            if (isset($vars['transfer'])) {
                $domain = $this->transferDomain($vars['domain'], $row->id, $vars);
            } else {
                $domain = $this->registerDomain($vars['domain'], $row->id, $vars);
            }

            if ($domain) {
                if (!empty($nameservers)) {
                    $this->setDomainNameservers($vars['domain'], $row->id ?? null, $nameservers);
                }

                if (!empty($contacts)) {
                    $this->setDomainContacts($vars['domain'], $contacts, $row->id ?? null);
                }
            }
        }

        // Return service fields
        $service_fields = [
            [
                'key' => 'domain',
                'value' => $vars['domain'],
                'encrypted' => 0
            ]
        ];

        for ($i = 1; $i <= 5; $i++) {
            if (!empty($vars['ns'][$i])) {
                $service_fields[] = [
                    'key' => 'ns[' . $i . ']',
                    'value' => $vars['ns'][$i],
                    'encrypted' => 0
                ];
            }
        }

        return $service_fields;
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
        if (($row = $this->getModuleRow())) {
            $api = $this->getApi(
                $row->meta->application_key,
                $row->meta->secret_key,
                $row->meta->consumer_key,
                $row->meta->endpoint
            );
        }
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Set request parameters
        if ($api) {
            $params = [
                'renew' => [
                    'automatic' => false,
                    'forced' => false,
                    'deleteAtExpiration' => true
                ]
            ];
            $this->apiRequest($api, '/domain/' . $service_fields->domain . '/serviceInfos', $row->meta->endpoint, $params, 'put');
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
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function suspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        if (($row = $this->getModuleRow())) {
            $api = $this->getApi(
                $row->meta->application_key,
                $row->meta->secret_key,
                $row->meta->consumer_key,
                $row->meta->endpoint
            );
        }
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Set request parameters
        if ($api) {
            $params = [
                'renew' => [
                    'automatic' => false,
                    'forced' => false,
                    'deleteAtExpiration' => false
                ]
            ];
            $this->apiRequest($api, '/domain/' . $service_fields->domain . '/serviceInfos', $row->meta->endpoint, $params, 'put');
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
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function unsuspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        if (($row = $this->getModuleRow())) {
            $api = $this->getApi(
                $row->meta->application_key,
                $row->meta->secret_key,
                $row->meta->consumer_key,
                $row->meta->endpoint
            );
        }
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Set request parameters
        if ($api) {
            $params = [
                'renew' => [
                    'automatic' => true,
                    'forced' => false,
                    'deleteAtExpiration' => false
                ]
            ];
            $this->apiRequest($api, '/domain/' . $service_fields->domain . '/serviceInfos', $row->meta->endpoint, $params, 'put');
        }

        return null;
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
        if (($row = $this->getModuleRow())) {
            $api = $this->getApi(
                $row->meta->application_key,
                $row->meta->secret_key,
                $row->meta->consumer_key,
                $row->meta->endpoint
            );
        }
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Set request parameters
        if ($api) {
            $years = 1;
            foreach ($package->pricing as $pricing) {
                if ($pricing->id == $service->pricing_id) {
                    $years = $pricing->term;
                    break;
                }
            }

            $this->renewDomain($service_fields->domain, $row->id, ['years' => $years]);
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
        // Validate the service fields
        $rules = [
            'domain' => [
                'valid' => [
                    'if_set' => $edit,
                    'rule' => function ($domain) {
                        $validator = new Server();

                        return $validator->isDomain($domain);
                    },
                    'message' => Language::_('OvhDomains.!error.domain.valid', true)
                ]
            ],
            'ns' => [
                'count'=>[
                    'rule' => [[$this, 'validateNameServerCount']],
                    'message' => Language::_('OvhDomains.!error.ns_count', true)
                ],
                'valid'=>[
                    'rule'=>[[$this, 'validateNameServers']],
                    'message' => Language::_('OvhDomains.!error.ns_valid', true)
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Returns all tabs to display to a client when managing a service.
     *
     * @param stdClass $service A stdClass object representing the service
     * @return array An array of tabs in the format of method => title, or method => array where array contains:
     *
     *  - name (required) The name of the link
     *  - icon (optional) use to display a custom icon
     *  - href (optional) use to link to a different URL
     *      Example:
     *      ['methodName' => "Title", 'methodName2' => "Title2"]
     *      ['methodName' => ['name' => "Title", 'icon' => "icon"]]
     */
    public function getClientServiceTabs($service)
    {
        $tabs = [
            'tabClientWhois' => Language::_('OvhDomains.tab_client_whois', true),
            'tabClientNameservers' => Language::_('OvhDomains.tab_client_nameservers', true),
            'tabClientDns' => Language::_('OvhDomains.tab_client_dns', true),
            'tabClientSettings' => Language::_('OvhDomains.tab_client_settings', true)
        ];

        // Check if DNS Management is enabled
        if (!$this->featureServiceEnabled('dns_management', $service)) {
            unset($tabs['tabClientDns']);
        }

        return $tabs;
    }

    /**
     * Returns all tabs to display to an admin when managing a service
     *
     * @param stdClass $service A stdClass object representing the service
     * @return array An array of tabs in the format of method => title.
     *  Example: ['methodName' => "Title", 'methodName2' => "Title2"]
     */
    public function getAdminServiceTabs($service)
    {
        $tabs = [
            'tabWhois' => Language::_('OvhDomains.tab_whois', true),
            'tabNameservers' => Language::_('OvhDomains.tab_nameservers', true),
            'tabDns' => Language::_('OvhDomains.tab_dns', true),
            'tabSettings' => Language::_('OvhDomains.tab_settings', true)
        ];

        // Check if DNS Management is enabled
        if (!$this->featureServiceEnabled('dns_management', $service)) {
            unset($tabs['tabDns']);
        }

        return $tabs;
    }

    /**
     * Checks if a feature is enabled for a given service
     *
     * @param string $feature The name of the feature to check if it's enabled (e.g. id_protection)
     * @param stdClass $service An object representing the service
     * @return bool True if the feature is enabled, false otherwise
     */
    private function featureServiceEnabled($feature, $service)
    {
        // Get service option groups
        foreach ($service->options as $option) {
            if ($option->option_name == $feature) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whois tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabWhois(
        $package,
        $service,
        array $get = null,
        array $post = null,
        array $files = null
    ) {
        $this->view = new View('tab_whois', 'default');
        $this->view->base_uri = $this->base_uri;

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Fetch domain contacts
        try {
            $contact = $this->getDomainContacts($service_fields->domain, $service->module_row_id);
            $vars = (object) ($contact[0] ?? []);
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['contacts' => $e->getMessage()]]);
        }

        // Update whois contact
        if (!empty($post)) {
            $this->setDomainContacts($service_fields->domain, $post, $service->module_row_id);
            $vars = (object) $post;
        }

        // Set countries list
        Loader::loadModels($this, ['Countries']);
        $this->view->set(
            'countries',
            $this->Form->collapseObjectArray($this->Countries->getList(), ['name', 'alt_name'], 'alpha2', ' - ')
        );

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('whois_fields', Configure::get('OvhDomains.contact_fields'));
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ovh_domains' . DS);

        return $this->view->fetch();
    }

    /**
     * Nameservers tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabNameservers(
        $package,
        $service,
        array $get = null,
        array $post = null,
        array $files = null
    ) {
        $this->view = new View('tab_nameservers', 'default');
        $this->view->base_uri = $this->base_uri;

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Set default name servers
        $vars = (object) [];
        for ($i = 1; $i <= 5; $i++) {
            if (empty($vars->{'ns[' . $i . ']'}) && !empty($service_fields->{'ns[' . $i . ']'})) {
                $vars->{'ns[' . $i . ']'} = $service_fields->{'ns[' . $i . ']'};
            }
        }

        // Fetch domain nameservers
        try {
            $nameservers = $this->getDomainNameServers($service_fields->domain, $service->module_row_id);

            foreach ($nameservers as $ns => $nameserver) {
                if (!is_array($nameserver)) {
                    continue;
                }

                $vars->{'ns[' . ($ns + 1) . ']'} = $nameserver['url'];
            }
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['contacts' => $e->getMessage()]]);
        }

        // Update domain nameservers
        if (!empty($post)) {
            $this->setDomainNameservers($service_fields->domain, $service->module_row_id, $post['ns']);

            $vars = (object) [];
            foreach ($post['ns'] as $ns => $nameserver) {
                $vars->{'ns[' . $ns . ']'} = $nameserver;
            }
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('nameserver_fields', Configure::get('OvhDomains.nameserver_fields'));
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ovh_domains' . DS);

        return $this->view->fetch();
    }

    /**
     * DNS records tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabDns(
        $package,
        $service,
        array $get = null,
        array $post = null,
        array $files = null
    ) {
        $this->view = new View('tab_dns', 'default');
        $this->view->base_uri = $this->base_uri;

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Initialize API
        $row = $this->getModuleRow($service->module_row_id);
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Delete DNS record
        if (isset($post['delete'])) {
            $this->apiRequest($api, '/domain/zone/' . $service_fields->domain . '/record/' . trim($post['delete']), $row->meta->endpoint, [], 'delete');
        }

        // Add DNS record
        if (!empty($post) && !isset($post['delete'])) {
            $cart = [
                'fieldType' => $post['record_type'] ?? '',
                'subDomain' => $post['host'] ?? '',
                'target' => $post['value'] ?? '',
                'ttl' => $post['ttl'] ?? 3600
            ];
            $this->apiRequest($api, '/domain/zone/' . $service_fields->domain . '/record', $row->meta->endpoint, $cart, 'post');

            $vars = (object) $post;
        }

        // Fetch DNS records
        try {
            $records = $this->getDomainDnsRecords($service_fields->domain, $service->module_row_id);
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['records' => $e->getMessage()]]);
        }

        // Set record types
        $record_types = [
            'A', 'AAAA', 'CAA', 'CNAME', 'DKIM', 'DMARC', 'DNAME',
            'LOC', 'MX', 'NAPTR', 'NS', 'PTR', 'SPF', 'SRV', 'SSHFP',
            'TLSA', 'TXT'
        ];
        $record_types = array_combine($record_types, $record_types);

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('records', $records ?? []);
        $this->view->set('record_types', $record_types);
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ovh_domains' . DS);

        return $this->view->fetch();
    }

    /**
     * Settings tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabSettings(
        $package,
        $service,
        array $get = null,
        array $post = null,
        array $files = null
    ) {
        $this->view = new View('tab_settings', 'default');
        $this->view->base_uri = $this->base_uri;

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Initialize API
        $row = $this->getModuleRow($service->module_row_id);
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Determine if this service has access to epp_code
        $epp_code = $package->meta->epp_code ?? '0';

        // Get domain auth code
        $auth_code = null;
        if (!$this->getDomainIsLocked($service_fields->domain)) {
            $auth_code = $this->apiRequest($api, '/domain/' . $service_fields->domain . '/authInfo', $row->meta->endpoint, [], 'get');
        }

        // Update domain settings
        if (!empty($post)) {
            if (!empty($post['transferLockStatus'])) {
                if ($post['transferLockStatus'] == 'locked') {
                    $this->lockDomain($service_fields->domain, $service->module_row_id);
                }
                if ($post['transferLockStatus'] == 'unlocked') {
                    $this->unlockDomain($service_fields->domain, $service->module_row_id);
                }
            }

            $vars = (object) $post;
        }

        // Get domain status
        $is_locked = $this->getDomainIsLocked($service_fields->domain);

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('epp_code', $epp_code);
        $this->view->set('auth_code', $auth_code);
        $this->view->set('is_locked', $is_locked);
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ovh_domains' . DS);

        return $this->view->fetch();
    }

    /**
     * Whois client tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientWhois(
        $package,
        $service,
        array $get = null,
        array $post = null,
        array $files = null
    ) {
        $this->view = new View('tab_client_whois', 'default');
        $this->view->base_uri = $this->base_uri;

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Fetch domain contacts
        try {
            $contact = $this->getDomainContacts($service_fields->domain, $service->module_row_id);
            $vars = (object) ($contact[0] ?? []);
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['contacts' => $e->getMessage()]]);
        }

        // Update whois contact
        if (!empty($post)) {
            $this->setDomainContacts($service_fields->domain, $post, $service->module_row_id);
            $vars = (object) $post;
        }

        // Set countries list
        Loader::loadModels($this, ['Countries']);
        $this->view->set(
            'countries',
            $this->Form->collapseObjectArray($this->Countries->getList(), ['name', 'alt_name'], 'alpha2', ' - ')
        );

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('whois_fields', Configure::get('OvhDomains.contact_fields'));
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ovh_domains' . DS);

        return $this->view->fetch();
    }

    /**
     * Nameservers client tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientNameservers(
        $package,
        $service,
        array $get = null,
        array $post = null,
        array $files = null
    ) {
        $this->view = new View('tab_client_nameservers', 'default');
        $this->view->base_uri = $this->base_uri;

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Set default name servers
        $vars = (object) [];
        for ($i = 1; $i <= 5; $i++) {
            if (empty($vars->{'ns[' . $i . ']'}) && !empty($service_fields->{'ns[' . $i . ']'})) {
                $vars->{'ns[' . $i . ']'} = $service_fields->{'ns[' . $i . ']'};
            }
        }

        // Fetch domain nameservers
        try {
            $nameservers = $this->getDomainNameServers($service_fields->domain, $service->module_row_id);

            foreach ($nameservers as $ns => $nameserver) {
                if (!is_array($nameserver)) {
                    continue;
                }

                $vars->{'ns[' . ($ns + 1) . ']'} = $nameserver['url'];
            }
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['contacts' => $e->getMessage()]]);
        }

        // Update domain nameservers
        if (!empty($post)) {
            $this->setDomainNameservers($service_fields->domain, $service->module_row_id, $post['ns']);

            $vars = (object) [];
            foreach ($post['ns'] as $ns => $nameserver) {
                $vars->{'ns[' . $ns . ']'} = $nameserver;
            }
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('nameserver_fields', Configure::get('OvhDomains.nameserver_fields'));
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ovh_domains' . DS);

        return $this->view->fetch();
    }

    /**
     * DNS records client tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientDns(
        $package,
        $service,
        array $get = null,
        array $post = null,
        array $files = null
    ) {
        $this->view = new View('tab_client_dns', 'default');
        $this->view->base_uri = $this->base_uri;

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Initialize API
        $row = $this->getModuleRow($service->module_row_id);
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Delete DNS record
        if (isset($post['delete'])) {
            $this->apiRequest($api, '/domain/zone/' . $service_fields->domain . '/record/' . trim($post['delete']), $row->meta->endpoint, [], 'delete');
        }

        // Add DNS record
        if (!empty($post) && !isset($post['delete'])) {
            $cart = [
                'fieldType' => $post['record_type'] ?? '',
                'subDomain' => $post['host'] ?? '',
                'target' => $post['value'] ?? '',
                'ttl' => $post['ttl'] ?? 3600
            ];
            $this->apiRequest($api, '/domain/zone/' . $service_fields->domain . '/record', $row->meta->endpoint, $cart, 'post');

            $vars = (object) $post;
        }

        // Fetch DNS records
        try {
            $records = $this->getDomainDnsRecords($service_fields->domain, $service->module_row_id);
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['records' => $e->getMessage()]]);
        }

        // Set record types
        $record_types = [
            'A', 'AAAA', 'CAA', 'CNAME', 'DKIM', 'DMARC', 'DNAME',
            'LOC', 'MX', 'NAPTR', 'NS', 'PTR', 'SPF', 'SRV', 'SSHFP',
            'TLSA', 'TXT'
        ];
        $record_types = array_combine($record_types, $record_types);

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('records', $records ?? []);
        $this->view->set('record_types', $record_types);
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ovh_domains' . DS);

        return $this->view->fetch();
    }

    /**
     * Settings client tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientSettings(
        $package,
        $service,
        array $get = null,
        array $post = null,
        array $files = null
    ) {
        $this->view = new View('tab_client_settings', 'default');
        $this->view->base_uri = $this->base_uri;

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Initialize API
        $row = $this->getModuleRow($service->module_row_id);
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Determine if this service has access to epp_code
        $epp_code = $package->meta->epp_code ?? '0';

        // Get domain auth code
        $auth_code = null;
        if (!$this->getDomainIsLocked($service_fields->domain)) {
            $auth_code = $this->apiRequest($api, '/domain/' . $service_fields->domain . '/authInfo', $row->meta->endpoint, [], 'get');
        }

        // Update domain settings
        if (!empty($post)) {
            if (!empty($post['transferLockStatus'])) {
                if ($post['transferLockStatus'] == 'locked') {
                    $this->lockDomain($service_fields->domain, $service->module_row_id);
                }
                if ($post['transferLockStatus'] == 'unlocked') {
                    $this->unlockDomain($service_fields->domain, $service->module_row_id);
                }
            }

            $vars = (object) $post;
        }

        // Get domain status
        $is_locked = $this->getDomainIsLocked($service_fields->domain);

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('epp_code', $epp_code);
        $this->view->set('auth_code', $auth_code);
        $this->view->set('is_locked', $is_locked);
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'ovh_domains' . DS);

        return $this->view->fetch();
    }

    /**
     * Gets a list of DNS records associated with a domain zone
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return array An array containing a list of DNS records
     */
    private function getDomainDnsRecords($domain, $module_row_id = null)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Get DNS records
        $records = $this->apiRequest($api, '/domain/zone/' . $domain . '/record', $row->meta->endpoint, [], 'get');

        if (!is_scalar($records)) {
            foreach ($records as &$record) {
                $record = (array) $this->apiRequest($api, '/domain/zone/' . $domain . '/record/' . $record, $row->meta->endpoint, [], 'get');
            }
        } else {
            $records = [];
        }

        return (array) $records;
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
        // Set default name servers
        if (empty($vars->{'ns[1]'})) {
            for ($i = 1; $i <= 5; $i++) {
                if (empty($vars->{'ns[' . $i . ']'}) && !empty($package->meta->ns[$i - 1])) {
                    $vars->{'ns[' . $i . ']'} = $package->meta->ns[$i - 1];
                }
            }
        }

        // Handle transfer request
        if (isset($vars->transfer) || isset($vars->auth_code)) {
            $fields = array_merge(
                Configure::get('OvhDomains.transfer_fields'),
                Configure::get('OvhDomains.nameserver_fields')
            );

            return $this->arrayToModuleFields($fields, null, $vars);
        } else {
            $fields = array_merge(
                Configure::get('OvhDomains.domain_fields'),
                Configure::get('OvhDomains.nameserver_fields')
            );

            return $this->arrayToModuleFields($fields, null, $vars);
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
        // Set default name servers
        if (empty($vars->{'ns[1]'})) {
            for ($i = 1; $i <= 5; $i++) {
                if (empty($vars->{'ns[' . $i . ']'}) && !empty($package->meta->ns[$i - 1])) {
                    $vars->{'ns[' . $i . ']'} = $package->meta->ns[$i - 1];
                }
            }
        }

        // Handle transfer request
        if (isset($vars->transfer) || isset($vars->auth_code)) {
            $fields = array_merge(
                Configure::get('OvhDomains.transfer_fields'),
                Configure::get('OvhDomains.nameserver_fields')
            );

            // We should already have the domain name don't make editable
            $fields['domain']['type'] = 'hidden';
            $fields['domain']['label'] = null;

            return $this->arrayToModuleFields($fields, null, $vars);
        } else {
            $fields = array_merge(
                Configure::get('OvhDomains.domain_fields'),
                Configure::get('OvhDomains.nameserver_fields')
            );

            // We should already have the domain name don't make editable
            $fields['domain']['type'] = 'hidden';
            $fields['domain']['label'] = null;

            return $this->arrayToModuleFields($fields, null, $vars);
        }
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
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Create a new order cart
        $subsidiary = trim(strtoupper(str_replace('ovh-', '', $row->meta->endpoint)));
        $cart = [
            'ovhSubsidiary' => ($subsidiary == 'EU') ? 'FR' : $subsidiary
        ];
        $response = $this->apiRequest($api, '/order/cart', $row->meta->endpoint, $cart, 'post');
        $cart_id = $response->cartId ?? null;

        if (!is_null($cart_id)) {
            // Add domain to the cart
            $domain = [
                'domain' => $domain
            ];
            $domain_cart = $this->apiRequest($api, '/order/cart/' . $cart_id . '/domain', $row->meta->endpoint, $domain, 'post');
        }

        return isset($domain_cart->settings['pricingMode']) && $domain_cart->settings['pricingMode'] == 'create-default';
    }

    /**
     * Verifies that the provided domain name is available for transfer
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the domain is available for transfer, false otherwise
     */
    public function checkTransferAvailability($domain, $module_row_id = null)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Create a new order cart
        $subsidiary = trim(strtoupper(str_replace('ovh-', '', $row->meta->endpoint)));
        $cart = [
            'ovhSubsidiary' => ($subsidiary == 'EU') ? 'FR' : $subsidiary
        ];
        $response = $this->apiRequest($api, '/order/cart', $row->meta->endpoint, $cart, 'post');
        $cart_id = $response->cartId ?? null;

        if (!is_null($cart_id)) {
            // Add domain to the cart
            $domain = [
                'domain' => $domain
            ];
            $domain_cart = $this->apiRequest($api, '/order/cart/' . $cart_id . '/domain', $row->meta->endpoint, $domain, 'post');
        }

        return isset($domain_cart->settings['pricingMode']) && $domain_cart->settings['pricingMode'] == 'transfer-default';
    }

    /**
     * Gets a list of basic information for a domain
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return array A list of common domain information
     *
     *  - * The contents of the return vary depending on the registrar
     */
    public function getDomainInfo($domain, $module_row_id = null)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Set request parameters
        return $this->apiRequest($api, '/domain/' . $domain, $row->meta->endpoint, [], 'get');
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
        $row = $this->getModuleRow($service->module_row_id);
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Set request parameters
        $domain = $this->apiRequest($api, '/domain/' . $this->getServiceDomain($service) . '/serviceInfos', $row->meta->endpoint, [], 'get');

        return isset($domain->services->expiration)
            ? date($format, strtotime($domain->services->expiration))
            : false;
    }

    /**
     * Gets the domain name from the given service
     *
     * @param stdClass $service The service from which to extract the domain name
     * @return string The domain name associated with the service
     * @see Services::get()
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
     * Register a new domain through the registrar
     *
     * @param string $domain The domain to register
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @param array $vars A list of vars to submit with the registration request
     *
     *  - * The contents of $vars vary depending on the registrar
     * @return bool True if the domain was successfully registered, false otherwise
     */
    public function registerDomain($domain, $module_row_id = null, array $vars = [])
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Create a new order cart
        $subsidiary = trim(strtoupper(str_replace('ovh-', '', $row->meta->endpoint)));
        $cart = [
            'ovhSubsidiary' => ($subsidiary == 'EU') ? 'FR' : $subsidiary
        ];
        $response = $this->apiRequest($api, '/order/cart', $row->meta->endpoint, $cart, 'post');
        $cart_id = $response->cartId ?? null;

        if (!is_null($cart_id)) {
            // Add domain to be transferred to the cart
            $transfer = [
                'domain' => $domain,
                'duration' => 'P' . ($vars['qty'] ?? $vars['years'] ?? 1) . 'Y'
            ];
            $this->apiRequest($api, '/order/cart/' . $cart_id . '/domain', $row->meta->endpoint, $transfer, 'post');

            // Assign the cart to the current user
            $this->apiRequest($api, '/order/cart/' . $cart_id . '/assign', $row->meta->endpoint, [], 'post');

            // Checkout the order
            $checkout = [
                'autoPayWithPreferredPaymentMethod' => false,
                'waiveRetractationPeriod' => false
            ];
            $domain_order = $this->apiRequest($api, '/order/cart/' . $cart_id . '/checkout', $row->meta->endpoint, $checkout, 'post');

            // Pay the order
            if (isset($domain_order->orderId)) {
                $pay_params = [
                    'paymentMean' => 'ovhAccount'
                ];
                $this->apiRequest($api, '/me/order/' . $domain_order->orderId . '/payWithRegisteredPaymentMean', $row->meta->endpoint, $pay_params, 'post');
            }
        }

        // Verify order status
        if (!empty($domain_order->orderId)) {
            $order = $this->apiRequest($api, '/me/order/' . $domain_order->orderId . '/status', $row->meta->endpoint, [], 'get');

            if (in_array($order->status ?? $order->scalar, ['delivered', 'delivering', 'checking'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Renew a domain through the registrar
     *
     * @param string $domain The domain to renew
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @param array $vars A list of vars to submit with the renew request
     *
     *  - * The contents of $vars vary depending on the registrar
     * @return bool True if the domain was successfully renewed, false otherwise
     */
    public function renewDomain($domain, $module_row_id = null, array $vars = [])
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Set request parameters
        $params = [
            'renew' => [
                'automatic' => true,
                'forced' => true,
                'deleteAtExpiration' => false,
                'manualPayment' => false,
                'period' => 'P' . ($vars['qty'] ?? $vars['years'] ?? 1) . 'Y'
            ]
        ];
        $response = $this->apiRequest($api, '/domain/' . $domain . '/serviceInfos', $row->meta->endpoint, $params, 'put');

        return !empty($response);
    }

    /**
     * Transfer a domain through the registrar
     *
     * @param string $domain The domain to register
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @param array $vars A list of vars to submit with the transfer request
     *
     *  - * The contents of $vars vary depending on the registrar
     * @return bool True if the domain was successfully transferred, false otherwise
     */
    public function transferDomain($domain, $module_row_id = null, array $vars = [])
    {
        $qty = $vars['qty'] ?? $vars['years'] ?? 1;
        unset($vars['qty']);
        unset($vars['years']);

        $transfer = $this->registerDomain($domain, $module_row_id, $vars);
        $this->renewDomain($domain, $module_row_id, array_merge($vars, ['years' => $qty]));

        return $transfer;
    }

    /**
     * Gets a list of contacts associated with a domain
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return array A list of contact objects with the following information:
     *
     *  - external_id The ID of the contact in the registrar
     *  - email The primary email associated with the contact
     *  - phone The phone number associated with the contact
     *  - first_name The first name of the contact
     *  - last_name The last name of the contact
     *  - address1 The contact's address
     *  - address2 The contact's address line two
     *  - city The contact's city
     *  - state The 3-character ISO 3166-2 subdivision code
     *  - zip The zip/postal code for this contact
     *  - country The 2-character ISO 3166-1 country code
     */
    public function getDomainContacts($domain, $module_row_id = null)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Set request parameters
        $response = $this->apiRequest($api, '/domain/' . $domain, $row->meta->endpoint, [], 'get');

        $contacts = [];
        if (isset($response->whoisOwner)) {
            $contact = $this->apiRequest($api, '/domain/contact/' . $response->whoisOwner, $row->meta->endpoint, [], 'get');
            $contacts[] = [
                'external_id' => $contact->id ?? '',
                'email' => $contact->email ?? '',
                'phone' => $contact->phone ?? '',
                'first_name' => $contact->firstName ?? '',
                'last_name' => $contact->lastName ?? '',
                'address1' => $contact->address['line1'] ?? '',
                'address2' => $contact->address['line2'] ?? '',
                'city' => $contact->address['city'] ?? '',
                'state' => $contact->address['province'] ?? '',
                'zip' => $contact->address['zip'] ?? '',
                'country' => $contact->address['country'] ?? ''
            ];
        }

        return $contacts;
    }

    /**
     * Returns whether the domain has a registrar lock
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the domain has a registrar lock, false otherwise
     */
    public function getDomainIsLocked($domain, $module_row_id = null)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Set request parameters
        $response = $this->apiRequest($api, '/domain/' . $domain, $row->meta->endpoint, [], 'get');

        return (($response->transferLockStatus ?? '') == 'locked');
    }

    /**
     * Gets a list of name server data associated with a domain
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return array A list of name servers, each with the following fields:
     *
     *  - url The URL of the name server
     *  - ips A list of IPs for the name server
     */
    public function getDomainNameServers($domain, $module_row_id = null)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Set request parameters
        $response = $this->apiRequest($api, '/domain/' . $domain . '/nameServer', $row->meta->endpoint, [], 'get');

        $nameservers = [];
        if (!is_iterable($response)) {
            foreach ($response ?? [] as $nameserver_id) {
                $nameserver = $this->apiRequest($api, '/domain/' . $domain . '/nameServer/' . $nameserver_id, $row->meta->endpoint, [], 'get');
                $nameservers[] = [
                    'url' => $nameserver->host,
                    'ips' => [!is_null($nameserver->ip) ? $nameserver->ip : gethostbyname($nameserver->host)]
                ];
            }
        }

        $this->Input->setErrors([]);

        return $nameservers;
    }

    /**
     * Locks the given domain
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the domain was successfully locked, false otherwise
     */
    public function lockDomain($domain, $module_row_id = null)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Set request parameters
        $params = [
            'transferLockStatus' => 'locked'
        ];
        $response = $this->apiRequest($api, '/domain/' . $domain, $row->meta->endpoint, $params, 'put');

        return !empty($response);
    }

    /**
     * Updates the list of contacts associated with a domain
     *
     * @param string $domain The domain for which to update contact info
     * @param array $vars A list of contact arrays with the following information:
     *
     *  - external_id The ID of the contact in the registrar (optional)
     *  - email The primary email associated with the contact
     *  - phone The phone number associated with the contact
     *  - first_name The first name of the contact
     *  - last_name The last name of the contact
     *  - address1 The contact's address
     *  - address2 The contact's address line two
     *  - city The contact's city
     *  - state The 3-character ISO 3166-2 subdivision code
     *  - zip The zip/postal code for this contact
     *  - country The 2-character ISO 3166-1 country code
     *  - * Other fields required by the registrar
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the contacts were updated, false otherwise
     */
    public function setDomainContacts($domain, array $vars = [], $module_row_id = null)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Get domain whois owner
        $domain_info = $this->apiRequest($api, '/domain/' . $domain, $row->meta->endpoint, [], 'get');

        // Update contact
        $contact_params = [
            'email' => $vars['email'] ?? '',
            'phone' => $vars['phone'] ?? '',
            'firstName' => $vars['first_name'] ?? '',
            'lastName' => $vars['last_name'] ?? '',
            'address' => [
                'line1' => $vars['address1'] ?? '',
                'line2' => $vars['address2'] ?? '',
                'city' => $vars['city'] ?? '',
                'province' => $vars['state'] ?? '',
                'country' => $vars['country'] ?? '',
                'zip' => $vars['zip'] ?? ''
            ]
        ];

        if (!empty($domain_info->whoisOwner)) {
            // Merge new parameters with the current remote parameters
            $external_contact = (array) $this->apiRequest($api, '/domain/contact/' . $domain_info->whoisOwner, $row->meta->endpoint, [], 'get');
            if (isset($external_contact['id'])) {
                unset($external_contact['id']);
            }
            $contact_params = array_merge($external_contact, $contact_params);

            // Update contact
            $contact_response = $this->apiRequest($api, '/domain/contact/' . $domain_info->whoisOwner, $row->meta->endpoint, $contact_params, 'put');
        }

        return !empty($contact_response);
    }

    /**
     * Assign new name servers to a domain
     *
     * @param string $domain The domain for which to assign new name servers
     * @param int|null $module_row_id The ID of the module row to fetch for the current module
     * @param array $vars A list of name servers to assign (e.g. [ns1, ns2])
     * @return bool True if the name servers were successfully updated, false otherwise
     */
    public function setDomainNameservers($domain, $module_row_id = null, array $vars = [])
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Set request parameters
        $params = [];
        foreach ($vars as $ns) {
            if (empty($ns)) {
                continue;
            }

            $params['nameServers'][] = [
                'host' => trim($ns),
                'ip' => gethostbyname(trim($ns))
            ];
        }
        $response = $this->apiRequest($api, '/domain/' . $domain . '/nameServers/update', $row->meta->endpoint, $params, 'post');

        return !empty($response);
    }

    /**
     * Unlocks the given domain
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the domain was successfully unlocked, false otherwise
     */
    public function unlockDomain($domain, $module_row_id = null)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Set request parameters
        $params = [
            'transferLockStatus' => 'unlocked'
        ];
        $response = $this->apiRequest($api, '/domain/' . $domain, $row->meta->endpoint, $params, 'put');

        return !empty($response);
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
            Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'ovh_domains' . DS
        );

        if ($cache) {
            $response = unserialize(base64_decode($cache));
        }

        if (!isset($response)) {
            try {
                $row = $this->getModuleRow($module_row_id);
                if (!$row) {
                    $rows = $this->getModuleRows();
                    if (isset($rows[0])) {
                        $row = $rows[0];
                    }
                    unset($rows);
                }

                $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

                // Set request parameters
                $response = (array) $this->apiRequest($api, '/domain/extensions', $row->meta->endpoint, [], 'get');

                foreach ($response as &$tld) {
                    $tld = '.' . $tld;
                }

                // Save TLDs in cache
                if (Configure::get('Caching.on') && is_writable(CACHEDIR)) {
                    try {
                        Cache::writeCache(
                            'tlds',
                            base64_encode(serialize($response)),
                            strtotime(Configure::get('Blesta.cache_length')) - time(),
                            Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'ovh_domains' . DS
                        );
                    } catch (Exception $e) {
                        // Write to cache failed, so disable caching
                        Configure::set('Caching.on', false);
                    }
                }
            } catch (Throwable $e) {
                $response = Configure::get('OvhDomains.tlds');
            }
        }

        return (array) $response;
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
        // Get all TLDs
        $tlds = $this->getTlds($module_row_id);

        // Get all currencies
        Loader::loadModels($this, ['Currencies']);

        $currencies = [];
        $company_currencies = $this->Currencies->getAll(Configure::get('Blesta.company_id'));
        foreach ($company_currencies as $currency) {
            $currencies[$currency->code] = $currency;
        }

        // Format pricing
        $response = [];
        foreach ($tlds as $tld) {
            $tld = '.' . ltrim($tld, '.');

            // Filter by 'tlds'
            if (isset($filters['tlds']) && !in_array($tld, $filters['tlds'])) {
                continue;
            }

            if (!isset($response[$tld])) {
                $response[$tld] = [];
            }

            // Get TLD price
            $tld_price = $this->getTldPrice($tld, $module_row_id);

            // Get currency
            $currency = $tld_price['register']['currencyCode'] ?? 'CAD';

            // Validate if the reseller currency exists in the company
            if (!isset($currencies[$currency])) {
                $this->Input->setErrors([
                    'currency' => [
                        'not_exists' => Language::_('OvhDomains.!error.currency.not_exists', true)
                    ]
                ]);

                return [];
            }

            // Calculate term prices
            for ($i = 1; $i <= 10; $i++) {
                // Filter by 'terms'
                if (isset($filters['terms']) && !in_array($i, $filters['terms'])) {
                    continue;
                }

                foreach ($currencies as $currency) {
                    // Filter by 'currencies'
                    if (isset($filters['currencies']) && !in_array($currency->code, $filters['currencies'])) {
                        continue;
                    }

                    if (!isset($response[$tld][$currency->code])) {
                        $response[$tld][$currency->code] = [];
                    }

                    if (!isset($response[$tld][$currency->code][$i])) {
                        $response[$tld][$currency->code][$i] = [
                            'register' => null,
                            'transfer' => null,
                            'renew' => null
                        ];
                    }

                    foreach ($tld_price as $category => $price) {
                        $response[$tld][$currency->code][$i][$category] = $this->Currencies->convert(
                            ($price['value'] ?? 0) * $i,
                            $price['currencyCode'] ?? 'CAD',
                            $currency->code,
                            Configure::get('Blesta.company_id')
                        );
                    }
                }
            }
        }

        return $response;
    }

    /**
     * Get the pricing of a TLD
     *
     * @param string $tld The TLD to fetch the pricing
     * @param int $module_row_id The module row ID
     * @return mixed An object containing the pricing information, or false if the pricing could not be fetched
     */
    private function getTldPrice($tld, $module_row_id = null)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->application_key, $row->meta->secret_key, $row->meta->consumer_key, $row->meta->endpoint);

        // Fetch the TLD pricing from the cache, if they exist
        $cache = Cache::fetchCache(
            'tlds_price_' . ltrim($tld, '.'),
            Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'ovh_domains' . DS
        );

        if ($cache) {
            $response = unserialize(base64_decode($cache));
        }

        // Create a new order cart
        if (!isset($response)) {
            $subsidiary = trim(strtoupper(str_replace('ovh-', '', $row->meta->endpoint)));
            $cart_params = [
                'ovhSubsidiary' => ($subsidiary == 'EU') ? 'FR' : $subsidiary
            ];
            $cart = $this->apiRequest($api, '/order/cart', $row->meta->endpoint, $cart_params, 'post');

            // Add the domain to the cart
            $domain = [
                'domain' => md5(time() . rand(0, 255)) . '.' . ltrim($tld, '.'),
                'duration' => 'P1Y'
            ];
            $response = $this->apiRequest($api, '/order/cart/' . ($cart->cartId ?? '') . '/domain', $row->meta->endpoint, $domain, 'post');

            // Save pricing in cache
            if (Configure::get('Caching.on') && is_writable(CACHEDIR)) {
                try {
                    Cache::writeCache(
                        'tlds_price_' . ltrim($tld, '.'),
                        base64_encode(serialize($response)),
                        strtotime(Configure::get('Blesta.cache_length')) - time(),
                        Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'ovh_domains' . DS
                    );
                } catch (Exception $e) {
                    // Write to cache failed, so disable caching
                    Configure::set('Caching.on', false);
                }
            }
        }

        $pricing = [];
        if (!empty($response->prices)) {
            foreach ($response->prices as $price) {
                if ($price['label'] == 'PRICE') {
                    $price['label'] = 'REGISTER';
                }
                if ($price['label'] == 'TOTAL') {
                    $price['label'] = 'TRANSFER';
                }

                $pricing[strtolower($price['label'])] = $price['price'];
            }
        }

        return $pricing;
    }

    /**
     * Initialize the API library
     *
     * @param string $application_key The OVH application key
     * @param string $secret_key The OVH application secret key
     * @param string $consumer_key 'true' to use the Sandbox API
     * @param string $endpoint 'true' to use the Sandbox API
     * @return \Ovh\Api The \Ovh\Api instance, or false if the loader fails to load the file
     */
    private function getApi($application_key, $secret_key, $consumer_key, $endpoint)
    {
        return new \Ovh\Api(
            $application_key,
            $secret_key,
            $endpoint,
            $consumer_key
        );
    }

    /**
     * Send a request to the OVH API
     *
     * @param \Ovh\Api $api The OVH API instance
     * @param string $path The API path to send the request
     * @param string $endpoint The API endpoint to connect
     * @param array $params An array containing the parameters to send to the API
     * @param string $method The HTTP method to use to send the request
     * @return mixed The response from the API, void if an error occurred
     */
    private function apiRequest(\Ovh\Api $api, string $path, string $endpoint, array $params = null, string $method = 'get')
    {
        $method = strtolower($method);

        if (is_array($params) && empty($params)) {
            $params = null;
        }

        if (method_exists($api, $method)) {
            // Set the request url
            $request_url = ($this->endpoints[$endpoint] ?? $endpoint . '/') . $path;

            // Log request
            $this->log($request_url, json_encode($params), 'input', true);

            // Set the domain status
            try {
                $response = (object) $api->{$method}($path, $params);

                if (isset($response->class) && isset($response->message)) {
                    $this->Input->setErrors(['errors' => [$response->class => $response->message]]);
                    $this->log($request_url, json_encode($response), 'output', false);
                } else {
                    $this->log($request_url, json_encode($response), 'output', true);

                    return $response;
                }
            } catch (Throwable $e) {
                if ($e->hasResponse()) {
                    $response = $e->getResponse()->getBody()->getContents();
                    if (!empty($response)) {
                        $response = json_decode($response);
                    }

                    $this->Input->setErrors(['errors' => [$endpoint => $response->message ?? $e->getMessage()]]);
                    $this->log($request_url, json_encode($response), 'output', false);
                } else {
                    $this->Input->setErrors(['errors' => [$endpoint => $e->getMessage()]]);
                    $this->log($request_url, json_encode($e->getTraceAsString()), 'output', false);
                }
            }
        }
    }
}
