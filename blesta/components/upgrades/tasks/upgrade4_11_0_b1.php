<?php
/**
 * Upgrades to version 4.11.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_11_0B1 extends UpgradeUtil
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
            'addServiceCancellationReason',
            'addForgotUsernameEmail',
            'addPluginCards',
            'installClientCardsPlugin',
            'addLayoutSettingsPermission'
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
     * Add the cancellation reason to a service
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addServiceCancellationReason($undo = false)
    {
        if ($undo) {
            $sql = 'ALTER TABLE `services` DROP `cancellation_reason`';
            $this->Record->query($sql);
        } else {
            $sql = 'ALTER TABLE `services` ADD `cancellation_reason` TEXT NULL DEFAULT NULL AFTER `suspension_reason`';
            $this->Record->query($sql);
        }
    }

    /**
     * Adds a new email template for username recovery
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addForgotUsernameEmail($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            Loader::loadModels($this, ['Companies', 'Emails', 'Languages']);

            // Add the service_scheduled_cancellation email group
            $this->Record->insert(
                'email_groups',
                [
                    'action' => 'forgot_username',
                    'type' => 'client',
                    'tags' => '{contact.first_name},{contact.last_name},{username},{ip_address}'
                ]
            );
            $email_group_id = $this->Record->lastInsertId();

            // Fetch all companies
            $companies = $this->Companies->getAll();

            foreach ($companies as $company) {
                // Fetch all languages installed for this company
                $languages = $this->Languages->getAll($company->id);

                // Add the forgot_username email template for each installed language
                foreach ($languages as $language) {
                    // Fetch the reset_password email to copy fields from
                    $reset_password = $this->Emails->getByType($company->id, 'reset_password', $language->code);

                    if ($reset_password) {
                        $vars = [
                            'email_group_id' => $email_group_id,
                            'company_id' => $company->id,
                            'lang' => $language->code,
                            'from' => $reset_password->from,
                            'from_name' => $reset_password->from_name,
                            'subject' => 'Forgot Username',
                            'text' => 'Hi {contact.first_name},

Someone at the IP address {ip_address} requested the username for your account.
The username for your account is: {username}

If you did not make this request, you can safely ignore this email.',
                            'html' => '<p>Hi {contact.first_name},</p>
                            <p>Someone at the IP address {ip_address} requested the username for your account.<br/>The username for your account is: <b>{username}</b></p>
                            <p>If you did not make this request, you can safely ignore this email.</p>',
                            'email_signature_id' => $reset_password->email_signature_id,
                            'status' => 'active'
                        ];

                        $this->Record->insert('emails', $vars);
                    }
                }
            }

            // Add Blesta.default_forgot_username_value
            if (file_exists(CONFIGDIR . 'blesta.php')) {
                $this->addConfig(CONFIGDIR . 'blesta.php', 'Blesta.default_forgot_username_value', true);
            }
        }
    }

    /**
     * Adds a new plugin cards table to the database
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPluginCards($undo = false)
    {
        if ($undo) {
            // Drop plugin_cards table
            $this->Record->drop('plugin_cards');
        } else {
            // Create plugin_cards table
            $this->Record
                ->setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
                ->setField('plugin_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                ->setField('callback', ['type' => 'varchar', 'size' => 255])
                ->setField('callback_type', ['type' => 'enum', 'size' => "'value','html'", 'default' => 'value'])
                ->setField('background', ['type' => 'varchar', 'size' => 255, 'is_null' => true, 'default' => null])
                ->setField('background_type', ['type' => 'enum', 'size' => "'color','gradient','image'", 'default' => 'color'])
                ->setField('level', ['type' => 'enum', 'size' => "'client','staff'", 'default' => 'client'])
                ->setField('label', ['type' => 'varchar', 'size' => 255, 'is_null' => true, 'default' => null])
                ->setField('link', ['type' => 'varchar', 'size' => 255, 'is_null' => true, 'default' => null])
                ->setField('enabled', ['type' => 'tinyint', 'size' => 1, 'default' => 1])
                ->setKey(['id'], 'primary')
                ->create('plugin_cards', true);
        }
    }

    /**
     * Install the Client Cards plugin to all companies on the system
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function installClientCardsPlugin($undo = false)
    {
        if ($undo) {
            // Nothing to do
        } else {
            $plugins_to_install = Configure::get('plugins_to_install');
            if (is_array($plugins_to_install)) {
                $plugins_to_install[] = 'client_cards';
            } else {
                $plugins_to_install = ['client_cards'];
            }
            Configure::set('plugins_to_install', $plugins_to_install);
        }
    }

    /**
     * Adds a new permission for Layout settings page
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addLayoutSettingsPermission($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            // Get the package permission group
            Loader::loadModels($this, ['Permissions', 'Companies']);
            Loader::loadComponents($this, ['Acl']);

            // Fetch all staff groups
            $staff_groups = $this->Record->select(['staff_groups.*'])
                ->from('staff_groups')
                ->group(['staff_groups.id'])
                ->fetchAll();

            // Determine comparable permission access
            $staff_group_access = [];
            foreach ($staff_groups as $staff_group) {
                $staff_group_access[$staff_group->id] = [
                    'admin_company_lookandfeel' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_company_lookandfeel',
                        '*',
                        'staff',
                        $staff_group->company_id
                    )
                ];
            }

            $group = $this->Permissions->getGroupByAlias('admin_settings');

            // Add the new layout permission
            if ($group) {
                $permissions = [
                    'group_id' => $group->id,
                    'name' => 'StaffGroups.permissions.admin_company_lookandfeel_layout',
                    'alias' => 'admin_company_lookandfeel',
                    'action' => 'layout'
                ];
                $this->Permissions->add($permissions);

                foreach ($staff_groups as $staff_group) {
                    // If staff group has access to similar item, grant access to this item
                    if ($staff_group_access[$staff_group->id][$permissions['alias']]) {
                        $this->Acl->allow('staff_group_' . $staff_group->id, $permissions['alias'], $permissions['action']);
                    } else {
                        $this->Acl->deny('staff_group_' . $staff_group->id, $permissions['alias'], $permissions['action']);
                    }
                }
            }

            // Update company settings
            $companies = $this->Companies->getAll();

            foreach ($companies as $company) {
                $this->Companies->setSetting($company->id, 'layout_cards_order', base64_encode(serialize([])));
            }
        }
    }
}
