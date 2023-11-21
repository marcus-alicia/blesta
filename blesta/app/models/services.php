<?php
/**
 * Service management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Services extends AppModel
{
    /**
     * Initialize Services
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['services']);
    }

    /**
     * Returns the number of results available for the given status
     *
     * @param int $client_id The ID of the client to select status count values for
     * @param string $status The status value to select a count of ('active', 'canceled', 'pending', 'suspended')
     * @param bool $children True to fetch all services, including child
     *  services, or false to fetch only services without a parent (optional, default true)
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - client_id The client ID (optional)
     *  - excluded_pricing_term The pricing term by which to exclude results (optional)
     *  - module_id The module ID on which to filter packages (optional)
     *  - pricing_period The pricing period for which to fetch services (optional)
     *  - package_id The package ID (optional)
     *  - package_name The (partial) name of the packages for which to fetch services (optional)
     *  - service_meta The (partial) value of meta data on which to filter services (optional)
     *  - status The status type of the services to fetch (optional, default 'active'):
     *    - active All active services
     *    - canceled All canceled services
     *    - pending All pending services
     *    - suspended All suspended services
     *    - in_review All services that require manual review before they may become pending
     *    - scheduled_cancellation All services scheduled to be canceled
     *    - all All active/canceled/pending/suspended/in_review
     *  - type The type of the services, it can be 'services', 'domains' or null for all (optional, default null)
     * @return int The number representing the total number of services for this client with that status
     */
    public function getStatusCount($client_id, $status = 'active', $children = true, array $filters = [])
    {
        return $this->getServices(
            array_merge($filters, ['client_id' => $client_id, 'status' => $status]),
            $children
        )->numResults();
    }

    /**
     * Returns a list of services for the given client and status
     *
     * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @param bool $children True to fetch all services, including child
     *  services, or false to fetch only services without a parent (optional, default true)
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - client_id The client ID (optional)
     *  - package_id The package ID (optional)
     *  - module_id The module ID on which to filter packages (optional)
     *  - package_name The (partial) name of the packages for which to fetch services (optional)
     *  - service_meta The (partial) value of meta data on which to filter services (optional)
     *  - status The status type of the services to fetch (optional, default 'active'):
     *    - active All active services
     *    - canceled All canceled services
     *    - pending All pending services
     *    - suspended All suspended services
     *    - in_review All services that require manual review before they may become pending
     *    - scheduled_cancellation All services scheduled to be canceled
     *    - all All active/canceled/pending/suspended/in_review
     *  - type The type of the services, it can be 'services', 'domains' or null for all (optional, default null)
     * @param array $formatted_filters The filter to apply with items in one or more of the following formats:
     *
     *  - table.column => value
     *  - ['column' => column, 'operator' => operator, 'value' => value]
     *  - table => [column1 => value, column2 => value]
     *  - table => [['column1' => column, 'operator' => operator, 'value' => value], column2 => value]
     * @return array An array of stdClass objects representing services
     */
    public function getAll(
        $order_by = ['date_added' => 'DESC'],
        $children = true,
        array $filters = [],
        array $formatted_filters = []
    ) {
        if (!isset($filters['status'])) {
            $filters['status'] = 'active';
        }

        // If sorting by term, sort by both term and period
        if (isset($order_by['term'])) {
            $temp_order_by = $order_by;

            $order_by = ['period' => $order_by['term'], 'term' => $order_by['term']];

            // Sort by any other fields given as well
            foreach ($temp_order_by as $sort => $order) {
                if ($sort == 'term') {
                    continue;
                }

                $order_by[$sort] = $order;
            }
        }

        // Get a list of services
        $services = $this->getServices($filters, $children, $formatted_filters)->
            order($order_by)->
            fetchAll();
        return $this->appendServiceInfo($services);
    }

    /**
     * Returns a list of services for the given client and status
     *
     * @param int $client_id The ID of the client to select services for
     * @param string $status The status to filter by (optional, default "active"), one of:
     *
     *  - active All active services
     *  - canceled All canceled services
     *  - pending All pending services
     *  - suspended All suspended services
     *  - in_review All services that require manual review before they may become pending
     *  - scheduled_cancellation All services scheduled to be canceled
     *  - all All active/canceled/pending/suspended/in_review
     * @param int $page The page to return results for (optional, default 1)
     * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @param bool $children True to fetch all services, including child
     *  services, or false to fetch only services without a parent (optional, default true)
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - client_id The client ID (optional)
     *  - excluded_pricing_term The pricing term by which to exclude results (optional)
     *  - module_id The module ID on which to filter packages (optional)
     *  - pricing_period The pricing period for which to fetch services (optional)
     *  - package_id The package ID (optional)
     *  - package_name The (partial) name of the packages for which to fetch services (optional)
     *  - service_meta The (partial) value of meta data on which to filter services (optional)
     *  - status The status type of the services to fetch (optional, default 'active'):
     *    - active All active services
     *    - canceled All canceled services
     *    - pending All pending services
     *    - suspended All suspended services
     *    - in_review All services that require manual review before they may become pending
     *    - scheduled_cancellation All services scheduled to be canceled
     *    - all All active/canceled/pending/suspended/in_review
     *  - type The type of the services, it can be 'services', 'domains' or null for all (optional, default null)
     * @return array An array of stdClass objects representing services
     */
    public function getList(
        $client_id = null,
        $status = 'active',
        $page = 1,
        $order_by = ['date_added' => 'DESC'],
        $children = true,
        array $filters = []
    ) {
        // If sorting by term, sort by both term and period
        if (isset($order_by['term'])) {
            $temp_order_by = $order_by;

            $order_by = ['period' => $order_by['term'], 'term' => $order_by['term']];

            // Sort by any other fields given as well
            foreach ($temp_order_by as $sort => $order) {
                if ($sort == 'term') {
                    continue;
                }

                $order_by[$sort] = $order;
            }
        }

        // Get a list of services
        $this->Record = $this->getServices(
            array_merge(['client_id' => $client_id, 'status' => $status], $filters),
            $children
        );

        $services = $this->Record->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->fetchAll();

        return $this->appendServiceInfo($services);
    }

    /**
     * Retrieves a list of all services in the system
     *
     * @param int $client_id The ID of the client whose services to retrieve (optional)
     * @param int $page The page to return results for (optional, default 1)
     * @return array An array of stdClass objects representing each service
     */
    public function getSimpleList($client_id = null, $page = 1)
    {
        return $this->Record->select()
            ->from('services')
            ->where('client_id', '=', $client_id)
            ->limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())
            ->fetchAll();
    }

    /**
     * Returns a list of services for the given client and status unpaged
     *
     * @param int $client_id The ID of the client to select services for
     * @param string $status The status to filter by (optional, default "active"), one of:
     *
     *  - active All active services
     *  - canceled All canceled services
     *  - pending All pending services
     *  - suspended All suspended services
     *  - in_review All services that require manual review before they may become pending
     *  - scheduled_cancellation All services scheduled to be canceled
     *  - all All active/canceled/pending/suspended/in_review
     *  - type The type of the services, it can be 'services', 'domains' or null for all (optional, default null)
     * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @param bool $children True to fetch all services, including child
     *  services, or false to fetch only services without a parent (optional, default true)
     * @param int $package_id The ID of the package for which to select services (optional)
     * @return array An array of stdClass objects representing services
     */
    public function getAllByClient(
        $client_id,
        $status = 'active',
        $order_by = ['date_added' => 'DESC'],
        $children = true,
        $package_id = null
    ) {
        // If sorting by term, sort by both term and period
        if (isset($order_by['term'])) {
            $temp_order_by = $order_by;

            $order_by = ['period' => $order_by['term'], 'term' => $order_by['term']];

            // Sort by any other fields given as well
            foreach ($temp_order_by as $sort => $order) {
                if ($sort == 'term') {
                    continue;
                }

                $order_by[$sort] = $order;
            }
        }

        // Get a list of services
        $this->Record = $this->getServices(
            ['client_id' => $client_id, 'status' => $status, 'package_id' => $package_id],
            $children
        );
        return $this->appendServiceInfo($this->Record->order($order_by)->fetchAll());
    }

    /**
     * Returns the total number of services for a client, useful
     * in constructing pagination for the getList() method.
     *
     * @param int $client_id The client ID
     * @param string $status The status type of the services to fetch (optional, default 'active'), one of:
     *
     *  - active All active services
     *  - canceled All canceled services
     *  - pending All pending services
     *  - suspended All suspended services
     *  - in_review All services that require manual review before they may become pending
     *  - scheduled_cancellation All services scheduled to be canceled
     *  - all All active/canceled/pending/suspended/in_review
     * @param bool $children True to fetch all services, including child
     *  services, or false to fetch only services without a parent (optional, default true)
     * @param int $package_id The ID of the package for which to select services (optional)
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - client_id The client ID (optional)
     *  - excluded_pricing_term The pricing term by which to exclude results (optional)
     *  - module_id The module ID on which to filter packages (optional)
     *  - pricing_period The pricing period for which to fetch services (optional)
     *  - package_id The package ID (optional)
     *  - package_name The (partial) name of the packages for which to fetch services (optional)
     *  - service_meta The (partial) value of meta data on which to filter services (optional)
     *  - status The status type of the services to fetch (optional, default 'active'):
     *    - active All active services
     *    - canceled All canceled services
     *    - pending All pending services
     *    - suspended All suspended services
     *    - in_review All services that require manual review before they may become pending
     *    - scheduled_cancellation All services scheduled to be canceled
     *    - all All active/canceled/pending/suspended/in_review
     *  - type The type of the services, it can be 'services', 'domains' or null for all (optional, default null)
     * @return int The total number of services
     * @see Services::getList()
     */
    public function getListCount(
        $client_id,
        $status = 'active',
        $children = true,
        $package_id = null,
        array $filters = []
    ) {
        $this->Record = $this->getServices(
            array_merge(['client_id' => $client_id, 'status' => $status, 'package_id' => $package_id], $filters),
            $children
        );

        // Return the number of results
        return $this->Record->numResults();
    }

    /**
     * Search services
     *
     * @param string $query The value to search services for
     * @param int $page The page number of results to fetch (optional, default 1)
     * @param bool $search_fields If true will also search service fields for the value
     * @return array An array of services that match the search criteria
     */
    public function search($query, $page = 1, $search_fields = false)
    {
        $this->Record = $this->searchServices($query, $search_fields);

        // Set order by clause
        $order_by = [];
        if (Configure::get('Blesta.id_code_sort_mode')) {
            foreach ((array)Configure::get('Blesta.id_code_sort_mode') as $key) {
                $order_by[$key] = 'ASC';
            }
        } else {
            $order_by = ['date_added' => 'ASC'];
        }

        return $this->Record->group(['temp.id'])->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->fetchAll();
    }

    /**
     * Return the total number of services returned from Services::search(), useful
     * in constructing pagination
     *
     * @param string $query The value to search services for
     * @param bool $search_fields True to search service fields for the given $query value, or false to not search them
     * @return int The number of results
     * @see Transactions::search()
     */
    public function getSearchCount($query, $search_fields = false)
    {
        $this->Record = $this->searchServices($query, $search_fields);
        return $this->Record->group(['temp.id'])->numResults();
    }

    /**
     * Determines whether a service has a parent services of the given status
     *
     * @param int $service_id The ID of the service to check
     * @return bool True if the service has a parent, false otherwise
     */
    public function hasParent($service_id)
    {
        return (boolean)$this->Record->select()->from('services')->
            where('parent_service_id', '!=', null)->
            where('id', '=', $service_id)->fetch();
    }

    /**
     * Determines whether a service has any child services of the given status
     *
     * @param int $service_id The ID of the service to check
     * @param string $status The status of any child services to filter on
     *  (e.g. "active", "canceled", "pending", "suspended", "in_review", or
     *  null for any status) (optional, default null)
     * @return bool True if the service has children, false otherwise
     */
    public function hasChildren($service_id, $status = null)
    {
        $this->Record->select()->from('services')->
            where('parent_service_id', '=', $service_id);

        if ($status) {
            $this->Record->where('status', '=', $status);
        }

        return ($this->Record->numResults() > 0);
    }

    /**
     * Retrieves a list of all services that are child of the given parent service ID
     *
     * @param int $parent_service_id The ID of the parent service whose child services to fetch
     * @param string $status The status type of the services to fetch (optional, default 'all'):
     *
     *  - active All active services
     *  - canceled All canceled services
     *  - pending All pending services
     *  - suspended All suspended services
     *  - in_review All services that require manual review before they may become pending
     *  - scheduled_cancellation All services scheduled to be canceled
     *  - all All active/canceled/pending/suspended/in_review
     * @return array A list of stdClass objects representing each child service
     */
    public function getAllChildren($parent_service_id, $status = 'all')
    {
        // Get all child services
        $services = $this->getServices(['status' => $status])->
            where('services.parent_service_id', '=', $parent_service_id)->
            fetchAll();

        return $this->appendServiceInfo($services);
    }

    /**
     * Retrieves the total number of add-on/children services for the given parent service ID
     *
     * @param int $parent_service_id The ID of the parent service whose add-on-children services to count
     * @param string $status The status type of the services to fetch (optional, default 'all'):
     *
     *  - active All active services
     *  - canceled All canceled services
     *  - pending All pending services
     *  - suspended All suspended services
     *  - in_review All services that require manual review before they may become pending
     *  - scheduled_cancellation All services scheduled to be canceled
     *  - all All active/canceled/pending/suspended/in_review
     * @return int The total number of child services
     */
    public function getAllChildrenCount($parent_service_id, $status = 'all')
    {
        // Get all child services
        return $this->getServices(['status' => $status])
            ->where('services.parent_service_id', '=', $parent_service_id)
            ->numResults();
    }

    /**
     * Retrieves the date on which the next invoice is expected to be generated for a service
     *
     * @param int $service_id The ID of the service whose next invoice date to fetch
     * @param string $format The date format to return the date in (optional, default 'Y-m-d H:i:s')
     * @return mixed The next expected invoice date in UTC, or null if no further invoices are expected to be generated
     */
    public function getNextInvoiceDate($service_id, $format = 'Y-m-d H:i:s')
    {
        // Fetch the service
        $service = $this->Record->select(['services.*', 'client_groups.id' => 'client_group_id'])->
            from('services')->
            innerJoin('clients', 'clients.id', '=', 'services.client_id', false)->
                on('client_groups.company_id', '=', Configure::get('Blesta.company_id'))->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            where('services.id', '=', $service_id)->fetch();

        // No expected renewal
        if (!$service || empty($service->date_renews)) {
            return null;
        }

        // Get the invoice days before renewal, and whether services can be renewed (invoiced) when suspended
        Loader::loadModels($this, ['ClientGroups']);
        Loader::loadHelpers($this, ['Form']);
        $client_group_settings = $this->ClientGroups->getSettings($service->client_group_id);
        $client_group_settings = $this->Form->collapseObjectArray($client_group_settings, 'value', 'key');
        $inv_suspended_services = (isset($client_group_settings['inv_suspended_services'])
            && $client_group_settings['inv_suspended_services'] == 'true')
                ? true
                : false;
        $inv_days_before_renewal = abs((int)$client_group_settings['inv_days_before_renewal']);
        unset($client_group_settings);

        // Set the date at which invoices would be created based on the
        // renew date and invoice days before renewal
        $invoice_date = $this->dateToUtc(
            $this->Date->modify(
                $service->date_renews . 'Z',
                '-' . $inv_days_before_renewal . ' days',
                'c',
                Configure::get('Blesta.company_timezone')
            ),
            'c'
        );

        if ($service->status == 'active' || ($inv_suspended_services && $service->status == 'suspended')) {
            return $this->Date->cast($invoice_date, $format);
        }
        return null;
    }

    /**
     * Retrieves a list of services ready to be renewed for this client group
     *
     * @param int $client_group_id The client group ID to fetch renewing services from
     * @return array A list of stdClass objects representing services set ready to be renewed
     */
    public function getAllRenewing($client_group_id)
    {
        Loader::loadModels($this, ['ClientGroups']);
        Loader::loadHelpers($this, ['Form']);

        // Determine whether services can be renewed (invoiced) if suspended
        $client_group_settings = $this->ClientGroups->getSettings($client_group_id);
        $client_group_settings = $this->Form->collapseObjectArray($client_group_settings, 'value', 'key');
        $inv_suspended_services = (isset($client_group_settings['inv_suspended_services'])
            && $client_group_settings['inv_suspended_services'] == 'true')
                ? true
                : false;
        $inv_days_before_renewal = abs((int)$client_group_settings['inv_days_before_renewal']);
        unset($client_group_settings);

        $fields = [
            'services.*',
            'pricings.term', 'pricings.period', 'pricings.price', 'pricings.price_renews',
            'pricings.price_transfer', 'pricings.setup_fee', 'pricings.cancel_fee', 'pricings.currency',
            'packages.id' => 'package_id', 'package_names.name'
        ];

        $this->Record->select($fields)->
            from('services')->
            innerJoin('package_pricing', 'package_pricing.id', '=', 'services.pricing_id', false)->
            innerJoin('pricings', 'pricings.id', '=', 'package_pricing.pricing_id', false)->
            innerJoin('packages', 'packages.id', '=', 'package_pricing.package_id', false)->
            on('package_names.lang', '=', Configure::get('Blesta.language'))->
            leftJoin('package_names', 'package_names.package_id', '=', 'packages.id', false)->
            innerJoin('clients', 'services.client_id', '=', 'clients.id', false)->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            open()->
                where('services.status', '=', 'active');

        // Also invoice suspended services
        if ($inv_suspended_services) {
            $this->Record->orWhere('services.status', '=', 'suspended');
        }

        $this->Record->close();

        // Ensure only fetching records for the current company
        // whose renew date is <= (today + invoice days before renewal)
        $invoice_date = $this->Date->modify(
            date('c'),
            '+' . $inv_days_before_renewal . ' days',
            'Y-m-d 23:59:59',
            Configure::get('Blesta.company_timezone')
        );
        $this->Record->where('client_groups.company_id', '=', Configure::get('Blesta.company_id'))->
            where('client_groups.id', '=', $client_group_id)->
            where('services.date_renews', '!=', null)->
            where('pricings.period', '!=', 'onetime')->
            where('pricings.term', '>', '0')->
            where('services.date_renews', '<=', $this->dateToUtc($invoice_date))->
            open()->
                where('services.date_canceled', '=', null)->
                orWhere('services.date_canceled', '>', 'services.date_renews', false)->
            close()->
            order(['services.client_id' => 'ASC']);

        return $this->appendServiceInfo($this->Record->fetchAll());
    }

    /**
     * Retrieves all renewable paid services
     *
     * @param string $date The date after which to fetch paid renewable services (deprecated)
     * @param bool $include_failed_renewals Wether to include services
     *     that have already failed renewal the maximum number of times
     * @return array A list of services that have been paid and may be processed
     */
    public function getAllRenewablePaid($date = null, $include_failed_renewals = false)
    {
        return $this->getRenewablePaidQuery($include_failed_renewals)->fetchAll();
    }

    /**
     * Retrieves a list of renewable paid services
     *
     * @param bool $include_failed_renewals Wether to include services
     *     that have already failed renewal the maximum number of times
     * @param int $page The page to return results for (optional, default 1)
     * @param array $order_by The sort and order conditions (e.g. array('sort_field' => "ASC"), optional)
     * @return array A paginated list of services that have been paid and may be processed
     */
    public function getRenewablePaidList(
        $include_failed_renewals = false,
        $page = 1,
        $order_by = ['date_added' => 'DESC']
    ) {
        return $this->getRenewablePaidQuery($include_failed_renewals)->
            order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->
            fetchAll();
    }

    /**
     * Retrieves a count of renewable paid services
     *
     * @param bool $include_failed_renewals Wether to include services
     *     that have already failed renewal the maximum number of times
     * @return array A list of services that have been paid and may be processed
     */
    public function getRenewablePaidCount($include_failed_renewals = false)
    {
        return $this->getRenewablePaidQuery($include_failed_renewals)->numResults();
    }

    /**
     * Retrieves a query for renewable paid services
     *
     * @param bool $include_failed_renewals Wether to include services
     *     that have already failed renewal the maximum number of times
     * @return PDOStatement A query for services that have been paid and may be processed
     */
    public function getRenewablePaidQuery($include_failed_renewals = false)
    {
        // Get all active services
        $this->Record = $this->getServices();

        $sub_query_sql = $this->Record->get();
        $values = $this->Record->values;
        $this->Record->reset();

        // Get all invoices and attached services greater than the given date
        $this->Record->select(
                [
                    'temp_services.*',
                    'invoices.id' => 'renewal_invoice_id',
                    'service_invoices.failed_attempts',
                    'service_invoices.maximum_attempts'
                ]
            )->
            from('invoices')->
            innerJoin('invoice_lines', 'invoice_lines.invoice_id', '=', 'invoices.id', false)->
            appendValues($values)->
            on('service_invoices.service_id', '=', 'invoice_lines.service_id', false)->
            innerJoin('service_invoices', 'service_invoices.invoice_id', '=', 'invoice_lines.invoice_id', false)->
            innerJoin(
                [$sub_query_sql => 'temp_services'],
                'temp_services.id',
                '=',
                'service_invoices.service_id',
                false
            )->
            where('invoices.date_closed', '!=', null);

        if (!$include_failed_renewals) {
            $this->Record->where('service_invoices.failed_attempts', '<', 'service_invoices.maximum_attempts', false);
        }

        return $this->Record->group(['temp_services.id']);
    }

    /**
     * Retrieves a list of paid pending services
     *
     * @param int $client_group_id The ID of the client group whose paid pending invoices to fetch
     * @return array A list of services that have been paid and are still pending
     */
    public function getAllPaidPending($client_group_id)
    {
        $current_time = $this->dateToUtc(date('c'));

        // Get pending services that are neither canceled nor suspended
        $this->Record = $this->getServices(['status' => 'pending']);
        $this->Record->open()->
                where('services.date_suspended', '=', null)->
                orWhere('services.date_suspended', '>', $current_time)->
            close()->
            open()->
                where('services.date_canceled', '=', null)->
                orWhere('services.date_canceled', '>', $current_time)->
            close();

        $sub_query_sql = $this->Record->get();
        $values = $this->Record->values;
        $this->Record->reset();

        // Get all pending services that have been paid
        $services = $this->Record->select(['temp_services.*'])->
            appendValues($values)->
            from([$sub_query_sql => 'temp_services'])->
            leftJoin('invoice_lines', 'temp_services.id', '=', 'invoice_lines.service_id', false)->
            on('invoices.status', 'in', ['active', 'proforma'])->
            leftJoin('invoices', 'invoice_lines.invoice_id', '=', 'invoices.id', false)->
            innerJoin('clients', 'clients.id', '=', 'temp_services.client_id', false)->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            where('client_groups.id', '=', $client_group_id)->
            open()->
                open()->
                    where('temp_services.staff_id', '!=', null)->
                    where('invoices.id', '=', null)->
                close()->
                orWhere('invoices.date_closed', '!=', null)->
            close()->
            group(['temp_services.id'])->
            fetchAll();

        return $this->appendServiceInfo($services);
    }

    /**
     * Retrieves a list of services ready to be suspended
     *
     * @param int $client_group_id The ID of the client group
     * @param string $suspension_date The date before which service would be considered suspended
     * @return array A list of stdClass objects representing services pending suspension
     */
    public function getAllPendingSuspension($client_group_id, $suspension_date)
    {
        $this->Record = $this->getServices(['status' => 'active']);

        $services = $this->Record->
            innerJoin('invoice_lines', 'invoice_lines.service_id', '=', 'services.id', false)->
            innerJoin('invoices', 'invoices.id', '=', 'invoice_lines.invoice_id', false)->
            leftJoin('service_changes', 'service_changes.invoice_id', '=', 'invoices.id', false)->
            where('invoices.status', 'in', ['active', 'proforma'])->
            where('invoices.date_closed', '=', null)->
            where('invoices.date_due', '<=', $this->dateToUtc($suspension_date))->
            where('service_changes.id', '=', null)->
            where('client_groups.id', '=', $client_group_id)->
            group(['services.id'])->
            fetchAll();

        return $this->appendServiceInfo($services);
    }

    /**
     * Retrieves a list of paid suspended services ready to be unsuspended. Will
     * only return services that were automatically suspended (not manually
     * suspended by a staff member).
     *
     * @param int $client_group_id The ID of the client group
     * @return array A list of stdClass objects representing services pending unsuspension
     */
    public function getAllPendingUnsuspension($client_group_id)
    {
        $log_services = clone $this->Record;
        $log_services_sql = $log_services->select(['MAX(log_services.id)' => 'id'])->
            from('log_services')->where('log_services.status', '=', 'suspended')->
            where('log_services.service_id', '=', 'services.id', false)->
            group(['log_services.service_id'])->get();
        $log_services_values = $log_services->values;
        unset($log_services);

        $invoices = clone $this->Record;
        $invoices_sql = $invoices->select(['invoice_lines.service_id'])->
            from('invoice_lines')->
            on('invoices.status', 'in', ['active', 'proforma'])->
            on('invoices.date_closed', '=', null)->
            on('invoices.date_due', '<=', $this->dateToUtc(date('c')))->
            innerJoin('invoices', 'invoices.id', '=', 'invoice_lines.invoice_id', false)->
            where('invoice_lines.service_id', '=', 'services.id', false)->
            group(['invoice_lines.service_id'])->get();
        $invoices_values = $invoices->values;
        unset($invoices);

        $this->Record = $this->getServices(['status' => 'suspended']);

        $sql = $this->Record->innerJoin('log_services', 'log_services.service_id', '=', 'services.id', false)->
            where('log_services.staff_id', '=', null)->
            where('client_groups.id', '=', $client_group_id)->
            where('log_services.id', 'in', [$log_services_sql], false)->
            where('services.id', 'notin', [$invoices_sql], false)->
            group(['log_services.service_id'])->get();

        $values = $this->Record->values;
        $this->Record->reset();

        $services = $this->Record->query($sql, array_merge($values, $log_services_values, $invoices_values))
            ->fetchAll();

        return $this->appendServiceInfo($services);
    }

    /**
     * Retrieves a list of services ready to be canceled
     *
     * @return array A list of stdClass objects representing services pending cancelation
     */
    public function getAllPendingCancelation()
    {
        // Get services set to be canceled
        $this->Record = $this->getServices(['status' => 'all']);
        $services = $this->Record->where('services.date_canceled', '<=', $this->dateToUtc(date('c')))->
            open()->
                where('services.status', '=', 'active')->
                orWhere('services.status', '=', 'suspended')->
            close()->
            fetchAll();

        return $this->appendServiceInfo($services);
    }

    /**
     * Adds more information to the given service
     *
     * @param array $services A list of stdClass objects each representing a service
     * @return array An array of services with additional service information
     */
    private function appendServiceInfo(array $services)
    {
        if (!isset($this->ModuleManager)) {
            Loader::loadModels($this, ['ModuleManager']);
        }
        if (!isset($this->Packages)) {
            Loader::loadModels($this, ['Packages']);
        }

        // Fetch each services' fields and add them to the list
        foreach ($services as &$service) {
            // Get all fields
            $service->fields = $this->getFields($service->id);
            // Collect service options
            $service->options = $this->getOptions($service->id);
            // Collect package pricing data
            $service->package_pricing = $this->getPackagePricing($service->pricing_id);
            // Collect package data
            $service->package = $this->Packages->get($service->package_pricing->package_id);
            // Get the service name from the package
            $service->name = (isset($service->package->name) ? $service->package->name : '');

            // Determine the module ID so we can fetch the updated service name from it
            $module_id = false;
            if (isset($service->package->module_id)) {
                $module_id = $service->package->module_id;
            } else {
                $module_data = $this->Record->select('module_id')
                    ->from('module_rows')
                    ->where('id', '=', $service->module_row_id)
                    ->fetch();
                $module_id = ($module_data && isset($module_data->module_id) ? $module_data->module_id : $module_id);
            }

            // Attempt to revise the service name from the module
            if ($module_id) {
                // Get service label from the module
                $service->name = $this->ModuleManager->moduleRpc(
                    $module_id,
                    'getServiceName',
                    [$service]
                );
            }
        }

        return $services;
    }

    /**
     * Searches services of the given module that contains the given service
     * field key/value pair.
     *
     * @param int $module_id The ID of the module to search services on
     * @param string $key They service field key to search
     * @param string $value The service field value to search
     * @return array An array of stdClass objects, each containing a service
     */
    public function searchServiceFields($module_id, $key, $value)
    {
        $this->Record = $this->getServices(['status' => 'all']);
        return $this->Record->innerJoin('module_rows', 'module_rows.id', '=', 'services.module_row_id', false)->
            on('service_fields.key', '=', $key)->on('service_fields.value', '=', $value)->
            innerJoin('service_fields', 'service_fields.service_id', '=', 'services.id', false)->
            where('module_rows.module_id', '=', $module_id)->
            group('services.id')->fetchAll();
    }

    /**
     * Partially constructs the query for searching services
     *
     * @param string $query The value to search services for
     * @param bool $search_fields If true will also search service fields for the value
     * @return Record The partially constructed query Record object
     * @see Services::search(), Services::getSearchCount()
     */
    private function searchServices($query, $search_fields = false)
    {
        $this->Record = $this->getServices(['status' => 'all']);

        if ($search_fields) {
            $this->Record->select(['service_fields.value' => 'service_field_value'])->
                leftJoin('module_rows', 'module_rows.id', '=', 'services.module_row_id', false)->
                on('service_fields.encrypted', '=', 0)->
                on('service_fields.value', 'like', '%' . $query . '%')->
                leftJoin('service_fields', 'service_fields.service_id', '=', 'services.id', false);
        }

        $sub_query_sql = $this->Record->get();
        $values = $this->Record->values;
        $this->Record->reset();

        $this->Record = $this->Record->select()->appendValues($values)->from([$sub_query_sql => 'temp'])->
            like('CONVERT(temp.id_code USING utf8)', '%' . $query . '%', true, false)->
            orLike('temp.name', '%' . $query . '%');
        if ($search_fields) {
            $this->Record->orLike('temp.service_field_value', '%' . $query . '%');
        }

        return $this->Record;
    }

    /**
     * Partially constructs the query required by Services::getList() and others
     *
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - client_id The client ID (optional)
     *  - excluded_pricing_term The pricing term by which to exclude results (optional)
     *  - module_id The module ID on which to filter packages (optional)
     *  - pricing_period The pricing period for which to fetch services (optional)
     *  - package_id The package ID (optional)
     *  - package_name The (partial) name of the packages for which to fetch services (optional)
     *  - service_meta The (partial) value of meta data on which to filter services (optional)
     *  - status The status type of the services to fetch (optional, default 'active'):
     *    - active All active services
     *    - canceled All canceled services
     *    - pending All pending services
     *    - suspended All suspended services
     *    - in_review All services that require manual review before they may become pending
     *    - scheduled_cancellation All services scheduled to be canceled
     *    - all All active/canceled/pending/suspended/in_review
     *  - type The type of the services, it can be 'services', 'domains' or null for all (optional, default null)
     * @param bool $children True to fetch all services, including child
     *  services, or false to fetch only services without a parent (optional, default true)
     * @param array $formatted_filters The filter to apply with items in one or more of the following formats:
     *
     *  - table.column => value
     *  - ['column' => column, 'operator' => operator, 'value' => value]
     *  - table => [column1 => value, column2 => value]
     *  - table => [['column1' => column, 'operator' => operator, 'value' => value], column2 => value]
     * @return Record The partially constructed query Record object
     */
    private function getServices(array $filters = [], $children = true, array $formatted_filters = [])
    {
        if (!isset($this->ModuleManager)) {
            Loader::loadModels($this, ['ModuleManager']);
        }

        $default_filters = [
            'client_id' => null,
            'excluded_pricing_term' => null,
            'module_id' => null,
            'package_group_id' => null,
            'package_id' => null,
            'pricing_period' => null,
            'status' => 'active',
        ];

        // Set default filters
        foreach ($default_filters as $default_filter => $default_value) {
            if (empty($filters[$default_filter])) {
                $filters[$default_filter] = $default_value;
            }
        }

        $fields = [
            'services.*',
            'REPLACE(services.id_format, ?, services.id_value)' => 'id_code',
            'REPLACE(clients.id_format, ?, clients.id_value)' => 'client_id_code',
            'pricings.term', 'pricings.period', 'package_names.name',
            'contacts.first_name' => 'client_first_name',
            'contacts.last_name' => 'client_last_name',
            'contacts.company' => 'client_company',
            'contacts.address1' => 'client_address1',
            'contacts.email' => 'client_email',
            'packages.prorata_day' => 'package_prorata_day',
            'packages.id' => 'package_id'
        ];

        $this->Record->select($fields)
            ->appendValues([
                $this->replacement_keys['services']['ID_VALUE_TAG'],
                $this->replacement_keys['clients']['ID_VALUE_TAG']
            ])
            ->from('services')
            ->innerJoin('package_pricing', 'package_pricing.id', '=', 'services.pricing_id', false)
            ->innerJoin('pricings', 'pricings.id', '=', 'package_pricing.pricing_id', false)
            ->innerJoin('packages', 'packages.id', '=', 'package_pricing.package_id', false)
            ->on('package_names.lang', '=', Configure::get('Blesta.language'))
            ->leftJoin('package_names', 'package_names.package_id', '=', 'packages.id', false)
            ->innerJoin('clients', 'services.client_id', '=', 'clients.id', false)
            ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)
            ->on('contacts.contact_type', '=', 'primary')
            ->innerJoin('contacts', 'contacts.client_id', '=', 'clients.id', false);

        // Filter out child services
        if (!$children) {
            $this->Record->where('services.parent_service_id', '=', null);
        }

        // Format filters
        if ($filters['client_id'] !== null) {
            $formatted_filters['services']['client_id'] = $filters['client_id'];
        }
        unset($filters['client_id']);

        if ($filters['package_group_id'] !== null) {
            $formatted_filters['services']['package_group_id'] = $filters['package_group_id'];
        }
        unset($filters['package_group_id']);

        if ($filters['package_id'] !== null) {
            $formatted_filters['packages']['id'] = $filters['package_id'];
        }
        unset($filters['package_id']);

        if ($filters['excluded_pricing_term'] !== null) {
            $formatted_filters['pricings'][] = [
                'column' => 'term',
                'operator' => '!=',
                'value' => $filters['excluded_pricing_term']
            ];
        }
        unset($filters['excluded_pricing_term']);

        if ($filters['pricing_period'] !== null) {
            $formatted_filters['pricings']['period'] = $filters['pricing_period'];
        }
        unset($filters['pricing_period']);

        if ($filters['module_id'] !== null) {
            $formatted_filters['packages']['module_id'] = $filters['module_id'];
        }
        unset($filters['module_id']);

        if (!empty($filters['package_name'])) {
            $this->Record->open()
                    ->where('package_names.lang', '=', Configure::get('Blesta.language'))
                    ->where('package_names.name', 'LIKE', '%' . $filters['package_name'] . '%')
                ->close();
        }
        unset($filters['package_name']);

        $this->Record = $this->applyFilters($this->Record, $formatted_filters);

        // Filter on service meta data
        if (!empty($filters['service_meta'])) {
            $this->Record->on('service_fields.value', 'LIKE', '%' . $filters['service_meta'] . '%')
                ->innerJoin('service_fields', 'service_fields.service_id', '=', 'services.id', false);
        }

        // Filter on status
        if ($filters['status'] != 'all') {
            $custom_statuses = ['scheduled_cancellation'];

            if (!in_array($filters['status'], $custom_statuses)) {
                $this->Record->where('services.status', '=', $filters['status']);
            } else {
                // Custom status type
                switch ($filters['status']) {
                    case 'scheduled_cancellation':
                        $this->Record->where('services.date_canceled', '>', $this->dateToUtc(date('c')));
                        break;
                    default:
                        break;
                }
            }
        }

        // Filter on type
        if (isset($filters['type']) && !is_null($filters['type'])) {
            // Build a list of all the registrar modules
            $registrar_modules = [];
            $modules = $this->ModuleManager->getInstalled(['type' => 'registrar']);

            foreach ($modules as $module) {
                $registrar_modules[] = $module->id;
            }

            // Filter by domains
            if ($filters['type'] == 'domains') {
                $this->Record->where(
                    'packages.module_id',
                    'in',
                    (empty($registrar_modules) ? [null] : $registrar_modules)
                );
            }

            // Filter by services
            if ($filters['type'] == 'services' && !empty($registrar_modules)) {
                $this->Record->where('packages.module_id', 'not in', $registrar_modules);
            }
        }

        // Ensure only fetching records for the current company
        $this->Record->where('client_groups.company_id', '=', Configure::get('Blesta.company_id'));

        return $this->Record->group('services.id');
    }

    /**
     * Fetches the pricing information for a service
     *
     * @param int $service_id The ID of the service whose pricing info te fetch
     * @param string $currency_code The ISO 4217 currency code to convert
     *  pricing to (optional, defaults to service's currency)
     * @return mixed An stdClass object representing service pricing fields, or false if none exist
     */
    public function getPricingInfo($service_id, $currency_code = null)
    {
        Loader::loadModels($this, ['Currencies']);

        $fields = [
            'services.*',
            'pricings.term', 'pricings.period',
            'IFNULL(services.override_price, pricings.price)' => 'price',
            'pricings.setup_fee', 'pricings.cancel_fee',
            'IFNULL(services.override_currency, pricings.currency)' => 'currency',
            'package_names.name', 'packages.id' => 'package_id', 'packages.module_id', 'packages.taxable'
        ];

        $service = $this->Record->select($fields)->from('services')->
            innerJoin('package_pricing', 'package_pricing.id', '=', 'services.pricing_id', false)->
            innerJoin('pricings', 'pricings.id', '=', 'package_pricing.pricing_id', false)->
            innerJoin('packages', 'packages.id', '=', 'package_pricing.package_id', false)->
            on('package_names.lang', '=', Configure::get('Blesta.language'))->
            leftJoin('package_names', 'package_names.package_id', '=', 'packages.id', false)->
            innerJoin('clients', 'services.client_id', '=', 'clients.id', false)->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            where('services.id', '=', $service_id)->
            fetch();

        if ($service) {
            // Include additional service information
            $services = $this->appendServiceInfo([$service]);
            $service = $services[0];

            // Get the client setting for tax exemption
            Loader::loadComponents($this, ['SettingsCollection']);
            $tax_exempt = $this->SettingsCollection->fetchClientSetting($service->client_id, null, 'tax_exempt');
            $tax_exempt = (isset($tax_exempt['value']) && $tax_exempt['value'] == 'true' ? true : false);

            // Set the pricing info to return
            $taxable = (!$tax_exempt && ($service->taxable == '1'));
            $pricing_info = [
                'package_name' => (isset($service->package->name) ? $service->package->name : ''),
                'name' => $service->name,
                'price' => $service->price,
                'tax' => $taxable,
                'setup_fee' => $service->setup_fee,
                'cancel_fee' => $service->cancel_fee,
                'currency' => ($currency_code ? strtoupper($currency_code) : $service->currency)
            ];

            // Convert amounts if another currency has been given
            if ($currency_code && $currency_code != $service->currency) {
                $pricing_info['price'] = $this->Currencies->convert(
                    $service->price,
                    $service->currency,
                    $currency_code,
                    Configure::get('Blesta.company_id')
                );
                $pricing_info['setup_fee'] = $this->Currencies->convert(
                    $service->setup_fee,
                    $service->currency,
                    $currency_code,
                    Configure::get('Blesta.company_id')
                );
                $pricing_info['cancel_fee'] = $this->Currencies->convert(
                    $service->cancel_fee,
                    $service->currency,
                    $currency_code,
                    Configure::get('Blesta.company_id')
                );
            }

            return (object)$pricing_info;
        }

        return false;
    }

    /**
     * Fetch a single service, including service field data
     *
     * @param int $service_id The ID of the service to fetch
     * @return mixed A stdClass object representing the service, false if no such service exists
     */
    public function get($service_id)
    {
        $fields = ['services.*', 'REPLACE(services.id_format, ?, services.id_value)' => 'id_code'];

        $service = $this->Record->select($fields)
            ->appendValues([$this->replacement_keys['services']['ID_VALUE_TAG']])
            ->from('services')
            ->where('id', '=', $service_id)
            ->fetch();

        // Include additional service info
        if ($service) {
            $services = $this->appendServiceInfo([$service]);
            $service = $services[0];
        }

        return $service;
    }

    /**
     * Get package pricing
     *
     * @param int $pricing_id
     * @return mixed stdClass object representing the package pricing, false otherwise
     */
    public function getPackagePricing($pricing_id)
    {
        $fields = ['package_pricing.*', 'pricings.term',
            'pricings.period', 'pricings.price', 'pricings.price_renews', 'pricings.price_transfer', 'pricings.setup_fee',
            'pricings.cancel_fee', 'pricings.currency'
        ];
        return $this->Record->select($fields)->
            from('package_pricing')->
            innerJoin('pricings', 'pricings.id', '=', 'package_pricing.pricing_id', false)->
            where('package_pricing.id', '=', $pricing_id)->fetch();
    }

    /**
     * Adds a new service to the system
     *
     * @param array $vars An array of service info including:
     *
     *  - parent_service_id The ID of the service this service is a child of (optional)
     *  - package_group_id The ID of the package group this service was added from (optional)
     *  - pricing_id The package pricing schedule ID for this service
     *  - client_id The ID of the client to add the service under
     *  - staff_id The ID of the staff member that added the service
     *  - module_row_id The module row to add the service under (optional, default module will decide)
     *  - coupon_id The ID of the coupon used for this service (optional)
     *  - qty The quantity consumed by this service (optional, default 1)
     *  - override_price The price to set for this service, overriding the
     *      package pricing value for the selected term (optional, default null)
     *  - override_currency The currency to set for this service,
     *      overriding the package pricing value for the selected term (optional, default null)
     *  - status The status of this service (optional, default 'pending'):
     *      - active
     *      - canceled
     *      - pending
     *      - suspended
     *      - in_review
     *  - suspension_reason The reason a service is being suspended
     *  - date_added The date this service is added (default to today's date UTC)
     *  - date_renews The date the service renews (optional, default calculated by package term)
     *  - date_last_renewed The date the service last renewed (optional)
     *  - date_suspended The date the service was last suspended (optional)
     *  - date_canceled The date the service was last canceled (optional)
     *  - use_module Whether or not to use the module when creating the
     *      service ('true','false', default 'true', forced 'false' if status is 'pending' or 'in_review')
     *  - configoptions An array of key/value pairs of package options
     *      where the key is the package option ID and the value is the option value (optional)
     *  - * Any other service field data to pass to the module
     * @param array $packages An array of packages ordered along with this service to determine
     *  if the given coupon may be applied given in one of the following formats:
     *
     *  - A numerically indexed array of package IDs
     *  - An array of package IDs and pricing IDs [packageID => pricingID]
     * @param bool $notify True to notify the client by email regarding this
     *  service creation, false to not send any notification (optional, default false)
     * @return int The ID of this service, void if error
     */
    public function add(array $vars, array $packages = null, $notify = false)
    {
        // Trigger the Services.addBefore event
        extract($this->executeAndParseEvent('Services.addBefore', compact('vars', 'packages', 'notify')));

        // Remove config options with 0 quantity
        if (isset($vars['configoptions']) && is_array($vars['configoptions'])) {
            $vars['configoptions'] = $this->formatConfigOptions($vars['configoptions']);
        }

        // Validate that the service can be added
        $vars = $this->validate($vars, $packages);

        if ($errors = $this->Input->errors()) {
            return;
        }

        if (!isset($vars['status'])) {
            $vars['status'] = 'pending';
        }
        if (!isset($vars['use_module'])) {
            $vars['use_module'] = 'true';
        }

        // If status is pending or in_review can't allow module to add
        if ($vars['status'] == 'pending' || $vars['status'] == 'in_review') {
            $vars['use_module'] = 'false';
        }

        if (!isset($this->ModuleManager)) {
            Loader::loadModels($this, ['ModuleManager']);
        }
        if (!isset($this->Packages)) {
            Loader::loadModels($this, ['Packages']);
        }
        if (!isset($this->PackageOptions)) {
            Loader::loadModels($this, ['PackageOptions']);
        }
        if (!isset($this->Pricings)) {
            Loader::loadModels($this, ['Pricings']);
        }

        $module_data = $this->getModuleClassByPricingId($vars['pricing_id']);

        if ($module_data) {
            $module = $this->ModuleManager->initModule($module_data->id, Configure::get('Blesta.company_id'));

            if ($module) {
                // Find the package and parent service/package used for this service
                $parent_package = null;
                $parent_service = null;
                $package = $this->Packages->getByPricingId($vars['pricing_id']);

                // Filter config options that may be set
                if (isset($vars['configoptions']) && is_array($vars['configoptions'])) {
                    // Fetch the addable service options for the new service
                    $service_options = $this->getServiceOptions($vars['pricing_id'], $vars['configoptions']);
                    $temp_config_options = [];

                    // Filter out config options that may be added from the list given
                    foreach ($service_options['add'] as $option_id => $temp_pricing_id) {
                        if (array_key_exists($option_id, (array)$vars['configoptions'])) {
                            $temp_config_options[$option_id] = $vars['configoptions'][$option_id];
                        }
                    }

                    // Set the filtered config options
                    $vars['configoptions'] = $temp_config_options;
                    $config_options = $vars['configoptions'];

                    // Check if module row has been provided as a configurable option
                    $module_row_fields = $this->getConfigurableModuleFields($vars['configoptions']);
                    if (!empty($module_row_fields['module_row'])
                        && $module->getModuleRow($module_row_fields['module_row'])
                    ) {
                        $vars['module_row_id'] = $module_row_fields['module_row'];
                    } elseif (!empty($module_row_fields['module_group'])
                        && ($module_row_id = $module->selectModuleRow($module_row_fields['module_group']))
                    ) {
                        $vars['module_row_id'] = $module_row_id;
                    }
                }

                if (isset($vars['parent_service_id'])) {
                    $parent_service = $this->get($vars['parent_service_id']);

                    if ($parent_service) {
                        $parent_package = $this->Packages->getByPricingId($parent_service->pricing_id);
                    }
                }

                // Set the module row to use if not given
                if (!isset($vars['module_row_id'])) {
                    // Set module row to that defined for the package if available and the client can't set a module group
                    if ($package->module_row && $package->module_group_client !== '1') {
                        $vars['module_row_id'] = $package->module_row;
                    } else if ($package->module_group_client == '1' && isset($vars['module_group_id'])) {
                        // Set the module row from the module group set by the client
                        $vars['module_row_id'] = $module->selectModuleRow($vars['module_group_id']);
                    } else {
                        // If no module row defined for the package, let the module decide which row to use
                        $vars['module_row_id'] = $module->selectModuleRow($package->module_group);
                    }
                }
                $module->setModuleRow($module->getModuleRow($vars['module_row_id']));

                // Reformat $vars[configoptions] to support name/value fields defined by the package options
                if (isset($vars['configoptions']) && is_array($vars['configoptions'])) {
                    $vars['configoptions'] = $this->PackageOptions->formatOptions($vars['configoptions']);
                }

                // Add through the module
                $service_info = $module->addService($package, $vars, $parent_package, $parent_service, $vars['status']);

                // Set any errors encountered attempting to add the service
                if (($errors = $module->errors())) {
                    $this->Input->setErrors($errors);
                    return;
                }

                // Fetch company settings on services
                Loader::loadComponents($this, ['SettingsCollection']);
                $company_settings = $this->SettingsCollection->fetchSettings(null, Configure::get('Blesta.company_id'));

                // Creates subquery to calculate the next service ID value on the fly
                /*
                $values = array($company_settings['services_start'], $company_settings['services_increment'],
                    $company_settings['services_start'], $company_settings['services_increment'],
                    $company_settings['services_start'], $company_settings['services_pad_size'],
                    $company_settings['services_pad_str']);
                */
                $values = [$company_settings['services_start'], $company_settings['services_increment'],
                    $company_settings['services_start']];

                $sub_query = new Record();
                /*
                $sub_query->select(array("LPAD(IFNULL(GREATEST(MAX(t1.id_value),?)+?,?), " .
                    "GREATEST(CHAR_LENGTH(IFNULL(MAX(t1.id_value)+?,?)),?),?)"), false)->
                */
                $sub_query->select(['IFNULL(GREATEST(MAX(t1.id_value),?)+?,?)'], false)->
                    appendValues($values)->
                    from(['services' => 't1'])->
                    innerJoin('clients', 'clients.id', '=', 't1.client_id', false)->
                    innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
                    where('client_groups.company_id', '=', Configure::get('Blesta.company_id'))->
                    where('t1.id_format', '=', $company_settings['services_format']);
                // run get on the query so $sub_query->values are built
                $sub_query->get();

                // Copy record so that it is not overwritten during validation
                $record = clone $this->Record;
                $this->Record->reset();

                $vars['id_format'] = $company_settings['services_format'];
                // id_value will be calculated on the fly using a subquery
                $vars['id_value'] = $sub_query;

                // Attempt to set cancellation date if package is single term
                if ($vars['status'] == 'active'
                    && isset($package->single_term)
                    && $package->single_term == 1
                    && !isset($vars['date_canceled'])
                ) {
                    if (isset($vars['date_renews'])) {
                        $vars['date_canceled'] = $vars['date_renews'];
                    }
                }

                // Add the service
                $fields = [
                    'id_format', 'id_value', 'parent_service_id', 'package_group_id', 'pricing_id', 'client_id',
                    'staff_id', 'module_row_id', 'coupon_id', 'qty', 'override_price', 'override_currency', 'status',
                    'suspension_reason', 'date_added', 'date_renews', 'date_last_renewed', 'date_suspended',
                    'date_canceled'
                ];

                // Assign subquery values to this record component
                $this->Record->appendValues($sub_query->values);

                // Ensure the subquery value is set first because its the first value
                $vars = array_merge(['id_value' => null], $vars);

                $this->Record->insert('services', $vars, $fields);

                $service_id = $this->Record->lastInsertId();

                // Log that the service was created
                $log_vars = array_intersect_key($vars, array_flip($fields));
                unset($log_vars['id_value']);
                $this->logger->info('Created Service', array_merge($log_vars, ['id' => $service_id]));

                // Add all service fields
                if (is_array($service_info)) {
                    $this->setFields($service_id, $service_info);
                }

                // Add all service options
                if (isset($service_options) && isset($config_options)) {
                    $this->setServiceOptions($service_id, $service_options, $config_options);
                }

                // Decrement usage of quantity
                $this->decrementQuantity($vars['qty'] ?? 1, $vars['pricing_id'], false);

                // Get service pricing
                $pricing = $this->getPackagePricing($vars['pricing_id']);

                // Set the renewal price as the override price
                if ($package->override_price ?? false) {
                    $override = [
                        'override_price' => $vars['override_price'] ?? $pricing->price_renews ?? $pricing->price ?? null,
                        'override_currency' => $vars['override_currency'] ?? $pricing->currency ?? null
                    ];
                    $fields = ['override_price', 'override_currency'];
                    $this->Record->where('services.id', '=', $service_id)->update('services', $override, $fields);
                }

                // Send an email regarding this service creation, only when active
                if ($notify && $vars['status'] == 'active') {
                    $this->sendNotificationEmail($this->get($service_id), $package, $vars['client_id']);
                }

                // Trigger the Services.addAfter event
                $this->executeAndParseEvent('Services.addAfter', [
                    'service_id' => $service_id,
                    'service_activated' => ($vars['status'] ?? null) == 'active',
                    'vars' => $vars
                ]);

                return $service_id;
            }
        }
    }

    /**
     * Returns an array containing the module row and module group from the configurable options
     *
     * @param array $config_options An array containing the configurable options
     * @param stdClass|null $service An object representing the service for which to get the module fields
     * @return array An array containing the module row id and/or module group id, if available
     */
    private function getConfigurableModuleFields(array $config_options, $service = null)
    {
        if (!isset($this->PackageOptions)) {
            Loader::loadModels($this, ['PackageOptions']);
        }
        if (!isset($this->Form)) {
            Loader::loadHelpers($this, ['Form']);
        }

        // Reformat $config_options to support name/value fields defined by the package options
        $formatted_options = $this->PackageOptions->formatOptions($config_options);

        // Get a list of currently assigned options
        $service_options = [];
        if ($service) {
            $service_options = $this->Form->collapseObjectArray($service->options, 'option_value', 'option_name');
        }

        // Override module row id or module group if provided on a configurable option. On edit only set the field if
        // the config option has changed
        $module_fields = [];
        if (!empty($formatted_options['module_row_id'])
            && is_numeric($formatted_options['module_row_id'])
            && (!isset($service_options['module_row_id'])
                || $service_options['module_row_id'] != $formatted_options['module_row_id']
            )
        ) {
            $module_fields['module_row'] = $formatted_options['module_row_id'];
        }

        if (!empty($formatted_options['module_row_group'])
            && is_numeric($formatted_options['module_row_group'])
            && (!isset($service_options['module_row_group'])
                || $service_options['module_row_group'] != $formatted_options['module_row_group']
            )
        ) {
            $module_fields['module_group'] = $formatted_options['module_row_group'];
        }

        return $module_fields;
    }

    /**
     * Edits a service. Only one module action may be performend at a time. For
     * example, you can't change the pricing_id and edit the module service
     * fields in a single request.
     *
     * @param int $service_id The ID of the service to edit
     * @param array $vars An array of service info:
     *
     *  - parent_service_id The ID of the service this service is a child of
     *  - package_group_id The ID of the package group this service was added from
     *  - pricing_id The package pricing schedule ID for this service
     *  - client_id The ID of the client this service belongs to
     *  - staff_id The ID of the staff member that added the service
     *  - module_row_id The module row to add the service under
     *  - coupon_id The ID of the coupon used for this service
     *  - qty The quanity consumed by this service
     *  - override_price The price to set for this service, overriding the
     *      package pricing value for the selected term (optional, default null)
     *  - override_currency The currency to set for this service, overriding
     *      the package pricing value for the selected term (optional, default null)
     *  - status The status of this service:
     *      - active
     *      - canceled
     *      - pending
     *      - suspended
     *      - in_review
     *  - suspension_reason The reason a service is being suspended
     *  - date_added The date this service is added
     *  - date_renews The date the service renews
     *  - date_last_renewed The date the service last renewed
     *  - date_advance_renewal The date the service will be renewed in advance
     *  - date_suspended The date the service was last suspended
     *  - date_canceled The date the service was last canceled
     *  - use_module Whether or not to use the module for this request ('true','false', default 'true')
     *  - configoptions An array of key/value pairs of package options where
     *      the key is the package option ID and the value is the option value.
     *      Defining the 'configoptions' key will update all config options.
     *      Always include all config options if setting any, or changing the pricing_id. (optional)
     *  - * Any other service field data to pass to the module
     * @param bool $bypass_module $vars['use_module'] notifies the module of whether
     *     or not it should internally use its module connection to process the request, however
     *     in some instances it may be necessary to prevent the module from being notified of
     *     the request altogether. If true, this will prevent the module from being notified of the request.
     * @param bool $notify If true and the service is set to active will send the service activation notification
     * @return int The ID of this service, void if error
     */
    public function edit($service_id, array $vars, $bypass_module = false, $notify = false)
    {
        // Trigger the Services.editBefore event
        extract($this->executeAndParseEvent(
            'Services.editBefore',
            compact('service_id', 'vars', 'bypass_module', 'notify')
        ));

        // Validate whether the service can be updated
        $vars = $this->validateEdit($service_id, $vars, $bypass_module);

        if (($errors = $this->Input->errors())) {
            return;
        }

        if (!isset($this->PackageOptions)) {
            Loader::loadModels($this, ['PackageOptions']);
        }

        extract($this->getRelations($service_id));

        $package_from = clone $package;
        $pricing_id = $service->pricing_id;

        // If changing pricing ID, load up module with the new pricing ID
        if (isset($vars['pricing_id']) && $vars['pricing_id'] != $pricing_id) {
            $pricing_id = $vars['pricing_id'];

            $package = $this->Packages->getByPricingId($pricing_id);

            // If the term is being changed from a 'onetime' term to a reccuring term update
            // the renew date accordingly
            foreach ($package->pricing as $pricing) {
                if ($pricing->id == $pricing_id) {
                    if ($service->date_renews != null && $pricing->period == 'onetime') {
                        $vars['date_renews'] = null;
                    } elseif ($service->date_renews == null && $pricing->period != 'onetime') {
                        $vars['date_renews'] = $this->getNextRenewDate(
                            date('c'),
                            $pricing->term,
                            $pricing->period,
                            'Y-m-d H:i:s',
                            $package->prorata_day
                        );
                    }
                }
            }
        }

        $module = null;
        if (($module_data = $this->getModuleClassByPricingId($pricing_id))) {
            $module = $this->ModuleManager->initModule($module_data->id, Configure::get('Blesta.company_id'));
        }

        // Filter config options that may be set
        if (isset($vars['configoptions'])) {
            // Fetch the addable, updatable, and deletable service options for the updated service
            $service_options = $this->getServiceOptions($pricing_id, (array)$vars['configoptions'], $service_id);
            $temp_config_options = [];

            // Filter out config options that may be added from the list given
            foreach ($service_options['add'] as $option_id => $temp_pricing_id) {
                if (array_key_exists($option_id, (array)$vars['configoptions'])) {
                    $temp_config_options[$option_id] = $vars['configoptions'][$option_id];
                }
            }
            // Filter out config options that may be updated from the list given
            foreach ($service_options['edit'] as $option_id => $option_pricing) {
                if (array_key_exists($option_id, (array)$vars['configoptions'])) {
                    $temp_config_options[$option_id] = $vars['configoptions'][$option_id];
                }
            }
            // Filter out config options that may be deleted from the list given
            foreach ($service_options['delete'] as $option_id => $option_pricing) {
                if (array_key_exists($option_id, $vars['configoptions'])) {
                    $temp_config_options[$option_id] = $vars['configoptions'][$option_id];
                }
            }

            // Set the filtered config options
            $vars['configoptions'] = $temp_config_options;
            $config_options = $vars['configoptions'];

            // Check if module row has been provided as a configurable option
            $module_row_fields = $this->getConfigurableModuleFields($vars['configoptions'], $service);
            if ($module) {
                $service_provision = $service->status == 'pending'
                    && isset($vars['status'])
                    && $vars['status'] == 'active';
                if (!empty($module_row_fields['module_row'])
                    && $module->getModuleRow($module_row_fields['module_row'])
                ) {
                    $vars['module_row_id'] = $module_row_fields['module_row'];
                } elseif (!empty($module_row_fields['module_group'])
                    && $service_provision
                    && ($module_row_id = $module->selectModuleRow($module_row_fields['module_group']))
                ) {
                    // Only evaluate module_group option on service provision
                    $vars['module_row_id'] = $module_row_id;
                }
            }
        }

        if ($module_data && !$bypass_module && $module) {
            // Set the module row used for this service
            $module_row_id = $service->module_row_id;
            // If changing module row ID, set the correct module row for this service
            if (isset($vars['module_row_id'])) {
                $module_row_id = $vars['module_row_id'];
            }
            $module->setModuleRow($module->getModuleRow($module_row_id));

            // Reformat $vars[configoptions] to support name/value fields defined by the package options
            if (isset($vars['configoptions']) && is_array($vars['configoptions'])) {
                $vars['configoptions'] = $this->PackageOptions->formatOptions($vars['configoptions']);
            } elseif (!isset($vars['configoptions'])) {
                $vars['configoptions'] = [];
                foreach ($service->options as $option) {
                    // The option value is the selected value or the quantity for quantity types
                    $value = $option->value;
                    if ($option->option_type == 'quantity') {
                        $value = $option->qty;
                    }

                    $vars['configoptions'][$option->option_name] = $value;
                }
                unset($option);
            }

            $service_info = null;

            // Attempt to change the package via module if pricing has changed
            if (isset($vars['pricing_id'])
                && $service->pricing_id != $vars['pricing_id']
                && $vars['use_module'] == 'true'
            ) {
                $service_info = $module->changeServicePackage(
                    $package_from,
                    $package,
                    $service,
                    $parent_package,
                    $parent_service
                );

                if (($errors = $module->errors())) {
                    $this->Input->setErrors($errors);
                    return;
                } elseif ($service_info && is_array($service_info)) {
                    // Update the service fields changed from the package
                    $this->setFields($service_id, $service_info);
                    // Refetch the service (and thus the new service fields)
                    $service = $this->get($service_id);
                }
            }

            // If service is currently pending and status is now "active", call addService on the module
            if ($service->status == 'pending'
                && isset($vars['status'])
                && $vars['status'] == 'active'
            ) {
                $vars['pricing_id'] = $service->pricing_id;
                $vars['client_id'] = $service->client_id;
                $service_info = $module->addService(
                    $package,
                    $vars,
                    $parent_package,
                    $parent_service,
                    $vars['status']
                );
            } else {
                $service_info = $module->editService(
                    $package,
                    $service,
                    $vars,
                    $parent_package,
                    $parent_service
                );
            }

            if (($errors = $module->errors())) {
                $this->Input->setErrors($errors);
                return;
            }

            // Set all service fields (if any given)
            if (is_array($service_info)) {
                $this->setFields($service_id, $service_info);
            }

            // Add/update/delete service options
            if (isset($service_options) && isset($config_options)) {
                $this->setServiceOptions($service_id, $service_options, $config_options);
            }

            // Decrement usage of quantity
            $this->decrementQuantity(
                isset($vars['qty']) ? $vars['qty'] : 1,
                $vars['pricing_id'],
                false,
                $service->qty
            );

            // Send an email regarding this service creation, only when active
            if ($notify && isset($vars['status']) && $vars['status'] == 'active') {
                $this->sendNotificationEmail($this->get($service_id), $package, $service->client_id);
            }
        }

        // Attempt to set cancellation date if package is single term
        if ($service->status == 'pending' && isset($vars['status']) && $vars['status'] == 'active' &&
            isset($package->single_term) && $package->single_term == 1 && !isset($vars['date_canceled'])) {
            if (isset($vars['date_renews'])) {
                $vars['date_canceled'] = $vars['date_renews'];
            } else {
                $vars['date_canceled'] = $service->date_renews;
            }
        }

        $fields = [
            'parent_service_id', 'package_group_id', 'pricing_id', 'client_id', 'staff_id', 'module_row_id',
            'coupon_id', 'qty', 'override_price', 'override_currency', 'status', 'suspension_reason',
            'date_added', 'date_renews', 'date_last_renewed', 'date_advance_renewal', 'date_suspended', 'date_canceled'
        ];

        // Only update if $vars contains something in $fields
        $intersect = array_intersect_key($vars, array_flip($fields));
        if (!empty($intersect)) {
            $this->Record->where('services.id', '=', $service_id)->update('services', $vars, $fields);

            // Log that the service was updated
            $this->logger->info('Updated Service', array_merge($intersect, ['id' => $service_id]));
        }

        // Trigger the Services.editAfter event
        $this->executeAndParseEvent(
            'Services.editAfter',
            [
                'service_id' => $service_id,
                'service_activated' => ($vars['status'] ?? null) == 'active'
                    && in_array($service->status, ['pending', 'in_review']),
                'vars' => $vars,
                'old_service' => $service
            ]
        );

        return $service_id;
    }

    /**
     * Updates the service to set all of its service config options
     *
     * @param int $service_id The ID of the service to update
     * @param array $service_options An array of service options from Services::getServiceOptions
     * @param array $config_options A key/value array of config options selected
     *  with the key being the option ID and the value being the selected value
     */
    private function setServiceOptions($service_id, array $service_options, array $config_options)
    {
        if (!isset($this->PackageOptions)) {
            Loader::loadModels($this, ['PackageOptions']);
        }

        // Add the new config options
        foreach ($service_options['add'] as $option_id => $pricing_id) {
            if (array_key_exists($option_id, $config_options)
                && ($option_value = $this->PackageOptions->getValue($option_id, $config_options[$option_id]))
                && ($option = $this->PackageOptions->get($option_id))
            ) {
                $value = ($option_value->value === null ? null : $config_options[$option_id]);
                $encrypt = ($option->type === 'password' && $value !== null);
                $vars = [
                    'service_id' => $service_id,
                    'option_pricing_id' => $pricing_id,
                    'qty' => ($option_value->value === null ? $config_options[$option_id] : 1),
                    'value' => ($encrypt ? $this->systemEncrypt($value) : $value),
                    'encrypted' => $this->boolToInt($encrypt)
                ];

                $this->Record->insert('service_options', $vars);
            }
        }

        // Update the current config options
        foreach ($service_options['edit'] as $option_id => $temp_pricing) {
            $pricing_id = $temp_pricing['new'];
            $old_pricing_id = $temp_pricing['old'];

            if (array_key_exists($option_id, $config_options)
                && ($option_value = $this->PackageOptions->getValue($option_id, $config_options[$option_id]))
                && ($option = $this->PackageOptions->get($option_id))
            ) {
                $value = ($option_value->value === null ? null : $config_options[$option_id]);
                $encrypt = ($option->type === 'password' && $value !== null);
                $vars = [
                    'service_id' => $service_id,
                    'option_pricing_id' => $pricing_id,
                    'qty' => ($option_value->value === null ? $config_options[$option_id] : 1),
                    'value' => ($encrypt ? $this->systemEncrypt($value) : $value),
                    'encrypted' => $this->boolToInt($encrypt)
                ];

                $this->Record->where('service_id', '=', $service_id)->
                    where('option_pricing_id', '=', $old_pricing_id)->
                    update('service_options', $vars);
            }
        }

        // Remove config options that can no longer be set on the service
        foreach ($service_options['delete'] as $option_id => $pricing_id) {
            $this->Record->from('service_options')->
                where('service_id', '=', $service_id)->
                where('option_pricing_id', '=', $pricing_id)->
                delete();
        }
    }

    /**
     * Retrieves a list of package option IDs that can be added/updated to the given service
     *
     * @param int $pricing_id The ID of the new pricing ID to use for the service
     * @param array $config_options A key/value array of option IDs and their selected values
     * @param int $service_id The ID of the current service before it has been updated (optional)
     * @return array An array containing:
     *
     *  - add A key/value array of option IDs and their option pricing ID to be added
     *  - edit An array containing:
     *      - new A key/value array of option IDs and their new option pricing ID to be upgraded to
     *      - old A key/value array of option IDs and their old (current) option pricing ID to upgrade from
     *  - delete A key/value array of current option IDs and their option pricing ID to be removed
     */
    private function getServiceOptions($pricing_id, array $config_options, $service_id = null)
    {
        // Fetch the selected pricing information
        if (!isset($this->PackageOptions)) {
            Loader::loadModels($this, ['PackageOptions']);
        }

        // Fetch the current service options and key them by the option ID
        $current_options = [];
        if ($service_id) {
            $current_options = $this->getOptions($service_id);
            $current_option_ids = [];
            foreach ($current_options as $option) {
                $current_option_ids[$option->option_id] = $option;
            }
            $current_options = $current_option_ids;
            unset($current_option_ids);
        }

        // Fetch the available service options and key them by the option ID
        $pricing = null;
        $available_options = $this->getOptionsAvailable(
            $pricing_id,
            $pricing,
            $this->PackageOptions->formatServiceOptions($current_options)
        );
        $available_option_ids = [];
        foreach ($available_options as $option) {
            $available_option_ids[$option->id] = $option;
        }
        $available_options = $available_option_ids;
        unset($available_option_ids);

        // Determine what options are to be added, updated, or removed
        $options = ['add' => [], 'edit' => [], 'delete' => []];
        foreach ($config_options as $option_id => $value) {
            if (($option_value = $this->PackageOptions->getValue($option_id, $value))) {
                $price = null;
                if ($pricing) {
                    $price = $this->PackageOptions->getValuePrice(
                        $option_value->id,
                        $pricing->term,
                        $pricing->period,
                        $pricing->currency
                    );
                }

                // Skip any options that don't have pricing
                if (!$price) {
                    continue;
                }

                // Option is available to be set
                if (array_key_exists($option_id, $available_options)) {
                    // If the option is set to the quantity of 0, it should be removed, so skip it
                    if ($value == 0
                        && ($option = $this->PackageOptions->get($option_id))
                        && $option->type == 'quantity'
                    ) {
                        continue;
                    }

                    // Determine whether the given option will be added or updated
                    if (array_key_exists($option_id, $current_options)) {
                        $options['edit'][$option_id] = [
                            'new' => $price->id,
                            'old' => $current_options[$option_id]->option_pricing_id
                        ];
                    } else {
                        $options['add'][$option_id] = $price->id;
                    }

                    // Unset the option from the current options
                    unset($current_options[$option_id]);
                }
            }
        }

        // All remaining current options will be removed
        foreach ($current_options as $option_id => $option) {
            $options['delete'][$option_id] = $option->option_pricing_id;
        }

        return $options;
    }

    /**
     * Fetches all of the package options available for a given pricing
     *
     * @param int $pricing_id The package pricing ID of the package from which to fetch package options
     * @param mixed $pricing The package pricing object to update to set with the selected pricing
     * @param array $options An array of key/value pairs for filtering options, including:
     *
     *  - configoptions An array of key/value pairs currently in use where
     *      each key is the package option ID and each value is the option value
     * @return array An array of package options
     */
    private function getOptionsAvailable($pricing_id, &$pricing = null, array $options = [])
    {
        if (!isset($this->Packages)) {
            Loader::loadModels($this, ['Packages']);
        }
        if (!isset($this->PackageOptions)) {
            Loader::loadModels($this, ['PackageOptions']);
        }

        // Fetch the package pricing
        $available_options = [];
        if (($package = $this->Packages->getByPricingId($pricing_id))) {
            $pricing = null;
            foreach ($package->pricing as $package_pricing) {
                if ($package_pricing->id == $pricing_id) {
                    $pricing = $package_pricing;
                    break;
                }
            }

            // Set the available package options
            if ($pricing) {
                $available_options = $this->PackageOptions->getAllByPackageId(
                    $pricing->package_id,
                    $pricing->term,
                    $pricing->period,
                    $pricing->currency,
                    null,
                    $options
                );
            }
        }

        return $available_options;
    }

    /**
     * Permanently deletes a pending service from the system
     *
     * @param int $service_id The ID of the pending service to delete
     * @param int $validate True to validate whether the service is capable of being deleted, or false to
     *  permanently delete its record regardless (optional, default true)
     */
    public function delete($service_id, $validate = true)
    {
        // Set delete rules
        $rules = [];
        if ($validate) {
            // A service may not be deleted if it has any children unless those children are all canceled
            $rules = [
                'service_id' => [
                    'has_children' => [
                        'rule' => [[$this, 'validateHasChildren'], 'canceled'],
                        'negate' => true,
                        'message' => $this->_('Services.!error.service_id.has_children')
                    ]
                ],
                'status' => [
                    'valid' => [
                        'rule' => ['in_array', ['pending', 'in_review', 'canceled']],
                        'message' => $this->_('Services.!error.status.valid')
                    ]
                ]
            ];
        }

        // Fetch the service's status
        $status = '';
        if (($service = $this->get($service_id))) {
            $status = $service->status;
        }

        $vars = ['service_id' => $service_id, 'status' => $status];
        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            // Delete the pending service
            $this->Record->from('services')
                ->leftJoin('service_fields', 'service_fields.service_id', '=', 'services.id', false)
                ->leftJoin('service_options', 'service_options.service_id', '=', 'services.id', false)
                ->leftJoin('service_invoices', 'service_invoices.service_id', '=', 'services.id', false)
                ->where('services.id', '=', $service_id)
                ->delete(['services.*', 'service_fields.*', 'service_options.*', 'service_invoices.*']);

            // Delete all cancelled addons associated to the service
            $this->Record->from('services')
                ->leftJoin('service_fields', 'service_fields.service_id', '=', 'services.id', false)
                ->leftJoin('service_options', 'service_options.service_id', '=', 'services.id', false)
                ->leftJoin('service_invoices', 'service_invoices.service_id', '=', 'services.id', false)
                ->where('services.parent_service_id', '=', $service_id)
                ->where('services.status', '=', 'canceled')
                ->delete(['services.*', 'service_fields.*', 'service_options.*', 'service_invoices.*']);

            // Log that the service was deleted
            // Remove service fields that may contain sensitive information
            unset($service->fields);
            $this->logger->info('Deleted Service', (array)$service);
        }
    }

    /**
     * Sends the service cancellation email
     *
     * @param stdClass $service An object representing the service
     * @param stdClass $package An object representing the package associated with the service
     */
    private function sendCancellationNoticeEmail($service, $package)
    {
        Loader::loadModels($this, ['Clients', 'Emails']);

        // Fetch the client
        $client = $this->Clients->get($service->client_id);

        // Get the tags for the email
        $tags = $this->getSuspensionAndCancellationTags($service, $package, $client);

        // Send the email
        $this->Emails->send(
            'service_cancellation',
            $package->company_id,
            $client->settings['language'],
            $client->email,
            $tags,
            null,
            null,
            null,
            ['to_client_id' => $client->id]
        );
    }

    /**
     * Sends the service scheduled cancellation email
     *
     * @param stdClass $service An object representing the service
     * @param stdClass $package An object representing the package associated with the service
     */
    private function sendScheduledCancellationNoticeEmail($service, $package)
    {
        Loader::loadModels($this, ['Clients', 'Emails', 'MessengerManager']);

        // Fetch the client
        $client = $this->Clients->get($service->client_id);

        // Get the tags for the email
        $tags = $this->getSuspensionAndCancellationTags($service, $package, $client);

        // Send the email
        $this->Emails->send(
            'service_scheduled_cancellation',
            $package->company_id,
            $client->settings['language'],
            $client->email,
            $tags,
            null,
            null,
            null,
            ['to_client_id' => $client->id]
        );

        // Send message
        $this->MessengerManager->send(
            'service_scheduled_cancellation',
            $tags,
            [$client->user_id]
        );
    }

    /**
     * Sends the service (un)suspension email
     *
     * @param string $type The type of email to send (i.e. "suspend" or "unsuspend")
     * @param stdClass $service An object representing the service
     * @param stdClass $package An object representing the package associated with the service
     */
    private function sendSuspensionNoticeEmail($type, $service, $package)
    {
        Loader::loadModels($this, ['Clients', 'Emails', 'MessengerManager']);

        // Fetch the client
        $client = $this->Clients->get($service->client_id);

        // Get the tags for the email
        $tags = $this->getSuspensionAndCancellationTags($service, $package, $client);

        $action = ($type == 'suspend' ? 'service_suspension' : 'service_unsuspension');
        $this->Emails->send(
            $action,
            $package->company_id,
            $client->settings['language'],
            $client->email,
            $tags,
            null,
            null,
            null,
            ['to_client_id' => $client->id]
        );

        // Send message
        $this->MessengerManager->send(
            $action,
            $tags,
            [$client->user_id]
        );
    }

    /**
     * Gets a list of tags for the serice suspension and cancellation email
     *
     * @param stdClass $service An object representing the service
     * @param stdClass $package An object representing the package associated with the service
     * @param stdClass $client An object representing the client associated with the service
     */
    private function getSuspensionAndCancellationTags($service, $package, $client)
    {
        Loader::loadModels($this, ['Contacts']);

        $local_date = clone $this->Date;
        $local_date->setTimezone('UTC', Configure::get('Blesta.company_timezone'));

        // Format package pricing
        if (!empty($service->package_pricing)) {
            Loader::loadModels($this, ['Currencies', 'Packages']);

            // Format the currency values
            $service->package_pricing->price_formatted = $this->Currencies->toCurrency(
                $service->package_pricing->price,
                $service->package_pricing->currency,
                $package->company_id
            );
            $service->package_pricing->setup_fee_formatted = $this->Currencies->toCurrency(
                $service->package_pricing->setup_fee,
                $service->package_pricing->currency,
                $package->company_id
            );
            $service->package_pricing->cancel_fee_formatted = $this->Currencies->toCurrency(
                $service->package_pricing->cancel_fee,
                $service->package_pricing->currency,
                $package->company_id
            );

            // Set pricing period to a language value
            $package_period_lang = $this->Packages->getPricingPeriods();
            if (isset($package_period_lang[$service->package_pricing->period])) {
                $service->package_pricing->period_formatted = $package_period_lang[$service->package_pricing->period];
            }
        }

        // Add each service field as a tag
        if (!empty($service->fields)) {
            $fields = [];
            foreach ($service->fields as $field) {
                $fields[$field->key] = $field->value;
            }
            $service = (object)array_merge((array)$service, $fields);
        }

        // Format service fields
        $service->date_canceled_formatted = $local_date->cast($service->date_canceled, $client->settings['date_format']);

        // Add each package meta field as a tag
        if (!empty($package->meta)) {
            $fields = [];
            foreach ($package->meta as $key => $value) {
                $fields[$key] = $value;
            }
            $package = (object)array_merge((array)$package, $fields);
        }

        // Set package name to the translation in the recipients language
        foreach ($package->names as $name) {
            if ($name->lang == $client->settings['language']) {
                $package->name = $name->name;
                break;
            }
        }

        // Set package descriptions to their translation in the recipients language
        foreach ($package->descriptions as $description) {
            if ($description->lang == $client->settings['language']) {
                $package->description = $description->text;
                $package->description_html = $description->html;
                break;
            }
        }

        return [
            'contact' => $this->Contacts->get($client->contact_id),
            'package' => $package,
            'pricing' => $service->package_pricing,
            'service' => $service,
            'client' => $client
        ];
    }

    /**
     * Sends a service confirmation email
     *
     * @param stdClass $service An object representing the service created
     * @param stdClass $package An object representing the package associated with the service
     * @param int $client_id The ID of the client to send the notification to
     */
    private function sendNotificationEmail($service, $package, $client_id)
    {
        Loader::loadModels($this, ['Clients', 'Contacts', 'Emails', 'ModuleManager', 'MessengerManager']);

        // Fetch the client
        $client = $this->Clients->get($client_id);

        // Look for the correct language of the email template to send, or default to English
        $service_email_content = null;
        foreach ($package->email_content as $index => $email) {
            // Save English so we can use it if the default language is not available
            if ($email->lang == 'en_us') {
                $service_email_content = $email;
            }

            // Use the client's default language
            if ($client->settings['language'] == $email->lang) {
                $service_email_content = $email;
                break;
            }
        }

        // Set all tags for the email
        $language_code = ($service_email_content ? $service_email_content->lang : null);

        // Get the module and set the module host name
        $module = $this->ModuleManager->initModule($package->module_id, $package->company_id);
        $module_row = $this->ModuleManager->getRow($service->module_row_id);

        // Set all acceptable module meta fields
        $module_fields = [];
        if (!empty($module_row->meta)) {
            $tags = $module->getEmailTags();
            $tags = (isset($tags['module']) && is_array($tags['module']) ? $tags['module'] : []);

            if (!empty($tags)) {
                foreach ($module_row->meta as $key => $value) {
                    if (in_array($key, $tags)) {
                        $module_fields[$key] = $value;
                    }
                }
            }
        }
        $module = (object)$module_fields;

        // Format package pricing
        if (!empty($service->package_pricing)) {
            Loader::loadModels($this, ['Currencies', 'Packages']);

            // Set pricing period to a language value
            $package_period_lang = $this->Packages->getPricingPeriods();
            if (isset($package_period_lang[$service->package_pricing->period])) {
                $service->package_pricing->period = $package_period_lang[$service->package_pricing->period];
            }
        }

        // Add each service field as a tag
        if (!empty($service->fields)) {
            $fields = [];
            foreach ($service->fields as $field) {
                $fields[$field->key] = $field->value;
            }
            $service = (object)array_merge((array)$service, $fields);
        }

        // Add each package meta field as a tag
        if (!empty($package->meta)) {
            $fields = [];
            foreach ($package->meta as $key => $value) {
                $fields[$key] = $value;
            }
            $package = (object)array_merge((array)$package, $fields);
        }

        // Set package name to the translation in the recipients language
        foreach ($package->names as $name) {
            if ($name->lang == $client->settings['language']) {
                $package->name = $name->name;
                break;
            }
        }

        // Set package descriptions to their translation in the recipients language
        foreach ($package->descriptions as $description) {
            if ($description->lang == $client->settings['language']) {
                $package->description = $description->text;
                $package->description_html = $description->html;
                break;
            }
        }

        $tags = [
            'contact' => $this->Contacts->get($client->contact_id),
            'package' => $package,
            'pricing' => $service->package_pricing,
            'module' => $module,
            'service' => $service,
            'client' => $client,
            'package.email_html' => (isset($service_email_content->html) ? $service_email_content->html : ''),
            'package.email_text' => (isset($service_email_content->text) ? $service_email_content->text : '')
        ];

        $this->Emails->send(
            'service_creation',
            $package->company_id,
            $language_code,
            $client->email,
            $tags,
            null,
            null,
            null,
            ['to_client_id' => $client->id]
        );

        // Send message
        $this->MessengerManager->send(
            'service_creation',
            $tags,
            [$client->user_id]
        );
    }

    /**
     * Fetches all relations (e.g. packages and services) for the given service ID
     *
     * @param int $service_id The ID of the service to fetch relations for
     * @return array A array consisting of:
     *
     *  - service The given service
     *  - package The service's package
     *  - parent_service The parent service
     *  - parent_package The parent service's package
     */
    private function getRelations($service_id)
    {
        // Fetch the service
        $service = $this->get($service_id);

        // Fetch the parent's service
        if ($service && $service->parent_service_id) {
            $parent_service = $this->get($service->parent_service_id);
        }

        return [
            'service' => ($service ? $service : null),
            'package' => (isset($service->package) ? $service->package : null),
            'parent_service' => (isset($parent_service) ? $parent_service : null),
            'parent_package' => (isset($parent_service->package) ? $parent_service->package : null)
        ];
    }

    /**
     * Schedule a service for cancellation. All cancellations requests are processed
     * by the cron.
     *
     * @param int $service_id The ID of the service to schedule cancellation
     * @param array $vars An array of service info including:
     *
     *  - date_canceled The date the service is to be canceled. Possible values:
     *      - 'end_of_term' Will schedule the service to be canceled at the end of the current term
     *      - date greater than now will schedule the service to be canceled on that date
     *      - date less than now will immediately cancel the service
     *  - use_module Whether or not to use the module when canceling the
     *      service, if canceling now ('true','false', default 'true')
     *  - reapply_payments True or false, default true. When the service is canceled, line items may be
     *      removed from related invoice(s). Any payments already applied to these invoices will be
     *      unapplied and then reapplied to cover the cost of the remaining line items.
     *  - notify_cancel 'true' to notify the client by email regarding this service cancellation, 'false'
     *      to not send any notification
     *  - cancellation_reason The reason for the service cancellation
     */
    public function cancel($service_id, array $vars)
    {
        // Trigger the Services.cancelBefore event
        extract($this->executeAndParseEvent('Services.cancelBefore', compact('service_id', 'vars')));

        Loader::loadComponents($this, ['SettingsCollection']);

        // Cancel all children services as well
        $addon_services = $this->getAllChildren($service_id);
        foreach ($addon_services as $addon_service) {
            // Only cancel services not already canceled
            if ($addon_service->status !== 'canceled') {
                // Add-ons should not reapply payments.
                // Not until the parent service completes
                $reapply = ['reapply_payments' => false];
                $this->cancel($addon_service->id, array_merge($vars, $reapply));
            }
        }

        $vars['service_id'] = $service_id;

        if (!isset($vars['use_module'])) {
            $vars['use_module'] = 'true';
        }
        if (isset($vars['status'])) {
            unset($vars['status']);
        }

        if (!isset($vars['date_canceled'])) {
            $vars['date_canceled'] = date('c');
        }

        $rules = [
            'service_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'services'],
                    'message' => $this->_('Services.!error.service_id.exists')
                ]
            ],
            //'date_canceled' must be either a valid date or 'end_of_term'
            'date_canceled' => [
                'valid' => [
                    'rule' => [[$this, 'validateDateCanceled']],
                    'message' => $this->_('Services.!error.date_canceled.valid')
                ]
            ]
        ];
        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            extract($this->getRelations($service_id));

            if ($vars['date_canceled'] == 'end_of_term') {
                $vars['date_canceled'] = $service->date_renews;
            } else {
                $vars['date_canceled'] = $this->dateToUtc($vars['date_canceled']);
            }

            // If date_canceled is greater than now use module must be false
            if (strtotime($vars['date_canceled']) > time()) {
                $vars['use_module'] = 'false';
            } else {
                // Set service to canceled if cancel date is <= now
                $vars['status'] = 'canceled';
            }

            if (isset($service->status) && $service->status == 'pending') {
                $vars['use_module'] = 'false';
            }

            // Cancel the service using the module
            if ($vars['use_module'] == 'true') {
                $this->cancelOnModule($service, $package, $parent_service, $parent_package);

                // The service could not be canceled on the module,
                // so don't bother continuing on to cancel it
                if ($this->Input->errors()) {
                    return;
                }
            }

            // Update the service
            $fields = ['date_canceled', 'status', 'cancellation_reason'];
            $this->Record->where('services.id', '=', $service_id)->
                update('services', $vars, $fields);

            // Log that the service was canceled
            $log_vars = array_intersect_key($vars, array_flip($fields));
            $this->logger->info('Canceled Service', array_merge($log_vars, ['id' => $service_id]));

            // Trigger the Services.cancelAfter event
            $this->executeAndParseEvent(
                'Services.cancelAfter',
                ['service_id' => $service_id, 'vars' => $vars, 'old_service' => $service]
            );

            // Remove this service from all open invoices
            // iff the service is now canceled
            // and the setting is enabled to do so
            if (isset($vars['status']) && $vars['status'] == 'canceled') {
                $void_invoice_setting = $this->SettingsCollection->fetchClientSetting(
                    $service->client_id,
                    (isset($this->Clients) ? $this->Clients : null),
                    'void_invoice_canceled_service'
                );

                if ((isset($void_invoice_setting['value']) ? $void_invoice_setting['value'] : null) == 'true') {
                    $void_inv_canceled_service_days = $this->SettingsCollection->fetchClientSetting(
                        $service->client_id,
                        (isset($this->Clients) ? $this->Clients : null),
                        'void_inv_canceled_service_days'
                    );

                    $options = array_intersect_key($vars, array_flip(['reapply_payments']));
                    $options['void_inv_canceled_service_days'] = (
                        isset($void_inv_canceled_service_days['value'])
                            ? $void_inv_canceled_service_days['value']
                            : '0'
                    );
                    $this->voidInvoices($service_id, $options);
                }
            }

            // Create an invoice for this service cancellation
            $this->addCancelInvoice($service, $vars['date_canceled']);

            $send_cancellation_notice = $this->SettingsCollection->fetchClientSetting(
                $service->client_id,
                (isset($this->Clients) ? $this->Clients : null),
                'send_cancellation_notice'
            );

            if ((isset($vars['notify_cancel']) && $vars['notify_cancel'] == 'true')
                || (!isset($vars['notify_cancel']) && $send_cancellation_notice['value'] == 'true')
            ) {
                if (strtotime($vars['date_canceled']) > time()) {
                    // Send the scheduled cancellation email
                    $this->sendScheduledCancellationNoticeEmail($this->get($service_id), $package);
                } else {
                    // Send the cancellation email
                    $this->sendCancellationNoticeEmail($this->get($service_id), $package);
                }
            }
        }
    }

    /**
     * Cancels the given service using its module. Sets Input errors on failure
     *
     * @see Services::cancel
     * @param stdClass $service The service to cancel
     * @param stdClass $package The package associated with the service
     * @param mixed $parent_service An stdClass object representing the parent_service, if any
     * @param mixed $parent_package An stdClass object representing the parent package, if any
     */
    private function cancelOnModule($service, $package, $parent_service = null, $parent_package = null)
    {
        if (!isset($this->ModuleManager)) {
            Loader::loadModels($this, ['ModuleManager']);
        }

        $module_data = $this->getModuleClassByPricingId($service->pricing_id);

        if ($module_data) {
            $module = $this->ModuleManager->initModule($module_data->id, Configure::get('Blesta.company_id'));

            if ($module) {
                // Set the module row used for this service
                $module->setModuleRow($module->getModuleRow($service->module_row_id));

                $service_info = $module->cancelService($package, $service, $parent_package, $parent_service);

                if (($errors = $module->errors())) {
                    $this->Input->setErrors($errors);
                    return;
                }

                // Set all service fields (if any given)
                if (is_array($service_info)) {
                    $this->setFields($service->id, $service_info);
                }
            }
        }
    }

    /**
     * Creates an invoice for a client based on the given service being canceled
     * i.e. service cancellation fee
     *
     * @see Services::cancel
     * @param stdClass $service The service in process of being canceled
     * @param string $date_canceled The service cancellation date
     */
    private function addCancelInvoice($service, $date_canceled)
    {
        // Create an invoice regarding this service's cancelation
        if ($service->package_pricing->period != 'onetime'
            && $service->package_pricing->cancel_fee > 0
            && $service->date_renews != $date_canceled
        ) {
            Loader::loadModels($this, ['Clients', 'Invoices']);
            Loader::loadComponents($this, ['SettingsCollection']);

            // Get the client settings
            $client_settings = $this->SettingsCollection->fetchClientSettings($service->client_id, $this->Clients);

            // Get the pricing info
            if ($client_settings['default_currency'] != $service->package_pricing->currency) {
                $pricing_info = $this->getPricingInfo($service->id, $client_settings['default_currency']);
            } else {
                $pricing_info = $this->getPricingInfo($service->id);
            }

            // Create the invoice
            if ($pricing_info) {
                $invoice_vars = [
                    'client_id' => $service->client_id,
                    'date_billed' => date('c'),
                    'date_due' => date('c'),
                    'status' => 'active',
                    'currency' => $pricing_info->currency,
                    'delivery' => [$client_settings['inv_method']],
                    'lines' => [
                        [
                            'service_id' => $service->id,
                            'description' => Language::_(
                                'Invoices.!line_item.service_cancel_fee_description',
                                true,
                                $pricing_info->package_name,
                                $pricing_info->name
                            ),
                            'qty' => 1,
                            'amount' => $pricing_info->cancel_fee,
                            'tax' => !isset($client_settings['cancelation_fee_tax'])
                                || $client_settings['cancelation_fee_tax'] == 'true'
                                    ? $service->package->taxable
                                    : 0
                        ]
                    ]
                ];

                $this->Invoices->add($invoice_vars);
            }
        }
    }

    /**
     * Any open invoices that contain line items related to the given service
     * will be removed from the invoice, and all payments to it unapplied.
     *
     * If the result is that all line items would be removed from the invoice, then the
     * invoice will be voided instead.
     *
     * @see Services::cancel
     * @param int $service_id The ID of the service whose open invoices to void
     * @param array $options An array of options:
     *
     *  - reapply_payments True or false, whether to reapply transaction
     *      payments that are unapplied. Default true
     *  - void_inv_canceled_service_days The number of days the invoice may be past due and still be voided
     *      - any to disregard the past due date
     *      - 0 to be the date the invoice is due
     *      - 1 to be one day after the invoice is due, 2, ..., n to be n days after the invoice is due
     */
    private function voidInvoices($service_id, array $options = [])
    {
        // Fetch the service
        if (!($service = $this->get($service_id))) {
            return;
        }

        $local_date = clone $this->Date;
        $local_date->setTimezone('UTC', Configure::get('Blesta.company_timezone'));

        // Fetch all open invoices for this service
        Loader::loadModels($this, ['Companies', 'Invoices', 'Transactions']);
        $invoices = $this->Invoices->getAllWithService($service->id, $service->client_id, 'open');
        $void_inv_canceled_service_days = (isset($options['void_inv_canceled_service_days']) ? $options['void_inv_canceled_service_days'] : '0');

        foreach ($invoices as $invoice) {
            // Skip invoices that are past due a configured number of days. They are not to be voided
            if ($void_inv_canceled_service_days != 'any') {
                $invoice_due_date = $this->dateToUtc(
                    $local_date->cast(
                        $local_date->modify(
                            $invoice->date_due . 'Z',
                            '+' . abs((int)$void_inv_canceled_service_days) . ' days',
                            'c'
                        ),
                        'Y-m-d 23:59:59'
                    )
                );

                if (strtotime($invoice_due_date) <= strtotime($this->dateToUtc(date('c')))) {
                    continue;
                }
            }

            // Unapply any transactions from the invoice
            $transactions = $this->Transactions->getApplied(null, $invoice->id);
            $total_applied = [];
            foreach ($transactions as $transaction) {
                $total_applied[$transaction->id] = $transaction->applied_amount;
                $this->Transactions->unapply($transaction->id, [$invoice->id]);
            }

            // Fetch the invoice line items
            $can_void = true;
            $line_items = $this->Invoices->getLineItems($invoice->id);
            foreach ($line_items as $line_item) {
                if ($line_item->service_id != $service->id) {
                    $can_void = false;
                    break;
                }
            }

            // Fetch the date time
            $date = $local_date->cast(
                date('c'),
                $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'datetime_format')->value
            );

            // Void the invoice or remove all of the service's line items from it
            $notes = (isset($invoice->note_private) ? $invoice->note_private : null);
            $vars = [
                'note_private' => (empty($notes)
                    ? ''
                    : $notes . "\n\n"
                ) . $this->_('Invoices.!note_private.service_cancel_date', $service->id, $date),
                'status' => $invoice->status
            ];

            if ($can_void) {
                $this->Invoices->edit($invoice->id, array_merge($vars, ['status' => 'void']));
                continue;
            }

            // Set default vars for editing the invoice
            $vars['lines'] = [];
            $vars['note_private'] .= "\n" . $this->_('Invoices.!note_private.removed_lines');

            // Update all line items to delete this service's line items
            // and to re-save the existing ones
            foreach ($line_items as $line_item) {
                if ($line_item->service_id !== $service->id) {
                    // Re-add other existing line items
                    $vars['lines'][] = array_merge(
                        (array)$line_item,
                        ['tax' => !empty($line_item->taxes)]
                    );
                    continue;
                }

                // Mark the service line item for removal, and update
                // the private note to include what was removed
                $vars['lines'][] = ['id' => $line_item->id];
                $vars['note_private'] .= "\n"
                    . $this->_(
                        'Invoices.!note_private.line_item',
                        $this->truncateDecimal($line_item->qty, 0),
                        $this->currencyToDecimal($line_item->amount, $invoice->currency),
                        $line_item->description
                    );
            }

            // Update the invoice to remove the line items
            $this->Invoices->edit($invoice->id, $vars);

            // Reapply unapplied transaction payments
            if (!array_key_exists('reapply_payments', $options) || $options['reapply_payments']) {
                // Re-fetch the invoice and its new totals
                $invoice = $this->Invoices->get($invoice->id);

                // Reapply the same transactions to the remaining line items on the invoice
                // that were previously removed
                $total_due = $invoice->due;
                foreach ($total_applied as $transaction_id => $amount) {
                    if ($total_due <= 0) {
                        break;
                    }

                    // Determine the amount to apply to the invoice
                    $apply_amount = min($total_due, $amount);
                    $total_due -= $apply_amount;

                    $this->Transactions->apply(
                        $transaction_id,
                        [
                            'date' => date('c'),
                            'amounts' => [
                                ['invoice_id' => $invoice->id, 'amount' => $apply_amount]
                            ]
                        ]
                    );
                }
            }
        }
    }

    /**
     * Removes the scheduled cancellation for the given service
     *
     * @param int $service_id The ID of the service to remove scheduled cancellation from
     */
    public function unCancel($service_id)
    {
        // Unancel all children services as well
        $addon_services = $this->getAllChildren($service_id);
        foreach ($addon_services as $addon_service) {
            $this->unCancel($addon_service->id);
        }

        // Update the service
        $this->Record->where('services.id', '=', $service_id)->
            where('services.status', '!=', 'canceled')->
            update('services', ['date_canceled' => null, 'cancellation_reason' => null]);

        // Log that the service was uncanceled
        $this->logger->info('Uncanceled Service', ['id' => $service_id]);
    }

    /**
     * Suspends a service
     *
     * @param int $service_id The ID of the service to suspend
     * @param array $vars An array of info including:
     *
     *  - use_module Whether or not to use the module when suspending the service ('true','false', default 'true')
     *  - staff_id The ID of the staff member that issued the service suspension
     *  - suspension_reason The reason for the service suspension
     */
    public function suspend($service_id, array $vars = [])
    {
        // Trigger the Services.suspendBefore event
        extract($this->executeAndParseEvent('Services.suspendBefore', compact('service_id', 'vars')));

        if (!isset($vars['use_module'])) {
            $vars['use_module'] = 'true';
        }
        $vars['date_suspended'] = $this->dateToUtc(date('c'));
        $vars['status'] = 'suspended';

        extract($this->getRelations($service_id));

        // Cancel the service using the module
        if ($vars['use_module'] == 'true') {
            if (!isset($this->ModuleManager)) {
                Loader::loadModels($this, ['ModuleManager']);
            }

            $module_data = $this->getModuleClassByPricingId($service->pricing_id);

            if ($module_data) {
                $module = $this->ModuleManager->initModule($module_data->id, Configure::get('Blesta.company_id'));

                if ($module) {
                    // Set the module row used for this service
                    $module->setModuleRow($module->getModuleRow($service->module_row_id));

                    $service_info = $module->suspendService($package, $service, $parent_package, $parent_service);

                    if (($errors = $module->errors())) {
                        $this->Input->setErrors($errors);
                        return;
                    }

                    // Set all service fields (if any given)
                    if (is_array($service_info)) {
                        $this->setFields($service_id, $service_info);
                    }
                }
            }
        }

        // Update the service
        $fields = ['date_suspended', 'status', 'suspension_reason'];
        $this->Record->where('services.id', '=', $service_id)->
            update('services', $vars, $fields);

        // Log that the service was suspended
        $log_vars = array_intersect_key($vars, array_flip($fields));
        $this->logger->info('Suspended Service', array_merge($log_vars, ['id' => $service_id]));

        // Trigger the Services.suspendAfter event
        $this->executeAndParseEvent(
            'Services.suspendAfter',
            ['service_id' => $service_id, 'vars' => $vars, 'old_service' => $service]
        );

        // Log the service suspension
        $log_service = [
            'service_id' => $service_id,
            'staff_id' => (array_key_exists('staff_id', $vars) ? $vars['staff_id'] : null),
            'status' => 'suspended',
            'date_added' => $this->dateToUtc(date('c'))
        ];
        $this->Record->insert('log_services', $log_service);

        // Send the suspension email
        $this->sendSuspensionNoticeEmail(
            'suspend',
            $this->get($service_id),
            $package
        );
    }

    /**
     * Unsuspends a service
     *
     * @param int $service_id The ID of the service to unsuspend
     * @param array $vars An array of info including:
     *
     *  - use_module Whether or not to use the module when unsuspending the service ('true','false', default 'true')
     *  - staff_id The ID of the staff member that issued the service unsuspension
     */
    public function unsuspend($service_id, array $vars = [])
    {
        // Trigger the Services.unsuspendBefore event
        extract($this->executeAndParseEvent('Services.unsuspendBefore', compact('service_id', 'vars')));

        if (!isset($vars['use_module'])) {
            $vars['use_module'] = 'true';
        }
        $vars['date_suspended'] = null;
        $vars['date_canceled'] = null;
        $vars['status'] = 'active';

        extract($this->getRelations($service_id));

        // Cancel the service using the module
        if ($vars['use_module'] == 'true') {
            if (!isset($this->ModuleManager)) {
                Loader::loadModels($this, ['ModuleManager']);
            }

            $module_data = $this->getModuleClassByPricingId($service->pricing_id);

            if ($module_data) {
                $module = $this->ModuleManager->initModule($module_data->id, Configure::get('Blesta.company_id'));

                if ($module) {
                    // Set the module row used for this service
                    $module->setModuleRow($module->getModuleRow($service->module_row_id));

                    $service_info = $module->unsuspendService($package, $service, $parent_package, $parent_service);

                    if (($errors = $module->errors())) {
                        $this->Input->setErrors($errors);
                        return;
                    }

                    // Set all service fields (if any given)
                    if (is_array($service_info)) {
                        $this->setFields($service_id, $service_info);
                    }
                }
            }
        }

        // Update the service
        $fields = ['date_suspended', 'date_canceled', 'status'];
        $this->Record->where('services.id', '=', $service_id)->
            update('services', $vars, $fields);

        // Log that the service was unsuspended
        $log_vars = array_intersect_key($vars, array_flip($fields));
        $this->logger->info('Unsuspended Service', array_merge($log_vars, ['id' => $service_id]));

        // Trigger the Services.unsuspendAfter event
        $this->executeAndParseEvent(
            'Services.unsuspendAfter',
            ['service_id' => $service_id, 'vars' => $vars, 'old_service' => $service]
        );

        // Log the service unsuspension
        $log_service = [
            'service_id' => $service_id,
            'staff_id' => (array_key_exists('staff_id', $vars) ? $vars['staff_id'] : null),
            'status' => 'unsuspended',
            'date_added' => $this->dateToUtc(date('c'))
        ];
        $this->Record->insert('log_services', $log_service);

        // Send the unsuspension email
        $this->sendSuspensionNoticeEmail('unsuspend', $this->get($service_id), $package);
    }

    /**
     * Processes the renewal for the given service by contacting the module
     * (if supported by the module), to let it know that the service should be
     * renewed. Note: This method does not affect the renew date of the service
     * in Blesta, it merely notifies the module; this action takes place after
     * a service has been paid not when its renew date is bumped.
     *
     * @param int $service_id The ID of the service to process the renewal for
     * @param int|null $invoice_id The ID of the paid invoice that triggered this renewal (optional)
     */
    public function renew($service_id, $invoice_id = null)
    {
        if (!isset($this->ServiceInvoices)) {
            Loader::loadModels($this, ['ServiceInvoices']);
        }

        extract($this->getRelations($service_id));

        if (!$service) {
            return;
        }

        if (!isset($this->ModuleManager)) {
            Loader::loadModels($this, ['ModuleManager']);
        }

        $module_data = $this->getModuleClassByPricingId($service->pricing_id);

        if ($module_data) {
            $module = $this->ModuleManager->initModule($module_data->id, Configure::get('Blesta.company_id'));

            if ($module) {
                $service_info = $module->renewService($package, $service, $parent_package, $parent_service);

                if (($errors = $module->errors())) {
                    // Get the current failed_attempts counter
                    $service_invoice_query = $this->Record->select()
                        ->from('service_invoices')
                        ->where('service_id', '=', $service_id);
                    if ($invoice_id) {
                        $service_invoice_query->where('invoice_id', '=', $invoice_id);
                    }
                    $service_invoice = $service_invoice_query->fetch();

                    // Increment the failed_attempts counter
                    $service_invoice_update_query = $this->Record->where('service_id', '=', $service_id);
                    if ($invoice_id) {
                        $service_invoice_update_query->where('invoice_id', '=', $invoice_id);
                    }
                    $service_invoice_update_query->update(
                        'service_invoices',
                        ['failed_attempts' => ($service_invoice->failed_attempts ?? 0) + 1]
                    );

                    $this->Input->setErrors($errors);
                    return;
                }

                // Delete the association used for renewal between the service and the renewal invoice
                $this->ServiceInvoices->delete($service_id, $invoice_id);

                // Set all service fields (if any given)
                if (is_array($service_info)) {
                    $this->setFields($service_id, $service_info);
                }

                // Log that the service was renewed on the module
                // Remove service fields and package meta data that may contain sensitive information
                unset($service->fields, $parent_service->fields, $package->meta, $parent_package->meta);
                $this->logger->info(
                    'Renewed Service via module',
                    [
                        'id' => $service_id,
                        'module' => ['id' => $module_data->id],
                        'package' => (array)$package,
                        'service' => (array)$service,
                        'parent_package' => ($parent_package ? (array)$parent_package : $parent_package),
                        'parent_service' => ($parent_service ? (array)$parent_service : $parent_service)
                    ]
                );
            }
        }
    }

    /**
     * Moves a service to a different client
     *
     * @param int $service_id The ID of the service to move
     * @param int $client_id The ID of the client where the service will be moved on
     * @return int The ID of the service, void on error
     */
    public function move($service_id, $client_id)
    {
        if (!isset($this->Invoices)) {
            Loader::loadModels($this, ['Invoices']);
        }

        if (!isset($this->Clients)) {
            Loader::loadModels($this, ['Clients']);
        }

        // Check if the client is valid and is active
        $client = $this->Clients->get($client_id);
        if (!$client || $client->status !== 'active') {
            return;
        }

        // Check if the service is valid and is active
        $service = $this->get($service_id);
        if (!$service || $service->status !== 'active') {
            return;
        }

        // Determine whether invoices for this service remain unpaid
        $unpaid_invoices = $this->Invoices->getAllWithService($service->id, $service->client_id, 'open');
        if (!empty($unpaid_invoices)) {
            $this->Input->setErrors(['move' => ['unpaid_invoices' => $this->_('Services.!error.move.unpaid_invoices')]]);
        }

        // Check if the service has child services and if these are active
        $this->Record->begin();
        if ($this->hasChildren($service->id) && !$this->errors()) {
            $children = $this->getAllChildren($service->id);

            foreach ($children as $child) {
                // Determine whether invoices for this child service remain unpaid
                $unpaid_invoices = $this->Invoices->getAllWithService($child->id, $child->client_id, 'open');
                if (!empty($unpaid_invoices)) {
                    $this->Input->setErrors(['move' => ['unpaid_invoices' => $this->_('Services.!error.move.unpaid_invoices')]]);
                }

                // Move child service to the new client
                $this->Record->where('id', '=', $child->id)
                    ->update('services', ['client_id' => $client->id]);
            }
        }

        // Move service to the new client
        $this->Record->where('id', '=', $service->id)
            ->update('services', ['client_id' => $client->id]);

        if (($errors = $this->errors())) {
            $this->Record->rollBack();
            $this->Input->setErrors($errors);

            return;
        }

        $this->logger->info(
            'Moved Service #' . $service->id . ' to Client #' . $client->id_value,
            ['service_id' => $service->id, 'client_id' => $client->id]
        );

        $this->Record->commit();

        return $service_id;
    }

    /**
     * Retrieves a list of service status types
     *
     * @return array Key=>value pairs of status types
     */
    public function getStatusTypes()
    {
        return [
            'active' => $this->_('Services.getStatusTypes.active'),
            'canceled' => $this->_('Services.getStatusTypes.canceled'),
            'pending' => $this->_('Services.getStatusTypes.pending'),
            'suspended' => $this->_('Services.getStatusTypes.suspended'),
            'in_review' => $this->_('Services.getStatusTypes.in_review'),
        ];
    }

    /**
     * Returns all action options that can be performed for a service.
     *
     * @param string $current_status Set to filter actions that may be
     *  performed if the service is in the given state options include:
     *
     *  - active
     *  - suspended
     *  - canceled
     * @return array An array of key/value pairs where each key is the action
     *  that may be performed and the value is the friendly name for the action
     */
    public function getActions($current_status = null)
    {
        $actions = [
            'suspend' => $this->_('Services.getActions.suspend'),
            'unsuspend' => $this->_('Services.getActions.unsuspend'),
            'cancel' => $this->_('Services.getActions.cancel'),
            'schedule_cancel' => $this->_('Services.getActions.schedule_cancel'),
            'change_renew' => $this->_('Services.getActions.change_renew'),
            'update_coupon' => $this->_('Services.getActions.update_coupon')
        ];

        switch ($current_status) {
            case 'active':
                unset($actions['unsuspend']);
                break;
            case 'suspended':
                unset($actions['suspend']);
                break;
            case 'pending':
            case 'canceled':
                return [];
        }
        return $actions;
    }

    /**
     * Updates the field data for the given service, removing all existing data and replacing it with the given data
     *
     * @param int $service_id The ID of the service to set fields on
     * @param array $vars A numerically indexed array of field data containing:
     *
     *  - key The key for this field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted ('true', 'false', default 'false')
     */
    public function setFields($service_id, array $vars)
    {
        $do_delete = $this->Record->select()->from('service_fields')->
            where('service_fields.service_id', '=', $service_id)->numResults();

        $this->begin();

        // Avoid deadlock by not performing non-insert query within transaction unless record(s) exist
        if ($do_delete) {
            $this->Record->from('service_fields')->
                where('service_fields.service_id', '=', $service_id)->delete();
        }

        if (!empty($vars)) {
            foreach ($vars as $field) {
                $this->addField($service_id, $field);
            }
        }

        if ($this->Input->errors()) {
            $this->rollBack();
        } else {
            $this->commit();
        }
    }

    /**
     * Adds a service field for a particular service
     *
     * @param int $service_id The ID of the service to add to
     * @param array $vars An array of service field info including:
     *
     *  - key The name of the value to add
     *  - value The value to add
     *  - encrypted Whether or not to encrypt the value when storing ('true', 'false', default 'false')
     */
    public function addField($service_id, array $vars)
    {
        $vars['service_id'] = $service_id;
        $this->Input->setRules($this->getFieldRules());

        if ($this->Input->validates($vars)) {
            // qty is a special key that may not be stored as a service field
            if ($vars['key'] == 'qty') {
                return;
            }

            if (empty($vars['encrypted'])) {
                $vars['encrypted'] = '0';
            }
            $vars['encrypted'] = $this->boolToInt($vars['encrypted']);

            $fields = ['service_id', 'key', 'value', 'serialized', 'encrypted'];

            // Serialize if needed
            $serialize = !is_scalar($vars['value']);
            $vars['serialized'] = (int)$serialize;
            if ($serialize) {
                $vars['value'] = serialize($vars['value']);
            }

            // Encrypt if needed
            if ($vars['encrypted'] > 0) {
                $vars['value'] = $this->systemEncrypt($vars['value']);
            }

            $this->Record->insert('service_fields', $vars, $fields);
        }
    }

    /**
     * Edit a service field for a particular service
     *
     * @param int $service_id The ID of the service to edit
     * @param array $vars An array of service field info including:
     *
     *  - key The name of the value to edit
     *  - value The value to update with
     *  - encrypted Whether or not to encrypt the value when storing ('true', 'false', default 'false')
     */
    public function editField($service_id, array $vars)
    {
        $this->Input->setRules($this->getFieldRules());

        if ($this->Input->validates($vars)) {
            //if (empty($vars['encrypted']))
            //    $vars['encrypted'] = "0";
            if (array_key_exists('encrypted', $vars)) {
                $vars['encrypted'] = $this->boolToInt($vars['encrypted']);
            }

            $fields = ['value', 'serialized', 'encrypted'];

            // Serialize if needed
            $serialize = !is_scalar($vars['value']);
            $vars['serialized'] = (int)$serialize;
            if ($serialize) {
                $vars['value'] = serialize($vars['value']);
            }

            // Encrypt if needed
            if (array_key_exists('encrypted', $vars) && $vars['encrypted'] > 0) {
                $vars['value'] = $this->systemEncrypt($vars['value']);
            }

            $vars['service_id'] = $service_id;
            $fields[] = 'key';
            $fields[] = 'service_id';
            $this->Record->duplicate('value', '=', $vars['value'])->
                insert('service_fields', $vars, $fields);
        }
    }

    /**
     * Returns the configurable options for the service
     *
     * @param int $service_id
     * @return array An array of stdClass objects, each representing a service option
     */
    public function getOptions($service_id)
    {
        $fields = ['service_options.*', 'package_option_values.value' => 'option_value',
            'package_option_values.name' => 'option_value_name',
            'package_option_values.option_id' => 'option_id',
            'package_options.label' => 'option_label',
            'package_options.name' => 'option_name',
            'package_options.type' => 'option_type',
            'package_options.addable' => 'option_addable',
            'package_options.editable' => 'option_editable',
            'pricings.term' => 'option_pricing_term', 'pricings.period' => 'option_pricing_period',
            'pricings.price' => 'option_pricing_price', 'pricings.price_renews' => 'option_pricing_price_renews',
            'pricings.price_transfer' => 'option_pricing_price_transfer', 'pricings.setup_fee' => 'option_pricing_setup_fee',
            'pricings.currency' => 'option_pricing_currency'
        ];

        $options = $this->Record->select($fields)
            ->from('service_options')
            ->leftJoin(
                'package_option_pricing',
                'package_option_pricing.id',
                '=',
                'service_options.option_pricing_id',
                false
            )
            ->leftJoin(
                'package_option_values',
                'package_option_values.id',
                '=',
                'package_option_pricing.option_value_id',
                false
            )
            ->leftJoin('pricings', 'pricings.id', '=', 'package_option_pricing.pricing_id', false)
            ->leftJoin('package_options', 'package_options.id', '=', 'package_option_values.option_id', false)
            ->where('service_id', '=', $service_id)
            ->fetchAll();

        // Decrypt each option value if necessary
        foreach ($options as &$option) {
            if ((int)$option->encrypted === 1) {
                $option->value = $this->systemDecrypt($option->value);
            }
        }

        return $options;
    }

    /**
     * Returns all default welcome email tags, which are set into the email that is
     * delivered when a service is provisioned.
     *
     * @return array A multi-dimensional array of tags where the first
     *  dimension is the category and the second is a numeric array of tags
     */
    public function getWelcomeEmailTags()
    {
        return [
            'client' => ['id', 'id_code', 'first_name', 'last_name'],
            'pricing' => ['term', 'period', 'currency', 'price', 'setup_fee', 'cancel_fee']
        ];
    }

    /**
     * Calculates the next renew date using a given date, term, and period
     *
     * @param string $last_renew_date The date the service last renewed. If
     *  never renewed this should be the service add date
     * @param int $term The term value relating to the given period
     * @param string $period The period (day, week, month, year, onetime)
     * @param string $format The date format to return the date in (optional, default 'Y-m-d H:i:s')
     * @param int $pro_rata_day The day of the month to prorate to. Only set
     *  this value to fetch the prorated renew date. Only used for month/year
     *  periods. Ignored if the $last_renew_date is on the $pro_rata_day. (optional, default null)
     * @return string The date the service renews in UTC. In the event that
     *  the service does not renew or the renew date can not be calculated null is returned
     */
    public function getNextRenewDate($last_renew_date, $term, $period, $format = 'Y-m-d H:i:s', $pro_rata_day = null)
    {
        if ($last_renew_date == null) {
            return null;
        }

        $last_renew_date = $this->dateToUtc($last_renew_date, 'c');

        // Fetch the renew date based on the prorate day
        if ($pro_rata_day && in_array($period, ['month', 'year'])) {
            if (!isset($this->Packages)) {
                Loader::loadModels($this, ['Packages']);
            }

            // Use the prorate date if not null, otherwise default to the full
            // term below (i.e. the date is on the pro rata day, so no proration is necessary)
            $prorate_date = $this->Packages->getProrateDate($last_renew_date, $period, $pro_rata_day);
            if ($prorate_date !== null) {
                if (!$prorate_date) {
                    return null;
                }

                return $this->dateToUtc($prorate_date, $format);
            }
        }

        if (in_array($period, ['day', 'week', 'month', 'year'])) {
            return $this->dateToUtc(
                $this->Date->modify(
                    $last_renew_date,
                    '+' . abs((int)$term) . ' ' . $period . 's',
                    'c',
                    Configure::get('Blesta.company_timezone')
                ),
                $format
            );
        }

        return null;
    }

    /**
     * Retrieves a list of line item amounts from the given line items
     * @see Services::buildServiceCouponLineItems
     * @note Deprecate and remove this method along with Services::buildServiceCouponLineItems
     *
     * @param array $line_items An array of line items that the coupon will be applying toward:
     *
     *  - service_id The ID of the service, matching one of the given $services
     *  - description The line item description
     *  - qty The line item quantity
     *  - amount The line item amount
     *  - tax Whether or not the line item is taxable
     *  - service_option_id The ID of the service option the line item represents, if any
     *  - setup_fee Whether or not the line item is a setup fee
     * @return array An array containing an array of services keyed by service ID, and any service option amounts
     *     keyed by service option ID, including:
     *
     *  - before_cutoff An array of items including:
     *      - amount The service amount (null if no service amount is known)
     *      - qty The service quantity
     *      - options An array of service options, including:
     *          - amount The service option amount
     *          - qty The service option quantity
     *  - after_cutoff An array of items after the cutoff including:
     *      - amount The service amount (null if no service amount is known)
     *      - qty The service quantity
     *      - options An array of service options, including:
     *          - amount The service option amount
     *          - qty The service option quantity
     */
    private function getServiceLineItemAmounts(array $line_items)
    {
        // Build a list of line item amounts for services
        $line_item_amounts = [];

        foreach ($line_items as $line_item) {
            // Line item must belong to a service, have an amount, and not be a setup fee
            if (!is_array($line_item) || empty($line_item['service_id']) ||
                (array_key_exists('setup_fee', $line_item) && $line_item['setup_fee']) ||
                !array_key_exists('amount', $line_item) || !array_key_exists('qty', $line_item)) {
                continue;
            }

            // If a service exists multiple times, it may be after the cutoff date in which case it
            // should be added a second time
            $cutoff = isset($line_item['after_cutoff']) && $line_item['after_cutoff']
                ? 'after_cutoff'
                : 'before_cutoff';

            // Setup a line item amount for a service
            if (!array_key_exists($line_item['service_id'], $line_item_amounts)) {
                $line_item_amounts[$line_item['service_id']] = [
                    'before_cutoff' => [
                        'amount' => null,
                        'qty' => 1,
                        'options' => []
                    ],
                    'after_cutoff' => [
                        'amount' => null,
                        'qty' => 1,
                        'options' => []
                    ]
                ];
            }

            // Set any service option amounts
            if (array_key_exists('service_option_id', $line_item) && !empty($line_item['service_option_id'])) {
                $line_item_amounts[$line_item['service_id']][$cutoff]['options'][$line_item['service_option_id']] = [
                    'amount' => $line_item['amount'],
                    'qty' => $line_item['qty']
                ];
            } else {
                // Set the service amount
                $line_item_amounts[$line_item['service_id']][$cutoff]['amount'] = $line_item['amount'];
                $line_item_amounts[$line_item['service_id']][$cutoff]['qty'] = $line_item['qty'];
            }
        }

        return $line_item_amounts;
    }

    /**
     * Retrieves a list of coupons to be applied to an invoice for services,
     * assuming the services given are for a single client only
     *
     * @param array $services An array of stdClass objects, each representing a service
     * @param string $default_currency The ISO 4217 currency code for the client
     * @param array $coupons A reference to coupons that will need to be incremented
     * @param bool $services_renew True if all of the given $services are
     *  renewing services, or false if all $services are new services (optional, default false)
     * @param array $line_items An array of line items that the coupon will be
     *  applying toward (optional but highly recommended):
     *
     *  - service_id The ID of the service, matching one of the given $services
     *  - description The line item description
     *  - qty The line item quantity
     *  - amount The line item amount
     *  - tax Whether or not the line item is taxable
     *  - service_option_id The ID of the service option the line item represents, if any
     *  - setup_fee Whether or not the line item is a setup fee
     *  - after_cutoff Whether or not the line item is after the cutoff date
     * @return array An array of coupon line items to append to an invoice
     */
    public function buildServiceCouponLineItems(
        array $services,
        $default_currency,
        &$coupons,
        $services_renew = false,
        array $line_items = []
    ) {
        Loader::loadModels($this, ['Coupons', 'Currencies']);
        // Load Invoice language needed for line items
        if (!isset($this->Invoices)) {
            Language::loadLang(['invoices']);
        }

        $coupons = [];
        $coupon_service_ids = [];
        $service_list = [];
        $now_timestamp = $this->Date->toTime($this->Coupons->dateToUtc('c'));

        // Determine which coupons could be used
        foreach ($services as $service) {
            // Fetch the coupon associated with this service
            if ($service->coupon_id && !isset($coupons[$service->coupon_id])) {
                $coupons[$service->coupon_id] = $this->Coupons->get($service->coupon_id);
            }

            // Skip this service if it has no active coupon or it does not apply to renewing services
            if (!$service->coupon_id || !isset($coupons[$service->coupon_id]) ||
                $coupons[$service->coupon_id]->status != 'active' ||
                ($services_renew && $coupons[$service->coupon_id]->recurring != '1')) {
                continue;
            }

            if (!isset($service->package_pricing)) {
                $service->package_pricing = $this->getPackagePricing($service->pricing_id);
            }

            // See if this coupon has a discount available in the correct currency
            $coupon_amount = false;
            foreach ($coupons[$service->coupon_id]->amounts as $amount) {
                if ($amount->currency == $service->package_pricing->currency) {
                    $coupon_amount = $amount;
                    break;
                }
            }
            unset($amount);

            // Add the coupon if it is usable
            if ($coupon_amount) {
                // Verify coupon applies to this package
                $valid_package = false;
                $coupon_recurs = ($coupons[$service->coupon_id]->recurring == '1');
                foreach ($coupons[$service->coupon_id]->packages as $coupon_package) {
                    if ($coupon_package->package_id == $service->package_pricing->package_id) {
                        $valid_package = true;
                    }
                }

                // Verify that the coupon applies to this term
                $valid_term = empty($coupons[$service->coupon_id]->terms);
                foreach ($coupons[$service->coupon_id]->terms as $coupon_term) {
                    if ($coupon_term->term == $service->package_pricing->term
                        && $coupon_term->period == $service->package_pricing->period
                    ) {
                        $valid_term = true;
                        break;
                    }
                }

                // Determine whether the coupon applies to this service
                $coupon_applies = ($valid_package
                    && $valid_term
                    && (!$services_renew || ($services_renew && $coupon_recurs))
                );

                // Validate whether the coupon passes its limitations
                $apply_coupon = false;
                if ($coupon_applies) {
                    // Coupon applies to renewing services ignoring limits
                    if ($services_renew && $coupons[$service->coupon_id]->limit_recurring != '1') {
                        $apply_coupon = true;
                    } else {
                        // Max quantity may be 0 for unlimited uses, otherwise
                        // it must be larger than the used quantity to apply
                        $coupon_qty_reached = $coupons[$service->coupon_id]->max_qty == '0'
                            ? false
                            : $coupons[$service->coupon_id]->used_qty >= $coupons[$service->coupon_id]->max_qty;

                        // Coupon must be valid within start/end dates and must not exceed used quantity
                        if ($now_timestamp >= $this->Date->toTime($coupons[$service->coupon_id]->start_date) &&
                            $now_timestamp <= $this->Date->toTime($coupons[$service->coupon_id]->end_date) &&
                            !$coupon_qty_reached) {
                            $apply_coupon = true;
                        }
                    }
                }

                // The coupon applies to the service
                if ($apply_coupon) {
                    if (!isset($coupon_service_ids[$service->coupon_id])) {
                        $coupon_service_ids[$service->coupon_id] = [];
                    }
                    $coupon_service_ids[$service->coupon_id][] = $service->id;
                    $service_list[$service->id] = $service;
                }
            }
        }

        // Build a list of line item amounts for services
        $line_item_amounts = $this->getServiceLineItemAmounts($line_items);
        unset($line_items);

        // Create the line items for the coupons set
        $line_items = [];
        foreach ($coupon_service_ids as $coupon_id => $service_ids) {
            // Skip if coupon is not available
            if (!isset($coupons[$coupon_id]) || !$coupons[$coupon_id]) {
                continue;
            }

            $line_item_amount = null;
            $line_item_description = null;
            $line_item_quantity = 1;
            $currency = null;

            $discount_amount = null;
            $service_total = 0;

            // Set the line item amount/description
            foreach ($coupons[$coupon_id]->amounts as $amount) {
                // Calculate the total from each service related to this coupon
                foreach ($service_ids as $service_id) {
                    // Skip if service is not available or incorrect currency
                    if (!isset($service_list[$service_id])
                        || ($amount->currency != $service_list[$service_id]->package_pricing->currency)
                    ) {
                        continue;
                    }

                    $service_amount = $service_list[$service_id]->package_pricing->price;
                    $line_item_quantity = $service_list[$service_id]->qty;
                    $discount_amount = abs($amount->amount);
                    $options_total = $this->getServiceOptionsTotal($service_id);

                    // Replace the options total with the sum of each service option line item amount
                    if (isset($line_item_amounts[$service_id])) {
                        // Set the base service amount and quantity
                        if (isset($line_item_amounts[$service_id]['before_cutoff']['amount']) ||
                            isset($line_item_amounts[$service_id]['after_cutoff']['amount'])) {
                            // Sum the before/after cutoff amounts
                            $before_amount = $line_item_amounts[$service_id]['before_cutoff']['amount'];
                            $after_amount = $line_item_amounts[$service_id]['after_cutoff']['amount'];
                            $service_amount = ($before_amount === null ? 0 : $before_amount)
                                + ($after_amount === null ? 0 : $after_amount);

                            // Before/after cutoff quantity always presumed to be identical
                            $line_item_quantity = $line_item_amounts[$service_id]['before_cutoff']['qty'];
                        }

                        // Calculate the service option amount total for each amount
                        $override_option_total = false;
                        foreach (['before_cutoff', 'after_cutoff'] as $cutoff) {
                            if (!empty($line_item_amounts[$service_id][$cutoff]['options'])) {
                                // Override the option total
                                $options_total = ($override_option_total === false ? 0 : $options_total);
                                $override_option_total = true;

                                foreach ($line_item_amounts[$service_id][$cutoff]['options'] as $option_amount) {
                                    $options_total += ($option_amount['qty'] * $option_amount['amount']);
                                }
                            }
                        }
                    }

                    // Set the discount amount based on percentage
                    if ($amount->type == 'percent') {
                        $line_item_description = Language::_(
                            'Invoices.!line_item.coupon_line_item_description_percent',
                            true,
                            $coupons[$coupon_id]->code,
                            $discount_amount
                        );
                        $discount_amount /= 100;
                        $line_item_amount += -(abs($service_amount * $line_item_quantity) * $discount_amount);

                        // Include the service options amount
                        if ($coupons[$coupon_id]->apply_package_options == '1' && $options_total > 0) {
                            $line_item_amount += -(abs($options_total) * $discount_amount);
                        }
                    } else {
                        // Set the discount amount based on amount
                        // Set the coupon amount to deduct from the package
                        $package_cost = ($service_amount * $line_item_quantity);
                        $temp_discount_amount = $discount_amount >= $package_cost
                            ? $package_cost
                            : $discount_amount;

                        // Determine the coupon discount amount from the package's config options as well
                        if ($coupons[$coupon_id]->apply_package_options == '1' && $options_total > 0) {
                            // Set the coupon amount to deduct from the coupon remainder
                            if ($temp_discount_amount < $discount_amount) {
                                $temp_discount_amount += (
                                    ($discount_amount - $temp_discount_amount) >= $options_total
                                        ? $options_total
                                        : ($discount_amount - $temp_discount_amount)
                                );
                            }
                        }

                        $line_item_amount += -max(0, $temp_discount_amount);
                        $line_item_description = Language::_(
                            'Invoices.!line_item.coupon_line_item_description_amount',
                            true,
                            $coupons[$coupon_id]->code
                        );
                    }

                    $currency = $amount->currency;
                }
            }
            unset($amount);

            // Create the line item
            if ($line_item_amount && $line_item_description && $currency) {
                // Convert the amount to the default currency for this client
                if ($currency != $default_currency) {
                    $line_item_amount = $this->Currencies->convert(
                        $line_item_amount,
                        $currency,
                        $default_currency,
                        Configure::get('Blesta.company_id')
                    );
                }

                $line_items[] = [
                    'service_id' => null,
                    'description' => $line_item_description,
                    'qty' => 1,
                    'amount' => $line_item_amount,
                    'tax' => false
                ];
            }
        }

        return $line_items;
    }

    /**
     * Retrieves the full total service options cost for the given service
     * @see Services::buildServiceCouponLineItems
     *
     * @param int $service_id The ID of the service to which the options belong
     * @return float The total cost of all service options
     */
    private function getServiceOptionsTotal($service_id)
    {
        Loader::loadModels($this, ['PackageOptions']);

        $total = 0.0;

        // Fetch the pricing info for this service in its defined currency
        // (no currency conversion) so service options (below)
        // can be converted from the original service currency to the new currency
        $base_pricing_info = $this->getPricingInfo($service_id);

        // Set each service configurable option line item
        $service_options = $this->getOptions($service_id);
        foreach ($service_options as $service_option) {
            $package_option = $this->PackageOptions->getByPricingId($service_option->option_pricing_id);

            if ($package_option
                && property_exists($package_option, 'value')
                && property_exists($package_option->value, 'pricing')
                && $package_option->value->pricing
            ) {
                // Get the total option price
                $amount = $this->PackageOptions->getValuePrice(
                    $package_option->value->id,
                    $package_option->value->pricing->term,
                    $package_option->value->pricing->period,
                    isset($base_pricing_info->currency) ? $base_pricing_info->currency : ''
                );

                // This doesn't consider proration
                if ($amount) {
                    $total += ($service_option->qty * $amount->price);
                }
            }
        }

        return $total;
    }

    /**
     * Retrieves a presenter representing a set of items, discounts, and taxes for the service
     *
     * @param int $service_id The ID of the service whose renewal pricing to fetch
     * @param array $options An array of options used to construct the presenter
     * @return bool|Blesta\Core\Pricing\Presenter\Type\ServicePresenter The presenter, otherwise false
     */
    public function getPresenter($service_id, array $options)
    {
        Loader::loadModels($this, ['Companies', 'Coupons', 'Invoices', 'ModuleManager']);
        Loader::loadComponents($this, ['SettingsCollection']);

        // We must have a service
        if (!($service = $this->get($service_id))) {
            return false;
        }

        // Fetch any coupon that should be applied
        $coupons = [];
        if (!empty($service->coupon_id) && ($coupon = $this->Coupons->get((int)$service->coupon_id))
            && $coupon->company_id == Configure::get('Blesta.company_id')
        ) {
            $coupons[] = $coupon;
        }

        // Retrieve the pricing builder from the container and update the date format options
        $container = Configure::get('container');
        $container['pricing.options'] = [
            'dateFormat' => $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'date_format')
                ->value,
            'dateTimeFormat' => $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'datetime_format')
                ->value
        ];

        // Determine if this is a domain service
        if ((
            $registrar = $this->ModuleManager->getInstalled([
                'type' => 'registrar',
                'company_id' => Configure::get('Blesta.company_id'),
                'module_id' => $service->package->module_id
            ])
        )) {
            $options['item_type'] = 'domain';
        }

        $factory = $this->getFromContainer('pricingBuilder');
        $serviceBuilder = $factory->service();

        // Build the service presenter
        $serviceBuilder->settings($this->SettingsCollection->fetchClientSettings($service->client_id));
        $serviceBuilder->taxes($this->Invoices->getTaxRules($service->client_id));
        $serviceBuilder->discounts($coupons);
        $serviceBuilder->options($options);

        return $serviceBuilder->build($service);
    }

    /**
     * Retrieves a presenter representing a set of items, discounts, and taxes for service data
     *
     * @param int $client_id The ID of the client the service data is for
     * @param array $vars An array of input representing the new service data:
     *
     *  - configoptions An array of key/value pairs where each key is an
     *      option ID and each value is the selected value
     *  - pricing_id The ID of the new pricing selected
     *  - qty The service quantity
     *  - coupon_code A new coupon code to use
     *  - parent_service_id The ID of this service's parent
     * @param array $options An array of options used to construct the presenter
     * @return bool|Blesta\Core\Pricing\Presenter\Type\ServiceDataPresenter The presenter, otherwise false
     */
    public function getDataPresenter($client_id, array $vars, array $options)
    {
        Loader::loadModels(
            $this,
            [
                'Companies', 'Clients', 'ClientGroups', 'Coupons',
                'Invoices', 'ModuleManager', 'Packages', 'PackageOptions'
            ]
        );
        Loader::loadComponents($this, ['SettingsCollection']);

        // Fetch the client
        $pricing = null;
        $pricing_id = (isset($vars['pricing_id']) ? (int)$vars['pricing_id'] : null);
        if (!$pricing_id) {
            return false;
        }

        // Fetch the package and the selected pricing
        if (($package = $this->Packages->getByPricingId($pricing_id))) {
            foreach ($package->pricing as $price) {
                if ($price->id == $pricing_id) {
                    $pricing = $price;
                    break;
                }
            }

            $service_date_renews = $this->getNextRenewDate(
                date('c'),
                $pricing->term,
                $pricing->period,
                'c',
                $package->prorata_day
            );

            if (($dates = $this->Packages->getProrataDates(
                $pricing_id,
                date('c'),
                $service_date_renews
            ))) {
                $options['prorateEndDate'] = $dates['end_date'];
            }
        }

        // Synchronize this service with its parent if set to do so and it is not already being prorated
        $renew_date = $this->Date->format(
            'Y-m-d',
            $this->getNextRenewDate(
                date('c'),
                $pricing->term,
                $pricing->period,
                'c'
            )
        );

        if (isset($vars['parent_service_id'])) {
            if (($client = $this->Clients->get($client_id))
                && ($client_group = $this->ClientGroups->get($client->client_group_id))
                && ($parent_service = $this->get($vars['parent_service_id']))
                && $this->canSyncToParent($pricing, $parent_service->package_pricing, $client_group->id)
                && $renew_date != $this->Date->format('Y-m-d', $parent_service->date_renews . 'Z')
            ) {
                $options['prorateEndDate'] = $parent_service->date_renews;
            }
        }

        // Package and pricing must be available
        if (empty($package) || empty($pricing)) {
            return false;
        }

        // Fetch all package options
        $package_options = $this->PackageOptions->getAllByPackageId(
            $package->id,
            $pricing->term,
            $pricing->period,
            $pricing->currency
        );

        // Determine if this is a domain service
        if ((
            $registrar = $this->ModuleManager->getInstalled([
                'type' => 'registrar',
                'company_id' => Configure::get('Blesta.company_id'),
                'module_id' => $package->module_id
            ])
        )) {
            $options['item_type'] = 'domain';
        }

        // Fetch any coupon that should be applied
        $coupons = [];
        if (isset($vars['coupon_id'])
            && ($coupon = $this->Coupons->get((int)$vars['coupon_id']))
            && $coupon->company_id == Configure::get('Blesta.company_id')
        ) {
            $coupons[] = $coupon;
        }

        // Retrieve the pricing builder from the container and update the date format options
        $container = Configure::get('container');
        $container['pricing.options'] = [
            'dateFormat' => $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'date_format')
                ->value,
            'dateTimeFormat' => $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'datetime_format')
                ->value
        ];

        $factory = $this->getFromContainer('pricingBuilder');
        $serviceData = $factory->serviceData();

        // Build the service presenter
        $serviceData->settings($this->SettingsCollection->fetchClientSettings($client_id));
        $serviceData->taxes($this->Invoices->getTaxRules($client_id));
        $serviceData->discounts($coupons);
        $serviceData->options($options);

        return $serviceData->build($vars, $package, $pricing, $package_options);
    }

    /**
     * Gets a list of items based on the service data given and updates line totals accordingly
     *
     * @param array $vars A list of service data:
     *
     *  - client_id The ID of the client this service is for
     *  - configoptions An array of key/value pairs where each key is an
     *      option ID and each value is the selected value
     *  - pricing_id The ID of the new pricing selected
     *  - qty The service quantity
     *  - coupon_code A new coupon code to use
     *  - parent_service_id The ID of this service's parent
     * @param array $options A list of options for the pricing presenter:
     *
     *  - recur Boolean true/false. Whether the pricing items are recurring,
     *      or if they are being added for the first time (default false)
     *  - transfer Boolean true/false. Whether to use the transfer price
     *      or not, i.e., the service is a domain being transferred.
     *      May affect coupon discounts. Default false.
     *  - includeSetupFees Whether to include applicable setup fees
     *  - prorateStartDate Will prorate the service from this date to the prorateEndDate
     *  - prorateEndDate Will override the otherwise calculated prorate end date
     * @param array $line_totals A list of totals to be updated:
     *
     *  - subtotal
     *  - total
     *  - total_without_exclusive_tax
     *  - tax A list of applicable tax totals
     *  - discount
     * @param string $currency The currency to convert the total to
     * @param string $from_currency The currency to convert the total from
     * @return array A list of invoice items for the service
     */
    public function getServiceItems(
        array $vars,
        array $options,
        array &$line_totals,
        $currency = null,
        $from_currency = null
    ) {
        Loader::loadModels($this, ['Currencies']);
        Loader::loadHelpers($this, ['CurrencyFormat']);
        $that = $this;

        // Get the data presenter for this service
        if (!($presenter = $this->getDataPresenter($vars['client_id'], $vars, $options))) {
            return [];
        }

        // Add total from this service to the overall totals
        $totals = $presenter->totals();

        // Anonymous function for converting values to the proper currency
        $convert = function ($amount) use ($that, $from_currency, $currency) {
            return $that->Currencies->convert($amount, $from_currency, $currency, Configure::get('Blesta.company_id'));
        };

        // Add to the totals
        $total_keys = ['subtotal', 'total', 'total_without_exclusive_tax'];
        foreach ($total_keys as $total_key) {
            if (!isset($line_totals[$total_key])) {
                $line_totals[$total_key] = 0;
            }

            $line_totals[$total_key] += $convert($totals->{$total_key});
        }

        if ($totals->discount_amount > 0) {
            if (!isset($line_totals['discount'])) {
                $line_totals['discount'] = 0;
            }

            // Add discount amount from this addon to the overall discount amount
            $line_totals['discount'] += -1 * $convert($totals->discount_amount);
        }

        if (!isset($line_totals['tax'])) {
            $line_totals['tax'] = [];
        }
        foreach ($presenter->taxes() as $tax) {
            if (!isset($line_totals['tax'][$tax->description])) {
                $line_totals['tax'][$tax->description] = 0;
            }

            // Add tax amount from this addon to the overall tax amount
            $line_totals['tax'][$tax->description] += $convert($tax->total);
        }

        $items = [];
        foreach ($presenter->items() as $item) {
            // Add an item under this addon (i.e. the service itself, a config option, a setup fee, etc.)
            $items[] = [
                'description' => $item->description,
                'qty' => $item->qty,
                'price' => $this->CurrencyFormat->format($convert($item->subtotal), $currency)
            ];
        }

        return $items;
    }

    /**
     * Retrieves the next expected renewal price of a service based on its current
     * configuration, options, and pricing
     *
     * @param int $service_id The ID of the service whose renewal pricing to fetch
     * @param string $currency The ISO 4217 3-character currency code to convert the total to
     * @return float The next expected renewal price
     */
    public function getRenewalPrice($service_id, $currency = null)
    {
        Loader::loadModels($this, ['Currencies']);

        // Fetch the service
        $service = $this->get($service_id);

        // Non-recurring pricing do not renew
        if (!$service || $service->package_pricing->period == 'onetime') {
            return 0;
        }

        // Get the service items
        $options = [
            'includeSetupFees' => false,
            'recur' => true,
            'applyDate' => (!empty($service->date_renews) ? $service->date_renews . 'Z' : date('c'))
        ];
        $presenter = $this->getPresenter($service_id, $options);

        $total = $presenter->totals()->total;

        // Convert the total to the given currency
        if ($currency && !empty($service->package_pricing->currency)) {
            $total = $this->Currencies->convert(
                $total,
                $service->package_pricing->currency,
                $currency,
                Configure::get('Blesta.company_id')
            );
        }

        return $total;
    }

    /**
     * Return all field data for the given service, decrypting fields where neccessary
     *
     * @param int $service_id The ID of the service to fetch fields for
     * @return array An array of stdClass objects representing fields, containing:
     *
     *  - key The service field name
     *  - value The value for this service field
     *  - encrypted Whether or not this field was originally encrypted (1 true, 0 false)
     */
    protected function getFields($service_id)
    {
        $fields = $this->Record->select(['key', 'value', 'serialized', 'encrypted'])->
            from('service_fields')->where('service_id', '=', $service_id)->
            fetchAll();
        $num_fields = count($fields);
        for ($i = 0; $i < $num_fields; $i++) {
            // If the field is encrypted, must decrypt the field
            if ($fields[$i]->encrypted) {
                $fields[$i]->value = $this->systemDecrypt($fields[$i]->value);
            }

            if ($fields[$i]->serialized) {
                $fields[$i]->value = unserialize($fields[$i]->value);
            }
        }

        return $fields;
    }

    /**
     * Returns info regarding the module belonging to the given $package_pricing_id
     *
     * @param int $package_pricing_id The package pricing ID to fetch the module of
     * @return mixed A stdClass object containing module info and the package
     *  ID belonging to the given $package_pricing_id, false if no such module exists
     */
    private function getModuleClassByPricingId($package_pricing_id)
    {
        return $this->Record->select(['modules.*', 'packages.id' => 'package_id'])->from('package_pricing')->
            innerJoin('packages', 'packages.id', '=', 'package_pricing.package_id', false)->
            innerJoin('modules', 'modules.id', '=', 'packages.module_id', false)->
            where('package_pricing.id', '=', $package_pricing_id)->
            fetch();
    }

    /**
     * Formats given config options by removing options with 0 quantity
     *
     * @param array $config_options An array of key/value pairs of package
     *  options where the key is the package option ID and the value is the option value (optional)
     * @return array An array of key/value pairs of package options where
     *  the key is the package option ID and the value is the option value
     */
    private function formatConfigOptions(array $config_options = [])
    {
        if (!isset($this->PackageOptions)) {
            Loader::loadModels($this, ['PackageOptions']);
        }

        // Remove config options with quantity of 0
        if (!empty($config_options)) {
            foreach ($config_options as $option_id => $value) {
                if ($value == 0 && ($option = $this->PackageOptions->get($option_id)) && $option->type == 'quantity') {
                    unset($config_options[$option_id]);
                    continue;
                }
            }
        }

        return $config_options;
    }

    /**
     * Validates a service's 'status' field
     *
     * @param string $status The status type
     * @return bool True if $status is valid, false otherwise
     */
    public function validateStatus($status)
    {
        $options = array_keys($this->getStatusTypes());
        return in_array($status, $options);
    }

    /**
     * Validates whether to use a module when adding/editing a service
     *
     * @param string $use_module
     * @return bool True if validated, false otherwise
     */
    public function validateUseModule($use_module)
    {
        $options = ['true', 'false'];
        return in_array($use_module, $options);
    }

    /**
     * Validates a service field's 'encrypted' field
     *
     * @param string $encrypted Whether or not to encrypt
     */
    public function validateEncrypted($encrypted)
    {
        $options = [0, 1, 'true', 'false'];
        return in_array($encrypted, $options);
    }

    /**
     * Validates whether the given service has children NOT of the given status
     *
     * @param int $service_id The ID of the parent service to validate
     * @param string $status The status of children services to ignore
     *  (e.g. "canceled") (optional, default null to not ignore any child services)
     * @return bool True if the service has children not of the given status, false otherwise
     */
    public function validateHasChildren($service_id, $status = null)
    {
        $this->Record->select()->from('services')->
            where('parent_service_id', '=', $service_id);

        if ($status) {
            $this->Record->where('status', '!=', $status);
        }

        return ($this->Record->numResults() > 0);
    }

    /**
     * Retrieves the rule set for adding/editing service fields
     *
     * @return array The rules
     */
    public function getFieldRules()
    {
        $rules = [
            'key' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Services.!error.key.empty')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 32],
                    'message' => $this->_('Services.!error.key.length')
                ],
            ],
            'encrypted' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateEncrypted']],
                    'message' => $this->_('Services.!error.encrypted.format'),
                    'post_format' => [[$this, 'boolToInt']]
                ]
            ]
        ];
        return $rules;
    }

    /**
     * Retrieves the rule set for adding/editing services
     *
     * @param array $vars An array of input fields
     * @param bool $edit Whether or not this is an edit request
     * @param int $service_id The ID of the service being edited (optional, default null)
     * @return array The rules
     */
    private function getRules($vars, $edit = false, $service_id = null)
    {
        $rules = [
            'parent_service_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'services'],
                    'message' => $this->_('Services.!error.parent_service_id.exists')
                ],
                'parent' => [
                    'if_set' => true,
                    'rule' => [[$this, 'hasParent']],
                    'negate' => true,
                    'message' => $this->_('Services.!error.parent_service_id.parent')
                ]
            ],
            'package_group_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'package_groups'],
                    'message' => $this->_('Services.!error.package_group_id.exists')
                ]
            ],
            'id_format' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Services.!error.id_format.empty')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 64],
                    'message' => $this->_('Services.!error.id_format.length')
                ]
            ],
            'id_value' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'isInstanceOf'], 'Record'],
                    'message' => $this->_('Services.!error.id_value.valid')
                ]
            ],
            'pricing_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'package_pricing'],
                    'message' => $this->_('Services.!error.pricing_id.exists')
                ]
            ],
            'client_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => $this->_('Services.!error.client_id.exists')
                ],
                'allowed' => [
                    'rule' => [[$this, 'validateAllowed'], isset($vars['pricing_id']) ? $vars['pricing_id'] : null],
                    'message' => $this->_('Services.!error.client_id.allowed')
                ]
            ],
            'module_row_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'module_rows'],
                    'message' => $this->_('Services.!error.module_row_id.exists')
                ]
            ],
            'coupon_id' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [
                        [$this, 'validateCoupon'],
                        isset($vars['coupon_packages']) ? $vars['coupon_packages'] : null
                    ],
                    'message' => $this->_('Services.!error.coupon_id.valid')
                ]
            ],
            'qty' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('Services.!error.qty.format')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 10],
                    'message' => $this->_('Services.!error.qty.length')
                ],
                'available' => [
                    'if_set' => true,
                    'rule' => [
                        [$this, 'decrementQuantity'],
                        isset($vars['pricing_id']) ? $vars['pricing_id'] : null,
                        true,
                        $edit && isset($vars['current_qty']) ? $vars['current_qty'] : null
                    ],
                    'message' => $this->_('Services.!error.qty.available')
                ]
            ],
            'override_price' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validatePriceOverride']],
                    'message' => $this->_('Services.!error.override_price.format'),
                    'post_format' => [[$this, 'currencyToDecimal'], ['_linked' => 'override_currency'], 4]
                ],
                'override' => [
                    'rule' => [
                        [$this, 'validateOverrideFields'],
                        (isset($vars['override_currency']) ? $vars['override_currency'] : null)
                    ],
                    'message' => $this->_('Services.!error.override_price.override')
                ]
            ],
            'override_currency' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'code', 'currencies'],
                    'message' => $this->_('Services.!error.override_currency.format')
                ]
            ],
            'status' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateStatus']],
                    'message' => $this->_('Services.!error.status.format')
                ]
            ],
            'date_added' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isDate',
                    'post_format' => [[$this, 'dateToUtc']],
                    'message' => $this->_('Services.!error.date_added.format')
                ]
            ],
            'date_renews' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [
                        [$this, 'validateDateRenews'],
                        isset($vars['date_last_renewed']) ? $vars['date_last_renewed'] : null
                    ],
                    'message' => $this->_(
                        'Services.!error.date_renews.valid',
                        isset($vars['date_last_renewed'])
                        ? $this->Date->cast($vars['date_last_renewed'], 'Y-m-d')
                        : null
                    )
                ],
                'format' => [
                    'if_set' => true,
                    'rule' => 'isDate',
                    'post_format' => [[$this, 'dateToUtc']],
                    'message' => $this->_('Services.!error.date_renews.format')
                ]
            ],
            'date_last_renewed' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isDate',
                    'post_format' => [[$this, 'dateToUtc']],
                    'message' => $this->_('Services.!error.date_last_renewed.format')
                ]
            ],
            'date_suspended' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isDate',
                    'post_format' => [[$this, 'dateToUtc']],
                    'message' => $this->_('Services.!error.date_suspended.format')
                ]
            ],
            'date_canceled' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isDate',
                    'post_format' => [[$this, 'dateToUtc']],
                    'message' => $this->_('Services.!error.date_canceled.format')
                ]
            ],
            'use_module' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateUseModule']],
                    'message' => $this->_('Services.!error.use_module.format')
                ]
            ],
            'configoptions' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [
                        [$this, 'validateConfigOptions'],
                        isset($vars['pricing_id']) ? $vars['pricing_id'] : null
                    ],
                    'message' => $this->_('Services.!error.configoptions.valid')
                ]
            ],
            'module_row' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'module_rows'],
                    'message' => $this->_('Services.!error.module_row.valid')
                ]
            ],
            'module_group' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'module_groups'],
                    'message' => $this->_('Services.!error.module_group.valid')
                ]
            ]
        ];

        // Set rules for editing services
        if ($edit) {
            Loader::loadModels($this, ['PackageOptions']);

            // Determine override pricing
            $override_price = (array_key_exists('override_price', $vars) ? $vars['override_price'] : '');
            $override_currency = (array_key_exists('override_currency', $vars) ? $vars['override_currency'] : '');
            if ($service_id && ($service = $this->get($service_id))) {
                if ($override_price === '' || $override_currency === '') {
                    // Empty strings set for override pricing will fail validation,
                    // so if one is not given, use the current values
                    if ($override_currency === '') {
                        $override_currency = $service->override_currency;
                    }
                    if ($override_price === '') {
                        $override_price = $service->override_price;
                    }
                }

                if (!empty($vars['coupon_id']) && $vars['coupon_id'] == $service->coupon_id) {
                    unset($rules['coupon_id']);
                }

                // Update the rule for configoptions to pass in the current service options to validate against
                $options = $this->PackageOptions->formatServiceOptions($service->options);
                $rules['configoptions']['valid']['rule'] = [
                    [$this, 'validateConfigOptions'],
                    isset($vars['pricing_id']) ? $vars['pricing_id'] : null,
                    isset($options['configoptions']) ? (array)$options['configoptions'] : []
                ];
            }

            // Validate service exists
            $rules['service_id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'services'],
                    'message' => $this->_('Services.!error.service_id.exists')
                ]
            ];

            // Pricing ID rule
            $rules['pricing_id']['overrides'] = [
                'rule' => [[$this, 'validatePricingWithOverrides'], $service_id, $override_price, $override_currency],
                'message' => $this->_('Services.!error.pricing_id.overrides')
            ];

            $rules['prorate'] = [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['true', 'false']],
                    'message' => $this->_('Services.!error.prorate.format')
                ]
            ];

            // Remove id_format and id_value, they cannot be updated
            unset($rules['id_format'], $rules['id_value'], $rules['client_id']['allowed']);

            $rules['pricing_id']['exists']['if_set'] = true;
            $rules['client_id']['exists']['if_set'] = true;
        }

        return $rules;
    }

    /**
     * Determines if a service should be prorated to match its parent
     *
     * @param stdClass $pricing The new service pricing
     * @param stdClass $parent_pricing The parent service pricing
     * @param int $client_group_id The ID of the client group this service is under
     * @return bool Whether to synchronize a service with its parent
     */
    public function canSyncToParent(stdClass $pricing, stdClass $parent_pricing, $client_group_id)
    {
        Loader::loadModels($this, ['Packages', 'ClientGroups']);
        // Determine whether this service is prorated to match the renew date of its parent service
        return (
            ($synchronize_addons = $this->ClientGroups->getSetting($client_group_id, 'synchronize_addons'))
            && $synchronize_addons->value == 'true'
            && $pricing
            && $parent_pricing
            && $parent_pricing->term == $pricing->term
            && $parent_pricing->period == $pricing->period
            && ($pricing->period == 'month' || $pricing->period == 'year')
            && ($package = $this->Packages->getByPricingId($pricing->id))
            && $package->prorata_day === null
        );
    }

    /**
     * Determines the date a service should be prorated to so it matches its parent
     *
     * @param stdClass $pricing The new service pricing
     * @param stdClass $parent_pricing The parent service pricing
     * @param int $client_group_id The ID of the client group this service is under
     * @return string The expected renew date of the new service, or false if not being synchronized
     */
    public function getChildRenewDate(stdClass $pricing, stdClass $parent_pricing, $client_group_id)
    {
        Loader::loadModels($this, ['Packages']);
        if ($this->canSyncToParent($pricing, $parent_pricing, $client_group_id)) {
            $package = $this->Packages->getByPricingId($parent_pricing->id);
            // Get the next renew date for the parent service
            $parent_date_renews = $this->getNextRenewDate(
                date('c'),
                $parent_pricing->term,
                $parent_pricing->period,
                'Y-m-d H:i:s',
                $package ? $package->prorata_day : null
            );

            // Prorate the parent service if set to do so
            if (($dates = $this->Packages->getProrataDates($parent_pricing->id, date('c'), $parent_date_renews))) {
                $parent_date_renews = $dates['end_date'];
            }

            // No need to submit a prorata day since a prorated package would not have reached this point
            $service_date_renews = $this->getNextRenewDate(date('c'), $pricing->term, $pricing->period, 'Y-m-d H:i:s');
            if ($this->Date->format('Y-m-d', $parent_date_renews)
                != $this->Date->format('Y-m-d', $service_date_renews)
            ) {
                // Return the parent service's renew date
                return $parent_date_renews;
            }
        }

        return false;
    }

    /**
     * Determines whether the given option value is currently in use by any non-cancelled service
     *
     * @param int $option_value_id The package option value ID
     * @param int $option_pricing_id The package option pricing ID
     * @return bool True if the service option value is in use by at least one service, or false otherwise
     */
    public function isServiceOptionValueInUse($option_value_id, $option_pricing_id = null)
    {
        $this->Record->select(['services.id'])
            ->from('services')
            ->innerJoin('service_options', 'service_options.service_id', '=', 'services.id', false)
            ->innerJoin(
                'package_option_pricing',
                'package_option_pricing.id',
                '=',
                'service_options.option_pricing_id',
                false
            )
            ->where('package_option_pricing.option_value_id', '=', $option_value_id)
            ->where('services.status', '!=', 'canceled');

        if ($option_pricing_id !== null) {
            $this->Record->where('package_option_pricing.id', '=', (int)$option_pricing_id);
        }

        return ($this->Record->fetch() !== false);
    }

    /**
     * Checks if the given $field is a reference of $class
     *
     * @param mixed $field The field whose instance to check
     * @param mixed $class The class or instance to check against
     * @return bool True if the $field is an instance of $class, otherwise false
     */
    public function isInstanceOf($field, $class)
    {
        return $field instanceof $class;
    }

    /**
     * Gets the type of the given service
     *
     * @param int $service_id The ID of the parent service to validate
     * @return string The type of the service, it could be "service" or "domain"
     */
    public function getType($service_id)
    {
        $service = $this->get($service_id);

        // Get the module belonging to the service
        Loader::loadModels($this, ['ModuleManager']);
        $module = $this->ModuleManager->initModule($service->package->module_id);

        if (is_subclass_of($module, 'RegistrarModule')) {
            return 'domain';
        }

        return 'service';
    }

    /**
     * Performs all validation necessary before adding a service
     *
     * @param array $vars An array of service info including:
     *
     *  - parent_service_id The ID of the service this service is a child of (optional)
     *  - package_group_id The ID of the package group this service was added from (optional)
     *  - pricing_id The package pricing schedule ID for this service
     *  - client_id The ID of the client to add the service under
     *  - module_row_id The module row to add the service under (optional, default is first available)
     *  - coupon_id The ID of the coupon used for this service (optional)
     *  - qty The quanity consumed by this service (optional, default 1)
     *  - status The status of this service ('active','canceled','pending','suspended', default 'pending')
     *  - date_added The date this service is added (default to today's date UTC)
     *  - date_renews The date the service renews (optional, default calculated by package term)
     *  - date_last_renewed The date the service last renewed (optional)
     *  - date_suspended The date the service was last suspended (optional)
     *  - date_canceled The date the service was last canceled (optional)
     *  - use_module Whether or not to use the module when creating the service ('true','false', default 'true')
     *  - configoptions An array of key/value pairs of package options where
     *      the key is the package option ID and the value is the option value (optional)
     *  - * Any other service field data to pass to the module
     * @param array $packages An array of packages ordered along with this service to determine
     *  if the given coupon may be applied given in one of the following formats:
     *
     *  - A numerically indexed array of package IDs
     *  - An array of package IDs and pricing IDs [packageID => pricingID]
     * @return array $vars An array of $vars, modified by error checking
     * @see Services::validateService()
     */
    public function validate(array $vars, array $packages = null)
    {
        Loader::loadModels($this, ['Packages', 'Clients', 'ClientGroups']);
        if (!isset($this->ModuleManager)) {
            Loader::loadModels($this, ['ModuleManager']);
        }

        $vars['coupon_packages'] = $packages;

        if (!isset($vars['qty'])) {
            $vars['qty'] = 1;
        }

        // Check basic rules
        $this->Input->setRules($this->getRules($vars, false));

        // Set date added if not given
        if (!isset($vars['date_added'])) {
            $vars['date_added'] = date('c');
        }

        // Get the package
        if (isset($vars['pricing_id']) && empty($vars['date_renews'])) {
            $package = $this->Packages->getByPricingId($vars['pricing_id']);

            // Set the next renew date based on the package pricing
            if ($package) {
                foreach ($package->pricing as $pricing) {
                    if ($pricing->id == $vars['pricing_id']) {
                        // Set date renews
                        $vars['date_renews'] = $this->getNextRenewDate(
                            $vars['date_added'],
                            $pricing->term,
                            $pricing->period,
                            'c',
                            $package->prorata_day
                        );
                        break;
                    }
                }
                unset($pricing);
            }

            // Set the services renew date to that of its parent if set to do so and it is not
            // already being prorated
            if (isset($vars['parent_service_id']) && isset($vars['client_id'])) {
                $pricing = $this->getPackagePricing($vars['pricing_id']);
                if (($client = $this->Clients->get($vars['client_id']))
                    && ($client_group = $this->ClientGroups->get($client->client_group_id))
                    && ($parent_service = $this->get($vars['parent_service_id']))
                    && $this->canSyncToParent($pricing, $parent_service->package_pricing, $client_group->id)
                ) {
                    $vars['date_renews'] = $parent_service->date_renews . 'Z';
                }
            }
        }

        // Check if module row has been provided as a configurable option
        $module_fields = $this->getConfigurableModuleFields($vars['configoptions'] ?? []);
        $vars = array_merge($vars, $module_fields);
        if ($this->Input->validates($vars)) {
            $module = $this->ModuleManager->initModule($package->module_id);

            if ($module) {
                // Reformat $vars[configoptions] to support name/value fields defined by the package options
                $temp_options = [];
                if (isset($vars['configoptions']) && is_array($vars['configoptions'])) {
                    $temp_options = $vars['configoptions'];
                    $vars['configoptions'] = $this->PackageOptions->formatOptions($temp_options);
                }

                $module->validateService($package, $vars);

                if (isset($vars['configoptions']) && is_array($vars['configoptions'])) {
                    $vars['configoptions'] = $temp_options;
                }

                // If any errors encountered through the module, set errors
                if (($errors = $module->errors())) {
                    $this->Input->setErrors($errors);
                    return;
                }
            }
        }
        return $vars;
    }

    /**
     * Performs all validation necessary before updating a service
     *
     * @param int $service_id The ID of the service to validate
     * @param array $vars An array of key/value pairs to be evaluated
     * @param bool $bypass_module Whether or not to ignore the module service edit validation
     * @return array $vars An array of $vars, modified by error checking:
     *
     *  - configoptions An array of key/value pairs of package options where
     *      the key is the package option ID and the value is the option value (optional)
     * @see Services::validateServiceEdit()
     */
    private function validateEdit($service_id, array $vars, $bypass_module = false)
    {
        $service = $this->get($service_id);

        // Set the service ID to validate against
        $vars['service_id'] = $service_id;

        // If the renew date changes, set the last renew date for rule validation whether given or not unless the
        // given value is null
        if (isset($vars['date_renews'])) {
            if (isset($vars['date_last_renewed'])) {
                $vars['date_last_renewed'] = $this->dateToUtc($vars['date_last_renewed'], 'c');
            } elseif ($service && $service->date_last_renewed) {
                $vars['date_last_renewed'] = $this->dateToUtc(strtotime($service->date_last_renewed . 'Z'), 'c');
            }
        }

        // Set whether to use the module based on whether we are bypassing it
        if ($bypass_module) {
            $vars['use_module'] = 'false';
        } elseif (!isset($vars['use_module'])) {
            $vars['use_module'] = 'true';
        }

        if ($service) {
            // Ensure we have a pricing ID set
            if (!isset($vars['pricing_id'])) {
                $vars['pricing_id'] = $service->pricing_id;
            }

            // Ensure we have a quantity set
            if (!isset($vars['qty'])) {
                $vars['qty'] = $service->qty;
            }

            $vars['current_qty'] = $service->qty;

            // If service is currently pending and status is now "active",
            // the service will be added, not edited, via the module
            // @see Services::edit
            if ($service->status == 'pending' && isset($vars['status']) && $vars['status'] == 'active') {
                // So bypass just validating the module service edit since it wouldn't exist on it anyway
                $bypass_module = true;
            }
        }

        if (!isset($this->Packages)) {
            Loader::loadModels($this, ['Packages']);
        }

        if (isset($vars['pricing_id']) && ($new_package = $this->Packages->getByPricingId($vars['pricing_id']))) {
            $vars['coupon_packages'] = [$new_package->id => $vars['pricing_id']];
        }

        // Check if module row has been provided as a configurable option
        $module_fields = $this->getConfigurableModuleFields($vars['configoptions'] ?? []);
        $vars = array_merge($vars, $module_fields);

        // Validate whether the service can be edited
        $this->Input->setRules($this->getRules($vars, true, $service_id));

        if ($this->Input->validates($vars) && !$bypass_module) {
            // Validate that the service can be edited via the module
            $module = $this->ModuleManager->initModule($service->package->module_id);

            if ($module) {
                // Reformat $vars[configoptions] to support name/value fields defined by the package options
                $temp_options = [];
                if (isset($vars['configoptions']) && is_array($vars['configoptions'])) {
                    $temp_options = $vars['configoptions'];
                    $vars['configoptions'] = $this->PackageOptions->formatOptions($temp_options);
                }

                $module->validateServiceEdit($service, $vars);

                if (isset($vars['configoptions']) && is_array($vars['configoptions'])) {
                    $vars['configoptions'] = $temp_options;
                }

                // If any errors encountered through the module, set errors
                if (($errors = $module->errors())) {
                    $this->Input->setErrors($errors);
                }
            }
        }

        return $vars;
    }

    /**
     * Validates service info, including module options, for creating a service. An alternative to Services::validate()
     * Sets Input errors on failure
     *
     * @param stdClass $package A stdClass object representing the package for the service
     * @param array $vars An array of values to be evaluated, including:
     *
     *  - invoice_method The invoice method to use when creating the service, options are:
     *      - create Will create a new invoice when adding this service
     *      - append Will append this service to an existing invoice (see 'invoice_id')
     *      - none Will not create any invoice
     *  - invoice_id The ID of the invoice to append to if invoice_method is set to 'append'
     *  - pricing_id The ID of the package pricing to use for this service
     *  - configoptions An array of key/value pairs of package options where
     *      the key is the package option ID and the value is the option value (optional)
     *  - * Any other service field data to pass to the module
     * @see Services::validate()
     */
    public function validateService($package, array $vars)
    {
        if (!isset($this->ModuleManager)) {
            Loader::loadModels($this, ['ModuleManager']);
        }

        // Check if module row has been provided as a configurable option
        $module_fields = $this->getConfigurableModuleFields($vars['configoptions'] ?? []);
        $vars = array_merge($vars, $module_fields);

        $rules = [
            /*
            'client_id' => array(
                'exists' => array(
                    'rule' => array(array($this, "validateExists"), "id", "clients"),
                    'message' => $this->_("Services.!error.client_id.exists")
                )
            ),
            */
            'invoice_method' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['create', 'append', 'none']],
                    'message' => $this->_('Services.!error.invoice_method.valid')
                ]
            ],
            'pricing_id' => [
                'valid' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'package_pricing'],
                    'message' => $this->_('Services.!error.pricing_id.valid')
                ]
            ],
            'configoptions' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [
                        [$this, 'validateConfigOptions'],
                        isset($vars['pricing_id']) ? $vars['pricing_id'] : null
                    ],
                    'message' => $this->_('Services.!error.configoptions.valid')
                ]
            ],
            'qty' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('Services.!error.qty.format')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 10],
                    'message' => $this->_('Services.!error.qty.length')
                ],
                'available' => [
                    'if_set' => true,
                    'rule' => [
                        [$this, 'decrementQuantity'],
                        isset($vars['pricing_id']) ? $vars['pricing_id'] : null,
                        true
                    ],
                    'message' => $this->_('Services.!error.qty.available')
                ]
            ],
            'module_row' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'module_rows'],
                    'message' => $this->_('Services.!error.module_row.valid')
                ]
            ],
            'module_group' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'module_groups'],
                    'message' => $this->_('Services.!error.module_group.valid')
                ]
            ],
            /*
            'status' => array(
                'format' => array(
                    'if_set' => true,
                    'rule' => array(array($this, "validateStatus")),
                    'message' => $this->_("Services.!error.status.format")
                )
            ),
            */
        ];

        $this->Input->setRules($rules);
        if ($this->Input->validates($vars)) {
            $module_data = $this->getModuleClassByPricingId($vars['pricing_id']);

            if ($module_data) {
                $module = $this->ModuleManager->initModule($module_data->id);

                // Reformat $vars[configoptions] to support name/value fields defined by the package options
                $temp_options = [];
                if (isset($vars['configoptions']) && is_array($vars['configoptions'])) {
                    $temp_options = $vars['configoptions'];
                    $vars['configoptions'] = $this->PackageOptions->formatOptions($temp_options);
                }

                if ($module && !$module->validateService($package, $vars)) {
                    $this->Input->setErrors($module->errors());
                }

                if (isset($vars['configoptions']) && is_array($vars['configoptions'])) {
                    $vars['configoptions'] = $temp_options;
                }
            }
        }
    }

    /**
     * Performs all validation necessary for updating a service. Sets Input errors on failure
     *
     * @param int $service_id The ID of the service to validate
     * @param array $vars An array of key/value pairs to be evaluated:
     *
     *  - configoptions An array of key/value pairs of package options where
     *      the key is the package option ID and the value is the option value (optional)
     * @param bool $bypass_module Whether or not to ignore the module service edit validation
     */
    public function validateServiceEdit($service_id, array $vars, $bypass_module = false)
    {
        $this->validateEdit($service_id, $vars, $bypass_module);
    }

    /**
     * Verifies if the given coupon ID can be applied to the requested packages
     *
     * @param int $coupon_id The ID of the coupon to validate
     * @param array $packages An array of pacakges to confirm the coupon can be applied given
     *  in one of the following formats:
     *
     *  - A numerically indexed array of package IDs
     *  - An array of package IDs and pricing IDs [packageID => pricingID]
     * @return bool True if the coupon can be applied, false otherwise
     */
    public function validateCoupon($coupon_id, array $packages = null)
    {
        if (!isset($this->Coupons)) {
            Loader::loadModels($this, ['Coupons']);
        }

        return (boolean)$this->Coupons->getForPackages(null, $coupon_id, $packages);
    }

    /**
     * Verifies that the given date value is valid for a cancel date
     *
     * @param string $date The date to cancel a service or "end_of_term" to cancel at the end of the term
     * @return bool True if $date is valid, false otherwise
     */
    public function validateDateCanceled($date)
    {
        return ($this->Input->isDate($date) || strtolower($date) == 'end_of_term');
    }

    /**
     * Verifies that the given renew date is greater than the last renew date (if available)
     *
     * @param string $renew_date The date a service should renew
     * @param string $last_renew_date The date a service last renewed
     * @return bool True if renew date is valid, false otherwise
     */
    public function validateDateRenews($renew_date, $last_renew_date = null)
    {
        if ($last_renew_date) {
            return $this->dateToUtc($renew_date) > $this->dateToUtc($last_renew_date);
        }
        return true;
    }

    /**
     * Verifies that the given price override is in a valid format
     *
     * @param float $price The price override
     * @return bool True if the price is valid, false otherwise
     */
    public function validatePriceOverride($price)
    {
        if ($price === null) {
            return true;
        }

        return is_numeric($price);
    }

    /**
     * Verifies that the given price and currency fields have been set together
     *
     * @param mixed $price The price override, or null
     * @param mixed $currency The currency override, or null
     * @return bool True if the price and currency have been set properly together, or false otherwise
     */
    public function validateOverrideFields($price, $currency)
    {
        // Price and currency overrides need to both be null, or both be set
        if (($price === null && $currency === null) || ($price !== null && $currency !== null)) {
            return true;
        }

        return false;
    }

    /**
     * Verifies that the given service and pricing ID are valid with price overrides
     *
     * @param int $pricing_id The ID of the pricing term
     * @param int $service_id The ID of the service being updated
     * @param float $price The price override amount
     * @param string $currency The price override currency
     * @return bool True if the pricing ID may be set for this service given the price overrides, or false otherwise
     */
    public function validatePricingWithOverrides($pricing_id, $service_id, $price, $currency)
    {
        $service = $this->get($service_id);

        if ($service) {
            // Package pricing can only be changed if overrides are valid
            if ($service->pricing_id != $pricing_id) {
                // If removing the price and currency overrides, changing the pricing term is valid
                if ($price === null && $currency === null) {
                    return true;
                }

                // Cannot change package term when price overrides are set
                if ($service->override_price !== null
                    || $service->override_currency !== null
                    || $price !== null
                    || $currency !== null
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Verifies that the client has access to the package for the given pricing ID
     *
     * @param int $client_id The ID of the client
     * @param int $pricing_id The ID of the package pricing
     * @return bool True if the client can add the package, false otherwise
     */
    public function validateAllowed($client_id, $pricing_id)
    {
        if ($pricing_id == null) {
            return true;
        }
        return (boolean)$this->Record->select(['packages.id'])->from('package_pricing')->
            innerJoin('packages', 'packages.id', '=', 'package_pricing.package_id', false)->
            on('client_packages.client_id', '=', $client_id)->
            leftJoin('client_packages', 'client_packages.package_id', '=', 'packages.id', false)->
            where('package_pricing.id', '=', $pricing_id)->
            open()->
                where('packages.status', '=', 'active')->
                open()->
                    orWhere('packages.status', '=', 'restricted')->
                    Where('client_packages.client_id', '=', $client_id)->
                close()->
            close()->
            fetch();
    }

    /**
     * Verifies that the given package options are valid
     *
     * @param array $config_options An array of key/value pairs where each key
     *  is the package option ID and each value is the option value
     * @param int $pricing_id The package pricing ID
     * @param array $current_options An array of key/value pairs currently on the service
     *  where each key is the package option ID and each value is the option value
     * @return bool True if valid, false otherwise
     */
    public function validateConfigOptions($config_options, $pricing_id, array $current_options = [])
    {
        if (!isset($this->PackageOptions)) {
            Loader::loadModels($this, ['PackageOptions']);
        }

        foreach ($config_options as $option_id => $value) {
            // Fetch the package option associated with the selected config option
            $option = $this->PackageOptions->get($option_id);
            $type = ($option ? $option->type : '');

            $this->Record->select(['package_option_values.*'])
                ->from('package_pricing')
                ->innerJoin(
                    'package_option',
                    'package_pricing.package_id',
                    '=',
                    'package_option.package_id',
                    false
                )
                ->innerJoin(
                    'package_option_group',
                    'package_option_group.option_group_id',
                    '=',
                    'package_option.option_group_id',
                    false
                )
                ->innerJoin(
                    'package_options',
                    'package_options.id',
                    '=',
                    'package_option_group.option_id',
                    false
                )
                ->innerJoin(
                    'package_option_values',
                    'package_option_values.option_id',
                    '=',
                    'package_options.id',
                    false
                )
                ->where('package_options.id', '=', $option_id)
                ->where('package_pricing.id', '=', $pricing_id);

            // Package option types text/textarea/password do not need to verify against a specific value
            // since the option value is provided by the user, but the other package option types do
            if (!in_array($type, ['text', 'textarea', 'password'])) {
                $this->Record->open()
                ->where('package_option_values.value', '=', $value)
                ->orWhere('package_options.type', '=', 'quantity')
                ->close();
            }

            $result = $this->Record->fetch();

            if (!$result) {
                return false;
            }

            // Check quantities
            if (($result->min != null && $result->min > $value)) {
                return false;
            }

            $max_32_bit_integer = 4294967295;
            if (($result->max != null && $result->max < $value)
                || ($type == 'quantity' && $value > $max_32_bit_integer)
            ) {
                return false;
            }

            if ($result->step != null && $value != $result->max && ($value - (int)$result->min) % $result->step !== 0) {
                return false;
            }

            // Inactive option values are invalid unless they are already a current option value
            if ($result->status === 'inactive'
                && (!array_key_exists($option_id, $current_options) || $current_options[$option_id] != $value)
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Decrements the package quantity if $check_only is false, otherwise only validates
     * the quantity could be decremented.
     *
     * @param int $quantity The quantity requested
     * @param int $pricing_id The pricing ID
     * @param bool $check_only True to only verify the quantity could be decremented, false otherwise
     * @param mixed $current_qty The currenty quantity being consumed by the service
     * @return bool true if the quantity could be (not necessarily has been) consumed, false otherwise
     */
    public function decrementQuantity($quantity, $pricing_id, $check_only = true, $current_qty = null)
    {
        if (!$pricing_id) {
            return true;
        }

        // Check if quantity can be deductable
        $consumable = (boolean)$this->Record->select()->from('package_pricing')->
            innerJoin('packages', 'package_pricing.package_id', '=', 'packages.id', false)->
            where('package_pricing.id', '=', $pricing_id)->
            open()->
                where('packages.qty', '>=', $quantity - (int)$current_qty)->
                orWhere('packages.qty', '=', null)->
            close()->
            fetch();

        if ($consumable && !$check_only) {
            $this->Record->set('packages.qty', 'packages.qty-?', false)->
                appendValues([$quantity - (int)$current_qty])->
                innerJoin('package_pricing', 'package_pricing.package_id', '=', 'packages.id', false)->
                where('package_pricing.id', '=', $pricing_id)->
                where('packages.qty', '>', 0)->
                update('packages');
        }
        return $consumable;
    }
}
