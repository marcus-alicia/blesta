<?php
/**
 * Ticket Manager component
 *
 * Connects to POP3/IMAP mail servers to download emails for each ticket department,
 * and creates tickets or ticket replies from provided email messages.
 *
 * @package blesta
 * @subpackage blesta.plugins.support_manager.components
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class TicketManager
{
    /**
     * @var int The maximum number of replies that may be sent within a given period of time
     */
    private $max_reply_limit = 5;
    /**
     * @var string Amount of time to verify replies within
     */
    private $reply_period = '5 minutes';
    /**
     * @var array A set of options for ticket emails, e.g. "client_uri"
     */
    private $options = [];
    /**
     * @var string The path to the system temp directory
     */
    private $tmp_dir = null;

    /**
     * Sets an array of options for use with processing tickets from email, e.g. "client_uri", "admin_uri", "is_cli"
     *
     * @param array $options A set of key/value pairs
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Connects to all POP3/IMAP support departments and processes emails for
     * each, creating or replying to tickets as necessary.
     */
    public function processDepartmentEmails()
    {
        $company_id = Configure::get('Blesta.company_id');

        Loader::load(PLUGINDIR . 'support_manager' . DS . 'vendors' . DS . 'php-imap' . DS . 'ImapMailbox.php');
        Loader::load(
            PLUGINDIR . 'support_manager' . DS . 'vendors' . DS . 'mime_mail_parser' . DS . 'MimeMailParser.class.php'
        );

        Loader::loadModels($this, ['SupportManager.SupportManagerDepartments']);

        // Download messages for reach department
        foreach ($this->SupportManagerDepartments->getByMethod($company_id, ['pop3', 'imap']) as $department) {
            $host = $department->host;
            $port = $department->port;
            $user = $department->user;
            $password = $department->pass;
            $service = $department->method;
            $security = $department->security == 'none' ? null : $department->security;
            $box_name = $department->box_name;
            $mark_as = $department->mark_messages;

            // POP3 always returns all messages, so we must delete any messages
            // we've already read to prevent them from creating duplicate tickets
            if ($service == 'pop3') {
                $mark_as = 'deleted';
            }

            $flags = [];
            $flags[] = $service;
            if ($security) {
                $flags[] = $security;
            }
            $flags[] = 'novalidate-cert';

            $connection = '{' . $host . ($port ? ':' . $port : null) . '/'
                . implode('/', $flags) . '}' . imap_utf7_encode($box_name);

            try {
                $mailbox = new ImapMailbox($connection, $user, $password);

                $search_type = 'ALL';
                if ($mark_as == 'read') {
                    $search_type = 'UNSEEN';
                }

                foreach ($mailbox->searchMailbox($search_type) as $mail_id) {
                    $email = new MimeMailParser();

                    $email->setText($mailbox->fetchHeader($mail_id) . $mailbox->fetchBody($mail_id));
                    $this->processTicketFromEmail($email, $department);

                    if ($mark_as == 'read') {
                        $mailbox->markMessageAsRead($mail_id);
                    } else {
                        $mailbox->deleteMessage($mail_id);
                    }
                }
                unset($mailbox);
            } catch (Exception $e) {
                // Ignore errors, continue on to the next department
            }
        }
    }

    /**
     * Creates a ticket or ticket reply from an email message
     *
     * @param MimeMailParser $email The email object
     * @param stdClass $department A stdClass object representing the department,
     *  null to detect the department from the TO address
     */
    public function processTicketFromEmail(MimeMailParser $email, $department = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $company_id = Configure::get('Blesta.company_id');

        if (!isset($this->EmailParser)) {
            Loader::loadComponents($this, ['SupportManager.EmailParser']);
        }
        if (!isset($this->SupportManagerTickets)) {
            Loader::loadModels($this, ['SupportManager.SupportManagerTickets']);
        }
        if (!isset($this->SupportManagerDepartments)) {
            Loader::loadModels($this, ['SupportManager.SupportManagerDepartments']);
        }
        if (!isset($this->Settings)) {
            Loader::loadModels($this, ['Settings']);
        }

        if (!$this->tmp_dir) {
            $tmp_dir = $this->Settings->getSetting('temp_dir');
            if ($tmp_dir) {
                $this->tmp_dir = $tmp_dir->value;
            }
        }

        // Fetch TO address
        $to = $this->EmailParser->getAddress($email, 'x-original-to');
        if (empty($to)) {
            $to = $this->EmailParser->getAddress($email, 'to');
        }
        $to = array_unique($to);

        $from = $this->EmailParser->getAddress($email, 'from');
        if (isset($from[0])) {
            $from = $from[0];
        }

        // From address must be a string
        $from = (is_string($from) ? $from : '');

        $subject = $this->EmailParser->getSubject($email);
        $subject = $subject == '' ? Configure::get('SupportManager.summary_default') : $subject;

        $ticket_info = $this->SupportManagerTickets->parseTicketInfo($subject);

        $body = $this->EmailParser->getText($email);
        if ($ticket_info) {
            $body = $this->cleanupBody($body);
        }

        // Ensure there exists email body content to be used in the reply
        // so that the reply is always added to the ticket
        $body = (!empty($body) ? $body : '<<NO CONTENT>>');

        // Fetch the references to all files uploaded for this ticket
        $files = $this->EmailParser->getAttachments($email, $this->tmp_dir);

        // Set company hostname and client URI for ticket email
        $client_uri = (array_key_exists('client_uri', $this->options)
            ? $this->options['client_uri']
            : WEBDIR . Configure::get('Route.client') . '/'
        );
        $hostname = isset(Configure::get('Blesta.company')->hostname) ? Configure::get('Blesta.company')->hostname : '';

        // Handle ticket replies
        if ($ticket_info) {
            // Ensure ticket code is valid
            if ($ticket_info['valid']) {
                $ticket = $this->SupportManagerTickets->getTicketByCode($ticket_info['ticket_code'], false);

                // If ticket found, record the reply
                if ($ticket) {
                    $reply = [
                        'type' => 'reply',
                        'details' => $body,
                        'staff_id' => null,
                        'client_id' => null
                    ];

                    // Re-open this ticket
                    if ($ticket->status == 'closed') {
                        $reply['status'] = 'open';
                    }

                    // Fetch the department
                    if ($department === null) {
                        $department = $this->SupportManagerDepartments->get($ticket->department_id);
                    }

                    // If reply came from staff member, put staff ID
                    if (($staff = $this->SupportManagerDepartments->getStaffByEmail($ticket->department_id, $from))) {
                        $reply['staff_id'] = $staff->id;

                        // If the ticket was previously open change it to awaiting client's reply
                        if ($ticket->status == 'open' && $department && $department->automatic_transition == '1') {
                            $reply['status'] = 'awaiting_reply';
                        }
                    } else {
                        // If the reply was not from a staff member, it must have been the client
                        $reply['client_id'] = $ticket->client_id;

                        // Check if one of the client's contacts replied
                        if (($contact = $this->SupportManagerTickets->getContactByEmail($reply['client_id'], $from))
                            && $contact->contact_type != 'primary') {
                            $reply['contact_id'] = $contact->id;
                        }

                        // If the ticket was previously awaiting this client's reply change it back to open
                        if ($ticket->status == 'awaiting_reply') {
                            $reply['status'] = 'open';
                        }
                    }

                    // Check if only clients are allowed to reply to tickets
                    if ($reply['staff_id'] == null && $reply['client_id'] == null
                        && $department && $department->clients_only) {
                        // Only clients are allowed to reply to tickets
                        $this->sendBounceNotice($email);
                        return;
                    }

                    $reply_id = $this->SupportManagerTickets->addReply($ticket->id, $reply, $files);

                    if (!$reply_id) {
                        // Ticket reply failed
                        $this->sendBounceNotice($email, $reply['client_id']);
                    } else {
                        // Don't allow reply to be sent if enough emails have been sent to this
                        // address within the given window of time
                        if ($department
                            && $this->SupportManagerTickets->checkLoopBack(
                                $department->email,
                                $this->max_reply_limit,
                                $this->reply_period
                            )
                        ) {
                            // Send the email associated with this ticket
                            $key = mt_rand();
                            $hash = $this->SupportManagerTickets->generateReplyHash($ticket->id, $key);
                            $additional_tags = [
                                'SupportManager.ticket_updated' => [
                                    'update_ticket_url' => $this->Html->safe(
                                        $hostname . $client_uri . 'plugin/support_manager/client_tickets/reply/'
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
                        }
                    }
                } else {
                    // Ticket not found
                    $this->sendBounceNotice($email);
                    return;
                }
            } else {
                // Ticket code is not valid
                $this->sendBounceNotice($email);
                return;
            }
        } else {
            // Handle creating a new ticket
            $department_found = false;
            // Attempt to create a ticket from the first valid department
            foreach ($to as $address) {
                // Look up department based on to address if not given
                if (!$department) {
                    $department = $this->SupportManagerDepartments->getByEmail($company_id, $address);
                }

                if ($department) {
                    $department_found = true;

                    // Try to find an existing client with this from address to assign the ticket to
                    $client = $this->SupportManagerTickets->getClientByEmail($company_id, $from);

                    $client_id = null;
                    $from_email = null;

                    if ($client) {
                        $client_id = $client->id;
                    } else {
                        $from_email = $from;
                    }

                    // Ignore tickets opened by the support department itself
                    if ($department->email == $from_email) {
                        return;
                    }

                    // Check if only clients are allowed to open tickets
                    if ($client_id == null && $department->clients_only) {
                        // Only clients are allowed to open tickets
                        $this->sendBounceNotice($email);
                        return;
                    }

                    $ticket_info = [
                        'department_id' => $department->id,
                        'summary' => $subject,
                        'priority' => $department->default_priority
                    ];

                    if ($client_id) {
                        $ticket_info['client_id'] = $client_id;
                    }
                    if ($from_email) {
                        $ticket_info['email'] = $from_email;
                    }

                    $ticket_id = $this->SupportManagerTickets->add($ticket_info, ($from_email ? true : false));

                    if (!$ticket_id) {
                        // Ticket could not be added
                        $this->sendBounceNotice($email, $client_id);
                        return;
                    }

                    $reply = [
                        'type' => 'reply',
                        'details' => $body
                    ];

                    if ($client_id) {
                        $reply['client_id'] = $client_id;

                        // Check if one of the client's contacts created the ticket
                        if (($contact = $this->SupportManagerTickets->getContactByEmail($reply['client_id'], $from))
                            && $contact->contact_type != 'primary') {
                            $reply['contact_id'] = $contact->id;
                        }
                    }

                    $reply_id = $this->SupportManagerTickets->addReply($ticket_id, $reply, $files, true);

                    // Don't allow reply to be sent if enough emails have been sent to this
                    // address within the given window of time
                    if ($this->SupportManagerTickets->checkLoopBack(
                        $address,
                        $this->max_reply_limit,
                        $this->reply_period
                    )) {
                        // Send the email associated with this ticket
                        $key = mt_rand();
                        $hash = $this->SupportManagerTickets->generateReplyHash($ticket_id, $key);
                        $additional_tags = [
                            'SupportManager.ticket_updated' => [
                                'update_ticket_url' => $this->Html->safe(
                                    $hostname . $client_uri . 'plugin/support_manager/client_tickets/reply/'
                                    . $ticket_id . '/?sid='
                                    . rawurlencode(
                                        $this->SupportManagerTickets->systemEncrypt(
                                            'h=' . substr($hash, -16) . '|k=' . $key
                                        )
                                    )
                                )
                            ]
                        ];
                        $this->SupportManagerTickets->sendEmail($reply_id, $additional_tags);
                    }
                    return;
                }
            }

            if (!$department_found) {
                // Department not found
                $this->sendBounceNotice($email);
            }
        }
    }

    /**
     * Sends a bounce to sender
     *
     * @param MimeMailParser $email The email object that bounced
     * @param int $client_id The ID of the client that sent the email (if any)
     */
    private function sendBounceNotice(MimeMailParser $email, $client_id = null)
    {
        if (!isset($this->Emails)) {
            Loader::loadModels($this, ['Emails']);
        }
        if (!isset($this->Clients)) {
            Loader::loadModels($this, ['Clients']);
        }

        // Send email to the from address
        $to = $this->EmailParser->getAddress($email, 'from');
        if (isset($to[0])) {
            $to = $to[0];
        }

        // Don't allow bounce to be sent if enough emails have been sent to this
        // address within the given window of time
        if (!$this->SupportManagerTickets->checkLoopBack($to, $this->max_reply_limit, $this->reply_period)) {
            return;
        }

        $lang = null;
        if ($client_id) {
            $client = $this->Clients->get($client_id);

            if ($client && $client->settings['language']) {
                $lang = $client->settings['language'];
            }
        }

        $tags = [];
        $options = ['to_client_id' => $client_id];

        $this->Emails->send(
            'SupportManager.ticket_bounce',
            Configure::get('Blesta.company_id'),
            $lang,
            $to,
            $tags,
            null,
            null,
            null,
            $options
        );
    }

    /**
     * Clean the body of a message by removing quoted text
     *
     * @param string $body The body (possibly) containing quoted text
     * @return string The body with quoted text removed
     */
    private function cleanupBody($body)
    {
        return preg_replace("/^>.*?[\r\n]/m", '', $body);
    }
}
