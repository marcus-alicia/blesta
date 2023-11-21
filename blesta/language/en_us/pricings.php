<?php
/**
 * Language definitions for the Pricings model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Pricing errors
$lang['Pricings.!error.term.format'] = 'Term must be a number.';
$lang['Pricings.!error.term.length'] = 'Term length may not exceed 5 characters.';
$lang['Pricings.!error.term.valid'] = 'The term must be greater than 0.';
$lang['Pricings.!error.period.format'] = 'Invalid period type.';
$lang['Pricings.!error.price.format'] = 'Price must be a number.';
$lang['Pricings.!error.price_renews.format'] = 'Renewal price must be a number.';
$lang['Pricings.!error.price_renews.valid'] = 'Renewal price cannot be set for a one time period.';
$lang['Pricings.!error.price_transfer.format'] = 'Renewal price must be a number.';
$lang['Pricings.!error.setup_fee.format'] = 'Setup fee must be a number.';
$lang['Pricings.!error.cancel_fee.format'] = 'Cancel fee must be a number.';
$lang['Pricings.!error.currency.format'] = 'Currency code must be 3 characters.';
$lang['Pricings.!error.term.in_use'] = 'Term cannot be updated in a pricing that is in use.';
$lang['Pricings.!error.period.in_use'] = 'Period cannot be updated in a pricing that is in use.';
$lang['Pricings.!error.currency.in_use'] = 'Currency cannot be updated in a pricing that is in use.';

// Periods singular
$lang['Pricings.getPeriods.day'] = 'Day';
$lang['Pricings.getPeriods.week'] = 'Week';
$lang['Pricings.getPeriods.month'] = 'Month';
$lang['Pricings.getPeriods.year'] = 'Year';
$lang['Pricings.getPeriods.onetime'] = 'One time';

// Periods plural
$lang['Pricings.getPeriods.day_plural'] = 'Days';
$lang['Pricings.getPeriods.week_plural'] = 'Weeks';
$lang['Pricings.getPeriods.month_plural'] = 'Months';
$lang['Pricings.getPeriods.year_plural'] = 'Years';
$lang['Pricings.getPeriods.onetime_plural'] = 'One time';
