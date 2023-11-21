<?php
/**
 * Billing Overview plugin handler
 *
 * @package blesta
 * @subpackage blesta.plugins.billing_overview
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class BillingOverviewPlugin extends Plugin
{

    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        Language::loadLang('billing_overview_plugin', null, dirname(__FILE__) . DS . 'language' . DS);
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

        // Add the billing overview table, *IFF* not already added
        try {
            // billing_overview_settings
            $this->Record->
                setField('staff_id', ['type'=>'int', 'size'=>10, 'unsigned'=>true, 'auto_increment'=>false])->
                setField('company_id', ['type'=>'int', 'size'=>10, 'unsigned'=>true, 'auto_increment'=>false])->
                setField('key', ['type'=>'varchar', 'size'=>255])->
                setField('value', ['type'=>'varchar', 'size'=>255])->
                setField('order', ['type'=>'int', 'size'=>5, 'default'=>0])->
                setKey(['staff_id', 'company_id', 'key'], 'primary')->
                create('billing_overview_settings', true);
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
            // Handle the upgrade, set errors using $this->Input->setErrors() if any errors encountered
            if (!isset($this->Record)) {
                Loader::loadComponents($this, ['Record']);
            }

            // Upgrade to 1.1.0
            if (version_compare($current_version, '1.1.0', '<')) {
                Loader::loadModels($this, ['BillingOverview.BillingOverviewSettings']);

                // Update the order of the settings to make room for 2 more settings
                $this->Record->set('order', '`order`+1', false, false)
                    ->where('order', '>', 11)
                    ->update('billing_overview_settings');
                $this->Record->set('order', '`order`+1', false, false)
                    ->where('order', '>', 2)
                    ->update('billing_overview_settings');

                // Fetch each staff member
                $staff = $this->Record->select(['staff_id', 'company_id'])->from('billing_overview_settings')->
                    group(['company_id', 'staff_id'])->getStatement();

                // Update each staff member's settings to include the new revenue_year setting
                foreach ($staff as $member) {
                    // Add the revenue_year setting
                    $vars = [
                        'staff_id' => $member->staff_id,
                        'company_id' => $member->company_id,
                        'key' => 'revenue_year',
                        'value' => 1,
                        'order' => 3
                    ];
                    $this->Record->insert('billing_overview_settings', $vars);

                    // Add the graph_revenue_year setting
                    $vars['key'] = 'graph_revenue_year';
                    $vars['order'] = 13;
                    $this->Record->insert('billing_overview_settings', $vars);
                }
            }

            // Upgrade to 1.3.0
            if (version_compare($current_version, '1.3.0', '<')) {
                $this->upgrade1_3_0();
            }

            // Upgrade to 1.6.0
            if (version_compare($current_version, '1.6.0', '<')) {
                $this->upgrade1_6_0();
            }

            // Upgrade to 1.9.0
            if (version_compare($current_version, '1.9.0', '<')) {
                $this->upgrade1_9_0($plugin_id);
            }

            // Upgrade to 1.9.2
            if (version_compare($current_version, '1.9.2', '<')) {
                $this->upgrade1_9_2();
            }
        }
    }

    /**
     * Update to v1.3.0
     */
    private function upgrade1_3_0()
    {
        Loader::loadModels($this, ['BillingOverview.BillingOverviewSettings']);

        // Update the order of the settings to make room for 3 more settings
        $this->Record->set('order', '`order`+3', false, false)
            ->where('order', '>', 3)
            ->update('billing_overview_settings');

        // Fetch each staff member
        $staff = $this->Record->select(['staff_id', 'company_id'])->from('billing_overview_settings')->
            group(['company_id', 'staff_id'])->getStatement();

        // Each new setting and its order
        $settings = ['credits_today' => 4, 'credits_month' => 5, 'credits_year' => 6];

        // Updat each staff member's settings to include the new credit settings
        foreach ($staff as $member) {
            // Add each new setting
            foreach ($settings as $setting => $order) {
                $vars = [
                    'staff_id' => $member->staff_id,
                    'company_id' => $member->company_id,
                    'key' => $setting,
                    'value' => 0,
                    'order' => $order
                ];

                $this->Record->insert('billing_overview_settings', $vars);
            }
        }
    }

    /**
     * Update to v1.6.0
     */
    private function upgrade1_6_0()
    {
        // Update the order of the settings to make room for 2 more settings at order 8 and 10
        $this->Record->set('order', '`order`+1', false, false)->
            where('order', '>', 7)->
            update('billing_overview_settings');
        $this->Record->set('order', '`order`+1', false, false)->
            where('order', '>', 9)->
            update('billing_overview_settings');

        // Fetch each staff member
        $staff = $this->Record->select(['staff_id', 'company_id'])->
            from('billing_overview_settings')->
            group(['company_id', 'staff_id'])->
            getStatement();
        $record = $this->newRecord();

        // Keep a hashmap of inv_types and companies
        $company_inv_types = [];

        // Add the new proforma amount settings based on existing invoice amount settings
        foreach ($staff as $staff_member) {
            if (!isset($company_inv_types[$staff_member->company_id])) {
                // Check the company invoice type
                $inv_type = $record->select(['value'])->
                    from('company_settings')->
                    where('company_id', '=', $staff_member->company_id)->
                    where('key', '=', 'inv_type')->
                    fetch();

                $company_inv_types[$staff_member->company_id] = $inv_type ? $inv_type->value : 'standard';
            }

            // Create a list of settings and their order
            $setting_keys = [8 => 'invoiced_today', 10 => 'invoiced_month'];
            $settings = [];
            foreach ($setting_keys as $order => $setting_key) {
                $enabled = 0;

                // Get the current invoice amount settings for this staff and company
                $setting = $record->select(['value'])->
                    from('billing_overview_settings')->
                    where('company_id', '=', $staff_member->company_id)->
                    where('staff_id', '=', $staff_member->staff_id)->
                    where('key', '=', $setting_key)->
                    fetch();

                // Base the value of this setting on that of the old setting
                if ($setting && $setting->value !== '' && $company_inv_types[$staff_member->company_id] == 'proforma') {
                    $enabled = $setting->value;
                }

                $settings[$setting_key] = [
                    'staff_id' => $staff_member->staff_id,
                    'company_id' => $staff_member->company_id,
                    'key' => $setting_key . '_proforma',
                    'value' => $enabled,
                    'order' => $order
                ];
            }

            foreach ($settings as $setting) {
                $record->insert('billing_overview_settings', $setting);
            }
        }
    }

    /**
     * Update to v1.9.0
     *
     * @param int $plugin_id The ID of the plugin being upgraded
     * @param bool $manage_acl Whether to allow/deny permissions using ACL
     */
    private function upgrade1_9_0($plugin_id, $manage_acl = true)
    {
        Loader::loadComponents($this, ['Acl', 'Record']);

        if ($manage_acl) {
            // Add access to all staff members for the billing overview widget
            $staff_groups = $this->Record->select(['staff_groups.id'])->
                from('staff_groups')->
                innerJoin('plugins', 'plugins.company_id', '=', 'staff_groups.company_id', false)->
                where('plugins.dir', '=', 'billing_overview')->
                group('staff_groups.id')->
                fetchAll();
            foreach ($staff_groups as $staff_group) {
                $this->Acl->deny('staff_group_' . $staff_group->id, 'billing_overview.admin_main', 'dashboard');
            }
        }
    }

    /**
     * Update to v1.9.2
     */
    private function upgrade1_9_2()
    {
        Loader::loadComponents($this, ['Acl', 'Record']);

        // Add access to all staff members for the billing overview widget
        $staff_groups = $this->Record->select(['staff_groups.id'])->
            from('staff_groups')->
            innerJoin('plugins', 'plugins.company_id', '=', 'staff_groups.company_id', false)->
            where('plugins.dir', '=', 'billing_overview')->
            group('staff_groups.id')->
            fetchAll();
        foreach ($staff_groups as $staff_group) {
            $this->Acl->removeAcl('staff_group_' . $staff_group->id, 'billing_overview.admin_main', '*');
            $this->Acl->allow('staff_group_' . $staff_group->id, 'billing_overview.admin_main', '*');
        }
    }

    /**
     * Creates a new database connection
     *
     * @return Record a new instance of Record
     */
    private function newRecord()
    {
        Loader::loadComponents($this, ['Record']);

        $reuse = Configure::get('Database.reuse_connection');
        Configure::set('Database.reuse_connection', false);

        $record = new Record(Configure::get('Database.profile'));

        Configure::set('Database.reuse_connection', $reuse);

        return $record;
    }

    /**
     * Performs any necessary cleanup actions
     *
     * @param int $plugin_id The ID of the plugin being uninstalled
     * @param bool $last_instance True if $plugin_id is the last instance across all
     *  companies for this plugin, false otherwise
     */
    public function uninstall($plugin_id, $last_instance)
    {
        if (!isset($this->Record)) {
            Loader::loadComponents($this, ['Record']);
        }

        // Remove all billing_overview tables *IFF* no other company in the system is using this plugin
        if ($last_instance) {
            $this->Record->drop('billing_overview_settings');
        }
    }

    /**
     * Returns all actions to be configured for this widget (invoked after install() or upgrade(),
     * overwrites all existing actions)
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
                'uri' => 'widget/billing_overview/admin_main/dashboard',
                'name' => 'BillingOverviewPlugin.name',
                'enabled' => 0
            ],
            [
                'action' => 'widget_staff_billing',
                'uri' => 'widget/billing_overview/admin_main/',
                'name' => 'BillingOverviewPlugin.name'
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
                'name' => Language::_('BillingOverviewPlugin.name', true),
                'alias' => 'billing_overview.admin_main',
                'action' => '*'
            ],
            [
                'group_alias' => 'admin_main',
                'name' => Language::_('BillingOverviewPlugin.name', true),
                'alias' => 'billing_overview.admin_main',
                'action' => 'dashboard'
            ],
        ];
    }
}
