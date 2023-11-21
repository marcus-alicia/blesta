<?php
namespace Blesta\Core\Automation;

use Blesta\Core\Automation\Type\Cron\Task as CronTask;
use Minphp\Date\Date;
use Exception;
use BadMethodCallException;
use Loader;
use ReflectionClass;
use stdClass;
use Throwable;

/**
 * Factory class for generating runnable automation tasks and cron tasks
 *
 * @package blesta
 * @subpackage blesta.core.Automation
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class TaskFactory
{
    /**
     * @var \Minphp\Date\Date An instance of \Minphp\Date\Date
     */
    protected $date;

    /**
     * Initialize the factory
     *
     * @param \Minphp\Date\Date $date A configured instance of \Minphp\Date\Date
     */
    public function __construct(Date $date)
    {
        $this->date = $date;
    }

    /**
     * Autoload \Blesta\Core\Automation\Tasks\Task\* by name
     *
     * @param string $name The name of the task to load
     * @param array $arguments An array of arguments to pass to the task instance constructor
     * @return \Blesta\Core\Automation\Type\Common\AutomationTypeInterface An instance of the task
     * @throws BadMethodCallException
     */
    public function __call($name, array $arguments)
    {
        $class = '\\Blesta\\Core\\Automation\\Tasks\\Task\\' . Loader::toCamelCase($name);

        try {
            $reflect = new ReflectionClass($class);

            $instance = $reflect->newInstanceArgs($arguments);
            $instance->setDate($this->date);

            return $instance;
        } catch (Throwable $e) {
            throw new BadMethodCallException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * Creates a new cron task
     *
     * @param stdClass $task An stdClass object representing the cron task
     * @param array $options An array of options:
     *
     *  - log_group The name of the cron log group to use for logging purposes
     * @return Blesta\Core\Automation\Type\Cron\Task The cron task
     */
    public function cronTask(stdClass $task, array $options = [])
    {
        return new CronTask($task, $this->date, $options);
    }
}
