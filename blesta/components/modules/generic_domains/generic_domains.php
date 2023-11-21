<?php
/**
 * Generic Domains Registrar Module
 *
 * @package blesta
 * @subpackage blesta.components.modules.generic_domains
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class GenericDomains extends RegistrarModule
{
    /**
     * Initializes the module
     */
    public function __construct()
    {
        // Load the language required by this module
        Language::loadLang('generic_domains', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load module config
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
        Configure::load('generic_domains', dirname(__FILE__) . DS . 'config' . DS);
    }

    /**
     * Performs any necessary bootstraping actions. Sets Input errors on
     * failure, preventing the module from being added.
     *
     * @return array A numerically indexed array of meta data containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function install()
    {
        Loader::loadModels($this, ['ModuleManager']);
        Loader::loadComponents($this, ['Record']);

        // Get the ID that this module will have after installation
        $table_status = $this->Record->query('SHOW TABLE STATUS LIKE "modules"')->fetch();
        $module_id = isset($table_status->auto_increment) ? $table_status->auto_increment : 1;

        // Add module row
        $this->Record->insert('module_rows', ['module_id' => $module_id]);
        $module_row_id = $this->Record->lastInsertId();

        // Add module row meta
        $vars = ['name' => 'Generic Module Row'];
        $module_row_meta = $this->addModuleRow($vars);

        foreach ($module_row_meta as $row_meta) {
            $row_meta = array_merge($row_meta, ['module_row_id' => $module_row_id]);
            $this->Record->insert('module_row_meta', $row_meta);
        }
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
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'generic_domains' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Html', 'Widget']);

        $this->view->set('module', $module);

        return $this->view->fetch();
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
    )
    {
        $meta = [];
        $fields = ['domain', 'transfer_key'];
        foreach ($vars as $key => $value) {
            if (in_array($key, $fields)) {
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
        // Get current service fields
        $service_fields = isset($service->fields) ? $this->serviceFieldsToObject($service->fields) : (object) [];

        // Update submitted service fields
        $fields = ['domain', 'transfer_key'];
        foreach ($fields as $field) {
            if (property_exists($service_fields, $field) && isset($vars[$field])) {
                $service_fields->{$field} = $vars[$field];
            }
        }

        return [
            [
                'key' => 'domain',
                'value' => isset($service_fields->domain) ? $service_fields->domain : '',
                'encrypted' => 0
            ],
            [
                'key' => 'tranfer_key',
                'value' => isset($service_fields->tranfer_key) ? $service_fields->tranfer_key : '',
                'encrypted' => 0
            ],
        ];
    }

    /**
     * Returns all fields to display to an admin attempting to add a service with the module.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getAdminAddFields($package, $vars = null)
    {
        // Handle transfer request
        if (isset($vars->transfer) || isset($vars->transfer_key)) {
            $fields = Configure::get('GenericDomains.transfer_fields');
        } else {
            $fields = Configure::get('GenericDomains.domain_fields');
        }

        return $this->arrayToModuleFields($fields, null, $vars);
    }

    /**
     * Returns all fields to display to an admin attempting to edit a service with the module.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields to render as
     *  well as any additional HTML markup to include
     */
    public function getAdminEditFields($package, $vars = null)
    {
        $fields = Configure::get('GenericDomains.transfer_fields');

        return $this->arrayToModuleFields($fields, null, $vars);
    }

    /**
     * Returns all fields to display to an admin attempting to add a service with the module.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getClientAddFields($package, $vars = null)
    {
        // Handle transfer request
        if (isset($vars->transfer) || isset($vars->transfer_key)) {
            $fields = Configure::get('GenericDomains.transfer_fields');

            // We should already have the domain name don't make editable
            $fields['domain']['type'] = 'hidden';
            $fields['domain']['label'] = null;

            return $this->arrayToModuleFields($fields, null, $vars);
        } else {
            // Handle domain registration
            $fields = Configure::get('GenericDomains.domain_fields');

            // We should already have the domain name don't make editable
            $fields['domain']['type'] = 'hidden';
            $fields['domain']['label'] = null;

            $module_fields = $this->arrayToModuleFields($fields, null, $vars);

            return $module_fields;
        }
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
        $tld_options = $fields->label(Language::_('GenericDomains.package_fields.tld_options', true));

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

        return $fields;
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
        if (class_exists('\Iodev\Whois\Factory')) {
            $whois = \Iodev\Whois\Factory::get()->createWhois();

            try {
                return $whois->isDomainAvailable($domain);
            } catch (Exception $e) {
                return true;
            }
        }

        return true;
    }

    /**
     * Get a list of the TLDs supported by the registrar module
     *
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return array A list of all TLDs supported by the registrar module
     */
    public function getTlds($module_row_id = null)
    {
        return Configure::get('GenericDomains.tlds');
    }
}
