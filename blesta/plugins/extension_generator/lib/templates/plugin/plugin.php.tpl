<?php
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * {{name}} plugin handler
 *{{array:authors}}
 * @link {{authors.url}} {{authors.name}}{{array:authors}}
 */
class {{class_name}}Plugin extends Plugin
{
    public function __construct()
    {
        // Load components required by this plugin
        Loader::loadComponents($this, ['Input', 'Record']);

        Language::loadLang('{{snake_case_name}}_plugin', null, dirname(__FILE__) . DS . 'language' . DS);
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
    }

    /**
     * Performs any necessary bootstraping actions
     *
     * @param int $plugin_id The ID of the plugin being installed
     */
    public function install($plugin_id)
    {{{function:installTables}}
        try {
////        Plugins manage their own database interactions.  Here the table are define, and they are removed in the
////        uninstall() method.  The Record component is used for all database interactions and can be found under
////        vendors/minphp/record/src/Record.php
////
////        Table creation has been automatically generater for you, but you may find it useful

            // Create database tables{{array:tables}}
            // {{tables.name}}
            $this->Record{{array:tables.columns}}
                ->setField(
                    '{{tables.columns.name}}',
                    [
                        'type' => '{{tables.columns.type}}',{{if:tables.columns.length:}}{{else:tables.columns.length}}
                        'size' => "{{tables.columns.length}}",{{endif:tables.columns.length}}{{if:tables.columns.type:INT}}
                        'unsigned' => true,{{if:tables.columns.primary:true}}
                        'auto_increment' => true,{{endif:tables.columns.primary}}{{endif:tables.columns.type}}{{if:tables.columns.nullable:true}}
                        'is_null' => true,{{endif:tables.columns.nullable}}{{if:tables.columns.default:}}{{if:tables.columns.nullable:true}}
                        'default' => null,{{endif:tables.columns.nullable}}{{else:tables.columns.default}}
                        'default' => {{tables.columns.default}},{{endif:tables.columns.default}}
                    ]
                ){{if:tables.columns.primary:true}}
                ->setKey(['{{tables.columns.name}}'], 'primary'){{endif:tables.columns.primary}}{{array:tables.columns}}
                ->create('{{tables.name}}', true);
                {{array:tables}}
        } catch (Exception $e) {
            // Error adding... no permission?
            $this->Input->setErrors(['db' => ['create' => $e->getMessage()]]);
            return;
        }{{function:installTables}}{{function:addCronTasks}}{{function:getCronTasks}}

        // Add cron tasks for this plugin
        $this->addCronTasks($this->getCronTasks());{{function:getCronTasks}}{{function:addCronTasks}}

////        // Fetch all currently-installed languages for this company, for which email templates should be created for
////        $languages = $this->Languages->getAll(Configure::get('Blesta.company_id'));
////
////        // Add all email templates
////        $emails = Configure::get('{{class_name}}.install.emails');
////        foreach ($emails as $email) {
////            $group = $this->EmailGroups->getByAction($email['action']);
////            if ($group) {
////                $group_id = $group->id;
////            } else {
////                $group_id = $this->EmailGroups->add([
////                    'action' => $email['action'],
////                    'type' => $email['type'],
////                    'plugin_dir' => $email['plugin_dir'],
////                    'tags' => $email['tags']
////                ]);
////            }
////
////            // Set from hostname to use that which is configured for the company
////            if (isset(Configure::get('Blesta.company')->hostname)) {
////                $email['from'] = str_replace(
////                    '@mydomain.com',
////                    '@' . Configure::get('Blesta.company')->hostname,
////                    $email['from']
////                );
////            }
////
////            // Add the email template for each language
////            foreach ($languages as $language) {
////                $this->Emails->add([
////                    'email_group_id' => $group_id,
////                    'company_id' => Configure::get('Blesta.company_id'),
////                    'lang' => $language->code,
////                    'from' => $email['from'],
////                    'from_name' => $email['from_name'],
////                    'subject' => $email['subject'],
////                    'text' => $email['text'],
////                    'html' => $email['html']
////                ]);
////            }
////        }
    }

