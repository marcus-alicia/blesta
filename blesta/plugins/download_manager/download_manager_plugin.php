<?php
/**
 * Download Manager plugin handler
 *
 * @package blesta
 * @subpackage blesta.plugins.download_manager
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class DownloadManagerPlugin extends Plugin
{
    /**
     * Initialize the plugin
     */
    public function __construct()
    {
        Language::loadLang('download_manager_plugin', null, dirname(__FILE__) . DS . 'language' . DS);

        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this plugin
        Loader::loadComponents($this, ['Input', 'Record']);
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

        // Add all download tables, *IFF* not already added
        try {
            // download_files
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField(
                    'category_id',
                    ['type' => 'int', 'size' => 10, 'unsigned' => true, 'is_null' => true, 'default' => null]
                )->
                setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('name', ['type' => 'varchar', 'size' => 255])->
                setField('file_name', ['type' => 'varchar', 'size' => 255])->
                setField('public', ['type' => 'tinyint', 'size' => 1, 'default' => 0])->
                setField('permit_client_groups', ['type' => 'tinyint', 'size' => 1, 'default' => 0])->
                setField('permit_packages', ['type' => 'tinyint', 'size' => 1, 'default' => 0])->
                setKey(['id'], 'primary')->
                setKey(['category_id'], 'index')->
                setKey(['company_id'], 'index')->
                create('download_files', true);

            // download_categories
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField(
                    'parent_id',
                    ['type' => 'int', 'size' => 10, 'unsigned' => true, 'is_null' => true, 'default' => null]
                )->
                setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('name', ['type' => 'varchar', 'size' => 255])->
                setField('description', ['type' => 'text'])->
                setKey(['id'], 'primary')->
                setKey(['parent_id'], 'index')->
                setKey(['company_id'], 'index')->
                create('download_categories', true);

            // download_file_groups
            $this->Record->
                setField('file_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('client_group_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setKey(['file_id', 'client_group_id'], 'primary')->
                create('download_file_groups', true);

            // download_file_packages
            $this->Record->
                setField('file_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('package_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setKey(['file_id', 'package_id'], 'primary')->
                create('download_file_packages', true);

            // download_logs
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('file_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField(
                    'client_id',
                    ['type' => 'int', 'size' => 10, 'unsigned' => true, 'is_null' => true, 'default' => null]
                )->
                setField(
                    'contact_id',
                    ['type' => 'int', 'size' => 10, 'unsigned' => true, 'is_null' => true, 'default' => null]
                )->
                setField('date_added', ['type' => 'datetime'])->
                setKey(['id'], 'primary')->
                setKey(['file_id'], 'index')->
                setKey(['client_id'], 'index')->
                setKey(['contact_id'], 'index')->
                create('download_logs', true);

            // download_urls
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('file_id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'is_null' => true, 'default' => null])->
                setField('category_id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'is_null' => true, 'default' => null])->
                setField('url', ['type' => 'varchar', 'size' => 255])->
                setKey(['id'], 'primary')->
                setKey(['company_id'], 'index')->
                setKey(['url'], 'index')->
                create('download_urls', true);

            // Set the uploads directory
            Loader::loadComponents($this, ['SettingsCollection', 'Upload']);
            $temp = $this->SettingsCollection->fetchSetting(null, Configure::get('Blesta.company_id'), 'uploads_dir');
            $upload_path = $temp['value'] . Configure::get('Blesta.company_id') . DS . 'download_files' . DS;
            // Create the upload path if it doesn't already exist
            $this->Upload->createUploadPath($upload_path, 0777);
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
            if (version_compare($current_version, '2.10.0', '<')) {
                $this->upgrade2_10_0();
            }

            if (version_compare($current_version, '2.10.1', '<')) {
                $this->Record->query(
                    "ALTER TABLE `download_urls` CHANGE `category_id` `category_id` INT(10) UNSIGNED NULL DEFAULT NULL"
                );
            }
        }
    }

    /**
     * Update to v2.10.0
     */
    private function upgrade2_10_0()
    {
        if (!isset($this->Record)) {
            Loader::loadComponents($this, ['Record']);
        }

        try {
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('file_id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'is_null' => true, 'default' => null])->
                setField('category_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('url', ['type' => 'varchar', 'size' => 255])->
                setKey(['id'], 'primary')->
                setKey(['company_id'], 'index')->
                setKey(['url'], 'index')->
                create('download_urls', true);
        } catch (Exception $e) {
            // Error adding... no permission?
            $this->Input->setErrors(['db'=> ['create'=>$e->getMessage()]]);
            return;
        }
    }

    /**
     * Performs any necessary cleanup actions
     *
     * @param int $plugin_id The ID of the plugin being uninstalled
     * @param bool $last_instance True if $plugin_id is the last instance across
     * all companies for this plugin, false otherwise
     */
    public function uninstall($plugin_id, $last_instance)
    {
        // Remove the tables created by this plugin
        if ($last_instance) {
            try {
                $this->Record->drop('download_categories');
                $this->Record->drop('download_files');
                $this->Record->drop('download_file_groups');
                $this->Record->drop('download_file_packages');
                $this->Record->drop('download_logs');
            } catch (Exception $e) {
                // Error dropping... no permission?
                $this->Input->setErrors(['db'=> ['create'=>$e->getMessage()]]);
                return;
            }
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
                'action' => 'nav_primary_client',
                'uri' => 'plugin/download_manager/client_main/',
                'name' => 'DownloadManagerPlugin.client_main'
            ],
            [
                'action' => 'nav_secondary_staff',
                'uri' => 'plugin/download_manager/admin_main/',
                'name' => 'DownloadManagerPlugin.admin_main',
                'options' => ['parent' => 'tools/']
            ]
        ];
    }
}
