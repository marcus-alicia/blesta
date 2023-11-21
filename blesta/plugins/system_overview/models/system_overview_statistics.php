<?php
/**
 * System Overview statistics
 *
 * @package blesta
 * @subpackage blesta.plugins.system_overview.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SystemOverviewStatistics extends SystemOverviewModel
{
    /**
     * Initialize
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retrieves the number of clients of the given status
     *
     * @param int $company_id The company ID from which to fetch the number of clients
     * @param string $status The status of the client ("active", "inactive", "fraud") (optional, default "active")
     * @return int The number of clients with the given status
     */
    public function getClientCount($company_id, $status = 'active')
    {
        return $this->Record->select('clients.id')->from('clients')->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            where('client_groups.company_id', '=', $company_id)->
            where('clients.status', '=', $status)->
            numResults();
    }

    /**
     * Retrieves the number of recurring invoices currently active in the system
     * for the currently active company
     */
    public function getRecurringInvoiceCount()
    {
        Loader::loadModels($this, ['Invoices']);
        return $this->Invoices->getRecurringCount();
    }

    /**
     * Retrieves the number of services of the given status
     *
     * @param int $company_id The company ID from which to fetch the number of services
     * @param string $status The status of the service
     *  ("active", "canceled", "pending", "suspended", "scheduled_cancellation") (optional, default "active")
     * @return int The number of services with the given status
     */
    public function getServiceCount($company_id, $status = 'active')
    {
        // Fetch services
        $this->Record = $this->Record->select('services.id')->from('services')->
            innerJoin('clients', 'clients.id', '=', 'services.client_id', false)->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            where('client_groups.company_id', '=', $company_id);

        // Filter by status
        $custom_statuses = ['scheduled_cancellation'];
        if (!in_array($status, $custom_statuses)) {
            $this->Record->where('services.status', '=', $status);
        } else {
            // Custom status type
            switch ($status) {
                case 'scheduled_cancellation':
                    $this->Record->where('services.date_canceled', '>', $this->dateToUtc(date('c')));
                    break;
                default:
                    break;
            }
        }

        return $this->Record->numResults();
    }

    /**
     * Retrieves the number of active users over time
     *
     * @param int $company_id The company ID from which to fetch active users
     * @param string $start_date The UTC start date timestamp in yyyy-mm-dd hh:mm:ss format
     * @param string $end_date The UTC end date timestamp in yyyy-mm-dd hh:mm:ss format
     * @return int The number of active users
     */
    public function getActiveUsersCount($company_id, $start_date, $end_date)
    {
        return $this->Record->select('id')->from('log_users')->
            where('company_id', '=', $company_id)->
            where('date_updated', '>=', $start_date)->
            where('date_updated', '<=', $end_date)->
            group('user_id')->
            numResults();
    }

    /**
     * Retrieves the number of services of a given status between the given dates
     *
     * @param string $start_date The UTC start date timestamp in yyyy-mm-dd hh:mm:ss format
     * @param string $end_date The UTC end date timestamp in yyyy-mm-dd hh:mm:ss format
     * @param string $status The status of the service
     *  ("active", "canceled", "pending", "suspended", "scheduled_cancellation") (optional, default "active")
     * @return int The number of services of the given status
     */
    public function getServices($company_id, $start_date, $end_date, $status = 'active')
    {
        // Fetch the services
        $this->Record = $this->Record->select('services.id')->from('services')->
            innerJoin('clients', 'clients.id', '=', 'services.client_id', false)->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            where('client_groups.company_id', '=', $company_id);

        $date_field = 'services.date_added';

        // Filter by status
        $custom_statuses = ['scheduled_cancellation'];
        if (!in_array($status, $custom_statuses)) {
            $this->Record->where('services.status', '=', $status);

            // Set the filter date field as the date canceled or date suspended
            if (in_array($status, ['canceled', 'suspended'])) {
                $date_field = 'services.date_' . strtolower($status);
            }
        } else {
            // Custom status type
            switch ($status) {
                case 'scheduled_cancellation':
                    $this->Record->where('services.date_canceled', '>', $this->dateToUtc(date('c')));
                    break;
                default:
                    break;
            }
        }

        // Filter by the start/end dates
        $this->Record->where($date_field, '>=', $start_date)->
            where($date_field, '<=', $end_date);

        return $this->Record->numResults();
    }

    /**
     * Retrieves the number of clients created between the given dates
     *
     * @param string $start_date The UTC start date timestamp in yyyy-mm-dd hh:mm:ss format
     * @param string $end_date The UTC end date timestamp in yyyy-mm-dd hh:mm:ss format
     */
    public function getClients($company_id, $start_date, $end_date)
    {
        return $this->Record->select('users.id')->from('users')->
            innerJoin('clients', 'clients.user_id', '=', 'users.id', false)->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            where('client_groups.company_id', '=', $company_id)->
            where('users.date_added', '>=', $start_date)->
            where('users.date_added', '<=', $end_date)->
            numResults();
    }
}
