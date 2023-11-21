<?php
/**
 * Language definitions for the Client Login controller
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Error
$lang['ClientLogin.!error.unknown_user'] = 'That username is not recognized or the password is not capable of being reset.';
$lang['ClientLogin.!error.unknown_email'] = 'That email address is not recognized or the username is not capable of being recovered.';
$lang['ClientLogin.!error.captcha.invalid'] = 'The captcha entered was invalid. Please try again.';


// Success
$lang['ClientLogin.!success.reset_sent'] = 'A confirmation email has been sent to the address on record.';
$lang['ClientLogin.!success.forgot_sent'] = 'An email with your username on record has been sent to your email address.';


// Login
$lang['ClientLogin.index.page_title'] = 'Log In';
$lang['ClientLogin.index.description'] = 'Please log in to access the client area.';
$lang['ClientLogin.index.login_heading'] = 'Log In';
$lang['ClientLogin.index.field_username'] = 'Username';
$lang['ClientLogin.index.field_password'] = 'Password';
$lang['ClientLogin.index.field_rememberme'] = 'Remember me on this computer';
$lang['ClientLogin.index.field_loginsubmit'] = 'Log In';
$lang['ClientLogin.index.link_resetpassword'] = 'Reset My Password';
$lang['ClientLogin.index.link_forgotusername'] = 'Forgot My Username';


// OTP
$lang['ClientLogin.otp.page_title'] = 'Log In';
$lang['ClientLogin.otp.description'] = 'Please enter your One Time Password.';
$lang['ClientLogin.otp.login_heading'] = 'Log In';
$lang['ClientLogin.otp.field_otp'] = 'One Time Password';
$lang['ClientLogin.otp.field_submit'] = 'Log In';


// Reset
$lang['ClientLogin.reset.page_title'] = 'Reset Password';
$lang['ClientLogin.reset.reset_heading'] = 'Reset Password';
$lang['ClientLogin.reset.description'] = 'Enter your username to request a password reset link.';
$lang['ClientLogin.reset.field_username'] = 'Username';
$lang['ClientLogin.reset.field_resetsubmit'] = 'Reset Password';
$lang['ClientLogin.reset.link_login'] = 'Cancel, Log In';


// Confirm Reset
$lang['ClientLogin.confirmreset.page_title'] = 'Confirm Password Reset';
$lang['ClientLogin.confirmreset.reset_heading'] = 'Confirm Password Reset';
$lang['ClientLogin.confirmreset.description'] = 'Please enter your new password.';
$lang['ClientLogin.confirmreset.field_new_password'] = 'New Password';
$lang['ClientLogin.confirmreset.field_confirm_password'] = 'Confirm New Password';
$lang['ClientLogin.confirmreset.field_resetsubmit'] = 'Set Password';
$lang['ClientLogin.confirmreset.link_login'] = 'Cancel, Log In';


// Forgot
$lang['ClientLogin.forgot.page_title'] = 'Forgot Username';
$lang['ClientLogin.forgot.reset_heading'] = 'Forgot Username';
$lang['ClientLogin.forgot.description'] = 'Enter your email address to request your username.';
$lang['ClientLogin.forgot.field_email'] = 'Email Address';
$lang['ClientLogin.forgot.field_forgotsubmit'] = 'Recover Username';
$lang['ClientLogin.forgot.link_login'] = 'Cancel, Log In';
