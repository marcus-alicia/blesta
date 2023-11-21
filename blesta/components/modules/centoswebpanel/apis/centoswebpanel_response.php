<?php

class CentoswebpanelResponse
{
    private $status;
    private $raw;
    private $response;
    private $errors;
    private $headers;

    /**
     * CentoswebpanelResponse constructor.
     *
     * @param string $apiResponse
     */
    public function __construct($apiResponse)
    {
        $this->raw = isset($apiResponse['content']) ? $apiResponse['content'] : '';
        $this->headers = isset($apiResponse['headers']) ? $apiResponse['headers'] : '';
        $response = json_decode($this->raw);
        $this->status = 200;
        $this->response = $response;

        if ($response && $response->status == 'Error') {
            $this->errors = $response->msj;
        }

        if (!empty($this->headers)) {
            $headerOne = explode(' ', $this->headers[0]);
            if (count($headerOne) >= 2) {
                $this->status = $headerOne[1];
            }
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
