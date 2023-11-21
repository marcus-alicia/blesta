<?php
/**
 * Upgrades to version 3.5.0-b4
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2015, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_5_0B4 extends UpgradeUtil
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
     * @retrun array A numerically indexed array of tasks to execute for the upgrade process
     */
    public function tasks()
    {
        return [
            'addClientsServicePermissions'
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
     * Adds staff permissions for the new ClientsService controller
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    public function addClientsServicePermissions($undo = false)
    {
        Loader::loadModels($this, ['Permissions', 'StaffGroups']);
        Loader::loadComponents($this, ['Acl']);

        if ($undo) {
            // Nothing to do
        } else {
            $staff_groups = $this->StaffGroups->getAll();
            // Determine comparable permission access
            $staff_group_access = [];
            foreach ($staff_groups as $staff_group) {
                $staff_group_access[$staff_group->id] = [
                    'admin_clients_service' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_clients',
                        'editservice',
                        'staff',
                        $staff_group->company_id
                    )
                ];
            }

            $group = $this->Permissions->getGroupByAlias('admin_clients');
            if ($group) {
                $permissions = [
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_clients_service',
                        'alias' => 'admin_clients_service',
                        'action' => '*'
                    ]
                ];
                foreach ($permissions as $vars) {
                    $this->Permissions->add($vars);

                    foreach ($staff_groups as $staff_group) {
                        // If staff group has access to similar item, grant access to this item
                        if ($staff_group_access[$staff_group->id][$vars['alias']]) {
                            $this->Acl->allow('staff_group_' . $staff_group->id, $vars['alias'], $vars['action']);
                        }
                    }
                }
            }

            // Clear cache for each staff group
            foreach ($staff_groups as $staff_group) {
                Cache::clearCache('nav_staff_group_' . $staff_group->id, $staff_group->company_id . DS . 'nav' . DS);
            }
        }
    }
}
