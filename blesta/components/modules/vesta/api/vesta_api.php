<?php

/**
 * Vesta API
 *
 * @package blesta
 * @subpackage blesta.components.modules.vesta.vestaapi
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class VestaApi
{
    private $user_name;
    private $password;
    private $host_name;
    private $port;
    private $use_ssl;

    /**
     * Initializes the class
     */
    public function __construct($user_name, $password, $host_name, $port, $use_ssl)
    {
        $this->user_name = $user_name;
        $this->password = $password;
        $this->host_name = $host_name;
        $this->port = $port;
        $this->use_ssl = $use_ssl;
    }

    /**
     * Return a string containing the last error for the current session
     * @param string $command the Vesta API command to call
     * @param array $params the parameters to include in the API request
     * @return mixed string|Array the curl error message or an array representing the API response
     */
    private function apiRequest($command, array $params)
    {
        $curl = curl_init();
        $params['user'] = $this->user_name;
        $params['password'] = $this->password;
        $params['cmd'] = $command;
        $params['return_code'] = 'yes';
        //  $params['arg1'] = 'json';
        $params = http_build_query($params);
        $url = '';

        if ($this->use_ssl == 'true') {
            $url .= 'https://';
        } elseif ($this->use_ssl == 'false') {
            $url .= 'http://';
        }

        $url .= $this->host_name . ':' . $this->port . '/api/';


        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Accept: application/json', ' Accept-Charset: UTF-8']);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Expect:', 'Accept-Charset: UTF-8']);
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
     * Creates a user account
     * @param array $params an array of parameters
     * @return array an array representing the API response and the status of the call
     */
    public function createUserAccount(array $params)
    {
        $params = [
            'arg1' => $params['username'],
            'arg2' => $params['password'],
            'arg3' => $params['email'],
            'arg4' => $params['package_name'],
            'arg5' => $params['first_name'],
            'arg6' => $params['last_name'],
            'arg7' => $params['email'],
        ];
        $response = $this->apiRequest('v-add-user', $params);

        $return_response = [
            'status' => 'false',
            'response' => $response
        ];

        if ($response === 'OK') {
            $return_response['status'] = 'true';
        }
        return $return_response;
    }

    /**
     * Add a domain for the user account
     * @param string $username the username of the account
     * @param string $domain the domain to be added to the account
     * @return array an array representing the API response and the status of the call
     */
    public function addDomain($username, $domain)
    {
        $params = [
            'arg1' => $username,
            'arg2' => $domain,
        ];
        $response = $this->apiRequest('v-add-domain', $params);

        $return_response = [
            'status' => 'false',
            'response' => $response
        ];

        if ($response === 'OK') {
            $return_response['status'] = 'true';
        }
        return $return_response;
    }

    /**
     * Enables SSH for a specific account
     * @param string $username the username of the account
     * @param string $action specifies whether to enable or disable the shell access to the user (enable,disable)
     * @return array an array representing the API response and the status of the call
     */
    public function sshAccess($username, $action = 'enable')
    {
        if ($action == 'enable') {
            $action = 'bash';
        } else {
            $action = 'nologin';
        }
        $params = [
            'arg1' => $username,
            'arg2' => $action,
        ];
        $response = $this->apiRequest('v-change-user-shell', $params);

        $return_response = [
            'status' => 'false',
            'response' => $response
        ];

        if ($response === 'OK') {
            $return_response['status'] = 'true';
        }
        return $return_response;
    }

    /**
     * Delete a user account
     * @param string $username the username of the account to be deleted
     * @return array an array representing the API response and the status of the call
     */
    public function deleteUserAccount($username)
    {
        $response = $this->apiRequest('v-delete-user', [
            'arg1' => $username,
        ]);

        $return_response = [
            'status' => 'false',
            'response' => $response
        ];

        if ($response === 'OK') {
            $return_response['status'] = 'true';
        }
        return $return_response;
    }

    /**
     * Suspend a user account
     * @param string $username the username of the account to be suspended
     * @return array an array representing the API response and the status of the call
     */
    public function suspendUserAccount($username)
    {
        $response = $this->apiRequest('v-suspend-user', [
            'arg1' => $username,
        ]);

        $return_response = [
            'status' => 'false',
            'response' => $response
        ];

        if ($response === 'OK') {
            $return_response['status'] = 'true';
        }
        return $return_response;
    }

    /**
     * Un-suspend a user account
     * @param string $username the username of the account to be un-suspended
     * @return array an array representing the API response and the status of the call
     */
    public function unSuspendUserAccount($username)
    {
        $response = $this->apiRequest('v-unsuspend-user', [
            'arg1' => $username,
        ]);

        $return_response = [
            'status' => 'false',
            'response' => $response
        ];

        if ($response === 'OK') {
            $return_response['status'] = 'true';
        }
        return $return_response;
    }

    /**
     * Change a password for a user account
     * @param string $username the username of the account
     * @param string $password the new password of the account
     * @return array an array representing the API response and the status of the call
     */
    public function changeAccountPassword($username, $password)
    {
        $response = $this->apiRequest('v-change-user-password', [
            'arg1' => $username,
            'arg2' => $password,
        ]);

        $return_response = [
            'status' => 'false',
            'response' => $response
        ];

        if ($response === 'OK') {
            $return_response['status'] = 'true';
        }
        return $return_response;
    }

    /**
     * Change the package for a user account
     * @param string $username the username of the account
     * @param string $package the new package name to change to
     * @return array an array representing the API response and the status of the call
     */
    public function changeUserPackage($username, $package)
    {
        $response = $this->apiRequest('v-change-user-package', [
            'arg1' => $username,
            'arg2' => $package,
        ]);

        $return_response = [
            'status' => 'false',
            'response' => $response
        ];

        if ($response === 'OK') {
            $return_response['status'] = 'true';
        }
        return $return_response;
    }

    /**
     * Get account's usage
     * @param string $username the username of the account
     * Example Response:
     *
     * @return array an array representing the API response and the status of the call
     */
    public function getAccountsUsage($username)
    {
        $response = $this->apiRequest('v-list-user', [
            'arg1' => $username,
            'arg2' => 'json',
        ]);

        $return_response = json_decode($response, true);

        if (!empty($return_response)) {
            $return_response['status'] = 'true';
        } else {
            $return_response = ['status' => 'false'];
        }
        return $return_response;
    }
}
