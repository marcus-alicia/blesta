<?php

/**
 * Processes payments through remote gateways and records the transactions.
 * Supports non-merchant gateways by returning markup used to render those payment buttons.
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Payments extends AppModel
{
    /**
     * Initialize Payments
     */
    public function __construct()
    {
        parent::__construct();
        Loader::loadComponents($this, ['GatewayPayments']);
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
     * @param array $invoice_amounts An array of key/value pairs where each
     *  key represents an invoice ID and each value is the amount to apply to that invoice
     * @param array $options An array of options including:
     *
     *  - description The Description of the charge
     *  - return_url The URL to redirect users to after a successful payment
     *  - recur An array of recurring info including:
     *      - start_date The date/time in UTC that the recurring payment begins
     *      - amount The amount to recur
     *      - term The term to recur
     *      - period The recurring period (day, week, month, year, onetime)
     *          used in conjunction with term in order to determine the next recurring payment
     * @param int $gateway_id The ID of the nonmerchant gateway to fetch, if
     *  null will return all nonmerchant gateways for the currency
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
        // Update the invoice amounts to arrays of invoice IDs and amounts
        $inv_amounts = [];
        if ($invoice_amounts) {
            foreach ($invoice_amounts as $invoice_id => $amt) {
                $inv_amounts[] = ['id' => $invoice_id, 'amount' => $amt];
            }
        }
        if (isset($options['recur']['amount'])) {
            $options['recur']['amount'] = $this->currencyToDecimal($options['recur']['amount'], $currency);
        }

        $result = $this->GatewayPayments->getBuildProcess(
            $contact_info,
            $amount,
            $currency,
            $inv_amounts,
            $options,
            $gateway_id
        );
        if (($errors = $this->GatewayPayments->errors())) {
            $this->Input->setErrors($errors);
        }
        return $result;
    }

    /**
     * Returns HTML markup used to render a custom credit card form for a merchant gateway
     *
     * @param string $currency The currency the payment is in
     * @return string Custom CC form HTML from the merchant
     */
    public function getBuildCcForm($currency)
    {
        $result = $this->GatewayPayments->getBuildCcForm($currency);

        if (($errors = $this->GatewayPayments->errors())) {
            $this->Input->setErrors($errors);
        }

        return $result;
    }

    /**
     * Returns HTML markup used to render a custom ach form for a merchant gateway
     *
     * @param string $currency The currency the payment is in
     * @param array $account_info An array of bank account info
     * @return string Custom ACH form HTML from the merchant
     */
    public function getBuildAchForm($currency, $account_info = null)
    {
        $result = $this->GatewayPayments->getBuildAchForm($currency, $account_info);

        if (($errors = $this->GatewayPayments->errors())) {
            $this->Input->setErrors($errors);
        }

        return $result;
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
        $result = $this->GatewayPayments->getBuildAchVerificationForm($currency, $vars);

        if (($errors = $this->GatewayPayments->errors())) {
            $this->Input->setErrors($errors);
        }

        return $result;
    }

    /**
     * Gets an Html form from the merchant gateway to use as an addition to the regular payment confirmation
     * pages in Blesta
     *
     * @param int $client_id The ID of the client that payment confirmation is being viewed for
     * @param int $transaction_id The ID of the transaction being confirmed
     * @return string The Html, if any, provided by the merchant gateway for confirming payments
     */
    public function getBuildPaymentConfirmation($client_id, $transaction_id) {
        $result = $this->GatewayPayments->getBuildPaymentConfirmation($client_id, $transaction_id);

        if (($errors = $this->GatewayPayments->errors())) {
            $this->Input->setErrors($errors);
        }

        return $result;
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
     * @param array $invoice_amounts An array of key/value pairs where each
     *  key represents an invoice ID and each value is the amount to apply to that invoice
     * @param array $options An array of options including:
     *
     *  - description The Description of the charge
     *  - return_url The URL to redirect users to after a successful payment
     *  - recur An array of recurring info including:
     *      - start_date The date/time in UTC that the recurring payment begins
     *      - amount The amount to recur
     *      - term The term to recur
     *      - period The recurring period (day, week, month, year, onetime)
     *          used in conjunction with term in order to determine the next recurring payment
     * @param int $gateway_id The ID of the nonmerchant gateway to fetch, if
     *  null will return all nonmerchant gateways for the currency
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
        // Update the invoice amounts to arrays of invoice IDs and amounts
        $inv_amounts = [];
        if ($invoice_amounts) {
            foreach ($invoice_amounts as $invoice_id => $amt) {
                $inv_amounts[] = ['id' => $invoice_id, 'amount' => $amt];
            }
        }

        $result = $this->GatewayPayments->getBuildAuthorize(
            $contact_info,
            $amount,
            $currency,
            $inv_amounts,
            $options,
            $gateway_id
        );
        if (($errors = $this->GatewayPayments->errors())) {
            $this->Input->setErrors($errors);
        }
        return $result;
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
     * @param int $account_id The account ID to be used to process the payment if no $account_info given
     * @param array $options An array of options including (optional):
     *
     *  - invoices An array of key/value pairs where each key represents an
     *      invoice ID and each value is the amount to apply to that invoice (optional, may not exceed $amount)
     *  - staff_id The ID of the staff member that processed this payment
     *  - email_receipt If true (default true), will send an email receipt
     *      to the client and BCC appropriate staff members
     *  - passphrase The value used to encrypt the private key that is used to decrypt account details
     * @return object An object representing the transaction if attempted,
     *  void otherwise. Check Payments::errors(), as some transactions may be attempted and yet still produce errors
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
        if (isset($options['passphrase'])) {
            $this->GatewayPayments->setPassphrase($options['passphrase']);
        }
        $result = $this->GatewayPayments->processPayment(
            $client_id,
            $type,
            $amount,
            $currency,
            $account_info,
            $account_id,
            $options
        );
        if (($errors = $this->GatewayPayments->errors())) {
            $this->Input->setErrors($errors);
        }
        return $result;
    }

    /**
     * Authorizes a payment for the given client of the given type using the supplied details.
     * This method submits payment to the gateway and records the transaction
     *
     * @param int $client_id The ID of the client to authorize the payment for
     * @param string $type The type of payment to submit "cc" or "ach",
     *  NOTE: Only "cc" is supported, but this varaible remains for consistency (and possible future support)
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
     *  - invoices An array of key/value pairs where each key represents an
     *      invoice ID and each value is the amount to apply to that invoice (optional, may not exceed $amount)
     *  - staff_id The ID of the staff member that processed this payment
     *  - email_receipt If true (default true), will send an email receipt
     *      to the client and BCC appropriate staff members
     *  - passphrase The value used to encrypt the private key that is used to decrypt account details
     * @return object An object representing the transaction if attempted,
     *  void otherwise. Check Payments::errors(), as some transactions may be attempted and yet still produce errors
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
        if (isset($options['passphrase'])) {
            $this->GatewayPayments->setPassphrase($options['passphrase']);
        }

        $result = $this->GatewayPayments->authorizePayment(
            $client_id,
            $type,
            $amount,
            $currency,
            $account_info,
            $account_id,
            $options
        );
        if (($errors = $this->GatewayPayments->errors())) {
            $this->Input->setErrors($errors);
        }
        return $result;
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
     *  - invoices An array of key/value pairs where each key represents an
     *      invoice ID and each value is the amount to apply to that invoice (optional, may not exceed $amount)
     *  - staff_id The ID of the staff member that processed this payment
     *  - email_receipt If true (default true), will send an email receipt
     *      to the client and BCC appropriate staff members
     * @return object An object representing the transaction if attempted,
     *  void otherwise. Check Payments::errors(), as some transactions may be attempted and yet still produce errors
     */
    public function capturePayment($client_id, $transaction_id, $amount = null, array $options = null)
    {
        $result = $this->GatewayPayments->capturePayment($client_id, $transaction_id, $amount, $options);
        if (($errors = $this->GatewayPayments->errors())) {
            $this->Input->setErrors($errors);
        }
        return $result;
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
     * @return mixed An object representing the transaction if attempted,
     *  void otherwise. Check Payments::errors(), as some transactions may be attempted and yet still produce errors
     */
    public function refundPayment($client_id, $transaction_id, $amount = null, array $options = null)
    {
        $result = $this->GatewayPayments->refundPayment($client_id, $transaction_id, $amount, $options);
        if (($errors = $this->GatewayPayments->errors())) {
            $this->Input->setErrors($errors);
        }
        return $result;
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
     * @return mixed An object representing the transaction if attempted,
     *  void otherwise. Check Payments::errors(), as some transactions may be attempted and yet still produce errors
     */
    public function voidPayment($client_id, $transaction_id, array $options = null)
    {
        $result = $this->GatewayPayments->voidPayment($client_id, $transaction_id, $options);
        if (($errors = $this->GatewayPayments->errors())) {
            $this->Input->setErrors($errors);
        }
        return $result;
    }

    /**
     * Returns errors set in this object's Input object
     */
    public function errors()
    {
        return $this->Input->errors();
    }
}
