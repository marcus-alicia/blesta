<?php
// Errors
$lang['Plesk.!error.simplexml_required'] = 'The simplexml extension is required for this module.';

$lang['Plesk.!error.api.internal'] = 'An internal error occurred, or the server did not respond to the request.';

$lang['Plesk.!error.server_name.empty'] = 'You must enter a Server Label.';
$lang['Plesk.!error.host_name.valid'] = 'The hostname appears to be invalid.';
$lang['Plesk.!error.ip_address.valid'] = 'The IP address appears to be invalid.';
$lang['Plesk.!error.port.format'] = 'The port number must be a number.';
$lang['Plesk.!error.username.empty'] = 'Please enter a username.';
$lang['Plesk.!error.password.empty'] = 'Please enter a password.';
$lang['Plesk.!error.panel_version.valid'] = 'Please select your Plesk Panel version.';
$lang['Plesk.!error.reseller.valid'] = 'Whether this account is a reseller account must be set to true or false.';
$lang['Plesk.!error.account_limit_valid'] = 'Account Limit must be left blank (for unlimited accounts) or set to some integer value.';
$lang['Plesk.!error.name_servers.valid'] = 'One or more of the name servers entered are invalid.';
$lang['Plesk.!error.name_servers.count'] = 'You must define at least 2 name servers.';
$lang['Plesk.!error.module_row.missing'] = 'An internal error occurred. The module row is unavailable.';

$lang['Plesk.!error.meta[plan].empty'] = 'Please select a service plan.';
$lang['Plesk.!error.meta[reseller_plan].empty'] = 'Please select a reseller account plan.';
$lang['Plesk.!error.meta[type].valid'] = 'Account type must be either standard or reseller.';

$lang['Plesk.!error.plesk_domain.format'] = 'Please enter a valid domain name, e.g. domain.com';
$lang['Plesk.!error.plesk_username.length'] = 'The username must be between 1 and 32 characters in length.';
$lang['Plesk.!error.plesk_password.length'] = 'The password must be between 16 and 20 characters in length.';
$lang['Plesk.!error.plesk_confirm_password.matches'] = 'The passwords do not match.';
$lang['Plesk.!error.plesk_webspace_id.exists'] = 'The Subscription ID given does not exist in Plesk.';

$lang['Plesk.!error.downgrade.unsupported'] = 'Downgrading from a reseller account to a non-reseller account is not supported.';

$lang['Plesk.!error.api.webspace_delete_filter_missing'] = 'Missing filter for deleting a specific subscription.';
$lang['Plesk.!error.api.customer_delete_filter_missing'] = 'Missing filter for deleting a specific customer.';
$lang['Plesk.!error.api.reseller_delete_filter_missing'] = 'Missing filter for deleting a specific reseller.';


// Common
$lang['Plesk.please_select'] = '-- Please Select --';


// Tabs
$lang['Plesk.tab_stats'] = 'Statistics';
$lang['Plesk.tab_client_stats'] = 'Statistics';


// Statistics
$lang['Plesk.stats.unlimited'] = 'Unlimited';
$lang['Plesk.!bytes.value'] = '%1$s %2$s'; // %1$s is a number value, %2$s is the unit of that value (i.e., one of B, KB, MB, GB)


// Tab Stats
$lang['Plesk.tab_stats.info_title'] = 'Information';
$lang['Plesk.tab_stats.bandwidth_title'] = 'Bandwidth';
$lang['Plesk.tab_stats.disk_title'] = 'Disk';

$lang['Plesk.tab_stats.info_heading.field'] = 'Field';
$lang['Plesk.tab_stats.info_heading.value'] = 'Value';
$lang['Plesk.tab_stats.bandwidth_heading.used'] = 'Used';
$lang['Plesk.tab_stats.bandwidth_heading.limit'] = 'Limit';
$lang['Plesk.tab_stats.disk_heading.used'] = 'Used';
$lang['Plesk.tab_stats.disk_heading.limit'] = 'Limit';

$lang['Plesk.tab_stats.info.domain'] = 'Domain';
$lang['Plesk.tab_stats.info.ip_address'] = 'IP Address';


