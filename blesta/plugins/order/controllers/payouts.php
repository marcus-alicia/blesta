<?php
/**
 * Payouts controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order.controllers
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Payouts extends OrderAffiliateController
{
    /**
     * Pre Action
     */
    public function preAction()
    {
        parent::preAction();

        $this->requireLogin();
        $this->uses([
            'Order.OrderAffiliates',
            'Order.OrderAffiliatePaymentMethods',
            'Order.OrderAffiliatePayouts',
            'Order.OrderAffiliateSettings',
            'Currencies'
        ]);

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);
    }

    /**
     * Displays a list of all previous payout requests for a given client
     */
    public function index()
    {
        // Get affiliate or redirect if not given
        if (!($affiliate = $this->OrderAffiliates->getByClientId($this->Session->read('blesta_client_id')))) {
            $this->redirect($this->base_uri . 'order/affiliates/signup/');
        }

        // Set current page of results
        $status = (isset($this->get[0]) ? $this->get[0] : 'pending');
        $page = (isset($this->get[1]) ? (int)$this->get[1] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'id');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        // Set the number of payout requests of each type
        $status_count = [
            'approved' => $this->OrderAffiliatePayouts->getListCount([
                'affiliate_id' => $affiliate->id,
                'status' => 'approved'
            ]),
            'pending' => $this->OrderAffiliatePayouts->getListCount([
                'affiliate_id' => $affiliate->id,
                'status' => 'pending'
            ]),
            'declined' => $this->OrderAffiliatePayouts->getListCount([
                'affiliate_id' => $affiliate->id,
                'status' => 'declined'
            ])
        ];

        $payouts = $this->OrderAffiliatePayouts->getList(
            ['affiliate_id' => $affiliate->id, 'status' => $status],
            $page,
            [$sort => $order]
        );
        $total_results = $this->OrderAffiliatePayouts->getListCount([
            'affiliate_id' => $affiliate->id,
            'status' => $status
        ]);

        // Get available payment methods
        $payment_methods = $this->Form->collapseObjectArray(
            $this->OrderAffiliatePaymentMethods->getAll($this->company_id),
            'name',
            'id'
        );

        $this->set('payouts', $payouts);
        $this->set('status_count', $status_count);
        $this->set('status', $status);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('payment_methods', $payment_methods);
        $this->set('tabs', $this->getTabs());

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination_client'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'order/payouts/index/' . $status . '/[p]/'
            ]
        );
        $this->setPagination($this->get, $settings);

        // Render the request if ajax
        return $this->renderAjaxWidgetIfAsync(isset($this->get[1]) || isset($this->get['sort']));
    }

    /**
     * Request payout page
     */
    public function add()
    {
        // Get affiliate or redirect if not given
        if (!($client = $this->Clients->get($this->Session->read('blesta_client_id')))
            || !($affiliate = $this->OrderAffiliates->getByClientId($client->id))
        ) {
            $this->redirect($this->base_uri . 'order/affiliates/signup/');
        }

        // We only allow one payout request at a time, validate if there is another pending payout request
        if ($this->OrderAffiliatePayouts->getListCount(['affiliate_id' => $affiliate->id, 'status' => 'pending']) > 0) {
            $this->flashMessage(
                'error',
                Language::_('Payouts.!error.exceeded_payout_requests', true),
                null,
                false
            );
            $this->redirect($this->base_uri . 'order/payouts/');
        }

        // Get available payment methods
        $payment_methods = $this->Form->collapseObjectArray(
            $this->OrderAffiliatePaymentMethods->getAll($this->company_id),
            'name',
            'id'
        );

        // Get company currencies
        $currencies = $this->Form->collapseObjectArray($this->Currencies->getAll($this->company_id), 'code', 'code');

        // Get affiliate settings
        $affiliate_settings = $this->Form->collapseObjectArray(
            $this->OrderAffiliateSettings->getSettings($affiliate->id),
            'value',
            'key'
        );

        // Set withdrawal currency
        $withdrawal_currency = isset($affiliate_settings['withdrawal_currency'])
            ? $affiliate_settings['withdrawal_currency']
            : 'USD';

        // Get available payout
        $available_payout = $this->CurrencyFormat->cast(
            $this->getAvailableAffiliatePayout(
                $affiliate->id,
                $withdrawal_currency
            ),
            $withdrawal_currency
        );

        // Set an error message if the available payout is less than the minimum withdrawal amount
        if (isset($affiliate_settings['min_withdrawal_amount'])
            && $available_payout < $affiliate_settings['min_withdrawal_amount']
        ) {
            $this->setMessage(
                'error',
                Language::_('Payouts.!error.minimum_payout_not_available', true),
                false,
                null,
                false
            );
        }

        // Build exchange rates list
        $exchange_rates = [];
        foreach ($this->Currencies->getAll($this->company_id) as $currency) {
            $exchange_rates[$currency->code] = $this->Currencies->convert(
                1,
                $withdrawal_currency,
                $currency->code,
                $this->company_id
            );
        }

        if (!empty($this->post)) {
            // Validate that the affiliate has sufficient funds to process the payout request
            if ($available_payout < $this->post['requested_amount']) {
                $this->flashMessage(
                    'error',
                    Language::_('Payouts.!error.insufficient_funds_payout_request', true),
                    null,
                    false
                );
                $this->redirect($this->base_uri . 'order/payouts/');
            }

            // Add payout request
            $amount = $this->Currencies->convert(
                isset($this->post['requested_amount']) ? $this->post['requested_amount'] : 0,
                $withdrawal_currency,
                isset($this->post['requested_currency'])
                    ? $this->post['requested_currency']
                    : $withdrawal_currency,
                $this->company_id
            );
            $fields = [
                'affiliate_id' => $affiliate->id,
                'payment_method_id' => isset($this->post['payment_method']) ? $this->post['payment_method'] : null,
                'requested_amount' => $amount,
                'paid_amount' => isset($this->post['requested_amount']) ? $this->post['requested_amount'] : null,
                'paid_currency' => $withdrawal_currency
            ];
            $this->OrderAffiliatePayouts->add(array_merge($this->post, $fields));

            if (($errors = $this->OrderAffiliatePayouts->errors())) {
                $this->setMessage('error', $errors, false, null, false);
            } else {
                $this->flashMessage(
                    'message',
                    Language::_('Payouts.!success.payout_requested', true),
                    null,
                    false
                );
                $this->redirect($this->base_uri . 'order/payouts/');
            }
        }

        $this->set('affiliate_settings', $affiliate_settings);
        $this->set('available_payout', $available_payout);
        $this->set('payment_methods', $payment_methods);
        $this->set('currencies', $currencies);
        $this->set('exchange_rates', $exchange_rates);
        $this->set('client', $client);
        $this->set('vars', !empty($this->post) ? (object)$this->post : (object)[]);
        $this->set('tabs', $this->getTabs());

        return $this->renderAjaxWidgetIfAsync();
    }
}
