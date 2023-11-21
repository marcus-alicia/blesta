<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'maxmind_response.php';

/**
 * Maxmind API processor
 *
 * Documentation on the Maxmind API: http://dev.maxmind.com/minfraud/
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package maxmind
 */
class MaxmindApi
{
    /**
     * @var string The Maxmind minfraud server hostname
     */
    private $server;

    /**
     * Initialize the API
     *
     * @param string $server The server to process requests with
     */
    public function __construct($server = 'minfraud.maxmind.com')
    {
        $this->server = $server;
    }

    /**
     * Submits a request to the API
     *
     * @param string $uri The URI to submit to
     * @param array $args An array of key/value pair arguments to submit to the given API command
     * @return MaxmindResponse The response object
     */
    public function submit($uri, array $args = [])
    {
        $url = 'https://' . $this->server . '/' . $uri;

        $this->last_request = [
            'url' => $url,
            'args' => $args
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);

        return new MaxmindResponse($response);
    }

    /**
     * Returns the details of the last request made
     *
     * @return array An array containg:
     *  - url The URL of the last request
     *  - args The paramters passed to the URL
     */
    public function lastRequest()
    {
        return $this->last_request;
    }
}
