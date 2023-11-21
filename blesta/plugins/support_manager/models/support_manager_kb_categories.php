<?php
/**
 * SupportManagerKbCategories model
 *
 * @package blesta
 * @subpackage blesta.plugins.support_manager
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SupportManagerKbCategories extends SupportManagerModel
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        Language::loadLang('support_manager_kb_categories', null, PLUGINDIR . 'support_manager' . DS . 'language' . DS);
    }

    /**
     * Creates a new knowledge base category
     *
     * @param array $vars A list of category fields including:
     *  - parent_id The category ID of this child category's parent (optional)
     *  - company_id The ID of the company to assign this category to
     *  - name The name of this category
     *  - description The category description (optional)
     *  - access The type of access to give to this category ("public", "private", "hidden", or "staff"; optional, default public)
     * @return stdClass An stdClass object representing the category, or void on error
     */
    public function add(array $vars)
    {
        // Set dates
        $vars['date_created'] = date('c');
        $vars['date_updated'] = $vars['date_created'];

        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ['parent_id', 'company_id', 'name', 'description', 'access', 'date_created', 'date_updated'];
            $this->Record->insert('support_kb_categories', $vars, $fields);
            return $this->get($this->Record->lastInsertId());
        }
    }

    /**
     * Updates a knowledge base category
     *
     * @param int $category_id The ID of the category to edit
     * @param array $vars A list of category fields including (all optional):
     *  - parent_id The category ID of this child category's parent
     *  - name The name of this category
     *  - description The category description
     *  - access The type of access to give to this category ("public", "private", "hidden", or "staff")
     * @return stdClass An stdClass object representing the category, or void on error
     */
    public function edit($category_id, array $vars)
    {
        // Set date, category
        $vars['date_updated'] = date('c');
        $vars['category_id'] = $category_id;

        // Company can't be changed. Set it to its current value for validation
        unset($vars['company_id']);
        if (($category = $this->get($category_id))) {
            $vars['company_id'] = $category->company_id;
        }

        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            $fields = ['parent_id', 'name', 'description', 'access', 'date_updated'];
            $this->Record->where('id', '=', $category_id)->update('support_kb_categories', $vars, $fields);
            return $this->get($category_id);
        }
    }

    /**
     * Deletes a knowledge base category
     *
     * @param int $category_id The ID of the category to delete
     */
    public function delete($category_id)
    {
        // In order to delete a category that has articles within it,
        // the articles must belong to another category as well
        $rules = [
            'category_id' => [
                'parent' => [
                    'rule' => [[$this, 'validateCategoryDeletion'], $category_id],
                    'message' => $this->_('SupportManagerKbCategories.!error.articles.parent')
                ]
            ]
        ];

        $vars = ['category_id' => $category_id];
        $this->Input->setRules($rules);

        if ($this->Input->validates($vars) && ($category = $this->get($category_id))) {
            $this->Record->begin();

            // Set any children to use this articles' parent
            $this->Record->where('parent_id', '=', $category_id)->
                update('support_kb_categories', ['parent_id' => $category->parent_id]);

            // Remove articles assigned to this category along with this category
            $this->Record->from('support_kb_categories')
                ->leftJoin(
                    'support_kb_article_categories',
                    'support_kb_article_categories.category_id',
                    '=',
                    'support_kb_categories.id',
                    false
                )
                ->where('support_kb_categories.id', '=', $category_id)
                ->delete(['support_kb_categories.*', 'support_kb_article_categories.*']);

            $this->Record->commit();
        }
    }

    /**
     * Fetches the total number of categories and articles directly descended from the given category
     *
     * @param int $category_id The ID of the category whose item count to fetch
     * @param mixed $access A numerically-indexed array containing the access
     *  levels of articles/categories that can be fetched, or null for all
     *  (i.e. "public", "private", "hidden", or "staff"; optional, default null for all)
     * @return array An array containing:
     *  - articles The number of articles within this category
     *  - categories The number of subcategories within this category
     */
    public function getItemCount($category_id, $access = null)
    {
        // Fetch the number of articles within this category
        $this->Record->select(['support_kb_articles.id'])
            ->from('support_kb_articles')
            ->innerJoin(
                'support_kb_article_categories',
                'support_kb_article_categories.article_id',
                '=',
                'support_kb_articles.id',
                false
            )
            ->where('support_kb_article_categories.category_id', '=', $category_id);

        // Filter by access level
        if ($access !== null && is_array($access)) {
            $this->Record->where('support_kb_articles.access', 'in', array_values($access));
        }

        $total_articles = $this->Record->numResults();

        // Fetch the number of subcategories within this category
        $this->Record->select(['support_kb_categories.id'])->from('support_kb_categories')->
            where('support_kb_categories.parent_id', '=', $category_id);

        // Filter by access level
        if ($access !== null && is_array($access)) {
            $this->Record->where('support_kb_categories.access', 'in', array_values($access));
        }

        $total_categories = $this->Record->numResults();

        return [
            'articles' => $total_articles,
            'categories' => $total_categories
        ];
    }

    /**
     * Retrieves a category
     *
     * @param int $category_id The ID of the category to fetch
     * @return mixed An stdClass object representing the category, or false if it does not exist
     */
    public function get($category_id)
    {
        $this->Record = $this->getCategories($category_id);

        return $this->Record->fetch();
    }

    /**
     * Retrieves a list of all categories for the given company that are first-descendants under the given parent,
     * unless $parent_id is boolean false, or $categorize is set to true,
     * in which case all categories for the company are returned
     *
     * @param int $company_id The ID of the company whose categories to fetch
     * @param mixed $parent_id The ID of the category whose children to fetch; null for no parent
     *  (i.e. base/home category); or boolean false to ignore the parent (optional, default false)
     * @param bool $categorize True to nest categories within their respective parents
     *  (set a $parent_id to avoid duplicate results), or false for a flattened list (optional, default false)
     * @param mixed $access A numerically-indexed array containing the access levels of
     *  categories that can be fetched, or null for all
     *  (i.e. "public", "private", "hidden", or "staff"; optional, default null for all)
     * @return array A list of categories. If $categorize is true, each parent category
     *  will contain the attribute 'children' with a further list of nested categories
     */
    public function getAll($company_id, $parent_id = false, $categorize = false, $access = null)
    {
        $this->Record = $this->getCategories(null, $company_id);

        if ($parent_id !== false) {
            $this->Record->where('parent_id', '=', $parent_id);
        }

        // Filter by access level
        if ($access !== null && is_array($access)) {
            $this->Record->where('support_kb_categories.access', 'in', array_values($access));
        }

        $categories = $this->Record->order(['name'=>'ASC'])->fetchAll();

        foreach ($categories as &$category) {
            $items = $this->getItemCount($category->id, $access);
            $category->total_items = ($items['articles'] + $items['categories']);

            // Build a list of nested categories
            if ($categorize) {
                $category->children = array_merge([], $this->getAll($company_id, $category->id, $categorize));
            }
        }

        return $categories;
    }

    /**
     * Retrieves all parent categories for the given category, including the given category
     *
     * @param int $category_id The child category for which to retrieve parents for
     * @param array $exclude An array of parent categories to exclude from the results (optional, default empty array)
     * @return array A numerically-indexed array of categories ordered by the highest
     *  category down to, and including, the given category
     */
    public function getAllParents($category_id, array $exclude = [])
    {
        // Get this category
        $category = $this->get($category_id);

        // Get parents for this category and avoid infinite loop
        if ($category && $category->parent_id !== null && !in_array($category->parent_id, $exclude)) {
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
     * @param int $category_id The ID of the category to fetch (optional)
     * @param int $company_id The ID of the company whose categories to fetch (optional)
     * @return Record A partially-constructed Record object
     */
    private function getCategories($category_id = null, $company_id = null)
    {
        $this->Record->select()->from('support_kb_categories');

        if ($category_id) {
            $this->Record->where('id', '=', $category_id);
        }
        if ($company_id) {
            $this->Record->where('company_id', '=', $company_id);
        }

        return $this->Record;
    }

    /**
     * Retrieves a list of access types and their language
     *
     * @return array An array of access types and their language
     */
    public function getAccessTypes()
    {
        return [
            'public' => $this->_('SupportManagerKbCategories.access_types.public'),
            'private' => $this->_('SupportManagerKbCategories.access_types.private'),
            'hidden' => $this->_('SupportManagerKbCategories.access_types.hidden'),
            'staff' => $this->_('SupportManagerKbCategories.access_types.staff')
        ];
    }

    /**
     * Retrieves rules for validating adding/editing a category
     *
     * @param array $vars A set of input data
     * @param bool $edit True to fetch the edit rules, or false for the add rules (optional, default false)
     * @return array A set of validation rules
     */
    private function getRules(array $vars, $edit = false)
    {
        $rules = [
            'parent_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'support_kb_categories'],
                    'message' => $this->_('SupportManagerKbCategories.!error.parent_id.exists')
                ],
                'valid_company' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateCompanyMatches'], (isset($vars['company_id']) ? $vars['company_id'] : null)],
                    'message' => $this->_('SupportManagerKbCategories.!error.parent_id.valid_company')
                ],
                'valid_parent' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateParent'], (isset($vars['category_id']) ? $vars['category_id'] : null)],
                    'message' => $this->_('SupportManagerKbCategories.!error.parent_id.valid_parent')
                ]
            ],
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('SupportManagerKbCategories.!error.company_id.exists')
                ]
            ],
            'name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('SupportManagerKbCategories.!error.name.empty')
                ],
                'length' => [
                    'rule' => ['maxLength', 255],
                    'message' => $this->_('SupportManagerKbCategories.!error.name.length')
                ]
            ],
            'access' => [
                'type' => [
                    'if_set' => true,
                    'rule' => ['in_array', array_keys($this->getAccessTypes())],
                    'message' => $this->_('SupportManagerKbCategories.!error.access.type')
                ]
            ],
            'date_created' => [
                'valid' => [
                    'rule' => true,
                    'message' => '',
                    'post_format' => [[$this, 'dateToUtc']]
                ]
            ],
            'date_updated' => [
                'valid' => [
                    'rule' => true,
                    'message' => '',
                    'post_format' => [[$this, 'dateToUtc']]
                ]
            ],
        ];

        if ($edit) {
            // Editing cannot change company
            unset($rules['company_id']);

            // All rules optional
            $rules = $this->setRulesIfSet($rules);

            // Validate the category itself exists to edit
            $rules['category_id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'support_kb_categories'],
                    'message' => $this->_('SupportManagerKbCategories.!error.category_id.exists')
                ]
            ];
        }

        return $rules;
    }

    /**
     * Validates that the given parent category belongs to the same company as the current category
     *
     * @param int $parent_category_id The ID of the parent category to validate
     * @param int $company_id The ID of the company to check against
     * @return bool True if the parent does not exist or matches the company, false otherwise
     */
    public function validateCompanyMatches($parent_category_id, $company_id)
    {
        $parent = $this->get($parent_category_id);

        if ($parent && $parent->company_id != $company_id) {
            return false;
        }
        return true;
    }

    /**
     * Validates that the given parent category can be assigned to the given category
     *
     * @param int $parent_category_id The ID of the parent category
     * @param int $category_id The ID of the category to assign the parent to
     * @return bool True if the category can be assigned to the parent, or false otherwise
     */
    public function validateParent($parent_category_id, $category_id)
    {
        // Cannot assign a category parent to itself
        if (!empty($category_id)) {
            return ($parent_category_id != $category_id);
        }
        return true;
    }

    /**
     * Validates whether the given category can be deleted
     *
     * @param int $category_id The ID of the category to delete
     * @return bool True if the category can be deleted, or false otherwise
     */
    public function validateCategoryDeletion($category_id)
    {
        // Fetch articles that belong to this category
        $this->Record->select(['support_kb_article_categories.article_id'])->
            from('support_kb_article_categories')->
            where('support_kb_article_categories.category_id', '=', $category_id);

        $articles = clone $this->Record;
        $num_articles = $articles->numResults();
        unset($articles);

        $sub_query = $this->Record->get();
        $values = $this->Record->values;
        $this->Record->reset();

        // Fetch the other categories that each article belongs to other than this category, limited to 1 apiece
        $count = $this->Record->select(['support_kb_article_categories.category_id'])
            ->from('support_kb_article_categories')
            ->appendValues($values)
            ->innerJoin(
                [$sub_query => 'articles'],
                'articles.article_id',
                '=',
                'support_kb_article_categories.article_id',
                false
            )
            ->where('support_kb_article_categories.category_id', '!=', $category_id)
            ->group(['articles.article_id'])
            ->numResults();

        // Each article belongs to at least 1 other category
        return ($count == $num_articles);
    }
}
