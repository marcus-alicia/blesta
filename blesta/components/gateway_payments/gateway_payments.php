<?php
/**
 * Processes payments through gateways, records their transactions and sends out
 * email notices when payments are successfully processed all according to the
 * client and company settings for the client and company in question
 *
 * @package blesta
 * @subpackage blesta.components.gateway_payments
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class GatewayPayments
{

    /**
     * @var int The current company, set when this object is initialized
     */
    private $company_id;
    /**
     * @var string The passphrase used to decrypt account information (optional, only used for added security)
     */
    private $passphrase = null;

    /**
     * Initializes the object, loading the dependent components and models
     */
    public function __construct()
    {
        $this->company_id = Configure::get('Blesta.company_id');
        Language::loadLang('gateway_payments');

        Loader::loadComponents($this, ['Gateways', 'Input']);
        Loader::loadHelpers($this, ['DataStructure', 'Date', 'CurrencyFormat' => [$this->company_id]]);
        Loader::loadModels(
            $this,
            [
                'Accounts',
                'Clients',
                'Companies',
                'Contacts',
                'Emails',
                'GatewayManager',
                'Transactions',
                'Countries',
                'States'
            ]
        );
    }

    /**
     * Sets the passphrase to be used when processing payments on existing accounts.
     * This is the value used to encrypt the private key that is used to decrypt account details.
     *
     * @param string $passphrase The passphrase to set
     */
    public function setPassphrase($passphrase)
    {
        $this->passphrase = $passphrase;
    }

    /**
     * Initializes the gateway and readies it for use
     *
     * @param stdClass $gateway A stdClass object representing the installed gateway
     * @param string $currency The ISO 4217 currency code to process
     */
    private function initGateway($gateway, $currency = null)
    {

        // Convert numerically indexed meta data to a key/value pair array
        $ArrayHelper = $this->DataStructure->create('Array');
        $meta = $ArrayHelper->numericToKey($gateway->meta, 'key', 'value');

        // Instantiate the gateway object
        $gateway_obj = $this->Gateways->create($gateway->class, $gateway->type);
        // Set the meta data for this gateway
        $gateway_obj->setMeta($meta);
        // Set the ID of the gateway (for logging purposes)
        $gateway_obj->setGatewayId($gateway->id);
        // Set the currency to be used when processing
        $gateway_obj->setCurrency($currency);

        return $gateway_obj;
    }

    /**
     * Returns an array of HTML markup used to render capture and pay requests for non-merchant gateways
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
     * @param string $currency The current to charge
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
     *      - period The recurring period (day, week, month, year, onetime) used
     *        in conjunction with term in order to determine the next recurring payment
     * @param int $gateway_id The ID of the nonmerchant gateway to fetch,
     *  if null will return all nonmerchant gateways for the currency
     * @return array An array of HTML, indexed by the gateway name
     */
    public function getBuildProcess(
        array $contact_info,
        $amount,
        $currency,
        array $invoice_amounts = null,
        array $options = null,
        $gateway_id = null
    ) {
        $html = [];
        if ($gateway_id !== null) {
            $gateways = [
                $this->GatewayManager->getInstalledNonmerchant($this->company_id, null, $gateway_id, $currency)
            ];
        } else {
            $gateways = $this->GatewayManager->getAllInstalledNonmerchant($this->company_id, $currency);
        }

        foreach ($gateways as $gateway) {
            // Initialize the gateway
            $gateway_obj = $this->initGateway($gateway, $currency);
            $html[$gateway->class] = (array)$gateway_obj->buildProcess(
                $contact_info,
                $amount,
                $invoice_amounts,
                $options
            );

            if (($errors = $gateway_obj->errors())) {
                $this->Input->setErrors($errors);
            }
        }

        return $html;
    }

    /**
     * Returns HTML markup used to render a custom credit card form for a merchant gateway
     *
     * @param string $currency The currency to charge
     * @return string Custom cc form HTML from the merchant
     */
    public function getBuildCcForm($currency)
    {
        $html = '';
        $gateway = $this->GatewayManager->getInstalledMerchant($this->company_id, $currency);

        $errors = null;
        if ($gateway) {
            // Initialize the gateway
            $gateway_obj = $this->initGateway($gateway, $currency);

            if ($gateway_obj instanceof MerchantCcForm) {
                $html = $gateway_obj->buildCcForm();
                $errors = $gateway_obj->errors();
            }
        }

        if ($errors) {
            $this->Input->setErrors($errors);
        }

        return $html;
    }

    /**
     * Returns HTML markup used to render a custom ACH form for a merchant gateway
     *
     * @param string $currency The currency to charge
     * @param array $account_info An array of bank account info (optional)
     * @return string Custom ach form HTML from the merchant
     */
    public function getBuildAchForm($currency, $account_info = null)
    {
        $html = '';
        $gateway = $this->GatewayManager->getInstalledMerchant($this->company_id, $currency);

        $errors = null;
        if ($gateway) {
            // Initialize the gateway
            $gateway_obj = $this->initGateway($gateway, $currency);

            if ($gateway_obj instanceof MerchantAchForm) {
                $html = $gateway_obj->buildAchForm($account_info);
                $errors = $gateway_obj->errors();
            }
        }

        if ($errors) {
            $this->Input->setErrors($errors);
        }

        return $html;
    }

    /**
     * Returns HTML markup used to render a custom verification ach form for a merchant gateway
     *
     * @param string $currency The currency the payment is in
     * @param array $vars An array including:
     *
     *  - first_deposit The first deposit amount
     *  - second_deposit The second deposit amount
     * @return string Custom ACH form HTML from the merchant
     */
    public function getBuildAchVerificationForm($currency, $vars = null)
    {
        $html = '';
        $gateway = $this->GatewayManager->getInstalledMerchant($this->company_id, $currency);

        $errors = null;
        if ($gateway) {
            // Initialize the gateway
            $gateway_obj = $this->initGateway($gateway, $currency);

            if ($gateway_obj instanceof MerchantAchVerification) {
                $html = $gateway_obj->buildAchVerificationForm($vars);
                $errors = $gateway_obj->errors();
            }
        }

        if ($errors) {
            $this->Input->setErrors($errors);
        }

        return $html;
    }

    /**
     * Gets an Html form from the merchant gateway to use as an addition to the regular payment confirmation
     * pages in Blesta
     *
     * @param int $client_id The ID of the client that payment confirmation is being viewed for
     * @param int $transaction_id The ID of the transaction being confirmed
     * @return string The Html, if any, provided by the merchant gateway for confirming payments
     */
    public function getBuildPaymentConfirmation($client_id, $transaction_id)
    {
        // Fetch the transaction to be captured
        $transaction = $this->Transactions->get($transaction_id);

        // Verify transaction exists and belongs to this client
        if (!$transaction || $transaction->client_id != $client_id) {
            $this->Input->setErrors($this->transactionExistsError());
            return;
        }

        $html = '';

        // Fetch the gateway used for the confirmation
        $gateway = $this->GatewayManager->getInstalledMerchant(
            $this->company_id,
            $transaction->currency,
            $transaction->gateway_id
        );

        // Verify that the gateway exists
        if (!$gateway) {
            $this->Input->setErrors($this->gatewayExistsError());
            return;
        }

        $errors = null;

        // Initialize the gateway
        $gateway_obj = $this->initGateway($gateway, $transaction->currency);

        if ($gateway_obj instanceof MerchantCcForm) {
            $html = $gateway_obj->buildPaymentConfirmation(
                $transaction->reference_id,
                $transaction->transaction_id,
                $transaction->amount
            );
            $errors = $gateway_obj->errors();
        }

        if ($errors) {
            $this->Input->setErrors($errors);
        }

        return $html;
    }

    /**
     * Returns an array of HTML markup used to render authorization requests for non-merchant gateways
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
     * @param string $currency The current to charge
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
     *      - period The recurring period (day, week, month, year, onetime) used
     *        in conjunction with term in order to determine the next recurring payment
     * @param int $gateway_id The ID of the nonmerchant gateway to fetch, if null
     *  will return all nonmerchant gateways for the currency
     * @return array An array of HTML, indexed by the gateway name
     */
    public function getBuildAuthorize(
        array $contact_info,
        $amount,
        $currency,
        array $invoice_amounts = null,
        array $options = null,
        $gateway_id = null
    ) {
        $html = [];
        if ($gateway_id !== null) {
            $gateways = [
                $this->GatewayManager->getInstalledNonmerchant($this->company_id, null, $gateway_id, $currency)
            ];
        } else {
            $gateways = $this->GatewayManager->getAllInstalledNonmerchant($this->company_id, $currency);
        }

        foreach ($gateways as $gateway) {
            // Initialize the gateway
            $gateway_obj = $this->initGateway($gateway, $currency);

            $html[$gateway->class] = (array)$gateway_obj->buildAuthorize(
                $contact_info,
                $amount,
                $invoice_amounts,
                $options
            );
        }

        return $html;
    }

    /**
     * Process the payment received request, user redirected back after successful payment.
     *
     * @param string $gateway_name The file name of the gateway excluding any extension
     * @param array An array of GET parameters passed into the callback request
     * @param array An array of POST parameters passed into the callback request
     * @return array An array of transaction data, may set errors using Input if the data appears invalid:
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
     * @see GatewayPayments::processReceived()
     */
    public function processReceived($gateway_name, array $get, array $post)
    {
        $gateway = $this->GatewayManager->getInstalledNonmerchant($this->company_id, $gateway_name);

        // Verify that the gateway exists
        if (!$gateway) {
            $this->Input->setErrors($this->gatewayExistsError());
            return;
        }

        // Initialize the gateway without currency because we don't know it yet.
        $gateway_obj = $this->initGateway($gateway);

        // Attempt to validate the notification
        $response = $gateway_obj->success($get, $post);

        if (($errors = $gateway_obj->errors())) {
            $this->Input->setErrors($errors);
        }

        return $response;
    }

    /**
     * Process the notification request from a remote gateway. Records the transaction (if valid),
     * and sends out the necessary emails.
     *
     * @param string $gateway_name The file name of the gateway excluding any extension
     * @param array An array of GET parameters passed into the callback request
     * @param array An array of POST parameters passed into the callback request
     * @param string The type of gateway, it can be nonmerchant or merchant, nonmerchant by default
     * @return object An object representing the transaction if attempted, void otherwise.
     *  Check GatewayPayments::errors(), as some transactions may be attempted and yet still produce errors
     */
    public function processNotification($gateway_name, array $get, array $post, $type = 'nonmerchant')
    {
        if ($type == 'nonmerchant') {
            $gateway = $this->GatewayManager->getInstalledNonmerchant($this->company_id, $gateway_name);
        } elseif ($type == 'merchant') {
            $gateway = $this->GatewayManager->getInstalledMerchant($this->company_id, null, null, $gateway_name);
        }

        // Verify that the gateway exists
        if (!$gateway) {
            $this->Input->setErrors($this->gatewayExistsError());
            return;
        }

        // Initialize the gateway without currency because we don't know it yet.
        $gateway_obj = $this->initGateway($gateway);

        // Attempt to validate the notification
        if (method_exists($gateway_obj, 'validate')) {
            $response = $gateway_obj->validate($get, $post);
        } else {
            return;
        }

        // If a response was given, record the transaction
        if (is_array($response)) {
            // Check if a transaction already exists on the system for this notification
            $transaction = $this->Transactions->getByTransactionId($response['transaction_id'] ?? null);

            // Set the transaction type
            $response['type'] = 'other';
            if ($gateway_obj instanceof MerchantCc && !($gateway_obj instanceof MerchantAch)) {
                $response['type'] = 'cc';
            }
            if ($gateway_obj instanceof MerchantAch && !($gateway_obj instanceof MerchantCc)) {
                $response['type'] = 'ach';
            }
            if ($gateway_obj instanceof MerchantAch && $gateway_obj instanceof MerchantCc && isset($transaction->type)) {
                $response['type'] = $transaction->type;
            }

            // Set transaction data
            $trans_data = [
                'client_id' => $response['client_id'],
                'amount' => $response['amount'],
                'currency' => $response['currency'],
                'type' => $response['type'],
                'gateway_id' => $gateway->id,
                'transaction_id' => $response['transaction_id'] ?? null,
                'reference_id' => $response['reference_id'] ?? null,
                'message' => substr($response['message'] ?? '', 0, 255),
                'parent_transaction_id' => $response['parent_transaction_id'] ?? null,
                'status' => $response['status']
            ];

            // Fetch the parent transaction (if available)
            $parent_transaction = null;
            if ($trans_data['parent_transaction_id']) {
                $parent_transaction = $this->Transactions->getByTransactionId(
                    $trans_data['parent_transaction_id'],
                    $trans_data['client_id'],
                    $gateway->id
                );
            }

            // Fetch the existing transaction in the system for this gateway (if available)
            $transaction = null;
            if ($trans_data['transaction_id']) {
                $transaction = $this->Transactions->getByTransactionId(
                    $trans_data['transaction_id'],
                    null,
                    $gateway->id
                );
            }

            // Handle refunds where a separate approved parent transaction exists
            if ($trans_data['status'] == 'refunded' && $parent_transaction) {
                // Handle FULL refund by updating the parent transaction
                if ($parent_transaction->status == 'approved' &&
                    (string)$this->CurrencyFormat->cast($parent_transaction->amount, $parent_transaction->currency) ==
                    (string)$this->CurrencyFormat->cast(abs($trans_data['amount']), $trans_data['currency'])) {
                    // Don't update amount, it's the same
                        unset($trans_data['amount']);
                        // Don't update transaction_id
                        unset($trans_data['transaction_id']);
                        // Don't update parent_transaction_id
                        unset($trans_data['parent_transaction_id']);
                        // Update parent transaction record
                        $transaction = $parent_transaction;
                } elseif ($parent_transaction->status != 'approved') {
                    // Can not handle partial refunds on unapproved transactions
                    $this->Input->setErrors($this->unexpectedStatusError());
                    return;
                } else {
                    // Handle PARTIAL refund by adding a new approved negative transaction
                    if ($trans_data['amount'] > 0) {
                        $trans_data['amount'] *= -1;
                    }
                    $trans_data['status'] == 'approved';
                }
            }

            // If the transaction exists, update it
            if ($transaction) {
                // Don't update client_id to prevent transaction from being reassigned
                unset($trans_data['client_id']);

                $this->Transactions->edit($transaction->id, $trans_data);
                $transaction_id = $transaction->id;
            } elseif ($type == 'nonmerchant') {
                // Add the transaction
                $transaction_id = $this->Transactions->add($trans_data);
            }
        } elseif (($errors = $gateway_obj->errors())) {
            // If no response given and errors set, pass those errors along
            $this->Input->setErrors($errors);
            return;
        }

        // Set any errors with adding the transaction
        if (($errors = $this->Transactions->errors())) {
            $this->Input->setErrors($errors);
        } else {
            // Apply the transaction to the invoices given (if any)
            if (isset($response['invoices']) && is_array($response['invoices'])) {
                // Format invoices into something suitable for Transactions::apply()
                foreach ($response['invoices'] as &$invoice) {
                    $invoice['invoice_id'] = $invoice['id'];
                    unset($invoice['id']);
                }

                if (!empty($response['invoices']) && $response['status'] == 'approved') {
                    $this->Transactions->apply($transaction_id, ['amounts' => $response['invoices']]);
                }
            }

            $transaction = $this->Transactions->get($transaction_id);

            // Send an email regarding the non-merchant payment received
            if (isset($response['status']) && isset($response['client_id']) &&
                $response['status'] == 'approved' && $transaction &&
                ($client = $this->Clients->get($response['client_id'])) &&
                $type == 'nonmerchant'
            ) {
                // Set date helper info
                $this->Date->setTimezone('UTC', Configure::get('Blesta.company_timezone'));
                $this->Date->setFormats([
                    'date_time' => $this->Companies
                        ->getSetting(Configure::get('Blesta.company_id'), 'datetime_format')
                        ->value
                ]);

                $amount = $this->CurrencyFormat->format($transaction->amount, $transaction->currency);

                $tags = [
                    'contact' => $client,
                    'transaction' => $transaction,
                    'date_added' => $this->Date->cast($transaction->date_added, 'date_time')
                ];

                $this->Emails->send(
                    'payment_nonmerchant_approved',
                    $this->company_id,
                    $client->settings['language'],
                    $client->email,
                    $tags,
                    null,
                    null,
                    null,
                    ['to_client_id' => $client->id]
                );
            }

            return $transaction;
        }
    }

    /**
     * Processes a payment for the given client of the given type using the supplied details.
     * This method submits payment to the gateway, records the transaction, and
     * sends out any necessary emails
     *
     * @param int $client_id The ID of the client to submit the payment for
     * @param string $type The type of payment to submit "cc" or "ach"
     * @param float $amount The amount to process
     * @param string $currency The ISO 4217 currency code this payment is to be processed under
     * @param array $account_info An array of account info if no $account_id used, including:
     *
     *  - first_name The first name on the account
     *  - last_name The last name on the account
     *  - account_number The bank account number (if ach)
     *  - routing_number The bank account routing number (if ach)
     *  - type The bank account type (checking, savings, business_checking) (if ach)
     *  - card_number The credit card number (if cc)
     *  - card_exp The card expiration date in yyyymm format (if cc)
     *  - card_security_code The card security code (if cc)
     *  - address1 The address 1 line of the account holder
     *  - address2 The address 2 line of the account holder
     *  - city The city of the account holder
     *  - state The 2 or 3 character state code
     *  - country The 2 or 3 character country code
     *  - zip The zip/postal code of the account holder
     *  - reference_id The reference ID attached to this account given by the payment processor (if cc) (optional)
     *  - client_reference_id The reference ID for the client this payment account belongs to (if cc) (optional)
     * @param int $account_id The account ID to be used to process the payment if no $account_info given
     * @param array $options An array of options including (optional):
     *
     *  - invoices An array of key/value pairs where each key represents an invoice ID and each value is the amount to
     *      apply to that invoice (optional, may not exceed $amount)
     *  - staff_id The ID of the staff member that processed this payment
     *  - email_receipt If true (default true), will send an email receipt to
     *      the client and BCC appropriate staff members
     * @return object An object representing the transaction if attempted, void otherwise.
     *  Check GatewayPayments::errors(), as some transactions may be attempted and yet still produce errors
     */
    public function processPayment(
        $client_id,
        $type,
        $amount,
        $currency,
        array $account_info = null,
        $account_id = null,
        array $options = null
    ) {
        $amount = $this->CurrencyFormat->cast($amount, $currency);

        $default_options = [
            'invoices' => null,
            'staff_id' => null,
            'email_receipt' => true
        ];
        $options = array_merge($default_options, (array)$options);

        $contact_id = null; // Hold the ID of the contact to email if given $account_id
        $stored = false; // Flag whether or not this payment is to be processed using the stored payment account methods

        // Set account info and gateway being used
        $gateway = false;
        if (empty($account_info)) {
            if ($type == 'cc') {
                $account = $this->Accounts->getCc($account_id, true, $this->passphrase, $options['staff_id']);
            } elseif ($type == 'ach') {
                $account = $this->Accounts->getAch($account_id, true, $this->passphrase, $options['staff_id']);
            }

            // Verify the account exists and belongs to this client
            if (!$account || $account->client_id != $client_id) {
                $this->Input->setErrors($this->accountExistsError());
                return;
            }

            $contact_id = $account->contact_id;
            $account_info = $this->accountToAccountInfo($account, $type);

            // If gateway ID give, must be processed using stored payment account methods
            if ($account->gateway_id) {
                $stored = true;
            }
        } else {
            if ($type == 'cc') {
                $account_info['type'] = $this->Accounts->creditCardType($account_info['card_number']);
            }
        }

        // Convert 2 or 3-char country to country array
        $account_info['country'] = (array)$this->Countries->get($account_info['country']);
        // Convert 2 or 3-char state to state array
        $account_info['state'] = (array)$this->States->get(
            isset($account_info['country']['alpha2']) ? $account_info['country']['alpha2'] : null,
            $account_info['state']
        );

        // Fetch the gateway to be used with this company and currency
        if ($stored) {
            $gateway = $this->GatewayManager->getInstalledMerchant($this->company_id, $currency, $account->gateway_id);
        } else {
            $gateway = $this->GatewayManager->getInstalledMerchant($this->company_id, $currency);
        }

        // Verify that the gateway exists
        if (!$gateway) {
            $this->Input->setErrors($this->gatewayExistsError());
            return;
        }

        $gateway_obj = $this->initGateway($gateway, $currency);

        // Set the ID of the staff user processing the payment (if any, for logging purposes)
        if (isset($options['staff_id'])) {
            $gateway_obj->setStaffId($options['staff_id']);
        }

        $apply_amounts = $this->invoiceArrayToAmounts($options['invoices']);
        unset($options['invoices']);

        $vars = [
            'client_id' => $client_id,
            'type' => $type,
            'amount' => $amount,
            'currency' => $currency,
            'apply_amounts' => $apply_amounts
        ];

        $this->Input->setRules($this->processPaymentRules($gateway_obj, 'ach|cc', $stored));

        // Ensure error checking passes
        if ($this->Input->validates($vars)) {
            switch ($type) {
                case 'cc':
                    if ($stored) {
                        $response = $gateway_obj->processStoredCc(
                            $account->client_reference_id,
                            $account->reference_id,
                            $amount,
                            $apply_amounts
                        );
                    } else {
                        $response = $gateway_obj->processCc($account_info, $amount, $apply_amounts);
                    }
                    break;
                case 'ach':
                    if ($stored) {
                        $response = $gateway_obj->processStoredAch(
                            $account->client_reference_id,
                            $account->reference_id,
                            $amount,
                            $apply_amounts
                        );
                    } else {
                        $response = $gateway_obj->processAch($account_info, $amount, $apply_amounts);
                    }
                    break;
            }

            // Set any errors with the gateway
            if (($errors = $gateway_obj->errors())) {
                $this->Input->setErrors($errors);

                if (!$response) {
                    return;
                }
            }

            $transaction_id = $this->Transactions->add([
                'client_id' => $client_id,
                'amount' => $amount,
                'currency' => $currency,
                'type' => $type,
                'account_id' => $account_id,
                'gateway_id' => $gateway->id,
                'transaction_id' => $response['transaction_id'],
                'reference_id' => $response['reference_id'] ?? null,
                'message' => substr($response['message'] ?? '', 0, 255),
                'status' => $response['status']
            ]);

            if (($errors = $this->Transactions->errors())) {
                $this->Input->setErrors($errors);
            } else {
                // Now that the payment has been processed and recorded, apply it to the given
                // invoices
                if (!empty($apply_amounts) && $response['status'] == 'approved') {
                    $this->Transactions->apply($transaction_id, ['amounts' => $apply_amounts]);
                }

                // Set processing error(s)
                if ($response['status'] == 'declined' || $response['status'] == 'error') {
                    $this->setProcessingError($response);
                }

                // Increment auto debit failure count on decline
                if ($response['status'] == 'declined') {
                    $this->Clients->setDebitAccountFailure($client_id, $type, $account_id);
                } elseif ($response['status'] == 'approved') {
                    // Reset auto debit failure count on success
                    $this->Clients->resetDebitAccountFailure($client_id, $type, $account_id);
                }

                // If set to send emails fetch the client and send the email
                if ($options['email_receipt']) {
                    $client = $this->Clients->get($client_id);
                    // Default contact to this client (just in case no contact specified)
                    $contact = $client;
                    $email = null;
                    if ($contact_id) {
                        $contact = $this->Contacts->get($contact_id);
                        $email = $contact->email;
                    } else {
                        $email = $client->email;
                    }

                    $tags = [
                        'contact' => $contact,
                        'company' => $this->Companies->get($client->company_id),
                        'amount' => $this->CurrencyFormat->format($amount, $currency),
                        'response' => (object)$response,
                        'last_four' => $this->getLast4($account_info, $type)
                    ];

                    // Set credit card specific tags
                    if ($type == 'cc') {
                        $card_types = $this->Accounts->getCcTypes();
                        $tags['card_type'] = isset($card_types[$account_info['type']])
                            ? $card_types[$account_info['type']]
                            : null;
                    } else {
                        // Set bank account specific tags
                        $account_types = $this->Accounts->getAchTypes();
                        $tags['account_type'] = isset($account_types[$account_info['type']])
                            ? $account_types[$account_info['type']]
                            : null;
                    }

                    $this->Emails->send(
                        'payment_' . $type . '_' . $response['status'],
                        $this->company_id,
                        $client->settings['language'],
                        $email,
                        $tags,
                        null,
                        null,
                        null,
                        ['to_client_id' => $client->id, 'from_staff_id' => $options['staff_id']]
                    );
                }

                return $this->Transactions->get($transaction_id);
            }
        }
    }

    /**
     * Authorizes a payment for the given client of the given type using the supplied details.
     * This method submits payment to the gateway and records the transaction
     *
     * @param int $client_id The ID of the client to authorize the payment for
     * @param string $type The type of payment to submit "cc" or "ach", NOTE: Only "cc" is supported, but this
     *  varaible remains for consistency (and possible future support)
     * @param float $amount The amount to authorize
     * @param string $currency The ISO 4217 currency code this payment is to be processed under
     * @param array $account_info An array of account info if no $account_id used, including:
     *
     *  - first_name The first name on the account
     *  - last_name The last name on the account
     *  - account_number The bank account number (if ach)
     *  - routing_number The bank account routing number (if ach)
     *  - type The bank account type (checking, savings, business_checking) (if ach)
     *  - card_number The credit card number (if cc)
     *  - card_exp The card expiration date in yyyymm format (if cc)
     *  - card_security_code The card security code (if cc)
     *  - address1 The address 1 line of the account holder
     *  - address2 The address 2 line of the account holder
     *  - city The city of the account holder
     *  - state The 2 or 3 character state code
     *  - country The 2 or 3 character country code
     *  - zip The zip/postal code of the account holder
     * @param int $account_id The account ID to be used to process the payment if no $account_info given
     * @param array $options An array of options including (optional):
     *
     *  - invoices An array of key/value pairs where each key represents an invoice ID and each value is the amount to
     *      apply to that invoice (optional, may not exceed $amount)
     *  - staff_id The ID of the staff member that processed this payment
     *  - email_receipt If true (default true), will send an email receipt to the client and BCC appropriate staff
     *      members
     * @return object An object representing the transaction if attempted, void otherwise.
     *  Check GatewayPayments::errors(), as some transactions may be attempted and yet still produce errors
     */
    public function authorizePayment(
        $client_id,
        $type,
        $amount,
        $currency,
        array $account_info = null,
        $account_id = null,
        array $options = null
    ) {
        $amount = $this->CurrencyFormat->cast($amount, $currency);

        $default_options = [
            'invoices' => null,
            'staff_id' => null,
            'email_receipt' => true
        ];
        $options = array_merge($default_options, (array)$options);

        $contact_id = null; // Hold the ID of the contact to email if given $account_id
        $stored = false; // Flag whether or not this payment is to be processed using the stored payment account methods

        // Set account info and gateway being used
        $gateway = false;
        if (empty($account_info)) {
            if ($type == 'cc') {
                $account = $this->Accounts->getCc($account_id, true, $this->passphrase, $options['staff_id']);
            } elseif ($type == 'ach') {
                $account = $this->Accounts->getAch($account_id, true, $this->passphrase, $options['staff_id']);
            }

            // Verify the account exists and belongs to this client
            if (!$account || $account->client_id != $client_id) {
                $this->Input->setErrors($this->accountExistsError());
                return;
            }

            $contact_id = $account->contact_id;
            $account_info = $this->accountToAccountInfo($account, $type);

            // If this is a stored account, fetch that gateway
            if ($account->gateway_id) {
                $stored = true;
            } // must be processed using stored payment account methods
        } else {
            if ($type == 'cc') {
                $account_info['type'] = $this->Accounts->creditCardType($account_info['card_number']);
            }
        }

        // Convert 2 or 3-char country to country array
        $account_info['country'] = (array)$this->Countries->get($account_info['country']);
        // Convert 2 or 3-char state to state array
        $account_info['state'] = (array)$this->States->get(
            isset($account_info['country']['alpha2']) ? $account_info['country']['alpha2'] : null,
            $account_info['state']
        );

        // Fetch the gateway to be used with this company and currency
        if ($stored) {
            $gateway = $this->GatewayManager->getInstalledMerchant($this->company_id, $currency, $account->gateway_id);
        } else {
            $gateway = $this->GatewayManager->getInstalledMerchant($this->company_id, $currency);
        }

        // Verify that the gateway exists
        if (!$gateway) {
            $this->Input->setErrors($this->gatewayExistsError());
            return;
        }

        $gateway_obj = $this->initGateway($gateway, $currency);

        // Set the ID of the staff user processing the payment (if any, for logging purposes)
        if (isset($options['staff_id'])) {
            $gateway_obj->setStaffId($options['staff_id']);
        }

        $apply_amounts = $this->invoiceArrayToAmounts($options['invoices']);
        unset($options['invoices']);

        $vars = [
            'client_id' => $client_id,
            'type' => $type,
            'amount' => $amount,
            'currency' => $currency
        ];

        $this->Input->setRules($this->processPaymentRules($gateway_obj, 'cc', $stored));

        // Ensure error checking passes
        if ($this->Input->validates($vars)) {
            switch ($type) {
                case 'cc':
                    if ($stored) {
                        $response = $gateway_obj->authorizeStoredCc(
                            $account->client_reference_id,
                            $account->reference_id,
                            $amount,
                            $apply_amounts
                        );
                    } else {
                        $response = $gateway_obj->authorizeCc($account_info, $amount, $apply_amounts);
                    }
                    break;
            }

            // Set any errors with the gateway
            if (($errors = $gateway_obj->errors())) {
                $this->Input->setErrors($errors);

                if (!$response) {
                    return;
                }
            }

            $transaction_id = $this->Transactions->add([
                'client_id' => $client_id,
                'amount' => $amount,
                'currency' => $currency,
                'type' => $type,
                'gateway_id' => $gateway->id,
                'account_id' => $account_id,
                'transaction_id' => $response['transaction_id'],
                'reference_id' => $response['reference_id'] ?? null,
                'message' => substr($response['message'] ?? '', 0, 255),
                'status' => $response['status']
            ]);

            if (($errors = $this->Transactions->errors())) {
                $this->Input->setErrors($errors);
                return;
            }

            // Set processing error(s)
            if ($response['status'] == 'declined' || $response['status'] == 'error') {
                $this->setProcessingError($response);
            }

            return $this->Transactions->get($transaction_id);
        }
    }

    /**
     * Captures a payment for the given client and transaction in the supplied amount.
     * This method submits payment to the gateway, updates the transaction, and
     * sends out any necessary emails
     *
     * @param int $client_id The ID of the client to capture the payment for
     * @param int $transaction_id The ID of the transactiosn to capture
     * @param float $amount The amount to capture
     * @param array $options An array of options including (optional):
     *
     *  - invoices An array of key/value pairs where each key represents an invoice ID and each value is the amount
     *      to apply to that invoice (optional, may not exceed $amount)
     *  - staff_id The ID of the staff member that processed this payment
     *  - email_receipt If true (default true), will send an email receipt to the client and BCC appropriate staff
     *      members
     * @return object An object representing the transaction if attempted, void otherwise.
     *  Check GatewayPayments::errors(), as some transactions may be attempted and yet still produce errors
     */
    public function capturePayment($client_id, $transaction_id, $amount = null, array $options = null)
    {
        $default_options = [
            'invoices' => null,
            'staff_id' => null,
            'email_receipt' => true
        ];
        $options = array_merge($default_options, (array)$options);

        $contact_id = null; // Hold the ID of the contact to email if given $account_id
        $stored = false; // Flag whether or not this payment is to be processed using the stored payment account methods

        // Set account info and gateway being used
        $gateway = false;

        // Fetch the transaction to be captured
        $transaction = $this->Transactions->get($transaction_id);

        // Verify transaction exists and belongs to this client
        if (!$transaction || $transaction->client_id != $client_id) {
            $this->Input->setErrors($this->transactionExistsError());
            return;
        }

        // If no amount given, capture the full amount of the previous authorization
        if ($amount == null) {
            $amount = $transaction->amount;
        }

        $amount = $this->CurrencyFormat->cast($amount, $transaction->currency);

        // Fetch the account if one was used for this transaction
        if ($transaction->type == 'cc') {
            $account = $this->Accounts->getCc($transaction->account_id);
        } elseif ($transaction->type == 'ach') {
            $account = $this->Accounts->getAch($transaction->account_id);
        }

        // Fetch the gateway used for the authorization
        $gateway = $this->GatewayManager->getInstalledMerchant(
            $this->company_id,
            $transaction->currency,
            $transaction->gateway_id
        );

        if ($account) {
            $contact_id = $account->contact_id;

            // Check if this was a stored account
            if ($account->gateway_id) {
                $stored = true;
            } // must be processed using stored payment account methods
        }

        // Verify that the gateway exists
        if (!$gateway) {
            $this->Input->setErrors($this->gatewayExistsError());
            return;
        }

        $gateway_obj = $this->initGateway($gateway, $transaction->currency);

        // Set the ID of the staff user processing the payment (if any, for logging purposes)
        if (isset($options['staff_id'])) {
            $gateway_obj->setStaffId($options['staff_id']);
        }

        $apply_amounts = $this->invoiceArrayToAmounts($options['invoices']);
        unset($options['invoices']);

        $vars = [
            'client_id' => $client_id,
            'type' => $transaction->type,
            'amount' => $amount,
            'currency' => $transaction->currency,
            'apply_amounts' => $apply_amounts
        ];

        $this->Input->setRules($this->processPaymentRules($gateway_obj, 'cc', $stored));

        // Ensure error checking passes
        if ($this->Input->validates($vars)) {
            switch ($transaction->type) {
                case 'cc':
                    if ($stored) {
                        $response = $gateway_obj->captureStoredCc(
                            $account->client_reference_id,
                            $account->reference_id,
                            $transaction->reference_id,
                            $transaction->transaction_id,
                            $amount
                        );
                    } else {
                        $response = $gateway_obj->captureCc(
                            $transaction->reference_id,
                            $transaction->transaction_id,
                            $amount
                        );
                    }
                    break;
            }

            // Set any errors with the gateway
            if (($errors = $gateway_obj->errors())) {
                $this->Input->setErrors($errors);

                if (!$response) {
                    return;
                }
            }

            // Update the transaction
            $trans_data = $response;
            $trans_data['amount'] = $amount;
            $this->Transactions->edit($transaction->id, $trans_data, $options['staff_id']);

            if (($errors = $this->Transactions->errors())) {
                $this->Input->setErrors($errors);
            } else {
                // Now that the payment has been processed and recorded, apply it to the given
                // invoices
                if (!empty($apply_amounts) && $response['status'] == 'approved') {
                    $this->Transactions->apply($transaction->id, ['amounts' => $apply_amounts]);
                }

                // Set processing error(s)
                if ($response['status'] == 'declined' || $response['status'] == 'error') {
                    $this->setProcessingError($response);
                }

                // If set to send emails fetch the client and send the email
                if ($options['email_receipt'] === true || $options['email_receipt'] == 'true') {
                    $client = $this->Clients->get($client_id);
                    // Default contact to this client (just in case no contact specified)
                    $contact = $client;
                    $email = null;
                    if ($contact_id) {
                        $contact = $this->Contacts->get($contact_id);
                        $email = $contact->email;
                    } else {
                        $email = $client->email;
                    }

                    $tags = [
                        'contact' => $contact,
                        'amount' => $this->CurrencyFormat->format($amount, $transaction->currency),
                        'transaction_id' => $transaction->id,
                        'response' => $response,
                        'last_four' => (isset($account->last4) ? $account->last4 : null),
                        'company' => $this->Companies->get($this->company_id)
                    ];

                    // Set credit card specific tags
                    if ($transaction->type == 'cc' && isset($account->type)) {
                        $card_types = $this->Accounts->getCcTypes();
                        $tags['card_type'] = isset($card_types[$account->type])
                            ? $card_types[$account->type]
                            : null;
                    }

                    $this->Emails->send(
                        'payment_' . $transaction->type . '_' . $response['status'],
                        $this->company_id,
                        $client->settings['language'],
                        $email,
                        $tags,
                        null,
                        null,
                        null,
                        ['to_client_id' => $client->id, 'from_staff_id' => $options['staff_id']]
                    );
                }

                return $this->Transactions->get($transaction->id);
            }
        }
    }

    /**
     * Refunds a payment for the given client and transaction in the supplied amount.
     * This method submits payment to the gateway and updates the transaction
     *
     * @param int $client_id The ID of the client to refund the payment for
     * @param int $transaction_id The ID of the transactiosn to refund
     * @param float $amount The amount to refund, defaults to the original transaction amount if not given
     * @param array $options An array of options including (optional):
     *
     *  - staff_id The ID of the staff member that processed this payment
     *  - notes Any additional notes to include with this transaction
     * @return mixed An object representing the transaction if attempted, void otherwise.
     *  Check GatewayPayments::errors(), as some transactions may be attempted and yet still produce errors
     */
    public function refundPayment($client_id, $transaction_id, $amount = null, array $options = null)
    {
        $default_options = [
            'invoices' => null,
            'staff_id' => null,
            'email_receipt' => true
        ];
        $options = array_merge($default_options, (array)$options);

        $contact_id = null; // Hold the ID of the contact to email if given $account_id
        $stored = false; // Flag whether or not this payment is to be processed using the stored payment account methods

        // Set account info and gateway being used
        $gateway = false;

        // Fetch the transaction to be captured
        $transaction = $this->Transactions->get($transaction_id);

        // Verify transaction exists and belongs to this client
        if (!$transaction || $transaction->client_id != $client_id) {
            $this->Input->setErrors($this->transactionExistsError());
            return;
        }

        // If no amount given, refund the full amount of the previous authorization
        if ($amount == null) {
            $amount = $transaction->amount;
        }

        $amount = $this->CurrencyFormat->cast($amount, $transaction->currency);

        // Fetch the account if one was used for this transaction
        $account = null;
        if ($transaction->type == 'cc') {
            $account = $this->Accounts->getCc($transaction->account_id);
        } elseif ($transaction->type == 'ach') {
            $account = $this->Accounts->getAch($transaction->account_id);
        }

        // Fetch the gateway used for the authorization
        if ($transaction->gateway_type == 'nonmerchant') {
            $gateway = $this->GatewayManager->getInstalledNonmerchant(
                $this->company_id,
                null,
                $transaction->gateway_id
            );
        } else {
            $gateway = $this->GatewayManager->getInstalledMerchant(
                $this->company_id,
                $transaction->currency,
                $transaction->gateway_id
            );
        }

        if ($account) {
            $contact_id = $account->contact_id;

            // Check if this was a stored account
            if ($account->gateway_id) {
                $stored = true;
            } // must be processed using stored payment account methods
        }

        // Verify that the gateway exists
        if (!$gateway) {
            $this->Input->setErrors($this->gatewayExistsError());
            return;
        }

        $gateway_obj = $this->initGateway($gateway, $transaction->currency);

        // Set the ID of the staff user processing the payment (if any, for logging purposes)
        if (isset($options['staff_id'])) {
            $gateway_obj->setStaffId($options['staff_id']);
        }

        $vars = [
            'client_id' => $client_id,
            'type' => $transaction->type,
            'amount' => $amount,
            'currency' => $transaction->currency
        ];

        if ($transaction->gateway_type == 'nonmerchant') {
            $this->Input->setRules($this->processPaymentRules($gateway_obj, 'other', false));
        } else {
            $this->Input->setRules($this->processPaymentRules($gateway_obj, 'ach|cc', $stored));
        }

        // Ensure error checking passes
        if ($this->Input->validates($vars)) {
            switch ($transaction->type) {
                case 'cc':
                    if ($stored) {
                        $response = $gateway_obj->refundStoredCc(
                            $account->client_reference_id,
                            $account->reference_id,
                            $transaction->reference_id,
                            $transaction->transaction_id,
                            $amount
                        );
                    } else {
                        $response = $gateway_obj->refundCc(
                            $transaction->reference_id,
                            $transaction->transaction_id,
                            $amount
                        );
                    }
                    break;
                case 'ach':
                    if ($stored) {
                        $response = $gateway_obj->refundStoredAch(
                            $account->client_reference_id,
                            $account->reference_id,
                            $transaction->reference_id,
                            $transaction->transaction_id,
                            $amount
                        );
                    } else {
                        $response = $gateway_obj->refundAch(
                            $transaction->reference_id,
                            $transaction->transaction_id,
                            $amount
                        );
                    }
                    break;
                default:
                    // Handle non-merchant gateway refund
                    if ($transaction->gateway_type == 'nonmerchant') {
                        $response = $gateway_obj->refund(
                            $transaction->reference_id,
                            $transaction->transaction_id,
                            $amount,
                            (isset($options['notes']) ? $options['notes'] : null)
                        );
                    }
                    break;
            }

            // Set any errors with the gateway
            if (($errors = $gateway_obj->errors())) {
                $this->Input->setErrors($errors);

                if (!$response) {
                    return;
                }
            }

            // Ensure status is "refunded"
            if ($response['status'] != 'refunded') {
                $this->Input->setErrors($this->unexpectedStatusError());
                return;
            }

            // Update the transaction
            $trans_data = $response;
            $trans_data['amount'] = $amount;
            $this->Transactions->edit($transaction->id, $trans_data, $options['staff_id']);

            if (($errors = $this->Transactions->errors())) {
                $this->Input->setErrors($errors);
            } else {
                return $this->Transactions->get($transaction->id);
            }
        }
    }

    /**
     * Voids a payment for the given client and transaction.
     * This method submits payment to the gateway and updates the transaction
     *
     * @param int $client_id The ID of the client to refund the payment for
     * @param int $transaction_id The ID of the transactiosn to refund
     * @param array $options An array of options including (optional):
     *
     *  - staff_id The ID of the staff member that processed this payment
     * @return mixed An object representing the transaction if attempted, void otherwise.
     *  Check GatewayPayments::errors(), as some transactions may be attempted and yet still produce errors
     */
    public function voidPayment($client_id, $transaction_id, array $options = null)
    {
        $default_options = [
            'invoices' => null,
            'staff_id' => null,
            'email_receipt' => true
        ];
        $options = array_merge($default_options, (array)$options);

        $contact_id = null; // Hold the ID of the contact to email if given $account_id
        $stored = false; // Flag whether or not this payment is to be processed using the stored payment account methods

        // Set account info and gateway being used
        $gateway = false;

        // Fetch the transaction to be captured
        $transaction = $this->Transactions->get($transaction_id);

        // Verify transaction exists and belongs to this client
        if (!$transaction || $transaction->client_id != $client_id) {
            $this->Input->setErrors($this->transactionExistsError());
            return;
        }

        $amount = $this->CurrencyFormat->cast($transaction->amount, $transaction->currency);

        // Fetch the account if one was used for this transaction
        $account = null;
        if ($transaction->type == 'cc') {
            $account = $this->Accounts->getCc($transaction->account_id);
        } elseif ($transaction->type == 'ach') {
            $account = $this->Accounts->getAch($transaction->account_id);
        }

        // Fetch the gateway used for the authorization
        if ($transaction->gateway_type == 'nonmerchant') {
            $gateway = $this->GatewayManager->getInstalledNonmerchant(
                $this->company_id,
                null,
                $transaction->gateway_id
            );
        } else {
            $gateway = $this->GatewayManager->getInstalledMerchant(
                $this->company_id,
                $transaction->currency,
                $transaction->gateway_id
            );
        }

        if ($account) {
            $contact_id = $account->contact_id;

            // Check if this was a stored account
            if ($account->gateway_id) {
                $stored = true;
            } // must be processed using stored payment account methods
        }

        // Verify that the gateway exists
        if (!$gateway) {
            $this->Input->setErrors($this->gatewayExistsError());
            return;
        }

        $gateway_obj = $this->initGateway($gateway, $transaction->currency);

        // Set the ID of the staff user processing the payment (if any, for logging purposes)
        if (isset($options['staff_id'])) {
            $gateway_obj->setStaffId($options['staff_id']);
        }

        $vars = [
            'client_id' => $client_id,
            'type' => $transaction->type,
            'amount' => $amount,
            'currency' => $transaction->currency
        ];

        if ($transaction->gateway_type == 'nonmerchant') {
            $this->Input->setRules($this->processPaymentRules($gateway_obj, 'other', false));
        } else {
            $this->Input->setRules($this->processPaymentRules($gateway_obj, 'ach|cc', $stored));
        }

        // Ensure error checking passes
        if ($this->Input->validates($vars)) {
            switch ($transaction->type) {
                case 'cc':
                    if ($stored) {
                        $response = $gateway_obj->voidStoredCc(
                            $account->client_reference_id,
                            $account->reference_id,
                            $transaction->reference_id,
                            $transaction->transaction_id
                        );
                    } else {
                        $response = $gateway_obj->voidCc($transaction->reference_id, $transaction->transaction_id);
                    }
                    break;
                case 'ach':
                    if ($stored) {
                        $response = $gateway_obj->voidStoredAch(
                            $account->client_reference_id,
                            $account->reference_id,
                            $transaction->reference_id,
                            $transaction->transaction_id
                        );
                    } else {
                        $response = $gateway_obj->voidAch($transaction->reference_id, $transaction->transaction_id);
                    }
                    break;
                default:
                    // Handle non-merchant gateway void
                    if ($transaction->gateway_type == 'nonmerchant') {
                        $response = $gateway_obj->void($transaction->reference_id, $transaction->transaction_id);
                    }
                    break;
            }

            // Set any errors with the gateway
            if (($errors = $gateway_obj->errors())) {
                $this->Input->setErrors($errors);

                if (!$response) {
                    return;
                }
            }


            // Ensure status is "void"
            if ($response['status'] != 'void') {
                $this->Input->setErrors($this->unexpectedStatusError());
                return;
            }

            // Update the transaction
            $trans_data = $response;
            $trans_data['amount'] = $amount;
            $this->Transactions->edit($transaction->id, $trans_data, $options['staff_id']);

            if (($errors = $this->Transactions->errors())) {
                $this->Input->setErrors($errors);
            } else {
                return $this->Transactions->get($transaction->id);
            }
        }
    }

    /**
     * Communicates with the remote gateway to store the account.
     * SHOULD NOT BE USED TO CREATE PAYMENT ACCOUNTS IN THE SYSTEM. See Accounts::addAch() or
     * Accounts::addCc() instead.
     *
     * @param string $type The type of account ("cc" or "ach")
     * @param array $vars An array of ACH or CC account info including:
     *
     *  - contact_id The contact ID tied to this account
     *  - first_name The first name on the account
     *  - last_name The last name on the account
     *  - address1 The address line 1 on the account (optional)
     *  - address2 The address line 2 on the account (optional)
     *  - city The city on the account (optional)
     *  - state The ISO 3166-2 subdivision code on the account (optional)
     *  - zip The zip code on the account (optional)
     *  - country The ISO 3166-1 2-character country code (optional, defaults to 'US')
     *  - account The account number (if ach)
     *  - routing The routing number (if ach)
     *  - type The type of account, 'checking' or 'savings', (if ach, defaults to 'checking')
     *  - card_number The card number (if cc)
     *  - expiration The card expiration date in yyyymm format (if cc)
     *  - security_code The card security code (if cc)
     *  - reference_id The reference ID attached to this account given by the payment processor (optional)
     *  - client_reference_id The reference ID for the client this payment account belongs to (optional)
     *  - currency The currency in which is denominated the account (optional)
     * @return mixed False if the gateway does not support off-site payment accounts or is not enabled for off-site
     *  payment accounts, void for all other errors (errors set, use GatewayPayments::errors() to fetch errors),
     *  else sets an array containing:
     *
     *  - gateway_id The ID of the gateway used to process the request
     *  - client_reference_id The reference ID for this client
     *  - reference_id The reference ID for this payment account
     *  - expiration The expiration date of the stored card (if cc) (optional)
     *  - last4 The last four digits of the stored card (if cc) (optional)
     *  - type The type of the stored card (e.g amex) (if cc) (optional)
     * @see Accounts::addAch()
     * @see Accounts::addCc()
     */
    public function storeAccount($type, array $vars)
    {
        // Verify the contact exists
        $contact = $this->Contacts->get(isset($vars['contact_id']) ? $vars['contact_id'] : null);
        if (!$contact) {
            $this->Input->setErrors($this->contactExistsError());
            return;
        }

        // Verify the client exists
        $client = $this->Clients->get($contact->client_id);
        if (!$client) {
            // If client doesn't exist, contact theoretically doesn't exist either
            $this->Input->setErrors($this->contactExistsError());
            return;
        }

        // Set the currency to be used for this client so we can fetch the appropriate gateway
        $currency = $vars['currency'] ?? $client->settings['default_currency'];

        // Fetch the gateway to be used with this company and currency
        $gateway = $this->GatewayManager->getInstalledMerchant($this->company_id, $currency);

        // Verify that the gateway exists
        if (!$gateway) {
            //$this->Input->setErrors($this->gatewayExistsError());
            return false;
        }

        // Run through accountToAccountInfo() to format account info correctly
        $account_info = $this->accountToAccountInfo((object)$vars, $type);

        if ($type == 'cc') {
            $account_info['type'] = $this->Accounts->creditCardType($account_info['card_number']);
        }

        // Convert 2 or 3-char country to country array
        $account_info['country'] = (array)$this->Countries->get($account_info['country']);
        // Convert 2 or 3-char state to state array
        $account_info['state'] = (array)$this->States->get(
            isset($account_info['country']['alpha2']) ? $account_info['country']['alpha2'] : null,
            $account_info['state']
        );

        // Get existing client reference id (if any)
        $client_reference_id = $this->Accounts->getClientReferenceId($client->id, $gateway->id, 'active', $type);

        $gateway_obj = $this->initGateway($gateway, $currency);

        // Verify gateway works off-site and is enabled to store account off-site
        if (!$this->implementsGateway($type, $gateway_obj, true)
            || (($type == 'ach' && !$gateway_obj->requiresAchStorage())
                || ($type == 'cc' && !$gateway_obj->requiresCcStorage()))
        ) {
            return false;
        }

        switch ($type) {
            case 'cc':
                $response = $gateway_obj->storeCc($account_info, (array)$contact, $client_reference_id);
                break;
            case 'ach':
                $response = $gateway_obj->storeAch($account_info, (array)$contact, $client_reference_id);
                break;
        }

        if (!($errors = $gateway_obj->errors())) {
            if (!$response) {
                $this->Input->setErrors($this->accountStoreError());
            } else {
                return array_merge(['gateway_id' => $gateway->id], $response);
            }
        } else {
            $this->Input->setErrors($errors);
        }
    }

    /**
     * Communicates with the remote gateway to update the account.
     * SHOULD NOT BE USED TO UPDATE PAYMENT ACCOUNTS IN THE SYSTEM. See Accounts::editAch() or
     * Accounts::editCc() instead.
     *
     * @param string $type The type of account ("cc" or "ach")
     * @param object $account The existing account record that is to be updated
     * @param array $vars An array of ACH or CC account info including:
     *
     *  - contact_id The contact ID tied to this account
     *  - first_name The first name on the account
     *  - last_name The last name on the account
     *  - address1 The address line 1 on the account (optional)
     *  - address2 The address line 2 on the account (optional)
     *  - city The city on the account (optional)
     *  - state The ISO 3166-2 subdivision code on the account (optional)
     *  - zip The zip code on the account (optional)
     *  - country The ISO 3166-1 2-character country code (optional, defaults to 'US')
     *  - account The account number (if ach)
     *  - routing The routing number (if ach)
     *  - type The type of account, 'checking' or 'savings', (if ach, defaults to 'checking')
     *  - card_number The card number (if cc)
     *  - expiration The card expiration date in yyyymm format (if cc)
     *  - security_code The card security code (if cc)
     *  - reference_id The reference ID attached to this account given by the payment processor (optional)
     *  - client_reference_id The reference ID for the client this payment account belongs to (optional)
     *  - account_changed True if the account details (bank account or card number, etc.) have been updated, false
     *    otherwise
     *  - currency The currency in which is denominated the account (optional)
     * @return mixed False if the gateway does not support off-site payment accounts or is not enabled for off-site
     *  payment accounts, void for all other errors (errors set, use GatewayPayments::errors() to fetch errors), else
     *  sets an array containing:
     *
     *  - gateway_id The ID of the gateway used to process the request
     *  - client_reference_id The reference ID for this client
     *  - reference_id The reference ID for this payment account
     *  - expiration The expiration date of the stored card (if cc) (optional)
     *  - last4 The last four digits of the stored card (if cc) (optional)
     *  - type The type of the stored card (e.g amex) (if cc) (optional)
     * @see Accounts::editAch()
     * @see Accounts::editCc()
     */
    public function updateAccount($type, $account, array $vars)
    {
        // If the payment account is not set up under a gateway there's no point in attempting to update it because it
        // won't exist
        if (!isset($account->gateway_id) || !$account->gateway_id) {
            return false;
        }

        // Verify the contact exists
        $contact = $this->Contacts->get(isset($account->contact_id) ? $account->contact_id : null);
        if (!$contact) {
            $this->Input->setErrors($this->contactExistsError());
            return;
        }

        // Verify the client exists
        $client = $this->Clients->get($contact->client_id);
        if (!$client) {
            // If client doesn't exist, contact theoretically doesn't exist either
            $this->Input->setErrors($this->contactExistsError());
            return;
        }

        // Set the currency to be used for this client so we can fetch the appropriate gateway
        $currency = $vars['currency'] ?? $client->settings['default_currency'];

        // Fetch the gateway to be used with this company and currency
        $gateway = $this->GatewayManager->getInstalledMerchant($this->company_id, $currency, $account->gateway_id);

        // Verify that the gateway exists
        if (!$gateway) {
            $this->Input->setErrors($this->gatewayExistsError());
            return;
        }

        // Run through accountToAccountInfo() to format account info correctly
        $account_info = $this->accountToAccountInfo((object)$vars, $type);

        if ($type == 'cc') {
            $account_info['type'] = $this->Accounts->creditCardType($account_info['card_number']);
        }

        // Convert 2 or 3-char country to country array
        $account_info['country'] = (array)$this->Countries->get($account_info['country']);
        // Convert 2 or 3-char state to state array
        $account_info['state'] = (array)$this->States->get(
            isset($account_info['country']['alpha2']) ? $account_info['country']['alpha2'] : null,
            $account_info['state']
        );

        $gateway_obj = $this->initGateway($gateway, $currency);

        // Verify gateway works off-site and is enabled to store account off-site
        if (!$this->implementsGateway($type, $gateway_obj, true)
            || (($type == 'ach' && !$gateway_obj->requiresAchStorage())
                || ($type == 'cc' && !$gateway_obj->requiresCcStorage()))
        ) {
            return false;
        }

        // Process the request
        switch ($type) {
            case 'cc':
                $response = $gateway_obj->updateCc(
                    $account_info,
                    (array)$contact,
                    $account->client_reference_id,
                    $account->reference_id
                );
                break;
            case 'ach':
                $response = $gateway_obj->updateAch(
                    $account_info,
                    (array)$contact,
                    $account->client_reference_id,
                    $account->reference_id
                );
                break;
        }

        if (!($errors = $gateway_obj->errors())) {
            if (!$response) {
                $this->Input->setErrors($this->accountUpdateError());
            } else {
                return array_merge(['gateway_id' => $gateway->id], $response);
            }
        } else {
            $this->Input->setErrors($errors);
        }
    }

    /**
     * Communicates with the remote gateway to verify the account.
     * SHOULD NOT BE USED TO UPDATE PAYMENT ACCOUNTS IN THE SYSTEM. See Accounts::editAch() or
     * Accounts::verifyAchDeposits() instead.
     *
     * @param string $type The type of account ("cc" or "ach") (only "ach" is supported at the moment)
     * @param object $account The existing account record that is to be verified
     * @param array $vars An array including:
     *
     *  - first_deposit The first deposit amount
     *  - second_deposit The second deposit amount
     * @see Accounts::verifyAchDeposits()
     */
    public function verifyAccount($type, $account, array $vars)
    {
        // If the payment account is not set up under a gateway there's no point in attempting to validate it because
        // it won't exist
        if (!isset($account->gateway_id) || !$account->gateway_id) {
            return false;
        }

        // Verify the contact exists
        $contact = $this->Contacts->get($account->contact_id ?? null);
        if (!$contact) {
            $this->Input->setErrors($this->contactExistsError());
            return;
        }

        // Verify the client exists
        $client = $this->Clients->get($contact->client_id);
        if (!$client) {
            // If client doesn't exist, contact theoretically doesn't exist either
            $this->Input->setErrors($this->contactExistsError());
            return;
        }

        // Set the currency to be used for this client so we can fetch the appropriate gateway
        $currency = $client->settings['default_currency'];

        // Fetch the gateway to be used with this company and currency
        $gateway = $this->GatewayManager->getInstalledMerchant($this->company_id, $currency, $account->gateway_id);

        // Verify that the gateway exists
        if (!$gateway) {
            $this->Input->setErrors($this->gatewayExistsError());
            return;
        }

        $gateway_obj = $this->initGateway($gateway, $currency);

        // Verify account
        if ($type == 'ach') {
            $response = $gateway_obj->verifyAch($vars, $account->client_reference_id, $account->reference_id);
        } else {
            $this->Input->setErrors([
                'type' => [
                    'invalid' => Language::_('GatewayPayments.!error.type.invalid', true)
                ]
            ]);

            return;
        }

        if (!($errors = $gateway_obj->errors())) {
            if (!$response) {
                $this->Input->setErrors($this->accountVerifyError());
            } else {
                return array_merge(['gateway_id' => $gateway->id], $response);
            }
        } else {
            $this->Input->setErrors($errors);
        }
    }

    /**
     * Communicates with the remote gateway to update the account.
     * SHOULD NOT BE USED TO UPDATE PAYMENT ACCOUNTS IN THE SYSTEM. See Accounts::deleteAch() or
     * Accounts::deleteCc() instead.
     *
     * @param string $type The type of account ("cc" or "ach")
     * @param object $account The existing account record that is to be updated
     * @return mixed False if the gateway does not support off-site payment accounts or is not enabled for off-site
     *  payment accounts, void for all other errors (errors set, use GatewayPayments::errors() to fetch errors),
     *  else sets an array containing:
     *
     *  - gateway_id The ID of the gateway used to process the request
     *  - client_reference_id The reference ID for this client
     *  - reference_id The reference ID for this payment account
     * @see Accounts::deleteAch()
     * @see Accounts::deleteCc()
     */
    public function removeAccount($type, $account)
    {
        // If the payment account is not set up under a gateway there's no point in attempting to remove it because
        // it won't exist
        if (!isset($account->gateway_id) || !$account->gateway_id) {
            return false;
        }

        // Verify the contact exists
        $contact = $this->Contacts->get(isset($account->contact_id) ? $account->contact_id : null);
        if (!$contact) {
            $this->Input->setErrors($this->contactExistsError());
            return;
        }

        // Verify the client exists
        $client = $this->Clients->get($contact->client_id);
        if (!$client) {
            // If client doesn't exist, contact theoretically doesn't exist either
            $this->Input->setErrors($this->contactExistsError());
            return;
        }

        // Set the currency to be used for this client so we can fetch the appropriate gateway
        $currency = $client->settings['default_currency'];

        // Fetch the gateway to be used with this company and currency
        $gateway = $this->GatewayManager->getInstalledMerchant($this->company_id, $currency, $account->gateway_id);

        // Verify that the gateway exists
        if (!$gateway) {
            $this->Input->setErrors($this->gatewayExistsError());
            return;
        }

        $gateway_obj = $this->initGateway($gateway, $currency);

        // Verify gateway works off-site
        if (!$this->implementsGateway($type, $gateway_obj, true)) {
            return false;
        }

        // Process the request
        switch ($type) {
            case 'cc':
                $response = $gateway_obj->removeCc($account->client_reference_id, $account->reference_id);
                break;
            case 'ach':
                $response = $gateway_obj->removeAch($account->client_reference_id, $account->reference_id);
                break;
        }

        if (!($errors = $gateway_obj->errors())) {
            if (!$response) {
                $this->Input->setErrors($this->accountRemoveError());
            } else {
                return array_merge(['gateway_id' => $gateway->id], $response);
            }
        } else {
            $this->Input->setErrors($errors);
        }
    }

    /**
     * Returns errors set in this object's Input object
     */
    public function errors()
    {
        return $this->Input->errors();
    }

    /**
     * Returns whether or not the supplied gateway object implements the correct gateway interface
     * based on its type and off site capabilities
     *
     * @param string $type The type of merchant gateway (cc or ach)
     * @param object The gateway object
     * @param bool $off_site True if attempting to verify support for off site payments, false otherwise
     * @return bool True if the gateway object supports the appropriate interface, false otherwise
     */
    public function implementsGateway($type, $gateway_obj, $off_site)
    {
        $interface = '';
        if ($off_site) {
            if ($type == 'cc') {
                $interface = 'MerchantCcOffsite';
            } else {
                $interface = 'MerchantAchOffsite';
            }
        } else {
            if ($type == 'cc') {
                $interface = 'MerchantCc';
            } else {
                $interface = 'MerchantAch';
            }
        }
        return $gateway_obj instanceof $interface;
    }

    /**
     * Converts a key/value array of invoices and their apply amounts to a numerically
     * indexed array containing keys for 'invoice_id' and 'amount'
     *
     * @param array $invoices A key/value array where each key represents an invoice ID and the value represents the
     *  apply amount
     * @return array A numerically indexed array containing the 'invoice_id' and 'amount' keys
     */
    private function invoiceArrayToAmounts(array $invoices = null)
    {
        $amounts = [];
        if (!empty($invoices)) {
            foreach ($invoices as $id => $amount) {
                $amounts[] = ['invoice_id' => $id, 'amount' => $amount];
            }
        }
        return $amounts;
    }

    /**
     * Converts a CC or ACH account record object to an array
     * @param object $account The object to convert
     * @param string $type The type of account ("cc" or "ach")
     * @return array An array containing:
     *
     *  - first_name The first name on the account
     *  - last_name The last name on the account
     *  - account_number The bank account number (if ach)
     *  - routing_number The bank account routing number (if ach)
     *  - type If ACH the bank account type (checking, savings, business_checking),
     *      if Credit Card the card type (amex, visa, etc.)
     *  - card_number The credit card number (if cc)
     *  - card_exp The card expiration date in yyyymm format (if cc)
     *  - card_security_code The card security code (if cc)
     *  - address1 The address 1 line of the account holder
     *  - address2 The address 2 line of the account holder
     *  - city The city of the account holder
     *  - state The 2 or 3 character state code
     *  - country The 2 or 3 character country code
     *  - zip The zip/postal code of the account holder
     *  - account_changed (optional) True if the account details have changed, false otherwise
     *  - last4 (optional) The last 4 digits of account_number or card_number if the card number or account number
     *    is not known (due to being stored off site)
     */
    private function accountToAccountInfo($account, $type)
    {
        $account_info = [];

        switch ($type) {
            case 'ach':
                $account_info = [
                    'first_name' => $account->first_name,
                    'last_name' => $account->last_name,
                    'account_number' => isset($account->account) ? $account->account : null,
                    'routing_number' => isset($account->routing) ? $account->routing : null,
                    'type' => isset($account->type) ? $account->type : null,
                    'address1' => isset($account->address1) ? $account->address1 : null,
                    'address2' => isset($account->address2) ? $account->address2 : null,
                    'city' => isset($account->city) ? $account->city : null,
                    'state' => isset($account->state) ? $account->state : null,
                    'country' => isset($account->country) ? $account->country : null,
                    'zip' => isset($account->zip) ? $account->zip : null,
                    'reference_id' => isset($account->reference_id) ? $account->reference_id : null,
                    'client_reference_id' => isset($account->client_reference_id) ? $account->client_reference_id : null
                ];
                break;
            case 'cc':
                $account_info = [
                    'first_name' => $account->first_name,
                    'last_name' => $account->last_name,
                    'card_number' => isset($account->number) ? $account->number : null,
                    'card_exp' => isset($account->expiration) ? $account->expiration : null,
                    'card_security_code' => isset($account->security_code) ? $account->security_code : null,
                    'type' => isset($account->type) ? $account->type : null,
                    'address1' => isset($account->address1) ? $account->address1 : null,
                    'address2' => isset($account->address2) ? $account->address2 : null,
                    'city' => isset($account->city) ? $account->city : null,
                    'state' => isset($account->state) ? $account->state : null,
                    'country' => isset($account->country) ? $account->country : null,
                    'zip' => isset($account->zip) ? $account->zip : null,
                    'reference_id' => isset($account->reference_id) ? $account->reference_id : null,
                    'client_reference_id' => isset($account->client_reference_id) ? $account->client_reference_id : null
                ];
                break;
        }

        if (!empty($account_info) && isset($account->last4)) {
            $account_info['last4'] = $account->last4;
        }

        if (!empty($account_info) && isset($account->account_changed)) {
            $account_info['account_changed'] = $account->account_changed;
        }

        return $account_info;
    }

    /**
     * Returns the last 4 digits of either the credit card or bank account number
     *
     * @param array $account_info An array of account info (see Accounts::getCc(), Accounts::getAch())
     * @param string $type Either "cc" (Credit Card) or "ach" (ACH bank transfer)
     * @return string The last 4 digits of the credit card or bank account number
     */
    private function getLast4($account_info, $type)
    {
        if (isset($account_info['last4'])) {
            return $account_info['last4'];
        } else {
            switch ($type) {
                case 'ach':
                    if (isset($account_info['account_number'])) {
                        return substr($account_info['account_number'], -4);
                    }
                    break;
                case 'cc':
                    if (isset($account_info['card_number'])) {
                        return substr($account_info['card_number'], -4);
                    }
                    break;
            }
        }
        return null;
    }

    /**
     * Returns an error to be use when the required gateway does not exist or is not enabled
     *
     * @return array An array containing error data suitable for Input::setErrors()
     * @see Input::setErrors()
     */
    private function gatewayExistsError()
    {
        return [
            'gateway' => [
                'exists' => Language::_('GatewayPayments.!error.gateway.exists', true)
            ]
        ];
    }

    /**
     * Returns an error to be use when the required transaction does not exist for the client
     *
     * @return array An array containing error data suitable for Input::setErrors()
     * @see Input::setErrors()
     */
    private function transactionExistsError()
    {
        return [
            'transaction_id' => [
                'exists' => Language::_('GatewayPayments.!error.transaction_id.exists', true)
            ]
        ];
    }

    /**
     * Returns an error to be use when the required account does not exist for the client
     *
     * @return array An array containing error data suitable for Input::setErrors()
     * @see Input::setErrors()
     */
    private function accountExistsError()
    {
        return [
            'account_id' => [
                'exists' => Language::_('GatewayPayments.!error.account_id.exists', true)
            ]
        ];
    }

    /**
     * Returns an error to be use when the required contact does not exist for the client
     *
     * @return array An array containing error data suitable for Input::setErrors()
     * @see Input::setErrors()
     */
    private function contactExistsError()
    {
        return [
            'contact_id' => [
                'exists' => Language::_('GatewayPayments.!error.contact_id.exists', true)
            ]
        ];
    }


    /**
     * Returns an array of rules that verify that the given $gateway_obj implements
     * the correct interface for the payment type requested.
     *
     * @param object $gateway_obj The gateway object to verify implements the correct interface
     * @param string $types A string containing the types of payments allowed
     *  (pipe delimited, e.g. "ach|cc" or just "cc")
     * @param bool $off_site True if attempting to verify support for off site payments, false otherwise
     */
    private function processPaymentRules($gateway_obj, $types = 'ach|cc', $off_site = false)
    {
        $rules = [
            'type' => [
                'valid' => [
                    'rule' => ['matches', '/^(' . $types . ')$/i'],
                    'message' => Language::_('GatewayPayments.!error.type.valid', true)
                ]
            ],
            'amount' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => function ($amount) {
                        return $amount > 0;
                    },
                    'message' => Language::_('GatewayPayments.!error.amount.valid', true)
                ]
            ]
        ];

        // Verify the gateway implements the correct interface if this is a merchant gateway
        if ($types != 'other') {
            $rules['type']['instance'] = [
                'rule' => [[$this, 'implementsGateway'], $gateway_obj, $off_site],
                'message' => Language::_('GatewayPayments.!error.type.instance', true)
            ];
        }

        return $rules;
    }

    /**
     * Returns an error to be use when the account could not be stored with the gateway
     *
     * @return array An array containing error data suitable for Input::setErrors()
     * @see Input::setErrors()
     */
    private function accountStoreError()
    {
        return [
            'reference_id' => [
                'store' => Language::_('GatewayPayments.!error.reference_id.store', true)
            ]
        ];
    }

    /**
     * Returns an error to be use when the account could not be updated with the gateway
     *
     * @return array An array containing error data suitable for Input::setErrors()
     * @see Input::setErrors()
     */
    private function accountUpdateError()
    {
        return [
            'reference_id' => [
                'update' => Language::_('GatewayPayments.!error.reference_id.update', true)
            ]
        ];
    }

    /**
     * Returns an error to be used when the account could not be verified from the gateway
     *
     * @return array An array containing error data suitable for Input::setErrors()
     * @see Input::setErrors()
     */
    private function accountVerifyError()
    {
        return [
            'reference_id' => [
                'verify' => Language::_('GatewayPayments.!error.reference_id.verify', true)
            ]
        ];
    }

    /**
     * Returns an error to be used when the account could not be removed from the gateway
     *
     * @return array An array containing error data suitable for Input::setErrors()
     * @see Input::setErrors()
     */
    private function accountRemoveError()
    {
        return [
            'reference_id' => [
                'remove' => Language::_('GatewayPayments.!error.reference_id.remove', true)
            ]
        ];
    }

    /**
     * Returns an error to be used when the gateway returned an unexpected response status (i.e. expected "returned"
     *  but received "error" or something similar)
     *
     * @return array An array containing error data suitable for Input::setErrors()
     * @see Input::setErrors()
     */
    private function unexpectedStatusError()
    {
        return [
            'response' => [
                'status' => Language::_('GatewayPayments.!error.response_status', true)
            ]
        ];
    }

    /**
     * Sets input errors based on the response status
     *
     * @param array $response A key/value array of response parameters including:
     *
     *  - status The response status
     */
    private function setProcessingError(array $response)
    {
        $errors = $this->Input->errors();

        switch ($response['status']) {
            case 'declined':
                $errors['gateway']['declined'] = Language::_('GatewayPayments.!error.gateway.declined', true);
                break;
            case 'error':
                $errors['gateway']['error'] = Language::_('GatewayPayments.!error.gateway.error', true);
                break;
        }

        $this->Input->setErrors($errors);
    }
}
