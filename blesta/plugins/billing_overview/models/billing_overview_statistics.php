<?php
/**
 * Billing Overview statistics
 *
 * @package blesta
 * @subpackage blesta.plugins.billing_overview.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class BillingOverviewStatistics extends BillingOverviewModel
{
    /**
     * Initialize
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Fetches the revenue received over a date range
     *
     * @param int $company_id The company ID from which to fetch revenue
     * @param string $currency The ISO 4217 3-character currency code
     * @param string $start_date The start date
     * @param string $end_date The end date
     * @param string $type The type of transaction to filter by -- "cc", "ach", "other" (optional, default all)
     * @return float The total revenue received over the given date range
     */
    public function getRevenue($company_id, $currency, $start_date, $end_date, $type = null)
    {
        $total = 0;

        // Fetch all ACH/CC revenue
        $this->Record = $this->getTransactionRevenue($company_id, $currency, $start_date, $end_date, $type);
        $this->Record->where('transactions.type', '!=', 'other');
        $amount = $this->Record->fetch();

        if ($amount && !empty($amount->total)) {
            $total += $amount->total;
        }

        // Fetch all other revenue -- no credits
        if ($type === null || $type == 'other') {
            $this->Record = $this->getTransactionRevenue($company_id, $currency, $start_date, $end_date, 'other');

            // An 'other' type with a 'debit' transaction is revenue,
            // and any without a transaction_type is too
            $this->Record->leftJoin(
                'transaction_types',
                'transaction_types.id',
                '=',
                'transactions.transaction_type_id',
                false
            )
                ->open()
                ->where('transaction_types.type', '=', 'debit')
                ->orWhere('transactions.transaction_type_id', '=', null)
                ->close();

            $amount = $this->Record->fetch();

            if ($amount && !empty($amount->total)) {
                $total += $amount->total;
            }
        }

        return $total;
    }

    /**
     * Fetches the revenue received over a date range for gateways
     *
     * @param int $company_id The company ID from which to fetch revenue
     * @param string $currency The ISO 4217 3-character currency code
     * @param string $start_date The start date
     * @param string $end_date The end date
     * @param string $type The type of transaction to filter by -- "cc", "ach", "other"
     * @return array An array of stdClass objects containing:
     *  - total The total revenue received over the given date range
     *  - gateway_id The ID of the gateway
     *  - type The transaction type
     */
    public function getGatewayRevenue($company_id, $currency, $start_date, $end_date, $type)
    {
        $this->Record = $this->getTransactionRevenue($company_id, $currency, $start_date, $end_date, $type);

        return $this->Record->select(['transactions.gateway_id', 'transactions.type'])
            ->where('transactions.gateway_id', '!=', null)
            ->group(['transactions.gateway_id'])
            ->fetchAll();
    }

    /**
     * Fetches the amounts received for transactions of type 'other' that are not for gateways
     * i.e. custom 'debit' and/or 'credit' transaction types
     *
     * @param int $company_id The company ID from which to fetch revenue
     * @param string $currency The ISO 4217 3-character currency code
     * @param string $start_date The start date
     * @param string $end_date The end date
     * @return array An array of stdClass objects containing:
     *  - total The total revenue received over the given date range
     *  - id The transaction type ID
     *  - name The name of the transaction type
     *  - type The transaction type
     */
    public function getOtherRevenue($company_id, $currency, $start_date, $end_date)
    {
        $this->Record = $this->getTransactionRevenue($company_id, $currency, $start_date, $end_date, 'other');

        return $this->Record->select(['transaction_types.*'])
            ->leftJoin(
                'transaction_types',
                'transaction_types.id',
                '=',
                'transactions.transaction_type_id',
                false
            )
            ->where('transactions.gateway_id', '=', null)
            ->group(['transaction_types.id'])
            ->fetchAll();
    }

    /**
     *
     * Partially constructs a Record object for fetching revenue from transaction information
     *
     * @param int $company_id The company ID from which to fetch revenue
     * @param string $currency The ISO 4217 3-character currency code
     * @param string $start_date The start date
     * @param string $end_date The end date
     * @param string $type The type of transaction to filter by -- "cc", "ach", "other" (optional, default all)
     * @return Record A partially-constructed Record object for fetching revenue
     */
    private function getTransactionRevenue($company_id, $currency, $start_date, $end_date, $type = null)
    {
        // Fetch the total revenue
        $this->Record->select(['SUM(IFNULL(transactions.amount,0))'=>'total'], false)->
            from('transactions')->
            innerJoin('clients', 'clients.id', '=', 'transactions.client_id', false)->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            where('client_groups.company_id', '=', $company_id)->
            where('transactions.status', '=', 'approved')->
            where('transactions.currency', '=', $currency)->
            where('transactions.date_added', '>=', $this->dateToUtc($start_date))->
            where('transactions.date_added', '<=', $this->dateToUtc($end_date));

        // Filter by transaction type
        if ($type != null) {
            $this->Record->where('transactions.type', '=', $type);
        }

        return $this->Record;
    }

    /**
     * Fetches the credits (non-income) received over a date range
     *
     * @param int $company_id The company ID from which to fetch revenue
     * @param string $currency The ISO 4217 3-character currency code
     * @param string $start_date The start date
     * @param string $end_date The end date
     * @return float The total credits over the given date range
     */
    public function getCredits($company_id, $currency, $start_date, $end_date)
    {
        // Fetch the transaction revenue, but filter for only credits
        $this->Record = $this->getTransactionRevenue($company_id, $currency, $start_date, $end_date, 'other');
        $this->Record
            ->innerJoin('transaction_types', 'transaction_types.id', '=', 'transactions.transaction_type_id', false)
            ->where('transaction_types.type', '=', 'credit');
        $amount = $this->Record->fetch();

        if ($amount && !empty($amount->total)) {
            return $amount->total;
        }

        return 0;
    }

    /**
     * Fetches the total amount invoiced over a date range
     *
     * @param int $company_id The company ID from which to fetch revenue
     * @param string $currency The ISO 4217 3-character currency code
     * @param string $start_date The start date timestamp
     * @param string $end_date The end date timestamp
     * @param array $statuses A list of acceptable invoice statuses (active, proforma, or both)
     * @return float The total invoiced amount over the given date range
     */
    public function getAmountInvoiced(
        $company_id,
        $currency,
        $start_date,
        $end_date,
        array $statuses = ['active', 'proforma']
    ) {
        if (empty($statuses)) {
            $statuses = ['active', 'proforma'];
        }

        // Get all active invoices within the range
        $amount = $this->Record->select(['SUM(IFNULL(invoices.total,0))' => 'total'], false)->from('invoices')->
            innerJoin('clients', 'clients.id', '=', 'invoices.client_id', false)->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            where('client_groups.company_id', '=', $company_id)->
            where('invoices.status', 'in', $statuses)->
            where('invoices.currency', '=', $currency)->
            where('invoices.date_billed', '>=', $this->dateToUtc($start_date))->
            where('invoices.date_billed', '<=', $this->dateToUtc($end_date))->
            fetch();

        if ($amount) {
            return $amount->total;
        }
        return 0;
    }

    /**
     * Fetches the total amount invoiced for all unpaid invoices
     *
     * @param int $company_id The company ID from which to fetch the outstanding balance
     * @param string $currency The ISO 4217 3-character currency code
     * @return float The total outstanding balance
     */
    public function getOutstandingBalance($company_id, $currency)
    {
        // Fetch all active open invoices
        $amount = $this->Record
            ->select(
                ['SUM(GREATEST(?, IFNULL(invoices.total,?) - IFNULL(invoices.paid,?)))' => 'total'],
                false
            )
            ->appendValues([0, 0, 0])
            ->from('invoices')
            ->innerJoin('clients', 'clients.id', '=', 'invoices.client_id', false)
            ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)
            ->where('client_groups.company_id', '=', $company_id)
            ->where('invoices.status', '=', 'active')
            ->where('invoices.currency', '=', $currency)
            ->where('invoices.date_closed', '=', null)
            ->where('invoices.date_billed', "<=", $this->dateToUtc(date('c')))
            ->fetch();

        if ($amount) {
            return $amount->total;
        }
        return 0;
    }

    /**
     * Fetches the total amount invoiced for all past due invoices
     *
     * @param int $company_id The company ID from which to fetch the past due balance
     * @param string $currency The ISO 4217 3-character currency code
     * @return float The total overdue balance
     */
    public function getOverdueBalance($company_id, $currency)
    {
        // Fetch all past due invoices
        $amount = $this->Record
            ->select(['SUM(GREATEST(0, IFNULL(invoices.total,0) - IFNULL(invoices.paid,0)))' => 'total'], false)
            ->from('invoices')
            ->innerJoin('clients', 'clients.id', '=', 'invoices.client_id', false)
            ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)
            ->where('client_groups.company_id', '=', $company_id)
            ->where('invoices.status', 'in', ['active', 'proforma'])
            ->where('invoices.currency', '=', $currency)
            ->where('invoices.date_closed', '=', null)
            ->where('invoices.date_due', '<', $this->dateToUtc(date('c')))
            ->fetch();
        if ($amount) {
            return $amount->total;
        }
        return 0;
    }

    /**
     * Fetches the number of upcoming services set to be canceled
     *
     * @param int $company_id The company ID
     * @return int The number of upcoming services set to be canceled
     */
    public function getScheduledCancelationsCount($company_id)
    {
        // Fetch the number of upcoming services set to be canceled
        return $this->Record->select('services.id')->
            from('services')->
            innerJoin('clients', 'clients.id', '=', 'services.client_id', false)->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            where('client_groups.company_id', '=', $company_id)->
            where('services.status', '=', 'active')->
            where('services.date_canceled', '!=', null)->
            where('services.date_canceled', '>=', $this->dateToUtc(date('c')))->
            group('services.id')->
            numResults();
    }

    /**
     * Fetches the number of active services
     *
     * @param int $company_id The company ID from which to get services
     * @return int The number of active services
     */
    public function getActiveServicesCount($company_id)
    {
        // Fetch the number of active services
        return $this->Record->select('services.id')->
            from('services')->
            innerJoin('clients', 'clients.id', '=', 'services.client_id', false)->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            where('client_groups.company_id', '=', $company_id)->
            where('services.status', '=', 'active')->
            group('services.id')->
            numResults();
    }

    /**
     * Fetches the number of services added within the given date range
     *
     * @param int $company_id The company ID from which to get services
     * @param string $start_date The start date timestamp
     * @param string $end_date The end date timestamp
     * @return int The number of services added within the given date range
     */
    public function getServicesAddedCount($company_id, $start_date, $end_date)
    {
        // Fetch services added
        return $this->Record->select('services.id')->
            from('services')->
            innerJoin('clients', 'clients.id', '=', 'services.client_id', false)->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            where('client_groups.company_id', '=', $company_id)->
            where('services.date_added', '>=', $this->dateToUtc($start_date))->
            where('services.date_added', '<=', $this->dateToUtc($end_date))->
            group('services.id')->
            numResults();
    }

    /**
     * Fetches the number of services canceled within the given date range
     *
     * @param int $company_id The company ID from which to get services
     * @param string $start_date The start date timestamp
     * @param string $end_date The end date timestamp
     * @return int The number of services canceled within the given date range
     */
    public function getServicesCanceledCount($company_id, $start_date, $end_date)
    {
        // Fetch services canceled
        return $this->Record->select('services.id')->
            from('services')->
            innerJoin('clients', 'clients.id', '=', 'services.client_id', false)->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            where('client_groups.company_id', '=', $company_id)->
            where('services.date_canceled', '!=', null)->
            where('services.date_canceled', '>=', $this->dateToUtc($start_date))->
            where('services.date_canceled', '<=', $this->dateToUtc($end_date))->
            group('services.id')->
            numResults();
    }
}
