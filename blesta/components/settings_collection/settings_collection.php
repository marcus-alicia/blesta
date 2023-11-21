<?php
/**
 * A collection manager of company settings
 *
 * @package blesta
 * @subpackage blesta.components.settingscollection
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SettingsCollection
{
    /**
     * Initialize the SettingsCollection
     */
    public function __construct()
    {
        Loader::loadHelpers($this, ['DataStructure']);
        $this->ArrayHelper = $this->DataStructure->create('Array');
    }

    /**
     * Fetches all settings that apply to this company. Settings are inherited
     * in the order of company_settings -> settings where "->" represents the
     * left item inheriting (and overwriting in the case of duplicates) values
     * found in the right item.
     *
     * @param Companies $companies A reference to the Companies model object
     * @param int $company_id The company ID to retrieve settings for
     * @param bool $ignore_inheritence True to only retrieve company settings, false to get all inherited
     *  settings (default false)
     * @return array A key=>value array of company settings
     */
    public function fetchSettings(Companies $companies = null, $company_id = null, $ignore_inheritence = false)
    {
        $companies = $this->loadIfNotSet($companies, 'Companies');
        return $this->ArrayHelper->numericToKey(
            $companies->getSettings($company_id, $ignore_inheritence),
            'key',
            'value'
        );
    }

    /**
     * Fetches a specific setting that apply to this company. Settings are inherited
     * in the order of company_settings -> settings where "->" represents the
     * left item inheriting (and overwriting in the case of duplicates) values
     * found in the right item.
     *
     * @param \Companies $companies The Companies object model (optional)
     * @param int $company_id The company ID to retrieve a setting for (optional)
     * @param string $key The key name of the setting to fetch (optional)
     * @return array containing the key and value for this setting
     */
    public function fetchSetting(Companies $companies = null, $company_id = null, $key = null)
    {
        $companies = $this->loadIfNotSet($companies, 'Companies');
        return (array)$companies->getSetting($company_id, $key);
    }

    /**
     * Fetches all system settings.
     *
     * @param Settings $settings A reference to the Settings model object
     * @return array A key=>value array of system settings
     */
    public function fetchSystemSettings(Settings $settings = null)
    {
        $settings = $this->loadIfNotSet($settings, 'Settings');
        return $this->ArrayHelper->numericToKey($settings->getSettings(), 'key', 'value');
    }

    /**
     * Fetches a specific system setting.
     *
     * @param Settings $settings A reference to the Settings model object
     * @param string $key The key name of the setting to fetch
     * @return array containing the key and value for this setting
     */
    public function fetchSystemSetting(Settings $settings = null, $key = null)
    {
        $settings = $this->loadIfNotSet($settings, 'Settings');
        return (array)$settings->getSetting($key);
    }

    /**
     * Fetches all client group settings for a particular group
     *
     * @param int $client_group_id The client group ID to fetch settings for
     * @param ClientGroups $settings A reference to the ClientGroups model object
     * @param bool $ignore_inheritence True to fetch only client group settings without inheriting from
     *  company or system settings (default false)
     * @return array A key=>value array of client group settings
     */
    public function fetchClientGroupSettings(
        $client_group_id,
        ClientGroups $settings = null,
        $ignore_inheritence = false
    ) {
        $settings = $this->loadIfNotSet($settings, 'ClientGroups');
        return $this->ArrayHelper->numericToKey(
            $settings->getSettings($client_group_id, $ignore_inheritence),
            'key',
            'value'
        );
    }

    /**
     * Fetches a specific ClientGroup setting.
     *
     * @param int $client_group_id The client group ID to fetch a setting for
     * @param ClientGroups $settings A reference to the ClientGroups model object
     * @param string $key The key name of the setting to fetch
     * @return array containing the key and value for this setting
     */
    public function fetchClientGroupSetting($client_group_id, ClientGroups $settings = null, $key = null)
    {
        $settings = $this->loadIfNotSet($settings, 'ClientGroups');
        return (array)$settings->getSetting($client_group_id, $key);
    }

    /**
     * Fetches all client settings for a particular client
     *
     * @param int $client_id The client ID to fetch settings for
     * @param Clients $settings A reference to the Clients model object
     * @return array A key=>value array of client group settings
     */
    public function fetchClientSettings($client_id, Clients $settings = null)
    {
        $settings = $this->loadIfNotSet($settings, 'Clients');
        return $this->ArrayHelper->numericToKey($settings->getSettings($client_id), 'key', 'value');
    }

    /**
     * Fetches a specific Client setting.
     *
     * @param int $client_id The client group ID to fetch a setting for
     * @param Clients $settings A reference to the Clients model object
     * @param string $key The key name of the setting to fetch
     * @return array containing the key and value for this setting
     */
    public function fetchClientSetting($client_id, Clients $settings = null, $key = null)
    {
        $settings = $this->loadIfNotSet($settings, 'Clients');
        return (array)$settings->getSetting($client_id, $key);
    }

    /**
     * Loads the given model if nothing passed into $obj
     *
     * @param mixed $obj The model object to be used, null to initialize the model using $model instead.
     * @param string $model The name of the model to initialize if $obj is not given
     * @return object The model object specified by $obj or created using $model if not specified.
     */
    private function loadIfNotSet($obj, $model)
    {
        if (!$obj) {
            if (!isset($this->$model)) {
                Loader::loadModels($this, [$model]);
            }
            return $this->$model;
        }
        return $obj;
    }
}
