<?php
/**
 * Interworx API Module
 *
 * @package blesta
 * @subpackage blesta.components.modules.interworx
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 * @link http://docs.interworx.com/interworx/api/ Interworx API Documentation
 */
class InterworxApi
{
    /**
     * @var string Debugging type 'none' | 'log' | 'print'
     */
    private $debug = 'none';
    /**
     * @var string The host to connect to. Can be IP or domain
     */
    private $host = '127.0.0.1';
    /**
     * @var string The port to connect with
     */
    private $port = '2443';
    /**
     * @var string The protocol to connect with Ex. https | http
     */
    private $protocol = 'https';
    /**
     * @var int The value of unlimited
     */
    private $unlimited_value = 999999999;
    /**
     * @var string The remote access key to connect with
     */
    private $apikey    = null;

    /**
     * Returns the Debug Type currently set
     *
     * @return string The debug type 'none' | 'log' | 'print'
     * @see setDebug()
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * Sets the Debug Type
     *
     * @param string $debug  'none' | 'log' | 'print'
     * @see getDebug()
     */
    public function setDebug($debug = 'print')
    {
        $this->debug = $debug;
    }

    /**
     * Returns the Host currently set
     *
     * @return string The host to connect to.
     * @see setHost()
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Sets the Host
     *
     * @param string $host  Can be IP or Domain
     * @see getHost()
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * Returns the Port currently set
     *
     * @return string The port to connect with.
     * @see setPort()
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Sets the Port
     *
     * @param string $port
     * @see getPort()
     */
    public function setPort($port)
    {
        if (!is_int($port)) {
            $port = intval($port);
        }

        $this->port = $port;
    }

    /**
     * Returns the Protocol currently set
     *
     * @return string The port to connect with.
     * @see setProtocol()
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * Sets the Protocol to connect with
     *
     * @param string $protocol Ex. https | http
     * @see getProtocol()
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
    }

    /**
     * Sets the Remote API Key to connect with
     *
     * @param string $apikey
     */
    public function setApiKey($apikey)
    {
        $this->apikey = $apikey;
    }

    /**
     * Checks if the Value is unlimited
     *
     * @param string $value
     * @return bool
     */
    public function isUnlimited($value = '')
    {
        if (intval($value) >= $this->unlimited_value) {
            return true;
        }
        return false;
    }

    #
    # TODO: add support for different clients like nusoap or xmlrpc
    #

    /**
     * Sets and Runs the API Query to Call
     *
     * @param string $api_controller - Ex. /nodeworx/siteworx
     * @param string $action - Ex. add
     * @param array $input - List of Values to pass to the API Query.
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response The values on success and error message on error.
     *  - log data to be stored in a log.
     */
    private function apiQuery($api_controller = '', $action = '', $input = [])
    {

        // Check to make sure all the data needed to perform the query is in place
        if (!$api_controller) {
            return $this->getResponse(null, 'no_controller');
        }

        if (!$action) {
            return $this->getResponse(null, 'no_action');
        }

        try {
            $query = $this->sendQuery($api_controller, $action, $input);
        } catch (Exception $e) {
            $query = $this->getResponse(null, $e->getMessage());
        }

        if (empty($query)) {
            $query = $this->getResponse(null, 'internal');
        }

        return $query;
    }

