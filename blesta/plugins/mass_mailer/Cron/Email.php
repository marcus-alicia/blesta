<?php
namespace Blesta\MassMailer\Cron;

use Loader;

/**
 * Sends email
 *
 * @package blesta
 * @subpackage blesta.plugins.mass_mailer
 * @copyright Copyright (c) 2016, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Email
{
    /**
     * Init
     */
    public function __construct()
    {
        Loader::loadModels(
            $this,
            [
                'MassMailer.MassMailerJobs',
                'MassMailer.MassMailerEmails',
                'MassMailer.MassMailerTasks'
            ]
        );
    }

    /**
     * Sends mass emails for all jobs
     */
    public function run()
    {
        // Retrieve all email jobs that are in progress or ready to run
        $jobs = $this->MassMailerJobs->getAll('email', ['pending', 'in_progress']);

        // Build the export
        foreach ($jobs as $job) {
            // The job must have an email to send, otherwise there is nothing
            // we can do but mark it complete
            if (!($email = $this->MassMailerEmails->getByJob($job->id))) {
                $this->completeJob($job->id);
                continue;
            }

            // Mark this job as now in progress
            if ($job->status === 'pending') {
                $this->MassMailerJobs->edit($job->id, ['status' => 'in_progress']);
            }

            // Send an email for each task in the job
            while (($task = $this->MassMailerTasks->getByJob($job->id))) {
                // Send the email
                $this->MassMailerEmails->send($task, $email);

                // Delete the task
                $this->MassMailerTasks->delete($task->id);
            }

            $this->completeJob($job->id);
        }
    }

    /**
     * Marks the given job complete
     *
     * @param int $job_id The ID of the jbo
     */
    private function completeJob($job_id)
    {
        // Mark the job complete
        $this->MassMailerJobs->edit($job_id, ['status' => 'complete']);
    }
}
