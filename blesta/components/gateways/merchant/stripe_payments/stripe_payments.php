<?php
/**
 * Stripe Credit Card processing gateway. Supports offsite payment
 * processing for Credit Cards using the latest secure methods from Stripe.
 *
 * The Stripe API can be found at: https://stripe.com/docs/api
 *
 * @package blesta
 * @subpackage blesta.components.gateways.stripe_payments
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class StripePayments extends MerchantGateway implements MerchantAch, MerchantAchOffsite, MerchantAchVerification, MerchantAchForm, MerchantCc, MerchantCcOffsite, MerchantCcForm
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
        Language::loadLang('stripe_payments', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load product configuration required by this module
        Configure::load('stripe_payments', dirname(__FILE__) . DS . 'config' . DS);

        // Check if Stripe.js is already loaded
        if ($this->global('stripe_js') == null) {
            $this->global('stripe_js', false);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettings(array $meta = null)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('settings', 'default');
        $this->view->setDefaultView('components' . DS . 'gateways' . DS . 'merchant' . DS . 'stripe_payments' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        Loader::loadModels($this, ['GatewayManager']);

        // Check if the old Stripe gateway is installed and see how many cc accounts are linked to it
        $legacy_stripe_installed = false;
        $gateways = $this->GatewayManager->getByClass('stripe_gateway', Configure::get('Blesta.company_id'));
        if (!empty($gateways)) {
            $legacy_stripe_installed = true;

            $record = new Record;
            $accounts_remaining = $record->select()->
                from('accounts_cc')->
                where('gateway_id', '=', $gateways[0]->id)->
                where('reference_id', '!=', null)->
                where('status', '=', 'active')->
                numResults();

            $this->view->set('accounts_remaining', $accounts_remaining);
            $this->view->set('batch_size', Configure::get('StripePayments.migration_batch_size'));
        }

        $this->view->set('legacy_stripe_installed', $legacy_stripe_installed);
        $this->view->set('meta', $meta);

        return $this->view->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function editSettings(array $meta)
    {
        // Validate the given meta data to ensure it meets the requirements
        $rules = [
            'publishable_key' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('StripePayments.!error.publishable_key.empty', true)
                ]
            ],
            'secret_key' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('StripePayments.!error.secret_key.empty', true)
                ],
                'valid' => [
                    'rule' => [[$this, 'validateConnection']],
                    'message' => Language::_('StripePayments.!error.secret_key.valid', true)
                ]
            ]
        ];

        $this->Input->setRules($rules);

        // Migrate accounts
        if ($this->Input->validates($meta) && isset($meta['migrate_accounts'])) {
            $this->migrateLegacyAccounts($meta);
        }

        unset($meta['migrate_accounts']);
        return $meta;
    }

    /**
     * Migrates payment accounts from the old Stripe gateway to the new Stripe Payments gateway
     *
     * @param array $meta An array of meta (settings) data for this gateway
     */
    private function migrateLegacyAccounts(array $meta)
    {
        Loader::loadModels($this, ['GatewayManager']);

        // Get the old Stripe gateway
        $legacy_stripe = $this->GatewayManager->getByClass('stripe_gateway', Configure::get('Blesta.company_id'));
        // Get the new Stripe Payments gateway
        $stripe_payments = $this->GatewayManager->getByClass('stripe_payments', Configure::get('Blesta.company_id'));
        if (!empty($legacy_stripe) && !empty($stripe_payments)) {
            // Get the offsite accounts linked to the old gateway
            $record = new Record;
            $legacy_stripe_accounts = $record->select()->
                from('accounts_cc')->
                where('gateway_id', '=', $legacy_stripe[0]->id)->
                where('reference_id', '!=', null)->
                where('status', '=', 'active')->
                getStatement();

            // Set the meta data for this gateway
            $this->setMeta($meta);
            // Set the ID of the gateway (for logging purposes)
            $this->setGatewayId($stripe_payments[0]->id);

            // Collect reference IDs for all of the old accounts by fetching the customer from stripe
            $accounts_references = [];
            $accounts_collected = 0;
            $batch_size = Configure::get('StripePayments.migration_batch_size');
            foreach ($legacy_stripe_accounts as $legacy_stripe_account) {
                if ($accounts_collected >= $batch_size) {
                    break;
                }

                // Fetch the customer
                $customer = $this->handleApiRequest(
                    ['Stripe\Customer', 'retrieve'],
                    [$legacy_stripe_account->reference_id],
                    $this->base_url . 'customers - retrieve'
                );

                // Determine the customer's card reference ID.
                // The Stripe API has changed over time, so the reference ID may be in any of the following fields
                $card_id = null;
                if (!empty($customer->default_source)) {
                    $card_id = $customer->default_source;
                } elseif (!empty($customer->default_card)) {
                    $card_id = $customer->default_card;
                } elseif (isset($customer->active_card) && isset($customer->active_card->id)) {
                    $card_id = $customer->active_card->id;
                }

                if ($card_id !== null) {
                    // Store the reference IDs
                    $accounts_references[$legacy_stripe_account->id] = [
                        'gateway_id' => $stripe_payments[0]->id,
                        'reference_id' => $card_id,
                        'client_reference_id' => $customer->id,
                    ];
                    $accounts_collected++;
                }
            }
            $record->reset();

            // Update the reference and gateway IDs in Blesta
            foreach ($accounts_references as $account_id => $account_references) {
                $record->where('id', '=', $account_id)->update('accounts_cc', $account_references);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function encryptableFields()
    {
        return ['secret_key'];
    }

    /**
     * {@inheritdoc}
     */
    public function setMeta(array $meta = null)
    {
        $this->meta = $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresCustomerPresent()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function buildCcForm()
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = $this->makeView(
            'cc_form',
            'default',
            str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS)
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Declare to Stripe the possibility of us creating a card PaymentMethod through this page
        // This is confirmed in the view using stripe.handleCardSetup
        $setup_intent = $this->handleApiRequest(
            ['Stripe\SetupIntent', 'create'],
            [],
            $this->base_url . 'setup_intents - create'
        );

        // Check if Stripe.js is already loaded
        $load_stripe = false;
        if (!$this->global('stripe_js')) {
            $this->global('stripe_js', true);
            $load_stripe = true;
        }

        $this->view->set('load_stripe', $load_stripe);
        $this->view->set('setup_intent', $setup_intent);
        $this->view->set('meta', $this->meta);

        return $this->view->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function buildPaymentConfirmation($reference_id, $transaction_id, $amount)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = $this->makeView(
            'payment_confirmation',
            'default',
            str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS)
        );

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $payment_intent = $this->handleApiRequest(
            ['Stripe\PaymentIntent', 'retrieve'],
            [$reference_id],
            $this->base_url . 'payment_intents - retrieve'
        );

        $this->view->set('payment_intent', $payment_intent);
        $this->view->set('meta', $this->meta);

        return $this->view->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function processCc(array $card_info, $amount, array $invoice_amounts = null)
    {
        // The process is the same since both use payment methods and payment intents
        return $this->processStoredCc(
            null,
            $card_info['reference_id'],
            $amount,
            $invoice_amounts,
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function authorizeCc(array $card_info, $amount, array $invoice_amounts = null)
    {
        return $this->authorizeStoredCc(null, $card_info['reference_id'], $amount, $invoice_amounts);
    }

    /**
     * {@inheritdoc}
     */
    public function captureCc($reference_id, $transaction_id, $amount, array $invoice_amounts = null)
    {
        return $this->captureStoredCc(null, null, $reference_id, $transaction_id, $amount, $invoice_amounts);
    }

    /**
     * {@inheritdoc}
     */
    public function voidCc($reference_id, $transaction_id)
    {
        return $this->voidTransaction($reference_id, $transaction_id);
    }

    /**
     * Void a charge
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @return array An array of transaction data including:
     *
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard message for
     *      this transaction status (optional)
     */
    public function voidTransaction($reference_id, $transaction_id)
    {
        // Cancel the PaymentIntent if we don't have a Charge ID yet
        if ($reference_id && !$transaction_id) {
            $payment_intent = $this->handleApiRequest(
                ['Stripe\PaymentIntent', 'retrieve'],
                [$reference_id],
                $this->base_url . 'payment_intents - retrieve'
            );

            // Make sure we actually fetched a valid PaymentIntent
            if ($this->Input->errors()) {
                return;
            }

            // Cancel the PaymentIntent
            $this->handleApiRequest(
                function ($payment_intent) {
                    return $payment_intent->cancel();
                },
                [$payment_intent],
                $this->base_url . 'payment_intents - cancel'
            );

            // Void must be successful
            if ($this->Input->errors()) {
                return;
            }

            // TODO make sure we don't need to do a check on $canceled_payment_intent->status
            // or $canceled_payment_intent->error like we do on card payments

            $response = [
                'status' => 'void',
                'reference_id' => $reference_id,
                'transaction_id' => $transaction_id
            ];
        } else {
            // Refund a previous charge
            $response = $this->refundTransaction($reference_id, $transaction_id, null);
            $response['status'] = 'void';

            // refund must be successful
            if ($this->Input->errors()) {
                return;
            }
        }

        // Set status to void
        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function refundCc($reference_id, $transaction_id, $amount)
    {
        return $this->refundTransaction($reference_id, $transaction_id, $amount);
    }

    /**
     * Refund a charge
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @param float $amount The amount to refund this card
     * @return array An array of transaction data including:
     *
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard message for
     *      this transaction status (optional)
     */
    public function refundTransaction($reference_id, $transaction_id, $amount)
    {
        $refund_params = ['charge' => $transaction_id];
        if ($amount) {
            $refund_params['amount'] = $this->formatAmount($amount, $this->currency);
        }

        $refund = $this->handleApiRequest(
            ['Stripe\Refund', 'create'],
            [$refund_params],
            $this->base_url . 'refunds - create'
        );
        $errors = $this->Input->errors();

        // Get the status from the refund response
        if ($errors || isset($refund->error)) {
            if (empty($errors)) {
                $this->Input->setErrors(
                    ['stripe_error' => ['refund' => (isset($refund->error->message) ? $refund->error->message : null)]]
                );
            }

            return false;
        }

        // Return formatted response
        return [
            'status' => 'refunded',
            'reference_id' => $reference_id,
            'transaction_id' => $transaction_id
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function storeCc(array $card_info, array $contact, $client_reference_id = null)
    {
        // Get the PaymentMethod from Stripe
        $card = $this->handleApiRequest(
            ['Stripe\PaymentMethod', 'retrieve'],
            [(isset($card_info['reference_id']) ? $card_info['reference_id'] : null)],
            $this->base_url . 'payment_methods - retrieve'
        );

        if ($this->Input->errors()) {
            return false;
        }

        // Attach the PaymentMethod to an existing Stripe customer if we have one on record
        $attached = false;
        if ($client_reference_id) {
            // Get the Customer from Stripe
            $customer = $this->handleApiRequest(
                ['Stripe\Customer', 'retrieve'],
                [$client_reference_id],
                $this->base_url . 'customers - retrieve'
            );

            if ($customer && (!isset($customer->deleted) || !$customer->deleted)) {
                $attached = $this->handleApiRequest(
                    function ($customer_id, $card) {
                        return $card->attach(['customer' => $customer_id]);
                    },
                    [(isset($client_reference_id) ? $client_reference_id : null), $card],
                    $this->base_url . 'payment_methods - attach'
                );
            }
        }

        // If we were not able to attach the PaymentMethod to an existing customer then create a new one
        if (!$attached) {
            // Reset errors so that if attaching failed we can still create a new customer and not show errors
            $this->Input->setErrors([]);

            // Set fields for the new customer profile
            $fields = [
                'payment_method' => (isset($card_info['reference_id']) ? $card_info['reference_id'] : null),
                'email' => (isset($contact['email']) ? $contact['email'] : null),
                'name' => (!empty($contact['first_name']) && !empty($contact['last_name'])
                    ? (isset($contact['first_name']) ? $contact['first_name'] : null) . ' ' . (isset($contact['last_name']) ? $contact['last_name'] : null)
                    : '')
            ];
            if (!empty($contact['address1'])) {
                $fields['address'] = [
                    'line1' => (isset($contact['address1']) ? $contact['address1'] : null),
                    'line2' => (isset($contact['address2']) ? $contact['address2'] : null),
                    'city' => (isset($contact['city']) ? $contact['city'] : null),
                    'state' => (isset($contact['state']) ? $contact['state'] : null),
                    'country' => (isset($contact['country']) ? $contact['country'] : null),
                    'postal_code' => (isset($contact['zip']) ? $contact['zip'] : null)
                ];
            }

            $customer = $this->handleApiRequest(
                ['Stripe\Customer', 'create'],
                [$fields],
                $this->base_url . 'customers - create'
            );
        }

        if ($this->Input->errors()) {
            return false;
        }

        // Return the reference IDs and card information
        return [
            'client_reference_id' => (isset($customer->id) ? $customer->id : $client_reference_id),
            'reference_id' => (isset($card_info['reference_id']) ? $card_info['reference_id'] : null),
            'last4' => (isset($card->card->last4) ? $card->card->last4 : null),
            'expiration' => (isset($card->card->exp_year) ? $card->card->exp_year : null)
                . str_pad((isset($card->card->exp_month) ? $card->card->exp_month : null), 2, 0, STR_PAD_LEFT),
            'type' => $this->mapCardType((isset($card->card->brand) ? $card->card->brand : null))
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function updateCc(array $card_info, array $contact, $client_reference_id, $account_reference_id)
    {
        // Add a new payment account to the same client
        $card_data = $this->storeCc($card_info, $contact, $client_reference_id);

        if ($this->Input->errors()) {
            return false;
        }

        // Remove the old payment account if possible
        if (false === $this->removeCc($client_reference_id, $account_reference_id)) {
            // Ignore any errors caused by attempting to remove the old account
            $this->Input->setErrors([]);
        }

        return $card_data;
    }

    /**
     * Executes a given action using the API, handling errors and logging
     *
     * @param callable $api_method The function to execute
     * @param array $params The parameters to pass to the function
     * @param string $log_url The url to associate with the logs for this request
     * @return mixed False on error, other wise an object representing the Stripe response
     */
    private function handleApiRequest($api_method, array $params, $log_url)
    {
        $this->loadApi();

        // Attempt to update the customer's card
        $errors = [];
        $loggable_response = [];
        try {
            $response = call_user_func_array($api_method, $params);

            // Convert the response to a loggable array
            $loggable_response = $response->jsonSerialize();
        } catch (\Stripe\Exception\InvalidRequestException $exception) {
            if (!empty($exception->getJsonBody())) {
                $loggable_response = $exception->getJsonBody();
                $errors = [
                    $loggable_response['error']['type'] => [
                        'error' => $this->formatErrorMessage($loggable_response['error'])
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        } catch (\Stripe\Exception\CardException $exception) {
            if (!empty($exception->getJsonBody())) {
                $loggable_response = $exception->getJsonBody();
                $errors = [
                    $loggable_response['error']['type'] => [
                        $loggable_response['error']['code'] => $this->formatErrorMessage($loggable_response['error'])
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        } catch (\Stripe\Exception\AuthenticationException $exception) {
            if (!empty($exception->getJsonBody())) {
                // Don't use the actual error (as it may contain an API key, albeit invalid),
                // rather a general auth error
                $loggable_response = $exception->getJsonBody();
                $errors = [
                    $loggable_response['error']['type'] => [
                        'auth_error' => Language::_('StripePayments.!error.auth', true)
                    ]
                ];
            } else {
                // Gateway returned an invalid response
                $errors = $this->getCommonError('general');
            }
        } catch (Throwable $e) {
            // Any other exception, including Stripe_ApiError
            $errors = $this->getCommonError('general');
            $loggable_response = ['error' => $e->getMessage()];
        }

        // Set any errors
        if (!empty($errors)) {
            $this->Input->setErrors($this->getCommonError('general'));
        }

        // Log the request
        $this->logRequest($log_url, $params, $loggable_response);

        if (empty($response)) {
            $response = (object) $loggable_response;
            $response->status = 'error';

            if (is_string($loggable_response['error'])) {
                $response->error = (object) ['message' => $loggable_response['error']];
            }
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function removeCc($client_reference_id, $account_reference_id)
    {
        // Get the PaymentMethod from Stripe
        $card = $this->handleApiRequest(
            ['Stripe\PaymentMethod', 'retrieve'],
            [$account_reference_id],
            $this->base_url . 'payment_methods - retrieve'
        );

        if ($this->Input->errors()) {
            return false;
        }

        // Detach the PaymentMethod from it's associated Stripe customer
        $this->handleApiRequest(
            function ($card) {
                return $card->detach();
            },
            [$card],
            $this->base_url . 'payment_methods - detach'
        );

        if ($this->Input->errors()) {
            return false;
        }

        return ['client_reference_id' => $client_reference_id, 'reference_id' => $account_reference_id];
    }

    /**
     * {@inheritdoc}
     */
    public function processStoredCc(
        $client_reference_id,
        $account_reference_id,
        $amount, array
        $invoice_amounts = null,
        $customer_present = false
    )
    {
        // Charge the given PaymentMethod through Stripe
        $charge = [
            'amount' => $this->formatAmount($amount, ($this->currency ?? null)),
            'currency' => ($this->currency ?? null),
            'customer' => $client_reference_id,
            'payment_method' => $account_reference_id,
            'description' => $this->getChargeDescription($invoice_amounts),
            'confirm' => true,
            'off_session' => true
        ];

        if ($customer_present) {
            unset($charge['off_session']);
        }

        $payment = $this->handleApiRequest(
            ['Stripe\PaymentIntent', 'create'],
            [$charge],
            $this->base_url . 'payment_intents - create'
        );
        $errors = $this->Input->errors();

        // Set whether there was an error
        $status = 'error';
        if (
            (is_object($payment) && isset($payment->error) && (($payment->error->code ?? null) === 'card_declined'))
            || (is_array($payment)
                && isset($payment['error'])
                && (($payment['error']['code'] ?? null) === 'card_declined')
            )
        ) {
            $status = 'declined';
        } elseif (
            (!isset($payment->error) && !isset($payment['error']))
            && empty($errors)
            && ($payment->status ?? $payment['status'] ?? null) === 'succeeded'
        ) {
            $status = 'approved';
        } else {
            $message = (is_object($payment) && isset($payment->error))
                ? ($payment->error->message ?? null)
                : ((is_array($payment) && isset($payment['error']['message'])) ? $payment['error']['message'] : null);
        }

        return [
            'status' => $status,
            'reference_id' => null,
            'transaction_id' => ($payment->charges->data[0]->id ?? null),
            'message' => ($message ?? null)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function authorizeStoredCc(
        $client_reference_id,
        $account_reference_id,
        $amount,
        array $invoice_amounts = null
    ) {
        // Create a PaymentIntent through Stripe
        $payment = [
            'amount' => $this->formatAmount($amount, (isset($this->currency) ? $this->currency : null)),
            'currency' => (isset($this->currency) ? $this->currency : null),
            'description' => $this->getChargeDescription($invoice_amounts),
            'payment_method' => $account_reference_id,
            'capture_method' => 'manual',
            'setup_future_usage' => 'off_session'
        ];
        if ($client_reference_id) {
            $payment['customer'] = $client_reference_id;
        }

        // Declare to Stripe the possibility of us creating a payment through this page
        $payment_intent = $this->handleApiRequest(
            ['Stripe\PaymentIntent', 'create'],
            [$payment],
            $this->base_url . 'payment_intents - create'
        );

        if ($this->Input->errors()) {
            return false;
        }

        $status = 'error';
        if (isset($payment_intent->status)) {
            switch ($payment_intent->status) {
                case 'requires_confirmation':
                case 'requires_action':
                case 'requires_source_action':
                case 'processing':
                    $status = 'pending';
                    break;
                case 'canceled':
                    $status = 'declined';
                    break;
                case 'succeeded':
                    $status = 'approved';
                    break;
                case 'requires_payment_method':
                case 'requires_source':
                default:
                    $message = isset($payment_intent->error) ? (isset($payment_intent->error->message) ? $payment_intent->error->message : null) : '';
            }
        }

        return [
            'status' => $status,
            'reference_id' => $payment_intent->id,
            'transaction_id' => null, // This should eventually be filled by the Charge ID
            'message' => (isset($message) ? $message : null)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function captureStoredCc(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id,
        $amount,
        array $invoice_amounts = null
    ) {
        $payment_intent = $this->handleApiRequest(
            ['Stripe\PaymentIntent', 'retrieve'],
            [$transaction_reference_id],
            $this->base_url . 'payment_intents - retrieve'
        );

        if (!empty($payment_intent->charges->data[0]->failure_code)) {
            return [
                'status' => in_array(
                    $payment_intent->charges->data[0]->failure_code,
                    ['card_declined', 'bank_account_declined']
                )
                    ? 'declined'
                    : 'error',
                'reference_id' => ($payment_intent->id ?? null),
                'transaction_id' => ($payment_intent->charges->data[0]->id ?? null),
                'message' => $payment_intent->charges->data[0]->failure_message
            ];
        }

        $captured_payment_intent = $this->handleApiRequest(
            function ($payment_intent) {
                return $payment_intent->capture();
            },
            [$payment_intent],
            $this->base_url . 'payment_intent - capture'
        );

        $status = 'error';
        if (isset($captured_payment_intent->status)) {
            switch ($captured_payment_intent->status) {
                case 'requires_confirmation':
                case 'requires_action':
                case 'requires_source_action':
                case 'processing':
                    $status = 'pending';
                    break;
                case 'canceled':
                case 'requires_payment_method':
                    $status = 'declined';
                    break;
                case 'succeeded':
                    $status = 'approved';
                    break;
                case 'requires_source':
                default:
                    $message = isset($captured_payment_intent->error)
                        ? ($captured_payment_intent->error->message ?? null)
                        : '';
            }
        }

        return [
            'status' => $status,
            'reference_id' => ($captured_payment_intent->id ?? null),
            'transaction_id' => ($captured_payment_intent->charges->data[0]->id ?? null),
            'message' => ($message ?? null)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function voidStoredCc(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id
    ) {
        // Void or refund a previous charge
        $response = $this->voidTransaction($transaction_reference_id, $transaction_id);

        // Operation must be successful
        if ($this->Input->errors()) {
            return;
        }

        // Set status to void
        $response['status'] = 'void';
        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function refundStoredCc(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id,
        $amount
    ) {
        // Return formatted response
        return $this->refundCc($transaction_reference_id, $transaction_id, $amount);
    }

    /**
     * {@inheritdoc}
     */
    public function requiresCcStorage()
    {
        return true;
    }

    /**
     * Loads the API if not already loaded
     */
    private function loadApi()
    {
        Loader::load(dirname(__FILE__) . DS . 'vendor' . DS . 'stripe' . DS . 'stripe-php' . DS . 'init.php');
        Stripe\Stripe::setApiKey((isset($this->meta['secret_key']) ? $this->meta['secret_key'] : null));

        // Include identifying information about this being a gateway for Blesta
        Stripe\Stripe::setAppInfo('Blesta ' . $this->getName(), $this->getVersion(), 'https://blesta.com');
    }

    /**
     * Log the request
     *
     * @param string $url The URL of the API request to log
     * @param array The input parameters sent to the gateway
     * @param array The response from the gateway
     */
    private function logRequest($url, array $params, array $response)
    {
        // Define all fields to mask when logging
        $mask_fields = [
            'number', // CC number
            'exp_month',
            'exp_year',
            'cvc'
        ];

        // Determine success or failure for the response
        $success = false;
        if (!(($errors = $this->Input->errors()) || isset($response['error']))) {
            $success = true;
        }

        // Log data sent to the gateway
        $this->log(
            $url,
            serialize($params),
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
     * @param string $direction
     * @return int The amount in cents
     */
    private function formatAmount($amount, $currency, $direction = 'to')
    {
        $non_decimal_currencies = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY',
            'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VUV', 'XAF', 'XOF', 'XPF'];

        if (is_numeric($amount) && !in_array($currency, $non_decimal_currencies)) {
            if ($direction == 'to') {
                $amount *= 100;
            } else {
                $amount /= 100;
            }
        }
        return (int)round($amount);
    }

    /**
     * Converts the card type from Stripe to the equivalent in Blesta
     *
     * @param string $stripe_card_type The card type from Stripe
     * @return string The card type for Blesta
     */
    private function mapCardType($stripe_card_type)
    {
        $card_type_map = [
            'amex' => 'amex',
            'diners' => 'dc-int',
            'discover' => 'disc',
            'jcb' => 'jcb',
            'mastercard' => 'mc',
            'unionpay' => 'cup',
            'visa' => 'visa',
            'unknown' => 'other'
        ];

        return array_key_exists($stripe_card_type, $card_type_map) ? $card_type_map[$stripe_card_type] : 'other';
    }

    /**
     * Checks whether a key can be used to connect to the Stripe API
     *
     * @param string $secret_key The API to connect with
     * @return boolean True if a successful API call was made, false otherwise
     */
    public function validateConnection($secret_key)
    {
        $success = true;
        try {
            // Attempt to make an API request
            Loader::load(dirname(__FILE__) . DS . 'vendor' . DS . 'stripe' . DS . 'stripe-php' . DS . 'init.php');
            Stripe\Stripe::setApiKey($secret_key);
            Stripe\Balance::retrieve();
        } catch (Exception $e) {
            $success = false;
        }

        return $success;
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
            return Language::_('StripePayments.charge_description_default', true);
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
            return Language::_('StripePayments.charge_description_default', true);
        }

        // Truncate the description to a max of 1000 characters since that is Stripe's limit for the description field
        $description = Language::_('StripePayments.charge_description', true, implode(', ', $id_codes));
        if (strlen($description) > 1000) {
            $description = $string->truncate($description, ['length' => 997]) . '...';
        }

        return $description;
    }

    /**
     * {@inheritdoc}
     */
    public function buildAchForm($account_info = null)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = $this->makeView(
            'ach_form',
            'default',
            str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS)
        );

        // Load the models and helpers required for this view
        Loader::loadModels($this, ['Accounts', 'Companies']);
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Declare to Stripe the possibility of us creating a bank account PaymentMethod through this page
        // This is confirmed in the view using stripe.handleCardSetup
        $setup_intent = $this->handleApiRequest(
            ['Stripe\SetupIntent', 'create'],
            [],
            $this->base_url . 'setup_intents - create'
        );

        // Get bank account, if already exists
        $status = 'new';
        if (!empty($account_info['reference_id']) && !empty($account_info['client_reference_id'])) {
            $account = $this->handleApiRequest(
                ['Stripe\Customer', 'retrieveSource'],
                [$account_info['client_reference_id'], $account_info['reference_id']],
                $this->base_url . 'customers - retrieveSource'
            );

            if ($account->status == 'new') {
                $status = 'unverified';
            }
        }

        // Check if Stripe.js is already loaded
        $load_stripe = false;
        if (!$this->global('stripe_js')) {
            $this->global('stripe_js', true);
            $load_stripe = true;
        }

        // Set select options
        $holder_types = [
            'individual' => Language::_('StripePayments.ach_form.field_holder_type_individual', true),
            'company' => Language::_('StripePayments.ach_form.field_holder_type_company', true)
        ];

        $this->view->set('load_stripe', $load_stripe);
        $this->view->set('setup_intent', $setup_intent);
        $this->view->set('meta', $this->meta);
        $this->view->set('types', $this->Accounts->getAchTypes());
        $this->view->set('status', $status);
        $this->view->set('holder_types', $holder_types);
        $this->view->set('account_info', $account_info);
        $this->view->set('company', $this->Companies->get(Configure::get('Blesta.company_id')));

        return $this->view->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function requiresAchStorage()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function buildAchVerificationForm(array $vars = [])
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function processAch(array $account_info, $amount, array $invoice_amounts = null)
    {
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }

    /**
     * {@inheritdoc}
     */
    public function voidAch($reference_id, $transaction_id)
    {
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }

    /**
     * {@inheritdoc}
     */
    public function refundAch($reference_id, $transaction_id, $amount)
    {
        return $this->refundTransaction($reference_id, $transaction_id, $amount);
    }

    /**
     * {@inheritdoc}
     */
    public function storeAch(array $account_info, array $contact, $client_reference_id = null)
    {
        if ($client_reference_id == null) {
            // Set fields for the new customer profile
            $fields = [
                'source' => ($account_info['reference_id'] ?? null),
                'email' => ($contact['email'] ?? null),
                'name' => (!empty($contact['first_name']) && !empty($contact['last_name']) ?
                    ($contact['first_name'] ?? null) . ' ' . ($contact['last_name'] ?? null) : '')
            ];
            if (!empty($contact['address1'])) {
                $fields['address'] = [
                    'line1' => ($contact['address1'] ?? null),
                    'line2' => ($contact['address2'] ?? null),
                    'city' => ($contact['city'] ?? null),
                    'state' => ($contact['state'] ?? null),
                    'country' => ($contact['country'] ?? null),
                    'postal_code' => ($contact['zip'] ?? null)
                ];
            }
            $customer = $this->handleApiRequest(
                ['Stripe\Customer', 'create'],
                [$fields],
                $this->base_url . 'customers - create'
            );

            if (isset($customer->default_source)) {
                $account_info['reference_id'] = $customer->default_source;
            }
        } else {
            // Attach the bank account to the existing customer
            $customer = $this->handleApiRequest(
                ['Stripe\Customer', 'retrieve'],
                [$client_reference_id],
                $this->base_url . 'customers - retrieve'
            );
            $source = $this->handleApiRequest(
                ['Stripe\Customer', 'createSource'],
                [($customer->id ?? $client_reference_id), ['source' => $account_info['reference_id']]],
                $this->base_url . 'customers - createSource'
            );

            // Fetch the source
            if (isset($source->id)) {
                $account_info['reference_id'] = $source->id;
            }

            if ($this->Input->errors()) {
                return false;
            }
        }

        // Get bank account
        $account = $this->handleApiRequest(
            ['Stripe\Customer', 'retrieveSource'],
            [($customer->id ?? $client_reference_id), $account_info['reference_id']],
            $this->base_url . 'customers - retrieveSource'
        );

        if ($this->Input->errors()) {
            return false;
        }

        // Return the reference IDs and bank account information
        return [
            'client_reference_id' => ($customer->id ?? $client_reference_id),
            'reference_id' => ($account_info['reference_id'] ?? null),
            'last4' => ($account->last4 ?? null)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function updateAch(array $account_info, array $contact, $client_reference_id, $account_reference_id)
    {
        // Add a new bank account to the same client
        $account_data = $this->storeAch($account_info, $contact, $client_reference_id);

        if ($this->Input->errors()) {
            return false;
        }

        // Remove the old payment account if possible
        if (false === $this->removeAch($client_reference_id, $account_reference_id)) {
            // Ignore any errors caused by attempting to remove the old account
            $this->Input->setErrors([]);
        }

        return $account_data;
    }

    /**
     * {@inheritdoc}
     */
    public function verifyAch(array $vars, $client_reference_id = null, $account_reference_id = null)
    {
        // Get bank account
        $account = $this->handleApiRequest(
            ['Stripe\Customer', 'retrieveSource'],
            [$client_reference_id, $account_reference_id],
            $this->base_url . 'customers - retrieveSource'
        );

        if ($this->Input->errors()) {
            return false;
        }

        // Verify bank account
        if (isset($account->customer) && $account->customer == $client_reference_id) {
            try {
                $account->verify(['amounts' => [($vars['first_deposit'] ?? 0), ($vars['second_deposit'] ?? 0)]]);
            } catch (Throwable $e) {
                $this->Input->setErrors(['verify' => ['error' => $e->getMessage()]]);
            }
        }

        if ($this->Input->errors()) {
            return false;
        }

        return [
            'client_reference_id' => $client_reference_id,
            'reference_id' => $account_reference_id
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function removeAch($client_reference_id, $account_reference_id)
    {
        $this->handleApiRequest(
            ['Stripe\Customer', 'deleteSource'],
            [$client_reference_id, $account_reference_id],
            $this->base_url . 'customers - deleteSource'
        );

        if ($this->Input->errors()) {
            return false;
        }

        return [
            'client_reference_id' => $client_reference_id,
            'reference_id' => $account_reference_id
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function processStoredAch(
        $client_reference_id,
        $account_reference_id,
        $amount,
        array $invoice_amounts = null
    )
    {
        // Charge the given PaymentMethod through Stripe
        $charge = [
            'amount' => $this->formatAmount($amount, ($this->currency ?? null)),
            'currency' => ($this->currency ?? null),
            'customer' => $client_reference_id,
            'source' => $account_reference_id,
            'description' => $this->getChargeDescription($invoice_amounts)
        ];

        $payment = $this->handleApiRequest(
            ['Stripe\Charge', 'create'],
            [$charge],
            $this->base_url . 'charges - create'
        );
        $errors = $this->Input->errors();

        if ($errors) {
            return false;
        }

        // Set whether there was an error
        $status = 'error';
        if (isset($payment['error'])) {
            $status = 'declined';
        } elseif (!isset($payment->error)
            && empty($errors)
            && isset($payment->status)
            && $payment->status === 'pending'
        ) {
            $status = 'approved';
        } else {
            $message = isset($payment->error)
                ? ($payment->error->message ?? null)
                : ($payment['error']['message'] ?? '');
        }

        return [
            'status' => $status,
            'reference_id' => ($payment->balance_transaction ?? null),
            'transaction_id' => ($payment->id ?? null),
            'message' => ($message ?? null)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function voidStoredAch(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id
    )
    {
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }

    /**
     * {@inheritdoc}
     */
    public function refundStoredAch(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id,
        $amount
    )
    {
        return $this->refundTransaction($transaction_reference_id, $transaction_id, $amount);
    }

    /**
     * Formats an error message returned by the API
     *
     * @param array $loggable_response A key/value array containing:
     *
     *  - code The error code
     *  - message The error message
     *  - type The type of the error
     * @return string The formatted error message
     */
    private function formatErrorMessage($loggable_response)
    {
        // Check if a language definition exists for this error message
        $lang = Language::_(
            'StripePayments.!error.' . ($loggable_response['code'] ?? $loggable_response['type'] ?? ''),
            true
        );

        if (!empty($lang)) {
            return $lang;
        }

        // If the message contains a URL to Stripe, remove it
        $message_lines = explode('. ', str_replace("\n", '. ', $loggable_response['message']));
        foreach ($message_lines as $line => $message) {
            if (str_contains($message, 'stripe.com')) {
                unset($message_lines[$line]);
            }
        }
        $loggable_response['message'] = trim(implode('. ', $message_lines), '.') . '.';

        return $loggable_response['message'];
    }

    /**
     * Defines or retrieves a global variable
     *
     * @param string $key The name of the global variable
     * @param string $value The value of the global variable (optional)
     * @return mixed The value of the global variable, null if undefined
     */
    private function global($key, $value = null)
    {
        $class = Loader::toCamelCase(get_class($this));
        $key = $class . '.' . $key;

        if (is_null($value)) {
            return $GLOBALS[$key] ?? null;
        } else {
            $GLOBALS[$key] = $value;
        }
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
        // Get event payload
        $payload = @file_get_contents('php://input');
        if (!empty($payload)) {
            $payload = json_decode($payload);
        } else {
            $payload = (object) [];
        }

        // Validate only payment intent events
        if ($payload->data->object->object !== 'charge') {
            return false;
        }

        // Fetch client
        Loader::loadComponents($this, ['Record']);
        $charge_id = $payload->data->object->id ?? $payload->data->object->charges->data[0]->id ?? null;
        $transaction = $this->Record->select()
            ->from('transactions')
                ->open()
                    ->where('transactions.transaction_id', '=', $charge_id)
                    ->orWhere('transactions.reference_id', '=', $charge_id)
                ->close()
            ->fetch();

        if (empty($transaction->client_id)) {
            return false;
        }

        // Get event status
        $status = 'error';
        $stripe_status = $payload->data->object->charges->data[0]->status ?? $payload->data->object->status ?? 'failed';
        if (isset($stripe_status)) {
            switch ($stripe_status) {
                case 'requires_capture':
                case 'pending':
                case 'requires_payment_method':
                    $status = 'pending';
                    break;
                case 'canceled':
                case 'failed':
                    $status = 'declined';
                    break;
                case 'succeeded':
                    $status = 'approved';
                    break;
            }
        }

        return [
            'client_id' => $transaction->client_id,
            'amount' => $this->formatAmount(
                $payload->data->object->amount ?? $payload->data->object->amount_captured ?? 0,
                strtoupper($payload->data->object->currency ?? ''),
                'from'
            ),
            'currency' => strtoupper($payload->data->object->currency) ?? null,
            'status' => $status,
            'reference_id' => $transaction->reference_id,
            'transaction_id' => $transaction->transaction_id,
            'message' => $payload->data->object->failure_message ?? null
        ];
    }
}
