<?php
/**
 * SolusVM API response handler
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package solusvm
 */
class SolusvmResponse
{
    /**
     * @var SimpleXMLElement The XML parsed response from the API
     */
    private $xml;
    /**
     * @var string The raw response from the API (XML)
     */
    private $raw;

    /**
     * Initializes the SolusVM Response
     *
     * @param string $response The raw XML response data from an API request
     */
    public function __construct($response)
    {
        $this->raw = $response;

        // Make valid XML if no XML tag given
        if (substr($response, 0, 5) != '<?xml') {
            $response = "<?xml version='1.0'?>\n<document>\n" .
                $response . '</document>';
        } else {
            // Make valid XML if no wrapper tag given
            $response = "<?xml version='1.0'?>\n<document>\n" .
                substr_replace($response, '', 0, strpos($response, "\n")+1) .
                '</document>';
        }

        // Encode any ampersands
        $response = str_replace('&', '&amp;', $response);

        try {
            // Fix an API malformatted XML response where a tag ends but does not begin
            if (preg_match("/<\/sessionexpire>/", $response) && !preg_match('/<sessionexpire>/', $response)) {
                $response = preg_replace("/<\/sessionexpire>/", '', $response);
            }

            $this->xml = new SimpleXMLElement($response);
        } catch (Exception $e) {
            // Invalid response
        }
    }

    /**
     * Returns the status of the API Response
     *
     * @return string The status (success, error, null if invalid response)
     */
    public function status()
    {
        if ($this->xml && $this->xml instanceof SimpleXMLElement) {
            return (string)$this->xml->status;
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
            if ($this->xml->status == 'error') {
                return $this->formatResponse($this->xml);
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
        return json_decode(json_encode($data));
    }
}
