<?php

use Blesta\Core\Util\Common\Traits\Container;
use PhillipsData\PrioritySchedule\ScheduleInterface;
use PhillipsData\PrioritySchedule\FirstAvailable;
use PhillipsData\PrioritySchedule\RoundRobin;

/**
 * Abstract class that all Modules must extend
 *
 * @package blesta
 * @subpackage blesta.components.modules
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class Module
{
    // Load traits
    use Container;

    /**
     * @var Http An Http object, used to make HTTP requests
     */
    protected $Http;
    /**
     * @var stdClass A stdClass object representing the configuration for this module
     */
    protected $config;
    /**
     * @var string The name of the module row meta field used as the maximum limit
     */
    protected $module_row_field_limit = 'account_limit';
    /**
     * @var string The name of the module row meta field used as the current total
     */
    protected $module_row_field_total = 'account_count';
    /**
     * @var string The base URI for the requested module action
     */
    public $base_uri;
    /**
     * @var stdClass A stdClass object representing the module
     */
    private $module;
    /**
     * @var stdClass A stdClass object representing the module row
     */
    private $module_row;
    /**
     * @var array An array of messages keyed by type (e.g. ['success' => ['message' => ['Message 1', 'Message 2']]])
     */
    private $messages = [];
    /**
     * @var string The random ID to identify the group of this module request for logging purposes
     */
    private $log_group;

    /**
     * Returns the name of this module
     *
     * @return string The common name of this module
     */
    public function getName()
    {
        if (isset($this->config->name)) {
            return $this->translate($this->config->name);
        }
        return null;
    }

    /**
     * Returns the description of this module
     *
     * @return string The description of this module
     */
    public function getDescription()
    {
        if (isset($this->config->description)) {
            return $this->translate($this->config->description);
        }
        return null;
    }

    /**
     * Returns the version of this module
     *
     * @return string The current version of this module
     */
    public function getVersion()
    {
        if (isset($this->config->version)) {
            return $this->config->version;
        }
        return null;
    }

    /**
     * Returns the type of this module
     *
     * @return string The type of this module
     */
    public function getType()
    {
        if (isset($this->config->type)) {
            return $this->config->type;
        }
        return 'generic';
    }

    /**
     * Returns the name and URL for the authors of this module
     *
     * @return array A numerically indexed array that contains an array
     *  with key/value pairs for 'name' and 'url', representing the name
     *  and URL of the authors of this module
     */
    public function getAuthors()
    {
        if (isset($this->config->authors)) {
            foreach ($this->config->authors as &$author) {
                $author = (array)$author;
            }
            return $this->config->authors;
        }
        return null;
    }

    /**
     * Returns the value used to identify a particular service
     *
     * @param stdClass $service A stdClass object representing the service
     * @return string A value used to identify this service amongst other similar services
     */
    public function getServiceName($service)
    {
        if (isset($this->config->service->name_key)) {
            foreach ($service->fields as $field) {
                if ($field->key == $this->config->service->name_key) {
                    return $field->value;
                }
            }
        }
        return null;
    }

    /**
     * Returns a noun used to refer to a module row (e.g. "Server", "VPS", "Reseller Account", etc.)
     *
     * @return string The noun used to refer to a module row
     */
    public function moduleRowName()
    {
        if (isset($this->config->module->row)) {
            return $this->translate($this->config->module->row);
        }
        return null;
    }

    /**
     * Returns a noun used to refer to a module row in plural form (e.g. "Servers", "VPSs", "Reseller Accounts", etc.)
     *
     * @return string The noun used to refer to a module row in plural form
     */
    public function moduleRowNamePlural()
    {
        if (isset($this->config->module->rows)) {
            return $this->translate($this->config->module->rows);
        }
        return null;
    }

    /**
     * Returns a noun used to refer to a module group (e.g. "Server Group", "Cloud", etc.)
     *
     * @return string The noun used to refer to a module group
     */
    public function moduleGroupName()
    {
        if (isset($this->config->module->group)) {
            return $this->translate($this->config->module->group);
        }
        return null;
    }

    /**
     * Returns the key used to identify the primary field from the set of module row meta fields.
     * This value can be any of the module row meta fields.
     *
     * @return string The key used to identify the primary field from the set of module row meta fields
     */
    public function moduleRowMetaKey()
    {
        if (isset($this->config->module->row_key)) {
            return $this->config->module->row_key;
        }
        return null;
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
    }

    /**
     * Performs any necessary cleanup actions. Sets Input errors on failure
     * after the module has been uninstalled.
     *
     * @param int $module_id The ID of the module being uninstalled
     * @param bool $last_instance True if $module_id is the last instance
     *  across all companies for this module, false otherwise
     */
    public function uninstall($module_id, $last_instance)
    {
    }

    /**
     * Returns the relative path from this module's directory to the logo for
     * this module. Defaults to views/default/images/logo.png
     *
     * @return string The relative path to the module's logo
     */
    public function getLogo()
    {
        if (isset($this->config->logo)) {
            return $this->config->logo;
        }
        return 'views/default/images/logo.png';
    }

    /**
     * Runs the cron task identified by the key used to create the cron task
     *
     * @param string $key The key used to create the cron task
     * @return array An array containing the log lines to be displayed when executing the cron task
     * @see CronTasks::add()
     */
    public function cron($key)
    {
        return [];
    }

    /**
     * Sets the module to be used for any subsequent requests
     *
     * @param stdClass A stdClass object representing the module
     * @see ModuleManager::get()
     */
    final public function setModule($module)
    {
        $this->module = $module;
    }

    /**
     * Sets the module row to be used for any subsequent requests
     *
     * @param stdClass A stdClass object representing the module row
     * @see ModuleManager::getRow()
     */
    final public function setModuleRow($module_row)
    {
        $this->module_row = $module_row;
    }

    /**
     * Fetches the module currently in use
     *
     * @return stdClass A stdClass object representing the module
     */
    final public function getModule()
    {
        return $this->module;
    }

    /**
     * Fetches the requested module row for the current module
     *
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return stdClass A stdClass object representing the module row
     */
    final public function getModuleRow($module_row_id = null)
    {
        if ($module_row_id) {
            if (!isset($this->ModuleManager)) {
                Loader::loadModels($this, ['ModuleManager']);
            }

            $row = $this->ModuleManager->getRow($module_row_id);

            if ($row && $row->module_id == $this->module->id) {
                return $row;
            }
            return false;
        }
        return $this->module_row;
    }

    /**
     * Returns all module rows available to the current module
     *
     * @param int $module_group_id The ID of the module group to filter rows by
     * @return array An array of stdClass objects each representing a module row, false if no module set
     */
    final public function getModuleRows($module_group_id = null)
    {
        if (!isset($this->module->id)) {
            return false;
        }

        if (!isset($this->ModuleManager)) {
            Loader::loadModels($this, ['ModuleManager']);
        }

        return $this->ModuleManager->getRows($this->module->id, $module_group_id);
    }

    /**
     * Returns the value used to identify a particular package service which has
     * not yet been made into a service. This may be used to uniquely identify
     * an uncreated service of the same package (i.e. in an order form checkout)
     *
     * @param stdClass $packages A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @return string The value used to identify this package service
     * @see Module::getServiceName()
     */
    public function getPackageServiceName($packages, array $vars = null)
    {
        if (isset($this->config->package->name_key) && isset($vars[$this->config->package->name_key])) {
            return $vars[$this->config->package->name_key];
        }
        return null;
    }

    /**
     * Attempts to validate service info. This is the top-level error checking method. Sets Input errors on failure.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request:
     *
     *  - configoptions An array of key/value pairs of package options where
     *      the key is the package option label and the value is the option value (optional)
     * @return bool True if the service validates, false otherwise. Sets Input errors when false.
     */
    public function validateService($package, array $vars = null)
    {
        return true;
    }

    /**
     * Attempts to validate an existing service against a set of service info updates. Sets Input errors on failure.
     *
     * @param stdClass $service A stdClass object representing the service to validate for editing
     * @param array $vars An array of user-supplied info to satisfy the request:
     *
     *  - configoptions An array of key/value pairs of package options where
     *      the key is the package option label and the value is the option value (optional)
     * @return bool True if the service update validates or false otherwise. Sets Input errors when false.
     */
    public function validateServiceEdit($service, array $vars = null)
    {
        return true;
    }

    /**
     * Adds the service to the remote server. Sets Input errors on failure,
     * preventing the service from being added.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request:
     *
     *  - configoptions An array of key/value pairs of package options where
     *      the key is the package option label and the value is the option value (optional)
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being added (if the current service is an addon
     *  service and parent service has already been provisioned)
     * @param string $status The status of the service being added. These include:
     *
     *  - active
     *  - canceled
     *  - pending
     *  - suspended
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *
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
        return [];
    }

    /**
     * Edits the service on the remote server. Sets Input errors on failure,
     * preventing the service from being edited.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $vars An array of user supplied info to satisfy the request:
     *
     *  - configoptions An array of key/value pairs of package options where
     *      the key is the package option label and the value is the option value (optional)
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being edited (if the current service is an addon service)
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function editService(
        $package,
        $service,
        array $vars = [],
        $parent_package = null,
        $parent_service = null
    ) {
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
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function cancelService($package, $service, $parent_package = null, $parent_service = null)
    {
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
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function renewService($package, $service, $parent_package = null, $parent_service = null)
    {
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
     *
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
     *
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
     *
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
     * Deletes the package on the remote server. Sets Input errors on failure,
     * preventing the package from being deleted.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function deletePackage($package)
    {
    }

    /**
     * Returns the rendered view of the manage module page
     *
     * @param mixed $module A stdClass object representing the module and its rows
     * @param array $vars An array of post data submitted to or on the manage
     *  module page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when
     *  viewing the manager module page
     */
    public function manageModule($module, array &$vars)
    {
        return '';
    }

    /**
     * Returns the rendered view of the add module row page
     *
     * @param array $vars An array of post data submitted to or on the add
     *  module row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when
     *  viewing the add module row page
     */
    public function manageAddRow(array &$vars)
    {
        return '';
    }

    /**
     * Returns the rendered view of the edit module row page
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of post data submitted to or on the edit
     *  module row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when
     *  viewing the edit module row page
     */
    public function manageEditRow($module_row, array &$vars)
    {
        return '';
    }

    /**
     * Adds the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being added.
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
        $meta = [];
        foreach ($vars as $key => $value) {
            $meta[] = [
                'key' => $key,
                'value' => $value,
                'encrypted' => 0
            ];
        }
        return $meta;
    }

    /**
     * Edits the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being updated.
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of module info to update
     * @return array A numerically indexed array of meta fields for the module row containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function editModuleRow($module_row, array &$vars)
    {
        $meta = [];
        foreach ($vars as $key => $value) {
            $meta[] = [
                'key' => $key,
                'value' => $value,
                'encrypted' => 0
            ];
        }
        return $meta;
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
     * Returns an array of available service delegation order methods. The module
     * will determine how each method is defined. For example, the method "first"
     * may be implemented such that it returns the module row with the least number
     * of services assigned to it.
     *
     * @return array An array of order methods in key/value pairs where the key
     *  is the type to be stored for the group and value is the name for that option
     * @see Module::selectModuleRow()
     */
    public function getGroupOrderOptions()
    {
    }

    /**
     * Determines which module row should be attempted when a service is provisioned
     * for the given group based upon the order method set for that group.
     *
     * @param int $module_group_id The ID of the module group from which to select a row
     * @return int The module row ID to attempt to add the service with
     * @see Module::getGroupOrderOptions()
     */
    public function selectModuleRow($module_group_id)
    {
        if (!isset($this->ModuleManager)) {
            Loader::loadModels($this, ['ModuleManager']);
        }

        // Fetch the module row group
        $group = $this->ModuleManager->getGroup($module_group_id);
        $row_id = 0;

        // The group must have module rows
        if (empty($group->rows)) {
            return $row_id;
        }

        // The schedule must be first available or round robin
        try {
            $schedule = $this->getPrioritySchedule($group->add_order);
        } catch (InvalidArgumentException $e) {
            return $row_id;
        }

        // Add the module rows to the schedule
        foreach ($group->rows as $row) {
            // Skip this row if the module group enforces strict limits and the row has reached its limit
            if ($group->force_limits && $this->limitReached($row)) {
                continue;
            }

            $schedule->insert($row);
        }

        // Fetch the module row
        try {
            $row = $schedule->extract();
            $row_id = $row->id;
        } catch (Exception $e) {
            // No valid row
        }

        return $row_id;
    }

    /**
     * Checks if a module row is at or past its limit
     *
     * @param stdClass $row The module row
     * @return boolean Whether the module row has reached its limit
     */
    private function limitReached($row)
    {
        $limit_field = $this->module_row_field_limit;
        $total_field = $this->module_row_field_total;
        $limit = property_exists($row->meta, $limit_field) ? trim($row->meta->{$limit_field} ?? '') : null;
        $total = trim(property_exists($row->meta, $total_field) ? $row->meta->{$total_field} : 0);
        return ($limit !== null && $limit !== '' && $limit <= $total);
    }

    /**
     * Retrieves the priority schedule to use for fetching a module row
     *
     * @param string $type The module group order type ('first' or 'roundrobin')
     * @return ScheduleInterface The selected schedule
     * @throws InvalidArgumentException When an invalid $type is given
     */
    private function getPrioritySchedule($type)
    {
        // Fetch the schedule to use
        $schedule = null;
        switch ($type) {
            case 'first':
                $schedule = new FirstAvailable();
                break;
            case 'roundrobin':
                $schedule = new RoundRobin();
                break;
            default:
                throw new InvalidArgumentException(
                    sprintf('%s is not a valid priority schedule', $type)
                );
                break;
        }

        // Set the callback for the schedule
        $this->attachPriorityScheduleCallback($schedule);
        return $schedule;
    }

    /**
     * Sets a callback function to the schedule for comparing module rows
     *
     * @param ScheduleInterface $schedule The selected schedule to use for fetching a module row
     */
    protected function attachPriorityScheduleCallback(ScheduleInterface $schedule)
    {
        $row_value = function ($row, $field) {
            return trim(property_exists($row->meta, $field) ? $row->meta->{$field} : 0);
        };

        switch (true) {
            case $schedule instanceof FirstAvailable:
                $schedule->setCallback(function ($row) use ($row_value) {
                    $limit = $row_value($row, $this->module_row_field_limit);
                    $total = $row_value($row, $this->module_row_field_total);

                    // A blank limit is always available
                    return ($limit === '' || (int)$total < (int)$limit);
                });
                break;
            case $schedule instanceof RoundRobin:
                $schedule->setCallback(function ($row1, $row2) use ($row_value) {
                    $row1_total = (int)$row_value($row1, $this->module_row_field_total);
                    $row1_limit = (int)$row_value($row1, $this->module_row_field_limit);
                    $row2_total = (int)$row_value($row2, $this->module_row_field_total);
                    $row2_limit = (int)$row_value($row2, $this->module_row_field_limit);

                    // Check if either row has reached its limit
                    $row1_limit_reached = ($row1_total >= $row1_limit && $row1_limit !== '');
                    $row2_limit_reached = ($row2_total >= $row2_limit && $row2_limit !== '');

                    if ($row1_limit_reached || $row2_limit_reached) {
                        if ($row1_limit_reached && $row2_limit_reached) {
                            // If both have reached their limit, compare the difference between their limit and total
                            $row1_total = $row1_total - $row1_limit;
                            $row2_total = $row2_total - $row2_limit;
                        } else {
                            // Use the row whose limit has not been reached
                            return ($row1_limit_reached ? -1 : 1);
                        }
                    }

                    if ($row1_total === $row2_total) {
                        return 0;
                    }

                    return ($row1_total < $row2_total ? 1 : -1);
                });
                break;
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
        return new ModuleFields();
    }

    /**
     * Returns an array of key values for fields stored for a module, package,
     * and service under this module, used to substitute those keys with their
     * actual module, package, or service meta values in related emails.
     *
     * @return array A multi-dimensional array of key/value pairs where each key
     *  is one of 'module', 'package', or 'service' and each value is a numerically
     *  indexed array of key values that match meta fields under that category.
     * @see Modules::addModuleRow()
     * @see Modules::editModuleRow()
     * @see Modules::addPackage()
     * @see Modules::editPackage()
     * @see Modules::addService()
     * @see Modules::editService()
     */
    public function getEmailTags()
    {
        if (isset($this->config->email_tags)) {
            return (array)$this->config->email_tags;
        }
        return [];
    }

    /**
     * Returns the email template of this module
     *
     * @return array A multi-dimensional array of key/value pairs where each key
     *  is the language code and each value is a key/value array containing the 'text'
     *  and 'html' template.
     */
    public function getEmailTemplate()
    {
        $module_name = Loader::fromCamelCase(get_class($this));

        if (file_exists(dirname(__FILE__) . DS . $module_name . DS . 'config' . DS . $module_name . '.php')) {
            Configure::load($module_name, dirname(__FILE__) . DS . $module_name . DS . 'config' . DS);
            $template = Configure::get(Loader::toCamelCase($module_name) . '.email_templates');

            return $template;
        }

        return [];
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
        return new ModuleFields();
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
        return new ModuleFields();
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
        return new ModuleFields();
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
     * Returns all tabs to display to an admin when managing a service
     *
     * @param stdClass $service A stdClass object representing the service
     * @return array An array of tabs in the format of method => title.
     *  Example: ['methodName' => "Title", 'methodName2' => "Title2"]
     */
    public function getAdminServiceTabs($service)
    {
        Loader::loadModels($this, ['Packages']);
        $package = $this->Packages->get($service->package_id ?? $service->package->id);
        if ($package) {
            return $this->getAdminTabs($package);
        }

        return [];
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
        $package = $this->Packages->get($service->package_id ?? $service->package->id);
        if ($package) {
            return $this->getClientTabs($package);
        }

        return [];
    }

    /**
     * Returns all tabs to display to an admin when managing a service whose
     * package uses this module. Maintained for backwards compatibility,
     * use getAdminServiceTabs() instead.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title.
     *  Example: array('methodName' => "Title", 'methodName2' => "Title2")
     * @see Module::getAdminServiceTabs()
     */
    public function getAdminTabs($package)
    {
        return [];
    }

    /**
     * Returns all tabs to display to a client when managing a service whose
     * package uses this module. Maintained for backwards compatibility,
     * use getClientServiceTabs() instead.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title, or method => array where array contains:
     *
     *  - name (required) The name of the link
     *  - icon (optional) use to display a custom icon
     *  - href (optional) use to link to a different URL
     *      Example:
     *      array('methodName' => "Title", 'methodName2' => "Title2")
     *      array('methodName' => array('name' => "Title", 'icon' => "icon"))
     * @see Module::getClientServiceTabs()
     */
    public function getClientTabs($package)
    {
        return [];
    }


    /**
     * Returns HTML to insert into the service management page in the client interface
     *
     * @param stdClass $package A stdClass object representing the service's package
     * @param stdClass $service A stdClass object representing the service being managed
     * @return string HTML content to insert
     */
    public function getClientManagementContent($package, $service)
    {
        return '';
    }

    /**
     * Return all validation errors encountered
     *
     * @return mixed Boolean false if no errors encountered, an array of errors otherwise
     */
    public function errors()
    {
        if (isset($this->Input) && is_object($this->Input) && $this->Input instanceof Input) {
            return $this->Input->errors();
        }
    }

    /**
     * Sets a message
     *
     * @param string $type The type of message ('success', 'error", or 'notice')
     * @param string $message The message text to display
     */
    final protected function setMessage($type, $message)
    {
        if (!array_key_exists($type, $this->messages)) {
            $this->messages[$type] = ['message' => []];
        }

        $this->messages[$type]['message'][] = $message;
    }

    /**
     * Retrieves a set of messages set by the module
     *
     * @return array An array of messages
     */
    final public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Process a request over HTTP using the supplied method type, url and parameters.
     *
     * @param string $method The method type (e.g. GET, POST)
     * @param string $url The URL to post to
     * @param mixed An array of parameters or a URL encoded list of key/value pairs
     * @param string The output result from executing the request
     */
    protected function httpRequest($method, $url = null, $params = null)
    {
        if (!isset($this->Http)) {
            Loader::loadComponents($this, ['Net']);
            $this->Http = $this->Net->create('Http');
        }

        if (is_array($params)) {
            $params = http_build_query($params);
        }

        return $this->Http->request($method, $url, $params);
    }

    /**
     * Attempts to log the given info to the module log.
     *
     * @param string $url The URL contacted for this request
     * @param string $data A string of module data sent along with the request (optional)
     * @param string $direction The direction of the log entry (input or output, default input)
     * @param bool $success True if the request was successful, false otherwise
     * @return string Returns the 8-character group identifier, used to link log entries together
     * @throws Exception Thrown if $data was invalid and could not be added to the log
     */
    protected function log($url, $data = null, $direction = 'input', $success = false)
    {
        if (!isset($this->Logs)) {
            Loader::loadModels($this, ['Logs']);
        }

        // Create a random 8-character group identifier
        if ($this->log_group == null) {
            $this->log_group = substr(md5(mt_rand()), mt_rand(0, 23), 8);
        }

        $requestor = $this->getFromContainer('requestor');
        $log = [
            'staff_id' => $requestor->staff_id,
            'module_id' => $this->module->id,
            'direction' => $direction,
            'url' => $url,
            'data' => $data,
            'status' => ($success ? 'success' : 'error'),
            'group' => $this->log_group
        ];
        $this->Logs->addModule($log);

        if (($error = $this->Logs->errors())) {
            throw new Exception(serialize($error));
        }

        return $this->log_group;
    }

    /**
     * Converts numerically indexed service field arrays into an object with member variables
     *
     * @param array $fields A numerically indexed array of stdClass objects containing
     *  key and value member variables, or an array containing 'key' and 'value' indexes
     * @return stdClass A stdClass objects with member variables
     */
    protected function serviceFieldsToObject(array $fields)
    {
        $data = new stdClass();
        foreach ($fields as $field) {
            if (is_array($field)) {
                $data->{$field['key']} = $field['value'];
            } else {
                $data->{$field->key} = $field->value;
            }
        }

        return $data;
    }

    /**
     * Converts an array to a ModuleFields object
     *
     * @param array An array of key/value pairs where each key is the field name and each value is array consisting of:
     *
     *  - label The field label
     *  - type The field type(text, textarea, select, checkbox, radio)
     *  - options A key/value array where each key is the option value and each value
     *      is the option name, or a string to set as the default value for hidden and text inputs
     *  - attributes A key/value array
     * @param ModuleFields $fields An existing ModuleFields object to append fields to,
     *  null to create create a new object
     * @param stdClass $vars A stdClass object of input key/value pairs
     * @return ModuleFields A ModuleFields object containing the fields
     */
    protected function arrayToModuleFields($arr, ModuleFields $fields = null, $vars = null)
    {
        if ($fields == null) {
            $fields = new ModuleFields();
        }

        foreach ($arr as $name => $field) {
            $label = isset($field['label']) ? $field['label'] : null;
            $type = isset($field['type']) ? $field['type'] : null;
            $options = isset($field['options']) ? $field['options'] : null;
            $attributes = isset($field['attributes']) ? $field['attributes'] : [];

            $field_id = isset($attributes['id']) ? $attributes['id'] : $name . '_id';

            $field_label = null;
            if ($type !== 'hidden') {
                $field_label = $fields->label($label, $field_id);
            }

            $attributes['id'] = $field_id;

            switch ($type) {
                default:
                    $value = $options;
                    $type = 'field' . ucfirst($type);
                    $field_label->attach(
                        $fields->{$type}($name, isset($vars->{$name}) ? $vars->{$name} : $value, $attributes)
                    );
                    break;
                case 'hidden':
                    $value = $options;
                    $fields->setField(
                        $fields->fieldHidden($name, isset($vars->{$name}) ? $vars->{$name} : $value, $attributes)
                    );
                    break;
                case 'select':
                    $field_label->attach(
                        $fields->fieldSelect(
                            $name,
                            $options,
                            isset($vars->{$name}) ? $vars->{$name} : null,
                            $attributes
                        )
                    );
                    break;
                case 'checkbox':
                    // No break
                case 'radio':
                    $i = 0;
                    foreach ($options as $key => $value) {
                        $option_id = $field_id . '_' . $i++;
                        $option_label = $fields->label($value, $option_id);

                        $checked = false;
                        if (isset($vars->{$name})) {
                            if (is_array($vars->{$name})) {
                                $checked = in_array($key, $vars->{$name});
                            } else {
                                $checked = $key == $vars->{$name};
                            }
                        }

                        if ($type == 'checkbox') {
                            $field_label->attach(
                                $fields->fieldCheckbox($name, $key, $checked, ['id' => $option_id], $option_label)
                            );
                        } else {
                            $field_label->attach(
                                $fields->fieldRadio($name, $key, $checked, ['id' => $option_id], $option_label)
                            );
                        }
                    }
                    break;
            }

            if ($field_label) {
                $fields->setField($field_label);
            }
        }

        return $fields;
    }

    /**
     * Loads a given config file
     *
     * @param string $file The path to the JSON file to load
     */
    protected function loadConfig($file)
    {
        if (file_exists($file)) {
            $this->config = json_decode(file_get_contents($file));
        }
    }

    /**
     * Translate the given str, or passthrough if no translation et
     *
     * @param string $str The string to translate
     * @return string The translated string
     */
    private function translate($str)
    {
        $pass_through = Configure::get('Language.allow_pass_through');
        Configure::set('Language.allow_pass_through', true);
        $str = Language::_($str, true);
        Configure::set('Language.allow_pass_through', $pass_through);

        return $str;
    }

    /**
     * Fetches an array containing the error response to be set using Input::setErrors()
     *
     * @param string $type The type of error to fetch. Values include:
     *
     *  - invalid An invalid API response received
     *  - unsupported The action is not supported by the module
     *  - general A general error occurred
     * @return mixed An array containing the error to populate using Input::setErrors(),
     *  false if the type does not exist
     */
    protected function getCommonError($type)
    {
        Language::loadLang('module');

        $message = '';
        switch ($type) {
            case 'invalid':
                $message = Language::_('Module.!error.invalid', true);
                break;
            case 'unsupported':
                $message = Language::_('Module.!error.unsupported', true);
                break;
            case 'general':
                $message = Language::_('Module.!error.general', true);
                break;
            default:
                return false;
        }

        return [
            'module' => [
                $type => $message
            ]
        ];
    }
}
