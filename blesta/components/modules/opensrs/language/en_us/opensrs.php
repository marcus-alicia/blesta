<?php
// Basics
$lang['Opensrs.name'] = 'OpenSRS';
$lang['Opensrs.description'] = 'OpenSRS is a wholesale domain registrar that offers a premium, white-label platform that connects reseller partners with the solutions they need.';
$lang['Opensrs.module_row'] = 'Account';
$lang['Opensrs.module_row_plural'] = 'Accounts';


// Module management
$lang['Opensrs.add_module_row'] = 'Add Account';
$lang['Opensrs.manage.module_rows_title'] = 'Accounts';
$lang['Opensrs.manage.module_rows_heading.user'] = 'User';
$lang['Opensrs.manage.module_rows_heading.key'] = 'API Key';
$lang['Opensrs.manage.module_rows_heading.sandbox'] = 'Sandbox';
$lang['Opensrs.manage.module_rows_heading.options'] = 'Options';
$lang['Opensrs.manage.module_rows.edit'] = 'Edit';
$lang['Opensrs.manage.module_rows.delete'] = 'Delete';
$lang['Opensrs.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this account?';
$lang['Opensrs.manage.module_rows_no_results'] = 'There are no accounts.';

// Row Meta
$lang['Opensrs.row_meta.user'] = 'User';
$lang['Opensrs.row_meta.key'] = 'Key';
$lang['Opensrs.row_meta.sandbox'] = 'Sandbox';
$lang['Opensrs.row_meta.sandbox_true'] = 'Yes';
$lang['Opensrs.row_meta.sandbox_false'] = 'No';

// Add row
$lang['Opensrs.add_row.box_title'] = 'Add OpenSRS Account';
$lang['Opensrs.add_row.basic_title'] = 'Basic Settings';
$lang['Opensrs.add_row.add_btn'] = 'Add Account';

// Edit row
$lang['Opensrs.edit_row.box_title'] = 'Edit OpenSRS Account';
$lang['Opensrs.edit_row.basic_title'] = 'Basic Settings';
$lang['Opensrs.edit_row.add_btn'] = 'Update Account';

// Package fields
$lang['Opensrs.package_fields.type'] = 'Type';
$lang['Opensrs.package_fields.type_domain'] = 'Domain Registration';
$lang['Opensrs.package_fields.type_ssl'] = 'SSL Certificate';
$lang['Opensrs.package_fields.tld_options'] = 'TLDs';
$lang['Opensrs.package_fields.ns1'] = 'Name Server 1';
$lang['Opensrs.package_fields.ns2'] = 'Name Server 2';
$lang['Opensrs.package_fields.ns3'] = 'Name Server 3';
$lang['Opensrs.package_fields.ns4'] = 'Name Server 4';
$lang['Opensrs.package_fields.ns5'] = 'Name Server 5';

// Service management
$lang['Opensrs.tab_whois.title'] = 'Whois';
$lang['Opensrs.tab_whois.section_owner'] = 'Registrant';
$lang['Opensrs.tab_whois.section_admin'] = 'Administrative';
$lang['Opensrs.tab_whois.section_tech'] = 'Technical';
$lang['Opensrs.tab_whois.section_billing'] = 'Billing';
$lang['Opensrs.tab_whois.field_submit'] = 'Update Whois';

$lang['Opensrs.tab_nameservers.title'] = 'Name Servers';
$lang['Opensrs.tab_nameserver.field_ns'] = 'Name Server %1$s'; // %1$s is the name server number
$lang['Opensrs.tab_nameservers.field_submit'] = 'Update Name Servers';

$lang['Opensrs.tab_settings.title'] = 'Settings';
$lang['Opensrs.tab_settings.field_registrar_lock'] = 'Registrar Lock';
$lang['Opensrs.tab_settings.field_registrar_lock_yes'] = 'Set the registrar lock. Recommended to prevent unauthorized transfer.';
$lang['Opensrs.tab_settings.field_registrar_lock_no'] = 'Release the registrar lock so the domain can be transferred.';
$lang['Opensrs.tab_settings.field_whois_privacy'] = 'WHOIS Privacy';
$lang['Opensrs.tab_settings.field_whois_privacy_state_yes'] = 'Enabled';
$lang['Opensrs.tab_settings.field_whois_privacy_state_no'] = 'Disabled';
$lang['Opensrs.tab_settings.field_epp_code'] = 'EPP Code';
$lang['Opensrs.tab_settings.field_request_epp'] = 'Request EPP Code/Transfer Key';
$lang['Opensrs.tab_settings.field_submit'] = 'Update Settings';

// Errors
$lang['Opensrs.!error.user.valid'] = 'Please enter a user';
$lang['Opensrs.!error.key.valid'] = 'Please enter a key';
$lang['Opensrs.!error.key.valid_connection'] = 'The user and key combination appear to be invalid, or your Opensrs account may not be configured to allow API access.';
$lang['Opensrs.!error.registrant_type.format'] = 'Please select a registrant type';
$lang['Opensrs.!error.registrant_vat_id.format'] = 'Please enter a VAT ID';
$lang['Opensrs.!error.siren_siret.format'] = 'Please enter a SIREN/SIRET Number';
$lang['Opensrs.!error.trademark_number.format'] = 'Please enter a Trademark Number';

// Domain Transfer Fields
$lang['Opensrs.transfer.domain'] = 'Domain Name';
$lang['Opensrs.transfer.auth_info'] = 'EPP Code';

// Domain Fields
$lang['Opensrs.domain.domain'] = 'Domain Name';

// Nameserver Fields
$lang['Opensrs.nameserver.ns1'] = 'Name Server 1';
$lang['Opensrs.nameserver.ns2'] = 'Name Server 2';
$lang['Opensrs.nameserver.ns3'] = 'Name Server 3';
$lang['Opensrs.nameserver.ns4'] = 'Name Server 4';


// Whois Fields
$lang['Opensrs.whois.owner.first_name'] = 'First Name';
$lang['Opensrs.whois.owner.last_name'] = 'Last Name';
$lang['Opensrs.whois.owner.org_name'] = 'Company';
$lang['Opensrs.whois.owner.address1'] = 'Address 1';
$lang['Opensrs.whois.owner.address2'] = 'Address 2';
$lang['Opensrs.whois.owner.city'] = 'City';
$lang['Opensrs.whois.owner.state'] = 'State/Province';
$lang['Opensrs.whois.owner.postal_code'] = 'Postal Code';
$lang['Opensrs.whois.owner.country'] = 'Country';
$lang['Opensrs.whois.owner.phone'] = 'Phone';
$lang['Opensrs.whois.owner.email'] = 'Email';

$lang['Opensrs.whois.tech.first_name'] = 'First Name';
$lang['Opensrs.whois.tech.last_name'] = 'Last Name';
$lang['Opensrs.whois.tech.org_name'] = 'Company';
$lang['Opensrs.whois.tech.address1'] = 'Address 1';
$lang['Opensrs.whois.tech.address2'] = 'Address 2';
$lang['Opensrs.whois.tech.city'] = 'City';
$lang['Opensrs.whois.tech.state'] = 'State/Province';
$lang['Opensrs.whois.tech.postal_code'] = 'Postal Code';
$lang['Opensrs.whois.tech.country'] = 'Country';
$lang['Opensrs.whois.tech.phone'] = 'Phone';
$lang['Opensrs.whois.tech.email'] = 'Email';

$lang['Opensrs.whois.admin.first_name'] = 'First Name';
$lang['Opensrs.whois.admin.last_name'] = 'Last Name';
$lang['Opensrs.whois.admin.org_name'] = 'Company';
$lang['Opensrs.whois.admin.address1'] = 'Address 1';
$lang['Opensrs.whois.admin.address2'] = 'Address 2';
$lang['Opensrs.whois.admin.city'] = 'City';
$lang['Opensrs.whois.admin.state'] = 'State/Province';
$lang['Opensrs.whois.admin.postal_code'] = 'Postal Code';
$lang['Opensrs.whois.admin.country'] = 'Country';
$lang['Opensrs.whois.admin.phone'] = 'Phone';
$lang['Opensrs.whois.admin.email'] = 'Email';

$lang['Opensrs.whois.billing.first_name'] = 'First Name';
$lang['Opensrs.whois.billing.last_name'] = 'Last Name';
$lang['Opensrs.whois.billing.org_name'] = 'Company';
$lang['Opensrs.whois.billing.address1'] = 'Address 1';
$lang['Opensrs.whois.billing.address2'] = 'Address 2';
$lang['Opensrs.whois.billing.city'] = 'City';
$lang['Opensrs.whois.billing.state'] = 'State/Province';
$lang['Opensrs.whois.billing.postal_code'] = 'Postal Code';
$lang['Opensrs.whois.billing.country'] = 'Country';
$lang['Opensrs.whois.billing.phone'] = 'Phone';
$lang['Opensrs.whois.billing.email'] = 'Email';

// .US domain fields
$lang['Opensrs.domain.category'] = 'Registrant Type';
$lang['Opensrs.domain.category.c11'] = 'US citizen';
$lang['Opensrs.domain.category.c12'] = 'Permanent resident of the US';
$lang['Opensrs.domain.category.c21'] = 'US entity or organization';
$lang['Opensrs.domain.category.c31'] = 'Foreign organization';
$lang['Opensrs.domain.category.c32'] = 'Foreign organization with an office in the US';
$lang['Opensrs.domain.app_purpose'] = 'Purpose';
$lang['Opensrs.domain.app_purpose.p1'] = 'Business';
$lang['Opensrs.domain.app_purpose.p2'] = 'Non-profit';
$lang['Opensrs.domain.app_purpose.p3'] = 'Personal';
$lang['Opensrs.domain.app_purpose.p4'] = 'Educational';
$lang['Opensrs.domain.app_purpose.p5'] = 'Governmental';

// .EU domain fields
$lang['Opensrs.domain.owner_confirm_address'] = 'Owner E-Mail Address';

// .CA domain fields
$lang['Opensrs.domain.legal_type'] = 'Legal Type';
$lang['Opensrs.domain.legal_type.cco'] = 'Corporation';
$lang['Opensrs.domain.legal_type.cct'] = 'Canadian citizen';
$lang['Opensrs.domain.legal_type.res'] = 'Canadian resident';
$lang['Opensrs.domain.legal_type.gov'] = 'Government entity';
$lang['Opensrs.domain.legal_type.edu'] = 'Educational';
$lang['Opensrs.domain.legal_type.ass'] = 'Unincorporated Association';
$lang['Opensrs.domain.legal_type.hop'] = 'Hospital';
$lang['Opensrs.domain.legal_type.prt'] = 'Partnership';
$lang['Opensrs.domain.legal_type.tdm'] = 'Trade-mark';
$lang['Opensrs.domain.legal_type.trd'] = 'Trade Union';
$lang['Opensrs.domain.legal_type.plt'] = 'Political Party';
$lang['Opensrs.domain.legal_type.lam'] = 'Libraries, Archives and Museums';
$lang['Opensrs.domain.legal_type.trs'] = 'Trust';
$lang['Opensrs.domain.legal_type.abo'] = 'Aboriginal Peoples';
$lang['Opensrs.domain.legal_type.inb'] = 'Indian Band';
$lang['Opensrs.domain.legal_type.lgr'] = 'Legal Representative';
$lang['Opensrs.domain.legal_type.omk'] = 'Official Mark';
$lang['Opensrs.domain.legal_type.maj'] = 'The Queen';

// .UK domain fields
$lang['Opensrs.domain.registrant_type'] = 'Legal Type';
$lang['Opensrs.domain.registrant_type.ind'] = 'UK individual';
$lang['Opensrs.domain.registrant_type.find'] = 'Non-UK individual';
$lang['Opensrs.domain.registrant_type.ltd'] = 'UK Limited Company';
$lang['Opensrs.domain.registrant_type.plc'] = 'UK Public Limited Company';
$lang['Opensrs.domain.registrant_type.ptnr'] = 'UK Partnership';
$lang['Opensrs.domain.registrant_type.llp'] = 'UK Limited Liability Partnership';
$lang['Opensrs.domain.registrant_type.ip'] = 'UK Industrial/Provident Registered Company';
$lang['Opensrs.domain.registrant_type.stra'] = 'UK Sole Trader';
$lang['Opensrs.domain.registrant_type.sch'] = 'UK School';
$lang['Opensrs.domain.registrant_type.rchar'] = 'UK Registered Charity';
$lang['Opensrs.domain.registrant_type.gov'] = 'UK Government Body';
$lang['Opensrs.domain.registrant_type.other'] = 'UK Entity (other)';
$lang['Opensrs.domain.registrant_type.crc'] = 'UK Corporation by Royal Charter';
$lang['Opensrs.domain.registrant_type.fcorp'] = 'Foreign Organization';
$lang['Opensrs.domain.registrant_type.stat'] = 'UK Statutory Body FIND';
$lang['Opensrs.domain.registrant_type.fother'] = 'Other Foreign Organizations';
$lang['Opensrs.domain.registration_number'] = 'Company ID Number';
$lang['Opensrs.domain.trading_name'] = 'Registrant Name';

// .ASIA domain fields
$lang['Opensrs.domain.legal_entity_type'] = 'Legal Type';
$lang['Opensrs.domain.legal_entity_type.corporation'] = 'Corporations or Companies';
$lang['Opensrs.domain.legal_entity_type.cooperative'] = 'Cooperatives';
$lang['Opensrs.domain.legal_entity_type.partnership'] = 'Partnerships or Collectives';
$lang['Opensrs.domain.legal_entity_type.government'] = 'Government Bodies';
$lang['Opensrs.domain.legal_entity_type.politicalParty'] = 'Political parties or Trade Unions';
$lang['Opensrs.domain.legal_entity_type.society'] = 'Trusts, Estates, Associations or Societies';
$lang['Opensrs.domain.legal_entity_type.institution'] = 'Institutions';
$lang['Opensrs.domain.legal_entity_type.naturalPerson'] = 'Natural Persons';
$lang['Opensrs.domain.id_type'] = 'Form of Identity';
$lang['Opensrs.domain.id_type.certificate'] = 'Certificate of Incorporation';
$lang['Opensrs.domain.id_type.legislation'] = 'Charter';
$lang['Opensrs.domain.id_type.societyRegistry'] = 'Societies Registry';
$lang['Opensrs.domain.id_type.politicalPartyRegistry'] = 'Political Party Registry';
$lang['Opensrs.domain.id_type.passport'] = 'Passport/ Citizenship ID';
$lang['Opensrs.domain.id_number'] = 'Identity Number';

// .FR domain fields
$lang['Opensrs.domain.registrant_type'] = 'Legal Type';
$lang['Opensrs.domain.registrant_type.individual'] = 'Individual';
$lang['Opensrs.domain.registrant_type.organization'] = 'Company';
$lang['Opensrs.domain.registrant_vat_id'] = 'VAT ID';
$lang['Opensrs.domain.siren_siret'] = 'SIREN/SIRET Number';
$lang['Opensrs.domain.trademark_number'] = 'Trademark Number';

// .IT domain fields
$lang['Opensrs.domain.reg_code'] = 'Registration Code';
$lang['Opensrs.domain.entity_type'] = 'Entity Type';
$lang['Opensrs.domain.entity_type.1'] = 'Person';
$lang['Opensrs.domain.entity_type.2'] = 'Company';

// .LAW domain fields
$lang['Opensrs.domain.qli_accreditation_id'] = 'Accreditation ID';
$lang['Opensrs.domain.qli_accreditation_body'] = 'Accreditation Body';
$lang['Opensrs.domain.qli_jurisdiction_country'] = 'Accreditation Country';
$lang['Opensrs.domain.qli_jurisdiction_state'] = 'Accreditation State';
$lang['Opensrs.domain.qli_accreditation_year'] = 'Accreditation Year';

// .XXX domain fields
$lang['Opensrs.domain.icm_membership_id'] = 'ICM Membership ID';
