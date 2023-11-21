<?php
use Blesta\Core\Util\Common\Traits\Container;

/**
 * CyberPanel API.
 *
 * @package blesta
 * @subpackage blesta.components.modules.cyberpanel
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class CyberpanelApi
{
    // Load traits
    use Container;

    /**
     * @var string The CyberPanel hostname
     */
    private $hostname;

    /**
     * @var string The CyberPanel username
     */
    private $username;

    /**
     * @var string The CyberPanel password
     */
    private $password;

    /**
     * @var bool Use ssl in all the api requests
     */
    private $use_ssl;

    /**
     * Initializes the class.
     *
     * @param string $hostname The CyberPanel server hostname
     * @param string $username The CyberPanel admin username
     * @param string $password The CyberPanel admin password
     * @param bool $use_ssl True to connect to the api using SSL
     */
    public function __construct($hostname, $username, $password, $use_ssl = true)
    {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->use_ssl = $use_ssl;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Send a request to the CyberPanel API.
     *
     * @param string $method Specifies the api function to invoke
     * @param array $params The parameters to include in the api call
     * @return stdClass An object containing the api response
     */
    public function apiRequest($method, array $params = [])
    {
        // Set api url
        $protocol = ($this->use_ssl ? 'https' : 'http');
        $url = $protocol . '://' . $this->hostname . ':8090/api/' . $method;

        // Set authentication details
        $auth = [
            'adminUser' => $this->username,
            'adminPass' => $this->password
        ];

        // Merge the authentication details with the parameters array
        $params = array_merge($auth, $params);

        // Send request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $result = curl_exec($ch);

        if ($result == false) {
            $this->logger->error(curl_error($ch));
        } else {
            $data = json_decode($result);
        }

        curl_close($ch);

        return $data ?? null;
    }

    /**
     * Creates a new account in the server.
     *
     * @param array $params An array contaning the following arguments:
     *  - username: The account username
     *  - password: The account password
     *  - domain: The account domain name
     *  - email: The account email address
     *  - package: The package name to assign to the account
     * @return stdClass An object containing the api response
     */
    public function createAccount($params)
    {
        // Build the parameters array
        $api_params = [
            'domainName' => isset($params['domain']) ? $params['domain'] : null,
            'ownerEmail' => isset($params['email']) ? $params['email'] : null,
            'packageName' => isset($params['package']) ? $params['package'] : null,
            'websiteOwner' => isset($params['username']) ? $params['username'] : null,
            'ownerPassword' => isset($params['password']) ? $params['password'] : null
        ];

        return $this->apiRequest('createWebsite', $api_params);
    }

    /**
     * Removes an existing account from the server.
     *
     * @param string $domain The main domain of the account
     * @return stdClass An object containing the api response
     */
    public function deleteAccount($domain)
    {
        return $this->apiRequest('deleteWebsite', ['domainName' => $domain]);
    }

    /**
     * Suspend an existing account from the server.
     *
     * @param string $domain The main domain of the account
     * @return stdClass An object containing the api response
     */
    public function suspendAccount($domain)
    {
        return $this->apiRequest('submitWebsiteStatus', ['websiteName' => $domain, 'state' => 'Suspend']);
    }

    /**
     * Unsuspends an existing account from the server.
     *
     * @param string $domain The main domain of the account
     * @return stdClass An object containing the api response
     */
    public function unsuspendAccount($domain)
    {
        return $this->apiRequest('submitWebsiteStatus', ['websiteName' => $domain, 'state' => 'Unsuspend']);
    }

    /**
     * Updates the password of an account.
     *
     * @param string $username The account username
     * @param string $new_password The new password for the account
     * @return stdClass An object containing the api response
     */
    public function updateAccountPassword($username, $new_password)
    {
        return $this->apiRequest('changeUserPassAPI', ['websiteOwner' => $username, 'ownerPassword' => $new_password]);
    }

    /**
     * Updates the package of an account.
     *
     * @param string $domain The main domain of the account
     * @param string $new_package The new package for the account
     * @return stdClass An object containing the api response
     */
    public function updateAccountPackage($domain, $new_package)
    {
        return $this->apiRequest('changePackageAPI', ['websiteName' => $domain, 'packageName' => $new_package]);
    }

    /**
     * Get the info for the given user
     *
     * @param string $username The username of the account
     * @return mixed An stdClass object representing the user if it exists, false otherwise
     */
    public function getUserInfo($username)
    {
        // Build the parameters array
        $api_params = ['username' => $username];
        $user = $this->apiRequest('getUserInfo', $api_params);

        return $user;
    }
}
