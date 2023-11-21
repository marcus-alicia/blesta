## Blesta Automation

The Blesta\Core\Automation library can be used to construct and run tasks,
primarily automated tasks via cron. Tasks may perform almost any action,
including writing log data.


### Basic Usage

#### Factories

##### Task Factory

The _Blesta\Core\Automation\TaskFactory_ provides methods to instantiate
cron tasks and individual automation tasks, such as tasks to create invoices,
suspend services, etc.

The TaskFactory attempts to load any task from
_Blesta\Core\Automation\Tasks\Task\\*_ called by method name.

```
use Blesta\Core\Automation\TaskFactory;
use Minphp\Date\Date;

$factory = new TaskFactory(new Date());

// The cron task must be constructed from cron task object data
$data = (object)[
    'id' => 1,
    'name' => 'My Task',
    'type' => 'interval',
    'task_run_id' => 1,
    'company_id' => 1,
    'time' => null
    'enabled' => true,
    'date_enabled' => '2018-01-01 00:00:00',
    'date_last_started' => '2018-01-01 12:00:00',
    'date_last_completed' => '2018-01-01 12:00:05',
    'plugin_id' => 5,
    'plugin_dir' => 'support_manager',
    'plugin_name' => 'Support Manager',
    'plugin_version' => '3.0.0',
    'plugin_enabled' => true
];

// Blesta\Core\Automation\Type\Cron\Task
$cronTask = $factory->cronTask($data);

// "plugin" method exists because Blesta\Core\Automation\Tasks\Task\Plugin class exists
$task = $factory->plugin($cronTask);
```

#### Cron Task

A cron task must be instantiated from a set of cron task object data. It
extends _Blesta\Core\Automation\Type\Cron\AbstractCron_, which implements the
_Blesta\Core\Automation\Type\Common\AutomationTypeInterface_ and
_BLesta\Core\Automation\Type\Common\LoggableInterface_ interfaces.

##### Instantiation

```
use Blesta\Core\Automation\Type\Cron\Task

// The cron task must be constructed from cron task object data
$data = (object)[
    'id' => 1,
    'name' => 'My Task',
    'type' => 'interval',
    'task_run_id' => 1,
    'company_id' => 1,
    'time' => null
    'enabled' => true,
    'date_enabled' => '2018-01-01 00:00:00',
    'date_last_started' => '2018-01-01 12:00:00',
    'date_last_completed' => '2018-01-01 12:00:05',
    'plugin_id' => 5,
    'plugin_dir' => 'support_manager',
    'plugin_name' => 'Support Manager',
    'plugin_version' => '3.0.0',
    'plugin_enabled' => true
];

$options = [
    'log_group' => '1234567890abcxyz'
];
$cronTask = new Task($data, $options);
```

Options may include:

* log_group - (optional) _string_, a unique name to use for logging purposes.
Defaults to a random value.


##### Methods

###### AbstractCron::raw

The cron task may return the original object data provided to it via _::raw()_

```
// stdClass object set on initialization
$data = $cronTask->raw();
```

###### AbstractCron::canRun

You may check whether the cron task is available to be run at the given date by
calling this method. Note that this may be determined relative to the last run
date.

```
// Determine whether this cron task can run at this date
$canRun = $cronTask->canRun('2018-01-01T00:00:00+00:00');
```

#### Automation Tasks

Automation tasks can extend the
_Blesta\Core\Automation\Tasks\Common\AbstractTask_ abstract class, which
implements the _Blesta\Core\Automation\Tasks\Common\RunnableInterface_
and _Blesta\Core\Automation\Tasks\Common\LoggableInterface_ interfaces.

##### AbstractTask

###### AbstractTask::__construct

An automation task must be passed an instance of the
_Blesta\Core\Automation\Type\Common\AutomationTypeInterface_ along with a set of
(optional) options.

The cron task _Blesta\Core\Automation\Type\Cron\Task_ already implements this
interface, so for brevity, we will use that to construct a new task:

```
use Blesta\Core\Automation\Tasks\Common\AbstractTask;

// Assume $cronTask is an instance of _Blesta\Core\Automation\Type\Cron\Task_

class Task extends AbstractTask { }

$task = new Task($cronTask);

// Create a new task and pass in options
$options = [
    'print_log' => true,
    'cli' => false
];
$task2 = new Task($cronTask, $options);
```

Options may include:

* print_log - (optional) _true/false_, whether to allow logged content to be
printed out. Default false.

* cli - (optional) _true/false_, whether the task is being run in CLI mode
Default true.

###### AbstractTask::setDate

An automation task often needs to perform date manipulation, and so you may pass
it an instance of _Minphp\Date\Date_.

```
// Assume $task is an instance of AbstractTask
$task->setDate(new Minphp\Date\Date());
```

###### AbstractTask::loadLanguage

An automation task often needs to log information, and that information may be
language-dependent, so it's pertinent for it to load the proper language files
to source definitions.

The language can be loaded automatically if it lives in
_/blesta/core/Automation/Tasks/Task/language/_ and follows the standard language
file structure. The file name of the language file should be the snake-case name
of the class to be loaded automatically.

```
// Assume $task is an instance of AbstractTask

// The language can be loaded using the default language configuration in Blesta
$task->loadLanguage();
```

You may also provide the specific language file to load:

```
// Assume $task is an instance of AbstractTask

// The following will load the file on the file system from
// /var/www/public_html/blesta/language/en_uk/my_file.php
$languageDirectory = '/var/www/public_html/blesta/language/'
$task->loadLanguage($languageDirectory, 'en_uk', 'my_file');
```

###### AbstractTask::log

Tasks that extend AbstractTask can take advantage of logging strings to
standard output if configured to.

```
// Assume $task is an instance of AbstractTask

// Log/print content for this task
$task->log('Test 1');
$task->log('Test 2');
$task->log('Test 3');
```

##### Tasks

For any tasks that extend _Blesta\Core\Automation\Tasks\Common\AbstractTask_,
they must implement the _::run()_ method. This is the runnable method that
executes the task. They must also implement the protected method
_::isTimeToRun()_ to return a boolean value signifying whether or not it is time
for the task to run.

###### ::run

```
// Assume $task is an instance of AbstractTask

// Do something
$task->run();
```
