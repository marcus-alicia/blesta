<?php
/**
 * Razorpay
 *
 * Razorpay Webhook reference:
 * https://razorpay.com/docs/webhooks/
 * Razorpay API reference:
 * https://razorpay.com/docs/api/
 *
 * @package blesta
 * @subpackage blesta.components.gateways.razorpay
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Razorpay extends NonmerchantGateway
{
    /**
     * @var array An array of meta data for this gateway
     */
    private $meta;
    /**
     * @var string The URL to post payments to
     */
    private $razorpay_url = 'https://api.razorpay.com/v1/checkout/embedded';

    /**
     * Construct a new merchant gateway
     */
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this gateway
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this gateway
        Language::loadLang('razorpay', null, dirname(__FILE__) . DS . 'language' . DS);
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
            'key_id' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Razorpay.!error.key_id.valid', true)
                ]
            ],
            'key_secret' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Razorpay.!error.key_secret.valid', true)
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
        return ['key_id', 'key_secret', 'webhook_secret'];
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
     *
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
     *
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @param array $options An array of options including:
     *
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

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Html']);

        // Load library methods
        Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'Razorpay.php');
        $api = new Razorpay\Api\Api($this->meta['key_id'], $this->meta['key_secret']);

        // Force 2-decimal places only
        $amount = number_format($amount, 2, '.', '');

        // Set all invoices to pay
        if (isset($invoice_amounts) && is_array($invoice_amounts)) {
            $invoices = $this->serializeInvoices($invoice_amounts);
        }

        // Generate an order
        $fields = [
            'payment_capture' => 1,
            'amount' => $amount * 100,
            'currency' => $this->currency
        ];

        try {
            $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($fields), 'input', true);

            $order = $api->order->create($fields);

            // Set the order custom fields
            $api->request->request('PATCH', 'orders/' . $order->id, [
                'notes' => [
                    'invoice_amounts' => (isset($invoices) ? $invoices : null),
                    'client_id' => (isset($contact_info['client_id']) ? $contact_info['client_id'] : null)
                ]
            ]);

            // Log the API response
            $this->log(
                (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
                serialize($order),
                'output',
                isset($order->id)
            );
        } catch (Razorpay\Api\Errors\Error $e) {
            $this->Input->setErrors(
                ['error' => ['message' => $e->getMessage()]]
            );

            return null;
        }

        // Get the company name
        $company = $this->Companies->get(Configure::get('Blesta.company_id'));

        // Get client data
        $client = $this->Clients->get($contact_info['client_id']);

        // An array of key/value hidden fields to set for the payment form
        $fields = [
            'key_id' => $this->meta['key_id'],
            'name' => $company->name,
            'description' => (isset($options['description']) ? $options['description'] : null),
            'order_id' => $order->id,
            'amount' => $amount * 100,
            'currency' => $this->currency,
            'prefill' => [
                'name' => $this->Html->concat(
                    ' ',
                    (isset($contact_info['first_name']) ? $contact_info['first_name'] : null),
                    (isset($contact_info['last_name']) ? $contact_info['last_name'] : null)
                ),
                'email' => $client->email
            ],
            'notes' => [
                'invoice_amounts' => (isset($invoices) ? $invoices : null),
                'client_id' => (isset($contact_info['client_id']) ? $contact_info['client_id'] : null)
            ],
            'callback_url' => (isset($options['return_url']) ? $options['return_url'] : null)
        ];

        // Set contact phone number
        if ((isset($contact_info['id']) ? $contact_info['id'] : false)) {
            Loader::loadModels($this, ['Contacts']);

            if (($contact = $this->Contacts->get($contact_info['id']))) {
                // Set a phone number, if one exists
                $contact_numbers = $this->Contacts->getNumbers($contact_info['id'], 'phone');
                if (isset($contact_numbers[0]) && !empty($contact_numbers[0]->number)) {
                    $fields['prefill']['contact'] = preg_replace('/[^0-9]/', '', $contact_numbers[0]->number);
                } else {
                    $fields['prefill']['contact'] = '11111111111';
                }
            }
        }

        return $this->buildForm($this->razorpay_url, $fields);
    }

    /**
     * Builds the HTML form
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
     *
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
        // Get the request body posted by the webhook
        $webhook = file_get_contents('php://input');

        if (!empty($webhook)) {
            $vars = json_decode($webhook);

            // Log the API response
            $this->log(
                (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
                serialize($vars),
                'output',
                json_last_error() === JSON_ERROR_NONE
            );
        } else {
            return null;
        }

        // Fetch client id
        $client_id = (isset($vars->payload->order->entity->notes->client_id) ? $vars->payload->order->entity->notes->client_id : null);

        // Verify webhook signature
        $headers = getallheaders();
        $status = 'error';

        $signature = (isset($headers['X-Razorpay-Signature']) ? $headers['X-Razorpay-Signature'] : null);
        $expected_signature = hash_hmac(
            'sha256',
            trim($webhook),
            $this->meta['webhook_secret']
        );

        if ($expected_signature === $signature) {
            // Set transaction status
            switch ($vars->payload->payment->entity->status) {
                case 'captured':
                    $status = 'approved';
                    break;
                case 'created':
                case 'authorized':
                    $status = 'pending';
                    break;
                case 'refunded':
                    $status = 'refunded';
                    break;
                default:
                    $status = 'declined';
                    break;
            }

            // Force 2-decimal places only
            $amount = number_format(($vars->payload->order->entity->amount_paid / 100), 2, '.', '');

            // Fetch order currency
            $currency = $vars->payload->order->entity->currency;

            // Fetch serialized invoices
            $invoices = $vars->payload->order->entity->notes->invoice_amounts;
        }

        return [
            'client_id' => $client_id,
            'amount' => (isset($amount) ? $amount : null),
            'currency' => (isset($currency) ? $currency : null),
            'status' => $status,
            'reference_id' => (isset($vars->payload->payment->entity->email) ? $vars->payload->payment->entity->email : null),
            'transaction_id' => (isset($vars->payload->payment->entity->order_id) ? $vars->payload->payment->entity->order_id : null),
            'invoices' => $this->unserializeInvoices((isset($invoices) ? $invoices : null))
        ];
    }

    /**
     * Returns data regarding a success transaction. This method is invoked when
     * a client returns from the non-merchant gateway's web site back to Blesta.
     *
     * @param array $get The GET data for this request
     * @param array $post The POST data for this request
     * @return array An array of transaction data, may set errors using Input if the data appears invalid
     *
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
        // Load library methods
        Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'Razorpay.php');
        $api = new Razorpay\Api\Api($this->meta['key_id'], $this->meta['key_secret']);

        $client_id = (isset($get['client_id']) ? $get['client_id'] : null);

        // Fetch order
        if (isset($post['razorpay_order_id'])) {
            try {
                $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($post), 'input', true);

                $order = $api->order->fetch($post['razorpay_order_id']);

                // Log the API response
                $this->log(
                    (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
                    serialize($order),
                    'output',
                    isset($order->id)
                );
            } catch (Razorpay\Api\Errors\Error $e) {
                $this->Input->setErrors(
                    ['error' => ['message' => $e->getMessage()]]
                );

                return null;
            }

            // Force 2-decimal places only
            $amount = number_format(($order->amount / 100), 2, '.', '');

            // Fetch order currency
            $currency = $order->currency;

            // Fetch serialized invoices
            $invoices = $order->notes->invoice_amounts;
        }

        // Check if an error has been returned
        if (isset($post['error'])) {
            $this->Input->setErrors(
                ['error' => ['message' => (isset($post['error']['description']) ? $post['error']['description'] : null)]]
            );

            // Log the API response
            $this->log(
                (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
                serialize($post),
                'output',
                false
            );
        }

        // Validate order signature
        $status = 'declined';

        if (isset($post['razorpay_order_id'])) {
            $signature = $api->utility->verifyPaymentSignature(
                [
                    'razorpay_signature' => (isset($post['razorpay_signature']) ? $post['razorpay_signature'] : null),
                    'razorpay_payment_id' => (isset($post['razorpay_payment_id']) ? $post['razorpay_payment_id'] : null),
                    'razorpay_order_id' => (isset($post['razorpay_order_id']) ? $post['razorpay_order_id'] : null)
                ]
            );

            if ($signature) {
                $status = 'approved';
            }
        }

        if (($errors = $this->Input->errors()) && empty($errors) && $status !== 'approved') {
            $this->Input->setErrors($this->getCommonError('invalid'));
        }

        return [
            'client_id' => $client_id,
            'amount' => (isset($amount) ? $amount : null),
            'currency' => (isset($currency) ? $currency : null),
            'invoices' => $this->unserializeInvoices((isset($invoices) ? $invoices : null)),
            'status' => $status,
            'transaction_id' => (isset($post['razorpay_order_id']) ? $post['razorpay_order_id'] : null)
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
     *
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function refund($reference_id, $transaction_id, $amount, $notes = null)
    {
        // Load library methods
        Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'Razorpay.php');
        $api = new Razorpay\Api\Api($this->meta['key_id'], $this->meta['key_secret']);

        // Fetch order payments
        try {
            $this->log(
                (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
                serialize(compact('reference_id', 'transaction_id', 'amount')),
                'input',
                true
             );

            $order_payments = $api->order->fetch($transaction_id)->payments();

            // Log the API response
            $this->log(
                (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
                serialize($order_payments),
                'output',
                !empty($order_payments)
            );
        } catch (Razorpay\Api\Errors\Error $e) {
            $this->Input->setErrors(
                ['error' => ['message' => $e->getMessage()]]
            );

            return null;
        }

        // Refund all payments
        foreach ($order_payments->items as $payment) {
            try {
                $payment->refund();
            } catch (Razorpay\Api\Errors\Error $e) {
                $this->Input->setErrors(
                    ['error' => ['message' => $e->getMessage()]]
                );

                return null;
            }
        }

        return [
            'status' => 'refunded',
            'transaction_id' => $transaction_id,
        ];
    }

    /**
     * Serializes an array of invoice info into a string
     *
     * @param array A numerically indexed array invoices info including:
     *
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
     *
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
