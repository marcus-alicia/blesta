<?php
use Blesta\Core\Util\Filters\PackageOptionGroupFilters;
use Blesta\Core\Util\Filters\PackageOptionFilters;

/**
 * Admin Package Options Management
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminPackageOptions extends AppController
{
    /**
     * Packages pre-action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        $this->uses(['PackageOptionGroups', 'PackageOptions']);
        $this->helpers(['DataStructure']);

        // Create Array Helper
        $this->ArrayHelper = $this->DataStructure->create('Array');

        Language::loadLang(['admin_package_options']);
    }

    /**
     * List package option groups/options
     */
    public function index()
    {
        // Set current page of results
        $type = (isset($this->get[0]) ? $this->get[0] : 'groups');
        $page = (isset($this->get[1]) ? (int) $this->get[1] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : ($type == 'groups' ? 'name' : 'label'));
        $order = (isset($this->get['order']) ? $this->get['order'] : 'asc');
        $negate_order = ($order == 'asc' ? 'desc' : 'asc');

        // Set filters from post input
        $post_filters = [];
        if (isset($this->post['filters'])) {
            $post_filters = $this->post['filters'];
            unset($this->post['filters']);

            foreach($post_filters as $filter => $value) {
                if (empty($value)) {
                    unset($post_filters[$filter]);
                }
            }
        }

        // Set the number of packages of each type
        $option_filters = array_merge(['hidden' => 0], $post_filters);
        $type_count = [
            'groups' => $this->PackageOptionGroups->getListCount($this->company_id, $option_filters),
            'options' => $this->PackageOptions->getListCount($this->company_id, $option_filters)
        ];

        $this->set('type', $type);
        $this->set('type_count', $type_count);
        $this->set('page', $page);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', $negate_order);

        // Build the content partial to list groups/options
        $template = 'admin_package_option_groups';
        $template_params = [
            'sort' => $sort,
            'order' => $order,
            'negate_order' => $negate_order,
            'type' => $type
        ];

        // Set options or groups
        if ($type == 'options') {
            $template = 'admin_package_option_options';
            $template_params['package_options'] = $this->PackageOptions->getList(
                $this->company_id,
                $page,
                [$sort => $order],
                $option_filters
            );
            $total_results = $type_count['options'];

            // Set the input field filters for the widget
            $package_option_filters = new PackageOptionFilters();
        } else {
            $template_params['package_groups'] = $this->PackageOptionGroups->getList(
                $this->company_id,
                $page,
                [$sort => $order],
                $option_filters
            );
            $total_results = $type_count['groups'];

            // Set the input field filters for the widget
            $package_option_filters = new PackageOptionGroupFilters();
        }

        $filters = $package_option_filters->getFilters(
            ['language' => Configure::get('Blesta.language'), 'company_id' => Configure::get('Blesta.company_id')],
            $post_filters
        );

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'package_options/index/' . $type . '/[p]/',
                'params' => ['sort' => $sort, 'order' => $order],
            ]
        );
        $this->setPagination($this->get, $settings);

        $this->set('content', $this->partial($template, $template_params));
        $this->set('filter_vars', $post_filters);
        $this->set('filters', $filters);

        // Render the request if ajax
        return $this->renderAjaxWidgetIfAsync(isset($this->get[1]) || isset($this->get['sort']));
    }

    /**
     * AJAX Fetch the expandable row section for package options
     */
    public function optionInfo()
    {
        // Ensure we have a valid package option
        if (!$this->isAjax()
            || !isset($this->get[0])
            || !($package_option = $this->PackageOptions->get($this->get[0]))
            || $package_option->company_id != $this->company_id
        ) {
            header($this->server_protocol . ' 403 Forbidden');
            exit();
        }

        // Set the package option
        $vars = [
            'package_option' => $package_option,
            'value_statuses' => $this->PackageOptions->getValueStatuses()
        ];

        echo $this->outputAsJson($this->partial('admin_package_options_optioninfo', $vars));
        return false;
    }

    /**
     * AJAX Fetch the expandable row section for package option groups
     */
    public function groupInfo()
    {
        // Ensure we have a valid package option group
        if (!$this->isAjax()
            || !isset($this->get[0])
            || !($package_group = $this->PackageOptionGroups->get($this->get[0]))
            || $package_group->company_id != $this->company_id
        ) {
            header($this->server_protocol . ' 403 Forbidden');
            exit();
        }

        // Fetch all the package options in this group
        $vars = [
            'package_options' => $this->PackageOptionGroups->getAllOptions($package_group->id),
            'package_group' => $package_group
        ];

        echo $this->outputAsJson($this->partial('admin_package_options_groupinfo', $vars));
        return false;
    }

    /**
     * Adds a package option
     */
    public function add()
    {
        $this->uses(['Currencies', 'Packages']);

        $vars = new stdClass();

        if (!empty($this->post)) {
            $data = $this->post;
            $data['company_id'] = $this->company_id;

            // Format the option values and pricing arrays
            $data = $this->formatOptionValueData($data);

            $option_id = $this->PackageOptions->add($data);

            if (($errors = $this->PackageOptions->errors())) {
                // Error, reset vars
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                // Success
                $this->flashMessage('message', Language::_('AdminPackageOptions.!success.option_added', true));
                $this->redirect($this->base_uri . 'package_options/index/options/');
            }
        }

        // Get default currency
        $default_currency = $this->SettingsCollection->fetchSetting(null, $this->company_id, 'default_currency');
        $default_currency = $default_currency['value'];

        // Set default currency as the selected currency
        if (empty($this->post)) {
            $vars->values = ['pricing' => [['currency' => [$default_currency]]]];
        }

        // Fetch all available package option groups
        $package_option_groups = $this->Form->collapseObjectArray(
            $this->PackageOptionGroups->getAll($this->company_id),
            'name',
            'id'
        );

        // Set all selected package option groups in assigned and unset all selected groups from available
        if (isset($vars->groups) && is_array($vars->groups)) {
            $selected = [];

            foreach ($package_option_groups as $id => $name) {
                if (in_array($id, $vars->groups)) {
                    $selected[$id] = $name;
                    unset($package_option_groups[$id]);
                }
            }

            $vars->groups = $selected;
        }

        $please_select = ['' => Language::_('AppController.select.please', true)];
        $this->set('types', $please_select + $this->PackageOptions->getTypes());
        $this->set('value_statuses', $this->PackageOptions->getValueStatuses());
        $this->set(
            'currencies',
            $this->Form->collapseObjectArray($this->Currencies->getAll($this->company_id), 'code', 'code')
        );
        $this->set('default_currency', $default_currency);
        $this->set('periods', $this->Packages->getPricingPeriods());
        $this->set('package_option_groups', $package_option_groups);
        $this->set('vars', $vars);
    }

    /**
     * Updates a package option
     */
    public function edit()
    {
        // Ensure a valid package option was given
        if (!isset($this->get[0])
            || !($option = $this->PackageOptions->get($this->get[0]))
            || $option->company_id != $this->company_id
        ) {
            $this->redirect($this->base_uri . 'package_options/index/options/');
        }

        $this->uses(['Currencies', 'Packages']);

        if (!empty($this->post)) {
            // Format the option values and pricing arrays
            $data = $this->formatOptionValueData($this->post);

            $option_id = $this->PackageOptions->edit($option->id, $data);

            if (($errors = $this->PackageOptions->errors())) {
                // Error, reset vars
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                // Success
                $this->flashMessage('message', Language::_('AdminPackageOptions.!success.option_updated', true));
                $this->redirect($this->base_uri . 'package_options/index/options/');
            }
        }

        // Set initial option data
        if (!isset($vars)) {
            $vars = $option;

            // Format the values, pricing, and groups to HTML-style arrays
            if (property_exists($vars, 'values')) {
                foreach ($vars->values as $index => &$value) {
                    if (property_exists($value, 'pricing')) {
                        foreach ($value->pricing as $pricing_index => &$price) {
                            $vars->values[$index]->pricing[$pricing_index] = (array) $price;
                        }
                        $vars->values[$index]->pricing = $this->ArrayHelper->numericToKey(
                            $vars->values[$index]->pricing
                        );
                    }
                    $vars->values[$index] = (array) $value;
                }
                $vars->values = $this->ArrayHelper->numericToKey($vars->values);
            }

            if (property_exists($vars, 'groups')) {
                foreach ($vars->groups as $key => $group) {
                    $vars->groups[$key] = $group->id;
                }
            }
        }

        // Get default currency
        $default_currency = $this->SettingsCollection->fetchSetting(null, $this->company_id, 'default_currency');
        $default_currency = $default_currency['value'];

        // Fetch all available package option groups
        $package_option_groups = $this->Form->collapseObjectArray(
            $this->PackageOptionGroups->getAll($this->company_id),
            'name',
            'id'
        );

        // Set all selected package option groups in assigned and unset all selected groups from available
        if (isset($vars->groups) && is_array($vars->groups)) {
            $selected = [];

            foreach ($package_option_groups as $id => $name) {
                if (in_array($id, $vars->groups)) {
                    $selected[$id] = $name;
                    unset($package_option_groups[$id]);
                }
            }

            $vars->groups = $selected;
        }

        $please_select = ['' => Language::_('AppController.select.please', true)];
        $this->set('types', $please_select + $this->PackageOptions->getTypes());
        $this->set('value_statuses', $this->PackageOptions->getValueStatuses());
        $this->set(
            'currencies',
            $this->Form->collapseObjectArray($this->Currencies->getAll($this->company_id), 'code', 'code')
        );
        $this->set('default_currency', $default_currency);
        $this->set('periods', $this->Packages->getPricingPeriods());
        $this->set('package_option_groups', $package_option_groups);
        $this->set('vars', $vars);
    }

    /**
     * Deletes a package option
     */
    public function delete()
    {
        // Ensure a valid package option was given
        if (!isset($this->post['id'])
            || !($option = $this->PackageOptions->get($this->post['id']))
            || $option->company_id != $this->company_id
        ) {
            $this->redirect($this->base_uri . 'package_options/index/options/');
        }

        $this->PackageOptions->delete($option->id);

        $this->flashMessage('message', Language::_('AdminPackageOptions.!success.option_deleted', true));
        $this->redirect($this->base_uri . 'package_options/index/options/');
    }

    /**
     * Removes a package option from a package option group
     */
    public function remove()
    {
        // Ensure a valid package option and group were given
        if (!isset($this->post['option_id'])
            || !isset($this->post['group_id'])
            || !($option = $this->PackageOptions->get($this->post['option_id']))
            || !($group = $this->PackageOptionGroups->get($this->post['group_id']))
        ) {
            $this->redirect($this->base_uri . 'package_options/');
        }

        $this->PackageOptions->removeFromGroup($option->id, [$group->id]);

        $this->flashMessage('message', Language::_('AdminPackageOptions.!success.option_removed', true));
        $this->redirect($this->base_uri . 'package_options/');
    }

    /**
     * Add package option group
     */
    public function addGroup()
    {
        $this->uses(['Packages']);

        $vars = new stdClass();

        // Add the package option group
        if (!empty($this->post)) {
            $data = $this->post;
            $data['company_id'] = $this->company_id;
            $this->PackageOptionGroups->add($data);

            if (($errors = $this->PackageOptionGroups->errors())) {
                // Error, reset vars
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                // Success
                $this->flashMessage('message', Language::_('AdminPackageOptions.!success.group_added', true));
                $this->redirect($this->base_uri . 'package_options/');
            }
        }

        // Fetch all available packages
        $packages = $this->Form->collapseObjectArray($this->Packages->getAll($this->company_id, ['name' => 'ASC'], null, null, ['hidden' => 1]), 'name', 'id');

        $vars->packages = (isset($vars->packages) && is_array($vars->packages) ? $vars->packages : []);
        $vars->packages = $this->getSelectedSwappableOptions($packages, $vars->packages);

        $this->set('vars', $vars);
        $this->set('packages', $packages);
    }

    /**
     * Updates a package option group
     */
    public function editGroup()
    {
        // Ensure a valid package option group was given
        if (!isset($this->get[0])
            || !($group = $this->PackageOptionGroups->get($this->get[0]))
            || $group->company_id != $this->company_id
        ) {
            $this->redirect($this->base_uri . 'package_options/');
        }

        $this->uses(['Packages']);
        $vars = $group;

        // Update the package option group
        if (!empty($this->post)) {
            $data = $this->post;
            $this->PackageOptionGroups->edit($group->id, $data);

            if (($errors = $this->PackageOptionGroups->errors())) {
                // Error, reset vars
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                // Success
                $this->flashMessage('message', Language::_('AdminPackageOptions.!success.group_updated', true));
                $this->redirect($this->base_uri . 'package_options/');
            }
        }

        // Fetch all available packages
        $packages = $this->Form->collapseObjectArray($this->Packages->getAll($this->company_id, ['name' => 'ASC'], null, null, ['hidden' => 1]), 'name', 'id');

        $vars->packages = (isset($vars->packages) && is_array($vars->packages) ? $vars->packages : []);
        $vars->packages = $this->getSelectedSwappableOptions($packages, $vars->packages);

        $this->set('vars', $vars);
        $this->set('packages', $packages);
    }

    /**
     * Deletes a package option group
     */
    public function deleteGroup()
    {
        // Ensure a valid package option group was given
        if (!isset($this->post['id'])
            || !($group = $this->PackageOptionGroups->get($this->post['id']))
            || $group->company_id != $this->company_id
        ) {
            $this->redirect($this->base_uri . 'package_options/');
        }

        $this->PackageOptionGroups->delete($group->id);

        $this->flashMessage('message', Language::_('AdminPackageOptions.!success.group_deleted', true));
        $this->redirect($this->base_uri . 'package_options/');
    }

    /**
     * Manages configurable options logic
     */
    public function logic()
    {
        $this->uses(['PackageOptionConditions', 'PackageOptionConditionSets']);

        if (!isset($this->get[0])
            || !($group = $this->PackageOptionGroups->get($this->get[0]))
            || $group->company_id != $this->company_id
        ) {
            $this->redirect($this->base_uri . 'package_options/');
        }

        // Get the option for the current option
        $group_options = $this->PackageOptionGroups->getAllOptions($group->id);
        $package_options = $group_options;
        $option = null;
        foreach ($group_options as $key => $group_option) {
            if (!isset($this->get[1]) || $group_option->id == $this->get[1]) {
                $option = $this->PackageOptions->get($group_option->id);
                unset($package_options[$key]);
                break;
            }
        }

        // No options for which to set logic
        if (!$option) {
            $this->redirect($this->base_uri . 'package_options/');
        }

        // Get the current condition sets for this option
        $condition_sets = $this->PackageOptionConditionSets->getAll(
            ['option_id' => $option->id, 'option_group_id' => $group->id]
        );
        $set_ids = array_map(function ($value) { return $value->id;}, $condition_sets);
        $sets_by_id = array_combine($set_ids, $condition_sets);

        $vars = (object) [];
        if (!empty($this->post)) {
            $post = (object) $this->post;
            $errors = null;

            // Start transaction for adding conditions and sets
            $this->PackageOptionConditionSets->begin();
            foreach (($post->condition_sets ?? []) as $condition_set) {
                $conditions_by_id = [];

                // If no option_value_ids are submitted, set an empty list
                if (!isset($condition_set['option_value_ids'])) {
                    $condition_set['option_value_ids'] = [];
                }

                // Add or edit condition set
                if (!empty($condition_set['id'])) {
                    $set_id = $this->PackageOptionConditionSets->edit($condition_set['id'], $condition_set);

                    if (isset($sets_by_id[$condition_set['id']])) {
                        // Get current condtions in this set
                        $condition_ids = array_map(
                            function ($value) { return $value->id; },
                            $sets_by_id[$condition_set['id']]->conditions
                        );
                        $conditions_by_id = array_combine(
                            $condition_ids,
                            $sets_by_id[$condition_set['id']]->conditions
                        );

                        unset($sets_by_id[$set_id]);
                    }
                } else {
                    $set_id = $this->PackageOptionConditionSets->add($condition_set);
                    $condition_set['id'] = $set_id;
                }

                // Abort the whole process if we encounter an error
                if (($errors = $this->PackageOptionConditionSets->errors())) {
                    break;
                }

                $set_object = (object) $condition_set;
                foreach (($set_object->conditions ?? []) as $condition) {
                    $condition['condition_set_id'] = $set_object->id;
                    // Add or edit condition set
                    if (!empty($condition['id'])) {
                        $this->PackageOptionConditions->edit($condition['id'], $condition);

                        unset($conditions_by_id[$condition['id']]);
                    } else {
                        $this->PackageOptionConditions->add($condition);
                    }

                    // Abort the whole process if we encounter an error
                    if (($errors = $this->PackageOptionConditions->errors())) {
                        break 2;
                    }
                }

                // Delete any conditions that were not included
                foreach ($conditions_by_id as $delete_condition) {
                    $this->PackageOptionConditions->delete($delete_condition->id);
                }
            }

            // Delete any condition sets that were not included
            foreach ($sets_by_id as $delete_set) {
                $this->PackageOptionConditionSets->delete($delete_set->id);
            }

            if ($errors) {
                // Rollback all database changes and reset vars to the view
                $this->PackageOptionConditionSets->rollback();
                $vars = $this->formatLogicVars($this->post);

                // Set error message
                $this->setMessage('error', $errors);
            } else {
                // Commit all database changes
                $this->PackageOptionConditionSets->commit();

                // Success message and redirect
                $this->flashMessage('message', Language::_('AdminPackageOptions.!success.logic_updated', true));
                $this->redirect($this->base_uri . 'package_options/');
            }
        } else {
            $vars->condition_sets = $condition_sets;
        }

        $this->set('vars', $vars);
        $this->set('group', $group);
        $this->set('option', $option);
        $this->set(
            'operators',
            ['' => Language::_('AppController.select.please', true)] + $this->PackageOptionConditions->getOperators()
        );
        $this->set('group_options', $this->Form->collapseObjectArray($group_options, 'label', 'id'));
        $this->set(
            'package_options',
            ['' => Language::_('AppController.select.please', true)] + $this->Form->collapseObjectArray($package_options, 'label', 'id')
        );

        return $this->renderAjaxWidgetIfAsync(false);
    }

    /**
     * Updates a package option group
     */
    public function logicSettings()
    {
        // Ensure a valid package option group was given
        if (!isset($this->get[0])
            || !($group = $this->PackageOptionGroups->get($this->get[0]))
            || $group->company_id != $this->company_id
        ) {
            $this->redirect($this->base_uri . 'package_options/');
        }

        $this->uses(['Packages']);
        $vars = $group;

        // Update the package option group
        if (!empty($this->post)) {
            $data = $this->post;
            if (!isset($data['hide_options'])) {
                $data['hide_options'] = '0';
            }

            $this->PackageOptionGroups->edit($group->id, $data);

            if (($errors = $this->PackageOptionGroups->errors())) {
                // Error, reset vars
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                // Success
                $this->flashMessage('message', Language::_('AdminPackageOptions.!success.group_updated', true));
                $this->redirect($this->base_uri . 'package_options/logic/' . $group->id);
            }
        }

        $this->set('vars', $vars);
        $this->set('group', $group);
    }

    /**
     * Formats logic post data to be returned to the view
     *
     * @param array $post The post data to be formatted
     * @return stdClass Formatted post data
     */
    private function formatLogicVars(array $post) {
        $vars = (object) $post;
        foreach (($vars->condition_sets ?? []) as $index => $condition_set) {
            $object_condition_set = (object) $condition_set;
            foreach (($object_condition_set->conditions ?? []) as $condition_index => $condition) {
                $condition['triggering_option'] = $this->PackageOptions->get(($condition['trigger_option_id']?? null));
                $object_condition_set->conditions[$condition_index] = (object) $condition;
            }

            if (!empty($object_condition_set->conditions)) {
                $object_condition_set->conditions = array_values($object_condition_set->conditions);
            }
            $vars->condition_sets[$index] = $object_condition_set;
        }

        if (!empty($vars->condition_sets)) {
            $vars->condition_sets = array_values($vars->condition_sets);
        }

        return $vars;
    }

    /**
     * Outputs the given package option as a json object
     */
    public function getOption()
    {
        if (!$this->isAjax()
            || !isset($this->get[0])
            || !($option = $this->PackageOptions->get($this->get[0]))
        ) {
            $this->redirect($this->base_uri . 'package_options/');
        }

        $this->outputAsJson($option);
        return false;
    }

    /**
     * Order package options within a group
     */
    public function orderOptions()
    {
        if (!$this->isAjax()) {
            $this->redirect($this->base_uri . 'package_options/');
        }

        if (!empty($this->post)) {
            $this->PackageOptionGroups->orderOptions($this->post['group_id'], $this->post['options']);
        }
        return false;
    }

    /**
     * Order package option values within an option
     */
    public function orderValues()
    {
        if (!$this->isAjax()) {
            $this->redirect($this->base_uri . 'package_options/');
        }

        if (!empty($this->post)) {
            $this->PackageOptions->orderValues($this->post['option_id'], $this->post['values']);
        }
        return false;
    }

    /**
     * Formats the option values and pricing data submitted when adding or editing an option
     *
     * @param array $vars A list of input vars
     * @return array A list of formatted input vars
     */
    private function formatOptionValueData(array $vars)
    {
        if (!isset($vars['addable'])) {
            $vars['addable'] = 0;
        }
        if (!isset($vars['editable'])) {
            $vars['editable'] = 0;
        }

        // Format the array data
        if (isset($vars['values']) && is_array($vars['values'])) {
            $vars['values'] = $this->ArrayHelper->keyToNumeric($this->post['values']);

            foreach ($vars['values'] as &$value) {
                if (empty($value['id'])) {
                    $value['id'] = null;
                }

                // Format the pricing data
                if (isset($value['pricing']) && is_array($value['pricing'])) {
                    $value['pricing'] = $this->ArrayHelper->keyToNumeric($value['pricing']);
                }

                // Quantity types should always have a value of NULL. Non-quantity types must have
                // a non-null value, and null min/max/step
                if (isset($vars['type']) && $vars['type'] == 'quantity') {
                    $value['value'] = null;

                    // The max value may be null (unlimited) if blank
                    $value['max'] = (isset($value['max']) && $value['max'] != '' ? $value['max'] : null);
                } else {
                    $value['value'] = (isset($value['value']) ? $value['value'] : '');
                    $value['min'] = null;
                    $value['max'] = null;
                    $value['step'] = null;
                }

                if (isset($value['pricing'])) {
                    foreach ($value['pricing'] as &$pricing) {
                        // Set a null ID so that new pricings can be properly added
                        if (empty($pricing['id'])) {
                            $pricing['id'] = null;
                        }

                        // Make sure there is no renewal price set for onetime pricings
                        if (array_key_exists('period', (array)$pricing) && $pricing['period'] == 'onetime') {
                            $pricing['price_renews'] = null;
                            $pricing['price_enable_renews'] = '0';
                        }
                    }
                }

                // Set the default checkbox value for 'default' if unchecked
                if (isset($vars['type']) && $vars['type'] == 'checkbox') {
                    $value['default'] = (isset($value['default']) ? $value['default'] : '0');
                }
            }
        }
        return $vars;
    }

    /**
     * Retrieves a list of selected options for the swappable multi-select, and removes them from the available groups
     *
     * @param array &$available_groups A reference to a key/value array of all the available groups to choose from
     * @param array $selected_groups A key/value array of all the selected groups
     * @return array A key/value indexed array subset of the $available_groups that have been selected
     */
    private function getSelectedSwappableOptions(array &$available_groups, array $selected_groups)
    {
        $selected = [];

        foreach ($available_groups as $id => $name) {
            if (in_array($id, $selected_groups)) {
                $selected[$id] = $name;
                unset($available_groups[$id]);
            }
        }

        return $selected;
    }
}
