<?php
/**
 * Upgrades to version 3.1.1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_1_1 extends UpgradeUtil
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
            'updatePermissions'
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
     * Add new permissions
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function updatePermissions($undo = false)
    {
        Loader::loadModels($this, ['Permissions', 'StaffGroups']);
        Loader::loadComponents($this, ['Acl']);

        if ($undo) {
            // Nothing to undo
        } else {
            $staff_groups = $this->StaffGroups->getAll();
            // Determine comparable permission access
            $staff_group_access = [];
            foreach ($staff_groups as $staff_group) {
                $staff_group_access[$staff_group->id] = [
                    'admin_system_staff' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_system_staff',
                        'manage',
                        'staff',
                        $staff_group->company_id
                    )
                ];
            }

            $group = $this->Permissions->getGroupByAlias('admin_settings');
            if ($group) {
                $permissions = [
                    // Add staff
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_system_staff_add',
                        'alias' => 'admin_system_staff',
                        'action' => 'add'
                    ],
                    // Edit staff
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_system_staff_edit',
                        'alias' => 'admin_system_staff',
                        'action' => 'edit'
                    ],
                    // Change staff status
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_system_staff_status',
                        'alias' => 'admin_system_staff',
                        'action' => 'status'
                    ],
                    // Add group
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_system_staff_addgroup',
                        'alias' => 'admin_system_staff',
                        'action' => 'addgroup'
                    ],
                    // Edit group
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_system_staff_editgroup',
                        'alias' => 'admin_system_staff',
                        'action' => 'editgroup'
                    ],
                    // Delete group
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_system_staff_deletegroup',
                        'alias' => 'admin_system_staff',
                        'action' => 'deletegroup'
                    ],
                ];
                foreach ($permissions as $vars) {
                    // If the permission exists (see 3.0.9) skip it
                    if ($this->Permissions->getByAlias($vars['alias'], null, $vars['action'])) {
                        continue;
                    }

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
