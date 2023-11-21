<?php
/**
 * Order Form Parent Controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderFormController extends OrderController
{
    /**
     * @var stdClass The order form
     */
    protected $order_form;
    /**
     * @var object The order type object for the selected order form
     */
    protected $order_type;
    /**
     * @var stdClass A stdClass object representing the client, null if not logged in
     */
    protected $client;
    /**
     * @var string The string to prefix all custom client field IDs with
     */
    protected $custom_field_prefix = 'custom_field';
    /**
     * @var string The cart name used for this order form
     */
    protected $cart_name;

    /**
     * Setup
     */
    public function preAction()
    {
        parent::preAction();

        $this->uses(['Order.OrderSettings', 'Order.OrderForms', 'Companies',
            'Clients', 'Currencies', 'PackageGroups', 'Packages', 'PluginManager', 'Services'
        ]);

        // Redirect if this plugin is not installed for this company
        if (!$this->PluginManager->isInstalled('order', $this->company_id)) {
            $this->redirect($this->client_uri);
        }

        if ($this->isLoggedIn()) {
            $area = $this->plugin ? $this->plugin . '.*' : $this->controller;
            $this->requirePermission($area);
        }

        if ($this->Session->read('blesta_client_id')) {
            $this->client = $this->Clients->get($this->Session->read('blesta_client_id'));
        }

        $default_form = $this->OrderSettings->getSetting($this->company_id, 'default_form');

        $order_label = null;
        if ($default_form) {
            $this->order_form = $this->OrderForms->get(
                $default_form->value,
                ['client_id' => ($this->client ? $this->client->id : null)]
            );
            if ($this->order_form) {
                $order_label = $this->order_form->label;
            }
        }

        // Ensure that label always appears as a URI element
        if (isset($this->get[0])) {
            $order_label = $this->get[0];
            $this->order_form = null;
        } elseif ($order_label) {
            $this->redirect($this->base_uri . 'order/main/index/' . $order_label);
        }

        if (!$this->order_form) {
            $this->order_form = $this->OrderForms->getByLabel(
                $this->company_id,
                urldecode($order_label ?? ''),
                ['client_id' => ($this->client ? $this->client->id : null)]
            );
        }

        // If the order form doesn't exist, is inactive, or restricted to clients
        // and not logged in as client show form listing
        if (!$this->order_form
            || $this->order_form->status != 'active'
            || ($this->order_form->visibility === 'client' && !$this->client)
        ) {
            $this->redirect($this->base_uri . 'order/forms/');
        }

        // Ready the session cart for this order form
        $this->cart_name = $this->company_id . '-' . $order_label;
        $this->components(['SessionCart' => [$this->cart_name, $this->Session]]);

        // If the order form requires SSL redirect to HTTPS
        if ($this->order_form->require_ssl  && !(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')) {
            $this->redirect(
                str_replace('http://', 'https://', $this->base_url)
                . ltrim($_SERVER['REQUEST_URI'] ?? '', '/')
            );
        }

        // Get default template
        Configure::load('order', PLUGINDIR . 'order' . DS . 'config' . DS);
        $default_template = Configure::get('Order.order_forms.default_template');

        // Auto load language for the template
        Language::loadLang('order_form_controller', null, PLUGINDIR . DS . 'order' . DS . 'language' . DS);
        Language::loadLang('cart', null, PLUGINDIR . DS . 'order' . DS . 'language' . DS);

        $template_lang_files = [Loader::fromCamelCase(get_class($this)), $this->order_form->type, 'main', 'summary'];
        $templates_dir = PLUGINDIR . 'order' . DS . 'views' . DS . 'templates' . DS;

        foreach ($template_lang_files as $lang_file) {
            $template_lang_dir = $templates_dir . $this->order_form->template . DS . 'language' . DS;
            $language = $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'language')->value;

            if (!file_exists($template_lang_dir . $language . DS . $lang_file . '.php')) {
                $template_lang_dir = $templates_dir . $default_template . DS . 'language' . DS;
            }
            Language::loadLang($lang_file, null, $template_lang_dir);
        }

        $this->view->setView(null, 'templates' . DS . $this->order_form->template);
        if (($structure_dir = $this->getViewDir(null, true)) && substr($structure_dir, 0, 6) == 'client') {
            $this->structure->setDefaultView(APPDIR);
        }
        $this->structure->setView(null, $structure_dir);

        $this->structure->set('outer_class', 'order');
        $this->structure->set(
            'custom_head',
            '<link href="'
            . Router::makeURI(
                str_replace('index.php/', '', WEBDIR) . $this->view->view_path . 'views/' . $this->view->view
            )
            . '/css/order.css" rel="stylesheet" type="text/css" />'
        );

        $this->view->setView(null, $this->getViewDir());

        $this->base_uri = WEBDIR;
        $this->view->base_uri = $this->base_uri;
        $this->structure->base_uri = $this->base_uri;

        $this->structure->set('page_title', $this->order_form->name);
        $this->structure->set('title', $this->order_form->name);

        // Load the order type
        $this->order_type = $this->loadOrderType($this->order_form->type);

        // Set the client info
        $this->view->set('client', $this->client);
        $this->structure->set('client', $this->client);

        // Set the order form in the view and structure
        $this->view->set('order_form', $this->order_form);
        $this->structure->set('order_form', $this->order_form);

        $this->view->set('is_ajax', $this->isAjax());

        // Set the currency to use for this order form
        $this->setCurrency();

        // Set the affiliate to use for this order form
        $this->setAffiliate();

        // Fallback to the default template
        $view_action = (!empty($this->action) && $this->action !== 'index') ? '_' . $this->action : '';
        $view_file = ROOTWEBDIR . $this->view->view_path . 'views' . DS
            . $this->view->view . DS . $this->controller . $view_action . $this->view->view_ext;

        // Check if the required view exists on the current template, if not,
        // fallback to the default template
        if (!file_exists($view_file)) {
            $this->view->setView(null, 'templates' . DS . $default_template);
            $this->view->view = str_replace($this->order_form->template, $default_template, $this->view->view);
            $this->view->view_dir = str_replace($this->order_form->template, $default_template, $this->view->view_dir);
        }
    }

    /**
     * Renders the current view as an AJAX response if this is an AJAX request
     *
     * @return bool True to render the layout false otherwise
     */
    protected function renderView()
    {
        if ($this->isAjax()) {
            $view = $this->controller . (!$this->action || $this->action == 'index' ? '' : '_' . $this->action);
            $this->outputAsJson($this->view->fetch($view));
            return false;
        }

        return true;
    }

    /**
     * Set all pricing periods
     */
    protected function getPricingPeriods()
    {
        // Set language for periods
        $periods = $this->Packages->getPricingPeriods();
        foreach ($this->Packages->getPricingPeriods(true) as $period => $lang) {
            $periods[$period . '_plural'] = $lang;
        }
        return $periods;
    }

    /**
     * Load the order type required for this order form
     *
     * @param string $order_type The Order type for this order form
     * @return object An OrderType object
     */
    protected function loadOrderType($order_type)
    {
        Loader::load(PLUGINDIR . 'order' . DS . 'lib' . DS . 'order_type.php');
        Loader::load(
            PLUGINDIR . 'order' . DS . 'lib' . DS . 'order_types' . DS
            . $order_type . DS . 'order_type_' . $order_type . '.php'
        );
        $class_name = Loader::toCamelCase('order_type_' . $order_type);

        $order_type = new $class_name();
        $order_type->setOrderForm($this->order_form);
        $order_type->setCart($this->SessionCart);
        $order_type->base_uri = $this->base_uri;

        return $order_type;
    }

    /**
     * Sets the ISO 4217 currency code to use for the order form
     */
    protected function setCurrency()
    {
        // If user attempts to change currency, verify it can be set
        // Currency can only be changed if cart is empty
        if (isset($this->get['currency']) && $this->SessionCart->isEmptyCart()) {
            foreach ($this->order_form->currencies as $currency) {
                if ($currency->currency == $this->get['currency']) {
                    $this->SessionCart->setData('currency', $currency->currency);
                    break;
                }
            }
        } elseif (!isset($this->get['currency']) && $this->SessionCart->isEmptyCart()) {
            // If a queued item for the cart exists, verify and set its pricing currency for the order form
            $cart = $this->SessionCart->get();
            if ($this->SessionCart->isEmptyCart() && !$this->SessionCart->isEmptyQueue()
                && !$this->SessionCart->getData('currency')
                && ($queue_item = $this->SessionCart->checkQueue()) && count($cart['queue']) == 1) {
                $pricing_id = $queue_item['pricing_id'];

                // Fetch the package info for the selected pricing ID
                if (($package = $this->Packages->getByPricingId($pricing_id))) {
                    // Find the matching pricing
                    foreach ($package->pricing as $pricing) {
                        // Set the queued pricing currency as the form currency
                        if ($pricing->id == $pricing_id) {
                            foreach ($this->order_form->currencies as $currency) {
                                if ($currency->currency == $pricing->currency) {
                                    $this->SessionCart->setData('currency', $currency->currency);
                                    break;
                                }
                            }
                            break;
                        }
                    }
                }
            }
        }

        // If no currency for this session, default to the company's default currency,
        // or the first available currency for the order form
        if ($this->SessionCart->getData('currency') == null) {
            $temp = $this->Companies->getSetting($this->company_id, 'default_currency');
            if ($temp) {
                $company_currency = $temp->value;
            }

            foreach ($this->order_form->currencies as $currency) {
                if ($currency->currency == $company_currency) {
                    $this->SessionCart->setData('currency', $currency->currency);
                    break;
                }
            }

            if ($this->SessionCart->getData('currency') == null && isset($this->order_form->currencies[0]->currency)) {
                $this->SessionCart->setData('currency', $this->order_form->currencies[0]->currency);
            }
        }
    }

    /**
     * Sets the affiliate code to use for the order form
     */
    protected function setAffiliate()
    {
        if (isset($this->get['a'])) {
            $this->setAffiliateCode($this->get['a']);
        }
    }

    /**
     * Returns the computed order summary as an array
     *
     * @param array $temp_items An array of items to appear as if they exist in the cart
     * @param int $item_index The index of cart items that $temp_items replaces (if any)
     * @return array An array of cart summary details
     */
    protected function getSummary(array $temp_items = null, $item_index = null)
    {
        if (!isset($this->ModuleManager)) {
            $this->uses(['ModuleManager']);
        }

        if (!isset($this->OrderOrders)) {
            $this->uses(['Order.OrderOrders']);
        }

        $data = [];

        $client_id = null;
        $country = null;
        $state = null;

        if ($this->client) {
            $client_id = $this->client->id;
        } else {
            $user = $this->SessionCart->getItem('user');
            $country = isset($user['country']) ? $user['country'] : null;
            $state = isset($user['state']) ? $user['state'] : null;
        }

        $data['cart'] = $this->SessionCart->get();
        if ($temp_items != null) {
            if ($item_index !== null) {
                unset($data['cart']['items'][$item_index]);
            }
            $data['cart']['items'] = array_merge($data['cart']['items'], $temp_items);
        }

        // Get order form meta
        if (!empty($this->order_form->meta)) {
            $this->order_form->meta = $this->Form->collapseObjectArray(
                $this->order_form->meta,
                'value',
                'key'
            );
        }

        $domains = [];
        $services = [];
        foreach ($data['cart']['items'] as $index => &$item) {
            $package = $this->Packages->getByPricingId($item['pricing_id']);

            // Get service name
            $service_name = $this->ModuleManager->moduleRpc(
                $package->module_id,
                'getPackageServiceName',
                [$package, $item]
            );
            $item['service_name'] = $service_name;
            $item['package_group_id'] = $item['group_id'];
            $item['index'] = $index;
            $item['package_options'] = $this->getPackageOptions($package, $item);

            // Set pricing
            $package = $this->updatePackagePricing($package, $this->SessionCart->getData('currency'));

            $item += ['package' => $package];

            // Check if the item is a domain
            if ($item['group_id'] == ($this->order_form->meta['domain_group'] ?? 0)) {
                $domains[$item['domain'] ?? $index] = $index;
            } else {
                $services[$item['domain'] ?? $index] = $index;
            }
        }

        // Process free domains
        foreach ($domains as $domain => $index) {
            if (!array_key_exists($domain, $services)) {
                continue;
            }

            // Set the domain as free
            if ($this->OrderOrders->eligibleFreeDomain(
                (array) $data['cart']['items'][$services[$domain]],
                (array) $data['cart']['items'][$domains[$domain]],
                $this->order_form
            )) {
                $data['cart']['items'][$index]['override_price'] = 0;
                $data['cart']['items'][$index]['override_currency'] = $data['cart']['currency'];
            }
        }

        // Set cart items as transfer, if applicable
        $cart_items = [];
        if (!empty($data['cart']['queue'])) {
            foreach ($data['cart']['queue'] as $queued_item) {
                $cart_items[$queued_item['pricing_id']] = $queued_item;
            }

            unset($queued_item);
        } else if (!empty($data['cart']['items'])) {
            $current_cart = $this->SessionCart->get();
            foreach ($current_cart['items'] as $cart_item) {
                $cart_items[$cart_item['pricing_id']] = $cart_item;
            }

            unset($cart_item);
        }

        if (!empty($cart_items) && !empty($data['cart']['items'])) {
            foreach ($data['cart']['items'] as &$cart_item) {
                if (isset($cart_items[$cart_item['pricing_id']]['transfer'])) {
                    $cart_item['transfer'] = $cart_items[$cart_item['pricing_id']]['transfer'];
                }
            }
        }

        // Merge addons into each cart item
        foreach ($data['cart']['items'] as &$item) {
            $addons = $this->getAddons($item, $data['cart']);
            unset($item['addons']);
            if (!empty($addons)) {
                $item['addons'] = [];
                foreach ($addons as $index) {
                    $item['addons'][] = $data['cart']['items'][$index];
                    unset($data['cart']['items'][$index]);
                }
            }
        }
        $data['cart']['items'] = array_values($data['cart']['items']);

        $data['totals'] = $this->getLineTotals($client_id, $data['cart']);

        // Retrieve the recurring totals, but don't update the cart by reference
        $cart = $data['cart'];
        $data['totals_recurring'] = [];

        foreach ($this->getCartItemsByPeriod($cart['items']) as $key => $items) {
            $temp_cart = ['items' => $items['items']];

            $data['totals_recurring'][$key] = [
                'term' => $items['term'],
                'period' => $items['period'],
                'totals' => $this->getLineTotals($client_id, $temp_cart, true)
            ];
        }

        return $data;
    }

    /**
     * Remove invalid items from the cart
     */
    protected function cleanCart($client_id = null)
    {
        $cart = $this->SessionCart->get();
        $package_counts = $this->getClientPackageCounts(false);
        foreach ($cart['items'] as $index => &$item) {
            $package = $this->Packages->getByPricingId($item['pricing_id']);
            $client_id = isset($this->client) ? $this->client->id : $client_id;
            $limit_reached = false;
            if ($client_id && $package->client_qty !== null) {
                $limit_reached = $package->client_qty
                    <= (isset($package_counts[$package->id]) ? $package_counts[$package->id] : 0);

                if (!isset($package_counts[$package->id])) {
                    $package_counts[$package->id] = 0;
                }
                $package_counts[$package->id]++;
            }

            // Remove items that are invalid due to package limits
            if (!$package || $package->qty == '0' || $limit_reached) {
                $item = $this->SessionCart->removeItem($index);
                $this->removeAddons($item);
                $this->setMessage(
                    'notice',
                    Language::_('OrderFormController.!notice.items_removed', true),
                    false,
                    null,
                    false
                );
                $this->flashMessage(
                    'notice',
                    Language::_('OrderFormController.!notice.items_removed', true),
                    null,
                    false
                );
            }
        }
    }

    /**
     * Retrieves a set of cart items grouped by their term/period
     *
     * @param array $items A list of cart items
     * @return array An array of items grouped by their term/period, including:
     *  - term The term
     *  - period The period
     *  - items The array of items in that term/period
     */
    private function getCartItemsByPeriod($items)
    {
        $grouped_items = [];

        foreach ($items as $item) {
            // Group items by their term and period
            $key = '';
            $term = '';
            $period = '';
            foreach ($item['package']->pricing as $price) {
                if (isset($price->id) && $price->id == $item['pricing_id']) {
                    $key = $price->term . $price->period;
                    $term = $price->term;
                    $period = $price->period;
                    break;
                }
            }

            // Skip any invalid price that has no key
            if (empty($key) || $period == 'onetime') {
                continue;
            }

            // Add the item to the list of grouped items
            if (!array_key_exists($key, (array)$grouped_items)) {
                $grouped_items[$key] = ['term' => $term, 'period' => $period, 'items' => []];
            }

            // Fetch the addons for this item and separate them out from the item
            $addons = isset($item['addons']) ? $item['addons'] : [];
            unset($item['addons']);
            $grouped_items[$key]['items'][] = $item;

            // Merge the addon items into the current groups
            if (!empty($addons)) {
                $grouped_addons = $this->getCartItemsByPeriod($addons);
                foreach ($grouped_addons as $type => $addons) {
                    if (isset($grouped_items[$type])) {
                        // Add items of the same term/period
                        $grouped_items[$type]['items'] = array_merge($grouped_items[$type]['items'], $addons['items']);
                    } else {
                        // Merge items of different terms/periods
                        $grouped_items = array_merge($grouped_items, [$type => $addons]);
                    }
                }
            }
        }

        return $grouped_items;
    }

    /**
     * Gets the line totals for an order
     *
     * @param int $client_id The ID of the client making this order
     * @param array $cart The order cart
     * @param bool $recurring Whether or not to fetch recurring line totals or initial
     *  line totals (optional, default false for initial)
     * @return array A list of line totals including
     *  - subtotal: The order total
     *  - total: The order total
     *  - discount: The total amount discounted on this order
     *  - tax: A list of taxes that apply to this order
     */
    private function getLineTotals($client_id, &$cart, $recurring = false)
    {
        // Set options for the builder to use to construct the presenter
        // The service is being added anew (unless $recurring is true), so include setup fees, prorate from now,
        // and denote that this is not a recurring service being added (it's a new service)
        $options = [
            'includeSetupFees' => !$recurring,
            'prorateStartDate' => ($recurring ? null : date('c')),
            'recur' => $recurring,
            'transfer' => false,
            'upgrade' => false
        ];

        // Initialize line totals
        $line_totals = ['total' => 0, 'total_without_exclusive_tax' => 0, 'subtotal' => 0, 'tax' => []];
        $cart['display_items'] = [];

        // Get the currency to convert totals to
        $currency = $this->SessionCart->getData('currency');

        // Store data common to all these services
        $coupon_id = $this->getCouponId($this->SessionCart->getData('coupon'));
        $service = ['coupon_id' => $coupon_id, 'client_id' => $client_id];

        // Anonymous function for formatting service data to be submitted to the pricing presenter
        $format_service_item = function ($item, $type, $options) use (&$cart, &$line_totals, $currency, $service) {
            $service['configoptions'] = $item['configoptions'] ?? [];
            $service['pricing_id'] = $item['pricing_id'];
            $service['name'] = $item['service_name'];
            $service['qty'] = abs(isset($item['qty']) && is_numeric($item['qty']) ? (int)$item['qty'] : 1);

            // Add config options to the list if recurring so that the renewal prices can be used
            if ($options['recur']) {
                $options['config_options'] = $service['configoptions'];
            }

            if (isset($item['transfer'])) {
                $options['transfer'] = true;
            }

            $pricing = $this->Services->getPackagePricing($item['pricing_id']);

            // Set override price and currency
            if (isset($item['override_price']) && isset($item['override_currency'])) {
                $service['override_price'] = $item['override_price'];
                $service['override_currency'] = $item['override_currency'];

                $pricing->price = $item['override_price'];
                $pricing->price_transfer = $item['override_price'];
                $pricing->currency = $item['override_currency'];
            }

            $display_items = $this->Services->getServiceItems(
                $service,
                $options,
                $line_totals,
                $currency,
                $pricing->currency
            );

            $i = 0;
            foreach ($display_items as $display_item) {
                $display_item['index'] = $item['index'];
                $display_item['type'] = ($i++ == 0 ? $type : 'config_fee');

                if (isset($item['transfer'])) {
                    $display_item['transfer'] = $item['transfer'];
                }

                $cart['display_items'][] = $display_item;
            }
        };

        foreach ($cart['items'] as $item) {
            // Update the items in the cart
            $format_service_item($item, 'service', $options);

            if (isset($item['addons'])) {
                // Get the parent service's pricing info
                $pricing = $this->Services->getPackagePricing($item['pricing_id']);

                foreach ($item['addons'] as $addon_item) {
                    // Synchronize this addon with the parent service if set to do so and it is not
                    // already being prorated
                    $addon_pricing = $this->Services->getPackagePricing($addon_item['pricing_id']);
                    if ($pricing
                        && !$recurring
                        && $addon_pricing
                        && ($sync_date = $this->Services->getChildRenewDate(
                            $addon_pricing,
                            $pricing,
                            $this->order_form->client_group_id
                        ))
                    ) {
                        // Set the prorate date to the parent service's renew date
                        $options['prorateEndDate'] = $sync_date;
                    }

                    $format_service_item($addon_item, 'addon', $options);
                    unset($options['prorateEndDate']);
                }
            }
        }

        // Format totals and discount
        $total_keys = ['subtotal', 'total', 'total_without_exclusive_tax', 'discount'];
        foreach ($total_keys as $total_key) {
            if (isset($line_totals[$total_key])) {
                $line_totals[$total_key] = [
                    'amount' => $line_totals[$total_key],
                    'amount_formatted' => $this->CurrencyFormat->format($line_totals[$total_key], $currency)
                ];
            }
        }

        foreach ($line_totals['tax'] as &$tax) {
            // Format each tax total
            $tax = ['amount' => $tax, 'amount_formatted' => $this->CurrencyFormat->format($tax, $currency)];
        }

        return $line_totals;
    }


    /**
     * Checks if the domain being configured is eligible for bundling
     *
     * @param array $item A list of item details
     * @return boolean True if the domain is eligible for bundling, false otherwise
     */
    protected function isItemBundlableDomain($item) {
        $bundlable_domain = false;
        if (!empty($item['domain']) && !empty($this->order_form->meta['tlds'])) {
            $package = $this->Packages->getByPricingId($item['pricing_id'] ?? null);
            if ($package && !empty(array_intersect($package->meta->tlds ?? [], $this->order_form->meta['tlds']))) {
                $bundlable_domain = true;
            }
        }
        return $bundlable_domain;
    }

    /**
     * Checks if the item being configured is eligible for bundling
     *
     * @param array $item A list of item details
     * @return boolean True if the item is eligible for bundling, false otherwise
     */
    protected function isItemBundlablePackage($item) {
        return !empty($this->order_form->meta['package_groups'])
            && in_array($item['group_id'], $this->order_form->meta['package_groups']);
    }

    /**
     * Checks if there is a bundle eligible domain in the cart
     *
     * @return True if the cart contains a bundle eligible domain, false otherwise
     */
    protected function cartHasEligibleDomain()
    {
        $cart = $this->SessionCart->get();
        $bundlable_domain = false;
        foreach ($cart['items'] as $item) {
            if ($this->isItemBundlableDomain($item)) {
                $bundlable_domain = true;
                break;
            }
        }
        return $bundlable_domain;
    }

    /**
     * Fetches the coupon ID for a given coupon code and package ID
     *
     * @param string $coupon_code The coupon code
     * @return mixed The coupon ID if it exists, 0 if it does not exist, or null if no coupon code was given
     */
    private function getCouponId($coupon_code)
    {
        $this->uses(['Coupons']);
        $coupon_id = null;
        $coupon_code = trim($coupon_code ?? '');

        if ($coupon_code !== '') {
            if (($coupon = $this->Coupons->getByCode($coupon_code))) {
                $coupon_id = $coupon->id;
            } else {
                $coupon_id = 0;
            }
        }

        return $coupon_id;
    }

    /**
     * Fetches all package options for the given package. Uses the given item to select and set pricing
     *
     * @param stdClass $package The package to fetch options for
     * @param array $item An array of item info
     * @retrun stdClass A stdClass object representing the package option and its price
     */
    protected function getPackageOptions($package, $item)
    {
        if (!isset($this->PackageOptions)) {
            $this->uses(['PackageOptions']);
        }

        $package_options = $this->PackageOptions->getByPackageId($package->id);
        foreach ($package_options as $i => $option) {
            if (isset($item['configoptions']) && array_key_exists($option->id, (array)$item['configoptions'])) {
                // Exclude quantity items if empty
                if ($option->type == 'quantity' && empty($item['configoptions'][$option->id])) {
                    unset($package_options[$i]);
                    continue;
                }

                foreach ($package->pricing as $pricing) {
                    if ($pricing->id == $item['pricing_id']) {
                        break;
                    }
                }
                $option->price = $this->getOptionPrice($pricing, $option->id, $item['configoptions'][$option->id]);
                $option->selected_value_name = isset($option->values[0]->name) ? $option->values[0]->name : null;

                if (isset($option->values)) {
                    foreach ($option->values as $value) {
                        if ($value->value == $item['configoptions'][$option->id]) {
                            $option->selected_value_name = $value->name;
                            break;
                        }
                    }
                }
            }
        }
        unset($option);

        return $package_options;
    }

    /**
     * Returns the pricing term for the given option ID and value
     *
     * @param stdClass $package_pricing The package pricing
     * @param int $option_id The package option ID
     * @param string $value The package option value
     * @return mixed A stdClass object representing the price if found, false otherwise
     */
    protected function getOptionPrice($package_pricing, $option_id, $value)
    {
        if (!isset($this->PackageOptions)) {
            $this->uses(['PackageOptions']);
        }

        $singular_periods = $this->Packages->getPricingPeriods();
        $plural_periods = $this->Packages->getPricingPeriods(true);

        $value = $this->PackageOptions->getValue($option_id, $value);
        if ($value) {
            return $this->PackageOptions->getValuePrice(
                $value->id,
                $package_pricing->term,
                $package_pricing->period,
                $package_pricing->currency,
                $this->SessionCart->getData('currency')
            );
        }

        return false;
    }

    /**
     * Updates all given packages with pricing for the given currency. Evaluates
     * the company setting to determine if package pricing can be converted based
     * on currency conversion, or whether the package can only be offered in the
     * configured currency. If the package pricing can not be converted automatically
     * it will be removed.
     *
     * @param mixed An array of stdClass objects each representing a package,
     *  or a stdClass object representing a package
     * @param string $currency The ISO 4217 currency code to update to
     * @return array An array of stdClass objects each representing a package
     */
    protected function updatePackagePricing($packages, $currency)
    {
        $default_currency_setting = $this->Companies->getSetting($this->company_id, 'default_currency');
        $default_currency = $default_currency_setting ? $default_currency_setting->value : null;
        $multi_currency_pricing = $this->Companies->getSetting($this->company_id, 'multi_currency_pricing');
        $allow_conversion = true;

        if ($multi_currency_pricing->value == 'package') {
            $allow_conversion = false;
        }

        if (is_object($packages)) {
            $packages = $this->convertPackagePrice($packages, $currency, $allow_conversion, $default_currency);
        } else {
            foreach ($packages as &$package) {
                $package = $this->convertPackagePrice($package, $currency, $allow_conversion, $default_currency);
            }
        }

        return $packages;
    }

    /**
     * Convert pricing for the given package and currency
     *
     * @param stdClass $package A stdClass object representing a package
     * @param string $currency The ISO 4217 currency code to update to
     * @param bool $allow_conversion True to allow conversion, false otherwise
     * @param string $default_currency The default currency from which to convert
     * @return stdClass A stdClass object representing a package
     */
    protected function convertPackagePrice($package, $currency, $allow_conversion, $default_currency = null)
    {
        $all_pricing = [];
        foreach ($package->pricing as $pricing) {
            // Only convert pricings for the default currency
            if ($default_currency && $default_currency != $pricing->currency && $currency != $pricing->currency) {
                continue;
            }

            $converted = false;
            if ($pricing->currency != $currency) {
                $converted = true;
            }

            $pricing = $this->Packages->convertPricing($pricing, $currency, $allow_conversion);
            if ($pricing) {
                if (!$converted) {
                    $all_pricing[$pricing->term . $pricing->period] = $pricing;
                } elseif (!array_key_exists($pricing->term . $pricing->period, (array)$all_pricing)) {
                    $all_pricing[$pricing->term . $pricing->period] = $pricing;
                }
            }
        }

        $package->pricing = array_values($all_pricing);
        return $package;
    }

    /**
     * Removes all addon items for a given item
     *
     * @param array $item An item in the form of:
     *  - pricing_id The ID of the package pricing item to add
     *  - group_id The ID of the package group the item belongs to
     *  - addons An array of addons containing:
     *      - uuid The unique ID for each addon
     */
    protected function removeAddons($item)
    {
        $indexes = $this->getAddons($item);
        $this->SessionCart->removeItems($indexes);
    }

    /**
     * Fetches the cart index for each addon item associated with this item
     *
     * @param array $item An item in the form of:
     *  - pricing_id The ID of the package pricing item to add
     *  - group_id The ID of the package group the item belongs to
     * @param array $cart The cart to use, else will pull from the session
     * @return array An array of cart item indexes where the addon items live
     */
    protected function getAddons($item, array $cart = null)
    {
        if (isset($item['addons'])) {
            $indexes = [];
            if ($cart === null) {
                $cart = $this->SessionCart->get();
            }

            if (!empty($cart['items'])) {
                foreach ($item['addons'] as $uuid) {
                    foreach ($cart['items'] as $index => $cart_item) {
                        if (isset($cart_item['uuid']) && $uuid == $cart_item['uuid']) {
                            $indexes[] = $index;
                            break;
                        }
                    }
                }
            }

            return $indexes;
        }
        return [];
    }

    /**
     * Verifies if the given item is valid for this order form
     *
     * @param array $item An item in the form of:
     *  - pricing_id The ID of the package pricing item to add
     *  - group_id The ID of the package group the item belongs to
     * @return bool True if the item is valid for this order form, false otherwise
     */
    protected function isValidItem($item)
    {
        if (!isset($item['pricing_id'])
            || !isset($item['group_id'])
            || !($item_group = $this->PackageGroups->get($item['group_id']))
        ) {
            return false;
        }

        $currency = $this->SessionCart->getData('currency');
        $multi_currency_pricing = $this->Companies->getSetting($this->company_id, 'multi_currency_pricing');
        $allow_conversion = true;

        if ($multi_currency_pricing->value == 'package') {
            $allow_conversion = false;
        }

        $valid_groups = $this->order_type->getGroupIds();

        foreach ($valid_groups as $group_id) {
            if ($item_group->type == 'addon') {
                foreach ($item_group->parents as $parent_group) {
                    if ($parent_group->id == $group_id) {
                        return true;
                    }
                }
            } elseif ($item_group->id == $group_id) {
                // Fetch all active packages for this group
                $packages = $this->Packages->getAllPackagesByGroup($group_id, 'active', ['hidden' => true]);

                if ($this->client) {
                    // Add restricted packages allowed for this user
                    $client_packages = [];
                    foreach ($this->Clients->getRestrictedPackages($this->client->id) as $package) {
                        $client_packages[] = $package->package_id;
                    }

                    $restricted_packages = $this->Packages->getAllPackagesByGroup(
                        $group_id,
                        'restricted',
                        ['hidden' => true]
                    );
                    foreach ($restricted_packages as $package) {
                        if (in_array($package->id, $client_packages)) {
                            $packages[] = $package;
                        }
                    }
                }

                foreach ($packages as $package) {
                    foreach ($package->pricing as $pricing) {
                        if ($pricing->id == $item['pricing_id']
                            && $this->Packages->convertPricing($pricing, $currency, $allow_conversion)) {
                            return true;
                        }
                    }
                }
                break;
            }
        }

        return false;
    }

    /**
     * Set view directories. Allows order template type views to override order template views.
     * Also allows order templates to use own structure view.
     */
    protected function getViewDir($view = null, $structure = false)
    {
        $base_dir = PLUGINDIR . 'order' . DS . 'views' . DS;

        if ($structure) {
            if (file_exists($base_dir . 'templates' . DS . $this->order_form->template . DS
                . 'types' . DS . $this->order_form->type . DS . $this->structure_view . '.pdt')
            ) {
                return 'templates' . DS . $this->order_form->template . DS . 'types' . DS . $this->order_form->type;
            } elseif (file_exists($base_dir . 'templates' . DS . $this->order_form->template
                . DS . $this->structure_view . '.pdt')
            ) {
                return 'templates' . DS . $this->order_form->template;
            }

            return 'client' . DS . $this->layout;
        } else {
            if ($view == null) {
                $view = $this->view->file;
            }
            // Use the view file set for this view (if set)
            if (!$view) {
                // Auto-load the view file. These have the format of:
                // [controller_name]_[method_name] for all non-index methods
                $view = Loader::fromCamelCase(get_class($this)) .
                    ($this->action != null && $this->action != 'index' ? '_' . strtolower($this->action) : '');
            }

            $template_type = 'templates' . DS . $this->order_form->template . DS
                . 'types' . DS . $this->order_form->type;
            if (file_exists($base_dir . $template_type . DS . $view . '.pdt')) {
                return $template_type;
            }

            return 'templates' . DS . $this->order_form->template;
        }
    }

    /**
     * Returns an array of payment options
     *
     * @param string $currency The currency to fetch payment options for
     * @return array An array containing payment option details:
     *  - nonmerchant_gateways An array of stdClass objects representing nonmerchant_gateways
     *  - merchant_gateway A stdClass object representing the merchant gateway
     *  - payment_types An array of accepted merchant payment types
     *  - currency The currency
     */
    protected function getPaymentOptions($currency = null)
    {
        if ($currency == null) {
            $currency = $this->SessionCart->getData('currency');
        }

        $this->uses(['GatewayManager', 'Transactions']);

        if (isset($this->client->settings)) {
            $settings = $this->client->settings;
        } else {
            $this->components(['SettingsCollection']);
            $settings = $this->SettingsCollection->fetchSettings(null, $this->company_id);
        }

        // Fetch merchant gateway for this currency
        $merchant_gateway = $this->GatewayManager->getInstalledMerchant($this->company_id, $currency, null);

        // Verify $merchant_gateway is enabled for this order form, if not, unset
        // Set all nonmerchant gateways available
        $valid_merchant_gateway = false;
        $nonmerchant_gateways = [];
        foreach ($this->order_form->gateways as $gateway) {
            if ($merchant_gateway && $gateway->gateway_id == $merchant_gateway->id) {
                $valid_merchant_gateway = true;
                continue;
            }

            $gw = $this->GatewayManager->getInstalledNonmerchant(
                $this->company_id,
                null,
                $gateway->gateway_id,
                $currency
            );
            if ($gw) {
                $nonmerchant_gateways[] = $gw;
            }
        }

        if (!$valid_merchant_gateway) {
            $merchant_gateway = null;
        }

        // Set the payment types allowed
        $transaction_types = $this->Transactions->transactionTypeNames();
        $payment_types = [];
        if ($merchant_gateway) {
            if ((in_array('MerchantAch', $merchant_gateway->info['interfaces'])
                || in_array('MerchantAchOffsite', $merchant_gateway->info['interfaces']))
                && (!$settings || $settings['payments_allowed_ach'] == 'true')) {
                $payment_types['ach'] = $transaction_types['ach'];
            }
            if ((in_array('MerchantCc', $merchant_gateway->info['interfaces'])
                || in_array('MerchantCcOffsite', $merchant_gateway->info['interfaces']))
                && (!$settings || $settings['payments_allowed_cc'] == 'true')) {
                $payment_types['cc'] = $transaction_types['cc'];
            }
        }

        return compact('nonmerchant_gateways', 'merchant_gateway', 'payment_types', 'currency');
    }

    /**
     * Handles an error. If AJAX, will output the error as a JSON object with index 'error'.
     * Else will flash the error and redirect
     *
     * @param mixed $error The error message
     * @param redirect The URI to redirect to
     */
    protected function handleError($error, $redirect = null)
    {
        if ($this->isAjax()) {
            $this->outputAsJson([
                'error' => $this->setMessage('error', $error, true, null, false)
            ]);
            exit;
        } elseif ($redirect != null) {
            $this->flashMessage('error', $error, null, false);
            $this->redirect($redirect);
        } else {
            $this->setMessage('error', $error, false, null, false);
        }
    }

    /**
     * Checks whether the client user owns the client account
     *
     * @param stdClass $client The client
     * @param Session $session The user's session
     * @return boolean True if the client user owns the client account, false otherwise
     */
    protected function isClientOwner($client, Session $session)
    {
        if (!$client) {
            return true;
        }
        
        // Get the contact of the current session
        $this->uses(['Contacts']);
        $contact = $this->Contacts->getByUserId($session->read('blesta_id'), $client->id);

        return (
            $this->Contacts->hasPermission(Configure::get('Blesta.company_id'), ($contact->id ?? $client->contact_id), 'order.*')
            || $client->user_id == $session->read('blesta_id')
            || $this->isStaffAsClient()
        );
    }

    /**
     * Performs an anti-fraud check on the given order and client info
     *
     * @param array $order_settings A list of order settings
     * @param stdClass $client A list of client information
     * @return A list of errors on failure, false otherwise
     */
    protected function runAntifraudCheck(array $order_settings, stdClass $client)
    {
        $this->components(['Order.Antifraud']);
        $requestor = $this->getFromContainer('requestor');

        $antifraud = (isset($order_settings['antifraud']) ? $order_settings['antifraud'] : null);
        try {
            $fraud_detect = $this->Antifraud->create($antifraud, [$order_settings]);
            $status = $fraud_detect->verify([
                'ip' => (isset($requestor->ip_address) ? $requestor->ip_address : null),
                'first_name' => (isset($client->first_name) ? $client->first_name : null),
                'last_name' => (isset($client->last_name) ? $client->last_name : null),
                'email' => (isset($client->email) ? $client->email : null),
                'address1' => (isset($client->address1) ? $client->address1 : null),
                'address2' => (isset($client->address2) ? $client->address2 : null),
                'city' => (isset($client->city) ? $client->city : null),
                'state' => (isset($client->state) ? $client->state : null),
                'country' => (isset($client->country) ? $client->country : null),
                'zip' => (isset($client->zip) ? $client->zip : null),
                'phone' => $this->Contacts->intlNumber(
                    (isset($client->numbers[0]['number']) ? $client->numbers[0]['number'] : null),
                    (isset($client->country) ? $client->country : null)
                )
            ]);
        } catch (Exception $e) {
            // Log the antifraud module could not be loaded or performed
            if (isset($this->logger)) {
                // Remove settings from the client, as they may contain sensitive information
                $log_client = (array)$client;
                unset($log_client['settings'], $log_client['new_password'], $log_client['confirm_password']);

                $this->logger->info(
                    'Could not load AntiFraud to check client',
                    ['antifraud' => $antifraud, 'client' => $log_client, 'error' => $e]
                );
            }

            return [];
        }

        $errors = false;
        if (isset($fraud_detect->Input)) {
            $errors = $fraud_detect->Input->errors();
        }

        $this->SessionCart->setData('fraud_report', $fraud_detect->fraudDetails());
        $this->SessionCart->setData('fraud_status', $status);

        if ($status == 'review') {
            $errors = false;
        } // remove errors (if any)

        return $errors;
    }

    /**
     * Gets a list of service counts by package for the current client
     *
     * @param bool $evaluate_cart True to evaluate the items currently in the cart when counting the services a client
     *     has for each product
     * @return array A list of package service counts
     */
    protected function getClientPackageCounts($evaluate_cart = true)
    {
        $this->uses(['Packages', 'Services']);
        if (!isset($this->client)) {
            return [];
        }

        $services = $this->Services->getAllByClient($this->client->id, 'all');
        $package_counts = [];
        foreach ($services as $service) {
            if (!isset($package_counts[$service->package->id])) {
                $package_counts[$service->package->id] = 0;
            }
            $package_counts[$service->package->id]++;
        }

        if ($evaluate_cart) {
            $cart = $this->SessionCart->get();
            foreach ($cart['items'] as $item) {
                $package = $this->Packages->getByPricingId($item['pricing_id']);

                if (!isset($package_counts[$package->id])) {
                    $package_counts[$package->id] = 0;
                }
                $package_counts[$package->id]++;
            }
        }

        return $package_counts;
    }
}
