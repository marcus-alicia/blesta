<?php
/**
 * en_us language for the ispmanager module.
 */
// Basics
$lang['Ispmanager.name'] = 'ISPmanager';
$lang['Ispmanager.description'] = 'ISPmanager provides rich feature set for managing websites, creating users, handling domains, emails, databases, etc.';
$lang['Ispmanager.module_row'] = 'Server';
$lang['Ispmanager.module_row_plural'] = 'Servers';
$lang['Ispmanager.module_group'] = 'Server Group';
$lang['Ispmanager.tab_client_actions'] = 'Actions';

// Module management
$lang['Ispmanager.add_module_row'] = 'Add Server';
$lang['Ispmanager.add_module_group'] = 'Add Server Group';
$lang['Ispmanager.manage.module_rows_title'] = 'Servers';
$lang['Ispmanager.manage.module_groups_title'] = 'Server Groups';
$lang['Ispmanager.manage.module_rows_heading.name'] = 'Server Label';
$lang['Ispmanager.manage.module_rows_heading.hostname'] = 'Hostname';
$lang['Ispmanager.manage.module_rows_heading.accounts'] = 'Accounts';
$lang['Ispmanager.manage.module_rows_heading.options'] = 'Options';
$lang['Ispmanager.manage.module_groups_heading.name'] = 'Group Name';
$lang['Ispmanager.manage.module_groups_heading.servers'] = 'Server Count';
$lang['Ispmanager.manage.module_groups_heading.options'] = 'Options';
$lang['Ispmanager.manage.module_rows.count'] = '%1$s / %2$s'; // %1$s is the current number of accounts, %2$s is the total number of accounts available
$lang['Ispmanager.manage.module_rows.edit'] = 'Edit';
$lang['Ispmanager.manage.module_groups.edit'] = 'Edit';
$lang['Ispmanager.manage.module_rows.delete'] = 'Delete';
$lang['Ispmanager.manage.module_groups.delete'] = 'Delete';
$lang['Ispmanager.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this server?';
$lang['Ispmanager.manage.module_groups.confirm_delete'] = 'Are you sure you want to delete this server group?';
$lang['Ispmanager.manage.module_rows_no_results'] = 'There are no servers.';
$lang['Ispmanager.manage.module_groups_no_results'] = 'There are no server groups.';

$lang['Ispmanager.order_options.first'] = 'First Non-full Server';
$lang['Ispmanager.order_options.roundrobin'] = 'Evenly Distribute Among Servers';

// Add row
$lang['Ispmanager.add_row.box_title'] = 'Add ISPmanager Server';
$lang['Ispmanager.add_row.basic_title'] = 'Basic Settings';
$lang['Ispmanager.add_row.name_servers_title'] = 'Name Servers';
$lang['Ispmanager.add_row.notes_title'] = 'Notes';
$lang['Ispmanager.add_row.name_server_btn'] = 'Add Additional Name Server';
$lang['Ispmanager.add_row.name_server_col'] = 'Name Server';
$lang['Ispmanager.add_row.name_server_host_col'] = 'Hostname';
$lang['Ispmanager.add_row.name_server'] = 'Name server %1$s'; // %1$s is the name server number (e.g. 3)
$lang['Ispmanager.add_row.remove_name_server'] = 'Remove';
$lang['Ispmanager.add_row.add_btn'] = 'Add Server';

$lang['Ispmanager.edit_row.box_title'] = 'Edit ISPmanager Server';
$lang['Ispmanager.edit_row.basic_title'] = 'Basic Settings';
$lang['Ispmanager.edit_row.name_servers_title'] = 'Name Servers';
$lang['Ispmanager.edit_row.notes_title'] = 'Notes';
$lang['Ispmanager.edit_row.name_server_btn'] = 'Add Additional Name Server';
$lang['Ispmanager.edit_row.name_server_col'] = 'Name Server';
$lang['Ispmanager.edit_row.name_server_host_col'] = 'Hostname';
$lang['Ispmanager.edit_row.name_server'] = 'Name server %1$s'; // %1$s is the name server number (e.g. 3)
$lang['Ispmanager.edit_row.remove_name_server'] = 'Remove';
$lang['Ispmanager.edit_row.add_btn'] = 'Edit Server';

$lang['Ispmanager.row_meta.server_name'] = 'Server Label';
$lang['Ispmanager.row_meta.host_name'] = 'Hostname';
$lang['Ispmanager.row_meta.user_name'] = 'User Name';
$lang['Ispmanager.row_meta.password'] = 'Password';
$lang['Ispmanager.row_meta.use_ssl'] = 'Use SSL when connecting to the API (recommended)';
$lang['Ispmanager.row_meta.account_limit'] = 'Account Limit';

// Client actions
$lang['Ispmanager.tab_client_actions.change_password'] = 'Change Password';
$lang['Ispmanager.tab_client_actions.field_ispmanager_password'] = 'Password';
$lang['Ispmanager.tab_client_actions.field_ispmanager_confirm_password'] = 'Confirm Password';
$lang['Ispmanager.tab_client_actions.field_password_submit'] = 'Update Password';

// Package fields
$lang['Ispmanager.package_fields.template'] = 'Template';

// Service fields
$lang['Ispmanager.service_field.domain'] = 'Domain';
$lang['Ispmanager.service_field.username'] = 'Username';
$lang['Ispmanager.service_field.password'] = 'Password';

// Service info
$lang['Ispmanager.service_info.username'] = 'Username';
$lang['Ispmanager.service_info.password'] = 'Password';
$lang['Ispmanager.service_info.server'] = 'Server';
$lang['Ispmanager.service_info.options'] = 'Options';
$lang['Ispmanager.service_info.option_login'] = 'Log in';

// Tooltips
$lang['Ispmanager.service_field.tooltip.domain_edit'] = 'This change will not affect ISPmanager, but only change the local record in Blesta.';
$lang['Ispmanager.service_field.tooltip.username_edit'] = 'This change will not affect ISPmanager, but only change the local record in Blesta.';
$lang['Ispmanager.service_field.tooltip.username'] = 'You may leave the username blank to automatically generate one.';
$lang['Ispmanager.service_field.tooltip.password'] = 'You may leave the password blank to automatically generate one.';

// Errors
$lang['Ispmanager.!error.server_name_valid'] = 'You must enter a Server Label.';
$lang['Ispmanager.!error.host_name_valid'] = 'The Hostname appears to be invalid.';
$lang['Ispmanager.!error.user_name_valid'] = 'The User Name appears to be invalid.';
$lang['Ispmanager.!error.password_valid'] = 'The Password appears to be invalid.';
$lang['Ispmanager.!error.password_valid_connection'] = 'A connection to the server could not be established. Please check to ensure that the User Name and Password are correct.';
$lang['Ispmanager.!error.account_limit_valid'] = 'Account Limit must be left blank (for unlimited accounts) or set to some integer value.';
$lang['Ispmanager.!error.name_servers_valid'] = 'One or more of the name servers entered are invalid.';
$lang['Ispmanager.!error.name_servers_count'] = 'You must define at least 2 name servers.';
$lang['Ispmanager.!error.meta[template].empty'] = 'A Template is required.';
$lang['Ispmanager.!error.api.internal'] = 'An internal error occurred, or the server did not respond to the request.';
$lang['Ispmanager.!error.module_row.missing'] = 'An internal error occurred. The module row is unavailable.';

$lang['Ispmanager.!error.ispmanager_domain.format'] = 'Please enter a valid domain name, e.g. domain.com.';
$lang['Ispmanager.!error.ispmanager_domain.test'] = "Domain name can not start with 'test'.";
$lang['Ispmanager.!error.ispmanager_username.format'] = 'The username may contain only letters and numbers.';
$lang['Ispmanager.!error.ispmanager_username.test'] = "The username may not begin with 'test'.";
$lang['Ispmanager.!error.ispmanager_username.length'] = 'The username must be between 1 and 16 characters in length.';
$lang['Ispmanager.!error.ispmanager_password.valid'] = 'Password must be at least 8 characters in length.';
$lang['Ispmanager.!error.ispmanager_password.matches'] = 'Password and Confirm Password do not match.';

$lang['Ispmanager.!error.api'] = 'An internal error occurred, or the server did not respond to the request.';
