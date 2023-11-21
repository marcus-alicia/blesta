<?php

use Blesta\Core\Util\Captcha\Captcha;
use Blesta\Core\Util\Input\Fields\InputFields;
use Blesta\Core\Util\Input\Fields\Html as FieldsHtml;

/**
 * Support Manager Client Tickets controller
 *
 * @package blesta
 * @subpackage blesta.plugins.support_manager
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientTickets extends SupportManagerController
{
    /**
     * Setup
     */
    public function preAction()
    {
        parent::preAction();

        $allowed = $this->hasPermission('support_manager.*');

        // Restore structure view location of the client portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);
        if (!$allowed) {
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

        // Do not require login for the following methods
        if ($this->action != 'add' && $this->action != 'reply' && $this->action != 'close' &&
            $this->action != 'departments' && $this->action != 'getpriorities' && $this->action != 'getattachment') {
            $this->requireLogin($this->base_uri . 'plugin/support_manager/client_tickets/departments/');
        }

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
     * View tickets
     */
    public function index()
    {
        $status = (isset($this->get[0]) ? $this->get[0] : 'not_closed');
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
                'uri' => $this->base_uri . 'plugin/support_manager/client_tickets/index/' . $status . '/[p]/',
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
        return $this->renderAjaxWidgetIfAsync(isset($this->get[1]) || isset($this->get['sort']));
    }

    /**
     * 1st step of adding a ticket -- select the department
     */
    public function departments()
    {
        // Check whether client is logged in
        $logged_in = false;
        if ($this->isLoggedIn()) {
            $logged_in = true;
        }

        // Get all departments visible to clients
        $departments = $this->SupportManagerDepartments->getAll(
            $this->company_id,
            'visible',
            (!$logged_in ? false : null)
        );

        // Must be logged in if there are no departments
        if (!$logged_in && empty($departments)) {
            $this->requireLogin();
        }

        // Include the TextParser
        $this->helpers(['TextParser']);

        $this->set('departments', $departments);
    }

    /**
     * 2nd step of adding a ticket -- actually creating it
     */
    public function add()
    {
        // Check whether client is logged in
        $logged_in = false;
        if ($this->isLoggedIn()) {
            $logged_in = true;
        }

        // Ensure a valid department was given
        if (!isset($this->get[0]) || !($department = $this->SupportManagerDepartments->get($this->get[0])) ||
            ($department->company_id != $this->company_id) || (!$logged_in && $department->clients_only == '1') ||
            $department->status == 'hidden') {
            $this->redirect($this->base_uri . 'plugin/support_manager/client_tickets/departments/');
        }

        // Process dropzone attachments
        if (isset($this->files['dropzone'])) {
            $this->processDropzoneAttachments();
        }
        if (isset($this->post['dropzone'])) {
            $this->files = $this->fetchDropzoneFiles();
        }

        // Get the captcha for this department if the user is not logged in
        $captcha = null;
        if (!$logged_in && $department->require_captcha) {
            $captcha = Captcha::get();
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

            // Validate captcha
            if ($captcha !== null) {
                $success = Captcha::validate($captcha, $this->post);

                if (!$success) {
                    $errors = ['captcha' => ['invalid' => Language::_('ClientTickets.!error.captcha.invalid', true)]];
                }
            }

            if (empty($errors)) {
                // Set fields
                $data = $this->post;
                $data['status'] = 'open';
                $data['type'] = 'reply';
                $data['department_id'] = $department->id;

                // Refuse impersonations
                unset($data['staff_id'], $data['client_id']);

                // Set client iff logged in
                if ($logged_in) {
                    $data['client_id'] = $this->client_id;

                    // Set contact that is replying
                    if ($this->contact) {
                        $data['contact_id'] = $this->contact->id;
                    }
                }

                // Create a transaction
                $this->SupportManagerTickets->begin();

                // Create the ticket
                $ticket_id = $this->SupportManagerTickets->add($data, !$logged_in);
                $ticket_errors = $this->SupportManagerTickets->errors();
                $reply_errors = [];

                // Create the initial reply
                if (!$ticket_errors) {
                    $reply_id = $this->SupportManagerTickets->addReply($ticket_id, $data, $this->files, true);
                    $reply_errors = $this->SupportManagerTickets->errors();
                }

                $errors = array_merge(($ticket_errors ? $ticket_errors : []), ($reply_errors ? $reply_errors : []));
                if ($errors) {
                    // Rollback changes
                    $this->SupportManagerTickets->rollBack();
                }
            }

            if ($errors) {
                // Error, reset vars
                $vars = (object)$this->post;
                $this->setMessage('error', $errors, false, null, false);
            } else {
                // Success, commit the transaction
                $this->SupportManagerTickets->commit();

                // Send the email associated with this ticket
                $this->SupportManagerTickets->sendEmail($reply_id);

                $ticket = $this->SupportManagerTickets->get($ticket_id);
                $this->flashMessage(
                    'message',
                    Language::_('ClientTickets.!success.ticket_created', true, $ticket->code),
                    null,
                    false
                );
                $redirect_url = $this->base_uri . 'plugin/support_manager/client_tickets/';
                if (!$logged_in) {
                    $redirect_url .= 'departments/';
                }
                $this->redirect($redirect_url);
            }
        }

        // Include Dropzone.js
        $this->Javascript->setFile(
            'views/default/js/dropzone.js',
            'head',
            str_replace('/index.php', '', WEBDIR) . $this->view->view_path
        );

        // Set default department priority
        if (!isset($vars)) {
            $vars = (object)['priority' => $department->default_priority];
        }

        // Set department custom fields
        $input_fields = $this->formatDepartmentCustomFields($department->fields, $vars->custom_fields ?? []);
        $department_fields = new FieldsHtml($input_fields);

        $please_select = ['' => Language::_('AppController.select.please', true)];

        $this->set('vars', $vars);
        $this->set('priorities', ($please_select + $this->SupportManagerTickets->getPriorities()));
        $this->set('department_fields', $department_fields->generate());
        $this->set('logged_in', $logged_in);
        $this->set('captcha', ($captcha !== null ? $captcha->buildHtml() : ''));
    }

    /**
     * Checks whether access can be granted to a client, whether logged-in or not
     *
     * @param int $ticket_id The ID of the ticket
     * @param string $redirect_to The URL to redirect the user to on failure
     *  (optional, default null redirect to the client listing)
     * @return array A set of key/value pairs including:
     *  - allow_reply_by_url boolean true if access is granted for non-logged-in clients, false otherwise
     *  - ticket mixed An stdClass object representing the ticket, or false if one does not exist
     *  - sid mixed The hash code, if any
     */
    private function requireAccess($ticket_id, $redirect_to = null)
    {
        // Fetch the ticket
        $redirect = false;
        $allow_reply_by_url = false;
        $ticket = $this->SupportManagerTickets->get($ticket_id, true, ['reply', 'log']);
        $sid = (isset($this->get['sid']) ? $this->get['sid'] : (isset($this->post['sid']) ? $this->post['sid'] : null));

        if ($ticket) {
            // Login required for clients and closed tickets
            if ($ticket->client_id !== null) {
                $this->requireLogin();

                // Ticket must belong to this client
                if ($ticket->client_id != $this->client_id) {
                    $redirect = true;
                }
            } elseif (!$sid || !($department = $this->SupportManagerDepartments->get($ticket->department_id))
                || ($department->company_id != $this->company_id) || ($department->clients_only == '1')
            ) {
                // Not-logged-in clients either may not reply to this department, or did not provide the required hash
                $redirect = true;
            } else {
                // Validate hash in URL to allow replies to this ticket without logging in
                $params = [];
                $temp = explode('|', $this->SupportManagerTickets->systemDecrypt($sid));

                if (count($temp) > 1) {
                    foreach ($temp as $field) {
                        $field = explode('=', $field, 2);
                        if (count($field) >= 2) {
                            $params[$field[0]] = $field[1];
                        }
                    }
                }

                // Confirm whether the hash matches
                if (!isset($params['h']) || !isset($params['k'])
                    || $params['h'] != substr(
                        $this->SupportManagerTickets->generateReplyHash($ticket->id, $params['k']),
                        -16
                    )
                ) {
                    $redirect = true;
                } else {
                    $allow_reply_by_url = true;
                }
            }
        } else {
            $redirect = true;
        }

        // Redirect
        if ($redirect) {
            $this->redirect(($redirect_to ? $redirect_to : $this->base_uri . 'plugin/support_manager/client_tickets/'));
        }

        return [
            'allow_reply_by_url' => $allow_reply_by_url,
            'ticket' => $ticket,
            'sid' => $sid
        ];
    }

    /**
     * Reply to a ticket
     */
    public function reply()
    {
        // Ensure a valid ticket was given
        $redirect_url = $this->base_uri . 'plugin/support_manager/client_tickets/';
        if (!isset($this->get[0])) {
            $this->redirect($redirect_url);
        }

        // Process dropzone attachments
        if (isset($this->files['dropzone'])) {
            $this->processDropzoneAttachments();
        }
        if (isset($this->post['dropzone'])) {
            $this->files = $this->fetchDropzoneFiles();
        }

        // Require valid credentials be given
        $access = $this->requireAccess($this->get[0], $redirect_url);
        $ticket = $access['ticket'];

        $this->uses(['SupportManager.SupportManagerStaff']);

        $department = $this->SupportManagerDepartments->get($ticket->department_id);

        // Reply to the ticket
        if (!empty($this->post)) {
            // Set custom field checkboxes default value
            foreach ($department->fields ?? [] as $field) {
                if ($field->type == 'checkbox') {
                    if (!isset($this->post['custom_fields'][$field->id]) && $field->client_add == '1') {
                        $this->post['custom_fields'][$field->id] = '0';
                    }
                }
            }

            $data = $this->post;
            $data['type'] = 'reply';
            $data['staff_id'] = null;
            $data['contact_id'] = ($this->contact ? $this->contact->id : null);

            // Remove ability to change ticket options
            unset(
                $data['department_id'],
                $data['summary'],
                $data['priority'],
                $data['status'],
                $data['ticket_staff_id']
            );

            // If the ticket was previously awaiting this client's reply, or it was closed, change it back to open
            switch ($ticket->status) {
                case 'closed':
                case 'awaiting_reply':
                    $data['status'] = 'open';
                    break;
            }

            // Check whether the client is closing the ticket
            $close = false;
            if (!empty($data['action_type']) && $data['action_type'] == 'close') {
                $data['status'] = 'closed';
                $close = true;
            }

            // Discard custom fields that are already set
            foreach ($ticket->custom_fields as $field_id => $value) {
                if (!empty($value) && isset($data['custom_fields'][$field_id])) {
                    unset($data['custom_fields'][$field_id]);
                }
            }

            // Create a transaction
            $this->SupportManagerTickets->begin();

            // Add the reply
            $reply_id = $this->SupportManagerTickets->addReply($ticket->id, $data, $this->files);

            if (($errors = $this->SupportManagerTickets->errors())) {
                // Error, reset vars
                $this->SupportManagerTickets->rollBack();

                // Close the ticket if necessary
                if ($close && ($ticket = $this->SupportManagerTickets->get($ticket->id))
                    && $ticket->status != 'closed'
                ) {
                    $this->SupportManagerTickets->close($ticket->id);
                    $this->flashMessage(
                        'message',
                        Language::_('ClientTickets.!success.ticket_closed', true, $ticket->code),
                        null,
                        false
                    );
                    $this->redirect(
                        $this->base_uri . 'plugin/support_manager/client_tickets/'
                        . ($access['allow_reply_by_url']
                            ? 'reply/' . $ticket->id . '/?sid=' . rawurlencode($access['sid'])
                            : ''
                        )
                    );
                }

                $vars = (object)$this->post;
                $this->setMessage('error', $errors, false, null, false);
            } else {
                // Success, commit
                $this->SupportManagerTickets->commit();

                // Send the email associated with this ticket
                $this->SupportManagerTickets->sendEmail($reply_id);

                // Check whether the ticket was just closed and set the appropriate message
                if ($close && ($ticket = $this->SupportManagerTickets->get($ticket->id))
                    && $ticket->status == 'closed') {
                    $this->flashMessage(
                        'message',
                        Language::_('ClientTickets.!success.ticket_closed', true, $ticket->code),
                        null,
                        false
                    );
                } else {
                    $this->flashMessage(
                        'message',
                        Language::_('ClientTickets.!success.ticket_updated', true, $ticket->code),
                        null,
                        false
                    );
                }

                $this->redirect(
                    $this->base_uri . 'plugin/support_manager/client_tickets/'
                    . ($access['allow_reply_by_url']
                        ? 'reply/' . $ticket->id . '/?sid=' . rawurlencode($access['sid'])
                        : ''
                    )
                );
            }
        }

        // Include Dropzone.js
        $this->Javascript->setFile(
            'views/default/js/dropzone.js',
            'head',
            str_replace('/index.php', '', WEBDIR) . $this->view->view_path
        );

        // Load the Text Parser
        $this->helpers(['TextParser']);

        // Set vars if not set
        if (!isset($vars)) {
            $vars = $ticket;
        }

        // Make staff settings available for those staff that have replied to this ticket
        $staff_settings = [];
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
        }

        // Set department custom fields
        $input_fields = $this->formatDepartmentCustomFields($department->fields, $vars->custom_fields ?? []);
        $department_fields = new FieldsHtml($input_fields);

        $this->set('staff_settings', $staff_settings);

        $this->set('thumbnails_per_row', Configure::get('SupportManager.thumbnails_per_row'));
        $this->set('ticket', $ticket);
        $this->set('sid', $access['sid']);
        $this->set('vars', $vars);
        $this->set('statuses', $this->SupportManagerTickets->getStatuses());
        $this->set('priorities', $this->SupportManagerTickets->getPriorities());
        $this->set('department_fields', $department_fields->generate());
        $this->set('priority_classes', $this->getPriorityClasses());
    }

    /**
     * Closes the given ticket
     */
    public function close()
    {
        // Ensure a valid ticket was given
        $redirect_url = $this->base_uri . 'plugin/support_manager/client_tickets/';
        if (empty($this->post['id'])) {
            $this->redirect($redirect_url);
        }

        // Require valid credentials be given
        $access = $this->requireAccess($this->post['id'], $redirect_url);
        $ticket = $access['ticket'];

        // Close ticket if not done already
        if ($ticket && $ticket->status != 'closed') {
            $this->SupportManagerTickets->close($ticket->id);
            $this->flashMessage(
                'message',
                Language::_('ClientTickets.!success.ticket_closed', true, $ticket->code),
                null,
                false
            );
        }
        $this->redirect(
            $redirect_url
            . ($access['allow_reply_by_url'] ? 'reply/' . $ticket->id . '/?sid=' . rawurlencode($access['sid']) : '')
        );
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
        if (!$this->isAjax() || !($department = $this->SupportManagerDepartments->get($this->get[0])) ||
            $department->company_id != $this->company_id || $department->status != 'visible') {
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
     * Streams an attachment to view
     */
    public function getAttachment()
    {
        // Ensure a valid attachment was given
        if (!isset($this->get[0]) || !($attachment = $this->SupportManagerTickets->getAttachment($this->get[0])) ||
            !isset($attachment->ticket_id)) {
            $this->redirect($this->base_uri . 'plugin/support_manager/client_tickets/');
        }

        // Require valid credentials be given
        $this->requireAccess($attachment->ticket_id, null);

        $this->components(['Download']);

        $this->Download->downloadFile($attachment->file_name, $attachment->name);
        return false;
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
            Language::_('ClientTickets.index.field_ticket_number', true),
            'ticket_number'
        );
        $ticket_number->attach(
            $filters->fieldText(
                'filters[ticket_number]',
                isset($vars['ticket_number']) ? $vars['ticket_number'] : null,
                [
                    'id' => 'ticket_number',
                    'class' => 'form-control stretch',
                    'placeholder' => Language::_('ClientTickets.index.field_ticket_number', true)
                ]
            )
        );
        $filters->setField($ticket_number);

        // Set priority filter
        $priorities = $this->SupportManagerTickets->getPriorities();
        $priority = $filters->label(
            Language::_('ClientTickets.index.field_priority', true),
            'priority'
        );
        $priority->attach(
            $filters->fieldSelect(
                'filters[priority]',
                ['' => Language::_('ClientTickets.index.any', true)] + $priorities,
                isset($vars['priority']) ? $vars['priority'] : null,
                ['id' => 'priority']
            )
        );
        $filters->setField($priority);

        // Set summary filter
        $summary = $filters->label(
            Language::_('ClientTickets.index.field_summary', true),
            'summary'
        );
        $summary->attach(
            $filters->fieldText(
                'filters[summary]',
                isset($vars['summary']) ? $vars['summary'] : null,
                [
                    'id' => 'summary',
                    'class' => 'form-control stretch',
                    'placeholder' => Language::_('ClientTickets.index.field_summary', true)
                ]
            )
        );
        $filters->setField($summary);

        // Set last reply filter
        $time_options = [];
        $minutes = [15, 30];
        $hours = [1, 6, 12, 24, 72];

        foreach ($minutes as $minute) {
            $time_options[$minute] = Language::_('ClientTickets.index.minutes', true, $minute);
        }

        foreach ($hours as $hour) {
            $time_options[$hour * 60] = Language::_('ClientTickets.index.' . ($hour == 1 ? 'hour' : 'hours'), true, $hour);
        }

        $last_reply = $filters->label(
            Language::_('ClientTickets.index.field_last_reply', true),
            'last_reply'
        );
        $last_reply->attach(
            $filters->fieldSelect(
                'filters[last_reply]',
                ['' => Language::_('ClientTickets.index.any', true)] + $time_options,
                isset($vars['last_reply']) ? $vars['last_reply'] : null,
                ['id' => 'last_reply']
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
            // Skip field if visible only to staff members
            if ($field->visibility == 'staff') {
                continue;
            }

            // Disable field if client can't add or a value is already set
            $disabled = [];
            if ($field->client_add !== '1' || ($field->client_add == '1' && !empty($vars[$field->id]))) {
                $disabled = ['disabled' => 'disabled'];
            }

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

            // Set placeholder if the field is encrypted and not empty
            if (!empty($vars[$field->id]) && ($field->encrypted ?? '0') == '1') {
                $custom_fields = $input_fields->label($field->label);
                $input_fields->setField(
                    $custom_fields->attach(
                        $input_fields->fieldText(
                            '',
                            Language::_('ClientTickets.formatDepartmentCustomFields.encrypted_field', true),
                            ['disabled' => 'disabled'],
                            $input_fields->label($field->description, null)
                        )
                    )
                );
                continue;
            }

            // Build text and textarea field
            switch ($field->type) {
                case 'text':
                    $custom_fields = $input_fields->label($field->label, 'custom_fields_' . $field->id, $disabled);
                    $input_fields->setField(
                        $custom_fields->attach(
                            $input_fields->fieldText(
                                'custom_fields[' . $field->id . ']',
                                $vars[$field->id] ?? '',
                                array_merge(['id' => 'custom_fields_' . $field->id], $disabled),
                                $input_fields->label($field->description, null, $disabled)
                            )
                        )
                    );
                    break;
                case 'quantity':
                    $custom_fields = $input_fields->label($field->label, 'custom_fields_' . $field->id, $disabled);
                    $input_fields->setField(
                        $custom_fields->attach(
                            $input_fields->fieldText(
                                'custom_fields[' . $field->id . ']',
                                $vars[$field->id] ?? $field->min ?? 0,
                                array_merge([
                                    'id' => 'custom_fields_' . $field->id,
                                    'class' => 'quantity_slider',
                                    'data-slider-min' => $field->min ?? 0,
                                    'data-slider-max' => $field->max ?? 0,
                                    'data-slider-step' => $field->step ?? 1,
                                    'data-slider-value' => $vars[$field->id] ?? 0
                                ], $disabled),
                                $input_fields->label($field->description, null, $disabled)
                            )
                        )
                    );
                    break;
                case 'textarea':
                    $custom_fields = $input_fields->label($field->label, 'custom_fields_' . $field->id, $disabled);
                    $input_fields->setField(
                        $custom_fields->attach(
                            $input_fields->fieldTextarea(
                                'custom_fields[' . $field->id . ']',
                                $vars[$field->id] ?? '',
                                array_merge(['id' => 'custom_fields_' . $field->id], $disabled),
                                $input_fields->label($field->description, null, $disabled)
                            )
                        )
                    );
                    break;
                case 'password':
                    $custom_fields = $input_fields->label($field->label, 'custom_fields_' . $field->id, $disabled);
                    $input_fields->setField(
                        $custom_fields->attach(
                            $input_fields->fieldPassword(
                                'custom_fields[' . $field->id . ']',
                                array_merge(['id' => 'custom_fields_' . $field->id], $disabled),
                                $input_fields->label($field->description, null, $disabled)
                            )
                        )
                    );
                    break;
                case 'select':
                    $options = $this->formatCustomFieldOptions($field->options ?? []);
                    $custom_fields = $input_fields->label($field->label, 'custom_fields_' . $field->id, $disabled);

                    $input_fields->setField(
                        $custom_fields->attach(
                            $input_fields->fieldSelect(
                                'custom_fields[' . $field->id . ']',
                                $options['options'] ?? [],
                                $vars[$field->id] ?? $options['default'] ?? null,
                                array_merge(['id' => 'custom_fields_' . $field->id], $disabled),
                                [],
                                $input_fields->label($field->description, null, $disabled)
                            )
                        )
                    );
                    break;
                case 'radio':
                    $options = $this->formatCustomFieldOptions($field->options ?? []);
                    $custom_fields = $input_fields->label($field->label, 'custom_fields_' . $field->id, $disabled);

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
                                array_merge(['id' => 'custom_fields_' . $field->id . '_' . $value], $disabled),
                                $input_fields->label($name, 'custom_fields_' . $field->id . '_' . $value, $disabled)
                            )
                        );
                    }

                    $input_fields->setField($custom_fields);
                    break;
                case 'checkbox':
                    $custom_fields = $input_fields->label($field->description, null, $disabled);
                    $input_fields->setField(
                        $custom_fields->attach(
                            $input_fields->fieldCheckbox(
                                'custom_fields[' . $field->id . ']',
                                '1',
                                ($vars[$field->id] ?? '0') == '1',
                                array_merge(['id' => 'custom_fields_' . $field->id], $disabled),
                                $input_fields->label($field->label, 'custom_fields_' . $field->id, $disabled)
                            )
                        )
                    );
                    break;
            }
        }

        $input_fields->setHtml("
            <script type=\"text/javascript\">
                $(document).ready(function() {
                    if ($('.quantity_slider').length) {
                        $('.quantity_slider').slider({
                            tooltip: 'show'
                        });
                        $('.quantity_slider[disabled]').parent().find('.slider').attr('style', 'opacity: 0.5; pointer-events: none;');
                    }
                });
            </script>
        ");

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
