<?php
/**
 * Language definitions for the Admin Tools controller/views
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Success messages
$lang['AdminTools.!success.currency_converted'] = '%1$s = %2$s'; // %1$s is the from currency code and amount, %2$s is the to currency code and amount
$lang['AdminTools.!success.collation_updated'] = 'The database collation has been successfully updated.';
$lang['AdminTools.!success.max_updated'] = 'The renewal attempt maximum was successfully updated.';
$lang['AdminTools.!success.dequeue'] = 'The service was successfully removed from the renewal queue.';


// Log names
$lang['AdminTools.getlognames.text_module'] = 'Module';
$lang['AdminTools.getlognames.text_messenger'] = 'Messenger';
$lang['AdminTools.getlognames.text_gateway'] = 'Gateway';
$lang['AdminTools.getlognames.text_email'] = 'Email';
$lang['AdminTools.getlognames.text_users'] = 'User Logins';
$lang['AdminTools.getlognames.text_contacts'] = 'Contacts';
$lang['AdminTools.getlognames.text_client_settings'] = 'Client Settings';
$lang['AdminTools.getlognames.text_accountaccess'] = 'Account Access';
$lang['AdminTools.getlognames.text_transactions'] = 'Transactions';
$lang['AdminTools.getlognames.text_cron'] = 'Cron';
$lang['AdminTools.getlognames.text_invoice_delivery'] = 'Invoice Delivery';


// Convert Currency
$lang['AdminTools.convertcurrency.page_title'] = 'Tools Convert Currency';
$lang['AdminTools.convertcurrency.boxtitle_currency'] = 'Convert Currency';

$lang['AdminTools.convertcurrency.field_amount'] = 'Amount';
$lang['AdminTools.convertcurrency.field_from'] = 'From';
$lang['AdminTools.convertcurrency.field_to'] = 'To';

$lang['AdminTools.convertcurrency.field_currencysubmit'] = 'Convert Currency';


// Utilities
$lang['AdminTools.utilities.page_title'] = 'Tools Utilities';
$lang['AdminTools.utilities.boxtitle_utilities'] = 'Utilities';

$lang['AdminTools.utilities.heading_utility'] = 'Utility';
$lang['AdminTools.utilities.heading_description'] = 'Description';
$lang['AdminTools.utilities.heading_options'] = 'Options';

$lang['AdminTools.utilities.update_collation'] = 'Update Database Collation';
$lang['AdminTools.utilities.field_update_to_utf8mb4'] = 'Update to utf8mb4';
$lang['AdminTools.utilities.text_update_to_utf8mb4'] = 'Update the character set and collation of your database, tables, and columns to utf8mb4 to support 4 byte characters such as emojis.  This may take several minutes.';
$lang['AdminTools.utilities.text_update_to_utf8mb4_requirements'] = 'Please upgrade to MySQL to 5.7+ or MariaDB to 10.2.2+ to support utf8mb4 characters such as emojis.';
$lang['AdminTools.utilities.text_update_to_utf8mb4_supported'] = 'Database already supports utf8mb4.';
$lang['AdminTools.utilities.text_update_to_utf8mb4_config'] = 'To insert and fetch utf8mb4 characters update your blesta.php configuration file to use \'charset_query\' => "SET NAMES \'utf8mb4\'".';


// Logs
$lang['AdminTools.logs.page_title'] = 'Tools Logs';
$lang['AdminTools.logs.boxtitle_logs'] = 'Logs';



// Logs
$lang['AdminTools.renewals.page_title'] = 'Renewel Queue';
$lang['AdminTools.renewals.boxtitle_renewals'] = 'Service Renewal Queue';

$lang['AdminTools.renewals.heading_client'] = 'Client';
$lang['AdminTools.renewals.heading_service_id'] = 'Service ID';
$lang['AdminTools.renewals.heading_failed_attempts'] = 'Failed Attempts';
$lang['AdminTools.renewals.heading_maximum_attempts'] = 'Maximum Attempts';
$lang['AdminTools.renewals.heading_invoice'] = 'Invoice';
$lang['AdminTools.renewals.heading_options'] = 'Options';
$lang['AdminTools.renewals.confirm_dequeue'] = 'Are you sure you want to remove this service from the renewal queue?';
$lang['AdminTools.renewals.option_view'] = 'View Invoice';
$lang['AdminTools.renewals.option_dequeue'] = 'Dequeue';
$lang['AdminTools.renewals.option_change_max'] = 'Change Maximum';

$lang['AdminTools.renewals.no_results'] = 'There are no services currently queued for renewal.';


$lang['AdminTools.change_max_attempts.boxtitle_change_max_attempts'] = 'Change Maximum Attempts';

$lang['AdminTools.change_max_attempts.field_max_attempts'] = 'Maximum Attempts';
$lang['AdminTools.change_max_attempts.field_cancel'] = 'Cancel';
$lang['AdminTools.change_max_attempts.btn_save'] = 'Save';


// Module logs
$lang['AdminTools.logmodule.page_title'] = 'Tools Logs';
$lang['AdminTools.logmodule.text_name'] = 'Name';
$lang['AdminTools.logmodule.text_staffname'] = 'Staff';
$lang['AdminTools.logmodule.text_date'] = 'Date';

$lang['AdminTools.logmodule.no_results'] = 'There are no module logs.';


// Module log list
$lang['AdminTools.moduleloglist.text_direction'] = 'Direction';
$lang['AdminTools.moduleloglist.text_data'] = 'Data';
$lang['AdminTools.moduleloglist.text_date'] = 'Date';
$lang['AdminTools.moduleloglist.text_status'] = 'Status';
$lang['AdminTools.moduleloglist.text_input'] = 'Input';
$lang['AdminTools.moduleloglist.text_output'] = 'Output';
$lang['AdminTools.moduleloglist.text_error'] = 'Error';
$lang['AdminTools.moduleloglist.text_success'] = 'Success';

$lang['AdminTools.moduleloglist.no_results'] = 'There is no data for this module log.';


// Messenger logs
$lang['AdminTools.logmessenger.page_title'] = 'Tools Logs';
$lang['AdminTools.logmessenger.text_name'] = 'Name';
$lang['AdminTools.logmessenger.text_recipient'] = 'Recipient';
$lang['AdminTools.logmessenger.text_date'] = 'Date';

$lang['AdminTools.logmessenger.no_results'] = 'There are no messenger logs.';


// Messenger log list
$lang['AdminTools.messengerloglist.text_direction'] = 'Direction';
$lang['AdminTools.messengerloglist.text_data'] = 'Data';
$lang['AdminTools.messengerloglist.text_date'] = 'Date';
$lang['AdminTools.messengerloglist.text_status'] = 'Status';
$lang['AdminTools.messengerloglist.text_input'] = 'Input';
$lang['AdminTools.messengerloglist.text_output'] = 'Output';
$lang['AdminTools.messengerloglist.text_error'] = 'Error';
$lang['AdminTools.messengerloglist.text_success'] = 'Success';

$lang['AdminTools.messengerloglist.no_results'] = 'There is no data for this messenger log.';


// Gateway logs
$lang['AdminTools.loggateway.page_title'] = 'Tools Logs';
$lang['AdminTools.loggateway.text_name'] = 'Name';
$lang['AdminTools.loggateway.text_staffname'] = 'Staff';
$lang['AdminTools.loggateway.text_date'] = 'Date';

$lang['AdminTools.loggateway.no_results'] = 'There are no gateway logs.';


// Gateway log list
$lang['AdminTools.gatewayloglist.text_direction'] = 'Direction';
$lang['AdminTools.gatewayloglist.text_data'] = 'Data';
$lang['AdminTools.gatewayloglist.text_date'] = 'Date';
$lang['AdminTools.gatewayloglist.text_status'] = 'Status';
$lang['AdminTools.gatewayloglist.text_input'] = 'Input';
$lang['AdminTools.gatewayloglist.text_output'] = 'Output';
$lang['AdminTools.gatewayloglist.text_error'] = 'Error';
$lang['AdminTools.gatewayloglist.text_success'] = 'Success';

$lang['AdminTools.gatewayloglist.no_results'] = 'There is no data for this gateway log.';


// Email log
$lang['AdminTools.logemail.page_title'] = 'Tools Logs';
$lang['AdminTools.logemail.text_date'] = 'Date';
$lang['AdminTools.logemail.text_subject'] = 'Subject';
$lang['AdminTools.logemail.text_summary'] = 'Summary';
$lang['AdminTools.logemail.text_status'] = 'Status';
$lang['AdminTools.logemail.text_to'] = 'To';
$lang['AdminTools.logemail.text_cc'] = 'CC';
$lang['AdminTools.logemail.text_from'] = 'From';
$lang['AdminTools.logemail.text_resend'] = 'Resend';

$lang['AdminTools.logemail.text_sent'] = 'Sent';
$lang['AdminTools.logemail.text_unsent'] = 'Unsent';

$lang['AdminTools.logemail.no_results'] = 'There are no email logs.';


// User Login log
$lang['AdminTools.logusers.page_title'] = 'Tools Logs';
$lang['AdminTools.logusers.text_name'] = 'Name';
$lang['AdminTools.logusers.text_username'] = 'Username';
$lang['AdminTools.logusers.text_type'] = 'Type';
$lang['AdminTools.logusers.text_result'] = 'Result';
$lang['AdminTools.logusers.text_ipaddress'] = 'IP Address';
$lang['AdminTools.logusers.text_date'] = 'Date';
$lang['AdminTools.logusers.text_staff'] = 'Staff';
$lang['AdminTools.logusers.text_client'] = 'Client';
$lang['AdminTools.logusers.text_contact'] = 'Contact';
$lang['AdminTools.logusers.text_success'] = 'Success';
$lang['AdminTools.logusers.text_failure'] = 'Failure';
$lang['AdminTools.logusers.text_location'] = 'Location';

$lang['AdminTools.logusers.no_results'] = 'There are no user login logs.';


// Contacts log
$lang['AdminTools.logcontacts.page_title'] = 'Tools Logs';
$lang['AdminTools.logcontacts.text_name'] = 'Name';
$lang['AdminTools.logcontacts.text_date'] = 'Date';
$lang['AdminTools.logcontacts.text_field'] = 'Field';
$lang['AdminTools.logcontacts.text_previous'] = 'Previous Value';
$lang['AdminTools.logcontacts.text_new'] = 'New value';

$lang['AdminTools.logcontacts.no_results'] = 'There are no contact logs.';


// Cleint Settings log
$lang['AdminTools.logclientsettings.page_title'] = 'Tools Logs';
$lang['AdminTools.logclientsettings.text_client'] = 'Client';
$lang['AdminTools.logclientsettings.text_user'] = 'Performed By';
$lang['AdminTools.logclientsettings.text_ip_address'] = 'IP Address';
$lang['AdminTools.logclientsettings.text_date'] = 'Date';
$lang['AdminTools.logclientsettings.text_field'] = 'Field';
$lang['AdminTools.logclientsettings.text_previous'] = 'Previous Value';
$lang['AdminTools.logclientsettings.text_new'] = 'New Value';

$lang['AdminTools.logclientsettings.no_results'] = 'There are no client setting logs.';


// Transactions log
$lang['AdminTools.logtransactions.page_title'] = 'Tools Logs';
$lang['AdminTools.logtransactions.text_client_name'] = 'Client';
$lang['AdminTools.logtransactions.text_staff_name'] = 'Staff';
$lang['AdminTools.logtransactions.text_date'] = 'Date';
$lang['AdminTools.logtransactions.text_field'] = 'Field';
$lang['AdminTools.logtransactions.text_previous'] = 'Previous Value';
$lang['AdminTools.logtransactions.text_new'] = 'New value';

$lang['AdminTools.logtransactions.no_results'] = 'There are no transaction logs.';


// Account Access log
$lang['AdminTools.logaccountaccess.page_title'] = 'Tools Logs';
$lang['AdminTools.logaccountaccess.name'] = 'Staff';
$lang['AdminTools.logaccountaccess.type'] = 'Type';
$lang['AdminTools.logaccountaccess.date'] = 'Date';
$lang['AdminTools.logaccountaccess.text_cc'] = 'Credit Card';
$lang['AdminTools.logaccountaccess.text_ach'] = 'ACH';

$lang['AdminTools.logaccountaccess.no_results'] = 'There are no account access logs.';

$lang['AdminTools.accountaccess.name'] = 'Name';
$lang['AdminTools.accountaccess.type'] = 'Type';
$lang['AdminTools.accountaccess.last4'] = 'Last 4';
$lang['AdminTools.accountaccess.type_cc'] = '%1$s - %2$s'; // %1$s is the account type (Credit Card) and %2$s is the type of account (MasterCard, Visa, etc.)
$lang['AdminTools.accountaccess.type_ach'] = '%1$s - %2$s'; // %1$s is the account type (ACH) and %2$s is the type of account (Checking or Savings)

$lang['AdminTools.accountaccess.no_results'] = 'There are no account details for this record.';


// Cron log
$lang['AdminTools.logcron.page_title'] = 'Tools Logs';
$lang['AdminTools.logcron.task'] = 'Task';
$lang['AdminTools.logcron.start_date'] = 'Start Date';
$lang['AdminTools.logcron.end_date'] = 'End Date';
$lang['AdminTools.logcron.output'] = 'Output';
$lang['AdminTools.logcron.no_output'] = 'No output recorded for this log.';
$lang['AdminTools.logcron.no_results'] = 'There are no cron logs.';


// Invoice Delivery logs
$lang['AdminTools.loginvoicedelivery.page_title'] = 'Tools Logs';

$lang['AdminTools.loginvoicedelivery.invoice_id_code'] = 'Invoice #';
$lang['AdminTools.loginvoicedelivery.first_name'] = 'Client';
$lang['AdminTools.loginvoicedelivery.method'] = 'Delivery Method';
$lang['AdminTools.loginvoicedelivery.date_sent'] = 'Date Sent';
$lang['AdminTools.loginvoicedelivery.no_results'] = 'There are no invoice delivery logs.';
