<?php
/**
 * Client Documents Client Main controller
 *
 * @package blesta
 * @subpackage blesta.plugins.client_documents
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientMain extends ClientDocumentsController
{
    /**
     * Setup
     */
    public function preAction()
    {
        parent::preAction();

        $this->requireLogin();

        $this->uses(['ClientDocuments.ClientDocumentsFiles']);

        // Restore structure view location of the client portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);

        $this->client_id = $this->Session->read('blesta_client_id');
    }

    /**
     * Show file listing
     */
    public function index()
    {
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_added');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('documents', $this->ClientDocumentsFiles->getAll($this->client_id, [$sort => $order]));

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(isset($this->get['whole_widget']) ? null : isset($this->get['sort']));
        }
    }

    /**
     * Downloads a file
     */
    public function download()
    {
        // Ensure a valid attachment was given
        if (!isset($this->get[0]) || !($document = $this->ClientDocumentsFiles->get($this->get[0])) ||
            $document->client_id != $this->client_id) {
            exit();
        }

        $this->components(['Download']);

        $name = $document->name;
        $ext = strstr($document->file_name, '.');

        if (strstr($name, '.') != $ext) {
            $name .= $ext;
        }

        $this->Download->downloadFile($document->file_name, $name);
        return false;
    }
}
