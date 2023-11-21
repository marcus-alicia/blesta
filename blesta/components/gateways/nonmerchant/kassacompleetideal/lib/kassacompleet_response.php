<?php

class KassacompleetResponse
{
    private $raw;
    private $response;
    private $errors;

    /**
     * KassacompleetResponse constructor.
     *
     * @param string $apiResponse  The raw data returned by the Kassacompleet API
     */
    public function __construct($apiResponse)
    {
        $this->raw = $apiResponse;
        $this->response = json_decode($apiResponse);
        $this->errors = '';

        if (isset($this->response->error)) {
            $this->errors = isset($this->response->error->value) ? $this->response->error->value : '';
        }
    }

    /**
     * Get the raw data of this response
     *
     * @return string The raw data of this response
     */
    public function raw()
    {
        return $this->raw;
    }

    /**
     * Get the decoded data from this response
     *
     * @return stdClass The decoded data from this response
     */
    public function response()
    {
        return $this->response;
    }

    /**
     * Get any errors from this response
     *
     * @return string The errors from this response
     */
    public function errors()
    {
        return $this->errors;
    }
}
