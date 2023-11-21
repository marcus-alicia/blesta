<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Tasks\Common\AbstractTask;
use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Language;
use Loader;
use stdClass;

/**
 * The autodebit automation task
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Autodebit extends AbstractTask
{
    /**
     * @var array A list of installed gateways keyed by currency code
     */
    private $installedGateways = [];
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
     *  - passphrase The encryption private key passphrase used for allowing payment accounts to be automatically
     *      processed
     */
    public function __construct(AutomationTypeInterface $task, array $options = [])
    {
        parent::__construct($task, $options);

        Loader::loadModels($this, ['Accounts', 'ClientGroups', 'Clients', 'GatewayManager', 'Invoices', 'Payments']);
        Loader::loadHelpers($this, ['CurrencyFormat']);
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
        $this->log(Language::_('Automation.task.autodebit.attempt', true));

        // Execute the autodebit cron task
        $this->process();

        // Log the task has completed
        $this->log(Language::_('Automation.task.autodebit.completed', true));
        $this->logComplete();
    }

    /**
     * Processes the task
     *
     * @param stdClass $data The autodebit task data
     */
    private function process()
    {
        // Fetch all client groups
        $clientGroups = $this->ClientGroups->getAll($this->companyId);

        // Send all autodebit invoices foreach client group
        foreach ($clientGroups as $clientGroup) {
            // Get invoices to be autodebited for this group
            $invoices = $this->Invoices->getAllAutodebitableInvoices($clientGroup->id);

            // Create a list of clients and total amounts due for each currency
            $clients = $this->groupInvoices($invoices);

            $this->autodebitClients($clients);
        }
    }

    /**
     * Groups invoices into invoice IDs by client and $type
     *
     * @param array $invoices A list of stdClass objects representing invoices
     * @return array A list of invoices grouped by client and delivery method
     */
    private function groupInvoices(array $invoices)
    {
        $groupedInvoices = [];

        // Group invoices
        foreach ($invoices as $invoice) {
            // Set a client group for invoices
            if (!isset($groupedInvoices[$invoice->client_id])) {
                $groupedInvoices[$invoice->client_id] = [];
            }

            $groupedInvoices[$invoice->client_id][] = $invoice;
        }

        // Remove data no longer needed
        unset($invoices, $invoice);

        $clients = [];
        foreach ($groupedInvoices as $clientId => $clientInvoices) {
            foreach ($clientInvoices as $clientInvoice) {
                if (!isset($clients[$clientId][$clientInvoice->currency])) {
                    $clients[$clientId][$clientInvoice->currency] = ['amount' => 0, 'invoice_amounts' => []];
                }
                $clients[$clientId][$clientInvoice->currency]['amount'] += $clientInvoice->due;
                $clients[$clientId][$clientInvoice->currency]['invoice_amounts'][$clientInvoice->id]
                    = $this->CurrencyFormat->cast($clientInvoice->due, $clientInvoice->currency);
            }
        }

        return $clients;
    }

    /**
     * Processes each clients autodebit payments
     *
     * @param array $clients A list of invoices grouped by client and delivery method
     */
    private function autodebitClients($clients)
    {
        // Autodebit cards
        foreach ($clients as $clientId => $currencies) {
            // Get the payment account to charge
            $client = $this->Clients->get($clientId, false);
            $debitAccount = $this->Clients->getDebitAccount($clientId);
            $paymentAccount = false;

            // Cannot autodebit without an autodebit account
            if (!$debitAccount) {
                continue;
            }

            if ($debitAccount->type == 'cc') {
                $paymentAccount = $this->Accounts->getCc($debitAccount->account_id);

                // Only process locally stored accounts if passphrase set
                if ($this->options['passphrase'] != '' && $paymentAccount && $paymentAccount->number == '') {
                    $paymentAccount = false;
                }
            } elseif ($debitAccount->type == 'ach') {
                $paymentAccount = $this->Accounts->getAch($debitAccount->account_id);

                // Only process locally stored accounts if passphrase set
                if ($this->options['passphrase'] != '' && $paymentAccount && $paymentAccount->account == '') {
                    $paymentAccount = false;
                }
            }

            // No payment account available to autodebit
            if (!$paymentAccount) {
                continue;
            }

            $this->chargeCard($client, $debitAccount, $paymentAccount, $currencies);
        }
    }

    /**
     * Charges a client's card for each of the given currency charges
     *
     * @param stdClass $client The client this charge is for
     * @param stdClass $debitAccount The client's autodebit account
     * @param stdClass $paymentAccount The client's payment account
     * @param array $currencies The charges for this client, sorted by currency
     */
    private function chargeCard(stdClass $client, stdClass $debitAccount, stdClass $paymentAccount, array $currencies)
    {
        // Charge each currency separately
        foreach ($currencies as $currencyCode => $charges) {
            // Format amount due
            $amountDue = $this->CurrencyFormat->cast($charges['amount'], $currencyCode);

            // Log attempt to charge
            $this->log(
                Language::_(
                    'Automation.task.autodebit.charge_attempt',
                    true,
                    $client->id_code,
                    $this->CurrencyFormat->format($amountDue, $currencyCode, ['code' => 'true'])
                )
            );

            // Set active gateway for use with the given currency
            if (!array_key_exists($currencyCode, $this->installedGateways)) {
                $this->installedGateways[$currencyCode] = $this->GatewayManager->getInstalledMerchant(
                    $this->companyId,
                    $currencyCode
                );
            }

            // Process the payment
            if ($amountDue > 0 && $paymentAccount && $this->installedGateways[$currencyCode]) {
                $options = [
                    'invoices' => $charges['invoice_amounts'],
                    'staff_id' => null,
                    'email_receipt' => true,
                    'passphrase' => $this->options['passphrase']
                ];
                // Process payment and send any necessary emails
                $this->Payments->processPayment(
                    $client->id,
                    $debitAccount->type,
                    $amountDue,
                    $currencyCode,
                    null,
                    $paymentAccount->id,
                    $options
                );

                // Log success/failure
                if (($errors = $this->Payments->errors())) {
                    $this->log(Language::_('Automation.task.autodebit.charge_failed', true));

                    // Reset errors
                    $this->resetErrors($this->Payments);
                } else {
                    $this->log(Language::_('Automation.task.autodebit.charge_success', true));
                }
            } else {
                // Unable to process the charge
                $this->log(Language::_('Automation.task.autodebit.charge_failed', true));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function isTimeToRun()
    {
        return $this->options['passphrase'] != '' || $this->task->canRun(date('c'));
    }
}
