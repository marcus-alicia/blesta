<?php
// Dates
Configure::set('SupportManager.time_format', 'H:i:s');
Configure::set('SupportManager.reply_date_format', 'M j Y g:i A');

// Summary truncate length (max number of words)
Configure::set('SupportManager.summary_truncate_length', 8);
// Default summary if blank
Configure::set('SupportManager.summary_default', 'N/A');

// Ticket number code length in number of digits. In case of conflicts (duplicate ticket codes) because of a large number of tickets, increase this number.
Configure::set('SupportManager.ticket_code_length', 7);

// The maximum number of popular Knowledgebase articles to show
Configure::set('SupportManager.max_kb_popular_articles', 5);
// The max number of characters in an article title to set in the URI, separated on words
Configure::set('SupportManager.max_chars_article_title_uri', 50);

// Emails
Configure::set('SupportManager.install.emails', [
    [
        'action' => 'SupportManager.ticket_received',
        'type' => 'client',
        'plugin_dir' => 'support_manager',
        'tags' => '{ticket},{ticket_hash_code},{client},{reply_contact},{update_ticket_url}',
        'from' => 'support@mydomain.com',
        'from_name' => 'Support',
        'subject' => 'We have received your ticket',
        'text' => 'We have received your request and someone will be looking at it shortly.',
        'html' => '<p>We have received your request and someone will be looking at it shortly.</p>'
    ],
    [
        'action' => 'SupportManager.ticket_updated',
        'type' => 'client',
        'plugin_dir' => 'support_manager',
        'tags' => '{ticket},{update_ticket_url},{ticket_hash_code},{client}',
        'from' => 'support@mydomain.com',
        'from_name' => 'Support',
        'subject' => 'Update to Ticket {ticket_hash_code}',
        'text' => '{ticket.details}


--

To reply to this ticket, be sure to email {ticket.department_email} from the address we sent this notice to. You may also update the ticket in our support area at {update_ticket_url}.',
        'html' => '<p>
	{ticket.details_html}</p>
<p>
	&nbsp;</p>
<p>
	--</p>
<p>
	To reply to this ticket, be sure to email <a href="mailto:{ticket.department_email}">{ticket.department_email}</a> from the address we sent this notice to. You may also update the ticket in our support area at <a href="http://{update_ticket_url}">{update_ticket_url}</a>.</p>
'
    ],
    [
        'action' => 'SupportManager.ticket_bounce',
        'type' => 'client',
        'plugin_dir' => 'support_manager',
        'tags' => '',
        'from' => 'support@mydomain.com',
        'from_name' => 'Support',
        'subject' => 'Support Request Failed',
        'text' => 'Our system received your email, but was unable to process it for one of the following reasons..

1. The email address you sent the message from does not belong to any of our clients and this department only allows existing clients to open tickets.

2. You replied to a ticket notice, and we are unable to determine what ticket number you are responding to.

3. The department you emailed no longer exists.',
        'html' => '<p>
	Our system received your email, but was unable to process it for one of the following reasons..</p>
<p>
	1. The email address you sent the message from does not belong to any of our clients and this department only allows existing clients to open tickets.</p>
<p>
	2. You replied to a ticket notice, and we are unable to determine what ticket number you are responding to.</p>
<p>
	3. The department you emailed no longer exists.</p>
'
    ],
    [
        'action' => 'SupportManager.staff_ticket_updated',
        'type' => 'staff',
        'plugin_dir' => 'support_manager',
        'tags' => '{ticket},{ticket_hash_code},{client},{reply_contact}',
        'from' => 'support@mydomain.com',
        'from_name' => 'Support',
        'subject' => 'Update to Ticket {ticket_hash_code}',
        'text' => '{ticket.details}

--

Ticket #: {ticket.code}

Status: {ticket.status_language}

Priority: {ticket.priority_language}

Department: {ticket.department_name}

--

To reply to this ticket, be sure to email {ticket.department_email} from the address we sent this notice to, or you may do so from the Staff interface.',
        'html' => '<p>
	{ticket.details_html}</p>
<p>
	--</p>
<p>
	Ticket #: {ticket.code}</p>
<p>
	Status: {ticket.status_language}</p>
<p>
	Priority: {ticket.priority_language}</p>
<p>
	Department: {ticket.department_name}</p>
<p>
	--</p>
<p>
	To reply to this ticket, be sure to email <a href="mailto:{ticket.department_email}">{ticket.department_email}</a> from the address we sent this notice to, or you may do so from the Staff interface.</p>
'
    ],
    [
        'action' => 'SupportManager.staff_ticket_updated_mobile',
        'type' => 'staff',
        'plugin_dir' => 'support_manager',
        'tags' => '{ticket},{ticket_hash_code},{client},{reply_contact}',
        'from' => 'support@mydomain.com',
        'from_name' => 'Support',
        'subject' => 'Ticket {ticket_hash_code}',
        'text' => '{ticket.details}

--

Ticket #: {ticket.code} | Status: {ticket.status_language} | Priority: {ticket.priority_language} | Department: {ticket.department_name}
',
        'html' => '<p>
	{ticket.details_html}</p>
<p>
	--</p>
<p>
	Ticket #: {ticket.code} | Status: {ticket.status_language} | Priority: {ticket.priority_language} | Department: {ticket.department_name}</p>
<p>
	&nbsp;</p>
'
    ],
    [
        'action' => 'SupportManager.staff_ticket_assigned',
        'type' => 'staff',
        'plugin_dir' => 'support_manager',
        'tags' => '{ticket},{staff.first_name},{staff.last_name}',
        'from' => 'support@mydomain.com',
        'from_name' => 'Support',
        'subject' => 'Ticket #{ticket.code} has been assigned to you',
        'text' => '{staff.first_name},

A ticket has been assigned to you.

--

Ticket #: {ticket.code} | Status: {ticket.status_language} | Priority: {ticket.priority_language} | Department: {ticket.department_name}
',
        'html' => '<p>{staff.first_name},</p>
<p>A ticket has been assigned to you.</p>
<p>--</p>
<p>Ticket #: {ticket.code} | Status: {ticket.status_language} | Priority: {ticket.priority_language} | Department: {ticket.department_name}</p>
'
    ],
    [
        'action' => 'SupportManager.ticket_reminder',
        'type' => 'client',
        'plugin_dir' => 'support_manager',
        'tags' => '{ticket},{client}',
        'from' => 'support@mydomain.com',
        'from_name' => 'Support',
        'subject' => 'Ticket #{ticket.code} is waiting for your reply',
        'text' => 'We have not received a reply from you for quite a while, if we do not receive a reply from you soon we will consider the ticket as solved and we will proceed to close it.',
        'html' => '<p>We have not received a reply from you for quite a while, if we do not receive a reply from you soon we will consider the ticket as solved and we will proceed to close it.</p>'
    ],
    [
        'action' => 'SupportManager.staff_ticket_reminder',
        'type' => 'staff',
        'plugin_dir' => 'support_manager',
        'tags' => '{ticket},{staff.first_name},{staff.last_name}',
        'from' => 'support@mydomain.com',
        'from_name' => 'Support',
        'subject' => 'Ticket #{ticket.code} is waiting for your reply',
        'text' => '{staff.first_name},

A ticket is waiting for your reply.

--

Ticket #: {ticket.code} | Status: {ticket.status_language} | Priority: {ticket.priority_language} | Department: {ticket.department_name}
',
        'html' => '<p>{staff.first_name},</p>
<p>A ticket is waiting for your reply.</p>
<p>--</p>
<p>Ticket #: {ticket.code} | Status: {ticket.status_language} | Priority: {ticket.priority_language} | Department: {ticket.department_name}</p>
'
    ]
]);

// Messages
Configure::set('SupportManager.install.messages', [
    [
        'action' => 'SupportManager.staff_ticket_updated',
        'type' => 'staff',
        'tags' => '{ticket},{ticket_hash_code},{client},{reply_contact}',
        'content' => ['sms' => 'Ticket #: {ticket.code}
        
A ticket has been updated.

Status: {ticket.status_language}
Priority: {ticket.priority_language}
Department: {ticket.department_name}
Details: {ticket.details}
'
        ]
    ],
    [
        'action' => 'SupportManager.staff_ticket_assigned',
        'type' => 'staff',
        'tags' => '{ticket},{staff.first_name},{staff.last_name}',
        'content' => ['sms' => 'Ticket #: {ticket.code}

A ticket has been assigned to you.

Status: {ticket.status_language}
Priority: {ticket.priority_language}
Department: {ticket.department_name}
'
        ]
    ]
]);

// Image MIME types
Configure::set('SupportManager.image_mime_types', ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'ico']);
Configure::set('SupportManager.thumbnails_per_row', 6);

