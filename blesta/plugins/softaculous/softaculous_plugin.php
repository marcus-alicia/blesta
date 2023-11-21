<?php
use Blesta\Core\Util\Common\Traits\Container;
class SoftaculousPlugin extends Plugin
{
    // Load traits
    use Container;
    public function __construct()
    {
        Language::loadLang('softaculous_plugin', null, dirname(__FILE__) . DS . 'language' . DS);
        Loader::loadComponents($this, ['Input', 'Record']);

        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
        Configure::load('softaculous', dirname(__FILE__) . DS . 'config' . DS);
    }

    /**
     * Performs any necessary bootstraping actions
     *
     * @param int $plugin_id The ID of the plugin being installed
     */
    public function install($plugin_id)
    {
        // Add all support tables, *IFF* not already added
        try {
            // Add new table to store services with failed installation attempts
            $this->Record->
                setField('service_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('errors', ['type' => 'mediumtext', 'nullable' => true, 'default' => null])->
                setField('attempts', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setKey(['service_id'], 'primary')->
                create('softaculous_queued_services', true);
        } catch (Exception $e) {
            // Error adding... no permission?
            $this->Input->setErrors(['db' => ['create' => $e->getMessage()]]);
            return;
        }

        // Add cron tasks
        $this->addCronTasks($this->getCronTasks());

        return $plugin_id;
    }

    /**
     * Returns all events to be registered for this plugin (invoked after install() or upgrade(),
     * overwrites all existing events)
     *
     * @return array A numerically indexed array containing:
     *
     *  - event The event to register for
     *  - callback A string or array representing a callback function or class/method. If a user (e.g.
     *      non-native PHP) function or class/method, the plugin must automatically define it when the plugin is loaded.
     *      To invoke an instance methods pass "this" instead of the class name as the 1st callback element.
     */
    public function getEvents()
    {
        return [
            [
                'event' => 'Services.add',
                'callback' => ['this', 'softAutoInstall']
            ],
            [
                'event' => 'Services.edit',
                'callback' => ['this', 'softAutoInstall']
            ]
            // Add multiple events here
        ];
    }

    /**
     * Retrieves cron tasks available to this plugin along with their default values
     *
     * @return array A list of cron tasks
     */
    private function getCronTasks()
    {
        return [
            // Cron task to attempt script installations
            [
                'key' => 'script_installation',
                'task_type' => 'plugin',
                'dir' => 'softaculous',
                'name' => Language::_('SoftaculousPlugin.cron.script_installation_name', true),
                'description' => Language::_('SoftaculousPlugin.cron.script_installation_desc', true),
                'type' => 'interval',
                'type_value' => 5,
                'enabled' => 1
            ]
        ];
    }

    /**
     * Execute the cron task
     *
     * @param string $key The cron task to execute
     */
    public function cron($key)
    {
        switch ($key) {
            case 'script_installation':
                $this->cronScriptInstallation();
                break;
            default:
                break;
        }
    }

    /**
     * Attempt queued script installations
     */
    private function cronScriptInstallation()
    {
        Loader::loadModels($this, ['Softaculous.SoftaculousQueuedServices']);
        $queued_services = $this->SoftaculousQueuedServices->getAll(Configure::get('Blesta.company_id'));

        // Attempt installation script for each queued service
        foreach ($queued_services as $queued_service) {
            $this->runInstaller($queued_service->service_id);
        }
    }

    /**
     * Performs migration of data from $current_version (the current installed version)
     * to the given file set version
     *
     * @param string $current_version The current installed version of this plugin
     * @param int $plugin_id The ID of the plugin being upgraded
     */
    public function upgrade($current_version, $plugin_id)
    {
        // Upgrade if possible
        if (version_compare($this->getVersion(), $current_version, '>')) {
            // Handle the upgrade, set errors using $this->Input->setErrors() if any errors encountered

            // Upgrade to 1.2.0
            if (version_compare($current_version, '1.2.0', '<')) {
                // Add new cron task to auto-close open tickets
                $cron_tasks = $this->getCronTasks();
                $install_task = null;
                foreach ($cron_tasks as $task) {
                    if ($task['key'] == 'script_installation') {
                        $install_task = $task;
                        break;
                    }
                }

                if ($install_task) {
                    $this->addCronTasks([$install_task]);
                }

                // Add new table to store services with failed installation attempts
                $this->Record->
                    setField('service_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                    setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                    setField('errors', ['type' => 'mediumtext', 'is_null' => true, 'default' => null])->
                    setField('attempts', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                    setKey(['service_id'], 'primary')->
                    create('softaculous_queued_services', true);
            }
        }
    }

    /**
     * Runs an installation script for a newly activated service
     *
     * @param Event $event
     */
    public function softAutoInstall($event)
    {
        $par = $event->getParams();

        // Make sure the service is being activated at this time
        $service_activated = isset($par['vars']['status'])
            && $par['vars']['status'] == 'active'
            && ($event->getName() == 'Services.add'
                || ($event->getName() == 'Services.edit'
                    && in_array($par['old_service']->status, ['pending', 'in_review'])
                )
            );

        if ($service_activated) {
            $this->runInstaller($par['service_id']);
        }
    }

    /**
     * Run the installer for the given service and class
     *
     * @param int $service_id The ID of the service for which to run the script
     * @throws Exception
     */
    private function runInstaller($service_id)
    {
        Loader::loadModels($this, ['Services', 'ModuleManager', 'Softaculous.SoftaculousQueuedServices']);

        // Fetch necessary data
        $service = $this->Services->get($service_id);
        $module_row = $this->ModuleManager->getRow($service->module_row_id);

        // Get module info
        $module_info = $this->getModuleClassByPricingId($service->pricing_id);

        // This plugin only supports the following modules: cPanel, CentOS Web Panel, Plesk, InterWorx and ISPmanager
        $accepted_modules = ['cpanel', 'centoswebpanel', 'plesk', 'direct_admin', 'interworx', 'ispmanager'];
        if (!$module_info || !in_array(strtolower($module_info->class), $accepted_modules)) {
            // Remove the service from the queue since it is invalid
            $this->SoftaculousQueuedServices->delete($service_id);
            return;
        }

        try {
            // Load the installer for this module
            $installer = $this->loadInstaller(strtolower($module_info->class));

            // Run the installation script
            $installer->install($service, $module_row->meta);
            if (($errors = $installer->errors())) {
                $queued_service = $this->SoftaculousQueuedServices->get($service_id);
                if (!$queued_service) {
                    // Create a record of the failed service and module so we can re-attempt installation later
                    $this->SoftaculousQueuedServices->add([
                        'service_id' => $service_id,
                        'company_id' => $service->package->company_id,
                        'errors' => serialize($errors),
                        'attempts' => 1,
                    ]);
                } else {
                    $queued_service->attempts++;
                    if ($queued_service->attempts > Configure::get('Softaculous.max_attempts')) {
                        $this->SoftaculousQueuedServices->delete($service_id);
                    } else {
                        // Update the errors and increment the attempt
                        $this->SoftaculousQueuedServices->edit(
                            $service_id,
                            [
                                'errors' => serialize($errors),
                                'attempts' => $queued_service->attempts,
                            ]
                        );
                    }

                }
            } else {
                // Remove the service since we succeeded
                $this->SoftaculousQueuedServices->delete($service_id);
                $logger = $this->getFromContainer('logger');
                $logger->info(Language::_('SoftaculousPlugin.installation_success', true, $service_id));
            }
        } catch (Throwable $e) {
            throw new Exception(Language::_('SoftaculousPlugin.library_error', true));
        } catch (Exception $e) {
            throw new Exception(Language::_('SoftaculousPlugin.library_error', true));
        }
    }

    /**
     * Returns info regarding the module belonging to the given $package_pricing_id
     *
     * @param int $package_pricing_id The package pricing ID to fetch the module of
     * @return mixed A stdClass object containing module info and the package
     *  ID belonging to the given $package_pricing_id, false if no such module exists
     */
    private function getModuleClassByPricingId($package_pricing_id)
    {
        return $this->Record->select(['modules.*', 'packages.id' => 'package_id'])->from('package_pricing')->
            innerJoin('packages', 'packages.id', '=', 'package_pricing.package_id', false)->
            innerJoin('modules', 'modules.id', '=', 'packages.module_id', false)->
            where('package_pricing.id', '=', $package_pricing_id)->
            fetch();
    }

    /**
     * Loads the given library into this object
     *
     * @param string $panel_name The panel to load an installer for
     * @return SoftaculousInstaller
     */
    private function loadInstaller($panel_name)
    {
        $class_name = str_replace('_', '', ucwords($panel_name)) . 'Installer';
        if (isset($this->{$class_name})) {
            return $this->{$class_name};
        }

        $file_name = dirname(__FILE__) . DS . 'lib' . DS . $panel_name . '_installer.php';

        if (file_exists($file_name)) {
            // Load the library requested
            include_once $file_name;

            $logger = $this->getFromContainer('logger');
            $this->{$class_name} = new $class_name($logger);
            return $this->{$class_name};
        } else {
            throw new Exception(Language::_('SoftaculousPlugin.library_error', true));
        }
    }

    /**
     * Attempts to add new cron tasks for this plugin
     *
     * @param array $tasks A list of cron tasks to add
     * @see SoftaculousPlugin::install()
     */
    private function addCronTasks(array $tasks)
    {
        Loader::loadModels($this, ['CronTasks']);
        foreach ($tasks as $task) {
            $task_id = $this->CronTasks->add($task);

            if (!$task_id) {
                $cron_task = $this->CronTasks->getByKey($task['key'], $task['dir'], $task['task_type']);
                if ($cron_task) {
                    $task_id = $cron_task->id;
                }
            }

            if ($task_id) {
                $task_vars = ['enabled' => $task['enabled']];
                if ($task['type'] == 'interval') {
                    $task_vars['interval'] = $task['type_value'];
                } else {
                    $task_vars['time'] = $task['type_value'];
                }

                $this->CronTasks->addTaskRun($task_id, $task_vars);
            }
        }
    }
}
