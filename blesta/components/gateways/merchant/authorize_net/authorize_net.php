<?php
/**
 * Authorize.net Credit Card and ACH payment processing gateway. Supports both
 * onsite and offsite payment processing for Credit Cards and ACH payments.
 *
 * Documentation on the AIM (Advanced Integration Method) API can be found at:
 * http://www.authorize.net/support/AIM_guide.pdf
 * Documentation on the CIM (Customer Information Manager) API can be found at:
 * http://www.authorize.net/support/CIM_XML_guide.pdf
 * eCheck payment processing utilizes the AIM API, but has supplemental documentation at:
 * http://developer.authorize.net/guides/echeck.pdf
 *
 * A list of all Authorize.net APIs can be found at: http://developer.authorize.net/api/
 *
 * @package blesta
 * @subpackage blesta.components.gateways.authorize_net
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AuthorizeNet extends MerchantGateway implements MerchantAch, MerchantAchOffsite, MerchantCc, MerchantCcOffsite
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
        Language::loadLang('authorize_net', null, dirname(__FILE__) . DS . 'language' . DS);
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
        $this->view->setDefaultView('components' . DS . 'gateways' . DS . 'merchant' . DS . 'authorize_net' . DS);
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('meta', $meta);
        // Set the APIs available through this gateway
        $this->view->set('apis', [
            'aim'=>Language::_('Authorize_net.apis_aim', true),
            'cim'=>Language::_('Authorize_net.apis_cim', true)
        ]);
        // Set the validation modes for CIM
        $this->view->set('validation_modes', [
            'none'=>Language::_('Authorize_net.validation_modes_none', true),
            'testMode'=>Language::_('Authorize_net.validation_modes_test', true),
            'liveMode'=>Language::_('Authorize_net.validation_modes_live', true)
        ]);
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
            'login_id' => [
                'format' => [
                    'rule' => ['betweenLength', 1, 20],
                    'message' => Language::_('Authorize_net.!error.login_id.format', true)
                ]
            ],
            'transaction_key' => [
                'format' => [
                    'rule' => ['betweenLength', 16, 16],
                    'message' => Language::_('Authorize_net.!error.transaction_key.format', true)
                ]
            ],
            'test_mode' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['true', 'false']],
                    'message' => Language::_('Authorize_net.!error.test_mode.valid', true)
                ]
            ],
            'dev_mode' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['true', 'false']],
                    'message' => Language::_('Authorize_net.!error.dev_mode.valid', true)
                ]
            ]
        ];
        $this->Input->setRules($rules);

        if (!isset($meta['test_mode'])) {
            $meta['test_mode'] = 'false';
        }
        if (!isset($meta['dev_mode'])) {
            $meta['dev_mode'] = 'false';
        }

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
        return ['login_id', 'transaction_key'];
    }

    /**
     * Used to determine whether this gateway can be configured for autodebiting accounts
     *
     * @return bool True if the customer must be present (e.g. in the case of credit card
     *  customer must enter security code), false otherwise
     */
    public function requiresCustomerPresent()
    {
        return false;
    }

    /**
     * Informs the system of whether or not this gateway is configured for offsite customer
     * information storage for ACH payments
     *
     * @return bool True if the gateway expects the offset methods to be called for ACH payments,
     *  false to process the normal methods instead
     */
    public function requiresAchStorage()
    {
        return (isset($this->meta['api']) && $this->meta['api'] == 'cim') ? true : false;
    }

    /**
     * Informs the system of whether or not this gateway is configured for offsite customer
     * information storage for credit card payments
     *
     * @return bool True if the gateway expects the offset methods to be called for credit card payments,
     *  false to process the normal methods instead
     */
    public function requiresCcStorage()
    {
        return (isset($this->meta['api']) && $this->meta['api'] == 'cim') ? true : false;
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
     * Store a credit card off site
     *
     * @param array $card_info An array of card info to store off site including:
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
     * @param array $contact An array of contact information for the billing contact this
     *  account is to be set up under including:
     *  - id The ID of the contact
     *  - client_id The ID of the client this contact resides under
     *  - user_id The ID of the user this contact represents
     *  - contact_type The contact type
     *  - contact_type_id The reference ID for this custom contact type
     *  - contact_type_name The name of the contact type
     *  - first_name The first name of the contact
     *  - last_name The last name of the contact
     *  - title The title of the contact
     *  - company The company name of the contact
     *  - email The email address of the contact
     *  - address1 The address of the contact
     *  - address2 The address line 2 of the contact
     *  - city The city of the contact
     *  - state An array of state info including:
     *      - code The 2 or 3-character state code
     *      - name The local name of the country
     *  - country An array of country info including:
     *      - alpha2 The 2-character country code
     *      - alpha3 The 3-character country code
     *      - name The english name of the country
     *      - alt_name The local name of the country
     *  - zip The zip/postal code of the contact
     *  - date_added The date/time the contact was added
     * @param string $client_reference_id The reference ID for the client on the remote gateway (if one exists)
     * @return mixed False on failure or an array containing:
     *  - client_reference_id The reference ID for this client
     *  - reference_id The reference ID for this payment account
     */
    public function storeCc(array $card_info, array $contact, $client_reference_id = null)
    {
        Loader::loadModels($this, ['Accounts']);
        $accounts = $this->Accounts->getAllCc($contact['id']);
        $account_reference_ids = [];
        foreach ($accounts as $account) {
            $account_reference_ids[] = $account->reference_id;
        }

        $this->loadApi('CIM');
        $response = $this->AuthorizeNetCim->store(
            'cc',
            $card_info,
            ['customer_id' => $contact['client_id']],
            $client_reference_id,
            $account_reference_ids
        );
        $this->logCim($response);

        if (!$response) {
            return false;
        }

        return [
            'client_reference_id' => $response['profile_id'],
            'reference_id' => $response['payment_profile_id']
        ];
    }

    /**
     * Update a credit card stored off site
     *
     * @param array $card_info An array of card info to store off site including:
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
     *  - account_changed True if the account details (bank account or card number, etc.)
     *  have been updated, false otherwise
     * @param array $contact An array of contact information for the billing contact this
     *  account is to be set up under including:
     *  - id The ID of the contact
     *  - client_id The ID of the client this contact resides under
     *  - user_id The ID of the user this contact represents
     *  - contact_type The contact type
     *  - contact_type_id The reference ID for this custom contact type
     *  - contact_type_name The name of the contact type
     *  - first_name The first name of the contact
     *  - last_name The last name of the contact
     *  - title The title of the contact
     *  - company The company name of the contact
     *  - email The email address of the contact
     *  - address1 The address of the contact
     *  - address2 The address line 2 of the contact
     *  - city The city of the contact
     *  - state An array of state info including:
     *      - code The 2 or 3-character state code
     *      - name The local name of the country
     *  - country An array of country info including:
     *      - alpha2 The 2-character country code
     *      - alpha3 The 3-character country code
     *      - name The english name of the country
     *      - alt_name The local name of the country
     *  - zip The zip/postal code of the contact
     *  - date_added The date/time the contact was added
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @return mixed False on failure or an array containing:
     *  - client_reference_id The reference ID for this client
     *  - reference_id The reference ID for this payment account
     */
    public function updateCc(array $card_info, array $contact, $client_reference_id, $account_reference_id)
    {
        // The card number must be provided
        if (empty($card_info['card_number'])) {
            $this->Input->setErrors([
                'card_number' => [
                    'missing' => Language::_('Authorize_net.!error.card_number.missing', true)
                ]
            ]);
            return false;
        }

        $this->loadApi('CIM');
        $response = $this->AuthorizeNetCim->update(
            'cc',
            $card_info,
            ['customer_id' => $contact['client_id']],
            $client_reference_id,
            $account_reference_id
        );
        $this->logCim($response);

        if (!$response) {
            return false;
        }

        return [
            'client_reference_id' => (isset($response['profile_id']) ? $response['profile_id'] : null),
            'reference_id' => (isset($response['payment_profile_id']) ? $response['payment_profile_id'] : null)
        ];
    }

    /**
     * Remove a credit card stored off site
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to remove
     * @return array An array containing:
     *  - client_reference_id The reference ID for this client
     *  - reference_id The reference ID for this payment account
     */
    public function removeCc($client_reference_id, $account_reference_id)
    {
        $this->loadApi('CIM');
        $response = $this->AuthorizeNetCim->delete($client_reference_id, $account_reference_id);
        $this->logCim($response);

        return [
            'client_reference_id' => (isset($response['profile_id']) ? $response['profile_id'] : null),
            'reference_id' => (isset($response['payment_profile_id']) ? $response['payment_profile_id'] : null)
        ];
    }

    /**
     * Charge a credit card stored off site
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @param float $amount The amount to process
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
    public function processStoredCc($client_reference_id, $account_reference_id, $amount, array $invoice_amounts = null)
    {
        $this->loadApi('CIM');
        $response = $this->AuthorizeNetCim->authCapture(
            $client_reference_id,
            $account_reference_id,
            $amount,
            $invoice_amounts
        );
        $this->logCim($response);

        $this->setTransactionErrors($response);

        // Set response status
        $status = 'error';
        if (isset($response['x_response_code'])) {
            switch ($response['x_response_code']) {
                case '1':
                    $status = 'approved';
                    break;
                case '2':
                    $status = 'declined';
                    break;
                case '3':
                    $status = 'error';
                    break;
                case '4':
                    $status = 'pending';
                    break;
            }
        }

        return [
            'status' => $status,
            'reference_id' => substr((isset($response['x_last4']) ? $response['x_last4'] : null), -4),
            'transaction_id' => (isset($response['x_trans_id']) ? $response['x_trans_id'] : null),
            'message' => (isset($response['x_response_reason_text']) ? $response['x_response_reason_text'] : null)
        ];
    }

    /**
     * Authorizees a credit card stored off site
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @param float $amount The amount to authorize
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
    public function authorizeStoredCc(
        $client_reference_id,
        $account_reference_id,
        $amount,
        array $invoice_amounts = null
    ) {
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }

    /**
     * Charge a previously authorized credit card stored off site
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @param string $transaction_reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The ID of the previously authorized transaction
     * @param float $amount The amount to capture
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
    public function captureStoredCc(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id,
        $amount,
        array $invoice_amounts = null
    ) {
        $this->loadApi('CIM');
        $response = $this->AuthorizeNetCim->capture(
            $client_reference_id,
            $account_reference_id,
            $transaction_id,
            $amount,
            $invoice_amounts
        );
        $this->logCim($response);

        $this->setTransactionErrors($response);

        // Set response status
        $status = 'error';
        if (isset($response['x_response_code'])) {
            switch ($response['x_response_code']) {
                case '1':
                    $status = 'approved';
                    break;
                case '2':
                    $status = 'declined';
                    break;
                case '3':
                    $status = 'error';
                    break;
                case '4':
                    $status = 'pending';
                    break;
            }
        }

        return [
            'status' => $status,
            'reference_id' => substr((isset($response['x_last4']) ? $response['x_last4'] : null), -4),
            'transaction_id' => (isset($response['x_trans_id']) ? $response['x_trans_id'] : null),
            'message' => (isset($response['x_response_reason_text']) ? $response['x_response_reason_text'] : null)
        ];
    }

    /**
     * Void an off site credit card charge
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @param string $transaction_reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The ID of the previously authorized transaction
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard message for
     *      this transaction status (optional)
     */
    public function voidStoredCc(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id
    ) {
        $this->loadApi('CIM');
        $response = $this->AuthorizeNetCim->void($client_reference_id, $account_reference_id, $transaction_id);
        $this->logCim($response);

        $this->setTransactionErrors($response);

        // Set response status
        $status = 'error';
        if (isset($response['x_response_code'])) {
            switch ($response['x_response_code']) {
                case '1':
                    $status = 'void';
                    break;
                case '2':
                    $status = 'declined';
                    break;
                case '3':
                    $status = 'error';
                    break;
                case '4':
                    $status = 'pending';
                    break;
            }
        }

        return [
            'status' => $status,
            'reference_id' => substr((isset($response['x_last4']) ? $response['x_last4'] : null), -4),
            'transaction_id' => (isset($response['x_trans_id']) ? $response['x_trans_id'] : null),
            'message' => (isset($response['x_response_reason_text']) ? $response['x_response_reason_text'] : null)
        ];
    }

    /**
     * Refund an off site credit card charge
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @param string $transaction_reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The ID of the previously authorized transaction
     * @param float $amount The amount to refund
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard message
     *      for this transaction status (optional)
     */
    public function refundStoredCc(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id,
        $amount
    ) {
        $this->loadApi('CIM');
        $response = $this->AuthorizeNetCim->refund(
            $client_reference_id,
            $account_reference_id,
            $transaction_id,
            $amount
        );
        $this->logCim($response);

        $this->setTransactionErrors($response);

        // Set response status
        $status = 'error';
        if (isset($response['x_response_code'])) {
            switch ($response['x_response_code']) {
                case '1':
                    $status = 'refunded';
                    break;
                case '2':
                    $status = 'declined';
                    break;
                case '3':
                    $status = 'error';
                    break;
                case '4':
                    $status = 'pending';
                    break;
            }
        }

        return [
            'status' => $status,
            'reference_id' => substr((isset($response['x_last4']) ? $response['x_last4'] : null), -4),
            'transaction_id' => (isset($response['x_trans_id']) ? $response['x_trans_id'] : null),
            'message' => (isset($response['x_response_reason_text']) ? $response['x_response_reason_text'] : null)
        ];
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
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function processCc(array $card_info, $amount, array $invoice_amounts = null)
    {
        $this->loadApi('AIM');
        $response = $this->AuthorizeNetAim->authCaptureCc($card_info, $amount, $invoice_amounts);
        $this->logAim($response);

        $this->setTransactionErrors($response);

        // Set response status
        $status = 'error';
        if (isset($response['x_response_code'])) {
            switch ($response['x_response_code']) {
                case '1':
                    $status = 'approved';
                    break;
                case '2':
                    $status = 'declined';
                    break;
                case '3':
                    $status = 'error';
                    break;
                case '4':
                    $status = 'pending';
                    break;
            }
        }

        return [
            'status' => $status,
            'reference_id' => substr((isset($card_info['card_number']) ? $card_info['card_number'] : null), -4),
            'transaction_id' => (isset($response['x_trans_id']) ? $response['x_trans_id'] : null),
            'message' => (isset($response['x_response_reason_text']) ? $response['x_response_reason_text'] : null)
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
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
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
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function voidCc($reference_id, $transaction_id)
    {
        $this->loadApi('AIM');
        $response = $this->AuthorizeNetAim->voidCc($transaction_id);
        $this->logAim($response);

        $this->setTransactionErrors($response);

        // Set response status
        $status = 'error';
        if (isset($response['x_response_code'])) {
            switch ($response['x_response_code']) {
                case '1':
                    $status = 'void';
                    break;
                case '2':
                    $status = 'declined';
                    break;
                case '3':
                    $status = 'error';
                    break;
                case '4':
                    $status = 'pending';
                    break;
            }
        }

        return [
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => (isset($response['x_trans_id']) ? $response['x_trans_id'] : null),
            'message' => (isset($response['x_response_reason_text']) ? $response['x_response_reason_text'] : null)
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
     *      message for this transaction status (optional)
     */
    public function refundCc($reference_id, $transaction_id, $amount)
    {
        $this->loadApi('AIM');
        $response = $this->AuthorizeNetAim->refundCc($transaction_id, $reference_id, $amount);
        $this->logAim($response);

        $this->setTransactionErrors($response);

        // Set response status
        $status = 'error';
        if (isset($response['x_response_code'])) {
            switch ($response['x_response_code']) {
                case '1':
                    $status = 'refunded';
                    break;
                case '2':
                    $status = 'declined';
                    break;
                case '3':
                    $status = 'error';
                    break;
                case '4':
                    $status = 'pending';
                    break;
            }
        }

        return [
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => (isset($response['x_trans_id']) ? $response['x_trans_id'] : null),
            'message' => (isset($response['x_response_reason_text']) ? $response['x_response_reason_text'] : null)
        ];
    }




    /**
     * Store an ACH account off site
     *
     * @param array $account_info An array of bank account info including:
     *  - first_name The first name on the account
     *  - last_name The last name on the account
     *  - account_number The bank account number
     *  - routing_number The bank account routing number
     *  - type The bank account type (checking, savings, business_checking)
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
     * @param array $contact An array of contact information for the billing contact this
     *  account is to be set up under including:
     *  - id The ID of the contact
     *  - client_id The ID of the client this contact resides under
     *  - user_id The ID of the user this contact represents
     *  - contact_type The contact type
     *  - contact_type_id The reference ID for this custom contact type
     *  - contact_type_name The name of the contact type
     *  - first_name The first name of the contact
     *  - last_name The last name of the contact
     *  - title The title of the contact
     *  - company The company name of the contact
     *  - email The email address of the contact
     *  - address1 The address of the contact
     *  - address2 The address line 2 of the contact
     *  - city The city of the contact
     *  - state An array of state info including:
     *      - code The 2 or 3-character state code
     *      - name The local name of the country
     *  - country An array of country info including:
     *      - alpha2 The 2-character country code
     *      - alpha3 The 3-character country code
     *      - name The english name of the country
     *      - alt_name The local name of the country
     *  - zip The zip/postal code of the contact
     *  - date_added The date/time the contact was added
     * @param string $client_reference_id The reference ID for the client on the remote gateway (if one exists)
     * @return mixed False on failure or an array containing:
     *  - client_reference_id The reference ID for this client
     *  - reference_id The reference ID for this payment account
     */
    public function storeAch(array $account_info, array $contact, $client_reference_id = null)
    {
        Loader::loadModels($this, ['Accounts']);
        $accounts = $this->Accounts->getAllAch($contact['id']);
        $account_reference_ids = [];
        foreach ($accounts as $account) {
            $account_reference_ids[] = $account->reference_id;
        }

        $this->loadApi('CIM');
        $response = $this->AuthorizeNetCim->store(
            'ach',
            $account_info,
            ['customer_id' => $contact['client_id']],
            $client_reference_id,
            $account_reference_ids
        );
        $this->logCim($response);

        if (!$response) {
            return false;
        }

        return [
            'client_reference_id' => (isset($response['profile_id']) ? $response['profile_id'] : null),
            'reference_id' => (isset($response['payment_profile_id']) ? $response['payment_profile_id'] : null)
        ];
    }

    /**
     * Update an off site ACH account
     *
     * @param array $account_info An array of bank account info including:
     *  - first_name The first name on the account
     *  - last_name The last name on the account
     *  - account_number The bank account number
     *  - routing_number The bank account routing number
     *  - type The bank account type (checking, savings, business_checking)
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
     *  - account_changed True if the account details (bank account or card number, etc.)
     *      have been updated, false otherwise
     * @param array $contact An array of contact information for the billing contact
     *  this account is to be set up under including:
     *  - id The ID of the contact
     *  - client_id The ID of the client this contact resides under
     *  - user_id The ID of the user this contact represents
     *  - contact_type The contact type
     *  - contact_type_id The reference ID for this custom contact type
     *  - contact_type_name The name of the contact type
     *  - first_name The first name of the contact
     *  - last_name The last name of the contact
     *  - title The title of the contact
     *  - company The company name of the contact
     *  - email The email address of the contact
     *  - address1 The address of the contact
     *  - address2 The address line 2 of the contact
     *  - city The city of the contact
     *  - state An array of state info including:
     *      - code The 2 or 3-character state code
     *      - name The local name of the country
     *  - country An array of country info including:
     *      - alpha2 The 2-character country code
     *      - alpha3 The 3-character country code
     *      - name The english name of the country
     *      - alt_name The local name of the country
     *  - zip The zip/postal code of the contact
     *  - date_added The date/time the contact was added
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @return mixed False on failure or an array containing:
     *  - client_reference_id The reference ID for this client
     *  - reference_id The reference ID for this payment account
     */
    public function updateAch(array $account_info, array $contact, $client_reference_id, $account_reference_id)
    {
        $this->loadApi('CIM');
        $response = $this->AuthorizeNetCim->update(
            'ach',
            $account_info,
            ['customer_id' => $contact['client_id']],
            $client_reference_id,
            $account_reference_id
        );
        $this->logCim($response);

        if (!$response) {
            return false;
        }

        return [
            'client_reference_id' => (isset($response['profile_id']) ? $response['profile_id'] : null),
            'reference_id' => (isset($response['payment_profile_id']) ? $response['payment_profile_id'] : null)
        ];
    }

    /**
     * Remove an off site ACH account
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to remove
     * @return array An array containing:
     *  - client_reference_id The reference ID for this client
     *  - reference_id The reference ID for this payment account
     */
    public function removeAch($client_reference_id, $account_reference_id)
    {
        $this->loadApi('CIM');
        $response = $this->AuthorizeNetCim->delete($client_reference_id, $account_reference_id);
        $this->logCim($response);

        return [
            'client_reference_id' => (isset($response['profile_id']) ? $response['profile_id'] : null),
            'reference_id' => (isset($response['payment_profile_id']) ? $response['payment_profile_id'] : null)
        ];
    }

    /**
     * Process an off site ACH account transaction
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @param float $amount The amount to process
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
    public function processStoredAch(
        $client_reference_id,
        $account_reference_id,
        $amount,
        array $invoice_amounts = null
    ) {
        $this->loadApi('CIM');
        $response = $this->AuthorizeNetCim->authCapture(
            $client_reference_id,
            $account_reference_id,
            $amount,
            $invoice_amounts
        );
        $this->logCim($response);

        $this->setTransactionErrors($response);

        // Set response status
        $status = 'error';
        if (isset($response['x_response_code'])) {
            switch ($response['x_response_code']) {
                case '1':
                    $status = 'approved';
                    break;
                case '2':
                    $status = 'declined';
                    break;
                case '3':
                    $status = 'error';
                    break;
                case '4':
                    $status = 'pending';
                    break;
            }
        }

        return [
            'status' => $status,
            'reference_id' => substr((isset($response['x_last4']) ? $response['x_last4'] : null), -4),
            'transaction_id' => (isset($response['x_trans_id']) ? $response['x_trans_id'] : null),
            'message' => (isset($response['x_response_reason_text']) ? $response['x_response_reason_text'] : null)
        ];
    }

    /**
     * Void an off site ACH account transaction
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @param string $transaction_reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The ID of the previously authorized transaction
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function voidStoredAch(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id
    ) {
        $this->loadApi('CIM');
        $response = $this->AuthorizeNetCim->void($client_reference_id, $account_reference_id, $transaction_id);
        $this->logCim($response);

        $this->setTransactionErrors($response);

        // Set response status
        $status = 'error';
        if (isset($response['x_response_code'])) {
            switch ($response['x_response_code']) {
                case '1':
                    $status = 'void';
                    break;
                case '2':
                    $status = 'declined';
                    break;
                case '3':
                    $status = 'error';
                    break;
                case '4':
                    $status = 'pending';
                    break;
            }
        }

        return [
            'status' => $status,
            'reference_id' => substr((isset($response['x_last4']) ? $response['x_last4'] : null), -4),
            'transaction_id' => (isset($response['x_trans_id']) ? $response['x_trans_id'] : null),
            'message' => (isset($response['x_response_reason_text']) ? $response['x_response_reason_text'] : null)
        ];
    }

    /**
     * Refund an off site ACH account transaction
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @param string $transaction_reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The ID of the previously authorized transaction
     * @param float $amount The amount to refund
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function refundStoredAch(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id,
        $amount
    ) {
        $this->loadApi('CIM');
        $response = $this->AuthorizeNetCim->refund(
            $client_reference_id,
            $account_reference_id,
            $transaction_id,
            $amount
        );
        $this->logCim($response);

        $this->setTransactionErrors($response);

        // Set response status
        $status = 'error';
        if (isset($response['x_response_code'])) {
            switch ($response['x_response_code']) {
                case '1':
                    $status = 'refunded';
                    break;
                case '2':
                    $status = 'declined';
                    break;
                case '3':
                    $status = 'error';
                    break;
                case '4':
                    $status = 'pending';
                    break;
            }
        }

        return [
            'status' => $status,
            'reference_id' => substr((isset($response['x_last4']) ? $response['x_last4'] : null), -4),
            'transaction_id' => (isset($response['x_trans_id']) ? $response['x_trans_id'] : null),
            'message' => (isset($response['x_response_reason_text']) ? $response['x_response_reason_text'] : null)
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
     *  - type The bank account type (checking, savings, business_checking)
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
        $this->loadApi('AIM');
        $response = $this->AuthorizeNetAim->authCaptureAch($account_info, $amount, 'WEB', $invoice_amounts);
        $this->logAim($response);

        $this->setTransactionErrors($response);

        // Set response status
        $status = 'error';
        if (isset($response['x_response_code'])) {
            switch ($response['x_response_code']) {
                case '1':
                    $status = 'approved';
                    break;
                case '2':
                    $status = 'declined';
                    break;
                case '3':
                    $status = 'error';
                    break;
                case '4':
                    $status = 'pending';
                    break;
            }
        }

        return [
            'status' => $status,
            'reference_id' => substr((isset($account_info['account_number']) ? $account_info['account_number'] : null), -4)
                . ':' . substr((isset($account_info['routing_number']) ? $account_info['routing_number'] : null), -4),
            'transaction_id' => (isset($response['x_trans_id']) ? $response['x_trans_id'] : null),
            'message' => (isset($response['x_response_reason_text']) ? $response['x_response_reason_text'] : null)
        ];
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
        $this->loadApi('AIM');
        $response = $this->AuthorizeNetAim->voidAch($transaction_id);
        $this->logAim($response);

        $this->setTransactionErrors($response);

        // Set response status
        $status = 'error';
        if (isset($response['x_response_code'])) {
            switch ($response['x_response_code']) {
                case '1':
                    $status = 'void';
                    break;
                case '2':
                    $status = 'declined';
                    break;
                case '3':
                    $status = 'error';
                    break;
                case '4':
                    $status = 'pending';
                    break;
            }
        }

        return [
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => (isset($response['x_trans_id']) ? $response['x_trans_id'] : null),
            'message' => (isset($response['x_response_reason_text']) ? $response['x_response_reason_text'] : null)
        ];
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
        $this->loadApi('AIM');

        $temp = explode(':', $reference_id);
        $account_last4 = (isset($temp[0]) ? $temp[0] : null);
        $routing_last4 = (isset($temp[1]) ? $temp[1] : null);
        unset($temp);

        $response = $this->AuthorizeNetAim->refundAch($transaction_id, $account_last4, $routing_last4, $amount);
        $this->logAim($response);

        // Set response status
        $status = 'error';
        if (isset($response['x_response_code'])) {
            switch ($response['x_response_code']) {
                case '1':
                    $status = 'refunded';
                    break;
                case '2':
                    $status = 'declined';
                    break;
                case '3':
                    $status = 'error';
                    break;
                case '4':
                    $status = 'pending';
                    break;
            }
        }

        return [
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => (isset($response['x_trans_id']) ? $response['x_trans_id'] : null),
            'message' => (isset($response['x_response_reason_text']) ? $response['x_response_reason_text'] : null)
        ];
    }

    /**
     * Sets error details when a transaction fails
     */
    private function setTransactionErrors($response)
    {
        if (isset($response['x_response_code'])
            && ($response['x_response_code'] != '2' || $response['x_response_code'] == '3')
        ) {
            switch ($response['x_response_reason_code']) {
                // card number is invalid
                case '6':
                    $this->Input->setErrors($this->getCommonError('card_number_invalid'));
                    break;
                // card has expired
                case '8':
                    $this->Input->setErrors($this->getCommonError('card_expired'));
                    break;
                // routing number is invalid
                case '9':
                    $this->Input->setErrors($this->getCommonError('routing_number_invalid'));
                    break;
                // account number is invalid
                case '10':
                    $this->Input->setErrors($this->getCommonError('account_number_invalid'));
                    break;
                // duplicate transaction has been submitted
                case '11':
                    $this->Input->setErrors($this->getCommonError('duplicate_transaction'));
                    break;
                // transaction not found
                case '16':
                    $this->Input->setErrors($this->getCommonError('transaction_not_found'));
                    break;
                // card type not accepted
                case '17':
                    $this->Input->setErrors($this->getCommonError('card_not_accepted'));
                    break;
                // invalid card security code
                case '44':
                    $this->Input->setErrors($this->getCommonError('invalid_security_code'));
                    break;
                // AVS mismatch
                case '45':
                    $this->Input->setErrors($this->getCommonError('address_verification_failed'));
                    break;
                default:
                    if ($response['x_response_code'] == '3') {
                        $this->Input->setErrors($this->getCommonError('general'));
                    }
                    break;
            }
        }
    }

    /**
     * Loads the given API if not already loaded
     *
     * @param string $type The type of API to load (AIM or CIM)
     */
    private function loadApi($type)
    {
        $type = strtolower($type);
        switch ($type) {
            case 'aim':
                if (!isset($this->AuthorizeNetAim)) {
                    Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'authorize_net_aim.php');
                    $this->AuthorizeNetAim = new AuthorizeNetAim(
                        $this->meta['login_id'],
                        $this->meta['transaction_key'],
                        $this->meta['test_mode'] == 'true',
                        $this->meta['dev_mode'] == 'true'
                    );
                }

                $this->AuthorizeNetAim->setCurrency($this->currency);
                break;
            case 'cim':
                if (!isset($this->AuthorizeNetCim)) {
                    Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'authorize_net_cim.php');
                    $this->AuthorizeNetCim = new AuthorizeNetCim(
                        $this->meta['login_id'],
                        $this->meta['transaction_key'],
                        $this->meta['dev_mode'] == 'true',
                        $this->meta['validation_mode']
                    );
                }

                $this->AuthorizeNetCim->setCurrency($this->currency);
                break;
        }
    }

    /**
     * Log the request performed using the AIM API
     *
     * @param mixed The parsed response from the gateway
     */
    private function logAim($response)
    {

        // Define all fields to mask when logging
        $mask_fields = [
            'x_login' => ['length' => -4],
            'x_tran_key',
            'x_card_num',
            'x_exp_date',
            'x_card_code',
            'x_bank_aba_code',
            'x_bank_acct_num',
        ];

        // Log data sent to the gateway
        $this->log(
            $this->AuthorizeNetAim->getUrl(),
            serialize($this->maskData($this->AuthorizeNetAim->getParams(), $mask_fields)),
            'input',
            true
        );

        // Log response from the gateway
        // (which is successful if, and only if, we received a response from the gateway)
        $this->log(
            $this->AuthorizeNetAim->getUrl(),
            $this->AuthorizeNetAim->getRawResponse(),
            'output',
            isset($response['x_response_code'])
        );
    }

    /**
     * Log the request performed using the CIM API
     *
     * @param bool Whether or not the response was successful
     */
    private function logCim($success)
    {

        // Define all fields to mask when logging
        $mask_fields = [
            'x_login' => ['length' => -4],
            'x_tran_key',
            'x_card_num',
            'x_exp_date',
            'x_card_code',
            'x_bank_aba_code',
            'x_bank_acct_num',
            'name' => ['length' => -4],
            'transactionKey',
            'cardNumber',
            'expirationDate',
            'cardCode',
            'routingNumber',
            'accountNumber'
        ];

        // Log data sent to and from the gateway
        $urls = (array)$this->AuthorizeNetCim->getUrl();
        $params = (array)$this->AuthorizeNetCim->getParams();
        $responses = (array)$this->AuthorizeNetCim->getRawResponse();
        foreach ($params as $i => $param) {
            $params[$i] = $this->maskDataRecursive($params[$i], $mask_fields);

            $this->log($urls[$i], serialize($params[$i]), 'input', true);

            // Log response from the gateway
            // (which is successful if, and only if, we received a response from the gateway)
            $this->log($urls[$i], $responses[$i], 'output', $success);
        }
    }
}
