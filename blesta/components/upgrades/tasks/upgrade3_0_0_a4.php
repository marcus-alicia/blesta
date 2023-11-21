<?php
/**
 * Upgrades to version 3.0.0.a4
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_0_0A4 extends UpgradeUtil
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
            'alterCustomFields',
            'alterInvoices',
            'updateInvoiceTotals',
            'updateEmails'
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
     * Alter custom fields
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function alterCustomFields($undo = false)
    {
        if ($undo) {
            $this->Record->query(
                "ALTER TABLE `client_fields`
                    CHANGE `show_client` `show_client` TINYINT( 1 ) SIGNED NOT NULL DEFAULT '0';
				ALTER TABLE `client_fields` DROP `read_only`;"
            );
        } else {
            $this->Record->query(
                "ALTER TABLE `client_fields`
                    CHANGE `show_client` `show_client` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';
				ALTER TABLE `client_fields` ADD `read_only` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';"
            );
        }
    }

    /**
     * Alter invoices table
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function alterInvoices($undo = false)
    {
        if ($undo) {
            $this->Record->query(
                'ALTER TABLE `invoices` DROP `subtotal`,
				DROP `total`,
				DROP `paid`'
            );
        } else {
            $this->Record->query(
                "ALTER TABLE `invoices` ADD `subtotal` DECIMAL( 12, 4 ) NOT NULL DEFAULT '0.0000' AFTER `status` ,
				ADD `total` DECIMAL( 12, 4 ) NOT NULL DEFAULT '0.0000' AFTER `subtotal` ,
				ADD `paid` DECIMAL( 12, 4 ) NOT NULL DEFAULT '0.0000' AFTER `total`;
				ALTER TABLE `invoices` ADD INDEX ( `date_billed` , `status` );"
            );
        }
    }

    /**
     * Update invoice totals
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function updateInvoiceTotals($undo = false)
    {
        if ($undo) {
            // Nothing to undo
        } else {
            Loader::loadModels($this, ['Invoices']);
            $invoices = $this->Record->select(['invoices.id'])
                ->from('invoices')
                ->where('subtotal', '=', '0.0000')
                ->fetchAll();

            foreach ($invoices as $invoice) {
                // Fetch the totals
                $presenter = $this->Invoices->getPresenter($invoice->id);
                $totals = $presenter->totals();

                $fields = [
                    'subtotal' => $totals->subtotal,
                    'total' => $totals->total,
                    'paid' => $this->Invoices->getPaid($invoice->id)
                ];

                $this->Record->where('id', '=', $invoice->id)->update('invoices', $fields);
            }
        }
    }

    /**
     * UPdate emails
     *
     * @param bool $undo True to undo the change, false to perform the change
     */
    private function updateEmails($undo = false)
    {
        if ($undo) {
            // No need to undo
        } else {
            $fields = [
                'text' => "Hi {contact.first_name},

An invoice has been created for your account and is attached to this email in PDF format.
{% for invoice in invoices %}
Invoice #: {invoice.id_code}

{% if autodebit %}{% if invoice.autodebit_date_formatted %}Auto debit is enabled for your account, so we'll automatically process the card you have on file on {invoice.autodebit_date_formatted} unless payment has been applied sooner.{% else %}If you would like us to automatically charge your card, login to your account at http://{client_url} to set up auto debit.{% endif %}{% else %}If you would like us to automatically charge your card, login to your account at http://{client_url} to set up auto debit.{% endif %}

Pay Now, visit http://{invoice.payment_url} (No login required)
{% endfor %}
If you have any questions about your invoice, please let us know!",
                'html' => "<p>
	Hi {contact.first_name},<br />
	<br />
	An invoice has been created for your account and is attached to this email in PDF format.<br />
	{% for invoice in invoices %}<br />
	Invoice #:<strong> {invoice.id_code}</strong></p>
<p>
	{% if autodebit %}{% if invoice.autodebit_date_formatted %}Auto debit is enabled for your account, so we'll automatically process the card you have on file on <strong>{invoice.autodebit_date_formatted}</strong> unless payment has been applied sooner.{% else %}If you would like us to automatically charge your card, login to your account at <a href=\"http://{client_url}\">http://{client_url}</a> to set up auto debit.{% endif %}{% else %}If you would like us to automatically charge your card, login to your account at <a href=\"http://{client_url}\">http://{client_url}</a> to set up auto debit.{% endif %}<br />
	<br />
	<a href=\"http://{invoice.payment_url}\">Pay Now</a> (No login required)<br />
	{% endfor %}<br />
If you have any questions about your invoice, please let us know!</p>"
            ];

            $this->Record->where('emails.email_group_id', '=', 9)
                ->where('emails.lang', '=', 'en_us')
                ->update('emails', $fields);
        }
    }
}