    /**
     * Performs any necessary cleanup actions
     *
     * @param int $plugin_id The ID of the plugin being uninstalled
     * @param bool $last_instance True if $plugin_id is the last instance across
     *  all companies for this plugin, false otherwise
     */
    public function uninstall($plugin_id, $last_instance)
    {{{function:getCronTasks}}
        Loader::loadModels($this, ['CronTasks']);

        // Fetch the cron tasks for this plugin
        $cron_tasks = $this->getCronTasks();
{{function:getCronTasks}}
        if ($last_instance) {
            {{function:installTables}}try {
                // Remove database tables{{array:tables}}
                $this->Record->drop('{{tables.name}}');{{array:tables}}
            } catch (Exception $e) {
                // Error dropping... no permission?
                $this->Input->setErrors(['db' => ['create' => $e->getMessage()]]);
                return;
            }{{function:installTables}}{{function:getCronTasks}}

            // Remove the cron tasks
            foreach ($cron_tasks as $task) {
                $cron_task = $this->CronTasks->getByKey($task['key'], $task['dir'], $task['task_type']);
                if ($cron_task) {
                    $this->CronTasks->deleteTask($cron_task->id, $task['task_type'], $task['dir']);
                }
            }{{function:getCronTasks}}
        }{{function:getCronTasks}}

        // Remove individual cron task runs
        foreach ($cron_tasks as $task) {
            $cron_task_run = $this->CronTasks->getTaskRunByKey($task['key'], $task['dir'], false, $task['task_type']);
            if ($cron_task_run) {
                $this->CronTasks->deleteTaskRun($cron_task_run->task_run_id);
            }
        }{{function:getCronTasks}}
////
////
////        Loader::loadModels($this, ['Emails', 'EmailGroups']);
////        Configure::load('{{snake_case_name}}', dirname(__FILE__) . DS . 'config' . DS);
////
////        $emails = Configure::get('{{class_name}}.install.emails');
////        // Remove emails and email groups as necessary
////        foreach ($emails as $email) {
////            // Fetch the email template created by this plugin
////            $group = $this->EmailGroups->getByAction($email['action']);
////
////            // Delete all emails templates belonging to this plugin's email group and company
////            if ($group) {
////                $this->Emails->deleteAll($group->id, Configure::get('Blesta.company_id'));
////
////                if ($last_instance) {
////                    $this->EmailGroups->delete($group->id);
////                }
////            }
////        }
    }{{function:addCronTasks}}

    /**
     * Attempts to add new cron tasks for this plugin
     *
     * @param array $tasks A list of cron tasks to add
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
                if ($task['type'] === 'time') {
                    $task_vars['time'] = $task['type_value'];
                } else {
                    $task_vars['interval'] = $task['type_value'];
                }

                $this->CronTasks->addTaskRun($task_id, $task_vars);
            }
        }
    }{{function:addCronTasks}}{{function:getCronTasks}}

    /**
     * Retrieves cron tasks available to this plugin along with their default values
     *
     * @return array A list of cron tasks
     */
    private function getCronTasks()
    {
        return [{{array:cron_tasks}}
            [
                'key' => '{{cron_tasks.name}}',
                'task_type' => 'plugin',
                'dir' => '{{snake_case_name}}',
                'name' => Language::_('{{class_name}}Plugin.getCronTasks.{{cron_tasks.name}}', true),
                'description' => Language::_('{{class_name}}Plugin.getCronTasks.{{cron_tasks.name}}_description', true),
                'type' => '{{cron_tasks.type}}',
                'type_value' => '{{cron_tasks.time}}',
                'enabled' => 1
            ],{{array:cron_tasks}}
        ];
    }

