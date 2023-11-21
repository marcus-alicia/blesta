<?php
/**
 * Cornerstone Credit Card processing gateway. Supports onsite
 * payment processing for Credit Cards and ACH.
 *
 * The Cornerstone API can be found at: https://cps.transactiongateway.com/merchants/resources/integration/integration_portal.php
 *
 * @package blesta
 * @subpackage blesta.components.gateways.cornerstone
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Cornerstone extends MerchantGateway implements MerchantCc, MerchantAch
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
        Language::loadLang('cornerstone', null, dirname(__FILE__) . DS . 'language' . DS);
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
     * Create and return the view content required to modify the settings of this gateway
     *
     * @param array $meta An array of meta (settings) data belonging to this gateway
     * @return string HTML content containing the fields to update the meta data for this gateway
     */
    public function getSettings(array $meta = null)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('settings', 'default');
        $this->view->setDefaultView('components' . DS . 'gateways' . DS . 'merchant' . DS . 'cornerstone' . DS);
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
            'security_key'=>[
                'empty'=>[
                    'rule'=>'isEmpty',
                    'negate'=>true,
                    'message'=>Language::_('Cornerstone.!error.security_key.empty', true)
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
        return ['security_key'];
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
     * Used to determine whether this gateway can be configured for autodebiting accounts
     *
     * @return bool True if the customer must be present
     *  (e.g. in the case of credit card customer must enter security code), false otherwise
     */
    public function requiresCustomerPresent()
    {
        return false;
    }

    /**
     * Charge a credit card
     *
     * @param array $card_info An array of credit card info including:
     *
     *  - first_name The first name on the card
     *  - last_name The last name on the card
     *  - card_number The card number
     *  - card_exp The card expiration date in yyyymm format
     *  - card_security_code The 3 or 4 digit security code of the card (if available)
     *  - type The credit card type
     *  - address1 The address 1 line of the card holder
     *  - address2 The address 2 line of the card holder
     *  - city The city of the card holder
     *  - state An array of state info including:
     *      - code The 2 or 3-character state code
     *      - name The local name of the state
     *  - country An array of country info including:
     *      - alpha2 The 2-character country code
     *      - alpha3 The 3-character country code
     *      - name The english name of the country
     *      - alt_name The local name of the country
     *  - zip The zip/postal code of the card holder
     * @param float $amount The amount to charge this card
     * @param array $invoice_amounts An array of invoices, each containing:
     *
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function processCc(array $card_info, $amount, array $invoice_amounts = null)
    {
        // Attempt to process this sale transaction
        $transaction = $this->processTransaction($this->getCcParams('sale', null, $amount, $card_info));

        // Save the last 4 of the CC number (for potential use with refunds)
        $transaction['reference_id'] = substr((isset($card_info['card_number']) ? $card_info['card_number'] : null), -4);

        return $transaction;
    }

    /**
     * Authorize a credit card
     *
     * @param array $card_info An array of credit card info including:
     *
     *  - first_name The first name on the card
     *  - last_name The last name on the card
     *  - card_number The card number
     *  - card_exp The card expiration date in yyyymm format
     *  - card_security_code The 3 or 4 digit security code of the card (if available)
     *  - type The credit card type
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
     *
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function authorizeCc(array $card_info, $amount, array $invoice_amounts = null)
    {
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }

    /**
     * Capture the funds of a previously authorized credit card
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @param float $amount The amount to capture on this card
     * @param array $invoice_amounts An array of invoices, each containing:
     *
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function captureCc($reference_id, $transaction_id, $amount, array $invoice_amounts = null)
    {
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }

    /**
     * Void a credit card charge
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @return array An array of transaction data including:
     *
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function voidCc($reference_id, $transaction_id)
    {
        // Void this payment transaction
        $result = $this->processTransaction($this->getCcParams('void', $transaction_id));

        // An approved voided transaction should have a status of void
        if ($result['status'] == 'approved') {
            $result['status'] = 'void';
        }

        return $result;
    }

    /**
     * Refund a credit card charge
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @param float $amount The amount to refund this card
     * @return array An array of transaction data including:
     *
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function refundCc($reference_id, $transaction_id, $amount)
    {
        // Refund this payment transaction
        $result = $this->processTransaction($this->getCcParams('refund', $transaction_id, $amount));

        // An approved refunded transaction should have a status of refunded
        if ($result['status'] == 'approved') {
            $result['status'] = 'refunded';
        }

        return $result;
    }

    /**
     * Sets the parameters for credit card transactions
     *
     * @param string $transaction_type The type of transaction to process
     *  (sale, auth, refund, capture, void, update, credit)
     * @param int $transaction_id The ID of a previous transaction if available
     * @param float $amount The amount to charge this card
     * @param array $card_info An array of credit card info including:
     *
     *  - first_name The first name on the card
     *  - last_name The last name on the card
     *  - card_number The card number
     *  - card_exp The card expiration date in yyyymm format
     *  - card_security_code The 3 or 4 digit security code of the card (if available)
     *  - type The credit card type
     *  - address1 The address 1 line of the card holder
     *  - address2 The address 2 line of the card holder
     *  - city The city of the card holder
     *  - state An array of state info including:
     *      - code The 2 or 3-character state code
     *      - name The local name of the state
     *  - country An array of country info including:
     *      - alpha2 The 2-character country code
     *      - alpha3 The 3-character country code
     *      - name The english name of the country
     *      - alt_name The local name of the country
     *  - zip The zip/postal code of the card holder
     * @return array A key=>value list of all transaction fields
     */
    private function getCcParams($transaction_type, $transaction_id = null, $amount = null, array $card_info = null)
    {
        $params = [];

        switch ($transaction_type) {
            case 'sale':
            case 'auth':
                $params = [
                    'ccnumber' => (isset($card_info['card_number']) ? $card_info['card_number'] : null),
                    'ccexp' => substr((isset($card_info['card_exp']) ? $card_info['card_exp'] : null), 4, 2)
                        . substr((isset($card_info['card_exp']) ? $card_info['card_exp'] : null), 2, 2),
                    'amount' => number_format($amount, 2, '.', ''),
                    'cvv' => (isset($card_info['card_security_code']) ? $card_info['card_security_code'] : null),
                    'firstname' => (isset($card_info['first_name']) ? $card_info['first_name'] : null),
                    'lastname' => (isset($card_info['last_name']) ? $card_info['last_name'] : null),
                    'address1' => (isset($card_info['address1']) ? $card_info['address1'] : null),
                    'address2' => (isset($card_info['address2']) ? $card_info['address2'] : null),
                    'city' => (isset($card_info['city']) ? $card_info['city'] : null),
                    'state' => substr((isset($card_info['state']['code']) ? $card_info['state']['code'] : null), 0, 2),
                    'zip' => (isset($card_info['zip']) ? $card_info['zip'] : null),
                    'country' => (isset($card_info['country']['alpha2']) ? $card_info['country']['alpha2'] : null)
                ];
                break;
            case 'refund':
            case 'capture':
                $params = [
                    'transactionid' => (isset($transaction_id) ? $transaction_id : null)
                ];

                if ($amount > 0) {
                    $params['amount'] = number_format($amount, 2, '.', '');
                }
            case 'void':
                $params = [
                    'transactionid' => (isset($transaction_id) ? $transaction_id : null)
                ];
                break;
        }

        return array_merge($params, ['type' => $transaction_type]);
    }

    /**
     * Sets the parameters for ACH transactions
     *
     * @param string $transaction_type The type of transaction to process
     *  (sale, auth, refund, capture, void, update, credit)
     * @param int $transaction_id The ID of a previous transaction if available
     * @param float $amount The amount to charge this card
     * @param array $account_info An array of bank account info including:
     *
     *  - first_name The first name on the account
     *  - last_name The last name on the account
     *  - account_number The bank account number
     *  - routing_number The bank account routing number
     *  - type The bank account type (checking or savings)
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
     *  - zip The zip/postal code of the account holder
     * @return array A key=>value list of all transaction fields
     */
    private function getAchParams($transaction_type, $transaction_id = null, $amount = null, array $account_info = null)
    {
        // Load the helpers required
        Loader::loadHelpers($this, ['Html']);

        $params = [];

        switch ($transaction_type) {
            case 'sale':
            case 'auth':
                $params = [
                    'checkname' => $this->Html->concat(' ', $account_info['first_name'], $account_info['last_name']),
                    'checkaba' => (isset($account_info['routing_number']) ? $account_info['routing_number'] : null),
                    'checkaccount' => (isset($account_info['account_number']) ? $account_info['account_number'] : null),
                    'account_type' => (isset($account_info['type']) ? $account_info['type'] : null),
                    'amount' => number_format($amount, 2, '.', ''),
                    'payment' => 'check',
                    'firstname' => (isset($account_info['first_name']) ? $account_info['first_name'] : null),
                    'lastname' => (isset($account_info['last_name']) ? $account_info['last_name'] : null),
                    'address1' => (isset($account_info['address1']) ? $account_info['address1'] : null),
                    'address2' => (isset($account_info['address2']) ? $account_info['address2'] : null),
                    'city' => (isset($account_info['city']) ? $account_info['city'] : null),
                    'state' => substr((isset($account_info['state']['code']) ? $account_info['state']['code'] : null), 0, 2),
                    'zip' => (isset($account_info['zip']) ? $account_info['zip'] : null),
                    'country' => (isset($account_info['country']['alpha2']) ? $account_info['country']['alpha2'] : null)
                ];
                break;
            case 'refund':
                $params = [
                    'transactionid' => (isset($transaction_id) ? $transaction_id : null),
                    'payment' => 'check'
                ];

                if ($amount > 0) {
                    $params['amount'] = number_format($amount, 2, '.', '');
                }
            case 'void':
                $params = [
                    'transactionid' => (isset($transaction_id) ? $transaction_id : null),
                    'payment' => 'check'
                ];
                break;
        }

        return array_merge($params, ['type' => $transaction_type]);
    }

    /**
     * Process an ACH transaction
     *
     * @param array $account_info An array of bank account info including:
     *
     *  - first_name The first name on the account
     *  - last_name The last name on the account
     *  - account_number The bank account number
     *  - routing_number The bank account routing number
     *  - type The bank account type (checking or savings)
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
     *  - zip The zip/postal code of the account holder
     * @param float $amount The amount to debit this account
     * @param array $invoice_amounts An array of invoices, each containing:
     *
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function processAch(array $account_info, $amount, array $invoice_amounts = null)
    {
        // Attempt to process this sale transaction
        return $this->processTransaction($this->getAchParams('sale', null, $amount, $account_info));
    }

    /**
     * Void an ACH transaction
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @return array An array of transaction data including:
     *
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function voidAch($reference_id, $transaction_id)
    {
        // Attempt to void this transaction
        $result = $this->processTransaction($this->getAchParams('void', $transaction_id));

        // An approved voided transaction should have a status of void
        if ($result['status'] == 'approved') {
            $result['status'] = 'void';
        }

        return $result;
    }

    /**
     * Refund an ACH transaction
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @param float $amount The amount to refund this account
     * @return array An array of transaction data including:
     *
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function refundAch($reference_id, $transaction_id, $amount)
    {
        // Attempt to void this transaction
        $result = $this->processTransaction($this->getAchParams('refund', $transaction_id));

        // An approved voided transaction should have a status of void
        if ($result['status'] == 'approved') {
            $result['status'] = 'refunded';
        }

        return $result;
    }

    /**
     * Processes a transaction
     *
     * @param array $fields An array of key=>value pairs to process
     * @return array A list of response key=>value pairs including:
     *
     *  - status (approved, declined, or error)
     *  - reference_id
     *  - transaction_id
     *  - message
     */
    private function processTransaction($fields)
    {
        // Load library methods
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'cornerstone_api.php');
        $api = new CornerstoneApi($this->meta['security_key']);

        // Submit the request
        $request = $api->submit($fields);

        if (!$request) {
            $this->Input->setErrors($this->getCommonError('general'));

            return;
        }

        // Parse the response
        $response = $request->response();
        $response->status = $request->status();

        // Log the transaction (with the parsed response)
        $this->logRequest($fields, (array) $response);

        // Set the status
        $status = 'error';
        if ($response->status == 'approved') {
            $status = 'approved';
        }
        if ($response->status == 'declined') {
            $status = 'declined';
        }

        // Set an error, if any
        if ($status == 'error') {
            $this->Input->setErrors($this->getCommonError('general'));
        }

        return [
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => (isset($response->transactionid) ? $response->transactionid : null),
            'message' => (isset($response->responsetext) ? $response->responsetext : null)
        ];
    }

    /**
     * Log the request
     *
     * @param array The input parameters sent to the gateway
     * @param array The response from the gateway
     */
    private function logRequest($params, $response)
    {
        // Load library methods
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'cornerstone_api.php');

        // Mask any specific fields
        $mask_fields = [
            'security_key',
            'ccnumber', // CC number
            'ccexp', // CC expiration
            'checkaba', // routing number
            'checkaccount', // checking account number
            'cvv' // CVV code
        ];

        // Determine success/failure
        $success = false;
        if ((isset($response['status']) ? $response['status'] : null) == 'approved') {
            $success = true;
        }

        // Log data sent to the gateway
        $this->log(CornerstoneApi::LIVE_URL, serialize($this->maskData($params, $mask_fields)), 'input', true);

        // Log response from the gateway
        $this->log(CornerstoneApi::LIVE_URL, serialize($this->maskData($response, $mask_fields)), 'output', $success);
    }
}
