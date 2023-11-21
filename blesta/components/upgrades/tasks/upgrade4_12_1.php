<?php
/**
 * Upgrades to version 4.12.1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_12_1 extends UpgradeUtil
{
    /**
     * @var array An array of all tasks completed
     */
    private $tasks = [];

    /**
     * Setup
     */
    public function __construct()
    {
        Configure::load('blesta');
        Loader::loadComponents($this, ['Record']);
    }

    /**
     * Returns a numerically indexed array of tasks to execute for the upgrade process
     *
     * @return array A numerically indexed array of tasks to execute for the upgrade process
     */
    public function tasks()
    {
        return [
            'addMissingMessengerTemplates'
        ];
    }

    /**
     * Processes the given task
     *
     * @param string $task The task to process
     */
    public function process($task)
    {
        $tasks = $this->tasks();

        // Ensure task exists
        if (!in_array($task, $tasks)) {
            return;
        }

        $this->tasks[] = $task;
        $this->{$task}();
    }

    /**
     * Rolls back all tasks completed for the upgrade process
     */
    public function rollback()
    {
        // Undo all tasks
        while (($task = array_pop($this->tasks))) {
            $this->{$task}(true);
        }
    }

    /**
     * Adds missing messenger templates to all the system companies
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addMissingMessengerTemplates($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            Loader::loadModels($this, ['Companies', 'Plugins', 'PluginManager']);

            // Fetch all companies
            $companies = $this->Companies->getAll();

            foreach ($companies as $company) {
                $installed_plugins = $this->PluginManager->getAll($company->id);

                foreach ($installed_plugins as $installed_plugin) {
                    // Get plugin messages
                    $plugin = $this->Plugins->create($installed_plugin->dir);
                    $messages = $plugin->getMessageTemplates();

                    if ($messages && is_array($messages)) {
                        $plugin_messages = [];
                        foreach ($messages as $message) {
                            $plugin_messages[$message['action']] = $message;
                        }

                        // Get installed messages
                        $installed_messages = $this->Record->select(['messages.id', 'message_groups.*'])
                            ->from('messages')
                            ->innerJoin('plugins', 'plugins.id', '=', $installed_plugin->id)
                            ->innerJoin('message_groups', 'message_groups.id', '=', 'messages.message_group_id', false)
                            ->where('messages.company_id', '=', 'plugins.company_id', false)
                            ->where('message_groups.plugin_dir', '=', 'plugins.dir', false)
                            ->fetchAll();

                        $current_messages = [];
                        foreach ($installed_messages as $message) {
                            $current_messages[$message->action] = $message;
                        }

                        // Add the missing message templates
                        foreach ($plugin_messages as $action => $message) {
                            if (!array_key_exists($action, (array)$current_messages)) {
                                $this->PluginManager->addMessage($installed_plugin->id, $message);
                            }
                        }
                    }
                }
            }
        }
    }
}
