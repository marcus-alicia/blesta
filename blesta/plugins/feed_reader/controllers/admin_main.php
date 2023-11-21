<?php
/**
 * Feed Reader main controller
 *
 * @package blesta
 * @subpackage blesta.plugins.feed_reader
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminMain extends FeedReaderController
{
    public function preAction()
    {
        parent::preAction();

        $this->requireLogin();

        $this->uses(['FeedReader.FeedReaderFeeds']);
        Language::loadLang('admin_main', null, PLUGINDIR . 'feed_reader' . DS . 'language' . DS);
    }

    /**
     * Renders the feed reader widget
     */
    public function index()
    {
        // Only available via AJAX
        if (!$this->isAjax()) {
            $this->redirect($this->base_uri);
        }

        $this->FeedReaderFeeds->setPerPage(5);

        $page = isset($this->get[0]) ? $this->get[0] : 1;

        $articles = $this->FeedReaderFeeds->getArticles(
            $this->Session->read('blesta_staff_id'),
            $this->company_id,
            null,
            $page
        );
        $feeds = $this->FeedReaderFeeds->getSubscribedFeeds(
            $this->Session->read('blesta_staff_id'),
            $this->company_id,
            $order = ['updated' => 'desc']
        );
        $last_updated = null;
        if (isset($feeds[0])) {
            $last_updated = $feeds[0]->updated;
        }

        $this->set('articles', $articles);
        $this->set('last_updated', $last_updated);
        $this->set('page', $page);
        $this->set('reload_twitter_follow', isset($this->get[0]));
        $this->set(
            'total_pages',
            ceil(
                $this->FeedReaderFeeds->getArticlesCount($this->Session->read('blesta_staff_id'), $this->company_id)
                / $this->FeedReaderFeeds->getPerPage()
            )
        );

        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) ? false : null);
    }

    /**
     * Renders the feed reader widget for the billing page
     */
    public function billing()
    {
        // Fetch content from the index action
        $this->action = 'index';
        $this->set('billing', 'true');
        return $this->index();
    }

    /**
     * List all feeds avilable
     */
    public function settings()
    {
        // Only available via AJAX
        if (!$this->isAjax()) {
            $this->redirect($this->base_uri);
        }

        $this->set(
            'feeds',
            $this->FeedReaderFeeds->getSubscribedFeeds($this->Session->read('blesta_staff_id'), $this->company_id)
        );
        return $this->renderAjaxWidgetIfAsync(false);
    }

    /**
     * Add a feed
     */
    public function add()
    {
        if (!empty($this->post)) {
            // Add the feed
            $this->FeedReaderFeeds->addFeed($this->post, $this->Session->read('blesta_staff_id'), $this->company_id);

            // Set errors, if any
            if (($errors = $this->FeedReaderFeeds->errors())) {
                $this->flashMessage('error', $errors);
            } else {
                // Handle successful add
                $this->flashMessage('message', Language::_('AdminMain.!success.feed_added', true));
            }

            $this->redirect($this->base_uri);
        }

        return $this->renderAjaxWidgetIfAsync(false);
    }

    /**
     * Refresh a feed (fetch articles for that feed)
     */
    public function refresh()
    {

        // Refresh the feed if given
        if (isset($this->get['feed_id'])) {
            $this->FeedReaderFeeds->fetchArticles($this->get['feed_id']);
            $this->flashMessage('message', Language::_('AdminMain.!success.feed_refreshed', true));
        }

        if ($this->isAjax()) {
            $this->action = 'settings';
            return $this->settings();
        } else {
            $this->redirect($this->base_uri . 'widget/feed_reader/admin_main/index/1/');
        }
    }

    /**
     * Remove a feed
     */
    public function remove()
    {
        if (isset($this->get['feed_id'])) {
            $this->FeedReaderFeeds->deleteSubscriber(
                $this->get['feed_id'],
                $this->Session->read('blesta_staff_id'),
                $this->company_id
            );
            $this->flashMessage('message', Language::_('AdminMain.!success.feed_removed', true));
        }

        if ($this->isAjax()) {
            $this->action = 'settings';
            return $this->settings();
        } else {
            $this->redirect($this->base_uri . 'widget/feed_reader/admin_main/index/1/');
        }
    }
}
