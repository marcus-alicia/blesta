<?php
/**
 * Language definitions for the Transactions model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Transaction errors
$lang['Transactions.!error.client_id.exists'] = 'Invalid client ID.';
$lang['Transactions.!error.amount.format'] = 'Amount must be a number.';
$lang['Transactions.!error.currency.length'] = 'Currency must be 3 characters in length.';
$lang['Transactions.!error.type.format'] = 'Invalid transaction type.';
$lang['Transactions.!error.transaction_type_id.exists'] = 'Invalid transaction type ID.';
$lang['Transactions.!error.gateway_id.exists'] = 'Invalid gateway ID.';
$lang['Transactions.!error.transaction_id.length'] = 'The Transaction ID length may not exceed 128 characters in length.';
$lang['Transactions.!error.parent_transaction_id.length'] = 'The Parent Transaction ID length may not exceed 128 characters in length.';
$lang['Transactions.!error.reference_id.length'] = 'The Reference ID length may not exceed 128 characters in length.';
$lang['Transactions.!error.status.format'] = 'Invalid transaction status.';
$lang['Transactions.!error.transaction_id.exists'] = 'Invalid transaction ID.';
$lang['Transactions.!error.date_added.format'] = 'Transaction date can not be a future date.';
$lang['Transactions.!error.message.length'] = 'The message length may not exceed 255 characters in length.';

// Transaction applied errors
$lang['Transactions.!error.transaction_id.exists'] = 'Invalid transaction ID.';
$lang['Transactions.!error.transaction_id.currency_matches'] = "The invoice amounts could not be applied because they do not match the transaction's currency.";
$lang['Transactions.!error.invoice_id.exists'] = 'Invalid invoice ID.';
$lang['Transactions.!error.amounts.overage'] = 'One or more Amount to Pay values could not be applied to the specified invoice. Ensure that the Amount to Pay does not exceed the Amount Due on the invoice, that the invoice is open, and the sum of the Amount to Pay values do not exceed the Payment or Credit Amount.';
$lang['Transactions.!error.amounts.positive'] = 'One or more Amount to Pay values is negative. Ensure that each Amount to Pay value is zero or more.';
$lang['Transactions.!error.date.format'] = 'The date applied is invalid.';

$lang['Transactions.!error.currency.missing'] = 'Please set a currency.';
$lang['Transactions.!error.currency.mismatch'] = 'The given currency does not match at least one of the associated invoices.';


// Transaction type errors
$lang['Transactions.!error.name.empty'] = 'Please enter a name.';
$lang['Transactions.!error.name.length'] = 'Name length may not exceed 32 characters.';
$lang['Transactions.!error.type.valid'] = 'Please select a valid type.';
$lang['Transactions.!error.is_lang.format'] = 'is_lang must be a number.';
$lang['Transactions.!error.is_lang.length'] = 'is_lang length may not exceed 1 character.';
$lang['Transactions.!error.type_id.exists'] = 'Invalid transaction type ID.';

// Transaction types
// Standard types
$lang['Transactions.types.cc'] = 'Credit Card';
$lang['Transactions.types.ach'] = 'ACH';
$lang['Transactions.types.other'] = 'Other';

// Status values
$lang['Transactions.status.approved'] = 'Approved';
$lang['Transactions.status.declined'] = 'Declined';
$lang['Transactions.status.void'] = 'Void';
$lang['Transactions.status.error'] = 'Error';
$lang['Transactions.status.pending'] = 'Pending';
$lang['Transactions.status.refunded'] = 'Refunded';
$lang['Transactions.status.returned'] = 'Returned';

// Transaction debit types
$lang['Transactions.debit_types.debit'] = 'Debit';
$lang['Transactions.debit_types.credit'] = 'Credit';
