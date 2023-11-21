<?php
/**
 * OpenSRS Domain Management
 *
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package opensrs.commands
 */
class OpensrsDomains
{
    /**
     * @var OpensrsApi
     */
    private $api;

    /**
     * Sets the API to use for communication
     *
     * @param OpensrsApi $api The API to use for communication
     */
    public function __construct(OpensrsApi $api)
    {
        $this->api = $api;
    }

    /**
     * Returns information about the a specific domain.
     *
     * @param array $vars An array of input params including:
     *
     *  - domain Domain name for which domain information needs to be requested
     *  - limit The maximum number of domains to return per page
     *  - max_to_expiry Defines the expiration range (in days): to fetch the list of domains
     *  that are between max_to_expiry and min_to_expiry before expiration date.
     *  - min_to_expiry Defines the expiration range (in days): to fetch the list of domains
     *  that are between max_to_expiry and min_to_expiry before expiration date.
     *  - type Type of query. Allowed values are:
     *      - admin Returns admin contact information.
     *      - all_info Returns all information.
     *      - billing Returns billing contact information.
     *      - ca_whois_display_setting Returns the current CIRA Whois Privacy setting for .CA domains.
     *      - domain_auth_info Returns domain authorization code, if applicable.
     *      - expire_action Returns the action to be taken upon domain expiry, specifically whether to auto-renew
     *          the domain, or let it expire silently.
     *      - forwarding_email Returns forwarding email for .NAME 2nd level.
     *      - list Returns list of domains in the same profile or returns list of domains for user using cookie method.
     *      - nameservers Returns nameserver information.
     *      - owner Returns owner contact information.
     *      - rsp_whois_info Returns name and contact information for RSP.
     *      - status Returns lock or escrow status of the domain.
     *      - tech Returns tech contact information.
     *      - tld_data Returns additional information that is required by some registries, such as the residency
     *          of the registrant.
     *      - waiting history Returns information on asynchronous requests.
     *      - whois_privacy_state Returns the state for the WHOIS
     *          Privacy feature: enabled, disabled, enabling, or disabling.
     *         Note: If the TLD does not allow WHOIS Privacy, always returns Disabled.
     *      - whois_publicity_state Returns the state for the WHOIS Publicity feature: enabled, disabled.
     *         Note: If the TLD does not allow WHOIS Privacy, always returns Disabled.
     *      - xpack_waiting_history Returns the state of completed/cancelled requests not yet deleted from
     *          the database for .DKdomains.
     *          All completed/cancelled requests are deleted from the database two weeks after they move to final state.
     * @return OpensrsResponse
     */
    public function get(array $vars) : OpensrsResponse
    {
        return $this->api->submit('get', $vars);
    }

    /**
     * Lists domains that have been deleted due to expiration or deleted by request (revoked). This command
     * applies to all domains in a reseller's profile. Results include the domain, status, and deleted
     * date.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function getDeletedDomains(array $vars) : OpensrsResponse
    {
        return $this->api->submit('get_deleted_domains', $vars);
    }

    /**
     * Retrieves domains that expire within a specified date range.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function getDomainsByExpiredate(array $vars) : OpensrsResponse
    {
        return $this->api->submit('get_domains_by_expiredate', $vars);
    }

    /**
     * Retrieves the domain notes that detail the history of the domain, for example, renewals and
     * transfers.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function getNotes(array $vars) : OpensrsResponse
    {
        return $this->api->submit('get_notes', $vars);
    }

    /**
     * Queries all the information on an order ID, but does not return sensitive information such as
     * username, password, and Authcode.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function getOrderInfo(array $vars) : OpensrsResponse
    {
        return $this->api->submit('get_order_info', $vars);
    }

    /**
     * Retrieves information about orders placed for a specific domain.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function getOrdersByDomain(array $vars) : OpensrsResponse
    {
        return $this->api->submit('get_orders_by_domain', $vars);
    }

    /**
     * Queries the contact information for the specified domains.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function getDomainsContacts(array $vars) : OpensrsResponse
    {
        return $this->api->submit('get_domains_contacts', $vars);
    }

    /**
     * Retrieves the text of the reseller agreement known as Exhibit A.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function getContract(array $vars) : OpensrsResponse
    {
        return $this->api->submit('get_contract', $vars);
    }

    /**
     * Queries the price of a domain, and can be used to determine the cost of a billable transaction for
     * any TLD. A returned price for a given domain does not guarantee the availability of the domain, but
     * indicates that the requested action is supported by the system and calculates the cost to register
     * the domain (if available).
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function getPrice(array $vars) : OpensrsResponse
    {
        return $this->api->submit('get_price', $vars);
    }

    /**
     * When a domain is registered or transferred, or when the registrant contact information is changed,
     * the registrant must reply to an email requesting them to confirm that the submitted contact
     * information is correct. This command returns the current state of the verification request.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function getRegistrantVerificationStatus(array $vars) : OpensrsResponse
    {
        return $this->api->submit('get_registrant_verification_status', $vars);
    }

    /**
     * Determines the availability of a specified domain name.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function lookup(array $vars) : OpensrsResponse
    {
        return $this->api->submit('lookup', $vars);
    }

    /**
     * Checks whether a specified name, word, or phrase is available for registration in gTLDs and ccTLDs,
     * suggests other similar domain names for .COM, .NET, .ORG, .INFO, .BIZ, .US, and .MOBI domains, and
     * checks whether they are available. Reseller must be enabled for the specified TLDs. Can also be used
     * to search for domains owned by external domain suppliers.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function nameSuggest(array $vars) : OpensrsResponse
    {
        return $this->api->submit('name_suggest', $vars);
    }

    /**
     * Until June 10, 2019, if you want to register an available second level .UK domain name, and the name
     * is already registered as a third level .UK domain (for example. .co.uk or .org.uk), the owner
     * contact information for the second level .UK name must be an exact match to the owner contact
     * information of the equivalent third level .UK domain name. This command checks whether the specified
     * domain name is registered as a third level .UK domain (for example. .co.uk or .org.uk) with the same
     * registrar and reseller, and if so, returns the owner contact details.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function ukGetBlockerContact(array $vars) : OpensrsResponse
    {
        return $this->api->submit('uk_get_blocker_contact', $vars);
    }

    /**
     * Sends the Authcode for an EPP domain to the admin contact. If the domain for which the request is made
     * does not use the EPP protocol, an error is returned..
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function sendAuthcode(array $vars) : OpensrsResponse
    {
        return $this->api->submit('send_authcode', $vars);
    }
}
