<?php
/**
 * Order Affiliate Setting Management
 *
 * @package blesta
 * @subpackage blesta.plugins.order.models
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderAffiliateSettings extends OrderModel
{
    /**
     * Fetches all affiliate settings
     *
     * @param int $affiliate_id The affiliate ID
     * @return mixed An array of objects with key/value pairs of settings, false if no results found
     */
    public function getSettings($affiliate_id)
    {
        return $this->Record->select(['key', 'value'])
            ->from('order_affiliate_settings')
            ->where('affiliate_id', '=', $affiliate_id)
            ->fetchAll();
    }

    /**
     * Fetch a single setting by key name
     *
     * @param int $affiliate_id The affiliate ID
     * @param string $key The key name of the setting to fetch
     * @return mixed An stdObject containing the key and value, false if no such key exists
     */
    public function getSetting($affiliate_id, $key)
    {
        return $this->Record->select(['key', 'value'])
            ->from('order_affiliate_settings')
            ->where('affiliate_id', '=', $affiliate_id)
            ->where('key', '=', $key)->fetch();
    }

    /**
     * Sets a group of settings with key/value pairs
     *
     * @param int $affiliate_id The affiliate ID
     * @param array $settings Settings to set as key/value pairs
     * @see Settings::setSetting()
     */
    public function setSettings($affiliate_id, array $settings)
    {
        foreach ($settings as $key => $value) {
            $this->setSetting($affiliate_id, $key, $value);
        }
    }

    /**
     * Sets the setting with the given key, overwriting any existing value with that key
     *
     * @param int $affiliate_id The affiliate ID
     * @param string $key The setting identifier
     * @param string $value The value to set for this setting
     */
    public function setSetting($affiliate_id, $key, $value)
    {
        $fields = ['affiliate_id' => $affiliate_id, 'key' => $key, 'value' => $value];
        // Perform input validation on the settings
        $old_errors = $this->Input->errors();
        $this->Input->setRules($this->getRules());

        if (!$this->Input->validates($fields)) {
            return;
        } elseif ($old_errors) {
            // Reset the old errors since each new input validation resets errors
            $this->Input->setErrors($old_errors);
        }

        // Update totals if the currency is being updated
        if ($key == 'withdrawal_currency') {
            Loader::loadModels($this, ['Currencies']);

            $new_currency = $value;

            $old_currency = $this->getSetting($affiliate_id, 'withdrawal_currency');
            $total_available = $this->getSetting($affiliate_id, 'total_available');
            $total_withdrawn = $this->getSetting($affiliate_id, 'total_withdrawn');

            $old_currency = isset($old_currency->value) ? $old_currency->value : 'USD';
            $total_available = isset($total_available->value) ? $total_available->value : 0;
            $total_withdrawn = isset($total_withdrawn->value) ? $total_withdrawn->value : 0;

            $params = [
                'total_available' => $this->Currencies->convert(
                    $total_available,
                    $old_currency,
                    $new_currency,
                    Configure::get('Blesta.company_id')
                ),
                'total_withdrawn' => $this->Currencies->convert(
                    $total_withdrawn,
                    $old_currency,
                    $new_currency,
                    Configure::get('Blesta.company_id')
                )
            ];
            $this->setSettings($affiliate_id, $params);
        }

        $this->Record->duplicate('value', '=', $fields['value'])
            ->insert('order_affiliate_settings', $fields);
    }

    /**
     * Unsets a setting from the order affiliate settings.
     *
     * @param int $affiliate_id The affiliate ID
     * @param string $key The setting to unset
     */
    public function unsetSetting($affiliate_id, $key)
    {
        $this->Record->from('order_affiliate_settings')
            ->where('affiliate_id', '=', $affiliate_id)
            ->where('key', '=', $key)
            ->delete();
    }

    /**
     * Retrieves rules for adding a setting
     *
     * @return array The input validation rules
     */
    private function getRules()
    {
        return [
            'affiliate_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'order_affiliates'],
                    'message' => $this->_('OrderAffiliateSettings.!error.affiliate_id.exists')
                ],
            ]
        ];
    }
}
