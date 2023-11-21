<?php

use Blesta\Core\Util\Filters\TransactionFilters;

/**
 * Client portal transactions controller
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientTransactions extends ClientController
{
    /**
     * Pre Action
     */
    public function preAction()
    {
        parent::preAction();

        // Load models, language
        $this->uses(['Clients', 'Transactions']);
    }

    /**
     * List transactions
     */
    public function index()
    {
        $status = (isset($this->get[0]) ? $this->get[0] : 'approved');
        $page = (isset($this->get[1]) ? (int) $this->get[1] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_added');
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

        // Set the number of transactions of each type
        $status_count = [
            'approved' => $this->Transactions->getStatusCount($this->client->id, 'approved', $post_filters),
            'declined' => $this->Transactions->getStatusCount($this->client->id, 'declined', $post_filters),
            'void' => $this->Transactions->getStatusCount($this->client->id, 'void', $post_filters),
            'error' => $this->Transactions->getStatusCount($this->client->id, 'error', $post_filters),
            'pending' => $this->Transactions->getStatusCount($this->client->id, 'pending', $post_filters),
            'refunded' => $this->Transactions->getStatusCount($this->client->id, 'refunded', $post_filters),
            'returned' => $this->Transactions->getStatusCount($this->client->id, 'returned', $post_filters)
        ];

        // Get transactions for this client
        $transactions = $this->Transactions->getList($this->client->id, $status, $page, [$sort => $order], $post_filters);
        $total_results = $this->Transactions->getListCount($this->client->id, $status, $post_filters);

        // Set the input field filters for the widget
        $transaction_filters = new TransactionFilters();
        $this->set(
            'filters',
            $transaction_filters->getFilters(
                [
                    'language' => Configure::get('Blesta.language'),
                    'company_id' => Configure::get('Blesta.company_id'),
                    'client' => true
                ],
                $post_filters
            )
        );

        $this->set('filter_vars', $post_filters);
        $this->set('transactions', $transactions);
        $this->set('client', $this->client);
        $this->set('status', $status);
        $this->set('status_count', $status_count);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set(
            'widget_state',
            isset($this->widgets_state['transactions']) ? $this->widgets_state['transactions'] : null
        );
        // Holds the name of all of the transaction types
        $this->set('transaction_types', $this->Transactions->transactionTypeNames());
        // Holds the name of all of the transaction status values
        $this->set('transaction_status', $this->Transactions->transactionStatusNames());
        $this->structure->set(
            'page_title',
            Language::_('ClientTransactions.index.page_title', true, $this->client->id_code)
        );

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination_client'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'transactions/index/' . $status . '/[p]/',
                'params' => ['sort' => $sort, 'order' => $order],
            ]
        );
        $this->setPagination($this->get, $settings);

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(
                isset($this->get['whole_widget']) ? null : (isset($this->get[2]) || isset($this->get['sort']))
            );
        }
    }

    /**
     * AJAX request for all transactions an invoice has applied
     */
    public function applied()
    {
        $this->uses(['Invoices']);

        $transaction = $this->Transactions->get((int) $this->get[0]);

        // Ensure the transaction belongs to the client and this is an ajax request
        if (!$this->isAjax() || !$transaction || $transaction->client_id != $this->client->id) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $vars = [
            'client' => $this->client,
            'applied' => $this->Transactions->getApplied($transaction->id),
            'transaction' => $transaction
        ];

        // Send the template
        echo $this->partial('client_transactions_applied', $vars);

        // Render without layout
        return false;
    }
}
