<?php
/**
 * Authorize.net Accept.js Credit Card processing gateway. Supports both
 * onsite and offsite payment processing for Credit Cards payments.
 *
 * A list of all Authorize.net API can be found at: https://developer.authorize.net/api/reference/features/acceptjs.html
 *
 * @package blesta
 * @subpackage blesta.components.gateways.authorize_net_acceptjs
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AuthorizeNetAcceptjs extends MerchantGateway implements MerchantCc, MerchantCcOffsite, MerchantCcForm
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

        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('authorize_net_acceptjs', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load product configuration required by this module
        Configure::load('authorize_net_acceptjs', dirname(__FILE__) . DS . 'config' . DS);
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
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('settings', 'default');
        $this->view->setDefaultView('components' . DS . 'gateways' . DS . 'merchant' . DS . 'authorize_net_acceptjs' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        Loader::loadModels($this, ['GatewayManager']);

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
        // Validate the given meta data to ensure it meets the requirements
        $rules = [
            'transaction_key' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('AuthorizeNetAcceptjs.!error.transaction_key.empty', true)
                ]
            ],
            'login_id' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('AuthorizeNetAcceptjs.!error.login_id.empty', true)
                ],
                'valid' => [
                    'rule' => [
                        [$this, 'validateConnection'],
                        $meta['transaction_key'],
                        $meta['sandbox']
                    ],
                    'message' => Language::_('AuthorizeNetAcceptjs.!error.login_id.valid', true)
                ]
            ],
            'sandbox' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['true', 'false']],
                    'message' => Language::_('AuthorizeNetAcceptjs.!error.sandbox.valid', true)
                ]
            ]
        ];

        // Set checkbox if not set
        if (!isset($meta['sandbox'])) {
            $meta['sandbox'] = 'false';
        }

        $this->Input->setRules($rules);

        // Validate the given meta data to ensure it meets the requirements
        $this->Input->validates($meta);

        return $meta;
    }

    /**
     * Returns an array of all fields to encrypt when storing in the database
     *
     * @return array An array of the field names to encrypt when storing in the database
     */
    public function encryptableFields()
    {
        return ['login_id', 'transaction_key'];
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
     * Used to determine whether this gateway can be configured for autodebiting accounts
     *
     * @return bool True if the customer must be present (e.g. in the case of credit card
     *  customer must enter security code), false otherwise
     */
    public function requiresCustomerPresent()
    {
        return false;
    }

    /**
     * Informs the system of whether or not this gateway is configured for offsite customer
     * information storage for credit card payments
     *
     * @return bool True if the gateway expects the offset methods to be called for credit card payments,
     *  false to process the normal methods instead
     */
    public function requiresCcStorage()
    {
        return true;
    }

    /**
     * Returns HTML markup used to render a custom credit card form for a merchant gateway
     *
     * @return string Custom cc form HTML from the merchant
     */
    public function buildCcForm()
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = $this->makeView('cc_form', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Date']);

        // Set available credit card expiration dates
        $expiration = [
            // Get months with full name (e.g. "January")
            'months' => $this->Date->getMonths(1, 12, 'm', 'F'),
            // Sets years from the current year to 10 years in the future
            'years' => $this->Date->getYears(date('Y'), date('Y') + 10, 'Y', 'Y')
        ];

        // Get Client Key
        $this->loadApi('CIM');
        $merchant = $this->AuthorizeNetCim->getMerchantDetailsRequest();
        $client_key = $merchant['publicClientKey'] ?? '';

        $this->view->set('meta', $this->meta);
        $this->view->set('expiration', $expiration);
        $this->view->set('client_key', $client_key);

        return $this->view->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function buildPaymentConfirmation($reference_id, $transaction_id, $amount)
    {
        return '';
    }

    /**
     * Charge a credit card
     *
     * @param array $card_info An array of credit card info including:
     *  - first_name The first name on the card
     *  - last_name The last name on the card
     *  - card_number The card number
     *  - card_exp The card expiration date
     *  - card_security_code The 3 or 4 digit security code of the card (if available)
     *  - address1 The address 1 line of the card holder
     *  - address2 The address 2 line of the card holder
     *  - city The city of the card holder
     *  - state An array of state info including:
     *      - code The 2 or 3-character state code
     *      - name The local name of the country
     *  - country An array of country info including:
     *      - alpha2 The 2-character country code
     *      - alpha3 The 3-character country code
     *      - name The english name of the country
     *      - alt_name The local name of the country
     *  - zip The zip/postal code of the card holder
     * @param float $amount The amount to charge this card
     * @param array $invoice_amounts An array of invoices, each containing:
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function processCc(array $card_info, $amount, array $invoice_amounts = null)
    {
        // Load api
        $this->loadApi('ACCEPT');

        // Log input
        $masked_params = $card_info;
        $masked_params['reference_id'] = '****';
        $this->log('/authCaptureTransaction', serialize($masked_params), 'input', true);

        // Unserialize reference id
        $data = $this->unserializeReference($card_info['reference_id']);

        // Create the payment object for a payment nonce
        $opaque_data = new net\authorize\api\contract\v1\OpaqueDataType();
        $opaque_data->setDataDescriptor($data['data_descriptor']);
        $opaque_data->setDataValue($data['data_value']);

        // Create payment type
        $payment_type = new net\authorize\api\contract\v1\PaymentType();
        $payment_type->setOpaqueData($opaque_data);

        // Create order
        $order = new net\authorize\api\contract\v1\OrderType();
        $order->setInvoiceNumber($this->getChargeInvoice($invoice_amounts));
        $order->setDescription($this->getChargeDescription($invoice_amounts));

        // Set customer address
        $customer_address = new net\authorize\api\contract\v1\CustomerAddressType();
        $customer_address->setFirstName($card_info['first_name'] ?? '');
        $customer_address->setLastName($card_info['last_name'] ?? '');
        $customer_address->setAddress($card_info['address1'] ?? '');
        $customer_address->setCity($card_info['city'] ?? '');
        $customer_address->setState($card_info['state']['code'] ?? '');
        $customer_address->setZip($card_info['zip'] ?? '00000');
        $customer_address->setCountry($card_info['country']['alpha3'] ?? '');

        // Set customer identifying information
        $reference_id = substr(md5($card_info['reference_id'] ?? ''), 0 ,18);
        $customer_data = new net\authorize\api\contract\v1\CustomerDataType();
        $customer_data->setType('individual');
        $customer_data->setId($reference_id);

        // Create a transaction
        $transaction = new net\authorize\api\contract\v1\TransactionRequestType();
        $transaction->setTransactionType('authCaptureTransaction');
        $transaction->setAmount($amount);
        $transaction->setOrder($order);
        $transaction->setPayment($payment_type);
        $transaction->setBillTo($customer_address);
        $transaction->setCustomer($customer_data);

        // Assemble the complete transaction request
        $request = new net\authorize\api\contract\v1\CreateTransactionRequest();
        $request->setMerchantAuthentication($this->AuthorizeNetAccept);
        $request->setRefId($reference_id);
        $request->setTransactionRequest($transaction);

        // Create the controller and get the response
        try {
            $controller = new net\authorize\api\controller\CreateTransactionController($request);
            $response = $controller->executeWithApiResponse(
                $this->meta['sandbox'] == 'true'
                    ? \net\authorize\api\constants\ANetEnvironment::SANDBOX
                    : \net\authorize\api\constants\ANetEnvironment::PRODUCTION
            );
        } catch (Throwable $e) {
            $this->Input->setErrors(['authnet_error' => ['auth_capture' => $e->getMessage()]]);
            $this->log('/authCaptureTransaction', serialize(['auth_capture' => $e->getMessage()]), 'output');

            return;
        }

        // Log response
        $this->log(
            '/authCaptureTransaction',
            serialize($response),
            'output',
            $response->getMessages()->getResultCode() == 'Ok'
        );

        if ($response->getMessages()->getResultCode() !== 'Ok') {
            $this->Input->setErrors(['authnet_error' => ['auth_capture' => $response->getMessages()->getMessage()[0]->getText()]]);

            return;
        }

        return [
            'status' => $response->getMessages()->getResultCode() == 'Ok' ? 'accepted' : 'declined',
            'reference_id' => substr(md5($card_info['reference_id'] ?? ''), 0 ,18),
            'transaction_id' => $response->getTransactionResponse()->getTransId() ?? '',
            'message' => $response->getMessages()->getMessage()[0]->getText() ?? null
        ];
    }

    /**
     * Authorize a credit card
     *
     * @param array $card_info An array of credit card info including:
     *  - first_name The first name on the card
     *  - last_name The last name on the card
     *  - card_number The card number
     *  - card_exp The card expidation date
     *  - card_security_code The 3 or 4 digit security code of the card (if available)
     *  - address1 The address 1 line of the card holder
     *  - address2 The address 2 line of the card holder
     *  - city The city of the card holder
     *  - state An array of state info including:
     *      - code The 2 or 3-character state code
     *      - name The local name of the country
     *  - country An array of country info including:
     *      - alpha2 The 2-character country code
     *      - alpha3 The 3-cahracter country code
     *      - name The english name of the country
     *      - alt_name The local name of the country
     *  - zip The zip/postal code of the card holder
     * @param float $amount The amount to charge this card
     * @param array $invoice_amounts An array of invoices, each containing:
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function authorizeCc(array $card_info, $amount, array $invoice_amounts = null)
    {
        // Load api
        $this->loadApi('ACCEPT');

        // Log input
        $masked_params = $card_info;
        $masked_params['reference_id'] = '****';
        $this->log('/authOnlyTransaction', serialize($masked_params), 'input', true);

        // Unserialize reference id
        $data = $this->unserializeReference($card_info['reference_id']);

        // Create the payment object for a payment nonce
        $opaque_data = new net\authorize\api\contract\v1\OpaqueDataType();
        $opaque_data->setDataDescriptor($data['data_descriptor']);
        $opaque_data->setDataValue($data['data_value']);

        // Create payment type
        $payment_type = new net\authorize\api\contract\v1\PaymentType();
        $payment_type->setOpaqueData($opaque_data);

        // Create order
        $order = new net\authorize\api\contract\v1\OrderType();
        $order->setInvoiceNumber($this->getChargeInvoice($invoice_amounts));
        $order->setDescription($this->getChargeDescription($invoice_amounts));

        // Set customer address
        $customer_address = new net\authorize\api\contract\v1\CustomerAddressType();
        $customer_address->setFirstName($card_info['first_name'] ?? '');
        $customer_address->setLastName($card_info['last_name'] ?? '');
        $customer_address->setAddress($card_info['address1'] ?? '');
        $customer_address->setCity($card_info['city'] ?? '');
        $customer_address->setState($card_info['state']['code'] ?? '');
        $customer_address->setZip($card_info['zip'] ?? '00000');
        $customer_address->setCountry($card_info['country']['alpha3'] ?? '');

        // Set customer identifying information
        $reference_id = substr(md5($card_info['reference_id'] ?? ''), 0 ,18);
        $customer_data = new net\authorize\api\contract\v1\CustomerDataType();
        $customer_data->setType('individual');
        $customer_data->setId($reference_id);

        // Create a transaction
        $transaction = new net\authorize\api\contract\v1\TransactionRequestType();
        $transaction->setTransactionType('authOnlyTransaction');
        $transaction->setAmount($amount);
        $transaction->setOrder($order);
        $transaction->setPayment($payment_type);
        $transaction->setBillTo($customer_address);
        $transaction->setCustomer($customer_data);

        // Assemble the complete transaction request
        $request = new net\authorize\api\contract\v1\CreateTransactionRequest();
        $request->setMerchantAuthentication($this->AuthorizeNetAccept);
        $request->setRefId($reference_id);
        $request->setTransactionRequest($transaction);

        // Create the controller and get the response
        try {
            $controller = new net\authorize\api\controller\CreateTransactionController($request);
            $response = $controller->executeWithApiResponse(
                $this->meta['sandbox'] == 'true'
                    ? \net\authorize\api\constants\ANetEnvironment::SANDBOX
                    : \net\authorize\api\constants\ANetEnvironment::PRODUCTION
            );
        } catch (Throwable $e) {
            $this->Input->setErrors(['authnet_error' => ['authorize' => $e->getMessage()]]);
            $this->log('/authOnlyTransaction', serialize(['authorize' => $e->getMessage()]), 'output');

            return;
        }

        // Log response
        $this->log(
            '/authOnlyTransaction',
            serialize($response->getTransactionResponse() ?? $response),
            'output',
            $response->getMessages()->getResultCode() == 'Ok'
        );

        if ($response->getMessages()->getResultCode() !== 'Ok') {
            $this->Input->setErrors(['authnet_error' => ['authorize' => $response->getMessages()->getMessage()[0]->getText()]]);

            return;
        }

        return [
            'status' => $response->getMessages()->getResultCode() == 'Ok' ? 'pending' : 'declined',
            'reference_id' => substr(md5($card_info['reference_id'] ?? ''), 0 ,18),
            'transaction_id' => $response->getTransactionResponse()->getTransId() ?? '',
            'message' => $response->getMessages()->getMessage()[0]->getText() ?? null
        ];
    }

    /**
     * Capture the funds of a previously authorized credit card
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @param float $amount The amount to capture on this card
     * @param array $invoice_amounts An array of invoices, each containing:
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function captureCc($reference_id, $transaction_id, $amount, array $invoice_amounts = null)
    {
        // Load api
        $this->loadApi('ACCEPT');

        // Execute api call, only if the transaction_id is not empty
        if (empty($transaction_id)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));

            return;
        }

        // Log input
        $masked_params = compact('reference_id', 'transaction_id', 'amount', 'invoice_amounts');
        $this->log('/priorAuthCaptureTransaction', serialize($masked_params), 'input', true);

        // Create a transaction
        $transaction = new net\authorize\api\contract\v1\TransactionRequestType();
        $transaction->setTransactionType('priorAuthCaptureTransaction');
        $transaction->setRefTransId($transaction_id);

        // Send request
        $request = new net\authorize\api\contract\v1\CreateTransactionRequest();
        $request->setMerchantAuthentication($this->AuthorizeNetAccept);
        $request->setRefId($reference_id);
        $request->setTransactionRequest($transaction);

        try {
            $controller = new net\authorize\api\controller\CreateTransactionController($request);
            $response = $controller->executeWithApiResponse(
                $this->meta['sandbox'] == 'true'
                    ? \net\authorize\api\constants\ANetEnvironment::SANDBOX
                    : \net\authorize\api\constants\ANetEnvironment::PRODUCTION
            );
        } catch (Throwable $e) {
            $this->Input->setErrors(['authnet_error' => ['capture' => $e->getMessage()]]);
            $this->log('/priorAuthCaptureTransaction', serialize(['capture' => $e->getMessage()]), 'output');

            return;
        }

        // Log response
        $this->log(
            '/priorAuthCaptureTransaction',
            serialize($response->getTransactionResponse() ?? $response),
            'output',
            $response->getMessages()->getResultCode() == 'Ok'
        );

        if ($response->getMessages()->getResultCode() !== 'Ok') {
            $this->Input->setErrors(['authnet_error' => ['capture' => $response->getMessages()->getMessage()[0]->getText()]]);

            return;
        }

        return [
            'status' => $response->getMessages()->getResultCode() == 'Ok' ? 'approved' : 'declined',
            'reference_id' => $reference_id,
            'transaction_id' => $transaction_id,
            'message' => $response->getMessages()->getMessage()[0]->getText() ?? null
        ];
    }

    /**
     * Void a credit card charge
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function voidCc($reference_id, $transaction_id)
    {
        // Load api
        $this->loadApi('ACCEPT');

        // Execute api call, only if the transaction_id is not empty
        if (empty($transaction_id)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));

            return;
        }

        // Log input
        $masked_params = compact('reference_id', 'transaction_id');
        $this->log('/voidTransaction', serialize($masked_params), 'input', true);

        // Create a transaction
        $transaction = new net\authorize\api\contract\v1\TransactionRequestType();
        $transaction->setTransactionType('voidTransaction');
        $transaction->setRefTransId($transaction_id);

        // Void transaction
        $request = new net\authorize\api\contract\v1\CreateTransactionRequest();
        $request->setMerchantAuthentication($this->AuthorizeNetAccept);
        $request->setRefId($reference_id);
        $request->setTransactionRequest($transaction);

        try {
            $controller = new net\authorize\api\controller\CreateTransactionController($request);
            $response = $controller->executeWithApiResponse(
                $this->meta['sandbox'] == 'true'
                    ? \net\authorize\api\constants\ANetEnvironment::SANDBOX
                    : \net\authorize\api\constants\ANetEnvironment::PRODUCTION
            );
        } catch (Throwable $e) {
            $this->Input->setErrors(['authnet_error' => ['void' => $e->getMessage()]]);
            $this->log('/voidTransaction', serialize(['void' => $e->getMessage()]), 'output');

            return;
        }

        // Log response
        $this->log(
            '/voidTransaction',
            serialize($response->getTransactionResponse() ?? $response),
            'output',
            $response->getMessages()->getResultCode() == 'Ok'
        );

        if ($response->getMessages()->getResultCode() !== 'Ok') {
            $this->Input->setErrors(['authnet_error' => ['void' => $response->getMessages()->getMessage()[0]->getText()]]);

            return;
        }

        return [
            'status' => $response->getMessages()->getResultCode() == 'Ok' ? 'void' : 'error',
            'reference_id' => $reference_id,
            'transaction_id' => $transaction_id,
            'message' => $response->getMessages()->getMessage()[0]->getText() ?? null
        ];
    }

    /**
     * Refund a credit card charge
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @param float $amount The amount to refund this card
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function refundCc($reference_id, $transaction_id, $amount)
    {
        // Load api
        $this->loadApi('ACCEPT');

        // Log input
        $masked_params = compact('reference_id', 'transaction_id');
        $this->log('/refundTransaction', serialize($masked_params), 'input', true);

        // Get transaction
        $request = new net\authorize\api\contract\v1\GetTransactionDetailsRequest();
        $request->setMerchantAuthentication($this->AuthorizeNetAccept);
        $request->setTransId($transaction_id);
        $controller = new net\authorize\api\controller\GetTransactionDetailsController($request);

        $response = $controller->executeWithApiResponse(
            $this->meta['sandbox'] == 'true'
                ? \net\authorize\api\constants\ANetEnvironment::SANDBOX
                : \net\authorize\api\constants\ANetEnvironment::PRODUCTION
        );
        $masked_credit_card = $response->getTransaction()->getPayment()->getCreditCard();

        // Create a refund transaction
        $credit_card = new net\authorize\api\contract\v1\CreditCardType();
        $credit_card->setCardNumber($masked_credit_card->getCardNumber());
        $credit_card->setExpirationDate($masked_credit_card->getExpirationDate());

        $payment = new net\authorize\api\contract\v1\PaymentType();
        $payment->setCreditCard($credit_card);

        $transaction = new net\authorize\api\contract\v1\TransactionRequestType();
        $transaction->setTransactionType('refundTransaction');
        $transaction->setAmount($amount);
        $transaction->setPayment($payment);
        $transaction->setRefTransId($transaction_id);

        // Refund transaction
        $request = new net\authorize\api\contract\v1\CreateTransactionRequest();
        $request->setMerchantAuthentication($this->AuthorizeNetAccept);
        $request->setRefId($reference_id);
        $request->setTransactionRequest($transaction);

        try {
            $controller = new net\authorize\api\controller\CreateTransactionController($request);
            $response = $controller->executeWithApiResponse(
                $this->meta['sandbox'] == 'true'
                    ? \net\authorize\api\constants\ANetEnvironment::SANDBOX
                    : \net\authorize\api\constants\ANetEnvironment::PRODUCTION
            );
        } catch (Throwable $e) {
            $this->Input->setErrors(['authnet_error' => ['refund' => $e->getMessage()]]);
            $this->log('/refundTransaction', serialize(['refund' => $e->getMessage()]), 'output');

            return;
        }

        // Log response
        $this->log(
            '/refundTransaction',
            serialize($response->getTransactionResponse() ?? $response),
            'output',
            $response->getMessages()->getResultCode() == 'Ok'
        );

        if ($response->getMessages()->getResultCode() !== 'Ok') {
            $this->Input->setErrors(['authnet_error' => [
                'refund' => $response->getTransactionResponse()->getErrors()[0]->getErrorText()
                    ?? $response->getMessages()->getMessage()[0]->getText()
            ]]);

            return;
        }

        return [
            'status' => $response->getMessages()->getResultCode() == 'Ok' ? 'refunded' : 'error',
            'reference_id' => $reference_id,
            'transaction_id' => $transaction_id,
            'message' => $response->getTransactionResponse()->getErrors()[0]->getErrorText()
                ?? $response->getMessages()->getMessage()[0]->getText()
        ];
    }

    /**
     * Store a credit card off site
     *
     * @param array $card_info An array of card info to store off site including:
     *  - first_name The first name on the card
     *  - last_name The last name on the card
     *  - card_number The card number
     *  - card_exp The card expiration date
     *  - card_security_code The 3 or 4 digit security code of the card (if available)
     *  - address1 The address 1 line of the card holder
     *  - address2 The address 2 line of the card holder
     *  - city The city of the card holder
     *  - state An array of state info including:
     *      - code The 2 or 3-character state code
     *      - name The local name of the country
     *  - country An array of country info including:
     *      - alpha2 The 2-character country code
     *      - alpha3 The 3-character country code
     *      - name The english name of the country
     *      - alt_name The local name of the country
     *  - zip The zip/postal code of the card holder
     * @param array $contact An array of contact information for the billing contact this
     *  account is to be set up under including:
     *  - id The ID of the contact
     *  - client_id The ID of the client this contact resides under
     *  - user_id The ID of the user this contact represents
     *  - contact_type The contact type
     *  - contact_type_id The reference ID for this custom contact type
     *  - contact_type_name The name of the contact type
     *  - first_name The first name of the contact
     *  - last_name The last name of the contact
     *  - title The title of the contact
     *  - company The company name of the contact
     *  - email The email address of the contact
     *  - address1 The address of the contact
     *  - address2 The address line 2 of the contact
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
     *  - date_added The date/time the contact was added
     * @param string $client_reference_id The reference ID for the client on the remote gateway (if one exists)
     * @return mixed False on failure or an array containing:
     *  - client_reference_id The reference ID for this client
     *  - reference_id The reference ID for this payment account
     */
    public function storeCc(array $card_info, array $contact, $client_reference_id = null)
    {
        // Load api
        $this->loadApi('ACCEPT');
        Loader::loadModels($this, ['Accounts']);

        // Log input
        $masked_params = $card_info;
        $masked_params['client_reference_id'] = '****';
        $masked_params['reference_id'] = '****';
        $this->log('/CreateCustomerProfileRequest', serialize($masked_params), 'input', true);

        // Unserialize reference id
        $data = $this->unserializeReference($card_info['reference_id']);

        // Create the payment object for a payment nonce
        $opaque_data = new net\authorize\api\contract\v1\OpaqueDataType();
        $opaque_data->setDataDescriptor($data['data_descriptor']);
        $opaque_data->setDataValue($data['data_value']);

        // Create customer profile
        $payment_type = new net\authorize\api\contract\v1\PaymentType();
        $payment_type->setOpaqueData($opaque_data);
        $card_type = $this->Accounts->creditCardType($card_info['client_reference_id'] ?? '');

        $customer_address = new net\authorize\api\contract\v1\CustomerAddressType();
        $customer_address->setFirstName($card_info['first_name'] ?? '');
        $customer_address->setLastName($card_info['last_name'] ?? '');
        $customer_address->setAddress($card_info['address1'] ?? '');
        $customer_address->setCity($card_info['city'] ?? '');
        $customer_address->setState($card_info['state']['code'] ?? '');
        $customer_address->setZip($card_info['zip'] ?? '00000');
        $customer_address->setCountry($card_info['country']['alpha3'] ?? '');

        $payment_profile = new net\authorize\api\contract\v1\CustomerPaymentProfileType();
        $payment_profile->setCustomerType('individual');
        $payment_profile->setBillTo($customer_address);
        $payment_profile->setPayment($payment_type);

        $customer_profile = new net\authorize\api\contract\v1\CustomerProfileType();
        $customer_profile->setDescription($card_info['first_name'] . ' ' . $card_info['last_name']);
        $customer_profile->setMerchantCustomerId("M_" . time());
        $customer_profile->setpaymentProfiles([$payment_profile]);
        $customer_profile->setShipToList([$customer_address]);

        // Assemble the complete transaction request
        $profile_reference = substr(md5($card_info['reference_id'] ?? ''), 0 ,18);
        $request = new net\authorize\api\contract\v1\CreateCustomerProfileRequest();
        $request->setMerchantAuthentication($this->AuthorizeNetAccept);
        $request->setRefId($profile_reference);
        $request->setProfile($customer_profile);

        $card_info['client_reference_id'] = '';
        $card_info['reference_id'] = '';

        // Create the controller and get the response
        try {
            $controller = new net\authorize\api\controller\CreateCustomerProfileController($request);
            $response = $controller->executeWithApiResponse(
                $this->meta['sandbox'] == 'true'
                    ? \net\authorize\api\constants\ANetEnvironment::SANDBOX
                    : \net\authorize\api\constants\ANetEnvironment::PRODUCTION
            );
        } catch (Throwable $e) {
            $this->Input->setErrors(['authnet_error' => ['authorize' => $e->getMessage()]]);
            $this->log('/CreateCustomerProfileRequest', serialize(['authorize' => $e->getMessage()]), 'output');

            return;
        }

        // Log response
        $this->log(
            '/CreateCustomerProfileRequest',
            serialize($response),
            'output',
            $response->getMessages()->getResultCode() == 'Ok'
        );

        if ($response->getMessages()->getResultCode() !== 'Ok') {
            $this->Input->setErrors(['authnet_error' => ['authorize' => $response->getMessages()->getMessage()[0]->getText()]]);

            return;
        }

        return [
            'client_reference_id' => $response->getCustomerProfileId(),
            'reference_id' => $response->getCustomerPaymentProfileIdList()[0] ?? '',
            'last4' => $card_info['last4'] ?? '',
            'expiration' => $card_info['card_exp'] ?? '',
            'type' => $card_type
        ];
    }

    /**
     * Update a credit card stored off site
     *
     * @param array $card_info An array of card info to store off site including:
     *  - first_name The first name on the card
     *  - last_name The last name on the card
     *  - card_number The card number
     *  - card_exp The card expiration date
     *  - card_security_code The 3 or 4 digit security code of the card (if available)
     *  - address1 The address 1 line of the card holder
     *  - address2 The address 2 line of the card holder
     *  - city The city of the card holder
     *  - state An array of state info including:
     *      - code The 2 or 3-character state code
     *      - name The local name of the country
     *  - country An array of country info including:
     *      - alpha2 The 2-character country code
     *      - alpha3 The 3-character country code
     *      - name The english name of the country
     *      - alt_name The local name of the country
     *  - zip The zip/postal code of the card holder
     *  - account_changed True if the account details (bank account or card number, etc.)
     *  have been updated, false otherwise
     * @param array $contact An array of contact information for the billing contact this
     *  account is to be set up under including:
     *  - id The ID of the contact
     *  - client_id The ID of the client this contact resides under
     *  - user_id The ID of the user this contact represents
     *  - contact_type The contact type
     *  - contact_type_id The reference ID for this custom contact type
     *  - contact_type_name The name of the contact type
     *  - first_name The first name of the contact
     *  - last_name The last name of the contact
     *  - title The title of the contact
     *  - company The company name of the contact
     *  - email The email address of the contact
     *  - address1 The address of the contact
     *  - address2 The address line 2 of the contact
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
     *  - date_added The date/time the contact was added
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @return mixed False on failure or an array containing:
     *  - client_reference_id The reference ID for this client
     *  - reference_id The reference ID for this payment account
     */
    public function updateCc(array $card_info, array $contact, $client_reference_id, $account_reference_id)
    {
        return $this->storeCc($card_info, $contact, $client_reference_id);
    }

    /**
     * Remove a credit card stored off site
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to remove
     * @return array An array containing:
     *  - client_reference_id The reference ID for this client
     *  - reference_id The reference ID for this payment account
     */
    public function removeCc($client_reference_id, $account_reference_id)
    {
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }

    /**
     * Charge a credit card stored off site
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @param float $amount The amount to process
     * @param array $invoice_amounts An array of invoices, each containing:
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function processStoredCc($client_reference_id, $account_reference_id, $amount, array $invoice_amounts = null)
    {
        // Load api
        $this->loadApi('ACCEPT');

        // Log input
        $masked_params = compact('client_reference_id', 'account_reference_id', 'amount', 'invoice_amounts');
        $masked_params['client_reference_id'] = '****';
        $this->log('/authCaptureTransaction', serialize($masked_params), 'input', true);

        $reference_id = substr(md5(time()), 0 ,18);

        $payment = new net\authorize\api\contract\v1\PaymentProfileType();
        $payment->setPaymentProfileId($account_reference_id);

        $profile = new net\authorize\api\contract\v1\CustomerProfilePaymentType();
        $profile->setCustomerProfileId($client_reference_id);
        $profile->setPaymentProfile($payment);

        $transaction = new net\authorize\api\contract\v1\TransactionRequestType();
        $transaction->setTransactionType('authCaptureTransaction');
        $transaction->setAmount($amount);
        $transaction->setProfile($profile);

        $request = new net\authorize\api\contract\v1\CreateTransactionRequest();
        $request->setMerchantAuthentication($this->AuthorizeNetAccept);
        $request->setRefId($reference_id);
        $request->setTransactionRequest($transaction);

        try {
            $controller = new net\authorize\api\controller\CreateTransactionController($request);
            $response = $controller->executeWithApiResponse(
                $this->meta['sandbox'] == 'true'
                    ? \net\authorize\api\constants\ANetEnvironment::SANDBOX
                    : \net\authorize\api\constants\ANetEnvironment::PRODUCTION
            );
        } catch (Throwable $e) {
            $this->Input->setErrors(['authnet_error' => ['auth_capture' => $e->getMessage()]]);
            $this->log('/authCaptureTransaction', serialize(['auth_capture' => $e->getMessage()]), 'output');

            return;
        }

        // Log response
        $this->log(
            '/authCaptureTransaction',
            serialize($response->getTransactionResponse() ?? $response),
            'output',
            $response->getMessages()->getResultCode() == 'Ok'
        );

        if ($response->getMessages()->getResultCode() !== 'Ok') {
            $this->Input->setErrors(['authnet_error' => ['auth_capture' => $response->getMessages()->getMessage()[0]->getText()]]);

            return;
        }

        return [
            'status' => $response->getMessages()->getResultCode() == 'Ok' ? 'approved' : 'error',
            'reference_id' => $reference_id,
            'transaction_id' => $response->getTransactionResponse()->getTransId() ?? '',
            'message' => $response->getMessages()->getMessage()[0]->getText() ?? null
        ];
    }

    /**
     * Authorizees a credit card stored off site
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @param float $amount The amount to authorize
     * @param array $invoice_amounts An array of invoices, each containing:
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function authorizeStoredCc(
        $client_reference_id,
        $account_reference_id,
        $amount,
        array $invoice_amounts = null
    ) {
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }

    /**
     * Charge a previously authorized credit card stored off site
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @param string $transaction_reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The ID of the previously authorized transaction
     * @param float $amount The amount to capture
     * @param array $invoice_amounts An array of invoices, each containing:
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function captureStoredCc(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id,
        $amount,
        array $invoice_amounts = null
    ) {
        $this->Input->setErrors($this->getCommonError('unsupported')); return;
    }

    /**
     * Void an off site credit card charge
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @param string $transaction_reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The ID of the previously authorized transaction
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard message for
     *      this transaction status (optional)
     */
    public function voidStoredCc(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id
    ) {
        return $this->voidCc($transaction_reference_id, $transaction_id);
    }

    /**
     * Refund an off site credit card charge
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @param string $transaction_reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The ID of the previously authorized transaction
     * @param float $amount The amount to refund
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard message
     *      for this transaction status (optional)
     */
    public function refundStoredCc(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id,
        $amount
    ) {
        return $this->refundCc($transaction_reference_id, $transaction_id, $amount);
    }

    /**
     * Loads the given API if not already loaded
     *
     * @param string $type The type of API to load (CIM or ACCEPT)
     */
    private function loadApi($type)
    {
        Loader::load(dirname(__FILE__) . DS . 'vendor' . DS . 'authorizenet' . DS . 'authorizenet' . DS . 'autoload.php');

        $type = strtolower($type);
        switch ($type) {
            case 'cim':
                if (!isset($this->AuthorizeNetCim)) {
                    Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'authorize_net_cim.php');
                    $this->AuthorizeNetCim = new AuthorizeNetCim(
                        $this->meta['login_id'],
                        $this->meta['transaction_key'],
                        $this->meta['sandbox'] == 'true'
                    );
                }

                $this->AuthorizeNetCim->setCurrency($this->currency);
                break;
            case 'accept':
                if (!isset($this->AuthorizeNetAccept)) {
                    $this->AuthorizeNetAccept = new net\authorize\api\contract\v1\MerchantAuthenticationType();
                    $this->AuthorizeNetAccept->setName($this->meta['login_id']);
                    $this->AuthorizeNetAccept->setTransactionKey($this->meta['transaction_key']);
                }

                break;
        }
    }

    /**
     * Validates the connection with Authorize.net
     *
     * @param string $login_id The Authorize.net login id
     * @param string $transaction_key The Authorize.net transaction key
     * @param string $sandbox Whether to use the sandbox endpoint, 'true' to use the sandbox endpoint
     */
    public function validateConnection($login_id, $transaction_key, $sandbox = 'false')
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'authorize_net_cim.php');

        $AuthorizeNetCim = new AuthorizeNetCim(
            $login_id,
            $transaction_key,
            $sandbox == 'true',
            'liveMode'
        );
        $merchant = $AuthorizeNetCim->getMerchantDetailsRequest();

        return !empty($merchant['publicClientKey']);
    }

    /**
     * Unserialize the reference id
     *
     * @param string $reference_id The reference id returned by Accept.js
     * @return array A list containing the data value and data descriptor
     */
    private function unserializeReference($reference_id)
    {
        $parts = explode("|", $reference_id, 2);

        return [
            'data_value' => $parts[0] ?? '',
            'data_descriptor' => $parts[1] ?? ''
        ];
    }

    /**
     * Retrieves the description for CC charges
     *
     * @param array|null $invoice_amounts An array of invoice amounts (optional)
     * @return string The charge description
     */
    private function getChargeDescription(array $invoice_amounts = null)
    {
        // No invoice amounts, set a default description
        if (empty($invoice_amounts)) {
            return Language::_('AuthorizeNetAcceptjs.charge_description_default', true);
        }

        Loader::loadModels($this, ['Invoices']);
        Loader::loadComponents($this, ['DataStructure']);
        $string = $this->DataStructure->create('string');

        // Create a list of invoices being paid
        $id_codes = [];
        foreach ($invoice_amounts as $invoice_amount) {
            if (($invoice = $this->Invoices->get($invoice_amount['invoice_id']))) {
                $id_codes[] = $invoice->id_code;
            }
        }

        // Use the default description if there are no valid invoices
        if (empty($id_codes)) {
            return Language::_('AuthorizeNetAcceptjs.charge_description_default', true);
        }

        // Truncate the description to a max of 20 characters
        $description = Language::_('AuthorizeNetAcceptjs.charge_description', true, implode(' ', $id_codes));
        if (strlen($description) > 20) {
            $description = $string->truncate($description, ['length' => 20]) . '...';
        }

        return $description;
    }

    /**
     * Retrieves the invoice number for CC charges
     *
     * @param array|null $invoice_amounts An array of invoice amounts (optional)
     * @return string The invoice charge number
     */
    private function getChargeInvoice(array $invoice_amounts = null)
    {
        // No invoice amounts, set a default description
        if (empty($invoice_amounts)) {
            return time();
        }

        Loader::loadModels($this, ['Invoices']);
        Loader::loadComponents($this, ['DataStructure']);

        // Create a list of invoices being paid
        $id_codes = [];
        foreach ($invoice_amounts as $invoice_amount) {
            if (($invoice = $this->Invoices->get($invoice_amount['invoice_id']))) {
                $id_codes[] = $invoice->id_code;
            }
        }

        if (count($id_codes) == 1) {
            return preg_replace('~\D~', '', trim($id_codes[0] ?? time()));
        }

        return time();
    }
}
