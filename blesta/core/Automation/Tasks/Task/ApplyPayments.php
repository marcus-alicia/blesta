<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Tasks\Common\AbstractTask;
use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Language;
use Loader;
use stdClass;

/**
 * The apply_payments automation task
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ApplyPayments extends AbstractTask
{
    /**
     * {@inheritdoc}
     */
    public function __construct(AutomationTypeInterface $task, array $options = [])
    {
        parent::__construct($task, $options);

        Loader::loadModels($this, ['Clients', 'ClientGroups', 'Transactions']);
        Loader::loadComponents($this, ['SettingsCollection']);
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        // This task cannot be run right now
        if (!$this->isTimeToRun()) {
            return;
        }

        // Log the task has begun
        $this->log(Language::_('Automation.task.apply_payments.attempt', true));

        // Execute the apply payments cron task
        $this->process($this->task->raw());

        // Log the task has completed
        $this->log(Language::_('Automation.task.apply_payments.completed', true));
        $this->logComplete();
    }

    /**
     * Processes the task
     *
     * @param stdClass $data The apply payments task data
     */
    private function process(stdClass $data)
    {
        // Get all client groups
        $clientGroups = $this->ClientGroups->getAll($data->company_id);
        $appliedCredits = false;

        foreach ($clientGroups as $clientGroup) {
            // Ensure we can auto apply credits for this client group
            $applyCredits = $this->SettingsCollection->fetchClientGroupSetting(
                $clientGroup->id,
                $this->ClientGroups,
                'auto_apply_credits'
            );
            $applyCredits = (isset($applyCredits['value']) && $applyCredits['value'] != 'true' ? false : true);

            if (!$applyCredits) {
                continue;
            }

            $this->log(Language::_('Automation.task.apply_payments.attempt_group', true, $clientGroup->name));

            // Apply credits for all clients in the client group
            $this->applyCredits($clientGroup->id, $appliedCredits);

            $this->log(Language::_('Automation.task.apply_payments.completed_group', true, $clientGroup->name));
        }

        // Log nothing applied
        if (!$appliedCredits) {
            $this->log(Language::_('Automation.task.apply_payments.apply_none', true));
        }
    }

    /**
     * Applies credits for the given client group
     *
     * @param int $clientGroupId The ID of the client group to apply credits for
     * @param bool reference $appliedCredits Whether or not any credits have been applied
     */
    private function applyCredits($clientGroupId, &$appliedCredits)
    {
        // Get each client in this group
        $clients = $this->Clients->getAll(null, $clientGroupId);

        foreach ($clients as $client) {
            // Attempt to apply credits
            $amountsApplied = $this->Transactions->applyFromCredits($client->id);

            // Avoid the possibility of unapplicable transaction errors being erroneously
            // re-used for other clients by requiring the applied amount to be set
            if ($amountsApplied !== null && ($errors = $this->Transactions->errors())) {
                $this->log(Language::_('Automation.task.apply_payments.apply_failed', true, $client->id));

                // Reset errors
                $this->resetErrors($this->Transactions);
            } elseif (!empty($amountsApplied)) {
                $appliedCredits = true;

                foreach ($amountsApplied as $transactionId => $amounts) {
                    foreach ($amounts as $applied) {
                        $this->log(
                            Language::_(
                                'Automation.task.apply_payments.apply_success',
                                true,
                                $transactionId,
                                $client->id,
                                $applied['invoice_id'],
                                $applied['amount']
                            )
                        );
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function isTimeToRun()
    {
        return $this->task->canRun(date('c'));
    }
}
