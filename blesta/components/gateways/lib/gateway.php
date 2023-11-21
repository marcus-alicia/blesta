<?php

use Blesta\Core\Util\Common\Traits\Container;

/**
 * Abstract class that all Gateways extend through MerchantGateway or NonmerchantGateway
 *
 * Defines all methods gateways must inherit and provides all methods common between all
 * gateways
 *
 * @package blesta
 * @subpackage blesta.components.gateways
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class Gateway
{
    // Load traits
    use Container;

    /**
     * @var string The random ID to identify the group of this transaction for logging purposes
     */
    private $log_group;
    /**
     * @var int The ID of the staff member using the gateway
     */
    private $staff_id;
    /**
     * @var int The ID of the gateway instance being used
     */
    private $gateway_id;
    /**
     * @var stdClass A stdClass object representing the configuration for this gateway
     */
    protected $config;

    /**
     * Returns the name of this gateway
     *
     * @return string The common name of this gateway
     */
    public function getName()
    {
        if (isset($this->config->name)) {
            return $this->translate($this->config->name);
        }
        return null;
    }

    /**
     * Returns the description of this gateway
     *
     * @return string The description of this gateway
     */
    public function getDescription()
    {
        if (isset($this->config->description)) {
            return $this->translate($this->config->description);
        }
        return null;
    }

    /**
     * Returns the version of this gateway
     *
     * @return string The current version of this gateway
     */
    public function getVersion()
    {
        if (isset($this->config->version)) {
            return $this->config->version;
        }
        return null;
    }

    /**
     * Returns the name and URL for the authors of this gateway
     *
     * @return array The name and URL of the authors of this gateway
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
     * Return all currencies supported by this gateway
     *
     * @return array A numerically indexed array containing all currency codes (ISO 4217 format) this gateway supports
     */
    public function getCurrencies()
    {
        if (isset($this->config->currencies)) {
            return $this->config->currencies;
        }
        return [];
    }

    /**
     * Sets the currency code to be used for all subsequent payments
     *
     * @param string $currency The ISO 4217 currency code to be used for subsequent payments
     */
    abstract public function setCurrency($currency);

    /**
     * Create and return the view content required to modify the settings of this gateway
     *
     * @param array $meta An array of meta (settings) data belonging to this gateway
     * @return string HTML content containing the fields to update the meta data for this gateway
     */
    abstract public function getSettings(array $meta = null);

    /**
     * Validates the given meta (settings) data to be updated for this gateway
     *
     * @param array $meta An array of meta (settings) data to be updated for this gateway
     * @return array The meta data to be updated in the database for this gateway, or reset into the form on failure
     */
    abstract public function editSettings(array $meta);

    /**
     * Returns an array of all fields to encrypt when storing in the database
     *
     * @return array An array of the field names to encrypt when storing in the database
     */
    abstract public function encryptableFields();

    /**
     * Sets the meta data for this particular gateway
     *
     * @param array $meta An array of meta data to set for this gateway
     */
    abstract public function setMeta(array $meta = null);

    /**
     * Performs any necessary bootstraping actions
     */
    public function install()
    {
    }

    /**
     * Performs migration of data from $current_version (the current installed version)
     * to the given file set version
     *
     * @param string $current_version The current installed version of this gateway
     */
    public function upgrade($current_version)
    {
    }

    /**
     * Performs any necessary cleanup actions
     *
     * @param int $gateway_id The ID of the gateway being uninstalled
     * @param boolean $last_instance True if $gateway_id is the last instance across all companies for
     *  this gateway, false otherwise
     */
    public function uninstall($gateway_id, $last_instance)
    {
    }

    /**
     * Returns the relative path from this gateway's directory to the logo for
     * this module. Defaults to views/default/images/logo.png
     *
     * @return string The relative path to the gateway's logo
     */
    public function getLogo()
    {
        if (isset($this->config->logo)) {
            return $this->config->logo;
        }
        return 'views/default/images/logo.png';
    }

    /**
     * Returns the URL to the signup page for this gateway.
     *
     * @return string The URL to the signup page if one exists, null otherwise
     */
    public function getSignupUrl()
    {
        if (isset($this->config->signup_url)) {
            return $this->config->signup_url;
        }
        return null;
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
     * Sets the ID of the gateway for a particular transaction
     *
     * @param int $id The gateway ID
     */
    public function setGatewayId($id)
    {
        $this->gateway_id = $id;
    }

    /**
     * Sets the ID of the staff member on a particular transaction
     *
     * @param int $id The staff ID
     */
    public function setStaffId($id)
    {
        $this->staff_id = $id;
    }

    /**
     * Attempts to log the given info to the gateway log.
     *
     * @param string $url The URL contacted for this request
     * @param string $data A string of gateway data sent along with the request (optional)
     * @param string $direction The direction of the log entry (input or output, default input)
     * @param boolean $success True if the request was successful, false otherwise
     * @return string Returns the 8-character group identifier, used to link log entries together
     * @throws Exception Thrown if $data was invalid and could not be added to the log
     */
    protected function log($url, $data = null, $direction = 'input', $success = false)
    {
        if (!isset($this->Logs)) {
            Loader::loadModels($this, ['Logs']);
        }

        // Create a random 8-character group identifier
        if ($this->log_group == null) {
            $this->log_group = substr(md5(mt_rand()), mt_rand(0, 23), 8);
        }

        // Set the staff ID to the staff ID given, otherwise the current staff member
        $staff_id = $this->staff_id;
        if ($this->staff_id === null) {
            $requestor = $this->getFromContainer('requestor');
            $staff_id = $requestor->staff_id;
        }

        $log = [
            'staff_id' => $staff_id,
            'gateway_id' => $this->gateway_id,
            'direction' => $direction,
            'url' => $url,
            'data' => $data,
            'status' => ($success ? 'success' : 'error'),
            'group' => $this->log_group
        ];
        $this->Logs->addGateway($log);

        if (($error = $this->Logs->errors())) {
            throw new Exception(serialize($error));
        }

        return $this->log_group;
    }

    /**
     * Masks each field listed in $mask_fields that also appears in $data, such
     * that sensitive information is redacted.
     *
     * @param array $data An array of key/value pairs
     * @param array $mask_fields An array of key/value pairs where each key identifies a key in $data and whose
     *  value is an array containing:
     *
     *  - char The character to use as the mask
     *  - length The length of the original data to remain unmasked. A negative number will leave that many
     *      characters unmasked from the end of the string, while a positive number will leave that many characters
     *      unmasked from the beginning of the string, 0 will mask all characters
     * @param string $mask_char The character to use as the mask character if not specificed in $mask_fields array
     * @param int $unmask_length The length and direction of characters to remain unmasked if not specified in
     *  $mask_fields array
     * @return array The $data array with fields masked as necessary
     */
    protected function maskData(array $data, array $mask_fields, $mask_char = 'x', $unmask_length = 0)
    {
        foreach ($mask_fields as $field => $rule) {
            // If $field is a number and $rule is not an array, assume that no
            // rule exists and that $rule is actually the $field value. This allows
            // shorthand notation of field to mask
            if (is_numeric($field) && !is_array($rule)) {
                $field = $rule;
            }

            if (isset($data[$field])) {
                $data[$field] = $this->maskValue($data[$field], $rule, $mask_char, $unmask_length);
            }
        }
        return $data;
    }

    /**
     * Masks each field listed in $mask_fields that also appears in $data, such
     * that sensitive information is redacted. Will recursively traverse $data looking
     * for keys that match those in $mask_fields.
     *
     * @param array $data An array of key/value pairs
     * @param array $mask_fields An array of key/value pairs where each key identifies a key in $data and whose value
     *  is an array containing:
     *
     *  - char The character to use as the mask
     *  - length The length of the original data to remain unmasked. A negative number will leave that many characters
     *      unmasked from the end of the string, while a positive number will leave that many characters unmasked from
     *      the beginning of the string, 0 will mask all characters
     * @param string $mask_char The character to use as the mask character if not specificed in $mask_fields array
     * @param int $unmask_length The length and direction of characters to remain unmasked if not specified in
     *  $mask_fields array
     * @return array The $data array with fields masked as necessary
     */
    protected function maskDataRecursive(array $data, array $mask_fields, $mask_char = 'x', $unmask_length = 0)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskDataRecursive($value, $mask_fields, $mask_char, $unmask_length);
            } else {
                // Rule may be defined numerically, so search for a match on the field name within the list of
                // mask fields for the rule
                $rule = array_search($key, $mask_fields);

                // Rules may also be defined using string index values
                if (($rule !== false) && array_key_exists($key, $mask_fields)) {
                    $rule = $mask_fields[$key];
                }

                if ($rule !== false) {
                    $data[$key] = $this->maskValue($value, $rule, $mask_char, $unmask_length);
                }
            }
        }
        return $data;
    }

    /**
     * Masks the given value using a set of rules, defaulting to a set of mask rules if no specific rule given
     *
     * @param string $value The value to mask
     * @param mixed $rule An array of rule setting, or a string to use $mask_char and $unmask_length
     *  default values instead. Values include:
     *
     *  - char The character to use as the mask
     *  - length The length of the original data to remain unmasked. A negative number will leave that many characters
     *      unmasked from the end of the string, while a positive number will leave that many characters unmasked from
     *      the beginning of the string, 0 will mask all characters
     * @param string $mask_char The character to use as the mask character if not specificed by $rule
     * @param int $unmask_length The length and direction of characters to remain unmasked if not specified by $rule
     */
    private function maskValue($value, $rule, $mask_char, $unmask_length)
    {
        $mask = $mask_char;
        if (is_array($rule) && isset($rule['char'])) {
            $mask = $rule['char'];
        }

        $unmask = $unmask_length;
        if (is_array($rule) && isset($rule['length'])) {
            $unmask = $rule['length'];
        }

        if ($unmask < 0) {
            $unmask_value = substr($value, $unmask);
            $mask_value = substr($value, 0, $unmask);
            $value = str_repeat($mask, strlen($mask_value)) . $unmask_value;
        } elseif ($unmask > 0) {
            $unmask_value = substr($value, 0, $unmask);
            $mask_value = substr($value, $unmask);
            $value = $unmask_value. str_repeat($mask, strlen($mask_value));
        } else {
            $value = str_repeat($mask, strlen($value));
        }

        return $value;
    }

    /**
     * Returns $value if $value isset, otherwise returns $alt
     *
     * @deprecated since v5.1.0, use isset() instead
     *
     * @param mixed $value The value to return if $value isset
     * @param mixed $alt The value to return if $value is not set
     * @return mixed Either $value or $alt
     */
    protected function ifSet(&$value, $alt = null)
    {
        if (isset($value)) {
            return $value;
        }

        return $alt;
    }

    /**
     * Initializes a View object and returns it
     *
     * @param string $file The view file to load
     * @param string $view The view directory name to find the view file
     * @param string $view_path The path to the $view relative to the root web directory
     * @return View An instance of the View object
     */
    protected function makeView($file, $view = 'default', $view_path = null)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $view = new View($file, $view);

        if ($view_path !== null) {
            $view->setDefaultView($view_path);
        }

        return $view;
    }

    /**
     * Loads a given config file
     *
     * @param string $file The full path to the config file to load
     */
    protected function loadConfig($file)
    {
        if (file_exists($file)) {
            $this->config = json_decode(file_get_contents($file));
        }
    }

    /**
     * Fetch a client ID from the given email address
     *
     * @param string $email The email address for the client
     * @return mixed The client ID if found, null otherwise
     */
    protected function clientIdFromEmail($email)
    {
        if (!isset($this->Record)) {
            Loader::loadComponents($this, ['Record']);
        }

        $contact = $this->Record->select(['contacts.client_id'])
            ->from('contacts')
            ->where('contacts.contact_type', '=', 'primary')
            ->where('contacts.email', '=', $email)
            ->fetch();
        if ($contact) {
            return $contact->client_id;
        }
        return null;
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
