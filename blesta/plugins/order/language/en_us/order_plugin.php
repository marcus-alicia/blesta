<?php
// Plugin details
$lang['OrderPlugin.name'] = "Order System";

// Orders widget name
$lang['OrderPlugin.admin_main.name'] = "Orders";

// Subtab names
$lang['OrderPlugin.admin_forms.name'] = "Order Forms";
$lang['OrderPlugin.admin_affiliates.name'] = "Affiliates";
$lang['OrderPlugin.admin_affiliates.payouts'] = "Affiliate Payouts";
$lang['OrderPlugin.admin_affiliates.payment_methods'] = "Affiliate Payment Methods";

// Client links
$lang['OrderPlugin.client.name'] = "Order";
$lang['OrderPlugin.client_affiliates.name'] = "Affiliates";

// Client action
$lang['OrderPlugin.action_staff_client.affiliates'] = "Manage Affiliate";

// Client orders name
$lang['OrderPlugin.client_orders.name'] = "Order History";

// Cron task
$lang['OrderPlugin.cron.accept_paid_orders_name'] = "Accept Paid Pending Orders";
$lang['OrderPlugin.cron.accept_paid_orders_desc'] = "Automatically accepts paid pending orders if the order form allows it.";

$lang['OrderPlugin.cron.affiliate_monthly_report_name'] = "Affiliate Monthly Report";
$lang['OrderPlugin.cron.affiliate_monthly_report_desc'] = "A monthly affiliate email report will be sent on the 1st of the month for the previous month.";

$lang['OrderPlugin.cron.mature_affiliate_referrals_name'] = "Affiliate Orders";
$lang['OrderPlugin.cron.mature_affiliate_referrals_desc'] = "Evaluates whether any pending affiliate referrals have reached maturity, and if so, updates them and payout amounts.";

$lang['OrderPlugin.cron.process_abandoned_orders_name'] = 'Process Abandoned Orders';
$lang['OrderPlugin.cron.process_abandoned_orders_desc'] = 'Send reminder emails, perform cancellations, and deactivate clients for abandoned orders';

// Plugin events
$lang['OrderPlugin.event_getsearchoptions.orders'] = 'Order Search';

// Plugin cards
$lang['OrderPlugin.card_client.orders'] = 'Orders';

$lang['AdminCompanyPlugins.settings.event.domains.delete'] = 'This event is triggered when a TLD is deleted from Domain Manager.';
$lang['AdminCompanyPlugins.settings.event.domains.enable'] = 'This event is triggered when a TLD is enabled from Domain Manager.';
$lang['AdminCompanyPlugins.settings.event.domains.disable'] = 'This event is triggered when a TLD is disabled from Domain Manager.';
$lang['AdminCompanyPlugins.settings.event.domains.updatedomainscompanysettingsafter'] = 'This event is triggered after the Domain Manager company settings are updated.';
$lang['AdminCompanyPlugins.settings.event.domains.updatepricingafter'] = 'This event is triggered after the pricing of a TLD is updated.';

// Permissions
$lang['OrderPlugin.permission.admin_affiliates'] = 'Affiliates';
$lang['OrderPlugin.permission.admin_affiliates_add'] = 'Add Affiliate';
$lang['OrderPlugin.permission.admin_affiliates_update'] = 'Update Affiliate';
$lang['OrderPlugin.permission.admin_affiliates_activate'] = 'Activate Affiliate';
$lang['OrderPlugin.permission.admin_affiliates_deactivate'] = 'Deactivate Affiliate';
$lang['OrderPlugin.permission.admin_affiliates_settings'] = 'Affiliate Settings';
$lang['OrderPlugin.permission.admin_payouts'] = 'Payouts';
$lang['OrderPlugin.permission.admin_payouts_edit'] = 'Edit Payout';
$lang['OrderPlugin.permission.admin_payouts_approve'] = 'Approve Payout';
$lang['OrderPlugin.permission.admin_payouts_decline'] = 'Decline Payout';
$lang['OrderPlugin.permission.admin_payment_methods'] = 'Payment Methods';
$lang['OrderPlugin.permission.admin_payment_methods_add'] = 'Add Payment Method';
$lang['OrderPlugin.permission.admin_payment_methods_edit'] = 'Edit Payment Method';
$lang['OrderPlugin.permission.admin_payment_methods_delete'] = 'Delete Payment Method';

$lang['OrderPlugin.permission.admin_main'] = 'Orders';
$lang['OrderPlugin.permission.admin_main_widget'] = 'Client Profile Widget';

// Permission groups
$lang['OrderPlugin.permission.admin_affiliates'] = 'Affiliates';
