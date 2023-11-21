<?php
/**
 * Abstract class that all Merchant Gateways must extend
 *
 * @package blesta
 * @subpackage blesta.components.gateways
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class MerchantGateway extends Gateway
{
    /**
     * @var Http An Http object, used to make HTTP requests
     */
    protected $Http;

    /**
     * Used to determine whether this gateway can be configured for autodebiting accounts
     *
     * @return boolean True if the customer must be present (e.g. in the case of credit card customer
     *  must enter security code), false otherwise
     */
    abstract public function requiresCustomerPresent();

    /**
     * Process a request over HTTP using the supplied method type, url and parameters.
     *
     * @param string $method The method type (e.g. GET, POST)
     * @param string $url The URL to post to
     * @param mixed An array of parameters or a URL encoded list of key/value pairs
     * @param string The output result from executing the request
     */
    protected function httpRequest($method, $url = null, $params = null)
    {
        if (!isset($this->Http)) {
            Loader::loadComponents($this, ['Net']);
            $this->Http = $this->Net->create('Http');
        }

        if (is_array($params)) {
            $params = http_build_query($params);
        }

        return $this->Http->request($method, $url, $params);
    }

    /**
     * Fetches an array containing the error response to be set using Input::setErrors()
     *
     * @param string $type The type of error to fetch. Values include:
     *
     *  - card_number_invalid
     *  - card_expired
     *  - routing_number_invalid
     *  - account_number_invalid
     *  - duplicate_transaction
     *  - card_not_accepted
     *  - invalid_security_code
     *  - address_verification_failed
     *  - transaction_not_found The transaction was not found on the remote gateway
     *  - unsupported The action is not supported by the gateway
     *  - general A general error occurred
     * @return mixed An array containing the error to populate using Input::setErrors(),
     *  false if the type does not exist
     */
    protected function getCommonError($type)
    {
        Language::loadLang('merchant_gateway');

        $message = '';
        $field = '';

        switch ($type) {
            case 'card_number_invalid':
                $field = 'card_number';
                $message = Language::_('MerchantGateway.!error.card_number_invalid', true);
                break;
            case 'card_expired':
                $field = 'card_exp';
                $message = Language::_('MerchantGateway.!error.card_expired', true);
                break;
            case 'routing_number_invalid':
                $field = 'routing_number';
                $message = Language::_('MerchantGateway.!error.routing_number_invalid', true);
                break;
            case 'account_number_invalid':
                $field = 'account_number';
                $message = Language::_('MerchantGateway.!error.account_number_invalid', true);
                break;
            case 'duplicate_transaction':
                $field = 'amount';
                $message = Language::_('MerchantGateway.!error.duplicate_transaction', true);
                break;
            case 'card_not_accepted':
                $field = 'type';
                $message = Language::_('MerchantGateway.!error.card_not_accepted', true);
                break;
            case 'invalid_security_code':
                $field = 'card_security_code';
                $message = Language::_('MerchantGateway.!error.invalid_security_code', true);
                break;
            case 'address_verification_failed':
                $field = 'zip';
                $message = Language::_('MerchantGateway.!error.address_verification_failed', true);
                break;
            case 'transaction_not_found':
                $field = 'transaction_id';
                $message = Language::_('MerchantGateway.!error.transaction_not_found', true);
                break;
            case 'unsupported':
                $message = Language::_('MerchantGateway.!error.unsupported', true);
                break;
            case 'general':
                $message = Language::_('MerchantGateway.!error.general', true);
                break;
            default:
                return false;
        }

        return [
            $field => [
                $type => $message
            ]
        ];
    }
}
