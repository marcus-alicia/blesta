<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'checkout2_response.php';

/**
 * 2Checkout API V5 Response
 *
 * @package blesta
 * @subpackage blesta.components.gateways.checkout2.api
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Checkout2ResponseV5 extends Checkout2Response
{
    /**
     * 2checkoutResponse constructor.
     *
     * @param array $apiResponse A list of response data including
     *
     *  - headers The headers returned in the API response
     *  - content The data returned in the API response
     */
    public function __construct(array $apiResponse)
    {
        $this->raw = $apiResponse['content'];
        $this->response = json_decode($apiResponse['content']);
        $this->headers = $apiResponse['headers'];

        // Get status code from the header
        $this->status = '400';
        if (isset($this->headers[0])) {
            $status_parts = explode(' ', $this->headers[0]);
            if (isset($status_parts[1])) {
                $this->status = $status_parts[1];
            }
        }

        // Set any errors
        $this->errors = [];
        if (!empty($this->response->error_code) && isset($this->response->message)) {
            $this->errors[$this->response->error_code] = $this->response->message;
        }
    }
}