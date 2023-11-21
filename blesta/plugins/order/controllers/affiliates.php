<?php
/**
 * Affiliates controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order.controllers
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Affiliates extends OrderAffiliateController
{
    /**
     * Pre Action
     */
    public function preAction()
    {
        parent::preAction();

        $this->action = empty($this->action) ? 'index' : $this->action;
        if ($this->action !== 'index' && $this->action !== 'signup') {
            $this->requireLogin();
        }

        $this->uses([
            'Order.OrderAffiliates',
            'Order.OrderAffiliateReferrals',
            'Order.OrderAffiliatePayouts',
            'Order.OrderAffiliateSettings',
            'Order.OrderAffiliateCompanySettings'
        ]);

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);
    }

    /**
     * Displays affiliate information for a given client
     */
    public function index()
    {
        // Get affiliate or redirect if not given
        $client_id = $this->Session->read('blesta_client_id');
        if (empty($client_id) || !($affiliate = $this->OrderAffiliates->getByClientId($client_id))) {
            $this->redirect($this->base_uri . 'order/affiliates/signup/');
        }

        // Activate affiliate
        if (isset($this->post['activate']) && $this->post['activate'] == 'true') {
            $this->OrderAffiliates->edit($affiliate->id, ['status' => 'active']);
        }

        // Calculate the number of months since this affiliate was added
        $days_active = $this->OrderAffiliates->getAffiliateDaysActive($affiliate->id);

        $affiliate_settings = $affiliate
            ? $this->Form->collapseObjectArray(
                $this->OrderAffiliateSettings->getSettings($affiliate->id),
                'value',
                'key'
            )
            : [];
        $this->set('affiliate', $affiliate);
        $this->set('affiliate_settings', $affiliate_settings);
        $this->set(
            'available_payout',
            $this->getAvailableAffiliatePayout(
                $affiliate->id,
                isset($affiliate_settings['withdrawal_currency'])
                    ? $affiliate_settings['withdrawal_currency']
                    : 'USD'
            )
        );
        $this->set('referral_link', trim($this->base_url, '/') . $this->base_uri . 'order/forms/a/' . $affiliate->code);
        $this->set('days_active', $days_active);
        $this->set('tabs', $this->getTabs());

        return $this->renderAjaxWidgetIfAsync();
    }

    /**
     * Signup page for affiliates
     */
    public function signup()
    {
        // Redirect if affiliate already exists
        $client_id = $this->Session->read('blesta_client_id');
        if (!empty($client_id) && ($affiliate = $this->OrderAffiliates->getByClientId($client_id))) {
            $this->redirect($this->base_uri . 'order/affiliates/');
        }

        if (!empty($this->post['signup'])) {
            $affiliate_id = $this->addAffiliate($client_id);

            if ($affiliate_id) {
                $this->flashMessage(
                    'message',
                    Language::_('Affiliates.!success.affiliate_added', true),
                    null,
                    false
                );
                $this->redirect($this->base_uri . 'order/affiliates/');
            }
        }

        $signup_content = $this->OrderAffiliateCompanySettings->getSetting(
            Configure::get('Blesta.company_id'),
            'signup_content'
        );
        $this->set('logged_in', $this->isLoggedIn());
        $this->set('signup_content', $signup_content ? $signup_content->value : '');

        return $this->renderAjaxWidgetIfAsync();
    }
}
