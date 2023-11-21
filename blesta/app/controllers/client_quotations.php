<?php

use Blesta\Core\Util\Filters\QuotationFilters;

/**
 * Client portal quotations controller
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientQuotations extends ClientController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        parent::preAction();

        $this->uses(['Clients', 'Quotations']);
    }

    /**
     * List quotations
     */
    public function index()
    {
        // Get current page of results
        $status = ($this->get[0] ?? 'pending');
        $page = (isset($this->get[1]) ? (int) $this->get[1] : 1);
        $sort = ($this->get['sort'] ?? 'date_expires');
        $order = ($this->get['order'] ?? 'desc');

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

        // Get the quotations
        if ($status == 'approved') {
            $quotations = array_merge(
                $this->Quotations->getList($this->client->id, $status, $page, [$sort => $order], $post_filters),
                $this->Quotations->getList($this->client->id, 'invoiced', $page, [$sort => $order], $post_filters)
            );
            $total_results = $this->Quotations->getListCount($this->client->id, $status, $post_filters)
                + $this->Quotations->getListCount($this->client->id, 'invoiced', $post_filters);

        } else {
            $quotations = $this->Quotations->getList($this->client->id, $status, $page, [$sort => $order], $post_filters);
            $total_results = $this->Quotations->getListCount($this->client->id, $status, $post_filters);
        }

        // Set the number of quotations of each type
        $status_count = [
            'pending' => $this->Quotations->getStatusCount($this->client->id, 'pending', $post_filters),
            'approved' =>
                $this->Quotations->getStatusCount($this->client->id, 'approved', $post_filters)
                + $this->Quotations->getStatusCount($this->client->id, 'invoiced', $post_filters),
            'expired' => $this->Quotations->getStatusCount($this->client->id, 'expired', $post_filters)
        ];

        // Set the input field filters for the widget
        $quotation_filters = new QuotationFilters();
        $this->set(
            'filters',
            $quotation_filters->getFilters(
                ['language' => Configure::get('Blesta.language'), 'company_id' => Configure::get('Blesta.company_id')],
                $post_filters
            )
        );

        $this->set('filter_vars', $post_filters);
        $this->set('status', $status);
        $this->set('client', $this->client);
        $this->set('quotations', $quotations);
        $this->set('status_count', $status_count);
        $this->set('widget_state', $this->widgets_state['quotations'] ?? null);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->structure->set(
            'page_title',
            Language::_('ClientQuotations.index.page_title', true, $this->client->id_code)
        );

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination_client'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'quotations/index/' . $status . '/[p]/',
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
     * AJAX request for all invoices associated to a quotation
     */
    public function invoices()
    {
        $this->uses(['Quotations']);

        $quotation = $this->Quotations->get((int) $this->get[0]);

        // Ensure the quotation belongs to the client and this is an ajax request
        if (!$this->isAjax() || !$quotation || $quotation->client_id != $this->client->id) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $vars = [
            'client' => $this->client,
            'invoices' => $this->Quotations->getInvoices($this->get[0])
        ];

        // Send the template
        echo $this->partial('client_quotations_invoices', $vars);

        // Render without layout
        return false;
    }

    /**
     * Streams the given quotation to the browser
     */
    public function view()
    {
        $this->uses(['Quotations']);
        $this->components(['QuotationDelivery']);

        // Ensure we have a quotation to load, and that it belongs to this client
        if (!isset($this->get[0])
            || !($quotation = $this->Quotations->get((int) $this->get[0]))
            || ($quotation->client_id != $this->client->id)
        ) {
            $this->redirect($this->base_uri);
        }

        $this->components(['InvoiceDelivery']);
        $this->QuotationDelivery->downloadQuotations([$quotation->id]);
        exit;
    }

    /**
     * Approve quotation
     */
    public function approve()
    {
        $this->uses(['Quotations', 'Users']);

        // Ensure we have a quotation that belongs to the client and is not currently canceled or suspended
        if (!($quotation = $this->Quotations->get((int) $this->get[0]))
            || $quotation->client_id != $this->client->id
            || $quotation->status != 'pending'
        ) {
            if ($this->isAjax()) {
                exit();
            }
            $this->redirect($this->base_uri);
        }

        if (!empty($this->post)) {
            // Verify that client's password is correct, set $errors otherwise
            $user = $this->Users->get($this->Session->read('blesta_id'));
            $username = ($user ? $user->username : '');

            if ($this->Users->auth($username, ['password' => $this->post['password']])) {
                // Approve the quotation
                $this->Quotations->updateStatus($quotation->id, 'approved');
            } else {
                $errors = ['password' => ['mismatch' => Language::_('ClientQuotations.!error.password_mismatch', true)]];
            }

            if (!empty($errors) || ($errors = $this->Quotations->errors())) {
                $this->flashMessage('error', $errors);
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_(
                        'ClientQuotations.!success.approved_quotation',
                        true
                    )
                );
            }

            $this->redirect($this->base_uri . 'quotations/index/approved/');
        }

        echo $this->view->fetch('client_quotations_approve');
        return false;
    }
}
