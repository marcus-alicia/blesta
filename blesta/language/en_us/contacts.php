<?php
/**
 * Language definitions for the Contacts model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Contact errors
$lang['Contacts.!error.client_id.exists'] = 'Invalid client ID.';
$lang['Contacts.!error.user_id.exists'] = 'Invalid user ID.';
$lang['Contacts.!error.contact_type.format'] = 'Invalid contact type.';
$lang['Contacts.!error.contact_type.inv_address_to'] = 'Invoices are set to be addressed to this contact and must be changed before updating this contact.';
$lang['Contacts.!error.contact_type_id.format'] = 'Invalid contact type ID.';
$lang['Contacts.!error.first_name.empty'] = 'Please enter a first name.';
$lang['Contacts.!error.last_name.empty'] = 'Please enter a last name.';
$lang['Contacts.!error.title.length'] = 'Title length may not exceed 64 characters.';
$lang['Contacts.!error.title.empty'] = 'Please enter a title.';
$lang['Contacts.!error.company.length'] = 'Company length may not exceed 128 characters.';
$lang['Contacts.!error.company.empty'] = 'Please enter a company.';
$lang['Contacts.!error.email.format'] = 'Invalid email address.';
$lang['Contacts.!error.email.unique'] = 'Email address is already taken.';
$lang['Contacts.!error.city.empty'] = 'Please enter a city.';
$lang['Contacts.!error.state.length'] = 'State length may not exceed 3 characters.';
$lang['Contacts.!error.state.country_exists'] = 'Please select the country that matches the selected state.';
$lang['Contacts.!error.state.empty'] = 'Please select a state/province.';
$lang['Contacts.!error.country.length'] = 'Country length may not exceed 3 characters.';
$lang['Contacts.!error.country.empty'] = 'Please select a country.';
$lang['Contacts.!error.contact_id.exists'] = 'Invalid contact ID.';
$lang['Contacts.!error.contact_id.primary'] = 'The primary client contact may not be deleted.';
$lang['Contacts.!error.address1.empty'] = 'Please enter the first line of your address.';
$lang['Contacts.!error.address2.empty'] = 'Please enter the second line of your address.';
$lang['Contacts.!error.zip.empty'] = 'Please enter a zip/postal code.';
$lang['Contacts.!error.phone.required'] = 'Please enter at least one phone number.';
$lang['Contacts.!error.fax.required'] = 'Please enter at least one fax number.';

$lang['Contacts.!error.first_name.read_only'] = 'First name is a read-only field and cannot be updated, contact us to change.';
$lang['Contacts.!error.last_name.read_only'] = 'Last name is a read-only field and cannot be updated, contact us to change.';
$lang['Contacts.!error.title.read_only'] = 'Title is a read-only field and cannot be updated, contact us to change.';
$lang['Contacts.!error.company.read_only'] = 'Company is a read-only field and cannot be updated, contact us to change.';
$lang['Contacts.!error.email.read_only'] = 'Address is a read-only field and cannot be updated, contact us to change.';
$lang['Contacts.!error.city.read_only'] = 'City is a read-only field and cannot be updated, contact us to change.';
$lang['Contacts.!error.state.read_only'] = 'State/province is a read-only field and cannot be updated, contact us to change.';
$lang['Contacts.!error.country.read_only'] = 'Country is a read-only field and cannot be updated, contact us to change.';
$lang['Contacts.!error.address1.read_only'] = 'The first address line is a read-only field and cannot be updated, contact us to change.';
$lang['Contacts.!error.address2.read_only'] = 'The second address line is a read-only field and cannot be updated, contact us to change.';
$lang['Contacts.!error.zip.read_only'] = 'Zip/postal code is a read-only field and cannot be updated, contact us to change.';

// Contact number errors
$lang['Contacts.!error.number.empty'] = 'Please enter a contact number.';
$lang['Contacts.!error.type.format'] = 'Invalid type.';
$lang['Contacts.!error.location.format'] = 'Invalid location.';
$lang['Contacts.!error.id.exists'] = 'Invalid contact number ID.';

// Contact type errors
$lang['Contacts.!error.company_id.format'] = 'Invalid company ID.';
$lang['Contacts.!error.name.empty'] = 'Please enter a name.';
$lang['Contacts.!error.is_lang.format'] = 'is_lang must be a number.';
$lang['Contacts.!error.is_lang.length'] = 'is_lang length may not exceed 1 character.';
$lang['Contacts.!error.contact_type_id.exists'] = 'Invalid contact type ID.';


// Text
$lang['Contacts.getcontacttypes.primary'] = 'Primary';
$lang['Contacts.getcontacttypes.billing'] = 'Billing';
$lang['Contacts.getcontacttypes.other'] = 'Other';

$lang['Contacts.getnumbertypes.phone'] = 'Phone';
$lang['Contacts.getnumbertypes.fax'] = 'Fax';

$lang['Contacts.getnumberlocations.home'] = 'Home';
$lang['Contacts.getnumberlocations.work'] = 'Work';
$lang['Contacts.getnumberlocations.mobile'] = 'Mobile';

$lang['Contacts.getPermissionOptions.client_invoices'] = 'Invoices';
$lang['Contacts.getPermissionOptions.client_services'] = 'Services';
$lang['Contacts.getPermissionOptions.client_transactions'] = 'Transactions';
$lang['Contacts.getPermissionOptions.client_contacts'] = 'Contacts';
$lang['Contacts.getPermissionOptions.client_accounts'] = 'Payment Accounts';
$lang['Contacts.getPermissionOptions.client_emails'] = 'Email History';
$lang['Contacts.getPermissionOptions._managed'] = 'Managed Accounts';
$lang['Contacts.getPermissionOptions._invoice_delivery'] = 'Invoice Delivery';
$lang['Contacts.getPermissionOptions._credits'] = 'Credits';
