<?php
/**
 * Language definitions for the Coupons model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Amount Types
$lang['Coupons.getAmountTypes.amount'] = 'Amount';
$lang['Coupons.getAmountTypes.percent'] = 'Percent';

// Coupon rules
$lang['Coupons.!error.code.empty'] = 'Please enter a coupon code.';
$lang['Coupons.!error.code.length'] = 'Coupon code length may not exceed 64 characters.';
$lang['Coupons.!error.code.unique'] = 'The coupon code is currently set on a different coupon and may not be reused.';
$lang['Coupons.!error.company_id.exists'] = 'Invalid company ID given.';
$lang['Coupons.!error.package_group_id.length'] = 'Package group ID may not exceed 10 characters.';
$lang['Coupons.!error.used_qty.format'] = 'Used quantity must be a number.';
$lang['Coupons.!error.used_qty.length'] = 'Used quantity length may not exceed 10 characters.';
$lang['Coupons.!error.max_qty.format'] = 'Max quantity must be a number.';
$lang['Coupons.!error.max_qty.length'] = 'Max quantity length may not exceed 10 characters.';
$lang['Coupons.!error.max_qty.exceeded'] = 'The used quantity may not exceed the max quantity.';
$lang['Coupons.!error.start_date.format'] = 'Invalid start date format.';
$lang['Coupons.!error.end_date.format'] = 'Invalid end date format.';
$lang['Coupons.!error.status.format'] = 'Invalid status.';
$lang['Coupons.!error.recurring.format'] = 'Recurring must be a number.';
$lang['Coupons.!error.recurring.length'] = 'Recurring length may not exceed 1 character.';
$lang['Coupons.!error.limit_recurring.format'] = 'Limit Recurring must be a number.';
$lang['Coupons.!error.limit_recurring.length'] = 'Limit Recurring length may not exceed 1 character.';
$lang['Coupons.!error.apply_package_options.format'] = 'Whether the coupon applies to configurable options must be set to 1 or 0.';
$lang['Coupons.!error.internal_use_only.format'] = 'Whether the coupon is for internal use only must be set to 1 or 0.';
$lang['Coupons.!error.coupon_id.exists'] = 'Invalid coupon ID.';

// Coupon Package rules
$lang['Coupons.!error.packages[].exists'] = 'At least one of the packages to which you are assigning this coupon are invalid.';

// Coupon Amounts rules
$lang['Coupons.!error.amounts.exists'] = 'Only one currency of each type may apply as a discount to this coupon.';
$lang['Coupons.!error.amounts[][currency].length'] = 'The currency code must be 3 characters.';
$lang['Coupons.!error.amounts[][amount].format'] = 'Each discount value must be a number.';
$lang['Coupons.!error.amounts[][amount].positive'] = 'Each discount value must be positive.';
$lang['Coupons.!error.amounts[][type].format'] = 'Invalid amount type.';
