<?php
/**
 * Quantum Gateway XML Response
 *
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package quantum_xml
 */
class QuantumXmlResponse
{
    /**
     * @var SimpleXMLElement The XML parsed response from the API
     */
    private $xml;
    /**
     * @var string The raw response from the API
     */
    private $raw;

    /**
     * Initializes the Namecheap Response
     *
     * @param string $response The raw XML response data from an API request
     */
    public function __construct($response)
    {
        $this->raw = $response;

        try {
            $this->xml = new SimpleXMLElement($this->raw);
        } catch (Exception $e) {
            // Invalid response
        }
    }

    /**
     * Returns the gateway response
     *
     * @return stdClass A stdClass object representing the gateway response result, null if invalid response
     */
    public function response()
    {
        if ($this->xml && $this->xml instanceof SimpleXMLElement) {
            return $this->formatResponse($this->xml->Result);
        }
        return null;
    }

    /**
     * Returns the status of the API Responses
     *
     * @return string The status (success, error, null = invalid responses)
     */
    public function status()
    {
        if ($this->xml && $this->xml instanceof SimpleXMLElement) {
            return strtolower((string)$this->xml->ResponseSummary->Status);
        }
        return null;
    }

    /**
     * Returns all errors contained in the response
     *
     * @return stdClass A stdClass object representing the errors in the response, false if invalid response
     */
    public function errors()
    {
        if ($this->xml && $this->xml instanceof SimpleXMLElement) {
            return (object)[$this->xml->ResponseSummary->Status => $this->xml->ResponseSummary->StatusDescription];
        }
        return false;
    }

    /**
     * Returns the raw response
     *
     * @return string The raw response
     */
    public function raw()
    {
        return $this->raw;
    }

    /**
     * Formats the given $data into a stdClass object by first JSON encoding, then JSON decoding it
     *
     * @param mixed $data The data to convert to a stdClass object
     * @return stdClass $data in a stdClass object form
     */
    private function formatResponse($data)
    {
        return json_decode(json_encode($data));
    }
}
