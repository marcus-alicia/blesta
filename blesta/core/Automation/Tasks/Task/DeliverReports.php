<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Tasks\Common\AbstractTask;
use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Configure;
use Language;
use Loader;
use stdClass;

/**
 * The deliver_reports automation task
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class DeliverReports extends AbstractTask
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

        Loader::loadModels($this, ['ReportManager', 'Staff']);
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
        $this->log(Language::_('Automation.task.deliver_reports.attempt', true));

        // Execute the deliver reports cron task
        $this->process($this->task->raw());

        // Log the task has completed
        $this->log(Language::_('Automation.task.deliver_reports.completed', true));
        $this->logComplete();
    }

    /**
     * Processes the task
     *
     * @param stdClass $data The deliver reports task data
     */
    private function process(stdClass $data)
    {
        // Deliver reports
        $this->deliverReportAgingInvoices($data);
        $this->deliverReportInvoiceCreation($data);
        $this->deliverReportTaxLiability($data);
    }

    /**
     * Delivers the Aging Invoices report
     *
     * @param stdClass $data The deliver reports task data
     */
    private function deliverReportAgingInvoices(stdClass $data)
    {
        // Only deliver the aging invoices report on the first of the month
        if ($this->isCurrentDay(1)) {
            $this->log(Language::_('Automation.task.deliver_reports.aging_invoices.attempt', true));

            // Fetch the report
            $vars = ['status' => 'active'];
            $path_to_file = $this->ReportManager->fetchAll('aging_invoices', $vars, 'csv', 'file');

            // Set attachment
            $attachments = [];
            if (file_exists($path_to_file)) {
                $attachments[] = [
                    'path' => $path_to_file,
                    'name' => 'aging_invoices_' . $this->date->format('Y-m-d', date('c')) . '.csv'
                ];
            } else {
                $this->log(Language::_('Automation.task.deliver_reports.aging_invoices.attachment_fail', true));
            }

            $tags = ['company' => Configure::get('Blesta.company')];
            $this->Staff->sendNotificationEmail('report_ar', $data->company_id, $tags, null, null, $attachments);

            if (($errors = $this->Staff->errors())) {
                // Error, failed to send
                $this->log(Language::_('Automation.task.deliver_reports.aging_invoices.email_error', true));

                // Reset errors
                $this->resetErrors($this->Staff);
            } else {
                // Success, email sent
                $this->log(Language::_('Automation.task.deliver_reports.aging_invoices.email_success', true));
            }

            // Remove the temp file
            @unlink($path_to_file);
        }
    }

    /**
     * Delivers the Tax Liability report
     *
     * @param stdClass $data The deliver reports task data
     */
    private function deliverReportTaxLiability(stdClass $data)
    {
        // Only deliver the tax liability report on the first of the month
        if ($this->isCurrentDay(1)) {
            $this->log(Language::_('Automation.task.deliver_reports.tax_liability.attempt', true));

            // Fetch the report
            $vars = [
                'start_date' => $this->date->modify(
                    date('c'),
                    '-1 day',
                    'Y-m-01',
                    $this->options['timezone']
                ),
                'end_date' => $this->date->modify(
                    date('c'),
                    '-1 day',
                    'Y-m-t',
                    $this->options['timezone']
                )
            ];
            $path_to_file = $this->ReportManager->fetchAll('tax_liability', $vars, 'csv', 'file');

            // Set attachment
            $attachments = [];
            if (file_exists($path_to_file)) {
                $attachments[] = [
                    'path' => $path_to_file,
                    'name' => 'tax_liability_' . $this->date->format('Y-m-d', date('c')) . '.csv'
                ];
            } else {
                $this->log(Language::_('Automation.task.deliver_reports.tax_liability.attachment_fail', true));
            }

            $tags = ['company' => Configure::get('Blesta.company')];
            $this->Staff->sendNotificationEmail(
                'report_tax_liability',
                $data->company_id,
                $tags,
                null,
                null,
                $attachments
            );

            if (($errors = $this->Staff->errors())) {
                // Error, failed to send
                $this->log(Language::_('Automation.task.deliver_reports.tax_liability.email_error', true));

                // Reset errors
                $this->resetErrors($this->Staff);
            } else {
                // Success, email sent
                $this->log(Language::_('Automation.task.deliver_reports.tax_liability.email_success', true));
            }

            // Remove the temp file
            @unlink($path_to_file);
        }
    }

    /**
     * Delivers the Invoice Creation report
     *
     * @param stdClass $data The deliver reports task data
     */
    private function deliverReportInvoiceCreation(stdClass $data)
    {
        $this->log(Language::_('Automation.task.deliver_reports.invoice_creation.attempt', true));

        // Fetch the report
        $yesterday = $this->date->modify(date('c'), '-1 day', 'Y-m-d', $this->options['timezone']);
        $vars = [
            'status' => 'active',
            'start_date' => $yesterday,
            'end_date' => $yesterday
        ];
        $path_to_file = $this->ReportManager->fetchAll('invoice_creation', $vars, 'csv', 'file');

        // Set attachment
        $attachments = [];
        if (file_exists($path_to_file)) {
            $attachments[] = [
                'path' => $path_to_file,
                'name' => 'invoice_creation_' . $yesterday . '.csv'
            ];
        } else {
            $this->log(Language::_('Automation.task.deliver_reports.invoice_creation.attachment_fail', true));
        }

        $tags = ['company' => Configure::get('Blesta.company')];
        $this->Staff->sendNotificationEmail(
            'report_invoice_creation',
            $data->company_id,
            $tags,
            null,
            null,
            $attachments
        );

        if (($errors = $this->Staff->errors())) {
            // Error, failed to send
            $this->log(Language::_('Automation.task.deliver_reports.invoice_creation.email_error', true));

            // Reset errors
            $this->resetErrors($this->Staff);
        } else {
            // Success, email sent
            $this->log(Language::_('Automation.task.deliver_reports.invoice_creation.email_success', true));
        }

        // Remove the temp file
        @unlink($path_to_file);
    }

    /**
     * {@inheritdoc}
     */
    protected function isTimeToRun()
    {
        return $this->task->canRun(date('c'));
    }
}
