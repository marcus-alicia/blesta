<?php
/**
 * Upgrades to version 5.6.1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade5_6_1 extends UpgradeUtil
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
            'addStaffIdColumn',
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
     * Adds the "staff_id" column to the "services" table
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function addStaffIdColumn($undo = false)
    {
        if ($undo) {
            $this->Record->query("ALTER TABLE `services` DROP `staff_id`;");
        } else {
            $this->Record->query("ALTER TABLE `services` ADD `staff_id` INT UNSIGNED NULL DEFAULT NULL AFTER `client_id`");
        }
    }
}
