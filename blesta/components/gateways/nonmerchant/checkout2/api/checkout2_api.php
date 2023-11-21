<?php
/**
 * 2Checkout API
 *
 * @package blesta
 * @subpackage blesta.components.gateways.checkout2.api
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class Checkout2Api
{
    /**
     * Fetches the url to redirect clients to for payment in 2Checkout
     *
     * @return string The payment url
     */
    abstract public function getPaymentUrl();

    /**
     * Refunds a charge in 2Checkout
     *
     * @param array $params A list of parameters for issuing a refund
     * @return \Checkout2Response
     */
    abstract public function refund(array $params);
}
