<?php
/**
 * OpenSRS Module
 *
 * @package blesta
 * @subpackage blesta.components.modules.opensrs
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Opensrs extends RegistrarModule
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
        Language::loadLang('opensrs', null, dirname(__FILE__) . DS . 'language' . DS);

        Configure::load('opensrs', dirname(__FILE__) . DS . 'config' . DS);
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
        $fr_fields = Configure::get('Opensrs.domain_fields.fr');
        $rules = [
            'tld_data[registrant_extra_info][registrant_type]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [
                        'in_array',
                        array_keys($fr_fields['tld_data[registrant_extra_info][registrant_type]']['options'])
                    ],
                    'message' => Language::_('Opensrs.!error.registrant_type.format', true)
                ]
            ],
            'tld_data[registrant_extra_info][registrant_vat_id]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Opensrs.!error.registrant_vat_id.format', true)
                ]
            ],
            'tld_data[registrant_extra_info][siren_siret]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Opensrs.!error.siren_siret.format', true)
                ]
            ],
            'tld_data[registrant_extra_info][trademark_number]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Opensrs.!error.trademark_number.format', true)
                ]
            ]
        ];

        // Set required fields based on the selected .fr TLD legal type
        if (isset($vars['tld_data']['registrant_extra_info']['registrant_type'])) {
            $company_fields = [
                'tld_data[registrant_extra_info][registrant_vat_id]',
                'tld_data[registrant_extra_info][siren_siret]',
                'tld_data[registrant_extra_info][trademark_number]'
            ];

            if ($vars['tld_data']['registrant_extra_info']['registrant_type'] == 'organization') {
                foreach ($company_fields as $field) {
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
        $tld = null;
        $input_fields = [];

        // Set domain fields
        if (isset($vars['domain'])) {
            $tld = $this->getTld($vars['domain']);
        }

        $whois_fields = Configure::get('Opensrs.whois_fields');
        if (array_key_exists('auth_info', $vars)) {
            $input_fields = array_merge(
                Configure::get('Opensrs.transfer_fields'),
                Configure::get('Opensrs.nameserver_fields'),
                $whois_fields,
                (array)Configure::get('Opensrs.domain_fields' . $tld)
            );
        } else {
            $input_fields = array_merge(
                Configure::get('Opensrs.domain_fields'),
                Configure::get('Opensrs.nameserver_fields'),
                $whois_fields,
                (array)Configure::get('Opensrs.domain_fields' . $tld)
            );
        }

        if (isset($vars['use_module']) && $vars['use_module'] == 'true') {
            // Set registration period
            $vars['period'] = 1;
            foreach ($package->pricing as $pricing) {
                if ($pricing->id == $vars['pricing_id']) {
                    $vars['period'] = $pricing->term;
                    break;
                }
            }

            // Set all whois info from client
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
                if (str_contains($key, 'first_name')) {
                    $vars[$key] = $client->first_name;
                } elseif (str_contains($key, 'last_name')) {
                    $vars[$key] = $client->last_name;
                } elseif (str_contains($key, 'org_name')) {
                    $vars[$key] = $client->company;
                } elseif (str_contains($key, 'address1')) {
                    $vars[$key] = $client->address1;
                } elseif (str_contains($key, 'address2')) {
                    $vars[$key] = $client->address2;
                } elseif (str_contains($key, 'city')) {
                    $vars[$key] = $client->city;
                } elseif (str_contains($key, 'state')) {
                    $vars[$key] = $client->state;
                } elseif (str_contains($key, 'postal_code')) {
                    $vars[$key] = $client->zip;
                } elseif (str_contains($key, 'country')) {
                    $vars[$key] = $client->country;
                } elseif (str_contains($key, 'phone')) {
                    $vars[$key] = trim($this->formatPhone(
                        isset($contact_numbers[0]) ? $contact_numbers[0]->number : '11111111111',
                        $client->country
                    ));
                } elseif (str_contains($key, 'email')) {
                    $vars[$key] = $client->email;
                }

                if (empty($vars[$key])) {
                    if (str_contains($key, 'postal_code')) {
                        $vars[$key] = '33064';
                    } else {
                        $vars[$key] = 'NA';
                    }
                }
            }

            // Set country for .asia domains
            if ($tld == '.asia') {
                $vars['tld_data[ced_info][locality_country]'] = $client->country;
            }

            // Build contacts array
            Loader::loadHelpers($this, ['DataStructure']);
            $this->Array = $this->DataStructure->create('Array');

            $fields = $this->Array->unflatten(
                array_intersect_key($this->Array->flatten($vars), $input_fields)
            );
            $fields['client_id'] = $vars['client_id'];

            // Register domain
            $this->registerDomain($vars['domain'], $package->module_row, $fields);

            if ($this->Input->errors()) {
                return;
            }

            // Set nameservers
            $this->setDomainNameservers($vars['domain'], $package->module_row, [
                $fields['nameserver_list'][0]['name'] ?? '',
                $fields['nameserver_list'][1]['name'] ?? '',
                $fields['nameserver_list'][2]['name'] ?? '',
                $fields['nameserver_list'][3]['name'] ?? '',
            ]);

            // Ignore nameserver errors
            $this->Input->setErrors([]);

            return [['key' => 'domain', 'value' => $vars['domain'], 'encrypted' => 0]];
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
        $fields = $this->serviceFieldsToObject($service->fields);

        // Set expiration year
        $expiration_date = $this->getExpirationDate($service);
        $vars = [
            'currentexpirationyear' => date('Y', strtotime(
                !empty($expiration_date) ? $expiration_date : $service->date_renews
            )),
            'period' => 1
        ];

        // Set renew period
        foreach ($package->pricing as $pricing) {
            if ($pricing->id == $service->pricing_id) {
                $vars['period'] = $pricing->term;
                break;
            }
        }

        // Renew domain
        $this->renewDomain($fields->domain, $package->module_row, $vars);

        return null;
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'opensrs' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'opensrs' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'opensrs' . DS);

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

        // Set all TLD checkboxes
        $tld_options = $fields->label(Language::_('Opensrs.package_fields.tld_options', true));

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
            $type = $fields->label(Language::_('Opensrs.package_fields.ns' . $i, true), 'opensrs_ns' . $i);
            $type->attach(
                $fields->fieldText(
                    'meta[ns][]',
                    ($vars->meta['ns'][$i - 1] ?? null),
                    ['id' => 'opensrs_ns' . $i]
                )
            );
            $fields->setField($type);
        }

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

        // Set default name servers
        if (!isset($vars->ns1) && isset($package->meta->ns)) {
            $i = 0;
            foreach ($package->meta->ns as $ns) {
                $vars->{'nameserver_list[' . $i++ . '][name]'} = $ns;
            }
        }

        // Handle transfer request
        if (isset($vars->transfer) || isset($vars->auth_code)) {
            return $this->arrayToModuleFields(Configure::get('Opensrs.transfer_fields'), null, $vars);
        } else {
            // Set domain fields
            if (isset($vars->domain)) {
                $tld = $this->getTld($vars->domain);
            }

            // Handle domain registration
            $module_fields = $this->arrayToModuleFields(
                array_merge(
                    Configure::get('Opensrs.domain_fields'),
                    Configure::get('Opensrs.nameserver_fields'),
                    isset($tld) ? (array)Configure::get('Opensrs.domain_fields' . $tld) : []
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

        return ($module_fields ?? new ModuleFields());
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
        // Set default name servers
        if (!isset($vars->ns) && isset($package->meta->ns)) {
            $i = 0;
            foreach ($package->meta->ns as $ns) {
                $vars->{'nameserver_list[' . $i++ . '][name]'} = $ns;
            }
        }

        // Handle transfer request
        if (isset($vars->transfer) || isset($vars->auth_code)) {
            $fields = Configure::get('Opensrs.transfer_fields');

            // We should already have the domain name don't make editable
            $fields['domain']['type'] = 'hidden';
            $fields['domain']['label'] = null;

            return $this->arrayToModuleFields($fields, null, $vars);
        } else {
            // Set domain fields
            if (isset($vars->domain)) {
                $tld = $this->getTld($vars->domain);
            }

            // Handle domain registration
            $fields = array_merge(
                Configure::get('Opensrs.nameserver_fields'),
                Configure::get('Opensrs.domain_fields'),
                isset($tld) ? Configure::get('Opensrs.domain_fields' . $tld) : []
            );

            // We should already have the domain name don't make editable
            $fields['domain']['type'] = 'hidden';
            $fields['domain']['label'] = null;

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
        if (isset($vars->domain)) {
            $tld = $this->getTld($vars->domain);

            $extension_fields = Configure::get('Opensrs.domain_fields' . $tld);
            if ($extension_fields) {
                // Set the fields
                if ($client) {
                    $fields = array_merge(
                        Configure::get('Opensrs.nameserver_fields'),
                        Configure::get('Opensrs.domain_fields'),
                        $extension_fields
                    );
                } else {
                    $fields = array_merge(
                        Configure::get('Opensrs.domain_fields'),
                        Configure::get('Opensrs.nameserver_fields'),
                        $extension_fields
                    );
                }

                if ($client) {
                    // We should already have the domain name don't make editable
                    $fields['domain']['type'] = 'hidden';
                    $fields['domain']['label'] = null;
                }

                // Build the module fields
                $module_fields = new ModuleFields();

                // Allow AJAX requests
                $ajax = $module_fields->fieldHidden('allow_ajax', 'true', ['id' => 'opensrs_allow_ajax']);
                $module_fields->setField($ajax);
                $please_select = ['' => Language::_('AppController.select.please', true)];

                foreach ($fields as $key => $field) {
                    // Build the field
                    $label = $module_fields->label((isset($field['label']) ? $field['label'] : ''), $key);

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
     * Returns all tabs to display to an admin when managing a service
     *
     * @param stdClass $service A stdClass object representing the service
     * @return array An array of tabs in the format of method => title.
     *  Example: ['methodName' => "Title", 'methodName2' => "Title2"]
     */
    public function getAdminServiceTabs($service)
    {
        Loader::loadModels($this, ['Packages']);

        $tabs = [
            'tabWhois' => Language::_('Opensrs.tab_whois.title', true),
            'tabNameservers' => Language::_('Opensrs.tab_nameservers.title', true),
            'tabSettings' => Language::_('Opensrs.tab_settings.title', true)
        ];

        return $tabs;
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
        Loader::loadModels($this, ['Packages']);

        $tabs = [
            'tabClientWhois' => [
                'name' => Language::_('Opensrs.tab_whois.title', true),
                'icon' => 'fas fa-users'
            ],
            'tabClientNameservers' => [
                'name' => Language::_('Opensrs.tab_nameservers.title', true),
                'icon' => 'fas fa-server'
            ],
            'tabClientSettings' => [
                'name' => Language::_('Opensrs.tab_settings.title', true),
                'icon' => 'fas fa-cog'
            ]
        ];

        return $tabs;
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

        $vars = new stdClass();
        $whois_fields = Configure::get('Opensrs.whois_fields');
        $fields = $this->serviceFieldsToObject($service->fields);

        if (!empty($post)) {
            // Build contacts array
            Loader::loadHelpers($this, ['DataStructure']);
            $this->Array = $this->DataStructure->create('Array');

            $post = $this->Array->unflatten(
                array_intersect_key($this->Array->flatten($post), $whois_fields)
            );

            // Update contacts
            $contacts = [];
            foreach ($post['contact_set'] as $type => $contact) {
                $contact['external_id'] = $type;
                $contacts[] = $contact;
            }

            $this->setDomainContacts($fields->domain, $contacts, $package->module_row);

            $vars = (object)$this->Array->flatten($post);
        } else {
            // Build contacts array
            Loader::loadHelpers($this, ['DataStructure']);
            $this->Array = $this->DataStructure->create('Array');

            $contacts = $this->getDomainContacts($fields->domain, $package->module_row);
            $data = ['contact_set' => []];
            foreach ($contacts as $contact) {
                $data['contact_set'][$contact->external_id] = (array) $contact;
            }
            $data = $this->Array->flatten($data);

            // Format fields
            foreach ($data as $name => $value) {
                // Value must be a string
                if (!is_scalar($value)) {
                    $value = '';
                }
                $vars->{$name} = $value;
            }
        }

        $this->view->set('vars', $vars);
        $this->view->set('fields', $this->arrayToModuleFields($whois_fields, null, $vars)->getFields());
        $this->view->set('sections', ['owner', 'admin', 'tech', 'billing']);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'opensrs' . DS);

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
        $fields = $this->serviceFieldsToObject($service->fields);

        if (!empty($post)) {
            // Update domain nameservers
            $this->setDomainNameservers($fields->domain, $package->module_row, ($post['ns'] ?? []));

            $vars = (object)$post;
        } else {
            // Get domain nameservers
            $nameservers = $this->getDomainNameServers($fields->domain, $package->module_row);

            $vars->ns = [];
            if (!empty($nameservers)) {
                foreach ($nameservers as $ns) {
                    $vars->ns[] = $ns['url'];
                }
            }
        }

        $this->view->set('vars', $vars);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'opensrs' . DS);

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

        $row = $this->getModuleRow($package->module_row);
        $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');

        $vars = new stdClass();
        $fields = $this->serviceFieldsToObject($service->fields);

        // Determine if this service has access to id_protection
        $id_protection = $this->featureServiceEnabled('id_protection', $service);
        
        // Determine if this service has access to epp_code
        $epp_code = $package->meta->epp_code ?? '0';

        if (!empty($post)) {
            // Set domain status
            if ($post['registrar_lock'] == 'true') {
                $this->lockDomain($fields->domain, $package->module_row);
            } else {
                $this->unlockDomain($fields->domain, $package->module_row);
            }

            // Set whois privacy status
            $domains_provisioning = new OpensrsDomainsProvisioning($api);
            $response = $domains_provisioning->modify([
                'domain' => $fields->domain,
                'data' => 'whois_privacy_state',
                'affect_domains' => '0',
                'state' => $post['whois_privacy_state'] == 'true' ? 'Y' : 'N'
            ]);
            $this->processResponse($api, $response);

            // Send EPP code
            if (($post['request_epp'] ?? 'false') == 'true') {
                $domains = new OpensrsDomains($api);
                $response = $domains->sendAuthcode([
                    'domain_name' => $fields->domain
                ]);
                $this->processResponse($api, $response);
            }

            $vars = (object)$post;
        } else {
            $vars->registrar_lock = $this->getDomainIsLocked($fields->domain, $package->module_row) ? 'true' : 'false';
            $vars->whois_privacy_state = $this->getDomainIsPrivate($fields->domain, $package->module_row)
                ? 'true'
                : 'false';
        }

        $this->view->set('id_protection', $id_protection);
        $this->view->set('epp_code', $epp_code);
        $this->view->set('vars', $vars);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'opensrs' . DS);

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

        $domains = new OpensrsDomains($api);
        $result = $domains->lookup(['domain' => $domain]);
        $response = $result->response();

        $this->logRequest($api, $result);

        if ($result->status() != 'OK') {
            return false;
        }

        return strtolower($response->attributes['status']) == 'available';
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
        // Prevent users from transferring an unregistered domain
        return !$this->checkAvailability($domain, $module_row_id);
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
        $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');

        $domains = new OpensrsDomains($api);
        $response = $domains->get([
            'domain' => $domain,
            'type' => 'all_info'
        ]);
        $this->processResponse($api, $response);
        $response = $response->response();

        $contacts = $response->attributes['contact_set'] ?? [];
        foreach ($contacts as $type => &$contact) {
            $contact['phone'] = $this->formatPhone($contact['phone'], $contact['country']);
            $contact['zip'] = $contact['postal_code'] ?? '00000';
            $contact['external_id'] = $type;
            $contact = (object)$contact;
        }

        return array_values($contacts);
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
        $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');

        $domains_provisioning = new OpensrsDomainsProvisioning($api);

        // Build contact set
        $contact_set = [];
        foreach ($vars as $contact) {
            $contact['phone'] = $this->formatPhone($contact['phone'], $contact['country']);
            $contact['postal_code'] = $contact['zip'] ?? '00000';
            $contact_set[$contact['external_id'] ?? 'owner'] = $contact;
        }

        // Update domain
        $response = $domains_provisioning->modify([
            'domain' => $domain,
            'data' => 'contact_info',
            'affect_domains' => '0',
            'contact_set' => $contact_set
        ]);
        $this->processResponse($api, $response);

        return $response->status() == 'OK';
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
        $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');

        $domains = new OpensrsDomains($api);
        $response = $domains->get([
            'domain' => $domain,
            'type' => 'all_info'
        ]);
        $this->processResponse($api, $response);
        $response = $response->response();

        return $response->attributes ?? [];
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
        $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');

        $domains = new OpensrsDomains($api);
        $response = $domains->get([
            'domain' => $domain,
            'type' => 'status'
        ]);
        $this->processResponse($api, $response);
        $response = $response->response();

        return $response->attributes['lock_state'] == '1';
    }

    /**
     * Returns whether the domain has WHOIS privacy enabled
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the domain has a registrar lock, false otherwise
     */
    private function getDomainIsPrivate($domain, $module_row_id = null)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');

        $domains = new OpensrsDomains($api);
        $response = $domains->get([
            'domain' => $domain,
            'type' => 'whois_privacy_state'
        ]);
        $this->processResponse($api, $response);
        $response = $response->response();

        return $response->attributes['state'] == 'enabled';
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
        $domain_info = $this->getDomainInfo($domain, $module_row_id);

        $nameservers = [];
        foreach ($domain_info['nameserver_list'] as $nameserver) {
            $nameservers[] = [
                'url' => $nameserver['name'] ?? '',
                'ips' => [$nameserver['ipaddress'] ?? gethostbyname($nameserver['name'] ?? '')]
            ];
        }

        return $nameservers;
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
        $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');

        $domains_provisioning = new OpensrsDomainsNs($api);

        // Remove empty nameservers
        foreach ($vars as $key => $ns) {
            if (empty($ns)) {
                unset($vars[$key]);
            }
        }

        // Update domain
        $response = $domains_provisioning->advancedUpdateNameserver([
            'domain' => $domain,
            'op_type' => 'assign',
            'assign_ns' => array_values($vars)
        ]);
        $this->processResponse($api, $response);

        return $response->status() == 'OK';
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
        $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');

        $domains_provisioning = new OpensrsDomainsProvisioning($api);

        // Update domain
        $response = $domains_provisioning->modify([
            'data' => 'status',
            'domain' => $domain,
            'lock_state' => '1',
            'affect_domains' => '0'
        ]);
        $this->processResponse($api, $response);

        return $response->status() == 'OK';
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
        $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');

        $domains_provisioning = new OpensrsDomainsProvisioning($api);

        // Update domain
        $response = $domains_provisioning->modify([
            'data' => 'status',
            'domain' => $domain,
            'lock_state' => '0',
            'affect_domains' => '0'
        ]);
        $this->processResponse($api, $response);

        return $response->status() == 'OK';
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
        $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');

        // Set all whois info from client
        if (!isset($this->Clients)) {
            Loader::loadModels($this, ['Clients']);
        }
        $client = $this->Clients->get($vars['client_id'] ?? null);
        unset($vars['client_id']);

        // Set registration parameters
        $params = [
            'domain' => $domain,
            'auto_renew' => 0,
            'reg_type' => isset($vars['auth_info']) ? 'transfer' : 'new',
            'reg_username' => 'usr' . ($client->id_value ?? $client->id ?? rand(10000, 99999)),
            'reg_password' => substr(base64_encode(md5($client->id_value)), 0, 15),
            'handle' => 'process'
        ];
        $fields = array_merge($params, $vars);

        // Register domain
        $domains = new OpensrsDomainsProvisioning($api);
        $response = $domains->swRegister($fields);
        $this->processResponse($api, $response);

        return $response->status() == 'OK';
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
        $vars['reg_type'] = 'transfer';

        return $this->registerDomain($domain, $module_row_id, $vars);
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
        $api = $this->getApi($row->meta->user, $row->meta->key, $row->meta->sandbox == 'true');

        $params = [
            'handle' => 'process',
            'domain' => $domain,
            'currentexpirationyear' => date('Y'),
            'period' => 1
        ];
        $fields = array_merge($params, $vars);

        $domains = new OpensrsDomainsProvisioning($api);
        $response = $domains->renew($fields);
        $this->processResponse($api, $response);

        return $response->status() == 'OK';
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

        $domains = new OpensrsDomains($api);
        $result = $domains->get(['domain' => $domain, 'type' => 'all_info']);
        $this->processResponse($api, $result);

        if ($result->status() != 'OK') {
            return false;
        }
        $response = $result->response();

        return $this->Date->format($format, $response->attributes['expiredate'] ?? date('c'));
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
        return Configure::get('Opensrs.tlds');
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
                    'message' => Language::_('Opensrs.!error.user.valid', true)
                ]
            ],
            'key' => [
                'valid' => [
                    'last' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Opensrs.!error.key.valid', true)
                ],
                'valid_connection' => [
                    'rule' => [
                        [$this, 'validateConnection'],
                        $vars['user'],
                        $vars['sandbox'] ?? 'false'
                    ],
                    'message' => Language::_('Opensrs.!error.key.valid_connection', true)
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
        $domains = new OpensrsDomains($api);
        $response = $domains->lookup(['domain' => 'blesta.com'])->response();

        return $response->is_success == '1';
    }

    /**
     * Initializes the OpensrsApi and returns an instance of that object
     *
     * @param string $username The user to connect as
     * @param string $key The key to use when connecting
     * @param bool $sandbox Whether or not to process in sandbox mode (for testing)
     * @return OpensrsApi The OpensrsApi instance
     */
    private function getApi($username, $key, $sandbox)
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'opensrs_api.php');

        return new OpensrsApi($username, $key, $sandbox);
    }

    /**
     * Process API response, setting an errors, and logging the request
     *
     * @param OpensrsApi $api The opensrs API object
     * @param OpensrsResponse $response The opensrs API response object
     */
    private function processResponse(OpensrsApi $api, OpensrsResponse $response)
    {
        $this->logRequest($api, $response);

        // Set errors, if any
        if ($response->status() != 'OK') {
            $errors = isset($response->errors()->response_text) ? $response->errors()->response_text : '';
            $this->Input->setErrors(['errors' => [$errors]]);
        }
    }

    /**
     * Logs the API request
     *
     * @param OpensrsApi $api The opensrs API object
     * @param OpensrsResponse $response The opensrs API response object
     */
    private function logRequest(OpensrsApi $api, OpensrsResponse $response)
    {
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

        $number = preg_replace('/[^0-9+]+/', '', $number);

        return trim($this->Contacts->intlNumber($number, $country, '.'));
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
}
