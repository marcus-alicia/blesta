<?php
/**
 * Language definitions for the Invoices model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Recurring invoice pricing periods
$lang['Invoices.getPricingPeriods.day'] = 'Day';
$lang['Invoices.getPricingPeriods.week'] = 'Week';
$lang['Invoices.getPricingPeriods.month'] = 'Month';
$lang['Invoices.getPricingPeriods.year'] = 'Year';

// Invoice delivery methods
$lang['Invoices.getDeliveryMethods.email'] = 'Email';
$lang['Invoices.getDeliveryMethods.paper'] = 'Paper';
$lang['Invoices.getDeliveryMethods.interfax'] = 'InterFax';
$lang['Invoices.getDeliveryMethods.postalmethods'] = 'PostalMethods';

// Private note descriptions
$lang['Invoices.!note_private.service_cancel_date'] = 'Service #%1$s canceled %2$s.'; // %1$s is the service ID, %2$s is the formatted service cancellation date and time
$lang['Invoices.!note_private.removed_lines'] = 'Removed line items:';
$lang['Invoices.!note_private.line_item'] = '- %1$s @ %2$s: %3$s'; // %1$s is the line item quantity, %2$s is the unit price, %3$s is the line item description

// Invoice line item descriptions
$lang['Invoices.!line_item.service_renew_description'] = '%5$s%1$s - %2$s (%3$s - %4$s)'; // %1$s is the name of the package, %2$s is the name of the service, %3$s is the service's renew date, %4$s is the service's next renew date, %5$s is the addon identifier set only for addon services
$lang['Invoices.!line_item.service_cancel_fee_description'] = '%1$s - %2$s Cancelation Fee'; // %1$s is the name of the package, %2$s is the name of the service
$lang['Invoices.!line_item.service_setup_fee_description'] = '%1$s - %2$s Setup Fee'; // %1$s is the name of the package, %2$s is the name of the service
$lang['Invoices.!line_item.service_option_renew_description'] = '↳ %1$s - %2$s'; // %1$s is the package option label, %2$s is the service option name
$lang['Invoices.!line_item.service_option_setup_fee_description'] = '↳ %1$s - %2$s Setup Fee'; // %1$s is the package option label, %2$s is the service option name
$lang['Invoices.!line_item.service_prorated_upgrade_description'] = 'Prorated Upgrade from %1$s to %2$s - %3$s (%4$s - %5$s)'; // %1$s is the current package name, %2$s is the new package name, %3$s is the service name, %4$s is the current date, %5$s is the service's next renew date
$lang['Invoices.!line_item.service_prorated_upgrade_description_onetime'] = 'Prorated Upgrade from %1$s to %2$s - %3$s'; // %1$s is the current package name, %2$s is the new package name, %3$s is the service name
$lang['Invoices.!line_item.service_option_prorated_upgrade'] = '↳ Prorated Upgrade of %1$s from %2$s to %3$s'; // %1$s is the service option label name, %2$s is the current service option value, %3$s is the new service option value
$lang['Invoices.!line_item.service_option_prorated_upgrade_date'] = '↳ Prorated Upgrade of %1$s from %2$s to %3$s (%4$s - %5$s)'; // %1$s is the service option label name, %2$s is the current service option value, %3$s is the new service option value, %4$s is the current date, %5$s is the service's next renew date
$lang['Invoices.!line_item.service_option_prorated_upgrade_onetime'] = '↳ Prorated Upgrade of %1$s from %2$s to %3$s'; // %1$s is the service option label name, %2$s is the current service option value, %3$s is the new service option value
$lang['Invoices.!line_item.service_option_prorated_upgrade_text'] = '↳ Prorated Upgrade of %1$s'; // %1$s is the service option label name
$lang['Invoices.!line_item.service_option_prorated_upgrade_text_date'] = '↳ Prorated Upgrade of %1$s (%2$s - %3$s)'; // %1$s is the service option label name, %2$s is the current date, %3$s is the service's next renew date
$lang['Invoices.!line_item.service_option_prorated_upgrade_text_onetime'] = '↳ Prorated Upgrade of %1$s'; // %1$s is the service option label name
$lang['Invoices.!line_item.service_option_prorated_upgrade_qty'] = '↳ Prorated Upgrade of %1$s from %2$sx %3$s to %4$sx %5$s'; // %1$s is the service option label name, %2$s is the old service option quantity, %3$s is the current service option value, %4$s is the new service option quantity, %5$s is the new service option value
$lang['Invoices.!line_item.service_option_prorated_upgrade_qty_date'] = '↳ Prorated Upgrade of %1$s from %2$sx %3$s to %4$sx %5$s (%6$s - %7$s)'; // %1$s is the service option label name, %2$s is the old service option quantity, %3$s is the current service option value, %4$s is the new service option quantity, %5$s is the new service option value, %6$s is the current date, %7$s is the service's next renew date
$lang['Invoices.!line_item.service_option_prorated_upgrade_qty_onetime'] = '↳ Prorated Upgrade of %1$s from %2$sx %3$s to %4$sx %5$s'; // %1$s is the service option label name, %2$s is the old service option quantity, %3$s is the current service option value, %4$s is the new service option quantity, %5$s is the new service option value
$lang['Invoices.!line_item.service_option_prorated_addition'] = '↳ Prorated Addition of %1$s %2$s'; // %1$s is the service option label name, %2$s is the service option value
$lang['Invoices.!line_item.service_option_prorated_addition_date'] = '↳ Prorated Addition of %1$s %2$s (%3$s - %4$s)'; // %1$s is the service option label name, %2$s is the service option value, %3$s is the current date, %4$s is the service's next renew date
$lang['Invoices.!line_item.service_option_prorated_addition_onetime'] = '↳ Prorated Addition of %1$s %2$s'; // %1$s is the service option label name, %2$s is the service option value
$lang['Invoices.!line_item.service_option_prorated_addition_text'] = '↳ Prorated Addition of %1$s'; // %1$s is the service option label name
$lang['Invoices.!line_item.service_option_prorated_addition_text_date'] = '↳ Prorated Addition of %1$s (%2$s - %3$s)'; // %1$s is the service option label name, %2$s is the current date, %3$s is the service's next renew date
$lang['Invoices.!line_item.service_option_prorated_addition_text_onetime'] = '↳ Prorated Addition of %1$s'; // %1$s is the service option label name
$lang['Invoices.!line_item.service_option_prorated_addition_qty'] = '↳ Prorated Addition of %1$s %2$sx %3$s'; // %1$s is the service option label name, %2$s is the service option quantity, %3$s is the service option value
$lang['Invoices.!line_item.service_option_prorated_addition_qty_date'] = '↳ Prorated Addition of %1$s %2$sx %3$s (%4$s - %5$s)'; // %1$s is the service option label name, %2$s is the service option quantity, %3$s is the service option value, %4$s is the current date, %5$s is the service's next renew date
$lang['Invoices.!line_item.service_option_prorated_addition_qty_onetime'] = '↳ Prorated Addition of %1$s %2$sx %3$s'; // %1$s is the service option label name, %2$s is the service option quantity, %3$s is the service option value
$lang['Invoices.!line_item.prorated_credit'] = 'Prorated Credit';
$lang['Invoices.!line_item.coupon_line_item_description_amount'] = 'Coupon %1$s'; // %1$s is the coupon code
$lang['Invoices.!line_item.coupon_line_item_description_percent'] = 'Coupon %1$s - %2$s%%'; // %1$s is the coupon code, %2$s is the coupon discount percentage, the two percent symbols (%%) must both be used together to display a single percent symbol
$lang['Invoices.!line_item.recurring_renew_description'] = '%1$s (%2$s - %3$s)'; // %1$s is the line item description, %2$s is the invoice's renew date, %3$s is the invoice's next renew date


// Statuses
$lang['Invoices.status.active'] = 'Active';
$lang['Invoices.status.proforma'] = 'Pro forma';
$lang['Invoices.status.draft'] = 'Draft';
$lang['Invoices.status.void'] = 'Void';

// Types
$lang['Invoices.types.standard'] = 'Standard';
$lang['Invoices.types.proforma'] = 'Pro forma';

// Cache methods
$lang['Invoices.cache_methods.none'] = 'None';
$lang['Invoices.cache_methods.json'] = 'JSON';
$lang['Invoices.cache_methods.json_pdf'] = 'JSON + PDF';


// Invoice Delivery errors
$lang['Invoices.!error.invoice_id.exists'] = 'Invalid invoice ID.';
$lang['Invoices.!error.invoice_recur_id.exists'] = 'Invalid recurring invoice ID.';
$lang['Invoices.!error.method.exists'] = 'You must set at least one delivery method.';

$lang['Invoices.!error.delivery.empty'] = 'Please enter an invoice delivery method.';
$lang['Invoices.!error.delivery.length'] = 'The invoice delivery method length may not exceed 32 characters.';

// Invoice errors
$lang['Invoices.!error.invoice_add.failed'] = 'This invoice could not be created. Please try again.';
$lang['Invoices.!error.id_format.empty'] = 'No ID format set for invoices.';
$lang['Invoices.!error.id_format.length'] = 'The ID format for invoices may not exceed 64 characters.';
$lang['Invoices.!error.id_value.valid'] = 'Unable to determine invoice ID value.';
$lang['Invoices.!error.id.amount_applied'] = 'Invoice lines, currency, and status may not be updated because an amount has already been applied to this invoice.';
$lang['Invoices.!error.client_id.exists'] = 'Invalid client ID.';
$lang['Invoices.!error.date_billed.format'] = 'The billed date is in an invalid date format.';
$lang['Invoices.!error.date_due.format'] = 'The due date is in an invalid date format.';
$lang['Invoices.!error.date_due.after_billed'] = 'The date due must be on or after the date billed.';
$lang['Invoices.!error.date_closed.format'] = 'The closed date is in an invalid date format.';
$lang['Invoices.!error.date_autodebit.format'] = 'The due date is in an invalid date format.';
$lang['Invoices.!error.autodebit.valid'] = 'Please select whether or not to allow autodebit for this invoice.';
$lang['Invoices.!error.status.format'] = 'Invalid status.';
$lang['Invoices.!error.currency.length'] = 'The currency code must be 3 characters in length.';
$lang['Invoices.!error.delivery.exists'] = 'The delivery method given does not exist.';
$lang['Invoices.!error.term.format'] = 'The term should be a number.';
$lang['Invoices.!error.term.bounds'] = 'The term should be between 1 and 65535.';
$lang['Invoices.!error.period.format'] = 'The period is invalid.';
$lang['Invoices.!error.duration.format'] = 'The duration is invalid.';
$lang['Invoices.!error.date_renews.format'] = 'The recurring invoice renew date must be a valid date format.';
$lang['Invoices.!error.date_last_renewed.format'] = 'The last recurring invoice renew date must be a valid date format.';
$lang['Invoices.!error.invoice_id.draft'] = 'The given invoice is not a draft invoice, and therefore could not be deleted.';
$lang['Invoices.!error.domain_renew.failed'] = 'Domains can only be renewed for up to 10 years.';


// Invoice line errors
$lang['Invoices.!error.lines[][id].exists'] = 'Invalid line item ID.';
$lang['Invoices.!error.lines[][service_id].exists'] = 'Invalid service ID.';
$lang['Invoices.!error.lines[][description].empty'] = 'Please enter a line item description.';
$lang['Invoices.!error.lines[][qty].format'] = 'The quantity must be a number.';
$lang['Invoices.!error.lines[][qty].minimum'] = 'Please enter a quantity of 1 or more.';
$lang['Invoices.!error.lines[][amount].format'] = 'The unit cost must be a number.';
$lang['Invoices.!error.lines[][tax].format'] = "Line item tax must be a 'true' or 'false'";
