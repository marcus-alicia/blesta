<?php
/**
 * Upgrades to version 4.1.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_1_0B1 extends UpgradeUtil
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
            'addForceLimits',
            'updatePriceFields',
            'addInternalUseField',
            'installNoneModule',
            'dropCouponsType',
            'createLogDirectory',
            'addSettings',
            'updatePluginActions',
            'setThemeSettings',
            'updateAutodebitPendingEmail'
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
     * Updates the price fields in the database from decimal(12,4) to decimal(19,4)
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function updatePriceFields($undo = false)
    {
        $decimal_digits = ($undo ? 12 : 19);

        // coupon_amounts.amount
        $this->Record->query(
            'ALTER TABLE `coupon_amounts` CHANGE `amount` `amount` DECIMAL( ' . $decimal_digits . ", 4 )
            NOT NULL DEFAULT '0.0000';"
        )->closeCursor();

        // currencies.exchange_rate
        $this->Record->query(
            'ALTER TABLE `currencies` CHANGE `exchange_rate` `exchange_rate` DECIMAL( ' . ($undo ? '14' : '21') . ", 6 )
            NOT NULL DEFAULT '0.0000';"
        )->closeCursor();

        // invoices.total, invoices.subtotal, invoices.paid, invoices.previous_due
        $this->Record->query(
            'ALTER TABLE `invoices`
            CHANGE `total` `total` DECIMAL( ' . $decimal_digits . ", 4 ) NOT NULL DEFAULT '0.0000',
            CHANGE `subtotal` `subtotal` DECIMAL( " . $decimal_digits . ", 4 ) NOT NULL DEFAULT '0.0000',
            CHANGE `paid` `paid` DECIMAL( " . $decimal_digits . ", 4 ) NOT NULL DEFAULT '0.0000',
            CHANGE `previous_due` `previous_due` DECIMAL( " . $decimal_digits . ", 4 )
            NOT NULL DEFAULT '0.0000';"
        )->closeCursor();

        // invoice_lines.amount, invoice_lines.qty
        $this->Record->query(
            'ALTER TABLE `invoice_lines`
            CHANGE `amount` `amount` DECIMAL( ' . $decimal_digits . ", 4 ) NOT NULL DEFAULT '0.0000',
            CHANGE `qty` `qty` DECIMAL( " . $decimal_digits . ", 4 ) NOT NULL DEFAULT '1.0000';"
        )->closeCursor();

        // invoice_recur_lines.amount, invoice_recur_lines.qty
        $this->Record->query(
            'ALTER TABLE `invoice_recur_lines`
            CHANGE `amount` `amount` DECIMAL( ' . $decimal_digits . ", 4 ) NOT NULL DEFAULT '0.0000',
            CHANGE `qty` `qty` DECIMAL( " . $decimal_digits . ", 4 ) NOT NULL DEFAULT '1.0000';"
        )->closeCursor();

        // pricings.price, pricings.setup_fee, pricings.cancel_fee
        $this->Record->query(
            'ALTER TABLE `pricings`
            CHANGE `price` `price` DECIMAL( ' . $decimal_digits . ", 4 ) NOT NULL DEFAULT '0.0000',
            CHANGE `setup_fee` `setup_fee` DECIMAL( " . $decimal_digits . ", 4 ) NOT NULL DEFAULT '0.0000',
            CHANGE `cancel_fee` `cancel_fee` DECIMAL( " . $decimal_digits . ", 4 ) NOT NULL DEFAULT '0.0000';"
        )->closeCursor();

        // services.override_price
        $this->Record->query(
            'ALTER TABLE `services` CHANGE `override_price` `override_price`
            DECIMAL( ' . $decimal_digits . ', 4 ) NULL DEFAULT NULL;'
        )->closeCursor();

        // taxes.amount
        $this->Record->query(
            'ALTER TABLE `taxes` CHANGE `amount` `amount` DECIMAL( ' . $decimal_digits . ", 4 )
            NOT NULL DEFAULT '0.0000';"
        )->closeCursor();

        // transactions.amount
        $this->Record->query(
            'ALTER TABLE `transactions` CHANGE `amount` `amount` DECIMAL( ' . $decimal_digits . ", 4 )
            NOT NULL DEFAULT '0.0000';"
        )->closeCursor();

        // transaction_applied.amount
        $this->Record->query(
            'ALTER TABLE `transaction_applied` CHANGE `amount` `amount` DECIMAL( ' . $decimal_digits . ", 4 )
            NOT NULL DEFAULT '0.0000';"
        )->closeCursor();
    }

    /**
     * Install the none module
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function installNoneModule($undo = false)
    {
        // No undo
        if ($undo) {
            return;
        }

        Loader::loadComponents($this, ['Modules']);

        // Fetch all companies to install the None module for
        $companies = $this->Record->select()->from('companies')->fetchAll();

        // Instantiate the module and return the instance
        $module = $this->Modules->create('none');
        $vars = [
            'name' => $module->getName(),
            'version' => $module->getVersion(),
            'class' => 'none'
        ];

        $fields = ['company_id', 'name', 'class', 'version'];

        foreach ($companies as $company) {
            $this->Record->insert('modules', array_merge($vars, ['company_id' => $company->id]), $fields);
        }
    }

    /**
     * Adds coupon field for internal use only
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function addInternalUseField($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `coupons` DROP `internal_use_only`;')->closeCursor();
        } else {
            $this->Record->query(
                'ALTER TABLE `coupons` ADD `internal_use_only` TINYINT(1) NOT NULL DEFAULT 0;'
            )->closeCursor();
        }
    }

    /**
     * Add the force_limits column to the module_groups table
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function addForceLimits($undo = false)
    {
        $this->Record->query(
            'ALTER TABLE `module_groups` ADD `force_limits` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;'
        )->closeCursor();
    }

    /**
     * Drops the coupons type field
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function dropCouponsType($undo = false)
    {
        if ($undo) {
            $this->Record->query(
                "ALTER TABLE `coupons` ADD `type` enum('inclusive','exclusive') COLLATE utf8_unicode_ci
                    NOT NULL DEFAULT 'exclusive' AFTER `status`;"
            )->closeCursor();
        } else {
            $this->Record->query('ALTER TABLE `coupons` DROP `type`;')->closeCursor();
        }
    }

    /**
     * Create the new log directory
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function createLogDirectory($undo = false)
    {
        if ($undo) {
            // Remove the setting
            $this->Record->from('settings')
                ->where('key', '=', 'log_dir')
                ->delete(['settings.*']);

            return;
        }

        // Attempt to create the log directory above the public web directory if it does not already exist
        $webdir = str_replace('/', DS, str_replace('index.php/', '', WEBDIR));
        $public_root_web = rtrim(str_replace($webdir == DS ? '' : $webdir, '', ROOTWEBDIR), DS) . DS;
        $log_dir = realpath(dirname($public_root_web)) . DS . 'logs_blesta' . DS;

        if (!file_exists($log_dir) && @mkdir($log_dir, 0755)) {
            // Attempt to create an .htaccess file to deny access to the directory just in case
            // it's made public and mod rewrite is available to deny access
            $htaccess = <<<HT
Order deny,allow
Deny from all
HT;
            file_put_contents($log_dir . '.htaccess', $htaccess);
        }

        // Save the new log_dir setting
        $this->Record->insert('settings', ['key' => 'log_dir', 'value' => $log_dir]);
    }

    /**
     * Adds the new company/client group setting for required contact fields
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function addSettings($undo = false)
    {
        // Add the required contact field setting
        $setting = 'required_contact_fields';

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
     * Updates the primary key on the plugin_actions table to accommodate
     * multiple identical actions per plugin
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function updatePluginActions($undo = false)
    {
        if ($undo) {
            // Reset the primary key for plugin_actions
            $this->Record->query(
                'ALTER TABLE `plugin_actions` DROP PRIMARY KEY, ADD PRIMARY KEY (`plugin_id`, `action`);'
            )->closeCursor();
        } else {
            // Update the primary key for plugin_actions to include the URI
            $this->Record->query(
                'ALTER TABLE `plugin_actions` DROP PRIMARY KEY, ADD PRIMARY KEY (`plugin_id`, `action`, `uri`);'
            )->closeCursor();
        }
    }

    /**
     * Set colors for the new theme settings
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function setThemeSettings($undo = false)
    {
        $themes = $this->Record->select()->from('themes')->where('type', '=', 'client')->getStatement();
        $record = $this->newRecord();

        if ($undo) {
            foreach ($themes as $theme) {
                $theme->data = unserialize(base64_decode($theme->data));

                unset($theme->data['colors']['theme_highlight_navigation_text_color_top']);
                unset($theme->data['colors']['theme_highlight_navigation_hover_text_color_top']);

                $theme->data = base64_encode(serialize($theme->data));

                $record->where('id', '=', $theme->id)->update('themes', (array)$theme, ['data']);
            }
        } else {
            // Default colors for all themes
            $text_color = 'efefef';
            $hover_text_color = 'ffffff';

            // Darker colors used for the specified light header themes
            $text_color_dark = '4f4f4f';
            $hover_text_color_dark = '8f8f8f';

            $hover_text_color_darker = '2f2f2f';

            $light_themes = ['FOUR', 'Clean', 'Cloudy Day', 'Slate'];

            foreach ($themes as $theme) {
                // Decode data
                $theme->data = unserialize(base64_decode($theme->data));

                // Set new colors
                if (in_array($theme->name, $light_themes)) {
                    $theme->data['colors']['theme_highlight_navigation_text_color_top'] = $text_color_dark;
                    $theme->data['colors']['theme_highlight_navigation_hover_text_color_top'] =
                        ($theme->name == 'Cloudy Day' ? $hover_text_color_darker : $hover_text_color_dark);
                } else {
                    $theme->data['colors']['theme_highlight_navigation_text_color_top'] = $text_color;
                    $theme->data['colors']['theme_highlight_navigation_hover_text_color_top'] = $hover_text_color;
                }

                // Encode data
                $theme->data = base64_encode(serialize($theme->data));

                // Set data
                $record->where('id', '=', $theme->id)->update('themes', (array)$theme, ['data']);
            }
        }
    }

    /**
     * Update autodebit pending email tags
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function updateAutodebitPendingEmail($undo = false)
    {
        if ($undo) {
            // nothing to do
        } else {
            // Get all auto_debit_pending emails
            $emails = $this->Record->select(['emails.*'])
                ->from('email_groups')
                ->innerJoin('emails', 'emails.email_group_id', '=', 'email_groups.id', false)
                ->where('email_groups.action', '=', 'auto_debit_pending')
                ->getStatement();

            $record = $this->newRecord();

            // Update both the html and the text for each email
            foreach ($emails as $email) {
                // Replace the client_url tag with the client_uri tag
                $email_fields = str_replace(
                    '{client_url}',
                    '{client_uri}',
                    ['html' => $email->html, 'text' => $email->text]
                );

                // If the tag does not already have a prefix, add http://
                $email_fields = preg_replace(
                    '/(?<!\/\/){client_uri}/i',
                    'http://{client_uri}',
                    $email_fields
                );

                // Replace the payment_account.type tag with the payment_account.type_name tag
                $email_fields = str_replace(
                    '{payment_account.type}',
                    '{payment_account.type_name}',
                    $email_fields
                );

                $record->where('emails.id', '=', $email->id)->update('emails', $email_fields, ['html', 'text']);
            }
        }
    }
}
