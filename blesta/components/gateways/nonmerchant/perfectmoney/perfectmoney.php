<?php
/**
 * Perfect Money.
 *
 * Shopping cart interface can be found at:
 * https://perfectmoney.is/sci_generator.html
 * Perfect Money API reference:
 * https://perfectmoney.is/sample-api.html
 *
 * @package blesta
 * @subpackage blesta.components.gateways.perfectmoney
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Perfectmoney extends NonmerchantGateway
{
    /**
     * @var string The version of this gateway
     */
    private static $version = '1.1.0';

    /**
     * @var string The authors of this gateway
     */
    private static $authors = [['name' => 'Phillips Data, Inc.', 'url' => 'http://www.blesta.com']];

    /**
     * @var array An array of meta data for this gateway
     */
    private $meta;

    /**
     * @var string The URL to post payments to
     */
    private $perfectmoney_url = 'https://perfectmoney.is/api/step1.asp';

    /**
     * Construct a new non-merchant gateway.
     */
    public function __construct()
    {
        // Load components required by this gateway
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this gateway
        Language::loadLang('perfectmoney', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * Returns the name of this gateway.
     *
     * @return string The common name of this gateway
     */
    public function getName()
    {
        return Language::_('Perfectmoney.name', true);
    }

    /**
     * Returns the version of this gateway.
     *
     * @return string The current version of this gateway
     */
    public function getVersion()
    {
        return self::$version;
    }

    /**
     * Returns the name and URL for the authors of this gateway.
     *
     * @return array The name and URL of the authors of this gateway
     */
    public function getAuthors()
    {
        return self::$authors;
    }

    /**
     * Return all currencies supported by this gateway.
     *
     * @return array A numerically indexed array containing all currency codes (ISO 4217 format) this gateway supports
     */
    public function getCurrencies()
    {
        return ['EUR', 'USD'];
    }

    /**
     * Sets the currency code to be used for all subsequent payments.
     *
     * @param string $currency The ISO 4217 currency code to be used for subsequent payments
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * Create and return the view content required to modify the settings of this gateway.
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
     * Validates the given meta (settings) data to be updated for this gateway.
     *
     * @param array $meta An array of meta (settings) data to be updated for this gateway
     * @return array The meta data to be updated in the database for this gateway, or reset into the form on failure
     */
    public function editSettings(array $meta)
    {
        // Verify meta data is valid
        $rules = [
            'payee_account' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Perfectmoney.!error.payee_account.valid', true)
                ]
            ],
            'passphrase' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Perfectmoney.!error.passphrase.valid', true)
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
     * Returns an array of all fields to encrypt when storing in the database.
     *
     * @return array An array of the field names to encrypt when storing in the database
     */
    public function encryptableFields()
    {
        return ['payee_account', 'passphrase'];
    }

    /**
     * Sets the meta data for this particular gateway.
     *
     * @param array $meta An array of meta data to set for this gateway
     */
    public function setMeta(array $meta = null)
    {
        $this->meta = $meta;
    }

    /**
     * Returns all HTML markup required to render an authorization and capture payment form.
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
        // Load the models required
        Loader::loadModels($this, ['Companies']);

        // Force 2-decimal places only
        $amount = round($amount, 2);
        if (isset($options['recur']['amount'])) {
            $options['recur']['amount'] = round($options['recur']['amount'], 2);
        }

        // Set url to post to
        $post_to = $this->perfectmoney_url;

        // Get company information
        $company = $this->Companies->get(Configure::get('Blesta.company_id'));

        // An array of key/value hidden fields to set for the payment form
        $fields = [
            'PAYMENT_ID' => uniqid(),
            'PAYEE_ACCOUNT' => (isset($this->meta['payee_account']) ? $this->meta['payee_account'] : null),
            'PAYEE_NAME' => (isset($company->name) ? $company->name : null),
            'PAYMENT_AMOUNT' => $amount,
            'PAYMENT_UNITS' => $this->currency,
            'STATUS_URL' => Configure::get('Blesta.gw_callback_url')
                . Configure::get('Blesta.company_id')
                . '/perfectmoney/?client_id='
                . (isset($contact_info['client_id']) ? $contact_info['client_id'] : null),
            'PAYMENT_URL' => (isset($options['return_url']) ? $options['return_url'] : null),
            'PAYMENT_URL_METHOD' => 'POST',
            'NOPAYMENT_URL' => (isset($options['return_url']) ? $options['return_url'] : null),
            'NOPAYMENT_URL_METHOD' => 'POST',
            'BAGGAGE_FIELDS' => 'INVOICES',
            'PAYMENT_METHOD' => 'Pay Now!'
        ];

        // Set all invoices to pay
        if (isset($invoice_amounts) && is_array($invoice_amounts)) {
            $fields['INVOICES'] = $this->serializeInvoices($invoice_amounts);
        }

        // Log input
        $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($fields), 'input', true);

        return $this->buildForm($post_to, $fields);
    }

    /**
     * Builds the HTML form.
     *
     * @param string $post_to The URL to post to
     * @param array $fields An array of key/value input fields to set in the form
     * @return string The HTML form
     */
    private function buildForm($post_to, $fields)
    {
        $this->view = $this->makeView('process', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

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
     *  - parent_transaction_id The ID returned by the gateway to identify this transaction's
     *      original transaction (in the case of refunds)
     */
    public function validate(array $get, array $post)
    {
        // Calculate MD5 hash
        $signature = (isset($post['PAYMENT_ID']) ? $post['PAYMENT_ID'] : null) . ':'
            . (isset($post['PAYEE_ACCOUNT']) ? $post['PAYEE_ACCOUNT'] : null) . ':'
            . (isset($post['PAYMENT_AMOUNT']) ? $post['PAYMENT_AMOUNT'] : null) . ':'
            . (isset($post['PAYMENT_UNITS']) ? $post['PAYMENT_UNITS'] : null) . ':'
            . (isset($post['PAYMENT_BATCH_NUM']) ? $post['PAYMENT_BATCH_NUM'] : null) . ':'
            . (isset($post['PAYER_ACCOUNT']) ? $post['PAYER_ACCOUNT'] : null) . ':'
            . strtoupper(md5($this->meta['passphrase'])) . ':'
            . (isset($post['TIMESTAMPGMT']) ? $post['TIMESTAMPGMT'] : null);

        $v2_hash = strtoupper(md5($signature));

        // Ensure payment is verified, and validate that the business is valid
        // and matches that configured for this gateway, to prevent payments
        // being recognized that were delivered to a different account
        $account_id = strtolower((isset($this->meta['payee_account']) ? $this->meta['payee_account'] : null));

        if (strtolower((isset($post['PAYEE_ACCOUNT']) ? $post['PAYEE_ACCOUNT'] : null)) != $account_id
            || (isset($post['V2_HASH']) ? $post['V2_HASH'] : null) != $v2_hash
        ) {
            $this->Input->setErrors($this->getCommonError('invalid'));

            // Log error response
            $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($post), 'output', false);

            return;
        }

        // Capture the IPN status, or reject it if invalid
        $status = 'declined';
        if ($v2_hash == (isset($post['V2_HASH']) ? $post['V2_HASH'] : null)) {
            $status = 'approved';

            // Log response
            $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($post), 'output', true);
        }

        $client_id = (isset($get['client_id']) ? $get['client_id'] : null);

        return [
            'client_id' => $client_id,
            'amount' => (isset($post['PAYMENT_AMOUNT']) ? $post['PAYMENT_AMOUNT'] : null),
            'currency' => (isset($post['PAYMENT_UNITS']) ? $post['PAYMENT_UNITS'] : null),
            'status' => $status,
            'transaction_id' => (isset($post['PAYMENT_BATCH_NUM']) ? $post['PAYMENT_BATCH_NUM'] : null),
            'invoices' => $this->unserializeInvoices((isset($post['INVOICES']) ? $post['INVOICES'] : null))
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

        return [
            'client_id' => $client_id,
            'amount' => (isset($post['PAYMENT_AMOUNT']) ? $post['PAYMENT_AMOUNT'] : null),
            'currency' => (isset($post['PAYMENT_UNITS']) ? $post['PAYMENT_UNITS'] : null),
            'invoices' => $this->unserializeInvoices((isset($post['INVOICES']) ? $post['INVOICES'] : null)),
            'status' => 'approved', // we wouldn't be here if it weren't, right?
            'transaction_id' => (isset($post['PAYMENT_BATCH_NUM']) ? $post['PAYMENT_BATCH_NUM'] : null)
        ];
    }

    /**
     * Serializes an array of invoice info into a string.
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
     * Unserializes a string of invoice info into an array.
     *
     * @param string A serialized string of invoice info in the format of key1=value1|key2=value2
     * @param mixed $str
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
}
