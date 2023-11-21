<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Blesta\Core\Automation\Tasks\Common\StaffNoticeTask;
use Language;
use Loader;
use stdClass;

/**
 * The unsuspend_services automation task
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class UnsuspendServices extends StaffNoticeTask
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

        Loader::loadModels($this, ['ClientGroups', 'Services']);
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
        $this->log(Language::_('Automation.task.unsuspendservices.attempt', true));

        // Execute the unsuspend_services cron task
        $this->process($this->task->raw());

        // Log the task has completed
        $this->log(Language::_('Automation.task.unsuspendservices.completed', true));
        $this->logComplete();
    }

    /**
     * Processes the task
     *
     * @param stdClass $data The unsuspend service task data
     */
    private function process(stdClass $data)
    {
        // Get all client groups
        $client_groups = $this->ClientGroups->getAll($data->company_id);

        foreach ($client_groups as $client_group) {
            // Get all services ready to be unsuspended
            $services = $this->Services->getAllPendingUnsuspension($client_group->id);

            // Unsuspend the services
            foreach ($services as $service) {
                $this->Services->unsuspend($service->id, ['use_module' => 'true', 'staff_id' => null]);

                // Log the change
                if (($errors = $this->Services->errors())) {
                    // Send the error email
                    $this->sendServiceUnsuspendError($service, $errors);

                    $this->log(
                        Language::_(
                            'Automation.task.unsuspendservices.error',
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
                            'Automation.task.unsuspendservices.success',
                            true,
                            $service->id_code,
                            $service->client_id_code
                        )
                    );
                }
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
