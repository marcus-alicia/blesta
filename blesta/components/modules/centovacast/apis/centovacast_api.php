<?php
use Blesta\Core\Util\Common\Traits\Container;

/**
 * Centovacast API.
 *
 * @package blesta
 * @subpackage blesta.components.modules.centovacast
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class CentovacastApi
{
    // Load traits
    use Container;

    /**
     * @var string The format of the response of the request, can be json or xml
     */
    private $format = 'json';

    /**
     * @var string The server hostname
     */
    private $hostname;

    /**
     * @var int The server port
     */
    private $port;

    /**
     * @var bool Use ssl in all the api requests
     */
    private $use_ssl;

    /**
     * @var string The Centovacast username
     */
    private $username;

    /**
     * @var string The Centovacast password
     */
    private $password;

    /**
     * Initializes the class.
     */
    public function __construct($hostname, $username, $password, $port = 2199, $use_ssl = false)
    {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
        $this->use_ssl = $use_ssl;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Send a request to the Centovacast API.
     *
     * @param string $method Specifies the class name and method to invoke, separated by a period
     * @param array $params The parameters to include in the api
     * @return stdClass An object containing the api response
     */
    public function apiRequest($method, array $params = [])
    {
        // Set authentication details
        $params['password'] = $this->username . '|' . $this->password;

        $protocol = ($this->use_ssl ? 'https' : 'http');
        $query = '&f=' . $this->format . (!empty($params) ? '&' . http_build_query(['a' => $params]) : '');

        $url = $protocol . '://' . $this->hostname . ':' . $this->port . '/api.php?xm=' . $method . $query;

        // Send request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
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
     * Creates a new client streaming server account in the server.
     *
     * @param string $username Specifies the username for this account
     * @param array $params An array contaning the following arguments:
     *     - hostname: Specifies the hostname for the stream.
     *     - ipaddress: Specifies the IP address on which the streaming server should listen.
     *     - port: Specifies the port number on which the streaming server should listen.
     *     - rpchostid: Specifies the ID number of the hosting server on which this account should be created.
     *     - adminpassword: Specifies the password for this stream.
     *     - sourcepassword: Specifies the source password for this stream.
     *     - maxclients: Specifies the maximum number of listeners that may simultaneously tune in to this stream.
     *     - maxbitrate: Specifies the maximum bit rate for this stream, in kilobits per second (kbps).
     *     - transferlimit: Specifies the maximum monthly data transfer for this stream, in megabytes (MB).
     *     - diskquota: Specifies the maximum disk space for this stream, in megabytes (MB).
     *     - title: Specifies the title for the stream.
     *     - genre: Specifies the genre of the stream.
     *     - autostart: Specifies whether or not the stream should automatically be started after provisioning.
     *     - servertype: Specifies the streaming server type for the stream.
     *     - apptypes: Specifies the supporting application types for autoDJ support.
     *     - usesource: Specifies whether or not the stream uses autoDJ capabilities.
     * @return stdClass An object containing the request response
     */
    public function createAccount($params)
    {
        return $this->apiRequest('system.provision', $params);
    }

    /**
     * Edits a existing client streaming server account in the server.
     *
     * @param string $username Specifies the username for this account
     * @param array $params An array contaning the following arguments:
     *     - hostname: Specifies the hostname for the stream.
     *     - ipaddress: Specifies the IP address on which the streaming server should listen.
     *     - port: Specifies the port number on which the streaming server should listen.
     *     - rpchostid: Specifies the ID number of the hosting server on which this account should be created.
     *     - adminpassword: Specifies the password for this stream.
     *     - sourcepassword: Specifies the source password for this stream.
     *     - maxclients: Specifies the maximum number of listeners that may simultaneously tune in to this stream.
     *     - maxbitrate: Specifies the maximum bit rate for this stream, in kilobits per second (kbps).
     *     - transferlimit: Specifies the maximum monthly data transfer for this stream, in megabytes (MB).
     *     - diskquota: Specifies the maximum disk space for this stream, in megabytes (MB).
     *     - title: Specifies the title for the stream.
     *     - genre: Specifies the genre of the stream.
     *     - autostart: Specifies whether or not the stream should automatically be started after provisioning.
     *     - servertype: Specifies the streaming server type for the stream.
     *     - apptypes: Specifies the supporting application types for autoDJ support.
     *     - usesource: Specifies whether or not the stream uses autoDJ capabilities.
     * @return stdClass An object containing the request response
     */
    public function editAccount($username, $params)
    {
        return $this->apiRequest('server.reconfigure', array_merge(['username' => $username], $params));
    }

    /**
     * Suspend an account.
     *
     * @param string $username Specifies the username for this account
     * @return stdClass An object containing the response of the request
     */
    public function suspendAccount($username)
    {
        return $this->apiRequest('system.setstatus', ['username' => $username, 'status' => 'disabled']);
    }

    /**
     * Unsuspend an suspended account.
     *
     * @param string $username Specifies the username for this account
     * @return stdClass An object containing the response of the request
     */
    public function unsuspendAccount($username)
    {
        return $this->apiRequest('system.setstatus', ['username' => $username, 'status' => 'enabled']);
    }

    /**
     * Terminate an account.
     *
     * @param string $username Specifies the username for this account
     * @return stdClass An object containing the response of the request
     */
    public function terminateAccount($username)
    {
        return $this->apiRequest('system.terminate', ['username' => $username, 'clientaction' => 'delete']);
    }

    /**
     * Get the status of an account.
     *
     * @param string $username Specifies the username for this account
     * @return stdClass An object containing the status of the account
     */
    public function getAccount($username)
    {
        return $this->apiRequest('server.getaccount', ['username' => $username]);
    }

    /**
     * List all the accounts of the system.
     *
     * @return stdClass An object containing a list of the accounts
     */
    public function listAccounts()
    {
        return $this->apiRequest('system.listaccounts');
    }

    /**
     * Starts the streaming server.
     *
     * @param string $username Specifies the username for this account
     * @return stdClass An object containing the response of the request
     */
    public function startStream($username)
    {
        return $this->apiRequest('server.start', ['username' => $username]);
    }

    /**
     * Restarts the streaming server.
     *
     * @param string $username Specifies the username for this account
     * @return stdClass An object containing the response of the request
     */
    public function restartStream($username)
    {
        return $this->apiRequest('server.restart', ['username' => $username]);
    }

    /**
     * Stop the streaming server.
     *
     * @param string $username Specifies the username for this account
     * @return stdClass An object containing the response of the request
     */
    public function stopStream($username)
    {
        return $this->apiRequest('server.stop', ['username' => $username]);
    }

    /**
     * Retrieves status information from the streaming server for a CentovaCast client account.
     *
     * @param string $username Specifies the username for this account
     * @param string $mountpoints A comma-delimited list of mount points whose status should also be checked
     * @return stdClass An object containing the response of the request
     */
    public function getStream($username, $mountpoints)
    {
        return $this->apiRequest('server.getstatus', ['username' => $username, 'mountpoints' => $mountpoints]);
    }

    /**
     * List all the servers of the system.
     *
     * @return stdClass An object containing a list of the hosting servers
     */
    public function listServers()
    {
        return $this->apiRequest('system.listhosts');
    }

    /**
     * List all the accounts usernames of the system.
     *
     * @return stdClass An object containing a list of the accounts
     */
    public function listUsernames()
    {
        try {
            $accounts = $this->apiRequest('system.listaccounts')->response->data;
        } catch (Exception $e) {
            // The system don't have any account yet
            return [];
        }

        $usernames = [];

        foreach ($accounts as $value) {
            $usernames[] = $value->username;
        }

        return $usernames;
    }
}
