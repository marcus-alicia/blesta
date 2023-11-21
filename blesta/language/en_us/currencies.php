<?php
/**
 * Language definitions for the Currencies model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

$lang['Currencies.!error.code.length'] = 'The currency code must be 3 alpha characters as defined in ISO 4217.';
$lang['Currencies.!error.code.exists'] = 'A currency with code %1$s and company ID %2$s is already taken.'; // %1$s is the currency code, %2$s is the company ID number
$lang['Currencies.!error.company_id.exists'] = 'Invalid company ID.';
$lang['Currencies.!error.format.format'] = 'Invalid currency format.';
$lang['Currencies.!error.precision.format'] = 'The currency precision must be a number between 0 and 4, inclusive.';
$lang['Currencies.!error.prefix.length'] = 'The currency prefix may not exceed 10 characters in length.';
$lang['Currencies.!error.suffix.length'] = 'The currency suffix may not exceed 10 characters in length.';
$lang['Currencies.!error.exchange_rate.format'] = 'The exchange rate must be a number.';
$lang['Currencies.!error.exchange_updated.format'] = 'The exchange updated date is in an invalid date format.';
$lang['Currencies.!error.currency_code.in_use'] = 'The currency %1$s is currently in use and cannot be deleted.'; // %1$s is the currency code
$lang['Currencies.!error.currency_code.is_default'] = 'The currency %1$s is the default currency and cannot be deleted.'; // %1$s is the currency code

$lang['Currencies.!error.processor.invalid'] = 'Invalid exchange rate processor.';
$lang['Currencies.!error.processor.empty'] = 'No exchange rate processor has been set.';
