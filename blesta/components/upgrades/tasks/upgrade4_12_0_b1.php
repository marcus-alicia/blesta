<?php
/**
 * Upgrades to version 4.12.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_12_0B1 extends UpgradeUtil
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
            'addVerificationEmail',
            'addEmailVerifications',
            'addPackagesHiddenColumn',
            'addPackageGroupsHiddenColumn',
            'addPricingsPriceTransferColumn',
            'addModuleTypes',
            'addStaffNumberMobileColumn',
            'addMessengerTables',
            'addMessengerPagePermissions',
            'addMailTestPermissions',
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
     * Adds a new email template for email verification
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addVerificationEmail($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            Loader::loadModels($this, ['Companies', 'Emails', 'Languages']);

            // Add the verify_email email group
            $this->Record->insert(
                'email_groups',
                [
                    'action' => 'verify_email',
                    'type' => 'client',
                    'tags' => '{verification_url},{contact.first_name},{contact.last_name},{username},{ip_address}'
                ]
            );
            $email_group_id = $this->Record->lastInsertId();

            // Fetch all companies
            $companies = $this->Companies->getAll();

            foreach ($companies as $company) {
                // Fetch all languages installed for this company
                $languages = $this->Languages->getAll($company->id);

                // Add the verify_email email template for each installed language
                foreach ($languages as $language) {
                    // Fetch the forgot_username email to copy fields from
                    $forgot_username = $this->Emails->getByType($company->id, 'forgot_username', $language->code);

                    if ($forgot_username) {
                        $vars = [
                            'email_group_id' => $email_group_id,
                            'company_id' => $company->id,
                            'lang' => $language->code,
                            'from' => $forgot_username->from,
                            'from_name' => $forgot_username->from_name,
                            'subject' => 'Verify your Email Address',
                            'text' => 'Hi {contact.first_name},

Some features of your account may be restricted, or orders may be held, until your email address is verified. To verify your email address, please click the link below or copy and paste it into your browser.
http://{verification_url}',
                            'html' => '<p>Hi {contact.first_name},</p>
                            <p>Some features of your account may be restricted, or orders may be held, until your email address is verified. To verify your email address, please click the link below or copy and paste it into your browser.<br />http://{verification_url}</p>',
                            'email_signature_id' => $forgot_username->email_signature_id,
                            'status' => 'active'
                        ];

                        $this->Record->insert('emails', $vars);
                    }
                }
            }
        }
    }

    /**
     * Adds a email verifications table to the database
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addEmailVerifications($undo = false)
    {
        if ($undo) {
            // Drop plugin_cards table
            $this->Record->drop('email_verifications');
        } else {
            // Create plugin_cards table
            $this->Record
                ->setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
                ->setField('contact_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                ->setField('email', ['type' => 'varchar', 'size' => 255])
                ->setField('token', ['type' => 'varchar', 'size' => 255])
                ->setField('verified', ['type' => 'tinyint', 'size' => 1, 'default' => 0])
                ->setField('redirect_url', ['type' => 'varchar', 'size' => 255, 'is_null' => true, 'default' => null])
                ->setField('date_sent', ['type' => 'datetime'])
                ->setKey(['contact_id', 'email'], 'unique')
                ->setKey(['id'], 'primary')
                ->create('email_verifications', true);

            // Update company settings
            Loader::loadModels($this, ['Companies']);
            $companies = $this->Companies->getAll();

            foreach ($companies as $company) {
                $this->Companies->setSetting($company->id, 'email_verification', 'false');
                $this->Companies->setSetting($company->id, 'prevent_unverified_payments', 'false');
            }
        }
    }

    /**
     * Adds a "hidden" column to the packages table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPackagesHiddenColumn($undo = false)
    {
        if ($undo) {
            $sql = 'ALTER TABLE `packages` DROP `hidden`';
            $this->Record->query($sql);
        } else {
            $sql = 'ALTER TABLE `packages` ADD `hidden` TINYINT(1) NULL DEFAULT 0 AFTER `status`';
            $this->Record->query($sql);
        }
    }

    /**
     * Adds a "hidden" column to the package groups table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPackageGroupsHiddenColumn($undo = false)
    {
        if ($undo) {
            $sql = 'ALTER TABLE `package_groups` DROP `hidden`';
            $this->Record->query($sql);
        } else {
            $sql = 'ALTER TABLE `package_groups` ADD `hidden` TINYINT(1) NULL DEFAULT 0 AFTER `type`';
            $this->Record->query($sql);
        }
    }

    /**
     * Adds "price_transfer" column to the pricings table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPricingsPriceTransferColumn($undo = false)
    {
        if ($undo) {
            $sql = 'ALTER TABLE `pricings` DROP `price_transfer`';
            $this->Record->query($sql);
        } else {
            $sql = 'ALTER TABLE `pricings` ADD `price_transfer` DECIMAL(19,4) NULL DEFAULT NULL AFTER `price_renews`';
            $this->Record->query($sql);
        }
    }

    /**
     * Add module types to the system
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addModuleTypes($undo = false)
    {
        if ($undo) {
            // Drop module_types table
            $this->Record->drop('module_types');

            // Drop type_id column to the modules table
            $sql = 'ALTER TABLE `modules` DROP `type_id`';
            $this->Record->query($sql);
        } else {
            // Create module_types table
            $this->Record
                ->setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
                ->setField('name', ['type' => 'varchar', 'size' => 64])
                ->setKey(['name'], 'unique')
                ->setKey(['id'], 'primary')
                ->create('module_types', true);

            // Add default module types
            $this->Record->insert('module_types', ['name' => 'generic']);
            $this->Record->insert('module_types', ['name' => 'registrar']);

            // Add type_id column to the modules table
            $sql = 'ALTER TABLE `modules` ADD `type_id` INT(10) UNSIGNED NOT NULL DEFAULT 1 AFTER `company_id`';
            $this->Record->query($sql);
        }
    }

    /**
     * Adds "number_mobile" column to the staff table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addStaffNumberMobileColumn($undo = false)
    {
        if ($undo) {
            $sql = 'ALTER TABLE `staff` DROP `number_mobile`';
            $this->Record->query($sql);
        } else {
            $sql = 'ALTER TABLE `staff` ADD `number_mobile` VARCHAR(64) NULL DEFAULT NULL AFTER `email_mobile`';
            $this->Record->query($sql);
        }
    }

    /**
     * Adds the messenger related tables to the database
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addMessengerTables($undo = false)
    {
        if ($undo) {
            // Drop tables
            $this->Record->drop('messengers');
            $this->Record->drop('messenger_meta');
            $this->Record->drop('log_messenger');
            $this->Record->drop('message_groups');
            $this->Record->drop('messages');
            $this->Record->drop('message_content');
        } else {
            // Create messengers table
            $this->Record
                ->setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
                ->setField('dir', ['type' => 'varchar', 'size' => 64])
                ->setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                ->setField('name', ['type' => 'varchar', 'size' => 128])
                ->setField('version', ['type' => 'varchar', 'size' => 16])
                ->setKey(['company_id', 'dir'], 'unique')
                ->setKey(['id'], 'primary')
                ->create('messengers', true);

            // Create messenger_meta table
            $this->Record
                ->setField('messenger_id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
                ->setField('key', ['type' => 'varchar', 'size' => 32])
                ->setField('value', ['type' => 'text'])
                ->setField('serialized', ['type' => 'tinyint', 'size' => 1, 'default' => 0])
                ->setField('encrypted', ['type' => 'tinyint', 'size' => 1, 'default' => 0])
                ->setKey(['messenger_id', 'key'], 'primary')
                ->create('messenger_meta', true);

            // Create log_messenger table
            $this->Record
                ->setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
                ->setField('to_user_id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'is_null' => true, 'default' => null])
                ->setField('messenger_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                ->setField('direction', ['type' => 'enum', 'size' => "'input','output'"])
                ->setField('data', ['type' => 'mediumtext'])
                ->setField('date_added', ['type' => 'datetime'])
                ->setField('success', ['type' => 'tinyint', 'size' => 1, 'default' => 0])
                ->setField('group', ['type' => 'char', 'size' => 8])
                ->setKey(['messenger_id', 'to_user_id'], 'index')
                ->setKey(['id'], 'primary')
                ->create('log_messenger', true);

            // Create message_groups table
            $this->Record
                ->setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
                ->setField('action', ['type' => 'varchar', 'size' => 64])
                ->setField('type', ['type' => 'enum', 'size' => "'client','staff','shared'", 'default' => 'client'])
                ->setField('plugin_dir', ['type' => 'varchar', 'size' => 64, 'is_null' => true, 'default' => null])
                ->setField('tags', ['type' => 'text'])
                ->setKey(['type'], 'index')
                ->setKey(['action'], 'unique')
                ->setKey(['id'], 'primary')
                ->create('message_groups', true);

            // Create messages table
            $this->Record
                ->setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
                ->setField('message_group_id', ['type' => 'int', 'size' => 10])
                ->setField('company_id', ['type' => 'int', 'size' => 10])
                ->setField('type', ['type' => 'enum', 'size' => "'sms'", 'default' => 'sms'])
                ->setField('status', ['type' => 'enum', 'size' => "'active','inactive'", 'default' => 'active'])
                ->setKey(['message_group_id'], 'index')
                ->setKey(['id'], 'primary')
                ->create('messages', true);

            // Create message_content table
            $this->Record
                ->setField('message_id', ['type' => 'int', 'size' => 10])
                ->setField('lang', ['type' => 'varchar', 'size' => 5])
                ->setField('content', ['type' => 'mediumtext'])
                ->setKey(['message_id', 'lang'], 'primary')
                ->create('message_content', 'type', true);

            // Add Blesta.auto_delete_messenger_logs
            if (file_exists(CONFIGDIR . 'blesta.php')) {
                $this->addConfig(CONFIGDIR . 'blesta.php', 'Blesta.auto_delete_messenger_logs', false);
            }

            // Add required company settings
            Loader::loadModels($this, ['Companies']);
            $companies = $this->Companies->getAll();

            foreach ($companies as $company) {
                $this->Companies->setSetting($company->id, 'messenger_configuration', base64_encode(serialize([])));
            }
        }
    }

    /**
     * Adds permissions for messenger pages
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addMessengerPagePermissions($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            // Get the settings permission group
            Loader::loadModels($this, ['Permissions']);
            Loader::loadComponents($this, ['Acl']);
            $group = $this->Permissions->getGroupByAlias('admin_settings');

            // Add the new messenger permissions
            if ($group) {
                $actions = ['manage', 'install', 'uninstall', 'upgrade', 'configuration', 'templates', 'edittemplate'];
                $staff_groups = $this->Record->select(['id', 'company_id'])->from('staff_groups')->fetchAll();

                foreach ($actions as $action) {
                    $this->Permissions->add([
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_messengers_' . $action,
                        'alias' => 'admin_company_messengers',
                        'action' => $action
                    ]);
                    foreach ($staff_groups as $staff_group) {
                        $authorized = $this->Permissions->authorized(
                            'staff_group_' . $staff_group->id,
                            'admin_company_modules',
                            '*',
                            'staff',
                            $staff_group->company_id
                        );
                        if ($authorized) {
                            $this->Acl->allow('staff_group_' . $staff_group->id, 'admin_company_messengers', $action);
                        } else {
                            $this->Acl->deny('staff_group_' . $staff_group->id, 'admin_company_messengers', $action);
                        }
                    }
                }

                $this->Permissions->add([
                    'group_id' => $group->id,
                    'name' => 'StaffGroups.permissions.admin_company_messengers',
                    'alias' => 'admin_company_messengers',
                    'action' => '*'
                ]);
                foreach ($staff_groups as $staff_group) {
                    $authorized = $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_company_modules',
                        '*',
                        'staff',
                        $staff_group->company_id
                    );
                    if ($authorized) {
                        $this->Acl->allow('staff_group_' . $staff_group->id, 'admin_company_messengers', '*');
                    } else {
                        $this->Acl->deny('staff_group_' . $staff_group->id, 'admin_company_messengers', '*');
                    }
                }
            }
        }
    }

    /**
     * Adds permission for mail settings test
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addMailTestPermissions($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            // Get the settings permission group
            Loader::loadModels($this, ['Permissions']);
            Loader::loadComponents($this, ['Acl']);
            $group = $this->Permissions->getGroupByAlias('admin_settings');

            // Add the new mail settings test
            if ($group) {
                $this->Permissions->add([
                    'group_id' => $group->id,
                    'name' => 'StaffGroups.permissions.admin_company_emails_mailtest',
                    'alias' => 'admin_company_emails',
                    'action' => 'mailtest'
                ]);

                $staff_groups = $this->Record->select(['id', 'company_id'])->from('staff_groups')->fetchAll();
                foreach ($staff_groups as $staff_group) {
                    $authorized = $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_company_emails',
                        'mail',
                        'staff',
                        $staff_group->company_id
                    );
                    if ($authorized) {
                        $this->Acl->allow('staff_group_' . $staff_group->id, 'admin_company_emails', 'mailtest');
                    } else {
                        $this->Acl->deny('staff_group_' . $staff_group->id, 'admin_company_emails', 'mailtest');
                    }
                }
            }
        }
    }
}
