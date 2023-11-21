<?php
use Blesta\Core\Util\Common\Traits\Container;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'solusvm_response.php';

/**
 * SolusVM API processor
 *
 * Documentation on the SolusVM API: http://docs.solusvm.com/admin_api
 * Documentation moved to: http://docs.solusvm.com/v2/Default.htm#Developer/Admin-Api/Admin-Api.htm
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package solusvm
 */
class SolusvmApi
{
    // Load traits
    use Container;

    /**
     * @var string The user ID to connect as
     */
    private $user_id;
    /**
     * @var string The key to use when connecting
     */
    private $key;
    /**
     * @var string The host to use when connecting (IP address or hostname)
     */
    private $host;
    /**
     * @var string The port to use when connecting
     */
    private $port;
    /**
     * @var array An array representing the last request made
     */
    private $last_request = ['url' => null, 'args' => null];

    /**
     * Sets the connection details
     *
     * @param string $user_id The user ID to connect as
     * @param string $key The key to use when connecting
     * @param string $host The host to use when connecting (IP address or hostname)
     * @param string $port The port to use when connecting
     */
    public function __construct($user_id, $key, $host, $port = 5656)
    {
        $this->user_id = $user_id;
        $this->key = $key;
        $this->host = $host;
        $this->port = $port;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Submits a request to the API
     *
     * @param string $command The command to submit (e.g. vserver-create)
     * @param array $args An array of key/value pair arguments to submit to the given API command
     * @return SolusvmResponse The response object
     */
    public function submit($command, array $args = [])
    {
        $url = 'https://' . $this->host . ':' . $this->port . '/api/admin/command.php';

        $args['id'] = $this->user_id;
        $args['key'] = $this->key;
        $args['action'] = $command;

        $this->last_request = [
            'url' => $url,
            'args' => $args
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Expect:']);

        $response = curl_exec($ch);

        if ($response == false) {
            $this->logger->error(curl_error($ch));
        }

        curl_close($ch);

        return new SolusvmResponse($response);
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
}
