<?php
// Errors
$lang['Eway.!error.customer_id.empty'] = 'Please enter your eWAY Customer ID.';
$lang['Eway.!error.customer_id.length'] = 'The Customer ID may not exceed 8 characters in length.';
$lang['Eway.!error.developer_mode.valid'] = 'Developer mode must be set to "true" if given.';
$lang['Eway.!error.test_mode.valid'] = 'Test mode must be set to "true" if given.';

$lang['Eway.!error.refund_password.empty'] = 'A refund password must be set in order to process refunds through eWAY.';
$lang['Eway.!error.libxml_required'] = 'The libxml and simplexml extensions are required for this gateway.';
$lang['Eway.!error.invalid_xml'] = 'The gateway responded with invalid XML.';

$lang['Eway.name'] = 'eWAY';
$lang['Eway.description'] = 'eWAY is 25% of the Australian online market and trades in 5 countries. Processes billions of dollars in payments every year across the globe for tens of thousands of businesses';

// Settings
$lang['Eway.customer_id'] = 'Customer ID';
$lang['Eway.refund_password'] = 'Refund Password';
$lang['Eway.developer_mode'] = 'Developer Mode';
$lang['Eway.test_mode'] = 'Test Mode';

$lang['Eway.tooltip_refund_password'] = 'Enter the Refund Password you have set in your eWAY account. This is not your eWAY account password. A refund password must be set in order to process refunds through eWAY.';
$lang['Eway.tooltip_developer_mode'] = 'Enabling this option will post transactions to the eWAY sandbox environment. You must have a sandbox test account in order to use this environment.';
$lang['Eway.tooltip_test_mode'] = 'Test mode will use an eWAY test account with a test credit card number regardless of what you enter. For more information regarding test mode in eWAY please consult the manual.';
