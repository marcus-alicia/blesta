<?php
/**
 * en_us language for the Tcadmin module
 */
// Basics
$lang['Tcadmin.name'] = "Tcadmin";
$lang['Tcadmin.description'] = "TCAdmin the game hosting control panel was designed from the ground up to help today's Game Service Provider save time and money, while allowing end users complete control over their servers.";
$lang['Tcadmin.module_row'] = "Server";
$lang['Tcadmin.module_row_plural'] = "Servers";
$lang['Tcadmin.module_group'] = "Server Group";

// Module management
$lang['Tcadmin.add_module_row'] = "Add Server";
$lang['Tcadmin.add_module_group'] = "Add Server Group";
$lang['Tcadmin.manage.module_rows_title'] = "Servers";
$lang['Tcadmin.manage.module_groups_title'] = "Server Groups";
$lang['Tcadmin.manage.module_rows_heading.name'] = "Server Label";
$lang['Tcadmin.manage.module_rows_heading.hostname'] = "Hostname";
$lang['Tcadmin.manage.module_rows_heading.port'] = "Port";
$lang['Tcadmin.manage.module_rows_heading.options'] = "Options";
$lang['Tcadmin.manage.module_groups_heading.name'] = "Group Name";
$lang['Tcadmin.manage.module_groups_heading.servers'] = "Server Count";
$lang['Tcadmin.manage.module_groups_heading.options'] = "Options";
$lang['Tcadmin.manage.module_rows.edit'] = "Edit";
$lang['Tcadmin.manage.module_groups.edit'] = "Edit";
$lang['Tcadmin.manage.module_rows.delete'] = "Delete";
$lang['Tcadmin.manage.module_groups.delete'] = "Delete";
$lang['Tcadmin.manage.module_rows.confirm_delete'] = "Are you sure you want to delete this server?";
$lang['Tcadmin.manage.module_groups.confirm_delete'] = "Are you sure you want to delete this server group?";
$lang['Tcadmin.manage.module_rows_no_results'] = "There are no servers.";
$lang['Tcadmin.manage.module_groups_no_results'] = "There are no server groups.";


$lang['Tcadmin.order_options.first'] = "First non-full server";

// Add row
$lang['Tcadmin.add_row.box_title'] = "Add Tcadmin Server";
$lang['Tcadmin.add_row.basic_title'] = "Basic Settings";
$lang['Tcadmin.add_row.add_btn'] = "Add Server";

$lang['Tcadmin.edit_row.box_title'] = "Edit Tcadmin Server";
$lang['Tcadmin.edit_row.basic_title'] = "Basic Settings";
$lang['Tcadmin.edit_row.add_btn'] = "Edit Server";

$lang['Tcadmin.row_meta.server_name'] = "Server Label";
$lang['Tcadmin.row_meta.host_name'] = "Hostname";
$lang['Tcadmin.row_meta.user_name'] = "User Name";
$lang['Tcadmin.row_meta.password'] = "Password";
$lang['Tcadmin.row_meta.port'] = "Port";
$lang['Tcadmin.row_meta.use_ssl'] = "Use SSL when connecting to the API (recommended)";
$lang['Tcadmin.row_meta.account_limit'] = "Account Limit";

// Package fields
$lang['Tcadmin.package_fields.server_type'] = "Server Type";
$lang['Tcadmin.package_fields.game_server'] = "Game Server";
$lang['Tcadmin.package_fields.voice_server'] = "Voice Server";
$lang['Tcadmin.package_fields.supported_servers'] = "Supported Tcadmin Servers";
$lang['Tcadmin.package_fields.yes'] = "Yes";
$lang['Tcadmin.package_fields.no'] = "No";
$lang['Tcadmin.package_fields.start'] = "Start after Creation";
$lang['Tcadmin.package_fields.priority'] = "Priority";
$lang['Tcadmin.package_fields.startup'] = "Startup";
$lang['Tcadmin.package_fields.priority.abovenormal'] = "Above Normal";
$lang['Tcadmin.package_fields.priority.belownormal'] = "Below Normal";
$lang['Tcadmin.package_fields.priority.normal'] = "Normal";
$lang['Tcadmin.package_fields.priority.high'] = "High";
$lang['Tcadmin.package_fields.priority.idle'] = "Idle";
$lang['Tcadmin.package_fields.priority.realtime'] = "Real Time";
$lang['Tcadmin.package_fields.startup.automatic'] = "Automatic";
$lang['Tcadmin.package_fields.startup.manual'] = "Manual";
$lang['Tcadmin.package_fields.startup.disabled'] = "Disabled";

// Service fields
$lang['Tcadmin.service_field.hostname'] = "Hostname";
$lang['Tcadmin.service_field.user_name'] = "Tcadmin Username";
$lang['Tcadmin.service_field.user_password'] = "Tcadmin Password";
$lang['Tcadmin.service_field.rcon_password'] = "RCON Password";
$lang['Tcadmin.service_field.private_password'] = "Private Password";


// Service info
$lang['Tcadmin.stored_locally_only'] = "This field will be updated locally only";
$lang['Tcadmin.service_info.hostname'] = "Hostname";
$lang['Tcadmin.service_info.rcon_password'] = "RCON Password";
$lang['Tcadmin.service_info.private_password'] = "Private Password";
$lang['Tcadmin.service_info.username'] = "Username";
$lang['Tcadmin.service_info.password'] = "Password";
$lang['Tcadmin.service_info.server'] = "Server";
$lang['Tcadmin.service_info.options'] = "Options";
$lang['Tcadmin.service_info.option_login'] = "Log in";


// Errors
$lang['Tcadmin.!error.server_name_valid'] = "You must enter a Server Label.";
$lang['Tcadmin.!error.host_name_valid'] = "The Hostname appears to be invalid.";
$lang['Tcadmin.!error.user_name_valid'] = "The User Name appears to be invalid.";
$lang['Tcadmin.!error.port_valid'] = "The Port appears to be invalid.";
$lang['Tcadmin.!error.password_valid'] = "The Password appears to be invalid.";
$lang['Tcadmin.!error.account_limit_valid'] = "The Account Limit appears to be invalid.";
$lang['Tcadmin.!error.meta[server_type].valid'] = "Please select a valid server type.";
$lang['Tcadmin.!error.meta[supported_servers].empty'] = "Please select one of the supported Tcadmin servers.";
$lang['Tcadmin.!error.meta[start].valid'] = "Please select a valid start option";
$lang['Tcadmin.!error.meta[priority].valid'] = "Please select a valid priority";
$lang['Tcadmin.!error.meta[startup].valid'] = "Please select a valid startup options";
$lang['Tcadmin.!error.api.internal'] = "An internal error occurred, or the server did not respond to the request.";
$lang['Tcadmin.!error.module_row.missing'] = "An internal error occurred. The module row is unavailable.";

$lang['Tcadmin.!error.hostname.format'] = "Please enter a valid domain name, e.g. domain.com.";
$lang['Tcadmin.!error.hostname.test'] = "Domain name can not start with 'test'.";
$lang['Tcadmin.!error.user_name.empty'] = "Username can't be empty.";
$lang['Tcadmin.!error.user_password.valid'] = "Password must be at least 8 characters in length.";
$lang['Tcadmin.!error.rcon_password.valid'] = "RCON Password must be at least 8 characters in length.";
$lang['Tcadmin.!error.private_password.valid'] = "Private Password must be at least 8 characters in length.";
?>