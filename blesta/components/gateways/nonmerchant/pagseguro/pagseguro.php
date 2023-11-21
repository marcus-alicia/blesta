<?php
/**
 * Pagseguro Payment Gateway
 *
 * @package blesta
 * @subpackage blesta.components.gateways.nonmerchant.pagseguro
 * @author Phillips Data, Inc.
 * @author Nirays Technologies
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 * @link http://nirays.com/ Nirays
 */
class Pagseguro extends NonmerchantGateway
{
    /**
     * @var array An array of meta data for this gateway
     */
    private $meta;
    /**
     * Construct Pagseguro gateway
     */
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this gateway
        Loader::loadComponents($this, ['Input']);

        // Load components required by this gateway
        Loader::loadModels($this, ['Clients', 'Contacts', 'Transactions', 'Companies']);

        // Load the language required by this gateway
        Language::loadLang('pagseguro', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * Attempt to install this gateway
     */
    public function install()
    {
        $errors = [];

        // Ensure the the system has the libxml extension
        if (!extension_loaded('libxml')) {
            $errors['libxml'] = ['required' => Language::_('Pagseguro.!error.libxml_required', true)];
        }
        // DOM extension required
        if (!extension_loaded('dom')) {
            $errors['dom'] = ['required' => Language::_('Pagseguro.!error.dom_required', true)];
        }

        if (!empty($errors)) {
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
            'email_id' => [
                'format' => [
                    'rule' => ['isEmail'],
                    'message' => Language::_('Pagseguro.!error.email.format', true)
                ]
            ],
            'token' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Pagseguro.!error.token.empty', true)
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
        return ['token'];
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
     *      - period The recurring period (day, week, month, year, onetime) used in
     *          conjunction with term in order to determine the next recurring payment
     * @return string HTML markup required to render an authorization and capture payment form
     */
    public function buildProcess(array $contact_info, $amount, array $invoice_amounts = null, array $options = null)
    {
        $this->view = $this->makeView('process', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        //Load pagseguro library
        Loader::load(dirname(__FILE__) . DS . 'pagsegurolibrary' . DS . 'PagSeguroLibrary.php');

        // Set cms version
        PagSeguroLibrary::setCMSVersion('3.0.0');

        // Set plugin version
        PagSeguroLibrary::setModuleVersion($this->getVersion());

        // Set charset
        PagSeguroConfig::setApplicationCharset('UTF-8');

        // Instantiate a new payment request
        $payment_request = new PagSeguroPaymentRequest();

        // Sets the currency
        $currency = (isset($this->currency) ? $this->currency : null);
        $payment_request->setCurrency($currency);

        // Add item
        $amount = round($amount, 2);
        $payment_request->addItem('1', (isset($options['description']) ? $options['description'] : null), 1, $amount);

        // Set a reference
        $invoices = $this->serializeInvoices($invoice_amounts);
        $payment_request->setReference((isset($invoices) ? $invoices : null));

        // Set customer information.
        $name = (isset($contact_info['first_name']) ? $contact_info['first_name'] : null). ' ' . (isset($contact_info['last_name']) ? $contact_info['last_name'] : null);
        $client = $this->Clients->get($contact_info['client_id']);
        $email = (isset($client->email) ? $client->email : null);
        $payment_request->setSender($name, $email, null, null, null, null);

         // Sets redirect url
        $redirect_url = (isset($options['return_url']) ? $options['return_url'] : null);

        $query = parse_url($redirect_url, PHP_URL_QUERY);

        // Returns a string if the URL has parameters or NULL if not
        if ($query) {
            $redirect_url .= '&';
        } else {
            $redirect_url .= '?';
        }

        // The redirection url for the gateway.
        $query_param = 'am='.$amount.'&in='.$invoices;
        $redirect_url .= $query_param;

        // Set redirect url and limit url to 98 characters as library sets redirect
        // url blank if more than 98 characters.
        $payment_request->setRedirectUrl(substr($redirect_url, 0, 98));

        // Sets notification url
        $notification_url = Configure::get('Blesta.gw_callback_url')
            . Configure::get('Blesta.company_id') . '/pagseguro/?client_id='
            . (isset($contact_info['client_id']) ? $contact_info['client_id'] : null);
        $payment_request->setNotificationURL($notification_url);

        // Sending a payment request to pagseguro
        try {
            $credentials = new PagSeguroAccountCredentials($this->meta['email_id'], $this->meta['token']);

            //Log the input
            $connectionData = new PagSeguroConnectionData($credentials, 'paymentService');
            $checkout_url = $connectionData->getResource('checkoutUrl');
            $this->log($checkout_url, serialize($payment_request), 'input', true);

            //Response url obtained from Pagseguro
            $url_pagseguro =  $payment_request->register($credentials);

            //Log the response
            $this->log((isset($checkout_url) ? $checkout_url : null), serialize($url_pagseguro), 'output', true);
        } catch (PagSeguroServiceException $e) {
            $messages =[];
            foreach ($e->getErrors() as $key => $error) {
                $message = $error->getCode() .': '. $error->getMessage(); // The error message
                array_push($messages, $message);
            }

            if (isset($messages)) {
                //Log the unsuccessful response
                $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($messages), 'output', false);
            }

            $this->Input->setErrors($this->getCommonError('invalid'));
        } catch (Exception $e) {
            $this->Input->setErrors($this->getCommonError('invalid'));
            //Log the exception
            $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($e), 'output', false);
        }

        // Set url for redirection
        if (isset($url_pagseguro) && !$this->Input->errors()) {
            header('Location:'.$url_pagseguro);
        }

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

        //Load Pagseguro library
        Loader::load(dirname(__FILE__) . DS . 'pagsegurolibrary' . DS . 'PagSeguroLibrary.php');

        // Log notification from Pagseguro
        $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($post), 'output', true);

        $type = (isset($post['notificationType']) && trim($post['notificationType']) != '')
            ? trim($post['notificationType'])
            : null;
        $code = (isset($post['notificationCode']) && trim($post['notificationCode']) != '')
            ? trim($post['notificationCode'])
            : null;

        if ($code && $type) {
            $notification_type = new PagSeguroNotificationType($type);
            $response_type = $notification_type->getTypeFromValue();

            switch (strtolower($response_type)) {

                case 'transaction':
                    $credentials = new PagSeguroAccountCredentials($this->meta['email_id'], $this->meta['token']);
                    try {
                        $transaction = PagSeguroNotificationService::checkTransaction($credentials, $code);

                        //Get trasaction details and status
                        if (isset($transaction)) {
                            $status_value = $transaction->getStatus()->getValue();
                        }

                        switch ((isset($status_value) ? $status_value : null)) {
                            case 1:
                                $status = 'pending';
                                break;
                            case 2:
                                $status = 'pending';
                                break;
                            case 3:
                                $status = 'approved';
                                break;
                            case 4:
                                $status = 'approved';
                                break;
                            case 5:
                                $status = 'void';
                                break;
                            case 6:
                                $status = 'refunded';
                                break;
                            case 7:
                                $status = 'declined';
                                break;
                            default:
                                $status = 'declined';
                        }

                        //Get invoice information & amount
                        $transaction_info = PagSeguroTransactionSearchService::searchByCode($credentials, $code);

                        //Log the response after querying for transaction information
                        $connection_data = new PagSeguroConnectionData($credentials, 'notificationService');
                        $service_url = $connection_data->getServiceUrl();
                        $this->log((isset($service_url) ? $service_url : null), serialize($transaction_info), 'output', true);

                        $invoices = $transaction_info->GetReference();
                        if (isset($invoices)) {
                            $data = [
                                'client_id' => (isset($get['client_id']) ? $get['client_id'] : null),
                                'amount' => (!empty($transaction_info->getGrossAmount()) ? $transaction_info->getGrossAmount() : ''),
                                'currency' => 'BRL',
                                'invoices' => $this->deserializeInvoices($invoices),
                                'status' => (isset($status) ? $status : null),
                                'transaction_id' => (!empty($transaction_info->getCode()) ? $transaction_info->getCode() : ''),
                                'parent_transaction_id' => null
                            ];
                        }
                    } catch (PagSeguroServiceException $e) {
                        $this->Input->setErrors($this->getCommonError('invalid'));
                        $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($e), 'output', false);
                    } catch (Exception $e) {
                        $this->Input->setErrors($this->getCommonError('invalid'));
                        //Log the exception
                        $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($e), 'output', false);
                    }
                    break;

                default:
                    $this->Input->setErrors(
                        [
                            'transaction' => [
                                'unknown' => Language::_('Pagseguro.!error.notification.type', true)
                                    . $notification_type->getValue()
                            ]
                        ]
                    );
            }
        } else {
            $this->Input->setErrors(
                [
                    'transaction' => [
                        'invalid' => Language::_('Pagseguro.!error.notification.invalid', true)
                    ]
                ]
            );
        }
        if (isset($data)) {
            return $data;
        }
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
        if (isset($get['in'])) {
            $invoices = $this->deserializeInvoices($get['in']);
        }

        return  [
                'client_id' => (isset($get['client_id']) ? $get['client_id'] : null),
                'amount' => (isset($get['am']) ? $get['am'] : null),
                'currency' => 'BRL',
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
     * @param string $transaction_id The transaction ID for the previously authorized transaction
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
            $str .= ($i > 0 ? '-' : '') . $invoice['id'] . '_' . $invoice['amount'];
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
    private function deserializeInvoices($str)
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
