<?php

use Blesta\Core\Util\Input\Fields\InputFields;

/**
 * Order main controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminMain extends OrderAffiliateController
{
    /**
     * Pre action
     */
    public function preAction()
    {
        parent::preAction();

        $this->requireLogin();

        $this->uses(['Order.OrderOrders']);

        Language::loadLang('admin_main', null, PLUGINDIR . 'order' . DS . 'language' . DS);
    }

    /**
     * Renders the orders widget
     */
    public function index()
    {
        // Only available via AJAX
        if (!$this->isAjax()) {
            $this->redirect($this->base_uri . 'billing/');
        }

        $this->components(['SettingsCollection']);

        $status = (isset($this->get[0]) ? $this->get[0] : 'pending');
        $page = (isset($this->get[1]) ? (int)$this->get[1] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_added');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        if (isset($this->get[0])) {
            $status = $this->get[0];
        }

        // If no page set, fetch counts
        if (!isset($this->get[1])) {
            $status_count = [
                'pending' => $this->OrderOrders->getListCount('pending'),
                'accepted' => $this->OrderOrders->getListCount('accepted'),
                'fraud' => $this->OrderOrders->getListCount('fraud'),
                'canceled' => $this->OrderOrders->getListCount('canceled'),
            ];
            $this->set('status_count', $status_count);
        }

        $statuses = $this->OrderOrders->getStatuses();
        unset($statuses[$status]);

        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('status', $status);
        $this->set('statuses', $statuses);

        $total_results = $this->OrderOrders->getListCount($status);
        $orders = $this->OrderOrders->getList($status, $page, [$sort => $order]);

        // Determine the geo IP
        $system_settings = $this->SettingsCollection->fetchSystemSettings();
        foreach ($orders as $ord) {
            $ord->geo_ip = $this->getGeoIp($ord->ip_address, $system_settings);
        }

        $this->set('orders', $orders);

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'widget/order/admin_main/index/' . $status . '/[p]/'
            ]
        );
        $this->setPagination($this->get, $settings);

        return $this->renderAjaxWidgetIfAsync(
            isset($this->get['sort']) ? true : (isset($this->get[1]) || isset($this->get[0]) ? false : null)
        );
    }

    /**
     * Renders the order widget for the dashboard
     */
    public function dashboard()
    {
        // Fetch content from the index action
        $this->action = 'index';
        $this->set('dashboard', 'true');
        return $this->index();
    }

    /**
     * Returns the orders widget for a given client
     */
    public function orders()
    {
        $this->uses(['Clients']);
        $this->components(['SettingsCollection']);

        // Only available via AJAX
        if (!$this->isAjax()) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        // Ensure a valid client was given
        $client_id = ($this->get['client_id'] ?? ($this->get[0] ?? null));

        if (empty($client_id) || !($client = $this->Clients->get($client_id))) {
            $this->redirect($this->base_uri . 'clients/');
        }
        $this->set('client', $client);

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

        $order_filters = array_merge([
            'client_id' => $client->id
        ], $post_filters);

        $status = ($this->get[1] ?? 'pending');
        $page = (isset($this->get[2]) ? (int)$this->get[2] : 1);
        $sort = ($this->get['sort'] ?? 'date_added');
        $order = ($this->get['order'] ?? 'desc');

        // If no page set, fetch counts
        if (!isset($this->get[2])) {
            $status_count = [
                'pending' => $this->OrderOrders->getListCount('pending', $order_filters),
                'accepted' => $this->OrderOrders->getListCount('accepted', $order_filters),
                'fraud' => $this->OrderOrders->getListCount('fraud', $order_filters),
                'canceled' => $this->OrderOrders->getListCount('canceled', $order_filters),
            ];
            $this->set('status_count', $status_count);
        }

        // Set the input field filters for the widget
        $filters = $this->getFilters($post_filters);
        $this->set('filters', $filters);
        $this->set('filter_vars', $post_filters);

        $statuses = $this->OrderOrders->getStatuses();
        unset($statuses[$status]);

        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('status', $status);
        $this->set('statuses', $statuses);

        $total_results = $this->OrderOrders->getListCount($status, $order_filters);
        $orders = $this->OrderOrders->getList($status, $page, [$sort => $order], $order_filters);

        // Determine the geo IP
        $system_settings = $this->SettingsCollection->fetchSystemSettings();
        foreach ($orders as $ord) {
            $ord->geo_ip = $this->getGeoIp($ord->ip_address, $system_settings);
        }

        $this->set('orders', $orders);

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'plugin/order/admin_main/orders/' . $client->id . '/' . $status . '/[p]/'
            ]
        );
        $this->setPagination($this->get, $settings);

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(
                isset($this->get['client_id']) ? null : (isset($this->get[2]) || isset($this->get['sort']))
            );
        }
    }

    /**
     * Gets a list of input fields for filtering domains
     *
     * @param array $vars A list of submitted inputs that act as defaults for filter fields including:
     *  - order_number The module ID on which to filter packages
     * @return InputFields An object representing the list of filter input field
     */
    private function getFilters(array $vars = [])
    {
        $fields = new InputFields();

        // Set the order number filter
        $order_number = $fields->label(
            Language::_('AdminMain.getfilters.field_order_number', true),
            'order_number'
        );
        $order_number->attach(
            $fields->fieldText(
                'filters[order_number]',
                $vars['order_number'] ?? null,
                [
                    'id' => 'order_number',
                    'class' => 'form-control stretch',
                    'placeholder' => Language::_('AdminMain.getfilters.field_order_number', true)
                ]
            )
        );
        $fields->setField($order_number);

        return $fields;
    }

    /**
     * Client orders count
     */
    public function clientOrdersCount()
    {
        $this->uses(['Order.OrderOrders']);

        $client_id = $this->get[0] ?? null;
        $status = $this->get[1] ?? 'pending';

        echo $this->OrderOrders->getListCount($status, ['client_id' => $client_id]);

        return false;
    }

    /**
     * List related information for a given order
     */
    public function orderInfo()
    {
        // Ensure a department ID was given
        if (!$this->isAjax() || !isset($this->get[0]) ||
            !($order = $this->OrderOrders->get($this->get[0]))) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $this->uses(['Transactions', 'Services', 'Packages']);

        // Set language for periods
        $periods = $this->Packages->getPricingPeriods();
        foreach ($this->Packages->getPricingPeriods(true) as $period => $lang) {
            $periods[$period . '_plural'] = $lang;
        }

        // Set services
        $services = [];
        foreach ($order->services as $temp) {
            if (($service = $this->Services->get($temp->service_id))) {
                $services[] = $service;
            }
        }

        // JSON-decode the order fraud report we have stored for use in the view
        if (!empty($order->fraud_report)) {
            $order->fraud_report = (array)json_decode($order->fraud_report);
        }

        $vars = [
            'order' => $order,
            'applied'=> $this->Transactions->getApplied(null, $order->invoice_id),
            'services' => $services,
            'periods' => $periods,
            'transaction_types' => $this->Transactions->transactionTypeNames()
        ];

        // Send the template
        echo $this->partial('admin_main_orderinfo', $vars);

        // Render without layout
        return false;
    }

    /**
     * Outputs the badge response for the current number of orders with the given status
     */
    public function statusCount()
    {
        // Only available via AJAX
        if (!$this->isAjax()) {
            $this->redirect($this->base_uri . 'billing/');
        }

        $this->uses(['Order.OrderOrders']);
        $status = isset($this->get[0]) ? $this->get[0] : 'pending';

        echo $this->OrderOrders->getListCount($status);
        return false;
    }

    /**
     * Settings
     */
    public function settings()
    {
        // Only available via AJAX
        if (!$this->isAjax()) {
            $this->redirect($this->base_uri . 'billing/');
        }

        $this->helpers(['DataStructure']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        $this->uses(['Order.OrderStaffSettings']);

        $settings = $this->ArrayHelper->numericToKey(
            $this->OrderStaffSettings->getSettings($this->Session->read('blesta_staff_id'), $this->company_id),
            'key',
            'value'
        );
        $this->set('vars', $settings);

        return $this->renderAjaxWidgetIfAsync(false);
    }

    /**
     * Update settings
     */
    public function update()
    {
        $this->uses(['Order.OrderStaffSettings']);

        // Get all overview settings
        if (!empty($this->post)) {
            $this->OrderStaffSettings->setSettings(
                $this->Session->read('blesta_staff_id'),
                $this->company_id,
                $this->post
            );

            $this->flashMessage('message', Language::_('AdminMain.!success.settings_updated', true));
        }

        $this->redirect($this->base_uri . 'billing/');
    }

    /**
     * Update affiliate settings
     */
    public function updateAffiliateSettings()
    {
        $this->uses(['Clients', 'Order.OrderAffiliates', 'Order.OrderAffiliateSettings']);


        // Get client or redirect if not given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Get affiliate or redirect if not given
        if (!($affiliate = $this->OrderAffiliates->getByClientId($client->id))) {
            $this->redirect($this->base_uri . 'plugin/order/admin_affiliates/add/' . $client->id);
        }

        // Set all company affiliate settings
        if (!empty($this->post)) {
            if (!isset($this->post['order_recurring'])) {
                $this->post['order_recurring'] = 'false';
            }

            $this->OrderAffiliateSettings->setSettings(
                $affiliate->id,
                $this->post
            );

            $this->flashMessage('message', Language::_('AdminMain.!success.affiliate_settings_updated', true));
        }

        $this->redirect($this->base_uri . 'plugin/order/admin_main/affiliates/' . $client->id);
    }

    /**
     * Update status for the given set of orders
     */
    public function updateStatus()
    {
        if (isset($this->post['order_id'])) {
            $this->OrderOrders->setStatus($this->post);
        }

        $this->flashMessage('message', Language::_('AdminMain.!success.status_updated', true));

        if (isset($this->get['client_id'])) {
            $this->redirect($this->base_uri . 'clients/view/'. ($this->get['client_id'] ?? ($this->get[0] ?? null)));
        } else {
            $this->redirect($this->base_uri . 'billing/');
        }
    }

    /**
     * Search orders
     */
    public function search()
    {
        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);

        // Load the Text Parser
        $this->helpers(['TextParser']);

        // Get search criteria
        $search = (isset($this->get['search']) ? $this->get['search'] : '');
        if (isset($this->post['search'])) {
            $search = $this->post['search'];
        }

        // Set page title
        $this->structure->set('page_title', Language::_('AdminMain.search.page_title', true, $search));

        $this->components(['SettingsCollection']);

        $page = (isset($this->get['p']) ? (int)$this->get['p'] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_added');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');


        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('search', $search);

        // Search
        $orders = $this->OrderOrders->search(
            $search,
            $page,
            [$sort => $order, 'orders.id' => $order]
        );

        // Determine the geo IP
        $system_settings = $this->SettingsCollection->fetchSystemSettings();
        foreach ($orders as $ord) {
            $ord->geo_ip = $this->getGeoIp($ord->ip_address, $system_settings);
        }

        $this->set('statuses', $this->OrderOrders->getStatuses());
        $this->set('orders', $orders);
        $this->set('search', $search);

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $this->OrderOrders->getSearchCount($search),
                'uri' => $this->base_uri . 'widget/order/admin_main/search/',
                'params' => ['p' => '[p]', 'search' => $search, 'sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(
                isset($this->post['search']) ? null : (isset($this->get['search']) || isset($this->get['sort']))
            );
        }
    }

    /**
     * Displays affiliate information for a given client
     */
    public function affiliates()
    {
        $this->uses(
            [
                'Clients',
                'Currencies',
                'Order.OrderAffiliates',
                'Order.OrderAffiliateSettings',
                'Order.OrderAffiliateCompanySettings'
            ]
        );

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);

        // Get client or redirect if not given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Get affiliate or redirect if not given
        if (!($affiliate = $this->OrderAffiliates->getByClientId($client->id))) {
            $this->redirect($this->base_uri . 'plugin/order/admin_affiliates/add/' . $client->id);
        }

        // Change the affiliate status to active
        if (isset($this->post['activate']) && $this->post['activate'] == 'true') {
            $this->OrderAffiliates->edit($affiliate->id, ['status' => 'active']);
        }

        // Get affiliate settings
        $affiliate_settings = $affiliate
            ? $this->Form->collapseObjectArray(
                $this->OrderAffiliateSettings->getSettings($affiliate->id),
                'value',
                'key'
            )
            : [];

        // Calculate the number of days since this affiliate was added
        $days_active = $this->OrderAffiliates->getAffiliateDaysActive($affiliate->id);

        // Get affiliate statistics
        $statistics = $this->getStatistics($affiliate->id);

        $datetime = $this->Date->format('c');
        $date_range = [
            'start' => strtotime($this->OrderAffiliates->dateToUtc(
                $this->Date->modify($datetime, '-3 months')
            )),
            'end' => strtotime($this->OrderAffiliates->dateToUtc($datetime))
        ];

        $this->set('vars', $affiliate_settings);
        $this->set('client', $client);
        $this->set('affiliate', $affiliate);
        $this->set('affiliate_settings', $affiliate_settings);
        $this->set('statistics', $statistics);
        $this->set('date_range', $date_range);
        $this->set(
            'available_payout',
            $this->getAvailableAffiliatePayout(
                $affiliate->id,
                isset($affiliate_settings['withdrawal_currency'])
                    ? $affiliate_settings['withdrawal_currency']
                    : 'USD'
            )
        );
        $this->set(
            'referral_link',
            trim($this->base_url, '/')
                . (isset($this->public_uri) ? $this->public_uri : '/')
                . 'order/forms/a/' . $affiliate->code
        );
        $this->set('days_active', $days_active);
        $this->set('commission_types', $this->OrderAffiliateCompanySettings->getCommissionTypes());
        $this->set('order_frequencies', $this->OrderAffiliateCompanySettings->getOrderFrequencies());
        $this->set(
            'currencies',
            $this->Form->collapseObjectArray($this->Currencies->getAll($this->company_id), 'code', 'code')
        );

        $this->structure->set('page_title', Language::_('AdminMain.affiliates.page_title', true));
        $this->structure->set('title', Language::_('AdminMain.affiliates.page_title', true));

        return $this->renderAjaxWidgetIfAsync();
    }

    /**
     * Get the affiliate statistics
     *
     * @param int $affiliate_id The ID of the affiliate from which to obtain the statistics
     * @return array A multidimensional key/pair indexed array keyed by type and timestamp
     */
    private function getStatistics($affiliate_id)
    {
        $this->uses(
            [
                'Order.OrderAffiliateReferrals',
                'Order.OrderAffiliateStatistics'
            ]
        );

        // Set dates
        $datetime = $this->Date->format('c');
        $dates = [
            'start' => $this->OrderAffiliateReferrals->dateToUtc(
                $this->Date->cast($this->Date->modify($datetime, '-12 months'), 'Y-m-d')
            ),
            'end' => $this->OrderAffiliateReferrals->dateToUtc($datetime)
        ];

        // Get affiliate referrals for this month
        $referrals_stats = [];
        $referrals = $this->OrderAffiliateReferrals->getAll([
            'affiliate_id' => $affiliate_id,
            'start_date' => $this->Date->cast($dates['start'], 'Y-m-d'),
            'end_date' => $this->Date->cast($dates['end'], 'Y-m-d')
        ]);

        foreach ($referrals as $referral) {
            $timestamp = strtotime($this->Date->cast($referral->date_added, 'Y-m-d'));
            $referrals_stats[$timestamp] = (isset($referrals_stats[$timestamp]) ? $referrals_stats[$timestamp] : 0) + 1;
        }

        // Get affiliate stats for this year
        $visits_stats = [];
        $sales_stats = [];
        $affiliate_stats = $this->OrderAffiliateStatistics->get([
            'affiliate_id' => $affiliate_id,
            'start_date' => $this->Date->cast($dates['start'], 'Y-m-d'),
            'end_date' => $this->Date->cast($dates['end'], 'Y-m-d')
        ]);

        foreach ($affiliate_stats as $stat) {
            $timestamp = strtotime($this->Date->cast($stat->date, 'Y-m-d'));
            $visits_stats[$timestamp] = (isset($visits_stats[$timestamp]) ? $visits_stats[$timestamp] : 0)
                + (isset($stat->visits) ? $stat->visits : 0);
            $sales_stats[$timestamp] = (isset($sales_stats[$timestamp]) ? $sales_stats[$timestamp] : 0)
                + (isset($stat->sales) ? $stat->sales : 0);
        }

        $statistics = [
            'referrals' => $referrals_stats,
            'visits' => $visits_stats,
            'sales' => $sales_stats
        ];

        // Build graph data
        $date_range = [
            'start' => strtotime($dates['start']),
            'end' => strtotime($dates['end'])
        ];
        $step = 24 * 60 * 60; // 1 day

        foreach ($statistics as $type => $values) {
            $year_days = 0;

            for ($i = $date_range['start']; $i <= $date_range['end']; $i = $i + $step) {
                $statistics[$type][$i] = isset($statistics[$type][$i]) ? $statistics[$type][$i] : 0;
                $year_days++;
            }

            $statistics[$type] = array_slice($statistics[$type], 0, $year_days, true);
            ksort($statistics[$type]);
        }

        return $statistics;
    }
}
