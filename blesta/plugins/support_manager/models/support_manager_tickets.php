<?php
/**
 * SupportManagerTickets model
 *
 * @package blesta
 * @subpackage blesta.plugins.support_manager
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SupportManagerTickets extends SupportManagerModel
{
    /**
     * The system-level staff ID
     */
    private $system_staff_id = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        Configure::load('mime', dirname(__FILE__) . DS . '..' . DS . 'config' . DS);
        Language::loadLang('support_manager_tickets', null, PLUGINDIR . 'support_manager' . DS . 'language' . DS);
    }

    /**
     * Adds a support ticket
     *
     * @param array $vars A list of ticket vars, including:
     *  - department_id The ID of the department to assign this ticket
     *  - staff_id The ID of the staff member this ticket is assigned to (optional)
     *  - service_id The ID of the service this ticket is related to (optional)
     *  - client_id The ID of the client this ticket is assigned to (optional)
     *  - email The email address that a ticket was emailed in from (optional)
     *  - summary A brief title/summary of the ticket issue
     *  - priority The ticket priority (i.e. "emergency", "critical", "high", "medium", "low") (optional, default "low")
     *  - status The status of the ticket
     *  (i.e. "open", "awaiting_reply", "in_progress", "on_hold", "closed", "trash") (optional, default "open")
     *  - custom_fields An array containing the ticket custom fields, where the key is the field id
     * @param bool $require_email True to require the email field be given,
     *  false otherwise (optional, default false)
     * @return mixed The ticket ID, or null on error
     */
    public function add(array $vars, $require_email = false)
    {
        // Generate a ticket number
        $vars['code'] = $this->generateCode();

        if (isset($vars['staff_id']) && $vars['staff_id'] == '') {
            $vars['staff_id'] = null;
        }
        if (isset($vars['service_id']) && $vars['service_id'] == '') {
            $vars['service_id'] = null;
        }

        $vars['date_updated'] = $vars['date_added'] = date('c');
        $this->Input->setRules($this->getRules($vars, false, $require_email));

        if ($this->Input->validates($vars)) {
            // Add the support ticket
            $fields = ['code', 'department_id', 'staff_id', 'service_id', 'client_id',
                'email', 'summary', 'priority', 'status', 'date_added', 'date_updated'];
            $this->Record->insert('support_tickets', $vars, $fields);

            $ticket_id = $this->Record->lastInsertId();
            if ($ticket_id) {
                $this->addCustomFields($ticket_id, $vars['custom_fields'] ?? []);
            }

            return $ticket_id;
        }
    }

    /**
     * Adds the custom field values associated with the ticket
     *
     * @param int $ticket_id The ID of the ticket to associate with the custom fields
     * @param array $custom_fields An array containing the ticket custom fields, where the key is the field id
     */
    private function addCustomFields(int $ticket_id, array $custom_fields)
    {
        if (!empty($custom_fields)) {
            foreach ($custom_fields as $field_id => $value) {
                $field = $this->Record->select()
                    ->from('support_department_fields')
                    ->where('id', '=', $field_id)
                    ->fetch();

                $vars = [
                    'ticket_id' => $ticket_id,
                    'field_id' => $field_id,
                    'value' => $value,
                    'encrypted' => $field->encrypted ?? '0'
                ];

                if (($field->encrypted ?? '0') == '1') {
                    $vars['value'] = $this->systemEncrypt($vars['value']);
                }

                $fields = ['ticket_id', 'field_id', 'value', 'encrypted'];
                $this->Record->insert('support_ticket_fields', $vars, $fields);
            }
        }
    }

    /**
     * Updates a support ticket
     *
     * @param int $ticket_id The ID of the ticket to update
     * @param array $vars A list of ticket vars, including (all optional):
     *  - department_id The department to reassign the ticket to
     *  - staff_id The ID of the staff member to assign the ticket to
     *  - service_id The ID of the client service this ticket relates to
     *  - client_id The ID of the client this ticket is to be assigned to (can only be set if it is currently null)
     *  - summary A brief title/summary of the ticket issue
     *  - priority The ticket priority (i.e. "emergency", "critical", "high", "medium", "low")
     *  - status The status of the ticket (i.e. "open", "awaiting_reply", "in_progress", "on_hold", "closed", "trash")
     *  - by_staff_id The ID of the staff member performing the edit
     *      (optional, defaults to null to signify the edit is performed by the client)
     *  - custom_fields An array containing the ticket custom fields, where the key is the field id
     * @param bool $log True to update the ticket for any loggable changes,
     *  false to explicitly not log changes (optional, default true)
     * @return stdClass An stdClass object representing the ticket (without replies)
     */
    public function edit($ticket_id, array $vars, $log = true)
    {
        $vars['ticket_id'] = $ticket_id;

        if (isset($vars['staff_id']) && $vars['staff_id'] == '') {
            $vars['staff_id'] = null;
        }
        if (isset($vars['service_id']) && $vars['service_id'] == '') {
            $vars['service_id'] = null;
        }

        $vars['date_updated'] = date('c');
        $this->Input->setRules($this->getRules($vars, true));

        // Update the ticket
        if ($this->Input->validates($vars)) {
            $fields = ['department_id', 'staff_id', 'service_id', 'client_id', 'summary',
                'priority', 'status', 'date_updated'];

            // Allow the date closed to be set
            if (isset($vars['status'])) {
                $fields[] = 'date_closed';
                if ($vars['status'] == 'closed') {
                    if (empty($vars['date_closed'])) {
                        $vars['date_closed'] = $this->dateToUtc(date('c'));
                    }
                } else {
                    $vars['date_closed'] = null;
                }
            }

            // Log any changes and update the ticket
            if ($log) {
                $log_vars = [
                    'type' => 'log',
                    'by_staff_id' => (isset($vars['by_staff_id']) ? $vars['by_staff_id'] : null)
                ];

                foreach ([
                    'by_staff_id', 'department_id', 'summary', 'priority', 'status', 'ticket_staff_id'
                ] as $field) {
                    if (isset($vars[$field])) {
                        $log_vars[$field] = $vars[$field];
                    }
                }

                // Set the staff that updated the ticket
                if (array_key_exists('by_staff_id', $log_vars)) {
                    $log_vars['staff_id'] = $log_vars['by_staff_id'];
                    unset($log_vars['by_staff_id']);
                }

                $this->addReply($ticket_id, $log_vars);

                // Adding the reply does not update client_id, nor service_id, so update those manually
                $ticket_vars = [];
                if (isset($vars['client_id'])) {
                    $ticket_vars['client_id'] = $vars['client_id'];
                }
                if (isset($vars['service_id'])) {
                    $ticket_vars['service_id'] = $vars['service_id'];
                }

                if (!empty($ticket_vars)) {
                    $this->Record->where('id', '=', $ticket_id)->update('support_tickets', $ticket_vars, $fields);
                }
            } else {
                // Only update the ticket
                $this->Record->where('id', '=', $ticket_id)->update('support_tickets', $vars, $fields);
            }

            // Update custom fields
            $this->editCustomFields($ticket_id, $vars['custom_fields'] ?? []);

            if ($vars['status'] == 'closed') {
                // Delete fields marked as auto-delete after closing the ticket
                $this->Record->from('support_ticket_fields')
                    ->innerJoin('support_department_fields', 'support_department_fields.id', '=', 'support_ticket_fields.field_id', false)
                    ->where('support_department_fields.auto_delete', '=', '1')
                    ->where('support_ticket_fields.ticket_id', '=', $ticket_id)
                    ->delete(['support_ticket_fields.*']);
            }

            return $this->get($ticket_id, false);
        }
    }

    /**
     * Updates the custom field values associated with the ticket
     *
     * @param int $ticket_id The ID of the ticket to associate with the custom fields
     * @param array $custom_fields An array containing the ticket custom fields, where the key is the field id
     */
    private function editCustomFields(int $ticket_id, array $custom_fields)
    {
        if (!empty($custom_fields)) {
            foreach ($custom_fields as $field_id => $value) {
                $field = $this->Record->select()
                    ->from('support_department_fields')
                    ->where('id', '=', $field_id)
                    ->fetch();

                $vars = [
                    'field_id' => $field_id,
                    'ticket_id' => $ticket_id,
                    'value' => $value,
                    'encrypted' => $field->encrypted ?? '0'
                ];
                if (($field->encrypted ?? '0') == '1') {
                    $vars['value'] = $this->systemEncrypt($vars['value']);
                }

                // Skip if the field type is password and the value is empty
                if (($field->type ?? 'text') == 'password' && empty($value)) {
                    continue;
                }

                // Add/update custom field
                $fields = ['field_id', 'ticket_id', 'value', 'encrypted'];
                $this->Record->duplicate('value', '=', $vars['value'])->
                    duplicate('encrypted', '=', $vars['encrypted'])->
                    insert('support_ticket_fields', $vars, $fields);
            }
        }

        // Remove values from deleted fields
        $this->Record->from('support_ticket_fields')
            ->leftJoin('support_department_fields', 'support_department_fields.id', '=', 'support_ticket_fields.field_id', false)
            ->where('support_department_fields.id', '=', null)
            ->where('support_ticket_fields.ticket_id', '=', $ticket_id)
            ->delete(['support_ticket_fields.*']);
    }

    /**
     * Reassigns tickets to the given client
     *
     * @param array $vars A list of input variables including:
     *  - ticket_ids An array of ticket IDs to reassign
     *  - client_id The client to reassign the ticket to
     *  - staff_id The staff performing this action
     */
    public function reassignTickets(array $vars)
    {
        Loader::loadModels($this, ['Clients']);
        $rules = [
            'ticket_ids[]' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'support_tickets'],
                    'message' => $this->_('SupportManagerTickets.!error.ticket_ids[].exists')
                ]
            ],
            'client_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => $this->_('SupportManagerTickets.!error.client_id.exists')
                ],
                'company' => [
                    'rule' => [
                        function ($client_id, $ticket_ids) {
                            if (!is_array($ticket_ids)) {
                                return false;
                            }

                            return count($ticket_ids) == $this->Record->select('clients.id')->
                                from('clients')->
                                innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
                                innerJoin(
                                    'support_departments',
                                    'support_departments.company_id',
                                    '=',
                                    'client_groups.company_id',
                                    false
                                )->
                                innerJoin(
                                    'support_tickets',
                                    'support_tickets.department_id',
                                    '=',
                                    'support_departments.id',
                                    false
                                )->
                                where('clients.id', '=', $client_id)->
                                where('support_tickets.id', 'in', $ticket_ids)->
                                numResults();
                        },
                        ['_linked' => 'ticket_ids'],
                    ],
                    'message' => $this->_('SupportManagerTickets.!error.client_id.company')
                ]
            ],
            'staff_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'staff'],
                    'message' => $this->_('SupportManagerTickets.!error.staff_id.exists')
                ]
            ]
        ];

        $this->Input->setRules($rules);

        $errors = [];
        if ($this->Input->validates($vars)) {
            $client = $this->Clients->get($vars['client_id']);

            $this->Record->begin();
            $this->Record->where('id', 'in', $vars['ticket_ids'])->
                update('support_tickets', ['client_id' => $client->id, 'email' => null]);

            $this->Record->innerJoin('contacts', 'contacts.client_id', '=', 'support_replies.client_id')->
                where('support_replies.ticket_id', 'in', $vars['ticket_ids'])->
                update('support_replies', ['contact_id' => ['value' => 'contacts.id', 'bind_value' => false]]);

            foreach ($vars['ticket_ids'] as $ticket_id) {
                $this->addReply(
                    $ticket_id,
                    [
                        'type' => 'note',
                        'details' => Language::_(
                            'SupportManagerTickets.reassign_note',
                            true,
                            $client->first_name . ' ' . $client->last_name
                        ),
                        'staff_id' => $vars['staff_id']
                    ]
                );

                if (($errors = $this->Input->errors())) {
                    $this->Record->rollback();
                    break;
                }
            }

            if (empty($errors)) {
                $this->Record->commit();
            }
        }
    }

    /**
     * Updates multiple support tickets at once
     * @see SupportManagerTickets::edit()
     *
     * @param array $ticket_ids An array of ticket IDs to update
     * @param array $vars An array consisting of arrays of ticket vars whose index refers to the
     *  index of the $ticket_ids array representing the vars of the specific ticket to update;
     *  or an array of vars to apply to all tickets; each including (all optional):
     *  - department_id The department to reassign the ticket to
     *  - staff_id The ID of the staff member to assign the ticket to
     *  - service_id The ID of the client service this ticket relates to
     *  - client_id The ID of the client this ticket is to be assigned to (can only be set if it is currently null)
     *  - summary A brief title/summary of the ticket issue
     *  - priority The ticket priority (i.e. "emergency", "critical", "high", "medium", "low")
     *  - status The status of the ticket (i.e. "open", "awaiting_reply", "in_progress", "on_hold", "closed", "trash")
     *  - by_staff_id The ID of the staff member performing the edit
     *      (optional, defaults to null to signify the edit is performed by the client)
     */
    public function editMultiple(array $ticket_ids, array $vars)
    {
        // Determine whether to apply vars to all tickets, or whether each ticket has separate vars
        $separate_vars = (isset($vars[0]) && is_array($vars[0]));

        $rules = [
            'tickets' => [
                // Check whether the tickets can be assigned to the given service(s)
                'service_matches' => [
                    'rule' => [[$this, 'validateServicesMatchTickets'], $ticket_ids],
                    'message' => $this->_('SupportManagerTickets.!error.tickets.service_matches')
                ],
                // Check whether the tickets can be assigned to the given department(s)
                'department_matches' => [
                    'rule' => [[$this, 'validateDepartmentsMatchTickets'], $ticket_ids],
                    'message' => $this->_('SupportManagerTickets.!error.tickets.department_matches')
                ]
            ]
        ];

        $multiple_vars = ['tickets' => $vars];

        $this->Input->setRules($rules);
        if ($this->Input->validates($multiple_vars)) {
            // Validate each ticket individually
            foreach ($ticket_ids as $key => $ticket_id) {
                // Each ticket has separate vars
                $temp_vars = $vars;
                if ($separate_vars) {
                    // Since all fields are optional, we don't need to require any vars be given for every ticket
                    // and they will simply not be updated at all
                    if (!isset($vars[$key]) || empty($vars[$key])) {
                        $vars[$key] = [];
                    }

                    $temp_vars = $vars[$key];
                }

                // Validate an individual ticket
                $temp_vars['ticket_id'] = $ticket_id;
                $this->Input->setRules($this->getRules($temp_vars, true));
                if (!$this->Input->validates($temp_vars)) {
                    return;
                }
            }

            // All validation passed, update all tickets accordingly
            foreach ($ticket_ids as $key => $ticket_id) {
                $temp_vars = $vars;
                if ($separate_vars) {
                    $temp_vars = $vars[$key];
                }

                $this->edit($ticket_id, $temp_vars);
            }
        }
    }

    /**
     * Closes a ticket and logs that it has been closed
     *
     * @param int $ticket_id The ID of the ticket to close
     * @param int $staff_id The ID of the staff that closed the ticket
     *  (optional, default null if client closed the ticket)
     */
    public function close($ticket_id, $staff_id = null)
    {
        // Update the ticket to closed
        $vars = ['status' => 'closed', 'date_closed' => date('c')];

        // Set who closed the ticket
        if ($staff_id !== null) {
            $vars['by_staff_id'] = $staff_id;
        }

        // Set the current assigned ticket staff member as the staff member on edit, so that it does not get removed
        $ticket = $this->get($ticket_id, false);
        if ($ticket) {
            $vars['staff_id'] = $ticket->staff_id;
        }

        $this->edit($ticket_id, $vars);

        // Delete fields marked as auto-delete after closing the ticket
        $this->Record->from('support_ticket_fields')
            ->innerJoin('support_department_fields', 'support_department_fields.id', '=', 'support_ticket_fields.field_id', false)
            ->where('support_department_fields.auto_delete', '=', '1')
            ->where('support_ticket_fields.ticket_id', '=', $ticket_id)
            ->delete(['support_ticket_fields.*']);
    }

    /**
     * Closes all open tickets (not "in_progress") based on the department settings
     *
     * @param int $department_id The ID of the department whose tickets to close
     */
    public function closeAllByDepartment($department_id)
    {
        Loader::loadModels($this, ['Companies', 'SupportManager.SupportManagerDepartments']);

        $department = $this->SupportManagerDepartments->get($department_id);
        if ($department && $department->close_ticket_interval !== null) {
            $reply = '';
            if ($department->response_id !== null) {
                $response = $this->Record->select()->from('support_responses')->
                    where('id', '=', $department->response_id)->fetch();
                $reply = ($response ? $response->details : '');
            }

            $company = $this->Companies->get($department->company_id);
            $hostname = isset($company->hostname) ? $company->hostname : '';
            $last_reply_date = $this->dateToUtc(
                date('c', strtotime('-' . abs($department->close_ticket_interval) . ' minutes'))
            );

            $sub_query = $this->Record->select(['MAX(support_replies.id)'])->from('support_replies')->
                where('support_replies.ticket_id', '=', 'support_tickets.id', false)->
                where('support_replies.type', '=', 'reply')->get();
            $values = $this->Record->values;
            $this->Record->reset();

            $tickets = $this->Record->select(['support_tickets.id'])->
                from('support_replies')->
                innerJoin('support_tickets', 'support_replies.ticket_id', '=', 'support_tickets.id', false)->
                appendValues($values)->
                where('support_replies.id', 'in', [$sub_query], false)->
                where('support_tickets.department_id', '=', $department->id)->
                where('support_tickets.status', 'notin', ['in_progress', 'on_hold', 'closed', 'trash'])->
                where('support_replies.type', '=', 'reply')->
                where('support_replies.staff_id', '!=', null)->
                where('support_replies.date_added', '<=', $last_reply_date)->
                fetchAll();

            // Close the tickets
            foreach ($tickets as $ticket) {
                // Add any reply and email, and close the ticket
                $this->staffReplyEmail($reply, $ticket->id, $hostname, $this->system_staff_id);
                $this->close($ticket->id, $this->system_staff_id);
            }
        }
    }

    /**
     * Permanently deletes the given tickets and everything associated with them
     *
     * @param array $ticket_ids A list of tickets to delete
     */
    public function delete(array $ticket_ids)
    {
        if (!empty($ticket_ids)) {
            $this->Record->from('support_tickets')
                ->leftJoin('support_replies', 'support_replies.ticket_id', '=', 'support_tickets.id', false)
                ->leftJoin('support_attachments', 'support_attachments.reply_id', '=', 'support_replies.id', false)
                ->leftJoin('support_ticket_fields', 'support_ticket_fields.ticket_id', '=', 'support_tickets.id', false)
                ->where('support_tickets.id', 'in', $ticket_ids)
                ->delete(['support_tickets.*','support_replies.*','support_attachments.*', 'support_ticket_fields.*']);
        }
    }

    /**
     * Sends a reminder for the given tickets
     *
     * @param array $ticket_ids A list of tickets for which to send a reminder
     */
    public function sendReminder(array $ticket_ids)
    {
        Loader::loadModels($this, [
            'Clients',
            'Contacts',
            'Staff',
            'Emails',
            'SupportManager.SupportManagerDepartments',
            'SupportManager.SupportManagerStaff'
        ]);
        Loader::loadHelpers($this, ['Form']);

        if (!empty($ticket_ids)) {
            foreach ($ticket_ids as $ticket_id) {
                // Get ticket
                $ticket = $this->get($ticket_id);

                // Get department
                $department = $this->SupportManagerDepartments->get($ticket->department_id);

                // Get last reply
                $replies = $this->getReplies($ticket_id, ['reply']);
                $last_reply = reset($replies);

                // Get staff emails
                $staff_emails = [];
                if (!empty($ticket->staff_id)) {
                    $staff = $this->Staff->get($ticket->staff_id, $ticket->company_id);
                    $staff->settings = $this->Form->collapseObjectArray($staff->settings, 'value', 'key');
                    $staff_emails[$staff->id] = $staff->email;
                } else {
                    $staff = $this->SupportManagerStaff->getAllAvailable($ticket->company_id, $ticket->department_id);
                    foreach ($staff as $available_staff) {
                        $staff_emails[$available_staff->id] = $available_staff->email;
                    }
                }

                // Get client emails
                $client = $this->Clients->get($ticket->client_id);
                $client_emails = [];
                if (!empty($client->email)) {
                    $client_emails[] = $client->email;
                }

                // Send reminders
                if (empty($last_reply->staff_id)) {
                    // Send reminder to Staff
                    $email_action = 'SupportManager.staff_ticket_reminder';

                    foreach ($staff_emails as $staff_id => $staff_email) {
                        $staff = $this->Staff->get($staff_id, $ticket->company_id);
                        $language = isset($staff->settings['language'])
                            ? $staff->settings['language']
                            : Configure::get('Blesta.language');
                        $tags = ['ticket' => $ticket, 'staff' => $staff];

                        $this->Emails->send(
                            $email_action,
                            $ticket->company_id,
                            $language,
                            [$staff->email],
                            array_merge($tags, $this->getCustomFieldsEmailTags($ticket->id ?? null))
                        );
                    }
                } else {
                    // Send reminder to Client
                    $email_action = 'SupportManager.ticket_reminder';
                    $language = isset($client->settings['language'])
                        ? $client->settings['language']
                        : Configure::get('Blesta.language');
                    $tags = ['ticket' => $ticket, 'client' => $client];
                    $emails = $client_emails;
                    $options = [
                        'to_client_id' => $ticket->client_id,
                        'from_staff_id' => isset($staff->id) ? $staff->id : null,
                        'reply_to' => $department->email
                    ];

                    $this->Emails->send(
                        $email_action,
                        $ticket->company_id,
                        $language,
                        $emails,
                        array_merge($tags, $this->getCustomFieldsEmailTags($ticket->id ?? null)),
                        null,
                        null,
                        null,
                        $options
                    );
                }

                // Log reminder
                $this->Record->insert('support_reminders', [
                    'ticket_id' => $ticket->id,
                    'status' => $ticket->status,
                    'date_sent' => $this->dateToUtc(date('c'))
                ]);
            }
        }
    }

    /**
     * Deletes all trash ticket based on the department settings
     *
     * @param int $department_id The ID of the department whose tickets to close
     */
    public function deleteAllByDepartment($department_id)
    {
        Loader::loadModels($this, ['Companies', 'SupportManager.SupportManagerDepartments']);

        $department = $this->SupportManagerDepartments->get($department_id);
        if ($department && $department->delete_ticket_interval !== null) {
            $deletion_cutoff = $this->dateToUtc($this->Date->modify(
                date('c'),
                '-' . abs($department->delete_ticket_interval) . ' minutes',
                'c',
                Configure::get('Blesta.company_timezone')
            ));

            $tickets = $this->Record->select('support_tickets.id')
                ->from('support_tickets')
                ->where('support_tickets.department_id', '=', $department->id)
                ->where('support_tickets.date_updated', '<=', $deletion_cutoff)
                ->where('support_tickets.status', '=', 'trash')
                ->fetchAll();

            $deletable_tickets = [];
            foreach ($tickets as $ticket) {
                $deletable_tickets[] = $ticket->id;
            }

            $this->delete($deletable_tickets);
        }
    }

    /**
     * Sends all tickets a reminder based on the department settings
     *
     * @param int $department_id The ID of the department whose tickets to notify
     */
    public function notifyAllByDepartment($department_id)
    {
        Loader::loadModels($this, ['Companies', 'SupportManager.SupportManagerDepartments']);

        $department = $this->SupportManagerDepartments->get($department_id);
        if ($department && $department->reminder_ticket_interval !== null) {
            $reminder_cutoff = $this->dateToUtc($this->Date->modify(
                date('c'),
                '-' . abs($department->reminder_ticket_interval) . ' minutes',
                'c',
                Configure::get('Blesta.company_timezone')
            ));
            $statuses = !empty($department->reminder_ticket_status)
                ? array_keys($department->reminder_ticket_status)
                : [];
            $priorities = !empty($department->reminder_ticket_priority)
                ? array_keys($department->reminder_ticket_priority)
                : [];

            // Build last reply subquery
            $subquery_record = clone $this->Record;
            $subquery_record->reset();
            $subquery = $subquery_record->select([
                'support_replies.ticket_id',
                'MAX(support_replies.date_added)' => 'last_reply'
            ])
                ->from('support_replies')
                ->where('support_replies.type', '=', 'reply')
                ->group('support_replies.ticket_id')
                ->get();
            $subquery_values = $subquery_record->values;

            // Build last notification subquery
            $subquery_notifications_record = clone $this->Record;
            $subquery_notifications_record->reset();
            $subquery_notifications = $subquery_notifications_record->select([
                'support_reminders.ticket_id',
                'support_reminders.status',
                'MAX(support_reminders.date_sent)' => 'last_reminder'
            ])
                ->from('support_reminders')
                ->group('support_reminders.ticket_id')
                ->get();

            // Fetch pending tickets to be notified
            $this->Record->select('support_tickets.id')
                ->from('support_tickets')
                ->innerJoin(
                    [$subquery => 'support_replies'],
                    'support_replies.ticket_id',
                    '=',
                    'support_tickets.id',
                    false
                )
                ->appendValues($subquery_values)
                ->leftJoin(
                    [$subquery_notifications => 'support_reminders'],
                    'support_reminders.ticket_id',
                    '=',
                    'support_tickets.id',
                    false
                )
                ->where('support_tickets.department_id', '=', $department->id)
                ->where('support_replies.last_reply', '<=', $reminder_cutoff)
                ->open()
                    ->where('support_reminders.last_reminder', '<', 'support_replies.last_reply', false)
                    ->orWhere('support_reminders.status', '=', null)
                ->close();

            if (!empty($statuses)) {
                $this->Record->where('support_tickets.status', 'in', $statuses);
            }

            if (!empty($priorities)) {
                $this->Record->where('support_tickets.priority', 'in', $priorities);
            }

            $tickets = $this->Record->fetchAll();

            $notify_tickets = [];
            foreach ($tickets as $ticket) {
                $notify_tickets[] = $ticket->id;
            }

            $this->sendReminder($notify_tickets);
        }
    }

    /**
     * Adds a reply to a ticket. If ticket data (e.g. department_id, status, priority, summary) have changed
     * then this will also invoke SupportManagerTickets::edit() to update the ticket, and record any log entries.
     *
     * Because of this functionality, this method is assumed to (and should) already be in a transaction when called,
     * and SupportManagerTickets::edit() should not be called separately.
     *
     * @param int $ticket_id The ID of the ticket to reply to
     * @param array $vars A list of reply vars, including:
     *  - staff_id The ID of the staff member this reply is from (optional)
     *  - client_id The ID of the client this reply is from (optional)
     *  - contact_id The ID of a client's contact that this reply is from (optional)
     *  - type The type of reply (i.e. "reply, "note", "log") (optional, default "reply")
     *  - details The details of the ticket (optional)
     *  - department_id The ID of the ticket department (optional)
     *  - summary The ticket summary (optional)
     *  - priority The ticket priority (optional)
     *  - status The ticket status (optional)
     *  - ticket_staff_id The ID of the staff member the ticket is assigned to (optional)
     *  - custom_fields An array containing the ticket custom fields, where the key is the field id
     * @param array $files A list of file attachments that matches the global FILES array,
     *  which contains an array of "attachment" files
     * @param bool $new_ticket True if this reply is apart of ticket being created, false otherwise (default false)
     * @return int The ID of the ticket reply on success, void on error
     */
    public function addReply($ticket_id, array $vars, array $files = null, $new_ticket = false)
    {
        $vars['ticket_id'] = $ticket_id;
        $vars['date_added'] = date('c');
        if (!isset($vars['type'])) {
            $vars['type'] = 'reply';
        }

        // Remove reply details if it contains only the signature
        if (isset($vars['details']) && isset($vars['staff_id'])) {
            if (!isset($this->SupportManagerStaff)) {
                Loader::loadModels($this, ['SupportManager.SupportManagerStaff']);
            }

            $staff_settings = $this->SupportManagerStaff->getSettings(
                $vars['staff_id'],
                Configure::get('Blesta.company_id')
            );
            if (isset($staff_settings['signature']) && trim($staff_settings['signature']) == trim($vars['details'])) {
                $vars['details'] = '';
            }
        }

        // Determine whether or not options have changed that need to be logged
        $log_options = [];
        // "status" should be the last element in case it is set to closed, so it will be the last log entry added
        $loggable_fields = ['department_id' => 'department_id', 'ticket_staff_id' => 'staff_id', 'summary' => 'summary',
            'priority' => 'priority', 'status' => 'status'];

        if (!$new_ticket
            && (
                isset($vars['department_id']) || isset($vars['summary'])
                || isset($vars['priority']) || isset($vars['status'])
                || isset($vars['ticket_staff_id'])
            )
        ) {
            if (($ticket = $this->get($ticket_id, false))) {
                // Determine if any log replies need to be made
                foreach ($loggable_fields as $key => $option) {
                    // Save to be logged iff the field has been changed
                    if (isset($vars[$key]) && property_exists($ticket, $option) && $ticket->{$option} != $vars[$key]) {
                        $log_options[] = $key;
                    }
                }
            }
        }

        // Check whether logs are being added simultaneously, and if so, do not
        // add a reply iff no reply details, nor files, are attached
        // i.e. allow log entries to be added without a reply/note regardless of vars['type']
        $skip_reply = false;
        if (!empty($log_options) && empty($vars['details'])
            && (empty($files) || empty($files['attachment']['name'][0]))
        ) {
            $skip_reply = true;
        }

        if (!$skip_reply) {
            $this->Input->setRules($this->getReplyRules($vars, $new_ticket));

            if ($this->Input->validates($vars)) {
                // Create the reply
                $fields = ['ticket_id', 'staff_id', 'contact_id', 'type', 'details', 'date_added'];
                $this->Record->insert('support_replies', $vars, $fields);
                $reply_id = $this->Record->lastInsertId();

                // Handle file upload
                if (!empty($files['attachment'])) {
                    Loader::loadComponents($this, ['SettingsCollection', 'Upload']);

                    // Set the uploads directory
                    $temp = $this->SettingsCollection->fetchSetting(
                        null,
                        Configure::get('Blesta.company_id'),
                        'uploads_dir'
                    );
                    $upload_path = $temp['value'] . Configure::get('Blesta.company_id')
                        . DS . 'support_manager_files' . DS;

                    // Create the upload path if it doesn't already exist
                    $this->Upload->createUploadPath($upload_path, 0777);

                    $this->Upload->setFiles($files, false);
                    $this->Upload->setUploadPath($upload_path);

                    $file_vars = ['files' => []];
                    if (!($errors = $this->Upload->errors())) {
                        // Will not overwrite existing file
                        $this->Upload->writeFile('attachment', false, null, [$this, 'makeFileName']);
                        $data = $this->Upload->getUploadData();

                        // Set the file names/paths
                        foreach ($files['attachment']['name'] as $index => $file_name) {
                            if (isset($data['attachment'][$index])) {
                                $file_vars['files'][] = [
                                    'name' => $data['attachment'][$index]['orig_name'],
                                    'file_name' => $data['attachment'][$index]['full_path']
                                ];
                            }
                        }

                        $errors = $this->Upload->errors();
                    }

                    // Error, could not upload the files
                    if ($errors) {
                        $this->Input->setErrors($errors);
                        // Attempt to remove the files if they were somehow written
                        foreach ($file_vars['files'] as $files) {
                            if (isset($files['file_name'])) {
                                @unlink($files['file_name']);
                            }
                        }
                        return;
                    } else {
                        // Add the attachments
                        $file_fields = ['reply_id', 'name', 'file_name'];
                        foreach ($file_vars['files'] as $files) {
                            if (!empty($files)) {
                                $this->Record->insert(
                                    'support_attachments',
                                    array_merge($files, ['reply_id' => $reply_id]),
                                    $file_fields
                                );
                            }
                        }
                    }
                }
            }
        }

        // Update custom fields
        $this->editCustomFields($ticket_id, $vars['custom_fields'] ?? []);

        // Only attempt to update log options if there are no previous errors
        if (!empty($log_options) && !$this->errors()) {
            // Update the support ticket
            $data = array_intersect_key($vars, $loggable_fields);
            $ticket_staff_id_field = [];
            if (isset($data['ticket_staff_id'])) {
                $ticket_staff_id_field = (isset($data['ticket_staff_id'])
                    ? ['staff_id' => $data['ticket_staff_id']]
                    : []
                );
            }

            $this->edit($ticket_id, array_merge($data, $ticket_staff_id_field), false);

            if (!($errors = $this->errors())) {
                // Log each support ticket field change
                foreach ($log_options as $field) {
                    $log_vars = [
                        'staff_id' => (array_key_exists('staff_id', $vars)
                            ? $vars['staff_id']
                            : $this->system_staff_id
                        ),
                        'type' => 'log'
                    ];

                    $lang_var1 = '';
                    switch ($field) {
                        case 'department_id':
                            $department = $this->Record->select('name')->from('support_departments')->
                                where('id', '=', $vars['department_id'])->fetch();
                            $lang_var1 = ($department ? $department->name : '');
                            break;
                        case 'priority':
                            $priorities = $this->getPriorities();
                            $lang_var1 = (isset($priorities[$vars['priority']]) ? $priorities[$vars['priority']] : '');
                            break;
                        case 'status':
                            $statuses = $this->getStatuses();
                            $lang_var1 = (isset($statuses[$vars['status']]) ? $statuses[$vars['status']] : '');
                            break;
                        case 'ticket_staff_id':
                            if (!isset($this->Staff)) {
                                Loader::loadModels($this, ['Staff']);
                            }

                            $staff = $this->Staff->get($vars['ticket_staff_id']);

                            if ($vars['ticket_staff_id'] && $staff) {
                                $lang_var1 = $staff->first_name . ' ' . $staff->last_name;
                            } else {
                                $lang_var1 = Language::_('SupportManagerTickets.log.unassigned', true);
                            }
                            // No break
                        default:
                            break;
                    }

                    $log_vars['details'] = Language::_('SupportManagerTickets.log.' . $field, true, $lang_var1);

                    $this->addReply($ticket_id, $log_vars);
                }
            }
        }

        // Return the ID of the reply
        if (isset($reply_id)) {
            return $reply_id;
        }
    }

    /**
     * Replies to a ticket and sends a ticket updated email
     *
     * @param string $reply The details to include in the reply
     * @param int $ticket_id The ID of the ticket to reply to
     * @param string $hostname The hostname of the company to which this ticket belongs
     * @param int $staff_id The ID of the staff member replying to the ticket (optional, default 0 for system reply)
     * @param array $additional_tags A key=>value list of the email_action=>tags array to send
     *  e.g. array('SupportManager.ticket_updated' => array('tag' => "value"))
     */
    private function staffReplyEmail($reply, $ticket_id, $hostname, $staff_id = 0, $additional_tags = [])
    {
        // Add the reply and send the email
        if (!empty($reply)) {
            if (!isset($this->Html)) {
                Loader::loadHelpers($this, ['Html']);
            }

            $tags = [
                'SupportManager.ticket_updated' => [
                    'update_ticket_url' => $this->getUpdateTicketUrl($ticket_id, $hostname)
                ]
            ];
            $tags = array_merge($tags, $additional_tags);
            $reply_id = $this->addReply($ticket_id, ['details' => $reply, 'staff_id' => $staff_id]);
            $this->sendEmail($reply_id, $tags);
        }
    }

    /**
     * Merges a set of tickets into another
     *
     * @param int $ticket_id The ID of the ticket that will receive the merges
     * @param array $tickets A list of ticket IDs to be merged
     */
    public function merge($ticket_id, array $tickets)
    {
        $ticket = $this->get($ticket_id);

        $rules = [
            'ticket_id' => [
                'exists' => [
                    'rule' => ($ticket ? true : false),
                    'message' => $this->_('SupportManagerTickets.!error.ticket_id.exists')
                ]
            ],
            'tickets' => [
                'valid' => [
                    'rule' => [[$this, 'validateTicketsMergeable'], $ticket_id],
                    'message' => $this->_('SupportManagerTickets.!error.tickets.valid')
                ]
            ],
            'merge_into' => [
                'itself' => [
                    'rule' => ['in_array', $tickets],
                    'negate' => true,
                    'message' => $this->_('SupportManagerTickets.!error.merge_into.itself')
                ]
            ]
        ];

        $vars = ['ticket_id' => $ticket_id, 'tickets' => $tickets, 'merge_into' => $ticket_id];

        $this->Input->setRules($rules);
        if ($this->Input->validates($vars)) {
            Loader::loadModels($this, ['Companies']);

            foreach ($tickets as $current_ticket_id) {
                // Fetch the ticket
                $current_ticket = $this->get($current_ticket_id, false);

                // Determine the company hostname
                $company = $this->Companies->get($current_ticket->company_id);
                $hostname = isset($company->hostname) ? $company->hostname : '';

                // Merge all ticket notes/replies into the other ticket
                $this->Record->where('ticket_id', '=', $current_ticket->id)->
                    where('type', '!=', 'log')->
                    update('support_replies', ['ticket_id' => $ticket->id]);

                // Add a new reply to indicate this ticket has been merged with another, and close it
                $reply = Language::_('SupportManagerTickets.merge.reply', true, $ticket->code);
                $this->staffReplyEmail($reply, $current_ticket_id, $hostname, $this->system_staff_id);
                $this->close($current_ticket_id, $this->system_staff_id);
            }
        }
    }

    /**
     * Splits the given ticket with the given replies, notes, into a new ticket
     *
     * @param int $ticket_id The ID of the ticket to split
     * @param array $replies A list of reply IDs belonging to the given ticket, which should be assigned to a new ticket
     * @return int The ID of the newly-created ticket on success, or void on error
     */
    public function split($ticket_id, array $replies)
    {
        // Fetch the ticket
        $ticket = $this->get($ticket_id);

        $rules = [
            'ticket_id' => [
                'exists' => [
                    'rule' => ($ticket ? true : false),
                    'message' => $this->_('SupportManagerTickets.!error.ticket_id.exists')
                ]
            ],
            'replies' => [
                'valid' => [
                    'rule' => [[$this, 'validateReplies'], $ticket_id],
                    'message' => $this->_('SupportManagerTickets.!error.replies.valid')
                ],
                'notes' => [
                    'rule' => [[$this, 'validateSplitReplies'], $ticket_id],
                    'message' => $this->_('SupportManagerTickets.!error.replies.notes')
                ]
            ]
        ];

        $vars = ['ticket_id' => $ticket_id, 'replies' => $replies];

        $this->Input->setRules($rules);
        if ($this->Input->validates($vars)) {
            // Create the new ticket
            $new_ticket_id = $this->add((array)$ticket);

            if ($new_ticket_id) {
                // Re-assign the replies
                foreach ($replies as $reply_id) {
                    $this->Record->where('id', '=', (int)$reply_id)
                        ->update('support_replies', ['ticket_id' => $new_ticket_id]);
                }
            }

            return $new_ticket_id;
        }
    }

    /**
     * Retrieves the total number of tickets in the given status assigned to the given staff/client
     *
     * @param string $status The status of the support tickets
     *  ('open', 'awaiting_reply', 'in_progress', 'on_hold', 'closed', 'trash')
     * @param int $staff_id The ID of the staff member assigned to the tickets or associated departments (optional)
     * @param int $client_id The ID of the client assigned to the tickets (optional)
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - staff_id The ID of the staff member assigned to the tickets or associated departments (optional)
     *  - client_id The ID of the client assigned to the tickets (optional)
     *  - ticket_id The ID of a specific ticket to fetch
     *  - ticket_number The (partial) ticket number on which to filter tickets
     *  - priority The priority on which to filter tickets
     *  - department_id The department ID on which to filter tickets
     *  - summary The (partial) summary of the ticket line on which to filter tickets
     *  - last_reply The elapsed time from the last reply on which to filter tickets
     *  - status The status of the support tickets
     *      ('open', 'awaiting_reply', 'in_progress', 'on_hold', 'closed', 'trash', 'not_closed')
     * @return int The total number of tickets in the given status
     */
    public function getStatusCount($status, $staff_id = null, $client_id = null, array $filters = [])
    {
        return $this->getTickets(array_merge(
            $filters,
            ['staff_id' => $staff_id, 'client_id' => $client_id, 'status' => $status]
        ))->numResults();
    }

    /**
     * Retrieves a specific ticket
     *
     * @param int $ticket_id The ID of the ticket to fetch
     * @param bool $get_replies True to include the ticket replies, false not to
     * @param array $reply_types A list of reply types to include (optional, default null for all)
     *  - "reply", "note", "log"
     * @param int $staff_id The ID of the staff member assigned to the tickets or associated departments (optional)
     * @return mixed An stdClass object representing the ticket, or false if none exist
     */
    public function get($ticket_id, $get_replies = true, array $reply_types = null, $staff_id = null)
    {
        // Get the ticket
        $ticket = $this->getTickets(['staff_id' => $staff_id, 'ticket_id' => $ticket_id])->fetch();

        if ($ticket && $get_replies) {
            $ticket->replies = $this->getReplies($ticket->id, $reply_types);
        }

        if ($ticket) {
            $ticket->custom_fields = [];
            $custom_fields = $this->Record->select()
                ->from('support_ticket_fields')
                ->where('support_ticket_fields.ticket_id', '=', $ticket->id)
                ->fetchAll();

            foreach ($custom_fields as $field) {
                if ($field->encrypted == '1') {
                    $field->value = $this->systemDecrypt($field->value);
                }

                $ticket->custom_fields[$field->field_id] = $field->value;
            }
        }

        return $ticket;
    }

    /**
     * Retrieves a specific ticket
     *
     * @param int $code The code of the ticket to fetch
     * @param bool $get_replies True to include the ticket replies, false not to
     * @param array $reply_types A list of reply types to include (optional, default null for all)
     *  - "reply", "note", "log"
     * @return mixed An stdClass object representing the ticket, or false if none exist
     */
    public function getTicketByCode($code, $get_replies = true, array $reply_types = null)
    {
        // Get the ticket
        $ticket = $this->getTickets()->where('support_tickets.code', '=', $code)->fetch();

        if ($get_replies) {
            $ticket->replies = $this->getReplies($ticket->id, $reply_types);
        }

        if ($ticket) {
            $ticket->custom_fields = $this->Record->select()
                ->from('support_ticket_fields')
                ->where('support_ticket_fields.ticket_id', '=', $ticket->id)
                ->innerJoin('support_department_fields', 'support_department_fields.id', '=', 'support_ticket_fields.field_id', false)
                ->order(['support_department_fields.order' => 'asc'])
                ->fetchAll();
        }

        return $ticket;
    }

    /**
     * Converts the given file name into an appropriate file name to store to disk
     *
     * @param string $file_name The name of the file to rename
     * @return string The rewritten file name in the format of
     *  YmdTHisO_[hash] (e.g. 20121009T154802+0000_1f3870be274f6c49b3e31a0c6728957f)
     */
    public function makeFileName($file_name)
    {
        $ext = strrchr($file_name, '.');
        $file_name = md5($file_name . uniqid()) . $ext;

        return $this->dateToUtc(date('c'), "Ymd\THisO") . '_' . $file_name;
    }

    /**
     * Retrieve a list of tickets
     *
     * @param string $status The status of the support tickets
     *  ('open', 'awaiting_reply', 'in_progress', 'on_hold', 'closed', 'trash', 'not_closed')
     * @param int $staff_id The ID of the staff member assigned to the tickets or associated departments (optional)
     * @param int $client_id The ID of the client assigned to the tickets (optional)
     * @param int $page The page number of results to fetch
     * @param array $order_by A list of sort=>order options
     * @param bool $get_replies True to include the ticket replies, false not to
     * @param array $reply_types A list of reply types to include (optional, default null for all)
     *  - "reply", "note", "log"
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - ticket_number The (partial) ticket number on which to filter tickets
     *  - priority The priority on which to filter tickets
     *  - department_id The department on which to filter tickets
     *  - summary The (partial) summary of the ticket line on which to filter tickets
     *  - assigned_staff The assigned staff member on which to filter tickets
     *  - last_reply The elapsed time from the last reply on which to filter tickets
     * @return array A list of stdClass objects representing tickets
     */
    public function getList(
        $status,
        $staff_id = null,
        $client_id = null,
        $page = 1,
        array $order_by = ['last_reply_date' => 'desc'],
        $get_replies = true,
        array $reply_types = null,
        array $filters = []
    ) {
        $tickets = $this->getTickets(
            array_merge(['status' => $status, 'staff_id' => $staff_id, 'client_id' => $client_id], $filters)
        )->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->fetchAll();

        // Fetch ticket replies
        if ($get_replies) {
            foreach ($tickets as &$ticket) {
                $ticket->replies = $this->getReplies($ticket->id, $reply_types);
                $ticket->custom_fields = $this->Record->select()
                    ->from('support_ticket_fields')
                    ->where('support_ticket_fields.ticket_id', '=', $ticket->id)
                    ->innerJoin('support_department_fields', 'support_department_fields.id', '=', 'support_ticket_fields.field_id', false)
                    ->order(['support_department_fields.order' => 'asc'])
                    ->fetchAll();
            }
        }

        return $tickets;
    }

    /**
     * Retrieves the total number of tickets
     *
     * @param string $status The status of the support tickets
     *  ('open', 'awaiting_reply', 'in_progress', 'on_hold', 'closed', 'trash', 'not_closed')
     * @param int $staff_id The ID of the staff member assigned to the tickets or associated departments (optional)
     * @param int $client_id The ID of the client assigned to the tickets (optional)
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - ticket_number The (partial) ticket number on which to filter tickets
     *  - priority The priority on which to filter tickets
     *  - department_id The department on which to filter tickets
     *  - summary The (partial) summary of the ticket line on which to filter tickets
     *  - assigned_staff The assigned staff member on which to filter tickets
     *  - last_reply The elapsed time from the last reply on which to filter tickets
     * @return int The total number of tickets
     */
    public function getListCount($status, $staff_id = null, $client_id = null, array $filters = [])
    {
        return $this->getTickets(
            array_merge(['status' => $status, 'staff_id' => $staff_id, 'client_id' => $client_id], $filters)
        )->numResults();
    }

    /**
     * Search tickets
     *
     * @param string $query The value to search tickets for
     * @param int $staff_id The ID of the staff member searching tickets (optional)
     * @param int $page The page number of results to fetch (optional, default 1)
     * @param array $order_by The sort=>$order options
     * @return array An array of tickets that match the search criteria
     */
    public function search($query, $staff_id = null, $page = 1, $order_by = ['last_reply_date' => 'desc'])
    {
        $this->Record = $this->searchTickets($query, $staff_id);
        return $this->Record->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->
            fetchAll();
    }

    /**
     * Seaches for tickets, specifically by ticket code of a given status
     *
     * @param string $query The value to search ticket codes for
     * @param int $staff_id The ID of the staff member searching tickets (optional)
     * @param mixed $status The status of tickets to search (optional, default null for all)
     * @param int $page The page number of results to fetch (optional, default 1)
     * @param array $order_by The sort=>$order options
     * @return array An array of tickets that match the search criteria
     */
    public function searchByCode(
        $query,
        $staff_id = null,
        $status = null,
        $page = 1,
        $order_by = ['last_reply_date' => 'desc']
    ) {
        $this->Record = $this->getTickets(['status' => $status, 'staff_id' => $staff_id]);

        $this->Record->open()->
            like('support_tickets.code', '%' . $query . '%')->
            close();

        return $this->Record->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->
            fetchAll();
    }

    /**
     * Returns the total number of tickets returned from SupportManagerTickets::search(), useful
     * in constructing pagination
     *
     * @param string $query The value to search tickets for
     * @param int $staff_id The ID of the staff member searching tickets (optional)
     * @see SupportManagerTickets::search()
     */
    public function getSearchCount($query, $staff_id = null)
    {
        $this->Record = $this->searchTickets($query, $staff_id);
        return $this->Record->numResults();
    }

    /**
     * Partially constructs the query for searching tickets
     *
     * @param string $query The value to search tickets for
     * @param int $staff_id The ID of the staff member searching tickets
     * @return Record The partially constructed query Record object
     * @see SupportManagerTickets::search(), SupportManagerTickets::getSearchCount()
     */
    private function searchTickets($query, $staff_id = null)
    {
        // Fetch the tickets
        $this->Record = $this->getTickets(['staff_id' => $staff_id, 'type' => 'all'])
            ->open()
                ->like('support_tickets.summary', '%' . $query . '%')
                ->orLike('support_tickets.email', '%' . $query . '%')
                ->orLike('support_tickets.code', '%' . $query . '%')
                ->orWhere('MATCH(support_replies.details)', ' AGAINST', [$query])
            ->close();

        return $this->Record;
    }

    /**
     * Retrieves a specific attachment
     *
     * @param int $attachment_id The ID of the attachment to fetch
     * @return mixed An stdClass object representing the attachment, or false if none exist
     */
    public function getAttachment($attachment_id)
    {
        $fields = [
            'support_attachments.*',
            'support_replies.ticket_id',
            'support_tickets.client_id',
            'support_tickets.department_id'
        ];
        return $this->Record->select($fields)->from('support_attachments')->
            innerJoin('support_replies', 'support_replies.id', '=', 'support_attachments.reply_id', false)->
            innerJoin('support_tickets', 'support_tickets.id', '=', 'support_replies.ticket_id', false)->
            where('support_attachments.id', '=', $attachment_id)->fetch();
    }

    /**
     * Retrieves a list of attachments for a given ticket
     *
     * @param int $ticket_id The ID of the ticket to fetch attachments for
     * @param int $reply_id The ID of the reply belonging to this ticket to fetch attachments for
     * @return array A list of attachments
     */
    public function getAttachments($ticket_id, $reply_id = null)
    {
        $fields = ['support_attachments.*'];
        $this->Record->select($fields)->from('support_attachments')->
            innerJoin('support_replies', 'support_replies.id', '=', 'support_attachments.reply_id', false)->
            innerJoin('support_tickets', 'support_tickets.id', '=', 'support_replies.ticket_id', false)->
            where('support_tickets.id', '=', $ticket_id);

        // Fetch attachments only for a specific reply
        if ($reply_id) {
            $this->Record->where('support_replies.id', '=', $reply_id);
        }

        return $this->Record->order(['support_replies.date_added' => 'DESC'])->fetchAll();
    }

    /**
     * Gets all replies to a specific ticket
     *
     * @param $ticket_id The ID of the ticket whose replies to fetch
     * @param array $types A list of reply types to include (optional, default null for all)
     *  - "reply", "note", "log"
     * @return array A list of replies to the given ticket
     */
    private function getReplies($ticket_id, array $types = null)
    {
        $fields = ['support_replies.*',
            'IF(
				support_replies.staff_id IS NULL,
				IF(client_contacts.first_name IS NULL, contacts.first_name, client_contacts.first_name),
				staff.first_name
			)' => 'first_name',
            'IF(
				support_replies.staff_id IS NULL,
				IF(client_contacts.last_name IS NULL, contacts.last_name, client_contacts.last_name),
				staff.last_name
			)' => 'last_name',
            'IF(
				support_replies.staff_id IS NULL,
				IF(client_contacts.email IS NULL, contacts.email, client_contacts.email),
				staff.email
			)' => 'email'
        ];

        $this->Record->select($fields, false)
            ->select(
                [
                    'IF(support_replies.staff_id = ?, ?, IF(staff.id IS NULL, IF(support_tickets.email IS NULL, ?, ?), ?))'
                    => 'reply_by'
                ],
                false
            )
            ->appendValues([$this->system_staff_id, 'system', 'client', 'email', 'staff'])
            ->from('support_replies')
            ->innerJoin('support_tickets', 'support_tickets.id', '=', 'support_replies.ticket_id', false)
            ->leftJoin('clients', 'clients.id', '=', 'support_tickets.client_id', false)
                ->on('contacts.contact_type', '=', 'primary')
            ->leftJoin('contacts', 'contacts.client_id', '=', 'clients.id', false)
                ->on('client_contacts.contact_type', '!=', 'primary')
            ->leftJoin(
                ['contacts' => 'client_contacts'],
                'client_contacts.id',
                '=',
                'support_replies.contact_id',
                false
            )
            ->leftJoin('staff', 'staff.id', '=', 'support_replies.staff_id', false)
            ->where('support_tickets.id', '=', $ticket_id);

        // Filter by specific types given
        if ($types) {
            $i = 0;
            foreach ($types as $type) {
                if ($i++ == 0) {
                    $this->Record->open()->where('support_replies.type', '=', $type);
                } else {
                    $this->Record->orWhere('support_replies.type', '=', $type);
                }
            }

            if ($i > 0) {
                $this->Record->close();
            }
        }

        $replies = $this->Record->order(['support_replies.date_added' => 'DESC', 'support_replies.id' => 'DESC'])
            ->fetchAll();

        // Fetch attachments
        foreach ($replies as &$reply) {
            $reply->attachments = $this->getAttachments($ticket_id, $reply->id);
        }

        return $replies;
    }

    /**
     * Returns a Record object for fetching tickets
     *
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - staff_id The ID of the staff member assigned to the tickets or associated departments (optional)
     *  - client_id The ID of the client assigned to the tickets (optional)
     *  - ticket_id The ID of a specific ticket to fetch
     *  - ticket_number The (partial) ticket number on which to filter tickets
     *  - priority The priority on which to filter tickets
     *  - department_id The department ID on which to filter tickets
     *  - summary The (partial) summary of the ticket line on which to filter tickets
     *  - last_reply The elapsed time from the last reply on which to filter tickets
     *  - status The status of the support tickets
     *      ('open', 'awaiting_reply', 'in_progress', 'on_hold', 'closed', 'trash', 'not_closed')
     *  - type The reply type to fetch ('reply', 'note', 'all')
     * @return Record A partially-constructed Record object for fetching tickets
     */
    private function getTickets(array $filters = [])
    {
        // Fetch all departments this staff belongs to
        $department_ids = [];
        if ($filters['staff_id']) {
            $department_ids = $this->getStaffDepartments($filters['staff_id']);
        }

        // Set default reply type
        if (empty($filters['type'])) {
            $filters['type'] = 'reply';
        }

        $sub_query = new Record();
        $sub_query->select(['support_replies.ticket_id', 'MAX(support_replies.date_added)' => 'reply_date'])->
            from('support_replies');

        if (isset($filters['type']) && $filters['type'] !== 'all') {
            $sub_query->where('support_replies.type', '=', $filters['type']);
        }

        $sub_query->group(['support_replies.ticket_id']);
        $replies = $sub_query->get();
        $reply_values = $sub_query->values;
        $this->Record->reset();

        $fields = [
            'support_tickets.*',
            'support_replies.date_added' => 'last_reply_date',
            'support_replies.staff_id' => 'last_reply_staff_id',
            'support_departments.name' => 'department_name',
            'support_departments.company_id',
            'staff_assigned.first_name' => 'assigned_staff_first_name',
            'staff_assigned.last_name' => 'assigned_staff_last_name',
            'contacts.first_name' => 'contact_first_name',
            'contacts.last_name' => 'contact_last_name',
            'contacts.company' => 'contact_company'
        ];
        $last_reply_fields = [
            'IF(
                support_replies.staff_id IS NULL,
                IF(support_tickets.email IS NULL, ?, ?),
                IF(support_replies.staff_id = ?, ?, ?)
            )' => 'last_reply_by',
            'IF(
				support_replies.staff_id IS NULL,
				IF(client_contacts.first_name IS NULL, contacts.first_name, client_contacts.first_name),
				staff.first_name
			)' => 'last_reply_first_name',
            'IF(
				support_replies.staff_id IS NULL,
				IF(client_contacts.last_name IS NULL, contacts.last_name, client_contacts.last_name),
				staff.last_name
			)' => 'last_reply_last_name',
            'IF(support_replies.staff_id IS NULL, IFNULL(support_tickets.email, ?), ?)' => 'last_reply_email'
        ];
        $last_reply_values = [
            'client', 'email', $this->system_staff_id, 'system', 'staff',
            null, null
        ];

        $this->Record->select($fields)
            ->select($last_reply_fields, false)
            ->appendValues($last_reply_values)
            ->from('support_tickets');

        if (isset($filters['type']) && $filters['type'] !== 'all') {
            $this->Record->on('support_replies.type', '=', $filters['type']);
        }

        $this->Record->innerJoin('support_replies', 'support_tickets.id', '=', 'support_replies.ticket_id', false)
                ->on('support_replies.date_added', '=', 'replies.reply_date', false)
            ->innerJoin([$replies => 'replies'], 'replies.ticket_id', '=', 'support_replies.ticket_id', false)
            ->appendValues($reply_values)
                ->on('support_departments.company_id', '=', Configure::get('Blesta.company_id'))
            ->innerJoin('support_departments', 'support_departments.id', '=', 'support_tickets.department_id', false)
            ->leftJoin('clients', 'clients.id', '=', 'support_tickets.client_id', false)
                ->on('contacts.contact_type', '=', 'primary')
            ->leftJoin('contacts', 'contacts.client_id', '=', 'clients.id', false)
                ->on('client_contacts.contact_type', '!=', 'primary')
            ->leftJoin(
                ['contacts' => 'client_contacts'],
                'client_contacts.id',
                '=',
                'support_replies.contact_id',
                false
            )
            ->leftJoin('staff', 'staff.id', '=', 'support_replies.staff_id', false)
            ->leftJoin(['staff' => 'staff_assigned'], 'staff_assigned.id', '=', 'support_tickets.staff_id', false);

        // Filter by status
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'not_closed':
                    $this->Record->where('support_tickets.status', 'notin', ['closed', 'trash']);
                    break;
                default:
                    $this->Record->where('support_tickets.status', '=', $filters['status']);
                    break;
            }
        }

        // Filter by a single ticket
        if (!empty($filters['ticket_id'])) {
            $this->Record->where('support_tickets.id', '=', $filters['ticket_id']);
        }

        // Filter by tickets staff can view
        if (!empty($filters['staff_id'])) {
            // Staff must be assigned to the ticket or in the same department as the ticket
            $this->Record->open()->where('support_tickets.staff_id', '=', $filters['staff_id']);

            if (!empty($department_ids)) {
                $this->Record->orWhere('support_tickets.department_id', 'in', $department_ids);
            }

            $this->Record->close();
        }

        // Filter by tickets assigned to the client
        if (!empty($filters['client_id'])) {
            $this->Record->where('support_tickets.client_id', '=', $filters['client_id']);
        }

        // Filter by ticket number
        if (!empty($filters['ticket_number'])) {
            $this->Record->where('support_tickets.code', 'LIKE', '%' . $filters['ticket_number']. '%');
        }

        // Filter by priority
        if (!empty($filters['priority'])) {
            $this->Record->where('support_tickets.priority', '=', $filters['priority']);
        }

        // Filter by department id
        if (!empty($filters['department_id'])) {
            $this->Record->where('support_tickets.department_id', '=', $filters['department_id']);
        }

        // Filter by summary
        if (!empty($filters['summary'])) {
            $this->Record->where('support_tickets.summary', 'LIKE', '%' . $filters['summary'] . '%');
        }

        // Filter by assigned staff
        if (!empty($filters['assigned_staff'])) {
            $this->Record->where('support_tickets.staff_id', '=', $filters['assigned_staff']);
        }

        // Filter by last reply
        if (!empty($filters['last_reply'])) {
            $this->Record->where('support_tickets.date_updated', '>', $this->Date->modify(
                date('c'),
                '-' . $filters['last_reply'] . ' minutes',
                'c',
                Configure::get('Blesta.company_timezone')
            ));
        }

        $this->Record->group(['support_tickets.id']);

        return $this->Record;
    }

    /**
     * Retrieves a list of priorities and their language
     *
     * @return array A list of priority => language priorities
     */
    public function getPriorities()
    {
        return [
            'emergency' => $this->_('SupportManagerTickets.priority.emergency'),
            'critical' => $this->_('SupportManagerTickets.priority.critical'),
            'high' => $this->_('SupportManagerTickets.priority.high'),
            'medium' => $this->_('SupportManagerTickets.priority.medium'),
            'low' => $this->_('SupportManagerTickets.priority.low')
        ];
    }

    /**
     * Retrieves a list of statuses and their language
     *
     * @return array A list of status => language statuses
     */
    public function getStatuses()
    {
        return [
            'open' => $this->_('SupportManagerTickets.status.open'),
            'awaiting_reply' => $this->_('SupportManagerTickets.status.awaiting_reply'),
            'in_progress' => $this->_('SupportManagerTickets.status.in_progress'),
            'on_hold' => $this->_('SupportManagerTickets.status.on_hold'),
            'closed' => $this->_('SupportManagerTickets.status.closed'),
            'trash' => $this->_('SupportManagerTickets.status.trash')
        ];
    }

    /**
     * Retrieves a list of reply types and their language
     *
     * @return array A list of type => language reply types
     */
    public function getReplyTypes()
    {
        return [
            'reply' => $this->_('SupportManagerTickets.type.reply'),
            'note' => $this->_('SupportManagerTickets.type.note'),
            'log' => $this->_('SupportManagerTickets.type.log')
        ];
    }

    /**
     * Retrieves a list of department IDs for a given staff member
     *
     * @param int $staff_id The ID of the staff member whose departments to fetch
     * @return array A list of department IDs that this staff member belongs to
     */
    private function getStaffDepartments($staff_id)
    {
        // Fetch all departments this staff belongs to
        $departments = $this->Record->select(['support_staff_departments.department_id'])->
            from('support_staff_departments')->
            where('support_staff_departments.staff_id', '=', $staff_id)->
            fetchAll();

        // Create a list of department IDs this staff belongs to
        $department_ids = [];
        foreach ($departments as $department) {
            $department_ids[] = $department->department_id;
        }

        return $department_ids;
    }

    /**
     * Fetches the client for the given company using the given email address.
     * Searches first the primary contact of each client, and if no results found
     * then any contact for the clients in the given company. Returns the first
     * client found.
     *
     * @param int $company_id The ID of the company to fetch a client for
     * @param string $email The email address to fetch clients on
     * @return mixed A stdClass object representing the client whose contact
     *  matches the email address, false if no client found
     */
    public function getClientByEmail($company_id, $email)
    {
        // Fetch client based on primary contact email
        $client = $this->Record->select(['clients.*'])->
            from('contacts')->
            innerJoin('clients', 'clients.id', '=', 'contacts.client_id', false)->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            where('client_groups.company_id', '=', $company_id)->
            where('contacts.email', '=', $email)->
            where('contacts.contact_type', '=', 'primary')->fetch();

        // If no client found, fetch client based on any contact email
        if (!$client) {
            $client = $this->Record->select(['clients.*'])->
                from('contacts')->
                innerJoin('clients', 'clients.id', '=', 'contacts.client_id', false)->
                innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
                where('client_groups.company_id', '=', $company_id)->
                where('contacts.email', '=', $email)->fetch();
        }
        return $client;
    }

    /**
     * Fetches a client's contact given the contact's email address
     *
     * @param int $client_id The ID of the client whose contact the email address is presumed to be from
     * @param string $email The email address
     * @return mixed An stdClass object representing the contact with the given email address, or false if none exist
     */
    public function getContactByEmail($client_id, $email)
    {
        // Assume contact emails are unique per client, and only choose the first
        return $this->Record->select(['contacts.*'])->
            from('contacts')->
            where('contacts.email', '=', $email)->
            where('contacts.client_id', '=', $client_id)->
            fetch();
    }

    /**
     * Retrieves a list of all contact email addresses that have replied to the given ticket.
     * This does not include the client's primary contact email.
     *
     * @param int $ticket_id The ID of the ticket whose contact emails to fetch
     * @return array A numerically indexed array of email addresses of each contact that has replied to this ticket.
     *  May be an empty array if no contact, or only the primary client contact, has replied.
     */
    public function getContactEmails($ticket_id)
    {
        // Fetch the email addresses of all contacts set on the ticket replies
        $emails = $this->Record->select(['contacts.email'])->
            from('support_replies')->
            innerJoin('contacts', 'contacts.id', '=', 'support_replies.contact_id', false)->
            where('support_replies.ticket_id', '=', $ticket_id)->
            group(['contacts.email'])->
            fetchAll();

        $contact_emails = [];
        foreach ($emails as $email) {
            $contact_emails[] = $email->email;
        }

        return $contact_emails;
    }

    /**
     * Returns the ticket info if any exists
     *
     * @param string $body The body of the message
     * @return mixed Null if no ticket info exists, an array otherwise containing:
     *  - ticket_code The ticket code number
     *  - code The validation code that can be used to verify the ticket number
     *  - valid Whether or not the code is valid for this ticket_code
     */
    public function parseTicketInfo($str)
    {
        // Format of ticket number #NUM -CODE-
        // For example: #504928 -efa3-
        // Example in subject: Your Ticket #504928 -efa3- Has a New Comment
        preg_match("/\#([0-9]+) \-([a-f0-9]+)\-/i", $str, $matches);

        if (count($matches) < 3) {
            return null;
        }

        $ticket_code = isset($matches[1]) ? $matches[1] : null;
        $code = isset($matches[2]) ? $matches[2] : null;

        return [
            'ticket_code' => $ticket_code,
            'code' => $code,
            'valid' => $this->validateReplyCode($ticket_code, $code)
        ];
    }

    /**
     * Generates a pseudo-random hash from an sha256 HMAC of the ticket ID
     *
     * @param int $ticket_id The ID of the ticket to generate the hash for
     * @param mixed $key A key to include in the hash
     * @return string A hexadecimal hash of the given length
     */
    public function generateReplyHash($ticket_id, $key)
    {
        return $this->systemHash($ticket_id . $key);
    }

    /**
     * Generates a pseudo-random reply code from an sha256 HMAC of the ticket ID code
     *
     * @param int $ticket_code The ticket code to generate the reply code from
     * @param int $length The length of the reply code between 4 and 64 characters (optional, default 4)
     * @return string A hexadecimal reply code of the given length
     */
    public function generateReplyCode($ticket_code, $length = 4)
    {
        $hash = $this->systemHash($ticket_code);
        $hash_size = strlen($hash);

        if ($length < 4) {
            $length = 4;
        } elseif ($length > $hash_size) {
            $length = $hash_size;
        }

        return substr($hash, mt_rand(0, $hash_size-$length), $length);
    }

    /**
     * Generates a pseudo-random reply code from an sha256 HMAC of the ticket ID code
     * and concatenates it with the ticket ID
     *
     * @param int $ticket_code The ticket code to generate the reply code from
     * @param int $length The length of the reply code between 4 and 64 characters
     * @return string A formatted reply number (e.g. "#504928 -efa3-")
     */
    public function generateReplyNumber($ticket_code, $length = 4)
    {
        // Format of ticket number #NUM -CODE-
        // For example: #504928 -efa3-

        $code = $this->generateReplyCode($ticket_code, $length);
        return '#' . $ticket_code . ' -' . $code . '-';
    }

    /**
     * Sends ticket updated/received emails
     *
     * @param int $reply_id The ID of the ticket reply that the email is to use
     * @param array $additional_tags A key=>value list of the email_action=>tags array to send
     *  e.g. array('SupportManager.ticket_received' => array('tag' => "value"))
     */
    public function sendEmail($reply_id, $additional_tags = [])
    {
        // Fetch the associated ticket
        $fields = ['support_tickets.*', 'support_replies.id' => 'reply_id',
            'support_replies.staff_id' => 'reply_staff_id',
            'support_replies.contact_id' => 'reply_contact_id',
            'support_replies.type' => 'reply_type', 'support_replies.details',
            'support_replies.date_added' => 'reply_date_added',
            'support_departments.id' => 'department_id', 'support_departments.company_id' => 'company_id',
            'support_departments.name' => 'department_name', 'support_departments.email' => 'department_email',
            'support_departments.override_from_email', 'support_departments.include_attachments',
            'support_departments.attachment_types', 'support_departments.max_attachment_size'];
        $ticket = $this->Record->select($fields)
            ->select(
                [
                    'IF(support_replies.staff_id = ?, ?, IF(staff.id IS NULL, IF(support_tickets.email IS NULL, ?, ?), ?))'
                    => 'reply_by'
                ],
                false
            )
            ->appendValues([$this->system_staff_id, 'system', 'client', 'email', 'staff'])
            ->from('support_replies')
            ->innerJoin('support_tickets', 'support_tickets.id', '=', 'support_replies.ticket_id', false)
            ->innerJoin('support_departments', 'support_departments.id', '=', 'support_tickets.department_id', false)
            ->leftJoin('clients', 'clients.id', '=', 'support_tickets.client_id', false)
                ->on('contacts.contact_type', '=', 'primary')
            ->leftJoin('contacts', 'contacts.client_id', '=', 'clients.id', false)
            ->leftJoin('staff', 'staff.id', '=', 'support_replies.staff_id', false)
            ->where('support_replies.id', '=', $reply_id)
            ->fetch();

        // Only send email if the ticket is a reply type
        $mime_types = Configure::get('SupportManager.mime_types');
        if ($ticket && $ticket->reply_type == 'reply') {
            $ticket->attachment_types = array_map(
                function ($value) use ($mime_types) {
                    $value = trim($value);
                    return array_key_exists($value, $mime_types) ? $mime_types[$value] : $value;
                },
                explode(',', $ticket->attachment_types)
            );
            $ticket->attachment_types[] = 'application/octet-stream';

            // Determine whether this is the only reply or not
            $total_replies = $this->Record->select(['support_replies.id'])->from('support_tickets')->
                innerJoin('support_replies', 'support_replies.ticket_id', '=', 'support_tickets.id', false)->
                where('support_tickets.id', '=', $ticket->id)->
                numResults();

            // Determine whether this ticket has any attachments
            $num_attachments = $this->Record->select(['support_attachments.*'])->from('support_tickets')->
                innerJoin('support_replies', 'support_replies.ticket_id', '=', 'support_tickets.id', false)->
                innerJoin('support_attachments', 'support_attachments.reply_id', '=', 'support_replies.id', false)->
                where('support_tickets.id', '=', $ticket->id)->numResults();
            $ticket->has_attachments = ($num_attachments > 0);

            // Check if this specific reply has any attachments
            $ticket->reply_has_attachments = false;
            $ticket->attachments = null;
            if ($num_attachments > 0) {
                $reply_attachments = $this->Record->select()
                    ->from('support_attachments')
                    ->where('reply_id', '=', $reply_id)
                    ->fetchAll();
                foreach ($reply_attachments as $reply_attachment) {
                    if (!isset($ticket->attachments)) {
                        $ticket->attachments = [];
                        $ticket->reply_has_attachments = true;
                    }

                    if (function_exists('mime_content_type')) {
                        $mime_type = mime_content_type($reply_attachment->file_name);
                    } else {
                        $pathinfo = pathinfo($reply_attachment->file_name);
                        $mime_type = array_key_exists($pathinfo['extension'], $mime_types)
                            ? $mime_types[$pathinfo['extension']]
                            : $pathinfo['extension'];
                    }

                    if ($ticket->include_attachments
                        && filesize($reply_attachment->file_name) / 1000000 < $ticket->max_attachment_size
                        && in_array($mime_type, $ticket->attachment_types)
                    ) {
                        $ticket->attachments[] = [
                            'path' => $reply_attachment->file_name,
                            'name' => $reply_attachment->name,
                            'type' => $mime_type
                        ];
                    }
                }
            }

            // Set status/priority language
            $priorities = $this->getPriorities();
            $statuses = $this->getStatuses();
            $ticket->priority_language = $priorities[$ticket->priority];
            $ticket->status_language = $statuses[$ticket->status];

            // Parse details into HTML for HTML templates
            Loader::loadHelpers($this, ['TextParser']);
            $ticket->details_html = $this->TextParser->encode('markdown', $ticket->details);

            // Send the ticket emails
            $this->sendTicketEmail($ticket, ($total_replies == 1), $additional_tags);
        }
    }

    /**
     * Sends a notice to the ticket's assigned staff member to notify them that the ticket has been assigned to them
     *
     * @param int $ticket_id The ID of the ticket that a staff member has been assigned
     */
    public function sendTicketAssignedEmail($ticket_id)
    {
        Loader::loadModels($this, ['Emails', 'Staff', 'MessengerManager']);

        // Notify the assigned staff in regards to this ticket
        if (($ticket = $this->get($ticket_id, false)) && !empty($ticket->staff_id)
            && ($staff = $this->Staff->get($ticket->staff_id)) && $staff->email) {
            // Set status/priority language
            $priorities = $this->getPriorities();
            $statuses = $this->getStatuses();
            $ticket->priority_language = $priorities[$ticket->priority];
            $ticket->status_language = $statuses[$ticket->status];

            $tags = ['ticket' => $ticket, 'staff' => $staff];
            $email_action = 'SupportManager.staff_ticket_assigned';

            $language = $this->Staff->getSetting($staff->id, 'language');
            $this->Emails->send(
                $email_action,
                $ticket->company_id,
                $language ? $language->value : Configure::get('Blesta.language'),
                $staff->email,
                array_merge($tags, $this->getCustomFieldsEmailTags($ticket->id ?? null))
            );

            $this->MessengerManager->send($email_action, $tags, [$staff->user_id]);
        }
    }

    /**
     * Sends ticket emails
     *
     * @param stdClass $ticket An stdClass object representing the ticket
     * @param bool $new_ticket True if this is the first ticket reply, false if it is a reply to an existing ticket
     * @param array $additional_tags A key=>value list of the email_action=>tags array to send
     *  e.g. array('SupportManager.ticket_received' => array('tag' => "value"))
     */
    private function sendTicketEmail($ticket, $new_ticket, $additional_tags = [])
    {
        switch ($ticket->reply_by) {
            case 'system':
            case 'staff':
                $this->sendTicketByStaffEmail($ticket, $additional_tags);
                break;
            case 'email':
            case 'client':
                $this->sendTicketByClientEmail($ticket, $new_ticket, $additional_tags);
                break;
            default:
                break;
        }
    }

    /**
     * Sends a ticket received notice to a client for a new ticket
     *
     * @param stdClass $ticket An stdClass object representing the ticket
     * @param array $additional_tags A key=>value list of the email_action=>tags array to send
     *  e.g. array('SupportManager.ticket_received' => array('tag' => "value"))
     */
    private function sendTicketReceived($ticket, $additional_tags = [])
    {
        Loader::loadModels($this, ['Clients', 'Contacts', 'Emails', 'Companies']);
        if (!isset($this->Html)) {
            Loader::loadHelpers($this, ['Html']);
        }

        // Set options for the email
        $options = [
            'to_client_id' => $ticket->client_id,
            'from_staff_id' => null,
            'reply_to' => $ticket->department_email
        ];

        // Set the template from address to the departments'
        if (property_exists($ticket, 'override_from_email') && $ticket->override_from_email == 1) {
            $options['from'] = $ticket->department_email;
        }

        // Get the contact for this reply
        $contact = $this->getTicketReplyContact($ticket);

        $to_email = $ticket->email;
        $cc_email = [];
        if ($ticket->client_id > 0) {
            $client = $this->Clients->get($ticket->client_id);
            if ($client) {
                $to_email = $client->email;

                // If the ticket was created by a contact, CC the contact
                if ($ticket->reply_contact_id && ($contact = $this->Contacts->get($ticket->reply_contact_id))) {
                    $cc_email[] = $contact->email;
                }
            }
        }
        $language = (isset($client->settings['language'])
            ? $client->settings['language']
            : Configure::get('Blesta.language')
        );

        $email_action = 'SupportManager.ticket_received';

        // Set the tags
        $other_tags = (isset($additional_tags[$email_action]) ? $additional_tags[$email_action] : []);

        // Determine the company hostname
        $company = $this->Companies->get($ticket->company_id);
        $hostname = isset($company->hostname) ? $company->hostname : '';
        $tags = array_merge(
            [
                'ticket' => $ticket,
                'ticket_hash_code' => $this->generateReplyNumber($ticket->code, 4),
                'reply_contact' => $contact,
                'client' => isset($client) ? $client : null,
                'update_ticket_url' => $this->getUpdateTicketUrl($ticket->id, $hostname)
            ],
            $other_tags
        );
        $this->Emails->send(
            $email_action,
            $ticket->company_id,
            $language,
            $to_email,
            array_merge($tags, $this->getCustomFieldsEmailTags($ticket->id ?? null)),
            $cc_email,
            null,
            isset($ticket->attachments) ? $ticket->attachments : null,
            $options
        );
    }

    /**
     * Forms a link for a customer ticket
     *
     * @param int $ticket_id The ID of the ticket to link
     * @param string $hostname The company hostname to link
     */
    private function getUpdateTicketUrl($ticket_id, $hostname)
    {
        $key = mt_rand();
        $hash = $this->generateReplyHash($ticket_id, $key);

        $url = $hostname . $this->getWebDirectory() . Configure::get('Route.client')
            . '/plugin/support_manager/client_tickets/reply/' . $ticket_id
            . '/?sid=' . rawurlencode($this->systemEncrypt('h=' . substr($hash, -16) . '|k=' . $key));

        return $this->Html->safe($url);
    }

    /**
     * Sends the ticket updated email to staff regarding a ticket created/updated by a client.
     * In the case $new_ticket is true, a ticket received notice is also sent to the client.
     *
     * @param stdClass $ticket An stdClass object representing the ticket
     * @param bool $new_ticket True if this is the first ticket reply, false if it is a reply to an existing ticket
     * @param array $additional_tags A key=>value list of the email_action=>tags array to send
     *  e.g. array('SupportManager.ticket_received' => array('tag' => "value"))
     */
    private function sendTicketByClientEmail($ticket, $new_ticket, $additional_tags = [])
    {
        Loader::loadModels(
            $this,
            [
                'Emails', 'Staff', 'MessengerManager',
                'SupportManager.SupportManagerStaff', 'SupportManager.SupportManagerDepartments'
            ]
        );

        // Get the contact for this reply
        $contact = $this->getTicketReplyContact($ticket);

        if ($new_ticket) {
            // Don't send any emails if this is a new ticket and ticket received email is disabled
            if (!isset($ticket->department_id)
                || !($department = $this->SupportManagerDepartments->get($ticket->department_id))
                || (isset($department->send_ticket_received) && $department->send_ticket_received != '1')
            ) {
                return;
            }

            // Send the ticket received notification to the client
            $this->sendTicketReceived($ticket, $additional_tags);
        }

        // Set the date/time that each staff member must be available to receive notices
        $day = strtolower($this->dateToUtc($ticket->reply_date_added . 'Z', 'D'));
        $time = $this->dateToUtc($ticket->reply_date_added . 'Z', 'H:i:s');

        // Fetch all staff available to receive notifications at this time
        $staff = $this->SupportManagerStaff->getAllAvailable(
            $ticket->company_id,
            $ticket->department_id,
            [$day => $time]
        );

        $to_addresses = [];
        $to_mobile_addresses = [];
        $to_staff_user_ids = [];

        // Check each staff member is set to receive the notice
        foreach ($staff as $member) {
            $language = $this->Staff->getSetting($member->id, 'language');
            // Determine whether this staff is set to receive the ticket email
            if (isset($member->settings['ticket_emails']) && is_array($member->settings['ticket_emails'])) {
                foreach ($member->settings['ticket_emails'] as $priority => $enabled) {
                    if ($enabled == 'true' && $ticket->priority == $priority) {
                        $to_addresses[] = [
                            'email' => $member->email,
                            'language' => $language ? $language->value : Configure::get('Blesta.language')
                        ];
                        break;
                    }
                }
            }

            // Determine whether this staff is set to receive the ticket mobile email
            if (!empty($member->email_mobile) && isset($member->settings['mobile_ticket_emails'])
                && is_array($member->settings['mobile_ticket_emails'])
            ) {
                foreach ($member->settings['mobile_ticket_emails'] as $priority => $enabled) {
                    if ($enabled == 'true' && $ticket->priority == $priority) {
                        $to_mobile_addresses[] = [
                            'email' => $member->email_mobile,
                            'language' => $language ? $language->value : Configure::get('Blesta.language')
                        ];
                        break;
                    }
                }
            }

            // Determine whether this staff is set to receive the ticket notifications
            if (isset($member->settings['ticket_messenger_notifications'])
                && is_array($member->settings['ticket_messenger_notifications'])
            ) {
                foreach ($member->settings['ticket_messenger_notifications'] as $priority => $enabled) {
                    if ($enabled == 'true' && $ticket->priority == $priority) {
                        $to_staff_user_ids[] = $member->user_id;
                        break;
                    }
                }
            }
        }

        $options = [
            'to_client_id' => null,
            'from_staff_id' => null,
            'reply_to' => $ticket->department_email
        ];

        // Set the template from address to the departments'
        if (property_exists($ticket, 'override_from_email') && $ticket->override_from_email == 1) {
            $options['from'] = $ticket->department_email;
        }

        $client = null;
        if (property_exists($ticket, 'client_id') && $ticket->client_id > 0) {
            $client = $this->Clients->get($ticket->client_id);
        }

        // Set the tags
        $ticket_hash_code = $this->generateReplyNumber($ticket->code, 6);
        $email_action = 'SupportManager.staff_ticket_updated';
        $other_tags = (isset($additional_tags[$email_action]) ? $additional_tags[$email_action] : []);
        $tags = array_merge(
            [
                'ticket' => $ticket,
                'ticket_hash_code' => $ticket_hash_code,
                'reply_contact' => $contact,
                'client' => $client
            ],
            $other_tags
        );

        // Send the staff ticket updated emails
        foreach ($to_addresses as $key => $address) {
            $this->Emails->send(
                $email_action,
                $ticket->company_id,
                $address['language'],
                $address['email'],
                array_merge($tags, $this->getCustomFieldsEmailTags($ticket->id ?? null)),
                null,
                null,
                isset($ticket->attachments) ? $ticket->attachments : null,
                $options
            );
        }

        // Set the tags
        $email_action = 'SupportManager.staff_ticket_updated_mobile';
        $other_tags = (isset($additional_tags[$email_action]) ? $additional_tags[$email_action] : []);
        $tags = array_merge(
            [
                'ticket' => $ticket,
                'ticket_hash_code' => $ticket_hash_code,
                'reply_contact' => $contact,
                'client' => $client
            ],
            $other_tags
        );

        // Send the staff ticket updated mobile emails
        foreach ($to_mobile_addresses as $key => $address) {
            $this->Emails->send(
                $email_action,
                $ticket->company_id,
                $address['language'],
                $address['email'],
                array_merge($tags, $this->getCustomFieldsEmailTags($ticket->id ?? null)),
                null,
                null,
                isset($ticket->attachments) ? $ticket->attachments : null,
                $options
            );
        }

        $this->MessengerManager->send('SupportManager.staff_ticket_updated', $tags, $to_staff_user_ids);
    }

    /**
     *  Retrieves the client contact that replied to this ticket, otherwise the client contact this ticket is assigned to if available
     * @see ::sendTicketByClientEmail, ::sendTicketReceived
     *
     * @param stdClass $ticket An object representing the given ticket
     * @return stdClass An object representing the contact assigned to this ticket
     */
    private function getTicketReplyContact(stdClass $ticket)
    {
        Loader::loadModels($this, ['Clients', 'Contacts']);
        $contact = null;
        if ($ticket->reply_contact_id) {
            $contact = $this->Contacts->get($ticket->reply_contact_id);
        } elseif (!$ticket->reply_staff_id && ($client = $this->Clients->get($ticket->client_id, false))) {
            $contacts = $this->Contacts->getAll($client->id, 'primary');
            if (!empty($contacts)) {
                $contact = $contacts[0];
            }
        }

        return $contact;
    }

    /**
     * Sends the ticket email to a client regarding a ticket created/updated by a staff member
     *
     * @param stdClass $ticket An stdClass object representing the ticket
     * @param array $additional_tags A key=>value list of the email_action=>tags array to send
     *  e.g. array('SupportManager.ticket_received' => array('tag' => "value"))
     */
    private function sendTicketByStaffEmail($ticket, $additional_tags = [])
    {
        Loader::loadModels($this, ['Clients', 'Contacts', 'Emails']);

        // Fetch client to set email language
        $to_email = $ticket->email;
        $cc_email = [];
        if ($ticket->client_id > 0) {
            $client = $this->Clients->get($ticket->client_id);
            if ($client) {
                $to_email = $client->email;

                // CC all contacts that have replied to the ticket
                $cc_email = $this->getContactEmails($ticket->id);
            }
        }
        $language = (isset($client->settings['language'])
            ? $client->settings['language']
            : Configure::get('Blesta.language')
        );

        $email_action = 'SupportManager.ticket_updated';

        // Send the email to the client
        $other_tags = (isset($additional_tags[$email_action]) ? $additional_tags[$email_action] : []);
        $tags = array_merge(
            [
                'ticket' => $ticket,
                'ticket_hash_code' => $this->generateReplyNumber($ticket->code, 4),
                'client' => isset($client) ? $client : null
            ],
            $other_tags
        );
        $options = [
            'to_client_id' => $ticket->client_id,
            'from_staff_id' => null,
            'reply_to' => $ticket->department_email
        ];

        // Set the template from address to the departments'
        if (property_exists($ticket, 'override_from_email') && $ticket->override_from_email == 1) {
            $options['from'] = $ticket->department_email;
        }

        $this->Emails->send(
            $email_action,
            $ticket->company_id,
            $language,
            $to_email,
            array_merge($tags, $this->getCustomFieldsEmailTags($ticket->id ?? null)),
            $cc_email,
            null,
            isset($ticket->attachments) ? $ticket->attachments : null,
            $options
        );
    }

    /**
     * Checks whether a particular email address has received more than $count emails
     * in the last $time_limit seconds
     *
     * @param string $email The email address to check
     * @param int $count The maximum number of allowed emails within the time limit
     * @param string $time_limit The time length in the past (e.g. "5 minutes")
     * @return bool True if the email has received <= $count emails since $time_limit, false otherwise
     */
    public function checkLoopBack($email, $count, $time_limit)
    {
        // Fetch the number of emails sent to the email address recently
        $past_date = $this->dateToUtc(strtotime(date('c') . ' -' . $time_limit));
        $emails_sent = $this->Record->select()->from('log_emails')->
            where('from_address', '=', $email)->
            where('date_sent', '>=', $past_date)->
            numResults();

        if ($emails_sent <= $count) {
            return true;
        }
        return false;
    }

    /**
     * Validates that the given reply code is correct for the ticket ID code
     *
     * @param int $ticket_code The ticket code to validate the reply code for
     * @return bool True if the reply code is valid, false otherwise
     */
    public function validateReplyCode($ticket_code, $code)
    {
        $hash = $this->systemHash($ticket_code);
        return strpos($hash, $code) !== false;
    }

    /**
     * Retrieves a list of rules for adding/editing support ticket replies
     *
     * @param array $vars A list of input vars
     * @param bool $new_ticket True to get the rules if this ticket is in the process of
     *  being created, false otherwise (optional, default false)
     * @return array A list of ticket reply rules
     */
    private function getReplyRules(array $vars, $new_ticket = false)
    {
        $rules = [
            'staff_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateStaffExists']],
                    'message' => $this->_('SupportManagerTickets.!error.staff_id.exists')
                ]
            ],
            'contact_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'contacts'],
                    'message' => $this->_('SupportManagerTickets.!error.contact_id.exists')
                ],
                'valid' => [
                    'if_set' => true,
                    'rule' => [
                        [$this, 'validateClientContact'],
                        (isset($vars['ticket_id']) ? $vars['ticket_id'] : null),
                        (isset($vars['client_id']) ? $vars['client_id'] : null)
                    ],
                    'message' => $this->_('SupportManagerTickets.!error.contact_id.valid')
                ]
            ],
            'type' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', array_keys($this->getReplyTypes())],
                    'message' => $this->_('SupportManagerTickets.!error.type.format')
                ]
            ],
            'details' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('SupportManagerTickets.!error.details.empty')
                ]
            ],
            'date_added' => [
                'format' => [
                    'rule' => true,
                    'message' => '',
                    'post_format' => [[$this, 'dateToUtc']]
                ]
            ]
        ];

        if ($new_ticket) {
            // The reply type must be 'reply' on a new ticket
            $rules['type']['new_valid'] = [
                'if_set' => true,
                'rule' => ['compares', '==', 'reply'],
                'message' => $this->_('SupportManagerTickets.!error.type.new_valid')
            ];
        } else {
            // Validate ticket exists
            $rules['ticket_id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'support_tickets'],
                    'message' => $this->_('SupportManagerTickets.!error.ticket_id.exists')
                ]
            ];
            // Validate client can reply to this ticket
            $rules['client_id'] = [
                'attached_to' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateClientTicket'], (isset($vars['ticket_id']) ? $vars['ticket_id'] : null)],
                    'message' => $this->_('SupportManagerTickets.!error.client_id.attached_to')
                ]
            ];
        }

        return $rules;
    }

    /**
     * Retrieves a list of rules for adding/editing support tickets
     *
     * @param array $vars A list of input vars
     * @param bool $edit True to get the edit rules, false for the add rules (optional, default false)
     * @param bool $require_email True to require the email field be given, false otherwise (optional, default false)
     * @return array A list of support ticket rules
     */
    private function getRules(array $vars, $edit = false, $require_email = false)
    {
        $rules = [
            'code' => [
                'format' => [
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => $this->_('SupportManagerTickets.!error.code.format')
                ]
            ],
            'department_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'support_departments'],
                    'message' => $this->_('SupportManagerTickets.!error.department_id.exists')
                ]
            ],
            'staff_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateStaffExists']],
                    'message' => $this->_('SupportManagerTickets.!error.staff_id.exists')
                ]
            ],
            'service_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'services'],
                    'message' => $this->_('SupportManagerTickets.!error.service_id.exists')
                ],
                'belongs' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateClientService'], (isset($vars['client_id']) ? $vars['client_id'] : null)],
                    'message' => $this->_('SupportManagerTickets.!error.service_id.belongs')
                ]
            ],
            'client_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => $this->_('SupportManagerTickets.!error.client_id.exists')
                ]
            ],
            'email' => [
                'format' => [
                    'rule' => [[$this, 'validateEmail'], $require_email],
                    'message' => $this->_('SupportManagerTickets.!error.email.format')
                ]
            ],
            'summary' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('SupportManagerTickets.!error.summary.empty')
                ],
                'length' => [
                    'rule' => ['maxLength', 255],
                    'message' => $this->_('SupportManagerTickets.!error.summary.length')
                ]
            ],
            'priority' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', array_keys($this->getPriorities())],
                    'message' => $this->_('SupportManagerTickets.!error.priority.format')
                ]
            ],
            'status' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', array_keys($this->getStatuses())],
                    'message' => $this->_('SupportManagerTickets.!error.status.format')
                ]
            ],
            'date_added' => [
                'format' => [
                    'rule' => true,
                    'message' => $this->_('SupportManagerTickets.!error.date_added.format'),
                    'post_format' => [[$this, 'dateToUtc']]
                ]
            ],
            'date_updated' => [
                'format' => [
                    'rule' => true,
                    'message' => $this->_('SupportManagerTickets.!error.date_updated.format'),
                    'post_format' => [[$this, 'dateToUtc']]
                ]
            ],
            'by_staff_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateStaffExists']],
                    'message' => $this->_('SupportManagerTickets.!error.by_staff_id.exists')
                ]
            ]
        ];

        if ($edit) {
            // Remove unnecessary rules
            unset($rules['date_added']);

            // Require that a client ID not be set
            $rules['client_id']['set'] = [
                'rule' => [[$this, 'validateTicketUnassigned'], (isset($vars['ticket_id']) ? $vars['ticket_id'] : null)],
                'message' => Language::_('SupportManagerTickets.!error.client_id.set', true)
            ];

            // Set edit-specific rules
            $rules['date_closed'] = [
                'format' => [
                    'rule' => [[$this, 'validateDateClosed']],
                    'message' => $this->_('SupportManagerTickets.!error.date_closed.format'),
                    'post_format' => [[$this, 'dateToUtc']]
                ]
            ];

            // Set all rules to optional
            $rules = $this->setRulesIfSet($rules);

            // Require a ticket be given
            $rules['ticket_id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'support_tickets'],
                    'message' => $this->_('SupportManagerTickets.!error.ticket_id.exists')
                ]
            ];

            // Make sure that a trashed ticket is not edited
            $rules['status']['trash'] = [
                'rule' => function ($status) use ($vars) {
                    $ticket = $this->get((isset($vars['ticket_id']) ? $vars['ticket_id'] : null), false);
                    return !($ticket && $ticket->status == 'trash' && ($status == 'trash' || $status == null));
                },
                'message' => $this->_('SupportManagerTickets.!error.status.trash')
            ];
        }

        return $rules;
    }

    /**
     * Validates whether the given client can reply to the given ticket
     *
     * @param int $client_id The ID of the client
     * @param int $ticket_id The ID of the ticket
     * @return bool True if the client can reply to the ticket, false otherwise
     */
    public function validateClientTicket($client_id, $ticket_id)
    {
        // Ensure this client is assigned this ticket
        $results = $this->Record->select('id')->from('support_tickets')->
            where('id', '=', $ticket_id)->where('client_id', '=', $client_id)->
            numResults();

        return ($results > 0);
    }

    /**
     * Validates whether the given contact can reply to the given ticket for the ticket's client
     *
     * @param int $contact_id The ID of the contact
     * @param int $ticket_id The ID of the ticket
     * @param int $client_id The ID of the client assigned to the ticket if the ticket
     *  is not known (optional, default null)
     * @return bool True if the contact can reply to the ticket, false otherwise
     */
    public function validateClientContact($contact_id, $ticket_id, $client_id = null)
    {
        // Contact does not need to be set
        if ($contact_id === null) {
            return true;
        }

        $ticket = $this->get($ticket_id, false);

        // In case a ticket is not yet known (e.g. in the process of being created), compare with the given client
        $client_id = ($ticket && $ticket->client_id ? $ticket->client_id : $client_id);

        if ($client_id !== null) {
            // The ticket and the contact must belong to a client
            $found = $this->Record->select()->from('contacts')->
                where('id', '=', $contact_id)->
                where('client_id', '=', $client_id)->
                numResults();

            if ($found) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validates that the given client can be assigned to the given ticket
     *
     * @param int $client_id The ID of the client to assign to the ticket
     * @param int $ticket_id The ID of the ticket
     * @return bool True if the client may be assigned to the ticket, false otherwise
     */
    public function validateTicketUnassigned($client_id, $ticket_id)
    {
        // Fetch the ticket
        $ticket = $this->get($ticket_id, false);

        // No ticket found, ignore this error
        if (!$ticket) {
            return true;
        }

        // Ticket may have either no client, or this client
        if ($ticket->client_id === null || $ticket->client_id == $client_id) {
            // Client must also be in the same company as the ticket
            $count = $this->Record->select(['client_groups.id'])->
                from('client_groups')->
                innerJoin('clients', 'clients.client_group_id', '=', 'client_groups.id', false)->
                where('clients.id', '=', $client_id)->
                where('client_groups.company_id', '=', $ticket->company_id)->
                numResults();

            if ($count > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validates that the given staff ID exists when adding/editing tickets
     *
     * @param int $staff_id The ID of the staff member
     * @return bool True if the staff ID exists, false otherwise
     */
    public function validateStaffExists($staff_id)
    {
        if ($staff_id == '' || $staff_id == $this->system_staff_id
            || $this->validateExists($staff_id, 'id', 'staff', false)
        ) {
            return true;
        }
        return false;
    }

    /**
     * Validates that the given service ID is assigned to the given client ID
     *
     * @param int $service_id The ID of the service
     * @param int $client_id The ID of the client
     * @return bool True if the service ID belongs to the client ID, false otherwise
     */
    public function validateClientService($service_id, $client_id)
    {
        $count = $this->Record->select()->from('services')->
            where('id', '=', $service_id)->
            where('client_id', '=', $client_id)->
            numResults();

        return ($count > 0);
    }

    /**
     * Validates the email address given for support tickets
     *
     * @param string $email The support ticket email address
     * @param bool $require_email True to require the email field be given, false otherwise (optional, default false)
     * @return bool True if the email address is valid, false otherwise
     */
    public function validateEmail($email, $require_email = false)
    {
        return (empty($email) && !$require_email ? true : $this->Input->isEmail($email));
    }

    /**
     * Validates the date closed for support tickets
     *
     * @param string $date_closed The date a ticket is closed
     * @return bool True if the date is in a valid format, false otherwise
     */
    public function validateDateClosed($date_closed)
    {
        return (empty($date_closed) ? true : $this->Input->isDate($date_closed));
    }

    /**
     * Validates that the given replies belong to the given ticket and that they are of the reply/note type.
     *
     * @param array $replies A list of IDs representing ticket replies
     * @param int $ticket_id The ID of the ticket to which the replies belong
     * @param bool $all False to require that at least 1 ticket reply not be given for this ticket,
     *  or true to allow all (optional, default false)
     * @return bool True if all of the given replies are valid; false otherwise
     */
    public function validateReplies(array $replies, $ticket_id, $all = false)
    {
        // Must have at least one reply ID
        if (empty($replies) || !($ticket = $this->get($ticket_id))) {
            return false;
        }

        // Fetch replies that are valid
        $valid_replies = $this->getValidTicketReplies($ticket_id);
        $num_notes = 0;
        $num_replies = 0;

        // Count the number of ticket notes and replies
        foreach ($valid_replies as $reply) {
            if ($reply->type == 'note') {
                $num_notes++;
            } else {
                $num_replies++;
            }
        }

        // Check that all replies given are valid replies
        foreach ($replies as $reply_id) {
            if (!array_key_exists($reply_id, $valid_replies)) {
                return false;
            }

            // Decrement the number of notes/replies that would be available to the ticket
            if ($valid_replies[$reply_id]->type == 'note') {
                $num_notes--;
            } else {
                $num_replies--;
            }
        }

        // At least one reply must be left remaining
        if (!$all && $num_replies <= 0) {
            return false;
        }

        // There must be valid replies
        return !empty($valid_replies);
    }

    /**
     * Validates that the given replies belong to the given ticket, that they are of the reply/note type, and that they
     * are not all only note types.
     * i.e. In addition to replies of the 'note' type, at least one 'reply' type must be included
     *
     * @param array $replies A list of IDs representing ticket replies
     * @param int $ticket_id The ID of the ticket to which the replies belong
     * @return bool True if no replies are given, or at least one is of the 'reply' type; false otherwise
     */
    public function validateSplitReplies(array $replies, $ticket_id)
    {
        // No replies, nothing to validate
        if (empty($replies)) {
            return true;
        }

        // Fetch the ticket replies
        $valid_replies = $this->getValidTicketReplies($ticket_id);

        foreach ($replies as $reply_id) {
            // At least one ticket reply must be of the 'reply' type
            if (array_key_exists($reply_id, $valid_replies) && $valid_replies[$reply_id]->type == 'reply') {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves a list of ticket replies of the "reply" and "note" type belonging to the given ticket
     *
     * @param int $ticket_id The ID of the ticket
     * @return array An array of stdClass objects representing each reply, keyed by the reply ID
     */
    private function getValidTicketReplies($ticket_id)
    {
        $valid_replies = [];

        if (($ticket = $this->get($ticket_id))) {
            foreach ($ticket->replies as $reply) {
                if (in_array($reply->type, ['reply', 'note'])) {
                    $valid_replies[$reply->id] = $reply;
                }
            }
        }

        return $valid_replies;
    }

    /**
     * Validates that the given open tickets can be merged into the given ticket
     *
     * @param array $tickets A list of ticket IDs
     * @param int $ticket_id The ID of the ticket the tickets are to be merged into
     * @return bool True if all of the given tickets can be merged into the ticket, or false otherwise
     */
    public function validateTicketsMergeable(array $tickets, $ticket_id)
    {
        // Fetch the ticket
        $ticket = $this->get($ticket_id, false);
        if (!$ticket || $ticket->status == 'closed') {
            return false;
        }

        // Check whether every ticket belongs to the same client (or email address),
        // belongs to the same company, and are open
        foreach ($tickets as $old_ticket_id) {
            // Fetch the ticket
            $old_ticket = $this->get($old_ticket_id, false);
            if (!$old_ticket) {
                return false;
            }

            // Check company matches, client matches, and ticket is open
            if (($old_ticket->company_id != $ticket->company_id) || ($old_ticket->status == 'closed') ||
                ($old_ticket->client_id != $ticket->client_id || $old_ticket->email != $ticket->email)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validates that all of the given tickets can be updated to the associated service
     *
     * @param array $vars An array consisting of arrays of ticket vars whose index
     *  refers to the index of the $ticket_ids array representing the vars of the specific
     *  ticket to update; or an array of vars to apply to all tickets; each including:
     *  - service_id The ID of the client service this ticket relates to
     * @param array $ticket_ids An array of ticket IDs to update
     * @return bool True if the service(s) match the tickets, or false otherwise
     */
    public function validateServicesMatchTickets(array $vars, array $ticket_ids)
    {
        // Determine whether to apply vars to all tickets, or whether each ticket has separate vars
        $separate_vars = (isset($vars[0]) && is_array($vars[0]));

        // Check whether the tickets can be assigned to the given service(s)
        foreach ($ticket_ids as $key => $ticket_id) {
            // Each ticket has separate vars specific to that ticket
            $temp_vars = $vars;
            if ($separate_vars) {
                // Since all fields are optional, we don't need to require a service_id be given
                if (!isset($vars[$key]) || empty($vars[$key])) {
                    $vars[$key] = [];
                }

                $temp_vars = $vars[$key];
            }

            // Check whether the client has this service
            if (isset($temp_vars['service_id'])) {
                // Fetch the ticket
                $ticket = $this->get($ticket_id, false);
                if ($ticket && !empty($ticket->client_id)) {
                    // Check whether the client has the service
                    $services = $this->Record->select(['id'])
                        ->from('services')
                        ->where('client_id', '=', $ticket->client_id)
                        ->fetchAll();
                    $temp_services = [];
                    foreach ($services as $service) {
                        $temp_services[] = $service->id;
                    }

                    if (!in_array($temp_vars['service_id'], $temp_services)) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validates that all of the given tickets can be updated to the associated department
     *
     * @param array $vars An array consisting of arrays of ticket vars whose index
     *  refers to the index of the $ticket_ids array representing the vars of the specific
     *  ticket to update; or an array of vars to apply to all tickets; each including:
     *  - department_id The department to reassign the ticket to
     * @param array $ticket_ids An array of ticket IDs to update
     * @return bool True if the department(s) match the tickets, or false otherwise
     */
    public function validateDepartmentsMatchTickets(array $vars, array $ticket_ids)
    {
        // Determine whether to apply vars to all tickets, or whether each ticket has separate vars
        $separate_vars = (isset($vars[0]) && is_array($vars[0]));

        // Check whether the tickets can be assigned to the given service(s)
        foreach ($ticket_ids as $key => $ticket_id) {
            // Each ticket has separate vars specific to that ticket
            $temp_vars = $vars;
            if ($separate_vars) {
                // Since all fields are optional, we don't need to require a department_id be given
                if (!isset($vars[$key]) || empty($vars[$key])) {
                    $vars[$key] = [];
                }

                $temp_vars = $vars[$key];
            }

            if (isset($temp_vars['department_id'])) {
                // Fetch the ticket
                $ticket = $this->get($ticket_id, false);
                if ($ticket) {
                    // Fetch the department company of this ticket
                    $department = $this->Record->select(['company_id'])
                        ->from('support_departments')
                        ->where('id', '=', $ticket->department_id)
                        ->fetch();

                    // Ensure the new department is in the same company as the ticket's department
                    $same_company = $this->Record->select()->from('support_departments')->
                        where('id', '=', $temp_vars['department_id'])->
                        where('company_id', '=', ($department ? $department->company_id : ''))->
                        fetch();

                    if (!$same_company) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Generates a ticket number
     *
     * @return int A ticket number
     */
    private function generateCode()
    {
        // Determine the number of digits to contain in the ticket number
        $digits = (int)Configure::get('SupportManager.ticket_code_length');
        $min = str_pad('1', $digits, '1');
        $max = str_pad('9', $digits, '9');

        // Attempt to generate a ticket code without duplicates 3 times
        // and accepts the third ticket code regardless of duplication
        $attempts = 0;
        $ticket_code = '';
        while ($attempts++ < 3) {
            $ticket_code = mt_rand($min, $max);

            // Skip if this ticket already exists
            if ($this->validateExists($ticket_code, 'code', 'support_tickets')) {
                continue;
            }
            return $ticket_code;
        }
        return $ticket_code;
    }

    /**
     * Returns an array containing the email tags from the custom fields of a given ticket
     *
     * @param int $ticket_id
     * @return array An array containing "custom_fields", which contains the ticket custom fields where the key is the
     *  ID of the field or the snake_case version of the field label
     */
    private function getCustomFieldsEmailTags(int $ticket_id) : array
    {
        $ticket = $this->get($ticket_id);

        $tags = [];
        if (!empty($ticket->custom_fields)) {
            foreach ($ticket->custom_fields as $field_id => $value) {
                $field = $this->Record->select()
                    ->from('support_department_fields')
                    ->where('support_department_fields.id', '=', $field_id)
                    ->fetch();
                $key = Loader::fromCamelCase(str_replace(' ', '', ucwords(strtolower($field->label))));

                $tags['custom_fields'][$field_id] = $value;
                $tags['custom_fields'][$key] = $value;
            }
        }

        return $tags;
    }
}
