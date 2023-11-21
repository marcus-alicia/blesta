<?php
// Success messages
$lang['AdminDepartments.!success.department_created'] = 'The %1$s department was successfully created.'; // %1$s is the name of the department
$lang['AdminDepartments.!success.department_updated'] = 'The %1$s department was successfully updated.'; // %1$s is the name of the department
$lang['AdminDepartments.!success.department_deleted'] = 'The %1$s department was successfully deleted.'; // %1$s is the name of the department


// Page titles
$lang['AdminDepartments.index.page_title'] = 'Support Manager > Departments';
$lang['AdminDepartments.add.page_title'] = 'Support Manager > Departments > Add Department';
$lang['AdminDepartments.edit.page_title'] = 'Support Manager > Departments > Edit Department';


// Index
$lang['AdminDepartments.index.categorylink_adddepartment'] = 'Add Department';
$lang['AdminDepartments.index.boxtitle_departments'] = 'Departments';

$lang['AdminDepartments.index.heading_name'] = 'Name';
$lang['AdminDepartments.index.heading_description'] = 'Description';
$lang['AdminDepartments.index.heading_email'] = 'Email';
$lang['AdminDepartments.index.heading_assigned_staff'] = 'Assigned Staff';
$lang['AdminDepartments.index.heading_default_priority'] = 'Default Priority';
$lang['AdminDepartments.index.heading_options'] = 'Options';
$lang['AdminDepartments.index.option_edit'] = 'Edit';
$lang['AdminDepartments.index.option_delete'] = 'Delete';
$lang['AdminDepartments.index.confirm_delete'] = 'Departments with tickets assigned to them may not be deleted until all tickets have been re-assigned to an alternate department. Are you sure you want to delete this department?';

$lang['AdminDepartments.index.no_results'] = 'There are no departments.';

$lang['AdminDepartments.assigned_staff.heading_assigned_staff'] = 'Assigned Staff';
$lang['AdminDepartments.assigned_staff.heading_staff'] = 'Staff';
$lang['AdminDepartments.assigned_staff.no_results'] = 'There are no staff assigned to this department.';

$lang['AdminDepartments.!tooltip.piping_config'] = 'Set your piping path as shown, but be sure to use the correct path to PHP on your server. Make sure pipe.php is executable. You may also need to edit pipe.php to include a hashbang on line 1 that would look something like: #!/usr/bin/php';
$lang['AdminDepartments.!tooltip.close_ticket_interval'] = 'All tickets with a status other than %1$s whose last reply is from a staff member will be automatically closed if no replies have been made within the selected amount of time.'; // %1$s is the ticket status In Progress
$lang['AdminDepartments.!tooltip.delete_ticket_interval'] = 'All tickets with a status of %1$s will be automatically deleted after remaining in that status for the selected amount of time.'; // %1$s is the ticket status (e.g. Trash)
$lang['AdminDepartments.!tooltip.reminder_ticket_interval'] = 'For all tickets with a status other than %1$s, a reminder will be sent to the other part if no replies have been made within the selected amount of time.'; // %1$s is the ticket status Closed
$lang['AdminDepartments.!tooltip.send_ticket_received'] = 'Unchecking this box indicates that new tickets created for this department (by email or client) will not send a reply notice to the client or staff through the Ticket Received email or Staff Ticket Updated email, respectively.';
$lang['AdminDepartments.!tooltip.automatic_transition'] = 'Changes the status to %1$s when a staff member replies.'; // %1$s is the 'Awaiting Client Reply' status
$lang['AdminDepartments.!tooltip.include_attachments'] = 'Mail servers have limitations on attachment sizes and file types. To mitigate the risk of email being rejected, please include attachment type and size limitations. To be sure the client is aware of attachments that were not included, use the {ticket.reply_has_attachments} tag in ticket notices.';
$lang['AdminDepartments.!tooltip.attachment_types'] = 'List of supported extensions separated by comma (e.g. zip,jpg,png).';
$lang['AdminDepartments.!tooltip.max_attachment_size'] = 'Maximum size of attachment to include in MB.';
$lang['AdminDepartments.!tooltip.client_add'] = 'Whether or not the client can submit the field, if false the client will only be able to read the field.';
$lang['AdminDepartments.!tooltip.auto_delete'] = 'If the ticket is closed, the field data will be deleted automatically. The data will no longer be visible to staff.';

