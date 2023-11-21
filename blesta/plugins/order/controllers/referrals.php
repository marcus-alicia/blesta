<?php
/**
 * Referrals controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order.controllers
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Referrals extends OrderAffiliateController
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
            'Order.OrderAffiliateReferrals'
        ]);

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);
    }

    /**
     * Displays a list of all referrals for a given affiliate
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
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_added');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        // Set the number of referrals of each type
        $status_count = [
            'pending' => $this->OrderAffiliateReferrals->getListCount([
                'affiliate_id' => $affiliate->id,
                'status' => 'pending'
            ]),
            'mature' => $this->OrderAffiliateReferrals->getListCount([
                'affiliate_id' => $affiliate->id,
                'status' => 'mature'
            ]),
            'canceled' => $this->OrderAffiliateReferrals->getListCount([
                'affiliate_id' => $affiliate->id,
                'status' => 'canceled'
            ])
        ];

        $referrals = $this->OrderAffiliateReferrals->getList(
            ['affiliate_id' => $affiliate->id, 'status' => $status],
            $page,
            [$sort => $order]
        );
        $total_results = $this->OrderAffiliateReferrals->getListCount([
            'affiliate_id' => $affiliate->id,
            'status' => $status
        ]);

        $this->set('referrals', $referrals);
        $this->set('status_count', $status_count);
        $this->set('status', $status);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('tabs', $this->getTabs());

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination_client'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'order/referrals/index/' . $status . '/[p]/'
            ]
        );
        $this->setPagination($this->get, $settings);

        // Render the request if ajax
        return $this->renderAjaxWidgetIfAsync(isset($this->get[1]) || isset($this->get['sort']));
    }
}
