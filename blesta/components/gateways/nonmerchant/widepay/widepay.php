<?php
/**
 * Widepay
 *
 * API docs can be found at: https://widepay.github.io/api/#cobranca-gerando
 *
 * @package blesta
 * @subpackage blesta.components.gateways.widepay
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Widepay extends NonmerchantGateway
{
    /**
     * @var array An array of meta data for this gateway
     */
    private $meta;

    /**
     * Construct a new nonmerchant gateway
     */
    public function __construct()
    {
        // Load the Wide Pay API
        Loader::load(dirname(__FILE__) . DS . 'api' . DS . 'widepay_api.php');

        // Load configuration required by this gateway
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('widepay', null, dirname(__FILE__) . DS . 'language' . DS);
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
        $this->view = $this->makeView(
            'settings',
            'default',
            str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS)
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
        // Verify meta data is valid
        $rules = [
            'wallet_id' => [
                'format' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Widepay.!error.wallet_id.format', true)
                ]
            ],
            'wallet_token' => [
                'format' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Widepay.!error.wallet_token.format', true)
                ]
            ]
        ];
        $this->Input->setRules($rules);

        if (!isset($meta['allow_card_payment'])) {
            $meta['allow_card_payment'] = 'false';
        }

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
        return ['wallet_id', 'wallet_token'];
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
     * Returns all HTML markup required to render an authorization and capture payment form.
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
        Loader::loadModels($this, ['Clients']);

        // Load the helpers required
        Loader::loadHelpers($this, ['Html']);

        if (!empty($_POST)) {
            // Load the models required
            Loader::loadModels($this, ['Contacts', 'Invoices']);

            // Load library methods
            $api = $this->getApi();

            // Force 2-decimal places only
            $amount = number_format($amount, 2, '.', '');

            // Get client data
            $client = $this->Clients->get($contact_info['client_id'], false);
            $client->fields = $this->Clients->getCustomFieldValues($contact_info['client_id']);

            // Get the entity type and identification number from custom fields
            $cpf_cnpj = '';
            $entity_type = 'Física';
            foreach ($client->fields as $field) {
                if (strtolower($field->name) == 'cpf/cnpj') {
                    $cpf_cnpj = $field->value;
                }

                if (strtolower($field->name) == 'entity type') {
                    $entity_type = $field->value;
                }
            }

            // Get client phone number
            $contact_numbers = $this->Contacts->getNumbers($client->contact_id);

            $client_phone = '';
            foreach ($contact_numbers as $contact_number) {
                // Set phone number
                if ($contact_number->type == 'phone') {
                    $client_phone = $contact_number->number;
                    break;
                }
            }

            if (!empty($client_phone)) {
                $client_phone = preg_replace('/[^0-9]/', '', $client_phone);
            }

            // Build the payment request
            $notification_url = Configure::get('Blesta.gw_callback_url') . Configure::get('Blesta.company_id')
                . '/widepay/?client_id=' . $contact_info['client_id'];
            // Convert special characters that had been html encoded
            $form_type = isset($_POST['submit_widepay_ticket']) ? 'Boleto' : 'Cartão';
            $params = [
                'forma' => $form_type,
                'cliente' => $this->Html->concat(
                    ' ',
                    (isset($contact_info['first_name']) ? $contact_info['first_name'] : null),
                    (isset($contact_info['last_name']) ? $contact_info['last_name'] : null)
                ),
                'pessoa' => $entity_type,
                'email' => (isset($client->email) ? $client->email : null),
                'telefone' => (isset($client_phone) ? $client_phone : null),
                'itens' => [],
                'notificacao' => (isset($notification_url) ? $notification_url : null),
                'redirecionamento' => $options['return_url'],
            ];

            // Set conditional fields
            if ($entity_type == 'Física') {
                $params['cpf'] = $cpf_cnpj;
            } else {
                $params['cnpj'] = $cpf_cnpj;
            }

            // Set all invoices to pay
            if (!empty($invoice_amounts) && is_array($invoice_amounts)) {
                $earliest_invoice_date = null;
                foreach ($invoice_amounts as $invoice_amount) {
                    $params['itens'][] = [
                        'descricao' => $invoice_amount['id'],
                        'valor' => $invoice_amount['amount'],
                    ];

                    if ($form_type == 'Boleto') {
                        $invoice = $this->Invoices->get($invoice_amount['id']);
                        if (!isset($earliest_invoice_date)
                            || strtotime($invoice->date_due) < strtotime($earliest_invoice_date)
                        ) {
                            $earliest_invoice_date = $invoice->date_due;
                        }
                    }
                }

                if (isset($earliest_invoice_date) && $form_type == 'Boleto') {
                    $params['vencimento'] = date('Y-m-d H:i:s', strtotime($earliest_invoice_date . 'Z +3 days'));
                }
            }

            $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), json_encode($params), 'input', true);

            // Send the request to the api
            $request = $api->createCharge($params);
            $errors = $request->errors();
            if (empty($errors)) {
                $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), $request->raw(), 'output', true);

                $charge_response = $request->response();

                // Redirect the use to Wide Pay to finish payment
                $this->redirectToUrl($charge_response->link);
            } else {
                // The api has been responded with an error, set the error
                $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), $request->raw(), 'output', false);
                $this->Input->setErrors(
                    ['api' => $request->errors()]
                );
            }
        }

        // Build the payment form
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
        $this->view->set('allow_card_payment', (isset($this->meta['allow_card_payment']) ? $this->meta['allow_card_payment'] : 'true'));

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
        $api = $this->getApi();

        // The api has been responded with an error, set the error
        $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), json_encode($post), 'input', true);

        // Get the charge details from Wide Pay
        $charge_response = $api->getNotificationCharge(isset($post['notificacao']) ? $post['notificacao'] : '');

        // Log the Wide Pay response
        $errors = $charge_response->errors();
        $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), $charge_response->raw(), 'output', empty($errors));

        $response = $charge_response->response();

        // Get the status of the charge
        $status = empty($errors) ? 'approved' : 'error';
        if ((isset($response->cobranca->status) ? $response->cobranca->status : null)) {
            $status = $this->mapStatus($response->cobranca->status);
        }

        return [
            'client_id' => (isset($get['client_id']) ? $get['client_id'] : null),
            'amount' => (isset($response->cobranca->valor) ? $response->cobranca->valor : 0),
            'currency' => 'BRL',
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => (isset($response->cobranca->id) ? $response->cobranca->id : null),
            'invoices' => $this->unserializeInvoices((isset($response->cobranca->itens) ? $response->cobranca->itens : []))
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
        $api = $this->getApi();

        $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), [], 'input', true);

        // Get the charge information from Wide Pay
        $charge_response = $api->getCharge((isset($get['cobranca']) ? $get['cobranca'] : null));
        $response = $charge_response->response();

        $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), $charge_response->raw(), 'output', true);

        return [
            'client_id' => (isset($get['client_id']) ? $get['client_id'] : null),
            'amount' => (isset($response->cobrancas[0]->valor) ? $response->cobrancas[0]->valor : null),
            'currency' => 'BRL',
            'status' => $this->mapStatus($response->cobrancas[0]->status),
            'reference_id' => null,
            'transaction_id' => (isset($get['cobranca']) ? $get['cobranca'] : null),
            'invoices' => $this->unserializeInvoices((isset($response->cobrancas[0]->itens) ? $response->cobrancas[0]->itens : []))
        ];
    }

    /**
     * Void a payment or authorization
     *
     * @param string $reference_id The reference ID for the previously submitted transaction
     * @param string $transaction_id The transaction ID for the previously submitted transaction
     * @param string $notes Notes about the void that may be sent to the client by the gateway
     * @return array An array of transaction data including:
     *
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard message for
     *      this transaction status (optional)
     */
