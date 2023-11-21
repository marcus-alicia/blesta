<?php
namespace Blesta\Core\Automation\Tasks\Task;

use Blesta\Core\Automation\Type\Common\AutomationTypeInterface;
use Blesta\Core\Automation\Tasks\Common\StaffNoticeTask;
use Language;
use Loader;
use stdClass;

/**
 * The provision_pending_services automation task
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Task
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ProvisionPendingServices extends StaffNoticeTask
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

        Loader::loadModels($this, ['ClientGroups', 'Services']);
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
        $this->log(Language::_('Automation.task.provision_pending_services.attempt', true));

        // Execute the suspend_services cron task
        $this->process($this->task->raw());

        // Log the task has completed
        $this->log(Language::_('Automation.task.provision_pending_services.completed', true));
        $this->logComplete();
    }

    /**
     * Processes the task
     *
     * @param stdClass $data The provision pending services task data
     */
    private function process(stdClass $data)
    {
        // Get all client groups for this company
        $client_groups = $this->ClientGroups->getAll($data->company_id);

        foreach ($client_groups as $client_group) {
            // Determine whether we should auto provision paid pending services for this client group
            $provision_services = $this->SettingsCollection->fetchClientGroupSetting(
                $client_group->id,
                $this->ClientGroups,
                'auto_paid_pending_services'
            );

            if (!isset($provision_services['value']) || $provision_services['value'] != 'true') {
                continue;
            }

            // Fetch all paid pending services for this client group
            $services = $this->Services->getAllPaidPending($client_group->id);

            // Set a 10 second cutoff for provisioning services from the current time
            $service_cutoff_time = $this->date->toTime($this->date->format('c')) - 10;

            foreach ($services as $service) {
                // Skip services that were added too recently. They may not have an invoice yet.
                // This temporarily works around a race condition whereby this task provisions the service
                // before it could be paid because it has no invoice yet
                if ($this->date->toTime($service->date_added . 'Z') >= $service_cutoff_time) {
                    continue;
                }

                // Add service module fields
                $module_fields = [];
                foreach ($service->fields as $field) {
                    $module_fields[$field->key] = $field->value;
                }

                // Change the status of the service to 'active' and set the renew date based on the current day
                $service_fields = $this->getServiceFields($service, $client_group);
                $this->Services->edit($service->id, array_merge($module_fields, $service_fields), false, true);

                // Log the change
                if (($errors = $this->Services->errors())) {
                    // Send the error email
                    $this->sendServiceCreateError($service, $errors);

                    $this->log(
                        Language::_(
                            'Automation.task.provision_pending_services.error',
                            true,
                            $service->id_code,
                            $service->client_id_code
                        )
                    );

                    // Reset errors
                    $this->resetErrors($this->Services);
                } else {
                    $this->log(
                        Language::_(
                            'Automation.task.provision_pending_services.success',
                            true,
                            $service->id_code,
                            $service->client_id_code
                        )
                    );
                }
            }
        }
    }

    /**
     * Returns a list of service fields for the new service
     *
     * @param stdClass $service The service being provisioned
     * @param stdClass $client_group The client group for the client this is being created for
     * @return array A list of services fields
     */
    private function getServiceFields(stdClass $service, stdClass $client_group)
    {
        $service_fields = [
            'status' => 'active'
        ];

        // Determine whether this service is prorated to match the renew date of its parent service
        $pricing = $this->Services->getPackagePricing($service->pricing_id);
        $prorate_to_parent = ($parent_service = $this->Services->get($service->parent_service_id))
            && $this->Services->canSyncToParent(
                $pricing,
                $parent_service->package_pricing,
                $client_group->id
            );

        // Update the renew date for non-prorata services
        if (!$prorate_to_parent && $service->package_prorata_day === null) {
            $service_fields['date_renews'] = ($service->period != 'onetime'
                ? $this->date->modify(
                    date('c'),
                    '+' . $service->term . ' ' . $service->period,
                    'c',
                    isset($this->options['timezone']) ? $this->options['timezone'] : 'UTC'
                )
                : null
            );
        }

        return $service_fields;
    }

    /**
     * {@inheritdoc}
     */
    protected function isTimeToRun()
    {
        return $this->task->canRun(date('c'));
    }
}
