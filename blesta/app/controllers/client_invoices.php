<?php

use Blesta\Core\Util\Filters\InvoiceFilters;

/**
 * Client portal invoices controller
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientInvoices extends ClientController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        parent::preAction();

        $this->uses(['Clients', 'Invoices']);
    }

    /**
     * List invoices
     */
    public function index()
    {
        // Get current page of results
        $status = ((isset($this->get[0]) && ($this->get[0] == 'closed')) ? $this->get[0] : 'open');
        $page = (isset($this->get[1]) ? (int) $this->get[1] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_due');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        // Set filters from post input
        $post_filters = [];
        if (isset($this->post['filters'])) {
            $post_filters = $this->post['filters'];
            unset($this->post['filters']);

            foreach($post_filters as $filter => $value) {
                if (empty($value)) {
                    unset($post_filters[$filter]);
                }
            }
        }

        // Get the invoices
        $invoices = $this->Invoices->getList($this->client->id, $status, $page, [$sort => $order], $post_filters);
        $total_results = $this->Invoices->getListCount($this->client->id, $status, $post_filters);

        // Set the number of invoices of each type
        $status_count = [
            'open' => $this->Invoices->getStatusCount($this->client->id, 'open', $post_filters),
            'closed' => $this->Invoices->getStatusCount($this->client->id, 'closed', $post_filters)
        ];

        // Set the input field filters for the widget
        $invoice_filters = new InvoiceFilters();
        $this->set(
            'filters',
            $invoice_filters->getFilters(
                ['language' => Configure::get('Blesta.language'), 'company_id' => Configure::get('Blesta.company_id')],
                $post_filters
            )
        );

        $this->set('filter_vars', $post_filters);
        $this->set('status', $status);
        $this->set('client', $this->client);
        $this->set('invoices', $invoices);
        $this->set('status_count', $status_count);
        $this->set('widget_state', isset($this->widgets_state['invoices']) ? $this->widgets_state['invoices'] : null);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->structure->set(
            'page_title',
            Language::_('ClientInvoices.index.page_title', true, $this->client->id_code)
        );

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination_client'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'invoices/index/' . $status . '/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(
                isset($this->get['whole_widget']) ? null : (isset($this->get[1]) || isset($this->get['sort']))
            );
        }
    }

    /**
     * AJAX request for all transactions an invoice has applied
     */
    public function applied()
    {
        $this->uses(['Transactions']);

        $invoice = $this->Invoices->get((int) $this->get[0]);

        // Ensure the invoice belongs to the client and this is an ajax request
        if (!$this->isAjax() || !$invoice || $invoice->client_id != $this->client->id) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $vars = [
            'client' => $this->client,
            'applied' => $this->Transactions->getApplied(null, $this->get[0]),
            // Holds the name of all of the transaction types
            'transaction_types' => $this->Transactions->transactionTypeNames()
        ];

        // Send the template
        echo $this->partial('client_invoices_applied', $vars);

        // Render without layout
        return false;
    }

    /**
     * Streams the given invoice to the browser
     */
    public function view()
    {
        // Ensure we have a invoice to load, and that it belongs to this client
        if (!isset($this->get[0])
            || !($invoice = $this->Invoices->get((int) $this->get[0]))
            || ($invoice->client_id != $this->client->id)
        ) {
            $this->redirect($this->base_uri);
        }

        $this->components(['InvoiceDelivery']);
        $this->InvoiceDelivery->downloadInvoices([$invoice->id]);
        exit;
    }
}
