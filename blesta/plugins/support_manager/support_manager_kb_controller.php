<?php
/**
 * Support Manager parent controller
 *
 * @package blesta
 * @subpackage blesta.plugins.support_manager
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SupportManagerKbController extends SupportManagerController
{
    /**
     * Setup
     */
    public function preAction()
    {
        parent::preAction();
    }

    /**
     * Retrieves a list of bread crumbs for knowledgebase categories
     *
     * @param int $parent_category_id The ID of the category to fetch breadcrumbs for (optional, default null for all)
     * @return array A list of parent categories, including the given category
     */
    protected function getBreadCrumbs($parent_category_id = null)
    {
        if (!isset($this->SupportManagerKbCategories)) {
            $this->uses(['SupportManagerKbCategories']);
        }

        $categories = [];

        if ($parent_category_id) {
            $categories = $this->SupportManagerKbCategories->getAllParents($parent_category_id);
        }

        return $categories;
    }

    /**
     * Fetches the article content to be shown in the given language.
     * Defaults to the client/company language, or the first available article content
     * if the given language does not exist for the article
     *
     * @param stdClass $article An stdClass object representing the article
     * @param string $lang The language code in ISO 639-1 ISO 3166-1 alpha-2
     *  concatenated format (e.g. "en_us"; optional, defaults to the client/company's default language)
     * @return stdClass An stdClass object representing the article content
     */
    protected function getArticleContent($article, $lang = null)
    {
        if (!isset($this->SupportManagerKbArticles)) {
            $this->uses(['SupportManagerKbArticles']);
        }

        // Fetch the article content if it's not already set
        $all_content = (isset($article->content) ? $article->content : []);
        if (empty($article->content)) {
            $all_content = $this->SupportManagerKbArticles->getContent($article->id, $lang);
        }

        // Find the article content for the given language
        $content = [];
        if (!empty($all_content)) {
            // Set the language codes to look for
            $language_codes = $this->getDefaultLanguage();
            $language_codes = ($lang ? array_merge($language_codes, ['client' => $lang]) : $language_codes);

            // Find the content in the given language. But default to the company language,
            // or the first language available for content that exists if the preferred one is not available
            foreach ($all_content as $article_content) {
                if (array_key_exists('client', $language_codes)
                    && $article_content->lang == $language_codes['client']
                ) {
                    $content = $article_content;
                    break;
                } elseif (array_key_exists('company', $language_codes)
                    && $article_content->lang == $language_codes['company']
                ) {
                    $content = $article_content;
                } elseif (empty($content)) {
                    $content = $article_content;
                }
            }
        }

        return (empty($content) ? new stdClass() : $content);
    }

    /**
     * Fetches the article title for links in the URI
     *
     * @param stdClass $article An stdClass object representing the article
     * @return string The url encoded title of the article to show in the URI
     */
    protected function getArticleTitleUri($article)
    {
        if (!$article) {
            return '';
        }

        // Use the title set onto the article, if any
        $title = (property_exists($article, 'title') ? $article->title : '');

        // Fetch the title of the article to set
        if (empty($title)) {
            $content = $this->getArticleContent($article);

            if (property_exists($content, 'title')) {
                $title = $content->title;
            }
        }

        // Set the article URI title over a given number of characters, breaking at new words
        $num_chars = (int)Configure::get('SupportManager.max_chars_article_title_uri');
        $uri_title = strtolower($title);
        $length = strlen($uri_title);
        $end_pos = strpos($uri_title, ' ', ($length <= $num_chars ? $length : $num_chars));
        $end_pos = ($end_pos ? $end_pos : $length);

        // Set ASCII characters to remove
        $ascii = array_merge(range(0, 47), range(58, 64), range(91, 96), range(123, 127));
        $remove_chars = [];
        foreach ($ascii as $int) {
            $remove_chars[] = chr($int);
        }

        // Format the title by removing spaces, ASCII characters, and consecutive dashes
        $spaces = preg_replace("/\s/", ' ', substr($uri_title, 0, $end_pos));
        $dashes = str_replace($remove_chars, '-', $spaces);
        $formatted_title = preg_replace('/-+/', '-', $dashes);

        // Remove beginning and ending dashes, unless that is all that remains
        $temp_title = trim($formatted_title, '-');
        $formatted_title = (empty($temp_title) ? $formatted_title : $temp_title);

        return rawurlencode($formatted_title);
    }

    /**
     * Determines the client and company language codes available for the current user
     *
     * @return array An array of language codes, which may contain:
     *  - client The client's language code in ISO 639-1 ISO 3166-1 alpha-2 concatenated format (e.g. "en_us")
     *  - company The company's language code in ISO 639-1 ISO 3166-1 alpha-2 concatenated format (e.g. "en_us")
     */
    protected function getDefaultLanguage()
    {
        $this->uses(['Clients']);
        $this->components(['SettingsCollection']);

        $language_codes = [];

        // Set the company language
        $language = $this->SettingsCollection->fetchSetting(null, $this->company_id, 'language');
        if ($language && array_key_exists('value', $language)) {
            $language_codes = ['client' => $language['value'], 'company' => $language['value']];
        }

        // Set the client language
        if ($this->Session->read('blesta_client_id')
            && ($client = $this->Clients->get($this->Session->read('blesta_client_id')))
        ) {
            $language = $this->SettingsCollection->fetchClientSetting($client->id, null, 'language');
            if ($language && array_key_exists('value', $language)) {
                $language_codes['client'] = $language['value'];
            }
        } elseif (!$this->Session->read('blesta_client_id') && !empty(Configure::get('Blesta.language'))) {
            $language_codes['client'] = Configure::get('Blesta.language');
        }

        return $language_codes;
    }
}
