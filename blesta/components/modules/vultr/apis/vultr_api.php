<?php
use Blesta\Core\Util\Common\Traits\Container;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vultr_response.php';

/**
 * Vultr API v2
 *
 * @package blesta
 * @subpackage blesta.components.modules.vultr
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class VultrApi
{
    // Load traits
    use Container;

    /**
     * @var string The api key
     */
    private $api_key;

    /**
     * @var array An array representing the last request made
     */
    private $last_request = ['url' => null, 'params' => null];

    /**
     * Initializes the class.
     *
     * @param string $api_key The api key
     */
    public function __construct($api_key)
    {
        $this->api_key = $api_key;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Sends a request to the Vultr API.
     *
     * @param string $method Specifies the endpoint and method to invoke
     * @param array $params The parameters to include in the api call
     * @param string $type The HTTP request type
     * @return VultrResponse An instance of the API response
     */
    public function apiRequest($method, array $params = [], $type = 'GET')
    {
        // Send request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        // Set authentication details
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json'
        ]);

        // Build GET request
        if ($type == 'GET') {
            if (!empty($params)) {
                $get = http_build_query($params);
            }
        }

        // Build body request
        if ($type == 'POST' || $type == 'PATCH' || $type == 'PUT') {
            if ($type == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
            }

            if (!empty($params)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            }
        }

        // Execute request
        $url = 'https://api.vultr.com/v2/' . trim($method, '/');
        $this->last_request = [
            'url' => $url,
            'params' => $params
        ];

        curl_setopt($ch, CURLOPT_URL, $url . (isset($get) ? '?' . $get : null));

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response == false && empty($status)) {
            $error = [
                'error' => 'An internal error occurred, or the server did not respond to the request.',
                'status' => 500
            ];
            $this->logger->error(curl_error($ch));

            return new VultrResponse(['content' => json_encode($error), 'status' => $error['status']]);
        }

        curl_close($ch);

        return new VultrResponse([
            'content' => $response,
            'status' => $status
        ]);
    }

    /**
     * Sends a legacy request to the Vultr API.
     *
     * @param string $method Specifies the endpoint and method to invoke
     * @return stdClass The API response
     */
    public function legacyRequest($method)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['API-Key: ' . $this->api_key]);
        curl_setopt($ch, CURLOPT_URL, 'https://api.vultr.com/v1/' . trim($method, '/'));
                
        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        
        $response = (object) json_decode(curl_exec($ch));
        curl_close($ch);

        return $response;
    }

    /**
     * Returns the details of the last request made.
     *
     * @return array An array containg:
     *     - url: The URL of the last request.
     *     - params: The paramters passed to the URL.
     */
    public function lastRequest()
    {
        return $this->last_request;
    }

    /**
     * Loads a command class.
     *
     * @param string $command The command class filename to load
     */
    public function loadCommand($command)
    {
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . $command . '.php';
    }
}
