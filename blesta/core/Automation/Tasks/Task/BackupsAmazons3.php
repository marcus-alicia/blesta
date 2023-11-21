<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Tasks\Common\AbstractTask;
use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Exception;
use Language;
use Loader;
use Throwable;

/**
 * The backups_amazons3 automation task
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class BackupsAmazons3 extends AbstractTask
{
    /**
     * {@inheritdoc}
     */
    public function __construct(AutomationTypeInterface $task, array $options = [])
    {
        parent::__construct($task, $options);

        Loader::loadModels($this, ['Backup']);
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
        $this->log(Language::_('Automation.task.backups_amazons3.attempt', true));

        // Execute the backups amazons3 cron task
        $this->process();

        // Log the task has completed
        $this->log(Language::_('Automation.task.backups_amazons3.completed', true));
        $this->logComplete();
    }

    /**
     * Processes the task
     */
    private function process()
    {
        try {
            // Send the backup to the amazon s3 server
            $this->Backup->sendBackup('amazons3');

            if (($errors = $this->Backup->errors()) && isset($errors['amazons3_failed'])) {
                $this->log($errors['amazons3_failed']);
            } else {
                $this->log(Language::_('Automation.task.backups_amazons3.success', true));
            }
        } catch (Throwable $e) {
            $this->log($e->getMessage());
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
