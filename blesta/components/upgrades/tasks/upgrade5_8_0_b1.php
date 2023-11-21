<?php
/**
 * Upgrades to version 5.8.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade5_8_0B1 extends UpgradeUtil
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
            'createAccountManagementInvitationsTable',
            'addAccountManagementInviteEmail',
            'increasePackageMetaKeySize',
            'addReadOnlyContactFieldSetting',
            'addClientFieldsLink',
            'addPackagesModuleGroupClient',
            'createPackageModuleGroupsTable',
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
     * Creates the required tables by the Account Management system in the database
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function createAccountManagementInvitationsTable($undo = false)
    {
        Loader::loadModels($this, ['Companies']);

        if ($undo) {
            try {
                $this->Record->drop('account_management_invitations');
                $this->Record->query(
                        'ALTER TABLE `contact_permissions` DROP `client_id`;
                        ALTER TABLE `contact_permissions` DROP PRIMARY KEY, ADD PRIMARY KEY (`contact_id`, `area`);'
                    )->closeCursor();
            } catch (Exception $e) {
                // Nothing to do
            }
        } else {
            // account_management_invitations
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('client_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('email', ['type' => 'varchar', 'size' => 255])->
                setField('permissions', ['type' => 'text'])->
                setField('token', ['type' => 'varchar', 'size' => 255])->
                setField(
                    'status',
                    ['type' => 'enum', 'size' => "'accepted','pending','invalid'", 'default' => 'invalid']
                )->
                setKey(['id'], 'primary')->
                create('account_management_invitations', true);
            $this->Record->query(
                    'ALTER TABLE `contact_permissions` ADD `client_id` INT UNSIGNED NULL DEFAULT NULL AFTER `contact_id`;
                    ALTER TABLE `contact_permissions` DROP PRIMARY KEY, ADD UNIQUE INDEX (`contact_id`, `area`, `client_id`);'
                )->closeCursor();
        }
    }

    /**
     * Increases the size of the "key" column on the package_meta table from 32 to 128 characters
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function increasePackageMetaKeySize($undo = false)
    {
        if ($undo) {
            $sql = 'ALTER TABLE `package_meta` CHANGE `key` `key` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;';
            $this->Record->query($sql)->closeCursor();
        } else {
            $sql = 'ALTER TABLE `package_meta` CHANGE `key` `key` VARCHAR(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;';
            $this->Record->query($sql)->closeCursor();
        }
    }

    /**
     * Adds the account management invite email template
     *
     * @param bool $undo True to undo the change, false to perform the change
     */
    private function addAccountManagementInviteEmail($undo = false)
    {
        if ($undo) {
            return;
        }

        Loader::loadModels($this, ['Companies', 'Emails', 'Languages']);

        // Add the quotation delivery email template group
        $this->Record->query("INSERT INTO `email_groups` (`id` , `action` , `type` , `plugin_dir` , `tags`)
            VALUES (
                NULL ,
                'account_management_invite',
                'client',
                NULL ,
                '{contact.first_name} {contact.last_name} {company.name} {verification_url}'
            );")->closeCursor();
        $email_group_id = $this->Record->lastInsertId();

        // Fetch all companies
        $companies = $this->Companies->getAll();

        foreach ($companies as $company) {
            // Fetch all languages installed for this company
            $languages = $this->Languages->getAll($company->id);

            // Add the quotation delivery email template for each installed language
            foreach ($languages as $language) {
                // Fetch the invoice delivery unpaid email to copy fields from
                $service_suspension_email = $this->Emails->getByType($company->id, 'service_suspension', $language->code);

                if ($service_suspension_email) {
                    $vars = [
                        'email_group_id' => $email_group_id,
                        'company_id' => $company->id,
                        'lang' => $language->code,
                        'from' => $service_suspension_email->from,
                        'from_name' => $service_suspension_email->from_name,
                        'subject' => 'A user has invited you to manage their account',
                        'text' => 'Hi there,

{contact.first_name} {contact.last_name} has invited you to manage their account.

To accept or reject this invitation, click the following link: http://{verification_url}/',
                        'html' => '<p>Hi there,</p>

<p>{contact.first_name} {contact.last_name} has invited you to manage their account.</p>

<p>To accept or reject this invitation, click the following link: <a href="http://{verification_url}/">http://{verification_url}/</a></p>',
                        'email_signature_id' => $service_suspension_email->email_signature_id
                    ];

                    $this->Record->insert('emails', $vars);
                }
            }
        }
    }

    /**
     * Adds the new company/client group setting for read only contact fields
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function addReadOnlyContactFieldSetting($undo = false)
    {
        // Add the required contact field setting
        $setting = 'read_only_contact_fields';

        if ($undo) {
            // Remove the new setting
            $this->Record->from('client_group_settings')->where('key', '=', $setting)->delete();
            $this->Record->from('company_settings')->where('key', '=', $setting)->delete();
        } else {
            $value = base64_encode(serialize([]));

            // Add to company settings
            $companies = $this->Record->select()->from('companies')->getStatement();
            foreach ($companies as $company) {
                $this->Record->insert(
                    'company_settings',
                    ['key' => $setting, 'value' => $value, 'company_id' => $company->id]
                );
            }
        }
    }

    /**
     * Adds the new link column to the client_fields table
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function addClientFieldsLink($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `client_fields` DROP `link`;')->closeCursor();
        } else {
            $this->Record->query(
                    'ALTER TABLE `client_fields` ADD `link` MEDIUMTEXT NULL DEFAULT NULL AFTER `name`;'
                )->closeCursor();
        }
    }

    /**
     * Adds the new module group client column to the packages table
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function addPackagesModuleGroupClient($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `packages` DROP `module_group_client`;')->closeCursor();
        } else {
            $this->Record->query(
                    'ALTER TABLE `packages` ADD `module_group_client` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT 0 AFTER `module_group`;'
                )->closeCursor();
        }
    }

    /**
     * Adds the new package_module_groups table
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function createPackageModuleGroupsTable($undo = false)
    {
        Loader::loadModels($this, ['Companies']);

        if ($undo) {
            try {
                $this->Record->drop('package_module_groups');
            } catch (Exception $e) {
                // Nothing to do
            }
        } else {
            // package_module_groups
            $this->Record->
                setField('package_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('module_group_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setKey(['package_id', 'module_group_id'], 'primary')->
                create('package_module_groups', true);
        }
    }
}
