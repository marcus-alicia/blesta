<?php
/**
 * OpenSRS API response handler
 *
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package opensrs
 */
class OpensrsResponse
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
     * Initializes the Opensrs Response
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
     * Returns the CommandResponse
     *
     * @return stdClass A stdClass object representing the CommandResponses, null if invalid response
     */
    public function response() : ?object
    {
        if ($this->xml && $this->xml instanceof SimpleXMLElement) {
            return $this->formatResponse($this->xml->body->data_block);
        }

        return null;
    }

    /**
     * Returns the status of the API Responses
     *
     * @return string The status (OK = success, ERROR = error, null = invalid responses)
     */
    public function status() : ?string
    {
        if ($this->xml && $this->xml instanceof SimpleXMLElement) {
            return ($this->formatResponse($this->xml->body->data_block)->is_success ?? false) ? 'OK' : 'ERROR';
        }

        return null;
    }

    /**
     * Returns all errors contained in the response
     *
     * @return stdClass A stdClass object representing the errors in the response, null if invalid response
     */
    public function errors() : ?object
    {
        if ($this->xml && $this->xml instanceof SimpleXMLElement) {
            $error = 'Internal Server Error';

            $error_msg = $this->formatResponse($this->xml->body->data_block)->attributes['error']
                ?? $this->formatResponse($this->xml->body->data_block)->response_text
                ?? $error;

            return (object)[
                'response_text' => $error_msg,
                'response_code' => $this->formatResponse($this->xml->body->data_block)->response_code ?? 500
            ];
        }
        
        return null;
    }

    /**
     * Returns the raw response
     *
     * @return string The raw response
     */
    public function raw() : string
    {
        return $this->raw;
    }

    /**
     * Formats the given $data into a stdClass object by first JSON encoding, then JSON decoding it
     *
     * @param mixed $data The data to convert to a stdClass object
     * @return stdClass $data in a stdClass object form
     */
    private function formatResponse($data) : object
    {
        return (object) $this->castToArray($data);
    }

    /**
     * Casts a SimpleXML dt_assoc or dt_array entity, into an array
     *
     * @param mixed $data The data to convert to an array
     * @return array $data in array form
     */
    private function castToArray($data) : array
    {
        $array = [];

        // Parse object
        if (isset($data->dt_assoc)) {
            foreach ($data->dt_assoc as $item) {
                $i = 0;
                foreach ($item as $entry) {
                    $attributes = $entry->attributes();
                    $key = (string)$attributes['key'] ?? $i;

                    if (!empty(trim((string) $entry)) || is_numeric(trim((string) $entry))) {
                        $array[$key] = (string) $entry;
                    } else {
                        $array[$key] = $this->castToArray($entry);
                    }

                    if (empty($array[$key]) && is_array($array[$key])) {
                        $array[$key] = null;
                    }

                    $i++;
                }
            }
        }

        // Parse array
        if (isset($data->dt_array)) {
            foreach ($data->dt_array as $item) {
                $i = 0;
                foreach ($item as $entry) {
                    $attributes = $entry->attributes();
                    $key = (string)$attributes['key'] ?? $i;

                    if (!empty(trim((string) $entry)) || is_numeric(trim((string) $entry))) {
                        $array[$key] = (string) $entry;
                    } else {
                        $array[$key] = $this->castToArray($entry);
                    }

                    if (empty($array[$key]) && is_array($array[$key])) {
                        $array[$key] = null;
                    }

                    $i++;
                }
            }
        }

        return $array;
    }
}
