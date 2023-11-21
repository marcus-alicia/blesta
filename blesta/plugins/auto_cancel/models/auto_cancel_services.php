<?php
class AutoCancelServices extends AppModel
{
    /**
     *
     * @param Services $services_model
     * @param int $company_id
     * @param string $schedule_days
     * @param string $cancel_days
     */
    public function scheduleCancellation(
        Services $services_model,
        $company_id,
        $schedule_days,
        $cancel_days
    ) {
        // Can not proceed unless values are non-empty
        if ($schedule_days === '' || $cancel_days === '') {
            return;
        }

        $minimum_suspend_date = $this->dateToUtc(
            $this->Date->cast(strtotime('-' . $schedule_days . ' days'), 'c')
        );

        $services = $this->Record->select(['services.id', 'services.date_suspended'])
            ->from('services')
            ->innerJoin('clients', 'clients.id', '=', 'services.client_id', false)
            ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)
            ->where('client_groups.company_id', '=', $company_id)
            ->where('services.status', '=', 'suspended')
            ->where('services.date_suspended', '<=', $minimum_suspend_date)
            ->where('services.date_canceled', '=', null)
            ->fetchAll();

        foreach ($services as $service) {
            // Cancellation date is suspend date + N days, or next midnight
            $cancellation_date = max(
                $this->dateToUtc(
                    strtotime($service->date_suspended . 'Z +' . $cancel_days . ' days')
                ),
                $this->dateToUtc(strtotime('+1 day midnight'))
            );

            // Set the cancellation date
            $services_model->cancel(
                $service->id,
                [
                    'use_module' => 'false',
                    'date_canceled' => $cancellation_date
                ]
            );
        }
    }
}