$lang['AdminDepartments.!text.add_response'] = 'Set an Auto-Close Predefined Response';
$lang['AdminDepartments.!text.no_selected_response'] = 'No auto-close response selected.';
$lang['AdminDepartments.!text.remove_response'] = 'Remove';


// Add department
$lang['AdminDepartments.add.boxtitle_adddepartment'] = 'Add Department';

$lang['AdminDepartments.add.tab_general'] = 'General';
$lang['AdminDepartments.add.tab_custom_fields'] = 'Custom Fields';

$lang['AdminDepartments.add.field_name'] = 'Name';
$lang['AdminDepartments.add.field_description'] = 'Description';
$lang['AdminDepartments.add.field_clients_only'] = 'Allow only clients to open or reply to tickets';
$lang['AdminDepartments.add.field_require_captcha'] = 'Require Human Verification for unauthenticated users';
$lang['AdminDepartments.add.field_email'] = 'Email';
$lang['AdminDepartments.add.field_override_from_email'] = 'Override the from address set in email templates with the email address set for this department';
$lang['AdminDepartments.add.field_send_ticket_received'] = 'Send Auto-Response Emails for New Tickets';
$lang['AdminDepartments.add.field_automatic_transition'] = 'Automatically transition ticket status on admin reply';
$lang['AdminDepartments.add.field_method'] = 'Email Handling';
$lang['AdminDepartments.add.field_piping_config'] = 'Piping Configuration';
$lang['AdminDepartments.add.field_default_priority'] = 'Default Priority';
$lang['AdminDepartments.add.field_include_attachments'] = 'Include supported attachments in ticket notices';
$lang['AdminDepartments.add.field_attachment_types'] = 'Supported Attachment Types';
$lang['AdminDepartments.add.field_max_attachment_size'] = 'Max Attachment Size';
$lang['AdminDepartments.add.field_security'] = 'Security';
$lang['AdminDepartments.add.field_box_name'] = 'Box Name';
$lang['AdminDepartments.add.field_mark_messages'] = 'Mark Messages as';
$lang['AdminDepartments.add.field_host'] = 'Host';
$lang['AdminDepartments.add.field_user'] = 'User';
$lang['AdminDepartments.add.field_pass'] = 'Pass';
$lang['AdminDepartments.add.field_port'] = 'Port';
$lang['AdminDepartments.add.field_close_ticket_interval'] = 'Automatically Close Tickets';
$lang['AdminDepartments.add.field_delete_ticket_interval'] = 'Automatically Delete Tickets';
$lang['AdminDepartments.add.field_reminder_ticket_interval'] = 'Automatically Send Ticket Reminders';
$lang['AdminDepartments.add.field_reminder_ticket_status'] = 'Send Reminders to Tickets with Status';
$lang['AdminDepartments.add.field_reminder_ticket_priority'] = 'Send Reminders to Tickets with Priority';
$lang['AdminDepartments.add.field_response_id'] = 'Auto-Close Ticket Response';
$lang['AdminDepartments.add.field_status'] = 'Status';
$lang['AdminDepartments.add.field_addsubmit'] = 'Add Department';
$lang['AdminDepartments.add.field_add_field'] = 'Add Field';

$lang['AdminDepartments.add.heading_label'] = 'Label';
$lang['AdminDepartments.add.heading_description'] = 'Description';
$lang['AdminDepartments.add.heading_visibility'] = 'Visibility';
$lang['AdminDepartments.add.heading_type'] = 'Type';
$lang['AdminDepartments.add.heading_min'] = 'Min';
$lang['AdminDepartments.add.heading_max'] = 'Max';
$lang['AdminDepartments.add.heading_step'] = 'Step';
$lang['AdminDepartments.add.heading_client_add'] = 'Client can Add';
$lang['AdminDepartments.add.heading_encrypted'] = 'Encrypted';
$lang['AdminDepartments.add.heading_auto_delete'] = 'Auto-Delete';
$lang['AdminDepartments.add.heading_options'] = 'Options';
$lang['AdminDepartments.add.heading_name'] = 'Name';
$lang['AdminDepartments.add.heading_value'] = 'Value';
$lang['AdminDepartments.add.heading_default'] = 'Default';
$lang['AdminDepartments.add.text_delete'] = 'Delete';
$lang['AdminDepartments.add.text_add'] = 'Add';


// Edit department
$lang['AdminDepartments.edit.boxtitle_adddepartment'] = 'Edit Department';

