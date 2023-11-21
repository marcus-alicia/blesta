<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Tasks\Common\AbstractTask;
use Exception;
use Language;
use Loader;
use stdClass;
use Throwable;

/**
 * The module automation task to execute a module's cron actions
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Module extends AbstractTask
{
    /**
     * {@inheritdoc}
     */
    public function run()
    {
        // This task cannot be run right now
        if (!$this->isTimeToRun()) {
            return;
        }

        // Retrieve the raw task data, expecting the cron task to contain a module object
        $data = $this->task->raw();

        // This is not a valid module task to be run
        if (empty($data->module) || !isset($data->key) || !isset($data->module->class)) {
            return;
        }

        // Log the task has begun
        $this->log(Language::_('Automation.task.module.attempt', true, $data->module->class, $data->key));

        // Execute the module cron task
        $logs = $this->process($data);

        foreach ($logs as $log) {
            $this->log($log);
        }

        // Log the task has completed
        $this->log(Language::_('Automation.task.module.completed', true, $data->module->class, $data->key));
        $this->logComplete();
    }

    /**
     * Processes the task
     *
     * @param stdClass $data The module task data
     * @return array An array containing the log lines returned by the module
     */
    private function process(stdClass $data)
    {
        Loader::loadComponents($this, ['Modules']);

        // Execute the module cron task
        $log = [];
        try {
            $module = $this->Modules->create($data->module->class);
            $module->setModule($data->module);
            $log = $module->cron($data->key);
        } catch (Throwable $e) {
            // Unexpected error
            $this->log($e->getMessage() . "\n" . $e->getTraceAsString());

            if ($this->logger) {
                $this->logger->alert($e);
            }
        }

        return is_array($log) ? $log : [];
    }

    /**
     * {@inheritdoc}
     */
    protected function isTimeToRun()
    {
        return $this->task->canRun(date('c'));
    }
}
