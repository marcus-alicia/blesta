<?php
/**
 * Upgrades to version 4.1.1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_1_1 extends UpgradeUtil
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
            'addServiceInvoiceAssociations'
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
     * Adds service_invoices table for association
     *
     * @param bool $undo Whether to add or undo the change
     */
    public function addServiceInvoiceAssociations($undo = false)
    {
        if ($undo) {
            $this->Record->drop('service_invoices');
        } else {
            // Create the `service_invoices` table if it doesn't exist
            $this->Record->setField('invoice_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                ->setField('service_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                ->setKey(['invoice_id', 'service_id'], 'primary')
                ->create('service_invoices', true);

            // Fetch all of the open invoices that have a service (i.e. services to be renewed)
            $this->Record->select(['invoice_lines.invoice_id', 'invoice_lines.service_id'])
                ->from('invoice_lines')
                ->innerJoin('invoices', 'invoices.id', '=', 'invoice_lines.invoice_id', false)
                ->where('invoices.status', 'in', ['active', 'proforma'])
                ->where('invoice_lines.service_id', '!=', null)
                ->group(['invoice_lines.invoice_id', 'invoice_lines.service_id']);

            $sql = $this->Record->get();
            $values = $this->Record->values;
            $this->Record->reset();

            // Insert all of the services to be renewed into the table
            $this->Record->query(
                'INSERT INTO `service_invoices` (`invoice_id`, `service_id`) (' . $sql . ');',
                $values
            );
        }
    }
}
