<?php
/**
 * Upgrades to version 4.0.0-b2
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2016, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_0_0B2 extends UpgradeUtil
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
            'addVoidInvoiceSetting'
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
     * Adds a new setting to handle voiding invoices associated with a service that is canceled
     *
     * @param bool $undo Whether to add or undo the change
     */
    public function addVoidInvoiceSetting($undo = false)
    {
        $setting = 'void_invoice_canceled_service';

        if ($undo) {
            $this->Record->from('company_settings')
                ->where('key', '=', $setting)
                ->delete();
            $this->Record->from('client_group_settings')
                ->where('key', '=', $setting)
                ->delete();
        } else {
            // Determine what value to set for the setting by assuming a system
            // with no clients is a new installation
            $matches = $this->Record->select()->from('clients')->numResults();
            $value = ($matches === 0 ? 'true' : 'false');

            $companies = $this->Record->select()->from('companies')->fetchAll();
            foreach ($companies as $company) {
                $vars = [
                    'key' => $setting,
                    'company_id' => $company->id,
                    'value' => $value,
                    'encrypted' => 0,
                    'inherit' => 1
                ];
                $this->Record->insert('company_settings', $vars);
            }

            // Also add the setting to any client groups that inherit settings
            $client_groups = $this->Record->select(['client_groups.id'])
                ->from('client_groups')
                ->innerJoin(
                    'client_group_settings',
                    'client_group_settings.client_group_id',
                    '=',
                    'client_groups.id',
                    false
                )
                ->group(['client_groups.id'])
                ->fetchAll();
            foreach ($client_groups as $client_group) {
                $vars = [
                    'key' => $setting,
                    'client_group_id' => $client_group->id,
                    'value' => $value,
                    'encrypted' => 0
                ];
                $this->Record->insert('client_group_settings', $vars);
            }
        }
    }
}
