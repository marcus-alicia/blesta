<?php
/**
 * Upgrades to version 3.4.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_4_0B1 extends UpgradeUtil
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
     * @retrun array A numerically indexed array of tasks to execute for the upgrade process
     */
    public function tasks()
    {
        return [
            'addNonmerchantPaymentEmail',
            'addCompanySettings',
            'addIndexes',
            'updatePlugins',
            'updateAccountRegistrationEmail',
            'addPackageGroupDescription',
            'addDisplayPaymentsSetting',
            'addContactPermissions',
            'addSessionIp'
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
     * Adds a new email template for non merchant payments
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addNonmerchantPaymentEmail($undo = false)
    {
        if ($undo) {
            return;
        }

        Loader::loadModels($this, ['Companies', 'Emails', 'Languages']);

        // Add the payment_nonmerchant_approved email group
        $this->Record->query("INSERT INTO `email_groups` (`id`, `action`, `type`, `notice_type`, `plugin_dir`, `tags`)
            VALUES (
                NULL,
                'payment_nonmerchant_approved',
                'client',
                'bcc',
                NULL,
                '{contact.first_name},{contact.last_name},{transaction.amount},{transaction.currency},
                {transaction.transaction_id},{date_added}'
            );");
        $email_group_id = $this->Record->lastInsertId();

        // Fetch all companies
        $companies = $this->Companies->getAll();

        foreach ($companies as $company) {
            // Fetch all languages installed for this company
            $languages = $this->Languages->getAll($company->id);

            // Add the payment_nonmerchant_approved email template for each installed language
            foreach ($languages as $language) {
                // Fetch the payment_manual_approved email to copy fields from
                $payment_email = $this->Emails->getByType($company->id, 'payment_manual_approved', $language->code);

                if ($payment_email) {
                    $vars = [
                        'email_group_id' => $email_group_id,
                        'company_id' => $company->id,
                        'lang' => $language->code,
                        'from' => $payment_email->from,
                        'from_name' => $payment_email->from_name,
                        'subject' => 'Payment Received',
                        'text' => 'Hi {contact.first_name},

We have received your {transaction.gateway_name} payment in the amount of {transaction.amount | currency_format transaction.currency} and it has been applied to your account. Please keep this email as a receipt for your records.

Amount: {transaction.amount | currency_format transaction.currency}
Transaction Number: {transaction.transaction_id}

Thank you for your business!',
                        'html' => '<p>
	Hi {contact.first_name},<br />
	<br />
	We have received your {transaction.gateway_name} payment in the amount of {transaction.amount | currency_format transaction.currency} and it has been applied to your account. Please keep this email as a receipt for your records.<br />
	<br />
	Amount: {transaction.amount | currency_format transaction.currency}<br />
	Transaction Number: {transaction.transaction_id}<br />
	<br />
	Thank you for your business!</p>'
                    ];

                    $this->Record->insert('emails', $vars);
                }
            }
        }
    }

    /**
     * Adds new company settings
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addCompanySettings($undo = false)
    {
        Loader::loadModels($this, ['Companies']);

        $company_settings = ['send_payment_notices' => 'true', 'show_client_tax_id' => 'true'];

        // Fetch all companies
        $companies = $this->Companies->getAll();

        // Add or remove the settings
        foreach ($companies as $company) {
            foreach ($company_settings as $setting => $default_value) {
                if ($undo) {
                    $this->Record->from('company_settings')
                        ->where('key', '=', $setting)
                        ->delete(['company_settings.*']);
                } else {
                    $this->Record->insert(
                        'company_settings',
                        ['key' => $setting, 'company_id' => $company->id, 'value' => $default_value]
                    );
                }
            }
        }
    }

    /**
     * Adds the inv_display_payments company setting
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addDisplayPaymentsSetting($undo = false)
    {
        Loader::loadModels($this, ['Companies']);

        // Fetch all companies
        $companies = $this->Companies->getAll();

        // Add or remove the settings
        foreach ($companies as $company) {
            if ($undo) {
                $this->Record->from('company_settings')
                    ->where('key', '=', 'inv_display_payments')
                    ->delete(['company_settings.*']);
            } else {
                $this->Record->insert(
                    'company_settings',
                    ['key' => 'inv_display_payments', 'company_id' => $company->id, 'value' => 'true']
                );
            }
        }
    }

    /**
     * Adds indexes
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addIndexes($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `invoices` DROP INDEX `client_id` ,
				ADD INDEX `client_id` ( `client_id` );');

            $this->Record->query('ALTER TABLE `contacts` DROP INDEX `client_id` ,
				ADD INDEX `client_id` ( `client_id` );');

            $this->Record->query('ALTER TABLE `invoices_recur` DROP INDEX `client_id` ,
				ADD INDEX `client_id` ( `client_id` );');

            $this->Record->query('ALTER TABLE `log_emails` DROP INDEX `to_client_id` ,
				ADD INDEX `to_client_id` ( `to_client_id` );');
        } else {
            $this->Record->query('ALTER TABLE `invoices` DROP INDEX `client_id` ,
				ADD INDEX `client_id` ( `client_id` , `status` , `date_billed` , `date_closed` );');

            $this->Record->query('ALTER TABLE `contacts` DROP INDEX `client_id` ,
				ADD INDEX `client_id` ( `client_id` , `contact_type` );');

            $this->Record->query('ALTER TABLE `invoices_recur` DROP INDEX `client_id` ,
				ADD INDEX `client_id` ( `client_id` , `date_renews` );');

            $this->Record->query('ALTER TABLE `log_emails` DROP INDEX `to_client_id` ,
				ADD INDEX `to_client_id` ( `to_client_id` , `date_sent` );');
        }
    }

    /**
     * Updates plugins to allow enable/disable
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function updatePlugins($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `plugins` DROP INDEX `company_id`;');
            $this->Record->query('ALTER TABLE `plugins` DROP `enabled`;');
        } else {
            $this->Record->query("ALTER TABLE `plugins`
                ADD `enabled` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `version`;");
            $this->Record->query('ALTER TABLE `plugins` ADD INDEX `company_id` ( `company_id` , `enabled` , `dir` );');
        }
    }

    /**
     * Updates the Account Registration (account_welcome) email to remove the default use of the {password} tag
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function updateAccountRegistrationEmail($undo = false)
    {
        if ($undo) {
            return;
        }

        Loader::loadModels($this, ['Companies', 'Emails', 'Languages']);

        // Fetch all companies
        $companies = $this->Companies->getAll();

        foreach ($companies as $company) {
            // Fetch all languages installed for this company
            $languages = $this->Languages->getAll($company->id);

            // Update the account_welcome email template for each installed language
            foreach ($languages as $language) {
                // Fetch the account_welcome email to update
                $welcome_email = $this->Emails->getByType($company->id, 'account_welcome', $language->code);

                // Update the welcome email to not use the {password} tag by default
                if ($welcome_email) {
                    // Only replace exact matches
                    $vars = [
                        'text' => str_replace(
                            'Password: {password}',
                            'Password: (Use the password you signed up with)',
                            $welcome_email->text
                        ),
                        'html' => str_replace(
                            'Password: {password}<br />',
                            'Password: (Use the password you signed up with)<br />',
                            $welcome_email->html
                        )
                    ];

                    if ($welcome_email->text != $vars['text'] || $welcome_email->html != $vars['html']) {
                        $this->Record->where('id', '=', $welcome_email->id)->update('emails', $vars, ['text', 'html']);
                    }
                }
            }
        }
    }

    /**
     * Updates the package_groups table to add a column for description
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPackageGroupDescription($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `package_groups` DROP `description`;');
        } else {
            $this->Record->query('ALTER TABLE `package_groups` ADD `description` TEXT NULL DEFAULT NULL AFTER `name`;');
        }
    }

    /**
     * Add contact permissions
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addContactPermissions($undo = false)
    {
        if ($undo) {
            $this->Record->drop('contact_permissions');
        } else {
            $this->Record->
                setField('contact_id', ['type' => 'int', 'size' => 10])->
                setField('area', ['type' => 'varchar', 'size' => 255])->
                setKey(['contact_id', 'area'], 'primary')->
                create('contact_permissions');
        }
    }

    /**
     * Add session IP address tracking
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addSessionIp($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `staff_groups` DROP `session_lock`;');
        } else {
            $this->Record->query("ALTER TABLE `staff_groups`
                ADD `session_lock` TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER `name`;");
            $this->Record->query("UPDATE `staff_groups` SET `session_lock`='0';");
        }
    }
}
