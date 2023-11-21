<?php
/**
 * Upgrades to version 3.3.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_3_0B1 extends UpgradeUtil
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
            'updateSettings',
            'priceOverride',
            'updateInvoices',
            'addEmailTemplates',
            'inheritSettings',
            'addPackageProration',
            'addModuleClientMeta'
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
     * Update settings
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function updateSettings($undo = false)
    {
        $companies = $this->Record->select()->from('companies')->fetchAll();

        if ($undo) {
            // Delete client_change_service_term
            foreach ($companies as $company) {
                $this->Record->from('company_settings')->
                    where('company_id', '=', $company->id)->
                    where(
                        'key',
                        'in',
                        ['client_change_service_term', 'inv_type', 'inv_proforma_format', 'inv_proforma_start']
                    )->
                    delete();
            }
        } else {
            // Add setting client_change_service_term
            foreach ($companies as $company) {
                $settings = [
                    [
                        'key' => 'client_change_service_term',
                        'company_id' => $company->id,
                        'value' => 'false'
                    ],
                    [
                        'key' => 'inv_type',
                        'company_id' => $company->id,
                        'value' => 'standard'
                    ],
                    [
                        'key' => 'inv_proforma_format',
                        'company_id' => $company->id,
                        'value' => 'PROFORMA-{num}'
                    ],
                    [
                        'key' => 'inv_proforma_start',
                        'company_id' => $company->id,
                        'value' => '1'
                    ],
                    [
                        'key' => 'client_change_service_package',
                        'company_id' => $company->id,
                        'value' => 'false'
                    ],
                ];

                foreach ($settings as $values) {
                    $this->Record->insert('company_settings', $values);
                }
            }
        }
    }

    /**
     * Sets a new inheritable field for company/system settings
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function inheritSettings($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `settings` DROP `inherit`;');
            $this->Record->query('ALTER TABLE `company_settings` DROP `inherit`;');
        } else {
            $this->Record->query("ALTER TABLE `settings` ADD `inherit` TINYINT(1) NOT NULL DEFAULT '1' ;");
            $this->Record->query("ALTER TABLE `company_settings` ADD `inherit` TINYINT(1) NOT NULL DEFAULT '1' ;");

            // Default tax_id to not inheritable
            $this->Record->query("UPDATE `settings` SET `inherit` = '0' WHERE `settings`.`key` = 'tax_id';");
            $this->Record->query("UPDATE `company_settings`
                SET `inherit` = '0' WHERE `company_settings`.`key` = 'tax_id';");
        }
    }

    /**
     * Updates invoices added proforma as status
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function updateInvoices($undo = false)
    {
        if ($undo) {
            $this->Record->query("ALTER TABLE `invoices`
                CHANGE `status` `status` ENUM( 'active', 'draft', 'void' )
                    CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active';");
        } else {
            $this->Record->query("ALTER TABLE `invoices`
                CHANGE `status` `status` ENUM( 'active', 'proforma', 'draft', 'void' )
                    CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active';");
        }
    }

    /**
     * Adds price override to services
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function priceOverride($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `services` DROP `override_price`;');
            $this->Record->query('ALTER TABLE `services` DROP `override_currency`;');
        } else {
            $this->Record->query('ALTER TABLE `services`
                ADD `override_price` DECIMAL( 12, 4 ) NULL DEFAULT NULL AFTER `qty`;');
            $this->Record->query('ALTER TABLE `services`
                ADD `override_currency` VARCHAR( 3 ) NULL DEFAULT NULL AFTER `override_price`;');
        }
    }

    /**
     * Adds email templates, i.e. the tax liability report
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addEmailTemplates($undo = false)
    {
        if ($undo) {
            return;
        }

        Loader::loadModels($this, ['Companies', 'Emails', 'Languages']);

        // Add the tax liability report email template group
        $this->Record->query("INSERT INTO `email_groups` (`id`, `action`, `type`, `notice_type`, `plugin_dir`, `tags`)
            VALUES (
                NULL,
                'report_tax_liability',
                'staff',
                'to',
                NULL,
                '{staff.first_name},{staff.last_name},{company.name}'
            );");
        $email_group_id = $this->Record->lastInsertId();

        // Fetch all companies
        $companies = $this->Companies->getAll();

        foreach ($companies as $company) {
            // Fetch all languages installed for this company
            $languages = $this->Languages->getAll($company->id);

            // Add the tax liability report email template for each installed language
            foreach ($languages as $language) {
                // Fetch the aging invoices email to copy fields from
                $report_email = $this->Emails->getByType($company->id, 'report_ar', $language->code);

                if ($report_email) {
                    $vars = [
                        'email_group_id' => $email_group_id,
                        'company_id' => $company->id,
                        'lang' => $language->code,
                        'from' => $report_email->from,
                        'from_name' => $report_email->from_name,
                        'subject' => 'Monthly Tax Liability Report',
                        'text' => 'Hi {staff.first_name},

A Tax Liability Report has been generated for {company.name} and is attached to this email as a CSV file.',
                        'html' => '<p>Hi {staff.first_name},</p>
<p>A Tax Liability Report has been generated for {company.name} and is attached to this email as a CSV file.</p>',
                        'email_signature_id' => $report_email->email_signature_id
                    ];

                    $this->Record->insert('emails', $vars);
                }
            }
        }
    }

    /**
     * Adds fields to the package table for proration
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPackageProration($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `packages` DROP `prorata_day`;');
            $this->Record->query('ALTER TABLE `packages` DROP `prorata_cutoff`;');
        } else {
            $this->Record->query('ALTER TABLE `packages` ADD `prorata_day` TINYINT(3) UNSIGNED NULL DEFAULT NULL ;');
            $this->Record->query('ALTER TABLE `packages` ADD `prorata_cutoff` TINYINT(3) UNSIGNED NULL DEFAULT NULL ;');
        }
    }

    /**
     * Adds module_client_meta table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addModuleClientMeta($undo = false)
    {
        if ($undo) {
            try {
                $this->Record->drop('module_client_meta', true);
            } catch (Exception $e) {
                // Nothing to do
            }
        } else {
            $this->Record->
                setField('module_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('module_row_id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0])->
                setField('client_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('key', ['type' => 'varchar', 'size' => 32])->
                setField('value', ['type' => 'text', 'is_null' => true, 'default' => null])->
                setField('serialized', ['type' => 'tinyint', 'size' => 1, 'default' => 0])->
                setField('encrypted', ['type' => 'tinyint', 'size' => 1, 'default' => 0])->
                setKey(['client_id', 'key', 'module_id', 'module_row_id'], 'primary')->
                create('module_client_meta');
        }
    }
}
