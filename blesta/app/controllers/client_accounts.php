<?php

/**
 * Client portal accounts controller
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientAccounts extends ClientController
{
    /**
     * Main pre-action
     */
    public function preAction()
    {
        parent::preAction();

        $this->uses(['Accounts', 'Clients', 'Contacts']);
    }

    /**
     * List all client accounts, allow client to set primary account
     */
    public function index()
    {
        // Set the default account set for autodebiting to none
        $vars = (object) ['account_id' => 'none'];

        // Set an account for autodebiting
        if (!empty($this->post)) {
            // Delete the debit account if set to none, or given invalid value
            if ($this->post['account_id'] == 'none' || !is_numeric($this->post['account_id'])) {
                // Delete account, send message on success, ignore otherwise (there was nothing to delete)
                if ($this->Clients->deleteDebitAccount($this->client->id)) {
                    $this->setMessage('message', Language::_('ClientAccounts.!success.defaultaccount_deleted', true));
                }
            } else {
                // Add the debit account
                $this->Clients->addDebitAccount($this->client->id, $this->post);

                if (($errors = $this->Clients->errors())) {
                    // Error, reset vars
                    $vars = (object) $this->post;
                    $this->setMessage('error', $errors);
                } else {
                    // Success, debit account added/updated
                    $this->setMessage('message', Language::_('ClientAccounts.!success.defaultaccount_updated', true));
                }
            }
        }

        // Get all payment accounts
        $payment_accounts = $this->getAccounts();

        // Go straight to add a payment account if none exist
        if (empty($payment_accounts)
            && !($this->client->settings['payments_allowed_cc'] == 'false'
                && $this->client->settings['payments_allowed_ach'] == 'false')
        ) {
            $this->redirect($this->base_uri . 'accounts/add/');
        }

        // Get the current account set for autodebiting
        $client_account = $this->Clients->getDebitAccount($this->client->id);

        // Get active debit account, if any
        if (($debit_account = $this->getDebitAccount($payment_accounts, $client_account))) {
            $vars->account_id = $debit_account['account_id'];
            $vars->type = $debit_account['type'];
        }

        // Set an explanatory message
        $this->setMessage('info', Language::_('ClientAccounts.!info.account_info', true));
        // Display a message when autodebit is disabled
        if (('true' != $this->client->settings['autodebit'])) {
            $this->setMessage('notice', Language::_('ClientAccounts.!notice.reenable_autodebit', true));
        }

        $this->set('account_types', $this->Accounts->getTypes());
        $this->set('ach_types', $this->Accounts->getAchTypes());
        $this->set('cc_types', $this->Accounts->getCcTypes());
        $this->set('accounts', $payment_accounts);
        $this->set('client', $this->client);
        $this->set('vars', $vars);
        $this->set('navigation', $this->partial('client_accounts_navigation'));
    }

    /**
     * Create a new payment account
     */
    public function add()
    {
        $this->uses(['Countries', 'States', 'GatewayManager']);
        $this->components(['Gateways']);

        // Set valid account types
        $valid_account_types = ['cc', 'ach'];

        // Set default country
        $vars = new stdClass();
        $vars->country = (!empty($this->client->settings['country']) ? $this->client->settings['country'] : '');

        // Set notice if CC and ACH payment types are not enabled
        if (($this->client->settings['payments_allowed_cc'] == 'false') &&
            ($this->client->settings['payments_allowed_ach'] == 'false')) {
            $this->flashMessage('notice', Language::_('ClientAccounts.!notice.disabled', true));
            $this->redirect($this->base_uri . 'accounts/');
        } elseif ($this->client->settings['payments_allowed_cc'] == 'false') {
            // Set the only account type available
            $valid_account_types = ['ach'];
        } elseif ($this->client->settings['payments_allowed_ach'] == 'false') {
            $valid_account_types = ['cc'];
        }

        // Set the account type given, if any
        $account_type = (isset($this->get[0]) && in_array($this->get[0], $valid_account_types) ? $this->get[0] : null);
        $vars->account_type = $account_type;

        // Set current step
        $step = 1;
        if ($account_type) {
            $step = 2;
        }

        // Create a payment account
        if (!empty($this->post)) {
            // Step 1, select the payment account type available
            if (isset($this->post['payment_account_type'])) {
                // Continue to next step
                if (in_array($this->post['payment_account_type'], $valid_account_types)) {
                    $this->redirect($this->base_uri . 'accounts/add/' . $this->post['payment_account_type'] . '/');
                }

                // Error, invalid type
                $this->setMessage('error', [
                    'payment_account_type' => [
                        'invalid' => Language::_('ClientAccounts.!error.payment_account_type_invalid', true)
                    ]
                ]);
            } else {
                // Step 2, create the payment account
                // Fetch the contact we're about to set the payment account for
                $temp_contact_id = $this->post['contact_id'] ?? 0;
                $contact = $this->Contacts->get($temp_contact_id);

                // Set contact ID to create this account for (default to the client's contact ID)
                if (!$contact || ($contact->client_id != $this->client->id)) {
                    $this->post['contact_id'] = $this->client->contact_id;
                }

                // Double check the account type given is enabled. Refuse any failures
                if (!in_array(($this->post['account_type'] ?? ''), $valid_account_types)) {
                    $this->flashMessage('error', Language::_('ClientAccounts.!error.account_invalid', true));
                    $this->redirect($this->base_uri . 'accounts/');
                }

                // Create the account
                if ($this->post['account_type'] == 'cc') {
                    // Unset fields specific to ACH
                    unset($this->post['type'], $this->post['account'], $this->post['routing']);

                    if (isset($this->post['expiration_year']) || isset($this->post['expiration_month'])) {
                        // Concatenate the expiration date to the form 'yyyymm'
                        $this->post['expiration'] = ($this->post['expiration_year'] ?? '')
                            . ($this->post['expiration_month'] ?? '');
                    }

                    $account_id = $this->Accounts->addCc($this->post);
                } elseif ($this->post['account_type'] == 'ach') {
                    $account_id = $this->Accounts->addAch($this->post);
                }

                if (($errors = $this->Accounts->errors())) {
                    // Error, reset vars
                    $this->post['contact_id'] = ($temp_contact_id == 0 ? 'none' : $temp_contact_id);
                    $vars = (object) $this->post;
                    $this->setMessage('error', $errors);
                } else {
                    // Success, account created
                    $this->flashMessage('message', Language::_('ClientAccounts.!success.account_created', true));

                    // Check if the account must be verified
                    $gateway = $this->GatewayManager->getInstalledMerchant(
                        $this->company_id,
                        $this->client->settings['default_currency']
                    );
                    if ($gateway) {
                        $gateway_obj = $this->Gateways->create($gateway->class, $gateway->type);

                        if ($gateway_obj instanceof MerchantAchVerification && $this->post['account_type'] == 'ach') {
                            $this->redirect($this->base_uri . 'accounts/verifyach/' . $account_id . '/');
                        } else {
                            $this->redirect($this->base_uri . 'accounts/');
                        }
                    } else {
                        $this->redirect($this->base_uri . 'accounts/');
                    }
                }
            }
        } elseif ($step == 1 && count($valid_account_types) == 1) {
            // Only one type available, go straight to step 2
            $this->redirect($this->base_uri . 'accounts/add/' . $valid_account_types[0] . '/');
        }

        $this->set('step', $step);
        $this->set('vars', $vars);

        // Set the contact info partial to the view
        $this->setContactView($vars);

        // Check whether payment accounts exist, and display a message if not
        $payment_accounts = $this->getAccounts();
        if (empty($payment_accounts)) {
            $this->setMessage('info', Language::_('ClientAccounts.!info.no_accounts', true));
        }

        // Set the ACH/CC info partial to the view
        $this->set('account_info', ($account_type == 'ach' ? $this->getAchView($vars) : $this->getCcView($vars)));
        $this->set('navigation', $this->partial('client_accounts_navigation'));
    }

    /**
     * Edit a credit card payment account
     */
    public function editCc()
    {
        $this->uses(['Countries', 'States']);

        // Ensure a valid CC account ID has been given and belongs to this client
        if (!isset($this->get[0])
            || !($payment_account = $this->Accounts->getCc((int) $this->get[0]))
            || ($payment_account->client_id != $this->client->id)
            || ($payment_account->status != 'active')
        ) {
            $this->redirect($this->base_uri . 'accounts/');
        }

        // Set notice if the CC payment type setting is not enabled
        if ($this->client->settings['payments_allowed_cc'] == 'false') {
            $this->flashMessage('notice', Language::_('ClientAccounts.!notice.cc_disabled', true));
            $this->redirect($this->base_uri . 'accounts/');
        }

        $vars = [];

        // Edit the CC account
        if (!empty($this->post)) {
            if (isset($this->post['expiration_year']) || isset($this->post['expiration_month'])) {
                // Concatenate the expiration date to the form 'yyyymm'
                $this->post['expiration'] = (
                        isset($this->post['expiration_year']) ? $this->post['expiration_year'] : ''
                    ) . (isset($this->post['expiration_month']) ? $this->post['expiration_month'] : '');
            }

            // Update the account
            $this->Accounts->editCc($payment_account->id, $this->post);

            if (($errors = $this->Accounts->errors())) {
                // Error, reset vars
                $vars = (object) $this->post;
                $vars->gateway_id = $payment_account->gateway_id;
                $this->setMessage('error', $errors);
            } else {
                // Success, account updated
                $this->flashMessage('message', Language::_('ClientAccounts.!success.ccaccount_updated', true));
                $this->redirect($this->base_uri . 'accounts/');
            }
        }

        // Set current account
        if (empty($vars)) {
            $vars = $payment_account;

            // Parse out the expiration date for the CC# (yyyymm)
            $vars->expiration_month = substr($vars->expiration, -2);
            $vars->expiration_year = substr($vars->expiration, 0, 4);
        }

        // Set the contact info partial to the view
        $this->setContactView($vars, true);
        // Set the CC info partial to the view
        $this->set('cc_info', $this->getCcView($vars, true));
        $this->set('navigation', $this->partial('client_accounts_navigation'));
    }

    /**
     * Add an ACH payment account
     */
    public function editAch()
    {
        // Ensure a valid client has been given
        if (!isset($this->get[0])
            || !($payment_account = $this->Accounts->getAch((int) $this->get[0]))
            || ($payment_account->client_id != $this->client->id)
            || ($payment_account->status != 'active')
        ) {
            if (($payment_account->status ?? '') == 'unverified') {
                $this->redirect($this->base_uri . 'accounts/verifyach/' . ((int) $this->get[0]) . '/');
            } else {
                $this->redirect($this->base_uri . 'accounts/');
            }
        }

        $this->uses(['Countries', 'States', 'GatewayManager']);
        $this->components(['Gateways']);

        // Show notice if the ACH payment type setting is not enabled
        if ($this->client->settings['payments_allowed_ach'] == 'false') {
            $this->flashMessage('notice', Language::_('ClientAccounts.!notice.ach_disabled', true));
            $this->redirect($this->base_uri . 'accounts/');
        }

        $vars = [];

        // Edit the ACH account
        if (!empty($this->post)) {
            // Update the account
            $this->Accounts->editAch($payment_account->id, $this->post);

            if (($errors = $this->Accounts->errors())) {
                // Error, reset vars
                $vars = (object) $this->post;
                $vars->gateway_id = $payment_account->gateway_id;
                $this->setMessage('error', $errors);
            } else {
                // Success, account updated
                $this->flashMessage('message', Language::_('ClientAccounts.!success.achaccount_updated', true));

                // Check if the account must be verified
                $gateway = $this->GatewayManager->getInstalledMerchant(
                    $this->company_id,
                    $this->client->settings['default_currency']
                );
                if ($gateway) {
                    $gateway_obj = $this->Gateways->create($gateway->class, $gateway->type);

                    if ($gateway_obj instanceof MerchantAchVerification) {
                        $this->redirect($this->base_uri . 'accounts/verifyach/' . $payment_account->id . '/');
                    } else {
                        $this->redirect($this->base_uri . 'accounts/');
                    }
                } else {
                    $this->redirect($this->base_uri . 'accounts/');
                }
            }
        }

        // Set current account
        if (empty($vars)) {
            $vars = $payment_account;
        }

        // Set the contact info partial to the view
        $this->setContactView($vars, true);
        // Set the ACH info partial to the view
        $this->set('ach_info', $this->getAchView($vars, true));
        $this->set('navigation', $this->partial('client_accounts_navigation'));
    }

    /**
     * Verifies an ACH payment account
     */
    public function verifyAch()
    {
        $this->uses(['Payments']);

        // Ensure a valid account has been given
        if (!isset($this->get[0])
            || !($payment_account = $this->Accounts->getAch((int) $this->get[0]))
            || ($payment_account->client_id != $this->client->id)
            || ($payment_account->status != 'unverified')
        ) {
            $this->redirect($this->base_uri . 'accounts/');
        }

        // Show notice if the ACH payment type setting is not enabled
        if ($this->client->settings['payments_allowed_ach'] == 'false') {
            $this->flashMessage('notice', Language::_('ClientAccounts.!notice.ach_disabled', true));
            $this->redirect($this->base_uri . 'accounts/');
        }

        // Show verification message
        $this->setMessage('info', Language::_('ClientAccounts.!info.ach_verification', true));

        $vars = [];

        // Verify the ACH account
        if (!empty($this->post)) {
            // Update the account
            $this->Accounts->verifyAchDeposits($payment_account->id, $this->post);

            if (($errors = $this->Accounts->errors())) {
                // Error, reset vars
                $vars = (object) $this->post;
                $vars->gateway_id = $payment_account->gateway_id;
                $this->setMessage('error', $errors);
            } else {
                // Success, account updated
                $this->flashMessage('message', Language::_('ClientAccounts.!success.achaccount_verified', true));
                $this->redirect($this->base_uri . 'accounts/');
            }
        }

        // Fetch the ach verification form
        $verification_form = $this->Payments->getBuildAchVerificationForm(
            $this->client->settings['default_currency'],
            (array) $vars
        );

        $this->set('vars', $vars);
        $this->set('verification_form', $verification_form);
        $this->set('navigation', $this->partial('client_accounts_navigation'));
    }

    /**
     * Delete a credit card payment account
     */
    public function deleteCc()
    {
        // Ensure a valid account has been given
        if (!isset($this->post['id'])
            || !($payment_account = $this->Accounts->getCc((int) $this->post['id']))
            || ($payment_account->client_id != $this->client->id)
            || ($payment_account->status != 'active')
        ) {
            $this->redirect($this->base_uri . 'accounts/');
        }

        $this->Accounts->deleteCc($payment_account->id);

        // Success, account deleted
        $this->flashMessage('message', Language::_('ClientAccounts.!success.ccaccount_deleted', true));

        // Determine where to redirect based on whether accounts exist or can be added
        $payment_accounts = $this->getAccounts();
        $add_cc = ($this->client->settings['payments_allowed_cc'] != 'false');
        $add_ach = ($this->client->settings['payments_allowed_ach'] != 'false');
        if (empty($payment_accounts) && ($add_cc || $add_ach)) {
            $both = true;
            $type = '';
            if ($add_cc && !$add_ach) {
                $type = 'cc';
                $both = false;
            } elseif (!$add_cc && $add_ach) {
                $type = 'ach';
                $both = false;
            }
            $this->redirect($this->base_uri . 'accounts/add/' . ($both ? '' : $type));
        }
        $this->redirect($this->base_uri . 'accounts/');
    }

    /**
     * Delete an ACH payment account
     */
    public function deleteAch()
    {
        // Ensure a valid account has been given
        if (!isset($this->post['id'])
            || !($payment_account = $this->Accounts->getAch((int) $this->post['id']))
            || ($payment_account->client_id != $this->client->id)
            || ($payment_account->status != 'active' && $payment_account->status != 'unverified')
        ) {
            $this->redirect($this->base_uri . 'accounts/');
        }

        $this->Accounts->deleteAch($payment_account->id);

        // Success, account deleted
        $this->flashMessage('message', Language::_('ClientAccounts.!success.achaccount_deleted', true));

        // Determine where to redirect based on whether accounts exist or can be added
        $payment_accounts = $this->getAccounts();
        $add_cc = ($this->client->settings['payments_allowed_cc'] != 'false');
        $add_ach = ($this->client->settings['payments_allowed_ach'] != 'false');
        if (empty($payment_accounts) && ($add_cc || $add_ach)) {
            $both = true;
            $type = '';
            if ($add_cc && !$add_ach) {
                $type = 'cc';
                $both = false;
            } elseif (!$add_cc && $add_ach) {
                $type = 'ach';
                $both = false;
            }
            $this->redirect($this->base_uri . 'accounts/add/' . ($both ? '' : $type));
        }
        $this->redirect($this->base_uri . 'accounts/');
    }

    /**
     * Sets the contact partial view
     * @see ClientAccounts::addCc(), ClientAccounts::addAch(), ClientAccounts::editCc(), ClientAccounts::editAch()
     *
     * @param stdClass $vars The input vars object for use in the view
     * @param bool $edit True if this is an edit, false otherwise
     */
    private function setContactView(stdClass $vars, $edit = false)
    {
        $this->uses(['Countries', 'States', 'ClientGroups']);

        $contacts = [];

        if (!$edit) {
            // Set an option for no contact
            $no_contact = [
                (object) [
                    'id' => 'none',
                    'first_name' => Language::_('ClientAccounts.setcontactview.text_none', true),
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
            'edit' => $edit
        ];

        // Load language for partial
        Language::loadLang('client_contacts');
        $this->set('contact_info', $this->partial('client_accounts_contact_info', $contact_info));
    }

    /**
     * Retrieves the ACH partial view
     * @see ClientAccounts::addAch(), ClientAccounts::editAch()
     *
     * @param stdClass $vars The input vars object for use in the view
     * @param bool $edit True if this is an edit, false otherwise
     * @param bool $save_account True to offer an option to save these payment details, false otherwise
     * @return string The ACH account info partial
     */
    private function getAchView(stdClass $vars, $edit = false, $save_account = false)
    {
        $this->uses(['Payments', 'GatewayManager']);
        $this->components(['Gateways']);

        // Fetch the ach form to be used with this company and currency
        $gateway_form = $this->Payments->getBuildAchForm($this->client->settings['default_currency'], (array)$vars);

        // Check if the account must be verified
        $gateway = $this->GatewayManager->getInstalledMerchant(
            $this->company_id,
            $this->client->settings['default_currency']
        );
        if ($gateway) {
            $gateway_obj = $this->Gateways->create($gateway->class, $gateway->type);

            if ($gateway_obj instanceof MerchantAchVerification) {
                $this->setMessage(
                    'info',
                    Language::_('ClientAccounts.!info.ach_verification' . ($edit ? '_edit' : ''), true)
                );
            }
        }

        // Set partial for ACH info
        $ach_info = [
            'types' => $this->Accounts->getAchTypes(),
            'vars' => $vars,
            'edit' => $edit,
            'client' => $this->client,
            'gateway_form' => $gateway_form,
            'save_account' => $save_account
        ];

        return $this->partial('client_accounts_ach_info', $ach_info);
    }

    /**
     * Retrieves the CC partial view
     * @see ClientAccounts::addCc(), ClientAccounts::editCc()
     *
     * @param stdClass $vars The input vars object for use in the view
     * @param bool $edit True if this is an edit, false otherwise
     * @param bool $save_account True to offer an option to save these payment details, false otherwise
     * @return string The CC account info partial
     */
    private function getCcView(stdClass $vars, $edit = false, $save_account = false)
    {
        $this->uses(['Payments']);

        // Fetch the cc form to be used with this company and currency
        $gateway_form = $this->Payments->getBuildCcForm($this->client->settings['default_currency']);

        // Set available credit card expiration dates
        $years = $this->Date->getYears(date('Y'), date('Y') + 10, 'Y', 'Y');

        // Set the card year in case of an old, expired, card
        if (!empty($vars->expiration_year)
            && !array_key_exists($vars->expiration_year, $years)
            && preg_match('/^[0-9]{4}$/', $vars->expiration_year)
        ) {
            $card_year = [$vars->expiration_year => $vars->expiration_year];

            if ((int) $vars->expiration_year < reset($years)) {
                $years = $card_year + $years;
            } elseif ((int) $vars->expiration_year > end($years)) {
                $years += $card_year;
            }
        }

        $expiration = [
            // Get months with full name (e.g. "January")
            'months' => $this->Date->getMonths(1, 12, 'm', 'F'),
            // Sets years from the current year to 10 years in the future
            'years' => $years
        ];

        // Set partial for CC info
        $cc_info = [
            'expiration' => $expiration,
            'vars' => $vars,
            'edit' => $edit,
            'client' => $this->client,
            'gateway_form' => $gateway_form,
            'save_account' => $save_account
        ];

        return $this->partial('client_accounts_cc_info', $cc_info);
    }

    /**
     * Retrieves a list of all client payment accounts
     *
     * @return array A list of CC and ACH payment accounts belonging to this client
     */
    private function getAccounts()
    {
        // Set the primary contact accounts
        $primary_contact = $this->Contacts->getAll($this->client->id, 'primary');
        $accounts = [];

        if (!empty($primary_contact[0])) {
            $cc_accounts = $this->Accounts->getAllCc($primary_contact[0]->id);
            $ach_accounts = $this->Accounts->getAllAch($primary_contact[0]->id, true);

            $accounts = array_merge($cc_accounts, $ach_accounts);
        }

        // Set billing contact accounts
        $billing_contacts = $this->Contacts->getAll($this->client->id, 'billing');
        for ($i = 0, $num_billing_contacts = count($billing_contacts); $i < $num_billing_contacts; $i++) {
            $cc_accounts = $this->Accounts->getAllCc($billing_contacts[$i]->id);
            $ach_accounts = $this->Accounts->getAllAch($billing_contacts[$i]->id, true);

            $accounts = array_merge($accounts, $cc_accounts, $ach_accounts);
        }

        return $accounts;
    }

    /**
     * Retrieves the active debit account selected for this client
     * @see ClientAccounts::index()
     *
     * @param array A list of ACH and CC payment account objects
     * @param stdClass $client_account An stdClass object representing the current
     *  active debit account (optional, default false)
     * @return mixed False if no debit account is set, otherwise an array of debit account settings including:
     *
     *  - account_id The account ID
     *  - type The account type
     */
    private function getDebitAccount($payment_accounts, $client_account = false)
    {
        // Determine which account is currently set for autodebiting
        if (!empty($payment_accounts) && $client_account) {
            for ($i = 0, $num_accounts = count($payment_accounts); $i < $num_accounts; $i++) {
                // Account ID and account type must be identical
                if (($payment_accounts[$i]->id == $client_account->account_id) &&
                    ($payment_accounts[$i]->account_type == $client_account->type)) {
                    // This account is set to be autodebited
                    return [
                        'account_id' => $payment_accounts[$i]->id,
                        'type' => $payment_accounts[$i]->account_type
                    ];
                }
            }
        }

        return false;
    }
}
