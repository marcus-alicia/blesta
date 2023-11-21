<?php
/**
 * Auto Cancel Plugin
 */
class AutoCancelPlugin extends Plugin
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        Language::loadLang(
            'auto_cancel_plugin',
            null,
            PLUGINDIR . 'auto_cancel' . DS . 'language' . DS
        );
    }

    /**
     * {@inheritdoc}
     */
    public function install($plugin_id)
    {
        Loader::loadModels($this, ['CronTasks']);
        $this->addCronTasks($this->getCronTasks());
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall($plugin_id, $last_instance)
    {
        if (!isset($this->Record)) {
            Loader::loadComponents($this, ['Record']);
        }
        Loader::loadModels(
            $this,
            ['AutoCancel.AutoCancelSettings', 'CronTasks']
        );

        $settings = array_values($this->AutoCancelSettings->supportedSettings());

        // Remove settings created by this plugin
        $this->Record->from('company_settings')
            ->where('company_id', '=', Configure::get('Blesta.company_id'))
            ->where('key', 'in', $settings)
            ->delete();

        $cron_tasks = $this->getCronTasks();

        if ($last_instance) {
            // Remove the cron tasks
            foreach ($cron_tasks as $task) {
                $cron_task = $this->CronTasks
                    ->getByKey($task['key'], $task['dir'], $task['task_type']);
                if ($cron_task) {
                    $this->CronTasks->deleteTask($cron_task->id, $task['task_type'], $task['dir']);
                }
            }
        }

        // Remove individual cron task runs
        foreach ($cron_tasks as $task) {
            $cron_task_run = $this->CronTasks
                ->getTaskRunByKey($task['key'], $task['dir'], false, $task['task_type']);
            if ($cron_task_run) {
                $this->CronTasks->deleteTaskRun($cron_task_run->task_run_id);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cron($key)
    {
        if ($key === 'schedule_cancellation') {
            $this->scheduleCancellationCron(Configure::get('Blesta.company_id'));
        }
    }

    /**
     * Schedules cancellation
     *
     * @param int $company_id
     */
    private function scheduleCancellationCron($company_id)
    {
        Loader::loadModels(
            $this,
            [
                'AutoCancel.AutoCancelSettings',
                'AutoCancel.AutoCancelServices',
                'Services'
            ]
        );

        $settings = $this->AutoCancelSettings->getSettings($company_id);

        if (array_key_exists('schedule_days', $settings)
            && array_key_exists('cancel_days', $settings)
        ) {
            $this->AutoCancelServices->scheduleCancellation(
                $this->Services,
                $company_id,
                $settings['schedule_days'],
                $settings['cancel_days']
            );
        }
    }

    /**
     * Retrieves cron tasks available to this plugin along with their default values
     *
     * @return array A list of cron tasks
     */
    private function getCronTasks()
    {
        return [
            // Cron task to check for incoming email tickets
            [
                'key' => 'schedule_cancellation',
                'dir' => 'auto_cancel',
                'task_type' => 'plugin',
                'name' => Language::_(
                    'AutoCancelPlugin.getCronTasks.schedule_cancellation_name',
                    true
                ),
                'description' => Language::_(
                    'AutoCancelPlugin.getCronTasks.schedule_cancellation_desc',
                    true
                ),
                'type' => 'time',
                'type_value' => '12:00:00',
                'enabled' => 1
            ]
        ];
    }

    /**
     * Attempts to add new cron tasks for this plugin
     *
     * @param array $tasks A list of cron tasks to add
     */
    private function addCronTasks(array $tasks)
    {
        foreach ($tasks as $task) {
            $task_id = $this->CronTasks->add($task);

            if (!$task_id) {
                $cron_task = $this->CronTasks->getByKey(
                    $task['key'],
                    $task['dir'],
                    $task['task_type']
                );
                if ($cron_task) {
                    $task_id = $cron_task->id;
                }
            }

            if ($task_id) {
                $task_vars = ['enabled' => $task['enabled']];
                if ($task['type'] === 'interval') {
                    $task_vars['interval'] = $task['type_value'];
                } else {
                    $task_vars['time'] = $task['type_value'];
                }

                $this->CronTasks->addTaskRun($task_id, $task_vars);
            }
        }
    }
}
