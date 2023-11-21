<?php
/**
 * Upgrades to version 5.5.0-b2
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade5_5_0B2 extends UpgradeUtil
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
            'updateContactFieldSettings',
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
     * Adds the new company/client group setting for Fax and Phone contact fields
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function updateContactFieldSettings($undo = false)
    {
        Loader::loadModels($this, ['Companies', 'ClientGroups']);

        if ($undo) {
            // Nothing to do
        } else {
            $setting = 'shown_contact_fields';

            // Update company settings
            $companies = $this->Record->select()->from('companies')->getStatement();
            foreach ($companies as $company) {
                $company_setting = $this->Companies->getSetting($company->id, $setting);
                $value = isset($company_setting->value) ? unserialize(base64_decode($company_setting->value)) : [];

                $value[] = 'fax';
                $value[] = 'phone';

                $this->Companies->setSetting($company->id, $setting, base64_encode(serialize($value)));
            }

            // Update client group settings
            $client_groups = $this->Record->select()->from('client_groups')->getStatement();
            foreach ($client_groups as $client_group) {
                $company_setting = $this->ClientGroups->getSetting($client_group->id, $setting);
                $value = isset($company_setting->value) ? unserialize(base64_decode($company_setting->value)) : [];

                $value[] = 'fax';
                $value[] = 'phone';

                $this->ClientGroups->setSetting($client_group->id, $setting, base64_encode(serialize($value)));
            }
        }
    }
}
