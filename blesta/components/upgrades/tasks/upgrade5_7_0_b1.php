<?php
/**
 * Upgrades to version 5.7.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade5_7_0B1 extends UpgradeUtil
{
    /**
     * @var array An array of all tasks completed
     */
    private $tasks = [];

    /**
     * Setup
     */
    public function __construct()
    {
        Loader::loadComponents($this, ['Record']);
    }

    /**
     * Returns a numerically indexed array of tasks to execute for the upgrade process
     *
     * @return array A numerically indexed array of tasks to execute for the upgrade process
     */
    public function tasks()
    {
        return [
            'createQuotationTables',
            'addQuotationsNaviagtionItem',
            'addQuotationsWidget',
            'addTransitionQuotationsCron',
            'addQuotationMessengerTemplates',
            'addQuotationDeliveryEmail',
            'addStaffQuotationApprovedEmail',
            'addParityStringSetting',
            'addQuotationsPermissions',
            'addRenewalQueueNavItem',
            'addToolsRenewalQueuePermissions',
        ];
    }

    /**
     * Processes the given task
     *
     * @param string $task The task to process
     */
    public function process($task)
    {
        $tasks = $this->tasks();

        // Ensure task exists
        if (!in_array($task, $tasks)) {
            return;
        }

        $this->tasks[] = $task;
        $this->{$task}();
    }

    /**
     * Rolls back all tasks completed for the upgrade process
     */
    public function rollback()
    {
        // Undo all tasks
        while (($task = array_pop($this->tasks))) {
            $this->{$task}(true);
        }
    }

    /**
     * Creates the required tables by the Quotation system in the database
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function createQuotationTables($undo = false)
    {
        Loader::loadModels($this, ['Companies']);

        if ($undo) {
            try {
                $this->Record->drop('quotations');
                $this->Record->drop('quotation_lines');
                $this->Record->drop('quotation_line_taxes');
                $this->Record->drop('quotation_invoices');
            } catch (Exception $e) {
                // Nothing to do
            }
        } else {
            // quotations
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('id_format', ['type' => 'varchar', 'size' => 64])->
                setField('id_value', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('client_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('staff_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('title', ['type' => 'varchar', 'size' => 255])->
                setField(
                    'status',
                    ['type' => 'enum', 'size' => "'draft','pending','approved','invoiced','expired','dead','lost'", 'default' => 'draft']
                )->
                setField('subtotal', ['type' => 'decimal', 'size' => '19,4', 'default' => '0.0000'])->
                setField('total', ['type' => 'decimal', 'size' => '19,4', 'default' => '0.0000'])->
                setField('currency', ['type' => 'char', 'size' => 3, 'default' => 'USD'])->
                setField('notes', ['type' => 'text'])->
                setField('private_notes', ['type' => 'text'])->
                setField('date_created', ['type' => 'datetime'])->
                setField('date_expires', ['type' => 'datetime'])->
                setKey(['id'], 'primary')->
                create('quotations', true);

            // quotation_lines
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('quotation_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('description', ['type' => 'text'])->
                setField('qty', ['type' => 'decimal', 'size' => '19,4', 'default' => '0.0000'])->
                setField('amount', ['type' => 'decimal', 'size' => '19,4', 'default' => '0.0000'])->
                setField('order', ['type' => 'smallint', 'size' => 5, 'unsigned' => true])->
                setKey(['id'], 'primary')->
                create('quotation_lines', true);

            // quotation_line_taxes
            $this->Record->
                setField('line_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('tax_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('cascade', ['type' => 'tinyint', 'size' => 1, 'default' => 0])->
                setField('subtract', ['type' => 'tinyint', 'size' => 1, 'default' => 0])->
                setKey(['line_id', 'tax_id'], 'primary')->
                create('quotation_line_taxes', true);

            // quotation_invoices
            $this->Record->
                setField('quotation_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('invoice_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setKey(['quotation_id', 'invoice_id'], 'primary')->
                create('quotation_invoices', true);

            // Update company settings
            $companies = $this->Record->select()->from('companies')->getStatement();
            foreach ($companies as $company) {
                $this->Companies->setSetting($company->id, 'quotation_valid_days', '30', null, 1);
                $this->Companies->setSetting($company->id, 'quotation_dead_days', '10', null, 1);
                $this->Companies->setSetting($company->id, 'quotation_deposit_percentage', '50', null, 1);
                $this->Companies->setSetting($company->id, 'quotation_format', 'QUOTE-{num}', null, 1);
                $this->Companies->setSetting($company->id, 'quotation_increment', '1', null, 1);
                $this->Companies->setSetting($company->id, 'quotation_start', '1', null, 1);
            }

            // Update blesta.php config
            if (file_exists(CONFIGDIR . 'blesta.php')) {
                $this->addConfig(CONFIGDIR . 'blesta.php', 'Blesta.quotation_valid_max_days', 60);
                $this->editConfig(CONFIGDIR . 'blesta.php', 'Blesta.replacement_keys', [
                    'clients' => ['ID_VALUE_TAG' => '{num}'],
                    'invoices' => ['ID_VALUE_TAG' => '{num}'],
                    'quotations' => ['ID_VALUE_TAG' => '{num}'],
                    'packages' => ['ID_VALUE_TAG' => '{num}'],
                    'services' => ['ID_VALUE_TAG' => '{num}']
                ]);
            }
        }
    }

    /**
     * Adds "Quotations" to the navigation bar
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function addQuotationsNaviagtionItem($undo = false)
    {
        Loader::loadModels($this, ['Actions', 'Companies', 'Navigation']);

        // Fetch all companies
        $companies = $this->Companies->getAll();

        if ($undo) {
            foreach ($companies as $company) {
                // Delete the action
                $this->Record->from('actions')->
                    leftJoin('navigation_items', 'navigation_items.action_id', '=', 'actions.id', false)->
                    where('actions.url', '=', 'billing/quotations/')->
                    where('actions.location', '=', 'nav_staff')->
                    where('actions.company_id', '=', $company->id)->
                    delete(['actions.*', 'navigation_items.*']);
            }
        } else {
            foreach ($companies as $company) {
                $action_id = $this->Actions->add([
                    'location' => 'nav_staff',
                    'url' => 'billing/quotations/',
                    'name' => 'Navigation.getprimary.nav_billing_quotations',
                    'company_id' => $company->id,
                    'editable' => 0
                ]);
                $this->Navigation->add([
                    'action_id' => $action_id,
                    'parent_url' => 'billing/'
                ]);
            }
        }
    }

    /**
     * Adds "Quotations" widget to the client profile
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function addQuotationsWidget($undo = false)
    {
        Loader::loadModels($this, ['Actions', 'Companies']);

        // Fetch all companies
        $companies = $this->Companies->getAll();

        if ($undo) {
            foreach ($companies as $company) {
                // Delete the action
                $this->Record->from('actions')->
                    where('actions.url', '=', 'quotations/?whole_widget=true')->
                    where('actions.location', '=', 'widget_client_home')->
                    where('actions.company_id', '=', $company->id)->
                    delete(['actions.*']);
            }
        } else {
            foreach ($companies as $company) {
                $this->Actions->add([
                    'location' => 'widget_client_home',
                    'url' => 'quotations/?whole_widget=true',
                    'name' => 'ClientQuotations.index.boxtitle_quotations',
                    'company_id' => $company->id,
                    'editable' => 0
                ]);
            }
        }
    }

    /**
     * Adds a new invoice late fees table to the database and their respective cron task
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addTransitionQuotationsCron($undo = false)
    {
        Loader::loadModels($this, ['Companies', 'CronTasks']);

        if ($undo) {
            $cron = $this->CronTasks->getByKey('transition_quotations', null, 'system');

            $this->Record->from('cron_task_runs')
                ->innerJoin('cron_tasks', 'cron_tasks.id', '=', 'cron_task_runs.task_id', false)
                ->where('cron_tasks.task_type', '=', 'system')
                ->where('cron_task_runs.task_id', '=', $cron->id)
                ->delete(['cron_task_runs.*']);

            $this->Record->from('cron_tasks')
                ->where('id', '=', $cron->id)
                ->delete();
        } else {
            $task_id = $this->CronTasks->add([
                'key' => 'transition_quotations',
                'task_type' => 'system',
                'name' => 'CronTasks.crontask.name.transition_quotations',
                'description' => 'CronTasks.crontask.description.transition_quotations',
                'is_lang' => 1,
                'type' => 'interval'
            ]);

            if ($task_id) {
                $companies = $this->Companies->getAll();
                foreach ($companies as $company) {
                    // Add cron task run for the company
                    $vars = [
                        'task_id' => $task_id,
                        'company_id' => $company->id,
                        'time' => null,
                        'interval' => '5',
                        'enabled' => 1,
                        'date_enabled' => $this->Companies->dateToUtc(date('c'))
                    ];

                    $this->Record->insert('cron_task_runs', $vars);
                }
            }
        }
    }

    /**
     * Adds quotation messenger templates:
     *
     *  - Quotation Delivery
     *  - Quotation Approved
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addQuotationMessengerTemplates($undo = false)
    {
        if ($undo) {
            // Nothing to do
        } else {
            // Load required models
            Loader::loadModels($this, ['MessageGroups', 'Messages']);

            $templates = [
                [
                    'action' => 'quotation_delivery',
                    'type' => 'client',
                    'tags' => '{contact.first_name} {contact.last_name} {company.name} {quotations} {client_url}',
                    'content' => [
                        'sms' => 'Hi {contact.first_name}!
{% for quotation in quotations %}
A quotation #{quotation.id_code} was created.
{% endfor %}
To view it or approve it please log in at http://{client_url}login/.
Thank you for choosing {company.name}!'
                    ]
                ],
                [
                    'action' => 'staff_quotation_approved',
                    'type' => 'staff',
                    'tags' => '{staff.first_name} {staff.last_name} {contact.first_name} {contact.last_name} {quotations}',
                    'content' => [
                        'sms' => 'Hi {staff.first_name}!
{% for quotation in quotations %}
The quotation #{quotation.id_code} was approved!.
{% endfor %}'
                    ]
                ]
            ];

            foreach ($templates as $template) {
                // Add message group
                $message_group = $this->MessageGroups->getByAction($template['action']);

                if ($message_group) {
                    $group_id = $message_group->id;
                } else {
                    $group_id = $this->MessageGroups->add($template);
                }

                if (!$group_id) {
                    return;
                }

                // Get all companies from the system
                $companies = $this->Record->select()->from('companies')->fetchAll();

                foreach ($companies as $company) {
                    // Get company languages
                    $languages = $this->Record->select()->from('languages')->where('company_id', '=', $company->id)->fetchAll();

                    foreach ($template['content'] as $type => $content) {
                        $message_content = [];
                        foreach ($languages as $language) {
                            $message_content[] = ['lang' => $language->code, 'content' => $content];
                        }

                        $this->Messages->add(
                            [
                                'message_group_id' => $group_id,
                                'company_id' => $company->id,
                                'type' => $type,
                                'status' => 'inactive',
                                'content' => $message_content
                            ]
                        );
                    }
                }
            }
        }
    }

    /**
     * Adds the quotation delivery email template
     *
     * @param bool $undo True to undo the change, false to perform the change
     */
    private function addQuotationDeliveryEmail($undo = false)
    {
        if ($undo) {
            return;
        }

        Loader::loadModels($this, ['Companies', 'Emails', 'Languages']);

        // Add the quotation delivery email template group
        $this->Record->query("INSERT INTO `email_groups` (`id` , `action` , `type` , `plugin_dir` , `tags`)
            VALUES (
                NULL ,
                'quotation_delivery',
                'client',
                NULL ,
                '{contact.first_name} {contact.last_name} {company.name} {quotations} {client_url}'
            );");
        $email_group_id = $this->Record->lastInsertId();

        // Fetch all companies
        $companies = $this->Companies->getAll();

        foreach ($companies as $company) {
            // Fetch all languages installed for this company
            $languages = $this->Languages->getAll($company->id);

            // Add the quotation delivery email template for each installed language
            foreach ($languages as $language) {
                // Fetch the invoice delivery unpaid email to copy fields from
                $invoice_delivery_email = $this->Emails->getByType($company->id, 'service_suspension', $language->code);

                if ($invoice_delivery_email) {
                    $vars = [
                        'email_group_id' => $email_group_id,
                        'company_id' => $company->id,
                        'lang' => $language->code,
                        'from' => $invoice_delivery_email->from,
                        'from_name' => $invoice_delivery_email->from_name,
                        'subject' => 'Quote Created',
                        'text' => 'Hi {contact.first_name},

{% for quotation in quotations %}
A quote #{quotation.id_code} was created.
{% endfor %}
To view it or approve login to your account at http://{client_url}login/.',
                        'html' => '<p>Hi {contact.first_name},</p>
{% for quotation in quotations %}
<p>A quote #{quotation.id_code} was created.</p>
{% endfor %}
<p>To view it or approve login to your account at <a href="http://{client_url}login/">http://{client_url}login/</a></p>',
                        'email_signature_id' => $invoice_delivery_email->email_signature_id
                    ];

                    $this->Record->insert('emails', $vars);
                }
            }
        }
    }

    /**
     * Adds the staff quotation delivery email template
     *
     * @param bool $undo True to undo the change, false to perform the change
     */
    private function addStaffQuotationApprovedEmail($undo = false)
    {
        if ($undo) {
            return;
        }

        Loader::loadModels($this, ['Companies', 'Emails', 'Languages']);

        // Add the quotation approved email template group
        $this->Record->query(
            "INSERT INTO `email_groups` (`id` , `action` , `type` , `plugin_dir` , `tags`)
            VALUES (
                NULL ,
                'staff_quotation_approved',
                'staff',
                NULL ,
                '{staff.first_name} {staff.last_name} {contact.first_name} {contact.last_name} {quotations}'
            );"
        );
        $email_group_id = $this->Record->lastInsertId();

        // Fetch all companies
        $companies = $this->Companies->getAll();

        foreach ($companies as $company) {
            // Fetch all languages installed for this company
            $languages = $this->Languages->getAll($company->id);

            // Add the quotation approved email template for each installed language
            foreach ($languages as $language) {
                // Fetch the invoice delivery unpaid email to copy fields from
                $invoice_delivery_email = $this->Emails->getByType($company->id, 'service_suspension', $language->code);

                if ($invoice_delivery_email) {
                    $vars = [
                        'email_group_id' => $email_group_id,
                        'company_id' => $company->id,
                        'lang' => $language->code,
                        'from' => $invoice_delivery_email->from,
                        'from_name' => $invoice_delivery_email->from_name,
                        'subject' => 'Quotation Approved',
                        'text' => 'Hi {staff.first_name},

{% for quotation in quotations %}
The quote #{quotation.id_code} was approved!.
{% endfor %}
View or invoice the quote from the Staff interface.',
                        'html' => '<p>Hi {staff.first_name},</p>
{% for quotation in quotations %}
<p>The quote #{quotation.id_code} was approved by {contact.first_name} {contact.last_name}!.</p>
{% endfor %}
<p>View or invoice the quote from the Staff interface.</p>',
                        'email_signature_id' => $invoice_delivery_email->email_signature_id
                    ];

                    $this->Record->insert('emails', $vars);
                }
            }
        }
    }

    /**
     * Add permission to Quotations
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addQuotationsPermissions($undo = false)
    {
        Loader::loadModels($this, ['Permissions', 'StaffGroups']);
        Loader::loadComponents($this, ['Acl']);

        if ($undo) {
            // Nothing to undo
        } else {
            $staff_groups = $this->StaffGroups->getAll();
            // Determine comparable permission access
            $staff_group_access = [];
            foreach ($staff_groups as $staff_group) {
                $staff_group_access[$staff_group->id] = [
                    'admin_billing_quotations' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_billing',
                        'invoices',
                        'staff',
                        $staff_group->company_id
                    ),
                    'admin_clients_quotations' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_clients',
                        'invoices',
                        'staff',
                        $staff_group->company_id
                    ),
                    'admin_clients_createquotation' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_clients',
                        'createinvoice',
                        'staff',
                        $staff_group->company_id
                    ),
                    'admin_clients_editquotation' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_clients',
                        'editinvoice',
                        'staff',
                        $staff_group->company_id
                    )
                ];
            }

            $group_billing = $this->Permissions->getGroupByAlias('admin_billing');
            $group_clients = $this->Permissions->getGroupByAlias('admin_clients');

            $permissions = [
                [
                    'group_id' => $group_billing->id,
                    'name' => 'StaffGroups.permissions.admin_billing_quotations',
                    'alias' => 'admin_billing_quotations',
                    'action' => 'quotations'
                ],
                [
                    'group_id' => $group_clients->id,
                    'name' => 'StaffGroups.permissions.admin_clients_quotations',
                    'alias' => 'admin_clients_quotations',
                    'action' => 'quotations'
                ],
                [
                    'group_id' => $group_clients->id,
                    'name' => 'StaffGroups.permissions.admin_clients_quotations',
                    'alias' => 'admin_clients_createquotation',
                    'action' => 'createquotation'
                ],
                [
                    'group_id' => $group_clients->id,
                    'name' => 'StaffGroups.permissions.admin_clients_quotations',
                    'alias' => 'admin_clients_editquotation',
                    'action' => 'editquotation'
                ]
            ];

            foreach ($permissions as $vars) {
                $this->Permissions->add($vars);

                foreach ($staff_groups as $staff_group) {
                    // If staff group has access to similar item, grant access to this item
                    if ($staff_group_access[$staff_group->id][$vars['alias']]) {
                        $this->Acl->allow('staff_group_' . $staff_group->id, $vars['alias'], $vars['action']);
                    }
                }
            }
        }
    }

    /**
     * Adds a parity string setting to the system
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function addParityStringSetting($undo = false)
    {
        Loader::loadModels($this, ['Settings']);

        if ($undo) {
            // Nothing to do
        } else {
            if (Configure::get('Blesta.system_key')) {
                $this->Settings->setSetting(
                    'system_key_parity_string',
                    $this->Settings->systemEncrypt("I pity the fool that doesn't copy their config file!")
                );
            }
        }
    }

    /**
     * Adds a parity string setting to the system
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function addRenewalQueueNavItem($undo = false)
    {
        Loader::loadModels($this, ['Companies']);

        if ($undo) {
            // Nothing to do
        } else {
            foreach ($this->Companies->getAll() as $company) {
                // Insert the action for this renewal management navigation item
                $action_vars = [
                    'location' => 'nav_staff',
                    'url' => 'tools/renewals/',
                    'name' => 'Navigation.getprimary.nav_tools_renewals',
                    'company_id' => $company->id,
                    'options' => null,
                    'editable' => '0',
                ];
                $this->Record->insert('actions', $action_vars);
                $action_id = $this->Record->lastInsertId();

                // Get the tools nav item
                $tools_nav_item = $this->Record->select('navigation_items.*')->
                    from('navigation_items')->
                    innerJoin('actions', 'actions.id', '=', 'navigation_items.action_id', false)->
                    where('actions.url', '=', 'tools/')->
                    where('actions.company_id', '=', $company->id)->
                    fetch();

                // Get the logs nav item
                $logs_nav_item = $this->Record->select('navigation_items.*')->
                    from('navigation_items')->
                    innerJoin('actions', 'actions.id', '=', 'navigation_items.action_id', false)->
                    where('actions.url', '=', 'tools/logs/')->
                    where('actions.company_id', '=', $company->id)->
                    fetch();

                if ($tools_nav_item) {
                    // Increment the order column for all Tools nav items after Logs
                    $this->Record->query('UPDATE navigation_items
                        SET `order` = `order` + 1
                        WHERE `order` > ? AND `parent_id` = ?',
                        [$logs_nav_item->order ?? 0, $tools_nav_item->id ?? null]
                    );
                }

                // Insert the navigation item
                $navigation_vars = [
                    'action_id' => $action_id,
                    'order' => ($logs_nav_item->order ?? 0) + 1,
                    'parent_id' => $tools_nav_item->id ?? null
                ];
                $this->Record->insert('navigation_items', $navigation_vars);
            }
        }
    }

    /**
     * Adds a permission for tools renewal queue page
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addToolsRenewalQueuePermissions($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            // Get the admin tools permission group
            Loader::loadModels($this, ['Permissions']);
            Loader::loadComponents($this, ['Acl']);
            $group = $this->Permissions->getGroupByAlias('admin_tools');

            // Determine comparable permission access
            $staff_groups = $this->Record->select()->from('staff_groups')->fetchAll();
            $staff_group_access = [];
            foreach ($staff_groups as $staff_group) {
                $staff_group_access[$staff_group->id] = $this->Permissions->authorized(
                    'staff_group_' . $staff_group->id,
                    'admin_tools',
                    'logs',
                    'staff',
                    $staff_group->company_id
                );
            }

            // Add the new tools renewals page permission
            if ($group) {
                $this->Permissions->add([
                    'group_id' => $group->id,
                    'name' => 'StaffGroups.permissions.admin_tools_renewals',
                    'alias' => 'admin_tools',
                    'action' => 'renewals'
                ]);

                foreach ($staff_groups as $staff_group) {
                    if ($staff_group_access[$staff_group->id]) {
                        $this->Acl->allow('staff_group_' . $staff_group->id, 'admin_tools', 'renewals');
                    }
                }
            }
        }
    }
}
