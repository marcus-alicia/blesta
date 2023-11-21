<?php
/**
 * Language definitions for the Client Contacts controller/views
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Success messages
$lang['ClientContacts.!success.contact_deleted'] = 'The contact %1$s %2$s was successfully deleted!'; // %1$s is the contact's first name, %2$s is the contact's last name
$lang['ClientContacts.!success.contact_updated'] = 'The contact was successfully updated!';
$lang['ClientContacts.!success.contact_added'] = 'The contact was successfully created!';


// Info messages
$lang['ClientContacts.!info.no_contacts'] = "You don't currently have any contacts, add your first contact below.";
$lang['ClientContacts.!info.unverified_email'] = 'The email address associated to this contact has not been verified. A verification email has been sent to %1$s.'; // %1$s is the email address to be verified
$lang['ClientContacts.!info.unverified_email_button'] = 'Resend Verification Email';


// Index
$lang['ClientContacts.index.page_title'] = 'Client #%1$s Contacts'; // %1$s is the client ID number

$lang['ClientContacts.index.create_contact'] = 'Add Contact';

$lang['ClientContacts.index.boxtitle_contacts'] = 'Contacts';
$lang['ClientContacts.index.heading_name'] = 'Name';
$lang['ClientContacts.index.heading_email'] = 'Email';
$lang['ClientContacts.index.heading_type'] = 'Type';
$lang['ClientContacts.index.heading_options'] = 'Options';
$lang['ClientContacts.index.option_edit'] = 'Edit';
$lang['ClientContacts.index.option_delete'] = 'Delete';

$lang['ClientContacts.index.confirm_delete'] = 'Are you sure you want to delete this contact?';

$lang['ClientContacts.index.no_results'] = 'You have no contacts. To add your first contact, click the Add Contact button above.';


// Add contact
$lang['ClientContacts.add.heading_contact'] = 'Contact Information';
$lang['ClientContacts.add.page_title'] = 'Client #%1$s Add Contact'; // %1$s is the client ID number
$lang['ClientContacts.add.boxtitle_create'] = 'Add Contact';

$lang['ClientContacts.add.heading_settings'] = 'Additional Settings';
$lang['ClientContacts.add.field_contact_type'] = 'Contact Type';
$lang['ClientContacts.add.field_addsubmit'] = 'Create Contact';

$lang['ClientContacts.add.heading_authentication'] = 'Authentication';
$lang['ClientContacts.add.field_enable_login'] = 'Enable Login';
$lang['ClientContacts.add.field_username'] = 'Username';
$lang['ClientContacts.add.field_new_password'] = 'New Password';
$lang['ClientContacts.add.field_confirm_password'] = 'Confirm Password';

$lang['ClientContacts.add.heading_permissions'] = 'Permissions';


// Edit contact
$lang['ClientContacts.edit.heading_contact'] = 'Contact Information';
$lang['ClientContacts.edit.page_title'] = 'Client #%1$s Edit Contact'; // %1$s is the client ID number
$lang['ClientContacts.edit.boxtitle_edit'] = 'Edit Contact';

$lang['ClientContacts.edit.heading_settings'] = 'Additional Settings';
$lang['ClientContacts.edit.field_contact_type'] = 'Contact Type';
$lang['ClientContacts.edit.field_editsubmit'] = 'Update Contact';

$lang['ClientContacts.edit.heading_settings'] = 'Additional Settings';

$lang['ClientContacts.edit.heading_authentication'] = 'Authentication';
$lang['ClientContacts.edit.field_enable_login'] = 'Enable Login';
$lang['ClientContacts.edit.field_username'] = 'Username';
$lang['ClientContacts.edit.field_new_password'] = 'New Password';
$lang['ClientContacts.edit.field_confirm_password'] = 'Confirm Password';

$lang['ClientContacts.edit.heading_permissions'] = 'Permissions';

$lang['ClientContacts.tooltip.client_invoices'] = 'Display Invoices on the dashboard, as well as any payment reminder alerts.';
$lang['ClientContacts.tooltip.client_services'] = 'Display Services on the dashboard, and allow them to be managed.';
$lang['ClientContacts.tooltip.client_transactions'] = 'Display Transactions on the dashboard.';
$lang['ClientContacts.tooltip.client_contacts'] = 'Allow Contacts to be managed.';
$lang['ClientContacts.tooltip.client_accounts'] = 'Allow Payment Accounts to be managed.';
$lang['ClientContacts.tooltip.client_emails'] = 'Display a list of email history.';
$lang['ClientContacts.tooltip._invoice_delivery'] = 'Allow the invoice delivery method to be viewed and changed.';
$lang['ClientContacts.tooltip._credits'] = 'Allow the account credits to be viewed.';


// Set Contact View
$lang['ClientContacts.setcontactview.text_none'] = 'None';


// Contact Info partial
$lang['ClientContacts.contact_info.heading_contact'] = 'Contact Information';
$lang['ClientContacts.contact_info.text_select_contact'] = 'You may select an existing contact to pre-populate this form.';

$lang['ClientContacts.contact_info.field_contact_id'] = 'Copy Contact Information From';
$lang['ClientContacts.contact_info.field_first_name'] = 'First Name';
$lang['ClientContacts.contact_info.field_last_name'] = 'Last Name';
$lang['ClientContacts.contact_info.field_company'] = 'Company';
$lang['ClientContacts.contact_info.field_title'] = 'Title';
$lang['ClientContacts.contact_info.field_address1'] = 'Address 1';
$lang['ClientContacts.contact_info.field_address2'] = 'Address 2';
$lang['ClientContacts.contact_info.field_city'] = 'City';
$lang['ClientContacts.contact_info.field_country'] = 'Country';
$lang['ClientContacts.contact_info.field_state'] = 'State';
$lang['ClientContacts.contact_info.field_zip'] = 'Zip/Postal Code';
$lang['ClientContacts.contact_info.field_email'] = 'Email';


// Phone Number partial
$lang['ClientContacts.phone_numbers.heading_phone'] = 'Phone Numbers';
$lang['ClientContacts.phone_numbers.categorylink_number'] = 'Add Additional';

$lang['ClientContacts.phone_numbers.field_phonetype'] = 'Type';
$lang['ClientContacts.phone_numbers.field_phonelocation'] = 'Location';
$lang['ClientContacts.phone_numbers.field_phonenumber'] = 'Number';
$lang['ClientContacts.phone_numbers.field_phoneoptions'] = 'Options';
$lang['ClientContacts.phone_numbers.text_remove'] = 'Remove';


// Navigation
$lang['ClientContacts.navigation.nav_contacts'] = 'Contacts';
$lang['ClientContacts.navigation.nav_contacts_add'] = 'Add Contact';
$lang['ClientContacts.navigation.nav_return'] = 'Return to Dashboard';
