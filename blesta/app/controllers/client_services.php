<?php

use Blesta\Core\Pricing\Presenter\Type\PresenterInterface;
use Blesta\Core\Util\Filters\ServiceFilters;
use Blesta\Core\Util\Input\Fields\Html as FieldsHtml;
use Blesta\Core\Util\PackageOptions\Logic as OptionLogic;

/**
 * Client portal services controller
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientServices extends ClientController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        parent::preAction();

        // Load models, language
        $this->uses(['Clients', 'Packages', 'Services', 'PluginManager', 'ModuleManager']);
        $this->components(['SettingsCollection']);
    }

    /**
     * List services
     */
    public function index()
    {
        $status = (isset($this->get[0]) ? $this->get[0] : 'active');
        $page = (isset($this->get[1]) ? (int)$this->get[1] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_added');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        // Set filters from post input
        $post_filters = [];
        if (isset($this->post['filters'])) {
            $post_filters = $this->post['filters'];
            unset($this->post['filters']);

            foreach($post_filters as $filter => $value) {
                if (empty($value)) {
                    unset($post_filters[$filter]);
                }
            }
        }
        $filters = $post_filters;

        // Exclude domains, if the domain manager plugin is installed
        if ($this->PluginManager->isInstalled('domains', Configure::get('Blesta.company_id'))) {
            $filters['type'] = 'services';
        }

        $services = $this->Services->getList(
            $this->client->id,
            $status,
            $page,
            [$sort => $order],
            false,
            $filters
        );
        $total_results = $this->Services->getListCount($this->client->id, $status, false, null, $filters);

        // Set the number of services of each type, not including children
        $status_count = [
            'active' => $this->Services->getStatusCount($this->client->id, 'active', false, $filters),
            'canceled' => $this->Services->getStatusCount($this->client->id, 'canceled', false, $filters),
            'pending' => $this->Services->getStatusCount($this->client->id, 'pending', false, $filters),
            'suspended' => $this->Services->getStatusCount($this->client->id, 'suspended', false, $filters),
        ];

        // Set language for periods
        $periods = $this->Packages->getPricingPeriods();
        foreach ($this->Packages->getPricingPeriods(true) as $period => $lang) {
            $periods[$period . '_plural'] = $lang;
        }

        // Set the expected service renewal price
        foreach ($services as $service) {
            $service->renewal_price = $this->Services->getRenewalPrice($service->id);
        }

        // Set the input field filters for the widget
        $service_filters = new ServiceFilters();
        $this->set(
            'filters',
            $service_filters->getFilters(
                [
                    'language' => Configure::get('Blesta.language'),
                    'company_id' => Configure::get('Blesta.company_id'),
                    'client' => true
                ],
                $post_filters
            )
        );

        $this->set('filter_vars', $post_filters);
        $this->set('periods', $periods);
        $this->set('client', $this->client);
        $this->set('status', $status);
        $this->set('services', $services);
        $this->set('status_count', $status_count);
        $this->set('widget_state', isset($this->widgets_state['services']) ? $this->widgets_state['services'] : null);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination_client'),
            [
                'total_results' => $total_results,
                'uri' => $this->Html->safe($this->base_uri . 'services/index/' . $status . '/[p]/'),
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(
                isset($this->get['whole_widget']) ? null : (isset($this->get[1]) || isset($this->get['sort']))
            );
        }
    }

    /**
     * Manage service
     */
    public function manage()
    {
        $this->uses(['Coupons', 'ModuleManager']);

        // Ensure we have a service
        if (!($service = $this->Services->get((int)$this->get[0])) || $service->client_id != $this->client->id) {
            $this->redirect($this->base_uri);
        }

        $package = $this->Packages->get($service->package->id);
        $module = $this->ModuleManager->initModule($service->package->module_id);
        $module->base_uri = $this->base_uri;

        // Determine the plugin/method being managed
        $method = null;
        $plugin_id = null;
        $tab_view = null;

        // If the first GET argument is a number, we must infer this to mean a plugin, not a module
        if (isset($this->get[1])) {
            // Disallow clients from viewing module/plugin tabs if the service is not active
            if ($service->status != 'active') {
                $statuses = $this->Services->getStatusTypes();
                $this->flashMessage(
                    'error',
                    Language::_('ClientServices.!error.tab_unavailable', true, $statuses[$service->status])
                );
                $this->redirect($this->base_uri . 'services/manage/' . $service->id);
            }

            // Process the selected module/plugin tab
            if (is_numeric($this->get[1])) {
                // No plugin method was given to call, or the plugin is not supported by the service
                $valid_plugins = $this->Form->collapseObjectArray($package->plugins, 'plugin_id', 'plugin_id');
                if (!isset($this->get[2]) || !array_key_exists($this->get[1], $valid_plugins)) {
                    $this->redirect($this->base_uri . 'services/manage/' . $service->id . '/');
                }

                $plugin_id = $this->get[1];
                $method = $this->get[2];

                // Process and retrieve the plugin tab content
                $tab_view = $this->processPluginTab($plugin_id, $method, $service);
            } else {
                $method = $this->get[1];

                // Process and retrieve the module tab content
                $tab_view = $this->processModuleTab($module, $method, $package, $service);
            }
        } else {
            // Process and retrieve the default management tab content
            $partial_tab_view = $this->processModuleTab(
                $module,
                'getClientManagementContent',
                $package,
                $service,
                true
            );

            if (!empty($partial_tab_view)) {
                $this->set('partial_tab_view', $partial_tab_view);
            }
        }

        // Set sidebar tabs
        $this->buildTabs($service, $package, $module, $method, $plugin_id);
        $this->set('tab_view', $tab_view);

        // Set language for periods
        $periods = $this->Packages->getPricingPeriods();
        foreach ($this->Packages->getPricingPeriods(true) as $period => $lang) {
            $periods[$period . '_plural'] = $lang;
        }

        // Set whether the client can cancel a service
        // First check whether the service is already canceled
        if (!$service->date_canceled
            || (
                $service->date_canceled
                && strtotime($this->Date->cast($service->date_canceled, 'date_time')) > strtotime(date('c'))
            )
        ) {
            // Service has not already been canceled, check whether the
            // setting is enabled for clients to cancel services
            $client_cancel_service = $this->client->settings['clients_cancel_services'] == 'true';
        } else {
            // Service is already canceled, can't cancel it again
            $client_cancel_service = false;
        }

        // Set whether the client can renew a service
        $client_renew_service = ($this->client->settings['clients_renew_services'] ?? null) == 'true';

        // Set whether the client can change the service term
        $client_change_service_term = isset($this->client->settings['client_change_service_term'])
            && $this->client->settings['client_change_service_term'] == 'true';
        $alternate_service_terms = [];
        if ($client_change_service_term && isset($service->package_pricing->id)
            && isset($service->package_pricing->period) && $service->package_pricing->period != 'onetime') {
            $alternate_service_terms = $this->getPackageTerms($package, [$service->package_pricing->id]);
        }

        // Set whether the client can upgrade the service to another package in the same group
        $client_change_service_package = isset($this->client->settings['client_change_service_package'])
            && $this->client->settings['client_change_service_package'] == 'true';
        if ($client_change_service_package) {
            $upgradable_packages = $this->getUpgradablePackages(
                $package,
                ($service->parent_service_id ? 'addon' : 'standard')
            );
            $client_change_service_package = (!empty($upgradable_packages));
        }

        // Set whether the any config options are available to be added/updated
        $available_options = $this->getAvailableOptions($service);

        // Determine whether a recurring coupon applies to this service
        $recurring_coupon = false;
        if ($service->coupon_id && $service->date_renews) {
            $recurring_coupon = $this->Coupons->getRecurring(
                $service->coupon_id,
                $service->package_pricing->currency,
                $service->date_renews . 'Z'
            );
        }

        // Set the expected service renewal price
        $service->renewal_price = $this->Services->getRenewalPrice($service->id);

        // Display a notice regarding this service having queued service changes
        $queued_changes = $this->pendingServiceChanges($service->id);
        if (!empty($queued_changes) && $this->queueServiceChanges()) {
            $this->setMessage('notice', Language::_('ClientServices.!notice.queued_service_change', true));
        }

        // Set partial for the service information box
        $service_params = [
            'periods' => $periods,
            'service' => $service,
            'next_invoice_date' => $this->Services->getNextInvoiceDate($service->id),
            'client_cancel_service' => $client_cancel_service,
            'client_renew_service' => $client_renew_service,
            'client_change_service_term' => $client_change_service_term,
            'client_change_service_package' => $client_change_service_package,
            'alternate_service_terms' => $alternate_service_terms,
            'available_config_options' => (!empty($available_options)),
            'recurring_coupon' => $recurring_coupon,
            'queued_service_change' => !empty($queued_changes)
        ];
        $this->set('service_infobox', $this->partial('client_services_service_infobox', $service_params));

        $this->set('service', $service);
        $this->set('package', $package);

        // Display a notice regarding the service being suspended/canceled
        $error_notice = [];
        if (!empty($service->date_suspended)) {
            $error_notice[] = Language::_(
                'ClientServices.manage.text_date_suspended',
                true,
                $this->Date->cast($service->date_suspended)
            );
        }
        if (!empty($service->date_canceled)) {
            $scheduled = false;
            if ($this->Date->toTime($this->Date->cast($service->date_canceled))
                > $this->Date->toTime($this->Date->cast(date('c')))
            ) {
                $scheduled = true;
            }
            $error_notice[] = Language::_(
                'ClientServices.manage.text_date_' . ($scheduled ? 'to_cancel' : 'canceled'),
                true,
                $this->Date->cast($service->date_canceled)
            );
        }
        if (!empty($error_notice)) {
            $this->setMessage('error', ['notice' => $error_notice]);
        }

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(isset($this->get['whole_widget']) ? null : false);
        }
    }

    /**
     * Processes and retrieves the module tab content for the given method
     *
     * @param Module $module The module instance
     * @param string $method The method on the module to call to retrieve the tab content
     * @param stdClass $package An stdClass object representing the package
     * @param stdClass $service An stdClass object representing the service being managed
     * @param bool $partial True to return the module tab as a partial view
     * @return string The tab content
     */
    private function processModuleTab($module, $method, stdClass $package, stdClass $service, $partial = false)
    {
        $content = '';

        // Get tabs
        $client_tabs = $module->getClientServiceTabs($service);
        if ($partial) {
            $client_tabs[$method] = $method;
        }
        $valid_method = array_key_exists(strtolower($method), array_change_key_case($client_tabs, CASE_LOWER));

        // Load/process the tab request
        if ($valid_method && is_callable([$module, $method])) {
            // Set the module row used for this service
            $module->setModuleRow($module->getModuleRow($service->module_row_id));

            // Call the module method and set any messages to the view
            $content = $module->{$method}($package, $service, $this->get, $this->post, $this->files);
            $this->setServiceTabMessages($module->errors(), $module->getMessages());
        } else {
            // Invalid method called, redirect
            if (!$partial) {
                $this->redirect($this->base_uri . 'services/manage/' . $service->id);
            }
        }

        return $content;
    }

    /**
     * Processes and retrieves the plugin tab content for the given method
     *
     * @param int $plugin_id The ID of the plugin
     * @param string $method The method on the plugin to call to retrieve the tab content
     * @param stdClass $service An stdClass object representing the service being managed
     * @return string The tab content
     */
    private function processPluginTab($plugin_id, $method, stdClass $service)
    {
        $content = '';

        if (($plugin = $this->getPlugin($plugin_id))) {
            $plugin->base_uri = $this->base_uri;

            // Get tabs
            $client_tabs = $plugin->getClientServiceTabs($service);
            $valid_method = array_key_exists(strtolower($method), array_change_key_case($client_tabs, CASE_LOWER));

            // Retrieve the plugin tab content
            if ($valid_method && is_callable([$plugin, $method])) {
                // Call the module method and set any messages to the view
                $content = $plugin->{$method}($service, $this->get, $this->post, $this->files);
                $this->setServiceTabMessages($plugin->errors(), $plugin->getMessages());
            } else {
                // Invalid method called, redirect
                $this->redirect($this->base_uri . 'services/manage/' . $service->id);
            }
        }

        return $content;
    }

    /**
     * Sets messages to the view based on the given errors and messages provided
     *
     * @param array|bool|null $errors An array of error messages (optional)
     * @param array $messages An array of any other messages keyed by type (optional)
     */
    private function setServiceTabMessages($errors = null, array $messages = null)
    {
        // Prioritize error messages over any other messages
        if (!empty($errors)) {
            $this->setMessage('error', $errors);
        } elseif (!empty($messages)) {
            // Display messages if any
            foreach ($messages as $type => $message) {
                $this->setMessage($type, $message);
            }
        } elseif (!empty($this->post)) {
            // Default to display a message after POST
            $this->setMessage('success', Language::_('ClientServices.!success.manage.tab_updated', true));
        }
    }

    /**
     * Fetches a list of package options that are addable or editable for this service
     *
     * @param stdClass $service An stdClass object representing the service
     * @return array An array of all addable and editable package options for the service
     */
    private function getAvailableOptions($service)
    {
        // Fetch the package options that can be added or updated
        return $this->getSettableOptions(
            $service->package->id,
            $service->package_pricing->term,
            $service->package_pricing->period,
            $service->package_pricing->currency,
            $service->options,
            $this->getSelectedOptionIds($service)
        );
    }

    /**
     * Fetches a set of available package option IDs for the given service that the user can add or update
     * @see ClientServices::getAvailableOptions
     *
     * @param stdClass $service An stdClass object representing the service
     * @return array A key/value array of available options where each key is the option ID
     */
    private function getSelectedOptionIds($service)
    {
        $this->uses(['PackageOptions']);

        // Create a list of option IDs currently set
        $option_ids = [];
        foreach ($service->options as $option) {
            $option_ids[] = $option->option_id;
        }

        // Fetch addable package options that don't currently exist
        $filters = array_merge(
            ['addable' => 1, 'disallow' => $option_ids],
            $this->PackageOptions->formatServiceOptions($service->options)
        );
        $options = $this->PackageOptions->getAllByPackageId(
            $service->package->id,
            $service->package_pricing->term,
            $service->package_pricing->period,
            $service->package_pricing->currency,
            null,
            $filters
        );

        // Key each addable option by ID
        $available_option_ids = [];
        foreach ($options as $option) {
            $available_option_ids[$option->id] = '';
        }

        return $available_option_ids;
    }

    /**
     * Returns an array of all pricing terms for the given package that optionally
     * recur and do not match the given pricing IDs
     *
     * @param stdClass $package A stdClass object representing the package to fetch the terms for
     * @param array $pricing_ids An array of pricing IDs to exclude (optional)
     * @param mixed $service An stdClass object representing the service (optional)
     * @param bool $remove_non_recurring_terms True to include only package terms that recur,
     *  or false to include all (optional, default true)
     * @param bool $match_periods True to only set terms that match the period set for the
     *  $service (i.e. recurring periods -> recurring periods OR one-time -> one-time)
     *  $remove_non_recurring_terms should be set to false when this is true (optional, default false)
     * @param bool $upgrade Whether these terms are being fetched for a package upgrade
     * @return array An array of key/value pairs where the key is the package pricing ID and the
     *  value is a string representing the price, term, and period.
     */
    private function getPackageTerms(
        stdClass $package,
        array $pricing_ids = [],
        $service = null,
        $remove_non_recurring_terms = true,
        $match_periods = false,
        $upgrade = false
    ) {
        $singular_periods = $this->Packages->getPricingPeriods();
        $plural_periods = $this->Packages->getPricingPeriods(true);
        $terms = [];
        if (isset($package->pricing) && !empty($package->pricing)) {
            foreach ($package->pricing as $price) {
                // Ignore non-recurring terms, and exclude the given pricing IDs
                if (($remove_non_recurring_terms && $price->period == 'onetime')
                    || in_array($price->id, $pricing_ids)) {
                    continue;
                }

                // Check that the service period matches this term's period
                // (i.e. recurring -> recurring OR one-time -> one-time)
                if ($match_periods && $service
                    && !(
                        $service->package_pricing->period == $price->period
                        || ($price->period != 'onetime' && $service->package_pricing->period != 'onetime')
                    )
                ) {
                    continue;
                }

                // Set the package pricing with to the service override values
                $amount = $price->price;
                $renew_amount = (isset($price->price_renews) ? $price->price_renews : 0);
                $currency = $price->currency;
                $term = 'ClientServices.get_package_terms.term';

                if ($service && $service->pricing_id == $price->id
                    && !empty($service->override_price) && !empty($service->override_currency)) {
                    $amount = $service->override_price;
                    $currency = $service->override_currency;
                    $renew_amount = $amount;
                } else {
                    // Determine the price to use if it is not being overriden by the override price
                    if ($service && isset($price->price_renews) && (!$upgrade || $package->upgrades_use_renewal)) {
                        $amount = $renew_amount;
                    } elseif (isset($price->price_renews) && $price->price != $price->price_renews) {
                        $term = 'ClientServices.get_package_terms.term_recurring';
                    }
                }

                $terms[$price->id] = Language::_(
                    $term,
                    true,
                    $price->term,
                    $price->term != 1 ? $plural_periods[$price->period] : $singular_periods[$price->period],
                    $this->CurrencyFormat->format($amount, $currency),
                    $this->CurrencyFormat->format($renew_amount, $currency)
                );
                if ($price->period == 'onetime') {
                    $terms[$price->id] = Language::_(
                        'ClientServices.get_package_terms.term_onetime',
                        true,
                        $price->term != 1 ? $plural_periods[$price->period] : $singular_periods[$price->period],
                        $this->CurrencyFormat->format($amount, $currency)
                    );
                }
            }
        }
        return $terms;
    }

    /**
     * Returns an array of packages that can be upgraded/downgraded from the same package group
     *
     * @param stdClass $package The package from which to fetch other upgradable packages
     * @param string $type The type of package group ("standard" or "addon")
     * @return array An array of stdClass objects representing packages in the same group
     */
    private function getUpgradablePackages($package, $type)
    {
        if (!$package || empty($package->module_id)) {
            return [];
        }

        $packages = $this->Packages->getCompatiblePackages($package->id, $package->module_id, $type);

        $restricted_packages = $this->Clients->getRestrictedPackages($this->client->id);
        $restricted_package_ids = [];
        foreach ($restricted_packages as $package_ids) {
            $restricted_package_ids[] = $package_ids->package_id;
        }

        foreach ($packages as $index => $temp_package) {
            // Remove unavailable restricted packages
            if ($temp_package->status == 'inactive'
                || ($temp_package->status == 'restricted' && !in_array($temp_package->id, $restricted_package_ids))) {
                unset($packages[$index]);
                continue;
            }

            // Remove the given package from the list since you cannot upgrade/downgrade to the identical package
            if ($package->id == $temp_package->id) {
                unset($packages[$index]);
            }
        }

        return array_values($packages);
    }

    /**
     * Renew Service
     */
    public function renew()
    {
        $this->uses(['Services', 'Invoices', 'Users', 'Companies']);
        $this->components(['Record']);

        $client_can_renew_service = isset($this->client->settings['clients_renew_services'])
            && $this->client->settings['clients_renew_services'] == 'true';
        $client_can_change_term = isset($this->client->settings['client_change_service_term'])
            && $this->client->settings['client_change_service_term'] == 'true';

        // Ensure we have a service that belongs to the client and is not currently canceled or suspended
        if (!$client_can_renew_service
            || !($service = $this->Services->get((int)$this->get[0]))
            || $service->client_id != $this->client->id
            || in_array($service->status, ['canceled', 'suspended'])
        ) {
            if ($this->isAjax()) {
                exit();
            }
            $this->redirect($this->base_uri);
        }

        // Determine whether invoices for this service remain unpaid
        $unpaid_invoices = $this->Invoices->getAllWithService($service->id, $this->client->id, 'open');

        // Get service package
        $package = $this->Packages->get($service->package->id);

        // Get service terms
        $terms = $this->getPackageTerms($package, [], $service);

        // Set the confirmation message for renewing the service
        $renew_messages = [];

        foreach ($terms as $pricing_id => $term) {
            $term_name = $term;
            $term = $this->Record->select('pricings.*')->
                from('pricings')->
                innerJoin('package_pricing', 'package_pricing.pricing_id', '=', 'pricings.id', false)->
                where('package_pricing.id', '=', $pricing_id)->
                fetch();
            $date_format = $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'date_format');
            $next_renewal_date = $this->Date->modify(
                date('c', strtotime($service->date_renews)),
                '+' . $term->term . ' ' . $term->period,
                $date_format->value,
                Configure::get('Blesta.company_timezone')
            );
            $renew_messages[$pricing_id] = Language::_(
                'ClientServices.renew.confirm_renew',
                true,
                $term_name,
                $next_renewal_date
            );
        }

        // Renew service
        if (!empty($this->post)) {
            // Verify that client's password is correct, set $errors otherwise
            $user = $this->Users->get($this->Session->read('blesta_id'));
            $username = ($user ? $user->username : '');

            if ($this->Users->auth($username, ['password' => $this->post['password']])) {
                // Disallow renew if the current service has not been paid
                if (!empty($unpaid_invoices)) {
                    $this->flashMessage('error', Language::_('ClientServices.!error.invoices_renew_service', true));
                    $this->redirect($this->base_uri . 'services/manage/' . $service->id . '/');
                }

                // If clients can't change the term, only use the current one
                if (!$client_can_change_term) {
                    $this->post['pricing_id'] = $service->package_pricing->id;
                }

                // Redirect if no valid term pricing ID was given
                if (empty($this->post['pricing_id']) || !array_key_exists($this->post['pricing_id'], $terms)) {
                    $this->redirect($this->base_uri . 'services/manage/' . $service->id . '/');
                }

                // Create the invoice for these renewing services
                if (empty($errors)) {
                    $this->Invoices->createRenewalFromService($service->id, 1, $this->post['pricing_id']);
                }
            } else {
                $errors = ['password' => ['mismatch' => Language::_('ClientServices.!error.password_mismatch', true)]];
            }

            if (!empty($errors) || ($errors = $this->Invoices->errors())) {
                $this->flashMessage('error', $errors);
                $this->redirect($this->base_uri . 'services/manage/' . $service->id . '/');
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('ClientServices.!success.service_renewed', true)
                );
                $this->redirect($this->base_uri . 'pay/');
            }
        }

        $this->set('service', $service);
        $this->set('package', $package);
        $this->set('renew_messages', $renew_messages);
        $this->set('client_can_change_term', $client_can_change_term);
        $this->set('terms', ['' => Language::_('AppController.select.please', true)] + $terms);

        echo $this->view->fetch('client_services_renew');
        return false;
    }

    /**
     * Cancel Service
     */
    public function cancel()
    {
        $this->uses(['Currencies', 'Users']);

        $client_can_cancel_service = isset($this->client->settings['clients_cancel_services'])
            && $this->client->settings['clients_cancel_services'] == 'true';

        // Ensure we have a service that belongs to the client and is not currently canceled or suspended
        if (!$client_can_cancel_service
            || !($service = $this->Services->get((int)$this->get[0]))
            || $service->client_id != $this->client->id
            || in_array($service->status, ['canceled', 'suspended'])
        ) {
            if ($this->isAjax()) {
                exit();
            }
            $this->redirect($this->base_uri);
        }

        if (!empty($this->post)) {
            $data = [
                'date_canceled' => isset($this->post['date_canceled']) ? $this->post['date_canceled'] : null,
                'cancellation_reason' => isset($this->post['cancellation_reason']) ? $this->post['cancellation_reason'] : null
            ];

            // Verify that client's password is correct, set $errors otherwise
            $user = $this->Users->get($this->Session->read('blesta_id'));
            $username = ($user ? $user->username : '');

            if ($this->Users->auth($username, ['password' => $this->post['password']])) {
                // Cancel the service
                switch ($this->post['cancel']) {
                    case 'now':
                        $this->Services->cancel($service->id, $data);
                        break;
                    case 'term':
                        // Cancel at end of service term
                        $data['date_canceled'] = 'end_of_term';
                        $this->Services->cancel($service->id, $data);
                        break;
                    default:
                        // Do not cancel
                        $this->Services->unCancel($service->id);
                        break;
                }
            } else {
                $errors = ['password' => ['mismatch' => Language::_('ClientServices.!error.password_mismatch', true)]];
            }

            if (!empty($errors) || ($errors = $this->Services->errors())) {
                $this->flashMessage('error', $errors);
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_(
                        'ClientServices.!success.service_'
                        . ($this->post['cancel'] == 'term' ? 'schedule_' : ($this->post['cancel'] == '' ? 'not_' : ''))
                        . 'canceled',
                        true
                    )
                );
            }

            $this->redirect($this->base_uri . 'services/manage/' . $service->id . '/');
        }

        // Set language for periods
        $periods = $this->Packages->getPricingPeriods();
        foreach ($this->Packages->getPricingPeriods(true) as $period => $lang) {
            $periods[$period . '_plural'] = $lang;
        }

        // Set whether the client can cancel a service
        // First check whether the service is already canceled
        if (!$service->date_canceled
            || (
                $service->date_canceled
                && strtotime($this->Date->cast($service->date_canceled, 'date_time')) > strtotime(date('c'))
            )
        ) {
            // Service has not already been canceled, check whether the setting is enabled
            // for clients to cancel services
            $client_cancel_service = $this->client->settings['clients_cancel_services'] == 'true';
        } else {
            // Service is already canceled, can't cancel it again
            $client_cancel_service = false;
        }

        // Set the cancellation to be at the end of the term
        if (!isset($vars)) {
            $vars = (object)['cancel' => ''];
        }

        // Set the confirmation message for canceling the service
        $cancel_messages = [
            'now' => Language::_('ClientServices.cancel.confirm_cancel_now', true),
            'term' => Language::_('ClientServices.cancel.confirm_cancel', true)
        ];
        if (isset($service->package_pricing->cancel_fee) && $service->package_pricing->cancel_fee > 0) {
            // Get the client settings
            $client_settings = $this->SettingsCollection->fetchClientSettings($service->client_id);

            // Get the pricing info
            if ($client_settings['default_currency'] != $service->package_pricing->currency) {
                $pricing_info = $this->Services->getPricingInfo($service->id, $client_settings['default_currency']);
            } else {
                $pricing_info = $this->Services->getPricingInfo($service->id);
            }

            // Set the formatted cancellation fee and confirmation message
            if ($pricing_info) {
                $cancellation_fee = $this->Currencies->toCurrency(
                    $pricing_info->cancel_fee,
                    $pricing_info->currency,
                    $this->company_id
                );

                $cancel_messages['now'] = Language::_('ClientServices.cancel.confirm_cancel_now', true) . ' '
                    . Language::_('ClientServices.cancel.confirm_cancel_now_fee', true, $cancellation_fee);

                if ($pricing_info->tax) {
                    $cancel_messages['now'] = Language::_('ClientServices.cancel.confirm_cancel_now', true) . ' '
                        . Language::_('ClientServices.cancel.confirm_cancel_now_fee_tax', true, $cancellation_fee);
                }
            }
        }

        foreach ($cancel_messages as $key => $message) {
            $cancel_messages[$key] = $this->setMessage('notice', $message, true);
        }

        $this->set('service', $service);
        $this->set('package', $this->Packages->get($service->package->id));
        $this->set('vars', $vars);
        $this->set('confirm_cancel_messages', $cancel_messages);

        echo $this->view->fetch('client_services_cancel');
        return false;
    }

    /**
     * Change service term
     */
    public function changeTerm()
    {
        $client_can_change_service_term = isset($this->client->settings['client_change_service_term'])
            && $this->client->settings['client_change_service_term'] == 'true';

        // Ensure we have a service with alternate package terms available to change to
        if (!$client_can_change_service_term
            || !($service = $this->Services->get((int)$this->get[0]))
            || $service->client_id != $this->client->id
            || $service->status != 'active'
            || !empty($service->date_canceled)
            || ($service->package_pricing->period == 'onetime')
            || !($package = $this->Packages->get($service->package->id))
            || !($terms = $this->getPackageTerms($package, [], $service))
            || empty($terms)) {
            $this->redirect($this->base_uri);
        }

        // Changes may not be made to the service while a pending change currently exists
        $queued_changes = $this->pendingServiceChanges($service->id);
        if (!empty($queued_changes) && $this->queueServiceChanges()) {
            $this->redirect($this->base_uri . 'services/manage/' . $service->id . '/');
        }

        // Remove any change term session information
        $this->Session->clear('client_update_service');

        // Determine whether invoices for this service remain unpaid
        $this->uses(['Invoices', 'ModuleManager']);
        $unpaid_invoices = $this->Invoices->getAllWithService($service->id, $this->client->id, 'open');

        // Remove current term
        $current_term = (isset($terms[$service->package_pricing->id]) ? $terms[$service->package_pricing->id] : '');
        unset($terms[$service->package_pricing->id]);

        // Update the term
        if (!empty($this->post)) {
            // Disallow term change if the current service has not been paid
            if (!empty($unpaid_invoices)) {
                $this->flashMessage('error', Language::_('ClientServices.!error.invoices_change_term', true));
                $this->redirect($this->base_uri . 'services/manage/' . $service->id . '/');
            }

            // Redirect if no valid term pricing ID was given
            if (empty($this->post['pricing_id']) || !array_key_exists($this->post['pricing_id'], $terms)) {
                $this->redirect($this->base_uri . 'services/changeterm/' . $service->id);
            }

            // Remove override prices
            $vars = $this->post;
            $vars['override_price'] = null;
            $vars['override_currency'] = null;

            // Continue to the review step
            $data = ['service_id' => $service->id, 'vars' => $vars, 'type' => 'service_term'];
            $this->Session->write('client_update_service', $data);
            $this->redirect($this->base_uri . 'services/review/' . $service->id);
        }

        $this->set('package', $package);
        $this->set('service', $service);
        $this->set('terms', ['' => Language::_('AppController.select.please', true)] + $terms);
        $this->set('current_term', $current_term);
        $this->set('unpaid_invoices', $unpaid_invoices);

        // Set sidebar tabs
        $module = $this->ModuleManager->initModule($service->package->module_id);
        $module->base_uri = $this->base_uri;
        $this->buildTabs($service, $package, $module, 'changeterm');
    }

    /**
     * Upgrades or downgrades a service by changing the package
     */
    public function upgrade()
    {
        // Set whether the client can upgrade the service to another package in the same group
        $client_change_service_package = isset($this->client->settings['client_change_service_package'])
            && $this->client->settings['client_change_service_package'] == 'true';
        $service = null;
        $upgradable_packages = [];

        // Fetch the service and any upgradable packages
        if ($client_change_service_package && isset($this->get[0])) {
            $service = $this->Services->get((int)$this->get[0]);
        }
        if ($client_change_service_package && $service) {
            $upgradable_packages = $this->getUpgradablePackages(
                $service->package,
                ($service->parent_service_id ? 'addon' : 'standard')
            );
        }

        // Ensure we have a valid service with packages that can be upgraded
        if (!$client_change_service_package
            || !$service
            || $service->client_id != $this->client->id
            || $service->status != 'active'
            || !empty($service->date_canceled)
            || empty($upgradable_packages)
        ) {
            $this->redirect($this->base_uri);
        }

        // Changes may not be made to the service while a pending change currently exists
        $queued_changes = $this->pendingServiceChanges($service->id);
        if (!empty($queued_changes) && $this->queueServiceChanges()) {
            $this->redirect($this->base_uri . 'services/manage/' . $service->id . '/');
        }

        // Remove any upgrade package session information
        $this->Session->clear('client_update_service');

        // Determine whether invoices for this service remain unpaid
        $this->uses(['Invoices', 'ModuleManager']);
        $unpaid_invoices = $this->Invoices->getAllWithService($service->id, $this->client->id, 'open');

        // Build the list of upgradable package terms
        $terms = [];
        foreach ($upgradable_packages as $pack) {
            $group_terms = $this->getPackageTerms($pack, [], $service, false, true, true);
            if (!empty($group_terms)) {
                $terms['package_' . $pack->id] = ['name' => $pack->name, 'value' => 'optgroup'];
                $terms += $group_terms;
            }
        }

        if (!empty($this->post)) {
            // Disallow upgrade if the current service has not been paid
            if (!empty($unpaid_invoices)) {
                $this->flashMessage('error', Language::_('ClientServices.!error.invoices_upgrade_package', true));
                $this->redirect($this->base_uri . 'services/manage/' . $service->id . '/');
            }

            // Redirect if no valid term pricing ID was given
            if (empty($this->post['pricing_id']) || !array_key_exists($this->post['pricing_id'], $terms)) {
                $this->redirect($this->base_uri . 'services/upgrade/' . $service->id);
            }

            // Set client limit error if it has been reached
            if (($package = $this->Packages->getByPricingId($this->post['pricing_id']))
                && $package->client_qty !== null
            ) {
                $service_count = $this->Services->getListCount(
                    $this->client->id,
                    'all',
                    true,
                    $package->id
                );

                if ($package->client_qty <= $service_count) {
                    $this->flashMessage('error', Language::_('ClientServices.!notice.client_limit', true));
                    $this->redirect($this->base_uri . 'services/upgrade/' . $service->id);
                }
            }

            // Remove override prices
            $vars = $this->post;
            $vars['override_price'] = null;
            $vars['override_currency'] = null;

            // Continue to the review step
            $data = ['service_id' => $service->id, 'vars' => $vars, 'type' => 'service_package'];
            $this->Session->write('client_update_service', $data);
            $this->redirect($this->base_uri . 'services/review/' . $service->id);
        }

        // Set the current package and term
        $singular_periods = $this->Packages->getPricingPeriods();
        $plural_periods = $this->Packages->getPricingPeriods(true);
        $amount = isset($service->package_pricing->price_renews)
            ? $service->package_pricing->price_renews
            : $service->package_pricing->price;
        $currency = $service->package_pricing->currency;
        if (!empty($service->override_price) && !empty($service->override_currency)) {
            $amount = $service->override_price;
            $currency = $service->override_currency;
        }
        $current_term = Language::_(
            'ClientServices.upgrade.current_package',
            true,
            $service->package->name,
            $service->package_pricing->term,
            $service->package_pricing->term != 1
            ? $plural_periods[$service->package_pricing->period]
            : $singular_periods[$service->package_pricing->period],
            $this->CurrencyFormat->format($amount, $currency)
        );
        if ($service->package_pricing->period == 'onetime') {
            $current_term = Language::_(
                'ClientServices.upgrade.current_package_onetime',
                true,
                $service->package->name,
                $service->package_pricing->term != 1
                ? $plural_periods[$service->package_pricing->period]
                : $singular_periods[$service->package_pricing->period],
                $this->CurrencyFormat->format($amount, $currency)
            );
        }

        $this->set('service', $service);
        $this->set('terms', ['' => Language::_('AppController.select.please', true)] + $terms);
        $this->set('current_term', $current_term);
        $this->set('unpaid_invoices', $unpaid_invoices);

        // Set sidebar tabs
        $package = $this->Packages->get($service->package->id);
        $module = $this->ModuleManager->initModule($service->package->module_id);
        $module->base_uri = $this->base_uri;
        $this->buildTabs($service, $package, $module, 'changeterm');
    }

    /**
     * List Addons
     */
    public function addons()
    {
        // Determine whether a service is given and may have addons
        if (!isset($this->get[0])
            || !($service = $this->Services->get((int)$this->get[0]))
            || $service->client_id != $this->client->id
        ) {
            $this->redirect($this->base_uri);
        }

        // Fetch addons
        $addon_services = $this->Services->getAllChildren($service->id);
        $available_addons = $this->getAddonPackages($service->package_group_id);

        // Must have addons available to view this page
        if (empty($addon_services) && empty($available_addons)) {
            $this->redirect($this->base_uri . 'services/manage/' . $service->id . '/');
        }

        // Fetch the package and module
        $this->uses(['ModuleManager']);
        $package = $this->Packages->get($service->package->id);
        $module = $this->ModuleManager->initModule($service->package->module_id);
        $module->base_uri = $this->base_uri;

        // Set sidebar tabs
        $this->buildTabs($service, $package, $module, 'addons');

        // Set language for periods
        $periods = $this->Packages->getPricingPeriods();
        foreach ($this->Packages->getPricingPeriods(true) as $period => $lang) {
            $periods[$period . '_plural'] = $lang;
        }
        $this->set('periods', $periods);
        $this->set('statuses', $this->Services->getStatusTypes());
        $this->set('services', $addon_services);
        $this->set('service', $service);
        $this->set('package', $this->Packages->get($service->package->id));
        $this->set(
            'client_can_create_addons',
            (
                !empty($available_addons)
                && (
                    isset($this->client->settings['client_create_addons'])
                    && $this->client->settings['client_create_addons'] == 'true'
                )
            )
        );
    }

    /**
     * Create an addon
     */
    public function addAddon()
    {
        // Ensure a valid service was given
        $client_can_create_addon = (isset($this->client->settings['client_create_addons'])
            && $this->client->settings['client_create_addons'] == 'true');
        if (!$client_can_create_addon
            || !isset($this->get[0])
            || !($service = $this->Services->get((int)$this->get[0]))
            || $service->client_id != $this->client->id
            || !($available_addons = $this->getAddonPackages($service->package_group_id))
        ) {
            $this->redirect($this->base_uri);
        }

        // Fetch the package and module
        $this->uses(['Invoices', 'ModuleManager', 'PackageOptionConditionSets', 'PackageOptions']);
        $package = $this->Packages->get($service->package->id);
        $module = $this->ModuleManager->initModule($service->package->module_id);
        $module->base_uri = $this->base_uri;

        $package_group_id = (isset($this->get['package_group_id']) ? $this->get['package_group_id'] : '');
        $addon_pricing_id = (isset($this->get['pricing_id']) ? $this->get['pricing_id'] : '');

        // Detect module refresh fields
        $refresh_fields = isset($this->post['refresh_fields']) && $this->post['refresh_fields'] == 'true';

        // Go to configure service options
        if (!empty($package_group_id) && !empty($addon_pricing_id)) {
            // Determine whether the addon is valid
            $fields = $this->validateAddon($service->id, $package_group_id, $addon_pricing_id);
            if (!$fields['valid']) {
                $this->setMessage('error', Language::_('ClientServices.!error.addon_invalid', true));
            } elseif ($fields['package']->client_qty !== null) {
                $service_count = $this->Services->getListCount(
                    $this->client->id,
                    'all',
                    true,
                    $fields['package']->id
                );

                if ($fields['package']->client_qty <= $service_count) {
                    $this->set('limit_reached', true);
                    $this->setMessage('error', Language::_('ClientServices.!notice.client_limit', true));
                }
            } elseif (!$refresh_fields && !empty($this->post)) {
                $data = $this->post;

                // Set pricing term selected
                $data['package_id'] = $fields['package']->id;
                $data['pricing_id'] = $fields['pricing']->id;
                $data['parent_service_id'] = $fields['parent_service']->id;
                $data['package_group_id'] = $fields['package_group']->id;
                $data['client_id'] = $this->client->id;
                $data['status'] = 'pending';
                $data['use_module'] = 'true';

                // Unset any fields that may adversely affect the Services::add() call
                unset(
                    $data['override_price'],
                    $data['override_currency'],
                    $data['date_added'],
                    $data['date_renews'],
                    $data['date_last_renewed'],
                    $data['date_suspended'],
                    $data['date_canceled'],
                    $data['notify_order'],
                    $data['invoice_id'],
                    $data['invoice_method'],
                    $data['coupon_id']
                );

                // Attempt to add the addon
                if (isset($data['qty'])) {
                    $data['qty'] = (int)$data['qty'];
                }


                // Validate that the submitted config options are valid given the Option Logic
                $option_logic = new OptionLogic();
                $option_logic->setPackageOptionConditionSets(
                    $this->PackageOptionConditionSets->getAll(
                        [
                            'package_id' => $fields['package']->id,
                            'opition_ids' => $this->Form->collapseObjectArray(
                                $this->PackageOptions->getAllByPackageId(
                                    $fields['pricing']->package_id,
                                    $fields['pricing']->term,
                                    $fields['pricing']->period,
                                    $fields['pricing']->currency
                                ),
                                'id',
                                'id'
                            )
                        ],
                        ['option_id']
                    )
                );

                if (!($errors = $option_logic->validate((isset($data['configoptions']) ? $data['configoptions'] : [])))) {
                    // Verify addon looks correct in order to proceed
                    $this->Services->validateService($fields['package'], $data);

                    $errors = $this->Services->errors();
                }

                // Verify fields look correct in order to proceed
                if (!empty($errors)) {
                    $this->setMessage('error', $errors);
                } else {
                    // Add addon
                    $service_id = $this->Services->add($data, ['package_id' => $fields['package']->id]);

                    if (($errors = $this->Services->errors())) {
                        $this->setMessage('error', $errors);
                    } else {
                        // Create the invoice
                        $invoice_id = $this->Invoices->createFromServices(
                            $this->client->id,
                            [$service_id],
                            $fields['currency'],
                            date('c')
                        );

                        // Redirect the client to pay the invoice
                        if ($invoice_id) {
                            $this->flashMessage(
                                'message',
                                Language::_('ClientServices.!success.addon_service_created', true)
                            );
                            $this->redirect($this->base_uri . 'pay/method/' . $invoice_id . '/');
                        }
                    }
                }
            }

            // Set the configurable options partial for the selected addon
            $data = array_merge($this->post, ['addon' => $package_group_id . '_' . $addon_pricing_id]);
            if (!empty($fields['pricing']) && ($addon_options = $this->getAddonOptions($fields, true, $data))) {
                $this->set('addon_options', $addon_options);
                $vars = (object)$data;
            } else {
                $vars = (object)['addon' => ''];
            }
        }

        // Set sidebar tabs
        $this->buildTabs($service, $package, $module, 'addons');

        $this->set('module', $module->getModule());
        $this->set('package', $package);
        $this->set('service', $service);
        $this->set('addons', $this->getAddonPackageList($available_addons));
        $this->set('vars', (isset($vars) ? $vars : new stdClass()));
    }

    /**
     * AJAX - Retrieves the configurable options for a given addon
     *
     * @param array $fields A list of addon fields (optional)
     * @param bool $return True to return this partial view, false to output as json (optional, default false)
     * @param array $vars A list of input vars (optional)
     * @return mixed False if addon fields are invalid; a partial view if $return is true; otherwise null
     */
    public function getAddonOptions($fields = [], $return = false, $vars = [])
    {
        if (!$this->isAjax() && !$return) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $this->uses(['PackageOptionConditionSets']);

        // Validate the addon
        if (!empty($this->get['package_group_id']) && !empty($this->get['pricing_id']) && !empty($this->get[0])) {
            $this->uses(['PackageOptions']);

            // Determine whether the addon is valid
            $fields = $this->validateAddon(
                (int)$this->get[0],
                (int)$this->get['package_group_id'],
                (int)$this->get['pricing_id']
            );
            if (!$fields['valid']) {
                return false;
            }
        }

        // Build the partial
        if (!empty($fields) && $fields['valid'] && $fields['module']) {
            // Set the module service fields and package option fields
            $vars = (object)$vars;
            // Set the 'new' option to 1 to indicate these config options are for a new package being added
            // Set the 'new' option to 0 to indicate these config options are for changes to form fields
            // (e.g. unchecking a checkbox that we do not want to display a default option for)
            $options = [
                'new' => (isset($vars->configoptions) ? 0 : 1),
                'addable' => 1
            ];

            $service_fields = $fields['module']->getClientAddFields($fields['package'], $vars);

            // If the client can select the module group, show a dropdown with the available options
            $package = $this->Packages->get($fields['pricing']->package_id ?? null);
            $module = $this->ModuleManager->initModule($package->module_id, $this->company_id);
            if ($package->module_group_client == '1') {
                $module_groups = $this->Form->collapseObjectArray($package->module_groups, 'name', 'id');
                $module_group_id = $service_fields->label(
                    $module->moduleGroupName() ?? Language::_('ClientServices.getaddonoptions.field_module_group_id', true),
                    'module_group_id'
                );
                $module_group_id->attach(
                    $service_fields->fieldSelect(
                        'module_group_id',
                        $module_groups,
                        $this->post['module_group_id'] ?? $vars->module_group_id ?? null,
                        ['id' => 'module_group_id']
                    )
                );
                $service_fields->setField($module_group_id);
            }

            // Set package options
            $package_options = $this->PackageOptions->getFields(
                $fields['pricing']->package_id,
                $fields['pricing']->term,
                $fields['pricing']->period,
                $fields['pricing']->currency,
                $vars,
                null,
                $options
            );

            $fields_html = new FieldsHtml($service_fields);
            $option_logic = new OptionLogic();
            $option_logic->setPackageOptionConditionSets(
                $this->PackageOptionConditionSets->getAll(
                    [
                        'package_id' => $fields['pricing']->package_id,
                        'opition_ids' => $this->Form->collapseObjectArray(
                            $this->PackageOptions->getAllByPackageId(
                                $fields['pricing']->package_id,
                                $fields['pricing']->term,
                                $fields['pricing']->period,
                                $fields['pricing']->currency,
                                null,
                                $options
                            ),
                            'id',
                            'id'
                        )
                    ],
                    ['option_id']
                )
            );
            $option_logic->setOptionContainerSelector($fields_html->getContainerSelector());

            $vars = [
                'module' => $fields['module']->getModule(),
                'input_html' => $fields_html,
                'package_options' => $this->partial(
                    'client_services_package_options',
                    [
                        'input_html' => (new FieldsHtml($package_options)),
                        'option_logic_js' => $option_logic->getJavascript()
                    ]
                )
            ];

            $partial = $this->partial('client_services_configure_addon', $vars);
            if ($return) {
                return $partial;
            }
            $this->outputAsJson($partial);
        }

        return false;
    }

    /**
     * Validates that the given data is valid for a client
     * @see ClientServices::addAddon(), ClientServices::getAddonOptions()
     *
     * @param int $service_id The ID of the parent service to which the addon is to be assigned
     * @param int $package_group_id The ID of the package group
     * @param int $price_id The ID of the addon's package pricing
     * @return array An array of fields including:
     *
     *  - valid True if the addon is valid; false otherwise
     *  - parent_service An stdClass object representing the parent service of the
     *      addon (optional, only if valid is true)
     *  - package An stdClass object representing the addon package (optional, only if valid is true)
     *  - pricing An stdClass object representing the package pricing term (optional, only if valid is true)
     *  - package_group An stdClass object representing the addon package group (optional, only if valid is true)
     *  - module An stdClass object representing the module (optional, only if valid is true)
     *  - currency The currency code
     */
    private function validateAddon($service_id, $package_group_id, $price_id)
    {
        $this->uses(['ModuleManager', 'PackageGroups']);

        // Ensure a valid addon was given
        if (!($parent_service = $this->Services->get((int)$service_id))
            || $parent_service->client_id != $this->client->id
            || !($available_addons = $this->getAddonPackages($parent_service->package_group_id, true))
            || !($package = $this->Packages->getByPricingId((int)$price_id))
            || !($package_group = $this->PackageGroups->get((int)$package_group_id))
            || $package_group->company_id != $this->company_id) {
            return ['valid' => false];
        }

        // Confirm that the given package is an available addon
        $valid = false;
        $addon_groups = (isset($available_addons[$package_group->id]) ? $available_addons[$package_group->id] : []);
        $currency = $this->Clients->getSetting($this->client->id, 'default_currency');
        $currency = $currency->value;
        $pricing = null;
        foreach ($addon_groups->addons as $addon) {
            if ($addon->id == $package->id) {
                $valid = true;

                // Fetch the pricing
                $pricing = $this->getPricing($package->pricing, (int)$price_id);
                $currency = ($pricing ? $pricing->currency : $currency);
                break;
            }
        }

        // Ensure a valid module exists
        $module = $this->ModuleManager->initModule($package->module_id, $this->company_id);
        if (!$module) {
            $valid = false;
        }

        $module->base_uri = $this->base_uri;

        return compact('valid', 'parent_service', 'package', 'pricing', 'package_group', 'module', 'currency');
    }

    /**
     * Service Info
     */
    public function serviceInfo()
    {
        $this->uses(['ModuleManager']);

        // Ensure we have a service
        if (!($service = $this->Services->get((int)$this->get[0])) || $service->client_id != $this->client->id) {
            $this->redirect($this->base_uri);
        }
        $this->set('service', $service);

        $package = $this->Packages->get($service->package->id);
        $module = $this->ModuleManager->initModule($service->package->module_id);

        if ($module) {
            $module->base_uri = $this->base_uri;
            $module->setModuleRow($module->getModuleRow($service->module_row_id));
            $this->set('content', $module->getClientServiceInfo($service, $package));
        }

        // Set any addon services
        $services = $this->Services->getAllChildren($service->id);
        // Set the expected service renewal price
        foreach ($services as $service) {
            $service->renewal_price = $this->Services->getRenewalPrice($service->id);
        }
        $this->set('services', $services);

        // Set language for periods
        $periods = $this->Packages->getPricingPeriods();
        foreach ($this->Packages->getPricingPeriods(true) as $period => $lang) {
            $periods[$period . '_plural'] = $lang;
        }
        $this->set('periods', $periods);
        $this->set('statuses', $this->Services->getStatusTypes());

        echo $this->outputAsJson($this->view->fetch('client_services_serviceinfo'));
        return false;
    }

    /**
     * Retrieves a list of name/value pairs for addon packages
     *
     * @param array $addons A list of package groups containing addon packages available to the client
     * @return array A list of name/value pairs
     */
    private function getAddonPackageList(array $addons)
    {
        // Set language for periods
        $periods = $this->Packages->getPricingPeriods();
        foreach ($this->Packages->getPricingPeriods(true) as $period => $lang) {
            $periods[$period . '_plural'] = $lang;
        }

        $addon_packages = ['' => Language::_('AppController.select.please', true)];
        foreach ($addons as $package_group) {
            foreach ($package_group->addons as $addon) {
                // Add addon package name
                $addon_packages[] = ['name' => $addon->name, 'value' => 'optgroup'];

                foreach ($addon->pricing as $price) {
                    // Set term
                    $period_singular = (isset($periods[$price->period]) ? $periods[$price->period] : '');
                    $period_plural = (isset($periods[$price->period . '_plural'])
                        ? $periods[$price->period . '_plural']
                        : ''
                    );

                    if ((isset($price->period) ? $price->period : null) == 'onetime') {
                        $term = $period_singular;
                    } else {
                        $term = (isset($price->term) ? $price->term : '');
                        $term = Language::_(
                            'ClientServices.addaddon.term',
                            true,
                            $term,
                            ($term == 1 ? $period_singular : $period_plural)
                        );
                    }

                    // Set price, setup fee
                    $cost = $this->CurrencyFormat->format(
                        (isset($price->price) ? $price->price : null),
                        (isset($price->currency) ? $price->currency : null),
                        ['code' => false]
                    );
                    $renew_cost = $this->CurrencyFormat->format(
                        (isset($price->price_renews) ? $price->price_renews : 0),
                        (isset($price->currency) ? $price->currency : null),
                        ['code' => false]
                    );
                    $option_term = 'ClientServices.addaddon.term_price';
                    $display_renewal_price = ((isset($price->price_renews) ? $price->price_renews : null)
                        && (isset($price->price_renews) ? $price->price_renews : null) != (isset($price->price) ? $price->price : null)
                    );
                    $name = Language::_(
                        $option_term . ($display_renewal_price ? '_recurring' : ''),
                        true,
                        $term,
                        $cost,
                        $renew_cost
                    );

                    if ($price->setup_fee > 0) {
                        $setup_fee = $this->CurrencyFormat->format(
                            $price->setup_fee,
                            $price->currency,
                            ['code' => false]
                        );
                        $name = Language::_(
                            $option_term . '_setupfee' . ($display_renewal_price ? '_recurring' : ''),
                            true,
                            $term,
                            $cost,
                            $setup_fee,
                            $renew_cost
                        );
                    }

                    // Add addon package
                    $addon_packages[] = ['name' => $name, 'value' => $package_group->id . '_' . $price->id];
                }
            }
        }
        return $addon_packages;
    }

    /**
     * Retrieves a list of all addon packages available to the client in the given package group
     *
     * @param int $parent_group_id The ID of the parent group to list packages for
     * @return array An array of addon package groups containing an array of addon packages
     */
    private function getAddonPackages($parent_group_id)
    {
        $this->uses(['Packages']);

        $packages = [];
        $package_groups = $this->Packages->getAllAddonGroups($parent_group_id);

        foreach ($package_groups as $package_group) {
            $temp_packages = $this->Packages->getAllPackagesByGroup($package_group->id);
            $restricted_packages = $this->Clients->getRestrictedPackages($this->client->id);

            foreach ($temp_packages as $package) {
                // Check whether the client has access to this package
                if ($package->status == 'inactive') {
                    continue;
                } elseif ($package->status == 'restricted') {
                    $available = false;
                    foreach ($restricted_packages as $restricted) {
                        if ($restricted->package_id == $package->id) {
                            $available = true;
                            break;
                        }
                    }

                    if (!$available) {
                        continue;
                    }
                }

                // Add the addon package to the list
                if (!isset($packages[$package_group->id])) {
                    $packages[$package_group->id] = $package_group;
                    $packages[$package_group->id]->addons = [];
                }
                $packages[$package_group->id]->addons[] = $package;
            }
        }

        return $packages;
    }

    /**
     * Builds and sets the sidebar tabs for the service management views
     *
     * @param stdClass $service An stdClass object representing the service
     * @param stdClass $package An stdClass object representing the package used by the service
     * @param Module $module An instance of the module used by the service
     * @param string|null $method The method being called (i.e. the tab action, optional)
     * @param int|null $plugin_id The ID of the plugin being called (optional)
     */
    private function buildTabs(stdClass $service, stdClass $package, $module, $method = null, $plugin_id = null)
    {
        // Service information tab
        $tabs = [
            [
                'name' => Language::_('ClientServices.manage.tab_service_info', true),
                'attributes' => [
                    'href' => $this->base_uri . 'services/manage/' . $service->id . '/',
                    'class' => 'ajax'
                ],
                'current' => ($plugin_id === null && $method === null),
                'icon' => 'fas fa-info-circle'
            ]
        ];

        // Determine whether addons are accessible
        $has_addons = $this->Services->hasChildren($service->id);
        if (!$has_addons) {
            $available_addons = $this->getAddonPackages($service->package_group_id);
            $has_addons = !empty($available_addons);
        }

        if ($has_addons) {
            $tabs[] = [
                'name' => Language::_('ClientServices.manage.tab_addons', true),
                'attributes' => [
                    'href' => $this->base_uri . 'services/addons/' . $service->id . '/',
                    'class' => 'ajax'
                ],
                'current' => ($plugin_id === null && $method == 'addons'),
                'icon' => 'fas fa-plus-circle'
            ];
        }

        // Get tabs
        $tabs = array_merge($tabs, $this->formatExtensionTabs($service, $package, $module, $method, $plugin_id));

        // Return to dashboard
        $tabs[] = [
            'name' => Language::_('ClientServices.manage.tab_service_return', true),
            'attributes' => ['href' => $this->base_uri],
            'current' => false,
            'icon' => 'fas fa-arrow-left'
        ];

        $this->set('tabs', $this->partial('client_services_tabs', ['tabs' => $tabs]));
    }


    /**
     * Format module tabs into an array of tabs
     *
     * @param stdClass $service An stdClass object representing the service
     * @param stdClass $package An stdClass object representing the package used by the service
     * @param Module $module An instance of the module used by the service
     * @param string|null $method The method being called (i.e. the tab action, optional)
     * @param int|null $plugin_id The ID of the plugin being called (optional)
     */
    private function formatExtensionTabs(
        stdClass $service,
        stdClass $package,
        $module,
        $method = null,
        $plugin_id = null
    ) {
        $extension_tabs = [];

        // Set the module tabs
        $module_tabs = $module->getClientServiceTabs($service);
        foreach ($module_tabs as $action => $link) {
            if (!is_array($link)) {
                $link = ['name' => $link];
            }

            if (!isset($link['href'])) {
                $link['href'] = $this->base_uri . 'services/manage/' . $service->id . '/' . $action . '/';
                $link['class'] = 'ajax';
            }

            $extension_tabs[] = [
                'name' => $link['name'],
                'attributes' => ['href' => $link['href'], 'class' => isset($link['class']) ? $link['class'] : ''],
                'current' => ($plugin_id === null && strtolower($action) == strtolower($method ?? '')),
                'icon' => (!isset($link['icon']) ? 'fas fa-cog' : $link['icon'])
            ];
        }

        // Set the plugin tabs
        foreach ($package->plugins as $plug) {
            // Skip the plugin if it is not available
            if (!($plugin = $this->getPlugin($plug->plugin_id))) {
                continue;
            }

            foreach ($plugin->getClientServiceTabs($service) as $action => $tab) {
                $attributes = [
                    'href' => (!empty($tab['href'])
                        ? $tab['href']
                        : $this->base_uri . 'services/manage/' . $service->id . '/' . $plug->plugin_id
                            . '/' . $action . '/'
                    ),
                    'class' => 'ajax'
                ];

                $extension_tabs[] = [
                    'name' => $tab['name'],
                    'attributes' => $attributes,
                    'current' => ($plug->plugin_id == $plugin_id && strtolower($action) === strtolower($method ?? '')),
                    'icon' => (!isset($tab['icon']) ? 'fas fa-cog' : $tab['icon'])
                ];
            }
        }

        return $extension_tabs;
    }

    /**
     * Retrieves an instance of the given plugin if it is enabled
     *
     * @param int $plugin_id The ID of the plugin
     * @return Plugin|null An instance of the plugin
     */
    private function getPlugin($plugin_id)
    {
        $this->uses(['PluginManager']);
        $this->components(['Plugins']);

        if (($plugin = $this->PluginManager->get($plugin_id)) && $plugin->enabled == '1') {
            try {
                return $this->Plugins->create($plugin->dir);
            } catch (Throwable $e) {
                // Do nothing
            }
        }

        return null;
    }

    /**
     * AJAX Fetch all package options for the given pricing ID and service ID
     */
    public function packageOptions()
    {
        $this->uses(['Services', 'Packages', 'PackageOptions']);

        // Ensure we have a valid pricing ID and service ID
        if (!isset($this->get[0])
            || !isset($this->get[1])
            || !($service = $this->Services->get((int)$this->get[0]))
            || $service->client_id != $this->client->id
            || !($package = $this->Packages->getByPricingId((int)$this->get[1]))
        ) {
            if ($this->isAjax()) {
                header($this->server_protocol . ' 401 Unauthorized');
                exit();
            }
            $this->redirect($this->base_uri);
        }

        // Determine the selected pricing
        $pricing = $this->getPricing($package->pricing, (int)$this->get[1]);

        // Format the service options into configoptions
        $options = $this->PackageOptions->formatServiceOptions($service->options);
        $options['service_id'] = $service->id;

        // Fetch only editable package options that are already set
        $add_fields = false;
        $edit_fields = false;
        if ($pricing) {
            $filters = array_merge(
                [
                    'new' => 0,
                    'editable' => 1,
                    'allow' => (isset($options['configoptions']) ? array_keys($options['configoptions']) : []),
                    'upgrade' => $service->package->id != $package->id
                ],
                $options
            );
            $edit_fields = $this->getPackageOptionFields(
                $pricing->package_id,
                $pricing->term,
                $pricing->period,
                $pricing->currency,
                (object)$options,
                null,
                $filters
            );

            // Fetch only addable package options that are not already set
            $filters = array_merge(
                [
                    'new' => 1,
                    'addable' => 1,
                    'disallow' => (isset($options['configoptions']) ? array_keys($options['configoptions']) : []),
                    'upgrade' => $service->package->id != $package->id
                ],
                $options
            );
            $add_fields = $this->getPackageOptionFields(
                $pricing->package_id,
                $pricing->term,
                $pricing->period,
                $pricing->currency,
                (object)$options,
                null,
                $filters
            );
        }

        $output = ['html' => '', 'limit_reached' => false];
        if ($package->client_qty !== null) {
            $service_count = $this->Services->getListCount(
                $this->client->id,
                'all',
                true,
                $package->id
            );

            if ($package->client_qty <= $service_count) {
                $output['limit_reached'] = true;
                $output['html'] .= $this->setMessage(
                    'error',
                    Language::_('ClientServices.!notice.client_limit', true),
                    true
                );
            }
        }

        // Set the partial to include the fields into
        $output['html'] .= $this->partial(
            'client_services_manage_package_options',
            ['add_fields' => $add_fields, 'edit_fields' => $edit_fields]
        );

        echo $this->outputAsJson($output);
        return false;
    }

    /**
     * Builds a partial template for the package options
     *
     * @param int $package_id The ID of the package whose package options to fetch
     * @param int $term The package option pricing term
     * @param string $period The package option pricing period
     * @param string $currency The ISO 4217 currency code for this pricing
     * @param stdClass $vars An stdClass object containing input fields
     * @param string $convert_currency The ISO 4217 currency code to convert the pricing to
     * @param array $options An array of filtering options (optional):
     *
     *  - addable Set to 1 to only include options that are addable by clients; 0 to only include
     *      options that are NOT addable by clients; otherwise every option is included
     *  - editable Set to 1 to only include options that are editable by clients; 0 to only include
     *      options that are NOT editable by clients; otherwise every option is included
     *  - allow An array of option IDs to include (i.e. white-list). An empty array would return no
     *      options. Not setting this 'option_ids' key will allow any option
     *  - disallow An array of option IDs not to include (i.e. black-list). An empty array would allow all options.
     *  - configoptions An array of key/value pairs currently in use where
     *      each key is the package option ID and each value is the option value
     *  - new Set to 1 if this is for a new package, or 0 if this is for an existing package (default 1)
     * @return mixed The partial template, or boolean false if no fields are available
     */
    private function getPackageOptionFields(
        $package_id,
        $term,
        $period,
        $currency,
        $vars,
        $convert_currency = null,
        array $options = null
    ) {
        $this->uses(['PackageOptionConditionSets']);
        // Fetch only editable package options that are already set
        $package_options = $this->PackageOptions->getFields(
            $package_id,
            $term,
            $period,
            $currency,
            $vars,
            $convert_currency,
            $options
        );
        $option_fields = $package_options->getFields();
        $fields_html = new FieldsHtml($package_options);
        $option_logic = new OptionLogic();
        $option_logic->setPackageOptionConditionSets(
            $this->PackageOptionConditionSets->getAll(
                [
                    'package_id' => $package_id,
                    'opition_ids' => $this->Form->collapseObjectArray(
                        $this->PackageOptions->getAllByPackageId(
                            $package_id,
                            $term,
                            $period,
                            $currency,
                            $convert_currency,
                            $options
                        ),
                        'id',
                        'id'
                    )
                ],
                ['option_id']
            )
        );
        $option_logic->setOptionContainerSelector($fields_html->getContainerSelector());
        return (
            !empty($option_fields)
                ? $this->partial(
                    'client_services_package_options',
                    [
                        'fields' => $option_fields,
                        'input_html' => $fields_html,
                        'option_logic_js' => $option_logic->getJavascript()
                    ]
                )
                : false
        );
    }

    /**
     * Builds a list of package options for a service that may be upgraded/downgraded
     */
    public function manageOptions()
    {
        // Determine whether a valid service is given and whether available options exist to be managed
        if (!isset($this->get[0])
            || !($service = $this->Services->get((int)$this->get[0]))
            || $service->client_id != $this->client->id
            || $service->status != 'active'
            || !($available_options = $this->getAvailableOptions($service))
        ) {
            $this->redirect($this->base_uri);
        }

        // Changes may not be made to the service while a pending change currently exists
        $queued_changes = $this->pendingServiceChanges($service->id);
        if (!empty($queued_changes) && $this->queueServiceChanges()) {
            $this->redirect($this->base_uri . 'services/manage/' . $service->id . '/');
        }

        // Remove any config option session information
        $this->Session->clear('client_update_service');

        // Determine whether invoices for this service remain unpaid
        $this->uses(['Invoices', 'ModuleManager', 'PackageOptions']);
        $unpaid_invoices = $this->Invoices->getAllWithService($service->id, $this->client->id, 'open');

        // Save the selected config options for the review step
        if (!empty($this->post)) {
            // Disallow upgrade if the current service has not been paid
            if (!empty($unpaid_invoices)) {
                $this->flashMessage('error', Language::_('ClientServices.!error.invoices_manage_options', true));
                $this->redirect($this->base_uri . 'services/manage/' . $service->id . '/');
            }

            $options = (isset($this->post['configoptions']) ? $this->post['configoptions'] : []);

            $this->Session->write(
                'client_update_service',
                [
                    'service_id' => $service->id,
                    'vars' => ['configoptions' => (array)$options],
                    'type' => 'config_options'
                ]
            );
            $this->redirect($this->base_uri . 'services/review/' . $service->id);
        }

        // Set sidebar tabs
        $package = $this->Packages->get($service->package->id);
        $module = $this->ModuleManager->initModule($service->package->module_id);
        $module->base_uri = $this->base_uri;
        $this->buildTabs($service, $package, $module, null);

        // Format the service options into configoptions
        $options = $this->PackageOptions->formatServiceOptions($service->options);
        $options['service_id'] = $service->id;

        // Fetch only editable package options that are already set
        $filters = array_merge(
            [
                'new' => 0,
                'editable' => 1,
                'allow' => (isset($options['configoptions']) ? array_keys($options['configoptions']) : [])
            ],
            $options
        );
        $edit_fields = $this->getPackageOptionFields(
            $package->id,
            $service->package_pricing->term,
            $service->package_pricing->period,
            $service->package_pricing->currency,
            (object)$options,
            null,
            $filters
        );

        // Fetch only addable package options that are not already set
        $filters = array_merge(
            [
                'new' => 1,
                'addable' => 1,
                'disallow' => (isset($options['configoptions']) ? array_keys($options['configoptions']) : [])
            ],
            $options
        );
        $add_fields = $this->getPackageOptionFields(
            $package->id,
            $service->package_pricing->term,
            $service->package_pricing->period,
            $service->package_pricing->currency,
            (object)$options,
            null,
            $filters
        );

        $this->set('service', $service);
        $this->set('package', $package);
        $this->set('module', $module);
        $this->set('available_options', (!empty($edit_fields) || !empty($add_fields)));
        $this->set('unpaid_invoices', $unpaid_invoices);
        $this->set(
            'package_options',
            $this->partial(
                'client_services_manage_package_options',
                ['add_fields' => $add_fields, 'edit_fields' => $edit_fields, 'show_no_options_message' => true]
            )
        );
    }

    /**
     * Review page for updating package and package options
     */
    public function review()
    {
        // Determine whether a valid service is given
        if (!isset($this->get[0])
            || !($service = $this->Services->get((int)$this->get[0]))
            || $service->client_id != $this->client->id
            || !($data = $this->Session->read('client_update_service'))
        ) {
            $this->redirect($this->base_uri);
        }

        // Redirect if the service doesn't match the session info
        if (!isset($data['service_id']) || $data['service_id'] != $service->id) {
            $this->redirect($this->base_uri . 'services/manage/' . $service->id);
        }

        $this->uses(['Invoices', 'ModuleManager', 'PackageOptions', 'ServiceChanges', 'PackageOptionConditionSets']);

        // Fetch all of the input data
        $vars = (isset($data['vars']) ? $data['vars'] : []);
        $selected_options = (isset($vars['configoptions']) ? (array)$vars['configoptions'] : []);

        // Fetch the pricing to use for all settable options
        $pricing_id = (isset($vars['pricing_id']) ? $vars['pricing_id'] : null);
        if ($pricing_id && ($new_package = $this->Packages->getByPricingId($pricing_id))) {
            $pricing = $this->getPricing($new_package->pricing, $pricing_id);

            // Set error about client limit if it has been reached
            if ($new_package->id != $service->package->id
                && $new_package->client_qty !== null
            ) {
                $service_count = $this->Services->getListCount(
                    $this->client->id,
                    'all',
                    true,
                    $new_package->id
                );

                if ($new_package->client_qty <= $service_count) {
                    $this->flashMessage('error', Language::_('ClientServices.!notice.client_limit', true));
                    $this->redirect($this->base_uri . 'services/manage/' . $service->id);
                }
            }
        }

        // Fetch options that the client can set
        $new_package_id = (isset($new_package) && $new_package ? $new_package->id : $service->package->id);
        $pricing = (isset($pricing) ? $pricing : $service->package_pricing);
        $settable_options = $this->getSettableOptions(
            $new_package_id,
            $pricing->term,
            $pricing->period,
            $pricing->currency,
            $service->options,
            $selected_options
        );
        $options = [];
        $settable_option_ids = [];
        foreach ($settable_options as $option) {
            $settable_option_ids[$option->id] = $option->id;
            if (array_key_exists($option->id, $selected_options)) {
                $options[$option->id] = $selected_options[$option->id];
            }
        }

        // Fetch existing options that are not currently being modified to maintain
        // between the current service and new package
        $matching_options = $this->getCurrentMatchingOptions(
            $new_package_id,
            $pricing->term,
            $pricing->period,
            $pricing->currency,
            $service->options,
            $settable_option_ids
        );
        $options += $matching_options;

        // Include any price overrides, if given
        $override_fields = ['override_price', 'override_currency'];
        $overrides = [];
        foreach ($override_fields as $override) {
            // Remove any price overrides by making them null if the term/package has changed
            if ($pricing->id !== $service->pricing_id) {
                $overrides[$override] = null;
            } elseif (isset($service->{$override})) {
                // Maintain the service's existing price override
                $overrides[$override] = $service->{$override};
            }
        }

        // Include the current service module fields (to pass any module error checking)
        $vars = ['qty' => $service->qty];
        foreach ($service->fields as $field) {
            $vars[$field->key] = $field->value;
        }
        $vars = array_merge(
            $vars,
            $overrides,
            ['pricing_id' => $pricing->id, 'configoptions' => $options, 'use_module' => 'true']
        );

        // Determine the items/totals
        $serviceChange = $this->ServiceChanges->getPresenter($service->id, $vars);
        $total = $serviceChange->totals()->total;

        if (!empty($this->post)) {
            // Determine whether credits are allowed
            $errors = false;
            $invoice_id = '';
            $queue_service = $this->queueServiceChanges();
            $allow_credit = $this->SettingsCollection->fetchClientSetting(
                $this->client->id,
                $this->Clients,
                'client_prorate_credits'
            );
            $allow_credit = (isset($allow_credit['value']) && $allow_credit['value'] == 'true');

            // Validate that the submitted config options are valid given the Option Logic
            $option_logic = new OptionLogic();
            $option_logic->setPackageOptionConditionSets(
                $this->PackageOptionConditionSets->getAll(
                    [
                        'package_id' => $pricing->package_id,
                        'opition_ids' => $this->Form->collapseObjectArray(
                            $this->PackageOptions->getAllByPackageId(
                                $pricing->package_id,
                                $pricing->term,
                                $pricing->period,
                                $pricing->currency,
                                null,
                                $selected_options
                            ),
                            'id',
                            'id'
                        )
                    ],
                    ['option_id']
                )
            );

            if (!($errors = $option_logic->validate($options))) {
                $this->Services->validateServiceEdit($service->id, $vars);
                $errors = $this->Services->errors();
            }

            // Create the invoice for the service change
            if (empty($errors) && $total > 0) {
                $invoice_data = $this->createInvoice($serviceChange, $pricing->currency, $service->id);
                $invoice_id = $invoice_data['invoice_id'];
                $errors = $invoice_data['errors'];
            }

            if (empty($errors)) {
                // Perform the service change if we didn't already update the service
                if ($queue_service && $total > 0) {
                    $result = $this->queueServiceChange($service->id, $invoice_id, $vars);
                    $errors = $result['errors'];
                } else {
                    $this->Services->edit($service->id, $vars);
                    $errors = $this->Services->errors();
                }
            }

            // Issue a credit for the service change
            if (empty($errors) && $total < 0 && $allow_credit) {
                $transaction_id = $this->createCredit(abs($total), $pricing->currency);
            }

            // Set error/success message
            if (empty($errors)) {
                // Clear the service change session info
                $this->Session->clear('client_update_service');

                // Determine the success message to show
                $action_type = (isset($data['type']) ? $data['type'] : '');
                $message = ($queue_service
                    ? 'ClientServices.!success.service_queue'
                    : 'ClientServices.!success.' . $action_type . '_updated'
                );
                $redirect_uri = $this->base_uri . 'services/manage/' . $service->id . '/';

                // Redirect to pay the invoice if we have one
                if (($invoice = $this->Invoices->get($invoice_id)) && $invoice->due > 0) {
                    $message = ($queue_service
                        ? 'ClientServices.!success.service_queue_pay'
                        : $message
                    );
                    $redirect_uri = $this->base_uri . 'pay/method/' . $invoice_id . '/';
                }

                $this->flashMessage('message', Language::_($message, true));
                $this->redirect($redirect_uri);
            } else {
                // Set the error
                $this->setMessage('error', $errors);
            }
        }

        // Set sidebar tabs
        $package = $this->Packages->get($service->package->id);
        $module = $this->ModuleManager->initModule($service->package->module_id);
        $module->base_uri = $this->base_uri;
        $this->buildTabs($service, $package, $module, null);

        // Set language for periods
        $periods = $this->Packages->getPricingPeriods();
        foreach ($this->Packages->getPricingPeriods(true) as $period => $lang) {
            $periods[$period . '_plural'] = $lang;
        }

        // Determine what the new service totals would be once upgraded
        $recurServiceData = null;
        if ($pricing->period != 'onetime') {
            $presenter_options = [
                'includeSetupFees' => false,
                'recur' => true,
                'upgrade' => false,
                'config_options' => (isset($vars['configoptions']) ? $vars['configoptions'] : [])
            ];
            $recurServiceData = $this->Services->getDataPresenter($this->client->id, $vars, $presenter_options);
        }

        $this->set('periods', $periods);
        $this->set('service', $service);
        $this->set('package', $package);
        $this->set('module', $module);
        $this->set('review', $this->formatServiceReview($service, $options, $pricing_id));
        $this->set(
            'totals',
            $this->totals($serviceChange, $pricing->currency, ($recurServiceData ? $recurServiceData : null))
        );
    }

    /**
     * AJAX updates totals for input data changed for a service
     */
    public function updateTotals()
    {
        if (!$this->isAjax()) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        // Only allow the pricing ID and config options to be provided
        $vars = array_intersect_key($this->post, array_flip(['pricing_id', 'configoptions', 'parent_service_id']));
        $vars['qty'] = 1;

        // Determine the currency to be used
        $pricing_id = (isset($vars['pricing_id']) ? $vars['pricing_id'] : null);
        if ($pricing_id && ($package = $this->Packages->getByPricingId((int)$pricing_id))) {
            $pricing = $this->getPricing($package->pricing, (int)$pricing_id);
            $currency = $pricing->currency;
        }

        // Default to client's currency
        if (empty($currency)) {
            $currency = $this->Clients->getSetting($this->client->id, 'default_currency');
            $currency = $currency->value;
        }

        // Set options for the builder to use to construct the presenter
        $now = date('c');
        $options = [
            'includeSetupFees' => true,
            // Line items show they are billed from this date
            'startDate' => $now,
            'prorateStartDate' => $now,
            'recur' => false,
            'upgrade' => false
        ];

        // Determine the items/totals
        $serviceData = $this->Services->getDataPresenter($this->client->id, $vars, $options);

        // Only set the totals if we have a presenter to set them with
        if ($serviceData) {
            // Fetch the presenter for this service recurring
            $recurServiceData = null;
            if ($pricing->period != 'onetime') {
                $options = [
                    'includeSetupFees' => false,
                    'recur' => true,
                    'upgrade' => false,
                    'config_options' => (isset($vars['configoptions']) ? $vars['configoptions'] : [])
                ];
                $recurServiceData = $this->Services->getDataPresenter($this->client->id, $vars, $options);
            }

            echo $this->outputAsJson(
                $this->totals($serviceData, $currency, ($recurServiceData ? $recurServiceData : null))
            );
        }
        return false;
    }

    /**
     * Builds and returns the totals partial
     *
     * @param PresenterInterface $presenter An instance of the PresenterInterface
     * @param string $currency The ISO 4217 currency code
     * @param PresenterInterface $recurPresenter An instance of the PresenterInterface representing a renewing service
     * @return string The totals partial template
     */
    private function totals(PresenterInterface $presenter, $currency, PresenterInterface $recurPresenter = null)
    {
        $pricingFactory = $this->getFromContainer('pricing');
        $arrayMerge = $pricingFactory->arrayMerge();

        return $this->partial(
            'client_services_totals',
            [
                'totals' => $presenter->totals(),
                'totals_recurring' => ($recurPresenter ? $recurPresenter->totals() : null),
                'discounts' => $arrayMerge->combineSum($presenter->discounts(), 'id', 'total'),
                'taxes' => $arrayMerge->combineSum($presenter->taxes(), 'id', 'total'),
                'currency' => $currency,
                'settings' => $this->client->settings
            ]
        );
    }

    /**
     * Creates an invoice from the given line items
     *
     * @param PresenterInterface $presenter An instance of the PresenterInterface
     * @param string $currency The ISO-4217 currency code
     * @param int $service_id The ID of the service the items are for (optional)
     * @return array An key/value array containing:
     *
     *  - invoice_id The ID of the invoice, if created
     *  - errors An array of errors if the invoice could not be created
     */
    private function createInvoice(PresenterInterface $presenter, $currency, $service_id = null)
    {
        // Invoice and queue the service change
        $invoice_vars = [
            'client_id' => $this->client->id,
            'date_billed' => date('c'),
            'date_due' => date('c'),
            'currency' => $currency,
            'lines' => $this->makeLineItems($presenter, $service_id)
        ];

        // Create the invoice
        $invoice_id = $this->Invoices->add($invoice_vars);

        return [
            'invoice_id' => $invoice_id,
            'errors' => $this->Invoices->errors()
        ];
    }

    /**
     * Creates a set of line items from the given presenter
     * @see ClientServices::createInvoice
     *
     * @param PresenterInterface $presenter An instance of the PresenterInterface
     * @param int $service_id The ID of the service the items are for (optional)
     * @return array An array of line items
     */
    private function makeLineItems(PresenterInterface $presenter, $service_id = null)
    {
        $items = [];

        // Setup line items from each of the presenter's items
        foreach ($presenter->items() as $item) {
            // Tax has to be deconstructed since the presenter's tax amounts
            // cannot be passed along
            $items[] = [
                'qty' => $item->qty,
                'amount' => $item->price,
                'description' => $item->description,
                'tax' => !empty($item->taxes),
                'service_id' => ($service_id ? $service_id : null)
            ];
        }

        // Add a line item for each discount amount
        foreach ($presenter->discounts() as $discount) {
            // The total discount is the negated total
            $items[] = [
                'qty' => 1,
                'amount' => (-1 * $discount->total),
                'description' => $discount->description,
                'tax' => false,
                'service_id' => ($service_id ? $service_id : null)
            ];
        }

        return $items;
    }

    /**
     * Creates an in house credit for the client
     *
     * @param float $amount The amount to credit
     * @param string $currency The ISO 4217 currency code for the credit
     * @return int $transaction_id The ID of the transaction for this credit
     */
    private function createCredit($amount, $currency)
    {
        $this->uses(['Transactions']);

        // Apply the credit to the client account
        $vars = [
            'client_id' => $this->client->id,
            'amount' => $amount,
            'currency' => $currency,
            'type' => 'other'
        ];

        // Find and set the transaction type to In House Credit, if available
        $transaction_types = $this->Transactions->getTypes();
        foreach ($transaction_types as $type) {
            if ($type->name == 'in_house_credit') {
                $vars['transaction_type_id'] = $type->id;
                break;
            }
        }

        return $this->Transactions->add($vars);
    }

    /**
     * Queue's a service change for later processing
     *
     * @param int $service_id The ID of the service being queued
     * @param int $invoice_id The ID of the invoice related to the service change
     * @param array $vars An array of all data to queue to successfully update a service
     * @return array An array of queue info, including:
     *
     *  - service_change_id The ID of the service change, if created
     *  - errors An array of errors
     */
    private function queueServiceChange($service_id, $invoice_id, array $vars)
    {
        // Create a new service change
        unset($vars['prorate']);
        $change_vars = ['data' => $vars];
        $change_id = $this->ServiceChanges->add($service_id, $invoice_id, $change_vars);

        return [
            'service_change_id' => $change_id,
            'errors' => $this->ServiceChanges->errors()
        ];
    }

    /**
     * Retrieves a list of service options that can be set by the client for the given package and term information
     *
     * @param int $package_id The ID of the package whose options to use
     * @param int $term The pricing term
     * @param string $period The pricing period
     * @param string $currency The ISO 4217 pricing currency code
     * @param array $current_options An array of current service options
     * @param array $selected_options A key/value list of option IDs and their selected values
     * @return array An array of stdClass objects representing each package option field that can be set by the client
     */
    private function getSettableOptions(
        $package_id,
        $term,
        $period,
        $currency,
        array $current_options,
        array $selected_options = []
    ) {
        $this->uses(['PackageOptions']);

        // Fetch all available and current options
        $options = $this->PackageOptions->getAllByPackageId(
            $package_id,
            $term,
            $period,
            $currency,
            null,
            $this->PackageOptions->formatServiceOptions($current_options)
        );

        // Re-key each current option by ID
        $edit_options = [];
        foreach ($current_options as $current_option) {
            $edit_options[$current_option->option_id] = $current_option;
        }
        unset($current_options, $current_option);

        // Determine all package options that can be set by the client
        $available_options = [];

        foreach ($options as $option) {
            // Set editable options
            if (array_key_exists($option->id, $edit_options) && $option->editable == '1') {
                $available_options[] = $option;
            }

            // Set addable options
            if (!array_key_exists($option->id, $edit_options) && $option->addable == '1'
                && array_key_exists($option->id, $selected_options)) {
                $available_options[] = $option;
            }
        }

        return $available_options;
    }

    /**
     * Retrieves a list of current options that match those available from the given package and term information
     *
     * @param int $package_id The ID of the package whose options to use
     * @param int $term The pricing term
     * @param string $period The pricing period
     * @param string $currency The ISO 4217 pricing currency code
     * @param array $current_options An array of current service options
     * @param array $settable_options An array of options that could be selected for modification
     * @return array A key/value array where the key is the option ID and the value is the selected option value
     */
    private function getCurrentMatchingOptions(
        $package_id,
        $term,
        $period,
        $currency,
        array $current_options,
        array $settable_options = []
    ) {
        $this->uses(['PackageOptions']);

        // Fetch all available and package options
        $options = $this->PackageOptions->getAllByPackageId(
            $package_id,
            $term,
            $period,
            $currency,
            null,
            $this->PackageOptions->formatServiceOptions($current_options)
        );

        // Re-key each option and option value
        $package_options = [];
        foreach ($options as $option) {
            $package_options[$option->id] = [];
            foreach ($option->values as $value) {
                $package_options[$option->id][$value->id] = $value->value;
            }
        }
        unset($options, $option);

        // Re-key each current option by ID
        $edit_options = [];
        foreach ($current_options as $current_option) {
            $edit_options[$current_option->option_id] = $current_option;
        }
        unset($current_options, $current_option);

        // Check whether each current option is an available package option
        $available_options = [];
        foreach ($edit_options as $option_id => $option) {
            // Existing option is an available package option that is not already being modified.
            if (array_key_exists($option_id, $package_options) && !array_key_exists($option_id, $settable_options)) {
                $available_options[$option_id] = ($option->option_type == 'quantity' ? $option->qty : $option->value);
            }
        }

        return $available_options;
    }

    /**
     * Formats package/term and package options into separate sections representing their current and new values/pricing
     *
     * @param stdClass $service An stdClass object representing the service
     * @param array $option_values A key/value array of the new service option IDs and their values
     * @param int $pricing_id The new pricing ID (optional)
     * @return array A formatted array of all options
     */
    private function formatServiceReview($service, array $option_values = [], $pricing_id = null)
    {
        $formatted_package = (object)['current' => null, 'new' => null];

        // Fetch the current package and its pricing info
        $package = $this->Packages->get($service->package->id);
        $package->pricing = $this->getPricing($package->pricing, $service->pricing_id);
        $formatted_package->current = $package;

        // Fetch the new package and its pricing info
        if ($pricing_id) {
            $package = $this->Packages->getByPricingId($pricing_id);
            $package->pricing = $this->getPricing($package->pricing, $pricing_id);
            $formatted_package->new = $package;
        }

        // Fetch the formatted config options
        $formatted_options = $this->formatReviewOptions($service, $option_values, $pricing_id);

        return (object)[
            'packages' => $formatted_package,
            'config_options' => $formatted_options
        ];
    }

    /**
     * Formats package options and their values into categories for current and new
     * @see ClientServices::formatServiceReview
     *
     * @param stdClass $service An stdClass object representing the service
     * @param array $option_values A key/value array of the new service option IDs and their values
     * @param int $pricing_id The new pricing ID (optional)
     * @return array A formatted array of all options
     */
    private function formatReviewOptions($service, array $option_values = [], $pricing_id = null)
    {
        if (!isset($this->PackageOptions)) {
            $this->uses(['PackageOptions']);
        }

        $formatted_options = [];

        // Fetch the current package options
        $current_values = $this->PackageOptions->formatServiceOptions($service->options);
        $current_values = (array_key_exists('configoptions', $current_values) ? $current_values['configoptions'] : []);

        // Fetch all of the possible options
        $all_options = $this->PackageOptions->getByPackageId($service->package->id);

        $pricing = null;
        // Fetch all possible options for the new pricing
        if ($pricing_id && ($new_package = $this->Packages->getByPricingId($pricing_id))) {
            $new_options = $this->PackageOptions->getByPackageId($new_package->id);

            // Key all options by ID
            $option_ids = [];
            foreach ($all_options as $option) {
                $option_ids[$option->id] = null;
            }

            // Combine all options with the new package options
            foreach ($new_options as $option) {
                if (!array_key_exists($option->id, $option_ids)) {
                    $all_options[] = $option;
                }
            }
            unset($new_options, $option);

            // Set the new package pricing information
            $pricing = $this->getPricing($new_package->pricing, $pricing_id);
        }

        // Set the new pricing as the current service pricing if not set
        $pricing = ($pricing ? $pricing : $service->package_pricing);

        // Match the available package options with the given options
        $i = 0;
        foreach ($all_options as $package_option) {
            if (array_key_exists($package_option->id, $option_values)
                || array_key_exists($package_option->id, $current_values)) {
                $formatted_options[$i] = $package_option;
                $formatted_options[$i]->new_value = false;
                $formatted_options[$i]->current_value = false;

                // Fetch the new option value
                if (array_key_exists($package_option->id, $option_values)) {
                    $formatted_options[$i]->new_value = $this->PackageOptions->getValue(
                        $package_option->id,
                        $option_values[$package_option->id]
                    );

                    if ($formatted_options[$i]->new_value) {
                        // Set the selected value
                        $formatted_options[$i]->new_value->selected_value = $option_values[$package_option->id];

                        // Set pricing
                        $formatted_options[$i]->new_value->pricing = $this->PackageOptions->getValuePrice(
                            $formatted_options[$i]->new_value->id,
                            $pricing->term,
                            $pricing->period,
                            $pricing->currency
                        );
                    }
                }

                // Fetch the current option value
                if (array_key_exists($package_option->id, $current_values)) {
                    $formatted_options[$i]->current_value = $this->PackageOptions->getValue(
                        $package_option->id,
                        $current_values[$package_option->id]
                    );

                    if ($formatted_options[$i]->current_value) {
                        // Set the selected value
                        $formatted_options[$i]->current_value->selected_value = $current_values[$package_option->id];

                        // Set pricing
                        $formatted_options[$i]->current_value->pricing = $this->PackageOptions->getValuePrice(
                            $formatted_options[$i]->current_value->id,
                            $service->package_pricing->term,
                            $service->package_pricing->period,
                            $service->package_pricing->currency
                        );
                    }
                }

                $i++;
            }
        }

        // Remove any options/values that should not be shown
        foreach ($formatted_options as $i => &$option) {
            // If there is no new or current value, remove the option entirely
            if (!$option->new_value && !$option->current_value) {
                unset($formatted_options[$i]);
                continue;
            }

            // Remove any quantity options that are not being set, or remove the value
            //  if being changed to a quantity of 0
            if ($option->type == 'quantity') {
                // If no current value exists, and the new value is a quantity of 0, remove the option entirely
                if (!$option->current_value && $option->new_value && $option->new_value->selected_value == '0') {
                    unset($formatted_options[$i]);
                } elseif ($option->current_value && $option->new_value && $option->new_value->selected_value == '0') {
                    // If a current value exists, and the new value is a quantity of 0, only remove the new value
                    $option->new_value = false;
                }
            }
        }

        return array_values($formatted_options);
    }

    /**
     * Retrieves a list of pending service changes for the given service
     *
     * @param int $service_id The ID of the service
     * @return array An array of all pending service changes for the given service
     */
    private function pendingServiceChanges($service_id)
    {
        if (!isset($this->ServiceChanges)) {
            $this->uses(['ServiceChanges']);
        }

        return $this->ServiceChanges->getAll('pending', $service_id);
    }

    /**
     * Determines whether queuing service changes is enabled
     *
     * @return bool True if queuing service changes is enabled, or false otherwise
     */
    private function queueServiceChanges()
    {
        // Determine whether to queue the service change or process it immediately
        $queue = $this->SettingsCollection->fetchClientSetting(
            $this->client->id,
            $this->Clients,
            'process_paid_service_changes'
        );
        $queue = (isset($queue['value']) ? $queue['value'] : null);

        return ($queue == 'true');
    }

    /**
     * Retrieves the matching pricing information from the given pricings
     *
     * @param array $pricings An array of stdClass objects representing each pricing
     * @param int $pricing_id The ID of the pricing to retrieve from the list
     * @return mixed An stdClass object representing the pricing information, or null if not found
     */
    private function getPricing(array $pricings, $pricing_id)
    {
        $pricing = null;
        foreach ($pricings as $price) {
            if ($price->id == $pricing_id) {
                $pricing = $price;
                break;
            }
        }

        return $pricing;
    }
}
