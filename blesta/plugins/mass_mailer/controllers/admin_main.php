<?php
/**
 * Mass Mailer Admin Main controller
 *
 * @package blesta
 * @subpackage blesta.plugins.mass_mailer.controllers
 * @copyright Copyright (c) 2016, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminMain extends MassMailerController
{
    /**
     * Setup page
     */
    public function preAction()
    {
        parent::preAction();

        $this->structure->set('page_title', Language::_('AdminMain.index.page_title', true));
    }

    /**
     * List mailing jobs
     */
    public function index()
    {
        $this->uses(
            [
                'MassMailer.MassMailerJobs',
                'MassMailer.MassMailerEmails',
                'MassMailer.MassMailerTasks'
            ]
        );

        $page = (isset($this->get[0]) ? (int)$this->get[0] : 1);

        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $this->MassMailerJobs->getListCount(),
                'uri' => $this->base_uri . 'plugin/mass_mailer/admin_main/index/[p]/'
            ]
        );
        $this->setPagination($this->get, $settings);

        // Set the job's email to it, if any
        $jobs = $this->MassMailerJobs->getList($page);
        foreach ($jobs as $job) {
            $job->email = $this->MassMailerEmails->getByJob($job->id);
            $job->total_tasks = $this->MassMailerTasks->getCountByJob($job->id);

            // Convert the HTML to text if no text exists for the email
            if ($job->email) {
                $job->email->text = $this->MassMailerEmails->htmlToText(
                    $job->email->html,
                    $job->email->text
                );
            }
        }

        $this->set('page', $page);
        $this->set('jobs', $jobs);
        $this->set('statuses', $this->MassMailerJobs->getStatuses());

        // Set the page refresh timer on non-AJAX pages
        if (!$this->isAjax()) {
            $this->set('set_timer', true);
        }

        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]));
    }

    /**
     * Download the CSV export
     */
    public function download()
    {
        // Need the job ID
        if (!isset($this->get[0])) {
            $this->redirect($this->base_uri . 'plugin/mass_mailer/admin_main/');
        }

        $this->uses(['MassMailer.MassMailerExports']);

        $this->MassMailerExports->setJob((int)$this->get[0]);
        $filename = $this->MassMailerExports->getFilename();
        $file_path = $this->MassMailerExports->getUploadDirectory() . $filename;

        if (file_exists($file_path)) {
            $this->components(['Download']);
            $this->Download->downloadFile($file_path, $filename);
            return false;
        }

        $this->flashMessage(
            'error',
            Language::_('AdminMain.!error.export_found', true),
            null,
            false
        );
        $this->redirect($this->base_uri . 'plugin/mass_mailer/admin_main/');
    }
}
