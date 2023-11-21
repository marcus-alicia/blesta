<?php

/**
 * Backup
 *
 * Creates and delivery backups of the database.
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Backup extends AppModel
{
    /**
     * Initialize Backup
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['backup']);
    }

    /**
     * Creates a backup and forces a download of it
     */
    public function download()
    {
        $file = $this->buildDump();

        // If any errors occured building the dump, don't attempt to stream the file
        if ($this->Input->errors()) {
            return;
        }

        if (!isset($this->Download)) {
            Loader::loadComponents($this, ['Download']);
        }
        $this->Download->streamFile($file);

        // Remove the download file, no need to keep it around
        unlink($file);
        exit;
    }

    /**
     * Creates and sends a backup to the SFTP and/or AmazonS3 account configured for this system
     *
     * @param string $type The type of backup to send (i.e. "ftp", "amazons3", "all") (optional, default "all")
     */
    public function sendBackup($type = 'all')
    {
        Loader::loadComponents($this, ['SettingsCollection', 'Security', 'Net']);
        $settings = $this->SettingsCollection->fetchSystemSettings();

        $file = null;
        // Build the dump if we have a place to send it
        if ((
                ($type == 'all' || $type == 'ftp') && isset($settings['ftp_host']) && !empty($settings['ftp_host'])
            ) || (
                ($type == 'all' || $type == 'amazons3')
                && isset($settings['amazons3_access_key'])
                && !empty($settings['amazons3_access_key'])
            )
        ) {
            $file = $this->buildDump();
        }

        // Only proceed if we have a backup file
        if (!$file) {
            return;
        }

        $errors = [];

        // SFTP the file
        if (($type == 'all' || $type == 'ftp') && isset($settings['ftp_host']) && !empty($settings['ftp_host'])) {
            $this->Net_SFTP = $this->Security->create('Net', 'SFTP', [$settings['ftp_host'], $settings['ftp_port']]);
            $uploaded = false;

            // Attempt to login to test the connection and navigate to the given path. Show success or error
            if ($this->Net_SFTP->login($settings['ftp_username'], $settings['ftp_password'])
                && $this->Net_SFTP->chdir($settings['ftp_path'])
            ) {
                // Upload the file
                $mode = constant(get_class($this->Net_SFTP) . '::SOURCE_LOCAL_FILE');
                if ($this->Net_SFTP->put(basename($file), $file, $mode)) {
                    $uploaded = true;
                }
            }

            if (!$uploaded) {
                $errors = ['ftp_failed' => $this->_('Backup.!error.ftp_failed', true)];
            }
        }

        // Upload the file to Amazon S3
        if (($type == 'all' || $type == 'amazons3')
            && isset($settings['amazons3_access_key'])
            && !empty($settings['amazons3_access_key'])
        ) {
            $this->AmazonS3 = $this->Net->create(
                'AmazonS3',
                [
                    $settings['amazons3_access_key'],
                    $settings['amazons3_secret_key'],
                    true,
                    isset($settings['amazons3_region']) ? $settings['amazons3_region'] : null
                ]
            );
            $connection = $this->AmazonS3->getBucket($settings['amazons3_bucket'], null, null, 1);
            $uploaded = false;

            // Upload the file if the connection is good
            if ($connection !== false) {
                if ($this->AmazonS3->upload($file, $settings['amazons3_bucket'])) {
                    $uploaded = true;
                }
            }

            if (!$uploaded) {
                $errors = array_merge($errors, ['amazons3_failed' => $this->_('Backup.!error.amazons3_failed', true)]);
            }
        }

        if (!empty($errors)) {
            $this->Input->setErrors([$errors]);
        }

        // Remove the backup file from the disk
        unlink($file);
    }

    /**
     * Returns an array of frequencies that may be set for backup scheduling
     *
     * @return array An array of key/value pairs where each key is a number of
     *  hours and each value is a string representing that number of hours.
     */
    public function frequencies()
    {
        $frequency = ['never' => Language::_('Backup.frequencies.never', true)];

        // Build hours 1 through 12
        for ($i = 1; $i <= 12;) {
            $frequency[$i] = Language::_(($i == 1 ? 'Backup.frequencies.hour' : 'Backup.frequencies.hours'), true, $i);

            if ($i == 1) {
                $i++;
            } elseif ($i <= 12) {
                $i += 2;
            }
        }

        // Build days 1 through 7 (with bonus 1.5 days entry)
        for ($i = 24, $j = 12; $i <= 168; $i += $j) {
            if ($i > 36) {
                $j = 24;
            }
            $frequency[$i] = Language::_(
                ($i == 24 ? 'Backup.frequencies.day' : 'Backup.frequencies.days'),
                true,
                round($i / 24, 2)
            );
        }
        return $frequency;
    }

    /**
     * Builds a dump of the database, returns the path to the file
     *
     * @return string The full path to the database dump
     */
    private function buildDump()
    {
        $db_info = Configure::get('Database.profile');

        Loader::loadComponents($this, ['SettingsCollection']);
        $temp = $this->SettingsCollection->fetchSystemSetting(null, 'temp_dir');
        $temp_dir = (isset($temp['value']) ? $temp['value'] : null);

        $this->Input->setRules($this->getRules());

        $vars = [
            'temp_dir' => $temp_dir,
            'db_info' => $db_info
        ];

        if ($this->Input->validates($vars)) {
            // ISO 8601
            $file = $db_info['database'] . '_' . date('Y-m-d\THis\Z');
            $file_name = $temp_dir . $file . '.sql';

            // Set default port, if port not provided by database profile
            $db_info['port'] = isset($db_info['port']) ? $db_info['port'] : '3306';

            exec(
                'mysqldump --host=' . escapeshellarg($db_info['host'])
                . ' --port=' . escapeshellarg($db_info['port'])
                . ' --user=' . escapeshellarg($db_info['user'])
                . ' --password=' . escapeshellarg($db_info['pass'])
                . ' ' . escapeshellarg($db_info['database']) . ' > '
                . escapeshellarg($file_name)
            );

            // GZip the file if possible
            if (function_exists('gzwrite')) {
                $chunk_size = 4096;
                $compress_file_name = $file_name . '.gz';
                // Compress as much as possible
                $gz = gzopen($compress_file_name, 'wb9');
                $fh = fopen($file_name, 'rb');

                // Read from the original and write in chunks to preserve memory
                while (!feof($fh)) {
                    $data = fread($fh, $chunk_size);
                    if ($data) {
                        gzwrite($gz, $data);
                    }
                }
                unset($data);
                $compressed = gzclose($gz);

                // Remove the original data file
                if ($compressed) {
                    unlink($file_name);
                    return $compress_file_name;
                }
            }

            return $file_name;
        }
    }

    /**
     * Returns all rules set for building database dumps
     *
     * @return array An array of rules to check
     */
    private function getRules()
    {
        $rules = [
            'temp_dir' => [
                'writable' => [
                    'rule' => 'is_writable',
                    'message' => $this->_('Backup.!error.temp_dir.writable')
                ]
            ],
            'db_info[driver]' => [
                'support' => [
                    'rule' => ['compares', '==', 'mysql'],
                    'message' => $this->_('Backup.!error.driver.support')
                ]
            ]
        ];

        return $rules;
    }
}
