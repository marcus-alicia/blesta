<?php
/**
 * Feed Reader plugin handler
 *
 * @package blesta
 * @subpackage blesta.plugins.feed_reader
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class FeedReaderPlugin extends Plugin
{
    /**
     * @var array An array of default feeds to add when the plugin is installed for a given company
     */
    private static $default_feeds = ['http://www.blesta.com/feed/'];

    public function __construct()
    {
        // Load components required by this plugin
        Loader::loadComponents($this, ['Input']);

        Language::loadLang('feed_reader_plugin', null, dirname(__FILE__) . DS . 'language' . DS);
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
    }

    /**
     * Performs any necessary bootstraping actions
     *
     * @param int $plugin_id The ID of the plugin being installed
     */
    public function install($plugin_id)
    {
        if (!isset($this->Record)) {
            Loader::loadComponents($this, ['Record']);
        }

        $errors = [];
        // Ensure the the system meets the requirements for this plugin
        if (!extension_loaded('libxml')) {
            $errors['libxml']['required'] = Language::_('FeedReaderPlugin.!error.libxml_required', true);
        }
        if (!extension_loaded('dom')) {
            $errors['dom']['required'] = Language::_('FeedReaderPlugin.!error.dom_required', true);
        }

        if (!empty($errors)) {
            $this->Input->setErrors($errors);
            return;
        }

        Loader::loadModels($this, ['FeedReader.FeedReaderFeeds']);

        // Add all feed_reader tables, *IFF* not already added
        try {
            // feed_reader_feeds
            $this->Record->
                setField('id', ['type'=>'int', 'size'=>10, 'unsigned'=>true, 'auto_increment'=>true])->
                setField('url', ['type'=>'varchar', 'size'=>255])->
                setField('updated', ['type'=>'datetime', 'is_null'=>true, 'default'=>null])->
                setKey(['id'], 'primary')->
                setKey(['url'], 'unique')->
                create('feed_reader_feeds', true);

            // feed_reader_defaults
            $this->Record->
                setField('feed_id', ['type'=>'int', 'size'=>10, 'unsigned'=>true])->
                setField('company_id', ['type'=>'int', 'size'=>10, 'unsigned'=>true])->
                setKey(['feed_id', 'company_id'], 'primary')->
                create('feed_reader_defaults', true);

            // feed_reader_articles
            $this->Record->
                setField('id', ['type'=>'int', 'size'=>10, 'unsigned'=>true, 'auto_increment'=>true])->
                setField('feed_id', ['type'=>'int', 'size'=>10, 'unsigned'=>true])->
                setField('guid', ['type'=>'varchar', 'size'=>255])->
                setField('data', ['type'=>'text', 'is_null'=>true, 'default'=>null])->
                setField('date', ['type'=>'datetime'])->
                setKey(['id'], 'primary')->
                setKey(['feed_id', 'guid'], 'unique')->
                create('feed_reader_articles', true);

            // feed_reader_subscribers
            $this->Record->
                setField('feed_id', ['type'=>'int', 'size'=>10, 'unsigned'=>true])->
                setField('company_id', ['type'=>'int', 'size'=>10, 'unsigned'=>true])->
                setField('staff_id', ['type'=>'int', 'size'=>10, 'unsigned'=>true])->
                setKey(['feed_id', 'company_id', 'staff_id'], 'primary')->
                create('feed_reader_subscribers', true);
        } catch (Exception $e) {
            // Error adding... no permission?
            $this->Input->setErrors(['db'=> ['create'=>$e->getMessage()]]);
            return;
        }

        // Add all default feeds for this company
        if (is_array(self::$default_feeds)) {
            for ($i = 0; $i < count(self::$default_feeds); $i++) {
                $this->FeedReaderFeeds->addFeed(
                    ['url' => self::$default_feeds[$i], 'company_id' => Configure::get('Blesta.company_id')]
                );
            }
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

            // Upgrade to 2.5.0
            if (version_compare($current_version, '2.5.0', '<')) {
                $this->upgrade2_5_0($plugin_id);
            }

            // Upgrade to 2.5.2
            if (version_compare($current_version, '2.5.2', '<')) {
                $this->upgrade2_5_2();
            }
        }
    }

    /**
     * Update to v2.5.0
     *
     * @param int $plugin_id The ID of the plugin being upgraded
     * @param bool $manage_acl Whether to allow/deny permissions using ACL
     */
    private function upgrade2_5_0($plugin_id, $manage_acl = true)
    {
        Loader::loadComponents($this, ['Acl', 'Record']);

        if ($manage_acl) {
            // Add access to all staff members for the feed reader widget
            $staff_groups = $this->Record->select(['staff_groups.id'])->
                from('staff_groups')->
                innerJoin('plugins', 'plugins.company_id', '=', 'staff_groups.company_id', false)->
                where('plugins.dir', '=', 'feed_reader')->
                group('staff_groups.id')->
                fetchAll();
            foreach ($staff_groups as $staff_group) {
                $this->Acl->deny('staff_group_' . $staff_group->id, 'feed_reader.admin_main', 'billing');
            }
        }
    }

    /**
     * Update to v2.5.2
     */
    private function upgrade2_5_2()
    {
        Loader::loadComponents($this, ['Acl', 'Record']);

        // Add access to all staff members for the feed reader widget
        $staff_groups = $this->Record->select(['staff_groups.id'])->
            from('staff_groups')->
            innerJoin('plugins', 'plugins.company_id', '=', 'staff_groups.company_id', false)->
            where('plugins.dir', '=', 'feed_reader')->
            group('staff_groups.id')->
            fetchAll();
        foreach ($staff_groups as $staff_group) {
            $this->Acl->removeAcl('staff_group_' . $staff_group->id, 'feed_reader.admin_main', '*');
            $this->Acl->allow('staff_group_' . $staff_group->id, 'feed_reader.admin_main', '*');
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

        // Remove all feed_reader tables *IFF* no other company in the system is using this plugin
        if ($last_instance) {
            $this->Record->drop('feed_reader_feeds');
            $this->Record->drop('feed_reader_defaults');
            $this->Record->drop('feed_reader_articles');
            $this->Record->drop('feed_reader_subscribers');
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
                'uri' => 'widget/feed_reader/admin_main/',
                'name' => 'FeedReaderPlugin.name'
            ],
            [
                'action' => 'widget_staff_billing',
                'uri' => 'widget/feed_reader/admin_main/billing',
                'name' => 'FeedReaderPlugin.name',
                'enabled' => 0
            ],
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
                'name' => Language::_('FeedReaderPlugin.name', true),
                'alias' => 'feed_reader.admin_main',
                'action' => 'billing'
            ],
            [
                'group_alias' => 'admin_main',
                'name' => Language::_('FeedReaderPlugin.name', true),
                'alias' => 'feed_reader.admin_main',
                'action' => '*'
            ]
        ];
    }
}
