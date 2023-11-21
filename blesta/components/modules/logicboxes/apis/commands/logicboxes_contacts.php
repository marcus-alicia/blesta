<?php
/**
 * LogicBoxes Contact Management
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package logicboxes.commands
 */
class LogicboxesContacts
{
    /**
     * @var LogicboxesApi
     */
    private $api;

    /**
     * Sets the API to use for communication
     *
     * @param LogicboxesApi $api The API to use for communication
     */
    public function __construct(LogicboxesApi $api)
    {
        $this->api = $api;
    }

    /**
     * Adds a Contact to the domain using the details provided.
     *
     * @param array $vars An array of input params including:
     *  - name Name of the contact
     *  - company Name of the company
     *  - email Email address of the contact
     *  - address-line-1 First line of address of the contact
     *  - city Name of the City
     *  - country Country Code as per ISO 3166-1 alpha-2
     *  - zipcode ZIP code
     *  - phone-cc Telephone number country code
     *  - phone Telephone number
     *  - customer-id The Customer under whom you want to create the Contact
     *  - type The Contact Type. This can take following values:
     *      Contact, CaContact, CnContact, CoContact, CoopContact, DeContact,
     *      EsContact, EuContact, NlContact, RuContact, UkContact
     *  - address-line-2 Second line of address of the contact
     *  - address-line-3 Third line of address of the contact
     *  - state Name of the state
     *  - fax-cc Fax number country code
     *  - fax Fax number
     *  - attr-name Mapping key of any extra information to be associated for the contact
     *      that is being added. Refer the description of attr-value.
     *      - .ASIA:
     *          - locality Two-lettered Country code
     *          - legalentitytype naturalPerson, corporation, cooperative, partnership,
     *              government, politicalParty, society, institution, other
     *          - identform passport, certificate, legislation, societyRegistry, politicalPartyRegistry, other
     *          - otheridentform Mention Identity form. Mandatory if identform chosen as 'other'
     *      - .CA:
     *          - CPR CCO, CCT, RES, GOV, EDU, ASS, HOP, PRT, TDM, TRD, PLT, LAM, TRS, ABO, INB, LGR, OMK, MAJ
     *          - AgreementVersion Value of the GetRegistrant Agreement version
     *          - AgreementValue 'y' indicating that the Registrant has agreed to the .CA Registrant Agreement
     *      - .COOP:
     *          - sponsor ROID of Sponsor (Co-operative Reference)
     *      - .ES:
     *          - es_form_juridica 1,39,47,59,68,124,150,152,164,181,197,203,229,269,286,365,434,
     *              436,439,476,510,524,525,554,560,562,566,608,612,713,717,744,745,746,747,877,878,879
     *          - es_tipo_identificacion 1 (DNI or NIF), 3 (NIE), 0 (Other ID)
     *          - es_identificacion Depending upon which es_tipo_identificacion you provided,
     *              mention that ID's number as a value
     *      - .NL
     *          - legalForm PERSOON, ANDERS
     *      - .PRO:
     *          - profession One of the following:
     *              Acupuncturists,Allied Health Professionals,Ambulance Services,Architects,
     *              Asbestos Removal Professionals,Barbers and Barber Shops,Certified Financial Analysts,
     *              Certified Financial Planners,Certified Public Accountants,Check Cashers,Chiropractors,
     *              Contractors, Home Improvement,Cosmetologists and Aestheticians,Debt Collectors,
     *              Dentists and Dental Hygienists,Dieticians and Nutritionists,Doctors,Educators,
     *              Electricians,Electrologists,Emergency Medical Technician,Engineers and Land Surveyors,
     *              Finance Companies,Financial Professional,Funeral Services,Health Care,
     *              Hearing Instrument Specialists,Home Inspectors,HVAC Technicians,Insurance,
     *              Investment Advisors,Landscape Architects,Lawyers,Lead Paint Inspectors,
     *              Manufactured Building Producers,Massage Therapy,Money Transmitters,
     *              Mortgage Lenders and Brokers,Municipal Building Inspectors,Nurses and Nurse Aides,
     *              Nursing Home Administrators,Nutritionists,Opticians,Optometrists,Perfusionist,
     *              Pharmacists,Physical Therapists,Physician Assistants,Physicians,
     *              Plumbers and Gas Fitters,Podiatrists,Psychologists,Public Relations,
     *              Radio and TV Technicians,Real Estate,Real Estate Appraisers,
     *              Respiratory Therapists,Sanitarians,Social Workers,Speech Pathologists and Audiologists,
     *              Veterinarians,Water Plant Operator,X-Ray Technicians,Internet Professional,
     *              Medical Professional,Legal Professional,
     *              Other (If your profession does not match any of the above mentioned values,
     *                  you may provide your own value and it will be included under Other in our system.)
     *      - .RU:
     *          - contract-type PRS (individual), ORG (organization)
     *          - birth-date DD.MM.YYYY format
     *          - org-r This value indicates the legally registered company name as mentioned
     *              in the Company Formation documents. This value needs to include at least 2
     *              words and is mandatory for the Organization Contact Type.
     *          - person-r This is the Registrant's full name (preferably in Russian).
     *              It is mandatory to send this name-value pair for Individual Contact Type.
     *          - address-r This value indicates the Registrant's address (preferably in Russian).
     *              This value needs to include at least 2 words and is mandatory for the Organization Contact Type.
     *          - kpp This value is the Territory-linked Taxpayer number. This is a 9 digit number
     *              and is mandatory for the Organization Contact Type, when the Country is Russia.
     *          - code This value is the Taxpayer Identification Number (TIN). This is a 10
     *              digit number and is mandatory for Organization Contact Type, when the Country is Russia.
     *          - passport This is value needs to include the Document number, Issued by,
     *              Issued Date details. It is mandatory to send this name-value pair for Individual Contact Type.
     *      - .US:
     *          - purpose P1, P2, P3, P4, P5
     *          - category C11, C12, C21, C31, C32
     *  - attr-value Mapping value of the extra details required to be associated with a particular
     *      Contact before registering a domain name. This together with attr-name shall contain the extra details.
     * @return LogicboxesResponse
     */
    public function add(array $vars)
    {
        return $this->api->submit('contacts/add', $vars);
    }

