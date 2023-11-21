<?php
/**
 * Download Manager Categories
 *
 * Manages download categories
 *
 * @package blesta
 * @subpackage blesta.plugins.download_manager.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

class DownloadManagerCategories extends DownloadManagerModel
{
    /**
     * Initialize
     */
    public function __construct()
    {
        parent::__construct();

        Language::loadLang('download_manager_categories', null, PLUGINDIR . 'download_manager' . DS . 'language' . DS);
    }

    /**
     * Creates a category
     *
     * @param array $vars A list of category input vars, including:
     *  - parent_id The ID of the parent category to this category (optional, default null)
     *  - name The name of the category
     *  - description A description of this category
     * @return stdClass An stdClass object representing the newly created category, or void on error
     * @see DownloadManagerCategories::get()
     */
    public function add(array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            // Create the category
            $fields = ['parent_id', 'company_id', 'name', 'description'];
            $this->Record->insert('download_categories', $vars, $fields);

            return $this->get($this->Record->lastInsertId());
        }
    }

    /**
     * Updates a category
     *
     * @param int $category_id The ID of the category to update
     * @param array $vars A list of category input vars, including:
     *  - parent_id The ID of the parent category to this category (optional, default null)
     *  - name The name of the category
     *  - description A description of this category
     * @return stdClass An stdClass object representing the category, or void on error
     * @see DownloadManagerCategories::get()
     */
    public function edit($category_id, array $vars)
    {
        $vars['category_id'] = $category_id;
        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            // Update the category
            $fields = ['parent_id', 'company_id', 'name', 'description'];
            $this->Record->where('id', '=', $category_id)->update('download_categories', $vars, $fields);

            return $this->get($category_id);
        }
    }

    /**
     * Deletes the category and moves all child categories to this categories' parent
     * along with this categories' files
     *
     * @param int $category_id The ID of the category to delete
     */
    public function delete($category_id)
    {
        // Get the category
        $category = $this->get($category_id);

        // Delete the category
        if ($category) {
            // Begin a transaction
            $this->Record->begin();

            // Update all children of this category to be in the parent category
            $this->Record->where('parent_id', '=', $category->id)->
                update('download_categories', ['parent_id'=>$category->parent_id]);

            // Update files in this category to be in the parent category
            $this->Record->where('category_id', '=', $category->id)->
                update('download_files', ['category_id'=>$category->parent_id]);

            // Finally, delete this category
            $this->Record->from('download_categories')->where('id', '=', $category->id)->delete();

            // Commit the transaction
            $this->Record->commit();
        }
    }

    /**
     * Fetches a specific category
     *
     * @param int $category_id The ID of the category to fetch
     * @return mixed An stdClass object representing the category, or false if one does not exist
     */
    public function get($category_id)
    {
        return $this->getCategories()->where('id', '=', $category_id)->fetch();
    }

    /**
     * Fetches all categories
     *
     * @param int $company_id The ID of the company from which to fetch categories
     * @param int $parent_id The parent category ID whose categories to fetch
     * @return array A list of categories with the given parent ID
     */
    public function getAll($company_id, $parent_id = null)
    {
        $this->Record = $this->getCategories();

        return $this->Record->where('parent_id', '=', $parent_id)->
            where('company_id', '=', $company_id)->
            order(['name'=>'ASC'])->fetchAll();
    }

    /**
     * Retrieves all parent categories for the given category, including the given category
     *
     * @param int $category_id The child category for which to retrieve parents for
     * @param array $exclude An array of parent categories to exclude from the results (optional, default empty array)
     * @return array A numerically-indexed array of categories ordered
     *  by the highest category down to, and including, the given category
     */
    public function getAllParents($category_id, array $exclude = [])
    {
        // Get this category
        $category = $this->get($category_id);

        // Get parents for this category and avoid infinite loop
        if ($category->parent_id !== null && !in_array($category->parent_id, $exclude)) {
            return array_merge(
                $this->getAllParents($category->parent_id, array_merge($exclude, [$category->id])),
                [$category]
            );
        }

        return [$category];
    }

    /**
     * Partially constructs a Record object for fetching categories
     *
     * @return Record A partially-constructed Record object
     */
    private function getCategories()
    {
        return $this->Record->select()->from('download_categories');
    }

    /**
     * Retrieves a list of rules to validate add/editing categories
     *
     * @param array $vars A list of input vars to validate
     * @param bool $edit True to fetch the edit rules, false to fetch the add rules (optional, default false)
     * @return array A list of rules
     */
    private function getRules(array $vars, $edit = false)
    {
        $rules = [
            'parent_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'download_categories'],
                    'message' => $this->_('DownloadManagerCategories.!error.parent_id.exists')
                ]
            ],
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('DownloadManagerCategories.!error.company_id.exists')
                ]
            ],
            'name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('DownloadManagerCategories.!error.name.empty')
                ]
            ],
            'description' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('DownloadManagerCategories.!error.description.empty')
                ]
            ]
        ];

        if ($edit) {
            // Update rules
            $rules['category_id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'download_categories'],
                    'message' => $this->_('DownloadManagerCategories.!error.category_id.exists')
                ]
            ];

            // Make sure the parent does not reference itself
            $rules['parent_id']['loop'] = [
                'if_set' => true,
                'rule' => ['matches', '!=', (isset($vars['category_id']) ? $vars['category_id'] : null)],
                'message' => $this->_('DownloadManagerCategories.!error.parent_id.loop')
            ];
        }

        return $rules;
    }
}
