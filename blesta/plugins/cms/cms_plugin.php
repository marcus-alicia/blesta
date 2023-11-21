<?php
/**
 * CMS plugin handler
 *
 * @package blesta
 * @subpackage blesta.plugins.cms
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class CmsPlugin extends Plugin
{
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        Language::loadLang('cms_plugin', null, dirname(__FILE__) . DS . 'language' . DS);
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

        Configure::load('cms', dirname(__FILE__) . DS . 'config' . DS);

        // Add the CMS tables, *IFF* not already added
        try {
            $this->Record->
                setField('uri', ['type'=>'varchar', 'size'=>255])->
                setField('company_id', ['type'=>'int', 'size'=>10, 'unsigned'=>true])->
                setField('lang', ['type'=>'varchar', 'size'=>5, 'default' => 'en_us'])->
                setField('title', ['type'=>'varchar', 'size'=>255])->
                setField('content', ['type'=>'text'])->
                setKey(['uri', 'company_id', 'lang'], 'primary')->
                create('cms_pages', true);

            // Install default index page
            $vars = [
                'uri' => '/',
                'company_id' => Configure::get('Blesta.company_id'),
                'lang' => 'en_us',
                'title' => Language::_('CmsPlugin.index.title', true),
                'content' => Configure::get('Cms.index.content_install_notice') . Configure::get('Cms.index.content')
            ];

            // Add the index page
            $fields = ['uri', 'company_id', 'lang', 'title', 'content'];
            try {
                // Attempt to add the page
                $this->Record->insert('cms_pages', $vars, $fields);
            } catch (Exception $e) {
                // Do nothing; re-use the existing entry
            }
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
        if (!isset($this->Record)) {
            Loader::loadComponents($this, ['Record']);
        }

        // Upgrade if possible
        if (version_compare($this->getVersion(), $current_version, '>')) {
            Configure::load('cms', dirname(__FILE__) . DS . 'config' . DS);

            // Upgrade to v1.0.2
            if (version_compare($current_version, '1.0.2', '<')) {
                // Update the index page for all companies
                $index_pages = $this->Record->select()->from('cms_pages')->where('uri', '=', '/')->fetchAll();

                // Replace order URL
                foreach ($index_pages as $index_page) {
                    $new_content = str_replace('{base_url}order/', '{blesta_url}order/', $index_page->content);

                    $vars = ['content' => $new_content];
                    $this->Record->where('uri', '=', '/')->where('company_id', '=', $index_page->company_id)->
                        update('cms_pages', $vars, ['content']);
                }
            }

            // Upgrade to v2.0.0
            if (version_compare($current_version, '2.0.0', '<')) {
                // Replace the index page for all companies
                $index_pages = $this->Record->select()->from('cms_pages')->where('uri', '=', '/')->fetchAll();
                $vars = ['content' => Configure::get('Cms.index.content')];

                foreach ($index_pages as $index_page) {
                    // Update the default page title
                    $vars['title'] = ($index_page->title == 'Portal'
                        ? Language::_('CmsPlugin.index.title', true)
                        : $index_page->title
                    );

                    $this->Record->where('uri', '=', '/')->where('company_id', '=', $index_page->company_id)->
                        update('cms_pages', $vars, ['content', 'title']);
                }
            }

            // Upgrade to v2.2.0
            if (version_compare($current_version, '2.2.0', '<')) {
                // Replace the index page for all companies
                $index_pages = $this->Record->select()->from('cms_pages')->where('uri', '=', '/')->fetchAll();

                foreach ($index_pages as $index_page) {
                    // Update conditionals in the body to check that each plugin is enabled
                    $search_replace = [
                        '{% if plugins.support_manager %}' => '{% if plugins.support_manager.enabled %}',
                        '{% if plugins.order %}' => '{% if plugins.order.enabled %}',
                        '{% if plugins.download_manager %}' => '{% if plugins.download_manager.enabled %}'
                    ];
                    $vars = [
                        'content' => str_replace(
                            array_keys($search_replace),
                            array_values($search_replace),
                            $index_page->content
                        )
                    ];

                    $this->Record->where('uri', '=', '/')->where('company_id', '=', $index_page->company_id)->
                        update('cms_pages', $vars, ['content']);
                }
            }

            // Upgrade to v2.8.0
            if (version_compare($current_version, '2.8.0', '<')) {
                Loader::loadModels($this, ['Companies']);

                // Add "lang" column to the cms_pages table
                $this->Record->query('ALTER TABLE `cms_pages` ADD `lang` VARCHAR(5) NOT NULL DEFAULT \'en_us\' AFTER `company_id`;');
                $this->Record->query(
                    'ALTER TABLE `cms_pages` DROP PRIMARY KEY, ADD PRIMARY KEY(`uri`, `company_id`, `lang`);'
                );

                // Sets the default language for index page for all companies
                $index_pages = $this->Record->select()->from('cms_pages')->where('uri', '=', '/')->fetchAll();

                foreach ($index_pages as $index_page) {
                    // Get company language
                    $lang = $this->Companies->getSetting($index_page->company_id, 'language');

                    // Update index page
                    $this->Record->where('uri', '=', '/')->where('company_id', '=', $index_page->company_id)->
                        update('cms_pages', ['lang' => $lang->value ?? 'en_us'], ['lang']);
                }
            }
        }
    }

    /**
     * Performs any necessary cleanup actions
     *
     * @param int $plugin_id The ID of the plugin being uninstalled
     * @param bool $last_instance True if $plugin_id is the last instance
     * across all companies for this plugin, false otherwise
     */
    public function uninstall($plugin_id, $last_instance)
    {
        if (!isset($this->Record)) {
            Loader::loadComponents($this, ['Record']);
        }

        // Remove all tables *IFF* no other company in the system is using this plugin
        if ($last_instance) {
            try {
                $this->Record->drop('cms_pages');
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
        return [];
    }
}
