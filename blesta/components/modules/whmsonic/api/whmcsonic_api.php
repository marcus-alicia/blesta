<?php
/**
 * Whmsonic API.
 *
 * @package blesta
 * @subpackage blesta.components.modules.whmsonic.whmsonicapi
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @see http://www.blesta.com/ Blesta
 */
class WhmsonicApi
{
    private $password;
    private $ip_address;
    private $use_ssl;

    /**
     * Initializes the class.
     *
     * @param mixed $password
     * @param mixed $ip_address
     * @param mixed $use_ssl
     */
    public function __construct($password, $ip_address, $use_ssl)
    {
        $this->password = $password;
        $this->ip_address = $ip_address;
        $this->use_ssl = $use_ssl;
    }

    /**
     * Return a string containing the last error for the current session.
     *
     * @param string $command the Whmsonic API command to call
     * @param array $params the parameters to include in the API request
     * @return mixed string|Array the curl error message or an array representing the API response
     */
    private function apiRequest($command, array $params)
    {
        $curl = curl_init();
        $params['cmd'] = $command;
        $params['ip'] = $this->ip_address;

        $params = http_build_query($params);

        $url = '';
        $port = '2086';

        if ($this->use_ssl == 'true') {
            $url .= 'https://';
            $port = '2087';
        } elseif ($this->use_ssl == 'false') {
            $url .= 'http://';
            $port = '2086';
        }
        $url .= $this->ip_address . ':' . $port . '/whmsonic/modules/api.php';

        curl_setopt($curl, CURLAUTH_BASIC, CURLAUTH_ANY);
        curl_setopt($curl, CURLOPT_USERPWD, 'root:' . $this->password);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        $curl_output = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if (empty($error)) {
            return $curl_output;
        }

        return $error;
    }

    /**
     * Creates a user account.
     *
     * @param array $params an array of parameters
     * @param string $type specifies whether the account is 'internal' or 'External'
     * @return array an array representing the status of the operation
     */
    public function createRadio(array $params, $type = 'internal')
    {
        $status = false;
        $parameters = [];
        $parameters['ctype'] = $type;
        $parameters['esend'] = 'yes'; // always send confirmation email to client

        if (isset($params['username'])) {
            $parameters['rad_username'] = $params['username'];
        }
        if (isset($params['radio_password'])) {
            $parameters['pass'] = $params['radio_password'];
        }
        if (isset($params['bitrate'])) {
            $parameters['bitrate'] = $params['bitrate'];
        }
        if (isset($params['hspace'])) {
            $parameters['hspace'] = $params['hspace'];
        }
        if (isset($params['autodj'])) {
            $parameters['autodj'] = $params['autodj'];
        }
        if (isset($params['bandwidth'])) {
            $parameters['bw'] = $params['bandwidth'];
        }
        if (isset($params['listeners'])) {
            $parameters['limit'] = $params['listeners'];
        }
        if (isset($params['client_email'])) {
            $parameters['cemail'] = $params['client_email'];
        }
        if (isset($params['client_name'])) {
            $parameters['cname'] = $params['client_name'];
        }

        $response = $this->apiRequest('create', $parameters);
        if ($response == 'Complete') {
            $status = true;
        }

        return [
            'status' => $status,
            'response' => $response,
        ];
    }

    /**
     * Suspends a user account.
     *
     * @param string $username the account's username to suspend
     * @return array an array representing the status of the operation
     */
    public function suspendRadio($username)
    {
        $status = false;

        $response = $this->apiRequest('suspend', [
            'rad_username' => $username,
        ]);
        if ($response == 'Complete') {
            $status = true;
        }

        return [
            'status' => $status,
            'response' => $response,
        ];
    }

    /**
     * Un-suspends a user account.
     *
     * @param string $username the account's username to un-suspend
     * @return array an array representing the status of the operation
     */
    public function unSuspendRadio($username)
    {
        $status = false;

        $response = $this->apiRequest('unsuspend', [
            'rad_username' => $username,
        ]);
        if ($response == 'Complete') {
            $status = true;
        }

        return [
            'status' => $status,
            'response' => $response,
        ];
    }

    /**
     * Terminates a user account.
     *
     * @param string $username the account's username to terminate
     * @return array an array representing the status of the operation
     */
    public function terminateRadio($username)
    {
        $status = false;

        $response = $this->apiRequest('terminate', [
            'rad_username' => $username,
        ]);
        if ($response == 'Complete') {
            $status = true;
        }

        return [
            'status' => $status,
            'response' => $response,
        ];
    }

    /**
     * Check if a FTP account is enabled.
     *
     * @param $ip_address The server ip address
     * @param $username The account username
     * @param $password The account password
     * @return string Returns "yes" if the account is enabled, otherwise returns "no"
     */
    public function ftpAccountPermissions($ip_address, $username, $password)
    {
        $result = 'no';
        if (function_exists('ftp_connect')) {
            $conn_id = @ftp_connect($ip_address);

            if (@ftp_login($conn_id, $username, $password)) {
                $result = 'yes';
            }

            ftp_close($conn_id);
        }

        return $result;
    }
}
