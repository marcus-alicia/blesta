<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Tasks\Common\AbstractTask;
use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Language;
use Loader;
use stdClass;

/**
 * The create_invoices automation task
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class CreateInvoices extends AbstractTask
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

        Loader::loadModels($this, ['Clients', 'Invoices', 'Services', 'ClientGroups']);
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

        $data = $this->task->raw();
        $this->companyId = $data->company_id;

        // Log the task has begun
        $this->log(Language::_('Automation.task.create_invoices.attempt', true));

        // Execute the create_invoices cron task
        $this->process();

        // Log the task has completed
        $this->log(Language::_('Automation.task.create_invoices.completed', true));
        $this->logComplete();
    }

    /**
     * Processes the task
     *
     * @param stdClass $data The create_invoices task data
     */
    private function process()
    {
        // Create invoices for renewing services
        $this->createRenewingServiceInvoices();

        // Create recurring invoices
        $this->createRecurringInvoices();
    }

    /**
     * Create all renewing service invoices
     */
    private function createRenewingServiceInvoices()
    {
        // Keep track of things to log
        $output = '';

        // Get all client groups
        $client_groups = $this->ClientGroups->getAll($this->companyId);

        foreach ($client_groups as $client_group) {
            // Fetch all services ready to be renewed
            $services = $this->Services->getAllRenewing($client_group->id);
            // All services that failed to generate an invoice
            $failed_services = [];
            // Group services on invoices?
            $inv_group_services = true;
            if (($opt = $this->ClientGroups->getSetting($client_group->id, 'inv_group_services'))) {
                $inv_group_services = $opt->value === 'false'
                    ? false
                    : true;
            }
            unset($opt);

            // Go through each service and renew it as many times as necessary (to catch up)
            // and create all necessary invoices
            while (!empty($services)) {
                $invoice_services = [];

                // Setup an ordered list of services
                $i = 0;
                $inv_due_date = null;
                foreach ($services as $service) {
                    // Skip services that failed invoice generation
                    if (in_array($service->id, $failed_services)) {
                        continue;
                    }

                    $service->date_renews .= 'Z';

                    // The service date_renews is the same for all services in this loop
                    if ($i++ == 0) {
                        $inv_due_date = $service->date_renews;
                    }

                    // Calculate the next renew date (which gives us back UTC)
                    $service->next_renew_date = $this->Services->getNextRenewDate(
                        $service->date_renews,
                        $service->term,
                        $service->period,
                        'c'
                    );

                    // Add the service to the list of services for this client to be included on the invoice
                    if ($service->next_renew_date) {
                        // Add the service to the list of those to be added per invoice
                        if (!isset($invoice_services[$service->client_id])) {
                            $invoice_services[$service->client_id] = [];
                        }
                        $invoice_services[$service->client_id][] = $service;
                    }
                }
                unset($services, $service, $i);

                // If nothing to invoice, break out
                if (empty($invoice_services)) {
                    break;
                }

                // Generate an invoice for each client containing all renewing services
                foreach ($invoice_services as $client_id => $services) {
                    // Fetch the currency to generate the invoice in
                    $client_default_currency = $this->SettingsCollection->fetchClientSetting(
                        $client_id,
                        null,
                        'default_currency'
                    );
                    $default_currency = (isset($client_default_currency['value'])
                        ? $client_default_currency['value']
                        : null
                    );

                    // Build individual invoices for each service
                    if (!$inv_group_services) {
                        // However, still group child services with their parents
                        $family_services = [];
                        foreach ($services as $service) {
                            // Create a group of services containing the parent and all children
                            if ($service->parent_service_id) {
                                if (!isset($family_services[$service->parent_service_id])) {
                                    $family_services[$service->parent_service_id] = [];
                                }
                                $family_services[$service->parent_service_id][] = $service;
                            } else {
                                if (!isset($family_services[$service->id])) {
                                    $family_services[$service->id] = [];
                                }
                                $family_services[$service->id] = [$service];
                            }
                        }

                        // Create the invoice for the service
                        foreach ($family_services as $service_group) {
                            $output .= $this->invoiceServices(
                                $client_id,
                                $service_group,
                                $default_currency,
                                $inv_due_date,
                                $failed_services
                            );
                        }
                    } else {
                        // Create the invoice for all services
                        $output .= $this->invoiceServices(
                            $client_id,
                            $services,
                            $default_currency,
                            $inv_due_date,
                            $failed_services
                        );
                    }
                }

                // Re-fetch the services that need to be renewed to continue catching up
                $services = $this->Services->getAllRenewing($client_group->id);
            }
        }

        if (!empty($output)) {
            $this->log($output);
        }
    }

    /**
     * Create all recurring invoices
     */
    private function createRecurringInvoices()
    {
        // Get all client groups
        $client_groups = $this->ClientGroups->getAll($this->companyId);

        foreach ($client_groups as $client_group) {
            // Get all recurring invoices set to renew for this client group
            $invoices = $this->Invoices->getAllRenewingRecurring($client_group->id);
            $clients = [];

            foreach ($invoices as $invoice) {
                // Get the client
                if (!isset($clients[$invoice->client_id])) {
                    $clients[$invoice->client_id] = $this->Clients->get($invoice->client_id);
                }

                // Create a new recurring invoice (and possibly multiple)
                $invoice_created = $this->Invoices->addFromRecurring(
                    $invoice->id,
                    $clients[$invoice->client_id]->settings
                );

                // Log success/error for only those that had invoices to create and succeeded or failed to do so
                if (($errors = $this->Invoices->errors())) {
                    $this->log(
                        Language::_(
                            'Automation.task.create_invoices.recurring_invoice_failed',
                            true,
                            $invoice->id,
                            $clients[$invoice->client_id]->id_code
                        )
                    );

                    // Reset errors
                    $this->resetErrors($this->Invoices);
                } elseif ($invoice_created) {
                    $this->log(
                        Language::_(
                            'Automation.task.create_invoices.recurring_invoice_success',
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
     * Generate an invoice for a list client services
     *
     * @param int $client_id The ID of the client to which these services belong
     * @param array $services The services to invoice
     * @param string $default_currency The default currency for this client (if any)
     * @param string $inv_due_date The date this invoice will be due
     * @param array $failed_services A list of service that have already failed to be invoiced during this task run
     */
    private function invoiceServices(
        $client_id,
        array $services,
        $default_currency,
        $inv_due_date,
        array &$failed_services
    ) {
        // Keep track of things to log
        $output = '';

        // Generate an invoice for the services
        $service_ids = [];
        foreach ($services as $service) {
            $service_ids[] = $service->id;
        }

        // Set a CSV of service IDs for the log
        $csv_service_ids = implode(', ', $service_ids);

        // Create the invoice for these renewing services
        $invoice_id = $this->Invoices->createFromServices(
            $client_id,
            $service_ids,
            $default_currency,
            $inv_due_date,
            false,
            true,
            [],
            1,
            true
        );

        // Log the details
        if (($errors = $this->Invoices->errors())) {
            // Error, flag all service IDs that failed to generate an invoice
            $failed_services = array_unique(array_merge($failed_services, $service_ids));
            $output .= Language::_(
                'Automation.task.create_invoices.service_invoice_error',
                true,
                print_r($errors, true),
                $client_id,
                $csv_service_ids
            );

            // Reset errors
            $this->resetErrors($this->Invoices);
        } else {
            // Success, invoice was created. Update service renew dates
            foreach ($services as $service) {
                $dates = ['date_renews' => $service->next_renew_date, 'date_last_renewed' => $service->date_renews];
                $this->Services->edit($service->id, $dates, true);
            }

            $output .= Language::_(
                'Automation.task.create_invoices.service_invoice_success',
                true,
                $invoice_id,
                $client_id,
                $csv_service_ids
            );
        }

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    protected function isTimeToRun()
    {
        return $this->task->canRun(date('c'));
    }
}
