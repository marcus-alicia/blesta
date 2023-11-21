<?php
// Errors
$lang['SupportManagerDepartments.!error.company_id.exists'] = 'Invalid company ID.';
$lang['SupportManagerDepartments.!error.name.empty'] = 'Please enter a name for this department.';
$lang['SupportManagerDepartments.!error.description.empty'] = 'Please enter a description.';
$lang['SupportManagerDepartments.!error.email.format'] = 'Please enter a valid email address.';
$lang['SupportManagerDepartments.!error.method.format'] = 'Invalid method type.';
$lang['SupportManagerDepartments.!error.method.imap'] = 'The PHP IMAP extension is required for downloading messages via POP3 or IMAP.';
$lang['SupportManagerDepartments.!error.method.mailparse'] = 'The PHP Mailparse PECL extension is required for parsing email tickets.';
$lang['SupportManagerDepartments.!error.default_priority.format'] = 'Invalid default priority type.';
$lang['SupportManagerDepartments.!error.host.format'] = 'Please enter a valid host name.';
$lang['SupportManagerDepartments.!error.host.length'] = 'The host name may not exceed 128 characters in length.';
$lang['SupportManagerDepartments.!error.user.format'] = 'Please enter a user name.';
$lang['SupportManagerDepartments.!error.user.length'] = 'The user name may not exceed 64 characters in length.';
$lang['SupportManagerDepartments.!error.password.format'] = 'Please enter a password.';
$lang['SupportManagerDepartments.!error.port.format'] = 'Please enter a port number.';
$lang['SupportManagerDepartments.!error.port.length'] = 'The port may not exceed 6 digits in length.';
$lang['SupportManagerDepartments.!error.service.format'] = 'Please select a valid service type.';
$lang['SupportManagerDepartments.!error.security.format'] = 'Please select a valid security type.';
$lang['SupportManagerDepartments.!error.mark_messages.format'] = 'Please select a valid message type to mark messages.';
$lang['SupportManagerDepartments.!error.mark_messages.valid'] = 'Messages using POP3 may only be marked as deleted.';
$lang['SupportManagerDepartments.!error.clients_only.format'] = 'Whether to allow clients to open or reply to tickets must be set to 0 or 1.';
$lang['SupportManagerDepartments.!error.require_captcha.format'] = 'Whether to require human verification must be set to 0 or 1.';
$lang['SupportManagerDepartments.!error.override_from_email.format'] = "Whether to allow this department's email address to be used as the from address in email templates must be set to 0 or 1.";
$lang['SupportManagerDepartments.!error.send_ticket_received.format'] = "Whether to send ticket confirmation emails for this department must be set to 0 or 1.";
$lang['SupportManagerDepartments.!error.close_ticket_interval.format'] = 'Please select a valid close ticket interval.';
$lang['SupportManagerDepartments.!error.delete_ticket_interval.format'] = 'Please select a valid delete ticket interval.';
$lang['SupportManagerDepartments.!error.reminder_ticket_interval.format'] = 'Please select a valid reminder ticket interval.';
$lang['SupportManagerDepartments.!error.reminder_ticket_status.format'] = 'Please select a valid ticket reminder status.';
$lang['SupportManagerDepartments.!error.reminder_ticket_priority.format'] = 'Please select a valid ticket reminder priority.';
$lang['SupportManagerDepartments.!error.include_attachments.format'] = 'Whether to include ticket attachments must be set to 0 or 1.';
$lang['SupportManagerDepartments.!error.attachment_types.length'] = 'The attachment type list may not exceed 255 characters in length.';
$lang['SupportManagerDepartments.!error.max_attachment_size.format'] = 'The maximum attachment size must be a numeric value.';
$lang['SupportManagerDepartments.!error.response_id.format'] = 'Please select a valid auto-close response.';
$lang['SupportManagerDepartments.!error.status.format'] = 'Invalid status type.';
$lang['SupportManagerDepartments.!error.department_id.exists'] = 'Invalid department ID.';
$lang['SupportManagerDepartments.!error.label.empty'] = 'Please enter a label.';
$lang['SupportManagerDepartments.!error.visibility.format'] = 'Please select a valid visibility status.';
$lang['SupportManagerDepartments.!error.type.format'] = 'Please select a valid field type.';
$lang['SupportManagerDepartments.!error.client_add.format'] = 'Whether to allow client to add must be set to 0 or 1.';
$lang['SupportManagerDepartments.!error.encrypted.format'] = 'Whether to encrypt the field data must be set to 0 or 1.';
$lang['SupportManagerDepartments.!error.auto_delete.format'] = 'Whether to auto-delete the field data after closing a ticket must be set to 0 or 1.';

$lang['SupportManagerDepartments.!error.department_id.has_tickets'] = 'The department could not be deleted because it currently has tickets assigned to it.';


// Methods
$lang['SupportManagerDepartments.methods.none'] = 'None';
$lang['SupportManagerDepartments.methods.pipe'] = 'Piping';
$lang['SupportManagerDepartments.methods.pop3'] = 'POP3';
$lang['SupportManagerDepartments.methods.imap'] = 'IMAP';


// Statuses
$lang['SupportManagerDepartments.statuses.hidden'] = 'Hidden';
$lang['SupportManagerDepartments.statuses.visible'] = 'Visible';


// Priorities
$lang['SupportManagerDepartments.priorities.emergency'] = 'Emergency';
$lang['SupportManagerDepartments.priorities.critical'] = 'Critical';
$lang['SupportManagerDepartments.priorities.high'] = 'High';
$lang['SupportManagerDepartments.priorities.medium'] = 'Medium';
$lang['SupportManagerDepartments.priorities.low'] = 'Low';


// Security types
$lang['SupportManagerDepartments.security_types.none'] = 'None';
$lang['SupportManagerDepartments.security_types.ssl'] = 'SSL';
$lang['SupportManagerDepartments.security_types.tls'] = 'TLS';


// Message types
$lang['SupportManagerDepartments.message_types.read'] = 'Read';
$lang['SupportManagerDepartments.message_types.deleted'] = 'Deleted';


// Field types
$lang['SupportManagerDepartments.field_types.checkbox'] = 'Checkbox';
$lang['SupportManagerDepartments.field_types.radio'] = 'Radio';
$lang['SupportManagerDepartments.field_types.select'] = 'Drop-down';
$lang['SupportManagerDepartments.field_types.quantity'] = 'Quantity';
$lang['SupportManagerDepartments.field_types.text'] = 'Text';
$lang['SupportManagerDepartments.field_types.textarea'] = 'Textarea';
$lang['SupportManagerDepartments.field_types.password'] = 'Password';


// Visibility options
$lang['SupportManagerDepartments.visibility_options.client'] = 'Client and Staff';
$lang['SupportManagerDepartments.visibility_options.staff'] = 'Staff Only';


// Ticket Intervals
$lang['SupportManagerDepartments.ticket_intervals.day'] = '1 Day';
$lang['SupportManagerDepartments.ticket_intervals.days'] = '%1$s Days'; // %1$s is the number of days


// Reminder Intervals
$lang['SupportManagerDepartments.reminder_intervals.hour'] = '1 Hour';
$lang['SupportManagerDepartments.reminder_intervals.hours'] = '%1$s Hours'; // %1$s is the number of hours
$lang['SupportManagerDepartments.reminder_intervals.minutes'] = '%1$s Minutes'; // %1$s is the number of minutes
