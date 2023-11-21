<?php
use Blesta\Core\Util\Common\Traits\Container;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'opensrs_response.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . 'opensrs_domains.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . 'opensrs_domains_dns.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . 'opensrs_domains_ns.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands'
    . DIRECTORY_SEPARATOR . 'opensrs_domains_provisioning.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands'
    . DIRECTORY_SEPARATOR . 'opensrs_domains_transfer.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . 'opensrs_ssl.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . 'opensrs_users.php';

/**
 * OpenSRS API processor
 *
 * Documentation on the OpenSRS API: https://domains.opensrs.guide/docs
 *
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package opensrs
 */
class OpensrsApi
{
    // Load traits
    use Container;

    const SANDBOX_URL = 'https://horizon.opensrs.net:55443';
    const LIVE_URL = 'https://rr-n1-tor.opensrs.net:55443';

    /**
     * @var string The user to connect as
     */
    private $username;

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
     * @param string $username The user to connect as
     * @param string $key The key to use when connecting
     * @param bool $sandbox Whether or not to process in sandbox mode (for testing)
     */
    public function __construct(string $username, string $key, bool $sandbox = true)
    {
        $this->username = $username;
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
     * @param string $object The API object
     * @return OpensrsResponse The response object
     */
    public function submit(string $command, array $args = [], string $object = 'domain') : OpensrsResponse
    {
        // Build XML document
        $xml_request = $this->buildXml($command, $args, $object);

        // Set API endpoint url
        $url = self::LIVE_URL;
        if ($this->sandbox) {
            $url = self::SANDBOX_URL;
        }

        // Build signature
        $siganture = md5($xml_request . $this->key);
        $headers = [
            'Content-Type: text/xml',
            'X-Username: ' . trim($this->username),
            'X-Signature: ' . md5($siganture . $this->key),
            'Content-Length: ' . strlen($xml_request)
        ];

        // Set last request
        $this->last_request = [
            'url' => $url,
            'args' => $args
        ];

        // Send request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_request);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

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

        curl_close($ch);

        return new OpensrsResponse($response);
    }

    /**
     * Builds the XML request to be sent to the API
     *
     * @param string $command The command to call from the API
     * @param array $args The arguments to be sent to the executed command
     * @param string $object The API object
     */
    private function buildXml(string $command, array $args = [], string $object = 'domain') : string
    {
        // Build XML document
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="no"?><OPS_envelope/>');

        $xml->addChild('header')
            ->addChild('version', '0.9');

        $dt_assoc = $xml->addChild('body')
            ->addChild('data_block')
            ->addChild('dt_assoc');
        $dt_assoc->addChild('item', 'XCP')
            ->addAttribute('key', 'protocol');
        $dt_assoc->addChild('item', strtoupper($command))
            ->addAttribute('key', 'action');
        $dt_assoc->addChild('item', strtoupper($object))
            ->addAttribute('key', 'object');

        // Build attributes
        $dt_assoc = $dt_assoc->addChild('item');
        $dt_assoc->addAttribute('key', 'attributes');
        $dt_assoc = $dt_assoc->addChild('dt_assoc');

        $this->buildRecursiveAttributes($dt_assoc, $args);

        // Format XML document
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        return $dom->saveXML();
    }

    /**
     * Appends a multi-dimensional array to a SimpleXMLElement object
     *
     * @param SimpleXMLElement $dt_assoc SimpleXMLElement object representing the API request
     * @param array $args A multi-dimensional array of arguments to append to the XML request
     */
    private function buildRecursiveAttributes(SimpleXMLElement &$dt_assoc, array $args) : void
    {
        foreach ($args as $key => $value) {
            if (is_array($value)) {
                $assoc = $dt_assoc->addChild('item');
                $assoc->addAttribute('key', $key);
                $assoc = $assoc->addChild(isset($value[0]) ? 'dt_array' : 'dt_assoc');

                $this->buildRecursiveAttributes($assoc, $value);
            } else {
                $dt_assoc->addChild('item', $value)
                    ->addAttribute('key', $key);
            }
        }
    }

    /**
     * Returns the details of the last request made
     *
     * @return array An array containing:
     *  - url The URL of the last request
     *  - args The parameters passed to the URL
     */
    public function lastRequest() : array
    {
        return $this->last_request;
    }
}
