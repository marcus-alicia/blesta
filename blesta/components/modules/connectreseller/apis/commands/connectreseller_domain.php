<?php
/**
 * ConnectReseller Domain Management
 *
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package connectreseller.commands
 */
class ConnectresellerDomain
{
    /**
     * @var ConnectresellerApi
     */
    private $api;

    /**
     * Sets the API to use for communication
     *
     * @param ConnectresellerApi $api The API to use for communication
     */
    public function __construct(ConnectresellerApi $api)
    {
        $this->api = $api;
    }

    /**
     * Calls the API method
     *
     * @param string $name The method name to call
     * @param array $vars Arguments to pass to the method
     * @return mixed An array containing the response, or null on error
     */
    public function __call($name, array $vars) : ConnectresellerResponse
    {
        return $this->api->apiRequest($name, $vars[0] ?? []);
    }

    /**
     * Checks availability of the specified domain name.
     *
     * @param array $vars An array of input params including:
     *  - websiteName Domain Name that you need to check the availability
     * @return ConnectresellerResponse The response object
     */
    public function check(array $vars) : ConnectresellerResponse
    {
        return $this->api->apiRequest('/checkDomain', $vars);
    }

    /**
     * Cancel the domain Transfer-In with us till the order is in stall status.
     *
     * @param array $vars An array of input params including:
     *  - id The ID of the domain that you want to cancel the transfer
     * @return ConnectresellerResponse The response object
     */
    public function cancelTransfer(array $vars) : ConnectresellerResponse
    {
        return $this->api->apiRequest('/CancelTransfer', $vars);
    }

    /**
     * Validates a transfer a domain name with us.
     *
     * @param array $vars An array of input params including:
     *  - domainName Domain for you want to validate the transfer
     * @return ConnectresellerResponse The response object
     */
    public function syncTransfer(array $vars) : ConnectresellerResponse
    {
        return $this->api->apiRequest('/syncTransfer', $vars);
    }

    /**
     * Getting Registered Domain details with us by Id.
     *
     * @param array $vars An array of input params including:
     *  - id The ID of the domain that you want to get
     * @return ConnectresellerResponse The response object
     */
    public function getById(array $vars) : ConnectresellerResponse
    {
        return $this->api->apiRequest('/ViewDomainById', $vars);
    }

    /**
     * Getting Registered Domain details with us.
     *
     * @param array $vars An array of input params including:
     *  - websiteName name of the Domain whose details need to be fetched
     * @return ConnectresellerResponse The response object
     */
    public function get(array $vars) : ConnectresellerResponse
    {
        return $this->api->apiRequest('/ViewDomain', $vars);
    }

    /**
     * Getting Registered Domain details with us by a search query.
     *
     * @param array $vars An array of input params including:
     *  - page Page for which details needs to be fetched (optional)
     *  - maxIndex Max no of records needs to be fetch (optional)
     *  - clientId Filter search by client ID (optional)
     *  - searchQuery Search query to filter by (optional)
     * @return ConnectresellerResponse The response object
     */
    public function search(array $vars) : ConnectresellerResponse
    {
        if (!isset($vars['page'])) {
            $vars['page'] = 1;
        }

        if (!isset($vars['maxIndex'])) {
            $vars['maxIndex'] = 10;
        }

        return $this->api->apiRequest('/SearchDomainList', $vars);
    }

    /**
     * Getting Registered Domain details with us.
     *
     * @param array $vars An array of input params including:
     *  - domainNameId Domain name Id
     *  - websiteName Domain Name
     *  - nameServer1 New Name Servers 1 of the domain name
     *  - nameServer2 New Name Servers 2 of the domain name
     *  - nameServer3 New Name Servers 3 of the domain name
     *  - nameServer4 New Name Servers 4 of the domain name
     * @return ConnectresellerResponse The response object
     */
    public function updateNameServers(array $vars) : ConnectresellerResponse
    {
        return $this->api->apiRequest('/UpdateNameServer', $vars);
    }

    /**
     * Locks or unlocks the domain name.
     *
     * @param array $vars An array of input params including:
     *  - domainNameId Domain name Id
     *  - websiteName Domain Name
     *  - isDomainLocked Lock Status for domain. (1 or 0)
     * @return ConnectresellerResponse The response object
     */
    public function manageLock(array $vars) : ConnectresellerResponse
    {
        return $this->api->apiRequest('/ManageDomainLock', $vars);
    }

    /**
     * Enables or disables WHOIS privacy.
     *
     * @param array $vars An array of input params including:
     *  - domainNameId Domain name Id
     *  - iswhoisprotected Privacy Status for domain. (1 or 0)
     * @return ConnectresellerResponse The response object
     */
    public function managePrivacy(array $vars) : ConnectresellerResponse
    {
        return $this->api->apiRequest('/ManageDomainPrivacyProtection', $vars);
    }

    /**
     * Suspends a domain.
     *
     * @param array $vars An array of input params including:
     *  - domainNameId Domain name Id
     *  - websiteName Domain Name
     *  - isDomainSuspend Whether or not the domain is suspended (1 or 0)
     * @return ConnectresellerResponse The response object
     */
    public function suspend(array $vars) : ConnectresellerResponse
    {
        return $this->api->apiRequest('/ManageDomainSuspend', $vars);
    }

    /**
     * Fetches the EPP code from a domain.
     *
     * @param array $vars An array of input params including:
     *  - domainNameId Domain name Id
     * @return ConnectresellerResponse The response object
     */
    public function getEpp(array $vars) : ConnectresellerResponse
    {
        return $this->api->apiRequest('/ViewEPPCode', $vars);
    }
}
