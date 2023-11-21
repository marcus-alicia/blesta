<?php
/**
 * Language definitions for the Backup model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Errors
$lang['Backup.!error.driver.support'] = 'The database driver is not supported.';
$lang['Backup.!error.temp_dir.writable'] = 'The temp directory is not writable.';
$lang['Backup.!error.ftp_failed'] = 'The SFTP backup failed.';
$lang['Backup.!error.amazons3_failed'] = 'The AmazonS3 backup failed.';

$lang['Backup.frequencies.never'] = 'Never';
$lang['Backup.frequencies.hour'] = 'Hour';
$lang['Backup.frequencies.hours'] = '%1$s Hours'; // %1$s is the number of hours
$lang['Backup.frequencies.day'] = 'Day';
$lang['Backup.frequencies.days'] = '%1$s Days'; // %1$s is the number of days
