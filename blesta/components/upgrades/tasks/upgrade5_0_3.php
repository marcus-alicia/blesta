<?php

/**
 * Upgrades to version 5.0.3
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade5_0_3 extends UpgradeUtil
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
            'addMissingActions',
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
     * Adds actions for additional company plugins
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addMissingActions($undo = false)
    {
        if ($undo) {
            // Do nothing
        } else {
            $actions = $this->Record->select(
                    ['actions.*', 'matching_plugins.id' => 'plugin_id', 'matching_plugins.company_id']
                )->
                from('actions')->
                innerJoin('plugins', 'plugins.id', '=', 'actions.plugin_id', false)->
                on('matching_plugins.company_id', '!=', 'plugins.company_id', false)->
                innerJoin(['plugins' => 'matching_plugins'], 'matching_plugins.dir', '=', 'plugins.dir', false)->
                fetchAll();
            foreach ($actions as $action) {
                unset($action->id);
                $this->Record->duplicate('location', '=', $action->location)->insert('actions', (array)$action);
            }
        }
    }
}
