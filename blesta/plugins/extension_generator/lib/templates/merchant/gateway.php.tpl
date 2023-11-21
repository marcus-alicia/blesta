<?php
/**
 * {{name}} Gateway
 *{{array:authors}}
 * @link {{authors.url}} {{authors.name}}{{array:authors}}
 */
class {{class_name}} extends MerchantGateway implements {{array:supported_features}}{{if:supported_features.ach:true}}MerchantAch,{{endif:supported_features.ach}}{{if:supported_features.ach_offset:true}}MerchantAchOffsite,{{endif:supported_features.ach_offset}}{{if:supported_features.cc:true}}MerchantCc,{{endif:supported_features.cc}}{{if:supported_features.cc_offsite:true}}MerchantCcOffsite,{{endif:supported_features.cc_offsite}}{{if:supported_features.cc_form:true}}MerchantCcForm,{{endif:supported_features.cc_form}}{{array:supported_features}}
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
////        // Load the {{name}} API
////        Loader::load(dirname(__FILE__) . DS . 'api' . DS . '{{snake_case_name}}_api.php');

        // Load configuration required by this gateway
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this gateway
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this gateway
        Language::loadLang('{{snake_case_name}}', null, dirname(__FILE__) . DS . 'language' . DS);
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
        $this->view->setDefaultView('components' . DS . 'gateways' . DS . 'merchant' . DS . '{{snake_case_name}}' . DS);
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
////// For more information on writing validation rules, see the
////// docs at https://docs.blesta.com/display/dev/Error+Checking
////
        $rules = [{{array:fields}}
            '{{fields.name}}' => [
                'valid' => [
                    'rule' => true,
                    'message' => Language::_('{{class_name}}.!error.{{fields.name}}.valid', true)
                ]
            ],{{array:fields}}
        ];
        $this->Input->setRules($rules);

        // Set unset checkboxes
        $checkbox_fields = [{{array:fields}}{{if:fields.type:Checkbox}}'{{fields.name}}',{{endif:fields.type}}{{array:fields}}];

        foreach ($checkbox_fields as $checkbox_field) {
            if (!isset($meta[$checkbox_field])) {
                $meta[$checkbox_field] = 'false';
            }
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
        return [{{array:fields}}{{if:fields.encryptable:true}}'{{fields.name}}',{{endif:fields.encryptable}}{{array:fields}}];
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
        return {{require_ach_storage}};
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
        return {{require_cc_storage}};
    }

    /**
     * Sets the currency code to be used for all subsequent payments
     *
     * @param string $currency The ISO 4217 currency code to be used for subsequent payments
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }{{function:storeCc}}

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
        $this->Input->setErrors($this->getCommonError('unsupported'));
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $card_info and $contact for the API call */];
////    $response = $api->storeCc($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    $card = $response->response();
////
////    return [
////        'client_reference_id' => $card->user_id_field,
////        'reference_id' => $card->id_field,
////    ];
    }{{function:storeCc}}{{function:updateCc}}

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
        $this->Input->setErrors($this->getCommonError('unsupported'));
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $card_info and $contact for the API call */];
////    $response = $api->updateCc($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    $card = $response->response();
////
////    return [
////        'client_reference_id' => $card->user_id_field,
////        'reference_id' => $card->id_field,
////    ];
    }{{function:updateCc}}{{function:removeCc}}

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
        $this->Input->setErrors($this->getCommonError('unsupported'));
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $card_info and $contact for the API call */];
////    $response = $api->removeCc($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    return [
////        'client_reference_id' => $client_reference_id,
////        'reference_id' => $account_reference_id,
////    ];
    }{{function:removeCc}}{{function:processStoredCc}}

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
        $this->Input->setErrors($this->getCommonError('unsupported'));
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $client_reference_id, $account_reference_id, $amount, and $invoice_amounts for the API call */];
////    $response = $api->processStoredCc($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    $transaction = $response->response();
////
////    return [
////        'status' => $this->mapStatus($transaction->status),
////        'reference_id' => null,
////        'transaction_id' => $transaction->id_field,
////    ];
    }{{function:processStoredCc}}{{function:authorizeStoredCc}}

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
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $client_reference_id, $account_reference_id, $amount, and $invoice_amounts for the API call */];
////    $response = $api->authorizeStoredCc($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    $transaction = $response->response();
////
////    return [
////        'status' => $this->mapStatus($transaction->status),
////        'reference_id' => null,
////        'transaction_id' => $transaction->id_field,
////    ];
    }{{function:authorizeStoredCc}}{{function:captureStoredCc}}

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
        $this->Input->setErrors($this->getCommonError('unsupported'));
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $account_reference_id, $transaction_reference_id ,$client_reference_id,
////        $account_reference_id, $amount, and $invoice_amounts for the API call */];
////    $response = $api->captureStoredCc($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    $transaction = $response->response();
////
////    return [
////        'status' => $this->mapStatus($transaction->status),
////        'reference_id' => null,
////        'transaction_id' => $transaction->id_field,
////    ];
    }{{function:captureStoredCc}}{{function:voidStoredCc}}

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
        $this->Input->setErrors($this->getCommonError('unsupported'));
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $account_reference_id, $transaction_reference_id,
////        $client_reference_id, $account_reference_id for the API call */];
////    $response = $api->voidStoredCc($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    $transaction = $response->response();
////
////    return [
////        'status' => $this->mapStatus($transaction->status),
////        'reference_id' => null,
////        'transaction_id' => $transaction->id_field,
////    ];
    }{{function:voidStoredCc}}{{function:refundStoredCc}}

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
        $this->Input->setErrors($this->getCommonError('unsupported'));
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $account_reference_id, $transaction_reference_id, $client_reference_id,
////        $account_reference_id, and $amount for the API call */];
////    $response = $api->refundStoredCc($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    $transaction = $response->response();
////
////    return [
////        'status' => $this->mapStatus($transaction->status),
////        'reference_id' => null,
////        'transaction_id' => $transaction->id_field,
////    ];
    }{{function:refundStoredCc}}{{function:processCc}}

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
        $this->Input->setErrors($this->getCommonError('unsupported'));
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $card_info, $amount, and $invoice_amounts for the API call */];
////    $response = $api->processCc($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    $transaction = $response->response();
////
////    return [
////        'status' => $this->mapStatus($transaction->status),
////        'reference_id' => null,
////        'transaction_id' => $transaction->id_field,
////    ];
    }{{function:processCc}}{{function:authorizeCc}}

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
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $card_info, $amount, and $invoice_amounts for the API call */];
////    $response = $api->authorizeCc($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    $transaction = $response->response();
////
////    return [
////        'status' => $this->mapStatus($transaction->status),
////        'reference_id' => null,
////        'transaction_id' => $transaction->id_field,
////    ];
    }{{function:authorizeCc}}{{function:captureCc}}

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
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $reference_id, $transaction_id, $amount, and $invoice_amounts for the API call */];
////    $response = $api->captureCc($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    $transaction = $response->response();
////
////    return [
////        'status' => $this->mapStatus($transaction->status),
////        'reference_id' => null,
////        'transaction_id' => $transaction->id_field,
////    ];
    }{{function:captureCc}}{{function:voidCc}}

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
        $this->Input->setErrors($this->getCommonError('unsupported'));
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $reference_id and $transaction_id for the API call */];
////    $response = $api->voidCc($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    return [
////        'status' => $this->mapStatus($transaction->status),
////        'reference_id' => $reference_id,
////        'transaction_id' => $transaction_id,
////    ];
    }{{function:voidCc}}{{function:refundCc}}

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
        $this->Input->setErrors($this->getCommonError('unsupported'));
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $reference_id, $transaction_id, and $amount for the API call */];
////    $response = $api->refundCc($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    $refund = $response->response();
////
////    return [
////        'status' => $this->mapStatus($transaction->status),
////        'reference_id' => null,
////        'transaction_id' => $refund->transaction_id_field,
////    ];
    }{{function:refundCc}}{{function:storeAch}}

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
        $this->Input->setErrors($this->getCommonError('unsupported'));
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $account_info, $contact, and $client_reference_id for the API call */];
////    $response = $api->processCc($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    $ach = $response->response();
////
////    return [
////        'client_reference_id' => $ach->user_id_field,
////        'reference_id' => $ach->id_field,
////    ];
    }{{function:storeAch}}{{function:updateAch}}

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
        $this->Input->setErrors($this->getCommonError('unsupported'));
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $account_info, $contact, $client_reference_id, and $account_reference_id for the API call */];
////    $response = $api->updateAch($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    $ach = $response->response();
////
////    return [
////        'client_reference_id' => $ach->user_id_field,
////        'reference_id' => $ach->id_field,
////    ];
    }{{function:updateAch}}{{function:removeAch}}

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
        $this->Input->setErrors($this->getCommonError('unsupported'));
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $client_reference_id and $account_reference_id for the API call */];
////    $response = $api->removeAch($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    return [
////        'client_reference_id' => $client_reference_id,
////        'reference_id' => $account_reference_id,
////    ];
    }{{function:removeAch}}{{function:processStoredAch}}

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
        $this->Input->setErrors($this->getCommonError('unsupported'));
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $client_reference_id, $account_reference_id, $amount, and $invoice_amounts for the API call */];
////    $response = $api->processStoredAch($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    $transaction = $response->response();
////
////    return [
////        'status' => $this->mapStatus($transaction->status),
////        'reference_id' => null,
////        'transaction_id' => $transaction->id_field,
////    ];
    }{{function:processStoredAch}}{{function:voidStoredAch}}

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
        $this->Input->setErrors($this->getCommonError('unsupported'));
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $client_reference_id, $account_reference_id,
////        $transaction_reference_id, and $transaction_id for the API call */];
////    $response = $api->voidStoredAch($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
///
////    return [
////        'status' => $this->mapStatus($transaction->status),
////        'reference_id' => $transaction_reference_id,
////        'transaction_id' => $transaction_id,
////    ];
    }{{function:voidStoredAch}}{{function:refundStoredAch}}

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
        $this->Input->setErrors($this->getCommonError('unsupported'));
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $client_reference_id, $account_reference_id,
////        $transaction_reference_id, $transaction_id, and $amount for the API call */];
////    $response = $api->refundStoredAch($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    $refund = $response->response();
////
////    return [
////        'status' => $this->mapStatus($transaction->status),
////        'reference_id' => null,
////        'transaction_id' => $refund->transaction_id_field,
////    ];
    }{{function:refundStoredAch}}{{function:processAch}}

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
        $this->Input->setErrors($this->getCommonError('unsupported'));
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $account_info, $amount, and $invoice_amounts for the API call */];
////    $response = $api->processAch($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    $transaction = $response->response();
////
////    return [
////        'status' => $this->mapStatus($transaction->status),
////        'reference_id' => null,
////        'transaction_id' => $transaction->id_field,
////    ];
    }{{function:processAch}}{{function:voidAch}}

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
        $this->Input->setErrors($this->getCommonError('unsupported'));
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $reference_id and $transaction_id for the API call */];
////    $response = $api->voidAch($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    return [
////        'status' => $this->mapStatus($transaction->status),
////        'reference_id' => $reference_id,
////        'transaction_id' => $transaction_id,
////    ];
    }{{function:voidAch}}{{function:refundAch}}

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
        $this->Input->setErrors($this->getCommonError('unsupported'));
