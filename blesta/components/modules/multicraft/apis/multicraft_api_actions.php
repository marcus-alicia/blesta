<?php
/**
 * Multicraft API Actions
 *
 * @package blesta
 * @subpackage blesta.components.modules.multicraft.apis.commands
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class MulticraftApiActions
{
    /**
     * @var Log entries
     */
    private $logs;
    /**
     * @var The MulticraftApi
     */
    private $api;
    /**
     * @var The URL of the server where the action is to be performed
     */
    private $url;

    /**
     * Initialize
     *
     * @param MulticraftApi $api An instance of the Multicraft API
     * @param string $url The URL where the API action is being performed (optional)
     */
    public function __construct(MulticraftApi $api, $url = "")
    {
        $this->api = $api;
        $this->url = $url;
        $this->resetLogs();
    }

    /**
     * Calls the API method
     *
     * @param string $name The method name to call
     * @param array $vars Arguments to pass to the method
     * @return mixed An array containing the response, or null on error
     */
    public function __call($name, array $vars)
    {
        return $this->submit($name, $vars);
    }

    /**
     * Performs an action and logs its attempt
     *
     * @param string $method The API method to call
     * @param array $vars A list arguments to pass to the method (optional)
     * @return mixed An array containing the response, or null on error
     */
    protected function submit($method, array $vars = [])
    {
        $response = null;
        $raw_response = null;

        try {
            $response = call_user_func_array([$this->api, $method], $vars);
            $raw_response = $this->api->rawResponse();
        } catch (Exception $e) {
            // Error
        }

        // Add a log entry for this action
        $success = (isset($response['success']) && $response['success']);
        $masked_params = $vars;
        if (isset($masked_params['login_password'])) {
            $masked_params['login_password'] = "***";
        }
        // The third parameter to the createUser method is a password
        if (strtolower($method) === 'createuser' && isset($masked_params[2])) {
            $masked_params[2] = "***";
        }

        $this->log(
            [
                'url' => $this->url . "|" . $method,
                'data' => ($masked_params !== null ? serialize($masked_params) : null),
                'success' => true
            ],
            [
                'url' => $this->url . "|" . $method,
                'data' => serialize($raw_response),
                'success' => $success
            ]
        );

        return $response;
    }

    /**
     * Records a set of input/output log entries for an API command
     *
     * @param array $input
     * @param array $output
     */
    protected function log($input, $output)
    {
        $this->logs[] = ['input' => $input, 'output' => $output];
    }

    /**
     * Retrieves any logs set
     *
     * @return array An array of logs containing input and output log data
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * Resets the logs
     */
    public function resetLogs()
    {
        $this->logs = [];
    }
}
