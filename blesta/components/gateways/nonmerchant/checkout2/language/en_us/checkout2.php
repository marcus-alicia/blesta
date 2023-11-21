<?php
// Gateway name
$lang['Checkout2.name'] = '2Checkout';
$lang['Checkout2.description'] = '2Checkout.com is an online payment processing service that helps you accept credit cards, PayPal, and debit cards. Over 17,000 companies in over 180 countries use 2Checkout';

// Settings
$lang['Checkout2.api_version'] = 'API Version';
$lang['Checkout2.vendor_id'] = 'Vendor Account Number';
$lang['Checkout2.merchant_code'] = 'Merchant Code';
$lang['Checkout2.secret_key'] = 'Secret Key';
$lang['Checkout2.secret_word'] = 'Secret Word';
$lang['Checkout2.buy_link_secret_word'] = 'Buy Link Secret Word';
$lang['Checkout2.api_username'] = 'API Username';
$lang['Checkout2.api_username_note'] = 'This, as well as the API Password, are required in order to process refunds through 2Checkout.';
$lang['Checkout2.api_password'] = 'API Password';
$lang['Checkout2.sandbox'] = 'Sandbox';
$lang['Checkout2.test_mode'] = 'Test Mode';

// Refund
$lang['Checkout2.refund.comment'] = 'Initiating a refund for %1$s.'; // %1$s is the refund amount

// Process form
$lang['Checkout2.buildprocess.submit'] = 'Pay with 2Checkout';

// Get API Versions
$lang['Checkout2.getapiversions.v1'] = 'Version 1 (Legacy)';
$lang['Checkout2.getapiversions.v5'] = 'Version 5';

// Errors
$lang['Checkout2.!error.api_version.valid'] = 'Please enter a valid API version.';
$lang['Checkout2.!error.vendor_id.empty'] = 'Please enter your Vendor Account Number.';
$lang['Checkout2.!error.merchant_code.empty'] = 'Please enter your Merchant Code.';
$lang['Checkout2.!error.secret_word.empty'] = 'Please enter your Secret Word.';
$lang['Checkout2.!error.buy_link_secret_word.empty'] = 'Please enter your Buy Link Secret Word.';
$lang['Checkout2.!error.secret_key.empty'] = 'Please enter your Secret Key.';
$lang['Checkout2.!error.test_mode.valid'] = 'Test mode must be set to either \'true\' or \'false\'.';
$lang['Checkout2.!error.sandbox.valid'] = 'Sandbox must be set to either \'true\' or \'false\'.';
$lang['Checkout2.!error.key.valid'] = 'The key used to verify this sale originated from 2Checkout is invalid.';
$lang['Checkout2.!error.hash.valid'] = 'The hash used to verify this sale originated from 2Checkout is invalid.';
$lang['Checkout2.!error.credit_card_processed.completed'] = 'The transaction was not processed successfully.';
$lang['Checkout2.!error.sid.valid'] = 'The Vendor Account Number does not match the account number provided by the transaction.';
