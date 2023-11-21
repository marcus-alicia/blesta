<?php

/**
 * Package Option Group management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PackageOptionGroups extends AppModel
{
    /**
     * Initialize PackageOptionGroups
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['package_option_groups']);
    }

    /**
     * Fetches the package option group
     *
     * @param int $option_group_id The ID of the package option group to fetch
     * @return mixed A stdClass object representing the package option group, false if no such group exists
     */
    public function get($option_group_id)
    {
        $option_group = $this->Record->select()
            ->from('package_option_groups')
            ->where('id', '=', $option_group_id)
            ->fetch();

        // Fetch all package IDs
        if ($option_group) {
            $option_group->packages = $this->getAllPackageIds($option_group->id);
        }

        return $option_group;
    }

    /**
     * Fetches all package option groups for a given company
     *
     * @param int $company_id The company ID
     * @param array $filters A list of package groups filters including:
     *
     *  - hidden Whether or nor to include the hidden package option groups
     * @return array An array of stdClass objects representing each package option group
     */
    public function getAll($company_id, array $filters = [])
    {
        $option_groups = $this->getGroups($company_id, $filters)->fetchAll();

        // Fetch all package IDs
        foreach ($option_groups as &$option_group) {
            $option_group->packages = $this->getAllPackageIds($option_group->id);
        }

        return $option_groups;
    }

    /**
     * Fetches the package IDs of all packages assigned to this package option group
     *
     * @param int $option_group_id The ID of the package option group whose assigned packages to fetch
     * @return array An array of stdClass objects representing
     */
    public function getAllPackageIds($option_group_id)
    {
        $package_ids = [];
        $packages = $this->Record->select('package_id')->from('package_option')->
            where('option_group_id', '=', $option_group_id)->fetchAll();

        foreach ($packages as $package) {
            $package_ids[] = $package->package_id;
        }

        return $package_ids;
    }

    /**
     * Fetches all package options assigned to this package option group
     *
     * @param int $option_group_id The ID of the package option group whose package options to fetch
     * @param array $filters A list of package groups filters including:
     *
     *  - hidden Whether or nor to include the hidden package options
     * @return array An array of stdClass objects representing each package option group
     */
    public function getAllOptions($option_group_id, array $filters = [])
    {
        $fields = ['package_options.*'];
        $options = $this->Record->select($fields)->from('package_options')->
            on('package_option_group.option_group_id', '=', $option_group_id)->
            innerJoin('package_option_group', 'package_option_group.option_id', '=', 'package_options.id', false)->
            order(['package_option_group.order' => 'ASC']);

        if (isset($filters['hidden']) && !(bool)$filters['hidden']) {
            $options->where('package_options.hidden', '=', 0);
        }

        return $options->fetchAll();
    }

    /**
     * Fetches a list of all package option groups for a given company
     *
     * @param int $company_id The company ID to fetch package option groups for
     * @param int $page The page to return results for
     * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @param array $filters A list of package groups filters including:
     *
     *  - hidden Whether or nor to include the hidden package option groups
     * @return array An array of objects, each representing a package option group
     */
    public function getList($company_id, $page = 1, array $order_by = ['name' => 'asc'], array $filters = [])
    {
        $this->Record = $this->getGroups($company_id, $filters);

        if ($order_by) {
            $this->Record->order($order_by);
        }

        return $this->Record->limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->fetchAll();
    }

    /**
     * Returns the total number of package option groups returned from PackageOptionGroups::getList(),
     * useful in constructing pagination for the getList() method.
     *
     * @param int $company_id The company ID to fetch package option groups for
     * @param array $filters A list of package groups filters including:
     *
     *  - hidden Whether or nor to include the hidden package option groups
     * @return int The total number of package option groups
     * @see PackageOptionGroups::getList()
     */
    public function getListCount($company_id, array $filters = [])
    {
        return $this->getGroups($company_id, $filters)->numResults();
    }

    /**
     * Partially-constructs the Record object for fetching package option groups
     *
     * @param int $company_id The company ID to filter package option groups
     * @param array $filters A list of package groups filters including:
     *
     *  - hidden Whether or nor to include the hidden package option groups
     * @return Record A partially-constructed Record object
     */
    private function getGroups($company_id, array $filters = [])
    {
        $this->Record->select()->from('package_option_groups')->
            where('company_id', '=', $company_id);

        if (isset($filters['hidden']) && !(bool)$filters['hidden']) {
            $this->Record->where('package_option_groups.hidden', '=', 0);
        }

        return $this->Record;
    }

    /**
     * Adds a package option group for the given company
     *
     * @param array $vars An array of package option group info including:
     *
     *  - company_id The ID for the company under which to add the package option group
     *  - name The package option group name
     *  - description The description of the group
     *  - packages A list of package IDs to assign to this option group (optional)
     *  - hidden 1 if the option is hidden (optional)
     *  - hide_options Whether options in this group disabled by option logic should be hidden (optional)
     * @return int The package option group ID, void on error
     */
    public function add(array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            // Start a transaction
            $in_transaction = $this->Record->getConnection()->inTransaction();
            if (!$in_transaction) {
                $this->Record->begin();
            }

            $fields = ['company_id', 'name', 'description', 'hidden', 'hide_options'];
            $this->Record->insert('package_option_groups', $vars, $fields);
            $option_group_id = $this->Record->lastInsertId();

            // Set package options, if any
            if (!empty($vars['packages'])) {
                $this->setPackages($option_group_id, $vars['packages']);
            }

            // Commit
            if (!$in_transaction) {
                $this->Record->commit();
            }

            return $option_group_id;
        }
    }

    /**
     * Updates a package option group
     *
     * @param int $option_group_id The ID of the package option group to update
     * @param array $vars An array of package option group info including:
     *
     *  - name The package option group name
     *  - description The description of the group
     *  - packages A list of package IDs to assign to this option group (optional)
     *  - hidden 1 if the option is hidden (optional)
     *  - hide_options Whether options in this group disabled by option logic should be hidden (optional)
     * @return int The package option group ID, void on error
     */
    public function edit($option_group_id, array $vars)
    {
        // Cannot change company ID
        unset($vars['company_id']);

        $vars['group_id'] = $option_group_id;
        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            // Start a transaction
            $in_transaction = $this->Record->getConnection()->inTransaction();
            if (!$in_transaction) {
                $this->Record->begin();
            }

            $fields = ['name', 'description', 'hidden', 'hide_options'];
            $this->Record->where('id', '=', $option_group_id)->update('package_option_groups', $vars, $fields);

            // Set package options, if any
            if (isset($vars['packages'])) {
                $this->removePackages($option_group_id);
                if (!empty($vars['packages'])) {
                    $this->setPackages($option_group_id, $vars['packages']);
                }
            }

            // Commit
            if (!$in_transaction) {
                $this->Record->commit();
            }

            return $option_group_id;
        }
    }

    /**
     * Permanently removes a package option group from the system
     *
     * @param int $option_group_id The package option group ID to delete
     */
    public function delete($option_group_id)
    {
        // Delete the package option group and option references
        $this->Record->from('package_option_groups')
            ->leftJoin(
                'package_option_group',
                'package_option_group.option_group_id',
                '=',
                'package_option_groups.id',
                false
            )
            ->leftJoin(
                'package_option',
                'package_option.option_group_id',
                '=',
                'package_option_groups.id',
                false
            )
            ->where('package_option_groups.id', '=', $option_group_id)
            ->delete(['package_option_group.*', 'package_option_groups.*', 'package_option.*']);
    }

    /**
     * Save the package options for the given group in the provided order
     *
     * @param int $option_group_id The ID of the group to order options for
     * @param array $option_ids A numerically indexed array of option IDs
     */
    public function orderOptions($option_group_id, array $option_ids)
    {
        for ($i = 0, $total = count($option_ids); $i < $total; $i++) {
            $this->Record->where('option_id', '=', $option_ids[$i])->
                where('option_group_id', '=', $option_group_id)->
                update('package_option_group', ['order' => $i]);
        }
    }

    /**
     * Assigns packages to a package option group
     *
     * @param int $option_group_id The package option group ID
     * @param array $packages A numerically-indexed list of package IDs to assign
     */
    private function setPackages($option_group_id, array $packages)
    {
        foreach ($packages as $package_id) {
            $vars = ['package_id' => $package_id, 'option_group_id' => $option_group_id];
            $this->Record->duplicate('option_group_id', '=', $option_group_id)->insert('package_option', $vars);
        }
    }

    /**
     * Removes all package assignments from a package option group
     *
     * @param int $option_group_id The package option group ID
     */
    private function removePackages($option_group_id)
    {
        $this->Record->from('package_option')->where('option_group_id', '=', $option_group_id)->delete();
    }

    /**
     * Validates that the given group is valid
     *
     * @param int $package_id The ID of the package to validate
     * @param int $company_id The ID of the company the package should belong
     *  to (optional, required if $option_group_id not given, default null)
     * @param int $option_group_id The ID of the option group that the package
     *  is to be assigned to (optional, required if $company_id not given, default null)
     * @return bool True if the package option group is valid, or false otherwise
     */
    public function validatePackage($package_id, $company_id = null, $option_group_id = null)
    {
        // Package may not be given
        if (empty($package_id)) {
            return true;
        }

        // Fetch the company ID that the package should belong to from the option group
        if ($option_group_id) {
            $option_group = $this->Record->select('company_id')
                ->from('package_option_groups')
                ->where('id', '=', $option_group_id)
                ->fetch();
            if ($option_group) {
                $company_id = $option_group->company_id;
            }
        }

        // Check whether this is a valid package
        $count = $this->Record->select(['id'])->from('packages')->
            where('id', '=', $package_id)->where('company_id', '=', $company_id)->numResults();

        return ($count > 0);
    }

    /**
     * Retrieves the rules for adding/editing a package option group
     *
     * @param array $vars A list of input vars used in validation
     * @param bool $edit True to fetch the edit rules, false for the add rules (optional, default false)
     * @return array A list of rules
     */
    private function getRules(array $vars, $edit = false)
    {
        $rules = [
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('PackageOptionGroups.!error.company_id.exists')
                ]
            ],
            'name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('PackageOptionGroups.!error.name.empty')
                ],
                'length' => [
                    'rule' => ['maxLength', 128],
                    'message' => $this->_('PackageOptionGroups.!error.name.length')
                ]
            ],
            'packages[]' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [
                        [$this, 'validatePackage'],
                        (isset($vars['company_id']) ? $vars['company_id'] : null),
                        (isset($vars['group_id']) ? $vars['group_id'] : null)
                    ],
                    'message' => $this->_('Packages.!error.option_groups[].valid')
                ]
            ],
            'hide_options' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array',  [0, 1]],
                    'message' => $this->_('PackageOptionGroups.!error.hide_options.valid')
                ]
            ],
            'hidden' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array',  [0, 1]],
                    'message' => $this->_('PackageOptionGroups.!error.hidden.valid')
                ]
            ]
        ];

        if ($edit) {
            // Remove the company ID check
            unset($rules['company_id']);

            // Set all fields optional
            $rules = $this->setRulesIfSet($rules);

            // Require a valid group
            $rules['group_id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'package_option_groups'],
                    'message' => $this->_('PackageOptionGroups.!error.group_id.exists')
                ]
            ];
        }

        return $rules;
    }
}
