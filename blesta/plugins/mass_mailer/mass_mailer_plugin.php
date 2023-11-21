<?php

use Blesta\MassMailer\Cron\Email;
use Blesta\MassMailer\Cron\Export;

/**
 * Mass Mailer plugin handler
 *
 * @package blesta
 * @subpackage blesta.plugins.mass_mailer
 * @copyright Copyright (c) 2016, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class MassMailerPlugin extends Plugin
{
    /**
     * Load dependencies
     */
    public function __construct()
    {
        Language::loadLang('mass_mailer_plugin', null, dirname(__FILE__) . DS . 'language' . DS);

        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        Loader::loadComponents($this, ['Input', 'Record']);
    }

    /**
     * Run the cron task
     *
     * @param string $key The cron task key
     */
    public function cron($key)
    {
        // Create the upload directory if it does not already exist
        $this->createUploadDirectory();

        if ($key === 'export') {
            $export = new Export();
            $export->setColumns($this->getConfig('export'));
            $export->run();
        } elseif ($key === 'mass_mail') {
            $email = new Email();
            $email->run();
        }
    }

    /**
     * Returns all actions to be configured for this widget (invoked after install()
     * or upgrade(), overwrites all existing actions)
     *
     * @return array A numerically indexed array containing:
     *  - action The action to register for
     *  - uri The URI to be invoked for the given action
     *  - name The name to represent the action
     *  - options An array of options (optional)
     */
    public function getActions()
    {
        return [
            [
                'action' => 'nav_secondary_staff',
                'uri' => 'plugin/mass_mailer/admin_main/',
                'name' => 'MassMailerPlugin.nav_secondary_staff.admin_main',
                'options' => ['parent' => 'tools/']
            ]
        ];
    }

    /**
     * Performs any necessary bootstraping actions
     *
     * @param int $plugin_id The ID of the plugin being installed
     */
    public function install($plugin_id)
    {
        $methods = [
            'createUploadDirectory' => [],
            'addTables' => [],
            'addPermissions' => [$plugin_id],
            'addCronTasks' => []
        ];

        foreach ($methods as $method => $params) {
            // Attempt to perform the installation task
            if (!call_user_func_array([$this, $method], $params)) {
                return;
            }
        }
    }

    /**
     * Performs any necessary cleanup actions
     *
     * @param int $plugin_id The ID of the plugin being uninstalled
     * @param bool $last_instance True if $plugin_id is the last instance across
     *  all companies for this plugin, false otherwise
     */
    public function uninstall($plugin_id, $last_instance)
    {
        // Always remove the permissions on uninstall for this plugin
        $uninstall = true;
        $methods = [
            'addPermissions' => [$plugin_id, $uninstall],
            'addCronTasks' => [$uninstall, $last_instance]
        ];

        // Only remove the tables if all instances have been uninstalled
        if ($last_instance) {
            $methods['addTables'] = [$uninstall];
        }

        foreach ($methods as $method => $params) {
            // Attempt to perform the uninstallation task
            if (!call_user_func_array([$this, $method], $params)) {
                return;
            }
        }
    }

    /**
     * Creates the upload directory for the mass mailer on the file system
     * May set Input errors on failure
     *
     * @return bool True on success, false on failure
     */
    protected function createUploadDirectory()
    {
        // Set the uploads directory
        Loader::loadComponents($this, ['SettingsCollection', 'Upload']);
        $temp = $this->SettingsCollection->fetchSetting(
            null,
            Configure::get('Blesta.company_id'),
            'uploads_dir'
        );
        $upload_path = $temp['value'] . Configure::get('Blesta.company_id')
            . DS . 'mass_mailer_files' . DS;

        // Create the upload path if it doesn't already exist
        $this->Upload->createUploadPath($upload_path, 0777);

        if ($this->Upload->errors()) {
            $this->Input->setErrors($this->Upload->errors());
            return false;
        }

        return true;
    }

    /**
     * Retrieves the JSON config file
     *
     * @param string $filename The name of the file, minus the extension (must be .json)
     * @return mixed An stdClass object representing the contents of the file, otherwise false
     */
    protected function getConfig($filename)
    {
        // Load the file
        $file = dirname(__FILE__) . DS . 'config' . DS . $filename . '.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file));
        }

        return false;
    }

    /**
     * Adds permissions for this plugin. Sets Input errors on failure
     *
     * @param int $plugin_id The ID of the plugin
     * @param bool $undo True to remove the permissions, otherwise adds them
     * @return bool True on success, false on failure
     */
    private function addPermissions($plugin_id, $undo = false)
    {
        Loader::loadModels($this, ['Permissions']);
        $controllers = ['admin_compose', 'admin_filter', 'admin_main'];

        if ($undo) {
            // Remove the permissions
            foreach ($controllers as $controller) {
                $permission = $this->Permissions->getByAlias('mass_mailer.' . $controller, $plugin_id);
                if ($permission) {
                    $this->Permissions->delete($permission->id);
                }
            }
        } else {
            // Add a new permission for the mass mailer
            $group = $this->Permissions->getGroupByAlias('admin_tools');

            foreach ($controllers as $controller) {
                $perm = [
                    'plugin_id' => $plugin_id,
                    'group_id' => $group->id,
                    'name' => Language::_('MassMailerPlugin.permission.' . $controller, true),
                    'alias' => 'mass_mailer.' . $controller,
                    'action' => '*'
                ];
                $this->Permissions->add($perm);

                if (($errors = $this->Permissions->errors())) {
                    $this->Input->setErrors($errors);
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Adds the database tables for this plugin. Sets Input errors on failure
     *
     * @param int $plugin_id The ID of the plugin
     * @param bool $undo True to drop the tables, otherwise adds them
     * @return bool True on success, false on failure
     */
    private function addTables($undo = false)
    {
        try {
            // Add or remove the tables
            if ($undo) {
                $this->Record->drop('mass_mailer_jobs', true);
                $this->Record->drop('mass_mailer_tasks', true);
                $this->Record->drop('mass_mailer_emails', true);
            } else {
                $this->Record
                    ->setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
                    ->setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                    ->setField(
                        'status',
                        ['type' => 'enum', 'size' => "'pending','in_progress','complete'", 'default' => 'pending']
                    )
                    ->setField('task_count', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0])
                    ->setField('data', ['type' => 'mediumtext'])
                    ->setField('date_added', ['type' => 'datetime'])
                    ->setKey(['id'], 'primary')
                    ->setKey(['company_id'], 'index')
                    ->create('mass_mailer_jobs', true);

                $this->Record
                    ->setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
                    ->setField('job_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                    ->setField('contact_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                    ->setField(
                        'service_id',
                        ['type' => 'int', 'size' => 10, 'unsigned' => true, 'is_null' => true, 'default' => null]
                    )
                    ->setKey(['id'], 'primary')
                    ->setKey(['job_id', 'contact_id', 'service_id'], 'unique')
                    ->create('mass_mailer_tasks', true);

                $this->Record
                    ->setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
                    ->setField('job_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                    ->setField('from_name', ['type' => 'varchar', 'size' => 255])
                    ->setField('from_address', ['type' => 'varchar', 'size' => 255])
                    ->setField('subject', ['type' => 'varchar', 'size' => 255])
                    ->setField('text', ['type' => 'mediumtext', 'is_null' => true, 'default' => null])
                    ->setField('html', ['type' => 'mediumtext', 'is_null' => true, 'default' => null])
                    ->setField('log', ['type' => 'tinyint', 'size' => 1, 'unsigned' => true, 'default' => 0])
                    ->setKey(['id'], 'primary')
                    ->setKey(['job_id'], 'unique')
                    ->create('mass_mailer_emails', true);
            }
        } catch (Exception $e) {
            // Error.. no permission?
            $this->Input->setErrors(['db' => ['create' => $e->getMessage()]]);
            return false;
        }

        return true;
    }

    /**
     * Adds cron tasks
     *
     * @param bool $undo True to remove the cron tasks (default false)
     * @param bool $last_instance True if the plugin is being completely uninstalled (default false)
     * @return bool True on success, false on failure
     */
    private function addCronTasks($undo = false, $last_instance = false)
    {
        Loader::loadModels($this, ['CronTasks']);

        foreach ($this->getCronTasks() as $task) {
            if ($undo) {
                $this->deleteCronTask($task, $last_instance);
            } else {
                if (!$this->addCronTask($task)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Creates a new cron task
     * @see MassMailerPlugin::addCronTasks
     *
     * @param array $task The cron task fields to add
     * @return bool True if the cron task was added, false otherwise
     */
    private function addCronTask($task)
    {
        // Fetch the cron task if it already exists
        if (($cron_task = $this->CronTasks->getByKey($task['key'], $task['dir'], $task['task_type']))) {
            $task_id = $cron_task->id;
        } else {
            // Create the cron task
            $task_id = $this->CronTasks->add($task);

            if (($errors = $this->CronTasks->errors())) {
                $this->Input->setErrors($errors);
                return false;
            }
        }

        // Create the cron task run
        if ($task_id) {
            $task_vars = [
                'enabled' => $task['enabled'],
                $task['type'] => $task['type_value']
            ];

            $this->CronTasks->addTaskRun($task_id, $task_vars);

            if (($errors = $this->CronTasks->errors())) {
                $this->Input->setErrors($errors);
                return false;
            }
        }

        return true;
    }

    /**
     * Removes the given cron task
     *
     * @param array $task The cron task fields to remove
     * @param bool $last_instance Whether the plugin is being completely uninstalled
     */
    private function deleteCronTask($task, $last_instance = false)
    {
        // Delete the cron task run
        if (($task_run = $this->CronTasks->getTaskRunByKey($task['key'], $task['dir'], false, $task['task_type']))) {
            $this->CronTasks->deleteTaskRun($task_run->task_run_id);
        }

        // Delete the cron task only if this is the last instance
        if ($last_instance &&
            ($cron_task = $this->CronTasks->getByKey($task['key'], $task['dir'], $task['task_type']))
        ) {
            $this->CronTasks->deleteTask($cron_task->id, $task['task_type'], $task['dir']);
        }
    }

    /**
     * Retrieves a set of installable cron tasks
     *
     * @return array An array of each cron task and its fields
     */
    private function getCronTasks()
    {
        return [
            [
                'key' => 'export',
                'dir' => 'mass_mailer',
                'task_type' => 'plugin',
                'name' => Language::_('MassMailerPlugin.cron.export_name', true),
                'description' => Language::_('MassMailerPlugin.cron.export_desc', true),
                'type' => 'interval',
                'type_value' => 5,
                'enabled' => 1
            ],
            [
                'key' => 'mass_mail',
                'dir' => 'mass_mailer',
                'task_type' => 'plugin',
                'name' => Language::_('MassMailerPlugin.cron.mass_mail_name', true),
                'description' => Language::_('MassMailerPlugin.cron.mass_mail_desc', true),
                'type' => 'interval',
                'type_value' => 5,
                'enabled' => 1
            ]
        ];
    }
}
