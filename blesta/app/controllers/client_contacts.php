<?php

/**
 * Client portal contacts controller
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientContacts extends ClientController
{
    /**
     * Pre action
     */
    public function preAction()
    {
        parent::preAction();

        $this->uses(['Clients', 'Contacts', 'Users']);
    }

    /**
     * List contacts
     */
    public function index()
    {
        // Set sort and order
        $order = (isset($this->get['order']) ? $this->get['order'] : 'asc');
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'first_name');

        // Get all contacts, not primary
        $contacts = $this->Contacts->getAll($this->client->id, null, [$sort => $order]);
        foreach ($contacts as $index => $contact) {
            if ($contact->id == $this->client->contact_id) {
                unset($contacts[$index]);
                break;
            }
        }

        // Skip to add a contact if none currently exist
        if (empty($contacts)) {
            $this->flashMessage('info', Language::_('ClientContacts.!info.no_contacts', true));
            $this->redirect($this->base_uri . 'contacts/add/');
        }

        // Re-index the array
        $contacts = array_values($contacts);

        $this->set('contacts', $contacts);
        $this->set('contact_types', $this->getContactTypes());
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(isset($this->get['whole_widget']) ? null : isset($this->get['sort']));
        }
        $this->set('navigation', $this->partial('client_contacts_navigation'));
    }

    /**
     * Create a contact
     */
    public function add()
    {
        $this->ArrayHelper = $this->DataStructure->create('Array');

        $vars = new stdClass();

        // Set client settings
        $vars->country = $this->client->settings['country'];
        $vars->currency = $this->client->settings['default_currency'];
        $vars->language = $this->client->settings['language'];

        // Get all possible permissions
        $permission_options = $this->Contacts->getPermissionOptions($this->company_id);

        // Filter out any to which this user does not have access
        foreach ($permission_options as $key => $permission_option) {
            if (!$this->hasPermission($key)) {
                unset($permission_options[$key]);
            }
        }

        // Add contact
        $user_errors = false;
        $contact_errors = false;
        if (!empty($this->post)) {
            $this->post['client_id'] = $this->client->id;

            $vars = $this->post;
            unset($vars['user_id']);

            $this->Contacts->begin();

            // Set contact type to 'other' if contact type id is given
            if (isset($this->post['contact_type']) && is_numeric($this->post['contact_type'])) {
                $vars['contact_type_id'] = $this->post['contact_type'];
                $vars['contact_type'] = 'other';
            } else {
                $vars['contact_type_id'] = null;
            }

            // Remove contact ID so as to not interfere with rules since this field is
            // for swapping contact data in the interface
            unset($vars['contact_id']);

            // Format any phone numbers
            $vars['numbers'] = $this->ArrayHelper->keyToNumeric($this->post['numbers'] ?? []);
            $vars['permissions'] = [];
            if (isset($this->post['permissions'])) {
                $this->post['permissions']['area'] = array_intersect(
                    array_keys($permission_options),
                    $this->post['permissions']['area']
                );
                $vars['permissions'] = $this->ArrayHelper->keyToNumeric($this->post['permissions']);
            }

            if (!empty($vars['enable_login'])) {
                $vars['user_id'] = $this->Users->add($vars);
                $user_errors = $this->Users->errors();
            }

            // Create the contact
            $this->Contacts->add($vars);
            $contact_errors = $this->Contacts->errors();

            $errors = array_merge(($contact_errors ? $contact_errors : []), ($user_errors ? $user_errors : []));
            if (!empty($errors)) {
                $this->Contacts->rollback();
                // Error, reset vars
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                $this->Contacts->commit();
                // Success
                $this->flashMessage('message', Language::_('ClientContacts.!success.contact_added', true));
                $this->redirect($this->base_uri . 'contacts/');
            }
        }

        $this->set('permissions', $permission_options);
        $this->set('contact_types', $this->getContactTypes());
        $this->set('vars', $vars);
        $this->set('navigation', $this->partial('client_contacts_navigation'));
        // Set partials
        $this->setContactView($vars);
        $this->setPhoneView($vars);
    }

    /**
     * Edit a contact
     */
    public function edit()
    {
        // Ensure a valid contact was given
        if (!isset($this->get[0])
            || !($contact = $this->Contacts->get((int) $this->get[0]))
            || ($contact->client_id != $this->client->id)
        ) {
            $this->redirect($this->base_uri . 'contacts/');
        }

        $user = false;
        if ($contact->user_id) {
            $user = $this->Users->get($contact->user_id);
        }

        // Get all possible permissions
        $permission_options = $this->Contacts->getPermissionOptions($this->company_id);

        // Filter out any to which this user does not have access
        foreach ($permission_options as $key => $permission_option) {
            if (!$this->hasPermission($key)) {
                unset($permission_options[$key]);
            }
        }

        $this->ArrayHelper = $this->DataStructure->create('Array');

        $vars = [];

        $contact_errors = false;
        $user_errors = false;
        if (!empty($this->post)) {
            $this->Contacts->begin();
            $vars = $this->post;
            unset($vars['user_id']);

            // Set contact type to 'other' if contact type id is given
            if (isset($this->post['contact_type']) && is_numeric($this->post['contact_type'])) {
                $vars['contact_type_id'] = $this->post['contact_type'];
                $vars['contact_type'] = 'other';
            } else {
                $vars['contact_type_id'] = null;
            }

            // Format the phone numbers
            $vars['numbers'] = $this->ArrayHelper->keyToNumeric($this->post['numbers'] ?? []);
            $vars['permissions'] = [];
            if (isset($this->post['permissions'])) {
                $this->post['permissions']['area'] = array_intersect(
                    array_keys($permission_options),
                    $this->post['permissions']['area']
                );
                $vars['permissions'] = $this->ArrayHelper->keyToNumeric($this->post['permissions']);
            }
            ## TODO readd permissions that are already selected but this user doesn't have access to

            if (!empty($vars['enable_login'])) {
                if ($contact->user_id) {
                    if (empty($vars['confirm_password'])) {
                        unset($vars['confirm_password']);
                    }

                    unset($vars['username']);
                    $this->Users->edit($contact->user_id, $vars);
                } else {
                    $vars['user_id'] = $this->Users->add($vars);
                }

                $user_errors = $this->Users->errors();
            } elseif ($contact->user_id) {
                $this->Users->delete($contact->user_id);
                $vars['user_id'] = null;
            }

            // Update the contact
            $this->Contacts->edit($contact->id, $vars);
            $contact_errors = $this->Contacts->errors();

            $errors = array_merge(($contact_errors ? $contact_errors : []), ($user_errors ? $user_errors : []));
            if (!empty($errors)) {
                $this->Contacts->rollback();
                // Error, reset vars
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                $this->Contacts->commit();
                // Success
                $this->flashMessage('message', Language::_('ClientContacts.!success.contact_updated', true));
                $this->redirect($this->base_uri . 'contacts/');
            }
        }

        // Check if the email address has been verified
        $this->uses(['EmailVerifications']);
        if (($email_verification = $this->EmailVerifications->getByContactId($contact->id))) {
            $time = time();
            $hash = $this->Clients->systemHash('c=' . $email_verification->contact_id . '|t=' . $time);
            $message = Language::_(
                'ClientContacts.!info.unverified_email',
                true,
                $email_verification->email
            );
            $options = [
                'info_buttons' => [
                    [
                        'url' => $this->base_uri . 'verify/send/?sid=' . rawurlencode(
                                $this->Clients->systemEncrypt(
                                    'c=' . $email_verification->contact_id . '|t=' . $time . '|h=' . substr($hash, -16)
                                )
                            ),
                        'label' => Language::_('ClientContacts.!info.unverified_email_button', true),
                        'icon_class' => 'fa-share'
                    ]
                ]
            ];

            $this->setMessage('info', $message, false, $options);
        }

        // Set current contact
        if (empty($vars)) {
            $vars = $contact;

            // Set contact type if it is not a default type
            if (is_numeric($vars->contact_type_id)) {
                $vars->contact_type = $vars->contact_type_id;
            }

            // Set contact phone numbers formatted for HTML
            $vars->numbers = $this->ArrayHelper->numericToKey($this->Contacts->getNumbers($contact->id));

            $vars->permissions = $this->ArrayHelper->numericToKey($this->Contacts->getPermissions($contact->id));
        }

        $this->set('permissions', $permission_options);
        $this->set('contact_types', $this->getContactTypes());
        $this->set('contact', $contact);
        $this->set('vars', $vars);
        $this->set('user', $user);
        $this->set('navigation', $this->partial('client_contacts_navigation'));
        // Set partials
        $this->setContactView($vars, true, $contact);
        $this->setPhoneView($vars);
    }

    /**
     * Delete a contact
     */
    public function delete()
    {
        // Ensure a valid contact was given
        if (!isset($this->post['id'])
            || !($contact = $this->Contacts->get((int) $this->post['id']))
            || ($contact->client_id != $this->client->id)) {
            $this->redirect($this->base_uri . 'contacts/');
        }

        // Attempt to delete the contact
        $this->Contacts->delete($contact->id);

        if (($errors = $this->Contacts->errors())) {
            $this->flashMessage('error', $errors);
        } else {
            $this->flashMessage(
                'message',
                Language::_('ClientContacts.!success.contact_deleted', true, $contact->first_name, $contact->last_name)
            );
        }

        $this->redirect($this->base_uri . 'contacts/');
    }

    /**
     * Sets the contact partial view
     * @see ClientContacts::add(), ClientContacts::edit()
     *
     * @param stdClass $vars The input vars object for use in the view
     * @param bool $edit True if this is an edit, false otherwise
     * @param stdClass $contact An object representing the current contact being updated
     */
    private function setContactView(stdClass $vars, $edit = false, $contact = null)
    {
        $this->uses(['Countries', 'States', 'ClientGroups']);

        $contacts = [];
        $contact_fields_groups = ['required_contact_fields', 'shown_contact_fields', 'read_only_contact_fields'];

        if (!$edit) {
            // Set an option for no contact
            $no_contact = [
                (object) [
                    'id' => 'none',
                    'first_name' => Language::_('ClientContacts.setcontactview.text_none', true),
                    'last_name' => ''
                ]
            ];

            // Set all contacts whose info can be prepopulated (primary or billing only)
            $contacts = array_merge(
                $this->Contacts->getAll($this->client->id, 'primary'),
                $this->Contacts->getAll($this->client->id, 'billing')
            );
            $contacts = array_merge($no_contact, $contacts);
        }

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
            'edit' => $edit,
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

        $this->set('contact_info', $this->partial('client_contacts_contact_info', $contact_info));
    }

    /**
     * Sets the contact phone number partial view
     * @see ClientContacts::add(), ClientContacts::edit()
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
     * Retrieves a list of contact types. Useful for a drop-down list
     * @see ClientContacts::index(), ClientContacts::add(), ClientContacts::edit()
     *
     * @return array A list of contact types
     */
    private function getContactTypes()
    {
        // Set all contact types besides 'primary' and 'other'
        $contact_types = $this->Contacts->getContactTypes();
        $contact_type_ids = $this->Form->collapseObjectArray(
            $this->Contacts->getTypes($this->client->company_id),
            'real_name',
            'id'
        );
        unset($contact_types['primary'], $contact_types['other']);

        return $contact_types + $contact_type_ids;
    }
}