// Tab Client Stats
$lang['Plesk.tab_client_stats.info_title'] = 'Information';

$lang['Plesk.tab_client_stats.info_heading.field'] = 'Field';
$lang['Plesk.tab_client_stats.info_heading.value'] = 'Value';

$lang['Plesk.tab_client_stats.info.domain'] = 'Domain';
$lang['Plesk.tab_client_stats.info.ip_address'] = 'IP Address';

$lang['Plesk.tab_client_stats.disk_title'] = 'Disk Usage';
$lang['Plesk.tab_client_stats.bandwidth_title'] = 'Bandwidth Usage';
$lang['Plesk.tab_client_stats.usage'] = '(%1$s/%2$s)'; // %1$s is the amount of resource usage, %2$s is the resource usage limit
$lang['Plesk.tab_client_stats.not_available'] = 'NA';


// Basics
$lang['Plesk.name'] = 'Plesk';
$lang['Plesk.description'] = 'Plesk is a commercial web hosting platform with a control panel that allows a server administrator to set up new websites, reseller accounts, e-mail accounts and DNS entries through a web-based interface.';
$lang['Plesk.module_row'] = 'Server';
$lang['Plesk.module_row_plural'] = 'Servers';
$lang['Plesk.module_group'] = 'Server Group';


// Service fields
$lang['Plesk.service_field.domain'] = 'Domain';
$lang['Plesk.service_field.username'] = 'Username';
$lang['Plesk.service_field.password'] = 'Password';
$lang['Plesk.service_field.confirm_password'] = 'Confirm Password';
$lang['Plesk.service_field.text_generate_password'] = 'Generate Password';
$lang['Plesk.service_field.webspace_id'] = 'Subscription (Webspace) ID';

$lang['Plesk.service_field.tooltip.username'] = 'You may leave the username blank to automatically generate one.';
$lang['Plesk.service_field.tooltip.webspace_id'] = 'Only set a Subscription ID if you are not provisioning this service with the module. It may be used for certain API requests.';
$lang['Plesk.service_field.tooltip.webspace_id_edit'] = 'The Subscription ID will only be changed locally. It will not be changed in Plesk.';


// Package fields
$lang['Plesk.package_fields.plan'] = 'Plesk Service Plan';
$lang['Plesk.package_fields.type'] = 'Account Type';
$lang['Plesk.package_fields.type_standard'] = 'Standard';
$lang['Plesk.package_fields.type_reseller'] = 'Reseller';
$lang['Plesk.package_fields.reseller_plan'] = 'Reseller Account Plan';


// Service info
$lang['Plesk.service_info.username'] = 'Username';
$lang['Plesk.service_info.password'] = 'Password';
$lang['Plesk.service_info.server'] = 'Server';
$lang['Plesk.service_info.options'] = 'Options';
$lang['Plesk.service_info.option_login'] = 'Log in';


// Add module row
$lang['Plesk.add_row.box_title'] = 'Add Plesk Server';
$lang['Plesk.add_row.basic_title'] = 'Basic Settings';
$lang['Plesk.add_row.name_servers_title'] = 'Name Servers';
$lang['Plesk.add_row.name_server_btn'] = 'Add Additional Name Server';
$lang['Plesk.add_row.name_server_col'] = 'Name Server';
$lang['Plesk.add_row.name_server_host_col'] = 'Hostname';
$lang['Plesk.add_row.name_server'] = 'Name server %1$s'; // %1$s is the name server number (e.g. 3)
$lang['Plesk.add_row.remove_name_server'] = 'Remove';
$lang['Plesk.add_row.add_btn'] = 'Add Server';


// Edit module row
$lang['Plesk.edit_row.box_title'] = 'Edit Plesk Server';
$lang['Plesk.edit_row.basic_title'] = 'Basic Settings';
$lang['Plesk.edit_row.name_servers_title'] = 'Name Servers';
$lang['Plesk.edit_row.name_server_btn'] = 'Add Additional Name Server';
$lang['Plesk.edit_row.name_server_col'] = 'Name Server';
$lang['Plesk.edit_row.name_server_host_col'] = 'Hostname';
$lang['Plesk.edit_row.name_server'] = 'Name server %1$s'; // %1$s is the name server number (e.g. 3)
$lang['Plesk.edit_row.remove_name_server'] = 'Remove';
$lang['Plesk.edit_row.add_btn'] = 'Edit Server';


