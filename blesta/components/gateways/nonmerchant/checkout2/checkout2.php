<?php
/**
 * 2Checkout
 *
 * See the API files for links to the 2Checkout API documentation and configuration instructions
 *
 * @package blesta
 * @subpackage blesta.components.gateways.checkout2
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Checkout2 extends NonmerchantGateway
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

        // Load components required by this gateway
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this gateway
        Language::loadLang('checkout2', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * Performs migration of data from $current_version (the current installed version)
     * to the given file set version
     *
     * @param string $current_version The current installed version of this gateway
     */
    public function upgrade($current_version)
    {
        if (version_compare($current_version, '2.0.0', '<')) {
            Loader::loadModels($this, ['GatewayManager']);

            $gateways = $this->GatewayManager->getByClass('Checkout2');

            foreach ($gateways as $gateway) {
                $meta = ['api_version' => 'v1'];
                foreach ($gateway->meta as $meta_item) {
                    $meta[$meta_item->key] = $meta_item->value;
                }

                $this->GatewayManager->edit($gateway->id, ['meta' => $meta]);
            }
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

        $this->view->set('versions', $this->getApiVersions());
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
            'api_version' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['array_key_exists', $this->getApiVersions()],
                    'message' => Language::_('Checkout2.!error.api_version.valid', true)
                ]
            ],
            'test_mode' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['true', 'false']],
                    'message' => Language::_('Checkout2.!error.test_mode.valid', true)
                ]
            ]
        ];

        if ((isset($meta['api_version']) ? $meta['api_version'] : 'v1') == 'v1') {
            $rules['vendor_id'] = [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Checkout2.!error.vendor_id.empty', true)
                ]
            ];
            $rules['secret_word'] = [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Checkout2.!error.secret_word.empty', true)
                ]
            ];
            $rules['sandbox'] = [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['true', 'false']],
                    'message' => Language::_('Checkout2.!error.sandbox.valid', true)
                ]
            ];

            // Set checkbox if not set
            if (!isset($meta['sandbox'])) {
                $meta['sandbox'] = 'false';
            }
        } else {
            $rules['merchant_code'] = [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Checkout2.!error.merchant_code.empty', true)
                ]
            ];
            $rules['buy_link_secret_word'] = [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Checkout2.!error.buy_link_secret_word.empty', true)
                ]
            ];
            $rules['secret_key'] = [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Checkout2.!error.secret_key.empty', true)
                ]
            ];
        }

        // Set checkbox if not set
        if (!isset($meta['test_mode'])) {
            $meta['test_mode'] = 'false';
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
        return ['vendor_id', 'secret_word', 'merchant_code', 'buy_link_secret_word', 'secret_key'];
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

        // Force 2-decimal places only
        $amount = round($amount, 2);
        if (isset($options['recur']['amount'])) {
            $options['recur']['amount'] = round($options['recur']['amount'], 2);
        }

        $this->view = $this->makeView('process', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get a list of key/value hidden fields to set for the payment form
        $api_version = (isset($this->meta['api_version']) ? $this->meta['api_version'] : 'v1');
        $fields = $this->getOrderFields($contact_info, $amount, $invoice_amounts, $options, $api_version);

        $api = $this->getApi($api_version);
        $this->view->set('post_to', $api->getPaymentUrl());
        $this->view->set('form_method', $api_version == 'v1' ? 'post' : 'get');
        $this->view->set('fields', $fields);

        return $this->view->fetch();
    }

    /**
     * Formats the given Blesta data for the given API version
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
     * @param string $version The API version for which to format the data
     * @return array A list of data to be submitted to 2Checkout
     */
    private function getOrderFields(
        array $contact_info,
        $amount,
        array $invoice_amounts = null,
        array $options = null,
        $version = 'v1'
    ) {
        $contact_data = [
            'city' => (isset($contact_info['city']) ? $contact_info['city'] : null),
            'zip' => (isset($contact_info['zip']) ? $contact_info['zip'] : null),
            'country' => (isset($contact_info['country']['alpha3']) ? $contact_info['country']['alpha3'] : null)
        ];

        // Set contact email address and phone number
        if ((isset($contact_info['id']) ? $contact_info['id'] : false)) {
            Loader::loadModels($this, ['Contacts']);
            if (($contact = $this->Contacts->get($contact_info['id']))) {
                $contact_data['email'] = $contact->email;

                // Set a phone number, if one exists
                $contact_numbers = $this->Contacts->getNumbers($contact_info['id'], 'phone');
                if (isset($contact_numbers[0]) && !empty($contact_numbers[0]->number)) {
                    $contact_data['phone'] = preg_replace('/[^0-9]/', '', $contact_numbers[0]->number);
                }
            }
        }
        if ($version == 'v1') {
            $data = array_merge(
                [
                    // Set account/invoice info to use later
                    'client_id' => (isset($contact_info['client_id']) ? $contact_info['client_id'] : null),
                    'invoices' => base64_encode(serialize($invoice_amounts)),
                    'currency_code' => $this->currency,
                    // Set required fields
                    'sid' => (isset($this->meta['vendor_id']) ? $this->meta['vendor_id'] : null),
                    'cart_order_id' => (isset($contact_info['client_id']) ? $contact_info['client_id'] : null) . '-' . time(),
                    'total' => $amount,
                    'pay_method' => 'CC', // default to credit card option
                    'x_Receipt_Link_URL' => Configure::get('Blesta.gw_callback_url')
                        . Configure::get('Blesta.company_id') . '/checkout2/',
                    // Pre-populate billing information
                    'card_holder_name' => $this->Html->concat(
                        ' ',
                        (isset($contact_info['first_name']) ? $contact_info['first_name'] : null),
                        (isset($contact_info['last_name']) ? $contact_info['last_name'] : null)
                    ),
                    'street_address' => (isset($contact_info['address1']) ? $contact_info['address1'] : null),
                    'street_address2' => (isset($contact_info['address2']) ? $contact_info['address2'] : null),
                    'state' => (isset($contact_info['state']['code']) ? $contact_info['state']['code'] : null),
                ],
                $contact_data
            );

            // Set test mode
            if ((isset($this->meta['test_mode']) ? $this->meta['test_mode'] : null) == 'true') {
                $data['demo'] = 'Y';
            }
        } else {
            $data = array_merge(
                [
                    'currency' => $this->currency,
                    'customer-ext-ref' => (isset($contact_info['client_id']) ? $contact_info['client_id'] : null),
                    'dynamic' => 1,
                    'item-ext-ref' => base64_encode(serialize($invoice_amounts)),
                    'merchant' => (isset($this->meta['merchant_code']) ? $this->meta['merchant_code'] : null),
                    'price' => $amount,
                    'prod' => $options['description'],
                    'qty' => 1,
                    'return-type' => 'redirect',
                    'return-url' => $options['return_url'],
                    'tangible' => 0,
                    'tpl' => 'one-column',
                    'type' => 'product',
                    // Pre-populate billing information
                    'state' => (isset($contact_info['state']['name']) ? $contact_info['state']['name'] : null),
                    'company' => (isset($contact_info['company']) ? $contact_info['company'] : null),
                    'address' => $this->Html->concat(
                        ' ',
                        (isset($contact_info['address1']) ? $contact_info['address1'] : null),
                        (isset($contact_info['address2']) ? $contact_info['address2'] : null)
                    ),
                    'name' => $this->Html->concat(
                        ' ',
                        (isset($contact_info['first_name']) ? $contact_info['first_name'] : null),
                        (isset($contact_info['last_name']) ? $contact_info['last_name'] : null)
                    ),
                ],
                $contact_data
            );

            if ((isset($this->meta['test_mode']) ? $this->meta['test_mode'] : null) == 'true') {
                $data['test'] = 1;
            }

            // Sort the data alphabetically by key
            ksort($data);

            $signature_values = [];
            $signature_fields = [
                'currency', 'prod', 'price', 'qty', 'tangible', 'type', 'opt', 'description', 'recurrence', 'duration',
                'renewal-price', 'return-url', 'return-type', 'expiration', 'order-ext-ref', 'item-ext-ref',
                'customer-ref', 'customer-ext-ref', 'lock'
            ];
            foreach ($data as $key => $value) {
                if (in_array($key, $signature_fields)) {
                    $signature_values[] = strlen($value) . $value;
                }
            }

            $signature = implode('', $signature_values);
            $data['signature'] = hash_hmac('sha256', $signature, (isset($this->meta['buy_link_secret_word']) ? $this->meta['buy_link_secret_word'] : null));
        }

        return $data;
    }

    /**
     * Gets a list of API versions
     *
     * @return array A list of API versions and their language
     */
    private function getApiVersions()
    {
        return [
            'v1' => Language::_('Checkout2.getapiversions.v1', true),
            'v5' => Language::_('Checkout2.getapiversions.v5', true)
        ];
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
        $rules = [];
        if ((isset($this->meta['api_version']) ? $this->meta['api_version'] : 'v1') == 'v1') {
            // Order number to verify key must be "1" if demo mode is set
            $order_number = ((isset($post['demo']) ? $post['demo'] : null) == 'Y') ? '1' : (isset($post['order_number']) ? $post['order_number'] : null);

            // Validate the response is as expected
            $rules = [
                'key' => [
                    'valid' => [
                        'rule' => [
                            'compares',
                            '==',
                            strtoupper(
                                md5(
                                    (isset($this->meta['secret_word']) ? $this->meta['secret_word'] : null)
                                    . (isset($this->meta['vendor_id']) ? $this->meta['vendor_id'] : null)
                                    . $order_number
                                    . (isset($post['total']) ? $post['total'] : null)
                                )
                            )
                        ],
                        'message' => Language::_('Checkout2.!error.key.valid', true)
                    ]
                ],
                'credit_card_processed' => [
                    'completed' => [
                        'rule' => ['compares', '==', 'Y'],
                        'message' => Language::_('Checkout2.!error.credit_card_processed.completed', true)
                    ]
                ],
                'sid' => [
                    'valid' => [
                        'rule' => ['compares', '==', (isset($this->meta['vendor_id']) ? $this->meta['vendor_id'] : null)],
                        'message' => Language::_('Checkout2.!error.sid.valid', true)
                    ]
                ]
            ];
        } else {
            $hash_string = strlen((isset($post['IPN_PID'][0]) ? $post['IPN_PID'][0] : null)) . (isset($post['IPN_PID'][0]) ? $post['IPN_PID'][0] : null)
                . strlen((isset($post['IPN_PNAME'][0]) ? $post['IPN_PNAME'][0] : null)) . (isset($post['IPN_PNAME'][0]) ? $post['IPN_PNAME'][0] : null)
                . strlen((isset($post['IPN_DATE']) ? $post['IPN_DATE'] : null)) . (isset($post['IPN_DATE']) ? $post['IPN_DATE'] : null)
                . strlen((isset($post['IPN_DATE']) ? $post['IPN_DATE'] : null)) . (isset($post['IPN_DATE']) ? $post['IPN_DATE'] : null);

            // This is to respond to 2Checkout so they know the notification was received
            echo '<EPAYMENT>' . (isset($post['IPN_DATE']) ? $post['IPN_DATE'] : null) . '|'
                . hash_hmac('md5', $hash_string, (isset($this->meta['secret_key']) ? $this->meta['secret_key'] : null)) . '</EPAYMENT>';

            // Construct a hash to validate the order data sent by 2Checkout
            $ipn_hash_string = '';
            foreach ($post as $key => $value) {
                if ($key == 'HASH') {
                    continue;
                }

                if (is_array($value)) {
                    $ipn_hash_string .= isset($value[0]) ? strlen($value[0]) . $value[0] : 0;
                } else {
                    $ipn_hash_string .= strlen($value) . $value;
                }
            }

            // Validate the response is as expected
            $rules = [
                'HASH' => [
                    'valid' => [
                        'rule' => [
                            'compares',
                            '==',
                            hash_hmac('md5', $ipn_hash_string, (isset($this->meta['secret_key']) ? $this->meta['secret_key'] : null))
                        ],
                        'message' => Language::_('Checkout2.!error.hash.valid', true)
                    ]
                ]
            ];

            // Map fields from the REST 5.0 API to those of the legacy API
            $post['total'] = (isset($post['IPN_TOTALGENERAL']) ? $post['IPN_TOTALGENERAL'] : 0);
            $post['currency_code'] = (isset($post['CURRENCY']) ? $post['CURRENCY'] : 'USD');
            $post['order_number'] = (isset($post['REFNO']) ? $post['REFNO'] : null);
            $post['invoices'] = (isset($post['IPN_EXTERNAL_REFERENCE'][0]) ? $post['IPN_EXTERNAL_REFERENCE'][0] : null);
            $post['client_id'] = (isset($post['EXTERNAL_CUSTOMER_REFERENCE']) ? $post['EXTERNAL_CUSTOMER_REFERENCE'] : null);
        }

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
        $params = [];
        if ((isset($this->meta['api_version']) ? $this->meta['api_version'] : 'v1') == 'v1') {
            $params = [
                'client_id' => (isset($post['client_id']) ? $post['client_id'] : null),
                'amount' => (isset($post['total']) ? $post['total'] : null),
                'currency' => (isset($post['currency_code']) ? $post['currency_code'] : null),
                'invoices' => unserialize(base64_decode((isset($post['invoices']) ? $post['invoices'] : null))),
                'status' => 'approved',
                'transaction_id' => (isset($post['order_number']) ? $post['order_number'] : null),
                'parent_transaction_id' => null
            ];
        } else {
            $params = [
                'client_id' => (isset($get['customer-ext-ref']) ? $get['customer-ext-ref'] : null),
                'amount' => (isset($get['total']) ? $get['total'] : null),
                'currency' => (isset($get['total-currency']) ? $get['total-currency'] : null),
                'invoices' => isset($get['item-ext-ref']) ? unserialize(base64_decode($get['item-ext-ref'])) : [],
                'status' => 'approved',
                'transaction_id' => null,
                'parent_transaction_id' => null
            ];
        }

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
        $params = [];
        $api_version = (isset($this->meta['api_version']) ? $this->meta['api_version'] : 'v1');
        if ($api_version == 'v1') {
            $params = [
                'sale_id' => $transaction_id,
                // Category is the reason for the refund and must be a value in the domain: [1-6]U[8-17]
                'category' => 13, // 13 = Service refunded at sellers request
                'comment' => str_replace(['>', '<'], '', $notes) // comment cannot contain '>' or '<'
            ];

            // Set a default comment since the field is required
            if (empty($params['comment'])) {
                Loader::loadHelpers($this, ['CurrencyFormat']);
                $params['comment'] = Language::_(
                    'Checkout2.refund.comment',
                    true,
                    $this->CurrencyFormat->cast($amount, $this->currency)
                );
            }
        } else {
            $params = [
                'refno' => $transaction_id,
                'amount' => $amount,
                'comment' => $notes,
                'reason' => 'Other'
            ];
        }

        // Attempt a refund
        $api = $this->getApi($api_version);
        $refund_response = $api->refund($params);

        // Log data sent
        $this->log('refund', json_encode($params), 'input', true);

        // Log the response
        $errors = $refund_response->errors();
        $success = $refund_response->status() == '200' && empty($errors);
        $this->log('refund', $refund_response->raw(), 'output', $success);

        // Output errors
        if (!$success) {
            $this->Input->setErrors(['api' => $errors]);
            return;
        }

        return [
            'status' => 'refunded',
            'transaction_id' => null
        ];
    }

    /**
     * Gets the API class for the given version
     *
     * @param string $version The API version to fetch
     * @return \Checkout2ApiV1|\Checkout2ApiV5
     */
    private function getApi($version)
    {
        if (strtolower($version) == 'v1') {
            // Load the 2Checkout API
            Loader::load(dirname(__FILE__) . DS . 'api' . DS . 'checkout2_api_v1.php');
            return new Checkout2ApiV1(
                (isset($this->meta['api_username']) ? $this->meta['api_username'] : null),
                (isset($this->meta['api_password']) ? $this->meta['api_password'] : null),
                (isset($this->meta['sandbox']) ? $this->meta['sandbox'] : 'false')
            );
        } else {
            // Load the 2Checkout API
            Loader::load(dirname(__FILE__) . DS . 'api' . DS . 'checkout2_api_v5.php');
            return new Checkout2ApiV5(
                (isset($this->meta['merchant_code']) ? $this->meta['merchant_code'] : null),
                (isset($this->meta['secret_key']) ? $this->meta['secret_key'] : null)
            );
        }
    }
}
