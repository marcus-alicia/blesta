<?php
/**
 * Language definitions for the Client Managers controller/views
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Success messages
$lang['ClientManagers.!success.manager_invited'] = 'An invitation was successfully sent to the matching user on record.';
$lang['ClientManagers.!success.manager_revoked'] = 'The manager has been revoked successfully!'; // %1$s is the contact's first name, %2$s is the contact's last name
$lang['ClientManagers.!success.manager_updated'] = 'The manager was updated successfully.';
$lang['ClientManagers.!success.account_gave_up'] = 'Your access to the %1$s account has been revoked successfully!'; // %1$s is the email address of the account
$lang['ClientManagers.!success.invitation_accepted'] = 'You have accepted the invitation successfully. ';
$lang['ClientManagers.!success.invitation_declined'] = 'You have declined the invitation successfully.';


// Navigation
$lang['ClientManagers.navigation.nav_managers'] = 'Invited Managers';
$lang['ClientManagers.navigation.nav_managed_accounts'] = 'Managed Accounts';
$lang['ClientManagers.navigation.nav_return'] = 'Return to Dashboard';


// Index
$lang['ClientManagers.index.boxtitle_managers'] = 'Invited Managers';
$lang['ClientManagers.index.add_manager'] = 'Add Manager';
$lang['ClientManagers.index.heading_client_id'] = 'Client #';
$lang['ClientManagers.index.heading_email'] = 'Email';
$lang['ClientManagers.index.heading_name'] = 'Full Name';
$lang['ClientManagers.index.heading_company'] = 'Company';
$lang['ClientManagers.index.heading_status'] = 'Status';
$lang['ClientManagers.index.heading_options'] = 'Options';

$lang['ClientManagers.index.text_status_accepted'] = 'Accepted';
$lang['ClientManagers.index.text_status_pending'] = 'Pending';

$lang['ClientManagers.index.option_edit'] = 'Edit';
$lang['ClientManagers.index.option_revoke'] = 'Revoke';

$lang['ClientManagers.index.confirm_revoke'] = 'Are you sure you want to revoke access to this account?';

$lang['ClientManagers.index.no_results'] = 'You have not invited any users to manage this account.';


// Add manager
$lang['ClientManagers.add.boxtitle_add_manager'] = 'Add Manager';
$lang['ClientManagers.add.field_addsubmit'] = 'Add Manager';

$lang['ClientManagers.add.heading_invite_manager'] = 'Invite Manager';
$lang['ClientManagers.add.field_email'] = 'Email Address';
$lang['ClientManagers.tooltip.field_email'] = 'The e-mail address associated to an account registered in %1$s.'; // %1$s is the company name

$lang['ClientManagers.add.heading_permissions'] = 'Permissions';
$lang['ClientManagers.tooltip.client_invoices'] = 'Display Invoices on the dashboard, as well as any payment reminder alerts.';
$lang['ClientManagers.tooltip.client_services'] = 'Display Services on the dashboard, and allow them to be managed.';
$lang['ClientManagers.tooltip.client_transactions'] = 'Display Transactions on the dashboard.';
$lang['ClientManagers.tooltip.client_contacts'] = 'Allow Contacts to be managed.';
$lang['ClientManagers.tooltip.client_accounts'] = 'Allow Payment Accounts to be managed.';
$lang['ClientManagers.tooltip.client_emails'] = 'Display a list of email history.';
$lang['ClientManagers.tooltip._managed'] = 'Allow the accounts that you have been invited to, to be managed.';
$lang['ClientManagers.tooltip._invoice_delivery'] = 'Allow the invoice delivery method to be viewed and changed.';
$lang['ClientManagers.tooltip._credits'] = 'Allow the account credits to be viewed.';


// Edit manager
$lang['ClientManagers.edit.boxtitle_edit_manager'] = 'Edit Manager';
$lang['ClientManagers.edit.field_addsubmit'] = 'Edit Manager';

$lang['ClientManagers.edit.heading_permissions'] = 'Permissions';


// Managing accounts
$lang['ClientManagers.accounts.boxtitle_managed_accounts'] = 'Managed Accounts';
$lang['ClientManagers.accounts.heading_client_id'] = 'Client #';
$lang['ClientManagers.accounts.heading_email'] = 'Email';
$lang['ClientManagers.accounts.heading_name'] = 'Full Name';
$lang['ClientManagers.accounts.heading_company'] = 'Company';
$lang['ClientManagers.accounts.heading_options'] = 'Options';

$lang['ClientManagers.accounts.option_manage'] = 'Manage';
$lang['ClientManagers.accounts.option_give_up_access'] = 'Give Up Access';

$lang['ClientManagers.accounts.confirm_give_up'] = 'Are you sure you want to give up your access to this account?';

$lang['ClientManagers.accounts.no_results'] = 'You are not currently managing any guests accounts.';

// Invitation
$lang['ClientManagers.invite.boxtitle_management_invitation'] = 'Management Invitation';
$lang['ClientManagers.invite.text_invitation'] = '%1$s %2$s has invited you to manage an account. If you accept this invitation you will be able to access and manage the account, if you have received this invitation by mistake you can safely decline it.'; // %1$s is the contact's first name, %2$s is the contact's last name

$lang['ClientManagers.invite.field_accept'] = 'Accept';
$lang['ClientManagers.invite.field_decline'] = 'Decline';
