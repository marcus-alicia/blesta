<?php
/**
 * SupportManagerResponses model
 *
 * @package blesta
 * @subpackage blesta.plugins.support_manager
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SupportManagerResponses extends SupportManagerModel
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        Language::loadLang('support_manager_responses', null, PLUGINDIR . 'support_manager' . DS . 'language' . DS);
    }

    /**
     * Adds a new response
     *
     * @param array $vars A list of input vars, including:
     *  - category_id The ID of the category this response is assigned to
     *  - name The name of the response
     *  - details The details (response)
     * @return mixed An stdClass object representing the predefined response, or void on error
     */
    public function add(array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            // Add the response
            $fields = ['category_id', 'name', 'details'];
            $this->Record->insert('support_responses', $vars, $fields);
            $response_id = $this->Record->lastInsertId();

            return $this->get($response_id);
        }
    }

    /**
     * Updates a response
     *
     * @param int $response_id The ID of the response to update
     * @param array $vars A list of input vars, including:
     *  - category_id The ID of the category this response is assigned to
     *  - name The name of the response
     *  - details The details (response)
     * @return mixed An stdClass object representing the predefined response, or void on error
     */
    public function edit($response_id, array $vars)
    {
        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            // Add the response
            $fields = ['category_id', 'name', 'details'];
            $this->Record->where('id', '=', $response_id)->
                update('support_responses', $vars, $fields);

            return $this->get($response_id);
        }
    }

    /**
     * Deletes a response
     *
     * @param int $response_id The ID of the response to delete
     */
    public function delete($response_id)
    {
        $this->Record->from('support_responses')->
            where('id', '=', $response_id)->
            delete();
    }

    /**
     * Retrieves a predefined response
     *
     * @param int $response_id The ID of the predefined response to fetch
     * @return mixed An stdClass object representing the predefined response, or false if none exist
     */
    public function get($response_id)
    {
        $fields = ['support_responses.*', 'support_response_categories.company_id'];
        return $this->Record->select($fields)
            ->from('support_responses')
            ->innerJoin(
                'support_response_categories',
                'support_response_categories.id',
                '=',
                'support_responses.category_id',
                false
            )
            ->where('support_responses.id', '=', $response_id)
            ->fetch();
    }

    /**
     * Retrieves a list of all responses from the given category
     *
     * @param int $company_id The ID of the company
     * @param int $category_id The ID of the category to fetch responses from
     * @return array A list of stdClass objects representing responses
     */
    public function getAll($company_id, $category_id)
    {
        $fields = ['support_responses.*', 'support_response_categories.company_id'];
        return $this->Record->select($fields)
            ->from('support_responses')
            ->innerJoin(
                'support_response_categories',
                'support_response_categories.id',
                '=',
                'support_responses.category_id',
                false
            )
            ->where('support_response_categories.company_id', '=', $company_id)
            ->where('support_responses.category_id', '=', $category_id)
            ->order(['support_responses.name' => 'ASC'])
            ->fetchAll();
    }


    /**
     * Adds a new category
     *
     * @param array $vars A list of input vars, including:
     *  - company_id The ID of the company this category belongs to
     *  - parent_id The ID of the parent category to assign this category to
     *  - name The name of the category
     * @return stdClass An stdClass object representing the category, or void on error
     */
    public function addCategory(array $vars)
    {
        $this->Input->setRules($this->getCategoryRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ['company_id', 'parent_id', 'name'];
            $this->Record->insert('support_response_categories', $vars, $fields);
            $category_id = $this->Record->lastInsertId();

            return $this->getCategory($category_id);
        }
    }

    /**
     * Updates a category
     *
     * @param int $category_id The ID of the category to update
     * @param array $vars A list of input vars, including:
     *  - parent_id The ID of the parent category to assign this category to
     *  - name The name of the category
     * @return stdClass An stdClass object representing the category, or void on error
     */
    public function editCategory($category_id, array $vars)
    {
        $this->Input->setRules($this->getCategoryRules($vars, true));

        if ($this->Input->validates($vars)) {
            $fields = ['parent_id', 'name'];
            $this->Record->where('id', '=', $category_id)->
                update('support_response_categories', $vars, $fields);

            return $this->getCategory($category_id);
        }
    }

    /**
     * Deletes a response category and moves
     *
     * @param int $category_id The ID of the category to delete
     */
    public function deleteCategory($category_id)
    {
        $vars = ['category_id' => $category_id];
        $this->Input->setRules($this->getCategoryDeleteRules());

        // Fetch the category
        $category = $this->getCategory($category_id);

        if ($this->Input->validates($vars)) {
            // Begin a transaction
            $this->Record->begin();

            // Update all children of this category to be in the parent category
            $this->Record->where('parent_id', '=', $category->id)->
                update('support_response_categories', ['parent_id'=>$category->parent_id]);

            // Update responses in this category to be in the parent category
            $this->Record->where('category_id', '=', $category->id)->
                update('support_responses', ['category_id'=>$category->parent_id]);

            // Finally, delete this category
            $this->Record->from('support_response_categories')->
                where('id', '=', $category->id)->delete();

            // Commit transaction
            $this->Record->commit();
        }
    }

    /**
     * Retrieves a category
     *
     * @param int $category_id The ID of the category to fetch
     * @return mixed An stdClass object representing the category, or false if none exist
     */
    public function getCategory($category_id)
    {
        return $this->Record->select()->from('support_response_categories')->
            where('id', '=', $category_id)->
            fetch();
    }

    /**
     * Retrieves a list of all categories from the given category
     *
     * @param int $company_id The ID of the company
     * @param int $category_id The ID of the category to fetch categories from
     * @return array A list of stdClass objects representing categories
     */
    public function getAllCategories($company_id, $category_id = null)
    {
        return $this->Record->select()->from('support_response_categories')->
            where('company_id', '=', $company_id)->
            where('parent_id', '=', $category_id)->
            order(['name' => 'ASC'])->
            fetchAll();
    }

    /**
     * Validates that the given category belongs to the company given
     *
     * @param int $category_id The ID of the category
     * @param int $company_id The ID of the company
     */
    public function validateCategoryCompany($category_id, $company_id)
    {
        $count = $this->Record->select()->from('support_response_categories')->
            where('id', '=', $category_id)->where('company_id', '=', $company_id)->
            numResults();

        return ($count > 0);
    }

    /**
     * Validates that the given category can be deleted
     *
     * @param int $category_id The ID of the category to delete
     * @return bool True if the category can be deleted, false otherwise
     */
    public function validateDeleteCategory($category_id)
    {
        // Fetch the number of responses that belong to this category where this category has no parent
        $count = $this->Record->select(['support_responses.id'])
            ->from('support_responses')
            ->innerJoin(
                'support_response_categories',
                'support_response_categories.id',
                '=',
                'support_responses.category_id',
                false
            )
            ->where('support_responses.category_id', '=', $category_id)
            ->where('support_response_categories.parent_id', '=', null)
            ->numResults();

        // Must return no results to validate
        return ($count == 0);
    }

    /**
     * Retrieves the rules for deleting a category
     *
     * @return array A list of rules
     */
    private function getCategoryDeleteRules()
    {
        $rules = [
            'category_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'support_response_categories'],
                    'message' => $this->_('SupportManagerResponses.!error.category_id.exists')
                ],
                'root_responses' => [
                    'rule' => [[$this, 'validateDeleteCategory']],
                    'message' => $this->_('SupportManagerResponses.!error.category_id.root_responses')
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Retrieves the rules for adding/editing a category
     *
     * @param array $vars A list of input vars to validate
     * @param bool $edit True to fetch the edit rules, false for the add rules (optional, default false)
     * @return array A list of rules
     */
    private function getCategoryRules(array $vars, $edit = false)
    {
        $rules = [
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('SupportManagerResponses.!error.company_id.exists')
                ]
            ],
            'parent_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'support_response_categories'],
                    'message' => $this->_('SupportManagerResponses.!error.parent_id.exists')
                ],
                'company' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateCategoryCompany'], (isset($vars['company_id']) ? $vars['company_id'] : null)],
                    'message' => $this->_('SupportManagerResponses.!error.parent_id.company')
                ]
            ],
            'name'=> [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('SupportManagerResponses.!error.name.empty')
                ],
                'length' => [
                    'rule' => ['maxLength', 64],
                    'message' => $this->_('SupportManagerResponses.!error.name.length')
                ]
            ]
        ];

        if ($edit) {
            // Remove unnecessary rules
            unset($rules['company_id']);

            // Set fields to optional
            $rules = $this->setRulesIfSet($rules);
        }

        return $rules;
    }

    /**
     * Retrieves the rules for adding/editing a response
     *
     * @param array $vars A list of input vars to validate
     * @param bool $edit True to fetch the edit rules, false for the add rules (optional, default false)
     * @return array A list of rules
     */
    private function getRules(array $vars, $edit = false)
    {
        $rules = [
            'category_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'support_response_categories'],
                    'message' => $this->_('SupportManagerResponses.!error.category_id.exists')
                ]
            ],
            'name'=> [
                'response_empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('SupportManagerResponses.!error.name.response_empty')
                ],
                'response_length' => [
                    'rule' => ['maxLength', 64],
                    'message' => $this->_('SupportManagerResponses.!error.name.response_length')
                ]
            ],
            'details' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('SupportManagerResponses.!error.details.empty')
                ]
            ]
        ];

        if ($edit) {
            // Set fields to optional
            $rules = $this->setRulesIfSet($rules);
        }

        return $rules;
    }
}
