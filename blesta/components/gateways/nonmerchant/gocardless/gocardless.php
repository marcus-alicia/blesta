<?php
/**
 * GoCardless Gateway.
 *
 * @package blesta
 * @subpackage blesta.components.gateways.gocardless
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Gocardless extends NonmerchantGateway
{
    /**
     * @var array An array of meta data for this gateway
     */
    private $meta;

    /**
     * Construct a new merchant gateway.
     */
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this gateway
        Loader::loadComponents($this, ['Input', 'Session']);

        // Load the language required by this gateway
        Language::loadLang('gocardless', null, dirname(__FILE__) . DS . 'language' . DS);
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
            'access_token' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['isPassword', 40],
                    'message' => Language::_('Gocardless.!error.access_token.valid', true)
                ]
            ],
            'webhook_secret' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['isPassword', 40],
                    'message' => Language::_('Gocardless.!error.webhook_secret.valid', true)
                ]
            ],
            'dev_mode' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['true', 'false']],
                    'message' => Language::_('Gocardless.!error.dev_mode.valid', true)
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
     * Returns an array of all fields to encrypt when storing in the database.
     *
     * @return array An array of the field names to encrypt when storing in the database
     */
    public function encryptableFields()
    {
        return ['access_token', 'webhook_secret'];
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
        Loader::loadModels($this, ['Companies', 'Clients']);

        // Load the GoCardless API
        $api = $this->getApi($this->meta['access_token'], $this->meta['dev_mode']);

        // Force 2-decimal places only
        $amount = number_format($amount, 2, '.', '');

        if (isset($options['recur']['amount'])) {
            $options['recur']['amount'] = round($options['recur']['amount'], 2);
        }

        // Get company information
        $company = $this->Companies->get(Configure::get('Blesta.company_id'));

        // Get client data
        $client = $this->Clients->get($contact_info['client_id']);

        // Set all invoices to pay
        if (isset($invoice_amounts) && is_array($invoice_amounts)) {
            $invoices = $this->serializeInvoices($invoice_amounts);
        }

        // Check if this transaction is eligible for subscription
        $recurring = false;

        if ((isset($options['recur']) ? $options['recur'] : null) &&
            (isset($options['recur']['amount']) ? $options['recur']['amount'] : null) > 0 &&
            (isset($options['recur']['amount']) ? $options['recur']['amount'] : null) == $amount &&
            (isset($options['recur']['period']) ? $options['recur']['period'] : null) !== 'day'
        ) {
            $recurring = true;
        }

        // Check the payment type
        $pay_type = null;

        if (
            (
                isset($_GET['pay_type'])
                    ? $_GET['pay_type']
                    : (isset($_POST['pay_type']) ? $_POST['pay_type'] : null)
            ) == 'subscribe'
        ) {
            $pay_type = 'subscribe';
        } elseif (
            (
                isset($_GET['pay_type'])
                    ? $_GET['pay_type']
                    : (isset($_POST['pay_type']) ? $_POST['pay_type'] : null)
            ) == 'onetime'
        ) {
            $pay_type = 'onetime';
        }

        // Validate the redirect flow id
        if (isset($_GET['redirect_flow_id'])) {
            try {
                // Fetch the previous saved session token
                $session_token = $this->Session->read('gocardless_token');

                // Complete the redirect flow if isn't complete already
                $params = [
                    'params' => [
                        'session_token' => $session_token
                    ]
                ];
                $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($params), 'input', true);
                $redirect_flow = $api->redirectFlows()->complete((isset($_GET['redirect_flow_id']) ? $_GET['redirect_flow_id'] : null), $params);

                // Log the API response
                $this->log(
                    (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
                    serialize($redirect_flow),
                    'output',
                    $this->getResponseStatus($redirect_flow)
                );

                // Create payment or subscription
                if ($pay_type == 'subscribe') {
                    // Set the inverval unit
                    $interval_unit = null;
                    switch ((isset($options['recur']['period']) ? $options['recur']['period'] : null)) {
                        case 'week':
                            $interval_unit = 'weekly';
                            break;
                        case 'month':
                            $interval_unit = 'monthly';
                            break;
                        case 'year':
                            $interval_unit = 'yearly';
                            break;
                    }

                    // Create subscription payment
                    $params = [
                        'params' => [
                            'amount' => (isset($options['recur']['amount']) ? $options['recur']['amount'] : null) * 100,
                            'currency' => (isset($this->currency) ? $this->currency : null),
                            'interval_unit' => (isset($interval_unit) ? $interval_unit : null),
                            'interval' => (isset($options['recur']['term']) ? $options['recur']['term'] : null),
                            'metadata' => [
                                'invoices' => (isset($invoices) ? $invoices : null),
                                'client_id' => (isset($contact_info['client_id']) ? $contact_info['client_id'] : null)
                            ],
                            'links' => [
                                'mandate' => isset($redirect_flow->api_response->body->redirect_flows->links->mandate)
                                    ? $redirect_flow->api_response->body->redirect_flows->links->mandate
                                    : null
                            ]
                        ]
                    ];

                    if ($interval_unit !== 'weekly') {
                        $params['day_of_month'] = date('d');
                    }

                    if ($interval_unit == 'yearly') {
                        $params['month'] = strtolower(date('F'));
                    }

                    $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($params), 'input', true);
                    $subscription = $api->subscriptions()->create($params);

                    // Log the API response
                    $this->log(
                        (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
                        serialize($subscription),
                        'output',
                        $this->getResponseStatus($subscription)
                    );

                    // Redirect to the return url
                    $return_url = $this->generateReturnUrl((isset($options['return_url']) ? $options['return_url'] : null), [
                        'subscription_id' => (isset($subscription->api_response->body->subscriptions->id) ? $subscription->api_response->body->subscriptions->id : null)
                    ]);
                    $this->redirectToUrl($return_url);
                } elseif ($pay_type == 'onetime') {
                    // Create one time payment
                    $params = [
                        'params' => [
                            'amount' => (isset($amount) ? $amount : null) * 100,
                            'currency' => (isset($this->currency) ? $this->currency : null),
                            'metadata' => [
                                'invoices' => (isset($invoices) ? $invoices : null),
                                'client_id' => (isset($contact_info['client_id']) ? $contact_info['client_id'] : null)
                            ],
                            'links' => [
                                'mandate' => (isset($redirect_flow->api_response->body->redirect_flows->links->mandate)
                                    ? $redirect_flow->api_response->body->redirect_flows->links->mandate
                                    : null
                                )
                            ]
                        ]
                    ];
                    $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($params), 'input', true);
                    $payment = $api->payments()->create($params);

                    // Log the API response
                    $this->log(
                        (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
                        serialize($payment),
                        'output',
                        $this->getResponseStatus($subscription)
                    );

                    // Redirect to the return url
                    $return_url = $this->generateReturnUrl((isset($options['return_url']) ? $options['return_url'] : null), [
                        'payment_id' => (isset($payment->api_response->body->payments->id) ? $payment->api_response->body->payments->id : null)
                    ]);
                    $this->redirectToUrl($return_url);
                }
            } catch (\GoCardlessPro\Core\Exception\ApiException $e) {
                $this->Input->setErrors(
                    ['internal' => ['response' => $e->getMessage()]]
                );
            }
        } elseif (!empty($pay_type)) {
            // Build successful redirect url for the redirect flow
            $redirect_url = $this->generateReturnUrl(null, [
                'pay_type' => $pay_type
            ]);

            // Generate a new session token
            $this->Session->clear('gocardless_token');
            $session_token = 'SESS_' . base64_encode(md5(uniqid() . (isset($contact_info['client_id']) ? $contact_info['client_id'] : null)));
            $this->Session->write('gocardless_token', $session_token);

            // Create a new redirect flow
            try {
                $params = [
                    'params' => [
                        'description' => (isset($options['description']) ? $options['description'] : $company->name),
                        'session_token' => $session_token,
                        'success_redirect_url' => $redirect_url,
                        'prefilled_customer' => [
                            'given_name' => (isset($client->first_name) ? $client->first_name : null),
                            'family_name' => (isset($client->last_name) ? $client->last_name : null),
                            'email' => (isset($client->email) ? $client->email : null),
                            'address_line1' => (isset($client->address1) ? $client->address1 : null),
                            'address_line2' => (isset($client->address2) ? $client->address2 : null),
                            'city' => (isset($client->city) ? $client->city : null),
                            'region' => (isset($client->state) ? $client->state : null),
                            'postal_code' => (isset($client->zip) ? $client->zip : null),
                            'country_code' => (isset($client->country) ? $client->country : null),
                            'company_name' => (isset($client->company) ? $client->company : null)
                        ]
                    ]
                ];
                $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($params), 'input', true);
                $redirect_flow = $api->redirectFlows()->create($params);

                // Log the API response
                $this->log(
                    (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
                    serialize($redirect_flow),
                    'output',
                    $this->getResponseStatus($redirect_flow)
                );

                // Redirect to the authorization page
                $this->redirectToUrl($redirect_flow->redirect_url);
            } catch (\GoCardlessPro\Core\Exception\ApiException $e) {
                $this->Input->setErrors(
                    ['internal' => ['response' => $e->getMessage()]]
                );
            } catch (\GoCardlessPro\Core\Exception\GoCardlessProException $e) {
                $this->Input->setErrors(
                    ['internal' => ['response' => $e->getMessage()]]
                );
            }
        }

        return $this->buildForm($recurring);
    }

    /**
     * Builds the HTML form.
     *
     * @param bool $recurring True if this is a recurring payment request, false otherwise
     * @return string The HTML form
     */
    private function buildForm($recurring = false)
    {
        $this->view = $this->makeView('process', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

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
        // Get the request body posted by the GoCardless API
        $request = file_get_contents('php://input');

        // Validate the webhook call
        $signature_header = isset($_SERVER['HTTP_WEBHOOK_SIGNATURE']) ? $_SERVER['HTTP_WEBHOOK_SIGNATURE'] : '';

        // Load the GoCardless API
        $api = $this->getApi($this->meta['access_token'], $this->meta['dev_mode']);

        // Parse the webhook request
        try {
            $events = \GoCardlessPro\Webhook::parse($request, $signature_header, $this->meta['webhook_secret']);

            $event_fields = [];

            foreach ($events as $event) {
                $resource = rtrim($event->resource_type, 's');

                if (isset($event->links[$resource])) {
                    $event_fields[$resource . '_id'] = $event->links[$resource];
                }
                if (isset($event->details['cause'])) {
                    $event_fields[$resource . '_status'] = $event->details['cause'];
                }
            }
        } catch (\GoCardlessPro\Core\Exception\InvalidSignatureException $e) {
            $this->Input->setErrors(
                ['internal' => ['response' => $e->getMessage()]]
            );

            return;
        }

        // Get the payment details
        $payment_details = null;

        try {
            if (isset($event_fields['payment_id'])) {
                $payment = $api->payments()->get((isset($event_fields['payment_id']) ? $event_fields['payment_id'] : null));
                $payment_details = $payment->api_response->body->payments;
            }

            // Check if the payment it's associated to an active subscription
            if (isset($payment_details->links->subscription)) {
                $subscription = $api->subscriptions()->get($payment_details->links->subscription);
                $subscription_details = $subscription->api_response->body->subscriptions;
            }
        } catch (\GoCardlessPro\Core\Exception\ApiException $e) {
            $this->Input->setErrors(
                ['internal' => ['response' => $e->getMessage()]]
            );

            return;
        }

        // Log the API response
        $this->log(
            (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
            serialize($payment_details),
            'output',
            $this->getResponseStatus($payment)
        );

        // Capture the webhook status, or reject it if invalid
        $status = 'error';

        switch (strtolower((isset($event_fields['payment_status']) ? $event_fields['payment_status'] : null))) {
            case 'payment_submitted':
                $status = 'approved';
                break;
            case 'payment_confirmed':
                $status = 'approved';
                break;
            case 'payment_paid_out':
                $status = 'approved';
                break;
            case 'customer_approval_denied':
                $status = 'declined';
                break;
            case 'direct_debit_not_enabled':
                $status = 'declined';
                break;
            case 'invalid_bank_details':
                $status = 'declined';
                break;
            case 'payment_cancelled':
                $status = 'void';
                break;
            case 'subscription_cancelled':
                $status = 'void';
                break;
            case 'payment_created':
                $status = 'pending';
                break;
            case 'subscription_created':
                $status = 'pending';
                break;
            case 'customer_approval_granted':
                $status = 'pending';
                break;
            case 'payment_retried':
                $status = 'pending';
                break;
            case 'chargeback_settled':
                $status = 'refunded';
                break;
            case 'refund_requested':
                $status = 'refunded';
                break;
        }

        // Get client id
        $client_id = (isset($payment_details->metadata->client_id)
            ? $payment_details->metadata->client_id
            : (isset($subscription_details->metadata->client_id) ? $subscription_details->metadata->client_id : null)
        );

        // Get invoices
        $invoices = (isset($payment_details->metadata->invoices)
            ? $payment_details->metadata->invoices
            : (isset($subscription_details->metadata->invoices) ? $subscription_details->metadata->invoices : null)
        );

        // Force 2-decimal places only
        $amount = (isset($payment_details->amount) ? $payment_details->amount : 0) / 100;
        $amount = number_format($amount, 2, '.', '');

        return [
            'client_id' => $client_id,
            'amount' => $amount,
            'currency' => (isset($payment_details->currency) ? $payment_details->currency : null),
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => (isset($payment_details->id) ? $payment_details->id : null),
            'invoices' => $this->unserializeInvoices($invoices)
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
        // Load the GoCardless API
        $api = $this->getApi($this->meta['access_token'], $this->meta['dev_mode']);

        // Get the client id
        $client_id = (isset($get['client_id']) ? $get['client_id'] : null);

        // Check if the payment is one time or a subscription
        $pay_type = 'onetime';

        if (isset($get['subscription_id'])) {
            $pay_type = 'subscribe';
        }

        // Get the payment details
        $payment_details = null;

        $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($get), 'input', true);

        try {
            if ($pay_type == 'subscribe') {
                $payment = $api->subscriptions()->get((isset($get['subscription_id']) ? $get['subscription_id'] : null));
                $payment_details = $payment->api_response->body->subscriptions;
                $payment_details->id = null;
            } elseif ($pay_type == 'onetime') {
                $payment = $api->payments()->get((isset($get['payment_id']) ? $get['payment_id'] : null));
                $payment_details = $payment->api_response->body->payments;
            }

            // Log the API response
            $this->log(
                (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
                serialize($payment_details),
                'output',
                $this->getResponseStatus($payment)
            );
        } catch (\GoCardlessPro\Core\Exception\ApiException $e) {
            $this->Input->setErrors(
                ['internal' => ['response' => $e->getMessage()]]
            );

            return;
        }

        // Force 2-decimal places only
        $amount = (isset($payment_details->amount) ? $payment_details->amount : 0) / 100;
        $amount = number_format($amount, 2, '.', '');

        return [
            'client_id' => $client_id,
            'amount' => $amount,
            'currency' => (isset($payment_details->currency) ? $payment_details->currency : null),
            'invoices' => $this->unserializeInvoices((isset($payment_details->metadata->invoices) ? $payment_details->metadata->invoices : null)),
            'status' => 'approved', // we wouldn't be here if it weren't, right?
            'transaction_id' => (isset($payment_details->id) ? $payment_details->id : null),
        ];
    }

    /**
     * Captures a previously authorized payment.
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction.
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
        // Load the GoCardless API
        $api = $this->getApi($this->meta['access_token'], $this->meta['dev_mode']);

        // Get the payment details
        try {
            $payment = $api->payments()->get($transaction_id);
            $payment_details = $payment->api_response->body->payments;

            // Log the API response
            $this->log(
                (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
                serialize($payment_details),
                'output',
                $this->getResponseStatus($payment)
            );

            // Check if the payment it's associated to an active subscription
            if (isset($payment_details->links->subscription)) {
                $subscription = $api->subscriptions()->get($payment_details->links->subscription);
                $subscription_details = $subscription->api_response->body->subscriptions;
            }

            // Log the API response
            $this->log(
                (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
                serialize($subscription_details),
                'output',
                $this->getResponseStatus($subscription)
            );

            // Cancel active subscription
            if (isset($subscription_details->id)) {
                $cancel = $api->subscriptions()->cancel($subscription_details->id);
            }

            return [
                'status' => 'void',
                'transaction_id' => (isset($transaction_id) ? $transaction_id : null)
            ];
        } catch (\GoCardlessPro\Core\Exception\ApiException $e) {
            $this->Input->setErrors(
                ['internal' => ['response' => $e->getMessage()]]
            );

            return;
        }
    }

    /**
     * Refund a payment.
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
        // Load the GoCardless API
        $api = $this->getApi($this->meta['access_token'], $this->meta['dev_mode']);

        // Force 2-decimal places only
        $amount = number_format($amount, 2, '.', '');

        // Process the refund (only one-time payments can be refunded)
        if (substr((isset($transaction_id) ? $transaction_id : null), 0, 2) == 'PM') {
            $params = [
                'params' => [
                    'amount' => (isset($amount) ? $amount : null) * 100,
                    'total_amount_confirmation' => (isset($amount) ? $amount : null) * 100,
                    'reference' => (isset($reference_id) ? $reference_id : null),
                    'links' => [
                        'payment' => (isset($transaction_id) ? $transaction_id : null)
                    ]
                ]
            ];

            try {
                $refund = $api->refunds()->create($params);

                if (!$this->getResponseStatus($refund)) {
                    $this->Input->setErrors($this->getCommonError('general'));

                    return;
                }

                // Log the successful response
                $this->log(
                    (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
                    serialize($refund),
                    'output',
                    $this->getResponseStatus($refund)
                );

                return [
                    'status' => 'refunded',
                    'transaction_id' => (isset($refund->api_response->body->refunds->id) ? $refund->api_response->body->refunds->id : null),
                ];
            } catch (\GoCardlessPro\Core\Exception\ApiException $e) {
                $this->Input->setErrors(
                    ['internal' => ['response' => $e->getMessage()]]
                );

                return;
            }
        } else {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }
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

    /**
     * Initializes the GoCardless API and returns an instance of that object with the given account information set.
     *
     * @param string $access_token The account access token
     * @param string $dev_mode Post transactions to the GoCardless Sandbox environment
     * @return GoCardlessPro A GoCardlessPro instance
     */
    private function getApi($access_token, $dev_mode = 'false')
    {
        if ($dev_mode == 'true') {
            $environment = \GoCardlessPro\Environment::SANDBOX;
        } elseif ($dev_mode == 'false') {
            $environment = \GoCardlessPro\Environment::LIVE;
        }

        return new \GoCardlessPro\Client([
            'access_token' => (isset($access_token) ? $access_token : null),
            'environment' => $environment
        ]);
    }

    /**
     * Generates a return url.
     *
     * @param string $return_url The return url, if no return url is provided, the current one will be used
     * @param array $params The GET parameters that will be added at the end of the url
     * @return string The formatted return url
     */
    private function generateReturnUrl($return_url = null, $params = [])
    {
        if (is_null($return_url)) {
            $return_url = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '')
                . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }

        if (!empty($params)) {
            $query = (strpos($return_url, '?') !== false ? '&' : '?') . http_build_query($params);
            $return_url = $return_url . $query;
        }

        return $return_url;
    }

    /**
     * Generates a redirect to the specified url.
     *
     * @param string $url The url to be redirected
     * @return bool True if the redirection was successful, false otherwise
     */
    private function redirectToUrl($url)
    {
        try {
            header('Location: ' . $url);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Returns the status of the API response.
     *
     * @param GoCardlessPro &$api_response The response of the api
     * @return bool True if the api response did not return any errors, false otherwise
     */
    private function getResponseStatus(&$api_response)
    {
        $status = false;

        if ((isset($api_response->api_response->status_code) ? $api_response->api_response->status_code : null) >= 200
            && (isset($api_response->api_response->status_code) ? $api_response->api_response->status_code : null) < 300
        ) {
            $status = true;
        }

        return $status;
    }
}
