<?php
/**
 * Logicboxes Module
 *
 * @package blesta
 * @subpackage blesta.components.modules.logicboxes
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Logicboxes extends RegistrarModule
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
        Language::loadLang('logicboxes', null, dirname(__FILE__) . DS . 'language' . DS);

        Configure::load('logicboxes', dirname(__FILE__) . DS . 'config' . DS);
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
                Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'logicboxes' . DS
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
        $api = $this->getApi($row->meta->reseller_id, $row->meta->key, $row->meta->sandbox == 'true');

        #
        # TODO: Handle validation checks
        #

        $tld = null;
        $input_fields = [];

        if (isset($vars['domain-name'])) {
            $tld = $this->getTld($vars['domain-name'], true);
        }

        if ($package->meta->type == 'domain') {
            $contact_fields = Configure::get('Logicboxes.contact_fields');
            $customer_fields = Configure::get('Logicboxes.customer_fields');
            $domain_field_basics = [
                'years' => true,
                'ns' => true,
                'customer-id' => true,
                'reg-contact-id' => true,
                'admin-contact-id' => true,
                'tech-contact-id' => true,
                'billing-contact-id' => true,
                'invoice-option' => true,
                'protect-privacy' => true
            ];
            $transfer_fields = array_merge(Configure::get('Logicboxes.transfer_fields'), $domain_field_basics);
            $domain_fields = array_merge(Configure::get('Logicboxes.domain_fields'), $domain_field_basics);
            $domain_contact_fields = (array)Configure::get('Logicboxes.contact_fields' . $tld);

            $input_fields = array_merge(
                $contact_fields,
                $customer_fields,
                $transfer_fields,
                $domain_fields,
                $domain_field_basics,
                $domain_contact_fields
            );
        }

        if (isset($vars['use_module']) && $vars['use_module'] == 'true') {
            if ($package->meta->type == 'domain') {
                $api->loadCommand('logicboxes_domains');
                $domains = new LogicboxesDomains($api);

                $contact_type = $this->getContactType($tld);
                $order_id = null;
                $vars['years'] = 1;

                foreach ($package->pricing as $pricing) {
                    if ($pricing->id == $vars['pricing_id']) {
                        $vars['years'] = $pricing->term;
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
                $customer_id = $this->getCustomerId($package->module_row, $client->email);
                $numbers = $this->Contacts->getNumbers($client->contact_id, 'phone');
                $contact_id = null;

                foreach (array_merge($contact_fields, $customer_fields) as $key => $field) {
                    if ($key == 'name') {
                        $vars[$key] = $client->first_name . ' ' . $client->last_name;
                    } elseif ($key == 'company') {
                        $vars[$key] = $client->company != '' ? $client->company : 'Not Applicable';
                    } elseif ($key == 'email') {
                        $vars[$key] = $client->email;
                    } elseif ($key == 'address-line-1') {
                        $vars[$key] = $client->address1;
                    } elseif ($key == 'address-line-2') {
                        $vars[$key] = $client->address2;
                    } elseif ($key == 'city') {
                        $vars[$key] = $client->city;
                    } elseif ($key == 'state') {
                        $vars[$key] = $client->state;
                    } elseif ($key == 'zipcode') {
                        $vars[$key] = $client->zip;
                    } elseif ($key == 'country') {
                        $vars[$key] = $client->country;
                    } elseif ($key == 'phone-cc') {
                        $part = explode(
                            '.',
                            $this->formatPhone(
                                isset($numbers[0]) ? $numbers[0]->number : null,
                                $client->country
                            )
                        );
                        if (count($part) == 2) {
                            $vars[$key] = ltrim($part[0], '+');
                            $vars['phone'] = $part[1] != '' ? $part[1] : '1111111';
                        }
                    } elseif ($key == 'username') {
                        $vars[$key] = $client->email;
                    } elseif ($key == 'passwd') {
                        $vars[$key] = substr(md5(mt_rand()), 0, 15);
                    } elseif ($key == 'lang-pref') {
                        $vars[$key] = substr($client->settings['language'], 0, 2);
                    }
                }

                // Set locality for .ASIA
                if ($tld == '.asia') {
                    $vars['attr_locality'] = $client->country;
                } elseif ($tld == '.ru') {
                    $vars['attr_org-r'] = $vars['company'];
                    $vars['attr_address-r'] = $vars['address-line-1'];
                    $vars['attr_person-r'] = $vars['name'];
                }

                // Create customer if necessary
                if (!$customer_id) {
                    $customer_id = $this->createCustomer(
                        $package->module_row,
                        array_intersect_key($vars, array_merge($contact_fields, $customer_fields))
                    );
                }

                $vars['type'] = $contact_type;

                $vars['customer-id'] = $customer_id;
                $contact_id = $this->createContact(
                    $package->module_row,
                    array_intersect_key($vars, array_merge($contact_fields, $domain_contact_fields))
                );
                $vars['reg-contact-id'] = $contact_id;
                $contact_id = $this->createContact(
                    $package->module_row,
                    array_intersect_key($vars, array_merge($contact_fields, $domain_contact_fields))
                );
                $vars['admin-contact-id'] = $this->formatContact($contact_id, $tld, 'admin');
                $contact_id = $this->createContact(
                    $package->module_row,
                    array_intersect_key($vars, array_merge($contact_fields, $domain_contact_fields))
                );
                $vars['tech-contact-id'] = $this->formatContact($contact_id, $tld, 'tech');
                $contact_id = $this->createContact(
                    $package->module_row,
                    array_intersect_key($vars, array_merge($contact_fields, $domain_contact_fields))
                );
                $vars['billing-contact-id'] = $this->formatContact($contact_id, $tld, 'billing');
                $vars['invoice-option'] = 'NoInvoice';
                $vars['protect-privacy'] = 'false';

                // Handle special contact assignment case for .ASIA
                if ($tld == '.asia') {
                    $vars['attr_cedcontactid'] = $contact_id;
                } elseif ($tld == '.au') {
                    // Handle special assignment case for .AU
                    $vars['attr_eligibilityName'] = $client->company;
                    $vars['attr_registrantName'] = $client->first_name . ' ' . $client->last_name;
                }

                $vars = array_merge($vars, $this->createMap($vars));

                // Include all attr-name and attr-value fields as acceptable keys for transfer/domains
                foreach ($vars as $key => $value) {
                    if (substr($key, 0, 5) == 'attr-') {
                        $transfer_fields[$key] = '';
                        $domain_fields[$key] = '';
                    }
                }

                // Handle transfer
                if (isset($vars['transfer']) || isset($vars['auth-code'])) {
                    $response = $domains->transfer(array_intersect_key($vars, $transfer_fields));
                } else {
                    // Handle registration
                    // Set nameservers
                    $vars['ns'] = [];
                    for ($i=1; $i<=5; $i++) {
                        if (isset($vars['ns' . $i]) && $vars['ns' . $i] != '') {
                            $vars['ns'][] = $vars['ns' . $i];
                        }
                    }

                    $response = $domains->register(array_intersect_key($vars, $domain_fields));
                }

                if (isset($response->response()->entityid)) {
                    $order_id = $response->response()->entityid;
                }

                $this->processResponse($api, $response);

                if ($this->Input->errors()) {
                    return;
                }

                return [
                    ['key' => 'domain', 'value' => $vars['domain-name'], 'encrypted' => 0],
                    ['key' => 'domain-name', 'value' => $vars['domain-name'], 'encrypted' => 0],
                    ['key' => 'order-id', 'value' => $order_id, 'encrypted' => 0]
                ];
            } else {
                #
                # TODO: Create SSL cert
                #
            }
        } elseif ($status == 'active') {
            if ($package->meta->type == 'domain') {
                $api->loadCommand('logicboxes_domains');
                $domains = new LogicboxesDomains($api);

                $order = array_intersect_key($vars, ['domain-name' => '']);
                $response = $domains->orderid($order);
                $this->processResponse($api, $response);

                if ($this->Input->errors()) {
                    return;
                }

                $order_id = null;
                if ($response->response()) {
                    $order_id = $response->response();
                }

                return [
                    ['key' => 'domain', 'value' => $vars['domain-name'], 'encrypted' => 0],
                    ['key' => 'domain-name', 'value' => $vars['domain-name'], 'encrypted' => 0],
                    ['key' => 'order-id', 'value' => $order_id, 'encrypted' => 0]
                ];
            }
        }

        $meta = [];
        $fields = array_intersect_key(
            $vars,
            array_merge(['ns1' => true, 'ns2' => true, 'ns3' => true, 'ns4' => true, 'ns5' => true], $input_fields)
        );

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
        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        if (empty($service_fields->{'order-id'})) {
            // Fetch the order ID
            $row = $this->getModuleRow($package->module_row);
            $api = $this->getApi($row->meta->reseller_id, $row->meta->key, $row->meta->sandbox == 'true');

            $api->loadCommand('logicboxes_domains');
            $domains = new LogicboxesDomains($api);

            $order = array_intersect_key($vars, ['domain-name' => '']);
            $response = $domains->orderid(
                ['domain-name' => ($service_fields->{'domain-name'} ?? '')]
            );
            $this->processResponse($api, $response);

            if ($this->Input->errors()) {
                return;
            }

            $order_id = null;
            if ($response->response()) {
                $order_id = $response->response();
            }

            $fields = [
                ['key' => 'order-id', 'value' => $order_id, 'encrypted' => 0],
                ['key' => 'domain', 'value' => ($service_fields->{'domain-name'} ?? ''), 'encrypted' => 0]
            ];
            foreach ($service_fields as $key => $value) {
                $fields[] = [
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                ];
            }

            return $fields;
        }

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
        $api = $this->getApi($row->meta->reseller_id, $row->meta->key, $row->meta->sandbox == 'true');

        // Renew domain
        if ($package->meta->type == 'domain') {
            $fields = $this->serviceFieldsToObject($service->fields);

            // Load the API
            $api->loadCommand('logicboxes_domains');
            $domains = new LogicboxesDomains($api);

            $response = $domains->details(['order-id' => $fields->{'order-id'}, 'options' => ['OrderDetails']]);
            $this->processResponse($api, $response);
            $order = $response->response();

            $vars = [
                'years' => 1,
                'order-id' => $fields->{'order-id'},
                'exp-date' => $order->endtime,
                'invoice-option' => 'NoInvoice'
            ];

            foreach ($package->pricing as $pricing) {
                if ($pricing->id == $service->pricing_id) {
                    $vars['years'] = $pricing->term;
                    break;
                }
            }

            // Only process renewal if adding years today will add time to the expiry date
            if (strtotime('+' . $vars['years'] . ' years') > $order->endtime) {
                $response = $domains->renew($vars);
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'logicboxes' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'logicboxes' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'logicboxes' . DS);

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
        $meta_fields = ['registrar', 'reseller_id', 'key', 'sandbox'];
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
        // Nothing to do
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
            'domain' => Language::_('Logicboxes.package_fields.type_domain', true),
            //'ssl' => Language::_("Logicboxes.package_fields.type_ssl", true)
        ];

        // Set type of package
        $type = $fields->label(Language::_('Logicboxes.package_fields.type', true), 'logicboxes_type');
        $type->attach(
            $fields->fieldSelect(
                'meta[type]',
                $types,
                (isset($vars->meta['type']) ? $vars->meta['type'] : null),
                ['id' => 'logicboxes_type']
            )
        );
        $fields->setField($type);

        // Set all TLD checkboxes
        $tld_options = $fields->label(Language::_('Logicboxes.package_fields.tld_options', true));

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
            $type = $fields->label(Language::_('Logicboxes.package_fields.ns' . $i, true), 'logicboxes_ns' . $i);
            $type->attach(
                $fields->fieldText(
                    'meta[ns][]',
                    (isset($vars->meta['ns'][$i-1]) ? $vars->meta['ns'][$i-1] : null),
                    ['id' => 'logicboxes_ns' . $i]
                )
            );
            $fields->setField($type);
        }

        $fields->setHtml("
			<script type=\"text/javascript\">
				$(document).ready(function() {
					toggleTldOptions($('#logicboxes_type').val());

					// Re-fetch module options to toggle fields
					$('#logicboxes_type').change(function() {
						toggleTldOptions($(this).val());
					});

					function toggleTldOptions(type) {
						if (type == 'ssl')
							$('.logicboxes_tlds').hide();
						else
							$('.logicboxes_tlds').show();
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

        // Handle universal domain name
        if (isset($vars->domain)) {
            $vars->{'domain-name'} = $vars->domain;
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
            if (isset($vars->transfer) || isset($vars->{'auth-code'})) {
                return $this->arrayToModuleFields(Configure::get('Logicboxes.transfer_fields'), null, $vars);
            } else {
                // Handle domain registration
                $module_fields = $this->arrayToModuleFields(
                    array_merge(
                        Configure::get('Logicboxes.domain_fields'),
                        Configure::get('Logicboxes.nameserver_fields')
                    ),
                    null,
                    $vars
                );

                if (isset($vars->{'domain-name'})) {
                    $tld = $this->getTld($vars->{'domain-name'});

                    if ($tld) {
                        $extension_fields = array_merge(
                            (array)Configure::get('Logicboxes.domain_fields' . $tld),
                            (array)Configure::get('Logicboxes.contact_fields' . $tld)
                        );
                        if ($extension_fields) {
                            $module_fields = $this->arrayToModuleFields($extension_fields, $module_fields, $vars);
                        }
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
            $vars->{'domain-name'} = $vars->domain;
        }

        if ($package->meta->type == 'domain') {
            // Set default name servers
            if (!isset($vars->ns1) && isset($package->meta->ns)) {
                $i=1;
                foreach ($package->meta->ns as $ns) {
                    $vars->{'ns' . $i++} = $ns;
                }
            }

            $tld = (property_exists($vars, 'domain-name') ? $this->getTld($vars->{'domain-name'}, true) : null);

            // Handle transfer request
            if (isset($vars->transfer) || isset($vars->{'auth-code'})) {
                $fields = Configure::get('Logicboxes.transfer_fields');

                // We should already have the domain name don't make editable
                $fields['domain-name']['type'] = 'hidden';
                $fields['domain-name']['label'] = null;

                $module_fields = $this->arrayToModuleFields($fields, null, $vars);

                $extension_fields = Configure::get('Logicboxes.contact_fields' . $tld);
                if ($extension_fields) {
                    $module_fields = $this->arrayToModuleFields($extension_fields, $module_fields, $vars);
                }

                return $module_fields;
            } else {
                // Handle domain registration
                $fields = array_merge(
                    Configure::get('Logicboxes.nameserver_fields'),
                    Configure::get('Logicboxes.domain_fields')
                );

                // We should already have the domain name don't make editable
                $fields['domain-name']['type'] = 'hidden';
                $fields['domain-name']['label'] = null;

                $module_fields = $this->arrayToModuleFields($fields, null, $vars);

                if (isset($vars->{'domain-name'})) {
                    $extension_fields = array_merge(
                        (array)Configure::get('Logicboxes.domain_fields' . $tld),
                        (array)Configure::get('Logicboxes.contact_fields' . $tld)
                    );
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
                'tabWhois' => Language::_('Logicboxes.tab_whois.title', true),
                'tabNameservers' => Language::_('Logicboxes.tab_nameservers.title', true),
                'tabSettings' => Language::_('Logicboxes.tab_settings.title', true)
            ];
        } else {
            #
            # TODO: Activate & uploads CSR, set field data, etc.
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
                'tabClientWhois' => Language::_('Logicboxes.tab_whois.title', true),
                'tabClientNameservers' => Language::_('Logicboxes.tab_nameservers.title', true),
                'tabClientSettings' => Language::_('Logicboxes.tab_settings.title', true)
            ];
        } else {
            #
            # TODO: Activate & uploads CSR, set field data, etc.
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
        $row = $this->getModuleRow($package->module_row);
        $api = $this->getApi($row->meta->reseller_id, $row->meta->key, $row->meta->sandbox == 'true');
        $api->loadCommand('logicboxes_domains');
        $domains = new LogicboxesDomains($api);

        $vars = new stdClass();

        $contact_fields = Configure::get('Logicboxes.contact_fields');
        $fields = $this->serviceFieldsToObject($service->fields);
        $sections = ['registrantcontact', 'admincontact', 'techcontact', 'billingcontact'];
        $show_content = true;

        if (!empty($post)) {
            $api->loadCommand('logicboxes_contacts');
            $contacts = new LogicboxesContacts($api);

            // Keep track of what contact IDs to associate with the domain
            $domain_contact_vars = [
                'reg-contact-id' => isset($post['registrantcontact_contact-id'])
                    ? $post['registrantcontact_contact-id']
                    : '',
                'admin-contact-id' => isset($post['admincontact_contact-id']) ? $post['admincontact_contact-id'] : '',
                'tech-contact-id' => isset($post['techcontact_contact-id']) ? $post['techcontact_contact-id'] : '',
                'billing-contact-id' => isset($post['billingcontact_contact-id'])
                    ? $post['billingcontact_contact-id']
                    : ''
            ];
            foreach ($sections as $section) {
                $contact = [];
                foreach ($post as $key => $value) {
                    if (strpos($key, $section . '_') !== false && $value != '') {
                        $contact[str_replace($section . '_', '', $key)] = $value;
                    }
                }

                // Get the current contact info for this contact type
                $response = $contacts->details(['contact-id' => $contact['contact-id']]);
                $this->processResponse($api, $response);
                if ($this->Input->errors()) {
                    break;
                }

                // Check if any field on the contact has changed
                $logicbox_contact = $response->response();
                $changed = false;
                foreach ($contact as $contact_field => $field_value) {
                    if (property_exists($logicbox_contact, $contact_field)
                        && $logicbox_contact->{$contact_field} != $field_value
                    ) {
                        $changed = true;
                        break;
                    }
                }

                // Only update this contact if something has changed
                if ($changed) {
                    // Add a new contact to replace the current one
                    $contact['customer-id'] = $logicbox_contact->customerid;
                    $contact['type'] = $logicbox_contact->type;
                    $response = $contacts->add($contact);
                    $this->processResponse($api, $response);
                    if ($this->Input->errors()) {
                        break;
                    }

                    $contact_id_field = 'reg-contact-id';
                    switch ($section) {
                        case 'admincontact':
                            $contact_id_field = 'admin-contact-id';
                            break;
                        case 'techcontact':
                            $contact_id_field = 'tech-contact-id';
                            break;
                        case 'billingcontact':
                            $contact_id_field = 'billing-contact-id';
                            break;
                    }

                    // Updated the contact ID for this type to the newly created one
                    $domain_contact_vars[$contact_id_field] = $response->response();
                }
            }

            if (!$this->Input->errors()) {
                // Modify the domain to associate it with the new contact IDs
                $response = $domains->modifyContact(['order-id' => $fields->{'order-id'}] + $domain_contact_vars);
                $this->processResponse($api, $response);
                if (!$this->Input->errors()) {
                    $this->setMessage('notice', Language::_('Logicboxes.managewhois.contact_transfer', true));
                }
            }

            $vars = (object)$post;
        } elseif (property_exists($fields, 'order-id')) {
            $response = $domains->details(
                [
                    'order-id' => $fields->{'order-id'},
                    'options' => [
                        'RegistrantContactDetails',
                        'AdminContactDetails',
                        'TechContactDetails',
                        'BillingContactDetails'
                    ]
                ]
            );

            if ($response->status() == 'OK') {
                $data= $response->response();

                // Format fields
                foreach ($sections as $section) {
                    foreach ($data->$section as $name => $value) {
                        if ($name == 'address1') {
                            $name = 'address-line-1';
                        } elseif ($name == 'address2') {
                            $name = 'address-line-2';
                        } elseif ($name == 'zip') {
                            $name = 'zipcode';
                        } elseif ($name == 'telnocc') {
                            $name = 'phone-cc';
                        } elseif ($name == 'telno') {
                            $name = 'phone';
                        } elseif ($name == 'emailaddr') {
                            $name = 'email';
                        } elseif ($name == 'contactid') {
                            $name = 'contact-id';
                        }
                        $vars->{$section . '_' . $name} = $value;
                    }
                }
            }
        } else {
            // No order-id; info is not available
            $show_content = false;
        }

        $contact_fields = array_merge(
            Configure::get('Logicboxes.contact_fields'),
            ['contact-id' => ['type' => 'hidden']]
        );
        unset($contact_fields['customer-id']);
        unset($contact_fields['type']);

        $all_fields = [];
        foreach ($contact_fields as $key => $value) {
            $all_fields['admincontact_' . $key] = $value;
            $all_fields['techcontact_' . $key] = $value;
            $all_fields['registrantcontact_' . $key] = $value;
            $all_fields['billingcontact_' . $key] = $value;
        }

        $module_fields = $this->arrayToModuleFields(Configure::get('Logicboxes.contact_fields'), null, $vars);

        $view = ($show_content ? $view : 'tab_unavailable');
        $this->view = new View($view, 'default');

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('vars', $vars);
        $this->view->set('fields', $this->arrayToModuleFields($all_fields, null, $vars)->getFields());
        $this->view->set('sections', $sections);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'logicboxes' . DS);
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
        $vars = new stdClass();

        $row = $this->getModuleRow($package->module_row);
        $api = $this->getApi($row->meta->reseller_id, $row->meta->key, $row->meta->sandbox == 'true');
        $api->loadCommand('logicboxes_domains');
        $domains = new LogicboxesDomains($api);

        $fields = $this->serviceFieldsToObject($service->fields);
        $show_content = true;

        $tld = $this->getTld($fields->{'domain-name'});
        $sld = substr($fields->{'domain-name'}, 0, -strlen($tld));

        if (property_exists($fields, 'order-id')) {
            if (!empty($post)) {
                $ns = [];
                foreach ($post['ns'] as $i => $nameserver) {
                    if ($nameserver != '') {
                        $ns[] = $nameserver;
                    }
                }
                $post['order-id'] = $fields->{'order-id'};
                $response = $domains->modifyNs(['order-id' => $fields->{'order-id'}, 'ns' => $ns]);
                $this->processResponse($api, $response);

                $vars = (object)$post;
            } else {
                $response = $domains->details(['order-id' => $fields->{'order-id'}, 'options' => 'NsDetails'])
                    ->response();

                $vars->ns = [];
                for ($i = 0; $i < 5; $i++) {
                    if (isset($response->{'ns' . ($i+1)})) {
                        $vars->ns[] = $response->{'ns' . ($i+1)};
                    }
                }
            }
        } else {
            // No order-id; info is not available
            $show_content = false;
        }

        $view = ($show_content ? $view : 'tab_unavailable');
        $this->view = new View($view, 'default');

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('vars', $vars);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'logicboxes' . DS);
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
        $vars = new stdClass();

        $row = $this->getModuleRow($package->module_row);
        $api = $this->getApi($row->meta->reseller_id, $row->meta->key, $row->meta->sandbox == 'true');
        $api->loadCommand('logicboxes_domains');
        $domains = new LogicboxesDomains($api);

        $fields = $this->serviceFieldsToObject($service->fields);
        $show_content = true;

        if (property_exists($fields, 'order-id')) {
            if (!empty($post)) {
                if (isset($post['registrar_lock'])) {
                    if ($post['registrar_lock'] == 'true') {
                        $response = $domains->enableTheftProtection([
                            'order-id' => $fields->{'order-id'},
                        ]);
                    } else {
                        $response = $domains->disableTheftProtection([
                            'order-id' => $fields->{'order-id'},
                        ]);
                    }
                    $this->processResponse($api, $response);
                }

                $vars = (object)$post;
            } else {
                $response = $domains->details(
                    ['order-id' => $fields->{'order-id'}, 'options' => ['OrderDetails']]
                )
                    ->response();

                if ($response) {
                    $vars->registrar_lock = 'false';
                    if (in_array('transferlock', $response->orderstatus)) {
                        $vars->registrar_lock = 'true';
                    }

                    $vars->epp_code = $response->domsecret;
                }
            }
        } else {
            // No order-id; info is not available
            $show_content = false;
        }

        $view = ($show_content ? $view : 'tab_unavailable');
        $this->view = new View($view, 'default');

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('vars', $vars);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'logicboxes' . DS);
        return $this->view->fetch();
    }

    /**
     * Creates a customer
     *
     * @param int $module_row_id The module row ID to add the customer under
     * @param array $vars An array of customer information
     * @return int The customer-id created, null otherwise
     * @see LogicboxesCustomers::signup()
     */
    private function createCustomer($module_row_id, $vars)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->reseller_id, $row->meta->key, $row->meta->sandbox == 'true');
        $api->loadCommand('logicboxes_customers');
        $customers = new LogicboxesCustomers($api);

        $response = $customers->signup($vars);

        $this->processResponse($api, $response);

        if (!$this->Input->errors() && $response->response() > 0) {
            return $response->response();
        }
        return null;
    }

    /**
     * Creates a contact
     *
     * @param int $module_row_id The module row ID to add the contact under
     * @param array $vars An array of contact information
     * @return int The contact-id created, null otherwise
     * @see LogicboxesContacts::add()
     */
    private function createContact($module_row_id, $vars)
    {
        unset($vars['lang-pref'], $vars['username'], $vars['passwd']);

        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->reseller_id, $row->meta->key, $row->meta->sandbox == 'true');
        $api->loadCommand('logicboxes_contacts');
        $contacts = new LogicboxesContacts($api);

        $vars = array_merge($vars, $this->createMap($vars));
        $response = $contacts->add($vars);

        $this->processResponse($api, $response);

        if (!$this->Input->errors() && $response->response() > 0) {
            return $response->response();
        }
        return null;
    }

    /**
     * Fetches the logicboxes customer ID based on username
     *
     * @param int $module_row_id The module row ID to search on
     * @param string $username The customer username (should be an email address)
     * @return int The logicboxes customer-id if one exists, null otherwise
     */
    private function getCustomerId($module_row_id, $username)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->reseller_id, $row->meta->key, $row->meta->sandbox == 'true');
        $api->loadCommand('logicboxes_customers');
        $customers = new LogicboxesCustomers($api);

        $vars = ['username' => $username, 'no-of-records' => 10, 'page-no' => 1];
        $response = $customers->search($vars);

        $this->processResponse($api, $response);

        if (isset($response->response()->{'1'}->{'customer.customerid'})) {
            return $response->response()->{'1'}->{'customer.customerid'};
        }
        return null;
    }

    /**
     * Fetches a logicboxes contact ID of a given logicboxes customer ID
     *
     * @param int $module_row_id The module row ID to search on
     * @param string $customer_id The logicboxes customer-id
     * @param string $type includes one of:
     *  - Contact
     *  - CoopContact
     *  - UkContact
     *  - EuContact
     *  - Sponsor
     *  - CnContact
     *  - CoContact
     *  - CaContact
     *  - DeContact
     *  - EsContact
     * @return int The logicboxes contact-id if one exists, null otherwise
     */
    private function getContactId($module_row_id, $customer_id, $type = 'Contact')
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->reseller_id, $row->meta->key, $row->meta->sandbox == 'true');
        $api->loadCommand('logicboxes_contacts');
        $contacts = new LogicboxesContacts($api);

        $vars = ['customer-id' => $customer_id, 'no-of-records' => 10, 'page-no' => 1, 'type' => $type];
        $response = $contacts->search($vars);

        $this->processResponse($api, $response);

        if (isset($response->response()->{'1'}->{'entity.entitiyid'})) {
            return $response->response()->{'1'}->{'entity.entitiyid'};
        }
        return null;
    }

    /**
     * Return the contact type required for the given TLD
     *
     * @param $tld The TLD to return the contact type for
     * @return string The contact type
     */
    private function getContactType($tld)
    {
        $type = 'Contact';
        // Detect contact type from TLD
        if (($tld_part = ltrim(strstr($tld, '.'), '.')) &&
            in_array($tld_part, ['ca', 'cn', 'co', 'coop', 'de', 'es', 'eu', 'nl', 'ru', 'uk'])) {
            $type = ucfirst($tld_part) . $type;
        }
        return $type;
    }

    /**
     * Create a so-called 'map' of attr-name and attr-value fields to cope with Logicboxes
     * ridiculous format requirements.
     *
     * @param $attr array An array of key/value pairs
     * @retrun array An array of key/value pairs where each $attr[$key] becomes
     *  "attr-nameN" and "attr-valueN" whose values are $key and $attr[$key], respectively
     */
    private function createMap($attr)
    {
        $map = [];

        $i=1;
        foreach ($attr as $key => $value) {
            if (substr($key, 0, 5) == 'attr_') {
                $map['attr-name' . $i] = str_replace('attr_', '', $key);
                $map['attr-value' . $i] = $value;
                $i++;
            }
        }
        return $map;
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
        $api = $this->getApi($row->meta->reseller_id, $row->meta->key, $row->meta->sandbox == 'true');
        $api->loadCommand('logicboxes_domains');
        $domains = new LogicboxesDomains($api);

        $tld = $this->getTld($domain);
        $sld = substr_replace($domain, '', -strlen($tld));

        $result = $domains->available(['domain-name' => $sld, 'tlds' => ltrim($tld, '.')]);

        if ($result->status() != 'OK') {
            return false;
        }

        $response = $result->response();

        return in_array($response->{$domain}->status, ['unknown', 'available']);
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
        $api = $this->getApi($row->meta->reseller_id, $row->meta->key, $row->meta->sandbox == 'true');
        $api->loadCommand('logicboxes_domains');
        $domains = new LogicboxesDomains($api);

        $result = $domains->detailsByName(['domain-name' => $domain, 'options' => 'All']);
        $this->processResponse($api, $result);

        if ($result->status() != 'OK') {
            return false;
        }

        $response = $result->response();

        return isset($response->endtime)
            ? $this->Date->format(
                $format,
                $response->endtime
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
                if ($service_field->key == 'domain-name') {
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
        return Configure::get('Logicboxes.tlds');
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
        Loader::loadModels($this, ['Currencies']);

        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->reseller_id, $row->meta->key, $row->meta->sandbox == 'true');

        // Get all currencies
        $currencies = [];
        $company_currencies = $this->Currencies->getAll(Configure::get('Blesta.company_id'));
        foreach ($company_currencies as $currency) {
            $currencies[$currency->code] = $currency;
        }

        // Get TLD product mapping
        $maping_cache = Cache::fetchCache(
            'tlds_mapping',
            Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'logicboxes' . DS
        );
        if ($maping_cache) {
            $tld_mapping = unserialize(base64_decode($maping_cache));
        } else {
            $tld_mapping = $this->getTldProductMapping($api);
            $this->writeCache('tlds_mapping', $tld_mapping);
        }

        // Get TLD pricings
        $pricing_cache = Cache::fetchCache(
            'tlds_prices',
            Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'logicboxes' . DS
        );
        if ($pricing_cache) {
            $product_pricings = unserialize(base64_decode($pricing_cache));
        } else {
            $product_pricings = $this->getTldProductPricings($api);
            $this->writeCache('tlds_prices', $product_pricings);
        }

        // Get reseller details
        $reseller_cache = Cache::fetchCache(
            'reseller_details',
            Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'logicboxes' . DS
        );
        if ($reseller_cache) {
            $details = unserialize(base64_decode($reseller_cache));
        }
        if (!isset($details)) {
            $api->loadCommand('logicboxes_reseller');
            $reseller = new LogicboxesReseller($api);

            $response = $reseller->details();
            $this->processResponse($api, $response);
            $details = $response->response();

            $this->writeCache('reseller_details', $details);
        }

        // Validate if the reseller currency exists in the company
        if (!isset($currencies[$details->parentsellingcurrencysymbol ?? 'USD'])) {
            $this->Input->setErrors(['currency' => ['not_exists' => Language::_('Logicboxes.!error.currency.not_exists', true)]]);

            return;
        }

        // Set TLD pricing
        $tld_yearly_prices = [];
        foreach ($product_pricings as $name => $pricing) {
            if (isset($tld_mapping[$name])) {
                foreach ($tld_mapping[$name] as $tld) {
                    $tld_name = '.' . $tld;
                    $tld_yearly_prices[$tld_name] = [];

                    // Filter by 'tlds'
                    if (isset($filters['tlds']) && !in_array($tld_name, $filters['tlds'])) {
                        continue;
                    }

                    // Convert prices to all currencies
                    foreach ($currencies as $currency) {
                        // Filter by 'currencies'
                        if (isset($filters['currencies']) && !in_array($currency->code, $filters['currencies'])) {
                            continue;
                        }

                        $tld_yearly_prices[$tld_name][$currency->code] = [];
                        $register_price =  $this->Currencies->convert(
                            $pricing->addnewdomain->{'1'},
                            $details->parentsellingcurrencysymbol ?? 'USD',
                            $currency->code,
                            Configure::get('Blesta.company_id')
                        );
                        $transfer_price = $this->Currencies->convert(
                            $pricing->addtransferdomain->{'1'},
                            $details->parentsellingcurrencysymbol ?? 'USD',
                            $currency->code,
                            Configure::get('Blesta.company_id')
                        );
                        $renewal_price = $this->Currencies->convert(
                            $pricing->renewdomain->{'1'},
                            $details->parentsellingcurrencysymbol ?? 'USD',
                            $currency->code,
                            Configure::get('Blesta.company_id')
                        );
                        foreach (range(1, 10) as $years) {
                            // Filter by 'terms'
                            if (isset($filters['terms']) && !in_array($years, $filters['terms'])) {
                                continue;
                            }

                            $tld_yearly_prices[$tld_name][$currency->code][$years] = [
                                'register' => $register_price * $years,
                                'transfer' => $transfer_price * $years,
                                'renew' => $renewal_price * $years
                            ];
                        }
                    }
                }
            }
        }

        return $tld_yearly_prices;
    }

    private function writeCache($cache_name, $content)
    {
        // Save the TLDs results to the cache
        if (Configure::get('Caching.on') && is_writable(CACHEDIR)) {
            try {
                Cache::writeCache(
                    $cache_name,
                    base64_encode(serialize($content)),
                    strtotime(Configure::get('Blesta.cache_length')) - time(),
                    Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'logicboxes' . DS
                );
            } catch (Exception $e) {
                // Write to cache failed, so disable caching
                Configure::set('Caching.on', false);
            }
        }
    }

    /**
     * Gets a list of TLDs organized by product
     *
     * @param LogicboxesApi $api
     * @return array A list of products and their associated TLDs
     */
    private function getTldProductMapping($api)
    {
        $api->loadCommand('logicboxes_products');
        $products = new LogicboxesProducts($api);
        $product_result = $products->getMappings();
        $this->processResponse($api, $product_result);

        // API request failed, return empty list
        if (trim($product_result->status()) !== 'OK') {
            return [];
        }

        // Format TLD list and organize by product
        $tld_mapping = [];
        $categories = $product_result->response();
        foreach ($categories->domorder ?? [] as $product) {
            $product_array = (array)$product;
            foreach ($product_array as $product_name => $tlds) {
                $tld_mapping[$product_name] = $tlds;
            }
        }

        return $tld_mapping;
    }

    /**
     * Gets a list of TLDs product pricings
     *
     * @param LogicboxesApi $api
     * @return stdClass A list of products and pricings
     */
    private function getTldProductPricings($api)
    {
        $api->loadCommand('logicboxes_products');
        $common = new LogicboxesProducts($api);
        $result = $common->getPricing([]);
        $this->processResponse($api, $result);

        if (trim($result->status()) !== 'OK') {
            return (object) [];
        }
        $response = (object) $result->response();

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
            'reseller_id' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Logicboxes.!error.reseller_id.valid', true)
                ]
            ],
            'key' => [
                'valid' => [
                    'last' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Logicboxes.!error.key.valid', true)
                ],
                'valid_connection' => [
                    'rule' => [
                        [$this, 'validateConnection'],
                        $vars['reseller_id'],
                        isset($vars['sandbox']) ? $vars['sandbox'] : 'false'
                    ],
                    'message' => Language::_('Logicboxes.!error.key.valid_connection', true)
                ]
            ]
        ];
    }

    /**
     * Validates that the given connection details are correct by attempting to check the availability of a domain
     *
     * @param string $key The API key
     * @param string $reseller_id The API reseller ID
     * @param string $sandbox "true" if this is a sandbox account, false otherwise
     * @return bool True if the connection details are valid, false otherwise
     */
    public function validateConnection($key, $reseller_id, $sandbox)
    {
        $api = $this->getApi($reseller_id, $key, $sandbox == 'true');
        $api->loadCommand('logicboxes_domains');
        $domains = new LogicboxesDomains($api);
        return $domains->available(['domain-name' => 'logicboxes', 'tlds' => ['com']])->status() == 'OK';
    }

    /**
     * Initializes the LogicboxesApi and returns an instance of that object
     *
     * @param string $reseller_id The reseller ID to connect as
     * @param string $key The key to use when connecting
     * @param bool $sandbox Whether or not to process in sandbox mode (for testing)
     * @return LogicboxesApi The LogicboxesApi instance
     */
    private function getApi($reseller_id, $key, $sandbox)
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'logicboxes_api.php');

        return new LogicboxesApi($reseller_id, $key, $sandbox);
    }

    /**
     * Process API response, setting an errors, and logging the request
     *
     * @param LogicboxesApi $api The logicboxes API object
     * @param LogicboxesResponse $response The logicboxes API response object
     */
    private function processResponse(LogicboxesApi $api, LogicboxesResponse $response)
    {
        $this->logRequest($api, $response);

        // Set errors, if any
        if ($response->status() != 'OK') {
            if (isset($response->errors()->message)) {
                $errors = $response->errors()->message;
            } elseif (isset($response->errors()->error)) {
                $errors = $response->errors()->error;
            }
            $this->Input->setErrors(['errors' => (array)$errors]);
        }
    }

    /**
     * Logs the API request
     *
     * @param LogicboxesApi $api The logicboxes API object
     * @param LogicboxesResponse $response The logicboxes API response object
     */
    private function logRequest(LogicboxesApi $api, LogicboxesResponse $response)
    {
        $last_request = $api->lastRequest();

        $masks = ['api-key'];
        foreach ($masks as $mask) {
            if (isset($last_request['args'][$mask])) {
                $last_request['args'][$mask] = str_repeat('x', strlen($last_request['args'][$mask]));
            }
        }

        $this->log($last_request['url'], serialize($last_request['args']), 'input', true);
        $this->log($last_request['url'], $response->raw(), 'output', $response->status() == 'OK');
    }

    /**
     * Returns the TLD of the given domain
     *
     * @param string $domain The domain to return the TLD from
     * @param bool $top If true will return only the top TLD, else will return
     *  the first matched TLD from the list of TLDs
     * @return string The TLD of the domain
     */
    private function getTld($domain, $top = false)
    {
        $tlds = $this->getTlds();

        $domain = strtolower($domain);

        if (!$top) {
            foreach ($tlds as $tld) {
                if (substr($domain, -strlen($tld)) == $tld) {
                    return $tld;
                }
            }
        }
        return strrchr($domain, '.');
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

    /**
     * Formats the contact ID for the given TLD and type
     *
     * @param int $contact_id The contact ID
     * @param string $tld The TLD being registered/transferred
     * @param string $type The contact type
     * @return int The contact ID to use
     */
    private function formatContact($contact_id, $tld, $type)
    {
        $tlds = [];
        switch ($type) {
            case 'admin':
            case 'tech':
                $tlds = ['.eu', '.nz', '.ru', '.uk'];
                break;
            case 'billing':
                $tlds = ['.ca', '.eu', '.nl', '.nz', '.ru', '.uk'];
                break;
        }
        if (in_array(strtolower($tld), $tlds)) {
            return -1;
        }
        return $contact_id;
    }
}
