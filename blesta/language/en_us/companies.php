<?php
/**
 * Language definitions for the Companies model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

$lang['Companies.!error.company_id.in_use'] = 'That company is currently in use and may not be deleted.';
$lang['Companies.!error.company_id.exists'] = 'Invalid company ID.';
$lang['Companies.!error.name.empty'] = 'Please enter a name.';
$lang['Companies.!error.name.length'] = 'Name length may not exceed 64 characters.';
$lang['Companies.!error.hostname.valid'] = 'Please enter a valid hostname.';
$lang['Companies.!error.hostname.length'] = 'The hostname may not exceed 255 characters in length.';
$lang['Companies.!error.hostname.unique'] = 'That hostname has already been taken by another company.';
$lang['Companies.!error.phone.length'] = 'Phone length may not exceed 64 characters.';
$lang['Companies.!error.fax.length'] = 'Fax length may not exceed 64 characters.';
$lang['Companies.!error.private_key_passphrase.valid'] = 'The passphrase you entered is invalid.';
$lang['Companies.!error.quota.allowed'] = 'There are no addon company licenses available. Please log in to your account at account.blesta.com or contact sales to purchase additional company licenses.';

$lang['Companies.!error.inv_format.format'] = 'Invoice Format can not conflict with the Invoice Draft Format.';
$lang['Companies.!error.inv_format.contains'] = 'Invoice Format must contain {num}.';
$lang['Companies.!error.inv_draft_format.format'] = 'Invoice Draft Format can not conflict with the Invoice Format.';
$lang['Companies.!error.inv_draft_format.contains'] = 'Invoice Draft Format must contain {num}.';
$lang['Companies.!error.inv_start.number'] = 'Invoice Start Value must be a number.';
$lang['Companies.!error.inv_increment.number'] = 'Invoice Increment Value must be a number.';
$lang['Companies.!error.inv_pad_size.number'] = 'Invoice Padding Size must be a number.';
$lang['Companies.!error.inv_pad_str.length'] = 'Invoice Padding Character must be a single character.';
$lang['Companies.!error.inv_proforma_format.format'] = 'Pro Forma Invoice Format can not conflict with the Draft Invoice Format.';
$lang['Companies.!error.inv_proforma_format.contains'] = 'Pro Forma Invoice Format must contain {num}.';
$lang['Companies.!error.inv_proforma_start.number'] = 'Pro Forma Invoice Start Value must be a number.';
