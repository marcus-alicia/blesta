<?php
use Blesta\Core\Util\Common\Traits\Container;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'enom_response.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . 'enom_all.php';

/**
 * Enom API processor
 *
 * Documentation on the Enom API: http://www.enom.com/APICommandCatalog/index.htm
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package enom
 */
class EnomApi
{
    // Load traits
    use Container;

    const SANDBOX_URL = 'https://resellertest.enom.com/interface.asp';
    const LIVE_URL = 'https://reseller.enom.com/interface.asp';

    /**
     * @var string The user to connect as
     */
    private $user;
    /**
     * @var string The key to use when connecting
     */
    private $key;
    /**
     * @var bool Whether or not to process in sandbox mode (for testing)
     */
    private $sandbox;
    /**
     * @var array An array representing the last request made
     */
    private $last_request = ['url' => null, 'args' => null];

    /**
     * Sets the connection details
     *
     * @param string $user The user to connect as
     * @param string $key The key to use when connecting
     * @param bool $sandbox Whether or not to process in sandbox mode (for testing)
     */
    public function __construct($user, $key, $sandbox = true)
    {
        $this->user = $user;
        $this->key = $key;
        $this->sandbox = $sandbox;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Submits a request to the API
     *
     * @param string $command The command to submit
     * @param array $args An array of key/value pair arguments to submit to the given API command
     * @return EnomResponse The response object
     */
    public function submit($command, array $args = [])
    {
        $url = self::LIVE_URL;
        if ($this->sandbox) {
            $url = self::SANDBOX_URL;
        }

        $args['uid'] = $this->user;
        $args['pw'] = $this->key;
        $args['responsetype'] = 'XML';
        $args['command'] = $command;

        $this->last_request = [
            'url' => $url,
            'args' => $args
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($args));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $response = curl_exec($ch);

        if ($response == false) {
            $this->logger->error(curl_error($ch));
        }

        return new EnomResponse($response);
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
