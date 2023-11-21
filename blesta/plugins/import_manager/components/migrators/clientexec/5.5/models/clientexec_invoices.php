<?php
/**
 * Generic Clientexec Invoices Migrator.
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.components.migrators.clientexec
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientexecInvoices
{
    /**
     * ClientexecInvoices constructor.
     *
     * @param Record $remote
     */
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    /**
     * Get all invoices.
     *
     * @return mixed The result of the sql transaction
     */
    public function get()
    {
        return $this->remote->select()->from('invoice')->fetchAll();
    }

    /**
     * Get the specific invoice.
     *
     * @param int $invoice_id
     * @return mixed The result of the sql transaction
     */
    public function getInvoice($invoice_id)
    {
        return $this->remote->select()->from('invoice')->where('id', '=', $invoice_id)->fetch();
    }

    /**
     * Get all invoice lines from an specific invoice.
     *
     * @param int $invoice_id
     * @return mixed The result of the sql transaction
     */
    public function getInvoiceLines($invoice_id)
    {
        return $this->remote->select()->from('invoiceentry')->where('invoiceid', '=', $invoice_id)->fetchAll();
    }

    /**
     * Get all transactions from an specific invoice.
     *
     * @param int $invoice_id
     * @return mixed The result of the sql transaction
     */
    public function getInvoiceTransactions($invoice_id)
    {
        return $this->remote->select()->from('invoicetransaction')->where('invoiceid', '=', $invoice_id)->fetchAll();
    }

    /**
     * Get the currency from an specific invoice.
     *
     * @param int $invoice_id
     * @return mixed The result of the sql transaction
     */
    public function getInvoiceCurrency($invoice_id)
    {
        $customer = $this->remote->select()
            ->from('invoice')
            ->innerJoin('users', 'users.id', '=', 'invoice.customerid')
            ->where('invoice.id', '=', $invoice_id)
            ->fetch();

        return !empty($customer->currency) ? $customer->currency : 'USD';
    }
}
