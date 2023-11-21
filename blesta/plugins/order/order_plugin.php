<?php
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * Order System plugin handler
 *
 * @package blesta
 * @subpackage blesta.plugins.order
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderPlugin extends Plugin
{
    /**
     * Construct
     */
    public function __construct()
    {
        Language::loadLang('order_plugin', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load components required by this plugin
        Loader::loadComponents($this, ['Input', 'Record']);

        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
    }

    /**
     * Performs any necessary bootstraping actions
     *
     * @param int $plugin_id The ID of the plugin being installed
     */
    public function install($plugin_id)
    {
        Loader::loadModels($this, ['CronTasks', 'Emails', 'EmailGroups', 'Languages']);
        Configure::load('order', dirname(__FILE__) . DS . 'config' . DS);

        try {
            // order_forms
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('label', ['type' => 'varchar', 'size' => 32])->
                setField('name', ['type' => 'varchar', 'size' => 128])->
                setField('description', ['type' => 'text'])->
                setField('template', ['type' => 'varchar', 'size' => 64])->
                setField('template_style', ['type' => 'varchar', 'size' => 64])->
                setField('type', ['type' => 'varchar', 'size' => 64])->
                setField('client_group_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('manual_review', ['type' => 'tinyint', 'size' => 1, 'default' => 0])->
                setField('allow_coupons', ['type' => 'tinyint', 'size' => 1, 'default' => 0])->
                setField('require_ssl', ['type' => 'tinyint', 'size' => 1, 'default' => 0])->
                setField('require_captcha', ['type' => 'tinyint', 'size' => 1, 'default' => 0])->
                setField('require_tos', ['type' => 'tinyint', 'size' => 1, 'default' => 0])->
                setField('tos_url', ['type' => 'varchar', 'size' => 255, 'is_null' => true, 'default' => null])->
                setField('abandoned_cart_first', ['type' => 'smallint', 'size' => 5, 'is_null' => true, 'default' => null])->
                setField('abandoned_cart_second', ['type' => 'smallint', 'size' => 5, 'is_null' => true, 'default' => null])->
                setField('abandoned_cart_third', ['type' => 'smallint', 'size' => 5, 'is_null' => true, 'default' => null])->
                setField('abandoned_cart_cancellation', ['type' => 'smallint', 'size' => 5, 'is_null' => true, 'default' => null])->
                setField('inactive_after_cancellation', ['type' => 'tinyint', 'size' => 1, 'default' => 0])->
                setField('status', ['type' => 'enum', 'size' => "'active','inactive'", 'default' => 'active'])->
                setField(
                    'visibility',
                    ['type' => 'enum', 'size' => "'public','shared','client'", 'default' => 'shared']
                )->
                setField('date_added', ['type' => 'datetime'])->
                setField('order', ['type' => 'smallint', 'size' => 5, 'default' => 0])->
                setKey(['id'], 'primary')->
                setKey(['label', 'company_id'], 'unique')->
                setKey(['status'], 'index')->
                setKey(['company_id'], 'index')->
                create('order_forms', true);

            // order_form_groups
            $this->Record->
                setField('order_form_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('package_group_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField(
                    'order',
                    ['type' => 'smallint', 'size' => 5, 'unsigned' => true, 'is_null' => false, 'default' => 0]
                )->
                setKey(['order_form_id', 'package_group_id'], 'primary')->
                create('order_form_groups', true);

            // order_form_meta
            $this->Record->
                setField('order_form_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('key', ['type' => 'varchar', 'size' => 32])->
                setField('value', ['type' => 'text'])->
                setKey(['order_form_id', 'key'], 'primary')->
                create('order_form_meta', true);

            // order_form_currencies
            $this->Record->
                setField('order_form_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('currency', ['type' => 'char', 'size' => 3])->
                setKey(['order_form_id', 'currency'], 'primary')->
                create('order_form_currencies', true);

            // order_form_gateways
            $this->Record->
                setField('order_form_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('gateway_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setKey(['order_form_id', 'gateway_id'], 'primary')->
                create('order_form_gateways', true);

            // order_staff_settings
            $this->Record->
                setField('staff_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('key', ['type' => 'varchar', 'size' => 32])->
                setField('value', ['type' => 'text'])->
                setKey(['staff_id', 'company_id', 'key'], 'primary')->
                create('order_staff_settings', true);

            // order_settings
            $this->Record->
                setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('key', ['type' => 'varchar', 'size' => 32])->
                setField('value', ['type' => 'text'])->
                setField('encrypted', ['type' => 'tinyint', 'size' => 1, 'default' => 0])->
                setKey(['key', 'company_id'], 'primary')->
                create('order_settings', true);

            // orders
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('order_number', ['type' => 'varchar', 'size' => 16])->
                setField('order_form_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('invoice_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('fraud_report', ['type' => 'text', 'is_null' => true, 'default' => null])->
                setField(
                    'fraud_status',
                    ['type' => 'enum', 'size' => "'allow','review','reject'", 'is_null' => true, 'default' => null]
                )->
                setField(
                    'status',
                    ['type' => 'enum', 'size' => "'pending','accepted','fraud','canceled'", 'default' => 'pending']
                )->
                setField(
                    'abandoned_notice',
                    ['type' => 'enum', 'size' => "'unsent','first','second','third','none'", 'default' => 'none']
                )->
                setField(
                    'ip_address',
                    ['type' => 'varchar', 'size' => 45, 'is_null' => true, 'default' => null]
                )->
                setField('date_added', ['type' => 'datetime'])->
                setKey(['id'], 'primary')->
                setKey(['order_number'], 'unique')->
                setKey(['order_form_id'], 'index')->
                setKey(['invoice_id'], 'index')->
                setKey(['status'], 'index')->
                create('orders', true);

            // order_services
            $this->Record->
                setField('order_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('service_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setKey(['order_id', 'service_id'], 'primary')->
                create('order_services', true);

            // Create affiliate database tables
            $this->createAffiliateTables();
        } catch (Exception $e) {
            // Error adding... no permission?
            $this->Input->setErrors(['db' => ['create' => $e->getMessage()]]);
            return;
        }

        // Add cron tasks
        $this->addCronTasks($this->getCronTasks());

        // Fetch all currently-installed languages for this company, for which email templates should be created for
        $languages = $this->Languages->getAll(Configure::get('Blesta.company_id'));

        // Add all email templates
        $emails = Configure::get('Order.install.emails');
        foreach ($emails as $email) {
            $group = $this->EmailGroups->getByAction($email['action']);
            if ($group) {
                $group_id = $group->id;
            } else {
                $group_id = $this->EmailGroups->add([
                    'action' => $email['action'],
                    'type' => $email['type'],
                    'plugin_dir' => $email['plugin_dir'],
                    'tags' => $email['tags']
                ]);
            }

            // Set from hostname to use that which is configured for the company
            if (isset(Configure::get('Blesta.company')->hostname)) {
                $email['from'] = str_replace(
                    '@mydomain.com',
                    '@' . Configure::get('Blesta.company')->hostname,
                    $email['from']
                );
            }

            // Add the email template for each language
            foreach ($languages as $language) {
                $this->Emails->add([
                    'email_group_id' => $group_id,
                    'company_id' => Configure::get('Blesta.company_id'),
                    'lang' => $language->code,
                    'from' => $email['from'],
                    'from_name' => $email['from_name'],
                    'subject' => $email['subject'],
                    'text' => $email['text'],
                    'html' => $email['html']
                ]);
            }
        }

        // Add initial affiliate company settings
        $this->populateAffiliateCompanySettings(Configure::get('Blesta.company_id'));
    }

    /**
     * Performs migration of data from $current_version (the current installed version)
     * to the given file set version
     *
     * @param string $current_version The current installed version of this plugin
     * @param int $plugin_id The ID of the plugin being upgraded
     */
    public function upgrade($current_version, $plugin_id)
    {
        Configure::load('order', dirname(__FILE__) . DS . 'config' . DS);

        // Upgrade if possible
        if (version_compare($this->getVersion(), $current_version, '>')) {
            // Handle the upgrade, set errors using $this->Input->setErrors() if any errors encountered
            if (version_compare($current_version, '1.1.0', '<')) {
                $this->Record->
                    setField('require_captcha', ['type' => 'tinyint', 'size' => 1, 'default' => 0])->
                    alter('order_forms');
                $this->Record->
                    setField(
                        'fraud_status',
                        ['type' => 'enum', 'size' => "'allow','review','reject'", 'is_null' => true, 'default' => null]
                    )->
                    alter('orders');
            }

            // Upgrade to 1.1.7
            if (version_compare($current_version, '1.1.7', '<')) {
                $this->updateEmailTemplates();
            }
            // Upgrade to 2.0.0
            if (version_compare($current_version, '2.0.0', '<')) {
                $this->Record->query(
                    "ALTER TABLE `orders` CHANGE `status` `status`
                    ENUM('pending', 'accepted', 'fraud', 'canceled') NOT NULL DEFAULT 'pending'"
                );
                $this->Record->query(
                    'ALTER TABLE `order_forms` ADD `template_style` VARCHAR( 64 ) NOT NULL AFTER `template`'
                );
                $this->Record->update('order_forms', ['template_style' => 'default']);
            }
            // Update to 2.2.2
            if (version_compare($current_version, '2.2.2', '<')) {
                // Convert default order form label to ID
                $this->Record->
                    from('order_forms')->
                    set('order_settings.value', 'order_forms.id', false)->
                    where('order_settings.key', '=', 'default_form')->
                    where('order_settings.value', '=', 'order_forms.label', false)->
                    where('order_settings.company_id', '=', 'order_forms.company_id', false)->
                    update('order_settings');
            }

            // Update to 2.10.0
            if (version_compare($current_version, '2.10.0', '<')) {
                Loader::loadModels($this, ['Emails', 'EmailGroups', 'Languages']);
                $languages = $this->Languages->getAll(Configure::get('Blesta.company_id'));

                // Add IP address to order
                $this->Record->query(
                    'ALTER TABLE `orders` ADD `ip_address` VARCHAR(39) NULL DEFAULT NULL AFTER `status`'
                )->closeCursor();

                // Update email templates to include the IP address
                $emails = Configure::get('Order.install.emails');
                foreach ($emails as $email) {
                    // Only update the order received templates
                    if (!in_array($email['action'], ['Order.received', 'Order.received_mobile'])) {
                        continue;
                    }

                    // Update each email template in this language
                    foreach ($languages as $language) {
                        $template = $this->Emails->getByType(
                            Configure::get('Blesta.company_id'),
                            $email['action'],
                            $language->code
                        );

                        // Missing template
                        if (!$template) {
                            continue;
                        }

                        $vars = [
                            'text' => str_replace(
                                'Amount: {invoice.total} {order.currency}{% if order.fraud_status !="" %}',
                                "Amount: {invoice.total} {order.currency}\n"
                                . "IP Address: {order.ip_address}{% if order.fraud_status !=\"\" %}",
                                $template->text
                            ),
                            'html' => str_replace(
                                'Amount: {invoice.total} {order.currency}{% if order.fraud_status !="" %}',
                                "Amount: {invoice.total} {order.currency}<br />\n"
                                . "IP Address: {order.ip_address}{% if order.fraud_status !=\"\" %}",
                                $template->html
                            )
                        ];

                        // Update the email template
                        if ($template->text != $vars['text'] || $template->html != $vars['html']) {
                            $this->Record->where('id', '=', $template->id)
                                ->update('emails', $vars, ['text', 'html']);
                        }
                    }
                }

                // Add visibility
                $this->Record->query(
                    "ALTER TABLE `order_forms` ADD `visibility`
                    ENUM( 'public', 'shared', 'client' ) NOT NULL
                    DEFAULT 'shared' AFTER `status` "
                );

                // Add description
                $this->Record->query(
                    "ALTER TABLE `order_forms` ADD `description`
                    TEXT NOT NULL AFTER `name` "
                );
            }

            // Update to 2.11.2
            if (version_compare($current_version, '2.11.2', '<')) {
                // Update all order records to JSON-encode the fraud report. This replaces previous serialization
                $this->upgrade2_11_2();
            }

            // Update to 2.13.0
            if (version_compare($current_version, '2.13.0', '<')) {
                // Add order form package group order
                $this->Record->query(
                    "ALTER TABLE `order_form_groups` ADD `order`
                    SMALLINT(5) NOT NULL DEFAULT 0 AFTER `package_group_id`"
                );
            }

            // Update to 2.22.0
            if (version_compare($current_version, '2.22.0', '<')) {
                Loader::loadComponents($this, ['Acl', 'Record']);

                // Add setting for whether to run fraud checks before or after client validation
                $company_ids = $this->Record->select('company_id')->
                    from('order_settings')->
                    group('company_id')->
                    fetchAll();
                foreach ($company_ids as $company_id) {
                    $this->Record->insert(
                        'order_settings',
                        ['company_id' => $company_id->company_id, 'key' => 'antifraud_after_validate', 'value' => 'true']
                    );
                }
                // Add IP address to order
                $this->Record->query(
                    'ALTER TABLE `orders` CHANGE `ip_address` `ip_address` VARCHAR(45) NULL DEFAULT NULL'
                )->closeCursor();

                // Add access to all staff members for the order widget
                $staff_groups = $this->Record->select(['staff_groups.id'])->
                    from('staff_groups')->
                    innerJoin('plugins', 'plugins.company_id', '=', 'staff_groups.company_id', false)->
                    where('plugins.dir', '=', 'order')->
                    group('staff_groups.id')->
                    fetchAll();
                foreach ($staff_groups as $staff_group) {
                    $this->Acl->deny('staff_group_' . $staff_group->id, 'order.admin_main', 'dashboard');
                }
            }

            // Update to 2.23.0
            if (version_compare($current_version, '2.23.0', '<')) {
                Loader::loadModels($this, ['Companies']);

                // Migrate human verification settings to the core settings
                $companies = $this->Companies->getAll();

                foreach ($companies as $company) {
                    $settings = [];
                    $order_settings = $this->Record->select(['key', 'value'])
                        ->from('order_settings')
                        ->where('company_id', '=', $company->id)
                        ->fetchAll();

                    foreach ($order_settings as $setting) {
                        $settings[$setting->key] = $setting->value;
                    }

                    // Update core settings
                    $fields = ['captcha', 'recaptcha_pub_key', 'recaptcha_shared_key'];
                    $this->Companies->setSettings($company->id, $settings, $fields);

                    // Remove old order settings
                    foreach ($fields as $field) {
                        $this->Record->from('order_settings')
                            ->where('key', '=', $field)
                            ->where('company_id', '=', $company->id)
                            ->delete();
                    }
                }
            }

            // Upgrade to 2.24.0
            if (version_compare($current_version, '2.24.0', '<')) {
                Loader::loadComponents($this, ['Acl', 'Record']);

                // Add affiliate email templates
                $this->updateEmailTemplates();

                // Add affiliate cron tasks
                $this->addCronTasks($this->getCronTasks());

                // Create affiliate database tables
                $this->createAffiliateTables();

                // Add initial affiliate company settings
                $this->populateAffiliateCompanySettings();

                // Allow staff groups for new permissions
                $permissions = $this->getPermissions();
                $staff_groups = $this->Record->select(['staff_groups.id'])
                    ->from('staff_groups')
                    ->innerJoin('plugins', 'plugins.company_id', '=', 'staff_groups.company_id', false)
                    ->where('plugins.dir', '=', 'order')
                    ->group('staff_groups.id')
                    ->fetchAll();

                foreach ($staff_groups as $staff_group) {
                    foreach ($permissions as $permission) {
                        if ($permission['group_alias'] == 'order.admin_affiliates') {
                            $this->Acl->allow(
                                'staff_group_' . $staff_group->id,
                                $permission['alias'],
                                $permission['action']
                            );
                        }
                    }
                }
            }

            // Upgrade to 2.25.0
            if (version_compare($current_version, '2.25.0', '<')) {
                Loader::loadComponents($this, ['Record']);

                $company_ids = $this->Record->select('company_id')->
                    from('order_settings')->
                    group('company_id')->
                    fetchAll();

                foreach ($company_ids as $company_id) {
                    $this->Record->insert(
                        'order_affiliate_company_settings',
                        [
                            'company_id' => $company_id->company_id,
                            'key' => 'excluded_packages',
                            'value' => serialize([])
                        ]
                    );
                }
            }

            // Upgrade to 2.26.0
            if (version_compare($current_version, '2.26.0', '<')) {
                Loader::loadComponents($this, ['Record']);

                $email_groups = $this->Record->
                    select()->
                    from('email_groups')->
                    where('plugin_dir', '=', 'order')->
                    where('action', 'like', '%Order.received%')->
                    fetchAll();

                foreach ($email_groups as $email_group) {
                    $this->Record->where('id', '=', $email_group->id)->
                        update('email_groups', ['tags' => $email_group->tags . ',{client}']);
                }

                $message_groups = $this->Record->
                    select()->
                    from('message_groups')->
                    where('plugin_dir', '=', 'order')->
                    where('action', '=', 'Order.received_staff')->
                    fetchAll();

                foreach ($message_groups as $message_group) {
                    $this->Record->where('id', '=', $message_group->id)->
                        update('message_groups', ['tags' => $message_group->tags . ',{client}']);
                }

                // Add order form order
                $this->Record->query(
                    "ALTER TABLE `order_forms` ADD `order`
                    SMALLINT(5) NOT NULL DEFAULT 0 AFTER `date_added`"
                );

                // Add 'hold_unverified_orders' setting, if not exists
                Loader::loadModels($this, ['Order.OrderSettings']);

                $company_ids = $this->Record->select('company_id')->
                    from('order_settings')->
                    group('company_id')->
                    fetchAll();

                foreach ($company_ids as $company_id) {
                    $hold_unverified_orders = $this->OrderSettings->getSetting(
                        $company_id->company_id,
                        'hold_unverified_orders'
                    );

                    if (!isset($hold_unverified_orders->value)) {
                        $this->OrderSettings->setSetting($company_id->company_id, 'hold_unverified_orders', '0');
                    }
                }
            }

            // Upgrade to 2.31.0
            if (version_compare($current_version, '2.31.0', '<')) {
                // Add abandoned_notice column to orders table
                $this->Record->query(
                    "ALTER TABLE `orders` ADD `abandoned_notice` enum('unsent','first','second','third','none') COLLATE utf8_unicode_ci
                    NOT NULL DEFAULT 'none' AFTER `status`;"
                );

                // Add additional columns to order_forms table
                $this->Record->query(
                    "ALTER TABLE `order_forms` ADD `abandoned_cart_first`
                    SMALLINT(5) NULL DEFAULT NULL AFTER `tos_url`"
                );
                $this->Record->query(
                    "ALTER TABLE `order_forms` ADD `abandoned_cart_second`
                    SMALLINT(5) NULL DEFAULT NULL AFTER `abandoned_cart_first`"
                );
                $this->Record->query(
                    "ALTER TABLE `order_forms` ADD `abandoned_cart_third`
                    SMALLINT(5) NULL DEFAULT NULL AFTER `abandoned_cart_second`"
                );
                $this->Record->query(
                    "ALTER TABLE `order_forms` ADD `abandoned_cart_cancellation`
                    SMALLINT(5) NULL DEFAULT NULL AFTER `abandoned_cart_third`"
                );
                $this->Record->query(
                    "ALTER TABLE `order_forms` ADD `inactive_after_cancellation`
                    TINYINT(1) NOT NULL DEFAULT 0 AFTER `abandoned_cart_cancellation`"
                );

                // Update email templates to include abandoned notices
                $this->updateEmailTemplates();

                // Add cron tasks
                $this->addCronTasks($this->getCronTasks());
            }

            // Update to 2.35.0
            if (version_compare($current_version, '2.35.0', '<')) {
                Loader::loadComponents($this, ['Acl', 'Record']);

                // Allow staff groups for new permissions
                $permissions = $this->getPermissions();
                $staff_groups = $this->Record->select(['staff_groups.id'])
                    ->from('staff_groups')
                    ->innerJoin('plugins', 'plugins.company_id', '=', 'staff_groups.company_id', false)
                    ->where('plugins.dir', '=', 'order')
                    ->group('staff_groups.id')
                    ->fetchAll();

                foreach ($staff_groups as $staff_group) {
                    foreach ($permissions as $permission) {
                        if ($permission['group_alias'] == 'order.admin_main') {
                            $this->Acl->allow(
                                'staff_group_' . $staff_group->id,
                                $permission['alias'],
                                $permission['action']
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Upgrades the Order plugin to v2.11.2
     */
    private function upgrade2_11_2()
    {
        $start = 0;
        $batch_size = 50;
        $i = 1;

        while (true) {
            // Fetch orders until we have no more to update
            $orders = $this->getOrders($start, $batch_size);

            if (empty($orders)) {
                break;
            }

            // Update all orders to JSON-encode the fraud report
            foreach ($orders as $order) {
                if (empty($order->fraud_report) || false === ($report = unserialize($order->fraud_report))) {
                    continue;
                }

                $this->Record->where('id', '=', $order->id)
                    ->update('orders', ['fraud_report' => json_encode($report)]);
            }

            // Set the LIMIT for the next records
            $start = $batch_size * $i;
            $i++;
        }
    }

    /**
     * Installs any templates in the config file that don't currently exist in the database
     */
    private function updateEmailTemplates()
    {
        Loader::loadModels($this, ['Emails', 'EmailGroups', 'Languages']);

        // Add emails missing in additional languages that have been installed before the plugin was installed
        $languages = $this->Languages->getAll(Configure::get('Blesta.company_id'));

        // Add all email templates in other languages IFF they do not already exist
        $emails = Configure::get('Order.install.emails');
        foreach ($emails as $email) {
            $group = $this->EmailGroups->getByAction($email['action']);
            if ($group) {
                $group_id = $group->id;
            } else {
                $group_id = $this->EmailGroups->add([
                    'action' => $email['action'],
                    'type' => $email['type'],
                    'plugin_dir' => $email['plugin_dir'],
                    'tags' => $email['tags']
                ]);
            }

            // Set from hostname to use that which is configured for the company
            if (isset(Configure::get('Blesta.company')->hostname)) {
                $email['from'] = str_replace(
                    '@mydomain.com',
                    '@' . Configure::get('Blesta.company')->hostname,
                    $email['from']
                );
            }

            // Add the email template for each language
            foreach ($languages as $language) {
                // Check if this email already exists for this language
                $template = $this->Emails->getByType(
                    Configure::get('Blesta.company_id'),
                    $email['action'],
                    $language->code
                );

                // Template already exists for this language
                if ($template !== false) {
                    continue;
                }

                // Add the missing email for this language
                $this->Emails->add([
                    'email_group_id' => $group_id,
                    'company_id' => Configure::get('Blesta.company_id'),
                    'lang' => $language->code,
                    'from' => $email['from'],
                    'from_name' => $email['from_name'],
                    'subject' => $email['subject'],
                    'text' => $email['text'],
                    'html' => $email['html']
                ]);
            }
        }
    }

    /**
     * Creates all tables for the affiliate system
     */
    private function createAffiliateTables()
    {
        // order_affiliates
        $this->Record->
            setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
            setField('client_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
            setField('code', ['type' => 'varchar', 'size' => 16])->
            setField('status', ['type' => 'enum', 'size' => "'active','inactive'", 'default' => 'active'])->
            setField('date_added', ['type' => 'datetime'])->
            setField('date_updated', ['type' => 'datetime', 'is_null' => true, 'default' => null])->
            setKey(['id'], 'primary')->
            setKey(['client_id'], 'unique')->
            setKey(['code'], 'unique')->
            setKey(['status', 'client_id'], 'index')->
            create('order_affiliates', true);

        // order_affiliate_settings
        $this->Record->
            setField('affiliate_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
            setField('key', ['type' => 'varchar', 'size' => 255])->
            setField('value', ['type' => 'text'])->
            setKey(['affiliate_id', 'key'], 'primary')->
            create('order_affiliate_settings', true);

        // order_affiliate_company_settings
        $this->Record->
            setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
            setField('key', ['type' => 'varchar', 'size' => 255])->
            setField('value', ['type' => 'text'])->
            setKey(['company_id', 'key'], 'primary')->
            create('order_affiliate_company_settings', true);

        // order_affiliate_referrals
        $this->Record->
            setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
            setField('affiliate_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
            setField('order_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
            setField('name', ['type' => 'varchar', 'size' => 255, 'is_null' => true, 'default' => null])->
            setField(
                'status',
                ['type' => 'enum', 'size' => "'pending','mature','canceled'", 'default' => 'pending']
            )->
            setField('amount', ['type' => 'decimal', 'size' => '19,4', 'default' => '0.0000'])->
            setField('currency', ['type' => 'char', 'size' => 3, 'default' => 'USD'])->
            setField('commission', ['type' => 'decimal', 'size' => '19,4', 'default' => '0.0000'])->
            setField('date_added', ['type' => 'datetime'])->
            setField('date_updated', ['type' => 'datetime', 'is_null' => true, 'default' => null])->
            setKey(['id'], 'primary')->
            setKey(['affiliate_id', 'date_added'], 'index')->
            setKey(['order_id'], 'index')->
            setKey(['status'], 'index')->
            create('order_affiliate_referrals', true);

        // order_affiliate_payouts
        $this->Record->
            setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
            setField('affiliate_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
            setField(
                'payment_method_id',
                ['type' => 'int', 'size' => 10, 'unsigned' => true, 'is_null' => true, 'default' => null]
            )->
            setField(
                'status',
                ['type' => 'enum', 'size' => "'pending','approved','declined'", 'default' => 'pending']
            )->
            setField('requested_amount', ['type' => 'decimal', 'size' => '19,4', 'default' => '0.0000'])->
            setField('requested_currency', ['type' => 'char', 'size' => 3, 'default' => 'USD'])->
            setField('paid_amount', ['type' => 'decimal', 'size' => '19,4', 'is_null' => true, 'default' => null])->
            setField('paid_currency', ['type' => 'char', 'size' => 3, 'is_null' => true, 'default' => null])->
            setField('date_requested', ['type' => 'datetime'])->
            setKey(['id'], 'primary')->
            setKey(['affiliate_id', 'status'], 'index')->
            setKey(['payment_method_id'], 'index')->
            create('order_affiliate_payouts', true);

        // order_affiliate_payment_methods
        $this->Record->
            setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
            setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
            setKey(['id'], 'primary')->
            setKey(['company_id'], 'index')->
            create('order_affiliate_payment_methods', true);

        // order_affiliate_payment_method_names
        $this->Record->
            setField('payment_method_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
            setField('lang', ['type' => 'varchar', 'size' => 5])->
            setField('name', ['type' => 'varchar', 'size' => 255])->
            setKey(['payment_method_id', 'lang'], 'primary')->
            create('order_affiliate_payment_method_names', true);

        // order_affiliate_statistics
        $this->Record->
            setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
            setField('affiliate_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
            setField('visits', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0])->
            setField('sales', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0])->
            setField('date', ['type' => 'datetime'])->
            setKey(['id'], 'primary')->
            create('order_affiliate_statistics', true);
    }

    /**
     * Populates the order_affiliate_company_settings table with initial setting values
     *
     * @param int $company_id The company id where the affiliate company settings will be populated
     */
    private function populateAffiliateCompanySettings($company_id = null)
    {
        Loader::loadModels($this, ['PluginManager']);

        // Get companies that have the order plugin installed
        if (empty($company_id)) {
            $company_ids = $this->Record->select('company_id')
                ->from('order_settings')
                ->group('company_id')
                ->fetchAll();
        } else {
            $company_ids = [
                (object) ['company_id' => $company_id]
            ];
        }

        $settings = [
            ['key' => 'enabled', 'value' => 'false'],
            ['key' => 'cookie_tld', 'value' => '180'],
            ['key' => 'commission_type', 'value' => 'percentage'],
            ['key' => 'commission_amount', 'value' => '0'],
            ['key' => 'order_frequency', 'value' => 'first'],
            ['key' => 'order_recurring', 'value' => 'false'],
            ['key' => 'maturity_days', 'value' => '90'],
            ['key' => 'min_withdrawal_amount', 'value' => '10'],
            ['key' => 'max_withdrawal_amount', 'value' => '100'],
            ['key' => 'withdrawal_currency', 'value' => 'USD'],
            [
                'key' => 'signup_content',
                'value' => '<p>We pay commissions for every order placed using your custom
                    affiliate link by tracking visitors you refer to us using a cookie. The cookie will last up to 180
                    days following the initial visit, so you will get a commission for the referral even if they do not
                    sign up immediately. If you have any questions, please contact us, or sign up by clicking the button
                    below.</p>'
            ],
        ];

        // Add default setting values for each company
        foreach ($company_ids as $company_id) {
            foreach ($settings as $setting) {
                $this->Record->insert(
                    'order_affiliate_company_settings',
                    array_merge(['company_id' => $company_id->company_id], $setting)
                );
            }
        }
    }

    /**
     * Retrieves a set of orders
     *
     * @see OrderPlugin::upgrade2_11_2
     * @param int $limit_start The start record
     * @param int $size The number of records to retrieve
     * @return array An array of orders with a fraud report
     */
    private function getOrders($limit_start, $size)
    {
        return $this->Record->select(['id', 'fraud_report'])
            ->from('orders')
            ->where('fraud_report', '!=', null)
            ->order(['id' => 'ASC'])
            ->limit($size, $limit_start)
            ->fetchAll();
    }

    /**
     * Performs any necessary cleanup actions
     *
     * @param int $plugin_id The ID of the plugin being uninstalled
     * @param bool $last_instance True if $plugin_id is the last instance across
     *  all companies for this plugin, false otherwise
     */
    public function uninstall($plugin_id, $last_instance)
    {
        Loader::loadModels($this, ['CronTasks', 'EmailGroups', 'Emails']);
        Configure::load('order', dirname(__FILE__) . DS . 'config' . DS);

        $emails = Configure::get('Order.install.emails');

        // Remove emails and email groups as necessary
        foreach ($emails as $email) {
            // Fetch the email template created by this plugin
            $group = $this->EmailGroups->getByAction($email['action']);

            // Delete all emails templates belonging to this plugin's email group and company
            if ($group) {
                $this->Emails->deleteAll($group->id, Configure::get('Blesta.company_id'));

                if ($last_instance) {
                    $this->EmailGroups->delete($group->id);
                }
            }
        }

        // Remove affiliate settings for this company
        $this->Record->from('order_affiliate_company_settings')
            ->where('company_id', '=', Configure::get('Blesta.company_id'))
            ->delete();

        $cron_task_run = $this->CronTasks->getTaskRunByKey('accept_paid_orders', 'order', false, 'plugin');

        if ($last_instance) {
            try {
                $this->Record->drop('order_affiliate_company_settings');
                $this->Record->drop('order_affiliate_referrals');
                $this->Record->drop('order_affiliates');
                $this->Record->drop('order_affiliate_settings');
                $this->Record->drop('order_affiliate_payment_method_names');
                $this->Record->drop('order_affiliate_payment_methods');
                $this->Record->drop('order_affiliate_payouts');
                $this->Record->drop('order_affiliate_statistics');
                $this->Record->drop('order_forms');
                $this->Record->drop('order_form_groups');
                $this->Record->drop('order_form_meta');
                $this->Record->drop('order_form_currencies');
                $this->Record->drop('order_form_gateways');
                $this->Record->drop('order_staff_settings');
                $this->Record->drop('order_settings');
                $this->Record->drop('orders');
                $this->Record->drop('order_services');
            } catch (Exception $e) {
                // Error dropping... no permission?
                $this->Input->setErrors(['db' => ['create' => $e->getMessage()]]);
                return;
            }

            // Remove the cron tasks altogether
            $cron_task_keys = [
                'accept_paid_orders', 'affiliate_monthly_report',
                'mature_affiliate_referrals', 'process_abandoned_orders'
            ];
            foreach ($cron_task_keys as $cron_task_key) {
                $cron_task = $this->CronTasks->getByKey($cron_task_key, 'order', 'plugin');
                if ($cron_task) {
                    $this->CronTasks->deleteTask($cron_task->id, 'plugin', 'order');
                }
            }
        }

        // Remove individual task run
        if ($cron_task_run) {
            $this->CronTasks->deleteTaskRun($cron_task_run->task_run_id);
        }
    }

    /**
     * Retrieves the total number of pending and approved orders
     *
     * @param int $client_id The ID of the client assigned to the orders
     * @return int The total number of pending and approved orders
     */
    public function getOrdersCount($client_id)
    {
        Loader::loadModels($this, ['Order.OrderOrders']);

        return $this->OrderOrders->getListCount('accepted', ['client_id' => $client_id])
            + $this->OrderOrders->getListCount('pending', ['client_id' => $client_id]);
    }

    /**
     * Returns all actions to be configured for this widget (invoked after install()
     * or upgrade(), overwrites all existing actions)
     *
     * @return array A numerically indexed array containing:
     *  - action The action to register for
     *  - uri The URI to be invoked for the given action
     *  - name The name to represent the action (can be language definition)
     */
    public function getActions()
    {
        return [
            [
                'action' => 'widget_staff_home',
                'uri' => 'widget/order/admin_main/dashboard',
                'name' => 'OrderPlugin.admin_main.name',
                'enabled' => 0
            ],
            [
                'action' => 'widget_staff_billing',
                'uri' => 'widget/order/admin_main/',
                'name' => 'OrderPlugin.admin_main.name'
            ],
            [
                'action' => 'widget_staff_client',
                'uri' => 'plugin/order/admin_main/orders',
                'name' => 'OrderPlugin.admin_main.name'
            ],
            [
                'action' => 'nav_secondary_staff',
                'uri' => 'plugin/order/admin_forms/',
                'name' => 'OrderPlugin.admin_forms.name',
                'options' => ['parent' => 'packages/']
            ],
            [
                'action' => 'nav_primary_client',
                'uri' => 'order/',
                'name' => 'OrderPlugin.client.name',
                'options' => [
                    'base_uri' => 'public',
                    'sub' => [
                        [
                            'uri' => 'order/',
                            'name' => 'OrderPlugin.client.name',
                        ],
                        [
                            'uri' => 'order/orders/',
                            'name' => 'OrderPlugin.client_orders.name',
                        ]
                    ]
                ]
            ],
            [
                'action' => 'nav_secondary_staff',
                'uri' => 'plugin/order/admin_affiliates/',
                'name' => 'OrderPlugin.admin_affiliates.name',
                'options' => ['parent' => 'clients/']
            ],
            [
                'action' => 'nav_primary_client',
                'uri' => 'order/affiliates/',
                'name' => 'OrderPlugin.client_affiliates.name',
                'options' => ['base_uri' => 'public'],
                'enabled' => 0
            ],
            [
                'action' => 'action_staff_client',
                'uri' => 'plugin/order/admin_main/affiliates/',
                'name' => 'OrderPlugin.action_staff_client.affiliates',
                'options' => ['class' => 'users', 'icon' => 'fa-users'],
                'enabled' => 0
            ]
        ];
    }

    /**
     * Returns all cards to be configured for this plugin (invoked after install() or upgrade(),
     * overwrites all existing cards)
     *
     * @return array A numerically indexed array containing:
     *
     *  - level The level this card should be displayed on (client or staff) (optional, default client)
     *  - callback A method defined by the plugin class for calculating the value of the card or fetching a custom html
     *  - callback_type The callback type, 'value' to fetch the card value or
     *      'html' to fetch the custom html code (optional, default value)
     *  - background The background color in hexadecimal or path to the background image for this card (optional)
     *  - background_type The background type, 'color' to set a hexadecimal background or
     *      'image' to set an image background (optional, default color)
     *  - label A string or language key appearing under the value as a label
     *  - link The link to which the card will be pointed (optional)
     *  - enabled Whether this card appears on client profiles by default
     *      (1 to enable, 0 to disable) (optional, default 1)
     */
    public function getCards()
    {
        return [
            [
                'level' => 'client',
                'callback' => ['this', 'getOrdersCount'],
                'callback_type' => 'value',
                'text_color' => '#ebebeb',
                'background' => '#343a40',
                'background_type' => 'color',
                'label' => 'OrderPlugin.card_client.orders',
                'link' => '/order/orders/',
                'enabled' => 1
            ]
        ];
    }

    /**
     * Returns all permissions to be configured for this plugin (invoked after install(), upgrade(),
     *  and uninstall(), overwrites all existing permissions)
     *
     * @return array A numerically indexed array containing:
     *
     *  - group_alias The alias of the permission group this permission belongs to
     *  - name The name of this permission
     *  - alias The ACO alias for this permission (i.e. the Class name to apply to)
     *  - action The action this ACO may control (i.e. the Method name of the alias to control access for)
     */
    public function getPermissions()
    {
        return [
            [
                'group_alias' => 'admin_packages',
                'name' => Language::_('OrderPlugin.admin_forms.name', true),
                'alias' => 'order.admin_forms',
                'action' => '*'
            ],
            [
                'group_alias' => 'admin_billing',
                'name' => Language::_('OrderPlugin.admin_main.name', true),
                'alias' => 'order.admin_main',
                'action' => '*'
            ],
            [
                'group_alias' => 'admin_main',
                'name' => Language::_('OrderPlugin.admin_main.name', true),
                'alias' => 'order.admin_main',
                'action' => 'dashboard'
            ],
            [
                'group_alias' => 'order.admin_affiliates',
                'name' => Language::_('OrderPlugin.permission.admin_affiliates', true),
                'alias' => 'order.admin_affiliates',
                'action' => '*'
            ],
            [
                'group_alias' => 'order.admin_affiliates',
                'name' => Language::_('OrderPlugin.permission.admin_affiliates_add', true),
                'alias' => 'order.admin_affiliates',
                'action' => 'add'
            ],
            [
                'group_alias' => 'order.admin_affiliates',
                'name' => Language::_('OrderPlugin.permission.admin_affiliates_update', true),
                'alias' => 'order.admin_affiliates',
                'action' => 'update'
            ],
            [
                'group_alias' => 'order.admin_affiliates',
                'name' => Language::_('OrderPlugin.permission.admin_affiliates_activate', true),
                'alias' => 'order.admin_affiliates',
                'action' => 'activate'
            ],
            [
                'group_alias' => 'order.admin_affiliates',
                'name' => Language::_('OrderPlugin.permission.admin_affiliates_deactivate', true),
                'alias' => 'order.admin_affiliates',
                'action' => 'deactivate'
            ],
            [
                'group_alias' => 'order.admin_affiliates',
                'name' => Language::_('OrderPlugin.permission.admin_affiliates_settings', true),
                'alias' => 'order.admin_affiliates',
                'action' => 'settings'
            ],
            [
                'group_alias' => 'order.admin_affiliates',
                'name' => Language::_('OrderPlugin.permission.admin_payment_methods', true),
                'alias' => 'order.admin_payment_methods',
                'action' => '*'
            ],
            [
                'group_alias' => 'order.admin_affiliates',
                'name' => Language::_('OrderPlugin.permission.admin_payment_methods_add', true),
                'alias' => 'order.admin_payment_methods',
                'action' => 'add'
            ],
            [
                'group_alias' => 'order.admin_affiliates',
                'name' => Language::_('OrderPlugin.permission.admin_payment_methods_edit', true),
                'alias' => 'order.admin_payment_methods',
                'action' => 'edit'
            ],
            [
                'group_alias' => 'order.admin_affiliates',
                'name' => Language::_('OrderPlugin.permission.admin_payment_methods_delete', true),
                'alias' => 'order.admin_payment_methods',
                'action' => 'delete'
            ],
            [
                'group_alias' => 'order.admin_affiliates',
                'name' => Language::_('OrderPlugin.permission.admin_payouts', true),
                'alias' => 'order.admin_payouts',
                'action' => '*'
            ],
            [
                'group_alias' => 'order.admin_affiliates',
                'name' => Language::_('OrderPlugin.permission.admin_payouts_edit', true),
                'alias' => 'order.admin_payouts',
                'action' => 'edit'
            ],
            [
                'group_alias' => 'order.admin_affiliates',
                'name' => Language::_('OrderPlugin.permission.admin_payouts_approve', true),
                'alias' => 'order.admin_payouts',
                'action' => 'approve'
            ],
            [
                'group_alias' => 'order.admin_affiliates',
                'name' => Language::_('OrderPlugin.permission.admin_payouts_decline', true),
                'alias' => 'order.admin_payouts',
                'action' => 'decline'
            ],
            [
                'group_alias' => 'order.admin_main',
                'name' => Language::_('OrderPlugin.permission.admin_main_widget', true),
                'alias' => 'order.admin_main',
                'action' => 'orders'
            ]
        ];
    }

    /**
     * Returns all permission groups to be configured for this plugin (invoked after install(), upgrade(),
     *  and uninstall(), overwrites all existing permission groups)
     *
     * @return array A numerically indexed array containing:
     *
     *  - name The name of this permission group
     *  - level The level this permission group resides on (staff or client)
     *  - alias The ACO alias for this permission group (i.e. the Class name to apply to)
     */
    public function getPermissionGroups()
    {
        return [
            [
                'name' => Language::_('OrderPlugin.permission.admin_affiliates', true),
                'level' => 'staff',
                'alias' => 'order.admin_affiliates'
            ],
            [
                'name' => Language::_('OrderPlugin.permission.admin_main', true),
                'level' => 'staff',
                'alias' => 'order.admin_main'
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageTemplates()
    {
        // Get the details for the Order.received_mobile email
        Configure::load('order', dirname(__FILE__) . DS . 'config' . DS);
        $emails = Configure::get('Order.install.emails');
        $order_received_email = null;
        foreach ($emails as $email) {
            if ($email['action'] == 'Order.received_mobile') {
                $order_received_email = $email;
                break;
            }
        }

        return [
            [
                'action' => 'Order.received_staff',
                'type' => 'staff',
                'tags' => isset($order_received_email) ? $order_received_email['tags'] : '',
                'content' => ['sms' => (isset($order_received_email) ? $order_received_email['text'] : '')],
            ],
        ];
    }

    /**
     * Returns the search options to append to the list of staff search options
     *
     * @param EventInterface $event The event to process
     */
    public function getSearchOptions(EventInterface $event)
    {
        $params = $event->getParams();

        if (isset($params['options'])) {
            $params['options'] += [
                $params['base_uri'] . 'plugin/order/admin_main/search/'
                => Language::_('OrderPlugin.event_getsearchoptions.orders', true)
            ];
        }

        $event->setParams($params);
    }

    /**
     * Register a sale when an order placed by a referral is paid
     *
     * @param EventInterface $event The event to process
     */
    public function registerReferralSale(EventInterface $event)
    {
        Loader::loadModels($this, ['Order.OrderAffiliateStatistics']);
        Loader::loadComponents($this, ['Record']);

        $params = $event->getParams();
        $referral = $this->Record->select(['orders.invoice_id', 'order_affiliate_referrals.*'])
            ->from('orders')
            ->innerJoin('order_affiliate_referrals', 'order_affiliate_referrals.order_id', '=', 'orders.id', false)
            ->where('orders.invoice_id', '=', $params['invoice_id'])
            ->fetch();

        if (!empty($referral) && isset($referral->affiliate_id)) {
            $this->OrderAffiliateStatistics->registerSale($referral->affiliate_id);
        }
    }

    /**
     * Create a referral for renewing services
     *
     * @param EventInterface $event The event to process
     */
    public function createRenewalReferral(EventInterface $event)
    {
        Loader::loadModels(
            $this,
            [
                'Invoices',
                'Clients',
                'Services',
                'Order.OrderAffiliateSettings',
                'Order.OrderAffiliateCompanySettings',
                'Order.OrderAffiliateReferrals',
                'Order.OrderAffiliates'
            ]
        );
        Loader::loadComponents($this, ['Record']);
        Loader::loadHelpers($this, ['Form']);

        $params = $event->getParams();
        if (!$params['services_renew']) {
            return;
        }

        // Get a list of services from the invoice that were referred
        $referred_services = $this->Record->select([
                'services.id',
                'orders.id' => 'order_id',
                'invoices.currency',
                'order_affiliate_referrals.affiliate_id'
            ])
            ->from('services')
            ->innerJoin('invoice_lines', 'invoice_lines.service_id', '=', 'services.id', false)
            ->innerJoin('invoices', 'invoices.id', '=', 'invoice_lines.invoice_id', false)
            ->innerJoin('orders', 'orders.invoice_id', '=', 'invoices.id', false)
            ->innerJoin('order_affiliate_referrals', 'order_affiliate_referrals.order_id', '=', 'orders.id', false)
            ->where('services.id', 'in', $params['service_ids'])
            ->group(['services.id'])
            ->fetchAll();

        if (empty($referred_services)) {
            return;
        }

        // Get excluded packages
        $settings = $this->OrderAffiliateCompanySettings->getSetting(
            Configure::get('Blesta.company_id'),
            'excluded_packages'
        );
        $excluded_packages = isset($settings->value) ? (array)unserialize($settings->value) : [];

        $client = $this->Clients->get($params['client_id']);
        foreach ($referred_services as $referred_service) {
            // Determine whether the affiliate is set to receive referrals from recurring orders
            $affiliate_settings = $this->Form->collapseObjectArray(
                $this->OrderAffiliateSettings->getSettings($referred_service->affiliate_id),
                'value',
                'key'
            );
            if (!isset($affiliate_settings['order_recurring']) || $affiliate_settings['order_recurring'] !== 'true') {
                continue;
            }


            // Get a presenter for the created invoice
            $presenter = $this->Invoices->getPresenter($params['invoice_id']);
            $collection = $presenter->collection();

            // Gather only the items for referred service
            foreach ($collection as $item) {
                $lines = $item->meta();
                foreach ($lines as $line) {
                    // Remove items that are not for the referred service
                    $fields = $line->getFields();
                    if ($fields->line->service_id !== $referred_service->id) {
                        $collection->remove($item);
                        break;
                    }

                    // Remove items for services from excluded packages
                    $service = $this->Services->get($fields->line->service_id);
                    if (in_array($service->package_pricing->package_id, $excluded_packages)) {
                        $collection->remove($item);
                        break;
                    }
                }
            }
            $totals = $presenter->totals();

            // If the total amount of the order minus the excluded packages is
            // less or equal to 0, move to the next service
            if ($totals->total_after_discount <= 0) {
                continue;
            }

            // Add referral
            $referral = [
                'affiliate_id' => $referred_service->affiliate_id,
                'order_id' => $referred_service->order_id,
                'name' => $client->first_name . ' ' . $client->last_name,
                'amount' => $totals->total_after_discount,
                'currency' => $referred_service->currency,
                'commission' => ($affiliate_settings['commission_type'] == 'percentage')
                    ? $totals->total_after_discount * ($affiliate_settings['commission_amount'] / 100)
                    : $affiliate_settings['commission_amount']
            ];
            $this->OrderAffiliateReferrals->add($referral);
        }
    }

    /**
     * Create a referral for renewing services
     *
     * @param EventInterface $event The event to process
     */
    public function clearTldPricingCache(EventInterface $event)
    {
        try {
            Cache::emptyCache(Configure::get('Blesta.company_id') . DS . 'plugins' . DS . 'order' . DS);
        } catch (Throwable $e) {
            // Do nothing
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getEvents()
    {
        return [
            [
                'event' => 'Clients.delete',
                'callback' => ['this', 'deleteHangingOrders']
            ],
            [
                'event' => 'AppController.structure',
                'callback' => ['this', 'setEmbedCode']
            ],
            [
                'event' => 'Navigation.getSearchOptions',
                'callback' => ['this', 'getSearchOptions']
            ],
            [
                'event' => 'Invoices.setClosed',
                'callback' => ['this', 'registerReferralSale']
            ],
            [
                'event' => 'Invoices.createFromServices',
                'callback' => ['this', 'createRenewalReferral']
            ],
            [
                'event' => 'Domains.enable',
                'callback' => ['this', 'clearTldPricingCache']
            ],
            [
                'event' => 'Domains.disable',
                'callback' => ['this', 'clearTldPricingCache']
            ],
            [
                'event' => 'Domains.delete',
                'callback' => ['this', 'clearTldPricingCache']
            ],
            [
                'event' => 'Domains.updatePricingAfter',
                'callback' => ['this', 'clearTldPricingCache']
            ],
            [
                'event' => 'Domains.updateDomainsCompanySettingsAfter',
                'callback' => ['this', 'clearTldPricingCache']
            ]
        ];
    }

    /**
     * Deletes all order data that is no longer associated with a valid service or invoice
     *
     * @param EventInterface $event The event to process
     */
    public function deleteHangingOrders(EventInterface $event)
    {
        Loader::loadModels($this, ['Order.OrderOrders']);

        $this->OrderOrders->deleteHangingOrders();
    }

    /**
     * Sets the defined embed code into the footer for order pages
     *
     * @param EventInterface $event The event object representing the AppController.structure event
     */
    public function setEmbedCode(EventInterface $event)
    {
        // Determine the action we're on and that it's in our white-list for us to set the embed code to this page
        $actions = [
            'signup/index',
            'main/index',
            'main/packages',
            'forms/index',
            'config/index',
            'config/preconfig',
            'checkout/index',
            'checkout/complete',
            'cart/index'
        ];

        $params = $event->getParams();
        $plugin = (!empty($params['plugin']) ? strtolower($params['plugin']) : null);
        $action = strtolower($params['controller'] . '/' . ($params['action'] == '' ? 'index' : $params['action']));
        if ($plugin !== 'order' || $params['portal'] != 'client' || !in_array($action, $actions)) {
            return;
        }

        // Set anti fraud embed code
        $this->setAntiFraudCode($event);

        // Fetch the embed code
        Loader::loadModels($this, ['Order.OrderSettings']);
        $embed_code = $this->OrderSettings->getSetting(Configure::get('Blesta.company_id'), 'embed_code');

        // If the embed code is not set, return
        if (empty($embed_code) || empty($embed_code->value)) {
            return;
        }

        Loader::loadHelpers($this, ['CurrencyFormat']);
        $this->CurrencyFormat->setCompany(Configure::get('Blesta.company_id'));

        // Load the template parser and generate the output
        $parser = new H2o();
        $parser->addFilter('currency_format', [$this->CurrencyFormat, 'format']);
        $parser_options = ['autoescape' => false];

        $output = $parser->parseString($embed_code->value, $parser_options)
            ->render($this->getEmbedCodeData($action, (!empty($params['get']) ? (array)$params['get'] : [])));

        // Update the event to set the embed code to just before the end body tag
        $value = $event->getReturnValue();
        $value['body_end'][] = $output;
        $event->setReturnValue($value);
    }

    /**
     * Sets the anti fraud embed code into the footer for order pages
     *
     * @param EventInterface $event The event object representing the AppController.structure event
     */
    private function setAntiFraudCode(EventInterface $event)
    {
        // Fetch the anti fraud code
        Loader::loadModels($this, ['Order.OrderSettings']);
        Loader::loadComponents($this, ['Order.Antifraud']);
        Loader::loadHelpers($this, ['Form']);

        $order_settings = $this->OrderSettings->getSettings(Configure::get('Blesta.company_id'));
        $order_settings = $this->Form->collapseObjectArray($order_settings, 'value', 'key');

        // If the embed code is not set, return
        if (isset($order_settings['enable_js']) && $order_settings['enable_js'] == 'enable') {
            $antifraud = isset($order_settings['antifraud']) ? $order_settings['antifraud'] : '';

            try {
                $fraud_detect = $this->Antifraud->create($antifraud, [$order_settings]);


                if (method_exists($fraud_detect, 'getJavascript')) {
                    $value = $event->getReturnValue();
                    $value['body_end'][] = $fraud_detect->getJavascript();
                    $event->setReturnValue($value);
                } else {
                    return;
                }
            } catch (Exception $e) {
                return;
            }
        } else {
            return;
        }
    }

    /**
     * Retrieves the tag replacement data based on order information from the user's cart
     *
     * @param string The page controller/action being accessed
     * @param array $get The GET arguments
     * @return array An array of key/value pairs representing tag replacement data for embed codes
     */
    private function getEmbedCodeData($action, array $get)
    {
        Loader::loadComponents($this, ['Session']);

        // Load the SessionCart to retrieve the data tags to make available to the embed code
        // $get[0] is always presumed to be the order label referencing the cart session
        $cart_name = Configure::get('Blesta.company_id') . '-' . (empty($get[0]) ? '' : $get[0]);
        Loader::loadComponents($this, ['SessionCart' => [$cart_name, $this->Session]]);

        $data = [
            'order_page' => $action
        ];

        switch ($action) {
            case 'checkout/complete':
                // If checkout is complete, we have an order number at the 1st GET index
                Loader::loadModels($this, ['Order.OrderOrders', 'Invoices']);

                if (isset($get[1]) && ($order = $this->OrderOrders->getByNumber($get[1]))) {
                    $data['order'] = $order;
                    $data['invoice'] = (!empty($order->invoice_id)
                        ? $this->Invoices->get($order->invoice_id)
                        : null
                    );
                }
                break;
            default:
                // Do nothing
                break;
        }

        // Include currency and item information if available
        $cart = $this->SessionCart->get();

        // Include the currency from the invoice created, or fallback to the cart
        if (isset($data['invoice']) && !empty($data['invoice']->currency)) {
            $data['currency'] = $data['invoice']->currency;
        } elseif (!empty($cart['currency'])) {
            $data['currency'] = $cart['currency'];
        }

        // Set all item packages and package groups from the invoice, or fallback to the cart
        if (isset($data['invoice']) && is_object($data['invoice'])) {
            $data['products'] = $this->buildInvoiceItems($data['invoice']);
        } elseif (!empty($cart['items'])) {
            $data['products'] = $this->buildItems($cart['items']);
        }

        return $data;
    }

    /**
     * Retrieves item information representing each primary service from the given invoice
     * @see OrderPlugin::buildItems
     *
     * @param stdClass $invoice An stdClass object representing the invoice and containing
     *  - line_items A set of invoice line items
     */
    private function buildInvoiceItems(stdClass $invoice)
    {
        Loader::loadModels($this, ['Services']);
        $service_ids = [];

        // Retrieve the necessary invoice line item information to construct the items
        foreach ($invoice->line_items as $line_item) {
            // Skip line items that reference a service we've already seen in this loop
            // We care not for config options--only the primary service(s)
            if ($line_item->service_id === null || array_key_exists($line_item->service_id, (array)$service_ids)) {
                continue;
            }

            if (($service = $this->Services->get($line_item->service_id))) {
                $service_ids[$service->id] = [
                    'pricing_id' => $service->pricing_id,
                    'group_id' => $service->package_group_id
                ];
            }
        }

        return $this->buildItems($service_ids);
    }

    /**
     * Creates an array of information representing each item
     *
     * @param array $items An array containing the:
     *  - pricing_id The ID of the selected pricing
     *  - group_id The ID of the package group
     */
    private function buildItems(array $items)
    {
        Loader::loadModels($this, ['Packages', 'PackageGroups']);

        $data = [];
        $packages = [];
        $package_groups = [];

        foreach ($items as $item) {
            // Skip items that are missing a pricing ID or package group ID,
            // or that has already been set (e.g. a config option)
            if (!isset($item['pricing_id']) || !isset($item['group_id']) || isset($packages[$item['pricing_id']])) {
                continue;
            }

            $product = (object)[];

            // Store the package in case it is used by additional items
            if (!isset($packages[$item['pricing_id']])) {
                $packages[$item['pricing_id']] = $this->Packages->getByPricingId($item['pricing_id']);
            }

            // Set the package for this item
            if (!empty($packages[$item['pricing_id']])) {
                $product->package = $packages[$item['pricing_id']];
            }

            // Store the package group in case it is used by additional items
            if (!isset($packages[$item['group_id']])) {
                $package_groups[$item['group_id']] = $this->PackageGroups->get($item['group_id']);
            }

            // Set the package group for this item
            if (!empty($package_groups[$item['group_id']])) {
                $product->package_group = $package_groups[$item['group_id']];
            }

            $data[] = $product;
        }

        return $data;
    }

    /**
     * Execute the cron task
     *
     * @param string $key The cron task to execute
     */
    public function cron($key)
    {
        switch ($key) {
            case 'accept_paid_orders':
                Loader::loadModels($this, ['Order.OrderOrders']);
                $this->OrderOrders->acceptPaidOrders();
                break;
            case 'affiliate_monthly_report':
                Loader::loadModels($this, ['Order.OrderAffiliates']);
                Loader::loadHelpers($this, ['Date']);

                if ($this->Date->cast('c', 'j') == 1) {
                    $this->OrderAffiliates->affiliateMonthlyReport();
                }
                break;
            case 'mature_affiliate_referrals':
                Loader::loadModels($this, ['Order.OrderAffiliateReferrals']);
                $this->OrderAffiliateReferrals->matureAffiliateReferrals();
                break;
            case 'process_abandoned_orders':
                Loader::loadModels($this, ['Order.OrderOrders']);
                $this->OrderOrders->notifyAbandonedOrders();
                break;
        }
    }

    /**
     * Retrieves cron tasks available to this plugin along with their default values
     *
     * @return array A list of cron tasks
     */
    private function getCronTasks()
    {
        return [
            // Cron task to automatically accept paid orders
            [
                'key' => 'accept_paid_orders',
                'task_type' => 'plugin',
                'dir' => 'order',
                'name' => Language::_('OrderPlugin.cron.accept_paid_orders_name', true),
                'description' => Language::_('OrderPlugin.cron.accept_paid_orders_desc', true),
                'type' => 'interval',
                'type_value' => 5,
                'enabled' => 1
            ],
            // Cron task to send all monthly reports/emails for the affiliate system
            [
                'key' => 'affiliate_monthly_report',
                'task_type' => 'plugin',
                'dir' => 'order',
                'name' => Language::_('OrderPlugin.cron.affiliate_monthly_report_name', true),
                'description' => Language::_('OrderPlugin.cron.affiliate_monthly_report_desc', true),
                'type' => 'time',
                'type_value' => '12:00:00',
                'enabled' => 1
            ],
            // Cron task to check for order referrals that have matured
            [
                'key' => 'mature_affiliate_referrals',
                'task_type' => 'plugin',
                'dir' => 'order',
                'name' => Language::_('OrderPlugin.cron.mature_affiliate_referrals_name', true),
                'description' => Language::_('OrderPlugin.cron.mature_affiliate_referrals_desc', true),
                'type' => 'time',
                'type_value' => '08:00:00',
                'enabled' => 1
            ],
            // Cron task to process abandoned orders
            [
                'key' => 'process_abandoned_orders',
                'task_type' => 'plugin',
                'dir' => 'order',
                'name' => Language::_('OrderPlugin.cron.process_abandoned_orders_name', true),
                'description' => Language::_('OrderPlugin.cron.process_abandoned_orders_desc', true),
                'type' => 'interval',
                'type_value' => 5,
                'enabled' => 1
            ]
        ];
    }

    /**
     * Attempts to add new cron tasks for this plugin
     *
     * @param array $tasks A list of cron tasks to add
     * @see Order::install(), Order::upgrade(), Order::getCronTasks()
     */
    private function addCronTasks(array $tasks)
    {
        Loader::loadModels($this, ['CronTasks']);

        foreach ($tasks as $task) {
            $task_id = $this->CronTasks->add($task);

            if (!$task_id) {
                $cron_task = $this->CronTasks->getByKey($task['key'], $task['dir'], $task['task_type']);
                if ($cron_task) {
                    $task_id = $cron_task->id;
                }
            }

            if ($task_id) {
                $task_vars = ['enabled' => $task['enabled']];
                if ($task['type'] == 'interval') {
                    $task_vars['interval'] = $task['type_value'];
                } else {
                    $task_vars['time'] = $task['type_value'];
                }

                $this->CronTasks->addTaskRun($task_id, $task_vars);
            }
        }
    }
}
