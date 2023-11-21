<?php
/**
 * Download Manager Client Main controller
 *
 * @package blesta
 * @subpackage blesta.plugins.download_manager
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientMain extends DownloadManagerController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        parent::preAction();

        $this->uses(['Clients']);

        // Fetch the client
        $this->client = $this->Clients->get($this->Session->read('blesta_client_id'));
        $this->client_id = (isset($this->client->id) ? $this->client->id : null);
        $this->company_id = (isset($this->client->company_id)
            ? $this->client->company_id
            : Configure::get('Blesta.company_id')
        );

        $this->uses([
            'DownloadManager.DownloadManagerCategories',
            'DownloadManager.DownloadManagerFiles',
            'DownloadManager.DownloadManagerUrls'
        ]);

        // Restore structure view location of the client portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);

        Language::loadLang('client_main', null, PLUGINDIR . 'download_manager' . DS . 'language' . DS);
    }

    /**
     * List categories/files
     */
    public function index()
    {
        // Get the current category
        $parent_category_id = (isset($this->get[0]) ? $this->get[0] : null);
        $category = null;
        if ($parent_category_id !== null) {
            $category = $this->DownloadManagerCategories->get($parent_category_id);
        }

        // Include the TextParser
        $this->helpers(['TextParser']);

        $this->set('categories', $this->DownloadManagerCategories->getAll($this->company_id, $parent_category_id));
        $this->set(
            'files',
            $this->DownloadManagerFiles->getAllAvailable($this->company_id, $this->client_id, $parent_category_id)
        );
        $this->set('total_files', $this->DownloadManagerFiles->getTotal($this->company_id, $parent_category_id));
        $this->set('category', $category);
        $this->set('parent_category', ($category ? $this->DownloadManagerCategories->get($category->parent_id) : null));
        $this->set('client_id', $this->client_id);

        if ($category) {
            $this->set('category_hierarchy', $this->DownloadManagerCategories->getAllParents($category->id));
        }
    }

    /**
     * Download a file
     */
    public function download()
    {
        // Ensure a file ID was provided
        if (!isset($this->get[0]) || !($file = $this->DownloadManagerFiles->get($this->get[0])) ||
            ($file->company_id != $this->company_id) ||
            !$this->DownloadManagerFiles->hasAccessToFile($file->id, $this->company_id, $this->client_id)) {
            $this->redirect($this->base_uri . 'plugin/download_manager/client_main/');
        }

        $this->components(['Download']);

        $this->uses(['DownloadManager.DownloadManagerLogs']);
        $log = [
            'client_id' => $this->client_id,
            'contact_id' => (isset($this->client->contact_id) ? $this->client->contact_id : null),
            'file_id' => $file->id
        ];
        $this->DownloadManagerLogs->add($log);

        // Set the file extension
        $extension = $this->DownloadManagerFiles->getFileExtension($file->file_name);

        $this->Download->downloadFile($file->file_name, $file->name . (!empty($extension) ? $extension : ''));

        return false;
    }

    /**
     * Download a file from a static url
     */
    public function static()
    {
        // Ensure an url was provided
        if (!isset($this->get[0]) || !($url = $this->DownloadManagerUrls->getByUrl($this->get[0])) ||
            ($url->company_id != $this->company_id)) {
            $this->redirect($this->base_uri . 'plugin/download_manager/client_main/');
        }

        // Get the file associated with this url
        if (!empty($url->file_id)) {
            $this->get[0] = $url->file_id;
        } elseif (!empty($url->category_id)) {
            $category_files = $this->DownloadManagerFiles->getAll($url->company_id, $url->category_id);
            $files_list = [];

            foreach ($category_files as $file) {
                $files_list[$file->id] = $file->modified_date;
            }
            asort($files_list);

            $latest_file = array_key_last($files_list);

            $this->get[0] = $latest_file ?? null;
        } else {
            $this->redirect($this->base_uri . '404/');
        }

        // Download the file
        $this->download();

        return false;
    }
}
