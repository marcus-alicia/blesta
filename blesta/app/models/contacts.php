<?php

/**
 * Contact management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Contacts extends AppModel
{
    /**
     * Initialize Contacts
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['contacts']);
    }

    /**
     * Add a contact with the fields given
     *
     * @param array $vars An array of variable contact info, including:
     *
     *  - client_id The client ID this contact will be associated with
     *  - user_id The user ID this contact belongs to if this contact has their own unique user record (optional)
     *  - staff_id The ID of the staff user, if a staff user is executing the action (optional)
     *  - contact_type The type of contact, either 'primary' (default), 'billing', or 'other' (optional)
     *  - contact_type_id The ID of the contact type if contact_type is 'other' (optional)
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
     *  - verify Whether or not the email should be verified, overrides the company and client group settings
     * @return int The contact ID, void on error
     * @see Contacts::addNumber()
     */
    public function add(array $vars)
    {
        // Trigger the Contacts.addBefore event
        extract($this->executeAndParseEvent('Contacts.addBefore', ['vars' => $vars]));

        if ($this->validateContact($vars, false, true)) {
            $vars = $this->adjustInput($vars);
            $vars['first_name'] = trim($vars['first_name']);
            $vars['last_name'] = trim($vars['last_name']);
            $vars['email'] = trim($vars['email']);

            $fields = ['client_id', 'user_id', 'contact_type', 'contact_type_id', 'first_name',
                'last_name', 'title', 'company', 'email', 'address1', 'address2', 'city', 'state',
                'zip', 'country', 'date_added'
            ];

            $vars['date_added'] = date('Y-m-d H:i:s');

            // Add contact
            $this->Record->insert('contacts', $vars, $fields);

            $contact_id = $this->Record->lastInsertId();

            // Set email address for verification
            Loader::loadModels($this, ['Clients', 'ClientGroups', 'EmailVerifications']);
            Loader::loadHelpers($this, ['Form']);

            if (($client = $this->Clients->get($vars['client_id'], true))) {
                $settings = $this->ClientGroups->getSettings($client->client_group_id);
                $settings = $this->Form->collapseObjectArray($settings, 'value', 'key');

                $vars['verify'] = isset($vars['verify'])
                    ? (bool)$vars['verify']
                    : ($settings['email_verification'] == 'true');

                if ($vars['verify']) {
                    // Add email verification
                    $this->EmailVerifications->add([
                        'contact_id' => $contact_id,
                        'email' => $vars['email']
                    ]);
                }
            }

            // Add contact numbers
            if (!empty($vars['numbers']) && is_array($vars['numbers'])) {
                // Update multiple numbers
                foreach ($vars['numbers'] as $number) {
                    // Ignore a case that neither ID nor number have been set
                    if (empty($number['id']) && empty($number['number'])) {
                        continue;
                    }

                    $this->addNumber($contact_id, $number);
                }
            }
            if (array_key_exists('permissions', $vars)) {
                $this->setPermissions($contact_id, $vars['permissions']);
            }

            // Trigger the Contacts.addAfter event
            $this->executeAndParseEvent('Contacts.addAfter', ['contact_id' => $contact_id, 'vars' => $vars]);

            return $contact_id;
        }
    }

    /**
     * Gets the rules for all contact fields that are require by the client group
     *
     * @param array $vars An array containing the parameters to validate
     * @param bool $edit Whether this data is being validated for an edit (optional, default false)
     * @param int $staff_id The ID of the staff user, if a staff user is executing the action (optional)
     * @return array A list of rules with the addition of the client groups required fields
     */
    private function getRequiredFieldRules(array $vars, $edit = false, $staff_id = null)
    {
        Loader::loadModels($this, ['ClientGroups']);

        $client_id = $vars['client_id'] ?? null;
        if (!is_null($staff_id)) {
            Loader::loadModels($this, ['Staff']);
            $staff = $this->Staff->get($staff_id);
        }

        $required_fields = [];
        $client = $this->Record->select('client_group_id')
            ->from('clients')
            ->where('id', '=', $client_id)
            ->fetch();

        if ($client) {
            $required_contact_fields = $this->ClientGroups->getSetting(
                $client->client_group_id,
                'required_contact_fields'
            );

            if ($required_contact_fields) {
                $required_fields = unserialize(base64_decode($required_contact_fields->value ?? ''));
            }
        }

        $rules = [];
        foreach ($required_fields as $field) {
            if (!in_array($field, ['phone', 'fax'])) {
                $rules[$field]['empty'] = [
                    'if_set' => $edit,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Contacts.!error.' . $field . '.empty')
                ];
            }
        }

        return $rules;
    }

    /**
     * Gets the rules for all contact fields that are read only by the client group
     *
     * @param array $vars An array containing the parameters to validate
     * @param bool $edit Whether this data is being validated for an edit (optional, default false)
     * @param int $staff_id The ID of the staff user, if a staff user is executing the action (optional)
     * @return array A list of rules with the addition of the client groups read only fields
     */
    private function getReadOnlyFieldRules(array $vars, $edit = false, $staff_id = null)
    {
        if (!$edit) {
            return [];
        }

        Loader::loadModels($this, ['Clients', 'ClientGroups']);

        $client_id = $vars['client_id'] ?? null;
        if (!is_null($staff_id)) {
            Loader::loadModels($this, ['Staff']);
            $staff = $this->Staff->get($staff_id);

            if (!empty($staff)) {
                return [];
            }
        }

        // Get contact
        $contact = $this->get($vars['contact_id'] ?? null);
        if (!$contact) {
            return [];
        }

        $read_only_fields = [];
        $client = $this->Clients->get($client_id);

        if ($client) {
            $read_only_contact_fields = $this->ClientGroups->getSetting(
                $client->client_group_id,
                'read_only_contact_fields'
            );

            if ($read_only_contact_fields) {
                $read_only_fields = unserialize(base64_decode($read_only_contact_fields->value ?? ''));
            }
        }

        // Set rules
        $rules = [];
        foreach ($read_only_fields as $field) {
            if (!in_array($field, ['phone', 'fax']) && !empty($client->{$field})) {
                // Omit rule if the field hasn't been previously set
                if (empty($contact->{$field})) {
                    continue;
                }

                // Omit rule if the current value is equal to the previous value
                if (isset($contact->{$field}) && isset($vars[$field]) && $vars[$field] == $contact->{$field}) {
                    continue;
                }

                $rules[$field]['read_only'] = [
                    'if_set' => $edit,
                    'rule' => 'isEmpty',
                    'negate' => false,
                    'message' => $this->_('Contacts.!error.' . $field . '.read_only')
                ];

                // If the previous value is not empty, and the current value is empty, negate the rule
                if (!empty($contact->{$field}) && empty($vars[$field])) {
                    $rules[$field]['read_only']['negate'] = true;
                }
            }
        }

        return $rules;
    }

    /**
     * Edit the contact with the fields given, all fields optional
     *
     * @param int $contact_id The contact ID to update
     * @param array $vars An array of variable contact info, including:
     *
     *  - user_id The user ID this contact belongs to if this contact has their own unique user record (optional)
     *  - staff_id The ID of the staff user, if a staff user is executing the action (optional)
     *  - contact_type The type of contact, either "primary" or "other" (optional, default the current value)
     *  - contact_type_id The ID of the contact type if contact_type is "other" (optional)
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
     *      - id The ID of the contact number to update (if empty, will add as new)
     *      - number The phone number to add (if empty, will remove this record)
     *      - type The type of phone number 'phone', 'fax' (optional, default 'phone')
     *      - location The location of this phone line 'home', 'work', 'mobile' (optional, default 'home')
     *  - verify Whether or not the email should be verified, overrides the company and client group settings
     * @return stdClass object represented the updated contact if successful, void otherwise
     * @see Contacts::addNumber()
     * @see Contacts::editNumber()
     */
    public function edit($contact_id, array $vars)
    {
        // Trigger the Contacts.editBefore event
        extract($this->executeAndParseEvent('Contacts.editBefore', ['contact_id' => $contact_id, 'vars' => $vars]));

        $vars['contact_id'] = $contact_id;
        if ($this->validateContact($vars, true, true)) {
            $vars = $this->adjustInput($vars, true);

            $old_contact = (array) $this->get($vars['contact_id']);

            $fields = ['user_id', 'contact_type', 'contact_type_id', 'first_name', 'last_name',
                'title', 'company', 'email', 'address1', 'address2', 'city', 'state', 'zip', 'country'
            ];
            $trim_fields = ['first_name', 'last_name', 'email'];
            foreach ($fields as $key => $field) {
                if (isset($vars[$field])) {
                    if (in_array($field, $trim_fields)) {
                        $vars[$field] = trim($vars[$field]);
                    }
                } else {
                    unset($fields[$key]);
                }
            }

            // Check if the email has been verified
            Loader::loadModels($this, ['Clients', 'ClientGroups', 'EmailVerifications']);
            Loader::loadHelpers($this, ['Form']);

            if (($client = $this->Clients->get($old_contact['client_id'], true))) {
                $settings = $this->ClientGroups->getSettings($client->client_group_id);
                $settings = $this->Form->collapseObjectArray($settings, 'value', 'key');

                $vars['verify'] = isset($vars['verify'])
                    ? (bool)$vars['verify']
                    : ($settings['email_verification'] == 'true');

                if ($vars['verify']) {
                    // Add email verification
                    $email_verification = $this->EmailVerifications->getByContactId($contact_id);
                    if (
                        empty($email_verification)
                        || (isset($email_verification->verified) && $email_verification->verified == 1)
                    ) {
                        $this->EmailVerifications->add([
                            'contact_id' => $contact_id,
                            'email' => $vars['email']
                        ]);
                    } elseif ($email_verification->email !== $vars['email']) {
                        $this->EmailVerifications->edit($email_verification->id, [
                            'email' => $vars['email'],
                            'verified' => (int)(
                                $email_verification->email !== $vars['email']
                                && $old_contact['email'] == $vars['email']
                            )
                        ]);
                    }

                    // Prevent saving the new email address
                    $vars['email'] = $old_contact['email'];
                }
            }

            // Update contact
            $this->Record->where('id', '=', $contact_id)->update('contacts', $vars, $fields);

            // Update/Delete contact numbers
            if (!empty($vars['numbers']) && is_array($vars['numbers'])) {
                // Update multiple numbers
                foreach ($vars['numbers'] as $number) {
                    // Ignore a case that neither ID nor number have been set
                    if (empty($number['id']) && empty($number['number'])) {
                        continue;
                    }

                    // Delete the number if the number is empty
                    if (empty($number['number'])) {
                        $this->deleteNumber($number['id']);
                        continue;
                    }

                    // Add a new number if ID is empty, otherwise update an existing one
                    if (empty($number['id'])) {
                        $this->addNumber($contact_id, $number);
                    } else {
                        $this->editNumber($number['id'], $number);
                    }
                }
            }
            if (array_key_exists('permissions', $vars)) {
                $this->setPermissions($contact_id, $vars['permissions']);
            }

            $new_contact = $this->get($contact_id);

            // Calculate the changes made to the contact and log those results
            $diff = array_diff_assoc($old_contact, (array) $new_contact);
            $fields = [];
            foreach ($diff as $key => $value) {
                $fields[$key]['prev'] = $value;
                $fields[$key]['cur'] = $new_contact->$key;
            }

            if (!empty($fields)) {
                if (!isset($this->Logs)) {
                    Loader::loadModels($this, ['Logs']);
                }
                $this->Logs->addContact(['contact_id' => $contact_id, 'fields' => $fields]);
            }

            // Trigger the Contacts.editAfter event
            $this->executeAndParseEvent(
                'Contacts.editAfter',
                ['contact_id' => $contact_id, 'vars' => $vars, 'old_contact' => (object)$old_contact]
            );

            return $new_contact;
        }
    }

    /**
     * Permanently removes this contact from the system.
     *
     * @param int $contact_id The ID of the contact to remove from the system
     */
    public function delete($contact_id)
    {
        // Trigger the Contacts.deleteBefore event
        extract($this->executeAndParseEvent('Contacts.deleteBefore', ['contact_id' => $contact_id]));

        $vars = ['contact_id' => $contact_id, 'contact_type' => null];

        // Nothing to delete
        if (!($contact = $this->get($contact_id))) {
            return;
        }

        Loader::loadModels($this, ['Clients', 'Users']);

        // The client must exist for the rules to be validated
        $rules = [];
        if (($client = $this->Clients->get($contact->client_id, false))) {
            $rules = [
                'contact_id' => [
                    'primary' => [
                        'rule' => [[$this, 'validateIsPrimary']],
                        'negate' => true,
                        'message' => $this->_('Contacts.!error.contact_id.primary')
                    ]
                ],
                'contact_type' => [
                    'inv_address_to' => [
                        'rule' => [[$this, 'validateReceivesInvoices'], $contact_id],
                        'negate' => true,
                        'message' => $this->_('Contacts.!error.contact_type.inv_address_to')
                    ]
                ]
            ];
        }

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            // Remove contact and contact numbers
            $this->Record->from('contacts')
                ->leftJoin('contact_numbers', 'contact_numbers.contact_id', '=', 'contacts.id', false)
                ->on('contact_permissions.client_id', '=', $contact->client_id)
                ->leftJoin('contact_permissions', 'contact_permissions.contact_id', '=', 'contacts.id', false)
                ->leftJoin('email_verifications', 'email_verifications.contact_id', '=', 'contacts.id', false)
                ->where('contacts.id', '=', $contact_id)
                ->delete(['contacts.*', 'contact_numbers.*', 'contact_permissions.*', 'email_verifications.*']);

            // Trigger the Contacts.deleteAfter event
            $this->executeAndParseEvent(
                'Contacts.deleteAfter',
                ['contact_id' => $contact_id, 'old_contact' => $contact]
            );
        }
    }

    /**
     * Fetch the contact with the given contact ID
     *
     * @param int $contact_id The contact ID to fetch
     * @return mixed A stdClass contact object, or false if the contact does not exist
     */
    public function get($contact_id)
    {
        $fields = [
            'contacts.*', 'contact_types.name' => 'contact_type_name',
            'contact_types.is_lang' => 'contact_type_is_lang'
        ];

        return $this->Record->select($fields)
            ->from('contacts')
            ->leftJoin('contact_types', 'contact_types.id', '=', 'contacts.contact_type_id')
            ->where('contacts.id', '=', $contact_id)
            ->fetch();
    }

    /**
     * Fetch the contact with the given contact ID
     *
     * @param int $user_id The contact user ID to fetch on
     * @param int $client_id The contact client ID to fetch on
     * @return mixed A stdClass contact object, or false if the contact does not exist
     */
    public function getByUserId($user_id, $client_id)
    {
        $fields = [
            'contacts.*', 'contact_types.name' => 'contact_type_name',
            'contact_types.is_lang' => 'contact_type_is_lang'
        ];

        return $this->Record->select($fields)
            ->from('contacts')
            ->leftJoin('contact_types', 'contact_types.id', '=', 'contacts.contact_type_id')
            ->where('contacts.user_id', '=', $user_id)
            ->where('contacts.client_id', '=', $client_id)
            ->fetch();
    }

    /**
     * Fetches a list of all contacts under a client
     *
     * @param int $client_id The client ID to fetch contacts for
     * @param int $page The page to return results for (optional, default 1)
     * @param string $order The sort and order fields (optional, default the last name and first name ascending)
     * @return array An array of objects
     */
    public function getList($client_id, $page = 1, array $order = ['last_name' => 'asc', 'first_name' => 'asc'])
    {
        $this->Record = $this->getContacts($client_id);

        // Return the results
        return $this->Record->order($order)
            ->limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())
            ->fetchAll();
    }

    /**
     * Return the total number of contacts returned from Contacts::getList(),
     * useful in constructing pagination for the getList() method.
     *
     * @param int $client_id The client ID to fetch contacts for
     * @return int The total number of clients
     * @see Companies::getList()
     */
    public function getListCount($client_id)
    {
        $this->Record = $this->getContacts($client_id);

        // Return the number of results
        return $this->Record->numResults();
    }

    /**
     * Fetches a list of all contacts under a client
     *
     * @param int $client_id The client ID to fetch contacts for
     * @param string $contact_type The contact type to fetch
     *  (i.e. "primary", "billing", or "other") (optional, default all contact types)
     * @param array $order The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @return array A list of stdClass objects representing each contact
     */
    public function getAll(
        $client_id,
        $contact_type = null,
        array $order = ['last_name' => 'asc', 'first_name' => 'asc']
    ) {
        $this->Record = $this->getContacts($client_id);

        // Include a specific contact type
        if ($contact_type != null) {
            $this->Record->where('contact_type', '=', $contact_type);
        }

        return $this->Record->order($order)->fetchAll();
    }

    /**
     * Partially constructs the query required by both Contacts::getList() and
     * Contacts::getListCount()
     *
     * @param int $client_id The client ID to fetch contacts for
     * @return Record The partially constructed query Record object
     */
    private function getContacts($client_id)
    {
        $fields = ['id', 'client_id', 'user_id', 'contact_type',
            'contact_type_id', 'first_name', 'last_name', 'title', 'company',
            'email', 'address1', 'address2', 'city', 'state', 'zip', 'country'];

        $this->Record->select($fields)->from('contacts')->where('client_id', '=', $client_id);

        return $this->Record;
    }

    /**
     * Retrieves a list of client contact types
     *
     * @return array Key=>value pairs of client contact types
     */
    public function getContactTypes()
    {
        return [
            'primary' => $this->_('Contacts.getcontacttypes.primary'),
            'billing' => $this->_('Contacts.getcontacttypes.billing'),
            'other' => $this->_('Contacts.getcontacttypes.other')
        ];
    }

    /**
     * Retrieve a single contact type
     *
     * @param int $contact_type_id The contact type ID
     * @return mixed An stdClass object representing the contact type, or false if none exist
     */
    public function getType($contact_type_id)
    {
        $contact_type = $this->Record->select(['id', 'company_id', 'name', 'is_lang'])->from('contact_types')->
            where('id', '=', $contact_type_id)->fetch();

        if ($contact_type) {
            // Set a real_name to the language definition, if applicable
            if ($contact_type->is_lang == '1') {
                $contact_type->real_name = $this->_('_ContactTypes.' . $contact_type->name, true);
            } else {
                $contact_type->real_name = $contact_type->name;
            }
        }

        return $contact_type;
    }

    /**
     * Return all existing contact types in the system for the given company
     *
     * @param int $company_id The company ID to fetch contact types for (optional)
     * @return array An array of stdClass objects representing contact types
     */
    public function getTypes($company_id = null)
    {
        $this->Record->select(['id', 'company_id', 'name', 'is_lang'])->from('contact_types');

        if ($company_id != null) {
            $this->Record->where('company_id', '=', $company_id);
        }

        $contact_types = $this->Record->fetchAll();

        // Set a real_name to the language definition, if applicable
        foreach ($contact_types as &$contact_type) {
            if ($contact_type->is_lang == '1') {
                $contact_type->real_name = $this->_('_ContactTypes.' . $contact_type->name, true);
            } else {
                $contact_type->real_name = $contact_type->name;
            }
        }

        return $contact_types;
    }

    /**
     * Add a contact type
     *
     * @param array $vars An array of contact type information including:
     *
     *  - name The name of this contact type
     *  - is_lang Whether or not 'name' is a language definition
     *  - company_id The company ID to add the contact type under (optional)
     * @return int The contact type ID created, void if error
     */
    public function addType(array $vars)
    {
        // Set company ID
        $vars['company_id'] = empty($vars['company_id']) ? null : $vars['company_id'];

        $this->Input->setRules($this->getTypeRules());

        if ($this->Input->validates($vars)) {
            // Add the type
            $fields = ['company_id', 'name', 'is_lang'];
            $this->Record->insert('contact_types', $vars, $fields);
            return $this->Record->lastInsertId();
        }
    }

    /**
     * Update the contact type with the given data
     *
     * @param int $contact_type_id The contact type ID to update
     * @param array $vars An array of contact type information including:
     *
     *  - name The name of this contact type
     *  - is_lang Whether or not 'name' is a language definition
     */
    public function editType($contact_type_id, array $vars)
    {
        $rules = $this->getTypeRules();
        $rules['contact_type_id'] = [
            'exists' => [
                'rule' => [[$this, 'validateExists'], 'id', 'contact_types'],
                'message' => $this->_('Contacts.!error.contact_type_id.exists')
            ]
        ];

        // Remove company_id constraint
        unset($rules['company_id']);

        $this->Input->setRules($rules);

        $vars['contact_type_id'] = $contact_type_id;

        if ($this->Input->validates($vars)) {
            // Update the type
            $fields = ['name', 'is_lang'];
            $this->Record->where('id', '=', $contact_type_id)->update('contact_types', $vars, $fields);
        }
    }

    /**
     * Delete a contact type and reset the type for all affected contacts to null
     *
     * @param int $contact_type_id The contact type ID
     */
    public function deleteType($contact_type_id)
    {
        // Set all contacts with this contact type ID to have a contact type ID of null
        $this->Record->where('contact_type_id', '=', $contact_type_id)->
            update('contacts', ['contact_type_id' => null]);

        // Finally delete the contact type
        $this->Record->from('contact_types')->where('id', '=', $contact_type_id)->delete();
    }

    /**
     * Adds a new number
     *
     * @param int $contact_id The contact ID to attach the number
     * @param array $vars An array of number information including:
     *
     *  - number The phone number to add
     *  - type The type of phone number 'phone', 'fax' (optional, default 'phone')
     *  - location The location of this phone line 'home', 'work', 'mobile' (optional, default 'home')
     * @return int The contact number ID of the number created, void if error
     */
    public function addNumber($contact_id, array $vars)
    {
        $this->Input->setRules($this->getNumberRules());

        // Validate contact_id as well
        $vars['contact_id'] = $contact_id;

        if ($this->Input->validates($vars)) {
            // Add the number
            $fields = ['contact_id', 'number', 'type', 'location'];
            $this->Record->insert('contact_numbers', $vars, $fields);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Updates an existing number
     *
     * @param int $contact_number_id The number ID to update
     * @param array $vars An array of number information including:
     *
     *  - number The phone number to add
     *  - type The type of phone number 'phone', 'fax' (optional, default 'phone')
     *  - location The location of this phone line 'home', 'work', 'mobile' (optional, default 'home')
     */
    public function editNumber($contact_number_id, array $vars)
    {
        $rules = $this->getNumberRules(false, true, true);

        // Remove contact_id constraint
        unset($rules['contact_id']);
        $this->Input->setRules($rules);

        // Validate the contact number ID as well
        $vars['id'] = $contact_number_id;

        if ($this->Input->validates($vars)) {
            // Update the number
            $fields = ['number', 'type', 'location'];
            $this->Record->where('id', '=', $contact_number_id)->update('contact_numbers', $vars, $fields);
        }
    }

    /**
     * Permanently removes a number from the system
     *
     * @param int $contact_number_id The Id of the number to delete
     */
    public function deleteNumber($contact_number_id)
    {
        // Delete the number
        $this->Record->from('contact_numbers')->where('id', '=', $contact_number_id)->delete();
    }

    /**
     * Fetches a specific number
     *
     * @param int $contact_number_id The ID of the number to fetch
     * @return mixed A stdClass object representing the number, false if no such number exists
     */
    public function getNumber($contact_number_id)
    {
        $fields = ['id', 'contact_id', 'number', 'type', 'location'];
        return $this->Record->select($fields)->from('contact_numbers')->
            where('id', '=', $contact_number_id)->fetch();
    }

    /**
     * Fetches all numbers for the given contact and (optionally) type
     *
     * @param int $contact_id The contact ID to fetch all numbers for
     * @param string $type The type of number to fetch ('phone', or 'fax', default null for all)
     * @param string $location The location of the number to fetch ('home', 'work', or 'mobile', default null for all)
     * @param array $order An array of sort and order fields (optional, default phone location ascending)
     * @return array An array of stdClass objects representing contact numbers, false if no records found
     */
    public function getNumbers(
        $contact_id,
        $type = null,
        $location = null,
        $order = ["FIELD(location,'work','home','mobile')" => 'ASC']
    ) {
        // Set whether we need to escape the order by clause
        $escape_orderby = isset($order["FIELD(location,'work','home','mobile')"]) && (count($order) == 1)
            ? false
            : true;

        $fields = ['id', 'contact_id', 'number', 'type', 'location'];
        $this->Record->select($fields)->from('contact_numbers')->
            where('contact_id', '=', $contact_id);
        if ($type !== null) {
            $this->Record->where('type', '=', $type);
        }
        if ($location !== null) {
            $this->Record->where('location', '=', $location);
        }
        return $this->Record->order($order, $escape_orderby)->fetchAll();
    }

    /**
     * Returns a list of contact number types
     *
     * @return array A key=>value list of contact number types
     */
    public function getNumberTypes()
    {
        return [
            'phone' => $this->_('Contacts.getnumbertypes.phone'),
            'fax' => $this->_('Contacts.getnumbertypes.fax')
        ];
    }

    /**
     * Returns a list of contact number locations
     *
     * @return array A key=>value list of contact number locations
     */
    public function getNumberLocations()
    {
        return [
            'home' => $this->_('Contacts.getnumberlocations.home'),
            'work' => $this->_('Contacts.getnumberlocations.work'),
            'mobile' => $this->_('Contacts.getnumberlocations.mobile')
        ];
    }

    /**
     * Internationalizes the given format number (+NNN.NNNNNNNNNN)
     *
     * @param string $number The phone number
     * @param string $country The ISO 3166-1 alpha2 country code
     * @param string $code_delimiter The delimiter to use between the country prefix and the number
     * @return string The number in +NNN.NNNNNNNNNN
     */
    public function intlNumber($number, $country, $code_delimiter = '.')
    {
        Configure::load('i18');
        $prefixes = Configure::get('i18.calling_codes');

        if (!isset($prefixes[$country])) {
            return $number;
        }

        $prefix = $prefixes[$country];

        // Is number already internationalized?
        if ($number != '' && $number[0] == '+') {
            $number = ltrim($number, '+');

            // Invalid format if prefix isn't in the number
            if (strpos($number, $prefix) !== 0) {
                return $number;
            }

            $number = substr_replace($number, '', 0, strlen($prefix));
        }

        $prefix = preg_replace('/[^0-9]+/', '', $prefix ?? '');
        $number = preg_replace('/[^0-9]+/', '', $number ?? '');

        return '+' . $prefix . $code_delimiter . $number;
    }

    /**
     * Sets permissions for the given contact
     *
     * @param int $contact_id The ID of the contact to set permissions for
     * @param array $vars A numerically indexed array of arrays containing:
     *
     *  - area The area
     */
    public function setPermissions($contact_id, array $vars)
    {
        $rules = [
            'contact_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'contacts'],
                    'message' => $this->_('Contacts.!error.contact_id.exists')
                ]
            ]
        ];
        $this->Input->setRules($rules);

        $data = ['contact_id' => $contact_id];
        if ($this->Input->validates($data)) {
            // Get contact
            $contact = $this->get($contact_id);

            // Set permissions
            $this->Record->
                from('contact_permissions')->
                where('contact_id', '=', $contact->id)->
                where('client_id', '=', $contact->client_id)->
                delete();

            $fields = ['contact_id', 'client_id', 'area'];
            foreach ($vars as $permission) {
                if (!isset($permission['area'])) {
                    continue;
                }
                $permission['contact_id'] = $contact->id;
                $permission['client_id'] = $contact->client_id;

                $this->Record->insert('contact_permissions', $permission, $fields);
            }
        }
    }

    /**
     * Fetch all permissions set for a contact
     *
     * @param int $contact_id The ID of the contact to set permissions for
     * @return array An array of stdClass objects, each representing a permission
     */
    public function getPermissions($contact_id)
    {
        $this->Record = $this->permissions($contact_id);
        return $this->Record->order(['area' => 'asc'])->
            fetchAll();
    }

    /**
     * Checks whether or not the contact has permissions for the given area
     *
     * @param int $company_id The ID of the company
     * @param int $contact_id The ID of the contact
     * @param string $area The area to check
     * @return bool True if permission allowed, false otherwise
     */
    public function hasPermission($company_id, $contact_id, $area)
    {
        $options = $this->getPermissionOptions($company_id);

        $parts = explode('.', $area, 2);
        $area = $parts[0] . (isset($parts[1]) ? '.*' : '');

        // Get client id from the contact
        $contact = $this->get($contact_id);
        $client_id = $contact->client_id;

        if (isset($options[$area])) {
            return (boolean) $this->Record->select()->
                from('contact_permissions')->
                where('contact_id', '=', $contact_id)->
                where('client_id', '=', $client_id)->
                where('area', '=', $area)->
                fetch();
        }

        // No permission option set
        return true;
    }

    /**
     * Returns an array of key/value pairs of all available permission options
     *
     * @param int $company_id The ID of the company to fetch options under
     * @return array An array of key/value pairs
     */
    public function getPermissionOptions($company_id)
    {
        if (!isset($this->Actions)) {
            Loader::loadModels($this, ['Actions']);
        }

        $options = [
            'client_invoices' => $this->_('Contacts.getPermissionOptions.client_invoices'),
            'client_services' => $this->_('Contacts.getPermissionOptions.client_services'),
            'client_transactions' => $this->_('Contacts.getPermissionOptions.client_transactions'),
            'client_contacts' => $this->_('Contacts.getPermissionOptions.client_contacts'),
            'client_accounts' => $this->_('Contacts.getPermissionOptions.client_accounts'),
            'client_emails' => $this->_('Contacts.getPermissionOptions.client_emails'),
            '_managed' => $this->_('Contacts.getPermissionOptions._managed'),
            '_invoice_delivery' => $this->_('Contacts.getPermissionOptions._invoice_delivery'),
            '_credits' => $this->_('Contacts.getPermissionOptions._credits')
        ];

        $action_locations = [
            'nav_client',
            'widget_client_home'
        ];

        foreach ($action_locations as $location) {
            $plugin_actions = $this->Actions->getAll(['company_id' => $company_id, 'location' => $location], true);
            foreach ($plugin_actions as $plugin_action) {
                if (!array_key_exists($plugin_action->plugin_dir . '.*', $options)) {
                    $options[$plugin_action->plugin_dir . '.*'] = $plugin_action->name;
                }
            }
        }

        return $options;
    }

    /**
     * A partial query of contact permissions
     *
     * @param int $contact_id The ID of the contact to fetch permissions for
     * @return Record
     */
    private function permissions($contact_id)
    {
        $contact = $this->get($contact_id);
        $fields = ['contact_id', 'area'];
        return $this->Record->select($fields)->
            from('contact_permissions')->
            where('client_id', '=', $contact->client_id ?? null)->
            where('contact_id', '=', $contact_id);
    }

    /**
     * Returns the rule set for adding/editing contacts
     *
     * @param array $vars The input vars
     * @param bool $edit Whether the input is being validated for an edit
     * @return array Contact rules
     */
    private function getRules(array $vars, $edit = false)
    {
        Loader::loadComponents($this, ['SettingsCollection']);
        $rules = [
            'client_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => $this->_('Contacts.!error.client_id.exists')
                ]
            ],
            'user_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'users', false],
                    'message' => $this->_('Contacts.!error.user_id.exists')
                ]
            ],
            'contact_type' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [
                        [$this, 'validateContactType'],
                        (isset($vars['contact_id']) ? $vars['contact_id'] : null),
                        (isset($vars['client_id']) ? $vars['client_id'] : null)
                    ],
                    'message' => $this->_('Contacts.!error.contact_type.format')
                ]
            ],
            'contact_type_id' => [
                'format' => [
                    'rule' => [
                        [$this, 'validateContactTypeId'],
                        (isset($vars['contact_type']) ? $vars['contact_type'] : null),
                        (isset($vars['client_id']) ? $vars['client_id'] : null)
                    ],
                    'message' => $this->_('Contacts.!error.contact_type_id.format')
                ]
            ],
            'first_name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Contacts.!error.first_name.empty')
                ]
            ],
            'last_name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Contacts.!error.last_name.empty')
                ]
            ],
            'title' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 64],
                    'message' => $this->_('Contacts.!error.title.length')
                ]
            ],
            'company' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 128],
                    'message' => $this->_('Contacts.!error.company.length')
                ]
            ],
            'email' => [
                'format' => [
                    'rule' => 'isEmail',
                    'message' => $this->_('Contacts.!error.email.format')
                ],
                'unique' => [
                    'rule' => [
                        function ($email, $client_id, $contact_type, $contact_id) {
                            // No client ID, another rule will validate against that
                            if (empty($client_id) || !is_numeric($client_id)) {
                                return true;
                            }

                            // Fetch the client to determine the company
                            $client = $this->Record->select(['clients.*', 'client_groups.company_id'])
                                ->from('clients')
                                ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)
                                ->where('clients.id', '=', $client_id)
                                ->fetch();

                            // Fetch the setting on whether to restrict the email address
                            $contact_email = '';
                            if ($client) {
                                $setting = $this->SettingsCollection->fetchSetting(
                                    null,
                                    $client->company_id,
                                    'unique_contact_emails'
                                );
                                $contact_email = (isset($setting['value']) ? $setting['value'] : $contact_email);
                            }

                            // No contact email setting, or it does not restrict against contact emails
                            if (empty($client)
                                || empty($contact_email)
                                || !in_array($contact_email, ['all', 'primary'])
                            ) {
                                return true;
                            }

                            // Only restrict primary contact email addresses
                            $restrict_primary = false;
                            if ($contact_email == 'primary') {
                                // If this contact is a primary contact, the same email should not be in use
                                // by another primary contact
                                if (empty($contact_type) || $contact_type == 'primary') {
                                    $restrict_primary = true;
                                } else {
                                    // This is not a primary contact, so the restriction does not apply
                                    return true;
                                }
                            }

                            // Ensure the given email is not taken by any other contact in this company
                            $this->Record->select(['contacts.*'])
                                ->from('contacts')
                                ->innerJoin('clients', 'clients.id', '=', 'contacts.client_id', false)
                                ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)
                                ->where('client_groups.company_id', '=', $client->company_id)
                                ->where('contacts.email', '=', $email);

                            // Compare only against other primary contacts
                            if ($restrict_primary) {
                                $this->Record->where('contacts.contact_type', '=', 'primary');
                            }

                            // Exclude this current contact from the result set since
                            // the contact can edit itself
                            if (!empty($contact_id) && is_numeric($contact_id)) {
                                $this->Record->where('contacts.id', '!=', $contact_id);
                            }

                            return $this->Record->numResults() === 0;
                        },
                        ['_linked' => 'client_id'],
                        ['_linked' => 'contact_type'],
                        ['_linked' => 'contact_id']
                    ],
                    'message' => $this->_('Contacts.!error.email.unique')
                ],
            ],
            'state' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 3],
                    'message' => $this->_('Contacts.!error.state.length')
                ],
                'country_exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateStateCountry'], (isset($vars['country']) ? $vars['country'] : null)],
                    'message' => $this->_('Contacts.!error.state.country_exists')
                ]
            ],
            'country' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 3],
                    'message' => $this->_('Contacts.!error.country.length')
                ]
            ]
        ];
        return $rules;
    }

    /**
     * Returns the rule set for adding/editing numbers
     *
     * @param bool $multiple True if validating multiple numbers, false to
     *  validate a single number (optional, default false)
     * @param bool $edit True if editing numbers, false otherwise (optional, default false)
     * @param bool $require_id True to require ID when editing numbers, false otherwise (optional, default false)
     * @param int $client_id The ID of the client this contact is associated with
     * @param array $vars The input vars
     * @return array Number rules
     */
    private function getNumberRules($multiple = false, $edit = false, $require_id = false, $client_id = null, array &$vars = [])
    {
        Loader::loadModels($this, ['ClientGroups']);

        $required_fields = [];
        if (!is_null($client_id)) {
            $client = $this->Record->select('client_group_id')
                ->from('clients')
                ->where('id', '=', $client_id)
                ->fetch();

            if ($client) {
                $required_contact_fields = $this->ClientGroups->getSetting(
                    $client->client_group_id,
                    'required_contact_fields'
                );

                if ($required_contact_fields) {
                    $required_fields = unserialize(base64_decode($required_contact_fields->value ?? ''));
                }
            }
        }

        $rules = [
            'contact_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'contacts'],
                    'message' => $this->_('Contacts.!error.contact_id.exists')
                ]
            ],
            'type' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateContactNumberType']],
                    'message' => $this->_('Contacts.!error.type.format')
                ]
            ],
            'location' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateContactNumberLocation']],
                    'message' => $this->_('Contacts.!error.location.format')
                ]
            ]
        ];

        if ($edit) {
            // Check ID exists on edit
            if ($require_id) {
                $rules['id'] = [
                    'exists' => [
                        'rule' => [[$this, 'validateExists'], 'id', 'contact_numbers'],
                        'message' => $this->_('Contacts.!error.id.exists')
                    ]
                ];
            } else {
                // Remove number constraint for deleting purposes on edit. @see Contacts::edit
                //unset($rules['number']);
            }
        }

        if ($multiple) {
            unset($rules['contact_id']);

            // Format number rules to suit our other rules
            foreach ($rules as $key => $value) {
                $new_key = 'numbers[][' . $key . ']';
                $rules[$new_key] = $value;
                unset($rules[$key]);
            }
        }
        unset($key);

        $required_types = [];
        if (in_array('phone', $required_fields)) {
            $required_types[] = 'phone';
        }
        if (in_array('fax', $required_fields)) {
            $required_types[] = 'fax';
        }
        if (!empty($required_types) && $multiple) {
            // The number can't be empty if the fields are required
            $rules['numbers[][number]'] = [
                'empty' => [
                    'if_set' => $edit,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Contacts.!error.number.empty')
                ]
            ];

            // Check if at least one number has been provided for each required type
            foreach ($required_types as $required_type) {
                $provided_type = false;
                $key = '';
                foreach ($vars['numbers'] as $key => $number) {
                    if ($number['type'] == $required_type) {
                        $provided_type = true;
                        break;
                    }
                }

                if (!$provided_type) {
                    $rules['numbers[' . $key . '][type]']['required'] = [
                        'rule' => false,
                        'message' => $this->_('Contacts.!error.' . $required_type . '.required')
                    ];
                }
            }
        }

        return $rules;
    }

    /**
     * Returns the rule set for adding/editing types
     *
     * @return array Type rules
     */
    private function getTypeRules()
    {
        $rules = [
            'company_id' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('Contacts.!error.company_id.format')
                ]
            ],
            'name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Contacts.!error.name.empty')
                ]
            ],
            'is_lang' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('Contacts.!error.is_lang.format')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 1],
                    'message' => $this->_('Contacts.!error.is_lang.length')
                ]
            ]
        ];
        return $rules;
    }

    /**
     * Validates the contact numbers 'type' field
     *
     * @param string $type The type to check
     * @return bool True if validated, false otherwise
     */
    public function validateContactNumberType($type)
    {
        return in_array($type, ['phone', 'fax']);
    }

    /**
     * Validates the contact numbers 'location' field
     *
     * @param string $location The location to check
     * @return bool True if validated, false otherwise
     */
    public function validateContactNumberLocation($location)
    {
        return in_array($location, ['home', 'work', 'mobile']);
    }

    /**
     * Validates a contact's 'contact_type' field
     *
     * @param string $contact_type The contact type to check
     * @param int $contact_id The ID of the contact being updated
     * @param int $client_id The ID of the contact being added to
     * @return bool True if validated, false otherwise
     */
    public function validateContactType($contact_type, $contact_id = null, $client_id = null)
    {
        // Ensure that if a contact is the primary contact that it can not be changed.
        if ($contact_id !== null) {
            $contact = $this->Record->select(['contacts.contact_type'])->from('contacts')->
                where('contacts.id', '=', $contact_id)->fetch();
            if ($contact && $contact->contact_type == 'primary' && $contact->contact_type != $contact_type) {
                return false;
            }
        }
        // Ensure that only one contact may be set as 'primary' for each client
        if ($client_id !== null && $contact_type == 'primary') {
            $contact = $this->Record->select(['contacts.id'])->from('contacts')->
                where('contacts.contact_type', '=', 'primary')->
                where('contacts.client_id', '=', $client_id)->fetch();
            if ($contact && $contact->id != $contact_id) {
                return false;
            }
        }

        return in_array($contact_type, ['primary', 'billing', 'other']);
    }

    /**
     * Validates a contact's 'contact_type_id' field
     *
     * @param int $contact_type_id The contact type ID to check
     * @param string $contact_type The contact type
     * @param int $client_id The ID of the client the contact's type is being set for
     * @return bool True if validated, false otherwise
     */
    public function validateContactTypeId($contact_type_id, $contact_type, $client_id)
    {
        // A valid contact type ID must be set if $contact_type == "other"
        // if it is not, return false
        if ($contact_type == 'other') {
            // Verify that the contact type ID is available to this client
            $count = $this->Record->select(['contact_types.id'])->from('contact_types')->
                innerJoin('client_groups', 'client_groups.company_id', '=', 'contact_types.company_id', false)->
                on('clients.id', '=', $client_id)->
                innerJoin('clients', 'clients.client_group_id', '=', 'client_groups.id', false)->
                where('contact_types.id', '=', $contact_type_id)->numResults();

            if ($count <= 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validates a country is set when a state is provided
     *
     * @param string $state The state
     * @param string $country The country
     * @return bool True if a country is set, false otherwise
     */
    public function validateCountrySet($state, $country)
    {
        return !empty($country);
    }

    /**
     * Validates whether this contact is the primary contact for a client
     *
     * @param int $contact_id The contact ID
     * @return bool True if this contact is a client's primary billing contact, false otherwise
     */
    public function validateIsPrimary($contact_id)
    {
        $contact = $this->get($contact_id);

        if ($contact && $contact->contact_type == 'primary') {
            return true;
        }
        return false;
    }

    /**
     * Validates whether the given contact is the contact whom invoices are addressed to and
     * can be based on the given contact type
     *
     * @param string $contact_type The contact type of the contact (i.e. "primary", "billing", "other"; optional)
     * @param int $contact_id The contact ID
     * @return bool True if this contact is the contact whom invoices are addressed to, false otherwise
     */
    public function validateReceivesInvoices($contact_type = null, $contact_id = null)
    {
        $contact = $this->get($contact_id);

        if ($contact) {
            $count = $this->Record->select('key')->
                from('client_settings')->
                where('client_id', '=', $contact->client_id)->
                where('key', '=', 'inv_address_to')->
                where('value', '=', $contact->id)->
                numResults();

            // The contact may only be the invoiced contact if it is a primary/billing contact
            // and is set to receive invoices
            if ($count > 0 && !in_array($contact_type, ['primary', 'billing'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validate contact information for add or edit
     *
     * @param array $vars An array of variable contact info, including:
     *
     *  - contact_id The contact ID to update (required on edit)
     *  - client_id The client ID this contact will be associated with
     *  - user_id The user ID this contact belongs to if this contact has their own unique user record (optional)
     *  - staff_id The ID of the staff user, if a staff user is executing the action (optional)
     *  - contact_type The type of contact, either 'primary' (default), 'billing', or 'other' (optional)
     *  - contact_type_id The ID of the contact type if contact_type is 'other' (optional)
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
     * @param bool $edit Whether this data is being validated for an edit (optional, default false)
     * @param bool $validate_client Whether to validate the client ID (optional, default false)
     * @return bool True if the contact info is valid, false otherwise
     * @see Contacts::addNumber()
     */
    public function validateContact(array $vars, $edit = false, $validate_client = false)
    {
        $vars = $this->adjustInput($vars, $edit);
        $rules = $this->getRules($vars, $edit);

        // Check for optional numbers data
        if (!empty($vars['numbers'])) {
            $multiple_numbers = false;
            if (is_array($vars['numbers'])) {
                $multiple_numbers = true;
            }

            $number_rules = $this->getNumberRules($multiple_numbers, $edit, false, $vars['client_id'] ?? null, $vars);

            // Put our rules together into one set
            $rules = array_merge($rules, $number_rules);
        }

        // Add rules for fields required by the client group
        if (isset($vars['client_id'])) {
            $rules = array_merge($rules, $this->getRequiredFieldRules($vars, $edit, ($vars['staff_id'] ?? null)));
            $rules = array_merge($rules, $this->getReadOnlyFieldRules($vars, $edit, ($vars['staff_id'] ?? null)));
        }

        if ($edit) {
            // Validate the contact ID exists
            $rules['contact_id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'contacts'],
                    'message' => $this->_('Contacts.!error.contact_id.exists')
                ]
            ];
            // Validate the contact can be updated based on its status to receive invoices
            $rules['contact_type']['inv_address_to'] = [
                'if_set' => true,
                'rule' => [[$this, 'validateReceivesInvoices'], (isset($vars['contact_id']) ? $vars['contact_id'] : null)],
                'negate' => true,
                'message' => $this->_('Contacts.!error.contact_type.inv_address_to')
            ];

            // Remove client and user ID constraints
            unset($rules['client_id']);
        }

        if (!$validate_client) {
            // Remove client constraints
            unset($rules['client_id']);
        }

        $this->Input->setRules($rules);

        return $this->Input->validates($vars);
    }

    /**
     * Adjusts input for contact creation/editing/validation
     *
     * @param array $vars An array of variable contact info, including:
     *
     *  - contact_id The contact ID to update
     *  - client_id The client ID this contact will be associated with
     *  - user_id The user ID this contact belongs to if this contact has their own unique user record (optional)
     *  - contact_type The type of contact, either 'primary' (default), 'billing', or 'other' (optional)
     *  - contact_type_id The ID of the contact type if contact_type is 'other' (optional)
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
     * @param bool $edit Whether this data is being adjusted for an update (optional, default false)
     * @return array The adjust input data
     */
    private function adjustInput(array $vars, $edit = false)
    {
        $old_contact = null;
        if ($edit) {
            $old_contact = $this->get((isset($vars['contact_id']) ? $vars['contact_id'] : null));
            $vars['client_id'] = (isset($old_contact->client_id) ? $old_contact->client_id : null);

            // Set the contact type for rule validation
            $vars['contact_type'] = (isset($vars['contact_type']) ? $vars['contact_type'] : $old_contact->contact_type);
            $vars['contact_type_id'] = (isset($vars['contact_type_id']) ? $vars['contact_type_id'] : $old_contact->contact_type_id);

            // Set the state to null if it's not given and the country has changed
            if (isset($vars['country'])
                && !isset($vars['state'])
                && $old_contact
                && $old_contact->country != $vars['country']
            ) {
                $vars['state'] = null;
            }
        } else {
            unset($vars['contact_id']);
        }

        return $vars;
    }
}
