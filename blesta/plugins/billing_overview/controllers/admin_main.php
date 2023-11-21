<?php
/**
 * Billing Overview main controller
 *
 * @package blesta
 * @subpackage blesta.plugins.billing_overview
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminMain extends BillingOverviewController
{
    /**
     * @var string A unique key to prefix transaction type lines
     */
    private $trans_type_key;

    /**
     * Pre action
     */
    public function preAction()
    {
        parent::preAction();

        $this->requireLogin();

        // Load settings
        $this->uses(['BillingOverview.BillingOverviewSettings']);

        // Load currency helper
        $this->helpers(['CurrencyFormat', 'Form', 'Html']);

        Language::loadLang('admin_main', null, PLUGINDIR . 'billing_overview' . DS . 'language' . DS);

        $this->trans_type_key = 'trans_' . time();
    }

    /**
     * Get graph date ranges
     */
    private function getDateRanges()
    {
        // Set graph date ranges
        return [
            7 => '7 ' . Language::_('AdminMain.date_range.days', true),
            30 => '30 ' . Language::_('AdminMain.date_range.days', true)
        ];
    }

    /**
     * Renders the billing overview widget
     */
    public function index()
    {
        // Only available via AJAX
        if (!$this->isAjax()) {
            $this->redirect($this->base_uri . 'billing/');
        }

        // Set the overview content
        $this->set('content', $this->partial('admin_main_overview', $this->overview(false)));

        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) ? false : null);
    }

    /**
     * Renders the billing overview widget for the dashboard
     */
    public function dashboard()
    {
        // Fetch content from the index action
        $this->action = 'index';
        $this->set('dashboard', 'true');
        return $this->index();
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

        // Get all overview settings
        $settings = [];
        $overview_settings = $this->BillingOverviewSettings->getSettings(
            $this->Session->read('blesta_staff_id'),
            $this->company_id
        );

        foreach ($overview_settings as $setting) {
            $settings[$setting->key] = $setting->value;
        }

        $this->set('vars', $settings);
        $this->set('date_ranges', $this->getDateRanges());

        return $this->renderAjaxWidgetIfAsync(false);
    }

    /**
     * Update settings
     */
    public function update()
    {
        // Set unchecked checkboxes
        $settings = ['revenue_today', 'revenue_month', 'revenue_year', 'credits_today', 'credits_month', 'credits_year',
            'invoiced_today', 'invoiced_month', 'invoiced_today_proforma', 'invoiced_month_proforma',
            'balance_outstanding', 'balance_overdue', 'scheduled_cancelation', 'services_active',
            'services_added_today', 'services_canceled_today', 'graph_revenue', 'graph_revenue_year', 'graph_invoiced',
            'show_legend'
        ];
        foreach ($settings as $setting) {
            if (!isset($this->post[$setting])) {
                $this->post[$setting] = 0;
            }
        }
        unset($settings, $setting);

        // Set each setting into indexed array for adding
        $settings = [];
        foreach ($this->post as $key => $value) {
            $settings[] = ['key' => $key, 'value' => $value];
        }

        // Add the settings
        $this->BillingOverviewSettings->add($this->Session->read('blesta_staff_id'), $this->company_id, $settings);

        if (($errors = $this->BillingOverviewSettings->errors())) {
            // Error
            $this->flashMessage('error', $errors);
        } else {
            // Success
            $this->flashMessage('message', Language::_('AdminMain.!success.options_updated', true));
        }

        $this->redirect($this->base_uri . 'billing/');
    }

    /**
     * Retrieves the billing overview inner content
     */
    public function overview($echo = true)
    {
        // Load settings, statistics, currencies
        $this->uses(['BillingOverview.BillingOverviewStatistics', 'Currencies']);
        $this->components(['SettingsCollection']);

        // Set staff ID
        $staff_id = $this->Session->read('blesta_staff_id');

        // Set dates
        $datetime = $this->Date->format('c');
        $dates = [
            'today_start' => $this->Date->cast($datetime, 'Y-m-d 00:00:00'),
            'today_end' => $this->Date->cast($datetime, 'Y-m-d 23:59:59'),
            'month_start' => $this->Date->cast($datetime, 'Y-m-01 00:00:00'),
            'month_end' => $this->Date->cast($datetime, 'Y-m-t 23:59:59'),
            'year_start' => $this->Date->cast($datetime, 'Y-01-01 00:00:00'),
            'year_end' => $this->Date->cast($datetime, 'Y-12-31 23:59:59')
        ];

        // Set currency
        $default_currency = $this->SettingsCollection->fetchSetting(null, $this->company_id, 'default_currency');
        $currency = (isset($default_currency['value']) ? $default_currency['value'] : '');

        if (!empty($this->post['currency'])) {
            $currency = $this->post['currency'];
        }

        // Get the statistics to show for this user
        $overview_settings = $this->BillingOverviewSettings->getSettings($staff_id, $this->company_id);

        // Set default settings for this staff member if none yet exist
        if (empty($overview_settings)) {
            $this->BillingOverviewSettings->addDefault($staff_id, $this->company_id);
            $overview_settings = $this->BillingOverviewSettings->getSettings($staff_id, $this->company_id);
        }

        // Set which statistics to show
        $active_statistics = [];
        foreach ($overview_settings as $setting) {
            if ($setting->value == 1) {
                $active_statistics[] = $setting->key;
            }
        }

        // Set statistics
        $statistics = [];
        // Get each statistic's data
        foreach ($active_statistics as $statistic) {
            $value = '';
            $value_class = '';
            $icon = '';

            // Set statistic-specific values
            switch ($statistic) {
                case 'revenue_today':
                    // Get today's revenue
                    $value = $this->CurrencyFormat->format(
                        $this->BillingOverviewStatistics->getRevenue(
                            $this->company_id,
                            $currency,
                            $dates['today_start'],
                            $dates['today_end']
                        ),
                        $currency
                    );
                    $value_class = 'more';
                    $icon = 'fa-chart-line';
                    break;
                case 'revenue_month':
                    // Get this month's revenue
                    $value = $this->CurrencyFormat->format(
                        $this->BillingOverviewStatistics->getRevenue(
                            $this->company_id,
                            $currency,
                            $dates['month_start'],
                            $dates['month_end']
                        ),
                        $currency
                    );
                    $value_class = 'more';
                    $icon = 'fa-chart-area';
                    break;
                case 'revenue_year':
                    // Get this year's revenue
                    $value = $this->CurrencyFormat->format(
                        $this->BillingOverviewStatistics->getRevenue(
                            $this->company_id,
                            $currency,
                            $dates['year_start'],
                            $dates['year_end']
                        ),
                        $currency
                    );
                    $value_class = 'more';
                    $icon = 'fa-chart-bar';
                    break;
                case 'credits_today':
                    // Get today's credits
                    $value = $this->CurrencyFormat->format(
                        $this->BillingOverviewStatistics->getCredits(
                            $this->company_id,
                            $currency,
                            $dates['today_start'],
                            $dates['today_end']
                        ),
                        $currency
                    );
                    $value_class = 'neutral';
                    $icon = 'fa-chart-line';
                    break;
                case 'credits_month':
                    // Get this month's credits
                    $value = $this->CurrencyFormat->format(
                        $this->BillingOverviewStatistics->getCredits(
                            $this->company_id,
                            $currency,
                            $dates['month_start'],
                            $dates['month_end']
                        ),
                        $currency
                    );
                    $value_class = 'neutral';
                    $icon = 'fa-chart-area';
                    break;
                case 'credits_year':
                    // Get this year's credits
                    $value = $this->CurrencyFormat->format(
                        $this->BillingOverviewStatistics->getCredits(
                            $this->company_id,
                            $currency,
                            $dates['year_start'],
                            $dates['year_end']
                        ),
                        $currency
                    );
                    $value_class = 'neutral';
                    $icon = 'fa-chart-bar';
                    break;
                case 'invoiced_today':
                    // Get today's invoice total
                    $value = $this->CurrencyFormat->format(
                        $this->BillingOverviewStatistics->getAmountInvoiced(
                            $this->company_id,
                            $currency,
                            $dates['today_start'],
                            $dates['today_end'],
                            ['active']
                        ),
                        $currency
                    );
                    $value_class = 'neutral';
                    $icon = 'fa-file-invoice-dollar';
                    break;
                case 'invoiced_month':
                    // Get this month's invoice total
                    $value = $this->CurrencyFormat->format(
                        $this->BillingOverviewStatistics->getAmountInvoiced(
                            $this->company_id,
                            $currency,
                            $dates['month_start'],
                            $dates['month_end'],
                            ['active']
                        ),
                        $currency
                    );
                    $value_class = 'neutral';
                    $icon = 'fa-file-invoice-dollar';
                    break;
                case 'invoiced_today_proforma':
                    // Get today's invoice total
                    $value = $this->CurrencyFormat->format(
                        $this->BillingOverviewStatistics->getAmountInvoiced(
                            $this->company_id,
                            $currency,
                            $dates['today_start'],
                            $dates['today_end'],
                            ['proforma']
                        ),
                        $currency
                    );
                    $value_class = 'neutral';
                    $icon = 'fa-file-invoice-dollar';
                    break;
                case 'invoiced_month_proforma':
                    // Get this month's invoice total
                    $value = $this->CurrencyFormat->format(
                        $this->BillingOverviewStatistics->getAmountInvoiced(
                            $this->company_id,
                            $currency,
                            $dates['month_start'],
                            $dates['month_end'],
                            ['proforma']
                        ),
                        $currency
                    );
                    $value_class = 'neutral';
                    $icon = 'fa-file-invoice-dollar';
                    break;
                case 'balance_outstanding':
                    // Get the total amount to be paid
                    $value = $this->CurrencyFormat->format(
                        $this->BillingOverviewStatistics->getOutstandingBalance($this->company_id, $currency),
                        $currency
                    );
                    $value_class = 'neutral';
                    $icon = 'fa-balance-scale';
                    break;
                case 'balance_overdue':
                    // Get the total amount past due
                    $value = $this->CurrencyFormat->format(
                        $this->BillingOverviewStatistics->getOverdueBalance($this->company_id, $currency),
                        $currency
                    );
                    $value_class = 'less';
                    $icon = 'fa-balance-scale';
                    break;
                case 'scheduled_cancelation':
                    // Get the number of service cancelations
                    $value = $this->BillingOverviewStatistics->getScheduledCancelationsCount($this->company_id);
                    $value_class = 'neutral';
                    $icon = 'fa-clock';
                    break;
                case 'services_active':
                    // Get the number of active services
                    $value = $this->BillingOverviewStatistics->getActiveServicesCount($this->company_id);
                    $value_class = 'neutral';
                    $icon = 'fa-cogs';
                    break;
                case 'services_added_today':
                    // Get the number of services added today
                    $value = $this->BillingOverviewStatistics->getServicesAddedCount(
                        $this->company_id,
                        $dates['today_start'],
                        $dates['today_end']
                    );
                    $value_class = 'neutral';
                    $icon = 'fa-plus';
                    break;
                case 'services_canceled_today':
                    // Get the number of services canceled today
                    $value = $this->BillingOverviewStatistics->getServicesCanceledCount(
                        $this->company_id,
                        $dates['today_start'],
                        $dates['today_end']
                    );
                    $value_class = 'neutral';
                    $icon = 'fa-minus';
                    break;
                default:
                    // Move on, this is not a statistic setting
                    continue 2;
            }

            $statistics[] = [
                'class' => $statistic,
                'name' => Language::_('AdminMain.index.statistic.' . $statistic, true),
                'value' => $value,
                'value_class' => $value_class,
                'icon' => 'fas fa-fw ' . $icon
            ];
        }

        $data = [
            'vars' => ['currency' => $currency],
            'currencies' => $this->Form->collapseObjectArray(
                $this->Currencies->getAll($this->company_id),
                'code',
                'code'
            ),
            'currency' => $this->Currencies->get($currency, $this->company_id),
            'statistics' => $statistics,
            'graphs' => $this->getGraphs($currency, $overview_settings)
        ];

        // Return the overview data
        if (!$echo) {
            return $data;
        }

        $this->outputAsJson(['overview'=>$this->partial('admin_main_overview', $data)]);
        return false;
    }

    /**
     * Sets up the data for each graph
     *
     * @param string $currency The ISO 4217 currency code
     * @param array $settings The plugin settings (optional)
     * @return array A list of graph data and settings
     */
    private function getGraphs($currency, $settings = null)
    {
        $this->uses(['GatewayManager', 'Transactions']);
        $gateways = [];
        $transaction_types = $this->Transactions->transactionTypeNames();

        // Get settings if not given
        if ($settings == null) {
            $settings = $this->BillingOverviewSettings->getSettings(
                $this->Session->read('blesta_staff_id'),
                $this->company_id
            );
        }

        // Get date range of graphs
        $graph_settings = [];
        foreach ($settings as $setting) {
            if ($setting->key == 'date_range' || $setting->key == 'show_legend') {
                $graph_settings[$setting->key] = $setting->value;
            }
        }

        // Get each graph in use
        $graphs = ['graphs' => [], 'settings' => $graph_settings];

        foreach ($settings as $setting) {
            $graph = null;

            // Setting is disabled
            if ($setting->value != 1) {
                continue;
            }

            switch ($setting->key) {
                case 'graph_invoiced':
                case 'graph_revenue':
                    // Get the graph data over the set interval
                    $graph = $this->getGraph(
                        $setting->key,
                        (isset($graph_settings['date_range']) ? $graph_settings['date_range'] : 0),
                        $currency
                    );
                    break;
                case 'graph_revenue_year':
                    // Get the graph data
                    $local_date = clone $this->Date;
                    $local_date->setTimezone('UTC', Configure::get('Blesta.company_timezone'));

                    $now = date('c');
                    $start_date = $this->BillingOverviewSettings->dateToUtc(
                        $local_date->format('Y-01-01 00:00:00', strtotime($now))
                    );
                    $end_date = $this->BillingOverviewSettings->dateToUtc(
                        $local_date->format('Y-12-31 23:59:59', strtotime($now))
                    );
                    $graph = $this->getGraphBetween($setting->key, $start_date, $end_date, $currency);
                    break;
            }

            if (isset($graph)) {
                // Set each graph line name
                $data = [];

                foreach ($graph as $key => $value) {
                    $disabled = false;

                    // Determine the name of the line
                    $line_name = Language::_('AdminMain.graph_line_name.' . $key, true);

                    // The line is for a gateway, use the gateway's name if available
                    if (is_numeric($key)) {
                        if (!isset($gateways[$key])) {
                            $gateways[$key] = '';

                            if (($gw = $this->GatewayManager->get($key))) {
                                $gateways[$key] = $gw->name;
                            }
                        }

                        $line_name = $gateways[$key];
                    } elseif (substr($key, 0, strlen($this->trans_type_key)) === $this->trans_type_key) {
                        // The line is for a transaction type, use the transaction type's name
                        $trans_type_name = str_replace($this->trans_type_key, '', $key);
                        if (array_key_exists($trans_type_name, $transaction_types)) {
                            $line_name = $transaction_types[$trans_type_name];
                        } else {
                            // The trans type is unknown or no longer exists, use 'other'
                            $line_name = Language::_('AdminMain.graph_line_name.other', true);
                        }

                        // Determine whether the transaction type should be disabled by default
                        // by checking for a third, 'credit', value in the set of points
                        foreach ($value as $point) {
                            if (isset($point[2]) && $point[2] === 'credit') {
                                $disabled = true;
                                break;
                            }
                        }
                    }

                    $data[] = [
                        'name' => $line_name,
                        'disabled' => $disabled,
                        'points' => json_encode($value)
                    ];
                }

                $graphs['graphs'][$setting->key] = [
                    'name' => Language::_('AdminMain.graph_name.' . $setting->key, true),
                    'data' => $data
                ];
            }
        }

        return $graphs;
    }

    /**
     * Retrieves graph date over a given interval
     *
     * @param string $key The graph setting key
     * @param string $start_date The start date from which to fetch data
     * @param string $end_date The end date from which to fetch data
     * @param string $currency The ISO 4217 currency code
     * @param string $interval The interval between data points (i.e. day, week, month, optional, default month)
     * @return array An array of line data representing the graph
     */
    private function getGraphBetween($key, $start_date, $end_date, $currency, $interval = 'month')
    {
        $lines = [];
        $interval = (in_array($interval, ['month', 'week', 'day']) ? $interval : 'month');

        $local_date = clone $this->Date;
        $local_date->setTimezone('UTC', Configure::get('Blesta.company_timezone'));
        $end = strtotime($local_date->cast($start_date));
        $now = date('c');
        $i = 0;

        // Set month interval, and whether to start at the beginning of the month, or sometime in between
        $month_interval = ($interval == 'month');
        $start_date_time = strtotime($start_date);
        $start_day_format = ($month_interval && $local_date->format('j', $start_date_time) == '1' ? '01' : 'd');

        $start_dates = [];
        $gateway_lines = [];
        $transaction_lines = [];

        while (strtotime($local_date->cast($end_date)) > $end) {
            $start_format = 'Y-m-' . ($i == 0 ? $start_day_format : ($month_interval ? '01' : 'd')) . ' 00:00:00';
            $date_start = date($start_format, $end);
            $date_end = date('Y-m-' . ($month_interval ? 't' : 'd') . ' 23:59:59', $end);
            $end = strtotime($date_start . ' +1 ' . $interval);
            $end = strtotime($this->BillingOverviewStatistics->dateToUtc($end, 'c'));

            // Set start time to milliseconds for use in JS Date()
            $start_time = strtotime($date_start);
            $start_dates[] = $start_time;

            switch ($key) {
                case 'graph_revenue_year':
                    // Skip fetching revenue received in a future date
                    if ($start_time > strtotime($now)) {
                        continue 2;
                    }

                    $lines['credit'][] = [
                        $start_time,
                        (float)$this->CurrencyFormat->cast(
                            $this->BillingOverviewStatistics->getRevenue(
                                $this->company_id,
                                $currency,
                                $date_start,
                                $date_end,
                                'cc'
                            ),
                            $currency
                        )
                    ];
                    $lines['ach'][] = [
                        $start_time,
                        (float)$this->CurrencyFormat->cast(
                            $this->BillingOverviewStatistics->getRevenue(
                                $this->company_id,
                                $currency,
                                $date_start,
                                $date_end,
                                'ach'
                            ),
                            $currency
                        )
                    ];

                    // Retrieve the revenue for every gateway
                    $gateways = $this->BillingOverviewStatistics->getGatewayRevenue(
                        $this->company_id,
                        $currency,
                        $date_start,
                        $date_end,
                        'other'
                    );
                    foreach ($gateways as $gateway) {
                        $gateway_lines[$gateway->gateway_id][] = [
                            $start_time,
                            (float)$gateway->total
                        ];
                    }

                    // Retrieve the revenue for every transaction type
                    $trans_types = $this->BillingOverviewStatistics->getOtherRevenue(
                        $this->company_id,
                        $currency,
                        $date_start,
                        $date_end
                    );
                    foreach ($trans_types as $trans_type) {
                        $trans_key = $this->trans_type_key
                            . (!empty($trans_type->name) ? $trans_type->name : '');

                        // Set the date and total, but also include the transaction type itself
                        // as a third value. See AdminMain::getGraphs
                        $transaction_lines[$trans_key][] = [
                            $start_time,
                            (float)$trans_type->total,
                            $trans_type->type
                        ];
                    }

                    break;
            }
            $i++;
        }

        // Omit lines that contain no amounts
        $lines = $this->removeEmptyLines($lines, ['credit']) + $gateway_lines + $transaction_lines;
        // Ensure every line has a value for every date interval
        $lines = $this->setDefaultValues($lines, $start_dates);

        return $lines;
    }

    /**
     * Retrieves graph data over a given interval
     *
     * @param string $key The graph setting key
     * @param int $days The time interval in days to retrieve data from
     * @param string $currency The ISO 4217 currency code
     * @return array An array of line data representing the graph
     */
    private function getGraph($key, $days, $currency)
    {
        // Set values for each graph line
        $lines = [];
        $datetime = $this->Date->format('c');
        $start_dates = [];
        $gateway_lines = [];
        $transaction_lines = [];

        for ($i = 0; $i < max(0, (int)$days); $i++) {
            // Set start/end dates
            $date = strtotime($datetime . ' -' . $i . ' days');
            $day_start = $this->Date->cast($date, 'Y-m-d 00:00:00');
            $day_end = $this->Date->cast($date, 'Y-m-d 23:59:59');

            // Set start time to milliseconds UTC for use in JS Date()
            $day_start_time = $date * 1000;
            $start_dates[] = $date;

            // Set each graph data point
            switch ($key) {
                case 'graph_revenue':
                    $lines['credit'][] = [
                        $day_start_time,
                        (float)$this->CurrencyFormat->cast(
                            $this->BillingOverviewStatistics->getRevenue(
                                $this->company_id,
                                $currency,
                                $day_start,
                                $day_end,
                                'cc'
                            ),
                            $currency
                        )
                    ];
                    $lines['ach'][] = [
                        $day_start_time,
                        (float)$this->CurrencyFormat->cast(
                            $this->BillingOverviewStatistics->getRevenue(
                                $this->company_id,
                                $currency,
                                $day_start,
                                $day_end,
                                'ach'
                            ),
                            $currency
                        )
                    ];

                    // Retrieve the revenue for every gateway
                    $gateways = $this->BillingOverviewStatistics->getGatewayRevenue(
                        $this->company_id,
                        $currency,
                        $day_start,
                        $day_end,
                        'other'
                    );
                    foreach ($gateways as $gateway) {
                        $gateway_lines[$gateway->gateway_id][] = [
                            $date,
                            (float)$gateway->total
                        ];
                    }

                    // Retrieve the revenue for every transaction type
                    $trans_types = $this->BillingOverviewStatistics->getOtherRevenue(
                        $this->company_id,
                        $currency,
                        $day_start,
                        $day_end
                    );
                    foreach ($trans_types as $trans_type) {
                        $trans_key = $this->trans_type_key
                            . (!empty($trans_type->name) ? $trans_type->name : '');

                        // Set the date and total, but also include the transaction type itself
                        // as a third value. See AdminMain::getGraphs
                        $transaction_lines[$trans_key][] = [
                            $date,
                            (float)$trans_type->total,
                            $trans_type->type
                        ];
                    }

                    break;
                case 'graph_invoiced':
                    $lines['total'][] = [
                        $day_start_time,
                        (float)$this->CurrencyFormat->cast(
                            $this->BillingOverviewStatistics->getAmountInvoiced(
                                $this->company_id,
                                $currency,
                                $day_start,
                                $day_end
                            ),
                            $currency
                        )
                    ];
                    break;
            }
        }

        // Omit lines that contain no amounts
        $lines = $this->removeEmptyLines($lines, ['total', 'credit']);

        // Ensure every gateway/transaction type has a value for every date interval
        $lines += $this->setDefaultValues($gateway_lines + $transaction_lines, $start_dates);

        // Order line values by oldest date first so the graph can display them correctly
        foreach ($lines as $type => $line) {
            $lines[$type] = array_reverse($line);
        }

        return $lines;
    }

    /**
     * Updates the given $lines to set default (zero) values for every interval in $dates
     *
     * @param array $lines An array of lines, each containing an array of data points
     *  whose 0th index is a timestamp matching those in $dates and whose 1st index is its value
     * @param array $dates An array of timestamps representing each interval
     * @return array An array of lines, each containing a complete list of all intervals,
     *  whose timestamps are in milliseconds
     */
    private function setDefaultValues(array $lines, array $dates)
    {
        $all_lines = [];
        $milliseconds = 1000;

        foreach ($lines as $key => $line) {
            // Create a default value for every interval
            $points = [];
            foreach ($dates as $date) {
                $points[$date] = [
                    $date * $milliseconds,
                    0
                ];
            }

            // Update the default value if we have any points
            foreach ($line as $point) {
                if (isset($points[$point[0]])) {
                    $data = [
                        $point[0] * $milliseconds,
                        $point[1]
                    ];

                    // Include a third value only if given
                    if (isset($point[2])) {
                        $data[] = $point[2];
                    }

                    $points[$point[0]] = $data;
                }
            }

            $all_lines[$key] = array_values($points);
        }

        return $all_lines;
    }

    /**
     * Removes lines from a graph that contain only zero values
     * @see AdminMain::getGraph()
     *
     * @param array An array of lines
     * @param array A list of type exceptions that should not be removed even when zero (e.g. "total")
     * @return array An updated array with line types of only zero values removed
     */
    private function removeEmptyLines(array $lines, array $exceptions = [])
    {
        foreach ($lines as $key => $index) {
            // Skip key exceptions
            if (in_array($key, $exceptions)) {
                continue;
            }

            foreach ($index as $value) {
                // Skip non-zero types
                if (isset($value[1]) && $value[1] != 0) {
                    continue 2;
                }
            }

            unset($lines[$key]);
        }

        return $lines;
    }
}
