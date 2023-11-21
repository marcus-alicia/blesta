<?php
/**
 * OpenSRS Provisioning Management
 *
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package opensrs.commands
 */
class OpensrsDomainsProvisioning
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
     * Submits a new registration or transfer order.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function swRegister(array $vars) : OpensrsResponse
    {
        return $this->api->submit('sw_register', $vars);
    }

    /**
     * Activates a parked .DE domain.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function activate(array $vars) : OpensrsResponse
    {
        return $this->api->submit('activate', $vars);
    }

    /**
     * Changes information associated with a domain.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function modify(array $vars) : OpensrsResponse
    {
        return $this->api->submit('modify', $vars);
    }

    /**
     * Processes or cancels pending orders.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function processPending(array $vars) : OpensrsResponse
    {
        return $this->api->submit('process_pending', $vars);
    }

    /**
     * Queries the status of a queued request.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function queryQueuedRequest(array $vars) : OpensrsResponse
    {
        return $this->api->submit('query_queued_request', $vars);
    }

    /**
     * Redeems a .COM, .NET, .CA, .IT, or .NL domain that has expired but is within the redemption grace
     * period.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function redeem(array $vars) : OpensrsResponse
    {
        return $this->api->submit('redeem', $vars);
    }

    /**
     * Renews a domain.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function renew(array $vars) : OpensrsResponse
    {
        return $this->api->submit('renew', $vars);
    }

    /**
     * Removes the domain at the registry.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function revoke(array $vars) : OpensrsResponse
    {
        return $this->api->submit('revoke', $vars);
    }

    /**
     * Sends or resends the verification email to the registrant.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function sendRegistrantVerificationEmail(array $vars) : OpensrsResponse
    {
        return $this->api->submit('send_registrant_verification_email', $vars);
    }

    /**
     * Assigns an affiliate id to a domain.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function setDomainAffiliateId(array $vars) : OpensrsResponse
    {
        return $this->api->submit('set_domain_affiliate_id', $vars);
    }

    /**
     * Submits a domain-information update for .DK domains.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function updateAllInfo(array $vars) : OpensrsResponse
    {
        return $this->api->submit('update_all_info', $vars);
    }

    /**
     * Submits a domain-contact information update to the OpenSRS system.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function updateContacts(array $vars) : OpensrsResponse
    {
        return $this->api->submit('update_contacts', $vars);
    }

    /**
     * Modifies the default messaging language on a domain.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function modifyMessagingLanguage(array $vars) : OpensrsResponse
    {
        return $this->api->submit('modify_messaging_language', $vars);
    }

    /**
     * Cancels pending or declined orders.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function cancelPendingOrders(array $vars) : OpensrsResponse
    {
        return $this->api->submit('cancel_pending_orders', $vars, 'order');
    }
}
