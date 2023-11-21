<?php
/**
 * Upgrades to version 5.4.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade5_4_0B1 extends UpgradeUtil
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
            'addUnverifiedStatusAccountsAch',
            'addPackageOptionsHiddenColumn',
            'addPackageOptionGroupsHiddenColumn',
            'addLogoSizeSettings',
            'addDataFeeds',
            'addDataFeedsPermissions',
            'addMerchantGatewayCallback',
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
     * Updates the accounts_ach.status field to include the "unverified" status
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function addUnverifiedStatusAccountsAch($undo = false)
    {
        if ($undo) {
            $this->Record->query(
                "ALTER TABLE `accounts_ach` CHANGE `status` `status` ENUM('active','inactive') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;"
            );
        } else {
            $this->Record->query(
                "ALTER TABLE `accounts_ach` CHANGE `status` `status` ENUM('active','inactive','unverified') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;"
            );
        }
    }

    /**
     * Adds a "hidden" column to the package options table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPackageOptionsHiddenColumn($undo = false)
    {
        if ($undo) {
            $sql = 'ALTER TABLE `package_options` DROP `hidden`';
            $this->Record->query($sql);
        } else {
            $sql = 'ALTER TABLE `package_options` ADD `hidden` TINYINT(1) NOT NULL DEFAULT 0 AFTER `editable`';
            $this->Record->query($sql);
        }
    }

    /**
     * Adds a "hidden" column to the package option groups table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPackageOptionGroupsHiddenColumn($undo = false)
    {
        if ($undo) {
            $sql = 'ALTER TABLE `package_option_groups` DROP `hidden`';
            $this->Record->query($sql);
        } else {
            $sql = 'ALTER TABLE `package_option_groups` ADD `hidden` TINYINT(1) NOT NULL DEFAULT 0 AFTER `description`';
            $this->Record->query($sql);
        }
    }

    /**
     * Sets the company settings for the client and admin logo size
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function addLogoSizeSettings($undo = false)
    {
        if ($undo) {
            // Nothing to do
        } else {
            // Add required company settings
            Loader::loadModels($this, ['Companies']);
            $companies = $this->Companies->getAll();

            foreach ($companies as $company) {
                $this->Companies->setSetting($company->id, 'admin_logo_height', 32);
                $this->Companies->setSetting($company->id, 'client_logo_height', 32);
            }
        }
    }

    /**
     * Adds the required tables for the data feeds
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addDataFeeds($undo = false)
    {
        if ($undo) {
            $this->Record->drop('data_feeds');
            $this->Record->drop('data_feed_endpoints');
        } else {
            // Create data_feeds table
            $this->Record
                ->setField('feed', ['type' => 'varchar', 'size' => 64])
                ->setField('dir', ['type' => 'varchar', 'size' => 64, 'is_null' => true, 'default' => null])
                ->setField('class', ['type' => 'varchar', 'size' => 255])
                ->setKey(['dir', 'class'], 'index')
                ->setKey(['feed'], 'primary')
                ->create('data_feeds', true);

            // Create data_feed_endpoints table
            $this->Record
                ->setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
                ->setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                ->setField('feed', ['type' => 'varchar', 'size' => 64])
                ->setField('endpoint', ['type' => 'varchar', 'size' => 64])
                ->setField('enabled', ['type' => 'tinyint', 'size' => 1, 'default' => 0])
                ->setKey(['company_id', 'feed', 'endpoint'], 'index')
                ->setKey(['id'], 'primary')
                ->create('data_feed_endpoints', true);

            // Add system data feeds
            Loader::loadModels($this, ['Companies']);
            $feeds = [
                [
                    'feed' => 'package',
                    'class' => '\\Blesta\\Core\\Util\\DataFeed\\Feeds\\PackageFeed',
                    'endpoints' => [
                        ['feed' => 'package', 'endpoint' => 'name'],
                        ['feed' => 'package', 'endpoint' => 'description'],
                        ['feed' => 'package', 'endpoint' => 'pricing']
                    ]
                ],
                [
                    'feed' => 'client',
                    'class' => '\\Blesta\\Core\\Util\\DataFeed\\Feeds\\ClientFeed',
                    'endpoints' => [
                        ['feed' => 'client', 'endpoint' => 'count']
                    ]
                ]
            ];
            foreach ($feeds as $feed) {
                $endpoints = $feed['endpoints'];
                unset($feed['endpoints']);

                $this->Record->insert('data_feeds', $feed);
                foreach ($endpoints as $endpoint) {
                    // Fetch all companies
                    $companies = $this->Companies->getAll();
                    foreach ($companies as $company) {
                        $endpoint['company_id'] = $company->id;
                        $this->Record->insert('data_feed_endpoints', $endpoint);
                    }
                }
            }
        }
    }

    /**
     * Adds data feeds permissions for the new AdminCompanyFeeds controller
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    public function addDataFeedsPermissions($undo = false)
    {
        Loader::loadModels($this, ['Permissions', 'StaffGroups']);
        Loader::loadComponents($this, ['Acl']);

        if ($undo) {
            // Nothing to do
        } else {
            $staff_groups = $this->StaffGroups->getAll();

            // Determine comparable permission access
            $staff_group_access = [];
            foreach ($staff_groups as $staff_group) {
                $staff_group_access[$staff_group->id] = [
                    'admin_company_feeds' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_settings',
                        'company',
                        'staff',
                        $staff_group->company_id
                    )
                ];
            }

            $group = $this->Permissions->getGroupByAlias('admin_settings');
            if ($group) {
                $permissions = [
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_feeds',
                        'alias' => 'admin_company_feeds',
                        'action' => '*'
                    ]
                ];
                foreach ($permissions as $vars) {
                    $this->Permissions->add($vars);

                    foreach ($staff_groups as $staff_group) {
                        // If staff group has access to similar item, grant access to this item
                        if ($staff_group_access[$staff_group->id][$vars['alias']]) {
                            $this->Acl->allow('staff_group_' . $staff_group->id, $vars['alias'], $vars['action']);
                        }
                    }
                }
            }

            // Clear cache for each staff group
            foreach ($staff_groups as $staff_group) {
                Cache::clearCache('nav_staff_group_' . $staff_group->id, $staff_group->company_id . DS . 'nav' . DS);
            }
        }
    }

    /**
     * Add Blesta.mgw_callback_url config to blesta.php
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function addMerchantGatewayCallback($undo = false)
    {
        if ($undo) {
            // Nothing to do
        } else {
            // Add Blesta.mgw_callback_url
            if (file_exists(CONFIGDIR . 'blesta.php') && file_exists(CONFIGDIR . 'blesta-new.php')) {
                $this->mergeConfig(CONFIGDIR . 'blesta.php', CONFIGDIR . 'blesta-new.php');
            }
        }
    }
}
