<?php

require_once dirname(__FILE__) . DS . '..' . DS . 'csv_migrator.php';

/**
 * Csv 1.0 Migrator.
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.components.migrators.csv
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Csv1_0 extends CsvMigrator
{
    /**
     * Construct.
     *
     * @param Record $local The database connection object to the local server
     */
    public function __construct(Record $local)
    {
        // Call parent constructor
        parent::__construct($local);

        // Set timeout limit
        set_time_limit(60 * 60 * 15); // 15 minutes

        // Load language
        Language::loadLang(['csv1_0'], null, dirname(__FILE__) . DS . 'language' . DS);

        // Set current path
        $this->path = dirname(__FILE__);
    }

    /**
     * Processes settings (validating input). Sets any necessary input errors.
     *
     * @param array $vars An array of key/value input pairs
     */
    public function processSettings(array $vars = null)
    {
        $rules = [
            'csv_data' => [
                'valid' => [
                    'rule' => [[$this, 'validateCsvData']],
                    'message' => Language::_('Csv1_0.!error.csv_data.invalid', true)
                ]
            ]
        ];

        // Validates input
        $this->Input->setRules($rules);

        if (!$this->Input->validates($vars)) {
            return;
        }

        if (isset($vars['enable_debug']) && $vars['enable_debug'] == 'true') {
            $this->enable_debug = true;
        }

        // Build settings array
        $this->settings = $vars;

        // Save parsed data
        $this->loadModel('CsvParser');
        $this->remote = $this->CsvParser->getRows($vars['csv_data']);
    }

    /**
     * Processes configuration (validating input). Sets any necessary input errors.
     *
     * @param array $vars An array of key/value input pairs
     */
    public function processConfiguration(array $vars = null)
    {
        // Set mapping for fields (local field => remote field)
        $this->mappings['remote_fields'] = [];
        if (isset($vars['remote_fields'])) {
            foreach ($vars['remote_fields'] as $local_field => $remote_field) {
                if (!empty($remote_field)) {
                    $this->mappings['remote_fields'][$local_field] = trim($remote_field);
                }
            }
        }
    }

    /**
     * Returns a view to handle settings.
     *
     * @param array $vars An array of input key/value pairs
     * @return string The HTML used to request input settings
     */
    public function getSettings(array $vars)
    {
        $this->view = $this->makeView('settings', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));
        $this->view->set('vars', (object) $vars);

        Loader::loadHelpers($this, ['Html', 'Form']);

        return $this->view->fetch();
    }

    /**
     * Returns a view to configuration run after settings but before import.
     *
     * @param array $vars An array of input key/value pairs
     * @return string The HTML used to request input settings, return null to bypass
     */
    public function getConfiguration(array $vars)
    {
        $this->view = $this->makeView('configuration', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));
        $this->view->set('vars', (object) $vars);

        Loader::loadHelpers($this, ['Html', 'Form']);
        Loader::loadModels($this, ['Packages']);

        // Get csv fields
        $this->loadModel('CsvParser');
        $remote_fields = $this->CsvParser->getColumnsSelect(isset($vars['csv_data']) ? $vars['csv_data'] : '');
        $remote_fields = array_merge(
            [Language::_('Csv1_0.configuration.no_import', true)],
            $remote_fields ? $remote_fields : []
        );
        $this->view->set('remote_fields', $remote_fields);

        return $this->view->fetch();
    }

    /**
     * Check if the given CSV data is valid.
     *
     * @param string $data The CSV data to validate
     * @return bool True if the data is valid
     */
    public function validateCsvData($data)
    {
        $rows = explode("\n", trim($data));

        if (is_array($rows) && count($rows) > 1) {
            $columns = explode(',', $rows[0]);

            return is_array($columns) && count($columns) > 1;
        }

        return false;
    }
}
