<?php
/**
 * CMS main controller
 *
 * @package blesta
 * @subpackage blesta.plugins.cms.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Main extends CmsController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        parent::preAction();

        $this->uses(['PluginManager', 'Cms.CmsPages', 'Clients']);

        // Redirect if this plugin is not installed for this company
        if (!$this->PluginManager->isInstalled('cms', $this->company_id)) {
            $this->redirect($this->client_uri);
        }

        // Use the same structure as that of the client portal for the company given
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, 'client' . DS . $this->layout);
        $this->structure->set(
            'custom_head',
            '<link href="' . Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path)
            . 'views/' . $this->view->view . '/css/styles.css" rel="stylesheet" type="text/css" />'
        );

        $this->base_uri = WEBDIR;
        $this->view->base_uri = $this->base_uri;
        $this->structure->base_uri = $this->base_uri;

        // Set the client info
        if ($this->Session->read('blesta_client_id')) {
            $this->client = $this->Clients->get($this->Session->read('blesta_client_id'));
            $this->view->set('client', $this->client);
            $this->structure->set('client', $this->client);
        }
    }

    /**
     * Portal/CMS index
     */
    public function index()
    {
        $uri = '/';
        if (isset($this->get[0])) {
            $uri = $this->get[0];
        }

        $this->uses(['Cms.CmsPages', 'PluginManager']);

        // Load the template parser
        $parser_options_html = Configure::get('Blesta.parser_options');
        // Don't escape html
        $parser_options_html['autoescape'] = false;

        // Get current language
        $lang = Configure::get('Blesta.language');

        // Check if the page exists
        if (($page = $this->CmsPages->get($uri, $this->company_id, $lang))) {
            // Get installed plugins
            $plugins = $this->PluginManager->getAll($this->company_id);
            $installed_plugins = [];
            foreach ($plugins as $plugin) {
                $installed_plugins[$plugin->dir] = $plugin;
            }

            // Set page content
            $url = rtrim($this->base_url, '/');

            $tags = [
                'base_url' => $this->Html->safe($url),
                'blesta_url' => $this->Html->safe($url . WEBDIR),
                'client_url' => $this->Html->safe($url . $this->client_uri),
                'admin_url' => $this->Html->safe($url . $this->admin_uri),
                'plugins' => $installed_plugins
            ];

            $page->content = H2o::parseString($page->content, $parser_options_html)->render($tags);

            $this->set('content', $page->content);
            $this->structure->set('page_title', $page->title);
            $this->structure->set('title', $page->title);
        } else {
            $this->redirect($this->base_uri);
        }
    }
}
