<?php
/**
 * Client Documents Files model
 *
 * @package blesta
 * @subpackage blesta.plugins.client_documents
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientDocumentsFiles extends ClientDocumentsModel
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        Language::loadLang('client_documents_files', null, PLUGINDIR . 'client_documents' . DS . 'language' . DS);
    }

    /**
     * Adds a new document
     *
     * @param array $vars A list of input vars including:
     *  - client_id
     *  - name
     *  - description
     * @param array $files A list of files in the format of post data $_FILES
     * @return int The ID of the document added, void on error
     */
    public function add(array $vars, array $files)
    {
        if (!isset($this->SettingsCollection)) {
            Loader::loadComponents($this, ['SettingsCollection', 'Upload']);
        }

        $vars['date_added'] = date('c');
        if (isset($vars['description']) && $vars['description'] == '') {
            unset($vars['description']);
        }

        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            // Handle file upload
            if (!empty($files['file'])) {
                // Set the uploads directory
                $temp = $this->SettingsCollection->fetchSetting(
                    null,
                    Configure::get('Blesta.company_id'),
                    'uploads_dir'
                );
                $upload_path = $temp['value'] . Configure::get('Blesta.company_id') . DS . 'client_documents' . DS;

                $this->Upload->setFiles($files);
                $this->Upload->setUploadPath($upload_path);
                $file_name = $this->makeFileName($files['file']['name']);

                if (!($errors = $this->Upload->errors())) {
                    // Will not overwrite existing file
                    $this->Upload->writeFile('file', false, $file_name);
                    $data = $this->Upload->getUploadData();

                    // Set the file name of the file that was uploaded
                    if (isset($data['file']['full_path'])) {
                        $vars['file_name'] = $data['file']['full_path'];
                    }

                    $errors = $this->Upload->errors();
                }

                // Error, could not upload the file
                if ($errors) {
                    $this->Input->setErrors($errors);
                    // Attempt to remove the file if it was somehow written
                    @unlink($upload_path . $file_name);
                    return;
                }
            }

            $fields = ['client_id', 'name', 'file_name', 'description', 'date_added'];
            $this->Record->insert('client_documents', $vars, $fields);
            return $this->Record->lastInsertId();
        }
    }

    /**
     * Deletes a document
     *
     * @param int $document_id The ID of the document to delete
     */
    public function delete($document_id)
    {
        $document = $this->get($document_id);

        if ($document) {
            // Remove from filesystem
            @unlink($document->file_name);
            // Remove from DB
            $this->Record->from('client_documents')->
                where('client_documents.id', '=', $document_id)->delete();
        }
    }

    /**
     * Retrieves a document
     *
     * @param int $document_id The ID of the document to fetch
     * @return mixed A stdClass object representing the document, false if no such document exists
     */
    public function get($document_id)
    {
        return $this->Record->select()->from('client_documents')->
            where('client_documents.id', '=', $document_id)->fetch();
    }

    /**
     * Retrieves all documents for a given client
     *
     * @param int $client_id The ID of the client whose documents to fetch
     * @param array $order_by A key/value pair where each key is a field and each value is the sort direction
     * @return array An array of stdClass objects, each representing a document
     */
    public function getAll($client_id, $order_by = ['date_added' => 'desc'])
    {
        return $this->Record->select()->from('client_documents')->
            where('client_documents.client_id', '=', $client_id)->
            order($order_by)->
            fetchAll();
    }

    /**
     * Return rules required for validating documents
     *
     * @param array $vars An array of input data
     * @param bool $edit Whether or not this is an edit
     * @return array An array of rules
     */
    private function getRules(array $vars, $edit = false)
    {
        $rules = [
            'client_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => Language::_('ClientDocumentsFiles.!error.client_id.exists', true)
                ]
            ],
            'name' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('ClientDocumentsFiles.!error.name.valid', true)
                ]
            ],
            'date_added' => [
                'valid' => [
                    'rule' => 'isDate',
                    'post_format' => [[$this, 'dateToUtc']],
                    'message' => Language::_('ClientDocumentsFiles.!error.date_added.valid', true)
                ]
            ]
        ];

        if ($edit) {
            // No need to validate client on edit; client can't be changed
            unset($rules['client_id']);
        }

        return $rules;
    }

    /**
     * Converts the given file name into an appropriate file name to store to disk
     *
     * @param string $file_name The name of the file to rename
     * @return string The rewritten file name in the format of YmdTHisO_[hash][ext]
     * (e.g. 20121009T154802+0000_1f3870be274f6c49b3e31a0c6728957f.txt)
     */
    public function makeFileName($file_name)
    {
        $ext = strrchr($file_name, '.');
        $file_name = md5($file_name . uniqid()) . $ext;

        return $this->dateToUtc(date('c'), "Ymd\THisO") . '_' . $file_name;
    }
}
