<?php
/**
 * Order Staff Settings
 *
 * @package blesta
 * @subpackage blesta.plugins.order.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderStaffSettings extends OrderModel
{
    /**
     * Sets a group of settings with key/value pairs
     *
     * @param int $staff_id The ID of the staff member to set settings for
     * @param $company_id The ID of the company under which the staff member settings should be set
     * @param array $settings Settings to set as key/value pairs
     * @see OrderStaffSettings::setSetting()
     */
    public function setSettings($staff_id, $company_id, array $settings)
    {
        foreach ($settings as $key => $value) {
            $this->setSetting($staff_id, $company_id, $key, $value);
        }
    }

    /**
     * Sets the setting with the given key, overwriting any existing value with that key
     *
     * @param int $staff_id The ID of the staff member to set settings for
     * @param $company_id The ID of the company under which the staff member settings should be set
     * @param string $key The setting identifier
     * @param string $value The value to set for this setting
     */
    public function setSetting($staff_id, $company_id, $key, $value)
    {
        $fields = ['staff_id' => $staff_id, 'company_id' => $company_id,
            'key'=>$key, 'value'=>$value];

        $this->Record->duplicate('value', '=', $fields['value'])->
            insert('order_staff_settings', $fields);
    }
    /**
     * Fetches all system settings
     *
     * @param int $staff_id The ID of the staff member to fetch settings for
     * @param $company_id The ID of the company under which the staff member settings should be fetched
     * @return mixed An array of objects with key/value pairs of settings, false if no results found
     */
    public function getSettings($staff_id, $company_id)
    {
        return $this->Record->select(['key', 'value'])->
            from('order_staff_settings')->where('staff_id', '=', $staff_id)->
            where('company_id', '=', $company_id)->fetchAll();
    }

    /**
     * Fetch a single setting by key name
     *
     * @param int $staff_id The ID of the staff member to fetch settings for
     * @param $company_id The ID of the company under which the staff member settings should be fetched
     * @param string $key The key name of the setting to fetch
     * @return mixed An stdObject containg the key and value, false if no such key exists
     */
    public function getSetting($staff_id, $company_id, $key)
    {
        return $this->Record->select(['key', 'value', 'encrypted'])->
            from('order_staff_settings')->where('key', '=', $key)->
            where('staff_id', '=', $staff_id)->
            where('company_id', '=', $company_id)->fetch();
    }

    /**
     * Fetches all active staff in the given company with the given order form setting
     *
     * @param $company_id The ID of the company under which the staff member settings should be fetched
     * @param string $key The key name of the setting to fetch
     * @param string $value The value to filter by for this setting
     * @return array An array of stdClass objects each representing a staff
     */
    public function getStaffWithSetting($company_id, $key, $value)
    {
        return $this->Record->select(['staff.*'])
            ->from('order_staff_settings')
            ->innerJoin('staff', 'staff.id', '=', 'order_staff_settings.staff_id', false)
            ->where('order_staff_settings.company_id', '=', $company_id)
            ->where('order_staff_settings.key', '=', $key)
            ->where('order_staff_settings.value', '=', $value)
            ->where('staff.status', '=', 'active')
            ->fetchAll();
    }
}
