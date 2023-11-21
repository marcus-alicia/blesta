<?php
/**
 * Upgrades to version 3.5.1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2015, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_5_1 extends UpgradeUtil
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
            'addCurrencyPrecision'
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
     * Adds staff permissions for the new ClientsService controller
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    public function addCurrencyPrecision($undo = false)
    {
        if ($undo) {
            // Remove precision
            $this->Record->query('ALTER TABLE `currencies` DROP `precision`;');
        } else {
            // Add precision
            $statement = $this->Record->query("ALTER TABLE `currencies`
                ADD `precision` TINYINT(1) UNSIGNED NOT NULL DEFAULT '4' AFTER `format`;");
            $statement->closeCursor();
            unset($statement);

            // Update each known currency to its precision
            $currencies = [
                'AUD' => 2,
                'EUR' => 2,
                'GBP' => 2,
                'INR' => 2,
                'JPY' => 0,
                'USD' => 2
            ];

            foreach ($currencies as $currency => $precision) {
                $this->Record->where('code', '=', $currency)->
                    update('currencies', ['precision' => $precision]);
            }
        }
    }
}
