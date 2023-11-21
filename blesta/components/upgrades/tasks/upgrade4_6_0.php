<?php
/**
 * Upgrades to version 4.6.0
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_6_0 extends UpgradeUtil
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
            'setClientProrateCredits'
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
     * Adds a new setting to handle whether to give prorated credit to clients for service downgrades
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function setClientProrateCredits($undo = false)
    {

        if ($undo) {
            // Nothing to do
        } else {
            // Add client_prorate_credits setting to company settings
            $companies = $this->Record->select('companies.id')->
                from('companies')->
                on('company_settings.key', '=', 'client_prorate_credits')->
                leftJoin('company_settings', 'company_settings.company_id', '=', 'companies.id', false)->
                where('company_settings.company_id', '=', null)->
                fetchAll();

            foreach ($companies as $company) {
                $this->Record->insert(
                    'company_settings',
                    ['key' => 'client_prorate_credits', 'company_id' => $company->id, 'value' => 'false']
                );
            }
        }
    }
}
