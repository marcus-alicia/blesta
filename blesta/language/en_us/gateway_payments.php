<?php
/**
 * Language definitions for the Gateway Payments component
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

$lang['GatewayPayments.!error.gateway.exists'] = 'The gateway does not exist or is not enabled.';
$lang['GatewayPayments.!error.transaction_id.exists'] = 'The transaction could not be found for that client.';
$lang['GatewayPayments.!error.account_id.exists'] = 'The payment account could not be found for that client.';
$lang['GatewayPayments.!error.contact_id.exists'] = 'The contact could not be found for that client.';
$lang['GatewayPayments.!error.type.valid'] = 'The payment type is not supported for this gateway.';
$lang['GatewayPayments.!error.type.instance'] = 'The gateway does not support payments in this manner.';
$lang['GatewayPayments.!error.amount.valid'] = 'Amount must be greater than zero.';
$lang['GatewayPayments.!error.reference_id.store'] = 'The payment gateway could not store the payment account.';
$lang['GatewayPayments.!error.reference_id.update'] = 'The payment gateway could not update the payment account.';
$lang['GatewayPayments.!error.reference_id.verify'] = 'The payment gateway could not verify the payment account.';
$lang['GatewayPayments.!error.reference_id.remove'] = 'The payment gateway could not remove the payment account.';
$lang['GatewayPayments.!error.response_status'] = 'The payment gateway returned an unexpected response.';
$lang['GatewayPayments.!error.gateway.declined'] = 'The payment was declined.';
$lang['GatewayPayments.!error.gateway.error'] = 'The payment gateway returned an error when processing the request.';
$lang['GatewayPayments.!error.type.invalid'] = 'The account type must be "ach" or "cc".';
