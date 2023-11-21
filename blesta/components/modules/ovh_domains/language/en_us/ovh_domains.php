<?php
/**
 * en_us language for the OVH Domains module.
 */
// Basics
$lang['OvhDomains.name'] = 'OVH Domains';
$lang['OvhDomains.description'] = '';
$lang['OvhDomains.module_row'] = 'Account';
$lang['OvhDomains.module_row_plural'] = 'Accounts';
$lang['OvhDomains.module_group'] = 'Account Group';


// Module management
$lang['OvhDomains.add_module_row'] = 'Add Account';
$lang['OvhDomains.add_module_group'] = 'Add Account Group';
$lang['OvhDomains.manage.module_rows_title'] = 'Accounts';

$lang['OvhDomains.manage.module_rows_heading.application_key'] = 'Application Key';
$lang['OvhDomains.manage.module_rows_heading.secret_key'] = 'Secret Key';
$lang['OvhDomains.manage.module_rows_heading.consumer_key'] = 'Consumer Key';
$lang['OvhDomains.manage.module_rows_heading.endpoint'] = 'Endpoint';
$lang['OvhDomains.manage.module_rows_heading.options'] = 'Options';
$lang['OvhDomains.manage.module_rows.edit'] = 'Edit';
$lang['OvhDomains.manage.module_rows.delete'] = 'Delete';
$lang['OvhDomains.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this Account';

$lang['OvhDomains.manage.module_rows_no_results'] = 'There are no Accounts.';

$lang['OvhDomains.manage.module_groups_title'] = 'Groups';
$lang['OvhDomains.manage.module_groups_heading.name'] = 'Name';
$lang['OvhDomains.manage.module_groups_heading.module_rows'] = 'Accounts';
$lang['OvhDomains.manage.module_groups_heading.options'] = 'Options';

$lang['OvhDomains.manage.module_groups.edit'] = 'Edit';
$lang['OvhDomains.manage.module_groups.delete'] = 'Delete';
$lang['OvhDomains.manage.module_groups.confirm_delete'] = 'Are you sure you want to delete this Account';

$lang['OvhDomains.manage.module_groups.no_results'] = 'There is no Account Group';


// Add row
$lang['OvhDomains.add_row.box_title'] = 'OVH Domains - Add Account';
$lang['OvhDomains.add_row.add_btn'] = 'Add Account';


// Edit row
$lang['OvhDomains.edit_row.box_title'] = 'OVH Domains - Edit Account';
$lang['OvhDomains.edit_row.edit_btn'] = 'Update Account';


// Row meta
$lang['OvhDomains.row_meta.application_key'] = 'Application Key';
$lang['OvhDomains.row_meta.secret_key'] = 'Secret Key';
$lang['OvhDomains.row_meta.consumer_key'] = 'Consumer Key';
$lang['OvhDomains.row_meta.endpoint'] = 'Endpoint';


// Errors
$lang['OvhDomains.!error.application_key.valid'] = 'Invalid Application Key';
$lang['OvhDomains.!error.secret_key.valid'] = 'Invalid Secret Key';
$lang['OvhDomains.!error.consumer_key.valid'] = 'Invalid Consumer Key';
$lang['OvhDomains.!error.endpoint.valid'] = 'Invalid Endpoint';
$lang['OvhDomains.!error.module_row.missing'] = 'An internal error occurred. The module row is unavailable.';
$lang['OvhDomains.!error.domain.valid'] = 'Invalid domain';
$lang['OvhDomains.!error.epp_code.valid'] = 'EPP Code must be 1 or 0.';
$lang['OvhDomains.!error.ns_count'] = 'At least 2 name servers are required.';
$lang['OvhDomains.!error.ns_valid'] = 'One or more name servers are invalid.';


// WHOIS
$lang['OvhDomains.tab_whois'] = 'WHOIS';
$lang['OvhDomains.tab_whois.heading'] = 'WHOIS';
$lang['OvhDomains.tab_whois.field_submit'] = 'Update WHOIS';


// Nameservers
$lang['OvhDomains.tab_nameservers'] = 'Nameservers';
$lang['OvhDomains.tab_nameservers.heading'] = 'Nameservers';
$lang['OvhDomains.tab_nameservers.field_submit'] = 'Update Nameservers';


// DNS Records
$lang['OvhDomains.tab_dns'] = 'DNS Records';
$lang['OvhDomains.tab_dns.heading'] = 'DNS Records';
$lang['OvhDomains.tab_dns.heading_add'] = 'Add Record';
$lang['OvhDomains.tab_dns.record_type'] = 'Record Type';
$lang['OvhDomains.tab_dns.host'] = 'Host';
$lang['OvhDomains.tab_dns.value'] = 'Value';
$lang['OvhDomains.tab_dns.ttl'] = 'TTL';
$lang['OvhDomains.tab_dns.options'] = 'Options';
$lang['OvhDomains.tab_dns.field_delete'] = 'Delete';
$lang['OvhDomains.tab_dns.field_add'] = 'Add Record';


// Settings
$lang['OvhDomains.tab_settings'] = 'Settings';
$lang['OvhDomains.tab_settings.heading'] = 'Settings';
$lang['OvhDomains.tab_settings.field_submit'] = 'Save Settings';


// Client WHOIS
$lang['OvhDomains.tab_client_whois'] = 'WHOIS';
$lang['OvhDomains.tab_client_whois.heading'] = 'WHOIS';
$lang['OvhDomains.tab_client_whois.field_submit'] = 'Update WHOIS';


// Client Nameservers
$lang['OvhDomains.tab_client_nameservers'] = 'Nameservers';
$lang['OvhDomains.tab_client_nameservers.heading'] = 'Nameservers';
$lang['OvhDomains.tab_client_nameservers.field_submit'] = 'Update Nameservers';


// Client DNS Records
$lang['OvhDomains.tab_client_dns'] = 'DNS Records';
$lang['OvhDomains.tab_client_dns.heading'] = 'DNS Records';
$lang['OvhDomains.tab_client_dns.record_type'] = 'Record Type';
$lang['OvhDomains.tab_client_dns.host'] = 'Host';
$lang['OvhDomains.tab_client_dns.value'] = 'Value';
$lang['OvhDomains.tab_client_dns.ttl'] = 'TTL';
$lang['OvhDomains.tab_client_dns.options'] = 'Options';
$lang['OvhDomains.tab_client_dns.field_delete'] = 'Delete';
$lang['OvhDomains.tab_client_dns.field_add'] = 'Add Record';


// Client Settings
$lang['OvhDomains.tab_client_settings'] = 'Settings';
$lang['OvhDomains.tab_client_settings.heading'] = 'Settings';
$lang['OvhDomains.tab_client_settings.heading_auth_code'] = 'Authorization Code';
$lang['OvhDomains.tab_client_settings.field_registrar_lock'] = 'Registrar Lock';
$lang['OvhDomains.tab_client_settings.field_registrar_lock_yes'] = 'Set the registrar lock. Recommended to prevent unauthorized transfer.';
$lang['OvhDomains.tab_client_settings.field_registrar_lock_no'] = 'Release the registrar lock so the domain can be transferred.';
$lang['OvhDomains.tab_client_settings.text_auth_code'] = 'Use this authorization code to transfer your domain to another provider.';
$lang['OvhDomains.tab_client_settings.field_submit'] = 'Save Settings';


// Transfer fields
$lang['OvhDomains.transfer.domain'] = 'Domain';
$lang['OvhDomains.transfer.auth_info'] = 'Authorization Code';


// Domain fields
$lang['OvhDomains.domain.domain'] = 'Domain';


// Nameserver fields
$lang['OvhDomains.nameserver.ns1'] = 'Nameserver 1';
$lang['OvhDomains.nameserver.ns2'] = 'Nameserver 2';
$lang['OvhDomains.nameserver.ns3'] = 'Nameserver 3';
$lang['OvhDomains.nameserver.ns4'] = 'Nameserver 4';
$lang['OvhDomains.nameserver.ns5'] = 'Nameserver 5';


// Contact fields
$lang['OvhDomains.contact.first_name'] = 'First Name';
$lang['OvhDomains.contact.last_name'] = 'Last Name';
$lang['OvhDomains.contact.email'] = 'E-mail Address';
$lang['OvhDomains.contact.address1'] = 'Address 1';
$lang['OvhDomains.contact.address2'] = 'Address 2';
$lang['OvhDomains.contact.city'] = 'City';
$lang['OvhDomains.contact.state'] = 'State';
$lang['OvhDomains.contact.zip'] = 'Zip Code';
$lang['OvhDomains.contact.country'] = 'Country';
$lang['OvhDomains.contact.phone'] = 'Phone';


// Service Fields
$lang['OvhDomains.service_fields.domain'] = 'Domain';


// Package Fields
$lang['OvhDomains.package_fields.epp_code'] = 'EPP Code';
$lang['OvhDomains.package_fields.ns1'] = 'Nameserver 1';
$lang['OvhDomains.package_fields.ns2'] = 'Nameserver 2';
$lang['OvhDomains.package_fields.ns3'] = 'Nameserver 3';
$lang['OvhDomains.package_fields.ns4'] = 'Nameserver 4';
$lang['OvhDomains.package_fields.ns5'] = 'Nameserver 5';

$lang['OvhDomains.package_field.tooltip.epp_code'] = 'Whether to allow users to request an EPP Code through the Blesta service interface.';
$lang['OvhDomains.package_fields.tld_options'] = 'TLDs';
