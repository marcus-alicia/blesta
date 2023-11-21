<?php
/**
 * Upgrades to version 5.8.1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade5_8_1 extends UpgradeUtil
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
            'setContactPermissionClientId',
            'revokeAccessForDeletedManagers'
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
     * Creates the required tables by the Account Management system in the database
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function setContactPermissionClientId($undo = false)
    {
        Loader::loadModels($this, ['Companies']);

        if ($undo) {
            // Nothing to do
        } else {
            $this->Record->query(
                'UPDATE `contact_permissions`
                INNER JOIN `contacts` ON `contacts`.`id` = `contact_permissions`.`contact_id`
                SET `contact_permissions`.`client_id` = `contacts`.`client_id`
                WHERE `contact_permissions`.`client_id` IS NULL;'
            )->closeCursor();
            $sql = 'ALTER TABLE `contact_permissions` CHANGE `client_id` `client_id` INT UNSIGNED NOT NULL;';
            $this->Record->query($sql)->closeCursor();
        }
    }
    
    /**
     * Revokes access for manager that should have already had their access revoked
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function revokeAccessForDeletedManagers($undo = false)
    {
        Loader::loadModels($this, ['Companies']);

        if ($undo) {
            // Nothing to do
        } else {
            return $this->Record->from('contact_permissions')
                ->innerJoin('contacts', 'contacts.id', '=', 'contact_permissions.contact_id', false)
                ->on('contacts.client_id', '!=', 'clients.id', false)
                ->innerJoin('clients', 'clients.id', '=', 'contact_permissions.client_id', false)
                ->leftJoin(
                    'account_management_invitations',
                    'account_management_invitations.email',
                    '=',
                    'contacts.email',
                    false
                )->
                where('account_management_invitations.id', '=', null)
                ->delete(['contact_permissions.*']);
        }
    }
}
