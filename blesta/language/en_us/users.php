<?php
/**
 * Language definitions for the Users model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

$lang['Users.!error.username.empty'] = 'Please enter a username.';
$lang['Users.!error.username.unique'] = 'That username has already been taken.';
$lang['Users.!error.current_password.matches'] = 'Invalid password.';
$lang['Users.!error.new_password.format'] = 'Please enter a password at least 6 characters in length.';
$lang['Users.!error.new_password.matches'] = 'The passwords do not match.';
$lang['Users.!error.two_factor_mode.format'] = 'Invalid two factor mode.';
$lang['Users.!error.two_factor_key.format'] = 'Invalid two factor key.';
$lang['Users.!error.username.auth'] = 'No matches found for that user/password combination.';
$lang['Users.!error.otp.auth'] = 'The one-time password entered is invalid. Maximum length is 16 characters';
$lang['Users.!error.user_id.exists'] = 'Invalid user ID.';
$lang['Users.!error.username.attempts'] = 'Too many failed login attempts detected.';
$lang['Users.!error.username.company'] = 'You are not authorized to login at this location.';

$lang['Users.!error.clients.exist'] = 'The user cannot be deleted because there is at least one client assigned to the user.';
