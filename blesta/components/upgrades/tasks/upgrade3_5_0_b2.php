<?php
/**
 * Upgrades to version 3.5.0-b2
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_5_0B2 extends UpgradeUtil
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
            'addReports',
            'addPackageGroupUpgradeSetting',
            'addAdminReportsCustomizePermissions',
            'addServiceUpgrades'
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
     * Adds custom reports
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addReports($undo = false)
    {
        if ($undo) {
            $this->Record->drop('reports');
            $this->Record->drop('report_fields');
        } else {
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('name', ['type' => 'varchar', 'size' => 255])->
                setField('query', ['type' => 'mediumtext'])->
                setField('date_created', ['type' => 'datetime'])->
                setKey(['company_id', 'date_created'], 'index')->
                setKey(['id'], 'primary')->
                create('reports', true);

            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('report_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('name', ['type' => 'varchar', 'size' => 128])->
                setField('label', ['type' => 'varchar', 'size' => 255])->
                setField('type', ['type' => 'enum', 'size' => "'text','date','select'"])->
                setField('values', ['type' => 'text', 'default' => null, 'is_null' => true])->
                setField('regex', ['type' => 'text', 'default' => null, 'is_null' => true])->
                setKey(['report_id'], 'index')->
                setKey(['id'], 'primary')->
                create('report_fields', true);
        }
    }

    /**
     * Adds a field to `package_groups` to indicate whether packages within a group may be upgraded/downgraded
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPackageGroupUpgradeSetting($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `package_groups` DROP `allow_upgrades`;');
        } else {
            $this->Record->query("ALTER TABLE `package_groups`
                ADD `allow_upgrades` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' ;");
        }
    }

    /**
     * Add permission to AdminReportsCustomize
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addAdminReportsCustomizePermissions($undo = false)
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
                    'admin_reports_customize' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_system_backup',
                        '*',
                        'staff',
                        $staff_group->company_id
                    )
                ];
            }

            $group = $this->Permissions->getGroupByAlias('admin_billing');

            $permissions = [
                [
                    'group_id' => $group->id,
                    'name' => 'StaffGroups.permissions.admin_reports_customize',
                    'alias' => 'admin_reports_customize',
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
    }

    /**
     * Adds a table for queued service upgrades
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addServiceUpgrades($undo = false)
    {
        Loader::loadModels($this, ['Companies']);

        if ($undo) {
            // Remove the table
            $this->Record->drop('service_changes');

            // Remove the cron task and cron task runs
            $this->Record->from('cron_tasks')->
                leftJoin('cron_task_runs', 'cron_task_runs.task_id', '=', 'cron_tasks.id', false)->
                where('cron_tasks.key', '=', 'process_service_changes')->
                delete(['cron_tasks.*', 'cron_task_runs.*']);

            // Remove the settings
            $this->Record->query("DELETE FROM `company_settings` WHERE `key`='process_paid_service_changes';");
            $this->Record->query("DELETE FROM `company_settings` WHERE `key`='cancel_service_changes_days';");
        } else {
            // Add the service_changes table
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('service_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('invoice_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField(
                    'status',
                    ['type' => 'enum', 'size' => "'pending','completed','canceled','error'", 'default' => 'pending']
                )->
                setField('data', ['type' => 'mediumtext'])->
                setField('date_added', ['type' => 'datetime'])->
                setField('date_status', ['type' => 'datetime'])->
                setKey(['service_id'], 'index')->
                setKey(['invoice_id'], 'unique')->
                setKey(['status', 'date_status'], 'index')->
                setKey(['id'], 'primary')->
                create('service_changes', true);

            // Add the cron task
            $vars = [
                'key' => 'process_service_changes',
                'plugin_dir' => null,
                'name' => 'CronTasks.crontask.name.process_service_changes',
                'description' => 'CronTasks.crontask.description.process_service_changes',
                'is_lang' => 1,
                'type' => 'interval'
            ];
            $this->Record->insert('cron_tasks', $vars);
            $task_id = $this->Record->lastInsertId();

            // Fetch all companies
            $companies = $this->Companies->getAll();

            foreach ($companies as $company) {
                // Add cron task run for the company
                if ($task_id) {
                    $vars = [
                        'task_id' => $task_id,
                        'company_id' => $company->id,
                        'time' => null,
                        'interval' => '5',
                        'enabled' => 1,
                        'date_enabled' => $this->Companies->dateToUtc(date('c'))
                    ];

                    $this->Record->insert('cron_task_runs', $vars);
                }

                // Add new settings for service changes on the company
                $this->Record->query(
                    "INSERT INTO `company_settings` (`key`, `company_id`, `value`)
                        VALUES ('process_paid_service_changes', ?, 'false');",
                    $company->id
                );
                $this->Record->query(
                    "INSERT INTO `company_settings` (`key`, `company_id`, `value`)
                        VALUES ('cancel_service_changes_days', ?, '7');",
                    $company->id
                );
            }
        }
    }
}
