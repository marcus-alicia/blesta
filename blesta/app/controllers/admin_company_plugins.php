<?php

/**
 * Admin Company Plugin Settings
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminCompanyPlugins extends AppController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        $this->uses(['PluginManager', 'Navigation']);

        Language::loadLang('admin_company_plugins');

        // Set the left nav for all settings pages to settings_leftnav
        $this->set(
            'left_nav',
            $this->partial('settings_leftnav', ['nav' => $this->Navigation->getCompany($this->base_uri)])
        );
    }

    /**
     * Redirect to installed plugins
     */
    public function index()
    {
        $this->redirect($this->base_uri . 'settings/company/plugins/installed/');
    }

    /**
     * Plugins Installed page
     */
    public function installed()
    {
        $this->setTabs();
        $this->set('show_left_nav', !$this->isAjax());
        $this->set('plugins', $this->PluginManager->getAll($this->company_id));
        return $this->renderAjaxWidgetIfAsync();
    }

    /**
     * Plugins Available page
     */
    public function available()
    {
        $this->setTabs();
        $this->set('show_left_nav', !$this->isAjax());
        $this->set('plugins', $this->PluginManager->getAvailable($this->company_id));
        return $this->renderAjaxWidgetIfAsync();
    }

    /**
     * Sets the available/installed tabs
     */
    private function setTabs()
    {
        $this->set(
            'link_tabs',
            [
                [
                    'name' => Language::_('AdminCompanyPlugins.!tab.installed', true),
                    'uri' => 'installed'
                ],
                [
                    'name' => Language::_('AdminCompanyPlugins.!tab.available', true),
                    'uri' => 'available'
                ]
            ]
        );
    }

    /**
     * Manage a plugin
     */
    public function manage()
    {
        // Fetch the plugin to manage
        if (!isset($this->get[0]) || !($plugin = $this->PluginManager->get($this->get[0]))) {
            $this->redirect($this->base_uri . 'settings/company/plugins/installed/');
        }

        $controller = 'admin_manage_plugin';
        $action = 'index';

        // Allow the URL to override the default controller and action to invoke
        if (isset($this->get['controller'])) {
            $controller = $this->get['controller'];
        }
        if (isset($this->get['action'])) {
            $action = $this->get['action'];
        }

        $controller = Loader::fromCamelCase($controller);
        $controller_name = Loader::toCamelCase($controller);
        $action = Loader::toCamelCase($action);

        $manage_controller = PLUGINDIR . $plugin->dir . DS . 'controllers' . DS . $controller . '.php';
        if (!file_exists($manage_controller)) {
            $this->flashMessage('error', Language::_('AdminCompanyPlugins.!error.setting_controller_invalid', true));
            $this->redirect($this->base_uri . 'settings/company/plugins/installed/');
        }

        // In order to manage a plugin we need to execute a controller within a controller,
        // so we must initialize and prime the embedded controller with data that
        // would normally be made available with automated bootstrapping.
        // Therefore, the management section of a plugin should be as simple as possible.
        // If there are complex actions that need to be performed it would be best to create
        // a plugin with the proper actions to create new pages from which the plugin
        // could be accessed directly, rather than filtered through AdminCompanyPlugins::manage().
        // Also, consider redirecting from a ManagePlugin controller to a URL controlled by the plugin itself.
        // Load the controller
        Loader::load($manage_controller);

        // Initialize and prime the controller
        $ctrl = new $controller_name($controller, $action, $this->is_cli);
        $ctrl->uri = $this->uri;
        $ctrl->uri_str = $this->uri_str;
        $ctrl->get = $this->get;
        $ctrl->post = $this->post;
        $ctrl->files = $this->files;
        $ctrl->controller = $controller;
        $ctrl->action = $action;
        $ctrl->is_cli = $this->is_cli;
        $ctrl->base_uri = $this->base_uri;
        $ctrl->parent = $this;
        $ctrl->plugin = $plugin->dir;

        // Execute the action and set the details in the view
        $this->set('content', $ctrl->$action());
    }

    /**
     * Manage settings for the plugin
     */
    public function settings()
    {
        // Fetch the plugin to update settings for
        if (!isset($this->get[0]) || !($plugin = $this->PluginManager->get($this->get[0]))) {
            $this->redirect($this->base_uri . 'settings/company/plugins/installed/');
        }

        // Determine what tab we're on
        $tabs = ['automation', 'actions', 'events'];
        $tab = $tabs[0];
        if (isset($this->get[1]) && in_array($this->get[1], $tabs)) {
            $tab = strtolower($this->get[1]);
        }

        $this->renderSettingTab($tab, $plugin);

        $this->set('plugin', $plugin);
        $this->set(
            'tabs',
            [
                [
                    'name' => Language::_('AdminCompanyPlugins.settings.tab_automation', true),
                    'current' => ($tab === 'automation'),
                    'attributes' => [
                        'href' => $this->base_uri . 'settings/company/plugins/settings/' . $plugin->id . '/automation/'
                    ]
                ],
                [
                    'name' => Language::_('AdminCompanyPlugins.settings.tab_actions', true),
                    'current' => ($tab === 'actions'),
                    'attributes' => [
                        'href' => $this->base_uri . 'settings/company/plugins/settings/' . $plugin->id . '/actions/'
                    ]
                ],
                [
                    'name' => Language::_('AdminCompanyPlugins.settings.tab_events', true),
                    'current' => ($tab === 'events'),
                    'attributes' => [
                        'href' => $this->base_uri . 'settings/company/plugins/settings/' . $plugin->id . '/events/'
                    ]
                ]
            ]
        );
        $this->structure->set(
            'page_title',
            Language::_('AdminCompanyPlugins.settings.page_title', true, $this->Html->safe($plugin->name))
        );
    }

    /**
     * Processes and renders the given setting tab
     * @see AdminCompanyPlugins::settings
     *
     * @param string $name The name of the setting tab to process/render
     * @param stdClass $plugin An stdClass object representing the plugin
     */
    private function renderSettingTab($name, stdClass $plugin)
    {
        $data = [];
        switch ($name) {
            default:
                // no break, automation is the default tab
            case 'automation':
                $data = $this->renderSettingsAutomation($plugin);
                break;
            case 'actions':
                $data = $this->renderSettingsActions($plugin);
                break;
            case 'events':
                $data = $this->renderSettingsEvents($plugin);
                break;
        }

        $this->set('tab', $this->partial('admin_company_plugins_settings_' . $name, $data));
    }

    /**
     * Renders/Processes the automation settings for a plugin
     *
     * @param stdClass $plugin An stdClass object representing the plugin
     * @param array $data An array of input data
     */
    private function renderSettingsAutomation(stdClass $plugin)
    {
        $this->uses(['CronTasks']);

        // Get all the cron tasks
        $task_runs = $this->CronTasks->getAllTaskRun(false, 'plugin', $plugin->dir);
        foreach ($task_runs as $task) {
            // Cast the time to the proper local time format
            if (!empty($task->time)) {
                $task->time = $this->Date->cast($task->time, 'H:i:s');
            }
        }

        // Sort the tasks by name
        $task_names = [];
        foreach ($task_runs as $key => $task) {
            $task_names[$key] = $task->real_name;
        }
        array_multisort($task_names, SORT_NATURAL, $task_runs);

        // Process any input data
        $this->processSettingsAutomation($plugin, $task_runs);

        return ['tasks' => $task_runs, 'vars' => (object)$this->post];
    }

    /**
     * Processes any input submission data
     * May sets any error messages to the current view, or redirect on successful submission
     *
     * @param stdClass $plugin An stdClass object representing the plugin
     * @param array $tasks An array of automation tasks
     */
    private function processSettingsAutomation(stdClass $plugin, array $tasks)
    {
        // Only handle POST data
        if (empty($this->post)) {
            return;
        }

        // Ensure the 'enabled' setting is available even when all checkboxes are unchecked
        $this->post = array_merge(['enabled' => []], $this->post);

        // Start a transaction
        $this->CronTasks->begin();

        // Update the provided fields on each cron task
        foreach ($tasks as &$task) {
            foreach ($this->post as $key => $task_run_ids) {
                // Skip updating cron task settings that are not in this list
                if (!in_array($key, ['enabled'])) {
                    continue;
                }

                // Set the selected value for this field
                if (array_key_exists($task->task_run_id, (array) $task_run_ids)) {
                    $task->{$key} = $task_run_ids[$task->task_run_id];
                } elseif ($key == 'enabled') {
                    // The 'enabled' checkbox should be set to disabled if it's not given
                    $task->enabled = 0;
                }
            }

            $this->CronTasks->editTaskRun($task->task_run_id, (array) $task);

            // Keep the most recent errors by breaking out
            if ($this->CronTasks->errors()) {
                break;
            }
        }

        // Use only the most recent cron task's errors.
        // Note: there should never be errors
        if (($errors = $this->CronTasks->errors())) {
            // Error, rollback and reset vars
            $this->CronTasks->rollBack();

            $this->setMessage('error', $errors);
        } else {
            // Success, commit changes
            $this->CronTasks->commit();

            $this->flashMessage('message', Language::_('AdminCompanyPlugins.!success.automation_updated', true));
            $this->redirect($this->base_uri . 'settings/company/plugins/settings/' . $plugin->id . '/');
        }
    }

    /**
     * Renders/Processes the automation settings for a plugin
     *
     * @param stdClass $plugin An stdClass object representing the plugin
     * @param array $data An array of input data
     */
    private function renderSettingsActions(stdClass $plugin)
    {
        $this->uses(['Actions']);
        // Process any input data on the untranslated actions
        $this->processSettingsActions($plugin, $this->Actions->getAll(['plugin_id' => $plugin->id], false));

        // Get all the translated plugin actions
        $actions = $this->Actions->getAll(['plugin_id' => $plugin->id]);

        // Categorize the actions into groups by action
        $action_groups = [];
        $location_descriptions = $this->Actions->getLocationDescriptions();
        foreach ($actions as $action) {
            if (!isset($action_groups[$action->location])) {
                $action_groups[$action->location] = [];
            }

            $action_groups[$action->location][] = $this->formatSettingAction($action);
        }

        return [
            'actions' => $action_groups,
            'location_descriptions' => $location_descriptions,
            'vars' => (object)$this->post
        ];
    }

    /**
     * Formats the given plugin action by adding additional fields
     *
     * @param stdClass $action The plugin action to format
     * @return stdClass The formatted plugin action
     */
    private function formatSettingAction(stdClass $action)
    {
        // Set the URI
        $base_uri = $this->getSettingActionBaseUri(
            $action->location,
            null,
            (isset($action->options['base_uri']) ? $action->options['base_uri'] : null)
        );
        $action->full_uri = $base_uri . $action->url;

        return $action;
    }

    /**
     * Determines the base URI of the given plugin action
     *
     * @param string $location The action location
     * @param string|null $parent_base_uri The parent action's base URI
     * @param string|null $base_uri The value of the action's base URI
     * @return string The plugin action base URI
     */
    private function getSettingActionBaseUri($location, $parent_base_uri = null, $base_uri = null)
    {
        // No base URI set means it inherits the parent
        $base_uri = ($parent_base_uri !== null && $base_uri === null ? $parent_base_uri : $base_uri);

        $nav_locations = ['nav_client', 'nav_staff', 'nav_public'];
        if (in_array($location, $nav_locations)) {
            // The base URI is based on the given base URI which may be a translated value for
            // admin/client/public or the default URI for the interface in which it is normally displayed
            $default_uri = ($location == 'nav_staff'
                ? $this->admin_uri
                : ($location == 'nav_staff' ? $this->client_uri : $this->public_uri)
            );
            $base_uri = (isset($base_uri) ? $base_uri : $default_uri);

            if (in_array($base_uri, ['public', 'admin', 'client'])) {
                $base_uri = (isset($this->{$base_uri . '_uri'}) ? $this->{$base_uri . '_uri'} : null);
            }
        }

        return (isset($base_uri) ? $base_uri : null);
    }

    /**
     * Processes any input submission data
     * May sets any error messages to the current view, or redirect on successful submission
     *
     * @param stdClass $plugin An stdClass object representing the plugin
     * @param array $actions An array of plugin actions
     */
    private function processSettingsActions(stdClass $plugin, array $actions)
    {
        // Only handle POST data
        if (empty($this->post)) {
            return;
        }

        // Ensure the 'enabled' setting is available even when all checkboxes are unchecked
        $this->post = array_merge(['enabled' => []], $this->post);

        // Start a transaction
        $this->PluginManager->begin();

        // Update the provided fields on each cron task
        foreach ($actions as &$action) {
            $action_key = $action->location . '-' . $action->url;

            foreach ($this->post as $key => $data) {
                // Skip updating action settings that are not in this list
                if (!in_array($key, ['enabled'])) {
                    continue;
                }

                // Set the selected value for this field
                if (array_key_exists($action_key, (array) $data)) {
                    $action->{$key} = $data[$action_key];
                } elseif ($key == 'enabled') {
                    // The 'enabled' checkbox should be set to disabled if it's not given
                    $action->enabled = 0;
                }
            }

            // Save the changes
            $this->Actions->edit($action->id, (array) $action);

            // Keep the most recent errors by breaking out
            if ($this->PluginManager->errors()) {
                break;
            }
        }

        // Use only the most recent action errors.
        // Note: there should never be errors
        if (($errors = $this->PluginManager->errors())) {
            // Error, rollback and reset vars
            $this->PluginManager->rollBack();

            $this->setMessage('error', $errors);
        } else {
            // Success, commit changes
            $this->PluginManager->commit();

            // Clear the navigation cache
            $this->clearNavCache();

            $this->flashMessage('message', Language::_('AdminCompanyPlugins.!success.actions_updated', true));
            $this->redirect($this->base_uri . 'settings/company/plugins/settings/' . $plugin->id . '/actions/');
        }
    }

    /**
     * Renders/Processes the event settings for a plugin
     *
     * @param stdClass $plugin An stdClass object representing the plugin
     * @param array $data An array of input data
     */
    private function renderSettingsEvents(stdClass $plugin)
    {
        // Get all the events
        $events = $this->PluginManager->getAllEvents($plugin->id);

        // Sort the events by event name
        $event_names = [];
        foreach ($events as $key => $event) {
            $event_names[$key] = $event->event;
        }
        array_multisort($event_names, SORT_NATURAL, $events);

        // Process any input data
        $this->processSettingsEvents($plugin, $events);

        return ['events' => $events, 'vars' => (object)$this->post];
    }

    /**
     * Processes any input submission data
     * May sets any error messages to the current view, or redirect on successful submission
     *
     * @param stdClass $plugin An stdClass object representing the plugin
     * @param array $events An array of events
     */
    private function processSettingsEvents(stdClass $plugin, array $events)
    {
        // Only handle POST data
        if (empty($this->post)) {
            return;
        }

        // Ensure the 'enabled' setting is available even when all checkboxes are unchecked
        $this->post = array_merge(['enabled' => []], $this->post);

        // Start a transaction
        $this->PluginManager->begin();

        // Update the provided fields on each cron task
        foreach ($events as &$event) {
            foreach ($this->post as $key => $data) {
                // Skip updating event settings that are not in this list
                if (!in_array($key, ['enabled'])) {
                    continue;
                }

                // Set the selected value for this field
                if (array_key_exists($event->event, (array) $data)) {
                    $event->{$key} = $data[$event->event];
                } elseif ($key == 'enabled') {
                    // The 'enabled' checkbox should be set to disabled if it's not given
                    $event->enabled = 0;
                }
            }

            // Remove the callback so we don't update it since it's still serialized at this point
            unset($event->callback);

            // Save the changes
            $this->PluginManager->editEvent($event->plugin_id, $event->event, (array) $event);

            // Keep the most recent errors by breaking out
            if ($this->PluginManager->errors()) {
                break;
            }
        }

        // Use only the most recent event errors.
        // Note: there should never be errors
        if (($errors = $this->PluginManager->errors())) {
            // Error, rollback and reset vars
            $this->PluginManager->rollBack();

            $this->setMessage('error', $errors);
        } else {
            // Success, commit changes
            $this->PluginManager->commit();

            $this->flashMessage('message', Language::_('AdminCompanyPlugins.!success.events_updated', true));
            $this->redirect($this->base_uri . 'settings/company/plugins/settings/' . $plugin->id . '/events/');
        }
    }

    /**
     * Install a plugin
     */
    public function install()
    {
        if (!isset($this->post['id'])) {
            $this->redirect($this->base_uri . 'settings/company/plugins/available/');
        }

        if (!isset($this->StaffGroups)) {
            $this->uses(['StaffGroups']);
        }
        $group = $this->StaffGroups->getStaffGroupByStaff($this->Session->read('blesta_staff_id'), $this->company_id);

        $plugin_id = $this->PluginManager->add([
            'dir' => $this->post['id'],
            'company_id' => $this->company_id,
            'staff_group_id' => $group->id
        ]);

        if (($errors = $this->PluginManager->errors())) {
            $this->flashMessage('error', $errors);
            $this->redirect($this->base_uri . 'settings/company/plugins/available/');
        } else {
            $this->flashMessage('message', Language::_('AdminCompanyPlugins.!success.installed', true));
            $this->redirect($this->base_uri . 'settings/company/plugins/installed/');
        }
    }

    /**
     * Uninstall a plugin
     */
    public function uninstall()
    {
        $this->performAction('delete', Language::_('AdminCompanyPlugins.!success.uninstalled', true));
    }

    /**
     * Disable a plugin
     */
    public function disable()
    {
        $this->performAction('disable', Language::_('AdminCompanyPlugins.!success.disabled', true));
    }

    /**
     * Enable a plugin
     */
    public function enable()
    {
        $this->performAction('enable', Language::_('AdminCompanyPlugins.!success.enabled', true));
    }

    /**
     * Upgrades a plugin
     */
    public function upgrade()
    {
        $this->performAction('upgrade', Language::_('AdminCompanyPlugins.!success.upgraded', true));
    }

    /**
     * Performs an action on the given installed plugin
     *
     * @param string $action The PluginManager method to invoke
     * @param string $message The success message to set on success
     */
    protected function performAction($action, $message)
    {
        if (!isset($this->post['id']) || !($plugin = $this->PluginManager->get($this->post['id']))) {
            $this->redirect($this->base_uri . 'settings/company/plugins/installed/');
        }

        call_user_func_array([$this->PluginManager, $action], [$this->post['id']]);

        if (($errors = $this->PluginManager->errors())) {
            $this->flashMessage('error', $errors);
            $this->redirect($this->base_uri . 'settings/company/plugins/installed/');
        } else {
            // Plugin upgrades can alter nav actions, so clear the cache too
            if ($action === 'upgrade') {
                $this->clearNavCache();
            }

            $this->flashMessage('message', $message);
            $this->redirect($this->base_uri . 'settings/company/plugins/installed/');
        }
    }

    /**
     * Clears the navigation cache for all staff in the system
     */
    private function clearNavCache()
    {
        $this->uses(['Staff', 'StaffGroups']);

        // Clear the navigation cache
        $groups = $this->StaffGroups->getAll();
        foreach ($groups as $group) {
            // Clear nav cache for this group
            $staff_members = $this->Staff->getAll(null, null, $group->id);
            foreach ($staff_members as $staff_member) {
                Cache::clearCache(
                    'nav_staff_group_' . $group->id,
                    $group->company_id . DS . 'nav' . DS . $staff_member->id . DS
                );
            }
        }
    }
}
