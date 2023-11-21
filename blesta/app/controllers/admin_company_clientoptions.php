<?php

/**
 * Admin Client Options
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminCompanyClientOptions extends AppController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        $this->uses(['Clients', 'ClientGroups', 'Navigation']);

        Language::loadLang('admin_company_clientoptions');

        // Set the left nav for all settings pages to settings_leftnav
        $this->set(
            'left_nav',
            $this->partial('settings_leftnav', ['nav' => $this->Navigation->getCompany($this->base_uri)])
        );
    }

    /**
     * Custom Fields page
     */
    public function index()
    {
        $this->redirect($this->base_uri . 'settings/company/clientoptions/general');
    }

    /**
     * Custom Fields page
     */
    public function customFields()
    {
        // Get all client groups and fields
        $client_groups = $this->ClientGroups->getAll($this->company_id);
        $client_fields = $this->Clients->getCustomFields($this->company_id);

        // Merge client groups and fields into a nicely formatted array of objects
        $groups = [];
        if ($client_groups) {
            $num_groups = count($client_groups);
            $num_fields = ($client_fields) ? count($client_fields) : 0;

            // Set client group and client fields
            for ($i = 0; $i < $num_groups; $i++) {
                $groups[$i] = new stdClass();
                $groups[$i]->name = $client_groups[$i]->name;
                $groups[$i]->color = $client_groups[$i]->color;
                $groups[$i]->fields = [];

                // Set any client fields for this group
                for ($j = 0; $j < $num_fields; $j++) {
                    if ($client_groups[$i]->id == $client_fields[$j]->client_group_id) {
                        $groups[$i]->fields[] = $client_fields[$j];
                    }
                }
            }
        }

        $this->set('groups', $groups);
        $this->set('types', $this->Clients->getCustomFieldTypes());
    }

    /**
     * Add custom field
     */
    public function addCustomField()
    {
        $this->helpers(['DataStructure']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        $vars = new stdClass();

        // Add the custom field
        if (!empty($this->post)) {
            // Set regex if custom field is required
            if (empty($this->post['required'])) {
                $this->post['regex'] = null;
            } elseif ($this->post['required'] == '/.+/') {
                $this->post['regex'] = $this->post['required'];
            }

            // Set empty checkboxes
            if (empty($this->post['is_lang'])) {
                $this->post['is_lang'] = '0';
            }
            if (empty($this->post['show_client'])) {
                $this->post['show_client'] = '0';
            }
            if (empty($this->post['read_only'])) {
                $this->post['read_only'] = '0';
            }
            if (empty($this->post['encrypted'])) {
                $this->post['encrypted'] = '0';
            }

            $post_data = $this->post;

            // Reformat select/checkbox values
            if (!empty($post_data['type'])) {
                switch ($post_data['type']) {
                    case 'text':
                        $post_data['default'] = (isset($this->post['default_text']) ? $this->post['default_text'] : null);
                        break;
                    case 'textarea':
                        $post_data['default'] = (isset($this->post['default_textarea']) ? $this->post['default_textarea'] : null);
                        break;
                    case 'checkbox':
                        // Include values specified as checkbox values
                        $post_data['values'] = $post_data['checkbox_value'];

                        // Set the default checkbox to the current value iff provided
                        $post_data['default'] = null;
                        if ((isset($this->post['default_checkbox']) ? $this->post['default_checkbox'] : null)) {
                            $post_data['default'] = $post_data['values'];
                        }
                        break;
                    case 'select':
                        // Include values specified as select values
                        $post_data['values'] = [];

                        $select_options = $this->ArrayHelper->keyToNumeric($post_data['select']);

                        // Set option values
                        foreach ($select_options as $option) {
                            $post_data['values'][(isset($option['option']) ? $option['option'] : '')] = (
                                isset($option['value']) ? $option['value'] : ''
                            );

                            // Set the default option to the default value
                            if ((isset($option['default']) ? $option['default'] : null)) {
                                $post_data['default'] = (isset($option['value']) ? $option['value'] : null);
                            }
                        }
                        break;
                }
            }

            // Add the custom field
            $this->Clients->addCustomField($post_data);

            if (($errors = $this->Clients->errors())) {
                // Error, reset vars
                $this->setMessage('error', $errors);
                $vars = (object) $this->post;
            } else {
                // Success, redirect
                $this->flashMessage('message', Language::_('AdminCompanyClientOptions.!success.field_created', true));
                $this->redirect($this->base_uri . 'settings/company/clientoptions/customfields/');
            }
        }

        $this->set(
            'groups',
            $this->Form->collapseObjectArray($this->ClientGroups->getAll($this->company_id), 'name', 'id')
        );
        $this->set('types', $this->Clients->getCustomFieldTypes());
        $this->set('required_types', $this->getRequired());
        $this->set('vars', $vars);

        // Set our notice, but remove any other messages that may be set so they are not duplicated
        $this->set(
            'configuration_warning',
            $this->setMessage(
                'notice',
                Language::_('AdminCompanyClientOptions.addcustomfield.configuration_warning', true),
                true,
                ['error' => null, 'info' => null, 'message' => null]
            )
        );
    }

    /**
     * Edit a custom field
     */
    public function editCustomField()
    {
        if (isset($this->get[0])
            && !($field = $this->Clients->getCustomField((int) $this->get[0], $this->company_id))
        ) {
            $this->redirect($this->base_uri . 'settings/company/clientoptions/customfields/');
        }

        $this->helpers(['DataStructure']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        $vars = [];

        // Edit the custom field
        if (!empty($this->post)) {
            // Set client group ID
            $this->post['client_group_id'] = $field->client_group_id;

            // Set regex if custom field is required
            if (empty($this->post['required'])) {
                $this->post['regex'] = null;
            } elseif ($this->post['required'] == '/.+/') {
                $this->post['regex'] = $this->post['required'];
            }

            // Set empty checkboxes
            if (empty($this->post['is_lang'])) {
                $this->post['is_lang'] = '0';
            }
            if (empty($this->post['show_client'])) {
                $this->post['show_client'] = '0';
            }
            if (empty($this->post['read_only'])) {
                $this->post['read_only'] = '0';
            }
            if (empty($this->post['encrypted'])) {
                $this->post['encrypted'] = '0';
            }

            $post_data = $this->post;

            // Reformat select/checkbox values
            if (!empty($post_data['type'])) {
                switch ($post_data['type']) {
                    case 'text':
                        $post_data['default'] = (isset($this->post['default_text']) ? $this->post['default_text'] : null);
                        break;
                    case 'textarea':
                        $post_data['default'] = (isset($this->post['default_textarea']) ? $this->post['default_textarea'] : null);
                        break;
                    case 'checkbox':
                        // Include values specified as checkbox values
                        $post_data['values'] = $post_data['checkbox_value'];

                        // Set the default checkbox to the current value iff provided, otherwise remove it
                        $post_data['default'] = null;
                        if ((isset($this->post['default_checkbox']) ? $this->post['default_checkbox'] : null)) {
                            $post_data['default'] = $post_data['values'];
                        }
                        break;
                    case 'select':
                        // Include values specified as select values
                        $post_data['values'] = [];

                        $select_options = $this->ArrayHelper->keyToNumeric($post_data['select']);

                        // Set option values
                        foreach ($select_options as $option) {
                            $post_data['values'][(isset($option['option']) ? $option['option'] : '')] = (
                                isset($option['value']) ? $option['value'] : ''
                            );

                            // Set the default option to the default value
                            if ((isset($option['default']) ? $option['default'] : null)) {
                                $post_data['default'] = (isset($option['value']) ? $option['value'] : null);
                            }
                        }
                        break;
                }
            }

            // Edit the custom field
            $this->Clients->editCustomField($field->id, $post_data);

            if (($errors = $this->Clients->errors())) {
                // Error, reset vars
                $this->setMessage('error', $errors);
                $vars = (object) $this->post;
            } else {
                // Success, redirect
                $this->flashMessage('message', Language::_('AdminCompanyClientOptions.!success.field_updated', true));
                $this->redirect($this->base_uri . 'settings/company/clientoptions/customfields/');
            }
        }

        // Set current field
        if (empty($vars)) {
            $vars = $field;

            // Set the required status of this custom field
            $vars->required = (empty($vars->regex) ? '' : ($vars->regex == '/.+/' ? $vars->regex : 'regex'));

            // Format the values
            if ($vars->type != null) {
                switch ($vars->type) {
                    case 'text':
                        $vars->default_text = $vars->default;
                        break;
                    case 'textarea':
                        $vars->default_textarea = $vars->default;
                        break;
                    case 'checkbox':
                        // Format the checkbox options for the view
                        $vars->checkbox_value = $vars->values;
                        $vars->default_checkbox = $vars->default;
                        break;
                    case 'select':
                        // Format the select options for the view
                        $select_values = [
                            'option' => [],
                            'value' => []
                        ];

                        // Set each select option/value
                        if (!empty($vars->values)) {
                            $i = 0;
                            foreach ($vars->values as $option => $value) {
                                $select_values['option'][$i] = $option;
                                $select_values['value'][$i] = $value;
                                $select_values['default'][$i] = ($vars->default == $value ? '1' : '0');
                                $i++;
                            }
                        }
                        $vars->select = $select_values;
                        break;
                }
            }
        }

        $this->set('types', $this->Clients->getCustomFieldTypes());
        $this->set('required_types', $this->getRequired());
        $this->set('vars', $vars);

        // Set our notice, but remove any other messages that may be set so they are not duplicated
        $this->set(
            'configuration_warning',
            $this->setMessage(
                'notice',
                Language::_('AdminCompanyClientOptions.addcustomfield.configuration_warning', true),
                true,
                ['error' => null, 'info' => null, 'message' => null]
            )
        );
    }

    /**
     * Delete a custom field
     */
    public function deleteCustomField()
    {
        // Ensure a valid custom field was given
        if (!isset($this->post['id'])
            || !($field = $this->Clients->getCustomField($this->post['id'], $this->company_id))
        ) {
            $this->redirect($this->base_uri . 'settings/company/clientoptions/customfields/');
        }

        $this->Clients->deleteCustomField($field->id);
        $this->flashMessage('message', Language::_('AdminCompanyClientOptions.!success.field_deleted', true));
        $this->redirect($this->base_uri . 'settings/company/clientoptions/customfields/');
    }

    /**
     * Set the required client creation fields for the company
     */
    public function requiredFields()
    {
        // Set a notice message if any client group has client group settings applied
        if ($this->clientGroupSettingsExist()) {
            $this->setMessage('notice', Language::_('AdminCompanyClientOptions.!notice.group_settings', true));
        }

        $this->uses(['Companies']);

        $contact_fields_groups = ['required_contact_fields', 'shown_contact_fields', 'read_only_contact_fields'];

        if (!empty($this->post)) {
            $white_list = [
                'first_name', 'last_name', 'company', 'title', 'address1', 'address2',
                'city', 'country', 'state', 'zip', 'email', 'phone', 'fax'
            ];

            foreach ($contact_fields_groups as $group_name) {
                // Default and serialize the contact fields group
                if (empty($this->post[$group_name])) {
                    $this->post[$group_name] = [];
                }

                foreach ($this->post[$group_name] as $key => $field) {
                    if (!in_array($field, $white_list)) {
                        unset($this->post[$group_name][$key]);
                    }
                }

                // Update settings
                $this->Companies->setSettings(
                    $this->company_id,
                    [$group_name => base64_encode(serialize((array) $this->post[$group_name]))],
                    [$group_name]
                );
            }

            $this->setMessage(
                'message',
                Language::_('AdminCompanyClientOptions.!success.requiredfields_updated', true)
            );
        }

        $settings = $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id);
        $vars = [];

        foreach ($contact_fields_groups as $group_name) {
            ${$group_name} = isset($settings[$group_name])
                ? unserialize(base64_decode($settings[$group_name]))
                : [];
            $vars[$group_name] = ${$group_name};
        }

        $this->set('required_fields_form', $this->partial('admin_company_require_fields_form', ['vars' => (object) $vars]));
    }

    /**
     * Returns a list of required custom field types
     *
     * @return array A key=>value array of custom field required types
     */
    private function getRequired()
    {
        return [
            '' => Language::_('AdminCompanyClientOptions.getRequired.no', true),
            '/.+/' => Language::_('AdminCompanyClientOptions.getRequired.yes', true),
            'regex' => Language::_('AdminCompanyClientOptions.getRequired.regex', true)
        ];
    }

    /**
     * Updates general client settings
     */
    public function general()
    {
        $this->uses(['Companies']);
        $this->components(['SettingsCollection']);

        $company_id = $this->company_id;

        // Set a notice message if any client group has client group settings applied
        if ($this->clientGroupSettingsExist()) {
            $this->setMessage('notice', Language::_('AdminCompanyClientOptions.!notice.group_settings', true));
        }

        if (!empty($this->post)) {
            // Set unchecked checkboxes
            $checkboxes = ['force_email_usernames', 'email_verification', 'prevent_unverified_payments'];

            foreach ($checkboxes as $checkbox) {
                if (!isset($this->post[$checkbox])) {
                    $this->post[$checkbox] = 'false';
                }
            }

            // Validate clients ID format
            if (str_contains($this->post['clients_format'] ?? '', '{num}')) {
                // Update company settings
                $fields = [
                    'unique_contact_emails',
                    'force_email_usernames',
                    'email_verification',
                    'prevent_unverified_payments',
                    'clients_increment',
                    'clients_start',
                    'clients_format'
                ];
                $this->Companies->setSettings($company_id, $this->post, $fields);

                $this->setMessage('message', Language::_('AdminCompanyClientOptions.!success.general_updated', true));
            } else {
                $this->setMessage('error', Language::_('AdminCompanyClientOptions.!error.clients_format', true));
            }
        }

        $vars = $this->SettingsCollection->fetchSettings($this->Companies, $company_id);
        $this->set(
            'client_general_form',
            $this->partial(
                'admin_company_client_general_form',
                ['vars' => $vars, 'unique_email_options' => $this->getUniqueEmailOptions()]
            )
        );
    }

    /**
     * Gets a list of contact email uniqueness options
     */
    private function getUniqueEmailOptions()
    {
        return [
            '' => Language::_('AdminCompanyClientOptions.general.field_unique_contact_emails_none', true),
            'primary' => Language::_('AdminCompanyClientOptions.general.field_unique_contact_emails_primary', true),
            'all' => Language::_('AdminCompanyClientOptions.general.field_unique_contact_emails_all', true),
        ];
    }

    /**
     * Checks if any client group has client-group-specific settings attached to it
     *
     * @return bool True if a client group exists with client-group-specific settings for this company, false otherwise
     */
    private function clientGroupSettingsExist()
    {
        $this->uses(['ClientGroups']);

        // Get a list of all Client Groups belonging to this company
        $client_groups = $this->ClientGroups->getAll($this->company_id);

        // Check if any client groups have client-group-specific settings
        if (!empty($client_groups) && is_array($client_groups)) {
            $num_groups = count($client_groups);
            for ($i = 0; $i < $num_groups; $i++) {
                // Fetch settings for a client group, ignoring any setting inheritence
                $settings = $this->SettingsCollection->fetchClientGroupSettings(
                    $client_groups[$i]->id,
                    $this->ClientGroups,
                    true
                );

                // If any client group settings exist, return
                if (!empty($settings)) {
                    return true;
                }
            }
        }
        return false;
    }
}
