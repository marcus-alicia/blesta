<?php
/**
 * Hubtel Gateway.
 *
 * The Hubtel API documentation can be found at:
 * https://developers.hubtel.com/documentations/online-checkout-api
 *
 * @package blesta
 * @subpackage blesta.components.gateways.nonmerchant.hubtel
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Hubtel extends NonmerchantGateway
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
        Language::loadLang('hubtel', null, dirname(__FILE__) . DS . 'language' . DS);
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
            'client_id' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Hubtel.!error.client_id.valid', true)
                ]
            ],
            'client_secret' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Hubtel.!error.client_secret.valid', true)
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
        return ['client_id', 'client_secret'];
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
     *      - alpha3 The 3-character country code
     *      - name The English name of the country
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

        // Load library methods
        Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'hubtel_api.php');
        $api = new HubtelApi($this->meta['client_id'], $this->meta['client_secret']);

        // Force 2-decimal places only
        $amount = number_format($amount, 2, '.', '');

        // Get company information
        $company = $this->Companies->get(Configure::get('Blesta.company_id'));

        // Get client data
        $client = $this->Clients->get($contact_info['client_id']);

        // Set all invoices to pay
        if (isset($invoice_amounts) && is_array($invoice_amounts)) {
            $invoices = $this->serializeInvoices($invoice_amounts);
        }

        // Build the payment request
        $items = [
            [
                'name' => (isset($options['description']) ? $options['description'] : null),
                'quantity' => 1,
                'unit_price' => $amount,
                'total_price' => $amount
            ]
        ];
        $store = [
            'name' => (isset($company->name) ? $company->name : null),
            'postal_address' => (isset($company->address) ? $company->address : null),
            'phone' => (isset($company->phone) ? $company->phone : null)
        ];
        $custom_data = [
            'client_id' => (isset($contact_info['client_id']) ? $contact_info['client_id'] : null),
            'currency' => (isset($this->currency) ? $this->currency : null),
            'invoices' => (isset($invoices) ? $invoices : null)
        ];
        $this->log(
            (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
            serialize(compact('items', 'store', 'custom_data')),
            'input',
            true
        );

        // Send the request to the api
        $redirect_url = Configure::get('Blesta.gw_callback_url') . Configure::get('Blesta.company_id')
            . '/hubtel/?client_id=' . $contact_info['client_id'];
        $request = $api->createInvoice(
            $items,
            (isset($options['description']) ? $options['description'] : null),
            $store,
            $redirect_url,
            $custom_data
        );

        // Build the payment form
        try {
            if (isset($request->response_code) && $request->response_code == '00') {
                $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($request), 'output', true);

                // Save the invoice token in the session
                $this->Session->clear('hubtel_token');
                $this->Session->write('hubtel_token', $request->token);

                return $this->buildForm($request->response_text);
            } else {
                // The api has been responded with an error, set the error
                $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($request), 'output', false);
                $this->Input->setErrors(
                    ['api' => ['response' => $request->response_text]]
                );

                return null;
            }
        } catch (Exception $e) {
            $this->Input->setErrors(
                ['internal' => ['response' => $e->getMessage()]]
            );
        }
    }

    /**
     * Builds the HTML form.
     *
     * @param string $post_to The URL to post to
     * @return string The HTML form
     */
    private function buildForm($post_to)
    {
        $this->view = $this->makeView('process', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('post_to', $post_to);

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
        // Load library methods
        Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'hubtel_api.php');
        $api = new HubtelApi($this->meta['client_id'], $this->meta['client_secret']);

        // Get invoice token from the session
        $token = $this->Session->read('hubtel_token');

        // Get the invoice details
        $response = $api->getInvoice($token);

        // Get invoices
        $invoices = (isset($response->custom_data->invoices) ? $response->custom_data->invoices : null);

        // Capture the transaction status
        $status = 'error';
        $return_status = false;

        if (isset($response->status)) {
            switch ($response->status) {
                case 'completed':
                    $status = 'approved';
                    $return_status = true;
                    break;
                case 'cancelled':
                    $status = 'declined';
                    $return_status = false;
                    break;
                case 'pending':
                    $status = 'pending';
                    $return_status = true;
                    break;
            }
        }

        // Log response
        $this->log((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null), serialize($response), 'output', $return_status);

        // Get payment details
        $amount = number_format($response->invoice->total_amount, 2, '.', '');
        $currency = (isset($response->custom_data->currency) ? $response->custom_data->currency : null);

        return [
            'client_id' => (isset($get['client_id']) ? $get['client_id'] : null),
            'amount' => $amount,
            'currency' => $currency,
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => $token,
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
        // Load library methods
        Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'hubtel_api.php');
        $api = new HubtelApi($this->meta['client_id'], $this->meta['client_secret']);

        // Get invoice token from the session
        $token = $this->Session->read('hubtel_token');

        // Get the invoice details
        $response = $api->getInvoice($token);

        // Get invoices
        $invoices = (isset($response->custom_data->invoices) ? $response->custom_data->invoices : null);

        // Get payment details
        $amount = number_format($response->invoice->total_amount, 2, '.', '');
        $currency = (isset($response->custom_data->currency) ? $response->custom_data->currency : null);

        return [
            'client_id' => (isset($get['client_id']) ? $get['client_id'] : null),
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'approved', // we wouldn't be here if it weren't, right?
            'reference_id' => null,
            'transaction_id' => $token,
            'invoices' => $this->unserializeInvoices($invoices)
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
}
