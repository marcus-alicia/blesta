<?php

/**
 * Package Option management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PackageOptions extends AppModel
{
    /**
     * Initialize PackageOptions
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['package_options']);
    }

    /**
     * Fetches all package option values (and subsequent pricing) for the given package option
     *
     * @param int $option_id The ID of the option whose values to retrieve
     * @return array A list of package option values, including package option pricings
     */
    public function getValues($option_id)
    {
        $values = $this->Record->select()->from('package_option_values')->
            where('option_id', '=', $option_id)->
            order(['order' => 'ASC'])->
            fetchAll();

        // Fetch the pricing info for each package option value
        foreach ($values as &$value) {
            $value->pricing = $this->getValuePricing($value->id);
        }

        return $values;
    }

    /**
     * Fetches all package option groups associated with the given package option
     *
     * @param int $option_id The ID of the option whose groups to retrieve
     * @return array A list of package option groups this option value is assigned to
     */
    public function getGroups($option_id)
    {
        $fields = ['package_option_groups.*'];
        return $this->Record->select($fields)
            ->from('package_options')
            ->innerJoin(
                'package_option_group',
                'package_option_group.option_id',
                '=',
                'package_options.id',
                false
            )
            ->innerJoin(
                'package_option_groups',
                'package_option_groups.id',
                '=',
                'package_option_group.option_group_id',
                false
            )
            ->where('package_options.id', '=', $option_id)
            ->order(['package_option_group.order' => 'ASC'])
            ->fetchAll();
    }

    /**
     * Fetches the package option
     *
     * @param int $option_id The ID of the package option to fetch
     * @return mixed A stdClass object representing the package option, false if no such option exists
     */
    public function get($option_id)
    {
        $option = $this->getOption($option_id);

        if ($option) {
            $option->values = $this->getValues($option_id);
            $option->groups = $this->getGroups($option_id);
        }

        return $option;
    }

    /**
     * Retrieves the package option by ID
     *
     * @param int $option_id The ID of the package option to fetch
     * @return mixed A stdClass object representing the package option, or false if no such option exists
     */
    private function getOption($option_id)
    {
        return $this->Record->select()->from('package_options')->where('id', '=', $option_id)->fetch();
    }

    /**
     * Fetches the package options for the given package ID
     *
     * @param int $package_id The ID of the package to fetch options for
     * @return array An array of stdClass objects, each representing a package option
     */
    public function getByPackageId($package_id)
    {
        $fields = ['package_options.*'];
        $options = $this->Record->select($fields)
            ->from('package_option')
            ->innerJoin(
                'package_option_group',
                'package_option_group.option_group_id',
                '=',
                'package_option.option_group_id',
                false
            )
            ->innerJoin('package_options', 'package_options.id', '=', 'package_option_group.option_id', false)
            ->where('package_option.package_id', '=', $package_id)
            ->order(['package_option.order' => 'ASC', 'package_option_group.order' => 'ASC'])
            ->fetchAll();

        foreach ($options as &$option) {
            $option->values = $this->getValues($option->id);
        }
        return $options;
    }

    /**
     * Fetches a package option for a specific option pricing ID.
     * Only option pricing and values associated with the given option pricing ID will be retrieved.
     *
     * @param int $option_pricing_id The ID of the package option pricing value whose package option to fetch
     * @return mixed An stdClass object representing the package option, or false if none exist
     */
    public function getByPricingId($option_pricing_id)
    {
        // Fetch the option
        $fields = ['package_options.*', 'package_option_pricing.option_value_id'];
        $option = $this->Record->select($fields)
            ->from('package_option_pricing')
            ->innerJoin(
                'package_option_values',
                'package_option_values.id',
                '=',
                'package_option_pricing.option_value_id',
                false
            )
            ->innerJoin('package_options', 'package_options.id', '=', 'package_option_values.option_id', false)
            ->where('package_option_pricing.id', '=', $option_pricing_id)->fetch();

        if ($option) {
            // Fetch the specific package option value
            $option->value = $this->Record->select()->from('package_option_values')->
                where('id', '=', $option->option_value_id)->
                fetch();

            if ($option->value) {
                $option->value->pricing = $this->getValuePricingById($option_pricing_id);
            }
        }

        return $option;
    }

    /**
     * Fetches all package option for a given company
     *
     * @param int $company_id The company ID
     * @param array $filters A list of package groups filters including:
     *
     *  - hidden Whether or nor to include the hidden package options
     * @return array An array of stdClass objects representing each package option
     */
    public function getAll($company_id, array $filters = [])
    {
        return $this->getOptions($company_id, $filters)->fetchAll();
    }

    /**
     * Fetches a list of all package options for a given company
     *
     * @param int $company_id The company ID to fetch package options from
     * @param int $page The page to return results for
     * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @param array $filters A list of package groups filters including:
     *
     *  - hidden Whether or nor to include the hidden package options
     * @return array An array of objects, each representing a package option
     */
    public function getList($company_id, $page = 1, array $order_by = ['name' => 'asc'], array $filters = [])
    {
        $this->Record = $this->getOptions($company_id, $filters);

        if ($order_by) {
            $this->Record->order($order_by);
        }

        return $this->Record->limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->fetchAll();
    }

    /**
     * Returns the total number of packages returned from PackageOptions::getList(),
     * useful in constructing pagination for the getList() method.
     *
     * @param int $company_id The company ID to fetch package options from
     * @param array $filters A list of package groups filters including:
     *
     *  - hidden Whether or nor to include the hidden package options
     * @return int The total number of package options
     * @see PackageOptions::getList()
     */
    public function getListCount($company_id, array $filters = [])
    {
        return $this->getOptions($company_id, $filters)->numResults();
    }

    /**
     * Partially-constructs the Record object for fetching package options
     *
     * @param int $company_id The company ID to fetch package options from
     * @param array $filters A list of package groups filters including:
     *
     *  - hidden Whether or nor to include the hidden package options
     * @return Record A partially-constructed Record object
     */
    private function getOptions($company_id, array $filters = [])
    {
        $fields = ['package_options.*'];

        $this->Record->select($fields)->from('package_options')->
            where('package_options.company_id', '=', $company_id);


        if (isset($filters['hidden']) && !(bool)$filters['hidden']) {
            $this->Record->where('package_options.hidden', '=', 0);
        }

        return $this->Record;
    }

    /**
     * Removes data from input vars that may have adverse effects on adding/editing data
     *
     * @param array $vars An array of package option info
     * @return array An array of package option info without fields that are not to be set
     */
    private function removeOptionVars(array $vars)
    {
        // Pricing may not have a cancel fee
        if (array_key_exists('values', $vars)) {
            foreach ($vars['values'] as $index_value => $value) {
                if (array_key_exists('pricing', $vars['values'][$index_value])
                    && is_array($vars['values'][$index_value]['pricing'])
                ) {
                    foreach ($vars['values'][$index_value]['pricing'] as $index => $price) {
                        unset($vars['values'][$index_value]['pricing'][$index]['cancel_fee']);
                    }
                }
            }
        }

        return $vars;
    }

    /**
     * Formats option values
     *
     * @param array $vars An array of input containing:
     *
     *  - type The type of package option
     *  - values An array of option values and pricing
     * @return array An array of all given option values
     */
    private function formatValues(array $vars)
    {
        $values = [];

        if (isset($vars['values']) && is_array($vars['values'])) {
            foreach ($vars['values'] as $value) {
                $temp_val = $value;

                // All option values must be active unless the type is radio or select
                if (isset($vars['type']) && !in_array($vars['type'], ['radio', 'select'])) {
                    $temp_val['status'] = 'active';
                }

                // Non-quantity default values must be binary, 1 or 0
                if (isset($vars['type']) && $vars['type'] != 'quantity' && array_key_exists('default', $temp_val)) {
                    $temp_val['default'] = (int)((isset($temp_val['default']) ? $temp_val['default'] : '0') == '1');
                }

                // Each quantity value must be null
                if (isset($vars['type']) && $vars['type'] == 'quantity') {
                    $temp_val['value'] = null;
                    // The max value may be null (unlimited) if blank
                    $temp_val['max'] = (isset($temp_val['max']) && $temp_val['max'] != '' ? $temp_val['max'] : null);
                    // The min value is assumed to be 0 if not given
                    $temp_val['min'] = (isset($temp_val['min']) && $temp_val['min'] != '' ? $temp_val['min'] : 0);
                } else {
                    // Each text/textarea/password field must have a blank option value
                    if (isset($vars['type']) && in_array($vars['type'], ['text', 'textarea', 'password'])) {
                        $temp_val['name'] = '';
                        $temp_val['value'] = '';
                    } else {
                        // All other non-quantity types must have a non-null value
                        $temp_val['value'] = (isset($temp_val['value']) ? $temp_val['value'] : '');
                    }

                    // Each non-quantity type must have a null min/max/step
                    $temp_val['min'] = null;
                    $temp_val['max'] = null;
                    $temp_val['step'] = null;
                }

                $values[] = $temp_val;
            }
        }

        return $values;
    }

    /**
     * Adds a package option for the given company
     *
     * @param array $vars An array of package option info including:
     *
     *  - company_id The ID of the company to assign the option to
     *  - label The label displayed for this option
     *  - name The field name for this option
     *  - type The field type for this option, one of:
     *      - select
     *      - checkbox
     *      - radio
     *      - quantity
     *      - text
     *      - textarea
     *      - password
     *  - addable 1 if the option is addable by a client
     *  - editable 1 if the option is editable by a client
     *  - hidden 1 if the option is hidden
     *  - values A numerically indexed array of value info including:
     *      - name The name of the package option value (display name)
     *      - value The value of the package option (optional, default null)
     *      - default 1 or 0, whether or not this value is the default value (optional, default 0)
     *          Only one value may be set as the default value for the option
     *          For options of the 'quantity' type, the default may be set to the quantity
     *      - status The status of the package option value (optional, default 'active')
     *          Only 'select' and 'radio' option types may be 'inactive'
     *      - min The minimum value if type is 'quantity'
     *      - max The maximum value if type is 'quantity', null for unlimited quantity
     *      - step The step value if type is 'quantity'
     *      - pricing A numerically indexed array of pricing info including:
     *          - term The term as an integer 1-65535 (optional, default 1)
     *          - period The period, 'day', 'week', 'month', 'year', 'onetime' (optional, default 'month')
     *          - price The price of this term (optional, default 0.00)
     *          - setup_fee The setup fee for this package (optional, default 0.00)
     *          - currency The ISO 4217 currency code for this pricing (optional, default USD)
     *  - groups An array of package option group IDs that the option belongs to (optional)
     * @return int The package option ID, void on error
     */
    public function add(array $vars)
    {
        // Remove any pricing cancel fee. One cannot be set
        $vars = $this->removeOptionVars($vars);
        // Format option values
        $vars['values'] = $this->formatValues($vars);

        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $in_transaction = $this->Record->getConnection()->inTransaction();
            if (!$in_transaction) {
                $this->Record->begin();
            }

            // Add the package option
            $fields = ['company_id', 'label', 'name', 'description', 'type', 'addable', 'editable', 'hidden'];
            $this->Record->insert('package_options', $vars, $fields);
            $option_id = $this->Record->lastInsertId();

            // Add package option values and pricing
            $this->addOptionValues(
                $option_id,
                $vars['company_id'],
                (isset($vars['values']) ? $vars['values'] : []),
                $vars['type']
            );

            // Assign package option groups
            $this->addOptionGroups($option_id, (isset($vars['groups']) ? $vars['groups'] : []));

            if (!$in_transaction) {
                $this->Record->commit();
            }

            return $option_id;
        }
    }

    /**
     * Updates a package option
     *
     * @param int $option_id The ID of the package option to update
     * @param array $vars An array of package option info including:
     *
     *  - label The label displayed for this option
     *  - name The field name for this option
     *  - type The field type for this option, one of:
     *      - select
     *      - checkbox
     *      - radio
     *      - quantity
     *      - text
     *      - textarea
     *      - password
     *  - addable 1 if the option is addable by a client
     *  - editable 1 if the option is editable by a client
     *  - hidden 1 if the option is hidden
     *  - values A numerically indexed array of value info including:
     *      - id The ID of the package option value to update. If the ID is
     *          not given, the option value will be added. (optional, required for edit)
     *      - name The name of the package option value (display name).
     *          If the 'name' is empty or not given, the option value will be deleted
     *      - value The value of the package option (optional, default null)
     *      - default 1 or 0, whether or not this value is the default value (optional, default 0)
     *          Only one value may be set as the default value for the option.
     *          For options of the 'quantity' type, the default may be set to the quantity
     *      - status The status of the package option value (optional, default 'active')
     *          Only 'select' and 'radio' option types may be 'inactive'
     *      - min The minimum value if type is 'quantity'
     *      - max The maximum value if type is 'quantity', null for unlimited quantity
     *      - step The step value if type is 'quantity'
     *      - pricing A numerically indexed array of pricing info including:
     *          - id The package option pricing ID to update. If the ID is
     *              not given, the pricing will be added. (optional, required for edit)
     *          - term The term as an integer 1-65535 (optional, default 1).
     *              If the term is not given along with an ID, the pricing will be deleted
     *          - period The period, 'day', 'week', 'month', 'year', 'onetime' (optional, default 'month')
     *          - price The price of this term (optional, default 0.00)
     *          - setup_fee The setup fee for this package (optional, default 0.00)
     *          - currency The ISO 4217 currency code for this pricing (optional, default USD)
     *  - groups An array of package option group IDs that the option belongs to (optional)
     * @return int The package option ID, void on error
     */
    public function edit($option_id, array $vars)
    {
        // Remove any pricing cancel fee. One cannot be set
        $vars = $this->removeOptionVars($vars);
        $vars['option_id'] = $option_id;
        // Format option values
        $vars['values'] = $this->formatValues($vars);

        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            // Fetch the option to set pricing based on its company ID
            $option = $this->get($option_id);
            $vars['company_id'] = $option->company_id;

            // Determine option groups
            $groups = (isset($vars['groups']) ? (array) $vars['groups'] : []);
            $current_groups = $this->getGroups($option_id);
            $remove_groups = [];
            foreach ($current_groups as $group) {
                if (!in_array($group->id, $groups)) {
                    $remove_groups[] = $group->id;
                }
            }

            $in_transaction = $this->Record->getConnection()->inTransaction();
            if (!$in_transaction) {
                $this->Record->begin();
            }

            // Add the package option
            $fields = ['label', 'name', 'description', 'type', 'addable', 'editable', 'hidden'];
            $this->Record->where('id', '=', $option_id)->update('package_options', $vars, $fields);

            // Add/update/delete package option values and pricing
            $this->addOptionValues(
                $option_id,
                $vars['company_id'],
                (isset($vars['values']) ? $vars['values'] : []),
                $vars['type']
            );

            // Assign package option groups
            $this->addOptionGroups($option_id, $groups);

            // Remove package option groups no longer assigned
            if (!empty($remove_groups)) {
                $this->removeFromGroup($option_id, $remove_groups);
            }

            if (!$in_transaction) {
                $this->Record->commit();
            }

            return $option_id;
        }
    }

    /**
     * Permanently removes a package option from the system
     *
     * @param int $option_id The package option ID to delete
     */
    public function delete($option_id)
    {
        // Delete the package option, its values, and pricing
        $this->Record->from('package_options')
            ->leftJoin(
                'package_option_values',
                'package_options.id',
                '=',
                'package_option_values.option_id',
                false
            )
            ->leftJoin(
                'package_option_pricing',
                'package_option_pricing.option_value_id',
                '=',
                'package_option_values.id',
                false
            )
            ->leftJoin('pricings', 'pricings.id', '=', 'package_option_pricing.pricing_id', false)
            ->leftJoin('package_option_group', 'package_option_group.option_id', '=', 'package_options.id', false)
            ->leftJoin(
                'package_option_conditions',
                'package_option_conditions.trigger_option_id',
                '=',
                'package_options.id',
                false
            )
            ->leftJoin(
                'package_option_condition_sets',
                'package_option_condition_sets.option_id',
                '=',
                'package_options.id',
                false
            )
            ->where('package_options.id', '=', $option_id)
            ->delete([
                'package_options.*', 'package_option_group.*',
                'package_option_values.*', 'package_option_pricing.*',
                'pricings.*', 'package_option_conditions.*',
                'package_option_condition_sets.*'
            ]);
    }

    /**
     * Deletes all package option values and associated pricing for the given package option
     *
     * @param int $option_id The ID of the package option whose values and pricing to delete
     * @param int $value_id The ID of the package option value whose value and pricing to delete (optional)
     */
    public function deleteOptionValues($option_id, $value_id = null)
    {
        $this->Record->from('package_option_values')
            ->leftJoin(
                'package_option_pricing',
                'package_option_pricing.option_value_id',
                '=',
                'package_option_values.id',
                false
            )
            ->leftJoin('pricings', 'pricings.id', '=', 'package_option_pricing.pricing_id', false)
            ->where('package_option_values.option_id', '=', $option_id);

        if ($value_id) {
            $this->Record->where('package_option_values.id', '=', $value_id);
        }

        $this->Record->delete(['package_option_values.*', 'package_option_pricing.*', 'pricings.*']);
    }

    /**
     * Deletes a single package option pricing
     *
     * @param int $pricing_id The package option pricing ID to delete
     */
    private function deleteOptionPricing($pricing_id)
    {
        $this->Record->from('package_option_pricing')->
            leftJoin('pricings', 'pricings.id', '=', 'package_option_pricing.pricing_id', false)->
            where('package_option_pricing.id', '=', $pricing_id)->
            delete(['package_option_pricing.*', 'pricings.*']);
    }

    /**
     * Removes the given package option from the given package option groups
     *
     * @param int $option_id The ID of the package option
     * @param array $option_groups A numerically-indexed array of package
     *  option group IDs of the package option groups that this option should no longer be assigned to
     */
    public function removeFromGroup($option_id, array $option_groups)
    {
        $this->Record->from('package_option_group')->
            where('option_id', '=', $option_id)->
            where('option_group_id', 'in', $option_groups)->
            delete(['package_option_group.*']);
    }

    /**
     * Save the package options values for the given option in the provided order
     *
     * @param int $option_id The ID of the option to order values for
     * @param array $value_ids A numerically indexed array of value IDs
     */
    public function orderValues($option_id, array $value_ids)
    {
        for ($i = 0, $total = count($value_ids); $i < $total; $i++) {
            $this->Record->where('id', '=', $value_ids[$i])->
                where('option_id', '=', $option_id)->
                update('package_option_values', ['order' => $i]);
        }
    }

    /**
     * Determines whether an option value is editable
     * @see PackageOptions::add
     * @see PackageOptions::edit
     *
     * @param int $value_id The ID of the package option value
     * @return bool True if the package option is editable, otherwise false
     */
    private function canEditValue($value_id)
    {
        // An option is editable if an option value ID is given
        return !empty($value_id);
    }

    /**
     * Determines whether an option value is deletable
     * @see PackageOptions::add
     * @see PackageOptions::edit
     *
     * @param int $value_id The ID of the package option value
     * @param null|string $name The name of the package option value
     * @param string $option_type The package option type
     * @return bool True if the package option value is deletable, otherwise false
     */
    private function canDeleteValue($value_id, $name, $option_type)
    {
        // The value can be deleted if there is no name given,
        // unless it is a type (i.e. text/textarea/password) that is blank
        return (!empty($value_id) && empty($name) && !in_array($option_type, ['text', 'textarea', 'password']));
    }

    /**
     * Determines whether an option value pricing is deletable
     * @see PackageOptions::add
     * @see PackageOptions::edit
     *
     * @param array $pricing An array of pricing for an option value
     *
     *  - id The option pricing ID
     *  - term The option pricing term (optional)
     * @return bool True if the option value pricing is deletable, otherwise false
     */
    private function canDeleteValuePrice(array $pricing)
    {
        // The pricing can be deleted if no term is given (not set)
        return (!empty($pricing['id']) && !isset($pricing['term']));
    }

    /**
     * Adds/updates/deletes package option values (and subsequent pricing) for a package option
     *
     * @param int $option_id The ID of the package option to add the values to
     * @param int $company_id The ID of the company the groups must belong to
     * @param array $values A list of option values and pricing
     * @param string $type The option type
     * @see PackageOptions::add(), PackageOptions::edit()
     */
    private function addOptionValues($option_id, $company_id, array $values, $type)
    {
        $num_values = count($values);
        $order = 0;
        for ($i = 0; $i < $num_values; $i++) {
            // Delete the value if no name is given, unless it is a type (i.e. text/textarea/password) that is blank
            if ($this->canDeleteValue((isset($values[$i]['id']) ? $values[$i]['id'] : null), (isset($values[$i]['name']) ? $values[$i]['name'] : null), $type)) {
                $this->deleteOptionValues($option_id, $values[$i]['id']);
                continue;
            }

            // Add the package option value
            $fields = ['option_id', 'name', 'value', 'default', 'status', 'order', 'min', 'max', 'step'];

            $values[$i]['option_id'] = $option_id;
            $values[$i]['order'] = $order;

            // Add or update the package option value
            if ($this->canEditValue((isset($values[$i]['id']) ? $values[$i]['id'] : null))) {
                $this->Record->where('id', '=', $values[$i]['id'])
                    ->update('package_option_values', $values[$i], $fields);
                $value_id = $values[$i]['id'];
            } else {
                $this->Record->insert('package_option_values', $values[$i], $fields);
                $value_id = $this->Record->lastInsertId();
            }

            // Add/update/delete package option pricing
            if ($value_id) {
                $this->addOptionPricing(
                    $value_id,
                    $company_id,
                    (isset($values[$i]['pricing']) ? $values[$i]['pricing'] : [])
                );
            }

            $order++;
        }
    }

    /**
     * Adds/updates/deletes package option pricing for a specific package option value
     *
     * @param int $option_value_id The ID of the package option value to add pricing to
     * @param int $company_id The ID of the company the groups must belong to
     * @param array $pricing A list of pricing to add to the option value
     * @see PackageOptions::add(), PackageOptions::edit(), PackageOptions::addOptionValues()
     */
    private function addOptionPricing($option_value_id, $company_id, array $pricing)
    {
        if (!isset($this->Pricings)) {
            Loader::loadModels($this, ['Pricings']);
        }

        // Add each price
        foreach ($pricing as &$price) {
            // Delete the pricing if no term is given (not set)
            if ($this->canDeleteValuePrice($price)) {
                $this->deleteOptionPricing($price['id']);
                continue;
            }

            // Update the package option pricing if an ID is given
            if (!empty($price['id'])) {
                // Fetch the pricing ID to update and update it
                if (($pricing_info = $this->getValuePricingById($price['id']))) {
                    $this->Pricings->edit($pricing_info->pricing_id, $price);
                    continue;
                }
            }

            // Add the price
            $price['company_id'] = $company_id;
            $this->Pricings->add($price);
            $pricing_id = $this->Record->lastInsertId();

            // Associate the price as a package option price
            if ($pricing_id) {
                $this->Record->insert(
                    'package_option_pricing',
                    ['option_value_id' => $option_value_id, 'pricing_id' => $pricing_id]
                );
            }
        }
    }

    /**
     * Associates the given package option with all of the given package option groups
     *
     * @param int $option_id The ID of the package option
     * @param array $option_groups A list of package option group IDs to associate with this option
     * @see PackageOptions::add(), PackageOptions::edit()
     */
    private function addOptionGroups($option_id, array $option_groups)
    {
        // Associate the option with each of the option groups
        foreach ($option_groups as $option_group) {
            // Get max order within the selected group
            $option_order = $this->Record->select(['MAX(order)' => 'order'])->
                from('package_option_group')->
                where('option_group_id', '=', $option_group)->
                fetch();
            $order = 0;
            if ($option_order) {
                $order = $option_order->order + 1;
            }

            $vars = ['option_id' => $option_id, 'option_group_id' => $option_group, 'order' => $order];
            $this->Record->duplicate('order', '=', 'order', false)->insert('package_option_group', $vars);
        }
    }

    /**
     * Retrieves a list of package option types and their language definitions
     *
     * @return array A key/value list of types and their language
     */
    public function getTypes()
    {
        return [
            'checkbox' => $this->_('PackageOptions.gettypes.checkbox'),
            'radio' => $this->_('PackageOptions.gettypes.radio'),
            'select' => $this->_('PackageOptions.gettypes.select'),
            'quantity' => $this->_('PackageOptions.gettypes.quantity'),
            'text' => $this->_('PackageOptions.gettypes.text'),
            'textarea' => $this->_('PackageOptions.gettypes.textarea'),
            'password' => $this->_('PackageOptions.gettypes.password')
        ];
    }

    /**
     * Retrieves a list of package option value statuses and their language definitions
     *
     * @return array A key/value list of statuses and their language
     */
    public function getValueStatuses()
    {
        return [
            'active' => $this->_('PackageOptions.getvaluestatuses.active'),
            'inactive' => $this->_('PackageOptions.getvaluestatuses.inactive')
        ];
    }

    /**
     * Retrieves a list of package pricing periods
     *
     * @param bool $plural True to return language for plural periods, false for singular
     * @return array Key=>value pairs of package pricing periods
     */
    public function getPricingPeriods($plural = false)
    {
        if (!isset($this->Pricings)) {
            Loader::loadModels($this, ['Pricings']);
        }
        return $this->Pricings->getPeriods($plural);
    }

    /**
     * Fetch pricing for the given package option value
     *
     * @param int $value_id The ID of the value to fetch
     * @return array An array of stdClass object each representing a pricing
     */
    public function getValuePricing($value_id)
    {
        $fields = ['package_option_pricing.id', 'package_option_pricing.pricing_id',
            'package_option_pricing.option_value_id', 'pricings.term',
            'pricings.period', 'pricings.price', 'pricings.price_renews', 'pricings.price_transfer',
            'pricings.setup_fee', 'pricings.cancel_fee', 'pricings.currency', 'currencies.precision'
        ];

        $pricing = $this->Record->select($fields)->from('package_option_pricing')->
            innerJoin('pricings', 'pricings.id', '=', 'package_option_pricing.pricing_id', false)->
            on('currencies.company_id', '=', 'pricings.company_id', false)->
            innerJoin('currencies', 'currencies.code', '=', 'pricings.currency', false)->
            where('package_option_pricing.option_value_id', '=', $value_id)->fetchAll();

        // Check if we need to increase the precision of the current price to avoid losing decimals
        foreach ($pricing as &$price) {
            if (substr((string)$price->price, -2) !== '00') {
                $price->precision = 4;
            }
        }

        return $pricing;
    }

    /**
     * Fetch pricing for the given package option pricing ID
     *
     * @param int $pricing_id The ID of option pricing
     * @return mixed A stdClass object representing the value pricing, false otherwise
     */
    public function getValuePricingById($pricing_id)
    {
        $fields = ['package_option_pricing.id', 'package_option_pricing.pricing_id',
            'package_option_pricing.option_value_id', 'pricings.term',
            'pricings.period', 'pricings.price', 'pricings.price_renews', 'pricings.price_transfer',
            'pricings.setup_fee', 'pricings.cancel_fee', 'pricings.currency', 'currencies.precision'
        ];

        $pricing = $this->Record->select($fields)->from('package_option_pricing')->
            innerJoin('pricings', 'pricings.id', '=', 'package_option_pricing.pricing_id', false)->
            on('currencies.company_id', '=', 'pricings.company_id', false)->
            innerJoin('currencies', 'currencies.code', '=', 'pricings.currency', false)->
            where('package_option_pricing.id', '=', $pricing_id)->fetch();

        // Check if we need to increase the precision of the current price to avoid losing decimals
        if (substr((string)$pricing->price, -2) !== '00') {
            $pricing->precision = 4;
        }

        return $pricing;
    }

    /**
     * Fetches the value based on the given option_id and value
     *
     * @param int $option_id The ID of the option to fetch the value for
     * @param string $value The value to fetch
     * @return mixed A stdClass object representing the value, false if not such value exists
     */
    public function getValue($option_id, $value)
    {
        $option = $this->getOption($option_id);

        $this->Record->select()
            ->from('package_option_values')
            ->where('option_id', '=', $option_id);

        // Option values must match unless they are quantity (i.e. null value) or the text, textarea, or password type
        if (!$option || !in_array($option->type, ['text', 'textarea', 'password'])) {
            $this->Record->open()
                ->where('value', '=', $value)
                ->orWhere('value', '=', null)
            ->close();
        }

        return $this->Record->fetch();
    }

    /**
     * Retrieves the package option value by ID
     *
     * @param int $option_value_id The ID of the package option value to fetch
     * @return false|stdClass An stdClass object representing the package option value if found, otherwise false
     */
    private function getValueById($option_value_id)
    {
        return $this->Record->select(['package_option_values.*', 'package_options.type' => 'option_type'])
            ->from('package_option_values')
            ->innerJoin('package_options', 'package_options.id', '=', 'package_option_values.option_id', false)
            ->where('package_option_values.id', '=', $option_value_id)
            ->fetch();
    }

    /**
     * Fetches pricing for the given option with the given value
     *
     * @param int $value_id The ID of the option to fetch
     * @param int $term The term to fetch fields for
     * @param string $period The period to fetch fields for
     * @param string $currency The currency to fetch fields for
     * @param string $convert_currency The currency to convert to (optional)
     * @return mixed A stdClass object representing the value pricing, false if no such pricing exists
     */
    public function getValuePrice($value_id, $term, $period, $currency, $convert_currency = null)
    {
        if (!isset($this->Currencies)) {
            Loader::loadHelpers($this, ['CurrencyFormat']);
            $this->Currencies = $this->CurrencyFormat->Currencies;
        }

        $pricing = $this->getValuePricing($value_id);
        if (!$pricing) {
            return false;
        }

        $match = false;
        foreach ($pricing as $price) {
            if ($price->term != $term || $price->period != $period || $price->currency != $currency) {
                continue;
            }
            $match = true;

            // Handle currency conversion here if given
            if ($convert_currency && $convert_currency != $currency) {
                $price->price = $this->Currencies->convert(
                    $price->price,
                    $currency,
                    $convert_currency,
                    Configure::get('Blesta.company_id')
                );
                $price->price_renews = $this->Currencies->convert(
                    $price->price_renews,
                    $currency,
                    $convert_currency,
                    Configure::get('Blesta.company_id')
                );
                $price->price_transfer = $this->Currencies->convert(
                    $price->price_transfer,
                    $currency,
                    $convert_currency,
                    Configure::get('Blesta.company_id')
                );
                $price->setup_fee = $this->Currencies->convert(
                    $price->setup_fee,
                    $currency,
                    $convert_currency,
                    Configure::get('Blesta.company_id')
                );
                $price->cancel_fee = $this->Currencies->convert(
                    $price->cancel_fee,
                    $currency,
                    $convert_currency,
                    Configure::get('Blesta.company_id')
                );
                $price->currency = $convert_currency;
            }
            break;
        }
        if ($match) {
            return $price;
        }
        return false;
    }

    /**
     * Fetches prorated pricing for the given option with the given value
     *
     * @param int $value_id The ID of the option to fetch
     * @param string $start_date The start date to prorate the price from
     * @param int $term The term to fetch fields for
     * @param string $period The period to fetch fields for
     * @param int $pro_rata_day The day of the month to prorate to
     * @param string $currency The currency to fetch fields for
     * @param string $convert_currency The currency to convert to (optional)
     * @return mixed A stdClass object representing the value pricing, false if no such pricing exists
     */
    public function getValueProrateAmount(
        $value_id,
        $start_date,
        $term,
        $period,
        $pro_rata_day,
        $currency,
        $convert_currency = null
    ) {
        $price = $this->getValuePrice($value_id, $term, $period, $currency, $convert_currency);

        if (!isset($this->Packages)) {
            Loader::loadModels($this, ['Packages']);
        }

        if ($price && isset($price->price)) {
            $price->price = $this->Packages->getProratePrice($price->price, $start_date, $term, $period, $pro_rata_day);
        }

        return $price;
    }

    /**
     * Retrieves all package options for a package given its term, period, and currency
     *
     * @param int $package_id The ID of the package
     * @param int $term The package term
     * @param string $period The package period
     * @param string $currency The pricing currency
     * @param string $convert_currency The currency to convert to (optional, default null)
     * @param array $options An array of key/value pairs for filtering options (optional, default null). May include:
     *
     *  - addable Set to 1 to only include options that are addable by
     *      clients; 0 to only include options that are NOT addable by
     *      clients; otherwise every option is included
     *  - editable Set to 1 to only include options that are editable by
     *      clients; 0 to only include options that are NOT editable by
     *      clients; otherwise every option is included
     *  - allow An array of option IDs to include (i.e. white-list). An
     *      empty array would return no options. Not setting this 'option_ids' key will allow any option
     *  - disallow An array of option IDs not to include (i.e. black-list). An empty array would allow all options.
     *  - configoptions An array of key/value pairs currently in use where
     *      each key is the package option ID and each value is the option value
     * @return array An array of package options including their values and pricing
     */
    public function getAllByPackageId(
        $package_id,
        $term,
        $period,
        $currency,
        $convert_currency = null,
        array $options = null
    ) {
        // Rename options
        $filters = (array) $options;
        $white_list = (array_key_exists('allow', $filters));
        $black_list = (array_key_exists('disallow', $filters));

        $options = $this->getByPackageId($package_id);

        foreach ($options as $i => &$option) {
            // Skip any white-list options not given or any black-list options given
            if (($white_list && !in_array($option->id, $filters['allow'])) ||
                ($black_list && in_array($option->id, $filters['disallow']))) {
                unset($options[$i]);
                continue;
            }

            // Remove addable/editable options that don't match the filtering criteria
            if ((array_key_exists('addable', $filters)
                    && (($filters['addable'] == '1' && $option->addable != '1')
                        || ($filters['addable'] == '0' && $option->addable != '0')
                    )
                ) || (array_key_exists('editable', $filters)
                    && (($filters['editable'] == '1' && $option->editable != '1')
                        || ($filters['editable'] == '0' && $option->editable != '0')
                    )
                )
            ) {
                unset($options[$i]);
                continue;
            }

            // Ensure the value is active and has pricing
            foreach ($option->values as $j => &$value) {
                // The option must be inactive to remove, but must also not be a selected value in the configoption list
                // because that would indicate it is an option we should include (e.g. an existing value to maintain)
                if ($value->status === 'inactive'
                    && (!array_key_exists('configoptions', $filters)
                        || !isset($filters['configoptions'][$option->id])
                        || $filters['configoptions'][$option->id] !== $value->value
                    )
                ) {
                    unset($option->values[$j]);
                    continue;
                }

                // Ensure there is a price for the value
                $value_price = $this->getValuePrice($value->id, $term, $period, $currency, $convert_currency);
                if ($value_price) {
                    $value->pricing = [$value_price];
                } else {
                    unset($option->values[$j]);
                }
            }
            unset($value);
            $option->values = array_values($option->values);
            if (empty($option->values)) {
                unset($options[$i]);
            }
        }

        return array_values($options);
    }

    /**
     * Get option fields
     *
     * @param int $package_id The ID of the package to fetch fields for
     * @param int $term The term to fetch fields for
     * @param string $period The period to fetch fields for
     * @param string $currency The currency to fetch fields for
     * @param stdClass $vars An stdClass object representing a set of post fields (optional, default null)
     * @param string $convert_currency The currency to convert to (optional, default null)
     * @param array $options An array of key/value pairs for filtering options (optional, default null). May include:
     *
     *  - addable Set to 1 to only include options that are addable by
     *      clients; 0 to only include options that are NOT addable by
     *      clients; otherwise every option is included
     *  - editable Set to 1 to only include options that are editable by
     *      clients; 0 to only include options that are NOT editable by
     *      clients; otherwise every option is included
     *  - allow An array of option IDs to include (i.e. white-list). An
     *      empty array would return no fields. Not setting this 'option_ids' key will allow any option
     *  - disallow An array of option IDs not to include (i.e. black-list). An empty array would allow all options.
     *  - configoptions An array of key/value pairs currently in use where
     *      each key is the package option ID and each value is the option value
     *  - new Set to 1 if this is for a new package, or 0 if this is for an existing package (default 1)
     * @return ModuleFields A ModuleFields object, containg the fields to render
     */
    public function getFields(
        $package_id,
        $term,
        $period,
        $currency,
        $vars = null,
        $convert_currency = null,
        array $options = null
    ) {
        if (!class_exists('ModuleFields')) {
            Loader::load(COMPONENTDIR . 'modules' . DS . 'module_field.php');
            Loader::load(COMPONENTDIR . 'modules' . DS . 'module_fields.php');
        }
        Loader::loadHelpers($this, ['CurrencyFormat', 'TextParser']);
        Loader::loadModels($this, ['Packages']);
        $Markdown = $this->TextParser->create('markdown');
        $this->Currencies = $this->CurrencyFormat->Currencies;

        $fields = new ModuleFields();

        // Determine whether we are assuming these options are new
        $new = ($options !== null && isset($options['new']) && $options['new'] == '0' ? false : true);
        $upgrade = $options !== null && isset($options['upgrade']) && $options['upgrade'];
        $service_options = $options !== null && isset($options['configoptions']) ? $options['configoptions'] : [];

        $options = $this->getAllByPackageId($package_id, $term, $period, $currency, $convert_currency, $options);
        $package = $this->Packages->get($package_id);

        foreach ($options as $option) {
            $field_name = 'configoptions[' . $option->id . ']';
            $field_value = isset($vars->configoptions[$option->id]) ? $vars->configoptions[$option->id] : null;
            $use_renewal_price = ($upgrade && $package->upgrades_use_renewal)
                || (!$upgrade
                    && isset($vars->service_id)
                    && isset($vars->configoptions)
                    && array_key_exists($option->id, $service_options)
                );

            switch ($option->type) {
                case 'checkbox':
                    $value = $option->values[0];
                    $pricing = $value->pricing[0];

                    // Use renewal price when adding/editing options for an existing service
                    $include_renewal_price = ($use_renewal_price && isset($pricing->price_renews));
                    $display_renewal_price = (!$include_renewal_price
                        && $pricing->period != 'onetime'
                        && isset($pricing->price_renews)
                        && $pricing->price_renews != $pricing->price
                    );
                    $price = $include_renewal_price ? $pricing->price_renews : $pricing->price;
                    $id = 'configoption_' . $option->id . '_' . $value->id;

                    // Set the default value, but only if the checkbox is being added as new
                    if ($field_value === null && $value->default == '1' && $new) {
                        $field_value = $value->value;
                    }

                    $label_term = 'PackageOptions.getfields.label_checkbox';
                    if ($pricing->setup_fee > 0) {
                        $field_label_name = Language::_(
                            $label_term . '_setup' . ($display_renewal_price ? '_recurring' : ''),
                            true,
                            $value->name,
                            $this->CurrencyFormat->format($price, $pricing->currency, ['decimals' => $pricing->precision]),
                            $this->CurrencyFormat->format($pricing->setup_fee, $pricing->currency, ['decimals' => $pricing->precision]),
                            $this->CurrencyFormat->format($pricing->price_renews, $pricing->currency, ['decimals' => $pricing->precision])
                        );
                    } else {
                        $field_label_name = Language::_(
                            $label_term . ($display_renewal_price ? '_recurring' : ''),
                            true,
                            $value->name,
                            $this->CurrencyFormat->format($price, $pricing->currency, ['decimals' => $pricing->precision]),
                            $this->CurrencyFormat->format($pricing->price_renews, $pricing->currency, ['decimals' => $pricing->precision])
                        );
                    }

                    // Create label
                    $field = $fields->label($option->label);
                    // Create field label
                    $field_label = $fields->label($field_label_name, $id);

                    // Set the package option description tooltip
                    if (!empty($option->description)) {
                        $tooltip = $fields->tooltip($Markdown->text($option->description));
                        $field->attach($tooltip);
                    }

                    // Create field and attach to label
                    $field->attach(
                        $fields->fieldCheckbox(
                            $field_name,
                            $value->value,
                            $field_value === $value->value,
                            ['id' => $id],
                            $field_label
                        )
                    );
                    // Set the label as a field
                    $fields->setField($field);
                    unset($value);
                    break;
                case 'radio':
                    // Create label
                    $field = $fields->label($option->label);

                    // Set the package option description tooltip
                    if (!empty($option->description)) {
                        $tooltip = $fields->tooltip($Markdown->text($option->description));
                        $field->attach($tooltip);
                    }

                    foreach ($option->values as $value) {
                        $pricing = $value->pricing[0];

                        // Use renewal price when adding/editing options for an existing service
                        $include_renewal_price = ($use_renewal_price && isset($pricing->price_renews));
                        $display_renewal_price = (!$include_renewal_price
                            && $pricing->period != 'onetime'
                            && isset($pricing->price_renews)
                            && $pricing->price_renews != $pricing->price
                        );
                        $price = $include_renewal_price ? $pricing->price_renews : $pricing->price;
                        $id = 'configoption_' . $option->id . '_' . $value->id;

                        // Set the default value
                        if ($field_value === null && $value->default == '1') {
                            $field_value = $value->value;
                        }

                        $label_term = 'PackageOptions.getfields.label_radio';
                        if ($pricing->setup_fee > 0) {
                            $field_label_name = Language::_(
                                $label_term . '_setup' . ($display_renewal_price ? '_recurring' : ''),
                                true,
                                $value->name,
                                $this->CurrencyFormat->format($price, $pricing->currency, ['decimals' => $pricing->precision]),
                                $this->CurrencyFormat->format($pricing->setup_fee, $pricing->currency, ['decimals' => $pricing->precision]),
                                $this->CurrencyFormat->format($pricing->price_renews, $pricing->currency, ['decimals' => $pricing->precision])
                            );
                        } else {
                            $field_label_name = Language::_(
                                $label_term . ($display_renewal_price ? '_recurring' : ''),
                                true,
                                $value->name,
                                $this->CurrencyFormat->format($price, $pricing->currency, ['decimals' => $pricing->precision]),
                                $this->CurrencyFormat->format($pricing->price_renews, $pricing->currency, ['decimals' => $pricing->precision])
                            );
                        }

                        // Create field label
                        $field_label = $fields->label($field_label_name, $id);

                        // Create field and attach to label
                        $field->attach(
                            $fields->fieldRadio(
                                $field_name,
                                $value->value,
                                $field_value === $value->value,
                                ['id' => $id],
                                $field_label
                            )
                        );
                    }
                    unset($value);

                    // Set the label as a field
                    $fields->setField($field);

                    break;
                case 'select':
                    // Create label
                    $id = 'configoption_' . $option->id;
                    $field = $fields->label($option->label, $id);

                    // Set the package option description tooltip
                    if (!empty($option->description)) {
                        $tooltip = $fields->tooltip($Markdown->text($option->description));
                        $field->attach($tooltip);
                    }

                    $option_values = [];
                    foreach ($option->values as $value) {
                        $pricing = $value->pricing[0];

                        // Use renewal price when adding/editing options for an existing service
                        $include_renewal_price = ($use_renewal_price && isset($pricing->price_renews));
                        $display_renewal_price = (!$include_renewal_price
                            && $pricing->period != 'onetime'
                            && isset($pricing->price_renews)
                            && $pricing->price_renews != $pricing->price
                        );
                        $price = $include_renewal_price ? $pricing->price_renews : $pricing->price;

                        // Set the default value
                        if ($field_value === null && $value->default == '1') {
                            $field_value = $value->value;
                        }

                        $label_term = 'PackageOptions.getfields.label_select';
                        if ($pricing->setup_fee > 0) {
                            $field_label_name = Language::_(
                                $label_term . '_setup' . ($display_renewal_price ? '_recurring' : ''),
                                true,
                                $value->name,
                                $this->CurrencyFormat->format($price, $pricing->currency, ['decimals' => $pricing->precision]),
                                $this->CurrencyFormat->format($pricing->setup_fee, $pricing->currency, ['decimals' => $pricing->precision]),
                                $this->CurrencyFormat->format($pricing->price_renews, $pricing->currency, ['decimals' => $pricing->precision])
                            );
                        } else {
                            $field_label_name = Language::_(
                                $label_term . ($display_renewal_price ? '_recurring' : ''),
                                true,
                                $value->name,
                                $this->CurrencyFormat->format($price, $pricing->currency, ['decimals' => $pricing->precision]),
                                $this->CurrencyFormat->format($pricing->price_renews, $pricing->currency, ['decimals' => $pricing->precision])
                            );
                        }

                        $option_values[$value->value] = $field_label_name;
                    }
                    unset($value);
                    $field->attach($fields->fieldSelect($field_name, $option_values, $field_value, ['id' => $id]));

                    // Set the label as a field
                    $fields->setField($field);
                    break;
                case 'quantity':
                    $value = $option->values[0];
                    $pricing = $value->pricing[0];

                    // Use renewal price when adding/editing options for an existing service
                    $include_renewal_price = ($use_renewal_price && isset($pricing->price_renews));
                    $display_renewal_price = (!$include_renewal_price
                        && $pricing->period != 'onetime'
                        && isset($pricing->price_renews)
                        && $pricing->price_renews != $pricing->price
                    );
                    $price = $include_renewal_price ? $pricing->price_renews : $pricing->price;
                    $id = 'configoption_' . $option->id . '_' . $value->id;

                    // Set default value
                    if ($field_value === null && $value->default != '0') {
                        $field_value = $value->default;
                    } elseif ($field_value == '') {
                        $field_value = $value->min;
                    }

                    $label_term = 'PackageOptions.getfields.label_quantity';
                    if ($pricing->setup_fee > 0) {
                        $field_label_name = Language::_(
                            $label_term . '_setup' . ($display_renewal_price ? '_recurring' : ''),
                            true,
                            $value->name,
                            $this->CurrencyFormat->format($price, $pricing->currency, ['decimals' => $pricing->precision]),
                            $this->CurrencyFormat->format($pricing->setup_fee, $pricing->currency, ['decimals' => $pricing->precision]),
                            $this->CurrencyFormat->format($pricing->price_renews, $pricing->currency, ['decimals' => $pricing->precision])
                        );
                    } else {
                        $field_label_name = Language::_(
                            $label_term . ($display_renewal_price ? '_recurring' : ''),
                            true,
                            $value->name,
                            $this->CurrencyFormat->format($price, $pricing->currency, ['decimals' => $pricing->precision]),
                            $this->CurrencyFormat->format($pricing->price_renews, $pricing->currency, ['decimals' => $pricing->precision])
                        );
                    }

                    // Create label
                    $field = $fields->label($option->label, $id);
                    // Create field label
                    $field_label = $fields->label($field_label_name);

                    // Set the package option description tooltip
                    if (!empty($option->description)) {
                        $tooltip = $fields->tooltip($Markdown->text($option->description));
                        $field->attach($tooltip);
                    }

                    // Create field and attach to label
                    $field->attach(
                        $fields->fieldText(
                            $field_name,
                            (int) $field_value,
                            [
                                'id' => $id,
                                'data-type' => 'quantity',
                                'data-min' => $value->min,
                                'data-max' => $value->max,
                                'data-step' => $value->step
                            ],
                            $field_label
                        )
                    );
                    // Set the label as a field
                    $fields->setField($field);
                    unset($value);
                    break;
                case 'text':
                    $value = $option->values[0];
                    $pricing = $value->pricing[0];

                    // Use renewal price when adding/editing options for an existing service
                    $include_renewal_price = ($use_renewal_price && isset($pricing->price_renews));
                    $display_renewal_price = (!$include_renewal_price
                        && $pricing->period != 'onetime'
                        && isset($pricing->price_renews)
                        && $pricing->price_renews != $pricing->price
                    );
                    $price = $include_renewal_price ? $pricing->price_renews : $pricing->price;
                    $show_price = (($price + $pricing->setup_fee) > 0) || $display_renewal_price;
                    $id = 'configoption_' . $option->id . '_' . $value->id;

                    $label_term = 'PackageOptions.getfields.label_text';
                    if ($pricing->setup_fee > 0) {
                        $field_label_name = Language::_(
                            $label_term . '_setup' . ($display_renewal_price ? '_recurring' : ''),
                            true,
                            $this->CurrencyFormat->format($price, $pricing->currency, ['decimals' => $pricing->precision]),
                            $this->CurrencyFormat->format($pricing->setup_fee, $pricing->currency, ['decimals' => $pricing->precision]),
                            $this->CurrencyFormat->format($pricing->price_renews, $pricing->currency, ['decimals' => $pricing->precision])
                        );
                    } else {
                        $field_label_name = Language::_(
                            $label_term . ($display_renewal_price ? '_recurring' : ''),
                            true,
                            $this->CurrencyFormat->format($price, $pricing->currency, ['decimals' => $pricing->precision]),
                            $this->CurrencyFormat->format($pricing->price_renews, $pricing->currency, ['decimals' => $pricing->precision])
                        );
                    }

                    // Create label
                    $field = $fields->label($option->label, $id);
                    // Create field label
                    $field_label = $fields->label($field_label_name);

                    // Set the package option description tooltip
                    if (!empty($option->description)) {
                        $tooltip = $fields->tooltip($Markdown->text($option->description));
                        $field->attach($tooltip);
                    }

                    // Create field and attach to label
                    $field->attach(
                        $fields->fieldText(
                            $field_name,
                            $field_value !== null ? $field_value : '',
                            ['id' => $id],
                            ($show_price ? $field_label : null)
                        )
                    );
                    // Set the label as a field
                    $fields->setField($field);
                    unset($value);
                    break;
                case 'password':
                    $value = $option->values[0];
                    $pricing = $value->pricing[0];

                    // Use renewal price when adding/editing options for an existing service
                    $include_renewal_price = ($use_renewal_price && isset($pricing->price_renews));
                    $display_renewal_price = (!$include_renewal_price
                        && $pricing->period != 'onetime'
                        && isset($pricing->price_renews)
                        && $pricing->price_renews != $pricing->price
                    );
                    $price = $include_renewal_price ? $pricing->price_renews : $pricing->price;
                    $show_price = (($price + $pricing->setup_fee) > 0) || $display_renewal_price;
                    $id = 'configoption_' . $option->id . '_' . $value->id;

                    $label_term = 'PackageOptions.getfields.label_password';
                    if ($pricing->setup_fee > 0) {
                        $field_label_name = Language::_(
                            $label_term . '_setup' . ($display_renewal_price ? '_recurring' : ''),
                            true,
                            $this->CurrencyFormat->format($price, $pricing->currency, ['decimals' => $pricing->precision]),
                            $this->CurrencyFormat->format($pricing->setup_fee, $pricing->currency, ['decimals' => $pricing->precision]),
                            $this->CurrencyFormat->format($pricing->price_renews, $pricing->currency, ['decimals' => $pricing->precision])
                        );
                    } else {
                        $field_label_name = Language::_(
                            $label_term . ($display_renewal_price ? '_recurring' : ''),
                            true,
                            $this->CurrencyFormat->format($price, $pricing->currency, ['decimals' => $pricing->precision]),
                            $this->CurrencyFormat->format($pricing->price_renews, $pricing->currency, ['decimals' => $pricing->precision])
                        );
                    }

                    // Create label
                    $field = $fields->label($option->label, $id);
                    // Create field label
                    $field_label = $fields->label($field_label_name);

                    // Set the package option description tooltip
                    if (!empty($option->description)) {
                        $tooltip = $fields->tooltip($Markdown->text($option->description));
                        $field->attach($tooltip);
                    }

                    // Create field and attach to label
                    $field->attach(
                        $fields->fieldPassword(
                            $field_name,
                            ['id' => $id, 'value' => ($field_value !== null ? $field_value : '')],
                            ($show_price ? $field_label : null)
                        )
                    );
                    // Set the label as a field
                    $fields->setField($field);
                    unset($value);
                    break;
                case 'textarea':
                    $value = $option->values[0];
                    $pricing = $value->pricing[0];

                    // Use renewal price when adding/editing options for an existing service
                    $include_renewal_price = ($use_renewal_price && isset($pricing->price_renews));
                    $display_renewal_price = (!$include_renewal_price
                        && $pricing->period != 'onetime'
                        && isset($pricing->price_renews)
                        && $pricing->price_renews != $pricing->price
                    );
                    $price = $include_renewal_price ? $pricing->price_renews : $pricing->price;
                    $show_price = (($price + $pricing->setup_fee) > 0) || $display_renewal_price;
                    $id = 'configoption_' . $option->id . '_' . $value->id;

                    $label_term = 'PackageOptions.getfields.label_textarea';
                    if ($pricing->setup_fee > 0) {
                        $field_label_name = Language::_(
                            $label_term . '_setup' . ($display_renewal_price ? '_recurring' : ''),
                            true,
                            $this->CurrencyFormat->format($price, $pricing->currency, ['decimals' => $pricing->precision]),
                            $this->CurrencyFormat->format($pricing->setup_fee, $pricing->currency, ['decimals' => $pricing->precision]),
                            $this->CurrencyFormat->format($pricing->price_renews, $pricing->currency, ['decimals' => $pricing->precision])
                        );
                    } else {
                        $field_label_name = Language::_(
                            $label_term . ($display_renewal_price ? '_recurring' : ''),
                            true,
                            $this->CurrencyFormat->format($price, $pricing->currency, ['decimals' => $pricing->precision]),
                            $this->CurrencyFormat->format($pricing->price_renews, $pricing->currency, ['decimals' => $pricing->precision])
                        );
                    }

                    // Create label
                    $field = $fields->label($option->label, $id);
                    // Create field label
                    $field_label = $fields->label($field_label_name);

                    // Set the package option description tooltip
                    if (!empty($option->description)) {
                        $tooltip = $fields->tooltip($Markdown->text($option->description));
                        $field->attach($tooltip);
                    }

                    // Create field and attach to label
                    $field->attach(
                        $fields->fieldTextarea(
                            $field_name,
                            ($field_value !== null ? $field_value : ''),
                            ['id' => $id],
                            ($show_price ? $field_label : null)
                        )
                    );
                    // Set the label as a field
                    $fields->setField($field);
                    unset($value);
                    break;
            }
        }

        return $fields;
    }

    /**
     * Formats the pricing term
     *
     * @param int $term The term length
     * @param string $period The period of this term
     * @return mixed The term formatted in accordance to the period, if possible
     */
    public function formatPricingTerm($term, $period)
    {
        if ($period == 'onetime') {
            return 0;
        }
        return $term;
    }

    /**
     * Formats options into key/value named pairs where each key is the
     * option field name from a key/value pair array where each key is the
     * option ID.
     *
     * @param array $options A key/value pair array where each key is the option ID
     */
    public function formatOptions(array $options)
    {
        $formatted_options = [];

        foreach ($options as $option_id => $value) {
            $option = $this->get($option_id);
            if ($option) {
                $formatted_options[$option->name] = $value;
            }
        }

        return $formatted_options;
    }

    /**
     * Formats options into configoption array elements where each key is the
     * option ID and each value is the option's selected value
     *
     * @param array $options An array of stdClass objects, each representing a service option and its value
     * @return array An array that contains configoptions and key/value pairs
     *  of option ID and the option's selected value
     */
    public function formatServiceOptions(array $options)
    {
        $data = [];
        foreach ($options as $option) {
            $data['configoptions'][$option->option_id] = $option->value !== null
                ? $option->value
                : $option->qty;
        }
        return $data;
    }

    /**
     * Validates that the term is valid for the period. That is, the term must be > 0
     * if the period is something other than "onetime".
     *
     * @param int $term The Term to validate
     * @param string $period The period to validate the term against
     * @return bool True if validated, false otherwise
     */
    public function validatePricingTerm($term, $period)
    {
        if ($period == 'onetime') {
            return true;
        }
        return $term > 0;
    }

    /**
     * Validates the pricing 'period' field type
     *
     * @param string $period The period type
     * @return bool True if validated, false otherwise
     */
    public function validatePricingPeriod($period)
    {
        $periods = $this->getPricingPeriods();

        if (isset($periods[$period])) {
            return true;
        }
        return false;
    }

    /**
     * Retrieves a list of rules for adding/editing package options
     *
     * @param array $vars A list of input vars used in validation
     * @param bool $edit True to fetch the edit rules, false for the add rules (optional, default false)
     * @return array A list of rules
     */
    private function getRules(array $vars, $edit = false)
    {
        // Retrieve the type from input
        $type = (isset($vars['type']) ? $vars['type'] : null);

        // Convert a value to an integer if not null
        $toInteger = function($value) {
            return ($value === null ? null : (int)$value);
        };

        $rules = [
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('PackageOptions.!error.company_id.exists')
                ]
            ],
            'label' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('PackageOptions.!error.label.empty')
                ],
                'length' => [
                    'rule' => ['maxLength', 128],
                    'message' => $this->_('PackageOptions.!error.label.length')
                ]
            ],
            'name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('PackageOptions.!error.name.empty')
                ],
                'length' => [
                    'rule' => ['maxLength', 128],
                    'message' => $this->_('PackageOptions.!error.name.length')
                ]
            ],
            'type' => [
                'valid' => [
                    'rule' => ['in_array', array_keys($this->getTypes())],
                    'message' => $this->_('PackageOptions.!error.type.valid')
                ]
            ],
            'values' => [
                'count' => [
                    'rule' => [[$this, 'validateOptionValueLimit'], $type],
                    'message' => $this->_('PackageOptions.!error.values.count')
                ],
                'select_value' => [
                    'rule' => [[$this, 'validateSelectTypeValues'], $type],
                    'message' => $this->_('PackageOptions.!error.values.select_value')
                ],
                'active_status' => [
                    'rule' => [
                        function($values, $type) {
                            // If the values are not given in the proper format, pass this rule
                            // A more appropriate rule will fail instead
                            if (!is_array($values)) {
                                return true;
                            }

                            // At least one package option value must be active (i.e. they cannot all be inactive)
                            // Not setting a status equates to it being active, unless it is to be deleted
                            foreach ($values as $value) {
                                // Skip option values that are set to be deleted because there must still be
                                // another value that is active on the option
                                $id = (isset($value['id']) ? $value['id'] : null);
                                $name = (isset($value['name']) ? $value['name'] : null);
                                if ($this->canDeleteValue($id, $name, $type)) {
                                    continue;
                                }

                                if ((isset($value['status']) ? $value['status'] : 'active') == 'active') {
                                    return true;
                                }
                            }

                            return false;
                        },
                        ['_linked' => 'type']
                    ],
                    'message' => $this->_('PackageOptions.!error.values.active_status')
                ],
                'single_default_value' => [
                    'rule' => function($values) {
                        // If the values are not given in the proper format, pass this rule
                        // A more appropriate rule will fail instead
                        if (!is_array($values)) {
                            return true;
                        }

                        // There may only be a max of one default value for the option
                        $total_defaults = 0;
                        foreach ($values as $value) {
                            if ((isset($value['default']) ? $value['default'] : '0') == '1') {
                                $total_defaults++;
                            }
                        }

                        return ($total_defaults <= 1);
                    },
                    'message' => $this->_('PackageOptions.!error.values.single_default_value')
                ],
                'unique' => [
                    'rule' => function($values) use ($type) {
                        // Only validate checkbox, radio, and select options
                        // since the others do not actually submit a value
                        return in_array($type, ['checkbox', 'radio', 'select'])
                            ? $this->validateUniqueValues($values)
                            : true;
                    },
                    'message' => $this->_('PackageOptions.!error.values.unique')
                ]
            ],
            'values[][name]' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => function($name) use ($type) {
                        // The name must be empty for text, textarea, and password
                        // types since it is unused in those cases
                        if (in_array($type, ['text', 'textarea', 'password'])) {
                            return $name === '';
                        } else {
                            return !empty($name);
                        }
                    },
                    'message' => $this->_('PackageOptions.!error.values[][name].empty')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 128],
                    'message' => $this->_('PackageOptions.!error.values[][name].length')
                ]
            ],
            'values[][value]' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 255],
                    'message' => $this->_('PackageOptions.!error.values[][value].length')
                ]
            ],
            'values[][status]' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', array_keys($this->getValueStatuses())],
                    'message' => $this->_('PackageOptions.!error.values[][status].valid')
                ]
            ],
            'values[][default]' => [
                'inactive' => [
                    'if_set' => true,
                    'rule' => [
                        function ($default, $status) {
                            // The default value may not be set to an inactive option value
                            if ($status == 'inactive' && $default != '0') {
                                return false;
                            }

                            return true;
                        },
                        ['_linked' => 'values[][status]']
                    ],
                    'message' => $this->_('PackageOptions.!error.values[][default].inactive')
                ],
                'qty_valid' => [
                    'if_set' => true,
                    'rule' => [
                        function ($default, $type, $min, $max, $step) {
                            // Pass this rule if the default value is not a valid number or a quantity type
                            // A separate rule will handle that validation
                            if ($type != 'quantity' || !is_numeric($default) || empty($step)) {
                                return true;
                            }

                            // The default quantity value must be valid according to the min/max/step
                            if ($default < $min
                                || ($max !== null && $default > $max)
                                || (($default - $min) % $step !== 0)
                            ) {
                                return false;
                            }

                            return true;
                        },
                        ['_linked' => 'type'],
                        ['_linked' => 'values[][min]'],
                        ['_linked' => 'values[][max]'],
                        ['_linked' => 'values[][step]']
                    ],
                    'message' => $this->_('PackageOptions.!error.values[][default].qty_valid')
                ],
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('PackageOptions.!error.values[][default].format')
                ]
            ],
            'values[][min]' => [
                'valid' => [
                    'rule' => [[$this, 'validateValueMin'], $type],
                    'message' => $this->_('PackageOptions.!error.values[][min].valid'),
                    'post_format' => [$toInteger]
                ]
            ],
            'values[][max]' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateValueMax'], $type],
                    'message' => $this->_('PackageOptions.!error.values[][max].valid'),
                    'post_format' => [$toInteger]
                ]
            ],
            'values[][step]' => [
                'valid' => [
                    'rule' => [[$this, 'validateValueStep'], $type],
                    'message' => $this->_('PackageOptions.!error.values[][step].valid'),
                    'post_format' => [$toInteger]
                ]
            ],
            'values[][pricing][][term]' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'formatPricingTerm'], ['_linked' => 'values[][pricing][][period]']],
                    'rule' => 'is_numeric',
                    'message' => $this->_('PackageOptions.!error.values[][pricing][][term].format')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 5],
                    'message' => $this->_('PackageOptions.!error.values[][pricing][][term].length')
                ],
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validatePricingTerm'], ['_linked' => 'values[][pricing][][period]']],
                    'message' => $this->_('PackageOptions.!error.values[][pricing][][term].valid')
                ]
            ],
            'values[][pricing][][period]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validatePricingPeriod']],
                    'message' => $this->_('PackageOptions.!error.values[][pricing][][period].format')
                ]
            ],
            'values[][pricing][][price]' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'currencyToDecimal'], ['_linked' => 'values[][pricing][][currency]'], 4],
                    'rule' => 'is_numeric',
                    'message' => $this->_('PackageOptions.!error.values[][pricing][][price].format')
                ]
            ],
            'values[][pricing][][price_renews]' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'currencyToDecimal'], ['_linked' => 'values[][pricing][][currency]'], 4],
                    'rule' => 'is_numeric',
                    'message' => $this->_('PackageOptions.!error.values[][pricing][][price_renews].format')
                ],
                'valid' => [
                    'if_set' => true,
                    'rule' => [
                        function($price, $period) {
                            // The renewal price may not be set for the onetime period
                            return ($period != 'onetime' || $price === null);
                        },
                        ['_linked' => 'values[][pricing][][period]']
                    ],
                    'message' => $this->_('PackageOptions.!error.values[][pricing][][price_renews].valid')
                ]
            ],
            'values[][pricing][][setup_fee]' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'currencyToDecimal'], ['_linked' => 'values[][pricing][][currency]'], 4],
                    'rule' => 'is_numeric',
                    'message' => $this->_('PackageOptions.!error.values[][pricing][][setup_fee].format')
                ]
            ],
            'values[][pricing][][currency]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^(.*){3}$/'],
                    'message' => $this->_('PackageOptions.!error.values[][pricing][][currency].format')
                ]
            ],
            'groups' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateGroupIds'], (isset($vars['company_id']) ? $vars['company_id'] : null)],
                    'message' => $this->_('PackageOptions.!error.groups.exists')
                ]
            ],
            'hidden' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array',  [0, 1]],
                    'message' => $this->_('PackageOptions.!error.hidden.valid')
                ]
            ]
        ];

        if ($edit) {
            Loader::loadModels($this, ['Services']);

            // Company ID may not be changed
            unset($rules['company_id']);

            // A valid package option is required
            $rules['option_id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'package_options'],
                    'message' => $this->_('PackageOptions.!error.option_id.exists')
                ]
            ];

            // Validate any IDs that may have been given
            $rules['values[][id]'] = [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'package_option_values'],
                    'message' => $this->_('PackageOptions.!error.values[][id].exists')
                ]
            ];
            $rules['values[][pricing][][id]'] = [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'package_option_pricing'],
                    'message' => $this->_('PackageOptions.!error.values[][pricing][][id].exists')
                ],
                // Validate each value price to ensure it is not being deleted if the value price is in use by a service
                'delete_in_use' => [
                    'if_set' => true,
                    'rule' => [
                        function($id, $term, $option_value_id) {
                            // The option value pricing cannot be deleted if it is in use by a service
                            if ($this->canDeleteValuePrice(['id' => $id, 'term' => $term])
                                && $this->Services->isServiceOptionValueInUse($option_value_id, $id)
                            ) {
                                return false;
                            }

                            return true;
                        },
                        ['_linked' => 'values[][pricing][][term]'],
                        ['_linked' => 'values[][id]']
                    ],
                    'message' => $this->_('PackageOptions.!error.values[][pricing][][id].delete_in_use')
                ]
            ];

            // Validate each value to ensure it is not being changed if the value is in use by a service
            $rules['values[][value]']['edit_in_use'] = [
                'if_set' => true,
                'rule' => [
                    function($value, $id, $name, $option_type) {
                        // The value cannot be changed if it is in use by a service
                        // However, text/textarea/password values are user-defined, and quantity values
                        // have no conflict with current service values, so they are ignored
                        if ($this->canEditValue($id)
                            // Ensure we aren't deleting the value, which is handled by a separate input validation rule
                            && !$this->canDeleteValue($id, $name, $option_type)
                            && ($option_value = $this->getValueById($id))
                            && !in_array($option_value->option_type, ['text', 'textarea', 'password', 'quantity'])
                            && $option_value->value != $value
                            && $this->Services->isServiceOptionValueInUse($id)
                        ) {
                            return false;
                        }

                        return true;
                    },
                    ['_linked' => 'values[][id]'],
                    ['_linked' => 'values[][name]'],
                    ['_linked' => 'type']
                ],
                'message' => $this->_('PackageOptions.!error.values[][value].edit_in_use')
            ];

            // Validate each value to ensure it is not being deleted if the value is in use by a service
            $rules['values[][value]']['delete_in_use'] = [
                'if_set' => true,
                'rule' => [
                    function($value, $id, $name, $option_type) {
                        // The value cannot be deleted if it is in use by a service
                        if ($this->canDeleteValue($id, $name, $option_type)
                            && $this->Services->isServiceOptionValueInUse($id)
                        ) {
                            return false;
                        }

                        return true;
                    },
                    ['_linked' => 'values[][id]'],
                    ['_linked' => 'values[][name]'],
                    ['_linked' => 'type']
                ],
                'message' => $this->_('PackageOptions.!error.values[][value].delete_in_use')
            ];
        }

        return $rules;
    }

    /**
     * Validates whether the given package option value given has a valid step set
     *
     * @param string $step The package option step value
     * @param string $type The package option type
     * @return bool True if the package option value has a valid step set, or false otherwise
     */
    public function validateValueStep($step, $type)
    {
        // The step for the quantity type must be at least 1
        if ($type == 'quantity' && (!is_numeric($step) || $step < 1)) {
            return false;
        } elseif ($type != 'quantity' && $step !== null) {
            return false;
        }
        return true;
    }

    /**
     * Validates whether the given package option value given has a valid minimum value
     *
     * @param string $min The package option minimum value
     * @param string $type The package option type
     * @return bool True if the package option value has a valid minimum value set, or false otherwise
     */
    public function validateValueMin($min, $type)
    {
        // The minimum quantity must be at least 0
        if ($type == 'quantity' && (!is_numeric($min) || $min < 0)) {
            return false;
        } elseif ($type != 'quantity' && $min !== null) {
            return false;
        }
        return true;
    }

    /**
     * Validates whether the given package option value given has a valid maximum value
     *
     * @param string $max The package option maximum value
     * @param string $type The package option type
     * @return bool True if the package option value has a valid maximum value set, or false otherwise
     */
    public function validateValueMax($max, $type)
    {
        // The maximum quantity must be at least 1
        if ($type == 'quantity' && ($max !== null && (!is_numeric($max) || $max < 1))) {
            return false;
        } elseif ($type != 'quantity' && $max !== null) {
            return false;
        }
        return true;
    }

    /**
     * Validates whether the number of package option values is valid for the given package option type
     *
     * @param array $values A numerically-indexed array of package option values
     * @param string $type The package option type
     * @return bool True if the number of package option values is valid for
     *  the given package option type, or false otherwise
     */
    public function validateOptionValueLimit($values, $type)
    {
        // Checkbox, quantity, text, textarea, and password types must have exactly 1 value
        $single_types = ['checkbox', 'quantity', 'text', 'textarea', 'password'];
        if (in_array($type, $single_types) && (!is_array($values) || count($values) != 1)) {
            return false;
        }
        return true;
    }

    /**
     * Validates whether any of the given package option values contains invalid special characters
     * for options of the 'select' type. An invalid character is determined to be one that is not equivalent
     * to its HTML encoded version
     *
     * @param array $values A numerically-indexed array of package option values
     * @param string $type The package option type
     * @return bool True if all package option values contain valid characters, or false otherwise
     */
    public function validateSelectTypeValues($values, $type)
    {
        Loader::loadHelpers($this, ['Html']);

        // Only select option values are of concern because browsers don't decode them on POST,
        // so we can only allow characters that are
        if (in_array($type, ['select']) && is_array($values)) {
            foreach ($values as $value) {
                // Each value must be equivalent to its HTML-safe value
                if (is_scalar($value['value']) && $this->Html->safe($value['value']) != $value['value']) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validates that no duplicate package option values were submitted
     *
     * @param array $values A numerically-indexed array of package option values
     * @return bool False if all package option values contain duplicates, or true otherwise
     */
    private function validateUniqueValues($values) {
        $option_values = [];
        foreach ((array)$values as $value) {
            if (isset($value['value']) && !isset($option_values[$value['value']])) {
                $option_values[$value['value']] = $value['value'];
            }
        }

        return count($option_values) === count((array)$values);
    }

    /**
     * Validates whether all of the given package option group IDs exist and belong to the given company
     *
     * @param array $groups An array of package option group IDs
     * @param int $company_id The ID of the company the groups must belong to (optional, default null)
     * @return bool True if all of the given package option groups exist and
     *  belong to the given company, or false otherwise
     */
    public function validateGroupIds($groups, $company_id = null)
    {
        // Groups may be empty
        if (empty($groups)) {
            return true;
        }

        if (is_array($groups)) {
            // Check each group
            foreach ($groups as $group_id) {
                $this->Record->select(['id'])->from('package_option_groups')->
                    where('id', '=', $group_id);

                // Filter on company ID if given
                if ($company_id) {
                    $this->Record->where('company_id', '=', $company_id);
                }

                $count = $this->Record->numResults();

                // This package option group doesn't exist
                if ($count <= 0) {
                    return false;
                }
            }
        } else {
            return false;
        }

        return true;
    }
}
