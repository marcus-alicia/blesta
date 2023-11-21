<?php

/**
 * Package Option Condition Set management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PackageOptionConditionSets extends AppModel
{
    /**
     * Initialize PackageOptionConditionSets
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['package_option_condition_sets']);
        Loader::loadModels($this, ['PackageOptions', 'PackageOptionConditions', 'PackageOptionGroups']);
    }

    /**
     * Fetches the package option condition set
     *
     * @param int $set_id The ID of the package option condition set to fetch
     * @return mixed A stdClass object representing the option condition set, false if no such set exists
     * @see PackageOptionConditions::getAll()
     */
    public function get($set_id)
    {
        if (($set = $this->getSets(['id' => $set_id])->group('package_option_condition_sets.id')->fetch())) {
            $set = $this->appendDetails($set);
        }
        return $set;
    }

    /**
     * Fetches all package option condition sets
     *
     * @param array $filters A list of filters:
     *
     *  - id The ID of the package option condition set
     *  - option_group_id The ID of the package option group for the set
     *  - option_id The ID of the package option for the set
     *  - option_ids The IDs of the package options by which to filter the sets
     *  - package_id The ID of the package for the set
     * @return array An array of stdClass objects representing each package option condition
     * @see PackageOptionConditions::getAll()
     */
    public function getAll(array $filters = [])
    {
        $sets = $this->getSets($filters)->group('package_option_condition_sets.id')->fetchAll();
        foreach (($sets ?? []) as &$set) {
            $set = $this->appendDetails($set);
        }

        return $sets;
    }

    /**
     * Appends addition details to a condition set object
     *
     * @param stdClass $set The condition set object
     * @return stdClass The updated condition set object
     */
    function appendDetails($set)
    {
        $set->option = $this->PackageOptions->get($set->option_id);
        $set->option_value_ids = array_map(
            function ($value) { return $value->value_id; },
            $this->Record->select()->
                from('package_option_condition_set_values')->
                where('package_option_condition_set_values.condition_set_id', '=', $set->id)->
                fetchAll()
        );
        $set->option_value_id = $set->option_value_ids[0] ?? null;
        $set->option_group = $this->PackageOptionGroups->get($set->option_group_id);
        $set->conditions = $this->PackageOptionConditions->getAll(['condition_set_id' => $set->id]);

        return $set;
    }

    /**
     * Partially-constructs the Record object for fetching package option condition sets
     *
     * @param array $filters A list of filters:
     *
     *  - id The ID of the package option condition set
     *  - option_group_id The ID of the package option group for the set
     *  - option_id The ID of the package option for the set
     *  - option_ids The IDs of the package options by which to filter the sets
     *  - package_id The ID of the package for the set
     * @param bool $select Whether to select field in the query
     * @return Record A partially-constructed Record object
     */
    private function getSets(array $filters = [], $select = true)
    {
        if ($select) {
            $this->Record->select(['package_option_condition_sets.*']);
        }
        $this->Record->from('package_option_condition_sets');

        if (isset($filters['id'])) {
            $this->Record->where('package_option_condition_sets.id', '=', $filters['id']);
        }
        if (isset($filters['option_group_id'])) {
            $this->Record->where('package_option_condition_sets.option_group_id', '=', $filters['option_group_id']);
        }
        if (isset($filters['option_id'])) {
            $this->Record->where('package_option_condition_sets.option_id', '=', $filters['option_id']);
        }
        if (isset($filters['option_ids'])) {
            $this->Record->where('package_option_condition_sets.option_id', 'in', $filters['option_ids']);
        }
        if (isset($filters['package_id'])) {
            $this->Record->innerJoin(
                    'package_option',
                    'package_option.option_group_id',
                    '=',
                    'package_option_condition_sets.option_group_id',
                    false
                )
                ->innerJoin(
                    'packages',
                    'packages.id',
                    '=',
                    'package_option.package_id',
                    false
                )
                ->where('packages.id', '=', $filters['package_id']);
        }

        return $this->Record;
    }

    /**
     * Adds a package option condition set
     *
     * @param array $vars An array of package option condition set info including:
     *
     *  - option_group_id The ID of the option group this condition set is for
     *  - option_id The ID of the option this condition set is for
     *  - option_value_ids The ID of the package option values for the set (optional)
     * @return int The package option condition set ID, void on error
     */
    public function add(array $vars)
    {
        // If the deprecated option_value_id field is submitted, use it to set option_value_ids
        if (isset($vars['option_value_id'])) {
            $vars['option_value_ids'] = [$vars['option_value_id']];
        }

        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            // Add the package option condition set
            $fields = ['option_group_id', 'option_id'];
            $this->Record->insert('package_option_condition_sets', $vars, $fields);
            $set_id = $this->Record->lastInsertId();


            // Add option value IDs
            foreach ($vars['option_value_ids'] as $option_value_id) {
                $this->Record->insert(
                    'package_option_condition_set_values',
                    ['condition_set_id' => $set_id, 'value_id' => $option_value_id]
                );
            }

            return $set_id;
        }
    }

    /**
     * Updates a package option
     *
     * @param int $set_id The ID of the package option condition set to update
     * @param array $vars An array of package option condition set info including:
     *
     *  - option_group_id The ID of the option group this condition set is for
     *  - option_id The ID of the option this condition set is for
     *  - option_value_ids The ID of the package option values for the set
     * @return int The package option condition set ID, void on error
     */
    public function edit($set_id, array $vars)
    {
        // If the deprecated option_value_id field is submitted, use it to set option_value_ids
        if (isset($vars['option_value_id'])) {
            $vars['option_value_ids'] = [$vars['option_value_id']];
        }

        // Set the condition set ID field
        $vars['id'] = $set_id;

        $this->Input->setRules($this->getRules($vars, true));
        if ($this->Input->validates($vars)) {
            // Edit the package option condition set
            $fields = ['option_group_id', 'option_id'];
            $this->Record->where('id', '=', $set_id)->update('package_option_condition_sets', $vars, $fields);

            // Update option value IDs
            if (isset($vars['option_value_ids'])) {
                // Delete existing option value IDs
                $this->Record->from('package_option_condition_set_values')->
                    where('package_option_condition_set_values.condition_set_id', '=', $set_id)->
                    delete();

                // Add new option value IDs
                foreach ($vars['option_value_ids'] as $option_value_id) {
                    $this->Record->insert(
                        'package_option_condition_set_values',
                        ['condition_set_id' => $set_id, 'value_id' => $option_value_id]
                    );
                }
            }

            return $set_id;
        }
    }

    /**
     * Permanently removes a package option condition set from the system
     *
     * @param int $set_id The package option condition set ID to delete
     */
    public function delete($set_id)
    {
        // Delete the package option condition set
        $this->getSets(['id' => $set_id], false)->
            leftJoin(
                'package_option_condition_set_values',
                'package_option_condition_set_values.condition_set_id',
                '=',
                'package_option_condition_sets.id'
            )->
            delete(['package_option_condition_sets.*', 'package_option_condition_set_values.*']);
    }

    /**
     * Retrieves a list of rules for adding/editing package option condition sets
     *
     * @param array $vars A list of input vars used in validation
     *
     *  - id The ID of the package option condition set
     *  - option_group_id The ID of the package option group for the set
     *  - option_id The ID of the package option for the set
     *  - option_value_ids The ID of the package option values for the set
     * @param bool $edit True to fetch the edit rules, false for the add rules (optional, default false)
     * @return array A list of rules
     */
    private function getRules(array $vars, $edit = false)
    {
        $rules = [
            'option_group_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'package_option_groups'],
                    'message' => Language::_('PackageOptionConditionSets.!error.option_group_id.exists', true)
                ]
            ],
            'option_id' => [
                'exists' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateExists'], 'id', 'package_options'],
                    'message' => Language::_('PackageOptionConditionSets.!error.option_id.exists', true)
                ]
            ],
            'option_value_ids[]' => [
                'exists' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateExists'], 'id', 'package_option_values'],
                    'message' => Language::_('PackageOptionConditionSets.!error.option_value_ids.exists', true)
                ]
            ],
        ];

        if ($edit) {
            // A valid package option condition set is required
            $rules['id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'package_option_condition_sets'],
                    'message' => Language::_('PackageOptionConditionSets.!error.id.exists', true)
                ]
            ];
        }

        return $rules;
    }
}
