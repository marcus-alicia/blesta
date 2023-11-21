<?php
/**
 * Language definitions for the Admin Billing controller/views
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Success messages
$lang['AdminBilling.!success.invoices_marked_printed'] = 'The selected invoices have been marked as printed.';
$lang['AdminBilling.!success.services_scheduled_cancel'] = 'The selected services were successfully scheduled to be canceled.';
$lang['AdminBilling.!success.services_scheduled_uncancel'] = 'The selected services are no longer scheduled to be canceled.';
$lang['AdminBilling.!success.services_pushed'] = 'The selected services were successfully pushed to the new client.';

// Error messages
$lang['AdminBilling.!error.no_invoices_selected'] = 'No invoices have been selected.';
$lang['AdminBilling.!error.invalid_passphrase'] = 'The passphrase entered is invalid.';
$lang['AdminBilling.!error.future_cancel_date'] = 'The scheduled cancellation date must not be in the past.';


// Index
$lang['AdminBilling.index.open_parethesis'] = '(';
$lang['AdminBilling.index.closed_parethesis'] = ')';
$lang['AdminBilling.index.page_title'] = 'Billing Overview';
$lang['AdminBilling.index.manage_widgets'] = 'Manage Widgets';
$lang['AdminBilling.index.customize_dashboard'] = 'Customize Overview';

$lang['AdminBilling.index.heading_actions'] = 'Action Items';
$lang['AdminBilling.index.no_actions'] = 'No action items pending.';

$lang['AdminBilling.index.action_printqueue'] = 'Items Pending Print';
$lang['AdminBilling.index.action_batch'] = 'Items Pending Batch';


// Billing dashboard
$lang['AdminBilling.updatedashboard.text_layout'] = 'Select a layout below to customize your billing overview.';

// Manage Widgets
$lang['AdminBilling.managewidgets.text_widgets'] = 'Drag widgets from the right to the left to add them to your billing overview, or from left to right to remove them.';
$lang['AdminBilling.managewidgets.text_version'] = 'ver %1$s'; // %1$s is the version number of the plugin
$lang['AdminBilling.managewidgets.text_author'] = 'Author: ';


// Invoices
$lang['AdminBilling.invoices.page_title'] = 'Billing Invoices';
$lang['AdminBilling.invoices.boxtitle_invoices'] = 'Invoices';

$lang['AdminBilling.invoices.heading_invoice'] = 'Invoice #';
$lang['AdminBilling.invoices.heading_recurinvoice'] = 'Recurring #';
$lang['AdminBilling.invoices.heading_client'] = 'Client #';
$lang['AdminBilling.invoices.heading_amount'] = 'Amount';
$lang['AdminBilling.invoices.heading_paid'] = 'Paid';
$lang['AdminBilling.invoices.heading_due'] = 'Due';
$lang['AdminBilling.invoices.heading_dateclosed'] = 'Date Closed';
$lang['AdminBilling.invoices.heading_datebilled'] = 'Date Billed';
$lang['AdminBilling.invoices.heading_datedue'] = 'Date Due';
$lang['AdminBilling.invoices.heading_options'] = 'Options';
$lang['AdminBilling.invoices.heading_term'] = 'Term';
$lang['AdminBilling.invoices.heading_duration'] = 'Duration';
$lang['AdminBilling.invoices.heading_count'] = 'Count';

$lang['AdminBilling.invoices.category_open'] = 'Open';
$lang['AdminBilling.invoices.category_drafts'] = 'Drafts';
$lang['AdminBilling.invoices.category_closed'] = 'Closed';
$lang['AdminBilling.invoices.category_voided'] = 'Voided';
$lang['AdminBilling.invoices.category_pastdue'] = 'Past Due';
$lang['AdminBilling.invoices.category_pending'] = 'Pending';
$lang['AdminBilling.invoices.category_recurring'] = 'Recurring';

$lang['AdminBilling.invoices.option_edit'] = 'Edit';
$lang['AdminBilling.invoices.option_view'] = 'View';
$lang['AdminBilling.invoices.option_pay'] = 'Pay';

$lang['AdminBilling.invoices.subtotal_w_tax'] = '%1$s +tax'; // %1$s is the sub total amount
$lang['AdminBilling.invoices.term_day'] = '%1$s day'; // %1$s is the term (an integer)
$lang['AdminBilling.invoices.term_week'] = '%1$s week'; // %1$s is the term (an integer)
$lang['AdminBilling.invoices.term_month'] = '%1$s month'; // %1$s is the term (an integer)
$lang['AdminBilling.invoices.term_year'] = '%1$s year'; // %1$s is the term (an integer)
$lang['AdminBilling.invoices.term_day_plural'] = '%1$s days'; // %1$s is the term (an integer)
$lang['AdminBilling.invoices.term_week_plural'] = '%1$s weeks'; // %1$s is the term (an integer)
$lang['AdminBilling.invoices.term_month_plural'] = '%1$s months'; // %1$s is the term (an integer)
$lang['AdminBilling.invoices.term_year_plural'] = '%1$s years'; // %1$s is the term (an integer)

$lang['AdminBilling.invoices.duration_finite'] = '%1$s times'; // %1$s is the number of times the recurring invoice will be created
$lang['AdminBilling.invoices.duration_infinite'] = '∞';

$lang['AdminBilling.invoices.no_results'] = 'There are no invoices with this status.';


// Quotations
$lang['AdminBilling.quotations.page_title'] = 'Billing Quotes';
$lang['AdminBilling.quotations.boxtitle_quotations'] = 'Quotes';

$lang['AdminBilling.quotations.heading_quotation'] = 'Quote #';
$lang['AdminBilling.quotations.heading_client'] = 'Client #';
$lang['AdminBilling.quotations.heading_title'] = 'Title';
$lang['AdminBilling.quotations.heading_staff'] = 'Quoted By';
$lang['AdminBilling.quotations.heading_subtotal'] = 'Subtotal';
$lang['AdminBilling.quotations.heading_total'] = 'Amount';
$lang['AdminBilling.quotations.heading_date_created'] = 'Creation Date';
$lang['AdminBilling.quotations.heading_date_expires'] = 'Expiration Date';
$lang['AdminBilling.quotations.heading_options'] = 'Options';

$lang['AdminBilling.quotations.option_edit'] = 'Edit';
$lang['AdminBilling.quotations.option_view'] = 'View';
$lang['AdminBilling.quotations.option_invoice'] = 'Create Invoice';
$lang['AdminBilling.quotations.option_approve'] = 'Approve';

$lang['AdminBilling.quotations.confirm_approve'] = 'Are you sure you want to approve this quote?';

$lang['AdminBilling.quotations.no_results'] = 'There are no quotes with this status.';


// Quotation Invoices
$lang['AdminBilling.quotationinvoices.headingexpand_invoice'] = 'Invoice #';
$lang['AdminBilling.quotationinvoices.headingexpand_amount'] = 'Amount';
$lang['AdminBilling.quotationinvoices.headingexpand_paid'] = 'Paid';
$lang['AdminBilling.quotationinvoices.headingexpand_date_billed'] = 'Date Billed';
$lang['AdminBilling.quotationinvoices.headingexpand_options'] = 'Options';

$lang['AdminBilling.quotationinvoices.option_view'] = 'View';

$lang['AdminBilling.quotationinvoices.invoices_no_results'] = 'There are no invoices associated to this quote.';


// Services
$lang['AdminBilling.services.page_title'] = 'Billing Services';
$lang['AdminBilling.services.boxtitle_services'] = 'Services';

$lang['AdminBilling.services.heading_client'] = 'Client #';
$lang['AdminBilling.services.heading_package'] = 'Package';
$lang['AdminBilling.services.heading_label'] = 'Label';
$lang['AdminBilling.services.heading_term'] = 'Term';
$lang['AdminBilling.services.heading_dateadded'] = 'Date Added';
$lang['AdminBilling.services.heading_daterenews'] = 'Date Renews';
$lang['AdminBilling.services.heading_datesuspended'] = 'Date Suspended';
$lang['AdminBilling.services.heading_datecanceled'] = 'Date Canceled';
$lang['AdminBilling.services.heading_options'] = 'Options';
$lang['AdminBilling.services.option_manage'] = 'Manage';
$lang['AdminBilling.services.option_delete'] = 'Delete';
$lang['AdminBilling.services.confirm_delete'] = 'Are you sure you want to delete this service?';

$lang['AdminBilling.services.category_active'] = 'Active';
$lang['AdminBilling.services.category_canceled'] = 'Canceled';
$lang['AdminBilling.services.category_suspended'] = 'Suspended';
$lang['AdminBilling.services.category_pending'] = 'Pending';
$lang['AdminBilling.services.category_in_review'] = 'In Review';
$lang['AdminBilling.services.category_scheduled_cancellation'] = 'Scheduled';

$lang['AdminBilling.services.text_never'] = 'Never';
$lang['AdminBilling.services.no_results'] = 'There are no services with this status.';

$lang['AdminBilling.services.recurring_term'] = '%1$s %2$s @ %3$s'; // %1$s is the service term length (number), %2$s is the service period, %3$s is the formatted service renewal price
$lang['AdminBilling.services.action.schedule_cancellation'] = 'Schedule Cancellation';
$lang['AdminBilling.services.action.field_action_type_term'] = 'End of Term';
$lang['AdminBilling.services.action.field_action_type_date'] = 'Specific Date';
$lang['AdminBilling.services.action.field_action_type_none'] = 'Do not cancel';
$lang['AdminBilling.services.action.push_to_client'] = 'Push to Client';
$lang['AdminBilling.services.action.field_client'] = 'Client:';
$lang['AdminBilling.services.field_actionsubmit'] = 'Submit';


// Service info
$lang['AdminBilling.serviceinfo.no_results'] = 'This service has no details.';
$lang['AdminBilling.serviceinfo.cancellation_reason'] = 'Reason for Cancellation: %1$s'; // %1$s is the reason this service was canceled


// Transactions
$lang['AdminBilling.transactions.page_title'] = 'Billing Transactions';
$lang['AdminBilling.transactions.boxtitle_transactions'] = 'Transactions';

$lang['AdminBilling.transactions.heading_client'] = 'Client #';
$lang['AdminBilling.transactions.heading_type'] = 'Type';
$lang['AdminBilling.transactions.heading_amount'] = 'Amount';
$lang['AdminBilling.transactions.heading_credit'] = 'Credited';
$lang['AdminBilling.transactions.heading_applied'] = 'Applied';
$lang['AdminBilling.transactions.heading_number'] = 'Number';
$lang['AdminBilling.transactions.heading_reference_id'] = 'Reference #';
$lang['AdminBilling.transactions.heading_date'] = 'Date';
$lang['AdminBilling.transactions.heading_options'] = 'Options';

$lang['AdminBilling.transactions.category_approved'] = 'Approved';
$lang['AdminBilling.transactions.category_declined'] = 'Declined';
$lang['AdminBilling.transactions.category_voided'] = 'Voided';
$lang['AdminBilling.transactions.category_error'] = 'Error';
$lang['AdminBilling.transactions.category_pending'] = 'Pending';
$lang['AdminBilling.transactions.category_refunded'] = 'Refunded';
$lang['AdminBilling.transactions.category_returned'] = 'Returned';

$lang['AdminBilling.transactions.option_edit'] = 'Edit';

$lang['AdminBilling.transactions.no_results'] = 'There are no transactions with this status.';

$lang['AdminBilling.transactions.headingexpand_invoice'] = 'Invoice';
$lang['AdminBilling.transactions.headingexpand_amount'] = 'Amount';
$lang['AdminBilling.transactions.headingexpand_appliedon'] = 'Applied On';
$lang['AdminBilling.transactions.applied_no_results'] = 'This transaction has not been applied to any invoices.';

$lang['AdminBilling.invoices.headingexpand_paymenttype'] = 'Payment Type';
$lang['AdminBilling.invoices.headingexpand_amount'] = 'Amount';
$lang['AdminBilling.invoices.headingexpand_applied'] = 'Applied';
$lang['AdminBilling.invoices.headingexpand_appliedon'] = 'Applied On';
$lang['AdminBilling.invoices.headingexpand_options'] = 'Options';
$lang['AdminBilling.invoices.applied_no_results'] = 'This invoice has no transactions applied to it.';
$lang['AdminBilling.invoices.text_edit'] = 'Edit';


// Print Queue
$lang['AdminBilling.printqueue.page_title'] = 'Billing Print Queue';
$lang['AdminBilling.printqueue.category_to_print'] = 'Print';
$lang['AdminBilling.printqueue.category_printed'] = 'Previously Printed';

$lang['AdminBilling.printqueue.boxtitle_printqueue'] = 'Print Queue';
$lang['AdminBilling.printqueue.no_results_printed'] = 'There are no previously printed invoices.';
$lang['AdminBilling.printqueue.no_results_to_print'] = 'There are no invoices queued to be printed.';

$lang['AdminBilling.printqueue.heading_invoice'] = 'Invoice #';
$lang['AdminBilling.printqueue.heading_client'] = 'Client #';
$lang['AdminBilling.printqueue.heading_amount'] = 'Amount';
$lang['AdminBilling.printqueue.heading_paid'] = 'Paid';
$lang['AdminBilling.printqueue.heading_due'] = 'Due';
$lang['AdminBilling.printqueue.heading_datebilled'] = 'Date Billed';
$lang['AdminBilling.printqueue.heading_datedue'] = 'Date Due';
$lang['AdminBilling.printqueue.heading_deliverydatesent'] = 'Date Sent';

$lang['AdminBilling.printqueue.text_printsubmit'] = 'Print';
$lang['AdminBilling.printqueue.text_marksubmit'] = 'Mark Printed';


// Batch
$lang['AdminBilling.batch.page_title'] = 'Billing Batch';
$lang['AdminBilling.batch.boxtitle_batch'] = 'Batch';
$lang['AdminBilling.batch.no_passphrase'] = 'Manual batch processing is not enabled.';

$lang['AdminBilling.batch.text_description'] = 'Enter your passphrase to manually batch process all invoices scheduled for auto debit with a locally stored payment account.';
$lang['AdminBilling.batch.field_passphrase'] = 'Passphrase';

$lang['AdminBilling.batch.text_batchsubmit'] = 'Batch Process';
