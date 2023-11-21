<?php
/**
 * Language definitions for the Quotations model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Quotation errors
$lang['Quotations.!error.quotation_add.failed'] = 'This quotation could not be created. Please try again.';
$lang['Quotations.!error.id.quotation_invoiced'] = 'This quote has been invoiced and cannot be edited.';
$lang['Quotations.!error.id_format.empty'] = 'No ID format set for quotations.';
$lang['Quotations.!error.id_format.length'] = 'The ID format for quotations may not exceed 64 characters.';
$lang['Quotations.!error.id_value.valid'] = 'Unable to determine quotation ID value.';
$lang['Quotations.!error.client_id.exists'] = 'Invalid client ID.';
$lang['Quotations.!error.staff_id.exists'] = 'Invalid staff ID.';
$lang['Quotations.!error.title.empty'] = 'Please enter a title.';
$lang['Quotations.!error.title.length'] = 'The Title for quotations may not exceed 255 characters.';
$lang['Quotations.!error.date_created.format'] = 'The creation date is in an invalid date format.';
$lang['Quotations.!error.date_expires.format'] = 'The expiration date is in an invalid date format.';
$lang['Quotations.!error.date_expires.after_created'] = 'The expiration due must be on or after the creation billed.';
$lang['Quotations.!error.status.format'] = 'Invalid status.';
$lang['Quotations.!error.currency.length'] = 'The currency code must be 3 characters in length.';
$lang['Quotations.!error.status.valid'] = 'The quotation must be either approved or pending to be invoiced.';

$lang['Quotations.!error.first_due_date.format'] = 'The first due date is in an invalid date format.';
$lang['Quotations.!error.second_due_date.format'] = 'The second due date is in an invalid date format.';
$lang['Quotations.!error.percentage_due.format'] = 'The percentage due must be a number.';
$lang['Quotations.!error.percentage_due.valid'] = 'The percentage due must be a number greater than zero and less or equal to 100.';


// Quotation line errors
$lang['Quotations.!error.lines[][id].exists'] = 'Invalid line item ID.';
$lang['Quotations.!error.lines[][description].empty'] = 'Please enter a line item description.';
$lang['Quotations.!error.lines[][qty].minimum'] = 'Please enter a quantity of 1 or more.';
$lang['Quotations.!error.lines[][amount].format'] = 'The unit cost must be a number.';
$lang['Quotations.!error.lines[][tax].format'] = "Line item tax must be a 'true' or 'false'";


$lang['Quotations.getstatuses.draft'] = 'Draft';
$lang['Quotations.getstatuses.approved'] = 'Approved';
$lang['Quotations.getstatuses.pending'] = 'Pending';
$lang['Quotations.getstatuses.expired'] = 'Expired';
$lang['Quotations.getstatuses.invoiced'] = 'Invoiced';
$lang['Quotations.getstatuses.dead'] = 'Dead';
$lang['Quotations.getstatuses.lost'] = 'Lost';
