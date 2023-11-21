<?php
/**
 * Language definitions for the Admin Company Emails settings controller/views
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Success messages
$lang['AdminCompanyEmails.!success.edittemplate_updated'] = 'The email template settings were successfully updated!';
$lang['AdminCompanyEmails.!success.editsignature_updated'] = 'The email signature has been successfully updated!';
$lang['AdminCompanyEmails.!success.addsignature_created'] = 'The email signature has been successfully created!';
$lang['AdminCompanyEmails.!success.deletesignature_deleted'] = 'The email signature has been successfully deleted!';
$lang['AdminCompanyEmails.!success.mail_updated'] = 'The Mail settings have been successfully updated!';
$lang['AdminCompanyEmails.!success.smtp_test'] = 'SMTP connection was successful!';
$lang['AdminCompanyEmails.!success.sendmail_test'] = 'Sendmail connection was successful!';


// Tooltips
$lang['AdminCompanyEmails.!tooltip.from_name'] = 'This is the friendly name of the email address displayed by the recipient\'s mail client.';
$lang['AdminCompanyEmails.!tooltip.from'] = 'This is the email address that this message should appear from.';
$lang['AdminCompanyEmails.!tooltip.subject'] = 'This is the subject of the message. Email subjects may use tags.';
$lang['AdminCompanyEmails.!tooltip.email_signature_id'] = 'The message will be appended with the selected signature.';
$lang['AdminCompanyEmails.!tooltip.include_attachments'] = 'If any file attachments are sent with this email template, unchecking this option will no longer attach them to emails.';
$lang['AdminCompanyEmails.!tooltip.status'] = 'No emails will be sent using this template unless this option is enabled.';

$lang['AdminCompanyEmails.!tooltip.html_email'] = 'Check to allow email with HTML content to be delivered. A plain-text version of emails will always be sent.';
$lang['AdminCompanyEmails.!tooltip.mail_delivery'] = 'SMTP uses a configured SMTP server for email delivery while Sendmail will attempt to send email through the Sendmail binary on the system. SMTP is generally faster, more secure, and more reliable, so that is the recommended option.';
$lang['AdminCompanyEmails.!tooltip.sendmail_path'] = 'The sendmail command to run including path and flags.';
$lang['AdminCompanyEmails.!tooltip.sendmail_from'] = 'This is only for testing the send mail command and will be used to send a test email to a random disposable email address.';
$lang['AdminCompanyEmails.!tooltip.smtp_host'] = 'Set the host name used to communicate with the SMTP server.';
$lang['AdminCompanyEmails.!tooltip.smtp_port'] = 'Set the port used to communicate with the SMTP server.';
$lang['AdminCompanyEmails.!tooltip.smtp_user'] = 'Set the SMTP user account to send mail through.';
$lang['AdminCompanyEmails.!tooltip.smtp_password'] = 'Set the password for the SMTP user account.';
$lang['AdminCompanyEmails.!tooltip.smtp_from'] = 'The from address to use when testing the settings.';
$lang['AdminCompanyEmails.!tooltip.smtp_to'] = 'This is only for testing the send mail command and will be used to send a test email to the specified email address (or a random disposable one).';
$lang['AdminCompanyEmails.!tooltip.submitmail'] = 'Update Settings';


// Common language
$lang['AdminCompanyEmails.!cancel.field.cancel'] = 'Cancel';


// Email templates
$lang['AdminCompanyEmails.templates.page_title'] = 'Settings > Company > Emails > Email Templates';
$lang['AdminCompanyEmails.templates.boxtitle_templates'] = 'Email Templates';

$lang['AdminCompanyEmails.templates.heading_client'] = 'Client Emails';
$lang['AdminCompanyEmails.templates.heading_staff'] = 'Staff Emails';
$lang['AdminCompanyEmails.templates.heading_shared'] = 'Shared Emails';
$lang['AdminCompanyEmails.templates.heading_plugins'] = 'Plugin Emails';

$lang['AdminCompanyEmails.templates.text_name'] = 'Name';
$lang['AdminCompanyEmails.templates.text_plugin'] = 'Plugin';
$lang['AdminCompanyEmails.templates.text_description'] = 'Description';
$lang['AdminCompanyEmails.templates.text_options'] = 'Options';

$lang['AdminCompanyEmails.templates.option_edit'] = 'Edit';

$lang['AdminCompanyEmails.templates.no_results'] = 'There are no templates of this type.';

$lang['AdminCompanyEmails.templates.field_templatesubmit'] = 'Update';

$lang['AdminCompanyEmails.templates.payment_cc_approved_name'] = 'Payment Approved (Credit Card)';
$lang['AdminCompanyEmails.templates.payment_cc_approved_desc'] = 'Notice sent after a successful credit card payment is approved.';
$lang['AdminCompanyEmails.templates.payment_cc_declined_name'] = 'Payment Declined (Credit Card)';
$lang['AdminCompanyEmails.templates.payment_cc_declined_desc'] = 'Notice sent after a credit card payment attempt is declined.';
$lang['AdminCompanyEmails.templates.payment_cc_error_name'] = 'Payment Error (Credit Card)';
$lang['AdminCompanyEmails.templates.payment_cc_error_desc'] = 'Notice sent after a credit card payment attempt results in error.';
$lang['AdminCompanyEmails.templates.payment_ach_approved_name'] = 'Payment Approved (ACH)';
$lang['AdminCompanyEmails.templates.payment_ach_approved_desc'] = 'Notice sent after a successful ACH payment is approved.';
$lang['AdminCompanyEmails.templates.payment_ach_declined_name'] = 'Payment Declined (ACH)';
$lang['AdminCompanyEmails.templates.payment_ach_declined_desc'] = 'Notice sent after a ACH payment attempt is declined.';
$lang['AdminCompanyEmails.templates.payment_ach_error_name'] = 'Payment Error (ACH)';
$lang['AdminCompanyEmails.templates.payment_ach_error_desc'] = 'Notice sent after an ACH payment attempt results in error.';
$lang['AdminCompanyEmails.templates.payment_manual_approved_name'] = 'Payment Received (Manual Entry)';
$lang['AdminCompanyEmails.templates.payment_manual_approved_desc'] = 'Notice sent after a payment is manually recorded.';
$lang['AdminCompanyEmails.templates.payment_nonmerchant_approved_name'] = 'Payment Received (Non-Merchant)';
$lang['AdminCompanyEmails.templates.payment_nonmerchant_approved_desc'] = 'Notice sent after a payment is received from a non-merchant gateway.';
$lang['AdminCompanyEmails.templates.credit_card_expiration_name'] = 'Credit Card Expiration';
$lang['AdminCompanyEmails.templates.credit_card_expiration_desc'] = 'Notice sent when an active credit card is about to expire.';
$lang['AdminCompanyEmails.templates.invoice_delivery_unpaid_name'] = 'Invoice Delivery (Unpaid)';
$lang['AdminCompanyEmails.templates.invoice_delivery_unpaid_desc'] = 'Notice containing a PDF copy of an unpaid invoice.';
$lang['AdminCompanyEmails.templates.invoice_delivery_paid_name'] = 'Invoice Delivery (Paid)';
$lang['AdminCompanyEmails.templates.invoice_delivery_paid_desc'] = 'Notice containing a PDF copy of a paid invoice.';
$lang['AdminCompanyEmails.templates.invoice_notice_first_name'] = 'Invoice Notice (1st)';
$lang['AdminCompanyEmails.templates.invoice_notice_first_desc'] = 'First invoice notice, either a reminder to pay or late notice.';
$lang['AdminCompanyEmails.templates.invoice_notice_second_name'] = 'Invoice Notice (2nd)';
$lang['AdminCompanyEmails.templates.invoice_notice_second_desc'] = 'Second invoice notice, either a reminder to pay or late notice.';
$lang['AdminCompanyEmails.templates.invoice_notice_third_name'] = 'Invoice Notice (3rd)';
$lang['AdminCompanyEmails.templates.invoice_notice_third_desc'] = 'Third invoice notice, either a reminder to pay or late notice.';
$lang['AdminCompanyEmails.templates.reset_password_name'] = 'Password Reset';
$lang['AdminCompanyEmails.templates.reset_password_desc'] = 'Password reset email containing a link to change the account password.';
$lang['AdminCompanyEmails.templates.forgot_username_name'] = 'Forgot Username';
$lang['AdminCompanyEmails.templates.forgot_username_desc'] = 'Username recovery email containing the username on record for the account.';
$lang['AdminCompanyEmails.templates.service_cancellation_name'] = 'Service Cancellation';
$lang['AdminCompanyEmails.templates.service_cancellation_desc'] = 'Service cancellation notice, sent when a service is canceled.';
$lang['AdminCompanyEmails.templates.service_scheduled_cancellation_name'] = 'Service Scheduled Cancellation';
$lang['AdminCompanyEmails.templates.service_scheduled_cancellation_desc'] = 'Service scheduled cancellation notice, sent when a service is scheduled for cancellation.';
$lang['AdminCompanyEmails.templates.service_suspension_name'] = 'Service Suspension';
$lang['AdminCompanyEmails.templates.service_suspension_desc'] = 'Service suspended notice, sent when a service is automatically suspended.';
$lang['AdminCompanyEmails.templates.service_unsuspension_name'] = 'Service Unsuspension';
$lang['AdminCompanyEmails.templates.service_unsuspension_desc'] = 'Service unsuspended notice, sent when a service is automatically unsuspended.';
$lang['AdminCompanyEmails.templates.account_management_invite_name'] = 'Account Management Invitation';
$lang['AdminCompanyEmails.templates.account_management_invite_desc'] = 'Notice sent after a user has invited you to manage their account.';
$lang['AdminCompanyEmails.templates.account_welcome_name'] = 'Account Registration';
$lang['AdminCompanyEmails.templates.account_welcome_desc'] = 'Welcome notice sent for new account registrations.';
$lang['AdminCompanyEmails.templates.report_ar_name'] = 'Aging Invoices Report';
$lang['AdminCompanyEmails.templates.report_ar_desc'] = 'Thirty, Sixety, Ninety day Aging Invoice Reports, delivered once per month.';
$lang['AdminCompanyEmails.templates.report_tax_liability_name'] = 'Tax Liability Report';
$lang['AdminCompanyEmails.templates.report_tax_liability_desc'] = 'A monthly Tax Liability Report, generated for the previous month.';
$lang['AdminCompanyEmails.templates.report_invoice_creation_name'] = 'Invoice Creation Report';
$lang['AdminCompanyEmails.templates.report_invoice_creation_desc'] = 'A daily report of invoices generated for the previous day.';
$lang['AdminCompanyEmails.templates.service_suspension_error_name'] = 'Suspension Error';
$lang['AdminCompanyEmails.templates.service_suspension_error_desc'] = 'Notice sent after a failed attempt to suspend a service.';
$lang['AdminCompanyEmails.templates.service_unsuspension_error_name'] = 'Unsuspension Error';
$lang['AdminCompanyEmails.templates.service_unsuspension_error_desc'] = 'Notice sent after a failed attempt to unsuspend a service.';
$lang['AdminCompanyEmails.templates.service_cancel_error_name'] = 'Cancellation Error';
$lang['AdminCompanyEmails.templates.service_cancel_error_desc'] = 'Notice sent after a failed attempt to cancel a service.';
$lang['AdminCompanyEmails.templates.service_creation_error_name'] = 'Creation Error';
$lang['AdminCompanyEmails.templates.service_creation_error_desc'] = 'Notice sent after a failed attempt to provision a service.';
$lang['AdminCompanyEmails.templates.service_renewal_error_name'] = 'Renewal Error';
$lang['AdminCompanyEmails.templates.service_renewal_error_desc'] = 'Notice sent after a failed attempt to renew a service.';
$lang['AdminCompanyEmails.templates.auto_debit_pending_name'] = 'Auto-Debit Pending';
$lang['AdminCompanyEmails.templates.auto_debit_pending_desc'] = 'Notice sent that indicates an automatic payment will be attempted soon.';
$lang['AdminCompanyEmails.templates.staff_reset_password_name'] = 'Password Reset';
$lang['AdminCompanyEmails.templates.staff_reset_password_desc'] = 'Password reset email containing a link to change the account password.';
$lang['AdminCompanyEmails.templates.service_creation_name'] = 'Service Creation';
$lang['AdminCompanyEmails.templates.service_creation_desc'] = 'Service creation notice, sent when a service has been created.';
$lang['AdminCompanyEmails.templates.verify_email_name'] = 'Email Verification';
$lang['AdminCompanyEmails.templates.verify_email_desc'] = 'Email verification link, sent when new login is created or a client changes their email address.';
$lang['AdminCompanyEmails.templates.quotation_delivery_name'] = 'Quote Delivery';
$lang['AdminCompanyEmails.templates.quotation_delivery_desc'] = 'Notice containing a PDF copy of a quote.';
$lang['AdminCompanyEmails.templates.staff_quotation_approved_name'] = 'Quote Approval';
$lang['AdminCompanyEmails.templates.staff_quotation_approved_desc'] = 'Notice sent after a quote has been approved by the client.';


// Edit email template
$lang['AdminCompanyEmails.edittemplate.page_title'] = 'Settings > Company > Emails > Edit Email Template';
$lang['AdminCompanyEmails.edittemplate.boxtitle_edittemplate'] = 'Edit Email Template %1$s'; // %1$s is the email template group name
$lang['AdminCompanyEmails.edittemplate.text_none'] = 'None';

$lang['AdminCompanyEmails.edittemplate.field.status'] = 'Enabled';
$lang['AdminCompanyEmails.edittemplate.field.from_name'] = 'From Name';
$lang['AdminCompanyEmails.edittemplate.field.from'] = 'From Email';
$lang['AdminCompanyEmails.edittemplate.field.subject'] = 'Subject';
$lang['AdminCompanyEmails.edittemplate.field.tags'] = 'Available Tags';
$lang['AdminCompanyEmails.edittemplate.field.text'] = 'Text';
$lang['AdminCompanyEmails.edittemplate.field.html'] = 'HTML';
$lang['AdminCompanyEmails.edittemplate.field.email_signature_id'] = 'Signature';
$lang['AdminCompanyEmails.edittemplate.field.include_attachments'] = 'Include Any Attachments';
$lang['AdminCompanyEmails.edittemplate.field.edittemplatesubmit'] = 'Update Template';


// Email signatures
$lang['AdminCompanyEmails.signatures.page_title'] = 'Settings > Company > Emails > Signatures';
$lang['AdminCompanyEmails.signatures.boxtitle_signatures'] = 'Signatures';

$lang['AdminCompanyEmails.signatures.categorylink_newsignature'] = 'New Signature';
$lang['AdminCompanyEmails.signatures.no_results'] = 'There are no email signatures.';

$lang['AdminCompanyEmails.signatures.text_name'] = 'Name';
$lang['AdminCompanyEmails.signatures.text_description'] = 'Description';
$lang['AdminCompanyEmails.signatures.text_options'] = 'Options';

$lang['AdminCompanyEmails.signatures.option_edit'] = 'Edit';
$lang['AdminCompanyEmails.signatures.option_delete'] = 'Delete';

$lang['AdminCompanyEmails.signatures.confirm_delete'] = 'Are you sure you want to delete this email signature?';


// Add email signature
$lang['AdminCompanyEmails.addsignature.page_title'] = 'Settings > Company > Emails > Add Signature';
$lang['AdminCompanyEmails.addsignature.boxtitle_addsignature'] = 'Add Signature';

$lang['AdminCompanyEmails.addsignature.field_name'] = 'Name';
$lang['AdminCompanyEmails.addsignature.field_text'] = 'Text';
$lang['AdminCompanyEmails.addsignature.field_html'] = 'HTML';
$lang['AdminCompanyEmails.addsignature.field_addsignaturesubmit'] = 'Create Signature';

$lang['AdminCompanyEmails.addsignature.text_signatures'] = 'Signatures are used for email templates, making it easier to modify email signatures in bulk';


// Edit email signature
$lang['AdminCompanyEmails.editsignature.page_title'] = 'Settings > Company > Emails > Edit Signature';
$lang['AdminCompanyEmails.editsignature.boxtitle_editsignature'] = 'Edit Signature';

$lang['AdminCompanyEmails.editsignature.field_name'] = 'Name';
$lang['AdminCompanyEmails.editsignature.field_text'] = 'Text';
$lang['AdminCompanyEmails.editsignature.field_html'] = 'HTML';
$lang['AdminCompanyEmails.editsignature.field_editsignaturesubmit'] = 'Update Signature';


// Mail
$lang['AdminCompanyEmails.mail.page_title'] = 'Settings > Company > Emails > Mail Settings';
$lang['AdminCompanyEmails.mail.boxtitle_mail'] = 'Mail Settings';

$lang['AdminCompanyEmails.mail.text_section'] = 'This section controls how email is delivered from Blesta. Sendmail is the simplest delivery method, but SMTP is generally faster and more reliable.';

$lang['AdminCompanyEmails.mail.field.sendmail_path'] = 'Sendmail Path';
$lang['AdminCompanyEmails.mail.field.sendmail_from'] = 'Sendmail Test From Address';
$lang['AdminCompanyEmails.mail.field.html_email'] = 'Enable HTML';
$lang['AdminCompanyEmails.mail.field.mail_delivery'] = 'Delivery Method';
$lang['AdminCompanyEmails.mail.field.test'] = 'Test These Settings';
$lang['AdminCompanyEmails.mail.field.smtp_host'] = 'SMTP Host';
$lang['AdminCompanyEmails.mail.field.smtp_port'] = 'SMTP Port';
$lang['AdminCompanyEmails.mail.field.smtp_user'] = 'SMTP User';
$lang['AdminCompanyEmails.mail.field.smtp_password'] = 'SMTP Password';
$lang['AdminCompanyEmails.mail.field.smtp_from'] = 'Test From Address';
$lang['AdminCompanyEmails.mail.field.smtp_to'] = 'Test To Address';
$lang['AdminCompanyEmails.mail.field.submitmail'] = 'Update Settings';

$lang['AdminCompanyEmails.mail.text_optional'] = 'Optional';


// Text
$lang['AdminCompanyEmails.getRequiredMethods.sendmail'] = 'Sendmail';
$lang['AdminCompanyEmails.getRequiredMethods.smtp'] = 'SMTP';
$lang['AdminCompanyEmails.getsmtpsecurityoptions.none'] = 'None';
$lang['AdminCompanyEmails.getsmtpsecurityoptions.ssl'] = 'SSL';
$lang['AdminCompanyEmails.getsmtpsecurityoptions.tls'] = 'TLS';
$lang['AdminCompanyEmails.gettemplateactions.update_from_email'] = 'Update "From Email"';
$lang['AdminCompanyEmails.gettemplateactions.update_from_name'] = 'Update "From Name"';
