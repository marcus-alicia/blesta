<?php

/**
 * Payumoney payment gateway
 *
 * @package blesta
 * @subpackage blesta.components.gateways.nonmerchant.payumoney
 * @author Phillips Data, Inc.
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Payumoney extends NonmerchantGateway
{
    /**
     * @var array An array of meta data for this gateway
     */
    private $meta;


    /**
     * @var string The URLs to post payments to in production mode
     */
    private $payumoney_url = 'https://secure.payu.in/';

    /**
     * @var string The URLs to post payments to in test mode
     */
    private $payumoney_test_url = 'https://test.payu.in/';


    /**
     * @var string The URLs to get payment details from in production mode
     */
    private $payumoney_get_url = 'https://www.payumoney.com/';

    /**
     * @var string The URLs to get payment details from  in test mode
     */
    private $payumoney_get_test_url = 'https://test.payumoney.com/';


    /**
     * Construct a new non-merchant gateway
     */
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this gateway
        Loader::loadComponents($this, ['Input']);

        // Load models required by this gateway
        Loader::loadModels($this, ['Clients', 'Contacts']);

        // Load the language required by this gateway
        Language::loadLang('payumoney', null, dirname(__FILE__) . DS . 'language' . DS);
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
        $this->view = $this->makeView('settings', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));

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
            'merchant_key' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Payumoney.!error.merchant_key.valid', true)
                ]
            ],
            'merchant_salt' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Payumoney.!error.merchant_salt.valid', true)
                ]
            ],
            'auth_header' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Payumoney.!error.auth_header.valid', true)
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
        return ['merchant_key', 'merchant_salt', 'auth_header'];
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
     * Returns all HTML mark-up required to render an authorization and capture payment form
     *
     * @param array $contact_info An array of contact info including:
     *    - id The contact ID
     *    - client_id The ID of the client this contact belongs to
     *    - user_id The user ID this contact belongs to (if any)
     *    - contact_type The type of contact
     *    - contact_type_id The ID of the contact type
     *    - first_name The first name on the contact
     *    - last_name The last name on the contact
     *    - title The title of the contact
     *    - company The company name of the contact
     *    - address1 The address 1 line of the contact
     *    - address2 The address 2 line of the contact
     *    - city The city of the contact
     *    - state An array of state info including:
     *        - code The 2 or 3-character state code
     *        - name The local name of the country
     *    - country An array of country info including:
     *        - alpha2 The 2-character country code
     *        - alpha3 The 3-character country code
     *        - name The English name of the country
     *        - alt_name The local name of the country
     *    - zip The zip/postal code of the contact
     * @param float $amount The amount to charge this contact
     * @param array $invoice_amounts An array of invoices, each containing:
     *    - id The ID of the invoice being processed
     *    - amount The amount being processed for this invoice (which is included in $amount)
     * @param array $options An array of options including:
     *    - description The Description of the charge
     * @return mixed A string of HTML mark-up required to render an authorization and capture payment form, or an
     *  array of HTML markup
     */
    public function buildProcess(array $contact_info, $amount, array $invoice_amounts = null, array $options = null)
    {
        $client = $this->Clients->get($contact_info['client_id']);
        $contact = $this->Contacts->getNumbers($contact_info['id'], 'phone');

        //redirection URL
        $redirect_url = Configure::get('Blesta.gw_callback_url') . Configure::get('Blesta.company_id')
            . '/payumoney/' . (isset($contact_info['client_id']) ? $contact_info['client_id'] : null);

        $order_id = (isset($contact_info['client_id']) ? $contact_info['client_id'] : null) . '-' . time();
        $merchant_key = (isset($this->meta['merchant_key']) ? $this->meta['merchant_key'] : null);
        $merchant_salt = (isset($this->meta['merchant_salt']) ? $this->meta['merchant_salt'] : null);

        //udf1 : Custom field to pass invoice details
        $udf1 = $this->serializeInvoices($invoice_amounts);
        //udf2 : Custom parameter to pass client id
        $udf2 = (isset($contact_info['client_id']) ? $contact_info['client_id'] : null);
        $hashSequence = $merchant_key . '|' . $order_id . '|' . $amount . '|' . $options['description'] . '|'
            . (isset($contact_info['first_name']) ? $contact_info['first_name'] : null) . '|' . (isset($client->email) ? $client->email : null) . '|' . $udf1 . '|'
            . $udf2 . '|||||||||';
        $hashSequence .= $merchant_salt;
        $hash = strtolower(hash('sha512', $hashSequence));
        // Filling the request parameters
        $fields = [
            'key' => $merchant_key,
            'service_provider' => 'payu_paisa',
            'productinfo' => $options['description'],
            'txnid' => $order_id,
            'surl' => $redirect_url,
            'furl' => $redirect_url,
            'amount' => $amount,
            'firstname' => (isset($contact_info['first_name']) ? $contact_info['first_name'] : null),
            'lastname' => (isset($contact_info['last_name']) ? $contact_info['last_name'] : null),
            'address1' => (isset($contact_info['address1']) ? $contact_info['address1'] : null),
            'address2' => (isset($contact_info['address2']) ? $contact_info['address2'] : null),
            'city' => (isset($contact_info['city']) ? $contact_info['city'] : null),
            'state' => (isset($contact_info['state']['name']) ? $contact_info['state']['name'] : null),
            'country' => (isset($contact_info['country']['name']) ? $contact_info['country']['name'] : null),
            'zipcode' => (isset($contact_info['zip']) ? $contact_info['zip'] : null),
            'email' => (isset($client->email) ? $client->email : null),
            'phone' => (isset($contact[0]->number) ? $contact[0]->number : null),
            //udf1 : Custom field to pass invoice details
            'udf1' => $udf1,
            //udf2 : Custom parameter to pass client id
            'udf2' => $udf2,
            'hash' => $hash,
        ];

        $this->view = $this->makeView('process', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));
        Loader::loadHelpers($this, ['Form', 'Html']);

        //Sets sandbox or production URL based on 'test_mode' parameter value.
        $this->view->set(
            'post_to',
            ((isset($this->meta['test_mode']) ? $this->meta['test_mode'] : null) == 'true'
                ? $this->payumoney_test_url
                : $this->payumoney_url) . '_payment'
        );
        $this->view->set('fields', $fields);

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
     *    - id The ID of the invoice to apply to
     *    - amount The amount to apply to the invoice
     *    - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *    - reference_id The reference ID for gateway-only use with this transaction (optional)
     *    - transaction_id The ID returned by the gateway to identify this transaction
     *    - parent_transaction_id The ID returned by the gateway to identify this transaction's original
     *      transaction (in the case of refunds)
     */
    public function validate(array $get, array $post)
    {
        // Validate the response is as expected
        $rules = [
            'key' => [
                'valid' => [
                    'rule' => ['compares', '==', (isset($this->meta['merchant_key']) ? $this->meta['merchant_key'] : null)],
                    'message' => Language::_('Payumoney.!error.key.valid', true)
                ]
            ]
        ];

        $this->Input->setRules($rules);
        $success = $this->Input->validates($post);

        // Log the response
        $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($post), 'output', $success);

        if (!$success) {
            return;
        }

        return [
            'client_id' => (isset($post['udf2']) ? $post['udf2'] : null),
            'amount' => (isset($post['amount']) ? $post['amount'] : null),
            'currency' => 'INR',
            //Serialized invoice numbers
            'invoices' => $this->deserializeInvoices((isset($post['udf1']) ? $post['udf1'] : null)),
            'status' => ((isset($post['status']) ? $post['status'] : null) === 'success' ? 'approved' : 'declined'),
            'transaction_id' => (isset($post['payuMoneyId']) ? $post['payuMoneyId'] : null),
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
     *    - id The ID of the invoice to apply to
     *    - amount The amount to apply to the invoice
     *    - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *    - transaction_id The ID returned by the gateway to identify this transaction
     *    - parent_transaction_id The ID returned by the gateway to identify this transaction's original transaction
     */
    public function success(array $get, array $post)
    {
        $status = ((isset($post['status']) ? $post['status'] : null) === 'success' ? 'approved' : 'declined');
        if (($errors = $this->Input->errors()) && empty($errors) && $status !== 'approved') {
            $this->Input->setErrors($this->getCommonError('invalid'));
        }

        return [
            'client_id' => (isset($post['udf2']) ? $post['udf2'] : null),
            'amount' => (isset($post['amount']) ? $post['amount'] : null),
            'currency' => 'INR',
            //Serialized invoice numbers
            'invoices' => $this->deserializeInvoices((isset($post['udf1']) ? $post['udf1'] : null)),
            'status' => $status,
            'transaction_id' => (isset($post['payuMoneyId']) ? $post['payuMoneyId'] : null),
            'parent_transaction_id' => null
        ];
    }

    /**
     * Refund a payment
     *
     * @param string $reference_id The reference ID for the previously submitted transaction
     * @param string $transaction_id The transaction ID for the previously submitted transaction
     * @param float $amount The amount to refund this transaction
     * @param string $notes Notes about the refund that may be sent to the client by the gateway
     * @return array An array of transaction data including:
     *    - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *    - reference_id The reference ID for gateway-only use with this transaction (optional)
     *    - transaction_id The ID returned by the remote gateway to identify this transaction
     *    - message The message to be displayed in the interface in addition to the standard message for this
     *      transaction status (optional)
     */
    public function refund($reference_id, $transaction_id, $amount, $notes = null)
    {
        $merchant_key = (isset($this->meta['merchant_key']) ? $this->meta['merchant_key'] : null);

        $request = $this->sendApi([
            'merchantKey' => $merchant_key,
            'paymentId' => $transaction_id,
            'refundAmount' => $amount,
        ], 'payment/merchant/refundPayment');

        $status = null;
        if ($request === true) {
            $status = 'refunded';
        }

        return [
            'status' => $status,
            'reference_id' => $reference_id,
            'transaction_id' => $transaction_id,
        ];
    }

    /**
     * Captures a previously authorized payment
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction.
     * @param $amount The amount.
     * @param array $invoice_amounts
     * @return array An array of transaction data including:
     *    - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *    - reference_id The reference ID for gateway-only use with this transaction (optional)
     *    - transaction_id The ID returned by the remote gateway to identify this transaction
     *    - message The message to be displayed in the interface in addition to the standard message for this
     *      transaction status (optional)
     */
    public function capture($reference_id, $transaction_id, $amount, array $invoice_amounts = null)
    {
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }

    /**
     * Void a payment or authorization
     *
     * @param string $reference_id The reference ID for the previously submitted transaction
     * @param string $transaction_id The transaction ID for the previously submitted transaction
     * @param string $notes Notes about the void that may be sent to the client by the gateway
     * @return array An array of transaction data including:
     *    - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *    - reference_id The reference ID for gateway-only use with this transaction (optional)
     *    - transaction_id The ID returned by the remote gateway to identify this transaction
     *    - message The message to be displayed in the interface in addition to the standard message for this
     *      transaction status (optional)
     */
    public function void($reference_id, $transaction_id, $notes = null)
    {
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }

    /**
     * Serializes an array of invoice info into a string
     *
     * @param array A numerically indexed array invoices info including:
     *  - id The ID of the invoice
     *  - amount The amount relating to the invoice
     * @return string A serialized string of invoice info in the format of key1=value1|key2=value2
     */
    private function serializeInvoices(array $invoices)
    {
        $str = '';
        foreach ($invoices as $i => $invoice) {
            $str .= ($i > 0 ? '-' : '') . $invoice['id'] . '_' . $invoice['amount'];
        }

        return $str;
    }

    /**
     * Deserializes a string of invoice info into an array
     *
     * @param string A serialized string of invoice info in the format of key1=value1|key2=value2
     * @return array A numerically indexed array invoices info including:
     *  - id The ID of the invoice
     *  - amount The amount relating to the invoice
     */
    private function deserializeInvoices($str)
    {
        $invoices = [];
        $temp = explode('-', $str);

        foreach ($temp as $pair) {
            $pairs = explode('_', $pair, 2);
            if (count($pairs) != 2) {
                continue;
            }

            $invoices[] = ['id' => $pairs[0], 'amount' => $pairs[1]];
        }

        return $invoices;
    }

    /**
     * Submits the API request, returns the result. Automatically sets
     * authentication parameters.
     *
     * @param array $params An array of name/value pairs to send to the API
     * @param string $function the endpoint to submit the request to
     * @return boolean whether the request was successful or not
     */
    private function sendApi(array $params, $function)
    {
        $url = '';
        $params = http_build_query($params);
        if ((isset($this->meta['test_mode']) ? $this->meta['test_mode'] : null) == 'true') {
            $url = $this->payumoney_get_test_url . $function . '?' . $params;
        } else {
            $url = $this->payumoney_get_url . $function . '?' . $params;
        }

        $options = [
            'http' => [
                'header' => 'Authorization: ' . $this->meta['auth_header']
                    . "\n Content-type: application/x-www-form-urlencoded\r\nContent-Length: "
                    . strlen($params) . "\n",
                'method' => 'POST',
                'Authorization' => $this->meta['auth_header'],
                'content' => $params
            ],
        ];
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result != false) {
            $result = json_decode($result, true);
            if ($result['status'] === 0) {
                $this->log($url, serialize($result), 'output', true);
                return true;
            } else {
                $this->log($url, serialize($result), 'output', false);
            }
        }

        return false;
    }
}
