<?php
// Basics
$lang['Namecheap.name'] = 'Namecheap';
$lang['Namecheap.description'] = 'Namecheap, Inc. is an ICANN-accredited domain name registrar, which provides domain name registration and web hosting. Namecheap is a budget hosting provider with 11 million registered users and 10 million domains.';
$lang['Namecheap.module_row'] = 'Account';
$lang['Namecheap.module_row_plural'] = 'Accounts';

// Errors
$lang['Namecheap.!error.currency.not_exists'] = 'The reseller currency does not exists in this company.';

// Module management
$lang['Namecheap.add_module_row'] = 'Add Account';
$lang['Namecheap.manage.module_rows_title'] = 'Accounts';
$lang['Namecheap.manage.module_rows_heading.user'] = 'User';
$lang['Namecheap.manage.module_rows_heading.key'] = 'API Key';
$lang['Namecheap.manage.module_rows_heading.sandbox'] = 'Sandbox';
$lang['Namecheap.manage.module_rows_heading.options'] = 'Options';
$lang['Namecheap.manage.module_rows.edit'] = 'Edit';
$lang['Namecheap.manage.module_rows.delete'] = 'Delete';
$lang['Namecheap.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this account?';
$lang['Namecheap.manage.module_rows_no_results'] = 'There are no accounts.';

// Row Meta
$lang['Namecheap.row_meta.user'] = 'User';
$lang['Namecheap.row_meta.key'] = 'Key';
$lang['Namecheap.row_meta.sandbox'] = 'Sandbox';
$lang['Namecheap.row_meta.sandbox_true'] = 'Yes';
$lang['Namecheap.row_meta.sandbox_false'] = 'No';

// Add row
$lang['Namecheap.add_row.box_title'] = 'Add Namecheap Account';
$lang['Namecheap.add_row.basic_title'] = 'Basic Settings';
$lang['Namecheap.add_row.add_btn'] = 'Add Account';

// Edit row
$lang['Namecheap.edit_row.box_title'] = 'Edit Namecheap Account';
$lang['Namecheap.edit_row.basic_title'] = 'Basic Settings';
$lang['Namecheap.edit_row.add_btn'] = 'Update Account';

// Package fields
$lang['Namecheap.package_fields.type'] = 'Type';
$lang['Namecheap.package_fields.type_domain'] = 'Domain Registration';
$lang['Namecheap.package_fields.type_ssl'] = 'SSL Certificate';
$lang['Namecheap.package_fields.tld_options'] = 'TLDs';
$lang['Namecheap.package_fields.ns1'] = 'Name Server 1';
$lang['Namecheap.package_fields.ns2'] = 'Name Server 2';
$lang['Namecheap.package_fields.ns3'] = 'Name Server 3';
$lang['Namecheap.package_fields.ns4'] = 'Name Server 4';
$lang['Namecheap.package_fields.ns5'] = 'Name Server 5';

// Service management
$lang['Namecheap.tab_whois.title'] = 'Whois';
$lang['Namecheap.tab_whois.section_Registrant'] = 'Registrant';
$lang['Namecheap.tab_whois.section_Admin'] = 'Administrative';
$lang['Namecheap.tab_whois.section_Tech'] = 'Technical';
$lang['Namecheap.tab_whois.section_AuxBilling'] = 'Billing';
$lang['Namecheap.tab_whois.field_submit'] = 'Update Whois';

$lang['Namecheap.tab_nameservers.title'] = 'Name Servers';
$lang['Namecheap.tab_nameserver.field_ns'] = 'Name Server %1$s'; // %1$s is the name server number
$lang['Namecheap.tab_nameservers.field_submit'] = 'Update Name Servers';

$lang['Namecheap.tab_settings.title'] = 'Settings';
$lang['Namecheap.tab_settings.field_registrar_lock'] = 'Registrar Lock';
$lang['Namecheap.tab_settings.field_registrar_lock_yes'] = 'Set the registrar lock. Recommended to prevent unauthorized transfer.';
$lang['Namecheap.tab_settings.field_registrar_lock_no'] = 'Release the registrar lock so the domain can be transferred.';
$lang['Namecheap.tab_settings.field_request_epp'] = 'Request EPP Code/Transfer Key';
$lang['Namecheap.tab_settings.field_submit'] = 'Update Settings';

// Errors
$lang['Namecheap.!error.user.valid'] = 'Please enter a user';
$lang['Namecheap.!error.key.valid'] = 'Please enter a key';
$lang['Namecheap.!error.key.valid_connection'] = 'The user and key combination appear to be invalid, or your Namecheap account may not be configured to allow API access.';


// Domain Transfer Fields
$lang['Namecheap.transfer.DomainName'] = 'Domain Name';
$lang['Namecheap.transfer.EPPCode'] = 'EPP Code';

// Domain Fields
$lang['Namecheap.domain.DomainName'] = 'Domain Name';
$lang['Namecheap.domain.Years'] = 'Years';

// Nameserver Fields
$lang['Namecheap.nameserver.ns1'] = 'Name Server 1';
$lang['Namecheap.nameserver.ns2'] = 'Name Server 2';
$lang['Namecheap.nameserver.ns3'] = 'Name Server 3';
$lang['Namecheap.nameserver.ns4'] = 'Name Server 4';
$lang['Namecheap.nameserver.ns5'] = 'Name Server 5';

//$lang['Namecheap.domain.IdnCode'] = "";
//$lang['Namecheap.domain.Nameservers'] = "";
//$lang['Namecheap.domain.AddFreeWhoisguard'] = "";
//$lang['Namecheap.domain.WGEnabled Enables'] = "";
//$lang['Namecheap.domain.AddFreePositiveSSL'] = "";

// Whois Fields
//$lang['Namecheap.whois.RegistrantOrganizationName'] = "Organization";
//$lang['Namecheap.whois.RegistrantJobTitle'] = "Job Title";
$lang['Namecheap.whois.RegistrantFirstName'] = 'First Name';
$lang['Namecheap.whois.RegistrantLastName'] = 'Last Name';
$lang['Namecheap.whois.RegistrantAddress1'] = 'Address 1';
$lang['Namecheap.whois.RegistrantAddress2'] = 'Address 2';
$lang['Namecheap.whois.RegistrantCity'] = 'City';
$lang['Namecheap.whois.RegistrantStateProvince'] = 'State/Province';
//$lang['Namecheap.whois.RegistrantStateProvinceChoice'] = "State/Province Choice";
$lang['Namecheap.whois.RegistrantPostalCode'] = 'Postal Code';
$lang['Namecheap.whois.RegistrantCountry'] = 'Country';
$lang['Namecheap.whois.RegistrantPhone'] = 'Phone';
//$lang['Namecheap.whois.RegistrantPhoneExt'] = "Phone Extension";
//$lang['Namecheap.whois.RegistrantFax'] = "Fax";
$lang['Namecheap.whois.RegistrantEmailAddress'] = 'Email';

//$lang['Namecheap.whois.TechOrganizationName'] = "Organization";
//$lang['Namecheap.whois.TechJobTitle'] = "Job Title";
$lang['Namecheap.whois.TechFirstName'] = 'First Name';
$lang['Namecheap.whois.TechLastName'] = 'Last Name';
$lang['Namecheap.whois.TechAddress1'] = 'Address 1';
$lang['Namecheap.whois.TechAddress2'] = 'Address 2';
$lang['Namecheap.whois.TechCity'] = 'City';
$lang['Namecheap.whois.TechStateProvince'] = 'State/Province';
//$lang['Namecheap.whois.TechStateProvinceChoice'] = "State/Province Choice";
$lang['Namecheap.whois.TechPostalCode'] = 'Postal Code';
$lang['Namecheap.whois.TechCountry'] = 'Country';
$lang['Namecheap.whois.TechPhone'] = 'Phone';
//$lang['Namecheap.whois.TechPhoneExt'] = "Phone Extension";
//$lang['Namecheap.whois.TechFax'] = "Fax";
$lang['Namecheap.whois.TechEmailAddress'] = 'Email';

//$lang['Namecheap.whois.AdminOrganizationName'] = "Organization";
//$lang['Namecheap.whois.AdminJobTitle'] = "Job Title";
$lang['Namecheap.whois.AdminFirstName'] = 'First Name';
$lang['Namecheap.whois.AdminLastName'] = 'Last Name';
$lang['Namecheap.whois.AdminAddress1'] = 'Address 1';
$lang['Namecheap.whois.AdminAddress2'] = 'Address 2';
$lang['Namecheap.whois.AdminCity'] = 'City';
$lang['Namecheap.whois.AdminStateProvince'] = 'State/Province';
//$lang['Namecheap.whois.AdminStateProvinceChoice'] = "State/Province Choice";
$lang['Namecheap.whois.AdminPostalCode'] = 'Postal Code';
$lang['Namecheap.whois.AdminCountry'] = 'Country';
$lang['Namecheap.whois.AdminPhone'] = 'Phone';
//$lang['Namecheap.whois.AdminPhoneExt'] = "Phone Extension";
//$lang['Namecheap.whois.AdminFax'] = "Fax";
$lang['Namecheap.whois.AdminEmailAddress'] = 'Email';

//$lang['Namecheap.whois.AuxBillingOrganizationName'] = "Organization";
//$lang['Namecheap.whois.AuxBillingJobTitle'] = "Job Title";
$lang['Namecheap.whois.AuxBillingFirstName'] = 'First Name';
$lang['Namecheap.whois.AuxBillingLastName'] = 'Last Name';
$lang['Namecheap.whois.AuxBillingAddress1'] = 'Address 1';
$lang['Namecheap.whois.AuxBillingAddress2'] = 'Address 2';
$lang['Namecheap.whois.AuxBillingCity'] = 'City';
$lang['Namecheap.whois.AuxBillingStateProvince'] = 'State/Province';
//$lang['Namecheap.whois.AuxBillingStateProvinceChoice'] = "State/Province Choice";
$lang['Namecheap.whois.AuxBillingPostalCode'] = 'Postal Code';
$lang['Namecheap.whois.AuxBillingCountry'] = 'Country';
$lang['Namecheap.whois.AuxBillingPhone'] = 'Phone';
//$lang['Namecheap.whois.AuxBillingPhoneExt'] = "Phone Extension";
//$lang['Namecheap.whois.AuxBillingFax'] = "Fax";
$lang['Namecheap.whois.AuxBillingEmailAddress'] = 'Email';

//$lang['Namecheap.whois.BillingFirstName'] = "First Name";
//$lang['Namecheap.whois.BillingLastName'] = "Last Name";
//$lang['Namecheap.whois.BillingAddress1'] = "Address 1";
//$lang['Namecheap.whois.BillingAddress2'] = "Address 2";
//$lang['Namecheap.whois.BillingCity'] = "City";
//$lang['Namecheap.whois.BillingStateProvince'] = "State/Province";
//$lang['Namecheap.whois.BillingStateProvinceChoice'] = "State/Province Choice";
//$lang['Namecheap.whois.BillingPostalCode'] = "Postal Code";
//$lang['Namecheap.whois.BillingCountry'] = "Country";
//$lang['Namecheap.whois.BillingPhone'] = "Phone";
//$lang['Namecheap.whois.BillingPhoneExt'] = "Phone Extension";
//$lang['Namecheap.whois.BillingFax'] = "Fax";
//$lang['Namecheap.whois.BillingEmailAddress'] = "Email";

// .US domain fields
$lang['Namecheap.domain.RegistrantNexus'] = 'Registrant Type';
$lang['Namecheap.domain.RegistrantNexus.c11'] = 'US citizen';
$lang['Namecheap.domain.RegistrantNexus.c12'] = 'Permanent resident of the US';
$lang['Namecheap.domain.RegistrantNexus.c21'] = 'US entity or organization';
$lang['Namecheap.domain.RegistrantNexus.c31'] = 'Foreign organization';
$lang['Namecheap.domain.RegistrantNexus.c32'] = 'Foreign organization with an office in the US';
$lang['Namecheap.domain.RegistrantPurpose'] = 'Purpose';
$lang['Namecheap.domain.RegistrantPurpose.p1'] = 'Business';
$lang['Namecheap.domain.RegistrantPurpose.p2'] = 'Non-profit';
$lang['Namecheap.domain.RegistrantPurpose.p3'] = 'Personal';
$lang['Namecheap.domain.RegistrantPurpose.p4'] = 'Educational';
$lang['Namecheap.domain.RegistrantPurpose.p5'] = 'Governmental';

// .EU domain fields
$lang['Namecheap.domain.EUAgreeWhoisPolicy'] = 'Whois Policy';
$lang['Namecheap.domain.EUAgreeWhoisPolicy.yes'] = 'I hereby agree that the Registry is entitled to transfer the data contained in this application to third parties(i) if ordered to do so by a public authority, carrying out its legitimate tasks; and (ii) upon demand of an ADR Provider as mentioned in section 16 of the Terms and Conditions which are published at www.eurid.eu; and (iii) as provided in Section 2 (WHOIS look-up facility) of the .eu Domain Name WHOIS Policy which is published at www.eurid.eu.';
$lang['Namecheap.domain.EUAgreeDeletePolicy'] = 'Deleteion Rules';
$lang['Namecheap.domain.EUAgreeDeletePolicy.yes'] = 'I agree and acknowledge to the special renewal and expiration terms set forth below for this domain name, including those terms set forth in the Registration Agreement. I understand that unless I have set this domain for autorenewal, this domain name must be explicitly renewed by the expiration date or the 20th of the month of expiration, whichever is sooner. (e.g. If the name expires on Sept 4th, 2008, then a manual renewal must be received by Sept 4th, 2008. If name expires on Sep 27th, 2008, the renewal request must be received prior to Sep 20th, 2008). If the name is not manually renewed or previously set to autorenew, a delete request will be issued by Namecheap. When a delete request is issued, the name will remain fully functional in my account until expiration, but will no longer be renewable nor will I be able to make any modifications to the name. These terms are subject to change.';

// .CA domain fields
$lang['Namecheap.domain.CIRALegalType'] = 'Legal Type';
$lang['Namecheap.domain.RegistrantPurpose.cco'] = 'Corporation';
$lang['Namecheap.domain.RegistrantPurpose.cct'] = 'Canadian citizen';
$lang['Namecheap.domain.RegistrantPurpose.res'] = 'Canadian resident';
$lang['Namecheap.domain.RegistrantPurpose.gov'] = 'Government entity';
$lang['Namecheap.domain.RegistrantPurpose.edu'] = 'Educational';
$lang['Namecheap.domain.RegistrantPurpose.ass'] = 'Unincorporated Association';
$lang['Namecheap.domain.RegistrantPurpose.hop'] = 'Hospital';
$lang['Namecheap.domain.RegistrantPurpose.prt'] = 'Partnership';
$lang['Namecheap.domain.RegistrantPurpose.tdm'] = 'Trade-mark';
$lang['Namecheap.domain.RegistrantPurpose.trd'] = 'Trade Union';
$lang['Namecheap.domain.RegistrantPurpose.plt'] = 'Political Party';
$lang['Namecheap.domain.RegistrantPurpose.lam'] = 'Libraries, Archives and Museums';
$lang['Namecheap.domain.RegistrantPurpose.trs'] = 'Trust';
$lang['Namecheap.domain.RegistrantPurpose.abo'] = 'Aboriginal Peoples';
$lang['Namecheap.domain.RegistrantPurpose.inb'] = 'Indian Band';
$lang['Namecheap.domain.RegistrantPurpose.lgr'] = 'Legal Representative';
$lang['Namecheap.domain.RegistrantPurpose.omk'] = 'Official Mark';
$lang['Namecheap.domain.RegistrantPurpose.maj'] = 'The Queen';
$lang['Namecheap.domain.CIRAWhoisDisplay'] = 'Whois';
$lang['Namecheap.domain.CIRAWhoisDisplay.full'] = 'Make Public';
$lang['Namecheap.domain.CIRAWhoisDisplay.private'] = 'Keep Private';

// .CO.UK domain fields
$lang['Namecheap.domain.COUKLegalType'] = 'Legal Type';
$lang['Namecheap.domain.COUKLegalType.ind'] = 'UK individual';
$lang['Namecheap.domain.COUKLegalType.find'] = 'Non-UK individual';
$lang['Namecheap.domain.COUKLegalType.ltd'] = 'UK Limited Company';
$lang['Namecheap.domain.COUKLegalType.plc'] = 'UK Public Limited Company';
$lang['Namecheap.domain.COUKLegalType.ptnr'] = 'UK Partnership';
$lang['Namecheap.domain.COUKLegalType.llp'] = 'UK Limited Liability Partnership';
$lang['Namecheap.domain.COUKLegalType.ip'] = 'UK Industrial/Provident Registered Company';
$lang['Namecheap.domain.COUKLegalType.stra'] = 'UK Sole Trader';
$lang['Namecheap.domain.COUKLegalType.sch'] = 'UK School';
$lang['Namecheap.domain.COUKLegalType.rchar'] = 'UK Registered Charity';
$lang['Namecheap.domain.COUKLegalType.gov'] = 'UK Government Body';
$lang['Namecheap.domain.COUKLegalType.other'] = 'UK Entity (other)';
$lang['Namecheap.domain.COUKLegalType.crc'] = 'UK Corporation by Royal Charter';
$lang['Namecheap.domain.COUKLegalType.fcorp'] = 'Foreign Organization';
$lang['Namecheap.domain.COUKLegalType.stat'] = 'UK Statutory Body FIND';
$lang['Namecheap.domain.COUKLegalType.fother'] = 'Other Foreign Organizations';
$lang['Namecheap.domain.COUKCompanyID'] = 'Company ID Number';
$lang['Namecheap.domain.COUKRegisteredfor'] = 'Registrant Name';

// .ME.UK domain fields
$lang['Namecheap.domain.MEUKLegalType'] = 'Legal Type';
$lang['Namecheap.domain.MEUKLegalType.ind'] = 'UK individual';
$lang['Namecheap.domain.MEUKLegalType.find'] = 'Non-UK individual';
$lang['Namecheap.domain.MEUKLegalType.ltd'] = 'UK Limited Company';
$lang['Namecheap.domain.MEUKLegalType.plc'] = 'UK Public Limited Company';
$lang['Namecheap.domain.MEUKLegalType.ptnr'] = 'UK Partnership';
$lang['Namecheap.domain.MEUKLegalType.llp'] = 'UK Limited Liability Partnership';
$lang['Namecheap.domain.MEUKLegalType.ip'] = 'UK Industrial/Provident Registered Company';
$lang['Namecheap.domain.MEUKLegalType.stra'] = 'UK Sole Trader';
$lang['Namecheap.domain.MEUKLegalType.sch'] = 'UK School';
$lang['Namecheap.domain.MEUKLegalType.rchar'] = 'UK Registered Charity';
$lang['Namecheap.domain.MEUKLegalType.gov'] = 'UK Government Body';
$lang['Namecheap.domain.MEUKLegalType.other'] = 'UK Entity (other)';
$lang['Namecheap.domain.MEUKLegalType.crc'] = 'UK Corporation by Royal Charter';
$lang['Namecheap.domain.MEUKLegalType.fcorp'] = 'Foreign Organization';
$lang['Namecheap.domain.MEUKLegalType.stat'] = 'UK Statutory Body FIND';
$lang['Namecheap.domain.MEUKLegalType.fother'] = 'Other Foreign Organizations';
$lang['Namecheap.domain.MEUKCompanyID'] = 'Company ID Number';
$lang['Namecheap.domain.MEUKRegisteredfor'] = 'Registrant Name';

// .ORG.UK domain fields
$lang['Namecheap.domain.ORGUKLegalType'] = 'Legal Type';
$lang['Namecheap.domain.ORGUKLegalType.ind'] = 'UK individual';
$lang['Namecheap.domain.ORGUKLegalType.find'] = 'Non-UK individual';
$lang['Namecheap.domain.ORGUKLegalType.ltd'] = 'UK Limited Company';
$lang['Namecheap.domain.ORGUKLegalType.plc'] = 'UK Public Limited Company';
$lang['Namecheap.domain.ORGUKLegalType.ptnr'] = 'UK Partnership';
$lang['Namecheap.domain.ORGUKLegalType.llp'] = 'UK Limited Liability Partnership';
$lang['Namecheap.domain.ORGUKLegalType.ip'] = 'UK Industrial/Provident Registered Company';
$lang['Namecheap.domain.ORGUKLegalType.stra'] = 'UK Sole Trader';
$lang['Namecheap.domain.ORGUKLegalType.sch'] = 'UK School';
$lang['Namecheap.domain.ORGUKLegalType.rchar'] = 'UK Registered Charity';
$lang['Namecheap.domain.ORGUKLegalType.gov'] = 'UK Government Body';
$lang['Namecheap.domain.ORGUKLegalType.other'] = 'UK Entity (other)';
$lang['Namecheap.domain.ORGUKLegalType.crc'] = 'UK Corporation by Royal Charter';
$lang['Namecheap.domain.ORGUKLegalType.fcorp'] = 'Foreign Organization';
$lang['Namecheap.domain.ORGUKLegalType.stat'] = 'UK Statutory Body FIND';
$lang['Namecheap.domain.ORGUKLegalType.fother'] = 'Other Foreign Organizations';
$lang['Namecheap.domain.ORGUKCompanyID'] = 'Company ID Number';
$lang['Namecheap.domain.ORGUKRegisteredfor'] = 'Registrant Name';

// .ASIA domain fields
$lang['Namecheap.domain.ASIALegalEntityType'] = 'Legal Type';
$lang['Namecheap.domain.ASIALegalEntityType.corporation'] = 'Corporations or Companies';
$lang['Namecheap.domain.ASIALegalEntityType.cooperative'] = 'Cooperatives';
$lang['Namecheap.domain.ASIALegalEntityType.partnership'] = 'Partnerships or Collectives';
$lang['Namecheap.domain.ASIALegalEntityType.government'] = 'Government Bodies';
$lang['Namecheap.domain.ASIALegalEntityType.politicalParty'] = 'Political parties or Trade Unions';
$lang['Namecheap.domain.ASIALegalEntityType.society'] = 'Trusts, Estates, Associations or Societies';
$lang['Namecheap.domain.ASIALegalEntityType.institution'] = 'Institutions';
$lang['Namecheap.domain.ASIALegalEntityType.naturalPerson'] = 'Natural Persons';
$lang['Namecheap.domain.ASIAIdentForm'] = 'Form of Identity';
$lang['Namecheap.domain.ASIAIdentForm.certificate'] = 'Certificate of Incorporation';
$lang['Namecheap.domain.ASIAIdentForm.legislation'] = 'Charter';
$lang['Namecheap.domain.ASIAIdentForm.societyRegistry'] = 'Societies Registry';
$lang['Namecheap.domain.ASIAIdentForm.politicalPartyRegistry'] = 'Political Party Registry';
$lang['Namecheap.domain.ASIAIdentForm.passport'] = 'Passport/ Citizenship ID';
$lang['Namecheap.domain.ASIAIdentNumber'] = 'Identity Number';

// .FR domain fields
$lang['Namecheap.!tooltip.FRRegistrantBirthDate'] = 'Set your birth date in the format: YYYY-MM-DD';
$lang['Namecheap.!tooltip.FRRegistrantLegalId'] = 'The SIREN number is the first part of the SIRET NUMBER and consists of 9 digits. The SIRET number is a unique identification number with 14 digits.';
$lang['Namecheap.!tooltip.FRRegistrantDunsNumber'] = 'The DUNS number consists of 9 digits, issued by Dun & Bradstreet.';
$lang['Namecheap.!tooltip.FRRegistrantJoDateDec'] = 'French associations listed with the Journal Officiel de la République Francaise should set a declaration date in the format: YYYY-MM-DD';
$lang['Namecheap.!tooltip.FRRegistrantJoDatePub'] = 'Enter the publication date in the Journal Officiel in the format: YYYY-MM-DD';

$lang['Namecheap.domain.FRLegalType'] = 'Legal Type';
$lang['Namecheap.domain.FRLegalType.individual'] = 'Individual';
$lang['Namecheap.domain.FRLegalType.company'] = 'Company';
$lang['Namecheap.domain.FRRegistrantBirthDate'] = 'Birth Date';
$lang['Namecheap.domain.FRRegistrantBirthplace'] = 'Birth Place';
$lang['Namecheap.domain.FRRegistrantLegalId'] = 'SIREN/SIRET Number';
$lang['Namecheap.domain.FRRegistrantTradeNumber'] = 'Trademark Number';
$lang['Namecheap.domain.FRRegistrantDunsNumber'] = 'DUNS Number';
$lang['Namecheap.domain.FRRegistrantLocalId'] = 'European Economic Area Local ID';
$lang['Namecheap.domain.FRRegistrantJoDateDec'] = 'The Journal Official Declaration Date';
$lang['Namecheap.domain.FRRegistrantJoDatePub'] = 'The Journal Official Publication Date';
$lang['Namecheap.domain.FRRegistrantJoNumber'] = 'The Journal Official Number';
$lang['Namecheap.domain.FRRegistrantJoPage'] = 'The Journal Official Announcement Page Number';



// Errors
$lang['Namecheap.!error.FRLegalType.format'] = 'Please select a valid Legal Type';
$lang['Namecheap.!error.FRRegistrantBirthDate.format'] = 'Please set your birth date in the format: YYYY-MM-DD';
$lang['Namecheap.!error.FRRegistrantBirthplace.format'] = 'Please set your birth place.';
$lang['Namecheap.!error.FRRegistrantLegalId.format'] = 'Please set your SIREN/SIRET Number';
$lang['Namecheap.!error.FRRegistrantTradeNumber.format'] = 'Please set your Trademark Number.';
$lang['Namecheap.!error.FRRegistrantDunsNumber.format'] = 'Please set your DUNS Number.';
$lang['Namecheap.!error.FRRegistrantLocalId.format'] = 'Please set your EEA Local ID.';
$lang['Namecheap.!error.FRRegistrantJoDateDec.format'] = 'Please set the Journal Declaration Date in the format: YYYY-MM-DD';
$lang['Namecheap.!error.FRRegistrantJoDatePub.format'] = 'Please set the Journal Publication Date in the format: YYYY-MM-DD';
$lang['Namecheap.!error.FRRegistrantJoNumber.format'] = 'Please set the Journal Number.';
$lang['Namecheap.!error.FRRegistrantJoPage.format'] = 'Please set the Journal Announcement Page Number.';
