<?php
/**
 * Download Manager main controller
 *
 * @package blesta
 * @subpackage blesta.plugins.download_manager
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminMain extends DownloadManagerController
{
    /**
     * Setup
     */
    public function preAction()
    {
        parent::preAction();

        // Load required models
        $this->uses([
            'DownloadManager.DownloadManagerCategories',
            'DownloadManager.DownloadManagerFiles',
            'DownloadManager.DownloadManagerUrls'
        ]);

        // Set the Data Structure Array
        $this->helpers(['DataStructure']);
        $this->ArrayHelper = $this->DataStructure->create('Array');
    }

    /**
     * Redirects to the 'Files' view
     */
    public function index()
    {
        $this->redirect($this->base_uri . 'plugin/download_manager/admin_main/files/');
    }

    /**
     * Returns the view to be rendered when managing this plugin
     */
    public function files()
    {
        // Get the current category
        $parent_category_id = ($this->get[0] ?? null);
        $category = null;
        if ($parent_category_id !== null) {
            $category = $this->DownloadManagerCategories->get($parent_category_id);
        }

        $vars = [
            'categories' => $this->DownloadManagerCategories->getAll($this->company_id, $parent_category_id),
            'files' => $this->DownloadManagerFiles->getAll($this->company_id, $parent_category_id),
            'category' => $category, // current category
            'parent_category' => ($category ? $this->DownloadManagerCategories->get($category->parent_id) : null)
        ];

        // Set variables to the view
        foreach ($vars as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Shows a list of all static URLs
     */
    public function urls()
    {
        $page = (isset($this->get[0]) ? (int)$this->get[0] : 1);
        $sort = ($this->get['sort'] ?? 'url');
        $order = ($this->get['order'] ?? 'desc');

        $total_results = $this->DownloadManagerUrls->getListCount($this->company_id);
        $vars = [
            'urls' => $this->DownloadManagerUrls->getList($this->company_id, $page, [$sort => $order]),
            'sort' => $sort,
            'order' => $order,
            'negate_order' => ($order == 'asc' ? 'desc' : 'asc'),
            'page' => $page,
            'base_url' => $this->base_url,
            'public_uri' => $this->public_uri
        ];

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'plugin/download_manager/admin_main/urls/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        // Set variables to the view
        foreach ($vars as $key => $value) {
            $this->set($key, $value);
        }

        // Render the request if ajax
        return $this->renderAjaxWidgetIfAsync(isset($this->get['sort']));
    }

    /**
     * Add a download
     */
    public function add()
    {
        $this->uses(['ClientGroups']);

        // Set category if given, otherwise default to the root category
        $category = (isset($this->get[0]) ? $this->DownloadManagerCategories->get($this->get[0]) : null);

        // Ensure the parent category is in the same company too
        if ($category && $category->company_id != $this->company_id) {
            $this->redirect($this->base_uri . 'plugin/download_manager/admin_main/files/');
        }

        // Get all client groups and packages for selection
        $client_groups = $this->ArrayHelper->numericToKey($this->ClientGroups->getAll($this->company_id), 'id', 'name');

        // Set vars
        $vars = [
            'client_groups' => $client_groups,
            'packages' => $this->getAvailablePackages(),
            'category' => $category // current category
        ];
        unset($client_groups);

        if (!empty($this->post)) {
            // Set the category this file is to be added in
            $data = [
                'category_id' => ($category->id ?? null),
                'company_id' => $this->company_id
            ];

            // Set vars according to selected items
            if (isset($this->post['type']) && $this->post['type'] == 'public') {
                $data['public'] = '1';
            } else {
                // Set availability to groups/packages
                if (isset($this->post['available_to_client_groups'])
                    && $this->post['available_to_client_groups'] == '1') {
                    $data['permit_client_groups'] = '1';
                }
                if (isset($this->post['available_to_packages']) && $this->post['available_to_packages'] == '1') {
                    $data['permit_packages'] = '1';
                }
            }

            // Set any client groups/packages
            if (isset($data['permit_client_groups'])) {
                $data['file_groups'] = isset($this->post['file_groups']) ? (array)$this->post['file_groups'] : [];
            }
            if (isset($data['permit_packages'])) {
                $data['file_packages'] = isset($this->post['file_packages']) ? (array)$this->post['file_packages'] : [];
            }

            // Remove file name if path not selected
            // This indicates that the file is expected to be uploaded by post
            if (isset($this->post['file_type']) && $this->post['file_type'] == 'upload') {
                unset($this->post['file_name']);
            }

            $data = array_merge($this->post, $data);

            // Add the download
            $this->DownloadManagerFiles->add($data, $this->files);

            if (($errors = $this->DownloadManagerFiles->errors())) {
                // Error, reset vars
                $vars['vars'] = (object)$this->post;
                $this->setMessage('error', $errors, false, null, false);
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminMain.!success.file_added', true),
                    null,
                    false
                );
                $this->redirect($this->base_uri . 'plugin/download_manager/admin_main/files/' . ($category->id ?? ''));
            }
        }

        // Set all selected client groups in assigned and unset all selected client groups from available
        if (isset($vars['vars']->file_groups) && is_array($vars['vars']->file_groups)) {
            $selected = [];

            foreach ($vars['client_groups'] as $id => $name) {
                if (in_array($id, $vars['vars']->file_groups)) {
                    $selected[$id] = $name;
                    unset($vars['client_groups'][$id]);
                }
            }

            $vars['vars']->file_groups = $selected;
        }

        // Set all selected packages in assigned and unset all selected packages from available
        if (isset($vars['vars']->file_packages) && is_array($vars['vars']->file_packages)) {
            $selected = [];

            foreach ($vars['packages'] as $id => $name) {
                if (in_array($id, $vars['vars']->file_packages)) {
                    $selected[$id] = $name;
                    unset($vars['packages'][$id]);
                }
            }

            $vars['vars']->file_packages = $selected;
        }

        // Set variables to the view
        foreach ($vars as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Add a static URL
     */
    public function addUrl()
    {
        // Set vars
        $vars = [
            'files' => $this->DownloadManagerUrls->getFileRoutes($this->company_id),
            'categories' => $this->DownloadManagerUrls->getCategoryRoutes($this->company_id)
        ];

        // Add new url
        if (!empty($this->post)) {
            // Set the URL data
            $data = [
                'company_id' => $this->company_id
            ];

            if ($this->post['url_type'] == 'file') {
                $data['file_id'] = $this->post['file'];

                if (($file = $this->DownloadManagerFiles->get($data['file_id']))) {
                    $data['category_id'] = $file->category_id ?? null;
                }
            }

            if ($this->post['url_type'] == 'category') {
                $data['file_id'] = null;
                $data['category_id'] = $this->post['category'];
            }

            $data = array_merge($this->post, $data);

            $this->DownloadManagerUrls->add($data);

            if (($errors = $this->DownloadManagerUrls->errors())) {
                // Error, reset vars
                $vars['vars'] = (object)$this->post;
                $this->setMessage('error', $errors, false, null, false);
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminMain.!success.url_added', true),
                    null,
                    false
                );
                $this->redirect($this->base_uri . 'plugin/download_manager/admin_main/urls/');
            }
        }

        // Set variables to the view
        foreach ($vars as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Edit a download
     */
    public function edit()
    {
        // Ensure a file was given
        if (!isset($this->get[0]) || !($file = $this->DownloadManagerFiles->get($this->get[0])) ||
            ($file->company_id != $this->company_id)) {
            $this->redirect($this->base_uri . 'plugin/download_manager/admin_main/files/');
        }

        $this->uses(['ClientGroups']);

        // Get all client groups and packages for selection
        $client_groups = $this->ArrayHelper->numericToKey($this->ClientGroups->getAll($this->company_id), 'id', 'name');

        // Set vars
        $vars = [
            'client_groups' => $client_groups,
            'packages' => $this->getAvailablePackages(),
            'category' => ($file->category_id ? $this->DownloadManagerCategories->get($file->category_id) : null)
        ];
        unset($client_groups);

        if (!empty($this->post)) {
            // Set the category this file belongs to
            $data = [
                'category_id' => $file->category_id,
                'company_id' => $this->company_id
            ];

            // Set vars according to selected items
            if (isset($this->post['type']) && $this->post['type'] == 'public') {
                $data['public'] = '1';
                $data['permit_client_groups'] = '0';
                $data['permit_packages'] = '0';
            } else {
                $data['public'] = '0';

                // Set availability to groups/packages
                if (isset($this->post['available_to_client_groups'])
                    && $this->post['available_to_client_groups'] == '1') {
                    $data['permit_client_groups'] = '1';
                }
                if (isset($this->post['available_to_packages']) && $this->post['available_to_packages'] == '1') {
                    $data['permit_packages'] = '1';
                }
            }

            // Set any client groups/packages
            if (isset($data['permit_client_groups'])) {
                $data['file_groups'] = isset($this->post['file_groups']) ? (array)$this->post['file_groups'] : [];
            }
            if (isset($data['permit_packages'])) {
                $data['file_packages'] = isset($this->post['file_packages']) ? (array)$this->post['file_packages'] : [];
            }

            // Remove file name if path not selected
            // This indicates that the file is expected to be uploaded by post
            if (isset($this->post['file_type']) && $this->post['file_type'] == 'upload') {
                unset($this->post['file_name']);
            }

            $data = array_merge($this->post, $data);

            // Update the download
            $this->DownloadManagerFiles->edit($file->id, $data, $this->files);

            if (($errors = $this->DownloadManagerFiles->errors())) {
                // Error, reset vars
                $vars['vars'] = (object)$this->post;

                // Set the original path to the file if it was removed
                if (empty($this->post['file_name'])) {
                    $vars['vars']->file_name = $file->file_name;
                }

                $this->setMessage('error', $errors, false, null, false);
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminMain.!success.file_updated', true),
                    null,
                    false
                );
                $this->redirect(
                    $this->base_uri . 'plugin/download_manager/admin_main/files/' . $file->category_id
                );
            }
        }

        // Set initial packages/client groups
        if (empty($vars['vars'])) {
            $vars['vars'] = $file;
            $vars['vars']->file_groups = $this->ArrayHelper->numericToKey(
                $file->client_groups,
                'client_group_id',
                'client_group_id'
            );
            $vars['vars']->file_packages = $this->ArrayHelper->numericToKey(
                $file->packages,
                'package_id',
                'package_id'
            );

            // Default to 'path' since a file has already been uploaded
            $vars['vars']->file_type = 'path';

            // Set default radio/checkboxes
            if ($file->permit_client_groups == '1' || $file->permit_packages == '1') {
                $vars['vars']->type = 'logged_in';
                $vars['vars']->available_to_client_groups = ($file->permit_client_groups == '1'
                    ? $file->permit_client_groups
                    : '0'
                );
                $vars['vars']->available_to_packages = ($file->permit_packages == '1'
                    ? $file->permit_packages
                    : '0'
                );
            }
        }

        // Set all selected client groups in assigned and unset all selected client groups from available
        if (isset($vars['vars']->file_groups) && is_array($vars['vars']->file_groups)) {
            $selected = [];

            foreach ($vars['client_groups'] as $id => $name) {
                if (in_array($id, $vars['vars']->file_groups)) {
                    $selected[$id] = $name;
                    unset($vars['client_groups'][$id]);
                }
            }

            $vars['vars']->file_groups = $selected;
        }

        // Set all selected packages in assigned and unset all selected packages from available
        if (isset($vars['vars']->file_packages) && is_array($vars['vars']->file_packages)) {
            $selected = [];

            foreach ($vars['packages'] as $id => $name) {
                if (in_array($id, $vars['vars']->file_packages)) {
                    $selected[$id] = $name;
                    unset($vars['packages'][$id]);
                }
            }

            $vars['vars']->file_packages = $selected;
        }

        // Set variables to the view
        foreach ($vars as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Edit a URL
     */
    public function editUrl()
    {
        // Ensure a url was given
        if (!isset($this->get[0]) || !($url = $this->DownloadManagerUrls->get($this->get[0])) ||
            ($url->company_id != $this->company_id)) {
            $this->redirect($this->base_uri . 'plugin/download_manager/admin_main/urls/');
        }

        // Set vars
        $vars = [
            'files' => $this->DownloadManagerUrls->getFileRoutes($this->company_id),
            'categories' => $this->DownloadManagerUrls->getCategoryRoutes($this->company_id)
        ];

        // Edit url
        if (!empty($this->post)) {
            // Set the URL data
            $data = [];
            if ($this->post['url_type'] == 'file') {
                $data['file_id'] = $this->post['file'];

                if (($file = $this->DownloadManagerFiles->get($data['file_id']))) {
                    $data['category_id'] = $file->category_id ?? null;
                }
            }

            if ($this->post['url_type'] == 'category') {
                $data['file_id'] = null;
                $data['category_id'] = $this->post['category'];
            }

            $data = array_merge($this->post, $data);

            $this->DownloadManagerUrls->update($url->id, $data);

            if (($errors = $this->DownloadManagerUrls->errors())) {
                // Error, reset vars
                $vars['vars'] = (object)$this->post;
                $this->setMessage('error', $errors, false, null, false);
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminMain.!success.url_updated', true),
                    null,
                    false
                );
                $this->redirect($this->base_uri . 'plugin/download_manager/admin_main/urls/');
            }
        }

        // Set url variables
        if (empty($vars['vars'])) {
            $vars['vars'] = $url;

            // Set url type
            $vars['vars']->url_type = !empty($url->file_id) ? 'file' : 'category';

            // Set file and category id
            if (!empty($url->file_id)) {
                $vars['vars']->file = $url->file_id;
                $vars['vars']->category = null;
            } else {
                $vars['vars']->file = null;
                $vars['vars']->category = $url->category_id;
            }
        }

        // Set variables to the view
        foreach ($vars as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Deletes a file
     */
    public function delete()
    {
        // Ensure the file ID was provided
        if (!isset($this->post['id']) || !($file = $this->DownloadManagerFiles->get($this->post['id'])) ||
            ($file->company_id != $this->company_id)) {
            $this->redirect($this->base_uri . 'plugin/download_manager/admin_main/files/');
        }

        // Get the current category
        $category_id = null;

        if ($file->category_id !== null) {
            $category = $this->DownloadManagerCategories->get($file->category_id);
            $category_id = $category->id;
        }

        // Delete the file
        $this->DownloadManagerFiles->delete($file->id);

        $this->flashMessage(
            'message',
            Language::_('AdminMain.!success.file_deleted', true),
            null,
            false
        );
        $this->redirect($this->base_uri . 'plugin/download_manager/admin_main/files/' . $category_id);
    }

    /**
     * Delete a URL
     */
    public function deleteUrl()
    {
        // Ensure the url ID was provided
        if (!isset($this->post['id']) || !($url = $this->DownloadManagerUrls->get($this->post['id'])) ||
            ($url->company_id != $this->company_id)) {
            $this->redirect($this->base_uri . 'plugin/download_manager/admin_main/urls/');
        }

        // Delete the file
        $this->DownloadManagerUrls->delete($url->id);

        $this->flashMessage(
            'message',
            Language::_('AdminMain.!success.url_deleted', true),
            null,
            false
        );
        $this->redirect($this->base_uri . 'plugin/download_manager/admin_main/urls/');
    }

    /**
     * Downloads a file
     */
    public function download()
    {
        // Ensure a file ID was provided
        if (!isset($this->get[0]) || !($file = $this->DownloadManagerFiles->get($this->get[0])) ||
            ($file->company_id != $this->company_id)) {
            $this->redirect($this->base_uri . 'plugin/download_manager/admin_main/files/');
        }

        $this->components(['Download']);

        // Set the file extension
        $extension = $this->DownloadManagerFiles->getFileExtension($file->file_name);

        $this->Download->downloadFile($file->file_name, $file->name . (!empty($extension) ? $extension : ''));
        die;
    }

    /**
     * Add a category
     */
    public function addCategory()
    {
        // Set category if given, otherwise default to the root category
        $current_category = (isset($this->get[0]) ? $this->DownloadManagerCategories->get($this->get[0]) : null);

        // Ensure the parent category is in the same company too
        if ($current_category && $current_category->company_id != $this->company_id) {
            $this->redirect($this->base_uri . 'plugin/download_manager/admin_main/files/');
        }

        $vars = [
            'category' => $current_category,
            'vars' => (object)['parent_id' => ($current_category->id ?? null)]
        ];

        if (!empty($this->post)) {
            // Create the category
            $data = array_merge($this->post, (array)$vars['vars']);
            $data['company_id'] = $this->company_id;
            $category = $this->DownloadManagerCategories->add($data);

            if (($errors = $this->DownloadManagerCategories->errors())) {
                // Error, reset vars
                $vars['vars'] = (object)$this->post;
                $this->setMessage('error', $errors, false, null, false);
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminMain.!success.category_added'),
                    null,
                    false
                );
                $this->redirect(
                    $this->base_uri . 'plugin/download_manager/admin_main/files/' . ($current_category->id ?? null)
                );
            }
        }

        // Set variables to the view
        foreach ($vars as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Edit a category
     */
    public function editCategory()
    {
        if (!isset($this->get[0]) || !($category = $this->DownloadManagerCategories->get($this->get[0])) ||
            ($category->company_id != $this->company_id)) {
            $this->redirect($this->base_uri . 'plugin/download_manager/admin_main/files/');
        }

        $vars = [
            'category' => $category
        ];

        if (!empty($this->post)) {
            // Update the category
            $data = $this->post;
            $data['company_id'] = $this->company_id;
            $category = $this->DownloadManagerCategories->edit($category->id, $data);

            if (($errors = $this->DownloadManagerCategories->errors())) {
                // Error, reset vars
                $vars['vars'] = (object)$this->post;
                $this->setMessage('error', $errors, false, null, false);
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminMain.!success.category_updated', true),
                    null,
                    false
                );
                $this->redirect(
                    $this->base_uri . 'plugin/download_manager/admin_main/files/' . ($category->parent_id ?? null)
                );
            }
        }

        // Set initial vars
        if (empty($vars['vars'])) {
            $vars['vars'] = $category;
        }

        // Set variables to the view
        foreach ($vars as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Deletes a category
     */
    public function deleteCategory()
    {
        // Ensure the category ID was provided
        if (!isset($this->post['id']) || !($category = $this->DownloadManagerCategories->get($this->post['id'])) ||
            ($category->company_id != $this->company_id)) {
            $this->redirect($this->base_uri . 'plugin/download_manager/admin_main/files/');
        }

        // Delete the file
        $this->DownloadManagerCategories->delete($category->id);

        $this->flashMessage(
            'message',
            Language::_('AdminMain.!success.category_deleted', true),
            null,
            false
        );
        $this->redirect(
            $this->base_uri . 'plugin/download_manager/admin_main/files/' . $category->parent_id
        );
    }

    /**
     * Retrieves a list of all available package names keyed by package ID
     *
     * @return array A key/value array of package names keyed by package ID
     */
    private function getAvailablePackages()
    {
        $this->uses(['Packages']);

        $packages = [];
        $statuses = $this->Packages->getStatusTypes();
        $all_packages = $this->Packages->getAll($this->company_id, ['name' => 'ASC']);

        foreach ($all_packages as $package) {
            $status = $statuses[$package->status] ?? $package->status;
            $packages[$package->id] = Language::_(
                'AdminMain.package_name',
                true,
                $package->name,
                $status
            );
        }

        return $packages;
    }
}
