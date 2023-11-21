<?php
/**
 * Orders History controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order.controllers
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Orders extends ClientController
{
    /**
     * Pre Action
     */
    public function preAction()
    {
        $this->structure->setDefaultView(APPDIR);
        parent::preAction();

        // Auto load language for the controller
        Language::loadLang(
            [Loader::fromCamelCase(get_class($this)), 'order_plugin'],
            null,
            dirname(__FILE__) . DS . 'language' . DS
        );

        // Override default view directory
        $this->view->view = 'default';
        $this->orig_structure_view = $this->structure->view;
        $this->structure->view = 'default';

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);

        $this->uses([
            'Order.OrderOrders',
            'Clients',
            'Invoices',
            'Transactions',
            'Services',
            'Packages'
        ]);

        Language::loadLang('orders', null, PLUGINDIR . 'order' . DS . 'language' . DS);
    }

    /**
     * Displays a list of all placed orders for a given client
     */
    public function index()
    {
        // Redirect if the client does not exist
        if (empty($this->client)) {
            $this->redirect($this->base_uri . 'order/');
        }

        // Set current page of results
        $sort_options = ['order_number', 'status', 'invoice_id_code', 'total', 'paid', 'date_added'];
        $order_options = ['desc', 'asc'];
        $page = (isset($this->get[0]) ? (int)$this->get[0] : 1);
        $sort = (isset($this->get['sort']) && in_array($this->get['sort'], $sort_options)
            ? $this->get['sort']
            : 'date_added'
        );
        $order = (isset($this->get['order']) && in_array($this->get['order'], $order_options)
            ? $this->get['order']
            : 'desc'
        );

        // Retrieve all orders
        $orders = $this->OrderOrders->getList(null, $page, [$sort => $order], ['client_id' => $this->client->id]);
        $total_results = $this->OrderOrders->getListCount(null, ['client_id' => $this->client->id]);

        // Get order statuses
        $statuses = $this->OrderOrders->getStatuses();

        $this->set('orders', $orders);
        $this->set('statuses', $statuses);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        // Set pagination parameters, set group if available
        $params = ['sort' => $sort, 'order' => $order];

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination_client'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'order/orders/index/[p]/',
                'params' => $params
            ]
        );
        $this->setPagination($this->get, $settings);

        // Render the request if ajax
        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));
    }

    /**
     * Displays the detailed information of a specific order
     */
    public function view()
    {
        // Get order or redirect if not given
        if (!($order = $this->OrderOrders->get($this->get[0])) || ($order->client_id !== $this->client->id)) {
            $this->redirect($this->base_uri . 'order/orders/');
        }

        // Get the invoice related to the order
        $invoice = $this->Invoices->get($order->invoice_id);
        $transactions = $this->Transactions->getApplied(null, $order->invoice_id);

        // Get the services belonging to the order
        $services = [];

        foreach ($invoice->line_items as $line_item) {
            if (!empty($line_item->service_id)) {
                $service = $this->Services->get($line_item->service_id);
                if ($service) {
                    $services[$line_item->service_id] = $service;
                    $services[$line_item->service_id]->renewal_price = $this->Services->getRenewalPrice(
                        $line_item->service_id
                    );
                }
            }
        }

        // Set language for periods
        $periods = $this->Packages->getPricingPeriods();
        foreach ($this->Packages->getPricingPeriods(true) as $period => $lang) {
            $periods[$period . '_plural'] = $lang;
        }

        $this->set('order', $order);
        $this->set('invoice', $invoice);
        $this->set('transactions', $transactions);
        $this->set('transaction_types', $this->Transactions->transactionTypeNames());
        $this->set('transaction_status', $this->Transactions->transactionStatusNames());
        $this->set('services', $services);
        $this->set('periods', $periods);
    }

    /**
     * Cancels an unpaid order
     */
    public function cancel()
    {
        // Get order or redirect if not given
        if (
            !($order = $this->OrderOrders->get($this->get[0]))
            || ($order->client_id !== $this->client->id)
            || ($order->paid != 0)
        ) {
            $this->redirect($this->base_uri . 'order/orders/');
        }

        // Cancel the order
        $this->OrderOrders->cancel($order->id);

        if (($errors = $this->OrderOrders->errors())) {
            $this->flashMessage(
                'error',
                $errors,
                null,
                false
            );
        } else {
            $this->flashMessage(
                'message',
                Language::_('Orders.!success.order_canceled', true),
                null,
                false
            );
        }
        $this->redirect($this->base_uri . 'order/orders/');
    }
}