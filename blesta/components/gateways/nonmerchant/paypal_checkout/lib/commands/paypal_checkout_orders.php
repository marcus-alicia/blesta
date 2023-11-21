<?php
/**
 * PayPal Checkout Orders Management
 *
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package paypal_checkout.commands
 */
class PaypalCheckoutOrders
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
     * Creates an order
     *
     * @param array $vars An array of input params including:
     *
     *  - purchase_units An array of purchase units. Each purchase unit establishes
     *      a contract between a payer and the payee
     *      - amount: The total order amount
     *      - description: The purchase description
     *      - supplementary_data: Contains Supplementary Data
     *  - intent The intent to either capture payment immediately or authorize
     *      a payment for an order after order creation
     * @return PaypalCheckoutResponse The response object
     */
    public function create(array $vars) : PaypalCheckoutResponse
    {
        return $this->api->apiRequest('/v2/checkout/orders', $vars, 'POST');
    }

    /**
     * Fetches an order
     *
     * @param array $vars An array of input params including:
     *
     *   - id The ID of the order for which to show details
     * @return PaypalCheckoutResponse The response object
     */
    public function get(array $vars) : PaypalCheckoutResponse
    {
        return $this->api->apiRequest(
            '/v2/checkout/orders/' . ($vars['id'] ?? ''),
            ['fields' => $vars['fields'] ?? null]
        );
    }

    /**
     * Updates an existing order
     *
     * @param array $vars An array of input params including:
     *
     *  - id The ID of the order to update
     *  - op The operation, it could be: replace, add or remove
     *  - path The JSON Pointer to the target document location at which to complete the operation
     *  - value The value to apply. The remove operation does not require a value.
     * @return PaypalCheckoutResponse The response object
     * @see https://developer.paypal.com/docs/api/orders/v2/#orders_patch
     */
    public function update(array $vars) : PaypalCheckoutResponse
    {
        $params = $vars;
        unset($params['id']);

        return $this->api->apiRequest(
            '/v2/checkout/orders/' . ($vars['id'] ?? ''),
            $params,
            'PATCH'
        );
    }

    /**
     * Confirms an existing order
     *
     * @param array $vars An array of input params including:
     *
     *  - id The ID of the order for which the payer confirms their intent to pay
     *  - processing_instruction The instruction to process an order
     *  - application_context Customizes the payer confirmation experience
     *  - payment_source The payment source definition
     * @return PaypalCheckoutResponse The response object
     */
    public function confirm(array $vars) : PaypalCheckoutResponse
    {
        $params = $vars;
        unset($params['id']);

        return $this->api->apiRequest(
            '/v2/checkout/orders/' . ($vars['id'] ?? '') . '/confirm-payment-source',
            $params,
            'POST'
        );
    }

    /**
     * Authorize payment for order
     *
     * @param array $vars An array of input params including:
     *
     *  - id The ID of the order for which to authorize
     *  - payment_source The source of payment for the order
     * @return PaypalCheckoutResponse The response object
     */
    public function authorize(array $vars) : PaypalCheckoutResponse
    {
        $params = $vars;
        unset($params['id']);

        return $this->api->apiRequest(
            '/v2/checkout/orders/' . ($vars['id'] ?? '') . '/authorize',
            $params,
            'POST'
        );
    }

    /**
     * Capture payment for order
     *
     * @param array $vars An array of input params including:
     *
     *  - id The ID of the order for which to capture
     *  - payment_source The source of payment for the order
     * @return PaypalCheckoutResponse The response object
     */
    public function capture(array $vars) : PaypalCheckoutResponse
    {
        $params = $vars;
        unset($params['id']);

        return $this->api->apiRequest(
            '/v2/checkout/orders/' . ($vars['id'] ?? '') . '/capture',
            $params,
            'POST'
        );
    }
}