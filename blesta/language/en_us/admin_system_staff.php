<?php
/**
 * Language definitions for the Admin System Staff settings controller/views
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Success messages
$lang['AdminSystemStaff.!success.staff_added'] = 'That staff member has been successfully added!';
$lang['AdminSystemStaff.!success.staff_updated'] = 'That staff member has been successfully updated!';

$lang['AdminSystemStaff.!success.group_added'] = 'The staff group, "%1$s", has been successfully created!'; // %1$s is the name of the staff group
$lang['AdminSystemStaff.!success.group_updated'] = 'The staff group, "%1$s", has been successfully updated!'; // %1$s is the name of the staff group
$lang['AdminSystemStaff.!success.group_deleted'] = 'The staff group, "%1$s", has been successfully deleted!'; // %1$s is the name of the staff group


// Manage Staff
$lang['AdminSystemStaff.manage.page_title'] = 'Settings > System > Staff > Manage Staff';
$lang['AdminSystemStaff.manage.boxtitle_manage'] = 'Manage Staff';

$lang['AdminSystemStaff.manage.category_active'] = 'Active';
$lang['AdminSystemStaff.manage.category_inactive'] = 'Inactive';
$lang['AdminSystemStaff.manage.categorylink_addstaff'] = 'Add Staff';

$lang['AdminSystemStaff.manage.heading_name']  = 'Name';
$lang['AdminSystemStaff.manage.heading_email']  = 'E-mail';
$lang['AdminSystemStaff.manage.heading_options']  = 'Options';

$lang['AdminSystemStaff.manage.no_results'] = 'There are no staff members with this status.';
$lang['AdminSystemStaff.manage.confirm_deactivate'] = 'Really deactivate this staff member?';
$lang['AdminSystemStaff.manage.confirm_reactivate'] = 'Really reactivate this staff member?';

$lang['AdminSystemStaff.manage.option_edit'] = 'Edit';
$lang['AdminSystemStaff.manage.option_deactivate'] = 'Deactivate';
$lang['AdminSystemStaff.manage.option_reactivate'] = 'Reactivate';
$lang['AdminSystemStaff.groups.option_delete'] = 'Delete';


// Add Staff
$lang['AdminSystemStaff.add.page_title'] = 'Settings > System > Staff > Add Staff';
$lang['AdminSystemStaff.add.boxtitle_addstaff'] = 'Add Staff';

$lang['AdminSystemStaff.add.heading_contact'] = 'Contact Info';
$lang['AdminSystemStaff.add.heading_authentication'] = 'Authentication';
$lang['AdminSystemStaff.add.heading_groups'] = 'Groups';

$lang['AdminSystemStaff.add.field_firstname'] = 'First Name';
$lang['AdminSystemStaff.add.field_lastname'] = 'Last Name';
$lang['AdminSystemStaff.add.field_email'] = 'E-mail';
$lang['AdminSystemStaff.add.field_numbermobile'] = 'Mobile Number';
$lang['AdminSystemStaff.add.field_username'] = 'Username';
$lang['AdminSystemStaff.add.field_username_username'] = 'Enter a username';
$lang['AdminSystemStaff.add.field_username_email'] = 'Use the email address as the username';
$lang['AdminSystemStaff.add.field_password'] = 'Password';
$lang['AdminSystemStaff.add.field_confirmpass'] = 'Confirm Password';
$lang['AdminSystemStaff.add.field_twofactormode'] = 'Two Factor Authentication';
$lang['AdminSystemStaff.add.field_twofactorkey'] = 'Two Factor Key';
$lang['AdminSystemStaff.add.field_twofactorpin'] = 'Two Factor Pin';

$lang['AdminSystemStaff.add.text_membergroups'] = 'Member Groups';
$lang['AdminSystemStaff.add.text_availablegroups'] = 'Available Groups';

$lang['AdminSystemStaff.add.field_addsubmit'] = 'Create Staff';


// Edit Staff
$lang['AdminSystemStaff.edit.page_title'] = 'Settings > System > Staff > Edit Staff';
$lang['AdminSystemStaff.edit.boxtitle_editstaff'] = 'Edit Staff';

$lang['AdminSystemStaff.edit.heading_contact'] = 'Contact Info';
$lang['AdminSystemStaff.edit.heading_authentication'] = 'Authentication';
$lang['AdminSystemStaff.edit.heading_groups'] = 'Groups';

$lang['AdminSystemStaff.edit.field_firstname'] = 'First Name';
$lang['AdminSystemStaff.edit.field_lastname'] = 'Last Name';
$lang['AdminSystemStaff.edit.field_username'] = 'Username';
$lang['AdminSystemStaff.edit.field_email'] = 'E-mail';
$lang['AdminSystemStaff.edit.field_numbermobile'] = 'Mobile Number';
$lang['AdminSystemStaff.edit.field_password'] = 'New Password';
$lang['AdminSystemStaff.edit.field_confirmpass'] = 'Confirm Password';
$lang['AdminSystemStaff.edit.field_twofactormode'] = 'Two Factor Authentication';
$lang['AdminSystemStaff.edit.field_twofactorkey'] = 'Two Factor Key';
$lang['AdminSystemStaff.edit.field_twofactorpin'] = 'Two Factor Pin';

$lang['AdminSystemStaff.edit.text_membergroups'] = 'Member Groups';
$lang['AdminSystemStaff.edit.text_availablegroups'] = 'Available Groups';

$lang['AdminSystemStaff.edit.field_editsubmit'] = 'Edit Staff';


// Staff Groups
$lang['AdminSystemStaff.groups.page_title'] = 'Settings > System > Staff > Staff Groups';
$lang['AdminSystemStaff.groups.boxtitle_groups'] = 'Staff Groups';

$lang['AdminSystemStaff.groups.categorylink_addgroup'] = 'Create Group';

$lang['AdminSystemStaff.groups.heading_name'] = 'Name';
$lang['AdminSystemStaff.groups.heading_company_name'] = 'Company Name';
$lang['AdminSystemStaff.groups.heading_staff'] = 'Number of Staff';
$lang['AdminSystemStaff.groups.heading_options'] = 'Options';

$lang['AdminSystemStaff.groups.no_results'] = 'There are no staff groups.';

$lang['AdminSystemStaff.groups.option_edit'] = 'Edit';
$lang['AdminSystemStaff.groups.option_delete'] = 'Delete';

$lang['AdminSystemStaff.groups.modal_delete'] = 'Are you sure you want to delete this staff group?';

$lang['AdminSystemStaff.!groups.text_separator'] = '/';
$lang['AdminSystemStaff.!groups.text_group_start'] = '(';
$lang['AdminSystemStaff.!groups.text_group_end'] = ')';


// Add Staff Group
$lang['AdminSystemStaff.addgroup.page_title'] = 'Settings > System > Staff > Create Group';
$lang['AdminSystemStaff.addgroup.boxtitle_addgroup'] = 'Create Group';

$lang['AdminSystemStaff.addgroup.field_name'] = 'Name';
$lang['AdminSystemStaff.addgroup.field_company'] = 'Company';
$lang['AdminSystemStaff.addgroup.field_session_lock'] = 'Log Out On IP Address Change';
$lang['AdminSystemStaff.addgroup.field_groupsubmit'] = 'Create Group';
$lang['AdminSystemStaff.addgroup.heading_general'] = 'General';
$lang['AdminSystemStaff.addgroup.heading_permissions'] = 'Access Control List';
$lang['AdminSystemStaff.addgroup.heading_email_notices'] = 'Email BCC Notices';
$lang['AdminSystemStaff.addgroup.heading_subscription_email_notices'] = 'Email Subscription Notices';

$lang['AdminSystemStaff.addgroup.text_check_all'] = 'Check All';
$lang['AdminSystemStaff.addgroup.text_uncheck_all'] = 'Uncheck All';


// Edit Staff Group
$lang['AdminSystemStaff.editgroup.page_title'] = 'Settings > System > Staff > Edit Group';
$lang['AdminSystemStaff.editgroup.boxtitle_editgroup'] = 'Edit Group';

$lang['AdminSystemStaff.editgroup.field_name'] = 'Name';
$lang['AdminSystemStaff.editgroup.field_company'] = 'Company';
$lang['AdminSystemStaff.editgroup.field_session_lock'] = 'Log Out On IP Address Change';
$lang['AdminSystemStaff.editgroup.field_groupsubmit'] = 'Edit Group';
$lang['AdminSystemStaff.editgroup.heading_general'] = 'General';
$lang['AdminSystemStaff.editgroup.heading_permissions'] = 'Access Control List';
$lang['AdminSystemStaff.editgroup.heading_email_notices'] = 'Email BCC Notices';
$lang['AdminSystemStaff.editgroup.heading_subscription_email_notices'] = 'Email Subscription Notices';
$lang['AdminSystemStaff.editgroup.dialog_confirm_edit_assigned'] = 'You are currently assigned to this staff group. Any changes you make will take effect immediately and you may be unable to access this area again. Are you sure you want to make these changes?';
