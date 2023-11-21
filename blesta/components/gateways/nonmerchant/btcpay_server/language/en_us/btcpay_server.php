<?php
/**
 * en_us language for the BTCPay Server gateway.
 */
// Basics
$lang['BtcpayServer.name'] = 'BTCPay (Bitcoin)';
$lang['BtcpayServer.description'] = 'BTCPayServer is a self-hosted, Bitcoin payment gateway with no fees.';


// Errors
$lang['BtcpayServer.!error.server_url.valid'] = 'Server URL invalid.';
$lang['BtcpayServer.!error.transaction_speed.valid'] = 'Please select a valid transaction speed.';
$lang['BtcpayServer.!error.store_id.valid'] = 'Store ID invalid.';
$lang['BtcpayServer.!error.api_key.valid'] = 'API Key invalid.';
$lang['BtcpayServer.!error.failed.response'] = 'The transaction could not be processed.';
$lang['BtcpayServer.!error.payment.invalid'] = 'The transaction is invalid and could not be processed.';
$lang['BtcpayServer.!error.payment.expired'] = 'The transaction has expired and could not be processed.';
$lang['BtcpayServer.!error.webhook_secret.valid'] = 'You must enter a valid Webhook Secret.';


// Settings
$lang['BtcpayServer.meta.server_url'] = 'Server URL';
$lang['BtcpayServer.meta.store_id'] = 'Store ID';
$lang['BtcpayServer.meta.api_key'] = 'API Key';
$lang['BtcpayServer.meta.transaction_speed'] = 'Transaction Speed';
$lang['BtcpayServer.meta.webhook_secret'] = 'Webhook Secret';


$lang['BtcpayServer.transaction.speed.high'] = 'High';
$lang['BtcpayServer.transaction.speed.medium'] = 'Medium';
$lang['BtcpayServer.transaction.speed.low'] = 'Low';

$lang['BtcpayServer.webhook'] = 'BTCPay Server Webhook';
$lang['BtcpayServer.webhook_note'] = 'Before you start using this gateway you must configure the following url as a Webhook in your BTCPay server.';


// Process
$lang['BtcpayServer.buildprocess.submit'] = 'Submit Payment';
