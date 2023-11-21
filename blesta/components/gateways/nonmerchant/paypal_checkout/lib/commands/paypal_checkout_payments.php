<?php
/**
 * PayPal Checkout Payments Management
 *
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package paypal_checkout.commands
 */
class PaypalCheckoutPayments
{
    /**
     * @var PaypalCheckoutApi
     */
    private $api;

    /**
     * Sets the API to use for communication
     *
     * @param PaypalCheckoutApi $api The API to use for communication
     */
    public function __construct(PaypalCheckoutApi $api)
    {
        $this->api = $api;
    }

    /**
     * Show details for captured payment
     *
     * @param array $vars An array of input params including:
     *
     *  - id The ID of the captured payment for which to show details
     * @return PaypalCheckoutResponse The response object
     */
    public function get(array $vars) : PaypalCheckoutResponse
    {
        return $this->api->apiRequest('/v2/payments/captures/' . ($vars['id'] ?? ''));
    }

    /**
     * Refund captured payment
     *
     * @param array $vars An array of input params including:
     *
     *  - capture_id The PayPal-generated ID for the captured payment to refund
     *  - note_to_payer The reason for the refund
     *  - amount The amount to refund
     *  - payment_source The payment source definition
     * @return PaypalCheckoutResponse The response object
     * @see https://developer.paypal.com/docs/api/payments/v2/#captures_refund
     */
    public function refund(array $vars) : PaypalCheckoutResponse
    {
        $params = $vars;
        unset($params['capture_id']);

        return $this->api->apiRequest(
            '/v2/payments/captures/' . ($vars['capture_id'] ?? '') . '/refund',
            $params,
            'POST'
        );
    }

    /**
     * Void an authorized payment
     *
     * @param array $vars An array of input params including:
     *
     *  - authorization_id The PayPal-generated ID for the captured payment to refund
     * @return PaypalCheckoutResponse The response object
     * @see https://developer.paypal.com/docs/api/payments/v2/#captures_refund
     */
    public function void(array $vars) : PaypalCheckoutResponse
    {
        return $this->api->apiRequest(
            '/v2/payments/authorization_id/' . ($vars['authorization_id'] ?? '') . '/void',
            [],
            'POST'
        );
    }

    /**
     * Capture payment
     *
     * @param array $vars An array of input params including:
     *
     *  - id The PayPal-generated ID for the authorized payment to capture
     *  - note_to_payer The reason for the payment
     *  - amount The amount to capture
     *  - final_capture Indicates whether you can make additional captures against the authorized payment.
     *      Set to true if you do not intend to capture additional payments against the authorization.
     * @return PaypalCheckoutResponse The response object
     */
    public function capture(array $vars) : PaypalCheckoutResponse
    {
        $params = $vars;
        unset($params['id']);

        return $this->api->apiRequest(
            '/v2/payments/authorizations/' . ($vars['id'] ?? '') . '/capture',
            $params,
            'POST'
        );
    }
}