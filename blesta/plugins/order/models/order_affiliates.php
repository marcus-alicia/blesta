<?php
/**
 * Order Affiliate Management
 *
 * @package blesta
 * @subpackage blesta.plugins.order.models
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderAffiliates extends OrderModel
{
    /**
     * Returns a list of affiliates for the given company
     *
     * @param int $company_id The ID of the company to fetch affiliates from
     * @param string $status The status of the affiliates to fetch:
     *
     *  - "active" Only active affiliates
     *  - "inactive" Only inactive affiliates
     *  - null All affiliates
     * @param int $page The page number of results to fetch
     * @param array $order A key/value pair array of fields to order the results by
     * @return array An array of stdClass objects, each representing an affiliate
     */
    public function getList($company_id, $status = null, $page = 1, array $order = ['id' => 'desc'])
    {
        $this->Record = $this->getAffiliate(['status' => $status, 'company_id' => $company_id]);
        return $this->Record
            ->order($order)
            ->limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())
            ->fetchAll();
    }

    /**
     * Returns the total number of affiliate for the given company
     *
     * @param int $company_id The ID of the company to fetch affiliate count from
     * @param string $status The status of the affiliates to fetch:
     *
     *  - "active" Only active affiliates
     *  - "inactive" Only inactive affiliates
     *  - null All affiliates
     * @return int The total number of affiliates for the given company
     */
    public function getListCount($company_id, $status = null)
    {
        $this->Record = $this->getAffiliate(['status' => $status, 'company_id' => $company_id]);
        return $this->Record->numResults();
    }

    /**
     * Returns all affiliates in the system for the given company
     *
     * @param int $company_id The ID of the company to fetch affiliates for
     * @param string $status The status of the affiliates to fetch:
     *
     *  - "active" Only active affiliates
     *  - "inactive" Only inactive affiliates
     *  - null All affiliates
     * @param array $order A key/value pair array of fields to order the results by
     * @return array An array of stdClass objects, each representing an affiliate
     */
    public function getAll($company_id, $status = null, array $order = ['id' => 'desc'])
    {
        $this->Record = $this->getAffiliate(['status' => $status, 'company_id' => $company_id]);
        return $this->Record->order($order)->fetchAll();
    }

    /**
     * Fetches the affiliate with the given ID
     *
     * @param int $affiliate_id The ID of the affiliate to fetch
     * @return mixed A stdClass object representing the affiliate, false if no such affiliate exists
     */
    public function get($affiliate_id)
    {
        $this->Record = $this->getAffiliate();
        return $this->Record->where('order_affiliates.id', '=', $affiliate_id)->fetch();
    }

    /**
     * Fetches the affiliate with the given client ID
     *
     * @param int $client_id The ID of the client for which to fetch an affiliate
     * @return mixed A stdClass object representing the affiliate, false if no such affiliate exists
     */
    public function getByClientId($client_id)
    {
        $this->Record = $this->getAffiliate(['client_id' => $client_id]);
        return $this->Record->fetch();
    }

    /**
     * Fetches the affiliate with the given code
     *
     * @param string $code The affiliate code
     * @return mixed A stdClass object representing the affiliate, false if no such affiliate exists
     */
    public function getByCode($code)
    {
        $this->Record = $this->getAffiliate(['code' => $code]);
        return $this->Record->fetch();
    }

    /**
     * Add an affiliate
     *
     * @param array $vars An array of input data including:
     *
     *  - client_id The ID of the client to associate the affiliate with
     *  - code The code to use when creating referral links for this affiliate (optional)
     *  - status The status of the affiliate ('active' or 'inactive', default 'active')
     *  - visits The number of times an affiliate's link has been visited (optional)
     *  - sales The number of sales completed after following the affiliates link (optional)
     * @return int The ID of the affiliate that was created, void on error
     */
    public function add(array $vars)
    {
        if (!isset($vars['code']) && isset($vars['client_id'])) {
            $vars['code'] = base64_encode($vars['client_id']);
        }

        $vars['date_added'] = date('c');
        $vars['date_updated'] = $vars['date_added'];

        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ['client_id', 'code', 'status', 'date_added', 'date_updated'];
            $this->Record->insert('order_affiliates', $vars, $fields);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Edit an affiliate
     *
     * @param int $affiliate_id The ID of the affiliate to edit
     * @param array $vars An array of input data including:
     *
     *  - client_id The ID of the client to associate the affiliate with
     *  - code The code to use when creating referral links for this affiliate
     *  - status The status of the affiliate ('active' or 'inactive', default 'active')
     *  - visits The number of times an affiliate's link has been visited (optional)
     *  - sales The number of sales completed after following the affiliates link (optional)
     * @return int The ID of the affiliate that was updated, void on error
     */
    public function edit($affiliate_id, array $vars)
    {
        $vars['affiliate_id'] = $affiliate_id;
        $vars['date_updated'] = date('c');
        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            $fields = ['client_id', 'code', 'status', 'date_updated'];
            $this->Record->where('id', '=', $affiliate_id)->update('order_affiliates', $vars, $fields);

            return $affiliate_id;
        }
    }

    /**
     * Permanently deletes the given affiliate
     *
     * @param int $affiliate_id The ID of the affiliate to delete
     * @param bool $permanent True to permanently delete the affiliate and associated
     *     records, false to mark as inactive
     */
    public function delete($affiliate_id, $permanent = false)
    {
        if ($permanent) {
            // Delete an affiliate and their associated records
            $this->Record->from('order_affiliates')
                ->innerJoin(
                    'order_affiliate_payouts',
                    'order_affiliate_payouts.affiliate_id',
                    '=',
                    'order_affiliates.id',
                    false
                )
                ->innerJoin(
                    'order_affiliate_referrals',
                    'order_affiliate_referrals.affiliate_id',
                    '=',
                    'order_affiliates.id',
                    false
                )
                ->innerJoin(
                    'order_affiliate_settings',
                    'order_affiliate_settings.affiliate_id',
                    '=',
                    'order_affiliates.id',
                    false
                )
                ->where('order_affiliates.id', '=', $affiliate_id)
                ->delete([
                    'order_affiliates.*',
                    'order_affiliate_payouts.*',
                    'order_affiliate_referrals.*',
                    'order_affiliate_settings.*'
                ]);
        } else {
            $this->Record->update('order_affiliates', ['status' => 'inactive']);
        }
    }

    /**
     * Returns all supported affiliate statuses in key/value pairs
     *
     * @return array A list of affiliate statuses
     */
    public function getStatuses()
    {
        return [
            'active' => $this->_('OrderAffiliates.getStatuses.active'),
            'inactive' => $this->_('OrderAffiliates.getStatuses.inactive'),
        ];
    }

    /**
     * Retrieves the number of affiliates given an affiliate status
     *
     * @param string $status The affiliate status type (optional, default 'active')
     * @param int $company_id The ID of the company to count affiliates for (optional)
     * @return int The number of affiliates of type $status
     */
    public function getStatusCount($status = 'active', $company_id = null)
    {
        return $this->getAffiliate(['status' => $status, 'company_id' => $company_id])->numResults();
    }

    /**
     * Returns a partial affiliate query
     *
     * @param array $filters A list of filters for the query
     *
     *  - status The affiliate status
     *  - company_id The ID of the company to which the affiliate clients must be assigned
     *  - client_id The ID of the client to which this affiliate is assigned
     *  - code The code to which this affiliate is assigned
     * @return Record A partially built affiliate query
     */
    private function getAffiliate(array $filters = [])
    {
        $select = [
            'order_affiliates.*',
            'contacts.first_name',
            'contacts.last_name',
            'IFNULL(SUM(order_affiliate_statistics.visits), ?)' => 'visits',
            'IFNULL(SUM(order_affiliate_statistics.sales), ?)' => 'sales'
        ];

        $this->Record->select($select)
            ->appendValues([0, 0])
            ->from('order_affiliates')
            ->innerJoin('clients', 'clients.id', '=', 'order_affiliates.client_id', false)
            ->on('contacts.contact_type', '=', 'primary')
            ->innerJoin('contacts', 'contacts.client_id', '=', 'clients.id', false)
            ->leftJoin('order_affiliate_statistics', 'order_affiliate_statistics.affiliate_id', '=', 'order_affiliates.id', false)
            ->group('order_affiliates.id');

        if (isset($filters['status'])) {
            $this->Record->where('order_affiliates.status', '=', $filters['status']);
        }

        if (isset($filters['company_id'])) {
            $this->Record
                ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)
                ->where('client_groups.company_id', '=', $filters['company_id']);
        }

        if (isset($filters['client_id'])) {
            $this->Record->where('order_affiliates.client_id', '=', $filters['client_id']);
        }

        if (isset($filters['code'])) {
            $this->Record->where('order_affiliates.code', '=', $filters['code']);
        }

        return $this->Record;
    }

    /**
     * Get the number of days an affiliate has been active
     *
     * @param int $affiliate_id The ID of the affiliate
     * @return int The number of days the affiliate has been active
     */
    public function getAffiliateDaysActive($affiliate_id)
    {
        // Get affiliate
        if (!($affiliate = $this->get($affiliate_id))) {
            return 0;
        }

        $start_date = strtotime($affiliate->date_added . 'Z');
        $current_date = time();
        $time_difference = $current_date - $start_date;

        return round($time_difference / (60 * 60 * 24));
    }

    /**
     * Send a affiliate email report for the previous month.
     */
    public function affiliateMonthlyReport()
    {
        Loader::loadModels($this, [
            'Order.OrderAffiliateReferrals',
            'Order.OrderAffiliateSettings',
            'Clients',
            'Emails',
            'Companies'
        ]);
        Loader::loadHelpers($this, ['Form']);

        // Fetch all active affiliates
        $affiliates = $this->getAll(Configure::get('Blesta.company_id'), 'active');

        foreach ($affiliates as $affiliate) {
            // Fetch the client
            $client = $this->Clients->get($affiliate->client_id);

            // Fetch the affiliate settings
            $affiliate->meta = $this->Form->collapseObjectArray(
                $this->OrderAffiliateSettings->getSettings($affiliate->id),
                'value',
                'key'
            );

            $start_date = $this->Date->format('Y-m-d', 'first day of last month');
            $end_date = $this->Date->format('Y-m-d', 'last day of last month');

            // Get previous month signups and referrals
            $signups = $this->OrderAffiliateReferrals->getListCount([
                'affiliate_id' => $affiliate->id,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);
            $referrals = $this->OrderAffiliateReferrals->getAll([
                'affiliate_id' => $affiliate->id,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);
            $statuses = $this->OrderAffiliateReferrals->getStatuses();

            foreach ($referrals as $key => $referral) {
                $referrals[$key]->status_formatted = isset($statuses[$referral->status]) ? $statuses[$referral->status] : '';
            }

            // Get date format
            $date_format = $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'date_format');

            // Get the tags for the email
            $tags = [
                'client' => $client,
                'affiliate' => $affiliate,
                'signups' => $signups,
                'referrals' => $referrals,
                'date_format' => isset($date_format->value) ? $date_format->value : 'M d, Y'
            ];

            // Send client notification email
            $this->Emails->send(
                'Order.affiliate_monthly_report',
                Configure::get('Blesta.company_id'),
                $client->settings['language'],
                $client->email,
                $tags,
                null,
                null,
                null,
                ['to_client_id' => $client->id]
            );
        }
    }

    /**
     * Returns all validation rules for adding/editing affiliates
     *
     * @param array $vars An array of input key/value pairs
     * @param bool $edit True if this if an edit, false otherwise
     * @return array An array of validation rules
     */
    private function getRules($vars, $edit = false)
    {
        $rules = [
            'client_id' => [
                'exists' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => $this->_('OrderAffiliates.!error.client_id.exists')
                ],
                'unique' => [
                    'if_set' => $edit,
                    'rule' => [
                        function ($client_id, $affiliate_id) {
                            // Ensure the given client is not taken by any other affiliate
                            $this->Record->select()
                                ->from('order_affiliates')
                                ->where('order_affiliates.client_id', '=', $client_id);

                            // Exclude this current affiliate from the result set since the affiliate can edit itself
                            if (!empty($affiliate_id) && is_numeric($affiliate_id)) {
                                $this->Record->where('order_affiliates.id', '!=', $affiliate_id);
                            }

                            return $this->Record->numResults() === 0;
                        },
                        ['_linked' => 'affiliate_id'],
                    ],
                    'message' => $this->_('OrderAffiliates.!error.client_id.unique')
                ],
            ],
            'code' => [
                'empty' => [
                    'if_set' => $edit,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('OrderAffiliates.!error.code.empty')
                ],
                'unique' => [
                    'if_set' => $edit,
                    'rule' => [
                        function ($code, $affiliate_id) {
                            // Ensure the given code is not taken by any other affiliate
                            $this->Record->select()
                                ->from('order_affiliates')
                                ->where('order_affiliates.code', '=', $code);

                            // Exclude this current affiliate from the result set since the affiliate can edit itself
                            if (!empty($affiliate_id) && is_numeric($affiliate_id)) {
                                $this->Record->where('order_affiliates.id', '!=', $affiliate_id);
                            }

                            return $this->Record->numResults() === 0;
                        },
                        ['_linked' => 'affiliate_id'],
                    ],
                    'message' => $this->_('OrderAffiliates.!error.code.unique')
                ],
            ],
            'status' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => ['array_key_exists', $this->getStatuses()],
                    'message' => $this->_('OrderAffiliates.!error.status.valid')
                ],
            ],
            'date_added' => [
                'format' => [
                    'rule' => 'isDate',
                    'message' => $this->_('OrderAffiliates.!error.date_added.format'),
                    'post_format' => [[$this, 'dateToUtc']]
                ],
            ],
            'date_updated' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isDate',
                    'message' => $this->_('OrderAffiliates.!error.date_updated.format'),
                    'post_format' => [[$this, 'dateToUtc']]
                ],
            ],
        ];

        if ($edit) {
            $rules['affiliate_id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'order_affiliates'],
                    'message' => $this->_('OrderAffiliates.!error.affiliate_id.exists')
                ],
            ];

            unset($rules['date_added']);
        }

        return $rules;
    }
}
