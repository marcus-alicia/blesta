<?php
/**
 * Language definitions for the Logs model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Common log errors
$lang['Logs.!error.deletelog_datetime'] = 'Invalid date format.';


// Common Email/User log errors
$lang['Logs.!error.company_id.exists'] = 'Invalid company ID.';

// Email log errors
$lang['Logs.!error.to_address.empty'] = 'Missing a to address.';
$lang['Logs.!error.from_address.empty'] = 'Missing a from address.';
$lang['Logs.!error.from_name.empty'] = 'Missing a from name.';
$lang['Logs.!error.subject.empty'] = 'Missing a subject.';
$lang['Logs.!error.sent.format'] = 'Sent must be a number.';
$lang['Logs.!error.sent.length'] = 'Sent length may not exceed 1 character.';

// Gateway and Module log errors
$lang['Logs.!error.staff_id.exists'] = 'Invalid staff ID.';
$lang['Logs.!error.module_id.exists'] = 'Invalid module ID.';
$lang['Logs.!error.gateway_id.exists'] = 'Invalid gateway ID.';
$lang['Logs.!error.direction.format'] = 'Invalid direction type.';
$lang['Logs.!error.url.empty'] = 'Missing URL.';
$lang['Logs.!error.status.format'] = 'Invalid status type.';
$lang['Logs.!error.group.empty'] = 'Missing group.';
$lang['Logs.!error.group.maxlength'] = 'Group length may not exceed 8 characters.';

// User log errors
$lang['Logs.!error.user_id.empty'] = 'Missing user ID.';
$lang['Logs.!error.ip_address.empty'] = 'Missing IP address.';
$lang['Logs.!error.ip_address.length'] = 'IP address length may not exceed 39 characters.';
$lang['Logs.!error.user_log_exists.empty'] = 'The user at the given IP address does not exist.';
$lang['Logs.!error.result.format'] = "Login result must be either 'success' or 'failure'.";

// Message log errors
$lang['Logs.!error.to_user_id.exists'] = 'Invalid user ID.';
$lang['Logs.!error.messenger_id.exists'] = 'Invalid messenger ID.';
$lang['Logs.!error.success.format'] = 'Invalid success value.';

// Contact log errors
$lang['Logs.!error.contact_id.empty'] = 'Missing contact ID.';

// Client setting log errors
$lang['Logs.!error.client_id.exists'] = 'Invalid client ID.';
$lang['Logs.!error.fields.empty'] = 'Missing loggable fields.';
$lang['Logs.!error.by_user_id.exists'] = 'Invalid by user ID.';
$lang['Logs.!error.ip_address.length'] = 'The IP address may not exceed 39 characters in length.';
$lang['Logs.!error.date_changed.valid'] = 'Invalid date changed.';

// Account Access log errors
$lang['Logs.!error.staff_id.empty'] = 'Missing staff ID.';
$lang['Logs.!error.type.format'] = 'Invalid type.';
$lang['Logs.!error.account_type.format'] = 'Invalid account type.';
$lang['Logs.!error.account_id.empty'] = 'Missing account ID.';
$lang['Logs.!error.first_name.empty'] = 'First name must not be empty.';
$lang['Logs.!error.last_name.empty'] = 'Last name must not be empty.';

// Cron log
$lang['Logs.!error.run_id.exists'] = 'Invalid cron task ID.';
$lang['Logs.!error.event.maxlength'] = 'Event length may not exceed 32 characters.';
$lang['Logs.!error.group.unique'] = 'The cron task ID and group have already been taken.';
$lang['Logs.!error.group.betweenlength'] = 'Group must be between 1 and 32 characters in length.';
$lang['Logs.!error.start_date.format'] = 'Invalid start date format.';
$lang['Logs.!error.end_date.format'] = 'Invalid end date format.';
