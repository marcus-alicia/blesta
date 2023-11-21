<?php
/**
 * Upgrades to version 3.0.0.b7
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_0_0B7 extends UpgradeUtil
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
            'setPrecision'
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
     * Updates supported decimal precision
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function setPrecision($undo = false)
    {
        if ($undo) {
            $statement = $this->Record->query(
                "ALTER TABLE `taxes` CHANGE `amount` `amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0.00';"
            );
            $statement->closeCursor();
            unset($statement);

            $statement = $this->Record->query(
                "ALTER TABLE `coupon_amounts` CHANGE `amount` `amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0.00';"
            );
            $statement->closeCursor();
            unset($statement);
        } else {
            $statement = $this->Record->query(
                "ALTER TABLE `taxes` CHANGE `amount` `amount` DECIMAL( 12, 4 ) NOT NULL DEFAULT '0.0000';"
            );
            $statement->closeCursor();
            unset($statement);

            $statement = $this->Record->query(
                "ALTER TABLE `coupon_amounts` CHANGE `amount` `amount` DECIMAL( 12, 4 ) NOT NULL DEFAULT '0.0000';"
            );
            $statement->closeCursor();
            unset($statement);
        }
    }
}
