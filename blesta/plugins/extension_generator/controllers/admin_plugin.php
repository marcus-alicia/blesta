<?php
/**
 * Extension Generator admin plugin controller
 *
 * @package blesta
 * @subpackage blesta.plugins.extension_generator
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminPlugin extends ExtensionGeneratorController
{
    /**
     * Setup
     */
    public function preAction()
    {
        parent::preAction();

        // Ensure extension exists
        if (!isset($this->get[0])
            || !($extension = $this->ExtensionGeneratorExtensions->get($this->get[0]))
            || $extension->company_id != $this->company_id
        ) {
            $this->redirect($this->base_uri . 'plugin/extension_generator/admin_main/');
        }

        $this->extension = $extension;
        $this->structure->set('page_title', Language::_('AdminPlugin.index.page_title', true, $extension->name));
    }

    /**
     * Returns the view to be rendered when configuring the basic settings for a plugin
     */
    public function basic()
    {
        // Attempt to upload logo if submitted
        $errors = $this->uploadLogo();

        if (!$errors) {
            // Perform edit and redirect or set errors and repopulate vars
            $vars = $this->processStep('plugin/basic', $this->extension);
        } else {
            $vars = $this->post;

            $this->setMessage('error', $errors, false, null, false);
        }

        // Set the view to render for all actions under this controller
        $this->set('form_type', $this->extension->form_type);
        $this->set('vars', $vars);

        // Set the node progress bar
        $nodes = $this->getNodes($this->extension);
        $page_step = array_search('plugin/basic', array_keys($nodes));
        $this->set(
            'progress_bar',
            $this->partial(
                'partial_progress_bar',
                ['nodes' => $nodes, 'page_step' => $page_step, 'extension' => $this->extension]
            )
        );
    }

    /**
     * Returns the view to be rendered when configuring the database tables for a plugin
     */
    public function database()
    {
        // Reset the indexes for the tables array and column sub-arrays
        if (isset($this->post['tables'])) {
            array_values($this->post['tables']);
            foreach ($this->post['tables'] as &$table) {
                // Reset the indexes for the column sub-arrays
                $table['columns'] = array_values($table['columns']);

                // Set a class name for the table's model
                $table['class_name'] = str_replace(
                        ' ',
                        '',
                        ucwords(str_replace('_', ' ', $table['name']))
                    );

                // Set unset checkboxes
                foreach ($table['columns'] as &$column) {
                    if (!isset($column['nullable'])) {
                        $column['nullable'] = 'false';
                    }

                    if (!isset($column['primary'])) {
                        $column['primary'] = 'false';
                    }

                    // Set the upper case equivilent of the column name
                    $column['uc_name'] = str_replace(' ', '', ucwords(str_replace('_', ' ', $column['name'])));

                    // Set values for enum columns
                    if ($column['type'] == 'ENUM') {
                        $values = explode(',', $column['length']);
                        $column['values'] = array_map(
                                function($value) { return ['value' => trim($value, "'")]; },
                                $values
                            );
                    }
                }
            }
        } elseif (!empty($this->post)) {
            $this->post['tables'] = [];
        }

        // Perform edit and redirect or set errors and repopulate vars
        $vars = $this->processStep('plugin/database', $this->extension);

        // Set the view to render for all actions under this controller
        $this->set('column_types', $this->getColumnTypes());
        $this->set('vars', $vars);

        // Set the node progress bar
        $nodes = $this->getNodes($this->extension);
        $page_step = array_search('plugin/database', array_keys($nodes));
        $this->set(
            'progress_bar',
            $this->partial(
                'partial_progress_bar',
                ['nodes' => $nodes, 'page_step' => $page_step, 'extension' => $this->extension]
            )
        );
    }

    /**
     * Returns the view to be rendered when configuring the core integrations for a plugin
     */
    public function integrations()
    {
        // Set empty array inputs
        if (!empty($this->post)) {
            $array_fields = ['actions', 'events', 'cards'];
            foreach ($array_fields as $array_field) {
                if (!isset($this->post[$array_field])) {
                    // Set empty array inputs
                    $this->post[$array_field] = [];
                }
            }

            $this->post['controllers'] = [];
        }

        // Format controller and action fields
        if (!empty($this->post['actions']['controller'])) {
            foreach ($this->post['actions']['controller'] as $index => $controller) {
                $action = $this->post['actions']['action'][$index];
                $this->post['actions']['controller_action'][$index] = $controller
                    . ($action == 'index' ?  '' : '_' . $action);
                $this->post['actions']['controller_class'][$index] = str_replace(
                        ' ',
                        '',
                        ucwords(str_replace('_', ' ', $controller))
                    );

                // Create a list of controllers to implement
                if (!isset($this->post['controllers'][$controller])) {
                    $this->post['controllers'][$controller] = [
                            'class_name' => $this->post['actions']['controller_class'][$index],
                            'snake_case_name' => $controller,
                            'actions' => []
                        ];
                }

                // Add this action to the list for its controller
                $this->post['controllers'][$controller]['actions'][$action] = [
                    'controller' => $controller,
                    'action' => $action,
                    'name' => $this->post['actions']['name'][$index],
                ];
            }
        }

        // Perform edit and redirect or set errors and repopulate vars
        $vars = $this->processStep('plugin/integrations', $this->extension);

        // Set the view to render for all actions under this controller
        $this->set('action_locations', $this->getActionLocations());
        $this->set('card_levels', $this->getCardLevels());
        $this->set('vars', $vars);

        // Set the node progress bar
        $nodes = $this->getNodes($this->extension);
        $page_step = array_search('plugin/integrations', array_keys($nodes));
        $this->set(
            'progress_bar',
            $this->partial(
                'partial_progress_bar',
                ['nodes' => $nodes, 'page_step' => $page_step, 'extension' => $this->extension]
            )
        );
    }

    /**
     * Returns the view to be rendered when configuring the additional features for a plugin
     */
    public function features()
    {
        // Set empty array inputs
        if (!empty($this->post)) {
            $array_fields = ['service_tabs', 'cron_tasks'];
            foreach ($array_fields as $array_field) {
                if (!isset($this->post[$array_field])) {
                    // Set empty array inputs
                    $this->post[$array_field] = [];
                }
            }
        }

        // Format service tab fields
        if (!empty($this->post['service_tabs']['method_name'])) {
            foreach ($this->post['service_tabs']['method_name'] as $index => $method) {
                $this->post['service_tabs']['snake_case_name'][$index] = strtolower(
                        preg_replace('/([A-Z])/', '_$1', $method)
                    );
            }
        }

        // Perform edit and redirect or set errors and repopulate vars
        $vars = $this->processStep('plugin/features', $this->extension);

        // Set the view to render for all actions under this controller
        $this->set('tab_levels', $this->getTabLevels());
        $this->set('task_types', $this->getTaskTypes());
        $this->set('optional_functions', $this->getOptionalFunctions());
        $this->set('vars', $vars);

        // Set the node progress bar
        $nodes = $this->getNodes($this->extension);
        $page_step = array_search('plugin/features', array_keys($nodes));
        $this->set(
            'progress_bar',
            $this->partial(
                'partial_progress_bar',
                ['nodes' => $nodes, 'page_step' => $page_step, 'extension' => $this->extension]
            )
        );
    }

    /**
     * Gets a list of optional functions and their settings
     *
     * @return A list of optional functions and their settings
     */
    protected function getOptionalFunctions()
    {
        $functions = [
            'upgrade' => ['enabled' => 'true'],
            'getPermissions' => ['enabled' => 'false'],
            'getPermissionGroups' => ['enabled' => 'false']
        ];

        foreach ($functions as $function => &$settings) {
            $settings['tooltip'] = Language::_(
                'AdminPlugin.getoptionalfunctions.tooltip_' . $function,
                true
            );
        }

        return $functions;
    }

    /**
     * Gets a list of database column types
     *
     * @return A list of database column types
     */
    private function getColumnTypes()
    {
        return [
            'INT' => 'INT',
            'TINYINT' => 'TINYINT',
            'VARCHAR' => 'VARCHAR',
            'TEXT' => 'TEXT',
            'DATETIME' => 'DATETIME',
            'ENUM' => 'ENUM',
        ];
    }

    /**
     * Gets a list of action locations and their language
     *
     * @return A list of action locations and their language
     */
    private function getActionLocations()
    {
        return [
            'nav_primary_staff' => Language::_('AdminPlugin.getactionlocations.nav_primary_staff', true),
            'nav_secondary_staff' => Language::_('AdminPlugin.getactionlocations.nav_secondary_staff', true),
            'action_staff_client' => Language::_('AdminPlugin.getactionlocations.action_staff_client', true),
            'nav_primary_client' => Language::_('AdminPlugin.getactionlocations.nav_primary_client', true),
            'widget_client_home' => Language::_('AdminPlugin.getactionlocations.widget_client_home', true),
            'widget_staff_home' => Language::_('AdminPlugin.getactionlocations.widget_staff_home', true),
            'widget_staff_client' => Language::_('AdminPlugin.getactionlocations.widget_staff_client', true),
            'widget_staff_billing' => Language::_('AdminPlugin.getactionlocations.widget_staff_billing', true),
        ];
    }

    /**
     * Gets a list of card levels and their language
     *
     * @return A list of card levels and their language
     */
    private function getCardLevels()
    {
        return [
            'client' => Language::_('AdminPlugin.getcardlevels.client', true),
            'staff' => Language::_('AdminPlugin.getcardlevels.staff', true),
        ];
    }
}
