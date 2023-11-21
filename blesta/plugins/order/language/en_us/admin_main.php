<?php
$lang['AdminMain.!success.status_updated'] = "The selected orders have been successfully updated.";
$lang['AdminMain.!success.settings_updated'] = "Your order settings were successfully updated.";
$lang['AdminMain.!success.affiliate_settings_updated'] = "The affiliate settings have been updated.";

$lang['AdminMain.index.boxtitle_order'] = "Orders";
$lang['AdminMain.index.no_results'] = "There are no orders of this type.";
$lang['AdminMain.index.category_pending'] = "In Review";
$lang['AdminMain.index.category_accepted'] = "Accepted";
$lang['AdminMain.index.category_fraud'] = "Fraud";
$lang['AdminMain.index.category_canceled'] = "Canceled";

// Order listing
$lang['AdminMain.index.heading_order_number'] = "Order #";
$lang['AdminMain.index.heading_client_id_code'] = "Client ID";
$lang['AdminMain.index.heading_invoice_id_code'] = "Invoice #";
$lang['AdminMain.index.heading_total'] = "Total";
$lang['AdminMain.index.heading_paid'] = "Paid";
$lang['AdminMain.index.heading_date_added'] = "Date Ordered";
$lang['AdminMain.index.field_ordersubmit'] = "Update Orders";
$lang['AdminMain.index.field_markas'] = "Mark as:";
$lang['AdminMain.index.text_location'] = "Location";

// Search
$lang['AdminMain.search.page_title'] = 'Search Results for "%1$s"'; // %1$s is the search keywords
$lang['AdminMain.search.boxtitle_order'] = 'Search Orders for "%1$s"'; // %1$s is the search keywords


$lang['AdminMain.getfilters.field_order_number'] = 'Order #';

// Order info
$lang['AdminMain.orderinfo.fraudreport_heading'] = "Fraud Report";
$lang['AdminMain.orderinfo.fraudreport_heading_key'] = "Key";
$lang['AdminMain.orderinfo.fraudreport_heading_value'] = "Value";

$lang['AdminMain.orderinfo.applied_heading'] = "Transactions";
$lang['AdminMain.orderinfo.applied_heading_paymenttype'] = "Payment Type";
$lang['AdminMain.orderinfo.applied_heading_amount'] = "Amount";
$lang['AdminMain.orderinfo.applied_heading_applied'] = "Applied";
$lang['AdminMain.orderinfo.applied_heading_appliedon'] = "Applied On";
$lang['AdminMain.orderinfo.applied_heading_options'] = "Options";
$lang['AdminMain.orderinfo.applied_option_edit'] = "Edit";
$lang['AdminMain.orderinfo.applied_no_results'] = "There are no transactions applied to this order.";

$lang['AdminMain.orderinfo.services_heading'] = "Services";
$lang['AdminMain.orderinfo.services_heading_package'] = "Package";
$lang['AdminMain.orderinfo.services_heading_label'] = "Label";
$lang['AdminMain.orderinfo.services_heading_term'] = "Term";
$lang['AdminMain.orderinfo.services_heading_dateadded'] = "Date Added";
$lang['AdminMain.orderinfo.services_heading_daterenews'] = "Date Renews";
$lang['AdminMain.orderinfo.services_heading_options'] = "Options";
$lang['AdminMain.orderinfo.services_option_manage'] = "Manage";

$lang['AdminMain.orderinfo.services_text_never'] = "Never";
$lang['AdminMain.orderinfo.services_no_results'] = "There are no services in this order.";

// Settings
$lang['AdminMain.settings.heading_notifications'] = "Order Notifications";
$lang['AdminMain.settings.field_email_notice'] = "Email";
$lang['AdminMain.settings.field_email_notice_never'] = "Do not send";
$lang['AdminMain.settings.field_email_notice_manual'] = "Only send if manual approval required";
$lang['AdminMain.settings.field_email_notice_always'] = "Always send";
$lang['AdminMain.settings.field_mobile_notice'] = "Mobile";
$lang['AdminMain.settings.field_mobile_notice_never'] = "Do not send";
$lang['AdminMain.settings.field_mobile_notice_manual'] = "Only send if manual approval required";
$lang['AdminMain.settings.field_mobile_notice_always'] = "Always send";
$lang['AdminMain.settings.field_messenger_notice'] = "Messengers";
$lang['AdminMain.settings.field_messenger_notice_never'] = "Do not send";
$lang['AdminMain.settings.field_messenger_notice_manual'] = "Only send if manual approval required";
$lang['AdminMain.settings.field_messenger_notice_always'] = "Always send";

$lang['AdminMain.settings.heading_affiliate_notifications'] = "Affiliate Notifications";
$lang['AdminMain.settings.field_payout_notice'] = 'Payout';
$lang['AdminMain.settings.field_payout_notice_never'] = 'Never';
$lang['AdminMain.settings.field_payout_notice_always'] = 'Always';