////    An API call is typically made here, something like the following
////
////    $api = $this->getApi();
////
////    $vars = [/* Format $reference_id, $transaction_id, and $amount for the API call */];
////    $response = $api->refundAch($vars);
////
////    if (!$response->success()) {
////        return false;
////    }
////
////    $refund = $response->response();
////
////    return [
////        'status' => $this->mapStatus($transaction->status),
////        'reference_id' => null,
////        'transaction_id' => $refund->transaction_id_field,
////    ];
    }{{function:refundAch}}{{function:buildCcForm}}

    /**
     * {@inheritdoc}
     */
    public function buildCcForm()
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = $this->makeView('cc_form', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('meta', $this->meta);

        return $this->view->fetch();
    }{{function:buildCcForm}}{{function:buildPaymentConfirmation}}

    /**
     * {@inheritdoc}
     */
    public function buildPaymentConfirmation($reference_id, $transaction_id, $amount)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = $this->makeView(
            'payment_confirmation',
            'default',
            str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS)
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('meta', $this->meta);

        return $this->view->fetch();
    }{{function:buildPaymentConfirmation}}{{function:processCc}}

////    /**
////     * Sets the parameters for credit card transactions
////     *
////     * @param string $transaction_type The type of transaction to process
////     *  (sale, auth, refund, capture, void, update, credit)
////     * @param int $transaction_id The ID of a previous transaction if available
////     * @param float $amount The amount to charge this card
////     * @param array $card_info An array of credit card info including:
////     *
////     *  - first_name The first name on the card
////     *  - last_name The last name on the card
////     *  - card_number The card number
////     *  - card_exp The card expiration date in yyyymm format
////     *  - card_security_code The 3 or 4 digit security code of the card (if available)
////     *  - type The credit card type
////     *  - address1 The address 1 line of the card holder
////     *  - address2 The address 2 line of the card holder
////     *  - city The city of the card holder
////     *  - state An array of state info including:
////     *      - code The 2 or 3-character state code
////     *      - name The local name of the state
////     *  - country An array of country info including:
////     *      - alpha2 The 2-character country code
////     *      - alpha3 The 3-character country code
////     *      - name The english name of the country
////     *      - alt_name The local name of the country
////     *  - zip The zip/postal code of the card holder
////     * @return array A key=>value list of all transaction fields
////     */
////    private function getCcParams($transaction_type, $transaction_id = null, $amount = null, array $card_info = null)
////    {
////        $params = [];
////
////        switch ($transaction_type) {
////            case 'sale':
////            case 'auth':
////                $params = [
////                    'ccnumber' => (isset($card_info['card_number']) ? $card_info['card_number'] : null),
////                    'ccexp' => substr((isset($card_info['card_exp']) ? $card_info['card_exp'] : null), 4, 2)
////                        . substr((isset($card_info['card_exp']) ? $card_info['card_exp'] : null), 2, 2),
////                    'amount' => number_format($amount, 2, '.', ''),
////                    'cvv' => (isset($card_info['card_security_code']) ? $card_info['card_security_code'] : null),
////                    'firstname' => (isset($card_info['first_name']) ? $card_info['first_name'] : null),
////                    'lastname' => (isset($card_info['last_name']) ? $card_info['last_name'] : null),
////                    'address1' => (isset($card_info['address1']) ? $card_info['address1'] : null),
////                    'address2' => (isset($card_info['address2']) ? $card_info['address2'] : null),
////                    'city' => (isset($card_info['city']) ? $card_info['city'] : null),
////                    'state' => substr((isset($card_info['state']['code']) ? $card_info['state']['code'] : null), 0, 2),
////                    'zip' => (isset($card_info['zip']) ? $card_info['zip'] : null),
////                    'country' => (isset($card_info['country']['alpha2']) ? $card_info['country']['alpha2'] : null)
////                ];
////                break;
////            case 'refund':
////            case 'capture':
////                $params = [
////                    'transactionid' => (isset($transaction_id) ? $transaction_id : null)
////                ];
////
////                if ($amount > 0) {
////                    $params['amount'] = number_format($amount, 2, '.', '');
////                }
////            case 'void':
////                $params = [
////                    'transactionid' => (isset($transaction_id) ? $transaction_id : null)
////                ];
////                break;
////        }
////
////        return array_merge($params, ['type' => $transaction_type]);
////    }{{function:processCc}}{{function:processAch}}
////
////    /**
////     * Sets the parameters for ACH transactions
////     *
////     * @param string $transaction_type The type of transaction to process
////     *  (sale, auth, refund, capture, void, update, credit)
////     * @param int $transaction_id The ID of a previous transaction if available
////     * @param float $amount The amount to charge this card
////     * @param array $account_info An array of bank account info including:
////     *
////     *  - first_name The first name on the account
////     *  - last_name The last name on the account
////     *  - account_number The bank account number
////     *  - routing_number The bank account routing number
////     *  - type The bank account type (checking or savings)
////     *  - address1 The address 1 line of the card holder
////     *  - address2 The address 2 line of the card holder
////     *  - city The city of the card holder
////     *  - state An array of state info including:
////     *      - code The 2 or 3-character state code
////     *      - name The local name of the country
////     *  - country An array of country info including:
////     *      - alpha2 The 2-character country code
////     *      - alpha3 The 3-character country code
////     *      - name The english name of the country
////     *      - alt_name The local name of the country
////     *  - zip The zip/postal code of the account holder
////     * @return array A key=>value list of all transaction fields
////     */
////    private function getAchParams($transaction_type, $transaction_id = null, $amount = null, array $account_info = null)
////    {
////        // Load the helpers required
////        Loader::loadHelpers($this, ['Html']);
////
////        $params = [];
////
////        switch ($transaction_type) {
////            case 'sale':
////            case 'auth':
////                $params = [
////                    'checkname' => $this->Html->concat(' ', $account_info['first_name'], $account_info['last_name']),
////                    'checkaba' => (isset($account_info['routing_number']) ? $account_info['routing_number'] : null),
////                    'checkaccount' => (isset($account_info['account_number']) ? $account_info['account_number'] : null),
////                    'account_type' => (isset($account_info['type']) ? $account_info['type'] : null),
////                    'amount' => number_format($amount, 2, '.', ''),
////                    'payment' => 'check',
////                    'firstname' => (isset($account_info['first_name']) ? $account_info['first_name'] : null),
////                    'lastname' => (isset($account_info['last_name']) ? $account_info['last_name'] : null),
////                    'address1' => (isset($account_info['address1']) ? $account_info['address1'] : null),
////                    'address2' => (isset($account_info['address2']) ? $account_info['address2'] : null),
////                    'city' => (isset($account_info['city']) ? $account_info['city'] : null),
////                    'state' => substr((isset($account_info['state']['code']) ? $account_info['state']['code'] : null), 0, 2),
////                    'zip' => (isset($account_info['zip']) ? $account_info['zip'] : null),
////                    'country' => (isset($account_info['country']['alpha2']) ? $account_info['country']['alpha2'] : null)
////                ];
////                break;
////            case 'refund':
////                $params = [
////                    'transactionid' => (isset($transaction_id) ? $transaction_id : null),
////                    'payment' => 'check'
////                ];
////
////                if ($amount > 0) {
////                    $params['amount'] = number_format($amount, 2, '.', '');
////                }
////            case 'void':
////                $params = [
////                    'transactionid' => (isset($transaction_id) ? $transaction_id : null),
////                    'payment' => 'check'
////                ];
////                break;
////        }
////
////        return array_merge($params, ['type' => $transaction_type]);
////    }{{function:processAch}}

    /**
     * Loads the given API if not already loaded
     */
    private function getApi()
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . '{{snake_case_name}}_api.php');
        return new {{class_name}}Api({{array:fields}}
            $this->meta['{{fields.name}}']{{if:fields.type:Checkbox}} == 'true'{{endif:fields.type}},{{array:fields}}
        );
    }
}
