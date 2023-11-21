<?php

/**
 * Client management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Clients extends AppModel
{
    /**
     * Initialize Clients
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['clients']);
    }

    /**
     * Creates a client account including user login, and contact. Sends client
     * welcome email if configured to do so.
     *
     * @param array $vars An array of client info including:
     *
     *  - username The username for this user. Must be unique across all companies for this installation.
     *  - new_password The password for this user
     *  - confirm_password The password for this user
     *  - client_group_id The client group this user belongs to
     *  - status The status of this client ('active', 'inactive',' 'fraud') (optional, default active)
     *  - first_name The first name of this contact
     *  - last_name The last name of this contact
     *  - title The business title for this contact (optional)
     *  - company The company/organization this contact belongs to (optional)
     *  - email This contact's email address
     *  - address1 This contact's address (optional)
     *  - address2 This contact's address line two (optional)
     *  - city This contact's city (optional)
     *  - state The 3-character ISO 3166-2 subdivision code, requires country (optional)
     *  - zip The zip/postal code for this contact (optional)
     *  - country The 2-character ISO 3166-1 country code, required if state is given (optional)
     *  - numbers An array of number data including (optional):
     *      - number The phone number to add
     *      - type The type of phone number 'phone', 'fax' (optional, default 'phone')
     *      - location The location of this phone line 'home', 'work', 'mobile' (optional, default 'home')
     *  - custom An array of custom fields in key/value format where each
     *      key is the custom field ID and each value is the value
     *  - settings An array of client settings including:
     *      - default_currency
     *      - language
     *      - username_type
     *      - tax_id
     *      - tax_exempt
     *  - send_registration_email 'true' to send client welcome email (default), 'false' otherwise
     *  - send_registration_message 'true' to send client welcome message (default), 'false' otherwise
     * @return stdClass A stdClass object representing the client, void on error
     * @see Users::add()
     * @see Clients::add()
     * @see Contacts::add()
     */
    public function create(array $vars)
    {
        // Trigger the Clients.createBefore event
        extract($this->executeAndParseEvent('Clients.createBefore', ['vars' => $vars]));

        Loader::loadModels($this, ['Users', 'Contacts', 'Companies', 'ClientGroups']);
        Loader::loadHelpers($this, ['Form']);

        // Begin new transaction
        $this->begin();

        if (($settings = $this->ClientGroups->getSettings($vars['client_group_id']))) {
            $settings = $this->Form->collapseObjectArray($settings, 'value', 'key');
        }
        $vars['verify'] =
            isset($vars['verify'])
            ? (bool) $vars['verify']
            : ((isset($settings['email_verification']) ? $settings['email_verification'] : 'false') == 'true');

        // Create a new user
        $user_vars = [
            'username' => (
            isset($vars['settings']['username_type']) && $vars['settings']['username_type'] == 'email'
            ) ? $vars['email'] : $vars['username'],
            'new_password' => $vars['new_password'],
            'confirm_password' => $vars['confirm_password'],
            'verify' => $vars['verify']
        ];

        $user_id = $this->Users->add($user_vars);
        $user_errors = $this->Users->errors();
        $client_errors = [];
        $contact_errors = [];
        $custom_field_errors = [];

        // The user creation must be successful in order to create a client
        if (empty($user_errors)) {
            $vars['user_id'] = $user_id;

            // Create the client
            $client_id = $this->add($vars);
            $client_errors = $this->errors();

            // Add client custom fields
            $custom_fields = $this->getCustomFields(Configure::get('Blesta.company_id'), $vars['client_group_id']);
            if (!empty($custom_fields)) {
                foreach ($custom_fields as $field) {
                    $this->setCustomField(
                        $field->id,
                        $client_id,
                        isset($vars['custom']) && array_key_exists($field->id, $vars['custom'])
                        ? $vars['custom'][$field->id]
                        : (isset($field->default) ? $field->default : null)
                    );

                    if (($custom_field_errors = $this->errors())) {
                        break;
                    }
                }
            }

            // Create the contact (and add any phone numbers)
            $vars['client_id'] = $client_id;
            // Contacts.user_id would be the contact's login credentials, so unset
            unset($vars['user_id']);
            $contact_id = $this->Contacts->add($vars);
            $contact_errors = $this->Contacts->errors();

            // Contact creation must be successful in order to create settings
            if (empty($contact_errors)) {
                $client_settings = [];
                if (isset($vars['settings'])) {
                    $client_settings = $vars['settings'];
                }

                // Always set to address invoices to the primary contact
                $client_settings['inv_address_to'] = $contact_id;

                $fields = [
                    'autodebit', 'autosuspend', 'default_currency', 'inv_address_to',
                    'inv_method', 'language', 'tax_exempt', 'tax_id', 'username_type',
                    'send_registration_email', 'receive_email_marketing',
                    'payments_allowed_ach', 'payments_allowed_cc'
                ];
                $this->setSettings($client_id, $client_settings, $fields);
            }
        }

        $errors = array_merge(
            ($user_errors ? $user_errors : []),
            ($client_errors ? $client_errors : []),
            ($contact_errors ? $contact_errors : []),
            ($custom_field_errors ? $custom_field_errors : [])
        );

        if (!empty($errors)) {
            // Error, rollback
            $this->rollBack();

            $this->Input->setErrors($errors);
        } else {
            // Success, commit
            $this->commit();

            if (isset($vars['settings']['tax_id'])) {
                $this->setSettings(
                    $vars['client_id'],
                    ['tax_id' => $vars['settings']['tax_id']],
                    ['tax_id', 'tax_exempt']
                );
            }

            $client = $this->get($client_id);

            // Send Account Registration email
            if (!isset($vars['send_registration_email']) || $vars['send_registration_email'] == 'true') {
                Loader::loadModels($this, ['Emails']);

                $template_name = 'account_welcome';
                $email_template = $this->Emails->getByType(
                    $client->company_id,
                    $template_name,
                    $client->settings['language']
                );
                if ($email_template->status == 'active') {
                    $company = $this->Companies->get($client->company_id);

                    // Get the company hostname
                    $hostname = isset($company->hostname) ? $company->hostname : '';

                    $tags = [
                        'contact' => $this->Contacts->get($contact_id),
                        'company' => $company,
                        'username' => $client->username,
                        'password' => $vars['new_password'],
                        'client_url' => $hostname . WEBDIR . Configure::get('Route.client') . '/'
                    ];

                    $options = ['to_client_id' => $client->id];
                    $this->Emails->send(
                        $template_name,
                        $client->company_id,
                        $client->settings['language'],
                        $client->email,
                        $tags,
                        null,
                        null,
                        null,
                        $options
                    );
                }
            }

            // Send Account Registration message
            if (!isset($vars['send_registration_message']) || $vars['send_registration_message'] == 'true') {
                Loader::loadModels($this, ['Messages', 'MessengerManager']);

                // Build tags array
                $company = $this->Companies->get($client->company_id);
                $hostname = isset($company->hostname) ? $company->hostname : '';
                $tags = [
                    'contact' => $this->Contacts->get($contact_id),
                    'company' => $company,
                    'username' => $client->username,
                    'password' => $vars['new_password'],
                    'client_url' => $hostname . WEBDIR . Configure::get('Route.client') . '/'
                ];
                $users = [$user_id];

                // Send message
                $template_name = 'account_welcome';
                $this->MessengerManager->send($template_name, $tags, $users);
            }

            // Log that the client was created
            $this->logger->info(
                'Created Client',
                ['client_id' => $client_id, 'user_id' => $user_id, 'contact_id' => $contact_id]
            );

            // Trigger the Clients.createAfter event
            $this->executeAndParseEvent('Clients.createAfter', ['client' => $client]);

            return $client;
        }
    }

    /**
     * Add a client to the clients table
     *
     * @param array $vars An array of client info including:
     *
     *  - id_code The client's reference ID code (for display purposes)
     *  - user_id The client's user ID
     *  - client_group_id The client group this user belongs to
     *  - status The status of this client ('active', 'inactive',' 'fraud') (optional, default active)
     * @return int The client ID, or void on faliure
     */
    public function add(array $vars)
    {
        // Trigger the Clients.addBefore event
        extract($this->executeAndParseEvent('Clients.addBefore', ['vars' => $vars]));

        // Note, you can't add a primary_account_id or primary_account_type when
        // adding a client because to create an account you must first create a contact
        // and to create a contact you must first create a client...
        // Fetch company settings on clients
        Loader::loadComponents($this, ['SettingsCollection']);
        $client_group_settings = $this->SettingsCollection->fetchClientGroupSettings($vars['client_group_id'] ?? null);
        $company_settings = $this->SettingsCollection->fetchSettings(null, Configure::get('Blesta.company_id'));

        // Creates subquery to calculate the next client ID value on the fly
        $sub_query = new Record();

        /*
          $values = array($company_settings['clients_start'], $company_settings['clients_increment'],
          $company_settings['clients_start'], $company_settings['clients_start'],
          $company_settings['clients_increment'], $company_settings['clients_start'],
          $company_settings['clients_pad_size'], $company_settings['clients_pad_str']);
         */
        $values = [
            $client_group_settings['clients_start'] ?? $company_settings['clients_start'],
            $client_group_settings['clients_increment'] ?? $company_settings['clients_increment'],
            $client_group_settings['clients_start'] ?? $company_settings['clients_start']
        ];

        /*
          $sub_query->select(array("LPAD(IFNULL(GREATEST(MAX(t1.id_value),?)+?,?),
          GREATEST(CHAR_LENGTH(IFNULL(GREATEST(MAX(t1.id_value),?)+?,?)),?),?)"), false)->
         */
        $sub_query->select(['IFNULL(GREATEST(MAX(t1.id_value),?)+?,?)'], false)->
            appendValues($values)->
            from(['clients' => 't1'])->
            innerJoin('client_groups', 'client_groups.id', '=', 't1.client_group_id', false)->
            where('client_groups.company_id', '=', Configure::get('Blesta.company_id'))->
            where('t1.id_format', '=', $client_group_settings['clients_format'] ?? $company_settings['clients_format']);
        // run get on the query so $sub_query->values are built
        $sub_query->get();

        // Copy record so that it is not overwritten during validation
        $record = clone $this->Record;
        $this->Record->reset();

        $vars['id_format'] = $client_group_settings['clients_format'] ?? $company_settings['clients_format'];
        // id_value will be calculated on the fly using a subquery
        $vars['id_value'] = $sub_query;

        if ($this->validateClient($vars, false, true)) {
            // Set the record back
            $this->Record = $record;
            unset($record);

            // Assign subquery values to this record component
            $this->Record->appendValues($sub_query->values);
            // Ensure the subquery value is set first because its the first value
            $vars = array_merge(['id_value' => null], $vars);

            // Add a client
            $fields = ['id_format', 'id_value', 'user_id', 'client_group_id', 'status'];
            $this->Record->insert('clients', $vars, $fields);

            $client_id = $this->Record->lastInsertId();

            // Trigger the Clients.addAfter event
            $this->executeAndParseEvent('Clients.addAfter', ['client_id' => $client_id, 'vars' => $vars]);

            return $client_id;
        }
    }

    /**
     * Edit a client from the clients table
     *
     * @param int $client_id The client's ID
     * @param array $vars An array of client info (all fields optional) including:
     *
     *  - id_code The client's reference ID code (for display purposes)
     *  - user_id The client's user ID
     *  - client_group_id The client group this user belongs to
     *  - status The status of this client ('active', 'inactive',' 'fraud')
     */
    public function edit($client_id, array $vars)
    {
        // Trigger the Clients.editBefore event
        extract($this->executeAndParseEvent('Clients.editBefore', ['client_id' => $client_id, 'vars' => $vars]));

        // Validate client_id
        $vars['client_id'] = $client_id;
        if ($this->validateClient($vars, true, true)) {
            // Get the client state prior to update
            $client = $this->get($client_id, false);

            // Update a client
            $fields = ['user_id', 'client_group_id', 'status'];
            $this->Record->where('id', '=', $client_id)->update('clients', $vars, $fields);

            // Log that the client was updated
            $log_vars = array_intersect_key($vars, array_flip($fields));
            $this->logger->info('Updated Client', array_merge($log_vars, ['id' => $client_id]));

            // Trigger the event
            $eventFactory = $this->getFromContainer('util.events');
            $eventListener = $eventFactory->listener();
            $eventListener->register('Clients.edit');
            $eventListener->trigger(
                $eventFactory->event(
                    'Clients.edit',
                    ['client_id' => $client_id, 'vars' => $vars, 'old_client' => $client]
                )
            );

            // Trigger the Clients.editAfter event
            $this->executeAndParseEvent(
                'Clients.editAfter',
                ['client_id' => $client_id, 'vars' => $vars, 'old_client' => $client]
            );
        }
    }

    /**
     * Permanently removes a client from the clients table. CAUTION: Deleting a
     * client will cause all invoices, services, transactions, etc. attached to
     * that client to become inaccessible.
     *
     * @param int $client_id The client ID to permanently remove from the system
     */
    public function delete($client_id)
    {
        // Trigger the Clients.deleteBefore event
        extract($this->executeAndParseEvent('Clients.deleteBefore', ['client_id' => $client_id]));

        $rules = [
            'client_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => Language::_('Clients.!error.client_id.exists', true)
                ],
                'deleteable' => [
                    'rule' => [[$this, 'validateClientDeleteable']],
                    'message' => Language::_('Clients.!error.client_id.deleteable', true)
                ]
            ]
        ];

        $this->Input->setRules($rules);
        $vars = ['client_id' => $client_id];

        if ($this->Input->validates($vars)) {
            $client = $this->get($client_id, false);
            $this->Record->from('clients')->where('id', '=', $client_id)->delete();

            #
            # TODO: Add this log to a new Logger event triggered on Clients.delete
            #
            // Log that the client was deleted
            $this->logger->info('Deleted Client', (array)$client);

            // Trigger the Clients.deleteAfter event
            $this->executeAndParseEvent('Clients.deleteAfter', ['client_id' => $client_id, 'old_client' => $client]);
        }
    }

    /**
     * Add custom client field
     *
     * @param array An array of custom field info including:
     *
     *  - client_group_id The client group ID the field will belong to
     *  - name The name of the custom field
     *  - link The link of the field name
     *  - is_lang Whether or not 'name' is a language definition (optional, default 0)
     *  - type The type of field (one of: 'text','checkbox','select','textarea') (optional, default 'text')
     *  - values Values for the field (text; if 'type' is 'select', values must be a key=>value array of option=>value)
     *  - default The default value (optional, should be one of the given select/checkbox values,
     *      or any for text/textarea)
     *  - regex Custom regex required by this field (optional, default null)
     *  - show_client Whether or not to allow the client to updated this field (optional, default 0)
     *  - read_only Whether or not the field is read only (optional, default 0)
     *  - encrypted Whether or not to encrypt values associated with this custom field (optional, default 0)
     * @return int The custom field ID, void on failure
     */
    public function addCustomField(array $vars)
    {
        $this->Input->setRules($this->getCustomFieldRules($vars));

        if ($this->Input->validates($vars)) {
            // Add a custom field
            $fields = [
                'client_group_id', 'name', 'link', 'is_lang', 'type', 'values',
                'default', 'regex', 'show_client', 'read_only', 'encrypted'
            ];
            $this->Record->insert('client_fields', $vars, $fields);
            return $this->Record->lastInsertId();
        }
    }

    /**
     * Edit custom client field
     *
     * @param int $field_id The ID of the custom field to update
     * @param array An array of custom field info including:
     *
     *  - client_group_id The client group ID the field will belong to
     *  - name The name of the custom field
     *  - link The link of the field name
     *  - is_lang Whether or not 'name' is a language definition (optional)
     *  - type The type of field (optional, one of: 'text','checkbox','select','textarea')
     *  - values Values for the field (optional; text; if 'type' is 'select',
     *      values must be a key=>value array of option=>value)
     *  - default The default value (optional, should be one of the given select/checkbox values,
     *      or any for text/textarea)
     *  - regex Custom regex required by this field (optional)
     *  - show_client Whether or not to allow the client to updated this field (optional)
     *  - read_only Whether or not the field is read only (optional)
     *  - encrypted Whether or not to encrypt values associated with this custom field (optional)
     * @return int The custom field ID, void on failure
     */
    public function editCustomField($field_id, array $vars)
    {
        $rules = $this->getCustomFieldRules($vars);
        $rules['client_field_id'] = [
            'exists' => [
                'rule' => [[$this, 'validateExists'], 'id', 'client_fields'],
                'message' => $this->_('Clients.!error.custom_field_id.exists')
            ]
        ];

        $this->Input->setRules($rules);

        $vars['client_field_id'] = $field_id;

        if ($this->Input->validates($vars)) {
            // Update a custom field
            $fields = [
                'client_group_id', 'name', 'link', 'is_lang', 'type', 'values',
                'default', 'regex', 'show_client', 'read_only', 'encrypted'
            ];
            $this->Record->where('id', '=', $field_id)->update('client_fields', $vars, $fields);
        }
    }

    /**
     * Permanently removes a custom client field and all entries for that field
     *
     * @param int $field_id The ID of the custom field to delete
     */
    public function deleteCustomField($field_id)
    {
        // Delete the custom field and all client values associated with it
        $this->Record->begin();
        $this->Record->from('client_fields')->where('id', '=', $field_id)->delete();
        $this->Record->from('client_values')->where('client_field_id', '=', $field_id)->delete();
        $this->Record->commit();
    }

    /**
     * Retrieve a list of all custom field types
     *
     * @return array A key=>value array of custom field types
     */
    public function getCustomFieldTypes()
    {
        return [
            'text' => $this->_('Clients.getCustomFieldTypes.textbox'),
            'checkbox' => $this->_('Clients.getCustomFieldTypes.checkbox'),
            'select' => $this->_('Clients.getCustomFieldTypes.dropdown'),
            'textarea' => $this->_('Clients.getCustomFieldTypes.textarea')
        ];
    }

    /**
     * Retrieves a list of all custom client fields by company
     *
     * @param int $company_id The company ID
     * @param int $client_group_id The client group ID of fields to fetch (optional, default null for all)
     * @param array $options An array of options to filter results on including:
     *
     *  - show_client (1 to return only fields shown to clients, 0 to return all fields not shown to clients)
     *  - read_only (1 to show only fields set to read-only, 0 to return only fields not set to read-only)
     * @return array An array of stdClass custom field objects
     */
    public function getCustomFields($company_id, $client_group_id = null, array $options = null)
    {
        $fields = ['client_fields.id', 'client_fields.client_group_id', 'client_fields.name', 'client_fields.link',
            'client_fields.is_lang', 'client_fields.type', 'client_fields.values', 'client_fields.default',
            'client_fields.show_client', 'client_fields.read_only', 'client_fields.regex', 'client_fields.encrypted'
        ];

        $this->Record->select($fields)->from('client_fields')->
            innerJoin('client_groups', 'client_fields.client_group_id', '=', 'client_groups.id', false)->
            where('client_groups.company_id', '=', $company_id);

        // Fetch only custom fields for a specific client group
        if ($client_group_id != null) {
            $this->Record->where('client_fields.client_group_id', '=', $client_group_id);
        }

        // Set filter options
        if (!empty($options)) {
            foreach ($options as $key => $value) {
                $this->Record->where('client_fields.' . $key, '=', $value);
            }
        }
        $custom_fields = $this->Record->fetchAll();

        if ($custom_fields) {
            foreach ($custom_fields as &$field) {
                // Set name to language define
                $field->real_name = $field->name;
                if ($field->is_lang == '1') {
                    $field->real_name = $this->_('_CustomFields.' . $field->name);
                }

                // Set link to field name
                Loader::loadHelpers($this, ['Html']);

                $field->real_name = $this->Html->safe($field->real_name);
                if (str_contains($field->real_name, '[') && str_contains($field->real_name, ']')) {
                    $real_name_parts = explode('[', $field->real_name, 2);
                    $real_name_parts = array_merge([$real_name_parts[0]], explode(']', $real_name_parts[1], 2));

                    if (count($real_name_parts) == 3) {
                        $real_name_parts[1] = '<a href="' . $this->Html->safe($field->link) . '" target="_blank">'
                            . $this->Html->safe($real_name_parts[1]) . '</a>';
                        $field->real_name = implode('', $real_name_parts);
                    }
                }

                // Unserialize values
                if ($field->values != null) {
                    $field->values = unserialize($field->values);
                }
            }
        }

        return $custom_fields;
    }

    /**
     * Retrieves a single custom field
     *
     * @param int $field_id The custom field ID to fetch
     * @param int $company_id The company ID (optional, default null)
     * @return mixed An stdClass of key=>value pairs, or false if the custom field does not exist
     */
    public function getCustomField($field_id, $company_id = null)
    {
        $fields = ['client_fields.id', 'client_fields.client_group_id', 'client_fields.name', 'client_fields.link',
            'client_fields.is_lang', 'client_fields.type', 'client_fields.values', 'client_fields.default',
            'client_fields.show_client', 'client_fields.read_only', 'client_fields.regex', 'client_fields.encrypted'
        ];

        $this->Record->select($fields)->from('client_fields')->
            innerJoin('client_groups', 'client_groups.id', '=', 'client_fields.client_group_id', false)->
            where('client_fields.id', '=', $field_id);

        if ($company_id != null) {
            $this->Record->where('client_groups.company_id', '=', $company_id);
        }

        $custom_field = $this->Record->fetch();

        if ($custom_field) {
            // Set name to language define
            $custom_field->real_name = $custom_field->name;
            if ($custom_field->is_lang == '1') {
                $custom_field->real_name = $this->_('_CustomFields.' . $custom_field->name);
            }

            // Set link to field name
            if (str_contains($custom_field->real_name, '[') && str_contains($custom_field->real_name, ']')) {
                Loader::loadHelpers($this, ['Html']);

                $real_name_parts = explode('[', $custom_field->real_name, 2);
                $real_name_parts = array_merge([$real_name_parts[0]], explode(']', $real_name_parts[1], 2));

                if (count($real_name_parts) == 3) {
                    $real_name_parts[1] = '<a href="' . $this->Html->safe($custom_field->link) . '" target="_blank">'
                        . $this->Html->safe($real_name_parts[1]) . '</a>';
                    $custom_field->real_name = implode('', $real_name_parts);
                }
            }

            // Unserialize values
            if ($custom_field->values != null) {
                $custom_field->values = unserialize($custom_field->values);
            }
        }

        return $custom_field;
    }

    /**
     * Sets the given value for the given custom client field
     *
     * @param int $field_id The ID of the custom client field
     * @param int $client_id The client ID the custom field value belongs to
     * @param string $value The value to assign to this client
     */
    public function setCustomField($field_id, $client_id, $value)
    {
        // Set the custom field's name for errors
        $custom_field = $this->getCustomField($field_id);

        // Set custom field values
        $vars = [
            'client_field_id' => $field_id,
            'client_id' => $client_id,
            'value' => $value,
            'encrypted' => $custom_field ? $custom_field->encrypted : 0
        ];

        if ($this->validateCustomField($field_id, $value, $client_id)) {
            if ($vars['value'] === null) {
                $vars['value'] = '';
            }

            if ($vars['encrypted'] == '1') {
                $vars['value'] = $this->systemEncrypt($vars['value']);
            }

            // Set a custom field
            $fields = ['client_field_id', 'client_id', 'value', 'encrypted'];
            $this->Record->duplicate('value', '=', $vars['value'])->
                duplicate('encrypted', '=', $vars['encrypted'])->
                insert('client_values', $vars, $fields);
        }
    }

    /**
     * Deletes a given client field value
     *
     * @param int $client_field_id The ID of the custom client field
     * @param int $client_id The client ID the custom field value belongs to
     */
    public function deleteCustomFieldValue($client_field_id, $client_id)
    {
        // Delete the custom client field value
        $this->Record->from('client_values')->
            where('client_field_id', '=', $client_field_id)->
            where('client_id', '=', $client_id)->
            delete();
    }

    /**
     * Deletes all custom field values for the given client
     *
     * @param int $client_id The ID of the client whose custom client values to delete
     */
    public function deleteCustomFieldValues($client_id)
    {
        $this->Record->from('client_values')->where('client_id', '=', $client_id)->delete();
    }

    /**
     * Fetches all custom field values assigned to a given client
     *
     * @param int $client_id The client ID
     * @return mixed An array of stdClass objects representing each custom field, or false if none exist
     */
    public function getCustomFieldValues($client_id)
    {
        $fields = ['client_values.client_id', 'client_values.value', 'client_values.encrypted' => 'value_encrypted',
            'client_fields.id', 'client_fields.client_group_id', 'client_fields.name',
            'client_fields.is_lang', 'client_fields.type', 'client_fields.values', 'client_fields.default',
            'client_fields.regex', 'client_fields.show_client', 'client_fields.read_only', 'client_fields.encrypted'
        ];

        $custom_fields = $this->Record->select($fields)->from('client_values')->
            innerJoin('client_fields', 'client_fields.id', '=', 'client_values.client_field_id', false)->
            where('client_id', '=', $client_id)->fetchAll();

        if ($custom_fields) {
            foreach ($custom_fields as &$field) {
                // Decrypt any encrypted custom fields
                if ($field->encrypted == '1' && $field->value_encrypted == '1') {
                    $field->value = $this->systemDecrypt($field->value);
                }

                // Set name to language define
                $field->real_name = $field->name;
                if ($field->is_lang == '1') {
                    $field->real_name = $this->_('_CustomFields.' . $field->name);
                }

                // Unserialize values
                if ($field->values != null) {
                    $field->values = unserialize($field->values);
                }
            }
        }

        return $custom_fields;
    }

    /**
     * Sets restricted package access for a client
     *
     * @param int $client_id The ID of the client whose restricted package access to set
     * @param array A list of package IDs to assign to this client
     */
    public function setRestrictedPackages($client_id, array $package_ids)
    {
        $rules = [
            'client_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => $this->_('Clients.!error.client_id.exists')
                ]
            ],
            'package_ids' => [
                'exists' => [
                    'rule' => [[$this, 'validatePackages']],
                    'message' => $this->_('Clients.!error.package_ids.exists')
                ]
            ]
        ];

        $this->Input->setRules($rules);

        $vars = ['client_id' => $client_id, 'package_ids' => $package_ids];

        if ($this->Input->validates($vars)) {
            // Remove current package access
            $this->deleteRestrictedPackages($client_id);

            // Add new package access
            foreach ($package_ids as $package_id) {
                $this->Record->insert('client_packages', ['client_id' => $client_id, 'package_id' => $package_id]);
            }
        }
    }

    /**
     * Removes all restricted package access for the given client
     *
     * @param int $client_id The ID of the client whose package access to remove
     */
    public function deleteRestrictedPackages($client_id)
    {
        $this->Record->from('client_packages')->where('client_id', '=', $client_id)->delete();
    }

    /**
     * Fetches all restricted package IDs accessible by the given client
     *
     * @param int $client_id The ID of the client whose restricted packages to fetch
     * @return array A list of stdClass objects each representing a restricted package ID
     */
    public function getRestrictedPackages($client_id)
    {
        return $this->Record->select('package_id')->from('client_packages')->
            where('client_id', '=', $client_id)->fetchAll();
    }

    /**
     * Validates that the given packages exist
     *
     * @param array $package_ids The IDs of the packages to check
     * @return bool True if the given packages exist, false otherwise
     */
    public function validatePackages(array $package_ids)
    {
        foreach ($package_ids as $package_id) {
            if (!$this->validateExists($package_id, 'id', 'packages')) {
                return false;
            }
        }
        return true;
    }

    /**
     * Add a note for the given client
     *
     * @param int $client_id The ID of the client to attach the note to
     * @param int $staff_id The ID of the staff member attempting to create the note, null for system
     * @param array $vars An array of note info including:
     *
     *  - title The title of the note
     *  - description The description of the note
     *  - stickied Whether this note is sticked or not (1 or 0, optional, default 0)
     * @return int The ID for this note, void on failure
     */
    public function addNote($client_id, $staff_id, array $vars)
    {
        // Trigger the Clients.addNoteBefore event
        extract($this->executeAndParseEvent(
            'Clients.addNoteBefore',
            ['client_id' => $client_id, 'staff_id' => $staff_id, 'vars' => $vars]
        ));

        $vars['client_id'] = $client_id;

        if ($staff_id != null) {
            $vars['staff_id'] = $staff_id;
        }

        $vars['date_added'] = date('Y-m-d H:i:s');
        $vars['date_updated'] = $vars['date_added'];

        $this->Input->setRules($this->getNoteRules());

        if ($this->Input->validates($vars)) {
            // Add a client note
            $fields = ['client_id', 'staff_id', 'title', 'description', 'stickied', 'date_added', 'date_updated'];
            $this->Record->insert('client_notes', $vars, $fields);

            $note_id = $this->Record->lastInsertId();

            // Trigger the Clients.addNoteAfter event
            $this->executeAndParseEvent('Clients.addNoteAfter', ['note_id' => $note_id, 'vars' => $vars]);

            return $note_id;
        }
    }

    /**
     * Edit an existing note for a client
     *
     * @param int $note_id The ID of the note
     * @param array $vars An array of note info (all optional) including:
     *
     *  - title The title of the note
     *  - description The description of the note
     *  - stickied Whether this note is sticked or not (1 or 0, optional, default 0)
     */
    public function editNote($note_id, array $vars)
    {
        // Trigger the Clients.editNoteBefore event
        extract($this->executeAndParseEvent(
            'Clients.editNoteBefore',
            ['note_id' => $note_id, 'vars' => $vars]
        ));

        $vars['date_updated'] = date('Y-m-d H:i:s');

        $rules = $this->getNoteRules();
        $rules['note_id'] = [
            'exists' => [
                'rule' => [[$this, 'validateExists'], 'id', 'client_notes'],
                'message' => $this->_('Clients.!error.note_id.exists')
            ]
        ];

        // Remove unnecessary rule constraints
        unset($rules['client_id'], $rules['staff_id']);

        $this->Input->setRules($rules);

        $vars['note_id'] = $note_id;

        if ($this->Input->validates($vars)) {
            // Get the note state prior to update
            $note = $this->getNote($note_id);

            // Update a client note
            $fields = ['title', 'description', 'stickied', 'date_updated'];
            $this->Record->where('client_notes.id', '=', $note_id)->update('client_notes', $vars, $fields);

            // Trigger the Clients.editNoteAfter event
            $this->executeAndParseEvent(
                'Clients.editNoteAfter',
                ['note_id' => $note_id, 'vars' => $vars, 'old_note' => $note]
            );
        }
    }

    /**
     * Sets the given note as unstickied
     *
     * @param int $note_id The note ID
     */
    public function unstickyNote($note_id)
    {
        $this->Record->where('id', '=', (int) $note_id)->set('stickied', '0')->update('client_notes');
    }

    /**
     * Delete the given client note
     *
     * @param int $note_id The ID of the note to delete
     */
    public function deleteNote($note_id)
    {
        // Trigger the Clients.deleteNoteBefore event
        extract($this->executeAndParseEvent('Clients.deleteNoteBefore', ['note_id' => $note_id]));

        // Get the note state prior to update
        $note = $this->getNote($note_id);

        $this->Record->from('client_notes')->where('id', '=', $note_id)->delete();

        // Trigger the Clients.deleteNoteAfter event
        $this->executeAndParseEvent('Clients.deleteNoteAfter', ['note_id' => $note_id, 'old_note' => $note]);
    }

    /**
     * Returns all notes attached to a client
     *
     * @param int $client_id The client to fetch notes for
     * @param int $page The page to return results for (optional, default 1)
     * @param string $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @return mixed An array of stdClass note objects, false if no notes exist
     */
    public function getNoteList($client_id, $page = 1, array $order_by = ['date_added' => 'DESC'])
    {
        $this->Record = $this->fetchNotes($client_id);

        // Return the results
        return $this->Record->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->fetchAll();
    }

    /**
     * Returns the count of all notes attached to a client
     *
     * @param int $client_id The client ID
     * @return int The number of notes belonging to this client
     * @see Clients::getNotes()
     */
    public function getNoteListCount($client_id)
    {
        $this->Record = $this->fetchNotes($client_id);

        return $this->Record->numResults();
    }

    /**
     * Retrieves a list of all sticked notes attached to this client
     *
     * @param int $client_id The client ID
     * @param int $max_limit The maximum number of recent stickied notes to retrieve (optional, default all)
     * @return array A list of stdClass objects representing each note
     */
    public function getAllStickyNotes($client_id, $max_limit = null)
    {
        $this->Record = $this->fetchNotes($client_id);
        $this->Record->where('client_notes.stickied', '=', '1');

        // Retrieve up to a maximum number of notes
        if ($max_limit != null) {
            $max_limit = (int) $max_limit;
            $this->Record->order(['client_notes.date_updated' => 'DESC'])->limit(max(1, $max_limit), 0);
        }

        return $this->Record->fetchAll();
    }

    /**
     * Partially constructs the query required by both Clients::getNotes() and
     * Clients::getNoteCount()
     *
     * @param int $client_id The ID of the client whose notes to fetch
     * @return Record The partially constructed query Record object
     */
    private function fetchNotes($client_id)
    {
        $fields = [
            'client_notes.id', 'client_notes.client_id', 'client_notes.staff_id', 'client_notes.title',
            'client_notes.description', 'client_notes.stickied', 'client_notes.date_added', 'client_notes.date_updated',
            'staff.first_name' => 'staff_first_name', 'staff.last_name' => 'staff_last_name'
        ];

        $this->Record->select($fields)->from('client_notes')->
            leftJoin('staff', 'client_notes.staff_id', '=', 'staff.id', false)->
            where('client_notes.client_id', '=', $client_id);

        return $this->Record;
    }

    /**
     * Returns the note specified
     *
     * @param int $note_id The ID of the note to fetch
     * @return mixed A stdClass object representing the note, false if the note does not exist
     */
    public function getNote($note_id)
    {
        return $this->Record->select([
                'id', 'client_id', 'staff_id', 'title', 'description', 'stickied',
                'date_added', 'date_updated'
            ])
            ->from('client_notes')
            ->where('id', '=', $note_id)
            ->fetch();
    }

    /**
     * Retrieves the client debit account
     *
     * @param int $client_id The ID of the client whose debit account to get
     * @return mixed A stdClass object representing the client account type, or false if none exist
     */
    public function getDebitAccount($client_id)
    {
        // Get the account ID and type of account to fetch
        $fields = ['client_id', 'account_id', 'type', 'failed_count'];
        return $this->Record->select($fields)->from('client_account')->
            where('client_id', '=', $client_id)->fetch();
    }

    /**
     * Adds a client debit account type
     * NOTE: if a debit account already exists for this client, it will be overwritten
     *
     * @param int $client_id The ID of the client whose debit account to add a debit account to
     * @param array $vars A list of client account types including:
     *
     *  - account_id The ID of the account to add
     *  - type The type of account to add ('cc' or 'ach')
     */
    public function addDebitAccount($client_id, array $vars)
    {
        // Set placeholder value for type if none given to determine the table to validate against
        $temp_type = (!empty($vars['type']) ? $vars['type'] : 'none');
        if (!isset($vars['failed_count'])) {
            $vars['failed_count'] = 0;
        }

        // Set the table to validate the account_id against
        $table = '';
        switch ($temp_type) {
            case 'cc':
            case 'ach':
                $table = 'accounts_' . $temp_type;
                break;
            default:
                $table = 'accounts_cc';
        }

        // Set rules
        $rules = [
            'client_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => $this->_('Clients.!error.client_id.exists')
                ]
            ],
            'account_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', $table],
                    'message' => $this->_('Clients.!error.account_id.exists')
                ]
            ],
            'type' => [
                'format' => [
                    'rule' => [[$this, 'validateAccountType']],
                    'message' => $this->_('Clients.!error.type.exists')
                ]
            ]
        ];

        $this->Input->setRules($rules);

        $vars['client_id'] = $client_id;

        // Add the client debit account
        if ($this->Input->validates($vars)) {
            $fields = ['client_id', 'account_id', 'type', 'failed_count'];
            $this->Record->duplicate('account_id', '=', $vars['account_id'])->
                duplicate('type', '=', $vars['type'])->
                insert('client_account', $vars, $fields);
        }
    }

    /**
     * Deletes a client debit account type
     *
     * @param int $client_id The ID of the client whose debit account to delete
     * @return bool True if the debit account was deleted, false otherwise
     */
    public function deleteDebitAccount($client_id)
    {
        $this->Record->from('client_account')->where('client_id', '=', $client_id)->delete();

        if ($this->Record->affectedRows() > 0) {
            return true;
        }
        return false;
    }

    /**
     * Increments the debit account failure count for the given client's debit account.
     * Removes the debit account if it exceeds the threshold for failures based on the "autodebit_attempts" setting.
     *
     * @param int $client_id The ID of the client to set autodebit failure
     * @param string $type The payment account type (ach, cc)
     * @param int $account_id The payment account ID
     */
    public function setDebitAccountFailure($client_id, $type = null, $account_id = null)
    {
        $attempts = $this->getSetting($client_id, 'autodebit_attempts');
        $client_account = $this->getDebitAccount($client_id);

        if (!$client_account) {
            return;
        }

        // If type or account ID then only process failure increment if they match the stored account
        if (($type !== null || $account_id !== null)
            && $client_account->type != $type
            && $client_account->account_id != $account_id
        ) {
            return;
        }

        $total_failures = $client_account->failed_count + 1;

        if ($attempts && $attempts->value <= $total_failures) {
            $this->deleteDebitAccount($client_id);

            if (!isset($this->Accounts)) {
                Loader::loadModels($this, ['Accounts']);
            }

            $account_types = $this->Accounts->getTypes();

            if ($client_account->type == 'cc') {
                $account = $this->Accounts->getCc($client_account->account_id);
            } else {
                $account = $this->Accounts->getAch($client_account->account_id);
            }

            $this->addNote(
                $client_id,
                null,
                [
                    'title' => Language::_('Clients.setDebitAccountFailure.note_title', true),
                    'description' => Language::_(
                        'Clients.setDebitAccountFailure.note_body',
                        true,
                        isset($account_types[$client_account->type]) ? $account_types[$client_account->type] : null,
                        isset($account->last4) ? $account->last4 : null
                    )
                ]
            );
        } else {
            $this->Record->where('client_id', '=', $client_id)->
                update('client_account', ['failed_count' => $total_failures]);
        }
    }

    /**
     * Resets the debit account failure count for the given client's debit account.
     *
     * @param int $client_id The ID of the client to reset autodebit failure
     * @param string $type The payment account type (ach, cc)
     * @param int $account_id The payment account ID
     */
    public function resetDebitAccountFailure($client_id, $type = null, $account_id = null)
    {
        $client_account = $this->getDebitAccount($client_id);

        // If type or account ID then only process failure increment if they match the stored account
        if ($client_account
            && ($type !== null || $account_id !== null)
            && $client_account->type != $type
            && $client_account->account_id != $account_id
        ) {
            return;
        }

        $this->Record->where('client_id', '=', $client_id)
            ->update('client_account', ['failed_count' => 0]);
    }

    /**
     * Add multiple client settings, with rule validation. If duplicate key, update the setting
     *
     * @param int $client_id The ID for the specified client
     * @param array $vars A single dimensional array of key/value pairs of settings
     */
    public function setClientSettings($client_id, array $vars)
    {
        // Attempt to fetch the company ID for this client
        $company_id = null;
        if (Configure::get('Blesta.company_id')) {
            $company_id = Configure::get('Blesta.company_id');
        }

        // Get invoice delivery methods to validate against
        Loader::loadModels($this, ['invoices']);
        $delivery_methods = $this->Invoices->getDeliveryMethods(
            $client_id,
            isset($client->group_id) ? $client->group_id : null
        );
        $invoice_delivery_methods = [];
        // Set the key of the invoice method
        foreach ($delivery_methods as $key => $value) {
            $invoice_delivery_methods[] = $key;
        }

        $rules = [
            'autodebit' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['true', 'false']],
                    'message' => $this->_('Clients.!error.autodebit.format', true)
                ]
            ],
            // Must be a valid billing/primary contact for this client
            'inv_address_to' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateBillingContact'], $client_id],
                    'message' => $this->_('Clients.!error.inv_address_to.exists', true)
                ]
            ],
            'default_currency' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateCurrencyExists'], $company_id],
                    'message' => $this->_('Clients.!error.default_currency.valid', true)
                ],
                'editable' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateClientSettingIsEditable'], 'client_set_currency', $client_id],
                    'message' => $this->_('Clients.!error.default_currency.editable', true)
                ]
            ],
            'inv_method' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', $invoice_delivery_methods],
                    'message' => $this->_('Clients.!error.inv_method.valid', true)
                ],
                'editable' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateClientSettingIsEditable'], 'client_set_invoice', $client_id],
                    'message' => $this->_('Clients.!error.inv_method.editable', true)
                ]
            ],
            'language' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateLanguageExists'], $company_id],
                    'message' => $this->_('Clients.!error.language.valid', true)
                ],
                'editable' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateClientSettingIsEditable'], 'client_set_lang', $client_id],
                    'message' => $this->_('Clients.!error.language.editable', true)
                ]
            ],
            'receive_email_marketing' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['true', 'false']],
                    'message' => $this->_('Clients.!error.receive_email_marketing.valid', true)
                ]
            ]
        ];

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            // Client editable settings
            $fields = [
                'autodebit', 'inv_address_to', 'default_currency', 'inv_method',
                'language', 'tax_id', 'receive_email_marketing'
            ];

            // Update the settings
            $this->setSettings($client_id, $vars, $fields);
        }
    }

    /**
     * Add multiple client settings, if duplicate key update the setting
     *
     * @param int $client_id The ID for the specified client
     * @param array $vars A single dimensional array of key/value pairs of settings
     * @param array $value_keys An array of key values to accept as valid fields
     */
    public function setSettings($client_id, array $vars, array $value_keys = null)
    {
        Loader::loadModels($this, ['Companies', 'TaxProviders']);
        Loader::loadComponents($this, ['SettingsCollection']);

        // Get client
        $client = $this->get($client_id);

        // Check if the client is tax exempt, if VAT validation is enabled and the tax id has been updated
        $tax_provider = $this->TaxProviders->getByCountry($client->country);
        if ($tax_provider !== false) {
            if (
                $tax_provider->isExemptionHandlerEnabled()
                && isset($vars['tax_id'])
                && in_array($client->country, $tax_provider->getCountries())
            ) {
                $tax_information = $tax_provider->getTaxInformation($vars['tax_id'], (array) $client);

                if (isset($tax_information['tax_exempt'])) {
                    $vars['tax_exempt'] = ($tax_information['tax_exempt'] ? 'true' : 'false');
                }
            }
        }

        // Filter settings
        if (!empty($value_keys)) {
            $vars = array_intersect_key($vars, array_flip($value_keys));
        }

        // Format the settings provided
        $settings = [];
        foreach ($vars as $key => $value) {
            $settings[] = ['key' => $key, 'value' => $value];
        }

        $this->saveSettings($client_id, $settings);
    }

    /**
     * Add a client setting, if duplicate key update the setting
     *
     * @param int $client_id The ID for the specified client
     * @param string $key The key for this client setting
     * @param string $value The value for this individual client setting
     * @param mixed $encrypted True to encrypt $value, false to store
     *  unencrypted, null to encrypt if currently set to encrypt
     */
    public function setSetting($client_id, $key, $value, $encrypted = null)
    {
        // Only non-null values may be saved. Use Clients::unsetSetting to remove a setting
        if ($value === null) {
            return;
        }

        $this->saveSettings($client_id, [['key' => $key, 'value' => $value, 'encrypted' => $encrypted]]);
    }

    /**
     * Delete a client setting
     *
     * @param int $client_id The ID for the specified client
     * @param string $key The key for this client setting
     */
    public function unsetSetting($client_id, $key)
    {
        // Delete the setting by providing a null value for it
        $this->saveSettings($client_id, [['key' => $key, 'value' => null]]);
    }

    /**
     * Deletes all client settings
     *
     * @param int $client_id The ID for the specified client
     */
    public function unsetSettings($client_id)
    {
        // Delete all of the client settings here
        #
        # TODO: logging all settings that were deleted may be useful, so a call to Clients::saveSettings
        # may be worthwhile in the future. Note that we will need to fetch all of the `client_settings`
        # and set their value to null, similar to Clients::unsetSetting
        #
        $this->Record->from('client_settings')->where('client_id', '=', $client_id)->delete();
    }

    /**
     * Saves client settings and performs logging
     * @see Clients::setSetting
     * @see Clients::setSettings
     * @see Clients::unsetSetting
     *
     * @param int $client_id The ID of the client whose settings to save
     * @param array $settings An array of settings, each containing an array that includes:
     *
     *  - key The key for this client setting
     *  - value The value for this individual client setting. Set to NULL to delete this setting
     *  - encrypted 1 if the value is encrypted, 0 if it is not, otherwise use the current setting's encryption status
     */
    private function saveSettings($client_id, array $settings)
    {
        // Set loggable setting fields
        $changes = [];
        $fields = ['key', 'client_id', 'value', 'encrypted'];

        // Save each setting being updated
        foreach ($settings as $setting) {
            $setting['client_id'] = $client_id;
            $setting['old_setting'] = $this->getSetting($setting['client_id'], $setting['key']);

            // Set whether the field should be encrypted based on the given value, or the existing value
            if (array_key_exists('encrypted', $setting)) {
                $setting['encrypted'] = (int)$setting['encrypted'];
            } elseif ($setting['old_setting']) {
                $setting['encrypted'] = (int)$setting['old_setting']->encrypted;
            } else {
                $setting['encrypted'] = 0;
            }

            // Encrypt the value if necessary
            if ($setting['encrypted']) {
                $setting['value'] = $this->systemEncrypt($setting['value']);
            }

            // Save or delete the setting
            if ($setting['value'] === null) {
                // null value provided, so delete the setting altogether
                $this->Record->from('client_settings')
                    ->where('key', '=', $setting['key'])
                    ->where('client_id', '=', $client_id)
                    ->delete();
            } else {
                // Save the new/updated setting
                $this->Record->duplicate('value', '=', $setting['value'])
                    ->duplicate('encrypted', '=', $setting['encrypted'])
                    ->insert('client_settings', $setting, $fields);
            }

            // Set loggable changes -- i.e. only settings that are not encrypted
            // and those that have changed
            $old_value = (isset($setting['old_setting']->value) ? $setting['old_setting']->value : null);
            if ($setting['encrypted'] === 0
                && (!$setting['old_setting'] || (int)$setting['old_setting']->encrypted === 0)
                && $old_value != $setting['value']
            ) {
                $changes[$setting['key']] = [
                    'prev' => ($setting['old_setting'] ? $setting['old_setting']->value : null),
                    'cur' => $setting['value']
                ];
            }
        }

        // Log all of the settings' previous and current values
        if (!isset($this->Logs)) {
            Loader::loadModels($this, ['Logs']);
        }

        $requestor = $this->getFromContainer('requestor');
        $this->Logs->addClientSetting([
            'client_id' => $client_id,
            'by_user_id' => $requestor->user_id,
            'ip_address' => $requestor->ip_address,
            'fields' => $changes
        ]);
    }

    /**
     * Fetch all settings that may apply to this client. Settings are inherited
     * in the order of client_settings -> client_group_settings -> company_settings -> settings
     * where "->" represents the left item inheriting (and overwriting in the
     * case of duplicates) values found in the right item.
     *
     * @param int $client_id The client ID to retrieve settings for
     * @return mixed An array of objects containg key/values for the settings, false if no records found
     */
    public function getSettings($client_id)
    {
        $max_records = Configure::get('Blesta.max_records');
        if (empty($max_records)) {
            // Default to 2^31 - 1
            $max_records = 2147483647;
        }

        // Client Settings
        $sql1 = $this->Record->select(['key', 'value', 'encrypted'])->
            select(['?' => 'level'], false)->appendValues(['client'])->
            from('client_settings')->
            where('client_id', '=', $client_id)->
            order(['NULL'], false)->
            limit($max_records)->
            get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // Client Group Settings
        $sql2 = $this->Record->select(['key', 'value', 'encrypted'])->
            select(['?' => 'level'], false)->appendValues(['client_group'])->
            from('clients')->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            innerJoin(
                'client_group_settings',
                'client_group_settings.client_group_id',
                '=',
                'client_groups.id',
                false
            )->
            where('clients.id', '=', $client_id)->
            order(['NULL'], false)->
            limit($max_records)->
            get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // Company Settings
        $sql3 = $this->Record->select(['key', 'value', 'encrypted'])->
            select(['?' => 'level'], false)->appendValues(['company'])->
            from('clients')->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            innerJoin('company_settings', 'company_settings.company_id', '=', 'client_groups.company_id', false)->
            where('clients.id', '=', $client_id)->
            where('company_settings.inherit', '=', '1')->
            order(['NULL'], false)->
            limit($max_records)->
            get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // System settings
        $sql4 = $this->Record->select(['key', 'value', 'encrypted'])->
            select(['?' => 'level'], false)->appendValues(['system'])->
            from('settings')->where('settings.inherit', '=', '1')->
            order(['NULL'], false)->
            limit($max_records)->
            get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        $settings = $this->Record->select()
            ->from([
                '((' . $sql1 . ') UNION (' . $sql2 . ') UNION (' . $sql3 . ') UNION (' . $sql4 . '))' => 'temp'
            ])
            ->group('temp.key')
            ->fetchAll();

        // Decrypt values where necessary
        for ($i = 0, $total = count($settings); $i < $total; $i++) {
            if ($settings[$i]->encrypted) {
                $settings[$i]->value = $this->systemDecrypt($settings[$i]->value);
            }
        }
        return $settings;
    }

    /**
     * Fetch a specific setting that may apply to this client. Settings are inherited
     * in the order of client_settings -> client_group_settings -> company_settings -> settings
     * where "->" represents the left item inheriting (and overwriting in the
     * case of duplicates) values found in the right item.
     *
     * @param int $client_id The client ID to retrieve settings for
     * @param string $key The setting key of the setting to fetch
     * @return mixed A stdClass object containg key/values for the settings, false if no record found
     */
    public function getSetting($client_id, $key)
    {
        $max_records = Configure::get('Blesta.max_records');
        if (empty($max_records)) {
            // Default to 2^31 - 1
            $max_records = 2147483647;
        }

        // Client Settings
        $sql1 = $this->Record->select(['key', 'value', 'encrypted'])->
            select(['?' => 'level'], false)->appendValues(['client'])->
            from('client_settings')->
            where('client_id', '=', $client_id)->
            where('client_settings.key', '=', $key)->
            order(['NULL'], false)->
            limit($max_records)->
            get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // Client Group Settings
        $sql2 = $this->Record->select(['key', 'value', 'encrypted'])->
            select(['?' => 'level'], false)->appendValues(['client_group'])->
            from('clients')->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            innerJoin(
                'client_group_settings',
                'client_group_settings.client_group_id',
                '=',
                'client_groups.id',
                false
            )->
            where('clients.id', '=', $client_id)->
            where('client_group_settings.key', '=', $key)->
            order(['NULL'], false)->
            limit($max_records)->
            get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // Company Settings
        $sql3 = $this->Record->select(['key', 'value', 'encrypted'])->
            select(['?' => 'level'], false)->appendValues(['company'])->
            from('clients')->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            innerJoin('company_settings', 'company_settings.company_id', '=', 'client_groups.company_id', false)->
            where('clients.id', '=', $client_id)->
            where('company_settings.key', '=', $key)->
            where('company_settings.inherit', '=', '1')->
            order(['NULL'], false)->
            limit($max_records)->
            get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // System settings
        $sql4 = $this->Record->select(['key', 'value', 'encrypted'])->
            select(['?' => 'level'], false)->appendValues(['system'])->
            from('settings')->
            where('settings.key', '=', $key)->
            where('settings.inherit', '=', '1')->
            order(['NULL'], false)->
            limit($max_records)->
            get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        $setting = $this->Record->select()
            ->from([
                '((' . $sql1 . ') UNION (' . $sql2 . ') UNION (' . $sql3 . ') UNION (' . $sql4 . '))' => 'temp'
            ])
            ->group('temp.key')
            ->fetch();

        if ($setting && $setting->encrypted) {
            $setting->value = $this->systemDecrypt($setting->value);
        }
        return $setting;
    }

    /**
     * Fetches a client with contact details of the primary account holder
     *
     * @param int $client_id The client ID to fetch
     * @param bool $get_settings True to fetch settings for this client (default), false otherwise
     * @return mixed An object containing client fields, false if the client does not exist.
     * @see Contacts::get(), Clients::getByUserId()
     */
    public function get($client_id, $get_settings = true)
    {
        // Load format helper for settings
        $this->ArrayHelper = $this->DataStructure->create('Array');

        $this->Record = $this->getAClient();
        $this->Record->where('clients.id', '=', $client_id);

        // Filter based on company ID
        if (Configure::get('Blesta.company_id')) {
            $this->Record->where('client_groups.company_id', '=', Configure::get('Blesta.company_id'));
        }

        $client = $this->Record->fetch();

        if ($client && $get_settings) {
            $client->settings = $this->ArrayHelper->numericToKey($this->getSettings($client->id), 'key', 'value');
        }

        return $client;
    }

    /**
     * Fetches a client with contact details of the primary account holder
     *
     * @param int $user_id The ID of the user
     * @param bool $get_settings True to fetch settings for this client, false otherwise
     * @return mixed An object containing client fields, false if the client does not exist.
     * @see Contacts::get(), Clients::getByUserId()
     */
    public function getByUserId($user_id, $get_settings = false)
    {
        // Load format helper for settings
        $this->ArrayHelper = $this->DataStructure->create('Array');

        $this->Record = $this->getAClient();

        $this->Record->leftJoin(['contacts' => 'contact_login'], 'contact_login.client_id', '=', 'clients.id', false)->
            open()->
                where('clients.user_id', '=', $user_id)->
                orWhere('contact_login.user_id', '=', $user_id)->
            close();

        // Filter based on company ID
        if (Configure::get('Blesta.company_id')) {
            $this->Record->where('client_groups.company_id', '=', Configure::get('Blesta.company_id'));
        }

        $client = $this->Record->fetch();

        if ($client && $get_settings) {
            $client->settings = $this->ArrayHelper->numericToKey($this->getSettings($client->id), 'key', 'value');
        }

        return $client;
    }

    /**
     * Partially constructs a query for fetching a single client
     *
     * @return Record A Record object representing the partial query for fetching a single client
     */
    private function getAClient()
    {
        $fields = ['clients.*', 'REPLACE(clients.id_format, ?, clients.id_value)' => 'id_code',
            'contacts.id' => 'contact_id', 'contacts.first_name', 'contacts.last_name', 'contacts.company',
            'contacts.title', 'contacts.email', 'contacts.address1', 'contacts.address2',
            'contacts.city', 'contacts.state', 'contacts.zip', 'contacts.country',
            'client_groups.name' => 'group_name', 'client_groups.company_id', 'users.username',
            'users.two_factor_mode', 'users.two_factor_key', 'users.two_factor_pin', 'users.date_added'];

        return $this->Record->select($fields)
            ->appendValues([$this->replacement_keys['clients']['ID_VALUE_TAG']])
            ->from('clients')
            ->innerJoin('client_groups', 'clients.client_group_id', '=', 'client_groups.id', false)
            ->innerJoin('contacts', 'contacts.client_id', '=', 'clients.id', false)
            ->innerJoin('users', 'users.id', '=', 'clients.user_id', false)
            ->where('contacts.contact_type', '=', 'primary');
    }

    /**
     * Fetches a list of all clients
     *
     * @param string $status The status type of the clients to fetch
     *  ('active', 'inactive', 'fraud', default null for all)
     * @param int $page The page to return results for (optional, default 1)
     * @param string $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - contact_name The (partial) name of the contact for which to fetch clients
     *  - contact_email The (partial) email address of the contact for which to fetch clients
     *  - contact_company The (partial) company name of the contact for which to fetch clients
     *  - contact_country The contact country on which to filter clients
     *  - client_group_id The client group ID on which to filter clients
     *  - invoice_method The invoice delivery method on which to filter clients
     *  - last_seen_start_date The start date of the "last seen" date range on which to filter clients
     *  - last_seen_end_date The end date of the "last seen" date range on which to filter clients
     * @return array An array of stdClass objects representing each client, or false if no results
     */
    public function getList($status = null, $page = 1, $order_by = ['id_code' => 'ASC'], array $filters = [])
    {
        $this->Record = $this->getClients(array_merge(['status' => $status], $filters));

        // If sorting by ID code, use id code sort mode
        if (isset($order_by['id_code']) && Configure::get('Blesta.id_code_sort_mode')) {
            $temp = $order_by['id_code'];
            unset($order_by['id_code']);

            foreach ((array) Configure::get('Blesta.id_code_sort_mode') as $key) {
                $order_by[$key] = $temp;
            }
        }

        // Return the results
        return $this->Record->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->fetchAll();
    }

    /**
     * Return the total number of clients returned from Clients::getList(), useful
     * in constructing pagination for the getList() method.
     *
     * @param string $status The status type of the clients to fetch
     *  ('active', 'inactive', 'fraud', default null for all)
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - contact_name The (partial) name of the contact for which to fetch clients
     *  - contact_email The (partial) email address of the contact for which to fetch clients
     *  - contact_company The (partial) company name of the contact for which to fetch clients
     *  - contact_country The contact country on which to filter clients
     *  - client_group_id The client group ID on which to filter clients
     *  - invoice_method The invoice delivery method on which to filter clients
     *  - last_seen_start_date The start date of the "last seen" date range on which to filter clients
     *  - last_seen_end_date The end date of the "last seen" date range on which to filter clients
     * @return int The total number of clients
     * @see Clients::getList()
     */
    public function getListCount($status = null, array $filters = [])
    {
        $this->Record = $this->getClients(array_merge(['status' => $status], $filters));

        // Return the number of results
        return $this->Record->numResults();
    }

    /**
     * Fetches all clients
     *
     * @param string $status The status type of the clients to fetch
     *  ('active', 'inactive', 'fraud', default null for all)
     * @param int $client_group_id The ID of the client group whose clients to fetch (optional, default null for all)
     * @return array An array of stdClass objects representing each client, or false if no results
     */
    public function getAll($status = null, $client_group_id = null)
    {
        return $this->getClients(['status' => $status, 'client_group_id' => $client_group_id])->fetchAll();
    }

    /**
     * Search clients
     *
     * @param string $query The value to search clients for
     * @param int $page The page number of results to fetch (optional, default 1)
     * @return array An array of clients that match the search criteria
     */
    public function search($query, $page = 1)
    {
        $this->Record = $this->searchClients($query);
        return $this->Record->limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->
            fetchAll();
    }

    /**
     * Return the total number of clients returned from Clients::search(), useful
     * in constructing pagination
     *
     * @param string $query The value to search clients for
     * @see Clients::search()
     */
    public function getSearchCount($query)
    {
        $this->Record = $this->searchClients($query);
        return $this->Record->numResults();
    }

    /**
     * Partially constructs the query for searching clients
     *
     * @param string $query The value to search clients for
     * @return Record The partially constructed query Record object
     * @see Clients::search(), Clients::getSearchCount()
     */
    private function searchClients($query)
    {
        $this->Record = $this->getClients();

        $sub_query_sql = $this->Record->get();
        $values = $this->Record->values;
        $this->Record->reset();

        $this->Record->select(['temp.*'])->appendValues($values)
            ->from([$sub_query_sql => 'temp'])
            ->on('contacts.contact_type', '!=', 'primary')
            ->leftJoin('contacts', 'contacts.client_id', '=', 'temp.id', false)
            ->leftJoin('client_notes', 'client_notes.client_id', '=', 'temp.id', false)
            ->like('CONVERT(temp.id_code USING utf8)', '%' . $query . '%', true, false)
            ->orLike('temp.company', '%' . $query . '%')
            ->orLike("CONCAT_WS(' ', temp.first_name, temp.last_name)", '%' . $query . '%')
            ->orLike('temp.address1', '%' . $query . '%')
            ->orLike('temp.email', '%' . $query . '%')
            ->orLike('contacts.company', '%' . $query . '%')
            ->orLike("CONCAT_WS(' ', contacts.first_name, contacts.last_name)", '%' . $query . '%')
            ->orLike('contacts.address1', '%' . $query . '%')
            ->orLike('contacts.email', '%' . $query . '%')
            ->orLike('client_notes.title', '%' . $query . '%')
            ->orLike('client_notes.description', '%' . $query . '%')
            ->group(['temp.id']);

        return $this->Record;
    }

    /**
     * Partially constructs the query required by both Clients::getList(),
     * Clients::getListCount(), Clients::getAll()
     *
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - contact_name The (partial) name of the contact for which to fetch clients
     *  - contact_email The (partial) email address of the contact for which to fetch clients
     *  - contact_company The (partial) company name of the contact for which to fetch clients
     *  - contact_country The contact country on which to filter clients
     *  - client_group_id The ID of the client group whose clients to fetch (optional, default null for all)
     *  - invoice_method The invoice delivery method on which to filter clients
     *  - last_seen_start_date The start date of the "last seen" date range on which to filter clients
     *  - last_seen_end_date The end date of the "last seen" date range on which to filter clients
     *  - status The status type of the clients to fetch ('active', 'inactive', 'fraud', default null for all)
     * @return Record The partially constructed query Record object
     */
    private function getClients(array $filters = [])
    {
        Loader::loadComponents($this, ['SettingsCollection']);

        $fields = ['clients.*', 'REPLACE(clients.id_format, ?, clients.id_value)' => 'id_code',
            'contacts.id' => 'contact_id', 'contacts.first_name',
            'contacts.last_name', 'contacts.company', 'contacts.email', 'contacts.address1', 'contacts.address2',
            'contacts.city', 'contacts.state', 'contacts.zip', 'contacts.country',
            'client_groups.name' => 'group_name', 'client_groups.company_id'];

        $this->Record->select($fields)
            ->appendValues([$this->replacement_keys['clients']['ID_VALUE_TAG']])
            ->from('clients')
            ->innerJoin('client_groups', 'clients.client_group_id', '=', 'client_groups.id', false)
            ->innerJoin('contacts', 'contacts.client_id', '=', 'clients.id', false)
            ->where('contacts.contact_type', '=', 'primary');

        // Filter based on client status
        if (!empty($filters['status'])) {
            $this->Record->where('clients.status', '=', $filters['status']);
        }

        // Filter based on client group
        if (!empty($filters['client_group_id'])) {
            $this->Record->where('clients.client_group_id', '=', $filters['client_group_id']);
        }

        // Filter based on contact name
        if (!empty($filters['contact_name'])) {
            $this->Record->where('CONCAT_WS(\' \', contacts.first_name, contacts.last_name)', 'LIKE', '%' . $filters['contact_name'] . '%');
        }

        // Filter based on contact email
        if (!empty($filters['contact_email'])) {
            $this->Record->where('contacts.email', 'LIKE', '%' . $filters['contact_email'] . '%');
        }

        // Filter based on contact company
        if (!empty($filters['contact_company'])) {
            $this->Record->where('contacts.company', 'LIKE', '%' . $filters['contact_company'] . '%');
        }

        // Filter based on contact country
        if (!empty($filters['contact_country'])) {
            $this->Record->where('contacts.country', '=', $filters['contact_country']);
        }

        // Filter based on invoice delivery method
        if (!empty($filters['invoice_method'])) {
            $this->Record->innerJoin('client_settings', 'client_settings.client_id', '=', 'clients.id', false)
                ->where('client_settings.value', '=', $filters['invoice_method'])
                ->where('client_settings.key', '=', 'inv_method');
        }

        // Filter based on last seen date range
        if (!empty($filters['last_seen_start_date']) || !empty($filters['last_seen_end_date'])) {
            $subquery_record = clone $this->Record;
            $subquery_record->reset();
            $subquery = $subquery_record->select(['log_users.user_id', 'MAX(log_users.date_updated)' => 'last_seen'])
                ->from('log_users')
                ->where('log_users.result', '=', 'success')
                ->where('log_users.company_id', '=', Configure::get('Blesta.company_id'))
                ->group('log_users.user_id')
                ->get();
            $values = $subquery_record->values;

            $this->Record->appendValues($values)
                ->innerJoin([$subquery => 'log_users'], 'log_users.user_id', '=', 'clients.user_id', false);

            // Save current timezone, we will need to restore it later
            $timezone = $this->SettingsCollection->fetchSetting(null, Configure::get('Blesta.company_id'), 'timezone');
            $timezone = array_key_exists('value', $timezone) ? $timezone['value'] : 'UTC';

            $this->Date->setTimezone($timezone, 'UTC');

            if (!empty($filters['last_seen_start_date'])) {
                $this->Record->where(
                    'log_users.last_seen',
                    '>=',
                    $this->Date->cast($filters['last_seen_start_date'] . ' 00:00:00', 'Y-m-d H:i:s')
                );
            }

            if (!empty($filters['last_seen_end_date'])) {
                $this->Record->where(
                    'log_users.last_seen',
                    '<=',
                    $this->Date->cast($filters['last_seen_end_date'] . ' 23:59:59', 'Y-m-d H:i:s')
                );
            }

            // Restore the previously saved timezone
            $this->Date->setTimezone('UTC', $timezone);
        }

        // Filter based on company ID
        if (Configure::get('Blesta.company_id')) {
            $this->Record->where('client_groups.company_id', '=', Configure::get('Blesta.company_id'));
        }

        return $this->Record;
    }

    /**
     * Fetches a specific email from the log for a given client
     *
     * @param int $client_id The client ID
     * @param int $email_log_id The email log ID of the email
     * @return mixed An stdClass object representing the email log, or false if it doesn't exist
     */
    public function getMailLogEntry($client_id, $email_log_id)
    {
        $this->Record = $this->getMailLog($client_id);

        return $this->Record->where('id', '=', $email_log_id)->fetch();
    }

    /**
     * Fetches the mail logs for a given client
     *
     * @param int $client_id The client ID
     * @param int $page The page of results to fetch
     * @param array $order_by The sort and order conditions (e.g. array("sort_field"=>"ASC"), optional)
     * @param int $sent Whether or not the email was actually sent (1 or 0, optional, default null for either)
     * @return mixed An array of stdClass objects representing client mail logs, or false if none exist
     */
    public function getMailLogList($client_id, $page = 1, $order_by = ['date_sent' => 'DESC'], $sent = null)
    {
        $this->Record = $this->getMailLog($client_id, $sent);

        return $this->Record->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->fetchAll();
    }

    /**
     * Retrieves the number of sent emails contained in the mail log
     *
     * @param int $client_id The client ID
     * @param int $sent Whether or not the email was actually sent (1 or 0, optional, default null for either)
     * @return int The number of emails contained in the log
     */
    public function getMailLogListCount($client_id, $sent = null)
    {
        return $this->getMailLog($client_id, $sent)->numResults();
    }

    /**
     * Partially constructs the query required by Clients::getMailLogList() and
     * Clients::getMailLogListCount()
     *
     * @param int $client_id The client ID
     * @param int $sent Whether or not the email was actually sent (1 or 0, optional, default null for either)
     * @return Record The partially constructed query Record object
     */
    private function getMailLog($client_id, $sent = null)
    {
        $fields = ['id', 'company_id', 'to_client_id', 'from_staff_id', 'to_address',
            'from_address', 'from_name', 'cc_address', 'subject', 'body_text', 'body_html',
            'sent', 'error', 'date_sent'
        ];

        $this->Record->select($fields)
            ->from('log_emails')
            ->where('to_client_id', '=', $client_id);

        // Filter on whether the email was sent
        if ($sent !== null) {
            $this->Record->where('sent', '=', (int) $sent);
        }

        return $this->Record;
    }

    /**
     * Retrieves a list of ISO 4217 currency codes used by the given client
     *
     * @param int $client_id The ID of the client whose used currencies to fetch
     * @return array A list of ISO 4217 currency codes
     */
    public function usedCurrencies($client_id)
    {
        // Fetch all currencies used in transactions
        $trans_currencies = $this->Record->select(['currency'])->
            from('transactions')->
            where('client_id', '=', $client_id)->
            group('currency')->
            fetchAll();

        // Fetch all currencies used in invoices
        $inv_currencies = $this->Record->select(['currency'])->
            from('invoices')->
            where('client_id', '=', $client_id)->
            group('currency')->
            fetchAll();

        // Combine the currency codes
        $currencies = [];
        foreach ($trans_currencies as $trans_curr) {
            $currencies[] = $trans_curr->currency;
        }
        foreach ($inv_currencies as $inv_curr) {
            $currencies[] = $inv_curr->currency;
        }

        return array_unique($currencies);
    }

    /**
     * Retrieves the number of clients given a client status
     *
     * @param string $status The client status type (optional, default 'active')
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - contact_name The (partial) name of the contact for which to fetch clients
     *  - contact_email The (partial) email address of the contact for which to fetch clients
     *  - contact_company The (partial) company name of the contact for which to fetch clients
     *  - contact_country The contact country on which to filter clients
     *  - client_group_id The client group ID on which to filter clients
     *  - invoice_method The invoice delivery method on which to filter clients
     *  - last_seen_start_date The start date of the "last seen" date range on which to filter clients
     *  - last_seen_end_date The end date of the "last seen" date range on which to filter clients
     * @return int The number of clients of type $status
     */
    public function getStatusCount($status = 'active', array $filters = [])
    {
        return $this->getClients(array_merge($filters, ['status' => $status]))->numResults();
    }

    /**
     * Retrieves a list of client status types
     *
     * @return array Key=>value pairs of client status types
     */
    public function getStatusTypes()
    {
        return [
            'active' => $this->_('Clients.getStatusTypes.active'),
            'inactive' => $this->_('Clients.getStatusTypes.inactive'),
            'fraud' => $this->_('Clients.getStatusTypes.fraud')
        ];
    }

    /**
     * Returns the rule set for adding clients
     *
     * @param array $vars The input vars
     * @param bool $edit True to use as edit rules, or false otherwise
     * @return array Client rules
     */
    private function getAddRules($vars, $edit = false)
    {
        Loader::loadModels($this, ['Companies', 'TaxProviders']);

        $rules = [
            'id_format' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Clients.!error.id_format.empty')
                ],
                'length' => [
                    'rule' => ['maxLength', 64],
                    'message' => $this->_('Clients.!error.id_format.length')
                ]
            ],
            'id_value' => [
                'valid' => [
                    'rule' => [[$this, 'isInstanceOf'], 'Record'],
                    'message' => $this->_('Clients.!error.id_value.valid')
                ]
            ],
            'user_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'users'],
                    'message' => $this->_('Clients.!error.user_id.exists')
                ],
                'unique' => [
                    'rule' => [[$this, 'validateExists'], 'user_id', 'clients'],
                    'negate' => true,
                    'message' => $this->_('Clients.!error.user_id.unique', (isset($vars['user_id']) ? $vars['user_id'] : null))
                ]
            ],
            'client_group_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'client_groups'],
                    'message' => $this->_('Clients.!error.client_group_id.exists')
                ]
            ],
            'status' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateStatus']],
                    'message' => $this->_('Clients.!error.status.format')
                ]
            ]
        ];

        // Validate tax id
        $tax_provider = $this->TaxProviders->getByCountry($vars['country'] ?? null);
        if (
            $tax_provider !== false
            && $tax_provider->isEnabled()
            && in_array(($vars['country'] ?? null), $tax_provider->getCountries())
            && !empty($vars['settings']['tax_id'])
        ) {
            $rules['settings[tax_id]'] = [
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$tax_provider, 'validateTaxId'], $vars],
                    'message' => $this->_('Clients.!error.settings[tax_id].valid')
                ]
            ];
        }

        // Rules for editing clients
        if ($edit) {
            // Remove id_format and id_value, they cannot be changed
            unset($rules['id_format'], $rules['id_value']);

            // Allow client to be edited with identical unique user_id
            $rules['user_id']['unique']['rule'] = [[$this, 'validateUserId'], (isset($vars['client_id']) ? $vars['client_id'] : null)];
            $rules['user_id']['unique']['negate'] = false;

            $edit_rules = [
                'client_id' => [
                    'exists' => [
                        'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                        'message' => $this->_('Clients.!error.client_id.exists')
                    ]
                ]
            ];

            $rules = array_merge($rules, $edit_rules);
        }

        return $rules;
    }

    /**
     * Returns the rule set for adding/editing client custom fields
     *
     * @param array A list of input vars
     * @return array Custom field rules
     */
    private function getCustomFieldRules(array $vars)
    {
        $rules = [
            'client_group_id' => [
                'format' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'client_groups'],
                    'message' => $this->_('Clients.!error.client_group_id.exists')
                ]
            ],
            'name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Clients.!error.name.empty')
                ]
            ],
            'link' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['filter_var', FILTER_VALIDATE_URL],
                    'message' => $this->_('Clients.!error.link.valid')
                ]
            ],
            'is_lang' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('Clients.!error.is_lang.format')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 1],
                    'message' => $this->_('Clients.!error.is_lang.length')
                ]
            ],
            'type' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateFieldType']],
                    'message' => $this->_('Clients.!error.type.format')
                ]
            ],
            'values' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateValues'], (isset($vars['type']) ? $vars['type'] : null)],
                    'message' => $this->_('Clients.!error.values.format'),
                    'post_format' => 'serialize',
                ]
            ],
            'default' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [
                        function ($default, $type, $values) {
                            // Any default value is acceptable for text/textarea fields
                            if (in_array($type, ['text', 'textarea'])) {
                                return true;
                            }

                            // Non-select field types must be an exact match to the values field
                            if ($type != 'select') {
                                return ($default == $values);
                            } elseif (!is_array($values)) {
                                // Values must be an array for the 'select' type
                                // Return true here to skip this rule; let another rule validate the type values
                                return true;
                            }

                            // The select field type must contain a value that matches the default value
                            return in_array($default, $values);
                        },
                        ['_linked' => 'type'],
                        ['_linked' => 'values']
                    ],
                    'message' => $this->_('Clients.!error.default.valid')
                ]
            ],
            'regex' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateRegex']],
                    'message' => $this->_('Clients.!error.regex.valid')
                ]
            ],
            'show_client' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('Clients.!error.show_client.format')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 1],
                    'message' => $this->_('Clients.!error.show_client.length')
                ]
            ],
            'encrypted' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('Clients.!error.encrypted.format')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 1],
                    'message' => $this->_('Clients.!error.encrypted.length')
                ]
            ],
            'read_only' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('Clients.!error.read_only.format')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 1],
                    'message' => $this->_('Clients.!error.read_only.length')
                ]
            ]
        ];

        if (empty($vars['link'])) {
            unset($rules['link']);
        }

        return $rules;
    }

    /**
     * Returns the rule set for adding/editing client notes
     *
     * @return array Note rules
     */
    private function getNoteRules()
    {
        $rules = [
            'client_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => $this->_('Clients.!error.client_id.exists')
                ]
            ],
            'staff_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'staff'],
                    'message' => $this->_('Clients.!error.staff_id.exists')
                ]
            ],
            'title' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Clients.!error.title.empty')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 255],
                    'message' => $this->_('Clients.!error.title.length')
                ]
            ],
            'stickied' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('Clients.!error.stickied.format')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 1],
                    'message' => $this->_('Clients.!error.stickied.length')
                ]
            ]
        ];
        return $rules;
    }

    /**
     * Determines whether the given regular expression is a valid PCRE
     *
     * @param string $regex The regular expression to check
     * @return bool True if the regular expression is valid, false otherwise
     */
    public function validateRegex($regex)
    {
        if (empty($regex)) {
            return true;
        }

        // Attempt to evaluate the regular expression
        try {
            $match = preg_match($regex, '');
        } catch (Exception $exc) {
            return false;
        }
        return true;
    }

    /**
     * Validates that the given contact ID is a primary or billing contact for the given client
     *
     * @param int $contact_id The contact ID
     * @param int $client_id The client ID
     */
    public function validateBillingContact($contact_id, $client_id)
    {
        // Look for the client contact
        $count = $this->Record->select('id')->from('contacts')->
            where('client_id', '=', $client_id)->where('id', '=', $contact_id)->
            open()->
                where('contact_type', '=', 'primary')->
                orWhere('contact_type', '=', 'billing')->
            close()->
            numResults();

        if ($count > 0) {
            return true;
        }
        return false;
    }

    /**
     * Validates that the given client can be deleted
     *
     * @param int $client_id The ID of the client to delete
     * @return bool True if the client may be deleted, false otherwise
     */
    public function validateClientDeleteable($client_id)
    {
        // Client is not deletable if they have any active or proforma invoices
        $count = $this->Record->select('invoices.id')
            ->from('invoices')
            ->innerJoin('clients', 'clients.id', '=', 'invoices.client_id', false)
            ->where('clients.id', '=', $client_id)
            ->where('invoices.status', 'in', ['active', 'proforma'])
            ->where('invoices.date_closed', '=', null)
            ->numResults();

        if ($count > 0) {
            return false;
        }

        // Client is not deletable if they have any active or suspended services
        $count = $this->Record->select('services.id')
            ->from('services')
            ->innerJoin('clients', 'clients.id', '=', 'services.client_id', false)
            ->where('clients.id', '=', $client_id)
            ->where('services.status', 'in', ['active', 'suspended'])
            ->numResults();

        if ($count > 0) {
            return false;
        }

        // Client is not deletable if they have any recurring invoices set
        $count = $this->Record->select('invoices_recur.id')
            ->from('invoices_recur')
            ->innerJoin('clients', 'clients.id', '=', 'invoices_recur.client_id', false)
            ->where('clients.id', '=', $client_id)
            ->numResults();

        if ($count > 0) {
            return false;
        }

        return true;
    }

    /**
     * Validates a currency exists
     *
     * @param string $code The currency code
     * @param int $company_id The company ID
     * @return bool True if the currency exists, false otherwise
     */
    public function validateCurrencyExists($code, $company_id)
    {
        $count = $this->Record->select('code')->from('currencies')->
            where('code', '=', $code)->where('company_id', '=', $company_id)->numResults();

        if ($count > 0) {
            return true;
        }
        return false;
    }

    /**
     * Validates that the given setting is editable by a client
     *
     * @param string $value The value of the setting
     * @param string $key The setting key (one of "client_set_currency", "client_set_invoice", or "client_set_lang")
     * @param int $client_id The ID of the client to validate against
     * @return bool True if the client may update the given setting, false otherwise
     */
    public function validateClientSettingIsEditable($value, $key, $client_id)
    {
        // The key must be one of the following configurable settings for clients
        $config_settings = ['client_set_currency', 'client_set_invoice', 'client_set_lang'];

        if (in_array($key, $config_settings)) {
            // Get the client to validate against
            $client = $this->get($client_id);

            if ($client) {
                if ((isset($client->settings[$key]) ? $client->settings[$key] : null) == 'true') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Validates that the given language code exists for this company
     *
     * @param string $code The language code in ISO 639-1 ISO 3166-1 alpha-2 concatenated format (i.e. "en_us")
     * @param int $company_id The company ID
     */
    public function validateLanguageExists($code, $company_id)
    {
        $count = $this->Record->select('code')->from('languages')->
            where('code', '=', $code)->where('company_id', '=', $company_id)->
            numResults();

        if ($count > 0) {
            return true;
        }
        return false;
    }

    /**
     * Validates client custom field values
     *
     * @param mixed $values The values of the custom field
     * @param string $type The type of custom field this is ('text', 'checkbox', 'select', 'textarea')
     * @return bool True if values are in the expected format, false otherwise
     */
    public function validateValues($values, $type)
    {
        // Values may be null
        if ($values == null) {
            return true;
        }

        // The values must be an array if the type is "select"
        if (!empty($values) && !is_array($values) && ($type == 'select')) {
            return false;
        }
        return true;
    }

    /**
     * Validates the client's 'status' field
     *
     * @param string $status The status to check
     * @return bool True if validated, false otherwise
     */
    public function validateStatus($status)
    {
        return in_array($status, ['active', 'inactive', 'fraud']);
    }

    /**
     * Validates the client's 'primary_account_type' field
     *
     * @param string $type The primary account type
     * @return bool True if validated, false otherwise
     */
    public function validateAccountType($type)
    {
        return in_array($type, ['ach', 'cc']);
    }

    /**
     * Validates the user ID belongs to $client_id
     *
     * @param int $user_id The client's user ID
     * @param int $client_id The client ID
     * @return bool True if $user_id belongs to $client_id, false otherwise
     */
    public function validateUserId($user_id, $client_id)
    {
        $count = $this->Record->select('id')->from('clients')->where('id', '=', $client_id)->
            where('user_id', '=', $user_id)->numResults();

        if ($count > 0) {
            return true;
        }
        return false;
    }

    /**
     * Validates the client custom field type
     *
     * @param string $type The custom field type
     * @return bool True if validated, false otherwise
     */
    public function validateFieldType($type)
    {
        return in_array($type, ['text', 'checkbox', 'select', 'textarea']);
    }

    /**
     * Validates that the given field belongs to the same company as the client
     *
     * @param int $field_id The custom client field ID
     * @param int $client_id The client ID
     * @return bool True if the custom field ID company matches the client's company ID, false otherwise
     */
    public function validateFieldCompany($field_id, $client_id)
    {
        // Set the company ID as the client's company, or default to the current company instead
        $client = $this->get($client_id, false);
        $company_id = (isset($client->company_id) ? $client->company_id : Configure::get('Blesta.company_id'));

        $count = $this->Record->select('client_groups.company_id')->from('client_fields')->
            innerJoin('client_groups', 'client_fields.client_group_id', '=', 'client_groups.id', false)->
            where('client_fields.id', '=', $field_id)->
            where('client_groups.company_id', '=', $company_id)->
            numResults();

        if ($count > 0) {
            return true;
        }
        return false;
    }

    /**
     * Validate client information for add or edit
     *
     * @param array $vars An array of user info including:
     *
     *  - id_code The client's reference ID code (for display purposes)
     *  - user_id The client's user ID
     *  - client_group_id The client group this user belongs to
     *  - status The status of this client ('active', 'inactive',' 'fraud') (optional, default active)
     * @param bool $edit Whether this data is being validated for an edit (optional, default false)
     * @param bool $final_validation Whether this is the final validation before add/edit (optional, default false)
     * @return bool True if the client info is valid, false otherwise
     */
    public function validateClient(array $vars, $edit = false, $final_validation = false)
    {
        $default_rules = $this->getAddRules($vars, $edit);
        if ($edit) {
            $rules = [];

            // Remove rules for unset fields
            foreach ($vars as $key => $value) {
                if (empty($value)) {
                    unset($vars[$key]);
                    continue;
                }

                if (isset($default_rules[$key])) {
                    $rules[$key] = $default_rules[$key];
                }
            }

            // Allow 'if_set' rules
            foreach ($default_rules as $field => $field_rules) {
                foreach ($field_rules as $type => $rule) {
                    if (!isset($rule['if_set']) || !$rule['if_set']) {
                        unset($field_rules[$type]);
                    }
                }

                if (!empty($field_rules)) {
                    $rules[$field] = $field_rules;
                }
            }

            unset($default_rules);
        } else {
            $rules = $default_rules;
        }

        if (!$final_validation) {
            unset($rules['user_id'], $rules['id_value'], $rules['id_format']);
        }

        $this->Input->setRules($rules);

        return $this->Input->validates($vars);
    }

    /**
     * Validates that a client can be created with the given data
     *
     * @param array $vars An array of client info including:
     *
     *  - username The username for this user. Must be unique across all companies for this installation.
     *  - new_password The password for this user
     *  - confirm_password The password for this user
     *  - client_group_id The client group this user belongs to
     *  - status The status of this client ('active', 'inactive',' 'fraud') (optional, default active)
     *  - first_name The first name of this contact
     *  - last_name The last name of this contact
     *  - title The business title for this contact (optional)
     *  - company The company/organization this contact belongs to (optional)
     *  - email This contact's email address
     *  - address1 This contact's address (optional)
     *  - address2 This contact's address line two (optional)
     *  - city This contact's city (optional)
     *  - state The 3-character ISO 3166-2 subdivision code, requires country (optional)
     *  - zip The zip/postal code for this contact (optional)
     *  - country The 2-character ISO 3166-1 country code, required if state is given (optional)
     *  - numbers An array of number data including (optional):
     *      - number The phone number to add
     *      - type The type of phone number 'phone', 'fax' (optional, default 'phone')
     *      - location The location of this phone line 'home', 'work', 'mobile' (optional, default 'home')
     *  - custom An array of custom fields in key/value format where each
     *      key is the custom field ID and each value is the value
     *  - settings An array of client settings including:
     *      - default_currency
     *      - language
     *      - username_type
     *      - tax_id
     *      - tax_exempt
     *  - send_registration_email 'true' to send client welcome email (default), 'false' otherwise
     * @return bool True if the client can be created, false otherwise
     * @see Users::validateUser()
     * @see Clients::validateClient()
     * @see Contacts::validateContact()
     */
    public function validateCreation(array $vars)
    {
        Loader::loadModels($this, ['Users', 'Contacts']);

        // Validate user info
        $user_vars = [
            'username' => (isset($vars['settings']['username_type']) && $vars['settings']['username_type'] == 'email')
                ? $vars['email']
                : $vars['username'],
            'new_password' => $vars['new_password'],
            'confirm_password' => $vars['confirm_password']
        ];
        $user_is_valid = $this->Users->validateUser($user_vars);
        $user_errors = $this->Users->errors();

        // Validate client info
        $client_is_valid = $this->validateClient($vars);
        $client_errors = $this->Input->errors();

        // Validate contact info
        $contact_is_valid = $this->Contacts->validateContact($vars);
        $contact_errors = $this->Contacts->errors();

        // Validate custom fields
        $custom_fields_are_valid = $this->validateCustomFields($vars);
        $custom_field_errors = $this->Input->errors();

        $errors = array_merge(
            ($user_errors ? $user_errors : []),
            ($client_errors ? $client_errors : []),
            ($contact_errors ? $contact_errors : []),
            ($custom_field_errors ? $custom_field_errors : [])
        );

        $this->Input->setErrors($errors);
        return $user_is_valid && $client_is_valid && $contact_is_valid && $custom_fields_are_valid;
    }

    /**
     * Validates a set of custom fields
     *
     * @param array $vars An array of client info including:
     *
     *  - client_group_id The client group this user belongs to
     *  - custom An array of custom fields in key/value format where each
     *      key is the custom field ID and each value is the value
     * @return bool True if the custom fields are valid, false otherwise
     */
    private function validateCustomFields($vars, $client_id = null)
    {
        // Add client custom fields
        $custom_fields = $this->getCustomFields(Configure::get('Blesta.company_id'), $vars['client_group_id']);

        foreach ((isset($custom_fields) ? $custom_fields : []) as $field) {
            $value = isset($vars['custom']) && array_key_exists($field->id, $vars['custom'])
                ? $vars['custom'][$field->id]
                : (isset($field->default) ? $field->default : null);

            if (!$this->validateCustomField($field->id, $value, $client_id)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validates a custom field
     *
     * @param int $field_id The ID of the custom client field
     * @param string $value The value to assign to this client
     * @param int $client_id The client ID the custom field value belongs to
     * @return bool True if the custom field is valid, false otherwise
     */
    private function validateCustomField($field_id, $value, $client_id = null)
    {
        // Set the custom field's name for errors
        $custom_field = $this->getCustomField($field_id);
        $custom_field_name = '';

        // Set whether the custom field values should be encrypted
        $encrypted = 0;

        if ($custom_field) {
            $custom_field_name = $custom_field->name;
            if ($custom_field->is_lang == '1') {
                $custom_field_name = $this->_('_CustomFields.' . $custom_field->name);
            }

            // Set encrypted status for values
            $encrypted = $custom_field->encrypted;
        }

        // Set custom field values
        $field_vars = [
            'client_field_id' => $field_id,
            'value' => $value,
            'encrypted' => $encrypted
        ];

        $rules = [
            'client_field_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'client_fields'],
                    'message' => $this->_('Clients.!error.client_field_id.exists')
                ]
            ],
            'encrypted' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('Clients.!error.encrypted.format')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 1],
                    'message' => $this->_('Clients.!error.encrypted.length')
                ]
            ]
        ];

        if ($client_id) {
            $field_vars['client_id'] = $client_id;

            $rules['client_field_id']['matches'] = [
                'rule' => [[$this, 'validateFieldCompany'], $client_id],
                'message' => $this->_('Clients.!error.client_field_id.matches')
            ];

            $rules['client_id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => $this->_('Clients.!error.client_id.exists')
                ]
            ];
        }

        // Set custom field-specific rules
        if ($custom_field) {
            // Require select options be set to a valid value
            if ($custom_field->type == 'select' && !empty($custom_field->values) && is_array($custom_field->values)) {
                // Don't set required select fields to an empty value
                if (empty($value) && empty($custom_field->regex) && !in_array($value, $custom_field->values)) {
                    return false;
                }

                $rules['value'] = [
                    'valid' => [
                        'rule' => ['in_array', $custom_field->values],
                        'message' => $this->_('Clients.!error.value.valid', $custom_field_name)
                    ]
                ];
            }

            // Require the value match the regex
            if (!empty($custom_field->regex)) {
                if (!isset($rules['value'])) {
                    $rules['value'] = [];
                }

                // Add rule if custom field is required
                $rules['value']['required'] = [
                    'rule' => ['matches', $custom_field->regex],
                    'message' => $this->_('Clients.!error.value.required', $custom_field_name)
                ];
            }
        }

        $this->Input->setRules($rules);

        return $this->Input->validates($field_vars);
    }

    /**
     * Checks if the given $field is a reference of $class
     *
     * @param mixed $field The value to check
     * @param mixed $class The class or instance to check against
     * @return bool True if the $field is an instance of $class, or false otherwise
     */
    public function isInstanceOf($field, $class)
    {
        return $field instanceof $class;
    }
}
