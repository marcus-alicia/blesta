<?php

/**
 * System setting management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Settings extends AppModel
{
    /**
     * Fetches all system settings
     *
     * @return mixed An array of objects with key/value pairs of settings, false if no results found
     */
    public function getSettings()
    {
        $settings = $this->Record->select(['key', 'value', 'encrypted', 'inherit'])->
            select(['?' => 'level'], false)->appendValues(['system'])->from('settings')->fetchAll();

        // Decrypt values where necessary
        for ($i = 0, $total = count($settings); $i < $total; $i++) {
            if ($settings[$i]->encrypted) {
                $settings[$i]->value = $this->systemDecrypt($settings[$i]->value);
            }
        }
        return $settings;
    }

    /**
     * Fetch a single setting by key name
     *
     * @param string $key The key name of the setting to fetch
     * @return mixed An stdObject containg the key and value, false if no such key exists
     */
    public function getSetting($key)
    {
        $setting = $this->Record->select(['key', 'value', 'encrypted', 'inherit'])
            ->select(['?' => 'level'], false)
            ->appendValues(['system'])
            ->from('settings')
            ->where('key', '=', $key)
            ->fetch();

        if ($setting && $setting->encrypted) {
            $setting->value = $this->systemDecrypt($setting->value);
        }
        return $setting;
    }

    /**
     * Sets a group of settings with key/value pairs
     *
     * @param array $settings Settings to set as key/value pairs
     * @param array $value_keys An array of key values to accept as valid fields
     * @see Settings::setSetting()
     */
    public function setSettings(array $settings, array $value_keys = null)
    {
        if (!empty($value_keys)) {
            $settings = array_intersect_key($settings, array_flip($value_keys));
        }
        foreach ($settings as $key => $value) {
            $this->setSetting($key, $value);
        }
    }

    /**
     * Sets the setting with the given key, overwriting any existing value with that key
     *
     * @param string $key The setting identifier
     * @param string $value The value to set for this setting
     * @param mixed $encrypted True to encrypt $value, false to store
     *  unencrypted, null to encrypt if currently set to encrypt
     * @param int $inherit Whether or not the setting should be inheritable
     */
    public function setSetting($key, $value, $encrypted = null, $inherit = null)
    {
        $fields = ['key' => $key, 'value' => $value];

        if ($inherit !== null) {
            $fields['inherit'] = (int) $inherit;
        }

        // If encryption is mentioned set the appropriate value and encrypt if necessary
        if ($encrypted !== null) {
            $fields['encrypted'] = (int) $encrypted;
            if ($encrypted) {
                $fields['value'] = $this->systemEncrypt($fields['value']);
            }
        } else {
            // Check if the value is currently encrypted and encrypt if necessary
            $setting = $this->getSetting($key);
            if ($setting && $setting->encrypted) {
                $fields['encrypted'] = 1;
                $fields['value'] = $this->systemEncrypt($fields['value']);
            }
        }

        $this->Record->duplicate('value', '=', $fields['value'])->
            insert('settings', $fields);
    }

    /**
     * Unsets a setting from the system settings. CAUTION: This method will
     * physically remove the setting from the system, and could have dire consequences.
     * You should never use this method, except when attempting to remove a setting
     * created by Settings::setSettings() or Settings::setSetting() that did not
     * previously exist for this installation.
     *
     * @param string $key The setting to unset
     */
    public function unsetSetting($key)
    {
        $this->Record->from('settings')->where('key', '=', $key)->delete();
    }

    /**
     * Returns the source version of Blesta
     *
     * @return string The source version of Blesta
     * @see Settings::getDbVersion()
     */
    public function getVersion()
    {
        return defined('BLESTA_VERSION') ? BLESTA_VERSION : null;
    }

    /**
     * Returns the database version of Blesta. A shortcut for Settings::getSetting("database_version").
     * Often this will not be exactly the same as the source version because
     * database changes are not made in every sourve version update.
     *
     * @return string The source version of Blesta
     * @see Settings::getVersion()
     */
    public function getDbVersion()
    {
        $setting = $this->getSetting('database_version');
        if ($setting) {
            return $setting->value;
        }
        return null;
    }

    /**
     * Returns whether or not there are upgrade tasks to run
     *
     * @return bool True if there are upgrade tasks to run, false otherwise
     */
    public function upgradable()
    {
        Loader::loadComponents($this, ['Upgrades']);
        $upgrades = $this->Upgrades->getUpgrades($this->getDbVersion(), $this->getVersion());
        return !empty($upgrades);
    }
}
