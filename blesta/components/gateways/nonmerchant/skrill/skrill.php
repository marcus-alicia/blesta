<?php
/**
 * Skrill payment gateway
 *
 * @package blesta
 * @subpackage blesta.components.gateways.nonmerchant.skrill
 * @author Phillips Data, Inc.
 * @author Nirays Technologies
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 * @link http://nirays.com/ Nirays
 */
class Skrill extends NonmerchantGateway
{
    /**
     * @var array An array of meta data for this gateway
     */
    private $meta;

    /**
     * Construct a new non-merchant gateway
     */
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this gateway
        Loader::loadComponents($this, ['Input']);

        // Load components required by this gateway
        Loader::loadModels($this, ['Clients']);

        // Load the language required by this gateway
        Language::loadLang('skrill', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * Attempt to install this gateway
     */
    public function install()
    {
        // Ensure the the system has the libxml extension
        if (!extension_loaded('libxml')) {
            $errors = [
                'libxml' => [
                    'required' => Language::_('Skrill.!error.libxml_required', true)
                ]
            ];
            $this->Input->setErrors($errors);
        }
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
            'user_email' => [
                'valid' => [
                    'rule' => ['isEmail', false],
                    'message' => Language::_('Skrill.!error.user_email.valid', true)
                ]
            ],
            'secret_word' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Skrill.!error.secret_word.empty', true)
                ]
            ],
            'merchant_id' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Skrill.!error.merchant_id.empty', true)
                ]
            ],
            'mqi' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Skrill.!error.mqi.empty', true)
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
        return ['secret_word', 'mqi'];
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
     *      - amount The amount to recur
     *      - term The term to recur
     *      - period The recurring period (day, week, month, year, onetime) used in conjunction
     *          with term in order to determine the next recurring payment
     * @return string HTML markup required to render an authorization and capture payment form
     */
    public function buildProcess(array $contact_info, $amount, array $invoice_amounts = null, array $options = null)
    {
        // Load the Skrill API
        Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'skrill_api.php');
        $client = $this->Clients->get($contact_info['client_id']);

        //Get language used by client
        $lang = $client->settings['language'];

        //Blesta returns language as en_us hence converting to ISO - 639 form
        $lang_key = strlen($lang) >= 2 ? strtoupper(substr($lang, 0, 2)) : '';

        //Skrill supported languages
        $lang_array = ['EN', 'DE', 'ES', 'FR', 'IT', 'PL', 'GR', 'RO', 'RU', 'TR', 'CN', 'CZ', 'NL', 'DA', 'SV','FI'];
        $language = in_array($lang_key, $lang_array) ? $lang_key : 'EN';

        $skrill = new Skrillapi(
            (isset($this->meta['user_email']) ? $this->meta['user_email'] : null),
            (isset($this->meta['secret_word']) ? $this->meta['secret_word'] : null),
            (isset($this->meta['merchant_id']) ? $this->meta['merchant_id'] : null),
            (isset($this->meta['mqi']) ? $this->meta['mqi'] : null)
        );
        $amount = round($amount, 2);// Force 2-decimal places only
        $return_url =  (isset($options['return_url']) ? $options['return_url'] : null);

        // The status update is given to the gateway by this url.
        $notification_url = Configure::get('Blesta.gw_callback_url')
            . Configure::get('Blesta.company_id') . '/skrill/'
            . (isset($contact_info['client_id']) ? $contact_info['client_id'] : null);
        $fields = [
            'pay_from_email' =>  (isset($client->email) ? $client->email : null),
            'firstname' => (isset($contact_info['first_name']) ? $contact_info['first_name'] : null),
            'lastname' =>  (isset($contact_info['last_name']) ? $contact_info['last_name'] : null),
            'address' => (isset($contact_info['address1']) ? $contact_info['address1'] : null),
            'address2' => (isset($contact_info['address2']) ? $contact_info['address2'] : null),
            'postal_code' => (isset($contact_info['zip']) ? $contact_info['zip'] : null),
            'city' => (isset($contact_info['city']) ? $contact_info['city'] : null),
            'state' => (isset($contact_info['state']['name']) ? $contact_info['state']['name'] : null),
            'country' => (isset($contact_info['country']['name']) ? $contact_info['country']['name'] : null),
            'pay_to_email' => (isset($this->meta['user_email']) ? $this->meta['user_email'] : null),
            'language' => $language,
            'currency' => (isset($this->currency) ? $this->currency : null),
            'return_url' => $return_url,
            'cancel_url' => (isset($options['return_url']) ? $options['return_url'] : null),
            'status_url' =>  $notification_url,
            'merchant_fields' => 'invoice_amounts,client_id',
            'invoice_amounts' => base64_encode($this->serializeInvoices($invoice_amounts)),
            'client_id' => (isset($contact_info['client_id']) ? $contact_info['client_id'] : null),
            'detail1_text' =>Language::_('Skrill.invoice.description', true),
            'detail1_description' => (isset($options['description']) ? $options['description'] : null),
            'amount' => $amount
        ];

        $this->view = $this->makeView('process', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));
        $this->view->set('fields', $fields);
        $this->view->set('post_url', $skrill->getPaymentUrl());

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
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
     */
    public function validate(array $get, array $post)
    {

        // Load the Skrill API
        Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'skrill_api.php');
        $skrill = new Skrillapi(
            (isset($this->meta['user_email']) ? $this->meta['user_email'] : null),
            (isset($this->meta['secret_word']) ? $this->meta['secret_word'] : null),
            (isset($this->meta['merchant_id']) ? $this->meta['merchant_id'] : null),
            (isset($this->meta['mqi']) ? $this->meta['mqi'] : null)
        );

        // Ensure the signature is valid (true/false)
        $valid = $skrill->validateResponse($post);
        $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($post), 'output', $valid);

        $status = 'declined';
        if ($valid) {
            // Valid transaction
            switch ($post['status']) {
                case '2':
                    $status = 'approved';
                    break;
                case '0':
                    $status = 'pending';
                    break;
                case '-1':
                    $status = 'declined';
                    break;
                case '-2':
                    $status = 'declined';
                    break;
                case '-3':
                    $status = 'refunded';
                    break;
            }
        }

        return [
            'client_id' => (isset($post['client_id']) ? $post['client_id'] : null),
            'amount' => (isset($post['mb_amount']) ? $post['mb_amount'] : null),
            'currency' => (isset($post['mb_currency']) ? $post['mb_currency'] : null),
            'invoices' => $this->unserializeInvoices(base64_decode((isset($post['invoice_amounts']) ? $post['invoice_amounts'] : null))),
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => (isset($post['mb_transaction_id']) ? $post['mb_transaction_id'] : null),
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
     */
    public function success(array $get, array $post)
    {
        return [
            'client_id' => (isset($post['client_id']) ? $post['client_id'] : null),
            'amount' => (isset($post['mb_amount']) ? $post['mb_amount'] : null),
            'currency' => (isset($post['mb_currency']) ? $post['mb_currency'] : null),
            'invoices' => $this->unserializeInvoices(base64_decode((isset($post['invoice_amounts']) ? $post['invoice_amounts'] : null))),
            'status' => 'approved',
            'reference_id' => null,
            'transaction_id' => (isset($post['mb_transaction_id']) ? $post['mb_transaction_id'] : null),
            'parent_transaction_id' => null
        ];
    }

    /**
     * Captures a previously authorized payment
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized
     * @param $amount The amount.
     * @param array $invoice_amounts
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
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
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function void($reference_id, $transaction_id, $notes = null)
    {
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }

    /**
     * Refund a payment
     *
     * @param string $reference_id The reference ID for the previously submitted transaction
     * @param string $transaction_id The transaction ID for the previously submitted transaction
     * @param float $amount The amount to refund this card
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
        // No MQI password implies it is not enabled and hence refund not supported
        if (empty($this->meta['mqi'])) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
            return;
        } else {
            // Load the Skrill API
            Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'skrill_api.php');
            $skrill = new Skrillapi(
                (isset($this->meta['user_email']) ? $this->meta['user_email'] : null),
                (isset($this->meta['secret_word']) ? $this->meta['secret_word'] : null),
                (isset($this->meta['merchant_id']) ? $this->meta['merchant_id'] : null),
                (isset($this->meta['mqi']) ? $this->meta['mqi'] : null)
            );
            $refund_status_url = Configure::get('Blesta.gw_callback_url')
                . Configure::get('Blesta.company_id') . '/skrill/';
            $fields = [
                'mb_transaction_id' => $transaction_id,
                'amount' => $amount,
                'refund_status_url' => $refund_status_url
            ];

            if ($notes) {
                $fields['refund_note'] = $notes;
            }

            $refund_url = $skrill->getRefundUrl();
            // Prepare refund request to get SID
            $this->log((isset($refund_url) ? $refund_url : null), serialize($fields), 'input', true);
            $skrill->prepareRequest($fields, 'refund');

            // Get response of the prepared request
            $response = $skrill->getRefundResponse();
            $error = $skrill->getError();
            $this->log((isset($refund_url) ? $refund_url : null), serialize($response), 'output', empty($error));

            // Execute the refund after obtaining sid
            if (!empty($response) && isset($response['sid'])) {
                // Execute the prepared request
                $this->log((isset($refund_url) ? $refund_url : null), serialize($fields), 'input', true);
                $skrill->prepareRequest(null, 'refund', $response['sid']);

                // Get response of the prepared request
                $response = $skrill->getRefundResponse();
                $error = $skrill->getError();
                $this->log((isset($refund_url) ? $refund_url : null), serialize($response), 'output', empty($error));

                if (!empty($error)) {
                    $this->Input->setErrors($this->getCommonError('general'));
                    return;
                }

                $status = 'declined';

                // Get response of the executed request
                if (!empty($response) && isset($response['status'])) {
                    switch ($response['status']) {
                        case '0':
                            $status = 'pending';
                            break;
                        case '2':
                            $status = 'refunded';
                            break;
                        case '-2':
                            $status = 'declined';
                            break;
                        default:
                            $status = 'declined';
                            break;
                    }
                }

                return [
                    'status' => $status,
                    'transaction_id' => (isset($response['mb_transaction_id']) ? $response['mb_transaction_id'] : null),
                    'reference_id' => null
                ];
            }
        }
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
}
