<?php

use Blesta\Core\Util\Validate\Server;
use Blesta\Core\Util\Common\Traits\Container;

/**
 * Internet.bs Module
 *
 * @package blesta
 * @subpackage blesta.components.modules.internetbs
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Internetbs extends RegistrarModule
{
    // Load traits
    use Container;

    /**
     * Initializes the module
     */
    public function __construct()
    {
        // Load the language required by this module
        Language::loadLang('internetbs', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load module config
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        Configure::load('internetbs', dirname(__FILE__) . DS . 'config' . DS);
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'internetbs' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'internetbs' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        if (!empty($vars)) {
            // Set unset checkboxes
            $checkbox_fields = ['sandbox'];
            foreach ($checkbox_fields as $checkbox_field) {
                if (!isset($vars[$checkbox_field])) {
                    $vars[$checkbox_field] = 'false';
                }
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'internetbs' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        if (empty($vars)) {
            $vars = $module_row->meta;
        } else {
            // Set unset checkboxes
            $checkbox_fields = ['sandbox'];
            foreach ($checkbox_fields as $checkbox_field) {
                if (!isset($vars[$checkbox_field])) {
                    $vars[$checkbox_field] = 'false';
                }
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
        $meta_fields = ['api_key', 'password', 'sandbox'];
        $encrypted_fields = ['api_key', 'password'];

        // Set unset checkboxes
        $checkbox_fields = ['sandbox'];
        foreach ($checkbox_fields as $checkbox_field) {
            if (!isset($vars[$checkbox_field])) {
                $vars[$checkbox_field] = 'false';
            }
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
        $meta_fields = ['api_key', 'password', 'sandbox'];
        $encrypted_fields = ['api_key', 'password'];

        // Set unset checkboxes
        $checkbox_fields = ['sandbox'];
        foreach ($checkbox_fields as $checkbox_field) {
            if (!isset($vars[$checkbox_field])) {
                $vars[$checkbox_field] = 'false';
            }
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
     * Builds and returns the rules required to add/edit a module row (e.g. server).
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getRowRules(&$vars)
    {
        $rules = [
            'api_key' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Internetbs.!error.api_key.valid', true)
                ]
            ],
            'password' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Internetbs.!error.password.valid', true)
                ],
                'valid_connection' => [
                    'rule' => [
                        [$this, 'validateConnection'],
                        $vars['api_key'],
                        $vars['sandbox']
                    ],
                    'message' => Language::_('Internetbs.!error.password.valid_connection', true)
                ]
            ],
            'sandbox' => [
                'format' => [
                    'rule' => ['in_array', ['true', 'false']],
                    'message' => Language::_('Internetbs.!error.sandbox.format', true)
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

        return $validator->isDomain($host_name) || $validator->isIp($host_name);
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
     * Validates whether or not the connection details are valid by attempting to fetch
     * the number of accounts that currently reside on the server.
     *
     * @param string $password The Internet.bs API password
     * @param string $api_key The Internet.bs API key
     * @param string $sandbox 'true' to use the sandbox API
     * @return bool True if the connection is valid, false otherwise
     */
    public function validateConnection($password, $api_key, $sandbox = 'false')
    {
        try {
            $api = $this->getApi($api_key, $password, $sandbox);

            // Load API command
            $command = new InternetbsDomain($api);

            // List the domains
            $response = $command->list();
            $this->processResponse($api, $response);

            return ($response->status() == 200);
        } catch (Exception $e) {
            // Trap any errors encountered, could not validate connection
        }

        return false;
    }

    /**
     * Returns an array of available service deligation order methods. The module
     * will determine how each method is defined. For example, the method "first"
     * may be implemented such that it returns the module row with the least number
     * of services assigned to it.
     *
     * @return array An array of order methods in key/value paris where the key is the
     *  type to be stored for the group and value is the name for that option
     * @see Module::selectModuleRow()
     */
    public function getGroupOrderOptions()
    {
        return [
            'roundrobin' => Language::_('Internetbs.order_options.roundrobin', true),
            'first' => Language::_('Internetbs.order_options.first', true)
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

        // Set unset checkboxes
        $checkbox_fields = ['epp_code'];
        foreach ($checkbox_fields as $checkbox_field) {
            if (!isset($vars['meta'][$checkbox_field])) {
                $vars['meta'][$checkbox_field] = '0';
            }
        }

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

        // Set unset checkboxes
        $checkbox_fields = ['epp_code'];
        foreach ($checkbox_fields as $checkbox_field) {
            if (!isset($vars['meta'][$checkbox_field])) {
                $vars['meta'][$checkbox_field] = '0';
            }
        }

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
            'meta[epp_code]' => [
                'format' => [
                    'ifset' => true,
                    'rule' => ['in_array', [0, 1]],
                    'message' => Language::_('Internetbs.!error.meta[epp_code].format', true)
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

        // Set module row
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
            // Fetch the 1st account from the list of accounts in the selected group
            $rows = $this->getModuleRows(
                isset($vars->module_group) && is_int($vars->module_group) ? $vars->module_group : null
            );

            if (isset($rows[0])) {
                $module_row = $rows[0];
            }
            unset($rows);
        }

        // Set the EPP Code field
        $epp_code = $fields->label(Language::_('Internetbs.package_fields.epp_code', true), 'internetbs_epp_code');
        $epp_code->attach(
            $fields->fieldCheckbox(
                'meta[epp_code]',
                '1',
                ($vars->meta['epp_code'] ?? null) == '1',
                ['id' => 'internetbs_epp_code']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Internetbs.package_fields.tooltip.epp_code', true));
        $epp_code->attach($tooltip);
        $fields->setField($epp_code);

        // Set all TLD checkboxes
        $tld_options = $fields->label(Language::_('Internetbs.package_fields.tld_options', true));

        $tlds = $this->getTlds($module_row->id);
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
                ['module_row' => ['missing' => Language::_('Internetbs.!error.module_row.missing', true)]]
            );

            return;
        }

        // Validate service
        $this->validateService($package, $vars);
        if ($this->Input->errors()) {
            return;
        }

        if (isset($vars['Domain'])) {
            $tld = $this->getTld($vars['Domain']);
        }

        // Build input fields
        $whois_fields = Configure::get('Internetbs.whois_fields');
        $domain_field_basics = [
            'Domain' => true,
            'Period' => true,
            'Ns_list' => true
        ];
        $transfer_fields = array_merge(Configure::get('Internetbs.transfer_fields'), $domain_field_basics);
        $domain_fields = array_merge(Configure::get('Internetbs.domain_fields'), $domain_field_basics);
        $domain_tld_fields = (array) Configure::get('Internetbs.domain_fields' . $tld);

        $input_fields = array_merge(
            $whois_fields,
            $transfer_fields,
            $domain_fields,
            $domain_field_basics,
            $domain_tld_fields
        );

        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            // Set period
            $vars['Period'] = 1;
            foreach ($package->pricing as $pricing) {
                if ($pricing->id == $vars['pricing_id']) {
                    $vars['Period'] = $pricing->term;
                    break;
                }
            }

            // Set all whois info from client ($vars['client_id'])
            if (!isset($this->Clients)) {
                Loader::loadModels($this, ['Clients']);
            }
            if (!isset($this->Contacts)) {
                Loader::loadModels($this, ['Contacts']);
            }

            $client = $this->Clients->get($vars['client_id']);
            $numbers = $this->Contacts->getNumbers($client->contact_id, 'phone');
            foreach ($input_fields as $key => $field) {
                switch ($key) {
                    case 'Registrant_FirstName':
                    case 'Admin_FirstName':
                    case 'Technical_FirstName':
                    case 'Billing_FirstName':
                        $vars[$key] = $client->first_name;
                        break;
                    case 'Registrant_LastName':
                    case 'Admin_LastName':
                    case 'Technical_LastName':
                    case 'Billing_LastName':
                        $vars[$key] = $client->last_name;
                        break;
                    case 'Registrant_Email':
                    case 'Admin_Email':
                    case 'Technical_Email':
                    case 'Billing_Email':
                        $vars[$key] = $client->email;
                        break;
                    case 'Registrant_PhoneNumber':
                    case 'Admin_PhoneNumber':
                    case 'Technical_PhoneNumber':
                    case 'Billing_PhoneNumber':
                        $vars[$key] = $this->formatPhone(
                            isset($numbers[0]) ? $numbers[0]->number : '1111111111',
                            $client->country
                        );
                        break;
                    case 'Registrant_Street':
                    case 'Admin_Street':
                    case 'Technical_Street':
                    case 'Billing_Street':
                        $vars[$key] = $client->address1;
                        break;
                    case 'Registrant_Street2':
                    case 'Admin_Street2':
                    case 'Technical_Street2':
                    case 'Billing_Street2':
                        $vars[$key] = $client->address2;
                        break;
                    case 'Registrant_Street3':
                    case 'Admin_Street3':
                    case 'Technical_Street3':
                    case 'Billing_Street3':
                        $vars[$key] = $client->state;
                        break;
                    case 'Registrant_City':
                    case 'Admin_City':
                    case 'Technical_City':
                    case 'Billing_City':
                        $vars[$key] = $client->city;
                        break;
                    case 'Registrant_CountryCode':
                    case 'Admin_CountryCode':
                    case 'Technical_CountryCode':
                    case 'Billing_CountryCode':
                        $vars[$key] = !empty($client->country) ? $client->country : 'US';
                        break;
                    case 'Registrant_PostalCode':
                    case 'Admin_PostalCode':
                    case 'Technical_PostalCode':
                    case 'Billing_PostalCode':
                        $vars[$key] = !empty($client->zip) ? $client->zip : '00000';
                        break;
                    case 'telHostingAccount':
                        $vars[$key] = $this->generateUsername($vars['Domain']);
                        break;
                    case 'telHostingPassword':
                        $vars[$key] = substr(base64_encode(md5($vars['Domain'])), 0, 12);
                        break;
                    case 'clientIp':
                        $requestor = $this->getFromContainer('requestor');
                        $vars[$key] = $requestor->ip_address ?? '127.0.0.1';
                        break;
                }
            }

            // Set locality for .ASIA
            if ($tld == '.asia') {
                $vars['DotAsiaCedLocality'] = $client->country;
            }

            // Get request fields
            $params = $this->getFieldsFromInput((array) $vars, $package);

            // Register domain
            $this->registerDomain($vars['Domain'], $row->id ?? null, $params);
        }

        // Return service fields
        return [
            [
                'key' => 'domain',
                'value' => $vars['Domain'],
                'encrypted' => 0
            ]
        ];
    }

    /**
     * Formats a phone number into +NNNNNNNNNNNNN
     *
     * @param string $number The phone number
     * @param string $country The ISO 3166-1 alpha2 country code
     * @return string The number in +NNNNNNNNNNNNN
     */
    private function formatPhone($number, $country)
    {
        if (!isset($this->Contacts)) {
            Loader::loadModels($this, ['Contacts']);
        }

        return $this->Contacts->intlNumber($number, $country);
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
            $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

            // Load API command
            $command = new InternetbsDomain($api);

            // Renew domain
            $params = [
                'Domain' => $this->getServiceDomain($service),
                'Period' => 1
            ];

            foreach ($package->pricing as $pricing) {
                if ($pricing->id == $service->pricing_id) {
                    $params['Period'] = $pricing->term;
                    break;
                }
            }

            // Check if the domain has been manually renewed for more years than the service pricing
            Loader::loadModels($this, ['ServiceChanges']);

            $service_changes = $this->ServiceChanges->getAll('pending', $service->id);
            foreach ($service_changes as $service_change) {
                if (isset($service_change->data->date_renews)) {
                    $next_renewal_date = $service_change->data->date_renews;
                    break;
                }
            }

            $renewal_diff = strtotime($next_renewal_date ?? $service->date_renews) - time();
            if ($renewal_diff > 0) {
                $next_renewal = round($renewal_diff / (60 * 60 * 24)) / 365;
                if ($next_renewal > $params['Period']) {
                    $params['Period'] = round($next_renewal, 0, PHP_ROUND_HALF_DOWN);
                }
            }

            // Only process renewal if adding years today will add time to the expiry date
            if (strtotime('+' . $params['Period'] . ' years') > strtotime($this->getExpirationDate($service))) {
                $params['Period'] = $params['Period'] . 'Y';

                $response = $command->renew($params);
                $this->processResponse($api, $response);
            }
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
            'ns1' => [
                'valid' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateHostName'], true],
                    'message' => Language::_('Internetbs.!error.ns1.valid', true)
                ]
            ],
            'ns2' => [
                'valid' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateHostName'], true],
                    'message' => Language::_('Internetbs.!error.ns2.valid', true)
                ]
            ],
            'ns3' => [
                'valid' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateHostName'], true],
                    'message' => Language::_('Internetbs.!error.ns3.valid', true)
                ]
            ],
            'ns4' => [
                'valid' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateHostName'], true],
                    'message' => Language::_('Internetbs.!error.ns4.valid', true)
                ]
            ],
            'ns5' => [
                'valid' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateHostName'], true],
                    'message' => Language::_('Internetbs.!error.ns5.valid', true)
                ]
            ]
        ];

        // Remove validation rules for optional fields
        if (isset($vars['ns3']) && empty($vars['ns3'])) {
            unset($rules['ns3']);
        }
        if (isset($vars['ns4']) && empty($vars['ns4'])) {
            unset($rules['ns4']);
        }
        if (isset($vars['ns5']) && empty($vars['ns5'])) {
            unset($rules['ns5']);
        }

        return $rules;
    }

    /**
     * Returns an array of service field to set for the service using the given input
     *
     * @param array $vars An array of key/value input pairs
     * @param stdClass $package A stdClass object representing the package for the service
     * @return array An array of key/value pairs representing service fields
     */
    private function getFieldsFromInput(array $vars, $package)
    {
        // Set nameservers list
        $vars['Ns_list'] = '';
        for ($i = 1; $i <= 5; $i++) {
            $vars['Ns_list'] .= $vars['ns' . $i] . ',';
            unset($vars['ns' . $i]);
        }
        $vars['Ns_list'] = rtrim($vars['Ns_list'], ',');

        // Format period
        if (isset($vars['Period']) && !str_contains($vars['Period'], 'Y')) {
            $vars['Period'] = $vars['Period'] . 'Y';
        }

        // Remove unsupported parameters
        $tld = $this->getTld($vars['Domain'] ?? '');
        $domain_field_basics = [
            'Domain' => true,
            'Period' => true,
            'Ns_list' => true
        ];
        $supported_fields = array_keys(array_merge(
            Configure::get('Internetbs.whois_fields'), Configure::get('Internetbs.transfer_fields'),
            Configure::get('Internetbs.domain_fields'), (array) Configure::get('Internetbs.domain_fields' . $tld),
            Configure::get('Internetbs.nameserver_fields'), $domain_field_basics
        ));

        foreach ($vars as $key => $value) {
            if (!in_array($key, $supported_fields)) {
                unset($vars[$key]);
            }
        }

        // Format TLD-specific fields
        $tld_fields = (array) Configure::get('Internetbs.domain_fields' . $tld);
        $contact_types = ['Registrant', 'Admin', 'Technical', 'Billing'];
        foreach ($tld_fields as $field_name => $field) {
            foreach ($contact_types as $contact_type) {
                $vars[$contact_type . '_' . $field_name] = $vars[$field_name];
            }
            unset($vars[$field_name]);
        }

        return $vars;
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

        return substr($username, 0, min($length, 8));
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
            'tabClientWhois' => Language::_('Internetbs.tab_client_whois', true),
            'tabClientNameservers' => Language::_('Internetbs.tab_client_nameservers', true),
            'tabClientUrlForwarding' => Language::_('Internetbs.tab_client_urlforwarding', true),
            'tabClientEmailForwarding' => Language::_('Internetbs.tab_client_emailforwarding', true),
            'tabClientSettings' => Language::_('Internetbs.tab_client_settings', true)
        ];

        // Check if DNS Management is enabled
        if (!$this->featureServiceEnabled('dns_management', $service)) {
            unset($tabs['tabClientNameservers']);
            unset($tabs['tabClientUrlForwarding']);
        }

        // Check if Email Forwarding is enabled
        if (!$this->featureServiceEnabled('email_forwarding', $service)) {
            unset($tabs['tabClientEmailForwarding']);
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
            'tabWhois' => Language::_('Internetbs.tab_whois', true),
            'tabNameservers' => Language::_('Internetbs.tab_nameservers', true),
            'tabUrlForwarding' => Language::_('Internetbs.tab_urlforwarding', true),
            'tabEmailForwarding' => Language::_('Internetbs.tab_emailforwarding', true),
            'tabSettings' => Language::_('Internetbs.tab_settings', true)
        ];

        // Check if DNS Management is enabled
        if (!$this->featureServiceEnabled('dns_management', $service)) {
            unset($tabs['tabNameservers']);
            unset($tabs['tabUrlForwarding']);
        }

        // Check if Email Forwarding is enabled
        if (!$this->featureServiceEnabled('email_forwarding', $service)) {
            unset($tabs['tabEmailForwarding']);
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
            $contacts = $this->getDomainContacts($service_fields->domain, $service->module_row_id);

            $vars = (object) [];
            foreach ($contacts as $contact) {
                if (!is_array($contact)) {
                    continue;
                }

                // Set contact type
                $type = $contact['external_id'] ?? '';
                unset($contact['external_id']);

                if (!isset($vars->$type)) {
                    $vars->$type = [];
                }

                // Format contact
                $fields_map = [
                    'email' => 'Email',
                    'phone' => 'PhoneNumber',
                    'first_name' => 'FirstName',
                    'last_name' => 'LastName',
                    'address1' => 'Street',
                    'address2' => 'Street2',
                    'state' => 'Street3',
                    'city' => 'City',
                    'country' => 'CountryCode',
                    'zip' => 'PostalCode'
                ];
                foreach ($contact as $field => $value) {
                    if (isset($fields_map[$field])) {
                        $vars->$type[$fields_map[$field]] = $value;
                    }
                }
            }
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['contacts' => $e->getMessage()]]);
        }

        // Set tab sections
        $sections = [
            'Registrant', 'Admin',
            'Technical', 'Billing'
        ];

        // Update whois contacts
        if (!empty($post)) {
            $params = [];
            $remote_fields_map = array_flip($fields_map);
            foreach ($post as $type => $contact) {
                $formatted_contact = [
                    'external_id' => $type
                ];
                foreach ($contact as $contact_field => $contact_value) {
                    if (isset($remote_fields_map[$contact_field])) {
                        $formatted_contact[$remote_fields_map[$contact_field]] = $contact_value;
                    }
                }
                $params[] = $formatted_contact;
            }

            $this->setDomainContacts($service_fields->domain, $params, $service->module_row_id);

            $vars = (object) $post;
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('sections', $sections);
        $this->view->set('whois_fields', Configure::get('Internetbs.whois_fields'));
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'internetbs' . DS);

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
            $contacts = $this->getDomainContacts($service_fields->domain, $service->module_row_id);

            $vars = (object) [];
            foreach ($contacts as $contact) {
                if (!is_array($contact)) {
                    continue;
                }

                // Set contact type
                $type = $contact['external_id'] ?? '';
                unset($contact['external_id']);

                if (!isset($vars->$type)) {
                    $vars->$type = [];
                }

                // Format contact
                $fields_map = [
                    'email' => 'Email',
                    'phone' => 'PhoneNumber',
                    'first_name' => 'FirstName',
                    'last_name' => 'LastName',
                    'address1' => 'Street',
                    'address2' => 'Street2',
                    'state' => 'Street3',
                    'city' => 'City',
                    'country' => 'CountryCode',
                    'zip' => 'PostalCode'
                ];
                foreach ($contact as $field => $value) {
                    if (isset($fields_map[$field])) {
                        $vars->$type[$fields_map[$field]] = $value;
                    }
                }
            }
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['contacts' => $e->getMessage()]]);
        }

        // Set tab sections
        $sections = [
            'Registrant', 'Admin',
            'Technical', 'Billing'
        ];

        // Update whois contacts
        if (!empty($post)) {
            $params = [];
            $remote_fields_map = array_flip($fields_map);
            foreach ($post as $type => $contact) {
                $formatted_contact = [
                    'external_id' => $type
                ];
                foreach ($contact as $contact_field => $contact_value) {
                    if (isset($remote_fields_map[$contact_field])) {
                        $formatted_contact[$remote_fields_map[$contact_field]] = $contact_value;
                    }
                }
                $params[] = $formatted_contact;
            }

            $this->setDomainContacts($service_fields->domain, $params, $service->module_row_id);

            $vars = (object) $post;
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('sections', $sections);
        $this->view->set('whois_fields', Configure::get('Internetbs.whois_fields'));
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'internetbs' . DS);

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

        // Fetch domain nameservers
        try {
            $nameservers = $this->getDomainNameServers($service_fields->domain, $service->module_row_id);

            $vars = (object) [];
            foreach ($nameservers as $i => $nameserver) {
                $vars->{'ns' . ($i + 1)} = $nameserver['url'];
            }
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['nameservers' => $e->getMessage()]]);
        }

        // Update nameservers
        if (!empty($post)) {
            $this->setDomainNameservers($service_fields->domain, $service->module_row_id, (array) $vars);

            $vars = (object) $post;
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('nameserver_fields', Configure::get('Internetbs.nameserver_fields'));
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'internetbs' . DS);

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

        // Fetch domain nameservers
        try {
            $nameservers = $this->getDomainNameServers($service_fields->domain, $service->module_row_id);

            $vars = (object) [];
            foreach ($nameservers as $i => $nameserver) {
                $vars->{'ns' . ($i + 1)} = $nameserver['url'];
            }
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['nameservers' => $e->getMessage()]]);
        }

        // Update nameservers
        if (!empty($post)) {
            $this->setDomainNameservers($service_fields->domain, $service->module_row_id, (array) $vars);

            $vars = (object) $post;
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('nameserver_fields', Configure::get('Internetbs.nameserver_fields'));
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'internetbs' . DS);

        return $this->view->fetch();
    }

    /**
     * URL Forwarding tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabUrlForwarding(
        $package,
        $service,
        array $get = null,
        array $post = null,
        array $files = null
    ) {
        $this->view = new View('tab_urlforwarding', 'default');
        $this->view->base_uri = $this->base_uri;

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Fetch domain url forwarding rules
        try {
            $row = $this->getModuleRow($service->module_row_id);
            $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

            // Load API command
            $command = new InternetbsDomainUrlforward($api);

            // List domain rules
            $response = $command->list(['Domain' => $service_fields->domain]);
            $this->processResponse($api, $response);

            $domain_rules = $response->response();

            unset($response);
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['url_forwarding' => $e->getMessage()]]);
        }

        // Add rule
        if (!empty($post)) {
            $response = $command->add($post);

            $vars = (object) $post;
        }

        // Delete rule
        if (!empty($get['delete']) && empty($post)) {
            $response = $command->remove(['Source' => $get['delete']]);
        }

        // Set errors, if any
        if (isset($response)) {
            $this->processResponse($api, $response);
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('domain_rules', $domain_rules->rule ?? []);
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'internetbs' . DS);

        return $this->view->fetch();
    }

    /**
     * URL Forwarding client tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientUrlForwarding(
        $package,
        $service,
        array $get = null,
        array $post = null,
        array $files = null
    ) {
        $this->view = new View('tab_client_urlforwarding', 'default');
        $this->view->base_uri = $this->base_uri;

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Fetch domain url forwarding rules
        try {
            $row = $this->getModuleRow($service->module_row_id);
            $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

            // Load API command
            $command = new InternetbsDomainUrlforward($api);

            // List domain rules
            $response = $command->list(['Domain' => $service_fields->domain]);
            $this->processResponse($api, $response);

            $domain_rules = $response->response();
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['url_forwarding' => $e->getMessage()]]);
        }

        // Update rules
        if (!empty($post)) {
            // Add rule
            if (empty($post['delete'])) {
                $response = $command->add($post);
            }

            // Delete rule
            if (!empty($post['delete'])) {
                $response = $command->remove(['Source' => $post['delete']]);
            }

            // Set errors, if any
            if (isset($response)) {
                $this->processResponse($api, $response);
            }

            $vars = (object) $post;
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('domain_rules', $domain_rules->rule ?? []);
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'internetbs' . DS);

        return $this->view->fetch();
    }

    /**
     * Email Forwarding tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabEmailForwarding(
        $package,
        $service,
        array $get = null,
        array $post = null,
        array $files = null
    ) {
        $this->view = new View('tab_emailforwarding', 'default');
        $this->view->base_uri = $this->base_uri;

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Fetch domain email forwarding rules
        try {
            $row = $this->getModuleRow($service->module_row_id);
            $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

            // Load API command
            $command = new InternetbsDomainEmailforward($api);

            // List domain rules
            $response = $command->list(['Domain' => $service_fields->domain]);
            $this->processResponse($api, $response);

            $domain_rules = $response->response();

            unset($response);
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['email_forwarding' => $e->getMessage()]]);
        }

        // Add rule
        if (!empty($post)) {
            $response = $command->add($post);

            $vars = (object) $post;
        }

        // Delete rule
        if (!empty($get['delete']) && empty($post)) {
            $response = $command->remove(['Source' => $get['delete']]);
        }

        // Set errors, if any
        if (isset($response)) {
            $this->processResponse($api, $response);
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('domain_rules', $domain_rules->rule ?? []);
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'internetbs' . DS);

        return $this->view->fetch();
    }

    /**
     * Email Forwarding client tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientEmailForwarding(
        $package,
        $service,
        array $get = null,
        array $post = null,
        array $files = null
    ) {
        $this->view = new View('tab_client_emailforwarding', 'default');
        $this->view->base_uri = $this->base_uri;

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Fetch domain email forwarding rules
        try {
            $row = $this->getModuleRow($service->module_row_id);
            $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

            // Load API command
            $command = new InternetbsDomainEmailforward($api);

            // List domain rules
            $response = $command->list(['Domain' => $service_fields->domain]);
            $this->processResponse($api, $response);

            $domain_rules = $response->response();
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['email_forwarding' => $e->getMessage()]]);
        }

        // Update rules
        if (!empty($post)) {
            // Add rule
            if (empty($post['delete'])) {
                $response = $command->add($post);
            }

            // Delete rule
            if (!empty($post['delete'])) {
                $response = $command->remove(['Source' => $post['delete']]);
            }

            // Set errors, if any
            if (isset($response)) {
                $this->processResponse($api, $response);
            }

            $vars = (object) $post;
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('domain_rules', $domain_rules->rule ?? []);
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'internetbs' . DS);

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

        // Determine if this service has access to id_protection
        $id_protection = $this->featureServiceEnabled('id_protection', $service);

        // Determine if this service has access to epp_code
        $epp_code = $package->meta->epp_code ?? '0';

        // Update domain settings
        if (!empty($post)) {
            if (!empty($post['registrarlock'])) {
                if ($post['registrarlock'] == 'ENABLED') {
                    $this->lockDomain($service_fields->domain, $service->module_row_id);
                }
                if ($post['registrarlock'] == 'DISABLED') {
                    $this->unlockDomain($service_fields->domain, $service->module_row_id);
                }
            }

            if (!empty($post['privatewhois'])) {
                $row = $this->getModuleRow($service->module_row_id);
                $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

                // Load API command
                $command = new InternetbsDomain($api);

                if ($post['privatewhois'] == 'ENABLED') {
                    $response = $command->privateWhoisEnable(['Domain' => $service_fields->domain]);
                }
                if ($post['privatewhois'] == 'DISABLED') {
                    $response = $command->privateWhoisDisable(['Domain' => $service_fields->domain]);
                }

                $this->processResponse($api, $response);
            }
        }

        // Fetch domain info
        $vars = (object) $this->getDomainInfo($service_fields->domain, $service->module_row_id);

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('id_protection', $id_protection);
        $this->view->set('epp_code', $epp_code);
        $this->view->set('vars', $vars);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'internetbs' . DS);

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

        // Determine if this service has access to id_protection
        $id_protection = $this->featureServiceEnabled('id_protection', $service);

        // Determine if this service has access to epp_code
        $epp_code = $package->meta->epp_code ?? '0';

        // Update domain settings
        if (!empty($post)) {
            if (!empty($post['registrarlock'])) {
                if ($post['registrarlock'] == 'ENABLED') {
                    $this->lockDomain($service_fields->domain, $service->module_row_id);
                }
                if ($post['registrarlock'] == 'DISABLED') {
                    $this->unlockDomain($service_fields->domain, $service->module_row_id);
                }
            }

            if (!empty($post['privatewhois'])) {
                $row = $this->getModuleRow($service->module_row_id);
                $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

                // Load API command
                $command = new InternetbsDomain($api);

                if ($post['privatewhois'] == 'ENABLED') {
                    $response = $command->privateWhoisEnable(['Domain' => $service_fields->domain]);
                }
                if ($post['privatewhois'] == 'DISABLED') {
                    $response = $command->privateWhoisDisable(['Domain' => $service_fields->domain]);
                }

                $this->processResponse($api, $response);
            }
        }

        // Fetch domain info
        $vars = (object) $this->getDomainInfo($service_fields->domain, $service->module_row_id);

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('id_protection', $id_protection);
        $this->view->set('epp_code', $epp_code);
        $this->view->set('vars', $vars);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'internetbs' . DS);

        return $this->view->fetch();
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
        // Handle universal domain name
        if (isset($vars->domain)) {
            $vars->Domain = $vars->domain;
        }

        // Set default name servers
        if (!isset($vars->ns) && isset($package->meta->ns)) {
            $i = 1;
            foreach ($package->meta->ns as $ns) {
                $vars->{'ns' . $i++} = $ns;
            }
        }

        // Handle transfer request
        if (isset($vars->transfer) || isset($vars->transferAuthInfo)) {
            return $this->arrayToModuleFields(Configure::get('Internetbs.transfer_fields'), null, $vars);
        } else {
            // Handle domain registration
            $fields = array_merge(
                Configure::get('Internetbs.nameserver_fields'),
                Configure::get('Internetbs.domain_fields')
            );
            $module_fields = $this->arrayToModuleFields($fields, null, $vars);

            // Build the domain fields
            $domain_fields = $this->buildDomainModuleFields($vars);
            if ($domain_fields) {
                $module_fields = $domain_fields;
            }
        }

        // Determine whether this is an AJAX request
        return ($module_fields ?? new ModuleFields());
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
        // Handle universal domain name
        if (isset($vars->domain)) {
            $vars->Domain = $vars->domain;
        }

        // Set default name servers
        if (!isset($vars->ns) && isset($package->meta->ns)) {
            $i = 1;
            foreach ($package->meta->ns as $ns) {
                $vars->{'ns' . $i++} = $ns;
            }
        }

        // Handle transfer request
        if (isset($vars->transfer) || isset($vars->transferAuthInfo)) {
            $fields = Configure::get('Internetbs.transfer_fields');

            // We should already have the domain name don't make editable
            $fields['Domain']['type'] = 'hidden';
            $fields['Domain']['label'] = null;

            return $this->arrayToModuleFields($fields, null, $vars);
        } else {
            // Handle domain registration
            $fields = array_merge(
                Configure::get('Internetbs.nameserver_fields'),
                Configure::get('Internetbs.domain_fields')
            );
            $module_fields = $this->arrayToModuleFields($fields, null, $vars);

            // We should already have the domain name don't make editable
            $fields['Domain']['type'] = 'hidden';
            $fields['Domain']['label'] = null;

            // Build the domain fields
            $domain_fields = $this->buildDomainModuleFields($vars);
            if ($domain_fields) {
                $module_fields = $domain_fields;
            }
        }

        // Determine whether this is an AJAX request
        return ($module_fields ?? new ModuleFields());
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
            $vars->Domain = $vars->domain;
        }

        // Set default name servers
        if (!isset($vars->ns) && isset($package->meta->ns)) {
            $i = 1;
            foreach ($package->meta->ns as $ns) {
                $vars->{'ns' . $i++} = $ns;
            }
        }

        // Handle transfer request
        if (isset($vars->transfer) || isset($vars->transferAuthInfo)) {
            $fields = Configure::get('Internetbs.transfer_fields');

            // We should already have the domain name don't make editable
            $fields['Domain']['type'] = 'hidden';
            $fields['Domain']['label'] = null;

            return $this->arrayToModuleFields($fields, null, $vars);
        } else {
            // Handle domain registration
            $fields = array_merge(
                Configure::get('Internetbs.nameserver_fields'),
                Configure::get('Internetbs.domain_fields')
            );

            // We should already have the domain name don't make editable
            $fields['Domain']['type'] = 'hidden';
            $fields['Domain']['label'] = null;

            $module_fields = $this->arrayToModuleFields($fields, null, $vars);

            // Build the domain fields
            $domain_fields = $this->buildDomainModuleFields($vars, true);
            if ($domain_fields) {
                $module_fields = $domain_fields;
            }
        }

        // Determine whether this is an AJAX request
        return ($module_fields ?? new ModuleFields());
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
        if (isset($vars->Domain)) {
            $tld = $this->getTld($vars->Domain);

            $extension_fields = Configure::get('Internetbs.domain_fields' . $tld);
            if ($extension_fields) {
                // Set the fields
                if ($client) {
                    $fields = array_merge(
                        Configure::get('Internetbs.nameserver_fields'),
                        Configure::get('Internetbs.domain_fields'),
                        $extension_fields
                    );
                } else {
                    $fields = array_merge(
                        Configure::get('Internetbs.domain_fields'),
                        Configure::get('Internetbs.nameserver_fields'),
                        $extension_fields
                    );
                }

                if ($client) {
                    // We should already have the domain name don't make editable
                    $fields['Domain']['type'] = 'hidden';
                    $fields['Domain']['label'] = null;
                }

                // Build the module fields
                $module_fields = new ModuleFields();

                // Allow AJAX requests
                $ajax = $module_fields->fieldHidden('allow_ajax', 'true', ['id'=>'internetbs_allow_ajax']);
                $module_fields->setField($ajax);
                $please_select = ['' => Language::_('AppController.select.please', true)];

                foreach ($fields as $key => $field) {
                    // Build the field
                    $label = $module_fields->label(($field['label'] ?? ''), $key);

                    $type = null;
                    if ($field['type'] == 'text') {
                        $type = $module_fields->fieldText(
                            $key,
                            ($vars->{$key} ?? ''),
                            ['id' => $key]
                        );
                    } elseif ($field['type'] == 'select') {
                        $type = $module_fields->fieldSelect(
                            $key,
                            (isset($field['options']) ? $please_select + $field['options'] : $please_select),
                            ($vars->{$key} ?? ''),
                            ['id' => $key]
                        );
                    } elseif ($field['type'] == 'hidden') {
                        $type = $module_fields->fieldHidden(
                            $key,
                            ($vars->{$key} ?? ''),
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

        return ($module_fields ?? false);
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
     * Returns all fields to display to a client attempting to edit a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getClientEditFields($package, $vars = null)
    {
        return $this->getClientAddFields($package, $vars);
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
        $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

        // Load API command
        $command = new InternetbsDomain($api);

        // Check if domain is available
        $response = $command->check(['Domain' => $domain]);
        $this->processResponse($api, $response);

        $domain_availability = $response->response();

        return ($domain_availability->status ?? 'UNAVAILABLE') !== 'UNAVAILABLE';
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
        // If the domain is available for registration, then it is not available for transfer
        return !$this->checkAvailability($domain, $module_row_id);
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
        $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

        // Load API command
        $command = new InternetbsDomain($api);

        // Get domain info
        $response = $command->info(['Domain' => $domain]);
        $this->processResponse($api, $response);

        $domain_info = $response->response();

        return (array) $domain_info;
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
        $domain = $this->getServiceDomain($service);
        $module_row_id = $service->module_row_id ?? null;

        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

        // Load API command
        $command = new InternetbsDomain($api);

        // Get domain info
        $response = $command->info(['Domain' => $domain]);
        $this->processResponse($api, $response);

        $domain_info = $response->response();

        return isset($domain_info->expirationdate)
            ? date($format, strtotime($domain_info->expirationdate))
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

        if (!$response) {
            return [];
        }

        // Get all currencies
        Loader::loadModels($this, ['Currencies']);

        $currencies = [];
        $company_currencies = $this->Currencies->getAll(Configure::get('Blesta.company_id'));
        foreach ($company_currencies as $currency) {
            $currencies[$currency->code] = $currency;
        }
        $response->currency = $response->currency ?? 'USD';

        // Format pricing
        $tld_yearly_prices = [];
        $categories = [
            'registration' => 'register',
            'transfer' => 'transfer',
            'renewal' => 'renew'
        ];

        foreach ($response->product ?? [] as $product) {
            // Skip if the product doesn't have any pricing
            if (empty($product->period)) {
                continue;
            }

            // Skip if the product doesn't match any of the allowed categories
            if (!array_key_exists($product->operation ?? '', $categories)) {
                continue;
            }

            // Get the TLD
            $tld = '.' . ltrim($product->type, '.');
            if (!isset($tld_yearly_prices[$tld])) {
                $tld_yearly_prices[$tld] = [];
            }

            // Filter by 'tlds'
            if (isset($filters['tlds']) && !in_array($tld, $filters['tlds'])) {
                continue;
            }

            // Validate if the reseller currency exists in the company
            if (!isset($currencies[$response->currency])) {
                $this->Input->setErrors(
                    [
                        'currency' => [
                            'not_exists' => Language::_('Internetbs.!error.currency.not_exists', true)
                        ]
                    ]
                );

                return;
            }

            foreach ($product->period ?? [] as $duration => $price_per_year) {
                // Filter by 'terms'
                if (isset($filters['terms']) && !in_array($duration, $filters['terms'])) {
                    continue;
                }

                foreach ($currencies ?? [] as $currency) {
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

                    $tld_yearly_prices[$tld][$currency->code][$duration][$categories[$product->operation]] = $this->Currencies->convert(
                        $price_per_year * $duration,
                        $response->currency,
                        $currency->code,
                        Configure::get('Blesta.company_id')
                    );
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
            Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'internetbs' . DS
        );

        if ($cache) {
            $response = unserialize(base64_decode($cache));
        }

        // Get remote price list
        if (!isset($response)) {
            $row = $this->getModuleRow($module_row_id);
            if (!$row) {
                $rows = $this->getModuleRows();
                if (isset($rows[0])) {
                    $row = $rows[0];
                }
                unset($rows);
            }
            $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

            // Load API command
            $command = new InternetbsAccount($api);

            // Get domain price list
            $price_list = $command->getPriceList(['version' => 2]);
            $this->processResponse($api, $price_list);
            $response = $price_list->response();

            // Save pricing in cache
            if (Configure::get('Caching.on')
                && is_writable(CACHEDIR)
                && $price_list->status() == 200
            ) {
                try {
                    Cache::writeCache(
                        'tlds_prices',
                        base64_encode(serialize($response)),
                        strtotime(Configure::get('Blesta.cache_length')) - time(),
                        Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'internetbs' . DS
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
        $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

        // Load API command
        $command = new InternetbsDomain($api);

        // Register domain
        $response = $command->create(array_merge([
            'Domain' => $domain,
            'Period' => isset($vars['qty']) ? ($vars['qty'] . 'Y') : '1Y'
        ], $vars));
        $this->processResponse($api, $response);

        return ($response->status() == 200);
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
        $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

        // Load API command
        $command = new InternetbsDomain($api);

        // Renew domain
        $response = $command->renew([
            'Domain' => $domain,
            'Period' => isset($vars['qty']) ? ($vars['qty'] . 'Y') : '1Y'
        ]);
        $this->processResponse($api, $response);

        return ($response->status() == 200);
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
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

        // Load API command
        $command = new InternetbsDomain($api);

        // Transfer domain
        $response = $command->transfer([
            'Domain' => $domain,
            'transferAuthInfo' => $vars['epp_code'] ?? '',
            'senderEmail' => 'noreply@' . Configure::get('Blesta.company')->hostname,
            'senderName' => Configure::get('Blesta.company')->name
        ]);
        $this->processResponse($api, $response);

        return ($response->status() == 200);
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
        $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

        // Load API command
        $command = new InternetbsDomain($api);

        // Get contacts
        $contact_types = ['Registrant', 'Admin', 'Technical', 'Billing'];
        $fields_map = [
            'email' => 'email',
            'phonenumber' => 'phone',
            'firstname' => 'first_name',
            'lastname' => 'last_name',
            'street' => 'address1',
            'street2' => 'address2',
            'street3' => 'state',
            'city' => 'city',
            'countrycode' => 'country',
            'postalcode' => 'zip'
        ];

        $response = $command->info(['Domain' => $domain]);
        $this->processResponse($api, $response);

        $domain_info = $response->response();

        $contacts = [];
        foreach ($contact_types as $contact_type) {
            $contact = [
                'external_id' => $contact_type
            ];
            $type = strtolower($contact_type);

            foreach ($domain_info->contacts->{$type} as $contact_field => $contact_value) {
                if (isset($fields_map[$contact_field])) {
                    $contact[$fields_map[$contact_field]] = $contact_value;
                }
            }

            $contacts[] = $contact;
        }

        foreach ($domain_info->contacts as $key => $value) {
            if (is_scalar($value)) {
                $contacts[$key] = $value;
            }
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
        $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

        // Load API command
        $command = new InternetbsDomain($api);

        // Get lock status
        $response = $command->lockStatus(['Domain' => $domain]);
        $this->processResponse($api, $response);

        $status = $response->response();

        return ($status->registrar_lock_status == 'LOCKED');
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
        $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

        // Load API command
        $command = new InternetbsDomain($api);

        // Search for domain
        $response = $command->list([
            'searchTermFilter' => $domain,
            'CompactList' => 'no',
            'ReturnFields' => 'NSList'
        ]);
        $this->processResponse($api, $response);

        $domain_info = $response->response();

        // Get current domain
        $current_domain = null;
        if (($domain_info->domaincount ?? 0) == 1) {
            $current_domain = $domain_info->domain[0] ?? (object) [];
        } else {
            foreach ($domain_info->domain as $domain_match) {
                if ($domain_match->name == $domain) {
                    $current_domain = $domain_match;
                    break;
                }
            }
        }

        // Get NS list
        $ns_list = [];
        if (isset($current_domain->nslist)) {
            $ns = explode(',', $current_domain->nslist);

            foreach ($ns as $url) {
                $ns_list[] = [
                    'url' => trim($url),
                    'ips' => [gethostbyname(trim($url))]
                ];
            }
        }

        return $ns_list;
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
        $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

        // Load API command
        $command = new InternetbsDomain($api);

        // Lock domain
        $response = $command->lock(['Domain' => $domain]);
        $this->processResponse($api, $response);

        return ($response->status() == 200);
    }

    /**
     * Resend domain transfer verification email
     *
     * @param string $domain The domain for which to resend the email
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the email was successfully sent, false otherwise
     */
    public function resendTransferEmail($domain, $module_row_id = null)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

        // Load API command
        $command = new InternetbsDomain($api);

        // Resend authorization email
        $response = $command->resendAuthEmail(['Domain' => $domain]);
        $this->processResponse($api, $response);

        return ($response->status() == 200);
    }

    /**
     * Restore a domain through the registrar
     *
     * @param string $domain The domain to restore
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @param array $vars A list of vars to submit with the restore request
     *
     *  - * The contents of $vars vary depending on the registrar
     * @return bool True if the domain was successfully restored, false otherwise
     */
    public function restoreDomain($domain, $module_row_id = null, array $vars = [])
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

        // Load API command
        $command = new InternetbsDomain($api);

        // Restore domain
        $response = $command->restore(['Domain' => $domain]);
        $this->processResponse($api, $response);

        return ($response->status() == 200);
    }

    /**
     * Send domain transfer auth code to admin email
     *
     * @param string $domain The domain for which to send the email
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the email was successfully sent, false otherwise
     */
    public function sendEppEmail($domain, $module_row_id = null)
    {
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return false;
    }

    /**
     * Updates the list of contacts associated with a domain
     *
     * @param string $domain The domain for which to update contact info
     * @param array $vars A list of contact arrays with the following information:
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
     *  - * Other fields required by the registrar
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the contacts were updated, false otherwise
     */
    public function setDomainContacts($domain, array $vars = [], $module_row_id = null)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

        // Load API command
        $command = new InternetbsDomain($api);

        // Build contacts array
        $contacts = [];
        foreach ($vars as $contact) {
            $contact_type = $contact['external_id'];
            unset($contact['external_id']);

            $fields_map = [
                'email' => 'Email',
                'phone' => 'PhoneNumber',
                'first_name' => 'FirstName',
                'last_name' => 'LastName',
                'address1' => 'Street',
                'address2' => 'Street2',
                'state' => 'Street3',
                'city' => 'City',
                'country' => 'CountryCode',
                'zip' => 'PostalCode'
            ];

            foreach ($fields_map as $local_field => $remote_field) {
                $contacts[$contact_type . '_' . $remote_field] = $contact[$local_field] ?? '';
            }
        }

        foreach ($vars as $field => $value) {
            if (is_scalar($value)) {
                $contacts[$field] = $value;
            }
        }

        // Set domain nameservers
        $response = $command->update(array_merge([
            'Domain' => $domain
        ], $contacts));
        $this->processResponse($api, $response);

        return ($response->status() == 200);
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
        $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

        // Load API command
        $command = new InternetbsDomain($api);

        // Set domain nameservers
        $response = $command->update([
            'Domain' => $domain,
            'Ns_list' => count($vars) > 1 ? implode(',', $vars) : reset($vars)
        ]);
        $this->processResponse($api, $response);

        return ($response->status() == 200);
    }

    /**
     * Assigns new ips to a name server
     *
     * @param array $vars A list of name servers and their new ips
     *
     *  - nsx => [ip1, ip2]
     * @param int|null $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the name servers were successfully updated, false otherwise
     */
    public function setNameserverIps(array $vars = [], $module_row_id = null)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

        // Load API command
        $command = new InternetbsDomainHost($api);

        // Set nameservers
        foreach ($vars as $host => $ip_list) {
            $response = $command->create([
                'Host' => $host,
                'IP_List' => count($ip_list) > 1 ? implode(',', $ip_list) : reset($ip_list)
            ]);
            $this->processResponse($api, $response);
        }

        return ($response->status() == 200);
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
        $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

        // Load API command
        $command = new InternetbsDomain($api);

        // Unlock domain
        $response = $command->unlock(['Domain' => $domain]);
        $this->processResponse($api, $response);

        return ($response->status() == 200);
    }

    /**
     * Set a new domain transfer auth code
     *
     * @param string $domain The domain for which to update the code
     * @param string $epp_code The new epp auth code to use
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @param array $vars A list of vars to submit with the update request
     *
     *  - * The contents of $vars vary depending on the registrar
     * @return bool True if the code was successfully updated, false otherwise
     */
    public function updateEppCode($domain, $epp_code, $module_row_id = null, array $vars = [])
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->api_key, $row->meta->password, $row->meta->sandbox);

        // Load API command
        $command = new InternetbsDomain($api);

        // Update the domain EPP code
        $response = $command->update([
            'Domain' => $domain,
            'transferAuthInfo' => $epp_code
        ]);
        $this->processResponse($api, $response);

        return ($response->status() == 200);
    }

    /**
     * Get a list of the TLDs supported by the registrar module
     *
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return array A list of all TLDs supported by the registrar module
     */
    public function getTlds($module_row_id = null)
    {
        try {
            $response = $this->getRawTldData($module_row_id);
            $tlds = [];
            $categories = [
                'registration' => 'register',
                'transfer' => 'transfer',
                'renewal' => 'renew'
            ];

            foreach ($response->product as $product) {
                // Skip if the product doesn't have any pricing
                if (empty($product->period)) {
                    continue;
                }

                // Skip if the product doesn't match any of the allowed categories
                if (!array_key_exists($product->operation ?? '', $categories)) {
                    continue;
                }

                // Get the TLD
                $tld = '.' . ltrim($product->type, '.');
                if (!isset($tlds[$tld])) {
                    $tlds[] = $tld;
                }
            }

            return $tlds;
        } catch (Throwable $e) {
            return Configure::get('Internetbs.tlds');
        }
    }

    /**
     * Initialize the API library
     *
     * @param string $api_key The Internet.bs API key
     * @param string $password The Internet.bs API password
     * @param string $sandbox 'true' to use the Sandbox API
     * @return InternetbsApi The InternetbsApi instance, or false if the loader fails to load the file
     */
    private function getApi($api_key, $password, $sandbox = 'false')
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'internetbs_api.php');

        return new InternetbsApi($api_key, $password, $sandbox == 'true');
    }

    /**
     * Process API response, setting an errors, and logging the request
     *
     * @param InternetbsApi $api The Internet.bs API object
     * @param InternetbsResponse $response The Internet.bs API response object
     */
    private function processResponse(InternetbsApi $api, InternetbsResponse $response)
    {
        // Set errors, if any
        if ($response->status() != 200) {
            $errors = $response->errors() ?? [];
            $this->Input->setErrors(['errors' => (array) $errors]);
        }

        $last_request = $api->lastRequest();
        $this->log($last_request['url'], serialize($last_request['args'] ?? []), 'input', true);
        $this->log($last_request['url'], $response->raw(), 'output', $response->status() == 200);
    }
}
