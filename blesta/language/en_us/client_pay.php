<?php
/**
 * Language definitions for the Client Pay controller
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Success
$lang['ClientPay.!success.payment_processed'] = 'The payment was successfully processed for %1$s. Transaction Number: %2$s'; // %1$s is the payment amount, %2$s is the transaction number
$lang['ClientPay.!success.credit_full_processed'] = 'The available credits have been applied.';
$lang['ClientPay.!success.credit_partial_processed'] = 'The available credits have been applied, but a balance still exists. Please complete this transaction by paying the remainder below.';
$lang['ClientPay.!success.invoice_paid'] = 'This invoice has previously been paid.';

// Info
$lang['ClientPay.!info.unverified_email'] = "A link was sent to the email address you provided. Please click the link in the email to verify your email address before proceeding to make payment.";
$lang['ClientPay.!info.unverified_email_button'] = 'Resend Verification Email';
$lang['ClientPay.!info.ach_verification'] = 'You need to verify this account before you can use it to make a payment.';
$lang['ClientPay.!info.ach_verification_redirect'] = 'You need to verify this account before you can use it to make a payment. This process will redirect you away from the current page.';


// Errors
$lang['ClientPay.!error.invalid_details'] = 'Please select a payment option.';
$lang['ClientPay.!error.invalid_amount'] = 'Other payment amounts may not be negative.';
$lang['ClientPay.!error.payment_amounts'] = 'Please select invoices to pay or enter another payment amount.';
$lang['ClientPay.!error.invoice_voided'] = 'This invoice has been voided and cannot be paid.';
$lang['ClientPay.!error.payment_authorize'] = 'Payment could not be authorized for the selected payment account.  Please select a different account or try again.';


// Index (pay process)
$lang['ClientPay.index.page_title'] = 'Client %1$s Pay'; // %1$s is the client ID

$lang['ClientPay.index.boxtitle_pay'] = 'Make Payment';
$lang['ClientPay.index.field_credit'] = 'Other Payment Amount';
$lang['ClientPay.index.field_submit'] = 'Continue';


// Method
$lang['ClientPay.method.page_title'] = 'Client %1$s Payment Method'; // %1$s is the client ID

$lang['ClientPay.method.boxtitle_method'] = 'Make Payment';
$lang['ClientPay.method.field_useaccount'] = 'Use Payment Account';
$lang['ClientPay.method.field_newdetails'] = 'New Payment Details';
$lang['ClientPay.method.heading_other_payment_options'] = 'Other Payment Options';
$lang['ClientPay.method.heading_payment_options'] = 'Payment Options';
$lang['ClientPay.method.field_paymentaccount'] = '%1$s %2$s - %3$s x%4$s'; // %1$s is the account first name, %2$s is the account last name, %3$s is the account type (card type or bank account type), %4$s is the last 4 of the account
$lang['ClientPay.method.field_paymentaccount_cc'] = 'Credit Card Accounts';
$lang['ClientPay.method.field_paymentaccount_autodebit'] = '(Auto Debit) %1$s %2$s - %3$s x%4$s'; // %1$s is the account first name, %2$s is the account last name, %3$s is the account type (card type or bank account type), %4$s is the last 4 of the account
$lang['ClientPay.method.field_paymentaccount_ach'] = 'ACH Accounts';
$lang['ClientPay.method.field_apply_credit'] = 'Apply Credit (%1$s Available)'; // %1$s is the amount of available credit that may be used
$lang['ClientPay.method.field_submit'] = 'Review and Confirm';
$lang['ClientPay.method.field_credit_submit'] = 'Apply Credit and Continue';
$lang['ClientPay.method.no_options'] = 'There are no payment options available.';

$lang['ClientPay.method.heading_invoice'] = 'Invoice #%1$s'; // %1$s is the invoice ID code
$lang['ClientPay.method.invoice_description'] = 'Description';
$lang['ClientPay.method.invoice_quantity'] = 'Quantity';
$lang['ClientPay.method.invoice_amount'] = 'Amount';


// Invoices
$lang['ClientPay.multipleinvoices.text_edit_amounts'] = 'Make Partial Payment Instead';
$lang['ClientPay.multipleinvoices.text_amount'] = 'Amount to Pay';
$lang['ClientPay.multipleinvoices.text_due'] = 'Amount Due';
$lang['ClientPay.multipleinvoices.text_invoice'] = 'Invoice #';
$lang['ClientPay.multipleinvoices.text_datedue'] = 'Date Due';
$lang['ClientPay.multipleinvoices.no_results'] = 'There are no invoices in this currency.';


// Confirm
$lang['ClientPay.confirm.page_title'] = 'Client %1$s Confirm Payment'; // %1$s is the client ID

$lang['ClientPay.confirm.boxtitle_confirm'] = 'Confirm Payment';
$lang['ClientPay.confirm.payment_details'] = 'Payment Details';
$lang['ClientPay.confirm.account_info'] = '%1$s (%2$s) ending in %3$s'; // %1$s is the account type (Credit Card or ACH), %2$s is the type (Savings, Checking, MasterCard, etc.) and %3$s is the last 4 digits of the account
$lang['ClientPay.confirm.account_info_type'] = '%1$s'; // %1$s is the account type (Credit Card or ACH)
$lang['ClientPay.confirm.account_exp'] = 'expires %1$s'; // %1$s is the date the credit card expires
$lang['ClientPay.confirm.total'] = 'Total:';
$lang['ClientPay.confirm.field_submit'] = 'Submit Payment';
$lang['ClientPay.confirm.field_edit'] = 'Edit Payment';

$lang['ClientPay.confirm.description_invoice'] = 'Invoice #%1$s'; // %1$s is the invoice number
$lang['ClientPay.confirm.description_invoice_separator'] = ',';
$lang['ClientPay.confirm.description_invoice_number'] = '#%1$s'; // %1$s is the invoice number
$lang['ClientPay.confirm.description_credit'] = 'Payment Credit';


// Received
$lang['ClientPay.received.page_title'] = 'Client %1$s Payment Received'; // %1$s is the client ID
$lang['ClientPay.received.boxtitle_received'] = 'Thank You!';
$lang['ClientPay.received.statement'] = 'Your payment is being processed.';
$lang['ClientPay.received.transaction_id'] = 'Transaction ID';
$lang['ClientPay.received.amount'] = 'Amount';
$lang['ClientPay.received.invoices'] = 'Invoice #';
$lang['ClientPay.received.text_try_again'] = 'Try Again';


// Set Contact view
$lang['ClientPay.setcontactview.text_none'] = 'None';
