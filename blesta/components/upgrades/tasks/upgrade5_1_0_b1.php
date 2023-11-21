<?php
/**
 * Upgrades to version 5.1.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade5_1_0B1 extends UpgradeUtil
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
            'createInvoicesCacheFolder',
            'addCaptchaEnabledFormsSettings',
            'raiseCompanySettingKeyMaxLength',
            'createConfigOptionConditionTables',
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
     * Creates an invoice cache folder and adds new company settings
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function createInvoicesCacheFolder($undo = false)
    {
        Loader::loadModels($this, ['Companies']);

        // Fetch all companies
        $companies = $this->Companies->getAll();

        // Set company settings
        $company_settings = ['inv_cache' => 'none', 'inv_cache_compress' => 'false'];

        if ($undo) {
            foreach ($companies as $company) {
                @$this->deleteFiles(CACHEDIR . $company->id . DS . 'invoices');
                foreach ($company_settings as $key => $value) {
                    $this->Companies->unsetSetting($company->id, $key);
                }
            }
        } else {
            foreach ($companies as $company) {
                if (Configure::get('Caching.on') && is_writable(CACHEDIR) && is_dir(CACHEDIR)) {
                    @mkdir(CACHEDIR . $company->id . DS . 'invoices', 0755);
                }
                $this->Companies->setSettings($company->id, $company_settings);
            }
        }
    }

    /**
     * Adds the required company settings for captcha enabled forms
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addCaptchaEnabledFormsSettings($undo = false)
    {
        Loader::loadModels($this, ['Companies']);

        // Fetch all companies
        $companies = $this->Companies->getAll();

        // Set company settings
        $company_settings = ['captcha_enabled_forms' => serialize([])];

        if ($undo) {
            foreach ($companies as $company) {
                foreach ($company_settings as $key => $value) {
                    $this->Companies->unsetSetting($company->id, $key);
                }
            }
        } else {
            foreach ($companies as $company) {
                $this->Companies->setSettings($company->id, $company_settings);
            }
        }
    }

    /**
     * Deletes all the files and subdirectories of a given directory
     *
     * @param string $directory The directory to delete
     */
    private function deleteFiles($directory)
    {
        try {
            if (is_file($directory)) {
                unlink($directory);
            } elseif (is_dir($directory)) {
                foreach (array_diff(scandir($directory), ['.', '..']) as $file) {
                    if (is_dir($directory . DS . $file)) {
                        $this->deleteFiles($directory . DS . $file);
                    } else {
                        unlink($directory . DS . $file);
                    }
                }

                rmdir($directory);
            }
        } catch (Exception $e) {
            // Do nothing
        }
    }

    /**
     * Update the column length of the company_settings.key field
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function raiseCompanySettingKeyMaxLength($undo = false)
    {
        if ($undo) {
            // Update the max length to 32
            $this->Record->query(
                "ALTER TABLE `company_settings` CHANGE `key` `key` VARCHAR(32) NOT NULL;"
            );
        } else {
            // Update the max length to 128
            $this->Record->query(
                "ALTER TABLE `company_settings` CHANGE `key` `key` VARCHAR(128) NOT NULL;"
            );
        }
    }

    /**
     * Add the tables for package option conditions
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function createConfigOptionConditionTables($undo = false)
    {
        if ($undo) {
                $this->Record->drop('package_option_condition_sets');
                $this->Record->drop('package_option_conditions');
        } else {
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('option_group_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField(
                    'option_id',
                    ['type' => 'int', 'size' => 10, 'unsigned' => true]
                )->
                setField(
                    'option_value_id',
                    ['type' => 'int', 'size' => 10, 'unsigned' => true, 'is_null' => true, 'default' => null]
                )->
                setKey(['id'], 'primary')->
                create('package_option_condition_sets', true);

            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('condition_set_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('trigger_option_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('operator', ['type' => 'enum', 'size' => "'>','<','=','!=','in'", 'default' => '='])->
                setField('value', ['type' => 'varchar', 'size' => 255, 'is_null' => true, 'default' => null])->
                setField('value_id', ['type' => 'int', 'size' => 10, 'is_null' => true, 'default' => null])->
                setKey(['id'], 'primary')->
                create('package_option_conditions', true);
        }
    }
}
