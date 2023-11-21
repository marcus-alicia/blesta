<?php
/**
 * Mass Mailer parent controller
 *
 * @package blesta
 * @subpackage blesta.plugins.mass_mailer
 * @copyright Copyright (c) 2016, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class MassMailerController extends AppController
{
    /**
     * @var The mass mailer session key
     */
    private $mass_mailer_session_key = 'mass_mailer_options';

    /**
     * Require admin to be login and setup the view
     */
    public function preAction()
    {
        $this->structure->setDefaultView(APPDIR);

        parent::preAction();

        $this->requireLogin();

        // Auto load language for the controller
        Language::loadLang(
            [Loader::fromCamelCase(get_class($this))],
            null,
            dirname(__FILE__) . DS . 'language' . DS
        );
        Language::loadLang(
            'mass_mailer_controller',
            null,
            dirname(__FILE__) . DS . 'language' . DS
        );

        // Override default view directory
        $this->view->view = "default";
        $this->orig_structure_view = $this->structure->view;
        $this->structure->view = "default";

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);
    }

    /**
     * Retrieves the logged-in staff user
     *
     * @return stdClass An stdClass object representing the staff member
     */
    protected function getStaff()
    {
        $this->uses(['Staff']);
        return $this->Staff->get($this->Session->read('blesta_staff_id'));
    }

    /**
     * Makes the date picker available to the view
     */
    protected function setDatePicker()
    {
        $this->components(['SettingsCollection']);
        $calendar = $this->SettingsCollection->fetchSetting(null, $this->company_id, 'calendar_begins');
        $first_day = (isset($calendar['value']) && $calendar['value'] == 'sunday'
            ? 0
            : 1
        );

        $this->Javascript->setFile('date.min.js');
        $this->Javascript->setFile('jquery.datePicker.min.js');
        $this->Javascript->setInline(
            'Date.firstDayOfWeek=' . $first_day . ';'
        );
    }

    /**
     * Makes the WYSIWYG available to the view
     */
    protected function setWysiwyg()
    {
        // Include WYSIWYG
        $this->Javascript->setFile('blesta/ckeditor/build/ckeditor.js', 'head', VENDORWEBDIR);
    }

    /**
     * Writes mass mailer data to session
     *
     * @param string $key The name of the key associated with the value
     * @param string $value The value
     */
    protected function write($key, $value)
    {
        // Fetch the current session data
        $data = [];
        $session = $this->Session->read($this->mass_mailer_session_key);
        if (!empty($session)) {
            $data = (array)$session;
        }

        // Merge the given data to the session
        $this->Session->write(
            $this->mass_mailer_session_key,
            array_merge($data, [$key => $value])
        );
    }

    /**
     * Reads mass mailer data from the session
     *
     * @return mixed The session data stored
     */
    protected function read()
    {
        return $this->Session->read($this->mass_mailer_session_key);
    }

    /**
     * Deletes the session data stored for the mass mailer
     */
    protected function clear()
    {
        $this->Session->clear($this->mass_mailer_session_key);
    }

    /**
     * Creates a job, its email, and tasks from session data
     * Sets success/error messages. Redirects on success.
     *
     * @param string $type The type of job to add, 'export' or 'email' (default 'email')
     */
    protected function addJob($type = 'email')
    {
        // Require filters be set
        $session = $this->read();
        if (!is_array($session) || !array_key_exists('filters', $session)) {
            return;
        }

        $this->uses(
            ['MassMailer.MassMailerJobs', 'MassMailer.MassMailerEmails', 'MassMailer.MassMailerTasks']
        );

        // Start a transaction
        $this->MassMailerJobs->begin();

        // Create the job
        $vars = [
            'status' => 'pending',
            'data' => ['filters' => $session['filters']]
        ];
        $job_id = $this->MassMailerJobs->add($vars);

        if (($errors = $this->MassMailerJobs->errors())) {
            $this->MassMailerJobs->rollBack();
            $this->setMessage('error', $errors, false, null, false);
            return;
        }

        // Create the email
        if ($type === 'email' && array_key_exists('email', $session)) {
            $this->MassMailerEmails->add($job_id, $session['email']);

            if (($errors = $this->MassMailerEmails->errors())) {
                $this->MassMailerJobs->rollBack();
                $this->setMessage('error', $errors, false, null, false);
                return;
            }
        }

        // Create the tasks
        $this->MassMailerTasks->add($job_id, $session['filters']);
        if (($errors = $this->MassMailerTasks->errors())) {
            $this->MassMailerJobs->rollBack();
            $this->setMessage('error', $errors, false, null, false);
            return;
        }

        // Update the total number of tasks set for the job
        $total_tasks = $this->MassMailerTasks->getCountByJob($job_id);
        $this->MassMailerJobs->edit($job_id, ['task_count' => $total_tasks]);

        // Successfully created the job and email
        $this->MassMailerJobs->commit();

        // Clear the session
        $this->clear();

        $message = ($type === 'email'
            ? Language::_('MassMailerController.!success.mail_job_added', true)
            : Language::_('MassMailerController.!success.export_job_added', true)
        );
        $this->flashMessage('message', $message, null, false);
        $this->redirect($this->base_uri . 'plugin/mass_mailer/admin_main/');
    }
}
