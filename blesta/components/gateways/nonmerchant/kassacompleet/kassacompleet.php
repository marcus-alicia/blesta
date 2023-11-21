<?php
/**
 * Kassa Compleet Gateway.
 *
 * @package blesta
 * @subpackage blesta.components.gateways.kassacompleet
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Kassacompleet extends NonmerchantGateway
{
    /**
     * Construct a new non-merchant gateway.
     */
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this gateway
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this gateway
        Language::loadLang('kassacompleet', null, dirname(__FILE__) . DS . 'language' . DS);
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
        $that = $this;

        // Verify meta data is valid
        $rules = [
            'api_key' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Kassacompleet.!error.api_key.empty', true)
                ],
                'valid' => [
                    'rule' => function ($api_key) use ($that) {
                        // Verify that we can make a successful request using the API
                        $api = $that->getApi($api_key);
                        $result = $api->getIssuers();

                        return $result->errors() == '';
                    },
                    'message' => Language::_('Kassacompleet.!error.api_key.valid', true)
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
        return ['api_key'];
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
        Loader::loadModels($this, ['Clients']);

        if (!empty($_POST)) {
            // Load the Kassa Compleet API
            $api = $this->getApi($this->meta['api_key']);

            $params = [
                'amount' => $amount * 100, // Kassa Compleet processes values in cents
                'currency' => $this->currency,
                'description' => $options['description'],
                'extra' => (object)[
                    'client_id' => $contact_info['client_id'],
                    'invoices' => $this->serializeInvoices($invoice_amounts)
                ],
                'return_url' => $options['return_url'],
                'webhook_url' => Configure::get('Blesta.gw_callback_url')
                    . Configure::get('Blesta.company_id') . '/kassacompleet/',
                'transactions' => [(object)['payment_method' => 'credit-card']]
            ];


            // Log data sent for order creation
            $this->log(
                'createorder',
                json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'input',
                true
            );

            // Create an order in Kassa Compleet
            $result = $api->createOrder($params);

            // Log post-back sent (an order object on success)
            $this->log(
                'createorder',
                json_encode($result->response(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'output',
                $result->errors() == ''
            );

            // Return null on error
            if ($result->errors()) {
                return null;
            }

            $data = $result->response();

            // Get the url to redirect the client to
            $transaction = isset($data->transactions) ? (isset($data->transactions[0]) ? $data->transactions[0] : null) : null;
            $kassacompleet_url = (isset($transaction->payment_url) ? $transaction->payment_url : null);

            // Redirect the use to Kassa Compleet to finish payment
            $this->redirectToUrl($kassacompleet_url);
        }

        return $this->buildForm();
    }

    /**
     * Builds the HTML form.
     *
     * @return string The HTML form
     */
    private function buildForm()
    {
        $this->view = $this->makeView('process', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));

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
     *  - parent_transaction_id The ID returned by the gateway to identify this transaction's
     *      original transaction (in the case of refunds)
     */
    public function validate(array $get, array $post)
    {
        // Load the Kassa Compleet API
        $api = $this->getApi($this->meta['api_key']);
        $callback_data = json_decode(@file_get_contents('php://input'));

        // Log request received
        $this->log(
            (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
            json_encode(
                $callback_data,
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            ),
            'output',
            true
        );

        // Log data sent for validation
        $this->log(
            'validate',
            json_encode(
                ['order_id' => $callback_data ? (isset($callback_data->order_id) ? $callback_data->order_id : null) : ''],
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            ),
            'input',
            true
        );

        $result = $api->getOrder($callback_data ? (isset($callback_data->order_id) ? $callback_data->order_id : null) : '');
        $data = $result->response();

        // Log post-back sent
        $this->log(
            'validate',
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'output',
            $result->errors() == ''
        );

        // Get status from the order
        $status = $this->mapStatus((isset($data->status) ? $data->status : 'error'));
        if ($status == 'see_transactions') {
            // If necessary pull the status from the transactions
            $status = (isset($data->transactions) ? $data->transactions : []) ? 'approved' : 'error';
            $status_weights = $this->getStatusWeights();

            // Use the lowest value status among the transactions
            foreach ((isset($data->transactions) ? $data->transactions : []) as $transaction) {
                $transaction_status = $this->mapStatus($transaction->status);
                if ((isset($status_weights[$transaction_status]) ? $status_weights[$transaction_status] : 100) < $status_weights[$status]) {
                    $status = $transaction_status;
                }
            }
        }


        return [
            'client_id' => (isset($data->extra->client_id) ? $data->extra->client_id : null),
            'amount' => (isset($data->amount) ? $data->amount : 0) / 100, // Kassa Compleet stores amounts in cents
            'currency' => (isset($data->currency) ? $data->currency : null),
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => (isset($data->id) ? $data->id : null),
            'invoices' => $this->unserializeInvoices((isset($data->extra->invoices) ? $data->extra->invoices : null))
        ];
    }

    /**
     * Maps a status from by Kassa Compleet to one in blesta
     *
     * @param string $status The status from Kassa Compleet
     * @return string The Blesta status
     */
    private function mapStatus($status)
    {
        switch ($status) {
            case 'new':
            case 'processing':
                return 'pending';
            case 'completed':
                return 'approved';
            case 'cancelled':
                return 'void';
            case 'expired':
                return 'error';
            case 'see-transactions':
                return 'see_transactions';
            case 'error':
            default:
                return'declined';
        }
    }

    /**
     * Gets a list of blesta transaction statuses and weights by which to compare them
     *
     * @return array A list of statuses and their weights
     */
    private function getStatusWeights()
    {
        return [
            'error' => 0,
            'declined' => 20,
            'cancelled' => 40,
            'pending' => 60,
            'approved' => 80,
            'see_transactions' => 100
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
        // Load the Kassa Compleet API
        $api = $this->getApi($this->meta['api_key']);

        // Get transaction data
        $result = $api->getOrder((isset($get['order_id']) ? $get['order_id'] : null));
        $data = $result->response();

        return [
            'client_id' => (isset($data->extra->client_id) ? $data->extra->client_id : null),
            'amount' => (isset($data->amount) ? $data->amount : 0) / 100, // Kassa Compleet stores amounts in cents
            'currency' => (isset($data->currency) ? $data->currency : null),
            'status' => 'approved', // we wouldn't be here if it weren't, right?
            'transaction_id' => (isset($data->id) ? $data->id : null),
            'invoices' => $this->unserializeInvoices((isset($data->extra->invoices) ? $data->extra->invoices : null))
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
     *  - message The message to be displayed in the interface in addition to the standard message for
     *    this transaction status (optional)
     */
    public function refund($reference_id, $transaction_id, $amount, $notes = null)
    {
        $params = [
            'amount' => $amount * 100, // Kassa Compleet stores amounts in cents
            'description' => $notes
        ];

        // Log data sent for validation
        $this->log(
            'refundorder',
            json_encode(['order_id' => $transaction_id] + $params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'input',
            true
        );

        // Load the Kassa Compleet API
        $api = $this->getApi($this->meta['api_key']);

        // Refund the order
        $result = $api->refundOrder($transaction_id, $params);

        // Log post-back sent (a refund order on success)
        $this->log(
            'refundorder',
            json_encode($result->response(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'output',
            $result->errors() == ''
        );

        if ($result->errors()) {
            $this->Input->setErrors(
                ['internal' => ['response' => $result->errors()]]
            );
            return;
        }

        return [
            'status' => 'refunded',
            'transaction_id' => null
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

    /**
     * Initializes the Kassa Compleet API
     *
     * @param string $api_key The Kassa Compleet webshop API key
     * @return KassacompleetApi
     */
    private function getApi($api_key)
    {
        // Load library methods
        Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'kassacompleet_api.php');

        return new KassacompleetApi($api_key);
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
}
