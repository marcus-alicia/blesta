<?php
/**
 * Shared Login plugin handler
 *
 * @package blesta
 * @subpackage blesta.plugins.shared_login
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SharedLoginPlugin extends Plugin
{
    /**
     * Init
     */
    public function __construct()
    {
        Language::loadLang('shared_login_plugin', null, dirname(__FILE__) . DS . 'language' . DS);

        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
    }

    /**
     * Performs any necessary bootstraping actions
     *
     * @param int $plugin_id The ID of the plugin being installed
     */
    public function install($plugin_id)
    {
        Loader::loadModels($this, ['Companies', 'PluginManager']);

        $plugin = $this->PluginManager->get($plugin_id);

        if (!$plugin) {
            return;
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            $key = bin2hex(openssl_random_pseudo_bytes(16));
        } else {
            $key = md5(uniqid($plugin_id, true) . mt_rand() . md5($plugin_id . mt_rand()));
        }

        $this->Companies->setSetting($plugin->company_id, 'shared_login.key', $key, true);
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
        Loader::loadModels($this, ['Companies', 'PluginManager']);

        $plugin = $this->PluginManager->get($plugin_id);

        if (!$plugin) {
            return;
        }

        $this->Companies->unsetSetting($plugin->company_id, 'shared_login.key');
    }
}
