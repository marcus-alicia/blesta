<?php
/**
 * CoinPayments.net, based on the PayPal Payments Standard plugin
 *
 * @package blesta
 * @subpackage blesta.components.gateways.coinpayments
 * @copyright Copyright (c) 2017, Phillips Data, Inc. Copyright (c) 2014 CoinPayments.net
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class CoinPayments extends NonmerchantGateway
{
    /**
     * @var array An array of meta data for this gateway
     */
    private $meta;

    /**
     * @var string The merchant ID of Phillips Data Inc.
     */
    private $merchant_id = '10f425656e02bac792ea749dba767aba';

    /**
     * Construct a new merchant gateway
     */
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this gateway
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this gateway
        Language::loadLang('coin_payments', null, dirname(__FILE__) . DS . 'language' . DS);
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
        $rules = [];

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
        return ['account_id', 'merchant_id', 'ipn_secret'];
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
     *      - period The recurring period (day, week, month, year, onetime) used in conjunction with term in
     *        order to determine the next recurring payment
     * @return mixed A string of HTML markup required to render an authorization and capture payment form, or an
     *  array of HTML markup
     */
    public function buildProcess(array $contact_info, $amount, array $invoice_amounts = null, array $options = null)
    {
        // Force 8-decimal places only
        $amount = round($amount, 8);
        if (isset($options['recur']['amount'])) {
            $options['recur']['amount'] = round($options['recur']['amount'], 8);
        }

        $post_to = 'https://www.coinpayments.net/index.php';

        // An array of key/value hidden fields to set for the payment form
        $fields = [
            'cmd' => '_pay',
            'reset' => '1',
            'merchant' => (isset($this->meta['merchant_id']) ? $this->meta['merchant_id'] : null),
            'currency' => $this->currency,
            'amountf' => $amount,
            'item_name' => (isset($options['description']) ? $options['description'] : null),
            'ipn_url' => Configure::get('Blesta.gw_callback_url')
                . Configure::get('Blesta.company_id')
                . '/coin_payments/?client_id='
                . (isset($contact_info['client_id']) ? $contact_info['client_id'] : null),
            'success_url' => (isset($options['return_url']) ? $options['return_url'] : null),
            'allow_extra' => 0, // no buyer notes
            'want_shipping' => 0, // no buyer shipping info
            'first_name' => (isset($contact_info['first_name']) ? $contact_info['first_name'] : null),
            'last_name' => (isset($contact_info['last_name']) ? $contact_info['last_name'] : null),
            'email' => (isset($contact_info['email']) ? $contact_info['email'] : null),
            'address1' => (isset($contact_info['address1']) ? $contact_info['address1'] : null),
            'address2' => (isset($contact_info['address2']) ? $contact_info['address2'] : null),
            'city' => (isset($contact_info['city']) ? $contact_info['city'] : null),
            'country' => (isset($contact_info['country']['alpha2']) ? $contact_info['country']['alpha2'] : null),
            'zip' => (isset($contact_info['zip']) ? $contact_info['zip'] : null),
            'author' => $this->merchant_id
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
        /*
        $recurring_fields = array();
        if ((isset($options['recur']) ? $options['recur'] : null) && (isset($options['recur']['amount']) ? $options['recur']['amount'] : null) > 0) {
            $recurring_fields = $fields;
            unset($recurring_fields['amount']);

            $t3 = null;
            // PayPal calls 'term' 'period' and 'period' 'term'...
            switch ((isset($options['recur']['period']) ? $options['recur']['period'] : null)) {
                case "day":
                    $t3 = "D";
                    break;
                case "week":
                    $t3 = "W";
                    break;
                case "month":
                    $t3 = "M";
                    break;
                case "year";
                    $t3 = "Y";
                    break;
            }

            $recurring_fields['cmd'] = "_xclick-subscriptions";
            $recurring_fields['a1'] = $amount;

            // Calculate days until recurring payment beings. Set initial term
            // to differ from future term iff start_date is set and is set to
            // a future date
            $day_diff = 0;
            if ((isset($options['recur']['start_date']) ? $options['recur']['start_date'] : null) &&
                ($day_diff = floor((strtotime($options['recur']['start_date']) - time())/(60*60*24))) > 0) {

                $recurring_fields['p1'] = $day_diff;
                $recurring_fields['t1'] = "D";
            }
            else {
                $recurring_fields['p1'] = (isset($options['recur']['term']) ? $options['recur']['term'] : null);
                $recurring_fields['t1'] = $t3;
            }
            $recurring_fields['a3'] = (isset($options['recur']['amount']) ? $options['recur']['amount'] : null);
            $recurring_fields['p3'] = (isset($options['recur']['term']) ? $options['recur']['term'] : null);
            $recurring_fields['t3'] = $t3;
            $recurring_fields['custom'] = null;
            $recurring_fields['modify'] = (isset($this->meta['modify']) ? $this->meta['modify'] : null) == "true" ? 1 : 0;
            $recurring_fields['src'] = "1"; // recur payments


            // Can't allow recurring field if prorated term is more than 90 days out
            if ($day_diff > 90)
                $recurring_fields = array();
        }
        */

        $regular_btn = $this->buildForm($post_to, $fields, false);
        return $regular_btn;
        /*
        $recurring_btn = null;
        if (!empty($recurring_fields))
            $recurring_btn = $this->buildForm($post_to, $recurring_fields, true);

        switch ((isset($this->meta['pay_type']) ? $this->meta['pay_type'] : null)) {
            case "both":
                if ($recurring_btn)
                    return array($regular_btn, $recurring_btn);
                return $regular_btn;
            case "subscribe":
                return $recurring_btn;
            case "onetime":
                return $regular_btn;
        }
        return null;
            */
    }

    /**
     * Builds the HTML form
     *
     * @param string $post_to The URL to post to
     * @param array $fields An array of key/value input fields to set in the form
     * @param boolean $recurring True if this is a recurring payment request, false otherwise
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

    private function checkIpnRequestIsValid($post)
    {
        $error_msg = null;

        if ((isset($post['ipn_mode']) ? $post['ipn_mode'] : null) == 'hmac') {
            if (isset($_SERVER['HTTP_HMAC']) && !empty($_SERVER['HTTP_HMAC'])) {
                $request = file_get_contents('php://input');
                if ($request !== false && !empty($request)) {
                    if ((isset($post['merchant']) ? $post['merchant'] : null) == trim((isset($this->meta['merchant_id']) ? $this->meta['merchant_id'] : null))) {
                        $hmac = hash_hmac('sha512', $request, trim((isset($this->meta['ipn_secret']) ? $this->meta['ipn_secret'] : null)));
                        if ($hmac !== $_SERVER['HTTP_HMAC']) {
                            $error_msg = 'HMAC signature does not match';
                        }
                    } else {
                        $error_msg = 'No or incorrect Merchant ID passed';
                    }
                } else {
                    $error_msg = 'Error reading POST data';
                }
            } else {
                $error_msg = 'No HMAC signature sent.';
            }
        } else {
            if ((isset($post['ipn_mode']) ? $post['ipn_mode'] : null) == 'httpauth'
                && !(isset($_SERVER['PHP_AUTH_USER'])
                    && isset($_SERVER['PHP_AUTH_PW'])
                    && $_SERVER['PHP_AUTH_USER'] == trim((isset($this->meta['merchant_id']) ? $this->meta['merchant_id'] : null))
                    && $_SERVER['PHP_AUTH_PW'] == trim((isset($this->meta['ipn_secret']) ? $this->meta['ipn_secret'] : null)))
            ) {
                    $error_msg = 'Invalid merchant id/ipn secret';
            } else {
                $error_msg = 'Unknown IPN mode!';
            }
        }

        return $error_msg;
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
     *  - parent_transaction_id The ID returned by the gateway to identify this transaction's original
     *    transaction (in the case of refunds)
     */
    public function validate(array $get, array $post)
    {
        // Log request received
        $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($post), 'output', true);

        // Ensure IPN is verified, and validate that the merchant ID is correct
        $error_msg = $this->checkIpnRequestIsValid($post);
        $pmt_status = intval((isset($post['status']) ? $post['status'] : '0'));

        $status = 'declined';
        if ($pmt_status >= 100 || $pmt_status == 1 || $pmt_status == 2) {
            $status = 'approved';
        } else {
            if ($pmt_status < 0) {
                $status = 'error';
            } else {
                $status = 'pending';
            }
        }

        if ($error_msg) {
            $report = 'IPN Error: ' . $error_msg . "\n\n";
            $report .= "POST Variables\n\n";
            foreach ($post as $k => $v) {
                $report .= '|' . $k . '| = |' . $v . "|\n";
            }
            $report .= 'HTTP Auth User = |' . $_SERVER['PHP_AUTH_USER'] . "|\n";
            $report .= 'HTTP Auth Pass = |' . $_SERVER['PHP_AUTH_PW'] . "|\n";
            $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($report), 'output', true);
        }

        return [
            'client_id' => (isset($get['client_id']) ? $get['client_id'] : null),
            'amount' => (isset($post['amount1']) ? $post['amount1'] : null),
            'currency' => (isset($post['currency1']) ? $post['currency1'] : null),
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => (isset($post['txn_id']) ? $post['txn_id'] : null),
            'parent_transaction_id' => '',
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
        return ['status' => 'pending'];
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
}
