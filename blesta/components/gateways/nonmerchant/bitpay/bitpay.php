<?php
use Blesta\Core\Util\Common\Traits\Container;
use BitPayKeyUtils\KeyHelper\PrivateKey;
use BitPaySDK\Model\Invoice\Invoice;
use BitPaySDK\Model\Invoice\Buyer;

/**
 * Bitpay Gateway
 *
 * Allows users to pay via Bitcoin
 *
 * @package blesta
 * @subpackage blesta.components.gateways.nonmerchant.bitpay
 * @author Phillips Data, Inc.
 * @author Nirays Technologies
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 * @link http://nirays.com/ Nirays
 */
class Bitpay extends NonmerchantGateway
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
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this gateway
        Loader::loadComponents($this, ['Input']);

        // Load components required by this gateway
        Loader::loadModels($this, ['Clients']);

        // Load the language required by this gateway
        Language::loadLang('bitpay', null, dirname(__FILE__) . DS . 'language' . DS);

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
     * Create and return the view content required to modify the settings of this gateway
     *
     * @param array $meta An array of meta (settings) data belonging to this gateway
     * @return string HTML content containing the fields to update the meta data for this gateway
     */
    public function getSettings(array $meta = null)
    {
        $this->view = $this->makeView(
            'settings',
            'default',
            str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS)
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Load the models required for this view
        Loader::loadHelpers($this, ['GatewayManager']);

        // Get current meta from the database
        $gateway = $this->GatewayManager->getByClass(get_class($this), Configure::get('Blesta.company_id'));
        $current_meta = isset($gateway[0]->meta)
            ? $this->Form->collapseObjectArray($gateway[0]->meta, 'value', 'key')
            : (object) [];

        // Generate a new private key, if there is no previously existing key
        $setup = empty($current_meta['private_key']);

        if ($setup) {
            $key_name = uniqid('bitpay');
            $private_key = new PrivateKey($key_name);
            $meta['private_key'] = (string)$private_key->generate();
            $meta['key_id'] = (string)$private_key->getId();

            // Generate the public key
            $public_key = null;
            if (!empty($meta['private_key'])) {
                $public_key = $private_key->getPublicKey();
            }

            // Generate the SIN code from the public key (used to request a pairing code)
            $sin_code = null;
            if (!empty($public_key)) {
                $sin_code = (string)$public_key->getSin();
            }

            // Generate a new token
            $pairing_code = null;
            if (!empty($sin_code)) {
                $token = $this->generateToken(
                    $sin_code,
                    $private_key,
                    $public_key,
                    'merchant', (isset($meta['test_mode']) ? $meta['test_mode'] : null)
                );
                $meta['token'] = $token['data'][0]['token'];
                $meta['client_id'] = isset($token['data'][0]['policies'][0]['params'][0])
                    ? $token['data'][0]['policies'][0]['params'][0]
                    : null;
                $pairing_code = $token['data'][0]['pairingCode'];
            }

            $this->view->set('pairing_code', $pairing_code);
        }

        $select_options = [
            'high' => Language::_('Bitpay.transaction.speed.high', true),
            'medium' => Language::_('Bitpay.transaction.speed.medium', true),
            'low' => Language::_('Bitpay.transaction.speed.low', true)
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
        // Verify meta data is valid
        if (!isset($meta['disconnect'])) {
            $rules = [
                'transaction_speed' => [
                    'valid' => [
                        'rule' => ['in_array', ['high', 'medium', 'low']],
                        'message' => Language::_('Bitpay.!error.transaction_speed.valid', true)
                    ]
                ],
                'token' => [
                    'valid' => [
                        'rule' => function ($token) use ($meta) {
                            // Validate connection to the BitPay API
                            if (empty($meta['test_mode'])) {
                                $meta['test_mode'] = 'false';
                            }

                            $bitpay = $this->getApi($meta);

                            try {
                                $ledgers = $bitpay->getLedgers();
                            } catch (Throwable $e) {
                                try {
                                    $subscriptions = $bitpay->getSubscriptions();
                                } catch (Throwable $s) {
                                    return false;
                                }
                            }

                            return true;
                        },
                        'message' => Language::_('Bitpay.!error.token.valid', true)
                    ]
                ]
            ];

            // Set test mode if not given
            if (empty($meta['test_mode'])) {
                $meta['test_mode'] = 'false';
            }

            // Refresh fields
            if (isset($meta['refresh_fields'])) {
                foreach ($meta as $key => $value) {
                    if ($key !== 'test_mode') {
                        $meta[$key] = '';
                    }
                }

                unset($meta['refresh_fields']);
                unset($rules['transaction_speed']);
                unset($rules['token']);
            }

            $this->Input->setRules($rules);

            // Validate the given meta data to ensure it meets the requirements
            $this->Input->validates($meta);
        }

        // Disconnect from BitPay
        if (isset($meta['disconnect'])) {
            foreach ($meta as $key => $value) {
                $meta[$key] = '';
            }

            unset($meta['disconnect']);
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
        return ['private_key', 'key_id', 'token'];
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
     *      - alpha3 The 3-character country code
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
     *      - period The recurring period (day, week, month, year, onetime)
     *          used in conjunction with term in order to determine the next recurring payment
     * @return string HTML markup required to render an authorization and capture payment form
     */
    public function buildProcess(array $contact_info, $amount, array $invoice_amounts = null, array $options = null)
    {
        $client = $this->Clients->get($contact_info['client_id']);

        // Initialize the API
        $bitpay = $this->getApi($this->meta);

        // Set amount and currency
        $amount = round($amount, 2); // Force 2-decimal places only
        $currency = (isset($this->currency) ? $this->currency : null);

        // Set redirect url
        $redirect_url = (isset($options['return_url']) ? $options['return_url'] : null);
        $query = parse_url($redirect_url, PHP_URL_QUERY);
        $pos_data = $this->serializeInvoices($invoice_amounts);

        if ($query) {
            $redirect_url .= '&';
        } else {
            $redirect_url .= '?';
        }

        $redirect_url .= 'price=' . $amount . '&posData=' . $pos_data . '&currency=' . $currency;

        // Set instant notification url
        $notification_url = Configure::get('Blesta.gw_callback_url')
            . Configure::get('Blesta.company_id') . '/bitpay/?client_id='
            . (isset($contact_info['client_id']) ? $contact_info['client_id'] : null);

        // Create invoice
        $invoice = new Invoice($amount, $currency);

        $invoice->setOrderId((isset($contact_info['client_id']) ? $contact_info['client_id'] : null) . '-' . time());
        $invoice->setPosData($pos_data);
        $invoice->setRedirectURL($redirect_url);
        $invoice->setNotificationURL($notification_url);
        $invoice->setNotificationEmail((isset($client->email) ? $client->email : null));
        $invoice->setTransactionSpeed((isset($this->meta['transaction_speed']) ? $this->meta['transaction_speed'] : null));
        $invoice->setItemDesc((isset($options['description']) ? $options['description'] : null));
        $invoice->setPhysical(false);

        $buyer = new Buyer();
        $buyer->setName(
            (isset($contact_info['first_name']) ? $contact_info['first_name'] : '')
                . ' ' . (isset($contact_info['last_name']) ? $contact_info['last_name'] : '')
        );
        $buyer->setAddress1((isset($contact_info['address1']) ? $contact_info['address1'] : ''));
        $buyer->setAddress2((isset($contact_info['address2']) ? $contact_info['address2'] : ''));
        $buyer->setLocality((isset($contact_info['city']) ? $contact_info['city'] : ''));
        $buyer->setRegion((isset($contact_info['state']['name']) ? $contact_info['state']['name'] : ''));
        $buyer->setPostalCode((isset($contact_info['zip']) ? $contact_info['zip'] : ''));
        $buyer->setCountry((isset($contact_info['country']['name']) ? $contact_info['country']['name'] : ''));
        $buyer->setEmail((isset($contact_info['email']) ? $contact_info['email'] : ''));

        $invoice->setBuyer($buyer);

        $this->log(
            (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
            serialize($invoice->toArray()),
            'input',
            true
        );

        $invoice = $bitpay->createInvoice($invoice);
        $invoice_url = $invoice->getURL();

        $this->log(
            (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
            serialize($invoice_url),
            'output',
            !empty($invoice_url)
        );

        $this->view = $this->makeView(
            'process',
            'default',
            str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS)
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('invoice_url', $invoice_url);

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
        // Initialize the API
        $bitpay = $this->getApi($this->meta);

        // Validates bitpay response and get status of payment
        $response = (array) $this->getIpn();
        $return_status = false;

        if (isset($response['id'])) {
            $invoice = $bitpay->getInvoice($response['id']);
            $response['price'] = $invoice->getPrice();
            $response['status'] = $invoice->getStatus();
            $response['currency'] = $invoice->getCurrency();
        }

        // Invoice variable
        $invoices = [];

        // Initial status
        $status = 'declined';

        // Set default error message in case no error message is returned from gateway
        if (is_string($response)) {
            $this->Input->setErrors(
                ['transaction' => ['response' => Language::_('Bitpay.!error.failed.response', true)]]
            );
        }

        // Log bitpay message
        if (isset($response['error'])) {
            $this->Input->setErrors(
                ['transaction' => ['response' => Language::_('Bitpay.!error.failed.response', true)]]
            );
        }

        // Log successful response
        if (isset($response['status'])) {
            switch ($response['status']) {
                // For low and medium transaction speeds, the order status is set to "Order Received".
                // The customer receives an initial email stating that the transaction has been paid.
                case 'paid':
                    $return_status = true;
                    $status = 'pending';
                    break;

                // For low and medium transaction speeds, the order status will not change.
                // For high transaction speed, the order status is set to "Order Received" here.
                // For all speeds, an email will be sent stating that the transaction has
                // been confirmed.
                case 'confirmed':
                    // display initial "thank you" if transaction speed is high,
                    // as the 'paid' status is skipped on high speed
                    $return_status = true;
                    $status = 'pending';
                    break;

                // The purchase receipt email is sent upon the invoice status changing to "complete", and the order
                // status is changed to Accepted Payment
                case 'complete':
                    $status = 'approved';
                    $return_status = true;
                    break;

                case 'invalid':
                    $this->Input->setErrors(
                        ['invalid' => ['response' => Language::_('Bitpay.!error.payment.invalid', true)]]
                    );
                    $status = 'declined';
                    $return_status = false;
                    break;

                case 'expired':
                    $this->Input->setErrors(
                        ['invalid' => ['response' => Language::_('Bitpay.!error.payment.expired', true)]]
                    );
                    $status = 'declined';
                    $return_status = false;
                    break;
            }
            $invoices = (isset($response['posData']) ? $response['posData'] : null);
        }

        $this->log(
            (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
            serialize($response),
            'output',
            $return_status
        );

        return [
            'client_id' => (isset($get['client_id']) ? $get['client_id'] : null),
            'amount' => (isset($response['price']) ? $response['price'] : null),
            'currency' => (isset($response['currency']) ? $response['currency'] : null),
            'invoices' => (isset($invoices) ? $invoices : null),
            'status' => $status,
            'transaction_id' => (isset($response['id']) ? $response['id'] : null),
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
        $invoices = [];
        if (isset($get['posData'])) {
            $invoices = $this->deSerializeInvoices($get['posData']);
        }

        return [
            'client_id' =>(isset($get['client_id']) ? $get['client_id'] : null),
            'amount' => (isset($get['price']) ? $get['price'] : null),
            'currency' => (isset($get['currency']) ? $get['currency'] : null),
            'invoices' => (isset($invoices) ? $invoices : null),
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
        // Initialize the API
        $bitpay = $this->getApi($this->meta);

        // Get invoice
        if (!empty($transaction_id)) {
            $invoice = $bitpay->getInvoice($transaction_id);
        }

        if (isset($invoice)) {
            $this->log(
                (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
                serialize($invoice->toArray()),
                'input',
                true
            );

            $email = $invoice->getBuyerProvidedEmail();

            if (empty($email)) {
                $this->Input->setErrors($this->getCommonError('unsupported'));

                return [];
            }

            try {
                $refund = $bitpay->createRefund($invoice, $email, $invoice->getPrice(), $invoice->getCurrency());
            } catch (Throwable $e) {
                $this->Input->setErrors(['refund' => ['exception' => $e->getMessage()]]);
            }

            $this->log(
                (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
                serialize(isset($refund) ? $refund : null),
                'output',
                isset($refund)
            );

            return [
                'status' => 'refunded',
                'transaction_id' => $transaction_id
            ];
        }

        $this->Input->setErrors($this->getCommonError('unsupported'));
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
            // Do not allow more invoices data to exceed the max length of 100 allowed by BitPay
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

    /**
     * Generate a new API token
     *
     * @param string $sin_code The SIN code
     * @param PrivateKey $private_key An instance of the private key
     * @param PublicKey $public_key An instance of the public key
     * @param string $facade The facade for the token to be generated, it can be 'merchant' or 'payroll'
     * @param string $test_mode Whether or not to generate a token for the production or test environment
     * @return array The API response containing the new token
     */
    private function generateToken($sin_code, $private_key, $public_key, $facade = 'merchant', $test_mode = 'false')
    {
        $data = json_encode([
            'id' => $sin_code,
            'facade' => $facade
        ]);

        if ($test_mode == 'true') {
            $bitpay_url = 'https://test.bitpay.com';
        } else {
            $bitpay_url = 'https://bitpay.com';
        }

        $curl = curl_init($bitpay_url . '/tokens');

        curl_setopt(
            $curl, CURLOPT_HTTPHEADER, [
            'x-accept-version: 2.0.0',
            'Content-Type: application/json',
            'x-identity'  => (string) $public_key,
            'x-signature' => $private_key->sign($bitpay_url . '/tokens' . $data),
        ]);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, stripslashes($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        $response = curl_exec($curl);

        if ($response == false) {
            $this->logger->error(curl_error($curl));
            $this->Input->setErrors($this->getCommonError('general'));

            return;
        }

        $response = json_decode($response, true);

        curl_close($curl);

        return $response;
    }

    /**
     * Initializes the BitPay API and returns an instance of BitPaySDK
     *
     * @param array $meta An array of meta (settings) data belonging to this gateway
     * @return BitPaySDK\Client A BitPaySDK instance
     */
    private function getApi(array $meta)
    {
        return BitPaySDK\Client::create()->withData(
            ($meta['test_mode'] == 'true') ? BitPaySDK\Env::Test : BitPaySDK\Env::Prod,
            isset($meta['private_key']) ? $meta['private_key'] : null,
            new BitPaySDK\Tokens(
                isset($meta['token']) ? $meta['token'] : null
            )
        );
    }

    /**
     * Call from your notification handler to convert $_POST data to an object containing invoice data
     *
     * @return mixed|string json object which contain the result.
     */
    private function getIpn()
    {
        $post = file_get_contents('php://input');

        if (!$post) {
            return 'No post data';
        }

        $json = json_decode($post, true);

        if (is_string($json)) {
            // Error message
            return $json;
        }

        if (!array_key_exists('posData', $json)) {
            return 'No posData';
        }

        $json['posData'] = $this->deSerializeInvoices($json['posData']);

        return $json;
    }
}
