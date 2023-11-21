<?php
/**
 * Language definitions for the Cron Tasks model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Errors
$lang['CronTasks.!error.key.unique'] = 'The cron task key provided is already taken.';
$lang['CronTasks.!error.key.length'] = 'The cron task key length may not exceed 64 characters.';
$lang['CronTasks.!error.task_type.format'] = 'Invalid task type. Must be module, plugin, or system.';
$lang['CronTasks.!error.dir.length'] = 'The directory length may not exceed 64 characters.';
$lang['CronTasks.!error.run_id.exists'] = 'Invalid cron task run ID.';
$lang['CronTasks.!error.id.exists'] = 'Invalid cron task ID.';
$lang['CronTasks.!error.name.empty'] = 'Please enter a name.';
$lang['CronTasks.!error.is_lang.format'] = 'is_lang must be a number.';
$lang['CronTasks.!error.is_lang.length'] = 'is_lang length may not exceed 1 character.';
$lang['CronTasks.!error.enabled.format'] = 'enabled must be a number.';
$lang['CronTasks.!error.enabled.length'] = 'enabled length may not exceed 1 character.';
$lang['CronTasks.!error.interval.format'] = 'Interval must be a number, representing minutes.';
$lang['CronTasks.!error.time.format'] = 'Time is in an invalid format.';
$lang['CronTasks.!error.type.format'] = 'Invalid cron task type. Must be either time or interval.';


// Cron Task types
$lang['CronTasks.task_type.system'] = 'System';
$lang['CronTasks.task_type.plugin'] = 'Plugin';
$lang['CronTasks.task_type.module'] = 'Module';


// Cron Task names and descriptions
$lang['CronTasks.crontask.name.create_invoice'] = 'Create Invoice';
$lang['CronTasks.crontask.description.create_invoice'] = 'Recurring invoices and renewing services are invoiced through this task, which runs once daily at the time specified.';

$lang['CronTasks.crontask.name.apply_invoice_late_fees'] = 'Apply Invoice Late Fees';
$lang['CronTasks.crontask.description.apply_invoice_late_fees'] = 'Applies late fees to open invoices a configured number of days after due.';

$lang['CronTasks.crontask.name.autodebit'] = 'Auto Debit';
$lang['CronTasks.crontask.description.autodebit'] = 'Payment accounts selected for auto debit will be run to pay off open invoices daily at the time specified.';

$lang['CronTasks.crontask.name.payment_reminders'] = 'Payment Reminders';
$lang['CronTasks.crontask.description.payment_reminders'] = 'Payment reminders and late notices are sent daily at the time specified.';

$lang['CronTasks.crontask.name.apply_payments'] = 'Apply Payments to Open Invoices';
$lang['CronTasks.crontask.description.apply_payments'] = 'Loose credits are applied to open invoices automatically at the interval selected.';

$lang['CronTasks.crontask.name.process_service_changes'] = 'Process Service Changes';
$lang['CronTasks.crontask.description.process_service_changes'] = 'Paid queued service changes (e.g. upgrades) are processed at the interval selected.';

$lang['CronTasks.crontask.name.process_renewing_services'] = 'Process Service Renewals';
$lang['CronTasks.crontask.description.process_renewing_services'] = 'Renewing services that are attached to modules are renewed at the interval selected.';

$lang['CronTasks.crontask.name.provision_pending_services'] = 'Provision Paid Pending Services';
$lang['CronTasks.crontask.description.provision_pending_services'] = 'Paid pending services are activated at the interval selected.';

$lang['CronTasks.crontask.name.cancel_scheduled_services'] = 'Cancel Scheduled Services';
$lang['CronTasks.crontask.description.cancel_scheduled_services'] = 'Services with future cancellation dates set are removed at the interval selected.';

$lang['CronTasks.crontask.name.card_expiration_reminders'] = 'Card Expiration Reminders 15th of Month';
$lang['CronTasks.crontask.description.card_expiration_reminders'] = 'A reminder will be sent on the 15th of the month for credit cards expiring that month at the time specified.';

$lang['CronTasks.crontask.name.deliver_invoices'] = 'Deliver Invoices';
$lang['CronTasks.crontask.description.deliver_invoices'] = 'Invoices that are scheduled for delivery will be sent at the interval selected.';

$lang['CronTasks.crontask.name.backups_amazons3'] = 'Amazon S3 Backups';
$lang['CronTasks.crontask.description.backups_amazons3'] = 'Amazon S3 Backups are scheduled under System Settings > Backup > Amazon S3.';

$lang['CronTasks.crontask.name.backups_sftp'] = 'SFTP Backups';
$lang['CronTasks.crontask.description.backups_sftp'] = 'SFTP Backups are scheduled under System Settings > Backup > Secure FTP.';

$lang['CronTasks.crontask.name.suspend_services'] = 'Suspend Services';
$lang['CronTasks.crontask.description.suspend_services'] = 'Past due services will be suspended daily at the time specified.';

$lang['CronTasks.crontask.name.exchange_rates'] = 'Exchange Rate Updates';
$lang['CronTasks.crontask.description.exchange_rates'] = 'Exchange rates will be updated at the interval specified. It is not recommended to run this more than twice daily for risk of being blocked.';

$lang['CronTasks.crontask.name.deliver_reports'] = 'Deliver Reports';
$lang['CronTasks.crontask.description.deliver_reports'] = 'A/R, Invoice Generation, Tax Liability, and other reports will be delivered daily at the time specified.';

$lang['CronTasks.crontask.name.cleanup_logs'] = 'Clean up Logs';
$lang['CronTasks.crontask.description.cleanup_logs'] = 'Old gateway, module, and other logs will be rotated daily depending on their retention settings at the time specified.';

$lang['CronTasks.crontask.name.unsuspend_services'] = 'Unsuspend Services';
$lang['CronTasks.crontask.description.unsuspend_services'] = 'Suspended services that have been paid will be unsuspended at the interval selected.';

$lang['CronTasks.crontask.name.transition_quotations'] = 'Transition Quotations';
$lang['CronTasks.crontask.description.transition_quotations'] = 'Mark quotations past the valid date, as expired';

$lang['CronTasks.crontask.name.license_validation'] = 'License Validation';
