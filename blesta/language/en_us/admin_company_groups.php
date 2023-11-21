<?php
/**
 * Language definitions for the Admin Company Client Group settings controller/views
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Success messages
$lang['AdminCompanyGroups.!success.add_created'] = '%1$s has been successfully created!'; // %1$s is the name of the client group
$lang['AdminCompanyGroups.!success.edit_updated'] = '%1$s has been successfully edited!'; // %1$s is the name of the client group
$lang['AdminCompanyGroups.!success.delete_deleted'] = '%1$s was successfully deleted!'; // %1$s is the name of the client group

// Error messages
$lang['AdminCompanyGroups.!error.delete_failed'] = '%1$s is the default group and cannot be deleted.'; // %1$s is the name of the client group

// Tooltips
$lang['AdminCompanyGroups.!tooltip.force_email_usernames'] = 'Removes the ability for clients to define their own username.';
$lang['AdminCompanyGroups.!tooltip.email_verification'] = 'Check to send an email verification email when a new login is created or a client changes their email. A notice will appear on the clients profile until they are verified.';


// Index
$lang['AdminCompanyGroups.index.page_title'] = 'Settings > Company > Client Groups';
$lang['AdminCompanyGroups.index.boxtitle_groups'] = 'Client Groups';
$lang['AdminCompanyGroups.index.categorylink_addgroup'] = 'Create Group';

$lang['AdminCompanyGroups.index.text_name'] = 'Name';
$lang['AdminCompanyGroups.index.text_description'] = 'Description';
$lang['AdminCompanyGroups.index.text_clients'] = 'Number of Clients';
$lang['AdminCompanyGroups.index.text_options'] = 'Options';

$lang['AdminCompanyGroups.index.option_edit'] = 'Edit';
$lang['AdminCompanyGroups.index.option_delete'] = 'Delete';

$lang['AdminCompanyGroups.index.no_results'] = 'There are no client groups.';

$lang['AdminCompanyGroups.index.confirm_delete'] = 'Are you sure you want to delete this client group? All clients in this group will be moved to the default group.';


// Add group
$lang['AdminCompanyGroups.add.page_title'] = 'Settings > Company > Client Groups > Create Group';
$lang['AdminCompanyGroups.add.boxtitle_addgroup'] = 'Create Group';

$lang['AdminCompanyGroups.add.heading_basic'] = 'Basic Options';
$lang['AdminCompanyGroups.add.heading_invoice'] = 'Invoice and Charge Options';
$lang['AdminCompanyGroups.add.heading_late_fees'] = 'Late Fees';
$lang['AdminCompanyGroups.add.heading_delivery'] = 'Invoice Delivery';
$lang['AdminCompanyGroups.add.heading_payment'] = 'Notices';
$lang['AdminCompanyGroups.add.heading_client_settings'] = 'General Client Settings';
$lang['AdminCompanyGroups.add.heading_client_fields'] = 'Required Client Fields';

$lang['AdminCompanyGroups.add.field_name'] = 'Name';
$lang['AdminCompanyGroups.add.field_color'] = 'Color';
$lang['AdminCompanyGroups.add.field_description'] = 'Description';
$lang['AdminCompanyGroups.add.field_delivery_methods'] = 'Invoice Delivery Methods';
$lang['AdminCompanyGroups.add.field_company_settings'] = 'Use Company Settings (uncheck to specify below)';
$lang['AdminCompanyGroups.add.field_force_email_usernames'] = 'Enforce Email Addresses as Usernames';
$lang['AdminCompanyGroups.add.field_email_verification'] = 'Enable Email Verification';

$lang['AdminCompanyGroups.add.text_addsubmit'] = 'Create Group';


// Edit group
$lang['AdminCompanyGroups.edit.page_title'] = 'Settings > Company > Client Groups > Edit Group';
$lang['AdminCompanyGroups.edit.boxtitle_editgroup'] = 'Edit Group';

$lang['AdminCompanyGroups.edit.heading_basic'] = 'Basic Options';
$lang['AdminCompanyGroups.edit.heading_invoice'] = 'Invoice and Charge Options';
$lang['AdminCompanyGroups.edit.heading_late_fees'] = 'Late Fees';
$lang['AdminCompanyGroups.edit.heading_delivery'] = 'Invoice Delivery';
$lang['AdminCompanyGroups.edit.heading_payment'] = 'Notices';
$lang['AdminCompanyGroups.edit.heading_client_settings'] = 'General Client Settings';
$lang['AdminCompanyGroups.edit.heading_client_fields'] = 'Required Client Fields';

$lang['AdminCompanyGroups.edit.field_name'] = 'Name';
$lang['AdminCompanyGroups.edit.field_color'] = 'Color';
$lang['AdminCompanyGroups.edit.field_description'] = 'Description';
$lang['AdminCompanyGroups.edit.field_delivery_methods'] = 'Invoice Delivery Methods';
$lang['AdminCompanyGroups.edit.field_company_settings'] = 'Use Company Settings (uncheck to specify below)';
$lang['AdminCompanyGroups.edit.field_force_email_usernames'] = 'Enforce Email Addresses as Usernames';

$lang['AdminCompanyGroups.edit.text_editsubmit'] = 'Edit Group';
