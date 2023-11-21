<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Tasks\Common\AbstractTask;
use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Configure;
use Language;
use Loader;

/**
 * The transition_quotations automation task
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class TransitionQuotations extends AbstractTask
{
    /**
     * Initialize a new task
     *
     * @param AutomationTypeInterface $task The raw automation task
     * @param array $options An additional options necessary for the task:
     *
     *  - print_log True to print logged content to stdout, or false otherwise (default false)
     *  - cli True if this is being run via the Command-Line Interface, or false otherwise (default true)
     *  - client_uri The URI of the client interface
     */
    public function __construct(AutomationTypeInterface $task, array $options = [])
    {
        parent::__construct($task, $options);

        Loader::loadModels($this, ['Companies', 'Quotations']);
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
        $this->log(Language::_('Automation.task.transition_quotations.attempt', true));

        // Execute the deliver_invoices cron task
        $this->process();

        // Log the task has completed
        $this->log(Language::_('Automation.task.transition_quotations.completed', true));
        $this->logComplete();
    }

    /**
     * Processes the task
     */
    private function process()
    {
        $num_quotations = null;

        $quotation_dead_days = $this->Companies->getSetting(
            Configure::get('Blesta.company_id'),
            'quotation_dead_days'
        )->value;

        $pending_quotations = $this->Quotations->getAll(null, 'pending');
        foreach ($pending_quotations as $pending_quotation) {
            if (strtotime(date('c')) > strtotime($pending_quotation->date_expires)) {
                $this->Quotations->updateStatus($pending_quotation->id, 'expired');
                $num_quotations++;

                $this->log(
                    Language::_(
                        'Automation.task.transition_quotations.expiration_success',
                        true,
                        $pending_quotation->id_code,
                        $pending_quotation->client_id_code
                    )
                );
            }
        }

        $expired_quotations = $this->Quotations->getAll(null, 'expired');
        foreach ($expired_quotations as $expired_quotation) {
            if (strtotime(date('c'))
                > strtotime($expired_quotation->date_expires . ' +' . $quotation_dead_days . 'days')
            ) {
                $this->Quotations->updateStatus($expired_quotation->id, 'dead');
                $num_quotations++;

                $this->log(
                    Language::_(
                        'Automation.task.transition_quotations.dead_success',
                        true,
                        $expired_quotation->id_code,
                        $expired_quotation->client_id_code
                    )
                );
            }
        }

        // No quotations were updated
        if ($num_quotations === null) {
            $this->log(Language::_('Automation.task.transition_quotations.none', true));
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
