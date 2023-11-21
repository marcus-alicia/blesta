<?php
/**
 * Internet.bs Domain Management
 *
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package internetbs.commands
 */
class InternetbsDomain
{
    /**
     * @var InternetbsApi
     */
    private $api;

    /**
     * Sets the API to use for communication
     *
     * @param InternetbsApi $api The API to use for communication
     */
    public function __construct(InternetbsApi $api)
    {
        $this->api = $api;
    }

    /**
     * The command is intended to check whether a domain is available for registration or not. The
     * command is not generating any cost.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name to check.
     * @return InternetbsResponse The response object
     */
    public function check(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/Check', $vars);
    }

    /**
     * The command is intended to register a new domain; while there are dozens of optional
     * parameters, only a few are required, other parameters can be safely ignored and used only
     * if and when you really need them.
     *
     * @param array $vars An array of input params including:
     *  - Domain Domain name with extension.
     *  - CloneContactsFromDomain If used, then any other contact related parameter is ignored.
     *  - Period The period for which the domain is registered for.
     *  - Ns_list List of name servers, delimited by comma.
     *  - privateWhois By default it is set to DISABLE, possible values are FULL, PARTIAL and DISABLE.
     *  - AutoRenew Enable or disable domain automatic renewal. Possible values are YES or NO.
     * @return InternetbsResponse The response object
     */
    public function create(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/Create', $vars, 'POST');
    }

    /**
     * The command is intended to update a domain, including Registrant Contact, Billing Contact, Admin
     * Contact, Tech. Contact, registrar locks status, epp auth info, name servers, private whois status, etc...
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name to update.
     *  - Ns_list List of name servers, delimited by comma.
     *  - privateWhois By default it is set to DISABLE, possible values are FULL, PARTIAL and DISABLE.
     *  - AutoRenew Enable or disable domain automatic renewal. Possible values are YES or NO.
     *  - Dnssec Use this option to specify DNSSEC config for domain.
     * @return InternetbsResponse The response object
     */
    public function update(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/Update', $vars, 'POST');
    }

    /**
     * The command is intended to update a domain, including Registrant Contact, Billing Contact, Admin
     * Contact, Tech. Contact, registrar locks status, epp auth info, name servers, private whois status, etc...
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name.
     * @return InternetbsResponse The response object
     */
    public function info(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/Info', $vars);
    }

    /**
     * The command is intended to view a domain registry status.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name.
     * @return InternetbsResponse The response object
     */
    public function registryStatus(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/RegistryStatus', $vars);
    }

    /**
     * The command is intended to initiate an incoming domain name transfer.
     *
     * The parameters are almost identical to those used for /Domain/Create, however some extra parameters
     * are optionally offered.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name.
     *  - transferAuthInfo The auth info (also transfer password, transfer secret, epp auth info, etc...).
     *  - senderEmail The email to be used as sender when sending the initial authorization for domain transfer
     *      as required by ICANN.
     *  - senderName The name used in the body of the initial authorization for the domain transfer email.
     *  - renewAfterTrasnfer By default it is set to NO, possible values are YES, NO. If set YES, then the
     *      domain will be renewed for one year once the transfer gets completed.
     * @return InternetbsResponse The response object
     */
    public function transfer(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/Transfer/Initiate', $vars, 'POST');
    }

    /**
     * This command is intended to reattempt a transfer in case an error occurred because inaccurate transfer
     * auth info was provided or because the domain was locked or in some other cases where an intervention
     * by the customer is required before retrying the transfer.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name.
     *  - transferAuthInfo The auth info (also transfer password, transfer secret, epp auth info, etc...).
     * @return InternetbsResponse The response object
     */
    public function transferRetry(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/Transfer/Retry', $vars, 'POST');
    }

    /**
     * The command is intended to cancel a pending incoming transfer request. If successful the
     * corresponding amount will be returned to your pre-paid balance.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name.
     * @return InternetbsResponse The response object
     */
    public function transferCancel(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/Transfer/Cancel', $vars, 'POST');
    }

    /**
     * The command is intended to resend the Initial Authorization for the Registrar Transfer email
     * for a pending, incoming transfer request. The operation is possible only if the current request
     * has not yet been accepted/rejected by the Registrant/Administrative contact, as it would
     * make no sense to ask again.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name.
     * @return InternetbsResponse The response object
     */
    public function resendAuthEmail(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/Transfer/ResendAuthEmail', $vars, 'POST');
    }

