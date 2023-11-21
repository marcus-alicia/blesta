<?php
/**
 * Upgrades to version 3.0.0.a6
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_0_0A6 extends UpgradeUtil
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
            'clearContactUsers',
            'updatePluginEvents'
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
     * Clear all contacts.user_id values, since they should not be set (it's part
     * of contact logins, which are not yet available)
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function clearContactUsers($undo = false)
    {
        if ($undo) {
            // Nothing to undo
        } else {
            $this->Record->query(
                'UPDATE `contacts` SET `user_id`=NULL WHERE `user_id` IS NOT NULL;'
            );
        }
    }

    /**
     * Update the maximum length of event names for plugin events
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function updatePluginEvents($undo = false)
    {
        if ($undo) {
            $this->Record->query(
                'ALTER TABLE `plugin_events`
                    CHANGE `event` `event` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;'
            );
        } else {
            $this->Record->query(
                'ALTER TABLE `plugin_events`
                    CHANGE `event` `event` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;'
            );
        }
    }
}
