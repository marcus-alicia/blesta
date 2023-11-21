<?php
/**
 * Upgrades to version 4.9.1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_9_1 extends UpgradeUtil
{
    /**
     * @var array An array of all tasks completed
     */
    private $tasks = [];

    /**
     * Setup
     */
    public function __construct()
    {
        Configure::load('blesta');
        Loader::loadComponents($this, ['Record']);
    }

    /**
     * Returns a numerically indexed array of tasks to execute for the upgrade process
     *
     * @return array A numerically indexed array of tasks to execute for the upgrade process
     */
    public function tasks()
    {
        return [
            'addMissingPluginPermissions'
        ];
    }

    /**
     * Processes the given task
     *
     * @param string $task The task to process
     */
    public function process($task)
    {
        $tasks = $this->tasks();

        // Ensure task exists
        if (!in_array($task, $tasks)) {
            return;
        }

        $this->tasks[] = $task;
        $this->{$task}();
    }

    /**
     * Rolls back all tasks completed for the upgrade process
     */
    public function rollback()
    {
        // Undo all tasks
        while (($task = array_pop($this->tasks))) {
            $this->{$task}(true);
        }
    }

    /**
     * Adds plugin permissions that exist for one instance of a plugin, but not the others
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addMissingPluginPermissions($undo = false)
    {
        // Get all permission groups that are assigned to a plugin installation
        $plugin_permission_groups = $this->Record->select(['permission_groups.*', 'plugins.dir' => 'plugin_dir'])->
            from('permission_groups')->
            innerJoin('plugins', 'plugins.id', '=', 'permission_groups.plugin_id', false)->
            fetchAll();
        foreach ($plugin_permission_groups as $permission_group) {
            // Get all plugin installations that share a dir with the installation assigned to this group
            // and are not already assigned to an identical group
            $groupless_plugins = $this->Record->select('plugins.id')->
                from('plugins')->
                on('permission_groups.name', '=', $permission_group->name)->
                on('permission_groups.level', '=', $permission_group->level)->
                on('permission_groups.alias', '=', $permission_group->alias)->
                leftJoin('permission_groups', 'permission_groups.plugin_id', '=', 'plugins.id', false)->
                where('plugins.dir', '=', $permission_group->plugin_dir)->
                where('permission_groups.id', '=', null)->
                fetchAll();

            // Add new permission groups for each plugin that is missing this group
            foreach ($groupless_plugins as $groupless_plugin) {
                $vars = [
                    'name' => $permission_group->name,
                    'level' => $permission_group->level,
                    'alias' => $permission_group->alias,
                    'plugin_id' => $groupless_plugin->id
                ];
                $this->Record->insert('permission_groups', $vars);
            }
        }

        // Get all permissions that are assigned to a plugin installation
        $plugin_permissions = $this->Record->select([
                'permissions.*',
                'permission_groups.name' => 'group_name',
                'permission_groups.level' => 'group_level',
                'permission_groups.alias' => 'group_alias',
                'permission_groups.plugin_id' => 'group_plugin_id',
                'plugins.dir' => 'plugin_dir'
            ])->
            from('permissions')->
            innerJoin('plugins', 'plugins.id', '=', 'permissions.plugin_id', false)->
            innerJoin('permission_groups', 'permission_groups.id', '=', 'permissions.group_id', false)->
            fetchAll();
        foreach ($plugin_permissions as $permission) {
            // Get all plugin installations that share a dir with the installation assigned to this permission
            // and are not already assigned to an identical permission
            $permissionless_plugins = $this->Record->select('plugins.id')->
                from('plugins')->
                on('permissions.name', '=', $permission->name)->
                on('permissions.alias', '=', $permission->alias)->
                on('permissions.action', '=', $permission->action)->
                leftJoin('permissions', 'permissions.plugin_id', '=', 'plugins.id', false)->
                where('plugins.dir', '=', $permission->plugin_dir)->
                where('permissions.id', '=', null)->
                fetchAll();

            // Add new permission groups for each plugin that is missing this permission
            foreach ($permissionless_plugins as $permissionless_plugin) {
                // Get the appropriate group to assign this permission to
                $group = $this->Record->select('permission_groups.id')->
                    from('permission_groups')->
                    where('permission_groups.name', '=', $permission->group_name)->
                    where('permission_groups.level', '=', $permission->group_level)->
                    where('permission_groups.alias', '=', $permission->group_alias)->
                    where(
                        'permission_groups.plugin_id',
                        '=',
                        $permission->group_plugin_id ? $permissionless_plugin->id : null
                    )->
                    fetch();

                if ($group) {
                    $vars = [
                        'group_id' => $group->id,
                        'name' => $permission->name,
                        'alias' => $permission->alias,
                        'action' => $permission->action,
                        'plugin_id' => $permissionless_plugin->id
                    ];
                    $this->Record->insert('permissions', $vars);
                }
            }
        }
    }
}
