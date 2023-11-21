<?php
/**
 * Language definitions for the Package Groups model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

$lang['PackageGroups.!error.names.format'] = 'The package group names are in an invalid format.';
$lang['PackageGroups.!error.names.empty_name'] = 'Please specify a package group name for each language.';
$lang['PackageGroups.!error.names.empty_lang'] = 'Please specify a package group language code for each name.';
$lang['PackageGroups.!error.descriptions.format'] = 'The package group descriptions are in an invalid format.';
$lang['PackageGroups.!error.descriptions.empty_lang'] = 'Please specify a package group language code for each description.';
$lang['PackageGroups.!error.type.format'] = 'Invalid group type.';
$lang['PackageGroups.!error.company_id.exists'] = 'Invalid company ID.';
$lang['PackageGroups.!error.parents.format'] = 'At least one parent group ID given is a non-standard group unavailable for use as a parent.';
$lang['PackageGroups.!error.hidden.format'] = 'Whether this group should be hidden in the interface must be set to 1 or 0.';
$lang['PackageGroups.!error.allow_upgrades.format'] = 'Whether packages within this group can be upgraded/downgraded must be set to 1 or 0.';


// Group types
$lang['PackageGroups.gettypes.standard'] = 'Standard';
$lang['PackageGroups.gettypes.addon'] = 'Add-on';
