<?php
/**
 * en_us language for the teamspeak module.
 */
// Basics
$lang['Teamspeak.name'] = 'TeamSpeak';
$lang['Teamspeak.description'] = 'TeamSpeak is a voice-over-Internet Protocol application for audio communication between users on a chat channel.';
$lang['Teamspeak.module_row'] = 'Server';
$lang['Teamspeak.module_row_plural'] = 'Servers';
$lang['Teamspeak.module_group'] = 'Server Group';
$lang['Teamspeak.tab_actions'] = 'Actions';
$lang['Teamspeak.tab_clients'] = 'Clients';
$lang['Teamspeak.tab_bans'] = 'Bans';
$lang['Teamspeak.tab_tokens'] = 'Tokens';
$lang['Teamspeak.tab_logs'] = 'Logs';
$lang['Teamspeak.tab_client_actions'] = 'Actions';
$lang['Teamspeak.tab_client_clients'] = 'Clients';
$lang['Teamspeak.tab_client_bans'] = 'Bans';
$lang['Teamspeak.tab_client_tokens'] = 'Tokens';
$lang['Teamspeak.tab_client_logs'] = 'Logs';

// Module management
$lang['Teamspeak.add_module_row'] = 'Add Server';
$lang['Teamspeak.add_module_group'] = 'Add Server Group';
$lang['Teamspeak.manage.module_rows_title'] = 'Servers';
$lang['Teamspeak.manage.module_groups_title'] = 'Server Groups';
$lang['Teamspeak.manage.module_rows_heading.name'] = 'Server Label';
$lang['Teamspeak.manage.module_rows_heading.hostname'] = 'Hostname';
$lang['Teamspeak.manage.module_rows_heading.accounts'] = 'Accounts';
$lang['Teamspeak.manage.module_rows_heading.options'] = 'Options';
$lang['Teamspeak.manage.module_groups_heading.name'] = 'Group Name';
$lang['Teamspeak.manage.module_groups_heading.servers'] = 'Server Count';
$lang['Teamspeak.manage.module_groups_heading.options'] = 'Options';
$lang['Teamspeak.manage.module_rows.count'] = '%1$s / %2$s'; // %1$s is the current number of accounts, %2$s is the total number of accounts available
$lang['Teamspeak.manage.module_rows.edit'] = 'Edit';
$lang['Teamspeak.manage.module_groups.edit'] = 'Edit';
$lang['Teamspeak.manage.module_rows.delete'] = 'Delete';
$lang['Teamspeak.manage.module_groups.delete'] = 'Delete';
$lang['Teamspeak.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this server?';
$lang['Teamspeak.manage.module_groups.confirm_delete'] = 'Are you sure you want to delete this server group?';
$lang['Teamspeak.manage.module_rows_no_results'] = 'There are no servers.';
$lang['Teamspeak.manage.module_groups_no_results'] = 'There are no server groups.';

$lang['Teamspeak.order_options.first'] = 'First Non-full Server';
$lang['Teamspeak.order_options.roundrobin'] = 'Evenly Distribute Among Servers';

// Add row
$lang['Teamspeak.add_row.box_title'] = 'Add TeamSpeak Server';
$lang['Teamspeak.add_row.basic_title'] = 'Basic Settings';
$lang['Teamspeak.add_row.notes_title'] = 'Notes';
$lang['Teamspeak.add_row.add_btn'] = 'Add Server';

$lang['Teamspeak.edit_row.box_title'] = 'Edit TeamSpeak Server';
$lang['Teamspeak.edit_row.basic_title'] = 'Basic Settings';
$lang['Teamspeak.edit_row.notes_title'] = 'Notes';
$lang['Teamspeak.edit_row.add_btn'] = 'Edit Server';

$lang['Teamspeak.row_meta.server_name'] = 'Server Label';
$lang['Teamspeak.row_meta.hostname'] = 'Hostname';
$lang['Teamspeak.row_meta.username'] = 'Username';
$lang['Teamspeak.row_meta.password'] = 'Password';
$lang['Teamspeak.row_meta.port'] = 'Port';
$lang['Teamspeak.row_meta.account_limit'] = 'Account Limit';

// Package fields
$lang['Teamspeak.package_fields.maxclients'] = 'Maximum Clients';

// Service fields
$lang['Teamspeak.service_field.name'] = 'Server Name';
$lang['Teamspeak.service_field.port'] = 'Port';
$lang['Teamspeak.service_field.sid'] = 'Virtual Server ID';

// Service management
$lang['Teamspeak.tab_actions.heading_status'] = 'Server Status';
$lang['Teamspeak.tab_actions.status_online'] = 'Online';
$lang['Teamspeak.tab_actions.status_offline'] = 'Offline';

$lang['Teamspeak.tab_actions.status_title'] = 'Server Status';
$lang['Teamspeak.tab_actions.server_title'] = 'Server Actions';
$lang['Teamspeak.tab_actions.action_restart'] = 'Restart';
$lang['Teamspeak.tab_actions.action_edit_name'] = 'Edit Name';
$lang['Teamspeak.tab_actions.action_stop'] = 'Stop';
$lang['Teamspeak.tab_actions.action_remove_ban'] = 'Remove Ban Rules';
$lang['Teamspeak.tab_actions.action_start'] = 'Start';
$lang['Teamspeak.tab_actions.action_reset_permissions'] = 'Reset Permissions';

$lang['Teamspeak.tab_actions.heading_change_name'] = 'Change Name';
$lang['Teamspeak.tab_actions.field_name'] = 'Name';
$lang['Teamspeak.tab_actions.field_name_submit'] = 'Change Name';

// Server clients
$lang['Teamspeak.tab_clients.heading_clients'] = 'Clients';
$lang['Teamspeak.tab_clients.clients_heading.client_id'] = 'Client ID';
$lang['Teamspeak.tab_clients.clients_heading.client_name'] = 'Client Name';
$lang['Teamspeak.tab_clients.clients_heading.options'] = 'Options';
$lang['Teamspeak.tab_clients.action_kick_client'] = 'Kick Client';
$lang['Teamspeak.tab_clients.text_no_clients'] = 'There are no clients yet.';

// Server bans
$lang['Teamspeak.tab_bans.heading_bans'] = 'Bans';
$lang['Teamspeak.tab_bans.bans_heading.date'] = 'Date';
$lang['Teamspeak.tab_bans.bans_heading.ban_id'] = 'Ban ID';
$lang['Teamspeak.tab_bans.bans_heading.ban_rule'] = 'Ban Rule';
$lang['Teamspeak.tab_bans.bans_heading.reason'] = 'Reason';
$lang['Teamspeak.tab_bans.bans_heading.options'] = 'Options';
$lang['Teamspeak.tab_bans.action_unban_client'] = 'Unban Client';
$lang['Teamspeak.tab_bans.text_no_bans'] = 'There are no ban rules yet.';

$lang['Teamspeak.tab_bans.heading_create_ban'] = 'Create Ban';
$lang['Teamspeak.tab_bans.field_ip_address'] = 'IP Address';
$lang['Teamspeak.tab_bans.field_reason'] = 'Reason';
$lang['Teamspeak.tab_bans.field_ban_submit'] = 'Create Ban';

// Server tokens
$lang['Teamspeak.tab_tokens.heading_tokens'] = 'Tokens';
$lang['Teamspeak.tab_tokens.tokens_heading.date'] = 'Creation Date';
$lang['Teamspeak.tab_tokens.tokens_heading.token'] = 'Token';
$lang['Teamspeak.tab_tokens.tokens_heading.description'] = 'Description';
$lang['Teamspeak.tab_tokens.tokens_heading.options'] = 'Options';
$lang['Teamspeak.tab_tokens.action_delete_token'] = 'Delete';
$lang['Teamspeak.tab_tokens.text_no_tokens'] = 'There are no tokens yet.';

$lang['Teamspeak.tab_tokens.heading_create_token'] = 'Create Token';
$lang['Teamspeak.tab_tokens.field_sgid'] = 'Server Group';
$lang['Teamspeak.tab_tokens.field_description'] = 'Description';
$lang['Teamspeak.tab_tokens.field_token_submit'] = 'Create Token';

// Server log
$lang['Teamspeak.tab_logs.heading_log'] = 'Log';
$lang['Teamspeak.tab_logs.log_heading.date'] = 'Date';
$lang['Teamspeak.tab_logs.log_heading.type'] = 'Type';
$lang['Teamspeak.tab_logs.log_heading.function'] = 'Function';
$lang['Teamspeak.tab_logs.log_heading.server_id'] = 'Server ID';
$lang['Teamspeak.tab_logs.log_heading.description'] = 'Description';
$lang['Teamspeak.tab_logs.text_no_logs'] = 'There are no logs yet.';

$lang['Teamspeak.tab_logs.type_WARNING'] = 'Warning';
$lang['Teamspeak.tab_logs.type_INFO'] = 'Information';
$lang['Teamspeak.tab_logs.type_DANGER'] = 'Danger';
$lang['Teamspeak.tab_logs.type_SUCCESS'] = 'Success';

// Client actions
$lang['Teamspeak.tab_client_actions.heading_status'] = 'Server Status';
$lang['Teamspeak.tab_client_actions.status_online'] = 'Online';
$lang['Teamspeak.tab_client_actions.status_offline'] = 'Offline';

$lang['Teamspeak.tab_client_actions.heading_actions'] = 'Actions';
$lang['Teamspeak.tab_client_actions.action_restart'] = 'Restart';
$lang['Teamspeak.tab_client_actions.action_edit_name'] = 'Edit Name';
$lang['Teamspeak.tab_client_actions.action_stop'] = 'Stop';
$lang['Teamspeak.tab_client_actions.action_remove_ban'] = 'Remove Ban Rules';
$lang['Teamspeak.tab_client_actions.action_start'] = 'Start';
$lang['Teamspeak.tab_client_actions.action_reset_permissions'] = 'Reset Permissions';

$lang['Teamspeak.tab_client_actions.heading_change_name'] = 'Change Name';
$lang['Teamspeak.tab_client_actions.field_name'] = 'Name';
$lang['Teamspeak.tab_client_actions.field_name_submit'] = 'Change Name';

// Client server clients
$lang['Teamspeak.tab_client_clients.heading_clients'] = 'Clients';
$lang['Teamspeak.tab_client_clients.clients_heading.client_id'] = 'Client ID';
$lang['Teamspeak.tab_client_clients.clients_heading.client_name'] = 'Client Name';
$lang['Teamspeak.tab_client_clients.clients_heading.options'] = 'Options';
$lang['Teamspeak.tab_client_clients.action_kick_client'] = 'Kick Client';
$lang['Teamspeak.tab_client_clients.text_no_clients'] = 'There are no clients yet.';

// Client server bans
$lang['Teamspeak.tab_client_bans.heading_bans'] = 'Bans';
$lang['Teamspeak.tab_client_bans.bans_heading.date'] = 'Date';
$lang['Teamspeak.tab_client_bans.bans_heading.ban_id'] = 'Ban ID';
$lang['Teamspeak.tab_client_bans.bans_heading.ban_rule'] = 'Ban Rule';
$lang['Teamspeak.tab_client_bans.bans_heading.reason'] = 'Reason';
$lang['Teamspeak.tab_client_bans.bans_heading.options'] = 'Options';
$lang['Teamspeak.tab_client_bans.action_unban_client'] = 'Unban Client';
$lang['Teamspeak.tab_client_bans.action_ban'] = 'Create Ban';
$lang['Teamspeak.tab_client_bans.text_no_bans'] = 'There are no ban rules yet.';

$lang['Teamspeak.tab_client_bans.heading_create_ban'] = 'Create Ban';
$lang['Teamspeak.tab_client_bans.field_ip_address'] = 'IP Address';
$lang['Teamspeak.tab_client_bans.field_reason'] = 'Reason';
$lang['Teamspeak.tab_client_bans.field_ban_submit'] = 'Create Ban';

// Client server tokens
$lang['Teamspeak.tab_client_tokens.heading_tokens'] = 'Tokens';
$lang['Teamspeak.tab_client_tokens.tokens_heading.date'] = 'Creation Date';
$lang['Teamspeak.tab_client_tokens.tokens_heading.token'] = 'Token';
$lang['Teamspeak.tab_client_tokens.tokens_heading.description'] = 'Description';
$lang['Teamspeak.tab_client_tokens.tokens_heading.options'] = 'Options';
$lang['Teamspeak.tab_client_tokens.action_delete_token'] = 'Delete';
$lang['Teamspeak.tab_client_tokens.action_token'] = 'Create Token';
$lang['Teamspeak.tab_client_tokens.text_no_tokens'] = 'There are no tokens yet.';

$lang['Teamspeak.tab_client_tokens.heading_create_token'] = 'Create Token';
$lang['Teamspeak.tab_client_tokens.field_sgid'] = 'Server Group';
$lang['Teamspeak.tab_client_tokens.field_description'] = 'Description';
$lang['Teamspeak.tab_client_tokens.field_token_submit'] = 'Create Token';

// Client server log
$lang['Teamspeak.tab_client_logs.heading_log'] = 'Log';
$lang['Teamspeak.tab_client_logs.log_heading.date'] = 'Date';
$lang['Teamspeak.tab_client_logs.log_heading.type'] = 'Type';
$lang['Teamspeak.tab_client_logs.log_heading.function'] = 'Function';
$lang['Teamspeak.tab_client_logs.log_heading.server_id'] = 'Server ID';
$lang['Teamspeak.tab_client_logs.log_heading.description'] = 'Description';
$lang['Teamspeak.tab_client_logs.text_no_logs'] = 'There are no logs yet.';

$lang['Teamspeak.tab_client_logs.type_WARNING'] = 'Warning';
$lang['Teamspeak.tab_client_logs.type_INFO'] = 'Information';
$lang['Teamspeak.tab_client_logs.type_DANGER'] = 'Danger';
$lang['Teamspeak.tab_client_logs.type_SUCCESS'] = 'Success';

// Service info
$lang['Teamspeak.service_info.hostname'] = 'Hostname';
$lang['Teamspeak.service_info.port'] = 'Port';
$lang['Teamspeak.service_info.token'] = 'Token';

// Tooltips
$lang['Teamspeak.package_fields.tooltip.maxclients'] = 'Specifies the maximum number of users of the virtual server.';
$lang['Teamspeak.service_field.tooltip.port'] = 'Set a specific port for the account, leave blank for select a port automatically.';
$lang['Teamspeak.service_field.tooltip.sid'] = 'The Virtual Server ID specifies the server from TeamSpeak to which this service will be attached. Changing this value will only affect this service locally.';

// Errors
$lang['Teamspeak.!error.server_name_valid'] = 'You must enter a Server Label.';
$lang['Teamspeak.!error.host_name_valid'] = 'The Hostname appears to be invalid.';
$lang['Teamspeak.!error.user_name_valid'] = 'The Username appears to be invalid.';
$lang['Teamspeak.!error.password_valid'] = 'The Password appears to be invalid.';
$lang['Teamspeak.!error.password_valid_connection'] = 'A connection to the server could not be established. Please check to ensure that the Hostname, Username, Port and Password are correct.';
$lang['Teamspeak.!error.account_limit_valid'] = 'Account Limit must be left blank (for unlimited accounts) or set to some integer value.';
$lang['Teamspeak.!error.port_valid'] = 'The Port must be an numeric value.';
$lang['Teamspeak.!error.meta[maxclients].valid'] = 'The Maximum Clients must be a numeric value.';

$lang['Teamspeak.!error.api.internal'] = 'An internal error occurred, or the server did not respond to the request.';
$lang['Teamspeak.!error.module_row.missing'] = 'An internal error occurred. The module row is unavailable.';

$lang['Teamspeak.!error.teamspeak_name.empty'] = 'The Server Name appears to be empty.';
