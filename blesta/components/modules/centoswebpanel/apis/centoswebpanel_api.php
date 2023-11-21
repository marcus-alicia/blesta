<?php
use Blesta\Core\Util\Common\Traits\Container;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'centoswebpanel_response.php';
/**
 * CentOS WebPanel API.
 *
 * @package blesta
 * @subpackage blesta.components.modules.centoswebpanel
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class CentoswebpanelApi
{
    // Load traits
    use Container;

    /**
     * @var string The server hostname
     */
    private $hostname;

    /**
     * @var int The port on which to connect to the API
     */
    private $port;

    /**
     * @var string The CentOS WebPanel api key
     */
    private $key;

    /**
     * @var bool Use ssl in all the api requests
     */
    private $use_ssl;

    /**
     * Initializes the class.
     *
     * @param mixed $hostname The CentOS WebPanel hostname or IP Address
     * @param int $port The port on which to connect to the API
     * @param mixed $key The api key
     * @param mixed $use_ssl True to connect to the api using SSL
     */
    public function __construct($hostname, $port, $key, $use_ssl = false)
    {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->key = $key;
        $this->use_ssl = $use_ssl;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Send a request to the CentOS WebPanel API.
     *
     * @param string $function Specifies the api function to invoke
     * @param array $params The parameters to include in the api
     * @param string $method Http request method (GET, DELETE, POST)
     * @return CentoswebpanelResponse An object containing the api response
     */
    private function apiRequest($function, array $params = [], $method = 'POST')
    {
        // Set api url
        $protocol = ($this->use_ssl ? 'https' : 'http');
        $url = $protocol . '://' . $this->hostname . ':' . $this->port . '/v1/' . $function;
        $ch = curl_init();

        // Set the API access key
        $params['key'] = $this->key;

        // Set the request method and parameters
        switch (strtoupper($method)) {
            case 'GET':
            case 'DELETE':
                $url .= empty($params) ? '' : '?' . http_build_query($params);
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POST, 1);
            default:
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                break;
        }

        // Send request
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
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
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        if ($result == false) {
            $error = [
                'status' => 'Error',
                'msj' => 'An internal error occurred, or the server did not respond to the request.'
            ];
            $this->logger->error(curl_error($ch));

            return new CentoswebpanelResponse(['content' => json_encode($error), 'headers' => []]);
        }

        curl_close($ch);

        // Return request response
        return new CentoswebpanelResponse(
            ['content' => substr($result, $header_size), 'headers' => explode("\n", substr($result, 0, $header_size))]
        );
    }

    /**
     * Creates a new account in the server.
     *
     * @param array $params An array contaning the following arguments:
     *  - domain: Main domain associated with the account
     *  - user: Username for the account
     *  - pass: Password for the account
     *  - email: Email Address of the account owner
     *  - package: Create account with package
     *  - inode: The account inodes limit, 0 for unlimited
     *  - limit_nofile: The maximum number of files that can host the account
     *  - limit_nproc: The maximum number of process that can run simultaneously, don’t use 0 as it will
     *     not allow any processes
     *  - server_ips: Ip server
     * @return CentoswebpanelResponse An object containing the request response
     */
    public function createAccount(array $params)
    {
        $params['action'] = 'add';
        return $this->apiRequest('account', $params);
    }

    /**
     * Updates an account in the server.
     *
     * @param array $params An array contaning the following arguments:
     *  - user: Username of the account being edited
     *  - email: Email Address of the account owner
     *  - package: Create account with package
     *  - inode: The account inodes limit, 0 for unlimited
     *  - limit_nofile: The maximum number of files that can host the account
     *  - limit_nproc: The maximum number of process that can run simultaneously, don’t use 0 as it will
     *     not allow any processes
     *  - server_ips: Ip server
     * @return CentoswebpanelResponse An object containing the request response
     */
    public function updateAccount(array $params)
    {
        // TODO This doesn't actually seem to work.  Figure out why and fix it
        $params['action'] = 'udp';
        return $this->apiRequest('account', $params);
    }

    /**
     * Removes an existing account from the server.
     *
     * @param string $username Specifies the username of the account
     * @param string $email Specifies the email address of the account owner
     * @return CentoswebpanelResponse An object containing the request response
     */
    public function removeAccount($username, $email)
    {
        $params = [
            'user' => $username,
            'email' => $email,
            'action' => 'del'
        ];
        return $this->apiRequest('account', $params);
    }

    /**
     * Suspend an existing account from the server.
     *
     * @param string $username Specifies the username of the account
     * @return CentoswebpanelResponse An object containing the request response
     */
    public function suspendAccount($username)
    {
        $params = [
            'user' => $username,
            'action' => 'susp'
        ];
        return $this->apiRequest('account', $params);
    }

    /**
     * Unsuspends an existing account from the server.
     *
     * @param string $username Specifies the username of the account
     * @return CentoswebpanelResponse An object containing the request response
     */
    public function unsuspendAccount($username)
    {
        $params = [
            'user' => $username,
            'action' => 'unsp'
        ];
        return $this->apiRequest('account', $params);
    }

    /**
     * Updates the password for the given user.
     *
     * @param array $params An array contaning the following arguments:
     *  - user: Username of the account to edit
     *  - pass: The new password for the account
     * @return CentoswebpanelResponse An object containing the request response
     */
    public function updatePassword(array $params)
    {
        $params['action'] = 'udp';
        return $this->apiRequest('changepass', $params);
    }

    /**
     * Updates the package for the given user.
     *
     * @param array $params An array contaning the following arguments:
     *  - user: Username of the account to edit
     *  - package: Password for the account
     * @return CentoswebpanelResponse An object containing the request response
     */
    public function updatePackage(array $params)
    {
        $params['action'] = 'udp';
        return $this->apiRequest('changepack', $params);
    }

    /**
     * Gets a list
     *
     * @return type
     */
    public function getPackages()
    {
        return $this->apiRequest('packages', ['action' => 'list']);
    }

    /**
     * Check if an account exists.
     *
     * @param string $username The username of the account
     * @return bool True if the account exists, false otherwise
     */
    public function accountExists($username)
    {
        $params = [
            'user' => $username,
            'action' => 'list'
        ];

        $accountResponse = $this->apiRequest('accountdetail', $params);
        return $accountResponse->status() == 200 && empty($accountResponse->errors());
    }

    /**
     * Get the client IP address.
     *
     * @return string The client IP address
     */
    public function getClientIp()
    {
        $ip_address = '';

        if (getenv('HTTP_CLIENT_IP')) {
            $ip_address = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip_address = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_X_FORWARDED')) {
            $ip_address = getenv('HTTP_X_FORWARDED');
        } elseif (getenv('HTTP_FORWARDED_FOR')) {
            $ip_address = getenv('HTTP_FORWARDED_FOR');
        } elseif (getenv('HTTP_FORWARDED')) {
            $ip_address = getenv('HTTP_FORWARDED');
        } else {
            $ip_address = getenv('REMOTE_ADDR');
        }

        return $ip_address;
    }
}
