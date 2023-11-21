<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Tasks\Common\AbstractTask;
use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Configure;
use Language;
use Loader;
use stdClass;

/**
 * The payment_reminders automation task
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PaymentReminders extends AbstractTask
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
     *  - timezone The timezone of the company for which this task is being processed
     *  - client_uri The URI of the client interface
     */
    public function __construct(AutomationTypeInterface $task, array $options = [])
    {
        parent::__construct($task, $options);

        Loader::loadModels($this, ['Accounts', 'ClientGroups', 'Clients', 'Contacts', 'Emails', 'Invoices']);
        Loader::loadHelpers($this, ['CurrencyFormat', 'Html']);
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

        $data = $this->task->raw();
        $this->companyId = $data->company_id;

        // Log the task has begun
        $this->log(Language::_('Automation.task.payment_reminders.attempt', true));

        // Execute the payment_reminders cron task
        $this->process();

        // Log the task has completed
        $this->log(Language::_('Automation.task.payment_reminders.completed', true));
        $this->logComplete();
    }

    /**
     * Processes the task
     */
    private function process()
    {
        // Get all open invoices
        $invoices = $this->Invoices->getAll();

        // Payment account types
        $ach_types = $this->Accounts->getAchTypes();
        $cc_types = $this->Accounts->getCcTypes();

        // Keep track of each client we have fetched and their autodebit accounts
        $clients = [];
        $autodebit_accounts = [];

        // Send a reminder regarding each invoice if now is the time to send such a notice
        $reminders = ['notice1', 'notice2', 'notice3'];
        foreach ($invoices as $invoice) {
            if (!isset($clients[$invoice->client_id])) {
                $clients[$invoice->client_id] = $this->Clients->get($invoice->client_id);
            }

            $client = $clients[$invoice->client_id];
            foreach ($reminders as $action) {
                if ($this->shouldSendReminder($invoice, $client, $action)) {
                    // Get the client's autodebit account to be included as an email tag
                    $autodebit_accounts[$client->id] = $this->getAutodebitAccount(
                        $client,
                        $autodebit_accounts,
                        $ach_types,
                        $cc_types
                    );

                    // Send the reminder
                    $this->sendNotices($client, $invoice, $autodebit_accounts[$client->id], $action);
                }
            }
        }

        // Remove data no longer needed
        unset($invoices, $reminders);

        // Fetch all client groups
        $client_groups = $this->ClientGroups->getAll($this->companyId);

        // Send reminders regarding invoices set to be autodebited soon
        foreach ($client_groups as $client_group) {
            // Get all invoices set to be autodebited in the future for this group
            $invoices = $this->Invoices->getAllAutodebitableInvoices(
                $client_group->id,
                true,
                'notice_pending_autodebit'
            );

            // Send a notice regarding each invoice
            foreach ($invoices as $invoice) {
                if (!isset($clients[$invoice->client_id])) {
                    $clients[$invoice->client_id] = $this->Clients->get($invoice->client_id);
                }
                $client = $clients[$invoice->client_id];

                // Skip clients that are not set to receive this notice
                if (isset($client->settings['send_payment_notices'])
                    && $client->settings['send_payment_notices'] != 'true'
                ) {
                    continue;
                }

                // Get the client's autodebit account to be included as an email tag
                $autodebit_accounts[$client->id] = $this->getAutodebitAccount(
                    $client,
                    $autodebit_accounts,
                    $ach_types,
                    $cc_types
                );

                // Send autodebit notices
                $this->sendNotices($client, $invoice, $autodebit_accounts[$client->id]);
            }
        }
    }

    /**
     * Gets the given client's default autodebit account
     *
     * @param stdClass $client The client to whom the account belongs
     * @param array $autodebit_accounts A list of already known autodebit accounts
     * @param array $ccTypes A list of credit card account types
     * @param array $achTypes A list of ach account types
     * @return stdClass An object representing the client's autodebit account
     */
    private function getAutodebitAccount($client, array $autodebit_accounts, array $ccTypes, array $achTypes)
    {
        // Get autodebit account info
        $autodebit_account = isset($autodebit_accounts[$client->id]) ? $autodebit_accounts[$client->id] : null;

        // Set the autodebit payment account (if any)
        if ($client->settings['autodebit'] == 'true'
            && !$autodebit_account
            && ($debit_account = $this->Clients->getDebitAccount($client->id))
        ) {
            // Get the account
            $autodebit_account = $debit_account->type == 'cc'
                ? $this->Accounts->getCc($debit_account->account_id)
                : $this->Accounts->getAch($debit_account->account_id);

            // Set the account type (as a tag for the email)
            $account_types = $debit_account->type == 'cc' ? $ccTypes : $achTypes;
            $autodebit_account->type_name = isset($account_types[$autodebit_account->type])
                    ? $account_types[$autodebit_account->type]
                    : $autodebit_account->type;

            $autodebit_account->account_type = $debit_account->type;
        }

        return $autodebit_account;
    }

    /**
     * Send an autodebit or payment notice for the given client and invoice
     *
     * @param stdClass $client The client to send this notice to
     * @param stdClass $invoice The invoice this notice is being sent for
     * @param stdClass $autodebit_account The client's autodebit account
     * @param string $action The type of payment notice to send (optional)
     */
    private function sendNotices($client, $invoice, $autodebit_account, $action = null)
    {
        // Get all contacts that should receive this notice
        $contacts = $this->Contacts->getAll($client->id, 'billing');
        if (empty($contacts)) {
            $contacts = $this->Contacts->getAll($client->id, 'primary');
        }

        foreach ($contacts as $contact) {
            // Send the notice
            $errors = null;
            $message = 'Automation.task.payment_reminders.success';
            if ($action) {
                // Payment notice
                $errors = $this->sendPaymentNotice($action, $client, $contact, $invoice, $autodebit_account);
                $message = ($errors
                    ? 'Automation.task.payment_reminders.failed'
                    : 'Automation.task.payment_reminders.success'
                );
            } else {
                // Autodebit notice
                $errors = $this->sendAutodebitNotice($client, $contact, $invoice, $autodebit_account);
                $message = ($errors
                    ? 'Automation.task.payment_reminders.autodebit_failed'
                    : 'Automation.task.payment_reminders.autodebit_success'
                );
            }

            // Log success/failure
            $this->log(Language::_(
                $message,
                true,
                $contact->first_name,
                $contact->last_name,
                $client->id_code,
                $invoice->id_code
            ));
        }
    }

    /**
     * Determines whether or not to send a payment reminder notice for an invoice
     * @see Cron::paymentReminders()
     *
     * @param stdClass $invoice An invoice object
     * @param stdClass $client The client object that the invoice belongs to
     * @param string $action The email notice action
     * @return bool True if a reminder should be sent out for this invoice, false otherwise
     */
    private function shouldSendReminder(stdClass $invoice, stdClass $client, $action)
    {
        // Ensure the settings allow for the client to receive this notice
        if (isset($client->settings[$action])
            && is_numeric($client->settings[$action])
            && (!isset($client->settings['send_payment_notices'])
                || $client->settings['send_payment_notices'] == 'true')
        ) {
            // Set today's date timestamp
            $todays_datetime = $this->date->toTime($this->date->format('Y-m-d', date('c')));

            // Set timestamp of when the reminder should be sent
            $days_from_due_date = (int)$client->settings[$action];
            $invoice_date = $this->date->format('Y-m-d', $invoice->date_due . 'Z');
            $invoice_reminder_datetime = $this->date->toTime(
                $this->date->modify(
                    $invoice_date,
                    ($days_from_due_date >= 0 ? '+' : '-') . abs($days_from_due_date) . ' days',
                    'c',
                    isset($this->options['timezone']) ? $this->options['timezone'] : 'UTC'
                )
            );

            // Reminder should be sent for this invoice today
            if ($invoice_reminder_datetime == $todays_datetime) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sends an autodebit notice to the given contact regarding this invoice
     * @see Cron::paymentReminders()
     *
     * @param stdClass $client The client object
     * @param stdClass $contact The contact object representing one of the client's contacts
     * @param stdClass $invoice The invoice to send a payment notice about, belonging to this client
     * @param mixed An stdClass object representing the autodebit payment account for this client (if any) (optional)
     * @return mixed An array of errors on failure, or false on success
     */
    private function sendAutodebitNotice(
        stdClass $client,
        stdClass $contact,
        stdClass $invoice,
        $autodebit_account = null
    ) {
        // Build tags
        $autodebit_date = $this->Invoices->getAutodebitDate($invoice->id);

        // Format invoice fields
        $invoice->date_due_formatted = $this->date->cast($invoice->date_due, $client->settings['date_format']);
        $invoice->date_billed_formatted = $this->date->cast($invoice->date_billed, $client->settings['date_format']);
        $invoice->date_closed_formatted = $this->date->cast($invoice->date_closed, $client->settings['date_format']);
        $invoice->date_autodebit_formatted = $this->date->cast(
            $invoice->date_autodebit,
            $client->settings['date_format']
        );

        // Set a hash for the payment URL
        $hash = $this->Invoices->createPayHash($client->id, $invoice->id);

        // Get the company hostname
        $hostname = isset(Configure::get('Blesta.company')->hostname) ? Configure::get('Blesta.company')->hostname : '';

        $tags = [
            'contact' => $contact,
            'invoice' => $invoice,
            'payment_account' => $autodebit_account,
            'payment_url' => $this->Html->safe(
                $hostname . $this->options['client_uri'] . 'pay/method/' . $invoice->id . '/?sid='
                . rawurlencode($this->Clients->systemEncrypt('c=' . $client->id . '|h=' . substr($hash, -16)))
            ),
            'autodebit_date' => $this->date->cast(
                ($autodebit_date ? $autodebit_date : ''),
                $client->settings['date_format']
            ),
            'amount' => $this->CurrencyFormat->cast($invoice->due, $invoice->currency),
            'amount_formatted' => $this->CurrencyFormat->format($invoice->due, $invoice->currency)
        ];

        // Send the email
        $this->Emails->send(
            'auto_debit_pending',
            $this->companyId,
            $client->settings['language'],
            $contact->email,
            $tags,
            null,
            null,
            null,
            ['to_client_id' => $client->id]
        );

        $errors = $this->Emails->errors();

        // Send message
        Loader::loadModels($this, ['MessengerManager']);
        $this->MessengerManager->send('auto_debit_pending', $tags, [$client->user_id]);

        if (empty($errors)) {
            $errors = $this->MessengerManager->errors();
        }

        // Reset errors
        $this->resetErrors($this->Emails);

        return $errors;
    }

    /**
     * Sends a payment notice to the given contact regarding this invoice
     * @see Cron::paymentReminders()
     *
     * @param string $action The payment notice setting (i.e. one of "notice1", "notice2", "notice3")
     * @param stdClass $client The client object
     * @param stdClass $contact The contact object representing one of the client's contacts
     * @param stdClass $invoice The invoice to send a payment notice about, belonging to this client
     * @param mixed An stdClass object representing the autodebit payment account for this client (if any) (optional)
     * @return mixed An array of errors on failure, or false on success
     */
    private function sendPaymentNotice(
        $action,
        stdClass $client,
        stdClass $contact,
        stdClass $invoice,
        $autodebit_account = null
    ) {
        // Determine the email template to send
        $email_group_action = null;
        switch ($action) {
            case 'notice1':
                $email_group_action = 'invoice_notice_first';
                break;
            case 'notice2':
                $email_group_action = 'invoice_notice_second';
                break;
            case 'notice3':
                $email_group_action = 'invoice_notice_third';
                break;
        }

        // Build tags
        $autodebit_date = $this->Invoices->getAutodebitDate($invoice->id);

        // Format invoice fields
        $invoice->date_due_formatted = $this->date->cast($invoice->date_due, $client->settings['date_format']);
        $invoice->date_billed_formatted = $this->date->cast($invoice->date_billed, $client->settings['date_format']);
        $invoice->date_closed_formatted = $this->date->cast($invoice->date_closed, $client->settings['date_format']);
        $invoice->date_autodebit_formatted = $this->date->cast(
            $invoice->date_autodebit,
            $client->settings['date_format']
        );

        // Set a hash for the payment URL
        $hash = $this->Invoices->createPayHash($client->id, $invoice->id);

        // Get the company hostname
        $hostname = isset(Configure::get('Blesta.company')->hostname) ? Configure::get('Blesta.company')->hostname : '';

        $tags = [
            'contact' => $contact,
            'invoice' => $invoice,
            'payment_account' => $autodebit_account,
            'client_url' => $this->Html->safe($hostname . $this->options['client_uri']),
            'payment_url' => $this->Html->safe(
                $hostname . $this->options['client_uri'] . 'pay/method/' . $invoice->id . '/?sid='
                . rawurlencode($this->Clients->systemEncrypt('c=' . $client->id . '|h=' . substr($hash, -16)))
            ),
            'autodebit' => ($client->settings['autodebit'] == 'true'),
            'autodebit_date' => $autodebit_date,
            'autodebit_date_formatted' => $this->date->cast(
                ($autodebit_date ? $autodebit_date : ''),
                $client->settings['date_format']
            )
        ];

        $options = [
            'email_template' => $email_group_action,
            'base_client_url' => $tags['client_url'],
            'email_tags' => $tags
        ];

        // Deliver the invoices
        $this->InvoiceDelivery->deliverInvoices([$invoice->id], 'email', $contact->email, null, $options);

        $errors = $this->InvoiceDelivery->errors();

        // Send message
        Loader::loadModels($this, ['MessengerManager']);
        $this->MessengerManager->send($options['email_template'], $tags, [$client->user_id]);

        if (empty($errors)) {
            $errors = $this->MessengerManager->errors();
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
