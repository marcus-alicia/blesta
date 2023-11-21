<?php
/**
 * Skrill payment gateway API.
 *
 * @package blesta
 * @subpackage blesta.components.gateways.nonmerchant.skrill.lib
 * @author Phillips Data, Inc.
 * @author Nirays Technologies
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 * @link http://nirays.com/ Nirays
 */
class Skrillapi
{
    /**
     * @var The response from the gateway
     */
    private $response = '';
    /**
     * @var The error response from the gateway
     */
    private $error = '';
    /**
     * @var The payment URL
     */
    private $url = 'https://pay.skrill.com/';
    /**
     * @var The refund URL
     */
    private $refund_url = 'https://www.skrill.com/app/refund.pl';

    /**
     * Initializes the request parameter
     *
     * @param string $user_email The Skrill username
     * @param string $secret_word The secret word used in developer settings
     * @param string $merchant_id The account id
     * @param string $mqi The MQI password
     */
    public function __construct($user_email, $secret_word = false, $merchant_id = false, $mqi = false)
    {

        //Set authorization parameters user email required for each request
        $this->user_email = $user_email;
        $this->secret_word = $secret_word;
        $this->merchant_id = $merchant_id ? $merchant_id : '';
        $this->mqi = $mqi ? $mqi : '';
    }

    /**
     * Returns the success response
     *
     * @return string The response from gateway
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Retrieves the refund response, if any
     *
     * @return mixed The response from the gateway
     */
    public function getRefundResponse()
    {
        return (isset($this->refund_response) ? $this->refund_response : '');
    }

    /**
     * Returns the error message from the gateway
     *
     * @return string The error message
     */

    public function getError()
    {
        return $this->error;
    }

    /**
     * Retrieves the payment URL to Skrill
     *
     * @param string $session_id The session ID to append to the payment URL (optional)
     * @return string The payment URL
     */
    public function getPaymentUrl($session_id = null)
    {
        return $this->url . ($session_id ? '?sid=' . $session_id : '');
    }

    /**
     * Retrieves the refund URL to Skrill
     *
     * @return string The refund URL
     */
    public function getRefundUrl()
    {
        return $this->refund_url;
    }

    /**
     * Generates a Session identifier
     */
    public function generateSession(array $params)
    {
        $ch = curl_init($this->url);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $this->response = curl_exec($ch);
        $this->error = curl_error($ch);
        curl_close($ch);
    }

    /**
     * Used for creating the redirection URL for making payments
     *
     * @param array $args The parameters to be send to Skrill
     * @param string $request_type The type of request charge / refund
     * @param string $sid The session id
     *
     */
    public function prepareRequest($args = false, $request_type = null, $sid = null)
    {
        $url = $this->url;

        // Set parameters for refund
        if ($request_type == 'refund') {
            $params['action'] = 'prepare';
            $params['email'] = $this->user_email;
            $params['password'] = md5($this->mqi);
            $url = $this->refund_url;
            $args = array_merge($params, (is_array($args) ? $args : []));
        }

        // Used for executing refund request
        if ($sid) {
            unset($args);
            $args =  ['action' => 'refund', 'sid' => $sid];
            $url = $this->refund_url;
        }

        $fields = http_build_query($args);

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // For refund the response  is an xml string
        if ($request_type == 'refund') {
            $this->parseXml(curl_exec($curl), 'refund');
        } else {
            $this->response = curl_exec($curl);
            $this->error = curl_error($curl);
        }
        curl_close($curl);
    }

    /**
     * Parse the response from gateway
     *
     * @param array $fields The post parameters from gateway
     *
     * @return bool true/false
     */
    public function validateResponse($fields)
    {
        $req_fields = [
            'merchant_id' => '',
            'mb_transaction_id' => '',
            'mb_amount' => '',
            'mb_currency' => '',
            'status' => '',
            'md5sig' => '',
            'pay_to_email' => ''
        ];

        foreach ($req_fields as $key => &$value) {
            $value = (isset($fields[$key]) ? $fields[$key] : '');
        }

        // Check the signature
        $signature = md5(
            $req_fields['merchant_id'] . $req_fields['mb_transaction_id']
            . strtoupper(md5($this->secret_word)) . $req_fields['mb_amount']
            . $req_fields['mb_currency'] . $req_fields['status']
        );

        return (strtoupper($signature) == $req_fields['md5sig'] && $req_fields['pay_to_email'] == $this->user_email);
    }
    /**
     * Validate the parameters from return url
     *
     * @param array $fields The get parameters in url
     *
     * @return bool true/false
     */
    public function validateReturnUrl($fields)
    {
        $req_fields = [
            'mb_transaction_id' => '',
            'msid' => ''
        ];

        foreach ($req_fields as $key => &$value) {
            $value = (isset($fields[$key]) ? $fields[$key] : '');
        }

        // Check the signature
        $signature = md5($this->merchant_id . $req_fields['mb_transaction_id'] . strtoupper(md5($this->secret_word)));

        return (strtoupper($signature) == $fields['msid']);
    }

    /**
     * Parse the response from gateway
     *
     * @param string $response The xml response
     * @param string $type The type of action - refund
     *
     */
    public function parseXml($xml, $type)
    {
        $parsed = simplexml_load_string($xml);

        if (isset($parsed)) {
            if (isset($parsed->error)) {
                $this->error = $parsed->error->error_msg;
            }
            if ($type == 'refund') {
                $this->refund_response = json_decode(json_encode($parsed), true);
            } else {
                $this->response = json_decode(json_encode($parsed), true);
            }
        }
    }
}
