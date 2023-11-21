<?php

use Blesta\Core\Util\Input\Fields\InputFields;
use Blesta\Core\Util\Input\Fields\Html as FieldsHtml;

/**
 * Support Manager Admin Tickets controller
 *
 * @package blesta
 * @subpackage blesta.plugins.support_manager
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminTickets extends SupportManagerController
{
    /**
     * Setup
     */
    public function preAction()
    {
        parent::preAction();

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);
        $this->requireLogin();

        // Load the Text Parser
        $this->helpers(['TextParser']);
        $this->uses(['SupportManager.SupportManagerStaff', 'SupportManager.SupportManagerTickets']);

        $this->staff_id = $this->Session->read('blesta_staff_id');

        $this->set('string', $this->DataStructure->create('string'));
    }

    /**
     * Retrieves a key/value list of actions that can be performed on tickets
     *
     * @return array A list of key/value pairs representing valid actions
     */
    private function getTicketActions()
    {
        return [
            'update_status' => Language::_('Global.action.update_status', true),
            'delete' => Language::_('Global.action.delete', true),
            'merge' => Language::_('Global.action.merge', true),
            'reassign' => Language::_('Global.action.reassign', true)
        ];
    }

    /**
     * Retrieves a key/value list of actions that can be performed on replies
     *
     * @return array A list of key/value pairs representing valid actions
     */
    private function getReplyActions()
    {
        return [
            'quote' => Language::_('Global.action.quote', true),
            'split' => Language::_('Global.action.split', true)
        ];
    }

    /**
     * Sets a message to the view if no staff or departments are set
     */
    private function setDepartmentStaffNotice()
    {
        $this->uses(['SupportManager.SupportManagerDepartments']);

        if ($this->SupportManagerDepartments->getListCount($this->company_id) == 0 ||
            $this->SupportManagerStaff->getListCount($this->company_id) == 0) {
            // Set language for the department/staff nav items
            $department = Language::_('SupportManagerPlugin.nav_primary_staff.departments', true);
            $staff = Language::_('SupportManagerPlugin.nav_primary_staff.staff', true);

            $this->setMessage(
                'notice',
                Language::_('AdminTickets.!notice.no_departments_staff', true, $department, $staff),
                false,
                null,
                false
            );
        }
    }

    /**
     * View tickets
     */
    public function index()
    {
        $status = (isset($this->get[0]) ? $this->get[0] : 'open');
        $page = (isset($this->get[1]) ? (int)$this->get[1] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'last_reply_date');
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

        $this->set('status', $status);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        // Set the number of tickets of each type
        $status_count = [
            'open' => $this->SupportManagerTickets->getStatusCount('open', $this->staff_id, null, $post_filters),
            'awaiting_reply' => $this->SupportManagerTickets->getStatusCount('awaiting_reply', $this->staff_id, null, $post_filters),
            'in_progress' => $this->SupportManagerTickets->getStatusCount('in_progress', $this->staff_id, null, $post_filters),
            'on_hold' => $this->SupportManagerTickets->getStatusCount('on_hold', $this->staff_id, null, $post_filters),
            'closed' => $this->SupportManagerTickets->getStatusCount('closed', $this->staff_id, null, $post_filters),
            'trash' => $this->SupportManagerTickets->getStatusCount('trash', $this->staff_id, null, $post_filters)
        ];

        $tickets = $this->SupportManagerTickets->getList(
            $status,
            $this->staff_id,
            null,
            $page,
            [$sort => $order, 'support_tickets.id' => $order],
            false,
            null,
            $post_filters
        );
        $total_results = $this->SupportManagerTickets->getListCount($status, $this->staff_id, null, $post_filters);

        // Set pagination parameters, set group if available
        $params = ['sort' => $sort, 'order' => $order];

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'plugin/support_manager/admin_tickets/index/' . $status . '/[p]/',
                'params' => $params
            ]
        );
        $this->setPagination($this->get, $settings);

        // Set the time that the ticket was last replied to
        foreach ($tickets as &$ticket) {
            $ticket->last_reply_time = $this->timeSince($ticket->last_reply_date);
        }

        $ticket_actions = $this->getTicketActions();
        if ($status !== 'trash') {
            unset($ticket_actions['delete']);
        }

        $this->set('staff_id', $this->staff_id);
        $this->set('tickets', $tickets);
        $this->set('page', $page);
        $this->set('status_count', $status_count);
        $this->set('priorities', $this->SupportManagerTickets->getPriorities());
        $this->set('ticket_actions', $ticket_actions);
        $this->set('ticket_statuses', $this->SupportManagerTickets->getStatuses());

        // Set the input field filters for the widget
        $filters = $this->getFilters($post_filters);
        $this->set('filters', $filters);
        $this->set('filter_vars', $post_filters);

        // Set a message if staff/departments are not setup
        if (!$this->isAjax()) {
            $this->setDepartmentStaffNotice();
            $this->set('set_ticket_time', true);
        }

        // Render the request if ajax
        return $this->renderAjaxWidgetIfAsync(isset($this->get[1]) || isset($this->get['sort']));
    }

    /**
     * View client profile ticket widget
     */
    public function client()
    {
        // Ensure a valid client was given
        $this->uses(['Clients']);
        $client_id = (isset($this->get['client_id'])
            ? $this->get['client_id']
            : (isset($this->get[0]) ? $this->get[0] : null)
        );
        if (empty($client_id) || !($client = $this->Clients->get($client_id))) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

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

        // Set the number of tickets of each type
        $status_count = [
            'open' => $this->SupportManagerTickets->getStatusCount('open', $this->staff_id, $client->id, $post_filters),
            'awaiting_reply' => $this->SupportManagerTickets->getStatusCount(
                'awaiting_reply',
                $this->staff_id,
                $client->id,
                $post_filters
            ),
            'in_progress' => $this->SupportManagerTickets->getStatusCount('in_progress', $this->staff_id, $client->id, $post_filters),
            'on_hold' => $this->SupportManagerTickets->getStatusCount('on_hold', $this->staff_id, $client->id, $post_filters),
            'closed' => $this->SupportManagerTickets->getStatusCount('closed', $this->staff_id, $client->id, $post_filters),
            'trash' => $this->SupportManagerTickets->getStatusCount('trash', $this->staff_id, $client->id, $post_filters)
        ];

        $status = (isset($this->get[1]) ? $this->get[1] : 'open');
        $page = (isset($this->get[2]) ? (int)$this->get[2] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'last_reply_date');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        // Fetch tickets
        $tickets = $this->SupportManagerTickets->getList(
            $status,
            $this->staff_id,
            $client->id,
            $page,
            [$sort => $order],
            true,
            null,
            $post_filters
        );

        // Set the time that the ticket was last replied to
        foreach ($tickets as &$ticket) {
            $ticket->last_reply_time = $this->timeSince($ticket->last_reply_date);
        }

        $this->set(
            'widget_state',
            isset($this->widgets_state['tickets_client']) ? $this->widgets_state['tickets_client'] : null
        );
        $this->set('tickets', $tickets);
        $this->set('client', $client);
        $this->set('status', $status);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('staff_id', $this->staff_id);
        $this->set('status_count', $status_count);
        $this->set('priorities', $this->SupportManagerTickets->getPriorities());

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $this->SupportManagerTickets->getListCount($status, $this->staff_id, $client->id),
                'uri' => $this->base_uri . 'plugin/support_manager/admin_tickets/client/'
                    . $client->id . '/' . $status . '/[p]/',
                'params' => ['sort' => $sort, 'order' => $order],
            ]
        );
        $this->setPagination($this->get, $settings);

        // Set the input field filters for the widget
        $filters = $this->getFilters($post_filters);
        $this->set('filters', $filters);
        $this->set('filter_vars', $post_filters);

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(
                isset($this->get['client_id']) ? null : (isset($this->get[2]) || isset($this->get['sort']))
            );
        }
    }

    /**
     * Client Ticket count
     */
    public function clientTicketCount()
    {
        $client_id = isset($this->get[0]) ? $this->get[0] : null;
        $status = isset($this->get[1]) ? $this->get[1] : 'open';

        echo $this->SupportManagerTickets->getStatusCount($status, $this->staff_id, $client_id);
        return false;
    }

    /**
     * Add a ticket
     */
    public function add()
    {
        $this->uses(['Clients', 'SupportManager.SupportManagerDepartments', 'SupportManager.SupportManagerStaff']);

        // Set the client if given
        $client_id = null;
        $client = null;
        if (isset($this->get[0])) {
            $client = $this->Clients->get($this->get[0]);
            $client_id = ($client ? $client->id : $this->get[0]);
        }

        // Process dropzone attachments
        if (isset($this->files['dropzone'])) {
            $this->processDropzoneAttachments();
        }
        if (isset($this->post['dropzone'])) {
            $this->files = $this->fetchDropzoneFiles();
        }

        $please_select = ['' => Language::_('AppController.select.please', true)];
        $department_staff = ['' => Language::_('AdminTickets.text.unassigned', true)];

        if (!empty($data['department_id'])) {
            $department = $this->SupportManagerDepartments->get($data['department_id']);
        }

        if (!empty($this->post)) {
            // Set custom field checkboxes default value
            foreach ($department->fields ?? [] as $field) {
                if ($field->type == 'checkbox') {
                    if (!isset($this->post['custom_fields'][$field->id])) {
                        $this->post['custom_fields'][$field->id] = '0';
                    }
                }
            }

            $data = $this->post;
            // Set staff ticket is assigned to
            $data['staff_id'] = (isset($data['ticket_staff_id']) ? $data['ticket_staff_id'] : $this->staff_id);
            $data['type'] = 'reply';

            // Set the client ID if not passed in by POST
            if (!isset($data['client_id'])) {
                $data['client_id'] = $client_id;
            }

            // Create a transaction
            $this->SupportManagerTickets->begin();

            // Open the ticket
            $ticket_id = $this->SupportManagerTickets->add($data);
            $ticket_errors = $this->SupportManagerTickets->errors();
            $reply_errors = [];

            // Create the initial reply
            if (!$ticket_errors) {
                // Set the staff that replied to this ticket
                $data['staff_id'] = $this->staff_id;
                $reply_id = $this->SupportManagerTickets->addReply($ticket_id, $data, $this->files, true);
                $reply_errors = $this->SupportManagerTickets->errors();
            }

            $errors = array_merge(($ticket_errors ? $ticket_errors : []), ($reply_errors ? $reply_errors : []));

            if ($errors) {
                // Error, reset vars
                $this->SupportManagerTickets->rollBack();

                $vars = (object)$this->post;
                $this->setMessage('error', $errors, false, null, false);

                // Set the priorities and staff to show
                if (!empty($data['department_id'])) {
                    $priorities = $please_select + $this->SupportManagerTickets->getPriorities();
                    $department_staff += $this->Form->collapseObjectArray(
                        $this->SupportManagerStaff->getAll($this->company_id, $data['department_id'], false),
                        ['first_name', 'last_name'],
                        'id',
                        ' '
                    );
                }
            } else {
                // Success
                $this->SupportManagerTickets->commit();

                // Fetch the ticket
                $ticket = $this->SupportManagerTickets->get($ticket_id);

                // Get the company hostname
                $hostname = isset(Configure::get('Blesta.company')->hostname)
                    ? Configure::get('Blesta.company')->hostname
                    : '';

                // Send the email associated with this ticket
                $key = mt_rand();
                $hash = $this->SupportManagerTickets->generateReplyHash($ticket->id, $key);
                $additional_tags = [
                    'SupportManager.ticket_updated' => [
                        'update_ticket_url' => $this->Html->safe(
                            $hostname . $this->client_uri . 'plugin/support_manager/client_tickets/reply/'
                            . $ticket->id . '/?sid='
                            . rawurlencode(
                                $this->SupportManagerTickets->systemEncrypt(
                                    'h=' . substr($hash, -16) . '|k=' . $key
                                )
                            )
                        )
                    ]
                ];
                $this->SupportManagerTickets->sendEmail($reply_id, $additional_tags);

                // Notify staff that a ticket has been assigned to them
                $assign_to_staff_id = (isset($data['ticket_staff_id']) ? $data['ticket_staff_id'] : $this->staff_id);
                if (!empty($assign_to_staff_id) && $this->staff_id != $ticket->staff_id) {
                    $this->SupportManagerTickets->sendTicketAssignedEmail($ticket->id);
                }

                $this->flashMessage(
                    'message',
                    Language::_('AdminTickets.!success.ticket_created', true, $ticket->code),
                    null,
                    false
                );
                $this->redirect($this->base_uri . 'plugin/support_manager/admin_tickets/');
            }
        }

        // Include Dropzone.js
        $this->Javascript->setFile(
            'views/default/js/dropzone.js',
            'head',
            str_replace('/index.php', '', WEBDIR) . $this->view->view_path
        );

        // Set departments, statuses
        $departments = $please_select
            + $this->Form->collapseObjectArray(
                $this->SupportManagerDepartments->getAll($this->company_id),
                'name',
                'id'
            );
        $statuses = $please_select + $this->SupportManagerTickets->getStatuses();
        unset($statuses['closed'], $statuses['trash']);

        // Set default vars
        if (!isset($vars)) {
            $vars = (object)['status' => 'open'];
        }

        // Set department custom fields
        if (!empty($data['department_id'])) {
            $department = $this->SupportManagerDepartments->get($data['department_id']);
            $input_fields = $this->formatDepartmentCustomFields($department->fields, $vars->custom_fields ?? []);
            $department_fields = new FieldsHtml($input_fields);
        }

        $this->set('vars', $vars);
        $this->set('departments', $departments);
        $this->set('priorities', ($priorities ?? $please_select));
        $this->set('department_fields', isset($department_fields) ? $department_fields->generate() : '');
        $this->set('statuses', $statuses);
        $this->set('department_staff', $department_staff);
        $this->set('client', $client);
        $this->set('staff_settings', $this->SupportManagerStaff->getSettings($this->staff_id, $this->company_id));
    }

    /**
     * Reply to a ticket
     */
    public function reply()
    {
        // Ensure a valid ticket is given
        if (!isset($this->get[0])
            || !($ticket = $this->SupportManagerTickets->get($this->get[0], true, null, $this->staff_id))) {
            $this->redirect($this->base_uri . 'plugin/support_manager/admin_tickets/');
        }

        // Process dropzone attachments
        if (isset($this->files['dropzone'])) {
            $this->processDropzoneAttachments();
        }
        if (isset($this->post['dropzone'])) {
            $this->files = $this->fetchDropzoneFiles();
        }

        $this->uses(['Clients', 'SupportManager.SupportManagerDepartments']);

        $department = $this->SupportManagerDepartments->get($ticket->department_id);

        if (!empty($this->post)) {
            // Set custom field checkboxes default value
            foreach ($department->fields ?? [] as $field) {
                if ($field->type == 'checkbox') {
                    if (!isset($this->post['custom_fields'][$field->id])) {
                        $this->post['custom_fields'][$field->id] = '0';
                    }
                }
            }

            $data = $this->post;
            $data['type'] = (isset($this->post['reply_type'])
                && in_array($this->post['reply_type'], ['reply', 'note']) ? $this->post['reply_type'] : null);
            $data['staff_id'] = $this->staff_id;

            // Set the details field
            if ($data['type'] == 'note') {
                $data['details'] = $data['notes'];
            }

            // Transition status unless already changed
            if (isset($data['status'])
                && $data['status'] == $ticket->status
                && $ticket->status == 'open'
                && $data['type'] != 'note'
                && ($department = $this->SupportManagerDepartments->get($ticket->department_id))
                && $department->automatic_transition == '1'
                && !empty($data['details'])
            ) {
                $data['status'] = 'awaiting_reply';
            }

            // Create a transaction
            $this->SupportManagerTickets->begin();

            // Add the reply
            $reply_id = $this->SupportManagerTickets->addReply($ticket->id, $data, $this->files);

            if (($errors = $this->SupportManagerTickets->errors())) {
                // Error, reset vars
                $this->SupportManagerTickets->rollBack();

                $vars = (object)$this->post;
                $this->setMessage('error', $errors, false, null, false);
            } else {
                // Success, commit
                $this->SupportManagerTickets->commit();

                // Get the company hostname
                $hostname = isset(Configure::get('Blesta.company')->hostname)
                    ? Configure::get('Blesta.company')->hostname
                    : '';

                // Send the email associated with this ticket
                $key = mt_rand();
                $hash = $this->SupportManagerTickets->generateReplyHash($ticket->id, $key);
                $additional_tags = [
                    'SupportManager.ticket_updated' => [
                        'update_ticket_url' => $this->Html->safe(
                            $hostname . $this->client_uri . 'plugin/support_manager/client_tickets/reply/'
                            . $ticket->id . '/?sid='
                            . rawurlencode(
                                $this->SupportManagerTickets->systemEncrypt('h=' . substr($hash, -16) . '|k=' . $key)
                            )
                        )
                    ]
                ];
                $this->SupportManagerTickets->sendEmail($reply_id, $additional_tags);

                // Notify staff that a ticket has been assigned to them
                if (!empty($this->post['ticket_staff_id']) && $this->post['ticket_staff_id'] != $ticket->staff_id
                    && $this->staff_id != $this->post['ticket_staff_id']) {
                    $this->SupportManagerTickets->sendTicketAssignedEmail($ticket->id);
                }

                $this->flashMessage(
                    'message',
                    Language::_('AdminTickets.!success.ticket_updated', true, $ticket->code),
                    null,
                    false
                );
                $this->redirect($this->base_uri . 'plugin/support_manager/admin_tickets/');
            }
        }

        // Include Dropzone.js
        $this->Javascript->setFile(
            'views/default/js/dropzone.js',
            'head',
            str_replace('/index.php', '', WEBDIR) . $this->view->view_path
        );

        // Set initial ticket
        if (!isset($vars)) {
            $vars = $ticket;
            $vars->ticket_staff_id = $ticket->staff_id;
        }

        $valid_extensions = Configure::get('SupportManager.image_mime_types');
        foreach ($ticket->replies as $reply) {
            $reply->images = [];
            foreach ($reply->attachments as $attachment) {
                $image_name_parts = explode('.', $attachment->name);
                $image_extension = end($image_name_parts);
                if (in_array(strtolower($image_extension), $valid_extensions)) {
                    $reply->images[$attachment->id] = $attachment->name;
                }
            }
        }

        // Set department custom fields
        $input_fields = $this->formatDepartmentCustomFields($department->fields, $vars->custom_fields ?? []);
        $department_fields = new FieldsHtml($input_fields);

        $this->set('ticket', $ticket);
        $this->set(
            'ticket_replies',
            $this->partial(
                'admin_tickets_replies',
                [
                    'ticket' => $ticket,
                    'ticket_actions' => $this->getReplyActions(),
                    'thumbnails_per_row' => Configure::get('SupportManager.thumbnails_per_row')
                ]
            )
        );
        $this->set('vars', $vars);
        $this->set(
            'refresh_message',
            $this->setMessage(
                'notice',
                Language::_('AdminTickets.reply.refresh', true)
                    . ' <a href="#ticket_replies">'
                        . Language::_('AdminTickets.reply.refresh_link', true)
                    . '</a>',
                true,
                ['preserve_tags' => true],
                false
            )
        );
        $this->set('statuses', $this->SupportManagerTickets->getStatuses());
        $this->set('priorities', $this->SupportManagerTickets->getPriorities());
        $this->set('department_fields', $department_fields->generate());
        $this->set('staff_id', $this->staff_id);

        // Set the client this ticket belongs to
        if (!empty($ticket->client_id)) {
            $this->set('client', $this->Clients->get($ticket->client_id, false));
        }

        $please_select = ['' => Language::_('AppController.select.please', true)];
        $departments = $please_select
            + $this->Form->collapseObjectArray(
                $this->SupportManagerDepartments->getAll($this->company_id),
                'name',
                'id'
            );

        $department_staff = ['' => Language::_('AdminTickets.text.unassigned', true)] +
            $this->Form->collapseObjectArray(
                $this->SupportManagerStaff->getAll($this->company_id, $ticket->department_id, false),
                ['first_name', 'last_name'],
                'id',
                ' '
            );

        $this->set('departments', $departments);
        $this->set('department_staff', $department_staff);

        // Make staff settings available for those staff that have replied to this ticket
        $staff_settings = [
            $this->staff_id => $this->SupportManagerStaff->getSettings($this->staff_id, $this->company_id)
        ];
        if (!empty($ticket->replies)) {
            foreach ($ticket->replies as $reply) {
                if ($reply->staff_id) {
                    if (!array_key_exists($reply->staff_id, $staff_settings)) {
                        $staff_settings[$reply->staff_id] = $this->SupportManagerStaff->getSettings(
                            $reply->staff_id,
                            $this->company_id
                        );
                    }
                }
            }
        }
        $this->set('staff_settings', $staff_settings);

        // Set the page title
        $this->structure->set('page_title', Language::_('AdminTickets.reply.page_title', true, $ticket->code));
    }

    /**
     * AJAX Fetch clients when searching
     * @see AdminTickets::add()
     */
    public function getClients()
    {
        // Ensure there is post data
        if (!$this->isAjax() || empty($this->post['search'])) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $this->uses(['Clients']);
        $search = $this->post['search'];
        $clients = $this->Form->collapseObjectArray(
            $this->Clients->search($search),
            ['id_code', 'first_name', 'last_name'],
            'id',
            ' '
        );

        echo json_encode(['clients' => $clients]);
        return false;
    }

    /**
     * AJAX Fetch non-closed tickets
     * @see AdminTickets::add()
     */
    public function searchByCode()
    {
        // Ensure there is post data
        if (!$this->isAjax() || empty($this->post['search'])) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $this->uses(['Clients']);

        // Find matching tickets
        $search = $this->post['search'];
        $page = 1;
        $tickets = $this->SupportManagerTickets->searchByCode(
            $search,
            $this->staff_id,
            'not_closed',
            $page,
            ['code' => 'desc']
        );

        // Fetch the client name for each ticket
        $clients = [];
        foreach ($tickets as &$ticket) {
            if (!empty($ticket->client_id) && !isset($clients[$ticket->client_id])) {
                $clients[$ticket->client_id] = $this->Clients->get($ticket->client_id, false);
            }

            $ticket->display_name = Language::_('AdminTickets.index.ticket_email', true, $ticket->code, $ticket->email);
            if (!empty($clients[$ticket->client_id])) {
                $ticket->display_name = Language::_(
                    'AdminTickets.index.ticket_name',
                    true,
                    $ticket->code,
                    $clients[$ticket->client_id]->first_name,
                    $clients[$ticket->client_id]->last_name
                );
            }
        }

        $tickets = $this->Form->collapseObjectArray($tickets, ['display_name'], 'id', ' ');

        echo json_encode(['tickets' => $tickets]);
        return false;
    }

    /**
     * Search tickets
     */
    public function search()
    {
        // Get search criteria
        $search = (isset($this->get['search']) ? $this->get['search'] : '');
        if (isset($this->post['search'])) {
            $search = $this->post['search'];
        }

        // Set page title
        $this->structure->set('page_title', Language::_('AdminTickets.search.page_title', true, $search));

        $page = (isset($this->get['p']) ? (int)$this->get['p'] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'last_reply_date');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('search', $search);

        // Search
        $tickets = $this->SupportManagerTickets->search(
            $search,
            $this->staff_id,
            $page,
            [$sort => $order, 'support_tickets.id' => $order]
        );
        foreach ($tickets as &$ticket) {
            $ticket->last_reply_time = $this->timeSince($ticket->last_reply_date);
        }

        $this->set('statuses', $this->SupportManagerTickets->getStatuses());
        $this->set('priorities', $this->SupportManagerTickets->getPriorities());
        $this->set('tickets', $tickets);

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $this->SupportManagerTickets->getSearchCount($search, $this->staff_id),
                'uri' => $this->base_uri . 'plugin/support_manager/admin_tickets/search/',
                'params' => ['p' => '[p]', 'search' => $search]
            ]
        );
        $this->setPagination($this->get, $settings);

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(
                isset($this->post['search']) ? null : (isset($this->get['search']) || isset($this->get['sort']))
            );
        }
    }

    /**
     * Performs a given ticket action
     */
    public function action()
    {
        // Ensure a valid action was given
        if (empty($this->post['action']) || !in_array($this->post['action'], array_keys($this->getTicketActions()))) {
            $this->redirect($this->base_uri . 'plugin/support_manager/admin_tickets/');
        }

        $ticket_ids = $this->getPostTicketIDs($this->post);

        switch ($this->post['action']) {
            case 'merge':
                if (!empty($this->post['ticket_id']) && !empty($ticket_ids)) {
                    $this->SupportManagerTickets->merge((int)$this->post['ticket_id'], (array)$ticket_ids);

                    if (($errors = $this->SupportManagerTickets->errors())) {
                        // Error
                        $this->flashMessage('error', $errors, null, false);
                    } else {
                        // Success
                        $ticket = $this->SupportManagerTickets->get((int)$this->post['ticket_id'], false);
                        $this->flashMessage(
                            'message',
                            Language::_('AdminTickets.!success.ticket_merge', true, ($ticket ? $ticket->code : '')),
                            null,
                            false
                        );
                    }
                }
                break;
            case 'update_status':
                $ticket_statuses = $this->SupportManagerTickets->getStatuses();

                if (!empty($ticket_ids) && !empty($this->post['status'])
                    && array_key_exists($this->post['status'], $ticket_statuses)) {
                    // Update the select tickets to the new status
                    $this->SupportManagerTickets->editMultiple(
                        (array)$ticket_ids,
                        ['by_staff_id' => $this->staff_id, 'status' => $this->post['status']]
                    );

                    if (($errors = $this->SupportManagerTickets->errors())) {
                        // Error
                        $this->flashMessage('error', $errors, null, false);
                    } else {
                        // Success
                        $this->flashMessage(
                            'message',
                            Language::_('AdminTickets.!success.ticket_update_status', true),
                            null,
                            false
                        );
                    }
                }
                break;
            case 'reassign':
                if (!empty($ticket_ids) && !empty($this->post['client_id'])) {
                    // Update the select tickets to the new client
                    $this->SupportManagerTickets->reassignTickets(
                        [
                            'ticket_ids' => (array)$ticket_ids,
                            'client_id' => $this->post['client_id'],
                            'staff_id' => $this->Session->read('blesta_staff_id')
                        ]
                    );

                    if (($errors = $this->SupportManagerTickets->errors())) {
                        // Error
                        $this->flashMessage('error', $errors, null, false);
                    } else {
                        // Success
                        $this->flashMessage(
                            'message',
                            Language::_('AdminTickets.!success.ticket_reassign', true),
                            null,
                            false
                        );
                    }
                }
        }

        // Maintain previous status view
        $ticket_status = isset($this->post['current_status'])
            ? $this->post['current_status']
            : '';
        $this->redirect($this->base_uri . 'plugin/support_manager/admin_tickets/index/' . $ticket_status);
    }

    /**
     * Permanently deletes the given tickets
     */
    public function delete()
    {
        $ticket_ids = $this->getPostTicketIDs($this->post);
        $this->SupportManagerTickets->delete($ticket_ids);

        $this->flashMessage(
            'message',
            Language::_('AdminTickets.!success.ticket_delete', true),
            null,
            false
        );

        $this->redirect($this->base_uri . 'plugin/support_manager/admin_tickets/');
    }

    /**
     * Gets a list of ticket IDs from post data
     *
     * @param array $post The post data
     * @return array The ticket IDs
     */
    private function getPostTicketIDs(array $post)
    {
        // Remove the 'all' ticket option if selected
        $ticket_ids = (isset($post['tickets']) ? (array)$post['tickets'] : []);
        if (isset($ticket_ids[0]) && $ticket_ids[0] == 'all') {
            unset($ticket_ids[0]);
            $ticket_ids = array_values($ticket_ids);
        }

        return $ticket_ids;
    }

    /**
     * AJAX Fetches the given replies for the given ticket as markdown-quoted text
     */
    public function getQuotedReplies()
    {
        if (!$this->isAjax() || !isset($this->get[0]) || empty($this->post['reply_ids']) ||
            !($ticket = $this->SupportManagerTickets->get($this->get[0], true, ['reply'], $this->staff_id))) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $reply_ids = explode(',', $this->post['reply_ids']);

        $replies = '';
        $i = 0;
        foreach ($ticket->replies as $reply) {
            if (in_array($reply->id, $reply_ids)) {
                $replies .= ($i++ > 0 ? "\n" : '') . "\n" . preg_replace("/\r\n|\r|\n/", "\n>", '>' . $reply->details);
            }
        }

        $this->outputAsJson($replies);
        return false;
    }

    /**
     * Performs a given action
     */
    public function replyAction()
    {
        // Ensure valid ticket was given
        if (!isset($this->get[0])
            || !($ticket = $this->SupportManagerTickets->get($this->get[0], true, null, $this->staff_id))
            || empty($this->post['action'])
            || !in_array($this->post['action'], array_keys($this->getReplyActions()))
        ) {
            $this->redirect($this->base_uri . 'plugin/support_manager/admin_tickets/');
        }

        // Perform the requested action
        switch ($this->post['action']) {
            case 'split':
                // Split the ticket
                $replies = (isset($this->post['replies']) ? $this->post['replies'] : []);
                $new_ticket_id = $this->SupportManagerTickets->split($ticket->id, $replies);

                if (($errors = $this->SupportManagerTickets->errors())) {
                    // Error
                    $this->flashMessage('error', $errors, null, false);
                    $this->redirect(
                        $this->base_uri . 'plugin/support_manager/admin_tickets/reply/' . $ticket->id . '/'
                    );
                } else {
                    // Success
                    $new_ticket = $this->SupportManagerTickets->get($new_ticket_id, false);
                    $this->flashMessage(
                        'message',
                        Language::_(
                            'AdminTickets.!success.ticket_split',
                            true,
                            $ticket->code,
                            ($new_ticket ? $new_ticket->code : '')
                        ),
                        null,
                        false
                    );
                    $this->redirect(
                        $this->base_uri . 'plugin/support_manager/admin_tickets/reply/' . $new_ticket->id . '/'
                    );
                }
                break;
        }

        $this->redirect($this->base_uri . 'plugin/support_manager/admin_tickets/reply/' . $ticket->id . '/');
    }

    /**
     * Streams an attachment to view
     */
    public function getAttachment()
    {
        // Ensure a valid attachment was given
        if (!isset($this->get[0]) || !($attachment = $this->SupportManagerTickets->getAttachment($this->get[0]))) {
            exit();
        }

        // Ensure the staff member can view the attachment
        $staff = $this->SupportManagerStaff->get($this->staff_id, $this->company_id);
        if (!in_array($attachment->department_id, $this->Form->collapseObjectArray($staff->departments, 'id', 'id'))) {
            exit();
        }

        $this->components(['Download']);

        $this->Download->downloadFile($attachment->file_name, $attachment->name);
        return false;
    }

    /**
     * AJAX Fetches a list of department priorities and the default priority
     */
    public function getPriorities()
    {
        $please_select = ['' => Language::_('AppController.select.please', true)];
        $vars = [
            'default_priority' => '',
            'priorities' => $please_select
        ];

        // Return nothing if the department not given
        if (!isset($this->get[0])) {
            $this->outputAsJson($vars);
            return false;
        }

        // Ensure a valid department was given
        $this->uses(['SupportManager.SupportManagerDepartments']);
        if (!$this->isAjax() || !($department = $this->SupportManagerDepartments->get($this->get[0])) ||
            $department->company_id != $this->company_id) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        // Set priorities
        $vars['default_priority'] = $department->default_priority;
        $vars['priorities'] = $please_select + $this->SupportManagerTickets->getPriorities();

        $this->outputAsJson($vars);
        return false;
    }

    /**
     * AJAX request to fetch all department that belong to a given support department
     */
    public function getDepartmentStaff()
    {
        $department_staff = ['' => Language::_('AdminTickets.text.unassigned', true)];

        if (!isset($this->get[0])) {
            $this->outputAsJson($department_staff);
            return false;
        }

        // Ensure a valid department was given
        $this->uses(['SupportManager.SupportManagerDepartments']);
        if (!$this->isAjax() || !($department = $this->SupportManagerDepartments->get($this->get[0])) ||
            $department->company_id != $this->company_id) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $department_staff += $this->Form->collapseObjectArray(
            $this->SupportManagerStaff->getAll($this->company_id, $department->id, false),
            ['first_name', 'last_name'],
            'id',
            ' '
        );

        // Get department fields
        $input_fields = $this->formatDepartmentCustomFields($department->fields, $this->post['custom_fields'] ?? []);
        $input_html = new FieldsHtml($input_fields);

        $this->outputAsJson([
            'department_staff' => $department_staff,
            'department_fields' => $input_html->generate()
        ]);
        return false;
    }

    /**
     * AJAX retrieves the partial that lists categories and responses
     */
    public function getResponseListing()
    {
        $this->uses(['SupportManager.SupportManagerResponses']);
        // Ensure a valid category was given
        $category = (isset($this->get[0]) ? $this->SupportManagerResponses->getCategory($this->get[0]) : null);
        if ($category && $category->company_id != $this->company_id) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        // Load language for responses
        Language::loadLang('admin_responses', null, PLUGINDIR . 'support_manager' . DS . 'language' . DS);

        // Build the partial for listing categories and responses
        $category_id = (isset($category->id) ? $category->id : null);
        $vars = [
            'categories' => $this->SupportManagerResponses->getAllCategories($this->company_id, $category_id),
            'category' => $category,
            'show_links' => false
        ];

        if ($category) {
            $vars['responses'] = $this->SupportManagerResponses->getAll($this->company_id, $category_id);
        }

        echo json_encode($this->partial('admin_responses_response_list', $vars));
        return false;
    }

    /**
     * AJAX retrieves the predefined response text for a specific response
     */
    public function getResponse()
    {
        $this->uses(['SupportManager.SupportManagerResponses']);
        // Ensure a valid response was given
        $response = (isset($this->get[0]) ? $this->SupportManagerResponses->get($this->get[0]) : null);
        if ($response && $response->company_id != $this->company_id) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        echo json_encode($response->details);
        return false;
    }

    /**
     * AJAX retrieves the predefined response text for a specific response
     */
    public function checkReplies()
    {
        $this->uses(['SupportManager.SupportManagerTickets']);
        // Ensure a valid ticket is given
        if (!$this->isAjax()
            || !isset($this->get[0])
            || !($ticket = $this->SupportManagerTickets->get($this->get[0], true, null, $this->staff_id))
        ) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }


        echo json_encode([
            'reply_count' => count($ticket->replies),
            'ticket_replies' => $this->partial(
                'admin_tickets_replies',
                [
                    'ticket' => $ticket,
                    'ticket_actions' => $this->getReplyActions(),
                    'thumbnails_per_row' => Configure::get('SupportManager.thumbnails_per_row')
                ]
            )
        ]);
        return false;
    }

    /**
     * Gets a list of input fields for filtering tickets
     *
     * @param array $vars A list of submitted inputs that act as defaults for filter fields including:
     *
     *  - ticket_number The (partial) ticket number on which to filter tickets
     *  - priority The priority on which to filter tickets
     *  - department_id The department on which to filter tickets
     *  - summary The (partial) summary of the ticket line on which to filter tickets
     *  - staff_id The assigned staff member on which to filter tickets
     *  - last_reply The elapsed time from the last reply on which to filter tickets
     * @return InputFields An object representing the list of filter input field
     */
    private function getFilters(array $vars)
    {
        $this->uses(['SupportManager.SupportManagerDepartments']);

        $filters = new InputFields();

        // Set ticket number filter
        $ticket_number = $filters->label(
            Language::_('AdminTickets.index.field_ticket_number', true),
            'ticket_number'
        );
        $ticket_number->attach(
            $filters->fieldText(
                'filters[ticket_number]',
                isset($vars['ticket_number']) ? $vars['ticket_number'] : null,
                [
                    'id' => 'ticket_number',
                    'class' => 'form-control stretch',
                    'placeholder' => Language::_('AdminTickets.index.field_ticket_number', true)
                ]
            )
        );
        $filters->setField($ticket_number);

        // Set priority filter
        $priorities = $this->SupportManagerTickets->getPriorities();
        $priority = $filters->label(
            Language::_('AdminTickets.index.field_priority', true),
            'priority'
        );
        $priority->attach(
            $filters->fieldSelect(
                'filters[priority]',
                ['' => Language::_('AdminTickets.index.any', true)] + $priorities,
                isset($vars['priority']) ? $vars['priority'] : null,
                ['id' => 'priority', 'class' => 'form-control']
            )
        );
        $filters->setField($priority);

        // Set department filter
        $departments = $this->Form->collapseObjectArray(
            $this->SupportManagerDepartments->getAll($this->company_id),
            'name',
            'id'
        );
        $department_id = $filters->label(
            Language::_('AdminTickets.index.field_department_id', true),
            'department_id'
        );
        $department_id->attach(
            $filters->fieldSelect(
                'filters[department_id]',
                ['' => Language::_('AdminTickets.index.any', true)] + $departments,
                isset($vars['department_id']) ? $vars['department_id'] : null,
                ['id' => 'department_id', 'class' => 'form-control']
            )
        );
        $filters->setField($department_id);

        // Set summary filter
        $summary = $filters->label(
            Language::_('AdminTickets.index.field_summary', true),
            'summary'
        );
        $summary->attach(
            $filters->fieldText(
                'filters[summary]',
                isset($vars['summary']) ? $vars['summary'] : null,
                [
                    'id' => 'summary',
                    'class' => 'form-control stretch',
                    'placeholder' => Language::_('AdminTickets.index.field_summary', true)
                ]
            )
        );
        $filters->setField($summary);

        // Set assigned staff filter
        $staff = [];
        foreach ($this->SupportManagerStaff->getAll($this->company_id) as $staff_member) {
            $staff[$staff_member->id] = $staff_member->first_name . ' ' . $staff_member->last_name;
        }

        $assigned_staff = $filters->label(
            Language::_('AdminTickets.index.field_assigned_staff', true),
            'assigned_staff'
        );
        $assigned_staff->attach(
            $filters->fieldSelect(
                'filters[assigned_staff]',
                ['' => Language::_('AdminTickets.index.any', true)] + $staff,
                isset($vars['assigned_staff']) ? $vars['assigned_staff'] : null,
                ['id' => 'assigned_staff', 'class' => 'form-control']
            )
        );
        $filters->setField($assigned_staff);

        // Set last reply filter
        $time_options = [];
        $minutes = [15, 30];
        $hours = [1, 6, 12, 24, 72];

        foreach ($minutes as $minute) {
            $time_options[$minute] = Language::_('AdminTickets.index.minutes', true, $minute);
        }

        foreach ($hours as $hour) {
            $time_options[$hour * 60] = Language::_('AdminTickets.index.' . ($hour == 1 ? 'hour' : 'hours'), true, $hour);
        }

        $last_reply = $filters->label(
            Language::_('AdminTickets.index.field_last_reply', true),
            'last_reply'
        );
        $last_reply->attach(
            $filters->fieldSelect(
                'filters[last_reply]',
                ['' => Language::_('AdminTickets.index.any', true)] + $time_options,
                isset($vars['last_reply']) ? $vars['last_reply'] : null,
                ['id' => 'last_reply', 'class' => 'form-control']
            )
        );
        $filters->setField($last_reply);

        return $filters;
    }

    /**
     * Formats the custom fields of a department in to a InputFields object
     *
     * @param array $fields An array containing the department custom fields
     * @param array $vars An array containing the default values for each of the custom fields
     * @return InputFields An InputFields object represeting the department custom fields
     */
    private function formatDepartmentCustomFields(array $fields, array $vars = [])
    {
        // Get custom fields
        $input_fields = new InputFields();

        foreach ($fields as $field) {
            // Set field label
            $label = Language::_($field->label, true);
            if (empty($label)) {
                $label = $field->label;
            }
            $field->label = $label;

            // Set field description
            $description = Language::_($field->description, true);
            if (empty($description)) {
                $description = $field->description;
            }
            $field->description = $description;

            // Build text and textarea field
            switch ($field->type) {
                case 'text':
                    $custom_fields = $input_fields->label($field->label, 'custom_fields_' . $field->id);
                    $input_fields->setField(
                        $custom_fields->attach(
                            $input_fields->fieldText(
                                'custom_fields[' . $field->id . ']',
                                $vars[$field->id] ?? '',
                                ['id' => 'custom_fields_' . $field->id]
                            )
                        )
                    );

                    // Add tooltip
                    if (!empty($field->description)) {
                        $tooltip = $input_fields->tooltip($field->description);
                        $custom_fields->attach($tooltip);
                    }
                    break;
                case 'quantity':
                    $custom_fields = $input_fields->label($field->label, 'custom_fields_' . $field->id);
                    $input_fields->setField(
                        $custom_fields->attach(
                            $input_fields->fieldText(
                                'custom_fields[' . $field->id . ']',
                                $vars[$field->id] ?? $field->min ?? 0,
                                [
                                    'id' => 'custom_fields_' . $field->id,
                                    'data-type' => 'quantity',
                                    'data-min' => $field->min ?? 0,
                                    'data-max' => $field->max ?? 0,
                                    'data-step' => $field->step ?? 1
                                ]
                            )
                        )
                    );

                    // Add tooltip
                    if (!empty($field->description)) {
                        $tooltip = $input_fields->tooltip($field->description);
                        $custom_fields->attach($tooltip);
                    }
                    break;
                case 'textarea':
                    $custom_fields = $input_fields->label($field->label, 'custom_fields_' . $field->id);
                    $input_fields->setField(
                        $custom_fields->attach(
                            $input_fields->fieldTextarea(
                                'custom_fields[' . $field->id . ']',
                                $vars[$field->id] ?? '',
                                ['id' => 'custom_fields_' . $field->id]
                            )
                        )
                    );

                    // Add tooltip
                    if (!empty($field->description)) {
                        $tooltip = $input_fields->tooltip($field->description);
                        $custom_fields->attach($tooltip);
                    }
                    break;
                case 'password':
                    // We use a text field instead of a password field as staff should be able to see the password
                    $custom_fields = $input_fields->label($field->label, 'custom_fields_' . $field->id);
                    $input_fields->setField(
                        $custom_fields->attach(
                            $input_fields->fieldText(
                                'custom_fields[' . $field->id . ']',
                                $vars[$field->id] ?? '',
                                ['id' => 'custom_fields_' . $field->id]
                            )
                        )
                    );

                    // Add tooltip
                    if (!empty($field->description)) {
                        $tooltip = $input_fields->tooltip($field->description);
                        $custom_fields->attach($tooltip);
                    }
                    break;
                case 'select':
                    $options = $this->formatCustomFieldOptions($field->options ?? []);
                    $custom_fields = $input_fields->label($field->label, 'custom_fields_' . $field->id);

                    $input_fields->setField(
                        $custom_fields->attach(
                            $input_fields->fieldSelect(
                                'custom_fields[' . $field->id . ']',
                                $options['options'] ?? [],
                                $vars[$field->id] ?? $options['default'] ?? null,
                                ['id' => 'custom_fields_' . $field->id],
                                []
                            )
                        )
                    );

                    // Add tooltip
                    if (!empty($field->description)) {
                        $tooltip = $input_fields->tooltip($field->description);
                        $custom_fields->attach($tooltip);
                    }
                    break;
                case 'radio':
                    $options = $this->formatCustomFieldOptions($field->options ?? []);
                    $custom_fields = $input_fields->label($field->label, 'custom_fields_' . $field->id);

                    // Add tooltip
                    if (!empty($field->description)) {
                        $tooltip = $input_fields->tooltip($field->description);
                        $custom_fields->attach($tooltip);
                    }

                    foreach ($options['options'] ?? [] as $value => $name) {
                        $custom_fields->attach(
                            $input_fields->fieldRadio(
                                'custom_fields[' . $field->id . ']',
                                $value,
                                ($vars[$field->id] ?? $options['default'] ?? null) == $value,
                                ['id' => 'custom_fields_' . $field->id . '_' . $value],
                                $input_fields->label($name, 'custom_fields_' . $field->id . '_' . $value)
                            )
                        );
                    }

                    $input_fields->setField($custom_fields);
                    break;
                case 'checkbox':
                    $custom_fields = $input_fields->label('');

                    // Add tooltip
                    if (!empty($field->description)) {
                        $tooltip = $input_fields->tooltip($field->description);
                        $custom_fields->attach($tooltip);
                    }

                    $input_fields->setField(
                        $custom_fields->attach(
                            $input_fields->fieldCheckbox(
                                'custom_fields[' . $field->id . ']',
                                '1',
                                ($vars[$field->id] ?? '0') == '1',
                                ['id' => 'custom_fields_' . $field->id],
                                $input_fields->label($field->label, 'custom_fields_' . $field->id)
                            )
                        )
                    );
                    break;
            }
        }

        return $input_fields;
    }

    /**
     * Formats the custom field options to be used in a "select" field
     *
     * @param array $options An array containing the custom field options
     * @return array An array containing the formatted custom field options
     */
    private function formatCustomFieldOptions(array $options = [])
    {
        $formatted_options = [];
        if (!empty($options['name'])) {
            foreach ($options['name'] as $i => $value) {
                $formatted_options['options'][$options['value'][$i]] = $options['name'][$i];

                if ($options['default'][$i] == '1') {
                    $formatted_options['default'] = $options['value'][$i];
                }
            }
        }

        return $formatted_options;
    }
}
