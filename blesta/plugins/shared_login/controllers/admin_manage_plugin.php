<?php
/**
 * Shared Login manage plugin controller
 *
 * @package blesta
 * @subpackage blesta.plugins.shared_login
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminManagePlugin extends AppController
{
    /**
     * Performs necessary initialization
     */
    private function init()
    {
        // Require login
        $this->parent->requireLogin();

        Language::loadLang('shared_login_manage_plugin', null, PLUGINDIR . 'shared_login' . DS . 'language' . DS);

        // Set the company ID
        $this->company_id = Configure::get('Blesta.company_id');

        // Set the plugin ID
        $this->plugin_id = (isset($this->get[0]) ? $this->get[0] : null);

        // Set the page title
        $this->parent->structure->set(
            'page_title',
            Language::_(
                'SharedLoginManagePlugin.'
                . Loader::fromCamelCase($this->action ? $this->action : 'index') . '.page_title',
                true
            )
        );

        // Set the view to render for all actions under this controller
        $this->view->setView(null, 'SharedLogin.default');
    }

    /**
     * Returns the view to be rendered when managing this plugin
     */
    public function index()
    {
        $this->init();

        if (!empty($this->post)) {
            $this->parent->Companies->setSetting($this->company_id, 'shared_login.key', $this->post['key']);
        }

        $key = $this->parent->Companies->getSetting($this->company_id, 'shared_login.key');
        $vars = (object)[
            'key' => $key->value
        ];

        // Set the view to render
        return $this->partial('admin_manage_plugin', compact('vars'));
    }
}
