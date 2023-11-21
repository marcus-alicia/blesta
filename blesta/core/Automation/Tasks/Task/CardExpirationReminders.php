<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Tasks\Common\AbstractTask;
use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Configure;
use Language;
use Loader;
use stdClass;

/**
 * The plugin automation task to execute a plugin's cron actions
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class CardExpirationReminders extends AbstractTask
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

        Loader::loadModels($this, ['Accounts', 'Clients', 'Contacts', 'Emails']);
        Loader::loadHelpers($this, ['Html']);
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
        $this->log(Language::_('Automation.task.card_expiration_reminders.attempt', true));

        // Execute the card expiration reminders cron task
        $this->process($this->task->raw());

        // Log the task has completed
        $this->log(Language::_('Automation.task.card_expiration_reminders.completed', true));
        $this->logComplete();
    }

    /**
     * Processes the task
     *
     * @param stdClass $data The card expiration reminders task data
     */
    private function process(stdClass $data)
    {
        $ccAccounts = $this->Accounts->getCardsExpireSoon(date('c'));

        // Get the company hostname
        $hostname = isset(Configure::get('Blesta.company')->hostname)
            ? Configure::get('Blesta.company')->hostname
            : '';

        // Send an email to every contact regarding the payment account card expiration
        $cardTypes = $this->Accounts->getCcTypes();
        foreach ($ccAccounts as $account) {
            // Get contact and client
            $contact = $this->Contacts->get($account->contact_id);
            $client = $this->Clients->get($contact->client_id);

            $tags = [
                'contact' => $contact,
                'card_type' => (isset($cardTypes[$account->type]) ? $cardTypes[$account->type] : ''),
                'last_four' => $account->last4,
                'client_url' => $this->Html->safe($hostname . $this->options['client_uri'])
            ];
            $this->Emails->send(
                'credit_card_expiration',
                $data->company_id,
                $client->settings['language'],
                $contact->email,
                $tags,
                null,
                null,
                null,
                ['to_client_id' => $client->id]
            );

            // Log success/error
            if (($errors = $this->Emails->errors())) {
                $this->log(
                    Language::_(
                        'Automation.task.card_expiration_reminders.failed',
                        true,
                        $contact->first_name,
                        $contact->last_name,
                        $client->id_code
                    )
                );

                // Reset errors
                $this->resetErrors($this->Emails);
            } else {
                $this->log(
                    Language::_(
                        'Automation.task.card_expiration_reminders.success',
                        true,
                        $contact->first_name,
                        $contact->last_name,
                        $client->id_code
                    )
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function isTimeToRun()
    {
        // Can only run on the 15th of the month
        return $this->isCurrentDay(15) && $this->task->canRun(date('c'));
    }
}
