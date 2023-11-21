<?php
/**
 * Feed Reader Feeds
 *
 * Manages feeds, articles, and subscribers
 *
 * @package blesta
 * @subpackage blesta.plugins.feed_reader.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
require_once PLUGINDIR . 'feed_reader' . DS . 'vendors' . DS . 'simplepie' . DS . 'autoloader.php';

class FeedReaderFeeds extends FeedReaderModel
{
    /**
     * @var string The amount of time to wait between feed updates (a suitable string for strtotime())
     */
    private static $query_feed_rate = '1 hour';
    /**
     * @var int The number of articles to keep per feed (only the latest articles are kept)
     */
    private static $articles_per_feed = 50;

    /**
     * Initialize Feeds
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang('feed_reader_feeds', null, PLUGINDIR . 'feed_reader' . DS . 'language' . DS);
    }

    /**
     * Adds a feed with the given details
     *
     * @param array $vars An array of feed information including:
     *  - url The full URL to the feed
     *  - company_id The company ID to make this feed a default feed for (optional)
     * @param int $staff_id The staff member adding the feed to subscribe to the feed
     * @param int $company_id The ID of the company belonging to the staff member to subscribe the feed to
     */
    public function addFeed(array $vars, $staff_id = null, $company_id = null)
    {
        $rules = [
            'url'=>[
                'valid'=>[
                    'rule'=>'isEmpty',
                    'negate'=>true,
                    'message'=>$this->_('FeedReaderFeeds.!error.url.valid')
                ]
            ],
            'company_id'=>[
                'exists'=>[
                    'if_set'=>true,
                    'rule'=>[[$this, 'validateExists'], 'id', 'companies'],
                    'message'=>$this->_('FeedReaderFeeds.!error.company_id.exists')
                ]
            ]
        ];

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            $feed_id = $this->getFeedIdByUrl($vars['url']);

            if (!$feed_id) {
                $fields = ['url', 'updated'];
                $this->Record->insert('feed_reader_feeds', $vars, $fields);

                $feed_id = $this->Record->lastInsertId();
            }

            // Subscribe the staff/company to the feed if given
            if ($staff_id && $company_id) {
                $this->addSubscriber($feed_id, $staff_id, $company_id);
            }

            // Set the feed as default is specified
            if (isset($vars['company_id'])) {
                $this->Record->duplicate('feed_id', '=', 'feed_id', false)->
                    insert('feed_reader_defaults', ['feed_id'=>$feed_id, 'company_id'=>$vars['company_id']]);
            }
        }
    }

    /**
     * Update a feed's last updated date/time
     *
     * @param int $feed_id The ID of the feed to update
     * @param array $vars An array of feed info including:
     *  - updated The date/time the feed was last updated
     */
    public function editFeed($feed_id, array $vars)
    {
        $rules = [
            'updated'=>[
                'valid'=>[
                    'pre_format'=>[[$this, 'dateToUtc']],
                    'rule'=>'isDate',
                    'message'=>$this->_('FeedReaderFeeds.!error.updated.valid')
                ]
            ]
        ];

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            $this->Record->where('id', '=', $feed_id)->update('feed_reader_feeds', $vars, ['updated']);
        }
    }

    /**
     * Deletes a feed and any subscribers and articles if possible
     *
     * @param int $feed_id The ID of the feed to delete
     * @param int $company_id The ID of the company to remove the feed for
     */
    public function deleteFeed($feed_id, $company_id)
    {

        // If there are no subscribers for this feed whatsoever, remove it from the system
        if (!$this->hasSubscribers($feed_id)) {
            $this->Record->from('feed_reader_feeds')->where('id', '=', $feed_id)->delete();
            $this->Record->from('feed_reader_defaults')->where('feed_id', '=', $feed_id)->delete();
            $this->Record->from('feed_reader_articles')->where('feed_id', '=', $feed_id)->delete();
        } else {
            // Unset the feed as a default feed for that company
            $this->Record->from('feed_reader_defaults')->where('feed_id', '=', $feed_id)->
                where('company_id', '=', $company_id)->delete();
        }
    }

    /**
     * Adds an article
     *
     * @param int $feed_id The ID of the feed to add an article to
     * @param array $vars An array of article information including:
     *  - data An array of data for the article
     *  - date The date of the article
     *  - guid The unique ID from this feed for this article
     */
    public function addArticle($feed_id, array $vars)
    {
        $vars['feed_id'] = $feed_id;
        if (isset($vars['data']) && !empty($vars['data'])) {
            $vars['data'] = base64_encode(serialize($vars['data']));
        }

        $rules = [
            'feed_id'=>[
                'exists'=>[
                    'if_set'=>true,
                    'rule'=>[[$this, 'validateExists'], 'id', 'feed_reader_feeds'],
                    'message'=>$this->_('FeedReaderFeeds.!error.feed_id.exists')
                ]
            ],
            'date'=>[
                'valid'=>[
                    'pre_format'=>[[$this, 'dateToUtc']],
                    'rule'=>'isDate',
                    'message'=>$this->_('FeedReaderFeeds.!error.date.valid')
                ]
            ]
        ];
        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            $fields = ['feed_id', 'guid', 'data', 'date'];
            $this->Record->duplicate('data', '=', 'data', false)->insert('feed_reader_articles', $vars, $fields);
        }
    }

    /**
     * Adds a subscriber to a feed
     *
     * @param int $feed_id The ID of the feed to subscribe to
     * @param int $staff_id The ID of the staff member to assign to the feed
     * @param int $company_id The ID of the company the feed should appear under for the given staff member
     */
    public function addSubscriber($feed_id, $staff_id, $company_id)
    {
        $vars = [
            'feed_id'=>$feed_id,
            'staff_id'=>$staff_id,
            'company_id'=>$company_id
        ];

        $rules = [
            'feed_id'=>[
                'exists'=>[
                    'rule'=>[[$this, 'validateExists'], 'id', 'feed_reader_feeds'],
                    'message'=>$this->_('FeedReaderFeeds.!error.feed_id.exists')
                ]
            ],
            'staff_id'=>[
                'exists'=>[
                    'rule'=>[[$this, 'validateExists'], 'id', 'staff'],
                    'message'=>$this->_('FeedReaderFeeds.!error.staff_id.exists')
                ]
            ],
            'company_id'=>[
                'exists'=>[
                    'rule'=>[[$this, 'validateExists'], 'id', 'companies'],
                    'message'=>$this->_('FeedReaderFeeds.!error.company_id.exists')
                ]
            ]
        ];
        $this->Input->setRules($rules);

        // Add a subscriber to the feed
        if ($this->Input->validates($vars)) {
            $fields = ['feed_id', 'company_id', 'staff_id'];
            $this->Record->insert('feed_reader_subscribers', $vars, $fields);
        }
    }

    /**
     * Deletes the subscriber from the feed, and additionally deletes the feed if there are no
     * other subscribers and the feed is not a default feed.
     *
     * @param int $feed_id The ID of the feed to remove from the subscriber
     * @param int $staff_id The ID of the staff member to unsubscribe
     * @param int $company_id The ID of the company for the staff member to unsubscribe the feed from
     */
    public function deleteSubscriber($feed_id, $staff_id, $company_id)
    {

        // Delete the subscriber
        $this->Record->from('feed_reader_subscribers')->where('feed_id', '=', $feed_id)->
            where('staff_id', '=', $staff_id)->where('company_id', '=', $company_id)->delete();

        // Fetch the feed
        $feed = $this->getFeed($feed_id);

        // If the feed is not a default feed then attempt to delete the feed
        // (which only deletes if there are no more subscribers)
        if ($feed && !isset($feed->company_id)) {
            $this->deleteFeed($feed_id, $company_id);
        }
    }

    /**
     * Fetches all feeds the user is explicitly subscribed to, or inherintly
     * subscribed if no explicit subscriptions exist
     *
     * @param int $staff_id The ID of the staff member to fetch subscriptions for
     * @param int $company_id The ID of the company to fetch subscriptions for
     * @param array $order  The order to list the feeds in key/value pairs where
     *  each key is a field and each value is a direction (asc or desc)
     */
    public function getSubscribedFeeds($staff_id, $company_id, $order = ['id' => 'asc'])
    {
        $feeds = $this->Record->select(['feed_reader_feeds.*'])
            ->from('feed_reader_feeds')
            ->on('feed_reader_subscribers.staff_id', '=', $staff_id)
            ->on('feed_reader_subscribers.company_id', '=', $company_id)
            ->innerJoin(
                'feed_reader_subscribers',
                'feed_reader_subscribers.feed_id',
                '=',
                'feed_reader_feeds.id',
                false
            )
            ->order($order)
            ->fetchAll();

        // If the user is subscribed to some feeds, return those
        if (!empty($feeds)) {
            return $feeds;
        }

        // If the user is not subscribed to any specific feeds, fetch default feeds and set those as subscribed
        $default_feeds = $this->getDefaultFeeds($company_id, $order);

        // Subscribe the user to default feeds
        foreach ($default_feeds as $feed) {
            $this->addSubscriber($feed->id, $staff_id, $company_id);
        }

        // Return default feeds
        return $default_feeds;
    }

    /**
     * Get default feeds for the given company. These are feeds which are rendered for a
     * user if they have no previously saved feeds.
     *
     * @param int $company_id The ID of the company to fetch feeds for
     * @param array $order The order to list the feeds in key/value pairs where
     *  each key is a field and each value is a direction (asc or desc)
     * @return array An array of stdClass objects each representing a default feed
     */
    public function getDefaultFeeds($company_id, $order = ['id' => 'asc'])
    {
        return $this->Record->select(['feed_reader_feeds.*'])->from('feed_reader_feeds')->
            innerJoin('feed_reader_defaults', 'feed_reader_defaults.feed_id', '=', 'feed_reader_feeds.id', false)->
            where('feed_reader_defaults.company_id', '=', $company_id)->
            order($order)->fetchAll();
    }

    /**
     * Returns a single feed record
     *
     * @param int $feed_id The ID of the feed to fetch
     * @return stdClass A stdClass object representing the feed, false if the feed does not exist
     */
    public function getFeed($feed_id)
    {
        return $this->Record->select()->from('feed_reader_feeds')->
            leftJoin('feed_reader_defaults', 'feed_reader_defaults.feed_id', '=', 'feed_reader_feeds.id', false)->
            where('id', '=', $feed_id)->fetch();
    }

    /**
     * Returns a list of articles the staff member is explicitly or implicitly subscribed to based on the given feed ID
     *
     * @param int $staff_id The ID of the staff member to fetch articles for
     * @param int $company_id The ID of the company to fetch articles for
     * @param int $feed_id The ID of the feed to fetch articles for
     *  (if not given will fetch articles for ALL subscribed feeds)
     * @param int $page The page number to fetch articles for
     * @param array $order The order to sort the result by
     * @return array An array of stdClass objects each representing an article containing the following:
     *  - id The ID of the article
     *  - feed_id The ID of the feed the article belongs to
     *  - guid The globally unique identifier for the article within the given feed
     *  - data An array of data from the feed
     *  - date The date the article was published
     */
    public function getArticles($staff_id, $company_id, $feed_id = null, $page = 1, $order = ['date' => 'desc'])
    {
        $this->Record = $this->articles($staff_id, $company_id, $feed_id);
        $articles = $this->Record->order($order)
            ->limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())
            ->fetchAll();

        // Unserialize the data from the return result
        foreach ($articles as &$article) {
            $article->data = unserialize(base64_decode($article->data));
        }

        return $articles;
    }

    /**
     * Returns a count of all articles that match the given criteria
     *
     * @param int $staff_id The ID of the staff member to fetch articles for
     * @param int $company_id The ID of the company to fetch articles for
     * @param int $feed_id The ID of the feed to fetch articles for
     *  (if not given will fetch articles for ALL subscribed feeds)
     * @return int The number of results that match the criteria
     */
    public function getArticlesCount($staff_id, $company_id, $feed_id = null)
    {
        $this->Record = $this->articles($staff_id, $company_id, $feed_id);
        return $this->Record->numResults();
    }

    /**
     * Fetch articles for the given feed, store them in the database and remove old records
     *
     * @param int $feed_id The ID of the feed to fetch articles for
     */
    public function fetchArticles($feed_id, $query_rate = '0 seconds')
    {
        $feed = $this->getFeed($feed_id);

        if (!$feed || strtotime($feed->updated) >= strtotime('-' . $query_rate)) {
            return;
        }

        $items = [];
        try {
            $reader = new SimplePie();
            $reader->set_feed_url($feed->url);
            $reader->enable_cache(false);
            $reader->init();
            $items = $reader->get_items();
        } catch (Exception $e) {
            // Could not fetch the feed, invalid URL or not parseable.
        }

        for ($i=0; $i<count($items); $i++) {
            $info = [
                'data' => [
                    'link' => $items[$i]->get_link(),
                    'title' => $items[$i]->get_title(),
                    'description' => $items[$i]->get_description()
                ],
                'date' => $items[$i]->get_date('c'),
                'guid' => $items[$i]->get_id()
            ];

            $this->addArticle($feed_id, $info);
        }
        $this->editFeed($feed_id, ['updated'=>date('c')]);

        // Remove old records for this feed
        $sub_query = $this->Record->select(['id'])->from('feed_reader_articles')->where('feed_id', '=', $feed_id)->
            order(['date'=>'desc'])->limit(self::$articles_per_feed)->get();
        $sub_query_values = $this->Record->values;
        $this->Record->reset();

        $query = $this->Record->appendValues($sub_query_values)->select()->from([$sub_query=>'temp'])->get();
        $query_values = $this->Record->values;
        $this->Record->reset();

        $this->Record->appendValues($query_values)
            ->from('feed_reader_articles')
            ->where('feed_reader_articles.id', 'notin', [$query], false)
            ->where('feed_reader_articles.feed_id', '=', $feed_id)
            ->delete();
    }

    /**
     * Builds a partially constructed query Record object use to fetch or count results
     *
     * @param int $staff_id The ID of the staff member to fetch articles for
     * @param int $company_id The ID of the company to fetch articles for
     * @param int $feed_id The ID of the feed to fetch articles for
     *  (if not given will fetch articles for ALL subscribed feeds)
     * @return Record A partially constructed query Record object
     * @see FeedReaderFeeds::getArticles()
     * @see FeedReaderFeeds::getArticlesCount()
     */
    private function articles($staff_id, $company_id, $feed_id = null)
    {

        // fetch the new articles from the system if the feed has not been updated recently
        if ($feed_id) {
            // Refresh the articles for the given feed
            $this->fetchArticles($feed_id, self::$query_feed_rate);
        } else {
            $feeds = $this->getSubscribedFeeds($staff_id, $company_id);

            // Refresh the articles for all subscribed feeds
            foreach ($feeds as $feed) {
                $this->fetchArticles($feed->id, self::$query_feed_rate);
            }
        }

        // List all articles explicitly or inherintly subscribed to by the staff member at the given company
        $this->Record->select(['feed_reader_articles.*'])->from('feed_reader_feeds')->
            innerJoin('feed_reader_articles', 'feed_reader_articles.feed_id', '=', 'feed_reader_feeds.id', false)->
            on('feed_reader_subscribers.staff_id', '=', $staff_id)->
            on('feed_reader_subscribers.company_id', '=', $company_id)->
            innerJoin('feed_reader_subscribers', 'feed_reader_subscribers.feed_id', '=', 'feed_reader_feeds.id', false);

        // Set the ID of the specific feed to fetch if given
        if ($feed_id) {
            $this->Record->where('feed_reader_subscribers.feed_id', '=', $feed_id);
        }

        return $this->Record;
    }

    /**
     * Checks if the given feed has any subscribers either for the feed in general or for the given company
     *
     * @param int $feed_id The ID of the feed to check for subscribers
     * @param int $company_id The ID of the company to check explicitly for subscribers to the given feed
     * @return bool True if there are subscribers, false otherwise
     */
    private function hasSubscribers($feed_id, $company_id = null)
    {
        $this->Record->select(['feed_id'])->from('feed_reader_subscribers')->
            where('feed_id', '=', $feed_id);

        if ($company_id) {
            $this->Record->where('company_id', '=', $company_id);
        }

        return (boolean)$this->Record->fetch();
    }

    /**
     * Fetches the ID of the feed with the given URL, if it exists
     *
     * @param string $url The URL to query a feed for
     * @return int The ID of the feed with the given URL, false if none exists
     */
    private function getFeedIdByUrl($url)
    {
        $feed = $this->Record->select(['id'])->from('feed_reader_feeds')->
            where('url', '=', $url)->fetch();

        if ($feed) {
            return $feed->id;
        }
        return false;
    }
}
