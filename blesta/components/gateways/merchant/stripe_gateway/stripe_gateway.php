<?php
/**
 * Stripe Credit Card processing gateway. Supports both
 * onsite and offsite payment processing for Credit Cards.
 *
 * The Stripe API can be found at: https://stripe.com/docs/api
 *
 * @package blesta
 * @subpackage blesta.components.gateways.stripe_gateway
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class StripeGateway extends MerchantGateway implements MerchantCc, MerchantCcOffsite
{
    /**
     * @var array An array of meta data for this gateway
     */
    private $meta;
    /**
     * @var string The base URL of API requests
     */
    private $base_url = 'https://api.stripe.com/v1/';

    /**
     * Construct a new merchant gateway
     */
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('stripe_gateway', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * Attempt to install this gateway
     */
    public function install()
    {
        // Ensure that the system has support for the JSON extension
        if (!function_exists('json_decode')) {
            $errors = [
                'json' => [
                    'required' => Language::_('Stripe_gateway.!error.json_required', true)
                ]
            ];
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
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('settings', 'default');
        $this->view->setDefaultView('components' . DS . 'gateways' . DS . 'merchant' . DS . 'stripe_gateway' . DS);
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
            'api_key'=>[
                'empty'=>[
                    'rule'=>'isEmpty',
                    'negate'=>true,
                    'message'=>Language::_('Stripe_gateway.!error.api_key.empty', true)
                ]
            ]
        ];

        // Set checkbox if not set
        if (!isset($meta['stored'])) {
            $meta['stored'] = 'false';
        }

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
        return ['api_key'];
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
     * @return bool True if the customer must be present
     *  (e.g. in the case of credit card customer must enter security code), false otherwise
     */
    public function requiresCustomerPresent()
    {
        return false;
    }


    /**
     * Charge a credit card
     *
     * @param array $card_info An array of credit card info including:
     *  - first_name The first name on the card
     *  - last_name The last name on the card
     *  - card_number The card number
     *  - card_exp The card expiration date in yyyymm format
     *  - card_security_code The 3 or 4 digit security code of the card (if available)
     *  - type The credit card type
     *  - address1 The address 1 line of the card holder
     *  - address2 The address 2 line of the card holder
     *  - city The city of the card holder
     *  - state An array of state info including:
     *      - code The 2 or 3-character state code
     *      - name The local name of the state
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
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function processCc(array $card_info, $amount, array $invoice_amounts = null)
    {
        Loader::loadModels($this, ['Invoices']);
        $this->loadApi();

        $amount = $this->formatAmount($amount, $this->currency);

        $id_codes = [];
        foreach ($invoice_amounts as $invoice_amount) {
            $invoice = $this->Invoices->get($invoice_amount['invoice_id']);
            $id_codes[] = $invoice->id_code;
        }
        $description = Language::_('Stripe_gateway.charge_description', true, implode(', ', $id_codes));

        $charge = [
            'amount' => $amount,
            'currency' => strtolower($this->currency),
            'card' => [
                'number' => (isset($card_info['card_number']) ? $card_info['card_number'] : null),
                'exp_month' => substr((isset($card_info['card_exp']) ? $card_info['card_exp'] : null), 4, 2),
                'exp_year' => substr((isset($card_info['card_exp']) ? $card_info['card_exp'] : null), 0, 4),
                'name' => (isset($card_info['first_name']) ? $card_info['first_name'] : null) . ' ' . (isset($card_info['last_name']) ? $card_info['last_name'] : null),
                'address_line1' => (isset($card_info['address1']) ? $card_info['address1'] : null),
                'address_line2' => (isset($card_info['address2']) ? $card_info['address2'] : null),
                'address_zip' => (isset($card_info['zip']) ? $card_info['zip'] : null),
                'address_state' => (isset($card_info['state']['code']) ? $card_info['state']['code'] : null),
                'address_country' => (isset($card_info['country']['alpha3']) ? $card_info['country']['alpha3'] : null),
                'cvc' => (isset($card_info['card_security_code']) ? $card_info['card_security_code'] : null)
            ],
            'description' => strlen($description) > 500 ? substr($description, 0, 497) . '...' : $description
        ];

        // Attempt to charge the card
        $errors = [];
        $response = new stdClass();
        try {
            $response = Stripe_Charge::create($charge);

            // Re-format the response
            $response = $response->__toArray(true);
        } catch (Stripe_InvalidRequestError $exception) {
            if (isset($exception->json_body)) {
                $response = $exception->json_body;
                $errors = [
                    $response['error']['type'] => [
                        'error' => $response['error']['message']
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        } catch (Stripe_CardError $exception) {
            if (isset($exception->json_body)) {
                $response = $exception->json_body;
                $errors = [
                    $response['error']['type'] => [
                        $response['error']['code'] => $response['error']['message']
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        } catch (Stripe_AuthenticationError $exception) {
            if (isset($exception->json_body)) {
                // Don't use the actual error (as it may contain an API key, albeit invalid),
                // rather a general auth error
                $response = $exception->json_body;
                $errors = [
                    $response['error']['type'] => [
                        'auth_error' => Language::_('Stripe_gateway.!error.auth', true)
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        }
        // Any other exception, including Stripe_ApiError
        catch (Exception $e) {
            $errors = $this->getCommonError('general');
        }

        // Set any errors
        if (!empty($errors)) {
            $this->Input->setErrors($errors);
        }

        $response = $this->objectToArray($response);

        // Log the request
        $url = $this->base_url . 'charges';
        $this->logRequest($url, $charge, $response);

        // Set whether there was an error
        $status = 'error';
        if (isset($response['error']) && isset($response['error']['code'])
            && $response['error']['code'] == 'card_declined'
        ) {
            $status = 'declined';
        }

        if (!isset($response['error']) && empty($errors)) {
            $status = 'approved';
        } else {
            $message = (isset($response['error']['message']) ? $response['error']['message'] : null);
        }

        // Return formatted response
        $reference_id = substr((isset($card_info['card_number']) ? $card_info['card_number'] : null), -4);
        return [
            'status' => $status,
            'reference_id' => (strlen($reference_id) == 0 ? null : $reference_id),
            'transaction_id' => (isset($response['id']) ? $response['id'] : null),
            'message' => ($message ?? null)
        ];
    }

    /**
     * Authorize a credit card
     *
     * @param array $card_info An array of credit card info including:
     *  - first_name The first name on the card
     *  - last_name The last name on the card
     *  - card_number The card number
     *  - card_exp The card expiration date in yyyymm format
     *  - card_security_code The 3 or 4 digit security code of the card (if available)
     *  - type The credit card type
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
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function authorizeCc(array $card_info, $amount, array $invoice_amounts = null)
    {
        // Gateway does not support this action
        $this->Input->setErrors($this->getCommonError('unsupported'));
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
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function captureCc($reference_id, $transaction_id, $amount, array $invoice_amounts = null)
    {
        // Gateway does not support this action
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }

    /**
     * Void a credit card charge
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function voidCc($reference_id, $transaction_id)
    {
        // Refund the transaction
        $response = $this->refundCc($reference_id, $transaction_id, null);

        // Refund must be successful
        if ($this->Input->errors()) {
            return;
        }

        // Set to "void" status
        $response['status'] = 'void';
        return $response;
    }

    /**
     * Refund a credit card charge
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @param float $amount The amount to refund this card
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function refundCc($reference_id, $transaction_id, $amount)
    {
        $this->loadApi();

        $amount = $this->formatAmount($amount, $this->currency);

        $refund = ['id' => $transaction_id];

        // Set an amount iff there is one. Otherwise, the full transaction
        // amount will be refunded by Stripe
        if (!empty($amount)) {
            $refund['amount'] = $amount;
        }

        // Attempt to refund the charge
        $errors = [];
        $response = new stdClass();
        try {
            $charge = Stripe_Charge::retrieve($refund['id']);
            $total = array_intersect_key($refund, array_flip(array('amount')));
            $response = $charge->refund($total);

            // Re-format the response
            $response = $response->__toArray(true);
        } catch (Stripe_InvalidRequestError $exception) {
            if (isset($exception->json_body)) {
                $response = $exception->json_body;
                $errors = [
                    $response['error']['type'] => [
                        'error' => $response['error']['message']
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        } catch (Stripe_CardError $exception) {
            if (isset($exception->json_body)) {
                $response = $exception->json_body;
                $errors = [
                    $response['error']['type'] => [
                        $response['error']['code'] => $response['error']['message']
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        } catch (Stripe_AuthenticationError $exception) {
            if (isset($exception->json_body)) {
                // Don't use the actual error (as it may contain an API key, albeit invalid),
                // rather a general auth error
                $response = $exception->json_body;
                $errors = [
                    $response->error['type'] => [
                        'auth_error' => Language::_('Stripe_gateway.!error.auth', true)
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        }
        // Any other exception, including Stripe_ApiError
        catch (Exception $e) {
            $errors = $this->getCommonError('general');
        }

        // Set any errors
        if (!empty($errors)) {
            $this->Input->setErrors($errors);
        }

        $response = $this->objectToArray($response);

        // Log the request
        $url = $this->base_url . 'refund';
        $this->logRequest($url, $refund, $response);

        // Set whether there was an error
        $status = 'error';
        if (!isset($response['error']) && empty($errors)) {
            $status = 'refunded';
        } else {
            $message = (isset($response['error']['message']) ? $response['error']['message'] : null);
        }

        // Return formatted response
        return [
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => (isset($response['id']) ? $response['id'] : null),
            'message' => ($message ?? null)
        ];
    }

    /**
     * Store a credit card off site
     *
     * @param array $card_info An array of card info to store off site including:
     *  - first_name The first name on the card
     *  - last_name The last name on the card
     *  - card_number The card number
     *  - card_exp The card expiration date in yyyymm format
     *  - card_security_code The 3 or 4 digit security code of the card (if available)
     *  - type The credit card type
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
     * @param array $contact An array of contact information for the billing contact
     *  this account is to be set up under including:
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
        // Attempt to create a new customer
        if (!($customer_id = $this->createCustomer($card_info, $contact))) {
            // A customer could not be created
            return false;
        }

        // Customer created and card stored
        return ['client_reference_id'=>null, 'reference_id'=>$customer_id];
    }

    /**
     * Update a credit card stored off site
     *
     * @param array $card_info An array of card info to store off site including:
     *  - first_name The first name on the card
     *  - last_name The last name on the card
     *  - card_number The card number
     *  - card_exp The card expiration date in yyyymm format
     *  - card_security_code The 3 or 4 digit security code of the card (if available)
     *  - type The credit card type
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
     *      have been updated, false otherwise
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
        $this->loadApi();

        // Set customer input data
        $customer = [
            'card' => [
                'number' => (isset($card_info['card_number']) ? $card_info['card_number'] : null),
                'exp_month' => substr((isset($card_info['card_exp']) ? $card_info['card_exp'] : null), 4, 2),
                'exp_year' => substr((isset($card_info['card_exp']) ? $card_info['card_exp'] : null), 0, 4),
                'name' => (isset($card_info['first_name']) ? $card_info['first_name'] : null) . ' ' . (isset($card_info['last_name']) ? $card_info['last_name'] : null),
                'address_line1' => (isset($card_info['address1']) ? $card_info['address1'] : null),
                'address_line2' => (isset($card_info['address2']) ? $card_info['address2'] : null),
                'address_zip' => (isset($card_info['zip']) ? $card_info['zip'] : null),
                'address_state' => (isset($card_info['state']['code']) ? $card_info['state']['code'] : null),
                'address_country' => (isset($card_info['country']['alpha3']) ? $card_info['country']['alpha3'] : null),
                'cvc' => (isset($card_info['card_security_code']) ? $card_info['card_security_code'] : null)
            ]
        ];

        // Attempt to update the customer's card
        $errors = [];
        $response = new stdClass();
        try {
            // Get the customer
            $stripe_customer = Stripe_Customer::retrieve($account_reference_id);

            // Save changes to the customer's card
            $stripe_customer->card = $customer['card'];
            $response = $stripe_customer->save();

            // Reformat the response
            $response = $response->__toArray(true);
        } catch (Stripe_InvalidRequestError $exception) {
            if (isset($exception->json_body)) {
                $response = $exception->json_body;
                $errors = [
                    $response['error']['type'] => [
                        'error' => $response['error']['message']
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        } catch (Stripe_CardError $exception) {
            if (isset($exception->json_body)) {
                $response = $exception->json_body;
                $errors = [
                    $response['error']['type'] => [
                        $response['error']['code'] => $response['error']['message']
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        } catch (Stripe_AuthenticationError $exception) {
            if (isset($exception->json_body)) {
                // Don't use the actual error (as it may contain an API key, albeit invalid),
                // rather a general auth error
                $response = $exception->json_body;
                $errors = [
                    $response->error['type'] => [
                        'auth_error' => Language::_('Stripe_gateway.!error.auth', true)
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        }
        // Any other exception, including Stripe_ApiError
        catch (Exception $e) {
            $errors = $this->getCommonError('general');
        }

        // Set any errors
        if (!empty($errors)) {
            $this->Input->setErrors($errors);
        }

        $response = $this->objectToArray($response);

        // Log the request
        $url = $this->base_url . 'customers';
        $this->logRequest($url, $customer, $response);

        // Return the customer
        if (empty($errors)) {
            return ['client_reference_id' => null, 'reference_id' => (isset($response['id']) ? $response['id'] : null)];
        }
        return false;
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
        $this->loadApi();

        // Set the input we're passing to Stripe
        $input = [
            'id' => $account_reference_id
        ];

        // Attempt to remove the credit card
        $errors = [];
        $response = new stdClass();
        try {
            // Get the customer
            $stripe_customer = Stripe_Customer::retrieve($account_reference_id);

            // Delete the customer
            $response = $stripe_customer->delete();

            // Reformat the response
            $response = $response->__toArray(true);
        } catch (Stripe_InvalidRequestError $exception) {
            if (isset($exception->json_body)) {
                $response = $exception->json_body;
                $errors = [
                    $response['error']['type'] => [
                        'error' => $response['error']['message']
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        } catch (Stripe_CardError $exception) {
            if (isset($exception->json_body)) {
                $response = $exception->json_body;
                $errors = [
                    $response['error']['type'] => [
                        $response['error']['code'] => $response['error']['message']
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        } catch (Stripe_AuthenticationError $exception) {
            if (isset($exception->json_body)) {
                // Don't use the actual error (as it may contain an API key, albeit invalid),
                // rather a general auth error
                $response = $exception->json_body;
                $errors = [
                    $response->error['type'] => [
                        'auth_error' => Language::_('Stripe_gateway.!error.auth', true)
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        }
        // Any other exception, including Stripe_ApiError
        catch (Exception $e) {
            $errors = $this->getCommonError('general');
        }

        // Set any errors
        if (!empty($errors)) {
            $this->Input->setErrors($errors);
        }

        $response = $this->objectToArray($response);

        // Log the request
        $url = $this->base_url . 'customers';
        $this->logRequest($url, $input, $response);

        if (empty($errors)) {
            return ['client_reference_id' => null, 'reference_id' => (isset($response['id']) ? $response['id'] : null)];
        }
        return false;
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
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function processStoredCc($client_reference_id, $account_reference_id, $amount, array $invoice_amounts = null)
    {
        Loader::loadModels($this, ['Invoices']);
        $this->loadApi();

        $amount = $this->formatAmount($amount, $this->currency);

        $id_codes = [];
        foreach ($invoice_amounts as $invoice_amount) {
            $invoice = $this->Invoices->get($invoice_amount['invoice_id']);
            $id_codes[] = $invoice->id_code;
        }
        $description = Language::_('Stripe_gateway.charge_description', true, implode(', ', $id_codes));

        // Set the input parameters
        $customer = [
            'amount' => $amount,
            'currency' => strtolower($this->currency),
            'customer' => $account_reference_id,
            'description' => strlen($description) > 500 ? substr($description, 0, 497) . '...' : $description
        ];

        // Attempt to charge the customer's card on record
        $errors = [];
        $response = new stdClass();
        try {
            $response = Stripe_Charge::create($customer);

            // Re-format the response
            $response = $response->__toArray(true);
        } catch (Stripe_InvalidRequestError $exception) {
            if (isset($exception->json_body)) {
                $response = $exception->json_body;
                $errors = [
                    $response['error']['type'] => [
                        'error' => $response['error']['message']
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        } catch (Stripe_CardError $exception) {
            if (isset($exception->json_body)) {
                $response = $exception->json_body;
                $errors = [
                    $response['error']['type'] => [
                        $response['error']['code'] => $response['error']['message']
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        } catch (Stripe_AuthenticationError $exception) {
            if (isset($exception->json_body)) {
                // Don't use the actual error (as it may contain an API key, albeit invalid),
                // rather a general auth error
                $response = $exception->json_body;
                $errors = [
                    $response['error']['type'] => [
                        'auth_error' => Language::_('Stripe_gateway.!error.auth', true)
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        }
        // Any other exception, including Stripe_ApiError
        catch (Exception $e) {
            $errors = $this->getCommonError('general');
        }

        // Set any errors
        if (!empty($errors)) {
            $this->Input->setErrors($errors);
        }

        $response = $this->objectToArray($response);

        // Log the request
        $url = $this->base_url . 'charges';
        $this->logRequest($url, $customer, $response);

        // Set whether there was an error
        $status = 'error';
        if (isset($response['error']) && isset($response['error']['code'])
            && $response['error']['code'] == 'card_declined'
        ) {
            $status = 'declined';
        }

        if (!isset($response['error']) && empty($errors)) {
            $status = 'approved';
        } else {
            $message = (isset($response['error']['message']) ? $response['error']['message'] : null);
        }

        // Return formatted response
        $reference_id = substr((isset($card_info['card_number']) ? $card_info['card_number'] : null), -4);
        return [
            'status' => $status,
            'reference_id' => (strlen($reference_id) == 0 ? null : $reference_id),
            'transaction_id' => (isset($response['id']) ? $response['id'] : null),
            'message' => ($message ?? null)
        ];
    }

    /**
     * Authorize a credit card stored off site (do not charge)
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @param float $amount The amount to authorize
     * @param array $invoice_amounts An array of invoices, each containing:
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
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
        // Gateway does not support this action
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
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
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
        // Gateway does not support this action
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }

    /**
     * Void an off site credit card charge
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @param string $transaction_reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The ID of the previously authorized transaction
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function voidStoredCc(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id
    ) {
        // Refund a previous charge
        $response = $this->refundCc($transaction_reference_id, $transaction_id, null);

        // Refund must be successful
        if ($this->Input->errors()) {
            return;
        }

        // Set status to void
        $response['status'] = 'void';
        return $response;
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
     *  - status The status of the transaction (approved, declined, void, pending, error, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function refundStoredCc(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id,
        $amount
    ) {
        // Refund the transaction
        return $this->refundCc($transaction_reference_id, $transaction_id, $amount);
    }

    /**
     * Used to determine if offsite credit card customer account information is enabled for the gateway
     * This is invoked after the gateway has been initialized and after Gateway::setMeta() has been called.
     * The gateway should examine its current settings to verify whether or not the system
     * should invoke the gateway's offsite methods
     *
     * @return bool True if the gateway expects the offset methods to be called for
     *  credit card payments, false to process the normal methods instead
     */
    public function requiresCcStorage()
    {
        return (isset($this->meta['stored']) && $this->meta['stored'] == 'true') ? true : false;
    }

    /**
     * Store a credit card off site
     *
     * @param array $card_info An array of card info to store off site including:
     *  - first_name The first name on the card
     *  - last_name The last name on the card
     *  - card_number The card number
     *  - card_exp The card expiration date in yyyymm format
     *  - card_security_code The 3 or 4 digit security code of the card (if available)
     *  - type The credit card type
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
     * @return mixed False on failure or the customer reference ID
     */
    private function createCustomer(array $card_info, array $contact)
    {
        $this->loadApi();

        $customer = [
            'email' => (isset($contact['email']) ? $contact['email'] : null),
            'card' => [
                'number' => (isset($card_info['card_number']) ? $card_info['card_number'] : null),
                'exp_month' => substr((isset($card_info['card_exp']) ? $card_info['card_exp'] : null), 4, 2),
                'exp_year' => substr((isset($card_info['card_exp']) ? $card_info['card_exp'] : null), 0, 4),
                'name' => (isset($card_info['first_name']) ? $card_info['first_name'] : null) . ' ' . (isset($card_info['last_name']) ? $card_info['last_name'] : null),
                'address_line1' => (isset($card_info['address1']) ? $card_info['address1'] : null),
                'address_line2' => (isset($card_info['address2']) ? $card_info['address2'] : null),
                'address_zip' => (isset($card_info['zip']) ? $card_info['zip'] : null),
                'address_state' => (isset($card_info['state']['code']) ? $card_info['state']['code'] : null),
                'address_country' => (isset($card_info['country']['alpha3']) ? $card_info['country']['alpha3'] : null),
                'cvc' => (isset($card_info['card_security_code']) ? $card_info['card_security_code'] : null)
            ]
        ];

        // Attempt to create a customer
        $errors = [];
        $response = new stdClass();
        try {
            $response = Stripe_Customer::create($customer);

            // Re-format the response
            $response = $response->__toArray(true);
        } catch (Stripe_InvalidRequestError $exception) {
            if (isset($exception->json_body)) {
                $response = $exception->json_body;
                $errors = [
                    $response['error']['type'] => [
                        'error' => $response['error']['message']
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        } catch (Stripe_CardError $exception) {
            if (isset($exception->json_body)) {
                $response = $exception->json_body;
                $errors = [
                    $response['error']['type'] => [
                        $response['error']['code'] => $response['error']['message']
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        } catch (Stripe_AuthenticationError $exception) {
            if (isset($exception->json_body)) {
                // Don't use the actual error (as it may contain an API key, albeit invalid),
                // rather a general auth error
                $response = $exception->json_body;
                $errors = [
                    $response->error['type'] => [
                        'auth_error' => Language::_('Stripe_gateway.!error.auth', true)
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        }
        // Any other exception, including Stripe_ApiError
        catch (Exception $e) {
            $errors = $this->getCommonError('general');
        }

        // Set any errors
        if (!empty($errors)) {
            $this->Input->setErrors($errors);
        }

        $response = $this->objectToArray($response);

        // Log the request
        $url = $this->base_url . 'customers';
        $this->logRequest($url, $customer, $response);

        // Return the customer ID
        if (empty($errors)) {
            return $response['id'];
        }
        return false;
    }

    /**
     * Loads the API if not already loaded
     */
    private function loadApi()
    {
        Loader::load(dirname(__FILE__) . DS . 'api' . DS . 'Stripe.php');
        Stripe::setApiKey($this->meta['api_key']);
        Stripe::setVerifySslCerts(false);
    }

    /**
     * Log the request
     *
     * @param string $url The URL of the API request to log
     * @param array The input parameters sent to the gateway
     * @param array The response from the gateway
     */
    private function logRequest($url, $params, $response)
    {
        // Define all fields to mask when logging
        $mask_fields = [
            'number', // CC number
            'exp_month',
            'exp_year',
            'cvc'
        ];

        $response = $this->objectToArray($response);

        // Determine success or failure for the response
        $success = false;
        if (!($errors = $this->Input->errors()) && !isset($response['error'])) {
            $success = true;
        }

        // Log data sent to the gateway
        $this->log(
            $url,
            serialize($this->maskDataRecursive($params, $mask_fields)),
            'input',
            (isset($params['error']) ? false : true)
        );

        // Log response from the gateway
        $this->log($url, serialize($this->maskDataRecursive($response, $mask_fields)), 'output', $success);
    }

    /**
     * Casts multi-dimensional objects to arrays
     *
     * @param mixed $object An object
     * @return array All objects cast to array
     */
    private function objectToArray($object)
    {
        if (is_object($object)) {
            $object = get_object_vars($object);
        }

        // Recurse over object to convert all object keys in $object to array
        if (is_array($object)) {
            return array_map([$this, __FUNCTION__], $object);
        }
        return $object;
    }

    /**
     * Convert amount from decimal value to integer representation of cents
     *
     * @param float $amount
     * @param string $currency
     * @return int The amount in cents
     */
    private function formatAmount($amount, $currency)
    {
        $non_decimal_currencies = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY',
            'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VUV', 'XAF', 'XOF', 'XPF'];

        if (is_numeric($amount) && !in_array($currency, $non_decimal_currencies)) {
            $amount *= 100;
        }
        return (int)round($amount);
    }
}
