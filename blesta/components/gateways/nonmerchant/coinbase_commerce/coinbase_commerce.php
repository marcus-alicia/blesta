<?php
/**
 * Coinbase Commerce Gateway
 *
 * Allows users to pay via Cryptocurrency
 *
 * @package blesta
 * @subpackage blesta.components.gateways.nonmerchant.coinbase_commerce
 * @author Phillips Data, Inc.
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class CoinbaseCommerce extends NonmerchantGateway
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
        // Load the Coinbase Commerce API
        Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'coinbase_commerce_api.php');

        // Load configuration required by this gateway
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this gateway
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this gateway
        Language::loadLang('coinbase_commerce', null, dirname(__FILE__) . DS . 'language' . DS);
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
        $this->view->setDefaultView(
            'components' . DS . 'gateways' . DS . 'nonmerchant' . DS . 'coinbase_commerce' . DS
        );

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
            'api_key' => [
                'valid' => [
                    'rule' => [[$this, 'validateConnection']],
                    'message' => Language::_('CoinbaseCommerce.!error.api_key.valid', true)
                ]
            ],
            'webhook_secret' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('CoinbaseCommerce.!error.webhook_secret.valid', true)
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
        return ['api_key', 'webhook_secret'];
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
        Loader::loadModels($this, ['Companies']);

        // Force 2-decimal places only
        $amount = round($amount, 2);
        if (isset($options['recur']['amount'])) {
            $options['recur']['amount'] = round($options['recur']['amount'], 2);
        }

        // Get currency
        $currency = ($this->currency ?? null);

        // Get company information
        $company = $this->Companies->get(Configure::get('Blesta.company_id'));

        // Set all invoices to pay
        if (isset($invoice_amounts) && is_array($invoice_amounts)) {
            $invoices = $this->serializeInvoices($invoice_amounts);
        }

        $this->view = $this->makeView('process', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Initialize Coinbase Commerce API
        $api = $this->getApi();
        $charges = new CoinbaseCommerceCharges($api);

        // Generate payment
        $payment = [
            'name' => ($company->name ?? null),
            'description' => ($options['description'] ?? null),
            'pricing_type' => 'fixed_price',
            'local_price' => [
                'amount' => $amount,
                'currency' => $currency
            ],
            'metadata' => [
                'customer_id' => ($contact_info['client_id'] ?? null),
                'customer_name' => ($contact_info['first_name'] ?? '') . ' ' . ($contact_info['last_name'] ?? ''),
                'invoices' => $invoices
            ],
            'redirect_url' => ($options['return_url'] ?? null),
            'cancel_url' => ($options['return_url'] ?? null)
        ];
        $charge = $charges->charge($payment)->response();

        $this->log(($_SERVER['REQUEST_URI'] ?? null), serialize($payment), 'input', true);
        $this->log(($_SERVER['REQUEST_URI'] ?? null), serialize($charge), 'output', isset($charge->data));

        // Save the current payment on session for future validation
        Loader::loadComponents($this, ['Session']);

        $this->Session->clear('blesta_coinbase_id');
        $this->Session->write('blesta_coinbase_id', $charge->data->code ?? null);

        $this->view->set('post_to', $charge->data->hosted_url ?? null);

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
        // Initialize API
        $api = $this->getApi();
        $charges = new CoinbaseCommerceCharges($api);

        $statuses = [
            'new' => 'pending',
            'pending' => 'pending',
            'unresolved' => 'pending',
            'resolved' => 'approved',
            'completed' => 'approved',
            'pending_refund' => 'approved',
            'expired' => 'error',
            'canceled' => 'void',
            'refunded' => 'refunded',
        ];

        // Fetch signature from webhook
        $signature = $_SERVER['HTTP_X_CC_WEBHOOK_SIGNATURE'] ?? $_SERVER['HTTP_CB_SIGNATURE'] ?? null;

        if (is_null($signature)) {
            return;
        }

        // Fetch webhook payload
        $payload = file_get_contents('php://input');
        $data = json_decode($payload);

        // Calculate payload signature
        $calculated_signature = hash_hmac('sha256', $payload, $this->meta['webhook_secret']);

        // Validate webhook payload
        $success = false;
        if ($calculated_signature === $signature) {
            $charge = $charges->get(['id' => $data->event->data->code])->response();

            if (!empty($charge)) {
                $success = true;
            }
        }

        // Validate response
        $status = 'error';
        $amount = null;
        $currency = null;

        if (!empty($charge->data->payments)) {
            foreach ($charge->data->payments as $payment) {
                if (is_null($amount)) {
                    $amount = 0;
                }

                $amount = $amount + ($payment->value->local->amount ?? $payment->net->local->amount ?? 0);
                $currency = $payment->value->local->currency ?? $payment->net->local->currency ?? null;
            }

            if (is_null($amount) && isset($charge->data->pricing->local)) {
                $amount = $charge->data->pricing->local->amount ?? null;
            }

            if (is_null($currency) && isset($charge->data->pricing->local)) {
                $currency = $charge->data->pricing->local->currency ?? null;
            }

            if (is_null($currency) || is_null($amount)) {
                return;
            }

            // Get payment status
            $last_timeline = end($charge->data->timeline);
            reset($charge->data->timeline);
            $status = $statuses[strtolower($last_timeline->status ?? 'PENDING')] ?? $status;

            if ($amount > 0 && $status == 'error') {
                $status = 'approved';
            }
        } else {
            return;
        }

        // Validate the webhook contains a valid client id
        Loader::loadModels($this, ['Clients']);
        if (!$this->Clients->validateExists($charge->data->metadata->customer_id, 'id', 'clients')) {
            $success = false;
        }

        // Log the response
        $this->log(($_SERVER['REQUEST_URI'] ?? null), serialize($charge), 'output', $success);

        if (!$success) {
            return;
        }

        return [
            'client_id' => ($charge->data->metadata->customer_id ?? null),
            'amount' => $amount,
            'currency' => $currency,
            'invoices' => $this->unserializeInvoices($charge->data->metadata->invoices ?? null),
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => $data->event->data->code ?? null,
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
        // Initialize API
        $api = $this->getApi();
        $amount = null;
        $currency = null;

        // Fetch the current payment on session
        Loader::loadComponents($this, ['Session']);
        $charge_id = $this->Session->read('blesta_coinbase_id');

        $statuses = [
            'new' => 'pending',
            'pending' => 'pending',
            'unresolved' => 'pending',
            'resolved' => 'approved',
            'completed' => 'approved',
            'pending_refund' => 'approved',
            'expired' => 'error',
            'canceled' => 'void',
            'refunded' => 'refunded',
        ];
        $status = 'pending';
        $success = false;
        if (!empty($charge_id)) {
            $charges = new CoinbaseCommerceCharges($api);
            $charge = $charges->get(['id' => $charge_id])->response();

            // Calculate amount
            if (!empty($charge->data->payments)) {
                $success = true;
                foreach ($charge->data->payments as $payment) {
                    if (is_null($amount)) {
                        $amount = 0;
                    }

                    $amount = $amount + ($payment->value->local->amount ?? $payment->net->local->amount ?? 0);
                    $currency = $payment->value->local->currency ?? $payment->net->local->currency ?? null;
                }
            }

            if (is_null($amount) && isset($charge->data->pricing->local)) {
                $amount = $charge->data->pricing->local->amount ?? null;
            }

            if (is_null($currency) && isset($charge->data->pricing->local)) {
                $currency = $charge->data->pricing->local->currency ?? null;
            }

            if (is_null($currency) || is_null($amount)) {
                return;
            }

            // Get payment status
            $last_timeline = end($charge->data->timeline);
            reset($charge->data->timeline);
            $status = $statuses[strtolower($last_timeline->status ?? 'PENDING')] ?? $status;
        }

        if (!$success) {
            return;
        }

        return [
            'client_id' => ($charge->data->metadata->customer_id ?? null),
            'amount' => $amount,
            'currency' => $currency,
            'invoices' => $this->unserializeInvoices($charge->data->metadata->invoices ?? null),
            'status' => $status,
            'transaction_id' => $charge_id,
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
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function refund($reference_id, $transaction_id, $amount, $notes = null)
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
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }

    /**
     * Loads the given API if not already loaded
     */
    private function getApi()
    {
        return new CoinbaseCommerceApi($this->meta['api_key']);
    }

    /**
     * Validates if the provided API Key is valid
     *
     * @param string $api_key The Coinbase Commerce API Key
     * @return bool True if the API Key is valid, false otherwise
     */
    public function validateConnection($api_key)
    {
        $this->meta['api_key'] = $api_key;

        $api = $this->getApi();
        $request = $api->submit('/charges', [
            'name' => 'Blesta',
            'description' => 'Blesta',
            'pricing_type' => 'fixed_price',
            'local_price' => [
                'amount' => 50,
                'currency' => 'USD'
            ]
        ]);
        $response = $request->response();

        return !empty($response->data);
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

        return base64_encode($str);
    }

    /**
     * Unserializes a string of invoice info into an array.
     *
     * @param string $str A serialized string of invoice info in the format of key1=value1|key2=value2
     * @return array A numerically indexed array invoices info including:
     *  - id The ID of the invoice
     *  - amount The amount relating to the invoice
     */
    private function unserializeInvoices($str)
    {
        if (empty($str)) {
            return null;
        }

        $str = base64_decode($str);

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
