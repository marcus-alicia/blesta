<?php
/**
 * Upgrades to version 4.4.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_4_0B1 extends UpgradeUtil
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
            'updatePackageOptionTypes',
            'setServiceOptionValues',
            'updateCountryNames',
            'updateServiceUnsuspensionNotice',
            'removeOldJavascriptFiles',
            'updateInvoiceDeliveryEmails',
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
     * Adds new package_options.type values (text, textarea, password)
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function updatePackageOptionTypes($undo = false)
    {
        if ($undo) {
            $this->Record->query(
                "ALTER TABLE `package_options`
                CHANGE `type` `type` ENUM('checkbox','radio','select','quantity')
                CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'select'"
            );
        } else {
            // Add new package option types (text, textarea, password)
            $this->Record->query(
                "ALTER TABLE `package_options`
                CHANGE `type` `type` ENUM('checkbox','radio','select','quantity','text','textarea','password')
                CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'select'"
            );
        }
    }

    /**
     * Adds new columns to service_options to include the selected option value
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function setServiceOptionValues($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `service_options` DROP `value`, DROP `encrypted`');
        } else {
            // Add the `value` and `encrypted` columns to `service_options`
            $this->Record->query(
                "ALTER TABLE `service_options`
                ADD `value` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `qty`,
                ADD `encrypted` TINYINT(1) NOT NULL DEFAULT '0' AFTER `value`"
            );

            // Move all of the existing package option values from `package_option_values` to `service_options`
            $this->Record->query(
                'UPDATE `service_options`, (
                    SELECT `so`.`id` AS `service_option_id`, `pov`.`value`
                    FROM `service_options` `so`
                    INNER JOIN `package_option_pricing` `pop` ON `pop`.`id` = `so`.`option_pricing_id`
                    INNER JOIN `package_option_values` `pov` ON `pov`.`id` = `pop`.`option_value_id`
                    GROUP BY `so`.`id`
                ) AS `option_value`
                SET `service_options`.`value` = `option_value`.`value`
                WHERE `option_value`.`service_option_id` = `service_options`.`id`'
            );
        }
    }

    /**
     * Updates the Israel country-name to its Hebrew translation
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function updateCountryNames($undo = false)
    {
        if ($undo) {
            // Nothing to do
            return;
        }

        // Set Israel's country name in Hebrew
        $this->Record->query("UPDATE `countries` SET `alt_name` = 'ישראל' WHERE `alpha2` = 'IL'");
    }

    /**
     * Updates the service_unsuspension email group to add it as a BCC notice type
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function updateServiceUnsuspensionNotice($undo = false)
    {
        if ($undo) {
            // No undoing this action
            return;
        }

        // Make the service_unspension notice a BCC type
        $this->Record->query(
            "UPDATE `email_groups` SET `notice_type` = 'bcc' WHERE `email_groups`.`action` = 'service_unsuspension'"
        );

        // Fetch and update all staff group notices that have
        // service suspensions enabled to also enable service unsuspension
        $this->Record->query(
            "INSERT INTO `staff_group_notices` (`staff_group_id`, `action`)
            SELECT `staff_group_id`, 'service_unsuspension' AS `action`
            FROM `staff_group_notices`
            WHERE `staff_group_notices`.`action` = 'service_suspension'"
        );

        // Set all staff subscribed to the service suspension BCC notice
        // to also be subscribed to the service unsuspension BCC notice
        $this->Record->query(
            "INSERT INTO `staff_notices` (`staff_group_id`, `staff_id`, `action`)
            SELECT `staff_group_id`, `staff_id`, 'service_unsuspension' AS `action`
            FROM `staff_notices`
            WHERE `staff_notices`.`action` = 'service_suspension'"
        );
    }

    /**
     * Removes all of the extraneous/old UI javascript files
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function removeOldJavascriptFiles($undo = false)
    {
        if ($undo) {
            // Nothing to do
            return;
        }

        // Old javascript files that can be deleted
        $admin_path = VIEWDIR . 'admin' . DS . 'default' . DS . 'javascript' . DS;
        $client_path = VIEWDIR . 'client' . DS . 'bootstrap' . DS . 'javascript' . DS;
        $files = [
            'admin' => [
                'blesta-4.3.0.min.js',
                'blesta-4.1.0.min.js',
                'blesta-4.0.0.min.js',
                'jquery-blesta-3.5.0.js',
                'jquery-blesta-0.1.0.js'
            ],
            'client' => [
                'blesta-4.3.0.min.js',
                'jquery-client-4.1.0.js',
                'jquery-client-3.5.0.js',
                'jquery-client-3.2.0.js',
                'jquery-client-0.1.0.js'
            ]
        ];

        foreach ($files['admin'] as $file) {
            $path = $admin_path . $file;
            if (file_exists($path)) {
                @unlink($path);
            }
        }

        foreach ($files['client'] as $file) {
            $path = $client_path . $file;
            if (file_exists($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * Updates the invoice_delivery_* templates to add a tag
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function updateInvoiceDeliveryEmails($undo = false)
    {
        // Take a portion of the HTML/Text fields for the unpaid delivery email and update them
        $html_orig = <<< HTML
{% if autodebit %}{% if invoice.autodebit_date_formatted %}Auto debit is enabled for your account, so we'll automatically process the card you have on file on <strong>{invoice.autodebit_date_formatted}</strong> unless payment has been applied sooner.{% else %}If you would like us to automatically charge your card, login to your account at <a href="http://{client_url}">http://{client_url}</a> to set up auto debit.{% endif %}{% else %}If you would like us to automatically charge your card, login to your account at <a href="http://{client_url}">http://{client_url}</a> to set up auto debit.{% endif %}<br />
HTML;
        $text_orig = <<< TEXT
{% if autodebit %}{% if invoice.autodebit_date_formatted %}Auto debit is enabled for your account, so we'll automatically process the card you have on file on {invoice.autodebit_date_formatted} unless payment has been applied sooner.{% else %}If you would like us to automatically charge your card, login to your account at http://{client_url} to set up auto debit.{% endif %}{% else %}If you would like us to automatically charge your card, login to your account at http://{client_url} to set up auto debit.{% endif %}
TEXT;
        $html_new = <<< HTML
{% if autodebit %}{% if payment_account %}{% if invoice.autodebit_date_formatted %}Auto debit is enabled for your account, so we'll automatically process the card you have on file on <strong>{invoice.autodebit_date_formatted}</strong> unless payment has been applied sooner.{% else %}If you would like us to automatically charge your card, login to your account at <a href="http://{client_url}">http://{client_url}</a> to set up auto debit.{% endif %}{% else %}If you would like us to automatically charge your card, login to your account at <a href="http://{client_url}">http://{client_url}</a> to set up auto debit.{% endif %}{% else %}If you would like us to automatically charge your card, login to your account at <a href="http://{client_url}">http://{client_url}</a> to set up auto debit.{% endif %}<br />
HTML;
        $text_new = <<< TEXT
{% if autodebit %}{% if payment_account %}{% if invoice.autodebit_date_formatted %}Auto debit is enabled for your account, so we'll automatically process the card you have on file on {invoice.autodebit_date_formatted} unless payment has been applied sooner.{% else %}If you would like us to automatically charge your card, login to your account at http://{client_url} to set up auto debit.{% endif %}{% else %}If you would like us to automatically charge your card, login to your account at http://{client_url} to set up auto debit.{% endif %}{% else %}If you would like us to automatically charge your card, login to your account at http://{client_url} to set up auto debit.{% endif %}
TEXT;

        // Update the unpaid email template
        $emails = $this->Record->select(['emails.id', 'emails.html', 'emails.text'])
            ->from('emails')
            ->innerJoin('email_groups', 'email_groups.id', '=', 'emails.email_group_id', false)
            ->where('email_groups.action', '=', 'invoice_delivery_unpaid')
            ->fetchAll();

        // Update the HTML/Text for the email templates
        foreach ($emails as $email) {
            $vars = [
                'html' => ($undo
                    ? str_replace($html_new, $html_orig, $email->html)
                    : str_replace($html_orig, $html_new, $email->html)
                ),
                'text' => ($undo
                    ? str_replace($text_new, $text_orig, $email->text)
                    : str_replace($text_orig, $text_new, $email->text)
                )
            ];

            $this->Record->where('id', '=', $email->id)
                ->update('emails', $vars);
        }

        // Update the invoice delivery tags to include the payment_account information
        $old_tags = '{contact.first_name},{contact.last_name},{invoices},{autodebit},{client_url}';
        $new_tags = $old_tags
                . ',{payment_account.first_name},{payment_account.last_name},{payment_account.account_type},'
                . '{payment_account.last4}';

        $this->Record->where('action', 'in', ['invoice_delivery_paid', 'invoice_delivery_unpaid'])
            ->update('email_groups', ['tags' => ($undo ? $old_tags : $new_tags)]);
    }
}
