<?php
use Blesta\Core\Util\Common\Traits\Container;
use BTCPayServer\Client\InvoiceCheckoutOptions;
use BTCPayServer\Util\PreciseNumber;

/**
 * BTCPay Server Gateway
 *
 * Allows users to pay via Bitcoin
 *
 * @package blesta
 * @subpackage blesta.components.gateways.nonmerchant.btcpay_server
 * @author Phillips Data, Inc.
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class BtcpayServer extends NonmerchantGateway
{
    // Load traits
    use Container;

    /**
     * @var array An array of meta data for this gateway
     */
    private $meta;

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
        Language::loadLang('btcpay_server', null, dirname(__FILE__) . DS . 'language' . DS);

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
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
        $this->view->setDefaultView('components' . DS . 'gateways' . DS . 'nonmerchant' . DS . 'btcpay_server' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Load the models required for this view
        Loader::loadModels($this, ['GatewayManager']);

        $select_options = [
            'high' => Language::_('BtcpayServer.transaction.speed.high', true),
            'medium' => Language::_('BtcpayServer.transaction.speed.medium', true),
            'low' => Language::_('BtcpayServer.transaction.speed.low', true)
        ];
        $this->view->set('meta', $meta);
        $this->view->set('select_options', $select_options);

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
        if (!isset($meta['disconnect'])) {
            $rules = [
                'server_url' => [
                    'valid' => [
                        'rule' => function ($server_url) {
                            return filter_var($server_url, FILTER_VALIDATE_URL);
                        },
                        'message' => Language::_('BtcpayServer.!error.server_url.valid', true)
                    ]
                ],
                'transaction_speed' => [
                    'valid' => [
                        'rule' => ['in_array', ['high', 'medium', 'low']],
                        'message' => Language::_('BtcpayServer.!error.transaction_speed.valid', true)
                    ]
                ],
                'store_id' => [
                    'valid' => [
                        'rule' => function ($store_id) use ($meta) {
                            try {
                                $api = $this->getApi($meta);
                                $store = $api->Store->getStore($store_id);

                                return !empty($store);
                            } catch (Throwable $e) {
                                return false;
                            }
                        },
                        'message' => Language::_('BtcpayServer.!error.store_id.valid', true)
                    ]
                ],
                'api_key' => [
                    'valid' => [
                        'rule' => function ($api_key) use ($meta) {
                            try {
                                $api = $this->getApi($meta);
                                $stores = $api->Store->getStores();

                                return !empty($stores);
                            } catch (Throwable $e) {
                                return false;
                            }
                        },
                        'message' => Language::_('BtcpayServer.!error.api_key.valid', true)
                    ]
                ],
                'webhook_secret' => [
                    'valid' => [
                        'rule' => 'isEmpty',
                        'negate' => true,
                        'message' => Language::_('BtcpayServer.!error.webhook_secret.valid', true)
                    ]
                ]
            ];

            $this->Input->setRules($rules);

            // Validate the given meta data to ensure it meets the requirements
            $this->Input->validates($meta);
        }

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
        return ['api_key'];
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
        // Load the view into this object, so helpers can be automatically add to the view
        $this->view = new View('process', 'default');
        $this->view->setDefaultView('components' . DS . 'gateways' . DS . 'nonmerchant' . DS . 'btcpay_server' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Load the models required for this view
        Loader::loadModels($this, ['Clients']);

        // Initialize API
        $api = $this->getApi($this->meta);

        // Get client
        $client = $this->Clients->get($contact_info['client_id']);

        // Set amount and currency
        $amount = round($amount, 2); // Force 2-decimal places only
        $currency = ($this->currency ?? null);

        // Set redirect url
        $redirect_url = ($options['return_url'] ?? null);
        $query = parse_url($redirect_url, PHP_URL_QUERY);
        $pos_data = $this->serializeInvoices($invoice_amounts);

        if ($query) {
            $redirect_url .= '&';
        } else {
            $redirect_url .= '?';
        }

        $redirect_url .= 'price=' . $amount . '&posData=' . $pos_data . '&currency=' . $currency;

        // Set invoice options and speed policy
        $invoice_options = new InvoiceCheckoutOptions();
        $speed_policy = $invoice_options::SPEED_HIGH;
        switch ($this->meta['transaction_speed'] ?? null) {
            case 'high':
                $speed_policy = $invoice_options::SPEED_HIGH;
                break;
            case 'medium':
                $speed_policy = $invoice_options::SPEED_MEDIUM;
                break;
            case 'low':
                $speed_policy = $invoice_options::SPEED_LOW;
                break;
        }
        $invoice_options->setSpeedPolicy($speed_policy)
            ->setPaymentMethods(['BTC'])
            ->setRedirectURL($redirect_url);

        // Create invoice
        try {
            $order_id = ($contact_info['client_id'] ?? null) . '-' . time();
            $params = [
                'posData' => $pos_data,
                'itemDesc' => ($options['description'] ?? null),
                'physical' => false,
                'buyerName' => ($contact_info['first_name'] ?? '') . ' ' . ($contact_info['last_name'] ?? ''),
                'buyerAddress1' => ($contact_info['address1'] ?? ''),
                'buyerAddress2' => ($contact_info['address2'] ?? ''),
                'buyerCity' => ($contact_info['city'] ?? ''),
                'buyerState' => ($contact_info['state']['name'] ?? ''),
                'buyerZip' => ($contact_info['zip'] ?? ''),
                'buyerCountry' => ($contact_info['country']['name'] ?? '')
            ];

            $this->log(rtrim($this->meta['server_url'], '/') . '/' . __FUNCTION__, serialize($params), 'input', true);

            $invoice = $api->Invoice->createInvoice(
                ($this->meta['store_id'] ?? null),
                $currency,
                PreciseNumber::parseString($amount),
                $order_id,
                ($client->email ?? null),
                $params,
                $invoice_options
            );

            $this->log(rtrim($this->meta['server_url'], '/') . '/' . __FUNCTION__, serialize($invoice), 'output', !empty($invoice));
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage());
            $this->log(rtrim($this->meta['server_url'], '/') . '/' . __FUNCTION__, serialize($e), 'output', false);
            $this->Input->setErrors([strtolower(__FUNCTION__) => ['response' => $e->getMessage()]]);

            return;
        }

        $this->view->set('server_url', $this->meta['server_url'] ?? '');
        $this->view->set('invoice_url', $invoice->getCheckoutLink());

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
        // Validates response and get status of payment
        $response = $this->getIpn();
        if (is_scalar($response) || empty($response)) {
            $this->logger->error($response);
            $this->Input->setErrors($this->getCommonError('invalid'));
        }

        // Initialize API
        $api = $this->getApi($this->meta);

        // Validate webhook
        if (($response['type'] ?? '') == 'InvoiceSettled') {
            $invoice = $api->Invoice->getInvoice($response['storeId'] ?? null, $response['invoiceId'] ?? null);

            // Set default error message in case no invoice is returned from gateway
            if (empty($invoice)) {
                $this->Input->setErrors(
                    ['transaction' => ['response' => Language::_('BtcpayServer.!error.failed.response', true)]]
                );
            }

            // Get invoice data
            $response['price'] = (string) $invoice->getAmount();
            $response['currency'] = $invoice->getCurrency();
            $response['id'] = $invoice->getId();
            $response['status'] = $invoice->getStatus();

            // Get invoice metadata
            $metadata = $invoice->getData()['metadata'];

            // Get client id
            $client_id = null;
            if (isset($metadata['orderId'])) {
                $order_id = explode('-', $metadata['orderId'], 2);

                if (isset($order_id[0]) && is_numeric($order_id[0])) {
                    $client_id = (int) $order_id[0];
                }
            }

            // Log successful response
            $status = null;
            if (isset($response['status'])) {
                switch ($response['status']) {
                    case 'Processing':
                        $status = 'pending';
                        break;
                    case 'Settled':
                    case 'PaidPartial':
                        $status = 'approved';
                        break;
                    case 'Invalid':
                        $this->Input->setErrors(
                            ['invalid' => ['response' => Language::_('BtcpayServer.!error.payment.invalid', true)]]
                        );
                        $status = 'declined';
                        break;
                    case 'Expired':
                        $this->Input->setErrors(
                            ['invalid' => ['response' => Language::_('BtcpayServer.!error.payment.expired', true)]]
                        );
                        $status = 'declined';
                        break;
                }
                $invoices = $this->deSerializeInvoices($metadata['posData'] ?? null);
            }

            if (!is_null($status)) {
                return [
                    'client_id' => $client_id,
                    'amount' => $response['price'],
                    'currency' => $response['currency'],
                    'invoices' => ($invoices ?? null),
                    'status' => $status,
                    'transaction_id' => $response['id'],
                    'parent_transaction_id' => null
                ];
            }
        }

        return [];
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
        $invoices = [];
        if (isset($get['posData'])) {
            $invoices = $this->deSerializeInvoices($get['posData']);
        }

        return [
            'client_id' => ($get['client_id'] ?? null),
            'amount' => ($get['price'] ?? null),
            'currency' => ($get['currency'] ?? null),
            'invoices' => ($invoices ?? null),
            'status' => 'approved',
            'transaction_id' => null,
            'parent_transaction_id' => null
        ];
    }

    /**
     * Captures a previously authorized payment
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction.
     * @param float $amount The amount.
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
     * Initializes the BTCPay SDK
     *
     * @param array $meta An array of meta (settings) data belonging to this gateway
     * @return stdClass An object with the instances of the BTCPay SDK
     */
    private function getApi(array $meta)
    {
        $url = rtrim($meta['server_url'] ?? '', '/');

        $clients = [
            'Store',
            'Invoice',
            'User'
        ];
        $api = [];

        foreach ($clients as $client) {
            $class = "\\BTCPayServer\\Client\\$client";
            $api[$client] = new $class($url, $meta['api_key'] ?? null);
        }

        return (object) $api;
    }

    /**
     * Call from your notification handler to convert $_POST data to an object containing invoice data
     *
     * @return string json object which contain the result.
     */
    private function getIpn()
    {
        $post = file_get_contents('php://input');

        if (!$post) {
            return 'No post data';
        }

        $json = (array) json_decode($post, true);
        if (is_scalar($json)) {
            // Error message
            $this->logger->error($json);

            return $json;
        }

        return $json;
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
            // Do not allow more invoices data to exceed the max length of 100 allowed by BTCPay
            // Storing and encoding data uses up 31 characters leaving only 69 for invoice data
            if (strlen($temp) + strlen($str) >= 69) {
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
}
