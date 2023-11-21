<?php
/**
 * Order Affiliate Referral Management
 *
 * @package blesta
 * @subpackage blesta.plugins.order.models
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderAffiliateReferrals extends OrderModel
{
    /**
     * Returns a list of referrals for the given affiliate
     *
     * @param array $filters A list of filters for the query
     *
     *  - affiliate_id The ID of the affiliate to fetch referrals from
     *  - start_date Get the referrals from this start date
     *  - end_date Get the referrals to this end date
     *  - status The status of the referrals to fetch:
     *      - "pending" Only pending referrals
     *      - "mature" Only mature referrals
     *      - "canceled" Only canceled referrals
     *      - null All referrals
     * @param int $page The page number of results to fetch
     * @param array $order A key/value pair array of fields to order the results by
     * @return array An array of stdClass objects, each representing a referral
     */
    public function getList(array $filters = [], $page = 1, array $order = ['id' => 'desc'])
    {
        $this->Record = $this->getAffiliateReferral($filters);
        return $this->Record->order($order)
            ->limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())
            ->fetchAll();
    }

    /**
     * Returns the total number of referrals for the given affiliate
     *
     * @param array $filters A list of filters for the query
     *
     *  - affiliate_id The ID of the affiliate to fetch referral count from
     *  - start_date Get the referrals from this start date
     *  - end_date Get the referrals to this end date
     *  - status The status of the referrals to fetch:
     *      - "pending" Only pending referrals
     *      - "mature" Only mature referrals
     *      - "canceled" Only canceled referrals
     *      - null All referrals
     * @return int The total number of referrals for the given affiliate
     */
    public function getListCount(array $filters = [])
    {
        $this->Record = $this->getAffiliateReferral($filters);
        return $this->Record->numResults();
    }

    /**
     * Returns all referrals in the system for the given affiliate
     *
     * @param array $filters A list of filters for the query
     *
     *  - affiliate_id The ID of the affiliate to fetch referrals from
     *  - start_date Get the referrals from this start date
     *  - end_date Get the referrals to this end date
     *  - status The status of the referrals to fetch:
     *      - "pending" Only pending referrals
     *      - "mature" Only mature referrals
     *      - "canceled" Only canceled referrals
     *      - null All referrals
     * @param array $order A key/value pair array of fields to order the results by
     * @return array An array of stdClass objects, each representing a referral
     */
    public function getAll(array $filters = [], array $order = ['id' => 'desc'])
    {
        $this->Record = $this->getAffiliateReferral($filters);
        return $this->Record->order($order)->fetchAll();
    }

    /**
     * Fetches the affiliate referral with the given ID
     *
     * @param int $referral_id The ID of the referral to fetch
     * @return mixed A stdClass object representing the referral, false if no such referral exists
     */
    public function get($referral_id)
    {
        $this->Record = $this->getAffiliateReferral();
        return $this->Record->where('order_affiliate_referrals.id', '=', $referral_id)->fetch();
    }

    /**
     * Add an affiliate referral
     *
     * @param array $vars An array of input data including:
     *
     *  - affiliate_id The ID of the affiliate to which the referral belongs
     *  - order_id The ID of the order with which the referral is associated
     *  - name The name by which we can display and refer to the referral (optional)
     *  - status The status of the referral (optional)
     *  - amount The amount invoiced through this referral (optional)
     *  - currency The currency this referral amount and commission is in (optional)
     *  - commission The amount the affiliate can get payed out with this referral matures (optional)
     * @return int The ID of the affiliate referral that was created, void on error
     */
    public function add(array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        $vars['date_added'] = date('c');
        $vars['date_updated'] = $vars['date_added'];
        if ($this->Input->validates($vars)) {
            $fields = [
                'affiliate_id', 'order_id', 'name', 'status', 'amount',
                'currency', 'commission', 'date_added', 'date_updated'
            ];

            $this->Record->insert('order_affiliate_referrals', $vars, $fields);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Edit an affiliate referral
     *
     * @param int $referral_id The ID of the affiliate referral to edit
     * @param array $vars An array of input data including:
     *
     *  - affiliate_id The ID of the affiliate to which the referral belongs
     *  - order_id The ID of the order with which the referral is associated
     *  - name The name by which we can display and refer to the referral (optional)
     *  - status The status of the referral (optional)
     *  - amount The amount invoiced through this referral (optional)
     *  - currency The currency this referral amount and commission is in (optional)
     *  - commission The amount the affiliate can get payed out with this referral matures (optional)
     * @return int The ID of the referral that was updated, void on error
     */
    public function edit($referral_id, array $vars)
    {
        $this->Input->setRules($this->getRules($vars, true));

        $vars['referral_id'] = $referral_id;
        $vars['date_updated'] = date('c');
        if ($this->Input->validates($vars)) {
            $fields = [
                'affiliate_id', 'order_id', 'name', 'status', 'amount',
                'currency', 'commission', 'date_added', 'date_updated'
            ];

            $this->Record->where('id', '=', $referral_id)->update('order_affiliate_referrals', $vars, $fields);

            return $referral_id;
        }
    }

    /**
     * Permanently deletes the given affiliate referral
     *
     * @param int $referral_id The ID of the affiliate referral to delete
     */
    public function delete($referral_id)
    {
        $this->Record->from('order_affiliate_referrals')->where('id', '=', $referral_id)->delete();
    }

    /**
     * Evaluates whether any pending affiliate referrals have reached maturity.
     */
    public function matureAffiliateReferrals()
    {
        Loader::loadModels($this, ['Order.OrderOrders', 'Order.OrderAffiliateSettings', 'Currencies']);
        Loader::loadComponents($this, ['SettingsCollection']);
        Loader::loadHelpers($this, ['Form', 'Date']);

        $referrals = $this->getAffiliateReferral(['status' => 'pending'])
            ->fetchAll();

        // Process referrals
        foreach ($referrals as $referral) {
            // Get referral order
            $order = $this->OrderOrders->get($referral->order_id);

            // Set the referral as canceled if the order is canceled
            if ($order->status = 'canceled') {
                $this->edit($referral->id, ['status' => 'canceled']);

                continue;
            }
            
            // Get affiliate settings
            $settings = $this->Form->collapseObjectArray(
                $this->OrderAffiliateSettings->getSettings($referral->affiliate_id),
                'value',
                'key'
            );
            $this->Date->setTimezone('UTC', Configure::get('Blesta.company_timezone'));
            $referral_date = $this->Date->modify(
                $this->dateToUtc($referral->date_added),
                '+' . (int)$settings['maturity_days'] . ' days',
                'Y-m-d H:i:s'
            );
            $current_date = $this->dateToUtc(date('c'));

            if ($current_date >= $referral_date && ($order->paid >= $order->total || !empty($order->date_closed))) {
                $this->edit($referral->id, ['status' => 'mature']);

                $total_available = $settings['total_available'] + $this->Currencies->convert(
                    $referral->commission,
                    $referral->currency,
                    $settings['withdrawal_currency'],
                    Configure::get('Blesta.company_id')
                );
                $this->OrderAffiliateSettings->setSetting($referral->affiliate_id, 'total_available', $total_available);
            }
        }
    }

    /**
     * Returns all supported affiliate referral statuses in key/value pairs
     *
     * @return array A list of affiliate referral statuses
     */
    public function getStatuses()
    {
        return [
            'pending' => $this->_('OrderAffiliateReferrals.getStatuses.pending'),
            'mature' => $this->_('OrderAffiliateReferrals.getStatuses.mature'),
            'canceled' => $this->_('OrderAffiliateReferrals.getStatuses.canceled'),
        ];
    }

    /**
     * Returns a partial affiliate referral query
     *
     * @param array $filters A list of filters for the query
     *
     *  - affiliate_id The ID of the affiliate to fetch referrals from
     *  - start_date Get the referrals from this start date
     *  - end_date Get the referrals to this end date
     *  - status The status of the referrals to fetch:
     *      - "pending" Only pending referrals
     *      - "mature" Only mature referrals
     *      - "canceled" Only canceled referrals
     *      - null All referrals
     * @return Record A partially built affiliate referral query
     */
    private function getAffiliateReferral(array $filters = [])
    {
        Loader::loadComponents($this, ['SettingsCollection']);
        Loader::loadHelpers($this, ['Date']);

        if (isset($filters['status'])) {
            $this->Record->where('order_affiliate_referrals.status', '=', $filters['status']);
        }

        if (isset($filters['affiliate_id'])) {
            $this->Record->where('order_affiliate_referrals.affiliate_id', '=', $filters['affiliate_id']);
        }

        if (isset($filters['start_date'])) {
            $this->Record->where(
                'order_affiliate_referrals.date_added',
                '>=',
                $this->dateToUtc($filters['start_date'] . ' 00:00:00')
            );
        }

        if (isset($filters['end_date'])) {
            $this->Record->where(
                'order_affiliate_referrals.date_added',
                '<=',
                $this->dateToUtc($filters['end_date'] . ' 23:59:59')
            );
        }

        return $this->Record->select(['order_affiliate_referrals.*', 'orders.order_number'])
            ->leftJoin('orders', 'orders.id', '=', 'order_affiliate_referrals.order_id', false)
            ->from('order_affiliate_referrals');
    }

    /**
     * Returns all validation rules for adding/editing affiliate referrals
     *
     * @param array $vars An array of input key/value pairs
     * @param bool $edit True if this if an edit, false otherwise
     * @return array An array of validation rules
     */
    private function getRules($vars, $edit = false)
    {
        $rules = [
            'affiliate_id' => [
                'exists' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateExists'], 'id', 'order_affiliates'],
                    'message' => $this->_('OrderAffiliateReferrals.!error.affiliate_id.exists')
                ],
            ],
            'order_id' => [
                'exists' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateExists'], 'id', 'orders'],
                    'message' => $this->_('OrderAffiliateReferrals.!error.order_id.exists')
                ],
            ],
            'name' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 255],
                    'message' => $this->_('OrderAffiliateReferrals.!error.name.length')
                ],
            ],
            'status' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => ['array_key_exists', $this->getStatuses()],
                    'message' => $this->_('OrderAffiliateReferrals.!error.status.valid')
                ],
            ],
            'amount' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('OrderAffiliateReferrals.!error.amount.format')
                ],
            ],
            'currency' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[A-Z]{3}$/'],
                    'message' => $this->_('OrderAffiliateReferrals.!error.currency.length')
                ],
            ],
            'commission' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('OrderAffiliateReferrals.!error.commission.format')
                ],
            ],
            'date_added' => [
                'format' => [
                    'rule' => 'isDate',
                    'message' => $this->_('OrderAffiliateReferrals.!error.date_added.format'),
                    'post_format' => [[$this, 'dateToUtc']]
                ],
            ],
            'date_updated' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isDate',
                    'message' => $this->_('OrderAffiliateReferrals.!error.date_updated.format'),
                    'post_format' => [[$this, 'dateToUtc']]
                ],
            ],
        ];

        if ($edit) {
            $rules['referral_id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'order_affiliate_referrals'],
                    'message' => $this->_('OrderAffiliates.!error.referral_id.exists')
                ],
            ];

            unset($rules['date_added']);
        }

        return $rules;
    }
}
