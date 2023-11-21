<?php
// Actions
$lang['Solusvm.!actions.boot'] = 'Boot';
$lang['Solusvm.!actions.reboot'] = 'Reboot';
$lang['Solusvm.!actions.shutdown'] = 'Shutdown';
$lang['Solusvm.!actions.reinstall'] = 'Reinstall';
$lang['Solusvm.!actions.hostname'] = 'Change Hostname';
$lang['Solusvm.!actions.password'] = 'Change Password';


// Errors
$lang['SolusVM.!error.simplexml_required'] = 'The simplexml extension is required for this module.';

$lang['Solusvm.!error.server_name.empty'] = 'Please enter a server label.';
$lang['Solusvm.!error.user_id.empty'] = 'Please enter a user ID.';
$lang['Solusvm.!error.key.empty'] = 'Please enter a key.';
$lang['Solusvm.!error.host.format'] = 'The hostname appears to be invalid.';
$lang['Solusvm.!error.port.format'] = 'Please enter a valid port number.';

$lang['Solusvm.!error.meta[type].valid'] = 'Please select a valid virtualization type.';
$lang['Solusvm.!error.meta[nodes].empty'] = 'Please select at least one node or node group.';
$lang['Solusvm.!error.meta[plan].empty'] = 'Please select a plan.';
$lang['Solusvm.!error.meta[set_template].format'] = 'Please set whether to select a template or to allow clients to set a template.';
$lang['Solusvm.!error.meta[template].empty'] = 'Please select a template.';
$lang['Solusvm.!error.meta[total_base_ip_addresses].format'] = 'Please enter only digits for the IP address quantity, with a value of 1 or more.';

$lang['Solusvm.!error.api.internal'] = 'An internal error occurred, or the server did not respond to the request.';

$lang['Solusvm.!error.solusvm_hostname.format'] = 'Please enter a valid fully qualified domain name (e.g. host.domain.com) for the hostname.';
$lang['Solusvm.!error.solusvm_template.valid'] = 'Please select a valid template.';

$lang['Solusvm.!error.create_client.failed'] = 'An internal error occurred and the client account could not be created.';

$lang['Solusvm.!error.api.template.valid'] = 'The selected template is invalid.';
$lang['Solusvm.!error.api.confirm.valid'] = 'You must acknowledge that you understand the reinstall action in order to perform the template reinstallation.';

$lang['Solusvm.!error.solusvm_root_password.length'] = 'The root password must be at least 6 characters in length.';
$lang['Solusvm.!error.solusvm_root_password.matches'] = 'The root passwords do not match.';

$lang['Solusvm.!error.solusvm_vserver_id.format'] = 'The Virtual Server ID must be an integer.';

$lang['Solusvm.!error.configoptions[customextraip].valid'] = 'The number of extra IPs may not be decreased without specifying which IPs to remove.';


// Success
$lang['Solusvm.!success.boot'] = 'The server is now booting.';
$lang['Solusvm.!success.reboot'] = 'The server is now rebooting.';
$lang['Solusvm.!success.shutdown'] = 'The server is now shutting down.';
$lang['Solusvm.!success.password'] = 'The password was changed successfully.';
$lang['Solusvm.!success.hostname'] = 'The hostname was changed successfully.';
$lang['Solusvm.!success.reinstall'] = 'The server is now reinstalling.';


// Common
$lang['Solusvm.please_select'] = '-- Please Select --';
$lang['Solusvm.!bytes.value'] = '%1$s%2$s'; // %1$s is a number value, %2$s is the unit of that value (i.e., one of B, KB, MB, GB)
$lang['Solusvm.!percent.used'] = '%1$s%'; // %1$s is a percentage value

// Basics
$lang['Solusvm.name'] = 'SolusVM';
$lang['Solusvm.description'] = 'Solus Virtual Manager (SolusVM) is a powerful GUI based VPS management system with full OpenVZ, Linux KVM, Xen Paravirtualization and Xen HVM support.';
$lang['Solusvm.module_row'] = 'SolusVM Master Server';
$lang['Solusvm.module_row_plural'] = 'Servers';
$lang['Solusvm.module_group'] = 'SolusVM Master Group';


