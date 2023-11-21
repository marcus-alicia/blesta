<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Tasks\Common\AbstractTask;
use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Configure;
use Language;
use Loader;
use stdClass;

/**
 * The cleanup_logs automation task
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class CleanupLogs extends AbstractTask
{
    /**
     * Initialize a new task
     *
     * @param AutomationTypeInterface $task The raw automation task
     * @param array $options An additional options necessary for the task:
     *
     *  - print_log True to print logged content to stdout, or false otherwise (default false)
     *  - cli True if this is being run via the Command-Line Interface, or false otherwise (default true)
     */
    public function __construct(AutomationTypeInterface $task, array $options = [])
    {
        parent::__construct($task, $options);

        Loader::loadModels($this, ['Logs']);
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
        $this->log(Language::_('Automation.task.cleanup_logs.attempt', true));

        // Execute the clean-up logs cron task
        $this->deleteCompanyLogs($this->task->raw());
        $this->deleteSystemLogs();

        // Log the task has completed
        $this->log(Language::_('Automation.task.cleanup_logs.completed', true));
        $this->logComplete();
    }

    /**
     * Delete old logs for the system
     */
    private function deleteSystemLogs()
    {
        // Delete old cron logs
        $days = Configure::get('Blesta.cron_log_retention_days');
        if ($days && is_numeric($days)) {
            $this->log(
                Language::_(
                    'Automation.task.cleanup_logs.logs_cron_deleted',
                    true,
                    $this->Logs->deleteCronLogs(
                        $this->date->modify($this->date->format('c'), '-' . abs((int)$days) . ' days', 'Y-m-d H:i:s')
                    )
                )
            );
        }
    }

    /**
     * Delete old logs for the given company
     *
     * @param stdClass $data The cleanup logs task data
     */
    private function deleteCompanyLogs(stdClass $data)
    {
        // Get the date at which logs should be purged from the settings
        $company_settings = $this->SettingsCollection->fetchSettings(null, $data->company_id);

        // Delete logs based on the rotation policy (log_days)
        if (isset($company_settings['log_days']) && is_numeric($company_settings['log_days'])) {
            // Get the (local) date at which logs should be purged
            $start_date = $this->date->format('c');
            $past_date = $this->date->modify(
                $start_date,
                '-' . abs((int)$company_settings['log_days']) . ' days',
                'Y-m-d H:i:s',
                $company_settings['timezone']
            );

            $this->deleteGatewayLogs($past_date);
            $this->deleteModuleLogs($past_date);
            $this->deleteAccountAccessLogs($past_date);
            $this->deleteContactLogs($past_date);
            $this->deleteClientSettingLogs($past_date);
            $this->deleteEmailLogs($past_date);
            $this->deleteMessengerLogs($past_date);
            $this->deleteUserLogs($past_date);
            $this->deleteServiceLogs($past_date);
            $this->deleteTransactionLogs($past_date);
        }
    }

    /**
     * Purges gateway logs older than the given date
     *
     * @param string $date The date before which to delete logs
     */
    private function deleteGatewayLogs($date)
    {
        // Delete the gateway logs
        if (Configure::get('Blesta.auto_delete_gateway_logs')) {
            $this->log(
                Language::_(
                    'Automation.task.cleanup_logs.logs_gateway_deleted',
                    true,
                    $this->Logs->deleteGatewayLogs($date)
                )
            );
        }
    }

    /**
     * Purges module logs older than the given date
     *
     * @param string $date The date before which to delete logs
     */
    private function deleteModuleLogs($date)
    {
        // Delete the module logs
        if (Configure::get('Blesta.auto_delete_module_logs')) {
            $this->log(
                Language::_(
                    'Automation.task.cleanup_logs.logs_module_deleted',
                    true,
                    $this->Logs->deleteModuleLogs($date)
                )
            );
        }
    }

    /**
     * Purges account access logs older than the given date
     *
     * @param string $date The date before which to delete logs
     */
    private function deleteAccountAccessLogs($date)
    {
        // Delete the account access logs
        if (Configure::get('Blesta.auto_delete_accountaccess_logs')) {
            $this->log(
                Language::_(
                    'Automation.task.cleanup_logs.logs_accountaccess_deleted',
                    true,
                    $this->Logs->deleteAccountAccessLogs($date)
                )
            );
        }
    }

    /**
     * Purges contact logs older than the given date
     *
     * @param string $date The date before which to delete logs
     */
    private function deleteContactLogs($date)
    {
        // Delete the contact logs
        if (Configure::get('Blesta.auto_delete_contact_logs')) {
            $this->log(
                Language::_(
                    'Automation.task.cleanup_logs.logs_contact_deleted',
                    true,
                    $this->Logs->deleteContactLogs($date)
                )
            );
        }
    }

    /**
     * Purges client setting logs older than the given date
     *
     * @param string $date The date before which to delete logs
     */
    private function deleteClientSettingLogs($date)
    {
        // Delete the client setting logs
        if (Configure::get('Blesta.auto_delete_client_setting_logs')) {
            $this->log(
                Language::_(
                    'Automation.task.cleanup_logs.logs_client_settings_deleted',
                    true,
                    $this->Logs->deleteClientSettingLogs($date)
                )
            );
        }
    }

    /**
     * Purges email logs older than the given date
     *
     * @param string $date The date before which to delete logs
     */
    private function deleteEmailLogs($date)
    {
        // Delete the email logs
        if (Configure::get('Blesta.auto_delete_email_logs')) {
            $this->log(
                Language::_(
                    'Automation.task.cleanup_logs.logs_email_deleted',
                    true,
                    $this->Logs->deleteEmailLogs($date)
                )
            );
        }
    }

    /**
     * Purges messenger logs older than the given date
     *
     * @param string $date The date before which to delete logs
     */
    private function deleteMessengerLogs($date)
    {
        // Delete the email logs
        if (Configure::get('Blesta.auto_delete_messenger_logs')) {
            $this->log(
                Language::_(
                    'Automation.task.cleanup_logs.logs_messenger_deleted',
                    true,
                    $this->Logs->deleteMessengerLogs($date)
                )
            );
        }
    }

    /**
     * Purges user logs older than the given date
     *
     * @param string $date The date before which to delete logs
     */
    private function deleteUserLogs($date)
    {
        // Delete the user logs
        if (Configure::get('Blesta.auto_delete_user_logs')) {
            $this->log(
                Language::_(
                    'Automation.task.cleanup_logs.logs_user_deleted',
                    true,
                    $this->Logs->deleteUserLogs($date)
                )
            );
        }
    }

    /**
     * Purges service logs older than the given date
     *
     * @param string $date The date before which to delete logs
     */
    private function deleteServiceLogs($date)
    {
        // Delete the service logs
        if (Configure::get('Blesta.auto_delete_service_logs')) {
            $this->log(
                Language::_(
                    'Automation.task.cleanup_logs.logs_service_deleted',
                    true,
                    $this->Logs->deleteServiceLogs($date)
                )
            );
        }
    }

    /**
     * Purges transaction logs older than the given date
     *
     * @param string $date The date before which to delete logs
     */
    private function deleteTransactionLogs($date)
    {
        // Delete the transaction logs
        if (Configure::get('Blesta.auto_delete_transaction_logs')) {
            $this->log(
                Language::_(
                    'Automation.task.cleanup_logs.logs_transaction_deleted',
                    true,
                    $this->Logs->deleteTransactionLogs($date)
                )
            );
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
