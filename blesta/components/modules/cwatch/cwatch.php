<?php

/**
 * cWatch Module.
 *
 * @package blesta
 * @subpackage blesta.components.modules.cwatch
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://blesta.com/license/ The Blesta License Agreement
 * @link http://blesta.com/ Blesta
 */
use Blesta\Core\Util\Common\Traits\Container;
use Blesta\Core\Util\Ftp\Ftp;
use Blesta\Core\Util\Validate\Server;

class Cwatch extends Module
{
    // Load traits
    use Container;

    const BASICPRODUCT = 'BASIC_DETECTION';
    const BASICTERM = 'UNLIMITED';

    /**
     * Initialize the Module.
     */
    public function __construct()
    {
        // Load the cWatch API
        Loader::load(dirname(__FILE__) . DS . 'api' . DS . 'cwatch_api.php');

        // Load components required by this module
        Loader::loadComponents($this, ['Record', 'Input']);

        // Load the language required by this module
        Language::loadLang('cwatch', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load configuration required by this module
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load product configuration required by this module
        Configure::load('cwatch', dirname(__FILE__) . DS . 'config' . DS);
    }

    /**
     * Returns the rendered view of the manage module page
     *
     * @param mixed $module A stdClass object representing the module and its rows
     * @param array $vars An array of post data submitted to or on the manager module
     *  page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the manager module page
     */
    public function manageModule($module, array &$vars)
    {
        $view = $this->getView('manage');
        Loader::loadHelpers($view, ['Form', 'Html', 'Widget']);

        $view->set('module', $module);
        $view->set('vars', (object) $vars);

        return $view->fetch();
    }

    /**
     * Adds the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being added. Returns a set of data, which may be
     * a subset of $vars, that is stored for this module row
     *
     * @param array $vars An array of module info to add
     * @return array A numerically indexed array of meta fields for the module row containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function manageAddRow(array &$vars)
    {
        $view = $this->getView('add_row');
        Loader::loadHelpers($view, ['Form', 'Html', 'Widget']);

        $view->set('vars', (object) $vars);
        return $view->fetch();
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
        $view = $this->getView('edit_row');
        Loader::loadHelpers($view, ['Form', 'Html', 'Widget']);

        if (empty($vars)) {
            $vars = $module_row->meta;
        }

        $view->set('vars', (object) $vars);
        return $view->fetch();
    }

    /**
     * Adds the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being added. Returns a set of data, which may be
     * a subset of $vars, that is stored for this module row
     *
     * @param array $vars An array of module info to add
     * @return array A numerically indexed array of meta fields for the module row containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function addModuleRow(array &$vars)
    {
        $meta_fields = ['username', 'password', 'cwatch_sandbox'];
        $encrypted_fields = ['password'];

        // Set unspecified checkboxes
        if (empty($vars['cwatch_sandbox'])) {
            $vars['cwatch_sandbox'] = 'false';
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
     * a subset of $vars, that is stored for this module row
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
        $meta_fields = ['username', 'password', 'cwatch_sandbox'];
        $encrypted_fields = ['password'];

        // Set unspecified checkboxes
        if (empty($vars['cwatch_sandbox'])) {
            $vars['cwatch_sandbox'] = 'false';
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
     * Load the view
     *
     * @param string $view The name of the view to load
     * @return \View
     */
    protected function getView($view)
    {
        $view_obj = new View($view, 'default');
        $view_obj->base_uri = $this->base_uri;
        $view_obj->setDefaultView('components' . DS . 'modules' . DS . 'cwatch' . DS);

        return $view_obj;
    }

    /**
     * Returns an array of available service delegation order methods. The module
     * will determine how each method is defined. For example, the method 'first'
     * may be implemented such that it returns the module row with the least number
     * of services assigned to it.
     *
     * @return array An array of order methods in key/value pairs where the key is
     *  the type to be stored for the group and value is the name for that option
     * @see Module::selectModuleRow()
     */
    public function selectModuleRow($module_group_id)
    {
        if (!isset($this->ModuleManager)) {
            Loader::loadModels($this, ['ModuleManager']);
        }

        $group = $this->ModuleManager->getGroup($module_group_id);
        if ($group) {
            switch ($group->add_order) {
                default:
                case 'first':
                    foreach ($group->rows as $row) {
                        return $row->id;
                    }
                    break;
            }
        }

        return 0;
    }

    /**
     * Returns all fields used when adding/editing a package, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields to
     *  render as well as any additional HTML markup to include
     */
    public function getPackageFields($vars = null)
    {
        Loader::loadHelpers($this, ['Html']);
        Loader::loadModels($this, ['ModuleManager']);

        $fields = new ModuleFields();

        // Set the package types
        $package_type = $fields->label(
            Language::_('CWatch.package_fields.package_type', true),
            'cwatch_package_type'
        );
        $package_type->attach(
            $fields->fieldSelect(
                'meta[cwatch_package_type]',
                $this->getPackageTypes(),
                (isset($vars->meta['cwatch_package_type']) ? $vars->meta['cwatch_package_type'] : 'multi_license'),
                ['id' => 'cwatch_package_type']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('CWatch.package_fields.tooltip.package_type', true));
        $package_type->attach($tooltip);
        $fields->setField($package_type);

        // Set the module row to use if not given
        if (empty($vars->module_row)) {
            if (!empty($vars->module_group) && is_numeric($vars->module_group)) {
                // Let the module decide which row to use
                $vars->module_row = $this->selectModuleRow($vars->module_group);
            } else {
                $rows = $this->getModuleRows();
                $vars->module_row = isset($rows[0]) ? $rows[0]->id : 0;
            }
        }
        // Set the module row for API calls
        $this->setModuleRow($this->getModuleRow($vars->module_row));

        // Set the license types
        $license_types = $this->getLicenseTypes();
        $license_type = $fields->label(
            Language::_('CWatch.package_fields.license_type', true),
            'cwatch_license_type'
        );
        $license_type->attach(
            $fields->fieldSelect(
                'meta[cwatch_license_type]',
                $license_types['language'],
                (isset($vars->meta['cwatch_license_type']) ? $vars->meta['cwatch_license_type'] : ''),
                ['id' => 'cwatch_license_type']
            )
        );
        $fields->setField($license_type);

        $html = "<script type=\"text/javascript\">
                $(document).ready(function() {
                    toggleLicenseField();
                    $('#cwatch_package_type').change(function () {
                        toggleLicenseField();
                    });

                    // Add a section to display available terms for each license type
                    if (!$('#license_type_terms').length) {
                        $('#cwatch_license_type').parent().append(
                            '<div id=\"license_type_terms\" class=\"pad\"></div>'
                        );
                    }

                    // Make a list of terms by license type
                    var license_terms = {";
        foreach ($license_types['terms'] as $license_type => $terms) {
            $html .= "'" . $license_type . "': '" . implode(", ", $terms) . "',";
        }
        $html .= "};

                    // When type license type is changed, display the available terms for the new type
                    $('#cwatch_license_type').change(function() {
                        $('#license_type_terms').html(
                            '" . Language::_('CWatch.package_fields.available_terms', true) . ": '
                                + license_terms[$(this).val()]
                        );
                    });
                    // Trigger the change event
                    $('#cwatch_license_type').change();
                });

                function toggleLicenseField() {
                    // Hide/show license types based on package type
                    if ($('#cwatch_package_type').val() == 'single_license') {
                        $('#cwatch_license_type').parent().show();
                    } else {
                        $('#cwatch_license_type').parent().hide();
                    }
                }
            </script>
        ";
        $fields->setHtml($html);

        return $fields;
    }

    /**
     * Gets a list of package types
     *
     * return array A list of cWatch package types
     */
    private function getPackageTypes()
    {
        return [
            'multi_license' => Language::_('CWatch.packagetypes.multi_license', true),
            'single_license' => Language::_('CWatch.packagetypes.single_license', true)
        ];
    }

    /**
     * Gets list of license types and some information about them
     *
     * @return array Language and available terms for all license types in the following fields:
     *  - language: A list of license keys and their language
     *  - terms: A list of license keys and arrays of their terms in term/term name key/value pairs
     */
    private function getLicenseTypes()
    {
        $api = $this->getApi();
        $license_type_response = $api->getLicenseTypes();
        $license_types = ['language' => [], 'terms' => []];
        if (!$license_type_response->errors()) {
            foreach ($license_type_response->response() as $license_type) {
                $license_language = Language::_(
                    'CWatch.package_fields.license_' . strtolower($license_type->licenseType),
                    true
                );

                $license_types['language'][$license_type->licenseType] = $license_language
                    ? $license_language
                    : $license_type->friendlyName;

                $license_types['terms'][$license_type->licenseType] = [];
                foreach ($license_type->compatibility as $license_term) {
                   $license_types['terms'][$license_type->licenseType][$license_term->term] = $license_term->friendlyName;
                }
            }
        }

        // Log request data
        $this->log('getlicensetypes', json_encode($api->lastRequest()), 'input', true);
        $this->log('getlicensetypes', $license_type_response->raw(), 'output', $license_type_response->status() == 200);

        return $license_types;
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
        Loader::loadModels($this, ['Services', 'ModuleClientMeta']);

        $this->validateService($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        if (isset($vars['cwatch_domain'])) {
            $vars['cwatch_domain'] = strtolower($vars['cwatch_domain']);
        }

        // Get a list of which email accounts are associated with this client
        $module = $this->getModule();
        $account_emails_meta = null;
        if (isset($vars['client_id'])) {
            $account_emails_meta = $this->ModuleClientMeta->get(
                $vars['client_id'],
                'cwatch_account_emails',
                $module->id
            );
        }

        $license_keys = [];
        $vars['cwatch_email'] = isset($vars['cwatch_email']) ? strtolower($vars['cwatch_email']) : null;
        if (isset($vars['use_module']) && $vars['use_module'] == 'true') {
            // Add user and licenses
            $license_keys = $this->pushUserAndLicenses($vars, $package);

            if ($this->Input->errors()) {
                // Error
                // Delete the user if this is the only service using this email
                if ($vars['cwatch_email']
                    && !$account_emails_meta
                    || !array_key_exists($vars['cwatch_email'], $account_emails_meta->value)
                    || (array_key_exists($vars['cwatch_email'], $account_emails_meta->value)
                        && $account_emails_meta->value[$vars['cwatch_email']] <= 0
                    )
                ) {
                    $this->deleteUser($vars['cwatch_email']);

                    // Remove this account from the client module meta
                    unset($account_emails_meta->value[$vars['cwatch_email']]);
                    $this->ModuleClientMeta->set(
                        $vars['client_id'],
                        $module->id,
                        0,
                        [['key' => 'cwatch_account_emails', 'value' => $account_emails_meta->value, 'encrypted' => 0]]
                    );
                }

                return;
            }

            if ($vars['cwatch_email']) {
                // Record this email for this user so they can use the same email for future services
                $account_emails = [$vars['cwatch_email'] => 1];
                if ($account_emails_meta) {
                    if (array_key_exists($vars['cwatch_email'], $account_emails_meta->value)) {
                        $account_emails_meta->value[$vars['cwatch_email']]++;
                    }

                    $account_emails = array_merge($account_emails, $account_emails_meta->value);
                }

                $this->ModuleClientMeta->set(
                    $vars['client_id'],
                    $module->id,
                    0,
                    [['key' => 'cwatch_account_emails', 'value' => $account_emails, 'encrypted' => 0]]
                );
            }
        }

        // Get user customer ID
        $api = $this->getApi();

        $customer_id = null;
        $user_response = $api->getUser($vars['cwatch_email']);
        if ($user_response->status() == 200
            && ($users = $user_response->response())
            && isset($users[0]->id)
        ) {
            $customer_id = $users[0]->id;
        }

        $return = [
            ['key' => 'cwatch_licenses', 'value' => $license_keys, 'encrypted' => 0],
            [
                'key' => 'cwatch_customer_id',
                'value' => $customer_id,
                'encrypted' => 0
            ]
        ];

        $return_fields = ['cwatch_email', 'cwatch_firstname', 'cwatch_lastname', 'cwatch_country'];
        if ($package->meta->cwatch_package_type == 'single_license' && $status != 'active') {
            $return_fields[] = 'cwatch_domain';
        }

        foreach ($return_fields as $field) {
            $return[] = ['key' => $field, 'value' => isset($vars[$field]) ? $vars[$field] : '', 'encrypted' => 0];
        }

        return $return;
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
        $this->validateServiceEdit($service, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Set input based on service fields if a field is not set
        $api = $this->getApi();
        $service_fields = $this->serviceFieldsToObject($service->fields);
        if (!property_exists($service_fields, 'cwatch_customer_id')) {
            $user_response = $api->getUser($service_fields->cwatch_email);
            if ($user_response->status() == 200
                && ($users = $user_response->response())
                && isset($users[0]->id)
            ) {
                $service_fields->cwatch_customer_id = $users[0]->id;
            }
        }

        $fields = ['cwatch_email', 'cwatch_firstname', 'cwatch_lastname', 'cwatch_country', 'cwatch_customer_id'];
        foreach ($fields as $field) {
            $vars[$field] = isset($vars[$field]) ? $vars[$field] : $service_fields->{$field};
        }

        $vars['cwatch_email'] = strtolower($vars['cwatch_email']);

        $license_keys = $service_fields->cwatch_licenses;
        if (isset($vars['use_module']) && $vars['use_module'] == 'true') {
            // Edit user and add licenses
            $license_keys = $this->pushUserAndLicenses($vars, $package, $service_fields->cwatch_licenses, true);
        }

        if ($this->Input->errors()) {
            return;
        }

        $return = [
            [
                'key' => 'cwatch_licenses',
                'value' => $license_keys,
                'encrypted' => 0
            ]
        ];

        $return_fields = [
            'cwatch_email', 'cwatch_firstname', 'cwatch_lastname',
            'cwatch_country', 'cwatch_customer_id'
        ];

        foreach ($return_fields as $field) {
            $return[] = ['key' => $field, 'value' => isset($vars[$field]) ? $vars[$field] : '', 'encrypted' => 0];
        }

        return $return;
    }

    /**
     * Add/edit a cwatch user and add any requested licenses
     *
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $package The package this service is using
     * @param array $service_licenses A list of licenses already belonging to the service
     * @param bool $edit Whether the user/license is being edited
     * @return array A list of licenses keys added
     */
    private function pushUserAndLicenses($vars, $package, array $service_licenses = [], $edit = false)
    {
        Loader::loadModels($this, ['Services']);

        $api = $this->getApi();

        // Fetch the customer to get their ID
        $user_response = $api->getUser($vars['cwatch_email']);
        $user_errors = $user_response->errors();
        $users = empty($user_errors) ? $user_response->response() : null;

        // Get a list of which email accounts are associated with this client
        $module = $this->getModule();
        $account_emails_meta = null;
        if (isset($vars['client_id'])) {
            $account_emails_meta = $this->ModuleClientMeta->get(
                $vars['client_id'],
                'cwatch_account_emails',
                $module->id
            );
        }

        $response = null;
        $edit_user = $edit
            || ($account_emails_meta && array_key_exists($vars['cwatch_email'], $account_emails_meta->value));
        if ($edit_user) {
            // Edit a customer account in cWatch
            $response = $api->editUser(
                isset($users[0]) ? $users[0]->id : '',
                $vars['cwatch_firstname'],
                $vars['cwatch_lastname'],
                $vars['cwatch_country']
            );
        } else {
            // Add a customer account in cWatch
            $response = $api->addUser(
                $vars['cwatch_email'],
                $vars['cwatch_firstname'],
                $vars['cwatch_lastname'],
                $vars['cwatch_country']
            );
        }

        // Log request data
        $this->log($edit_user ? 'edituser' : 'adduser', json_encode($api->lastRequest()), 'input', true);
        $this->log($edit_user ? 'edituser' : 'adduser', $response->raw(), 'output', $response->status() == 200);

        if ($response->status() != 200) {
            $this->Input->setErrors(['api' => ['internal' => $response->errors()]]);

            return [];
        }

        // Add or upgrade licenses
        $license_keys = [];
        if (isset($package->meta->cwatch_package_type) && $package->meta->cwatch_package_type == 'single_license') {
            $license_keys = $edit
                ? $this->upgradeSingleLicense($vars, $package, $service_licenses)
                : $this->provisionSingleLicense($vars, $package);
        } else {
            $license_keys = $this->provisionMultiLicense($vars, $service_licenses);
        }

        if ($this->Input->errors()) {
            // On error, deactivate any licenses that were added
            $this->deactivateLicenses($license_keys, isset($vars['cwatch_email']) ? $vars['cwatch_email'] : '');

            $license_keys = [];
        }

        return array_merge($license_keys, $service_licenses);
    }

    /**
     * Provision a cwatch license for a service on a single license package
     *
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $package The package this service is using
     * @return array A list of licenses keys added
     */
    private function provisionSingleLicense(array $vars, $package)
    {
        $api = $this->getApi();

        // Make sure we have a type for the license
        if (!isset($package->meta->cwatch_license_type)) {
            $this->Input->setErrors(['api' => ['internal' => Language::_('Cwatch.license_type.empty')]]);

            return [];
        }

        // Attempt to provision the requested license
        $license_key = $this->provisionLicense($vars, $package);
        if ($this->Input->errors()) {
            return [];
        }

        if (!empty($vars['cwatch_domain'])) {
            // Add the domain submitted for a single license package
            $site_response = $api->addSite(
                [
                    'email' => $vars['cwatch_email'],
                    'domain' => $vars['cwatch_domain'],
                    'licenseKey' => $license_key,
                    'initiateDns' => false,
                    'autoSsl' => false
                ]
            );

            // Log the site add
            $this->log('addsite', json_encode($api->lastRequest()), 'input', true);
            $this->log('addsite', $site_response->raw(), 'output', $site_response->status() == 200);

            if ($site_response->status() != 200) {
                $this->Input->setErrors(['api' => ['internal' => $site_response->errors()]]);
            }
        }

        return [$license_key];
    }

    /**
     * Upgrades a cwatch license or site for a service on single license package
     *
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $package The package this service is using
     * @param array $service_licenses A list of licenses already belonging to the service
     * @return array A list of licenses keys added
     */
    private function upgradeSingleLicense(array $vars, $package, array $service_licenses)
    {
        Loader::loadModels($this, ['Services']);

        $api = $this->getApi();

        // Get cWatch licenses
        $licenses_response = $api->getLicenses($vars['cwatch_email'], false);
        $licenses_errors = $licenses_response->errors();
        $licenses = empty($licenses_errors) ? $licenses_response->response() : [];

        // Get the current license if there is one
        $current_license = null;
        foreach ($licenses as $license) {
            if (strtolower($license->status) == 'valid'
                && ($service_licenses === null || in_array($license->licenseKey, $service_licenses))
            ) {
                $current_license = $license;
                break;
            }
        }

        if ($current_license) {
            // Get cWatch sites
            $sites = [];
            $sites_response = $api->getSites($vars['cwatch_email']);
            $sites_errors = $sites_response->errors();
            if (empty($sites_errors)) {
                $sites = $sites_response->response();
            }

            // Attach the site for this license
            foreach ($sites as $site) {
                if ($site->licenseKey == $current_license->licenseKey) {
                    $current_license->site = $site;
                    break;
                }
            }
        }

        // If this is an upgrade to a paid plan, make an upgrade request
        // If the license type of the package selected package is different than that of the current license
        // upgrade the current license to the new type if it is a higher plan
        $paid_license_types = [1 => 'STARTER', 2 => 'PRO', 3 => 'PREMIUM'];
        if (in_array($package->meta->cwatch_license_type, $paid_license_types)
            && $current_license
            && array_search($current_license->pricingTerm, $paid_license_types)
            && array_search($package->meta->cwatch_license_type, $paid_license_types)
                >= array_search($current_license->pricingTerm, $paid_license_types)
        ) {
            // Fetch the customer to get their ID
            $user_response = $api->getUser($vars['cwatch_email']);
            $user_errors = $user_response->errors();
            $users = empty($user_errors) ? $user_response->response() : null;

            // Make a request to change the pricing level (type) for this license
            $price_change_response = $api->changeLicensePricing(
                $package->meta->cwatch_license_type,
                isset($users[0]->id) ? $users[0]->id : '',
                $current_license->licenseKey
            );

            // Log the results
            $this->log('upgradelicense', json_encode($api->lastRequest()), 'input', true);
            $this->log(
                'upgradelicense',
                $price_change_response->raw(),
                'output',
                $price_change_response->status() == 200
            );

            if ($price_change_response->status() != 200) {
                // Record errors
                $this->Input->setErrors(['api' => ['internal' => $price_change_response->errors()]]);
            }

            return [];
        }

        // Create a new license to upgrade the domain to
        $license_key = $this->provisionLicense($vars, $package);

        if ($this->Input->errors()) {
            return [];
        } else {
            $license_keys[] = $license_key;
        }

        // If we are changing licenses, deactivate the old one
        if (isset($current_license->licenseKey)) {
            $api->deactivateLicense($current_license->licenseKey);
        }

        if ($current_license && isset($current_license->site)) {
            // Change the current domain over to the new license
            $site_response = $api->upgradeLicenseForSite(
                [
                    'renew' => false,
                    'email' => $vars['cwatch_email'],
                    'site' => $current_license->site->domain,
                    'licenseKeyNew' => $license_keys[0]
                ]
            );

            // Log the site upgrade
            $this->log('upgradesitelicense', json_encode($api->lastRequest()), 'input', true);
            $this->log('upgradesitelicense', $site_response->raw(), 'output', $site_response->status() == 200);
        }

        return $license_keys;
    }

    /**
     * Provisions a new license in cWatch
     *
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $package The package this service is using
     * @return string The key of the license that was added
     */
    private function provisionLicense($vars, $package)
    {
        $api = $this->getApi();

        // Determine what term to add these licenses for
        $license_term = 'MONTH_1';
        if (($pricing = $this->Services->getPackagePricing($vars['pricing_id']))) {
            $license_term = strtoupper($pricing->period) . '_' . $pricing->term;
        }

        // Add licenses to the customer account according to the config options provided
        $license_response = $api->addLicense(
            $package->meta->cwatch_license_type,
            $package->meta->cwatch_license_type == self::BASICPRODUCT ? self::BASICTERM : $license_term,
            $vars['cwatch_email'],
            $vars['cwatch_firstname'],
            $vars['cwatch_lastname'],
            $vars['cwatch_country']
        );
        $license = $license_response->response();

        // Log the request
        $this->log('addlicense', json_encode($api->lastRequest()), 'input', true);
        $this->log(
            'addlicense',
            $license_response->raw(),
            'output',
            $license_response->status() == 200
        );

        // Break on error
        if ($license_response->status() != 200) {
            $this->Input->setErrors(['api' => ['internal' => $license_response->errors()]]);
            return '';
        }

        // Success! Return the key of the license that was created
        return $license->distributionResult[0]->licenseKeys[0];
    }

    /**
     * Provision a cwatch license for a service on multi license package
     *
     * @param array $vars An array of user supplied info to satisfy the request
     * @param array $service_licenses A list of licenses already belonging to the service
     * @return array A list of licenses keys added
     */
    private function provisionMultiLicense($vars, array $service_licenses)
    {
        $api = $this->getApi();

        $license_type_list = $this->getLicenseTypes();
        $license_type_quantities = $license_type_list['language'];
        $unused_licenses_by_type = [];
        // Get a count of licenses to provide for each type
        foreach ($license_type_quantities as $license_type => $value) {
            $license_type_quantities[$license_type] = isset($vars['configoptions'][$license_type])
                ? $vars['configoptions'][$license_type]
                : 0;

            $unused_licenses_by_type[$license_type] = [];
        }

        // Get cWatch licenses
        $licenses_response = $api->getLicenses($vars['cwatch_email'], false);
        $license_errors = $licenses_response->errors();
        $licenses = empty($license_errors) ? $licenses_response->response() : [];

        // Count how many licenses to add
        foreach ($licenses as $license) {
            if (strtolower($license->status) == 'valid'
                && ($service_licenses === null || in_array($license->licenseKey, $service_licenses))
                && array_key_exists($license->pricingTerm, $license_type_quantities)
            ) {
                $license_type_quantities[$license->pricingTerm]--;

                if ($license->registeredDomainCount == 0) {
                    $unused_licenses_by_type[$license->pricingTerm][] = $license->licenseKey;
                }
            }
        }

        // Check for invalid license type counts
        $license_removals_by_type = [];
        foreach ($license_type_quantities as $license_type => $quantity) {
            // Add licenses
            if ($quantity + count($unused_licenses_by_type[$license_type]) < 0) {
                // Give an error if the config option has a value lower than the current number of used licenses
                // for this type
                $this->Input->setErrors(
                    ['licenses' => ['limit_exceeded' => Language::_('CWatch.!error.limit_exceeded', true)]]
                );

                return [];
            }

            // Record the number of licenses of this type to remove
            $license_removals_by_type[$license_type] = $quantity * -1;
        }

        // Determine what term to add these licenses for
        $license_term = 'MONTH_1';
        if (($pricing = $this->Services->getPackagePricing($vars['pricing_id']))) {
            $license_term = strtoupper($pricing->period) . '_' . $pricing->term;
        }

        // Keep track of the keys for all licenses that are added
        $license_keys = [];

        // Add licenses to the customer account according to the config options provided
        foreach ($license_type_quantities as $license_type => $quantity) {
            for ($i = 0; $i < $quantity; $i++) {
                // Add licenses
                $license_response = $api->addLicense(
                    $license_type,
                    $license_type == self::BASICPRODUCT ? self::BASICTERM : $license_term,
                    $vars['cwatch_email'],
                    $vars['cwatch_firstname'],
                    $vars['cwatch_lastname'],
                    $vars['cwatch_country']
                );

                $this->log('addlicense', json_encode($api->lastRequest()), 'input', true);
                $this->log(
                    'addlicense',
                    $license_response->raw(),
                    'output',
                    $license_response->status() == 200
                );

                // Break on error
                if ($license_response->status() != 200) {
                    $this->Input->setErrors(['api' => ['internal' => $license_response->errors()]]);

                    break 2;
                }

                // Keep track of which licenses were created by this edit so they can be reverted or added to
                // the list of keys for this service
                $license = $license_response->response();
                $license_keys[] = $license->distributionResult[0]->licenseKeys[0];
            }
        }

        // Delete licenses from the customer account according to the config options provided
        foreach ($license_removals_by_type as $license_type => $quantity) {
            for ($i = 0; $i < $quantity; $i++) {
                $this->deactivateLicenses(
                    array_slice($unused_licenses_by_type[$license_type], 0, $quantity),
                    $vars['cwatch_email']
                );
            }
        }

        return $license_keys;
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
        Loader::loadModels($this, ['ModuleClientMeta']);
        // Get cWatch API
        $module = $this->getModule();

        $account_emails_meta = $this->ModuleClientMeta->get($service->client_id, 'cwatch_account_emails', $module->id);
        $service_fields = $this->serviceFieldsToObject($service->fields);
        if ($account_emails_meta
            && array_key_exists($service_fields->cwatch_email, $account_emails_meta->value)
         ) {
            // Deactivate licenses
            $api_errors = $this->deactivateLicenses($service_fields->cwatch_licenses, $service_fields->cwatch_email);

            // Set Errors
            if (!empty($api_errors)) {
                $this->Input->setErrors(['api' => $api_errors]);
                return;
            }

            // Only delete if there are no other services using this email
            if (--$account_emails_meta->value[$service_fields->cwatch_email] == 0) {
                unset($account_emails_meta->value[$service_fields->cwatch_email]);
                // Delete user
                $this->deleteUser($service_fields->cwatch_email);
            }

            // Decrement the recorded number of services using this email
            $this->ModuleClientMeta->set(
                $service->client_id,
                $module->id,
                0,
                [['key' => 'cwatch_account_emails', 'value' => $account_emails_meta->value, 'encrypted' => 0]]
            );
        }
    }

    /**
     * Deactives the given licenses and removes any domains associated with them
     *
     * @param type $license_keys The keys of the licenses being deactivated
     * @param string $email The email these licenses are associated with
     * @return array A list of error messages returned by the API
     */
    private function deactivateLicenses($license_keys, $email)
    {
        $api = $this->getApi();
        // Get cWatch sites for the given email
        $sites = [];
        $sites_response = $api->getSites($email);
        $sites_errors = $sites_response->errors();
        if (empty($sites_errors)) {
            $sites = $sites_response->response();
        }

        $errors = [];
        // Remove any sites associated with the given licenses
        foreach ($sites as $site) {
            if (!in_array($site->licenseKey, $license_keys)) {
                continue;
            }

            $site_response = $api->removeSite($email, $site->domain);

            // Record errors
            if ($site_response->status() != 200) {
                $errors[$site->licenseKey] = $license_response->errors();
            }
        }

        // Deactivate each license if there was no problem removing the domain associated with it
        foreach ($license_keys as $license_key) {
            if (!empty($errors[$license_key])) {
                continue;
            }

            $license_response = $api->deactivateLicense($license_key);

            // Record errors
            if ($license_response->status() != 200) {
                $errors[$license_key] = $license_response->errors();
            }
        }

        return $errors;
    }

    /**
     * Deletes or suspends the given user
     *
     * @param string $email The email of the customer to delete or suspend
     */
    private function deleteUser($email)
    {
        // Get cWatch API
        $api = $this->getApi();

        $errors = ['api' => []];
        try {
            // Fetch all licenses for the user
            $list_response = $api->getLicenses($email);
            $licenses = [];
            if ($list_response->status() == 200) {
                $licenses = $list_response->response();
            }

            // Deactivate licenses
            $api_errors = $this->deactivateLicenses($licenses, $email);
            if (!empty($api_errors)) {
                $errors['api'] = $api_errors;
            }

            // Remove user
            $response = $api->deleteUser($email);
            if ($response->status() != 200) {
                $errors['api'][$email] = $response->errors();
            }

            $this->log('deleteuser', json_encode($api->lastRequest()), 'input', true);
            $this->log('deleteuser', $response->raw(), 'output', $response->status() == 200);
        } catch (Exception $e) {
            $errors['api']['internal'] = $e->getMessage();
        }

        // Set Errors
        if (!empty($errors['api'])) {
            $this->Input->setErrors($errors);
        }
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
        Loader::loadHelpers($this, ['Html', 'Form']);
        Loader::loadModels($this, ['Countries', 'Clients']);
        Loader::loadComponents($this, ['Session']);

        $fields = new ModuleFields();

        $requestor = $this->getFromContainer('requestor');
        $client_id = (isset($requestor->client_id) ? $requestor->client_id : $this->Session->read('blesta_client_id'));
        $client = $this->Clients->get($client_id);

        // Provision the license assigned to this package
        if ($package->meta->cwatch_package_type == 'single_license') {
            // Create domain label
            $domain = $fields->label(Language::_('Cwatch.service_field.domain', true), 'cwatch_domain');
            // Create domain field and attach to domain label
            $domain->attach(
                $fields->fieldText('cwatch_domain', (isset($vars->cwatch_domain) ? $vars->cwatch_domain : null), ['id' => 'cwatch_domain'])
            );
            // Set the label as a field
            $fields->setField($domain);
        }

        // Create email label
        $email = $fields->label(Language::_('Cwatch.service_field.email', true), 'cwatch_email');
        // Create email field and attach to email label
        $email->attach(
            $fields->fieldText(
                'cwatch_email',
                (isset($vars->cwatch_email) ? $vars->cwatch_email : ($client->email ?? null)),
                ['id' => 'cwatch_email']
            )
        );
        // Set the label as a field
        $fields->setField($email);

        // Create firstname label
        $firstname = $fields->label(Language::_('Cwatch.service_field.firstname', true), 'cwatch_firstname');
        // Create firstname field and attach to firstname label
        $firstname->attach(
            $fields->fieldText(
                'cwatch_firstname',
                (isset($vars->cwatch_firstname) ? $vars->cwatch_firstname : ($client->first_name ?? null)),
                ['id' => 'cwatch_firstname']
            )
        );
        // Set the label as a field
        $fields->setField($firstname);

        // Create lastname label
        $lastname = $fields->label(Language::_('Cwatch.service_field.lastname', true), 'cwatch_lastname');
        // Create lastname field and attach to lastname label
        $lastname->attach(
            $fields->fieldText(
                'cwatch_lastname',
                (isset($vars->cwatch_lastname) ? $vars->cwatch_lastname : ($client->last_name ?? null)),
                ['id' => 'cwatch_lastname']
            )
        );
        // Set the label as a field
        $fields->setField($lastname);

        // Create country label
        $country = $fields->label(Language::_('Cwatch.service_field.country', true), 'cwatch_country');
        // Create country field and attach to country label
        $country->attach(
            $fields->fieldSelect(
                'cwatch_country',
                $this->Form->collapseObjectArray($this->Countries->getList(), ['name', 'alt_name'], 'alpha2', ' - '),
                (isset($vars->cwatch_country) ? $vars->cwatch_country : ($client->country ?? null)),
                ['id' => 'cwatch_country']
            )
        );
        // Set the label as a field
        $fields->setField($country);

        return $fields;
    }

    /**
     * Returns all fields to display to an admin attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getClientAddFields($package, $vars = null)
    {
        return $this->getAdminAddFields($package, $vars);
    }

    /**
     * Client tab (add client/add malware scanner and view status)
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function getClientTabs($package)
    {
        return [
            'tabClientLicenses' => Language::_('CWatch.tab_licenses.licenses', true),
            'tabClientMalware' => Language::_('CWatch.tab_malware.malware', true)
        ];
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
        return [
            'tabLicenses' => Language::_('CWatch.tab_licenses.licenses', true),
            'tabMalware' => Language::_('CWatch.tab_malware.malware', true)
        ];
    }

    /**
     * Manage malware scanners
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientMalware($package, $service, array $get = null, array $post = null, array $files = null)
    {
        return $this->getMalwareTab('tab_client_malware', $service, $post);
    }

    /**
     * Manage malware scanners
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabMalware($package, $service, array $get = null, array $post = null, array $files = null)
    {
        return $this->getMalwareTab('tab_malware', $service, $post);
    }

    /**
     * Manage malware scanners
     *
     * @param string $template The name of the template to use
     * @param stdClass $service A stdClass object representing the current service
     * @param array $post Any POST parameters
     * @return string The string representing the contents of this tab
     */
    private function getMalwareTab($template, $service, $post)
    {
        // Load view
        $this->view = $this->getView($template);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        Loader::loadComponents($this, ['Security']);

        // Get cWatch API
        $api = $this->getApi();
        $service_fields = $this->serviceFieldsToObject($service->fields);

        $use_sftp = isset($post['sftp']);
        if (isset($post['test_ftp'])) {
            $error_message = Language::_('CWatch.!error.' . ($use_sftp ? 's' : '') . 'ftp_test', true);

            try {
                if ($use_sftp) {
                    $ftp = $this->Security->create(
                        'Net',
                        'SFTP',
                        [(isset($post['host']) ? $post['host'] : null), (isset($post['port']) ? $post['port'] : null)]
                    );

                    $success = $ftp->login((isset($post['username']) ? $post['username'] : null), (isset($post['password']) ? $post['password'] : null))
                        && $ftp->chdir((isset($post['path']) ? $post['path'] : null));
                } else {
                    // Set regular FTP options
                    $ftp_options = array(
                        'passive' => true,
                        'port' => (isset($post['port']) ? $post['port'] : null),
                        'timeout' => 30,
                        'curlOptions' => array(
                            CURLOPT_PROTOCOLS => CURLPROTO_FTP,
                        )
                    );
                    $protocol = 'ftp://';

                    $ftp = new Ftp();
                    $ftp->setServer($protocol . (isset($post['host']) ? $post['host'] : null));
                    $ftp->setCredentials(
                        (isset($post['username']) ? $post['username'] : null),
                        (isset($post['password']) ? $post['password'] : null)
                    );
                    $ftp->setOptions($ftp_options);

                    $success = $ftp->listDir((isset($post['path']) ? $post['path'] : null));
                }

                // Attempt to login to test the connection and navigate to the given path. Show success or error
                if ($success) {
                    echo $this->setMessage(
                        'message',
                        Language::_('CWatch.!success.' . ($use_sftp ? 's' : '') . 'ftp_test', true)
                    );
                } else {
                    echo $this->setMessage('error', $error_message);
                }
            } catch (Exception $e) {
                $this->setMessage('error', $error_message);
            }

            $this->view->set('vars', $post);
        } elseif (!empty($post)) {
            $scanner = $api->addScanner(
                $service_fields->cwatch_email,
                [
                    'domain' => (isset($post['domainname']) ? $post['domainname'] : null),
                    'password' => (isset($post['password']) ? $post['password'] : null),
                    'login' => (isset($post['username']) ? $post['username'] : null),
                    'host' => (isset($post['host']) ? $post['host'] : null),
                    'port' => (isset($post['port']) ? $post['port'] : null),
                    'path' => (isset($post['path']) ? $post['path'] : null),
                    'protocol' => $use_sftp ? 'SFTP' : 'FTP'
                ]
            );

            // Filter logging content
            $scanner_errors = $scanner->errors();
            $last_request = $api->lastRequest();
            if (isset($last_request['content']['password'])) {
                $last_request['content']['password'] = '***';
            }

            $scanner_raw = json_decode($scanner->raw());
            if (isset($scanner_raw->password)) {
                $scanner_raw->password = '***';
            }

            $this->log('addmalwarescanner', json_encode($last_request), 'input', true);
            $this->log('addmalwarescanner', json_encode($scanner_raw), 'output', empty($scanner_errors));

            if (!empty($scanner_errors)) {
                $this->Input->setErrors(['api' => ['internal' => $scanner_errors]]);
            }

            $this->view->set('vars', $post);
        }

        $sites_response = $api->getSites($service_fields->cwatch_email);
        $sites_errors = $sites_response->errors();
        $sites = ['' => Language::_('AppController.select.please', true)];
        $domains_ftp = [];
        if (empty($sites_errors)) {
            foreach ($sites_response->response() as $site) {
                $scanner_response = $api->getScanner($service_fields->cwatch_email, $site->domain);
                if (empty($sites_errors)) {
                    $scanner = $scanner_response->response();
                    $domains_ftp[$site->domain] = $scanner->ftp;
                }

                $sites[$site->domain] = $site->domain;
            }
        }

        $this->view->set('domains_ftp', $domains_ftp);
        $this->view->set('sites', $sites);
        $this->view->set('service', $service);
        return $this->view->fetch();
    }

    /**
     * Manage customer licenses
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientLicenses($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Determine which view template to show the user
        $template = 'tab_client_licenses';
        if (isset($get['action'])) {
            switch ($get['action']) {
                case 'add_site':
                    $template = 'client_add_site';
                    break;
                case 'upgrade_site':
                    $template = 'client_upgrade_site';
                    break;
            }
        }

        return $this->getLicensesTab($template, $service, $post, $get, $package);
    }

    /**
     * Manage customer licenses
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabLicenses($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Determine which view template to show the user
        $template = 'tab_licenses';
        if (isset($get['action'])) {
            switch ($get['action']) {
                case 'add_site':
                    $template = 'admin_add_site';
                    break;
                case 'upgrade_site':
                    $template = 'admin_upgrade_site';
                    break;
            }
        }

        return $this->getLicensesTab($template, $service, $post, $get, $package);
    }

    /**
     * Manage customer licenses
     *
     * @param string $template The name of the template to use
     * @param stdClass $service A stdClass object representing the current service
     * @param array $post Any POST parameters
     * @param array $get Any GET parameters
     * @param stdClass $package A stdClass object representing the current package
     * @return string The string representing the contents of this tab
     */
    private function getLicensesTab($template, $service, $post, $get, $package)
    {
        // Load view
        $this->view = $this->getView($template);
        if (!isset($get['action']) && $package->meta->cwatch_package_type == 'single_license') {
            $this->setMessage('notice', Language::_('CWatch.tab_licenses.upgrade_delay', true));
        }

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        Loader::loadModels($this, ['PackageOptions', 'Services']);

        $service_fields = $this->serviceFieldsToObject($service->fields);
        $service_licenses = $service_fields->cwatch_licenses;

        $license_key = isset($get['key']) ? $get['key'] : '';

        // Get cWatch API
        $api = $this->getApi();

        if (!empty($post)) {
            // Default the action to add_site
            $post['action'] = isset($post['action']) ? $post['action'] : 'add_site';

            switch ($post['action']) {
                case 'upgrade_site':
                    $this->upgradeLicenseForSite(
                        $service_fields->cwatch_email,
                        isset($post['domain']) ? $post['domain'] : '',
                        isset($post['licenseKey']) ? $post['licenseKey'] : ''
                    );
                    break;
                case 'deactivate_license':
                    $api_response = $api->deactivateLicense($post['licenseKey']);
                    break;
                case 'remove_domain':
                    $api_response = $api->removeSite(
                        $service_fields->cwatch_email,
                        isset($post['domain']) ? $post['domain'] : ''
                    );
                    break;
                case 'add_site':
                    // This is the default case
                default:
                    $api_response = $api->addSite(
                        [
                            'email' => $service_fields->cwatch_email,
                            'domain' => isset($post['domain']) ? $post['domain'] : '',
                            'licenseKey' => isset($post['licenseKey']) ? $post['licenseKey'] : '',
                            'initiateDns' => isset($post['initiateDns']) && $post['initiateDns'] == 1 ? true : false,
                            'autoSsl' => isset($post['autoSsl']) && $post['autoSsl'] == 1 ? true : false
                        ]
                    );
            }

            if (in_array($post['action'], ['add_site', 'remove_site', 'deactivate_license'])) {
                // Log request and response
                $api_errors = $api_response->errors();
                $this->log($post['action'], json_encode($api->lastRequest()), 'input', true);
                $this->log($post['action'], $api_response->raw(), 'output', empty($api_errors));

                // Set errors
                if (!empty($api_errors)) {
                    $this->Input->setErrors(['api' => ['internal' => $api_errors]]);
                }
            }
        }

        // Get cWatch site provisions
        $site_provisions = [];
        $provisions_response = $api->getSiteProvisions($service_fields->cwatch_email);
        $provisions_errors = $provisions_response->errors();
        if (empty($provisions_errors)) {
            $site_provisions = $provisions_response->response();
        }

        // Sort provisions by license
        $provisions_by_license = [];
        foreach ($site_provisions as $site_provision) {
            if (strtolower($site_provision->status) != 'add_site_fail'
                && strtolower($site_provision->status) != 'add_site_completed'
                && in_array($site_provision->licenseKey, $service_licenses)
            ) {
                $provisions_by_license[$site_provision->licenseKey] = $site_provision;
            }
        }

        // Get cWatch sites
        $sites = [];
        $sites_response = $api->getSites($service_fields->cwatch_email);
        $sites_errors = $sites_response->errors();
        if (empty($sites_errors)) {
            $sites = $sites_response->response();
        }

        // Sort sites by license and make a list of domains
        $sites_by_license = [];
        $domains = [];
        foreach ($sites as $site) {
            if (!in_array($site->licenseKey, $service_licenses)) {
                continue;
            }

            // Get the malware scanner for this site
            $scanner = $api->getScanner($service_fields->cwatch_email, $site->domain);
            $scanner_errors = $scanner->errors();
            if (empty($scanner_errors)) {
                $site->scanner = $scanner->response();
            }

            $sites_by_license[$site->licenseKey] = $site;
            $domains[$site->domain] = $site->domain;
        }

        // Get cWatch licenses
        $licenses_response = $api->getLicenses($service_fields->cwatch_email, false);
        $licenses_errors = $licenses_response->errors();
        $licenses = [];
        $inactive_licenses = [];
        $available_licenses = [];
        $selected_license = null;
        if (empty($licenses_errors)) {
            foreach ($licenses_response->response() as $license) {
                if (!in_array($license->licenseKey, $service_licenses)) {
                    continue;
                }

                if (isset($sites_by_license[$license->licenseKey])) {
                    // Use the associated site for domain info
                    $license->site = $sites_by_license[$license->licenseKey];
                } elseif (isset($provisions_by_license[$license->licenseKey])) {
                    // Use the associated provision for site info
                    $license->site = $provisions_by_license[$license->licenseKey];
                } elseif ($license->status == 'Valid') {
                    // Mark this license available for attaching a new domain
                    $available_licenses[$license->licenseKey] = Language::_(
                        'CWatch.tab_licenses.license_name',
                        true,
                        $license->licenseKey,
                        $license->productTitle
                    );
                }

                if ($license->status == 'Valid') {
                    $licenses[] = $license;
                } else {
                    $inactive_licenses[] = $license;
                }

                if ($license->licenseKey == $license_key) {
                    $selected_license = $license;
                }
            }
        }

        $this->view->set('site_statuses', $this->getSiteStatuses());
        $this->view->set('service', $service);
        $this->view->set('package_type', $package->meta->cwatch_package_type);
        $this->view->set('licenses', $licenses);
        $this->view->set('inactive_licenses', $inactive_licenses);
        $this->view->set('available_licenses', $available_licenses);
        $this->view->set('selected_license', $selected_license);
        $this->view->set('domains', $domains);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'cwatch' . DS);
        return $this->view->fetch();
    }

    private function upgradeLicenseForSite($email, $domain, $new_license_key)
    {
        $api = $this->getApi();

        // Change the domain over to the new license
        $site_response = $api->upgradeLicenseForSite(
            [
                'renew' => false,
                'email' => $email,
                'site' => $domain,
                'licenseKeyNew' => $new_license_key
            ]
        );

        // Log the site license change
        $this->log('upgradesitelicense', json_encode($api->lastRequest()), 'input', true);
        $this->log('upgradesitelicense', $site_response->raw(), 'output', $site_response->status() == 200);

        if ($site_response->status() != 200) {
            $this->Input->setErrors(['api' => [$site_response->errors()]]);
        }
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
        return $this->getServiceInfo('client_service_info', $service);
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
        return $this->getServiceInfo('admin_service_info', $service);
    }

    /**
     * Fetches the HTML content to display when viewing the service info.
     *
     * @param string $template The name of the template to use
     * @param stdClass $service A stdClass object representing the current service
     * @return string The string representing the contents of this tab
     */
    private function getServiceInfo($template, $service)
    {
        // Load view
        $this->view = $this->getView($template);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get cWatch API
        $api = $this->getApi();
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Get cWatch licenses
        $licenses_response = $api->getLicenses($service_fields->cwatch_email);
        $licenses_errors = $licenses_response->errors();
        $licenses = [];
        if (empty($licenses_errors)) {
            foreach ($licenses_response->response() as $license) {
                if (strtolower($license->status) == 'valid'
                    && in_array($license->licenseKey, $service_fields->cwatch_licenses)
                ) {
                    $licenses[] = $license;
                }
            }
        }

        $this->view->set('licenses', $licenses);
        $this->log('viewinfo', serialize($licenses), 'output', true);

        $login_url = 'https://partner.cwatch.comodo.com';
        $user_response = $api->getUser($service_fields->cwatch_email);
        $users = $user_response->response();
        $user_errors = $user_response->errors();
        if (empty($user_errors) && isset($users[0])) {
            $login_response = $api->getLogin($users[0]->camId, $service_fields->cwatch_email);
            $login_errors = $login_response->errors();
            if (empty($login_errors)) {
                $login = $login_response->response();
                $login_url = $login->loginUrl;
            }
        }
        $this->view->set('login_url', $login_url);

        return $this->view->fetch();
    }

    /**
     * Gets a list of cWatch site provision statuses and their languages
     *
     * @return array A list of cWatch site provision statuses and their languages
     */
    private function getSiteStatuses()
    {
        return [
            'WAITING' => Language::_('CWatch.getsitestatuses.waiting', true),
            'ADD_SITE_INPROGRESS' => Language::_('CWatch.getsitestatuses.site_inprogress', true),
            'ADD_SITE_RETRY' => Language::_('CWatch.getsitestatuses.site_retry', true),
            'ADD_SITE_COMPLETED' => Language::_('CWatch.getsitestatuses.site_completed', true),
            'ADD_SITE_FAIL' => Language::_('CWatch.getsitestatuses.site_failed', true),
            'INITIATE_DNS_INPROGRESS' => Language::_('CWatch.getsitestatuses.dns_inprogress', true),
            'INITIATE_DNS_RETRY' => Language::_('CWatch.getsitestatuses.dns_retry', true),
            'INITIATE_DNS_COMPLETED' => Language::_('CWatch.getsitestatuses.dns_completed', true),
            'INITIATE_DNS_FAIL' => Language::_('CWatch.getsitestatuses.dns_failed', true),
            'AUTO_SSL_INPROGRESS' => Language::_('CWatch.getsitestatuses.ssl_inprogress', true),
            'AUTO_SSL_RETRY' => Language::_('CWatch.getsitestatuses.ssl_retry', true),
            'AUTO_SSL_COMPLETED' => Language::_('CWatch.getsitestatuses.ssl_completed', true),
            'AUTO_SSL_FAIL' => Language::_('CWatch.getsitestatuses.ssl_fail', true)
        ];
    }

    /**
     * Loads the cWatch API based on current row data
     *
     * @return \CwatchApi
     */
    private function getApi()
    {
        $row = $this->getModuleRow();
        $username = isset($row->meta->username) ? $row->meta->username : '';
        $password = isset($row->meta->password) ? $row->meta->password : '';
        $sandbox = isset($row->meta->cwatch_sandbox) ? $row->meta->cwatch_sandbox : 'false';

        return new CwatchApi($username, $password, $sandbox == 'true');
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
        // Set the module row to use if not given
        if (!isset($vars['module_row_id'])) {
            // Set module row to that defined for the package if available
            if ($package->module_row) {
                $vars['module_row_id'] = $package->module_row;
            } else {
                // If no module row defined for the package, let the module decide which row to use
                $vars['module_row_id'] = $this->selectModuleRow($package->module_group);
            }
        }
        $this->setModuleRow($this->getModuleRow($vars['module_row_id']));

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
        Loader::loadModels($this, ['Packages']);
        $rules = $this->getServiceRules($vars, true);

        if (($package = $this->Packages->getByPricingID($service->pricing_id))
            && $package->meta->cwatch_package_type !== 'single_license'
        ) {
            unset($rules['cwatch_domain']);
        }

        $this->Input->setRules($rules);
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
        $client_id = isset($vars['client_id']) ? $vars['client_id'] : 0;
        $rules = [
            'cwatch_email' => [
                'format' => [
                    'rule' => 'isEmail',
                    'message' => Language::_('CWatch.!error.cwatch_email.format', true)
                ],
                'unique' => [
                    'rule' => function ($email) use ($client_id) {
                        Loader::loadModels($this, ['ModuleClientMeta']);

                        $module = $this->getModule();
                        $account_emails = $this->ModuleClientMeta->get(
                            $client_id,
                            'cwatch_account_emails',
                            $module->id
                        );

                        if ($account_emails
                            && array_key_exists($email, $account_emails->value)
                            && $account_emails->value[$email] > 0
                        ) {
                            return true;
                        }

                        // Fetch any user matching this email from cWatch
                        $api = $this->getApi();
                        $user_response = $api->getUser($email);
                        $user_errors = $user_response->errors();

                        if ($user_errors
                            || (($user = $user_response->response())
                                && !empty($user))
                        ) {
                            return false;
                        }

                        return true;
                    },
                    'message' => Language::_('CWatch.!error.cwatch_email.unique', true)
                ]
            ],
            'cwatch_firstname' => [
                'empty' => [
                    'if_set' => $edit,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('CWatch.!error.cwatch_firstname.empty', true)
                ]
            ],
            'cwatch_lastname' => [
                'empty' => [
                    'if_set' => $edit,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('CWatch.!error.cwatch_lastname.empty', true)
                ]
            ],
            'cwatch_country' => [
                'length' => [
                    'if_set' => $edit,
                    'rule' => ['maxLength', 3],
                    'message' => Language::_('CWatch.!error.cwatch_country.length', true)
                ]
            ],
            'cwatch_domain' => [
                'format' => [
                    'if_set' => true,
                    'rule' => function ($host_name) {
                        $validator = new Server();
                        return $validator->isDomain($host_name) || $validator->isIp($host_name);
                    },
                    'message' => Language::_('CWatch.!error.cwatch_domain.format', true)
                ],
//                'unique' => [
//                    'if_set' => true,
//                    'rule' => function ($host_name) {
//                        // Fetch any site matching this domain from cWatch
//                        $api = $this->getApi();
//                        $site_response = $api->checkSite($host_name);
//                        $site_errors = $site_response->errors();
//
//                        var_dump($site_response->raw());
//                        if (empty($site_errors)
//                            && ($site = $site_response->response())
//                            && isset($site->existing)
//                        ) {
//                            return !$site->existing;
//                        }
//
//                        return false;
//                    },
//                    'message' => Language::_('CWatch.!error.cwatch_domain.unique', true)
//                ],
            ]
        ];

        if ($edit) {
            unset($rules['cwatch_email']);
        }

        return $rules;
    }

    /**
     * Builds and returns the rules required to add/edit a module row (e.g. server)
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getRowRules(&$vars)
    {
        $rules = [
            'username' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('CWatch.!error.username.valid', true)
                ]
            ],
            'password' => [
                'valid' => [
                    'last' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('CWatch.!error.password.valid', true)
                ],
                'valid_connection' => [
                    'rule' => [
                        [$this, 'validateConnection'],
                        $vars['username'],
                        $vars['cwatch_sandbox'],
                    ],
                    'message' => Language::_('CWatch.!error.password.valid_connection', true)
                ]
            ],
        ];

        return $rules;
    }

    /**
     * Validates whether or not the connection details are valid by attempting to fetch
     * the number of accounts that currently reside on the server
     *
     * @return bool True if the connection is valid, false otherwise
     */
    public function validateConnection($password, $username, $sandbox)
    {
        try {
            $api = new CwatchApi($username, $password, $sandbox == 'true');

            $summary_response = $api->getAdmin();
            if (!$summary_response->errors()) {
                return true;
            }
        } catch (Exception $e) {
            // Trap any errors encountered, could not validate connection
        }
        return false;
    }
}
