<?php
/**
 * Custom Report
 *
 * @package blesta
 * @subpackage blesta.components.reports.custom_report
 * @copyright Copyright (c) 2015, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class CustomReport implements ReportInterface
{
    /**
     * @var \PDOStatement An instance of the PDOStatement
     */
    private $statement;

    /**
     * Load language
     */
    public function __construct()
    {
        Loader::loadComponents($this, ['Record', 'Input']);
        Loader::loadModels($this, ['ReportManager']);

        // Load the language required by this report
        Language::loadLang('custom_report', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Language::_('CustomReport.name', true);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormats()
    {
        return ['csv', 'json'];
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions($company_id, array $vars = [])
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('options', 'default');
        $this->view->setDefaultView('components' . DS . 'reports' . DS . 'custom_report' . DS);

        $reports = $this->getAllReports();

        if (isset($vars['report'])) {
            $report = $this->getReport($vars['report']);
            if ($report) {
                $this->view->set('fields', $report->fields);
            }
        }

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('vars', (object)$vars);
        $this->view->set('reports', ['' => Language::_('CustomReports.options.field_report_select', true)]
            + $this->Form->collapseObjectArray($reports, 'name', 'id'));

        return $this->view->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyInfo()
    {
        $fields = [];
        if ($this->statement) {
            for ($i = 0; $i < $this->statement->columnCount(); $i++) {
                $col = $this->statement->getColumnMeta($i);
                $fields[$col['name']] = ['name' => $col['name']];
            }
        }
        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($company_id, array $vars)
    {
        $report = $this->getReport($vars['report']);
        $fields = isset($vars['field']) ? $vars['field'] : [];

        $rules = [];

        foreach ($report->fields as $field) {
            if (null !== $field->regex) {
                $rules[$field->name] = [
                    'valid' => [
                        'rule' => ['matches', $field->regex],
                        'message' => Language::_('CustomReport.!error.field', true, $field->label)
                    ]
                ];
            }
        }

        $this->Input->setRules($rules);

        if ($this->Input->validates($fields)) {
            try {
                $this->statement = $this->Record->query($this->filterQuery($report->query), $fields);
            } catch (Exception $e) {
                $this->Input->setErrors(['query' => ['error' => $e->getMessage()]]);
                return new ArrayIterator([]);
            }
            return new IteratorIterator($this->statement);
        }
    }

    /**
     * Returns any errors
     *
     * @return mixed An array of errors, false if no errors set
     */
    public function errors()
    {
        return $this->Input->errors();
    }

    /**
     * Fetches a custom report
     *
     * @param int $report_id
     * @return stdClass The report
     */
    protected function getReport($report_id)
    {
        return $this->ReportManager->getReport($report_id);
    }

    /**
     * Fetches all custom reports
     *
     * @return array An array of stdClass, each representing a report
     */
    protected function getAllReports()
    {
        return $this->ReportManager->getReports();
    }

    /**
     * Protect users from themselves by (weakly) preventing destructive queries
     *
     * @param string $sql The user provided query
     * @return string The filtered query
     */
    protected function filterQuery($sql)
    {
        // only one statement, please
        list($filtered_sql) = explode(';', $sql);

        $filtered_sql = trim($filtered_sql);

        // Ensure this is a select query
        if (stripos($filtered_sql, 'SELECT') !== 0) {
            return null;
        }

        return $filtered_sql;
    }
}
