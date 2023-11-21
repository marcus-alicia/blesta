<?php
/**
 * Maxmind minFraud Phone Identification
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package maxmind
 */
class MaxmindPhoneIdHttp
{
    /**
     * Initialize the MaxmindPhoneIdHttp command
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
     *  - phone Telephone number. For international numbers,
     *      be sure to include the leading "+" sign followed by the country code.
     *  - l Your MaxMind license key.
     * @return MaxmindResponse The response
     */
    public function request($data)
    {
        return $this->api->submit('app/phone_id_http', $data);
    }
}
