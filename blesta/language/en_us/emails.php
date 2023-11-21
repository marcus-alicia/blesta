<?php
/**
 * Language definitions for the Emails model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Common email/email signature errors
$lang['Emails.!error.company_id.exists'] = 'Invalid company ID.';
$lang['Emails.!error.html.parse'] = 'Template parse error: %1$s'; // %1$s is the parse error
$lang['Emails.!error.text.parse'] = 'Template parse error: %1$s'; // %1$s is the parse error


// Email signature errors
$lang['Emails.!error.email_signature_id.in_use'] = 'That email signature is currently in use and may not be deleted.';
$lang['Emails.!error.name.empty'] = 'Please enter a signature name.';
$lang['Emails.!error.text.empty'] = 'Please enter a plain-text signature.';
$lang['Emails.!error.email_signature_id.exists'] = 'Invalid email signature ID.';


// Email errors
$lang['Emails.!error.email_group_id.exists'] = 'Invalid email group ID.';
$lang['Emails.!error.lang.empty'] = 'Please enter a language.';
$lang['Emails.!error.lang.length'] = 'The language length may not exceed 5 characters.';
$lang['Emails.!error.from.format'] = 'Please enter a valid from address.';
$lang['Emails.!error.from_name.empty'] = 'Please enter a from name.';
$lang['Emails.!error.reply_to.format'] = 'Please enter a valid reply-to address.';
$lang['Emails.!error.subject.empty'] = 'Please enter a subject.';
$lang['Emails.!error.email_signature_id.exists'] = 'Invalid email signature ID.';
$lang['Emails.!error.include_attachments'] = 'Whether to include attachments must be set to 0 or 1.';
$lang['Emails.!error.status.format'] = 'Invalid status.';
$lang['Emails.!error.email_id.exists'] = 'Invalid email ID.';
$lang['Emails.!error.company_id.unique'] = 'The email group ID and company ID for the given language is already taken.';

$lang['Emails.!error.action.exists'] = 'The action given is an invalid email group action.';
$lang['Emails.!error.to_addresses.empty'] = 'At least one To address must be provided.';
$lang['Emails.!error.to_addresses.format'] = 'At least one of the email To addresses provided is not a valid email address.';
$lang['Emails.!error.cc_addresses.format'] = 'At least one of the email CC addresses provided is not a valid email address.';
$lang['Emails.!error.bcc_addresses.format'] = 'At least one of the email BCC addresses provided is not a valid email address.';
$lang['Emails.!error.attachments.exist'] = 'At least one of the attachments provided does not exist on the file system.';

$lang['Emails.!error.email.failed_to_send'] = 'The email failed to send due to a configuration issue.';


// Text
$lang['Emails.getStatusTypes.active'] = 'No';
$lang['Emails.getStatusTypes.inactive'] = 'Yes';
