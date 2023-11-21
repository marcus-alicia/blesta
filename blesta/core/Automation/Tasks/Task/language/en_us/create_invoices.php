<?php
$lang['Automation.task.create_invoices.attempt'] = 'Attempting to renew services and create invoices.';
$lang['Automation.task.create_invoices.completed'] = 'The create invoices task has completed.';
$lang['Automation.task.create_invoices.recurring_invoice_failed'] = 'Unable to create a new invoice from recurring invoice #%1$s for client #%2$s.'; // %1$s is the recurring invoice number, %2$s is the client ID
$lang['Automation.task.create_invoices.recurring_invoice_success'] = 'Successfully created a new invoice from recurring invoice #%1$s for client #%2$s.'; // %1$s is the recurring invoice number, %2$s is the client ID

$lang['Automation.task.create_invoices.service_invoice_success'] = 'Successfully created invoice #%1$s for client #%2$s containing services %3$s.'; // %1$s is the invoice ID, %2$s is the client ID, %3$s is a comma-separated list of service IDs
$lang['Automation.task.create_invoices.service_invoice_error'] = 'Invoice failed to generate for client #%2$s containing services %3$s because: %1$s'; // %1$s is a dump of all errors, %2$s is the client ID, %3$s is a comma-separated list of service IDs
