<?php
namespace Blesta\MassMailer\Cron;

use Loader;

/**
 * Performs the CSV Export cron task
 *
 * @package blesta
 * @subpackage blesta.plugins.mass_mailer
 * @copyright Copyright (c) 2016, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Export
{
    /**
     * @var The heading columns
     */
    private $columns;

    /**
     * Init
     */
    public function __construct()
    {
        Loader::loadModels(
            $this,
            [
                'MassMailer.MassMailerJobs',
                'MassMailer.MassMailerExports',
                'MassMailer.MassMailerTasks'
            ]
        );

        // Set default values for the columns
        $this->columns = [
            'contacts' => [],
            'services' => []
        ];
    }

    /**
     * Runs the cron to perform CSV exports
     */
    public function run()
    {
        // Retrieve all export jobs that are in progress or ready to run
        $jobs = $this->MassMailerJobs->getAll('export', ['pending', 'in_progress']);

        // Build the export
        foreach ($jobs as $job) {
            // Mark this job as now in progress
            if ($job->status === 'pending') {
                $this->MassMailerJobs->edit($job->id, ['status' => 'in_progress']);
            }

            // Set the job to use in the export and whether to append to it or overwrite it
            $columns_set = false;
            $headers_set = ($job->status === 'in_progress');
            $this->MassMailerExports->setJob($job->id, ($headers_set ? 'a' : 'w'));

            // Add each task in the job to the export
            while (($task = $this->MassMailerTasks->getByJob($job->id))) {
                // Set the columns for this export. All tasks for the job use them
                if (!$columns_set) {
                    // Determine the columns to set for the export
                    // based on whether there is a service or not
                    $this->MassMailerExports->setColumns(
                        ($task->service_id ? $this->columns['services'] : $this->columns['contacts'])
                    );
                    $columns_set = true;
                }

                // Set the first row headings if not already set
                if (!$headers_set) {
                    $this->MassMailerExports->addHeaders();
                    $headers_set = true;
                }

                // Add the row for this task
                $this->MassMailerExports->addRow($task->id);

                // Delete the task
                $this->MassMailerTasks->delete($task->id);
            }

            // Mark the job complete
            $this->MassMailerJobs->edit($job->id, ['status' => 'complete']);
        }
    }

    /**
     * Sets the name/values of the columns for the export
     *
     * @param stdClass $columns An stdClass object representing the columns
     */
    public function setColumns($columns)
    {
        $this->columns['contacts'] = (isset($columns->contacts) ? (array)$columns->contacts : []);
        $this->columns['services'] = (isset($columns->services) ? (array)$columns->services : []);
    }
}
