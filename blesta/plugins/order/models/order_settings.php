<?php
/**
 * Order Settings
 *
 * Manage all order settings for the plugin
 *
 * @package blesta
 * @subpackage blesta.plugins.order.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderSettings extends OrderModel
{
    /**
     * @var array Input error messages
     */
    private $errors = [];

    /**
     * Fetches all order settings
     *
     * @param int $company_id The company ID
     * @return mixed An array of objects with key/value pairs of settings, false if no results found
     */
    public function getSettings($company_id)
    {
        $settings = $this->Record->select(['key', 'value', 'encrypted'])->
            from('order_settings')->
            where('company_id', '=', $company_id)->
            fetchAll();

        // Decrypt values where necessary
        for ($i=0; $i<count($settings); $i++) {
            if ($settings[$i]->encrypted) {
                $settings[$i]->value = $this->systemDecrypt($settings[$i]->value);
            }
        }
        return $settings;
    }

    /**
     * Fetch a single setting by key name
     *
     * @param int $company_id The company ID
     * @param string $key The key name of the setting to fetch
     * @return mixed An stdObject containing the key and value, false if no such key exists
     */
    public function getSetting($company_id, $key)
    {
        $setting = $this->Record->select(['key', 'value', 'encrypted'])->
            from('order_settings')->
            where('company_id', '=', $company_id)->
            where('key', '=', $key)->fetch();

        if ($setting && $setting->encrypted) {
            $setting->value = $this->systemDecrypt($setting->value);
        }
        return $setting;
    }

    /**
     * Sets a group of settings with key/value pairs
     *
     * @param int $company_id The company ID
     * @param array $settings Settings to set as key/value pairs
     * @see Settings::setSetting()
     */
    public function setSettings($company_id, array $settings)
    {
        foreach ($settings as $key => $value) {
            $this->setSetting($company_id, $key, $value);
        }
    }

    /**
     * Sets the setting with the given key, overwriting any existing value with that key
     *
     * @param int $company_id The company ID
     * @param string $key The setting identifier
     * @param string $value The value to set for this setting
     * @param mixed $encrypted True to encrypt $value, false to store unencrypted,
     *  null to encrypt if currently set to encrypt
     */
    public function setSetting($company_id, $key, $value, $encrypted = null)
    {
        $fields = ['company_id' => $company_id, 'key' => $key, 'value' => $value];

        // Perform input validation on the settings
        $data = [$key => $value];
        $old_errors = $this->Input->errors();
        $this->Input->setRules($this->getSettingRules());

        if (!$this->Input->validates($data)) {
            // This setting is invalid, override any input errors
            $this->overrideInputErrors();
            return;
        } elseif ($old_errors) {
            // Reset the old errors since each new input validation resets errors
            $this->Input->setErrors($old_errors);
        }

        // If encryption is mentioned set the appropriate value and encrypt if necessary
        if ($encrypted !== null) {
            $fields['encrypted'] = (int)$encrypted;
            if ($encrypted) {
                $fields['value'] = $this->systemEncrypt($fields['value']);
            }
        } else {
            // Check if the value is currently encrypted and encrypt if necessary
            $setting = $this->getSetting($company_id, $key);
            if ($setting && $setting->encrypted) {
                $fields['encrypted'] = 1;
                $fields['value'] = $this->systemEncrypt($fields['value']);
            }
        }

        $this->Record->duplicate('value', '=', $fields['value'])->
            insert('order_settings', $fields);
    }

    /**
     * Unsets a setting from the order settings.
     *
     * @param int $company_id The company ID
     * @param string $key The setting to unset
     */
    public function unsetSetting($company_id, $key)
    {
        $this->Record->from('order_settings')->where('company_id', '=', $company_id)->
            where('key', '=', $key)->delete();
    }

    /**
     * Fetches all antifraud components available
     *
     * @return array An array of key/value pairs where each key is the
     *  antifraud component class and each value is its name
     */
    public function getAntifraud()
    {
        $antifrauds = [];

        $antifraud_dir = realpath(dirname(__FILE__) . DS . '..' . DS) . DS . 'components' . DS . 'antifraud';
        $dir = opendir($antifraud_dir);
        while (false !== ($antifraud = readdir($dir))) {
            // If the file is not a hidden file, and is a directory, accept it
            if ($antifraud != 'lib' && substr($antifraud, 0, 1) != '.' && is_dir($antifraud_dir . DS . $antifraud)) {
                $name = $this->_('OrderSettings.getantifraud.' . $antifraud);
                $antifrauds[$antifraud] = empty($name) ? Loader::toCamelCase($antifraud) : $name;
            }
        }
        return $antifrauds;
    }

    /**
     * Overrides any input errors with new messages
     * @see Input::errors
     */
    private function overrideInputErrors()
    {
        // Swap the error with the error set in $this->errors
        $errors = $this->Input->errors();

        foreach ($this->errors as $error => $err) {
            foreach ($err as $type => $message) {
                if (isset($errors[$error][$type])) {
                    $errors[$error][$type] = $message;
                }
            }

            unset($this->errors[$error]);
        }

        $this->Input->setErrors($errors);
    }

    /**
     * Retrieves rules for adding a setting
     *
     * @return array The input validation rules
     */
    private function getSettingRules()
    {
        return [
            'embed_code' => [
                'format' => [
                    'if_set' => true,
                    'rule' => function($embed_code) {
                        // Verify that the embed code is parseable via H2o
                        try {
                            // Assume the default parsing options (e.g. VARIABLE_START with two braces "{{")
                            H2o::parseString($embed_code)->render();
                        } catch (H2o_Error $e) {
                            // Set parsing error as an input error
                            $this->errors['embed_code'] = [
                                'format' => $this->_('OrderSettings.!error.embed_code.parse', $e->getMessage())
                            ];

                            return false;
                        } catch (Exception $e) {
                            // Don't care about any other exception
                        }

                        return true;
                    },
                    'message' => $this->_('OrderSettings.!error.embed_code.parse', '')
                ]
            ]
        ];
    }
}
