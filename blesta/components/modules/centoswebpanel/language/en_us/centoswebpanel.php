<?php
/**
 * en_us language for the centoswebpanel module.
 */
// Basics
$lang['Centoswebpanel.name'] = 'CentOS WebPanel';
$lang['Centoswebpanel.description'] = 'Free Web Hosting control panel designed for quick and easy management of (Dedicated & VPS) servers';
$lang['Centoswebpanel.module_row'] = 'Server';
$lang['Centoswebpanel.module_row_plural'] = 'Servers';
$lang['Centoswebpanel.module_group'] = 'Server Group';

// Module management
$lang['Centoswebpanel.add_module_row'] = 'Add Server';
$lang['Centoswebpanel.add_module_group'] = 'Add Server Group';
$lang['Centoswebpanel.manage.module_rows_title'] = 'Servers';
$lang['Centoswebpanel.manage.module_groups_title'] = 'Server Groups';
$lang['Centoswebpanel.manage.module_rows_heading.name'] = 'Server Label';
$lang['Centoswebpanel.manage.module_rows_heading.hostname'] = 'Hostname';
$lang['Centoswebpanel.manage.module_rows_heading.accounts'] = 'Accounts';
$lang['Centoswebpanel.manage.module_rows_heading.options'] = 'Options';
$lang['Centoswebpanel.manage.module_groups_heading.name'] = 'Group Name';
$lang['Centoswebpanel.manage.module_groups_heading.servers'] = 'Server Count';
$lang['Centoswebpanel.manage.module_groups_heading.options'] = 'Options';
$lang['Centoswebpanel.manage.module_rows.count'] = '%1$s / %2$s'; // %1$s is the current number of accounts, %2$s is the total number of accounts available
$lang['Centoswebpanel.manage.module_rows.edit'] = 'Edit';
$lang['Centoswebpanel.manage.module_groups.edit'] = 'Edit';
$lang['Centoswebpanel.manage.module_rows.delete'] = 'Delete';
$lang['Centoswebpanel.manage.module_groups.delete'] = 'Delete';
$lang['Centoswebpanel.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this server?';
$lang['Centoswebpanel.manage.module_groups.confirm_delete'] = 'Are you sure you want to delete this server group?';
$lang['Centoswebpanel.manage.module_rows_no_results'] = 'There are no servers.';
$lang['Centoswebpanel.manage.module_groups_no_results'] = 'There are no server groups.';

$lang['Centoswebpanel.order_options.first'] = 'First Non-full Server';
$lang['Centoswebpanel.order_options.roundrobin'] = 'Evenly Distribute Among Servers';

// Add row
$lang['Centoswebpanel.add_row.box_title'] = 'Add CentOS WebPanel Server';
$lang['Centoswebpanel.add_row.basic_title'] = 'Basic Settings';
$lang['Centoswebpanel.add_row.name_servers_title'] = 'Name Servers';
$lang['Centoswebpanel.add_row.notes_title'] = 'Notes';
$lang['Centoswebpanel.add_row.name_server_btn'] = 'Add Additional Name Server';
$lang['Centoswebpanel.add_row.name_server_col'] = 'Name Server';
$lang['Centoswebpanel.add_row.name_server_host_col'] = 'Hostname';
$lang['Centoswebpanel.add_row.name_server'] = 'Name server %1$s'; // %1$s is the name server number (e.g. 3)
$lang['Centoswebpanel.add_row.remove_name_server'] = 'Remove';
$lang['Centoswebpanel.add_row.add_btn'] = 'Add Server';

$lang['Centoswebpanel.edit_row.box_title'] = 'Edit CentOS WebPanel Server';
$lang['Centoswebpanel.edit_row.basic_title'] = 'Basic Settings';
$lang['Centoswebpanel.edit_row.name_servers_title'] = 'Name Servers';
$lang['Centoswebpanel.edit_row.notes_title'] = 'Notes';
$lang['Centoswebpanel.edit_row.name_server_btn'] = 'Add Additional Name Server';
$lang['Centoswebpanel.edit_row.name_server_col'] = 'Name Server';
$lang['Centoswebpanel.edit_row.name_server_host_col'] = 'Hostname';
$lang['Centoswebpanel.edit_row.name_server'] = 'Name server %1$s'; // %1$s is the name server number (e.g. 3)
$lang['Centoswebpanel.edit_row.remove_name_server'] = 'Remove';
$lang['Centoswebpanel.edit_row.add_btn'] = 'Edit Server';

$lang['Centoswebpanel.row_meta.server_name'] = 'Server Label';
$lang['Centoswebpanel.row_meta.host_name'] = 'Hostname';
$lang['Centoswebpanel.row_meta.login_port'] = 'Login Port';
$lang['Centoswebpanel.row_meta.default_login_port'] = '2031';
$lang['Centoswebpanel.row_meta.port'] = 'API Port';
$lang['Centoswebpanel.row_meta.default_port'] = '2304';
$lang['Centoswebpanel.row_meta.api_key'] = 'API Key';
$lang['Centoswebpanel.row_meta.use_ssl'] = 'Use SSL when connecting to the API (recommended)';
$lang['Centoswebpanel.row_meta.account_limit'] = 'Account Limit';

// Package fields
$lang['Centoswebpanel.package_fields.package'] = 'Package ID';
$lang['Centoswebpanel.package_fields.inode'] = 'Maximum Inodes';
$lang['Centoswebpanel.package_fields.nofile'] = 'Maximum Files';
$lang['Centoswebpanel.package_fields.nproc'] = 'Maximum Process';

// Service fields
$lang['Centoswebpanel.service_field.domain'] = 'Domain';
$lang['Centoswebpanel.service_field.username'] = 'Username';
$lang['Centoswebpanel.service_field.password'] = 'Password';

// Service info
$lang['Centoswebpanel.service_info.username'] = 'Username';
$lang['Centoswebpanel.service_info.password'] = 'Password';
$lang['Centoswebpanel.service_info.server'] = 'Server';
$lang['Centoswebpanel.service_info.options'] = 'Options';
$lang['Centoswebpanel.service_info.option_login'] = 'Log in';

// Tooltips
$lang['Centoswebpanel.service_field.tooltip.domain_edit'] = 'This change will not affect CentOS Web Panel, but only change the local record in Blesta.';
$lang['Centoswebpanel.service_field.tooltip.username_edit'] = 'This change will not affect CentOS Web Panel, but only change the local record in Blesta.';
$lang['Centoswebpanel.service_field.tooltip.username'] = 'You may leave the username blank to automatically generate one.';
$lang['Centoswebpanel.service_field.tooltip.password'] = 'You may leave the password blank to automatically generate one.';

// Errors
$lang['Centoswebpanel.!error.server_name_valid'] = 'You must enter a Server Label.';
$lang['Centoswebpanel.!error.host_name_valid'] = 'The Hostname appears to be invalid.';
$lang['Centoswebpanel.!error.user_name_valid'] = 'The User Name appears to be invalid.';
$lang['Centoswebpanel.!error.login_port_valid'] = 'You must enter a login port.';
$lang['Centoswebpanel.!error.port_valid'] = 'You must enter an API port.';
$lang['Centoswebpanel.!error.remote_api_key_valid'] = 'The API Key appears to be invalid.';
$lang['Centoswebpanel.!error.remote_api_key_valid_connection'] = 'A connection to the server could not be established. Please check to ensure that the Hostname and API Key are correct.';
$lang['Centoswebpanel.!error.account_limit_valid'] = 'Account Limit must be left blank (for unlimited accounts) or set to some integer value.';
$lang['Centoswebpanel.!error.name_servers_valid'] = 'One or more of the name servers entered are invalid.';
$lang['Centoswebpanel.!error.name_servers_count'] = 'You must define at least 2 name servers.';
$lang['Centoswebpanel.!error.meta[package].empty'] = 'A CentOS WebPanel Package is required.';
$lang['Centoswebpanel.!error.meta[inode].valid'] = 'The Maximum Inodes must be a numeric value.';
$lang['Centoswebpanel.!error.meta[nofile].valid'] = 'The Maximum Files must be a numeric value.';
$lang['Centoswebpanel.!error.meta[nproc].valid'] = 'The Maximum Process must be a numeric value.';
$lang['Centoswebpanel.!error.api.internal'] = 'An internal error occurred, or the server did not respond to the request.';
$lang['Centoswebpanel.!error.module_row.missing'] = 'An internal error occurred. The module row is unavailable.';

$lang['Centoswebpanel.!error.centoswebpanel_domain.format'] = 'Please enter a valid domain name, e.g. domain.com.';
$lang['Centoswebpanel.!error.centoswebpanel_domain.test'] = "Domain name can not start with 'test'.";
$lang['Centoswebpanel.!error.centoswebpanel_username.format'] = 'The username may contain only letters and numbers and may not start with a number.';
$lang['Centoswebpanel.!error.centoswebpanel_username.test'] = "The username may not begin with 'test'.";
$lang['Centoswebpanel.!error.centoswebpanel_username.length'] = 'The username must be between 1 and 16 characters in length.';
$lang['Centoswebpanel.!error.centoswebpanel_password.valid'] = 'Password must be at least 8 characters in length.';
$lang['Centoswebpanel.!error.centoswebpanel_password.matches'] = 'Password and Confirm Password do not match.';

$lang['Centoswebpanel.!error.api'] = 'An internal error occurred, or the server did not respond to the request.';
