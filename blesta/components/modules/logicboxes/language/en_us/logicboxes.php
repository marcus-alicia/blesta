<?php
// Tooltips
$lang['Logicboxes.!tooltip.row_meta.sandbox'] = 'If Sandbox is checked, you must define test/demo account credentials. Using your production account credentials with Sandbox checked will still perform live actions.';

// Errors
$lang['Logicboxes.!error.currency.not_exists'] = 'The reseller currency does not exists in this company.';

// Basics
$lang['Logicboxes.name'] = 'LogicBoxes';
$lang['Logicboxes.description'] = 'LogicBoxes is a domain registrar.';
$lang['Logicboxes.module_row'] = 'Account';
$lang['Logicboxes.module_row_plural'] = 'Accounts';

// Module management
$lang['Logicboxes.add_module_row'] = 'Add Account';
$lang['Logicboxes.manage.module_rows_title'] = 'Accounts';
$lang['Logicboxes.manage.module_rows_heading.registrar'] = 'Registrar Name';
$lang['Logicboxes.manage.module_rows_heading.reseller_id'] = 'Reseller ID';
$lang['Logicboxes.manage.module_rows_heading.key'] = 'API Key';
$lang['Logicboxes.manage.module_rows_heading.sandbox'] = 'Sandbox';
$lang['Logicboxes.manage.module_rows_heading.options'] = 'Options';
$lang['Logicboxes.manage.module_rows.edit'] = 'Edit';
$lang['Logicboxes.manage.module_rows.delete'] = 'Delete';
$lang['Logicboxes.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this account?';
$lang['Logicboxes.manage.module_rows_no_results'] = 'There are no accounts.';

// Row Meta
$lang['Logicboxes.row_meta.registrar'] = 'Registrar Name';
$lang['Logicboxes.row_meta.reseller_id'] = 'Reseller ID';
$lang['Logicboxes.row_meta.key'] = 'Key';
$lang['Logicboxes.row_meta.sandbox'] = 'Sandbox';
$lang['Logicboxes.row_meta.sandbox_true'] = 'Yes';
$lang['Logicboxes.row_meta.sandbox_false'] = 'No';

// Add row
$lang['Logicboxes.add_row.box_title'] = 'Add LogicBoxes Account';
$lang['Logicboxes.add_row.basic_title'] = 'Basic Settings';
$lang['Logicboxes.add_row.add_btn'] = 'Add Account';

// Edit row
$lang['Logicboxes.edit_row.box_title'] = 'Edit LogicBoxes Account';
$lang['Logicboxes.edit_row.basic_title'] = 'Basic Settings';
$lang['Logicboxes.edit_row.add_btn'] = 'Update Account';

// Package fields
$lang['Logicboxes.package_fields.type'] = 'Type';
$lang['Logicboxes.package_fields.type_domain'] = 'Domain Registration';
$lang['Logicboxes.package_fields.type_ssl'] = 'SSL Certificate';
$lang['Logicboxes.package_fields.tld_options'] = 'TLDs';
$lang['Logicboxes.package_fields.ns1'] = 'Name Server 1';
$lang['Logicboxes.package_fields.ns2'] = 'Name Server 2';
$lang['Logicboxes.package_fields.ns3'] = 'Name Server 3';
$lang['Logicboxes.package_fields.ns4'] = 'Name Server 4';
$lang['Logicboxes.package_fields.ns5'] = 'Name Server 5';

// Service management
$lang['Logicboxes.tab_unavailable.message'] = 'This information is not yet available.';

$lang['Logicboxes.tab_whois.title'] = 'Whois';
$lang['Logicboxes.tab_whois.section_registrantcontact'] = 'Registrant';
$lang['Logicboxes.tab_whois.section_admincontact'] = 'Administrative';
$lang['Logicboxes.tab_whois.section_techcontact'] = 'Technical';
$lang['Logicboxes.tab_whois.section_billingcontact'] = 'Billing';
$lang['Logicboxes.tab_whois.field_submit'] = 'Update Whois';

$lang['Logicboxes.managewhois.contact_transfer'] = 'A new contact has been created with the given details and the process of transfering the domain to this contact has begun.  This action must be confirmed by both the current contact and new one before taking effect.';

$lang['Logicboxes.tab_nameservers.title'] = 'Name Servers';
$lang['Logicboxes.tab_nameserver.field_ns'] = 'Name Server %1$s'; // %1$s is the name server number
$lang['Logicboxes.tab_nameservers.field_submit'] = 'Update Name Servers';

$lang['Logicboxes.tab_settings.title'] = 'Settings';
$lang['Logicboxes.tab_settings.field_registrar_lock'] = 'Registrar Lock';
$lang['Logicboxes.tab_settings.field_registrar_lock_yes'] = 'Set the registrar lock. Recommended to prevent unauthorized transfer.';
$lang['Logicboxes.tab_settings.field_registrar_lock_no'] = 'Release the registrar lock so the domain can be transferred.';
$lang['Logicboxes.tab_settings.field_request_epp'] = 'Request EPP Code/Transfer Key';
$lang['Logicboxes.tab_settings.field_submit'] = 'Update Settings';

// Errors
$lang['Logicboxes.!error.registrar.valid'] = 'Please enter a registrar name.';
$lang['Logicboxes.!error.reseller_id.valid'] = 'Please enter a reseller ID.';
$lang['Logicboxes.!error.key.valid'] = 'Please enter a key.';
$lang['Logicboxes.!error.key.valid_connection'] = 'The reseller ID and key combination appear to be invalid, or your LogicBoxes account may not be configured to allow API access.';


// Domain Transfer Fields
$lang['Logicboxes.transfer.domain-name'] = 'Domain Name';
$lang['Logicboxes.transfer.auth-code'] = 'EPP Code';

// Domain Fields
$lang['Logicboxes.domain.domain-name'] = 'Domain Name';

// Nameserver Fields
$lang['Logicboxes.nameserver.ns1'] = 'Name Server 1';
$lang['Logicboxes.nameserver.ns2'] = 'Name Server 2';
$lang['Logicboxes.nameserver.ns3'] = 'Name Server 3';
$lang['Logicboxes.nameserver.ns4'] = 'Name Server 4';
$lang['Logicboxes.nameserver.ns5'] = 'Name Server 5';

// Contact Fields
$lang['Logicboxes.contact.customer-id'] = 'Customer ID';
$lang['Logicboxes.contact.type'] = 'Type';
$lang['Logicboxes.contact.name'] = 'Name';
$lang['Logicboxes.contact.company'] = 'Company';
$lang['Logicboxes.contact.email'] = 'Email';
$lang['Logicboxes.contact.address-line-1'] = 'Address 1';
$lang['Logicboxes.contact.address-line-2'] = 'Address 2';
$lang['Logicboxes.contact.city'] = 'City';
$lang['Logicboxes.contact.state'] = 'State';
$lang['Logicboxes.contact.zipcode'] = 'Zip';
$lang['Logicboxes.contact.country'] = 'Country';
$lang['Logicboxes.contact.phone-cc'] = 'Phone Country Dialing Code';
$lang['Logicboxes.contact.phone'] = 'Phone Number';
$lang['Logicboxes.contact.fax-cc'] = 'Fax Country Dialing Code';
$lang['Logicboxes.contact.fax'] = 'Fax Number';

// Customer Fields
$lang['Logicboxes.customer.username'] = 'Username';
$lang['Logicboxes.customer.passwd'] = 'Password';
$lang['Logicboxes.customer.lang-pref'] = 'Language';

// .ASIA Contact Fields
$lang['Logicboxes.contact.legalentitytype'] = 'Legal Type';
$lang['Logicboxes.contact.legalentitytype.corporation'] = 'Corporations or Companies';
$lang['Logicboxes.contact.legalentitytype.cooperative'] = 'Cooperatives';
$lang['Logicboxes.contact.legalentitytype.partnership'] = 'Partnerships or Collectives';
$lang['Logicboxes.contact.legalentitytype.government'] = 'Government Bodies';
$lang['Logicboxes.contact.legalentitytype.politicalParty'] = 'Political parties or Trade Unions';
$lang['Logicboxes.contact.legalentitytype.society'] = 'Trusts, Estates, Associations or Societies';
$lang['Logicboxes.contact.legalentitytype.institution'] = 'Institutions';
$lang['Logicboxes.contact.legalentitytype.naturalPerson'] = 'Natural Persons';
$lang['Logicboxes.contact.identform'] = 'Form of Identity';
$lang['Logicboxes.contact.identform.certificate'] = 'Certificate of Incorporation';
$lang['Logicboxes.contact.identform.legislation'] = 'Charter';
$lang['Logicboxes.contact.identform.societyRegistry'] = 'Societies Registry';
$lang['Logicboxes.contact.identform.politicalPartyRegistry'] = 'Political Party Registry';
$lang['Logicboxes.contact.identform.passport'] = 'Passport/ Citizenship ID';
$lang['Logicboxes.contact.identnumber'] = 'Identity Number';

// .CA Contact Fields
$lang['Logicboxes.contact.CPR'] = 'Legal Type';
$lang['Logicboxes.contact.CPR.cco'] = 'Corporation';
$lang['Logicboxes.contact.CPR.cct'] = 'Canadian citizen';
$lang['Logicboxes.contact.CPR.res'] = 'Canadian resident';
$lang['Logicboxes.contact.CPR.gov'] = 'Government entity';
$lang['Logicboxes.contact.CPR.edu'] = 'Educational';
$lang['Logicboxes.contact.CPR.ass'] = 'Unincorporated Association';
$lang['Logicboxes.contact.CPR.hop'] = 'Hospital';
$lang['Logicboxes.contact.CPR.prt'] = 'Partnership';
$lang['Logicboxes.contact.CPR.tdm'] = 'Trade-mark';
$lang['Logicboxes.contact.CPR.trd'] = 'Trade Union';
$lang['Logicboxes.contact.CPR.plt'] = 'Political Party';
$lang['Logicboxes.contact.CPR.lam'] = 'Libraries, Archives and Museums';
$lang['Logicboxes.contact.CPR.trs'] = 'Trust';
$lang['Logicboxes.contact.CPR.abo'] = 'Aboriginal Peoples';
$lang['Logicboxes.contact.CPR.inb'] = 'Indian Band';
$lang['Logicboxes.contact.CPR.lgr'] = 'Legal Representative';
$lang['Logicboxes.contact.CPR.omk'] = 'Official Mark';
$lang['Logicboxes.contact.CPR.maj'] = 'The Queen';

// .COOP Contact Fields
$lang['Logicboxes.contact.sponsor'] = 'ROID of Sponsor (Co-operative Reference)';

// .ES Contact Fields
$lang['Logicboxes.contact.es_tipo_identificacion'] = 'Identification Type';
$lang['Logicboxes.contact.es_tipo_identificacion.1'] = 'Spanish National Personal ID or company VAT ID (DNI or NIF)';
$lang['Logicboxes.contact.es_tipo_identificacion.3'] = 'Spanish resident alien ID (NIE)';
$lang['Logicboxes.contact.es_tipo_identificacion.0'] = "Passport number, Driver's License number, etc.";
$lang['Logicboxes.contact.es_identificacion'] = 'Identification Number';

// .NL Contact Fields
$lang['Logicboxes.contact.legalForm'] = 'Registrant Type';
$lang['Logicboxes.contact.legalForm.persoon'] = 'Natural Person';
$lang['Logicboxes.contact.legalForm.anders'] = 'Other';

// .PRO Contact Fields
$lang['Logicboxes.contact.profession'] = 'Profession';

// .RU Contact Fields
$lang['Logicboxes.contact.contract-type'] = 'Contact Type';
$lang['Logicboxes.contact.contract-type.org'] = 'Organization';
$lang['Logicboxes.contact.contract-type.prs'] = 'Individual';
$lang['Logicboxes.contact.birth-date'] = 'Birth Date (DD.MM.YYYY) if Individual';
$lang['Logicboxes.contact.kpp'] = 'Territory-linked Taxpayer number if Organization';
$lang['Logicboxes.contact.code'] = 'Taxpayer Identification Number (TIN) if Organization';
$lang['Logicboxes.contact.passport'] = 'Passport number, issued by, and issued date if Individual';

// .US Contact Fields
$lang['Logicboxes.contact.category'] = 'Registrant Type';
$lang['Logicboxes.contact.category.c11'] = 'US citizen';
$lang['Logicboxes.contact.category.c12'] = 'Permanent resident of the US';
$lang['Logicboxes.contact.category.c21'] = 'US entity or organization';
$lang['Logicboxes.contact.category.c31'] = 'Foreign organization';
$lang['Logicboxes.contact.category.c32'] = 'Foreign organization with an office in the US';
$lang['Logicboxes.contact.purpose'] = 'Purpose';
$lang['Logicboxes.contact.purpose.p1'] = 'Business';
$lang['Logicboxes.contact.purpose.p2'] = 'Non-profit';
$lang['Logicboxes.contact.purpose.p3'] = 'Personal';
$lang['Logicboxes.contact.purpose.p4'] = 'Educational';
$lang['Logicboxes.contact.purpose.p5'] = 'Governmental';

// .AU Domain Fields
$lang['Logicboxes.domain.id-type'] = 'Eligibility ID Type';
$lang['Logicboxes.domain.id-type.acn'] = 'Australian Company Number';
$lang['Logicboxes.domain.id-type.abn'] = 'Australian Business Number';
$lang['Logicboxes.domain.id-type.vic_bn'] = 'Victoria Business Number';
$lang['Logicboxes.domain.id-type.nsw_bn'] = 'New South Wales Business Number';
$lang['Logicboxes.domain.id-type.sa_bn'] = 'South Australia Business Number';
$lang['Logicboxes.domain.id-type.nt_bn'] = 'Northern Territory Business Number';
$lang['Logicboxes.domain.id-type.wa_bn'] = 'Western Australia Business Number';
$lang['Logicboxes.domain.id-type.tas_bn'] = 'Tasmania Business Number';
$lang['Logicboxes.domain.id-type.act_bn'] = 'Australian Capital Territory Business Number';
$lang['Logicboxes.domain.id-type.qld_bn'] = 'Queensland Business Number';
$lang['Logicboxes.domain.id-type.tm'] = 'Tradmark Number';
$lang['Logicboxes.domain.id-type.arbn'] = 'Australian Registered Body Number';
$lang['Logicboxes.domain.id-type.other'] = 'Other';
$lang['Logicboxes.domain.id'] = 'Eligibility ID';
$lang['Logicboxes.domain.policyReason'] = 'Eligibility Reason';
$lang['Logicboxes.domain.policyReason.1'] = 'Domain name is an exact match, acronym or abbreviation of the registrant’s company or trading name, organization or association name or trademark';
$lang['Logicboxes.domain.policyReason.2'] = 'Domain Name is closely and substantially connected to the registrant';
$lang['Logicboxes.domain.isAUWarranty'] = 'Warranty';
$lang['Logicboxes.domain.isAUWarranty.true'] = 'By submitting this application, I confirm that I am eligible to hold the domain name set out in this application, and that all information provided in this application is true, complete and correct and is not misleading in any way. If any of the information is later found not to be true, or is incomplete, incorrect, or misleading in any way or if this application is submitted in bad faith, the domain name license can be cancelled and can lead to a permanent loss of the use of the domain name.';

// .US domain fields
$lang['Logicboxes.domain.RegistrantNexus'] = 'Registrant Type';
$lang['Logicboxes.domain.RegistrantNexus.c11'] = 'US citizen';
$lang['Logicboxes.domain.RegistrantNexus.c12'] = 'Permanent resident of the US';
$lang['Logicboxes.domain.RegistrantNexus.c21'] = 'US entity or organization';
$lang['Logicboxes.domain.RegistrantNexus.c31'] = 'Foreign organization';
$lang['Logicboxes.domain.RegistrantNexus.c32'] = 'Foreign organization with an office in the US';
$lang['Logicboxes.domain.RegistrantPurpose'] = 'Purpose';
$lang['Logicboxes.domain.RegistrantPurpose.p1'] = 'Business';
$lang['Logicboxes.domain.RegistrantPurpose.p2'] = 'Non-profit';
$lang['Logicboxes.domain.RegistrantPurpose.p3'] = 'Personal';
$lang['Logicboxes.domain.RegistrantPurpose.p4'] = 'Educational';
$lang['Logicboxes.domain.RegistrantPurpose.p5'] = 'Governmental';

// .EU domain fields
$lang['Logicboxes.domain.EUAgreeWhoisPolicy'] = 'Whois Policy';
$lang['Logicboxes.domain.EUAgreeWhoisPolicy.yes'] = 'I hereby agree that the Registry is entitled to transfer the data contained in this application to third parties(i) if ordered to do so by a public authority, carrying out its legitimate tasks; and (ii) upon demand of an ADR Provider as mentioned in section 16 of the Terms and Conditions which are published at www.eurid.eu; and (iii) as provided in Section 2 (WHOIS look-up facility) of the .eu Domain Name WHOIS Policy which is published at www.eurid.eu.';
$lang['Logicboxes.domain.EUAgreeDeletePolicy'] = 'Deletion Rules';
$lang['Logicboxes.domain.EUAgreeDeletePolicy.yes'] = 'I agree and acknowledge to the special renewal and expiration terms set forth below for this domain name, including those terms set forth in the Registration Agreement. I understand that unless I have set this domain for autorenewal, this domain name must be explicitly renewed by the expiration date or the 20th of the month of expiration, whichever is sooner. (e.g. If the name expires on Sept 4th, 2008, then a manual renewal must be received by Sept 4th, 2008. If name expires on Sep 27th, 2008, the renewal request must be received prior to Sep 20th, 2008). If the name is not manually renewed or previously set to autorenew, a delete request will be issued by Logicboxes. When a delete request is issued, the name will remain fully functional in my account until expiration, but will no longer be renewable nor will I be able to make any modifications to the name. These terms are subject to change.';

// .CA domain fields
$lang['Logicboxes.domain.CIRALegalType'] = 'Legal Type';
$lang['Logicboxes.domain.RegistrantPurpose.cco'] = 'Corporation';
$lang['Logicboxes.domain.RegistrantPurpose.cct'] = 'Canadian citizen';
$lang['Logicboxes.domain.RegistrantPurpose.res'] = 'Canadian resident';
$lang['Logicboxes.domain.RegistrantPurpose.gov'] = 'Government entity';
$lang['Logicboxes.domain.RegistrantPurpose.edu'] = 'Educational';
$lang['Logicboxes.domain.RegistrantPurpose.ass'] = 'Unincorporated Association';
$lang['Logicboxes.domain.RegistrantPurpose.hop'] = 'Hospital';
$lang['Logicboxes.domain.RegistrantPurpose.prt'] = 'Partnership';
$lang['Logicboxes.domain.RegistrantPurpose.tdm'] = 'Trade-mark';
$lang['Logicboxes.domain.RegistrantPurpose.trd'] = 'Trade Union';
$lang['Logicboxes.domain.RegistrantPurpose.plt'] = 'Political Party';
$lang['Logicboxes.domain.RegistrantPurpose.lam'] = 'Libraries, Archives and Museums';
$lang['Logicboxes.domain.RegistrantPurpose.trs'] = 'Trust';
$lang['Logicboxes.domain.RegistrantPurpose.abo'] = 'Aboriginal Peoples';
$lang['Logicboxes.domain.RegistrantPurpose.inb'] = 'Indian Band';
$lang['Logicboxes.domain.RegistrantPurpose.lgr'] = 'Legal Representative';
$lang['Logicboxes.domain.RegistrantPurpose.omk'] = 'Official Mark';
$lang['Logicboxes.domain.RegistrantPurpose.maj'] = 'The Queen';
$lang['Logicboxes.domain.CIRAWhoisDisplay'] = 'Whois';
$lang['Logicboxes.domain.CIRAWhoisDisplay.full'] = 'Make Public';
$lang['Logicboxes.domain.CIRAWhoisDisplay.private'] = 'Keep Private';

// .CO.UK domain fields
$lang['Logicboxes.domain.COUKLegalType'] = 'Legal Type';
$lang['Logicboxes.domain.COUKLegalType.ind'] = 'UK individual';
$lang['Logicboxes.domain.COUKLegalType.find'] = 'Non-UK individual';
$lang['Logicboxes.domain.COUKLegalType.ltd'] = 'UK Limited Company';
$lang['Logicboxes.domain.COUKLegalType.plc'] = 'UK Public Limited Company';
$lang['Logicboxes.domain.COUKLegalType.ptnr'] = 'UK Partnership';
$lang['Logicboxes.domain.COUKLegalType.llp'] = 'UK Limited Liability Partnership';
$lang['Logicboxes.domain.COUKLegalType.ip'] = 'UK Industrial/Provident Registered Company';
$lang['Logicboxes.domain.COUKLegalType.stra'] = 'UK Sole Trader';
$lang['Logicboxes.domain.COUKLegalType.sch'] = 'UK School';
$lang['Logicboxes.domain.COUKLegalType.rchar'] = 'UK Registered Charity';
$lang['Logicboxes.domain.COUKLegalType.gov'] = 'UK Government Body';
$lang['Logicboxes.domain.COUKLegalType.other'] = 'UK Entity (other)';
$lang['Logicboxes.domain.COUKLegalType.crc'] = 'UK Corporation by Royal Charter';
$lang['Logicboxes.domain.COUKLegalType.fcorp'] = 'Foreign Organization';
$lang['Logicboxes.domain.COUKLegalType.stat'] = 'UK Statutory Body FIND';
$lang['Logicboxes.domain.COUKLegalType.fother'] = 'Other Foreign Organizations';
$lang['Logicboxes.domain.COUKCompanyID'] = 'Company ID Number';
$lang['Logicboxes.domain.COUKRegisteredfor'] = 'Registrant Name';

// .ME.UK domain fields
$lang['Logicboxes.domain.MEUKLegalType'] = 'Legal Type';
$lang['Logicboxes.domain.MEUKLegalType.ind'] = 'UK individual';
$lang['Logicboxes.domain.MEUKLegalType.find'] = 'Non-UK individual';
$lang['Logicboxes.domain.MEUKLegalType.ltd'] = 'UK Limited Company';
$lang['Logicboxes.domain.MEUKLegalType.plc'] = 'UK Public Limited Company';
$lang['Logicboxes.domain.MEUKLegalType.ptnr'] = 'UK Partnership';
$lang['Logicboxes.domain.MEUKLegalType.llp'] = 'UK Limited Liability Partnership';
$lang['Logicboxes.domain.MEUKLegalType.ip'] = 'UK Industrial/Provident Registered Company';
$lang['Logicboxes.domain.MEUKLegalType.stra'] = 'UK Sole Trader';
$lang['Logicboxes.domain.MEUKLegalType.sch'] = 'UK School';
$lang['Logicboxes.domain.MEUKLegalType.rchar'] = 'UK Registered Charity';
$lang['Logicboxes.domain.MEUKLegalType.gov'] = 'UK Government Body';
$lang['Logicboxes.domain.MEUKLegalType.other'] = 'UK Entity (other)';
$lang['Logicboxes.domain.MEUKLegalType.crc'] = 'UK Corporation by Royal Charter';
$lang['Logicboxes.domain.MEUKLegalType.fcorp'] = 'Foreign Organization';
$lang['Logicboxes.domain.MEUKLegalType.stat'] = 'UK Statutory Body FIND';
$lang['Logicboxes.domain.MEUKLegalType.fother'] = 'Other Foreign Organizations';
$lang['Logicboxes.domain.MEUKCompanyID'] = 'Company ID Number';
$lang['Logicboxes.domain.MEUKRegisteredfor'] = 'Registrant Name';

// .ORG.UK domain fields
$lang['Logicboxes.domain.ORGUKLegalType'] = 'Legal Type';
$lang['Logicboxes.domain.ORGUKLegalType.ind'] = 'UK individual';
$lang['Logicboxes.domain.ORGUKLegalType.find'] = 'Non-UK individual';
$lang['Logicboxes.domain.ORGUKLegalType.ltd'] = 'UK Limited Company';
$lang['Logicboxes.domain.ORGUKLegalType.plc'] = 'UK Public Limited Company';
$lang['Logicboxes.domain.ORGUKLegalType.ptnr'] = 'UK Partnership';
$lang['Logicboxes.domain.ORGUKLegalType.llp'] = 'UK Limited Liability Partnership';
$lang['Logicboxes.domain.ORGUKLegalType.ip'] = 'UK Industrial/Provident Registered Company';
$lang['Logicboxes.domain.ORGUKLegalType.stra'] = 'UK Sole Trader';
$lang['Logicboxes.domain.ORGUKLegalType.sch'] = 'UK School';
$lang['Logicboxes.domain.ORGUKLegalType.rchar'] = 'UK Registered Charity';
$lang['Logicboxes.domain.ORGUKLegalType.gov'] = 'UK Government Body';
$lang['Logicboxes.domain.ORGUKLegalType.other'] = 'UK Entity (other)';
$lang['Logicboxes.domain.ORGUKLegalType.crc'] = 'UK Corporation by Royal Charter';
$lang['Logicboxes.domain.ORGUKLegalType.fcorp'] = 'Foreign Organization';
$lang['Logicboxes.domain.ORGUKLegalType.stat'] = 'UK Statutory Body FIND';
$lang['Logicboxes.domain.ORGUKLegalType.fother'] = 'Other Foreign Organizations';
$lang['Logicboxes.domain.ORGUKCompanyID'] = 'Company ID Number';
$lang['Logicboxes.domain.ORGUKRegisteredfor'] = 'Registrant Name';

// .ASIA domain fields
$lang['Logicboxes.domain.ASIALegalEntityType'] = 'Legal Type';
$lang['Logicboxes.domain.ASIALegalEntityType.corporation'] = 'Corporations or Companies';
$lang['Logicboxes.domain.ASIALegalEntityType.cooperative'] = 'Cooperatives';
$lang['Logicboxes.domain.ASIALegalEntityType.partnership'] = 'Partnerships or Collectives';
$lang['Logicboxes.domain.ASIALegalEntityType.government'] = 'Government Bodies';
$lang['Logicboxes.domain.ASIALegalEntityType.politicalParty'] = 'Political parties or Trade Unions';
$lang['Logicboxes.domain.ASIALegalEntityType.society'] = 'Trusts, Estates, Associations or Societies';
$lang['Logicboxes.domain.ASIALegalEntityType.institution'] = 'Institutions';
$lang['Logicboxes.domain.ASIALegalEntityType.naturalPerson'] = 'Natural Persons';
$lang['Logicboxes.domain.ASIAIdentForm'] = 'Form of Identity';
$lang['Logicboxes.domain.ASIAIdentForm.certificate'] = 'Certificate of Incorporation';
$lang['Logicboxes.domain.ASIAIdentForm.legislation'] = 'Charter';
$lang['Logicboxes.domain.ASIAIdentForm.societyRegistry'] = 'Societies Registry';
$lang['Logicboxes.domain.ASIAIdentForm.politicalPartyRegistry'] = 'Political Party Registry';
$lang['Logicboxes.domain.ASIAIdentForm.passport'] = 'Passport/ Citizenship ID';
$lang['Logicboxes.domain.ASIAIdentNumber'] = 'Identity Number';
