<?php
/**
 * LogicBoxes Domain Management
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package logicboxes.commands
 */
class LogicboxesDomains
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
     * Checks the availability of the specified domain name(s).
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name(s) that you need to check the availability for.
     *  - tlds TLDs for which the domain name availability needs to be checked.
     *  - suggest-alternative Pass true if domain name suggestions are required. Default value is false. (optional)
     * @return LogicboxesResponse
     */
    public function available(array $vars)
    {
        return $this->api->submit('domains/available', $vars, 'GET');
    }

    /**
     * Checks the availability of the specified Internationalized Domain Name(s) (IDN).
     *
     * @param array $vars An array of input params including:
     *  - domain-name Internationalized Domain Name(s) that you need to check the availability for.
     *  - tld TLD for which the domain name availability needs to be checked.
     *  - idnLanguageCode While performing check availability for an Internationalized Domain Name,
     *      you need to provide the corresponding language code (2 or 3 characters)
     * @return LogicboxesResponse
     */
    public function idnAvailable(array $vars)
    {
        return $this->api->submit('domains/idn-available', $vars, 'GET');
    }

    /**
     * Suggests domain names for the given keyword.
     *
     * @param array $vars An array of input params including:
     *  - keyword Search term (keyword or phrase) e.g. "search" or "search world".
     *  - tlds TLDs you want to search in.
     *  - no-of-results Maximum number of suggestions to be returned.
     *  - hyphen-allowed Default value is false. Recommended value is true.
     *      If true is passed, generates suggestions with hyphens (Dashes) "-". (optional)
     *  - add-related Default value is false. Recommended value is true.
     *      If true is passed, generates suggestions with related keywords. (optional)
     * @return LogicboxesResponse
     */
    public function suggestNames(array $vars)
    {
        return $this->api->submit('domains/suggest-names', $vars, 'GET');
    }

    /**
     * Registers a domain name.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name that you need to Register (including TLD).
     *  - years Number of years for which you wish to Register this domain name.
     *  - ns An array of name servers of the domain name
     *  - customer-id The Customer for whom you wish to Register this domain name.
     *  - reg-contact-id The Registrant Contact of the domain name.
     *  - admin-contact-id The Administrative Contact of the domain name. Pass -1 for the following TLDs:
     *      .EU, .UK, .NZ, .RU
     *  - tech-contact-id The Technical Contact of the domain name. Pass -1 for the following TLDs:
     *      .EU, .UK, .NZ, .RU
     *  - billing-contact-id The Billing Contact of the domain name. Pass -1 for the following TLDs:
     *      .EU, .UK, .NZ, .CA, .RU, .NL
     *  - invoice-option This will decide how the Customer Invoice will be handled.
     *      Set any of below mentioned Invoice Options for your Customer:
     *      - NoInvoice This will not raise any Invoice. The Order will be executed.
     *      - PayInvoice This will raise an invoice and if there are sufficient funds in
     *          the customer's account the invoice will be paid and the order will be executed,
     *          else the order will remain pending in the system
     *      - KeepInvoice This will raise an Invoice for the Customer to pay later. The Order will be executed.
     *  - protect-privacy Enables / Disables the Privacy Protection setting for the domain name.
     *      Not supported for the following TLDs:
     *      .US, .IN, .EU, .UK, .ASIA, .TEL, .CN, .NZ, .CO, .CA, .DE, .ES, .AU, .RU, .PRO, .NL, .SX, .HN
     *  - attr-name Mapping key of the extra details needed to register a domain name. (optional)
     *      - idnLanguageCode
     *      - Required for .ASIA:
     *          - cedcontactid The contact ID of either Admin, Technical, Billing, or Registrant
     *      - Required for .AU:
     *          - id-type One of either:
     *              - ACN This is the Registrant's Australian Company Number.
     *              - ABN This is the Registrant's Australian Business Number.
     *              - VIC BN This is the Registrant's Victoria Business Number.
     *              - NSW BN This is the Registrant's New South Wales Business Number.
     *              - SA BN This is the Registrant's South Australia Business Number.
     *              - NT BN This is the Registrant's Northern Territory Business Number.
     *              - WA BN This is the Registrant's Western Australia Business Number.
     *              - TAS BN This is the Registrant's Tasmania Business Number.
     *              - ACT BN This is the Registrant's Australian Capital Territory Business Number.
     *              - QLD BN This is the Registrant's Queensland Business Number.
     *              - TM This is the Registrant's Trademark number.
     *              - ARBN This is the Registrant's Registrant's Australian Registered Body Number
     *              - Other
     *          - id Mention the appropriate ID as the value for this attr-name,
     *              depending upon the EligibilityID Type selected.
     *          - policyReason Mention the appropriate Eligibility Reason as the value for this attr-name.
     *          - isAUWarranty You need to display and accept a warranty from the Registrant.
     *              When sending this attribute, it's value needs to be true.
     *          - eligibilityType This is mandatory for only the id-type values Trademark and Other.
     *          - eligibilityName This is mandatory for only the id-type value Trademark.
     *              Mention the appropriate Eligibility Name (company name) as the value for this attr-name.
     *          - registrantName This is mandatory for only the id-type values VIC BN,
     *              NSW BN, SA BN, NT BN, WA BN, TAS BN, ACT BN, QLD BN, Trademark and Other.
     *              Mention the appropriate Registrant Name as the value for this attr-name.
     *              This value needs to be the proprietor's name and the proprietor should be an individual.
     *      - Required for .CN:
     *          - cnhosting This parameter indicates that the domain name will be hosted
     *              in China. The value needs to be passed as true.
     *          - cnhostingclause Through this parameter, the Registrant agrees to the terms
     *              and conditions for hosting the domain name in China. The value needs to be passed as yes.
     *  - attr-value Mapping value of the extra details required to register a domain name.
     *      This together with attr-name shall contain the extra details. (optional)
     * @return LogicboxesResponse
     */
    public function register(array $vars)
    {
        return $this->api->submit('domains/register', $vars);
    }

    /**
     * Transfers a domain name.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Specify the domain name that you want to transfer.
     *  - auth-code Authorization Code (a.k.a. Domain Secret) of the domain name that you
     *      want to transfer. The Authorization Code would be required to transfer a domain name
     *      of any of the following TLDs (extensions):
     *      .AU, .BIZ, .BZ, .CA, .CC, .CO, .COM, .COOP, .DE, .EU, .IN, .INFO, .MN, .MOBI,
     *      .NAME, .NET, .NL, .NZ, .ORG, .TV, .US, .WS, .XXX
     *  - customer-id The Customer for whom the Order should be added.
     *  - reg-contact-id The Registrant Contact of the domain name.
     *  - admin-contact-id The Administrative Contact of the domain name. Pass -1 for the following TLDs:
     *      .EU, .UK, .NZ, .RU
     *  - tech-contact-id The Technical Contact of the domain name. Pass -1 for the following TLDs:
     *      .EU, .UK, .NZ, .RU
     *  - billing-contact-id The Billing Contact of the domain name. Pass -1 for the following TLDs:
     *      .EU, .UK, .NZ, .CA, .RU, .NL
     *  - invoice-option This will decide how the Customer Invoice will be handled.
     *      Set any of below mentioned Invoice Options for your Customer:
     *      - NoInvoice This will not raise any Invoice. The Order will be executed.
     *      - PayInvoice This will raise an invoice and if there are sufficient funds in
     *          the customer's account the invoice will be paid and the order will be executed,
     *          else the order will remain pending in the system
     *      - KeepInvoice This will raise an Invoice for the Customer to pay later. The Order will be executed.
     *  - protect-privacy Enables / Disables the Privacy Protection setting for the domain name.
     *      Not supported for the following TLDs:
     *      .US, .IN, .EU, .UK, .ASIA, .TEL, .CN, .NZ, .CO, .CA, .DE, .ES, .AU, .RU, .PRO, .NL, .SX, .HN
     *  - ns An array of name servers of the domain name (optional)
     *  - attr-name Mapping key of the extra details needed to register a domain name. (optional)
     *      - Required for .ASIA:
     *          - cedcontactid The contact ID of either Admin, Technical, Billing, or Registrant
     *  - attr-value Mapping value of the extra details required to register a domain name.
     *      This together with attr-name shall contain the extra details. (optional)
     * @return LogicboxesResponse
     */
    public function transfer(array $vars)
    {
        return $this->api->submit('domains/transfer', $vars);
    }

    /**
     * Checks if a transfer request is valid for the specified domain name.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to check if the transfer request is valid.
     */
    public function validateTransfer(array $vars)
    {
        return $this->api->submit('domains/validate-transfer', $vars);
    }

    /**
     * Renews the specified Domain Registration Order for specified number of years.
     *
     * @param array $vars An array of input params including:
     *  - order-id Order Id of the Domain Registration Order that you want to Renew.
     *  - years Number of years for which you want to Renew this Order.
     *  - exp-date Current Expiry Date of the Order in epoch time format.
     *  - invoice-option This will decide how the Customer Invoice will be handled.
     *      Set any of below mentioned Invoice Options for your Customer:
     *      - NoInvoice This will not raise any Invoice. The Order will be executed.
     *      - PayInvoice This will raise an invoice and if there are sufficient funds in
     *          the customer's account the invoice will be paid and the order will be executed,
     *          else the order will remain pending in the system
     *      - KeepInvoice This will raise an Invoice for the Customer to pay later. The Order will be executed.
     * @return LogicboxesResponse
     */
    public function renew(array $vars)
    {
        return $this->api->submit('domains/renew', $vars);
    }

    /**
     * Gets a list of Domain Registration Orders matching the search criteria, along with the details.
     *
     * @param array $vars An array of input params including:
     *  - no-of-records Number of Orders to be fetched. This should be a value between 10 to 500.
     *  - page-no Page number for which details are to be fetched.
     *  - order-by One or more parameters by which you want to sort the Orders.
     *  - order-id Order Id(s) of the Domain Registration Order(s) whose details need to be fetched.
     *  - reseller-id Reseller Id(s) whose Orders need to be fetched.
     *  - customer-id Customer Id(s) whose Orders need to be fetched.
     *  - show-child-orders Whether Sub-Reseller Orders need to be fetched or not.
     *  - product-key Product keys of the TLDs.
     *  - status Status of the Order, namely, InActive, Active, Suspended,
     *      Pending Delete Restorable, Deleted or Archived
     *  - domain-name Name of the Domain.
     *  - creation-date-start UNIX TimeStamp for listing of Domain Registration
     *      Orders whose Creation Date is greater than creation-date-start.
     *  - creation-date-end UNIX TimeStamp for listing of Domain Registration
     *      Orders whose Creation Date is less than creation-date-end.
     *  - expiry-date-start UNIX TimeStamp for listing of Domain Registration
     *      Orders whose Expiry Date is greater than expiry-date-start.
     *  - expiry-date-end UNIX TimeStamp for listing of Domain Registration
     *      Orders whose Expiry Date is less than expiry-date-end.
     * @return LogicboxesResponse
     */
    public function search(array $vars)
    {
        return $this->api->submit('domains/search', $vars, 'GET');
    }

    /**
     * Gets the default Name Servers of the specified Customer.
     *
     * @param array $vars An array of input params including:
     *  - customer-id The Customer for whom you want to fetch the default Name Servers.
     * @return LogicboxesResponse
     */
    public function customerDefaultNs(array $vars)
    {
        return $this->api->submit('domains/custom-default-ns', $vars, 'GET');
    }

    /**
     * Gets the Order Id of a Registered domain name.
     *
     * @param array $vars An array of input params including:
     *  - domain-name The Registered domain name whose Order Id you want to know.
     * @return LogicboxesResponse
     */
    public function orderid(array $vars)
    {
        return $this->api->submit('domains/orderid', $vars, 'GET');
    }

    /**
     * Gets details of the Domain Registration Order associated with the specified Order Id.
     *
     * @param array $vars An array of input params including:
     *  - order-id Order Id of the Domain Registration Order whose details need to be fetched.
     *  - options Values can be: All, OrderDetails, ContactIds, RegistrantContactDetails,
     *      AdminContactDetails, TechContactDetails, BillingContactDetails, NsDetails, DomainStatus.
     * @return LogicboxesResponse
     */
    public function details(array $vars)
    {
        return $this->api->submit('domains/details', $vars, 'GET');
    }

    /**
     * Gets details of the Domain Registration Order associated with the specified domain name.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name associated with the Domain Registration Order whose details need to be fetched.
     *  - options Values can be: All, OrderDetails, ContactIds, RegistrantContactDetails,
     *      AdminContactDetails, TechContactDetails, BillingContactDetails, NsDetails, DomainStatus.
     * @return LogicboxesResponse
     */
    public function detailsByName(array $vars)
    {
        return $this->api->submit('domains/details-by-name', $vars, 'GET');
    }

    /**
     * Modifies the Name Servers of the specified Domain Registration Order.
     *
     * @param array $vars An array of input params including:
     *  - order-id Order Id of the Domain Registration Order whose Name Servers you want to modify.
     *  - ns New Name Servers.
     * @return LogicboxesResponse
     */
    public function modifyNs(array $vars)
    {
        return $this->api->submit('domains/modify-ns', $vars);
    }

    /**
     * Adds Child Name Servers for the specified Domain Registration Order.
     *
     * @param array $vars An array of input params including:
     *  - order-id Order Id of the Domain Registration Order for which you want to add the Child Name Servers.
     *  - cns Child Name Servers name that you want to add.
     *  - ip IP addresses that you want to associate with the Child Name Servers.
     * @return LogicboxesResponse
     */
    public function addCns(array $vars)
    {
        return $this->api->submit('domains/add-cns', $vars);
    }

    /**
     * Modifies the Host Name of the Child Name Server for the specified Domain Registration Order.
     *
     * @param array $vars An array of input params including:
     *  - order-id Order Id of the Domain Registration Order whose Child Name Server you want to change.
     *  - old-cns Current Child Name Server of the specified Order.
     *  - new-cns New Child Name Server that you want to associate with the Order.
     * @return LogicboxesResponse
     */
    public function modifyCnsName(array $vars)
    {
        return $this->api->submit('domains/modify-cns-name', $vars);
    }

    /**
     * Modifies the IP address associated with the specified Child Name Server
     * of the specified Domain Registration Order.
     *
     * @param array $vars An array of input params including:
     *  - order-id Order Id of the Domain Registration Order whose Child Name Server IP you want to change.
     *  - cns Name of the Child Name Server whose IP you want to change.
     *  - old-ip Currently associated IP address with the specified Child Name Server.
     *  - new-ip New IP address that you want to associate with the specified Child Name Server.
     * @return LogicboxesResponse
     */
    public function modifyCnsIp(array $vars)
    {
        return $this->api->submit('domains/modify-cns-ip', $vars);
    }

    /**
     * Deletes the IP address associated with the specified Child Name Server of
     * the particular Domain Registration Order.
     *
     * @param array $vars An array of input params including:
     *  - order-id Order Id of the Domain Registration Order.
     *  - cns Child Name Server's name for which the IP address needs to be deleted.
     *  - ip IP address that needs to be deleted.
     * @return LogicboxesResponse
     */
    public function deleteCnsIp(array $vars)
    {
        return $this->api->submit('domains/delete-cns-ip', $vars);
    }

    /**
     * Modifies the Contacts of the specified Domain Registration Order.
     *
     * @param array $vars An array of input params including:
     *  - order-id Order Id of the Domain Registration Order whose contact association you want to modify.
     *  - reg-contact-id The Contact that you want to use as the new Registrant Contact.
     *  - admin-contact-id The Contact that you want to use as the new Admin Contact.
     *  - tech-contact-id The Contact that you want to use as the new Technical Contact.
     *  - billing-contact-id The Contact that you want to use as the new Billing Contact.
     * @return LogicboxesResponse
     */
    public function modifyContact(array $vars)
    {
        return $this->api->submit('domains/modify-contact', $vars);
    }

    /**
     * Changes the Privacy Protection status of the specified Domain Registration Order.
     *
     * @param array $vars An array of input params including:
     *  - order-id Order Id of the Domain Registration Order whose privacy protection you want to change.
     *  - protect-privacy Enable / Disable Privacy Protection service. Possible values are: true or false.
     *  - reason The reason to enable / disable Privacy Protection.
     * @return LogicboxesResponse
     */
    public function modifyPrivacyProtection(array $vars)
    {
        return $this->api->submit('domains/modify-privacy-protection', $vars);
    }

    /**
     * Modifies the Auth-Code of the specified Domain Registration Order.
     *
     * @param array $vars An array of input params including:
     *  - order-id Order Id of the Domain Registration Order whose auth-code you want to modify.
     *  - auth-code New auth-code.
     * @return LogicboxesResponse
     */
    public function modifyAuthCode(array $vars)
    {
        return $this->api->submit('domains/modify-auth-code', $vars);
    }

    /**
     * Applies the Theft Protection Lock on the specified Order.
     *
     * @param array $vars An array of input params including:
     *  - order-id Order Id of the Domain Registration Order on which the Theft Protection Lock to be applied.
     * @return LogicboxesResponse
     */
    public function enableTheftProtection(array $vars)
    {
        return $this->api->submit('domains/enable-theft-protection', $vars);
    }

    /**
     * Disables the Theft Protection Lock on the specified order.
     *
     * @param array $vars An array of input params including:
     *  - order-id Order Id of the Domain Registration Order on which the Theft Protection Lock is to be removed.
     * @return LogicboxesResponse
     */
    public function disableTheftProtection(array $vars)
    {
        return $this->api->submit('domains/disable-theft-protection', $vars);
    }

    /**
     * Gets the list of the Locks applied on the specified Domain Registration Order.
     *
     * @param array $vars An array of input params including:
     *  - order-id Order Id of the Domain Registration Order whose list of the locks need to be fetched.
     * @return LogicboxesResponse
     */
    public function locks(array $vars)
    {
        return $this->api->submit('domains/locks', $vars, 'GET');
    }

    /**
     * Resends the Transfer Approval Mail for the specified Order.
     *
     * @param array $vars An array of input params including:
     *  - order-id Order ID for which the RFA mail needs to be resent.
     * @return LogicboxesResponse
     */
    public function resendRfa(array $vars)
    {
        return $this->api->submit('domains/resend-rfa', $vars);
    }

    /**
     * Cancels the Transfer-In Order that is awaiting Admin approval.
     *
     * @param array $vars An array of input params including:
     *  - order-id Order Id of the Transfer request that you want to Cancel
     * @return LogicboxesResponse
     */
    public function cancelTransfer(array $vars)
    {
        return $this->api->submit('domains/cancel-transfer', $vars);
    }

    /**
     * Deletes the specified Domain Registration Order.
     *
     * @param array $vars An array of input params including:
     *  - order-id Order Id of the Domain Registration Order that you want to delete
     * @return LogicboxesResponse
     */
    public function delete(array $vars)
    {
        return $this->api->submit('domains/delete', $vars);
    }

    /**
     * Restores the specified Domain Registration Order.
     *
     * @param array $vars An array of input params including:
     *  - order-id Order Id of the Domain Registration Order that you want to restore
     *  - invoice-option This will decide how the Customer Invoice will be handled.
     *      Set any of below mentioned Invoice Options for your Customer:
     *      - NoInvoice This will not raise any Invoice. The Order will be executed.
     *      - PayInvoice This will raise an invoice and if there are sufficient funds
     *          in the customer's account the invoice will be paid and the order will be
     *          executed, else the order will remain pending in the system
     *      - KeepInvoice This will raise an Invoice for the Customer to pay later. The Order will be executed.
     * @return LogicboxesResponse
     */
    public function restore(array $vars)
    {
        return $this->api->submit('domains/restore', $vars);
    }
}
