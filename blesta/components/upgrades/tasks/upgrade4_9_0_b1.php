<?php
/**
 * Upgrades to version 4.9.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_9_0B1 extends UpgradeUtil
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
            'updateIpAddressLength',
            'addServiceCancellationEmail',
            'addSendCancellationNoticesSetting',
            'addCancellationBccNotices',
            'addPluginActionEnabled',
            'addPluginEventEnabled',
            'addPluginSettingPermission',
            'addRenewalErrorEmail',
            'addRenewalErrorBccNotices',
            'addClientSettingIndex',
            'updateConfig',
            'addEmailSentIndex',
            'addPasswordResetPermission',
            'addPackageClientQty'
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
     * Increases the length of ip_address fields in log_users and log_client_settings tables
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function updateIpAddressLength($undo = false)
    {
        if ($undo) {
            $this->Record->query("ALTER TABLE `log_users` CHANGE `ip_address` `ip_address`
                VARCHAR(39) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;
            ");

            $this->Record->query("ALTER TABLE `log_client_settings` CHANGE `ip_address` `ip_address`
                VARCHAR(39) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
            ");
        } else {
            $this->Record->query("ALTER TABLE `log_users` CHANGE `ip_address` `ip_address`
                VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;
            ");

            $this->Record->query("ALTER TABLE `log_client_settings` CHANGE `ip_address` `ip_address`
                VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
            ");
        }
    }

    /**
     * Adds a new email template for service cancellation
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addServiceCancellationEmail($undo = false)
    {
        if ($undo) {
            return;
        }

        Loader::loadModels($this, ['Companies', 'Emails', 'Languages']);

        // Add the service_cancellation email group
        $this->Record->query("INSERT INTO `email_groups` (
                    `id`, `action`, `type`, `notice_type`, `plugin_dir`, `tags`
                ) VALUES (
                    NULL, ?, ?, ?, NULL, ?
                );
                ",
                [
                    'service_cancellation',
                    'client',
                    'bcc',
                    '{contact.first_name},{contact.last_name},{package.name},{service.name}'
                ]
            );
        $email_group_id = $this->Record->lastInsertId();

        // Fetch all companies
        $companies = $this->Companies->getAll();

        foreach ($companies as $company) {
            // Fetch all languages installed for this company
            $languages = $this->Languages->getAll($company->id);

            // Add the service_cancellation email template for each installed language
            foreach ($languages as $language) {
                // Fetch the service_suspension email to copy fields from
                $suspension_email = $this->Emails->getByType($company->id, 'service_suspension', $language->code);

                if ($suspension_email) {
                    $vars = [
                        'email_group_id' => $email_group_id,
                        'company_id' => $company->id,
                        'lang' => $language->code,
                        'from' => $suspension_email->from,
                        'from_name' => $suspension_email->from_name,
                        'subject' => 'Service Canceled',
                        'text' => 'Hi {contact.first_name},

Your service, {package.name} - {service.name}, has been canceled.',
                        'html' => '<p>Hi {contact.first_name},</p>
                            <p>Your service, {package.name} - {service.name}, has been canceled.</p>',
                        'email_signature_id' => $suspension_email->email_signature_id,
                        'status' => 'active'
                    ];

                    $this->Record->insert('emails', $vars);
                }
            }
        }
    }

    /**
     * Adds new setting for whether to send service cancellation notices
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addSendCancellationNoticesSetting($undo = false)
    {
        Loader::loadModels($this, ['Companies']);

        $setting = 'send_cancellation_notice';

        // Fetch all companies
        $companies = $this->Companies->getAll();

        // Add or remove the setting
        foreach ($companies as $company) {
            if ($undo) {
                $this->Record->from('company_settings')->where('key', '=', $setting)->delete();
            } else {
                $this->Record->insert(
                    'company_settings',
                    ['key' => $setting, 'company_id' => $company->id, 'value' => 'false']
                );
            }
        }
    }

    /**
     * Add BCC notice settings for staff members
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addCancellationBccNotices($undo = false)
    {
        Loader::loadModels($this, ['StaffGroups', 'Staff']);

        // Fetch all staff groups
        $staff_groups = $this->StaffGroups->getAll();

        // Add BCC notice
        foreach ($staff_groups as $staff_group) {
            if ($undo) {
                $this->Record->from('staff_group_notices')->where('action', '=', 'service_cancellation')->delete();
            } else {
                $notices = $this->StaffGroups->getNotices($staff_group->id);
                foreach ($notices as $notice) {
                    if ($notice->action == 'service_suspension') {
                        $this->Record->insert(
                            'staff_group_notices',
                            [
                                'staff_group_id' => $staff_group->id,
                                'action' => 'service_cancellation',
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
                $this->Record->from('staff_notices')->where('action', '=', 'service_cancellation')->delete();
            } else {
                $notices = $this->Staff->getNotices($staff_member->id);
                foreach ($notices as $notice) {
                    if ($notice->action == 'service_suspension') {
                        $this->Record->insert(
                            'staff_notices',
                            [
                                'staff_group_id' => $notice->staff_group_id,
                                'staff_id' => $staff_member->id,
                                'action' => 'service_cancellation',
                            ]
                        );

                        break;
                    }
                }
            }
        }
    }

    /**
     * Updates `plugin_actions` to have a new 'enabled' column
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPluginActionEnabled($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `plugin_actions` DROP INDEX `enabled`');
            $this->Record->query('ALTER TABLE `plugin_actions` DROP `enabled`');
        } else {
            $this->Record->query(
                'ALTER TABLE `plugin_actions` ADD `enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT "1" AFTER `options`'
            );
            $this->Record->query('ALTER TABLE `plugin_actions` ADD INDEX `enabled` (`enabled`)');
        }
    }

    /**
     * Updates `plugin_events` to have a new 'enabled' column
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPluginEventEnabled($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `plugin_events` DROP INDEX `enabled`');
            $this->Record->query('ALTER TABLE `plugin_events` DROP `enabled`');
        } else {
            $this->Record->query(
                'ALTER TABLE `plugin_events` ADD `enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT "1" AFTER `callback`'
            );
            $this->Record->query('ALTER TABLE `plugin_events` ADD INDEX `enabled` (`enabled`)');
        }
    }

    /**
     * Adds a new permission for Plugin Settings
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPluginSettingPermission($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            // Get the package permission group
            Loader::loadModels($this, ['Permissions']);
            Loader::loadComponents($this, ['Acl']);
            $group = $this->Permissions->getGroupByAlias('admin_settings');

            // Add the new plugin settings permission
            if ($group) {
                $this->Permissions->add([
                    'group_id' => $group->id,
                    'name' => 'StaffGroups.permissions.admin_company_plugins_settings',
                    'alias' => 'admin_company_plugins',
                    'action' => 'settings'
                ]);

                $staff_groups = $this->Record->select('id')->from('staff_groups')->fetchAll();
                foreach ($staff_groups as $staff_group) {
                    $aro_alias = 'staff_group_' . $staff_group->id;
                    $aco_alias = 'admin_company_plugins';
                    $action = 'settings';

                    // Set whether the permission is allowed dependent on whether
                    // the staff group is allowed to access the 'manage' permission
                    if ($this->Acl->check($aro_alias, $aco_alias, 'manage')) {
                        $this->Acl->allow($aro_alias, $aco_alias, $action);
                    } else {
                        $this->Acl->deny($aro_alias, $aco_alias, $action);
                    }
                }
            }
        }
    }

    /**
     * Adds a new Send Password Reset permission for Admin Clients
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPasswordResetPermission($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            // Get the package permission group
            Loader::loadModels($this, ['Permissions']);
            Loader::loadComponents($this, ['Acl']);
            $group = $this->Permissions->getGroupByAlias('admin_clients');

            // Add the new plugin settings permission
            if ($group) {
                $this->Permissions->add([
                    'group_id' => $group->id,
                    'name' => 'StaffGroups.permissions.admin_clients_passwordreset',
                    'alias' => 'admin_clients',
                    'action' => 'passwordreset'
                ]);

                $staff_groups = $this->Record->select('id')->from('staff_groups')->fetchAll();
                foreach ($staff_groups as $staff_group) {
                    $aro_alias = 'staff_group_' . $staff_group->id;
                    $aco_alias = 'admin_clients';
                    $action = 'passwordreset';

                    // Set whether the permission is allowed dependent on whether
                    // the staff group is allowed to access the 'email' permission
                    if ($this->Acl->check($aro_alias, $aco_alias, 'email')) {
                        $this->Acl->allow($aro_alias, $aco_alias, $action);
                    } else {
                        $this->Acl->deny($aro_alias, $aco_alias, $action);
                    }
                }
            }
        }
    }

    /**
     * Adds a new email template for service renewal failures
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addRenewalErrorEmail($undo = false)
    {
        if ($undo) {
            return;
        }

        Loader::loadModels($this, ['Companies', 'Emails', 'Languages']);

        // Add the service_renewal_error email group
        $this->Record->query("INSERT INTO `email_groups` (`id`, `action`, `type`, `notice_type`, `plugin_dir`, `tags`)
            VALUES (
                NULL,
                'service_renewal_error',
                'staff',
                'to',
                NULL,
                '{staff.first_name},{staff.last_name},{client.id_code},{client.first_name},{client.last_name},
                {client.email},{service.name},{package.name}'
            );");
        $email_group_id = $this->Record->lastInsertId();

        // Fetch all companies
        $companies = $this->Companies->getAll();

        foreach ($companies as $company) {
            // Fetch all languages installed for this company
            $languages = $this->Languages->getAll($company->id);

            // Add the service_renewal_error email template for each installed language
            foreach ($languages as $language) {
                // Fetch the service_cancel_error email to copy fields from
                $cancel_email = $this->Emails->getByType($company->id, 'service_cancel_error', $language->code);

                if ($cancel_email) {
                    $vars = [
                        'email_group_id' => $email_group_id,
                        'company_id' => $company->id,
                        'lang' => $language->code,
                        'from' => $cancel_email->from,
                        'from_name' => $cancel_email->from_name,
                        'subject' => 'Service Renewal Error',
                        'text' => 'Hi {staff.first_name},

There was an error renewing the following service:

{package.name} {service.name}
--
{client.id_code}
{client.first_name} {client.last_name}
{client.email}

Manage Service (http://{admin_uri}clients/editservice/{client.id}/{service.id}/)

The service may need to be modified so that it can be renewed automatically.
{% if errors %}{% for type in errors %}{% for error in type %}
Error: {error}

{% endfor %}{% endfor %}{% endif %}',
                        'html' => '<p>
	Hi {staff.first_name},<br />
	<br />
    There was an error renewing the following service:<br />
	<br />
	{package.name} {service.name}<br />
	--<br />
	{client.id_code}<br />
	{client.first_name} {client.last_name}<br />
	{client.email}</p>
<p>
	<a href="http://{admin_uri}clients/editservice/{client.id}/{service.id}/">Manage Service</a></p>
<p>
    The service may need to be modified so that it can be renewed automatically.<br />
	{% if errors %}{% for type in errors %}{% for error in type %}<br />
	Error: {error}</p>
<p>
	{% endfor %}{% endfor %}{% endif %}</p>',
                        'email_signature_id' => $cancel_email->email_signature_id,
                        'status' => 'active'
                    ];

                    $this->Record->insert('emails', $vars);
                }
            }
        }
    }

    /**
     * Add BCC notice settings for staff members
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addRenewalErrorBccNotices($undo = false)
    {
        Loader::loadModels($this, ['StaffGroups', 'Staff']);

        // Fetch all staff groups
        $staff_groups = $this->StaffGroups->getAll();

        // Add BCC notice
        foreach ($staff_groups as $staff_group) {
            if ($undo) {
                $this->Record->from('staff_group_notices')->where('action', '=', 'service_renewal_error')->delete();
            } else {
                $notices = $this->StaffGroups->getNotices($staff_group->id);
                foreach ($notices as $notice) {
                    if ($notice->action == 'service_cancel_error') {
                        $this->Record->insert(
                            'staff_group_notices',
                            [
                                'staff_group_id' => $staff_group->id,
                                'action' => 'service_renewal_error',
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
                $this->Record->from('staff_notices')->where('action', '=', 'service_renewal_error')->delete();
            } else {
                $notices = $this->Staff->getNotices($staff_member->id);
                foreach ($notices as $notice) {
                    if ($notice->action == 'service_cancel_error') {
                        $this->Record->insert(
                            'staff_notices',
                            [
                                'staff_group_id' => $notice->staff_group_id,
                                'staff_id' => $staff_member->id,
                                'action' => 'service_renewal_error',
                            ]
                        );

                        break;
                    }
                }
            }
        }
    }

    /**
     * Add index on client ID for the client_settings table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addClientSettingIndex($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `client_settings` DROP INDEX `client_id`');
        } else {
            $this->Record->query('ALTER TABLE `client_settings` ADD INDEX `client_id` (`client_id`)');
        }
    }

    /**
     * Updates the config
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function updateConfig($undo = false)
    {
        if ($undo) {
            // No need to undo anything
        } else {
            // Add Blesta.max_records
            if (file_exists(CONFIGDIR . 'blesta.php') && file_exists(CONFIGDIR . 'blesta-new.php')) {
                $this->mergeConfig(CONFIGDIR . 'blesta.php', CONFIGDIR . 'blesta-new.php');
            }
        }
    }

    /**
     * Add an index for the `sent` column of the `log_emails` table
     *
     * @param bool $undo True to undo the change, false to perform the change
     */
    private function addEmailSentIndex($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `log_emails` DROP INDEX `sent`');
        } else {
            $this->Record->query('ALTER TABLE `log_emails` ADD INDEX `sent` (`sent`)');
        }
    }

    /**
     * Add the client_qty field to the packages table
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function addPackageClientQty($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `packages` DROP `client_qty`;');
        } else {
            $this->Record->query('ALTER TABLE `packages`
                ADD `client_qty` INT UNSIGNED NULL DEFAULT NULL AFTER `qty`;
            ');
        }
    }
}
