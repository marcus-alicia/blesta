<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Blesta\Core\Automation\Tasks\Common\StaffNoticeTask;
use Language;
use Loader;
use stdClass;

/**
 * The suspend_services automation task
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SuspendServices extends StaffNoticeTask
{
    /**
     * Initialize a new task
     *
     * @param AutomationTypeInterface $task The raw automation task
     * @param array $options An additional options necessary for the task:
     *
     *  - print_log True to print logged content to stdout, or false otherwise (default false)
     *  - cli True if this is being run via the Command-Line Interface, or false otherwise (default true)
     *  - client_uri The URI of the client interface
     */
    public function __construct(AutomationTypeInterface $task, array $options = [])
    {
        parent::__construct($task, $options);

        Loader::loadModels($this, ['Clients', 'ClientGroups', 'Services']);
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        // This task cannot be run right now
        if (!$this->isTimeToRun()) {
            return;
        }

        // Log the task has begun
        $this->log(Language::_('Automation.task.suspendservices.attempt', true));

        // Execute the suspend_services cron task
        $this->process($this->task->raw());

        // Log the task has completed
        $this->log(Language::_('Automation.task.suspendservices.completed', true));
        $this->logComplete();
    }

    /**
     * Processes the suspend services task
     *
     * @param stdClass $data The suspend service task data
     */
    private function process(stdClass $data)
    {
        // Get all client groups
        $client_groups = $this->ClientGroups->getAll($data->company_id);

        // Determine the end of the day today, suspensions will then cover the entire day-of
        $today = $this->date->modify(
            date('c'),
            'midnight +1 day -1 second',
            'c',
            isset($this->options['timezone']) ? $this->options['timezone'] : 'UTC'
        );

        foreach ($client_groups as $client_group) {
            // Get the service suspension days
            $suspension_days = $this->ClientGroups->getSetting(
                $client_group->id,
                'suspend_services_days_after_due'
            );

            // Skip if we should not do any suspensions on this client group
            if ($suspension_days->value == 'never') {
                continue;
            }

            // Set the date at which services should be suspended if the invoices are past due
            // and encompass the entire day
            $suspension_date = $this->date->modify(
                $today,
                '-' . abs((int)$suspension_days->value) . ' days',
                'c',
                isset($this->options['timezone']) ? $this->options['timezone'] : 'UTC'
            );

            // Get all services ready to be suspended
            $services = $this->Services->getAllPendingSuspension($client_group->id, $suspension_date);

            // Suspend services
            $this->suspendServices($services);
        }
    }

    /**
     * Suspends the given list of services
     *
     * @param array $services A list of services to suspend
     */
    private function suspendServices(array $services)
    {
        $suspendable = [];
        // Suspend the services
        foreach ($services as $service) {
            if (!isset($suspendable[$service->client_id])) {
                $suspendable[$service->client_id] = 'false';
                $autosuspend = $this->Clients->getSetting($service->client_id, 'autosuspend');
                $autosuspend_date = $this->Clients->getSetting($service->client_id, 'autosuspend_date');

                if ($autosuspend) {
                    $suspendable[$service->client_id] = $autosuspend->value;
                }

                if ($suspendable[$service->client_id] == 'true' && $autosuspend_date) {
                    $suspendable[$service->client_id] = strtotime($autosuspend_date->value) < time()
                        ? 'true'
                        : 'false';
                }
            }

            // Do not attempt to suspend services if autosuspend is disabled
            if ($suspendable[$service->client_id] == 'false') {
                continue;
            }

            $this->Services->suspend(
                $service->id,
                [
                    'use_module' => 'true',
                    'staff_id' => null,
                    'suspension_reason' => Language::_('Automation.task.suspendservices.suspension_reason', true)
                ]
            );

            // Log the change
            if (($errors = $this->Services->errors())) {
                // Send the error email
                $this->sendServiceSuspendError($service, $errors);

                $this->log(
                    Language::_(
                        'Automation.task.suspendservices.error',
                        true,
                        $service->id_code,
                        $service->client_id_code
                    )
                );

                // Reset errors
                $this->resetErrors($this->Services);
            } else {
                $this->log(
                    Language::_(
                        'Automation.task.suspendservices.success',
                        true,
                        $service->id_code,
                        $service->client_id_code
                    )
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function isTimeToRun()
    {
        return $this->task->canRun(date('c'));
    }
}
