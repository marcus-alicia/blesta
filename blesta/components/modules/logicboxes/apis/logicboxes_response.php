<?php
/**
 * LogicBoxes API response handler
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package logicboxes
 */
class LogicboxesResponse
{
    /**
     * @var stdClass A stdClass object representing the response
     */
    private $response;
    /**
     * @var string The raw response from the API (JSON)
     */
    private $raw;

    /**
     * Initializes the LogicBoxes Response
     *
     * @param string $response The raw XML response data from an API request
     */
    public function __construct($response)
    {
        $this->raw = $response;
        $this->response = $this->formatResponse($this->raw);
    }

    /**
     * Returns the CommandResponse
     *
     * @return stdClass A stdClass object representing the CommandResponses, null if invalid response
     */
    public function response()
    {
        return $this->response;
    }

    /**
     * Returns the status of the API Responses
     *
     * @return string The status (OK = success, ERROR = error, null = invalid responses)
     */
    public function status()
    {
        if ($this->errors()) {
            return 'ERROR';
        } elseif ($this->raw) {
            return 'OK';
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
        if (isset($this->response->status) && strtolower($this->response->status) == 'error') {
            return $this->response;
        } elseif (isset($this->response->status) && strtolower($this->response->status) == 'failed') {
            return (object)['message' => $this->response->actionstatusdesc];
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
        return json_decode($data);
    }
}
