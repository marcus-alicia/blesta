<?php
/**
 * Order Affiliate Payment Method Management
 *
 * @package blesta
 * @subpackage blesta.plugins.order.models
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderAffiliatePaymentMethods extends OrderModel
{
    /**
     * Returns a list of affiliate payment methods for the given company
     *
     * @param int $company_id The ID of the company to fetch payment methods from
     * @param int $page The page number of results to fetch
     * @param array $order A key/value pair array of fields to order the results by
     * @return array An array of stdClass objects, each representing a payment method
     */
    public function getList($company_id, $page = 1, array $order = ['id' => 'desc'])
    {
        $this->Record = $this->getAffiliatePaymentMethod(['company_id' => $company_id]);

        $payment_methods = $this->Record->order($order)
            ->limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->fetchAll();
        foreach ($payment_methods as $payment_method) {
            $payment_method->names = $this->getNames($payment_method->id);
        }

        return $payment_methods;
    }

    /**
     * Returns the total number of payment methods for the given company
     *
     * @param int $company_id The ID of the company to fetch payment method count from
     * @return int The total number of affiliate payment methods for the given company
     */
    public function getListCount($company_id)
    {
        $this->Record = $this->getAffiliatePaymentMethod(['company_id' => $company_id]);
        return $this->Record->numResults();
    }

    /**
     * Returns all affiliate payment methods in the system for the given company
     *
     * @param int $company_id The ID of the company for which to fetch payment methods
     * @param array $order A key/value pair array of fields to order the results by
     * @return array An array of stdClass objects, each representing a payment method
     */
    public function getAll($company_id, array $order = ['id' => 'desc'])
    {
        $this->Record = $this->getAffiliatePaymentMethod(['company_id' => $company_id]);

        $payment_methods = $this->Record->order($order)->fetchAll();
        foreach ($payment_methods as $payment_method) {
            $payment_method->names = $this->getNames($payment_method->id);

            $payment_method->name = '';
            foreach ($payment_method->names as $name) {
                if ($name->lang == Configure::get('Blesta.language')) {
                    $payment_method->name = $name->name;
                    break;
                }

                if ($name->lang == 'en_us') {
                    $payment_method->name = $name->name;
                }
            }
        }

        return $payment_methods;
    }

    /**
     * Fetches the affiliate payment method with the given ID
     *
     * @param int $payment_method_id The ID of the payment method to fetch
     * @return mixed A stdClass object representing the payment method, false if no such payment method exists
     */
    public function get($payment_method_id)
    {
        $this->Record = $this->getAffiliatePaymentMethod();

        $payment_method = $this->Record->where('order_affiliate_payment_methods.id', '=', $payment_method_id)->fetch();
        $payment_method->names = $this->getNames($payment_method_id);

        return $payment_method;
    }

    /**
     * Add an affiliate payment method
     *
     * @param array $vars An array of input data including:
     *
     *  - company_id The ID of the company to which the payment method is assigned
     *  - names A list of name arrays each including:
     *      - lang The code of the language in which this name is written
     *      - name The name for this payment method and language
     * @return int The ID of the affiliate payment method that was created, void on error
     */
    public function add(array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ['company_id'];
            $this->Record->insert('order_affiliate_payment_methods', $vars, $fields);

            $payment_method_id = $this->Record->lastInsertId();
            $this->setNames($payment_method_id, $vars['names']);

            return $payment_method_id;
        }
    }

    /**
     * Edit an affiliate payment method
     *
     * @param int $payment_method_id The ID of the affiliate payment method to edit
     * @param array $vars An array of input data including:
     *
     *  - company_id The ID of the company to which the payment method is assigned
     *  - names A list of name arrays each including:
     *      - lang The code of the language in which this name is written
     *      - name The name for this payment method and language
     * @return int The ID of the affiliate that was updated, void on error
     */
    public function edit($payment_method_id, array $vars)
    {
        $vars['payment_method_id'] = $payment_method_id;
        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            $fields = ['company_id'];
            $this->Record->where('id', '=', $payment_method_id)
                ->update('order_affiliate_payment_methods', $vars, $fields);
            $this->setNames($payment_method_id, $vars['names']);

            return $payment_method_id;
        }
    }

    /**
     * Permanently deletes the given affiliate payment method
     *
     * @param int $payment_method_id The ID of the affiliate payment method to delete
     */
    public function delete($payment_method_id)
    {
        $this->Record->from('order_affiliate_payment_methods')
            ->innerJoin(
                'order_affiliate_payment_method_names',
                'order_affiliate_payment_method_names.payment_method_id',
                '=',
                'order_affiliate_payment_methods.id',
                false
            )
            ->where('order_affiliate_payment_methods.id', '=', $payment_method_id)
            ->delete(['order_affiliate_payment_methods.*', 'order_affiliate_payment_method_names.*']);
    }

    /**
     * Fetches all names created for the given payment method
     *
     * @param int $payment_method_id The payment method ID to fetch names for
     * @return array An array of stdClass objects representing payment method names
     */
    private function getNames($payment_method_id)
    {
        return $this->Record->select(['lang', 'name'])
            ->from('order_affiliate_payment_method_names')
            ->where('payment_method_id', '=', $payment_method_id)
            ->fetchAll();
    }

    /**
     * Sets the multilingual payment method names
     *
     * @param int $payment_method_id The ID of the payment method to set the names for
     * @param array $names An array including:
     *
     *  - lang The language code (e.g. 'en_us')
     *  - name The name in the specified language
     */
    private function setNames($payment_method_id, array $names)
    {
        // Add payment method names
        if (!empty($names) && is_array($names)) {
            $fields = ['payment_method_id', 'lang', 'name'];

            foreach ($names as $name) {
                $name['payment_method_id'] = $payment_method_id;
                $this->Record->duplicate('name', '=', (isset($name['name']) ? $name['name'] : null))
                    ->insert('order_affiliate_payment_method_names', $name, $fields);
            }
        }
    }

    /**
     * Returns a partial affiliate payment method query
     *
     * @param array $filters A list of filters for the query
     * @return Record A partially built affiliate payment method query
     */
    private function getAffiliatePaymentMethod(array $filters = [])
    {
        if (isset($filters['company_id'])) {
            $this->Record->where('order_affiliate_payment_methods.company_id', '=', $filters['company_id']);
        }

        return $this->Record->select()->from('order_affiliate_payment_methods');
    }

    /**
     * Returns all validation rules for adding/editing affiliate payment methods
     *
     * @param array $vars An array of input key/value pairs
     * @param bool $edit True if this if an edit, false otherwise
     * @return array An array of validation rules
     */
    private function getRules($vars, $edit = false)
    {
        $rules = [
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('OrderAffiliatePaymentMethods.!error.company_id.exists')
                ]
            ],
            'names[][lang]' => [
                'format' => [
                    'rule' => ['matches', '/^[a-z]{2}_[a-z]{2}$/i'],
                    'message' => $this->_('OrderAffiliatePaymentMethods.!error.names[][lang].format'),
                    'post_format' => 'strtolower'
                ],
            ],
            'names[][name]' => [
                'exists' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('OrderAffiliatePaymentMethods.!error.names[][name].empty')
                ]
            ],
        ];

        if ($edit) {
            $rules['payment_method_id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'order_affiliate_payment_methods'],
                    'message' => $this->_('OrderAffiliatePaymentMethods.!error.payment_method_id.exists')
                ],
            ];
        }

        return $rules;
    }
}
