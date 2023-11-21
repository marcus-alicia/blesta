<?php
/**
 * System Overview plugin handler
 *
 * @package blesta
 * @subpackage blesta.plugins.system_overview
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SystemOverviewPlugin extends Plugin
{
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        Language::loadLang('system_overview_plugin', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * Performs any necessary bootstraping actions
     *
     * @param int $plugin_id The ID of the plugin being installed
     */
    public function install($plugin_id)
    {
        if (!isset($this->Record)) {
            Loader::loadComponents($this, ['Input', 'Record']);
        }

        // Add the system overview table, *IFF* not already added
        try {
            // system_overview_settings
            $this->Record->
                setField('staff_id', ['type'=>'int', 'size'=>10, 'unsigned'=>true, 'auto_increment'=>false])->
                setField('company_id', ['type'=>'int', 'size'=>10, 'unsigned'=>true, 'auto_increment'=>false])->
                setField('key', ['type'=>'varchar', 'size'=>255])->
                setField('value', ['type'=>'varchar', 'size'=>255])->
                setField('order', ['type'=>'int', 'size'=>5, 'default'=>0])->
                setKey(['staff_id', 'company_id', 'key'], 'primary')->
                create('system_overview_settings', true);
        } catch (Exception $e) {
            // Error adding... no permission?
            $this->Input->setErrors(['db'=> ['create'=>$e->getMessage()]]);
            return;
        }
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
            if (!isset($this->Record)) {
                Loader::loadComponents($this, ['Input', 'Record']);
            }

            // Handle the upgrade, set errors using $this->Input->setErrors() if any errors encountered

            // Upgrade from 1.0.0 -> 1.1.0
            if ($current_version == '1.0.0') {
                // Get all staff
                $settings = $this->Record->select(['staff_id', 'company_id'])->
                    from('system_overview_settings')->
                    group(['staff_id', 'company_id'])->
                    getStatement();

                // Update all staff overview settings to include the new services_scheduled_cancellation setting
                // and adjust the order of all the other settings
                $this->Record->begin();

                // Update the order for all staff for all companies
                // Escape order since it's a keyword
                $this->Record->set('order', '`order`+1', false, false)->
                    where('order', '>=', 4)->update('system_overview_settings');

                // Add the new setting
                $fields = ['staff_id', 'company_id', 'key', 'value', 'order'];
                while (($setting = $settings->fetch())) {
                    $vars = [
                        'staff_id' => $setting->staff_id,
                        'company_id' => $setting->company_id,
                        'key' => 'services_scheduled_cancellation',
                        'value' => 0,
                        'order' => 4
                    ];
                    $this->Record->insert('system_overview_settings', $vars, $fields);
                }

                $this->Record->commit();
            }

            // Upgrade to 1.8.0
            if (version_compare($current_version, '1.8.0', '<')) {
                $this->upgrade1_8_0($plugin_id);
            }

            // Upgrade to 1.8.2
            if (version_compare($current_version, '1.8.2', '<')) {
                $this->upgrade1_8_2();
            }
        }
    }

    /**
     * Update to v1.8.0
     *
     * @param int $plugin_id The ID of the plugin being upgraded
     * @param bool $manage_acl Whether to allow/deny permissions using ACL
     */
    private function upgrade1_8_0($plugin_id, $manage_acl = true)
    {
        Loader::loadComponents($this, ['Acl', 'Record']);

        if ($manage_acl) {
            // Add access to all staff members for the system overview widget
            $staff_groups = $this->Record->select(['staff_groups.id'])->
                from('staff_groups')->
                innerJoin('plugins', 'plugins.company_id', '=', 'staff_groups.company_id', false)->
                where('plugins.dir', '=', 'system_overview')->
                group('staff_groups.id')->
                fetchAll();
            foreach ($staff_groups as $staff_group) {
                $this->Acl->deny('staff_group_' . $staff_group->id, 'system_overview.admin_main', 'billing');
            }
        }
    }

    /**
     * Update to v1.8.2
     */
    private function upgrade1_8_2()
    {
        Loader::loadComponents($this, ['Acl', 'Record']);

        // Add access to all staff members for the system overview widget
        $staff_groups = $this->Record->select(['staff_groups.id'])->
            from('staff_groups')->
            innerJoin('plugins', 'plugins.company_id', '=', 'staff_groups.company_id', false)->
            where('plugins.dir', '=', 'system_overview')->
            group('staff_groups.id')->
            fetchAll();
        foreach ($staff_groups as $staff_group) {
            $this->Acl->removeAcl('staff_group_' . $staff_group->id, 'system_overview.admin_main', '*');
            $this->Acl->allow('staff_group_' . $staff_group->id, 'system_overview.admin_main', '*');
        }
    }

    /**
     * Performs any necessary cleanup actions
     *
     * @param int $plugin_id The ID of the plugin being uninstalled
     * @param bool $last_instance True if $plugin_id is the last instance
     *  across all companies for this plugin, false otherwise
     */
    public function uninstall($plugin_id, $last_instance)
    {
        if (!isset($this->Record)) {
            Loader::loadComponents($this, ['Record']);
        }

        // Remove all system_overview tables *IFF* no other company in the system is using this plugin
        if ($last_instance) {
            $this->Record->drop('system_overview_settings');
        }
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
                'uri' => 'widget/system_overview/admin_main/',
                'name' => 'SystemOverviewPlugin.name'
            ],
            [
                'action' => 'widget_staff_billing',
                'uri' => 'widget/system_overview/admin_main/billing',
                'name' => 'SystemOverviewPlugin.name',
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
                'name' => Language::_('SystemOverviewPlugin.name', true),
                'alias' => 'system_overview.admin_main',
                'action' => 'billing'
            ],
            [
                'group_alias' => 'admin_main',
                'name' => Language::_('SystemOverviewPlugin.name', true),
                'alias' => 'system_overview.admin_main',
                'action' => '*'
            ]
        ];
    }
}
