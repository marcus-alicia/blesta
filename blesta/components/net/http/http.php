<?php
/**
 * Http component that wraps cURL
 *
 * @package blesta
 * @subpackage blesta.components.net.http
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Http
{
    /**
     * @var resource The curl connection
     */
    private $curl;
    /**
     * @var array An array of headers to sent during the request
     */
    private $headers = [];
    /**
     * @var string The cookie file to use during the request
     */
    private $cookie_file;
    /**
     * @var int The HTTP response code returned from the last request
     */
    private $response_code;
    /**
     * @var bool True to verify the SSL cert, false otherwise
     */
    private $verify_ssl = false;
    /**
     * @var array An array of options to set for this curl connection
     */
    private $options;
    /**
     * @var resource The stream to send debugging info, null to disable debugging
     */
    private $debug_stream;
    /**
     * @var bool True to enable persistent connections, false to reset the connection for each request
     */
    private $persistent_connection = false;

    /**
     * Creates a new NetHttp object, initializes a curl instance
     */
    public function __construct()
    {
        // Open a new curl connection
        $this->open();
    }

    /**
     * Closes the current curl instance, if open
     */
    public function __destruct()
    {
        // Close any existing curl connection
        $this->close();
    }

    /**
     * Explicitly open the curl instance, and close any open instance
     */
    public function open()
    {
        // Close any existing curl connection, then start a new one
        $this->close();
        $this->curl = curl_init();
    }

    /**
     * Explicitly close the curl instance, if open
     */
    public function close()
    {
        // Close any existing curl connection
        if ($this->curl) {
            curl_close($this->curl);
            $this->curl = null;
            $this->options = null;
        }
    }

    /**
     * Set whether or not this connection should be persistent
     *
     * @param bool $persist True to persist, false not to.
     */
    public function persistenConnection($persist)
    {
        $this->persistent_connection = $persist;
    }

    /**
     * Execute a GET request on the given URL
     *
     * @param string $url The URL to GET
     * @param mixed $params An array of key/value pairs, or a string of the format key=value&...
     * @return string The response from the URL
     * @see NetHttp::responseCode()
     */
    public function get($url, $params = null)
    {
        if (!is_array($params)) {
            parse_str($params, $params);
        }
        return $this->request('GET', $url, $params);
    }

    /**
     * Execut a POST request on the given URL
     *
     * @param string $url The URL to POST to
     * @param mixed $params An array of key/value pairs, or a string of the format key=value&... (optional)
     * @param array $files A multi-dimensional array of files in the format of [0]=>array("name"=>"Name of file",
     *  "file"=>"File path")
     * @return string The response from the URL
     * @see NetHttp::responseCode()
     */
    public function post($url, $params = null, $files = null)
    {
        if (is_array($files)) {
            // If posting files, params must be in array format
            if (!is_array($params)) {
                parse_str($params, $params);
            }

            $num_files = count($files);
            for ($i = 0; $i < $num_files; $i++) {
                $params[$files[$i]['name']]  = new CURLFile(
                    $files[$i]['file'],
                    isset($files[$i]['type']) ? $files[$i]['type'] : null
                );
            }
            unset($num_files);
        }

        return $this->request('POST', $url, $params);
    }

    /**
     * Fetch the response code for that last request
     *
     * @return int The HTTP response code for that last request
     */
    public function responseCode()
    {
        return $this->response_code;
    }

    /**
     * Set the cookie file to use for this request
     *
     * @param string The file name of the cookie file to set
     * @throws Exception thrown when the given $cookie_file does not exist
     */
    public function setCookieFile($cookie_file)
    {
        if (!file_exists($cookie_file)) {
            throw new Exception("'" . $cookie_file . "' does not exist.");
        }
        $this->cookie_file = $cookie_file;
    }

    /**
     * Set an array of headers, overwritting existing header data
     *
     * @param array $headers Headers of the format array("Content Type: text/html", "...")
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * Set a header value, appending it to the existing header data
     *
     * @param string $header Header string of the format "Content Type: text/html"
     */
    public function setHeader($header)
    {
        $this->headers[] = $header;
    }

    /**
     * Set the timeout for the current request
     *
     * @param int $seconds The number of seconds to wait before timing out
     */
    public function setTimeout($seconds)
    {
        $this->setOption(CURLOPT_TIMEOUT, (int)$seconds);
    }

    /**
     * Set the given $option and $value. Accepts CURLOPT_* options.
     *
     * @param int $option The CURLOPT_* option to set
     * @param string $value The value to set
     * @param bool $override True to override the existing value, otherwise will only set the value if not already set
     */
    public function setOption($option, $value, $override = true)
    {
        if (!$override && isset($this->options[$option])) {
            return;
        }
        $this->options[$option] = $value;
    }

    /**
     * Processes the request using the given method and URL, with optional urlencoded parameters
     *
     * @param string $method The request method (e.g. GET, POST, PUT, DELETE, etc.)
     * @param string $url The URL to requets
     * @param mixed $params An array of parametes or a URL encoded string of parameters
     * @return string The response from the URL
     * @see NetHttp::responseCode()
     */
    public function request($method, $url, $params = null)
    {
        if (!$this->curl) {
            $this->open();
        }

        // Set the output to be returned
        $this->setOption(CURLOPT_RETURNTRANSFER, true);

        // Set cookie, if one is enabled
        if ($this->cookie_file) {
            $this->setOption(CURLOPT_COOKIEJAR, $this->cookie_file);
            $this->setOption(CURLOPT_COOKIEFILE, $this->cookie_file);
        }

        // Set whether to verify SSL, if not already set
        $this->setOption(CURLOPT_SSL_VERIFYPEER, $this->verify_ssl, false);
        $this->setOption(CURLOPT_SSL_VERIFYHOST, $this->verify_ssl, false);

        // Set any headers
        if (!empty($this->headers)) {
            $this->setOption(CURLOPT_HTTPHEADER, $this->headers);
        }

        // Set request method
        $this->setOption(CURLOPT_CUSTOMREQUEST, $method);
        // Set request URL
        $this->setOption(CURLOPT_URL, $url);
        // Set request data (if any)
        if ($params) {
            //$this->setOption(CURLOPT_POST, true);
            $this->setOption(CURLOPT_POSTFIELDS, $params);
        }

        if ($this->debug_stream) {
            $this->setOption(CURLOPT_VERBOSE, true);
            $this->setOption(CURLOPT_STDERR, $this->debug_stream);
        }

        // Set options for this request
        $this->buildOptions();
        $result = curl_exec($this->curl);
        $this->response_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        if (!$this->persistent_connection) {
            $this->close();
        }

        return $result;
    }

    /**
     * Enables debugging, which is sent to the given stream
     *
     * @param resource $debug_stream The stream to output debugging info to
     */
    public function debug($debug_stream)
    {
        $this->debug_stream = $debug_stream;
    }

    /**
     * Passes all set options to curl
     *
     * @see NetHttp::setOption()
     */
    private function buildOptions()
    {
        if (!is_array($this->options)) {
            return;
        }

        foreach ($this->options as $opt => $value) {
            curl_setopt($this->curl, $opt, $value);
        }
    }
}
