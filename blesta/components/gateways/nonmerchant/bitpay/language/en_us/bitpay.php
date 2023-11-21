<?php

$lang['Bitpay.name'] = 'Bitpay';
$lang['Bitpay.description'] = 'A Bitcoin and crypto currency payment service provider';
$lang['Bitpay.pairing_code'] = 'Pairing Code';
$lang['Bitpay.transaction_speed'] = 'Transaction Speed';
$lang['Bitpay.transaction.speed.high'] = 'High';
$lang['Bitpay.transaction.speed.medium'] = 'Medium';
$lang['Bitpay.transaction.speed.low'] = 'Low';
$lang['Bitpay.pairing_code_note'] = 'Go to the BitPay dashboard to the "Payment Tools > API Tokens" section and approve the generated token using this pairing code "%1$s", then save the gateway settings by clicking on "Update Settings".'; // %1$s is the paring code
$lang['Bitpay.transaction_speed_note'] = "The transaction speed determines how quickly an invoice (in Bitpay) can be considered 'confirmed'.";
$lang['Bitpay.test_mode'] = 'Test Mode';
$lang['Bitpay.test_mode_note'] = 'If checked, the gateway will send payment requests to the test URL. Changing this will disconnect BitPay and a new token will be generated.';
$lang['Bitpay.connect.button'] = 'Connect with BitPay';
$lang['Bitpay.disconnect.button'] = 'Disconnect from BitPay';
$lang['Bitpay.buildprocess.submit'] = 'Pay using Bitcoin';

// Error
$lang['Bitpay.!error.transaction_speed.valid'] = 'Please select a valid transaction speed.';
$lang['Bitpay.!error.token.valid'] = 'The token is invalid or the pairing code hasn\'t been authorized in BitPay.';
$lang['Bitpay.!error.failed.response'] = 'The transaction could not be processed.';
$lang['Bitpay.!error.payment.invalid'] = 'The transaction is invalid and could not be processed.';
$lang['Bitpay.!error.payment.expired'] = 'The transaction has expired and could not be processed.';
