<?php
/**
 * Extension Generator plugin handler
 *
 * @package blesta
 * @subpackage blesta.plugins.extension_generator
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ExtensionGeneratorPlugin extends Plugin
{
    public function __construct()
    {
        // Load components required by this plugin
        Loader::loadComponents($this, ['Input', 'Record']);

        Language::loadLang('extension_generator_plugin', null, dirname(__FILE__) . DS . 'language' . DS);
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
    }

    /**
     * Performs any necessary bootstraping actions
     *
     * @param int $plugin_id The ID of the plugin being installed
     */
    public function install($plugin_id)
    {
        try {
            // extension_generator_extensions
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('name', ['type' => 'varchar', 'size' => 128])->
                setField(
                    'type',
                    ['type' => 'enum', 'size' => "'module','plugin','merchant','nonmerchant'", 'default' => 'module']
                )->
                setField('form_type', ['type' => 'enum', 'size' => "'basic','advanced'", 'default' => 'basic'])->
                setField('code_examples', ['type' => 'tinyint', 'size' => 1, 'default' => 0])->
                setField('data', ['type' => 'text', 'is_null' => true, 'default' => null])->
                setField('date_updated', ['type' => 'datetime'])->
                setKey(['id'], 'primary')->
                setKey(['company_id'], 'index')->
                create('extension_generator_extensions', true);

            // Set the uploads directory
            Loader::loadComponents($this, ['SettingsCollection', 'Upload']);
            $temp = $this->SettingsCollection->fetchSetting(null, Configure::get('Blesta.company_id'), 'uploads_dir');
            $upload_path = $temp['value'] . Configure::get('Blesta.company_id') . DS . 'extension_generator' . DS;
            // Create the upload path if it doesn't already exist
            $this->Upload->createUploadPath($upload_path, 0777);
        } catch (Exception $e) {
            // Error adding... no permission?
            $this->Input->setErrors(['db' => ['create' => $e->getMessage()]]);
            return;
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
        if ($last_instance) {
            try {
                $this->Record->drop('extension_generator_extensions');
            } catch (Exception $e) {
                // Error dropping... no permission?
                $this->Input->setErrors(['db' => ['create' => $e->getMessage()]]);
                return;
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
            if (version_compare($current_version, '1.1.0', '<')) {
                $this->Record->query(
                    "ALTER TABLE `extension_generator_extensions` CHANGE `type` `type`
                    ENUM('module', 'plugin', 'merchant', 'nonmerchant') NOT NULL DEFAULT 'module'"
                );
            }
        }
    }

    /**
     * Returns all actions to be configured for this widget (invoked after install()
     * or upgrade(), overwrites all existing actions)
     *
     * @return array A numerically indexed array containing:
     *  - action The action to register for
     *  - uri The URI to be invoked for the given action
     *  - name The name to represent the action
     *  - options An array of options (optional)
     */
    public function getActions()
    {
        return [
            [
                'action' => 'nav_secondary_staff',
                'uri' => 'plugin/extension_generator/admin_main/',
                'name' => 'ExtensionGeneratorPlugin.nav_secondary_staff.admin_main',
                'options' => ['parent' => 'tools/']
            ]
        ];
    }
}
