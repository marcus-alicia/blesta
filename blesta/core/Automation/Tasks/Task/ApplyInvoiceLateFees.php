<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Tasks\Common\AbstractTask;
use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Minphp\Date\Date;
use Configure;
use Language;
use Loader;

/**
 * The apply_invoice_late_fees automation task
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ApplyInvoiceLateFees extends AbstractTask
{
    /**
     * @var int The ID of the company this task is being processed for
     */
    private $companyId;

    /**
     * Initialize a new task
     *
     * @param AutomationTypeInterface $task The raw automation task
     * @param array $options An additional options necessary for the task:
     *
     *  - print_log True to print logged content to stdout, or false otherwise (default false)
     *  - cli True if this is being run via the Command-Line Interface, or false otherwise (default true)
     */
    public function __construct(AutomationTypeInterface $task, array $options = [])
    {
        parent::__construct($task, $options);

        Loader::loadModels($this, ['Companies', 'Clients', 'Invoices']);
        Loader::loadComponents($this, ['Record', 'SettingsCollection']);
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        // This task cannot be run right now
        if (!$this->isTimeToRun()) {
            return;
        }

        $data = $this->task->raw();
        $this->companyId = $data->company_id;

        // Log the task has begun
        $this->log(Language::_('Automation.task.apply_invoice_late_fees.attempt', true));

        // Execute the apply_invoice_late_fees cron task
        $this->process();

        // Log the task has completed
        $this->log(Language::_('Automation.task.apply_invoice_late_fees.completed', true));
        $this->logComplete();
    }

    /**
     * Processes the task
     */
    private function process()
    {
        // Apply late fees to overdue invoices
        $this->applyLateFees();
    }

    /**
     * Apply a late fee to open invoices a configured number of days after due.
     */
    private function applyLateFees()
    {
        // Format date
        $current_date = $this->Invoices->dateToUtc(date('c'));

        // Get the beginning of the current day relative to the configured timezone
        $this->date->setTimezone('UTC', Configure::get('Blesta.company_timezone'));
        $today_start_date = $this->date->format('Y-m-d 00:00:00', date('c'));

        // Convert this date to UTC and cast to a timestamp
        $this->date->setTimezone(Configure::get('Blesta.company_timezone'), 'UTC');
        $today_start_time = strtotime($this->date->format('c', $today_start_date));
        $this->date->setTimezone('UTC', 'UTC');

        // Get past due invoices and proformas
        $invoices = $this->Record->select(['invoices.*', 'clients.client_group_id'])
            ->from('invoices')
            ->where('invoices.date_closed', '=', null)
            ->where('invoices.status', 'in', ['active', 'proforma'])
            ->where('invoices.date_due', '<', $current_date)
            ->leftJoin('invoice_late_fees', 'invoice_late_fees.invoice_id', '=', 'invoices.id', false)
            ->where('invoice_late_fees.invoice_line_id', '=', null)
            ->innerJoin('clients', 'clients.id', '=', 'invoices.client_id', false)
            ->fetchAll();

        $clients = [];

        foreach ($invoices as $invoice) {
            // Fetch the client group settings
            $settings = $this->SettingsCollection->fetchClientGroupSettings($invoice->client_group_id);
            $settings['late_fees'] = (array) unserialize(base64_decode($settings['late_fees']));

            // Get the client
            if (!isset($clients[$invoice->client_id])) {
                $clients[$invoice->client_id] = $this->Clients->get($invoice->client_id);
            }

            // Validate if invoices are overdue by the specified number of days for the late fee to apply
            $invoice_date = $this->date->modify(
                $invoice->date_due,
                '+' . $settings['apply_inv_late_fees'] . ' days',
                'c'
            );

            if (
                ($today_start_time >= strtotime($invoice_date))
                && isset($settings['late_fees'][$invoice->currency]['enabled'])
                && $settings['late_fees'][$invoice->currency]['enabled'] == 'true'
            ) {
                // Calculate late fee
                $fee_amount = $settings['late_fees'][$invoice->currency]['amount'];

                if ($settings['late_fees'][$invoice->currency]['fee_type'] == 'percent') {
                    $fee_amount = ($invoice->total - $invoice->paid)
                        * ($settings['late_fees'][$invoice->currency]['amount'] / 100);

                    // Calculated the late fee based on the full amount of the invoice
                    if ($settings['late_fee_total_amount'] == 1) {
                        $fee_amount = $invoice->total * ($settings['late_fees'][$invoice->currency]['amount'] / 100);
                    }

                    // Set the minimum amount if the calculated percentage is less than the minimum
                    if ($fee_amount < $settings['late_fees'][$invoice->currency]['minimum']) {
                        $fee_amount = $settings['late_fees'][$invoice->currency]['minimum'];
                    }
                }

                // Add invoice line item
                $this->Record->insert('invoice_lines', [
                    'invoice_id' => $invoice->id,
                    'description' => Language::_(
                        'Automation.task.apply_invoice_late_fees.invoice_line',
                        true
                    ),
                    'qty' => 1,
                    'amount' => $fee_amount,
                    'order' => 0
                ]);
                $invoice_line_id = $this->Record->lastInsertId();

                if ($invoice_line_id) {
                    // Update invoice
                    $this->Record->insert('invoice_late_fees', [
                        'invoice_id' => $invoice->id,
                        'invoice_line_id' => $invoice_line_id
                    ]);

                    $this->Record->where('id', '=', $invoice->id)
                        ->update('invoices', [
                            'subtotal' => ($invoice->subtotal + $fee_amount),
                            'total' => ($invoice->total + $fee_amount)
                        ]);

                    $this->log(
                        Language::_(
                            'Automation.task.apply_invoice_late_fees.late_fee_success',
                            true,
                            $invoice->id,
                            $clients[$invoice->client_id]->id_code
                        )
                    );
                } else {
                    $this->log(
                        Language::_(
                            'Automation.task.apply_invoice_late_fees.late_fee_failed',
                            true,
                            $invoice->id,
                            $clients[$invoice->client_id]->id_code
                        )
                    );
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function isTimeToRun()
    {
        return $this->task->canRun(date('c'));
    }
}