    /**
     * Modifies the details of the specified Contact.
     *
     * @param array $vars An array of input params including:
     *  - contact-id The Contact whose details you want to modify
     *  - name Name of the contact
     *  - company Name of the company
     *  - email Email address of the contact
     *  - address-line-1 First line of address of the contact
     *  - city Name of the City
     *  - country Country Code as per ISO 3166-1 alpha-2
     *  - zipcode ZIP code
     *  - phone-cc Telephone number country code
     *  - phone Telephone number
     *  - address-line-2 Second line of address of the contact
     *  - address-line-3 Third line of address of the contact
     *  - state Name of the state
     *  - fax-cc Fax number country code
     *  - fax Fax number
     * @return LogicboxesResponse
     */
    public function modify(array $vars)
    {
        return $this->api->submit('contacts/modify', $vars);
    }

    /**
     * Gets the details for the specified Contact.
     *
     * @param array $vars An array of input params including:
     *  - contact-id The Contact Id for which details are required
     * @return LogicboxesResponse
     */
    public function details(array $vars)
    {
        return $this->api->submit('contacts/details', $vars, 'GET');
    }

    /**
     * Gets the Contact Details of the Contacts that match the Search criteria.
     *
     * @param array $vars An array of input params including:
     *  - customer-id The Customer for which you want to get the Contact Details
     *  - no-of-records Number of Records to be returned
     *  - page-no Page Number for which records are required
     *  - contact-id Array of Contact Ids for listing of specific Contacts
     *  - status List of Contact statuses. These can take any values from: InActive, Active, Suspended, Deleted
     *  - name Name of Contact
     *  - company Name of the Company
     *  - email Email address of the Contact
     *  - type Type of contact. Valid values are: Contact, CoopContact, UkContact, EuContact,
     *      Sponsor, CnContact, CoContact, CaContact, DeContact, EsContact.
     * @return LogicboxesResponse
     */
    public function search(array $vars)
    {
        return $this->api->submit('contacts/search', $vars, 'GET');
    }

