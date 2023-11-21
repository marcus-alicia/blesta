<?php
/**
 * Language definitions for the Staff model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

$lang['Staff.!error.user_id.exists'] = 'Invalid user ID.';
$lang['Staff.!error.user_id.unique'] = 'Staff user ID %1$s is already taken.'; // %1$s is the staff user ID number
$lang['Staff.!error.first_name.empty'] = 'Please enter a first name.';
$lang['Staff.!error.last_name.empty'] = 'Please enter a last name.';
$lang['Staff.!error.email.format'] = 'Please enter a valid email address.';
$lang['Staff.!error.status.format'] = 'Invalid status.';
$lang['Staff.!error.groups[].format'] = "There are one or more invalid group ID's.";
$lang['Staff.!error.groups.unique_company'] = 'Only one staff group per company may be assigned to a staff member.';
$lang['Staff.!error.staff_id.format'] = 'The staff ID must be a number.';
$lang['Staff.!error.company_id.format'] = 'The company ID must be a number.';
$lang['Staff.!error.uri.empty'] = 'Please enter a URI.';
$lang['Staff.!error.title.empty'] = 'Please enter a title.';
$lang['Staff.!error.order.format'] = 'The sort order must be a number.';
$lang['Staff.!error.order.length'] = 'The sort order length may not exceed 5 characters.';

$lang['Staff.!error.staff_group_id.exists'] = 'Invalid staff group ID.';
$lang['Staff.!error.staff_id.exists'] = 'Invalid staff ID.';
$lang['Staff.!error.action.exists'] = 'The email group action %1$s does not exist.'; // %1$s is the name of the email group action
$lang['Staff.!error.action[].exists'] = 'At least one of the email group actions does not exist.';
