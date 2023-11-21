<?php
/**
 * Upgrades to version 3.2.1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_2_1 extends UpgradeUtil
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
            'updatePermissions',
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
     * Update permissions
     *
     * @param bool $undo True to undo the change, or false to perform the change
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
                $clients_pay = $this->Permissions->authorized(
                    'staff_group_' . $staff_group->id,
                    'admin_clients',
                    'pay',
                    'staff',
                    $staff_group->company_id
                );

                $staff_group_access[$staff_group->id] = [
                    'admin_clients::recordpayment' => $clients_pay,
                    'admin_clients::makepayment' => $clients_pay
                ];
            }

            $group = $this->Permissions->getGroupByAlias('admin_clients');
            if ($group) {
                $permissions = [
                    // AdminClients::recordPayment
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_clients_recordpayment',
                        'alias' => 'admin_clients',
                        'action' => 'recordpayment'
                    ],
                    // AdminClients::makePayment
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_clients_makepayment',
                        'alias' => 'admin_clients',
                        'action' => 'makepayment'
                    ]
                ];
                foreach ($permissions as $vars) {
                    // If the permission exists skip it
                    if ($this->Permissions->getByAlias($vars['alias'], null, $vars['action'])) {
                        continue;
                    }

                    $this->Permissions->add($vars);

                    foreach ($staff_groups as $staff_group) {
                        // If staff group has access to similar item, grant access to this item
                        $access = false;
                        if (isset($staff_group_access[$staff_group->id][$vars['alias'] . '::' . $vars['action']])) {
                            $access = $staff_group_access[$staff_group->id][$vars['alias'] . '::' . $vars['action']];
                        } elseif (isset($staff_group_access[$staff_group->id][$vars['alias'] . '::*'])) {
                            $access = $staff_group_access[$staff_group->id][$vars['alias'] . '::*'];
                        }

                        if ($access) {
                            $this->Acl->allow('staff_group_' . $staff_group->id, $vars['alias'], $vars['action']);
                        }
                    }
                }
            }

            // Remove unused permissions
            $remove_permissions = [
                [
                    'alias' => 'admin_clients',
                    'action' => 'pay'
                ],
                [
                    'alias' => 'admin_clients',
                    'action' => 'payinvoice'
                ]
            ];

            foreach ($remove_permissions as $vars) {
                if ($permission = $this->Permissions->getByAlias($vars['alias'], null, $vars['action'])) {
                    $this->Permissions->delete($permission->id);
                }
            }
        }
    }
}
