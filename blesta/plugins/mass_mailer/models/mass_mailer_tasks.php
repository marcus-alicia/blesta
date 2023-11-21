<?php

use Blesta\MassMailer\Traits\Filters;

/**
 * MassMailerTasks model
 *
 * @package blesta
 * @subpackage blesta.plugins.mass_mailer.models
 * @copyright Copyright (c) 2016, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class MassMailerTasks extends MassMailerModel
{
    use Filters;

    /**
     * Init
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Adds tasks for the given job that match the contact filters given.
     * Sets errors on failure
     *
     * @param int $job_id The job ID to assign the tasks to
     * @param array $filters An array of filtering options
     */
    public function add($job_id, array $filters)
    {
        $vars = ['job_id' => $job_id];
        $this->Input->setRules($this->getRules());

        if ($this->Input->validates($vars)) {
            // Set the filters
            $this->setFilters($filters);

            // Set default query filter options
            $this->setClientFields(
                ["'" . (int)$vars['job_id'] . "'" => 'job_id', 'contacts.id'],
                false
            );
            $this->setServiceFields(['services.id' => 'service_id']);
            // Determine the fields to group on
            $group_by = ($this->filteringServices(true)
                ? ['services.id', 'contacts.id']
                : ['contacts.id']
            );
            $this->setGroupBy($group_by);

            // Build the query that filters for matching contacts
            $Record = clone $this->Record;
            $Record = $this->filter($Record);

            // Fetch only the values for the query
            $temp = clone $Record;
            $temp->get();
            $values = $temp->values;
            unset($temp);

            // Add the tasks
            $this->Record->query(
                'INSERT INTO `mass_mailer_tasks` ' . $this->buildInsertFields()
                . ' (' . $Record->get() . ');',
                $values
            );
        }
    }

    /**
     * Builds a set of field names for inserting into `mass_mailer_tasks`
     * Also sets the fields to group by
     *
     * @return string The fields to include when inserting into `mass_mailer_tasks`
     */
    private function buildInsertFields()
    {
        $insert_fields = '(`job_id`, `contact_id`';

        // Include a service_id column if filtering on services
        if ($this->filteringServices()) {
            $insert_fields .= ', `service_id`';
        }

        return $insert_fields . ')';
    }

    /**
     * Retrieves a mailing task
     *
     * @param int $task_id The ID of the task to fetch
     * @return mixed An stdClass object representing the task, otherwise false
     */
    public function get($task_id)
    {
        return $this->Record->select()
            ->from('mass_mailer_tasks')
            ->where('id', '=', $task_id)
            ->fetch();
    }

    /**
     * Retrieves a single task for the given job
     *
     * @param int $job_id The ID of the job
     * @return mixed An stdClass object representing the task, otherwise false
     */
    public function getByJob($job_id)
    {
        return $this->getTasks($job_id)->fetch();
    }

    /**
     * Retrieves the total number of tasks for the given job
     *
     * @param int $job_id The ID of the job
     * @return int The total number of tasks currently pending for the job
     */
    public function getCountByJob($job_id)
    {
        return $this->getTasks($job_id)->numResults();
    }

    /**
     * Deletes the mailing task
     *
     * @param int $task_id The ID of the task to delete
     */
    public function delete($task_id)
    {
        $this->Record->from('mass_mailer_tasks')
            ->where('id', '=', $task_id)
            ->delete();
    }

    /**
     * Retrieves a partially-constructed Record object for fetching tasks
     *
     * @param int $job_id The ID of the job whose tasks to fetch
     * @return Record
     */
    private function getTasks($job_id)
    {
        return $this->Record->select()
            ->from('mass_mailer_tasks')
            ->where('job_id', '=', $job_id);
    }

    /**
     * Retrieves the validation rules for adding tasks
     *
     * @return array An array of Input validation rules
     */
    private function getRules()
    {
        return [
            'job_id' => [
                'valid' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'mass_mailer_jobs'],
                    'message' => $this->_('MassMailerTasks.!error.job_id.valid')
                ]
            ]
        ];
    }
}
