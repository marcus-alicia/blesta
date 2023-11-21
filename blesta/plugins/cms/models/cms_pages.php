<?php
/**
 * CMS Pages
 *
 * Manages CMS pages
 *
 * @package blesta
 * @subpackage blesta.plugins.cms.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

class CmsPages extends CmsModel
{
    /**
     * @var string A parse validation error
     */
    private $parse_error = '';

    /**
     * Initialize
     */
    public function __construct()
    {
        parent::__construct();

        Language::loadLang('cms_pages', null, PLUGINDIR . 'cms' . DS . 'language' . DS);
    }

    /**
     * Adds a new CMS page or updates an existing one
     *
     * @param array $vars A list of input vars for creating a CMS page, including:
     *  - uri The URI of the page
     *  - company_id The ID of the company this page belongs to
     *  - lang The language of the page
     *  - title The page title
     *  - content The page content
     */
    public function add(array $vars)
    {
        // Set rules
        $this->Input->setRules($this->getRules($vars));

        // Add a new CMS page
        if ($this->Input->validates($vars)) {
            $fields = ['uri', 'company_id', 'lang', 'title', 'content'];
            $this->Record->duplicate('title', '=', $vars['title'])->
                duplicate('content', '=', $vars['content'])->
                insert('cms_pages', $vars, $fields);
        }

        // Override the parse error with the actual error on failure
        $this->setParseError();
    }

    /**
     * Fetches a page at the given URI for the given company
     *
     * @param string $uri The URI of the page
     * @param int $company_id The ID of the company the page belongs to
     * @param string $lang The language of the page
     * @return mixed An stdClass object representing the CMS page, or false if none exist
     */
    public function get($uri, $company_id, $lang = null)
    {
        if (is_null($lang)) {
            $lang = Configure::get('Blesta.language');
        }

        $page = $this->Record->select()->from('cms_pages')->
            where('uri', '=', $uri)->
            where('company_id', '=', $company_id)->
            where('lang', '=', $lang)->
            fetch();

        if (!$page) {
            $page = $this->Record->select()->from('cms_pages')->
                where('uri', '=', $uri)->
                where('company_id', '=', $company_id)->
                where('lang', '=', 'en_us')->
                fetch();
        }

        return $page;
    }

    /**
     * Returns a list of all pages of a given company
     *
     * @param int $company_id The ID of the company the pages belong to
     * @param array $filters A list of filters for the query
     * @return array A list of pages for the given company
     */
    public function getAll(int $company_id, array $filters = []) : array
    {
        $pages = $this->Record->select()->from('cms_pages')->
            where('company_id', '=', $company_id);

        if (isset($filters['lang'])) {
            $pages->where('lang', '=', $filters['lang']);
        }

        if (isset($filters['uri'])) {
            $pages->where('uri', '=', $filters['uri']);
        }

        return $pages->fetchAll();
    }

    /**
     * Retrieves a list of input rules for adding a CMS page
     *
     * @param array $vars A list of input vars
     * @return array A list of input rules
     */
    private function getRules(array $vars)
    {
        $rules = [
            'uri' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('CmsPages.!error.uri.empty')
                ]
            ],
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('CmsPages.!error.company_id.exists')
                ]
            ],
            'title' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('CmsPages.!error.title.empty')
                ]
            ],
            'content' => [
                'valid' => [
                    'rule' => function ($content) {
                        $parser_options_text = Configure::get('Blesta.parser_options');

                        try {
                            H2o::parseString($content, $parser_options_text)->render();
                        } catch (H2o_Error $e) {
                            $this->parse_error = $e->getMessage();
                            return false;
                        } catch (Exception $e) {
                            // Don't care about any other exception
                        }

                        return true;
                    },
                    'message' => $this->_('CmsPages.!error.content.valid', ''),
                    'final' => true
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Sets the parse error in the set of errors
     */
    private function setParseError()
    {
        // Ensure we have input errors, otherwise there is nothing to overwrite
        if (($errors = $this->Input->errors()) === false) {
            return;
        }

        if (isset($errors['content']['valid'])) {
            $errors['content']['valid'] = $this->_('CmsPages.!error.content.valid', $this->parse_error);
        }
        $this->Input->setErrors($errors);
    }
}
