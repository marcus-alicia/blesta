<?php
/**
 * Upgrades to version 4.7.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_7_0B1 extends UpgradeUtil
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
            'addClientCustomFieldDefaults',
            'updateConfig',
            'addAccountsCcTypeOther',
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
     * Adds a default field for client custom fields
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addClientCustomFieldDefaults($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `client_fields` DROP `default`');
        } else {
            $this->Record->query('ALTER TABLE `client_fields` ADD `default` TEXT NULL DEFAULT NULL AFTER `values`');
        }
    }

    /**
     * Updates the config
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function updateConfig($undo = false)
    {
        if ($undo) {
            // No need to undo anything
        } else {
            // Remove Blesta.transactions_validate_apply_round
            if (file_exists(CONFIGDIR . 'blesta.php')) {
                $this->mergeConfig(CONFIGDIR . 'blesta.php', CONFIGDIR . 'blesta-new.php');
            }
        }
    }

    /**
     * Adds the 'other' credit card type
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addAccountsCcTypeOther($undo = false)
    {
        if ($undo) {
            $this->Record->query("ALTER TABLE `accounts_cc` CHANGE `type` `type`
                ENUM('amex','bc','cup','dc-cb','dc-er','dc-int','dc-uc','disc','ipi',
                    'jcb','lasr','maes','mc','solo','switch','visa')
                CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;
            ");
        } else {
            $this->Record->query("ALTER TABLE `accounts_cc` CHANGE `type` `type`
                ENUM('amex','bc','cup','dc-cb','dc-er','dc-int','dc-uc','disc','ipi',
                    'jcb','lasr','maes','mc','solo','switch','visa','other')
                CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;
            ");
        }
    }
}
