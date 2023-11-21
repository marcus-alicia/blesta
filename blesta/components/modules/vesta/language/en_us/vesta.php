<?php
/**
 * en_us language for the Vesta module
 */
// Basics
$lang['Vesta.name'] = "Vesta";
$lang['Vesta.description'] = "Vesta, or VestaCP, is an open-source web control panel that is considered to be a great alternative to cPanel. Not only does its security and frequent updates match in quality with that of cPanel, it's also completely free.";
$lang['Vesta.module_row'] = "Server";
$lang['Vesta.module_row_plural'] = "Servers";
$lang['Vesta.module_group'] = "Server Group";
$lang['Vesta.tab_stats'] = "Statistics";
$lang['Vesta.tab_client_actions'] = "Actions";
$lang['Vesta.submit'] = "Submit";


$lang['Vesta.tab_stats.info.WEB_DOMAINS'] = "Domains";
$lang['Vesta.tab_stats.info.WEB_ALIASES'] = "Aliases";
$lang['Vesta.tab_stats.info.DNS_DOMAINS'] = "DNS Domains";
$lang['Vesta.tab_stats.info.DNS_RECORDS'] = "DNS Records";
$lang['Vesta.tab_stats.info.MAIL_DOMAINS'] = "Mail Domains";
$lang['Vesta.tab_stats.info.MAIL_ACCOUNTS'] = "Mail Accounts";
$lang['Vesta.tab_stats.info.DATABASES'] = "Databases";
$lang['Vesta.tab_stats.info.CRON_JOBS'] = "Cron Jobs";
$lang['Vesta.tab_stats.info.DISK_QUOTA'] = "Disk Usage";
$lang['Vesta.tab_stats.info.BANDWIDTH'] = "Bandwidth Usage";
$lang['Vesta.tab_stats.info.BACKUPS'] = "Backups";


// Module management
$lang['Vesta.add_module_row'] = "Add Server";
$lang['Vesta.add_module_group'] = "Add Server Group";
$lang['Vesta.manage.module_rows_title'] = "Servers";
$lang['Vesta.manage.module_groups_title'] = "Server Groups";
$lang['Vesta.manage.module_rows_heading.name'] = "Server Label";
$lang['Vesta.manage.module_rows_heading.hostname'] = "Hostname";
$lang['Vesta.manage.module_rows_heading.port'] = "Port";
$lang['Vesta.manage.module_rows_heading.accounts'] = "Accounts";
$lang['Vesta.manage.module_rows_heading.options'] = "Options";
$lang['Vesta.manage.module_groups_heading.name'] = "Group Name";
$lang['Vesta.manage.module_groups_heading.servers'] = "Server Count";
$lang['Vesta.manage.module_groups_heading.options'] = "Options";
$lang['Vesta.manage.module_rows.count'] = "%1\$s / %2\$s"; // %1$s is the current number of accounts, %2$s is the total number of accounts available
$lang['Vesta.manage.module_rows.edit'] = "Edit";
$lang['Vesta.manage.module_groups.edit'] = "Edit";
$lang['Vesta.manage.module_rows.delete'] = "Delete";
$lang['Vesta.manage.module_groups.delete'] = "Delete";
$lang['Vesta.manage.module_rows.confirm_delete'] = "Are you sure you want to delete this server?";
$lang['Vesta.manage.module_groups.confirm_delete'] = "Are you sure you want to delete this server group?";
$lang['Vesta.manage.module_rows_no_results'] = "There are no servers.";
$lang['Vesta.manage.module_groups_no_results'] = "There are no server groups.";


$lang['Vesta.order_options.first'] = "First non-full server";

// Add row
$lang['Vesta.add_row.box_title'] = "Add Vesta Server";
$lang['Vesta.add_row.basic_title'] = "Basic Settings";
$lang['Vesta.add_row.remove_name_server'] = "Remove";
$lang['Vesta.add_row.add_btn'] = "Add Server";

$lang['Vesta.edit_row.box_title'] = "Edit Vesta Server";
$lang['Vesta.edit_row.basic_title'] = "Basic Settings";
$lang['Vesta.edit_row.remove_name_server'] = "Remove";
$lang['Vesta.edit_row.add_btn'] = "Edit Server";

$lang['Vesta.row_meta.server_name'] = "Server Label";
$lang['Vesta.row_meta.host_name'] = "Hostname";
$lang['Vesta.row_meta.user_name'] = "User Name";
$lang['Vesta.row_meta.password'] = "Password";
$lang['Vesta.row_meta.port'] = "Port";
$lang['Vesta.row_meta.use_ssl'] = "Use SSL when connecting to the API (recommended)";

// Package fields
$lang['Vesta.package_fields.package_name'] = "Vesta Package Name";
$lang['Vesta.package_fields.package_name_how_to'] = "You can get the package name by logging into your Vesta Control Panel > Packages";

// Service fields
$lang['Vesta.service_field.domain'] = "Domain Name";
$lang['Vesta.service_field.username'] = "Username";
$lang['Vesta.service_field.password'] = "Password";


// Service info
$lang['Vesta.stored_locally_only'] = "This field will be updated locally only";
$lang['Vesta.service_info.hostname'] = "Hostname";
$lang['Vesta.service_info.username'] = "Username";
$lang['Vesta.service_info.password'] = "Password";
$lang['Vesta.service_info.server'] = "Server";
$lang['Vesta.service_info.options'] = "Options";
$lang['Vesta.service_info.option_login'] = "Log in";
$lang['Vesta.tab_client_actions.change_password'] = "Change Password";
$lang['Vesta.tab_client_actions.password'] = "Password";
$lang['Vesta.tab_client_actions.confirm_password'] = "Confirm Password";
$lang['Vesta.tab_stats.info_heading.field'] = "Field";
$lang['Vesta.tab_stats.info_heading.value'] = "Value";


// Errors
$lang['Vesta.!error.server_name_valid'] = "You must enter a Server Label.";
$lang['Vesta.!error.host_name_valid'] = "The Hostname appears to be invalid.";
$lang['Vesta.!error.user_name_valid'] = "The User Name appears to be invalid.";
$lang['Vesta.!error.port_valid'] = "The Port appears to be invalid.";
$lang['Vesta.!error.password_valid'] = "The Password appears to be invalid.";
$lang['Vesta.!error.meta[package_name].empty'] = "Please enter the Vesta package name.";
$lang['Vesta.!error.api.internal'] = "An internal error occurred, or the server did not respond to the request.";
$lang['Vesta.!error.module_row.missing'] = "An internal error occurred. The module row is unavailable.";

$lang['Vesta.!error.domain.format'] = "Please enter a valid domain name, e.g. domain.com.";
$lang['Vesta.!error.domain.test'] = "Domain name can not start with 'test'.";
$lang['Vesta.!error.user_name.empty'] = "Username can't be empty.";
$lang['Vesta.!error.password.valid'] = "Password must be at least 8 characters in length.";

$lang['Vesta.!error.actions_password.valid'] = "Password & Confirm Password fields can't be empty.";

?>