//    public function void($reference_id, $transaction_id, $notes = null)
//    {
//        $api = $this->getApi();
//
//        // Attempt to cancel the charge in Wide Pay
//        // For some reason I am not able to cancel a charge.  The charge used for testing was declined, so it is
//        // possible that prevents the action from suceeding
//        $charge_response = $api->cancelCharge($transaction_id);
//        $response = $charge_response->response();
//
//        if ((isset($response->sucesso) ? $response->sucesso : null)) {
//            return [
//                'status' => 'void',
//                'transaction_id' => $transaction_id,
//            ];
//        }
//    }

    /**
     * Map the status given by Wide Pay to the equivilent transaction status in Blesta
     *
     * @param string $widepay_status The charge status from Wide Pay
     * @return string The equvilent transaction status in Blesta
     */
    private function mapStatus($widepay_status)
    {
        $status = 'error';
        if ($widepay_status) {
            switch ($widepay_status) {
                case 'Aguardando':
                case 'Em análise':
                    $status = 'pending';
                    break;
                case 'Estornado':
                    $status = 'refunded';
                    break;
                case 'Recebido':
                case 'Recebido manualmente':
                    $status = 'approved';
                    break;
                case 'Recusado':
                case 'Cancelado':
                case 'Contestado':
                    $status = 'declined';
                    break;
                case 'Vencido':
                    $status = 'void';
                    break;
            }
        }

        return $status;
    }

    /**
     * Unserializes a string of invoice info into an array.
     *
     * @param array $items A list of items from Wide Pay
     * @return array A numerically indexed array invoices info including:
     *
     *  - id The ID of the invoice
     *  - amount The amount relating to the invoice
     */
    private function unserializeInvoices(array $items)
    {
        $invoices = [];
        foreach ($items as $item) {
            $invoices[] = ['id' => $item->descricao, 'amount' => $item->valor];
        }

        return $invoices;
    }

    /**
     * Loads the given API if not already loaded
     *
     * @return WidpayAPI
     */
    private function getApi()
    {
        return new WidepayAPI(
            $this->meta['wallet_id'],
            $this->meta['wallet_token']
        );
    }

    /**
     * Generates a redirect to the specified url.
     *
     * @param string $url The url to be redirected
     * @return bool True if the redirection was successful, false otherwise
     */
    private function redirectToUrl($url)
    {
        header('Location: ' . $url);
        exit();
    }
}
