<?php
/**
 * Upgrades to version 4.10.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_10_0B1 extends UpgradeUtil
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
        Configure::load('blesta');
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
            'addSmartSearchSettingsPermission',
            'addHumanVerificationSettingsPermission',
            'addInvoiceLateFees',
            'addInvoiceLateFeesSettingsPermission',
            'addServiceScheduledCancellationEmail',
            'addScheduledCancellationBccNotices'
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
     * Adds a new permission for Smart Search settings page
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addSmartSearchSettingsPermission($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            // Get the package permission group
            Loader::loadModels($this, ['Permissions']);
            Loader::loadComponents($this, ['Acl']);

            // Fetch all staff groups
            $staff_groups = $this->Record->select(['staff_groups.*'])
                 ->from('staff_groups')
                 ->group(['staff_groups.id'])
                 ->fetchAll();

            // Determine comparable permission access
            $staff_group_access = [];
            foreach ($staff_groups as $staff_group) {
                $staff_group_access[$staff_group->id] = [
                    'admin_company_general' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_company_general',
                        'localization',
                        'staff',
                        $staff_group->company_id
                    )
                ];
            }

            $group = $this->Permissions->getGroupByAlias('admin_settings');

            // Add the new smart search permission
            if ($group) {
                $permissions = [
                    'group_id' => $group->id,
                    'name' => 'StaffGroups.permissions.admin_company_general_smartsearch',
                    'alias' => 'admin_company_general',
                    'action' => 'smartsearch'
                ];
                $this->Permissions->add($permissions);

                foreach ($staff_groups as $staff_group) {
                    // If staff group has access to similar item, grant access to this item
                    if ($staff_group_access[$staff_group->id][$permissions['alias']]) {
                        $this->Acl->allow('staff_group_' . $staff_group->id, $permissions['alias'], $permissions['action']);
                    } else {
                        $this->Acl->deny('staff_group_' . $staff_group->id, $permissions['alias'], $permissions['action']);
                    }
                }
            }
        }
    }

    /**
     * Adds a new permission for Human Verification settings page
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addHumanVerificationSettingsPermission($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            // Get the package permission group
            Loader::loadModels($this, ['Permissions']);
            Loader::loadComponents($this, ['Acl']);

            // Fetch all staff groups
            $staff_groups = $this->Record->select(['staff_groups.*'])
                 ->from('staff_groups')
                 ->group(['staff_groups.id'])
                 ->fetchAll();

            // Determine comparable permission access
            $staff_group_access = [];
            foreach ($staff_groups as $staff_group) {
                $staff_group_access[$staff_group->id] = [
                    'admin_company_general' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_company_general',
                        'localization',
                        'staff',
                        $staff_group->company_id
                    )
                ];
            }

            $group = $this->Permissions->getGroupByAlias('admin_settings');

            // Add the new smart search permission
            if ($group) {
                $permissions = [
                    'group_id' => $group->id,
                    'name' => 'StaffGroups.permissions.admin_company_general_humanverification',
                    'alias' => 'admin_company_general',
                    'action' => 'humanverification'
                ];
                $this->Permissions->add($permissions);

                foreach ($staff_groups as $staff_group) {
                    // If staff group has access to similar item, grant access to this item
                    if ($staff_group_access[$staff_group->id][$permissions['alias']]) {
                        $this->Acl->allow('staff_group_' . $staff_group->id, $permissions['alias'], $permissions['action']);
                    } else {
                        $this->Acl->deny('staff_group_' . $staff_group->id, $permissions['alias'], $permissions['action']);
                    }
                }
            }
        }
    }

    /**
     * Adds a new invoice late fees table to the database and their respective cron task
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addInvoiceLateFees($undo = false)
    {
        Loader::loadModels($this, ['Companies', 'CronTasks']);

        if ($undo) {
            // Drop invoice_late_fees table
            $this->Record->drop('invoice_late_fees');

            // Remove company settings
            $fields = ['apply_inv_late_fees', 'late_fee_total_amount', 'late_fees'];
            $companies = $this->Companies->getAll();

            foreach ($companies as $company) {
                foreach ($fields as $field) {
                    $this->Companies->unsetSetting($company->id, $field);
                }
            }

            // Remove cron task
            $cron = $this->CronTasks->getByKey('apply_invoice_late_fees', null, 'system');

            $this->Record->from('cron_task_runs')
                ->innerJoin('cron_tasks', 'cron_tasks.id', '=', 'cron_task_runs.task_id', false)
                ->where('cron_tasks.task_type', '=', 'system')
                ->where('cron_task_runs.task_id', '=', $cron->id)
                ->delete(['cron_task_runs.*']);

            $this->Record->from('cron_tasks')
                ->where('id', '=', $cron->id)
                ->delete();
        } else {
            // Create invoice_late_fees table
            $this->Record
                ->setField('invoice_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                ->setField('invoice_line_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                ->setKey(['invoice_id', 'invoice_line_id'], 'primary')
                ->create('invoice_late_fees', true);

            // Update company settings
            $fields = ['apply_inv_late_fees' => 7, 'late_fee_total_amount' => 'true', 'late_fees' => ''];
            $companies = $this->Companies->getAll();

            foreach ($companies as $company) {
                $this->Companies->setSettings($company->id, $fields);
            }

            // Add cron task
            $task_id = $this->CronTasks->add([
                'key' => 'apply_invoice_late_fees',
                'task_type' => 'system',
                'name' => 'CronTasks.crontask.name.apply_invoice_late_fees',
                'description' => 'CronTasks.crontask.description.apply_invoice_late_fees',
                'is_lang' => 1,
                'type' => 'time'
            ]);

            if ($task_id) {
                foreach ($companies as $company) {
                    // Add cron task run for the company
                    $vars = [
                        'task_id' => $task_id,
                        'company_id' => $company->id,
                        'time' => '00:00',
                        'interval' => null,
                        'enabled' => 1,
                        'date_enabled' => $this->Companies->dateToUtc(date('c'))
                    ];

                    $this->Record->insert('cron_task_runs', $vars);
                }
            }
        }
    }

    /**
     * Adds a new permission for Late Fees settings page
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addInvoiceLateFeesSettingsPermission($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            // Get the package permission group
            Loader::loadModels($this, ['Permissions']);
            Loader::loadComponents($this, ['Acl']);

            // Fetch all staff groups
            $staff_groups = $this->Record->select(['staff_groups.*'])
                ->from('staff_groups')
                ->group(['staff_groups.id'])
                ->fetchAll();

            // Determine comparable permission access
            $staff_group_access = [];
            foreach ($staff_groups as $staff_group) {
                $staff_group_access[$staff_group->id] = [
                    'admin_company_billing' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_company_billing',
                        'invoices',
                        'staff',
                        $staff_group->company_id
                    )
                ];
            }

            $group = $this->Permissions->getGroupByAlias('admin_settings');

            // Add the new late fees permission
            if ($group) {
                $permissions = [
                    'group_id' => $group->id,
                    'name' => 'StaffGroups.permissions.admin_company_billing_latefees',
                    'alias' => 'admin_company_billing',
                    'action' => 'latefees'
                ];
                $this->Permissions->add($permissions);

                foreach ($staff_groups as $staff_group) {
                    // If staff group has access to similar item, grant access to this item
                    if ($staff_group_access[$staff_group->id][$permissions['alias']]) {
                        $this->Acl->allow('staff_group_' . $staff_group->id, $permissions['alias'], $permissions['action']);
                    } else {
                        $this->Acl->deny('staff_group_' . $staff_group->id, $permissions['alias'], $permissions['action']);
                    }
                }
            }
        }
    }

    /**
     * Adds a new email template for service scheduled cancellation
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addServiceScheduledCancellationEmail($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            Loader::loadModels($this, ['Companies', 'Emails', 'Languages']);

            // Add the service_scheduled_cancellation email group
            $this->Record->insert(
                'email_groups',
                [
                    'action' => 'service_scheduled_cancellation',
                    'type' => 'client',
                    'notice_type' => 'bcc',
                    'tags' => '{contact.first_name},{contact.last_name},{package.name},{service.name},{service.date_canceled_formatted}'
                ]
            );
            $email_group_id = $this->Record->lastInsertId();

            // Fetch all companies
            $companies = $this->Companies->getAll();

            foreach ($companies as $company) {
                // Fetch all languages installed for this company
                $languages = $this->Languages->getAll($company->id);

                // Add the service_scheduled_cancellation email template for each installed language
                foreach ($languages as $language) {
                    // Fetch the service_cancellation email to copy fields from
                    $cancellation_email = $this->Emails->getByType($company->id, 'service_cancellation', $language->code);

                    if ($cancellation_email) {
                        $vars = [
                            'email_group_id' => $email_group_id,
                            'company_id' => $company->id,
                            'lang' => $language->code,
                            'from' => $cancellation_email->from,
                            'from_name' => $cancellation_email->from_name,
                            'subject' => 'Scheduled Cancellation',
                            'text' => 'Hi {contact.first_name},

Your service, {package.name} - {service.name}, has been scheduled for cancellation. If no action is taken, it will be cancelled on {service.date_canceled_formatted}. If you believe this action is in error, please contact us as soon as possible.',
                            'html' => '<p>Hi {contact.first_name},</p>
                            <p>Your service, {package.name} - {service.name}, has been scheduled for cancellation. If no action is taken, it will be cancelled on {service.date_canceled_formatted}. If you believe this action is in error, please contact us as soon as possible.</p>',
                            'email_signature_id' => $cancellation_email->email_signature_id,
                            'status' => 'active'
                        ];

                        $this->Record->insert('emails', $vars);
                    }
                }
            }
        }
    }

    /**
     * Add BCC notice settings for staff members
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addScheduledCancellationBccNotices($undo = false)
    {
        Loader::loadModels($this, ['StaffGroups', 'Staff']);

        // Fetch all staff groups
        $staff_groups = $this->StaffGroups->getAll();

        // Add BCC notice
        foreach ($staff_groups as $staff_group) {
            if ($undo) {
                $this->Record->from('staff_group_notices')->where('action', '=', 'service_scheduled_cancellation')->delete();
            } else {
                $notices = $this->StaffGroups->getNotices($staff_group->id);
                foreach ($notices as $notice) {
                    if ($notice->action == 'service_cancellation') {
                        $this->Record->insert(
                            'staff_group_notices',
                            [
                                'staff_group_id' => $staff_group->id,
                                'action' => 'service_scheduled_cancellation',
                            ]
                        );

                        break;
                    }
                }
            }
        }

        // Fetch all staff
        $staff = $this->Staff->getAll();

        // Add BCC notice
        foreach ($staff as $staff_member) {
            if ($undo) {
                $this->Record->from('staff_notices')->where('action', '=', 'service_scheduled_cancellation')->delete();
            } else {
                $notices = $this->Staff->getNotices($staff_member->id);
                foreach ($notices as $notice) {
                    if ($notice->action == 'service_cancellation') {
                        $this->Record->insert(
                            'staff_notices',
                            [
                                'staff_group_id' => $notice->staff_group_id,
                                'staff_id' => $staff_member->id,
                                'action' => 'service_scheduled_cancellation',
                            ]
                        );

                        break;
                    }
                }
            }
        }
    }
}
