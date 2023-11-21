<?php
// Errors
$lang['SupportManagerTickets.!error.code.format'] = 'The ticket code must contain only digits.';
$lang['SupportManagerTickets.!error.department_id.exists'] = 'Please select a valid department.';
$lang['SupportManagerTickets.!error.staff_id.exists'] = 'The staff member selected for this ticket does not exist.';
$lang['SupportManagerTickets.!error.contact_id.exists'] = 'The contact selected for this ticket reply does not exist.';
$lang['SupportManagerTickets.!error.contact_id.valid'] = 'The contact may not reply to the ticket without a valid client.';
$lang['SupportManagerTickets.!error.by_staff_id.exists'] = 'The staff member set as performing the edit does not exist.';
$lang['SupportManagerTickets.!error.service_id.exists'] = 'The service selected for this ticket does not exist.';
$lang['SupportManagerTickets.!error.service_id.belongs'] = 'The service selected is invalid.';
$lang['SupportManagerTickets.!error.client_id.exists'] = 'The client selected for this ticket does not exist.';
$lang['SupportManagerTickets.!error.client_id.set'] = 'The ticket belongs to another client and the assigned client may not be changed.';
$lang['SupportManagerTickets.!error.email.format'] = 'Please enter a valid email address.';
$lang['SupportManagerTickets.!error.summary.empty'] = 'Please enter a summary.';
$lang['SupportManagerTickets.!error.summary.length'] = 'The summary may not exceed 255 characters in length.';
$lang['SupportManagerTickets.!error.priority.format'] = 'Please select a valid priority.';
$lang['SupportManagerTickets.!error.status.format'] = 'Please select a valid status.';
$lang['SupportManagerTickets.!error.status.trash'] = 'A trashed ticket may not be edited.';
$lang['SupportManagerTickets.!error.date_added.format'] = 'The ticket added date is in an invalid date format.';
$lang['SupportManagerTickets.!error.date_updated.format'] = 'The ticket updated date is in an invalid date format.';
$lang['SupportManagerTickets.!error.date_closed.format'] = 'The ticket close date is in an invalid date format.';
$lang['SupportManagerTickets.!error.ticket_id.exists'] = 'Invalid ticket ID.';

$lang['SupportManagerTickets.!error.type.format'] = 'Please select a valid reply type.';
$lang['SupportManagerTickets.!error.type.new_valid'] = "New tickets must have a reply type of 'reply'.";
$lang['SupportManagerTickets.!error.details.empty'] = 'Please enter some details about this ticket.';
$lang['SupportManagerTickets.!error.ticket_id.exists'] = 'Invalid ticket ID.';
$lang['SupportManagerTickets.!error.client_id.attached_to'] = 'The ticket reply may not be from a different client.';

$lang['SupportManagerTickets.!error.replies.valid'] = 'At least one ticket reply ID is invalid, or all replies have been selected. You must leave at least one reply remaining.';
$lang['SupportManagerTickets.!error.replies.notes'] = 'Ticket reply notes may not be split into a separate ticket without also including a ticket reply.';
$lang['SupportManagerTickets.!error.tickets.valid'] = 'At least one ticket selected is invalid, closed, or does not belong to the same client as the chosen ticket.';
$lang['SupportManagerTickets.!error.merge_into.itself'] = 'The ticket may not be merged with itself.';

$lang['SupportManagerTickets.!error.tickets.service_matches'] = 'At least one of the tickets could not be assigned to the given service because it does not belong to the associated client.';
$lang['SupportManagerTickets.!error.tickets.department_matches'] = 'At least one of the tickets could not be assigned to the given department because it does not belong to the same company.';

$lang['SupportManagerTickets.!error.ticket_ids[].exists'] = 'At least one ticket ID is invalid.';
$lang['SupportManagerTickets.!error.client_id.exists'] = 'Invalid client ID.';
$lang['SupportManagerTickets.!error.client_id.company'] = 'At least one ticket does not belong to the same company as the given client.';
$lang['SupportManagerTickets.!error.staff_id.exists'] = 'Invalid staff ID.';


// Replies
$lang['SupportManagerTickets.merge.reply'] = 'This ticket has been merged into ticket #%1$s.'; // %1$s is the ticket number


// Priorities
$lang['SupportManagerTickets.priority.emergency'] = 'Emergency';
$lang['SupportManagerTickets.priority.critical'] = 'Critical';
$lang['SupportManagerTickets.priority.high'] = 'High';
$lang['SupportManagerTickets.priority.medium'] = 'Medium';
$lang['SupportManagerTickets.priority.low'] = 'Low';


// Statuses
$lang['SupportManagerTickets.status.open'] = 'Awaiting Staff Reply';
$lang['SupportManagerTickets.status.awaiting_reply'] = 'Awaiting Client Reply';
$lang['SupportManagerTickets.status.in_progress'] = 'In Progress';
$lang['SupportManagerTickets.status.on_hold'] = 'On Hold';
$lang['SupportManagerTickets.status.closed'] = 'Closed';
$lang['SupportManagerTickets.status.trash'] = 'Trash';


// Reply types
$lang['SupportManagerTickets.type.reply'] = 'Reply';
$lang['SupportManagerTickets.type.note'] = 'Note';
$lang['SupportManagerTickets.type.log'] = 'Log';


// Log text
$lang['SupportManagerTickets.log.department_id'] = 'The department has been changed to %1$s.'; // %1$s is the department name
$lang['SupportManagerTickets.log.summary'] = 'The summary has been updated.';
$lang['SupportManagerTickets.log.priority'] = 'The priority has been changed to %1$s.'; // %1$s is the priority
$lang['SupportManagerTickets.log.status'] = 'The status has been changed to %1$s.'; // %1$s is the status
$lang['SupportManagerTickets.log.ticket_staff_id'] = 'Assigned to %1$s.'; // %1$s is the name of the department staff member the ticket was assigned to
$lang['SupportManagerTickets.log.unassigned'] = 'Not Assigned';

$lang['SupportManagerTickets.reassign_note'] = 'This ticket was re-assigned to %1$s. Previous client replies were re-assigned to this client and may have been made by another person.'; // %1$s is the client name
