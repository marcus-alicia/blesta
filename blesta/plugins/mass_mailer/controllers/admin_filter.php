<?php
/**
 * Mass Mailer Admin Filter controller
 * Step 1 of composing a mass email
 *
 * @package blesta
 * @subpackage blesta.plugins.mass_mailer.controllers
 * @copyright Copyright (c) 2016, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminFilter extends MassMailerController
{
    /**
     * @var array Accepted client filtering options
     */
    private $client_fields = [
        'client_group_ids', 'client_statuses', 'languages', 'contact_types',
        'client_start_date', 'client_end_date', 'receive_email_marketing'
    ];
    /**
     * @var array Accepted service filtering options
     */
    private $service_fields = [
        'filter_services', 'service_renew_date', 'service_statuses',
        'service_parent_type', 'packages', 'include_all_services', 'module_id',
        'module_rows'
    ];

    /**
     * Setup page
     */
    public function preAction()
    {
        parent::preAction();

        $this->structure->set('page_title', Language::_('AdminFilter.index.page_title', true));
    }

    /**
     * Mass Mailer options
     */
    public function index()
    {
        $dates = ['client_start_date', 'client_end_date', 'service_renew_date'];

        if (!empty($this->post)) {
            // Only set the expected fields
            $fields = array_merge($this->client_fields, $this->service_fields);
            $data = array_intersect_key($this->post, array_flip($fields));

            // Default checkboxes
            $checkboxes = ['filter_services', 'include_all_services'];
            foreach ($checkboxes as $checkbox) {
                $data[$checkbox] = (
                    isset($data[$checkbox])
                    ? $data[$checkbox]
                    : 'false'
                );
            }

            // Set date fields to include time
            foreach ($dates as $date) {
                if (array_key_exists($date, $data) && strlen(trim($data[$date])) > 0) {
                    $data[$date] = trim($data[$date]) . (
                        $date === 'client_start_date'
                        ? ' 00:00:00'
                        : ' 23:59:59'
                    );
                }
            }

            // Store the filters in the session
            $this->write('filters', $data);

            // Queue an export if selected
            if (array_key_exists('export', $this->post)) {
                $this->addJob('export');
            }

            // Redirect to compose the email
            $this->redirect($this->base_uri . 'plugin/mass_mailer/admin_compose/');
        }

        // Set the filter information from the session if available
        $vars = (object)[];
        $set_defaults = true;
        $session = $this->read();
        if (is_array($session) && array_key_exists('filters', $session)) {
            $vars = (object)$session['filters'];

            // Remove time from dates
            foreach ($dates as $date) {
                if (property_exists($vars, $date)) {
                    $vars->{$date} = trim(substr($vars->{$date}, 0, 10));
                }
            }

            $set_defaults = false;
        }

        $this->set('vars', $this->setFilters($vars, $set_defaults));
        $this->setDatePicker();
    }

    /**
     * AJAX Retrieves a set of module rows from the given module ID
     *
     * @param int $module_id The ID of the module whose rows to fetch
     * @return mixed An array of a module ID is given, otherwise outputs as JSON, assuming AJAX
     */
    public function moduleRows($module_id = null)
    {
        // Return module rows if a module ID is given
        $return = ($module_id !== null);

        // Must be AJAX
        if (!$return && !$this->isAjax()) {
            header($this->server_protocol . '401 Unauthorized');
            exit();
        }

        $this->uses(['ModuleManager']);
        $this->helpers(['DataStructure']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Determine the module ID from GET
        if (!$return) {
            $module_id = '';
            if (isset($this->get[0])) {
                $module_id = (int)$this->get[0];
            }
        }

        // No module, no module rows
        if (!($module = $this->ModuleManager->initModule($module_id, $this->company_id))) {
            // Return the rows
            if ($return) {
                return [];
            }

            $this->outputAsJson([]);
            return false;
        }

        // Fetch the module row label for each row
        $rows = $this->ArrayHelper->numericToKey(
            (array)$this->ModuleManager->getRows($module_id),
            'id',
            'meta'
        );
        $row_key = $module->moduleRowMetaKey();
        foreach ($rows as &$row) {
            $row = $row->{$row_key};
        }

        // Return the rows
        if ($return) {
            return $rows;
        }

        $this->outputAsJson($rows);
        return false;
    }

    /**
     * Sets all filtering options to the view
     *
     * @param stdClass $vars An stdClass object representing input fields
     * @param bool $set_defaults True to update $vars with default values, or false otherwise
     * @return stdClass $vars An stdClass object representing input fields with default values selected
     */
    protected function setFilters($vars, $set_defaults = true)
    {
        return (object)array_merge(
            (array)$this->setClientFilters($vars, $set_defaults),
            (array)$this->setServiceFilters($vars, $set_defaults)
        );
    }

    /**
     * Sets client filtering options to the view
     *
     * @param stdClass $vars An stdClass object representing input fields
     * @param bool $set_defaults True to update $vars with default values, or false otherwise
     * @return stdClass $vars An stdClass object representing input fields with default values selected
     */
    protected function setClientFilters($vars, $set_defaults = true)
    {
        $this->uses(['ClientGroups', 'Clients', 'Contacts', 'Languages']);

        // Fetch filtering option data
        $client_statuses = $this->Clients->getStatusTypes();

        $client_groups = $this->Form->collapseObjectArray(
            $this->ClientGroups->getAll($this->company_id),
            'name',
            'id'
        );

        $languages = $this->Form->collapseObjectArray(
            $this->Languages->getAll($this->company_id),
            'name',
            'code'
        );

        $contact_types = (
            $this->Contacts->getContactTypes()
            + $this->Form->collapseObjectArray(
                $this->Contacts->getTypes($this->company_id),
                'real_name',
                'id'
            )
        );
        unset($contact_types['other']);

        // Set default values
        if ($set_defaults) {
            $vars->client_statuses = ['active'];
            $vars->client_group_ids = array_keys($client_groups);
            $vars->languages = array_keys($languages);
            $vars->contact_types = ['primary'];
        }

        $this->set(
            compact('client_groups', 'client_statuses', 'contact_types', 'languages')
        );

        return $vars;
    }

    /**
     * Sets service filtering options to the view
     *
     * @param stdClass $vars An stdClass object representing input fields
     * @param bool $set_defaults True to update $vars with default values, or false otherwise
     * @return stdClass $vars An stdClass object representing input fields with default values selected
     */
    protected function setServiceFilters($vars, $set_defaults = true)
    {
        $this->uses(['ModuleManager', 'Packages', 'Services']);

        // Set service statuses
        $service_statuses = $this->Services->getStatusTypes();

        // Set package groups
        $package_groups = (
            ['' => Language::_('AdminFilter.text.all', true)]
            + $this->Form->collapseObjectArray(
                $this->Packages->getAllGroups($this->company_id),
                'name',
                'id'
            )
        );

        // Set packages
        $packages = $this->Packages->getAll($this->company_id);
        $package_attributes = $this->getPackageAttributes($packages);

        // Set modules
        $modules = (
            ['' => Language::_('AppController.select.please', true)]
            + $this->Form->collapseObjectArray(
                $this->ModuleManager->getAll($this->company_id),
                'name',
                'id'
            )
        );

        // Set default values
        if ($set_defaults) {
            $vars->package_group = '';
            $vars->service_statuses = ['active'];
        } else {
            // Only format the selected packages if any are given
            if (isset($vars->packages) && is_array($vars->packages)) {
                $vars->packages = $this->getSelectedPackages($vars->packages, $packages);
            }
        }

        // Set the module rows if a module ID is given
        $module_rows = [];
        if (!empty($vars->module_id)) {
            $module_rows = $this->moduleRows($vars->module_id);
        }

        // After packages have been modified (see AdminFilter::getSelectedPackages), format them
        $packages = $this->Form->collapseObjectArray($packages, 'name', 'id');

        $this->set(
            compact('service_statuses', 'package_groups', 'package_attributes', 'packages', 'modules', 'module_rows')
        );

        return $vars;
    }

    /**
     * Retrieves the package attributes by package groups
     *
     * @param array $packages An array of packages
     * @return array An array of package attributes keyed by package ID
     */
    private function getPackageAttributes($packages)
    {
        $package_attributes = [];

        // Build the package option attributes
        foreach ($packages as $package) {
            $groups = $this->Packages->getAllGroups($this->company_id, $package->id);

            $group_ids = [];
            foreach ($groups as $group) {
                $group_ids[] = 'group_' . $group->id;
            }

            if (!empty($group_ids)) {
                $package_attributes[$package->id] = ['class' => implode(' ', $group_ids)];
            }
        }

        return $package_attributes;
    }

    /**
     * Formats selected packages and removes them from the given $packages
     *
     * @param array $selected_packages An array of selected packages
     * @param array $packages An array of stdClass objects representing each package
     * @return array A formatted array of selected packages
     */
    private function getSelectedPackages($selected_packages, &$packages)
    {
        $assigned_packages = [];

        // Set the selected assigned packages
        if (!empty($selected_packages)) {
            $temp_packages = array_flip($selected_packages);

            // Find any assigned packages from the list of packages, and set them
            for ($i = 0, $num_packages = count($packages); $i < $num_packages; $i++) {
                if (isset($temp_packages[$packages[$i]->id])) {
                    // Set an assigned package
                    $assigned_packages[$packages[$i]->id] = $packages[$i]->name;
                    // Remove it from available packages
                    unset($packages[$i]);
                }
            }
        }

        return $assigned_packages;
    }
}
