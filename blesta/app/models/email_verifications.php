<?php

/**
 * Email Verifications management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class EmailVerifications extends AppModel
{
    /**
     * @var string The client uri
     */
    private $client_uri;

    /**
     * Initialize EmailVerifications
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['email_verifications']);

        $this->client_uri = WEBDIR . Configure::get('Route.client') . '/';
    }

    /**
     * Fetches the given email verification
     *
     * @param int $verification_id The ID of the email verification to fetch
     * @return mixed A stdClass object containing the email verification information,
     *  false if no such email verification exists
     */
    public function get($verification_id)
    {
        return $this->Record->select()
            ->from('email_verifications')
            ->where('email_verifications.id', '=', $verification_id)
            ->fetch();
    }

    /**
     * Fetches all the email verifications on the system
     *
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - contact_id The ID of the contact on which to filter email verifications
     *  - email The email address on which to filter email verifications
     *  - verified The status type of the email verification to fetch (optional, default null) one of the following:
     *      - 1 Fetch only verified rows
     *      - 0 Fetch only unverified rows
     *      - null Fetch all rows
     *  - date_sent
     * @return array An array of stdClass objects containing the email verification information
     */
    public function getAll(array $filters = [])
    {
        $this->Record->select()
            ->from('email_verifications');

        // Filter on contact id
        if (!empty($filters['contact_id'])) {
            $this->Record->where('email_verifications.contact_id', '=', $filters['contact_id']);
        }

        // Filter on email address
        if (!empty($filters['email'])) {
            $this->Record->where('email_verifications.email', '=', $filters['email']);
        }

        // Filter on verified status
        if (isset($filters['verified'])) {
            $this->Record->where('email_verifications.verified', '=', $filters['verified']);
        }

        // Filter on verified date sent
        if (isset($filters['date_sent'])) {
            $this->Record->where(
                'email_verifications.date_sent',
                '>=',
                $this->dateToUtc(
                    $this->Date->cast($filters['date_sent'] . ' 00:00:00', 'Y-m-d')
                )
            )->where(
                'email_verifications.date_sent',
                '<=',
                $this->dateToUtc(
                    $this->Date->cast($filters['date_sent'] . ' 23:59:59', 'Y-m-d')
                )
            );
        }

        return $this->Record->fetchAll();
    }

    /**
     * Fetches the given email verification by their token
     *
     * @param string $token The token of the email verification to fetch
     * @return mixed A stdClass object containing the email verification information,
     *  false if no such email verification exists
     */
    public function getByToken($token)
    {
        return $this->Record->select()
            ->from('email_verifications')
            ->where('email_verifications.token', '=', $token)
            ->order(['id' => 'desc'])
            ->fetch();
    }

    /**
     * Fetches the latest email verification by their contact ID
     *
     * @param int $contact_id The ID of the contact on which to fetch the latest email verification
     * @return mixed A stdClass object containing the email verification information,
     *  false if no such email verification exists
     */
    public function getByContactId($contact_id)
    {
        return $this->Record->select()
            ->from('email_verifications')
            ->where('email_verifications.contact_id', '=', $contact_id)
            ->order(['id' => 'desc'])
            ->fetch();
    }

    /**
     * Creates a new email verification using the given data
     *
     * @param array $vars An array of invoice data including:
     *
     *  - contact_id The ID of the contact to verify
     *  - email The email address to verify
     *  - token The email verification token (optional, If not given a token will be automatically generated)
     *  - verified Whether or not the email it's verified, (optional, 0 by default)
     *  - redirect_url The url to redirect after a successfully verification
     * @param bool $send Whether or not to send the verification email to the client (optional, true by default)
     * @return int The email verification ID, void on error
     */
    public function add($vars, $send = true)
    {
        // Set the rules for adding
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            // Trigger EmailVerifications.addBefore event
            $eventFactory = $this->getFromContainer('util.events');
            $eventListener = $eventFactory->listener();
            $eventListener->register('EmailVerifications.addBefore');
            $eventListener->trigger(
                $eventFactory->event('EmailVerifications.addBefore', ['vars' => $vars])
            );

            // Delete any past email verifications
            $this->Record->from('email_verifications')->where('contact_id', '=', $vars['contact_id'])->delete();

            // Set the default values for optional parameters
            $default = [
                'token' => $this->generateToken($vars['email']),
                'verified' => 0,
                'date_sent' => $this->dateToUtc(date('c')),
                'redirect_url' => $this->client_uri . 'login/'
            ];
            $vars = array_merge($default, $vars);

            // Add the email verification
            $fields = ['contact_id', 'email', 'token', 'verified', 'redirect_url', 'date_sent'];
            $this->Record->duplicate('token', '=', $vars['token'])
                ->duplicate('verified', '=', $vars['verified'])
                ->duplicate('redirect_url', '=', $vars['redirect_url'])
                ->duplicate('date_sent', '=', $vars['date_sent'])
                ->insert('email_verifications', $vars, $fields);

            $verification_id = $this->Record->lastInsertId();

            if ($send) {
                $this->send($verification_id);
            }

            if ($verification_id) {
                // Trigger EmailVerifications.addAfter event
                $eventFactory = $this->getFromContainer('util.events');
                $eventListener = $eventFactory->listener();
                $eventListener->register('EmailVerifications.addAfter');
                $eventListener->trigger(
                    $eventFactory->event('EmailVerifications.addAfter', ['verification_id' => $verification_id, 'vars' => $vars])
                );
            }

            return $verification_id;
        }
    }

    /**
     * Updates an email verification using the given data
     *
     * @param int $verification_id The ID of the email verification to update
     * @param array $vars An array of invoice data including:
     *
     *  - contact_id The ID of the contact to verify
     *  - email The email address to verify
     *  - token The email verification token
     *  - verified Whether or not the email it's verified
     *  - redirect_url The url to redirect after a successfully verification
     * @return int The email verification ID, void on error
     */
    public function edit($verification_id, $vars)
    {
        // Set the rules for editing
        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            // Get old verification
            $old_verification = $this->get($verification_id);

            // Trigger EmailVerifications.editBefore event
            $eventFactory = $this->getFromContainer('util.events');
            $eventListener = $eventFactory->listener();
            $eventListener->register('EmailVerifications.editBefore');
            $eventListener->trigger(
                $eventFactory->event('EmailVerifications.editBefore', [
                    'verification_id' => $verification_id,
                    'vars' => $vars,
                    'old_verification' => $old_verification
                ])
            );

            $fields = ['contact_id', 'email', 'token', 'verified', 'redirect_url', 'date_sent'];

            $this->Record->where('email_verifications.id', '=', $verification_id)
                ->update('email_verifications', $vars, $fields);

            // Trigger EmailVerifications.editAfter event
            $eventFactory = $this->getFromContainer('util.events');
            $eventListener = $eventFactory->listener();
            $eventListener->register('EmailVerifications.editAfter');
            $eventListener->trigger(
                $eventFactory->event('EmailVerifications.editAfter', [
                    'verification_id' => $verification_id,
                    'vars' => $vars,
                    'old_verification' => $old_verification
                ])
            );

            return $verification_id;
        }
    }

    /**
     * Permanently removes an email verification from the system
     *
     * @param int $verification_id The ID of the email verification to delete
     */
    public function delete($verification_id)
    {
        // Trigger EmailVerifications.deleteBefore event
        $eventFactory = $this->getFromContainer('util.events');
        $eventListener = $eventFactory->listener();
        $eventListener->register('EmailVerifications.deleteBefore');
        $eventListener->trigger(
            $eventFactory->event('EmailVerifications.deleteBefore', ['verification_id' => $verification_id])
        );

        $this->Record->from('email_verifications')
            ->where('email_verifications.id', '=', $verification_id)
            ->delete();

        // Trigger EmailVerifications.deleteAfter event
        $eventFactory = $this->getFromContainer('util.events');
        $eventListener = $eventFactory->listener();
        $eventListener->register('EmailVerifications.deleteAfter');
        $eventListener->trigger(
            $eventFactory->event('EmailVerifications.deleteAfter', ['verification_id' => $verification_id])
        );
    }

    /**
     * Permanently removes all email verifications from a specific contact
     *
     * @param int $contact_id The ID of the contact to delete all verifications
     */
    public function deleteAll($contact_id)
    {
        $this->Record->from('email_verifications')
            ->where('email_verifications.contact_id', '=', $contact_id)
            ->delete();
    }

    /**
     * Verifies an email address
     *
     * @param int $verification_id The ID of the email verification to set as verified
     */
    public function verify($verification_id)
    {
        Loader::loadModels($this, ['Contacts', 'Clients', 'Users']);

        $verification = $this->get($verification_id);
        $contact = $this->Contacts->get($verification->contact_id);
        $client = $this->Clients->get($contact->client_id);
        $user = $this->Users->get(isset($contact->user_id) ? $contact->user_id : $client->user_id);

        // Trigger EmailVerifications.verifyBefore event
        $eventFactory = $this->getFromContainer('util.events');
        $eventListener = $eventFactory->listener();
        $eventListener->register('EmailVerifications.verifyBefore');
        $eventListener->trigger(
            $eventFactory->event('EmailVerifications.verifyBefore', ['verification_id' => $verification_id])
        );

        // Update contact email address
        $this->Contacts->edit($contact->id, [
            'first_name' => $contact->first_name,
            'last_name' => $contact->last_name,
            'email' => $verification->email,
            'verify' => false
        ]);

        // Update user
        if (
            $user->username == $contact->email
            || (
                isset($client->settings['username_type'])
                && $client->settings['username_type'] == 'email'
                && $user->id == $client->user_id
                && $contact->id == $client->contact_id
            )
        ) {
            $this->Users->edit($user->id, [
                'username' => $verification->email,
                'verify' => false
            ]);
        }

        // Set email verification as verified
        $this->edit($verification_id, [
            'verified' => 1
        ]);

        // Trigger EmailVerifications.verifyAfter event
        $eventFactory = $this->getFromContainer('util.events');
        $eventListener = $eventFactory->listener();
        $eventListener->register('EmailVerifications.verifyAfter');
        $eventListener->trigger(
            $eventFactory->event('EmailVerifications.verifyAfter', ['verification_id' => $verification_id])
        );
    }

    /**
     * Generates a new token based on the given email address
     *
     * @param string $email The email address to generate a verification token
     * @return string The verification token
     */
    public function generateToken($email)
    {
        $time = time();

        return $this->systemHash('e=' . $email . '|t=' . $time);
    }

    /**
     * Sends the email verification link
     *
     * @param int $verification_id The ID of the email verification link to send
     */
    public function send($verification_id)
    {
        Loader::loadModels($this, ['Contacts', 'Clients', 'Users', 'Emails']);

        $verification = $this->get($verification_id);
        $contact = $this->Contacts->get($verification->contact_id);
        $client = $this->Clients->get($contact->client_id);
        $user = $this->Users->get(isset($contact->user_id) ? $contact->user_id : $client->user_id);

        // Get the company hostname
        $hostname = isset(Configure::get('Blesta.company')->hostname)
            ? Configure::get('Blesta.company')->hostname
            : '';
        $requestor = $this->getFromContainer('requestor');

        // Update date sent
        $this->edit($verification_id, [
            'date_sent' => $this->dateToUtc(date('c'))
        ]);

        // Send email verification link
        $tags = [
            'verification_url' => $hostname . $this->client_uri . 'verify/?token=' . $verification->token,
            'contact' => $contact,
            'username' => $user->username,
            'ip_address' => $requestor->ip_address
        ];

        $this->Emails->send(
            'verify_email',
            Configure::get('Blesta.company_id'),
            Configure::get('Blesta.language'),
            $verification->email,
            $tags,
            null,
            null,
            null,
            ['to_client_id' => $client->id, 'log_masked_tags' => ['verification_url']]
        );
    }

    /**
     * Returns the rule set for adding/editing invoices
     *
     * @param array $vars The input vars
     * @return array The email verification rules
     */
    private function getRules(array $vars, $edit = false)
    {
        $rules = [
            'contact_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'contacts'],
                    'message' => $this->_('EmailVerifications.!error.contact_id.exists')
                ]
            ],
            'email' => [
                'valid' => [
                    'rule' => ['filter_var', FILTER_VALIDATE_EMAIL],
                    'message' => $this->_('EmailVerifications.!error.email.valid')
                ]
            ],
            'token' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_string',
                    'message' => $this->_('EmailVerifications.!error.token.format')
                ]
            ],
            'verified' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1']],
                    'message' => $this->_('EmailVerifications.!error.verified.valid')
                ]
            ],
            'redirect_url' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => 'is_string',
                    'message' => $this->_('EmailVerifications.!error.redirect_url.valid')
                ]
            ]
        ];

        if ($edit) {
            $rules['contact_id']['exists']['if_set'] = true;
            $rules['email']['valid']['if_set'] = true;
        }

        return $rules;
    }
}
