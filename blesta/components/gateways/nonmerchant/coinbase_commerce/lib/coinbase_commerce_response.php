<?php
/**
 * Coinbase Commerce API response handler
 *
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package coinbase_commerce
 */
class CoinbaseCommerceResponse
{
    /**
     * @var object The parsed response from the API
     */
    private $response;

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
            $this->response = $this->formatResponse($this->raw);
        } catch (Exception $e) {
            // Invalid response
        }
    }

    /**
     * Returns the parsed response
     *
     * @return stdClass A stdClass object representing the response, null if invalid response
     */
    public function response() : ?object
    {
        return $this->response;
    }

    /**
     * Returns the status of the API response
     *
     * @return string The status (success, error, null = invalid responses)
     */
    public function status() : ?string
    {
        if (!empty($this->response->error)) {
            return 'error';
        } else if (is_object($this->response) && !empty($this->response)) {
            return 'success';
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
        return $this->response->error ?? null;
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
     * Formats the given $data into a stdClass object
     *
     * @param mixed $data The data to convert to a stdClass object
     * @return stdClass $data in a stdClass object form
     */
    private function formatResponse($data) : object
    {
        return (object) json_decode($data);
    }
}
