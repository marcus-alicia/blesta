<?php
use Blesta\Core\Util\Common\Traits\Container;

/**
 * DirectAdmin API class
 *
 * This is licensed under the Open Software License, version 3.0,
 * available at https://raw.github.com/FullAmbit/SiteSense/master/LICENSE.txt
 * @author https://github.com/flotwig/directadmin/
 */
class DirectAdminApi
{
    // Load traits
    use Container;

    /**
     * @var string
     */
    public $apiUrl = 'http://directadmin-install:2222';

    /**
     * @var string
     */
    public $user = '';

    /**
     * @var string
     */
    public $pass = '';

    /**
     * @var array
     */
    public $calls = [
        // Functions which modify users
        'createUser' => ['POST', 'text', 'CMD_API_ACCOUNT_USER', ['action' => 'create', 'add' => 'Submit']],
        'changePassword' => ['POST', 'text', 'CMD_API_USER_PASSWD'],
        'createReseller' => ['POST', 'text', 'CMD_API_ACCOUNT_RESELLER', ['action' => 'create', 'add' => 'Submit']],
        'modifyUserPackage' => ['POST', 'text', 'CMD_API_MODIFY_USER', ['action' => 'package', 'add' => 'Submit']],
        'deleteUser' => ['POST', 'text', 'CMD_API_SELECT_USERS', ['delete' => 'yes', 'confirmed' => 'Confirm']],
        'suspendUser' => ['POST', '', 'CMD_API_SELECT_USERS', ['dosuspend' => 'Suspend', 'location' => 'CMD_API_SELECT_USERS']],
        'unsuspendUser' => ['POST', '', 'CMD_API_SELECT_USERS', ['dounsuspend' => 'Unsuspend', 'location' => 'CMD_API_SELECT_USERS']],
        // Functions which list users
        'listUsersByReseller' => ['POST', 'list', 'CMD_API_SHOW_USERS'],
        'listResellers' => ['POST', 'list', 'CMD_API_SHOW_RESELLERS'],
        'listAdmins' => ['POST', 'list', 'CMD_API_SHOW_ADMINS'],
        'listUsers' => ['POST', 'list', 'CMD_API_SHOW_ALL_USERS'],
        // Server Information functions
        'getServerStatistics' => ['GET', 'array', 'CMD_API_ADMIN_STATS'],
        'getUserUsage' => ['GET', 'array', 'CMD_API_SHOW_USER_USAGE'],
        'getUserDomains' => ['GET', 'array', 'CMD_API_SHOW_USER_DOMAINS'],
        // User package info
        'getPackagesUser' => ['GET', 'list', 'CMD_API_PACKAGES_USER'],
        'getPackagesReseller' => ['GET', 'list', 'CMD_API_PACKAGES_RESELLER'],
        // Reseller IPS
        'getResellerIps' => ['GET', 'list', 'CMD_API_SHOW_RESELLER_IPS'],
        'getUserConfig' => ['GET', 'array', 'CMD_API_SHOW_USER_CONFIG']
    ];

    /**
     * DirectAdminApi constructor.
     */
    public function __construct()
    {
        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * @param $call
     * @param string $method
     * @param array $postvars
     * @return bool|string
     */
    private function contactApi($call, $method = 'POST', $postvars = [])
    {
        $url = trim($this->apiUrl, '/') . '/' . $call;
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if (!empty($postvars)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
        }

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        curl_setopt($ch, CURLOPT_USERAGENT, 'flotwig\'s directadmin class - https://github.com/flotwig/directadmin');
        curl_setopt($ch, CURLOPT_USERPWD, $this->user . ':' . $this->pass);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        $result = curl_exec($ch);

        if ($result == false) {
            $this->logger->error(curl_error($ch));
        }

        curl_close($ch);

        return $result;
    }

    /**
     * @param $call
     * @param $arguments
     * @return false|string
     */
    public function __call($call, $arguments)
    {
        if (empty($this->calls[$call])) {
            return false;
        } else {
            $call = $this->calls[$call];
        }
        if (empty($arguments)) {
            $response = $this->contactApi($call[2], $call[0]);
        } else if (empty($call[3])) {
            $response = $this->contactApi($call[2], $call[0], $arguments);
        } else {
            $response = $this->contactApi($call[2], $call[0], array_merge($call[3], $arguments));
        }
        $response = html_entity_decode($response);
        switch ($call[1]) {
            case 'array':
                parse_str($response, $response);
                return $response;
                break;
            case 'list':
            case 'text':
            default:
                parse_str($response, $response);
                return $response;
                break;
        }
    }

    /**
     * Sets the API URL
     *
     * @param string $url The API URL
     * @param string $port The API port
     */
    public function setUrl($url, $port = '2222')
    {
        $this->apiUrl = rtrim($url, "/") . ":" . $port;
    }

    /**
     * Sets the API user
     *
     * @param string $username The username
     */
    public function setUser($username)
    {
        $this->user = $username;
    }

    /**
     * Sets the API pass
     *
     * @param string $password The password
     */
    public function setPass($password)
    {
        $this->pass = $password;
    }
}
