<?php
/**
 * Language definitions for the Accounts model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Common ACH/CC errors
$lang['Accounts.!error.contact_id.exists'] = 'Invalid contact ID.';
$lang['Accounts.!error.first_name.empty'] = 'Please specify a first name.';
$lang['Accounts.!error.last_name.empty'] = 'Please specify a last name.';
$lang['Accounts.!error.state.length'] = 'State length may not exceed 3 characters.';
$lang['Accounts.!error.country.length'] = 'Country length may not exceed 3 characters.';
$lang['Accounts.!error.state.country_exists'] = 'Please select the country that matches the selected state.';
$lang['Accounts.!error.currency.format'] = 'The currency is invalid.';

// ACH errors
$lang['Accounts.!error.account.length'] = 'Please specify an account number.';
$lang['Accounts.!error.routing.empty'] = 'Please specify a routing number.';
$lang['Accounts.!error.last4.empty_ach'] = 'Please enter the last four digits of the account number.';
$lang['Accounts.!error.type.ach_format'] = 'Invalid type.';
$lang['Accounts.!error.ach_account_id.exists'] = 'Invalid account ID.';

// CC errors
$lang['Accounts.!error.number.valid'] = 'The card number given is invalid.';
$lang['Accounts.!error.expiration.empty'] = 'Please enter the credit card expiration date.';
$lang['Accounts.!error.expiration.valid'] = 'The expiration date has already lapsed.';
$lang['Accounts.!error.last4.empty_cc'] = 'Please enter the last four digits of the credit card number.';
$lang['Accounts.!error.type.cc_format'] = 'The card type is invalid or could not be determined.';
$lang['Accounts.!error.cc_account_id.exists'] = 'Invalid account ID.';


// Text
$lang['Accounts.getTypes.cc'] = 'Credit Card';
$lang['Accounts.getTypes.ach'] = 'Automated Clearing House';
$lang['Accounts.getTypes.other'] = 'Other';

$lang['Accounts.getAchTypes.checking'] = 'Checking';
$lang['Accounts.getAchTypes.savings'] = 'Savings';

$lang['Accounts.getCcTypes.amex'] = 'American Express';
$lang['Accounts.getCcTypes.bc'] = 'Bankcard';
$lang['Accounts.getCcTypes.cup'] = 'China Union Pay';
$lang['Accounts.getCcTypes.dc-cb'] = 'Diners Club Carte Blanche';
$lang['Accounts.getCcTypes.dc-er'] = 'Diners Club EnRoute';
$lang['Accounts.getCcTypes.dc-int'] = 'Diners Club International';
$lang['Accounts.getCcTypes.dc-uc'] = 'Diners Club US and Canada';
$lang['Accounts.getCcTypes.disc'] = 'Discover';
$lang['Accounts.getCcTypes.ipi'] = 'InstaPayment';
$lang['Accounts.getCcTypes.jcb'] = 'Japan Credit Bureau';
$lang['Accounts.getCcTypes.lasr'] = 'Laser';
$lang['Accounts.getCcTypes.maes'] = 'Maestro';
$lang['Accounts.getCcTypes.mc'] = 'Master Card';
$lang['Accounts.getCcTypes.solo'] = 'Solo';
$lang['Accounts.getCcTypes.switch'] = 'Switch';
$lang['Accounts.getCcTypes.visa'] = 'Visa';
$lang['Accounts.getCcTypes.other'] = 'Other';
