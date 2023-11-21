<?php
/**
 * Mass Mailer parent model
 *
 * @package blesta
 * @subpackage blesta.plugins.mass_mailer
 * @copyright Copyright (c) 2016, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class MassMailerModel extends AppModel
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Auto load language for these models
        $this->loadLang(Loader::fromCamelCase(get_class($this)));
    }

    /**
     * Retrieves the path to the uploads directory
     *
     * @return string The upload directory path
     */
    public function getUploadDirectory()
    {
        // Set the uploads directory
        Loader::loadComponents($this, ['SettingsCollection', 'Upload']);
        $temp = $this->SettingsCollection->fetchSetting(
            null,
            Configure::get('Blesta.company_id'),
            'uploads_dir'
        );
        return $temp['value'] . Configure::get('Blesta.company_id')
            . DS . 'mass_mailer_files' . DS;
    }

    /**
     * Loads the given language file
     *
     * @param string $filename The language filename without the extension
     */
    public function loadLang($filename)
    {
        // Load the language
        Language::loadLang(
            [$filename],
            null,
            dirname(__FILE__) . DS . 'language' . DS
        );
    }
}
