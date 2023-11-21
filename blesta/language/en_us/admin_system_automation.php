<?php
/**
 * Language definitions for the Admin System Automation settings controller/views
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Error messages
$lang['AdminSystemAutomation.!error.empty_cron_key'] = 'Please set a cron key.';


// Success messages
$lang['AdminSystemAutomation.!success.automation_updated'] = 'The Automation settings were successfully updated!';
$lang['AdminSystemAutomation.!success.cron_key'] = 'The cron key has been successfully updated!';


// Index
$lang['AdminSystemAutomation.index.page_title'] = 'Settings > System > Automation';
$lang['AdminSystemAutomation.index.boxtitle_automation'] = 'Automation';

$lang['AdminSystemAutomation.index.heading_status'] = 'Cron Status';

$lang['AdminSystemAutomation.index.field_croncommand'] = 'Example Cron Command';
$lang['AdminSystemAutomation.index.field_runcron'] = 'Run Cron Manually';
$lang['AdminSystemAutomation.index.field_cron_key'] = 'Cron Key';
$lang['AdminSystemAutomation.index.field_cronkey_submit'] = 'Update Cron Key';

$lang['AdminSystemAutomation.index.text_cron_last_ran'] = 'The cron last ran on %1$s.'; // %1$s is the date that the cron last ran
$lang['AdminSystemAutomation.index.text_cron_never_ran'] = 'The cron has never run.';
$lang['AdminSystemAutomation.index.text_cron_currently_running'] = 'The cron is currently running.';
$lang['AdminSystemAutomation.index.text_update_key'] = 'Update Cron Key';
$lang['AdminSystemAutomation.index.text_generate_code'] = 'Generate Code';

$lang['AdminSystemAutomation.index.note_cron_command'] = 'This is an example cron command that may be used to create a cron job on your server. When setting up the cron job, be sure to update the cron command to point to where PHP is installed if it differs from what is shown in this example.';
