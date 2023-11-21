<?php
/**
 * Language definitions for the Admin System Automation settings controller/views
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Success messages
$lang['AdminSystemBackup.!success.backup_updated'] = 'The Backup settings were successfully updated!';
$lang['AdminSystemBackup.!success.sftp_test'] = 'SFTP connection was successful!';
$lang['AdminSystemBackup.!success.amazons3_test'] = 'The AmazonS3 connection was successful!';
$lang['AdminSystemBackup.!success.backup_uploaded'] = 'The backup was successfully sent to the configured remote services!';

// Error messages
$lang['AdminSystemBackup.!error.sftp_test'] = 'The SFTP connection failed! Please check your settings and try again.';
$lang['AdminSystemBackup.!error.amazons3_test'] = 'The AmazonS3 connection failed! Please check your settings and try again. Note that connection details are case-sensitive.';
$lang['AdminSystemBackup.!error.backup_frequency'] = 'Invalid backup frequency.';


// Tooltips
$lang['AdminSystemBackup.!tooltip.ftp_host'] = 'The fully-qualified domain name of the backup server (e.g. "backup.domain.com").';
$lang['AdminSystemBackup.!tooltip.ftp_port'] = 'The port number, usually 22. This is the same port number as is used for SSH.';
$lang['AdminSystemBackup.!tooltip.ftp_username'] = 'The username for the FTP account.';
$lang['AdminSystemBackup.!tooltip.ftp_password'] = 'The password for the FTP account.';
$lang['AdminSystemBackup.!tooltip.ftp_path'] = 'The destination path where backups should be stored on the remote server (e.g. "/backup/").';
$lang['AdminSystemBackup.!tooltip.ftp_rate'] = 'The frequency interval to perform this backup.';

$lang['AdminSystemBackup.!tooltip.amazons3_region'] = 'The Amazon S3 region at which to store backups.';
$lang['AdminSystemBackup.!tooltip.amazons3_access_key'] = 'The Amazon S3 account Access Key.';
$lang['AdminSystemBackup.!tooltip.amazons3_secret_key'] = 'The Amazon S3 account Secret Key.';
$lang['AdminSystemBackup.!tooltip.amazons3_bucket'] = 'The case-sensitive name of the Amazon S3 bucket under which to store backups.';
$lang['AdminSystemBackup.!tooltip.amazons3_rate'] = 'The frequency interval to perform this backup.';


// FTP backup settings
$lang['AdminSystemBackup.ftp.page_title'] = 'Settings > System > Backup > Secure FTP';
$lang['AdminSystemBackup.ftp.boxtitle_backup'] = 'Secure FTP';

$lang['AdminSystemBackup.ftp.field.test'] = 'Test These Settings';
$lang['AdminSystemBackup.ftp.field.ftp_host'] = 'Hostname';
$lang['AdminSystemBackup.ftp.field.ftp_port'] = 'Port';
$lang['AdminSystemBackup.ftp.field.ftp_username'] = 'Username';
$lang['AdminSystemBackup.ftp.field.ftp_password'] = 'Password';
$lang['AdminSystemBackup.ftp.field.ftp_path'] = 'Path';
$lang['AdminSystemBackup.ftp.field.ftp_rate'] = 'Backup Every';
$lang['AdminSystemBackup.ftp.field.backupsubmit'] = 'Update Settings';


// Amazon S3 backup settings
$lang['AdminSystemBackup.amazon.page_title'] = 'Settings > System > Backup > Amazon S3';
$lang['AdminSystemBackup.amazon.boxtitle_backup'] = 'Amazon S3';

$lang['AdminSystemBackup.amazon.field.test'] = 'Test These Settings';
$lang['AdminSystemBackup.amazon.field.amazons3_region'] = 'Region';
$lang['AdminSystemBackup.amazon.field.amazons3_accesskey'] = 'Access Key';
$lang['AdminSystemBackup.amazon.field.amazons3_secretkey'] = 'Secret Key';
$lang['AdminSystemBackup.amazon.field.amazons3_bucket'] = 'Bucket';
$lang['AdminSystemBackup.amazon.field.amazons3_rate'] = 'Backup Every';
$lang['AdminSystemBackup.amazon.field.backupsubmit'] = 'Update Settings';


// On Demand
$lang['AdminSystemBackup.index.page_title'] = 'Settings > System > Backup > On Demand';
$lang['AdminSystemBackup.index.boxtitle_index'] = 'On Demand';
$lang['AdminSystemBackup.index.field_uploadbackup'] = 'Force Offsite Backup';
$lang['AdminSystemBackup.index.field_downloadbackup'] = 'Download Backup';
$lang['AdminSystemBackup.index.text_note'] = 'Here you can download a backup of your Blesta database to your computer or automatically upload a backup to your configured SFTP and/or Amazon S3 server.';
