<?php
/**
 * TheSSLStore plugin handler
 */
class ThesslstorePlugin extends Plugin
{
    /**
     * Init
     */
    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
        Language::loadLang("thesslstore_plugin", null, dirname(__FILE__) . DS . "language" . DS);
    }

    /**
     * Returns the name of this plugin
     *
     * @return string The common name of this plugin
     */
    public function getName()
    {
        return Language::_("TheSSLStorePlugin.name", true);
    }

    /**
     * Performs any necessary bootstraping actions
     *
     * @param int $plugin_id The ID of the plugin being installed
     */
    public function install($plugin_id)
    {

        Loader::loadModels($this, array('CronTasks'));
        $this->addCronTasks($this->getCronTasks());
        // Create Email group for expiration reminder email
        Loader::loadModels($this, array("Emails", "EmailGroups", "Languages", "Permissions"));
        $group = array(
                'action' => "Thesslstore.expiration_reminder",
                'type' => "client",
                'plugin_dir' => "thesslstore",
                'tags' => "{client.first_name},{service.name},{service.id},{servicelink},{days}"
        );
        // Add the custom group
        $group_id = $this->EmailGroups->add($group);
        // Fetch all currently-installed languages for this company, for which email templates should be created for
        $languages = $this->Languages->getAll(Configure::get("Blesta.company_id"));
        // Add the email template for each language
        foreach ($languages as $language)
        {
            $data = array(
                'email_group_id' => $group_id,
                'company_id' => Configure::get('Blesta.company_id'),
                'lang' => $language->code,
                'from' => 'no-reply@mydomain.com',
                'from_name' => 'My Company',
                'subject' => 'Certificate Expiration Reminder',
                'text' => 'Hello {client.first_name},
                This is a reminder that your SSL Certificate ({service.name}) with service ID#{service.id} (http://{servicelink}) is set to expire in {days} days.
                Please renew at your earliest convenience.
                Thank you!',
                'html' => '<p>Hello {client.first_name},</p>
                <p>This is a reminder that your SSL Certificate ({service.name}) with service ID#{service.id} (<a href="http://{servicelink}">{servicelink}</a>) is set to expire in {days} days.</p>
                <p>Please renew at your earliest convenience.</p>
                <p>Thank you!</p>'
            );
            $this->Emails->add($data);
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

            // Upgrade to 1.2.2
            if (version_compare($current_version, '1.2.2', '<')) {
                $this->upgrade1_2_2();
            }

            // Upgrade to 1.3.0
            if (version_compare($current_version, '1.3.0', '<')) {
                $this->upgrade1_3_0();
            }
        }
    }

    /**
     * Upgrades to v1.2.2 of the plugin
     * Changes email template tags, but leaves existing HTML/text content alone otherwise
     */
    private function upgrade1_2_2()
    {
        // Update all email templates to change the tags
        Loader::loadComponents($this, array('Record'));
        Loader::loadModels($this, array('EmailGroups'));

        if (($email_group = $this->EmailGroups->getByAction('Thesslstore.expiration_reminder'))) {
            // Update the email template tags to change the package.name to service.name
            $tags = '{client.first_name},{service.name},{service.id},{servicelink},{days}';
            $this->Record->where('id', '=', $email_group->id)
                ->update('email_groups', array('tags' => $tags));

            $emails = $this->Record->select(array('id', 'text', 'html'))
                ->from('emails')
                ->where('email_group_id', '=', $email_group->id)
                ->fetchAll();

            // Set HTTP protocol for servicelink tag and replace the package.name tag in all emails
            foreach ($emails as $email) {
                $search = array('{servicelink}', '{package.name}', '{package.');
                $replace = array('http://{servicelink}', '{service.name}', '{service.package.');
                $vars = array(
                    'text' => str_replace($search, $replace, $email->text),
                    'html' => str_replace($search, $replace, $email->html)
                );

                if ($vars['html'] != $email->html || $vars['text'] != $email->text) {
                    $this->Record->where('id', '=', $email->id)->update('emails', $vars);
                }
            }
        }
    }

    /**
     * Upgrades to v1.3.0 of the plugin
     * Removes service synchronization cron task that was moved to module
     */
    private function upgrade1_3_0()
    {
        Loader::loadModels($this, ['CronTasks']);

        // Remove the cron task
        $cron_task = $this->CronTasks->getByKey('tss_order_sync', 'thesslstore', 'plugin');
        if ($cron_task) {
            $this->CronTasks->deleteTask($cron_task->id, 'plugin', 'thesslstore');
        }
    }

    /**
     * Performs any necessary cleanup actions
     *
     * @param int $plugin_id The ID of the plugin being uninstalled
     * @param boolean $last_instance True if $plugin_id is the last instance across all companies for this plugin, false otherwise
     */
    public function uninstall($plugin_id, $last_instance)
    {
        Loader::loadModels($this, array("Emails", "EmailGroups", "Languages"));
        // Fetch the email template created by this plugin
        $group = $this->EmailGroups->getByAction('Thesslstore.expiration_reminder');

        // Delete all emails templates belonging to this plugin's email group and company
        if ($group) {
            $this->Emails->deleteAll($group->id, Configure::get("Blesta.company_id"));
            if ($last_instance)
                $this->EmailGroups->delete($group->id);
        }


        if (!isset($this->Record)) {
            Loader::loadComponents($this, array('Record'));
        }
        Loader::loadModels(
            $this,
            array('CronTasks')
        );

        $cron_tasks = $this->getCronTasks();

        if ($last_instance) {
            // Remove the cron tasks
            foreach ($cron_tasks as $task) {
                $cron_task = $this->CronTasks->getByKey($task['key'], $task['dir'], $task['task_type']);
                if ($cron_task) {
                    $this->CronTasks->deleteTask($cron_task->id, $task['task_type'], $task['dir']);
                }
            }
        }

        // Remove individual cron task runs
        foreach ($cron_tasks as $task) {
            $cron_task_run = $this->CronTasks->getTaskRunByKey($task['key'], $task['dir'], false, $task['task_type']);
            if ($cron_task_run) {
                $this->CronTasks->deleteTaskRun($cron_task_run->task_run_id);
            }
        }
    }

    public function cron($key)
    {
        if ($key == 'tss_expiration_reminder') {
            $this->certificateExpirationReminder();
        }
    }

    /**
     * Certificate Expiration Reminder function
     */
    private function certificateExpirationReminder()
    {
        Loader::loadModels($this, array('Clients', 'Emails', 'Services'));
        Loader::loadHelpers($this, array('Date', 'Html'));
        $this->Date->setTimezone('UTC', Configure::get('Blesta.company_timezone'));

        $now = date('c');
        $notice_days = 30;
        $filters = array(
            'renew_start_date' => $this->Clients->dateToUtc(
                $this->Date->modify($now, '+' . $notice_days . ' days', 'Y-m-d 00:00:00')
            ),
            'renew_end_date' => $this->Clients->dateToUtc(
                $this->Date->modify($now, '+' . $notice_days . ' days', 'Y-m-d 23:59:59')
            )
        );

        // Get the company hostname
        $hostname = isset(Configure::get('Blesta.company')->hostname)
            ? Configure::get('Blesta.company')->hostname
            : '';
        $client_uri = $hostname . $this->getWebDirectory() . Configure::get('Route.client') . '/';

        // Fetch all SSL Store module active/suspended services
        $services = $this->getAllServiceIds($filters);

        // Send the expiry notices
        foreach($services as $service) {
            // Fetch the service
            if (!($service_obj = $this->Services->get($service->id))) {
                continue;
            }

            // Fetch the client to email
            if (!($client = $this->Clients->get($service_obj->client_id))) {
                continue;
            }

            // Send the email
            $tags = array(
                'client' => $client,
                'service' => $service_obj,
                'servicelink' => $this->Html->safe($client_uri . 'services/manage/' . $service_obj->id . '/'),
                'days' => $notice_days
            );

            $this->Emails->send(
                'Thesslstore.expiration_reminder',
                Configure::get('Blesta.company_id'),
                $client->settings['language'],
                $client->email,
                $tags,
                null,
                null,
                null,
                array('to_client_id' => $client->id)
            );
        }
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
                $task_vars = array('enabled' => $task['enabled']);
                if ($task['type'] === "time") {
                    $task_vars['time'] = $task['type_value'];
                } else {
                    $task_vars['interval'] = $task['type_value'];
                }

                $this->CronTasks->addTaskRun($task_id, $task_vars);
            }
        }
    }

    /**
     * Retrieves cron tasks available to this plugin along with their default values
     *
     * @return array A list of cron tasks
     */
    private function getCronTasks()
    {
        return array(
            array(
                'key' => 'tss_expiration_reminder',
                'dir' => 'thesslstore',
                'task_type' => 'plugin',
                'name' => Language::_('TheSSLStorePlugin.getCronTasks.tss_expiration_reminder_name', true),
                'description' => Language::_('TheSSLStorePlugin.getCronTasks.tss_expiration_reminder_desc', true),
                'type' => 'time',
                'type_value' => '00:00:00',
                'enabled' => 1
            )
        );
    }

    /**
     * Retrieves a list of all service IDs representing active/suspended SSL Store module services for this company
     *
     * @param array $filters An array of filter options including:
     *  - renew_start_date The service's renew date to search from
     *  - renew_end_date The service's renew date to search to
     * @return array A list of stdClass objects containing:
     *  - id The ID of the service
     */
    private function getAllServiceIds(array $filters = array())
    {
        Loader::loadComponents($this, array('Record'));

        $this->Record->select(array('services.id'))
            ->from('services')
                ->on('service_fields.key', '=', 'thesslstore_order_id')
            ->innerJoin('service_fields', 'service_fields.service_id', '=', 'services.id', false)
            ->innerJoin('clients', 'clients.id', '=', 'services.client_id', false)
            ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)
            ->where('services.status', 'in', array('active', 'suspended'))
            ->where('client_groups.company_id', '=', Configure::get('Blesta.company_id'));

        if (!empty($filters['renew_start_date'])) {
            $this->Record->where('services.date_renews', '>=', $filters['renew_start_date']);
        }

        if (!empty($filters['renew_end_date'])) {
            $this->Record->where('services.date_renews', '<=', $filters['renew_end_date']);
        }

        return $this->Record->group(array('services.id'))
            ->fetchAll();
    }

    /**
     * Retrieves the web directory
     *
     * @return string The web directory
     */
    private function getWebDirectory()
    {
        $webdir = WEBDIR;
        $is_cli = (empty($_SERVER['REQUEST_URI']));

        // Set default webdir if running via CLI
        if ($is_cli) {
            Loader::loadModels($this, ['Settings']);
            $root_web = $this->Settings->getSetting('root_web_dir');
            if ($root_web) {
                $webdir = str_replace(DS, '/', str_replace(rtrim($root_web->value, DS), '', ROOTWEBDIR));

                if (!HTACCESS) {
                    $webdir .= 'index.php/';
                }
            }
        }

        return $webdir;
    }
}
