<?php
/**
 * Upgrades to version 5.6.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade5_6_0B1 extends UpgradeUtil
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
            'recreateUkVatField',
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
     * Makes sure the "tax_intra_eu_uk_vat" setting exists across all companies
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function recreateUkVatField($undo = false)
    {
        Loader::loadModels($this, ['Companies', 'ClientGroups']);

        if ($undo) {
            // Nothing to do
        } else {
            $setting = 'tax_intra_eu_uk_vat';

            // Update company settings
            $companies = $this->Record->select()->from('companies')->getStatement();
            foreach ($companies as $company) {
                $company_setting = $this->Companies->getSetting($company->id, $setting);

                if (empty($company_setting->value)) {
                    $this->Companies->setSetting($company->id, $setting, 'false');
                }
            }

            // Update client group settings
            $client_groups = $this->Record->select()->from('client_groups')->getStatement();
            foreach ($client_groups as $client_group) {
                $company_setting = $this->ClientGroups->getSetting($client_group->id, $setting);

                if (empty($company_setting->value)) {
                    $this->ClientGroups->setSetting($client_group->id, $setting, 'false');
                }
            }
        }
    }
}
