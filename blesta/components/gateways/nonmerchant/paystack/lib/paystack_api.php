<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'paystack_response.php';
/**
 * Paystack API.
 *
 * @package blesta
 * @subpackage blesta.components.modules.paystack
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 *
 * Documentation at https://developers.paystack.co/reference#paystack-standard-x
 */
class PaystackApi
{
    /**
     * @var string The Paystack API secret key
     */
    private $secret_key;

    /**
     * Initializes the class.
     *
     * @param string $secret_key The Paystack API secret key
     */
    public function __construct($secret_key)
    {
        $this->secret_key = $secret_key;
    }

    /**
     * Send a request to the Paystack API.
     *
     * @param string $method Specifies the endpoint and method to invoke
     * @param array $params The parameters to include in the api call
     * @param string $type The HTTP request type
     * @return stdClass An object containing the api response
     */
    private function apiRequest($method, array $params = [], $type = 'GET')
    {
        $url = 'https://api.paystack.co/';

        // Send request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $headers = [
          'Authorization: Bearer ' . $this->secret_key,
          'Content-Type: application/json',

        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Build GET request
        if ($type == 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            $method = $method . '?' . http_build_query($params);
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
        curl_setopt($ch, CURLOPT_URL, $url . trim($method, '/'));
        $data = new stdClass();
        if (curl_errno($ch)) {
            $data->message = curl_error($ch);
        } else {
            $data = json_decode(curl_exec($ch));
        }
        curl_close($ch);

        return new PaystackResponse($data);
    }

    /**
     * Build the payment request.
     *
     * @param array $params An array containing the following arguments:
     *  - email: Customer's email address
     *  - amount: Amount in kobo (1/100 niara)
     *  - reference: Unique transaction reference. Only -, ., = and alphanumeric characters allowed.
     *  - metadata: An object with the cancel_action property which controls to url for an aborted transaction
     * @return stdClass An object containing the api response
     */
    public function buildPayment($params)
    {
        return $this->apiRequest('transaction/initialize/', $params, 'POST');
    }


    /**
     * Validate this payment.
     *
     * @param string $reference The unique reference code for this payment
     * @return stdClass An object containing the api response
     */
    public function checkPayment($reference)
    {
        return $this->apiRequest('/transaction/verify/' . $reference, [], 'GET');
    }
}
