<?php

/**
 * Report Manager
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ReportManager extends AppModel
{
    /**
     * @var The path to the temp directory
     */
    private $temp_dir = null;

    /**
     * @var The company ID for this report
     */
    private $company_id = null;

    /**
     * Load language
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['report_manager']);
        Loader::loadComponents($this, ['Download', 'SettingsCollection']);

        // Set the date formats
        if (!isset($this->Companies)) {
            Loader::loadModels($this, ['Companies']);
        }

        $this->company_id = Configure::get('Blesta.company_id');
        $this->Date->setTimezone('UTC', $this->Companies->getSetting($this->company_id, 'timezone')->value);
        $this->Date->setFormats([
            'date' => $this->Companies->getSetting($this->company_id, 'date_format')->value,
            'date_time' => $this->Companies->getSetting($this->company_id, 'datetime_format')->value
        ]);

        // Set the temp directory
        $temp_dir = $this->SettingsCollection->fetchSystemSetting(null, 'temp_dir');
        if (isset($temp_dir['value'])) {
            $this->temp_dir = $temp_dir['value'];
        }
    }

    /**
     * Instantiates the given report and returns its instance
     *
     * @param string $class The name of the class in file_case to load
     * @return An instance of the report specified
     */
    private function loadReport($class)
    {
        // Load the report factory if not already loaded
        if (!isset($this->Reports)) {
            Loader::loadComponents($this, ['Reports']);
        }

        // Instantiate the module and return the instance
        return $this->Reports->create($class);
    }

    /**
     * Retrieves a list of report formats
     *
     * @return array A list of report formats and their language
     */
    public function getFormats()
    {
        return [
            'csv' => $this->_('ReportManager.getformats.csv'),
            'json' => $this->_('ReportManager.getformats.json')
        ];
    }

    /**
     * Retrieves the name for the given report type
     *
     * @param string $type The type of report to fetch the name for
     * @return string The name of the report
     */
    public function getName($type)
    {
        $this->Input->setRules($this->getRules());
        $params = ['type' => $type];

        if ($this->Input->validates($params)) {
            // Instantiate the report
            $report = $this->loadReport($type);

            return $report->getName();
        }
    }

    /**
     * Retrieves a list of all available reports (those that exist on the file system)
     *
     * @param string $format The format the reports must support (optional)
     * @return array An array representing each report and its name
     */
    public function getAvailable($format = 'any')
    {
        $reports = [];

        $dir = opendir(COMPONENTDIR . 'reports');
        while (false !== ($report = readdir($dir))) {
            // If the file is not a hidden file, and is a directory, accept it
            if (substr($report, 0, 1) != '.' && is_dir(COMPONENTDIR . 'reports' . DS . $report)) {
                try {
                    $rep = $this->loadReport($report);
                    if ($format == 'any'
                        || ($rep instanceof ReportInterface && $format == 'csv')
                        || in_array($format, $rep->getFormats())
                    ) {
                        $reports[$report] = $rep->getName();
                    }
                } catch (Exception $e) {
                    // The report could not be loaded, try the next
                    continue;
                }
            }
        }

        return $reports;
    }

    /**
     * Retrieves the options for the given report type. Sets Input errors on failure
     *
     * @param string $type The type of report to fetch the options for
     * @param array $vars A list of option values to pass to the report (optional)
     * @return string The options as a view
     */
    public function getOptions($type, array $vars = [])
    {
        $this->Input->setRules($this->getRules());
        $params = ['type' => $type];

        if ($this->Input->validates($params)) {
            // Instantiate the report
            $report = $this->loadReport($type);

            return $report->getOptions($this->company_id, $vars);
        }
    }

    /**
     * Generates the report type with the given vars. Sets Input errors on failure
     *
     * @param string $type The type of report to fetch
     * @param array $vars A list of option values to pass to the report
     * @param string $format The format of the report to generate (optional, default csv)
     * @param string $return (optional, default "download") One of the following:
     *
     *  - download To build and send the report to the browser to prompt for download; returns null
     *  - false To build and send the report to the browser to prompt for download; returns null
     *  - object To return a PDOStatement object representing the report data; returns PDOStatement
     *  - true To return a PDOStatement object representing the report data; returns PDOStatement
     *  - file To build the report and store it on the file system; returns the path to the file
     * @return mixed A PDOStatement, string, or void based on the $return parameter
     */
    public function fetchAll($type, array $vars, $format = 'csv', $return = 'download')
    {
        // Accept boolean return value for backward compatibility
        // Convert return to one of the 3 accepted types: download, object, file
        if ($return === true || $return == 'true') {
            $return = 'object';
        } elseif ($return === false || $return == 'false') {
            $return = 'download';
        }

        // Default to download
        $return = in_array($return, ['download', 'object', 'file']) ? $return : 'download';

        // Validate the report type/format are valid
        $rules = [
            'format' => [
                'valid' => [
                    'rule' => [[$this, 'validateFormat'], $type],
                    'message' => $this->_('ReportManager.!error.format.valid', true)
                ]
            ]
        ];

        $params = ['type' => $type, 'format' => $format];
        $this->Input->setRules(array_merge($this->getRules(), $rules));

        if ($this->Input->validates($params)) {
            // Instantiate the report
            $report = $this->loadReport($type);

            // Build the report data
            $results = $report->fetchAll($this->company_id, $vars);

            if (method_exists($report, 'errors') && ($errors = $report->errors())) {
                $this->Input->setErrors($errors);
                return;
            }

            // Return the Iterator
            if ($return === 'object') {
                return $results;
            }

            // Create the file
            $path_to_file = rtrim($this->temp_dir, DS) . DS . $this->makeFileName($format);

            if (empty($this->temp_dir)
                || !is_dir($this->temp_dir)
                || (file_put_contents($path_to_file, '') === false)
                || !is_writable($path_to_file)
            ) {
                $this->Input->setErrors(
                    [
                        'temp_dir' => [
                            'writable' => $this->_('ReportManager.!error.temp_dir.writable', true)
                        ]
                    ]
                );
                return;
            }

            // Build the report and send it to the browser
            switch ($format) {
                case 'csv':
                    $this->buildCsv($report, $results, $path_to_file);
                    break;
                case 'json':
                    $this->buildJson($report, $results, $path_to_file);
                    break;
            }

            // Return the path to the file on the file system
            if ($return == 'file') {
                return $path_to_file;
            }

            // Download the data
            $new_file_name = 'report-' . $type . '-' . $this->Date->cast(date('c'), 'Y-m-d') . '.' . $format;
            $this->Download->setContentType('text/' . $format);

            // Download from temp file
            $this->Download->downloadFile($path_to_file, $new_file_name);
            @unlink($path_to_file);
            exit();
        }
    }

    /**
     * Creates a temporary file name to store to disk
     *
     * @param string $ext The file extension
     * @return string The rewritten file name in the format of
     *  YmdTHisO_[hash].[ext] (e.g. 20121009T154802+0000_1f3870be274f6c49b3e31a0c6728957f.txt)
     */
    private function makeFileName($ext)
    {
        $file_name = md5(uniqid()) . $ext;

        return $this->Date->format('Ymd\THisO', date('c')) . '_' . $file_name;
    }

    /**
     * Build the report file in JSON format
     *
     * @param ReportInterface $report The report type this row is for
     * @param Iterator $results A list of data with which to create a JSON object
     * @param string $path_to_file The path to the JSON file
     */
    private function buildJson($report, $results, $path_to_file)
    {
        $keys = $report->getKeyInfo();

        $file = fopen($path_to_file, 'w+');
        // Wrap the data in a JSON array
        fwrite($file, '[');
        $i = 0;
        foreach ($results as $result) {
            // Format data and add to the file
            $result = $this->getRow($result, $keys, 'json');
            $result = json_encode(
                $result,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES
            );
            fwrite($file, ($i == 0 ? '' : ',') . $result);
            $i++;
        }

        // Close the JSON array
        fwrite($file, ']');
        fclose($file);
    }

    /**
     * Build the report file in CSV format
     *
     * @param ReportInterface $report The report type this row is for
     * @param Iterator $results A list of data with which to populate the CSV rows
     * @param string $path_to_file The path to the CSV file
     */
    private function buildCsv($report, $results, $path_to_file)
    {
        $headings = $report->getKeyInfo();

        $heading_names = [];
        foreach ($headings as $key => $column) {
            $heading_names[$key] = isset($column['name']) ? $column['name'] : $key;
        }

        $heading_row = $this->buildCsvRow($heading_names);

        // Create the file and add the headers
        $file = fopen($path_to_file, 'w+');
        fwrite($file, $heading_row);

        // Add row data
        foreach ($results as $fields) {
            // Add the row to the file
            fwrite($file, $this->buildCsvRow($this->getRow($fields, $headings, 'csv')));
        }

        fclose($file);
    }

    /**
     * Uses Excel-style formatting for CSV fields (individual cells)
     *
     * @param mixed $field A single string of data representing a cell, or an array of fields representing a row
     * @return mixed An escaped and formatted single cell or array of fields as given
     */
    protected function formatCsv($field)
    {
        if (is_array($field)) {
            foreach ($field as &$cell) {
                $cell = '"' . str_replace('"', '""', $cell) . '"';
            }

            return $field;
        }
        return '"' . str_replace('"', '""', $field) . '"';
    }

    /**
     * Builds a CSV row
     *
     * @param array $fields A list of data to place in each cell
     * @return string A CSV row containing the field data
     */
    protected function buildCsvRow(array $fields)
    {
        $row = '';
        $formatted_fields = $this->formatCsv($fields);
        $num_fields = count($fields);

        $i = 0;
        foreach ($fields as $key => $value) {
            $row .= $formatted_fields[$key] . (++$i == $num_fields ? "\n" : ',');
        }

        return $row;
    }

    /**
     * Get data for a row and format it
     *
     * @param stdClass $fields A list of data with which to populate the CSV rows
     * @param stdClass $keys A list of keys and their formatting rules
     * @param stdClass $format What report format is being used
     * @return array A row of data
     */
    private function getRow(stdClass $fields, $keys, $format)
    {
        $row = [];
        // Build each cell value
        foreach ($keys as $key => $value) {
            // Some properties may be objects themselves, for this reason we recursively filter any such properties
            if ($key === 'children') {
                foreach ($value as $child_key => $child_structure) {
                    if (!property_exists($fields, $child_key)) {
                        continue;
                    }

                    $formatted_children = [];
                    if (is_array($fields->{$child_key})) {
                        foreach ($fields->{$child_key} as $child) {
                            $formatted_children[] = $this->getRow($child, $child_structure, $format);
                        }
                    } elseif (is_object($fields->{$child_key})) {
                        $formatted_children = $this->getRow($fields->{$child_key}, $child_structure, $format);
                    } else {
                        $formatted_children = null;
                    }

                    $row[$child_key] = $formatted_children;
                }
                continue;
            }

            $cell = (property_exists($fields, $key) ? $fields->{$key} : '');
            $formatters = (array_key_exists('format', $value)
                ? (is_array($value['format']) ? $value['format'] : [$value['format']])
                : []
            );
            $options = (array_key_exists('options', $value) ? $value['options'] : null);

            foreach ($formatters as $formatter) {
                // Add a date format to the cell
                if ($formatter == 'date' && !empty($cell)) {
                    $cell = $this->Date->cast($cell, 'date_time');
                } elseif ($formatter == 'replace' && !empty($options) && is_array($options)) {
                    // Replace the value with one of the options provided
                    $cell = array_key_exists($cell, $options) ? $options[$cell] : $cell;
                } elseif (is_callable($formatter) && !in_array($formatter, ['date', 'replace'])) {
                    // Format using the provided callback, which should not be an existing formatter,
                    // like php's date function
                    $cell = call_user_func($formatter, $key, $cell, $format);
                }
            }

            $row[$key] = $cell;
        }

        return $row;
    }

    /**
     * Validates that the given report type exists
     *
     * @param string $type The report type
     * @return bool True if the report type exists, false otherwise
     */
    public function validateType($type)
    {
        $reports = $this->getAvailable();

        return array_key_exists($type, $reports);
    }
    /**
     * Validates that the given format is valid for the report type given
     *
     * @param string $format The report format
     * @param string $type The report type
     * @return bool True if the format is valid, false otherwise
     */
    public function validateFormat($format, $type)
    {
        $reports = $this->getAvailable($format);

        return array_key_exists($format, $this->getFormats()) && array_key_exists($type, $reports);
    }

    /**
     * Returns the rules to validate the report type
     *
     * @return array A list of rules
     */
    private function getRules()
    {
        return [
            'type' => [
                'valid' => [
                    'rule' => [[$this, 'validateType']],
                    'message' => $this->_('ReportManager.!error.type.valid', true)
                ]
            ]
        ];
    }

    /**
     * Fetch a custom report
     *
     * @param int $id The ID of the custom report
     * @return stdClass The custom report
     */
    public function getReport($id)
    {
        $fields = ['id', 'company_id', 'name', 'query', 'date_created'];
        $report = $this->Record->select($fields)
            ->from('reports')
            ->where('id', '=', $id)
            ->fetch();
        if ($report) {
            $report->fields = $this->getReportFields($id);
        }
        return $report;
    }

    /**
     * Fetch custom report fields
     *
     * @param int $id The ID of the custom report
     * @return array An array of stdClass objects representing custom report fields
     */
    protected function getReportFields($id)
    {
        $fields = ['id', 'report_id', 'name', 'label', 'type', 'values', 'regex'];

        $report_fields = $this->Record->select($fields)
            ->from('report_fields')
            ->where('report_id', '=', $id)
            ->fetchAll();

        foreach ($report_fields as $field) {
            if ($field->values != '') {
                $field->values = unserialize($field->values);
            }

            $field->required = 'no';
            if ($field->regex !== null) {
                $field->required = 'yes';
                if ($field->regex !== $this->reportDefaultRegex($field->type)) {
                    $field->required = 'custom';
                }
            }
        }
        return $report_fields;
    }

    /**
     * Get all custom reports available
     *
     * @return array An array of stdClass objects each representing a report
     */
    public function getReports()
    {
        $fields = ['id', 'company_id', 'name', 'query', 'date_created'];
        return $this->Record->select($fields)
            ->from('reports')
            ->where('company_id', '=', $this->company_id)
            ->fetchAll();
    }

    /**
     * Add a custom report
     *
     * @param array $vars
     * @return stdClass The report added
     */
    public function addReport(array $vars)
    {
        $vars['date_created'] = date('c');
        $vars['company_id'] = $this->company_id;

        $this->Input->setRules($this->getReportRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ['id', 'company_id', 'name', 'query', 'date_created'];
            $this->Record->insert('reports', $vars, $fields);

            $id = $this->Record->lastInsertId();

            $this->saveReportFields($id, $vars['fields']);
            return $this->getReport($id);
        }
    }

    /**
     * Edit a custom report
     *
     * @param int $id
     * @param array $vars
     * @return stdClass The report updated
     */
    public function editReport($id, array $vars)
    {
        $this->Input->setRules($this->getReportRules($vars, true));

        if ($this->Input->validates($vars)) {
            $fields = ['company_id', 'name', 'query'];
            $this->Record->where('id', '=', $id)
                ->update('reports', $vars, $fields);

            $this->saveReportFields($id, $vars['fields']);
            return $this->getReport($id);
        }
    }

    /**
     * Save report fields
     *
     * @param int $id The ID of the report whose fields to save
     * @param array $report_fields A numerically indexed array of report fields containing:
     *
     *  - id
     *  - name
     *  - label
     *  - type
     *  - values
     *  - regex
     */
    protected function saveReportFields($id, array $report_fields)
    {
        $fields = ['report_id', 'name', 'label', 'type', 'values', 'regex'];
        foreach ($report_fields as $field) {
            $field['report_id'] = $id;
            $field['regex'] = $this->requiredToRegex(
                $field['type'],
                $field['required'],
                $field['regex']
            );

            if (empty($field['values'])) {
                $field['values'] = null;
            } else {
                $values = explode(',', $field['values']);
                $temp_values = [];
                foreach ($values as $val) {
                    // Set the value as the key as well
                    $val = trim($val);
                    $temp_values[$val] = $val;
                }
                $field['values'] = serialize($temp_values);
            }

            // Remove field
            if (!empty($field['id']) && empty($field['name'])) {
                $this->Record->from('report_fields')
                    ->where('report_id', '=', $id)
                    ->where('id', '=', $field['id'])->delete();
            } elseif (!empty($field['id'])) {
                $this->Record->where('id', '=', $field['id'])
                    ->where('report_id', '=', $id)
                    ->update('report_fields', $field, $fields);
            } elseif (!empty($field['name'])) {
                $this->Record->insert('report_fields', $field, $fields);
            }
        }
    }

    /**
     * Delete a custom report
     *
     * @param int $id The ID of the custom report to delete
     */
    public function deleteReport($id)
    {
        $this->Record->from('reports')
            ->leftJoin('report_fields', 'report_fields.report_id', '=', 'reports.id', false)
            ->where('reports.id', '=', $id)
            ->where('reports.company_id', '=', $this->company_id)
            ->delete(['reports.*', 'report_fields.*']);
    }

    /**
     * Fetches rules for adding/editing custom reports
     *
     * @param array $vars
     * @param bool $edit
     * @return array Rules
     */
    private function getReportRules(array $vars, $edit = false)
    {
        $rules = [
            'name' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('ReportManager.!error.name.valid')
                ]
            ],
            'query' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'last' => true,
                    'message' => $this->_('ReportManager.!error.query.empty')
                ],
                'valid' => [
                    'rule' => [[$this, 'validateQuery']],
                    'message' => $this->_('ReportManager.!error.query.valid')
                ]
            ],
            'date_created' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isDate',
                    'post_format' => [[$this, 'dateToUtc']],
                    'message' => $this->_('ReportManager.!error.date_created.format')
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Validate that only one query is present, and it is a select query
     *
     * @param string $sql The user provided query
     * @return bool True if the query is valid, false otherwise
     */
    public function validateQuery($sql)
    {
        $pos = strpos($sql, ';');
        $filtered_sql = substr($sql, 0, $pos + 1);
        if ($pos === false) {
            $filtered_sql = $sql;
        }

        $filtered_sql = trim($filtered_sql);

        return ($filtered_sql === trim($sql) && stripos($filtered_sql, 'SELECT') === 0);
    }

    /**
     * Fetch report field types
     *
     * @return array An array of key/value pairs
     */
    public function reportFieldTypes()
    {
        return [
            'text' => $this->_('ReportManager.reportfieldtypes.text'),
            'select' => $this->_('ReportManager.reportfieldtypes.select'),
            'date' => $this->_('ReportManager.reportfieldtypes.date')
        ];
    }

    /**
     * Fetche report field required types
     *
     * @return array An array of key/value pairs
     */
    public function reportRequiredType()
    {
        return [
            'no' => $this->_('ReportManager.reportrequiredtypes.no'),
            'yes' => $this->_('ReportManager.reportrequiredtypes.yes'),
            'custom' => $this->_('ReportManager.reportrequiredtypes.custom')
        ];
    }

    /**
     * Convert the report field type and required status to a regex
     *
     * @param string $type The field type
     * @param string $required Required option
     * @param string $regex Defined regex (if any)
     * @return string The regex to use
     */
    protected function requiredToRegex($type, $required, $regex = null)
    {
        if ($required == 'custom') {
            return $regex;
        }
        if ($required == 'no') {
            return null;
        }

        return $this->reportDefaultRegex($type);
    }

    /**
     * Fetch the default regex for the given report field type
     *
     * @param string $type The field type
     * @return string The default regex to use for that type
     */
    protected function reportDefaultRegex($type)
    {
        switch ($type) {
            case 'date':
                return '/[0-9]{4}-[0-9]{2}-[0-9]{2}/';
            case 'select':
            // no break
            case 'text':
                return '/.+/';
        }
    }
}
