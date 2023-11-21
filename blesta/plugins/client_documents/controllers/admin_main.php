<?php
/**
 * Client Documents Admin Main controller
 *
 * @package blesta
 * @subpackage blesta.plugins.client_documents
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminMain extends ClientDocumentsController
{
    /**
     * Setup
     */
    public function preAction()
    {
        parent::preAction();

        $this->requireLogin();

        $this->uses(['Clients', 'ClientDocuments.ClientDocumentsFiles']);

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);

        $this->staff_id = $this->Session->read('blesta_staff_id');
    }

    /**
     * Show file listing
     */
    public function index()
    {
        // Get client or redirect if not given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_added');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        $this->set('client', $client);
        $this->set('documents', $this->ClientDocumentsFiles->getAll($client->id, [$sort => $order]));
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        return $this->renderAjaxWidgetIfAsync();
    }

    /**
     * Creates a new document
     */
    public function add()
    {
        // Get client or redirect if not given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $vars = [];

        if (!empty($this->post)) {
            $this->post['client_id'] = $client->id;
            $this->ClientDocumentsFiles->add($this->post, $this->files);

            if (($errors = $this->ClientDocumentsFiles->errors())) {
                // Error, reset vars
                $this->setMessage('error', $errors, false, null, false);
                $vars = (object)$this->post;
            } else {
                // Success
                $this->flashMessage('message', Language::_('AdminMain.!success.document_uploaded', true), null, false);
                $this->redirect($this->base_uri . 'plugin/client_documents/admin_main/index/' . $client->id);
            }
        }

        $this->set('vars', (object)$vars);
    }

    /**
     * Deletes a document
     */
    public function delete()
    {
        // Get document and client or redirect if not given
        if (!isset($this->post['id'])
            || !($document = $this->ClientDocumentsFiles->get($this->post['id']))
            || !($client = $this->Clients->get($document->client_id))
        ) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $this->ClientDocumentsFiles->delete($document->id);

        if (($errors = $this->ClientDocumentsFiles->errors())) {
            $this->flashMessage('error', $errors, null, false);
        } else {
            $this->flashMessage('message', Language::_('AdminMain.!success.document_deleted', true), null, false);
        }
        $this->redirect($this->base_uri . 'plugin/client_documents/admin_main/index/' . $client->id);
    }

    /**
     * Downloads a file
     */
    public function download()
    {
        // Ensure a valid attachment was given
        if (!isset($this->get[0]) || !($document = $this->ClientDocumentsFiles->get($this->get[0]))) {
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
