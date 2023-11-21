<?php
/**
 * Upgrades to version 3.4.3
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2015, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_4_3 extends UpgradeUtil
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
            'addIndexToTransactionApplied',
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
     * Add index to transaction applied
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addIndexToTransactionApplied($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `transaction_applied` DROP INDEX `invoice_id`');
        } else {
            $this->Record->query('ALTER TABLE `transaction_applied` ADD INDEX `invoice_id` ( `invoice_id` );');
        }
    }
}
