<?php
/**
 * PayFast Gateway
 *
 * API documentation: https://developers.payfast.co.za/docs#quickstart
 *
 * @package blesta
 * @subpackage blesta.components.gateways.nonmerchant.payfast
 * @author Phillips Data, Inc.
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Payfast extends NonmerchantGateway
{
    /**
     * @var array An array of meta data for this gateway
     */
    private $meta;

    /**
     * @var array An array containing the valid hostnames from PayFast
     */
    private $valid_hostnames = [
        'www.payfast.co.za',
        'w1w.payfast.co.za',
        'w2w.payfast.co.za',
        'sandbox.payfast.co.za'
    ];

    /**
     * Construct a new merchant gateway
     */
    public function __construct()
    {
        // Load configuration required by this gateway
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this gateway
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this gateway
        Language::loadLang('payfast', null, dirname(__FILE__) . DS . 'language' . DS);
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
        $this->view->setDefaultView('components' . DS . 'gateways' . DS . 'nonmerchant' . DS . 'payfast' . DS);

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
        $rules = [
            'merchant_id' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Payfast.!error.merchant_id.valid', true)
                ]
            ],
            'merchant_key' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Payfast.!error.merchant_key.valid', true)
                ]
            ],
            'sandbox' => [
                'valid' => [
                    'rule' => ['in_array', ['true', 'false']],
                    'message' => Language::_('Payfast.!error.sandbox.valid', true)
                ]
            ]
        ];
        $this->Input->setRules($rules);

        // Set unset checkboxes
        $checkbox_fields = ['sandbox'];
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
        return ['merchant_key'];
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

        // Set redirect url
        $redirect_url = ($options['return_url'] ?? null);
        $query = parse_url($redirect_url, PHP_URL_QUERY);
        $invoices = $this->serializeInvoices($invoice_amounts);

        if ($query) {
            $redirect_url .= '&';
        } else {
            $redirect_url .= '?';
        }
        $redirect_url .= 'amount=' . $amount . '&invoices=' . $invoices;

        // Build payment button
        $fields = [
            'merchant_id' => $this->meta['merchant_id'],
            'merchant_key' => $this->meta['merchant_key'],
            'return_url' => $redirect_url,
            'cancel_url' => $redirect_url,
            'notify_url' => Configure::get('Blesta.gw_callback_url')
                . Configure::get('Blesta.company_id') . '/payfast/?client_id=' . ($contact_info['client_id'] ?? null),
            'name_first' => ($contact_info['first_name'] ?? null),
            'name_last' => ($contact_info['last_name'] ?? null),
            'amount' => $amount,
            'item_name' => substr($options['description'] ?? null, 0, 90)
        ];

        // Set all invoices to pay
        if (isset($invoice_amounts) && is_array($invoice_amounts)) {
            $fields['custom_str1'] = $this->serializeInvoices($invoice_amounts);
        }

        // Generate signature
        $passphrase = !empty($this->meta['passphrase']) ? $this->meta['passphrase'] : null;
        $signature = $this->generateSignature($fields, $passphrase);
        $fields['signature'] = $signature;

        // Set post to URL
        $post_to = 'https://www.payfast.co.za/eng/process';
        if (($this->meta['sandbox'] ?? 'false') == 'true') {
            $post_to = 'https://sandbox.payfast.co.za/eng/process';
        }

        $this->log($post_to, serialize($fields), 'input', true);

        $this->view->set('post_to', $post_to);
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
        $status = 'error';
        $success = true;

        // Validate the request came from a trusted domain
        $requestor = parse_url($_SERVER['HTTP_REFERER'])['host'];
        if (!in_array($requestor, $this->valid_hostnames)) {
            $success = false;
        }

        // Encode posted data to a string
        $data_string = '';
        foreach ($post as $key => $value) {
            if ($key !== 'signature') {
                $data_string .= $key . '=' . urlencode($value) . '&';
            } else {
                break;
            }
        }

        // Validate request with the gateway
        if (!$this->validateRequest($data_string, ($this->meta ?? []))) {
            $success = false;
        }

        // Validate signature
        $passphrase = !empty($this->meta['passphrase']) ? $this->meta['passphrase'] : null;
        if (!$this->validateSignature($post, $data_string, $passphrase)) {
            $success = false;
        }

        // Set status
        if (($post['payment_status'] ?? 'ERROR') == 'COMPLETE') {
            $status = 'approved';
            $success = true;
        }

        // Log the response
        $this->log(($_SERVER['REQUEST_URI'] ?? null), serialize($post), 'output', $success);

        if (!$success) {
            $this->Input->setErrors($this->getCommonError('invalid'));

            return;
        }

        return [
            'client_id' => ($get['client_id'] ?? null),
            'amount' => ($post['amount_gross'] ?? null),
            'currency' => 'ZAR',
            'invoices' => $this->deSerializeInvoices($post['custom_str1'] ?? null),
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => ($post['pf_payment_id'] ?? null),
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
        return [
            'client_id' => ($get['client_id'] ?? null),
            'amount' => ($get['amount'] ?? null),
            'currency' => 'ZAR',
            'invoices' => $this->deSerializeInvoices($get['invoices'] ?? null),
            'status' => 'approved',
            'transaction_id' => null,
            'parent_transaction_id' => null
        ];
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
            $temp = ($i > 0 ? '|' : '') . $invoice['id'] . '=' . $invoice['amount'];
            // Do not allow more invoices data to exceed the max length of 255 allowed by PayFast
            if (strlen($temp) + strlen($str) >= 255) {
                break;
            }

            $str .= $temp;
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
    private function deSerializeInvoices($str)
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
     * Generates a MD5 signature to ensure the integrity of the data transfer.
     *
     * @param array $data An array containing the data to be sent to PayFast
     * @param string $passphrase The secret passphrase to salt the signature (optional)
     * @return string The MD5 signature
     */
    private function generateSignature(array $data, $passphrase = null)
    {
        // Create parameter string
        $serialized_data = '';
        foreach ($data as $key => $val) {
            if ($val !== '') {
                $serialized_data .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }

        // Remove last ampersand
        $signature = substr($serialized_data, 0, -1);
        if ($passphrase !== null) {
            $signature .= '&passphrase=' . urlencode(trim($passphrase));
        }

        return md5($signature);
    }

    /**
     * Verifies if the signature of the request is valid.
     *
     * @param array $data The posted request data
     * @param string $data_string The URL encoded data
     * @param string $passphrase The secret passphrase used to salt the signature (optional)
     * @return bool True if the signature is valid
     */
    private function validateSignature(array $data, $data_string, $passphrase = null)
    {
        // Calculate security signature
        if (!is_null($passphrase)) {
            $data_string = $data_string . '&passphrase=' . urlencode($passphrase);
        }

        $signature = md5($data_string);

        return ($data['signature'] === $signature);
    }

    /**
     * Validates if the posted request is valid
     *
     * @param string $data_string The URL encoded data
     * @param array $meta An array of meta data for this gateway
     * @return bool True if the request is valid.
     */
    private function validateRequest($data_string, array $meta = [])
    {
        // Set request URL
        $url = 'https://www.payfast.co.za/eng/query/validate';
        if (($this->meta['sandbox'] ?? 'false') == 'true') {
            $url = 'https://sandbox.payfast.co.za/eng/query/validate';
        }

        if (empty($meta)) {
            return true;
        }

        // Send request
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_USERAGENT, null);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

        // Validate response
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === 'VALID') {
            return true;
        }

        return false;
    }
}
