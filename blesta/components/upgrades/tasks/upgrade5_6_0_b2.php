<?php
/**
 * Upgrades to version 5.6.0-b2
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade5_6_0B2 extends UpgradeUtil
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
            'addKeyToLogCron',
            'removeMatchingServiceInvoices',
            'addAttemptsColumns',
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
     * Adds the "key" column to the "log_cron" table
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function addKeyToLogCron($undo = false)
    {
        if ($undo) {
            $this->Record->query("ALTER TABLE `log_cron` DROP `key`;");
        } else {
            $this->Record->query("ALTER TABLE `log_cron` ADD `key` VARCHAR( 64 ) NULL DEFAULT NULL;");
        }
    }

    /**
     * Removes the entries from the "service_invoices" table matching the following criteria:
     *
     *  - The linked invoices.date_closed < log_cron.start_date for the latest run of process_renewing_services for
     *     each company
     *  - The linked services.date_last_renewed === null
     *  - The linked invoices.status === 'void'
     * @param bool $undo Whether to undo the upgrade
     */
    private function removeMatchingServiceInvoices($undo = false)
    {
        $companies = $this->Record->select()->from('companies')->fetchAll();

        if ($undo) {
            // Nothing to do
        } else {
            foreach ($companies as $company) {
                // Fetch the cron task run ID
                $latest_run = $this->Record->select(['log_cron.start_date', 'log_cron.end_date'])->from('log_cron')->
                    innerJoin('cron_task_runs', 'cron_task_runs.id', '=', 'log_cron.run_id', false)->
                    innerJoin('cron_tasks', 'cron_tasks.id', '=', 'cron_task_runs.task_id', false)->
                    where('cron_task_runs.company_id', '=', $company->id)->
                    where('cron_tasks.key', '=', 'process_renewing_services')->
                    order(['log_cron.start_date' => 'DESC'])->
                    limit(1)->
                    fetch();

                // The linked invoices.date_closed < log_cron.start_date for the latest run of process_renewing_services
                if (!empty($latest_run->start_date)) {
                    $this->Record->from('service_invoices')->
                        innerJoin('invoices', 'invoices.id', '=', 'service_invoices.invoice_id', false)->
                        innerJoin('services', 'services.id', '=', 'service_invoices.service_id', false)->
                        on('package_groups.company_id', '=', $company->id)->
                        innerJoin('package_groups', 'package_groups.id', '=', 'services.package_group_id', false)->
                        where('invoices.date_closed', '<', $latest_run->start_date)->
                        delete(['service_invoices.*']);
                }
            }

            // The linked services.date_last_renewed === null
            $this->Record->from('service_invoices')->
                innerJoin('services', 'services.id', '=', 'service_invoices.service_id', false)->
                where('services.date_last_renewed', '=', null)->
                delete(['service_invoices.*']);

            // The linked invoices.status = 'void'
            $this->Record->from('service_invoices')->
                innerJoin('invoices', 'invoices.id', '=', 'service_invoices.invoice_id', false)->
                where('invoices.status', '=', 'void')->
                delete(['service_invoices.*']);
        }
    }

    /**
     * Adds the "failed_attempts" and "maximum_attempts" columns to the "services" table
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function addAttemptsColumns($undo = false)
    {
        if ($undo) {
            $this->Record->query(
                "ALTER TABLE `service_invoices` DROP `failed_attempts`;
				ALTER TABLE `service_invoices` DROP `maximum_attempts`;"
            );
            $this->Record->query("DELETE FROM `company_settings` WHERE `key`='service_renewal_attempts';");
        } else {
            $this->Record->query(
                "ALTER TABLE `service_invoices` ADD `failed_attempts` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';
				ALTER TABLE `service_invoices` ADD `maximum_attempts` INT( 10 ) UNSIGNED NOT NULL DEFAULT '1';"
            );

            $companies = $this->Record->select()->from('companies')->fetchAll();
            foreach ($companies as $company) {
                $this->Record->query(
                    "INSERT INTO `company_settings` (`key`, `company_id`, `value`)
                        VALUES ('service_renewal_attempts', ?, '24');",
                    $company->id
                );
            }
        }
    }
}
