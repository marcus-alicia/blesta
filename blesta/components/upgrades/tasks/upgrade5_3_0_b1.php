<?php
/**
 * Upgrades to version 5.3.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade5_3_0B1 extends UpgradeUtil
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
            'addNewMessengerTemplates',
            'addInvoiceAppendDescriptionsSetting',
            'addClientWidgetsActions',
            'addDefaultViewConfig',
            'updateAccountsCc',
            'addCurlSslVerificationConfiguration'
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
     * Adds the new following messenger templates:
     *
     *  - Account Registration
     *  - Auto-Debit Pending
     *  - Invoice Notice (1st)
     *  - Invoice Notice (2nd)
     *  - Invoice Notice (3rd)
     *  - Service Creation
     *  - Service Suspension
     *  - Service Scheduled Cancellation
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addNewMessengerTemplates($undo = false)
    {
        if ($undo) {
            // Nothing to do
        } else {
            // Load required models
            Loader::loadModels($this, ['MessageGroups', 'Messages']);

            $templates = [
                [
                    'action' => 'account_welcome',
                    'type' => 'client',
                    'tags' => '{contact.first_name} {contact.last_name} {company.name} {client_url} {username} {password}',
                    'content' => [
                        'sms' => 'Hi {contact.first_name}!
Thank you for registering with us. Please login and change your password.
Login at http://{client_url}login/ with:
U: {username}
P: (Password you signed up with)
Thank you for choosing {company.name}!'
                    ]
                ],
                [
                    'action' => 'auto_debit_pending',
                    'type' => 'client',
                    'tags' => '{contact.first_name} {contact.last_name} {invoice.id_code} {invoice.date_due_formatted} {payment_account.first_name} {payment_account.last_name} {payment_account.account_type} {payment_account.last4} {amount} {amount_formatted} {payment_url} {autodebit_date} {client_url}',
                    'content' => [
                        'sms' => 'Hi {contact.first_name}, payment will be charged to your account on {autodebit_date} in the amount of {amount_formatted} for invoice #{invoice.id_code}.
{% if payment_account.account_type == "ach" %}Your {payment_account.type_name} Account ending in {payment_account.last4} will be processed on this date. To alter the payment method, please login at http://\{client_uri}.{% else %}Your {payment_account.type_name} ending in {payment_account.last4} will be processed on this date. To alter the payment method, please login at http://{client_uri}.{% endif %} To disable automatic payments login to your account at http://{client_uri}.
Thank you for your continued business!'
                    ]
                ],
                [
                    'action' => 'invoice_notice_first',
                    'type' => 'client',
                    'tags' => '{contact.first_name} {contact.last_name} {invoice.id_code} {invoice.date_due_formatted} {payment_account.first_name} {payment_account.last_name} {payment_account.account_type} {payment_url} {autodebit} {autodebit_date} {autodebit_date_formatted} {client_url}',
                    'content' => [
                        'sms' => 'Hi {contact.first_name},
This is a reminder that invoice #{invoice.id_code} is due on {invoice.date_due_formatted}. If you have recently mailed in payment for this invoice, you can ignore this reminder.
Pay Now. http://{payment_url} (No Login Required)
Thank you for your continued business!'
                    ]
                ],
                [
                    'action' => 'invoice_notice_second',
                    'type' => 'client',
                    'tags' => '{contact.first_name} {contact.last_name} {invoice.id_code} {invoice.date_due_formatted} {payment_account.first_name} {payment_account.last_name} {payment_account.account_type} {payment_url} {autodebit} {autodebit_date} {autodebit_date_formatted} {client_url}',
                    'content' => [
                        'sms' => 'Hi {contact.first_name},
This is the 2nd notice we have sent regarding invoice #{invoice.id_code}. It was due on {invoice.date_due_formatted} and is now past due. If you have recently mailed in payment for this invoice, you can ignore this email.
Pay Now. http://{payment_url} (No login required)'
                    ]
                ],
                [
                    'action' => 'invoice_notice_third',
                    'type' => 'client',
                    'tags' => '{contact.first_name} {contact.last_name} {invoice.id_code} {invoice.date_due_formatted} {payment_account.first_name} {payment_account.last_name} {payment_account.account_type} {payment_url} {autodebit} {autodebit_date} {autodebit_date_formatted} {client_url}',
                    'content' => [
                        'sms' => 'Hi {contact.first_name},
This is the 3rd notice we have sent regarding invoice #{invoice.id_code}. It was due on {invoice.date_due_formatted} and is now past due. This is the last notice we will send regarding this particular invoice. If payment is not received soon, the account may be suspended.
Pay Now. http://{payment_url} (No login required)'
                    ]
                ],
                [
                    'action' => 'service_creation',
                    'type' => 'client',
                    'tags' => '{contact.first_name} {contact.last_name} {service.name}',
                    'content' => [
                        'sms' => 'Hi {contact.first_name},
Your service "{service.name}" has been approved and activated.'
                    ]
                ],
                [
                    'action' => 'service_scheduled_cancellation',
                    'type' => 'client',
                    'tags' => '{contact.first_name} {contact.last_name} {package.name} {service.name} {service.date_canceled_formatted}',
                    'content' => [
                        'sms' => 'Hi {contact.first_name},
Your service, {package.name} - {service.name}, has been scheduled for cancellation. It will be cancelled on {service.date_canceled_formatted}. If this is in error, please contact us ASAP.'
                    ]
                ],
                [
                    'action' => 'service_suspension',
                    'type' => 'client',
                    'tags' => '{contact.first_name} {contact.last_name} {package.name} {service.name} {service.suspension_reason}',
                    'content' => [
                        'sms' => 'Hi {contact.first_name},
Your service, {package.name} - {service.name} has been suspended. This is most commonly in response to 1) Non-payment 2) TOS or abuse violation.
Suspended service may be cancelled after an extended period. Please contact us if you have any questions.'
                    ]
                ],
                [
                    'action' => 'service_unsuspension',
                    'type' => 'client',
                    'tags' => '{contact.first_name} {contact.last_name} {package.name} {service.name}',
                    'content' => [
                        'sms' => 'Hi {contact.first_name},
Your service, {package.name} - {service.name} has been unsuspended.'
                    ]
                ],
                [
                    'action' => 'invoice_delivery_unpaid',
                    'type' => 'client',
                    'tags' => '{contact.first_name} {contact.last_name} {invoices} {autodebit} {client_url} {payment_account.first_name} {payment_account.last_name} {payment_account.account_type} {payment_account.last4}',
                    'content' => [
                        'sms' => 'Hi {contact.first_name},
An invoice has been created for your account.
{% for invoice in invoices %}
Invoice #: {invoice.id_code} - {invoice.total} {invoice.currency}
Pay Now, visit http://{invoice.payment_url} (No login required)
{% endfor %}'
                    ]
                ],
                [
                    'action' => 'invoice_delivery_paid',
                    'type' => 'client',
                    'tags' => '{contact.first_name} {contact.last_name} {invoices} {autodebit} {client_url} {payment_account.first_name} {payment_account.last_name} {payment_account.account_type} {payment_account.last4}',
                    'content' => [
                        'sms' => 'Hi {contact.first_name},
An invoice has been created for your account.
{% for invoice in invoices %}
Invoice #: {invoice.id_code} - {invoice.total} {invoice.currency}
{% endfor %}
This invoice has already been paid, so no payment is necessary for this one, but your account may have other balances.'
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
     * Adds a new setting for appending the package description to the invoice line
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addInvoiceAppendDescriptionsSetting($undo = false)
    {
        Loader::loadModels($this, ['Companies']);

        // Get all companies
        $companies = $this->Companies->getAll();

        if ($undo) {
            foreach ($companies as $company) {
                $this->Companies->unsetSetting($company->id, 'inv_append_descriptions');
            }
        } else {
            foreach ($companies as $company) {
                $this->Companies->setSetting($company->id, 'inv_append_descriptions', 'false');
            }
        }
    }

    /**
     * Adds the default client widgets as actions
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addClientWidgetsActions($undo = false)
    {
        $actions = [
            [
                'location' => 'widget_client_home',
                'url' => 'invoices/?whole_widget=true',
                'name' => 'ClientInvoices.index.boxtitle_invoices',
                'editable' => 0,
                'enabled' => 1
            ],
            [
                'location' => 'widget_client_home',
                'url' => 'services/?whole_widget=true',
                'name' => 'ClientServices.index.boxtitle_services',
                'editable' => 0,
                'enabled' => 1
            ],
            [
                'location' => 'widget_client_home',
                'url' => 'transactions/?whole_widget=true',
                'name' => 'ClientTransactions.index.boxtitle_transactions',
                'editable' => 0,
                'enabled' => 1
            ]
        ];

        // Get all companies from the system
        $companies = $this->Record->select()->from('companies')->fetchAll();

        foreach ($companies as $company) {
            if ($undo) {
                foreach ($actions as $action) {
                    $this->Record->from('actions')
                        ->where('company_id', '=', $company->id)
                        ->where('location', '=', 'widget_client_home')
                        ->where('url', '=', $action['url'])
                        ->delete();
                }
            } else {
                foreach ($actions as $action) {
                    $action['company_id'] = $company->id;
                    $this->Record->insert('actions', $action);
                }
            }
        }
    }

    /**
     * Adds the configuration values of the default views, which
     * will be inherited by the child themes
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addDefaultViewConfig($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            if (file_exists(CONFIGDIR . 'blesta.php')) {
                // Sets the default view template for the admin interface
                $this->addConfig(CONFIGDIR . 'blesta.php', 'Blesta.default_admin_view_template', 'default');

                // Sets the default view template for the client interface
                $this->addConfig(CONFIGDIR . 'blesta.php', 'Blesta.default_client_view_template', 'bootstrap');
            }
        }
    }

    /**
     * Updates the accounts_cc.reference_id and accounts_cc.client_reference_id field from varchar(128) to varchar(255)
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function updateAccountsCc($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `accounts_cc` CHANGE `reference_id` `reference_id` VARCHAR( 128 ) NULL DEFAULT NULL ;');
            $this->Record->query('ALTER TABLE `accounts_cc` CHANGE `client_reference_id` `client_reference_id` VARCHAR( 128 ) NULL DEFAULT NULL ;');
        } else {
            $this->Record->query('ALTER TABLE `accounts_cc` CHANGE `reference_id` `reference_id` VARCHAR( 255 ) NULL DEFAULT NULL ;');
            $this->Record->query('ALTER TABLE `accounts_cc` CHANGE `client_reference_id` `client_reference_id` VARCHAR( 255 ) NULL DEFAULT NULL ;');
        }
    }

    /**
     * Adds the new "Blesta.curl_verify_ssl" configuration option
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function addCurlSslVerificationConfiguration($undo = false)
    {
        if (file_exists(CONFIGDIR . 'blesta.php')) {
            $this->addConfig(CONFIGDIR . 'blesta.php', 'Blesta.curl_verify_ssl', false);
        }
    }
}
