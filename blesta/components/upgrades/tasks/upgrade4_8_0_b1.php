<?php
/**
 * Upgrades to version 4.8.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_8_0B1 extends UpgradeUtil
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
        Configure::load('blesta');
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
            'addCurrencyFormats',
            'addCompanySettings',
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
     * Creates new company settings
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addCompanySettings($undo = false)
    {
        // Add a setting for whether to display the date range on config option invoice line items
        $setting = 'inv_lines_verbose_option_dates';

        if ($undo) {
            // Remove the new setting
            $this->Record->from('client_group_settings')->where('key', '=', $setting)->delete();
            $this->Record->from('company_settings')->where('key', '=', $setting)->delete();
        } else {
            // Add to company settings
            $companies = $this->Record->select()->from('companies')->getStatement();
            foreach ($companies as $company) {
                $this->Record->insert(
                    'company_settings',
                    ['key' => $setting, 'value' => 'false', 'company_id' => $company->id]
                );
            }
        }
    }

    /**
     * Adds the '####.##' and '####,##' currency formats
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addCurrencyFormats($undo = false)
    {
        if ($undo) {
            $this->Record->query("ALTER TABLE `currencies` CHANGE `format` `format`
                ENUM('#,###.##','#.###,##','# ###.##','# ###,##','#,##,###.##','# ###','#.###','#,###')
                CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;
            ");
        } else {
            $this->Record->query("ALTER TABLE `currencies` CHANGE `format` `format`
                ENUM('#,###.##','#.###,##','# ###.##','# ###,##',
                    '#,##,###.##','# ###','#.###','#,###','####.##','####,##')
                CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;
            ");
        }
    }
}
