<?php

/**
 * States adhere to ISO 3166-2, and contain the native state/province/territory
 * name. The format of ISO 3166-2 is [ISO 3166-1 alpha2 country code]-[subdivision code].
 * This model requires that the ISO 3166-2 code be split on the hyphen into its
 * two parts.
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class States extends AppModel
{
    /**
     * Initialize States
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['states']);
    }

    /**
     * List all states for a given country (ISO 3166-1 alpha2)
     *
     * @param string $country The ISO 3166-1 alpha2 country code to fetch on
     * @param array $order_by A set of key/value fields to order by where each
     *  key is the field and each value is the direction (optional, default name ascending)
     * @return array An array of stdClass state objects
     */
    public function getList($country = null, array $order_by = ['name' => 'asc'])
    {
        $this->Record->select(['country_alpha2', 'code', 'name'])->from('states');

        if ($country !== null) {
            $this->Record->where('country_alpha2', '=', $country);
        }

        $order_by = array_merge(['country_alpha2' => 'asc'], $order_by);
        return $this->Record->order($order_by)->fetchAll();
    }

    /**
     * Get a specific state/province/territory based ISO 3166-1 alpha2 country code and ISO 3166-2 subdivision code
     *
     * @param string $country The ISO 3166-1 alpha2 country code to fetch on
     * @param string $code The ISO 3166-2 alpha-numeric subdivision code
     * @return mixed An stdClass object representing the state, or false if none exist
     */
    public function get($country, $code)
    {
        return $this->Record->select(['country_alpha2', 'code', 'name'])->
            from('states')->where('country_alpha2', '=', $country)->
            where('code', '=', $code)->fetch();
    }

    /**
     * Add a state
     *
     * @param array $vars An array of variable info including:
     *
     *  - code The ISO 3166-2 alpha-numeric subdivision code
     *  - country_alpha2 The ISO 3166-1 alpha2 country code
     *  - name The native language subdivision name
     */
    public function add(array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            // Add a state
            $fields = ['country_alpha2', 'code', 'name'];
            $this->Record->insert('states', $vars, $fields);
        }
    }

    /**
     * Edit a state by the ISO 3166-1 alpha2 country code and 3166-2 subdivision code
     *
     * @param string $country The ISO 3166-1 alpha2 country code
     * @param string $code The ISO 3166-2 subdivision code
     * @param array $vars An array of variable info including:
     *
     *  - name The native language subdivision name
     */
    public function edit($country, $code, array $vars)
    {
        $rules = $this->getRules($vars);

        // Only validate the name
        $rules = ['name' => $rules['name']];

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            // Update a state
            $fields = ['name'];
            $this->Record->where('country_alpha2', '=', $country)->where('code', '=', $code)->
                update('states', $vars, $fields);
        }
    }

    /**
     * Delete a state by the ISO 3166-1 alpha2 country code and 3166-2 subdivision code
     *
     * @param string $country The ISO 3166-1 alpha2 country code
     * @param string $code The ISO 3166-2 subdivision code
     */
    public function delete($country, $code)
    {
        $this->Record->from('states')->where('country_alpha2', '=', $country)->where('code', '=', $code)->delete();
    }

    /**
     * Returns the rule set for adding/editing states
     *
     * @param array $vars An array of state info including:
     *
     *  - code The state code
     *  - country_alpha2 The 2-character country code ISO 3166-2
     *  - name The name of the state in its native language
     * @return array State rules
     */
    private function getRules(array $vars)
    {
        $rules = [
            'code' => [
                'format' => [
                    'rule' => ['matches', '/^[0-9a-z]{1,3}$/i'],
                    'message' => $this->_('States.!error.code.format')
                ]
            ],
            'country_alpha2' => [
                'format' => [
                    'rule' => ['matches', '/^[a-z]{2}$/i'],
                    'message' => $this->_('States.!error.country_alpha2.format')
                ],
                'in_use' => [
                    'rule' => [[$this, 'get'], (isset($vars['code']) ? $vars['code'] : null)],
                    'negate' => true,
                    'message' => $this->_(
                        'States.!error.country_alpha2.in_use',
                        (isset($vars['country_alpha2']) ? $vars['country_alpha2'] : null),
                        (isset($vars['code']) ? $vars['code'] : null)
                    )
                ]
            ],
            'name' => [
                'format' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('States.!error.name.format')
                ]
            ]
        ];
        return $rules;
    }
}
