<?php
/**
 * System Status main controller
 *
 * @package blesta
 * @subpackage blesta.plugins.system_status
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminMain extends SystemStatusController
{
    /**
     * @var System status values for each status
     */
    private $status_values = [
        'cron' => ['serious' => 75, 'minor' => 50],
        'cron_task_stalled' => ['serious' => 25, 'minor' => 25],
        'trial' => ['serious' => 0, 'minor' => 0],
        'invoices' => ['serious' => 30, 'minor' => 30],
        'backup' => ['serious' => 15, 'minor' => 15],
        'updates' => ['serious' => 15, 'minor' => 0],
        'docroot' => ['serious' => 10, 'minor' => 10],
        'system_dir_writable' => ['serious' => 10, 'minor' => 10],
        'log_files_owner' => ['serious' => 15, 'minor' => 15],
        'error_reporting' => ['serious' => 15, 'minor' => 15],
        'db_version' => ['serious' => 50, 'minor' => 25],
        'php_version' => ['serious' => 50, 'minor' => 25],
        'sql_version' => ['serious' => 50, 'minor' => 25],
    ];
    /**
     * @var Time (in seconds) that must pass without a task ending before we deem it stalled
     */
    private $stalled_time = 3600;


    /**
     * Load language
     */
    public function preAction()
    {
        parent::preAction();

        $this->requireLogin();

        Language::loadLang('admin_main', null, PLUGINDIR . 'system_status' . DS . 'language' . DS);
    }

    /**
     * Renders the system status widget
     */
    public function index()
    {
        // Only available via AJAX
        if (!$this->isAjax()) {
            $this->redirect($this->base_uri);
        }

        $this->uses(['License', 'Logs', 'Settings']);
        $this->components(['SettingsCollection']);

        // Set default errors
        $errors = new stdClass();

        // Set the % status of the system
        $system_status = 100;
        $one_day = 86400; // seconds in a day
        $now_timestamp = $this->Date->toTime($this->Logs->dateToUtc(date('c')));
        $icons = [
            'success' => 'fas fa-fw fa-check',
            'error' => 'fab fa-fw fa-whmcs',
            'warning' => 'fas fa-fw fa-exclamation-triangle'
        ];

        // Default cron has never run
        $errors->cron = [
            'icon' => $icons['error'],
            'message' => Language::_('AdminMain.index.cron_serious', true),
            'link' => $this->base_uri . 'settings/system/automation/',
            'link_text' => Language::_('AdminMain.index.cron_configure', true),
            'status_value' => $this->status_values['cron']['serious']
        ];
        // Default no tasks stalled
        $errors->cron_task_stalled = false;

        // Determine if the cron has run recently
        if (($cron_last_ran = $this->Logs->getSystemCronLastRun())) {
            // Assume cron ran recently
            $errors->cron = false;

            // Set cron icon to exclamation if the cron has not run within the past 24 hours
            if (($this->Date->toTime($cron_last_ran->end_date) + $one_day) < $now_timestamp) {
                $errors->cron = [
                    'icon' => $icons['warning'],
                    'message' => Language::_('AdminMain.index.cron_minor', true),
                    'status_value' => $this->status_values['cron']['minor']
                ];
            } else {
                $stalled_tasks = $this->Logs->getRunningCronTasks($this->stalled_time);

                if (!empty($stalled_tasks)) {
                    $errors->cron_task_stalled = [
                        'icon' => $icons['warning'],
                        'message' => Language::_(
                            'AdminMain.index.cron_task_stalled_minor',
                            true,
                            floor($this->stalled_time / 60)
                        ),
                        'link' => $this->base_uri . 'settings/company/automation/',
                        'link_text' => Language::_('AdminMain.index.cron_task_stalled_automation', true),
                        'status_value' => $this->status_values['cron_task_stalled']['minor']
                    ];
                }
            }
        }

        // Assume the invoices have been run recently
        $errors->invoices = false;

        // Determine if the create invoice task has actually run recently
        if (($latest_invoice_cron = $this->Logs->getCronLastRun('create_invoice'))) {
            // Set this invoice icon to exclamation if it has not run in the past 24 hours
            if (($this->Date->toTime($latest_invoice_cron->end_date) + $one_day) < $now_timestamp) {
                $errors->invoices = [
                    'icon' => $icons['warning'],
                    'message' => Language::_('AdminMain.index.invoices_minor', true),
                    'status_value' => $this->status_values['invoices']['minor']
                ];
            }
        }

        // See if the cron has run any of the backups recently
        $sftp_backup_run_recently = false;
        if (($latest_sftp_backup = $this->Logs->getCronLastRun('backups_sftp', null, true))) {
            // Set whether this backup has run in the past 7 days
            if (($this->Date->toTime($latest_sftp_backup->end_date) + 7 * $one_day) >= $now_timestamp) {
                $sftp_backup_run_recently = true;
            }
        }

        $amazon_backup_run_recently = false;
        if (($latest_amazon_backup = $this->Logs->getCronLastRun('backups_amazons3', null, true))) {
            // Set whether this backup has run in the past 7 days
            if (($this->Date->toTime($latest_amazon_backup->end_date) + 7 * $one_day) >= $now_timestamp) {
                $amazon_backup_run_recently = true;
            }
        }

        // Assume the backup has run recently
        $errors->backup = false;

        // Fetch system settings
        $system_settings = $this->SettingsCollection->fetchSystemSettings();

        // Set error with backup
        if ((!$sftp_backup_run_recently && !empty($system_settings['ftp_host'])
            && !empty($system_settings['ftp_username']) && !empty($system_settings['ftp_password'])
            )
            || (!$amazon_backup_run_recently && !empty($system_settings['amazons3_access_key'])
                && !empty($system_settings['amazons3_secret_key']) && !empty($system_settings['amazons3_bucket'])
            )
        ) {
            // Set backup error
            $errors->backup = [
                'icon' => $icons['warning'],
                'message' => Language::_('AdminMain.index.backup_minor', true),
                'status_value' => $this->status_values['backup']['minor']
            ];
        }

        // Check Root Web Directory setting is accurate according to the current web server document root
        $root_dir = isset($system_settings['root_web_dir']) ? $system_settings['root_web_dir'] : null;
        if ($root_dir != DOCROOTDIR) {
            $errors->docroot = [
                'icon' => $icons['warning'],
                'message' => Language::_('AdminMain.index.docroot_minor', true),
                'link' => $this->base_uri . 'settings/system/general/basic/',
                'link_text' => Language::_('AdminMain.index.docroot_setting', true),
                'status_value' => $this->status_values['docroot']['minor']
            ];
        }

        // Check if system directories are writable
        $dirs_writable = ['temp_dir', 'uploads_dir', 'log_dir'];
        foreach ($dirs_writable as $dir) {
            if (!isset($system_settings[$dir])
                || !is_dir($system_settings[$dir])
                || !is_writable($system_settings[$dir])
            ) {
                $errors->system_dir_writable = [
                    'icon' => $icons['warning'],
                    'message' => Language::_('AdminMain.index.system_dir_writable_minor', true),
                    'link' => $this->base_uri . 'settings/system/general/basic/',
                    'link_text' => Language::_('AdminMain.index.system_dir_writable_setting', true),
                    'status_value' => $this->status_values['system_dir_writable']['minor']
                ];
                break;
            }
        }

        // Assume the log dir is owned by the web user
        $errors->log_files_owner = false;

        // Get all settings
        $settings = $this->SettingsCollection->fetchSystemSettings($this->Settings);
        if (isset($settings['log_dir']) && is_dir($settings['log_dir'])) {
            try {
                // Attempt to get the current user ID by creating a temp file and checking its owner
                $temp_file = tempnam(sys_get_temp_dir(), 'TMP');
                $web_uid = fileowner($temp_file);
                unlink($temp_file);
            } catch (Exception $ex) {
                // Get the current user ID from posix if available, otherwise use getmyuid() to get the owner of
                // the curent script file (admin_main.php)
                $web_uid = function_exists('posix_getuid') ? posix_getuid() : getmyuid();
            }

            // Check the current user against each log file's owner
            $cron_uid = null;
            foreach (scandir($settings['log_dir']) as $file_name) {
                if ($file_name == '..') {
                    continue;
                }

                if (strpos($file_name, 'cron') === false) {
                    // Ensure all regular log files are owned by the web user
                    if ($web_uid !== fileowner($settings['log_dir'] . $file_name)) {
                        $errors->log_files_owner = [
                            'icon' => $icons['warning'],
                            'message' => Language::_('AdminMain.index.log_files_owner_minor', true),
                            'status_value' => $this->status_values['log_files_owner']['minor']
                        ];
                        break;
                    }
                } else {
                    // Ensure that all cron log files are owned by the same user
                    if ($cron_uid === null) {
                        $cron_uid = fileowner($settings['log_dir'] . $file_name);
                        continue;
                    }

                    if ($cron_uid !== fileowner($settings['log_dir'] . $file_name)) {
                        $errors->log_files_owner = [
                            'icon' => $icons['warning'],
                            'message' => Language::_('AdminMain.index.log_files_owner_minor', true),
                            'status_value' => $this->status_values['log_files_owner']['minor']
                        ];
                        break;
                    }
                }
            }
        }

        // Assume this is not a trial
        $errors->trial = false;

        $license_data = $this->License->getLocalData();

        // Check whether this is actually a trial
        if ($license_data &&
            !empty($license_data['custom']['license_type']) &&
            $license_data['custom']['license_type'] == 'trial' &&
            !empty($license_data['custom']['cancellation_date'])
        ) {
            // Set trial notice
            $errors->trial = [
                'icon' => $icons['warning'],
                'message' => Language::_(
                    'AdminMain.index.trial_minor',
                    true,
                    $this->Date->cast($license_data['custom']['cancellation_date'])
                ),
                'link' => 'https://account.blesta.com/order/',
                'link_text' => Language::_('AdminMain.index.trial_buy', true),
                'status_value' => $this->status_values['trial']['minor']
            ];
        }

        // Check support and updates
        if (array_key_exists('updates', $license_data) && $license_data['updates'] !== false) {
            $expired = false;
            if ($license_data['updates'] !== null) {
                $expired = strtotime($license_data['updates']) < time();
            }

            $errors->updates = [
                'icon' => $icons[($expired ? 'warning' : 'success')],
                'message' => ($license_data['updates'] === null
                    ? Language::_('AdminMain.index.updates_forever', true)
                    : Language::_(
                        'AdminMain.index.updates_' . ($expired ? 'serious' : 'minor'),
                        true,
                        $this->Date->cast($license_data['updates'])
                    )
                ),
                'status_value' => $this->status_values['updates'][($expired ? 'serious' : 'minor')]
            ];

            if ($expired) {
                $errors->updates['link'] = 'http://www.blesta.com/support-and-updates/';
                $errors->updates['link_text'] = Language::_('AdminMain.index.updates_buy', true);
            }
        }

        // Check if database version is equal to the latest mapped version
        Configure::load('mappings', COMPONENTDIR . 'upgrades' . DS . 'tasks' . DS);

        $current_db_version = isset($system_settings['database_version']) ? $system_settings['database_version'] : null;
        $db_mapping = Configure::get('Upgrade.mappings');
        $latest_db_version = is_array($db_mapping) ? end($db_mapping) : null;

        if (version_compare($current_db_version, $latest_db_version, '<')) {
            $errors->db_version = [
                'icon' => $icons['warning'],
                'message' => Language::_('AdminMain.index.db_version_serious', true),
                'link' => $this->base_uri . 'upgrade/',
                'link_text' => Language::_('AdminMain.index.db_version_upgrade', true),
                'status_value' => $this->status_values['db_version']['serious']
            ];
        }

        // Check if the PHP version meets the minimum system requirements
        if (version_compare(PHP_VERSION, '7.2.0', '<')) {
            $errors->php_version = [
                'icon' => $icons['warning'],
                'message' => Language::_('AdminMain.index.php_version_serious', true),
                'link' => 'https://docs.blesta.com/display/user/Requirements',
                'link_text' => Language::_('AdminMain.index.php_version_requirements', true),
                'status_value' => $this->status_values['php_version']['serious']
            ];
        }

        // Check if error reporting or system.debug are enabled
        if (error_reporting() !== 0 || Configure::get('System.debug')) {
            $errors->error_reporting = [
                'icon' => $icons['warning'],
                'message' => Language::_('AdminMain.index.error_reporting', true),
                'status_value' => $this->status_values['error_reporting']['serious']
            ];
        }

        // Check if the MySQL/MariaDB version meets the minimum system requirements
        if (!isset($this->Record)) {
            Loader::loadComponents($this, ['Record']);
        }

        $pdo = $this->Record->connect();
        $server = (object) $pdo->query("SHOW VARIABLES like '%version%'")->fetchAll(PDO::FETCH_KEY_PAIR);

        if (
            strpos($server->version_comment, 'MySQL') !== false
            && version_compare($server->version, '5.6.0', '<')
        ) {
            $errors->sql_version = [
                'icon' => $icons['warning'],
                'message' => Language::_('AdminMain.index.sql_mysql_version_serious', true),
                'link' => 'https://docs.blesta.com/display/user/Requirements',
                'link_text' => Language::_('AdminMain.index.sql_mysql_version_requirements', true),
                'status_value' => $this->status_values['sql_version']['serious']
            ];
        }

        if (
            strpos($server->version_comment, 'MariaDB') !== false
            && version_compare($server->version, '10.0.5', '<')
        ) {
            $errors->sql_version = [
                'icon' => $icons['warning'],
                'message' => Language::_('AdminMain.index.sql_mariadb_version_serious', true),
                'link' => 'https://docs.blesta.com/display/user/Requirements',
                'link_text' => Language::_('AdminMain.index.sql_mariadb_version_requirements', true),
                'status_value' => $this->status_values['sql_version']['serious']
            ];
        }

        // Subtract system status values
        foreach ($errors as $error) {
            if (!$error) {
                continue;
            }
            $system_status -= $error['status_value'];
        }

        $this->set('errors', $errors);
        $this->set('system_status', max(0, $system_status));
        $this->set('health_status', $this->getStatusLanguage($system_status));

        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) ? false : null);
    }

    /**
     * Renders the system status widget for the billing page
     */
    public function billing()
    {
        // Fetch content from the index action
        $this->action = 'index';
        $this->set('billing', 'true');
        return $this->index();
    }

    /**
     * Settings
     */
    public function settings()
    {
        // Only available via AJAX
        if (!$this->isAjax()) {
            $this->redirect($this->base_uri);
        }

        return $this->renderAjaxWidgetIfAsync(false);
    }

    /**
     * Retrieves the system status language to use based on the overall status
     */
    private function getStatusLanguage($system_status)
    {
        if ($system_status <= 50) {
            return Language::_('AdminMain.index.health_poor', true);
        } elseif ($system_status <= 75) {
            return Language::_('AdminMain.index.health_fair', true);
        } elseif ($system_status <= 95) {
            return Language::_('AdminMain.index.health_good', true);
        }
        return Language::_('AdminMain.index.health_excellent', true);
    }
}
