<?php

/**
 * Admin Reports Management
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminReports extends AdminController
{
    /**
     * Pre-action setup method that is called before the index method, or the set controller action
     */
    public function preAction()
    {
        parent::preAction();
        $this->uses(['ReportManager']);
    }

    /**
     * List reports
     */
    public function index()
    {
        // Get the company settings
        $company_settings = $this->SettingsCollection->fetchSettings(null, $this->company_id);

        // Report types
        $types = $this->ReportManager->getAvailable();

        // Create the report
        if (!empty($this->post)) {
            // Set data to send to the report
            $data = $this->post;
            $type = (isset($data['type']) ? $data['type'] : null);
            $format = (isset($data['format']) ? $data['format'] : null);
            unset($data['type'], $data['format']);

            // Generate the report and send it to the browser
            $this->ReportManager->fetchAll($type, $data, $format);

            // Reset fields if anything goes wrong
            if (($errors = $this->ReportManager->errors())) {
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);

                // Reset the values for the report options
                $report_fields = $this->ReportManager->getOptions($type, $data);
                $this->set('report_fields', $report_fields);
            }
        }

        $select = ['' => Language::_('AppController.select.please', true)];
        $this->set(
            'types',
            $select + (isset($this->post['format']) ? $this->ReportManager->getAvailable($this->post['format']) : [])
        );
        $this->set('formats', $select + $this->ReportManager->getFormats());
        $this->set('vars', (isset($vars) ? $vars : new stdClass()));

        $this->Javascript->setFile('date.min.js');
        $this->Javascript->setFile('jquery.datePicker.min.js');
        $this->Javascript->setInline(
            'Date.firstDayOfWeek=' . ($company_settings['calendar_begins'] == 'sunday' ? 0 : 1) . ';'
        );
    }

    /**
     * AJAX retrieves the report types
     */
    public function getReportTypes()
    {
        // Require the format is valid
        if (!$this->isAjax()
            || !isset($this->get['format'])
            || ($this->get['format'] !== ''
                && !array_key_exists($this->get['format'], $this->ReportManager->getFormats())
            )
        ) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $select = ['' => Language::_('AppController.select.please', true)];
        $partial = $this->partial(
            'admin_reports_types',
            [
                'types' => $select + ($this->get['format'] !== ''
                    ? $this->ReportManager->getAvailable($this->get['format'])
                    : []),
                'type' => isset($this->get['type']) ? $this->get['type'] : null
            ]
        );

        // Fetch the report types view
        echo json_encode(['types' => $partial]);
        exit();
    }

    /**
     * AJAX retrieves the view for fields specific to a given report
     */
    public function getReportFields()
    {
        // Require the report is valid
        if (!$this->isAjax()
            || empty($this->get['type'])
            || !array_key_exists($this->get['type'], $this->ReportManager->getAvailable())
        ) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        // Fetch the report options view
        $options = $this->ReportManager->getOptions($this->get['type'], $this->get);

        echo json_encode(['fields' => $options]);
        exit();
    }
}
