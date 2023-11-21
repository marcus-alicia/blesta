<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Tasks\Common\StaffNoticeTask;
use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Language;
use Loader;

/**
 * The cancel_scheduled_services automation task
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class CancelScheduledServices extends StaffNoticeTask
{
    /**
     * {@inheritdoc}
     */
    public function __construct(AutomationTypeInterface $task, array $options = [])
    {
        parent::__construct($task, $options);

        Loader::loadModels($this, ['Services']);
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
        $this->log(Language::_('Automation.task.cancel_scheduled_services.attempt', true));

        // Execute the plugin cron task
        $this->process();

        // Log the task has completed
        $this->log(Language::_('Automation.task.cancel_scheduled_services.completed', true));
        $this->logComplete();
    }

    /**
     * Processes the task
     */
    private function process()
    {
        // Get services pending cancelation
        $services = $this->Services->getAllPendingCancelation();

        // Cancel each service
        foreach ($services as $service) {
            $this->Services->cancel(
                $service->id,
                ['date_canceled' => $this->date->format('Y-m-d H:i:s', $service->date_canceled . 'Z')]
            );

            if (($errors = $this->Services->errors())) {
                // Send the cancellation error email
                $this->sendServiceCancelError($service, $errors);

                $this->log(
                    Language::_(
                        'Automation.task.cancel_scheduled_services.cancel_error',
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
                        'Automation.task.cancel_scheduled_services.cancel_success',
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