    /**
     * Gets the details of the Default Contacts for the Customer.
     *
     * @param array $vars An array of input params including:
     *  - customer-id The Customer for whom you want to get the Default Contacts
     *  - type Type of default contact to be returned. It can be one or more of following
     *      contacts types : Contact, CoopContact, UkContact, EuContact, CnContact, CoContact,
     *      CaContact, DeContact, EsContact.
     * @return LogicboxesResponse
     */
    public function getDefault(array $vars)
    {
        return $this->api->submit('contacts/default', $vars);
    }

    /**
     * Associates mandatory extra details with the specified Contact to register .US, .COOP,
     * .ASIA, .CA, .ES, .RU, .PRO and .NL domain names.
     *
     * @param array $vars An array of input params including:
     *  - contact-id The Contact with which you want to associate extra details
     *  - attr-name Mapping key of any extra information to be associated for the contact that
     *      is being added. Refer the description of attr-value.
     *      - .ASIA:
     *          - locality Two-lettered Country code
     *          - legalentitytype naturalPerson, corporation, cooperative, partnership,
     *              government, politicalParty, society, institution, other
     *          - identform passport, certificate, legislation, societyRegistry, politicalPartyRegistry, other
     *          - otheridentform Mention Identity form. Mandatory if identform chosen as 'other'
     *      - .CA:
     *          - CPR CCO, CCT, RES, GOV, EDU, ASS, HOP, PRT, TDM, TRD, PLT, LAM, TRS, ABO, INB, LGR, OMK, MAJ
     *          - AgreementVersion Value of the GetRegistrant Agreement version
     *          - AgreementValue 'y' indicating that the Registrant has agreed to the .CA Registrant Agreement
     *      - .COOP:
     *          - sponsor ROID of Sponsor (Co-operative Reference)
     *      - .ES:
     *          - es_form_juridica 1,39,47,59,68,124,150,152,164,181,197,203,229,269,286,365,434,436,439,
     *              476,510,524,525,554,560,562,566,608,612,713,717,744,745,746,747,877,878,879
     *          - es_tipo_identificacion 1 (DNI or NIF), 3 (NIE), 0 (Other ID)
     *          - es_identificacion Depending upon which es_tipo_identificacion you provided,
     *              mention that ID's number as a value
     *      - .NL
     *          - legalForm PERSOON, ANDERS
     *      - .PRO:
     *          - profession One of the following:
     *              Acupuncturists,Allied Health Professionals,Ambulance Services,Architects,
     *              Asbestos Removal Professionals,Barbers and Barber Shops,Certified Financial Analysts,
     *              Certified Financial Planners,Certified Public Accountants,Check Cashers,
     *              Chiropractors,Contractors, Home Improvement,Cosmetologists and Aestheticians,Debt Collectors,
     *              Dentists and Dental Hygienists,Dieticians and Nutritionists,Doctors,Educators,
     *              Electricians,Electrologists,Emergency Medical Technician,Engineers and Land Surveyors,
     *              Finance Companies,Financial Professional,Funeral Services,Health Care,
     *              Hearing Instrument Specialists,Home Inspectors,HVAC Technicians,Insurance,Investment Advisors,
     *              Landscape Architects,Lawyers,Lead Paint Inspectors,Manufactured Building Producers,
     *              Massage Therapy,Money Transmitters,Mortgage Lenders and Brokers,
     *              Municipal Building Inspectors,Nurses and Nurse Aides,Nursing Home Administrators,
     *              Nutritionists,Opticians,Optometrists,Perfusionist,Pharmacists,Physical Therapists,
     *              Physician Assistants,Physicians,Plumbers and Gas Fitters,Podiatrists,
     *              Psychologists,Public Relations,Radio and TV Technicians,Real Estate,
     *              Real Estate Appraisers,Respiratory Therapists,Sanitarians,Social Workers,
     *              Speech Pathologists and Audiologists,Veterinarians,Water Plant Operator,
     *              X-Ray Technicians,Internet Professional,Medical Professional,Legal Professional,
     *              Other (If your profession does not match any of the above mentioned values,
     *                  you may provide your own value and it will be included under Other in our system.)
     *      - .RU:
     *          - contract-type PRS (individual), ORG (organization)
     *          - birth-date DD.MM.YYYY format
     *          - org-r This value indicates the legally registered company name as mentioned in
     *              the Company Formation documents. This value needs to include at least 2
     *              words and is mandatory for the Organization Contact Type.
     *          - person-r This is the Registrant's full name (preferably in Russian).
     *              It is mandatory to send this name-value pair for Individual Contact Type.
     *          - address-r This value indicates the Registrant's address (preferably in Russian).
     *              This value needs to include at least 2 words and is mandatory for the Organization Contact Type.
     *          - kpp This value is the Territory-linked Taxpayer number. This is a 9 digit
     *              number and is mandatory for the Organization Contact Type, when the Country is Russia.
     *          - code This value is the Taxpayer Identification Number (TIN). This is a 10
     *              digit number and is mandatory for Organization Contact Type, when the Country is Russia.
     *          - passport This is value needs to include the Document number, Issued by,
     *              Issued Date details. It is mandatory to send this name-value pair for Individual Contact Type.
     *      - .US:
     *          - purpose P1, P2, P3, P4, P5
     *          - category C11, C12, C21, C31, C32
     *  - attr-value Mapping value of the extra details required to be associated with a
     *      particular Contact before registering a domain name. This together with
     *      attr-name shall contain the extra details.
     *  - product-key The product keys for which the details are to be associated.
     *      They can be one of the set (domus | dotcoop | dotasia | dotca | dotes | dotpro | dotnl).
     * @return LogicboxesResponse
     */
    public function setDetails(array $vars)
    {
        return $this->api->submit('contacts/set-details', $vars);
    }

