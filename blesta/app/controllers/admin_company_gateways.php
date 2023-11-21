<?php

/**
 * Admin Company Gateway Settings
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminCompanyGateways extends AppController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        $this->uses(['GatewayManager', 'Navigation']);

        Language::loadLang('admin_company_gateways');

        // Set the left nav for all settings pages to settings_leftnav
        $this->set(
            'left_nav',
            $this->partial('settings_leftnav', ['nav' => $this->Navigation->getCompany($this->base_uri)])
        );
    }

    /**
     * Gateway Settings index page
     */
    public function index()
    {
        $this->redirect($this->base_uri . 'settings/company/gateways/installed/');
    }

    /**
     * Gateways Installed page
     */
    public function installed()
    {
        $this->setTabs();
        $this->set('show_left_nav', !$this->isAjax());

        $gateways = $this->GatewayManager->getAll($this->company_id);

        $this->set('gateway_types', ['merchant', 'nonmerchant']);
        $this->set('gateways', $gateways);
        return $this->renderAjaxWidgetIfAsync();
    }

    /**
     * Gateways Available page
     */
    public function available()
    {
        $this->setTabs();
        $this->set('show_left_nav', !$this->isAjax());
        $this->set('gateways', $this->GatewayManager->getAvailable(null, $this->company_id));
        return $this->renderAjaxWidgetIfAsync();
    }

    /**
     * Sets the installed/available tabs to the view
     */
    private function setTabs()
    {
        $this->set(
            'link_tabs',
            [
                [
                    'name' => Language::_('AdminCompanyGateways.!tab.installed', true),
                    'uri' => 'installed'
                ],
                [
                    'name' => Language::_('AdminCompanyGateways.!tab.available', true),
                    'uri' => 'available'
                ]
            ]
        );
    }

    /**
     * Manage a gateway
     */
    public function manage()
    {
        // Redirect if the gateway ID is invalid
        if (!isset($this->get[0])
            || !($gateway_info = $this->GatewayManager->get((int) $this->get[0]))
            || ($gateway_info->company_id != $this->company_id)
        ) {
            $this->redirect($this->base_uri . 'settings/company/gateways/installed/');
        }

        $this->uses(['Currencies']);
        $this->components(['Gateways']);
        $this->helpers(['DataStructure']);

        $gateway = $this->Gateways->create($gateway_info->class, $gateway_info->type);

        // Ready the array helper to convert meta object data to a key/value array
        $this->ArrayHelper = $this->DataStructure->create('Array');
        $vars = $this->ArrayHelper->numericToKey($gateway_info->meta, 'key', 'value');

        if (!empty($this->post)) {
            // Update the gateway's meta data and currencies
            $currencies = isset($this->post['currencies']) ? $this->post['currencies'] : [];

            // Remove currencies from meta data
            unset($this->post['currencies']);

            $this->GatewayManager->edit($this->get[0], ['meta' => $this->post, 'currencies' => $currencies]);

            if (($errors = $this->GatewayManager->errors())) {
                // Error, reset vars
                $this->setMessage('error', $errors);
                $vars = $this->post;
                $vars['currencies'] = $currencies;
            } else {
                // Success
                $this->flashMessage('message', Language::_('AdminCompanyGateways.!success.manage_updated', true));
                $this->redirect($this->base_uri . 'settings/company/gateways/installed/');
            }
        } else {
            // Set currencies in use by this gateway
            foreach ($gateway_info->currencies as $currency) {
                $vars['currencies'][] = $currency->currency;
            }
        }

        // Get all currencies for this company
        $company_currencies = $this->Currencies->getAll($this->company_id);

        // Create a list of gateway currencies and their statuses
        $gateways_in_use = [];
        $gateway_currencies = [];
        $i = 0;
        foreach ($gateway->getCurrencies() as $currency) {
            $gateway_currencies[$i] = new stdClass();
            $gateway_currencies[$i]->code = $currency;
            $gateway_currencies[$i]->disabled = false;
            $gateway_currencies[$i]->available = false;
            $gateway_currencies[$i]->in_use_by = null;

            // This currency is in use by another gateway, mark it disabled
            if ($gateway_info->type == 'merchant'
                && $this->GatewayManager->verifyCurrency($currency, $this->company_id, $gateway_info->id)
            ) {
                // Fetch the gateway that is using this currency
                if (!isset($gateways_in_use[$currency])) {
                    // Set all currencies this gateway is using so we don't need to fetch it again
                    if (($alt_gateway = $this->GatewayManager->getInstalledMerchant($this->company_id, $currency))) {
                        foreach ($alt_gateway->currencies as $alt_gateway_currency) {
                            $gateways_in_use[$alt_gateway_currency->currency] = $alt_gateway;
                        }
                    }

                    unset($alt_gateway);
                }

                $gateway_currencies[$i]->in_use_by = (isset($gateways_in_use[$currency])
                    ? $gateways_in_use[$currency]
                    : null
                );
                $gateway_currencies[$i]->disabled = true;
            }

            // Set whether this gateway currency is available for use by this company
            foreach ($company_currencies as $company_currency) {
                // This currency is available for use by this company
                if ($company_currency->code == $currency) {
                    $gateway_currencies[$i]->available = true;
                    break;
                }
            }

            $i++;
        }

        $this->set('gateway_currencies', $gateway_currencies);
        $this->set('gateway_info', $gateway_info);
        $this->set('gateway', $gateway);
        $this->set('content', $gateway->getSettings($vars));
        $this->set('vars', $vars);
        $this->structure->set(
            'page_title',
            Language::_('AdminCompanyGateways.manage.page_title', true, $gateway_info->name)
        );
    }

    /**
     * Install a gateway for this company
     */
    public function install()
    {
        if (!isset($this->post['id'])) {
            $this->redirect($this->base_uri . 'settings/company/gateways/available/');
        }

        $gateway_id = $this->GatewayManager->add([
            'class' => $this->post['id'],
            'company_id' => $this->company_id,
            'type' => isset($this->post['type']) ? $this->post['type'] : 'merchant'
        ]);

        if (($errors = $this->GatewayManager->errors())) {
            $this->flashMessage('error', $errors);
            $this->redirect($this->base_uri . 'settings/company/gateways/available/');
        } else {
            $this->flashMessage('message', Language::_('AdminCompanyGateways.!success.installed', true));
            $this->redirect($this->base_uri . 'settings/company/gateways/manage/' . $gateway_id);
        }
    }

    /**
     * Uninstall a gateway for this company
     */
    public function uninstall()
    {
        if (!isset($this->post['id']) || !($gateway = $this->GatewayManager->get($this->post['id']))) {
            $this->redirect($this->base_uri . 'settings/company/gateways/installed/');
        }

        $this->GatewayManager->delete($this->post['id']);

        if (($errors = $this->GatewayManager->errors())) {
            $this->setMessage('error', $errors);
        } else {
            $this->flashMessage('message', Language::_('AdminCompanyGateways.!success.uninstalled', true));
            $this->redirect($this->base_uri . 'settings/company/gateways/installed/');
        }
    }

    /**
     * Upgrade a gateway
     */
    public function upgrade()
    {
        // Fetch the module to upgrade
        if (!isset($this->post['id']) || !($gateway = $this->GatewayManager->get($this->post['id']))) {
            $this->redirect($this->base_uri . 'settings/company/gateways/installed/');
        }

        $this->GatewayManager->upgrade($this->post['id']);

        if (($errors = $this->GatewayManager->errors())) {
            $this->flashMessage('error', $errors);
        } else {
            $this->flashMessage('message', Language::_('AdminCompanyGateways.!success.upgraded', true));
        }
        $this->redirect($this->base_uri . 'settings/company/gateways/installed/');
    }
}
