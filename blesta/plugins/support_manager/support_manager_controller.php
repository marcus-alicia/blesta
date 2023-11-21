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
class SupportManagerController extends AppController
{
    /**
     * Setup
     */
    public function preAction()
    {
        $this->structure->setDefaultView(APPDIR);
        parent::preAction();

        // Load config
        Configure::load('support_manager', dirname(__FILE__) . DS . 'config' . DS);

        // Auto load language for the controller
        Language::loadLang([Loader::fromCamelCase(get_class($this))], null, dirname(__FILE__) . DS . 'language' . DS);
        Language::loadLang('global', null, dirname(__FILE__) . DS . 'language' . DS);

        // Override default view directory
        $this->view->view = 'default';
        $this->orig_structure_view = $this->structure->view;
        $this->structure->view = 'default';
    }

    /**
     * Fetch a list of file attachments uploaded by dropzone.js
     *
     * @return array A list of file attachments compatible with the global FILES array,
     *  which contains an array of "attachment" files
     */
    protected function fetchDropzoneFiles()
    {
        // Set the uploads directory
        $upload_path = $this->getDropzonePath();
        $files = [];

        // Build the full path for the attached files
        foreach ($this->post['dropzone']['name'] as $file) {
            $files[] = $upload_path . $file;
        }
        $this->post['dropzone']['tmp_name'] = $files;
        $files = ['attachment' => $this->post['dropzone']];
        unset($this->post['dropzone']);

        // Build files array
        $arrays = ['name', 'type', 'size', 'tmp_name'];

        foreach ($arrays as $array) {
            $files['attachment'][$array] = array_values($files['attachment'][$array]);
        }

        return $files;
    }

    /**
     * Temporarily save asynchronously uploaded files by dropzone.js
     */
    protected function processDropzoneAttachments()
    {
        Loader::loadComponents($this, ['Upload']);

        $upload_path = $this->getDropzonePath();

        // Create the upload path if it doesn't already exist
        $this->Upload->createUploadPath($upload_path, 0777);

        $this->Upload->setFiles($this->files);
        $this->Upload->setUploadPath($upload_path);

        if (!($errors = $this->Upload->errors())) {
            // Will not overwrite existing file
            $this->Upload->writeFile('dropzone');
            $data = $this->Upload->getUploadData();

            // Set the file name of the file that was uploaded
            if (isset($data['dropzone'][0]['full_path'])) {
                $this->files['dropzone']['tmp_name'] = [$data['dropzone'][0]['full_path']];
            }
        }

        exit;
    }

    /**
     * Gets the temporary path of uploaded files by dropzone.js
     *
     * @return string The temporary path where files are uploaded by dropzone.js
     */
    private function getDropzonePath()
    {
        Loader::loadComponents($this, ['SettingsCollection']);

        // Set the uploads directory
        $uploads_dir = $this->SettingsCollection->fetchSetting(
            null,
            Configure::get('Blesta.company_id'),
            'uploads_dir'
        );
        $uploads_dir = isset($uploads_dir['value']) ? $uploads_dir['value'] : '';

        return $uploads_dir . Configure::get('Blesta.company_id') . DS . 'support_manager_files' . DS . 'dropzone' . DS;
    }

    /**
     * Check to ensure that the Mailparse extension is enabled
     *
     * @return bool True if the Mailparse extension is enabled, false otherwise
     */
    protected function mailparseEnabled()
    {
        return extension_loaded('mailparse');
    }

    /**
     * Converts a past date to x days y mins format
     *
     * @param string $date_time The date time to convert
     * @return string The date converted to time
     */
    protected function timeSince($date_time)
    {
        $time = $this->Date->toTime(date('c')) - $this->Date->toTime($date_time);

        // Only deal with times in the past
        if ($time < 0) {
            return '';
        }

        $day = 86400; // seconds in a day
        $hour = 3600; // seconds in an hour

        $days_since = floor($time/$day); // Number of days since
        $hours_since = ($time/$hour)%24; // Number of hours since
        $mins_since = ($time/60)%60; // Number of mins since

        // Set the time language
        $days_since_lang = ($days_since > 0 ? Language::_('Global.time_since.day', true, $days_since) : '');
        $hours_since_lang = ($hours_since > 0 ? Language::_('Global.time_since.hour', true, $hours_since) : '');
        $time_since = $days_since_lang . ' ' . $hours_since_lang . ' ';

        // Include minutes if no other time unit is available, or if greater than 0
        if (empty($days_since_lang) && empty($hours_since_lang)) {
            $time_since .= Language::_('Global.time_since.minute', true, $mins_since);
        } else {
            $time_since .= ($mins_since > 0 ? Language::_('Global.time_since.minute', true, $mins_since) : '');
        }

        return $time_since;
    }

    /**
     * Verifies if the current user has permission to the given area
     *
     * @param string $area The generic area
     * @return bool True if user has permission, false otherwise
     */
    protected function hasPermission($area)
    {
        if (method_exists(get_parent_class($this), 'hasPermission')) {
            return parent::hasPermission($area);
        }

        if (!isset($this->Contacts)) {
            $this->uses(['Contacts']);
        }

        if ((
            $contact = $this->Contacts->getByUserId(
                $this->Session->read('blesta_id'),
                $this->Session->read('blesta_client_id')
            )
        )) {
            return $this->Contacts->hasPermission($this->company_id, $contact->id, $area);
        }

        return true;
    }
}

require_once dirname(__FILE__) . DS . 'support_manager_kb_controller.php';
