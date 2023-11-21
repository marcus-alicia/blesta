<?php
/**
 * System Status plugin handler
 *
 * @package blesta
 * @subpackage blesta.plugins.system_status
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SystemStatusPlugin extends Plugin
{
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        Language::loadLang('system_status_plugin', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * Performs any necessary bootstraping actions
     *
     * @param int $plugin_id The ID of the plugin being installed
     */
    public function install($plugin_id)
    {
        // Nothing to do
    }

    /**
     * Performs migration of data from $current_version (the current installed version)
     * to the given file set version
     *
     * @param string $current_version The current installed version of this plugin
     * @param int $plugin_id The ID of the plugin being upgraded
     */
    public function upgrade($current_version, $plugin_id)
    {
        // Upgrade if possible
        if (version_compare($this->getVersion(), $current_version, '>')) {
            // Handle the upgrade, set errors using $this->Input->setErrors() if any errors encountered

            // Upgrade to 1.7.0
            if (version_compare($current_version, '1.7.0', '<')) {
                $this->upgrade1_7_0($plugin_id);
            }

            // Upgrade to 1.7.2
            if (version_compare($current_version, '1.7.2', '<')) {
                $this->upgrade1_7_2();
            }
        }
    }

    /**
     * Update to v1.7.0
     *
     * @param int $plugin_id The ID of the plugin being upgraded
     * @param bool $manage_acl Whether to allow/deny permissions using ACL
     */
    private function upgrade1_7_0($plugin_id, $manage_acl = true)
    {
        Loader::loadComponents($this, ['Acl', 'Record']);

        if ($manage_acl) {
            // Add access to all staff members for the system status widget
            $staff_groups = $this->Record->select(['staff_groups.id'])->
                from('staff_groups')->
                innerJoin('plugins', 'plugins.company_id', '=', 'staff_groups.company_id', false)->
                where('plugins.dir', '=', 'system_status')->
                group('staff_groups.id')->
                fetchAll();
            foreach ($staff_groups as $staff_group) {
                $this->Acl->deny('staff_group_' . $staff_group->id, 'system_status.admin_main', 'billing');
            }
        }
    }

    /**
     * Update to v1.7.2
     */
    private function upgrade1_7_2()
    {
        Loader::loadComponents($this, ['Acl', 'Record']);

        // Add access to all staff members for the system status widget
        $staff_groups = $this->Record->select(['staff_groups.id'])->
            from('staff_groups')->
            innerJoin('plugins', 'plugins.company_id', '=', 'staff_groups.company_id', false)->
            where('plugins.dir', '=', 'system_status')->
            group('staff_groups.id')->
            fetchAll();
        foreach ($staff_groups as $staff_group) {
            $this->Acl->removeAcl('staff_group_' . $staff_group->id, 'system_status.admin_main', '*');
            $this->Acl->allow('staff_group_' . $staff_group->id, 'system_status.admin_main', '*');
        }
    }

    /**
     * Performs any necessary cleanup actions
     *
     * @param int $plugin_id The ID of the plugin being uninstalled
     * @param bool $last_instance True if $plugin_id is the last instance across
     *  all companies for this plugin, false otherwise
     */
    public function uninstall($plugin_id, $last_instance)
    {
        // Nothing to do
    }

    /**
     * Returns all actions to be configured for this widget
     * (invoked after install() or upgrade(), overwrites all existing actions)
     *
     * @return array A numerically indexed array containing:
     *  - action The action to register for
     *  - uri The URI to be invoked for the given action
     *  - name The name to represent the action (can be language definition)
     */
    public function getActions()
    {
        return [
            [
                'action' => 'widget_staff_home',
                'uri' => 'widget/system_status/admin_main/',
                'name' => 'SystemStatusPlugin.name'
            ],
            [
                'action' => 'widget_staff_billing',
                'uri' => 'widget/system_status/admin_main/billing',
                'name' => 'SystemStatusPlugin.name',
                'enabled' => 0
            ]
        ];
    }

    /**
     * Returns all permissions to be configured for this plugin (invoked after install(), upgrade(),
     *  and uninstall(), overwrites all existing permissions)
     *
     * @return array A numerically indexed array containing:
     *
     *  - group_alias The alias of the permission group this permission belongs to
     *  - name The name of this permission
     *  - alias The ACO alias for this permission (i.e. the Class name to apply to)
     *  - action The action this ACO may control (i.e. the Method name of the alias to control access for)
     */
    public function getPermissions()
    {
        return [
            [
                'group_alias' => 'admin_billing',
                'name' => Language::_('SystemStatusPlugin.name', true),
                'alias' => 'system_status.admin_main',
                'action' => 'billing'
            ],
            [
                'group_alias' => 'admin_main',
                'name' => Language::_('SystemStatusPlugin.name', true),
                'alias' => 'system_status.admin_main',
                'action' => '*'
            ]
        ];
    }
}
