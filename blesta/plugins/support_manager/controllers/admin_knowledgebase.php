<?php
/**
 * Support Manager Admin Knowledge Base controller
 *
 * @package blesta
 * @subpackage blesta.plugins.support_manager
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminKnowledgebase extends SupportManagerKbController
{
    /**
     * Setup
     */
    public function preAction()
    {
        parent::preAction();

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);
        $this->requireLogin();

        $this->uses(['SupportManager.SupportManagerKbCategories', 'SupportManager.SupportManagerKbArticles']);

        $this->staff_id = $this->Session->read('blesta_staff_id');
    }

    /**
     * Fetches a partial of article content
     *
     * @param array $vars A set of article content keyed by language code
     * @return string A partial containing the article content
     */
    private function getArticleContentPartial(array $vars = [])
    {
        $this->uses(['Languages']);
        $languages = $this->Languages->getAll($this->company_id);

        // Include WYSIWYG
        $this->Javascript->setFile('blesta/ckeditor/build/ckeditor.js', 'head', VENDORWEBDIR);

        $vars = [
            'languages' => $languages,
            'vars' => $vars,
            'content_types' => $this->SupportManagerKbArticles->getContentTypes()
        ];

        return $this->partial('admin_knowledgebase_article_content', $vars);
    }

    /**
     * Builds a list of nested categories to be used as select field options
     *
     * @param string $symbol The symbol to use to signify a further nesting for children (optional, default "-")
     * @return array A list of categories
     */
    private function getCategoriesList($symbol = '-')
    {
        $cats = $this->SupportManagerKbCategories->getAll($this->company_id, null, true);
        $categories = [];

        // Merge all base categories with their children
        foreach ($cats as $category) {
            $categories = ($categories + [$category->id => $category->name]
                + $this->buildCategoriesList($category->children, $symbol)
            );
        }

        return $categories;
    }

    /**
     * Support method for AdminKnowledgebase::getCategoriesList. Recursively builds the list of categories
     *
     * @param array $categories A set of formatted categories to include (optional)
     * @param string $symbol The symbol to use to signify a further nesting (optional, default "-")
     * @return A list of categories
     */
    private function buildCategoriesList($categories, $symbol)
    {
        $sub_categories = [];

        foreach ($categories as $category) {
            $sub_categories += ([$category->id => $symbol . ' ' . $category->name]
                + $this->buildCategoriesList($category->children, $symbol . substr($symbol, 0, 1))
            );
        }

        return $sub_categories;
    }

    /**
     * List knowledge base categories and articles
     */
    public function index()
    {
        // Get the current category
        $current_category_id = (isset($this->get[0]) ? $this->get[0] : null);
        $category = null;
        if ($current_category_id !== null) {
            $category = $this->SupportManagerKbCategories->get($current_category_id);
        }

        // Fetch the default language
        $this->components(['SettingsCollection']);
        $default_lang = $this->SettingsCollection->fetchSetting(null, $this->company_id, 'language');
        $default_lang = (isset($default_lang['value']) ? $default_lang['value'] : '');

        // Fetch the articles
        $articles = $this->SupportManagerKbArticles->getAll($this->company_id, $current_category_id);

        // Set article content in the language to show
        foreach ($articles as &$article) {
            $article = (object)array_merge((array)$article, (array)$this->getArticleContent($article));
            $article->uri_title = $this->getArticleTitleUri($article);
        }

        $this->set('categories', $this->SupportManagerKbCategories->getAll($this->company_id, $current_category_id));
        $this->set('articles', $articles);
        $this->set('current_category', $category);
        $this->set('default_language', $default_lang);
        $this->set(
            'bread_crumbs',
            $this->partial(
                'admin_knowledgebase_breadcrumbs_list',
                [
                    'current_category' => $category,
                    'categories' => $this->getBreadCrumbs(($category ? $category->id : null))
                ]
            )
        );
    }

    /**
     * Updates input data for body content to appropriate field names
     * @see AdminKnowledgebase::add, AdminKnowledgebase::edit
     *
     * @param array $content A list of article content
     * @return array A list of article content
     */
    private function getArticleBodyContent($content = [])
    {
        // Change field names from "body_html" or "body_text" to simply "body"
        if (is_array($content)) {
            foreach ($content as &$article) {
                $content_type = (isset($article['content_type']) ? $article['content_type'] : 'text');

                if ($content_type == 'text' && isset($article['body_text'])) {
                    $article['body'] = $article['body_text'];
                } elseif ($content_type == 'html' && isset($article['body_html'])) {
                    $article['body'] = $article['body_html'];
                }
            }
        }

        return $content;
    }

    /**
     * Adds an article
     */
    public function add()
    {
        // Set a category to assign to this article by default
        $parent_category = null;
        if (isset($this->get[0]) && ($category = $this->SupportManagerKbCategories->get($this->get[0])) &&
            $category->company_id == $this->company_id) {
            $parent_category = $category;
        }

        if (!empty($this->post)) {
            // Add the article
            $data = $this->post;
            $data['company_id'] = $this->company_id;

            // Set the body content
            $data['content'] = $this->getArticleBodyContent((isset($data['content']) ? $data['content'] : []));

            // Add the article
            $this->SupportManagerKbArticles->add($data);

            if (($errors = $this->SupportManagerKbArticles->errors())) {
                // Error
                $this->setMessage('error', $errors, false, null, false);
                $vars = (object)$data;
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminKnowledgebase.!success.article_added', true),
                    null,
                    false
                );
                $this->redirect(
                    $this->base_uri . 'plugin/support_manager/admin_knowledgebase/index/'
                    . ($parent_category ? $parent_category->id : '')
                );
            }
        }

        // Set default vars
        if (!isset($vars)) {
            // Select the given category by default
            $categories = [];
            if ($parent_category) {
                $categories[] = $parent_category->id;
            }

            $vars = (object)['categories' => $categories];
        }

        // Format the article content languages
        $content = [];
        if (isset($vars->content) && is_array($vars->content)) {
            foreach ($vars->content as $article_content) {
                $content_obj = (object)$article_content;
                if (isset($content_obj->lang)) {
                    $content[$content_obj->lang] = $content_obj;
                }
            }
        }

        // Set the article content
        $this->set('article_content', $this->getArticleContentPartial($content));
        $this->set('vars', $vars);
        $this->set('access_types', $this->SupportManagerKbCategories->getAccessTypes());
        $this->set(
            'categories',
            ['' => Language::_('AdminKnowledgebase.select.none', true)] + $this->getCategoriesList()
        );
        $this->set('category', $parent_category);
    }

    /**
     * Updates an article
     */
    public function edit()
    {
        // Ensure we have an article
        if (!isset($this->get[0]) || !($article = $this->SupportManagerKbArticles->get($this->get[0])) ||
            $article->company_id != $this->company_id) {
            $this->redirect($this->base_uri . 'plugin/support_manager/admin_knowledgebase/');
        }

        // Fetch the parent category, if given, for redirects
        $parent_category = null;
        if (isset($this->get[1]) && ($category = $this->SupportManagerKbCategories->get($this->get[1])) &&
            $category->company_id == $this->company_id) {
            $parent_category = $category;
        }

        if (!empty($this->post)) {
            // Set the body content
            $data = $this->post;
            $data['content'] = $this->getArticleBodyContent((isset($data['content']) ? $data['content'] : []));

            // Update the article
            $this->SupportManagerKbArticles->edit($article->id, $data);

            if (($errors = $this->SupportManagerKbArticles->errors())) {
                // Error
                $this->setMessage('error', $errors, false, null, false);
                $vars = (object)$data;
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminKnowledgebase.!success.article_updated', true),
                    null,
                    false
                );
                $this->redirect(
                    $this->base_uri . 'plugin/support_manager/admin_knowledgebase/index/'
                    . ($parent_category ? $parent_category->id : '')
                );
            }
        }

        // Set default vars
        $content = [];
        if (!isset($vars)) {
            $vars = $article;
            $vars->categories = array_values($this->Form->collapseObjectArray($vars->categories, 'id', 'id'));
        }

        // Format the article content languages
        if (isset($vars->content) && is_array($vars->content)) {
            foreach ($vars->content as $article_content) {
                $content_obj = (object)$article_content;
                if (isset($content_obj->lang)) {
                    $content[$content_obj->lang] = $content_obj;
                }
            }
        }

        // Set the article content
        $this->set('article_content', $this->getArticleContentPartial($content));
        $this->set('vars', $vars);
        $this->set('access_types', $this->SupportManagerKbCategories->getAccessTypes());
        $this->set(
            'categories',
            ['' => Language::_('AdminKnowledgebase.select.none', true)] + $this->getCategoriesList()
        );
        $this->set('category', $parent_category);
    }

    /**
     * Deletes an article
     */
    public function delete()
    {
        // Ensure a valid article was given
        if (!isset($this->post['id']) || !($article = $this->SupportManagerKbArticles->get($this->post['id'])) ||
            ($article->company_id != $this->company_id)) {
            $this->redirect($this->base_uri . 'plugin/support_manager/admin_knowledgebase/');
        }

        // Check for a category to redirect back to
        if (!empty($this->post['category_id'])
            && ($category = $this->SupportManagerKbCategories->get($this->post['category_id']))
            && ($category->company_id == $this->company_id)
        ) {
            $parent_category = $category;
        }

        // Delete the article
        $this->SupportManagerKbArticles->delete($article->id);

        $this->flashMessage('message', Language::_('AdminKnowledgebase.!success.article_deleted', true), null, false);
        $this->redirect(
            $this->base_uri . 'plugin/support_manager/admin_knowledgebase/index/'
            . (isset($parent_category) ? $parent_category->id : '')
        );
    }

    /**
     * Adds a category
     */
    public function addCategory()
    {
        // Determine if this category is being added under another
        $parent_category_id = null;
        if (isset($this->get[0]) && ($parent_category = $this->SupportManagerKbCategories->get($this->get[0])) &&
            $parent_category->company_id == $this->company_id) {
            $parent_category_id = $parent_category->id;
        }

        if (!empty($this->post)) {
            $data = $this->post;
            $data['company_id'] = $this->company_id;

            // Set parent to none if not given
            if (empty($data['parent_id'])) {
                $data['parent_id'] = null;
            }

            $this->SupportManagerKbCategories->add($data);

            if (($errors = $this->SupportManagerKbCategories->errors())) {
                // Error, reset vars
                $this->setMessage('error', $errors, false, null, false);
                $vars = (object)$this->post;
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminKnowledgebase.!success.category_added', true),
                    null,
                    false
                );
                $this->redirect(
                    $this->base_uri . 'plugin/support_manager/admin_knowledgebase/index/'
                    . ($parent_category_id ? $parent_category_id : '')
                );
            }
        }

        // Set the parent category
        if (!isset($vars)) {
            $vars = (object)['parent_id' => $parent_category_id];
        }

        $this->set('vars', $vars);
        $this->set('access_types', $this->SupportManagerKbCategories->getAccessTypes());
        $this->set('category', (isset($parent_category) && $parent_category ? $parent_category : null));
        $this->set(
            'categories',
            ['' => Language::_('AdminKnowledgebase.select.none', true)] + $this->getCategoriesList()
        );
    }

    /**
     * Updates a category
     */
    public function editCategory()
    {
        // Ensure we have a valid category
        if (!isset($this->get[0]) || !($category = $this->SupportManagerKbCategories->get($this->get[0])) ||
            $category->company_id != $this->company_id) {
            $this->redirect($this->base_uri . 'plugin/support_manager/admin_knowledgebase/');
        }

        if (!empty($this->post)) {
            // Set parent to none if not given
            $data = $this->post;
            if (empty($data['parent_id'])) {
                $data['parent_id'] = null;
            }

            // Update the category
            $this->SupportManagerKbCategories->edit($category->id, $data);

            if (($errors = $this->SupportManagerKbCategories->errors())) {
                // Error, reset vars
                $this->setMessage('error', $errors, false, null, false);
                $vars = (object)$this->post;
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminKnowledgebase.!success.category_updated', true),
                    null,
                    false
                );
                $this->redirect(
                    $this->base_uri . 'plugin/support_manager/admin_knowledgebase/index/'
                    . ($category->parent_id ? $category->parent_id : '')
                );
            }
        }

        if (!isset($vars)) {
            $vars = $category;
        }

        $this->set('access_types', $this->SupportManagerKbCategories->getAccessTypes());
        $this->set('vars', $vars);
        $this->set('category', $category);
        $this->set(
            'categories',
            ['' => Language::_('AdminKnowledgebase.select.none', true)] + $this->getCategoriesList()
        );
    }

    /**
     * Deletes a category
     */
    public function deleteCategory()
    {
        // Ensure a valid category was given
        if (!isset($this->post['id']) || !($category = $this->SupportManagerKbCategories->get($this->post['id'])) ||
            ($category->company_id != $this->company_id)) {
            $this->redirect($this->base_uri . 'plugin/support_manager/admin_knowledgebase/');
        }

        // Delete the category
        $this->SupportManagerKbCategories->delete($category->id);

        if (($errors = $this->SupportManagerKbCategories->errors())) {
            // Error, could not delete the category
            $this->flashMessage('error', $errors, null, false);
        } else {
            // Success
            $this->flashMessage(
                'message',
                Language::_('AdminKnowledgebase.!success.category_deleted', true),
                null,
                false
            );
        }

        $this->redirect(
            $this->base_uri . 'plugin/support_manager/admin_knowledgebase/index/'
            . ($category->parent_id ? $category->parent_id : '')
        );
    }
}
