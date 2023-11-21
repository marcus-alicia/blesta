<?php

use Blesta\Core\Util\Input\Fields\InputFields;

/**
 * Support Manager Client Main controller
 *
 * @package blesta
 * @subpackage blesta.plugins.support_manager
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientMain extends SupportManagerController
{
    /**
     * Setup
     */
    public function preAction()
    {
        parent::preAction();

        // Restore structure view location of the client portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);
        if (!$this->hasPermission('support_manager.*')) {
            if ($this->isAjax()) {
                // If ajax, send 403 response, user not granted access
                header($this->server_protocol . ' 403 Forbidden');
                exit();
            }

            $this->setMessage(
                'error',
                Language::_('AppController.!error.unauthorized_access', true),
                false,
                null,
                false
            );
            // Overwrite plugin view paths (if set)
            $this->view->setDefaultView(APPDIR);
            $this->render('unauthorized', Configure::get('System.default_view'));
            exit();
        }

        // Require login
        $this->requireLogin();

        $this->uses(['SupportManager.SupportManagerTickets', 'SupportManager.SupportManagerDepartments']);

        $this->client_id = $this->Session->read('blesta_client_id');

        // Fetch contact that is logged in, if any
        if (!isset($this->Contacts)) {
            $this->uses(['Contacts']);
        }
        $this->contact = $this->Contacts->getByUserId($this->Session->read('blesta_id'), $this->client_id);

        $this->set('string', $this->DataStructure->create('string'));
    }

    /**
     * Client widget
     */
    public function index()
    {
        if (!$this->isAjax()) {
            $this->redirect($this->base_uri . 'plugin/support_manager/client_tickets/');
        }

        $status = ($this->get[0] ?? 'not_closed');
        $page = (int) ($this->get[1] ?? 1);
        $sort = ($this->get['sort'] ?? 'last_reply_date');
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

        $this->set('status', $status);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        // Set the number of clients of each type
        $status_count = [
            'open' => $this->SupportManagerTickets->getStatusCount('not_closed', null, $this->client_id, $post_filters),
            'closed' => $this->SupportManagerTickets->getStatusCount('closed', null, $this->client_id, $post_filters)
        ];

        $tickets = $this->SupportManagerTickets->getList(
            $status,
            null,
            $this->client_id,
            $page,
            [$sort => $order, 'support_tickets.id' => $order],
            false,
            null,
            $post_filters
        );
        $total_results = $this->SupportManagerTickets->getListCount($status, null, $this->client_id, $post_filters);

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination_client'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'plugin/support_manager/client_main/index/' . $status . '/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        // Set the last reply time
        foreach ($tickets as &$ticket) {
            $ticket->last_reply_time = $this->timeSince($ticket->last_reply_date);
        }

        $this->set('tickets', $tickets);
        $this->set('status_count', $status_count);
        $this->set('priorities', $this->SupportManagerTickets->getPriorities());
        $this->set('statuses', $this->SupportManagerTickets->getStatuses());
        $this->set('priority_classes', $this->getPriorityClasses());

        // Set the input field filters for the widget
        $filters = $this->getFilters($post_filters);
        $this->set('filters', $filters);
        $this->set('filter_vars', $post_filters);

        // Render the request if ajax
        return $this->renderAjaxWidgetIfAsync(
            isset($this->get['sort']) ? true : (isset($this->get[0]) ? false : null)
        );
    }

    /**
     * Builds a hash mapping default support ticket priorities to class names
     *
     * @return array A key/value array of priority => class name
     */
    private function getPriorityClasses()
    {
        return [
            'low' => 'secondary',
            'medium' => 'info',
            'high' => 'success',
            'critical' => 'warning',
            'emergency' => 'danger'
        ];
    }

    /**
     * Gets a list of input fields for filtering tickets
     *
     * @param array $vars A list of submitted inputs that act as defaults for filter fields including:
     *
     *  - ticket_number The (partial) ticket number on which to filter tickets
     *  - priority The priority on which to filter tickets
     *  - summary The (partial) summary of the ticket line on which to filter tickets
     *  - last_reply The elapsed time from the last reply on which to filter tickets
     * @return InputFields An object representing the list of filter input field
     */
    private function getFilters(array $vars)
    {
        $filters = new InputFields();

        // Set ticket number filter
        $ticket_number = $filters->label(
            Language::_('ClientMain.index.field_ticket_number', true),
            'ticket_number'
        );
        $ticket_number->attach(
            $filters->fieldText(
                'filters[ticket_number]',
                $vars['ticket_number'] ?? null,
                [
                    'id' => 'ticket_number',
                    'class' => 'form-control stretch',
                    'placeholder' => Language::_('ClientMain.index.field_ticket_number', true)
                ]
            )
        );
        $filters->setField($ticket_number);

        // Set priority filter
        $priorities = $this->SupportManagerTickets->getPriorities();
        $priority = $filters->label(
            Language::_('ClientMain.index.field_priority', true),
            'priority'
        );
        $priority->attach(
            $filters->fieldSelect(
                'filters[priority]',
                ['' => Language::_('ClientMain.index.any', true)] + $priorities,
                $vars['priority'] ?? null,
                ['id' => 'priority']
            )
        );
        $filters->setField($priority);

        // Set summary filter
        $summary = $filters->label(
            Language::_('ClientMain.index.field_summary', true),
            'summary'
        );
        $summary->attach(
            $filters->fieldText(
                'filters[summary]',
                $vars['summary'] ?? null,
                [
                    'id' => 'summary',
                    'class' => 'form-control stretch',
                    'placeholder' => Language::_('ClientMain.index.field_summary', true)
                ]
            )
        );
        $filters->setField($summary);

        // Set last reply filter
        $time_options = [];
        $minutes = [15, 30];
        $hours = [1, 6, 12, 24, 72];

        foreach ($minutes as $minute) {
            $time_options[$minute] = Language::_('ClientMain.index.minutes', true, $minute);
        }

        foreach ($hours as $hour) {
            $time_options[$hour * 60] = Language::_('ClientMain.index.' . ($hour == 1 ? 'hour' : 'hours'), true, $hour);
        }

        $last_reply = $filters->label(
            Language::_('ClientMain.index.field_last_reply', true),
            'last_reply'
        );
        $last_reply->attach(
            $filters->fieldSelect(
                'filters[last_reply]',
                ['' => Language::_('ClientMain.index.any', true)] + $time_options,
                $vars['last_reply'] ?? null,
                ['id' => 'last_reply']
            )
        );
        $filters->setField($last_reply);

        return $filters;
    }
}
