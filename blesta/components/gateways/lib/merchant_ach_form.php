<?php
/**
 * Merchant ACH custom form interface. Defines all methods that
 * an ACH form payment gateway must implement.
 *
 * Modifying the form can allow the gateway to add reference_id and client_reference_id fields
 * or javascript to make calls to a gateway's JS API
 *
 * @package blesta
 * @subpackage blesta.components.gateways
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface MerchantAchForm
{
    /**
     * Returns all HTML markup required to render ACH payment form
     *
     * @param array $account_info An array of bank account info including:
     *
     *  - first_name The first name on the account
     *  - last_name The last name on the account
     *  - account_number The bank account number
     *  - routing_number The bank account routing number
     *  - type The bank account type (checking, savings)
     *  - address1 The address 1 line of the account holder
     *  - address2 The address 2 line of the account holder
     *  - city The city of the account holder
     *  - state An array of state info including:
     *      - code The 2 or 3-character state code
     *      - name The local name of the country
     *  - country An array of country info including:
     *      - alpha2 The 2-character country code
     *      - alpha3 The 3-character country code
     *      - name The english name of the country
     *      - alt_name The local name of the country
     *  - zip The zip/postal code of the account holder
     * @return mixed A string of HTML markup required to render an credit card payment form,
     *  or an array of HTML markup
     */
    public function buildAchForm($account_info = null);
}