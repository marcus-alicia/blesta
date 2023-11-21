<?php
/**
 * Upgrades to version 4.6.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_6_0B1 extends UpgradeUtil
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
            'addVoidInvoiceSetting',
            'addPackageOptionValueStatus',
            'addPackageOptionValueDefaults',
            'addPackageOptionDescriptions',
            'addRenewalPrice',
            'addPackageUpgradesUseRenewal',
            'updateConfig',
            'addGeneralClientSettingsPermission',
            'updateClientOptionsPermissions',
            'removeGlyphFonts',
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
     * Adds a new setting to handle how many days past due an invoice can be in order to be voided when it's associated
     * service is canceled
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addVoidInvoiceSetting($undo = false)
    {
        $setting = 'void_inv_canceled_service_days';

        if ($undo) {
            $this->Record->from('company_settings')
                ->where('key', '=', $setting)
                ->delete();
            $this->Record->from('client_group_settings')
                ->where('key', '=', $setting)
                ->delete();
        } else {
            // Add the setting to all companies
            $companies = $this->Record->select()->from('companies')->fetchAll();
            foreach ($companies as $company) {
                $vars = [
                    'key' => $setting,
                    'company_id' => $company->id,
                    'value' => '0',
                    'encrypted' => 0,
                    'inherit' => 1
                ];
                $this->Record->insert('company_settings', $vars);
            }

            // Also add the setting to any client groups that do not inherit company settings
            $client_groups = $this->Record->select(['client_groups.id'])
                ->from('client_groups')
                ->innerJoin(
                    'client_group_settings',
                    'client_group_settings.client_group_id',
                    '=',
                    'client_groups.id',
                    false
                )
                ->group(['client_groups.id'])
                ->fetchAll();
            foreach ($client_groups as $client_group) {
                $vars = [
                    'key' => $setting,
                    'client_group_id' => $client_group->id,
                    'value' => '0',
                    'encrypted' => 0
                ];
                $this->Record->insert('client_group_settings', $vars);
            }
        }
    }

    /**
     * Adds a new field to package option values to maintain their status
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addPackageOptionValueStatus($undo = false)
    {
        // Drop the option_id index, we will add it back
        $this->Record->query('ALTER TABLE `package_option_values` DROP INDEX `option_id`;');

        if ($undo) {
            $this->Record->query('ALTER TABLE `package_option_values` DROP `status`;');
            $this->Record->query('ALTER TABLE `package_option_values` ADD INDEX `option_id` (`option_id`);');
        } else {
            $this->Record->query('
                ALTER TABLE `package_option_values` ADD `status` ENUM("active","inactive")
                NOT NULL DEFAULT "active" AFTER `value`;
            ');
            $this->Record->query('ALTER TABLE `package_option_values` ADD INDEX `option_id` (`option_id`, `status`);');
        }
    }

    /**
     * Adds a new field to package option values to denote whether they are the default value for the option
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addPackageOptionValueDefaults($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `package_option_values` DROP `default`;');
        } else {
            $this->Record->query('
                ALTER TABLE `package_option_values` ADD `default` INT(10) UNSIGNED NOT NULL DEFAULT "0" AFTER `value`;
            ');
        }
    }

    /**
     * Add a new descriptions column to the package_options table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPackageOptionDescriptions($undo = false)
    {
        if ($undo) {
            // Drop the description column from the package_options table
            $this->Record->query('ALTER TABLE `package_options` DROP `description`;');
        } else {
            // Add the description column to the package_options table
            $this->Record->query('
                ALTER TABLE `package_options` ADD `description` MEDIUMTEXT NULL DEFAULT NULL AFTER `name`;
            ');
        }
    }

    /**
     * Adds a new column on the pricings table to track renewal prices
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addRenewalPrice($undo = false)
    {
        if ($undo) {
            // Remove `price_renews` column
            $this->Record->query('ALTER TABLE `pricings` DROP `price_renews`');
        } else {
            // Add the `price_renews` column to `pricings`
            $this->Record->query(
                "ALTER TABLE `pricings`
                ADD `price_renews` DECIMAL(19,4) NULL DEFAULT NULL AFTER `price`"
            );

            // Update all renewal prices to use the current price
            $this->Record->query(
                "UPDATE `pricings` SET `pricings`.`price_renews` = `pricings`.`price`
                WHERE `pricings`.`period` != 'onetime'"
            );
        }
    }

    /**
     * Adds the upgrades_use_renewal column to the packages table
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addPackageUpgradesUseRenewal($undo = false)
    {
        if ($undo) {
            // Remove `price_renews` column
            $this->Record->query('ALTER TABLE `packages` DROP `upgrades_use_renewal`');
        } else {
            // Add the `price_renews` column to `pricings`
            $this->Record->query(
                "ALTER TABLE `packages`
                ADD `upgrades_use_renewal` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1'"
            );
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
            // Add Blesta.transaction_deadlock_reattempts
            if (file_exists(CONFIGDIR . 'blesta.php')) {
                $this->mergeConfig(CONFIGDIR . 'blesta.php', CONFIGDIR . 'blesta-new.php');
            }
        }
    }

    /**
     * Adds a permission for general client setting page
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addGeneralClientSettingsPermission($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            // Get the client options permission group
            Loader::loadModels($this, ['Permissions']);
            Loader::loadComponents($this, ['Acl']);
            $group = $this->Permissions->getGroupByAlias('admin_settings');

            // Determine comparable permission access
            $staff_groups = $this->Record->select()->from('staff_groups')->fetchAll();
            $staff_group_access = [];
            foreach ($staff_groups as $staff_group) {
                $staff_group_access[$staff_group->id] = $this->Permissions->authorized(
                    'staff_group_' . $staff_group->id,
                    'admin_settings',
                    'company',
                    'staff',
                    $staff_group->company_id
                );
            }

            // Add the new general client setting page permission
            if ($group) {
                $this->Permissions->add([
                    'group_id' => $group->id,
                    'name' => 'StaffGroups.permissions.admin_company_clientoptions_general',
                    'alias' => 'admin_company_clientoptions',
                    'action' => 'general'
                ]);

                foreach ($staff_groups as $staff_group) {
                    if ($staff_group_access[$staff_group->id]) {
                        $this->Acl->allow('staff_group_' . $staff_group->id, 'admin_company_clientoptions', 'general');
                    }
                }
            }
        }
    }

    /**
     * Update permissions for the settings pages under Client Options
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function updateClientOptionsPermissions($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            Loader::loadModels($this, ['Permissions']);
            Loader::loadComponents($this, ['Acl']);

            // Determine comparable permission access
            $staff_groups = $this->Record->select()->from('staff_groups')->fetchAll();
            $staff_group_access = [];
            foreach ($staff_groups as $staff_group) {
                $staff_group_access[$staff_group->id] = $this->Permissions->authorized(
                    'staff_group_' . $staff_group->id,
                    'admin_company_customfields',
                    '*',
                    'staff',
                    $staff_group->company_id
                );
            }

            // Add the permissions for the client option pages
            $group = $this->Permissions->getGroupByAlias('admin_settings');
            if ($group) {
                $this->Permissions->add([
                    'group_id' => $group->id,
                    'name' => 'StaffGroups.permissions.admin_company_clientoptions_customfields',
                    'alias' => 'admin_company_clientoptions',
                    'action' => 'customfields'
                ]);

                $this->Permissions->add([
                    'group_id' => $group->id,
                    'name' => 'StaffGroups.permissions.admin_company_clientoptions_requiredfields',
                    'alias' => 'admin_company_clientoptions',
                    'action' => 'requiredfields'
                ]);

                // Give access to the proper staff groups
                foreach ($staff_groups as $staff_group) {
                    if ($staff_group_access[$staff_group->id]) {
                        $this->Acl->allow(
                            'staff_group_' . $staff_group->id,
                            'admin_company_clientoptions',
                            'customfields'
                        );

                        $this->Acl->allow(
                            'staff_group_' . $staff_group->id,
                            'admin_company_clientoptions',
                            'requiredfields'
                        );
                    }
                }
            }

            // Remove the old custom client fields permission
            $permission = $this->Permissions->getByAlias('admin_company_customfields');
            if ($permission) {
                $this->Permissions->delete($permission->id);
            }

            // Remove now unused aco record
            $this->Acl->removeAco('admin_company_customfields');
        }
    }

    /**
     * Removes glyph fonts that have been moved to a different directory
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function removeGlyphFonts($undo = false)
    {
        if ($undo) {
            // Nothing to do
        } else {
            $name = 'glyphicons-halflings-regular';
            $extensions = ['eot', 'svg', 'ttf', 'woff'];

            // Attempt to delete the glyphicon fonts that have been moved to another location
            foreach ($extensions as $ext) {
                $path = APPDIR . 'views' . DS . 'client' . DS . 'bootstrap' . DS . 'fonts' . DS . $name . '.' . $ext;
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
        }
    }
}
