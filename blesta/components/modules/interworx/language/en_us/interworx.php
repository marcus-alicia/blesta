<?php
/**
 * en_us language for the interworx module
 */
// Errors
$lang['Interworx.!error.libxml_required'] = 'The libxml extension is required for this module.';
$lang['Interworx.!error.soap_required'] = 'The SOAP extension is required for this module.';


// Basics
$lang['Interworx.name'] = 'Interworx';
$lang['Interworx.description'] = 'The InterWorx Web Control Panel is a Linux based dedicated server and VPS web hosting control panel. It is feature rich for both system administrators and website administrators.';
$lang['Interworx.module_row'] = 'Server';
$lang['Interworx.module_row_plural'] = 'Servers';
$lang['Interworx.module_group'] = 'Server Group';
$lang['Interworx.tab_stats'] = 'Statistics';

// Module management
$lang['Interworx.add_module_row'] = 'Add Server';
$lang['Interworx.add_module_group'] = 'Add Server Group';
$lang['Interworx.manage.module_rows_title'] = 'Servers';
$lang['Interworx.manage.module_groups_title'] = 'Server Groups';
$lang['Interworx.manage.module_rows_heading.name'] = 'Server Label';
$lang['Interworx.manage.module_rows_heading.hostname'] = 'Hostname';
$lang['Interworx.manage.module_rows_heading.accounts'] = 'Accounts';
$lang['Interworx.manage.module_rows_heading.options'] = 'Options';
$lang['Interworx.manage.module_groups_heading.name'] = 'Group Name';
$lang['Interworx.manage.module_groups_heading.servers'] = 'Server Count';
$lang['Interworx.manage.module_groups_heading.options'] = 'Options';
$lang['Interworx.manage.module_rows.count'] = '%1$s / %2$s'; // %1$s is the current number of accounts, %2$s is the total number of accounts available
$lang['Interworx.manage.module_rows.edit'] = 'Edit';
$lang['Interworx.manage.module_groups.edit'] = 'Edit';
$lang['Interworx.manage.module_rows.delete'] = 'Delete';
$lang['Interworx.manage.module_groups.delete'] = 'Delete';
$lang['Interworx.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this server?';
$lang['Interworx.manage.module_groups.confirm_delete'] = 'Are you sure you want to delete this server group?';
$lang['Interworx.manage.module_rows_no_results'] = 'There are no servers.';
$lang['Interworx.manage.module_groups_no_results'] = 'There are no server groups.';


$lang['Interworx.order_options.first'] = 'First Non-full Server';
$lang['Interworx.order_options.roundrobin'] = 'Evenly Distribute Among Servers';

// Add row
$lang['Interworx.add_row.box_title'] = 'Add Interworx Server';
$lang['Interworx.add_row.basic_title'] = 'Basic Settings';
$lang['Interworx.add_row.name_servers_title'] = 'Name Servers';
$lang['Interworx.add_row.notes_title'] = 'Notes';
$lang['Interworx.add_row.name_server_btn'] = 'Add Additional Name Server';
$lang['Interworx.add_row.name_server_col'] = 'Name Server';
$lang['Interworx.add_row.name_server_host_col'] = 'Hostname';
$lang['Interworx.add_row.name_server'] = 'Name server %1$s'; // %1$s is the name server number (e.g. 3)
$lang['Interworx.add_row.remove_name_server'] = 'Remove';
$lang['Interworx.add_row.add_btn'] = 'Add Server';

$lang['Interworx.edit_row.box_title'] = 'Edit Interworx Server';
$lang['Interworx.edit_row.basic_title'] = 'Basic Settings';
$lang['Interworx.edit_row.name_servers_title'] = 'Name Servers';
$lang['Interworx.edit_row.notes_title'] = 'Notes';
$lang['Interworx.edit_row.name_server_btn'] = 'Add Additional Name Server';
$lang['Interworx.edit_row.name_server_col'] = 'Name Server';
$lang['Interworx.edit_row.name_server_host_col'] = 'Hostname';
$lang['Interworx.edit_row.name_server'] = 'Name server %1$s'; // %1$s is the name server number (e.g. 3)
$lang['Interworx.edit_row.remove_name_server'] = 'Remove';
$lang['Interworx.edit_row.add_btn'] = 'Edit Server';

$lang['Interworx.row_meta.server_name'] = 'Server Label';
$lang['Interworx.row_meta.host_name'] = 'Hostname';
$lang['Interworx.row_meta.port'] = 'Port Number';
$lang['Interworx.row_meta.default_port'] = '2443';
$lang['Interworx.row_meta.key'] = 'Remote Key';
$lang['Interworx.row_meta.account_limit'] = 'Account Limit';
$lang['Interworx.row_meta.debug'] = 'Debugging';
$lang['Interworx.row_meta.debug.none'] = 'None';
$lang['Interworx.row_meta.debug.log'] = 'Log';
$lang['Interworx.row_meta.debug.print'] = 'Print';
$lang['Interworx.row_meta.use_ssl'] = 'Use SSL when connecting to the API (recommended)';

// Package fields
$lang['Interworx.package_fields.type'] = 'Account Type';
$lang['Interworx.package_fields.type_standard'] = 'Standard';
$lang['Interworx.package_fields.type_reseller'] = 'Reseller';
$lang['Interworx.package_fields.package'] = 'Interworx Package';

// Service fields
$lang['Interworx.service_field.reseller_id'] = 'Reseller ID';
$lang['Interworx.service_field.domain'] = 'Domain';
$lang['Interworx.service_field.username'] = 'Username';
$lang['Interworx.service_field.email'] = 'Email';
$lang['Interworx.service_field.password'] = 'Password';
$lang['Interworx.service_field.confirm_password'] = 'Confirm Password';

$lang['Interworx.service_field.tooltip.interworx_reseller_id'] = 'The reseller ID will only be changed locally. It will not be changed in Interworx.';
$lang['Interworx.service_field.tooltip.domain'] = 'The domain will only be changed locally. It will not be changed in Interworx.';
$lang['Interworx.service_field.tooltip.email'] = "You may leave the email blank to automatically use the client's email address.";
$lang['Interworx.service_field.tooltip.username'] = 'You may leave the username blank to automatically generate one.';
$lang['Interworx.service_field.tooltip.password'] = 'You may leave the password blank to automatically generate one.';


// Service management
$lang['Interworx.tab_stats.info_title'] = 'Information';
$lang['Interworx.tab_stats.info_heading.field'] = 'Field';
$lang['Interworx.tab_stats.info_heading.value'] = 'Value';
$lang['Interworx.tab_stats.info.server'] = 'Server';
$lang['Interworx.tab_stats.info.domain'] = 'Domain';
$lang['Interworx.tab_stats.info.ip'] = 'IP Address';
$lang['Interworx.tab_stats.bandwidth_title'] = 'Bandwidth';
$lang['Interworx.tab_stats.bandwidth_heading.used'] = 'Used';
$lang['Interworx.tab_stats.bandwidth_heading.limit'] = 'Limit';
$lang['Interworx.tab_stats.bandwidth_value'] = '%1$s GB'; // %1$s is the amount of bandwidth in GB
$lang['Interworx.tab_stats.bandwidth_unlimited'] = 'Unlimited';
$lang['Interworx.tab_stats.disk_title'] = 'Disk';
$lang['Interworx.tab_stats.disk_heading.used'] = 'Used';
$lang['Interworx.tab_stats.disk_heading.limit'] = 'Limit';
$lang['Interworx.tab_stats.disk_value'] = '%1$s MB'; // %1$s is the amount of disk in MB
$lang['Interworx.tab_stats.disk_unlimited'] = 'Unlimited';

// Package Information
$lang['Interworx.tab_stats.package_title'] = 'Package Information';
$lang['Interworx.tab_stats.package_info.id'] = 'ID';
$lang['Interworx.tab_stats.package_info.name'] = 'Name';
$lang['Interworx.tab_stats.package_info.opt_storage'] = 'Disk Space';
$lang['Interworx.tab_stats.package_info.opt_bandwidth'] = 'Bandwidth';
$lang['Interworx.tab_stats.package_info.opt_email_aliases'] = 'Email Aliases';
$lang['Interworx.tab_stats.package_info.opt_email_autoresponders'] = 'Email Autoresponders';
$lang['Interworx.tab_stats.package_info.opt_email_boxes'] = 'Email Accounts';
$lang['Interworx.tab_stats.package_info.opt_email_groups'] = 'Email Groups';
$lang['Interworx.tab_stats.package_info.opt_ftp_accounts'] = 'FTP Accounts';
$lang['Interworx.tab_stats.package_info.opt_mysql_dbs'] = 'MySQL Databases';
$lang['Interworx.tab_stats.package_info.opt_mysql_db_users'] = 'MySQL Users';
$lang['Interworx.tab_stats.package_info.opt_pointer_domains'] = 'Redirect Domains';
$lang['Interworx.tab_stats.package_info.opt_slave_domains'] = 'Slave Domains';
$lang['Interworx.tab_stats.package_info.opt_subdomains'] = 'Sub Domains';
$lang['Interworx.tab_stats.package_info.opt_backup'] = 'Backups Enabled';
$lang['Interworx.tab_stats.package_info.opt_cgi_access'] = 'CGI Access';
$lang['Interworx.tab_stats.package_info.opt_crontab'] = 'Cron Access';
$lang['Interworx.tab_stats.package_info.opt_dns_records'] = 'DNS Access';
$lang['Interworx.tab_stats.package_info.opt_resolve_xferlog_dns'] = 'Resolve XFERLOG DNS';
$lang['Interworx.tab_stats.package_info.opt_ssl'] = 'SSL';
$lang['Interworx.tab_stats.package_info.opt_burstable'] = 'Burstable';
$lang['Interworx.tab_stats.package_info.opt_save_xfer_logs'] = 'Save XFER Logs';
$lang['Interworx.tab_stats.package_info.opt_oversell_bandwidth'] = 'Oversell Bandwidth';
$lang['Interworx.tab_stats.package_info.opt_oversell_storage'] = 'Oversell Storage';
$lang['Interworx.tab_stats.package_info.opt_siteworx_accounts'] = 'Number of Account can sell';
$lang['Interworx.tab_stats.package_info.opt_unlimited'] = 'Unlimited';


// Tab unavailable
$lang['Interworx.tab_unavailable.message'] = 'This information is not yet available.';


// Client actions
$lang['Interworx.tab_client_actions'] = 'Actions';
$lang['Interworx.tab_client_actions.change_password'] = 'Change Password';
$lang['Interworx.tab_client_actions.field_interworx_password'] = 'Password';
$lang['Interworx.tab_client_actions.field_interworx_confirm_password'] = 'Confirm Password';
$lang['Interworx.tab_client_actions.field_password_submit'] = 'Update Password';


// Service info
$lang['Interworx.service_info.email'] = 'Email';
$lang['Interworx.service_info.password'] = 'Password';
$lang['Interworx.service_info.server'] = 'Server';
$lang['Interworx.service_info.options'] = 'Options';
$lang['Interworx.service_info.option_login'] = 'Log in';

// Errors
$lang['Interworx.!error.server_name_valid'] = 'You must enter a Server Label.';
$lang['Interworx.!error.host_name_valid'] = 'The Hostname appears to be invalid.';
$lang['Interworx.!error.user_name_valid'] = 'The User Name appears to be invalid.';
$lang['Interworx.!error.remote_key_valid'] = 'The Remote Key appears to be invalid.';
$lang['Interworx.!error.remote_key_valid_connection'] = 'A connection to the server could not be established. Please check to ensure that the Hostname, Port Number, and Remote Key are correct.';
$lang['Interworx.!error.account_limit_valid'] = 'Account Limit must be left blank (for unlimited accounts) or set to some integer value.';
$lang['Interworx.!error.name_servers_valid'] = 'One or more of the name servers entered are invalid.';
$lang['Interworx.!error.name_servers_count'] = 'You must define at least 2 name servers.';
$lang['Interworx.!error.meta[type].valid'] = 'Account type must be either standard or reseller.';
$lang['Interworx.!error.meta[package].empty'] = 'A Interworx Package is required.';


$lang['Interworx.!error.interworx_domain.format'] = 'Please enter a valid domain name of the form: domain.com';
$lang['Interworx.!error.interworx_username.format'] = 'The username may only contain alphanumeric characters.';
$lang['Interworx.!error.interworx_username.length'] = 'The username must be between 1 and 8 characters in length.';
$lang['Interworx.!error.interworx_password.format'] = 'Please enter a password.';
$lang['Interworx.!error.interworx_password.length'] = 'The password must be at least 6 characters in length.';
$lang['Interworx.!error.interworx_confirm_password.matches'] = 'The passwords do not match.';
$lang['Interworx.!error.interworx_email.format'] = 'Please enter a valid email address.';


// API Errors
$lang['Interworx.!error.api.internal'] = 'An internal error occurred, or the server did not respond to the request.';
$lang['Interworx.!error.api.no_controller'] = 'The API requires an api_controller to be passed to it.';
$lang['Interworx.!error.api.no_action'] = 'The API requires an action to be passed to it.';
$lang['Interworx.!error.api.soap_error'] = 'SOAP Error. Check to make sure that you have soap installed and that it is configured properly. Also check your HOST and PORT settings are correct.';
$lang['Interworx.!error.api.reported_error'] = 'API Call reported error:';
$lang['Interworx.!error.api.package_types'] = "Package Types don't Match. You cannot change between Reseller and Non-Reseller packages.";
$lang['Interworx.!error.api.create_account.no_array'] = 'createAccount requires an array passed to it.';
$lang['Interworx.!error.api.create_account.missing_fields'] = 'createAccount requires a username, email, password, and domain.';
$lang['Interworx.!error.api.create_account.username_length'] = 'Username must be between 1 and 8 characters long.';
$lang['Interworx.!error.api.create_account.username_characters'] = 'Username cannot have any spaces or special characters.';
$lang['Interworx.!error.api.no_ips'] = "Retrieving IP information, No Available IP's for this Interworx User";
$lang['Interworx.!error.api.no_packages'] = 'Retrieving Package information, No Packages found for this User';
$lang['Interworx.!error.api.duplicate_domain'] = 'Duplicate Domain, That domain already exists. Please choose another domain.';
$lang['Interworx.!error.api.duplicate_username'] = 'Duplicate Username, That username already exists. Please choose another username.';
$lang['Interworx.!error.api.duplicate_email'] = 'Duplicate Email, That email already exists. Please choose another email.';
$lang['Interworx.!error.api.no_domain'] = 'Missing Domain field in API. The Domain field is required';
$lang['Interworx.!error.api.no_reseller_access'] = 'This API call is not available to Resellers. You must be the Administrator.';
$lang['Interworx.!error.api.create_reseller.no_array'] = 'createReseller requires an array passed to it.';
$lang['Interworx.!error.api.create_reseller.missing_fields'] = 'createReseller requires a username, email, and password';
$lang['Interworx.!error.api.no_reseller_id'] = 'this API call requires the reseller_id';
$lang['Interworx.!error.api.no_accounts'] = 'No Accounts found.';
