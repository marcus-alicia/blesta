<?php
/**
 * Order Affiliate Company Setting Management
 *
 * @package blesta
 * @subpackage blesta.plugins.order.models
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderAffiliateCompanySettings extends OrderModel
{
    /**
     * Fetches all affiliate company settings
     *
     * @param int $company_id The company ID
     * @return mixed An array of objects with key/value pairs of settings, false if no results found
     */
    public function getSettings($company_id)
    {
        return $this->Record->select(['key', 'value'])
            ->from('order_affiliate_company_settings')
            ->where('company_id', '=', $company_id)
            ->fetchAll();
    }

    /**
     * Fetch a single setting by key name
     *
     * @param int $company_id The company ID
     * @param string $key The key name of the setting to fetch
     * @return mixed An stdObject containg the key and value, false if no such key exists
     */
    public function getSetting($company_id, $key)
    {
        return $this->Record->select(['key', 'value'])
            ->from('order_affiliate_company_settings')
            ->where('company_id', '=', $company_id)
            ->where('key', '=', $key)->fetch();
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
     */
    public function setSetting($company_id, $key, $value)
    {
        $fields = ['company_id' => $company_id, 'key' => $key, 'value' => $value];
        // Perform input validation on the settings
        $old_errors = $this->Input->errors();
        $this->Input->setRules($this->getRules());

        if (!$this->Input->validates($fields)) {
            return;
        } elseif ($old_errors) {
            // Reset the old errors since each new input validation resets errors
            $this->Input->setErrors($old_errors);
        }

        $this->Record->duplicate('value', '=', $fields['value'])
            ->insert('order_affiliate_company_settings', $fields);
    }

    /**
     * Unsets a setting from the order affiliate company settings.
     *
     * @param int $company_id The company ID
     * @param string $key The setting to unset
     */
    public function unsetSetting($company_id, $key)
    {
        $this->Record->from('order_affiliate_company_settings')
            ->where('company_id', '=', $company_id)
            ->where('key', '=', $key)
            ->delete();
    }

    /**
     * Returns all supported affiliate commission types in key/value pairs
     *
     * @return array A list of affiliate commission types
     */
    public function getCommissionTypes()
    {
        return [
            'fixed' => Language::_('OrderAffiliateCompanySettings.getcommissiontypes.fixed', true),
            'percentage' => Language::_('OrderAffiliateCompanySettings.getcommissiontypes.percentage', true),
        ];
    }

    /**
     * Returns all supported affiliate order frequencies in key/value pairs
     *
     * @return array A list of affiliate order frequencies
     */
    public function getOrderFrequencies()
    {
        return [
            'first' => Language::_('OrderAffiliateCompanySettings.getorderfrequencies.first', true),
            'any' => Language::_('OrderAffiliateCompanySettings.getorderfrequencies.any', true),
        ];
    }

    /**
     * Retrieves rules for adding a setting
     *
     * @return array The input validation rules
     */
    private function getRules()
    {
        return [
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('OrderAffiliateCompanySettings.!error.company_id.exists')
                ],
            ]
        ];
    }
}
