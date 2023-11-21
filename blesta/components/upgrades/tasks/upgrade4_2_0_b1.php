<?php
/**
 * Upgrades to version 4.2.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_2_0B1 extends UpgradeUtil
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
        Loader::loadComponents($this, ['Record', 'Security']);

        // Load the AES and Hash security libraries
        $this->Crypt_Hash = $this->Security->create('Crypt', 'Hash');
    }

    /**
     * Returns a numerically indexed array of tasks to execute for the upgrade process
     *
     * @retrun array A numerically indexed array of tasks to execute for the upgrade process
     */
    public function tasks()
    {
        return [
            'addCouponTerms',
            'addInvoiceAllowAutodebit',
            'addExchangeRateSetting',
            'updateWidgetKeys',
            'updateConfig',
            'removeYahooFinance'
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
     * Adds the autodebit column to the Invoices table
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addInvoiceAllowAutodebit($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `invoices` DROP `autodebit`;');
            $this->Record->query('ALTER TABLE `invoices_recur` DROP `autodebit`;');
        } else {
            $this->Record->query(
                "ALTER TABLE `invoices`
                    ADD `autodebit` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `date_autodebit`;"
            );
            $this->Record->query(
                "ALTER TABLE `invoices_recur`
                    ADD `autodebit` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `currency`;"
            );
        }
    }

    /**
     * Creates a new 'exchange_rates_processor_key' setting
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addExchangeRateSetting($undo = false)
    {
        if ($undo) {
            $this->Record->from('settings')->where('key', '=', 'exchange_rates_processor_key')->delete();
        } else {
            $this->Record->insert(
                'settings',
                [
                    'key' => 'exchange_rates_processor_key',
                    'value' => '',
                    'encrypted' => 0,
                    'comment' => 'The Exchange Rate Processor API Key',
                    'inherit' => 1
                ]
            );
        }
    }

    /**
     * Updates the keys for widget states
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function updateWidgetKeys($undo = false)
    {
        if ($undo) {
            // Do nothing
        } else {
            $states = $this->Record->select()
                ->from('staff_settings')
                ->where('key', 'like', '%Widgets_%_state')
                ->getStatement();

            $record = $this->newRecord();
            foreach ($states as $state) {
                $value = unserialize(base64_decode($state->value));
                $widgets = [];

                if (!empty($value)) {
                    foreach ($value as $widget => $settings) {
                        $widgets[$this->systemHash($widget)] = $settings;
                    }
                }

                $state->value = base64_encode(serialize($widgets));
                $record->where('key', '=', $state->key)
                    ->where('staff_id', '=', $state->staff_id)
                    ->update('staff_settings', (array)$state);
            }
        }
    }

    /**
     * Hashes the given value using sha256
     *
     * @param string The value to hash
     */
    private function systemHash($value)
    {
        $this->Crypt_Hash->setHash('sha256');
        $this->Crypt_Hash->setKey(Configure::get('Blesta.system_key'));
        return bin2hex($this->Crypt_Hash->hash($value));
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
            // Add Blesta.session_ttl and Blesta.cookie_ttl
            if (file_exists(CONFIGDIR . 'blesta.php') && file_exists(CONFIGDIR . 'blesta-new.php')) {
                $this->mergeConfig(CONFIGDIR . 'blesta.php', CONFIGDIR . 'blesta-new.php');
            }
        }
    }

    /**
     * Removes yahoo finance files and updates the exchange_rates_processor company setting
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function removeYahooFinance($undo = false)
    {
        if ($undo) {
            // Do nothing
        } else {
            $this->Record->where('key', '=', 'exchange_rates_processor')
                ->where('value', '=', 'yahoo_finance')
                ->update('company_settings', ['value' => 'google_finance']);

            $this->Record->where('key', '=', 'exchange_rates_processor')
                ->where('value', '=', 'yahoo_finance')
                ->update('settings', ['value' => 'google_finance']);

            if (file_exists(COMPONENTDIR . 'exchange_rates' . DS . 'yahoo_finance' . DS)) {
                $this->removeDir(COMPONENTDIR . 'exchange_rates' . DS . 'yahoo_finance' . DS);
            }
        }
    }

    /**
     * If able removes the given directory and all files/subdirectories inside it
     *
     * @param string $dir the path to the directory
     */
    private function removeDir($dir)
    {
        try {
            foreach (glob($dir . '*', GLOB_MARK) as $file) {
                if (is_dir($file)) {
                    $this->removeDir($file);
                } else {
                    unlink($file);
                }
            }

            rmdir($dir);
        } catch (Exception $e) {
            // Do nothing
        }
    }

    /**
     * Adds coupon_terms table
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addCouponTerms($undo = false)
    {
        if ($undo) {
            $this->Record->drop('coupon_terms');
        } else {
            $this->Record->setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
                ->setField('coupon_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                ->setField('term', ['type' => 'smallint', 'size' => 5, 'unsigned' => true])
                ->setField('period', ['type' => 'enum', 'size' => "'day','week','month','year','onetime'"])
                ->setKey(['coupon_id', 'period', 'term'], 'unique')
                ->setKey(['id'], 'primary')
                ->create('coupon_terms', true);
        }
    }
}
