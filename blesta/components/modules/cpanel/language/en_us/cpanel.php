<?php
/**
 * en_us language for the cpanel module
 */
// Basics
$lang['Cpanel.name'] = 'cPanel';
$lang['Cpanel.description'] = 'cPanel & WHM have been the industry leading web hosting platform for over 20 years. Trusted world-wide by technology partners Wordpress, CloudLinux, Lighstpeed, and more.';
$lang['Cpanel.module_row'] = 'Server';
$lang['Cpanel.module_row_plural'] = 'Servers';
$lang['Cpanel.module_group'] = 'Server Group';
$lang['Cpanel.tab_stats'] = 'Statistics';
$lang['Cpanel.tab_client_stats'] = 'Statistics';
$lang['Cpanel.tab_client_actions'] = 'Actions';

// Module management
$lang['Cpanel.add_module_row'] = 'Add Server';
$lang['Cpanel.add_module_group'] = 'Add Server Group';
$lang['Cpanel.manage.module_rows_title'] = 'Servers';
$lang['Cpanel.manage.module_groups_title'] = 'Server Groups';
$lang['Cpanel.manage.module_rows_heading.name'] = 'Server Label';
$lang['Cpanel.manage.module_rows_heading.hostname'] = 'Hostname';
$lang['Cpanel.manage.module_rows_heading.accounts'] = 'Accounts';
$lang['Cpanel.manage.module_rows_heading.options'] = 'Options';
$lang['Cpanel.manage.module_groups_heading.name'] = 'Group Name';
$lang['Cpanel.manage.module_groups_heading.servers'] = 'Server Count';
$lang['Cpanel.manage.module_groups_heading.options'] = 'Options';
$lang['Cpanel.manage.module_rows.count'] = '%1$s / %2$s'; // %1$s is the current number of accounts, %2$s is the total number of accounts available
$lang['Cpanel.manage.module_rows.count_server_group'] = '%1$s / %2$s (%3$s Available)'; // %1$s is the current number of accounts, %2$s is the total number of accounts available, %3$s is the total number of accounts available without over-subscription
$lang['Cpanel.manage.module_rows.edit'] = 'Edit';
$lang['Cpanel.manage.module_groups.edit'] = 'Edit';
$lang['Cpanel.manage.module_rows.delete'] = 'Delete';
$lang['Cpanel.manage.module_groups.delete'] = 'Delete';
$lang['Cpanel.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this server?';
$lang['Cpanel.manage.module_groups.confirm_delete'] = 'Are you sure you want to delete this server group?';
$lang['Cpanel.manage.module_rows_no_results'] = 'There are no servers.';
$lang['Cpanel.manage.module_groups_no_results'] = 'There are no server groups.';


$lang['Cpanel.order_options.first'] = 'First Non-full Server';
$lang['Cpanel.order_options.roundrobin'] = 'Evenly Distribute Among Servers';

// Add row
$lang['Cpanel.add_row.box_title'] = 'Add cPanel Server';
$lang['Cpanel.add_row.basic_title'] = 'Basic Settings';
$lang['Cpanel.add_row.name_servers_title'] = 'Name Servers';
$lang['Cpanel.add_row.notes_title'] = 'Notes';
$lang['Cpanel.add_row.name_server_btn'] = 'Add Additional Name Server';
$lang['Cpanel.add_row.name_server_col'] = 'Name Server';
$lang['Cpanel.add_row.name_server_host_col'] = 'Hostname';
$lang['Cpanel.add_row.name_server'] = 'Name server %1$s'; // %1$s is the name server number (e.g. 3)
$lang['Cpanel.add_row.remove_name_server'] = 'Remove';
$lang['Cpanel.add_row.add_btn'] = 'Add Server';

$lang['Cpanel.edit_row.box_title'] = 'Edit cPanel Server';
$lang['Cpanel.edit_row.basic_title'] = 'Basic Settings';
$lang['Cpanel.edit_row.name_servers_title'] = 'Name Servers';
$lang['Cpanel.edit_row.notes_title'] = 'Notes';
$lang['Cpanel.edit_row.name_server_btn'] = 'Add Additional Name Server';
$lang['Cpanel.edit_row.name_server_col'] = 'Name Server';
$lang['Cpanel.edit_row.name_server_host_col'] = 'Hostname';
$lang['Cpanel.edit_row.name_server'] = 'Name server %1$s'; // %1$s is the name server number (e.g. 3)
$lang['Cpanel.edit_row.remove_name_server'] = 'Remove';
$lang['Cpanel.edit_row.add_btn'] = 'Edit Server';

$lang['Cpanel.row_meta.server_name'] = 'Server Label';
$lang['Cpanel.row_meta.host_name'] = 'Hostname';
$lang['Cpanel.row_meta.user_name'] = 'User Name';
$lang['Cpanel.row_meta.key'] = 'Token (or Remote Key)';
$lang['Cpanel.row_meta.use_ssl'] = 'Use SSL when connecting to the API (recommended)';
$lang['Cpanel.row_meta.account_limit'] = 'Account Limit';

// Package fields
$lang['Cpanel.package_fields.type'] = 'Account Type';
$lang['Cpanel.package_fields.type_standard'] = 'Standard';
$lang['Cpanel.package_fields.type_reseller'] = 'Reseller';
$lang['Cpanel.package_fields.package'] = 'cPanel Package';
$lang['Cpanel.package_fields.acl'] = 'Access Control List';
$lang['Cpanel.package_fields.acl_default'] = 'Default';
$lang['Cpanel.package_fields.account_limit'] = 'Account Limit';
$lang['Cpanel.package_fields.dedicated_ip'] = 'Dedicated IP';
$lang['Cpanel.package_fields.sub_domains'] = 'Enable Selling Sub-Domains';
$lang['Cpanel.package_fields.sub_domains_enable'] = 'Enable';
$lang['Cpanel.package_fields.sub_domains_disable'] = 'Disable';
$lang['Cpanel.package_fields.domains_list'] = 'Available Domains List';
$lang['Cpanel.package_fields.dedicated_ip_no'] = 'No';
$lang['Cpanel.package_fields.dedicated_ip_yes'] = 'Yes';

// Service fields
$lang['Cpanel.service_field.domain'] = 'Domain';
$lang['Cpanel.service_field.sub_domain'] = 'Sub-Domain';
$lang['Cpanel.service_field.username'] = 'Username';
$lang['Cpanel.service_field.password'] = 'Password';
$lang['Cpanel.service_field.confirm_password'] = 'Confirm Password';
$lang['Cpanel.service_field.text_generate_password'] = 'Generate Password';

// Service management
$lang['Cpanel.tab_stats.info_title'] = 'Information';
$lang['Cpanel.tab_stats.info_heading.field'] = 'Field';
$lang['Cpanel.tab_stats.info_heading.value'] = 'Value';
$lang['Cpanel.tab_stats.info.domain'] = 'Domain';
$lang['Cpanel.tab_stats.info.ip'] = 'IP Address';
$lang['Cpanel.tab_stats.bandwidth_title'] = 'Bandwidth';
$lang['Cpanel.tab_stats.bandwidth_heading.used'] = 'Used';
$lang['Cpanel.tab_stats.bandwidth_heading.limit'] = 'Limit';
$lang['Cpanel.tab_stats.bandwidth_value'] = '%1$s MB'; // %1$s is the amount of bandwidth in MB
$lang['Cpanel.tab_stats.bandwidth_unlimited'] = 'unlimited';
$lang['Cpanel.tab_stats.disk_title'] = 'Disk';
$lang['Cpanel.tab_stats.disk_heading.used'] = 'Used';
$lang['Cpanel.tab_stats.disk_heading.limit'] = 'Limit';
$lang['Cpanel.tab_stats.disk_value'] = '%1$s MB'; // %1$s is the amount of disk in MB
$lang['Cpanel.tab_stats.disk_unlimited'] = 'unlimited';


// Client actions
$lang['Cpanel.tab_client_actions.change_password'] = 'Change Password';
$lang['Cpanel.tab_client_actions.field_cpanel_password'] = 'Password';
$lang['Cpanel.tab_client_actions.field_cpanel_confirm_password'] = 'Confirm Password';
$lang['Cpanel.tab_client_actions.field_password_submit'] = 'Update Password';


// Client Service management
$lang['Cpanel.tab_client_stats.info_title'] = 'Information';
$lang['Cpanel.tab_client_stats.info_heading.field'] = 'Field';
$lang['Cpanel.tab_client_stats.info_heading.value'] = 'Value';
$lang['Cpanel.tab_client_stats.info.domain'] = 'Domain';
$lang['Cpanel.tab_client_stats.info.ip'] = 'IP Address';
$lang['Cpanel.tab_client_stats.bandwidth_title'] = 'Bandwidth Usage (Month to Date)';
$lang['Cpanel.tab_client_stats.disk_title'] = 'Disk Usage';
$lang['Cpanel.tab_client_stats.usage'] = '(%1$s MB/%2$s MB)'; // %1$s is the amount of resource usage, %2$s is the resource usage limit
$lang['Cpanel.tab_client_stats.usage_unlimited'] = '(%1$s MB/∞)'; // %1$s is the amount of resource usage


// Service info
$lang['Cpanel.service_info.username'] = 'Username';
$lang['Cpanel.service_info.password'] = 'Password';
$lang['Cpanel.service_info.server'] = 'Server';
$lang['Cpanel.service_info.options'] = 'Options';
$lang['Cpanel.service_info.option_login'] = 'Log in';


// Tooltips
$lang['Cpanel.package_fields.tooltip.domains_list'] = 'Enter a CSV list of domains that will be available to provision sub-domains for, e.g. "domain1.com,domain2.com,domain3.com"';
$lang['Cpanel.service_field.tooltip.username'] = 'You may leave the username blank to automatically generate one.';
$lang['Cpanel.service_field.tooltip.password'] = 'You may leave the password blank to automatically generate one.';


// Errors
$lang['Cpanel.!error.server_name_valid'] = 'You must enter a Server Label.';
$lang['Cpanel.!error.host_name_valid'] = 'The Hostname appears to be invalid.';
$lang['Cpanel.!error.user_name_valid'] = 'The User Name appears to be invalid.';
$lang['Cpanel.!error.remote_key_valid'] = 'The Token (or Remote Key) appears to be invalid.';
$lang['Cpanel.!error.remote_key_valid_connection'] = 'A connection to the server could not be established. Please check to ensure that the Hostname, User Name, and Token (or Remote Key) are correct.';
$lang['Cpanel.!error.account_limit_valid'] = 'Account Limit must be left blank (for unlimited accounts) or set to some integer value.';
$lang['Cpanel.!error.name_servers_valid'] = 'One or more of the name servers entered are invalid.';
$lang['Cpanel.!error.name_servers_count'] = 'You must define at least 2 name servers.';
$lang['Cpanel.!error.meta[type].valid'] = 'Account type must be either standard or reseller.';
$lang['Cpanel.!error.meta[sub_domains].valid'] = 'Enable Sub-Domains must be set to either enable or disable.';
$lang['Cpanel.!error.meta[domains_list].valid'] = 'At least one available domain must be set and they must all represent a valid host name.';
$lang['Cpanel.!error.meta[account_limit].valid'] = 'Account limit must be a number.';
$lang['Cpanel.!error.meta[package].empty'] = 'A cPanel Package is required.';
$lang['Cpanel.!error.meta[dedicated_ip].format'] = 'The dedicated IP must be set to 0 or 1.';
$lang['Cpanel.!error.api.internal'] = 'An internal error occurred, or the server did not respond to the request.';
$lang['Cpanel.!error.module_row.missing'] = 'An internal error occurred. The module row is unavailable.';

$lang['Cpanel.!error.cpanel_domain.format'] = 'Please enter a valid domain name, e.g. domain.com.';
$lang['Cpanel.!error.cpanel_domain.valid'] = 'Invalid domain name.';
$lang['Cpanel.!error.cpanel_sub_domain.format'] = 'Please enter a valid sub-domain name, e.g. "site".';
$lang['Cpanel.!error.cpanel_sub_domain.availability'] = 'The sub-domain provided is not available.';
$lang['Cpanel.!error.cpanel_username.format'] = 'The username may contain only letters and numbers and may not start with a number.';
$lang['Cpanel.!error.cpanel_username.test'] = "The username may not begin with 'test'.";
$lang['Cpanel.!error.cpanel_username.length'] = 'The username must be between 1 and 16 characters in length.';
$lang['Cpanel.!error.cpanel_password.valid'] = 'Password must be at least 8 characters in length.';
$lang['Cpanel.!error.cpanel_password.matches'] = 'Password and Confirm Password do not match.';
$lang['Cpanel.!error.configoptions[dedicated_ip].format'] = 'The dedicated IP must be set to 0 or 1.';
