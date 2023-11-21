<?php

/**
 * Admin Company Data Feeds Settings
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminCompanyFeeds extends AppController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        $this->uses(['Navigation']);
        $this->helpers(['Form', 'Html']);

        Language::loadLang('admin_company_feeds');

        // Set the left nav for all settings pages to settings_leftnav
        $this->set(
            'left_nav',
            $this->partial('settings_leftnav', ['nav' => $this->Navigation->getCompany($this->base_uri)])
        );
    }

    /**
     * Data feeds list
     */
    public function index()
    {
        $this->uses(['Companies']);
        $this->components(['SettingsCollection']);

        // Get company settings
        $company_settings = $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id);

        $vars = !empty($this->post) ? $this->post : $company_settings;

        if (!empty($this->post)) {
            // Set unchecked checkboxes
            if (!isset($this->post['enable_data_feeds'])) {
                $this->post['enable_data_feeds'] = '0';
            }

            $fields = ['enable_data_feeds'];
            $this->Companies->setSettings($this->company_id, $this->post, $fields);

            $this->flashMessage(
                'message',
                Language::_('AdminCompanyFeeds.!success.datafeeds_updated', true)
            );
            $this->redirect($this->base_uri . 'settings/company/feeds/');
        }

        $this->set('vars', $vars);
    }

    /**
     * Data feed settings
     */
    public function settings()
    {
        $this->uses(['DataFeeds', 'Companies']);
        $this->components(['SettingsCollection']);
        $this->helpers(['DataStructure']);

        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Get company settings
        $company_settings = $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id);

        // Get all data feeds
        $feeds = $this->DataFeeds->getAll(['company_id' => $this->company_id]);

        $vars = !empty($this->post) ? $this->post : $company_settings;

        // Update settings
        if (!empty($this->post)) {
            // Set unchecked checkboxes
            if (!isset($this->post['endpoint'])) {
                $this->post['endpoint'] = [];
            }

            // Enable/disable endpoints
            if (isset($this->post['endpoint'])) {
                $company_endpoints = $this->ArrayHelper->numericToKey(
                    $this->DataFeeds->getAllEndpoints(['company_id' => $this->company_id]),
                    'id',
                    'id'
                );

                foreach ($company_endpoints as $endpoint_id) {
                    if (array_key_exists($endpoint_id, $this->post['endpoint']) && ($this->post['endpoint'][$endpoint_id] ?? '0') == '1') {
                        $this->DataFeeds->editEndpoint($endpoint_id, ['enabled' => 1]);
                    } else {
                        $this->DataFeeds->editEndpoint($endpoint_id, ['enabled' => 0]);
                    }
                }
            }

            // Update settings
            $accepted_fields = [];
            foreach ($feeds as $feed) {
                if (($instance = $this->DataFeeds->getInstance($feed->feed))) {
                    $fields = $instance->getOptionFields();

                    if (!empty($fields)) {
                        $requested_fields = [];
                        foreach ($fields->getFields() as $field) {
                            if (!empty($field->fields) && $field->type == 'label') {
                                foreach ($field->fields as $sub_field) {
                                    $requested_fields[] = $sub_field->params['name'];
                                }
                            } else {
                                $requested_fields[] = $field->params['name'];
                            }
                        }

                        $accepted_fields = $accepted_fields + array_filter($company_settings, function ($key) use ($requested_fields) {
                            return in_array($key, $requested_fields);
                        }, ARRAY_FILTER_USE_KEY);
                    }
                }
            }

            if (!empty($accepted_fields)) {
                $this->Companies->setSettings($this->company_id, $this->post, $accepted_fields);
            }

            $this->flashMessage(
                'message',
                Language::_('AdminCompanyFeeds.!success.settings_updated', true)
            );
            $this->redirect($this->base_uri . 'settings/company/feeds/settings/');
        }

        $this->set('vars', $vars);
        $this->set('feeds', $feeds);
    }
}
