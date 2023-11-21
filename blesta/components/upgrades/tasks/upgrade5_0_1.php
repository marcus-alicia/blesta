<?php
/**
 * Upgrades to version 5.0.1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade5_0_1 extends UpgradeUtil
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
            'addToolsUtilitiesAction',
            'addToolsUtilitiesPermissions',
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
     * Add the utilities/tools/ action and navigation items
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addToolsUtilitiesAction($undo = false)
    {
        if ($undo) {
            $this->Record->from('action')->
                innerJoin('navigation_items', 'navigation_items.action_id', '=', 'actions.id', false)->
                where('actions.url', '=', 'tools/utilities/')->
                delete(['actions.*', 'navigation_items.*']);
        } else {
            $companies = $this->Record->select()->from('companies')->fetchAll();
            foreach ($companies as $company) {
                $action_params = [
                    'location' => 'nav_staff',
                    'url' => 'tools/utilities/',
                    'name' => 'Navigation.getprimary.nav_tools_utilities',
                    'company_id' => $company->id,
                    'editable' => 0,
                    'enabled' => 1,
                ];
                $this->Record->insert('actions', $action_params);

                $action_id = $this->Record->lastInsertId();
                $tools_nav_item = $this->Record->select('navigation_items.id')->
                    from('actions')->
                    innerJoin('navigation_items', 'navigation_items.action_id', '=', 'actions.id', false)->
                    where('actions.url', '=', 'tools/')->
                    where('actions.company_id', '=', $company->id)->
                    fetch();
                if ($tools_nav_item) {
                    $last_nav_item = $this->Record->select('navigation_items.order')->
                        from('navigation_items')->
                        order(['order' => 'desc'])->
                        fetch();
                    $this->Record->insert(
                        'navigation_items',
                        ['action_id' => $action_id, 'order' => $last_nav_item->order, 'parent_id' => $tools_nav_item->id]
                    );
                }
            }

        }
    }

    /**
     * Adds a permission for tools utilities page
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addToolsUtilitiesPermissions($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            // Get the admin tools permission group
            Loader::loadModels($this, ['Permissions']);
            Loader::loadComponents($this, ['Acl']);
            $group = $this->Permissions->getGroupByAlias('admin_tools');

            // Determine comparable permission access
            $staff_groups = $this->Record->select()->from('staff_groups')->fetchAll();
            $staff_group_access = [];
            foreach ($staff_groups as $staff_group) {
                $staff_group_access[$staff_group->id] = $this->Permissions->authorized(
                    'staff_group_' . $staff_group->id,
                    'admin_tools',
                    'logs',
                    'staff',
                    $staff_group->company_id
                );
            }

            // Add the new tools utilities page permission
            if ($group) {
                $this->Permissions->add([
                    'group_id' => $group->id,
                    'name' => 'StaffGroups.permissions.admin_tools_utilities',
                    'alias' => 'admin_tools',
                    'action' => 'utilities'
                ]);

                foreach ($staff_groups as $staff_group) {
                    if ($staff_group_access[$staff_group->id]) {
                        $this->Acl->allow('staff_group_' . $staff_group->id, 'admin_tools', 'utilities');
                    }
                }
            }
        }
    }
}
