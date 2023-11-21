<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Tasks\Common\AbstractTask;
use Exception;
use Language;
use Loader;
use stdClass;
use Throwable;

/**
 * The plugin automation task to execute a plugin's cron actions
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Plugin extends AbstractTask
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

        // Retrieve the raw task data, expecting the cron task to contain a plugin object
        $data = $this->task->raw();

        // This is not a valid plugin task to be run
        if (empty($data->plugin)
            || !isset($data->key)
            || !isset($data->plugin->enabled)
            || !$data->plugin->enabled
            || !isset($data->plugin->dir)
        ) {
            return;
        }

        // Log the task has begun
        $this->log(Language::_('Automation.task.plugin.attempt', true, $data->plugin->dir, $data->key));

        // Execute the plugin cron task
        $logs = $this->process($data);

        foreach ($logs as $log) {
            $this->log($log);
        }

        // Log the task has completed
        $this->log(Language::_('Automation.task.plugin.completed', true, $data->plugin->dir, $data->key));
        $this->logComplete();
    }

    /**
     * Processes the task
     *
     * @param stdClass $data The plugin task data
     * @return array An array containing the log lines returned by the plugin
     */
    private function process(stdClass $data)
    {
        Loader::loadComponents($this, ['Plugins']);

        // Execute the plugin cron task
        $log = [];
        try {
            $plugin = $this->Plugins->create($data->plugin->dir);
            $log = $plugin->cron($data->key);
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
