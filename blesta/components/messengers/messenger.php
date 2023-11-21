<?php

use Blesta\Core\Util\Input\Fields\InputFields;

/**
 * Abstract class that all Messengers must extend
 *
 * @package blesta
 * @subpackage blesta.components.messengers
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class Messenger
{
    /**
     * @var stdClass A stdClass object representing the configuration for this messenger
     */
    protected $config;

    /**
     * @var string The base URI for the requested module action
     */
    public $base_uri;

    /**
     * @var stdClass A stdClass object representing the messenger
     */
    private $messenger;

    /**
     * @var array An array of messages keyed by type (e.g. ['success' => ['message' => ['Message 1', 'Message 2']]])
     */
    private $messages = [];
    /**
     * @var string The random ID to identify the group of this messenger request for logging purposes
     */
    private $log_group;

    /**
     * Returns the name of this messenger
     *
     * @return string The common name of this messenger
     */
    public function getName()
    {
        if (isset($this->config->name)) {
            return $this->translate($this->config->name);
        }
        return null;
    }

    /**
     * Returns the description of this messenger
     *
     * @return string The description of this messenger
     */
    public function getDescription()
    {
        if (isset($this->config->description)) {
            return $this->translate($this->config->description);
        }
        return null;
    }

    /**
     * Returns the version of this messenger
     *
     * @return string The current version of this messenger
     */
    public function getVersion()
    {
        if (isset($this->config->version)) {
            return $this->config->version;
        }
        return null;
    }

    /**
     * Returns the name and URL for the authors of this messenger
     *
     * @return array A numerically indexed array that contains an array
     *  with key/value pairs for 'name' and 'url', representing the name
     *  and URL of the authors of this messenger
     */
    public function getAuthors()
    {
        if (isset($this->config->authors)) {
            foreach ($this->config->authors as &$author) {
                $author = (array)$author;
            }
            return $this->config->authors;
        }
        return null;
    }

    /**
     * Performs any necessary bootstraping actions. Sets Input errors on
     * failure, preventing the messenger from being added.
     *
     * @return array A numerically indexed array of meta data containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function install()
    {
    }

    /**
     * Performs migration of data from $current_version (the current installed version)
     * to the given file set version. Sets Input errors on failure, preventing
     * the messenger from being upgraded.
     *
     * @param string $current_version The current installed version of this messenger
     */
    public function upgrade($current_version)
    {
    }

    /**
     * Performs any necessary cleanup actions. Sets Input errors on failure
     * after the messenger has been uninstalled.
     *
     * @param int $messenger_id The ID of the messenger being uninstalled
     * @param bool $last_instance True if $messenger_id is the last instance
     *  across all companies for this messenger, false otherwise
     */
    public function uninstall($messenger_id, $last_instance)
    {
    }

    /**
     * Send a message.
     *
     * @param mixed $to_user_id The user ID this message is to
     * @param string $content The content of the message to send
     * @param string $type The type of the message to send (optional)
     * @param mixed $deliver_to The destination of the message (optional)
     */
    public function send($to_user_id, $content, $type = null)
    {
    }

    /**
     * Returns a list of the message types supported by the messenger.
     *
     * @return array A list of the supported message types
     */
    public function getTypes()
    {
        if (isset($this->config->types)) {
            return $this->config->types;
        }
        return [];
    }

    /**
     * Returns all fields used when setting up a messenger, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param array $vars A stdClass object representing a set of post fields
     * @return InputFields An InputFields object, containing the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getConfigurationFields(&$vars = [])
    {
        return new InputFields();
    }

    /**
     * Updates the meta data for this messenger
     *
     * @param array $vars An array of messenger info to add
     * @return array A numerically indexed array of meta fields containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function setMeta(array $vars)
    {
        $meta = [];
        foreach ($vars as $key => $value) {
            $meta[] = [
                'key' => $key,
                'value' => $value,
                'encrypted' => 0
            ];
        }
        return $meta;
    }

    /**
     * Returns the relative path from this messenger's directory to the logo for
     * this messenger. Defaults to views/default/images/logo.png
     *
     * @return string The relative path to the messenger's logo
     */
    public function getLogo()
    {
        if (isset($this->config->logo)) {
            return $this->config->logo;
        }
        return 'views/default/images/logo.png';
    }

    /**
     * Sets the messenger to be used for any subsequent requests
     *
     * @param stdClass $messenger A stdClass object representing the messenger
     * @see MessengerManager::get()
     */
    final public function setMessenger($messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * Fetches the messenger currently in use
     *
     * @return stdClass A stdClass object representing the messenger
     */
    final public function getMessenger()
    {
        return $this->messenger;
    }

    /**
     * Retrieves a list of messenger meta data
     *
     * @return stdClass An object representing all messenger meta info
     */
    public function getMessengerMeta()
    {
        if (!isset($this->messenger->id)) {
            return false;
        }

        if (!isset($this->MessengerManager)) {
            Loader::loadModels($this, ['MessengerManager']);
        }

        return $this->MessengerManager->getMeta($this->messenger->id);
    }

    /**
     * Return all validation errors encountered
     *
     * @return mixed Boolean false if no errors encountered, an array of errors otherwise
     */
    public function errors()
    {
        if (isset($this->Input) && is_object($this->Input) && $this->Input instanceof Input) {
            return $this->Input->errors();
        }
    }

    /**
     * Sets a message
     *
     * @param string $type The type of message ('success', 'error', or 'notice')
     * @param string $message The message text to display
     */
    final protected function setMessage($type, $message)
    {
        if (!array_key_exists($type, (array)$this->messages)) {
            $this->messages[$type] = ['message' => []];
        }

        $this->messages[$type]['message'][] = $message;
    }

    /**
     * Retrieves a set of messages set by the messenger
     *
     * @return array An array of messages
     */
    final public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Process a request over HTTP using the supplied method type, url and parameters.
     *
     * @param string $method The method type (e.g. GET, POST)
     * @param string $url The URL to post to
     * @param mixed An array of parameters or a URL encoded list of key/value pairs
     * @param string The output result from executing the request
     */
    protected function httpRequest($method, $url = null, $params = null)
    {
        if (!isset($this->Http)) {
            Loader::loadComponents($this, ['Net']);
            $this->Http = $this->Net->create('Http');
        }

        if (is_array($params)) {
            $params = http_build_query($params);
        }

        return $this->Http->request($method, $url, $params);
    }

    /**
     * Attempts to log the given info to the messenger log.
     *
     * @param string $to_user_id The user ID this message is to
     * @param string $data A string of module data sent along with the request (optional)
     * @param string $direction The direction of the log entry (input or output, default input)
     * @param bool $success True if the message was successfully sent, false otherwise
     * @throws Exception Thrown if the content was invalid and could not be added to the log
     */
    protected function log($to_user_id, $data = null, $direction = 'input', $success = false)
    {
        if (!isset($this->Logs)) {
            Loader::loadModels($this, ['Logs']);
        }

        // Create a random 8-character group identifier
        if ($this->log_group == null) {
            $this->log_group = substr(md5(mt_rand()), mt_rand(0, 23), 8);
        }

        $log = [
            'messenger_id' => $this->messenger->id,
            'to_user_id' => $to_user_id,
            'direction' => $direction,
            'data' => $data,
            'success' => (int) $success,
            'group' => $this->log_group
        ];
        $this->Logs->addMessenger($log);

        if (($error = $this->Logs->errors())) {
            throw new Exception(serialize($error));
        }
    }

    /**
     * Loads a given config file
     *
     * @param string $file The path to the JSON file to load
     */
    protected function loadConfig($file)
    {
        if (file_exists($file)) {
            $this->config = json_decode(file_get_contents($file));
        }
    }

    /**
     * Translate the given str, or passthrough if no translation et
     *
     * @param string $str The string to translate
     * @return string The translated string
     */
    private function translate($str)
    {
        $pass_through = Configure::get('Language.allow_pass_through');
        Configure::set('Language.allow_pass_through', true);
        $str = Language::_($str, true);
        Configure::set('Language.allow_pass_through', $pass_through);

        return $str;
    }
}
