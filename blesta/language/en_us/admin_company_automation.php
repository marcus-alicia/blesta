<?php
/**
 * Language definitions for the Admin Company Automation settings controller/views
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Notice messages
$lang['AdminCompanyAutomation.!notice.cron_task_interval'] = 'There are automation tasks set to run every minute, but your cron does not appear to be running every minute. Update your cron to run every minute to take advantage of these shorter intervals.';

// Success messages
$lang['AdminCompanyAutomation.!success.automation_updated'] = 'The Automation settings were successfully updated!';
$lang['AdminCompanyAutomation.!success.task_cleared'] = 'The cron task has been cleared.';


// Index
$lang['AdminCompanyAutomation.index.page_title'] = 'Settings > Company > Automation';
$lang['AdminCompanyAutomation.index.boxtitle_automation'] = 'Automation';

$lang['AdminCompanyAutomation.index.field_automationsubmit'] = 'Update Settings';
$lang['AdminCompanyAutomation.index.field_enabled'] = 'Enabled';

$lang['AdminCompanyAutomation.index.text_interval'] = 'Interval';
$lang['AdminCompanyAutomation.index.text_starttime'] = 'Start Time';
$lang['AdminCompanyAutomation.index.text_task_lastran'] = 'Task Last Ran:';

$lang['AdminCompanyAutomation.index.no_results'] = 'There are no cron tasks.';
$lang['AdminCompanyAutomation.index.no_cron_lastran'] = 'Never';

$lang['AdminCompanyAutomation.index.text_cron_last_ran'] = 'The cron last ran on %1$s.'; // %1$s is the date that the cron last ran
$lang['AdminCompanyAutomation.index.text_cron_never_ran'] = 'The cron has never run.';

$lang['AdminCompanyAutomation.index.option_clear_task'] = 'Clear Task Lock';
$lang['AdminCompanyAutomation.index.confirm_clear_task'] = 'Are you sure you want to clear this cron task lock? If this task continuously stalls, there may be a more serious issue. Try running the cron manually, or checking the cron log for errors to determine the cause.';


// GetIntervals
$lang['AdminCompanyAutomation.getintervals.text_minute'] = 'minute';
$lang['AdminCompanyAutomation.getintervals.text_minutes'] = 'minutes';
$lang['AdminCompanyAutomation.getintervals.text_hour'] = 'hour';
$lang['AdminCompanyAutomation.getintervals.text_hours'] = 'hours';
