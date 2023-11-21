<?php

/**
 * Coupon term management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class CouponTerms extends AppModel
{
    /**
     * Initialize Coupons
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['coupon_terms']);
    }

    /**
     * Creates a new coupon term
     *
     * @param array $vars An array of coupon term information including:
     *
     *  - coupon_id The coupon ID this coupon term belongs to
     *  - term The number of periods for this coupon term
     *  - period The period for this coupon term
     * @return int The ID for this coupon term
     */
    public function add(array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            // Add coupon term
            $fields = ['coupon_id', 'term', 'period'];
            $this->Record->insert('coupon_terms', $vars, $fields);

            $term_id = $this->Record->lastInsertId();

            return $term_id;
        }
    }

    /**
     * Edits a coupon term
     *
     * @param int $term_id The ID of the coupon term to update
     * @param array $vars An array of coupon term information including:
     *
     *  - coupon_id The coupon ID this coupon term belongs to
     *  - term The number of periods for this coupon term
     *  - period The period for this coupon term
     */
    public function edit($term_id, array $vars)
    {
        $vars['term_id'] = $term_id;
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            // Add coupon term
            $fields = ['coupon_id', 'term', 'period'];
            $this->Record->where('id', '=', $term_id)->update('coupon_terms', $vars, $fields);
        }
    }

    /**
     * Permanently removes the coupon term from the system
     *
     * @param int $coupon_id The ID of the coupon to delete terms for
     * @param int $term_id The ID of the coupon term to delete (optional)
     */
    public function delete($coupon_id, $term_id = null)
    {
        $this->Record->from('coupon_terms')
            ->where('coupon_terms.coupon_id', '=', $coupon_id);

        if ($term_id) {
            $this->Record->where('coupon_terms.id', '=', $term_id);
        }

        $this->Record->delete();
    }

    /**
     * Gets the specified coupon term if it meets the given criteria
     *
     * @param int $term_id The ID of the coupon term to fetch
     * @param array $criteria A list of criteria to filter by, including:
     *
     *  - coupon_id The coupon ID this coupon term belongs to
     *  - term The number of periods for this coupon term
     *  - period The period for this coupon term
     * @return The given coupon term, false on failure
     */
    public function get($term_id, array $criteria = [])
    {
        $criteria['id'] = $term_id;
        return $this->getTerms($criteria)->fetch();
    }

    /**
     * Gets all coupon terms that meet the given criteria
     *
     * @param array $criteria A list of criteria to filter by, including:
     *
     *  - coupon_id The coupon ID this coupon term belongs to
     *  - term The number of periods for this coupon term
     *  - period The period for this coupon term
     */
    public function getAll(array $criteria = [])
    {
        return $this->getTerms($criteria)->fetchAll();
    }

    /**
     * Gets all coupon terms that meet the given criteria
     *
     * @param array $criteria A list of criteria to filter by, including:
     *
     *  - id The ID of the coupon term to fetch
     *  - coupon_id The coupon ID this coupon term belongs to
     *  - term The number of periods for this coupon term
     *  - period The period for this coupon term
     * @return Record The partially constructed query Record object
     */
    private function getTerms(array $criteria = [])
    {
        $this->Record->select()->from('coupon_terms');

        $white_list = ['id', 'coupon_id', 'term', 'period'];

        foreach ($criteria as $field => $value) {
            if (in_array($field, $white_list)) {
                $this->Record->where($field, '=', $value);
            }
        }

        return $this->Record;
    }

    /**
     * Gets a list of valid periods for coupon terms
     *
     * @return array A list of valid periods and their language
     */
    public function getPeriods()
    {
        return [
            'day' => $this->_('CouponTerms.getperiods.day'),
            'week' => $this->_('CouponTerms.getperiods.week'),
            'month' => $this->_('CouponTerms.getperiods.month'),
            'year' => $this->_('CouponTerms.getperiods.year'),
            'onetime' => $this->_('CouponTerms.getperiods.onetime')
        ];
    }

    /**
     * Returns the rule set for adding/editing coupon terms
     *
     * @param array $vars A list of input vars
     * @param bool $edit True to get the edit rules, false for the add rules
     * @return array Coupon term rules
     */
    private function getRules(array $vars, $edit = false)
    {
        $rules = [
            'coupon_id' => [
                'exists' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateExists'], 'id', 'coupons'],
                    'message' => $this->_('CouponTerms.!error.coupon_id.exists')
                ],
                'unique' => [
                    'if_set' => $edit,
                    'rule' => function ($coupon_id) use ($vars) {
                        return !(bool)$this->Record->select()
                            ->from('coupon_terms')
                            ->where('coupon_id', '=', $coupon_id)
                            ->where('term', '=', (isset($vars['term']) ? $vars['term'] : null))
                            ->where('period', '=', (isset($vars['period']) ? $vars['period'] : null))
                            ->where('id', '!=', (isset($vars['term_id']) ? $vars['term_id'] : null))
                            ->fetch();
                    },
                    'message' => $this->_('CouponTerms.!error.coupon_id.exists')
                ]
            ],
            'term' => [
                'format' => [
                    'if_set' => $edit,
                    'rule' => 'is_numeric',
                    'message' => $this->_('CouponTerms.!error.term.format')
                ]
            ],
            'period' => [
                'valid' => [
                    'if_set' => $edit,
                    'rule' => ['array_key_exists', $this->getPeriods()],
                    'message' => $this->_('CouponTerms.!error.period.valid')
                ],
            ]
        ];

        // Set edit-specific rules
        if ($edit) {
            $rules['term_id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'coupon_terms'],
                    'message' => $this->_('CouponTerms.!error.term_id.exists')
                ]
            ];
        }

        return $rules;
    }
}
