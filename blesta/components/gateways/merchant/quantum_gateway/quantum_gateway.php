<?php
/**
 * QuantumGateway Credit Card processing gateway. Supports onsite
 * payment processing for Credit Cards and ACH.
 *
 * The QuantumGateway API can be found at: http://www.quantumgateway.com/files/QGW-Non-Interactive_API.pdf
 *
 * @package blesta
 * @subpackage blesta.components.gateways.quantum_gateway
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 * @notes See your QuantumGateway control panel for test mode options
 */
class QuantumGateway extends MerchantGateway implements MerchantCc, MerchantAch
{
    /**
     * @var array An array of meta data for this gateway
     */
    private $meta;
    /**
     * @var string The base URL of API requests
     */
    private $base_url = 'https://secure.quantumgateway.com/cgi/tqgwdbe.php';
    /**
     * @var char The response separator
     */
    private $delimiter = '|';

    /**
     * Construct a new merchant gateway
     */
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('quantum_gateway', null, dirname(__FILE__) . DS . 'language' . DS);
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
        $this->view->setDefaultView('components' . DS . 'gateways' . DS . 'merchant' . DS . 'quantum_gateway' . DS);
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
            'gateway_login'=>[
                'empty'=>[
                    'rule'=>'isEmpty',
                    'negate'=>true,
                    'message'=>Language::_('Quantum_gateway.!error.gateway_login.empty', true)
                ]
            ],
            'restrict_key'=>[
                'empty'=>[
                    'rule'=>'isEmpty',
                    'negate'=>true,
                    'message'=>Language::_('Quantum_gateway.!error.restrict_key.empty', true)
                ]
            ],
            'maxmind'=>[
                'valid'=>[
                    'if_set'=>true,
                    'rule'=>['in_array', ['true', 'false']],
                    'message'=>Language::_('Quantum_gateway.!error.maxmind.valid', true)
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
        return ['gateway_login', 'restrict_key'];
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
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function processCc(array $card_info, $amount, array $invoice_amounts = null)
    {
        // Attempt to process this sale transaction
        $transaction = $this->processTransaction($this->getCcParams('SALES', null, $amount, $card_info));

        // Save the last 4 of the CC number (for potential use with refunds)
        $transaction['reference_id'] = substr((isset($card_info['card_number']) ? $card_info['card_number'] : null), -4);

        return $transaction;
    }

    /**
     * Authorize a credit card
     *
     * @param array $card_info An array of credit card info including:
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
     *      - alpha3 The 3-cahracter country code
     *      - name The english name of the country
     *      - alt_name The local name of the country
     *  - zip The zip/postal code of the card holder
     * @param float $amount The amount to charge this card
     * @param array $invoice_amounts An array of invoices, each containing:
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function authorizeCc(array $card_info, $amount, array $invoice_amounts = null)
    {
        // Authorize this transaction
        $transaction = $this->processTransaction($this->getCcParams('AUTH_ONLY', null, $amount, $card_info));

        // Save the last 4 of the CC number (for potential use with refunds)
        $transaction['reference_id'] = substr((isset($card_info['card_number']) ? $card_info['card_number'] : null), -4);

        return $transaction;
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
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function captureCc($reference_id, $transaction_id, $amount, array $invoice_amounts = null)
    {
        // Capture this payment transaction
        $transaction = $this->processTransaction($this->getCcParams('AUTH_CAPTURE', $transaction_id, $amount));

        // Keep the same reference ID as used with the authorize
        $transaction['reference_id'] = $reference_id;

        return $transaction;
    }

    /**
     * Void a credit card charge
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function voidCc($reference_id, $transaction_id)
    {
        // Void this payment transaction
        $result = $this->processTransaction($this->getCcParams('VOID', $transaction_id));

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
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function refundCc($reference_id, $transaction_id, $amount)
    {
        // Set the last4 of the CC (reference_id) required for a refund
        $params = array_merge(['ccnum'=>$reference_id], $this->getCcParams('RETURN', $transaction_id, $amount));

        // Refund this payment transaction
        $result = $this->processTransaction($params);

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
     *  (SALE, AUTH, REFUND, CAPTURE, VOID, UPDATE, CREDIT, AGG)
     * @param int $transaction_id The ID of a previous transaction if available
     * @param float $amount The amount to charge this card
     * @param array $card_info An array of credit card info including:
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
        // Set required transaction fields
        $charge_params = $this->getRequiredParams('CC', $transaction_type);

        switch ($transaction_type) {
            case 'SALES':
            case 'AUTH_ONLY':
                $params = [
                    'ccnum' => (isset($card_info['card_number']) ? $card_info['card_number'] : null),
                    'ccmo' => substr((isset($card_info['card_exp']) ? $card_info['card_exp'] : null), 4, 2),
                    'ccyr' => substr((isset($card_info['card_exp']) ? $card_info['card_exp'] : null), 2, 2),
                    'BADDR1' => (isset($card_info['address1']) ? $card_info['address1'] : null),
                    'BZIP1' => (isset($card_info['zip']) ? $card_info['zip'] : null),
                    'BNAME' => (isset($card_info['first_name']) ? $card_info['first_name'] : null) . ' ' . (isset($card_info['last_name']) ? $card_info['last_name'] : null),
                    'CVVtype' => '0', // Not passing CVV2
                    'amount' => $amount
                ];
                break;
            case 'AUTH_CAPTURE':
            case 'RETURN':
                $params = ['transID' => $transaction_id, 'amount' => $amount];
                break;
            case 'VOID':
                $params = ['transID' => $transaction_id];
                break;
        }

        return array_merge($charge_params, $params);
    }

    /**
     * Sets the parameters for ACH transactions
     *
     * @param string $transaction_type The type of transaction to process
     *  (SALE, AUTH, REFUND, CAPTURE, VOID, UPDATE, CREDIT, AGG)
     * @param int $transaction_id The ID of a previous transaction if available
     * @param float $amount The amount to charge this card
     * @param array $account_info An array of bank account info including:
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
        // Set required transaction fields
        $charge_params = $this->getRequiredParams('EFT', $transaction_type);

        // Set additional transaction-type specific fields
        $params = [];
        switch ($transaction_type) {
            case 'SALES':
                $params = [
                    'aba' => (isset($account_info['routing_number']) ? $account_info['routing_number'] : null),
                    'checkacct' => (isset($account_info['account_number']) ? $account_info['account_number'] : null),
                    'BADDR1' => (isset($account_info['address1']) ? $account_info['address1'] : null),
                    'BZIP1' => (isset($account_info['zip']) ? $account_info['zip'] : null),
                    'BNAME' => (isset($account_info['first_name']) ? $account_info['first_name'] : null)
                        . ' ' . (isset($account_info['last_name']) ? $account_info['last_name'] : null),
                    'amount' => $amount
                ];
                break;
            case 'RETURN':
                $params = ['transID' => $transaction_id, 'amount' => $amount];
                break;
            case 'VOID':
                $params = ['transID' => $transaction_id];
                break;
        }

        return array_merge($charge_params, $params);
    }

    /**
     * Retrieves a list of required fields shared by CC and ACH transactions
     *
     * @param string $payment_type The payment type of this transaction (CC or EFT)
     * @param string $transaction_type The type of transaction to process
     *  (CREDIT, SALES, AUTH_CAPTURE, AUTH_ONLY, RETURN, VOID, PREVIOUS_SALE)
     * @return array A list of key=>value pairs representing the required transaction parameters
     */
    private function getRequiredParams($payment_type, $transaction_type)
    {
        // Set required and default transaction fields
        return [
            'gwlogin' => (isset($this->meta['gateway_login']) ? $this->meta['gateway_login'] : null),
            'RestrictKey' => (isset($this->meta['restrict_key']) ? $this->meta['restrict_key'] : null),
            'trans_type' => $transaction_type,
            'trans_method' => $payment_type,
            'override_email_customer' => 'N', // Don't send customer an email
            'override_trans_email' => 'N', // Don't send customer an email
            'Dsep' => $this->delimiter,
            // 1 to use maxmind, 2 to not
            'MAXMIND' => ((isset($this->meta['maxmind']) ? $this->meta['maxmind'] : 'false') == 'true' ? '1' : '2')
        ];
    }

    /**
     * Process an ACH transaction
     *
     * @param array $account_info An array of bank account info including:
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
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function processAch(array $account_info, $amount, array $invoice_amounts = null)
    {
        // Attempt to process this sale transaction
        return $this->processTransaction($this->getAchParams('SALES', null, $amount, $account_info));
    }

    /**
     * Void an ACH transaction
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function voidAch($reference_id, $transaction_id)
    {
        // Attempt to void this transaction
        $result = $this->processTransaction($this->getAchParams('VOID', $transaction_id));

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
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function refundAch($reference_id, $transaction_id, $amount)
    {
        // Gateway does not support this action
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }

    /**
     * Processes a transaction
     *
     * @param array $fields An array of key=>value pairs to process
     * @return array A list of response key=>value pairs including:
     *  - status (approved, declined, or error)
     *  - reference_id
     *  - transaction_id
     *  - message
     */
    private function processTransaction($fields)
    {

        // Load the HTTP component, if not already loaded
        if (!isset($this->Http)) {
            Loader::loadComponents($this, ['Net']);
            $this->Http = $this->Net->create('Http');
        }

        // Submit the request
        $response = $this->Http->post($this->base_url, http_build_query($fields));

        // Parse the response
        $response = $this->parseResponse($response);

        // Log the transaction (with the parsed response)
        $this->logRequest($fields, $response);

        // Set the status
        $status = 'error';
        if ($response['status'] == 'APPROVED') {
            $status = 'approved';
        }
        if ($response['status'] == 'DECLINED') {
            $status = 'declined';
        }

        // Set an error, if any
        if ($status == 'error') {
            $this->Input->setErrors($this->getCommonError('general'));
        }

        return [
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => (isset($response['transaction_id']) ? $response['transaction_id'] : null),
            'message' => (isset($output['decline_reason']) ? $output['decline_reason'] : null)
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
        // Mask any specific fields
        $mask_fields = [
            'gwlogin',
            'RestrictKey',
            'ccnum', // CC number
            'ccmo', // CC expiration month
            'ccyr', // CC expiration year
            'aba', // routing number
            'checkacct', // checking account number
            'CVV2' // CVV2 (not used)
        ];

        // Determine success/failure (APPROVED, DECLINED)
        $success = false;
        if ((isset($response['status']) ? $response['status'] : null) == 'APPROVED') {
            $success = true;
        }

        // Log data sent to the gateway
        $this->log($this->base_url, serialize($this->maskData($params, $mask_fields)), 'input', true);

        // Log response from the gateway
        $this->log($this->base_url, serialize($this->maskData($response, $mask_fields)), 'output', $success);
    }

    /**
     * Parse the response and return an associative array containing the key=>value pairs
     *
     * @param string $response The response from the gateway
     * @return array An array of key=>value pairs representing the sample response values
     */
    private function parseResponse($response)
    {
        $output = explode($this->delimiter, $response);

        // Remove quotes
        foreach ($output as &$value) {
            $value = str_replace('"', '', trim($value));
        }

        // These fields are expected responses
        $result = [
            'status' => (isset($output[0]) ? $output[0] : null),
            'auth_code' => (isset($output[1]) ? $output[1] : null),
            'transaction_id' => (isset($output[2]) ? $output[2] : null),
            'avr_response' => (isset($output[3]) ? $output[3] : null),
            'cvv_response' => (isset($output[4]) ? $output[4] : null),
            'max_score' => (isset($output[5]) ? $output[5] : null)
        ];

        // Optional responses
        $optional_results = [];
        if ((isset($output[6]) ? $output[6] : false)) {
            $optional_results['decline_reason'] = $output[6];
        }
        if ((isset($result[7]) ? $result[7] : false)) {
            $optional_results['error_code'] = $output[7];
        }

        return array_merge($result, $optional_results);
    }
}
