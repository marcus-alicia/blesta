<?php
/**
 * Universal Module
 *
 * A module that can be customized to request any fields and post them to any
 * URL or email address
 *
 * @package blesta
 * @subpackage blesta.components.modules.universal_module
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class UniversalModule extends Module
{
    /**
     * @var string A set of reserved form fields that will not be wrapped in a meta[] array
     */
    private static $reserved_fields = ['qty'];

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
        Language::loadLang('universal_module', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * Returns the value used to identify a particular service
     *
     * @param stdClass $service A stdClass object representing the service
     * @return string A value used to identify this service amongst other similar services
     */
    public function getServiceName($service)
    {
        static $rows = [];
        if (!isset($rows[$service->module_row_id])) {
            $row[$service->module_row_id] = $this->getModuleRow($service->module_row_id);
        }

        // Fetch the first service field
        $key = null;
        if (isset($row[$service->module_row_id]->meta->service_field_name_0)) {
            $key = $row[$service->module_row_id]->meta->service_field_name_0;
        }

        // If the key is set, set the value if it is scalar
        $value = null;
        if ($key != null) {
            $fields = $this->serviceFieldsToObject($service->fields);

            if (isset($fields->{$key}) && is_scalar($fields->{$key})) {
                $value = $fields->{$key};
            }
        }

        // If the value could not be found, set it to the first scalar field instead, if any
        if ($value === null) {
            foreach ($service->fields as $field) {
                if (is_scalar($field->value)) {
                    $value = $field->value;
                    $key = $field->key;
                    break;
                }
            }
        }

        // Find and set the service value label (if any), otherwise default to the service value itself
        if ($key) {
            // Fetch module row meta data for finding the service value label
            $module_row_id = (isset($service->module_row_id) ? $service->module_row_id : null);
            $row = $this->getModuleRow($module_row_id);

            if ($row && isset($row->meta)) {
                $value = $this->formatServiceName($row->meta, $key, $value);
            }
        }

        return $value;
    }

    /**
     * Returns the value used to identify a particular package service which has
     * not yet been made into a service. This may be used to uniquely identify
     * an uncreated services of the same package (i.e. in an order form checkout)
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @return string The value used to identify this package service
     * @see Module::getServiceName()
     */
    public function getPackageServiceName($package, array $vars = null)
    {

        // Set the service name to the first scalar meta value
        $key = null;
        $value = null;
        if (isset($vars['meta']) && is_array($vars['meta'])) {
            foreach ($vars['meta'] as $meta_key => $meta_value) {
                if (is_scalar($meta_value)) {
                    $value = $meta_value;
                    $key = $meta_key;
                    break;
                }
            }
        }

        // Format the service name by changing the value to its label if necessary (e.g. drop-down options)
        if ($key) {
            // Fetch module row meta data for finding the service value label
            $module_row_id = (isset($package->module_row) ? $package->module_row : null);
            $row = $this->getModuleRow($module_row_id);

            if ($row && isset($row->meta) && $key) {
                $value = $this->formatServiceName($row->meta, $key, $value);
            }
        }

        return $value;
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
     *  service service and parent service has already been provisioned)
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
        $meta = $this->processService('add', $vars, $package);

        if ($this->Input->errors()) {
            return;
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
        $meta = $this->processService('edit', $vars, $package, $service);

        if ($this->Input->errors()) {
            return;
        }

        return $meta;
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
        if (!$this->sendNotification(
            'service_notice_cancel',
            array_merge($service->fields, [['key' => '_service', 'value' => (object)$service, 'encrypted' => false]]),
            $package->module_row,
            null,
            $package
        )) {
            $this->Input->setErrors(
                [
                    'service_notice_cancel' => [
                        'failed' => Language::_('UniversalModule.!error.service_notice_cancel.failed', true)
                    ]
                ]
            );
            return;
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
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function suspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        if (!$this->sendNotification(
            'service_notice_suspend',
            array_merge($service->fields, [['key' => '_service', 'value' => (object)$service, 'encrypted' => false]]),
            $package->module_row,
            null,
            $package
        )) {
            $this->Input->setErrors(
                [
                    'service_notice_suspend' => [
                        'failed' => Language::_('UniversalModule.!error.service_notice_suspend.failed', true)
                    ]
                ]
            );
            return;
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
        if (!$this->sendNotification(
            'service_notice_unsuspend',
            array_merge($service->fields, [['key' => '_service', 'value' => (object)$service, 'encrypted' => false]]),
            $package->module_row,
            null,
            $package
        )) {
            $this->Input->setErrors(
                [
                    'service_notice_unsuspend' => [
                        'failed' => Language::_('UniversalModule.!error.service_notice_unsuspend.failed', true)
                    ]
                ]
            );
            return;
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
        if (!$this->sendNotification(
            'service_notice_renew',
            array_merge($service->fields, [['key' => '_service', 'value' => (object)$service, 'encrypted' => false]]),
            $package->module_row,
            null,
            $package
        )) {
            $this->Input->setErrors(
                [
                    'service_notice_renew' => [
                        'failed' => Language::_('UniversalModule.!error.service_notice_renew.failed', true)
                    ]
                ]
            );
            return;
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
        if (!$this->sendNotification(
            'service_notice_package_change',
            array_merge($service->fields, [['key' => '_service', 'value' => (object)$service, 'encrypted' => false]]),
            $package_to->module_row,
            null,
            $package_to
        )) {
            $this->Input->setErrors(
                [
                    'service_notice_package_change' => [
                        'failed' => Language::_('UniversalModule.!error.service_notice_package_change.failed', true)
                    ]
                ]
            );
            return;
        }

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
        $meta = $this->processPackage('add', $vars);

        if ($this->Input->errors()) {
            return;
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
        $meta = $this->processPackage('edit', $vars, $package);

        if ($this->Input->errors()) {
            return;
        }

        return $meta;
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'universal_module' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set('module', $module);

        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the add module row page
     *
     * @param array $vars An array of post data submitted to or on the add module row
     *  page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the add module row page
     */
    public function manageAddRow(array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('add_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'universal_module' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set(
            'required_options',
            [
                'true' => Language::_('UniversalModule.true', true),
                'false' => Language::_('UniversalModule.false', true)
            ]
        );
        $this->view->set(
            'encrypt_options',
            [
                'true' => Language::_('UniversalModule.true', true),
                'false' => Language::_('UniversalModule.false', true)
            ]
        );
        $this->view->set('field_types', $this->getFieldTypes());
        $this->view->set('package_notices', $this->getPackageNotices());
        $this->view->set('service_notices', $this->getServiceNotices());
        $this->view->set('vars', (object)$vars);
        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the edit module row page
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of post data submitted to or on the edit module row
     *  page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the edit module row page
     */
    public function manageEditRow($module_row, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('edit_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'universal_module' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        if (empty($vars)) {
            $vars = $this->formatModuleRowFields($module_row->meta);
        }

        $this->view->set(
            'required_options',
            [
                'true' => Language::_('UniversalModule.true', true),
                'false' => Language::_('UniversalModule.false', true)
            ]
        );
        $this->view->set(
            'encrypt_options',
            [
                'true' => Language::_('UniversalModule.true', true),
                'false' => Language::_('UniversalModule.false', true)
            ]
        );
        $this->view->set('field_types', $this->getFieldTypes());
        $this->view->set('package_notices', $this->getPackageNotices());
        $this->view->set('service_notices', $this->getServiceNotices());
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
        $this->Input->setRules($this->getModuleRowRules($vars));

        if ($this->Input->validates($vars)) {
            return $this->formatRowMeta($vars);
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
        $this->Input->setRules($this->getModuleRowRules($vars));

        if ($this->Input->validates($vars)) {
            return $this->formatRowMeta($vars);
        }
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
        $fields = new ModuleFields();

        if (isset($vars->module_row) && $vars->module_row > 0) {
            $row = $this->getModuleRow($vars->module_row);

            $row_fields = [];
            if ($row->meta) {
                $row_fields = $this->formatModuleRowFields($row->meta);

                $field_data = [];
                // Reformat package fields into a more usable format
                foreach ($row_fields['package_fields'] as $key => $values) {
                    foreach ($values as $i => $value) {
                        $field_data[$i][$key] = $value;
                    }
                }

                $this->setModuleFields($fields, $field_data, $vars);
            }
        } elseif (isset($vars->module_id)) {
            $rows = $this->getModuleRows();

            if (empty($rows)) {
                $uri = WEBDIR . Configure::get('Route.admin') . '/settings/company/modules/addrow/' . $vars->module_id;
                $fields->setHtml(Language::_('UniversalModule.getPackageFields.empty_module_row', true, $uri));
            } else {
                $fields->setHtml('
					<script type="text/javascript">
						$(document).ready(function() {
							// Fetch initial module options
							fetchModuleOptions();
						});
					</script>
				');
            }
        } else {
            $fields->setHtml('
				<script type="text/javascript">
					$(document).ready(function() {
						// Fetch initial module options
						fetchModuleOptions();
					});
				</script>
			');
        }

        return $fields;
    }

    /**
     * Returns all fields to display to an admin attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param stdClass $vars stdClass A stdClass object representing a set of post fields
     * @param bool $client True if the admin fields are being retrieved for the client interface,
     *  or false otherwise (optional, default false)
     * @return ModuleFields A ModuleFields object, containg the fields to render as well as
     *  any additional HTML markup to include
     */
    public function getAdminAddFields($package, $vars = null, $client = false)
    {
        $fields = new ModuleFields();

        if (!isset($vars->meta)) {
            $vars->meta = [];
        }

        if (isset($package->module_row) && $package->module_row > 0) {
            $row = $this->getModuleRow($package->module_row);

            // Set the module row, which will allow us to reference it later when getName() is invoked
            $this->setModuleRow($row);

            $row_fields = [];
            if ($row->meta) {
                $row_fields = $this->formatModuleRowFields($row->meta);

                $field_data = [];
                // Reformat package fields into a more usable format
                foreach ($row_fields['service_fields'] as $key => $values) {
                    foreach ($values as $i => $value) {
                        // Allow admins to view hidden fields by converting them to text fields
                        if (!$client && $key == 'type' && $value == 'hidden') {
                            $value = 'text';
                        }

                        $field_data[$i][$key] = $value;
                    }
                }

                $this->setModuleFields($fields, $field_data, $vars);
            }
        }

        return $fields;
    }

    /**
     * Returns all fields to display to a client attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as well as
     *  any additional HTML markup to include
     */
    public function getClientAddFields($package, $vars = null)
    {
        // Same as admin
        return $this->getAdminAddFields($package, $vars, true);
    }

    /**
     * Returns all fields to display to an admin attempting to edit a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as well as
     *  any additional HTML markup to include
     */
    public function getAdminEditFields($package, $vars = null)
    {
        $fields = new ModuleFields();

        if (!isset($vars->meta)) {
            $vars->meta = [];
        }

        if (isset($vars->module_row_id) && $vars->module_row_id > 0) {
            $row = $this->getModuleRow($vars->module_row_id);

            // Set the module row, which will allow us to reference it later when getName() is invoked
            $this->setModuleRow($row);

            $row_fields = [];
            if ($row->meta) {
                $row_fields = $this->formatModuleRowFields($row->meta);

                $field_data = [];
                // Reformat package fields into a more usable format
                foreach ($row_fields['service_fields'] as $key => $values) {
                    foreach ($values as $i => $value) {
                        // Allow admins to view hidden fields by converting them to text fields
                        if ($key == 'type' && $value == 'hidden') {
                            $value = 'text';
                        }

                        $field_data[$i][$key] = $value;
                    }
                }

                $this->setModuleFields($fields, $field_data, $vars);
            }
        }

        return $fields;
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
        return null;
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
        if (!array_key_exists('meta', (array)$vars)) {
            $vars['meta'] = $vars;
        }

        if ($package) {
            $module_row_id = $package->module_row;
        } else {
            $module_row_id = isset($vars['module_row']) ? $vars['module_row'] : null;
        }

        $this->Input->setRules($this->getServiceRules($module_row_id, $vars));

        if (!isset($vars['meta'])) {
            $vars['meta'] = [];
        }

        $validation_fields = array_merge(
            $vars['meta'],
            array_intersect_key($vars, array_flip(self::$reserved_fields))
        );
        return $this->Input->validates($validation_fields);
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
        if (!array_key_exists('meta', (array)$vars)) {
            $vars['meta'] = $vars;
        }

        $this->Input->setRules($this->getServiceRules($service->module_row_id, $vars));

        if (!isset($vars['meta'])) {
            $vars['meta'] = [];
        }

        $validation_fields = array_merge(
            $vars['meta'],
            array_intersect_key($vars, array_flip(self::$reserved_fields))
        );
        return $this->Input->validates($validation_fields);
    }

    /**
     * Returns the rule set for adding/editing a service
     *
     * @param int $module_row_id The ID of the module row to use
     * @param array $vars A list of input vars
     * @return array Service rules
     */
    private function getServiceRules($module_row_id, array $vars = null)
    {
        $row = $this->getModuleRow($module_row_id);

        $rules = [];
        if ($row && $row->meta->service_rules != '' && isset($vars['meta'])) {
            $rules = json_decode($row->meta->service_rules, true);
        }

        $fields = $this->formatModuleRowFields($row->meta);

        // Set required rules
        if (isset($fields['service_fields']['required'])) {
            foreach ($fields['service_fields']['required'] as $i => $required) {
                $name = $fields['service_fields']['name'][$i];
                if ($required == 'true') {
                    $is_array = (isset($vars['meta'][$name]) && is_array($vars['meta'][$name]));
                    $rules[$name]['required'] = [
                        'rule' => $is_array ? 'count' : 'isEmpty',
                        'negate' => !$is_array,
                        'message' => Language::_(
                            'UniversalModule.!error.service_field.required',
                            true,
                            $fields['service_fields']['label'][$i]
                        )
                    ];
                }
            }
        }

        return $rules;
    }

    /**
     * Process Packages add/edit
     *
     * @param string $type The type of process (add/edit)
     * @param array $vars An array of key/value pairs
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    private function processPackage($type, array $vars, $package = null)
    {
        $module_row_id = null;
        if (isset($vars['module_row']) && $vars['module_row']) {
            $module_row_id = $vars['module_row'];
        } elseif ($package) {
            $module_row_id = $package->module_row;
        }
        $row = $this->getModuleRow($module_row_id);

        if (!$row) {
            $this->Input->setErrors([
                'module_row' => [
                    'invalid' => Language::_('UniversalModule.!error.module_row.invalid', true)
                ]
            ]);
            return;
        }

        $rules = [];
        if (isset($row->meta->package_rules) && $row->meta->package_rules != '' && isset($vars['meta'])) {
            $rules = json_decode($row->meta->package_rules, true);
        }

        $fields = $this->formatModuleRowFields($row->meta);

        // Set required rules
        if (isset($fields['package_fields']['required'])) {
            foreach ($fields['package_fields']['required'] as $i => $required) {
                $name = $fields['package_fields']['name'][$i];
                if ($required == 'true') {
                    $is_array = (isset($vars['meta'][$name]) && is_array($vars['meta'][$name]));
                    $rules[$name]['required'] = [
                        'rule' => $is_array ? 'count' : 'isEmpty',
                        'negate' => !$is_array,
                        'message' => Language::_(
                            'UniversalModule.!error.package_field.required',
                            true,
                            $fields['package_fields']['label'][$i]
                        )
                    ];
                }
            }
        }

        $this->Input->setRules($rules);
        if (!$this->Input->validates($vars['meta'])) {
            return;
        }

        $meta = [];
        if (isset($fields['package_fields']['name'])) {
            foreach ($fields['package_fields']['name'] as $i => $value) {
                if ($fields['package_fields']['type'][$i] == 'secret') {
                    continue;
                }

                $meta[] = [
                    'key' => $value,
                    'value' => isset($vars['meta'][$value]) ? $vars['meta'][$value] : '',
                    'encrypted' => $fields['package_fields']['encrypt'][$i] == 'true' ? 1 : 0
                ];
            }
        }

        if (!$this->sendNotification(
            'package_notice_' . $type,
            $meta,
            $module_row_id,
            $vars,
            $package ? (object)$package : null
        )) {
            $this->Input->setErrors(
                [
                    'package_notice_' . $type => [
                        'failed' => Language::_('UniversalModule.!error.package_notice_' . $type . '.failed', true)
                    ]
                ]
            );
            return;
        }

        return $meta;
    }

    /**
     * Process Services add/edit
     *
     * @param string $type The type of process (add/edit)
     * @param array $vars An array of key/value pairs
     * @param stdClass $package A stdClass object representing the current package (optional)
     * @param stdClass $service A stdClass object representing the current service (optional)
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    private function processService($type, array $vars, $package = null, $service = null)
    {
        if ($package) {
            $module_row_id = $package->module_row;
        } else {
            $module_row_id = isset($vars['module_row']) ? $vars['module_row'] : null;
        }

        if ($type == 'edit') {
            $this->validateServiceEdit($service, $vars);
        } else {
            $this->validateService($package, $vars);
        }

        if ($this->Input->errors()) {
            return;
        }

        $row = $this->getModuleRow($module_row_id);
        $fields = $this->formatModuleRowFields($row->meta);

        $meta = [];
        if (isset($fields['service_fields']['name'])) {
            foreach ($fields['service_fields']['name'] as $i => $value) {
                if ($fields['service_fields']['type'][$i] == 'secret') {
                    continue;
                }

                if (isset($vars['meta'][$value]) || isset($vars[$value])) {
                    if (!$meta) {
                        $meta = [];
                    }

                    $meta[] = [
                        'key' => $value,
                        'value' => isset($vars['meta'][$value]) ? $vars['meta'][$value] : $vars[$value],
                        'encrypted' => $fields['service_fields']['encrypt'][$i] == 'true' ? 1 : 0
                    ];
                }
            }
        }

        if (isset($vars['use_module']) && $vars['use_module'] == 'true') {
            if (!$this->sendNotification(
                'service_notice_' . $type,
                array_merge(
                    $meta,
                    $service ? [['key' => '_service', 'value' => (object)$service, 'encrypted' => false]] : []
                ),
                $module_row_id,
                $vars,
                $package
            )) {
                $this->Input->setErrors(
                    [
                        'service_notice_' . $type => [
                            'failed' => Language::_('UniversalModule.!error.service_notice_' . $type . '.failed', true)
                        ]
                    ]
                );
                return;
            }
        }

        return $meta;
    }

    /**
     * Sets fields into the ModuleFields object according to $field_data
     *
     * @param ModuleFields $fields The ModuleFields object to set fields to
     * @param array $field_data A numerically indexed array of field data including:
     *  - type
     *  - label
     *  - name
     *  - values
     * @param stdClass $vars A stdClass object representing input fields
     */
    private function setModuleFields(ModuleFields $fields, array $field_data, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        foreach ($field_data as $field) {
            $options = $this->unserializeMetaValues($field['values']);

            $field_type = 'field' . ucfirst($field['type']);
            $field_name = 'meta[' . $field['name'] . ']';
            $field_value = (isset($vars->meta[$field['name']])
                ? $vars->meta[$field['name']]
                : (isset($vars->{$field['name']}) ? $vars->{$field['name']} : $field['values'])
            );

            if (in_array($field['name'], self::$reserved_fields)) {
                $field_name = $field['name'];
                $field_value = (isset($vars->{$field['name']}) ? $vars->{$field['name']} : null);
            }

            switch ($field['type']) {
                case 'text':
                case 'hidden':
                case 'textarea':
                    $label = $fields->label($field['label'], 'uni_' . $field['name']);
                    $label->attach($fields->{$field_type}($field_name,
                        $field_value, ['id'=>'uni_' . $field['name']]));
                    $fields->setField($label);
                    break;
                case 'password':
                    $label = $fields->label($field['label'], 'uni_' . $field['name']);
                    $label->attach($fields->{$field_type}($field_name,
                        ['id'=>'uni_' . $field['name'], 'value' => $field_value]));
                    $fields->setField($label);
                    break;
                case 'select':
                    $label = $fields->label($field['label'], 'uni_' . $field['name']);
                    $label->attach($fields->{$field_type}($field_name, $options,
                        $field_value, ['id'=>'uni_' . $field['name']]));
                    $fields->setField($label);
                    break;
                case 'radio':
                case 'checkbox':
                    $label = $fields->label($field['label'], 'uni_' . $field['name']);
                    foreach ($options as $key => $value) {
                        $field_label = $fields->label($value, 'uni_' . $field['name'] . '_' . $key);

                        $checked = in_array($key, (array)$field_value);

                        $label->attach($fields->{$field_type}($field_name . ($field['type'] == 'checkbox' ? '[]' : ''),
                            $key, $checked, ['id'=>'uni_' . $field['name'] . '_' . $key], $field_label));
                    }
                    $fields->setField($label);
                    break;
            }
        }
    }

    /**
     * Sends notification for the given action if supported by the module row
     *
     * @param string $action The action to send a notification for
     * @param array $meta A numerically indexed array of meta fields containing:
     *  - key The key for this meta field
     *  - value The value for this key
     * @param int $module_row_id The ID of the module row to send the notification for
     * @param array $additional_fields An array of key/value pairs to send in the notification
     * @param stdClass $package A stdClass object representing the package (if any)
     * @return bool True if the notice was successful, false otherwise
     */
    private function sendNotification(
        $action,
        $meta,
        $module_row_id = null,
        $additional_fields = null,
        $package = null
    ) {
        $row = $this->getModuleRow($module_row_id);

        if ($row) {
            $tags = $this->serviceFieldsToObject((array)$meta);

            if ($package && !empty($package->meta)) {
                foreach ($package->meta as $key => $value) {
                    if (!isset($tags->{$key})) {
                        $tags->{$key} = $value;
                    }
                }
            }

            if ($package) {
                $tags->_package = $package;
            }

            // Look for 'secret' package fields to append
            $meta_fields = $this->formatModuleRowFields($row->meta);
            if (isset($meta_fields['package_fields']['type'])) {
                foreach ($meta_fields['package_fields']['type'] as $i => $type) {
                    $key = $meta_fields['package_fields']['name'][$i];
                    if ($type == 'secret' && !isset($tags->{$key})) {
                        $tags->{$key} = $meta_fields['package_fields']['values'][$i];
                    }
                }
            }

            // Look for 'secret' service fields to append if this is a service notice
            if (strpos($action, 'service') !== false && isset($meta_fields['service_fields']['type'])) {
                foreach ($meta_fields['service_fields']['type'] as $i => $type) {
                    if ($type == 'secret') {
                        $tags->{$meta_fields['service_fields']['name'][$i]}
                            = $meta_fields['service_fields']['values'][$i];
                    }
                }
            }

            // Add the tags for custom service/package field value names
            $tags = $this->getCustomFieldTags($tags, $module_row_id, $additional_fields);

            $tags->_action = $action;
            $tags->_other = $additional_fields;

            if (isset($row->meta->{$action}) && trim($row->meta->{$action}) != '') {
                if ($this->isUrl($row->meta->{$action})) {
                    $code = str_replace('notice', 'code', $action);
                    $response = str_replace('notice', 'response', $action);

                    return $this->sendHttpNotice(
                        $row->meta->{$action},
                        (array)$tags,
                        $row->meta->{$code},
                        $row->meta->{$response}
                    );
                } else {
                    $this->sendEmailNotice($action, (array)$tags, $row->meta);
                }
            }
        }

        return true;
    }

    /**
     * Adds a name tag for each of the custom package or service fields already included in $tags
     *
     * @param stdClass $tags The current list of tags
     * @param int $module_row_id The ID of the module row being used
     * @param array $vars A list of user input
     * @return stdClass The new list of tags, which includes the new custom field names
     */
    private function getCustomFieldTags(stdClass $tags, $module_row_id = null, array $vars = null)
    {
        $fields = array_merge(
            $this->getAdminAddFields((object)['module_row' => $module_row_id], (object)$vars)->getFields(),
            $this->getPackageFields((object)$vars)->getFields()
        );

        if (empty($fields)) {
            return $tags;
        }

        // Search through the module provided custom service fields
        foreach ($fields as $field) {
            // Check each field against the values given in the tags
            foreach ($field->fields as $index => $field_value) {
                // Isolate the actual field name
                $field_name = substr(
                    $field_value->params['name'],
                    5,
                    strpos($field_value->params['name'], ']') - 5
                );
                if (property_exists($tags, $field_name)) {
                    switch ($field_value->type) {
                        case 'fieldSelect':
                            // Get the value name from the field's list of options
                            $tags->{$field_name . '_name'} = $field_value->params['options'][$tags->{$field_name}];
                            break;
                        case 'fieldRadio':
                            // Use this value's name if it is the selected value
                            if ($field_value->params['value'] == $tags->{$field_name}) {
                                $tags->{$field_name . '_name'} = $field_value->label->params['name'];
                            }
                            break;
                        case 'fieldCheckbox':
                            // Get the name of this value if it is checked
                            if (in_array($field_value->params['value'], $tags->{$field_name})) {
                                if (!isset($tags->{$field_name . '_values'})) {
                                    $tags->{$field_name . '_values'} = [];
                                }

                                $tags->{$field_name . '_values'}[$index] = [
                                    'name' => $field_value->label->params['name'],
                                    'value' => $field_value->params['value']
                                ];
                            }
                            break;
                        default:
                            // If this field has a type with no value => name mapping, simply use the value
                            $tags->{$field_name . '_name'} = $tags->{$field_name};
                    }
                }
            }
        }

        return $tags;
    }

    /**
     * Sends an email notification to the given address with the given tags
     *
     * @param string $action The action to send the notification for
     * @param array $tags A key/value pairs of tags and their replacement data
     * @param stdClass $meta A stdClass object of module row meta field data
     * @return bool True if the email was successfully sent, false otherwise
     */
    private function sendEmailNotice($action, $tags, $meta)
    {
        Loader::loadModels($this, ['Emails']);

        $to = $meta->{$action};
        $from = null;
        $subject = null;
        $body = null;

        if (strpos($action, 'service') !== false) {
            $from = $meta->service_email_from;
            $subject = $meta->service_email_subject;
            $body = ['text' => $meta->service_email_text, 'html' => $meta->service_email_html];
        } else {
            $from = $meta->package_email_from;
            $subject = $meta->package_email_subject;
            $body = ['text' => $meta->package_email_text, 'html' => $meta->package_email_html];
        }

        $this->Emails->sendCustom($from, $from, $to, $subject, $body, $tags);

        if (($errors = $this->Emails->errors())) {
            $this->Input->setErrors($errors);
            return false;
        }
        return true;
    }

    /**
     * Sends an HTTP POST request to the given URL with the given arguments
     *
     * @param string $url The URL to post
     * @param array $args An array of key/value post fields
     * @param string $response_code The response code to accept for successful responses
     * @param string $response The response to expect for successful responses, may be a regular expression
     * @return bool True on success, false on error
     */
    private function sendHttpNotice($url, $args, $response_code = null, $response = null)
    {
        // Log request
        $this->log($url, serialize($args), 'input', true);

        $pass = true;
        $output = $this->httpRequest('POST', $url, $args);

        if ($response_code != '') {
            if (isset($this->Http)) {
                if ($response_code != $this->Http->responseCode()) {
                    $pass = false;
                }
            }
        }

        if ($response != '') {
            if (strpos($output, $response) === false) {
                $pass = false;
            }
        }

        // Log output
        $this->log($url, $output, 'output', $pass);

        return $pass;
    }

    /**
     * Formats module row input fields into a proper format required by
     * Module::addModuleRow() and Module::editModuleRow().
     *
     * @param array An array of input key/value pairs
     * @return array A numerically indexed array of meta fields for the module row containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    private function formatRowMeta(array &$vars)
    {
        $meta = [];
        $meta_fields = ['name', 'package_rules', 'service_rules',
            'package_email_from', 'package_email_subject', 'package_email_html', 'package_email_text',
            'service_email_from', 'service_email_subject', 'service_email_html', 'service_email_text'
        ];

        foreach ($this->getPackageNotices() as $key => $value) {
            $meta_fields[] = 'package_notice_' . $key;
            $meta_fields[] = 'package_code_' . $key;
            $meta_fields[] = 'package_response_' . $key;
        }

        foreach ($this->getServiceNotices() as $key => $value) {
            $meta_fields[] = 'service_notice_' . $key;
            $meta_fields[] = 'service_code_' . $key;
            $meta_fields[] = 'service_response_' . $key;
        }

        foreach ($vars as $key => $value) {
            if (in_array($key, $meta_fields)) {
                $meta[] = [
                    'key'=>$key,
                    'value'=>$value
                ];
            }
        }

        if (isset($vars['package_fields'])
            && isset($vars['package_fields']['label'])
            && is_array($vars['package_fields']['label'])
        ) {
            for ($j = 0, $i = 0, $total = count($vars['package_fields']['label']); $i < $total; $i++) {
                if ($vars['package_fields']['name'][$i] == '') {
                    continue;
                }

                $meta[] = [
                    'key' => 'package_field_label_' . $j,
                    'value' => $vars['package_fields']['label'][$i]
                ];
                $meta[] = [
                    'key' => 'package_field_name_' . $j,
                    'value' => $vars['package_fields']['name'][$i]
                ];
                $meta[] = [
                    'key' => 'package_field_type_' . $j,
                    'value' => $vars['package_fields']['type'][$i]
                ];
                $meta[] = [
                    'key' => 'package_field_values_' . $j,
                    'value' => $vars['package_fields']['values'][$i]
                ];
                $meta[] = [
                    'key' => 'package_field_required_' . $j,
                    'value' => $vars['package_fields']['required'][$i]
                ];
                $meta[] = [
                    'key' => 'package_field_encrypt_' . $j,
                    'value' => $vars['package_fields']['encrypt'][$i]
                ];

                $j++;
            }
        }

        if (isset($vars['service_fields'])
            && isset($vars['service_fields']['label'])
            && is_array($vars['service_fields']['label'])
        ) {
            for ($j = 0, $i = 0, $total = count($vars['service_fields']['label']); $i < $total; $i++) {
                if ($vars['service_fields']['name'][$i] == '') {
                    continue;
                }

                $meta[] = [
                    'key' => 'service_field_label_' . $j,
                    'value' => $vars['service_fields']['label'][$i]
                ];
                $meta[] = [
                    'key' => 'service_field_name_' . $j,
                    'value' => $vars['service_fields']['name'][$i]
                ];
                $meta[] = [
                    'key' => 'service_field_type_' . $j,
                    'value' => $vars['service_fields']['type'][$i]
                ];
                $meta[] = [
                    'key' => 'service_field_values_' . $j,
                    'value' => $vars['service_fields']['values'][$i]
                ];
                $meta[] = [
                    'key' => 'service_field_required_' . $j,
                    'value' => $vars['service_fields']['required'][$i]
                ];
                $meta[] = [
                    'key' => 'service_field_encrypt_' . $j,
                    'value' => $vars['service_fields']['encrypt'][$i]
                ];

                $j++;
            }
        }

        return $meta;
    }

    /**
     * Converts module row meta fields from key/value pairs to array sets suitable
     * for use in forms.
     *
     * @param stdClass $module_row_meta An object of module row meta fields
     * @return array An array of formatted module row meta fields
     */
    private function formatModuleRowFields($module_row_meta)
    {
        $fields = ['package_fields' => [], 'service_fields' => []];
        foreach ($module_row_meta as $key => $value) {
            $index = ltrim(strrchr($key, '_'), '_');
            if (substr($key, 0, 14) == 'package_field_') {
                $key = str_replace('_' . $index, '', str_replace('package_field_', '', $key));
                $fields['package_fields'][$key][$index] = $value;
            } elseif (substr($key, 0, 14) == 'service_field_') {
                $key = str_replace('_' . $index, '', str_replace('service_field_', '', $key));
                $fields['service_fields'][$key][$index] = $value;
            } else {
                $fields[$key] = $value;
            }
        }
        return $fields;
    }

    /**
     * Formats the service name value to its label, if any
     *
     * @param stdClass $meta An stdClass object containing of key/value meta data to
     *  be used to determine the service name
     * @param string $key The meta key selected
     * @param string $value The meta value selected
     */
    private function formatServiceName(stdClass $meta, $key, $value)
    {
        $value = ($value ? $value : null);

        // Determine if the value should be set to a service field name
        if ($key && !empty($meta)) {
            $row_fields = $this->formatModuleRowFields($meta);

            // Reformat service fields into a more usable format
            $field_data = [];
            foreach ($row_fields['service_fields'] as $field_key => $values) {
                foreach ($values as $i => $field_value) {
                    $field_data[$i][$field_key] = $field_value;
                }
            }

            // Find the matching service field whose values are used as the service field name
            $field_values = [];
            foreach ($field_data as $fields) {
                if (isset($fields['name']) && $fields['name'] == $key) {
                    // Format the meta values
                    $field_values = $this->unserializeMetaValues($fields['values']);
                    break;
                }
            }

            // Set the service field name to the field values' label
            if (isset($field_values[$value])) {
                $value = $field_values[$value];
            }
        }

        return $value;
    }

    /**
     * Unserialize meta values of the format key2:value|key2:value2
     *
     * @param string $values A serialized set of values
     * @return array An array of key/value pairs
     */
    private function unserializeMetaValues($values)
    {
        if ($values == '') {
            return [];
        }

        $pairs = preg_split('~(?<!\\\)' . preg_quote('|', '~') . '~', $values);

        $options = [];
        foreach ($pairs as $pair) {
            $pair = preg_split('~(?<!\\\)' . preg_quote(':', '~') . '~', $pair);
            //$pair = preg_split('~\\\\.(*SKIP)(*FAIL)|\:~s', $pair);
            if (count($pair) == 2) {
                $options[stripslashes($pair[0])] = stripslashes($pair[1]);
            }
        }

        return $options;
    }

    /**
     * Returns a key/value pair of package notices, which are events that trigger
     * a HTTP POST or Email to a given location
     *
     * @return array An array of key/value pair package notices, where each key is the
     *  notice type and each value is its name
     */
    private function getPackageNotices()
    {
        return [
            'add' => Language::_('UniversalModule.getpackagenotices.add', true),
            'edit' => Language::_('UniversalModule.getpackagenotices.edit', true)
        ];
    }

    /**
     * Returns a key/value pair of service notices, which are events that trigger
     * a HTTP POST or Email to a given location
     *
     * @return array An array of key/value pair service notices, where each key is the
     *  notice type and each value is its name
     */
    private function getServiceNotices()
    {
        return [
            'add' => Language::_('UniversalModule.getservicenotices.add', true),
            'edit' => Language::_('UniversalModule.getservicenotices.edit', true),
            'suspend' => Language::_('UniversalModule.getservicenotices.suspend', true),
            'unsuspend' => Language::_('UniversalModule.getservicenotices.unsuspend', true),
            'cancel' => Language::_('UniversalModule.getservicenotices.cancel', true),
            'renew' => Language::_('UniversalModule.getservicenotices.renew', true),
            'package_change' => Language::_('UniversalModule.getservicenotices.package_change', true),
        ];
    }

    /**
     * Returns a key/value pair of all input field types supported
     *
     * @return array An array of key/value pairs of input field types supported, where each key
     *  is the field type and each value it its name
     */
    private function getFieldTypes()
    {
        return [
            'text' => Language::_('UniversalModule.getfieldtypes.text', true),
            'textarea' => Language::_('UniversalModule.getfieldtypes.textarea', true),
            'password' => Language::_('UniversalModule.getfieldtypes.password', true),
            'select' => Language::_('UniversalModule.getfieldtypes.select', true),
            'radio' => Language::_('UniversalModule.getfieldtypes.radio', true),
            'checkbox' => Language::_('UniversalModule.getfieldtypes.checkbox', true),
            'hidden' => Language::_('UniversalModule.getfieldtypes.hidden', true),
            'secret' => Language::_('UniversalModule.getfieldtypes.secret', true),
        ];
    }

    /**
     * Returns all rules to validate when adding/edit a module row
     *
     * @return array An array of rules to validate when adding/editing a module row
     */
    private function getModuleRowRules(array $vars)
    {
        $rules = [
            'name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('UniversalModule.!error.name.empty', true)
                ]
            ]
        ];

        foreach ($vars as $key => $value) {
            if (strpos($key, 'service_notice_') !== false && $value != '' && !$this->isUrl($value)) {
                $rules['service_email_from']['required'] = [
                    'rule' => 'isEmail',
                    'message' => Language::_('UniversalModule.!error.service_email_from.required', true)
                ];
            } elseif (strpos($key, 'package_notice_') !== false && $value != '' && !$this->isUrl($value)) {
                $rules['package_email_from']['required'] = [
                    'rule' => 'isEmail',
                    'message' => Language::_('UniversalModule.!error.package_email_from.required', true)
                ];
            }
        }

        return $rules;
    }

    /**
     * Verifies whether or not the givne str is a URL
     *
     * @param string $str A string
     * @return bool True if $str is a URL, false otherwise
     */
    private function isUrl($str)
    {
        return preg_match("#^\S+://\S+\.\S+.+$#", $str);
    }
}
