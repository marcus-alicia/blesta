<?php
/**
 * Upgrades to version 3.0.0.b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_0_0B1 extends UpgradeUtil
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
            'savePackageGroup',
            'updateEmailTags'
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
     * Updates services to add services.package_group_id to store the package group ID associated with a service
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function savePackageGroup($undo = false)
    {
        if ($undo) {
            $this->Record->query(
                'ALTER TABLE `services` DROP `package_group_id`;
				ALTER TABLE `services` DROP INDEX `package_group_id`;'
            );
        } else {
            $this->Record->query(
                'ALTER TABLE `services` ADD `package_group_id` INT UNSIGNED NULL DEFAULT NULL AFTER `parent_service_id`;
				ALTER TABLE `services` ADD INDEX ( `package_group_id` );'
            );
        }
    }

    /**
     * Updates the tag values set on email_groups
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function updateEmailTags($undo = false)
    {
        if ($undo) {
            // Nothing to undo
            return;
        }

        $this->Record->query(
            "UPDATE `email_groups`
                SET `tags`='{contact.first_name},{contact.last_name},{package.email_html},{package.email_text}'
                WHERE `action`='service_creation';"
        );
    }
}
