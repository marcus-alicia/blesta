<?php
/**
 * Support Manager Knowledgebase controller
 *
 * @package blesta
 * @subpackage blesta.plugins.support_manager
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Knowledgebase extends SupportManagerKbController
{
    /**
     * Setup
     */
    public function preAction()
    {
        parent::preAction();

        $this->uses(
            ['PluginManager', 'SupportManager.SupportManagerKbCategories', 'SupportManager.SupportManagerKbArticles']
        );
        $this->helpers(['TextParser']);
        $Markdown = $this->TextParser->create('markdown');
        $Markdown->setBreaksEnabled(true)->setSafeMode(false);
        $this->set('Markdown', $Markdown);

        // Redirect if this plugin is not installed for this company
        if (!$this->PluginManager->isInstalled('support_manager', $this->company_id)) {
            $this->redirect($this->client_uri);
        }

        $this->base_uri = WEBDIR;
        $this->view->base_uri = $this->base_uri;
        $this->structure->base_uri = $this->base_uri;

        $this->structure->setView(null, $this->orig_structure_view);
        $this->structure->set(
            'custom_head',
            '<link href="'
            . Router::makeURI(
                str_replace('index.php/', '', WEBDIR) . $this->view->view_path . 'views/' . $this->view->view
            )
            . '/css/kb.css" rel="stylesheet" type="text/css" />'
        );

        $this->structure->set('page_title', Language::_('Knowledgebase.index.page_title', true));
        $this->structure->set('title', Language::_('Knowledgebase.index.page_title', true));
    }

    /**
     * Sets the search bar partial to the view
     *
     * @param array $vars A set of input vars to set as vars to the template
     */
    private function setSearchBar(array $vars = [])
    {
        $this->view->set('search_bar', $this->partial('knowledgebase_search_bar', ['vars' => (object)$vars]));
    }

    /**
     * Sets bread crumbs to the view
     *
     * @param mixed $category The ID of the knowledgebase category, or null for no category
     * @param stdClass $article An stdClass object representing the article being viewed
     */
    private function setBreadCrumbs($category = null, $article = null)
    {
        // Fetch the breadcrumbs
        $categories = $this->getBreadCrumbs(($category ? $category->id : null));

        // If no categories are known, but this article belongs to only one, then build the breadcrumbs to this article
        if ($article && empty($categories) && isset($article->categories) && count($article->categories) == 1) {
            $categories = $this->getBreadCrumbs($article->categories[0]->id);
        }

        $this->set(
            'bread_crumbs',
            $this->partial(
                'knowledgebase_breadcrumbs_bar',
                ['current_category' => $category, 'categories' => $categories, 'article' => $article]
            )
        );
    }

    /**
     * Knowledgebase overview
     */
    public function index()
    {
        // Get the current category to view
        $current_category_id = (isset($this->get[0]) ? $this->get[0] : null);
        $category = null;
        $access = $this->getAccess();
        if ($current_category_id !== null
            && ($category = $this->SupportManagerKbCategories->get($current_category_id))
        ) {
            // This category cannot be viewed if it is hidden, and is only accessible to users that are logged in
            if ($category->company_id != $this->company_id || !in_array($category->access, $access)) {
                $category = null;
                $current_category_id = null;
            }
        }

        // Fetch popular articles on the home/overview page
        if ($category === null) {
            // Fetch the popular articles
            $popular_articles = $this->SupportManagerKbArticles->getPopular(
                $this->company_id,
                null,
                $access,
                (int)Configure::get('SupportManager.max_kb_popular_articles')
            );

            // Set the article content language to use into the article
            foreach ($popular_articles as &$article) {
                $article = (object)array_merge((array)$article, (array)$this->getArticleContent($article));
                $article->uri_title = $this->getArticleTitleUri($article);
            }
            $this->set('popular_articles', $popular_articles);
        } else {
            // Fetch articles from this category
            $articles = $this->SupportManagerKbArticles->getAll($this->company_id, $category->id, $access);

            // Set the article content language to use into the article
            foreach ($articles as &$article) {
                $article = (object)array_merge((array)$article, (array)$this->getArticleContent($article));
                $article->uri_title = $this->getArticleTitleUri($article);
            }
            $this->set('articles', $articles);
        }

        // Only show breadcrumbs on subcategory pages
        if ($category) {
            $this->setBreadCrumbs($category);
        }

        $this->set(
            'categories',
            $this->SupportManagerKbCategories->getAll($this->company_id, $current_category_id, false, $access)
        );
        $this->set('current_category', $category);
        $this->setSearchBar();
    }

    /**
     * List article results
     */
    public function search()
    {
        $access = $this->getAccess();
        $page = (isset($this->get[0]) ? (int)$this->get[0] : 1);
        $total_results = 0;
        $search = '';

        // Set the search terms
        if (!empty($this->post['search'])) {
            $search = trim($this->post['search']);
        } elseif (!empty($this->get['search'])) {
            $search = trim($this->get['search']);
        }

        // Search for the articles
        if (!empty($search)) {
            $articles = $this->SupportManagerKbArticles->search($this->company_id, $search, $access, $page);
            $total_results = $this->SupportManagerKbArticles->getSearchCount($this->company_id, $search, $access);

            // Set the article content language to use into the article
            foreach ($articles as &$article) {
                $article = (object)array_merge((array)$article, (array)$this->getArticleContent($article));
                $article->uri_title = $this->getArticleTitleUri($article);
            }
            $this->set('articles', $articles);

            // If there is only one result, redirect to it
            if ($total_results == 1 && !empty($articles[0])) {
                $this->redirect(
                    $this->base_uri . 'plugin/support_manager/knowledgebase/view/'
                    . $articles[0]->id . '/' . $articles[0]->uri_title . '/'
                );
            }

            $vars = (object)['search' => $search];
        }

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination_client'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'plugin/support_manager/knowledgebase/search/[p]/',
                'params' => ['search' => $search]
            ]
        );
        $this->setPagination($this->get, $settings, false);

        if (!isset($vars)) {
            $vars = new stdClass();
        }

        $this->set('vars', $vars);
        $this->setBreadCrumbs();
        $this->setSearchBar((array)$vars);
    }

    /**
     * Display an article
     */
    public function view()
    {
        // Ensure a valid article was given
        $access = $this->getAccess();
        if (!isset($this->get[0]) || !($article = $this->SupportManagerKbArticles->get((int)$this->get[0]))
            || $article->company_id != $this->company_id
            || !in_array($article->access, $access)
        ) {
            $this->redirect($this->base_uri . 'plugin/support_manager/knowledgebase/');
        }

        // Get the current category
        $current_category_id = (isset($this->get[2]) ? $this->get[2] : null);
        $category = null;
        if ($current_category_id !== null
            && ($category = $this->SupportManagerKbCategories->get($current_category_id))
        ) {
            // This category cannot be viewed if it is hidden, and is only accessible to users that are logged in
            if ($category->company_id != $this->company_id || !in_array($category->access, $access)) {
                $category = null;
            }

            // This category must belong to the article
            $available = false;
            foreach ($article->categories as $cat) {
                if ($cat->id == $category->id) {
                    $available = true;
                    break;
                }
            }

            if (!$available) {
                $category = null;
            }
        }

        // Merge the article content for the language into this article
        $article = (object)array_merge((array)$article, (array)$this->getArticleContent($article));
        $article->uri_title = $this->getArticleTitleUri($article);

        // Fetch articles that this user has rated
        $rated_articles = $this->Session->read('support_manager_kb_rated_articles');
        $rated_articles = ($rated_articles ? (array)$rated_articles : []);

        $this->set('article', $article);
        $this->set('current_category', $category);
        $this->set('is_admin', is_numeric($this->Session->read('blesta_staff_id')));
        $this->set('voted', in_array($article->id, $rated_articles));
        $this->setBreadCrumbs($category, $article);
        $this->setSearchBar();

        // Override page title using article title
        $this->structure->set('page_title', Language::_('Knowledgebase.view.page_title', true, $article->title));
    }

    /**
     * AJAX rate an article
     */
    public function rate()
    {
        // Ensure we have a valid article
        $access = $this->getAccess();
        if (!$this->isAjax() || !isset($this->get[0])
            || !($article = $this->SupportManagerKbArticles->get((int)$this->get[0]))
            || $article->company_id != $this->company_id
            || !in_array($article->access, $access)
        ) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        // Set articles this user has rated
        $rated_articles = $this->Session->read('support_manager_kb_rated_articles');
        $rated_articles = ($rated_articles ? (array)$rated_articles : []);

        // Rate the article
        $rating = [];
        if (!in_array($article->id, $rated_articles) && !empty($this->post['direction'])) {
            $this->SupportManagerKbArticles->vote($article->id, $this->post['direction']);

            // Mark this article as rated for this user
            if (!$this->SupportManagerKbArticles->errors()) {
                $this->Session->write(
                    'support_manager_kb_rated_articles',
                    array_merge($rated_articles, [$article->id])
                );
            }

            // Re-fetch the article
            $article = $this->SupportManagerKbArticles->get($article->id);

            // Set data to update the page with
            if ($article && (in_array($this->post['direction'], ['up', 'down']))) {
                $rating['direction'] = $this->post['direction'];
                $rating['rating'] = ($this->post['direction'] == 'up' ? $article->up_votes : $article->down_votes);
            }
        }

        $this->outputAsJson($rating);
        return false;
    }

    private function getAccess()
    {
        $access = ['public'];
        if ($this->isLoggedIn()) {
            $access = array_merge($access, ['private']);
        }

        if (!empty($this->Session->read('blesta_staff_id'))) {
            $access = array_merge($access, ['staff']);
        }

        return $access;
    }
}
