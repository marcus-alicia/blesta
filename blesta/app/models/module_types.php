<?php

/**
 * Module Types Management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ModuleTypes extends AppModel
{
    /**
     * Initialize ModuleTypes
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['module_types']);
    }

    /**
     * Fetches a single module type
     *
     * @param int $type_id The ID of the module type to fetch
     * @return stdClass A class containing the id and name of the module type
     */
    public function get($type_id)
    {
        return $this->Record->select()
            ->from('module_types')
            ->where('id', '=', $type_id)
            ->fetch();
    }

    /**
     * Fetches a single module type by it's name
     *
     * @param string $name The name of the module type to fetch
     * @return stdClass A class containing the id and name of the module type
     */
    public function getByName($name)
    {
        return $this->Record->select()
            ->from('module_types')
            ->where('name', '=', $name)
            ->fetch();
    }

    /**
     * Fetches all the module types available in the system
     *
     * @param string $sort_by The field to sort by
     * @param string $order The direction to order results
     * @return array A numerically indexed array containing the module types
     */
    public function getAll($sort_by = 'name', $order = 'asc')
    {
        return $this->Record->select()
            ->from('module_types')
            ->order([$sort_by => $order])
            ->fetchAll();
    }

    /**
     * Returns a list of all module types on the system
     *
     * @return array A list of all module types
     */
    public function getList()
    {
        Loader::loadHelpers($this, ['Form']);

        return $this->Form->collapseObjectArray($this->getAll(), 'name', 'id');
    }

    /**
     * Adds a new module type to the system
     *
     * @param array $vars An array of module type data including:
     *
     *  - name The name of the new module type
     * @return int The ID of the module type, void on error
     */
    public function add(array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        // Add the module type to the database
        if ($this->Input->validates($vars)) {
            $fields = ['name'];
            $this->Record->insert('module_types', $vars, $fields);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Deletes a module type
     *
     * @param int $type_id The ID of the module type to delete
     */
    public function delete($type_id)
    {
        $this->Record->from('module_types')->where('id', '=', $type_id)->delete();
    }

    /**
     * Deletes a module type by it's name
     *
     * @param string $name The module type name to delete
     */
    public function deleteByName($name)
    {
        $this->Record->from('module_types')->where('name', '=', $name)->delete();
    }

    /**
     * Updates an existing module type
     *
     * @param int $type_id The id of the module type to update
     * @param array $vars An array of module type data including:
     *
     *  - name The name of the new module type
     */
    public function edit($type_id, array $vars)
    {
        // Set the type ID
        $vars['type_id'] = $type_id;

        $this->Input->setRules($this->getRules($vars, true));

        // Updates the module type
        if ($this->Input->validates($vars)) {
            $this->Record->where('id', '=', $type_id)->update('module_types', $vars);

            return $type_id;
        }
    }

    /**
     * Returns a list of default module types
     *
     * @return array A list of default module types
     */
    public function getDefaultTypes()
    {
        return [
            'generic',
            'registrar'
        ];
    }

    /**
     * Returns the rules to validate the module type
     *
     * @param array $vars The input vars
     * @param bool $edit Whether the module type is being edited (optional)
     * @return array A list of rules
     */
    private function getRules(array $vars = [], $edit = false)
    {
        $rules = [
            'name' => [
                'valid' => [
                    'negate' => true,
                    'rule' => [[$this, 'validateExists'], 'name', 'module_types'],
                    'message' => $this->_('ModuleTypes.!error.name.valid')
                ]
            ]
        ];

        if ($edit) {
            $rules['type_id'] = [
                'valid' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'module_types'],
                    'message' => $this->_('ModuleTypes.!error.type_id.valid')
                ]
            ];

            if (isset($vars['type_id'])) {
                $module_type = $this->get($vars['type_id']);

                if ($module_type->name == $vars['name']) {
                    unset($rules['name']);
                }
            }
        }

        return $rules;
    }
}
