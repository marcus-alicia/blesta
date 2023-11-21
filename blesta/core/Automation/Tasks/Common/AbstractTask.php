<?php
namespace Blesta\Core\Automation\Tasks\Common;

use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Minphp\Date\Date;
use Configure;
use Exception;
use Input;
use Language;
use Loader;
use Throwable;

/**
 * Base abstract class from which executable automation tasks may extend.
 * Also provides for logging task output.
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Common
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractTask implements LoggableInterface, RunnableInterface
{
    /**
     * @var Minphp\Date\Date An instance of Minphp\Date\Date
     */
    protected $date;
    /**
     * @var The monolog logger
     */
    protected $logger;
    /**
     * @var An array of options for this task:
     *
     *  - print_log True to print logged content to stdout, or false otherwise (default false)
     *  - cli True if this is being run via the Command-Line Interface, or false otherwise (default true)
     */
    protected $options = [];
    /**
     * @var An instance of the task
     */
    protected $task;

    /**
     * Initialize a new task
     *
     * @param AutomationTypeInterface $task The raw automation task
     * @param array $options An additional options necessary for the task:
     *
     *  - print_log True to print logged content to stdout, or false otherwise (default false)
     *  - cli True if this is being run via the Command-Line Interface, or false otherwise (default true)
     */
    public function __construct(AutomationTypeInterface $task, array $options = [])
    {
        // Set properties
        $this->task = $task;
        $this->options = array_merge($this->getDefaultOptions(), $options);

        // Attempt to autoload the language
        $this->loadLanguage();

        // Load logger
        try {
            $container = Configure::get('container');
            $this->logger = $container->get('logger');
        } catch (Throwable $e) {
            // Do nothing
        }

        // Set a default date
        $this->setDate(new Date(null, 'UTC', 'UTC'));
    }

    /**
     * Determines whether this task can run at the given date
     *
     * @return bool True if it is time for the task to run, or false otherwise
     */
    abstract protected function isTimeToRun();

    /**
     * Determines whether today is the day of the month given
     *
     * @param int $day The day of the month to check (i.e. in range [1,31])
     * @return bool True if today is the current day given, false otherwise
     */
    protected function isCurrentDay($day)
    {
        return ($day == $this->date->cast('c', 'j'));
    }

    /**
     * Sets the date object for date calculations
     *
     * @param Minphp\Date\Date $date An instance of Minphp\Date\Date
     */
    public function setDate(Date $date)
    {
        $this->date = $date;
    }

    /**
     * Attempts to load a language file for use
     *
     * @param string $directory The path to the directory containing the
     *  language file (optional, defaults to the default language directory)
     * @param string $langCode The ISO 639-1/2 language to load the
     *  $filename for (e.g. en_us) (optional, defaults to the current language)
     * @param string $filename The name of the file (without extension) containing
     *  the file (optional, attempts to load based on the current task name)
     */
    public function loadLanguage($directory = null, $langCode = null, $filename = null)
    {
        // Set the default directory to look for the language
        $defaultDirectory = COREDIR . 'Automation' . DS . 'Tasks' . DS . 'Task' . DS . 'language' . DS;

        // Load the language file
        $class = explode('\\', get_class($this));
        $name = ($filename !== null ? $filename : Loader::fromCamelCase(end($class)));
        $path = ($directory !== null ? $directory : $defaultDirectory);
        Language::loadLang($name, $langCode, $path);
    }

    /**
     * Logs the given content for this task
     *
     * @param string $content The content to log to cron
     */
    public function log($content)
    {
        // Print the content
        if ($this->options['print_log']) {
            // Assume line breaks should be HTML breaks unless in CLI
            $newLine = ($this->options['cli'] ? "\n" : '<br />');
            echo (!$this->options['cli'] ? nl2br($content) : $content) . $newLine;

            // Flush the output buffer
            if (@ob_get_contents()) {
                @ob_flush();
            }

            @flush();

            if (@ob_get_contents()) {
                @ob_end_flush();
            }
        }

        // Allow the task itself to perform logging
        if ($this->task instanceof LoggableInterface) {
            $this->task->log($content);
        }
    }

    /**
     * Marks the log completed
     */
    public function logComplete()
    {
        // Nothing to do except call the task to perform the log completion
        if ($this->task instanceof LoggableInterface) {
            $this->task->logComplete();
        }
    }

    /**
     * Reset Input errors on a given object
     *
     * @param object $object An instance of an object containing the Input component
     */
    protected function resetErrors($object)
    {
        if (is_object($object) && is_object($object->Input) && $object->Input instanceof Input) {
            $object->Input->setErrors([]);
        }
    }

    /**
     * Retrieves default options expected of tasks
     *
     * @return array An array of key/value pairs
     */
    private function getDefaultOptions()
    {
        return [
            'print_log' => false,
            'cli' => true
        ];
    }
}