    /**
     * Deletes the specified Contact.
     *
     * @param array $vars An array of input params including:
     *  - contact-id The Contact that you want to delete
     * @return LogicboxesResponse
     */
    public function delete(array $vars)
    {
        return $this->api->submit('contacts/delete', $vars);
    }

    /**
     * Gets a list of system default .COOP Sponsors (Co-operative Reference) and associated
     * Sponsor of the specified Customer.
     *
     * @param array $vars An array of input params including:
     *  - customer-id The Customer for whom you want to get the list of Sponsors
     * @return LogicboxesResponse
     */
    public function sponsors(array $vars)
    {
        return $this->api->submit('contacts/sponsors', $vars, 'GET');
    }

    /**
     * Validates the Registrant Contact(s) against the eligibility criteria(s) provided.
     *
     * @param array $vars An array of input params including:
     *  - contact-id The Registrant Contact Id(s) which you want to validate
     *  - eligibility-criteria To validate the Registrant Contact(s), pass the appropriate
     *      eligibility-criteria(s) corresponding to the domain name extension:
     *      - .ASIA CED_ASIAN_COUNTRY and CED_DETAILS
     *      - .CA CPR
     *      - .COOP SPONSORS
     *      - .ES ES_CONTACT_IDENTIFICATION_DETAILS
     *      - .EU EUROPEAN_COUNTRY
     *      - .RU RU_CONTACT_INFO
     *      - .US APP_PREF_NEXUS
     * @return LogicboxesResponse
     */
    public function validateRegistrant(array $vars)
    {
        return $this->api->submit('contacts/validate-registrant', $vars, 'GET');
    }
}
