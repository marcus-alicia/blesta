<?php
/**
 * CMS manage plugin controller
 *
 * @package blesta
 * @subpackage blesta.plugins.cms.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
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

        Language::loadLang('cms_manage_plugin', null, PLUGINDIR . 'cms' . DS . 'language' . DS);

        // Set the company ID
        $this->company_id = Configure::get('Blesta.company_id');

        // Set the plugin ID
        $this->plugin_id = (isset($this->get[0]) ? $this->get[0] : null);

        // Set the Javascript helper
        $this->Javascript = $this->parent->Javascript;

        // Set the page title
        $this->parent->structure->set(
            'page_title',
            Language::_(
                'CmsManagePlugin.' . Loader::fromCamelCase($this->action ? $this->action : 'index') . '.page_title',
                true
            )
        );

        // Set the view to render for all actions under this controller
        $this->view->setView(null, 'Cms.default');
    }

    /**
     * Returns the view to be rendered when managing this plugin
     */
    public function index()
    {
        $this->init();

        $this->uses(['Cms.CmsPages', 'Languages']);
        $this->helpers(['Form']);

        if (!empty($this->post)) {
            $data = $this->post;
            $uri = '/';

            foreach ($data[$uri] ?? [] as $lang => $page) {
                $page['company_id'] = $this->company_id;
                $page['lang'] = $lang;
                $page['uri'] = $uri;

                // Add the page
                $this->CmsPages->add($page);
            }

            if (($errors = $this->CmsPages->errors())) {
                // Error, reset vars
                $vars = $this->post;
                $this->parent->setMessage('error', $errors);
            } else {
                // Success
                $this->parent->flashMessage('message', Language::_('CmsManagePlugin.!success.plugin_updated', true));
                $this->redirect($this->base_uri . 'settings/company/plugins/installed/');
            }
        }

        // Get pages
        $pages = $this->formatPages(
            $this->CmsPages->getAll($this->company_id, ['uri' => '/'])
        );

        // Set default tags for index page
        $tags = ['{base_url}', '{blesta_url}', '{admin_url}', '{client_url}', '{plugins}'];

        // Get company languages
        $languages = $this->Languages->getAll($this->company_id);

        // Set the view to render
        $params = [
            'vars' => $vars ?? [],
            'pages' => $pages,
            'tags' => $tags,
            'languages' => $languages
        ];

        return $this->partial('admin_manage_plugin', $params);
    }

    /**
     * Formats the CMS pages
     *
     * @param array $pages A list of unformatted pages
     * @return array A multi-dimensional array with the formatted pages
     */
    private function formatPages(array $pages) : array
    {
        $formatted_pages = [];

        foreach ($pages as $page) {
            $formatted_pages[$page->uri][$page->lang] = $page;
        }

        return $formatted_pages;
    }
}
