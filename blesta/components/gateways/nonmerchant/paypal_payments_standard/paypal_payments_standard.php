<?php
/**
 * PayPal Payments Standard
 *
 * The PayPal Payments Standard API can be found at:
 * https://merchant.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/howto_html_wp_standard_overview
 * PayPal IPN reference:
 * https://merchant.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_admin_IPNIntro
 * PayPal API reference:
 * https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/howto_api_reference
 *
 * @package blesta
 * @subpackage blesta.components.gateways.paypal_payments_subscription
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PaypalPaymentsStandard extends NonmerchantGateway
{
    /**
     * @var array An array of meta data for this gateway
     */
    private $meta;
    /**
     * @var string The URL to post payments to
     */
    private $paypal_url = 'https://www.paypal.com/cgi-bin/webscr';
    /**
     * @var string The URL to post payments to in developer mode
     */
    private $paypal_dev_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    /**
     * @var string The URL to use when communicating with the PayPal API
     */
    private $paypal_api_url = 'https://api-3t.paypal.com/nvp';
    /**
     * @var string The URL to use when communicating with the PayPal API in developer mode
     */
    private $paypal_api_dev_url = 'https://api-3t.sandbox.paypal.com/nvp';


    /**
     * Construct a new merchant gateway
     */
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this gateway
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this gateway
        Language::loadLang('paypal_payments_standard', null, dirname(__FILE__) . DS . 'language' . DS);
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
            'account_id' => [
                'valid' => [
                    'rule' => ['isEmail', false],
                    'message' => Language::_('PaypalPaymentsStandard.!error.account_id.valid', true)
                ]
            ],
            'dev_mode'=>[
                'valid'=>[
                    'if_set'=>true,
                    'rule'=>['in_array', ['true', 'false']],
                    'message'=>Language::_('PaypalPaymentsStandard.!error.dev_mode.valid', true)
                ]
            ]
        ];

        // Set checkbox if not set
        if (!isset($meta['dev_mode'])) {
            $meta['dev_mode'] = 'false';
        }

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
        return ['account_id', 'api_username', 'api_password', 'api_signature'];
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
     *      - start_date The date/time in UTC that the recurring payment begins
     *      - amount The amount to recur
     *      - term The term to recur
     *      - period The recurring period (day, week, month, year, onetime) used in
     *          conjunction with term in order to determine the next recurring payment
     * @return mixed A string of HTML markup required to render an authorization and
     *  capture payment form, or an array of HTML markup
     */
    public function buildProcess(array $contact_info, $amount, array $invoice_amounts = null, array $options = null)
    {

        // Force 2-decimal places only
        $amount = round($amount, 2);
        if (isset($options['recur']['amount'])) {
            $options['recur']['amount'] = round($options['recur']['amount'], 2);
        }

        $post_to = $this->paypal_url;
        if ((isset($this->meta['dev_mode']) ? $this->meta['dev_mode'] : null) == 'true') {
            $post_to = $this->paypal_dev_url;
        }

        // An array of key/value hidden fields to set for the payment form
        $fields = [
            'cmd' => '_xclick',
            'business' => (isset($this->meta['account_id']) ? $this->meta['account_id'] : null),
            'page_style' => (isset($this->meta['page_style']) ? $this->meta['page_style'] : 'primary'),
            'item_name' => (isset($options['description']) ? $options['description'] : null),
            'amount' => $amount,
            'currency_code' => $this->currency,
            'notify_url' => Configure::get('Blesta.gw_callback_url')
                . Configure::get('Blesta.company_id') . '/paypal_payments_standard/?client_id='
                . (isset($contact_info['client_id']) ? $contact_info['client_id'] : null),
            'return' => (isset($options['return_url']) ? $options['return_url'] : null),
            'rm' => '2', // redirect back using POST
            'no_note' => '1', // no buyer notes
            'no_shipping' => '1', // no buyer shipping info
            'first_name' => (isset($contact_info['first_name']) ? $contact_info['first_name'] : null),
            'last_name' => (isset($contact_info['last_name']) ? $contact_info['last_name'] : null),
            'address1' => (isset($contact_info['address1']) ? $contact_info['address1'] : null),
            'address2' => (isset($contact_info['address2']) ? $contact_info['address2'] : null),
            'city' => (isset($contact_info['city']) ? $contact_info['city'] : null),
            'country' => (isset($contact_info['country']['alpha2']) ? $contact_info['country']['alpha2'] : null),
            'zip' => (isset($contact_info['zip']) ? $contact_info['zip'] : null),
            'charset' => 'utf-8',
            'bn' => 'PhillipsData_SP'
        ];

        // Set state if US
        if ((isset($contact_info['country']['alpha2']) ? $contact_info['country']['alpha2'] : null) == 'US') {
            $fields['state'] = (isset($contact_info['state']['code']) ? $contact_info['state']['code'] : null);
        }

        // Set all invoices to pay
        if (isset($invoice_amounts) && is_array($invoice_amounts)) {
            $fields['custom'] = $this->serializeInvoices($invoice_amounts);
        }

        // Build recurring payment fields
        $recurring_fields = [];
        if ((isset($options['recur']) ? $options['recur'] : null) && (isset($options['recur']['amount']) ? $options['recur']['amount'] : null) > 0) {
            $recurring_fields = $fields;
            unset($recurring_fields['amount']);

            $t3 = null;
            // PayPal calls 'term' 'period' and 'period' 'term'...
            switch ((isset($options['recur']['period']) ? $options['recur']['period'] : null)) {
                case 'day':
                    $t3 = 'D';
                    break;
                case 'week':
                    $t3 = 'W';
                    break;
                case 'month':
                    $t3 = 'M';
                    break;
                case 'year':
                    $t3 = 'Y';
                    break;
            }

            $recurring_fields['cmd'] = '_xclick-subscriptions';
            $recurring_fields['a1'] = $amount;

            // Calculate days until recurring payment begins. Set initial term
            // to differ from future term iff start_date is set and is set to
            // a future date
            $day_diff = 0;
            if ((isset($options['recur']['start_date']) ? $options['recur']['start_date'] : null) &&
                ($day_diff = floor((strtotime($options['recur']['start_date']) - time())/(60*60*24))) > 0) {
                $recurring_fields['p1'] = $day_diff;
                $recurring_fields['t1'] = 'D';
            } else {
                $recurring_fields['p1'] = (isset($options['recur']['term']) ? $options['recur']['term'] : null);
                $recurring_fields['t1'] = $t3;
            }
            $recurring_fields['a3'] = (isset($options['recur']['amount']) ? $options['recur']['amount'] : null);
            $recurring_fields['p3'] = (isset($options['recur']['term']) ? $options['recur']['term'] : null);
            $recurring_fields['t3'] = $t3;
            $recurring_fields['custom'] = null;
            $recurring_fields['src'] = '1'; // recur payments


            // Can't allow recurring field if prorated term is more than 90 days out
            if ($day_diff > 90) {
                $recurring_fields = [];
            }

            // Can't allow recurring field if the period is not valid (e.g. one-time)
            if ($t3 === null) {
                $recurring_fields = [];
            }
        }

        $regular_btn = $this->buildForm($post_to, $fields, false);
        $recurring_btn = null;
        if (!empty($recurring_fields)) {
            $recurring_btn = $this->buildForm($post_to, $recurring_fields, true);
        }

        switch ((isset($this->meta['pay_type']) ? $this->meta['pay_type'] : null)) {
            case 'both':
                if ($recurring_btn) {
                    return [$regular_btn, $recurring_btn];
                }
                return $regular_btn;
            case 'subscribe':
                return $recurring_btn;
            case 'onetime':
                return $regular_btn;
        }
        return null;
    }

    /**
     * Builds the HTML form
     *
     * @param string $post_to The URL to post to
     * @param array $fields An array of key/value input fields to set in the form
     * @param bool $recurring True if this is a recurring payment request, false otherwise
     * @return string The HTML form
     */
    private function buildForm($post_to, $fields, $recurring = false)
    {
        $this->view = $this->makeView('process', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('post_to', $post_to);
        $this->view->set('fields', $fields);
        $this->view->set('recurring', $recurring);

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
     *  - parent_transaction_id The ID returned by the gateway to identify this transaction's
     *      original transaction (in the case of refunds)
     */
    public function validate(array $get, array $post)
    {
        if (!isset($this->Http)) {
            Loader::loadComponents($this, ['Net']);
            $this->Http = $this->Net->create('Http');
        }

        $client_id = (isset($get['client_id']) ? $get['client_id'] : null);
        if (!$client_id) {
            $client_id = $this->clientIdFromEmail((isset($post['payer_email']) ? $post['payer_email'] : null));
        }

        $url = $this->paypal_url;
        if ($this->meta['dev_mode'] == 'true') {
            $url = $this->paypal_dev_url;
        }

        // Build data to post-back to the gateway for confirmation
        $confirm_post = array_merge(['cmd' => '_notify-validate'], $post);

        // Log request received
        $this->log(
            (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
            json_encode($this->utf8EncodeArray($post), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'output',
            true
        );

        // Confirm data with the gateway
        $response = $this->Http->post($url, http_build_query($confirm_post));

        // Log post-back sent
        $this->log(
            $url,
            json_encode($this->utf8EncodeArray($confirm_post), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'input',
            true
        );
        unset($confirm_post);


        // Ensure payment is verified, and validate that the business is valid
        // and matches that configured for this gateway, to prevent payments
        // being recognized that were delivered to a different account
        $account_id = strtolower((isset($this->meta['account_id']) ? $this->meta['account_id'] : null));

        if ($response != 'VERIFIED' || (
            strtolower((isset($post['business']) ? $post['business'] : null)) != $account_id &&
            strtolower((isset($post['receiver_email']) ? $post['receiver_email'] : null)) != $account_id)) {
            if ($response === 'INVALID') {
                $this->Input->setErrors($this->getCommonError('invalid'));

                // Log error response
                $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), $response, 'output', false);
                return;
            }
        }

        // Capture the IPN status, or reject it if invalid
        $status = 'error';
        switch (strtolower((isset($post['payment_status']) ? $post['payment_status'] : null))) {
            case 'completed':
                $status = 'approved';
                break;
            case 'pending':
                $status = 'pending';
                break;
            case 'refunded':
                $status = 'refunded';
                break;
            default:
                // Log request received, even though not necessarily processed
                $verified = ($response === 'VERIFIED');
                $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), $post['payment_status'], 'output', $verified);

                if ($verified) {
                    return;
                }
        }

        return [
            'client_id' => $client_id,
            'amount' => (isset($post['mc_gross']) ? $post['mc_gross'] : null),
            'currency' => (isset($post['mc_currency']) ? $post['mc_currency'] : null),
            'status' => $status,
            'reference_id' => (isset($post['payer_email']) ? $post['payer_email'] : null),
            'transaction_id' => (isset($post['txn_id']) ? $post['txn_id'] : null),
            'parent_transaction_id' => (isset($post['parent_txn_id']) ? $post['parent_txn_id'] : null),
            'invoices' => $this->unserializeInvoices((isset($post['custom']) ? $post['custom'] : null))
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
        $client_id = (isset($get['client_id']) ? $get['client_id'] : null);

        if (!$client_id) {
            $client_id = $this->clientIdFromEmail((isset($post['payer_email']) ? $post['payer_email'] : null));
        }

        return [
            'client_id' => $client_id,
            'amount' => (isset($post['mc_gross']) ? $post['mc_gross'] : null),
            'currency' => (isset($post['mc_currency']) ? $post['mc_currency'] : null),
            'invoices' => $this->unserializeInvoices((isset($post['custom']) ? $post['custom'] : null)),
            'status' => 'approved', // we wouldn't be here if it weren't, right?
            'transaction_id' => (isset($post['txn_id']) ? $post['txn_id'] : null)
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
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function refund($reference_id, $transaction_id, $amount, $notes = null)
    {
        $post_to = $this->getApiUrl();

        // Process the refund
        $params = [
            'METHOD' => 'RefundTransaction',
            'TRANSACTIONID' => $transaction_id,
            'REFUNDTYPE' => 'Full',
            //'AMT' => $amount,
            'CURRENCYCODE' => $this->currency,
            'NOTE' => $notes
        ];
        $response = $this->sendApi($post_to, $params);

        // If no response from gateway, set error and return
        if (!is_array($response)) {
            $this->Input->setErrors($this->getCommonError('general'));
            return;
        }

        $status = strtolower((isset($response['ACK']) ? $response['ACK'] : null));

        if ($status == 'success' || $status == 'successwithwarning') {
            // Log the successful response
            $this->log(
                $post_to,
                json_encode($this->utf8EncodeArray($response), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'output',
                true
            );

            return [
                'status' => 'refunded',
                'transaction_id' => (isset($response['REFUNDTRANSACTIONID']) ? $response['REFUNDTRANSACTIONID'] : null),
            ];
        } else {
            $this->Input->setErrors($this->getCommonError('general'));

            // Log the unsuccessful response
            $this->log(
                $post_to,
                json_encode($this->utf8EncodeArray($response), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'output',
                false
            );
        }
    }

    /**
     * Submits the API request, returns the result. Automatically sets
     * authentication parameters.
     *
     * @param string $post_to The API URL to submit the request to
     * @param array An array of name/value pairs to send to the API
     * @return array An array of name/value response pairs from the API
     */
    private function sendApi($post_to, array $params)
    {
        if (!isset($this->Http)) {
            Loader::loadComponents($this, ['Net']);
            $this->Http = $this->Net->create('Http');
        }

        $params['USER'] = (isset($this->meta['api_username']) ? $this->meta['api_username'] : null);
        $params['PWD'] = (isset($this->meta['api_password']) ? $this->meta['api_password'] : null);
        $params['SIGNATURE'] = (isset($this->meta['api_signature']) ? $this->meta['api_signature'] : null);
        $params['VERSION'] = '51.0';

        // make POST request to $post_to, log data sent and received
        $response = [];
        parse_str($this->Http->post($post_to, http_build_query($params)), $response);

        // Log data sent
        $this->log(
            $post_to,
            json_encode(
                $this->utf8EncodeArray($this->maskData($params, ['USER', 'PWD', 'SIGNATURE'])),
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            ),
            'input',
            true
        );

        return $response;
    }

    /**
     * Returns the URL to use for the API
     *
     * @return string The URL to use for the API
     */
    private function getApiUrl()
    {
        $post_to = $this->paypal_api_url;
        if ((isset($this->meta['dev_mode']) ? $this->meta['dev_mode'] : null) == 'true') {
            $post_to = $this->paypal_api_dev_url;
        }
        return $post_to;
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
            $str .= ($i > 0 ? '|' : '') . $invoice['id'] . '=' . $invoice['amount'];
        }
        return $str;
    }

    /**
     * Unserializes a string of invoice info into an array
     *
     * @param string A serialized string of invoice info in the format of key1=value1|key2=value2
     * @return array A numerically indexed array invoices info including:
     *  - id The ID of the invoice
     *  - amount The amount relating to the invoice
     */
    private function unserializeInvoices($str)
    {
        $invoices = [];
        $temp = explode('|', $str);
        foreach ($temp as $pair) {
            $pairs = explode('=', $pair, 2);
            if (count($pairs) != 2) {
                continue;
            }
            $invoices[] = ['id' => $pairs[0], 'amount' => $pairs[1]];
        }
        return $invoices;
    }

    /**
     *  Recursively convert all array values to a utf8-encoded string
     *
     * @param array $data The data to be encoded
     * @return array The encoded data
     */
    private function utf8EncodeArray(array $data) {
        foreach ($data as &$value) {
            if ($value !== null) {
                $value = (is_scalar($value) ? utf8_encode($value) : $this->utf8EncodeArray((array)$value));
            }
        }

        return $data;
    }
}