    /**
     * Runs the API Query to Call.
     * Do not make calls directly through this method.
     * Instead use the apiQuery() as it will catch Exceptions.
     *
     * @param string $api_controller - Ex. /nodeworx/siteworx
     * @param string $action - Ex. add
     * @param array $input - List of Values to pass to the API Query.
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response The values on success and error message on error.
     *  - log data to be stored in a log.
     */
    private function sendQuery($api_controller = '', $action = '', $input = [])
    {

        // Check to make sure all the data needed to perform the query is in place
        if (!$api_controller) {
            return $this->getResponse(null, 'no_controller');
        }

        if (!$action) {
            return $this->getResponse(null, 'no_action');
        }

        /* Input Example:
        * Be aware that even actions that require no input still require the parameter
        * Just pass in an empty array
        * $input = array("nickname"         => "Example User",
        * 				  "email"            => "exampleuser@example.com",
        * 				  "language"         => "en-us",
        * 				  "theme"            => "interworx",
        * 				  "password"         => "pass",
        * 				  "confirm_password" => "pass",
        * 				  "perms"            => array("LOGIN", "SWACCOUNTS" ));
        */

        // Check permissions
        if (!isset($input['perms']) || !is_array($input['perms'])) {
            $input['perms'] = [];
        }

        // Host URL
        $host_url = $this->protocol.'://'.$this->host.':'.$this->port.'/soap?wsdl';

        $log = [];
        $log['parameters'] = ['apikey'    => $this->apikey,
                                   'ctrl_name' => $api_controller,
                                   'action'    => $action,
                                   'input'     => $input];

        if ($this->debug == 'print') {
            echo '<pre><br/>sendQuery Parameters sent to Soap Client at ('.$host_url.') (Line: '.__LINE__.')<br/>';
            print_r($log['parameters']);
            echo '</pre>';
        }

        if (!class_exists('SoapClient')) {
            return $this->getResponse(null, 'soap_error1');
        }

        // Connect and Call the SOAP Query
        $response = '';
        try {
            $client = new SoapClient($host_url);
            $response = $client->route($this->apikey, $api_controller, $action, $input);
        } catch (Exception $e) {
            throw new Exception('soap_error2');
        }

        // Set Log Response
        $log['response'] = $response;

        // print out the response if debug mode is enabled.
        if ($this->debug == 'print') {
            echo '<pre><br/>sendQuery Response from ('.$host_url.') (Line: '.__LINE__.')<br/>';
            print_r($response);
            echo '</pre>';
        }

        // Check for Errors
        if (isset($response['status']) && $response['status']) {
            return $this->getResponse(
                null,
                'reported_error ' . $response['payload'],
                ($this->debug == 'log' ? $log : '')
            );
        }

        return $this->getResponse($response, '', ($this->debug == 'log' ? $log : ''));
    }

    /**
     * Validates and prepares the returned data
     *
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response The values on success and error message on error.
     *  - log data to be stored in a log.
     */
    private function getResponse($response, $error = '', $log = '')
    {
        if (!isset($response['payload']) || !isset($response['status']) || !is_array($response)) {
            $response = ['status' => 0, 'payload' => $response];
        }

        $results = new stdClass;
        $results->status = (!$error && isset($response['status']) && !$response['status']
            && isset($response['payload']) ? 'success' : 'error'
        );
        $results->response = (isset($response['payload']) ? $response['payload'] : null);
        $results->log = (!empty($log) ? "DEBUG LOG: \n".print_r($log, true) : '');

        if (!isset($response['payload']) && !$error) {
            $results->response = 'internal';
        } elseif (!empty($error)) {
            $results->response = $error;
        } elseif (!empty($response['payload']) && $results->status == 'error') {
            $results->response = $response['payload'];
        }

        // print out the results if debug mode is enabled.
        if ($this->debug == 'print') {
            echo '<pre><br/>getResponse Results (Line: '.__LINE__.')<br/>';
            print_r($results);
            echo '</pre>';
        }

        return $results;
    }

    /**
     * Gets the current User from by the Remote Access Key
     *
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response The values on success and error message on error.
     *  - log data to be stored in a log.
     */
    public function getUser()
    {
        $input = ['perms' => ['NODEWORXUSER']];
        $user = $this->apiQuery('/nodeworx/users', 'listWorkingUser', $input);

        return $user;
    }

    /**
     * Checks the Current user by Remote Access Key if they are a Reseller or not
     *
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response boolean on success and Error Message on error.
     *  - log data to be stored in a log.
     */
    public function isUserReseller()
    {
        $input = ['perms' => ['NODEWORXUSER']];
        return $this->apiQuery('/nodeworx/users', 'isReseller', $input);
    }

