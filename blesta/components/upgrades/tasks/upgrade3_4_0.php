<?php
/**
 * Upgrades to version 3.4.0
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_4_0 extends UpgradeUtil
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
            'updateConfig',
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
     * Update config
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function updateConfig($undo = false)
    {
        if ($undo) {
            if (file_exists(CONFIGDIR . 'blesta.php')) {
                $this->editConfig(CONFIGDIR . 'blesta.php', 'Blesta.marketplace_url', '""');
            }
        } else {
            if (file_exists(CONFIGDIR . 'blesta.php')) {
                $this->editConfig(
                    CONFIGDIR . 'blesta.php',
                    'Blesta.marketplace_url',
                    '"http://marketplace.blesta.com/"'
                );
            }
        }
    }
}
