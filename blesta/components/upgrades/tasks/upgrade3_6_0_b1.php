<?php
/**
 * Upgrades to version 3.6.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2015, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_6_0B1 extends UpgradeUtil
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
            'addTransactionType',
            'increaseContactCompanyLength'
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
     * Adds an income type to transactions
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    public function addTransactionType($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `transaction_types` DROP `type`;');
        } else {
            // Add the transaction income type field
            $this->Record->query("ALTER TABLE `transaction_types`
                ADD `type` ENUM('debit','credit')
                CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'debit' AFTER `name`;
            ");

            // Set the included in house credit type to credit
            $this->Record->where('name', '=', 'in_house_credit')
                ->update('transaction_types', ['type' => 'credit']);
        }
    }

    /**
     * Updates the field that stores contact company names to increase its length from 64 -> 128 characters
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    public function increaseContactCompanyLength($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `contacts`
                CHANGE `company` `company` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;');
        } else {
            $this->Record->query('ALTER TABLE `contacts`
                CHANGE `company` `company` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;');
        }
    }
}
