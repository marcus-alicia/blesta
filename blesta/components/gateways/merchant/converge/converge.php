<?php

/**
 * myvirtualmerchant.com Credit Card processing gateway.
 * Supports onsite payment processing for Credit Cards payments.
 *
 * @package blesta
 * @subpackage blesta.components.gateways.converge
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Converge extends MerchantGateway implements MerchantCc
{
    /**
     * @var array An array of meta data for this gateway
     */
    private $meta;

    /**
     * Construct a new merchant gateway
     */
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('converge', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * Sets the meta data for this particular gateway
     *
     * @param array $meta An array of meta data to set for this gateway
     */
    public function setMeta(array $meta = null)
    {
        $this->meta = $meta;
    }

    /**
     * Create and return the view content required to modify the settings of this gateway
     *
     * @param array $meta An array of meta (settings) data belonging to this gateway
     * @return string HTML content containing the fields to update the meta data for this gateway
     */
    public function getSettings(array $meta = null)
    {
        // Load the view into this object, so helpers can be automatically add to the view
        $this->view = new View('settings', 'default');
        $this->view->setDefaultView('components' . DS . 'gateways' . DS . 'merchant' . DS . 'converge' . DS);
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('meta', $meta);

        return $this->view->fetch();
    }

    /**
     * Validates the given meta (settings) data to be updated for this gateway
     *
     * @param array $meta An array of meta (settings) data to be updated for this gateway
     * @return array The meta data to be updated in the database for this gateway, or reset into the form on failure
     */
    public function editSettings(array $meta)
    {

        // Verify meta data is valid
        $rules = [
            'merchant_id' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Converge!error.login_id.format', true)
                ]
            ],
            'user_id' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Converge!error.transaction_key.format', true)
                ]
            ],
            'pin' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Converge!error.transaction_key.format', true)
                ]
            ],
            'multicurrency' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('ConvergemultiCurrency.error', true)
                ]
            ],
            'live_mode' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['true', 'false']],
                    'message' => Language::_('Converge!error.live_mode.valid', true)
                ]
            ]
        ];
        $this->Input->setRules($rules);

        // Validate the given meta data to ensure it meets the requirements
        $this->Input->validates($meta);
        // Return the meta data, no changes required regardless of success or failure for this gateway
        return $meta;
    }

    /**
     * Returns an array of all fields to encrypt when storing in the database
     *
     * @return array An array of the field names to encrypt when storing in the database
     */
    public function encryptableFields()
    {
        return ['merchant_id', 'user_id', 'pin'];
    }

    /**
     * Used to determine whether this gateway can be configured for autodebiting accounts
     *
     * @return bool True if the customer must be present (e.g. in the case of credit card
     * customer must enter security code), false otherwise
     */
    public function requiresCustomerPresent()
    {
        return false;
    }

    /**
     * Informs the system of whether or not this gateway is configured for offsite customer
     * information storage for credit card payments
     * @return bool True if the gateway expects the offset methods to be called for
     * credit card payments, false to process the normal methods instead
     */
    public function requiresCcStorage()
    {
        return false;
    }

    /**
     * Sets the currency code to be used for all subsequent payments
     *
     * @param string $currency The ISO 4217 currency code to be used for subsequent payments
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * Charge a credit card
     *
     * @param array $card_info An array of credit card info including:
     *  - first_name The first name on the card
     *  - last_name The last name on the card
     *  - card_number The card number
     *  - card_exp The card expiration date
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
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard message
     * for this transaction status (optional)
     */
    public function processCc(array $card_info, $amount, array $invoice_amounts = null)
    {
        $api = $this->loadApi();
        $actionInfo = [
            'ssl_amount' => $amount,
            'ssl_card_number' => $card_info['card_number'],
            'ssl_cvv2cvc2' => $card_info['card_security_code'],
            'ssl_exp_date' => $this->formatExpiryDate($card_info['card_exp']),
            'ssl_avs_zip' => $card_info['zip'],
            'ssl_avs_address' => $card_info['address1'],
            'ssl_first_name' => $card_info['first_name'],
            'ssl_last_name' => $card_info['last_name'],
        ];

        if ($this->meta['multicurrency']) {
            $actionInfo['ssl_transaction_currency'] = $this->currency;
        }


        if ($invoice_amounts) {
            $actionInfo['ssl_invoice_number'] = (isset($invoice_amounts[0]['invoice_id']) ? $invoice_amounts[0]['invoice_id'] : null);
        }

        $response = $api->ccsale($actionInfo);


        if (isset($response['ssl_result']) && $response['ssl_result'] == '0') {
            $status = 'approved';
            $success = true;
        } else {
            $status = 'declined';
            $response['ssl_result'] = isset($response['errorMessage'])?$response['errorMessage']:null;
            $success = false;
        }

        $this->logActions('ccsale', $success, isset($response['ssl_result_message']) ?
                $response['ssl_result_message'] : null, $actionInfo);


        return [
            'status' => $status,
            'reference_id' => substr((isset($card_info['card_number']) ? $card_info['card_number'] : null), -4),
            'transaction_id' => (isset($response['ssl_txn_id']) ? $response['ssl_txn_id'] : null),
            'message' => (isset($response['ssl_result_message']) ? $response['ssl_result_message'] : null)
        ];
    }

    /**
     * Authorize a credit card
     *
     * @param array $card_info An array of credit card info including:
     *  - first_name The first name on the card
     *  - last_name The last name on the card
     *  - card_number The card number
     *  - card_exp The card expidation date
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
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     * message for this transaction status (optional)
     */
    public function authorizeCc(array $card_info, $amount, array $invoice_amounts = null)
    {
        $api = $this->loadApi();
        $actionInfo = [
            'ssl_amount' => $amount,
            'ssl_card_number' => $card_info['card_number'],
            'ssl_cvv2cvc2' => $card_info['card_security_code'],
            'ssl_exp_date' => $this->formatExpiryDate($card_info['card_exp']),
            'ssl_avs_zip' => $card_info['zip'],
            'ssl_avs_address' => $card_info['address1'],
            'ssl_first_name' => $card_info['first_name'],
            'ssl_last_name' => $card_info['last_name']
        ];
        if ($this->meta['multicurrency']) {
            $actionInfo['ssl_transaction_currency'] = $this->currency;
        }

        if ($invoice_amounts) {
            $actionInfo['ssl_invoice_number'] = (isset($invoice_amounts[0]['invoice_id']) ? $invoice_amounts[0]['invoice_id'] : null);
        }


        $response = $api->ccauthonly($actionInfo);


        if (isset($response['ssl_result']) && $response['ssl_result'] == '0') {
            $status = 'approved';
            $success = true;
        } else {
            $status = 'declined';
            $response['ssl_result'] = isset($response['errorMessage'])?$response['errorMessage']:null;
            $success = false;
        }

        $this->logActions('ccauthonly', $success, isset($response['ssl_result_message']) ?
                $response['ssl_result_message'] : null, $actionInfo);

        return [
            'status' => $status,
            'reference_id' => substr((isset($card_info['card_number']) ? $card_info['card_number'] : null), -4),
            'transaction_id' => (isset($response['ssl_txn_id']) ? $response['ssl_txn_id'] : null),
            'message' => (isset($response['ssl_result_message']) ? $response['ssl_result_message'] : null)
        ];
    }

    /**
     * Capture the funds of a previously authorized credit card
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @param float $amount The amount to capture on this card
     * @param array $invoice_amounts An array of invoices, each containing:
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard message
     * for this transaction status (optional)
     */
    public function captureCc($reference_id, $transaction_id, $amount, array $invoice_amounts = null)
    {
        $api = $this->loadApi();
        $actionInfo = [
            'ssl_txn_id' => $transaction_id,
            'ssl_amount' => $amount,
        ];


        $response = $api->cccomplete($actionInfo);


        if (isset($response['ssl_result']) && $response['ssl_result'] == '0') {
            $status = 'approved';
            $success = true;
        } else {
            $status = 'declined';
            $response['ssl_result'] = isset($response['errorMessage'])?$response['errorMessage']:null;
            $success = false;
        }

        $this->logActions('cccomplete', $success, isset($response['ssl_result_message']) ?
                $response['ssl_result_message'] : null, $actionInfo);

        return [
            'status' => $status,
            'reference_id' => $reference_id,
            'transaction_id' => (isset($response['ssl_txn_id']) ? $response['ssl_txn_id'] : null),
            'message' => (isset($response['ssl_result_message']) ? $response['ssl_result_message'] : null)
        ];
    }

    /**
     * Void a credit card charge
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     * message for this transaction status (optional)
     */
    public function voidCc($reference_id, $transaction_id)
    {
        $api = $this->loadApi();
        $actionInfo = [
            'ssl_txn_id' => $transaction_id,
        ];


        $response = $api->ccvoid($actionInfo);



        if (isset($response['ssl_result']) && $response['ssl_result'] == '0') {
            $status = 'void';
            $success = true;
        } else {
            $status = 'declined';
            $response['ssl_result'] = isset($response['errorMessage'])?$response['errorMessage']:null;
            $success = false;
        }

        $this->logActions('ccvoid', $success, isset($response['ssl_result_message']) ?
                $response['ssl_result_message'] : null, $actionInfo);

        return [
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => (isset($response['ssl_txn_id']) ? $response['ssl_txn_id'] : null),
            'message' => (isset($response['ssl_result_message']) ? $response['ssl_result_message'] : null)
        ];
    }

    /**
     * Refund a credit card charge
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @param float $amount The amount to refund this card
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     * message for this transaction status (optional)
     */
    public function refundCc($reference_id, $transaction_id, $amount)
    {
        $api = $this->loadApi();
        $actionInfo = [
            'ssl_txn_id' => $transaction_id,
            '$amount' => $amount,
        ];

        if ($this->meta['multicurrency']) {
            $actionInfo['ssl_transaction_currency'] = $this->currency;
        }

        $response = $api->ccreturn($actionInfo);



        if (isset($response['ssl_result']) && $response['ssl_result'] == '0') {
            $status = 'refunded';
            $success = true;
        } else {
            $status = 'declined';
            $response['ssl_result'] = isset($response['errorMessage'])?$response['errorMessage']:null;
            $success = false;
        }

        $this->logActions('ccreturn', $success, isset($response['ssl_result_message']) ?
                $response['ssl_result_message'] : null, $actionInfo);

        return [
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => (isset($response['ssl_txn_id']) ? $response['ssl_txn_id'] : null),
            'message' => (isset($response['ssl_result_message']) ? $response['ssl_result_message'] : null)
        ];
    }

    /**
     * Loads the given API
     *
     */
    private function loadApi()
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'converge.php');
        $live_mode = isset($this->meta['live_mode']) && $this->meta['live_mode'] == true ?
            $this->meta['live_mode'] : false;

        return new ConvergeApi($this->meta['merchant_id'], $this->meta['user_id'], $this->meta['pin'], $live_mode);
    }

    /**
     * Format the credit card expiry date
     *
     * @param string $date The credit card expiry date to format
     * @return string a formatted date for use in the Converge API
     */
    private function formatExpiryDate($date)
    {
        $new_date = str_split(substr($date, -4), 2);
        return $new_date[1] . $new_date[0];
    }

    /**
     * Log the actions performed
     *
     * @param string $type The type of transaction
     * @param object $response The response received from the payment gateway of transaction
     * @param bool $success Whether an action was successfully performed or not
     */
    public function logActions($type, $success, $response, $request)
    {
        $mask_fields = [
            'ssl_card_number',
            'ssl_cvv2cvc2',
            'ssl_exp_date',
        ];

        // log the request sent to the gateway
        $this->log($type, serialize($this->maskDataRecursive($request, $mask_fields)), 'input', true);
        // log the response from gateway
        $this->log($type, serialize($response), 'output', $success);
    }
}
