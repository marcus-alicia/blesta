<?php
/**
 * Download Manager Files
 *
 * Manages file downloads
 *
 * @package blesta
 * @subpackage blesta.plugins.download_manager.models
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

class DownloadManagerUrls extends DownloadManagerModel
{
    /**
     * Initialize
     */
    public function __construct()
    {
        parent::__construct();

        Language::loadLang('download_manager_urls', null, PLUGINDIR . 'download_manager' . DS . 'language' . DS);
    }

    /**
     * Adds a download url
     *
     * @param array $vars An array containing:
     *
     *  - url The slug name for the url
     *  - category_id The ID of the category where the download file belongs
     *  - file_id The ID of the file for the download url (optional)
     *  - company_id The ID of the company (optional)
     * @return int The ID of the download url
     */
    public function add(array $vars)
    {
        if (empty($vars['company_id'])) {
            $vars['company_id'] = Configure::get('Blesta.company_id');
        }

        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            if (!empty($vars['file_id'])) {
                $file = $this->DownloadManagerFiles->get($vars['file_id']);
                if (str_contains($vars['url'], '.') && !str_contains($vars['url'], $file->extension)) {
                    $this->Input->setErrors(
                        ['url' => ['match' => Language::_('DownloadManagerUrls.!error.url.match', true)]]
                    );
                    return;
                }
            }

            $fields = ['company_id', 'category_id', 'file_id', 'url'];
            $this->Record->insert('download_urls', $vars, $fields);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Fetches an existing download url
     *
     * @param int $url_id The ID of the download url to fetch
     * @return stdClass An object representing the download url
     */
    public function get(int $url_id)
    {
        return $this->Record->select([
            'download_urls.*', 'download_categories.name' => 'category', 'download_files.name' => 'file'
        ])
            ->from('download_urls')
            ->leftJoin('download_categories', 'download_categories.id', '=', 'download_urls.category_id', false)
            ->leftJoin('download_files', 'download_files.id', '=', 'download_urls.file_id', false)
            ->where('download_urls.id', '=', $url_id)
            ->fetch();
    }

    /**
     * Fetches an existing download url
     *
     * @param string $url The name of the download url to fetch
     * @return stdClass An object representing the download url
     */
    public function getByUrl(string $url)
    {
        return $this->Record->select([
            'download_urls.*', 'download_categories.name' => 'category', 'download_files.name' => 'file'
        ])
            ->from('download_urls')
            ->leftJoin('download_categories', 'download_categories.id', '=', 'download_urls.category_id', false)
            ->leftJoin('download_files', 'download_files.id', '=', 'download_urls.file_id', false)
            ->where('download_urls.url', '=', $url)
            ->fetch();
    }

    /**
     * Fetch all download urls for a given company
     *
     * @param int $company_id The ID of the company to which obtain the download urls
     * @return array An array containing all the download urls
     */
    public function getAll(int $company_id) : array
    {
        return $this->Record->select([
            'download_urls.*', 'download_categories.name' => 'category', 'download_files.name' => 'file'
        ])
            ->from('download_urls')
            ->leftJoin('download_categories', 'download_categories.id', '=', 'download_urls.category_id', false)
            ->leftJoin('download_files', 'download_files.id', '=', 'download_urls.file_id', false)
            ->where('download_urls.company_id', '=', $company_id)
            ->fetchAll();
    }

    /**
     * Fetches a paginated list of download urls
     *
     * @param int $company_id The ID of the company to which obtain the download urls
     * @param int $page The page to return results for (optional, default 1)
     * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @return array An array of stdClass objects representing all download urls
     */
    public function getList(int $company_id, int $page = 1, array $order_by = ['id' => 'DESC']) : array
    {
        $urls = $this->Record->select([
            'download_urls.*', 'download_categories.name' => 'category', 'download_files.name' => 'file'
        ])
            ->from('download_urls')
            ->leftJoin('download_categories', 'download_categories.id', '=', 'download_urls.category_id', false)
            ->leftJoin('download_files', 'download_files.id', '=', 'download_urls.file_id', false)
            ->where('download_urls.company_id', '=', $company_id)
            ->order($order_by)
            ->limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())
            ->fetchAll();

        foreach ($urls as &$url) {
            $url->full_path = !empty($url->file_id)
                ? $this->getFileRoutes($company_id)[$url->file_id]
                : $this->getCategoryRoutes($company_id)[$url->category_id];
        }

        return $urls;
    }

    /**
     * Return the total number of download urls returned from DownloadManagerUrls::getList(),
     * useful in constructing pagination for the getList() method.
     *
     * @param int $company_id The ID of the company to which obtain the download urls
     * @return int The total number of download urls
     * @see DownloadManagerUrls::getList()
     */
    public function getListCount(int $company_id) : int
    {
        // Return the number of results
        return $this->Record->select()
            ->from('download_urls')
            ->where('download_urls.company_id', '=', $company_id)
            ->numResults();
    }

    /**
     * Updates an existing download url
     *
     * @param int $url_id The ID of the url to update
     * @param array $vars An array containing:
     *
     *  - url The slug name for the url
     *  - category_id The ID of the category where the download file belongs
     *  - file_id The ID of the file for the download URL (optional)
     * @return int The ID of the download url
     */
    public function update(int $url_id, array $vars)
    {
        $this->Input->setRules($this->getRules(array_merge($vars, ['id' => $url_id]), true));

        if ($this->Input->validates($vars)) {
            if (!empty($vars['file_id'])) {
                $file = $this->DownloadManagerFiles->get($vars['file_id']);
                if (str_contains($vars['url'], '.') && !str_contains($vars['url'], $file->extension)) {
                    $this->Input->setErrors(
                        ['url' => ['match' => Language::_('DownloadManagerUrls.!error.url.match', true)]]
                    );
                    return;
                }
            }

            $fields = ['file_id', 'category_id', 'url'];
            $this->Record->where('id', '=', $url_id)->update('download_urls', $vars, $fields);

            return $url_id;
        }
    }

    /**
     * Deletes an existing download url
     *
     * @param int $url_id The ID of the url to delete
     * @return bool True if the url was successfully removed
     */
    public function delete(int $url_id) : bool
    {
        $url = $this->get($url_id);

        if ($url) {
            // Begin a transaction
            $this->Record->begin();

            // Delete the file
            $this->Record->from('download_urls')->where('id', '=', $url_id)->delete();

            // Commit the changes
            $this->Record->commit();

            return true;
        }

        return false;
    }

    /**
     * Returns a list of all files available for stable urls
     *
     * @param int $company_id The ID of the company from which to fetch files
     * @return array A list of all files
     */
    public function getFileRoutes(int $company_id)
    {
        Loader::loadModels($this, ['DownloadManager.DownloadManagerFiles']);

        $files = [];

        $uncategorized_files = $this->DownloadManagerFiles->getAll($company_id);
        if (!empty($uncategorized_files)) {
            foreach ($uncategorized_files as $uncategorized_file) {
                $files[$uncategorized_file->id] = '/' . $uncategorized_file->name . $uncategorized_file->extension;
            }
        }

        $categories = $this->getCategoryRoutes($company_id);
        foreach ($categories as $category_id => $category_name) {
            $category_files = $this->DownloadManagerFiles->getAll($company_id, $category_id);
            if (!empty($category_files)) {
                foreach ($category_files as $file) {
                    $files[$file->id] = '/' . $category_name . '/' . $file->name . $file->extension;
                }
            }
        }

        return $files;
    }

    /**
     * Returns a list of all category routes available for stable urls
     *
     * @param int $company_id The ID of the company from which to fetch categories
     * @param int $parent_id The parent category ID whose categories to fetch
     * @param string $parent_route The full category route of the parent category
     * @return array A list containing all the categories routes
     */
    public function getCategoryRoutes(int $company_id, int $parent_id = null, string $parent_route = '')
    {
        Loader::loadModels($this, ['DownloadManager.DownloadManagerCategories']);

        $routes = [];
        $categories = $this->DownloadManagerCategories->getAll($company_id, $parent_id);

        foreach ($categories as $category) {
            $routes[$category->id] = (!empty($category->parent_id) ? $parent_route . '/' : '') . $category->name;

            if (($childs = $this->getCategoryRoutes($company_id, $category->id, $routes[$category->id]))) {
                $routes = (array) $routes + (array) $childs;
            }
        }

        return $routes;
    }

    /**
     * Retrieves a list of rules to validate add/editing urls
     *
     * @param array $vars A list of input vars to validate
     * @param bool $edit True to fetch the edit rules, false to fetch the add rules (optional, default false)
     * @return array A list of rules
     */
    private function getRules(array $vars, $edit = false)
    {
        $rules = [
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('DownloadManagerUrls.!error.company_id.exists')
                ]
            ],
            'url' => [
                'exists' => [
                    'if_set' => $edit,
                    'rule' => function ($url) use ($vars, $edit) {
                        $parent = new stdClass();
                        Loader::loadComponents($parent, ['Record']);

                        $query = $this->Record->select()
                            ->from('download_urls')
                            ->where('url', '=', $url)
                            ->where('company_id', '=', $vars['company_id'] ?? Configure::get('Blesta.company_id'));

                        if ($edit && isset($vars['id'])) {
                            $query->where('id', '!=', $vars['id']);
                        }

                        $count = $query->numResults();

                        return !($count > 0);
                    },
                    'message' => Language::_('DownloadManagerUrls.!error.url.exists', true)
                ],
                'format' => [
                    'if_set' => $edit,
                    'rule' => function ($url) {
                        return preg_match('/^[a-z0-9]+(-?[a-z0-9]+)*(\.[a-z]+)?$/i', $url);
                    },
                    'message' => Language::_('DownloadManagerUrls.!error.url.format', true)
                ]
            ],
            'category_id' => [
                'exists' => [
                    'if_set' => empty($vars['file_id']) ? $edit : true,
                    'rule' => [[$this, 'validateExists'], 'id', 'download_categories'],
                    'message' => $this->_('DownloadManagerUrls.!error.category_id.exists')
                ]
            ],
            'file_id' => [
                'exists' => [
                    'if_set' => empty($vars['category_id']) ? $edit : true,
                    'rule' => [[$this, 'validateExists'], 'id', 'download_files'],
                    'message' => $this->_('DownloadManagerUrls.!error.file_id.exists')
                ]
            ]
        ];

        if ($edit) {
            // Update rules
            unset($rules['company_id']);
        }

        return $rules;
    }
}