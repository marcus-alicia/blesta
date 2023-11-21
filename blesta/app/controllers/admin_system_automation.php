<?php

/**
 * Admin System Automation Settings
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminSystemAutomation extends AppController
{
    /**
     * Pre-action setup method that is called before the index method, or the set controller action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        $this->uses(['Logs', 'Navigation', 'Settings']);
        $this->components(['SettingsCollection']);
        $this->helpers(['DataStructure']);

        $this->ArrayHelper = $this->DataStructure->create('Array');

        Language::loadLang('admin_system_automation');

        // Set the left nav for all settings pages to settings_leftnav
        $this->set(
            'left_nav',
            $this->partial('settings_leftnav', ['nav' => $this->Navigation->getSystem($this->base_uri)])
        );
    }

    /**
     * Automation settings
     */
    public function index()
    {
        $vars = [];
        $error = false;

        // Update the cron key
        if (!empty($this->post)) {
            // Check that a non-empty string was given
            if (empty($this->post['cron_key'])) {
                // Error, a key must be set
                $vars = (object) $this->post;
                $error = true;
                $this->setMessage('error', Language::_('AdminSystemAutomation.!error.empty_cron_key', true));
            } else {
                // Success, update cron key
                $this->Settings->setSetting('cron_key', $this->post['cron_key'], true);
                $this->flashMessage('message', Language::_('AdminSystemAutomation.!success.cron_key', true));
                $this->redirect($this->base_uri . 'settings/system/automation/');
            }
        }

        // Set the time that the cron has last run
        if (($cron_last_ran = $this->Logs->getSystemCronLastRun())) {
            $cron_last_ran = $cron_last_ran->end_date;
        }

        // Set cron icon to active if the cron has run within the past 24 hours
        $icon = 'exclamation';
        if ($cron_last_ran !== false
            && (($this->Date->toTime($cron_last_ran) + 86400) > $this->Date->toTime($this->Logs->dateToUtc(date('c'))))
        ) {
            $icon = 'active';
        }

        $system_settings = $this->SettingsCollection->fetchSystemSettings();

        // Get the cron key
        if (!isset($system_settings['cron_key'])) {
            $cron_key = $this->createCronKey();
        } else {
            $cron_key = $system_settings['cron_key'];
        }

        // Set current cron key
        if (empty($vars)) {
            $vars = new stdClass();
            $vars->cron_key = $cron_key;
        }

        // Show the cron key by default if there is an error
        $this->set('show_cron_key', $error);
        $this->set('vars', $vars);
        $this->set('cron_icon', $icon);
        $this->set('cron_last_ran', $cron_last_ran);
        $this->set('cron_command', $this->createCronCommand());
    }

    /**
     * Try to get the path to the php excecutable
     *
     * @param bool $windows Whether the OS being used is Windows
     * @return string The path to php
     */
    private function getPathToPhp($windows)
    {
        // Get path from the PHP_BINARY constant
        if (defined('PHP_BINARY') && $dir_path = strstr(strrev(PHP_BINARY), DS)) {
            $path = strrev($dir_path) . 'php' . ($windows ? '.exe' : '');
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        // Get path from the PATH environment variable
        $paths = explode(PATH_SEPARATOR, getenv('PATH'));
        foreach ($paths as $path) {
            // We need this for XAMPP (Windows)
            if (strstr($path, 'php.exe') && $windows && file_exists($path) && is_file($path)) {
                return $path;
            } else {
                $php_executable = $path . DS . 'php' . ($windows ? '.exe' : '');

                if (file_exists($php_executable) && is_executable($php_executable)) {
                    return $php_executable;
                }
            }
        }

        return '/usr/bin/php';
    }

    /**
     * Generate the cron command for this installation that should be accurate
     *
     * @return string The cron command
     */
    private function createCronCommand()
    {
        $windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
        $command = $windows
            ? 'schtasks /create /sc minute /mo 1 /tn "BLESTA_CRON" /tr "\'' . $this->getPathToPhp($windows) . '\''
            : '* * * * * ' . $this->getPathToPhp($windows);

        return $command . ' -q ' . ROOTWEBDIR . 'index.php cron' . ($windows ? '"' : ' > /dev/null 2>&1');
    }

    /**
     * Creates and saves a system cron key
     *
     * @return string The cron key generated
     */
    private function createCronKey()
    {
        $this->StringHelper = $this->DataStructure->create('String');

        // Generate a random key with the following options
        $cron_key = $this->StringHelper->random();

        // Update the cron key setting
        $this->Settings->setSetting('cron_key', $cron_key, true);

        return $cron_key;
    }
}
