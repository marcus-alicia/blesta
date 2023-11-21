<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Tasks\Common\AbstractTask;
use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Configure;
use Language;
use Loader;
use stdClass;

/**
 * The deliver_invoices automation task
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class DeliverInvoices extends AbstractTask
{
    /**
     * Initialize a new task
     *
     * @param AutomationTypeInterface $task The raw automation task
     * @param array $options An additional options necessary for the task:
     *
     *  - print_log True to print logged content to stdout, or false otherwise (default false)
     *  - cli True if this is being run via the Command-Line Interface, or false otherwise (default true)
     *  - client_uri The URI of the client interface
     */
    public function __construct(AutomationTypeInterface $task, array $options = [])
    {
        parent::__construct($task, $options);

        Loader::loadModels($this, ['Accounts', 'Clients', 'Invoices', 'Contacts']);
        Loader::loadHelpers($this, ['Html']);
        Loader::loadComponents($this, ['InvoiceDelivery']);
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
        $this->log(Language::_('Automation.task.deliver_invoices.attempt', true));

        // Execute the deliver_invoices cron task
        $this->process();

        // Log the task has completed
        $this->log(Language::_('Automation.task.deliver_invoices.completed', true));
        $this->logComplete();
    }

    /**
     * Processes the task
     */
    private function process()
    {
        // Get enabled delivery methods that we may send invoices by
        $delivery_methods = $this->Invoices->getDeliveryMethods();

        // Get all invoices to be delivered
        $deliverable_invoices = $this->Invoices->getAll(null, 'to_deliver', ['invoices.client_id' => 'ASC']);

        // Group all deliverable invoices
        $client_invoices = $this->groupInvoices($deliverable_invoices);

        // Deliver the invoices
        $num_invoices = null;
        foreach ($client_invoices as $client_id => $invoice_status_types) {
            foreach ($invoice_status_types as $invoice_status_type => $methods) {
                foreach ($methods as $delivery_method => $invoice_ids) {
                    // Only deliver invoices if this method is in use and the client exists
                    if (isset($delivery_methods[$delivery_method]) && ($client = $this->Clients->get($client_id))) {
                        // Send the invoices to this client via this delivery method
                        $errors = $this->sendInvoices(
                            $invoice_ids,
                            $client,
                            $delivery_method,
                            $invoice_status_type
                        );

                        // Log success/error
                        $num_invoices = count($invoice_ids);
                        $delivery_method_name = Language::_(
                            'Automation.task.deliver_invoices.method_' . $delivery_method,
                            true
                        );

                        // Log success/error
                        $lang = '';
                        $args = [true, $client->id_code, $delivery_method_name];
                        if ($errors) {
                            $error_message = '';
                            foreach ($errors as $err) {
                                foreach ($err as $message) {
                                    $error_message = $message;
                                }
                            }

                            if ($num_invoices == 1) {
                                $lang = 'Automation.task.deliver_invoices.delivery_error_one';
                                $args = array_merge($args, [$error_message]);
                            } else {
                                $lang = 'Automation.task.deliver_invoices.delivery_error';
                                $args = array_merge($args, [$num_invoices, $error_message]);
                            }
                        } else {
                            if ($num_invoices == 1) {
                                $lang = 'Automation.task.deliver_invoices.delivery_success_one';
                            } else {
                                $lang = 'Automation.task.deliver_invoices.delivery_success';
                                $args = array_merge($args, [$num_invoices]);
                            }
                        }
                        $this->log(call_user_func_array('\Language::_', array_merge([$lang], $args)));
                    }
                }
            }
        }

        // No invoices were sent
        if ($num_invoices === null) {
            $this->log(Language::_('Automation.task.deliver_invoices.none', true));
        }
    }

    /**
     * Groups invoices into invoice IDs by client
     *
     * @param array $invoices A list of stdClass objects representing invoices
     * @return array A list of invoices grouped by client and delivery method
     */
    private function groupInvoices(array $invoices)
    {
        $grouped_invoices = [];

        // Group invoices
        foreach ($invoices as $invoice) {
            // Set a client group for invoices
            if (!isset($grouped_invoices[$invoice->client_id])) {
                $grouped_invoices[$invoice->client_id] = [];
            }

            // Set the invoice type based on status
            $invoice_status_type = 'unpaid';
            if ($invoice->date_closed != null) {
                $invoice_status_type = 'paid';
            }

            // Get the invoice delivery methods of this invoice
            $delivery_methods = $this->Invoices->getDelivery($invoice->id);
            foreach ($delivery_methods as $method) {
                if ($method->date_sent == null && $method->method != 'paper') {
                    if (!isset($grouped_invoices[$invoice->client_id][$invoice_status_type][$method->method])) {
                        $grouped_invoices[$invoice->client_id][$invoice_status_type][$method->method] = [];
                    }
                    $grouped_invoices[$invoice->client_id][$invoice_status_type][$method->method][]
                        = $method->invoice_id;
                }
            }
        }

        return $grouped_invoices;
    }

    /**
     * Sends a group of invoices to a given client via the delivery method and
     * marks each invoice delivered
     *
     * @param array $invoice_ids A list of invoice IDs belonging to the client
     * @param stdClass An stdClass object representing the client
     * @param string $delivery_method The delivery method to send the invoices with
     * @param string $invoice_status_type The invoice status type indicating the type of
     *  invoice_ids given: (optional, default "unpaid")
     *
     *  - paid
     *  - unpaid
     * @return array An array of errors from attempting to deliver the invoices
     */
    private function sendInvoices(array $invoice_ids, $client, $delivery_method, $invoice_status_type = 'unpaid')
    {
        // Get all billing contacts to deliver the invoices to, or the primary if none exist
        $contacts = $this->Contacts->getAll($client->id, 'billing');
        if (empty($contacts)) {
            $contacts = $this->Contacts->getAll($client->id, 'primary');
        }

        // Get the company hostname
        $hostname = isset(Configure::get('Blesta.company')->hostname) ? Configure::get('Blesta.company')->hostname : '';

        // Set the email template to use
        $email_template = 'invoice_delivery_unpaid';
        if ($invoice_status_type == 'paid') {
            $email_template = 'invoice_delivery_paid';
        }

        // Deliver the invoices to each contact
        $errors = [];
        $delivered = 0;
        foreach ($contacts as $contact) {
            // Deliver the invoice to the contact's email or fax number
            $deliver_to = $contact->email;
            if ($delivery_method == 'interfax') {
                $deliver_to = '';

                $fax_numbers = $this->Contacts->getNumbers($contact->id, 'fax');
                if (!empty($fax_numbers[0])) {
                    $deliver_to = $fax_numbers[0]->number;
                }
            }

            // Fetch the auto debit account, if any
            $debit_account = $this->Clients->getDebitAccount($contact->client_id);
            $autodebit_account = null;
            if (!empty($debit_account)) {
                // Get the account
                $autodebit_account = $debit_account->type == 'cc'
                    ? $this->Accounts->getCc($debit_account->account_id)
                    : $this->Accounts->getAch($debit_account->account_id);

                // Set the account type (as a tag for the email)
                $account_types = $debit_account->type == 'cc'
                    ? $this->Accounts->getCcTypes()
                    : $this->Accounts->getAchTypes();
                $autodebit_account->type_name = isset($account_types[$autodebit_account->type])
                    ? $account_types[$autodebit_account->type]
                    : $autodebit_account->type;

                $autodebit_account->account_type = $debit_account->type;
            }

            $options = [
                'email_template' => $email_template,
                'base_client_url' => $this->Html->safe($hostname . $this->options['client_uri']),
                // use the invoices that InvoiceDelivery::deliverInvoices() will build as the "invoices" tag
                'set_built_invoices' => true,
                'email_tags' => [
                    'contact' => $contact,
                    'invoices' => '', // this tag will be populated in InvoiceDelivery::deliverInvoices()
                    'autodebit' => ($client->settings['autodebit'] == 'true'),
                    'payment_account' => $autodebit_account,
                    'client_url' => $this->Html->safe($hostname . $this->options['client_uri'])
                ]
            ];

            // Deliver the invoices
            $this->InvoiceDelivery->deliverInvoices($invoice_ids, $delivery_method, $deliver_to, null, $options);

            // Set errors
            $temp_errors = $this->InvoiceDelivery->errors();
            if (is_array($temp_errors)) {
                $errors = array_merge($errors, $temp_errors);

                // Reset errors
                $this->resetErrors($this->InvoiceDelivery);
            } else {
                $delivered++;
            }

            // Send message
            $this->InvoiceDelivery->deliverInvoices($invoice_ids, 'sms', $deliver_to, null, $options);

            // Set errors
            $temp_errors = $this->InvoiceDelivery->errors();
            if (is_array($temp_errors)) {
                $errors = array_merge($errors, $temp_errors);

                // Reset errors
                $this->resetErrors($this->InvoiceDelivery);
            }
        }

        // Mark each invoice as sent if it has been delivered
        if ($delivered > 0) {
            // Get each invoice delivery record that has not yet been marked sent
            $delivery_records = $this->Invoices->getAllDelivery($invoice_ids, $delivery_method, 'unsent');

            // Mark each invoice sent
            foreach ($delivery_records as $delivery) {
                $this->Invoices->delivered($delivery->id);
            }
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    protected function isTimeToRun()
    {
        return $this->task->canRun(date('c'));
    }
}
