<?php
/**
 * Upgrades to version 3.0.3
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_0_3 extends UpgradeUtil
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
            'fixServiceSuspensionTemplate',
            'updateTaxesState'
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
     * Updates the Service Suspension email template to add email tags, and to update the text/html content
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function fixServiceSuspensionTemplate($undo = false)
    {
        if ($undo) {
            // Nothing to undo
            return;
        }

        // Update the service_suspension email group to add basic tags
        $vars = ['tags' => '{contact.first_name},{contact.last_name},{package.name}'];
        $this->Record->where('action', '=', 'service_suspension')->update('email_groups', $vars, array_keys($vars));

        // Update the service_suspension emails to set the default English text for this template
        $email_group = $this->Record->select()
            ->from('email_groups')
            ->where('action', '=', 'service_suspension')
            ->fetch();

        if ($email_group) {
            $email_text = <<< TXT
Hi {contact.first_name},

Your service, {package.name} has been suspended. The service may have been suspended for the following reasons:

1. Non-payment. If your service was suspended for non-payment, you may login at http://{client_uri} to post payment and re-activate the service.
2. TOS or abuse violation.

If the service is suspended for an extended period of time, it may be cancelled. Please contact us if you have any questions.
TXT;
            $email_html = <<< TXT
<p>Hi {contact.first_name},</p>
<p>Your service, {package.name} has been suspended. The service may have been suspended for the following reasons:</p>
<ol>
	<li>Non-payment. If your service was suspended for non-payment, you may login at&nbsp;<a href="http://{client_uri}">http://{client_uri}</a>&nbsp;to post payment and re-activate the service.</li>
	<li>TOS or abuse violation.</li>
</ol>
<p>If the service is suspended for an extended period of time, it may be cancelled. Please contact us if you have any questions.</p>
TXT;
            // Update the emails
            $vars = [
                'text' => $email_text,
                'html' => $email_html
            ];
            $this->Record->where('email_group_id', '=', $email_group->id)->
                where('lang', '=', 'en_us')->update('emails', $vars, array_keys($vars));
        }
    }

    /**
     * Updates the taxes.state field from varchar(2) to varchar(3)
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function updateTaxesState($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `taxes` CHANGE `state` `state` VARCHAR( 2 ) NULL DEFAULT NULL ;');
        } else {
            $this->Record->query('ALTER TABLE `taxes` CHANGE `state` `state` VARCHAR( 3 ) NULL DEFAULT NULL ;');
        }
    }
}
