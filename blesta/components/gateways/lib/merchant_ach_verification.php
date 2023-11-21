<?php
/**
 * Merchant ACH verification processing interface. Defines all methods that an ACH
 * offsite payment gateway must implement if verification is required for the accounts.
 * to be supported.
 *
 * @package blesta
 * @subpackage blesta.components.gateways
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface MerchantAchVerification
{
    /**
     * Returns all HTML markup required to render ACH verification form
     *
     * @param array $vars An array including:
     *
     *  - first_deposit The first deposit amount
     *  - second_deposit The second deposit amount
     * @return string The Html, if any, for confirming payments
     */
    public function buildAchVerificationForm(array $vars = []);
    /**
     * Store an ACH account off site
     *
     * @param array $vars An array including:
     *
     *  - first_deposit The first deposit amount
     *  - second_deposit The second deposit amount
     * @param string $client_reference_id The reference ID for the client on the remote gateway (if one exists)
     * @param string $account_reference_id The reference ID for the stored account on the remote gateway to update
     * @return mixed False on failure or an array containing:
     *
     *  - client_reference_id The reference ID for this client
     *  - reference_id The reference ID for this payment account
     */
    public function verifyAch(array $vars, $client_reference_id = null, $account_reference_id = null);
}
