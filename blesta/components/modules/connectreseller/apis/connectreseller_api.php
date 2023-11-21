<?php
use Blesta\Core\Util\Common\Traits\Container;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'connectreseller_response.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . 'connectreseller_domain.php';

/**
 * ConnectReseller API
 *
 * Documentation on the ConnectReseller API: https://resources.connectreseller.com/downloads/API_Document.pdf
 *
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package connectreseller
 */
class ConnectresellerApi
{
    // Load traits
    use Container;

    /**
     * @var string The URL of the API for ConnectReseller
     */
    private $api_url = 'https://api.connectreseller.com/ConnectReseller/ESHOP';

    /**
     * @var string The API key
     */
    private $api_key;

    /**
     * @var array The data sent with the last request served by this API
     */
    private $last_request = [];

    /**
     * Initializes the request parameter
     *
     * @param string $api_key The API key
     */
    public function __construct(string $api_key)
    {
        $this->api_key = $api_key;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Send an API request to ConnectReseller
     *
     * @param string $route The path to the API method
     * @param array $params The data to be sent
     * @param string $method Data transfer method (POST, GET, PUT, DELETE)
     * @return ConnectresellerResponse
     */
    public function apiRequest(string $route, array $params = [], string $method = 'GET') : ConnectresellerResponse
    {
        $url = $this->api_url . '/' . ltrim($route, '/');

        // Set API key and password
        $params = array_merge($params, [
            'APIKey' => $this->api_key
        ]);

        $curl = curl_init();
        switch (strtoupper($method)) {
            case 'DELETE':
                // Set data using get parameters
            case 'GET':
                $url .= empty($params) ? '' : '?' . http_build_query($params);
                break;
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);
                // Use the default behavior to set data fields
            default:
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
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
        $this->last_request = ['url' => $url, 'params' => array_merge($params, ['APIKey' => '***'])];
        $result = curl_exec($curl);

        if (curl_errno($curl)) {
            $error = [
                'error' => 'Curl Error',
                'message' => 'An internal error occurred, or the server did not respond to the request.',
                'status' => 500
            ];
            $this->logger->error(curl_error($curl));

            return new ConnectresellerResponse(['content' => json_encode($error), 'headers' => []]);
        }
        curl_close($curl);

        $data = explode("\n", $result);

        // Return request response
        return new ConnectresellerResponse([
            'content' => $data[count($data) - 1],
            'headers' => array_splice($data, 0, count($data) - 1)]
        );
    }

    /**
     * Returns the details of the last request made
     *
     * @return array An array containing:
     *  - url The URL of the last request
     *  - params The parameters passed to the URL
     */
    public function lastRequest() : array
    {
        return $this->last_request;
    }
}
