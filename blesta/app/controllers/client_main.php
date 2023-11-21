<?php

/**
 * Client portal main controller
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientMain extends ClientController
{
    /**
     * @var string The custom field prefix used in form names to keep them unique and easily referenced
     */
    private $custom_field_prefix = 'c_f';

    /**
     * @var array A list of client editable settings
     */
    private $editable_settings = [];

    /**
     * Main pre-action
     */
    public function preAction()
    {
        parent::preAction();

        // Allow states to be fetched and set the language without login
        if (in_array(strtolower($this->action), ['getstates', 'setlanguage'])) {
            return;
        }

        // Load models, language
        $this->uses(['Clients', 'Contacts']);

        $this->contact = $this->Contacts->getByUserId($this->Session->read('blesta_id'), $this->client->id);
        if (!$this->contact) {
            $this->contact = $this->Contacts->get($this->client->contact_id);
        }

        // Include client settings
        if ($this->contact) {
            $this->contact->settings = $this->client->settings;
        }

        // Set left client info section
        $this->setMyInfo();

        // Set editable client settings
        $client_settings = $this->client->settings;
        $this->editable_settings = [
            'autodebit' => true,
            'tax_id' => (isset($client_settings['show_client_tax_id'])
                ? ($client_settings['show_client_tax_id'] == 'true')
                : false
            ),
            'inv_address_to' => true,
            'default_currency' => (isset($client_settings['client_set_currency'])
                ? ($client_settings['client_set_currency'] == 'true')
                : false
            ),
            'inv_method' => (isset($client_settings['client_set_invoice'])
                ? ($client_settings['client_set_invoice'] == 'true')
                : false
            ),
            'language' => (isset($client_settings['client_set_lang'])
                ? ($client_settings['client_set_lang'] == 'true')
                : false
            ),
            'receive_email_marketing' => (isset($client_settings['show_receive_email_marketing'])
                ? ($client_settings['show_receive_email_marketing'] == 'true')
                : true
            )
        ];
    }

    /**
     * Client Profile
     */
    public function index()
    {
        if ($this->hasPermission('client_invoices')) {
            // Get all client currencies that there may be amounts due in
            $currencies = $this->Invoices->invoicedCurrencies($this->client->id);

            // Set a message for all currencies that have an amount due
            $amount_due_message = null;
            $max_due = 0;
            $past_due = 0;
            $max_due_currency = null;
            $currencies_owed = 0;
            foreach ($currencies as $currency) {
                $total_due = $this->Invoices->amountDue($this->client->id, $currency->currency);

                if ($total_due > $max_due) {
                    $max_due_currency = $currency->currency;
                    $max_due = $total_due;
                    $amount_due_message = Language::_(
                        'ClientMain.!info.invoice_due_text',
                        true,
                        $this->CurrencyFormat->format($total_due, $currency->currency)
                    );

                    // Get any past due amounts
                    $past_due = $this->Invoices->amountDue($this->client->id, $max_due_currency, 'past_due');
                    if ($past_due > 0) {
                        $amount_due_message = Language::_(
                            'ClientMain.!info.invoice_due_past_due_text',
                            true,
                            $this->CurrencyFormat->format($total_due, $currency->currency),
                            $this->CurrencyFormat->format($past_due, $currency->currency)
                        );
                    }

                    $currencies_owed++;
                }
            }

            if ($amount_due_message) {
                // Set a past due button
                $past_due_btn = [];
                $message_type = 'info';
                if ($past_due > 0) {
                    $message_type = 'notice';

                    $past_due_btn = [
                        'class' => 'btn',
                        'url' => $this->Html->safe($this->base_uri . 'pay/index/' . $max_due_currency . '/pastdue/'),
                        'label' => Language::_('ClientMain.!info.invoice_past_due_button', true)
                    ];
                }

                $message = ['amount_due' => [$amount_due_message]];
                if ($currencies_owed > 1) {
                    $message['amount_due'][] = Language::_('ClientMain.!info.invoice_due_other_currencies', true);
                }

                $params = [
                    $message_type . '_title' => Language::_(
                        'ClientMain.!info.invoice_due_title',
                        true,
                        $this->client->first_name
                    ),
                    $message_type . '_buttons' => []
                ];

                // Show the payment button when there is no past due button for the full amount
                if ($past_due < $max_due) {
                    $params[$message_type . '_buttons'][] = [
                        'class' => 'btn',
                        'url' => $this->Html->safe($this->base_uri . 'pay/index/' . $max_due_currency . '/'),
                        'label' => Language::_('ClientMain.!info.invoice_due_button', true),
                        'icon_class' => 'fa-plus-circle'
                    ];
                }

                // Add the past due button if any amounts are past due
                if (!empty($past_due_btn)) {
                    $params[$message_type . '_buttons'][] = $past_due_btn;
                }

                $this->setMessage($message_type, $message, false, $params);
            }

            // Add a note regarding in-review messages
            $this->setInReviewMessage();
        }

        // Set a message if the email hasn't been verified
        Loader::loadModels($this, ['ClientGroups', 'EmailVerifications', 'Contacts']);
        Loader::loadHelpers($this, ['Form']);

        $settings = $this->ClientGroups->getSettings($this->client->client_group_id);
        $settings = $this->Form->collapseObjectArray($settings, 'value', 'key');

        if (
            isset($settings['email_verification'])
            && $settings['email_verification'] == 'true'
        ) {
            $contacts = $this->Contacts->getAll($this->client->id);
            $email_verification = $this->EmailVerifications->getByContactId($this->contact->id);

            if (
                empty($email_verification)
                || (isset($email_verification->verified) && $email_verification->verified == 1)
            ) {
                foreach ($contacts as $contact) {
                    if (empty($contact->user_id)) {
                        $contact_email_verification = $this->EmailVerifications->getByContactId($contact->id);

                        if (
                            isset($contact_email_verification->verified) && $contact_email_verification->verified == 0
                        ) {
                            $email_verification = $contact_email_verification;
                            break;
                        }
                    }
                }
            }

            if (isset($email_verification->verified) && $email_verification->verified == 0) {
                if (isset($message_type) && $message_type == 'info' && !empty($message)) {
                    $message['amount_due'][] = Language::_(
                        'ClientMain.!info.email_pending_verification',
                        true,
                        $email_verification->email
                    );
                } else {
                    $message = Language::_(
                        'ClientMain.!info.email_pending_verification',
                        true,
                        $email_verification->email
                    );
                }

                $time = time();
                $hash = $this->Clients->systemHash('c=' . $email_verification->contact_id . '|t=' . $time);
                $options = [
                    'info_buttons' => [
                        [
                            'url' => $this->base_uri . 'verify/send/?sid=' . rawurlencode(
                                $this->Clients->systemEncrypt(
                                    'c=' . $email_verification->contact_id . '|t=' . $time . '|h=' . substr($hash, -16)
                                )
                            ),
                            'label' => Language::_('ClientMain.!info.email_pending_verification_button', true),
                            'icon_class' => 'fa-share'
                        ]
                    ]
                ];

                $this->setMessage('info', $message, false, $options);
            }
        }

        $this->set('client', $this->client);
    }

    /**
     * Sets a message to the view regarding in-review services
     */
    private function setInReviewMessage()
    {
        if (!isset($this->Services)) {
            $this->uses(['Services']);
        }

        // Fetch all in-review services
        $services = $this->Services->getAllByClient(
            $this->client->id,
            'in_review',
            ['date_added' => 'DESC'],
            false
        );

        // Construct a message to notify the client of their in-review services
        $service_list = [];
        $num_services = count($services);
        $max_services = 5;
        for ($i = 0; $i < min($max_services, $num_services); $i++) {
            $service_list[] = Language::_('ClientMain.!info.service_name', true, $services[$i]->package->name, $services[$i]->name);
        }
        unset($services);

        // Add a note about additional services
        if ($num_services > $max_services) {
            $services_over = ($num_services - $max_services);
            $service_list[] = Language::_(
                'ClientMain.!info.additional_service' . ($services_over > 1 ? 's' : ''),
                true,
                $services_over
            );
        }

        // Set the in-review message
        if (!empty($service_list)) {
            $this->setMessage(
                'info',
                [$service_list],
                false,
                [
                    'info_title' => Language::_('ClientMain.!info.service_in_review', true),
                    'info_buttons' => []
                ]
            );
        }
    }

    /**
     * Edit the client
     */
    public function edit()
    {
        $this->uses(['Currencies', 'Languages', 'Users']);
        $this->ArrayHelper = $this->DataStructure->create('Array');
        $is_primary = $this->client->contact_id == $this->contact->id;

        // Set user as the current user, or the client's primary user if logged in as staff
        $current_user_id = $this->Session->read('blesta_id');
        if ($this->isStaffAsClient()) {
            $current_user_id = $this->client->user_id;
        }

        $user = $this->Users->get($current_user_id);
        $company = Configure::get('Blesta.company');

        // Load the Base2n class from vendors
        $base32 = new Base2n(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', false, true, true);

        $vars = [];

        // Update the client
        if (!empty($this->post)) {
            // Set the client settings to update
            $new_client_settings = [];

            if ($this->editable_settings['receive_email_marketing']) {
                $new_client_settings['receive_email_marketing'] = empty($this->post['receive_email_marketing'])
                    ? 'false'
                    : $this->post['receive_email_marketing'];
            }

            foreach ($this->editable_settings as $setting => $enabled) {
                if (isset($this->post[$setting]) && $enabled) {
                    $new_client_settings[$setting] = $this->post[$setting];
                }
            }

            // Begin a new transaction
            $this->Clients->begin();

            // Update the email/password, or two-factor auth if given
            $email_user_type = (isset($this->client->settings['username_type'])
                && $this->client->settings['username_type'] == 'email');

            if (empty($this->post['new_password'])) {
                unset($this->post['new_password'], $this->post['confirm_password']);
            }

            // Set user fields to update
            $user_vars = array_intersect_key(
                $this->post,
                array_flip(
                    [
                        'current_password',
                        'new_password',
                        'confirm_password',
                        'two_factor_mode',
                        'two_factor_key',
                        'otp'
                    ]
                )
            );

            if (!array_key_exists('two_factor_mode', (array)$user_vars)) {
                $user_vars['two_factor_mode'] = 'none';
                $user_vars['two_factor_key'] = null;
            }

            $validate_password = (
                ($user_vars['two_factor_mode'] != 'none' && $user->two_factor_mode != $user_vars['two_factor_mode'])
                || !empty($user_vars['new_password'])
            );

            if ($is_primary && $email_user_type && isset($this->post['email'])) {
                $user_vars['username'] = $this->post['email'];
            }

            $this->Users->edit($user->id, $user_vars, $validate_password);
            $user_errors = $this->Users->errors();

            $custom_field_errors = false;
            $client_settings_errors = false;
            if ($is_primary) {
                // Update the client custom fields
                $custom_field_errors = $this->addCustomFields($this->post);

                // Update client settings
                $this->Clients->setClientSettings($this->client->id, $new_client_settings);
                $client_settings_errors = $this->Clients->errors();
            }

            // Update the phone numbers
            $vars = $this->post;
            // Format the phone numbers
            $vars['numbers'] = $this->ArrayHelper->keyToNumeric($this->post['numbers'] ?? []);

            // Update the contact
            unset($vars['user_id']);
            if (($vars['email'] ?? '') == $this->contact->email) {
                $vars['verify'] = false;
            }
            $this->Contacts->edit($this->contact->id, $vars);
            $contact_errors = $this->Contacts->errors();

            // Combine any errors
            $errors = array_merge(
                ($contact_errors ? $contact_errors : []),
                ($client_settings_errors ? $client_settings_errors : []),
                ($custom_field_errors ? $custom_field_errors : []),
                ($user_errors ? $user_errors : [])
            );

            if (!empty($errors)) {
                // Error, rollback
                $this->Clients->rollBack();

                $this->setMessage('error', $errors);
                $vars = (object) $this->post;
                $vars->username = $user->username;
            } else {
                // Success, commit
                $this->Clients->commit();
                if (isset($new_client_settings['tax_id'])) {
                    $this->Clients->setSettings(
                        $this->client->id,
                        ['tax_id' => $new_client_settings['tax_id']],
                        ['tax_id', 'tax_exempt']
                    );
                }

                $this->flashMessage('message', Language::_('ClientMain.!success.client_updated', true));
                $this->redirect($this->base_uri);
            }
        }

        // Set the initial client data
        if (empty($vars)) {
            $vars = (object) array_merge((array) $user, (array) $this->contact);

            // Set contact phone numbers formatted for HTML
            $vars->numbers = $this->ArrayHelper->numericToKey($vars->numbers);

            // Set client custom field values
            if ($is_primary) {
                $field_values = $this->Clients->getCustomFieldValues($this->client->id);
                foreach ($field_values as $field) {
                    $vars->{$this->custom_field_prefix . $field->id} = $field->value;
                }
            }
        }

        // Set whether to show additional settings section
        $show_additional_settings = false;
        if ($is_primary
            && ($this->editable_settings['language']
                || $this->editable_settings['receive_email_marketing']
                || 0 < count(
                    $custom_fields = $this->Clients->getCustomFields(
                        $this->client->company_id,
                        $this->client->client_group_id,
                        ['show_client' => 1]
                    )
                )
            )
        ) {
            $show_additional_settings = true;
        }

        // Get all client contacts for which to make invoices addressable to (primary and billing contacts)
        $contacts = array_merge(
            $this->Contacts->getAll($this->client->id, 'primary'),
            $this->Contacts->getAll($this->client->id, 'billing')
        );

        $this->set('username', $user->username);

        if ($is_primary) {
            $this->set('contacts', $this->Form->collapseObjectArray($contacts, ['first_name', 'last_name'], 'id', ' '));
            $this->set(
                'currencies',
                $this->Form->collapseObjectArray($this->Currencies->getAll($this->client->company_id), 'code', 'code')
            );
            $this->set(
                'languages',
                $this->Form->collapseObjectArray($this->Languages->getAll($this->client->company_id), 'name', 'code')
            );
        }

        // Generate random two-factor key
        if (!isset($vars->two_factor_key) || $vars->two_factor_key == '') {
            $vars->two_factor_key = $this->Users->systemHash(mt_rand() . md5(mt_rand()), null, 'sha1');
        }

        $vars->two_factor_mode = (property_exists($vars, 'two_factor_mode')
            ? $vars->two_factor_mode
            : $user->two_factor_mode
        );
        $vars->two_factor_key_base32 = $base32->encode(pack('H*', $vars->two_factor_key));

        $this->set('enabled_fields', $this->editable_settings);
        $this->set('show_additional_settings', $show_additional_settings);
        $this->set('vars', $vars);
        $this->set('two_factor_issuer', $company->name);
        $this->set('is_primary', $is_primary);

        // Set partials to view
        $this->setContactView($vars, $this->contact);
        $this->setPhoneView($vars);
        $this->setCustomFieldView($vars);
    }

    /**
     * Edit client's invoice method
     */
    public function invoiceMethod()
    {
        $this->requirePermission('_invoice_delivery');

        // Get available delivery methods
        $delivery_methods = $this->Invoices->getDeliveryMethods($this->client->id);

        $vars = [];

        if (!empty($this->post)) {
            // Only update the invoice method setting from this page
            $vars = ['inv_method' => (isset($this->post['inv_method']) ? $this->post['inv_method'] : '')];
            $this->Clients->setClientSettings($this->client->id, $vars);

            if (($errors = $this->Clients->errors())) {
                // Error, reset vars
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                // Success
                $new_invoice_method = isset($delivery_methods[$vars['inv_method']])
                    ? $delivery_methods[$vars['inv_method']]
                    : '';
                $this->flashMessage(
                    'message',
                    Language::_('ClientMain.!success.invoice_method_updated', true, $new_invoice_method)
                );
                $this->redirect($this->base_uri);
            }
        }

        // Set the invoice method, or reset when setting is disabled
        if (empty($vars) || !$this->editable_settings['inv_method']) {
            $vars = (object) ['inv_method' => $this->client->settings['inv_method']];
        }

        $this->set('vars', $vars);
        $this->set('enabled', $this->editable_settings['inv_method']);
        $this->set('delivery_methods', $delivery_methods);
    }

    /**
     * Attempts to add custom fields to a client
     *
     * @param array $vars The post data, containing custom fields
     * @return mixed An array of errors, or false if none exist
     * @see Clients::add(), Clients::edit()
     */
    private function addCustomFields(array $vars = [])
    {
        $client_id = $this->client->id;

        // Get the client's current custom fields
        $client_custom_fields = $this->Clients->getCustomFieldValues($client_id);

        // Create a list of custom field IDs to update
        $custom_fields = $this->Clients->getCustomFields($this->client->company_id, $this->client->client_group_id);
        $custom_field_ids = [];
        foreach ($custom_fields as $field) {
            if ($field->read_only) {
                continue;
            }
            $custom_field_ids[] = $field->id;
        }
        unset($field);

        // Build a list of given custom fields to update
        $custom_fields_set = [];
        foreach ($vars as $field => $value) {
            // Get the custom field ID from the name
            $field_id = preg_replace('/' . $this->custom_field_prefix . '/', '', $field, 1);

            // Set the custom field
            if ($field_id != $field && in_array($field_id, $custom_field_ids)) {
                $custom_fields_set[$field_id] = $value;
            }
        }
        unset($field, $value);

        // Set every custom field available, even if it's not given, for validation
        $deletable_fields = [];
        foreach ($custom_field_ids as $field) {
            $custom_field = $this->Clients->getCustomField($field, $this->client->company_id);
            if (!isset($custom_fields_set[$custom_field->id])) {
                // Only set custom field to validate if it is not read only
                if ($custom_field->read_only != '1' && $custom_field->show_client == '1') {
                    // Set a temp value for validation purposes
                    $custom_fields_set[$custom_field->id] = '';
                    // Set this field to be deleted
                    $deletable_fields[] = $custom_field->id;
                }
            }
        }
        unset($field_id);

        // Attempt to add/update each custom field
        $temp_field_errors = [];
        foreach ($custom_fields_set as $field_id => $value) {
            $this->Clients->setCustomField($field_id, $client_id, $value);
            $temp_field_errors[] = $this->Clients->errors();
        }
        unset($field_id, $value);

        // Delete the fields that were not given
        foreach ($deletable_fields as $field_id) {
            $this->Clients->deleteCustomFieldValue($field_id, $client_id);
        }

        // Combine multiple custom field errors together
        $custom_field_errors = [];
        for ($i = 0, $num_errors = count($temp_field_errors); $i < $num_errors; $i++) {
            // Skip any "error" that is not an array already
            if (!is_array($temp_field_errors[$i])) {
                continue;
            }

            // Change the keys of each custom field error so we can display all of them at once
            $error_keys = array_keys($temp_field_errors[$i]);
            $temp_error = [];

            foreach ($error_keys as $key) {
                $temp_error[$key . $i] = $temp_field_errors[$i][$key];
            }

            $custom_field_errors = array_merge($custom_field_errors, $temp_error);
        }

        return (empty($custom_field_errors) ? false : $custom_field_errors);
    }

    /**
     * Sets the contact partial view
     * @see ClientMain::edit()
     *
     * @param stdClass $vars The input vars object for use in the view
     * @param stdClass $contact An object representing the current contact being updated
     */
    private function setContactView(stdClass $vars, $contact = null)
    {
        $this->uses(['Countries', 'States', 'ClientGroups']);

        $contacts = [];
        $contact_fields_groups = ['required_contact_fields', 'shown_contact_fields', 'read_only_contact_fields'];

        // Set partial for contact info
        $contact_info = [
            'js_contacts' => json_encode($contacts),
            'contacts' => $this->Form->collapseObjectArray($contacts, ['first_name', 'last_name'], 'id', ' '),
            'countries' => $this->Form->collapseObjectArray(
                $this->Countries->getList(),
                ['name', 'alt_name'],
                'alpha2',
                ' - '
            ),
            'states' => $this->Form->collapseObjectArray($this->States->getList($vars->country), 'name', 'code'),
            'vars' => $vars,
            'edit' => true,
            'show_email' => true
        ];

        if (is_object($contact)) {
            $contact_info['contact'] = $contact;
        }

        // Get contact field groups
        foreach ($contact_fields_groups as $group_name) {
            if ($this->client) {
                ${$group_name} = $this->ClientGroups->getSetting($this->client->client_group_id, $group_name);

                if (${$group_name}) {
                    ${$group_name} = unserialize(base64_decode(${$group_name}->value));
                }
            }

            $contact_info[$group_name] = ${$group_name} ?? [];
        }

        // Load language for partial
        Language::loadLang('client_contacts');
        $this->set('contact_info', $this->partial('client_contacts_contact_info', $contact_info));
    }

    /**
     * Sets the contact phone number partial view
     * @see ClientMain::edit()
     *
     * @param stdClass $vars The input vars object for use in the view
     */
    private function setPhoneView(stdClass $vars)
    {
        $contact_fields_groups = ['required_contact_fields', 'shown_contact_fields', 'read_only_contact_fields'];

        // Set partial for phone numbers
        $partial_vars = [
            'numbers' => (isset($vars->numbers) ? $vars->numbers : []),
            'number_types' => $this->Contacts->getNumberTypes(),
            'number_locations' => $this->Contacts->getNumberLocations()
        ];

        // Get contact field groups
        foreach ($contact_fields_groups as $group_name) {
            if ($this->client) {
                ${$group_name} = $this->ClientGroups->getSetting($this->client->client_group_id, $group_name);

                if (${$group_name}) {
                    ${$group_name} = unserialize(base64_decode(${$group_name}->value));
                }
            }

            $partial_vars[$group_name] = ${$group_name} ?? [];
        }

        if (!in_array('phone', $shown_contact_fields) && !in_array('phone', $required_contact_fields)) {
            unset($partial_vars['number_types']['phone']);
        }
        if (!in_array('fax', $shown_contact_fields) && !in_array('fax', $required_contact_fields)) {
            unset($partial_vars['number_types']['fax']);
        }

        $this->set('phone_numbers', $this->partial('client_contacts_phone_numbers', $partial_vars));
    }

    /**
     * Sets the custom fields partial view
     * @see ClientMain::edit()
     *
     * @param stdClass $vars An stdClass object representing the client vars
     */
    private function setCustomFieldView(stdClass $vars)
    {
        // Set partial for custom fields
        $custom_fields = $this->Clients->getCustomFields($this->client->company_id, $this->client->client_group_id);
        $custom_field_values = null;

        // Swap key/value pairs for "Select" option custom fields (to display)
        foreach ($custom_fields as &$field) {
            // Swap select values
            if ($field->type == 'select' && is_array($field->values)) {
                $field->values = array_flip($field->values);
            }

            // Re-set any missing custom field values (e.g. in the case of errors) for read-only vars
            if ($field->read_only == '1' && !isset($vars->{$this->custom_field_prefix . $field->id})) {
                // Fetch the custom field values for this client
                if ($custom_field_values === null) {
                    $custom_field_values = $this->Clients->getCustomFieldValues($this->client->id);
                }

                // Set this custom field value to the client's value
                foreach ($custom_field_values as $custom_field) {
                    if ($custom_field->id == $field->id) {
                        $vars->{$this->custom_field_prefix . $field->id} = $custom_field->value;
                        break;
                    }
                }
            }
        }

        $partial_vars = [
            'vars' => $vars,
            'custom_fields' => $custom_fields,
            'custom_field_prefix' => $this->custom_field_prefix
        ];
        $this->set('custom_fields', $this->partial('client_main_custom_fields', $partial_vars));
    }

    /**
     * Sets a partial view that contains all left-column client info
     */
    private function setMyInfo()
    {
        $this->uses(['Accounts', 'Invoices', 'ManagedAccounts']);

        $client = $this->client;
        $contact = $this->contact;
        // Get client contact numbers
        $contact->numbers = $this->Contacts->getNumbers($contact->id);

        // Get available invoice delivery methods and set language for the one set for this client
        $invoice_delivery_methods = $this->Invoices->getDeliveryMethods($client->id, $client->client_group_id, true);
        $invoice_method_language = (isset($invoice_delivery_methods[$client->settings['inv_method']])
            ? $invoice_delivery_methods[$client->settings['inv_method']]
            : ''
        );

        // Check whether payment types used for payment accounts are enabled
        $show_autodebit = true;
        if ((!isset($this->client->settings['payments_allowed_ach'])
                || $this->client->settings['payments_allowed_ach'] != 'true')
            && (!isset($this->client->settings['payments_allowed_cc'])
                || $this->client->settings['payments_allowed_cc'] != 'true')
        ) {
            // If the client has no payment accounts, don't show the autodebit section
            if (count($this->Accounts->getAllCcByClient($this->client->id)) === 0
                && count($this->Accounts->getAllCcByClient($this->client->id)) === 0
            ) {
                $show_autodebit = false;
            }
        }

        $myinfo_settings = [
            'invoice' => [
                'enabled' => ('true' == $client->settings['client_set_invoice']),
                'description' => Language::_('ClientMain.myinfo.setting_invoices', true, $invoice_method_language)
            ],
            'autodebit' => [
                'enabled' => $show_autodebit,
                'description' => $this->getAutodebitDescription()
            ]
        ];

        if (!$this->hasPermission('_invoice_delivery')) {
            unset($myinfo_settings['invoice']);
        }
        if (!$this->hasPermission('client_accounts')) {
            unset($myinfo_settings['autodebit']);
        }

        $number_types = $this->Contacts->getNumberTypes();
        $number_locations = $this->Contacts->getNumberLocations();

        // Get client contacts
        $contacts = array_merge(
            $this->Contacts->getAll($this->client->id, 'billing'),
            $this->Contacts->getAll($this->client->id, 'other')
        );

        // Check if the current session is a manager or a primary contact
        $is_manager = !empty($this->Session->read('blesta_contact_id'));

        // Get accounts managed by the client
        $managed_accounts = false;
        if ($this->hasPermission('_managed') && !$is_manager) {
            $managed_accounts = array_slice($this->ManagedAccounts->getAll($this->client->id), 0, 4);
        }

        $this->set(
            'myinfo',
            $this->partial(
                'client_main_myinfo',
                compact(
                    'client',
                    'contact',
                    'myinfo_settings',
                    'invoice_delivery_methods',
                    'number_types',
                    'number_locations',
                    'contacts',
                    'managed_accounts'
                )
            )
        );
    }

    /**
     * AJAX Searches managed accounts
     */
    public function searchManagedAccounts()
    {
        // Ensure a valid client was given
        if (!$this->isAjax()) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $this->uses(['ManagedAccounts']);

        $search = null;
        if (isset($this->get[0])) {
            $search = $this->get[0];
        }

        if (empty($search)) {
            return false;
        }

        // Search accounts
        $results = $this->ManagedAccounts->search($this->client->id, $search, 0);

        // Build the vars
        $vars = [
            'accounts' => $results
        ];

        // Set the partial for currency amounts
        $response = $this->partial('client_main_searchmanagedaccounts', $vars);

        // JSON encode the AJAX response
        $this->outputAsJson($response);
        return false;
    }

    /**
     * AJAX Fetches the currency amounts for the my info sidebar
     */
    public function getCurrencyAmounts()
    {
        // Ensure a valid client was given
        if (!$this->isAjax()) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $this->requirePermission('_credits');

        $this->uses(['Currencies', 'Transactions']);

        $currency_code = $this->client->settings['default_currency'];
        if (isset($this->get[0]) && ($currency = $this->Currencies->get($this->get[0], $this->company_id))) {
            $currency_code = $currency->code;
        }

        // Fetch the amounts
        $amounts = [
            'total_credit' => [
                'lang' => Language::_('ClientMain.getcurrencyamounts.text_total_credits', true),
                'amount' => $this->CurrencyFormat->format(
                    $this->Transactions->getTotalCredit($this->client->id, $currency_code),
                    $currency_code
                )
            ]
        ];

        // Build the vars
        $vars = [
            'selected_currency' => $currency_code,
            'currencies' => array_unique(
                array_merge(
                    $this->Clients->usedCurrencies($this->client->id),
                    [$this->client->settings['default_currency']]
                )
            ),
            'amounts' => $amounts
        ];

        // Set the partial for currency amounts
        $response = $this->partial('client_main_getcurrencyamounts', $vars);

        // JSON encode the AJAX response
        $this->outputAsJson($response);
        return false;
    }

    /**
     * AJAX Fetch all states belonging to a given country (json encoded ajax request)
     */
    public function getStates()
    {
        $this->uses(['States']);
        // Prepend "all" option to state listing
        $states = [];
        if (isset($this->get[0])) {
            $states = (array) $this->Form->collapseObjectArray($this->States->getList($this->get[0]), 'name', 'code');
        }

        echo json_encode($states);
        return false;
    }

    /**
     * Retrieves the autodebit language description based on the payment account settings
     *
     * @return string The autodebit language description
     */
    private function getAutodebitDescription()
    {
        $client = $this->client;
        #
        # TODO: Clean this up... -- BEGIN
        #
        #
        // Set autodebit/invoice language based on settings
        $autodebit_description = Language::_('ClientMain.myinfo.setting_autodebit_disabled', true);
        if (('true' == $client->settings['autodebit'])
            && ($debit_account = $this->Clients->getDebitAccount($client->id))
        ) {
            $autodebit_days_before_due = $client->settings['autodebit_days_before_due'];
            $autodebit_description = Language::_('ClientMain.myinfo.setting_autodebit_enabled', true);
            $autodebit_account_description = '';

            // Set autodebit language based on account
            switch ($debit_account->type) {
                case 'cc':
                    if (($autodebit_account = $this->Accounts->getCc($debit_account->account_id))) {
                        $card_types = $this->Accounts->getCcTypes();
                        $card_type = (isset($card_types[$autodebit_account->type])
                            ? $card_types[$autodebit_account->type]
                            : ''
                        );

                        // Set the language based on how many days before due. Zero, one, or more
                        if ($autodebit_days_before_due == 0) {
                            $autodebit_account_description = Language::_(
                                'ClientMain.myinfo.setting_autodebit_cc_zero_days',
                                true,
                                $card_type,
                                $autodebit_account->last4
                            );
                        } elseif ($autodebit_days_before_due == 1) {
                            $autodebit_account_description = Language::_(
                                'ClientMain.myinfo.setting_autodebit_cc_one_day',
                                true,
                                $card_type,
                                $autodebit_account->last4
                            );
                        } else {
                            $autodebit_account_description = Language::_(
                                'ClientMain.myinfo.setting_autodebit_cc_days',
                                true,
                                $card_type,
                                $autodebit_account->last4,
                                $autodebit_days_before_due
                            );
                        }
                    }
                    break;
                case 'ach':
                    if (($autodebit_account = $this->Accounts->getAch($debit_account->account_id))) {
                        $account_types = $this->Accounts->getAchTypes();
                        $account_type = (isset($account_types[$autodebit_account->type])
                            ? $account_types[$autodebit_account->type]
                            : ''
                        );

                        if ($autodebit_days_before_due == 0) {
                            $autodebit_account_description = Language::_(
                                'ClientMain.myinfo.setting_autodebit_ach_zero_days',
                                true,
                                $account_type,
                                $autodebit_account->last4
                            );
                        } elseif ($autodebit_days_before_due == 1) {
                            $autodebit_account_description = Language::_(
                                'ClientMain.myinfo.setting_autodebit_ach_one_day',
                                true,
                                $account_type,
                                $autodebit_account->last4
                            );
                        } else {
                            $autodebit_account_description = Language::_(
                                'ClientMain.myinfo.setting_autodebit_ach_days',
                                true,
                                $account_type,
                                $autodebit_account->last4,
                                $autodebit_days_before_due
                            );
                        }
                    }
                    break;
            }

            // Combine the autodebit descriptions
            $autodebit_description = $this->Html->concat(' ', $autodebit_description, $autodebit_account_description);
        }
        #
        # TODO: Clean this up... -- END
        #
        #
        return $autodebit_description;
    }

    /**
     * Sets the default language for this session
     */
    public function setLanguage()
    {
        $this->uses(['Languages']);

        $this->setClientLanguage($this->post['language_code']);

        $this->redirect(isset($this->post['redirect_uri']) ? $this->post['redirect_uri'] : $this->base_uri);
    }
}
