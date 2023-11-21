<?php

/**
 * Pricing management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Pricings extends AppModel
{
    /**
     * Initialize Pricings
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['pricings']);
    }

    /**
     * Fetches the pricing
     *
     * @param int $pricing_id The ID of the pricing to fetch
     * @return mixed A stdClass object representing the pricing, false if no such pricing exists
     */
    public function get($pricing_id)
    {
        return $this->Record->select()->from('pricings')->
            where('pricings.id', '=', $pricing_id)->fetch();
    }

    /**
     * Fetches all pricing for a given company
     *
     * @param int $company_id The company ID
     * @return array An array of stdClass objects representing each pricing
     */
    public function getAll($company_id)
    {
        return $this->Record->select()->from('pricings')->
            where('pricings.company_id', '=', $company_id)->fetchAll();
    }

    /**
     * Fetches a list of pricing for a given company
     *
     * @param int $company_id The company ID to fetch pricing for
     * @param int $page The page to return results for
     * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @return array An array of objects, each representing a pricing
     */
    public function getList($company_id, $page = 1, array $order_by = ['period' => 'asc', 'term' => 'asc'])
    {
        return $this->Record->select()->from('pricings')->
            where('pricings.company_id', '=', $company_id)->
            order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->
            fetchAll();
    }

    /**
     * Return the total number of pricings returned from Pricings::getList(),
     * useful in constructing pagination for the getList() method.
     *
     * @param int $company_id The company ID to fetch pricings for
     * @return int The total number of pricings
     * @see Pricings::getList()
     */
    public function getListCount($company_id)
    {
        return $this->Record->select()->from('pricings')->
            where('pricings.company_id', '=', $company_id)->numResults();
    }

    /**
     * Adds a pricing for the given company
     *
     * @param array $vars An array of pricing info including:
     *
     *  - company_id The ID of the company to add the pricing for
     *  - term The term as an integer 1-65535 (optional, default 1)
     *  - period The period, 'day', 'week', 'month', 'year', 'onetime' (optional, default 'month')
     *  - price The price of this term (optional, default 0.00)
     *  - price_renews The renewal price of this term (optional, default null)
     *  - price_transfer The transfer price of this term (optional, default null)
     *  - setup_fee The setup fee for this pricing (optional, default 0.00)
     *  - cancel_fee The cancelation fee for this pricing (optional, default 0.00)
     *  - currency The ISO 4217 currency code for this pricing (optional, default USD)
     * @return int The ID of the pricing record added, void on error
     */
    public function add(array $vars)
    {
        if (!isset($vars['company_id'])) {
            $vars['company_id'] = Configure::get('Blesta.company_id');
        }

        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ['company_id', 'term', 'period', 'price', 'price_renews', 'price_transfer', 'setup_fee', 'cancel_fee', 'currency'];
            $this->Record->insert('pricings', $vars, $fields);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Updates a pricing
     *
     * @param int $pricing_id The ID of the pricing to edit
     * @param array $vars An array of pricing info including:
     *
     *  - term The term as an integer 1-65535 (optional, default 1)
     *  - period The period, 'day', 'week', 'month', 'year', 'onetime' (optional, default 'month')
     *  - price The price of this term (optional, default 0.00)
     *  - price_renews The renewal price of this term (optional, default null)
     *  - price_transfer The transfer price of this term (optional, default null)
     *  - setup_fee The setup fee for this pricing (optional, default 0.00)
     *  - cancel_fee The cancelation fee for this pricing (optional, default 0.00)
     *  - currency The ISO 4217 currency code for this pricing (optional, default USD)
     */
    public function edit($pricing_id, array $vars)
    {
        $this->Input->setRules($this->getRules($vars, true, $pricing_id));

        if ($this->Input->validates($vars)) {
            $fields = ['term', 'period', 'price', 'price_renews', 'price_transfer', 'setup_fee', 'cancel_fee', 'currency'];
            $this->Record->where('pricings.id', '=', $pricing_id)->
                update('pricings', $vars, $fields);
        }
    }

    /**
     * Permanently removes a pricing from the system.
     *
     * @param int $pricing_id The pricing ID to delete
     */
    public function delete($pricing_id)
    {
        $this->Record->from('pricings')->
            where('pricings.id', '=', $pricing_id)->delete();
    }

    /**
     * Validates that the term is valid for the period. That is, the term must be > 0
     * if the period is something other than "onetime".
     *
     * @param int $term The Term to validate
     * @param string $period The period to validate the term against
     * @return bool True if validated, false otherwise
     */
    public function validateTerm($term, $period)
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
    public function validatePeriod($period)
    {
        $periods = $this->getPeriods();

        if (isset($periods[$period])) {
            return true;
        }
        return false;
    }

    /**
     * Formats the pricing term
     *
     * @param int $term The term length
     * @param string $period The period of this term
     * @return mixed The term formatted in accordance to the period, if possible
     */
    public function formatTerm($term, $period)
    {
        if ($period == 'onetime') {
            return 0;
        }
        return $term;
    }

    /**
     * Checks if the pricing it's in use
     *
     * @param mixed $value The value of the variable to validate
     * @param string $var The name of the variable to validate
     * @param int $pricing_id
     */
    public function checkInUse($value, $var, $pricing_id)
    {
        // Get services using the current pricing
        $services = $this->Record->select()
            ->from('services')
            ->innerJoin('package_pricing', 'package_pricing.id', '=', 'services.pricing_id', false)
            ->where('package_pricing.pricing_id', '=', $pricing_id)
            ->where('services.status', '!=', 'canceled')
            ->fetchAll();

        if (empty($services)) {
            return true;
        }

        // Get the current value of the variable to validate
        $pricing = (array) $this->get($pricing_id);
        if (isset($pricing[$var]) && $value == $pricing[$var]) {
            return true;
        }

        return false;
    }

    /**
     * Retrieves a list of pricing periods
     *
     * @param bool $plural True to return language for plural periods, false for singular
     * @return array Key=>value pairs of pricing periods
     */
    public function getPeriods($plural = false)
    {
        $type = '';
        if ($plural) {
            $type = '_plural';
        }

        return [
            'day' => $this->_('Pricings.getPeriods.day' . $type),
            'week' => $this->_('Pricings.getPeriods.week' . $type),
            'month' => $this->_('Pricings.getPeriods.month' . $type),
            'year' => $this->_('Pricings.getPeriods.year' . $type),
            'onetime' => $this->_('Pricings.getPeriods.onetime' . $type)
        ];
    }

    /**
     * Fetches the rules for adding/editing pricing
     *
     * @param array $vars The input vars
     * @param bool $edit True to fetch the edit rules, or false for the add rules
     * @param int $pricing_id The ID of the pricing being updated (on edit)
     * @return array The pricing rules
     */
    private function getRules(array $vars, $edit = false, $pricing_id = null)
    {
        $rules = [
            'term' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'formatTerm'], ['_linked' => 'period']],
                    'rule' => 'is_numeric',
                    'message' => $this->_('Pricings.!error.term.format')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 5],
                    'message' => $this->_('Pricings.!error.term.length')
                ],
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateTerm'], ['_linked' => 'period']],
                    'message' => $this->_('Pricings.!error.term.valid')
                ],
                'in_use' => [
                    'if_set' => true,
                    'rule' => [[$this, 'checkInUse'], 'term', $pricing_id ?? null],
                    'message' => $this->_('Pricings.!error.term.in_use')
                ]
            ],
            'period' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validatePeriod']],
                    'message' => $this->_('Pricings.!error.period.format')
                ],
                'in_use' => [
                    'if_set' => true,
                    'rule' => [[$this, 'checkInUse'], 'period', $pricing_id ?? null],
                    'message' => $this->_('Pricings.!error.period.in_use')
                ]
            ],
            'price' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'currencyToDecimal'], ['_linked' => 'currency'], 4],
                    'rule' => 'is_numeric',
                    'message' => $this->_('Pricings.!error.price.format')
                ]
            ],
            'price_renews' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'currencyToDecimal'], ['_linked' => 'currency'], 4],
                    'rule' => 'is_numeric',
                    'message' => $this->_('Pricings.!error.price_renews.format')
                ],
                'valid' => [
                    'if_set' => true,
                    'rule' => [
                        function($price, $period) {
                            // The renewal price may not be set for the onetime period
                            return ($period != 'onetime' || $price === null);
                        },
                        ['_linked' => 'period']
                    ],
                    'message' => $this->_('Pricings.!error.price_renews.valid')
                ]
            ],
            'price_transfer' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'currencyToDecimal'], ['_linked' => 'currency'], 4],
                    'rule' => 'is_numeric',
                    'message' => $this->_('Pricings.!error.price_transfer.format')
                ]
            ],
            'setup_fee' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'currencyToDecimal'], ['_linked' => 'currency'], 4],
                    'rule' => 'is_numeric',
                    'message' => $this->_('Pricings.!error.setup_fee.format')
                ]
            ],
            'cancel_fee' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'currencyToDecimal'], ['_linked' => 'currency'], 4],
                    'rule' => 'is_numeric',
                    'message' => $this->_('Pricings.!error.cancel_fee.format')
                ]
            ],
            'currency' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^(.*){3}$/'],
                    'message' => $this->_('Pricings.!error.currency.format')
                ],
                'in_use' => [
                    'if_set' => true,
                    'rule' => [[$this, 'checkInUse'], 'currency', $pricing_id ?? null],
                    'message' => $this->_('Pricings.!error.currency.in_use')
                ]
            ]
        ];


        if (!$edit) {
            unset($rules['term']['in_use']);
            unset($rules['period']['in_use']);
            unset($rules['currency']['in_use']);
        }

        return $rules;
    }
}
