<?php
/**
 * MassMailerJobs model
 *
 * @package blesta
 * @subpackage blesta.plugins.mass_mailer.models
 * @copyright Copyright (c) 2016, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class MassMailerJobs extends MassMailerModel
{
    /**
     * Creates a new job for the current company
     *
     * @param array $vars An array of input including:
     *  - status The job status (optional, default 'pending')
     *  - task_count The total number of tasks on the job
     *  - data An array of data to save for this job, e.g.
     *      - filters An array of all filter attributes
     * @return mixed The ID of the job created, or void on error
     */
    public function add(array $vars)
    {
        $vars['company_id'] = Configure::get('Blesta.company_id');
        $vars['date_added'] = $this->dateToUtc(date('c'));

        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ['company_id', 'status', 'task_count', 'data', 'date_added'];

            $vars['data'] = serialize($vars['data']);
            $this->Record->insert('mass_mailer_jobs', $vars, $fields);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Updates the job to set the status
     *
     * @param int $job_id The ID of the job to update
     * @param array $vars An array of input including:
     *  - status The job status
     *  - task_count The total number of tasks on the job
     * @return stdClass The updated job
     */
    public function edit($job_id, array $vars)
    {
        // Make all rules optional
        $vars['job_id'] = $job_id;
        $rules = $this->getRules($vars, true);

        // Only allow the status to be updated
        $rules = array_intersect_key($rules, array_flip(['job_id', 'status', 'task_count']));
        // Status, task count are optional
        $rules['status']['format']['if_set'] = true;

        $this->Input->setRules($rules);
        if ($this->Input->validates($vars)) {
            $fields = ['status', 'task_count'];
            $this->Record->where('id', '=', $job_id)
                ->update('mass_mailer_jobs', $vars, $fields);
        }
    }

    /**
     * Retrieves a list of jobs for the current company
     *
     * @param int $page The page of results to fetch (optional, default 1)
     * @return array An array of stdClass objects representing each job
     */
    public function getList($page = 1)
    {
        $jobs = $this->getJobs()
            ->order(['date_added' => 'desc'])
            ->limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())
            ->fetchAll();

        // Unserialize the job data
        foreach ($jobs as $job) {
            $job->data = unserialize($job->data);
        }

        return $jobs;
    }

    /**
     * Retrieves the total list of jobs for the current company
     *
     * @return int The total number of jobs
     */
    public function getListCount()
    {
        return $this->getJobs()->numResults();
    }

    /**
     * Retrieves all jobs for the current company
     *
     * @param string $type The type of job to fetch (i.e. 'email' or 'export', default null for both)
     * @param array $statuses An array of statuses to filter by (optional)
     * @return array An array of stdClass objects representing each job
     */
    public function getAll($type = null, array $statuses = [])
    {
        $this->Record = $this->getJobs();

        // Filter by type
        if ($type) {
            if ($type == 'export') {
                // The job must not have an email
                $this->Record->select(['mass_mailer_emails.job_id'])
                    ->leftJoin(
                        'mass_mailer_emails',
                        'mass_mailer_emails.job_id',
                        '=',
                        'mass_mailer_jobs.id',
                        false
                    )
                    ->where('mass_mailer_emails.job_id', '=', null);
            } else {
                // The job must have an email
                $this->Record->innerJoin(
                    'mass_mailer_emails',
                    'mass_mailer_emails.job_id',
                    '=',
                    'mass_mailer_jobs.id',
                    false
                );
            }
        }

        // Filter by status
        if (!empty($statuses)) {
            $this->Record->where('mass_mailer_jobs.status', 'in', $statuses);
        }

        return $this->Record->fetchAll();
    }

    /**
     * Builds a select query for fetching jobs
     *
     * @return Record A partially-constructed Record object
     */
    private function getJobs()
    {
        $this->Record->select(['mass_mailer_jobs.*'])
            ->from('mass_mailer_jobs')
            ->where('company_id', '=', Configure::get('Blesta.company_id'));

        return $this->Record;
    }

    /**
     * Retrieves a set of statuses
     *
     * @return array An array of statuses and their language
     */
    public function getStatuses()
    {
        return [
            'pending' => $this->_('MassMailerJobs.status.pending'),
            'in_progress' => $this->_('MassMailerJobs.status.in_progress'),
            'complete' => $this->_('MassMailerJobs.status.complete')
        ];
    }

    /**
     * Retrieves a set of rules for validating whether to add a job
     *
     * @param array $vars An array of input
     * @param bool $edit True for the edit rules, false for the add rules
     * @return array A set of rules for Input validation
     */
    private function getRules(array $vars, $edit = false)
    {
        $rules = [
            'company_id' => [
                'valid' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('MassMailerJobs.!error.company_id.valid')
                ]
            ],
            'status' => [
                'format' => [
                    'rule' => ['in_array', array_keys($this->getStatuses())],
                    'message' => $this->_('MassMailerJobs.!error.status.format')
                ]
            ],
            'task_count' => [
                'format' => [
                    'if_set' => true,
                    'rule' => function ($task_count) {
                        // Task count must be a number
                        return preg_match('/^[0-9]+$/', $task_count);
                    },
                    'message' => $this->_('MassMailerJobs.!error.task_count.format')
                ]
            ],
            'data' => [
                'empty' => [
                    'rule' => function ($data) {
                        // Return whether the data is empty
                        return (empty($data));
                    },
                    'negate' => true,
                    'message' => $this->_('MassMailerJobs.!error.data.empty')
                ]
            ],
            'date_added' => [
                'format' => [
                    'rule' => ['isDate'],
                    'message' => $this->_('MassMailerJobs.!error.date_added.format')
                ]
            ]
        ];

        if ($edit) {
            $rules['job_id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'mass_mailer_jobs'],
                    'message' => $this->_('MassMailerJobs.!error.job_id.exists')
                ]
            ];
        }

        return $rules;
    }
}
