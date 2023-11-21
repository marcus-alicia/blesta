<?php
/**
 * Language definitions for the ReportManager model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Errors
$lang['ReportManager.!error.type.valid'] = 'Please select a valid report type.';
$lang['ReportManager.!error.format.valid'] = 'Please select a valid report format.';
$lang['ReportManager.!error.temp_dir.writable'] = 'The temp directory is not writable or the report could not be written to it.';

$lang['ReportManager.!error.name.valid'] = 'Report must have a name.';
$lang['ReportManager.!error.query.empty'] = 'Report must have a query.';
$lang['ReportManager.!error.query.valid'] = 'Only one query allowed, and it must be a SELECT query.';
$lang['ReportManager.!error.date_created.format'] = 'Date Created is an invalid format.';

// Formats
$lang['ReportManager.getformats.csv'] = 'CSV';
$lang['ReportManager.getformats.json'] = 'JSON';

$lang['ReportManager.reportfieldtypes.text'] = 'Text';
$lang['ReportManager.reportfieldtypes.select'] = 'Select';
$lang['ReportManager.reportfieldtypes.date'] = 'Date';

$lang['ReportManager.reportrequiredtypes.no'] = 'No';
$lang['ReportManager.reportrequiredtypes.yes'] = 'Yes';
$lang['ReportManager.reportrequiredtypes.custom'] = 'Custom Regex';
