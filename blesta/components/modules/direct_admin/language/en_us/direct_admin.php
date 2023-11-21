<?php
/**
 * Language for DirectAdmin
 */

// Errors
$lang['DirectAdmin.!error.server_name.empty'] = 'You must enter a Server Label.';
$lang['DirectAdmin.!error.host_name.format'] = 'The Host Name appears to be invalid.';
$lang['DirectAdmin.!error.port.format'] = 'The port must be a number.';
$lang['DirectAdmin.!error.user_name.empty'] = 'You must enter a User Name.';
$lang['DirectAdmin.!error.password.format'] = 'You must enter a Password.';
$lang['DirectAdmin.!error.use_ssl.format'] = 'Use SSL must be either true or false.';
$lang['DirectAdmin.!error.account_limit.valid'] = 'Account Limit must be left blank (for unlimited accounts) or set to some integer value.';

$lang['DirectAdmin.!error.name_servers.valid'] = 'One or more of the name servers entered are invalid.';
$lang['DirectAdmin.!error.name_servers.count'] = 'You must define at least 2 name servers.';

$lang['DirectAdmin.!error.api.internal'] = 'An internal error occurred, or the server did not respond to the request.';

$lang['DirectAdmin.!error.meta[type].format'] = 'Account Type must be either user or reseller.';
$lang['DirectAdmin.!error.meta[package].empty'] = 'A DirectAdmin package is required.';
$lang['DirectAdmin.!error.meta[ip].empty'] = 'An IP address is required.';

$lang['DirectAdmin.!error.direct_admin_domain.format'] = 'Please enter a valid domain name of the form: domain.com';
$lang['DirectAdmin.!error.direct_admin_username.format'] = 'The username may only contain alphanumeric characters.';
$lang['DirectAdmin.!error.direct_admin_username.length'] = 'The username must be between 4 and 8 characters in length.';
$lang['DirectAdmin.!error.direct_admin_password.format'] = 'Make sure the password contains the following: At least 12 characters, at least 1 upper-case character A-Z, at least 1 lower-case character a-z, at least 1 number 0-9, and at least 1 special character from the set: !"#$%&\'()*+,-./:;<=>?@[]^_` {|}';
$lang['DirectAdmin.!error.direct_admin_password.matches'] = 'Password and Confirm Password do not match.';
$lang['DirectAdmin.!error.direct_admin_email.format'] = 'Please enter a valid email address.';

$lang['DirectAdmin.!error.change_package.type'] = 'The module does not support changing between user and reseller packages.';


// Basic
$lang['DirectAdmin.name'] = 'DirectAdmin';
$lang['DirectAdmin.description'] = 'DirectAdmin is a graphical web-based web hosting control panel designed to make administration of websites easier.';
$lang['DirectAdmin.module_row'] = 'Server';
$lang['DirectAdmin.module_row_plural'] = 'Servers';
$lang['DirectAdmin.module_group'] = 'Server Group';
$lang['DirectAdmin.tab_stats'] = 'Statistics';
$lang['DirectAdmin.tab_client_actions'] = "Actions";

$lang['DirectAdmin.order_options.first'] = 'First Non-full Server';
$lang['DirectAdmin.order_options.roundrobin'] = 'Evenly Distribute Among Servers';

$lang['DirectAdmin.servers.no_results'] = 'There are no servers.';
$lang['DirectAdmin.server_groups.no_results'] = 'There are no server groups.';


// Service fields
$lang['DirectAdmin.service_field.domain'] = 'Domain';
$lang['DirectAdmin.service_field.username'] = 'Username';
$lang['DirectAdmin.service_field.password'] = 'Password';
$lang['DirectAdmin.service_field.email'] = 'Email';
$lang['DirectAdmin.service_field.text_generate_password'] = 'Generate Password';

// Client actions
$lang['DirectAdmin.tab_client_actions.change_password'] = "Change Password";
$lang['DirectAdmin.tab_client_actions.field_direct_admin_password'] = "Password";
$lang['DirectAdmin.tab_client_actions.field_direct_admin_confirm_password'] = "Confirm Password";
$lang['DirectAdmin.tab_client_actions.field_password_submit'] = "Update Password";
$lang['DirectAdmin.tab_client_actions.text_generate_password'] = 'Generate Password';

// Service info
$lang['DirectAdmin.service_info.username'] = 'Username';
$lang['DirectAdmin.service_info.password'] = 'Password';
$lang['DirectAdmin.service_info.server'] = 'Server';
$lang['DirectAdmin.service_info.options'] = 'Options';
$lang['DirectAdmin.service_info.option_login'] = 'Log in';


