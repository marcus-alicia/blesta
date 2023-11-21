<?php
/**
 * Language definitions for the Staff Groups model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

$lang['StaffGroups.!error.staff_group_id.exists'] = 'Invalid staff group ID.';
$lang['StaffGroups.!error.company_id.exists'] = 'Invalid company ID.';
$lang['StaffGroups.!error.name.empty'] = 'Please enter a name.';
$lang['StaffGroups.!error.name.length'] = 'Name length may not exceed 64 characters.';
$lang['StaffGroups.!error.no_company_id.exists'] = 'Can not log in, not assigned to any company.';
$lang['StaffGroups.!error.session_lock.valid'] = 'Invalid value specificed for session lock.';
$lang['StaffGroups.!error.action.exists'] = 'The email group action %1$s does not exist.'; // %1$s is the name of the email group action

$lang['StaffGroups.!error.num_staff.zero'] = 'The staff group, %1$s, could not be deleted because it currently has staff members assigned to it.'; // %1$s is the staff group name

$lang['StaffGroups.permission_group.home'] = 'Home';
$lang['StaffGroups.permission_group.clients'] = 'Clients';
$lang['StaffGroups.permission_group.billing'] = 'Billing';
$lang['StaffGroups.permission_group.packages'] = 'Packages';
$lang['StaffGroups.permission_group.tools'] = 'Tools';
$lang['StaffGroups.permission_group.search'] = 'Search';
$lang['StaffGroups.permission_group.settings'] = 'Settings';

$lang['StaffGroups.permissions.admin_main_calendar'] = 'Calendar';

$lang['StaffGroups.permissions.admin_clients_view'] = 'View Client';
$lang['StaffGroups.permissions.admin_clients_add'] = 'Add Client';
$lang['StaffGroups.permissions.admin_clients_edit'] = 'Edit Client';
$lang['StaffGroups.permissions.admin_clients_invoices'] = 'List Invoices';
$lang['StaffGroups.permissions.admin_clients_viewinvoice'] = 'View Invoice';
$lang['StaffGroups.permissions.admin_clients_createinvoice'] = 'Create Invoice';
$lang['StaffGroups.permissions.admin_clients_editinvoice'] = 'Edit Invoice';
$lang['StaffGroups.permissions.admin_clients_editrecurinvoice'] = 'Edit Recurring Invoice';
$lang['StaffGroups.permissions.admin_clients_deleteinvoice'] = 'Delete Invoice';
$lang['StaffGroups.permissions.admin_clients_transactions'] = 'List Transactions';
$lang['StaffGroups.permissions.admin_clients_edittransaction'] = 'Edit Transaction';
$lang['StaffGroups.permissions.admin_clients_services'] = 'List Services';
$lang['StaffGroups.permissions.admin_clients_addservice'] = 'Add Service';
$lang['StaffGroups.permissions.admin_clients_editservice'] = 'Edit Service';
$lang['StaffGroups.permissions.admin_clients_deleteservice'] = 'Delete Service';
$lang['StaffGroups.permissions.admin_clients_addcontact'] = 'Add Contact';
$lang['StaffGroups.permissions.admin_clients_editcontact'] = 'Edit Contact';
$lang['StaffGroups.permissions.admin_clients_deletecontact'] = 'Delete Contact';
$lang['StaffGroups.permissions.admin_clients_quickupdate'] = 'Quick Update';
$lang['StaffGroups.permissions.admin_clients_recordpayment'] = 'Record Payment';
$lang['StaffGroups.permissions.admin_clients_makepayment'] = 'Make Payment';
$lang['StaffGroups.permissions.admin_clients_accounts'] = 'View Payment Accounts';
$lang['StaffGroups.permissions.admin_clients_addccaccount'] = 'Add Credit Card Payment Account';
$lang['StaffGroups.permissions.admin_clients_addachaccount'] = 'Add ACH Payment Account';
$lang['StaffGroups.permissions.admin_clients_editccaccount'] = 'Edit Credit Card Payment Account';
$lang['StaffGroups.permissions.admin_clients_editachaccount'] = 'Edit ACH Payment Account';
$lang['StaffGroups.permissions.admin_clients_deleteccaccount'] = 'Delete Credit Card Payment Account';
$lang['StaffGroups.permissions.admin_clients_deleteachaccount'] = 'Delete ACH Payment Account';
$lang['StaffGroups.permissions.admin_clients_notes'] = 'View Notes';
$lang['StaffGroups.permissions.admin_clients_addnote'] = 'Add Note';
$lang['StaffGroups.permissions.admin_clients_editnote'] = 'Edit Note';
$lang['StaffGroups.permissions.admin_clients_deletenote'] = 'Delete Note';
$lang['StaffGroups.permissions.admin_clients_packages'] = 'Set Packages';
$lang['StaffGroups.permissions.admin_clients_email'] = 'Email Client';
$lang['StaffGroups.permissions.admin_clients_emails'] = 'View Maillog';
$lang['StaffGroups.permissions.admin_clients_merge'] = 'Merge Client';
$lang['StaffGroups.permissions.admin_clients_delete'] = 'Delete Client';
$lang['StaffGroups.permissions.admin_clients_loginasclient'] = 'Login as Client';
$lang['StaffGroups.permissions.admin_clients_passwordreset'] = 'Send Password Reset';

$lang['StaffGroups.permissions.admin_clients_service'] = 'View Service Totals';

$lang['StaffGroups.permissions.admin_billing_invoices'] = 'List Invoices';
$lang['StaffGroups.permissions.admin_billing_services'] = 'List Services';
$lang['StaffGroups.permissions.admin_billing_transactions'] = 'List Transactions';
$lang['StaffGroups.permissions.admin_billing_printqueue'] = 'Print Queue';
$lang['StaffGroups.permissions.admin_billing_batch'] = 'Batch';

$lang['StaffGroups.permissions.admin_tools_convert_currency'] = 'Convert Currency';
$lang['StaffGroups.permissions.admin_tools_logs'] = 'Logs';
$lang['StaffGroups.permissions.admin_tools_utilities'] = 'Utilities';
$lang['StaffGroups.permissions.admin_tools_renewals'] = 'Renewal Queue';

$lang['StaffGroups.permissions.admin_packages_add'] = 'Add Package';
$lang['StaffGroups.permissions.admin_packages_edit'] = 'Edit Package';
$lang['StaffGroups.permissions.admin_packages_delete'] = 'Delete Package';
$lang['StaffGroups.permissions.admin_packages_groups'] = 'View Package Groups';

$lang['StaffGroups.permissions.admin_settings_company'] = 'Company settings';
$lang['StaffGroups.permissions.admin_company_general_localization'] = 'Localization';
$lang['StaffGroups.permissions.admin_company_general_international'] = 'Internationalization';
$lang['StaffGroups.permissions.admin_company_general_themes'] = 'Themes';
$lang['StaffGroups.permissions.admin_company_general_addtheme'] = 'Add themes';
$lang['StaffGroups.permissions.admin_company_general_edittheme'] = 'Edit Theme';
$lang['StaffGroups.permissions.admin_company_general_deletetheme'] = 'Delete Theme';
$lang['StaffGroups.permissions.admin_company_general_encryption'] = 'Encryption';
$lang['StaffGroups.permissions.admin_company_general_marketing'] = 'Marketing';
$lang['StaffGroups.permissions.admin_company_general_smartsearch'] = 'Smart Search';
$lang['StaffGroups.permissions.admin_company_general_humanverification'] = 'Human Verification';
$lang['StaffGroups.permissions.admin_company_billing_invoices'] = 'Invoice and Charge Options';
$lang['StaffGroups.permissions.admin_company_billing_customization'] = 'Invoice Customization';
$lang['StaffGroups.permissions.admin_company_billing_deliverymethods'] = 'Invoice Delivery';
$lang['StaffGroups.permissions.admin_company_billing_latefees'] = 'Late Fees';
$lang['StaffGroups.permissions.admin_company_billing_notices'] = 'Notices';
$lang['StaffGroups.permissions.admin_company_billing_coupons'] = 'Coupons';
$lang['StaffGroups.permissions.admin_company_billing_addcoupon'] = 'Add Coupon';
$lang['StaffGroups.permissions.admin_company_billing_editcoupon'] = 'Edit Coupon';
$lang['StaffGroups.permissions.admin_company_billing_deletecoupon'] = 'Delete Coupon';
$lang['StaffGroups.permissions.admin_company_lookandfeel'] = 'Look and Feel';
$lang['StaffGroups.permissions.admin_company_lookandfeel_actions'] = 'Custom Actions';
$lang['StaffGroups.permissions.admin_company_lookandfeel_addaction'] = 'Add Action';
$lang['StaffGroups.permissions.admin_company_lookandfeel_editaction'] = 'Edit Action';
$lang['StaffGroups.permissions.admin_company_lookandfeel_layout'] = 'Layout';
$lang['StaffGroups.permissions.admin_company_lookandfeel_customize'] = 'Customize';
$lang['StaffGroups.permissions.admin_company_lookandfeel_navigation'] = 'Navigation';
$lang['StaffGroups.permissions.admin_company_themes'] = 'Themes';
$lang['StaffGroups.permissions.admin_company_modules'] = 'Modules';
$lang['StaffGroups.permissions.admin_company_modules_manage'] = 'Manage Module';
$lang['StaffGroups.permissions.admin_company_modules_install'] = 'Install Module';
$lang['StaffGroups.permissions.admin_company_modules_uninstall'] = 'Uninstall Module';
$lang['StaffGroups.permissions.admin_company_modules_upgrade'] = 'Upgrade Module';
$lang['StaffGroups.permissions.admin_company_gateways'] = 'Payment Gateways';
$lang['StaffGroups.permissions.admin_company_gateways_manage'] = 'Manage Gateway';
$lang['StaffGroups.permissions.admin_company_gateways_install'] = 'Install Gateway';
$lang['StaffGroups.permissions.admin_company_gateways_uninstall'] = 'Uninstall Gateway';
$lang['StaffGroups.permissions.admin_company_gateways_upgrade'] = 'Upgrade Gateway';
$lang['StaffGroups.permissions.admin_company_taxes'] = 'Taxes';
$lang['StaffGroups.permissions.admin_company_emails_mail'] = 'Mail Settings';
$lang['StaffGroups.permissions.admin_company_emails_mailtest'] = 'Mail Settings Test';
$lang['StaffGroups.permissions.admin_company_emails_templates'] = 'Email Templates';
$lang['StaffGroups.permissions.admin_company_emails_edittemplate'] = 'Edit Email Template';
$lang['StaffGroups.permissions.admin_company_emails_signatures'] = 'Email Signatures';
$lang['StaffGroups.permissions.admin_company_emails_addsignature'] = 'Add Email Signature';
$lang['StaffGroups.permissions.admin_company_emails_editsignature'] = 'Edit Email Signature';
$lang['StaffGroups.permissions.admin_company_emails_deletesignature'] = 'Delete Email Signature';
$lang['StaffGroups.permissions.admin_company_clientoptions_general'] = 'General Client Settings';
$lang['StaffGroups.permissions.admin_company_clientoptions_requiredfields'] = 'Required Client Fields';
$lang['StaffGroups.permissions.admin_company_clientoptions_customfields'] = 'Client Custom Fields';
$lang['StaffGroups.permissions.admin_company_currencies'] = 'Currencies';
$lang['StaffGroups.permissions.admin_company_messengers'] = 'Messengers';
$lang['StaffGroups.permissions.admin_company_messengers_manage'] = 'Manage Messenger';
$lang['StaffGroups.permissions.admin_company_messengers_install'] = 'Install Messengers';
$lang['StaffGroups.permissions.admin_company_messengers_uninstall'] = 'Uninstall Messengers';
$lang['StaffGroups.permissions.admin_company_messengers_upgrade'] = 'Upgrade Messengers';
$lang['StaffGroups.permissions.admin_company_messengers_configuration'] = 'Messenger Configuration';
$lang['StaffGroups.permissions.admin_company_messengers_templates'] = 'Messenger Templates';
$lang['StaffGroups.permissions.admin_company_messengers_edittemplate'] = 'Edit Messenger Templates';
$lang['StaffGroups.permissions.admin_company_plugins'] = 'Plugins';
$lang['StaffGroups.permissions.admin_company_plugins_manage'] = 'Manage Plugin';
$lang['StaffGroups.permissions.admin_company_plugins_settings'] = 'Plugin Settings';
$lang['StaffGroups.permissions.admin_company_plugins_install'] = 'Install Plugin';
$lang['StaffGroups.permissions.admin_company_plugins_uninstall'] = 'Uninstall Plugin';
$lang['StaffGroups.permissions.admin_company_plugins_upgrade'] = 'Upgrade Plugin';
$lang['StaffGroups.permissions.admin_company_groups'] = 'Client Groups';
$lang['StaffGroups.permissions.admin_settings_system'] = 'System settings';
$lang['StaffGroups.permissions.admin_system_general_basic'] = 'Basic Setup';
$lang['StaffGroups.permissions.admin_system_general_geoip'] = 'GeoIP Settings';
$lang['StaffGroups.permissions.admin_system_general_maintenance'] = 'Maintenance';
$lang['StaffGroups.permissions.admin_system_general_license'] = 'License Key';
$lang['StaffGroups.permissions.admin_system_automation'] = 'Automation';
$lang['StaffGroups.permissions.admin_system_companies'] = 'Companies';
$lang['StaffGroups.permissions.admin_system_backup'] = 'Backups';
$lang['StaffGroups.permissions.admin_system_staff_manage'] = 'Manage Staff';
$lang['StaffGroups.permissions.admin_system_staff_add'] = 'Add Staff';
$lang['StaffGroups.permissions.admin_system_staff_edit'] = 'Edit Staff';
$lang['StaffGroups.permissions.admin_system_staff_status'] = 'Staff Status';
$lang['StaffGroups.permissions.admin_system_staff_groups'] = 'Staff Groups';
$lang['StaffGroups.permissions.admin_system_staff_addgroup'] = 'Add Staff Group';
$lang['StaffGroups.permissions.admin_system_staff_editgroup'] = 'Edit Staff Group';
$lang['StaffGroups.permissions.admin_system_staff_deletegroup'] = 'Delete Staff Group';
$lang['StaffGroups.permissions.admin_system_api'] = 'API Access';
$lang['StaffGroups.permissions.admin_system_upgrade'] = 'Upgrade Options';
$lang['StaffGroups.permissions.admin_system_help'] = 'Help';
$lang['StaffGroups.permissions.admin_system_marketplace'] = 'Marketplace';

$lang['StaffGroups.permissions.admin_company_general_contacttypes'] = 'Contact Types';
$lang['StaffGroups.permissions.admin_company_general_addcontacttype'] = 'Add Contact Type';
$lang['StaffGroups.permissions.admin_company_general_editcontacttype'] = 'Edit Contact Type';
$lang['StaffGroups.permissions.admin_company_general_deletecontacttype'] = 'Delete Contact Type';
$lang['StaffGroups.permissions.admin_company_general_installlanguage'] = 'Install Language';
$lang['StaffGroups.permissions.admin_company_general_uninstalllanguage'] = 'Uninstall Language';
$lang['StaffGroups.permissions.admin_company_feeds'] = 'Data Feeds';
$lang['StaffGroups.permissions.admin_company_automation'] = 'Automation';
$lang['StaffGroups.permissions.admin_company_billing_acceptedtypes'] = 'Accepted Payment Types';
$lang['StaffGroups.permissions.admin_system_general_paymenttypes'] = 'Payment Types';
$lang['StaffGroups.permissions.admin_system_general_addtype'] = 'Add Payment Type';
$lang['StaffGroups.permissions.admin_system_general_edittype'] = 'Edit Payment Type';
$lang['StaffGroups.permissions.admin_system_general_deletetype'] = 'Delete Payment Type';
$lang['StaffGroups.permissions.admin_packages_addgroup'] = 'Add Group';
$lang['StaffGroups.permissions.admin_packages_editgroup'] = 'Edit Group';
$lang['StaffGroups.permissions.admin_packages_deletegroup'] = 'Delete Group';
$lang['StaffGroups.permissions.admin_package_options'] = 'Configurable Options';

$lang['StaffGroups.permissions.admin_reports'] = 'Reports';
$lang['StaffGroups.permissions.admin_reports_customize'] = 'Customize Reports';