    /**
     * Runs the cron task identified by the key used to create the cron task
     *
     * @param string $key The key used to create the cron task
     * @see CronTasks::add()
     */
    public function cron($key)
    {
//// It is often useful to define a method for each cron task to keep this method small
        switch ($key) {{{array:cron_tasks}}
            case '{{cron_tasks.name}}':
                // Perform necessary actions
                break;{{array:cron_tasks}}
        }
    }{{function:getCronTasks}}{{function:getActions}}

    /**
     * Returns all actions to be configured for this widget
     * (invoked after install() or upgrade(), overwrites all existing actions)
     *
     * @return array A numerically indexed array containing:
     *  - action The action to register for
     *  - uri The URI to be invoked for the given action
     *  - name The name to represent the action (can be language definition)
     *  - options An array of key/value pair options for the given action
     */
    public function getActions()
    {
        return [{{array:actions}}
            // {{actions.name}}
            [
                'action' => '{{actions.location}}',
                'uri' => 'plugin/{{snake_case_name}}/{{actions.controller}}/{{actions.action}}/',
                'name' => '{{class_name}}Plugin.{{actions.location}}.{{actions.action}}',
            ],{{array:actions}}
        ];
    }{{function:getActions}}{{function:getEvents}}

    /**
     * Returns all events to be registered for this plugin
     * (invoked after install() or upgrade(), overwrites all existing events)
     *
     * @return array A numerically indexed array containing:
     *  - event The event to register for
     *  - callback A string or array representing a callback function or class/method.
     *      If a user (e.g. non-native PHP) function or class/method, the plugin must
     *      automatically define it when the plugin is loaded. To invoke an instance
     *      methods pass "this" instead of the class name as the 1st callback element.
     */
    public function getEvents()
    {
        return [{{array:events}}
            [
                'event' => '{{events.event}}',
                'callback' => ['this', '{{events.callback}}']
            ],{{array:events}}
        ];
    }{{function:getEvents}}{{function:getCards}}

    /**
     * Returns all cards to be configured for this plugin (invoked after install() or upgrade(),
     * overwrites all existing cards)
     *
     * @return array A numerically indexed array containing:
     *
     *  - level The level this card should be displayed on (client or staff) (optional, default client)
     *  - callback A method defined by the plugin class for calculating the value of the card or fetching a custom html
     *  - callback_type The callback type, 'value' to fetch the card value or
     *      'html' to fetch the custom html code (optional, default value)
     *  - background The background color in hexadecimal or path to the background image for this card (optional)
     *  - background_type The background type, 'color' to set a hexadecimal background or
     *      'image' to set an image background (optional, default color)
     *  - label A string or language key appearing under the value as a label
     *  - link The link to which the card will be pointed (optional)
     *  - enabled Whether this card appears on client profiles by default
     *      (1 to enable, 0 to disable) (optional, default 1)
     */
    public function getCards()
    {
        return [{{array:cards}}
            [
                'level' => '{{cards.level}}',
                'callback' => ['this', '{{cards.callback}}'],
                'callback_type' => 'value',
                'background' => '#fff',
                'background_type' => 'color',
                'label' => '{{class_name}}Plugin.card_{{cards.level}}.{{cards.callback}}',
                'link' => '{{cards.link}}',
                'enabled' => 1
            ],{{array:cards}}
        ];
    }{{function:getCards}}{{function:allowsServiceTabs}}

    /**
     * Returns whether this plugin provides support for setting admin or client service tabs
     * @see Plugin::getAdminServiceTabs
     * @see Plugin::getClientServiceTabs
     *
     * @return bool True if the plugin supports service tabs, or false otherwise
     */
    public function allowsServiceTabs()
    {
        return true;
    }{{function:allowsServiceTabs}}{{function:getPermissions}}

    /**
     * Returns all permissions to be configured for this plugin (invoked after install(), upgrade(),
     *  and uninstall(), overwrites all existing permissions)
     *
     * @return array A numerically indexed array containing:
     *
     *  - group_alias The alias of the permission group this permission belongs to
     *  - name The name of this permission
     *  - alias The ACO alias for this permission (i.e. the Class name to apply to)
     *  - action The action this ACO may control (i.e. the Method name of the alias to control access for)
     */
    public function getPermissions()
    {
    }{{function:getPermissions}}{{function:getPermissionGroups}}

