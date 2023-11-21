<?php

/**
 * Package Group management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PackageGroups extends AppModel
{
    /**
     * Initialize PackageGroups
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['package_groups']);
    }

    /**
     * Returns a list of supported package group types
     *
     * @return array A list of package group types and their language name
     */
    public function getTypes()
    {
        return [
            'standard' => $this->_('PackageGroups.gettypes.standard'),
            'addon' => $this->_('PackageGroups.gettypes.addon')
        ];
    }

    /**
     * Retrieves the number of package groups of a given type
     *
     * @param int $company_id The ID of the company whose package groups to count
     * @param string $type The type of package groups to count ("standard" or "addon", default "standard")
     * @param array $filters A list of package groups filters including:
     *
     *  - hidden Whether or nor to include the hidden package groups
     * @return int The number of package groups of the given type
     */
    public function getTypeCount($company_id, $type = 'standard', array $filters = [])
    {
        $this->Record->select('id')->from('package_groups')->
            where('company_id', '=', $company_id)->
            where('type', '=', $type);

        if (!(!empty($filters['hidden']) && (bool)$filters['hidden'])) {
            $this->Record->where('package_groups.hidden', '=', 0);
        }

        return $this->Record->numResults();
    }

    /**
     * Returns a package group
     *
     * @param int $package_group_id The package group ID
     * @return mixed An stdClass object representing the package group, or false if the package group does not exist
     */
    public function get($package_group_id)
    {
        $package_group = $this->Record->select($this->getSelectFieldList())
            ->from('package_groups')
            ->on('package_group_names.lang', '=', Configure::get('Blesta.language'))
            ->leftJoin('package_group_names', 'package_group_names.package_group_id', '=', 'package_groups.id', false)
            ->where('id', '=', $package_group_id)
            ->fetch();

        if ($package_group) {
            // Get package group parents
            if ($package_group->type == 'addon') {
                $package_group->parents = $this->Record->select($this->getSelectFieldList())
                    ->from('package_group_parents')
                    ->innerJoin(
                        'package_groups',
                        'package_groups.id',
                        '=',
                        'package_group_parents.parent_group_id',
                        false
                    )
                    ->where('package_group_parents.group_id', '=', $package_group->id)
                    ->fetchAll();

                foreach ($package_group->parents as &$parent) {
                    $parent = $this->format($parent);
                }
                usort(
                    $package_group->parents,
                    function ($groupA, $groupB) { return strcmp($groupA->name, $groupB->name); }
                );
            }

            // Format the package group
            $package_group = $this->format($package_group);
        }

        return $package_group;
    }

    /**
     * Gets a list a fields to fetch for package groups
     *
     * @return array A list a fields to fetch for package groups
     */
    private function getSelectFieldList()
    {
        return [
            'package_groups.id',
            'package_groups.type',
            'package_groups.hidden',
            'package_groups.company_id',
            'package_groups.allow_upgrades'
        ];
    }

    /**
     * Formats the given package group
     *
     * @param stdClass $group An stdClass object representing a single package group
     * @return stdClass The given package group with formatted attributes
     */
    private function format(stdClass $group)
    {
        // Format the package group
        $group = $this->appendGroupNamesDescriptions($group);

        // Format the parents
        if (!empty($group->parents)) {
            foreach ($group->parents as $index => $parent) {
                $group->parents[$index] = $this->appendGroupNamesDescriptions($parent);
            }
        }

        return $group;
    }

    /**
     * Updates the given package group to set names and descriptions
     *
     * @param stdClass $package_group The package_group to update, containing at minimum:
     *
     *  - id The package group ID
     * @return stdClass The updated package group
     */
    private function appendGroupNamesDescriptions(stdClass $package_group)
    {
        $package_group->names = $this->getPackageGroupNames($package_group->id);
        $package_group->descriptions = $this->getPackageGroupDescriptions($package_group->id);

        foreach ($package_group->names as $name) {
            if ($name->lang == Configure::get('Blesta.language')) {
                $package_group->name = $name->name;
                break;
            } elseif ($name->lang == 'en_us') {
                $package_group->name = $name->name;
            }
        }

        foreach ($package_group->descriptions as $description) {
            if ($description->lang == Configure::get('Blesta.language')) {
                $package_group->description = $description->description;
                break;
            } elseif ($description->lang == 'en_us') {
                $package_group->description = $description->description;
            }
        }

        return $package_group;
    }

    /**
     * Fetches all package groups for a given company
     *
     * @param int $company_id The company ID
     * @param string $type The type of package group to get ('standard' or 'addon', optional, default both)
     * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @return array An array of stdClass objects representing each package group
     */
    public function getAll($company_id, $type = null, array $order_by = ['name' => 'asc'])
    {
        $this->Record->select($this->getSelectFieldList())
            ->from('package_groups')
            ->on('package_group_names.lang', '=', Configure::get('Blesta.language'))
            ->leftJoin('package_group_names', 'package_group_names.package_group_id', '=', 'package_groups.id', false)
            ->where('company_id', '=', $company_id);

        // Specify a type to get
        if ($type != null) {
            $this->Record->where('type', '=', $type);
        }

        $package_groups = $this->Record
            ->order($order_by)
            ->fetchAll();

        // Format the package groups
        foreach ($package_groups as $index => $package_group) {
            $package_groups[$index] = $this->format($package_group);
        }

        return $package_groups;
    }

    /**
     * Fetches a list of all package groups for a given company
     *
     * @param int $company_id The company ID to fetch package groups for
     * @param int $page The page to return results for
     * @param string $type The type of package group to get ("standard" or "addon", null for both)
     * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @param array $filters A list of package groups filters including:
     *
     *  - hidden Whether or nor to include the hidden package groups
     * @return array An array of objects, each representing a package group
     */
    public function getList($company_id, $page = 1, $type = null, array $order_by = ['name' => 'asc'], array $filters = [])
    {
        $this->Record = $this->getPackageGroups($company_id, $type, $filters);

        // Return the results
        $package_groups = $this->Record->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->fetchAll();

        foreach ($package_groups as &$package_group) {
            $package_group->parents = $this->Record->select($this->getSelectFieldList())->
                from('package_group_parents')->
                innerJoin('package_groups', 'package_groups.id', '=', 'package_group_parents.parent_group_id', false)->
                on('package_group_names.lang', '=', Configure::get('Blesta.language'))->
                leftJoin(
                    'package_group_names',
                    'package_group_names.package_group_id',
                    '=',
                    'package_groups.id',
                    false
                )->
                where('package_group_parents.group_id', '=', $package_group->id)->
                order($order_by)->
                fetchAll();

            // Format the package group
            $package_group = $this->format($package_group);
        }

        return $package_groups;
    }

    /**
     * Return the total number of package groups returned from PackageGroups::getList(),
     * useful in constructing pagination for the getList() method.
     *
     * @param int $company_id The company ID to fetch package groups for
     * @param string $type The type of package group to get ("standard" or "addon", null for both)
     * @param array $filters A list of package groups filters including:
     *
     *  - hidden Whether or nor to include the hidden package groups
     * @return int The total number of package groups
     * @see PackageGroups::getList()
     */
    public function getListCount($company_id, $type = null, array $filters = [])
    {
        $this->Record = $this->getPackageGroups($company_id, $type, $filters);

        // Return the number of results
        return $this->Record->numResults();
    }

    /**
     * Fetches all names created for the given package group
     *
     * @param int $package_group_id The package group ID to fetch names for
     * @return array An array of stdClass objects representing names
     */
    private function getPackageGroupNames($package_group_id)
    {
        return $this->Record->select(['lang', 'name'])
            ->from('package_group_names')
            ->where('package_group_id', '=', $package_group_id)
            ->fetchAll();
    }

    /**
     * Fetches all descriptions created for the given package group
     *
     * @param int $package_group_id The package group ID to fetch descriptions for
     * @return array An array of stdClass objects representing descriptions
     */
    private function getPackageGroupDescriptions($package_group_id)
    {
        return $this->Record->select(['lang', 'description'])
            ->from('package_group_descriptions')
            ->where('package_group_id', '=', $package_group_id)
            ->fetchAll();
    }

    /**
     * Partially constructs the query required by both PackageGroups::getList() and
     * PackageGroups::getListCount()
     *
     * @param int $company_id The company ID to fetch package groups for
     * @param string $type The type of package group to get ("standard" or "addon", null for both)
     * @param array $filters A list of package groups filters including:
     *
     *  - hidden Whether or nor to include the hidden package groups
     * @return Record The partially constructed query Record object
     */
    private function getPackageGroups($company_id, $type = null, array $filters = [])
    {
        $this->Record->select($this->getSelectFieldList())->
            from('package_groups')->
            on('package_group_names.lang', '=', Configure::get('Blesta.language'))->
            leftJoin('package_group_names', 'package_group_names.package_group_id', '=', 'package_groups.id', false)->
            where('company_id', '=', $company_id);

        if ($type != null) {
            $this->Record->where('type', '=', $type);
        }

        if (!(!empty($filters['hidden']) && (bool)$filters['hidden'])) {
            $this->Record->where('package_groups.hidden', '=', 0);
        }

        return $this->Record;
    }

    /**
     * Adds a package group for the given company
     *
     * @param array $vars An array of package group info including:
     *
     *  - company_id The ID for the company under which to add the package group
     *  - names A list of names for the package group in different languages, each including:
     *      - lang The language in ISO 636-1 2-char + "_" + ISO 3166-1 2-char (e.g. en_us)
     *      - name The name in the specified language
     *  - type The package group type, ('standard', or 'addon', optional, default 'standard')
     *  - descriptions A list of descriptions for this package group in different languages (optional)
     *      - lang The language in ISO 636-1 2-char + "_" + ISO 3166-1 2-char (e.g. en_us)
     *      - description The description in the specified language (optional, default null)
     *  - parents If type is 'addon', an array of 'standard' package groups this group belongs to
     *  - hidden Whether or not to hide this package group in the interface
     *      - 1 = true, 0 = false (optional, default 0)
     *  - allow_upgrades Whether or not packages within this group can be changed:
     *      - 1 = true, 0 = false (optional, default 1)
     * @return int The package group ID, void on error
     */
    public function add(array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ['type', 'company_id', 'hidden', 'allow_upgrades'];
            $this->Record->insert('package_groups', $vars, $fields);
            $package_group_id = $this->Record->lastInsertId();

            // Add package group descriptions
            if (!empty($vars['descriptions']) && is_array($vars['descriptions'])) {
                $this->setDescriptions($package_group_id, $vars['descriptions']);
            }

            // Add package group names
            if (!empty($vars['names']) && is_array($vars['names'])) {
                $this->setNames($package_group_id, $vars['names']);
            }

            if ($vars['type'] == 'addon' && isset($vars['parents'])) {
                // Add all parent groups that this group belongs to
                foreach ($vars['parents'] as $parent_group_id) {
                    $this->Record->set('group_id', $package_group_id)->
                        set('parent_group_id', $parent_group_id)->
                        insert('package_group_parents');
                }
            }

            return $package_group_id;
        }
    }

    /**
     * Updates a package group
     *
     * @param int $package_group_id The package group ID to update
     * @param array $vars An array of package group info including:
     *
     *  - company_id The ID for the company to which this package group belongs
     *  - names A list of names for the package group in different languages
     *      - lang The language in ISO 636-1 2-char + "_" + ISO 3166-1 2-char (e.g. en_us)
     *      - name The name in the specified language
     *  - type The package group type, 'standard', or 'addon' (optional, default standard)
     *  - descriptions A list of descriptions for this package group in different languages (optional)
     *      - lang The language in ISO 636-1 2-char + "_" + ISO 3166-1 2-char (e.g. en_us)
     *      - description The description in the specified language (optional, default null)
     *  - parents If type is 'addon', a numerically indexed array of 'standard' package groups this group belongs to
     *  - hidden Whether or not to hide this package group in the interface
     *      - 1 = true, 0 = false (optional, default 0)
     *  - allow_upgrades Whether or not packages within this group can be changed
     *      - 1 = true, 0 = false (optional, default 1)
     */
    public function edit($package_group_id, array $vars)
    {
        $rules = $this->getRules($vars, true);

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            $fields = ['type', 'hidden', 'allow_upgrades'];
            $this->Record->where('id', '=', $package_group_id)->update('package_groups', $vars, $fields);

            // Update package group descriptions
            if (!empty($vars['descriptions']) && is_array($vars['descriptions'])) {
                $this->setDescriptions($package_group_id, $vars['descriptions']);
            }

            // Update package group names
            if (!empty($vars['names']) && is_array($vars['names'])) {
                $this->setNames($package_group_id, $vars['names']);
            }

            // Delete all from parents, re-add as needed
            $this->Record->from('package_group_parents')->
                where('group_id', '=', $package_group_id)->delete();

            if ($vars['type'] == 'addon') {
                if (isset($vars['parents'])) {
                    // Add all parent groups this group belongs to
                    foreach ($vars['parents'] as $parent_group_id) {
                        $this->Record->set('group_id', $package_group_id)->
                            set('parent_group_id', $parent_group_id)->
                            insert('package_group_parents');
                    }
                }
            }
        }
    }

    /**
     * Permanently removes a package group from the system.
     *
     * @param int $package_group_id The package group ID to delete
     */
    public function delete($package_group_id)
    {
        // Start a transaction
        $this->Record->begin();

        // Unassign any packages assigned to this package group
        $this->Record->from('package_group')->where('package_group_id', '=', $package_group_id)->delete();

        // Delete any names from this package group
        $this->Record->from('package_group_names')->where('package_group_id', '=', $package_group_id)->delete();

        // Delete any descriptions from this package group
        $this->Record->from('package_group_descriptions')->where('package_group_id', '=', $package_group_id)->delete();

        // Delete the references from package_group_parents to this package group
        $this->Record->from('package_group_parents')->where('group_id', '=', $package_group_id)->
            orWhere('parent_group_id', '=', $package_group_id)->delete();

        // Delete the package group itself
        $this->Record->from('package_groups')->where('id', '=', $package_group_id)->delete();

        $this->Record->commit();
    }

    /**
     * Sets the multilingual package group names
     *
     * @param int $package_group_id The ID of the package group to set the names for
     * @param array $names An array of names including:
     *
     *  - lang The language code (e.g. 'en_us')
     *  - name The name in the specified language
     */
    private function setNames($package_group_id, array $names)
    {
        // Add package group names
        if (!empty($names)) {
            foreach ($names as $name) {
                // Skip any that are not provided with enough information
                if (!isset($name['name'])
                    || !isset($name['lang'])
                    || !is_scalar($name['name'])
                    || !is_scalar($name['lang'])
                ) {
                    continue;
                }

                $name['package_group_id'] = $package_group_id;
                $fields = ['package_group_id', 'lang', 'name'];
                $this->Record->duplicate('name', '=', $name['name'])
                    ->insert('package_group_names', $name, $fields);
            }
        }
    }

    /**
     * Sets the multilingual package group descriptions
     *
     * @param int $package_group_id The ID of the package group to set the descriptions for
     * @param array $descriptions An array of descriptions including:
     *
     *  - lang The language code (e.g. 'en_us')
     *  - description The description in the specified language (optional, default null)
     */
    private function setDescriptions($package_group_id, array $descriptions)
    {
        // Add package group descriptions
        if (!empty($descriptions)) {
            foreach ($descriptions as $description) {
                // Skip any that are not provided with enough information
                if (!isset($description['lang'])
                    || (isset($description['description']) && !is_scalar($description['description']))
                    || !is_scalar($description['lang'])
                ) {
                    continue;
                }

                $description['package_group_id'] = $package_group_id;
                $fields = ['package_group_id', 'lang', 'description'];
                $this->Record->duplicate('description', '=', (isset($description['description']) ? $description['description'] : null))
                    ->insert('package_group_descriptions', $description, $fields);
            }
        }
    }

    /**
     * Checks to ensure that every group parent consists of valid data
     *
     * @param array $parents A numerically-indexed array of parent group IDs
     * @param int $company_id The company ID to which this group belongs
     * @param string $type The type of group
     * @return bool True if every group parent consists of valid data, false otherwise
     */
    public function validateGroupParents(array $parents, $company_id, $type)
    {
        if ($type != 'addon') {
            // error, type must be addon
            return false;
        }

        // Get all groups that could potentially be a parent
        $standard_groups = $this->getAll($company_id, 'standard');

        // Create a list of available parent groups
        $available_groups = [];
        foreach ($standard_groups as $standard_group) {
            $available_groups[] = $standard_group->id;
        }

        // Check that every parent group ID given is in our list of available parent groups
        foreach ($parents as $parent_group_id) {
            if (!in_array($parent_group_id, $available_groups)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validates that the given type is a valid package group type
     *
     * @param string $type The package group type
     * @return bool True if the package group type is valid, false otherwise
     */
    public function validateType($type)
    {
        $types = $this->getTypes();
        return isset($types[$type]);
    }

    /**
     * Returns the rules for adding/editing package groups
     *
     * @param array $vars Key/value pairs of data to replace in language
     * @param bool $edit True to fetch the edit rules, or false for the add rules (optional, default false)
     * @return array The package group rules
     */
    private function getRules(array $vars, $edit = false)
    {
        $rules = [
            'names' => [
                'format' => [
                    'rule' => function ($names) use ($vars) {
                        // The 'names' value must be in the correct format
                        if (!is_array($names)) {
                            return false;
                        }

                        // The 'name' and 'lang' keys must exist
                        foreach ($names as $name) {
                            if (!array_key_exists('name', $name) || !array_key_exists('lang', $name)) {
                                return false;
                            }
                        }

                        return true;
                    },
                    'message' => $this->_('PackageGroups.!error.names.format'),
                    // Don't validate the subsequent rules if the formatting is invalid
                    'final' => true
                ],
                'empty_name' => [
                    'if_set' => !empty($vars['name']),
                    'rule' => function ($names) {
                        // The above rule is a 'final' rule, so we can assume the formatting is valid
                        // Verify all name values are not empty
                        foreach ($names as $name) {
                            if (empty($name['name'])) {
                                return false;
                            }
                        }

                        return true;
                    },
                    'message' => $this->_('PackageGroups.!error.names.empty_name')
                ],
                'empty_lang' => [
                    'if_set' => !empty($vars['name']),
                    'rule' => function ($names) {
                        // The above rule is a 'final' rule, so we can assume the formatting is valid
                        // Verify all lang values are not empty
                        foreach ($names as $name) {
                            if (empty($name['lang'])) {
                                return false;
                            }
                        }

                        return true;
                    },
                    'message' => $this->_('PackageGroups.!error.names.empty_lang')
                ]
            ],
            'descriptions' => [
                'format' => [
                    'if_set' => true,
                    'rule' => function ($descriptions) use ($vars) {
                        // The 'descriptions' value must be in the correct format
                        if (!is_array($descriptions)) {
                            return false;
                        }

                        // The 'description' and 'lang' keys must exist
                        foreach ($descriptions as $description) {
                            if (!array_key_exists('lang', $description)
                                || (array_key_exists('description', $description)
                                    && !is_scalar($description['description'])
                                )
                            ) {
                                return false;
                            }
                        }

                        return true;
                    },
                    'message' => $this->_('PackageGroups.!error.descriptions.format'),
                    // Don't validate the subsequent rules if the formatting is invalid
                    'final' => true
                ],
                'empty_lang' => [
                    'if_set' => true,
                    'rule' => function ($descriptions) {
                        // The above rule is a 'final' rule, so we can assume the formatting is valid
                        // Verify all lang values are not empty
                        foreach ($descriptions as $description) {
                            if (empty($description['lang'])) {
                                return false;
                            }
                        }

                        return true;
                    },
                    'message' => $this->_('PackageGroups.!error.descriptions.empty_lang')
                ]
            ],
            'type' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateType']],
                    'message' => $this->_('PackageGroups.!error.type.format')
                ]
            ],
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('PackageGroups.!error.company_id.exists')
                ]
            ],
            'parents' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [
                        [$this, 'validateGroupParents'],
                        (isset($vars['company_id']) ? $vars['company_id'] : null),
                        (isset($vars['type']) ? $vars['type'] : null)
                    ],
                    'message' => $this->_('PackageGroups.!error.parents.format')
                ]
            ],
            'hidden' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', [0, 1]],
                    'message' => $this->_('PackageGroups.!error.hidden.format')
                ]
            ],
            'allow_upgrades' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', [0, 1]],
                    'message' => $this->_('PackageGroups.!error.allow_upgrades.format')
                ]
            ]
        ];

        // No company_id will be passed on edit
        if ($edit) {
            unset($rules['company_id']);
        }

        return $rules;
    }
}
