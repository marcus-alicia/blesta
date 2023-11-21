<?php
/**
 * SupportManagerKbArticles model
 *
 * @package blesta
 * @subpackage blesta.plugins.support_manager
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SupportManagerKbArticles extends SupportManagerModel
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        Language::loadLang('support_manager_kb_articles', null, PLUGINDIR . 'support_manager' . DS . 'language' . DS);
    }


    /**
     * Creates a new knowledge base category
     *
     * @param array $vars A list of article fields including:
     *  - company_id The ID of the company to assign this article to
     *  - access The type of access to give to this article ("public", "private", "hidden", or "staff"; optional, default public)
     *  - content A numerically-indexed array containing article content, including:
     *      - lang The language code in ISO 639-1 ISO 3166-1 alpha-2 concatenated format (e.g. "en_us")
     *      - title The title of this article
     *      - body The body content of this article
     *      - content_type The type of body content set ('html' or 'text')
     *  - categories A numerically-indexed array of category IDs to assign this article to (optional)
     * @return stdClass An stdClass object representing the article, or void on error
     */
    public function add(array $vars)
    {
        // Set date created
        $vars['date_created'] = date('c');
        $vars['date_updated'] = $vars['date_created'];

        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $this->Record->begin();

            $this->Record->insert(
                'support_kb_articles',
                $vars,
                ['company_id', 'access', 'date_created', 'date_updated']
            );
            $article_id = $this->Record->lastInsertId();

            // Add the article content
            foreach ($vars['content'] as $content) {
                $this->addContent($article_id, $content);
            }

            // Add the article categories
            if (isset($vars['categories'])) {
                foreach ($vars['categories'] as $category_id) {
                    $this->addCategory($article_id, $category_id);
                }
            }

            $this->Record->commit();
        }
    }

    /**
     * Updates a knowledge base category
     *
     * @param int $article_id The ID of the article to edit
     * @param array $vars A list of article fields including:
     *  - access The type of access to give to this category ("public", "private", "hidden", or "staff"; optional)
     *  - content A numerically-indexed array containing article content.
     *      If title and body are blank, the content will be deleted. Fields required include:
     *      - lang The language code in ISO 639-1 ISO 3166-1 alpha-2 concatenated format (e.g. "en_us")
     *      - title The title of this article
     *      - body The body content of this article
     *      - content_type The type of body content set ('html' or 'text')
     *  - categories A numerically-indexed array of category IDs to assign this article to (optional)
     * @return stdClass An stdClass object representing the article, or void on error
     */
    public function edit($article_id, array $vars)
    {
        // Set date updated
        $vars['date_updated'] = date('c');
        $vars['article_id'] = $article_id;

        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            $this->Record->begin();

            $this->Record->where('id', '=', $article_id)
                ->update('support_kb_articles', $vars, ['access', 'date_updated']);

            // Add the article content
            foreach ($vars['content'] as $content) {
                $this->addContent($article_id, $content);
            }

            // Add the article categories
            $this->deleteCategories($article_id);
            if (isset($vars['categories'])) {
                foreach ($vars['categories'] as $category_id) {
                    $this->addCategory($article_id, $category_id);
                }
            }

            $this->Record->commit();
        }
    }

    /**
     * Deletes a knowledge base article
     *
     * @param int $article_id The ID of the article to delete
     */
    public function delete($article_id)
    {
        // Delete this article and all associations to it
        $this->Record->from('support_kb_articles')
            ->leftJoin(
                'support_kb_article_content',
                'support_kb_article_content.article_id',
                '=',
                'support_kb_articles.id',
                false
            )
            ->leftJoin(
                'support_kb_article_categories',
                'support_kb_article_categories.article_id',
                '=',
                'support_kb_articles.id',
                false
            )
            ->where('support_kb_articles.id', '=', $article_id)
            ->delete(['support_kb_articles.*', 'support_kb_article_content.*', 'support_kb_article_categories.*']);
    }

    /**
     * Adds or updates article content. Removes content if the title and body are empty, but the language is given
     * @see SupportManagerKbArticles::add, SupportManagerKbArticles::edit
     *
     * @param int $article_id The ID of the article to add content to
     * @param array $content The article content to add including:
     *  - lang The language code in ISO 639-1 ISO 3166-1 alpha-2 concatenated format (e.g. "en_us")
     *  - title The title of this article
     *  - body The body content of this article
     *  - content_type The type of body content set ('html' or 'text')
     */
    private function addContent($article_id, $content)
    {
        $content['article_id'] = $article_id;

        // Language is required
        if (empty($content['lang'])) {
            return;
        }

        $title = (empty($content['title']) ? '' : $content['title']);
        $body = (empty($content['body']) ? '' : $content['body']);
        $content_type = (empty($content['content_type'])
            || !in_array($content['content_type'], array_keys($this->getContentTypes()))
            ? 'text'
            : $content['content_type']
        );

        // Remove article content for a language if it is blank
        if (empty($content['title']) && empty($content['body'])) {
            $this->deleteContent($article_id, $content['lang']);
        } else {
            // Add/update article content
            $this->Record->duplicate('title', '=', $title)->
                duplicate('body', '=', $body)->
                duplicate('content_type', '=', $content_type)->
                insert('support_kb_article_content', $content, ['article_id', 'lang', 'title', 'body', 'content_type']);
        }
    }

    /**
     * Deletes article content for the given language
     *
     * @param int $article_id The ID of the article to add content to
     * @param string $lang The language code in ISO 639-1 ISO 3166-1 alpha-2 concatenated format (e.g. "en_us")
     */
    private function deleteContent($article_id, $lang)
    {
        $this->Record->from('support_kb_article_content')->
            where('article_id', '=', $article_id)->
            where('lang', '=', $lang)->
            delete();
    }

    /**
     * Adds an article category
     * @see SupportManagerKbArticles::add, SupportManagerKbArticles::edit
     *
     * @param int $article_id The ID of the article to assign the category
     * @param int $category_id The ID of the category to add
     */
    private function addCategory($article_id, $category_id)
    {
        if (!empty($category_id)) {
            $this->Record->duplicate('article_id', '=', $article_id)->
                insert('support_kb_article_categories', ['article_id' => $article_id, 'category_id' => $category_id]);
        }
    }

    /**
     * Deletes all categories assigned to an article
     * @see SupportManagerKbArticles::edit
     *
     * @param int $article_id The ID of the article whose categories to remove
     */
    private function deleteCategories($article_id)
    {
        $this->Record->from('support_kb_article_categories')->
            where('article_id', '=', $article_id)->
            delete(['support_kb_article_categories.*']);
    }

    /**
     * Adds a vote for an article
     *
     * @param int $article_id The ID of the article to add the vote for
     * @param string $direction The type of vote to add to the article (i.e. "up" or "down"; optional, default up)
     */
    public function vote($article_id, $direction = 'up')
    {
        $field = (($direction != 'down') ? 'up_votes' : 'down_votes');

        $this->Record->where('id', '=', $article_id)->
            set($field, $field . '+1', false, false)->
            update('support_kb_articles');
    }

    /**
     * Retrieves an article
     *
     * @param int $article_id The ID of the article to fetch
     * @return mixed An stdClass object representing the article, or false if it does not exist
     */
    public function get($article_id)
    {
        $this->Record = $this->getArticles($article_id);

        $article = $this->Record->fetch();

        if ($article) {
            $article->content = $this->getContent($article_id);
            $article->categories = $this->getCategories($article_id);
        }

        return $article;
    }

    /**
     * Retrieves a list of all articles in the given company or category
     *
     * @param int $company_id The ID of the company to fetch articles from (optional)
     * @param int $category_id The ID of the category to fetch articles from
     *  (optional, default null for the base/home category)
     * @param mixed $access A numerically-indexed array containing the access levels of
     *  articles that can be fetched, or null for all
     *  (i.e. "public", "private", "hidden", or "staff"; optional, default null for all)
     * @return array An array of stdClass objects each repreresting an article
     */
    public function getAll($company_id = null, $category_id = null, $access = null)
    {
        $this->Record = $this->getArticles(null, $company_id);

        // Filter by category
        $this->Record->leftJoin(
            'support_kb_article_categories',
            'support_kb_article_categories.article_id',
            '=',
            'support_kb_articles.id',
            false
        )
        ->where('support_kb_article_categories.category_id', '=', $category_id);

        // Filter by access level
        if ($access !== null && is_array($access)) {
            $this->Record->where('support_kb_articles.access', 'in', array_values($access));
        }

        $articles = $this->Record->fetchAll();

        foreach ($articles as &$article) {
            $article->content = $this->getContent($article->id);
            $article->categories = $this->getCategories($article->id);
        }

        return $articles;
    }

    /**
     * Fetches a set of popular articles as determined by votes cast on the article
     *
     * @param int $company_id The ID of the company to fetch articles from (optional)
     * @param int $category_id The ID of the category to fetch articles from
     *  (optional, default null to fetch from all articles)
     * @param mixed $access A numerically-indexed array containing the access levels of
     *  articles that can be fetched, or null for all
     *  (i.e. "public", "private", "hidden", or "staff"; optional, default null for all)
     * @param int $max_articles The maximum number of articles to fetch (optional, default 5)
     * @return array An array of stdClass objects each repreresting an article
     */
    public function getPopular($company_id = null, $category_id = null, $access = null, $max_articles = 5)
    {
        $this->Record = $this->getArticles(null, $company_id);

        // Filter by category and votes
        $this->Record->select(
            [
                'CAST(support_kb_articles.up_votes AS SIGNED) - CAST(support_kb_articles.down_votes AS SIGNED)'
                => 'votes'
            ],
            false
        )
            ->innerJoin(
                'support_kb_article_categories',
                'support_kb_article_categories.article_id',
                '=',
                'support_kb_articles.id',
                false
            )
            ->having('votes', '>', 0);

        if ($category_id) {
            $this->Record->where('support_kb_article_categories.category_id', '=', $category_id);
        }

        // Filter by access level
        if ($access !== null && is_array($access)) {
            $this->Record->where('support_kb_articles.access', 'in', array_values($access));
        }

        $articles = $this->Record->group(['support_kb_articles.id'])
            ->order(['votes' => 'DESC'])
            ->limit((int)$max_articles)
            ->fetchAll();

        foreach ($articles as &$article) {
            $article->content = $this->getContent($article->id);
            $article->categories = $this->getCategories($article->id);
        }

        return $articles;
    }

    /**
     * Retrieves content for an article in the given language
     *
     * @param int $article_id The ID of the article to fetch content for
     * @param string $lang The language code in ISO 639-1 ISO 3166-1 alpha-2
     *  concatenated format (e.g. "en_us"; optional)
     * @return array An array of stdClass objects representing the articles' content in each language
     */
    public function getContent($article_id, $lang = null)
    {
        $this->Record->select()->from('support_kb_article_content')->
            where('article_id', '=', $article_id);

        if ($lang) {
            $this->Record->where('lang', '=', $lang);
        }

        return $this->Record->fetchAll();
    }

    /**
     * Retrieves content for an article in the given language
     *
     * @param int $article_id The ID of the article to fetch assigned articles for
     * @return array An array of stdClass objects representing each category
     */
    public function getCategories($article_id)
    {
        return $this->Record->select(['support_kb_categories.*'])
            ->from('support_kb_categories')
            ->innerJoin(
                'support_kb_article_categories',
                'support_kb_article_categories.category_id',
                '=',
                'support_kb_categories.id',
                false
            )
            ->innerJoin(
                'support_kb_articles',
                'support_kb_articles.id',
                '=',
                'support_kb_article_categories.article_id',
                false
            )
            ->where('support_kb_articles.id', '=', $article_id)
            ->fetchAll();
    }

    /**
     * Retrieves a list of article access types and their language
     *
     * @return array An array of access types and their language
     */
    public function getAccessTypes()
    {
        return [
            'public' => $this->_('SupportManagerKbArticles.access_types.public'),
            'private' => $this->_('SupportManagerKbArticles.access_types.private'),
            'hidden' => $this->_('SupportManagerKbArticles.access_types.hidden'),
            'staff' => $this->_('SupportManagerKbArticles.access_types.staff')
        ];
    }

    /**
     * Retrieves a list of article content types and their language
     *
     * @return An array of content types and their language
     */
    public function getContentTypes()
    {
        return [
            'text' => $this->_('SupportManagerKbArticles.content_types.text'),
            'html' => $this->_('SupportManagerKbArticles.content_types.html')
        ];
    }

    /**
     * Searches for articles that match the given query
     *
     * @param int $company_id The ID of the company to fetch articles from
     * @param string $query The query to search articles for
     * @param mixed $access A numerically-indexed array containing the access levels of
     *  articles that can be fetched, or null for all
     *  (i.e. "public", "private", "hidden", or "staff"; optional, default null for all)
     * @param int $page The page number of search results to fetch (optional, default 1)
     * @return array An array of stdClass objects representing each matching article
     */
    public function search($company_id, $query, $access = null, $page = 1)
    {
        $this->Record = $this->searchArticles($company_id, $query, $access);

        return $this->Record->limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->fetchAll();
    }

    /**
     * Fetches the number of results that match the given query
     *
     * @param int $company_id The ID of the company to fetch articles from
     * @param string $query The query to search articles for
     * @param mixed $access A numerically-indexed array containing the access levels of
     *  articles that can be fetched, or null for all
     *  (i.e. "public", "private", "hidden", or "staff"; optional, default null for all)
     * @return array An array of stdClass objects representing each matching article
     */
    public function getSearchCount($company_id, $query, $access = null)
    {
        return $this->searchArticles($company_id, $query, $access)->numResults();
    }

    /**
     * Partially constructs a Record object to search articles
     *
     * @param int $company_id The ID of the company to fetch articles from
     * @param string $query The query to search articles for
     * @param mixed $access A numerically-indexed array containing the access levels of
     *  articles that can be fetched, or null for all
     *  (i.e. "public", "private", "hidden", or "staff"; optional, default null for all)
     * @return Record A partially-constructed Record object for searching articles
     */
    private function searchArticles($company_id, $query, $access = null)
    {
        $this->Record = $this->getArticles(null, $company_id);

        $this->Record->innerJoin(
            'support_kb_article_content',
            'support_kb_article_content.article_id',
            '=',
            'support_kb_articles.id',
            false
        )
            ->open()
                ->like('support_kb_article_content.title', '%' . $query . '%')
                ->orLike('support_kb_article_content.body', '%' . $query . '%')
            ->close();

        // Filter by access level
        if ($access !== null && is_array($access)) {
            $this->Record->where('support_kb_articles.access', 'in', array_values($access));
        }

        // Group on article to avoid duplicates
        $this->Record->group(['support_kb_articles.id']);

        return $this->Record;
    }

    /**
     * Partially constructs a Record object for fetching articles
     *
     * @param int $article The ID of the article to fetch
     * @return Record A partially-constructed Record object
     */
    private function getArticles($article_id = null, $company_id = null)
    {
        $this->Record->select(['support_kb_articles.*'])->from('support_kb_articles');

        if ($article_id) {
            $this->Record->where('support_kb_articles.id', '=', $article_id);
        }
        if ($company_id) {
            $this->Record->where('support_kb_articles.company_id', '=', $company_id);
        }

        return $this->Record;
    }

    /**
     * Retrieves rules for validating adding/editing a article
     *
     * @param array $vars A set of input data
     * @param bool $edit True to fetch the edit rules, or false for the add rules (optional, default false)
     * @return array A set of validation rules
     */
    private function getRules(array $vars, $edit = false)
    {
        $rules = [
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('SupportManagerKbArticles.!error.company_id.exists')
                ]
            ],
            'access' => [
                'type' => [
                    'if_set' => true,
                    'rule' => ['in_array', array_keys($this->getAccessTypes())],
                    'message' => $this->_('SupportManagerKbArticles.!error.access.type')
                ]
            ],
            'content[][lang]' => [
                'length' => [
                    'rule' => ['betweenLength', 5, 5],
                    'message' => $this->_('SupportManagerKbArticles.!error.content[][lang].length')
                ]
            ],
            'content[][title]' => [
                'length' => [
                    'rule' => ['maxLength', 255],
                    'message' => $this->_('SupportManagerKbArticles.!error.content[][title].length')
                ]
            ],
            'content[][content_type]' => [
                'valid' => [
                    'rule' => ['in_array', array_keys($this->getContentTypes())],
                    'message' => $this->_('SupportManagerKbArticles.!error.content[][content_type].valid')
                ]
            ],
            'content' => [
                'empty' => [
                    'rule' => [[$this, 'validateContentGiven']],
                    'message' => $this->_('SupportManagerKbArticles.!error.content.empty')
                ]
            ],
            'categories' => [
                'company' => [
                    'if_set' => true,
                    'pre_format' => ['array_unique'],
                    'rule' => [
                        [$this, 'validateCategoriesCompany'],
                        (isset($vars['company_id']) ? $vars['company_id'] : null),
                        (isset($vars['article_id']) ? $vars['article_id'] : null)
                    ],
                    'message' => $this->_('SupportManagerKbArticles.!error.categories.company'),
                ]
            ],
            'date_created' => [
                'valid' => [
                    'rule' => true,
                    'message' => '',
                    'post_format' => [[$this, 'dateToUtc']]
                ]
            ],
            'date_updated' => [
                'valid' => [
                    'rule' => true,
                    'message' => '',
                    'post_format' => [[$this, 'dateToUtc']]
                ]
            ],
        ];

        if ($edit) {
            // Editing cannot change company
            unset($rules['company_id'], $rules['date_created']);

            // Validate the article itself exists to edit
            $rules['article_id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'support_kb_articles'],
                    'message' => $this->_('SupportManagerKbArticles.!error.article_id.exists')
                ]
            ];
        }

        return $rules;
    }

    /**
     * Validates whether every given category belongs to the same company as the article
     *
     * @param array $categories A numerically-indexed array of category IDs
     * @param int $company_id The ID of the company that the article is assigned
     *  (optional, required if $article_id not given)
     * @param int $article_id The ID of the article to check against the categories
     *  (optional, requride if $company_id not given)
     * @return bool False if the categories given do not exist or are not assigned the
     *  same company as the article; true otherwise
     */
    public function validateCategoriesCompany($categories, $company_id = null, $article_id = null)
    {
        // Check we have a company ID to compare with
        if (empty($company_id)) {
            if (empty($article_id) || !($article = $this->get($article_id))) {
                return false;
            }
            $company_id = $article->company_id;
        }

        // Check each category belongs to the expected company
        if (is_array($categories)) {
            foreach ($categories as $category_id) {
                // Skip blank categories that may be set--they will not be added
                if (empty($category_id)) {
                    continue;
                }

                $category = $this->Record->select()
                    ->from('support_kb_categories')
                    ->where('id', '=', $category_id)
                    ->fetch();
                if (!$category || $category->company_id != $company_id) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validates that at least one language set (lang, title, body) is entered,
     * and that all others are given as well, or empty
     *
     * @param array $article_content An array of article content in multiple languages, containing:
     *  - lang The language code in ISO 639-1 ISO 3166-1 alpha-2 concatenated format (e.g. "en_us")
     *  - title The title of this article
     *  - body The body content of this article
     * @return bool True if at least one article content is complete and there
     *  are no others partially complete, otherwise false
     */
    public function validateContentGiven($article_content)
    {
        // Content is missing
        if (empty($article_content) || !is_array($article_content)) {
            return false;
        }

        $one_set_given = false;
        foreach ($article_content as $content) {
            $partial = [
                'lang' => !empty($content['lang']),
                'title' => !empty($content['title']),
                'body' => !empty($content['body']),
                'content_type' => !empty($content['content_type'])
            ];

            // This is a complete valid set
            if ($partial['lang'] && $partial['title'] && $partial['body'] && $partial['content_type']) {
                $one_set_given = true;
                continue;
            }

            // This is an incomplete partial set
            if ($partial['title'] || $partial['body']) {
                return false;
            }
        }

        return $one_set_given;
    }
}
