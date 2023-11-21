<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'kassacompleet_response.php';

/**
 * Kassa Compleet API.
 *
 * Documentation at https://s3-eu-west-1.amazonaws.com/wl1-apidocs/api.kassacompleet.nl/index.html
 */
class KassaCompleetApi
{
    /**
     * @var string The Kassa Compleet API key
     */
    private $api_key;
    /**
     * @var string The Kassa Compleet API URL
     */
    private $api_url = 'https://api.kassacompleet.nl/';

    /**
     * Initializes the class.
     *
     * @param string $api_key The Kassa Compleet webshop API key
     */
    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    /**
     * Sends a request to the Kassa Compleet API.
     *
     * @param string $method Specifies the endpoint and method to invoke
     * @param array $params The parameters to include in the api call
     * @param string $type The HTTP request type
     * @return KassacompleetResponse An object containing the api response
     */
    private function apiRequest($method, array $params = [], $type = 'GET')
    {
        // Send request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $headers = [
          'Authorization: Basic ' . base64_encode($this->api_key . ':'),
          'Content-Type: application/json',

        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Build GET request
        if ($type == 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            if (!empty($params)) {
                $method = $method . '?' . http_build_query($params);
            }
        }

        // Build POST request
        if ($type == 'POST') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POST, true);

            if (!empty($params)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            }
        }

        // Execute request
        curl_setopt($ch, CURLOPT_URL, $this->api_url . ltrim($method, '/'));
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            $data = json_encode(['message' => curl_error($ch)]);
        }
        curl_close($ch);

        return new KassacompleetResponse($data);
    }

    /**
     * Creates an order in Kassa Compleet using the given data
     *
     * @param array $data A list of data for creating the order including:
     *  - amount The amount being charged on the order
     *  - currency The currency the order will charge in
     *  - description A description of the order
     *  - return_url Where to return the user after they pay the order using Kassa Compleet
     *  - webhook_url Where Kassa Compleet should send updates of the order
     *  - transactions A list of objects, each containing an available payment method and any details for it
     * @return KassacompleetResponse An object containing all the data sent back by Kassa Compleet
     */
    public function createOrder(array $data)
    {
        return $this->apiRequest('v1/orders/', $data, 'POST');
    }

    /**
     * Gets an order from Kassa Compleet
     *
     * @param string $order_id The ID of the order in Kassa Compleet
     * @return KassacompleetResponse An object containing all the data sent back by Kassa Compleet
     */
    public function getOrder($order_id)
    {
        return $this->apiRequest('v1/orders/' . $order_id . '/');
    }

    /**
     * Refunds an order through Kassa Compleet
     *
     * @param string $order_id The ID of the order in Kassa Compleet
     * @param array $data A list of data for refunding the order including:
     *  - amount The amount to be refunded on the order
     *  - description A description of the refund
     * @return KassacompleetResponse An object containing all the data sent back by Kassa Compleet
     */
    public function refundOrder($order_id, array $data)
    {
        return $this->apiRequest('v1/orders/' . $order_id . '/refunds/', $data, 'POST');
    }

    /**
     * Gets a list of iDeal issuers from Kassa Compleet
     *
     * @return KassacompleetResponse An object containing all the data sent back by Kassa Compleet
     */
    public function getIssuers()
    {
        return $this->apiRequest('v1/ideal/issuers/');
    }
}
