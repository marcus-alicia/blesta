<?php
/**
 * 2Checkout API Response
 *
 * @package blesta
 * @subpackage blesta.components.gateways.checkout2.api
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class Checkout2Response
{
    /**
     * @var string The status code of this response
     */
    protected $status;
    /**
     * @var string The raw data from this response
     */
    protected $raw;
    /**
     * @var stdClass The formatted data from this response
     */
    protected $response;
    /**
     * @var array A list of errors from the response data
     */
    protected $errors;
    /**
     * @var array A list of headers from this response
     */
    protected $headers;

    /**
     * 2checkoutResponse constructor.
     *
     * @param array $apiResponse A list of response data including
     *
     *  - headers The headers returned in the API response
     *  - content The data returned in the API response
     */
    abstract public function __construct(array $apiResponse);

    /**
     * Get the status of this response
     *
     * @return string The status of this response
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * Get the raw data from this response
     *
     * @return string The raw data from this response
     */
    public function raw()
    {
        return $this->raw;
    }

    /**
     * Get the formatted data from this response
     *
     * @return stdClass The formatted data from this response
     */
    public function response()
    {
        return $this->response;
    }

    /**
     * Get any errors from this response
     *
     * @return array The errors from this response
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Get the headers returned with this response
     *
     * @return array The headers returned with this response
     */
    public function headers()
    {
        return $this->headers;
    }
}
