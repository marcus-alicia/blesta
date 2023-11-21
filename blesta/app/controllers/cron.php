<?php

use Blesta\Core\Automation\TaskFactory;

/**
 * The Cron controller. Handles all automated tasks that run via a cron job or
 * scheduled task.
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Cron extends AppController
{
    /**
     * @var array A list of cron task settings for the current company
     */
    private $cron_tasks = [];
    /**
     * @var string The passphrase used to process batch payments
     */
    private $passphrase = null;
    /**
     * @var TaskFactory An instance of the TaskFactory
     */
    private $task_factory;
    /**
     * @var array An array of task options
     */
    private $task_options = [];

    /**
     * Pre-action
     */
    public function preAction()
    {
        // Set a specific company
        if (!empty($this->get['company_id'])) {
            $this->uses(['Companies']);
            $company = $this->Companies->get((int)$this->get['company_id']);
        }

        // Set the default company if one was not provided
        if (empty($company)) {
            $company = $this->getCompany();
        }

        $this->primeCompany($company);

        // Set URLs
        $this->base_url = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '')
            . '://' . $company->hostname . '/';

        $this->uses(['CronTasks', 'Logs']);
        $this->components(['SettingsCollection']);
        Language::loadLang('cron');

        // If not being executed via command line, require a key
        if (!$this->is_cli) {
            $cron_key = $this->SettingsCollection->fetchSystemSetting(null, 'cron_key');

            if (!isset($this->get['cron_key']) || $cron_key['value'] != $this->get['cron_key']) {
                $this->redirect();
            }
        }

        // Set passphrase if given
        if (isset($this->get['passphrase'])) {
            $this->passphrase = $this->get['passphrase'];
        }

        // Override the memory limit, if given
        if (Configure::get('Blesta.cron_memory_limit')) {
            ini_set('memory_limit', Configure::get('Blesta.cron_memory_limit'));
        }

        // Load the task factory to run the automation tasks
        $this->task_factory = $this->taskFactory();

        // Set default task options
        $this->task_options = [
            'print_log' => true,
            'cli' => $this->is_cli
        ];
    }

    /**
     * Runs the cron
     */
    public function index()
    {
        // Run all tasks for all companies
        $companies = $this->Companies->getAll();
        $i = 0;
        $run_id = 0;
        $event = '';
        foreach ($companies as $company) {
            // Setup the company
            $this->primeCompany($company);

            // Set URLs
            $this->base_url = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '')
                . '://' . $company->hostname . '/';

            // Load the language specific to this company
            $company_default_language = $this->SettingsCollection->fetchSetting(
                $this->Companies,
                $this->company_id,
                'language'
            );
            if (isset($company_default_language['value'])) {
                $company_default_language = $company_default_language['value'];
            }
            Language::loadLang('cron', $company_default_language);

            // Remove the saved cron tasks and set the next company's
            $this->cron_tasks = [];
            // Save cron tasks for the current company, re-indexed by task type, key, and dir
            $cron_tasks = $this->CronTasks->getAllTaskRun();
            foreach ($cron_tasks as $cron_task) {
                // Categorize the tasks by task type
                if (!isset($this->cron_tasks[$cron_task->task_type])) {
                    $this->cron_tasks[$cron_task->task_type] = [];
                }

                $key = $cron_task->key . $cron_task->dir;
                $this->cron_tasks[$cron_task->task_type][$key] = $this->appendCronTaskLastRun($cron_task);
            }

            // Log this task has started
            $output = $this->setOutput(Language::_('Cron.index.attempt_all', true, $company->name));
            $cron_log_group = $this->createCronLogGroup();

            if ((
                $errors = $this->logTaskStarted(
                    $run_id,
                    $event,
                    $cron_log_group,
                    $this->Date->format('c'),
                    $output,
                    Loader::fromCamelCase(get_class($this)) . '_' . Loader::fromCamelCase(__FUNCTION__)
                )
            )) {
                // Error, cron could not be logged (this should never happen)
                echo Language::_('Cron.!error.cron.failed', true);
            }

            // Run through all tasks
            $this->all($cron_log_group);

            // Log this task has completed
            $output = $this->setOutput(Language::_('Cron.index.completed_all', true), $output);

            if ((
                $errors = $this->logTaskCompleted(
                    $run_id,
                    $event,
                    $cron_log_group,
                    $this->Date->format('c'),
                    $output
                )
            )) {
                // Error, cron could not be logged (this should never happen)
                echo Language::_('Cron.!error.cron.failed', true);
            }
        }

        // Remove data no longer needed
        unset($companies, $company, $this->cron_tasks);

        // Run all system tasks
        $this->allSystem();

        return false;
    }

    /**
     * Run all cron tasks
     *
     * @param string $cron_log_group The name of the group associated with the log (optional)
     * @return false
     */
    public function all($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // List of cron tasks in the order they will be run
        $tasks = ['createInvoices', 'applyInvoiceLateFees', 'applyCredits', 'autoDebitInvoices',
            'cardExpirationReminders', 'deliverInvoices', 'deliverReports', 'addPaidPendingServices',
            'suspendServices', 'unsuspendServices', 'cancelScheduledServices', 'processServiceChanges',
            'processRenewingServices', 'paymentReminders', 'updateExchangeRates', 'transitionQuotations',
            'pluginTasks', 'moduleTasks', 'cleanLogs'
        ];

        // Run each cron tasks
        for ($i = 0, $num_tasks = count($tasks); $i < $num_tasks; $i++) {
            try {
                call_user_func_array([$this, $tasks[$i]], [$cron_log_group]);
            } catch (Throwable $e) {
                // Error running cron task
                echo Language::_('Cron.!error.task_execution.failed', true, $e->getMessage(), $e->getTraceAsString());
            }
        }

        return false;
    }

    /**
     * Runs all system-level cron tasks
     */
    private function allSystem()
    {
        // Run all system tasks
        $run_id = 0;
        $event = '';

        // Log this task has started
        $output = $this->setOutput(Language::_('Cron.index.attempt_all_system', true));
        $cron_log_group = $this->createCronLogGroup();

        if ((
            $errors = $this->logTaskStarted(
                $run_id,
                $event,
                $cron_log_group,
                $this->Date->format('c'),
                $output,
                Loader::fromCamelCase(get_class($this)) . '_' . Loader::fromCamelCase(__FUNCTION__)
            )
        )) {
            // Error, cron could not be logged (this should never happen)
            echo Language::_('Cron.!error.cron.failed', true);
        }

        // List of cron tasks in the order they will be run
        $tasks = ['license', 'amazonS3Backup', 'sftpBackup'];

        // Run each cron tasks
        for ($i = 0, $num_tasks = count($tasks); $i < $num_tasks; $i++) {
            try {
                call_user_func_array([$this, $tasks[$i]], [$cron_log_group]);
            } catch (Throwable $e) {
                // Error running cron task
                echo Language::_('Cron.!error.task_execution.failed', true, $e->getMessage(), $e->getTraceAsString());
            }
        }

        // Log this task has completed
        $output = $this->setOutput(Language::_('Cron.index.completed_all_system', true), $output);

        if (($errors = $this->logTaskCompleted($run_id, $event, $cron_log_group, $this->Date->format('c'), $output))) {
            // Error, cron could not be logged (this should never happen)
            echo Language::_('Cron.!error.cron.failed', true);
        }
    }

    /**
     * Runs the create invoice task
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    public function createInvoices($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('create_invoice');

        // Run the create_invoices cron task
        $task = $this->task_factory->createInvoices(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            $this->task_options
        );
        $task->run();

        return false;
    }

    /**
     * Runs the apply invoice late fees task
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    public function applyInvoiceLateFees($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('apply_invoice_late_fees');

        // Run the create_invoices cron task
        $task = $this->task_factory->applyInvoiceLateFees(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            $this->task_options
        );
        $task->run();

        return false;
    }

    /**
     * Runs the apply credits task
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    public function applyCredits($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('apply_payments');

        // Run the apply payments cron task
        $task = $this->task_factory->applyPayments(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            $this->task_options
        );
        $task->run();

        return false;
    }

    /**
     * Runs the autodebit invoices task
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    public function autoDebitInvoices($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('autodebit');

        // Run the apply payments cron task
        $task = $this->task_factory->autodebit(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            array_merge($this->task_options, ['passphrase' => $this->passphrase])
        );
        $task->run();

        return false;
    }

    /**
     * Runs the payment reminders task
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    public function paymentReminders($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('payment_reminders');

        // Run the payment_reminders cron task
        $task = $this->task_factory->paymentReminders(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            array_merge(
                $this->task_options,
                ['client_uri' => $this->client_uri, 'timezone' => Configure::get('Blesta.company_timezone')]
            )
        );
        $task->run();

        return false;
    }

    /**
     * Runs the card expiration reminder task
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    public function cardExpirationReminders($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('card_expiration_reminders');

        // Run the apply payments cron task
        $task = $this->task_factory->cardExpirationReminders(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            array_merge($this->task_options, ['client_uri' => $this->client_uri])
        );
        $task->run();

        return false;
    }

    /**
     * Runs the deliver invoices task
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    public function deliverInvoices($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('deliver_invoices');

        // Run the deliver_invoices cron task
        $task = $this->task_factory->deliverInvoices(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            array_merge($this->task_options, ['client_uri' => $this->client_uri])
        );
        $task->run();

        return false;
    }

    /**
     * Runs the deliver reports task
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    public function deliverReports($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('deliver_reports');

        // Get the company timezone
        $timezone = Configure::get('Blesta.company_timezone');

        // Run the deliver reports cron task
        $task = $this->task_factory->deliverReports(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            array_merge($this->task_options, ['timezone' => $timezone])
        );
        $task->run();

        return false;
    }

    /**
     * Runs the process service changes task
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    public function processServiceChanges($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('process_service_changes');

        // Get the company timezone
        $timezone = Configure::get('Blesta.company_timezone');

        // Run the process service changes cron task
        $task = $this->task_factory->processServiceChanges(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            array_merge($this->task_options, ['timezone' => $timezone])
        );
        $task->run();

        return false;
    }

    /**
     * Runs the process renewing services task
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    public function processRenewingServices($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('process_renewing_services');

        // Run the process renewing services cron task
        $task = $this->task_factory->processRenewingServices(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            $this->task_options
        );
        $task->run();

        return false;
    }

    /**
     * Runs the suspend services task
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    public function suspendServices($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('suspend_services');

        // Get the company timezone
        $timezone = Configure::get('Blesta.company_timezone');

        // Run the suspend services cron task
        $task = $this->task_factory->suspendServices(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            array_merge($this->task_options, ['timezone' => $timezone])
        );
        $task->run();

        return false;
    }

    /**
     * Runs the unsuspend services task
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    public function unsuspendServices($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('unsuspend_services');

        // Run the unsuspend services cron task
        $task = $this->task_factory->unsuspendServices(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            $this->task_options
        );
        $task->run();

        return false;
    }

    /**
     * Runs the cancel scheduled services task
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    public function cancelScheduledServices($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('cancel_scheduled_services');

        // Run the cancel scheduled services cron task
        $task = $this->task_factory->cancelScheduledServices(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            $this->task_options
        );
        $task->run();

        return false;
    }

    /**
     * Runs the add paid pending services task
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    public function addPaidPendingServices($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('provision_pending_services');

        // Get the company timezone
        $timezone = Configure::get('Blesta.company_timezone');

        // Run the provision pending services cron task
        $task = $this->task_factory->provisionPendingServices(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            array_merge($this->task_options, ['timezone' => $timezone])
        );
        $task->run();

        return false;
    }

    /**
     * Runs the update exchange rates task
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    public function updateExchangeRates($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('exchange_rates');

        // Run the exchange rates cron task
        $task = $this->task_factory->exchangeRates(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            $this->task_options
        );
        $task->run();

        return false;
    }

    /**
     * Runs the transition quotations task
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    public function transitionQuotations($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('transition_quotations');

        // Run the exchange rates cron task
        $task = $this->task_factory->transitionQuotations(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            $this->task_options
        );
        $task->run();

        return false;
    }

    /**
     * Runs all plugin tasks
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     * @return bool false to not render a view
     */
    public function pluginTasks($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // No plugin tasks to be run
        if (empty($this->cron_tasks['plugin'])) {
            return false;
        }

        // Run all plugin tasks
        foreach ($this->cron_tasks['plugin'] as $cron_task) {
            if (!empty($cron_task->plugin)) {
                $task = $this->task_factory->plugin(
                    $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
                    $this->task_options
                );
                $task->run();
            }
        }

        return false;
    }

    /**
     * Runs all module tasks
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     * @return bool false to not render a view
     */
    public function moduleTasks($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // No module tasks to be run
        if (empty($this->cron_tasks['module'])) {
            return false;
        }

        // Run all module tasks
        foreach ($this->cron_tasks['module'] as $cron_task) {
            if (!empty($cron_task->module)) {
                $task = $this->task_factory->module(
                    $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
                    $this->task_options
                );
                $task->run();
            }
        }

        return false;
    }

    /**
     * Runs the clean-up logs task
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    public function cleanLogs($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('cleanup_logs');

        // Run the clean-up logs cron task
        $task = $this->task_factory->cleanupLogs(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            $this->task_options
        );
        $task->run();

        return false;
    }

    /**
     * Runs the SFTP database backup task (system)
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    private function sftpBackup($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('backups_sftp', null, true);

        // Run the sftp backup cron task
        $task = $this->task_factory->backupsSftp(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            $this->task_options
        );
        $task->run();

        return false;
    }

    /**
     * Runs the AmazonS3 database backup task (system)
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    private function amazonS3Backup($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('backups_amazons3', null, true);

        // Run the amazon s3 backup cron task
        $task = $this->task_factory->backupsAmazons3(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            $this->task_options
        );
        $task->run();

        return false;
    }

    /**
     * Runs the system task to call home and validate the license
     *
     * @param string $cron_log_group The cron log group that this event is apart of (optional, default null)
     */
    private function license($cron_log_group = null)
    {
        // Create a cron log group if none given
        if (!$cron_log_group) {
            $cron_log_group = $this->createCronLogGroup();
        }

        // Get this cron task
        $cron_task = $this->getCronTask('license_validation', null, true);

        // Run the license validation cron task
        $task = $this->task_factory->licenseValidation(
            $this->task_factory->cronTask($cron_task, ['log_group' => $cron_log_group]),
            $this->task_options
        );
        $task->run();

        return false;
    }

    /**
     * Determines whether a cron task is enabled and if it is time for the task to run or not
     *
     * @param stdClass $cron_task An stdClass object representing the cron task
     * @param bool $system True if the task is a system-level task, false if it is
     *  company-level (optional, default false)
     * @return bool True if this cron task can be run, false otherwise
     */
    private function isTimeToRun($cron_task, $system = false)
    {
        if ($cron_task && $cron_task->enabled == '1') {
            // Get the last time this task was run
            $last_run = $this->Logs->getCronLastRun($cron_task->key, $cron_task->dir, $system, $cron_task->task_type);

            // Check if the task is currently running
            $is_running = ($last_run && $last_run->start_date != null && $last_run->end_date == null);

            // If the current task is running, check if its safe to start a new process
            if ($is_running) {
                $safe_before_timestamp = $this->Date->toTime($this->Date->modify(
                    date('c'),
                    '-' . (int)abs(Configure::get('Blesta.cron_task_restart_limit')) .' minutes',
                    'c',
                    Configure::get('Blesta.company_timezone')
                ));

                return ($this->Date->toTime($last_run->start_date . 'Z') < $safe_before_timestamp);
            }

            // Handle time
            if ($cron_task->type == 'time') {
                // The current date rounded down to the nearest 5 minute interval in local time
                $rounding_interval = 60 * (Configure::get('Blesta.cron_minimum_run_interval')
                    ? (int)abs(Configure::get('Blesta.cron_minimum_run_interval'))
                    : 5);
                $rounded_time = $this->Date->format(
                    'H:i:s',
                    date('H:i:s', floor($this->Date->toTime(date('c')) / $rounding_interval) * $rounding_interval)
                );
                $cron_task_time = $this->Date->format('H:i:s', $cron_task->time);

                // Convert last run time to local timezone
                if ($last_run) {
                    $last_run_date = (int)preg_replace(
                        '/[^0-9]/',
                        '',
                        $this->Date->format('Y-m-d', $last_run->end_date)
                    );
                    $day_after_last_run_date = (int)preg_replace(
                        '/[^0-9]/',
                        '',
                        $this->Date->modify(
                            $last_run->end_date,
                            '+1 day',
                            'Y-m-d',
                            Configure::get('Blesta.company_timezone')
                        )
                    );
                    $current_date = (int)preg_replace('/[^0-9]/', '', $this->Date->format('Y-m-d'));

                    // If task has not already run today and the interval has lapsed, allow the task to run
                    return (($current_date > $day_after_last_run_date)
                        || ($current_date > $last_run_date && $rounded_time >= $cron_task_time));
                }

                // Task has never run, just ensure the interval has lapsed
                return ($rounded_time >= $cron_task_time);
            } elseif ($cron_task->type == 'interval') {
                // Handle interval
                // If never run, allow
                if (!$last_run) {
                    return true;
                }

                $rounding_interval = 60 * (Configure::get('Blesta.cron_minimum_run_interval')
                    ? (int)abs(Configure::get('Blesta.cron_minimum_run_interval'))
                    : 5);

                // The last run date rounded down to the nearest 5 minute interval
                $last_run_date = date(
                    'c',
                    floor($this->Date->toTime($last_run->start_date) / abs($rounding_interval))
                        * abs($rounding_interval)
                );

                // Ensure enough time has lapsed since the last run
                $next_run_date = $this->Date->toTime($this->Date->modify(
                    $last_run_date,
                    '+' . $cron_task->interval . ' minutes',
                    'c',
                    Configure::get('Blesta.company_timezone')
                ));

                return ($this->Date->toTime(date('c')) >= $next_run_date);
            }
        }
        return false;
    }

    /**
     * Determines whether today is the day of the month given
     *
     * @param int $day The day of the month to check (i.e. in range [1,31])
     * @return bool True if today is the current day given, false otherwise
     */
    private function isCurrentDay($day)
    {
        return ($day == $this->Date->cast('c', 'j'));
    }

    /**
     * Logs to the cron that a task has started
     *
     * @param int $run_id The run ID of this event
     * @param string $event The event to log
     * @param string $cron_log_group The cron log group that this event is apart of
     * @param string $start_date The start date of the task in Y-m-d H:i:s format
     * @param string $output The output from running the task (optional)
     * @param string $key The key of the cron task (optional)
     * @return mixed An array of errors, or false if there are no errors
     */
    private function logTaskStarted($run_id, $event, $cron_log_group, $start_date, $output = null, $key = null)
    {
        $cron_event_log = [
            'run_id' => $run_id,
            'event' => $event,
            'group' => $cron_log_group,
            'start_date' => $start_date,
            'output' => $output,
            'key' => $key
        ];

        // Log the cron event
        $this->Logs->addCron($cron_event_log);
        return $this->Logs->errors();
    }

    /**
     * Logs to the cron that a current task has been completed
     *
     * @param int $run_id The run ID of this event
     * @param string $event The event to log
     * @param string $cron_log_group The cron log group that this event is apart of
     * @param string $end_date The start date of the task in Y-m-d H:i:s format
     * @param string $output The output from running the task
     * @param string $key The key of the cron task (optional)
     * @return mixed An array of errors, or false if there are no errors
     */
    private function logTaskCompleted($run_id, $event, $cron_log_group, $end_date, $output, $key = null)
    {
        $cron_event_log = [
            'output' => $output,
            'end_date' => $end_date
        ];

        if (!is_null($key)) {
            $cron_event_log['key'] = $key;
        }

        // Update the cron event
        $this->Logs->updateCron($run_id, $cron_log_group, $event, $cron_event_log);
        return $this->Logs->errors();
    }

    /**
     * Creates a cron log group
     *
     * @return string The cron log group
     */
    private function createCronLogGroup()
    {
        return microtime(true);
    }

    /**
     * Retrieves a cron task
     *
     * @param string $cron_task_key The cron task key of the cron task to get
     * @param string $dir The directory this cron task is associated with
     * @param bool $system True if the task is a system-level task, false if it is
     *  company-level (optional, default false)
     * @param string $task_type The type of cron task this is (i.e. 'system', 'module', or 'plugin', default 'system')
     * @return mixed An stdClass representing the cron task, or false if none exist
     */
    private function getCronTask($cron_task_key, $dir = null, $system = false, $task_type = 'system')
    {
            $cron_task = $this->appendCronTaskLastRun(
                $this->CronTasks->getTaskRunByKey($cron_task_key, $dir, $system, $task_type)
            );

        return $cron_task;
    }

    /**
     * Updates the given $cron_task to append the last run dates
     *
     * @param stdClass $cron_task The cron task
     * @return stdClass The cron task appended with:
     *
     *  - date_last_started The last run date started
     *  - date_last_completed The last run date ended
     */
    private function appendCronTaskLastRun($cron_task)
    {
        // Determine the last time the cron task ran
        if ($cron_task) {
            $system = ($cron_task->company_id == 0);

            // Set the last run date, but also append Zulu time to indicate the time is in UTC
            $lastRun = $this->Logs->getCronLastRun($cron_task->key, $cron_task->dir, $system, $cron_task->task_type);
            $cron_task->date_last_started = ($lastRun && isset($lastRun->start_date)
                ? $lastRun->start_date . 'Z'
                : null
            );
            $cron_task->date_last_completed = ($lastRun && isset($lastRun->end_date)
                ? $lastRun->end_date . 'Z'
                : null
            );
        }

        return $cron_task;
    }

    /**
     * Sets output data
     *
     * @param string $new The new output data
     * @param string $old The old output data, if any (optional, default "")
     * @param bool $echo True to output the new text
     * @return string A concatenation of the old and new output
     */
    private function setOutput($new, $old = '', $echo = true)
    {
        if ($echo) {
            echo $new . ($this->is_cli ? "\n" : '<br />');
            if (@ob_get_contents()) {
                @ob_flush();
            }
            @flush();
            if (@ob_get_contents()) {
                @ob_end_flush();
            }
        }
        return $this->Html->concat(' ', $old, $new);
    }

    /**
     * Reset Input errors on a given object
     *
     * @param object $object An instance of an object containing the Input component
     */
    private function resetErrors($object)
    {
        if (is_object($object) && is_object($object->Input) && $object->Input instanceof Input) {
            $object->Input->setErrors([]);
        }
    }

    /**
     * Create an instance of the TaskFactory
     *
     * @return TaskFactory The task factory
     */
    private function taskFactory()
    {
        return new TaskFactory($this->Date);
    }
}
