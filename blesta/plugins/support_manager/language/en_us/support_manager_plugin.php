<?php
// Plugin name
$lang['SupportManagerPlugin.name'] = 'Support Manager';
$lang['SupportManagerPlugin.description'] = 'Allows users and staff to open and manage support tickets.';

// Cron tasks
$lang['SupportManagerPlugin.cron.poll_tickets_name'] = 'Download Tickets';
$lang['SupportManagerPlugin.cron.poll_tickets_desc'] = 'Connects to the POP3/IMAP server to download emails and convert them into tickets.';
$lang['SupportManagerPlugin.cron.close_tickets_name'] = 'Close Tickets';
$lang['SupportManagerPlugin.cron.close_tickets_desc'] = 'Automatically closes open tickets as configured for departments in the Support Manager.';
$lang['SupportManagerPlugin.cron.delete_tickets_name'] = 'Delete Trash Tickets';
$lang['SupportManagerPlugin.cron.delete_tickets_desc'] = 'Automatically delete trash tickets as configured for departments in the Support Manager.';
$lang['SupportManagerPlugin.cron.send_reminders_name'] = 'Send Reminders';
$lang['SupportManagerPlugin.cron.send_reminders_desc'] = 'Automatically send out reminders for tickets of a particular status after a certain period of time.';

// Plugin actions
$lang['SupportManagerPlugin.nav_primary_client.main'] = 'Support';
$lang['SupportManagerPlugin.nav_primary_client.tickets'] = 'Tickets';
$lang['SupportManagerPlugin.nav_primary_client.knowledgebase'] = 'Knowledge Base';
$lang['SupportManagerPlugin.widget_client_home.main'] = 'Tickets';
$lang['SupportManagerPlugin.nav_primary_staff.main'] = 'Support';
$lang['SupportManagerPlugin.nav_primary_staff.tickets']  = 'Tickets';
$lang['SupportManagerPlugin.nav_primary_staff.departments']  = 'Departments';
$lang['SupportManagerPlugin.nav_primary_staff.responses']  = 'Responses';
$lang['SupportManagerPlugin.nav_primary_staff.staff'] = 'Staff';
$lang['SupportManagerPlugin.nav_primary_staff.knowledgebase'] = 'Knowledge Base';
$lang['SupportManagerPlugin.nav_primary_staff.reminders'] = 'Reminders';
$lang['SupportManagerPlugin.widget_staff_client.tickets'] = 'Tickets';
$lang['SupportManagerPlugin.action_staff_client.add'] = 'Open Ticket';

// Plugin cards
$lang['SupportManagerPlugin.card_client.tickets'] = 'Tickets';

// Plugin events
$lang['SupportManagerPlugin.event_getsearchoptions.tickets'] = 'Ticket Search';

// Permissions
$lang['SupportManagerPlugin.permission.admin_main'] = 'Support';
$lang['SupportManagerPlugin.permission.admin_tickets'] = 'Tickets';
$lang['SupportManagerPlugin.permission.admin_tickets_delete'] = 'Permanently Delete Tickets';
$lang['SupportManagerPlugin.permission.admin_tickets_client'] = 'Client Profile Widget';
$lang['SupportManagerPlugin.permission.admin_departments'] = 'Departments';
$lang['SupportManagerPlugin.permission.admin_responses'] = 'Responses';
$lang['SupportManagerPlugin.permission.admin_staff'] = 'Staff';
$lang['SupportManagerPlugin.permission.admin_knowledgebase'] = 'Knowledge Base';
$lang['SupportManagerPlugin.permission.admin_reminders'] = 'Reminders';
