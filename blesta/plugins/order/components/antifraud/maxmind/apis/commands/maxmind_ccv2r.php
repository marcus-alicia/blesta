<?php
/**
 * Maxmind minFraud CCV2R
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package maxmind
 */
class MaxmindCcv2r
{
    /**
     * Initialize the MaxmindCcv2r command
     *
     * @param MaxmindApi The Maxmind API
     */
    public function __construct($api)
    {
        $this->api = $api;
    }

    /**
     * Submits the request to Maxmind and returns the result
     *
     * @param array $data An array of input data including:
     *  - i The IP address of the customer placing the order.
     *      This should be passed as a string like "44.55.66.77" or "2001:db8::2:1".
     *  - city The billing city for the customer.
     *  - region The billing region/state for the customer.
     *  - postal The billing postal (zip) code for the customer.
     *  - country The billing country for the customer.
     *      This can be passed as the full country name or as an ISO 3166 code.
     *  - license_key Your MaxMind license key.
     *  - shipAddr The shipping street address.
     *  - shipCity The shipping address city.
     *  - shipRegion The shipping address region/state.
     *  - shipPostal The shipping address postal (zip) code.
     *  - shipCountry The shipping address country.
     *  - domain The domain for the user's email address.
     *  - custPhone The customer's phone number, including area code and local exchange.
     *  - emailMD5 An MD5 hash of the user's email address.
     *  - usernameMD5 An MD5 hash of the user's username.
     *  - passwordMD5 An MD5 hash of the user's password.
     *  - bin The credit card BIN number. This is the first 6 digits of the credit card number.
     *  - binName The name of the bank which issued the credit card, based on the BIN number.
     *  - binPhone The customer service phone number listed on the back of the credit card.
     *  - sessionID Your internal session ID.
     *  - user_agent The User-Agent HTTP header.
     *  - accept_language The Accept-Language HTTP header.
     *  - txnID Your internal transaction ID for the order.
     *  - order_amount The customer's order amount.
     *  - order_currency The currency used for the customer's order as an ISO 4217 code.
     *  - shopID Your internal ID for the shop, affiliate, or merchant this order is coming from.
     *  - txn_type The transaction type. This can be set to one of the following strings:
     *      - creditcard
     *      - debitcard
     *      - paypal
     *      - google - Google checkout
     *      - other
     *      - lead - lead generation
     *      - survey - online survey
     *      - sitereg - site registration
     *  - avs_result The AVS check result, as returned to you by the credit card processor.
     *      The minFraud service accepts the following codes:
     *      - N no match
     *      - Y complete match
     *      - A partial match, address only
     *      - P partial match, postal code only
     *  - cvv_result The CVV check result. This should be either "N" or "Y".
     *  - requested_type This can be set to either "standard" or "premium".
     *      By default, we use the highest level of service available for your account.
     *  - forwardedIP The end user's IP address, as forwarded by a transparent proxy.
     * @return MaxmindResponse The response
     */
    public function request($data)
    {
        return $this->api->submit('app/ccv2r', $data);
    }
}
