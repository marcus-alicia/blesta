<?php
/**
 * Admin Affiliates Settings controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order.controllers
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminSettings extends OrderAffiliateController
{
    /**
     * Pre Action
     */
    public function preAction()
    {
        parent::preAction();

        $this->requireLogin();

        $this->uses(['Order.OrderAffiliateCompanySettings']);

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);

        Language::loadLang('admin_settings', null, PLUGINDIR . 'order' . DS . 'language' . DS);
    }

    /**
     * Affiliates company settings
     */
    public function index()
    {
        $this->uses(['Currencies']);

        // Get all company affiliate settings
        $settings = [];
        $company_affiliate_settings = $this->OrderAffiliateCompanySettings->getSettings($this->company_id);

        foreach ($company_affiliate_settings as $setting) {
            $settings[$setting->key] = $setting->value;
        }

        // Set message warning about no commissions
        if ($settings['commission_amount'] == 0) {
            $this->setMessage('error', Language::_('AdminSettings.settings.zero_amount', true), false, null, false);
        }

        // Set message explaining setting inheritance
        $this->setMessage('notice', Language::_('AdminSettings.settings.text_inheritance', true), false, null, false);

        // Include WYSIWYG
        $this->Javascript->setFile('blesta/ckeditor/build/ckeditor.js', 'head', VENDORWEBDIR);

        $this->set('vars', $settings);
        $this->set('commission_types', $this->OrderAffiliateCompanySettings->getCommissionTypes());
        $this->set('order_frequencies', $this->OrderAffiliateCompanySettings->getOrderFrequencies());
        $this->set(
            'currencies',
            $this->Form->collapseObjectArray($this->Currencies->getAll($this->company_id), 'code', 'code')
        );

        return $this->renderAjaxWidgetIfAsync(false);
    }

    /**
     * Update settings
     */
    public function update()
    {
        // Set all company affiliate settings
        if (!empty($this->post)) {
            if (!isset($this->post['order_recurring'])) {
                $this->post['order_recurring'] = 'false';
            }

            $this->OrderAffiliateCompanySettings->setSettings(
                $this->company_id,
                $this->post
            );

            $this->flashMessage('message', Language::_('AdminSettings.!success.settings_updated', true));
        }

        $this->redirect($this->base_uri . 'plugin/order/admin_affiliates/');
    }
}
