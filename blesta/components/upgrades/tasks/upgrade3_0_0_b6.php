<?php
/**
 * Upgrades to version 3.0.0.b6
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_0_0B6 extends UpgradeUtil
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
     * @retrun array A numerically indexed array of tasks to execute for the upgrade process
     */
    public function tasks()
    {
        return [
            'updateServiceCreationEmail'
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
     * Updates an incorrect email tag in the service creation email template
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function updateServiceCreationEmail($undo = false)
    {
        if ($undo) {
            // Nothing to undo
            return;
        }

        // Fetch all service creation emails
        $emails = $this->Record->select(['emails.id', 'emails.text', 'emails.html'])->from('emails')->
            on('email_groups.id', '=', 'emails.email_group_id', false)->
            innerJoin('email_groups', 'email_groups.action', '=', 'service_creation')->
            getStatement();

        // Update each service creation email to change {package.email} to {package.email_text} and {package.email_html}
        foreach ($emails as $email) {
            $vars = [
                'text' => str_replace('{package.email}', '{package.email_text}', $email->text),
                'html' => str_replace('{package.email}', '{package.email_html}', $email->html)
            ];

            if ($vars['text'] != $email->text || $vars['html'] != $email->html) {
                $this->Record->where('id', '=', $email->id)->update('emails', $vars);
            }
        }
    }
}
