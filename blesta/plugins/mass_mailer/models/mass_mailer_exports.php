<?php

use PhillipsData\Csv\Factory;
use Blesta\MassMailer\Traits\Parser;

/**
 * MassMailerExports model
 *
 * @package blesta
 * @subpackage blesta.plugins.mass_mailer.models
 * @copyright Copyright (c) 2016, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class MassMailerExports extends MassMailerModel
{
    use Parser;

    /**
     * @var The job ID
     */
    private $job_id;
    /**
     * @var The export's filename
     */
    private $filename = 'export.csv';
    /**
     * @var The job file's open mode
     */
    private $file_open_mode = 'w';
    /**
     * @var The CSV writer
     */
    private $writer;
    /**
     * @var The CSV columns
     */
    private $columns = [];

    /**
     * Set exportable columns
     */
    public function __construct()
    {
        parent::__construct();

        // Load language for the export columns
        $this->loadLang("config_export");
    }

    /**
     * Sets the job to use for this export
     *
     * @param int $job_id The ID of the job from which to create an export
     * @param string $file_open_mode The type of access to the file stream for this job
     *  (e.g. 'a' to append to an existing job file, 'w' to write a new job file; default 'w')
     */
    public function setJob($job_id, $file_open_mode = 'w')
    {
        $this->job_id = (int)$job_id;
        $this->filename = 'export-' . $this->job_id . '.csv';
        $this->writer = null;
        $this->file_open_mode = $file_open_mode;
    }

    /**
     * Sets the columns for the export
     *
     * @param array $columns A key/value array of columns where the key is the column name
     *  and the value is the value to use for the cell of each row in this column
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * Retrieves the filename of the saved export file
     *
     * @return string The filename
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Adds a row to the CSV export for the current job
     *
     * @param int $task_id The ID of the task representing the row to add to the export
     */
    public function addRow($task_id)
    {
        $vars = ['task_id' => $task_id];
        $this->Input->setRules($this->getRules());

        if (!$this->Input->validates($vars)) {
            return;
        }

        // Set the writer
        $this->createWriter();

        // Build the CSV row
        if (($task = $this->getTask((int)$task_id))) {
            // Fetch tags
            $default_tags = $this->getDefaultTags();
            $tags = array_merge(
                $this->getContactTags($task->contact_id, $task->service_id),
                $default_tags
            );

            // Fetch the parser to do tag replacement
            $parser = $this->getParser();
            $parser_options = $this->getParserOptions();

            // Do tag replacements
            $row = [];

            // Add the task row
            foreach ($this->columns as $col => $value) {
                // Determine the column name
                $column_name = $this->_('Config.export.' . $col);
                if (empty($column_name)) {
                    $column_name = $col;
                }

                // Encapsulate the value in the parser's expected characters
                $value = $parser_options['text']['VARIABLE_START']
                    . $value . $parser_options['text']['VARIABLE_END'];

                $row[$col] = $parser->parseString($value, $parser_options['text'])
                    ->render($tags);
            }

            // Add the row
            if ($this->writer) {
                $this->writer->writeRow($row);
            }
        }
    }

    /**
     * Adds a row of headers to the export using the preset columns
     */
    public function addHeaders()
    {
        // Set the writer
        $this->createWriter();

        // Add the row columns if not already set
        $row = [];
        foreach (array_keys($this->columns) as $column) {
            // Determine the column name
            $column_name = $this->_('Config.export.' . $column);
            if (empty($column_name)) {
                $column_name = $column;
            }

            $row[$column] = $column_name;
        }

        // Add the columns row
        if ($this->writer) {
            $this->writer->writeRow($row);
        }
    }

    /**
     * Creates an instance of the CSV writer
     */
    private function createWriter()
    {
        if ($this->writer === null) {
            try {
                $this->writer = Factory::writer(
                    $this->getUploadDirectory() . $this->filename,
                    ',',
                    '"',
                    '\\',
                    $this->file_open_mode
                );
            } catch (Exception $e) {
                $this->Input->setErrors(['error', $e->getMessage()]);
            }
        }
    }

    /**
     * Retrieves a mailing task row
     *
     * @param int $task_id The ID of the task to fetch
     * @return mixed An stdClass object representing the task, otherwise false
     */
    private function getTask($task_id)
    {
        return $this->Record->select()
            ->from('mass_mailer_tasks')
            ->where('id', '=', $task_id)
            ->fetch();
    }

    /**
     * Retrieves a set of rules for validating adding a row
     *
     * @return array An array of Input rules
     */
    private function getRules()
    {
        return [
            'task_id' => [
                'valid' => [
                    'rule' => [[$this, 'validateJobTask']],
                    'message' => $this->_('MassMailerExports.!error.task_id.valid')
                ]
            ]
        ];
    }

    /**
     * Validates that the given task belongs to the current job
     *
     * @param int $task_id The ID of the task
     * @return bool True if the task belongs to the current job, otherwise false
     */
    public function validateJobTask($task_id)
    {
        $total = $this->Record->select(['id'])
            ->from('mass_mailer_tasks')
            ->where('id', '=', $task_id)
            ->where('job_id', '=', $this->job_id)
            ->numResults();

        return ($total > 0);
    }
}