    /**
     * Creates a Siteworx Account
     *
     * @param array $values
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response Success Message on success and Error Message on error.
     *  - log data to be stored in a log.
     * @see modifyAccount
     * @see removeAccount
     * @link http://docs.interworx.com/interworx/api/index-Controller---nodeworx-siteworx.php
     */
    public function createAccount($values)
    {
        if (!is_array($values)) {
            return $this->getResponse(null, 'create_account.no_array');
        }
        if (!isset($values['username']) || !isset($values['password'])
            || !isset($values['domain']) || !isset($values['email'])
        ) {
            return $this->getResponse(null, 'create_account.missing_fields');
        }
        if (strlen($values['username']) > 8) {
            return $this->getResponse(null, 'create_account.username_length');
        }
        if (preg_match('/[^a-z0-9]/i', $values['username'])) {
            return $this->getResponse(null, 'create_account.username_characters');
        }

        // Set Input
        $input = [];
        $input['domainname'] = strtolower($values['domain']);
        $input['password'] = $input['confirm_password'] = $values['password'];
        $input['uniqname'] = strtolower($values['username']);
        $input['nickname'] = $input['uniqname'] = strtolower($values['username']);
        $input['email'] = $values['email'];

        // Get IP Address for account
        $ip = $this->getAvailableIp();
        if (empty($ip->response)) {
            return $this->getResponse(null, 'no_ips');
        }

        $input['ipaddress'] = $ip->response;

        // Set Permissions
        $input['perms'] = ['SWACCOUNTS'];

        // Add Package information
        $packages = $this->listPackages();
        $package_details = false;

        if (!empty($values['plan']) && isset($packages->status)
            && $packages->status == 'success' && !empty($packages->response)
        ) {
            foreach ($packages->response as $package) {
                if ($package['id'] == $values['plan']) {
                    $package_details = $package;
                    break;
                }
            }
        }

        if (!empty($package_details)) {
            foreach ($package_details as $detail => $detail_value) {
                if (strpos($detail, 'OPT_') !== false) {
                    $input[$detail] = (is_bool($detail_value) ? ($detail ? 1 : 0) : $detail_value);
                }
            }
        } else {
            return $this->getResponse(null, 'no_packages');
        }

        // Check for Duplicate Domain and Username
        $accounts = $this->listAccounts();
        if (isset($accounts->status) && $accounts->status == 'success' && isset($accounts->response)) {
            if (!empty($accounts->response) && is_array($accounts->response)) {
                foreach ($accounts->response as $account) {
                    if ($input['domainname'] == $account->domain) {
                        return $this->getResponse(null, 'duplicate_domain');
                    }

                    if ($input['uniqname'] == $account->uniqname) {
                        return $this->getResponse(null, 'duplicate_username');
                    }
                }
            }
        } else {
            return $resellers;
        }

        return $this->apiQuery('/nodeworx/siteworx', 'add', $input);
    }

    /**
     * Modifies a Siteworx Account
     *
     * @param array $input
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response Success Message on success and Error Message on error.
     *  - log data to be stored in a log.
     * @see createAccount
     * @see removeAccount
     * @link http://docs.interworx.com/interworx/api/index-Controller---nodeworx-siteworx.php
     */
    public function modifyAccount($input = [])
    {
        if (!isset($input['domain'])) {
            return $this->getResponse(null, 'no_domain');
        }

        // Set Permissions
        $input['perms'] = ['SWACCOUNTS'];

        // Add Package information
        if (!empty($input['plan'])) {
            $packages = $this->listPackages();
            $package_details = false;

            if (isset($packages->status) && $packages->status == 'success' && !empty($packages->response)) {
                foreach ($packages->response as $package) {
                    if ($package['id'] == $input['plan']) {
                        $package_details = $package;
                        break;
                    }
                }
            }

            if (!empty($package_details)) {
                foreach ($package_details as $detail => $detail_value) {
                    if (strpos($detail, 'OPT_') !== false) {
                        $input[$detail] = (is_bool($detail_value) ? ($detail ? 1 : 0) : $detail_value);
                    }
                }
            }
        }

        return $this->apiQuery('/nodeworx/siteworx', 'edit', $input);
    }

