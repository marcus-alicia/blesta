<?php
/**
 * Language definitions for the Client Quotations controller/views
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Error
$lang['ClientQuotations.!error.password_mismatch'] = 'The password does not match your log in password.';


// Success
$lang['ClientQuotations.!success.approved_quotation'] = 'The quotation has been approved successfully.';


// Index
$lang['ClientQuotations.index.page_title'] = 'Client #%1$s Quotations'; // %1$s is the client ID number

$lang['ClientQuotations.index.category_pending'] = 'Pending';
$lang['ClientQuotations.index.category_approved'] = 'Approved';
$lang['ClientQuotations.index.category_expired'] = 'Expired';

$lang['ClientQuotations.index.boxtitle_quotations'] = 'Quotations';
$lang['ClientQuotations.index.heading_quotation'] = 'Quotation #';
$lang['ClientQuotations.index.heading_title'] = 'Title';
$lang['ClientQuotations.index.heading_subtotal'] = 'Subtotal';
$lang['ClientQuotations.index.heading_total'] = 'Amount';
$lang['ClientQuotations.index.heading_date_created'] = 'Date Created';
$lang['ClientQuotations.index.heading_date_expires'] = 'Date Expires';
$lang['ClientQuotations.index.heading_options'] = 'Options';
$lang['ClientQuotations.index.option_view'] = 'View';
$lang['ClientQuotations.index.option_approve'] = 'Approve';

$lang['ClientQuotations.index.no_results'] = 'You have no %1$s Quotations.'; // %1$s is the language for the quotation category type


// Approve
$lang['ClientQuotations.approve.heading_approve'] = 'Approve';
$lang['ClientQuotations.approve.field_password'] = 'Log In Password to Approve';
$lang['ClientQuotations.approve.field_cancel'] = 'Cancel, do not approve';
$lang['ClientQuotations.approve.field_submit'] = 'Approve';


// Invoices
$lang['ClientQuotations.invoices.heading_invoice'] = 'Invoice #';
$lang['ClientQuotations.invoices.heading_amount'] = 'Amount';
$lang['ClientQuotations.invoices.heading_paid'] = 'Paid';
$lang['ClientQuotations.invoices.heading_date_billed'] = 'Date Billed';

$lang['ClientQuotations.invoices.no_results'] = 'This quotation has no invoices associated to it.';
