<?php
/**
 * Upgrades to version 4.0.0-b6
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_0_0B6 extends UpgradeUtil
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
    }

    /**
     * Returns a numerically indexed array of tasks to execute for the upgrade process
     *
     * @retrun array A numerically indexed array of tasks to execute for the upgrade process
     */
    public function tasks()
    {
        return [
            'updateConfig'
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
     * Updates the blesta config file to set a new SQL MODE query
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function updateConfig($undo = false)
    {
        // No undo
        if ($undo) {
            return;
        }

        $config = CONFIGDIR . 'blesta.php';
        if (file_exists($config)) {
            // Load Blesta configuration
            Configure::load('blesta');

            // Update the database info value to contain a new key/value pair to set the sql_mode
            $field = 'Blesta.database_info';

            if (($dbInfo = Configure::get($field))) {
                $dbInfo['sqlmode_query'] = "SET sql_mode='TRADITIONAL'";

                $this->editConfig($config, $field, $dbInfo);
            }
        }
    }
}
