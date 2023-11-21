<?php
/**
 * Language definitions for the Client Services controller/views
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Error messages
$lang['ClientServices.!error.password_mismatch'] = 'The password does not match your log in password.';
$lang['ClientServices.!error.invoices_manage_options'] = 'The configurable options cannot be changed until all invoices containing this service have been paid.';
$lang['ClientServices.!error.invoices_change_term'] = 'The term cannot be changed until all invoices containing this service have been paid.';
$lang['ClientServices.!error.invoices_renew_service'] = 'The service cannot be renewed until all invoices containing this service have been paid.';
$lang['ClientServices.!error.invoices_upgrade_package'] = 'The package cannot be changed until all invoices containing this service have been paid.';
$lang['ClientServices.!error.tab_unavailable'] = 'That section is not accessible while the service is in a %1$s state.'; // %1$s is the language for the service status (e.g. Suspended, Canceled)


// Warning messages
$lang['ClientServices.!warning.invoices_upgrade_package'] = 'Packages cannot be changed until all invoices for this service have been paid.';
$lang['ClientServices.!warning.invoices_change_term'] = 'The term cannot be changed until all invoices for this service have been paid.';
$lang['ClientServices.!warning.invoices_manage_options'] = 'Configurable options cannot be changed until all invoices for this service have been paid.';


// Notice messages
$lang['ClientServices.!notice.queued_service_change'] = 'This service has pending changes. Additional changes may not be made until the current pending changes have been processed.';
$lang['ClientServices.!notice.client_limit'] = 'Unable to assign package. You have already reached the service limit for the target package.';


// Success messages
$lang['ClientServices.!success.manage.tab_updated'] = 'The data was successfully updated.';
$lang['ClientServices.!success.service_canceled'] = 'The service was successfully canceled.';
$lang['ClientServices.!success.service_schedule_canceled'] = 'The service is scheduled to be canceled at the end of its term.';
$lang['ClientServices.!success.service_not_canceled'] = 'The service will not be canceled.';
$lang['ClientServices.!success.service_term_updated'] = 'The service term has been updated and will take effect on the next renew date.';
$lang['ClientServices.!success.addon_service_created'] = 'The addon service has been successfully created. However, it will not be activated until after payment has been received.';
$lang['ClientServices.!success.service_package_updated'] = 'The service package has been updated.';
$lang['ClientServices.!success.config_options_updated'] = 'The configurable options were successfully updated.';
$lang['ClientServices.!success.service_queue_pay'] = 'The service update has been queued to be processed. However, it will not be processed until after a payment has been received.';
$lang['ClientServices.!success.service_queue'] = 'The service update has been queued and will be processed shortly.';
$lang['ClientServices.!success.service_renewed'] = 'The service was successfully renewed.';


// Index
$lang['ClientServices.index.page_title'] = 'Client #%1$s Services'; // %1$s is the client ID number

$lang['ClientServices.index.boxtitle_services'] = 'Services';

$lang['ClientServices.index.category_active'] = 'Active';
$lang['ClientServices.index.category_pending'] = 'Pending';
$lang['ClientServices.index.category_suspended'] = 'Suspended';
$lang['ClientServices.index.category_canceled'] = 'Canceled';

$lang['ClientServices.index.heading_addons'] = 'Add-ons';
$lang['ClientServices.index.heading_status'] = 'Status';

$lang['ClientServices.index.heading_package'] = 'Package';
$lang['ClientServices.index.heading_label'] = 'Label';
$lang['ClientServices.index.heading_term'] = 'Term';
$lang['ClientServices.index.heading_datecreated'] = 'Date Created';
$lang['ClientServices.index.heading_daterenews'] = 'Date Renews';
$lang['ClientServices.index.heading_datesuspended'] = 'Date Suspended';
$lang['ClientServices.index.heading_datecanceled'] = 'Date Canceled';
$lang['ClientServices.index.heading_options'] = 'Options';
$lang['ClientServices.index.option_manage'] = 'Manage';

$lang['ClientServices.index.recurring_term'] = '%1$s %2$s @ %3$s'; // %1$s is the service term length (number), %2$s is the service period, %3$s is the formatted service renewal price

$lang['ClientServices.index.text_never'] = 'Never';

$lang['ClientServices.index.no_results'] = 'You have no %1$s Services.'; // %1$s is the language for the services category type (e.g. Active, Pending)

$lang['ClientServices.serviceinfo.no_results'] = 'This service has no details.';
$lang['ClientServices.serviceinfo.cancellation_reason'] = 'Reason for Cancellation: %1$s'; // %1$s is the reason this service was canceled


// Manage
$lang['ClientServices.manage.page_title'] = 'Client #%1$s Manage Service'; // %1$s is the client ID number

$lang['ClientServices.manage.boxtitle_manage'] = 'Manage %1$s - %2$s'; // %1$s is the package name, %2$s is the service name
$lang['ClientServices.manage.tab_service_info'] = 'Information';
$lang['ClientServices.manage.tab_addons'] = 'Addons';
$lang['ClientServices.manage.tab_service_return'] = 'Return to Dashboard';

$lang['ClientServices.manage.button_active'] = 'Active';
$lang['ClientServices.manage.button_pending'] = 'Pending';
$lang['ClientServices.manage.button_suspended'] = 'Suspended';
$lang['ClientServices.manage.button_in_review'] = 'In Review';
$lang['ClientServices.manage.button_canceled'] = 'Canceled';
$lang['ClientServices.manage.button_renew'] = 'Renew Now';
$lang['ClientServices.manage.button_cancel'] = 'Cancel Options';
$lang['ClientServices.manage.button_change_service_term'] = 'Change Term';
$lang['ClientServices.manage.button_change_service_package'] = 'Change Package';
$lang['ClientServices.manage.button_config_options'] = 'Change Configurable Options';

$lang['ClientServices.manage.heading_package'] = 'Package';
$lang['ClientServices.manage.heading_date_added'] = 'Creation Date';
$lang['ClientServices.manage.heading_package_term'] = 'Billing Cycle';
$lang['ClientServices.manage.heading_service_name'] = 'Label';
$lang['ClientServices.manage.heading_date_renews'] = 'Renew Date';
$lang['ClientServices.manage.heading_date_next_invoice'] = 'Next Invoice';
$lang['ClientServices.manage.heading_price_initial'] = 'Amount';
$lang['ClientServices.manage.heading_price'] = 'Recurring Amount';
$lang['ClientServices.manage.heading_setup_fee'] = 'Setup Fee';
$lang['ClientServices.manage.heading_price_onetime'] = 'Amount';
$lang['ClientServices.manage.heading_recurring_coupon'] = 'Recurring Coupon';
$lang['ClientServices.manage.text_coupon_percent'] = '%1$s (%2$s%%)';
$lang['ClientServices.manage.text_coupon_percent'] = '%1$s (%2$s%%)'; // %1$s is the coupon code, %2$s is the coupon discount percentage. You MUST use two % signs to represent a single percent (i.e. %%)
$lang['ClientServices.manage.text_coupon_amount'] = '%1$s (%2$s)'; // %1$s is the coupon code, %2$s is the formatted coupon amount

$lang['ClientServices.manage.text_price'] = '%1$sx %2$s'; // %1$s is the service quantity, %2$s is the package price
$lang['ClientServices.manage.text_date_never'] = 'Never';
$lang['ClientServices.manage.text_date_suspended'] = 'This service was suspended on %1$s.'; // %1$s is the date the service was suspended
$lang['ClientServices.manage.text_date_canceled'] = 'This service was canceled on %1$s.'; // %1$s is the date the service was canceled
$lang['ClientServices.manage.text_date_to_cancel'] = 'This service is scheduled to be canceled on %1$s.'; // %1$s is the date the service is scheduled to be canceled

$lang['ClientServices.manage.heading_options'] = 'Actions';
$lang['ClientServices.manage.heading_config_options'] = 'Configurable Options';


// Renew
$lang['ClientServices.renew.page_title'] = 'Client #%1$s Renew Service'; // %1$s is the client ID number

$lang['ClientServices.renew.heading_renew'] = 'Renew Service';

$lang['ClientServices.renew.field_pricing_id'] = 'Renewal Term';
$lang['ClientServices.renew.field_password'] = 'Log In Password to Confirm Changes';
$lang['ClientServices.renew.field_renew_cancel'] = 'Cancel, do not renew';
$lang['ClientServices.renew.field_renew_submit'] = 'Renew';
$lang['ClientServices.renew.confirm_renew'] = 'By clicking on "Save" a new invoice will be generated for the renewal of the service for the "%1$s" term, which will cover the service until "%2$s", by clicking on "Save" you will be redirected to the payment page to pay the generated invoice.'; // %1$s is the renewal term and %2$s is the next renewal date of the service

// Cancel
$lang['ClientServices.cancel.page_title'] = 'Client #%1$s Cancel Service'; // %1$s is the client ID number

$lang['ClientServices.cancel.heading_cancel'] = 'Cancel Service';

$lang['ClientServices.cancel.field_term_date'] = 'At End of Term (%1$s)'; // %1$s is the date the service's term ends
$lang['ClientServices.cancel.field_term'] = 'At End of Term';
$lang['ClientServices.cancel.field_now'] = 'Immediately';
$lang['ClientServices.cancel.field_do_not'] = 'Do not cancel';
$lang['ClientServices.cancel.field_cancellation_reason'] = 'Cancellation Reason';
$lang['ClientServices.cancel.field_password'] = 'Log In Password to Confirm Changes';
$lang['ClientServices.cancel.field_cancel_cancel'] = 'Cancel, do not change';
$lang['ClientServices.cancel.field_cancel_submit'] = 'Save';
$lang['ClientServices.cancel.confirm_cancel'] = 'Are you sure you want to cancel this service at the end of its term?';
$lang['ClientServices.cancel.confirm_cancel_now'] = 'Are you sure you want to cancel this service?';
$lang['ClientServices.cancel.confirm_cancel_now_fee'] = 'Canceling this service immediately will incur a cancellation fee of %1$s.'; // %1$s is the formatted amount of the cancelation fee
$lang['ClientServices.cancel.confirm_cancel_now_fee_tax'] = 'Canceling this service immediately will incur a cancellation fee of %1$s plus tax.'; // %1$s is the formatted amount of the cancelation fee


// Change Service Term
$lang['ClientServices.changeterm.page_title'] = 'Client #%1$s Change Term'; // %1$s is the client ID number

$lang['ClientServices.change_term.boxtitle'] = 'Change Term for %1$s - %2$s'; // %1$s is the package name, %2$s is the service name
$lang['ClientServices.change_term.field_current_term'] = 'Current Term';
$lang['ClientServices.change_term.field_pricing_id'] = 'New Term';

$lang['ClientServices.change_term.cancel'] = 'Cancel';
$lang['ClientServices.change_term.review'] = 'Review';


$lang['ClientServices.get_package_terms.term'] = '%1$s %2$s - %3$s'; // %1$s is the pricing term, %2$s is the pricing period, and %3$s is the formatted price
$lang['ClientServices.get_package_terms.term_recurring'] = '%1$s %2$s - %3$s (renews @ %4$s)'; // %1$s is the pricing term, %2$s is the pricing period, and %3$s is the formatted initial price, %4$s is the formatted renewal price
$lang['ClientServices.get_package_terms.term_onetime'] = '%1$s - %2$s'; // %1$s is the pricing period, and %2$s is the formatted price


// Upgrade Service
$lang['ClientServices.upgrade.page_title'] = 'Client #%1$s Change Package'; // %1$s is the client ID number

$lang['ClientServices.upgrade.boxtitle'] = 'Change Package from %1$s - %2$s'; // %1$s is the package name, %2$s is the service name
$lang['ClientServices.upgrade.btn_make_payment'] = 'Make a Payment';
$lang['ClientServices.upgrade.field_current_package'] = 'Current Package';
$lang['ClientServices.upgrade.field_pricing_id'] = 'New Package';
$lang['ClientServices.upgrade.field_cancel'] = 'Cancel';
$lang['ClientServices.upgrade.field_submit'] = 'Review';

$lang['ClientServices.upgrade.text_prorate'] = "If the new package you select costs more, you'll be invoiced for the prorated difference.";

$lang['ClientServices.upgrade.current_package'] = '%1$s (%2$s %3$s - %4$s)'; // %1$s is the package name, %2$s is the pricing term, %3$s is the pricing period, and %4$s is the formatted price
$lang['ClientServices.upgrade.current_package_onetime'] = '%1$s (%2$s - %3$s)'; // %1$s is the package name, %2$s is the pricing period, and %3$s is the formatted price


// Addons
$lang['ClientServices.addons.page_title'] = 'Client #%1$s Addons'; // %1$s is the client ID number
$lang['ClientServices.addons.boxtitle_addons'] = 'Addons for %1$s - %2$s'; // %1$s is the package name, %2$s is the service name
$lang['ClientServices.addons.create_addon'] = 'Add Addon';
$lang['ClientServices.addons.no_results'] = 'No addons are attached to this service.';


// Add addon
$lang['ClientServices.!error.addon_invalid'] = 'Please select a valid addon.';
$lang['ClientServices.addaddon.page_title'] = 'Client #%1$s Add Addon'; // %1$s is the client ID number
$lang['ClientServices.addaddon.boxtitle_addons'] = 'Add Addon for %1$s - %2$s'; // %1$s is the package name, %2$s is the service name
$lang['ClientServices.addaddon.header_add'] = 'Addons';
$lang['ClientServices.addaddon.term'] = '%1$s %2$s'; // %1$s is the package term (e.g. 1), %2$s is the package period (e.g. Month)
$lang['ClientServices.addaddon.term_price'] = '%1$s - %2$s'; // %1$s is the package term, (e.g. 1 Month), %2$s is the formatted package price
$lang['ClientServices.addaddon.term_price_recurring'] = '%1$s - %2$s (renews @ %3$s)'; // %1$s is the package term, (e.g. 1 Month), %2$s is the formatted initial package price, %3$s is the formatted renewal package price
$lang['ClientServices.addaddon.term_price_setupfee'] = '%1$s - %2$s + %3$s setup'; // %1$s is the package term, (e.g. 1 Month), %2$s is the formatted package price, %3$s is the formatted setup fee price
$lang['ClientServices.addaddon.term_price_setupfee_recurring'] = '%1$s - %2$s + %3$s setup (renews @ %4$s)'; // %1$s is the package term, (e.g. 1 Month), %2$s is the formatted initial package price, %3$s is the formatted setup fee price, %4$s is the formatted renewal package price
$lang['ClientServices.addaddon.submit_add'] = 'Create';


// Get addon options
$lang['ClientServices.getaddonoptions.field_module_group_id'] = 'Group';


// Configure Addon
$lang['ClientServices.configure_addon.header_options'] = 'Configurable Options'; // %1$s is the module name


// Manage Configurable Options
$lang['ClientServices.manageoptions.page_title'] = 'Client #%1$s Manage Configurable Options'; // %1$s is the client ID number
$lang['ClientServices.manageoptions.boxtitle_options'] = 'Manage Configurable Options for %1$s - %2$s'; // %1$s is the package name, %2$s is the service name

$lang['ClientServices.manageoptions.heading_current'] = 'Current Options';
$lang['ClientServices.manageoptions.heading_new'] = 'New Options';

$lang['ClientServices.manageoptions.no_options'] = 'There are no current configurable options available to update.';
$lang['ClientServices.manageoptions.cancel'] = 'Cancel';
$lang['ClientServices.manageoptions.review'] = 'Review';


// Review package/term/option changes
$lang['ClientServices.review.page_title'] = 'Client #%1$s Review Service'; // %1$s is the client ID number

$lang['ClientServices.review.boxtitle_review'] = 'Review Changes to %1$s - %2$s'; // %1$s is the package name, %2$s is the service name
$lang['ClientServices.review.heading_current_service'] = 'Current Service';
$lang['ClientServices.review.heading_updated_service'] = 'Updated Service';
$lang['ClientServices.review.heading_label'] = 'Option';
$lang['ClientServices.review.heading_old_value'] = 'Current Value';
$lang['ClientServices.review.heading_new_value'] = 'New Value';
$lang['ClientServices.review.value'] = '%1$s (%2$sx %3$s)'; // %1$s is the config option value name, %2$s is the quantity, %3$s is the formatted price
$lang['ClientServices.review.value_setup_fee'] = '%1$s (%2$sx %3$s, %4$s Setup Fee)'; // %1$s is the config option value name, %2$s is the quantity, %3$s is the formatted price, %4$s is the formatted setup fee
$lang['ClientServices.review.none'] = '-';

$lang['ClientServices.review.cancel'] = 'Cancel';
$lang['ClientServices.review.update'] = 'Save';


// Totals
$lang['ClientServices.totals.subtotal'] = 'Subtotal:';
$lang['ClientServices.totals.total'] = 'Total Due Today:';
$lang['ClientServices.totals.total_recurring'] = 'Total When Renewing:';
$lang['ClientServices.!tooltip.total_recurring'] = 'The total price when renewing represents the total cost of this service and all of its options expected at next renewal.';
