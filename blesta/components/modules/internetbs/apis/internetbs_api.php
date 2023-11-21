<?php
use Blesta\Core\Util\Common\Traits\Container;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'internetbs_response.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . 'internetbs_domain.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . 'internetbs_domain_host.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . 'internetbs_domain_urlforward.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . 'internetbs_domain_emailforward.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . 'internetbs_domain_dnsrecord.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . 'internetbs_account.php';

/**
 * Internet.bs API
 *
 * Documentation on the Internet.bs API: https://internetbs.net/internet-bs-api.pdf
 *
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package internetbs
 */
class InternetbsApi
{
    // Load traits
    use Container;

    /**
     * @var string The API URL
     */
    private $api_url = 'https://api.internet.bs';

    /**
     * @var string The sandbox API URL
     */
    private $sandbox_api_url = 'https://testapi.internet.bs';

    /**
     * @var string The API key provided by Internet.bs
     */
    private $api_key;

    /**
     * @var string The password provided by Internet.bs
     */
    private $password;

    /**
     * @var bool Whether to use or not the sandbox API
     */
    private $sandbox;

    /**
     * @var array The data sent with the last request served by this API
     */
    private $last_request = [];

    /**
     * Initializes the request parameter
     *
     * @param string $api_key The API key provided by Internet.bs
     * @param string $password The password provided by Internet.bs
     */
    public function __construct(string $api_key, string $password, bool $sandbox = false)
    {
        $this->api_key = $api_key;
        $this->password = $password;
        $this->sandbox = $sandbox;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Send an API request to Internet.bs
     *
     * @param string $route The path to the API method
     * @param array $args The data to be sent
     * @param string $method Data transfer method (POST, GET, PUT, DELETE)
     * @return InternetbsResponse The API response
     */
    public function apiRequest(string $route, array $args = [], string $method = 'GET') : InternetbsResponse
    {
        $url = ($this->sandbox ? $this->sandbox_api_url : $this->api_url) . '/' . ltrim($route, '/');
        $curl = curl_init();

        // Set API key and password
        $args = array_merge($args, [
            'ApiKey' => $this->api_key,
            'Password' => $this->password,
            'ResponseFormat' => 'JSON'
        ]);

        switch (strtoupper($method)) {
            case 'DELETE':
                // Set data using get parameters
            case 'GET':
                $url .= empty($args) ? '' : '?' . http_build_query($args);
                break;
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);
                // Use the default behavior to set data fields
            default:
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($args));
                break;
        }

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSLVERSION, 1);

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        // Save last request
        $this->last_request = ['url' => $url, 'args' => array_merge($args, ['ApiKey' => '***', 'Password' => '***'])];
        $result = curl_exec($curl);

        if (curl_errno($curl) || $result == false) {
            $error = [
                'error' => 'Curl Error',
                'message' => 'An internal error occurred, or the server did not respond to the request.',
                'status' => 500
            ];
            $this->logger->error(curl_error($curl));

            return new InternetbsResponse(['content' => json_encode($error), 'headers' => []]);
        }
        curl_close($curl);

        $data = explode("\n", $result);

        // Return request response
        return new InternetbsResponse([
            'content' => $data[count($data) - 1],
            'headers' => array_splice($data, 0, count($data) - 1)]
        );
    }

    /**
     * Returns the details of the last request made
     *
     * @return array An array containing:
     *  - url The URL of the last request
     *  - args The parameters passed to the URL
     */
    public function lastRequest() : array
    {
        return $this->last_request;
    }
}
