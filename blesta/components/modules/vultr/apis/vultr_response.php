<?php
/**
 * Vultr API v2 response handler
 *
 * @package blesta
 * @subpackage blesta.components.modules.vultr
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class VultrResponse
{
    /**
     * @var string The raw response from the API.
     */
    private $raw;

    /**
     * @var int The HTTP code response from the API.
     */
    private $status;

    /**
     * @var array An array containing the error messages
     */
    public $status_codes = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        419 => 'Authentication Timeout',
        420 => 'Method Failure',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Method Failure',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'No Response',
        449 => 'Retry With',
        450 => 'Blocked by Windows Parental Controls',
        451 => 'Unavailable For Legal Reasons',
        494 => 'Request Header Too Large',
        495 => 'Cert Error',
        496 => 'No Cert',
        497 => 'HTTP to HTTPS',
        499 => 'Client Closed Request',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        598 => 'Network read timeout error',
        599 => 'Network connect timeout error'
    ];

    /**
     * Initializes the Vultr Response.
     *
     * @param array $response The raw response data from an API request
     */
    public function __construct(array $response)
    {
        $this->raw = $response['content'] ?? json_encode([]);
        $this->status = $response['status'] ?? 418;
    }

    /**
     * Returns the status of the API Response.
     *
     * @return string The status (success, error, null if invalid response)
     */
    public function status()
    {
        return $this->status >= 300 ? 'error' : 'success';
    }

    /**
     * Returns the response.
     *
     * @return stdClass A stdClass object representing the response, null if invalid response
     */
    public function response()
    {
        if ($this->raw) {
            return $this->formatResponse($this->raw);
        }

        return null;
    }

    /**
     * Returns all errors contained in the response.
     *
     * @return stdClass A stdClass object representing the errors in the response, false if invalid response
     */
    public function errors()
    {
        $response = $this->response();

        if (!is_object($response) && !empty($this->raw)) {
            return (object) ['error' => $this->raw, 'status' => $this->status];
        }

        if ($this->status >= 300) {
            return (object) [
                'error' => $response->error ?? $this->status_codes[$this->status] ?? 'Internal Server Error',
                'status' => $this->status
            ];
        }
    }

    /**
     * Returns the raw response.
     *
     * @return string The raw response
     */
    public function raw()
    {
        return $this->raw;
    }

    /**
     * Decodes the response.
     *
     * @param mixed $data The JSON data to convert to a stdClass object
     * @return stdClass $data in a stdClass object form
     */
    private function formatResponse($data)
    {
        $response = json_decode($data);

        if (is_array($response)) {
            $response = (object) $response;
        }

        return $response;
    }
}
