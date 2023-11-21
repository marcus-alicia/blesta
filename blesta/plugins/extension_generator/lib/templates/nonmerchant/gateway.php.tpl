<?php
/**
 * {{name}} Gateway
 *{{array:authors}}
 * @link {{authors.url}} {{authors.name}}{{array:authors}}
 */
class {{class_name}} extends NonmerchantGateway
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
        $this->view->setDefaultView('components' . DS . 'gateways' . DS . 'nonmerchant' . DS . '{{snake_case_name}}' . DS);
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('meta', $meta);

        return $this->view->fetch();
    }{{function:upgrade}}

    /**
     * Performs migration of data from $current_version (the current installed version)
     * to the given file set version
     *
     * @param string $current_version The current installed version of this gateway
     */
    public function upgrade($current_version)
    {
////        if (version_compare($current_version, '1.1.0', '<')) {
////        }
    }{{function:upgrade}}

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
     * Sets the currency code to be used for all subsequent payments
     *
     * @param string $currency The ISO 4217 currency code to be used for subsequent payments
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * Returns all HTML markup required to render an authorization and capture payment form
     *
     * @param array $contact_info An array of contact info including:
     *  - id The contact ID
     *  - client_id The ID of the client this contact belongs to
     *  - user_id The user ID this contact belongs to (if any)
     *  - contact_type The type of contact
     *  - contact_type_id The ID of the contact type
     *  - first_name The first name on the contact
     *  - last_name The last name on the contact
     *  - title The title of the contact
     *  - company The company name of the contact
     *  - address1 The address 1 line of the contact
     *  - address2 The address 2 line of the contact
     *  - city The city of the contact
     *  - state An array of state info including:
     *      - code The 2 or 3-character state code
     *      - name The local name of the country
     *  - country An array of country info including:
     *      - alpha2 The 2-character country code
     *      - alpha3 The 3-cahracter country code
     *      - name The english name of the country
     *      - alt_name The local name of the country
     *  - zip The zip/postal code of the contact
     * @param float $amount The amount to charge this contact
     * @param array $invoice_amounts An array of invoices, each containing:
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @param array $options An array of options including:
     *  - description The Description of the charge
     *  - return_url The URL to redirect users to after a successful payment
     *  - recur An array of recurring info including:
     *      - amount The amount to recur
     *      - term The term to recur
     *      - period The recurring period (day, week, month, year, onetime) used in conjunction
     *          with term in order to determine the next recurring payment
     * @return string HTML markup required to render an authorization and capture payment form
     */
    public function buildProcess(array $contact_info, $amount, array $invoice_amounts = null, array $options = null)
    {

        // Force 2-decimal places only
        $amount = round($amount, 2);
        if (isset($options['recur']['amount'])) {
            $options['recur']['amount'] = round($options['recur']['amount'], 2);
        }

        $this->view = $this->makeView('process', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get a list of key/value hidden fields to set for the payment form
////        $fields = $this->getProcessFields($contact_info, $amount, $invoice_amounts, $options);

////        $this->view->set('post_to', $api->getPaymentUrl());
////        $this->view->set('fields', $fields);

        return $this->view->fetch();
    }

    /**
     * Validates the incoming POST/GET response from the gateway to ensure it is
     * legitimate and can be trusted.
     *
     * @param array $get The GET data for this request
     * @param array $post The POST data for this request
     * @return array An array of transaction data, sets any errors using Input if the data fails to validate
     *  - client_id The ID of the client that attempted the payment
     *  - amount The amount of the payment
     *  - currency The currency of the payment
     *  - invoices An array of invoices and the amount the payment should be applied to (if any) including:
     *      - id The ID of the invoice to apply to
     *      - amount The amount to apply to the invoice
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the gateway to identify this transaction
     *  - parent_transaction_id The ID returned by the gateway to identify this
     *      transaction's original transaction (in the case of refunds)
     */
    public function validate(array $get, array $post)
    {
////// For more information on writing validation rules, see the
////// docs at https://docs.blesta.com/display/dev/Error+Checking
////
////    There is often a hash validation and/or additional API calls that happen in this function as
////    its primary purpose is to validate the authenticity of a webhook callback from the payment processor
        $rules = [];

        $this->Input->setRules($rules);
        $success = $this->Input->validates($post);

        // Log the response
        $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($post), 'output', $success);

        if (!$success) {
            return;
        }

        return [
            'client_id' => (isset($post['client_id']) ? $post['client_id'] : null),
            'amount' => (isset($post['total']) ? $post['total'] : null),
            'currency' => (isset($post['currency_code']) ? $post['currency_code'] : null),
            'invoices' => unserialize(base64_decode((isset($post['invoices']) ? $post['invoices'] : null))),
            'status' => 'approved',
            'reference_id' => null,
            'transaction_id' => (isset($post['order_number']) ? $post['order_number'] : null),
            'parent_transaction_id' => null
        ];
    }

    /**
     * Returns data regarding a success transaction. This method is invoked when
     * a client returns from the non-merchant gateway's web site back to Blesta.
     *
     * @param array $get The GET data for this request
     * @param array $post The POST data for this request
     * @return array An array of transaction data, may set errors using Input if the data appears invalid
     *  - client_id The ID of the client that attempted the payment
     *  - amount The amount of the payment
     *  - currency The currency of the payment
     *  - invoices An array of invoices and the amount the payment should be applied to (if any) including:
     *      - id The ID of the invoice to apply to
     *      - amount The amount to apply to the invoice
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - transaction_id The ID returned by the gateway to identify this transaction
     *  - parent_transaction_id The ID returned by the gateway to identify this transaction's original transaction
     */
    public function success(array $get, array $post)
    {
////        Format data from $get and $post
////
////        $params = [
////            'client_id' => (isset($post['client_id']) ? $post['client_id'] : null),
////            'amount' => (isset($post['total']) ? $post['total'] : null),
////            'currency' => (isset($post['currency_code']) ? $post['currency_code'] : null),
////            'invoices' => unserialize(base64_decode((isset($post['invoices']) ? $post['invoices'] : null))),
////            'status' => 'approved',
////            'transaction_id' => (isset($post['order_number']) ? $post['order_number'] : null),
////            'parent_transaction_id' => null
////        ];
        $params = [
            'client_id' => '',
            'amount' => '',
            'currency' => '',
            'invoices' => '',
            'status' => 'approved',
            'transaction_id' => '',
            'parent_transaction_id' => null
        ];

        return $params;
    }

    /**
     * Refund a payment
     *
     * @param string $reference_id The reference ID for the previously submitted transaction
     * @param string $transaction_id The transaction ID for the previously submitted transaction
     * @param float $amount The amount to refund this transaction
     * @param string $notes Notes about the refund that may be sent to the client by the gateway
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function refund($reference_id, $transaction_id, $amount, $notes = null)
    {
        $this->Input->setErrors($this->getCommonError('unsupported'));
////        $params = [/* Format params for the refund request */];
////
////        // Attempt a refund
////        $api = $this->getApi();
////        $refund_response = $api->refund($params);
////
////        // Log data sent
////        $this->log('refund', json_encode($params), 'input', true);
////
////        // Log the response
////        $errors = $refund_response->errors();
////        $success = $refund_response->status() == '200' && empty($errors);
////        $this->log('refund', $refund_response->raw(), 'output', $success);
////
////        // Output errors
////        if (!$success) {
////            $this->Input->setErrors(['api' => $errors]);
////            return;
////        }
////
////        return [
////            'status' => 'refunded',
////            'transaction_id' => $transaction_id
////        ];
    }

    /**
     * Void a payment or authorization.
     *
     * @param string $reference_id The reference ID for the previously submitted transaction
     * @param string $transaction_id The transaction ID for the previously submitted transaction
     * @param string $notes Notes about the void that may be sent to the client by the gateway
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function void($reference_id, $transaction_id, $notes = null)
    {
        $this->Input->setErrors($this->getCommonError('unsupported'));
////
////        // Load the API
////        $api = $this->getApi();
////
////        $params = [/* Format params for the void request */];
////
////        // Log data sent
////        $this->log('void', json_encode($params), 'input', true);
////
////        // Get the payment details
////        $void_response = $api->void();
////        $errors = $void_response->errors();
////        $success = $void_response->status() == '200' && empty($errors);
////
////        // Log the API response
////        $this->log('void', $refund->raw(), 'output', $success);
////
////
////        return [
////            'status' => 'void',
////            'transaction_id' => (isset($transaction_id) ? $transaction_id : null)
////        ];
    }

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
