<?php

class PaystackResponse
{
    private $data;
    private $message;
    private $errors;
    private $status;

    /**
     * CwatchResponse constructor.
     *
     * @param stdClass $apiResponse
     */
    public function __construct(stdClass $apiResponse)
    {
        $this->data = isset($apiResponse->data) && $apiResponse->data ? $apiResponse->data : new stdClass();
        $this->message = '';
        $this->errors = '';

        $message = isset($apiResponse->message) ? $apiResponse->message : '';
        if (isset($apiResponse->status) && $apiResponse->status == true) {
            $this->message = $message;
        } else {
            $this->errors = $message;
        }

        $this->status = isset($apiResponse->status) ? $apiResponse->status : false;
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
     * Get the data from this response
     *
     * @return stdClass The data from this response
     */
    public function data()
    {
        return $this->data;
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
     * Get the message returned with this response
     *
     * @return string The message returned with this response
     */
    public function message()
    {
        return $this->message;
    }
}
