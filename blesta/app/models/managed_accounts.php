<?php
/**
 * Managed Accounts Model
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ManagedAccounts extends AppModel
{
    /**
     * @var string The client uri
     */
    private $client_uri;

    /**
     * Initialize ManagedAccounts
     */
    public function __construct()
    {
        parent::__construct();

        Language::loadLang(['managed_accounts']);
        Loader::loadModels($this, ['Contacts', 'Clients', 'Users', 'Emails', 'EmailVerifications']);
        Loader::loadHelpers($this, ['DataStructure']);

        $this->client_uri = WEBDIR . Configure::get('Route.client') . '/';
    }

    /**
     * Manage an account
     *
     * @param int $contact_id The ID of the contact belonging to the manager
     * @param int $client_id The ID of the client to manage
     */
    public function manage(int $contact_id, int $client_id)
    {
        $rules = [
            'contact_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'contacts'],
                    'message' => $this->_('ManagedAccounts.!error.contact_id.exists')
                ]
            ],
            'client_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => $this->_('ManagedAccounts.!error.client_id.exists')
                ]
            ]
        ];
        $this->Input->setRules($rules);

        $data = ['contact_id' => $contact_id, 'client_id' => $client_id];
        if ($this->Input->validates($data)) {
            Loader::loadModels($this, ['Users']);
            Loader::loadComponents($this, ['Session']);

            // Validate if the manager has permissions to manage the provided client
            $managed_account = $this->get($contact_id, $client_id);
            if (empty($managed_account->permissions)) {
                $this->Input->setErrors(['permissions' => ['manage' => $this->_('ManagedAccounts.!error.permissions.manage')]]);

                return false;
            }

            // Set session to manage the provided client id
            $this->Session->write('blesta_client_id', $client_id);

            // Save the client id of the manager, to switch back later
            $this->Session->write('blesta_contact_id', $contact_id);
        }
    }

    /**
     * Switches back to the manager account
     *
     * @param int $contact_id The ID of the contact of the manager to switch back
     * @param int $client_id The ID of the client currently being managed by the manager
     */
    public function switchBack(int $contact_id, int $client_id)
    {
        $rules = [
            'contact_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'contacts'],
                    'message' => $this->_('ManagedAccounts.!error.contact_id.exists')
                ]
            ],
            'client_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => $this->_('ManagedAccounts.!error.client_id.exists')
                ]
            ]
        ];
        $this->Input->setRules($rules);

        $data = ['contact_id' => $contact_id, 'client_id' => $client_id];
        if ($this->Input->validates($data)) {
            Loader::loadModels($this, ['Users']);
            Loader::loadComponents($this, ['Session']);

            // Get contact
            $contact = $this->Contacts->get($contact_id);

            // Validate if the manager has permissions to manage the provided client
            $managed_account = $this->get($contact_id, $client_id);
            if (empty($managed_account->permissions)) {
                $this->Input->setErrors(['permissions' => ['manage' => $this->_('ManagedAccounts.!error.permissions.manage')]]);

                return false;
            }

            // Set session to manage the provided client id
            $this->Session->write('blesta_client_id', $contact->client_id);

            // Clear the manager client id
            $this->Session->clear('blesta_contact_id');
        }
    }

    /**
     * Grants access to a contact to manage a specific client
     *
     * @param mixed $invitation_id The ID or the token of the invitation to approve
     */
    public function accept($invitation_id)
    {
        $invitation = null;
        if (is_numeric($invitation_id)) {
            $invitation = $this->getInvitation($invitation_id);
        } else if (is_string($invitation_id)) {
            $invitation = $this->getInvitationByToken($invitation_id);
        }

        if (empty($invitation)) {
            return false;
        }

        // Get client to manage
        $client = $this->Clients->get($invitation->client_id);

        // Get client manager by email
        $manager = $this->Record
            ->select(['contacts.*'])
            ->from('contacts')
            ->innerJoin('clients', 'clients.id', '=', 'contacts.client_id', false)
            ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)
            ->where('contacts.email', '=', $invitation->email)
            ->where('contacts.contact_type', '=', 'primary')
            ->where('client_groups.company_id', '=', Configure::get('Blesta.company_id'))
            ->fetch();

        // Set permissions
        if (!empty($manager)) {
            $invitation->permissions = unserialize($invitation->permissions ?? 'a:0:{}');
            $this->setPermissions($manager->id, $client->id, $invitation->permissions ?? []);
        }

        // Update invitation
        $this->Record->where('id', '=', $invitation->id)
            ->update('account_management_invitations', ['status' => 'accepted']);

        return true;
    }

    /**
     * Declines the invitation to manage a specific client
     *
     * @param mixed $invitation_id The ID or the token of the invitation to decline
     */
    public function decline($invitation_id)
    {
        $invitation = null;
        if (is_numeric($invitation_id)) {
            $invitation = $this->getInvitation($invitation_id);
        } else if (is_string($invitation_id)) {
            $invitation = $this->getInvitationByToken($invitation_id);
        }

        if (empty($invitation)) {
            return false;
        }

        // Remove invitation
        $this->Record
            ->from('account_management_invitations')
            ->where('id', '=', $invitation->id)
            ->delete();

        return true;
    }

    /**
     * Generates and mails a new invitation
     *
     * @param int $client_id The ID of the client to manage
     * @param string $email The email address of the contact that will be granted access
     * @param array $permissions A numerically indexed array of arrays containing:
     *
     *  - area The area
     * @return bool True if the invitation was sent successfully
     */
    public function invite(int $client_id, string $email, array $permissions)
    {
        // Validate if the email provided belongs to an existing account
        $client = $this->Record
            ->select(['clients.*'])
            ->from('contacts')
            ->innerJoin('clients', 'clients.id', '=', 'contacts.client_id', false)
            ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)
            ->where('contacts.email', '=', $email)
            ->where('contacts.contact_type', '=', 'primary')
            ->where('client_groups.company_id', '=', Configure::get('Blesta.company_id'))
            ->fetch();

        // Check if another invitation for the same client and email exists
        $rules = [
            'client_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => $this->_('ManagedAccounts.!error.client_id.exists')
                ]
            ],
            'email' => [
                'invitation' => [
                    'rule' => function ($email) use ($client_id) {
                        $invitation = $this->Record
                            ->select()
                            ->from('account_management_invitations')
                            ->where('account_management_invitations.email', '=', $email)
                            ->where('account_management_invitations.client_id', '=', $client_id)
                            ->fetch();

                        return empty($invitation);
                    },
                    'message' => $this->_('ManagedAccounts.!error.email.invitation'),
                    'post_format' => 'strtolower'
                ]
            ],
            'permissions' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('ManagedAccounts.!error.permissions.empty')
                ]
            ]
        ];
        $this->Input->setRules($rules);

        // Generate invitation token
        $token = $this->EmailVerifications->generateToken($email);

        // Set the invitation status
        $status = 'pending';
        if (empty($client)) {
            $status = 'invalid';
        }

        // Save the invitation
        $data = ['client_id' => $client_id, 'email' => $email, 'permissions' => $permissions];
        if ($this->Input->validates($data)) {
            $invitation = array_merge($data, [
                'permissions' => serialize($permissions ?? []),
                'token' => $token,
                'status' => $status
            ]);
            $this->Record->insert('account_management_invitations', $invitation);
            $invitation_id = $this->Record->lastInsertId();

            // Send the invitation email
            if ($status == 'invalid') {
                return true;
            }
            return $this->send($invitation_id);
        }

        return false;
    }

    /**
     * Sends the email invitation link
     *
     * @param int $invitation_id The ID of the email invitation to send
     * @return bool Returns true if the message was successfully sent, false otherwise
     */
    public function send(int $invitation_id)
    {
        $rules = [
            'invitation_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'account_management_invitations'],
                    'message' => $this->_('ManagedAccounts.!error.invitation_id.exists')
                ]
            ]
        ];
        $this->Input->setRules($rules);

        $data = ['invitation_id' => $invitation_id];
        if ($this->Input->validates($data)) {
            $invitation = $this->getInvitation($invitation_id);
            $client = $this->Clients->get($invitation->client_id);
            $contact = $this->Contacts->getByUserId(null, $client->id);

            if ($invitation->status !== 'pending') {
                return true;
            }

            // Get the company hostname
            $hostname =
                isset(Configure::get('Blesta.company')->hostname) ? Configure::get('Blesta.company')->hostname : '';

            // Send email verification link
            $tags = [
                'contact' => $contact,
                'company' => Configure::get('Blesta.company'),
                'verification_url' => $hostname . $this->client_uri . 'managers/invite/?token=' . $invitation->token,
            ];

            return $this->Emails->send(
                'account_management_invite',
                Configure::get('Blesta.company_id'),
                Configure::get('Blesta.language'),
                $invitation->email,
                $tags,
                null,
                null,
                null,
                ['to_client_id' => $client->id, 'log_masked_tags' => ['verification_url']]
            );
        }

        return false;
    }

    /**
     * Checks whether or not the manager has permissions for the given area
     *
     * @param int $company_id The ID of the company
     * @param int $contact_id The ID of the contact
     * @param string $area The area to check
     * @param int $client_id The ID of the client being managed by the contact
     * @return bool True if permission allowed, false otherwise
     */
    public function hasPermission($company_id, $contact_id, $area, $client_id)
    {
        if ($area == '_managed') {
            return false;
        }

        $options = $this->getPermissionOptions($company_id);

        $parts = explode('.', $area, 2);
        $area = $parts[0] . (isset($parts[1]) ? '.*' : '');

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
     * Sets permissions for the given contact and client
     *
     * @param int $contact_id The ID of the contact to set permissions for
     * @param array $vars A numerically indexed array of arrays containing:
     *
     *  - area The area
     */
    public function setPermissions(int $contact_id, int $client_id, array $vars)
    {
        $rules = [
            'contact_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'contacts'],
                    'message' => $this->_('ManagedAccounts.!error.contact_id.exists')
                ]
            ],
            'client_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => $this->_('ManagedAccounts.!error.client_id.exists')
                ]
            ]
        ];
        $this->Input->setRules($rules);

        $data = ['contact_id' => $contact_id, 'client_id' => $client_id];
        if ($this->Input->validates($data)) {
            $this->Record
                ->from('contact_permissions')
                ->where('contact_id', '=', $contact_id)
                ->where('client_id', '=', $client_id)
                ->delete();

            $fields = ['contact_id', 'client_id', 'area'];
            foreach ($vars['area'] ?? [] as $area) {
                $permission = ['area' => $area];
                $permission['contact_id'] = $contact_id;
                $permission['client_id'] = $client_id;

                $this->Record->insert('contact_permissions', $permission, $fields);
            }
        }
    }

    /**
     * Returns an array of key/value pairs of all available permission options
     *
     * @param int $company_id The ID of the company to fetch options under
     * @return array An array of key/value pairs
     */
    public function getPermissionOptions($company_id)
    {
        if (!isset($this->Contacts)) {
            Loader::loadModels($this, ['Contacts']);
        }

        $options = $this->Contacts->getPermissionOptions($company_id);
        if (isset($options['_managed'])) {
            unset($options['_managed']);
        }

        return $options;
    }

    /**
     * Revokes the access from a contact to a specific client
     *
     * @param int $contact_id The ID of the contact who have been granted access to a specific client
     * @param int $client_id the ID of the client
     * @return \PDOStatement
     */
    public function revoke(int $contact_id, int $client_id)
    {
        $rules = [
            'contact_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'contacts'],
                    'message' => $this->_('ManagedAccounts.!error.contact_id.exists')
                ]
            ],
            'client_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => $this->_('ManagedAccounts.!error.client_id.exists')
                ]
            ]
        ];
        $this->Input->setRules($rules);

        $data = ['contact_id' => $contact_id, 'client_id' => $client_id];
        if ($this->Input->validates($data)) {
            return $this->Record->from('contact_permissions')
                ->where('contact_permissions.contact_id', '=', $contact_id)
                ->where('contact_permissions.client_id', '=', $client_id)
                ->innerJoin('contacts', 'contacts.id', '=', 'contact_permissions.contact_id', false)
                ->innerJoin(
                    'account_management_invitations',
                    'account_management_invitations.email',
                    '=',
                    'contacts.email',
                    false
                )
                ->delete(['contact_permissions.*', 'account_management_invitations.*']);
        }
    }

    /**
     * Fetch an invitation
     *
     * @param int $invitation_id The ID of the invitation to fetch
     * @return mixed The invitation
     */
    public function getInvitation(int $invitation_id)
    {
        return $this->Record->select()
            ->from('account_management_invitations')
            ->where('account_management_invitations.id', '=', $invitation_id)
            ->fetch();
    }

    /**
     * Fetch an invitation by the token
     *
     * @param string $token The token of the invitation to fetch
     * @return mixed The invitation
     */
    public function getInvitationByToken(string $token)
    {
        return $this->Record->select()
            ->from('account_management_invitations')
            ->where('account_management_invitations.token', '=', $token)
            ->fetch();
    }

    /**
     * Fetches the pending invitations for a given client
     *
     * @param int $client_id The ID of the client to fetch the pending invitations
     * @return mixed An array of objects representing the pending invitations
     */
    public function getInvitations(int $client_id)
    {
        return $this->Record->select()
            ->from('account_management_invitations')
            ->where('account_management_invitations.client_id', '=', $client_id)
            ->fetchAll();
    }

    /**
     * Fetches a managed account
     *
     * @param int $contact_id The ID of the contact to fetch
     * @param int $client_id The ID of the client the contact is managing
     * @return mixed An object containing the manager, false if manager doesn't exist
     */
    public function get(int $contact_id, int $client_id)
    {
        // Load format helper for settings
        $this->ArrayHelper = $this->DataStructure->create('Array');

        $permissions = $this->Record->select(['contact_permissions.area'])
            ->from('contact_permissions')
            ->where('contact_permissions.contact_id', '=', $contact_id)
            ->where('contact_permissions.client_id', '=', $client_id)
            ->fetchAll();
        $permissions = $this->ArrayHelper->numericToKey($permissions);

        if (!empty($permissions)) {
            $client = $this->Clients->get($client_id);
            $client->permissions = (array) $permissions;

            return $client;
        }

        return false;
    }

    /**
     * Fetches a manager
     *
     * @param int $contact_id The ID of the contact to fetch
     * @param int $client_id The ID of the client the contact is managing
     * @return mixed An object containing the manager, false if manager doesn't exist
     */
    public function getManager(int $contact_id, int $client_id)
    {
        // Load format helper for settings
        $this->ArrayHelper = $this->DataStructure->create('Array');

        $permissions = $this->Record->select(['contact_permissions.area'])
            ->from('contact_permissions')
            ->where('contact_permissions.contact_id', '=', $contact_id)
            ->where('contact_permissions.client_id', '=', $client_id)
            ->fetchAll();
        $permissions = $this->ArrayHelper->numericToKey($permissions);

        if (!empty($permissions)) {
            $contact = $this->Contacts->get($contact_id);
            $contact->permissions = (array) $permissions;

            return $contact;
        }

        return false;
    }

    /**
     * Fetches a list of all accounts managed by a specific client
     *
     * @param int $client_id The client ID to fetch managed accounts for
     * @param int $page The page to return results for (optional, default 1)
     * @param array $order The sort and order fields (optional, default the last name and first name ascending)
     * @return array An array of objects
     */
    public function getList(int $client_id, int $page = 1, array $order = ['last_name' => 'asc', 'first_name' => 'asc'])
    {
        $this->Record = $this->getAccounts($client_id);

        // Return the results
        return $this->Record->order($order)
            ->limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())
            ->fetchAll();
    }

    /**
     * Fetches a list of all managers who have been granted access to a specific client
     *
     * @param int $client_id The client ID to fetch users for
     * @param int $page The page to return results for (optional, default 1)
     * @param array $order The sort and order fields (optional, default the last name and first name ascending)
     * @return array An array of objects
     */
    public function getManagersList(int $client_id, int $page = 1, array $order = ['last_name' => 'asc', 'first_name' => 'asc'])
    {
        $this->Record = $this->getManagers($client_id);

        // Return the results
        return $this->Record->order($order)
            ->limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())
            ->fetchAll();
    }

    /**
     * Return the total number of users returned from ManagedAccounts::getList(),
     * useful in constructing pagination for the getList() method.
     *
     * @param int $client_id The client ID to fetch managed accounts for
     * @return int The total number of users
     * @see Companies::getList()
     */
    public function getListCount(int $client_id)
    {
        $this->Record = $this->getAccounts($client_id);

        // Return the number of results
        return $this->Record->numResults();
    }

    /**
     * Return the total number of users returned from ManagedAccounts::getManagersList(),
     * useful in constructing pagination for the getManagersList() method.
     *
     * @param int $client_id The client ID to fetch managers for
     * @return int The total number of users
     * @see Companies::getList()
     */
    public function getManagersListCount(int $client_id)
    {
        $this->Record = $this->getManagers($client_id);

        // Return the number of results
        return $this->Record->numResults();
    }

    /**
     * Fetches a list of all accounts managed by a specific client
     *
     * @param int $client_id The client ID to fetch all accounts that can be managed
     * @param array $order The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @return array A list of stdClass objects representing each contact
     */
    public function getAll(int $client_id, array $order = ['last_name' => 'asc', 'first_name' => 'asc'])
    {
        $this->Record = $this->getAccounts($client_id);

        return $this->Record->order($order)->fetchAll();
    }

    /**
     * Fetches a list of all contacts managing a specific client
     *
     * @param int $client_id The client ID to fetch all users who have been granted access
     * @param array $order The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @return array A list of stdClass objects representing each contact
     */
    public function getAllManagers(int $client_id, array $order = ['last_name' => 'asc', 'first_name' => 'asc'])
    {
        $this->Record = $this->getManagers($client_id);

        return $this->Record->order($order)->fetchAll();
    }

    /**
     * Search accounts
     *
     * @param int $client_id The client ID to fetch accounts for
     * @param string $query The value to search accounts for
     * @param int $page The page number of results to fetch (optional, default 1)
     * @return array An array of accounts that match the search criteria
     */
    public function search(int $client_id, $query, $page = 1)
    {
        $this->Record = $this->searchAccounts($client_id, $query);

        if ($page == 0) {
            return $this->Record->fetchAll();
        }

        return $this->Record->limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())
            ->fetchAll();
    }

    /**
     * Return the total number of accounts returned from ManagedAccounts::search(), useful
     * in constructing pagination
     *
     * @param int $client_id The client ID to fetch accounts for
     * @param string $query The value to search accounts for
     * @see ManagedAccounts::search()
     */
    public function getSearchCount(int $client_id, $query)
    {
        $this->Record = $this->searchAccounts($query);

        return $this->Record->numResults();
    }

    /**
     * Partially constructs the query for searching accounts
     *
     * @param int $client_id The client ID to fetch accounts for
     * @param string $query The value to search accounts for
     * @return Record The partially constructed query Record object
     * @see ManagedAccounts::search(), ManagedAccounts::getSearchCount()
     */
    private function searchAccounts(int $client_id, $query)
    {
        $this->Record = $this->getAccounts($client_id);

        $sub_query_sql = $this->Record->get();
        $values = $this->Record->values;
        $this->Record->reset();

        $this->Record->select(['temp.*'])->appendValues($values)
            ->from([$sub_query_sql => 'temp'])
            ->like('CONVERT(temp.client_id_code USING utf8)', '%' . $query . '%', true, false)
            ->orLike('temp.company', '%' . $query . '%')
            ->orLike("CONCAT_WS(' ', temp.first_name, temp.last_name)", '%' . $query . '%')
            ->orLike('temp.email', '%' . $query . '%')
            ->group(['temp.id']);

        return $this->Record;
    }

    /**
     * Partially constructs the query required by both ManagedAccounts::getManagersList() and
     * ManagedAccounts::getManagersListCount()
     *
     * @param int $client_id The client ID to fetch users for
     * @return Record The partially constructed query Record object
     */
    private function getAccounts(int $client_id)
    {
        Loader::loadModels($this, ['Clients']);
        $client = $this->Clients->get($client_id);

        return $this->Record->select([
            'clients.*', 'contacts.first_name', 'contacts.last_name', 'contacts.email', 'contacts.company',
            'contacts.id' => 'contact_id', 'REPLACE(clients.id_format, ?, clients.id_value)' => 'client_id_code'
        ])
            ->appendValues([$this->replacement_keys['clients']['ID_VALUE_TAG']])
            ->from('contact_permissions')
            ->innerJoin('contacts', 'contacts.client_id', '=', 'contact_permissions.client_id', false)
            ->innerJoin('clients', 'clients.id', '=', 'contacts.client_id', false)
            ->where('contacts.contact_type', '=', 'primary')
            ->where('contact_permissions.contact_id', '=', $client->contact_id)
            ->where('contact_permissions.client_id', '!=', $client->id)
            ->group('contact_permissions.client_id');
    }

    /**
     * Partially constructs the query required by both ManagedAccounts::getManagersList() and
     * ManagedAccounts::getManagersListCount()
     *
     * @param int $client_id The client ID to fetch users for
     * @return Record The partially constructed query Record object
     */
    private function getManagers(int $client_id)
    {
        return $this->Record->select([
            'account_management_invitations.*', 'contacts.id' => 'contact_id', 'contacts.first_name',
            'contacts.last_name', 'contacts.company', 'clients.id_format', 'clients.id_value', 'clients.user_id',
            'clients.client_group_id', 'REPLACE(clients.id_format, ?, clients.id_value)' => 'client_id_code'
        ])
            ->appendValues([$this->replacement_keys['clients']['ID_VALUE_TAG']])
            ->from('account_management_invitations')
            ->leftJoin('contacts', 'contacts.email', '=', 'account_management_invitations.email', false)
            ->leftJoin('clients', 'clients.id', '=', 'contacts.client_id', false)
            ->on('contacts.contact_type', '=', 'primary')
            ->where('account_management_invitations.client_id', '=', $client_id)
            ->where('contacts.contact_type', '=', 'primary');
    }
}
