<?php

/**
 * Accounts contain both ACH and Credit Card account information. Permits
 * accounts to be fetched, added, edited, and deleted. Some accounts may require
 * processing with remote gateways when added or edited. In such instances
 * certain account details are not stored within the system, but only off-site
 * on the remote gateway.
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Accounts extends AppModel
{
    /**
     * Initialize Accounts
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['accounts']);
    }

    /**
     * Returns a list of all ACH accounts for a given contact
     *
     * @param int $contact_id The contact to fetch on
     * @param string $sortby The field to sort on
     * @param string $order The order to sort (asc, desc)
     * @return mixed An array of objects containing ACH fields or false if none exist
     */
    public function getListAch($contact_id, $sortby = 'last_name', $order = 'asc')
    {
        return $this->Record->select()->from('accounts_ach')->
            where('contact_id', '=', $contact_id)->where('status', '=', 'active')->order([$sortby => $order])->
            fetchAll();
    }

    /**
     * Returns a list of all CC accounts for a given contact
     *
     * @param int $contact_id The contact to fetch on
     * @param string $sortby The field to sort on
     * @param string $order The order to sort (asc, desc)
     * @return mixed An array of objects containing CC fields or false if none exist
     */
    public function getListCc($contact_id, $sortby = 'last_name', $order = 'asc')
    {
        return $this->Record->select()->from('accounts_cc')->
            where('contact_id', '=', $contact_id)->where('status', '=', 'active')->order([$sortby => $order])->
            fetchAll();
    }

    /**
     * Retrieves a list of all CC accounts for a given contact
     *
     * @param int $contact_id The contact to fetch on
     * @return mixed An array of objects containing CC account records, or false if none exist
     */
    public function getAllCc($contact_id)
    {
        $accounts = $this->Record->select(['accounts_cc.*'])->select(["'cc'" => 'account_type'], false)->
            from('accounts_cc')->where('accounts_cc.contact_id', '=', $contact_id)->
            where('status', '=', 'active')->fetchAll();

        // Decrypt fields
        if ($accounts) {
            foreach ($accounts as &$account) {
                $account->last4 = $this->systemDecrypt($account->last4);
                $account->expiration = $this->systemDecrypt($account->expiration);
            }
        }
        return $accounts;
    }

    /**
     * Retrieves a list of all CC accounts for a given client
     *
     * @param int $client_id The client ID to fetch on
     * @return array An array of objects containing CC account records
     */
    public function getAllCcByClient($client_id)
    {
        $accounts = $this->Record->select(['accounts_cc.*'])->select(["'cc'" => 'account_type'], false)->
            from('accounts_cc')->innerJoin('contacts', 'contacts.id', '=', 'accounts_cc.contact_id', false)->
            where('contacts.client_id', '=', $client_id)->
            where('accounts_cc.status', '=', 'active')->fetchAll();

        // Decrypt fields
        if ($accounts) {
            foreach ($accounts as &$account) {
                $account->last4 = $this->systemDecrypt($account->last4);
                $account->expiration = $this->systemDecrypt($account->expiration);
            }
        }
        return $accounts;
    }

    /**
     * Retrieves a list of all ACH accounts for a given contact
     *
     * @param int $contact_id The contact to fetch on
     * @param bool $unverified True to include unverified accounts
     * @return mixed An array of objects containing ACH account records, or false if none exist
     */
    public function getAllAch($contact_id, $unverified = false)
    {
        $accounts = $this->Record->select(['accounts_ach.*'])->select(["'ach'" => 'account_type'], false)->
            from('accounts_ach')->where('accounts_ach.contact_id', '=', $contact_id);

        if ($unverified) {
            $accounts->open()
                ->where('status', '=', 'active')
                ->orWhere('status', '=', 'unverified')
                ->close();
        } else {
            $accounts->where('status', '=', 'active');
        }

        $accounts = $accounts->fetchAll();

        // Decrypt fields
        if ($accounts) {
            foreach ($accounts as &$account) {
                $account->last4 = $this->systemDecrypt($account->last4);
            }
        }
        return $accounts;
    }

    /**
     * Retrieves a list of all ACH accounts for a given client
     *
     * @param int $client_id The client ID to fetch on
     * @param bool $unverified True to include unverified accounts
     * @return array An array of objects containing ACH account records
     */
    public function getAllAchByClient($client_id, $unverified = false)
    {
        $accounts = $this->Record->select(['accounts_ach.*'])->select(["'ach'" => 'account_type'], false)->
            from('accounts_ach')->innerJoin('contacts', 'contacts.id', '=', 'accounts_ach.contact_id', false)->
            where('contacts.client_id', '=', $client_id);

        if ($unverified) {
            $accounts->open()
                ->where('status', '=', 'active')
                ->orWhere('status', '=', 'unverified')
                ->close();
        } else {
            $accounts->where('status', '=', 'active');
        }

        $accounts = $accounts->fetchAll();

        // Decrypt fields
        if ($accounts) {
            foreach ($accounts as &$account) {
                $account->last4 = $this->systemDecrypt($account->last4);
            }
        }
        return $accounts;
    }

    /**
     * Retrieves a single CC account
     *
     * @param int $account_id The ID of the account to get
     * @param bool $decrypt Whether or not to decrypt the account number
     * @param string $passphrase The passphrase required to decrypt accounts (if set)
     * @param int $staff_id The ID of the staff member decrypting the account
     * @return mixed An object containing the CC account fields, or false if none exist
     */
    public function getCc($account_id, $decrypt = false, $passphrase = null, $staff_id = null)
    {
        $account = $this->Record->select(['accounts_cc.*', 'contacts.client_id'])->from('accounts_cc')->
            innerJoin('contacts', 'contacts.id', '=', 'accounts_cc.contact_id', false)->
            where('accounts_cc.id', '=', $account_id)->fetch();

        // Decrypt fields
        if ($account) {
            $orig_last4 = $account->last4;
            $account->last4 = $this->systemDecrypt($account->last4);
            $account->expiration = $this->systemDecrypt($account->expiration);

            if ($decrypt) {
                // Log account access
                if ($staff_id) {
                    if (!isset($this->Logs)) {
                        Loader::loadModels($this, ['Logs']);
                    }
                    $this->Logs->addAccountAccess([
                        'staff_id' => $staff_id,
                        'first_name' => $account->first_name,
                        'last_name' => $account->last_name,
                        'type' => 'cc',
                        'account_id' => $account_id,
                        'account_type' => $account->type,
                        'last4' => $orig_last4
                    ]);
                }
                $account->number = $this->accountDecrypt($account->number, $passphrase);
            }
        }
        return $account;
    }

    /**
     * Retrieves a single ACH account
     *
     * @param int $account_id The ID of the account to get
     * @param bool $decrypt Whether or not to decrypt the account number
     * @param string $passphrase The passphrase required to decrypt accounts (if set)
     * @param int $staff_id The ID of the staff member decrypting the account
     * @return mixed An object containing the ACH account fields, or false if none exist
     */
    public function getAch($account_id, $decrypt = false, $passphrase = null, $staff_id = null)
    {
        $account = $this->Record->select(['accounts_ach.*', 'contacts.client_id'])->from('accounts_ach')->
            innerJoin('contacts', 'contacts.id', '=', 'accounts_ach.contact_id', false)->
            where('accounts_ach.id', '=', $account_id)->fetch();

        // Decrypt fields
        if ($account) {
            $orig_last4 = $account->last4;
            $account->last4 = $this->systemDecrypt($account->last4);

            if ($decrypt) {
                // Log account access
                if ($staff_id) {
                    if (!isset($this->Logs)) {
                        Loader::loadModels($this, ['Logs']);
                    }
                    $this->Logs->addAccountAccess([
                        'staff_id' => $staff_id,
                        'first_name' => $account->first_name,
                        'last_name' => $account->last_name,
                        'type' => 'ach',
                        'account_id' => $account_id,
                        'account_type' => $account->type,
                        'last4' => $orig_last4
                    ]);
                }

                $account->account = $this->accountDecrypt($account->account, $passphrase);
                $account->routing = $this->accountDecrypt($account->routing, $passphrase);
            }
        }
        return $account;
    }

    /**
     * Returns the client reference ID previously used for any payment accounts belonging to the client
     * under the given gateway
     *
     * @param int $client_id The ID of the client to fetch the client reference ID for
     * @param int $gateway_id The ID of the gateway to that previous account was set up under
     * @param string $status The payment account status by which to filter (optional) (active or inactive)
     * @param string $type The payment account type by which to filter (optional) (cc or ach)
     * @return string Returns the client reference ID if found, null otherwise
     */
    public function getClientReferenceId($client_id, $gateway_id, $status = null, $type = null)
    {
        $fields = ['accounts_ach.client_reference_id' => 'ach_client_reference_id',
            'accounts_cc.client_reference_id' => 'cc_client_reference_id'];
        $this->Record->select($fields)->
            from('clients')->
            innerJoin('contacts', 'contacts.client_id', '=', 'clients.id', false)->
            on('accounts_ach.gateway_id', '=', $gateway_id)->
            leftJoin('accounts_ach', 'accounts_ach.contact_id', '=', 'contacts.id', false)->
            on('accounts_cc.gateway_id', '=', $gateway_id)->
            leftJoin('accounts_cc', 'accounts_cc.contact_id', '=', 'contacts.id', false)->
            where('clients.id', '=', $client_id);

        // Filter by status
        if ($status !== null) {
            $this->Record->open()->
                    where('accounts_ach.status', '=', $status)->
                    orWhere('accounts_cc.status', '=', $status)->
                close();
        }

        // Check for accounts only of the given type and order by ID
        if (in_array($type, ['cc', 'ach'])) {
            $this->Record->order(['accounts_' . $type . '.id' => 'desc']);
        }

        $account = $this->Record->fetch();

        if ($account) {
            if ($account->ach_client_reference_id && ($type === null || $type == 'ach')) {
                return $account->ach_client_reference_id;
            }
            if ($account->cc_client_reference_id && ($type === null || $type == 'cc')) {
                return $account->cc_client_reference_id;
            }
        }
        return null;
    }

    /**
     * Records an ACH account into the system
     *
     * @param array $vars An array of ACH account info including:
     *
     *  - contact_id The contact ID tied to this account
     *  - first_name The first name on the account
     *  - last_name The last name on the account
     *  - address1 The address line 1 on the account (optional)
     *  - address2 The address line 2 on the account (optional)
     *  - city The city on the account (optional)
     *  - state The ISO 3166-2 subdivision code on the account (optional)
     *  - zip The zip code on the account (optional)
     *  - country The ISO 3166-1 2-character country code (optional, defaults to 'US')
     *  - account The account number (will be encrypted) (optional)
     *  - routing The routing number (will be encrypted) (optional)
     *  - last4 The last 4 digits of the account number (will be encrypted) (optional if account is given)
     *  - type The type of account, 'checking' or 'savings', (optional, defaults to 'checking')
     *  - reference_id The reference ID attached to this account given by the payment processor (optional)
     *  - client_reference_id The reference ID for the client this payment account belongs to (optional)
     *  - currency The currency in which is denominated the account (optional)
     * @return int The ACH account ID for the record just added, void if not added
     */
    public function addAch(array $vars)
    {
        Loader::loadModels($this, ['GatewayManager']);
        Loader::loadComponents($this, ['Gateways']);

        if ($this->verifyAch($vars)) {
            // Attempt to store off-site if supported
            if (!isset($this->GatewayPayments)) {
                Loader::loadComponents($this, ['GatewayPayments']);
            }

            $response = $this->GatewayPayments->storeAccount('ach', $vars);

            if (($errors = $this->GatewayPayments->errors())) {
                $this->Input->setErrors($errors);
                return;
            }

            if (isset($vars['account'])) {
                $vars['last4'] = substr($vars['account'], -4);
            }

            // Get field values from the gateway response
            if ($response !== false) {
                $changable_fields = [
                    'last4', 'type', 'gateway_id',
                    'client_reference_id', 'reference_id'
                ];

                foreach ($changable_fields as $changable_field) {
                    if (isset($response[$changable_field])) {
                        $vars[$changable_field] = $response[$changable_field];
                    }
                }

                // Don't store the account or routing numbers, they're stored off-site
                unset($vars['account']);
                unset($vars['routing']);
            }

            // Revalidate ach after modifications from the gateway
            $this->Input->setRules($this->getAddAchRules($vars));
            if (!$this->Input->validates($vars)) {
                return;
            }

            // Encrypt fields with AES
            $vars['last4'] = isset($vars['last4']) ? $this->systemEncrypt($vars['last4']) : null;

            if ($response === false) {
                // We're not working off-site so encrypt the account and routing numbers for local storage
                $vars['account'] = isset($vars['account']) ? $this->accountEncrypt($vars['account']) : null;
                $vars['routing'] = isset($vars['routing']) ? $this->accountEncrypt($vars['routing']) : null;
            }

            // Check if the account must be verified
            if (isset($vars['gateway_id'])) {
                $gateway = $this->GatewayManager->get($vars['gateway_id']);
                if ($gateway) {
                    $gateway_obj = $this->Gateways->create($gateway->class, $gateway->type);

                    $vars['status'] = 'active';
                    if ($gateway_obj instanceof MerchantAchVerification) {
                        $vars['status'] = 'unverified';
                    }
                }
            }

            // Add ACH account
            $fields = ['contact_id', 'first_name', 'last_name', 'address1',
                'address2', 'city', 'state', 'zip', 'country', 'account', 'routing',
                'last4', 'type', 'gateway_id', 'client_reference_id', 'reference_id', 'status'
            ];
            $this->Record->insert('accounts_ach', $vars, $fields);
            $account_id = $this->Record->lastInsertId();
            $this->addDebitAccount($account_id, 'ach');

            return $account_id;
        }
    }

    /**
     * Verifies ACH account details provided to ensure proper entry into the system
     *
     * @param array $vars An array of ACH account info including:
     *
     *  - contact_id The contact ID tied to this account
     *  - first_name The first name on the account
     *  - last_name The last name on the account
     *  - address1 The address line 1 on the account (optional)
     *  - address2 The address line 2 on the account (optional)
     *  - city The city on the account (optional)
     *  - state The ISO 3166-2 subdivision code on the account (optional)
     *  - zip The zip code on the account (optional)
     *  - country The ISO 3166-1 2-character country code (optional, defaults to 'US')
     *  - account The account number (will be encrypted) (optional)
     *  - routing The routing number (will be encrypted) (optional)
     *  - last4 The last 4 digits of the account number (will be encrypted) (optional if account is given)
     *  - type The type of account, 'checking' or 'savings', (optional, defaults to 'checking')
     *  - reference_id The reference ID attached to this account given by the payment processor (optional)
     *  - client_reference_id The reference ID for the client this payment account belongs to (optional)
     * @return int The ACH account ID for the record just added, void if not added
     */
    public function verifyAch(array &$vars)
    {
        $rules = array_merge($this->getRules($vars, 'ach'), $this->getAddAchRules($vars));
        $this->Input->setRules($rules);

        return $this->Input->validates($vars);
    }

    /**
     * Updates an ACH account in the system, all fields optional
     *
     * @param int $account_id The account ID for this account
     * @param array $vars An array of ACH account info including:
     *
     *  - first_name The first name on the account
     *  - last_name The last name on the account
     *  - address1 The address line 1 on the account (optional)
     *  - address2 The address line 2 on the account (optional)
     *  - city The city on the account (optional)
     *  - state The ISO 3166-2 subdivision code on the account (optional)
     *  - zip The zip code on the account (optional)
     *  - country The ISO 3166-1 2-character country code (optional, defaults to 'US')
     *  - account The account number (will be encrypted) (optional)
     *  - routing The routing number (will be encrypted) (optional)
     *  - last4 The last 4 digits of the account number (will be encrypted) (optional if account is given)
     *  - type The type of account, 'checking' or 'savings', (optional, defaults to 'checking')
     *  - reference_id The reference ID attached to this account given by the payment processor (optional)
     *  - client_reference_id The reference ID for the client this payment account belongs to (optional)
     *  - currency The currency in which is denominated the account (optional)
     */
    public function editAch($account_id, array $vars)
    {
        Loader::loadModels($this, ['GatewayManager']);
        Loader::loadComponents($this, ['Gateways']);

        $account = $this->getAch($account_id);

        // Set the state to null if it's not given and the country has changed
        if (isset($vars['country']) && !isset($vars['state']) && $account && $account->country != $vars['country']) {
            $vars['state'] = null;
        }

        // Set the ach account ID
        $vars['ach_account_id'] = $account_id;

        $rules = $this->getRules($vars, 'ach', true);

        // Detect if account or routing number changed. If so, add to rules so validation can be done on those fields
        $vars['account_changed'] = false;
        if ((isset($vars['account']) && substr($vars['account'], 0, 1) != '*') ||
            (isset($vars['routing']) && substr($vars['routing'], 0, 1) != '*')) {
            $vars['account_changed'] = true; // account numbers updated
            $rules = array_merge($rules, $this->getAddAchRules($vars));
        } else {
            // Remove the account and routing number. It's not being updated
            unset($vars['account']);
            unset($vars['routing']);
        }

        $this->Input->setRules($rules);
        if ($this->Input->validates($vars)) {
            // Update ACH account
            $fields = ['first_name', 'last_name', 'address1',
                'address2', 'city', 'state', 'zip', 'country', 'type', 'status'
            ];

            // Attempt to store off-site if supported
            if (!isset($this->GatewayPayments)) {
                Loader::loadComponents($this, ['GatewayPayments']);
            }

            $response = $this->GatewayPayments->updateAccount('ach', $this->getAch($account_id), $vars);

            if (($errors = $this->GatewayPayments->errors())) {
                $this->Input->setErrors($errors);
                return;
            }

            $vars['last4'] = isset($vars['account']) ? substr($vars['account'], -4) : null;

            // Get field values from the gateway response
            if ($response !== false) {
                $changable_fields = ['last4', 'type', 'gateway_id', 'client_reference_id', 'reference_id'];

                foreach ($changable_fields as $field) {
                    if (isset($response[$field])) {
                        $vars[$field] = $response[$field];
                        $vars['account_changed'] = true;
                    }
                }

                // Don't store the account and routing number, it's stored off-site
                $vars['account'] = null;
                $vars['routing'] = null;
            }

            // Revalidate ach after modifications from the gateway
            $this->Input->setRules($this->getAddAchRules($vars));
            if (!$this->Input->validates($vars)) {
                return;
            }

            // Only update the account details in the system if they've changed
            if ($vars['account_changed']) {
                // Encrypt fields with AES
                $vars['last4'] = $this->systemEncrypt($vars['last4']);

                if ($response !== false) {
                    $vars['gateway_id'] = $response['gateway_id'];
                    $vars['client_reference_id'] = $response['client_reference_id'];
                    $vars['reference_id'] = $response['reference_id'];

                    // Don't store the account or routing numbers, they're stored off-site
                    unset($vars['account']);
                    unset($vars['routing']);
                } else {
                    // We're not working off-site so encrypt the account and routing numbers for local storage
                    $vars['account'] = isset($vars['account']) ? $this->accountEncrypt($vars['account']) : null;
                    $vars['routing'] = isset($vars['routing']) ? $this->accountEncrypt($vars['routing']) : null;
                }

                $fields = array_merge($fields, ['account', 'routing', 'last4', 'client_reference_id', 'reference_id']);
            }

            // Check if the account must be verified
            if (isset($vars['gateway_id'])) {
                $gateway = $this->GatewayManager->get($vars['gateway_id']);
                if ($gateway) {
                    $gateway_obj = $this->Gateways->create($gateway->class, $gateway->type);

                    $vars['status'] = 'active';
                    if ($gateway_obj instanceof MerchantAchVerification) {
                        $vars['status'] = 'unverified';
                    }
                }
            }

            $this->Record->where('id', '=', $account_id)->update('accounts_ach', $vars, $fields);
        }
    }

    /**
     * Removes an ACH account record using the given account ID. Attempts to remove
     * the payment account from the remote gateway (if stored off-site). Deletes sensitive
     * information and marks the record as inactive.
     *
     * @param int $account_id The account ID for this ACH account
     * @param bool $soft_delete True to perform a soft delete, or false to permanently
     *  delete the record (optional, default true)
     */
    public function deleteAch($account_id, $soft_delete = true)
    {
        // Attempt to delete the payment account off-site if supported
        if (!isset($this->GatewayPayments)) {
            Loader::loadComponents($this, ['GatewayPayments']);
        }

        #
        # TODO: an Accounts.deleteAch event should exist, and the payment account info should be deleted
        # through an event listener
        #
        $response = $this->GatewayPayments->removeAccount('ach', $this->getAch($account_id));

        // If gateway doesn't support off-site payment accounts or failed for some reason, disable the
        // payment account locally anyway (no ill side-effect for doing so).
        if ($soft_delete) {
            $this->Record->where('id', '=', $account_id)->
                update('accounts_ach', ['account' => null, 'routing' => null, 'status' => 'inactive']);
        } else {
            // Permanently delete the ACH Account
            $this->Record->from('accounts_ach')->where('id', '=', $account_id)->delete();
        }

        #
        # TODO: an Accounts.deleteAch event should exist, and the client_account info should be deleted
        # through an event listener
        #
        // Delete from assigned client account if set
        $this->Record->from('client_account')->where('account_id', '=', $account_id)->
            where('type', '=', 'ach')->delete();
    }

    /**
     * Verifies an ACH account with the gateway
     *
     * @param int $account_id The account ID for this account
     * @param array $vars An array including:
     *
     *  - first_deposit The first deposit amount
     *  - second_deposit The second deposit amount
     */
    public function verifyAchDeposits($account_id, array $vars)
    {
        // Get ACH account
        $account = $this->getAch($account_id);

        // Attempt to verify the account off-site if supported
        if (!isset($this->GatewayPayments)) {
            Loader::loadComponents($this, ['GatewayPayments']);
        }

        $response = $this->GatewayPayments->verifyAccount('ach', $account, $vars);
        if (($errors = $this->GatewayPayments->errors())) {
            $this->Input->setErrors($errors);
            return;
        }

        $this->Record->where('id', '=', $account_id)->
            update('accounts_ach', ['status' => 'active']);
    }

    /**
     * Records a CC account into the system
     *
     * @param array $vars An array of CC account info including:
     *
     *  - contact_id The contact ID tied to this account
     *  - first_name The first name on the account
     *  - last_name The last name on the account
     *  - address1 The address line 1 on the account (optional)
     *  - address2 The address line 2 on the account (optional)
     *  - city The city on the account (optional)
     *  - state The ISO 3166-2 subdivision code on the account (optional)
     *  - zip The zip code on the account (optional)
     *  - country The ISO 3166-1 2-character country code (optional; required if state is given; defaults to 'US')
     *  - number The credit card number (will be encrypted) (optional)
     *  - expiration The expiration date in yyyymm format (will be encrypted)
     *  - security_code The 3 or 4-digit security code (optional, only used when storing payment account info off-site)
     *  - last4 The last 4 digits of the card number (will be encrypted) (optional if number is given)
     *  - type The card type (optional, will be determined automatically if not given)
     *  - reference_id The reference ID attached to this account given by the payment processor (optional)
     *  - client_reference_id The reference ID for the client this payment account belongs to (optional)
     *  - currency The currency in which is denominated the account (optional)
     * @return int The CC account ID for the record just added, void if not added
     */
    public function addCc(array $vars)
    {
        if ($this->verifyCc($vars, false)) {
            // Attempt to store off-site if supported
            if (!isset($this->GatewayPayments)) {
                Loader::loadComponents($this, ['GatewayPayments']);
            }

            $vars['card_number'] = $vars['number'] ?? null;
            $response = $this->GatewayPayments->storeAccount('cc', $vars);

            if (($errors = $this->GatewayPayments->errors())) {
                $this->Input->setErrors($errors);
                return;
            }

            if (isset($vars['number'])) {
                $vars['last4'] = substr($vars['number'], -4);
            }

            // Get field values from the gateway response
            if ($response !== false) {
                $changable_fields = [
                    'expiration', 'last4', 'type', 'gateway_id',
                    'client_reference_id', 'reference_id'
                ];

                foreach ($changable_fields as $changable_field) {
                    if (isset($response[$changable_field])) {
                        $vars[$changable_field] = $response[$changable_field];
                    }
                }

                // Don't store the card number, it's stored off-site
                unset($vars['number']);
            }

            // Revalidate cc after modifications from the gateway
            $this->Input->setRules($this->getAddCcRules($vars));
            if (!$this->Input->validates($vars)) {
                return;
            }

            // Encrypt fields with AES
            $vars['expiration'] = $this->systemEncrypt($vars['expiration']);
            $vars['last4'] = $this->systemEncrypt($vars['last4']);

            if ($response === false) {
                // If no gateway ID set, we're not working off-site so encrypt the card number
                $vars['number'] = isset($vars['number']) ? $this->accountEncrypt($vars['number']) : null;
            }

            // Add CC account
            $fields = [
                'contact_id', 'first_name', 'last_name', 'address1', 'address2',
                'city', 'state', 'zip', 'country', 'number', 'expiration',
                'last4', 'type', 'gateway_id', 'client_reference_id',
                'reference_id'
            ];
            $this->Record->insert('accounts_cc', $vars, $fields);
            $account_id = $this->Record->lastInsertId();
            $this->addDebitAccount($account_id, 'cc');

            return $account_id;
        }
    }

    /**
     * Verifies CC account info to ensure proper entry into the system
     *
     * @param array $vars An array of CC account info including:
     *
     *  - contact_id The contact ID tied to this account
     *  - first_name The first name on the account
     *  - last_name The last name on the account
     *  - address1 The address line 1 on the account (optional)
     *  - address2 The address line 2 on the account (optional)
     *  - city The city on the account (optional)
     *  - state The ISO 3166-2 subdivision code on the account (optional)
     *  - zip The zip code on the account (optional)
     *  - country The ISO 3166-1 2-character country code (optional; required if state is given; defaults to 'US')
     *  - number The credit card number (will be encrypted) (optional)
     *  - expiration The expiration date in yyyymm format (will be encrypted)
     *  - security_code The 3 or 4-digit security code (optional, only used when storing payment account info off-site)
     *  - last4 The last 4 digits of the card number (will be encrypted) (optional if number is given)
     *  - type The card type (optional, will be determined automatically if not given)
     *  - reference_id The reference ID attached to this account given by the payment processor (optional)
     *  - client_reference_id The reference ID for the client this payment account belongs to (optional)
     * @param bool $require_fields Whether to require that the cc fields be set to pass validation
     * @return int The CC account ID for the record just added, void if not added
     */
    public function verifyCc(array &$vars, $require_fields = true)
    {
        // Set input rules
        $rules = array_merge($this->getRules($vars), $this->getAddCcRules($vars, $require_fields));

        // Set the type of card if not set
        if (!isset($vars['type'])) {
            // Set the type to the card number, we'll transform that into the card type
            // when validated
            $vars['type'] = isset($vars['number']) ? $vars['number'] : null;
            $rules['type']['cc_format']['pre_format'] = [([$this, 'creditCardType'])];
        }
        $this->Input->setRules($rules);

        return $this->Input->validates($vars);
    }

    /**
     * Updates a CC account in the system, all fields optional
     *
     * @param int $account_id The account ID for this account
     * @param array $vars An array of CC account info including:
     *
     *  - first_name The first name on the account
     *  - last_name The last name on the account
     *  - address1 The address line 1 on the account (optional)
     *  - address2 The address line 2 on the account (optional)
     *  - city The city on the account (optional)
     *  - state The ISO 3166-2 subdivision code on the account (optional)
     *  - zip The zip code on the account (optional)
     *  - country The ISO 3166-1 2-character country code (optional; required if state is given; defaults to 'US')
     *  - number The credit card number (will be encrypted) (optional)
     *  - expiration The expiration date in yyyymm format (will be encrypted) (optional)
     *  - last4 The last 4 digits of the card number (will be encrypted) (optional if number is given)
     *  - type The card type (optional, will be determined automatically if not given)
     *  - reference_id The reference ID attached to this account given by the payment processor (optional)
     *  - client_reference_id The reference ID for the client this payment account belongs to (optional)
     *  - currency The currency in which is denominated the account (optional)
     */
    public function editCc($account_id, array $vars)
    {
        $account = $this->getCc($account_id);

        // Set the state to null if it's not given and the country has changed
        if (isset($vars['country']) && !isset($vars['state']) && $account && $account->country != $vars['country']) {
            $vars['state'] = null;
        }

        // Set the cc account ID
        $vars['cc_account_id'] = $account_id;

        // Set rules
        $rules = $this->getRules($vars, 'cc', true);
        $cc_rules = $this->getAddCcRules($vars, false);

        // Detect if card number changed. If so, add to rules so validation can be done on that field
        $vars['account_changed'] = false;
        $card_changed = false;
        if (isset($vars['number']) && substr($vars['number'], 0, 1) != '*') {
            $rules = array_merge($rules, $cc_rules);
            $vars['account_changed'] = true;
            $card_changed = true;

            // Set the type of card if not set
            if (!isset($vars['type'])) {
                // Set the type to the card number, we'll transform that into the card type
                // when validated
                $vars['type'] = isset($vars['number']) ? $vars['number'] : null;
                $rules['type']['cc_format']['pre_format'] = [([$this, 'creditCardType'])];
            }
        } else {
            // Remove the card number. It's not being updated
            unset($vars['number']);

            if (isset($vars['expiration']) && $account->expiration != $vars['expiration']) {
                $vars['account_changed'] = true;
            }
            // Expiration must be validated regardless of whether the card number has been updated
            $rules['expiration'] = $cc_rules['expiration'];
        }

        $this->Input->setRules($rules);
        if ($this->Input->validates($vars)) {
            // Update CC account
            $fields = [ 'first_name', 'last_name', 'address1', 'address2', 'city',
                'state', 'zip', 'country', 'expiration'
            ];

            // Attempt to store off-site if supported
            if (!isset($this->GatewayPayments)) {
                Loader::loadComponents($this, ['GatewayPayments']);
            }

            $vars['card_number'] = $vars['number'] ?? null;
            $response = $this->GatewayPayments->updateAccount('cc', $account, $vars);

            if (($errors = $this->GatewayPayments->errors())) {
                $this->Input->setErrors($errors);
                return;
            }

            $vars['last4'] = isset($vars['number']) ? substr($vars['number'], -4) : null;
            $vars['expiration'] = $vars['expiration'] ?? $account->expiration;

            // Get field values from the gateway response
            if ($response !== false) {
                $changable_fields = [
                    'expiration', 'last4', 'type', 'gateway_id',
                    'client_reference_id', 'reference_id'
                ];

                foreach ($changable_fields as $field) {
                    if (isset($response[$field])) {
                        $vars[$field] = $response[$field];
                        $vars['account_changed'] = true;
                        $card_changed = $card_changed || in_array($field, ['last4', 'type']);
                    }
                }

                // Don't store the card number, it's stored off-site
                $vars['number'] = null;
            }

            // Revalidate cc after modifications from the gateway
            $this->Input->setRules($this->getAddCcRules($vars, false));
            if (!$this->Input->validates($vars)) {
                return;
            }

            // Encrypt fields with AES
            $vars['expiration'] = $this->systemEncrypt($vars['expiration']);

            // Only update the account details in the system if they've changed
            if ($vars['account_changed']) {
                // Encrypt fields with AES
                $vars['last4'] = $this->systemEncrypt($vars['last4']);

                if ($response === false) {
                    // If no gateway ID set, we're not working off-site so encrypt the card number
                    $vars['number'] = isset($vars['number']) ? $this->accountEncrypt($vars['number']) : null;
                }

                $fields = array_merge($fields, ['number', 'last4', 'type', 'client_reference_id', 'reference_id']);
            }

            // Only update the card number if it has changed
            if (!$card_changed) {
                unset($vars['number'], $vars['last4'], $vars['type']);
            }

            $this->Record->where('id', '=', $account_id)->update('accounts_cc', $vars, $fields);
        }
    }

    /**
     * Removes a CC account record using the givent account ID. Attempts to remove
     * the payment account from the remote gateway (if stored off-site). Deletes sensitive
     * information and marks the record as inactive.
     *
     * @param int $account_id The account ID for this ACH account
     * @param bool $soft_delete True to perform a soft delete, or false to permanently
     *  delete the record (optional, default true)
     */
    public function deleteCc($account_id, $soft_delete = true)
    {
        // Attempt to delete the payment account off-site if supported
        if (!isset($this->GatewayPayments)) {
            Loader::loadComponents($this, ['GatewayPayments']);
        }

        #
        # TODO: an Accounts.deleteCc event should exist, and the payment account info should be deleted
        # through an event listener
        #
        $response = $this->GatewayPayments->removeAccount('cc', $this->getCc($account_id));

        // If gateway doesn't support off-site payment accounts or failed for some reason, disable the
        // payment account locally anyway (no ill side-effect for doing so).
        if ($soft_delete) {
            $this->Record->where('id', '=', $account_id)->
                update('accounts_cc', ['number' => null, 'expiration' => '', 'status' => 'inactive']);
        } else {
            // Permanently delete the CC Account
            $this->Record->from('accounts_cc')->where('id', '=', $account_id)->delete();
        }

        #
        # TODO: an Accounts.deleteCc event should exist, and the client_account info should be deleted
        # through an event listener
        #
        // Delete from assigned client account if set
        $this->Record->from('client_account')->where('account_id', '=', $account_id)->
            where('type', '=', 'cc')->delete();
    }

    /**
     * Returns the accounts for all active clients with credit card payment
     * accounts set to expire in the month given by $date
     *
     * @param string $date The date to fetch card expirations for, will be
     *  converted to Ym format (e.g. 201003 = March, 2010)
     * @return array An array of stdClass objects representing the accounts whose cards expire in the month given
     */
    public function getCardsExpireSoon($date)
    {
        $date_exp = $this->systemEncrypt($this->dateToUtc($date, 'Ym'));

        $accounts = $this->Record->select('accounts_cc.*')->from('clients')->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            innerJoin('contacts', 'contacts.client_id', '=', 'clients.id', false)->
            innerJoin('accounts_cc', 'accounts_cc.contact_id', '=', 'contacts.id', false)->
            where('clients.status', '=', 'active')->
            where('accounts_cc.status', '=', 'active')->
            where('client_groups.company_id', '=', Configure::get('Blesta.company_id'))->
            where('accounts_cc.number', '!=', null)->
            where('accounts_cc.expiration', '=', $date_exp)->
            fetchAll();

        foreach ($accounts as &$account) {
            $account->last4 = $this->systemDecrypt($account->last4);
        }
        return $accounts;
    }

    /**
     * Returns a list of account types
     *
     * @return array Key=>value pairs of account types
     */
    public function getTypes()
    {
        return [
            'cc' => $this->_('Accounts.getTypes.cc'),
            'ach' => $this->_('Accounts.getTypes.ach'),
            //'other'=>$this->_("Accounts.getTypes.other")
        ];
    }

    /**
     * Returns a list of credit card account types
     *
     * @return array Key=>value pairs of CC account types
     */
    public function getAchTypes()
    {
        return [
            'checking' => $this->_('Accounts.getAchTypes.checking'),
            'savings' => $this->_('Accounts.getAchTypes.savings')
        ];
    }

    /**
     * Returns a list of credit card account types
     *
     * @return array Key=>value pairs of CC account types
     */
    public function getCcTypes()
    {
        return [
            'amex' => $this->_('Accounts.getCcTypes.amex'),
            'bc' => $this->_('Accounts.getCcTypes.bc'),
            'cup' => $this->_('Accounts.getCcTypes.cup'),
            'dc-cb' => $this->_('Accounts.getCcTypes.dc-cb'),
            'dc-er' => $this->_('Accounts.getCcTypes.dc-er'),
            'dc-int' => $this->_('Accounts.getCcTypes.dc-int'),
            'dc-uc' => $this->_('Accounts.getCcTypes.dc-uc'),
            'disc' => $this->_('Accounts.getCcTypes.disc'),
            'ipi' => $this->_('Accounts.getCcTypes.ipi'),
            'jcb' => $this->_('Accounts.getCcTypes.jcb'),
            'lasr' => $this->_('Accounts.getCcTypes.lasr'),
            'maes' => $this->_('Accounts.getCcTypes.maes'),
            'mc' => $this->_('Accounts.getCcTypes.mc'),
            'solo' => $this->_('Accounts.getCcTypes.solo'),
            'switch' => $this->_('Accounts.getCcTypes.switch'),
            'visa' => $this->_('Accounts.getCcTypes.visa'),
            'other' => $this->_('Accounts.getCcTypes.other')
        ];
    }

    /**
     * Returns the partial rule set common between ACH/CC records for
     * adding/editing ACH/CC records
     *
     * @param array $vars The input vars
     * @param sting $type The type of account being validated ('cc' or 'ach') (optional)
     * @param bool $edit Whether the account is being edited (optional)
     * @return array Common ACH/CC rules
     * @see Accounts::getAddAchRules()
     * @see Accounts::getAddCcRules()
     */
    private function getRules($vars, $type = 'cc', $edit = false)
    {
        $rules = [
            'contact_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'contacts'],
                    'message' => $this->_('Accounts.!error.contact_id.exists')
                ]
            ],
            'first_name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Accounts.!error.first_name.empty'),
                    'post_format' => ['trim']
                ]
            ],
            'last_name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Accounts.!error.last_name.empty'),
                    'post_format' => ['trim']
                ]
            ],
            'state' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 3],
                    'message' => $this->_('Accounts.!error.state.length')
                ],
                'country_exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateStateCountry'], (isset($vars['country']) ? $vars['country'] : null)],
                    'message' => $this->_('Accounts.!error.state.country_exists')
                ]
            ],
            'country' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 3],
                    'message' => $this->_('Accounts.!error.country.length')
                ]
            ],
            'currency' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^(.*){3}$/'],
                    'message' => $this->_('Accounts.!error.currency.format')
                ]
            ]
        ];

        if ($edit) {
            unset($rules['contact_id']); // can't update contact

            $rules[$type . '_account_id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'accounts_' . $type],
                    'message' => $this->_('Accounts.!error.' . $type . '_account_id.exists')
                ]
            ];
        }

        return $rules;
    }

    /**
     * Returns the partial rule set for adding/editing ACH records
     *
     * @param array $vars The input vars
     * @return array ACH specific rules
     * @see Accounts::getRules()
     */
    private function getAddAchRules($vars)
    {
        $rules = [
            'account' => [
                'length' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'formatNumber']],
                    'rule' => ['minLength', 4],
                    #
                    # TODO: Update rule to ensure account number is valid
                    #
                    #
                    'message' => $this->_('Accounts.!error.account.length')
                ]
            ],
            'routing' => [
                'empty' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'formatNumber']],
                    'rule' => ['minLength', 4],
                    #
                    # TODO: Update rule to ensure account number is valid
                    #
                    #
                    'message' => $this->_('Accounts.!error.routing.empty')
                ]
            ],
            'type' => [
                'ach_format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateAchType']],
                    'message' => $this->_('Accounts.!error.type.ach_format')
                ]
            ]
        ];
        return $rules;
    }

    /**
     * Returns the partial rule set for adding/editing CC records
     *
     * @param array $vars The input vars
     * @param bool $require_fields Whether to require that the cc fields be set to pass validation
     * @return array CC specific rules
     * @see Accounts::getRules()
     */
    private function getAddCcRules($vars, $require_fields = true)
    {
        $rules = [
            'number' => [
                'valid' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'formatNumber']],
                    'rule' => [[$this, 'luhnValid']],
                    'message' => $this->_('Accounts.!error.number.valid')
                ],
            ],
            'expiration' => [
                'empty' => [
                    'if_set' => !$require_fields,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Accounts.!error.expiration.empty')
                ],
                'valid' => [
                    'if_set' => !$require_fields,
                    'rule' => ['compares', '>=', date('Ym')],
                    'message' => $this->_('Accounts.!error.expiration.valid')
                ]
            ],
            'type' => [
                'cc_format' => [
                    'if_set' => !$require_fields,
                    'rule' => [[$this, 'validateCcType']],
                    'message' => $this->_('Accounts.!error.type.cc_format')
                ]
            ]
        ];
        return $rules;
    }

    /**
     * Adds the given account as the client's debit account if none are currently set.
     *
     * @param int $account_id The ID of the payment account
     * @param string $type The type of payment account ('cc' or 'ach')
     */
    private function addDebitAccount($account_id, $type)
    {
        Loader::loadModels($this, ['Clients', 'Contacts']);

        // Fetch the account to determine the contact
        $account = null;
        switch ($type) {
            case 'ach':
                $account = $this->getAch($account_id);
                break;
            case 'cc':
            default:
                $account = $this->getCc($account_id);
                break;
        }

        // Fetch the contact to determine the client
        $client_id = null;
        if ($account && ($contact = $this->Contacts->get($account->contact_id))) {
            $client_id = $contact->client_id;
        }

        // Set this account as the debit account if one is not already set
        if ($client_id && !$this->Clients->getDebitAccount($client_id)) {
            $vars = ['account_id' => $account_id, 'type' => $type];
            $this->Clients->addDebitAccount($client_id, $vars);
        }
    }

    /**
     * Performs an asymetric encryption on the given data for the current company using RSA.
     *
     * @param string $data The data to encrypt with the asymetric public-key
     * @return string The encrypted text base64 encoded
     */
    private function accountEncrypt($data)
    {
        $this->loadCrypto(['RSA']);
        // Each company has its own public key
        $company_id = Configure::get('Blesta.company_id');

        // Cache public keys for each company so we don't tax the database unnecessarily
        static $public_keys = [];

        if (!isset($this->SettingsCollection)) {
            Loader::loadComponents($this, ['SettingsCollection']);
        }

        // Get the public key, which is used to encrypt
        if (!isset($public_keys[$company_id])) {
            $temp = $this->SettingsCollection->fetchSetting(null, $company_id, 'public_key');
            $public_keys[$company_id] = isset($temp['value']) ? $temp['value'] : null;
            unset($temp);
        }

        // Load the public key
        $this->Crypt_RSA->loadKey($public_keys[$company_id]);

        // Decrypt the data and return it
        return base64_encode($this->Crypt_RSA->encrypt($data));
    }

    /**
     * Performs an asymetric decryption on the given data for the current company using RSA.
     *
     * @param string $data The data to decrypt with the asymetric private-key, base64 encoded
     * @param string $passphrase The passphrase required to decrypt the private-key stored in the system (if set)
     * @return mixed The decrypted text (string) on success, (boolean) false on failure
     */
    private function accountDecrypt($data, $passphrase = null)
    {
        if ($data == '') {
            return $data;
        }

        $this->loadCrypto(['RSA']);
        // Each company has its own private key, moreover, each company's passphrase for their private key may be unique
        $company_id = Configure::get('Blesta.company_id');

        // Cache private keys for each company so we don't tax the database unnecessarily
        static $private_keys = [];

        if (!isset($this->SettingsCollection)) {
            Loader::loadComponents($this, ['SettingsCollection']);
        }

        // If passphrase is set, ensure it is correct before attempting to decrypt
        $hash_pass = $this->SettingsCollection->fetchSetting(null, $company_id, 'private_key_passphrase');
        if ($passphrase !== null && isset($hash_pass['value']) && $hash_pass['value'] != $this->systemHash($passphrase)
        ) {
            return false;
        }


        // Get the private key, unencrypt it, we need it to decrypt
        if (!isset($private_keys[$company_id])) {
            $temp = $this->SettingsCollection->fetchSetting(null, $company_id, 'private_key');
            $private_keys[$company_id] = $this->systemDecrypt(
                isset($temp['value']) ? $temp['value'] : null,
                $passphrase
            );
            unset($temp);
        }

        // Load the private key
        $this->Crypt_RSA->loadKey($private_keys[$company_id]);

        // Decrypt the data and return it
        return $this->Crypt_RSA->decrypt(base64_decode($data));
    }

    /**
     * Formats a given string into an integer string
     *
     * @param string $value The value to format
     * @return string The formatted $value with all non-integer characters removed
     */
    public function formatNumber($value)
    {
        return preg_replace('/[^0-9]*/', '', $value);
    }

    /**
     * Validates the ACH 'type' field
     *
     * @param string $type The ACH type
     * @return bool True if validated, false otherwise
     */
    public function validateAchType($type)
    {
        switch ($type) {
            case 'checking':
            case 'savings':
                return true;
        }
        return false;
    }

    /**
     * Validates the CC 'type' field
     *
     * @param string $type The CC type
     * @return bool True if validated, false otherwise
     */
    public function validateCcType($type)
    {
        switch ($type) {
            case 'amex':
            case 'bc':
            case 'cup':
            case 'dc-cb':
            case 'dc-er':
            case 'dc-int':
            case 'dc-uc':
            case 'disc':
            case 'ipi':
            case 'jcb':
            case 'lasr':
            case 'maes':
            case 'mc':
            case 'solo':
            case 'switch':
            case 'visa':
                return true;
        }
        return false;
    }

    /**
     * Returns the card type based on the given card number (card numbers are ISO 7812 numbers)
     *
     * @param string $card_number The card number to evaluate
     * @return string The card type detected, null if the card is invalid or is
     *  otherwise not recognized. Values include:
     *
     *  - 'amex' - American Express,
     *  - 'bc' - Bankcard,
     *  - 'cup' - China Union Pay,
     *  - 'dc-cb' - Diners Club Carte Blanche,
     *  - 'dc-er' - Diners Club EnRoute,
     *  - 'dc-int' - Diners Club International,
     *  - 'dc-uc' - Diners Club US and Canada,
     *  - 'disc' - Discover,
     *  - 'ipi' - InstaPayment,
     *  - 'jcb' - Japan Credit Bureau,
     *  - 'lasr' - Laser,
     *  - 'maes' - Maestro,
     *  - 'mc' - Master Card,
     *  - 'solo' - Solo,
     *  - 'switch' - Switch,
     *  - 'visa' - Visa
     */
    public function creditCardType($card_number)
    {
        $card_number = $this->formatNumber($card_number);

        $pattern = [
            'amex' => [
                'regex' => '/^(34|37)/', // 34, 37
                'lengths' => [15]
            ],
            // INACTIVE
            'bc' => [
                'regex' => '/^(5610|56022[1-5])/', // 5610, 560221-560225
                'lengths' => [16]
            ],
            'cup' => [
                'regex' => '/^(62)/', // 62
                'lengths' => [16]
            ],
            'dc-cb' => [
                'regex' => '/^(30[0-5])/', // 300-305
                'lengths' => [14]
            ],
            // INACTIVE
            'dc-er' => [
                'regex' => '/^(2014|2149)/', // 2014, 2149
                'lengths' => [15]
            ],
            'dc-int' => [
                'regex' => '/^(36|38)/', // 36, 38
                'lengths' => [14]
            ],
            /* Treated as Mastercard
              'dc-uc'=>array(
              'regex'=>"/^(54|55)/",
              'lengths'=>array(16)
              ),
             */
            'disc' => [
                // 6011, 622126-622925, 644-649, 65
                'regex' => '/^(6011|622(12[6-9]|1[3-9][0-9]|[2-9][0-1][0-9]|92[0-5])|64[4-9]|65)/',
                'lengths' => [16]
            ],
            'ipi' => [
                'regex' => '/^(63[7-9])/', // 637-639
                'lengths' => [16]
            ],
            'jcb' => [
                'regex' => '/^(3)/', // 3
                'lengths' => [16]
            ],
            'lasr' => [
                // 6304, 6706, 6771, 6709
                'regex' => '/^(6304|6706|6771|6709)/',
                'lengths' => [16, 17, 18, 19]
            ],
            'maes' => [
                // 5018, 5020, 5038, 6304, 6759, 6761, 6763
                'regex' => '/^(5018|5020|5038|6304|6759|6761|6763)/',
                'lengths' => [12, 13, 14, 15, 16, 17, 18, 19]
            ],
            'mc' => [
                'regex' => '/^(5[1-5])/', // 51-55
                'lengths' => [16]
            ],
            'solo' => [
                'regex' => '/^(6334|6767)/', // 6334, 6767
                'lengths' => [16, 18, 19]
            ],
            'switch' => [
                // 4903, 4905, 4911, 4936, 564182, 633110, 6333, 6759
                'regex' => '/^(4903|4905|4911|4936|564182|633110|6333|6759)/',
                'lengths' => [16, 18, 19]
            ],
            'visa' => [
                'regex' => '/^(4)/', // 4
                'lengths' => [13, 16]
            ]
        ];

        $length = strlen($card_number);

        foreach ($pattern as $type => $rule) {
            // Evaluate credit card numbers against issued IIN (Issuer Identification Number) ranges and lengths
            if (preg_match($rule['regex'], $card_number) && in_array($length, $rule['lengths'])) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Performs the Luhn Algorithm on the given card number to verify that the card is valid.
     *
     * @param string $card_number The card number to validate
     * @return bool Returns true if the card was successfully validated against
     *  the Luhn algorithm (e.g. is valid), false otherwise
     */
    public function luhnValid($card_number)
    {
        $sum_table = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [0, 2, 4, 6, 8, 1, 3, 5, 7, 9]
        ];
        $sum = 0;
        $flip = 0;

        $card_length = strlen($card_number);
        for ($i = $card_length - 1; $i >= 0; $i--) {
            $sum += $sum_table[$flip++ & 0x1][$card_number[$i]];
        }
        return $sum % 10 == 0;
    }
}
