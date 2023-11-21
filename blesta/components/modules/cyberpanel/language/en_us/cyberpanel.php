<?php
/**
 * en_us language for the cyberpanel module.
 */
// Basics
$lang['Cyberpanel.name'] = 'CyberPanel';
$lang['Cyberpanel.description'] = 'CyberPanel is a next generation hosting control panel powered by OpenLiteSpeed. Built for speed, security, and reliability.';
$lang['Cyberpanel.module_row'] = 'Server';
$lang['Cyberpanel.module_row_plural'] = 'Servers';
$lang['Cyberpanel.module_group'] = 'Server Group';
$lang['Cyberpanel.tab_client_actions'] = 'Actions';

// Module management
$lang['Cyberpanel.add_module_row'] = 'Add Server';
$lang['Cyberpanel.add_module_group'] = 'Add Server Group';
$lang['Cyberpanel.manage.module_rows_title'] = 'Servers';
$lang['Cyberpanel.manage.module_groups_title'] = 'Server Groups';
$lang['Cyberpanel.manage.module_rows_heading.name'] = 'Server Label';
$lang['Cyberpanel.manage.module_rows_heading.hostname'] = 'Hostname';
$lang['Cyberpanel.manage.module_rows_heading.options'] = 'Options';
$lang['Cyberpanel.manage.module_groups_heading.name'] = 'Group Name';
$lang['Cyberpanel.manage.module_groups_heading.servers'] = 'Server Count';
$lang['Cyberpanel.manage.module_groups_heading.options'] = 'Options';
$lang['Cyberpanel.manage.module_rows.edit'] = 'Edit';
$lang['Cyberpanel.manage.module_groups.edit'] = 'Edit';
$lang['Cyberpanel.manage.module_rows.delete'] = 'Delete';
$lang['Cyberpanel.manage.module_groups.delete'] = 'Delete';
$lang['Cyberpanel.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this server?';
$lang['Cyberpanel.manage.module_groups.confirm_delete'] = 'Are you sure you want to delete this server group?';
$lang['Cyberpanel.manage.module_rows_no_results'] = 'There are no servers.';
$lang['Cyberpanel.manage.module_groups_no_results'] = 'There are no server groups.';

$lang['Cyberpanel.order_options.first'] = 'First Non-full Server';
$lang['Cyberpanel.order_options.roundrobin'] = 'Evenly Distribute Among Servers';

// Add row
$lang['Cyberpanel.add_row.box_title'] = 'Add CyberPanel Server';
$lang['Cyberpanel.add_row.basic_title'] = 'Basic Settings';
$lang['Cyberpanel.add_row.name_servers_title'] = 'Name Servers';
$lang['Cyberpanel.add_row.notes_title'] = 'Notes';
$lang['Cyberpanel.add_row.name_server_btn'] = 'Add Additional Name Server';
$lang['Cyberpanel.add_row.name_server_col'] = 'Name Server';
$lang['Cyberpanel.add_row.name_server_host_col'] = 'Hostname';
$lang['Cyberpanel.add_row.name_server'] = 'Name server %1$s'; // %1$s is the name server number (e.g. 3)
$lang['Cyberpanel.add_row.remove_name_server'] = 'Remove';
$lang['Cyberpanel.add_row.add_btn'] = 'Add Server';

$lang['Cyberpanel.edit_row.box_title'] = 'Edit CyberPanel Server';
$lang['Cyberpanel.edit_row.basic_title'] = 'Basic Settings';
$lang['Cyberpanel.edit_row.name_servers_title'] = 'Name Servers';
$lang['Cyberpanel.edit_row.notes_title'] = 'Notes';
$lang['Cyberpanel.edit_row.name_server_btn'] = 'Add Additional Name Server';
$lang['Cyberpanel.edit_row.name_server_col'] = 'Name Server';
$lang['Cyberpanel.edit_row.name_server_host_col'] = 'Hostname';
$lang['Cyberpanel.edit_row.name_server'] = 'Name server %1$s'; // %1$s is the name server number (e.g. 3)
$lang['Cyberpanel.edit_row.remove_name_server'] = 'Remove';
$lang['Cyberpanel.edit_row.add_btn'] = 'Edit Server';

$lang['Cyberpanel.row_meta.server_name'] = 'Server Label';
$lang['Cyberpanel.row_meta.host_name'] = 'Hostname';
$lang['Cyberpanel.row_meta.admin_username'] = 'Admin Username';
$lang['Cyberpanel.row_meta.admin_password'] = 'Admin Password';
$lang['Cyberpanel.row_meta.use_ssl'] = 'Use SSL when connecting to the API (recommended)';

// Package fields
$lang['Cyberpanel.package_fields.package'] = 'Package Name';

// Service fields
$lang['Cyberpanel.service_field.domain'] = 'Domain';
$lang['Cyberpanel.service_field.username'] = 'Username';
$lang['Cyberpanel.service_field.password'] = 'Password';

// Client actions
$lang['Cyberpanel.tab_client_actions.change_password'] = 'Change Password';
$lang['Cyberpanel.tab_client_actions.field_cyberpanel_password'] = 'Password';
$lang['Cyberpanel.tab_client_actions.field_cyberpanel_confirm_password'] = 'Confirm Password';
$lang['Cyberpanel.tab_client_actions.field_password_submit'] = 'Update Password';

// Service info
$lang['Cyberpanel.service_info.username'] = 'Username';
$lang['Cyberpanel.service_info.password'] = 'Password';
$lang['Cyberpanel.service_info.server'] = 'Server';
$lang['Cyberpanel.service_info.options'] = 'Options';
$lang['Cyberpanel.service_info.option_login'] = 'Log in';

// Tooltips
$lang['Cyberpanel.service_field.tooltip.username'] = 'You may leave the username blank to automatically generate one.';
$lang['Cyberpanel.service_field.tooltip.password'] = 'You may leave the password blank to automatically generate one.';

// Errors
$lang['Cyberpanel.!error.server_name_valid'] = 'You must enter a Server Label.';
$lang['Cyberpanel.!error.host_name_valid'] = 'The Hostname appears to be invalid.';
$lang['Cyberpanel.!error.remote_admin_username_valid'] = 'The Admin Username appears to be invalid.';
$lang['Cyberpanel.!error.remote_admin_password_valid'] = 'The Admin Password appears to be invalid.';
$lang['Cyberpanel.!error.remote_admin_password_valid_connection'] = 'A connection to the server could not be established. Please check to ensure that the Hostname and the Admin Password are correct.';
$lang['Cyberpanel.!error.name_servers_valid'] = 'One or more of the name servers entered are invalid.';
$lang['Cyberpanel.!error.name_servers_count'] = 'You must define at least 2 name servers.';
$lang['Cyberpanel.!error.meta[package].empty'] = 'A CyberPanel Package is required.';
$lang['Cyberpanel.!error.api.internal'] = 'An internal error occurred, or the server did not respond to the request.';
$lang['Cyberpanel.!error.module_row.missing'] = 'An internal error occurred. The module row is unavailable.';

$lang['Cyberpanel.!error.cyberpanel_domain.format'] = 'Please enter a valid domain name, e.g. domain.com.';
$lang['Cyberpanel.!error.cyberpanel_domain.test'] = "Domain name can not start with 'test'.";
$lang['Cyberpanel.!error.cyberpanel_username.format'] = 'The username may contain only letters and numbers and may not start with a number.';
$lang['Cyberpanel.!error.cyberpanel_username.test'] = "The username may not begin with 'test'.";
$lang['Cyberpanel.!error.cyberpanel_username.length'] = 'The username must be between 1 and 16 characters in length.';
$lang['Cyberpanel.!error.cyberpanel_password.valid'] = 'Password must be at least 8 characters in length.';
$lang['Cyberpanel.!error.cyberpanel_password.matches'] = 'Password and Confirm Password do not match.';
