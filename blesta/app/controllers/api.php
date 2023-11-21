<?php

/**
 * A RESTful API system for interacting with the Blesta backend.
 *
 * All public model methods are accessible. Plugin models may also be invoked by
 * simply formatting the model as Plugin.Model
 * (e.g. /api/plugin.model/method.format). Supports XML, JSON, and PHP as format
 * types.
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Api extends AppController
{
    const OK = 'OK';
    const NOT_FOUND = 'The requested resource does not exist.';
    const UNAUTHORIZED = 'The authorization details given appear to be invalid.';
    const FORBIDDEN = 'The requested resource is not accessible.';
    const UNSUPPORTED_FORMAT = 'The format requested is not supported by the server.';
    const MAINTENANCE_MODE = 'The requested resource is currently unavailable due to maintenance.';
    const INTERNAL_ERROR = 'An unexpected error occured.';
    const BAD_REQUEST = 'The request cannot be fulfilled due to bad syntax.';

    /**
     * @var array The available output formats
     */
    private static $formats = ['json' => 'application/json', 'php' => 'text/plain', 'xml' => 'application/xml'];

    /**
     * @var string The current output format
     */
    private $format = 'json';

    /**
     * @var string The full model string (e.g. Plugin.Model)
     */
    private $model;

    /**
     * @var string The model name to load (e.g. Model)
     */
    private $model_name;

    /**
     * @var string The method of the model to execute
     */
    private $method;

    /**
     * @var mixed An array containing all errors set by the model, false if no errors set
     */
    private $errors = false;

    /**
     * @var string The request method (POST, GET, PUT, DELETE)
     */
    private $request_method = 'POST';

    /**
     * Verify that the request is properly formatted, and ensure the requester
     * is authorized.
     */
    public function preAction()
    {
        // Detect the request method if given, otherwise default to POST
        $this->request_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'POST';

        // Authorize the API user
        $authorized = $this->authenticate();

        // Ensure that the request is formatted correctly
        if (isset($this->get[0]) && isset($this->get[1])) {
            $this->model = '';
            $fields = explode('.', $this->get[0]);
            foreach ($fields as $i => $field) {
                $this->model .= Loader::toCamelCase($field) . (isset($fields[$i + 1]) ? '.' : '');
            }

            $this->model_name = Loader::toCamelCase(
                str_contains($this->model, '.') ? ltrim(strrchr($this->model, '.'), '.') : $this->model
            );

            // Parse method from response format
            $temp = explode('.', $this->get[1], 2);

            if (count($temp) != 2) {
                $this->response(self::UNSUPPORTED_FORMAT);
            }

            $this->method = $temp[0];
            $temp_format = strtolower($temp[1]);

            if (!isset(self::$formats[$temp_format])) {
                $this->response(self::UNSUPPORTED_FORMAT);
            }

            $this->format = $temp_format;
            unset($temp_format);

            // Slice off [0] and [1] from $this->get, and move everything else down
            array_splice($this->get, 0, 2);
        } else {
            // No resource given
            $this->response(self::NOT_FOUND);
        }

        // Only proceed if authorized
        if (!$authorized) {
            $this->response(self::UNAUTHORIZED);
        }
    }

    /**
     * The backbone of the API. Processes the request for the desired resource
     */
    public function index()
    {
        $response = null;

        // Attempt to load the model
        try {
            $this->uses([$this->model]);
        } catch (Throwable $e) {
            // Model does not exist
            $this->response(self::NOT_FOUND);
        }

        // Ensure method exists
        if (!method_exists($this->{$this->model_name}, $this->method)) {
            $this->response(self::NOT_FOUND);
        }
        // Ensure method is callable
        if (!Router::isCallable($this->{$this->model_name}, $this->method, 'Model')) {
            $this->response(self::FORBIDDEN);
        }

        try {
            $params = null;
            if ($this->request_method == 'GET' || $this->request_method == 'DELETE') {
                $params = $this->get;
            } elseif ($this->request_method == 'POST') {
                $params = $this->post;
            } elseif ($this->request_method == 'PUT') {
                $params = $this->put;
            }

            // Sync up submitted parameters with those of the method by name
            $params = $this->matchParameters($params);
            // Invoke the requested method
            $response = call_user_func_array([$this->{$this->model_name}, $this->method], $params);
        } catch (Throwable $e) {
            // Parameter count mismatch, or the method threw an exception
            $this->response(self::INTERNAL_ERROR, $e->getMessage());
        }

        // Check for errors, if any found then return them in the response
        if (($this->errors = $this->{$this->model_name}->errors())) {
            $this->response(self::BAD_REQUEST, $response);
        }

        // The request was successful, return the response
        $this->response(self::OK, $response);
    }

    /**
     * Matches the given parameters with those defined in the requested method.
     *
     * @param array An array of candidate parameters to be passed to the requested method.
     * @return array An array of parameters to be passed to the requested method.
     */
    private function matchParameters($parameters = null)
    {
        $method_params = [];
        if (!is_array($parameters) || empty($parameters)) {
            return $method_params;
        }

        $method = new ReflectionMethod($this->{$this->model_name}, $this->method);
        $params = $method->getParameters();

        // Set all matching named parameters in the order defined by the method
        foreach ($params as $param) {
            if (isset($parameters[$param->name])) {
                $method_params[$param->name] = $parameters[$param->name];
                unset($parameters[$param->name]);
            } else {
                // Use default value for this parameter if not defined in the given $parameters list
                $method_params[$param->name] = $param->getDefaultValue();
            }
        }

        // Set all remaining non-numeric named parameters in the order given
        foreach ($parameters as $name => $value) {
            if (!is_numeric($name)) {
                $method_params[$name] = $value;
            }
        }

        return $method_params;
    }

    /**
     * Outputs the response and data of the current Request in the desired format
     *
     * @param string $response The server response
     * @param mixed $data The data to be output (will be encoded into the appropriate format automatically)
     * @param bool $encode True to automatically encode the given data, false otherwise.
     */
    private function response($response, $data = null, $encode = true)
    {
        // Result data to be returned to the requestor
        $result = [];
        $message = null;

        switch ($response) {
            case self::OK:
            default:
                $code = '200 OK';
                break;
            case self::BAD_REQUEST:
                $code = '400 Bad Request';
                $message = self::BAD_REQUEST;
                break;
            case self::UNAUTHORIZED:
                $code = '401 Unauthorized';
                $message = self::UNAUTHORIZED;
                break;
            case self::FORBIDDEN:
                $code = '403 Forbidden';
                $message = self::FORBIDDEN;
                break;
            case self::NOT_FOUND:
                $code = '404 Not Found';
                $message = self::NOT_FOUND;
                break;
            case self::UNSUPPORTED_FORMAT:
                $code = '415 Unsupported Media Type';
                $message = self::UNSUPPORTED_FORMAT;
                break;
            case self::INTERNAL_ERROR:
                $code = '500 Internal Server Error';
                $message = self::INTERNAL_ERROR;
                break;
            case self::MAINTENANCE_MODE:
                $code = '503 Service Unavailable';
                $message = self::MAINTENANCE_MODE;
                break;
        }

        // If an error occured, set the message and erorrs encountered
        if ($response != self::OK) {
            $result['message'] = $message;

            if ($this->errors) {
                $result['errors'] = $this->errors;
            }
        }

        // Set the response data
        $result['response'] = $data;

        header($this->server_protocol . ' ' . $code);
        header('Content-Type: ' . self::$formats[$this->format]);
        if ($encode) {
            echo $this->encode($result);
        } else {
            echo $result;
        }
        exit;
    }

    /**
     * Encodes the data given into the desired output format
     *
     * @param array $data the Data to encode into $this->format
     * @return string The encoded data based on the format given in $this->format
     */
    private function encode($data)
    {
        switch ($this->format) {
            case 'xml':
                if (isset($this->model)) {
                    $data = [strtolower($this->model) => $data];
                }
                return $this->Xml->makeXML($data);
            default:
            case 'json':
                return json_encode($data);
            case 'php':
                return serialize($data);
        }
    }

    /**
     * Attempt to authenticate the user
     *
     * @return bool True if the user is valid, false otherwise
     */
    private function authenticate()
    {
        $this->uses(['ApiKeys']);
        $authorized = false;

        $api_user = null;
        $api_key = null;

        switch ($this->getAuthMode()) {
            case 'basic':
                if (!isset($_SERVER['PHP_AUTH_USER'])) {
                    $auth = null;
                    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                        $auth = $_SERVER['HTTP_AUTHORIZATION'];
                    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                        $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
                    }

                    $parts = explode(':', base64_decode(substr($auth, 6)));
                    if (count($parts) == 2) {
                        list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = $parts;
                    }
                }
                $api_user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
                $api_key = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;
                $this->put = [];

                if ($this->request_method == 'PUT') {
                    parse_str(file_get_contents('php://input'), $this->put);
                }
                break;
            case 'cli':
                $params = [];
                $this->post = [];
                $this->get = [$this->get[0], $this->get[1]];
                $this->put = [];

                foreach ($_SERVER['argv'] as $i => $val) {
                    // Set the API user
                    if (($val == '-u' || $val == '-user') && isset($_SERVER['argv'][$i + 1])) {
                        $api_user = $_SERVER['argv'][$i + 1];
                    }
                    // Set the API key
                    if (($val == '-k' || $val == '-key') && isset($_SERVER['argv'][$i + 1])) {
                        $api_key = $_SERVER['argv'][$i + 1];
                    }
                    // Set the request method (POST, GET, PUT, DELETE)
                    if (($val == '-m' || $val == '-method') && isset($_SERVER['argv'][$i + 1])) {
                        $this->request_method = strtoupper($_SERVER['argv'][$i + 1]);
                    }
                    // Set parameters
                    if (($val == '-p' || $val == '-params') && isset($_SERVER['argv'][$i + 1])) {
                        parse_str($_SERVER['argv'][$i + 1], $params);
                    }
                }

                // Assign parameters to the property variable
                if ($params) {
                    if ($this->request_method == 'POST') {
                        $this->post = $params;
                    } elseif ($this->request_method == 'PUT') {
                        $this->put = $params;
                    } else {
                        $this->get = array_merge($this->get, $params);
                    }
                }

                break;
        }

        // Authenticate the API user
        $company_id = $this->ApiKeys->auth($api_user, $api_key);
        if (!$company_id) {
            $this->errors = $this->ApiKeys->errors();
        } else {
            $authorized = true;

            if (!isset($this->Companies)) {
                $this->uses(['Companies']);
            }

            // Prime the company
            $this->primeCompany($this->Companies->get($company_id));
        }

        return $authorized;
    }

    /**
     * Returns the authentication mode being used by the user
     *
     * @return string The authentication mode being used by the user
     */
    private function getAuthMode()
    {
        if ($this->is_cli) {
            return 'cli';
        }
        return 'basic';
    }
}