    /**
     * Suspends a Siteworx Account
     *
     * @param string domain
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response Success Message on success and Error Message on error.
     *  - log data to be stored in a log.
     * @see unsuspendAccount
     * @link http://docs.interworx.com/interworx/api/index-Controller---nodeworx-siteworx.php
     */
    public function suspendAccount($domain)
    {
        if (!isset($domain)) {
            return $this->getResponse(null, 'no_domain');
        }

        // Set Input
        $input = [];
        $input['domain'] = $domain;
        $input['perms'] = ['SWACCOUNTS'];

        return $this->apiQuery('/nodeworx/siteworx', 'suspend', $input);
    }

    /**
     * Unsuspends a Siteworx Account
     *
     * @param string domain
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response Success Message on success and Error Message on error.
     *  - log data to be stored in a log.
     * @see suspendAccount
     * @link http://docs.interworx.com/interworx/api/index-Controller---nodeworx-siteworx.php
     */
    public function unsuspendAccount($domain)
    {
        if (!isset($domain)) {
            return $this->getResponse(null, 'no_domain');
        }

        // Set Input
        $input = [];
        $input['domain'] = $domain;
        $input['perms'] = ['SWACCOUNTS'];

        return $this->apiQuery('/nodeworx/siteworx', 'unsuspend', $input);
    }

    /**
     * Removes a Siteworx Account
     *
     * @param string domain
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response Success Message on success and Error Message on error.
     *  - log data to be stored in a log.
     * @see createAccount
     * @see modifyAccount
     * @link http://docs.interworx.com/interworx/api/index-Controller---nodeworx-siteworx.php
     */
    public function removeAccount($domain)
    {
        if (!isset($domain)) {
            return $this->getResponse(null, 'no_domain');
        }

        // Set Input
        $input = [];
        $input['domain'] = $domain;
        $input['confirm_action'] = 1;
        $input['perms'] = ['SWACCOUNTS'];

        return $this->apiQuery('/nodeworx/siteworx', 'delete', $input);
    }

