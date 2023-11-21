<?php
/**
 * Language definitions for the Taxes model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Errors
$lang['Taxes.!error.company_id.exists'] = 'Invalid company ID.';
$lang['Taxes.!error.level.format'] = 'The tax level must be a number.';
$lang['Taxes.!error.level.length'] = 'The tax level length may not exceed 2 characters.';
$lang['Taxes.!error.name.length'] = 'The tax name length may not exceed 64 characters.';
$lang['Taxes.!error.amount.format'] = 'Amount must be a number.';
$lang['Taxes.!error.amount.positive'] = 'Amount must be positive.';
$lang['Taxes.!error.type.format'] = 'Invalid tax type.';
$lang['Taxes.!error.country.valid'] = 'Country is not a valid ISO 3166-1 country code.';
$lang['Taxes.!error.state.valid'] = 'State is not a valid ISO 3166-2 subdivision code.';
$lang['Taxes.!error.status.format'] = 'Invalid status.';
$lang['Taxes.!error.tax_id.exists'] = 'Invalid tax ID.';

// Statuses
$lang['Taxes.getTaxStatus.active'] = 'Active';
$lang['Taxes.getTaxStatus.inactive'] = 'Inactive';

// Types
$lang['Taxes.getTaxTypes.inclusive_calculated'] = 'Inclusive';
$lang['Taxes.getTaxTypes.inclusive'] = 'Inclusive (Additive)';
$lang['Taxes.getTaxTypes.exclusive'] = 'Exclusive';
