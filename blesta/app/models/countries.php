<?php

/**
 * Countries adhere to ISO 3166-1 and contain English and native country name
 * (when differing from English)
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Countries extends AppModel
{
    /**
     * Initialize Countries
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['countries']);
    }

    /**
     * List all countries
     *
     * @param string $sort_by The field to sort the list by
     * @param string $order The order to sort (acs, desc)
     * @return mixed An array of stdClass country objects, false if no records found
     */
    public function getList($sort_by = 'name', $order = 'asc')
    {
        return $this->Record->select(['alpha2', 'alpha3', 'name', 'alt_name'])->
            from('countries')->order([$sort_by => $order])->fetchAll();
    }

    /**
     * Get a specific country based ISO 3166-1 alpha2 or alpha3
     *
     * @param string $code The ISO 3166-1 alpha2 or alpha3 country code to search on
     * @return mixed A stdClass country object, false if no record found
     */
    public function get($code)
    {
        $field = 'alpha2';
        if (strlen($code) == 3) {
            $field = 'alpha3';
        }

        return $this->Record->select(['alpha2', 'alpha3', 'name', 'alt_name'])->
            from('countries')->where($field, '=', $code)->fetch();
    }

    /**
     * Add a country
     *
     * @param array $vars An array of variable info, including:
     *
     *  - alpha2 The ISO 3166-1 alpha2 country code
     *  - alpha3 The ISO 3166-1 alpha3 country code
     *  - name The english country name
     *  - alt_name The native language country name (optional)
     */
    public function add(array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            // Add a country
            $fields = ['alpha2', 'alpha3', 'name', 'alt_name'];
            $this->Record->insert('countries', $vars, $fields);
        }
    }

    /**
     * Edit a country by the ISO 3166-1 alpha2 code
     *
     * @param string $alpha2 The ISO 3166-1 alpha2 country code
     * @param array $vars An array of variable info, including:
     *
     *  - alpha3 The ISO 3166-1 alpha3 country code
     *  - name The english country name
     *  - alt_name The native language country name (optional)
     */
    public function edit($alpha2, array $vars)
    {
        $vars['alpha2'] = $alpha2;
        $rules = $this->getRules($vars);
        $rules['alpha2']['exists'] = [
            'rule' => [[$this, 'validateExists'], 'alpha2', 'countries'],
            'message' => $this->_('Countries.!error.alpha2.exists')
        ];

        // Remove in_use constraint
        unset($rules['alpha2']['in_use']);

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            // Update a country
            $fields = ['alpha3', 'name', 'alt_name'];
            $this->Record->where('alpha2', '=', $alpha2)->update('countries', $vars, $fields);
        }
    }

    /**
     * Delete a country by the ISO 3166-1 alpha2 or alpha3 code
     *
     * @param string $code The ISO 3166-1 alpha2 or alpha3 country code to delete
     */
    public function delete($code)
    {
        $field = strlen($code) == 2 ? 'alpha2' : 'alpha3';
        $this->Record->from('countries')->where($field, '=', $code)->delete();
    }

    /**
     * Returns the rules for adding/editing countries
     *
     * @param array $vars The key/value pairs used for language replacement
     * @return array The rules
     */
    private function getRules(array $vars)
    {
        $rules = [
            'alpha2' => [
                'format' => [
                    'rule' => ['matches', '/^[a-z]{2}$/i'],
                    'message' => $this->_('Countries.!error.alpha2.format')
                ],
                'in_use' => [
                    'rule' => [[$this, 'get']],
                    'negate' => true,
                    'message' => $this->_('Countries.!error.alpha2.in_use', (isset($vars['alpha2']) ? $vars['alpha2'] : null))
                ]
            ],
            'alpha3' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[a-z]{3}$/i'],
                    'message' => $this->_('Countries.!error.alpha3.format')
                ],
                'in_use' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateAlpha3InUse'], (isset($vars['alpha2']) ? $vars['alpha2'] : null)],
                    'negate' => true,
                    'message' => $this->_('Countries.!error.alpha3.in_use', (isset($vars['alpha3']) ? $vars['alpha3'] : null))
                ]
            ],
            'name' => [
                'format' => [
                    'rule' => ['isEmpty'],
                    'negate' => true,
                    'message' => $this->_('Countries.!error.name.format')
                ]
            ]
        ];
        return $rules;
    }

    /**
     * Validates that the given alpha3 character code for a country is already in use
     *
     * @param string $alpha3 The ISO 3166-1 alpha3 country code to check for
     * @param string $alpha2 The ISO 3166-1 alpha2 country code to ignore if it happens to match $alpha3
     * @return bool True if the alpha3 country code is already taken
     */
    public function validateAlpha3InUse($alpha3, $alpha2)
    {
        $country1 = $this->get($alpha2);
        $country2 = $this->get($alpha3);

        if ($country1 && $country2 && $country1->alpha2 == $country2->alpha2) {
            return false;
        }
        return ($country2 ? true : false);
    }
}