    /**
     * Creates a Reseller Account
     *
     * @param array $values
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response Success Message on success and Error Message on error.
     *  - log data to be stored in a log.
     * @see modifyReseller
     * @see removeReseller
     * @see listResellers
     * @link http://docs.interworx.com/interworx/api/index-Controller---nodeworx-reseller.php
     */
    public function createReseller($values)
    {

        // Check if is Reseller
        $results = $this->isUserReseller();
        if (isset($results->status) && $results->status == 'success' && isset($results->response)) {
            if ($results->response) {
                return $this->getResponse(null, 'no_reseller_access');
            }
        } else {
            // return Error
            return $results;
        }

        if (!is_array($values)) {
            return $this->getResponse(null, 'create_reseller.no_array');
        }
        if (!isset($values['username']) || !isset($values['password']) || !isset($values['email'])) {
            return $this->getResponse(null, 'create_reseller.missing_fields');
        }

        // Set Input
        $input = [];
        $input['password'] = $input['confirm_password'] = $values['password'];
        $input['email'] = $values['email'];
        $input['status'] = 'active';
        $input['nickname'] = $values['username'];

        // Get All Shared IP Addresses for account
        $ips = $this->listSharedIps();
        if (empty($ips->response) || !is_array($ips->response)) {
            return $this->getResponse(null, 'no_ips');
        }

        $input['ips'] = implode(',', $ips->response);

        // Set Permissions
        $input['perms'] = ['RESELLER'];

        // Add Package information
        $packages = $this->listResellerPackages();
        $package_details = false;

        if (!empty($values['plan']) && isset($packages->status)
            && $packages->status == 'success' && !empty($packages->response)
        ) {
            foreach ($packages->response as $package) {
                if ($package['id'] == $values['plan']) {
                    $package_details = $package;
                    break;
                }
            }
        }

        if (!empty($package_details)) {
            foreach ($package_details as $detail => $detail_value) {
                if (strpos($detail, 'OPT_') !== false) {
                    $input[$detail] = (is_bool($detail_value) ? ($detail ? 1 : 0) : $detail_value);
                }
            }
        } else {
            return $this->getResponse(null, 'no_packages');
        }

        // Check for Duplicate Email
        $resellers = $this->listResellers();
        if (isset($resellers->status) && $resellers->status == 'success' && isset($resellers->response)) {
            if (!empty($resellers->response) && is_array($resellers->response)) {
                foreach ($resellers->response as $reseller) {
                    if ($input['email'] == $reseller->email) {
                        return $this->getResponse(null, 'duplicate_email');
                    }
                }
            }
        } else {
            return $resellers;
        }

        $result = $this->apiQuery('/nodeworx/reseller', 'add', $input);

        // Get and Return New Reseller ID
        $reseller_id = 0;
        if (isset($result->status) && $result->status == 'success' && isset($result->response)) {
            $resellers = $this->listResellers();
            if (isset($resellers->status) && $resellers->status == 'success' && isset($resellers->response)) {
                if (!empty($resellers->response) && is_array($resellers->response)) {
                    $reseller = end($resellers->response);
                    $reseller_id = (isset($reseller->reseller_id) ? $reseller->reseller_id : 0);
                }
            } else {
                return $resellers;
            }
        } else {
            return $result;
        }
        $result->reseller_id = $reseller_id;

        // Return results
        return $result;
    }

    /**
     * Modifies a Reseller Account
     *
     * @param array $input
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response Success Message on success and Error Message on error.
     *  - log data to be stored in a log.
     * @see createReseller
     * @see removeReseller
     * @see listResellers
     * @link http://docs.interworx.com/interworx/api/index-Controller---nodeworx-reseller.php
     */
    public function modifyReseller($input)
    {

        // Check if is Reseller
        $results = $this->isUserReseller();
        if (isset($results->status) && $results->status == 'success' && isset($results->response)) {
            if ($results->response) {
                return $this->getResponse(null, 'no_reseller_access');
            }
        } else {
            // return Error
            return $results;
        }

        if (!isset($input['reseller_id'])) {
            return $this->getResponse(null, 'no_reseller_id');
        }

        // Set Input
        $input['perms'] = ['RESELLER'];

        // Add Package information
        if (!empty($input['plan'])) {
            $packages = $this->listResellerPackages();
            $package_details = false;

            if (isset($packages->status) && $packages->status == 'success' && !empty($packages->response)) {
                foreach ($packages->response as $package) {
                    if ($package['id'] == $input['plan']) {
                        $package_details = $package;
                        break;
                    }
                }
            }

            if (!empty($package_details)) {
                foreach ($package_details as $detail => $detail_value) {
                    if (strpos($detail, 'OPT_') !== false) {
                        $input[$detail] = (is_bool($detail_value) ? ($detail ? 1 : 0) : $detail_value);
                    }
                }
            }
        }

        return $this->apiQuery('/nodeworx/reseller', 'edit', $input);
    }

    /**
     * Suspends a Reseller Account
     *
     * @param int $reseller_id
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response Success Message on success and Error Message on error.
     *  - log data to be stored in a log.
     * @see unsuspendReseller
     * @see listResellers
     * @link http://docs.interworx.com/interworx/api/index-Controller---nodeworx-reseller.php
     */
    public function suspendReseller($reseller_id)
    {

        // Check if is Reseller
        $results = $this->isUserReseller();
        if (isset($results->status) && $results->status == 'success' && isset($results->response)) {
            if ($results->response) {
                return $this->getResponse(null, 'no_reseller_access');
            }
        } else {
            // return Error
            return $results;
        }

        if (!isset($reseller_id)) {
            return $this->getResponse(null, 'no_reseller_id');
        }

        // Set Input
        $input = [];
        $input['reseller_id'] = $reseller_id;
        $input['status'] = 'inactive';
        $input['perms'] = ['RESELLER'];

        return $this->apiQuery('/nodeworx/reseller', 'edit', $input);
    }

