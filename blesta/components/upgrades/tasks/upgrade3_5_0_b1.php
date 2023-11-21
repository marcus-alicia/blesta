<?php
/**
 * Upgrades to version 3.5.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_5_0B1 extends UpgradeUtil
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
            'addPackageOptionPermissions',
            'addInvoiceDateDueSettings',
            'addCouponConfigOptionSetting',
            'addAdminReportsPermissions'
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
     * Adds permission to package options
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPackageOptionPermissions($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `package_options` DROP `editable`;');
            $this->Record->query('ALTER TABLE `package_options` DROP `addable`;');
        } else {
            $this->Record->query("ALTER TABLE `package_options`
                ADD `addable` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `type`;");
            $this->Record->query("ALTER TABLE `package_options`
                ADD `editable` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `addable`;");
        }
    }

    /**
     * Adds new invoice display company settings
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addInvoiceDateDueSettings($undo = false)
    {
        if ($undo) {
            $this->Record->query("DELETE FROM `company_settings` WHERE `key`='inv_display_due_date_draft';");
            $this->Record->query("DELETE FROM `company_settings` WHERE `key`='inv_display_due_date_proforma';");
            $this->Record->query("DELETE FROM `company_settings` WHERE `key`='inv_display_due_date_inv';");
        } else {
            $companies = $this->Record->select()->from('companies')->fetchAll();
            foreach ($companies as $company) {
                $this->Record->query(
                    "INSERT INTO `company_settings` (`key`, `company_id`, `value`)
                        VALUES ('inv_display_due_date_draft', ?, 'true');",
                    $company->id
                );
                $this->Record->query(
                    "INSERT INTO `company_settings` (`key`, `company_id`, `value`)
                        VALUES ('inv_display_due_date_proforma', ?, 'true');",
                    $company->id
                );
                $this->Record->query(
                    "INSERT INTO `company_settings` (`key`, `company_id`, `value`)
                        VALUES ('inv_display_due_date_inv', ?, 'true');",
                    $company->id
                );
            }
        }
    }

    /**
     * Adds a new coupon option to identify whether or not the coupon applies to package options in addition to
     * the package itself
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addCouponConfigOptionSetting($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `coupons` DROP `apply_package_options`;');
        } else {
            $this->Record->query("ALTER TABLE `coupons`
                ADD `apply_package_options` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' ;");
        }
    }

    /**
     * Add permissions for the admin reports controller
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addAdminReportsPermissions($undo = false)
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
                    'admin_reports' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_billing',
                        'reports',
                        'staff',
                        $staff_group->company_id
                    )
                ];
            }

            $group = $this->Permissions->getGroupByAlias('admin_billing');
            if ($group) {
                $permissions = [
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_reports',
                        'alias' => 'admin_reports',
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

            // Remove "admin_billing/reports"
            $perm = $this->Permissions->getByAlias('admin_billing', null, 'reports');
            if ($perm) {
                $this->Permissions->delete($perm->id);
            }

            // Clear cache for each staff group
            foreach ($staff_groups as $staff_group) {
                Cache::clearCache('nav_staff_group_' . $staff_group->id, $staff_group->company_id . DS . 'nav' . DS);
            }
        }
    }
}