// Module row meta data
$lang['Plesk.row_meta.server_name'] = 'Server Label';
$lang['Plesk.row_meta.host_name'] = 'Hostname';
$lang['Plesk.row_meta.ip_address'] = 'IP Address';
$lang['Plesk.row_meta.port'] = 'Port';
$lang['Plesk.row_meta.username'] = 'Username';
$lang['Plesk.row_meta.password'] = 'Password';
$lang['Plesk.row_meta.reseller'] = 'Reseller Account';
$lang['Plesk.row_meta.panel_version'] = 'Plesk Panel Version';
$lang['Plesk.row_meta.account_limit'] = 'Account Limit';
$lang['Plesk.row_meta.tooltip.reseller'] = 'Check this box if this is a Plesk Reseller account. Plesk Administrator accounts may leave this box unchecked.';
$lang['Plesk.row_meta.tooltip.version'] = 'Every version of Plesk supports several versions of the XML API that were released with and before this version of Plesk, so newer versions are backward-compatible with older versions.';


// Module management
$lang['Plesk.order_options.first'] = 'First non-full server';
$lang['Plesk.order_options.roundrobin'] = 'Evenly Distribute Among Servers';

$lang['Plesk.add_module_row'] = 'Add Server';
$lang['Plesk.add_module_group'] = 'Add Server Group';
$lang['Plesk.manage.module_rows_title'] = 'Servers';
$lang['Plesk.manage.module_groups_title'] = 'Server Groups';
$lang['Plesk.manage.module_rows_heading.name'] = 'Server Label';
$lang['Plesk.manage.module_rows_heading.ip_address'] = 'IP Address';
$lang['Plesk.manage.module_rows_heading.accounts'] = 'Accounts';
$lang['Plesk.manage.module_rows_heading.options'] = 'Options';
$lang['Plesk.manage.module_rows.count'] = '%1$s / %2$s'; // %1$s is the current number of accounts, %2$s is the total number of accounts available
$lang['Plesk.manage.module_rows.count_server_group'] = '%1$s / %2$s (%3$s Available)'; // %1$s is the current number of accounts, %2$s is the total number of accounts available, %3$s is the total number of accounts available without over-subscription
$lang['Plesk.manage.module_groups_heading.name'] = 'Group Name';
$lang['Plesk.manage.module_groups_heading.servers'] = 'Server Count';
$lang['Plesk.manage.module_groups_heading.options'] = 'Options';
$lang['Plesk.manage.module_rows.edit'] = 'Edit';
$lang['Plesk.manage.module_groups.edit'] = 'Edit';
$lang['Plesk.manage.module_rows.delete'] = 'Delete';
$lang['Plesk.manage.module_groups.delete'] = 'Delete';
$lang['Plesk.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this server?';
$lang['Plesk.manage.module_groups.confirm_delete'] = 'Are you sure you want to delete this server group?';
$lang['Plesk.manage.module_rows_no_results'] = 'There are no servers.';
$lang['Plesk.manage.module_groups_no_results'] = 'There are no server groups.';


// Panel versions
$lang['Plesk.panel_version.windows'] = 'Windows';
$lang['Plesk.panel_version.linux'] = 'Linux/Unix';
$lang['Plesk.panel_version.plesk_type'] = 'Plesk %1$s for %2$s'; // %1$s is the Plesk panel version number, %2$s is the OS type (i.e. Windows or Linux/Unix)
$lang['Plesk.panel_version.plesk'] = 'Plesk %1$s'; // %1$s is the Plesk panel version number
$lang['Plesk.panel_version.parallels'] = 'Parallels Plesk Panel %1$s'; // %1$s is the Plesk panel version number
$lang['Plesk.panel_version.latest'] = 'Use Latest Version (Recommended)';
