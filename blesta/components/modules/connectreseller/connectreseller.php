<?php

use Blesta\Core\Util\Validate\Server;
use Blesta\Core\Util\Common\Traits\Container;

/**
 * Connectreseller Module
 *
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package connectreseller
 */
class Connectreseller extends RegistrarModule
{
    // Load traits
    use Container;

    /**
     * Initializes the module
     */
    public function __construct()
    {
        // Load the language required by this module
        Language::loadLang('connectreseller', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load module config
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        Configure::load('connectreseller', dirname(__FILE__) . DS . 'config' . DS);
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'connectreseller' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'connectreseller' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'connectreseller' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        if (empty($vars)) {
            $vars = $module_row->meta;
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
        $meta_fields = ['reseller_name', 'api_key'];
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
        $meta_fields = ['reseller_name', 'api_key'];
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
            'reseller_name' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Connectreseller.!error.reseller_name.valid', true)
                ]
            ],
            'api_key' => [
                'valid' => [
                    'rule' => [[$this, 'validateConnection']],
                    'message' => Language::_('Connectreseller.!error.api_key.valid', true)
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Validates that the given domain is valid.
     *
     * @param string $domain The domain to validate
     * @return bool True if the domain is valid, false otherwise
     */
    public function validateDomain($domain)
    {
        $validator = new Server();

        return $validator->isDomain($domain);
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
        foreach ($name_servers as &$name_server) {
            if (empty($name_server)) {
                unset($name_server);
            }
        }

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
                if (empty($name_server)) {
                    continue;
                }

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
     * @param string $api_key ConnectReseller API Key
     * @return bool True if the connection is valid, false otherwise
     */
    public function validateConnection($api_key)
    {
        try {
            $api = $this->getApi($api_key);

            // Load API command
            $command = new ConnectresellerDomain($api);

            // Set the domain status
            $response = $command->check([
                'websiteName' => substr(md5(time()), 0, 8) . '.com'
            ]);
            $this->processResponse($api, $response);

            return ($response->status() == 200);
        } catch (Exception $e) {
            // Trap any errors encountered, could not validate connection
        }

        return false;
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
                    'message' => Language::_('Connectreseller.!error.epp_code.valid', true)
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
        $epp_code = $fields->label(Language::_('Connectreseller.package_fields.epp_code', true), 'connectreseller_epp_code');
        $epp_code->attach(
            $fields->fieldCheckbox(
                'meta[epp_code]',
                'true',
                ($vars->meta['epp_code'] ?? null) == 'true',
                ['id' => 'connectreseller_epp_code']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Connectreseller.package_field.tooltip.epp_code', true));
        $epp_code->attach($tooltip);
        $fields->setField($epp_code);

        // Set all TLD checkboxes
        $tld_options = $fields->label(Language::_('Connectreseller.package_fields.tld_options', true));

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
        for ($i=1; $i<=4; $i++) {
            $type = $fields->label(Language::_('Connectreseller.package_fields.ns', true, $i), 'connectreseller_ns' . $i);
            $type->attach(
                $fields->fieldText(
                    'meta[ns][]',
                    ($vars->meta['ns'][$i - 1] ?? null),
                    ['id' => 'connectreseller_ns' . $i]
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
                ['module_row' => ['missing' => Language::_('Connectreseller.!error.module_row.missing', true)]]
            );

            return;
        }

        // Validate service
        $this->validateService($package, $vars);
        if ($this->Input->errors()) {
            return;
        }

        if (isset($vars['Websitename'])) {
            $tld = $this->getTld($vars['Websitename']);
        }

        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            // Set period
            $vars['years'] = 1;
            foreach ($package->pricing as $pricing) {
                if ($pricing->id == $vars['pricing_id']) {
                    $vars['years'] = $pricing->term;
                    break;
                }
            }

            // Register or transfer the domain
            if (!empty($vars['Authcode'])) {
                $success = $this->transferDomain($vars['Websitename'], $row->id, $vars);
            } else {
                $success = $this->registerDomain($vars['Websitename'], $row->id, $vars);
            }

            if (!$success) {
                return;
            }

            // Load API command
            $api = $this->getApi($row->meta->api_key);
            $command = new ConnectresellerDomain($api);

            // Get domain id
            $response = $command->get([
                'websiteName' => $vars['Websitename']
            ]);
            $this->processResponse($api, $response);
            $registered_domain = $response->response();
        }

        // Return service fields
        return [
            [
                'key' => 'domain',
                'value' => $vars['Websitename'],
                'encrypted' => 0
            ],
            [
                'key' => 'id',
                'value' => $registered_domain->responseData->domainNameId ?? null,
                'encrypted' => 0
            ]
        ];
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
        if (($row = $this->getModuleRow())) {
            $api = $this->getApi($row->meta->api_key);

            $service_fields = $this->serviceFieldsToObject($service->fields);
        }

        // Suspend domain
        if ($api) {
            // Load API command
            $command = new ConnectresellerDomain($api);

            $response = $command->suspend([
                'domainNameId' => $service_fields->id ?? null,
                'websiteName' => $service_fields->domain ?? null,
                'isDomainSuspend' => 1
            ]);
            $this->processResponse($api, $response);
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
        if (($row = $this->getModuleRow())) {
            $api = $this->getApi($row->meta->api_key);

            $service_fields = $this->serviceFieldsToObject($service->fields);
        }

        // Suspend domain
        if ($api) {
            // Load API command
            $command = new ConnectresellerDomain($api);

            $response = $command->suspend([
                'domainNameId' => $service_fields->id ?? null,
                'websiteName' => $service_fields->domain ?? null,
                'isDomainSuspend' => 0
            ]);
            $this->processResponse($api, $response);
        }

        return null;
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
        // Nothing to do

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
            $api = $this->getApi($row->meta->api_key);

            $service_fields = $this->serviceFieldsToObject($service->fields);
        }

        // Set period
        $years = 1;
        foreach ($package->pricing as $pricing) {
            if ($pricing->id == $service->pricing_id) {
                $years = $pricing->term;
                break;
            }
        }

        // Renew domain
        if ($api) {
            // Load API command
            $command = new ConnectresellerDomain($api);

            // Get client account
            $client = $this->Clients->get($service->client_id);
            $response = $command->ViewClient([
                'UserName' => $client->email
            ]);
            $this->processResponse($api, $response);
            $remote_client = $response->response();

            // Renew domain
            $response = $command->renewalOrder([
                'OrderType' => 2,
                'Duration' => $years,
                'Websitename' => $service_fields->domain ?? null,
                'Id' => $remote_client->responseData->clientId ?? null
            ]);
            $this->processResponse($api, $response);
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
            'Websitename' => [
                'valid' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateDomain']],
                    'message' => Language::_('Connectreseller.!error.domain.valid', true)
                ]
            ],
            'ns' => [
                'count' => [
                    'rule' => [[$this, 'validateNameServerCount']],
                    'message' => Language::_('Connectreseller.!error.ns.count', true)
                ],
                'valid' => [
                    'rule' => [[$this, 'validateNameServers']],
                    'message' => Language::_('Connectreseller.!error.ns.valid', true)
                ]
            ]
        ];

        // Unset irrelevant rules when editing a service
        if ($edit) {
            $edit_fields = ['ns'];

            foreach ($rules as $field => $rule) {
                if (!in_array($field, $edit_fields)) {
                    unset($rules[$field]);
                }
            }
        }

        return $rules;
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
        // Nothing to do

        return null;
    }

    /**
     * Initializes the ConnectresellerApi and returns an instance of that object.
     *
     * @param string $api_key ConnectReseller API Key
     * @return ConnectresellerApi The ConnectresellerApi instance
     */
    private function getApi($api_key)
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'connectreseller_api.php');

        $api = new ConnectresellerApi($api_key);

        return $api;
    }

    /**
     * Generates a password.
     *
     * @param int $min_length The minimum character length for the password (5 or larger)
     * @param int $max_length The maximum character length for the password (14 or fewer)
     * @return string The generated password
     */
    private function generatePassword($min_length = 10, $max_length = 14)
    {
        $pool = 'abcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
        $pool_size = strlen($pool);
        $length = mt_rand(max($min_length, 5), min($max_length, 14));
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= substr($pool, mt_rand(0, $pool_size - 1), 1);
        }

        return $password;
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
            'tabClientWhois' => Language::_('Connectreseller.tab_client_whois', true),
            'tabClientNameservers' => Language::_('Connectreseller.tab_client_nameservers', true),
            'tabClientDns' => Language::_('Connectreseller.tab_client_dns', true),
            'tabClientUrlForwarding' => Language::_('Connectreseller.tab_client_urlforwarding', true),
            'tabClientSettings' => Language::_('Connectreseller.tab_client_settings', true)
        ];

        // Check if DNS Management is enabled
        if (!$this->featureServiceEnabled('dns_management', $service)) {
            unset($tabs['tabClientDns']);
            unset($tabs['tabClientUrlForwarding']);
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
            'tabWhois' => Language::_('Connectreseller.tab_whois', true),
            'tabNameservers' => Language::_('Connectreseller.tab_nameservers', true),
            'tabDns' => Language::_('Connectreseller.tab_dns', true),
            'tabUrlForwarding' => Language::_('Connectreseller.tab_urlforwarding', true),
            'tabSettings' => Language::_('Connectreseller.tab_settings', true)
        ];

        // Check if DNS Management is enabled
        if (!$this->featureServiceEnabled('dns_management', $service)) {
            unset($tabs['tabDns']);
            unset($tabs['tabUrlForwarding']);
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
                    'email' => 'EmailAddress',
                    'phone' => 'PhoneNo',
                    'first_name' => 'Name',
                    'address1' => 'Address',
                    'city' => 'City',
                    'state' => 'StateName',
                    'zip' => 'Zip',
                    'country' => 'CountryName'
                ];
                foreach ($contact as $field => $value) {
                    if (isset($fields_map[$field])) {
                        $vars->$type[$fields_map[$field]] = $value;
                    }
                }

                if (isset($vars->$type['Name'])) {
                    $vars->$type['Name'] = trim($vars->$type['Name'] . ' ' . $contact['last_name']);
                }
            }
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['contacts' => $e->getMessage()]]);
        }

        // Set tab sections
        $sections = [
            'registrant', 'admin',
            'technical', 'billing'
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

                if (isset($formatted_contact['first_name'])) {
                    $name_parts = explode(' ', $formatted_contact['first_name'], 2);
                    $formatted_contact['first_name'] = $name_parts[0] ?? null;
                    $formatted_contact['last_name'] = $name_parts[1] ?? null;
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
        $this->view->set('whois_fields', Configure::get('Connectreseller.whois_fields'));
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'connectreseller' . DS);

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
                    'email' => 'EmailAddress',
                    'phone' => 'PhoneNo',
                    'first_name' => 'Name',
                    'address1' => 'Address',
                    'city' => 'City',
                    'state' => 'StateName',
                    'zip' => 'Zip',
                    'country' => 'CountryName'
                ];
                foreach ($contact as $field => $value) {
                    if (isset($fields_map[$field])) {
                        $vars->$type[$fields_map[$field]] = $value;
                    }
                }

                if (isset($vars->$type['Name'])) {
                    $vars->$type['Name'] = trim($vars->$type['Name'] . ' ' . $contact['last_name']);
                }
            }
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['contacts' => $e->getMessage()]]);
        }

        // Set tab sections
        $sections = [
            'registrant', 'admin',
            'technical', 'billing'
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

                if (isset($formatted_contact['first_name'])) {
                    $name_parts = explode(' ', $formatted_contact['first_name'], 2);
                    $formatted_contact['first_name'] = $name_parts[0] ?? null;
                    $formatted_contact['last_name'] = $name_parts[1] ?? null;
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
        $this->view->set('whois_fields', Configure::get('Connectreseller.whois_fields'));
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'connectreseller' . DS);

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
                $vars->{'ns[' . ($i + 1) . ']'} = $nameserver['url'];
            }
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['nameservers' => $e->getMessage()]]);
        }

        // Update nameservers
        if (!empty($post)) {
            $this->setDomainNameservers($service_fields->domain, $service->module_row_id, $post['ns'] ?? []);

            $vars = (object) [];
            foreach ($post['ns'] ?? [] as $i => $nameserver) {
                $vars->{'ns[' . $i . ']'} = $nameserver;
            }
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('nameserver_fields', Configure::get('Connectreseller.nameserver_fields'));
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'connectreseller' . DS);

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
                $vars->{'ns[' . ($i + 1) . ']'} = $nameserver['url'];
            }
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['nameservers' => $e->getMessage()]]);
        }

        // Update nameservers
        if (!empty($post)) {
            $this->setDomainNameservers($service_fields->domain, $service->module_row_id, $post['ns'] ?? []);

            $vars = (object) [];
            foreach ($post['ns'] ?? [] as $i => $nameserver) {
                $vars->{'ns[' . $i . ']'} = $nameserver;
            }
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('nameserver_fields', Configure::get('Connectreseller.nameserver_fields'));
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'connectreseller' . DS);

        return $this->view->fetch();
    }

    /**
     * DNS Records tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabDns($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_dns', 'default');
        $this->view->base_uri = $this->base_uri;

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Fetch domain settings
        $domain_settings = (object) $this->getDomainInfo($service_fields->domain, $service->module_row_id);

        // Load API command
        $row = $this->getModuleRow($service->module_row_id);
        $api = $this->getApi($row->meta->api_key);

        $command = new ConnectresellerDomain($api);

        // Add DNS record
        if (!empty($post) && !isset($post['action'])) {
            $command->ManageDNSRecords(['WebsiteId' => $domain_settings->websiteId]);
            $response = $command->AddDNSRecord(array_merge([
                'DNSZoneID' => $dns_records[0]->dnszoneID ?? null,
                'RecordPriority' => 1
            ], $post));
            $this->processResponse($api, $response);

            $vars = (object) $post;
        }

        // Delete DNS record
        if (($post['action'] ?? '') == 'delete') {
            $command->ManageDNSRecords(['WebsiteId' => $domain_settings->websiteId]);
            $response = $command->DeleteDNSRecord([
                'DNSZoneID' => $dns_records[0]->dnszoneID ?? null,
                'DNSZoneRecordID' => $post['dnszoneRecordID'] ?? null
            ]);
            $this->processResponse($api, $response);
        }

        // Fetch domain DNS records
        try {
            $response = $command->ViewDNSRecord(['WebsiteId' => $domain_settings->websiteId]);
            $this->processResponse($api, $response);
            $dns_records = $response->response()->responseData ?? [];
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['dns' => $e->getMessage()]]);
        }

        // Set supported record types
        $supported_types = [
            'A' => 'A',
            'AAAA' => 'AAAA',
            'SOA' => 'SOA',
            'NS' => 'NS',
            'CNAME' => 'CNAME',
            'MX' => ' MX'
        ];

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('dns_records', $dns_records ?? []);
        $this->view->set('supported_types', $supported_types);
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'connectreseller' . DS);

        return $this->view->fetch();
    }

    /**
     * DNS Records client tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientDns($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_client_dns', 'default');
        $this->view->base_uri = $this->base_uri;

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Fetch domain settings
        $domain_settings = (object) $this->getDomainInfo($service_fields->domain, $service->module_row_id);

        // Load API command
        $row = $this->getModuleRow($service->module_row_id);
        $api = $this->getApi($row->meta->api_key);

        $command = new ConnectresellerDomain($api);

        // Add DNS record
        if (!empty($post) && !isset($post['action'])) {
            $command->ManageDNSRecords(['WebsiteId' => $domain_settings->websiteId]);
            $response = $command->AddDNSRecord(array_merge([
                'DNSZoneID' => $dns_records[0]->dnszoneID ?? null,
                'RecordPriority' => 1
            ], $post));
            $this->processResponse($api, $response);

            $vars = (object) $post;
        }

        // Delete DNS record
        if (($post['action'] ?? '') == 'delete') {
            $command->ManageDNSRecords(['WebsiteId' => $domain_settings->websiteId]);
            $response = $command->DeleteDNSRecord([
                'DNSZoneID' => $dns_records[0]->dnszoneID ?? null,
                'DNSZoneRecordID' => $post['dnszoneRecordID'] ?? null
            ]);
            $this->processResponse($api, $response);
        }

        // Fetch domain DNS records
        try {
            $response = $command->ViewDNSRecord(['WebsiteId' => $domain_settings->websiteId]);
            $this->processResponse($api, $response);
            $dns_records = $response->response()->responseData ?? [];
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['dns' => $e->getMessage()]]);
        }

        // Set supported record types
        $supported_types = [
            'A' => 'A',
            'AAAA' => 'AAAA',
            'SOA' => 'SOA',
            'NS' => 'NS',
            'CNAME' => 'CNAME',
            'MX' => ' MX'
        ];

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('dns_records', $dns_records ?? []);
        $this->view->set('supported_types', $supported_types);
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'connectreseller' . DS);

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

        // Fetch domain settings
        $domain_settings = (object) $this->getDomainInfo($service_fields->domain, $service->module_row_id);

        // Load API command
        $row = $this->getModuleRow($service->module_row_id);
        $api = $this->getApi($row->meta->api_key);

        $command = new ConnectresellerDomain($api);

        // Update rules
        if (!empty($post)) {
            // Add rule
            if (!isset($post['delete'])) {
                $command->ManageDNSRecords(['WebsiteId' => $domain_settings->websiteId]);
                $response = $command->SetDomainForwarding([
                    'domainNameId' => $this->getDomainId($service_fields->domain),
                    'websiteId' => $domain_settings->websiteId,
                    'isMasking' => 1,
                    'rewrite' => $post['destination'] ?? ''
                ]);
            }

            // Delete rule
            if (isset($post['delete'])) {
                $response = $command->deletedomainforwarding(['websiteId' => $domain_settings->websiteId]);
            }

            // Set errors, if any
            if (isset($response)) {
                $this->processResponse($api, $response);
            }

            $vars = (object) $post;
        }

        // Fetch domain url forwarding rules
        try {
            $response = $command->GetDomainForwarding(['websiteId' => $domain_settings->websiteId]);
            $domain_rule = $response->response();
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['url_forwarding' => $e->getMessage()]]);
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('domain_rule', $domain_rule->responseData ?? null);
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'connectreseller' . DS);

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

        // Fetch domain settings
        $domain_settings = (object) $this->getDomainInfo($service_fields->domain, $service->module_row_id);

        // Load API command
        $row = $this->getModuleRow($service->module_row_id);
        $api = $this->getApi($row->meta->api_key);

        $command = new ConnectresellerDomain($api);

        // Update rules
        if (!empty($post)) {
            // Add rule
            if (!isset($post['delete'])) {
                $command->ManageDNSRecords(['WebsiteId' => $domain_settings->websiteId]);
                $response = $command->SetDomainForwarding([
                    'domainNameId' => $this->getDomainId($service_fields->domain),
                    'websiteId' => $domain_settings->websiteId,
                    'isMasking' => 1,
                    'rewrite' => $post['destination'] ?? ''
                ]);
            }

            // Delete rule
            if (isset($post['delete'])) {
                $response = $command->deletedomainforwarding(['websiteId' => $domain_settings->websiteId]);
            }

            // Set errors, if any
            if (isset($response)) {
                $this->processResponse($api, $response);
            }

            $vars = (object) $post;
        }

        // Fetch domain url forwarding rules
        try {
            $response = $command->GetDomainForwarding(['websiteId' => $domain_settings->websiteId]);
            $domain_rule = $response->response();
        } catch (Throwable $e) {
            $this->Input->setErrors(['errors' => ['url_forwarding' => $e->getMessage()]]);
        }

        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('domain_rule', $domain_rule->responseData ?? null);
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'connectreseller' . DS);

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
                if ($post['registrarlock'] == '1') {
                    $this->lockDomain($service_fields->domain, $service->module_row_id);
                }
                if ($post['registrarlock'] == '0') {
                    $this->unlockDomain($service_fields->domain, $service->module_row_id);
                }
            }

            if (!empty($post['privatewhois'])) {
                $row = $this->getModuleRow($service->module_row_id);
                $api = $this->getApi($row->meta->api_key);

                // Load API command
                $command = new ConnectresellerDomain($api);

                if ($post['privatewhois'] == '1') {
                    $response = $command->ManageDomainPrivacyProtection([
                        'domainNameId' => $this->getDomainId($service_fields->domain),
                        'iswhoisprotected' => 1
                    ]);
                }
                if ($post['privatewhois'] == '0') {
                    $response = $command->ManageDomainPrivacyProtection([
                        'domainNameId' => $this->getDomainId($service_fields->domain),
                        'iswhoisprotected' => 0
                    ]);
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

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'connectreseller' . DS);

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
                if ($post['registrarlock'] == '1') {
                    $this->lockDomain($service_fields->domain, $service->module_row_id);
                }
                if ($post['registrarlock'] == '0') {
                    $this->unlockDomain($service_fields->domain, $service->module_row_id);
                }
            }

            if (!empty($post['privatewhois'])) {
                $row = $this->getModuleRow($service->module_row_id);
                $api = $this->getApi($row->meta->api_key);

                // Load API command
                $command = new ConnectresellerDomain($api);

                if ($post['privatewhois'] == '1') {
                    $response = $command->ManageDomainPrivacyProtection([
                        'domainNameId' => $this->getDomainId($service_fields->domain),
                        'iswhoisprotected' => 1
                    ]);
                }
                if ($post['privatewhois'] == '0') {
                    $response = $command->ManageDomainPrivacyProtection([
                        'domainNameId' => $this->getDomainId($service_fields->domain),
                        'iswhoisprotected' => 0
                    ]);
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

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'connectreseller' . DS);

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
            $vars->Websitename = $vars->domain;
        }

        // Set default name servers
        if (!isset($vars->ns) && isset($package->meta->ns)) {
            $i = 1;
            foreach ($package->meta->ns as $ns) {
                $vars->{'ns[' . $i++ . ']'} = $ns;
            }
        }

        // Set posted name servers
        if (!empty($vars->ns) && is_array($vars->ns)) {
            $i = 1;
            foreach ($vars->ns as $ns) {
                $vars->{'ns[' . $i++ . ']'} = $ns;
            }
        }

        // Handle transfer request
        if (isset($vars->transfer) || isset($vars->Authcode)) {
            return $this->arrayToModuleFields(Configure::get('Connectreseller.transfer_fields'), null, $vars);
        } else {
            // Handle domain registration
            $fields = array_merge(
                Configure::get('Connectreseller.domain_fields'),
                Configure::get('Connectreseller.nameserver_fields')
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
            $vars->Websitename = $vars->domain;
        }

        // Set default name servers
        if (!isset($vars->ns) && isset($package->meta->ns)) {
            $i = 1;
            foreach ($package->meta->ns as $ns) {
                $vars->{'ns[' . $i++ . ']'} = $ns;
            }
        }

        // Set posted name servers
        if (!empty($vars->ns) && is_array($vars->ns)) {
            $i = 1;
            foreach ($vars->ns as $ns) {
                $vars->{'ns[' . $i++ . ']'} = $ns;
            }
        }

        // Handle transfer request
        if (isset($vars->transfer) || isset($vars->Authcode)) {
            $fields = Configure::get('Connectreseller.transfer_fields');

            // We should already have the domain name don't make editable
            $fields['Websitename']['type'] = 'hidden';
            $fields['Websitename']['label'] = null;

            return $this->arrayToModuleFields($fields, null, $vars);
        } else {
            // Handle domain registration
            $fields = array_merge(
                Configure::get('Connectreseller.domain_fields'),
                Configure::get('Connectreseller.nameserver_fields')
            );
            $module_fields = $this->arrayToModuleFields($fields, null, $vars);

            // We should already have the domain name don't make editable
            $fields['Websitename']['type'] = 'hidden';
            $fields['Websitename']['label'] = null;

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
            $vars->Websitename = $vars->domain;
        }

        // Set default name servers
        if (!isset($vars->ns) && isset($package->meta->ns)) {
            $i = 1;
            foreach ($package->meta->ns as $ns) {
                $vars->{'ns[' . $i++ . ']'} = $ns;
            }
        }

        // Set posted name servers
        if (!empty($vars->ns) && is_array($vars->ns)) {
            $i = 1;
            foreach ($vars->ns as $ns) {
                $vars->{'ns[' . $i++ . ']'} = $ns;
            }
        }

        // Handle transfer request
        if (isset($vars->transfer) || isset($vars->transferAuthInfo)) {
            $fields = Configure::get('Connectreseller.transfer_fields');

            // We should already have the domain name don't make editable
            $fields['Websitename']['type'] = 'hidden';
            $fields['Websitename']['label'] = null;

            return $this->arrayToModuleFields($fields, null, $vars);
        } else {
            // Handle domain registration
            $fields = array_merge(
                Configure::get('Connectreseller.domain_fields'),
                Configure::get('Connectreseller.nameserver_fields')
            );

            // We should already have the domain name don't make editable
            $fields['Websitename']['type'] = 'hidden';
            $fields['Websitename']['label'] = null;

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
        if (isset($vars->Websitename)) {
            $tld = $this->getTld($vars->Websitename);

            $extension_fields = Configure::get('Connectreseller.domain_fields' . $tld);
            if ($extension_fields) {
                // Set the fields
                $fields = array_merge(
                    Configure::get('Connectreseller.domain_fields'),
                    Configure::get('Connectreseller.nameserver_fields'),
                    $extension_fields
                );

                if ($client) {
                    // We should already have the domain name don't make editable
                    $fields['Websitename']['type'] = 'hidden';
                    $fields['Websitename']['label'] = null;
                }

                // Build the module fields
                $module_fields = new ModuleFields();

                // Allow AJAX requests
                $ajax = $module_fields->fieldHidden('allow_ajax', 'true', ['id'=>'connectreseller_allow_ajax']);
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
     * Verifies that the provided domain name is available
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the domain is available, false otherwise
     */
    public function checkAvailability($domain, $module_row_id = null)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->api_key);

        // Load API command
        $command = new ConnectresellerDomain($api);

        // Check domain availability
        $response = $command->checkDomain(['websiteName' => $domain]);
        $this->processResponse($api, $response);

        return ($response->status() == 200);
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
        $api = $this->getApi($row->meta->api_key);

        // Load API command
        $command = new ConnectresellerDomain($api);

        // Get domain
        $response = $command->get(['websiteName' => $domain]);
        $data = $response->response();

        $this->processResponse($api, $response);

        if (!empty($data->responseData)) {
            return (array) $data->responseData;
        }

        return [];
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
        $api = $this->getApi($row->meta->api_key);

        // Load API command
        $command = new ConnectresellerDomain($api);

        // Get domain
        $response = $command->get(['websiteName' => $domain]);
        $data = $response->response();

        $this->processResponse($api, $response);

        if (isset($data->responseData->expirationDate)) {
            return date($format, (int) $data->responseData->expirationDate / 1000);
        }

        return null;
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
        // Get TLD data
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

        // Format pricing
        $pricing = [];
        foreach ($response as $tld_price) {
            $tld = '.' . ltrim($tld_price->tld, '.');

            // Filter by 'tlds'
            if (isset($filters['tlds']) && !in_array($tld, $filters['tlds'])) {
                continue;
            }

            if (!isset($pricing[$tld])) {
                $pricing[$tld] = [];
            }

            // Get currency
            $currency = $tld_price->currencyCode ?? 'USD';

            // Validate if the reseller currency exists in the company
            if (!isset($currencies[$currency])) {
                $this->Input->setErrors([
                    'currency' => [
                        'not_exists' => Language::_('Connectreseller.!error.currency.not_exists', true)
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
                            'register' => $this->Currencies->convert(
                                ($tld_price->registrationPrice ?? 0) * $i,
                                $tld_price->currencyCode ?? 'USD',
                                $currency->code,
                                Configure::get('Blesta.company_id')
                            ),
                            'transfer' => $this->Currencies->convert(
                                ($tld_price->transferPrice ?? 0) * $i,
                                $tld_price->currencyCode ?? 'USD',
                                $currency->code,
                                Configure::get('Blesta.company_id')
                            ),
                            'renew' => $this->Currencies->convert(
                                ($tld_price->renewalPrice ?? 0) * $i,
                                $tld_price->currencyCode ?? 'USD',
                                $currency->code,
                                Configure::get('Blesta.company_id')
                            )
                        ];
                    }
                }
            }
        }

        return $pricing;
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
            Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'connectreseller' . DS
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
            $api = $this->getApi($row->meta->api_key);

            // Load API command
            $command = new ConnectresellerDomain($api);

            // Get domain price list
            $price_list = $command->tldsync();
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
                        Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'connectreseller' . DS
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
        $api = $this->getApi($row->meta->api_key);

        Loader::loadModels($this, ['Clients', 'Contacts']);

        // Load API command
        $command = new ConnectresellerDomain($api);

        // Get client account
        $client = $this->Clients->get($vars['client_id']);
        $response = $command->ViewClient([
            'UserName' => $client->email
        ]);
        $remote_client = $response->response();

        // Add new client if an existing one can't be found
        if ($response->status() !== 200 && !empty($client)) {
            $numbers = $this->Contacts->getNumbers($client->contact_id, 'phone');
            $phone = $this->formatPhone(
                isset($numbers[0]) ? $numbers[0]->number : null,
                $client->country
            );

            // Get phone extension
            $phone_extension = '+1';
            if (str_contains($phone, '.')) {
                $phone_parts = explode('.', $phone, 2);
                $phone_extension = $phone_parts[0] ?? '+1';
            }

            // Get phone number
            $phone_number = '1111111';
            if (str_contains($phone, '.')) {
                $phone_parts = explode('.', $phone, 2);
                $phone_number = $phone_parts[1] ?? '1111111';
            }

            $add_client = $command->AddClient([
                'FirstName' => $client->first_name,
                'UserName' => $client->email,
                'Password' => $this->generatePassword(),
                'CompanyName' => $client->company != '' ? $client->company : 'Not Applicable',
                'Address1' => $client->address1,
                'City' => $client->city,
                'StateName' => $client->state,
                'CountryName' => $client->country,
                'Zip' => $client->zip,
                'PhoneNo_cc' => $phone_extension,
                'PhoneNo' => $phone_number
            ]);
            $this->processResponse($api, $add_client);

            if ($add_client->status() == 200) {
                $remote_client = $add_client->response();
            } else {
                return false;
            }
        }

        // Register domain
        $response = $command->domainorder([
            'ProductType' => 1,
            'Websitename' => $domain,
            'Duration' => $vars['years'] ?? 1,
            'Id' => $remote_client->responseData->clientId ?? null,
            'IsWhoisProtection' => 0,
            'ns1' => $vars['ns'][1] ?? null,
            'ns2' => $vars['ns'][2] ?? null,
            'ns3' => $vars['ns'][3] ?? null,
            'ns4' => $vars['ns'][4] ?? null,
        ]);
        $this->processResponse($api, $response);

        return !empty($response->response()->responseData ?? null);
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
        $api = $this->getApi($row->meta->api_key);

        Loader::loadModels($this, ['Clients']);

        // Load API command
        $command = new ConnectresellerDomain($api);

        // Get client account
        $client = $this->Clients->get($vars['client_id']);
        $response = $command->ViewClient([
            'UserName' => $client->email
        ]);
        $this->processResponse($api, $response);
        $remote_client = $response->response();

        // Renew domain
        $response = $command->renewalOrder([
            'OrderType' => 2,
            'Websitename' => $domain,
            'Duration' => $vars['years'] ?? 1,
            'Id' => $remote_client->responseData->clientId ?? null
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
        $api = $this->getApi($row->meta->api_key);

        Loader::loadModels($this, ['Clients']);

        // Load API command
        $command = new ConnectresellerDomain($api);

        // Get client account
        $client = $this->Clients->get($vars['client_id']);
        $response = $command->ViewClient([
            'UserName' => $client->email
        ]);
        $remote_client = $response->response();

        // Add new client if an existing one can't be found
        if ($response->status() !== 200 && !empty($client)) {
            $numbers = $this->Contacts->getNumbers($client->contact_id, 'phone');
            $phone = $this->formatPhone(
                isset($numbers[0]) ? $numbers[0]->number : null,
                $client->country
            );

            // Get phone extension
            $phone_extension = '+1';
            if (str_contains($phone, '.')) {
                $phone_parts = explode('.', $phone, 2);
                $phone_extension = $phone_parts[0] ?? '+1';
            }

            // Get phone number
            $phone_number = '1111111';
            if (str_contains($phone, '.')) {
                $phone_parts = explode('.', $phone, 2);
                $phone_number = $phone_parts[1] ?? '1111111';
            }

            $add_client = $command->AddClient([
                'Name' => $client->first_name . ' ' . $client->last_name,
                'UserName' => $client->email,
                'Password' => $this->generatePassword(),
                'CompanyName' => $client->company != '' ? $client->company : 'Not Applicable',
                'Address1' => $client->address1,
                'City' => $client->city,
                'StateName' => $client->state,
                'CountryName' => $client->country,
                'Zip' => $client->zip,
                'PhoneNo_cc' => $phone_extension,
                'PhoneNo' => $phone_number,
                'Faxno_cc' => null,
                'FaxNo' => null,
                'Alternate_Phone_cc' => null,
                'Alternate_Phone' => null
            ]);
            $this->processResponse($api, $add_client);

            if ($add_client->status() == 200) {
                $remote_client = $add_client->response();
            } else {
                return false;
            }
        }

        // Transfer domain
        $response = $command->transferOrder([
            'OrderType' => 4,
            'Websitename' => $domain,
            'Authcode' => $vars['Authcode'] ?? '',
            'Id' => $remote_client->responseData->clientId ?? null
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
        $api = $this->getApi($row->meta->api_key);

        // Load API command
        $command = new ConnectresellerDomain($api);

        // Get the ID of the current contacts of the domain
        $data = $command->get(['websiteName' => $domain])->response();
        $contact_ids = [
            'registrant' => str_replace('OR_', '', $data->responseData->registrantContactId ?? null),
            'admin' => str_replace('OR_', '', $data->responseData->adminContactId ?? null),
            'technical' => str_replace('OR_', '', $data->responseData->technicalContactId ?? null),
            'billing' => str_replace('OR_', '', $data->responseData->billingContactId ?? null)
        ];

        $contacts = [];
        foreach ($contact_ids as $type => $id) {
            $contact = $command->ViewRegistrant(['RegistrantContactId' => $id]);
            $response = $contact->response();

            $this->processResponse($api, $contact);

            $name_parts = explode(' ', $response->responseData->name ?? null, 2);
            $contacts[] = [
                'external_id' => $type,
                'email' => $response->responseData->emailAddress ?? null,
                'phone' => '+' .($response->responseData->phoneCode ?? null) . '.'
                    . ($response->responseData->phoneNo ?? null),
                'first_name' => $name_parts[0] ?? null,
                'last_name' => $name_parts[1] ?? null,
                'address1' => $response->responseData->address1 ?? null,
                'city' => $response->responseData->city ?? null,
                'state' => $response->responseData->stateName ?? null,
                'zip' => $response->responseData->zipCode ?? null,
                'country' => $response->responseData->countryName ?? null
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
        $api = $this->getApi($row->meta->api_key);

        // Load API command
        $command = new ConnectresellerDomain($api);

        // Get domain
        $response = $command->get(['websiteName' => $domain]);
        $data = $response->response();

        $this->processResponse($api, $response);

        return ($data->responseData->isThiefProtected ?? 0) == 1;
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
        $api = $this->getApi($row->meta->api_key);

        // Load API command
        $command = new ConnectresellerDomain($api);

        // Get domain
        $response = $command->get(['websiteName' => $domain]);
        $data = $response->response();

        $ns = [];
        for ($i = 0; $i < 5; $i++) {
            if (!empty($data->responseData->{'nameserver' . ($i+1)})) {
                $ns[] = [
                    'url' => $data->responseData->{'nameserver' . ($i+1)},
                    'ips' => [gethostbyname($data->responseData->{'nameserver' . ($i+1)})]
                ];
            }
        }

        $this->processResponse($api, $response);

        return $ns;
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
        $api = $this->getApi($row->meta->api_key);

        // Load API command
        $command = new ConnectresellerDomain($api);

        // Set the domain status
        $response = $command->ManageTheftProtection([
            'domainNameId' => $this->getDomainId($domain, $module_row_id),
            'websiteName' => $domain,
            'isTheftProtection' => 1
        ]);
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
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return false;
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
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return false;
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
        $api = $this->getApi($row->meta->api_key);

        // Load API command
        $command = new ConnectresellerDomain($api);

        // Get the ID of the current contacts of the domain
        $data = $command->get(['websiteName' => $domain])->response();
        $contacts = [
            'registrantContactId' => str_replace('OR_', '', $data->responseData->registrantContactId ?? null),
            'adminContactId' => str_replace('OR_', '', $data->responseData->adminContactId ?? null),
            'technicalContactId' => str_replace('OR_', '', $data->responseData->technicalContactId ?? null),
            'billingContactId' => str_replace('OR_', '', $data->responseData->billingContactId ?? null)
        ];

        // Create new contacts, if they have not been created during provisioning
        foreach ($contacts as $id => $remote_contact) {
            if (str_replace('OR_', '', $data->responseData->registrantContactId ?? null) == $remote_contact && $id !== 'registrantContactId') {
                $contact = $vars[0] ?? [];
                $params = [
                    'Name' => trim($contact['first_name'] . ' ' . $contact['last_name']),
                    'EmailAddress' => $contact['email'] ?? null,
                    'CompanyName' => $contact['company_name'] ?? 'NA',
                    'Address' => $contact['address1'] ?? null,
                    'City' => $contact['city'] ?? null,
                    'StateName' => $contact['state'] ?? null,
                    'CountryName' => $contact['country'] ?? null,
                    'Zip' => $contact['zip'] ?? null,
                    'PhoneNo_cc' => '1',
                    'PhoneNo' => '1111111',
                    'Id' => $data->responseData->customerId ?? null
                ];

                $response = $command->AddRegistrantContact($params);
                $this->processResponse($api, $response);

                $new_contact = $response->response();
                if (isset($new_contact->responseMsg->id)) {
                    $contacts[$id] = $new_contact->responseMsg->id;
                }
            }
        }

        // Set contacts to the domain
        $command->updatecontact(array_merge([
            'domainNameId' => $this->getDomainId($domain, $module_row_id),
            'websiteName' => $domain
        ], $contacts));

        // Update contacts
        foreach ($vars as $contact) {
            // Get phone extension
            $phone_extension = '1';
            if (str_contains($contact['phone'], '.')) {
                $phone_parts = explode('.', $contact['phone'], 2);
                $phone_extension = ltrim($phone_parts[0] ?? '1', '+');
            }

            // Get phone number
            $phone_number = '1111111';
            if (str_contains($contact['phone'], '.')) {
                $phone_parts = explode('.', $contact['phone'], 2);
                $phone_number = $phone_parts[1] ?? '1111111';
            }

            $params = [
                'Id' => $contacts[$contact['external_id'] . 'ContactId'] ?? null,
                'Name' => trim($contact['first_name'] . ' ' . $contact['last_name']),
                'EmailAddress' => $contact['email'] ?? null,
                'CompanyName' => $contact['company_name'] ?? 'NA',
                'Address1' => $contact['address1'] ?? null,
                'City' => $contact['city'] ?? null,
                'StateName' => $contact['state'] ?? null,
                'CountryName' => $contact['country'] ?? null,
                'Zip' => $contact['zip'] ?? null,
                'PhoneNo_cc' => $phone_extension,
                'PhoneNo' => $phone_number,
                'domainId' => $this->getDomainId($domain, $module_row_id)
            ];

            $response = $command->ModifyRegistrantContact_whmcs($params);
            $this->processResponse($api, $response);
        }

        return (($response->status() ?? 500) == 200);
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
        $api = $this->getApi($row->meta->api_key);

        // Load API command
        $command = new ConnectresellerDomain($api);

        // Update domain nameservers
        $i = 1;
        $nameservers = [];
        foreach ($vars as $ns) {
            $nameservers['nameServer' . $i++] = $ns;
        }

        $response = $command->updateNameServers(array_merge([
            'domainNameId' => $this->getDomainId($domain, $module_row_id),
            'websiteName' => $domain
        ], $nameservers));
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
        $api = $this->getApi($row->meta->api_key);

        // Load API command
        $command = new ConnectresellerDomain($api);

        // Get the domain from the nameservers
        $domain = null;
        foreach ($vars as $nameserver => $ips) {
            $ns_parts = explode('.', $nameserver);
            unset($ns_parts[0]);
            $domain = implode('.', $ns_parts);
        }

        // We will try to remove any previous nameservers
        try {
            $child_ns = $command->getChildNameServers([
                'Id' => $this->getDomainId($domain, $module_row_id)
            ])->response();
            foreach ($child_ns->responseData as $ns) {
                $command->deleteChildNameServer([
                    'domainNameId' => $this->getDomainId($domain, $module_row_id),
                    'websiteName' => $domain,
                    'hostName' => $ns->hostname ?? null
                ]);
            }
        } catch (Throwable $e) {
            // Nothing to do
        }

        // Set the new nameservers
        foreach ($vars as $nameserver => $ips) {
            $response = $command->addChildNameServer([
                'domainNameId' => $this->getDomainId($domain, $module_row_id),
                'websiteName' => $domain,
                'hostName' => $nameserver,
                'ipAddress' => $ips[0] ?? null
            ]);
        }

        if (isset($response)) {
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
        $api = $this->getApi($row->meta->api_key);

        // Load API command
        $command = new ConnectresellerDomain($api);

        // Set the domain status
        $response = $command->ManageTheftProtection([
            'domainNameId' => $this->getDomainId($domain, $module_row_id),
            'websiteName' => $domain,
            'isTheftProtection' => 0
        ]);
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
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return false;
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
            Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'connectreseller' . DS
        );

        if ($cache) {
            $data = unserialize(base64_decode($cache));
        }

        if (!isset($response)) {
            try {
                $row = $this->getModuleRow($module_row_id);
                $api = $this->getApi($row->meta->api_key);

                // Load API command
                $command = new ConnectresellerDomain($api);

                // Set the domain status
                $response = $command->tldsync();
                $this->processResponse($api, $response);

                $data = $response->response();
                if (empty($data)) {
                    return Configure::get('Connectreseller.tlds');
                }

                // Save TLDs in cache
                if (Configure::get('Caching.on') && is_writable(CACHEDIR)) {
                    try {
                        Cache::writeCache(
                            'tlds',
                            base64_encode(serialize($data)),
                            strtotime(Configure::get('Blesta.cache_length')) - time(),
                            Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'connectreseller' . DS
                        );
                    } catch (Exception $e) {
                        // Write to cache failed, so disable caching
                        Configure::set('Caching.on', false);
                    }
                }
            } catch (Throwable $e) {
                return Configure::get('Connectreseller.tlds');
            }
        }

        $tlds = [];
        foreach ($data as $tld) {
            $tlds[] = $tld->tld;
        }

        if (empty($tlds)) {
            return Configure::get('Connectreseller.tlds');
        }

        return $tlds;
    }

    /**
     * Process API response, setting an errors, and logging the request
     *
     * @param ConnectresellerApi $api The ConnectReseller API object
     * @param ConnectresellerResponse $response The ConnectReseller API response object
     */
    private function processResponse(ConnectresellerApi $api, ConnectresellerResponse $response)
    {
        // Set errors, if any
        if ($response->status() != 200) {
            $errors = $response->errors() ?? [];
            if (!empty($errors)) {
                $this->Input->setErrors(['errors' => (array) $errors]);
            } else {
                $this->Input->setErrors($this->getCommonError('general'));
            }
        }

        $last_request = $api->lastRequest();
        $this->log($last_request['url'], serialize($last_request['args'] ?? []), 'input', true);
        $this->log($last_request['url'], $response->raw(), 'output', $response->status() == 200);
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
     * Get the ID of the domain name
     *
     * @param string $domain The domain name to fetch the ID
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return void
     */
    private function getDomainId($domain, $module_row_id = null)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->api_key);

        // Load API command
        $command = new ConnectresellerDomain($api);

        // Get domain id
        $response = $command->get([
            'websiteName' => $domain
        ]);
        $this->processResponse($api, $response);
        $registered_domain = $response->response();

        return $registered_domain->responseData->domainNameId ?? null;

        /*// Get module row
        $row = $this->getModuleRow($module_row_id);
        if (is_null($module_row_id)) {
            $module_row_id = $row->id ?? null;
        }

        Loader::loadComponents($this, ['Record']);

        // Create a subquery to fetch the services that have this daemon
        $this->Record->select(['sf.service_id'])
            ->from(['service_fields' => 'sf'])
            ->innerJoin(['services' => 's'], 's.id', '=', 'sf.service_id', false)
            ->where('s.module_row_id', '=', $module_row_id)
            ->where('sf.key', '=', 'domain')
            ->where('sf.value', '=', $domain)
            ->where('s.status', '!=', 'canceled');
        $subquery = $this->Record->get();
        $values = $this->Record->values;
        $this->Record->reset();

        // Get domain ID
        $domain_id = $this->Record->select(['service_fields.value'])
            ->from('service_fields')
            ->innerJoin('services', 'services.id', '=', 'service_fields.service_id', false)
            ->innerJoin([$subquery => 'sd'], 'sd.service_id', '=', 'services.id', false)
            ->appendValues($values)
            ->where('services.module_row_id', '=', $module_row_id)
            ->where('services.status', '!=', 'canceled')
            ->where('service_fields.key', '=', 'domain_id')
            ->group(['service_fields.service_id'])
            ->order(['service_fields.service_id' => 'desc'])
            ->fetch();

        return $domain_id->value ?? null;*/
    }
}
