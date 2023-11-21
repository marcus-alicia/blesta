<?php
use Blesta\Core\Util\Common\Traits\Container;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'coinbase_commerce_response.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . 'coinbase_commerce_charges.php';

/**
 * Coinbase Commerce API processor
 *
 * Documentation on the Coinbase Commerce API: https://docs.cloud.coinbase.com/commerce/docs/
 *
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package coinbase_commerce
 */
class CoinbaseCommerceApi
{
    // Load traits
    use Container;

    const API_URL = 'https://api.commerce.coinbase.com/';

    /**
     * @var string The key to use when connecting
     */
    private $api_key;

    /**
     * @var array An array representing the last request made
     */
    private $last_request = ['url' => null, 'args' => null];

    /**
     * Sets the connection details
     *
     * @param string $api_key The user to connect as
     */
    public function __construct(string $api_key)
    {
        $this->api_key = $api_key;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Submits a request to the API
     *
     * @param string $endpoint The endpoint to submit
     * @param array $args An array of key/value pair arguments to submit to the given API command
     * @return CoinbaseCommerceResponse The response object
     */
    public function submit(string $endpoint, array $args = []) : CoinbaseCommerceResponse
    {
        // Set API endpoint url
        $url = self::API_URL . ltrim($endpoint, '/');

        // Set last request
        $this->last_request = [
            'url' => $url,
            'args' => $args
        ];

        // Send request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-CC-Api-Key: ' . trim($this->api_key),
            'X-CC-Version: 2018-03-22'
        ]);

        if (!empty($args)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($args));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        }

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $response = curl_exec($ch);

        if ($response == false) {
            $this->logger->error(curl_error($ch));
        }

        curl_close($ch);

        return new CoinbaseCommerceResponse($response);
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
