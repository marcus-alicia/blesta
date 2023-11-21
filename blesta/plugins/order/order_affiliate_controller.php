<?php
/**
 * Order Affiliate Parent Controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderAffiliateController extends OrderController
{
    /**
     * Pre Action
     */
    public function preAction()
    {
        parent::preAction();

        $this->uses([
            'Order.OrderAffiliates',
            'Order.OrderAffiliateSettings',
            'Clients'
        ]);

        // If this is an admin controller, set the portal type to admin
        $this->portal = 'client';

        if (substr($this->controller, 0, 5) == 'admin') {
            $this->portal = 'admin';
        }

        // Validate if the affiliate is enabled
        if (
            $this->portal == 'client'
            && ($affiliate = $this->OrderAffiliates->getByClientId(isset($this->client->id) ? $this->client->id : null))
            && $affiliate->status !== 'active'
            && $this->controller !== 'affiliates'
            && empty($this->action)
        ) {
            $this->redirect($this->base_uri . 'order/affiliates/');
        }

        // Set the left nav for all settings pages to affiliate_leftnav
        if ($this->portal == 'admin') {
            $this->set(
                'left_nav',
                $this->getLeftNav()
            );
        }
    }

    /**
     * Get the total amount available to be paid out to the given affiliate
     *
     * @param int $affiliate_id The ID of the affiliate
     * @param string $currency The 3-character code of the currency in which to get the total
     * @return The total available payout
     */
    protected function getAvailableAffiliatePayout($affiliate_id, $currency)
    {
        $this->uses(
            ['Currencies', 'Order.OrderAffiliates', 'Order.OrderAffiliateSettings']
        );
        if (!($affiliate = $this->OrderAffiliates->get($affiliate_id))) {
            return 0;
        }

        // Get the total amount available from mature referrals
        $total_available = $this->OrderAffiliateSettings->getSetting($affiliate_id, 'total_available');
        $total_available = isset($total_available->value) ? $total_available->value : 0;

        // Get the total amount already paid out
        $total_withdrawn = $this->OrderAffiliateSettings->getSetting($affiliate_id, 'total_withdrawn');
        $total_withdrawn = isset($total_withdrawn->value) ? $total_withdrawn->value : 0;

        // Calculate total amount
        $withdrawal_currency = $this->OrderAffiliateSettings->getSetting($affiliate_id, 'withdrawal_currency');
        $withdrawal_currency = isset($withdrawal_currency->value) ? $withdrawal_currency->value : 0;

        $total = $this->Currencies->convert(
            ($total_available - $total_withdrawn),
            $withdrawal_currency,
            $currency,
            Configure::get('Blesta.company_id')
        );

        return max($total, 0);
    }

    /**
     * Add an affiliate for the given client
     *
     * @param int $client_id The ID of the client
     * @return int|null The ID of the newly added affiliate, null on error
     */
    protected function addAffiliate($client_id)
    {
        $this->uses([
            'Order.OrderAffiliates',
            'Order.OrderAffiliateCompanySettings',
            'Order.OrderAffiliateSettings'
        ]);

        $this->OrderAffiliates->begin();
        $affiliate_id = $this->OrderAffiliates->add(['client_id' => $client_id]);

        if (!($errors = $this->OrderAffiliates->errors())) {
            // Set default affiliate settings based on the company affiliate settings
            $settings = $this->Form->collapseObjectArray(
                $this->OrderAffiliateCompanySettings->getSettings(Configure::get('Blesta.company_id')),
                'value',
                'key'
            );
            unset($settings['signup_content'], $settings['cookie_tld'], $settings['enabled']);
            $settings['total_available'] = 0;
            $settings['total_withdrawn'] = 0;
            $this->OrderAffiliateSettings->setSettings($affiliate_id, $settings);
        }

        if (($errors = $this->OrderAffiliateSettings->errors())) {
            $this->OrderAffiliates->rollback();
            $this->setMessage('error', $errors, false, null, false);
            return null;
        } else {
            $this->OrderAffiliates->commit();
            return $affiliate_id;
        }
    }

    /**
     * Get the affiliate tabs
     *
     * @return string The partial view of the affiliate tabs
     */
    protected function getTabs()
    {
        Language::loadLang('affiliates', null, PLUGINDIR . 'order' . DS . 'language' . DS);

        return $this->partial('affiliate_tabs', ['current_tab' => $this->controller]);
    }

    /**
     * Get the affiliate left navigation bar
     *
     * @return string The partial view of the affiliate left navigation bar
     */
    protected function getLeftNav()
    {
        Language::loadLang('admin_affiliates', null, PLUGINDIR . 'order' . DS . 'language' . DS);

        return $this->partial('admin_affiliate_leftnav', ['current_tab' => $this->controller]);
    }
}
