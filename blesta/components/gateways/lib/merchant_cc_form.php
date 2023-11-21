<?php
/**
 * Merchant Credit Card custom form interface. Defines all methods that a
 * credit card form payment gateway must implement.
 *
 * Modifiying the form can allow the gateway to add reference_id and client_reference_id fields
 * or javascript to make calls to a gateway's JS API
 *
 * @package blesta
 * @subpackage blesta.components.gateways
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface MerchantCcForm
{
    /**
     * Returns all HTML markup required to render credit card payment form
     *
     * @return mixed A string of HTML markup required to render an credit card payment form,
     *  or an array of HTML markup
     */
    public function buildCcForm();

    /**
     * Gets an Html form to use as an addition to the regular payment confirmation pages in Blesta
     *
     * @param string $reference_id The reference ID of an entity related to the remote transaction
     * @param string $transaction_id The reference ID of the remote transaction
     * @param int $amount The amount of the remote transaction
     * @return string The Html, if any, for confirming payments
     */
    public function buildPaymentConfirmation($reference_id, $transaction_id, $amount);
}