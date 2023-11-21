<?php
/**
 * Admin Payouts controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order.controllers
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminPayouts extends OrderAffiliateController
{
    /**
     * Pre Action
     */
    public function preAction()
    {
        parent::preAction();

        $this->requireLogin();

        $this->uses([
            'Order.OrderAffiliatePayouts',
            'Order.OrderAffiliatePaymentMethods',
            'Order.OrderAffiliateSettings',
            'Currencies'
        ]);

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);

        Language::loadLang('admin_payouts', null, PLUGINDIR . 'order' . DS . 'language' . DS);
    }

    /**
     * List order affiliate payouts
     */
    public function index()
    {
        $status = (isset($this->get[0]) ? $this->get[0] : 'pending');
        $page = (isset($this->get[1]) ? (int)$this->get[1] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'id');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        // Set the number of payouts of each type
        $status_count = [
            'approved' => $this->OrderAffiliatePayouts->getStatusCount('approved', $this->company_id),
            'pending' => $this->OrderAffiliatePayouts->getStatusCount('pending', $this->company_id),
            'declined' => $this->OrderAffiliatePayouts->getStatusCount('declined', $this->company_id)
        ];
        $order_affiliate_payouts = $this->OrderAffiliatePayouts->getList(
            ['company_id' => $this->company_id, 'status' => $status],
            $page,
            [(strpos($sort, '.') >= 0 ? '' : 'order_affiliate_payouts.') . $sort => $order]
        );
        $total_results = $this->OrderAffiliatePayouts->getListCount(
            ['company_id' => $this->company_id, 'status' => $status]
        );

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'plugin/order/admin_payouts/index/' . $status . '/[p]/'
            ]
        );

        $this->setPagination($this->get, $settings);
        $this->set('order_affiliate_payouts', $order_affiliate_payouts);
        $this->set(
            'payment_methods',
            $this->Form->collapseObjectArray(
                $this->OrderAffiliatePaymentMethods->getAll(Configure::get('Blesta.company_id')),
                'name',
                'id'
            )
        );
        $this->set('status', $status);
        $this->set('status_count', $status_count);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        return $this->renderAjaxWidgetIfAsync(isset($this->get[1]) || isset($this->get['sort']));
    }

    /**
     * Edit an affiliate payout
     */
    public function edit()
    {
        // If the payout is given, make sure it exists
        if (!isset($this->get[0]) || !($payout = $this->OrderAffiliatePayouts->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'plugin/order/admin_payouts/');
        }

        // Get company currencies
        $currencies = $this->Form->collapseObjectArray($this->Currencies->getAll($this->company_id), 'code', 'code');

        // Get affiliate settings
        $affiliate_settings = $this->Form->collapseObjectArray(
            $this->OrderAffiliateSettings->getSettings($payout->affiliate_id),
            'value',
            'key'
        );

        // Set withdrawal currency
        $withdrawal_currency = isset($affiliate_settings['withdrawal_currency'])
            ? $affiliate_settings['withdrawal_currency']
            : 'USD';

        if (!empty($this->post)) {
            // Calculate paid amount
            $paid_amount = $payout->paid_amount;
            if ($payout->requested_amount !== $this->post['requested_amount']) {
                $paid_amount = $this->Currencies->convert(
                    $this->post['requested_amount'],
                    $this->post['requested_currency'],
                    $withdrawal_currency,
                    $this->company_id
                );
            }

            // Get available payout
            $available_payout = $this->getAvailableAffiliatePayout(
                $payout->affiliate_id,
                $payout->paid_currency
            ) + $payout->paid_amount;

            // Validate that the affiliate has sufficient funds to process the payout request
            if ($available_payout < $paid_amount) {
                $this->setMessage(
                    'error',
                    Language::_('AdminPayouts.!error.insufficient_funds_payout_request', true),
                    false,
                    null,
                    false
                );
            } else {
                // Edit payout request
                $fields = [
                    'affiliate_id' => $payout->affiliate_id,
                    'paid_amount' => $paid_amount,
                    'paid_currency' => $withdrawal_currency
                ];
                $this->OrderAffiliatePayouts->edit($payout->id, array_merge($this->post, $fields));

                if (($errors = $this->OrderAffiliatePayouts->errors())) {
                    $this->setMessage('error', $errors, false, null, false);
                } else {
                    $this->flashMessage(
                        'message',
                        Language::_('AdminPayouts.!success.payout_updated', true),
                        null,
                        false
                    );
                    $this->redirect($this->base_uri . 'plugin/order/admin_payouts/');
                }
            }
        }

        $this->set(
            'payment_methods',
            $this->Form->collapseObjectArray(
                $this->OrderAffiliatePaymentMethods->getAll(Configure::get('Blesta.company_id')),
                'name',
                'id'
            )
        );
        $this->set('statuses', $this->OrderAffiliatePayouts->getStatuses());
        $this->set('currencies', $currencies);
        $this->set('vars', !empty($this->post) ? (object)$this->post : $payout);
    }

    /**
     * Approve payout
     */
    public function approve()
    {
        // Get payout or redirect if not given
        $payout_id = isset($this->get[0]) ? $this->get[0] : (isset($this->post['id']) ? $this->post['id'] : null);
        if (!($payout = $this->OrderAffiliatePayouts->get($payout_id))) {
            $this->redirect($this->base_uri . 'plugin/order/admin_payouts/');
        }

        // Set payout status to approved
        if (!empty($this->post)) {
            $this->OrderAffiliatePayouts->edit(
                $payout->id,
                ['status' => 'approved']
            );

            $this->flashMessage('message', Language::_('AdminPayouts.!success.payout_approved', true));
        }

        $this->redirect($this->base_uri . 'plugin/order/admin_payouts/');
    }

    /**
     * Decline payout
     */
    public function decline()
    {
        // Get payout or redirect if not given
        $payout_id = isset($this->get[0]) ? $this->get[0] : (isset($this->post['id']) ? $this->post['id'] : null);
        if (!($payout = $this->OrderAffiliatePayouts->get($payout_id))) {
            $this->redirect($this->base_uri . 'plugin/order/admin_payouts/');
        }

        // Set payout status to declined
        if (!empty($this->post)) {
            $this->OrderAffiliatePayouts->edit(
                $payout->id,
                ['status' => 'declined']
            );

            $this->flashMessage('message', Language::_('AdminPayouts.!success.payout_declined', true));
        }

        $this->redirect($this->base_uri . 'plugin/order/admin_payouts/');
    }
}
