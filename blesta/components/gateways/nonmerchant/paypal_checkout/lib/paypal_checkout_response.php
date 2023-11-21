<?php
/**
 * PayPal Checkout API Response
 *
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package paypal_checkout.commands
 */
class PaypalCheckoutResponse
{
    private $status;
    private $raw;
    private $response;
    private $errors;
    private $headers;

    /**
     * PaypalCheckoutResponse constructor.
     *
     * @param array $api_response The API response
     */
    public function __construct(array $api_response)
    {
        $this->raw = $api_response['content'];
        $this->response = json_decode($api_response['content']);
        $this->headers = $api_response['headers'];

        $this->status = '400';
        if (isset($this->headers[0])) {
            $status_parts = explode(' ', $this->headers[0]);
            if (isset($status_parts[1])) {
                $this->status = $status_parts[1];
            }
        }

        // Set errors
        $this->errors = [];
        if (!preg_match('/20[0-9]/', $this->status)) {
            $error_key = $this->response->name ?? 'internal';
            $this->errors = [
                $error_key => $this->response->message ?? 'An unknown error occurred'
            ];
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
     * @return mixed The data response from this response
     */
    public function response()
    {
        return $this->response;
    }

    /**
     * Get any errors from this response
     *
     * @return array The errors from this response
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
