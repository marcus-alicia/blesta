<?php
/**
 * Upgrades to version 4.3.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_3_0B1 extends UpgradeUtil
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
     * @retrun array A numerically indexed array of tasks to execute for the upgrade process
     */
    public function tasks()
    {
        return [
            'addSupensionReasonTag',
            'addServiceSupensionReason',
            'removeNavCaches',
            'refactorCronTasks',
            'addPackageDeletionPermission',
            'addPackagePlugins',
            'createClientSettingsLog',
            'updateConfig',
            'addMarketingSettings',
            'addMarketingPermission',
            'addSystemEvents',
            'setSystemEvents',
            'removeGoogleFinance',
            'addProrateAddonSetting',
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
     * Add tag for the suspension reason
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addSupensionReasonTag($undo = false)
    {
        $email_group = $this->Record->select()->
            from('email_groups')->
            where('action', '=', 'service_suspension')->
            fetch();

        if ($email_group) {
            $tags = ($undo
                ? str_replace(',{service.suspension_reason}', '', $email_group->tags)
                : $email_group->tags . ',{service.suspension_reason}');
            $this->Record->where('id', '=', $email_group->id)->
                update('email_groups', ['tags' => $tags], ['tags']);
        }
    }

    /**
     * Add the suspension reason to a service
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addServiceSupensionReason($undo = false)
    {
        if ($undo) {
            $sql = 'ALTER TABLE `services` DROP `suspension_reason`';
            $this->Record->query($sql);
        } else {
            $sql = 'ALTER TABLE `services` ADD `suspension_reason` TEXT NULL DEFAULT NULL AFTER `status`';
            $this->Record->query($sql);
        }
    }

    /**
     * Remove nav caches
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function removeNavCaches($undo = false)
    {
        Loader::loadModels($this, ['StaffGroups']);

        if ($undo) {
            // Nothing to do
        } else {
            $staff_groups = $this->StaffGroups->getAll();

            // Clear cache for each staff group
            foreach ($staff_groups as $staff_group) {
                Cache::clearCache('nav_staff_group_' . $staff_group->id, $staff_group->company_id . DS . 'nav' . DS);
            }
        }
    }

    /**
     * Refactors the `cron_tasks` table to support tasks for modules
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function refactorCronTasks($undo = false)
    {
        if ($undo) {
            // Delete all of the module tasks, they did not exist before
            $this->Record->from('cron_tasks')
                ->leftJoin('cron_task_runs', 'cron_task_runs.task_id', '=', 'cron_tasks.id', false)
                ->where('cron_tasks.task_type', '=', 'module')
                ->delete(['cron_task_runs.*', 'cron_tasks.*']);

            // Drop the index on 'key'
            $this->Record->query('DROP INDEX `key` ON `cron_tasks`');

            // Rename 'dir' to 'plugin_dir'
            $this->Record->query('ALTER TABLE `cron_tasks` CHANGE `dir` `plugin_dir` VARCHAR(64) NULL DEFAULT NULL');

            // Drop the column `task_type`
            $this->Record->query('ALTER TABLE `cron_tasks` DROP COLUMN `task_type`');

            // Re-add the index on 'key'
            $this->Record->query('CREATE UNIQUE INDEX `key` ON `cron_tasks` (`key`, `plugin_dir`)');

        } else {
            // Create the new `task_type`
            $this->Record->query(
                'ALTER TABLE `cron_tasks` ADD COLUMN `task_type` ENUM("module","plugin","system")
                DEFAULT "system" NOT NULL AFTER `key`'
            );

            // Update all plugins to the 'plugin' task_type
            $this->Record->query('UPDATE `cron_tasks` SET `task_type` = "plugin" WHERE `plugin_dir` IS NOT NULL');

            // Drop the index on 'key'
            $this->Record->query('ALTER TABLE `cron_tasks` DROP INDEX `key`');

            // Rename the 'plugin_dir' column to just 'dir'
            $this->Record->query('ALTER TABLE `cron_tasks` CHANGE `plugin_dir` `dir` VARCHAR(64) NULL DEFAULT NULL');

            // Re-add the index
            $this->Record->query('ALTER TABLE `cron_tasks` ADD UNIQUE INDEX `key` (`key`, `task_type`, `dir`)');
        }
    }

    /**
     * Adds a permission for package deletion
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addPackageDeletionPermission($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            // Get the package permission group
            Loader::loadModels($this, ['Permissions']);
            Loader::loadComponents($this, ['Acl']);
            $group = $this->Permissions->getGroupByAlias('admin_packages');

            // Add the new package deletion permission
            if ($group) {
                $this->Permissions->add([
                    'group_id' => $group->id,
                    'name' => 'StaffGroups.permissions.admin_packages_delete',
                    'alias' => 'admin_packages',
                    'action' => 'delete'
                ]);

                $staff_groups = $this->Record->select('id')->from('staff_groups')->fetchAll();
                foreach ($staff_groups as $staff_group) {
                    $this->Acl->allow('staff_group_' . $staff_group->id, 'admin_packages', 'delete');
                }
            }
        }
    }

    /**
     * Creates the `package_plugins` table
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addPackagePlugins($undo = false)
    {
        $table = 'package_plugins';

        if ($undo) {
            $this->Record->drop($table);
        } else {
            $this->Record->setField('package_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                ->setField('plugin_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                ->setKey(['package_id', 'plugin_id'], 'primary')
                ->create($table, true);
        }
    }

    /**
     * Creates the `log_client_settings` table
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function createClientSettingsLog($undo = false)
    {
        $table = 'log_client_settings';

        if ($undo) {
            $this->Record->drop($table);
        } else {
            $this->Record->setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
                ->setField('client_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                ->setField(
                    'by_user_id',
                    ['type' => 'int', 'size' => 10, 'unsigned' => true, 'is_null' => true, 'default' => null]
                )
                ->setField('ip_address', ['type' => 'varchar', 'size' => 39, 'is_null' => true, 'default' => null])
                ->setField('change', ['type' => 'text'])
                ->setField('date_changed', ['type' => 'datetime'])
                ->setKey(['id'], 'primary')
                ->setKey(['client_id'], 'index')
                ->setKey(['by_user_id', 'ip_address'], 'index')
                ->setKey(['ip_address'], 'index')
                ->setKey(['date_changed'], 'index')
                ->create($table, true);
        }
    }

    /**
     * Update config
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function updateConfig($undo = false)
    {
        if ($undo) {
            // No need to undo anything
        } else {
            // Adds new config values:
            // i.e. 'Blesta.auto_delete_client_setting_logs', 'Blesta.cron_task_restart_limit',
            // 'Blesta.cron_minimum_run_interval'
            if (file_exists(CONFIGDIR . 'blesta.php') && file_exists(CONFIGDIR . 'blesta-new.php')) {
                $this->mergeConfig(CONFIGDIR . 'blesta.php', CONFIGDIR . 'blesta-new.php');
            }
        }
    }

    /**
     * Sets marketing settings
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addMarketingSettings($undo = false)
    {
        Loader::loadModels($this, ['Companies']);

        if ($undo) {
            $this->Record->from('company_settings')->
                where('key', 'in', ['receive_email_marketing', 'show_receive_email_marketing'])->
                delete();
        } else {
            $companies = $this->Companies->getAll();

            foreach ($companies as $company) {
                $this->Record->insert(
                    'company_settings',
                    ['key' => 'receive_email_marketing', 'company_id' => $company->id, 'value' => 'false']
                );
                $this->Record->insert(
                    'company_settings',
                    ['key' => 'show_receive_email_marketing', 'company_id' => $company->id, 'value' => 'true']
                );
            }
        }
    }

    /**
     * Adds permissions to the marketing company setting page
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addMarketingPermission($undo = false)
    {
        Loader::loadModels($this, ['Permissions']);
        Loader::loadComponents($this, ['Acl']);

        if ($undo) {
            // Nothing to do
        } else {
            // Fetch all staff groups
            $staff_groups = $this->Record->select(['staff_groups.*'])
                ->from('staff_groups')
                ->innerJoin('companies', 'companies.id', '=', 'staff_groups.company_id', false)
                ->group(['staff_groups.id'])
                ->fetchAll();

            // Determine comparable permission access
            $staff_group_access = [];
            foreach ($staff_groups as $staff_group) {
                $staff_group_access[$staff_group->id] = [
                    'admin_company_general' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_company_general',
                        'localization',
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
                        'name' => 'StaffGroups.permissions.admin_company_general_marketing',
                        'alias' => 'admin_company_general',
                        'action' => 'marketing'
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
        }
    }

    /**
     * Creates the `system_events` table
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addSystemEvents($undo = false)
    {
        $table = 'system_events';

        if ($undo) {
            $this->Record->drop($table);
        } else {
            $this->Record->setField('event', ['type' => 'varchar', 'size' => 255])
                ->setField('observer', ['type' => 'varchar', 'size' => 255])
                ->setKey(['event', 'observer'], 'primary')
                ->create($table, true);
        }
    }

    /**
     * Adds system events to the `system_events` table
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function setSystemEvents($undo = false)
    {
        if ($undo) {
            // Delete all existing system events
            $this->Record->from('system_events')->delete();
        } else {
            // Add all new system events
            $events = [
                'Clients.delete' => [
                    '\\Blesta\\Core\\Util\\Events\\Handlers\\ClientAccount',
                    '\\Blesta\\Core\\Util\\Events\\Handlers\\ClientNotes',
                    '\\Blesta\\Core\\Util\\Events\\Handlers\\ClientPackages',
                    '\\Blesta\\Core\\Util\\Events\\Handlers\\Clients',
                    '\\Blesta\\Core\\Util\\Events\\Handlers\\ClientSettings',
                    '\\Blesta\\Core\\Util\\Events\\Handlers\\ClientValues',
                    '\\Blesta\\Core\\Util\\Events\\Handlers\\Contacts',
                    '\\Blesta\\Core\\Util\\Events\\Handlers\\Invoices',
                    '\\Blesta\\Core\\Util\\Events\\Handlers\\InvoicesRecur',
                    '\\Blesta\\Core\\Util\\Events\\Handlers\\LogClientSettings',
                    '\\Blesta\\Core\\Util\\Events\\Handlers\\LogEmails',
                    '\\Blesta\\Core\\Util\\Events\\Handlers\\Services',
                    '\\Blesta\\Core\\Util\\Events\\Handlers\\Transactions'
                ],
                'Contacts.delete' => [
                    '\\Blesta\\Core\\Util\\Events\\Handlers\\AccountsAch',
                    '\\Blesta\\Core\\Util\\Events\\Handlers\\AccountsCc',
                    '\\Blesta\\Core\\Util\\Events\\Handlers\\Contacts',
                    '\\Blesta\\Core\\Util\\Events\\Handlers\\LogContacts'
                ],
                'Users.delete' => [
                    '\\Blesta\\Core\\Util\\Events\\Handlers\\LogUsers'
                ]
            ];

            $sql = 'INSERT INTO `system_events` (`event`, `observer`) VALUES ';
            $values = [];
            foreach ($events as $event => $observers) {
                foreach ($observers as $observer) {
                    $sql .= '(?,?),';
                    $values[] = $event;
                    $values[] = $observer;
                }
            }

            $this->Record->query(substr($sql, 0, -1), $values);
        }
    }

    /**
     * Adds a new 'synchronize_addons' company setting
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addProrateAddonSetting($undo = false)
    {
        if ($undo) {
            // Remove the new setting
            $this->Record->from('company_settings')->where('key', '=', 'synchronize_addons')->delete();
        } else {
            // Add the new setting to company settings
            $companies = $this->Record->select()->from('companies')->getStatement();
            foreach ($companies as $company) {
                $this->Record->insert(
                    'company_settings',
                    ['key' => 'synchronize_addons', 'value' => 'true', 'company_id' => $company->id]
                );
            }
        }
    }

    /**
     * Removes google finance files and updates the exchange_rates_processor company setting
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function removeGoogleFinance($undo = false)
    {
        if ($undo) {
            // Do nothing
        } else {
            // Change the Google Finance processor to XRates
            $this->Record->where('key', '=', 'exchange_rates_processor')
                ->where('value', '=', 'google_finance')
                ->update('company_settings', ['value' => 'x_rates']);

            $this->Record->where('key', '=', 'exchange_rates_processor')
                ->where('value', '=', 'google_finance')
                ->update('settings', ['value' => 'x_rates']);

            if (file_exists(COMPONENTDIR . 'exchange_rates' . DS . 'google_finance' . DS)) {
                $this->removeDir(COMPONENTDIR . 'exchange_rates' . DS . 'google_finance' . DS);
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
}
