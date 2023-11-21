<?php

class CwatchResponse
{
    private $status;
    private $raw;
    private $response;
    private $errors;
    private $headers;

    /**
     * CwatchResponse constructor.
     *
     * @param array $apiResponse
     */
    public function __construct(array $apiResponse)
    {
        $this->raw = isset($apiResponse['content']) ? $apiResponse['content'] : '';
        $this->headers = isset($apiResponse['headers']) ? $apiResponse['headers'] : '';
        $response = json_decode($this->raw);
        if (!isset($response->error)) {
            if (empty($response->validationErrors)) {
                $this->status = 200;
                $this->response = $response;
            } else {
                $this->status = 500;
                $this->errors = $response->validationErrors;
            }
        } else {
            $this->status = $response->status;
            $this->errors = $response->message;
            $this->response = $response;
        }
    }

    /**
     * Get the status of this response
     *
     * @return string The status of this response
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * Get the raw data from this response
     *
     * @return string The raw data from this response
     */
    public function raw()
    {
        return $this->raw;
    }

    /**
     * Get the data response from this response
     *
     * @return string The data response from this response
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

    /**
     * Get the headers returned with this response
     *
     * @return string The headers returned with this response
     */
    public function headers()
    {
        return $this->headers;
    }
}
