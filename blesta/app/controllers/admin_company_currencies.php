<?php

/**
 * Admin Company Currency Settings
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminCompanyCurrencies extends AppController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        $this->uses(['Companies', 'Currencies', 'Navigation']);
        $this->components(['SettingsCollection']);

        Language::loadLang('admin_company_currencies');

        // Set the left nav for all settings pages to settings_leftnav
        $this->set(
            'left_nav',
            $this->partial('settings_leftnav', ['nav' => $this->Navigation->getCompany($this->base_uri)])
        );
    }

    /**
     * Currency settings page
     */
    public function index()
    {
        $this->redirect($this->base_uri . 'settings/company/currencies/setup/');
    }

    /**
     * Setup currency settings
     */
    public function setup()
    {
        $vars = [];

        // Update currency settings
        if (!empty($this->post)) {
            // Set empty checkboxes if not given
            if (empty($this->post['show_currency_code'])) {
                $this->post['show_currency_code'] = 'false';
            }
            if (empty($this->post['client_set_currency'])) {
                $this->post['client_set_currency'] = 'false';
            }
            if (empty($this->post['exchange_rates_auto_update'])) {
                $this->post['exchange_rates_auto_update'] = 'false';
            }
            if (empty($this->post['multi_currency_pricing'])) {
                $this->post['multi_currency_pricing'] = 'exchange_rate';
            }

            // Update the settings
            $fields = ['default_currency', 'show_currency_code',
                'client_set_currency', 'exchange_rates_auto_update',
                'exchange_rates_processor', 'exchange_rates_processor_key',
                'exchange_rates_padding', 'multi_currency_pricing'];
            $this->Companies->setSettings($this->company_id, $this->post, $fields);

            $this->setMessage('message', Language::_('AdminCompanyCurrencies.!success.setup_updated', true));
        }

        // Set the settings
        $vars = $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id);

        // Populate the currencies drop down
        $currencies = $this->Currencies->getAll($this->company_id);

        // Set the last time the exchange rate was updated automatically
        $exchange_rate_updated = Language::_('AdminCompanyCurrencies.setup.no_exchange_updated', true);
        if (isset($vars['default_currency'])) {
            foreach ($currencies as $currency) {
                // Use the default currency as basis for the last exchange rate time
                if ($currency->code == $vars['default_currency'] && !empty($currency->exchange_updated)) {
                    $exchange_rate_updated = $this->Date->cast($currency->exchange_updated, 'date_time');
                    break;
                }
            }
        }

        $currencies = $this->Form->collapseObjectArray($currencies, ['code'], 'code', ' ');

        // Get the exchange rate processors
        $processor_data = [];
        foreach ($this->Currencies->getExchangeRateProcessors() as $processor => $name) {
            if (($rate_processor = $this->Currencies->getExchangeRateProcessor($processor))) {
                $processor_data[$processor] = (object)[
                    'processor' => $processor,
                    'name' => $name,
                    'requires_key' => $rate_processor->requiresKey()
                ];
            }
        }

        $this->set('exchange_rate_processor_data', $processor_data);
        $this->set('exchange_rate_last_updated', $exchange_rate_updated);
        $this->set('currencies', $currencies);
        $this->set('vars', $vars);
    }

    /**
     * List of active currencies
     */
    public function active()
    {
        // Set current page of results
        $page = (isset($this->get[0]) ? (int) $this->get[0] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'code');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'asc');

        $currencies = $this->Currencies->getList($this->company_id, $page, [$sort => $order]);
        $total_results = $this->Currencies->getListCount($this->company_id);

        // Get currency formats
        $formats = $this->Currencies->getFormats();

        // Set currency format values
        if (!empty($currencies)) {
            for ($i = 0, $num_currencies = count($currencies); $i < $num_currencies; $i++) {
                if (!empty($formats[$currencies[$i]->format])) {
                    $currencies[$i]->format = $formats[$currencies[$i]->format];
                }
            }
        }

        // Get the default currency for this company to remove the delete option
        $default_currency = $this->SettingsCollection->fetchSetting(
            $this->Companies,
            $this->company_id,
            'default_currency'
        );
        $default_currency = isset($default_currency['value']) ? $default_currency['value'] : '';

        $this->set('currencies', $currencies);
        $this->set('default_currency', $default_currency);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'settings/company/currencies/active/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        // Render the request if ajax
        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));
    }

    /**
     * Add a currency
     */
    public function add()
    {
        // Check for a currency code to pre-populate with
        if (isset($this->get[0])) {
            $currency_code = $this->get[0];
        }

        $vars = [];

        // Create a currency
        if (!empty($this->post)) {
            $this->post['company_id'] = $this->company_id;

            // Remove the exchange rate if it is empty
            if (empty($this->post['exchange_rate'])) {
                unset($this->post['exchange_rate']);
            }

            $this->Currencies->add($this->post);

            if (($errors = $this->Currencies->errors())) {
                // Error, reset vars
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminCompanyCurrencies.!success.add_created', true, $this->post['code'])
                );
                $this->redirect($this->base_uri . 'settings/company/currencies/active/');
            }
        }

        // Set input vars
        if (empty($vars)) {
            $vars = new stdClass();

            // Set currency code if given
            if (isset($currency_code)) {
                $vars->code = $currency_code;
            }
        }

        // Set a default exchange rate if none is set
        if (empty($vars->exchange_rate)) {
            // Default rate 1.000000
            $vars->exchange_rate = number_format(1, 6);
        }

        // Set whether exchange rates are automatically updated or not
        $exchange_rates_auto_update = $this->SettingsCollection->fetchSetting(
            $this->Companies,
            $this->company_id,
            'exchange_rates_auto_update'
        );
        $exchange_rates_auto_update = $exchange_rates_auto_update['value'];

        $this->set('exchange_rates_auto_update', $exchange_rates_auto_update);
        $this->set('formats', $this->Currencies->getFormats());
        $this->set('vars', $vars);
    }

    /**
     * Edit a currency
     */
    public function edit()
    {
        // Redirect if invalid currency code given
        if (!isset($this->get[0]) || !($currency = $this->Currencies->get($this->get[0], $this->company_id))) {
            $this->redirect($this->base_uri . 'settings/company/currencies/active/');
        }

        $currency_code = $this->get[0];
        $vars = [];

        // Edit a currency
        if (!empty($this->post)) {
            $this->post['company_id'] = $this->company_id;

            // Remove the exchange rate if it is empty
            if (empty($this->post['exchange_rate'])) {
                unset($this->post['exchange_rate']);
            }

            $this->Currencies->edit($currency_code, $this->company_id, $this->post);

            if (($errors = $this->Currencies->errors())) {
                // Error, reset vars
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminCompanyCurrencies.!success.edit_updated', true, $currency_code)
                );
                $this->redirect($this->base_uri . 'settings/company/currencies/active/');
            }
        }

        // Set the current currency
        if (empty($vars)) {
            $vars = $currency;
        }

        // Set whether exchange rates are automatically updated or not
        $exchange_rates_auto_update = $this->SettingsCollection->fetchSetting(
            $this->Companies,
            $this->company_id,
            'exchange_rates_auto_update'
        );
        $exchange_rates_auto_update = $exchange_rates_auto_update['value'];

        $this->set('exchange_rates_auto_update', $exchange_rates_auto_update);
        $this->set('formats', $this->Currencies->getFormats());
        $this->set('vars', $vars);
    }

    /**
     * Delete a currency
     */
    public function delete()
    {
        // Redirect if invalid currency code given
        if (!isset($this->post['id']) || !($currency = $this->Currencies->get($this->post['id'], $this->company_id))) {
            $this->redirect($this->base_uri . 'settings/company/currencies/active/');
        }

        // Attempt to delete the currency
        $this->Currencies->delete($currency->code, $this->company_id);

        if (($errors = $this->Currencies->errors())) {
            // Error
            $this->flashMessage('error', $errors);
        } else {
            // Success
            $this->flashMessage(
                'message',
                Language::_('AdminCompanyCurrencies.!success.delete_deleted', true, $currency->code)
            );
        }

        $this->redirect($this->base_uri . 'settings/company/currencies/active/');
    }

    /**
     * Update exchange rates
     */
    public function updateRates()
    {
        $this->Currencies->updateRates();

        if (($errors = $this->Currencies->errors())) {
            // Error
            $this->flashMessage('error', $errors);
        } else {
            // Success
            $this->flashMessage('message', Language::_('AdminCompanyCurrencies.!success.rates_updated', true));
        }

        $this->redirect($this->base_uri . 'settings/company/currencies/setup/');
    }
}
