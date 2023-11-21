<?php
/**
 * Coinbase Commerce Charges Management
 *
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package coinbase_commerce.commands
 */
class CoinbaseCommerceCharges
{
    /**
     * @var OpensrsApi
     */
    private $api;

    /**
     * Sets the API to use for communication
     *
     * @param CoinbaseCommerceApi $api The API to use for communication
     */
    public function __construct(CoinbaseCommerceApi $api)
    {
        $this->api = $api;
    }

    /**
     * Creates a charge and generates a payment addresses
     *
     * @param array $vars An array of input params including:
     *
     *  - name The name of the company
     *  - description The description of the payment
     *  - pricing_type Pricing of the payment (fixed_price)
     *  - redirect_url The URL to redirect after a successful payment
     *  - cancel_url The URL to redirect after a failed payment
     * @return CoinbaseCommerceResponse The response object
     */
    public function charge(array $vars) : CoinbaseCommerceResponse
    {
        return $this->api->submit('/charges', $vars);
    }

    /**
     * Fetches an existing charge
     *
     * @param array $vars An array of input params including:
     *
     *  - id The ID of the charge to fetch
     * @return CoinbaseCommerceResponse The response object
     */
    public function get(array $vars) : CoinbaseCommerceResponse
    {
        return $this->api->submit('/charges/' . ($vars['id'] ?? null));
    }
}
