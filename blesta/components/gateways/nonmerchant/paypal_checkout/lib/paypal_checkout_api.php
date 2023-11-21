<?php
use Blesta\Core\Util\Common\Traits\Container;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'paypal_checkout_response.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . 'paypal_checkout_orders.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . 'paypal_checkout_payments.php';

/**
 * PayPal Checkout API
 *
 * Documentation: https://developer.paypal.com/docs/api/orders/v2/
 *
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package paypal_checkout.commands
 */
class PaypalCheckoutApi
{
    // Load traits
    use Container;
    
    /**
     * @var array The API URL
     */
    private $api_url = [
        'sandbox' => 'https://api-m.sandbox.paypal.com',
        'live' => 'https://api-m.paypal.com'
    ];

    /**
     * @var array The data sent with the last request served by this API
     */
    private $last_request = [];

    /**
     * @var string The client ID used for OAuth authentication
     */
    private $client_id;

    /**
     * @var string The client secret used for OAuth authentication
     */
    private $client_secret;

    /**
     * @var string The API environment, it could be live or sandbox
     */
    private $environment;

    /**
     * @var string The OAuth token returned by PayPal to be used on this instance
     */
    private $token;

    /**
     * Initializes the request parameter
     */
    public function __construct(string $client_id, string $client_secret, string $environment = 'sandbox')
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->environment = $environment;

        // Authenticate to PayPal API
        $this->authenticate();

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Authenticates to the PayPal API using OAuth
     *
     * @return string The OAuth token
     */
    private function authenticate()
    {
        $permissions = ['grant_type' => 'client_credentials'];
        $headers = [
            'Authorization: Basic ' . base64_encode($this->client_id . ':' . $this->client_secret),
            'Content-Type: application/x-www-form-urlencoded'
        ];
        $session = $this->apiRequest('/v1/oauth2/token', $permissions, 'POST', $headers);
        $response = $session->response();

        $this->token = $response->access_token ?? null;

        if (empty($this->token) || $session->errors()) {
            throw new Exception('It was not possible to authenticate to the API. Verify that the credentials are correct.');
        }

        return $this->token;
    }

    /**
     * Send an API request to PaypalCheckout
     *
     * @param string $route The path to the API method
     * @param array $body The data to be sent
     * @param string $method Data transfer method (POST, GET, PUT, DELETE)
     * @param array $headers Overrides the default headers for this request
     * @return PaypalCheckoutResponse
     */
    public function apiRequest($route, array $body = [], $method = 'GET', array $headers = [])
    {
        $url = $this->api_url[$this->environment] . '/' . ltrim($route ?? '', '/');
        $curl = curl_init();

        if (!empty($body)) {
            switch (strtoupper($method)) {
                case 'DELETE':
                    // Set data using get parameters
                case 'GET':
                    $url .= empty($body) ? '' : '?' . http_build_query($body);
                    break;
                case 'POST':
                    curl_setopt($curl, CURLOPT_POST, 1);
                // Use the default behavior to set data fields
                default:
                    if (in_array('Content-Type: application/x-www-form-urlencoded', $headers)) {
                        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($body));
                    } else {
                        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
                    }
                    break;
            }
        }

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSLVERSION, 1);

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        // Set request headers
        if (empty($headers)) {
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->token
            ];
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $this->last_request = ['content' => $body, 'headers' => $headers];
        $result = curl_exec($curl);
        if (curl_errno($curl)) {
            $error = [
                'error' => 'Curl Error',
                'message' => 'An internal error occurred, or the server did not respond to the request.',
                'status' => 500
            ];

            return new PaypalCheckoutResponse(['content' => json_encode($error), 'headers' => []]);
        }
        curl_close($curl);

        $data = explode("\n", $result);

        // Return request response
        return new PaypalCheckoutResponse([
            'content' => $data[count($data) - 1],
            'headers' => array_splice($data, 0, count($data) - 1)]
        );
    }

    /**
     * Returns the details of the last request made
     *
     * @return array An array containing:
     *  - content The data of the request
     *  - headers The headers sent to the request
     */
    public function lastRequest() : array
    {
        return $this->last_request;
    }
}
