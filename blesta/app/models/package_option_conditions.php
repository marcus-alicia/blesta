<?php

/**
 * Package Option Condition management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PackageOptionConditions extends AppModel
{
    /**
     * Initialize PackageOptionConditions
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['package_option_conditions']);
        Loader::loadModels($this, ['PackageOptions']);
    }

    /**
     * Formats the given package option condition
     *
     * @param stdClass|false $condition An object representing a package option condition
     * @return stdClass|false The formatted object
     */
    private function formatCondition($condition)
    {
        if ($condition) {
            $condition->value_id = json_decode($condition->value_id, true);
            $condition->triggering_option = $this->PackageOptions->get($condition->trigger_option_id);
        }

        return $condition;
    }

    /**
     * Fetches the package option condition
     *
     * @param int $condition_id The ID of the package option condition to fetch
     * @return mixed A stdClass object representing the package option, false if no such option exists
     */
    public function get($condition_id)
    {
        return $this->formatCondition($this->getConditions(['id' => $condition_id])->fetch());
    }

    /**
     * Fetches all package option conditions
     *
     * @param array $filters A list of filters:
     *
     *  - id The ID of the condition
     *  - condition_set_id The ID of the set to which this condition belongs
     *  - trigger_option_id The ID of the option that triggers evaluation of this condition
     *  - operator The comparison operator used to evaluate this condition (>, <, =, !=, in)
     *  - value The value to compare this option against
     *  - value_id The ID of the value to compare this option against
     * @return array An array of stdClass objects representing each package option condition
     */
    public function getAll(array $filters = [])
    {
        $conditions = $this->getConditions($filters)->fetchAll();
        foreach (($conditions ?? []) as &$condition) {
            $condition = $this->formatCondition($condition);
        }

        return $conditions;
    }

    /**
     * Partially-constructs the Record object for fetching package option conditions
     *
     * @param array $filters A list of filters:
     *
     *  - id The ID of the condition
     *  - condition_set_id The ID of the set to which this condition belongs
     *  - trigger_option_id The ID of the option that triggers evaluation of this condition
     *  - operator The comparison operator used to evaluate this condition (>, <, =, !=, in)
     *  - value The value to compare this option against
     *  - value_id The ID of the value to compare this option against
     * @param bool $select Whether to select field in the query
     * @return Record A partially-constructed Record object
     */
    private function getConditions(array $filters = [], $select = true)
    {
        if ($select) {
            $this->Record->select(['package_option_conditions.*']);
        }

        $this->Record->from('package_option_conditions');

        if (isset($filters['id'])) {
            $this->Record->where('package_option_conditions.id', '=', $filters['id']);
        }
        if (isset($filters['condition_set_id'])) {
            $this->Record->where('package_option_conditions.condition_set_id', '=', $filters['condition_set_id']);
        }
        if (isset($filters['trigger_option_id'])) {
            $this->Record->where('package_option_conditions.trigger_option_id', '=', $filters['trigger_option_id']);
        }
        if (isset($filters['operator'])) {
            $this->Record->where('package_option_conditions.operator', '=', $filters['operator']);
        }
        if (isset($filters['value'])) {
            $this->Record->where('package_option_conditions.value', '=', $filters['value']);
        }
        if (isset($filters['value_id'])) {
            $this->Record->where('package_option_conditions.value_id', '=', $filters['value_id']);
        }

        return $this->Record;
    }

    /**
     * Adds a package option condition
     *
     * @param array $vars An array of package option condition info including:
     *
     *  - condition_set_id The ID of the set to which this condition belongs
     *  - trigger_option_id The ID of the option that triggers evaluation of this condition
     *  - operator The comparison operator used to evaluate this condition (>, <, =, !=, in)
     *  - value The value to compare this option against
     *  - value_id The ID of the value to compare this option against
     * @return int The package option condition ID, void on error
     */
    public function add(array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            if (isset($vars['value_id'])) {
                $vars['value_id'] = json_encode($vars['value_id']);
            }

            // Add the package option
            $fields = ['condition_set_id', 'trigger_option_id', 'operator', 'value', 'value_id'];
            $this->Record->insert('package_option_conditions', $vars, $fields);
            $condition_id = $this->Record->lastInsertId();

            return $condition_id;
        }
    }

    /**
     * Updates a package option condition
     *
     * @param int $condition_id The ID of the package option condition to update
     * @param array $vars An array of package option condition info including:
     *
     *  - id The ID of the condition
     *  - condition_set_id The ID of the set to which this condition belongs
     *  - trigger_option_id The ID of the option that triggers evaluation of this condition
     *  - operator The comparison operator used to evaluate this condition (>, <, =, !=, in)
     *  - value The value to compare this option against
     *  - value_id The ID of the value to compare this option against
     * @return int The package option condition ID, void on error
     */
    public function edit($condition_id, array $vars)
    {
        // Set  the condition ID field
        $vars['id'] = $condition_id;

        $this->Input->setRules($this->getRules($vars, true));
        if ($this->Input->validates($vars)) {
            if (isset($vars['value_id'])) {
                $vars['value_id'] = json_encode($vars['value_id']);
            }

            // Edit the package option condition
            $fields = ['condition_set_id', 'trigger_option_id', 'operator', 'value', 'value_id'];
            $this->Record->where('id', '=', $condition_id)->update('package_option_conditions', $vars, $fields);
            return $condition_id;
        }
    }

    /**
     * Permanently removes a package option condition from the system
     *
     * @param int $condition_id The package option condition ID to delete
     */
    public function delete($condition_id)
    {
        // Delete the package option condition
        $this->getConditions(['id' => $condition_id], false)->delete();
    }

    /**
     * Retrieves a list of package option condition operators and their language definitions
     *
     * @return array A key/value list of operators and their language
     */
    public function getOperators()
    {
        return [
            '>' => '>',
            '<' => '<',
            '=' => '=',
            '!=' => '!=',
            'in' => Language::_('PackageOptionConditions.getoperators.in', true),
            'notin' => Language::_('PackageOptionConditions.getoperators.notin', true),
        ];
    }

    /**
     * Retrieves a list of rules for adding/editing package option conditions
     *
     * @param array $vars A list of input vars used in validation
     *
     *  - id The ID of the condition
     *  - condition_set_id The ID of the set to which this condition belongs
     *  - trigger_option_id The ID of the option that triggers evaluation of this condition
     *  - operator The comparison operator used to evaluate this condition (>, <, =, !=, in)
     *  - value The value to compare this option against
     *  - value_id The ID of the value to compare this option against
     * @param bool $edit True to fetch the edit rules, false for the add rules (optional, default false)
     * @return array A list of rules
     */
    private function getRules(array $vars, $edit = false)
    {
        $that = $this;
        $rules = [
            'condition_set_id' => [
                'exists' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateExists'], 'id', 'package_option_condition_sets'],
                    'message' => Language::_('PackageOptionConditions.!error.condition_set_id.exists', true)
                ]
            ],
            'trigger_option_id' => [
                'exists' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateExists'], 'id', 'package_options'],
                    'message' => Language::_('PackageOptionConditions.!error.trigger_option_id.exists', true)
                ]
            ],
            'operator' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['array_key_exists', $this->getOperators()],
                    'message' => Language::_('PackageOptionConditions.!error.operator.valid', true)
                ]
            ],
            'value' => [
                'valid' => [
                    'if_set' => $edit || isset($vars['value_id']),
                    'rule' => 'is_scalar',
                    'message' => Language::_('PackageOptionConditions.!error.value.valid', true)
                ]
            ],
            'value_id' => [
                'exists' => [
                    'if_set' => $edit || isset($vars['value']),
                    'rule' => function ($value_id) use ($that) {
                        if (is_array($value_id)) {
                            $value_ids = $value_id;
                        } else {
                            $value_ids = [$value_id];
                        }

                        $valid = true;
                        foreach ($value_ids as $id) {
                            $valid = $valid && $that->validateExists($id, 'id', 'package_option_values');
                        }

                        return $valid;
                    },
                    'message' => Language::_('PackageOptionConditions.!error.value_id.exists', true)
                ]
            ],
        ];

        if ($edit) {
            // A valid package option condition is required
            $rules['id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'package_option_conditions'],
                    'message' => Language::_('PackageOptionConditions.!error.id.exists', true)
                ]
            ];
        }

        return $rules;
    }
}
