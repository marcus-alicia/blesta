<?php
/**
 * Upgrades to version 5.5.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade5_5_0B1 extends UpgradeUtil
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
            'addPackagesOverridePriceColumn',
            'addSettings',
            'addTransactionsMessage',
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
     * Adds a "override_price" column to the packages table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPackagesOverridePriceColumn($undo = false)
    {
        if ($undo) {
            $sql = 'ALTER TABLE `packages` DROP `override_price`';
            $this->Record->query($sql);
        } else {
            $sql = 'ALTER TABLE `packages` ADD `override_price` TINYINT(1) NOT NULL DEFAULT 0 AFTER `upgrades_use_renewal`';
            $this->Record->query($sql);
        }
    }

    /**
     * Adds the new company/client group setting for shown contact fields
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function addSettings($undo = false)
    {
        // Add the shown contact field setting
        $setting = 'shown_contact_fields';

        if ($undo) {
            // Remove the new setting
            $this->Record->from('client_group_settings')->where('key', '=', $setting)->delete();
            $this->Record->from('company_settings')->where('key', '=', $setting)->delete();
        } else {
            $settings = [
                'first_name', 'last_name', 'company', 'title', 'address1',
                'address2', 'city', 'country', 'state', 'zip', 'email'
            ];
            $value = base64_encode(serialize($settings));

            // Add to company settings
            $companies = $this->Record->select()->from('companies')->getStatement();
            foreach ($companies as $company) {
                $this->Record->insert(
                    'company_settings',
                    ['key' => $setting, 'value' => $value, 'company_id' => $company->id]
                );
            }
            
            $client_groups = $this->Record->select()->from('client_groups')->getStatement();
            foreach ($client_groups as $client_group) {
                $this->Record->insert(
                    'client_group_settings',
                    ['key' => $setting, 'value' => $value, 'client_group_id' => $client_group->id]
                );
            }
        }
    }

    /**
     * Adds a message column to the transactions table
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function addTransactionsMessage($undo = false)
    {
        if ($undo) {
            $this->Record->query(
                'ALTER TABLE `transactions` DROP `message`;'
            );
        } else {
            $this->Record->query(
                'ALTER TABLE `transactions` ADD `message` VARCHAR(255) NULL DEFAULT NULL AFTER `reference_id`;'
            );
        }
    }
}
