<?php
/**
 * Internet.bs API Response
 *
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package internetbs
 */
class InternetbsResponse
{
    private $status;
    private $raw;
    private $response;
    private $errors = [];
    private $headers;

    /**
     * InternetbsResponse constructor.
     *
     * @param array $api_response The API response
     */
    public function __construct(array $api_response)
    {
        $this->raw = $api_response['content'];
        $this->response = json_decode($api_response['content']);
        $this->headers = $api_response['headers'];

        // Set status
        $this->status = '400';
        if (isset($this->headers[0])) {
            $status_parts = explode(' ', $this->headers[0]);
            if (isset($status_parts[1])) {
                $this->status = (int) $status_parts[1];
            }
        }

        // Set errors
        $this->errors = [];
        if ($this->response && $this->response->status == 'FAILURE' && isset($this->response->message)) {
            $this->status = (int) $this->response->code ?? 500;
            $this->errors[$this->status] = $this->response->message;
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
     * @return stdClass The data response from this response
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
