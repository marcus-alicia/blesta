<?php
/**
 * Extension Generator Extension Management
 *
 * @package blesta
 * @subpackage blesta.plugins.extension_generator.models
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ExtensionGeneratorExtensions extends ExtensionGeneratorModel
{
    /**
     * Returns a list of extensions for the given company
     *
     * @param int $company_id The ID of the company to fetch extensions from
     * @param int $page The page number of results to fetch
     * @param array $order A key/value pair array of fields to order the results by
     * @return array An array of stdClass objects, each representing an extension
     */
    public function getList($company_id, $page = 1, array $order = ['date_updated' => 'desc'])
    {
        $extensions = $this->getExtension(['company_id' => $company_id])
            ->order($order)
            ->limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())
            ->fetchAll();

        foreach ($extensions as $extension) {
            $extension->data = unserialize($extension->data);
        }

        return $extensions;
    }

    /**
     * Returns the total number of extension for the given company
     *
     * @param int $company_id The ID of the company to fetch extension count from
     * @return int The total number of extensions for the given company
     */
    public function getListCount($company_id)
    {
        return $this->getExtension(['company_id' => $company_id])->numResults();
    }

    /**
     * Returns all extensions in the system for the given company
     *
     * @param int $company_id The ID of the company to fetch extensions for
     * @param array $order A key/value pair array of fields to order the results by
     * @return array An array of stdClass objects, each representing an extension
     */
    public function getAll($company_id, array $order = ['date_updated' => 'desc'])
    {
        $extensions = $this->getExtension(['company_id' => $company_id])->fetchAll();

        foreach ($extensions as $extension) {
            $extension->data = unserialize($extension->data);
        }

        return $extensions;
    }

    /**
     * Fetches the extension with the given ID
     *
     * @param int $extension_id The ID of the extension to fetch
     * @return mixed A stdClass object representing the extension, false if no such extension exists
     */
    public function get($extension_id)
    {
        $extension = $this->getExtension(['extension_id' => $extension_id])->fetch();
        $extension->data = unserialize($extension->data);

        return $extension;
    }

    /**
     * Add an extension
     *
     * @param array $vars An array of input data including:
     *
     *  - company_id The ID of the company with which to associate the extension
     *  - name The name of the extension
     *  - type The type of the extension
     *  - form_type The form type for creating/modifying the extension
     *  - code_examples Whether to include commented code exampled when generating the extension
     *  - data The save form data for the extension
     * @return int The ID of the extension that was created, void on error
     */
    public function add(array $vars)
    {
        $vars['date_updated'] = date('c');

        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $vars['data'] = serialize(isset($vars['data']) ? $vars['data'] : []);
            $fields = ['company_id', 'name', 'type', 'form_type', 'code_examples', 'data', 'date_updated'];
            $this->Record->insert('extension_generator_extensions', $vars, $fields);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Edit an extension
     *
     * @param int $extension_id The ID of the extension to edit
     * @param array $vars An array of input data including:
     *
     *  - company_id The ID of the company with which to associate the extension
     *  - name The name of the extension
     *  - type The type of the extension
     *  - form_type The form type for creating/modifying the extension
     *  - code_examples Whether to include commented code exampled when generating the extension
     *  - data The save form data for the extension
     * @return int The ID of the extension that was updated, void on error
     */
    public function edit($extension_id, array $vars)
    {
        $vars['date_updated'] = date('c');

        $vars['id'] = $extension_id;
        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            if (isset($vars['data'])) {
                $vars['data'] = serialize($vars['data']);
            }
            $fields = ['company_id', 'name', 'type', 'form_type', 'code_examples', 'data', 'date_updated'];
            $this->Record->where('id', '=', $extension_id)->update('extension_generator_extensions', $vars, $fields);

            return $extension_id;
        }
    }

    /**
     * Permanently deletes the given extension
     *
     * @param int $extension_id The ID of the extension to delete
     */
    public function delete($extension_id)
    {
        $extension = $this->get($extension_id);
        if ($extension && isset($extension->data['logo_path'])) {
            @unlink($extension->data['logo_path']);
        }
        // Delete an extension and their associated records
        $this->Record->from('extension_generator_extensions')->
            where('extension_generator_extensions.id', '=', $extension_id)->
            delete();
    }


    /**
     * Returns a partial extension query
     *
     * @param array $filters A list of filters for the query
     *
     *  - company_id The ID of the company to which the extensions must be assigned
     * @return Record A partially built extension query
     */
    private function getExtension(array $filters = [])
    {
        $this->Record->select()->from('extension_generator_extensions');

        if (isset($filters['extension_id'])) {
            $this->Record->where('extension_generator_extensions.id', '=', $filters['extension_id']);
        }

        if (isset($filters['company_id'])) {
            $this->Record->where('extension_generator_extensions.company_id', '=', $filters['company_id']);
        }

        return $this->Record;
    }

    /**
     * Gets a list of extension types and their languages
     *
     * @return A list of extension types and their languages
     */
    public function getTypes()
    {
        return [
            'module' => Language::_('ExtensionGeneratorExtensions.gettypes.module', true),
            'plugin' => Language::_('ExtensionGeneratorExtensions.gettypes.plugin', true),
            'merchant' => Language::_('ExtensionGeneratorExtensions.gettypes.merchant', true),
            'nonmerchant' => Language::_('ExtensionGeneratorExtensions.gettypes.nonmerchant', true)
        ];
    }

    /**
     * Gets a list of form types and their languages
     *
     * @return A list of form types and their languages
     */
    public function getFormTypes()
    {
        return [
            'basic' => Language::_('ExtensionGeneratorExtensions.getformtypes.basic', true),
            'advanced' => Language::_('ExtensionGeneratorExtensions.getformtypes.advanced', true)
        ];
    }

    /**
     * Returns all validation rules for adding/editing extensions
     *
     * @param array $vars An array of input key/value pairs
     *
     *  - company_id The ID of the company with which to associate the extension
     *  - name The name of the extension
     *  - type The type of the extension
     *  - form_type The form type for creating/modifying the extension
     *  - code_examples Whether to include commented code exampled when generating the extension
     *  - data The save form data for the extension
     *  - date_updated The date this extension was updated
     * @param bool $edit True if this if an edit, false otherwise
     * @return array An array of validation rules
     */
    private function getRules(array $vars, $edit = false)
    {
        $rules = [
            'name' => [
                'empty' => [
                    'if_set' => $edit,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('ExtensionGeneratorExtensions.!error.name.empty', true)
                ]
            ],
            'company_id' => [
                'exists' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('ExtensionGeneratorExtensions.!error.company_id.exists', true)
                ]
            ],
            'type' => [
                'valid' => [
                    'if_set' => $edit,
                    'rule' => ['array_key_exists', $this->getTypes()],
                    'message' => $this->_('ExtensionGeneratorExtensions.!error.type.valid', true)
                ]
            ],
            'form_type' => [
                'valid' => [
                    'if_set' => $edit,
                    'rule' => ['array_key_exists', $this->getFormTypes()],
                    'message' => $this->_('ExtensionGeneratorExtensions.!error.form_type.valid', true)
                ]
            ],
            'code_examples' => [
                'valid' => [
                    'if_set' => $edit,
                    'rule' => ['in_array', [0, 1]],
                    'message' => $this->_('ExtensionGeneratorExtensions.!error.code_examples.format', true)
                ]
            ],
            'date_updated' => [
                'format' => [
                    'rule' => 'isDate',
                    'post_format' => [[$this, 'dateToUtc']],
                    'message' => $this->_('ExtensionGeneratorExtensions.!error.date_updated.format', true)
                ]
            ],
        ];

        if ($edit) {
            $rules['id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'extension_generator_extensions'],
                    'message' => $this->_('ExtensionGeneratorExtensions.!error.id.exists', true)
                ]
            ];
        }

        return $rules;
    }
}
