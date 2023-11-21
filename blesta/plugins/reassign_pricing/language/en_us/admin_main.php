<?php
// Success
$lang['AdminMain.!success.service_updated'] = "The service pricing has been successfully updated.";


// Warning
$lang['AdminMain.!warning.reassign_pricing'] = "Reassigning pricing will only change the package and pricing associated with this service. Do not perform this action for upgrades or downgrades.";


// Page titles
$lang['AdminMain.index.page_title'] = "Client #%1\$s Reassign Pricing"; // %1$s is the client ID code


// Index
$lang['AdminMain.index.boxtitle_services'] = "Services for Client #%1\$s"; // %1$s is the client ID code
$lang['AdminMain.index.link_client'] = "Back to Client #%1\$s"; // %1$s is the client ID code
$lang['AdminMain.index.heading_package'] = "Package";
$lang['AdminMain.index.heading_label'] = "Label";
$lang['AdminMain.index.heading_term'] = "Term";
$lang['AdminMain.index.heading_date_created'] = "Date Created";
$lang['AdminMain.index.heading_date_renews'] = "Date Renews";
$lang['AdminMain.index.heading_options'] = "Options";
$lang['AdminMain.index.recurring_term'] = "%1\$s %2\$s @ %3\$s"; // %1$s is the term, %2$s is the period, %3$s is the renewal price
$lang['AdminMain.index.text_never'] = "Never";
$lang['AdminMain.index.no_results'] = "There are no services available from which to reassign pricing.";
$lang['AdminMain.index.option_service'] = "Reassign Pricing";


// Service
$lang['AdminMain.service.term_onetime'] = "Onetime - %3\$s"; // %1$s is the pricing term, %2$s is the pricing period, and %3$s is the formatted price
$lang['AdminMain.service.term'] = "%1\$s %2\$s - %3\$s"; // %1$s is the pricing term, %2$s is the pricing period, and %3$s is the formatted price

$lang['AdminMain.service.boxtitle_pricing'] = "Reassign Pricing for Service: %1\$s - %2\$s"; // %1$s is the package name, %2$s is the service name
$lang['AdminMain.service.heading_information'] = "Service Information";
$lang['AdminMain.service.text_package_name'] = "Package Name:";
$lang['AdminMain.service.text_label'] = "Label:";
$lang['AdminMain.service.text_qty'] = "Quantity:";
$lang['AdminMain.service.text_term'] = "Term:";
$lang['AdminMain.service.text_status'] = "Package Name:";
$lang['AdminMain.service.text_date_added'] = "Date Added:";
$lang['AdminMain.service.text_date_renews'] = "Date Renews:";
$lang['AdminMain.service.text_recurring_coupon'] = "Recurring Coupon:";
$lang['AdminMain.service.text_coupon_percent'] = "%1\$s (%2\$s%%)"; // %1$s is the coupon code, %2$s is the coupon discount percentage. You MUST use two % signs to represent a single percent (i.e. %%)
$lang['AdminMain.service.text_coupon_amount'] = "%1\$s (%2\$s)"; // %1$s is the coupon code, %2$s is the formatted coupon amount
$lang['AdminMain.service.text_renewal_price'] = "Renewal Price:";

$lang['AdminMain.service.heading_pricing'] = "Reassign Pricing";
$lang['AdminMain.service.field_pricing'] = "Package/Term";
$lang['AdminMain.service.field_group'] = "Package Group";
$lang['AdminMain.service.field_cancel'] = "Cancel";
$lang['AdminMain.service.field_submit'] = "Save";
