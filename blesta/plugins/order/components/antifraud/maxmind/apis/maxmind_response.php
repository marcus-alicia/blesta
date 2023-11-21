<?php
/**
 * Maxmind API response handler
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package maxmind
 */
class MaxmindResponse
{
    /**
     * @var string The raw response from the API
     */
    private $raw;

    /**
     * Initializes the Maxmind Response
     *
     * @param string $response The raw response data from an API request
     */
    public function __construct($response)
    {
        $this->raw = $response;
    }

    /**
     * Returns response
     *
     * @return stdClass A stdClass object representing
     */
    public function response()
    {
        return $this->formatResponse();
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
     * Returns all errors contained in the response
     *
     * @return stdClass A stdClass object representing the errors in the response, false if invalid response
     */
    public function errors()
    {
        if ($this->raw) {
            $response = $this->formatResponse();
            if (isset($response->err)) {
                return $response->err;
            }
        }
        return false;
    }

    /**
     * Formats the raw response into a stdClass object
     *
     * @return stdClass $data in a stdClass object form
     */
    private function formatResponse()
    {
        $response = new stdClass();

        $data = explode(';', $this->raw);
        foreach ($data as $parts) {
            $pair = explode('=', $parts, 2);
            if (count($pair) == 2) {
                $response->{$pair[0]} = $pair[1];
            }
        }

        return $response;
    }
}
