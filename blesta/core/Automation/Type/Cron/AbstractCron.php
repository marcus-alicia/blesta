<?php
namespace Blesta\Core\Automation\Type\Cron;

use Blesta\Core\Automation\Tasks\Common\LoggableInterface;
use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Minphp\Date\Date;
use Configure;
use Loader;
use stdClass;

/**
 * Base abstract class for representing a Blesta cron task.
 * Also provides for logging task output to the cron log.
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Type.Cron
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractCron implements AutomationTypeInterface, LoggableInterface
{
    /**
     * @var Minphp\Date\Date An instance of Minphp\Date\Date
     */
    private $date;
    /**
     * @var The log event for this log session
     */
    private $logEvent = 1;
    /**
     * @var Whether or not this task has written a cron log yet
     */
    private $logged = false;
    /**
     * @var The string content currently logged to the cron log
     */
    private $loggedContent = '';
    /**
     * @var array An array of key/value pairs
     */
    private $options = [];
    /**
     * @var stdClass The task data
     */
    private $task;

    /**
     * Initialize the cron task with cron task data
     *
     * @param stdClass $task An stdClass object containing:
     *
     *  - id The cron task ID
     *  - key The cron task key
     *  - name The name of the cron task
     *  - type The type of cron task (e.g. interval or time)
     *  - task_run_id The ID of the runnable task
     *  - company_id The ID of the company
     *  - time The time of day to run the task
     *  - interval The time interval to run the task
     *  - enabled Whether or not the task is enabled
     *  - date_enabled The date timestamp that the task was enabled
     *  - date_last_started The date timestamp that the task last started
     *  - date_last_completed The date timestamp that the task last completed
     *  - plugin_id The ID of the plugin associated with the cron task
     *  - plugin_dir The plugin directory
     *  - plugin_name The name of the plugin associated with the cron task
     *  - plugin_version The version of the plugin associated with the cron task
     *  - plugin_enabled Whether or not the plugin is enabled
     * @param Minphp\Date\Date An instance of Minphp\Date\Date
     * @param array $options An array of options, including:
     *
     *  - log_group The name of the cron log group to use for logging purposes
     */
    public function __construct(stdClass $task, Date $date, array $options = [])
    {
        $this->task = $task;
        $this->date = $date;
        $this->options = array_merge($this->getDefaultOptions(), $options);

        // Load the Logs model to allow logging to the cron log
        Loader::loadModels($this, ['Logs']);
    }

    /**
     * Retrieves the raw input for this task
     *
     * @return stdClass An stdClass object representing the cron task
     */
    public function raw()
    {
        return $this->task;
    }

    /**
     * Logs the given content to the cron log
     *
     * @param string $content The content to log
     */
    public function log($content)
    {
        // Append the content to the current logged content so it doesn't need to be re-fetched
        $content = (!empty($this->loggedContent) ? $this->loggedContent . "\n" : '') . $content;

        if (!$this->logged) {
            // Log a new entry
            $vars = [
                'run_id' => $this->task->task_run_id,
                'event' => $this->logEvent,
                'group' => $this->options['log_group'],
                'start_date' => date('c'),
                'output' => $content,
                'key' => $this->task->key ?? null,
            ];

            $this->Logs->addCron($vars);
            $this->logged = true;
        } else {
            // Update an existing log entry
            $vars = [
                'output' => $content
            ];

            $this->Logs->updateCron($this->task->task_run_id, $this->options['log_group'], $this->logEvent, $vars);
        }

        // Maintain an updated copy of the logged content for future overrides
        if (!$this->Logs->errors()) {
            $this->loggedContent = $content;
        }
    }

    /**
     * Marks the current cron task log complete
     */
    public function logComplete()
    {
        // Set an end date on the log to mark it complete
        if ($this->logged) {
            $vars = [
                'end_date' => date('c')
            ];

            $this->Logs->updateCron($this->task->task_run_id, $this->options['log_group'], $this->logEvent, $vars);

            // Reset our logged state so that we can begin a new cron log as a new event
            $this->logged = false;
            $this->logEvent++;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function canRun($date)
    {
        // The task must be enabled
        if (!(bool)$this->task->enabled) {
            return false;
        }

        // If the task is currently running, check if it's safe to start a new process
        if ($this->task->date_last_started !== null && $this->task->date_last_completed === null) {
            // A certain amount of time must pass before allowing a stalled/incomplete task to restart
            // e.g. 6 hours after the task began, allow it to be restarted
            $pastDate = $this->date->modify(
                $date,
                '-' . (int)abs(Configure::get('Blesta.cron_task_restart_limit')) .' minutes',
                'c',
                Configure::get('Blesta.company_timezone')
            );

            return ($this->date->toTime($this->task->date_last_started) < $this->date->toTime($pastDate));
        }

        // Determine whether the task can be run relative to the given date
        $canRun = false;
        switch ($this->task->type) {
            case 'time':
                $canRun = $this->taskTimePast($date, $this->task->date_last_started, $this->task->date_last_completed);
                break;
            case 'interval':
                $canRun = $this->taskIntervalPast($date, $this->task->date_last_started);
                break;
        }

        return $canRun;
    }

    /**
     * Rounds down the given timestamp to the nearest interval
     *
     * @param string $dateTime The date timestamp to round down to the nearest interval
     * @return int The UNIX timestamp of the nearest interval
     */
    private function getRoundedInterval($dateTime)
    {
        // Round down the date to the nearest 5-minute interval
        $roundingInterval = 60 * (Configure::get('Blesta.cron_minimum_run_interval')
            ? (int)abs(Configure::get('Blesta.cron_minimum_run_interval'))
            : 5);
        return (floor($this->date->toTime($dateTime) / $roundingInterval) * $roundingInterval);
    }

    /**
     * Determines whether the task's run interval has passed
     *
     * @param string $date The date timestamp
     * @param string|null $lastStartDate The UTC timestamp that the task last began
     * @return bool True if the interval date has passed, or false otherwise
     */
    private function taskIntervalPast($date, $lastStartDate = null)
    {
        // If the task has never run, it can run now
        if ($lastStartDate === null) {
            return true;
        }

        // Create a date object that is in UTC to ensure dates aren't formatted into a different timezone
        $utcDate = clone $this->date;
        $utcDate->setTimezone('UTC', 'UTC');

        // Create a date at the task interval from the rounded last run time
        $nextRunDate = $this->date->modify(
            $utcDate->format('c', $this->getRoundedInterval($lastStartDate)),
            '+' . (int)$this->task->interval . ' minutes',
            'c',
            Configure::get('Blesta.company_timezone')
        );

        return ($this->date->toTime($date) >= $this->date->toTime($nextRunDate));
    }

    /**
     * Determines whether the task's run time has passed
     *
     * @param string $date The date timestamp
     * @param string|null $lastStartDate The UTC timestamp that the task last began
     * @param string|null $lastEndDate The UTC timestamp that the task last ended
     * @return bool True if the interval date has passed, or false otherwise
     */
    private function taskTimePast($date, $lastStartDate = null, $lastEndDate = null)
    {
        // Get the time rounded down to the nearest interval and set the time string
        $time = $this->date->format('His', $this->getRoundedInterval($date));
        $taskTime = $this->date->format('His', $this->task->time);

        // If the task has never been run, it can run so long as the time-of-day has past
        if ($lastStartDate === null && $lastEndDate === null) {
            return ($time >= $taskTime);
        }

        // Times should be local after formatting
        $day = (int)$this->date->format('Ymd', $date);
        $lastRunDay = (int)$this->date->format('Ymd', $lastEndDate);
        $dayAfterLastRunDay = (int)$this->date->modify(
            $lastEndDate,
            '+1 day',
            'Ymd',
            Configure::get('Blesta.company_timezone')
        );

        // If the task has not run in more than a day,
        // or the task has not already run today and the time-of-day has past, it may be run
        return (($day > $dayAfterLastRunDay) || ($day > $lastRunDay && $time >= $taskTime));
    }

    /**
     * Retrieves default options expected of cron tasks
     *
     * @return array An array of key/value pairs
     */
    private function getDefaultOptions()
    {
        return [
            'log_group' => microtime(true)
        ];
    }
}
