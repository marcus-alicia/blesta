<?php
/**
 * Language definitions for the Clients model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Common client/custom field errors
$lang['Clients.!error.client_group_id.exists'] = 'Invalid client group ID.';

// Common custom field/note errors, and Debit Account error
$lang['Clients.!error.client_id.exists'] = 'Invalid client ID.';

// Debit Account errors
$lang['Clients.!error.account_id.exists'] = 'Invalid account ID.';
$lang['Clients.!error.type.exists'] = 'Invalid type.';

// Client errors
$lang['Clients.!error.id_format.empty'] = 'No ID format set for clients.';
$lang['Clients.!error.id_format.length'] = 'The ID format for clients may not exceed 64 characters.';
$lang['Clients.!error.id_value.valid'] = 'Unable to determine client ID value.';
$lang['Clients.!error.user_id.exists'] = 'Invalid user ID.';
$lang['Clients.!error.user_id.unique'] = 'Client user ID %1$s is already taken.'; // %1$s is the client user ID number
$lang['Clients.!error.status.format'] = 'Invalid status.';
$lang['Clients.!error.settings[tax_id].valid'] = 'Please specify a valid Tax ID/VATIN.';

$lang['Clients.!error.client_id.deleteable'] = 'The client may not be deleted while open invoices exist, recurring invoices exist, or active/suspended services exist. Please void any open invoices, delete any recurring invoices, and cancel any active/suspended services before proceeding.';

// Client settings errors
$lang['Clients.!error.autodebit.format'] = 'Invalid autodebit value.';
$lang['Clients.!error.inv_address_to.exists'] = 'Please specify a valid contact to address invoices to.';
$lang['Clients.!error.default_currency.valid'] = 'Invalid currency.';
$lang['Clients.!error.default_currency.editable'] = 'The currency may not be updated.';
$lang['Clients.!error.inv_method.valid'] = 'Invalid invoice delivery method.';
$lang['Clients.!error.inv_method.editable'] = 'The invoice delivery method may not be updated.';
$lang['Clients.!error.language.valid'] = 'Invalid language.';
$lang['Clients.!error.language.editable'] = 'The language may not be updated.';
$lang['Clients.!error.receive_email_marketing.valid'] = 'Invalid email marketing value.';

// Custom field errors
$lang['Clients.!error.name.empty'] = 'Please enter a name.';
$lang['Clients.!error.link.valid'] = 'Please enter a valid link.';
$lang['Clients.!error.is_lang.format'] = 'is_lang must be a number.';
$lang['Clients.!error.is_lang.length'] = 'is_lang may not exceed 1 character in length.';
$lang['Clients.!error.type.format'] = 'Invalid type.';
$lang['Clients.!error.values.format'] = 'The values are in an invalid format.';
$lang['Clients.!error.default.valid'] = 'The default value is invalid.';
$lang['Clients.!error.regex.valid'] = 'The regular expression is invalid.';
$lang['Clients.!error.show_client.format'] = 'Show client must be a number.';
$lang['Clients.!error.show_client.length'] = 'Show client may not exceed 1 character in length.';
$lang['Clients.!error.client_field_id.exists'] = 'Invalid custom field ID.';
$lang['Clients.!error.client_field_id.matches'] = 'The custom field is invalid.';
$lang['Clients.!error.encrypted.format'] = 'Encrypted must be a number.';
$lang['Clients.!error.encrypted.length'] = 'Encrypted may not exceed 1 character in length.';
$lang['Clients.!error.read_only.format'] = 'Read only must be a number.';
$lang['Clients.!error.read_only.length'] = 'Read only may not exceed 1 character in length.';
$lang['Clients.!error.value.required'] = '%1$s is in an invalid format.'; // %1$s is the name of the custom field
$lang['Clients.!error.value.valid'] = '%1$s is set to an invalid value.'; // %1$s is the name of the custom field

// Note errors
$lang['Clients.!error.staff_id.exists'] = 'Invalid staff ID.';
$lang['Clients.!error.title.empty'] = 'Please enter a title.';
$lang['Clients.!error.title.length'] = 'The title length may not exceed 255 characters.';
$lang['Clients.!error.note_id.exists'] = 'Invalid note ID.';
$lang['Clients.!error.stickied.format'] = 'Sticky must be a number.';
$lang['Clients.!error.stickied.length'] = 'Sticky length may not exceed 1 character.';

// Restricted packages
$lang['Clients.!error.package_ids.exists'] = 'At least one of the packages provided does not exist.';


// Text
$lang['Clients.getCustomFieldTypes.textbox'] = 'Text Box';
$lang['Clients.getCustomFieldTypes.checkbox'] = 'Check Box';
$lang['Clients.getCustomFieldTypes.dropdown'] = 'Drop Down';
$lang['Clients.getCustomFieldTypes.textarea'] = 'Text area';

$lang['Clients.getStatusTypes.active'] = 'Active';
$lang['Clients.getStatusTypes.inactive'] = 'Inactive';
$lang['Clients.getStatusTypes.fraud'] = 'Fraud';

$lang['Clients.setDebitAccountFailure.note_title'] = 'Auto debit disabled for payment account.';
$lang['Clients.setDebitAccountFailure.note_body'] = 'The %1$s payment account ending in x%2$s was disabled because it exceeded the maximum number of decline attempts.'; // %1$s is the payment account type, %2$s is the last four of the payment account


// Custom field language
$lang['Clients.customfield.cf2'] = 'Custom Field';
