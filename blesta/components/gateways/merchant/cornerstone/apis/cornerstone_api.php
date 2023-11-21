<?php
use Blesta\Core\Util\Common\Traits\Container;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cornerstone_api_response.php';

/**
 * Cornerstone Requester API
 *
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package cornerstone_api
 */
class CornerstoneApi
{
    // Load traits
    use Container;

    const LIVE_URL = 'https://cps.transactiongateway.com/api/transact.php';

    /**
     * @var string The security key to use when connecting
     */
    private $security_key;
    /**
     * @var array An array representing the last request made
     */
    private $last_request = ['url' => null, 'args' => null];

    /**
     * Sets the connection details
     *
     * @param string $security_key The security key to use when connecting
     */
    public function __construct($security_key)
    {
        $this->security_key = $security_key;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Submits a request to the API
     *
     * @param array $args An array of key/value pair arguments to submit to the given API command
     * @return CornerstoneApiResponse The response object
     */
    public function submit(array $args = [])
    {
        $url = self::LIVE_URL;

        $args['security_key'] = $this->security_key;

        $this->last_request = [
            'url' => $url,
            'args' => $args
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
        curl_setopt($ch, CURLOPT_POST, 1);

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (!($data = curl_exec($ch))) {
            $this->logger->error(curl_error($ch));

            return false;
        }

        curl_close($ch);

        return new CornerstoneApiResponse($data);
    }

    /**
     * Returns the details of the last request made
     *
     * @return array An array containing:
     *
     *  - url The URL of the last request
     *  - args The parameters passed to the URL
     */
    public function lastRequest()
    {
        return $this->last_request;
    }
}
