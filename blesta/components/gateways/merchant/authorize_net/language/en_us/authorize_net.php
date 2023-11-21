<?php
$lang['Authorize_net.name'] = 'Authorize.Net';
$lang['Authorize_net.description'] = 'Reliable and fast credit card and ACH processing';
$lang['Authorize_net.login_id'] = 'Login ID';
$lang['Authorize_net.transaction_key'] = 'Transaction Key';
$lang['Authorize_net.api'] = 'Payment API';
$lang['Authorize_net.test_mode'] = 'Test Mode';
$lang['Authorize_net.test_mode_note'] = 'The test mode feature is only supported by AIM. To test CIM transactions you must enable test mode from within your Authorize.net account.';
$lang['Authorize_net.dev_mode'] = 'Developer Mode';
$lang['Authorize_net.dev_mode_note'] = 'Enabling this option will post transactions to the Authorize.net developer environment. You must have a developer test account in order to use this environment.';

$lang['Authorize_net.apis_aim'] = 'AIM (default)';
$lang['Authorize_net.apis_cim'] = 'CIM (must be enabled by Authorize.Net)';

$lang['Authorize_net.validation_mode'] = 'Payment Account Validation Mode';
$lang['Authorize_net.validation_note'] = "This controls what type of validation is performed when a payment account is stored using CIM. 'None' performs no additional validation. 'Test' issues a test transaction that does not appear on the customer's statement but will generate an email to the merchant. 'Live' processes a $0.00 or $0.01 transaction that is immediately voided. Consult your Merchant Account Provider before setting this value to 'Live' as you may be subject to fees.";
$lang['Authorize_net.validation_modes_none'] = 'None';
$lang['Authorize_net.validation_modes_test'] = 'Test';
$lang['Authorize_net.validation_modes_live'] = 'Live';

$lang['Authorize_net.!error.login_id.format'] = 'Login ID should be no more than 20 characters and may not be empty.';
$lang['Authorize_net.!error.transaction_key.format'] = 'The transaction key must be 16 characters in length.';
$lang['Authorize_net.!error.test_mode.valid'] = 'Test mode must be set to "true" if given.';
$lang['Authorize_net.!error.dev_mode.valid'] = 'Developer mode must be set to "true" if given.';
$lang['Authorize_net.!error.card_number.missing'] = 'The expiration date cannot be updated without the full card number.';