$lang['AdminDepartments.edit.tab_general'] = 'General';
$lang['AdminDepartments.edit.tab_custom_fields'] = 'Custom Fields';

$lang['AdminDepartments.edit.field_name'] = 'Name';
$lang['AdminDepartments.edit.field_description'] = 'Description';
$lang['AdminDepartments.edit.field_clients_only'] = 'Allow only clients to open or reply to tickets';
$lang['AdminDepartments.edit.field_require_captcha'] = 'Require Human Verification for unauthenticated users';
$lang['AdminDepartments.edit.field_email'] = 'Email';
$lang['AdminDepartments.edit.field_override_from_email'] = 'Override the from address set in email templates with the email address set for this department';
$lang['AdminDepartments.edit.field_send_ticket_received'] = 'Send Auto-Response Emails for New Tickets';
$lang['AdminDepartments.edit.field_automatic_transition'] = 'Automatically transition ticket status on admin reply';
$lang['AdminDepartments.edit.field_method'] = 'Email Handling';
$lang['AdminDepartments.edit.field_piping_config'] = 'Piping Configuration';
$lang['AdminDepartments.edit.field_default_priority'] = 'Default Priority';
$lang['AdminDepartments.edit.field_include_attachments'] = 'Include supported attachments in ticket notices';
$lang['AdminDepartments.edit.field_attachment_types'] = 'Supported Attachment Types';
$lang['AdminDepartments.edit.field_max_attachment_size'] = 'Max Attachment Size';
$lang['AdminDepartments.edit.field_security'] = 'Security';
$lang['AdminDepartments.edit.field_box_name'] = 'Box Name';
$lang['AdminDepartments.edit.field_mark_messages'] = 'Mark Messages as';
$lang['AdminDepartments.edit.field_host'] = 'Host';
$lang['AdminDepartments.edit.field_user'] = 'User';
$lang['AdminDepartments.edit.field_pass'] = 'Pass';
$lang['AdminDepartments.edit.field_port'] = 'Port';
$lang['AdminDepartments.edit.field_close_ticket_interval'] = 'Automatically Close Tickets';
$lang['AdminDepartments.edit.field_delete_ticket_interval'] = 'Automatically Delete Tickets';
$lang['AdminDepartments.edit.field_reminder_ticket_interval'] = 'Automatically Send Ticket Reminders';
$lang['AdminDepartments.edit.field_reminder_ticket_status'] = 'Send Reminders to Tickets with Status';
$lang['AdminDepartments.edit.field_reminder_ticket_priority'] = 'Send Reminders to Tickets with Priority';
$lang['AdminDepartments.edit.field_response_id'] = 'Auto-Close Ticket Response';
$lang['AdminDepartments.edit.field_status'] = 'Status';
$lang['AdminDepartments.edit.field_addsubmit'] = 'Edit Department';
$lang['AdminDepartments.edit.field_add_field'] = 'Add Field';

$lang['AdminDepartments.edit.confirm_field_remove'] = 'Are you sure you want to delete this custom field? All data associated with this field will be deleted. If you want to hide this field and preserve the data, change it\'s Visibility to Staff Only.';
$lang['AdminDepartments.edit.no_results'] = 'There are no custom fields in this department.';

$lang['AdminDepartments.edit.heading_label'] = 'Label';
$lang['AdminDepartments.edit.heading_description'] = 'Description';
$lang['AdminDepartments.edit.heading_visibility'] = 'Visibility';
$lang['AdminDepartments.edit.heading_type'] = 'Type';
$lang['AdminDepartments.edit.heading_min'] = 'Min';
$lang['AdminDepartments.edit.heading_max'] = 'Max';
$lang['AdminDepartments.edit.heading_step'] = 'Step';
$lang['AdminDepartments.edit.heading_client_add'] = 'Client can Add';
$lang['AdminDepartments.edit.heading_encrypted'] = 'Encrypted';
$lang['AdminDepartments.edit.heading_auto_delete'] = 'Auto-Delete';
$lang['AdminDepartments.edit.heading_options'] = 'Options';
$lang['AdminDepartments.edit.heading_name'] = 'Name';
$lang['AdminDepartments.edit.heading_value'] = 'Value';
$lang['AdminDepartments.edit.heading_default'] = 'Default';
$lang['AdminDepartments.edit.text_delete'] = 'Delete';
$lang['AdminDepartments.edit.text_add'] = 'Add';
