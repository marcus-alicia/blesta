<?php
use Blesta\Core\Util\Common\Traits\Container;

/**
 * ISPmanager API.
 *
 * @package blesta
 * @subpackage blesta.components.modules.ispmanager
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class IspmanagerApi
{
    // Load traits
    use Container;

    /**
     * @var string The server hostname
     */
    private $hostname;

    /**
     * @var string The ISPmanager username
     */
    private $username;

    /**
     * @var string The ISPmanager password
     */
    private $password;

    /**
     * @var bool Use ssl in all the api requests
     */
    private $use_ssl;

    /**
     * @var int The port on which to connect to the API
     */
    private $port;

    /**
     * Initializes the class.
     *
     * @param mixed $hostname The ISPmanager hostname or IP Address
     * @param mixed $username The user name
     * @param mixed $password The user password
     * @param mixed $use_ssl True to connect to the api using SSL
     * @param int $port The port on which to connect to the API
     */
    public function __construct($hostname, $username, $password, $use_ssl = false, $port = 1500)
    {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->use_ssl = $use_ssl;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Send a request to the ISPmanager API.
     *
     * @param string $function Specifies the api function to invoke
     * @param array $params The parameters to include in the api
     * @param string $method Http request method (GET, POST)
     * @return stdClass The formatted API response
     */
    private function apiRequest($function, array $params = [], $method = 'GET')
    {
        // Set call parameters
        $vars = [
            'authinfo' => $this->username . ':' . $this->password,
            'out' => 'json',
            'func' => $function,
            'sok' => 'ok'
        ];
        $params = array_merge($params, $vars);

        // Set api url
        $protocol = ($this->use_ssl ? 'https' : 'http');
        $url = $protocol . '://' . $this->hostname . ':' . $this->port . '/ispmgr';

        $ch = curl_init();

        // Set the request method and parameters
        switch (strtoupper($method)) {
            case 'GET':
                $url .= empty($params) ? '' : '?' . http_build_query($params);
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                break;
        }

        // Send request
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
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

        if ($result == false) {
            $this->logger->error(curl_error($ch));
        }

        curl_close($ch);

        return $this->formatResponse($result);
    }

    /**
     * Format API response.
     *
     * @param string $response The raw API response
     * @return stdClass The formatted API response
     */
    private function formatResponse($response)
    {
        $response = json_decode($response);

        if (isset($response->doc->error->detail)) {
            $response->error = $response->doc->error->detail->{'$'};
        }

        if (isset($response->doc->elem)) {
            foreach ($response->doc->elem as $key => $element) {
                foreach ($element as $param => $value) {
                    if (!isset($value->{'$'})) {
                        unset($response->doc->elem->{$param});
                    } else {
                        $response->doc->elem[$key]->{$param} = $value->{'$'};
                    }
                }
            }
        }

        return $response;
    }

    /**
     * Creates a new account in the server.
     *
     * @param array $params An array contaning the following arguments:
     *
     *  - name: Login. Enter a username to login the control panel and enable shell access (if permitted)
     *  - fullname: Full name. Enter a full name of the user, it can be his first and last names,
     *      or any other information related to this account
     *  - owner: Owner. Owner of the newly created user
     *  - preset: Template. You can select a template not to specify the limits manually.
     *      Values from the template will be set automatically. Editing the template will enable you to change
     *      properties for all the users based on this template
     *  - passwd: Enter a new user password. Leaving this field blank will not change the password
     *  - confirm: Re-enter password. Re-enter the password to ensure it is correct.
     *  - create_time: Creation date . User creation form
     *  - comment: Comment. Enter detailed description, notes, etc.
     *  - backup: Select the check box to make sure backups on regular basis
     *  - webdomain_name: Domain name without www.
     *  - emaildomain_name: Domain name to be used for email.
     * @return stdClass An object containing the request response
     */
    public function createAccount(array $params)
    {
        return $this->apiRequest('user.add', $params);
    }

    /**
     * Updates an account in the server.
     *
     * @param array $params An array contaning the following arguments:
     *
     *  - elid: Username of the account being edited
     *  - fullname: Full name. Enter a full name of the user, it can be his first and last names,
     *      or any other information related to this account
     *  - owner: Owner. Owner of the newly created user
     *  - preset: Template. You can select a template not to specify the limits manually.
     *      Values from the template will be set automatically. Editing the template will enable you to change
     *      properties for all the users based on this template
     *  - passwd: Enter a new user password. Leaving this field blank will not change the password
     *  - confirm: Re-enter password. Re-enter the password to ensure it is correct.
     *  - create_time: Creation date . User creation form
     *  - comment: Comment. Enter detailed description, notes, etc.
     *  - backup: Select the check box to make sure backups on regular basis
     * @return stdClass An object containing the request response
     */
    public function updateAccount(array $params)
    {
        return $this->apiRequest('user.edit', $params);
    }

    /**
     * Removes an existing account from the server.
     *
     * @param string $username Specifies the username of the account
     * @return stdClass An object containing the request response
     */
    public function removeAccount($username)
    {
        $params = [
            'elid' => $username
        ];
        return $this->apiRequest('user.delete', $params);
    }

    /**
     * Suspend an existing account from the server.
     *
     * @param string $username Specifies the username of the account
     * @return stdClass An object containing the request response
     */
    public function suspendAccount($username)
    {
        $params = [
            'elid' => $username
        ];
        return $this->apiRequest('user.suspend', $params);
    }

    /**
     * Unsuspends an existing account from the server.
     *
     * @param string $username Specifies the username of the account
     * @return stdClass An object containing the request response
     */
    public function unsuspendAccount($username)
    {
        $params = [
            'elid' => $username
        ];
        return $this->apiRequest('user.resume', $params);
    }

    /**
     * Updates the password for the given user.
     *
     * @param array $params An array containing the following arguments:
     *
     *  - elid: Username of the account to edit
     *  - passwd: The new password for the account
     * @return stdClass An object containing the request response
     */
    public function updatePassword(array $params)
    {
        $params['confirm'] = $params['passwd'];

        return $this->apiRequest('user.edit', $params);
    }

    /**
     * Gets all the templates from the system
     *
     * @return stdClass An object containing the request response
     */
    public function getTemplates()
    {
        $request = $this->apiRequest('preset');

        if (isset($request->error)) {
            return (object) ['error' => $request->error, 'response' => $request];
        }

        return (object) ['response' => isset($request->doc->elem) ? $request->doc->elem : []];
    }

    /**
     * Gets all the existing accounts.
     *
     * @return stdClass An object containing the request response
     */
    public function getAccounts()
    {
        $request = $this->apiRequest('user');

        if (isset($request->error)) {
            return (object) ['error' => $request->error, 'response' => $request];
        }

        return (object) ['response' => isset($request->doc->elem) ? $request->doc->elem : []];
    }

    /**
     * Check if an account exists.
     *
     * @param string $username The username of the account
     * @return bool True if the account exists, false otherwise
     */
    public function accountExists($username)
    {
        $accounts = $this->getAccounts();

        if (isset($accounts->response) && is_array($accounts->response)) {
            foreach ($accounts->response as $account) {
                if ($account->name == $username) {
                    return true;
                }
            }
        }

        return false;
    }
}
