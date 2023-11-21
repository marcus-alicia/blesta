<?php
/**
 * Feed Reader manage plugin controller
 *
 * @package blesta
 * @subpackage blesta.plugins.feed_reader
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

        Language::loadLang('feed_reader_manage_plugin', null, PLUGINDIR . 'feed_reader' . DS . 'language' . DS);

        $this->uses(['FeedReader.FeedReaderFeeds']);
        // Use the parent data helper, it's already configured properly
        $this->Date = $this->parent->Date;

        $this->plugin_id = isset($this->get[0]) ? $this->get[0] : null;

        // Set the page title
        $this->parent->structure->set(
            'page_title',
            Language::_(
                'FeedReaderManagePlugin.'
                . Loader::fromCamelCase($this->action ? $this->action : 'index') . '.page_title',
                true
            )
        );
    }

    /**
     * Returns the view to be rendered when managing this plugin
     */
    public function index()
    {
        $this->init();

        $vars = [
            'plugin_id'=>$this->plugin_id,
            'feeds'=>$this->FeedReaderFeeds->getDefaultFeeds(Configure::get('Blesta.company_id'))
        ];

        // Set the view to render for all actions under this controller
        $this->view->setView(null, 'FeedReader.default');
        return $this->partial('admin_manage_plugin', $vars);
    }

    /**
     * Add a feed
     */
    public function add()
    {
        $this->init();

        $vars = ['plugin_id'=>$this->plugin_id];

        if (!empty($this->post)) {
            // Add the feed
            $this->post['company_id'] = Configure::get('Blesta.company_id');
            $this->FeedReaderFeeds->addFeed($this->post);

            // Set errors, if any
            if (($errors = $this->FeedReaderFeeds->errors())) {
                $this->parent->setMessage('error', $errors);
                $vars['vars'] = (object)$this->post;
            } else {
                // Handle successful add
                $this->parent->flashMessage('message', Language::_('FeedReaderManagePlugin.!success.feed_added', true));
                $this->redirect($this->base_uri . 'settings/company/plugins/manage/' . $this->plugin_id);
            }
        }


        // Set the view to render for all actions under this controller
        $this->view->setView(null, 'FeedReader.default');
        return $this->partial('admin_manage_plugin_add', $vars);
    }

    /**
     * Refres a feed (fetch articles for that feed)
     */
    public function refresh()
    {
        $this->init();

        // Refresh the feed if given
        if (isset($this->get['feed_id'])) {
            $this->FeedReaderFeeds->fetchArticles($this->get['feed_id']);
            $this->parent->flashMessage('message', Language::_('FeedReaderManagePlugin.!success.feed_refreshed', true));
        }
        $this->redirect($this->base_uri . 'settings/company/plugins/manage/' . $this->plugin_id);
    }

    /**
     * Remove a feed
     */
    public function remove()
    {
        $this->init();

        if (isset($this->post['feed_id'])) {
            $this->FeedReaderFeeds->deleteFeed($this->post['feed_id'], Configure::get('Blesta.company_id'));
            $this->parent->flashMessage('message', Language::_('FeedReaderManagePlugin.!success.feed_removed', true));
        }
        $this->redirect($this->base_uri . 'settings/company/plugins/manage/' . $this->plugin_id);
    }
}