$lang['AdminMain.settings.submit_settings'] = "Update Settings";
$lang['AdminMain.settings.submit_cancel'] = "Return to Order Listing";

// Affiliate Page
$lang['AdminMain.affiliates.page_title'] = "Affiliate Overview";
$lang['AdminMain.affiliates.boxtitle_affiliates'] = "Affiliate Overview";
$lang['AdminMain.affiliates.inactive'] = "This affiliate has been marked inactive.";
$lang['AdminMain.affiliates.activate'] = "Click here to reactivate";
$lang['AdminMain.affiliates.no_results'] = "No affiliate is associated with this client.  Click here to sign up.";

$lang['AdminMain.affiliates.boxtitle_affiliates'] = 'Affiliate Overview';
$lang['AdminMain.affiliates.title_details'] = 'Details';
$lang['AdminMain.affiliates.title_referral'] = 'Referral Link';

$lang['AdminMain.affiliates.visits'] = 'Clicks';
$lang['AdminMain.affiliates.monthly_visits'] = '%1$s/mo on average'; // %1$s is the number of average monthly visits
$lang['AdminMain.affiliates.sales'] = 'Total Sales';
$lang['AdminMain.affiliates.monthly_sales'] = '%1$s/mo on average'; // %1$s is the number of average monthly sales
$lang['AdminMain.affiliates.conversion_rate'] = 'Conversion Rate';
$lang['AdminMain.affiliates.conversion_fraction'] = '%1$s/%2$s on average'; // %1$s 1 or 0 if there are no sales, %2$s is the average number of visits per sale

$lang['AdminMain.affiliates.commission_type'] = 'Payout Level';
$lang['AdminMain.affiliates.min_withdrawal_amount'] = 'Minimum Payout Amount';
$lang['AdminMain.affiliates.maturity_days'] = 'Days to Mature';
$lang['AdminMain.affiliates.available_payout'] = 'Available for Payout';

$lang['AdminMain.affiliates.field.commission_fixed'] = '%1$s one-time for every matured referral'; // %1$s is the commission amount
$lang['AdminMain.affiliates.field.commission_percentage'] = '%1$s of each matured referral'; // %1$s is the commission percentage

$lang['AdminMain.affiliates.request_payout'] = 'Request Payout';

$lang['AdminMain.affiliates.boxtitle_client'] = 'Client #%1$s - %2$s %3$s';
$lang['AdminMain.affiliates.heading_affiliates'] = 'Affiliate Settings';
$lang['AdminMain.affiliates.heading_referral_link'] = 'Referral Link';
$lang['AdminMain.affiliates.field_commission_type'] = 'Commission Type';
$lang['AdminMain.affiliates.tooltip_commission_type'] = 'The calculation method used to determine referral commission.  Either a fixed value per order or a percentage of the order total.';
$lang['AdminMain.affiliates.field_commission_amount'] = 'Commission Amount';
$lang['AdminMain.affiliates.field_order_frequency'] = 'Order Frequency';
$lang['AdminMain.affiliates.tooltip_order_frequency'] = 'First to pay referrals only for the first order a client makes using a referral link, or Any to pay referrals for each order a client makes using a referral link.';
$lang['AdminMain.affiliates.field_order_recurring'] = 'Order Recurring';
$lang['AdminMain.affiliates.tooltip_order_recurring'] = 'Whether referrals will be paid for services ordered that renew.';
$lang['AdminMain.affiliates.field_maturity_days'] = 'Maturity Days';
$lang['AdminMain.affiliates.tooltip_maturity_days'] = 'The number of days after payment a referral matures and becomes available for payout.';
$lang['AdminMain.affiliates.field_min_withdrawal_amount'] = 'Minimum Withdrawal Amount';
$lang['AdminMain.affiliates.field_max_withdrawal_amount'] = 'Maximum Withdrawal Amount';
$lang['AdminMain.affiliates.field_withdrawal_currency'] = 'Withdrawal Currency';
$lang['AdminMain.affiliates.text_code_order_form'] = 'You may also link to a particular order form page as an affiliate by adding at the end ?a=%1$s to a link without query parameters or &a=%1$s to a link with query parameters.';

$lang['AdminMain.affiliates.heading_statistics'] = 'Statistics';
$lang['AdminMain.affiliates.graph_referrals'] = 'Referrals';
$lang['AdminMain.affiliates.graph_visits'] = 'Visits';
$lang['AdminMain.affiliates.graph_sales'] = 'Sales';

$lang['AdminMain.affiliates.submit_settings'] = 'Save Settings';
$lang['AdminMain.affiliates.submit_cancel'] = 'Cancel';
