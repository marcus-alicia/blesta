<?php
/**
 * Language definitions for the Admin Login controller/views
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Index
$lang['AdminLogin.index.page_title'] = 'Log in';
$lang['AdminLogin.index.title_adminarea'] = '%1$s | Staff Area'; // %1$s is the company name
$lang['AdminLogin.index.field_username'] = 'Username';
$lang['AdminLogin.index.field_password'] = 'Password';
$lang['AdminLogin.index.field_rememberme'] = 'Remember me on this computer.';
$lang['AdminLogin.index.field_loginsubmit'] = 'Log In';
$lang['AdminLogin.index.link_resetpassword'] = 'Reset My Password';


// OTP
$lang['AdminLogin.otp.page_title'] = 'OTP Login';
$lang['AdminLogin.otp.title_adminarea'] = '%1$s | Staff Area'; // %1$s is the company name
$lang['AdminLogin.otp.field_password'] = 'One-time Password';
$lang['AdminLogin.otp.field_loginsubmit'] = 'Log In';
$lang['AdminLogin.otp.link_login'] = 'Cancel, Log In';


// Reset
$lang['AdminLogin.reset.page_title'] = 'Reset Password';
$lang['AdminLogin.reset.title_adminarea'] = '%1$s | Reset Password'; // %1$s is the company name
$lang['AdminLogin.reset.field_username'] = 'Username';
$lang['AdminLogin.reset.field_resetsubmit'] = 'Reset Password';
$lang['AdminLogin.reset.link_login'] = 'Cancel, Log In';


// Confirm Reset
$lang['AdminLogin.confirmreset.page_title'] = 'Confirm Password Reset';
$lang['AdminLogin.confirmreset.title_adminarea'] = '%1$s | Confirm Password Reset'; // %1$s is the company name
$lang['AdminLogin.confirmreset.field_new_password'] = 'New Password';
$lang['AdminLogin.confirmreset.field_confirm_password'] = 'Confirm New Password';
$lang['AdminLogin.confirmreset.field_resetsubmit'] = 'Set Password';
$lang['AdminLogin.confirmreset.link_login'] = 'Cancel, Log In';


// Setup
$lang['AdminLogin.setup.page_title'] = 'Initial Setup';
$lang['AdminLogin.setup.title_adminarea'] = 'Initial Setup'; // %1$s is the company name
$lang['AdminLogin.setup.field_license_key'] = 'Your desired License Key';
$lang['AdminLogin.setup.trial_newsletter'] = 'By signing up for a trial, you agree to receive emails from us during your trial. You can opt-out at any time.';
$lang['AdminLogin.setup.field_newsletter'] = 'Sign-up for our newsletter. You can opt-out at any time.';
$lang['AdminLogin.setup.heading_create_account'] = 'Create your Staff account';
$lang['AdminLogin.setup.field_first_name'] = 'First Name';
$lang['AdminLogin.setup.field_last_name'] = 'Last Name';
$lang['AdminLogin.setup.field_username'] = 'Username';
$lang['AdminLogin.setup.field_email'] = 'Email Address';
$lang['AdminLogin.setup.field_password'] = 'Password';
$lang['AdminLogin.setup.field_confirm_password'] = 'Confirm Password';
$lang['AdminLogin.setup.field_submit'] = 'Finish';


// Error
$lang['AdminLogin.!error.unknown_user'] = 'That username is not recognized or the password is not capable of being reset.';
$lang['AdminLogin.!error.captcha.invalid'] = 'The captcha entered was invalid. Please try again.';


// Success
$lang['AdminLogin.!success.reset_sent'] = 'A confirmation email has been sent to the address on record.';


// Info
$lang['AdminLogin.!info.reset_password'] = 'Enter your username below and a time-sensitive link will be emailed to you so you can set a new password.';
$lang['AdminLogin.!info.otp'] = 'Two-Factor Authentication is required for this user. Please enter your OTP (One-time Password) below.';
