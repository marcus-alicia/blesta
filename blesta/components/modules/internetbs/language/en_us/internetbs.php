<?php
/**
 * American English (en_us) language for the Internet.bs module.
 */
// Basics
$lang['Internetbs.name'] = 'Internet.bs';
$lang['Internetbs.description'] = 'Internet.bs provides domain name registration and transfers for ccTLDs and gTLDs.';
$lang['Internetbs.module_row'] = 'Account';
$lang['Internetbs.module_row_plural'] = 'Accounts';
$lang['Internetbs.module_group'] = 'Account Group';


// Module management
$lang['Internetbs.add_module_row'] = 'Add Account';
$lang['Internetbs.add_module_group'] = 'Add Account Group';
$lang['Internetbs.manage.module_rows_title'] = 'Accounts';

$lang['Internetbs.manage.module_rows_heading.api_key'] = 'API Key';
$lang['Internetbs.manage.module_rows_heading.options'] = 'Options';
$lang['Internetbs.manage.module_rows.edit'] = 'Edit';
$lang['Internetbs.manage.module_rows.delete'] = 'Delete';
$lang['Internetbs.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this Account';

$lang['Internetbs.manage.module_rows_no_results'] = 'There are no Accounts.';

$lang['Internetbs.manage.module_groups_title'] = 'Groups';
$lang['Internetbs.manage.module_groups_heading.name'] = 'Name';
$lang['Internetbs.manage.module_groups_heading.module_rows'] = 'Accounts';
$lang['Internetbs.manage.module_groups_heading.options'] = 'Options';

$lang['Internetbs.manage.module_groups.edit'] = 'Edit';
$lang['Internetbs.manage.module_groups.delete'] = 'Delete';
$lang['Internetbs.manage.module_groups.confirm_delete'] = 'Are you sure you want to delete this Account Group';

$lang['Internetbs.manage.module_groups.no_results'] = 'There are no Account Groups';

$lang['Internetbs.order_options.roundrobin'] = 'Evenly Distribute Among Servers';
$lang['Internetbs.order_options.first'] = 'First Non-full Server';


// Add row
$lang['Internetbs.add_row.box_title'] = 'Internet.bs - Add Account';
$lang['Internetbs.add_row.add_btn'] = 'Add Account';


// Edit row
$lang['Internetbs.edit_row.box_title'] = 'Internet.bs - Edit Account';
$lang['Internetbs.edit_row.edit_btn'] = 'Update Account';


// Row meta
$lang['Internetbs.row_meta.api_key'] = 'API Key';
$lang['Internetbs.row_meta.password'] = 'Password';
$lang['Internetbs.row_meta.sandbox'] = 'Sandbox';


// Errors
$lang['Internetbs.!error.api_key.valid'] = 'Invalid API Key';
$lang['Internetbs.!error.password.valid'] = 'Invalid Password';
$lang['Internetbs.!error.password.valid_connection'] = 'A connection to the Internet.bs API could not be established. Please check to ensure that the API Key and Password are correct.';
$lang['Internetbs.!error.sandbox.format'] = 'Sandbox must be either "true" or "false".';
$lang['Internetbs.!error.module_row.missing'] = 'An internal error occurred. The module row is unavailable.';
$lang['Internetbs.!error.ns1.valid'] = 'Invalid Name Server 1';
$lang['Internetbs.!error.ns2.valid'] = 'Invalid Name Server 2';
$lang['Internetbs.!error.ns3.valid'] = 'Invalid Name Server 3';
$lang['Internetbs.!error.ns4.valid'] = 'Invalid Name Server 4';
$lang['Internetbs.!error.ns5.valid'] = 'Invalid Name Server 5';


// WHOIS
$lang['Internetbs.tab_whois'] = 'Whois';
$lang['Internetbs.tab_whois.section_Registrant'] = 'Registrant';
$lang['Internetbs.tab_whois.section_Admin'] = 'Admin';
$lang['Internetbs.tab_whois.section_Technical'] = 'Technical';
$lang['Internetbs.tab_whois.section_Billing'] = 'Billing';
$lang['Internetbs.tab_whois.field_submit'] = 'Update Whois';


// Client Whois
$lang['Internetbs.tab_client_whois'] = 'Whois';
$lang['Internetbs.tab_client_whois.section_Registrant'] = 'Registrant';
$lang['Internetbs.tab_client_whois.section_Admin'] = 'Admin';
$lang['Internetbs.tab_client_whois.section_Technical'] = 'Technical';
$lang['Internetbs.tab_client_whois.section_Billing'] = 'Billing';
$lang['Internetbs.tab_client_whois.field_submit'] = 'Update Whois';


// Nameservers
$lang['Internetbs.tab_nameservers'] = 'Nameservers';
$lang['Internetbs.tab_nameservers.field_submit'] = 'Update Nameservers';


// Client Nameservers
$lang['Internetbs.tab_client_nameservers'] = 'Nameservers';
$lang['Internetbs.tab_client_nameservers.field_submit'] = 'Update Nameservers';


// URL Forwarding
$lang['Internetbs.tab_urlforwarding'] = 'URL Forwarding';
$lang['Internetbs.tab_urlforwarding.heading_current_rules'] = 'Current Rules';
$lang['Internetbs.tab_urlforwarding.heading_add_rule'] = 'Add Rule';
$lang['Internetbs.tab_urlforwarding.heading_source'] = 'Source';
$lang['Internetbs.tab_urlforwarding.heading_destination'] = 'Destination';
$lang['Internetbs.tab_urlforwarding.heading_title'] = 'Title';
$lang['Internetbs.tab_urlforwarding.heading_options'] = 'Options';
$lang['Internetbs.tab_urlforwarding.option_delete'] = 'Delete';
$lang['Internetbs.tab_urlforwarding.text_no_forwarding_rules'] = 'There are no URL forwarding rules for this domain.';
$lang['Internetbs.tab_urlforwarding.field_source'] = 'Source';
$lang['Internetbs.tab_urlforwarding.field_destination'] = 'Destination';
$lang['Internetbs.tab_urlforwarding.field_title'] = 'Title';
$lang['Internetbs.tab_urlforwarding.field_submit'] = 'Add Rule';


// Client URL Forwarding
$lang['Internetbs.tab_client_urlforwarding'] = 'URL Forwarding';
$lang['Internetbs.tab_client_urlforwarding.heading_current_rules'] = 'Current Rules';
$lang['Internetbs.tab_client_urlforwarding.heading_add_rule'] = 'Add Rule';
$lang['Internetbs.tab_client_urlforwarding.heading_source'] = 'Source';
$lang['Internetbs.tab_client_urlforwarding.heading_destination'] = 'Destination';
$lang['Internetbs.tab_client_urlforwarding.heading_title'] = 'Title';
$lang['Internetbs.tab_client_urlforwarding.heading_options'] = 'Options';
$lang['Internetbs.tab_client_urlforwarding.option_delete'] = 'Delete';
$lang['Internetbs.tab_client_urlforwarding.text_no_forwarding_rules'] = 'There are no URL forwarding rules for this domain.';
$lang['Internetbs.tab_client_urlforwarding.field_source'] = 'Source';
$lang['Internetbs.tab_client_urlforwarding.field_destination'] = 'Destination';
$lang['Internetbs.tab_client_urlforwarding.field_title'] = 'Title';
$lang['Internetbs.tab_client_urlforwarding.field_submit'] = 'Add Rule';


// E-Mail Forwarding
$lang['Internetbs.tab_emailforwarding'] = 'E-Mail Forwarding';
$lang['Internetbs.tab_emailforwarding.heading_current_rules'] = 'Current Rules';
$lang['Internetbs.tab_emailforwarding.heading_add_rule'] = 'Add Rule';
$lang['Internetbs.tab_emailforwarding.heading_source'] = 'Source';
$lang['Internetbs.tab_emailforwarding.heading_destination'] = 'Destination';
$lang['Internetbs.tab_emailforwarding.heading_options'] = 'Options';
$lang['Internetbs.tab_emailforwarding.option_delete'] = 'Delete';
$lang['Internetbs.tab_emailforwarding.text_no_forwarding_rules'] = 'There are no E-Mail forwarding rules for this domain.';
$lang['Internetbs.tab_emailforwarding.field_source'] = 'Source';
$lang['Internetbs.tab_emailforwarding.field_destination'] = 'Destination';
$lang['Internetbs.tab_emailforwarding.field_submit'] = 'Add Rule';


// Client E-Mail Forwarding
$lang['Internetbs.tab_client_emailforwarding'] = 'E-Mail Forwarding';
$lang['Internetbs.tab_client_emailforwarding.heading_current_rules'] = 'Current Rules';
$lang['Internetbs.tab_client_emailforwarding.heading_add_rule'] = 'Add Rule';
$lang['Internetbs.tab_client_emailforwarding.heading_source'] = 'Source';
$lang['Internetbs.tab_client_emailforwarding.heading_destination'] = 'Destination';
$lang['Internetbs.tab_client_emailforwarding.heading_options'] = 'Options';
$lang['Internetbs.tab_client_emailforwarding.option_delete'] = 'Delete';
$lang['Internetbs.tab_client_emailforwarding.text_no_forwarding_rules'] = 'There are no E-Mail forwarding rules for this domain.';
$lang['Internetbs.tab_client_emailforwarding.field_source'] = 'Source';
$lang['Internetbs.tab_client_emailforwarding.field_destination'] = 'Destination';
$lang['Internetbs.tab_client_emailforwarding.field_submit'] = 'Add Rule';


// Settings
$lang['Internetbs.tab_settings'] = 'Settings';
$lang['Internetbs.tab_settings.heading_settings'] = 'Settings';
$lang['Internetbs.tab_settings.heading_auth_code'] = 'Authorization Code';
$lang['Internetbs.tab_settings.text_auth_code'] = 'Use this authorization code to transfer your domain to another provider.';
$lang['Internetbs.tab_settings.field_registrar_lock'] = 'Registrar Lock';
$lang['Internetbs.tab_settings.field_registrar_lock_yes'] = 'Set the registrar lock. Recommended to prevent unauthorized transfer.';
$lang['Internetbs.tab_settings.field_registrar_lock_no'] = 'Release the registrar lock so the domain can be transferred.';
$lang['Internetbs.tab_settings.field_whois_privacy'] = 'WHOIS Privacy';
$lang['Internetbs.tab_settings.field_whois_privacy_state_yes'] = 'Enabled';
$lang['Internetbs.tab_settings.field_whois_privacy_state_no'] = 'Disabled';
$lang['Internetbs.tab_settings.field_epp_code'] = 'EPP Code';
$lang['Internetbs.tab_settings.field_submit'] = 'Update Settings';


// Client Settings
$lang['Internetbs.tab_client_settings'] = 'Settings';
$lang['Internetbs.tab_client_settings.heading_settings'] = 'Settings';
$lang['Internetbs.tab_client_settings.heading_auth_code'] = 'Authorization Code';
$lang['Internetbs.tab_client_settings.text_auth_code'] = 'Use this authorization code to transfer your domain to another provider.';
$lang['Internetbs.tab_client_settings.field_registrar_lock'] = 'Registrar Lock';
$lang['Internetbs.tab_client_settings.field_registrar_lock_yes'] = 'Set the registrar lock. Recommended to prevent unauthorized transfer.';
$lang['Internetbs.tab_client_settings.field_registrar_lock_no'] = 'Release the registrar lock so the domain can be transferred.';
$lang['Internetbs.tab_client_settings.field_whois_privacy'] = 'WHOIS Privacy';
$lang['Internetbs.tab_client_settings.field_whois_privacy_state_yes'] = 'Enabled';
$lang['Internetbs.tab_client_settings.field_whois_privacy_state_no'] = 'Disabled';
$lang['Internetbs.tab_client_settings.field_epp_code'] = 'EPP Code';
$lang['Internetbs.tab_client_settings.field_submit'] = 'Update Settings';


// Service info
$lang['Internetbs.service_info.domain'] = 'Domain';


// Package Fields
$lang['Internetbs.package_fields.epp_code'] = 'EPP Code';

$lang['Internetbs.package_fields.tooltip.epp_code'] = 'Whether to allow users to request an EPP Code through the Blesta service interface.';
$lang['Internetbs.package_fields.tld_options'] = 'TLDs';

// Nameserver Fields
$lang['Internetbs.nameserver.ns1'] = 'Name Server 1';
$lang['Internetbs.nameserver.ns2'] = 'Name Server 2';
$lang['Internetbs.nameserver.ns3'] = 'Name Server 3';
$lang['Internetbs.nameserver.ns4'] = 'Name Server 4';
$lang['Internetbs.nameserver.ns5'] = 'Name Server 5';

// Transfer Fields
$lang['Internetbs.transfer.transferAuthInfo'] = 'EPP Code';

// Domain Fields
$lang['Internetbs.domain.Domain'] = 'Domain';

$lang['Internetbs.domain.DotAsiaCedEntity'] = 'Entity';
$lang['Internetbs.domain.DotAsiaCedEntity.corporation'] = 'Corporation';
$lang['Internetbs.domain.DotAsiaCedEntity.cooperative'] = 'Cooperative';
$lang['Internetbs.domain.DotAsiaCedEntity.partnership'] = 'Partnership';
$lang['Internetbs.domain.DotAsiaCedEntity.government'] = 'Government';
$lang['Internetbs.domain.DotAsiaCedEntity.politicalparty'] = 'Political Party';
$lang['Internetbs.domain.DotAsiaCedEntity.society'] = 'Society';
$lang['Internetbs.domain.DotAsiaCedEntity.institution'] = 'Institution';
$lang['Internetbs.domain.DotAsiaCedEntity.naturalPerson'] = 'Natural Person';
$lang['Internetbs.domain.DotAsiaCedEntity.other'] = 'Other';

$lang['Internetbs.domain.DotAsiaCedIdForm'] = 'Entity ID Form';
$lang['Internetbs.domain.DotAsiaCedIdForm.certificate'] = 'Certificate';
$lang['Internetbs.domain.DotAsiaCedIdForm.legislation'] = 'Legislation';
$lang['Internetbs.domain.DotAsiaCedIdForm.societyregistry'] = 'Society Registry';
$lang['Internetbs.domain.DotAsiaCedIdForm.politicalpartyregistry'] = 'Political Party Registry';
$lang['Internetbs.domain.DotAsiaCedIdForm.passport'] = 'Passport';

$lang['Internetbs.domain.DotAsiaCedIdFormOther'] = 'Entity ID Form (if Other)';

$lang['Internetbs.domain.DotUkOrgType'] = 'Organization Type';
$lang['Internetbs.domain.DotUkOrgType.ind'] = 'UK individual';
$lang['Internetbs.domain.DotUkOrgType.find'] = 'Non-UK individual';
$lang['Internetbs.domain.DotUkOrgType.ltd'] = 'UK Limited Company';
$lang['Internetbs.domain.DotUkOrgType.plc'] = 'UK Public Limited Company';
$lang['Internetbs.domain.DotUkOrgType.ptnr'] = 'UK Partnership';
$lang['Internetbs.domain.DotUkOrgType.llp'] = 'UK Limited Liability Partnership';
$lang['Internetbs.domain.DotUkOrgType.ip'] = 'UK Industrial/Provident Registered Company';
$lang['Internetbs.domain.DotUkOrgType.stra'] = 'UK Sole Trader';
$lang['Internetbs.domain.DotUkOrgType.sch'] = 'UK School';
$lang['Internetbs.domain.DotUkOrgType.rchar'] = 'UK Registered Charity';
$lang['Internetbs.domain.DotUkOrgType.gov'] = 'UK Government Body';
$lang['Internetbs.domain.DotUkOrgType.other'] = 'UK Entity (other)';
$lang['Internetbs.domain.DotUkOrgType.crc'] = 'UK Corporation by Royal Charter';
$lang['Internetbs.domain.DotUkOrgType.fcorp'] = 'Foreign Organization';
$lang['Internetbs.domain.DotUkOrgType.stat'] = 'UK Statutory Body FIND';
$lang['Internetbs.domain.DotUkOrgType.fother'] = 'Other Foreign Organizations';
$lang['Internetbs.domain.DotUkRegistrationNumber'] = 'Company ID Number';

$lang['Internetbs.domain.dotFRContactEntityType'] = 'Entity Type';
$lang['Internetbs.domain.dotFRContactEntityType.individual'] = 'Individual';
$lang['Internetbs.domain.dotFRContactEntityType.company'] = 'Company';
$lang['Internetbs.domain.dotFRContactEntityType.trademark'] = 'Trademark';
$lang['Internetbs.domain.dotFRContactEntityType.association'] = 'Association';
$lang['Internetbs.domain.dotFRContactEntityType.other'] = 'Other';

$lang['Internetbs.domain.dotFRContactEntityBirthDate'] = 'Birth Date (if Individual)';
$lang['Internetbs.domain.dotFrContactEntityBirthPlaceCountryCode'] = 'Birth Country Code (if Individual)';
$lang['Internetbs.domain.dotFRContactEntityBirthCity'] = 'Birth City (if Individual)';
$lang['Internetbs.domain.dotFRContactEntityBirthPlacePostalCode'] = 'Birth Place Postal Code (if Individual)';
$lang['Internetbs.domain.dotFRContactEntityRestrictedPublication'] = 'Restricted Publication';
$lang['Internetbs.domain.dotFRContactEntityName'] = 'Entity Name (if not Individual)';
$lang['Internetbs.domain.dotFRContactEntityTradeMark'] = 'Trademark (if Trademark)';
$lang['Internetbs.domain.dotFRContactEntityDateOfAssocation'] = 'Date of Association (if Association)';
$lang['Internetbs.domain.dotFRContactEntityDateOfPublication'] = 'Date of Publication (if Association)';
$lang['Internetbs.domain.dotFRContactEntityAnnounceNo'] = 'Announce Number (if Association)';
$lang['Internetbs.domain.dotFRContactEntityPageNo'] = 'Page Number (if Association)';
$lang['Internetbs.domain.dotFROtherContactEntity'] = 'Entity (if Other)';
$lang['Internetbs.domain.dotFRContactEntityDUNS'] = 'DUNS Number';

$lang['Internetbs.domain.dotitEntityType'] = 'Entity Type';
$lang['Internetbs.domain.dotitEntityType.1'] = 'Italian and foreign natural persons';
$lang['Internetbs.domain.dotitEntityType.2'] = 'Companies/one man companies';
$lang['Internetbs.domain.dotitEntityType.3'] = 'Freelance workers/professionals';
$lang['Internetbs.domain.dotitEntityType.4'] = 'Non-profit organizations';
$lang['Internetbs.domain.dotitEntityType.5'] = 'Public organizations';
$lang['Internetbs.domain.dotitEntityType.6'] = 'Other subjects';
$lang['Internetbs.domain.dotitEntityType.7'] = 'Foreigners who are not natural persons';

$lang['Internetbs.domain.dotitNationality'] = 'Nationality (ISO 3166-1 code of a country belonging to the European Union)';
$lang['Internetbs.domain.dotitRegCode'] = 'Codice Fiscale / VAT Number';
$lang['Internetbs.domain.dotitProvince'] = 'Province';

$lang['Internetbs.domain.usNexusCategory'] = 'Nexus Category';
$lang['Internetbs.domain.usNexusCategory.c11'] = 'US citizen';
$lang['Internetbs.domain.usNexusCategory.c12'] = 'Permanent resident of the US';
$lang['Internetbs.domain.usNexusCategory.c21'] = 'US entity or organization';
$lang['Internetbs.domain.usNexusCategory.c31'] = 'Foreign organization';
$lang['Internetbs.domain.usNexusCategory.c32'] = 'Foreign organization with an office in the US';

$lang['Internetbs.domain.usNexusCountry'] = 'Nexus Country (if Foreign Organization)';

$lang['Internetbs.domain.usPurpose'] = 'Purpose of the Domain';
$lang['Internetbs.domain.usPurpose.p1'] = 'Business use for profit';
$lang['Internetbs.domain.usPurpose.p2'] = 'Non-profit business';
$lang['Internetbs.domain.usPurpose.p3'] = 'Personal use';
$lang['Internetbs.domain.usPurpose.p4'] = 'Educational purposes';
$lang['Internetbs.domain.usPurpose.p5'] = 'Government purposes';

$lang['Internetbs.domain.nlLegalForm'] = 'Legal Form';
$lang['Internetbs.domain.nlLegalForm.BGG'] = 'Non-Dutch EC company';
$lang['Internetbs.domain.nlLegalForm.BRO'] = 'Non-Dutch legal form/enterprise/subsidiary';
$lang['Internetbs.domain.nlLegalForm.BV'] = 'Limited company';
$lang['Internetbs.domain.nlLegalForm.COOP'] = 'Cooperative';
$lang['Internetbs.domain.nlLegalForm.CV'] = 'Limited Partnership';
$lang['Internetbs.domain.nlLegalForm.EENMANSZAAK'] = 'Sole trader';
$lang['Internetbs.domain.nlLegalForm.EESV'] = 'European Economic Interest Group';
$lang['Internetbs.domain.nlLegalForm.KERK'] = 'Religious society';
$lang['Internetbs.domain.nlLegalForm.MAATSCHAP'] = 'Partnership';
$lang['Internetbs.domain.nlLegalForm.NV'] = 'Public Company';
$lang['Internetbs.domain.nlLegalForm.OWM'] = 'Mutual benefit company';
$lang['Internetbs.domain.nlLegalForm.PERSOON'] = 'Natural person';
$lang['Internetbs.domain.nlLegalForm.REDR'] = 'Shipping company';
$lang['Internetbs.domain.nlLegalForm.STICHTING'] = 'Foundation';
$lang['Internetbs.domain.nlLegalForm.VERENIGING'] = 'Association';
$lang['Internetbs.domain.nlLegalForm.VOF'] = 'Partnership';
$lang['Internetbs.domain.nlLegalForm.ANDERS'] = 'Other';
$lang['Internetbs.domain.nlRegNumber'] = 'Registration Number';

$lang['Internetbs.!tooltip.dotFRContactEntityBirthDate'] = 'The date must be in the YYYY-MM-DD format.';
$lang['Internetbs.!tooltip.dotFRContactEntityDateOfAssocation'] = 'The date must be in the YYYY-MM-DD format.';
$lang['Internetbs.!tooltip.dotFRContactEntityDateOfPublication'] = 'The date must be in the YYYY-MM-DD format.';

// WHOIS Fields
$lang['Internetbs.whois.Registrant_FirstName'] = 'First Name';
$lang['Internetbs.whois.Registrant_LastName'] = 'Last Name';
$lang['Internetbs.whois.Registrant_Email'] = 'Email Address';
$lang['Internetbs.whois.Registrant_PhoneNumber'] = 'Phone Number';
$lang['Internetbs.whois.Registrant_Street'] = 'Address';
$lang['Internetbs.whois.Registrant_Street2'] = 'Address 2';
$lang['Internetbs.whois.Registrant_City'] = 'City';
$lang['Internetbs.whois.Registrant_CountryCode'] = 'Country Code';
$lang['Internetbs.whois.Registrant_PostalCode'] = 'Postal Code';

$lang['Internetbs.whois.Admin_FirstName'] = 'First Name';
$lang['Internetbs.whois.Admin_LastName'] = 'Last Name';
$lang['Internetbs.whois.Admin_Email'] = 'Email Address';
$lang['Internetbs.whois.Admin_PhoneNumber'] = 'Phone Number';
$lang['Internetbs.whois.Admin_Street'] = 'Address';
$lang['Internetbs.whois.Admin_Street2'] = 'Address 2';
$lang['Internetbs.whois.Admin_City'] = 'City';
$lang['Internetbs.whois.Admin_CountryCode'] = 'Country Code';
$lang['Internetbs.whois.Admin_PostalCode'] = 'Postal Code';

$lang['Internetbs.whois.Technical_FirstName'] = 'First Name';
$lang['Internetbs.whois.Technical_LastName'] = 'Last Name';
$lang['Internetbs.whois.Technical_Email'] = 'Email Address';
$lang['Internetbs.whois.Technical_PhoneNumber'] = 'Phone Number';
$lang['Internetbs.whois.Technical_Street'] = 'Address';
$lang['Internetbs.whois.Technical_Street2'] = 'Address 2';
$lang['Internetbs.whois.Technical_City'] = 'City';
$lang['Internetbs.whois.Technical_CountryCode'] = 'Country Code';
$lang['Internetbs.whois.Technical_PostalCode'] = 'Postal Code';

$lang['Internetbs.whois.Billing_FirstName'] = 'First Name';
$lang['Internetbs.whois.Billing_LastName'] = 'Last Name';
$lang['Internetbs.whois.Billing_Email'] = 'Email Address';
$lang['Internetbs.whois.Billing_PhoneNumber'] = 'Phone Number';
$lang['Internetbs.whois.Billing_Street'] = 'Address';
$lang['Internetbs.whois.Billing_Street2'] = 'Address 2';
$lang['Internetbs.whois.Billing_City'] = 'City';
$lang['Internetbs.whois.Billing_CountryCode'] = 'Country Code';
$lang['Internetbs.whois.Billing_PostalCode'] = 'Postal Code';
