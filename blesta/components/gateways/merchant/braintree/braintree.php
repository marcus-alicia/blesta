<?php

/**
 * BrainTree Credit Card processing gateway.
 *
 * @package blesta
 * @subpackage blesta.components.gateways.braintree
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Braintree extends MerchantGateway implements MerchantCc, MerchantCcOffsite
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
        Language::loadLang('braintree', null, dirname(__FILE__) . DS . 'language' . DS);
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
        $this->view->setDefaultView('components' . DS . 'gateways' . DS . 'merchant' . DS . 'braintree' . DS);
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $environment = [
            'sandbox' => Language::_('Braintree.environment.sandbox', true),
            'production' => Language::_('Braintree.environment.production', true)
        ];
        $this->view->set('environment', $environment);
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
                    'message' => Language::_('Braintree.!error.merchant_id', true)
                ]
            ],
            'public_key' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Braintree.!error.public_key', true)
                ]
            ],
            'private_key' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Braintree.!error.private_key', true)
                ]
            ],
            'live_mode' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['true', 'false']],
                    'message' => Language::_('Braintree.!error.live_mode.valid', true)
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
        return ['merchant_id', 'private_key'];
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
     * Informs the system of whether or not this gateway is configured for offsite
     * customer information storage for credit card payments
     *
     * @return bool True if the gateway expects the offset methods to be called for credit card payments,
     * false to process the normal methods instead
     */
    public function requiresCcStorage()
    {
        return true;
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
     *  -first_name The first name on the card
     *  -last_name The last name on the card
     *  -card_number The card number
     *  -card_exp The card expiration date
     *  -card_security_code The 3 or 4 digit security code of the card (if available)
     *  -address1 The address 1 line of the card holder
     *  -address2 The address 2 line of the card holder
     *  -city The city of the card holder
     *  -state An array of state info including:
     *      -code The 2 or 3-character state code
     *      -name The local name of the country
     *  -country An array of country info including:
     *      -alpha2 The 2-character country code
     *      -alpha3 The 3-character country code
     *      -name The english name of the country
     *      -alt_name The local name of the country
     *  -zip The zip/postal code of the card holder
     * @param float $amount The amount to charge this card
     * @param array $invoice_amounts An array of invoices, each containing:
     *  -id The ID of the invoice being processed
     *  -amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *  -status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  -reference_id The reference ID for gateway-only use with this transaction (optional)
     *  -transaction_id The ID returned by the remote gateway to identify this transaction
     *  -message The message to be displayed in the interface in addition to the standard
     *  message for this transaction status (optional)
     */
    public function processCc(array $card_info, $amount, array $invoice_amounts = null)
    {
        $this->loadApi();
        $transaction_fields = [
            'amount' => $amount,
            'creditCard' => [
                'cardholderName' => $card_info['first_name'] . ' ' . $card_info['last_name'],
                'number' => $card_info['card_number'],
                'cvv' => $card_info['card_security_code'],
                'expirationDate' => $this->formatExpiryDate($card_info['card_exp']),
            ],
            'customer' => [
                'firstName' => $card_info['first_name'],
                'lastName' => $card_info['last_name'],
            ],
            'billing' => [
                'firstName' => $card_info['first_name'],
                'lastName' => $card_info['last_name'],
                'streetAddress' => $card_info['address1'],
                'postalCode' => $card_info['zip'],
                'locality' => $card_info['city'],
                'region' => $card_info['state']['code'],
                'countryCodeAlpha3' => $card_info['country']['alpha3'],
            ],
            'options' => [
                'submitForSettlement' => true,
                'threeDSecure' => [
                    'required' => false
                ]
            ],
        ];
        $result = Braintree_Transaction::sale($transaction_fields);

        if ($result->success) {
            $success = true;
            $status = 'approved';
            $response_message = isset($result->transaction->processorResponseText) ?
                $result->transaction->processorResponseText : null;
        } elseif ($result->transaction) {
            $status = 'declined';
            $success = false;
            $response_message = isset($result->transaction->processorResponseText) ?
                $result->transaction->processorResponseText : null;
        } else {
            $status = 'declined';
            $success = false;
            $response_message = isset($result->message) ? $result->message : null;
        }
        $this->logActions('processCc', $success, $result, $transaction_fields);

        return [
            'status' => $status,
            'reference_id' => substr((isset($card_info['card_number']) ? $card_info['card_number'] : null), -4),
            'transaction_id' => isset($result->transaction->id) ? $result->transaction->id : null,
            'message' => $response_message
        ];
    }

    /**
     * Authorize a credit card
     *
     * @param array $card_info An array of credit card info including:
     *  -first_name The first name on the card
     *  -last_name The last name on the card
     *  -card_number The card number
     *  -card_exp The card expidation date
     *  -card_security_code The 3 or 4 digit security code of the card (if available)
     *  -address1 The address 1 line of the card holder
     *  -address2 The address 2 line of the card holder
     *  -city The city of the card holder
     *  -state An array of state info including:
     *      -code The 2 or 3-character state code
     *      -name The local name of the country
     *  -country An array of country info including:
     *      -alpha2 The 2-character country code
     *      -alpha3 The 3-cahracter country code
     *      -name The english name of the country
     *      -alt_name The local name of the country
     *  -zip The zip/postal code of the card holder
     * @param float $amount The amount to charge this card
     * @param array $invoice_amounts An array of invoices, each containing:
     *  -id The ID of the invoice being processed
     *  -amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *  -status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  -reference_id The reference ID for gateway-only use with this transaction (optional)
     *  -transaction_id The ID returned by the remote gateway to identify this transaction
     *  -message The message to be displayed in the interface in addition to the standard
     * message for this transaction status (optional)
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
     *  -id The ID of the invoice being processed
     *  -amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *  -status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  -reference_id The reference ID for gateway-only use with this transaction (optional)
     *  -transaction_id The ID returned by the remote gateway to identify this transaction
     *  -message The message to be displayed in the interface in addition to the standard
     *  message for this transaction status (optional)
     */
    public function captureCc($reference_id, $transaction_id, $amount, array $invoice_amounts = null)
    {
        $this->loadApi();

        $result = Braintree_Transaction::submitForSettlement($transaction_id, $amount);

        if ($result->success) {
            $success = true;
            $status = 'approved';
            $response_message = isset($result->transaction->processorResponseText) ?
                $result->transaction->processorResponseText : null;
        } elseif ($result->transaction) {
            $status = 'declined';
            $success = false;
            $response_message = isset($result->transaction->processorResponseText) ?
                $result->transaction->processorResponseText : null;
        } else {
            $status = 'declined';
            $success = false;
            $response_message = isset($result->message) ? $result->message : null;
        }

        $this->logActions('captureCc', $success, $result, [
            'transaction_id' => $transaction_id,
            'amount' => $amount
        ]);

        return [
            'status' => $status,
            'reference_id' => $reference_id,
            'transaction_id' => isset($result->transaction->id) ? $result->transaction->id : null,
            'message' => $response_message
        ];
    }

    /**
     * Void a credit card charge
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @return array An array of transaction data including:
     *  -status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  -reference_id The reference ID for gateway-only use with this transaction (optional)
     *  -transaction_id The ID returned by the remote gateway to identify this transaction
     *  -message The message to be displayed in the interface in addition to the standard
     * message for this transaction status (optional)
     */
    public function voidCc($reference_id, $transaction_id)
    {
        $this->loadApi();

        $result = Braintree_Transaction::void($transaction_id);

        if ($result->success) {
            $success = true;
            $status = 'void';
            $response_message = isset($result->transaction->processorResponseText) ?
                $result->transaction->processorResponseText : null;
        } elseif ($result->transaction) {
            $status = 'declined';
            $success = false;
            $response_message = isset($result->transaction->processorResponseText) ?
                $result->transaction->processorResponseText : null;
        } else {
            $status = 'declined';
            $success = false;
            $response_message = isset($result->message) ? $result->message : null;
        }

        $this->logActions('voidCc', $success, $result, ['transaction_id' => $transaction_id]);

        return [
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => isset($result->transaction->id) ? $result->transaction->id : null,
            'message' => $response_message
        ];
    }

    /**
     * Refund a credit card charge
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @param float $amount The amount to refund this card
     * @return array An array of transaction data including:
     *  -status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  -reference_id The reference ID for gateway-only use with this transaction (optional)
     *  -transaction_id The ID returned by the remote gateway to identify this transaction
     *  -message The message to be displayed in the interface in addition to the standard
     * message for this transaction status (optional)
     */
    public function refundCc($reference_id, $transaction_id, $amount)
    {
        $this->loadApi();
        Loader::loadComponents($this, ['Input']);

        $result = Braintree_Transaction::refund($transaction_id, $amount);

        if ($result->success) {
            $success = true;
            $status = 'refunded';
            $response_message = isset($result->transaction->processorResponseText) ?
                $result->transaction->processorResponseText : null;
        } elseif ($result->transaction) {
            $status = 'declined';
            $success = false;
            $response_message = isset($result->transaction->processorResponseText) ?
                $result->transaction->processorResponseText : null;
        } else {
            $status = 'declined';
            $success = false;
            $response_message = isset($result->message) ? $result->message : null;
        }

        $this->logActions('refundCc', $success, $result, [
            'transaction_id' => $transaction_id,
            'amount' => $amount
        ]);

        return [
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => isset($result->transaction->id) ? $result->transaction->id : null,
            'message' => $response_message
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function storeCc(array $card_info, array $contact, $client_reference_id = null)
    {
        $this->loadApi();

        // Format card and customer data to be submitted to BrainTree
        $params = $this->getCardStorageParameters($card_info, $contact);

        $customer = null;
        // Retrieve an existing customer from BrainTree if set
        if (!is_null($client_reference_id)) {
            try {
                $customer = Braintree_Customer::find($client_reference_id);
            } catch (Braintree\Exception\NotFound $ex) {
                // Do nothing
            }
        }

        // No customer exists, create a new one
        if (is_null($customer)) {
            $result = Braintree_Customer::create($params['customer']);

            // Log customer creation
            $this->logActions(
                'Customer::Create',
                $result->success,
                $result,
                $params['customer']
            );

            // Customer creation failed
            if (!$result->success) {
                return false;
            }
            $customer = $result->customer;
        }

        // Create new credit card for the customer
        $params['card']['customerId'] = $customer->id;
        $card = Braintree_CreditCard::create($params['card']);

        // Log card creation
        $this->logActions(
            'CreditCard::Create',
            $card->success,
            $card,
            $params['card']
        );

        // Card creation failed
        if (!$card->success) {
            return false;
        }

        return [
            'client_reference_id' => $customer->id,
            'reference_id' => $card->creditCard->token
        ];
    }

    /**
     * Gets a list of card and customer data to be submitted to BrainTree
     *
     * @param array $card_info An array of card info to store off site including:
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
     *  - reference_id The reference ID attached to this account given by the payment processor (optional)
     *  - client_reference_id The reference ID for the client this payment account belongs to (optional)
     * @param array $contact An array of contact information for the billing contact this account is to be
     *  set up under including:
     *
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
     * @return array A list of card and customer to be submitted to BrainTree
     */
    private function getCardStorageParameters(array $card_info, array $contact)
    {
        $card_params = [
            'cardholderName' => $card_info['first_name'] . ' ' . $card_info['last_name'],
            'number' => $card_info['card_number'],
            'cvv' => $card_info['card_security_code'],
            'expirationDate' => $this->formatExpiryDate($card_info['card_exp']),
            'billingAddress' => [
                'firstName' => $card_info['first_name'],
                'lastName' => $card_info['last_name'],
                'streetAddress' => $card_info['address1'],
                'postalCode' => $card_info['zip'],
                'locality' => $card_info['city'],
                'region' => $card_info['state']['code'],
                'countryCodeAlpha3' => $card_info['country']['alpha3'],
            ]
        ];
        $customer_params = [
            'firstName' => $contact['first_name'],
            'lastName' => $contact['last_name'],
        ];
        return ['customer' => $customer_params, 'card' => $card_params];
    }

    /**
     * {@inheritdoc}
     */
    public function updateCc(array $card_info, array $contact, $client_reference_id, $account_reference_id)
    {
        $this->loadApi();

        // Format card and customer data to be submitted to BrainTree
        $params = $this->getCardStorageParameters($card_info, $contact);

        // Update credit card for the customer
        $card = Braintree_CreditCard::update($account_reference_id, $params['card']);

        // Log card update
        $this->logActions(
            'CreditCard::Update',
            $card->success,
            $card,
            $params['card']
        );

        // Card update failed
        if (!$card->success) {
            return false;
        }

        return [
            'client_reference_id' => $client_reference_id,
            'reference_id' => $card->creditCard->token
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function removeCc($client_reference_id, $account_reference_id)
    {
        $this->loadApi();

        // Remove credit card from the customer
        try {
            $card = Braintree_CreditCard::delete($account_reference_id);
        } catch (Throwable $e) {
            $this->Input->setErrors(['account_reference_id' => ['exception' => $e->getMessage()]]);
        }

        // Log card remove
        $this->logActions(
            'CreditCard::Remove',
            $card->success ?? null,
            $card ?? null,
            ['account_reference_id' => $account_reference_id]
        );

        // Card remove failed
        if (!isset($card->success) || !$card->success) {
            return false;
        }

        return [
            'client_reference_id' => $client_reference_id,
            'reference_id' => $card->id ?? $account_reference_id
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function processStoredCc(
        $client_reference_id,
        $account_reference_id,
        $amount,
        array $invoice_amounts = null
    ) {
        $this->loadApi();

        $transaction_fields = [
            'amount' => $amount,
            'paymentMethodToken' => $account_reference_id,
            'options' => [
                'submitForSettlement' => true
            ],
        ];
        $result = Braintree_Transaction::sale($transaction_fields);

        if ($result->success) {
            $success = true;
            $status = 'approved';
            $response_message = isset($result->transaction->processorResponseText) ?
                $result->transaction->processorResponseText : null;
        } elseif ($result->transaction) {
            $status = 'declined';
            $success = false;
            $response_message = isset($result->transaction->processorResponseText) ?
                $result->transaction->processorResponseText : null;
        } else {
            $status = 'declined';
            $success = false;
            $response_message = isset($result->message) ? $result->message : null;
        }
        $this->logActions('processCc', $success, $result, $transaction_fields);

        return [
            'status' => $status,
            'reference_id' => $account_reference_id,
            'transaction_id' => isset($result->transaction->id) ? $result->transaction->id : null,
            'message' => $response_message
        ];
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function captureStoredCc(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id,
        $amount,
        array $invoice_amounts = null
    ) {
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }

    /**
     * {@inheritdoc}
     */
    public function voidStoredCc(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id
    ) {
        return $this->voidCc($transaction_reference_id, $transaction_id);
    }

    /**
     * {@inheritdoc}
     */
    public function refundStoredCc(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id,
        $amount
    ) {
        return $this->refundCc($transaction_reference_id, $transaction_id, $amount);
    }

    /**
     * Loads the given API
     *
     */
    private function loadApi()
    {
        Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'Braintree.php');
        $environment = isset($this->meta['live_mode']) && $this->meta['live_mode'] == true ? 'production' : 'sandbox';
        Braintree_Configuration::environment($environment);
        Braintree_Configuration::merchantId($this->meta['merchant_id']);
        Braintree_Configuration::publicKey($this->meta['public_key']);
        Braintree_Configuration::privateKey($this->meta['private_key']);
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
        return $new_date[1] . '/' . $new_date[0];
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
            'number',
            'cvv',
            'expirationDate',
        ];

        // log the request sent to the gateway
        $this->log($type, serialize($this->maskDataRecursive($request, $mask_fields)), 'input', true);
        // log the response from gateway
        $this->log($type, serialize($response), 'output', $success);
    }
}
