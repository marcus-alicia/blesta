<?php
/**
 * Authorize.net AIM (Advanced Integration Method) and eCheck APIs
 *
 * An API for performing remote requests to Authorize.net using the AIM and eCheck APIs.
 *
 * Documentation on the AIM (Advanced Integration Method) API can be found at:
 * http://www.authorize.net/support/AIM_guide.pdf
 * eCheck payment processing utilizes the AIM API, but has supplemental documentation at:
 * http://developer.authorize.net/guides/echeck.pdf
 *
 * @package blesta
 * @subpackage blesta.components.gateways.authorize_net.apis
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AuthorizeNetAim
{
    /**
     * @var string The version of the AIM API to use
     */
    private static $aim_version = '3.1';
    /**
     * @var string The AIM test URL
     */
    private static $aim_test_url = 'https://test.authorize.net/gateway/transact.dll';
    /**
     * @var string The AIM live URL
     */
    private static $aim_live_url = 'https://secure.authorize.net/gateway/transact.dll';
    /**
     * @var bool Whether or not to submit payments as test transactions
     */
    private $test_mode = false;
    /**
     * @var bool Whether or not to submit payments using the test URL
     */
    private $dev_mode = false;
    /**
     * @var array The last response from the gateway
     */
    private $last_response = [];
    /**
     * @var string The last raw response from the gateway
     */
    private $raw_response = null;
    /**
     * @var array The set of parameters from the last request
     */
    private $last_params = [];
    /**
     * @var string The URL from the last request
     */
    private $last_url = null;
    /**
     * @var array Holds all request parameters set through the constructor
     */
    private $auth_params = [];
    /**
     * @var string The currency to use
     */
    private $currency;

    /**
     * Initializes the request parameter
     *
     * @param string $login_id The Authorize.net login ID
     * @param string $trans_key The Autnorize.net transaction key
     * @param bool $test_mode If true, will submit the request as a test transaction
     * @param bool $dev_mode If true, will submit to the AIM test URL rather than the live URL
     * @param char $delim_char The character to use as the delimiter in the response from the gateway
     * @param char $encap_char The character to use to encapsulate each delimited response
     */
    public function __construct(
        $login_id,
        $trans_key,
        $test_mode = false,
        $dev_mode = false,
        $delim_char = '|',
        $encap_char = ''
    ) {

        // Set test mode option
        $this->test_mode = $test_mode;
        // Set dev mode option
        $this->dev_mode = $dev_mode;

        // Set the authorization parameters required for each request
        $this->auth_params['x_login'] = $login_id;
        $this->auth_params['x_tran_key'] = $trans_key;
        $this->auth_params['x_delim_data'] = 'TRUE'; // response must be delimited
        $this->auth_params['x_delim_char'] = $delim_char;
        $this->auth_params['x_encap_char'] = $encap_char;
        $this->auth_params['x_relay_response'] = 'FALSE'; // AIM doesn't support relay responses
        $this->auth_params['x_version'] = self::$aim_version;

        if ($this->test_mode) {
            $this->auth_params['x_test_request'] = 'TRUE';
        }
    }

    /**
     * Sets the currency code to be used for all subsequent requests
     *
     * @param string $currency The ISO 4217 currency code to be used for subsequent requests
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * Authorizes a credit card transaction
     *
     * @param array $card_info An array of credit card info including:
     *  - first_name The first name on the card
     *  - last_name The last name on the card
     *  - card_number The card number
     *  - card_exp The card expidation date in yyyymm format
     *  - card_security_code The 3 or 4 digit security code of the card (if available)
     *  - address1 The address 1 line of the card holder
     *  - address2 The address 2 line of the card holder
     *  - city The city of the card holder
     *  - state An array of state info including:
     *      - code The 2 or 3-character state code
     *      - name The local name of the country
     *  - country An array of country info including:
     *      - alpha2 The 2-character country code
     *      - alpha3 The 3-cahracter country code
     *      - name The english name of the country
     *      - alt_name The local name of the country
     *  - zip The zip/postal code of the card holder
     * @param float $amount The amount to charge this card
     * @param array $invoice_amounts An array of invoices, each containing:
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array The response from the gateway
     */
    public function authCc(array $card_info, $amount, array $invoice_amounts = null)
    {
        $params = [
            'x_type' => 'AUTH_ONLY',
            'x_amount' => $amount,
            'x_card_num' => (isset($card_info['card_number']) ? $card_info['card_number'] : null),
            // from YYYYMM to MMYY
            'x_exp_date' => substr((isset($card_info['card_exp']) ? $card_info['card_exp'] : null), -2)
                . substr((isset($card_info['card_exp']) ? $card_info['card_exp'] : null), 2, 2),
            'x_card_code' => (isset($card_info['card_security_code']) ? $card_info['card_security_code'] : null),
            'x_method' => 'CC',
            'x_first_name' => (isset($card_info['first_name']) ? $card_info['first_name'] : null),
            'x_last_name' => (isset($card_info['last_name']) ? $card_info['last_name'] : null),
            'x_address' => (isset($card_info['address1']) ? $card_info['address1'] : null),
            'x_city' => (isset($card_info['city']) ? $card_info['city'] : null),
            'x_state' => (isset($card_info['state']['code']) ? $card_info['state']['code'] : null),
            'x_zip' => (isset($card_info['zip']) ? $card_info['zip'] : null),
            'x_country' => (isset($card_info['country']['alpha2']) ? $card_info['country']['alpha2'] : null),
            'x_currency_code' => $this->currency
        ];

        // If invoices given, pass along the 1st (since the gateway limits us
        // to 20 chars, we pretty much can only send one ID)
        if ($invoice_amounts) {
            $params['x_invoice_num'] = (isset($invoice_amounts[0]['invoice_id']) ? $invoice_amounts[0]['invoice_id'] : null);
        }

        return $this->submit($params);
    }

    /**
     * Captures the funds of a previously authorized credit card transaction
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @param float $amount The amount to capture
     * @param array $invoice_amounts An array of invoices, each containing:
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array The response from the gateway
     */
    public function captureCc($transaction_id, $amount, array $invoice_amounts = null)
    {
        $params = [
            'x_type'=>'PRIOR_AUTH_CAPTURE',
            'x_trans_id'=>$transaction_id,
            'x_method'=>'CC',
            'x_amount'=>$amount,
            'x_currency_code' => $this->currency
        ];

        // If invoices given, pass along the 1st (since the gateway limits us
        // to 20 chars, we pretty much can only send one ID)
        if ($invoice_amounts) {
            $params['x_invoice_num'] = (isset($invoice_amounts[0]['invoice_id']) ? $invoice_amounts[0]['invoice_id'] : null);
        }

        return $this->submit($params);
    }

    /**
     * Autorizes and captures a credit card transaction
     *
     * @param array $card_info An array of credit card info including:
     *  - first_name The first name on the card
     *  - last_name The last name on the card
     *  - card_number The card number
     *  - card_exp The card expiration date in yyyymm format
     *  - card_security_code The 3 or 4 digit security code of the card (if available)
     *  - address1 The address 1 line of the card holder
     *  - address2 The address 2 line of the card holder
     *  - city The city of the card holder
     *  - state An array of state info including:
     *      - code The 2 or 3-character state code
     *      - name The local name of the country
     *  - country An array of country info including:
     *      - alpha2 The 2-character country code
     *      - alpha3 The 3-character country code
     *      - name The english name of the country
     *      - alt_name The local name of the country
     *  - zip The zip/postal code of the card holder
     * @param float $amount The amount to charge this card
     * @param array $invoice_amounts An array of invoices, each containing:
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array The response from the gateway
     */
    public function authCaptureCc(array $card_info, $amount, array $invoice_amounts = null)
    {
        $params = [
            'x_type' => 'AUTH_CAPTURE',
            'x_amount' => $amount,
            'x_card_num' => (isset($card_info['card_number']) ? $card_info['card_number'] : null),
            // from YYYYMM to MMYY
            'x_exp_date' => substr((isset($card_info['card_exp']) ? $card_info['card_exp'] : null), -2)
                . substr((isset($card_info['card_exp']) ? $card_info['card_exp'] : null), 2, 2),
            'x_card_code' => (isset($card_info['card_security_code']) ? $card_info['card_security_code'] : null),
            'x_method' => 'CC',
            'x_first_name' => (isset($card_info['first_name']) ? $card_info['first_name'] : null),
            'x_last_name' => (isset($card_info['last_name']) ? $card_info['last_name'] : null),
            'x_address' => (isset($card_info['address1']) ? $card_info['address1'] : null),
            'x_city' => (isset($card_info['city']) ? $card_info['city'] : null),
            'x_state' => (isset($card_info['state']['code']) ? $card_info['state']['code'] : null),
            'x_zip' => (isset($card_info['zip']) ? $card_info['zip'] : null),
            'x_country' => (isset($card_info['country']['alpha2']) ? $card_info['country']['alpha2'] : null),
            'x_currency_code' => $this->currency
        ];

        // If invoices given, pass along the 1st (since the gateway limits us
        // to 20 chars, we pretty much can only send one ID)
        if ($invoice_amounts) {
            $params['x_invoice_num'] = (isset($invoice_amounts[0]['invoice_id']) ? $invoice_amounts[0]['invoice_id'] : null);
        }

        return $this->submit($params);
    }

    /**
     * Void a credit card charge
     *
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @return array The response from the gateway
     */
    public function voidCc($transaction_id)
    {
        $params = [
            'x_type'=>'VOID',
            'x_trans_id'=>$transaction_id,
            'x_method'=>'CC'
        ];
        return $this->submit($params);
    }

    /**
     * Refund a credit card charge
     *
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @param string $last4 The last 4 of the card used in the transaction
     * @param float $amount The amount to refund this card
     * @return array The response from the gateway
     */
    public function refundCc($transaction_id, $last4, $amount)
    {
        $params = [
            'x_type' => 'CREDIT',
            'x_trans_id' => $transaction_id,
            'x_method' => 'CC',
            'x_amount' => $amount,
            'x_card_num' => $last4,
            'x_currency_code' => $this->currency
        ];
        return $this->submit($params);
    }

    /**
     * Attempts to autorize and capture an ACH transaction using the given ID
     *
     * @param array $account_info An array of bank account info including:
     *  - first_name The first name on the account
     *  - last_name The last name on the account
     *  - account_number The bank account number
     *  - routing_number The bank account routing number
     *  - type The bank account type (checking, savings, business_checking)
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
     * @param float $amount The amount to debit this account
     * @param array $invoice_amounts An array of invoices, each containing:
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @param string $echeck_type The type of eCheck transaction (default WEB)
     * @return array The response from the gateway
     */
    public function authCaptureAch(array $account_info, $amount, $echeck_type = 'WEB', array $invoice_amounts = null)
    {
        switch ((isset($account_info['type']) ? $account_info['type'] : null)) {
            default:
            case 'checking':
                $account_type = 'CHECKING';
                break;
            case 'savings':
                $account_type = 'SAVINGS';
                break;
            case 'business_checking':
                $account_type = 'BUSINESSCHECKING';
                break;
        }

        $params = [
            'x_type' => 'AUTH_CAPTURE',
            'x_method' => 'ECHECK',
            'x_amount' => $amount,
            'x_bank_aba_code' => (isset($account_info['routing_number']) ? $account_info['routing_number'] : null),
            'x_bank_acct_num' => (isset($account_info['account_number']) ? $account_info['account_number'] : null),
            'x_bank_acct_type' => $account_type,
            'x_bank_name' => (isset($account_info['bank_name']) ? $account_info['bank_name'] : null),
            'x_bank_acct_name' => (isset($account_info['first_name']) ? $account_info['first_name'] : null)
                . ' ' . (isset($account_info['last_name']) ? $account_info['last_name'] : null),
            'x_echeck_type' => $echeck_type,
            'x_recurring_billing' => 'FALSE',
            'x_first_name' => (isset($account_info['first_name']) ? $account_info['first_name'] : null),
            'x_last_name' => (isset($account_info['last_name']) ? $account_info['last_name'] : null),
            'x_address' => (isset($account_info['address1']) ? $account_info['address1'] : null),
            'x_city' => (isset($account_info['city']) ? $account_info['city'] : null),
            'x_state' => (isset($account_info['state']['code']) ? $account_info['state']['code'] : null),
            'x_zip' => (isset($account_info['zip']) ? $account_info['zip'] : null),
            'x_country' => (isset($account_info['country']['alpha2']) ? $account_info['country']['alpha2'] : null),
            'x_currency_code' => $this->currency
        ];

        // If invoices given, pass along the 1st (since the gateway limits us
        // to 20 chars, we pretty much can only send one ID)
        if ($invoice_amounts) {
            $params['x_invoice_num'] = (isset($invoice_amounts[0]['invoice_id']) ? $invoice_amounts[0]['invoice_id'] : null);
        }

        return $this->submit($params);
    }

    /**
     * Attempts to void an ACH transaction using the given ID
     *
     * @param int $transaction_id The transaction ID to void
     * @return array The response from the gateway
     */
    public function voidAch($transaction_id)
    {
        $params = [
            'x_type' => 'VOID',
            'x_trans_id' => $transaction_id,
            'x_method' => 'ECHECK'
        ];
        return $this->submit($params);
    }

    /**
     * Attempts to refund an ACH transaction using the given ID and amount
     *
     * @param int $transaction_id The transaction ID to refund
     * @param int $account_last4 The last 4 digits of the account number used in the transaction
     * @param int $routing_last4 The last 4 digits of the routing number used in the transaction
     * @param float The amount to refund
     * @return array The response from the gateway
     */
    public function refundAch($transaction_id, $account_last4, $routing_last4, $amount)
    {
        $params = [
            'x_type' => 'CREDIT',
            'x_bank_acct_num' => $account_last4,
            'x_bank_aba_code' => $routing_last4,
            'x_trans_id' => $transaction_id,
            'x_method' => 'ECHECK',
            'x_amount' => $amount,
            'x_currency_code' => $this->currency
        ];
        return $this->submit($params);
    }

    /**
     * Returns the URL used in the last requests
     *
     * @return string The URL of the last requests
     */
    public function getUrl()
    {
        return $this->last_url;
    }

    /**
     * Returns the parameters sent to the gateway during the last request
     *
     * @return array An array of parameters from the last request
     */
    public function getParams()
    {
        return $this->last_params;
    }

    /**
     * Returns the parsed response of the last request
     *
     * @return array An array of response fields.
     */
    public function getResponse()
    {
        return $this->last_response;
    }

    /**
     * Returns the raw response of the last request
     *
     * @return string The last response (in raw format) from the gateway
     */
    public function getRawResponse()
    {
        return $this->raw_response;
    }

    /**
     * Makes a remote request to the gateway using the provided parameters.
     * Will automatically append the authorization parameters and contact the
     * appropriate URL based on the test mode setting.
     *
     * When the request is submitted, sets both the parameters sent to $this->last_params
     * and response to $this->last_response
     *
     * @param array $params The parameters to submit to the gateway.
     *  Authorization parametsrs will automatically be applied before sending
     * @return array The parsed response from the gateway
     */
    private function submit(array $params)
    {

        // Load the HTTP component, if not already loaded
        if (!isset($this->Http)) {
            Loader::loadComponents($this, ['Net']);
            $this->Http = $this->Net->create('Http');
        }

        // Merge our authorization parameters
        $this->last_params = $params = array_merge($params, $this->auth_params);

        // Set the URL to submit to
        $this->last_url = $url = ($this->dev_mode ? self::$aim_test_url : self::$aim_live_url);

        // Submit the requests
        $this->raw_response = $this->Http->post($url, http_build_query($params));

        return $this->last_response = $this->parseResponse($this->raw_response);
    }

    /**
     * Parse the string response from the gateway into an array
     *
     * @param string $response The response string to parse from the gateway
     * @return array The response string parsed into an array
     */
    private function parseResponse($response)
    {
        if ($response == '') {
            return $response;
        }

        $authnet_array = explode('|', $response);

        try {
            return [
                'x_response_code' => $authnet_array[0],
                'x_response_subcode' => $authnet_array[1],
                'x_response_reason_code' => $authnet_array[2],
                'x_response_reason_text' => $authnet_array[3],
                'x_auth_code' => $authnet_array[4],
                'x_avs_code' => $authnet_array[5],
                'x_trans_id' => $authnet_array[6],
                'x_invoice_num' => $authnet_array[7],
                'x_description' => $authnet_array[8],
                'x_amount' => $authnet_array[9],
                'x_method' => $authnet_array[10],
                'x_type' => $authnet_array[11],
                'x_cust_id' => $authnet_array[12],
                'x_first_name' => $authnet_array[13],
                'x_last_name' => $authnet_array[14],
                'x_company' => $authnet_array[15],
                'x_address' => $authnet_array[16],
                'x_city' => $authnet_array[17],
                'x_state' => $authnet_array[18],
                'x_zip' => $authnet_array[19],
                'x_country' => $authnet_array[20],
                'x_phone' => $authnet_array[21],
                'x_fax' => $authnet_array[22],
                'x_email' => $authnet_array[23],
                'x_ship_to_first_name' => $authnet_array[24],
                'x_ship_to_last_name' => $authnet_array[25],
                'x_ship_to_company' => $authnet_array[26],
                'x_ship_to_address' => $authnet_array[27],
                'x_ship_to_city' => $authnet_array[28],
                'x_ship_to_state' => $authnet_array[29],
                'x_ship_to_zip' => $authnet_array[30],
                'x_ship_to_country' => $authnet_array[31],
                'x_tax' => $authnet_array[32],
                'x_duty' => $authnet_array[33],
                'x_freight' => $authnet_array[34],
                'x_tax_exempt' => $authnet_array[35],
                'x_po_num' => $authnet_array[36],
                'x_md5_hash' => $authnet_array[37],
                'x_cvv2_resp_code' => $authnet_array[38]
            ];
        } catch (Exception $e) {
            // invalid response from gateway
        }

        return null;
    }
}
