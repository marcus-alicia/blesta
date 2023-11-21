<?php
/**
 * Merchant ACH offsite processing interface. Defines all methods that an ACH
 * offsite payment gateway must implement. Note: not all methods are required
 * to be supported.
 *
 * All ACH offsite gateways support storing customer information with the
 * gateway processor rather than within the system. This removes the burden of
 * secure storage from the merchant and places it in the hands of the gateway
 * processor.
 *
 * @package blesta
 * @subpackage blesta.components.gateways
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface MerchantAchOffsite
{
    /**
     * Store an ACH account off site
     *
     * @param array $account_info An array of bank account info including:
     *
     *  - first_name The first name on the account
     *  - last_name The last name on the account
     *  - account_number The bank account number
     *  - routing_number The bank account routing number
     *  - type The bank account type (checking, savings, business_checking)
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
     *  - zip The zip/postal code of the account holder
     * @param array $contact An array of contact information for the billing contact this account is to be set
     *  up under including:
     *
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
     *
     *  - client_reference_id The reference ID for this client
     *  - reference_id The reference ID for this payment account
     */
    public function storeAch(array $account_info, array $contact, $client_reference_id = null);
    /**
     * Update an off site ACH account
     *
     * @param array $account_info An array of bank account info including:
     *
     *  - first_name The first name on the account
     *  - last_name The last name on the account
     *  - account_number The bank account number
     *  - routing_number The bank account routing number
     *  - type The bank account type (checking, savings, business_checking)
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
     *  - zip The zip/postal code of the account holder
     *  - account_changed True if the account details (bank account or card number, etc.) have been updated,
     *    false otherwise
     * @param array $contact An array of contact information for the billing contact this account is to be set
     *  up under including:
     *
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
     *
     *  - client_reference_id The reference ID for this client
     *  - reference_id The reference ID for this payment account
     */
    public function updateAch(array $account_info, array $contact, $client_reference_id, $account_reference_id);
    /**
     * Remove an off site ACH account
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to remove
     * @return array An array containing:
     *
     *  - client_reference_id The reference ID for this client
     *  - reference_id The reference ID for this payment account
     */
    public function removeAch($client_reference_id, $account_reference_id);
    /**
     * Process an off site ACH account transaction
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @param float $amount The amount to process
     * @param array $invoice_amounts An array of invoices, each containing:
     *
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @return array An array of transaction data including:
     *
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard message for
     *      this transaction status (optional)
     */
    public function processStoredAch(
        $client_reference_id,
        $account_reference_id,
        $amount,
        array $invoice_amounts = null
    );
    /**
     * Void an off site ACH account transaction
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @param string $transaction_reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The ID of the previously authorized transaction
     * @return array An array of transaction data including:
     *
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard message for
     *      this transaction status (optional)
     */
    public function voidStoredAch(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id
    );
    /**
     * Refund an off site ACH account transaction
     *
     * @param string $client_reference_id The reference ID for the client on the remote gateway
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @param string $transaction_reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The ID of the previously authorized transaction
     * @param float $amount The amount to refund
     * @return array An array of transaction data including:
     *
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard message for
     *      this transaction status (optional)
     */
    public function refundStoredAch(
        $client_reference_id,
        $account_reference_id,
        $transaction_reference_id,
        $transaction_id,
        $amount
    );
    /**
     * Used to determine if offsite ACH customer account information is enabled for the gateway
     * This is invoked after the gateway has been initialized and after Gateway::setMeta() has been called.
     * The gateway should examine its current settings to verify whether or not the system
     * should invoke the gateway's offsite methods
     *
     * @return boolean True if the gateway expects the offset methods to be called for ACH payments,
     *  false to process the normal methods instead
     */
    public function requiresAchStorage();
}
