<?php
use Blesta\Core\Util\Common\Traits\Container;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'logicboxes_response.php';

/**
 * LogicBoxes API processor
 *
 * Documentation on the LogicBoxes API: http://manage.logicboxes.com/kb/answer/744
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package logicboxes
 */
class LogicboxesApi
{
    // Load traits
    use Container;

    const SANDBOX_URL = 'https://test.httpapi.com/api/';
    const LIVE_URL = 'https://httpapi.com/api/';
    const RESPONSE_FORMAT = 'json';

    /**
     * @var string The reseller ID to connect as
     */
    private $reseller_id;
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
     * @param string $reseller_id The reseller ID to connect as
     * @param string $key The key to use when connecting
     * @param bool $sandbox Whether or not to process in sandbox mode (for testing)
     */
    public function __construct($reseller_id, $key, $sandbox = false)
    {
        $this->reseller_id = $reseller_id;
        $this->key = $key;
        $this->sandbox = $sandbox;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Submits a request to the API
     *
     * @param string $command The command to submit (e.g. domains/available)
     * @param array $args An array of key/value pair arguments to submit to the given API command
     * @param string $method The request method (GET, POST, PUT, DELETE, etc.)
     * @return LogicboxesResponse The response object
     */
    public function submit($command, array $args = [], $method = 'POST')
    {
        $url = self::LIVE_URL;
        if ($this->sandbox) {
            $url = self::SANDBOX_URL;
        }

        $url .= $command . '.' . self::RESPONSE_FORMAT;

        $args['auth-userid'] = $this->reseller_id;
        $args['api-key'] = $this->key;

        $this->last_request = [
            'url' => $url,
            'args' => $args
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        if ($method == 'GET') {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . $this->buildQuery($args));
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->buildQuery($args));
        }

        $response = curl_exec($ch);

        if ($response == false) {
            $this->logger->error(curl_error($ch));
        }

        curl_close($ch);

        return new LogicboxesResponse($response);
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

    /**
     * Loads a command class
     *
     * @param string $command The command class filename to load
     */
    public function loadCommand($command)
    {
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . $command . '.php';
    }

    /**
     * Implementation of http_build_query() that treats numerical arrays as duplicate key values
     *
     * @param array An array of data
     * @return string A string of parameters encoded per RFC 3986
     */
    private function buildQuery(array $args)
    {
        $query = [];
        foreach ($args as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subkey => $subvalue) {
                    if (is_numeric($subkey)) {
                        $query[] = rawurlencode($key) . '=' . rawurlencode($subvalue);
                    } else {
                        $query[] = rawurlencode($key . '[' . $subkey . ']') . '=' . rawurlencode($subvalue);
                    }
                }
            } else {
                $query[] = rawurlencode($key) . '=' . rawurlencode($value);
            }
        }
        return implode('&', $query);
    }
}
