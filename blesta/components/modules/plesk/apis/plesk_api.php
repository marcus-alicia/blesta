<?php
use Blesta\Core\Util\Common\Traits\Container;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'plesk_response.php';

/**
 * Plesk API processor
 *
 * Documentation on the Plesk API:
 * v11.5: http://download1.parallels.com/Plesk/PP11/11.5/Doc/en-US/online/plesk-api-rpc/
 * v11.0: http://download1.parallels.com/Plesk/PP11/11.0/Doc/en-US/online/plesk-api-rpc/
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package plesk
 */
class PleskApi
{
    // Load traits
    use Container;

    /**
     * @var string The user to connect as
     */
    private $user;
    /**
     * @var string The password to use when connecting
     */
    private $password;
    /**
     * @var string The host to use when connecting (IP address or hostname)
     */
    private $host;
    /**
     * @var string The port to use when connecting
     */
    private $port;

    /**
     * Sets the connection details
     *
     * @param string $user The username to connect as
     * @param string $password The password to use when connecting
     * @param string $host The host to use when connecting (IP address or hostname)
     * @param string $port The port to use when connecting (optional, default 8443)
     */
    public function __construct($user, $password, $host, $port = 8443)
    {
        $this->user = $user;
        $this->password = $password;
        $this->host = $host;
        $this->port = $port;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Submits a request to the API
     *
     * @param string $xml The XML content to send
     * @param string $xml_container_path The XML container path for this API command
     * @return PleskResponse The response object
     */
    public function submit($xml, $xml_container_path)
    {
        $url = 'https://' . $this->host . ':' . $this->port . '/enterprise/control/agent.php';
        $headers = [
            'HTTP_AUTH_LOGIN: ' . $this->user,
            'HTTP_AUTH_PASSWD: ' . $this->password,
            'HTTP_PRETTY_PRINT: TRUE',
            'Content-Type: text/xml'
        ];

        $this->last_request = [
            'url' => $url,
            'xml' => $xml
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if ($response == false) {
            $this->logger->error(curl_error($ch));
        }

        curl_close($ch);

        return new PleskResponse($response, $xml_container_path);
    }

    /**
     * Returns the details of the last request made
     *
     * @return array An array containg:
     *  - url The URL of the last request
     *  - xml The XML passed to the URL
     */
    public function lastRequest()
    {
        return $this->last_request;
    }

    /**
     * Loads a command class
     *
     * @param string $command The command class filename to load
     * @param array $params A list of params to pass to the constructor of the API command
     * @return mixed The API command object just created
     */
    public function loadCommand($command, array $params = [])
    {
        require_once dirname(__FILE__) . DS . 'plesk_packets.php';

        $packet = new PleskPackets();
        return $packet->create($command, array_merge([$this], $params));
    }
}
