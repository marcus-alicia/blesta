<?php
/**
 * APNSCP API.
 *
 * @package blesta
 * @subpackage blesta.components.modules.apnscp
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

use Blesta\Core\Util\Common\Traits\Container;

class ApnscpApi
{
    // Load traits
    use Container;

    /**
     * @var string The server hostname
     */
    private $hostname;

    /**
     * @var string The APNSCP api key
     */
    private $api_key;

    /**
     * @var bool Use ssl in all the api requests
     */
    private $use_ssl;

    /**
     * @var bool The port on which to connect
     */
    private $port;

    /**
     * Initializes the class.
     *
     * @param mixed $hostname The APNSCP hostname or IP Address
     * @param mixed $api_key The APNSCP api key
     * @param mixed $use_ssl True to connect to the api using SSL, false otherwise
     * @param mixed $port The APNSCP port
     */
    public function __construct($hostname, $api_key, $use_ssl = false, $port = '2083')
    {
        $this->hostname = $hostname;
        $this->api_key = $api_key;
        $this->use_ssl = $use_ssl;
        $this->port = $port;
    }

    /**
     * Send a request to the APNSCP API.
     *
     * @param string $function Specifies the api function to invoke
     * @param array $params The parameters to include in the api
     * @param string $session_id The session ID for the SOAP request
     * @return stdClass An object containing the api response
     */
    public function apiRequest($function, array $params = [], $session_id = null)
    {
        // Set API location
        $soap_location = ($this->use_ssl ? 'https' : 'http') . '://' . $this->hostname . ':' . $this->port;

        // Create SOAP connection
        ini_set('default_socket_timeout', 5000);

	$headers = [
            'Abort-On: error',
            'X-Forwarded-For: ' . $this->getFromContainer('requestor')->ip_address
	];

        $client = new SoapClient($soap_location . '/apnscp.wsdl', [
            'location' => $soap_location . '/soap?authkey=' . $this->api_key,
            'uri' => 'urn:net.apnscp.soap',
            'trace' => true,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
	        ],
		'http' => [
                    'header' => implode("\r\n", $headers) . "\r\n"
                ]
            ])
        ]);

        // Set session ID
        if (!is_null($session_id)) {
            $client->__setCookie('esprit_id', $session_id);
        }

        try {
            return call_user_func_array([$client, $function], $params);
        } catch (SoapFault $e) {
            return (object) ['error' => $e->getMessage()];
        }
    }

    /**
     * Creates a new account in the server.
     *
     * @param array $params An array containing the following arguments:
     *
     *  - domain: Specifies the account domain name.
     *  - username: Specifies the account username.
     *  - password: Specifies the account password.
     *  - email: Specifies the client email address.
     *  - plan: Specifies the hosting plan for the account.
     * @return mixed True if the user was successfully created
     */
    public function createAccount($params)
    {
        $params = [
            'siteinfo.enabled' => true,
            'siteinfo.domain' => $params['domain'],
            'siteinfo.admin_user' => $params['username'],
            'siteinfo.email' => $params['email'],
            'siteinfo.plan' => $params['plan'],
            'auth.tpasswd' => $params['password']
        ];

        return $this->apiRequest(
            'admin_add_site',
            [$params['siteinfo.domain'], $params['siteinfo.admin_user'], $params]
        );
    }

    /**
     * Gets the account statistics.
     *
     * @param string $domain The account domain to fetch
     * @return stdClass An object containing the account statistics
     */
    public function getAccount($domain)
    {
        $session_id = $this->apiRequest('admin_hijack', [$domain]);

        if (!is_object($session_id)) {
            $disk = $this->apiRequest('site_get_account_quota', [], $session_id);
            $bandwidth = $this->apiRequest('site_get_bandwidth_usage', [], $session_id);

            return (object) [
                'disk' => [
                    'used' => $disk['qused'] / 1024,
                    'total' => $disk['qsoft'] / 1024
                ],
                'bandwidth' => [
                    'used' => $bandwidth['used'] / 1024 / 1024,
                    'total' => $bandwidth['total'] / 1024 / 1024
                ]
            ];
        }

        return $session_id;
    }

    /**
     * Suspends the account.
     *
     * @param string $domain The account domain to suspend
     * @return mixed True if the user was successfully suspended
     */
    public function suspendAccount($domain)
    {
        return $this->apiRequest('admin_deactivate_site', [$domain]);
    }

    /**
     * Unsuspends the account.
     *
     * @param string $domain The account domain to unsuspend
     * @return mixed True if the user was successfully unsuspended
     */
    public function unsuspendAccount($domain)
    {
        return $this->apiRequest('admin_activate_site', [$domain]);
    }

    /**
     * Deletes the account.
     *
     * @param string $domain The account domain to delete
     * @return mixed True if the user was successfully deleted
     */
    public function deleteAccount($domain)
    {
        return $this->apiRequest('admin_delete_site', [$domain, ['force' => true]]);
    }

    /**
     * Updates the password of an existing account.
     *
     * @param array $params An array containing the following arguments:
     *
     *  - domain: Specifies the account domain name.
     *  - username: Specifies the account username.
     *  - password: Specifies the account password.
     * @return mixed True if the user has been updated successfully
     */
    public function updateAccountPassword($params)
    {
        return $this->apiRequest('auth_change_password', [$params['password'], $params['username'], $params['domain']]);
    }

    /**
     * Updates the hosting plan of an existing account.
     *
     * @param array $params An array containing the following arguments:
     *
     *  - domain: Specifies the account domain name.
     *  - plan: Specifies the hosting plan for the account.
     * @return mixed True if the user has been updated successfully
     */
    public function updateAccountPlan($params)
    {
        return $this->apiRequest('admin_edit_site', [$params['domain'], ['siteinfo.plan' => $params['plan'], ['reset' => true]]]);
    }

    /**
     * Get all the existing accounts in the server.
     *
     * @return array An array containing all the accounts
     */
    public function getAllAccounts()
    {
        return $this->apiRequest('admin_get_domains');
    }

    /**
     * Get all the available hosting plans.
     *
     * @return array An array containing all the available plans
     */
    public function getHostingPlans()
    {
        $plans = [];
        $request = $this->apiRequest('admin_list_plans');

        if (is_array($request)) {
            $plans = array_combine($request, $request);
        }

        return $plans;
    }

    /**
     * Generate a Single Sign-On link for an account.
     *
     * @param string $domain The account domain
     * @param string $username The account username
     * @return mixed The SSO Link
     */
    public function getSsoLink($domain, $username)
    {
        $session_id = $this->apiRequest('admin_hijack', [$domain, $username, 'UI']);

        if (is_object($session_id)) {
            return $session_id;
        }

        return ($this->use_ssl ? 'https' : 'http') . '://' . $this->hostname . ':' . $this->port
            . '/apps/dashboard?esprit_id=' . $session_id;
    }
}