// Package fields
$lang['DirectAdmin.package_fields.type'] = 'Account Type';
$lang['DirectAdmin.package_fields.type_user'] = 'User';
$lang['DirectAdmin.package_fields.type_reseller'] = 'Reseller';
$lang['DirectAdmin.package_fields.package'] = 'DirectAdmin Package';
$lang['DirectAdmin.package_fields.ip'] = 'IP Address';
$lang['DirectAdmin.package_fields.ip_shared'] = 'Shared';
$lang['DirectAdmin.package_fields.ip_assign'] = 'Assign';


// Module management
$lang['DirectAdmin.add_module_row'] = 'Add Server';
$lang['DirectAdmin.add_module_group'] = 'Add Server Group';
$lang['DirectAdmin.manage.module_rows_title'] = 'Servers';
$lang['DirectAdmin.manage.module_groups_title'] = 'Server Groups';
$lang['DirectAdmin.manage.module_rows_heading.name'] = 'Server Label';
$lang['DirectAdmin.manage.module_rows_heading.host_name'] = 'Host Name';
$lang['DirectAdmin.manage.module_rows_heading.accounts'] = 'Accounts';
$lang['DirectAdmin.manage.module_rows_heading.options'] = 'Options';
$lang['DirectAdmin.manage.module_groups_heading.name'] = 'Group Name';
$lang['DirectAdmin.manage.module_groups_heading.servers'] = 'Server Count';
$lang['DirectAdmin.manage.module_groups_heading.options'] = 'Options';
$lang['DirectAdmin.manage.module_rows.count'] = '%1$s / %2$s'; // %1$s is the current number of accounts, %2$s is the total number of accounts available
$lang['DirectAdmin.manage.module_rows.edit'] = 'Edit';
$lang['DirectAdmin.manage.module_groups.edit'] = 'Edit';
$lang['DirectAdmin.manage.module_rows.delete'] = 'Delete';
$lang['DirectAdmin.manage.module_groups.delete'] = 'Delete';
$lang['DirectAdmin.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this server?';
$lang['DirectAdmin.manage.module_groups.confirm_delete'] = 'Are you sure you want to delete this server group?';


// Row meta data for add/edit rows
$lang['DirectAdmin.row_meta.server_name'] = 'Server Label';
$lang['DirectAdmin.row_meta.host_name'] = 'Host Name';
$lang['DirectAdmin.row_meta.port'] = 'Port';
$lang['DirectAdmin.row_meta.default_port'] = '2222';
$lang['DirectAdmin.row_meta.user_name'] = 'User Name';
$lang['DirectAdmin.row_meta.password'] = 'Password';
$lang['DirectAdmin.row_meta.use_ssl'] = 'Use SSL when connecting to the API (recommended)';
$lang['DirectAdmin.row_meta.account_limit'] = 'Account Limit';
$lang['DirectAdmin.row_meta.name_server'] = 'Name server %1$s'; // %1$s is the name server number (e.g. 2)


// Add row
$lang['DirectAdmin.add_row.box_title'] = 'Add DirectAdmin Server';
$lang['DirectAdmin.add_row.basic_title'] = 'Basic Settings';
$lang['DirectAdmin.add_row.name_servers_title'] = 'Name Servers';
$lang['DirectAdmin.add_row.name_server_btn'] = 'Add Additional Name Server';
$lang['DirectAdmin.add_row.name_server_col'] = 'Name Server';
$lang['DirectAdmin.add_row.name_server_host_col'] = 'Hostname';
$lang['DirectAdmin.add_row.remove_name_server'] = 'Remove';
$lang['DirectAdmin.add_row.notes_title'] = 'Notes';
$lang['DirectAdmin.add_row.add_btn'] = 'Add Server';

// Edit row
$lang['DirectAdmin.edit_row.box_title'] = 'Edit DirectAdmin Server';
$lang['DirectAdmin.edit_row.basic_title'] = 'Basic Settings';
$lang['DirectAdmin.edit_row.name_servers_title'] = 'Name Servers';
$lang['DirectAdmin.edit_row.name_server_btn'] = 'Add Additional Name Server';
$lang['DirectAdmin.edit_row.name_server_col'] = 'Name Server';
$lang['DirectAdmin.edit_row.name_server_host_col'] = 'Hostname';
$lang['DirectAdmin.edit_row.remove_name_server'] = 'Remove';
$lang['DirectAdmin.edit_row.notes_title'] = 'Notes';
$lang['DirectAdmin.edit_row.add_btn'] = 'Edit Server';


// Client info
$lang['DirectAdmin.tab_client_stats'] = 'Statistics';
$lang['DirectAdmin.tab_client_stats.info_title'] = 'Information';
$lang['DirectAdmin.tab_client_stats.info_heading.field'] = 'Field';
$lang['DirectAdmin.tab_client_stats.info_heading.value'] = 'Value';

$lang['DirectAdmin.tab_client_stats.no_results'] = 'Statistical information is currently unavailable.';
