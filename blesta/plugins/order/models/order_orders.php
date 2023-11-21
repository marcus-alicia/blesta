<?php
/**
 * Order Management
 *
 * @package blesta
 * @subpackage blesta.plugins.order.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderOrders extends OrderModel
{
    /**
     * Fetch a specific order by order ID
     *
     * @param int $order_id The ID of the order to fetch
     * @return stdClass A stdClass object representing the order
     */
    public function get($order_id)
    {
        $this->Record = $this->getOrders();
        $order = $this->Record->where('orders.id', '=', $order_id)->fetch();

        if ($order) {
            $order->services = $this->Record->select()->from('order_services')->
                where('order_id', '=', $order_id)->fetchAll();
        }
        return $order;
    }

    /**
     * Fetch a specific order by order number
     *
     * @param int $order_number The order number to fetch
     * @return stdClass A stdClass object representing the order
     */
    public function getByNumber($order_number)
    {
        $this->Record = $this->getOrders();
        return $this->Record->where('orders.order_number', '=', $order_number)->fetch();
    }

    /**
     * Fetches a list of all orders
     *
     * @param string $status The status of orders to fetch which can be one of, default null for all:
     *  - pending
     *  - accepted
     *  - fraud
     * @param int $page The page to return results for (optional, default 1)
     * @param string $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @param array $filters A list of order filters including: (optional)
     *
     *  - client_id The client ID on which to filter orders (optional)
     * @return mixed An array of objects or false if no results.
     */
    public function getList($status = null, $page = 1, array $order_by = ['order_number'=>'ASC'], $filters = [])
    {
        $this->Record = $this->getOrders($status, $filters);
        $this->Record->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage());

        return $this->Record->fetchAll();
    }

    /**
     * Return the total number of orders returned from OrderOrders::getList(),
     * useful in constructing pagination for the getList() method.
     *
     * @param string $status The status of orders to fetch which can be one of, default null for all:
     *  - pending
     *  - accepted
     *  - fraud
     * @param array $filters A list of order filters including: (optional)
     *
     *  - client_id The client ID on which to filter orders (optional)
     * @return int The total number of orders
     * @see OrderOrders::getList()
     */
    public function getListCount($status = null, $filters = [])
    {
        $this->Record = $this->getOrders($status, $filters);

        return $this->Record->numResults();
    }

    /**
     * Search orders
     *
     * @param string $query The value to search orders for
     * @param int $page The page number of results to fetch (optional, default 1)
     * @param array $order_by The sort=>$order options
     * @return array An array of tickets that match the search criteria
     */
    public function search($query, $page = 1, $order_by = ['date_added' => 'desc'])
    {
        $this->Record = $this->searchOrders($query);
        return $this->Record->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->
            fetchAll();
    }

    /**
     * Returns the total number of orders returned from OrderOrders::search(), useful
     * in constructing pagination
     *
     * @param string $query The value to search orders for
     * @see OrderOrders::search()
     */
    public function getSearchCount($query)
    {
        $this->Record = $this->searchOrders($query);
        return $this->Record->numResults();
    }

    /**
     * Partially constructs the query for searching orders
     *
     * @param string $query The value to search orders for
     * @return Record The partially constructed query Record object
     * @see OrderOrders::search(), OrderOrders::getSearchCount()
     */
    private function searchOrders($query)
    {
        // Fetch the orders
        $this->Record = $this->getOrders();

        $this->Record->open()->
                like('orders.order_number', '%' . $query . '%')->
            close();

        return $this->Record;
    }

    /**
     * Sets the status for the given set of order ID values, updates client
     * status appropriately (marking as fraud or active)
     *
     * @param array $vars An array of info containing:
     *  - order_id An array of order ID values
     *  - status The status to set for the order ID values:
     *      - accepted
     *      - pending
     *      - fraud
     *      - canceled
     */
    public function setStatus(array $vars)
    {
        $order_ids = [];
        foreach ((array)$vars['order_id'] as $id) {
            $order_ids[] = (int)$id;
        }

        // Update status of orders
        $this->Record->
            innerJoin('invoices', 'invoices.id', '=', 'orders.invoice_id', false)->
            innerJoin('clients', 'clients.id', '=', 'invoices.client_id', false)->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            where('client_groups.company_id', '=', Configure::get('Blesta.company_id'))->
            where('orders.id', 'in', $order_ids)->
            set('orders.status', $vars['status'])->
            update('orders');

        // Update status of services
        $service_status = $vars['status'] == 'accepted'
            ? 'pending'
            : ($vars['status'] == 'canceled' ? 'canceled' : 'in_review');
        $this->Record->
            innerJoin('order_services', 'order_services.service_id', '=', 'services.id', false)->
            innerJoin('orders', 'order_services.order_id', '=', 'orders.id', false)->
            innerJoin('clients', 'clients.id', '=', 'services.client_id', false)->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            where('client_groups.company_id', '=', Configure::get('Blesta.company_id'))->
            where('orders.id', 'in', $order_ids)->
            where('services.status', 'in', ['pending', 'in_review'])->
            set('services.status', $service_status)->
            update('services');

        // Update status of clients
        $client_status = null;
        if ($vars['status'] == 'accepted' || $vars['status'] == 'pending') {
            $client_status = 'active';
        } elseif ($vars['status'] == 'fraud') {
            $client_status = 'fraud';
        }

        if ($client_status) {
            $this->Record->
                innerJoin('invoices', 'invoices.client_id', '=', 'clients.id', false)->
                innerJoin('orders', 'orders.invoice_id', '=', 'invoices.id', false)->
                innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
                where('client_groups.company_id', '=', Configure::get('Blesta.company_id'))->
                where('orders.id', 'in', $order_ids)->
                set('clients.status', $client_status)->
                update('clients');
        }

        // Void all invoices for fraud and canceled orders
        if ($vars['status'] == 'fraud' || $vars['status'] == 'canceled') {
            if (!isset($this->Invoices)) {
                Loader::loadModels($this, ['Invoices']);
            }

            foreach ($order_ids as $order_id) {
                $order = $this->get($order_id);
                $this->Invoices->edit($order->invoice_id, ['status' => 'void']);
            }
        }
    }

    /**
     * Returns all possible order
     */
    public function getStatuses()
    {
        return [
            'pending' => Language::_('OrderOrders.getstatuses.pending', true),
            'accepted' => Language::_('OrderOrders.getstatuses.accepted', true),
            'fraud' => Language::_('OrderOrders.getstatuses.fraud', true),
            'canceled' => Language::_('OrderOrders.getstatuses.canceled', true)
        ];
    }

    /**
     * Fetches a partial Record object to fetch all orders of the given status
     * for the current company
     *
     * @param string $status The status of orders to fetch which can be one of, default null for all:
     *  - pending
     *  - accepted
     *  - fraud
     * @param array $filters A list of order filters including: (optional)
     *
     *  - client_id The client ID on which to filter orders (optional)
     * @return Record The partially constructed query Record object
     */
    private function getOrders($status = null, $filters = [])
    {
        $fields = ['orders.*', 'order_forms.label' => 'order_form_label',
            'order_forms.name' => 'order_form_name', 'invoices.client_id', 'invoices.total',
            'invoices.paid', 'invoices.currency', 'invoices.date_closed',
            'REPLACE(invoices.id_format, ?, invoices.id_value)' => 'invoice_id_code',
            'REPLACE(clients.id_format, ?, clients.id_value)' => 'client_id_code',
            'contacts.first_name'=>'client_first_name',
            'contacts.last_name'=>'client_last_name',
            'contacts.company'=>'client_company',
            'contacts.address1'=>'client_address1',
            'contacts.email'=>'client_email'
        ];

        $this->Record->select($fields)->
            appendValues(
                [
                    $this->replacement_keys['invoices']['ID_VALUE_TAG'],
                    $this->replacement_keys['clients']['ID_VALUE_TAG']
                ]
            )->
            from('orders')->
            innerJoin('invoices', 'invoices.id', '=', 'orders.invoice_id', false)->
            innerJoin('clients', 'clients.id', '=', 'invoices.client_id', false)->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            on('contacts.contact_type', '=', 'primary')->
            innerJoin('contacts', 'contacts.client_id', '=', 'clients.id', false)->
            leftJoin('order_forms', 'order_forms.id', '=', 'orders.order_form_id', false)->
            where('client_groups.company_id', '=', Configure::get('Blesta.company_id'));

        if ($status) {
            $this->Record->where('orders.status', '=', $status);
        }

        // Filter on client ID
        if (!empty($filters['client_id'])) {
            $this->Record->where('clients.id', '=', $filters['client_id']);
        }
        // Filter on client ID
        if (!empty($filters['order_number'])) {
            $this->Record->where('orders.order_number', 'LIKE', '%' . $filters['order_number'] . '%');
        }

        return $this->Record;
    }

    /**
     * Return the total number of orders placed by an specific client
     *
     * @param int $client_id The ID of the client to fetch the placed orders
     * @return int The total number of orders placed by the client
     */
    public function getOrdersByClientIdCount($client_id)
    {
        return $this->Record->select('orders.id')->
            from('orders')->
            innerJoin('order_services', 'order_services.order_id', '=', 'orders.id', false)->
            innerJoin('services', 'services.id', '=', 'order_services.service_id', false)->
            where('services.client_id', '=', $client_id)->
            group('order_services.order_id')->
            numResults();
    }

    /**
     * Add and invoice an order, by provisioning all services
     *
     * @param array $details An array of order details including:
     *  - client_id The ID of the client to add the order for
     *  - order_form_id The ID of the order form the order was placed under
     *  - currency The currency code the order was placed under
     *  - fraud_report Fraud details in text format, optional default null
     *  - fraud_status Fraud status ('allow', 'review', 'reject'), optional default null
     *  - status The status of the order ('pending', 'accepted', 'fraud') default 'pending'
     *  - coupon The coupon code used for the order, optional default null
     *  - ip_address The IP address of the customer that placed the order (optional, default null)
     * @param array $items A numerically indexed array of order items including:
     *  - parent_service_id The ID of the service this service is a child of (optional)
     *  - package_group_id The ID of the package group this service was added from (optional)
     *  - pricing_id The package pricing schedule ID for this service
     *  - module_row_id The module row to add the service under (optional, default module will decide)
     *  - addons An array of addon items each including:
     *      - package_group_id The ID of the package group this service was added from (optional)
     *      - pricing_id The package pricing schedule ID for this service
     *      - module_row_id The module row to add the service under (optional, default module will decide)
     *      - qty The quantity consumed by this service (optional, default 1)
     *      - * Any other service field data to pass to the module
     *  - qty The quantity consumed by this service (optional, default 1)
     *  - transfer True if the service is being transferred, false otherwise (optional, default false)
     *  - * Any other service field data to pass to the module
     * @return stdClass A stdClass object representing the order, void on error
     */
    public function add(array $details, array $items)
    {
        if (!isset($this->OrderForms)) {
            Loader::loadModels($this, ['Order.OrderForms']);
        }
        if (!isset($this->Services)) {
            Loader::loadModels($this, ['Services']);
        }
        if (!isset($this->Clients)) {
            Loader::loadModels($this, ['Clients']);
        }
        if (!isset($this->Coupons)) {
            Loader::loadModels($this, ['Coupons']);
        }
        if (!isset($this->Invoices)) {
            Loader::loadModels($this, ['Invoices']);
        }
        if (!isset($this->Emails)) {
            Loader::loadModels($this, ['Emails']);
        }
        if (!isset($this->MessengerManager)) {
            Loader::loadModels($this, ['MessengerManager']);
        }
        if (!isset($this->Form)) {
            Loader::loadHelpers($this, ['Form']);
        }

        if (!isset($details['status'])) {
            $details['status'] == 'pending';
        }

        $coupon_id = null;
        $packages = $this->getPackagesFromItems($items);

        if (isset($details['coupon'])) {
            $coupon = $this->Coupons->getForPackages($details['coupon'], null, $packages);

            if ($coupon && !$coupon->internal_use_only) {
                $coupon_id = $coupon->id;
            }
        }

        // Update client's default currency
        $this->Clients->setSetting($details['client_id'], 'default_currency', $details['currency']);

        // Get the order form
        $order_form = $this->OrderForms->get($details['order_form_id'] ?? null);
        if (!empty($order_form)) {
            $order_form->meta = $this->Form->collapseObjectArray(
                $order_form->meta,
                'value',
                'key'
            );
        }

        $service_ids = [];
        $domain_ids = [];
        $items_ids = [];
        $service_transfers = [];

        foreach ($items as $index => $item) {
            $service_id = $this->addService($details, $item, $packages, $coupon_id);

            // Process service
            if ($service_id) {
                // Check if the service is a domain
                if ($item['group_id'] == ($order_form->meta['domain_group'] ?? 0)) {
                    $domain_ids[$item['domain'] ?? $service_id] = $service_id;
                } else {
                    $service_ids[$item['domain'] ?? $service_id] = $service_id;
                }

                $items_ids[$service_id] = $index;

                if (isset($item['transfer'])) {
                    $service_transfers[] = $service_id;
                }

                // Add addons
                if (isset($item['addons'])) {
                    foreach ($item['addons'] as $addon) {
                        $addon['parent_service_id'] = $service_id;
                        $addon_service_id = $this->addService($details, $addon, $packages, $coupon_id);

                        if ($addon_service_id) {
                            $service_ids[] = $addon_service_id;
                        }
                    }
                }
            }
        }

        // Process free domains
        foreach ($domain_ids as $domain => $service_id) {
            if (!array_key_exists($domain, $service_ids)) {
                continue;
            }

            // Update domain service
            if ($this->eligibleFreeDomain(
                $items[$items_ids[$service_ids[$domain]]],
                $items[$items_ids[$domain_ids[$domain]]],
                $order_form
            )) {
                $service_edit = [
                    'override_price' => 0,
                    'override_currency' => $details['currency'],
                    'parent_service_id' => $service_ids[$domain]
                ];
                $this->Services->edit($service_id, $service_edit);

                if (($errors = $this->Services->errors())) {
                    $this->Input->setErrors($errors);

                    return;
                }
            }
        }

        // Add free domains to the invoice
        foreach ($domain_ids as $service_id) {
            $service_ids[] = $service_id;
        }

        if (empty($service_ids)) {
            if (($errors = $this->Services->errors())) {
                $this->Input->setErrors($errors);
            }
            return;
        }

        // Add an invoice from the service IDs
        $service_ids = array_values($service_ids);
        $invoice_id = $this->Invoices->createFromServices(
            $details['client_id'],
            $service_ids,
            $details['currency'],
            date('c'),
            true,
            false,
            $service_transfers
        );

        // Can not proceed if create invoice failed
        if (($errors = $this->Invoices->errors())) {
            $this->Input->setErrors($errors);
            return;
        }

        // Restore pricing for free domains, if they are free only for the first term
        if (($order_form->meta['first_term'] ?? '0') == '1') {
            foreach ($domain_ids as $service_id) {
                $service_edit = [
                    'override_price' => null,
                    'override_currency' => null
                ];
                $this->Services->edit($service_id, $service_edit, true);
            }
        }

        $order_form = $this->OrderForms->get($details['order_form_id']);
        $abandoned_order_handling = $order_form->abandoned_cart_first !== null
            || $order_form->abandoned_cart_second !== null
            || $order_form->abandoned_cart_third !== null
            || $order_form->abandoned_cart_cancellation !== null;

        // Record this order
        $vars = [
            'order_number' => $this->getOrderNumber(),
            'order_form_id' => $details['order_form_id'],
            'invoice_id' => $invoice_id,
            'fraud_report' => $details['fraud_report'] ?? null,
            'fraud_status' => $details['fraud_status'] ?? null,
            'status' => $details['status'],
            'ip_address' => $details['ip_address'] ?? null,
            'abandoned_notice' => $abandoned_order_handling ? 'unsent' : 'none',
            'date_added' => $this->dateToUtc(date('c'))
        ];
        $this->Record->insert('orders', $vars);
        $order_id = $this->Record->lastInsertId();

        // Record all services in this order
        foreach ($service_ids as $service_id) {
            $this->Record->insert('order_services', ['order_id' => $order_id, 'service_id' => $service_id]);
        }

        $order = $this->get($order_id);

        if ($order->fraud_status != 'reject') {
            // Fetch order services
            $services = [];
            foreach ($order->services as $service) {
                $temp_service = $this->Services->get($service->service_id);

                // Attach coupon object to each service that has a coupon ID
                if (!empty($temp_service->coupon_id)) {
                    $temp_service->coupon = $this->Coupons->get($temp_service->coupon_id);
                }

                $services[] = $temp_service;
            }

            // Send email notifications to staff about the order
            $tags = [
                'order' => $order,
                'invoice' => $this->Invoices->get($invoice_id),
                'services' => $services,
                'client' => $this->Clients->get($details['client_id'])
            ];

            // Fetch all staff that should receive the email notification
            if (!isset($this->OrderStaffSettings)) {
                Loader::loadModels($this, ['Order.OrderStaffSettings']);
            }

            $staff_email = $this->OrderStaffSettings->getStaffWithSetting(
                Configure::get('Blesta.company_id'),
                'email_notice',
                'always'
            );

            // Fetch staff to notify when an order requires manual approval
            if ($order->fraud_status == 'review') {
                $staff_email = array_merge(
                    $staff_email,
                    $this->OrderStaffSettings->getStaffWithSetting(
                        Configure::get('Blesta.company_id'),
                        'email_notice',
                        'manual'
                    )
                );
            }

            $to_addresses = [];
            foreach ($staff_email as $staff) {
                $to_addresses[] = $staff->email;
            }

            // Send to staff email
            $this->Emails->send('Order.received', Configure::get('Blesta.company_id'), null, $to_addresses, $tags);

            // Fetch all staff that should receive the mobile email notification
            $staff_email = $this->OrderStaffSettings->getStaffWithSetting(
                Configure::get('Blesta.company_id'),
                'mobile_notice',
                'always'
            );

            // Fetch staff to notify when an order requires manual approval
            if ($order->fraud_status == 'review') {
                $staff_email = array_merge(
                    $staff_email,
                    $this->OrderStaffSettings->getStaffWithSetting(
                        Configure::get('Blesta.company_id'),
                        'mobile_notice',
                        'manual'
                    )
                );
            }

            $to_addresses = [];
            foreach ($staff_email as $staff) {
                if ($staff->email_mobile) {
                    $to_addresses[] = $staff->email_mobile;
                }
            }

            // Send to staff mobile email
            $this->Emails->send(
                'Order.received_mobile',
                Configure::get('Blesta.company_id'),
                null,
                $to_addresses,
                $tags
            );

            $staff_message = $this->OrderStaffSettings->getStaffWithSetting(
                Configure::get('Blesta.company_id'),
                'messenger_notice',
                'always'
            );

            // Fetch staff to notify when an order requires manual approval
            if ($order->fraud_status == 'review') {
                $staff_message = array_merge(
                    $staff_message,
                    $this->OrderStaffSettings->getStaffWithSetting(
                        Configure::get('Blesta.company_id'),
                        'messenger_notice',
                        'manual'
                    )
                );
            }

            $staff_user_ids = [];
            foreach ($staff_message as $staff) {
                $staff_user_ids[] = $staff->user_id;
            }

            $this->MessengerManager->send('Order.received_staff', $tags, $staff_user_ids);
        }

        return $order;
    }

    /**
     * Validates for the given service item if the ordered domain is eligible for free
     *
     * @param array $service_item The array representing the service to validate against it
     * @param array $domain_item The array representing the domain to validate
     * @param stdClass $order_form An object representing the order form
     * @return bool True if the domain is eligible for free for the given service, false otherwise
     */
    public function eligibleFreeDomain(array $service_item, array $domain_item, $order_form)
    {
        Loader::loadHelpers($this, ['Form']);
        if (!empty($order_form->meta) && !is_string(array_key_first($order_form->meta))) {
            $order_form->meta = $this->Form->collapseObjectArray(
                $order_form->meta ?? [],
                'value',
                'key'
            );
        }

        // Validate free domains option is enabled
        if (($order_form->meta['free_domain'] ?? '0') == '0') {
            return false;
        }

        // Validate if the service item belongs to the approved package groups
        if (!in_array($service_item['group_id'], $order_form->meta['package_groups'])) {
            return false;
        }

        // Get the pricing from the service and domain
        $service_pricing = $this->Record->select(['pricings.*'])->
            from('package_pricing')->
            innerJoin('pricings', 'pricings.id', '=', 'package_pricing.pricing_id', false)->
            where('package_pricing.id', '=', $service_item['pricing_id'])->
            fetch();
        $domain_pricing = $this->Record->select(['pricings.*'])->
            from('package_pricing')->
            innerJoin('pricings', 'pricings.id', '=', 'package_pricing.pricing_id', false)->
            where('package_pricing.id', '=', $domain_item['pricing_id'])->
            fetch();

        // Return false if one of the pricing does not exist
        if (empty($service_pricing) || empty($domain_pricing)) {
            return false;
        }

        // If the term is limited to the lowest amount of years, verify if the provided pricing matches
        if (($order_form->meta['limit_term'] ?? '0') == '1') {
            $lowest_pricing = $this->Record->select(['pricings.*'])->
                from('package_pricing')->
                innerJoin('pricings', 'pricings.id', '=', 'package_pricing.pricing_id', false)->
                where('package_pricing.package_id', '=', $domain_item['package']->id)->
                where('pricings.currency', '=', $domain_pricing->currency)->
                where('pricings.period', '=', 'year')->
                order(['pricings.term' => 'ASC'])->
                fetch();

            if (empty($lowest_pricing) || $lowest_pricing->id !== $domain_pricing->id) {
                return false;
            }
        }

        // Validate if the TLD is available for free
        $tld = strstr($domain_item['domain'] ?? '', '.');
        if (!in_array($tld, $order_form->meta['tlds'])) {
            return false;
        }

        // Validate the service is not one-time
        if ($service_pricing->period == 'onetime') {
            return false;
        }

        return strtotime('+ ' . $order_form->meta['term'] . ' ' . $order_form->meta['period'])
            <= strtotime('+ ' . $service_pricing->term . ' ' . $service_pricing->period);
    }

    /**
     * @param array $details An array of order details including:
     *  - client_id The ID of the client to add the order for
     *  - order_form_id The ID of the order form the order was placed under
     *  - currency The currency code the order was placed under
     *  - fraud_report Fraud details in text format, optional default null
     *  - status The status of the order ('pending', 'accepted', 'fraud') default 'pending'
     *  - coupon The coupon code used for the order, optional default null
     *  - ip_address The IP address of the customer that placed the order (optional, default null)
     * @param array $item An array of item info including:
     *  - parent_service_id The ID of the service this service is a child of (optional)
     *  - package_group_id The ID of the package group this service was added from (optional)
     *  - pricing_id The package pricing schedule ID for this service
     *  - module_row_id The module row to add the service under (optional, default module will decide)
     *  - addons An array of addon items each including:
     *      - package_group_id The ID of the package group this service was added from (optional)
     *      - pricing_id The package pricing schedule ID for this service
     *      - module_row_id The module row to add the service under (optional, default module will decide)
     *      - qty The quanity consumed by this service (optional, default 1)
     *      - * Any other service field data to pass to the module
     *  - qty The quanity consumed by this service (optional, default 1)
     *  - * Any other service field data to pass to the module
     * @param array $packages A numerically indexed array of packages ordered along with
     *  this service to determine if the given coupon may be applied
     * @param int $coupon_id The ID of the coupon used
     * @return int The service ID of the service added, void on error
     */
    private function addService(array $details, array $item, array $packages, $coupon_id = null)
    {
        // Unset any fields that may adversely affect the Services::add() call
        unset($item['status'], $item['date_added'], $item['date_renews'],
            $item['date_last_renewed'], $item['date_suspended'],
            $item['date_canceled'], $item['use_module'],
            $item['override_price'], $item['override_currency']);

        $item['coupon_id'] = $coupon_id;
        $item['status'] = ($details['status'] == 'pending' ? 'in_review' : 'pending');
        $item['client_id'] = $details['client_id'];
        $service_id = $this->Services->add($item, $packages, false);

        // Set any errors encountered
        if (($errors = $this->Services->errors())) {
            $this->Input->setErrors($errors);
        }

        return $service_id;
    }

    /**
     * If manual review is not required by the order form, marks all paid orders
     * as accepted for all active clients for this company
     *
     * @see OrderOrders::setStatus()
     */
    public function acceptPaidOrders()
    {
        Loader::loadModels($this, ['Order.OrderSettings']);

        $hold_unverified_orders = $this->OrderSettings->getSetting(
            Configure::get('Blesta.company_id'),
            'hold_unverified_orders'
        );

        // Fetch all orders that are paid
        $paid_orders = $this->getOrders('pending');

        if (isset($hold_unverified_orders->value) && $hold_unverified_orders->value == 'true') {
            $paid_orders->leftJoin('email_verifications', 'email_verifications.contact_id', '=', 'contacts.id', false);
        }

        $paid_orders->where('invoices.date_closed', '!=', null)->
            where('clients.status', '=', 'active')->
            open()->
                where('fraud_status', '=', null)->
                orWhere('fraud_status', '=', 'allow')->
            close()->
            where('order_forms.manual_review', '=', '0');

        if (isset($hold_unverified_orders->value) && $hold_unverified_orders->value == 'true') {
            $paid_orders->open()->
                where('email_verifications.verified', '=', null)->
                orWhere('email_verifications.verified', '=', '1')->
            close();
        }

        $paid_orders = $paid_orders->fetchAll();

        foreach ($paid_orders as $order) {
            $this->setStatus(['order_id' => $order->id, 'status' => 'accepted']);
        }
    }

    /**
     * Fetches all packages IDs ordered for the given pricing IDs
     *
     * @param array $items An array of items including:
     *  - pricing_id The ID of the package pricing
     * @return array An array of package IDs derived from the given pricing_id values
     */
    public function getPackagesFromItems(array $items)
    {
        $pricing_ids = [];
        $package_ids = [];
        foreach ($items as $item) {
            $pricing_ids[] = $item['pricing_id'];
        }

        if (!empty($pricing_ids)) {
            $packages = $this->Record->select(['package_pricing.package_id', 'package_pricing.id'])->
                from('package_pricing')->
                where('package_pricing.id', 'in', $pricing_ids)->
                group(['package_pricing.package_id'])->fetchAll();

            foreach ($packages as $pack) {
                $package_ids[$pack->package_id] = $pack->id;
            }
        }

        return $package_ids;
    }

    /**
     * Generates a random order number
     *
     * @param string $prefix A prefix to set for the order number
     * @return string A random order number
     */
    private function getOrderNumber($prefix = null)
    {
        $number = null;
        $exists = true;

        while ($exists) {
            $number = uniqid($prefix ?? '');
            $exists = $this->Record->select(['orders.id'])
                ->from('orders')
                ->where('orders.order_number', '=', $number)
                ->fetch();
        }

        return $number;
    }

    /**
     * Deletes any orders not associated with a valid invoice or service
     */
    public function deleteHangingOrders()
    {
        $hanging_orders = $this->Record->select('orders.id')
            ->from('orders')
            ->leftJoin('invoices', 'invoices.id', '=', 'orders.invoice_id', false)
            ->leftJoin('order_services', 'order_services.order_id', '=', 'orders.id', false)
            ->leftJoin('services', 'services.id', '=', 'order_services.service_id', false)
            ->where('invoices.id', '=', null)
            ->open()
                ->orWhere('order_services.order_id', '!=', null)
                ->where('services.id', '=', null)
            ->close()
            ->fetchAll();

        foreach ($hanging_orders as $hanging_order) {
            $this->delete($hanging_order->id);
        }
    }


    /**
     * Deletes the given order
     *
     * @param int $order_id The ID of the order to delete
     */
    public function delete($order_id)
    {
        $this->Record->from('orders')
            ->leftJoin('order_services', 'order_services.order_id', '=', 'orders.id', false)
            ->where('orders.id', '=', $order_id)
            ->delete(['orders.*','order_services.*']);
    }

    /**
     * Cancels the given order
     *
     * @param int $order_id The ID of the order to delete
     */
    public function cancel($order_id)
    {
        if (!isset($this->Invoices)) {
            Loader::loadModels($this, ['Invoices']);
        }
        if (!isset($this->Services)) {
            Loader::loadModels($this, ['Services']);
        }

        $order = $this->get($order_id);

        // Delete services
        $invoice_lines = $this->Invoices->getLineItems($order->invoice_id);
        foreach ($invoice_lines as $line) {
            $this->Services->delete($line->service_id);
        }

        // Void invoice
        $this->Invoices->edit($order->invoice_id, ['status' => 'void']);

        $this->Record->where('orders.id', '=', $order_id)
            ->update('orders', ['status' => 'canceled']);
    }

    /**
     * Returns a list of all abandoned orders
     *
     * @param int $order_form_id The ID of the order form
     * @param int $elapsed_time The amount of time in hours that elapsed since the cart was abandoned
     * @return array A list of abandoned orders
     */
    public function getAbandoned(int $order_form_id, int $elapsed_time = 24) : array
    {
        $date = $this->dateToUtc(date('c', strtotime('-' . $elapsed_time . ' hours')), 'Y-m-d H:i:s');

        $abandoned_orders = $this->Record->select(['orders.*'])
            ->from('orders')
            ->innerJoin('invoices', 'invoices.id', '=', 'orders.invoice_id', false)
            ->leftJoin('transaction_applied', 'transaction_applied.invoice_id', '=', 'invoices.id', false)
            ->where('invoices.paid', '<', 'invoices.total', false)
            ->where('orders.status', '=', 'accepted')
            ->where('orders.order_form_id', '=', $order_form_id)
            ->where('orders.date_added', '<=', $date)
            ->where('orders.abandoned_notice', '!=', 'none')
            ->where('transaction_applied.transaction_id', '=', null)
            ->fetchAll();

        return $abandoned_orders;
    }

    /**
     * Sends an abandoned order reminder notice for a given order
     *
     * @param int $order_id The ID of the order for which to send the reminder
     * @param string $notice Whether is the 'first', 'second' or 'third' notice
     */
    public function sendAbandonedReminder(int $order_id, string $notice) : void
    {
        if (!isset($this->Html)) {
            Loader::loadHelpers($this, ['Html']);
        }

        if (!isset($this->Invoices)) {
            Loader::loadModels($this, ['Invoices']);
        }

        if (!isset($this->Clients)) {
            Loader::loadModels($this, ['Clients']);
        }

        if (!isset($this->Emails)) {
            Loader::loadModels($this, ['Emails']);
        }

        if (!isset($this->Settings)) {
            Loader::loadModels($this, ['Settings']);
        }

        $accepted_notices = ['first', 'second', 'third'];
        if (!in_array($notice, $accepted_notices)) {
            return;
        }

        $order = $this->get($order_id);
        $invoice = $this->Invoices->get($order->invoice_id ?? null);

        // Build payment url
        if ($invoice) {
            // Get client
            $client = $this->Clients->get($invoice->client_id);

            // Set a hash for the payment URL
            $hash = $this->Invoices->createPayHash($client->id, $invoice->id);

            // Set web dir
            $webdir = WEBDIR;
            if (empty($_SERVER['REQUEST_URI'])) {
                $root_web = $this->Settings->getSetting('root_web_dir');
                if ($root_web) {
                    $webdir = str_replace(
                        DS,
                        '/',
                        str_replace(rtrim(strtolower($root_web->value), DS), '', strtolower(ROOTWEBDIR))
                    );

                    if (!HTACCESS) {
                        $webdir .= 'index.php/';
                    }
                }
            }

            // Set client uri
            $client_uri = $webdir . Configure::get('Route.client') . '/';

            // Get the company hostname
            $hostname = isset(Configure::get('Blesta.company')->hostname) ? Configure::get('Blesta.company')->hostname : '';

            $payment_url = $this->Html->safe(
                $hostname . $client_uri . 'pay/method/' . $invoice->id . '/?sid='
                . rawurlencode($this->Clients->systemEncrypt('c=' . $client->id . '|h=' . substr($hash, -16)))
            );
        } else {
            return;
        }

        // Build tags array
        $tags = [
            'order' => $order,
            'invoice' => $invoice,
            'client' => $client,
            'services' => $order->services ?? [],
            'payment_url' => $payment_url
        ];

        // Get client email address
        $to_addresses = [$client->email ?? ''];

        if ($order->abandoned_notice == $notice) {
            return;
        }

        // Send notice
        $notice_order = ['unsent' => 0, 'first' => 1, 'second' => 2, 'third' => 3];
        if (($notice_order[$order->abandoned_notice] ?? 0) < ($notice_order[$notice] ?? 0)) {
            $this->Emails->send(
                'Order.abandoned_cart_' . $notice,
                Configure::get('Blesta.company_id'),
                null,
                $to_addresses,
                $tags,
                null,
                null,
                null,
                ['to_client_id' => $client->id]
            );

            $this->Record->where('orders.id', '=', $order_id)->update('orders', ['abandoned_notice' => $notice]);
        }
    }

    /**
     * Sends a reminder for abandoned orders
     */
    public function notifyAbandonedOrders()
    {
        Loader::loadModels($this, ['Companies', 'Order.OrderForms', 'Services', 'Invoices', 'Clients']);
        Loader::loadHelpers($this, ['Form']);

        $companies = $this->Companies->getAll();
        foreach ($companies as $company) {
            $order_forms = $this->OrderForms->getAll($company->id, 'active');
            foreach ($order_forms as $order_form) {
                // Send email notices
                $this->sendAbandonedOrderReminders($order_form);

                // Skip order cancellation if it isn't configured
                if ($order_form->abandoned_cart_cancellation === null) {
                    continue;
                }

                // Cancel abandoned orders
                $abandoned_orders = $this->getAbandoned($order_form->id, $order_form->abandoned_cart_cancellation);
                foreach ($abandoned_orders as $abandoned_order) {
                    $order = $this->get($abandoned_order->id);

                    // Cancel abandoned order
                    $this->cancel($order->id);

                    // Check if the client has other services
                    if ($order_form->inactive_after_cancellation == '1') {
                        $other_services = 0;
                        $client_services = $this->Form->collapseObjectArray(
                            $this->Services->getAllByClient($order->client_id, 'all'),
                            'id',
                            'id'
                        );
                        $order_services = $this->Form->collapseObjectArray($order->services, 'service_id', 'service_id');

                        foreach ($client_services as $client_service) {
                            if (!in_array($client_service, $order_services)) {
                                $other_services++;
                            }
                        }

                        if ($other_services == 0) {
                            // Mark client as inactive
                            $this->Clients->edit($order->client_id, ['status' => 'inactive']);
                        }
                    }
                }
            }
        }
    }

    /**
     * Send abandoned order notices for the given order form if configured
     *
     * @param stdClass $order_form The order form for which to send abandoned order notices
     */
    private function sendAbandonedOrderReminders($order_form)
    {
        $notices = ['first', 'second', 'third'];
        foreach ($notices as $notice) {
            if ($order_form->{'abandoned_cart_' . $notice} !== null) {
                $abandoned_orders = $this->getAbandoned($order_form->id, $order_form->{'abandoned_cart_' . $notice});
                foreach ($abandoned_orders as $abandoned_order) {
                    $this->sendAbandonedReminder($abandoned_order->id, $notice);
                }
            }
        }
    }
}