    /**
     * Unsuspends a Reseller Account
     *
     * @param int $reseller_id
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response Success Message on success and Error Message on error.
     *  - log data to be stored in a log.
     * @see suspendReseller
     * @see listResellers
     * @link http://docs.interworx.com/interworx/api/index-Controller---nodeworx-reseller.php
     */
    public function unsuspendReseller($reseller_id)
    {

        // Check if is Reseller
        $results = $this->isUserReseller();
        if (isset($results->status) && $results->status == 'success' && isset($results->response)) {
            if ($results->response) {
                return $this->getResponse(null, 'no_reseller_access');
            }
        } else {
            // return Error
            return $results;
        }

        if (!isset($reseller_id)) {
            return $this->getResponse(null, 'no_reseller_id');
        }

        // Set Input
        $input = [];
        $input['reseller_id'] = $reseller_id;
        $input['status'] = 'active';
        $input['perms'] = ['RESELLER'];

        return $this->apiQuery('/nodeworx/reseller', 'edit', $input);
    }

    /**
     * Removes a Reseller Account
     *
     * @param int $reseller_id
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response Success Message on success and Error Message on error.
     *  - log data to be stored in a log.
     * @see createReseller
     * @see modifyReseller
     * @see listResellers
     * @link http://docs.interworx.com/interworx/api/index-Controller---nodeworx-reseller.php
     */
    public function removeReseller($reseller_id)
    {

        // Check if is Reseller
        $results = $this->isUserReseller();
        if (isset($results->status) && $results->status == 'success' && isset($results->response)) {
            if ($results->response) {
                return $this->getResponse(null, 'no_reseller_access');
            }
        } else {
            // return Error
            return $results;
        }

        if (!isset($reseller_id)) {
            return $this->getResponse(null, 'no_reseller_id');
        }

        // Set Input
        $input = [];
        $input['reseller_id'] = $reseller_id;
        $input['perms'] = ['RESELLER'];

        return $this->apiQuery('/nodeworx/reseller', 'delete', $input);
    }

    /**
     * Lists accounts under the current User
     *
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response Array on success and Error Message on error.
     *  - log data to be stored in a log.
     * @see listPackages
     * @see listResellers
     */
    public function listAccounts()
    {
        $input = ['perms' => ['SWACCOUNTS' ]];
        return $this->apiQuery('/nodeworx/siteworx', 'listAccounts', $input);
    }

    /**
     * Lists Packages under the current User
     *
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response Array on success and Error Message on error.
     *  - log data to be stored in a log.
     * @see listAccounts
     * @see listResellerPackages
     * @see listResellers
     */
    public function listPackages()
    {
        $input = ['perms' => ['RESELLER' ]];
        return $this->apiQuery('/nodeworx/packages', 'listDetails', $input);
    }

    /**
     * Lists Reseller Packages under the current Reseller
     *
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response Array on success and Error Message on error.
     *  - log data to be stored in a log.
     * @see listPackages
     * @see listResellers
     */
    public function listResellerPackages()
    {

        // Check if is Reseller
        $results = $this->isUserReseller();
        if (isset($results->status) && $results->status == 'success' && isset($results->response)) {
            if ($results->response) {
                return $this->getResponse(null, 'no_reseller_access');
            }
        } else {
            // return Error
            return $results;
        }

        $input = ['perms' => ['RESELLER' ]];
        return $this->apiQuery('/nodeworx/reseller/packages', 'listDetails', $input);
    }

