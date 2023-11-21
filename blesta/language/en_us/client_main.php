<?php
/**
 * Language definitions for the Client Main controller/views
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Success messages
$lang['ClientMain.!success.client_updated'] = 'Your account information has been successfully updated.';
$lang['ClientMain.!success.invoice_method_updated'] = 'Your invoice method has been successfully updated. All future invoices will be delivered to you via %1$s.'; // %1$s is the clients invoice method (e.g. Email, Paper, etc.)


// Info message
$lang['ClientMain.!info.invoice_due_title'] = 'Welcome back, %1$s!'; // %1$s is the client's first name
$lang['ClientMain.!info.invoice_due_button'] = 'Make Payment';
$lang['ClientMain.!info.invoice_past_due_button'] = 'Pay Past Due';
$lang['ClientMain.!info.invoice_due_text'] = 'Your account has an outstanding balance of %1$s. Please make a payment at your earliest convenience.'; // %1$s is the total amount due for this client
$lang['ClientMain.!info.invoice_due_past_due_text'] = 'Your account has an outstanding balance of %1$s, of which %2$s is past due. Please make a payment at your earliest convenience.'; // %1$s is the total amount due for this client, %2$s is the total amount past due for this client
$lang['ClientMain.!info.invoice_due_other_currencies'] = 'You have an outstanding balance in other currencies as well.';
$lang['ClientMain.!info.service_in_review'] = 'The following services are pending review by staff. Once approved, they will be activated:';
$lang['ClientMain.!info.service_name'] = '%1$s - %2$s'; // %1$s is the package name, %2$s is the service name
$lang['ClientMain.!info.additional_service'] = '...and 1 additional service.';
$lang['ClientMain.!info.additional_services'] = '...and %1$s additional services.'; // %1$s is the number of additional services
$lang['ClientMain.!info.email_pending_verification'] = 'Your email address is currently unverified. A verification email has been sent to %1$s.'; // %1$s is the email address to be verified
$lang['ClientMain.!info.email_pending_verification_button'] = 'Resend Verification Email';
$lang['ClientMain.!info.no_contacts'] = 'There are no additional contacts.';
$lang['ClientMain.!info.no_managed_accounts'] = 'There are no accounts that you can manage.';


// Tooltips
$lang['ClientMain.!tooltip.invoice_method_email'] = 'Invoices will be emailed to the address we have on file.';
$lang['ClientMain.!tooltip.invoice_method_paper'] = 'We will print and mail invoices to the address we have on file.';
$lang['ClientMain.!tooltip.invoice_method_interfax'] = 'We will automatically fax invoices to the fax number we have on file. If selecting this option, please ensure that we have a valid fax number on file.';
$lang['ClientMain.!tooltip.invoice_method_postalmethods'] = 'We will automatically print and mail invoices to the address we have on file.';


// Index
$lang['ClientMain.index.page_title'] = 'Client #%1$s'; // %1$s is the client ID number


// Myinfo
$lang['ClientMain.myinfo.page_title'] = 'Client #%1$s'; // %1$s is the client ID number

$lang['ClientMain.myinfo.client_id'] = 'Client #%1$s'; // %1$s is the client ID number
$lang['ClientMain.myinfo.setting_invoices'] = 'You are currently receiving invoices by %1$s.'; // %1$s is the clients invoice method (e.g. Email, Paper, etc.)
$lang['ClientMain.myinfo.setting_autodebit_disabled'] = 'Your account is not set up for automatic payment.';
$lang['ClientMain.myinfo.setting_autodebit_enabled'] = 'Your account is set up for automatic payment.';
$lang['ClientMain.myinfo.setting_autodebit_cc_zero_days'] = "We'll charge your %1\$s ending in x%2\$s on the day payment is due."; // %1$s is the type of credit card (e.g. "Visa"), %2$s is the last 4 of the credit card number
$lang['ClientMain.myinfo.setting_autodebit_ach_zero_days'] = "We'll charge your %1\$s Account ending in x%2\$s on the day payment is due."; // %1$s is the type of payment account to charge (e.g. "Checking"), %2$s is the last 4 of the account number
$lang['ClientMain.myinfo.setting_autodebit_cc_one_day'] = "We'll charge your %1\$s ending in x%2\$s the day before payment is due."; // %1$s is the type of credit card (e.g. "Visa"), %2$s is the last 4 of the credit card number
$lang['ClientMain.myinfo.setting_autodebit_ach_one_day'] = "We'll charge your %1\$s Account ending in x%2\$s the day before payment is due."; // %1$s is the type of payment account to charge (e.g. "Checking"), %2$s is the last 4 of the account number
$lang['ClientMain.myinfo.setting_autodebit_cc_days'] = "We'll charge your %1\$s ending in x%2\$s %3\$s days before payment is due."; // %1$s is the type of credit card (e.g. "Visa"), %2$s is the last 4 of the credit card number, %3$s is the plural number of days (two or more) before a payment is due when the client's payment account will be charged
$lang['ClientMain.myinfo.setting_autodebit_ach_days'] = "We'll charge your %1\$s Account ending in x%2\$s %3\$s days before payment is due."; // %1$s is the type of payment account to charge (e.g. "Checking"), %2$s is the last 4 of the account number, %3$s is the plural number of days (two or more) before a payment is due when the client's payment account will be charged
$lang['ClientMain.myinfo.number'] = '(%1$s %2$s)'; // %1$s is the number location, %2$s is the number type

$lang['ClientMain.myinfo.contacts'] = 'Contacts';
$lang['ClientMain.myinfo.link_addcontact'] = 'Add Contact';

$lang['ClientMain.myinfo.managed_accounts'] = 'Managed Accounts';
$lang['ClientMain.myinfo.field_managed_accounts_search'] = 'Search (Company, email, or name)';

$lang['ClientMain.myinfo.setting_change'] = 'Change this?';

$lang['ClientMain.myinfo.link_editclient'] = 'Change';


// Edit Client
$lang['ClientMain.edit.heading_contact'] = 'Contact Information';
$lang['ClientMain.edit.page_title'] = 'Client #%1$s Edit My Information'; // %1$s is the client ID number
$lang['ClientMain.edit.boxtitle_edit'] = 'Edit My Information';

$lang['ClientMain.edit.heading_billing'] = 'Billing Information';
$lang['ClientMain.edit.field_taxid'] = 'Tax ID/VATIN';
$lang['ClientMain.edit.field_default_currency'] = 'Preferred Currency';
$lang['ClientMain.edit.field_invoiceaddress'] = 'Address Invoices To';

$lang['ClientMain.edit.heading_settings'] = 'Additional Settings';
$lang['ClientMain.edit.field_language'] = 'Language';
$lang['ClientMain.edit.field_receive_email_marketing'] = 'I want to receive emails about new products, services, and offers.';

$lang['ClientMain.edit.heading_authentication'] = 'Authentication';
$lang['ClientMain.edit.field_username'] = 'Username';
$lang['ClientMain.edit.field_new_password'] = 'New Password';
$lang['ClientMain.edit.field_confirm_password'] = 'Confirm Password';
$lang['ClientMain.edit.field_current_password'] = 'Current Password';
$lang['ClientMain.edit.field_two_factor_mode'] = 'Enable Two-Factor Authentication';
$lang['ClientMain.edit.two_factor_desc'] = "Two-Factor Authentication is a security feature in which you provide two means of identification to log into your account. If enabled, you'll log into your account with your username and password and then be prompted to enter a One-Time Password.";
$lang['ClientMain.edit.two_factor_instructions'] = 'In order to use Two-Factor Authentication, you need a TOTP token. If you have an Android, iOS, or Windows mobile device, your phone can act as your token and provide the necessary One-Time Password.';
$lang['ClientMain.edit.two_factor_step1'] = 'Download an Authenticator for your device:';
$lang['ClientMain.edit.two_factor_step2'] = 'Open the App, and scan the QR code below, or alternatively, enter one of the following keys:<br/>Hex - %1$s<br/>Base32 - %2$s'; // %1$s is the key
$lang['ClientMain.edit.two_factor_step3'] = "Confirm your One-Time Password, then click \"Update My Information\". You'll be required to enter your One-Time Password the next time you log in.";
$lang['ClientMain.edit.text_android'] = 'Android';
$lang['ClientMain.edit.text_ios'] = 'iOS';
$lang['ClientMain.edit.text_windows'] = 'Windows';
$lang['ClientMain.edit.field_otp'] = 'One-Time Password';

$lang['ClientMain.edit.field_editsubmit'] = 'Update My Information';




// Invoice Method
$lang['ClientMain.invoicemethod.page_title'] = 'Client #%1$s Invoice Method'; // %1$s is the client ID number

$lang['ClientMain.invoicemethod.boxtitle_inv_method'] = 'Invoice Method';
$lang['ClientMain.invoicemethod.text_invoice_method'] = 'Your invoice method is the method that we deliver new invoices to you.';
$lang['Clientmain.invoicemethod.field_methodsubmit'] = 'Update';


// Currency amounts
$lang['ClientMain.getcurrencyamounts.text_total_credits'] = 'Credits:';
