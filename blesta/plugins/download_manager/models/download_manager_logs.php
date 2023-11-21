<?php
/**
 * Download Manager Logs
 *
 * Logs downloads
 *
 * @package blesta
 * @subpackage blesta.plugins.download_manager.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

class DownloadManagerLogs extends DownloadManagerModel
{
    /**
     * Initialize
     */
    public function __construct()
    {
        parent::__construct();

        Language::loadLang('download_manager_logs', null, PLUGINDIR . 'download_manager' . DS . 'language' . DS);
    }

    /**
     * Logs that a file download has occurred
     *
     * @param array A list of input vars, including:
     *  - file_id The ID of the file downloaded
     *  - client_id The ID of the client that downloaded the file
     *  - contact_id The ID of the contact that downloaded the file
     */
    public function add(array $vars)
    {
        $this->Input->setRules([
            'file_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'download_files'],
                    'message' => $this->_('DownloadManagerLogs.!error.file_id.exists')
                ]
            ],
            'client_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => $this->_('DownloadManagerLogs.!error.client_id.exists')
                ]
            ],
            'contact_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'contacts'],
                    'message' => $this->_('DownloadManagerLogs.!error.contact_id.exists')
                ]
            ]
        ]);

        $vars['date_added'] = $this->dateToUtc(date('c'));

        if ($this->Input->validates($vars)) {
            $fields = ['file_id', 'client_id', 'contact_id', 'date_added'];
            $this->Record->insert('download_logs', $vars, $fields);
        }
    }

    /**
     * Fetches a download log by ID
     *
     * @param int $log_id The ID of the log entry to fetch
     * @return mixed An stdClass object representing the download log, or false if it does not exist
     */
    public function get($log_id)
    {
        return $this->Record->select()->from('download_logs')->where('id', '=', $log_id)->fetch();
    }

    /**
     * Fetches download logs for a given file
     *
     * @param int $file_id The ID of the file whose logs to fetch
     * @return array A list of download logs for this file
     */
    public function getAllByFile($file_id)
    {
        return $this->Record->select()->from('download_logs')->where('file_id', '=', $file_id)->fetchAll();
    }

    /**
     * Fetches the total number of times a specific file has been downloaded
     *
     * @param int $file_id The ID of the file whose logs to fetch
     * @return int The total number of times the file has been downloaded
     */
    public function getAllByFileCount($file_id)
    {
        return $this->Record->select()->from('download_logs')->where('file_id', '=', $file_id)->numResults();
    }
}
