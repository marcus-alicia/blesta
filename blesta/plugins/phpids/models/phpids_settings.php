<?php
/**
 * PHPIDS Settings
 *
 * @package blesta
 * @subpackage blesta.plugins.phpids
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PhpidsSettings extends PhpidsModel
{
    /**
     * Returns all settings stored for the current company
     *
     * @return stdClass A stdClass object with member variables that as setting names and values as setting values
     */
    public function getAll()
    {
        $settings = new stdClass();

        $fields = ['phpids_settings.key', 'phpids_settings.value'];
        $results = $this->Record->select($fields)->from('phpids_settings')->
            where('phpids_settings.company_id', '=', Configure::get('Blesta.company_id'))->
            fetchAll();

        foreach ($results as $result) {
            $settings->{$result->key} = $result->value;
        }
        return $settings;
    }

    /**
     * Updates a set of settings
     *
     * @param array $vars A key/value paired array of settings to update
     */
    public function update(array $vars)
    {

        // Redirect must be at least 15 if enabled
        if (isset($vars['redirect_min_score']) && $vars['redirect_min_score'] > 0) {
            $vars['redirect_min_score'] = max(15, $vars['redirect_min_score']);
        }

        $rules = [

        ];
        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            foreach ($vars as $key => $value) {
                $fields = [
                    'key' => $key,
                    'value' => $value,
                    'company_id' => Configure::get('Blesta.company_id')
                ];
                $this->Record->duplicate('value', '=', $fields['value'])->
                    insert('phpids_settings', $fields);
            }
        }
    }
}
