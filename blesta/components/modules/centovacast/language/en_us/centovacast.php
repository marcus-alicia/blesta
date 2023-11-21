<?php
/**
 * en_us language for the centovacast module
 */
// Basics
$lang['Centovacast.name'] = 'CentovaCast';
$lang['Centovacast.description'] = 'Manage a single station with ease, or automate a stream hosting business with thousands of clients. Centova Cast can handle virtually any stream hosting scenario!';
$lang['Centovacast.module_row'] = 'Server';
$lang['Centovacast.module_row_plural'] = 'Servers';
$lang['Centovacast.module_group'] = 'Server Group';
$lang['Centovacast.tab_stats'] = 'Statistics';
$lang['Centovacast.tab_actions'] = 'Actions';
$lang['Centovacast.tab_client_stats'] = 'Statistics';
$lang['Centovacast.tab_client_actions'] = 'Actions';


// Module management
$lang['Centovacast.add_module_row'] = 'Add Server';
$lang['Centovacast.add_module_group'] = 'Add Server Group';
$lang['Centovacast.manage.module_rows_title'] = 'Servers';
$lang['Centovacast.manage.module_groups_title'] = 'Server Groups';
$lang['Centovacast.manage.module_rows_heading.name'] = 'Server Label';
$lang['Centovacast.manage.module_rows_heading.hostname'] = 'Hostname';
$lang['Centovacast.manage.module_rows_heading.accounts'] = 'Accounts';
$lang['Centovacast.manage.module_rows_heading.options'] = 'Options';
$lang['Centovacast.manage.module_groups_heading.name'] = 'Group Name';
$lang['Centovacast.manage.module_groups_heading.servers'] = 'Server Count';
$lang['Centovacast.manage.module_groups_heading.options'] = 'Options';
$lang['Centovacast.manage.module_rows.count'] = '%1$s / %2$s'; // %1$s is the current number of accounts, %2$s is the total number of accounts available
$lang['Centovacast.manage.module_rows.edit'] = 'Edit';
$lang['Centovacast.manage.module_groups.edit'] = 'Edit';
$lang['Centovacast.manage.module_rows.delete'] = 'Delete';
$lang['Centovacast.manage.module_groups.delete'] = 'Delete';
$lang['Centovacast.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this server?';
$lang['Centovacast.manage.module_groups.confirm_delete'] = 'Are you sure you want to delete this server group?';
$lang['Centovacast.manage.module_rows_no_results'] = 'There are no servers.';
$lang['Centovacast.manage.module_groups_no_results'] = 'There are no server groups.';

$lang['Centovacast.order_options.first'] = 'First Non-full Server';
$lang['Centovacast.order_options.roundrobin'] = 'Evenly Distribute Among Servers';


// Add row
$lang['Centovacast.add_row.box_title'] = 'Add CentovaCast Server';
$lang['Centovacast.add_row.basic_title'] = 'Basic Settings';
$lang['Centovacast.add_row.notes_title'] = 'Notes';
$lang['Centovacast.add_row.add_btn'] = 'Add Server';

$lang['Centovacast.edit_row.box_title'] = 'Edit CentovaCast Server';
$lang['Centovacast.edit_row.basic_title'] = 'Basic Settings';
$lang['Centovacast.edit_row.notes_title'] = 'Notes';
$lang['Centovacast.edit_row.add_btn'] = 'Edit Server';

$lang['Centovacast.row_meta.server_name'] = 'Server Label';
$lang['Centovacast.row_meta.hostname'] = 'Hostname';
$lang['Centovacast.row_meta.ipaddress'] = 'IP Address';
$lang['Centovacast.row_meta.username'] = 'Username';
$lang['Centovacast.row_meta.password'] = 'Password';
$lang['Centovacast.row_meta.port'] = 'Port';
$lang['Centovacast.row_meta.use_ssl'] = 'Use SSL when connecting to the API (recommended)';
$lang['Centovacast.row_meta.account_limit'] = 'Account Limit';


// Package fields
$lang['Centovacast.package_fields.servertype'] = 'Server Type';
$lang['Centovacast.package_fields.apptypes'] = 'AutoDJ Type';
$lang['Centovacast.package_fields.usesource'] = 'AutoDJ Capabilities';
$lang['Centovacast.package_fields.maxclients'] = 'Maximum Clients';
$lang['Centovacast.package_fields.maxbitrate'] = 'Maximum Bitrate';
$lang['Centovacast.package_fields.transferlimit'] = 'Transfer Limit';
$lang['Centovacast.package_fields.diskquota'] = 'Disk Quota';


// Service fields
$lang['Centovacast.service_field.hostname'] = 'Hostname';
$lang['Centovacast.service_field.password'] = 'Password';
$lang['Centovacast.service_field.title'] = 'Title';
$lang['Centovacast.service_field.genre'] = 'Genre';
$lang['Centovacast.service_field.ipaddress'] = 'IP Address';
$lang['Centovacast.service_field.port'] = 'Port';


// Service management
$lang['Centovacast.tab_actions.status_title'] = 'Server Status';
$lang['Centovacast.tab_actions.server_title'] = 'Server Actions';
$lang['Centovacast.tab_actions.action_restart'] = 'Restart';
$lang['Centovacast.tab_actions.action_stop'] = 'Stop';
$lang['Centovacast.tab_actions.action_start'] = 'Start';

$lang['Centovacast.tab_stats.info_title'] = 'Information';
$lang['Centovacast.tab_stats.info_heading.field'] = 'Field';
$lang['Centovacast.tab_stats.info_heading.value'] = 'Value';

$lang['Centovacast.tab_stats.info.username'] = 'Username';
$lang['Centovacast.tab_stats.info.hostname'] = 'Hostname';
$lang['Centovacast.tab_stats.info.ipaddress'] = 'IP Address';
$lang['Centovacast.tab_stats.info.port'] = 'Port';
$lang['Centovacast.tab_stats.info.maxclients'] = 'Maximum Clients';
$lang['Centovacast.tab_stats.info.samplerate'] = 'Sample Rate (Hz)';
$lang['Centovacast.tab_stats.info.maxbitrate'] = 'Maximum Bitare (Kbps)';
$lang['Centovacast.tab_stats.info.adminpassword'] = 'Password';
$lang['Centovacast.tab_stats.info.sourcepassword'] = 'Source Password';

$lang['Centovacast.tab_stats.bandwidth_title'] = 'Bandwidth';
$lang['Centovacast.tab_stats.bandwidth_heading.used'] = 'Used';
$lang['Centovacast.tab_stats.bandwidth_heading.limit'] = 'Limit';
$lang['Centovacast.tab_stats.bandwidth_value'] = '%1$s MB'; // %1$s is the amount of bandwidth in MB
$lang['Centovacast.tab_stats.bandwidth_unlimited'] = 'unlimited';
$lang['Centovacast.tab_stats.disk_title'] = 'Disk';
$lang['Centovacast.tab_stats.disk_heading.used'] = 'Used';
$lang['Centovacast.tab_stats.disk_heading.limit'] = 'Limit';
$lang['Centovacast.tab_stats.disk_value'] = '%1$s MB'; // %1$s is the amount of disk in MB
$lang['Centovacast.tab_stats.disk_unlimited'] = 'unlimited';


// Client actions
$lang['Centovacast.tab_client_actions.heading_status'] = 'Server Status';
$lang['Centovacast.tab_client_actions.status_online'] = 'Online';
$lang['Centovacast.tab_client_actions.status_offline'] = 'Offline';

$lang['Centovacast.tab_client_actions.heading_actions'] = 'Actions';
$lang['Centovacast.tab_client_actions.action_restart'] = 'Restart';
$lang['Centovacast.tab_client_actions.action_genre'] = 'Change Genre';
$lang['Centovacast.tab_client_actions.action_stop'] = 'Stop';
$lang['Centovacast.tab_client_actions.action_radio_title'] = 'Change Radio Title';
$lang['Centovacast.tab_client_actions.action_start'] = 'Start';
$lang['Centovacast.tab_client_actions.action_password'] = 'Change Password';

$lang['Centovacast.tab_client_actions.heading_genre'] = 'Change Genre';
$lang['Centovacast.tab_client_actions.field_genre'] = 'Genre';
$lang['Centovacast.tab_client_actions.field_genre_submit'] = 'Update Genre';

$lang['Centovacast.tab_client_actions.heading_radio_title'] = 'Change Radio Title';
$lang['Centovacast.tab_client_actions.field_radio_title'] = 'Radio Title';
$lang['Centovacast.tab_client_actions.field_radio_title_submit'] = 'Update Radio Title';

$lang['Centovacast.tab_client_actions.heading_password'] = 'Change Password';
$lang['Centovacast.tab_client_actions.field_password'] = 'Password';
$lang['Centovacast.tab_client_actions.field_password_submit'] = 'Update Password';


// Client Service management
$lang['Centovacast.tab_client_stats.info_title'] = 'Information';
$lang['Centovacast.tab_client_stats.info_heading.field'] = 'Field';
$lang['Centovacast.tab_client_stats.info_heading.value'] = 'Value';

$lang['Centovacast.tab_client_stats.info.username'] = 'Username';
$lang['Centovacast.tab_client_stats.info.hostname'] = 'Hostname';
$lang['Centovacast.tab_client_stats.info.ipaddress'] = 'IP Address';
$lang['Centovacast.tab_client_stats.info.port'] = 'Port';
$lang['Centovacast.tab_client_stats.info.maxclients'] = 'Maximum Clients';
$lang['Centovacast.tab_client_stats.info.samplerate'] = 'Sample Rate (Hz)';
$lang['Centovacast.tab_client_stats.info.maxbitrate'] = 'Maximum Bitare (Kbps)';
$lang['Centovacast.tab_client_stats.info.adminpassword'] = 'Password';
$lang['Centovacast.tab_client_stats.info.sourcepassword'] = 'Source Password';

$lang['Centovacast.tab_client_stats.bandwidth_title'] = 'Bandwidth Usage (Month to Date)';
$lang['Centovacast.tab_client_stats.disk_title'] = 'Disk Usage';
$lang['Centovacast.tab_client_stats.usage'] = '(%1$s MB/%2$s MB)'; // %1$s is the amount of resource usage, %2$s is the resource usage limit
$lang['Centovacast.tab_client_stats.usage_unlimited'] = '(%1$s MB/∞)'; // %1$s is the amount of resource usage


// Service info
$lang['Centovacast.service_info.hostname'] = 'Hostname';
$lang['Centovacast.service_info.username'] = 'Username';
$lang['Centovacast.service_info.password'] = 'Password';
$lang['Centovacast.service_info.options'] = 'Options';
$lang['Centovacast.service_info.option_login'] = 'Login';


// Tooltips
$lang['Centovacast.package_fields.tooltip.maxclients'] = 'Specifies the maximum number of listeners that may simultaneously tune the stream.';
$lang['Centovacast.package_fields.tooltip.maxbitrate'] = 'Specifies the maximum bit rate for this stream, in kilobits per second (kbps).';
$lang['Centovacast.package_fields.tooltip.transferlimit'] = 'Specifies the maximum monthly data transfer for this stream, in megabytes (MB). 0 means unlimited.';
$lang['Centovacast.package_fields.tooltip.diskquota'] = 'Specifies the maximum disk space for this stream, in megabytes (MB). 0 means unlimited.';
$lang['Centovacast.service_field.tooltip.ipaddress'] = 'Overwrite default IP address.';
$lang['Centovacast.service_field.tooltip.port'] = 'Set a specific port for the account, leave blank for select a port automatically.';


// Errors
$lang['Centovacast.!error.server_name_valid'] = 'You must enter a Server Label.';
$lang['Centovacast.!error.host_name_valid'] = 'The Hostname appears to be invalid.';
$lang['Centovacast.!error.user_name_valid'] = 'The Username appears to be invalid.';
$lang['Centovacast.!error.password_valid'] = 'The Password appears to be invalid.';
$lang['Centovacast.!error.password_valid_connection'] = 'A connection to the server could not be established. Please check to ensure that the Hostname, Username, Port and Password are correct.';
$lang['Centovacast.!error.account_limit_valid'] = 'Account Limit must be left blank (for unlimited accounts) or set to some integer value.';
$lang['Centovacast.!error.port_valid'] = 'The Port must be an numeric value.';
$lang['Centovacast.!error.meta[servertype].valid'] = 'The Server Type appears to be invalid.';
$lang['Centovacast.!error.meta[apptypes].valid'] = 'The Auto DJ Type appears to be invalid.';
$lang['Centovacast.!error.meta[usesource].valid'] = 'The Auto DJ Capabilities appears to be invalid.';
$lang['Centovacast.!error.meta[maxclients].valid'] = 'The Maximum Clients must be a numeric value.';
$lang['Centovacast.!error.meta[maxbitrate].valid'] = 'The Maximum Bitrate must be a numeric value.';
$lang['Centovacast.!error.meta[transferlimit].valid'] = 'The Transfer Limit must be a numeric value.';
$lang['Centovacast.!error.meta[diskquota].valid'] = 'The Disk Quota must be a numeric value.';

$lang['Centovacast.!error.api.internal'] = 'An internal error occurred, or the server did not respond to the request.';
$lang['Centovacast.!error.module_row.missing'] = 'An internal error occurred. The module row is unavailable.';

$lang['Centovacast.!error.centovacast_hostname.format'] = 'Please enter a valid hostname, e.g. domain.com.';
$lang['Centovacast.!error.centovacast_adminpassword.valid'] = 'Password must be at least 5 characters in length.';
