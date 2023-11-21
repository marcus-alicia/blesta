<?php
/**
 * Upgrades to version 3.1.2
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_1_2 extends UpgradeUtil
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
                $coupons = $this->Permissions->authorized(
                    'staff_group_' . $staff_group->id,
                    'admin_company_billing',
                    'coupons',
                    'staff',
                    $staff_group->company_id
                );
                $signatures = $this->Permissions->authorized(
                    'staff_group_' . $staff_group->id,
                    'admin_company_emails',
                    'signatures',
                    'staff',
                    $staff_group->company_id
                );
                $themes = $this->Permissions->authorized(
                    'staff_group_' . $staff_group->id,
                    'admin_company_general',
                    'themes',
                    'staff',
                    $staff_group->company_id
                );

                $staff_group_access[$staff_group->id] = [
                    'admin_company_automation::*' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_company_general',
                        'automation',
                        'staff',
                        $staff_group->company_id
                    ),
                    'admin_company_billing::addcoupon' => $coupons,
                    'admin_company_billing::editcoupon' => $coupons,
                    'admin_company_billing::deletecoupon' => $coupons,
                    'admin_company_currencies::*' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_company_currencies',
                        'setup',
                        'staff',
                        $staff_group->company_id
                    ),
                    'admin_company_emails::edittemplate' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_company_emails',
                        'templates',
                        'staff',
                        $staff_group->company_id
                    ),
                    'admin_company_emails::addsignature' => $signatures,
                    'admin_company_emails::editsignature' => $signatures,
                    'admin_company_emails::deletesignature' => $signatures,
                    'admin_company_general::addtheme' => $themes,
                    'admin_company_general::edittheme' => $themes,
                    'admin_company_general::deletetheme' => $themes,
                    'admin_company_taxes::*' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_company_taxes',
                        'rules',
                        'staff',
                        $staff_group->company_id
                    ),
                    'admin_system_backup::*' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_system_backup',
                        'download',
                        'staff',
                        $staff_group->company_id
                    ),
                ];
            }

            $group = $this->Permissions->getGroupByAlias('admin_settings');
            if ($group) {
                $permissions = [
                    // Automation
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_automation',
                        'alias' => 'admin_company_automation',
                        'action' => '*'
                    ],
                    // Add Coupon
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_billing_addcoupon',
                        'alias' => 'admin_company_billing',
                        'action' => 'addcoupon'
                    ],
                    // Edit Coupon
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_billing_editcoupon',
                        'alias' => 'admin_company_billing',
                        'action' => 'editcoupon'
                    ],
                    // Delete Coupon
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_billing_deletecoupon',
                        'alias' => 'admin_company_billing',
                        'action' => 'deletecoupon'
                    ],
                    // Currencies
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_currencies',
                        'alias' => 'admin_company_currencies',
                        'action' => '*'
                    ],
                    // Edit Email Template
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_emails_edittemplate',
                        'alias' => 'admin_company_emails',
                        'action' => 'edittemplate'
                    ],
                    // Add signature
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_emails_addsignature',
                        'alias' => 'admin_company_emails',
                        'action' => 'addsignature'
                    ],
                    // Edit signature
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_emails_editsignature',
                        'alias' => 'admin_company_emails',
                        'action' => 'editsignature'
                    ],
                    // Delete signature
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_emails_deletesignature',
                        'alias' => 'admin_company_emails',
                        'action' => 'deletesignature'
                    ],
                    // Add theme
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_general_addtheme',
                        'alias' => 'admin_company_general',
                        'action' => 'addtheme'
                    ],
                    // Edit theme
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_general_edittheme',
                        'alias' => 'admin_company_general',
                        'action' => 'edittheme'
                    ],
                    // Delete theme
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_general_deletetheme',
                        'alias' => 'admin_company_general',
                        'action' => 'deletetheme'
                    ],
                    // Taxes
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_taxes',
                        'alias' => 'admin_company_taxes',
                        'action' => '*'
                    ],
                    // Backup
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_system_backup',
                        'alias' => 'admin_system_backup',
                        'action' => '*'
                    ],
                ];
                foreach ($permissions as $vars) {
                    // If the permission exists (see 3.0.10) skip it
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
                    'alias' => 'admin_company_general',
                    'action' => 'automation'
                ],
                [
                    'alias' => 'admin_company_currencies',
                    'action' => 'active'
                ],
                [
                    'alias' => 'admin_company_currencies',
                    'action' => 'setup'
                ],
                [
                    'alias' => 'admin_company_taxes',
                    'action' => 'basic'
                ],
                [
                    'alias' => 'admin_company_taxes',
                    'action' => 'rules'
                ],
                [
                    'alias' => 'admin_system_backup',
                    'action' => 'amazon'
                ],
                [
                    'alias' => 'admin_system_backup',
                    'action' => 'download'
                ],
                [
                    'alias' => 'admin_system_backup',
                    'action' => 'ftp'
                ],
                [
                    'alias' => 'admin_system_backup',
                    'action' => 'upload'
                ],
            ];

            foreach ($remove_permissions as $vars) {
                if ($permission = $this->Permissions->getByAlias($vars['alias'], null, $vars['action'])) {
                    $this->Permissions->delete($permission->id);
                }
            }

            // Clear cache for each staff group
            foreach ($staff_groups as $staff_group) {
                Cache::clearCache('nav_staff_group_' . $staff_group->id, $staff_group->company_id . DS . 'nav' . DS);
            }
        }
    }
}
