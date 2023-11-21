<?php
/**
 * Plesk API response handler
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package plesk
 */
class PleskResponse
{
    /**
     * @var SimpleXMLElement The XML parsed response from the API
     */
    private $xml;
    /**
     * @var string The XML API container path where the results reside
     */
    private $xml_container_path;
    /**
     * @var string The raw response from the API (XML)
     */
    private $raw;

    /**
     * Initializes the Plesk Response
     *
     * @param string $response The raw XML response data from an API request
     */
    public function __construct($response, $xml_container_path)
    {
        $this->raw = $response;
        $this->xml_container_path = $xml_container_path;

        try {
            $this->xml = new SimpleXMLElement($response);
        } catch (Exception $e) {
            // Nothing to do
        }
    }

    /**
     * Returns the status of the API Response
     *
     * @return string The status (ok, error, or null if invalid response)
     */
    public function status()
    {
        if ($this->xml && $this->xml instanceof SimpleXMLElement) {
            $response = $this->xml->xpath($this->xml_container_path);
            $response = (isset($response[0]) ? $response[0] : $response);

            // Only check the first result for status
            if (empty($response)) {
                // System error
                $response = $this->xml->xpath('/packet/system');
                $response = (isset($response[0]) ? $response[0] : $response);
                return (string)$response->status;
            }
            return (string)$response->result->status;
        }
        return null;
    }

    /**
     * Returns the response
     *
     * @return stdClass A stdClass object representing the response, null if invalid response
     */
    public function response()
    {
        if ($this->xml && $this->xml instanceof SimpleXMLElement) {
            return $this->formatResponse($this->xml);
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
            $response = $this->xml->xpath($this->xml_container_path);

            $response = (isset($response[0]) ? $response[0] : $response);

            // Check for system error
            if (empty($response)) {
                // System error
                $response = $this->xml->xpath('/packet/system');
                $response = (isset($response[0]) ? $response[0] : $response);
                return json_decode(json_encode($response));
            }

            // Only check the first result for status
            if ($response->result->status == 'error') {
                return json_decode(json_encode($response->result));
            }
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
     * Decodes the response
     *
     * @param mixed $data The JSON data to convert to a stdClass object
     * @return stdClass $data in a stdClass object form
     */
    private function formatResponse($data)
    {
        $response = $data->xpath($this->xml_container_path);
        $response = (isset($response[0]) ? $response[0] : $response);

        if (empty($response)) {
            // System error
            $response = $this->xml->xpath('/packet/system');
            $response = (isset($response[0]) ? $response[0] : $response);
        }
        return json_decode(json_encode($response));
    }
}
