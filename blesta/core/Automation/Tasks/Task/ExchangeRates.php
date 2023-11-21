<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Tasks\Common\AbstractTask;
use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Language;
use Loader;

/**
 * The exchange_rates automation task
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ExchangeRates extends AbstractTask
{
    /**
     * {@inheritdoc}
     */
    public function __construct(AutomationTypeInterface $task, array $options = [])
    {
        parent::__construct($task, $options);

        Loader::loadModels($this, ['Currencies']);
        Loader::loadHelpers($this, ['Html']);
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
        $this->log(Language::_('Automation.task.exchange_rates.attempt', true));

        // Execute the exchange rates cron task
        $this->process();

        // Log the task has completed
        $this->log(Language::_('Automation.task.exchange_rates.completed', true));
        $this->logComplete();
    }

    /**
     * Processes the task
     */
    private function process()
    {
        // Update the exchange rates
        $this->Currencies->updateRates();

        // Check for errors
        $error_messages = '';
        if (($errors = $this->Currencies->errors())) {
            $error_messages = Language::_('Automation.task.exchange_rates.failed', true);
            foreach ($errors as $error) {
                foreach ($error as $message) {
                    $error_messages = $this->Html->concat(' ', $error_messages, $message);
                }
            }

            // Reset errors
            $this->resetErrors($this->Currencies);
        }

        $this->log(
            (empty($error_messages) ? Language::_('Automation.task.exchange_rates.success', true) : $error_messages)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function isTimeToRun()
    {
        return $this->task->canRun(date('c'));
    }
}
