<?php

/**
 * Admin Tools
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminTools extends AppController
{
    /**
     * Tools pre-action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $orig_action = $this->action;
        if (substr($this->action, 0, 3) == 'log') {
            $this->action = 'logs';
        }

        $this->requireLogin();
        $this->action = $orig_action;

        $this->uses(['Logs']);
        Language::loadLang(['admin_tools']);
    }

    /**
     * Index
     */
    public function index()
    {
        // Default to logs (module log)
        $this->redirect($this->base_uri . 'tools/logs/module/');
    }

    /**
     * All logs
     */
    public function logs()
    {
        // Default to module log
        $this->redirect($this->base_uri . 'tools/logs/module/');
    }

    /**
     * List module log data
     */
    public function logModule()
    {
        $page = (isset($this->get[0]) ? (int) $this->get[0] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_added');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        // Fetch all the module log groups
        $module_logs = $this->Logs->getModuleList($page, [$sort => $order], true);

        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('module_logs', $module_logs);
        $this->set('link_tabs', $this->getLogNames());

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $this->Logs->getModuleListCount(true),
                'uri' => $this->base_uri . 'tools/logs/module/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));
    }

    /**
     * List module log data
     */
    public function logMessenger()
    {
        $page = (isset($this->get[0]) ? (int) $this->get[0] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_added');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        // Fetch all the messenger log groups
        $messenger_logs = $this->Logs->getMessengerList($page, [$sort => $order], true);

        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('messenger_logs', $messenger_logs);
        $this->set('link_tabs', $this->getLogNames());

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $this->Logs->getMessengerListCount(true),
                'uri' => $this->base_uri . 'tools/logs/messenger/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));
    }

    /**
     * AJAX request for all module log data under a specific module log group
     */
    public function moduleLogList()
    {
        if (!isset($this->get[0]) || !$this->isAjax()) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $vars = [
            'module_logs' => $this->Logs->getModuleGroupList($this->get[0])
        ];
        // Fetch module logs for a specific group and send the template
        echo $this->partial('admin_tools_moduleloglist', $vars);

        // Render without layout
        return false;
    }

    /**
     * AJAX request for all messenger log data under a specific messenger log group
     */
    public function messengerLogList()
    {
        if (!isset($this->get[0]) || !$this->isAjax()) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $vars = [
            'messenger_logs' => $this->Logs->getMessengerGroupList($this->get[0])
        ];
        // Fetch messenger logs for a specific group and send the template
        echo $this->partial('admin_tools_messengerloglist', $vars);

        // Render without layout
        return false;
    }

    /**
     * List gateway log data
     */
    public function logGateway()
    {
        $page = (isset($this->get[0]) ? (int) $this->get[0] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_added');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        // Fetch all the gateway log groups
        $gateway_logs = $this->Logs->getGatewayList($page, [$sort => $order], true);

        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('gateway_logs', $gateway_logs);
        $this->set('link_tabs', $this->getLogNames());

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $this->Logs->getGatewayListCount(true),
                'uri' => $this->base_uri . 'tools/logs/gateway/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));
    }

    /**
     * AJAX request for all gateway log data under a specific gateway log group
     */
    public function gatewayLogList()
    {
        if (!isset($this->get[0]) || !$this->isAjax()) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $vars = [
            'gateway_logs' => $this->Logs->getGatewayGroupList($this->get[0])
        ];
        // Fetch module logs for a specific group and send the template
        echo $this->partial('admin_tools_gatewayloglist', $vars);

        // Render without layout
        return false;
    }

    /**
     * List all email log data
     */
    public function logEmail()
    {
        $page = (isset($this->get[0]) ? (int) $this->get[0] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_sent');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        // Fetch all the module log groups
        $email_logs = $this->Logs->getEmailList($page, [$sort => $order], true);

        // Format CC addresses, if available
        if ($email_logs) {
            for ($i = 0, $num_logs = count($email_logs); $i < $num_logs; $i++) {
                // Format all CC addresses from CSV to array
                $cc_addresses = $email_logs[$i]->cc_address;
                $email_logs[$i]->cc_address = [];
                foreach (explode(',', $cc_addresses) as $address) {
                    if (!empty($address)) {
                        $email_logs[$i]->cc_address[] = $address;
                    }
                }
            }
        }

        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('email_logs', $email_logs);
        $this->set('link_tabs', $this->getLogNames());

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $this->Logs->getEmailListCount(true),
                'uri' => $this->base_uri . 'tools/logs/email/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));
    }

    /**
     * List all user log data
     */
    public function logUsers()
    {
        $page = (isset($this->get[0]) ? (int) $this->get[0] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_added');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        $user_logs = $this->Logs->getUserList($page, [$sort => $order]);

        if (!isset($this->SettingsCollection)) {
            $this->components(['SettingsCollection']);
        }

        // Check whether GeoIp is enabled
        $system_settings = $this->SettingsCollection->fetchSystemSettings();
        $use_geo_ip = ($system_settings['geoip_enabled'] == 'true');
        if ($use_geo_ip) {
            // Load GeoIP database
            $this->components(['Net']);
            if (!isset($this->NetGeoIp)) {
                $this->NetGeoIp = $this->Net->create('NetGeoIp');
            }
        }

        foreach ($user_logs as &$user) {
            $user->geo_ip = [];
            if ($use_geo_ip) {
                try {
                    $user->geo_ip = ['location' => $this->NetGeoIp->getLocation($user->ip_address)];
                } catch (Throwable $e) {
                    // Nothing to do
                }
            }
        }

        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('user_logs', $user_logs);
        $this->set('link_tabs', $this->getLogNames());

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $this->Logs->getUserListCount(),
                'uri' => $this->base_uri . 'tools/logs/users/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));
    }

    /**
     * List all contact log data
     */
    public function logContacts()
    {
        $page = (isset($this->get[0]) ? (int) $this->get[0] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_changed');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('contact_logs', $this->Logs->getContactList($page, [$sort => $order]));
        $this->set('link_tabs', $this->getLogNames());

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $this->Logs->getContactListCount(),
                'uri' => $this->base_uri . 'tools/logs/contacts/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));
    }

    /**
     * List all client settings log data
     */
    public function logClientSettings()
    {
        $page = (isset($this->get[0]) ? (int) $this->get[0] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_changed');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        $client_settings_logs = $this->Logs->getClientSettingsList($page, [$sort => $order]);

        if (!isset($this->SettingsCollection)) {
            $this->components(['SettingsCollection']);
        }

        // Check whether GeoIp is enabled
        $system_settings = $this->SettingsCollection->fetchSystemSettings();
        $use_geo_ip = ($system_settings['geoip_enabled'] == 'true');
        if ($use_geo_ip) {
            // Load GeoIP database
            $this->components(['Net']);
            if (!isset($this->NetGeoIp)) {
                $this->NetGeoIp = $this->Net->create('NetGeoIp');
            }
        }

        foreach ($client_settings_logs as &$setting_log) {
            $setting_log->geo_ip = [];
            if ($use_geo_ip) {
                try {
                    $setting_log->geo_ip = ['location' => $this->NetGeoIp->getLocation($setting_log->ip_address)];
                } catch (Throwable $e) {
                    // Nothing to do
                }
            }
        }

        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('client_settings_logs', $client_settings_logs);
        $this->set('link_tabs', $this->getLogNames());

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $this->Logs->getClientSettingsListCount(),
                'uri' => $this->base_uri . 'tools/logs/clientsettings/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));
    }

    /**
     * List all transaction log data
     */
    public function logTransactions()
    {
        $page = (isset($this->get[0]) ? (int) $this->get[0] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_changed');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('transaction_logs', $this->Logs->getTransactionList($page, [$sort => $order]));
        $this->set('link_tabs', $this->getLogNames());

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $this->Logs->getTransactionListCount(),
                'uri' => $this->base_uri . 'tools/logs/transactions/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));
    }

    /**
     * List all invoice delivery log data
     */
    public function logInvoiceDelivery()
    {
        $this->uses(['Invoices']);

        $page = (isset($this->get[0]) ? (int) $this->get[0] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_sent');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('invoice_logs', $this->Invoices->getDeliveryList(null, $page, [$sort => $order]));
        $this->set('link_tabs', $this->getLogNames());
        $this->set('invoice_methods', $this->Invoices->getDeliveryMethods(null, null, false));

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $this->Invoices->getDeliveryListCount(),
                'uri' => $this->base_uri . 'tools/logs/invoicedelivery/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));
    }

    /**
     * List all account access log data
     */
    public function logAccountAccess()
    {
        // When/who unencrypted credit cards

        $page = (isset($this->get[0]) ? (int) $this->get[0] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_accessed');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('access_logs', $this->Logs->getAccountAccessList($page, [$sort => $order]));
        $this->set('link_tabs', $this->getLogNames());

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $this->Logs->getAccountAccessListCount(),
                'uri' => $this->base_uri . 'tools/logs/accountaccess/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));
    }

    /**
     * AJAX request for all account access log data
     */
    public function accountAccess()
    {
        if (!isset($this->get[0]) || !$this->isAjax()) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $this->uses(['Accounts']);

        $vars = [
            'access_logs' => $this->Logs->getAccountAccessLog($this->get[0]),
            'account_types' => $this->Accounts->getTypes(),
            'cc_types' => $this->Accounts->getCcTypes(),
            'ach_types' => $this->Accounts->getAchTypes()
        ];
        // Fetch module logs for a specific group and send the template
        echo $this->partial('admin_tools_accountaccess', $vars);

        // Render without layout
        return false;
    }

    /**
     * List all cron log data
     */
    public function logCron()
    {
        $page = (isset($this->get[0]) ? (int) $this->get[0] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'start_date');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('cron_logs', $this->Logs->getCronList($page, [$sort => $order]));
        $this->set('link_tabs', $this->getLogNames());

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $this->Logs->getCronListCount(),
                'uri' => $this->base_uri . 'tools/logs/cron/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));
    }

    /**
     * Retrieves a list of link tabs for use in templates
     */
    private function getLogNames()
    {
        return [
            ['name' => Language::_('AdminTools.getlognames.text_module', true), 'uri' => 'module'],
            ['name' => Language::_('AdminTools.getlognames.text_messenger', true), 'uri' => 'messenger'],
            ['name' => Language::_('AdminTools.getlognames.text_gateway', true), 'uri' => 'gateway'],
            ['name' => Language::_('AdminTools.getlognames.text_email', true), 'uri' => 'email'],
            ['name' => Language::_('AdminTools.getlognames.text_users', true), 'uri' => 'users'],
            ['name' => Language::_('AdminTools.getlognames.text_contacts', true), 'uri' => 'contacts'],
            ['name' => Language::_('AdminTools.getlognames.text_client_settings', true), 'uri' => 'clientsettings'],
            ['name' => Language::_('AdminTools.getlognames.text_accountaccess', true), 'uri' => 'accountaccess'],
            ['name' => Language::_('AdminTools.getlognames.text_transactions', true), 'uri' => 'transactions'],
            ['name' => Language::_('AdminTools.getlognames.text_cron', true), 'uri' => 'cron'],
            ['name' => Language::_('AdminTools.getlognames.text_invoice_delivery', true), 'uri' => 'invoicedelivery'],
        ];
    }

    /**
     * Currency conversion
     */
    public function convertCurrency()
    {
        $this->uses(['Currencies']);
        $this->components(['SettingsCollection']);

        $vars = new stdClass();

        // Set current default currency
        $default_currency = $this->SettingsCollection->fetchSetting(null, $this->company_id, 'default_currency');
        $vars->to_currency = $default_currency['value'];

        // Do the conversion
        if (!empty($this->post)) {
            $vars = (object) $this->post;

            // Convert the currency
            $amount = (isset($this->post['amount']) ? $this->post['amount'] : 0);
            $to_currency = (isset($this->post['to_currency']) ? $this->post['to_currency'] : '');
            $from_currency = (isset($this->post['from_currency']) ? $this->post['from_currency'] : '');
            $converted_amount = $this->Currencies->convert($amount, $from_currency, $to_currency, $this->company_id);

            $this->setMessage(
                'message',
                Language::_(
                    'AdminTools.!success.currency_converted',
                    true,
                    $this->Currencies->toCurrency($amount, $from_currency, $this->company_id, true, true, true),
                    $this->Currencies->toCurrency($converted_amount, $to_currency, $this->company_id, true, true, true)
                )
            );
        }

        $this->set(
            'currencies',
            $this->Form->collapseObjectArray($this->Currencies->getAll($this->company_id), 'code', 'code')
        );
        $this->set('vars', $vars);
    }

    /**
     * Displays a list of utilities
     */
    public function utilities()
    {
        if (!isset($this->Record)) {
            Loader::loadComponents($this, ['Record']);
        }

        // Load database info from the config
        $database_info = Configure::get('Blesta.database_info');

        // Fetch non-utf8mb4 tables
        $non_utf8mb4_tables = $this->Record->select(
                ["concat('ALTER TABLE `', TABLE_SCHEMA, '`.`', table_name, '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;')" => 'query'],
                false
            )->
            from('information_schema.tables')->
            where('TABLE_SCHEMA', '=', $database_info['database'])->
            where('TABLE_COLLATION', '!=', 'utf8mb4_unicode_ci')->
            fetchAll();

        // Fetch non-utf8mb4 columns
        $select_string = "concat(
            'ALTER TABLE `',
            columns.TABLE_SCHEMA,
            '`.`',
            columns.table_name,
            '` MODIFY `',
            columns.column_name,
            '` ',
            columns.data_type,
            IF(columns.data_type NOT LIKE '%text%', '(', ''),
            IF(columns.data_type NOT LIKE '%text%', columns.CHARACTER_MAXIMUM_LENGTH, ''),
            IF(columns.data_type NOT LIKE '%text%', ')', ''),
            ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
        )";
        $non_utf8mb4_columns = $this->Record->select([$select_string => 'query'], false)->
            from(['information_schema.columns' => 'columns'])->
            where('TABLE_SCHEMA', '=', $database_info['database'])->
            where('COLLATION_NAME', '!=', 'utf8mb4_unicode_ci')->
            where('DATA_TYPE', '!=', 'enum')->
            fetchAll();

        if (isset($this->post['update_to_utf8mb4'])) {
            // Update the collation for the database
            $this->Record->query(
                'ALTER DATABASE `' . $database_info['database'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
            );
            foreach ($non_utf8mb4_tables as $table) {
                $this->Record->query($table->query);
            }
            foreach ($non_utf8mb4_columns as $column) {
                $this->Record->query($column->query);
            }

            try {
                // Replace charset query in the config
                $file_config = file_get_contents(CONFIGDIR . 'blesta.php');
                $updated_file_config = str_replace('SET NAMES \'utf8\'', 'SET NAMES \'utf8mb4\'', $file_config);
                file_put_contents(CONFIGDIR . 'blesta.php', $updated_file_config);
            } catch (Throwable $e) {
                // Do nothing
            }

            // Set success message and redirect
            $this->flashMessage('success', Language::_('AdminTools.!success.collation_updated'));
            $this->redirect($this->base_uri . 'tools/utilities/');
        }


        // Check if the MySQL/MariaDB version meets the minimum system requirements
        $pdo = $this->Record->connect();
        $server = (object) $pdo->query("SHOW VARIABLES like '%version%'")->fetchAll(PDO::FETCH_KEY_PAIR);

        $utf8mb4_requirements_met = true;
        if (
            (str_contains($server->version_comment, 'MySQL')
                && version_compare($server->version, '5.7.7', '<')
            )
            || (str_contains($server->version_comment, 'MariaDB')
                && version_compare($server->version, '10.2.2', '<')
            )
        ) {
            $utf8mb4_requirements_met = false;
        }


        $config_dbinfo = Configure::get('Database.profile');
        $config_charset_mb4 = is_array($config_dbinfo)
            && isset($config_dbinfo['charset_query'])
            && strpos($config_dbinfo['charset_query'], 'utf8mb4');

        $this->set('all_tables_utf8mb4', empty($non_utf8mb4_tables) && empty($non_utf8mb4_columns));
        $this->set('utf8mb4_requirements_met', $utf8mb4_requirements_met);
        $this->set('config_charset_mb4', $config_charset_mb4);
    }

    /**
     * Displays a list of renewing services
     */
    public function renewals()
    {
        $this->uses(['Services']);
        $page = (isset($this->get[0]) ? (int) $this->get[0] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_renews');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('services', $this->Services->getRenewablePaidList(true, $page, [$sort => $order]));

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $this->Services->getRenewablePaidCount(true),
                'uri' => $this->base_uri . 'tools/renewals/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));
    }

    /**
     * Update service max attempts
     */
    public function changeMaxAttempts()
    {
        if (!$this->isAjax() && empty($this->post)) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $this->uses(['Services']);
        $this->components(['Record']);

        // Fetch the service
        if (!isset($this->get[0])
            || !($service = $this->Services->get($this->get[0]))
        ) {
            $this->redirect($this->base_uri . 'tools/renewals/');
        }

        if (!empty($this->post)) {
            $service_renewal = $this->Record->select('service_invoices.maximum_attempts')->
                where('service_invoices.service_id', '=', $service->id)->
                update('service_invoices', ['maximum_attempts' => $this->post['max_attempts']]);
            $this->flashMessage('message', Language::_('AdminTools.!success.max_updated', true));
            $this->redirect($this->base_uri . 'tools/renewals/');
        }
        $service_renewal = $this->Record->select('service_invoices.maximum_attempts')->
            from('service_invoices')->
            where('service_invoices.service_id', '=', $service->id)->
            fetch();
        $service->maximum_attempts = $service_renewal->maximum_attempts ?? 0;

        echo $this->partial('admin_tools_change_max_attempts', ['service' => $service]);
        return false;
    }

    /**
     * Remove service from renewal queue
     */
    public function dequeue()
    {
        $this->uses(['Services']);
        $this->components(['Record']);

        // Fetch the service
        if (!isset($this->get[0])
            || !($service = $this->Services->get($this->get[0]))
        ) {
            $this->redirect($this->base_uri . 'tools/renewals/');
        }

        $this->Record->from('service_invoices')->
            where('service_invoices.service_id', '=', $service->id)->
            delete();

        // Success
        $this->flashMessage('message', Language::_('AdminTools.!success.dequeue', true));
        $this->redirect($this->base_uri . 'tools/renewals/');
        return false;
    }
}
