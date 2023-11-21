<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Tasks\Common\AbstractTask;
use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Language;
use Loader;
use stdClass;

/**
 * The process_service_changes automation task
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ProcessServiceChanges extends AbstractTask
{
    /**
     * Initialize a new task
     *
     * @param AutomationTypeInterface $task The raw automation task
     * @param array $options An additional options necessary for the task:
     *
     *  - print_log True to print logged content to stdout, or false otherwise (default false)
     *  - cli True if this is being run via the Command-Line Interface, or false otherwise (default true)
     *  - timezone The default timezone of the company
     */
    public function __construct(AutomationTypeInterface $task, array $options = [])
    {
        parent::__construct($task, $options);

        Loader::loadModels($this, ['Clients', 'Invoices', 'Services', 'ServiceChanges', 'Transactions']);
        Loader::loadComponents($this, ['SettingsCollection']);
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

        // Log the task has begun
        $this->log(Language::_('Automation.task.process_service_changes.attempt', true));

        // Execute the process service changes cron task
        $this->process();

        // Log the task has completed
        $this->log(Language::_('Automation.task.process_service_changes.completed', true));
        $this->logComplete();
    }

    /**
     * Processes the task
     */
    private function process()
    {
        // Process the service changes
        $this->runProcessServiceChanges();
    }

    /**
     * Performs queued service change updates
     *
     * @see ::processServiceChanges
     */
    private function runProcessServiceChanges()
    {
        // Get services statuses
        $statuses = $this->ServiceChanges->getStatuses();
        $client_group_settings = [];

        // Fetch all pending service changes
        $service_changes = $this->ServiceChanges->getAll('pending');

        foreach ($service_changes as $service_change) {
            // Check whether the associated invoice is closed and process it
            $invoice = $this->Invoices->get($service_change->invoice_id);
            if ($invoice) {
                // Skip processing the queued change if it is apart of another company.
                // Fetching a client filters on the current company automatically, so the client
                // must exist for us to process the queued change
                if (!($client = $this->Clients->get($invoice->client_id, false))) {
                    continue;
                }

                // Fetch client group settings
                if (!isset($client_group_settings[$client->client_group_id])) {
                    $client_group_settings[$client->client_group_id] =
                        $this->SettingsCollection->fetchClientGroupSettings($client->client_group_id);
                }

                // Only process active services
                $service = $this->Services->get($service_change->service_id);
                if (!$service || $service->status != 'active') {
                    $this->log(
                        Language::_(
                            'Automation.task.process_service_changes.service_inactive',
                            true,
                            $service_change->id
                        )
                    );
                } else {
                    $this->processServiceChange(
                        $service_change,
                        $invoice,
                        $client_group_settings[$client->client_group_id],
                        $statuses
                    );
                }
            } else {
                // No invoice for this service change. Mark it as error
                $this->ServiceChanges->edit($service_change->service_id, ['status' => 'error']);
                $this->log(
                    Language::_(
                        'Automation.task.process_service_changes.missing_invoice',
                        true,
                        $service_change->invoice_id,
                        $service_change->id,
                        $service_change->service_id,
                        $statuses['error']
                    )
                );
            }
        }
    }

    /**
     * Processes a single service change
     *
     * @see ::runProcessServiceChanges
     * @param stdClass $service_change An stdClass object representing the service change
     * @param stdClass $invoice An stdClass object representing the invoice
     * @param array $settings An array of client group settings
     * @param array $statuses An array of service change statuses
     */
    private function processServiceChange($service_change, $invoice, $settings, $statuses)
    {
        $cancel_days = $settings['cancel_service_changes_days'];
        $cancel_date = $this->ServiceChanges->dateToUtc(
            $this->date->modify(
                date('c'),
                '-' . (int)$cancel_days . ' days',
                'c',
                $this->options['timezone']
            )
        );

        // Process queued service changes if setting enables us to do so, and invoice is closed
        if ($settings['process_paid_service_changes'] == 'true' && $invoice->date_closed !== null
            && in_array($invoice->status, ['active', 'proforma'])
        ) {
            // Attempt to process the service change
            $this->ServiceChanges->process($service_change->id);

            // Log the result of the process
            $updated_change = $this->ServiceChanges->get($service_change->id);

            $this->log(
                Language::_(
                    'Automation.task.process_service_changes.process_result',
                    true,
                    $service_change->id,
                    $statuses[$updated_change->status]
                )
            );
        } elseif (strtotime($cancel_date) > strtotime($invoice->date_due)) {
            // The service change expired and must be canceled
            $this->ServiceChanges->edit($service_change->id, ['status' => 'canceled']);

            // Log that the change expired
            $updated_change = $this->ServiceChanges->get($service_change->id);

            $this->log(
                Language::_(
                    'Automation.task.process_service_changes.expired',
                    true,
                    $service_change->id,
                    $statuses[$updated_change->status]
                )
            );
        }

        // Update the associated invoice only if it has been changed (completed or canceled)
        if (isset($updated_change) && !in_array($updated_change->status, ['pending', 'error'])) {
            $this->updateServiceChangeInvoice($updated_change, $invoice);
        }
    }

    /**
     * Updates the invoice for a service change
     *
     * @see ::processServiceChange
     * @param stdClass $service_change An stdClass object representing the service change
     * @param stdClass $invoice An stdClass object representing the invoice
     */
    private function updateServiceChangeInvoice($service_change, $invoice)
    {
        // Set required invoice information to update
        $vars = ['status' => $invoice->status];

        // Update the invoice to associate it with the service
        if ($service_change->status == 'completed') {
            $vars['lines'] = [];

            foreach ($invoice->line_items as $line) {
                $vars['lines'][] = [
                    'id' => $line->id,
                    'invoice_id' => $line->invoice_id,
                    'service_id' => $service_change->service_id,
                    'description' => $line->description,
                    'qty' => $line->qty,
                    'amount' => $line->amount,
                    'tax' => !empty($line->taxes)
                ];
            }
        } else {
            // Fetch payments applied to the invoice
            $transactions = $this->Transactions->getApplied(null, $invoice->id);

            // Unapply payments from the invoice and void it
            foreach ($transactions as $transaction) {
                $this->Transactions->unapply($transaction->id, [$invoice->id]);
            }

            // Void the invoice
            $vars['status'] = 'void';
        }

        $this->Invoices->edit($invoice->id, $vars);
    }

    /**
     * {@inheritdoc}
     */
    protected function isTimeToRun()
    {
        return $this->task->canRun(date('c'));
    }
}
