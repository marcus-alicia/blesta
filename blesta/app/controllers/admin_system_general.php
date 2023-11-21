<?php

/**
 * Admin System General Settings
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminSystemGeneral extends AppController
{
    /**
     * Pre-action setup method that is called before the index method, or the set controller action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        $this->uses(['Navigation', 'Settings', 'Transactions']);
        $this->components(['SettingsCollection']);

        Language::loadLang('admin_system_general');

        // Set the left nav for all settings pages to settings_leftnav
        $this->set(
            'left_nav',
            $this->partial('settings_leftnav', ['nav' => $this->Navigation->getSystem($this->base_uri)])
        );
    }

    // General settings
    public function index()
    {
        $this->redirect($this->base_uri . 'settings/system/general/basic/');
    }

    /**
     * Basic settings
     */
    public function basic()
    {
        // Update basic settings
        if (!empty($this->post)) {
            // Set updatable fields
            $fields = [
                'log_days' => '',
                'log_dir' => '',
                'temp_dir' => '',
                'uploads_dir' => '',
                'root_web_dir' => '',
                'behind_proxy' => ''
            ];
            $data = array_intersect_key($this->post, $fields);

            // Set checkboxes if not given
            if (empty($this->post['behind_proxy'])) {
                $data['behind_proxy'] = 'false';
            }

            // Set trailing slashes if missing
            $dirs = ['temp_dir', 'uploads_dir', 'root_web_dir', 'log_dir'];
            foreach ($dirs as $dir) {
                if (!empty($data[$dir]) && substr($data[$dir], -1, 1) != DS) {
                    $data[$dir] .= DS;
                }
            }

            // Update the settings
            $this->Settings->setSettings($data, array_keys($fields));

            $this->setMessage('message', Language::_('AdminSystemGeneral.!success.basic_updated', true));
        }

        // Get all settings
        $settings = $this->SettingsCollection->fetchSystemSettings($this->Settings);

        // Check if directories are writable and set them accordingly
        $dirs_writable = [
            'temp_dir' => false,
            'uploads_dir' => false,
            'log_dir' => false
        ];

        foreach ($dirs_writable as $dir => &$value) {
            if (isset($settings[$dir])) {
                if (is_dir($settings[$dir]) && is_writable($settings[$dir])) {
                    $value = true;
                }
            }
        }

        // Set rotation policy drop-down for module logs
        $log_days = [];
        $log_days[1] = '1 ' . Language::_('AdminSystemGeneral.basic.text_day', true);
        for ($i = 2; $i <= 90; $i++) {
            $log_days[$i] = $i . ' ' . Language::_('AdminSystemGeneral.basic.text_days', true);
        }
        $log_days['never'] = Language::_('AdminSystemGeneral.basic.text_no_log', true);

        $this->set('log_days', $log_days);
        $this->set('dirs_writable', $dirs_writable);
        $this->set('vars', $settings);
    }

    /**
     * General GeoIP Settings page
     */
    public function geoIp()
    {
        $vars = [];
        $settings = $this->SettingsCollection->fetchSystemSettings($this->Settings);

        if (!empty($this->post)) {
            // Set geoip enabled field if not given
            if (empty($this->post['geoip_enabled'])) {
                $this->post['geoip_enabled'] = 'false';
            }

            if ($this->post['geoip_enabled'] == 'true' && !extension_loaded('mbstring')) {
                $this->setMessage('error', Language::_('AdminSystemGeneral.!error.geoip_mbstring_required', true));
            } else {
                $this->Settings->setSettings($this->post, ['geoip_enabled']);

                $this->setMessage('message', Language::_('AdminSystemGeneral.!success.geoip_updated', true));
            }
            $vars = $this->post;
        }

        // Set GeoIP settings
        if (empty($vars)) {
            $vars = $settings;
        }

        // Set whether the GeoIP database exists or not
        $this->components(['Net']);
        $this->NetGeoIp = $this->Net->create('NetGeoIp');
        $geoip_database_filename = $this->NetGeoIp->getGeoIpDatabaseFilename();
        $geoip_database_exists = false;

        if (isset($settings['uploads_dir'])) {
            if (file_exists($settings['uploads_dir'] . 'system' . DS . $geoip_database_filename)) {
                $geoip_database_exists = true;
            }

            $this->set('uploads_dir', $settings['uploads_dir']);
        }

        $this->set('geoip_database_filename', $geoip_database_filename);
        $this->set('geoip_database_exists', $geoip_database_exists);
        $this->set('vars', $vars);
    }

    /**
     * General Maintenance Settings page
     */
    public function maintenance()
    {
        $vars = [];

        if (!empty($this->post)) {
            // Set maintenance mode if not given
            if (empty($this->post['maintenance_mode'])) {
                $this->post['maintenance_mode'] = 'false';
            }

            $fields = ['maintenance_reason', 'maintenance_mode'];
            $this->Settings->setSettings($this->post, $fields);

            $this->setMessage('message', Language::_('AdminSystemGeneral.!success.maintenance_updated', true));
        }

        if (empty($vars)) {
            $vars = $this->SettingsCollection->fetchSystemSettings($this->Settings);
        }

        $this->set('vars', $vars);
    }

    /**
     * General License Settings page
     */
    public function license()
    {
        $this->uses(['License']);
        $vars = [];

        if (!empty($this->post) && isset($this->post['license_key'])) {
            $this->License->updateLicenseKey($this->post['license_key']);

            if (($errors = $this->License->errors())) {
                $this->setMessage('error', $errors);
            } else {
                $this->setMessage('message', Language::_('AdminSystemGeneral.!success.license_updated', true));
            }
        }

        if (empty($vars)) {
            $vars = $this->SettingsCollection->fetchSystemSettings($this->Settings);
        }

        $this->set('vars', $vars);
    }

    /**
     * Payment Types settings
     */
    public function paymentTypes()
    {
        $this->set('types', $this->Transactions->getTypes());
        $this->set('debit_types', $this->Transactions->getDebitTypes());
    }

    /**
     * Add a payment type
     */
    public function addType()
    {
        // Add a payment type
        if (!empty($this->post)) {
            $type_id = $this->Transactions->addType($this->post);

            if (($errors = $this->Transactions->errors())) {
                // Error, reset vars
                $this->setMessage('error', $errors);
                $vars = (object) $this->post;
            } else {
                // Success
                $payment_type = $this->Transactions->getType($type_id);
                $this->flashMessage(
                    'message',
                    Language::_('AdminSystemGeneral.!success.addtype_created', true, [$payment_type->real_name])
                );
                $this->redirect($this->base_uri . 'settings/system/general/paymenttypes/');
            }
        }

        if (empty($vars)) {
            $vars = new stdClass();
        }

        $this->set('vars', $vars);
        $this->set('types', $this->Transactions->getDebitTypes());
    }

    /**
     * Edit a payment type
     */
    public function editType()
    {
        if (!isset($this->get[0]) || !($type = $this->Transactions->getType((int) $this->get[0]))) {
            $this->redirect($this->base_uri . 'settings/system/general/paymenttypes/');
        }

        // Add a payment type
        if (!empty($this->post)) {
            // Set empty checkbox
            if (empty($this->post['is_lang'])) {
                $this->post['is_lang'] = '0';
            }

            $this->Transactions->editType($type->id, $this->post);

            if (($errors = $this->Transactions->errors())) {
                // Error, reset vars
                $this->setMessage('error', $errors);
                $vars = (object) $this->post;
            } else {
                // Success
                $payment_type = $this->Transactions->getType($type->id);
                $this->flashMessage(
                    'message',
                    Language::_('AdminSystemGeneral.!success.edittype_updated', true, [$payment_type->real_name])
                );
                $this->redirect($this->base_uri . 'settings/system/general/paymenttypes/');
            }
        }

        if (empty($vars)) {
            $vars = $this->Transactions->getType($type->id);
        }

        $this->set('vars', $vars);
        $this->set('types', $this->Transactions->getDebitTypes());
    }

    /**
     * Delete a payment type
     */
    public function deleteType()
    {
        if (!isset($this->post['id']) || !($type = $this->Transactions->getType((int) $this->post['id']))) {
            $this->redirect($this->base_uri . 'settings/system/general/paymenttypes/');
        }

        // Delete the payment type
        $this->Transactions->deleteType($type->id);

        $this->flashMessage(
            'message',
            Language::_('AdminSystemGeneral.!success.deletetype_deleted', true, [$type->real_name])
        );
        $this->redirect($this->base_uri . 'settings/system/general/paymenttypes/');
    }
}
