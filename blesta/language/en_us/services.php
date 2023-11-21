<?php
/**
 * Language definitions for the Services model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Service field errors
$lang['Services.!error.key.empty'] = 'The service field key must not be empty.';
$lang['Services.!error.key.length'] = 'The service field key must not exceed 32 characters.';
$lang['Services.!error.value.empty'] = 'The service field value must not be empty.';
$lang['Services.!error.encrypted.format'] = 'Invalid format for encrypted.';
$lang['Services.!error.move.unpaid_invoices'] = 'The service can\'t be moved to another client, because it has unpaid invoices.';


// Service errors
$lang['Services.!error.service_id.exists'] = 'Invalid service ID.';
$lang['Services.!error.parent_service_id.exists'] = 'Invalid parent service ID.';
$lang['Services.!error.parent_service_id.parent'] = 'The parent service ID already exists as a child to another service.';
$lang['Services.!error.package_group_id.exists'] = 'Invalid package group ID.';
$lang['Services.!error.id_format.empty'] = 'No ID format set for invoices.';
$lang['Services.!error.id_format.length'] = 'The ID format for invoices may not exceed 64 characters.';
$lang['Services.!error.id_value.valid'] = 'Unable to determine invoice ID value.';
$lang['Services.!error.pricing_id.exists'] = 'Please select a valid term.';
$lang['Services.!error.pricing_id.overrides'] = 'The package term cannot be changed when price overrides are set.';
$lang['Services.!error.client_id.exists'] = 'The client does not exist.';
$lang['Services.!error.client_id.allowed'] = 'The client can not access that package.';
$lang['Services.!error.module_row_id.exists'] = 'Invalid module row ID.';
$lang['Services.!error.coupon_id.valid'] = 'That coupon does not appear to be valid.';
$lang['Services.!error.qty.format'] = 'Quantity must be a number.';
$lang['Services.!error.qty.length'] = 'Quantity length may not exceed 10 characters.';
$lang['Services.!error.qty.available'] = 'Quantity limit reached. If possible, please select a smaller quantity.';
$lang['Services.!error.module_row.valid'] = 'The module row does not exist.';
$lang['Services.!error.module_group.valid'] = 'The module group does not exist.';
$lang['Services.!error.override_price.format'] = 'The price override must be a number.';
$lang['Services.!error.override_price.override'] = 'Both a price and currency must be set to override the current price.';
$lang['Services.!error.override_currency.format'] = 'Please select a valid currency.';
$lang['Services.!error.status.format'] = 'Invalid status.';
$lang['Services.!error.date_added.format'] = 'Invalid date added format.';
$lang['Services.!error.date_renews.format'] = 'Invalid renew date format.';
$lang['Services.!error.date_renews.valid'] = 'Renew date must be greater than last renew date of %1$s.'; // %1$s is the last renew date
$lang['Services.!error.date_last_renewed.format'] = 'Invalid last renewed date format.';
$lang['Services.!error.date_suspended.format'] = 'Invalid suspended date format.';
$lang['Services.!error.date_canceled.format'] = 'Invalid canceled date format.';
$lang['Services.!error.use_module.format'] = 'Invalid use module format.';
$lang['Services.!error.fields[][key].empty'] = 'A key is empty from the service fields.';
$lang['Services.!error.fields[][value].empty'] = 'A value is empty from the service fields.';
$lang['Services.!error.fields[][encrypted].format'] = 'A service field for encryption is in an invalid format.';
$lang['Services.!error.invoice_method.valid'] = 'You must select a valid invoice method.';
$lang['Services.!error.pricing_id.valid'] = 'You must select a valid term.';
$lang['Services.!error.date_canceled.valid'] = 'You must set a valid date to cancel this service.';
$lang['Services.!error.configoptions.valid'] = 'One of the configurable options selected is not valid for the service.';

$lang['Services.!error.status.valid'] = 'Only pending, canceled, or in review services may be deleted.';
$lang['Services.!error.service_id.has_children'] = 'This service may not be deleted because it has child services. Please cancel or delete these services and try again.';
$lang['Services.!error.prorate.format'] = "Whether to prorate must be set to 'true' or 'false'.";


// Text
$lang['Services.getStatusTypes.active'] = 'Active';
$lang['Services.getStatusTypes.canceled'] = 'Canceled';
$lang['Services.getStatusTypes.pending'] = 'Pending';
$lang['Services.getStatusTypes.suspended'] = 'Suspended';
$lang['Services.getStatusTypes.in_review'] = 'In Review';

$lang['Services.getActions.suspend'] = 'Suspend';
$lang['Services.getActions.unsuspend'] = 'Unsuspend';
$lang['Services.getActions.cancel'] = 'Cancel';
$lang['Services.getActions.schedule_cancel'] = 'Schedule Cancellation';
$lang['Services.getActions.change_renew'] = 'Change Renew Date';
$lang['Services.getActions.update_coupon'] = 'Update Coupon';
