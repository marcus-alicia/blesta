<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Blesta\Core\Automation\Tasks\Common\StaffNoticeTask;
use Language;
use Loader;
use stdClass;

/**
 * The process_renewing_services automation task
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ProcessRenewingServices extends StaffNoticeTask
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
        $this->log(Language::_('Automation.task.process_renewing_services.attempt', true));

        // Execute the process renewing services cron task
        $this->process($this->task->raw());

        // Log the task has completed
        $this->log(Language::_('Automation.task.process_renewing_services.completed', true));
        $this->logComplete();
    }

    /**
     * Processes the task
     *
     * @param stdClass $data The process renewing services task data
     */
    private function process(stdClass $data)
    {
        // Fetch all services since the last run date
        $services = $this->Services->getAllRenewablePaid();

        // Renew the services
        foreach ($services as $service) {
            $this->Services->renew($service->id, $service->renewal_invoice_id);

            // Log success/error
            if (($errors = $this->Services->errors())) {
                $this->log(
                    Language::_(
                        'Automation.task.process_renewing_services.renew_error',
                        true,
                        $service->id_code,
                        $service->client_id_code
                    )
                );

                // Send the error email
                $this->sendServiceRenewalError($service, $errors);

                // Reset errors
                $this->resetErrors($this->Services);
            } else {
                $this->log(
                    Language::_(
                        'Automation.task.process_renewing_services.renew_success',
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