// Module management
$lang['Solusvm.add_module_row'] = 'Add Server';
$lang['Solusvm.add_module_group'] = 'Add Server Group';
$lang['Solusvm.manage.module_rows_title'] = 'SolusVM Master Servers';
$lang['Solusvm.manage.module_groups_title'] = 'SolusVM Master Server Groups';
$lang['Solusvm.manage.module_rows_heading.server_label'] = 'Server Label';
$lang['Solusvm.manage.module_rows_heading.host'] = 'Hostname';
$lang['Solusvm.manage.module_rows_heading.options'] = 'Options';
$lang['Solusvm.manage.module_groups_heading.name'] = 'Group Name';
$lang['Solusvm.manage.module_groups_heading.servers'] = 'Server Count';
$lang['Solusvm.manage.module_groups_heading.options'] = 'Options';
$lang['Solusvm.manage.module_rows.edit'] = 'Edit';
$lang['Solusvm.manage.module_groups.edit'] = 'Edit';
$lang['Solusvm.manage.module_rows.delete'] = 'Delete';
$lang['Solusvm.manage.module_groups.delete'] = 'Delete';
$lang['Solusvm.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this server?';
$lang['Solusvm.manage.module_groups.confirm_delete'] = 'Are you sure you want to delete this server group?';
$lang['Solusvm.manage.module_rows_no_results'] = 'There are no servers.';
$lang['Solusvm.manage.module_groups_no_results'] = 'There are no server groups.';

$lang['Solusvm.order_options.first'] = 'First non-full server';


// Module row meta data
$lang['Solusvm.row_meta.server_name'] = 'Server Label';
$lang['Solusvm.row_meta.user_id'] = 'User ID';
$lang['Solusvm.row_meta.key'] = 'Key';
$lang['Solusvm.row_meta.host'] = 'Hostname';
$lang['Solusvm.row_meta.port'] = 'SSL Port Number';
$lang['Solusvm.row_meta.default_port'] = '5656';


// Server types
$lang['Solusvm.types.openvz'] = 'OpenVZ';
$lang['Solusvm.types.xen'] = 'Xen';
$lang['Solusvm.types.xen_hvm'] = 'Xen HVM';
$lang['Solusvm.types.kvm'] = 'KVM';


// Add module row
$lang['Solusvm.add_row.box_title'] = 'Add SolusVM Server';
$lang['Solusvm.add_row.basic_title'] = 'Basic Settings';
$lang['Solusvm.add_row.add_btn'] = 'Add Server';


// Edit module row
$lang['Solusvm.edit_row.box_title'] = 'Edit SolusVM Server';
$lang['Solusvm.edit_row.basic_title'] = 'Basic Settings';
$lang['Solusvm.edit_row.add_btn'] = 'Update Server';


// Package fields
$lang['Solusvm.package_fields.total_base_ip_addresses'] = 'Base IP Address Quantity';
$lang['Solusvm.package_fields.tooltip.total_base_ip_addresses'] = 'Enter the total number of IP addresses to include with new services. There must be at least one, and the IPs cannot be removed.';

$lang['Solusvm.package_fields.type'] = 'Type';
$lang['Solusvm.package_fields.template'] = 'Template';
$lang['Solusvm.package_fields.admin_set_template'] = 'Select a template';
$lang['Solusvm.package_fields.client_set_template'] = 'Let client set template';
$lang['Solusvm.package_fields.plan'] = 'Plan';

$lang['Solusvm.package_fields.assigned_nodes'] = 'Assigned Nodes';
$lang['Solusvm.package_fields.available_nodes'] = 'Available Nodes';

$lang['Solusvm.package_fields.set_node'] = 'Assign a set of nodes';
$lang['Solusvm.package_fields.set_node_group'] = 'Assign a node group';
$lang['Solusvm.package_fields.node_group'] = 'Node Group';


// Service fields
$lang['Solusvm.service_field.solusvm_hostname'] = 'Hostname';
$lang['Solusvm.service_field.solusvm_template'] = 'Template';
$lang['Solusvm.service_field.solusvm_vserver_id'] = 'Virtual Server ID';
$lang['Solusvm.service_field.tooltip.solusvm_vserver_id'] = 'The Virtual Server ID specifies the VPS from SolusVM to which this service will be attached. Changing this value will only affect this service locally.';


// Service Info fields
$lang['Solusvm.service_info.solusvm_main_ip_address'] = 'Primary IP Address';


// Tabs
$lang['Solusvm.tab_actions'] = 'Server Actions';
$lang['Solusvm.tab_stats'] = 'Stats';
$lang['Solusvm.tab_console'] = 'Console';
$lang['Solusvm.tab_ips'] = 'IP Addresses';


// Actions Tab
$lang['Solusvm.tab_actions.heading_actions'] = 'Actions';

$lang['Solusvm.tab_actions.status_online'] = 'Online';
$lang['Solusvm.tab_actions.status_offline'] = 'Offline';
$lang['Solusvm.tab_actions.status_disabled'] = 'Disabled';
$lang['Solusvm.tab_actions.server_status'] = 'Server Status';
$lang['Solusvm.tab_actions.node'] = 'Node: %1$s'; // %1$s is the name of the node

$lang['Solusvm.tab_actions.heading_reinstall'] = 'Reinstall';
$lang['Solusvm.tab_actions.field_template'] = 'Template';
$lang['Solusvm.tab_actions.field_confirm'] = 'I understand that by reinstalling, all data on the server will be permanently deleted, and the selected template will be installed.';
$lang['Solusvm.tab_actions.field_reinstall_submit'] = 'Reinstall';

$lang['Solusvm.tab_actions.heading_hostname'] = 'Change Hostname';
$lang['Solusvm.tab_actions.text_hostname_reboot'] = 'A change to the hostname will only take effect after the server has been rebooted.';
$lang['Solusvm.tab_actions.field_hostname'] = 'Hostname';
$lang['Solusvm.tab_actions.field_hostname_submit'] = 'Change Hostname';

$lang['Solusvm.tab_actions.heading_password'] = 'Change Password';
$lang['Solusvm.tab_actions.field_password'] = 'New Root Password';
$lang['Solusvm.tab_actions.field_confirm_password'] = 'Confirm Password';
$lang['Solusvm.tab_actions.field_password_submit'] = 'Change Password';
$lang['Solusvm.tab_actions.text_generate_password'] = 'Generate Password';


// Client Actions Tab
$lang['Solusvm.tab_client_actions.heading_actions'] = 'Actions';
$lang['Solusvm.tab_client_actions.heading_server_status'] = 'Server Status';
$lang['Solusvm.tab_client_actions.heading_node'] = 'Node';

$lang['Solusvm.tab_client_actions.status_online'] = 'Online';
$lang['Solusvm.tab_client_actions.status_offline'] = 'Offline';
$lang['Solusvm.tab_client_actions.status_disabled'] = 'Disabled';

$lang['Solusvm.tab_client_actions.heading_reinstall'] = 'Reinstall';
$lang['Solusvm.tab_client_actions.field_template'] = 'Template';
$lang['Solusvm.tab_client_actions.field_confirm'] = 'I understand that by reinstalling, all data on the server will be permanently deleted, and the selected template will be installed.';
$lang['Solusvm.tab_client_actions.field_reinstall_submit'] = 'Reinstall';

$lang['Solusvm.tab_client_actions.heading_hostname'] = 'Change Hostname';
$lang['Solusvm.tab_client_actions.text_hostname_reboot'] = 'A change to the hostname will only take effect after the server has been rebooted.';
$lang['Solusvm.tab_client_actions.field_hostname'] = 'Hostname';
$lang['Solusvm.tab_client_actions.field_hostname_submit'] = 'Change Hostname';

$lang['Solusvm.tab_client_actions.heading_password'] = 'Change Password';
$lang['Solusvm.tab_client_actions.field_password'] = 'New Root Password';
$lang['Solusvm.tab_client_actions.field_confirm_password'] = 'Confirm Password';
$lang['Solusvm.tab_client_actions.field_password_submit'] = 'Change Password';
$lang['Solusvm.tab_client_actions.text_generate_password'] = 'Generate Password';


// Stats Tab
$lang['Solusvm.tab_stats.heading_stats'] = 'Statistics';

$lang['Solusvm.tab_stats.bandwidth'] = 'Bandwidth:';
$lang['Solusvm.tab_stats.bandwidth_stats'] = '%1$s/%2$s'; // %1$s is the bandwidth used, %2$s is the total bandwidth available
$lang['Solusvm.tab_stats.bandwidth_percent_available'] = '(%1$s%%)'; // %1$s is the percentage of bandwidth used. You MUST use two % signs to represent a single percent (i.e. %%)
$lang['Solusvm.tab_stats.memory'] = 'Memory:';
$lang['Solusvm.tab_stats.memory_stats'] = '%1$s/%2$s'; // %1$s is the memory used, %2$s is the total memory available
$lang['Solusvm.tab_stats.memory_percent_available'] = '(%1$s%%)'; // %1$s is the percentage of memory used. You MUST use two % signs to represent a single percent (i.e. %%)
$lang['Solusvm.tab_stats.space'] = 'Disk Space:';
$lang['Solusvm.tab_stats.space_stats'] = '%1$s/%2$s'; // %1$s is the hard disk space used, %2$s is the total hard disk space available
$lang['Solusvm.tab_stats.space_percent_available'] = '(%1$s%%)'; // %1$s is the percentage of hard disk space used. You MUST use two % signs to represent a single percent (i.e. %%)
$lang['Solusvm.tab_status.no_results'] = 'Statistics are not currently available.';

$lang['Solusvm.tab_stats.heading_graphs'] = 'Graphs';


// Client Stats Tab
$lang['Solusvm.tab_client_stats.heading_stats'] = 'Statistics';

$lang['Solusvm.tab_client_stats.bandwidth'] = 'Bandwidth';
$lang['Solusvm.tab_client_stats.memory'] = 'Memory';
$lang['Solusvm.tab_client_stats.space'] = 'Disk Space';

$lang['Solusvm.tab_client_stats.usage'] = '(%1$s/%2$s)'; // %1$s is the amount of resources used, %2$s is the amount of total resources available

$lang['Solusvm.tab_client_stats.heading_graphs'] = 'Graphs';


// Console Tab
$lang['Solusvm.tab_console.heading_console'] = 'Console';
$lang['Solusvm.tab_console.console_username'] = 'Console Username:';
$lang['Solusvm.tab_console.console_password'] = 'Console Password:';

$lang['Solusvm.tab_console.vnc_ip'] = 'VNC Host:';
$lang['Solusvm.tab_console.vnc_port'] = 'VNC Port:';
$lang['Solusvm.tab_console.vnc_password'] = 'VNC Password:';


// Client Console Tab
$lang['Solusvm.tab_client_console.heading_console'] = 'Console';
$lang['Solusvm.tab_client_console.console_username'] = 'Console Username';
$lang['Solusvm.tab_client_console.console_password'] = 'Console Password';

$lang['Solusvm.tab_client_console.vnc_password'] = 'VNC Password';


// IPs Tab
$lang['Solusvm.tab_ips.heading_ips'] = 'IP Addresses';
$lang['Solusvm.tab_ips.primary_ip'] = 'Primary IP Address: %1$s'; // %1$s is the IP address
$lang['Solusvm.tab_ips.heading_extra_ips'] = 'Extra IP Addresses';
$lang['Solusvm.tab_ips.heading_ip'] = 'IP Address';
$lang['Solusvm.tab_ips.heading_options'] = 'Options';
$lang['Solusvm.tab_ips.option_remove'] = 'Remove IP';
$lang['Solusvm.tab_ips.confirm_remove'] = 'Are you sure you want to permanently remove this IP address?';


// Client IPs Tab
$lang['Solusvm.tab_client_ips.heading_extra'] = 'Extra IP Addresses';
$lang['Solusvm.tab_client_ips.primary_ip'] = 'Primary IP Address';
$lang['Solusvm.tab_client_ips.heading_remove_ip'] = 'Remove IP %1$s'; // %1$s is the IP address
$lang['Solusvm.tab_client_ips.confirm_remove_ip'] = 'Are you sure you want to permanently remove this IP Address?';
$lang['Solusvm.tab_client_ips.remove_ip'] = 'Remove IP';
$lang['Solusvm.tab_client_ips.cancel'] = 'Cancel';
