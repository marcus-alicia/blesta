<?php
/**
 * en_us language for the apnscp module.
 */
// Basics
$lang['Apnscp.name'] = 'ApisCP';
$lang['Apnscp.description'] = 'Formerly called APNSCP.';
$lang['Apnscp.module_row.name'] = 'Server';
$lang['Apnscp.module_row_plural.name'] = 'Servers';
$lang['Apnscp.module_group.name'] = 'Server Group';
$lang['Apnscp.tab_stats'] = 'Statistics';
$lang['Apnscp.tab_client_stats'] = 'Statistics';
$lang['Apnscp.tab_client_actions'] = 'Actions';

// Module management
$lang['Apnscp.add_module_row'] = 'Add Server';
$lang['Apnscp.add_module_group'] = 'Add Server Group';
$lang['Apnscp.manage.module_rows_title'] = 'Servers';
$lang['Apnscp.manage.module_groups_title'] = 'Server Groups';
$lang['Apnscp.manage.module_rows_heading.name'] = 'Server Label';
$lang['Apnscp.manage.module_rows_heading.hostname'] = 'Hostname';
$lang['Apnscp.manage.module_rows_heading.accounts'] = 'Accounts';
$lang['Apnscp.manage.module_rows_heading.options'] = 'Options';
$lang['Apnscp.manage.module_groups_heading.name'] = 'Group Name';
$lang['Apnscp.manage.module_groups_heading.servers'] = 'Server Count';
$lang['Apnscp.manage.module_groups_heading.options'] = 'Options';
$lang['Apnscp.manage.module_rows.count'] = '%1$s / %2$s'; // %1$s is the current number of accounts, %2$s is the total number of accounts available
$lang['Apnscp.manage.module_rows.edit'] = 'Edit';
$lang['Apnscp.manage.module_groups.edit'] = 'Edit';
$lang['Apnscp.manage.module_rows.delete'] = 'Delete';
$lang['Apnscp.manage.module_groups.delete'] = 'Delete';
$lang['Apnscp.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this server?';
$lang['Apnscp.manage.module_groups.confirm_delete'] = 'Are you sure you want to delete this server group?';
$lang['Apnscp.manage.module_rows_no_results'] = 'There are no servers.';
$lang['Apnscp.manage.module_groups_no_results'] = 'There are no server groups.';

$lang['Apnscp.order_options.first'] = 'First Non-full Server';
$lang['Apnscp.order_options.roundrobin'] = 'Evenly Distribute Among Servers';

// Add row
$lang['Apnscp.add_row.box_title'] = 'Add ApisCP Server';
$lang['Apnscp.add_row.basic_title'] = 'Basic Settings';
$lang['Apnscp.add_row.name_servers_title'] = 'Name Servers';
$lang['Apnscp.add_row.notes_title'] = 'Notes';
$lang['Apnscp.add_row.name_server_btn'] = 'Add Additional Name Server';
$lang['Apnscp.add_row.name_server_col'] = 'Name Server';
$lang['Apnscp.add_row.name_server_host_col'] = 'Hostname';
$lang['Apnscp.add_row.name_server'] = 'Name server %1$s'; // %1$s is the name server number (e.g. 3)
$lang['Apnscp.add_row.remove_name_server'] = 'Remove';
$lang['Apnscp.add_row.add_btn'] = 'Add Server';

$lang['Apnscp.edit_row.box_title'] = 'Edit ApisCP Server';
$lang['Apnscp.edit_row.basic_title'] = 'Basic Settings';
$lang['Apnscp.edit_row.name_servers_title'] = 'Name Servers';
$lang['Apnscp.edit_row.notes_title'] = 'Notes';
$lang['Apnscp.edit_row.name_server_btn'] = 'Add Additional Name Server';
$lang['Apnscp.edit_row.name_server_col'] = 'Name Server';
$lang['Apnscp.edit_row.name_server_host_col'] = 'Hostname';
$lang['Apnscp.edit_row.name_server'] = 'Name server %1$s'; // %1$s is the name server number (e.g. 3)
$lang['Apnscp.edit_row.remove_name_server'] = 'Remove';
$lang['Apnscp.edit_row.add_btn'] = 'Edit Server';

$lang['Apnscp.row_meta.server_name'] = 'Server Label';
$lang['Apnscp.row_meta.host_name'] = 'Hostname';
$lang['Apnscp.row_meta.port'] = 'Port';
$lang['Apnscp.row_meta.default_port'] = '2083';
$lang['Apnscp.row_meta.api_key'] = 'API Key';
$lang['Apnscp.row_meta.use_ssl'] = 'Use SSL when connecting to the API (recommended)';
$lang['Apnscp.row_meta.account_limit'] = 'Account Limit';

// Package fields
$lang['Apnscp.package_fields.package'] = 'ApisCP Package';

// Service fields
$lang['Apnscp.service_field.domain'] = 'Domain';
$lang['Apnscp.service_field.username'] = 'Username';
$lang['Apnscp.service_field.password'] = 'Password';

// Service management
$lang['Apnscp.tab_stats.info_title'] = 'Information';
$lang['Apnscp.tab_stats.info_heading.field'] = 'Field';
$lang['Apnscp.tab_stats.info_heading.value'] = 'Value';
$lang['Apnscp.tab_stats.info.disk'] = 'Disk Quota';
$lang['Apnscp.tab_stats.info.bandwidth'] = 'Bandwidth Quota';

// Client actions
$lang['Apnscp.tab_client_actions.change_password'] = 'Change Password';
$lang['Apnscp.tab_client_actions.field_apnscp_password'] = 'Password';
$lang['Apnscp.tab_client_actions.field_password_submit'] = 'Update Password';

// Client Service management
$lang['Apnscp.tab_client_stats.info_title'] = 'Information';
$lang['Apnscp.tab_client_stats.bandwidth_title'] = 'Bandwidth Usage (Month to Date)';
$lang['Apnscp.tab_client_stats.disk_title'] = 'Disk Usage';
$lang['Apnscp.tab_client_stats.usage'] = '(%1$s MB/%2$s MB)'; // %1$s is the amount of resource usage, %2$s is the resource usage limit
$lang['Apnscp.tab_client_stats.usage_unlimited'] = '(%1$s MB/∞)'; // %1$s is the amount of resource usage

// Service info
$lang['Apnscp.service_info.username'] = 'Username';
$lang['Apnscp.service_info.password'] = 'Password';
$lang['Apnscp.service_info.server'] = 'Server';
$lang['Apnscp.service_info.options'] = 'Options';
$lang['Apnscp.service_info.option_login'] = 'Log in';

// Tooltips
$lang['Apnscp.service_field.tooltip.username'] = 'You may leave the username blank to automatically generate one.';
$lang['Apnscp.service_field.tooltip.password'] = 'You may leave the password blank to automatically generate one.';

// Errors
$lang['Apnscp.!error.server_name_valid'] = 'You must enter a Server Label.';
$lang['Apnscp.!error.host_name_valid'] = 'The Hostname appears to be invalid.';
$lang['Apnscp.!error.port_format'] = 'The port must be a number.';
$lang['Apnscp.!error.api_key_valid'] = 'The API Key appears to be invalid.';
$lang['Apnscp.!error.api_key_valid_connection'] = 'A connection to the server could not be established. Please check to ensure that the Hostname, API Key, and Port are correct.';
$lang['Apnscp.!error.account_limit_valid'] = 'Account Limit must be left blank (for unlimited accounts) or set to some integer value.';
$lang['Apnscp.!error.name_servers_valid'] = 'One or more of the name servers entered are invalid.';
$lang['Apnscp.!error.name_servers_count'] = 'You must define at least 2 name servers.';
$lang['Apnscp.!error.meta[package].empty'] = 'An ApisCP Package is required.';
$lang['Apnscp.!error.api.internal'] = 'An internal error occurred, or the server did not respond to the request.';
$lang['Apnscp.!error.module_row.missing'] = 'An internal error occurred. The module row is unavailable.';

$lang['Apnscp.!error.apnscp_domain.format'] = 'Please enter a valid domain name, e.g. domain.com.';
$lang['Apnscp.!error.apnscp_domain.test'] = "Domain name can not start with 'test'.";
$lang['Apnscp.!error.apnscp_username.format'] = 'The username may contain only letters and numbers and may not start with a number.';
$lang['Apnscp.!error.apnscp_username.test'] = "The username may not begin with 'test'.";
$lang['Apnscp.!error.apnscp_username.length'] = 'The username must be between 1 and 16 characters in length.';
$lang['Apnscp.!error.apnscp_password.valid'] = 'Password must be at least 8 characters in length.';
$lang['Apnscp.!error.apnscp_password.matches'] = 'Password and Confirm Password do not match.';
