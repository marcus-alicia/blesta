<?php
/**
 * Order Affiliate Payout Management
 *
 * @package blesta
 * @subpackage blesta.plugins.order.models
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderAffiliatePayouts extends OrderModel
{
    /**
     * Returns a list of payouts for the given affiliate
     *
     * @param array $filters A list of filters including:
     *
     *  - int $affiliate_id The ID of the affiliate to fetch payouts from
     *  - string $status The status of the payouts to fetch:
     *      - "pending" Only pending payouts
     *      - "approved" Only approved payouts
     *      - "declined" Only declined payouts
     *      - null All payouts
     * @param int $page The page number of results to fetch
     * @param array $order A key/value pair array of fields to order the results by
     * @return array An array of stdClass objects, each representing an payout
     */
    public function getList(array $filters = [], $page = 1, array $order = ['id' => 'desc'])
    {
        $this->Record = $this->getAffiliatePayout($filters);
        return $this->Record->order($order)
            ->limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())
            ->fetchAll();
    }

    /**
     * Returns the total number of payouts for the given affiliate
     *
     * @param array $filters A list of filters including:
     *
     *  - int $affiliate_id The ID of the affiliate to fetch payouts from
     *  - string $status The status of the payouts to fetch:
     *      - "pending" Only pending payouts
     *      - "approved" Only approved payouts
     *      - "declined" Only declined payouts
     *      - null All payouts
     * @return int The total number of payouts for the given affiliate
     */
    public function getListCount(array $filters = [])
    {
        $this->Record = $this->getAffiliatePayout($filters);
        return $this->Record->numResults();
    }

    /**
     * Returns all payouts in the system for the given affiliate
     *
     * @param array $filters A list of filters including:
     *
     *  - int $affiliate_id The ID of the affiliate to fetch payouts from
     *  - string $status The status of the payouts to fetch:
     *      - "pending" Only pending payouts
     *      - "approved" Only approved payouts
     *      - "declined" Only declined payouts
     *      - null All payouts
     * @param array $order A key/value pair array of fields to order the results by
     * @return array An array of stdClass objects, each representing a payout
     */
    public function getAll(array $filters = [], array $order = ['id' => 'desc'])
    {
        $this->Record = $this->getAffiliatePayout($filters);
        return $this->Record->order($order)->fetchAll();
    }

    /**
     * Fetches the affiliate payout with the given ID
     *
     * @param int $payout_id The ID of the affiliate payout to fetch
     * @return mixed A stdClass object representing the payout, false if no such payout exists
     */
    public function get($payout_id)
    {
        $this->Record = $this->getAffiliatePayout();
        return $this->Record->where('order_affiliate_payouts.id', '=', $payout_id)->fetch();
    }

    /**
     * Add an affiliate payout
     *
     * @param array $vars An array of input data including:
     *
     *  - affiliate_id The ID of the affiliate requesting the payout
     *  - payment_method_id The ID of the payment method the affiliate is requesting
     *  - status The status of the payout
     *  - requested_amount The amount of payout being requested
     *  - requested_currency The currency of payout being requested
     *  - paid_amount The amount of payout distributed so far
     *  - paid_currency The currency in which payout has been given
     * @return int The ID of the affiliate payout that was created, void on error
     */
    public function add(array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $vars['date_requested'] = $this->dateToUtc(date('c'));

            $fields = [
                'affiliate_id', 'payment_method_id', 'status',
                'requested_amount', 'requested_currency',
                'paid_amount', 'paid_currency', 'date_requested'
            ];
            $this->Record->insert('order_affiliate_payouts', $vars, $fields);

            // Send payout request notification
            $this->sendPayoutEmail($vars);

            // Update total withdrawn
            Loader::loadComponents($this, ['Order.OrderAffiliateSettings']);
            Loader::loadHelpers($this, ['Form']);

            $settings = $this->Form->collapseObjectArray(
                $this->OrderAffiliateSettings->getSettings($vars['affiliate_id']),
                'value',
                'key'
            );

            $total_withdrawn = $settings['total_withdrawn'] + $this->Currencies->convert(
                $vars['requested_amount'],
                $vars['requested_currency'],
                $settings['withdrawal_currency'],
                Configure::get('Blesta.company_id')
            );
            $this->OrderAffiliateSettings->setSetting($vars['affiliate_id'], 'total_withdrawn', $total_withdrawn);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Edit an affiliate payout
     *
     * @param int $payout_id The ID of the affiliate payout to edit
     * @param array $vars An array of input data including:
     *
     *  - affiliate_id The ID of the affiliate requesting the payout
     *  - payment_method_id The ID of the payment method the affiliate is requesting
     *  - status The status of the payout
     *  - requested_amount The amount of payout being requested
     *  - requested_currency The currency of payout being requested
     *  - paid_amount The amount of payout distributed so far
     *  - paid_currency The currency in which payout has been given
     * @return int The ID of the affiliate payout that was updated, void on error
     */
    public function edit($payout_id, array $vars)
    {
        Loader::loadModels($this, ['Currencies']);

        $vars['payout_id'] = $payout_id;
        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            // Update total withdrawn
            Loader::loadComponents($this, ['Order.OrderAffiliateSettings']);
            Loader::loadHelpers($this, ['Form']);

            $payout = $this->get($payout_id);

            if (!isset($vars['affiliate_id'])) {
                $vars['affiliate_id'] = $payout->affiliate_id;
            }

            $settings = $this->Form->collapseObjectArray(
                $this->OrderAffiliateSettings->getSettings($vars['affiliate_id']),
                'value',
                'key'
            );

            // Calculate withdrawal amount
            $requested_amount = $this->Currencies->convert(
                $payout->requested_amount,
                $payout->requested_currency,
                $settings['withdrawal_currency'],
                Configure::get('Blesta.company_id')
            );
            $new_requested_amount = $this->Currencies->convert(
                isset($vars['requested_amount']) ? $vars['requested_amount'] : $payout->requested_amount,
                isset($vars['requested_currency']) ? $vars['requested_currency'] : $payout->requested_currency,
                $settings['withdrawal_currency'],
                Configure::get('Blesta.company_id')
            );
            $total_withdrawn = $settings['total_withdrawn'] - $requested_amount + $new_requested_amount;

            if (isset($vars['status']) && $vars['status'] !== $payout->status) {
                if ($vars['status'] == 'declined') {
                    $total_withdrawn = $total_withdrawn - $new_requested_amount;
                }

                if ($vars['status'] == 'approved') {
                    $total_withdrawn = $total_withdrawn + $new_requested_amount;
                }
            }

            $this->OrderAffiliateSettings->setSetting($vars['affiliate_id'], 'total_withdrawn', $total_withdrawn);

            // Update payout
            $fields = [
                'affiliate_id', 'payment_method_id', 'status',
                'requested_amount', 'requested_currency',
                'paid_amount', 'paid_currency'
            ];
            $this->Record->where('id', '=', $payout_id)->update('order_affiliate_payouts', $vars, $fields);

            return $payout_id;
        }
    }

    /**
     * Permanently deletes the given affiliate payout
     *
     * @param int $payout_id The ID of the affiliate payout to delete
     */
    public function delete($payout_id)
    {
        $this->Record->from('order_affiliate_payouts')->where('id', '=', $payout_id)->delete();
    }

    /**
     * Returns all supported affiliate payout statuses in key/value pairs
     *
     * @return array A list of affiliate payout statuses
     */
    public function getStatuses()
    {
        return [
            'pending' => $this->_('OrderAffiliatePayouts.getStatuses.pending'),
            'approved' => $this->_('OrderAffiliatePayouts.getStatuses.approved'),
            'declined' => $this->_('OrderAffiliatePayouts.getStatuses.declined'),
        ];
    }

    /**
     * Returns a partial affiliate payout query
     *
     * @param array $filters A list of filters for the query
     *
     *  - company_id The ID of the company to which the affiliates for these payouts must be assigned
     *  - status The payout status
     *  - affiliate_id The ID of the affiliate to which the payouts are assigned
     * @return Record A partially built affiliate payout query
     */
    private function getAffiliatePayout(array $filters = [])
    {
        $this->Record->select([
                'order_affiliate_payouts.*',
                'order_affiliates.client_id',
                'order_affiliates.code' => 'affiliate_code',
                'contacts.first_name' => 'affiliate_first_name',
                'contacts.last_name' => 'affiliate_last_name',
                'order_affiliate_payment_method_names.name' => 'payment_method_name'
            ])
            ->from('order_affiliate_payouts')
            ->innerJoin(
                'order_affiliates',
                'order_affiliates.id',
                '=',
                'order_affiliate_payouts.affiliate_id',
                false
            )
            ->innerJoin('clients', 'clients.id', '=', 'order_affiliates.client_id', false)
            ->on('contacts.contact_type', '=', 'primary')
            ->innerJoin('contacts', 'contacts.client_id', '=', 'clients.id', false)
            ->on('order_affiliate_payment_method_names.lang', '=', Configure::get('Blesta.language'))
            ->leftJoin(
                'order_affiliate_payment_method_names',
                'order_affiliate_payment_method_names.payment_method_id',
                '=',
                'order_affiliate_payouts.payment_method_id',
                false
            );

        if (isset($filters['company_id'])) {
            $this->Record->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)
                ->where('client_groups.company_id', '=', $filters['company_id']);
        }

        if (isset($filters['status'])) {
            $this->Record->where('order_affiliate_payouts.status', '=', $filters['status']);
        }

        if (isset($filters['affiliate_id'])) {
            $this->Record->where('order_affiliate_payouts.affiliate_id', '=', $filters['affiliate_id']);
        }

        return $this->Record;
    }

    /**
     * Retrieves the number of affiliate payouts given a payout status
     *
     * @param string $status The payout status type (optional, default 'active')
     * @param int $company_id The ID of the company to count payouts for (optional)
     * @return int The number of payouts of type $status
     */
    public function getStatusCount($status = 'active', $company_id = null)
    {
        return $this->getAffiliatePayout(['status' => $status, 'company_id' => $company_id])->numResults();
    }

    /**
     * Returns all validation rules for adding/editing affiliate payouts
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
                    'message' => $this->_('OrderAffiliatePayouts.!error.affiliate_id.exists')
                ],
            ],
            'payment_method_id' => [
                'exists' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateExists'], 'id', 'order_affiliate_payment_methods'],
                    'message' => $this->_('OrderAffiliatePayouts.!error.payment_method_id.exists')
                ],
            ],
            'status' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => ['array_key_exists', $this->getStatuses()],
                    'message' => $this->_('OrderAffiliatePayouts.!error.status.valid')
                ],
            ],
            'requested_amount' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('OrderAffiliatePayouts.!error.requested_amount.format')
                ],
                'valid' => [
                    'if_set' => true,
                    'rule' => [
                        [$this, 'validateAmount'],
                        (isset($vars['requested_currency']) ? $vars['requested_currency'] : null),
                        (isset($vars['affiliate_id']) ? $vars['affiliate_id'] : null)
                    ],
                    'message' => $this->_('OrderAffiliatePayouts.!error.requested_amount.valid')
                ],
            ],
            'requested_currency' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[A-Z]{3}$/'],
                    'message' => $this->_('OrderAffiliatePayouts.!error.requested_currency.length')
                ],
            ],
            'paid_amount' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('OrderAffiliatePayouts.!error.paid_amount.format')
                ],
            ],
            'paid_currency' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[A-Z]{3}$/'],
                    'message' => $this->_('OrderAffiliatePayouts.!error.paid_currency.length')
                ],
            ],
        ];

        if ($edit) {
            $rules['payout_id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'order_affiliate_payouts'],
                    'message' => $this->_('OrderAffiliatePayouts.!error.payout_id.exists')
                ],
            ];

            if (!isset($vars['affiliate_id'])) {
                unset($rules['requested_amount']['valid']);
            }
        }

        return $rules;
    }

    /**
     * Validates that the requested amount id within the established limits
     *
     * @param $amount float The requested amount for the payout
     * @param $currency string The requested currency for the payout
     * @param $affiliate_id int The ID of the affiliate requesting the payout
     * @return bool True if the amount is valid and between the limits
     */
    public function validateAmount($amount, $currency, $affiliate_id)
    {
        Loader::loadModels($this, ['Order.OrderAffiliateSettings', 'Currencies']);
        Loader::loadHelpers($this, ['Form']);

        $affiliate_settings = $this->Form->collapseObjectArray(
            $this->OrderAffiliateSettings->getSettings($affiliate_id),
            'value',
            'key'
        );

        $amount = $this->Currencies->convert(
            $amount,
            $currency,
            (isset($affiliate_settings['withdrawal_currency']) ? $affiliate_settings['withdrawal_currency'] : 'USD'),
            Configure::get('Blesta.company_id')
        );

        return ($amount >= $affiliate_settings['min_withdrawal_amount'])
            && ($affiliate_settings['max_withdrawal_amount'] >= $amount);
    }

    /**
     * Sends payout request notification email
     *
     * @param array $payout An array of input data including:
     *
     *  - affiliate_id The ID of the affiliate requesting the payout
     *  - payment_method_id The ID of the payment method the affiliate is requesting
     *  - status The status of the payout
     *  - requested_amount The amount of payout being requested
     *  - requested_currency The currency of payout being requested
     *  - paid_amount The amount of payout distributed so far
     *  - paid_currency The currency in which payout has been given
     */
    private function sendPayoutEmail($payout)
    {
        Loader::loadModels($this, ['Order.OrderAffiliates', 'Order.OrderStaffSettings', 'Clients', 'Emails']);

        // Fetch the client
        $affiliate = $this->OrderAffiliates->get($payout['affiliate_id']);
        $client = $this->Clients->get($affiliate->client_id);

        // Get the tags for the email
        $tags = [
            'client' => $client,
            'affiliate' => $affiliate,
            'payout' => $payout
        ];

        // Send client notification email
        $this->Emails->send(
            'Order.affiliate_payout_request_received',
            Configure::get('Blesta.company_id'),
            $client->settings['language'],
            $client->email,
            $tags,
            null,
            null,
            null,
            ['to_client_id' => $client->id]
        );

        // Fetch all staff that should receive the email notification
        $staff_email = $this->OrderStaffSettings->getStaffWithSetting(
            Configure::get('Blesta.company_id'),
            'payout_notice',
            'always'
        );

        // Send email to staff
        foreach ($staff_email as $staff) {
            $tags['staff'] = $staff;
            $this->Emails->send(
                'Order.affiliate_payout_request',
                Configure::get('Blesta.company_id'),
                null,
                $staff->email,
                $tags
            );
        }
    }
}
