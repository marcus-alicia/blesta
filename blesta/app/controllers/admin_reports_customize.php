<?php

/**
 * Admin Reports Customization
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminReportsCustomize extends AdminController
{
    /**
     * Bootstrap
     */
    public function preAction()
    {
        parent::preAction();
        $this->uses(['ReportManager']);
    }

    /**
     * List custom reports
     */
    public function index()
    {
        $this->set('reports', $this->ReportManager->getReports());
    }

    /**
     * Add a custom report
     */
    public function add()
    {
        $array_helper = $this->DataStructure->create('Array');

        if (!empty($this->post)) {
            $this->post['fields'] = $array_helper->keyToNumeric($this->post['fields']);
            $this->ReportManager->addReport($this->post);

            if (($errors = $this->ReportManager->errors())) {
                $this->setMessage('error', $errors);
                $this->set('vars', (object) $this->post);
            } else {
                $this->flashMessage('message', Language::_('AdminReportsCustomize.!success.added', true));
                $this->redirect($this->base_uri . 'reports_customize');
            }
        }

        $this->set('field_types', $this->ReportManager->reportFieldTypes());
        $this->set('required_types', $this->ReportManager->reportRequiredType());
    }

    /**
     * Edit a custom report
     */
    public function edit()
    {
        if (!isset($this->get[0]) || !($report = $this->ReportManager->getReport($this->get[0]))) {
            $this->redirect($this->base_uri . 'reports_customize');
        }

        // Format the values to CSV for display
        foreach ($report->fields as &$field) {
            $field->values = $this->formatValues($field->values);
        }

        $array_helper = $this->DataStructure->create('Array');
        $report->fields = $array_helper->numericToKey($report->fields);
        $this->set('vars', $report);

        if (!empty($this->post)) {
            $this->post['fields'] = $array_helper->keyToNumeric($this->post['fields']);
            $this->ReportManager->editReport($report->id, $this->post);

            if (($errors = $this->ReportManager->errors())) {
                $this->setMessage('error', $errors);
                $this->set('vars', (object) $this->post);
            } else {
                $this->flashMessage('message', Language::_('AdminReportsCustomize.!success.edited', true));
                $this->redirect($this->base_uri . 'reports_customize');
            }
        }

        $this->set('field_types', $this->ReportManager->reportFieldTypes());
        $this->set('required_types', $this->ReportManager->reportRequiredType());
    }

    /**
     * Delete a custom report
     */
    public function delete()
    {
        if (!isset($this->post['id']) || !($report = $this->ReportManager->getReport($this->post['id']))) {
            $this->redirect($this->base_uri . 'reports_customize');
        }

        $this->ReportManager->deleteReport($report->id);
        $this->flashMessage('message', Language::_('AdminReportsCustomize.!success.deleted', true));
        $this->redirect($this->base_uri . 'reports_customize');
    }

    /**
     * Formats field values to CSV
     *
     * @param array $values A string or array of values
     * @return string A CSV list of each value
     */
    protected function formatValues($values)
    {
        if (is_array($values)) {
            $values = implode(',', $values);
        }

        return $values;
    }
}
