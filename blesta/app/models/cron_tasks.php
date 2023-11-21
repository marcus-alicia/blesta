<?php

/**
 * Cron Task management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class CronTasks extends AppModel
{
    /**
     * Initialize Cron Tasks
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['cron_tasks']);

        Loader::loadModels($this, ['ModuleManager', 'PluginManager']);
    }

    /**
     * Updates the given task to set additional fields to the object, such as language and module/plugin information
     *
     * @param stdClass $task An stdClass object representing a single cron task
     * @param bool $set_plugin_fields True to include plugin fields as a part of the task,
     *  or false otherwise (default false)
     * @return stdClass The updated task object
     */
    private function setTaskFields(stdClass $task, $set_plugin_fields = false)
    {
        // Select the plugin/module based on the task's company, if we have one
        $company_id = (isset($task->company_id) ? $task->company_id : null);

        // Set plugin or module fields
        switch ($task->task_type) {
            case 'plugin':
                // Attach the plugin information if available
                $plugins = $this->PluginManager->getByDir($task->dir, $company_id);

                if (isset($plugins[0])) {
                    $task->plugin = $plugins[0];
                }

                break;
            case 'module':
                // Attach the module information if available
                $modules = $this->ModuleManager->getByClass($task->dir, $company_id);

                if (isset($modules[0])) {
                    $task->module = $modules[0];
                }

                break;
            default:
                // Do nothing
                break;
        }

        // Set language definitions for this task
        $this->setLanguage($task);

        return $task;
    }

    /**
     * Retrieves a cron task
     *
     * @param int $id The cron task ID
     * @return mixed An stdClass object representing the cron task, or false if it does not exist
     */
    public function get($id)
    {
        $task = $this->Record->select()
            ->from('cron_tasks')
            ->where('id', '=', $id)
            ->fetch();

        // Set additional task fields
        if ($task) {
            $task = $this->setTaskFields($task);
        }

        return $task;
    }

    /**
     * Retrieves a cron task
     *
     * @param string $key The cron task key
     * @param string $plugin_dir The plugin directory of the plugin this cron task belongs to
     *  OR if $task_type is given, the directory that this cron task belongs to, plugin or not
     * @param string $task_type The type of task to fetch (one of 'system', 'plugin', or 'module'),
     *  default 'plugin' for backward compatibility
     * @return mixed An stdClass object representing the cron task, or false if it does not exist
     */
    public function getByKey($key, $plugin_dir = null, $task_type = 'plugin')
    {
        $this->Record->select()
            ->from('cron_tasks')
            ->where('key', '=', $key)
            ->where('dir', '=', $plugin_dir);

        // If a plugin directory was given, we must assume this is a plugin task unless otherwise specified
        if ($plugin_dir !== null || $task_type != 'plugin') {
            $this->Record->where('task_type', '=', $task_type);
        }

        $task = $this->Record->fetch();

        // Set additional task fields
        if ($task) {
            $task = $this->setTaskFields($task);
        }

        return $task;
    }

    /**
     * Retrieves a list of all cron tasks in the system
     *
     * @return array An array of stdClass objects representing each cron task
     */
    public function getAll()
    {
        $tasks = $this->Record->select()
            ->from('cron_tasks')
            ->fetchAll();

        // Set additional task fields for each task
        foreach ($tasks as &$task) {
            $task = $this->setTaskFields($task);
        }

        return $tasks;
    }

    /**
     * Adds a new cron task
     *
     * @param array $vars An array of key=>value fields including:
     *
     *  - key A unique key representing this cron task
     *  - task_type The type of cron task this represents (system, module, or plugin)
     *  - dir The directory this cron task belongs to (optional)
     *  - name The name of this cron task
     *  - description The description of this cron task (optional)
     *  - is_lang 1 if name and description are language definitions in the
     *      language file, 0 otherwise (optional, default 0)
     *  - type The type of cron task this is ("time" or "interval" based, optional, default "interval")
     * @return mixed The cron task ID created, or void on error
     */
    public function add(array $vars)
    {
        // Support the backward-incompatible behavior pre-v4.3 by also accepting a 'plugin_dir' field if given
        if (!empty($vars['plugin_dir']) && !array_key_exists('dir', (array)$vars) && !array_key_exists('task_type', (array)$vars)) {
            $vars['dir'] = $vars['plugin_dir'];
            $vars['task_type'] = 'plugin';
            unset($vars['plugin_dir']);
        } else {
            // Set the default task type
            $vars['task_type'] = (isset($vars['task_type']) ? $vars['task_type'] : 'system');
        }

        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ['key', 'task_type', 'dir', 'name', 'description', 'is_lang', 'type'];

            $this->Record->insert('cron_tasks', $vars, $fields);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Edits a cron task
     *
     * @param int $task_id The cron task ID to edit
     * @param array $vars A list of key=>value fields to update, including:
     *
     *  - name The name of this cron task
     *  - description The description of this cron task (optional)
     *  - is_lang 1 if name and description are language definitions in the
     *      language file, 0 otherwise (optional, default 0)
     */
    public function edit($task_id, array $vars)
    {
        $vars['id'] = $task_id;

        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            $fields = ['name', 'description', 'is_lang'];

            $this->Record->where('id', '=', $task_id)
                ->update('cron_tasks', $vars, $fields);
        }
    }

    /**
     * Deletes a cron task
     *
     * @param int $task_id The ID of the cron task to delete
     * @param string $task_type The task's task_type (i.e., 'plugin', or 'module')
     * @param string $dir The directory this cron task belongs to
     */
    public function deleteTask($task_id, $task_type, $dir)
    {
        // Do nothing if an invalid task type is given
        if (!in_array($task_type, ['plugin', 'module'])) {
            return;
        }

        // Delete the cron task and all cron task runs
        $this->Record->from('cron_tasks')
            ->leftJoin('cron_task_runs', 'cron_task_runs.task_id', '=', 'cron_tasks.id', false)
            ->where('cron_tasks.id', '=', $task_id)
            ->where('cron_tasks.task_type', '=', $task_type)
            ->where('cron_tasks.dir', '=', $dir)
            ->delete(['cron_tasks.*', 'cron_task_runs.*']);
    }

    /**
     * Sets when a cron task should run for a given company
     *
     * @param int $task_id The cron task ID associated with this runnable task
     * @param array $vars A list of key=>value fields to add, including:
     *
     *  - time The daily 24-hour time that this task should run
     *      (e.g. "14:25", optional, required if interval is not given)
     *  - interval The interval, in minutes, that this cron task should run
     *      (optional, required if time is not given)
     *  - enabled 1 if this cron task is enabled, 0 otherwise (optional, default 1)
     * @return mixed The cron task run ID created, or void on error
     */
    public function addTaskRun($task_id, array $vars)
    {
        $vars['task_id'] = $task_id;
        $vars['company_id'] = Configure::get('Blesta.company_id');
        $vars['date_enabled'] = $this->dateToUtc(date('c'));

        $rules = $this->getTaskRunRules($vars);
        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            $fields = ['task_id', 'company_id', 'enabled', 'date_enabled'];

            // Allow interval to be set if the rule is set, otherwise time, but not both
            if (isset($rules['interval'])) {
                $fields[] = 'interval';
            } else {
                $fields[] = 'time';
            }

            $this->Record->insert('cron_task_runs', $vars, $fields);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Updates when a cron task should run for the given company
     *
     * @param int $task_run_id The cron task run ID
     * @param array $vars A list of key=>value fields to update, including:
     *
     *  - time The daily 24-hour time that this task should run
     *      (e.g. "14:25", optional, required if interval is not given)
     *  - interval The interval, in minutes, that this cron task should run
     *      (optional, required if time is not given)
     *  - enabled 1 if this cron task is enabled, 0 otherwise (optional, default 1)
     */
    public function editTaskRun($task_run_id, array $vars)
    {
        $vars['id'] = $task_run_id;

        $rules = $this->getTaskRunRules($vars, true);
        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            $fields = ['enabled', 'date_enabled'];

            // Allow interval to be set if the rule is set, otherwise time, but not both
            if (isset($rules['interval'])) {
                $fields[] = 'interval';
            } else {
                $fields[] = 'time';
            }

            // Get the current task and change the date_enabled
            if (isset($vars['enabled']) && ($task_run = $this->getTaskRun($task_run_id))) {
                // Re-enable the task
                if ($vars['enabled'] == '1') {
                    if ($task_run->enabled == '0') {
                        $vars['date_enabled'] = $this->dateToUtc(date('c'));
                    }
                } else {
                    // Disabling this cron task run
                    $vars['date_enabled'] = null;
                    $vars['enabled'] = '0';
                }
            }

            $this->Record->where('id', '=', $task_run_id)
                ->update('cron_task_runs', $vars, $fields);
        }
    }

    /**
     * Deletes when a cron task should run for the given company.
     * NOTE: This will also delete the cron task itself iff the cron task is no longer used by any other company
     * and this cron task is related to a plugin
     *
     * @param int $task_run_id The cron task run ID
     */
    public function deleteTaskRun($task_run_id)
    {
        $fields = [
            'cron_tasks.dir',
            'cron_tasks.task_type',
            'cron_task_runs.task_id' => 'id',
            'cron_task_runs.company_id'
        ];

        // Fetch the cron task associated with this run task, but only those related to a plugin
        $cron_task = $this->Record->select($fields)
            ->from('cron_task_runs')
            ->innerJoin('cron_tasks', 'cron_tasks.id', '=', 'cron_task_runs.task_id', false)
            ->where('cron_task_runs.id', '=', $task_run_id)
            ->where('cron_tasks.task_type', 'in', ['module', 'plugin'])
            ->fetch();

        // Check and delete the cron task itself if it belongs to a module/plugin and is no longer in use
        // by another company
        if ($cron_task) {
            // Fetch all the cron task runs that are associated with this plugin for any other company
            $this->Record = $this->getAllTaskRuns();
            $num_run_tasks = $this->Record->where('cron_tasks.id', '=', $cron_task->id)
                ->where('cron_tasks.task_type', '=', $cron_task->task_type)
                ->where('cron_tasks.dir', '=', $cron_task->dir)
                ->where('cron_task_runs.company_id', '!=', $cron_task->company_id)
                ->numResults();

            // Delete the cron task if no other company uses it
            if ($num_run_tasks == 0) {
                $this->Record->from('cron_tasks')
                    ->where('cron_tasks.id', '=', $cron_task->id)
                    ->delete();
            }
        }

        // Delete the cron task run
        $this->Record->from('cron_task_runs')
            ->where('id', '=', $task_run_id)
            ->delete();
    }

    /**
     * Retrieves a cron task and its company-specific run settings
     *
     * @param int $task_run_id The cron task run ID
     * @param bool $system True to fetch only system cron tasks, false to fetch company cron tasks (default false)
     * @return mixed An stdClass object representing the runnable cron task, or false if one does not exist
     */
    public function getTaskRun($task_run_id, $system = false)
    {
        $this->Record = $this->getAllTaskRuns();
        $this->Record->where('cron_task_runs.id', '=', $task_run_id);

        if ($system) {
            $this->Record->where('cron_task_runs.company_id', '=', 0);
        } else {
            // Filter based on company ID
            if (Configure::get('Blesta.company_id')) {
                $this->Record->where('cron_task_runs.company_id', '=', Configure::get('Blesta.company_id'));
            }
        }

        $task = $this->Record->fetch();

        // Set additional task fields
        if ($task) {
            $task = $this->setTaskFields($task, true);
        }

        return $task;
    }

    /**
     * Retrieves a cron task and its company-specific run settings
     *
     * @param string $key The cron task key
     * @param string $plugin_dir The plugin directory of the plugin this cron task belongs to
     *  OR if $task_type is given, the directory that this cron task belongs to, plugin or not (default null)
     * @param bool $system True to fetch only system cron tasks, false to fetch company cron tasks (default false)
     * @param string $task_type The type of task to fetch (one of 'system', 'plugin', or 'module'),
     *  (default 'plugin' for backward compatibility)
     * @return mixed An stdClass object representing the runnable cron task, or false if one does not exist
     */
    public function getTaskRunByKey($key, $plugin_dir = null, $system = false, $task_type = 'plugin')
    {
        // Fetch the cron task run ID
        $this->Record->select('cron_task_runs.id')
            ->from('cron_tasks')
            ->innerJoin('cron_task_runs', 'cron_task_runs.task_id', '=', 'cron_tasks.id', false)
            ->where('cron_tasks.key', '=', $key)
            ->where('cron_tasks.dir', '=', $plugin_dir);

        // If a plugin directory was given, we must assume this is a plugin task unless otherwise specified
        if ($plugin_dir !== null || $task_type != 'plugin') {
            $this->Record->where('cron_tasks.task_type', '=', $task_type);
        }

        if ($system) {
            $this->Record->where('cron_task_runs.company_id', '=', 0);
        } else {
            // Filter based on company ID
            if (Configure::get('Blesta.company_id')) {
                $this->Record->where('cron_task_runs.company_id', '=', Configure::get('Blesta.company_id'));
            }
        }

        $cron_task_run = $this->Record->fetch();

        // Return the cron task run
        if ($cron_task_run) {
            return $this->getTaskRun($cron_task_run->id, $system);
        }

        return false;
    }

    /**
     * Retrieves a list of all cron tasks and their company-specific run settings for this company
     *
     * @param bool $system True to fetch only system cron tasks, false to fetch company cron tasks (default false)
     * @param string $task_type The type of task to fetch (i.e. 'system', 'plugin', 'module',
     *  or 'all' for any, default 'all')
     * @param string $dir The directory this cron task belongs to iff $task_type is
     *  'plugin' or 'module' (optional)
     * @return array A list of stdClass objects representing each cron task, or an empty array if none exist
     */
    public function getAllTaskRun($system = false, $task_type = 'all', $dir = null)
    {
        $this->Record = $this->getAllTaskRuns();

        if ($system) {
            $this->Record->where('cron_task_runs.company_id', '=', 0);
        } else {
            // Filter based on company ID
            if (Configure::get('Blesta.company_id')) {
                $this->Record->leftJoin('modules', 'modules.class', '=', 'cron_tasks.dir', false)
                    ->leftJoin('plugins', 'plugins.dir', '=', 'cron_tasks.dir', false)
                    ->where('cron_task_runs.company_id', '=', Configure::get('Blesta.company_id'))
                    ->open()
                        ->where('cron_tasks.task_type', '=', 'system')
                        ->open()
                            ->orWhere('plugins.company_id', '=', 'cron_task_runs.company_id', false)
                            ->where('cron_tasks.task_type', '=', 'plugin')
                        ->close()
                        ->open()
                            ->orWhere('modules.company_id', '=', 'cron_task_runs.company_id', false)
                            ->where('cron_tasks.task_type', '=', 'module')
                        ->close()
                    ->close();

                // Filter based on task type
                if (array_key_exists($task_type, (array)$this->getTaskTypes())) {
                    $this->Record->where('cron_tasks.task_type', '=', $task_type);

                    // Filter by directory for a specific plugin/module respectively
                    if ($dir !== null && in_array($task_type, ['plugin', 'module'])) {
                        $this->Record->where('cron_tasks.dir', '=', $dir);
                    }
                }
            }
        }

        $tasks = $this->Record->group(['cron_tasks.id'])
            ->fetchAll();

        // Set additional task fields
        foreach ($tasks as $task) {
            $task = $this->setTaskFields($task, true);
        }

        return $tasks;
    }

    /**
     * Partially constructs a Record object for queries required by both CronTasks::getAllTaskRun() and
     * CronTasks::deleteTaskRun()
     *
     * @return Record The partially constructed query Record object
     */
    private function getAllTaskRuns()
    {
        $fields = [
            'cron_tasks.*',
            'cron_task_runs.id' => 'task_run_id',
            'cron_task_runs.company_id',
            'cron_task_runs.time',
            'cron_task_runs.interval',
            'cron_task_runs.enabled',
            'cron_task_runs.date_enabled'
        ];

        $this->Record->select($fields)
            ->from('cron_task_runs')
            ->innerJoin('cron_tasks', 'cron_tasks.id', '=', 'cron_task_runs.task_id', false);

        return $this->Record;
    }

    /**
     * Sets the real name and description values, including language defines, for
     * the given task by reference
     *
     * @param stdClass $task A cron task object containing:
     *
     *  - name The name of the cron task
     *  - description The description of the cron task
     *  - is_lang 1 if the name and description are language definitions, or 0 otherwise
     */
    private function setLanguage(&$task)
    {
        // Set name and description to language define
        $task->real_name = $task->name;
        $task->real_description = $task->description;

        if ($task->is_lang == '1') {
            $name = $this->_($task->name);
            $description = $this->_($task->description);

            // Retrieve the translated definitions from the module/plugin
            if (!empty($task->dir)) {
                if (property_exists($task, 'plugin')) {
                    $name = $this->PluginManager->translate($task->dir, $task->name);
                    $description = $this->PluginManager->translate($task->dir, $task->description);
                } elseif (property_exists($task, 'module')) {
                    $name = $this->ModuleManager->translate($task->dir, $task->name);
                    $description = $this->ModuleManager->translate($task->dir, $task->description);
                }
            }

            $task->real_name = $name;
            $task->real_description = $description;
        }
    }

    /**
     * Retrieves the rules for adding/editing cron task runs
     *
     * @param array $vars A list of input fields
     * @param bool $edit Trtue for edit rules, false for add rules (optional, default false)
     * @return array A list of rules
     */
    private function getTaskRunRules(array $vars, $edit = false)
    {
        $rules = [
            'enabled' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('CronTasks.!error.enabled.format')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 1],
                    'message' => $this->_('CronTasks.!error.enabled.length')
                ]
            ],
            'time' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^([0-9]{1,2}):([0-9]{2})(:([0-9]{2}))?$/'],
                    'message' => $this->_('CronTasks.!error.time.format'),
                    'post_format' => [[$this, 'dateToUtc'], 'H:i:s']
                ]
            ],
            'interval' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('CronTasks.!error.interval.format')
                ]
            ]
        ];

        // Check the cron task type is as expected, and verify IDs exist
        $cron_task = false;
        if ($edit) {
            // Validate the cron task run ID exists if editing this task
            $rules['id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'cron_task_runs'],
                    'message' => $this->_('CronTasks.!error.run_id.exists')
                ]
            ];

            // Also retrieve the cron task to verify its type
            if (!empty($vars['id'])) {
                $cron_task = $this->getTaskRun($vars['id']);
            }
        } else {
            // Check that the cron task ID exists
            $rules['task_id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'cron_tasks'],
                    'message' => $this->_('CronTasks.!error.id.exists')
                ]
            ];

            // Also retrieve the cron task to verify its type
            if (!empty($vars['task_id'])) {
                $cron_task = $this->get($vars['task_id']);
            }
        }

        // Require a specific cron task type for the cron task run
        if ($cron_task) {
            if ($cron_task->type == 'time') {
                // Require time to be set
                unset($rules['time']['format']['if_set'], $rules['interval']);
            } else {
                // Require interval to be set
                unset($rules['interval']['format']['if_set'], $rules['time']);
            }
        }

        return $rules;
    }

    /**
     * Retrieves the rules for adding/editing cron tasks
     *
     * @param array $vars A list of input fields
     * @param bool $edit True for edit rules, false for add rules (optional, default false)
     * @return array A list of rules
     */
    private function getRules(array $vars, $edit = false)
    {
        $rules = [
            'name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('CronTasks.!error.name.empty')
                ]
            ],
            'is_lang' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('CronTasks.!error.is_lang.format')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 1],
                    'message' => $this->_('CronTasks.!error.is_lang.length')
                ]
            ]
        ];

        // Validate cron task ID if editing this task
        if ($edit) {
            $rules['id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'cron_tasks'],
                    'message' => $this->_('CronTasks.!error.id.exists')
                ]
            ];
        } else {
            $id = (empty($vars['id']) ? null : $vars['id']);
            $task_type = (empty($vars['task_type']) ? null : $vars['task_type']);
            $directory = (empty($vars['dir']) ? null : $vars['dir']);

            // Add-only rules for cron tasks
            $rules['type'] = [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['time', 'interval']],
                    'message' => $this->_('CronTasks.!error.type.format')
                ]
            ];
            $rules['key'] = [
                'unique' => [
                    'rule' => function ($key) use ($task_type, $directory, $id) {
                        $this->Record->select('id')
                            ->from('cron_tasks')
                            ->where('key', '=', $key)
                            ->where('task_type', '=', $task_type)
                            ->where('dir', '=', $directory);

                        // Exclude the given cron task ID
                        if ($id != null) {
                            $this->Record->where('id', '!=', $id);
                        }

                        $num_tasks = $this->Record->numResults();

                        return ($num_tasks == 0);
                    },
                    'message' => $this->_('CronTasks.!error.key.unique')
                ],
                'length' => [
                    'rule' => ['maxLength', 64],
                    'message' => $this->_('CronTasks.!error.key.length')
                ]
            ];
            $rules['task_type'] = [
                'format' => [
                    'rule' => ['in_array', ['module', 'plugin', 'system']],
                    'message' => $this->_('CronTasks.!error.task_type.format')
                ]
            ];
            $rules['dir'] = [
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 64],
                    'message' => $this->_('CronTasks.!error.dir.length')
                ]
            ];
        }

        return $rules;
    }

    /**
     * Retrieves a list of the task types and their language
     *
     * @return array A key/value list of task types and their language
     */
    public function getTaskTypes()
    {
        return [
            'system' => $this->_('CronTasks.task_type.system'),
            'plugin' => $this->_('CronTasks.task_type.plugin'),
            'module' => $this->_('CronTasks.task_type.module')
        ];
    }
}
