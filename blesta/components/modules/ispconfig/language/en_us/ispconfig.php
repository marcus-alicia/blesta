<?php
/**
 * en_us language for the ispconfig module.
 */
// Basics
$lang['Ispconfig.name'] = 'ISPConfig';
$lang['Ispconfig.description'] = 'ISPConfig 3 is an open source panel for Linux which is capable of managing multiple servers from one control panel.';
$lang['Ispconfig.module_row'] = 'Server';
$lang['Ispconfig.module_row_plural'] = 'Servers';
$lang['Ispconfig.module_group'] = 'Server Group';
$lang['Ispconfig.tab_stats'] = 'Statistics';
$lang['Ispconfig.tab_client_stats'] = 'Statistics';
$lang['Ispconfig.tab_client_actions'] = 'Actions';

// Module management
$lang['Ispconfig.add_module_row'] = 'Add Server';
$lang['Ispconfig.add_module_group'] = 'Add Server Group';
$lang['Ispconfig.manage.module_rows_title'] = 'Servers';
$lang['Ispconfig.manage.module_groups_title'] = 'Server Groups';
$lang['Ispconfig.manage.module_rows_heading.name'] = 'Server Label';
$lang['Ispconfig.manage.module_rows_heading.hostname'] = 'Hostname';
$lang['Ispconfig.manage.module_rows_heading.accounts'] = 'Accounts';
$lang['Ispconfig.manage.module_rows_heading.options'] = 'Options';
$lang['Ispconfig.manage.module_groups_heading.name'] = 'Group Name';
$lang['Ispconfig.manage.module_groups_heading.servers'] = 'Server Count';
$lang['Ispconfig.manage.module_groups_heading.options'] = 'Options';
$lang['Ispconfig.manage.module_rows.count'] = '%1$s / %2$s'; // %1$s is the current number of accounts, %2$s is the total number of accounts available
$lang['Ispconfig.manage.module_rows.edit'] = 'Edit';
$lang['Ispconfig.manage.module_groups.edit'] = 'Edit';
$lang['Ispconfig.manage.module_rows.delete'] = 'Delete';
$lang['Ispconfig.manage.module_groups.delete'] = 'Delete';
$lang['Ispconfig.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this server?';
$lang['Ispconfig.manage.module_groups.confirm_delete'] = 'Are you sure you want to delete this server group?';
$lang['Ispconfig.manage.module_rows_no_results'] = 'There are no servers.';
$lang['Ispconfig.manage.module_groups_no_results'] = 'There are no server groups.';

$lang['Ispconfig.order_options.first'] = 'First Non-full Server';
$lang['Ispconfig.order_options.roundrobin'] = 'Evenly Distribute Among Servers';

// Add row
$lang['Ispconfig.add_row.box_title'] = 'Add ISPConfig Server';
$lang['Ispconfig.add_row.basic_title'] = 'Basic Settings';
$lang['Ispconfig.add_row.name_servers_title'] = 'Name Servers';
$lang['Ispconfig.add_row.notes_title'] = 'Notes';
$lang['Ispconfig.add_row.name_server_btn'] = 'Add Additional Name Server';
$lang['Ispconfig.add_row.name_server_col'] = 'Name Server';
$lang['Ispconfig.add_row.name_server_host_col'] = 'Hostname';
$lang['Ispconfig.add_row.name_server'] = 'Name server %1$s'; // %1$s is the name server number (e.g. 3)
$lang['Ispconfig.add_row.remove_name_server'] = 'Remove';
$lang['Ispconfig.add_row.add_btn'] = 'Add Server';

$lang['Ispconfig.edit_row.box_title'] = 'Edit ISPConfig Server';
$lang['Ispconfig.edit_row.basic_title'] = 'Basic Settings';
$lang['Ispconfig.edit_row.name_servers_title'] = 'Name Servers';
$lang['Ispconfig.edit_row.notes_title'] = 'Notes';
$lang['Ispconfig.edit_row.name_server_btn'] = 'Add Additional Name Server';
$lang['Ispconfig.edit_row.name_server_col'] = 'Name Server';
$lang['Ispconfig.edit_row.name_server_host_col'] = 'Hostname';
$lang['Ispconfig.edit_row.name_server'] = 'Name server %1$s'; // %1$s is the name server number (e.g. 3)
$lang['Ispconfig.edit_row.remove_name_server'] = 'Remove';
$lang['Ispconfig.edit_row.add_btn'] = 'Edit Server';

$lang['Ispconfig.row_meta.server_name'] = 'Server Label';
$lang['Ispconfig.row_meta.host_name'] = 'Hostname';
$lang['Ispconfig.row_meta.port'] = 'Port';
$lang['Ispconfig.row_meta.default_port'] = '8080';
$lang['Ispconfig.row_meta.user_name'] = 'User Name';
$lang['Ispconfig.row_meta.password'] = 'Password';
$lang['Ispconfig.row_meta.use_ssl'] = 'Use SSL when connecting to the API (recommended)';
$lang['Ispconfig.row_meta.account_limit'] = 'Account Limit';

// Package fields
$lang['Ispconfig.package_fields.package'] = 'ISPConfig Package';
$lang['Ispconfig.package_fields.php_options'] = 'PHP Options';
$lang['Ispconfig.package_fields.ssh_options'] = 'SSH Options';

// Service fields
$lang['Ispconfig.service_field.domain'] = 'Domain';
$lang['Ispconfig.service_field.username'] = 'Username';
$lang['Ispconfig.service_field.password'] = 'Password';

// Service management
$lang['Ispconfig.tab_stats.info_title'] = 'Information';
$lang['Ispconfig.tab_stats.info_heading.field'] = 'Field';
$lang['Ispconfig.tab_stats.info_heading.value'] = 'Value';
$lang['Ispconfig.tab_stats.info.limit_web_quota'] = 'Web Quota';
$lang['Ispconfig.tab_stats.info.limit_traffic_quota'] = 'Bandwidth Quota';
$lang['Ispconfig.tab_stats.info.limit_ftp_user'] = 'FTP Users Limit';
$lang['Ispconfig.tab_stats.info.limit_web_domain'] = 'Web Domains Limit';
$lang['Ispconfig.tab_stats.info.limit_web_subdomain'] = 'Subdomains Limit';
$lang['Ispconfig.tab_stats.info.limit_web_aliasdomain'] = 'Alias Domain Limit';
$lang['Ispconfig.tab_stats.info.limit_webdav_user'] = 'WebDAV Users Limit';
$lang['Ispconfig.tab_stats.info.limit_database'] = 'Databases Limit';

// Client actions
$lang['Ispconfig.tab_client_actions.change_password'] = 'Change Password';
$lang['Ispconfig.tab_client_actions.field_ispconfig_password'] = 'Password';
$lang['Ispconfig.tab_client_actions.field_password_submit'] = 'Update Password';

// Client Service management
$lang['Ispconfig.tab_client_stats.info_title'] = 'Information';
$lang['Ispconfig.tab_client_stats.info_heading.field'] = 'Field';
$lang['Ispconfig.tab_client_stats.info_heading.value'] = 'Value';
$lang['Ispconfig.tab_client_stats.info.limit_web_quota'] = 'Web Quota';
$lang['Ispconfig.tab_client_stats.info.limit_traffic_quota'] = 'Bandwidth Quota';
$lang['Ispconfig.tab_client_stats.info.limit_ftp_user'] = 'FTP Users Limit';
$lang['Ispconfig.tab_client_stats.info.limit_web_domain'] = 'Web Domains Limit';
$lang['Ispconfig.tab_client_stats.info.limit_web_subdomain'] = 'Subdomains Limit';
$lang['Ispconfig.tab_client_stats.info.limit_web_aliasdomain'] = 'Alias Domain Limit';
$lang['Ispconfig.tab_client_stats.info.limit_webdav_user'] = 'WebDAV Users Limit';
$lang['Ispconfig.tab_client_stats.info.limit_database'] = 'Databases Limit';
$lang['Ispconfig.tab_client_stats.bandwidth_title'] = 'Bandwidth Usage (Month to Date)';
$lang['Ispconfig.tab_client_stats.disk_title'] = 'Disk Usage';
$lang['Ispconfig.tab_client_stats.usage'] = '(%1$s MB/%2$s MB)'; // %1$s is the amount of resource usage, %2$s is the resource usage limit
$lang['Ispconfig.tab_client_stats.usage_unlimited'] = '(%1$s MB/∞)'; // %1$s is the amount of resource usage

// Service info
$lang['Ispconfig.service_info.username'] = 'Username';
$lang['Ispconfig.service_info.password'] = 'Password';
$lang['Ispconfig.service_info.server'] = 'Server';
$lang['Ispconfig.service_info.options'] = 'Options';
$lang['Ispconfig.service_info.option_login'] = 'Log in';

// Tooltips
$lang['Ispconfig.service_field.tooltip.username'] = 'You may leave the username blank to automatically generate one.';
$lang['Ispconfig.service_field.tooltip.password'] = 'You may leave the password blank to automatically generate one.';

// Errors
$lang['Ispconfig.!error.server_name_valid'] = 'You must enter a Server Label.';
$lang['Ispconfig.!error.host_name_valid'] = 'The Hostname appears to be invalid.';
$lang['Ispconfig.!error.port_format'] = 'The port must be a number.';
$lang['Ispconfig.!error.user_name_valid'] = 'The User Name appears to be invalid.';
$lang['Ispconfig.!error.remote_password_valid'] = 'The Password appears to be invalid.';
$lang['Ispconfig.!error.remote_password_valid_connection'] = 'A connection to the server could not be established. Please check to ensure that the Hostname, User Name, and Password are correct.';
$lang['Ispconfig.!error.account_limit_valid'] = 'Account Limit must be left blank (for unlimited accounts) or set to some integer value.';
$lang['Ispconfig.!error.name_servers_valid'] = 'One or more of the name servers entered are invalid.';
$lang['Ispconfig.!error.name_servers_count'] = 'You must define at least 2 name servers.';
$lang['Ispconfig.!error.meta[package].empty'] = 'A ISPConfig Package is required.';
$lang['Ispconfig.!error.api.internal'] = 'An internal error occurred, or the server did not respond to the request.';
$lang['Ispconfig.!error.module_row.missing'] = 'An internal error occurred. The module row is unavailable.';

$lang['Ispconfig.!error.ispconfig_domain.format'] = 'Please enter a valid domain name, e.g. domain.com.';
$lang['Ispconfig.!error.ispconfig_domain.test'] = "Domain name can not start with 'test'.";
$lang['Ispconfig.!error.ispconfig_username.format'] = 'The username may contain only letters and numbers and may not start with a number.';
$lang['Ispconfig.!error.ispconfig_username.test'] = "The username may not begin with 'test'.";
$lang['Ispconfig.!error.ispconfig_username.length'] = 'The username must be between 1 and 16 characters in length.';
$lang['Ispconfig.!error.ispconfig_password.valid'] = 'Password must be at least 8 characters in length.';
$lang['Ispconfig.!error.ispconfig_password.matches'] = 'Password and Confirm Password do not match.';