    /**
     * The command is intended to retrieve the history of a transfer.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name.
     * @return InternetbsResponse The response object
     */
    public function transferHistory(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/Transfer/History', $vars, 'POST');
    }

    /**
     * The command is intended to immediately approve a pending, outgoing transfer request
     * (you are transferring a domain away). The operation is possible only if there is a
     * pending transfer away request from another Registrar. If you do not approve the transfer
     * within a specific time frame, in general 5 days for .com/.net domains, the transfer will
     * automatically be approved by the Registry.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name.
     * @return InternetbsResponse The response object
     */
    public function transferAwayApprove(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/TransferAway/Approve', $vars, 'POST');
    }

    /**
     * The command is intended to reject a pending, outgoing transfer request (you are transferring away a domain).
     * The operation is possible only if there is a pending transfer away request from another
     * Registrar. If you do not reject the transfer within a specific time frame, in general 5
     * days for .com/.net domains, the transfer will be automatically approved by the Registry.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name.
     * @return InternetbsResponse The response object
     */
    public function transferAwayReject(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/TransferAway/Reject', $vars, 'POST');
    }

    /**
     * The command is a purposely redundant auxiliary way to enable the RegistrarLock for a specific domain.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name.
     * @return InternetbsResponse The response object
     */
    public function lock(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/RegistrarLock/Enable', $vars, 'POST');
    }

    /**
     * The command is a purposely redundant auxiliary way to disable the RegistrarLock for a specific domain.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name.
     * @return InternetbsResponse The response object
     */
    public function unlock(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/RegistrarLock/Disable', $vars, 'POST');
    }

    /**
     * The command is a purposely redundant auxiliary way to retrieve the current
     * RegistrarLock status for specific domain.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name.
     * @return InternetbsResponse The response object
     */
    public function lockStatus(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/RegistrarLock/Status', $vars, 'POST');
    }

    /**
     * The command is a purposely redundant auxiliary way to enable Private Whois for a specific domain.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name.
     * @return InternetbsResponse The response object
     */
    public function privateWhoisEnable(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/PrivateWhois/Enable', $vars, 'POST');
    }

    /**
     * The command is a purposely redundant auxiliary way to disable Private Whois for a specific domain.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name.
     * @return InternetbsResponse The response object
     */
    public function privateWhoisDisable(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/PrivateWhois/Disable', $vars, 'POST');
    }

    /**
     * The command is a purposely redundant auxiliary way to obtain the Private Whois status for a specific domain.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name.
     * @return InternetbsResponse The response object
     */
    public function privateWhoisStatus(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/PrivateWhois/Status', $vars, 'POST');
    }

    /**
     * This command is intended to retrieve a list of domains in your account.
     *
     * @param array $vars An array of input params including:
     *  - ExpiringOnly You can set a value X expressed in days and the returned list will include
     *      all the domains expiring during the X next days accordingly to the X value you set.
     *  - PendingTransferOnly No value required, if present only domains in Pending Transfer
     *      status will be listed. Note you cannot use PendingTransfersOnly and ExpiringOnly
     *      at the same time otherwise an error message will be generated.
     *  - searchTermFilter To get the list of domains that contains a specific text. If you need
     *      to get only domains of a specific extension you need to start this parameter with
     *      a dot followed by the extension.
     * @return InternetbsResponse The response object
     */
    public function list(array $vars = []) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/List', $vars, 'POST');
    }

    /**
     * The command is intended to count total number of domains in the account. It also returns the
     * number of domains for each extension.
     *
     * @return InternetbsResponse The response object
     */
    public function count() : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/Count', [], 'POST');
    }

    /**
     * The command is intended to renew a domain.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name.
     *  - Period The period for which the domain is renewed for. The current only valid values are
     *      1Y, 2Y up to 10Y where Y stands for years.
     *  - discountCode A discount code if you have one. By default, no discount is used.
     * @return InternetbsResponse The response object
     */
    public function renew(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/Renew', $vars, 'POST');
    }

    /**
     * This command can be used to restore deleted domain that are still in redemption period
     * (which can still be restored).
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name.
     *  - discountCode A discount code if you have one. By default no discount is used.
     * @return InternetbsResponse The response object
     */
    public function restore(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/Restore', $vars, 'POST');
    }
}
