<?php

use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * Plugin manager. Handles installing/uninstalling plugins through their respective plugin handlers.
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PluginManager extends AppModel
{
    /**
     * Initialize Plugin Manager
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['plugin_manager']);
        Loader::loadModels($this, ['Actions']);
    }

    /**
     * Lists all installed plugins
     *
     * @param int $company_id The company ID
     * @param string $order The sort and order fields (optional, default name ascending)
     * @return array An array of stdClass objects representing installed plugins
     */
    public function getAll($company_id, array $order = ['name' => 'asc'])
    {
        $fields = ['id', 'dir', 'company_id', 'name', 'version', 'enabled'];

        $plugins = $this->Record->select($fields)->from('plugins')->where('company_id', '=', $company_id)->
                order($order)->fetchAll();

        $num_plugins = count($plugins);
        for ($i = 0; $i < $num_plugins;) {
            try {
                $plugin = $this->loadPlugin($plugins[$i]->dir);

                // Set the installed version of the plugin
                $plugins[$i]->installed_version = $plugins[$i]->version;
            } catch (Exception $e) {
                // Plugin could not be loaded
                $i++;
                continue;
            }

            $info = $this->getPluginInfo($plugin, $company_id);
            foreach ((array) $info as $key => $value) {
                $plugins[$i]->$key = $value;
            }
            $i++;
        }

        return $plugins;
    }

    /**
     * Fetches all plugins installed in the system
     *
     * @return array An array of stdClass objects, each representing an installed plugin record
     */
    public function getInstalled()
    {
        $fields = ['id', 'dir', 'company_id', 'name', 'version', 'enabled'];

        return $this->Record->select($fields)->from('plugins')->fetchAll();
    }

    /**
     * Fetches a plugin for a given company, or all plugins installed in the system for the given plugin directory
     *
     * @param string $plugin_dir The directory name of the plugin to return results for
     * @param int $company_id The ID of the company to fetch plugins for
     * @return array An array of stdClass objects, each representing an installed plugin record
     */
    public function getByDir($plugin_dir, $company_id = null)
    {
        $fields = ['id', 'dir', 'company_id', 'name', 'version', 'enabled'];

        $this->Record->select($fields)->from('plugins')->
            where('dir', '=', $plugin_dir);
        if ($company_id !== null) {
            $this->Record->where('company_id', '=', $company_id);
        }

        return $this->Record->fetchAll();
    }

    /**
     * Fetches a single installed plugin.
     *
     * @param int $plugin_id The plugin ID to fetch
     * @param bool $detailed True to return detailed information about the plugin, false otherwise
     * @param int $company_id The company ID to filter on, null for no
     *  filter, false for current company (optional, default false)
     * @return mixed A stdClass object representing the installed plugin,
     *  false if no such plugin exists or is not installed
     */
    public function get($plugin_id, $detailed = false, $company_id = null)
    {
        // Default to filtering by the current company ID
        if ($company_id === false) {
            $company_id = Configure::get('Blesta.company_id');
        }

        $fields = ['id', 'dir', 'company_id', 'name', 'version', 'enabled'];
        $this->Record->select($fields)->from('plugins')->where('id', '=', $plugin_id);

        // Filter by company ID unless the value NULL was explicitly given
        if ($company_id !== null) {
            $this->Record->where('company_id', '=', $company_id);
        }

        $plugin = $this->Record->fetch();

        if ($plugin && $detailed) {
            try {
                $loaded_plugin = $this->loadPlugin($plugin->dir);

                // Set the installed version of the plugin
                $plugin->installed_version = $plugin->version;

                $info = $this->getPluginInfo($loaded_plugin, $company_id);
                foreach ((array) $info as $key => $value) {
                    $plugin->$key = $value;
                }
            } catch (Exception $e) {
                // Plugin could not be loaded
            }
        }

        return $plugin;
    }

    /**
     * Lists all available plugins (those that exist on the file system)
     *
     * @param int $company_id The ID of the company to get available plugins for
     * @return array An array of stdClass objects representing available plugins
     */
    public function getAvailable($company_id = null)
    {
        $plugins = [];

        $dir = opendir(PLUGINDIR);
        for ($i = 0; false !== ($file = readdir($dir));) {
            // If the file is not a hidden file, and is a directory, accept it
            if (substr($file, 0, 1) != '.' && is_dir(PLUGINDIR . DS . $file)) {
                // Ensure a plugin handler is available, which is required to install or uninstall this plugin
                if (file_exists(PLUGINDIR . DS . $file . DS . $file . '_plugin.php')) {
                    try {
                        $plugin = $this->loadPlugin($file);
                        $plugins[$i] = new stdClass();
                    } catch (Exception $e) {
                        // The plugins could not be loaded, try the next one
                        continue;
                    }

                    $info = $this->getPluginInfo($plugin, $company_id);
                    foreach ((array) $info as $key => $value) {
                        $plugins[$i]->$key = $value;
                    }
                    $i++;
                }
            }
        }

        // Close the directory, we're done now
        closedir($dir);

        // Order the available plugins by their names using a natural order algorithm
        usort($plugins, function($current, $next) {
            $names = [$current->name, $next->name];
            natsort($names);

            $first = reset($names);

            return ($current->name == $first) ? -1 : 1;
        });

        return $plugins;
    }

    /**
     * Checks whether the given plugin is installed for the specified company
     *
     * @param string $dir The plugin dir (in file_case)
     * @param int $company_id The ID of the company to fetch for (null
     *  checks if the plugin is installed across any company)
     * @return bool True if the plugin is installed, false otherwise
     */
    public function isInstalled($dir, $company_id = null)
    {
        $this->Record->select(['plugins.id'])->from('plugins')->
            where('dir', '=', $dir);

        if ($company_id) {
            $this->Record->where('company_id', '=', $company_id);
        }

        return (boolean) $this->Record->fetch();
    }

    /**
     * Checks whether the given plugin is the last instance installed
     *
     * @param string $dir The plugin dir (in file_case)
     * @return bool True if the plugin is the last instance, false otherwise
     */
    public function isLastInstance($dir)
    {
        $count = $this->Record->select(['plugins.id'])->from('plugins')->
            where('dir', '=', $dir)->numResults();

        return ($count <= 1);
    }

    /**
     * Adds the plugin to the system
     *
     * @param array $vars An array of plugin information including:
     *
     *  - dir The dir name for the plugin to be installed
     *  - company_id The ID of the company the plugin should be installed for
     *  - staff_group_id The ID of the staff group to grant access to all permissions created by this plugin (optional)
     * @return int The ID of the plugin installed, void on error
     */
    public function add(array $vars)
    {
        Loader::loadModels($this, ['Navigation']);
        if (!isset($this->MessageGroups)) {
            Loader::loadModels($this, ['MessageGroups']);
        }

        $plugin = $this->loadPlugin($vars['dir']);

        $vars['version'] = $plugin->getVersion();
        $vars['name'] = $plugin->getName();

        $fields = ['company_id', 'name', 'dir', 'version'];
        $this->Record->insert('plugins', $vars, $fields);
        $plugin_id = $this->Record->lastInsertId();

        // Run the installation
        $plugin->install($plugin_id);

        // Check for errors installing
        if (($errors = $plugin->errors())) {
            $this->Input->setErrors($errors);

            // Delete the installed plugin
            $this->Record->from('plugins')->where('plugins.id', '=', $plugin_id)->delete();
            return;
        }

        $this->Input->setRules($this->getAddRules($vars));

        // Install the plugin with its actions, cards, events, messages, and permissions (if any)
        if ($this->Input->validates($vars)) {
            $actions = $plugin->getActions();
            $cards = $plugin->getCards();
            $events = $plugin->getEvents();
            $messages = $plugin->getMessageTemplates();
            $permissions = $plugin->getPermissions();
            $permission_groups = $plugin->getPermissionGroups();

            if ($actions && is_array($actions)) {
                foreach ($actions as $action) {
                    $this->addAction($plugin_id, $action);
                }
            }

            if ($cards && is_array($cards)) {
                foreach ($cards as $card) {
                    $this->addCard($plugin_id, $card);
                }
            }

            if ($events && is_array($events)) {
                foreach ($events as $event) {
                    $this->addEvent($plugin_id, $event);
                }
            }

            if ($messages && is_array($messages)) {
                foreach ($messages as $message) {
                    $this->addMessage($plugin_id, $message);
                }
            }

            // It is important that groups are added before permissions so that permissions are targeting valid groups
            if ($permission_groups && is_array($permission_groups)) {
                foreach ($permission_groups as $permission_group) {
                    $this->addPermissionGroup($plugin_id, $permission_group);
                }
            }

            if ($permissions && is_array($permissions)) {
                foreach ($permissions as $permission) {
                    $this->addPermission($plugin_id, $permission);
                }
            }

            // Grant all permissions to this staff group
            if (isset($vars['staff_group_id'])) {
                Loader::loadModels($this, ['StaffGroups']);

                $this->StaffGroups->grantPermission($vars['staff_group_id'], $plugin_id);
            }

            return $plugin_id;
        } else {
            // Rollback if validation failed
            $this->Record->rollBack();
        }
    }

    /**
     * Runs the plugin's upgrade method to upgrade the plugin to match that of the plugin's file version.
     * Sets errors in PluginManager::errors() if any errors are set by the plugin's upgrade method.
     *
     * @param int $plugin_id The ID of the plugin to upgrade
     */
    public function upgrade($plugin_id)
    {
        Loader::loadModels($this, ['Navigation']);
        $installed_plugin = $this->get($plugin_id);

        if (!$installed_plugin) {
            return;
        }

        $plugin = $this->loadPlugin($installed_plugin->dir);
        $file_version = $plugin->getVersion();

        // Execute the upgrade if the installed version doesn't match the file version
        if (version_compare($file_version, $installed_plugin->version, '!=')) {
            $upgrade_plugins = $this->getByDir($installed_plugin->dir);

            // Upgrade permissions
            $permissions = $plugin->getPermissions();
            $permission_groups = $plugin->getPermissionGroups();
            $permissions_map = ['old_permission_groups' => [], 'old_permissions' => []];

            foreach ($upgrade_plugins as $upgrade_plugin) {
                // Save a copy of the old permissions, in case we need to restore them later
                $permissions_map['old_permission_groups'] = array_merge(
                    $permissions_map['old_permission_groups'],
                    $this->Record->select()
                        ->from('permission_groups')
                        ->where('plugin_id', '=', $upgrade_plugin->id)
                        ->fetchAll()
                );
                $permissions_map['old_permissions'] = array_merge(
                    $permissions_map['old_permissions'],
                    $this->Record->select()
                        ->from('permissions')
                        ->where('plugin_id', '=', $upgrade_plugin->id)
                        ->fetchAll()
                );

                // Add the plugin permission groups anew (important that this comes before permissions to make
                // sure they have valid groups to target)
                if ($permission_groups && is_array($permission_groups)) {
                    // Delete current permission groups
                    $this->Record->from('permission_groups')->where('plugin_id', '=', $upgrade_plugin->id)->delete();
                    foreach ($permission_groups as $permission_group) {
                        $this->addPermissionGroup($upgrade_plugin->id, $permission_group);
                    }
                }

                // Add the plugin permissions anew
                if ($permissions && is_array($permissions)) {
                    // Delete current permissions
                    $this->Record->from('permissions')->where('plugin_id', '=', $upgrade_plugin->id)->delete();
                    foreach ($permissions as $permission) {
                        $this->addPermission($upgrade_plugin->id, $permission);
                    }
                }
            }

            // Upgrade plugin
            $plugin->upgrade($installed_plugin->version, $plugin_id);

            if (($errors = $plugin->errors())) {
                $this->Input->setErrors($errors);

                // Remove previously added permission groups and permissions
                foreach ($upgrade_plugins as $upgrade_plugin) {
                    $this->Record->from('permission_groups')->where('plugin_id', '=', $upgrade_plugin->id)->delete();
                    $this->Record->from('permissions')->where('plugin_id', '=', $upgrade_plugin->id)->delete();
                }

                // Restore old permission groups
                if (isset($permissions_map['old_permission_groups'])) {
                    foreach ($permissions_map['old_permission_groups'] as $permission_group) {
                        if (!empty($permission_group)) {
                            $this->addPermissionGroup($permission_group->plugin_id, [
                                'name' => $permission_group->name,
                                'level' => $permission_group->level,
                                'alias' => $permission_group->alias
                            ]);
                        }
                    }
                }

                // Restore old permissions
                if (isset($permissions_map['old_permissions'])) {
                    Loader::loadModels($this, ['Permissions']);

                    foreach ($permissions_map['old_permissions'] as $permission) {
                        if (!empty($permission)) {
                            $group = $this->Permissions->get($permission->group_id);
                            $this->addPermission($permission->plugin_id, [
                                'group_alias' => $group->alias,
                                'name' => $permission->name,
                                'alias' => $permission->alias,
                                'action' => $permission->action
                            ]);
                        }
                    }
                }
            } else {
                // The plugin itself has been upgraded, now upgrade the actions, cards and events
                // for ALL instances of the plugin
                $actions = $plugin->getActions();
                $cards = $plugin->getCards();
                $events = $plugin->getEvents();
                $messages = $plugin->getMessageTemplates();

                foreach ($upgrade_plugins as $upgrade_plugin) {
                    // Retrieve all plugin actions/cards/events prior to deletion
                    $current_actions = $this->getActionsByPlugin($upgrade_plugin->id);
                    $current_cards = $this->getCardsByPlugin($upgrade_plugin->id);
                    $current_events = $this->getEventsByPlugin($upgrade_plugin->id);
                    $current_messages = $this->getMessagesPlugin($upgrade_plugin->id);

                    // Delete current actions/cards/events
                    $this->Record->from('plugin_cards')->where('plugin_id', '=', $upgrade_plugin->id)->delete();
                    $this->Record->from('plugin_events')->where('plugin_id', '=', $upgrade_plugin->id)->delete();

                    $this->deleteActions($upgrade_plugin->id);


                    // U[date/add plugin actions
                    if ($actions && is_array($actions)) {
                        foreach ($actions as $action) {
                            $this->addAction($upgrade_plugin->id, $action, $current_actions);
                        }
                    }

                    // Add the plugin cards anew
                    if ($cards && is_array($cards)) {
                        foreach ($cards as $card) {
                            $this->addCard(
                                $upgrade_plugin->id,
                                $this->formatCardUpgrade($card, $current_cards)
                            );
                        }
                    }

                    // Add the plugin events anew
                    if ($events && is_array($events)) {
                        foreach ($events as $event) {
                            $this->addEvent(
                                $upgrade_plugin->id,
                                $this->formatEventUpgrade($event, $current_events)
                            );
                        }
                    }

                    // Add the new plugin messages
                    if ($messages && is_array($messages)) {
                        $plugin_messages = [];
                        foreach ($messages as $message) {
                            $plugin_messages[$message['action']] = $message;
                        }

                        foreach (array_diff_key($plugin_messages, $current_messages) as $action => $message) {
                            $this->addMessage($upgrade_plugin->id, $message);
                        }

                        foreach (array_diff_key($current_messages, $plugin_messages) as $action => $message) {
                            $this->deleteMessage($action);
                        }
                    }
                }

                // Update all installed plugins to the given version
                $this->setVersion($installed_plugin->dir, $file_version);
            }
        }
    }

    /**
     * Formats an event during an upgrade
     * @see PluginManager::upgrade
     *
     * @param array $event The event being upgraded
     * @param array $current_events An array of existing events already installed
     * @return array The formatted event
     */
    private function formatEventUpgrade(array $event, array $current_events)
    {
        // Check whether the event given is a current event
        if (!array_key_exists('event', $event) || !array_key_exists($event['event'], $current_events)) {
            return $event;
        }

        // Re-use the existing 'enabled' status between upgrades
        $current_event = $current_events[$event['event']];
        $event['enabled'] = $current_event->enabled;

        return $event;
    }

    /**
     * Formats a card during an upgrade
     * @see PluginManager::upgrade
     *
     * @param array $card The card being upgraded
     * @param array $current_cards An array of existing cards already installed
     * @return array The formatted card
     */
    private function formatCardUpgrade(array $card, array $current_cards)
    {
        // Check whether the card given is a current card
        $action = $card['level'] . '.' . implode('.', $card['callback']);
        if (!array_key_exists('callback', $card) || !array_key_exists($action, $current_cards)) {
            return $card;
        }

        // Re-use the existing 'enabled' status between upgrades
        $current_card = $current_cards[$action];
        $card['enabled'] = $current_card->enabled;
        $card['text_color'] = $current_card->text_color;
        $card['background'] = $current_card->background;
        $card['background_type'] = $current_card->background_type;

        return $card;
    }

    /**
     * Permanently and completely removes the plugin specified by $plugin_id
     *
     * @param int $plugin_id The ID of the plugin to permanently remove
     */
    public function delete($plugin_id)
    {
        Loader::loadModels($this, ['Messages', 'MessageGroups']);

        $plugin = $this->get($plugin_id);

        // Delete message templates for this plugin/company
        $messages = $this->Messages->getAll($plugin->company_id, ['plugin_dir' => $plugin->dir]);
        foreach ($messages as $message) {
            $this->Messages->delete($message->id);
        }

        // Only delete message groups if this is the last instance of the plugin
        if ($this->isLastInstance($plugin->dir)) {
            $message_groups = $this->MessageGroups->getAll(['plugin_dir' => $plugin->dir]);
            foreach ($message_groups as $message_group) {
                $this->MessageGroups->delete($message_group->id);
            }
        }

        // It's the responsibility of the plugin to remove any tables or entries
        // it has created that are no longer relevant
        $plugin_handler = $this->loadPlugin($plugin->dir);
        $plugin_handler->uninstall($plugin_id, $this->isLastInstance($plugin->dir));

        if (($errors = $plugin_handler->errors())) {
            $this->Input->setErrors($errors);
            return;
        }

        $this->Record->from('plugins')->where('id', '=', $plugin_id)->delete();
        $this->deleteActions($plugin_id);
        $this->Record->from('plugin_cards')->where('plugin_id', '=', $plugin_id)->delete();
        $this->Record->from('plugin_events')->where('plugin_id', '=', $plugin_id)->delete();

        // Delete plugin permissions
        $permissions = $plugin_handler->getPermissions();
        if ($permissions && is_array($permissions)) {
            foreach ($permissions as $permission) {
                $this->deletePermission($plugin_id, $permission['alias'], $permission['action']);
            }
        }

        // Delete plugin permission groups
        $permission_groups = $plugin_handler->getPermissionGroups();
        if ($permission_groups && is_array($permission_groups)) {
            foreach ($permission_groups as $permission_group) {
                $this->deletePermissionGroup($plugin_id, $permission_group['alias']);
            }
        }

        $this->clearNavCache($plugin->company_id);
    }

    /**
     * Enables a plugin
     *
     * @param int $plugin_id
     */
    public function enable($plugin_id)
    {
        $plugin = $this->get($plugin_id, false);
        $this->Record->where('id', '=', $plugin_id)->update('plugins', ['enabled' => '1']);

        $this->clearNavCache($plugin->company_id);
    }

    /**
     * Disables a plugin
     *
     * @param int $plugin_id
     */
    public function disable($plugin_id)
    {
        $plugin = $this->get($plugin_id, false);
        $this->Record->where('id', '=', $plugin_id)->update('plugins', ['enabled' => '0']);

        $this->clearNavCache($plugin->company_id);
    }

    /**
     * Clears the nav cache for the given company ID
     *
     * @param int $company_id The ID of the company to clear nav cache for
     */
    protected function clearNavCache($company_id)
    {
        Loader::loadModels($this, ['StaffGroups', 'Staff']);

        $groups = $this->StaffGroups->getAll($company_id);
        foreach ($groups as $group) {
            // Clear nav cache for this group
            $staff_members = $this->Staff->getAll($company_id, null, $group->id);
            foreach ($staff_members as $staff_member) {
                Cache::clearCache(
                    'nav_staff_group_' . $group->id,
                    $company_id . DS . 'nav' . DS . $staff_member->id . DS
                );
            }
        }
    }

    /**
     * Adds an event to the system with a callback to be invoked when the event is triggered
     *
     * @param int $plugin_id The ID of the plugin to add an event for
     * @param array $vars An array of event info including:
     *
     *  - event The event to register the callback under
     *  - callback The public static callback to invoke
     *  - enabled Sets whether the event is enabled (1 to enable, 0 to disable) (optional, default 1)
     */
    public function addEvent($plugin_id, array $vars)
    {
        // Set the plugin ID for the event
        $vars['plugin_id'] = $plugin_id;

        $this->Input->setRules($this->getEventRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ['plugin_id', 'event', 'callback', 'enabled'];
            $this->Record->insert('plugin_events', $vars, $fields);
        }
    }

    /**
     * Updates the given plugin event
     *
     * @param int $plugin_id The ID of the plugin whose event to update
     * @param string $event The name of the plugin event to update
     * @param array $vars An array of event fields to update including:
     *
     *  - callback The public static callback to invoke (optional)
     *  - enabled Sets whether the event is enabled (1 to enable, 0 to disable) (optional)
     */
    public function editEvent($plugin_id, $event, array $vars)
    {
        // Set the plugin/event this update is for
        $vars['plugin_id'] = $plugin_id;
        $vars['event'] = $event;

        $this->Input->setRules($this->getEventRules($vars, true));

        if ($this->Input->validates($vars)) {
            // Only allow the callback/enabled fields to be updated
            $fields = ['callback', 'enabled'];
            $this->Record->where('plugin_id', '=', $vars['plugin_id'])
                ->where('event', '=', $vars['event'])
                ->update('plugin_events', $vars, $fields);
        }
    }

    /**
     * Removes the event from the plugin so the event will no longer be triggered
     *
     * @param int $plugin_id The ID of the plugin to remove the event from
     * @param string $event The event to remove from the plugin
     */
    public function deleteEvent($plugin_id, $event)
    {
        $this->Record->from('plugin_events')
            ->where('plugin_id', '=', $plugin_id)
            ->where('event', '=', $event)
            ->delete();
    }

    /**
     * Retrieves the plugin event rules for add/edit
     *
     * @param array $vars An array of input data
     * @param bool $edit Whether or not to fetch the rules for an edit (optional, default false)
     * @return array An array of input validation rules including:
     *
     *  - plugin_id The ID of the plugin
     *  - event The event to register the callback under
     *  - callback The public static callback to invoke when the event is triggered
     *  - enabled Sets whether the action is enabled (1 to enable, 0 to disable)
     */
    private function getEventRules(array $vars, $edit = false)
    {
        $rules = [
            'plugin_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'plugins'],
                    'message' => $this->_('PluginManager.!error.plugin_id.exists')
                ]
            ],
            'event' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('PluginManager.!error.event.empty')
                ],
                'length' => [
                    'rule' => ['maxLength', 128],
                    'message' => $this->_('PluginManager.!error.event.length')
                ]
            ],
            'callback' => [
                'exists' => [
                    'rule' => true,
                    'post_format' => 'serialize',
                    'message' => $this->_('PluginManager.!error.callback.empty')
                ]
            ],
            'enabled' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', [1, 0]],
                    'message' => $this->_('PluginManager.!error.enabled.valid')
                ]
            ]
        ];

        if ($edit) {
            // Callback is optional
            $rules['callback']['exists']['if_set'] = true;

            // The plugin event must exist in order to update it
            $rules['event']['exists'] = [
                'rule' => [
                    function ($event, $plugin_id) {
                        $total = $this->Record->select()
                            ->from('plugin_events')
                            ->where('plugin_id', '=', $plugin_id)
                            ->where('event', '=', $event)
                            ->numResults();

                        return ($total === 1);
                    },
                    ['_linked' => 'plugin_id']
                ],
                'message' => $this->_('PluginManager.!error.event.exists')
            ];
        }

        return $rules;
    }

    /**
     * Adds a message to the system
     *
     * @param int $plugin_id The ID of the plugin to add a message for
     * @param array $vars An array of message info including:
     *
     *  - action The name of the action that triggers the message
     *  - type The level of the message ('staff', 'client', 'shared')
     *  - tags A comma separated list of replacement tags (e.g. {client},{service.name})
     *  - content A key/value list of messenger types and their default content ()
     */
    public function addMessage($plugin_id, array $vars)
    {
        if (!isset($this->Languages)) {
            Loader::loadModels($this, ['Languages']);
        }

        if (!isset($this->MessageGroups)) {
            Loader::loadModels($this, ['MessageGroups']);
        }

        if (!isset($this->Messages)) {
            Loader::loadModels($this, ['Messages']);
        }

        // Get plugin
        $plugin = $this->Record->select()
            ->from('plugins')
            ->where('id', '=', $plugin_id)
            ->fetch();

        // Add message group
        $vars['plugin_dir'] = $plugin->dir;
        $message_group = $this->MessageGroups->getByAction($vars['action']);

        if ($message_group) {
            $group_id = $message_group->id;
        } else {
            $group_id = $this->MessageGroups->add($vars);

            if (($errors = $this->MessageGroups->errors())) {
                $this->Input->setErrors($errors);
            }
        }

        if (!$group_id) {
            return;
        }

        // Fetch all currently-installed languages for this company, for which message templates should be created for
        $languages = $this->Languages->getAll($plugin->company_id);

        foreach ($vars['content'] as $type => $content) {
            $message_vars = [
                'message_group_id' => $group_id,
                'company_id' => $plugin->company_id,
                'type' => $type,
                'status' => 'active',
                'content' => []
            ];
            foreach ($languages as $language) {
                $message_vars['content'][] = [
                    'lang' => $language->code,
                    'content' => $content
                ];
            }

            $this->Messages->add($message_vars);

            if (($errors = $this->Messages->errors())) {
                $this->Input->setErrors($errors);
            }
        }
    }

    /**
     * Updates the given plugin message
     *
     * @param int $plugin_id The ID of the plugin to update the message for
     * @param array $vars An array of message info including:
     *
     *  - action The name of the action that triggers the message
     *  - type The level of the message ('staff', 'client', 'shared')
     *  - tags A comma separated list of replacement tags (e.g. {client},{service.name})
     *  - content A key/value list of messenger types and their default content ()
     * @param int $message_id The message ID to update
     */
    public function editMessage($plugin_id, array $vars, $message_id)
    {
        if (!isset($this->Languages)) {
            Loader::loadModels($this, ['Languages']);
        }

        if (!isset($this->MessageGroups)) {
            Loader::loadModels($this, ['MessageGroups']);
        }

        if (!isset($this->Messages)) {
            Loader::loadModels($this, ['Messages']);
        }

        // Get plugin
        $plugin = $this->Record->select()
            ->from('plugins')
            ->where('id', '=', $plugin_id)
            ->fetch();

        // Fetch message group
        $message_group = $this->MessageGroups->getByAction($vars['action']);

        if ($message_group) {
            $group_id = $message_group->id;
        } else {
            return;
        }

        // Fetch all currently-installed languages for this company, for which message templates should be updated for
        $languages = $this->Languages->getAll($plugin->company_id);

        foreach ($vars['content'] as $type => $content) {
            $message_vars = [
                'message_group_id' => $group_id,
                'company_id' => $plugin->company_id,
                'type' => $type,
                'status' => 'active',
                'content' => []
            ];
            foreach ($languages as $language) {
                $message_vars['content'][] = [
                    'lang' => $language->code,
                    'content' => $content
                ];
            }

            $this->Messages->edit($message_id, $message_vars);

            if (($errors = $this->Messages->errors())) {
                $this->Input->setErrors($errors);
            }
        }
    }

    /**
     * Removes the all message details for an action
     *
     * @param string $action The action for the message to remove from the plugin
     */
    public function deleteMessage($action)
    {
        if (!isset($this->MessageGroups)) {
            Loader::loadModels($this, ['MessageGroups']);
        }

        $message_group = $this->MessageGroups->getByAction($action);
        if ($message_group) {
            $this->MessageGroups->delete($message_group->id);
        }
    }

    /**
     * Retrieves all message templates that are registered for a particular plugin
     *
     * @param int $plugin_id The ID of the plugin
     * @return array An array of stdClass objects representing registered templates
     */
    private function getMessagesPlugin($plugin_id)
    {
        $temp = $this->Record->select(['messages.id', 'message_groups.*'])
            ->from('messages')
            ->innerJoin('plugins', 'plugins.id', '=', $plugin_id)
            ->innerJoin('message_groups', 'message_groups.id', '=', 'messages.message_group_id', false)
            ->where('messages.company_id', '=', 'plugins.company_id', false)
            ->where('message_groups.plugin_dir', '=', 'plugins.dir', false)
            ->fetchAll();

        // Re-key the messages by action
        $messages = [];
        foreach ($temp as $message) {
            $messages[$message->action] = $message;
        }

        return $messages;
    }

    /**
     * Adds a plugin action
     *
     * @param int $plugin_id The ID pf the plugin for which to add this action
     * @param array $vars An array of action fields to add including:
     *
     *  - location The identifier for the locations to display the action (optional, "nav_staff" by default)
     *       ('nav_client', 'nav_staff', 'nav_public', 'widget_client_home', 'widget_staff_home',
     *          'widget_staff_client', 'widget_staff_billing', 'action_staff_client')
     *  - url The URL of the action
     *  - name The language definition naming this action
     *  - options An array of key/value pairs to set for the given action (if necessary) (optional)
     *  - enabled Sets whether the action is enabled (1 to enable, 0 to disable) (optional)
     * @param array $current_actions An array of existing actions already installed
     */
    public function addAction($plugin_id, array $vars, array $current_actions = [])
    {
        // Keep track of actions that have been inserted
        static $inserted_action_ids = [];

        // Set the plugin this action is for
        $vars['plugin_id'] = $plugin_id;
        $plugin = $this->Record->select()->from('plugins')->where('id', '=', $plugin_id)->fetch();
        $vars['company_id'] = $plugin ? $plugin->company_id : Configure::get('Blesta.company_id');
        $var_sets = $this->convertOldActionParams($vars);

        foreach ($var_sets as $var_set) {
            $navigation_var_sets = [];
            if (isset($current_actions[$var_set['location']][$var_set['url']])) {
                if (isset($inserted_action_ids[$var_set['location']][$plugin_id][$var_set['url']])) {
                    // Skip re-adding an existing action if we have already done so during this upgrade
                    continue;
                }

                // Re-add action respecting the 'enabled' setting being used before
                $current_action = $current_actions[$var_set['location']][$var_set['url']];
                $var_set['enabled'] = $current_action->enabled;

                // Keep the previous navigation items
                $navigation_var_sets = $current_action->nav_items;
            } elseif ((!isset($var_set['enabled']) || $var_set['enabled'] == '1')) {
                // Add this action to the navigation if it is not disabled
                $navigation_var_sets[] = [
                    'company_id' => $var_set['company_id'],
                    'parent_url' => isset($var_set['parent_url'])
                        ? $var_set['parent_url']
                        : null,
                ];
            }

            if (isset($inserted_action_ids[$var_set['location']][$plugin_id][$var_set['url']])) {
                // Use the previously inserted action if we have already used this location, url, and plugin
                $action_id = $inserted_action_ids[$var_set['location']][$plugin_id][$var_set['url']];
            } else {
                // Add action and navigation item
                $action_id = $this->Actions->add($var_set);
                $inserted_action_ids[$var_set['location']][$plugin_id][$var_set['url']] = $action_id;
            }

            // Add navigation items for this action
            foreach ($navigation_var_sets as $navigation_vars) {
                $navigation_vars = (array)$navigation_vars;
                $navigation_vars['action_id'] = $action_id;
                unset($navigation_vars['parent_id']);
                $this->Navigation->add($navigation_vars);
            }
        }
    }

    /**
     * Allow backward compatibility for plugin_action parameters by converting them to the new form
     *
     * @param array $params A list of parameters for the plugin action
     * @return array A list of var sets each representing a plugin action
     */
    private function convertOldActionParams($params)
    {
        // Add the navigation items twice for the 'nav_primary_client' action,
        // once for the client nav and once for the public
        $nav_insert_iterations = 1;
        if (isset($params['action']) && $params['action'] == 'nav_primary_client') {
            $nav_insert_iterations = 2;
        }

        $nav_items = [];
        for ($i = 0; $i < $nav_insert_iterations; $i++) {
            // Set the location and url based on the old action parameters
            $primary_nav = $this->Actions->mapOldFields($params);

            // If this is the second primary navigation item being inserted then it is for the public nav
            if ($i === 1) {
                $primary_nav['location'] = 'nav_public';
            }

            if (isset($primary_nav['options']['parent'])) {
                $primary_nav['parent_url'] = $primary_nav['options']['parent'];
            }

            // Set actions based on the old action sub options
            $sub_items = [];
            if (isset($primary_nav['options']['sub'])) {
                foreach ($primary_nav['options']['sub'] as $sub_nav) {
                    $sub_items[] = [
                        'location' => $primary_nav['location'],
                        'url' => isset($sub_nav['uri']) ? $sub_nav['uri'] : $sub_nav['url'],
                        'name' => $sub_nav['name'],
                        'plugin_id' => $primary_nav['plugin_id'],
                        'enabled' => isset($sub_nav['enabled']) ? $sub_nav['enabled'] : '1',
                        'company_id' => $primary_nav['company_id'],
                        'parent_url' => $primary_nav['url']
                    ];
                }
            }
            unset($primary_nav['options']['sub']);

            // Set actions based on the old action secondary options
            if (isset($primary_nav['options']['secondary'])) {
                foreach ($primary_nav['options']['secondary'] as $secondary_nav) {
                    $sub_items[] = [
                        'location' => $primary_nav['location'],
                        'url' => isset($secondary_nav['uri']) ? $secondary_nav['uri'] : $secondary_nav['url'],
                        'name' => $secondary_nav['name'],
                        'plugin_id' => $primary_nav['plugin_id'],
                        'enabled' => isset($secondary_nav['enabled']) ? $secondary_nav['enabled'] : '1',
                        'company_id' => $primary_nav['company_id'],
                        'options' => serialize(['sub_as_secondary' => true]),
                        'parent_url' => $primary_nav['url']
                    ];
                }
            }
            unset($primary_nav['options']['secondary']);
            $nav_items = array_merge($nav_items, array_merge([$primary_nav], $sub_items));
        }

        return $nav_items;
    }

    /**
     * Removes the action from the plugin
     *
     * @param int $plugin_id The ID of the plugin to remove the action from
     * @param string $url The URL of the specific record to delete,
     *  otherwise defaults to delete all records for this plugin (optional)
     */
    public function deleteActions($plugin_id, $url = null)
    {
        $this->Actions->delete($plugin_id, $url);
    }

    /**
     * Adds a card to the system
     *
     * @param int $plugin_id The ID of the plugin to register the card under
     * @param array $vars An array of card fields including:
     *
     *  - level The level this card should be displayed on (client or staff) (optional, default client)
     *  - callback A method defined by the plugin class for calculating the value of the card or fetching a custom html
     *  - callback_type The callback type, 'value' to fetch the card value or
     *      'html' to fetch the custom html code (optional, default value)
     *  - text_color The text color in hexadecimal for this card (optional)
     *  - background The background color in hexadecimal or path to the background image for this card (optional)
     *  - background_type The background type, 'color' to set a hexadecimal background or
     *      'image' to set an image background (optional, default color)
     *  - label A string or language key appearing under the value as a label
     *  - link The link to which the card will be pointed (optional)
     *  - enabled Whether this card appears on client profiles by default
     *      (1 to enable, 0 to disable) (optional, default 1)
     */
    public function addCard($plugin_id, array $vars)
    {
        // Set the plugin this card is for
        $vars['plugin_id'] = $plugin_id;

        $this->Input->setRules($this->getCardRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = [
                'plugin_id', 'level', 'callback', 'callback_type',
                'label', 'link', 'text_color', 'background', 'background_type', 'enabled'
            ];
            $this->Record->insert('plugin_cards', $vars, $fields);
        }
    }

    /**
     * Updates the given plugin card
     *
     * @param int $plugin_id The ID of the plugin whose card to update
     * @param array $callback The callback of the plugin card to update
     * @param string $level The level of the plugin card to update
     * @param array $vars An array of action fields to update including:
     *
     *  - label A string or language key appearing under the value as a label
     *  - link The card link URL (optional)
     *  - text_color The text color in hexadecimal for this card (optional)
     *  - background The background color in hexadecimal or path to the background image for this card (optional)
     *  - background_type The background type, 'color' to set a hexadecimal background or
     *      'image' to set an image background (optional, default color)
     *  - enabled Whether this card appears on client profiles by default
     *      (1 to enable, 0 to disable) (optional, default 1)
     */
    public function editCard($plugin_id, $callback, $level, array $vars)
    {
        $vars['plugin_id'] = $plugin_id;
        $vars['callback'] = $callback;
        $vars['level'] = $level;

        $this->Input->setRules($this->getCardRules($vars, true));

        if ($this->Input->validates($vars)) {
            if (!is_scalar($vars['callback'])) {
                $vars['callback'] = serialize((array)$vars['callback']);
            }

            // Only allow the label/link/background/background_type/enabled fields to be updated
            $fields = ['label', 'link', 'text_color', 'background', 'background_type', 'enabled'];
            $this->Record->where('plugin_id', '=', $vars['plugin_id'])
                ->where('callback', '=', $vars['callback'])
                ->where('level', '=', $vars['level'])
                ->update('plugin_cards', $vars, $fields);
        }
    }

    /**
     * Removes the card from the plugin
     *
     * @param int $plugin_id The ID of the plugin to remove the card from
     * @param mixed $callback The callback of the plugin card to remove from the plugin (optional)
     * @param string $level The level of the plugin card to remove, otherwise defaults to
     *  delete all records for this card (optional)
     */
    public function deleteCard($plugin_id, $callback = null, $level = null)
    {
        if (!is_scalar($callback)) {
            $callback = serialize((array)$callback);
        }

        $this->Record->from('plugin_cards')
            ->where('plugin_id', '=', $plugin_id);

        if ($callback !== null) {
            $this->Record->where('callback', '=', $callback);
        }

        if ($level !== null) {
            $this->Record->where('level', '=', $level);
        }

        $this->Record->delete();
    }

    /**
     * Retrieves the plugin cards rules for add/edit
     *
     * @param array $vars An array of input data
     * @param bool $edit Whether or not to fetch the rules for an edit (optional, default false)
     * @return array An array of input validation rules including:
     *
     *  - plugin_id The ID of the plugin
     *  - level The level this card should be displayed on (client or staff) (optional, default client)
     *  - callback A method defined by the plugin class for calculating the value of the card or fetching a custom html
     *  - callback_type The callback type, 'value' to fetch the card value or
     *      'html' to fetch the custom html code (optional, default value)
     *  - text_color The text color in hexadecimal for this card (optional)
     *  - background The background color in hexadecimal or path to the background image for this card (optional)
     *  - background_type The background type, 'color' to set a hexadecimal background or
     *      'image' to set an image background (optional, default color)
     *  - label A string or language key appearing under the value as a label
     *  - link The link to which the card will be pointed (optional)
     *  - enabled Whether this card appears on client profiles by default
     *      (1 to enable, 0 to disable) (optional, default 1)
     */
    private function getCardRules(array $vars, $edit = false)
    {
        $rules = [
            'level' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['client', 'staff']],
                    'message' => $this->_('PluginManager.!error.level.valid')
                ]
            ],
            'callback' => [
                'unique' => [
                    'rule' => [
                        function ($callback, $plugin_id, $level) {
                            if (is_array($callback)) {
                                $callback = serialize($callback);
                            }

                            $total = $this->Record->select()
                                ->from('plugin_cards')
                                ->where('plugin_id', '=', $plugin_id)
                                ->where('callback', '=', $callback)
                                ->where('level', '=', $level)
                                ->numResults();

                            return ($total === 0);
                        },
                        ['_linked' => 'plugin_id'],
                        ['_linked' => 'level']
                    ],
                    'post_format' => 'serialize',
                    'message' => $this->_('PluginManager.!error.callback.unique')
                ]
            ],
            'callback_type' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['value', 'html']],
                    'message' => $this->_('PluginManager.!error.callback_type.valid')
                ]
            ],
            'label' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('PluginManager.!error.label.empty')
                ]
            ],
            'link' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('PluginManager.!error.link.empty')
                ]
            ],
            'text_color' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('PluginManager.!error.text_color.empty')
                ]
            ],
            'background' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('PluginManager.!error.background.empty')
                ],
                'valid' => [
                    'rule' => [
                        function($background, $background_type) {
                            // If the background type is set to "image", validate if a valid URL was given
                            if ($background_type == 'image') {
                                return preg_match(
                                    '/([--:\w?@%&+~#=]*\.[a-z]{2,4}\/{0,2})((?:[?&](?:\w+)=(?:\w+))+|[--:\w?@%&+~#=]+)?/',
                                    $background
                                );
                            }

                            return true;
                        },
                        $vars['background_type']
                    ],
                    'message' => $this->_('PluginManager.!error.background.valid')
                ]
            ],
            'background_type' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['color', 'image']],
                    'message' => $this->_('PluginManager.!error.background_type.valid')
                ]
            ],
            'enabled' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', [1, 0]],
                    'message' => $this->_('PluginManager.!error.enabled.valid')
                ]
            ]
        ];

        if ($edit) {
            // The unique rule is unnecessary, it is the one being updated
            unset($rules['callback']['unique']);

            // The label is optional
            $rules['label']['empty']['if_set'] = true;

            // The plugin ID is required to validate against
            $rules['plugin_id'] = [
                'valid' => [
                    'rule' => 'is_numeric',
                    'message' => $this->_('PluginManager.!error.plugin_id.valid')
                ]
            ];

            // The plugin action/uri must exist in order to update it
            $rules['callback']['exists'] = [
                'rule' => [
                    function ($callback, $plugin_id, $level) {
                        if (is_array($callback)) {
                            $callback = serialize($callback);
                        }

                        $total = $this->Record->select()
                            ->from('plugin_cards')
                            ->where('plugin_id', '=', $plugin_id)
                            ->where('callback', '=', $callback)
                            ->where('level', '=', $level)
                            ->numResults();

                        return ($total === 1);
                    },
                    ['_linked' => 'plugin_id'],
                    ['_linked' => 'level']
                ],
                'post_format' => 'serialize',
                'message' => $this->_('PluginManager.!error.callback.exists')
            ];
        }

        return $rules;
    }

    /**
     * Adds a permission to the system that is used to restrict access to a particular view
     *
     * @param int $plugin_id The ID of the plugin to register the permission under
     * @param array $vars An array of plugin fields including:
     *
     *  - group_alias The alias of the permission group this permission belongs to
     *  - name The name of this permission
     *  - alias The ACO alias for this permission (i.e. the Class name to apply to)
     *  - action The action this ACO may control (i.e. the Method name of the alias to control access for)
     */
    public function addPermission($plugin_id, array $vars)
    {
        Loader::loadModels($this, ['Permissions']);

        // Get permission group by alias
        $group = $this->Permissions->getGroupByAlias(isset($vars['group_alias']) ? $vars['group_alias'] : null);
        if (!$group) {
            // Get permission group by alias and plugin ID
            $group = $this->Permissions->getGroupByAlias(
                isset($vars['group_alias']) ? $vars['group_alias'] : null,
                $plugin_id
            );
        }

        // Ensure the permission does not already exist
        $permission = $this->Permissions->getByAlias(
            isset($vars['alias']) ? $vars['alias'] : null,
            $plugin_id,
            isset($vars['action']) ? $vars['action'] : null
        );

        // Add the permission
        if ($group && !$permission) {
            $vars['plugin_id'] = $plugin_id;
            $vars['group_id'] = $group->id;
            $this->Permissions->add($vars);
        }
    }

    /**
     * Removes the permission from the plugin
     *
     * @param int $plugin_id The ID of the plugin to remove the permission from
     * @param string $alias The alias of the specific record to delete,
     * @param string $action The action for which to remove a permission from the plugin
     */
    public function deletePermission($plugin_id, $alias, $action)
    {
        Loader::loadModels($this, ['Permissions']);

        // Get the permission ID
        $permission = $this->Permissions->getByAlias($alias, $plugin_id, $action);
        if ($permission) {
            // Delete the permission
            $this->Permissions->delete($permission->id);
        }
    }
    /**
     * Adds a permission group to the system that is used to restrict access to a set of views
     *
     * @param int $plugin_id The ID of the plugin to register the permission group under
     * @param array $vars An array of plugin fields including:
     *
     *  - name The name of this permission group
     *  - level The level this permission group resides on (staff or client)
     *  - alias The ACO alias for this permission group (i.e. the Class name to apply to)
     */
    public function addPermissionGroup($plugin_id, array $vars)
    {
        Loader::loadModels($this, ['Permissions']);

        // Ensure the permission group does not already exist
        $group = $this->Permissions->getGroupByAlias(isset($vars['alias']) ? $vars['alias'] : null, $plugin_id);

        // Add the permission group
        if (!$group) {
            $vars['plugin_id'] = $plugin_id;
            $this->Permissions->addGroup($vars);
        }
    }

    /**
     * Removes the permission group from the plugin
     *
     * @param int $plugin_id The ID of the plugin to remove the permission group from
     * @param string $alias The alias of the specific record to delete
     */
    public function deletePermissionGroup($plugin_id, $alias)
    {
        Loader::loadModels($this, ['Permissions']);

        // Get the permission group ID
        $group = $this->Permissions->getGroupByAlias($alias, $plugin_id);
        if ($group) {
            // Delete the permission group
            $this->Permissions->deleteGroup($group->id);
        }
    }

    /**
     * Retrieves all callbacks that are registered for a particular event and company
     *
     * @param int $company_id The ID of the company the event is registered under
     * @param string $event The event being requested
     * @param bool/int $enabled True for only enabled plugins/events, false for disabled, null for both
     * @return array An array of stdClass objects representing the registered callback events
     */
    public function getEvents($company_id, $event, $enabled = null)
    {
        return $this->fetchEvents(['company_id' => $company_id, 'event' => $event, 'enabled' => $enabled]);
    }

    /**
     * Retrieves all events from the given plugin
     *
     * @param int $plugin_id The ID of the plugin to fetch events under
     * @return array An array of stdClass objects representing registered events
     */
    public function getAllEvents($plugin_id)
    {
        return $this->fetchEvents(['plugin_id' => $plugin_id]);
    }

    /**
     * Fetches all plugin events
     *
     * @param array $options An array of options including:
     *
     *  - company_id The ID of the company whose events to fetch (optional)
     *  - plugin_id The ID of the plugin whose events to fetch (optional)
     *  - event The specific plugin event to fetch (optional)
     *  - enabled 1, 0, or null; what enabled status of events to fetch (optional, default null)
     * @return array An array of stdClass objects representing registered events
     */
    private function fetchEvents(array $options)
    {
        $fields = [
            'plugin_events.plugin_id', 'plugin_events.event',
            'plugin_events.callback', 'plugin_events.enabled', 'plugins.dir' => 'plugin_dir'
        ];
        $this->Record->select($fields)
            ->from('plugin_events')
            ->innerJoin('plugins', 'plugins.id', '=', 'plugin_events.plugin_id', false);

        // Set filters
        if (array_key_exists('plugin_id', $options)) {
            $this->Record->where('plugins.id', '=', $options['plugin_id']);
        }
        if (array_key_exists('company_id', $options)) {
            $this->Record->where('plugins.company_id', '=', $options['company_id']);
        }
        if (array_key_exists('event', $options)) {
            $this->Record->where('plugin_events.event', '=', $options['event']);
        }

        $enabled = array_key_exists('enabled', $options) ? $options['enabled'] : null;
        if ($enabled !== null) {
            $enabled = (int) $enabled;

            // Fetch enabled events if both plugin/event are enabled
            if ($enabled === 1) {
                $this->Record->where('plugins.enabled', '=', $enabled)
                    ->where('plugin_events.enabled', '=', $enabled);
            } else {
                // Fetch disabled events if plugin or event are disabled
                $this->Record->open()
                    ->where('plugins.enabled', '=', $enabled)
                    ->orWhere('plugin_events.enabled', '=', $enabled)
                    ->close();
            }
        }

        return $this->Record->fetchAll();
    }

    /**
     * Retrieves the specified event of the given plugin
     *
     * @param int $plugin_id The ID of the plugin to fetch the event under
     * @param string $event The event to fetch
     * @return mixed A stdClass object representing the plugin event, false if not such plugin event exists.
     */
    public function getEvent($plugin_id, $event)
    {
        return $this->Record->select(['plugin_id', 'event', 'callback', 'enabled'])
            ->from('plugin_events')
            ->where('plugin_id', '=', $plugin_id)
            ->where('event', '=', $event)
            ->fetch();
    }

    /**
     * Retrieves all events that are registered for a particular plugin
     *
     * @param int $plugin_id The ID of the plugin
     * @return array An array of stdClass objects representing registered events
     */
    private function getEventsByPlugin($plugin_id)
    {
        $temp = $this->Record->select()
            ->from('plugin_events')
            ->where('plugin_events.plugin_id', '=', $plugin_id)
            ->fetchAll();

        // Re-key the events by the event
        $events = [];
        foreach ($temp as $event) {
            $events[$event->event] = $event;
        }

        return $events;
    }

    /**
     * Retrieves all actians that are registered for a particular plugin
     *
     * @param int $plugin_id The ID of the plugin
     * @return array An array of stdClass objects representing registered actions
     */
    private function getActionsByPlugin($plugin_id)
    {
        $temp = $this->Actions->getAll(['plugin_id' => $plugin_id]);

        // Re-key the actions by the action and URI primary key
        $actions = [];
        foreach ($temp as $action) {
            $actions[$action->location][$action->url] = $action;
        }

        return $actions;
    }

    /**
     * Retrieves all actions that are registered for a particular action and company
     *
     * @param int $company_id The ID of the company the action is registered under
     * @param string $action The action being requested
     * @param bool/int $enabled True for only enabled plugins/actions, false for disabled, null for both
     * @param bool $translate Whether or not to translate the action names (optional, default true)
     * @return array An array of stdClass objects representing registered actions
     */
    public function getActions($company_id, $action, $enabled = null, $translate = true)
    {
        $filters = ['company_id' => $company_id, 'action' => $action];
        if ($enabled !== null) {
            $filters['enabled'] = (int) $enabled;
        }

        return array_filter(
            $this->Actions->getAll($filters, $translate),
            function ($action) {
                return !is_null($action->plugin_id);
            }
        );
    }

    /**
     * Retrieves all actions from the given plugin
     *
     * @param int $plugin_id The ID of the plugin to fetch actions under
     * @param bool $translate Whether or not to translate the action names (optional, default true)
     * @return array An array of stdClass objects representing registered actions
     */
    public function getAllActions($plugin_id, $translate = true)
    {
        return $this->Actions->getAll(['plugin_id' => $plugin_id], $translate);
    }

    /**
     * Retrieves the specified action from the given plugin
     *
     * @param int $plugin_id The ID of the plugin to fetch the action under
     * @param string $action The action to fetch
     * @param string $uri The URI of the specific record to retrieve,
     *  otherwise defaults to the first record found (optional)
     * @return mixed A stdClass object representing the plugin action, false if no such plugin action exists.
     */
    public function getAction($plugin_id, $action, $uri = null)
    {
        $filters = ['plugin_id' => $plugin_id];

        if ($uri !== null) {
            $filters['url'] = $uri;
        }

        $actions = $this->Actions->getAll($filters);
        return !empty($actions) ? $actions[0] : false;
    }

    /**
     * Retrieves all cards that are registered for a particular plugin
     *
     * @param int $plugin_id The ID of the plugin
     * @return array An array of stdClass objects representing registered cards
     */
    private function getCardsByPlugin($plugin_id)
    {
        $this->Record->select()
            ->from('plugin_cards')
            ->where('plugin_cards.plugin_id', '=', $plugin_id);

        $temp = $this->formatCards($this->Record->fetchAll(), false);

        // Re-key the actions by the action and URI primary key
        $cards = [];
        foreach ($temp as $card) {
            $action = $card->level . '.' . implode('.', $card->callback);
            $cards[$action] = $card;
        }

        return $cards;
    }

    /**
     * Retrieves all cards that are registered for a particular level and company
     *
     * @param int $company_id The ID of the company the card is registered under
     * @param string $level The card's level being requested, null for both
     * @param bool/int $enabled True for only enabled plugins/cards, false for disabled, null for both
     * @param bool $translate Whether or not to translate the card labels (optional, default true)
     * @return array An array of stdClass objects representing registered cards
     */
    public function getCards($company_id, $level = null, $enabled = null, $translate = true)
    {
        return $this->fetchCards(
            ['company_id' => $company_id, 'level' => $level, 'enabled' => $enabled, 'translate' => $translate]
        );
    }

    /**
     * Retrieves all cards from the given plugin
     *
     * @param int $plugin_id The ID of the plugin to fetch cards under
     * @param bool $translate Whether or not to translate the card labels (optional, default true)
     * @return array An array of stdClass objects representing registered cards
     */
    public function getAllCards($plugin_id, $translate = true)
    {
        return $this->fetchCards(['plugin_id' => $plugin_id, 'translate' => $translate]);
    }

    /**
     * Fetches all plugin cards
     *
     * @param array $options An array of options including:
     *
     *  - company_id The ID of the company whose cards to fetch (optional)
     *  - plugin_id The ID of the plugin whose cards to fetch (optional)
     *  - level The level of the plugin card to fetch (optional)
     *  - enabled 1, 0, or null; what enabled status of actions to fetch (optional, default null)
     * @return array An array of stdClass objects representing registered actions
     */
    private function fetchCards(array $options)
    {
        $fields = [
            'plugin_cards.id', 'plugin_cards.plugin_id', 'plugin_cards.level', 'plugin_cards.callback',
            'plugin_cards.callback_type', 'plugin_cards.text_color', 'plugin_cards.background',
            'plugin_cards.background_type', 'plugin_cards.label', 'plugin_cards.link',
            'plugin_cards.enabled', 'plugins.dir' => 'plugin_dir'
        ];
        $this->Record->select($fields)
            ->from('plugin_cards')
            ->innerJoin('plugins', 'plugins.id', '=', 'plugin_cards.plugin_id', false);

        // Set filters
        if (array_key_exists('plugin_id', $options)) {
            $this->Record->where('plugins.id', '=', $options['plugin_id']);
        }
        if (array_key_exists('company_id', $options)) {
            $this->Record->where('plugins.company_id', '=', $options['company_id']);
        }

        $level = array_key_exists('level', $options) ? $options['level'] : null;
        if ($level !== null) {
            $this->Record->where('plugin_cards.level', '=', $options['level']);
        }

        $enabled = array_key_exists('enabled', $options) ? $options['enabled'] : null;
        if ($enabled !== null) {
            $enabled = (int) $enabled;

            // Fetch enabled cards if both plugin/card are enabled
            if ($enabled === 1) {
                $this->Record->where('plugins.enabled', '=', $enabled)
                    ->where('plugin_cards.enabled', '=', $enabled);
            } else {
                // Fetch disabled cards if plugin or card are disabled
                $this->Record->open()
                        ->where('plugins.enabled', '=', $enabled)
                        ->orWhere('plugin_cards.enabled', '=', $enabled)
                    ->close();
            }
        }

        // Format the plugin cards
        $card_args = [$this->Record->fetchAll()];
        if (array_key_exists('translate', $options)) {
            $card_args[] = $options['translate'];
        }
        $cards = call_user_func_array([$this, 'formatCards'], $card_args);

        return $cards;
    }

    /**
     * Retrieves the specified card from the given plugin
     *
     * @param int $plugin_id The ID of the plugin to fetch the action under
     * @param mixed $callback The callback of the plugin card to fetch
     * @param string $level The level of the plugin card to fetch
     * @return mixed A stdClass object representing the plugin card, false if no such plugin card exists.
     */
    public function getCard($plugin_id, $callback, $level = null)
    {
        if (is_array($callback)) {
            $callback = serialize($callback);
        }

        $this->Record->select(['plugin_cards.*', 'plugins.dir' => 'plugin_dir'])
            ->from('plugin_cards')
            ->innerJoin('plugins', 'plugins.id', '=', 'plugin_cards.plugin_id', false)
            ->where('plugin_id', '=', $plugin_id)
            ->where('callback', '=', $callback);

        if ($level !== null) {
            $this->Record->where('level', '=', $level);
        }

        // Format the plugin action
        if (($plugin_card = $this->Record->fetch())) {
            return $this->formatCard($plugin_card);
        }

        return $plugin_card;
    }

    /**
     * Format the plugin cards
     *
     * @param array $cards A list of stdClass objects representing plugin cards to format
     * @param bool $translate Whether or not to translate any language definitions (optional, default true)
     * @return array The given $cards formatted
     */
    private function formatCards(array $cards, $translate = true)
    {
        foreach ($cards as $index => $card) {
            $cards[$index] = $this->formatCard($card, $translate);
        }

        return $cards;
    }

    /**
     * Formats the given plugin card
     *
     * @param stdClass $card The stdClass object representing the plugin card
     * @param bool $translate Whether or not to translate any language definitions (optional, default true)
     * @return stdClass The stdClass $card formatted
     */
    private function formatCard(stdClass $card, $translate = true)
    {
        // Unserialize the callback
        if (property_exists($card, 'callback')) {
            $card->callback = ($card->callback === null ? null : unserialize($card->callback));
        }

        // Translate the card's names
        if ($translate) {
            $card = $this->translateCard($card);
        }

        return $card;
    }

    /**
     * Translates the names of language definitions within the card
     *
     * @param stdClass $card The card whose language definitions to translate
     * @return stdClass The stdClass $card translated
     */
    private function translateCard(stdClass $card)
    {
        $dir = property_exists($card, 'plugin_dir') ? $card->plugin_dir : '';

        // Translate the card name
        if (property_exists($card, 'label')) {
            $card->label = $this->getTranslation($dir, $card->label);
        }

        return $card;
    }

    /**
     * Translates the given $values array for all matching $keys
     *
     * @param string $plugin_dir The directory name of the plugin to return results for
     * @param array $values An numerically-indexed array containing key/value pairs
     * @param array $keys An array of keys to to translate from the $values key/value paris
     * @return array The given $values are returned, with all matching value keys translated
     */
    private function translateArray($plugin_dir, array $values, array $keys)
    {
        foreach ($values as &$value) {
            foreach ($keys as $key) {
                if (isset($value[$key]) && is_scalar($value[$key])) {
                    $value[$key] = $this->getTranslation($plugin_dir, $value[$key]);
                }
            }
        }

        return $values;
    }

    /**
     * Retrieves the translation of the given term, if one is set, otherwise the term itself
     *
     * @param string $plugin_dir The directory name of the plugin to return results for
     * @param string $term The language term from the plugin to translate
     * @return string The translated term, if not empty, otherwise the given $term
     */
    private function getTranslation($plugin_dir, $term)
    {
        $name = $this->translate($plugin_dir, $term);
        if (!empty($name)) {
            $term = $name;
        }

        return $term;
    }

    /**
     * Retrieves the translated definition of the given term for the given plugin.
     * This assumes the plugin language file is the $plugin_dir concatenated with '_plugin'
     *
     * @param string $plugin_dir The directory name of the plugin to return results for
     * @param string $term The language term from the plugin to translate
     * @return string The translated term, if found
     */
    public function translate($plugin_dir, $term)
    {
        // Assume the plugin has its translations in the plugin names' "_plugin" file
        // e.g. "sample_plugin"
        Language::loadLang(
            $plugin_dir . '_plugin',
            null,
            PLUGINDIR . $plugin_dir . DS . 'language' . DS
        );

        return $this->_($term);
    }

    /**
     * Triggers the given event on all plugins registered to observe it
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event The event to process
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public function triggerEvents(EventInterface $event)
    {
        $plugin_events = $this->getEvents(Configure::get('Blesta.company_id'), $event->getName(), true);

        if ($plugin_events) {
            $this->invokePluginEvents($plugin_events, $event);
        }

        return $event;
    }

    /**
     * Invokes the plugin event on all registered plugins
     *
     * @param array $plugin_events An array of plugins that have registered events to be invoked
     * @param Blesta\Core\Util\Events\Common\EventInterface $event The event to process
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    private function invokePluginEvents(array $plugin_events, $event)
    {
        foreach ($plugin_events as $plugin_event) {
            try {
                // Load the plugin (so it can initialize the callback)
                $plugin = $this->loadPlugin($plugin_event->plugin_dir);

                // Allow the plugin to invoke instance methods
                $callback = unserialize($plugin_event->callback);
                if (is_array($callback) && isset($callback[1]) && $callback[0] == 'this') {
                    $callback[0] = $plugin;
                }

                // Invoke the callback
                call_user_func($callback, $event);

                #
                # TODO: Log this action
                #
            } catch (Exception $e) {
                #
                # TODO: Log this action failure
                #
            }
        }

        return $event;
    }

    /**
     * Updates all installed plugins with the version given
     *
     * @param string $dir The directory name of the plugin to update
     * @param string $version The version number to set for each plugin instance
     */
    private function setVersion($dir, $version)
    {
        $this->Record->where('dir', '=', $dir)->update('plugins', ['version' => $version]);
    }

    /**
     * Instantiates the given plugin and returns its instance
     *
     * @param string $dir The directory name of the plugin to load
     * @return An instance of the plugin specified
     */
    private function loadPlugin($dir)
    {
        // Load the plugin factory if not already loaded
        if (!isset($this->Plugins)) {
            Loader::loadComponents($this, ['Plugins']);
        }

        // Instantiate the plugin and return the instance
        return $this->Plugins->create($dir);
    }

    /**
     * Fetch information about the given plugin object
     *
     * @param object $plugin The plugin object to fetch info on
     * @param int $company_id The ID of the company to fetch the plugin info for
     */
    private function getPluginInfo($plugin, $company_id)
    {
        // Fetch supported interfaces
        $reflect = new ReflectionClass($plugin);
        $dir = str_replace('_plugin', '', Loader::fromCamelCase($reflect->getName()));

        $dirname = dirname($_SERVER['SCRIPT_NAME']);
        $info = [
            'dir' => $dir,
            'name' => $plugin->getName(),
            'version' => $plugin->getVersion(),
            'authors' => $plugin->getAuthors(),
            'logo' => Router::makeURI(
                ($dirname == DS ? '' : $dirname) . DS
                . str_replace(
                    ROOTWEBDIR,
                    '',
                    PLUGINDIR . $dir . DS . $plugin->getLogo()
                )
            ),
            'installed' => $this->isInstalled($dir, $company_id),
            'manageable' => file_exists(PLUGINDIR . $dir . DS . 'controllers' . DS . 'admin_manage_plugin.php'),
            'description' => $plugin->getDescription()
        ];

        unset($reflect);

        return $info;
    }

    /**
     * Returns all common rules for plugins
     *
     * @param array $vars The input vars
     * @return array Common plugin rules
     */
    private function getAddRules(array $vars)
    {
        $rules = [
            'dir' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('PluginManager.!error.dir.empty')
                ],
                'length' => [
                    'rule' => ['maxLength', 64],
                    'message' => $this->_('PluginManager.!error.dir.length')
                ]
            ],
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('PluginManager.!error.company_id.exists')
                ]
            ],
            'name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('PluginManager.!error.name.empty')
                ]
            ],
            'version' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('PluginManager.!error.version.empty')
                ],
                'length' => [
                    'rule' => ['maxLength', 16],
                    'message' => $this->_('PluginManager.!error.version.length')
                ]
            ]
        ];

        return $rules;
    }
}