    /**
     * Returns all permission groups to be configured for this plugin (invoked after install(), upgrade(),
     *  and uninstall(), overwrites all existing permission groups)
     *
     * @return array A numerically indexed array containing:
     *
     *  - name The name of this permission group
     *  - level The level this permission group resides on (staff or client)
     *  - alias The ACO alias for this permission group (i.e. the Class name to apply to)
     */
    public function getPermissionGroups()
    {
    }{{function:getPermissionGroups}}{{function:getClientServiceTabs}}


    /**
     * Returns all tabs to display to a client when managing a service
     *
     * @param stdClass $service A stdClass object representing the selected service
     * @return array An array of tabs in the format of method => array where array contains:
     *
     *  - name (required) The name of the link
     *  - icon (optional) use to display a custom icon
     *  - href (optional) use to link to a different URL
     *      Example:
     *      array('methodName' => "Title", 'methodName2' => "Title2")
     *      array('methodName' => array('name' => "Title", 'icon' => "icon"))
     */
    public function getClientServiceTabs(stdClass $service)
    {
        return [{{array:service_tabs}}{{if:service_tabs.level:client}}
            '{{service_tabs.method_name}}' => [
                'name' => Language::_('{{class_name}}.{{service_tabs.snake_case_name}}', true)
            ],{{endif:service_tabs.level}}{{array:service_tabs}}
        ];
    }{{function:getClientServiceTabs}}{{function:getAdminServiceTabs}}

    /**
     * Returns all tabs to display to an admin when managing a service
     *
     * @param stdClass $service An stdClass object representing the selected service
     * @return array An array of tabs in the format of method => array where array contains:
     *
     *  - name (required) The name of the link
     *  - href (optional) use to link to a different URL
     *      Example:
     *      array('methodName' => "Title", 'methodName2' => "Title2")
     *      array('methodName' => array('name' => "Title", 'href' => "https://blesta.com"))
     */
    public function getAdminServiceTabs(stdClass $service)
    {
        return [{{array:service_tabs}}{{if:service_tabs.level:staff}}
            '{{service_tabs.method_name}}' => [
                'name' => Language::_('{{class_name}}.{{service_tabs.snake_case_name}}', true)
            ],{{endif:service_tabs.level}}{{array:service_tabs}}
        ];
    }{{function:getAdminServiceTabs}}{{array:service_tabs}}

    /**
     * {{service_tabs.method_name}}
     *
     * @param stdClass $service An stdClass object representing the service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The content of the tab
     */
    public function {{service_tabs.method_name}}(
        stdClass $service,
        array $get = null,
        array $post = null,
        array $files = null
    ) {
        $this->view = new View('{{service_tabs.snake_case_name}}', '{{class_name}}.default');
        $this->view->base_uri = $this->base_uri;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        if (!empty($post)) {
            // Perform any post actions
            $vars = (object)$post;
        }

        $this->view->set('service_id', $service->id);
        $this->view->set('client_id', $service->client_id);
        $this->view->set('vars', (isset($vars) ? $vars : new stdClass()));

        return $this->view->fetch();
    }{{array:service_tabs}}{{array:cards}}

    /**
     * Retrieves the value for a client card
     *
     * @param int $client_id The ID of the client for which to fetch the card value
     * @return mixed The value for the client card
     */
    public function {{cards.callback}}($client_id)
    {
        return 'Example Value';
    }{{array:cards}}{{array:events}}

    /**
     * Handler for the {{events.event}} event
     *
     * @param EventInterface $event The event to process
     */
    public function {{events.callback}}(EventInterface $event)
    {
        $params = $event->getParams();
        $return = $event->getReturnValue();

        // Perform any necessary actions

        $event->setReturnValue($return);
    }{{array:events}}
}
