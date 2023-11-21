<?php
/**
 * Maxmind minFraud Proxy Detection
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package maxmind
 */
class MaxmindIpauthHttp
{
    /**
     * Initialize the MaxmindIpauthHttp command
     *
     * @param MaxmindApi The Maxmind API
     */
    public function __construct($api)
    {
        $this->api = $api;
    }

    /**
     * Submits the request to Maxmind and returns the result
     *
     * @param array $data An array of input data including:
     *  - i The IP address of the customer placing the order.
     *      This should be passed as a string like "44.55.66.77" or "2001:db8::2:1".
     *  - l Your MaxMind license key.
     * @return MaxmindResponse The response
     */
    public function request($data)
    {
        return $this->api->submit('app/ipauth_http', $data);
    }
}