    /**
     * Lists all Resellers. You must be an Admin to do this.
     *
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response Array on success and Error Message on error.
     *  - log data to be stored in a log.
     * @see createReseller
     * @see removeReseller
     * @see modifyReseller
     * @see listAccounts
     */
    public function listResellers()
    {

        // Check if is Reseller
        $results = $this->isUserReseller();
        if (isset($results->status) && $results->status == 'success' && isset($results->response)) {
            if ($results->response) {
                return $this->getResponse(null, 'no_reseller_access');
            }
        } else {
            // return Error
            return $results;
        }

        $input = ['perms' => ['RESELLER' ]];
        return $this->apiQuery('/nodeworx/reseller', 'listResellers', $input);
    }

    /**
     * Lists the usage of a domain or all domains under a Reseller.
     *
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response Array on success and Error Message on error.
     *  - log data to be stored in a log.
     */
    public function listUsage($domain = '')
    {
        if (!$domain) {
            return $this->apiQuery('/nodeworx/siteworx', 'listBandwidthAndStorage', ['perms' => ['SWACCOUNTS']]);
        }

        $accounts = $this->apiQuery('/nodeworx/siteworx', 'listBandwidthAndStorage', ['perms' => ['SWACCOUNTS']]);

        if ($accounts->status == 'success') {
            if (is_array($accounts->response) && empty($accounts->response)) {
                return $this->getResponse(null, 'no_accounts');
            }
            if (!empty($accounts->response)) {
                foreach ($accounts->response as $account) {
                    if ($account['domain'] == $domain) {
                        return $this->getResponse($account);
                    }
                }
            }
        }
        return $this->getResponse(null, 'internal');
    }

    /**
     * Lists all the IP's available for the current User or Reseller.
     *
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response Array on success and Error Message on error.
     *  - log data to be stored in a log.
     * @see listSharedIps
     * @see getAvailableIp
     */
    public function listIps()
    {
        return $this->apiQuery('/nodeworx/siteworx', 'listFreeIps', ['perms' => ['SWACCOUNTS']]);
    }

    /**
     * Lists all the Shared IP's available for the current User or Reseller.
     *
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response Array on success and Error Message on error.
     *  - log data to be stored in a log.
     * @see listIps
     * @see getAvailableIp
     */
    public function listSharedIps()
    {
        $ips = $this->listIps();

        $return_ips = [];

        if (!empty($ips->response) && isset($ips->status) && $ips->status == 'success') {
            foreach ($ips->response as $ip) {
                if (isset($ip[1]) && strpos(strtolower($ip[1]), 'shared')) {
                    $return_ips[] = $ip[0];
                }
            }
        } else {
            return $ips;
        }

        if (empty($return_ips)) {
            return $this->getResponse(null, 'no_ips');
        }

        return $this->getResponse($return_ips);
    }

    /**
     * Gets the first available IP for the current User or Reseller.
     *
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response Array on success and Error Message on error.
     *  - log data to be stored in a log.
     * @see listIps
     * @see listSharedIps
     */
    public function getAvailableIp()
    {
        $ips = $this->listIps();

        $return_ip = false;

        if (!empty($ips->response) && isset($ips->status) && $ips->status == 'success') {
            foreach ($ips->response as $ip) {
                if (isset($ip[1]) && strpos(strtolower($ip[1]), 'shared')) {
                    $return_ip = $ip[0];
                    break;
                }
            }
        } else {
            return $ips;
        }

        if (!$return_ip) {
            return $this->getResponse(null, 'no_ips');
        }

        return $this->getResponse($return_ip);
    }

    /**
     * Checks to see if it can connent to the Server.
     *
     * @return mixed
     *  - status The Status of the response ( error | success ).
     *  - response boolean on success and Error Message on error.
     *  - log data to be stored in a log.
     */
    public function canConnect()
    {
        $results = $this->apiQuery('/nodeworx/users', 'listWorkingUser', ['perms' => ['NODEWORXUSER']]);

        if (!empty($results->response['userid']) && is_int($results->response['userid'])) {
            return $this->getResponse(true);
        }

        return $results;
    }
}
