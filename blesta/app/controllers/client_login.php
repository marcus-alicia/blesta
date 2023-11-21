<?php

use Blesta\Core\Util\Captcha\Captcha;

/**
 * Client portal login controller
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientLogin extends AppController
{
    /**
     * Pre-action setup method that is called before the index method, or the set controller action
     */
    public function preAction()
    {
        parent::preAction();

        $this->uses(['Users', 'Clients', 'Companies']);
        Language::loadLang(['client_login']);

        // If logged in, redirect to client main
        if ($this->Session->read('blesta_id') > 0 && $this->Session->read('blesta_client_id') > 0) {
            $this->redirect($this->base_uri);
        }

        $this->structure->set('show_header', false);

        $this->set('company', $this->Companies->get(Configure::get('Blesta.company_id')));
    }

    /**
     * Login
     */
    public function index()
    {
        $this->structure->set('page_title', Language::_('ClientLogin.index.page_title', true));

        // Get captcha instance
        $captcha = null;
        if (Captcha::enabled('client_login')) {
            $captcha = Captcha::get();
        }

        if (!empty($this->post)) {
            // Ensure the IP address is determined automatically by disallowing it from being set
            unset($this->post['ip_address']);

            // Validate captcha
            if ($captcha !== null) {
                $success = Captcha::validate($captcha, $this->post);

                if (!$success) {
                    $errors = [
                        'captcha' => ['invalid' => Language::_('ClientLogin.!error.captcha.invalid', true)]
                    ];
                }
            }

            // Attempt to log user in
            if (empty($errors)) {
                $this->Users->login($this->Session, $this->post);

                if (($errors = $this->Users->errors())) {
                    $this->setMessage('error', $errors);
                    $this->set('vars', (object)$this->post);
                } else {
                    $this->forwardPostAuth();
                }
            } else {
                $this->setMessage('error', $errors);
                $this->set('vars', (object)$this->post);
            }
        }

        $this->set('captcha', ($captcha !== null ? $captcha->buildHtml() : ''));
    }

    /**
     * Handle otp requests
     */
    public function otp()
    {
        $this->structure->set('page_title', Language::_('ClientLogin.otp.page_title', true));

        if ($this->Session->read('blesta_auth') == '') {
            $this->redirect($this->base_uri . 'login/');
        }

        if (!empty($this->post)) {
            // Ensure the IP address is determined automatically by disallowing it from being set
            unset($this->post['ip_address']);

            // Attempt to log user in
            $this->Users->login($this->Session, $this->post);

            if (($errors = $this->Users->errors())) {
                $this->setMessage('error', $errors);
                $this->set('vars', (object)$this->post);
            } else {
                $this->forwardPostAuth();
            }
        }
    }

    /**
     * Reset password
     */
    public function reset()
    {
        $this->uses(['Clients', 'Contacts', 'Emails']);

        $this->structure->set('page_title', Language::_('ClientLogin.reset.page_title', true));

        // Get captcha instance
        $captcha = null;
        if (Captcha::enabled('client_login_reset')) {
            $captcha = Captcha::get();
        }

        if (!empty($this->post)) {
            // Validate captcha
            if ($captcha !== null) {
                $success = Captcha::validate($captcha, $this->post);

                if (!$success) {
                    $errors = [
                        'captcha' => ['invalid' => Language::_('ClientLogin.!error.captcha.invalid', true)]
                    ];
                }
            }

            if (empty($errors)) {
                $sent = Configure::get('Blesta.default_password_reset_value');

                if (isset($this->post['username']) && ($user = $this->Users->getByUsername($this->post['username']))) {
                    // Send reset password email
                    $client = $this->Clients->getByUserId($user->id);
                    if ($client && $client->status == 'active') {
                        $contact = null;

                        if (!($contact = $this->Contacts->getByUserId($user->id, $client->id))) {
                            $contact = $client;
                        }

                        // Get the company hostname
                        $hostname = isset(Configure::get('Blesta.company')->hostname)
                            ? Configure::get('Blesta.company')->hostname
                            : '';
                        $requestor = $this->getFromContainer('requestor');

                        $time = time();
                        $hash = $this->Clients->systemHash('u=' . $user->id . '|t=' . $time);
                        $tags = [
                            'client' => $client,
                            'contact' => $contact,
                            'ip_address' => $requestor->ip_address,
                            'password_reset_url' => $this->Html->safe(
                                $hostname . $this->base_uri . 'login/confirmreset/?sid=' .
                                rawurlencode(
                                    $this->Clients->systemEncrypt(
                                        'u=' . $user->id . '|t=' . $time . '|h=' . substr($hash, -16)
                                    )
                                )
                            )
                        ];
                        $this->Emails->send(
                            'reset_password',
                            $this->company_id,
                            Configure::get('Blesta.language'),
                            $contact->email,
                            $tags,
                            null,
                            null,
                            null,
                            ['to_client_id' => $client->id]
                        );
                        $sent = true;
                    }
                }

                if ($sent) {
                    $this->setMessage('message', Language::_('ClientLogin.!success.reset_sent', true));
                } else {
                    $this->setMessage('error', Language::_('ClientLogin.!error.unknown_user', true));
                }
            } else {
                $this->setMessage('error', $errors);
                $this->set('vars', (object)$this->post);
            }
        }

        $this->set('captcha', ($captcha !== null ? $captcha->buildHtml() : ''));
    }

    /**
     * Confirm password reset
     */
    public function confirmReset()
    {
        $this->uses(['Clients']);

        $this->structure->set('page_title', Language::_('ClientLogin.confirmreset.page_title', true));

        // Verify parameters
        if (!isset($this->get['sid'])) {
            $this->redirect($this->base_uri . 'login/');
        }

        $params = [];
        $temp = explode('|', $this->Clients->systemDecrypt($this->get['sid']));

        if (count($temp) <= 1) {
            $this->redirect($this->base_uri . 'login/');
        }

        foreach ($temp as $field) {
            $field = explode('=', $field, 2);
            $params[$field[0]] = $field[1];
        }

        // Verify reset has not expired
        $expiration_date = $this->Date->toTime($this->Date->modify(
            date('c'),
            '-' . Configure::get('Blesta.reset_password_ttl'),
            'c',
            Configure::get('Blesta.company_timezone')
        ));

        if ($params['t'] < $expiration_date) {
            $this->redirect($this->base_uri . 'login/');
        }

        // Verify hash matches
        if ($params['h'] != substr($this->Clients->systemHash('u=' . $params['u'] . '|t=' . $params['t']), -16)) {
            $this->redirect($this->base_uri . 'login/');
        }

        // Attempt to update the user's password and log in
        if (!empty($this->post)) {
            $client = $this->Clients->getByUserId($params['u']);
            $user = $this->Users->get($params['u']);

            if ($user && $client && $client->status == 'active') {
                // Update the user's password
                $this->Users->edit($params['u'], $this->post);

                if (!($errors = $this->Users->errors())) {
                    $this->post['username'] = $user->username;
                    $this->post['password'] = $this->post['new_password'];

                    // Ensure the IP address is determined automatically by disallowing it from being set
                    unset($this->post['ip_address']);

                    // Attempt to log user in
                    $this->Users->login($this->Session, $this->post);

                    $this->forwardPostAuth();
                } else {
                    $this->setMessage('error', $errors);
                }
            }
        }
    }

    /**
     * Forgot username
     */
    public function forgot()
    {
        $this->uses(['Clients', 'Contacts', 'Emails']);

        $this->structure->set('page_title', Language::_('ClientLogin.forgot.page_title', true));

        // Get captcha instance
        $captcha = null;
        if (Captcha::enabled('client_login_forgot')) {
            $captcha = Captcha::get();
        }

        if (!empty($this->post)) {
            // Validate captcha
            if ($captcha !== null) {
                $success = Captcha::validate($captcha, $this->post);

                if (!$success) {
                    $errors = [
                        'captcha' => ['invalid' => Language::_('ClientLogin.!error.captcha.invalid', true)]
                    ];
                }
            }

            if (empty($errors)) {
                $sent = Configure::get('Blesta.default_forgot_username_value');

                if (isset($this->post['email']) && ($users = $this->Users->getAllByEmail($this->post['email']))) {
                    foreach ($users as $user) {
                        // Send forgot username email
                        $client = $this->Clients->getByUserId($user->id);
                        $contact = null;

                        if (!($contact = $this->Contacts->getByUserId($user->id, $client->id))) {
                            $contact = $client;
                        }

                        if ($client && $client->status == 'active') {
                            $requestor = $this->getFromContainer('requestor');
                            $tags = [
                                'client' => $client,
                                'contact' => $contact,
                                'ip_address' => $requestor->ip_address,
                                'username' => $user->username
                            ];
                            $this->Emails->send(
                                'forgot_username',
                                $this->company_id,
                                Configure::get('Blesta.language'),
                                $contact->email,
                                $tags,
                                null,
                                null,
                                null,
                                ['to_client_id' => $client->id]
                            );
                            $sent = true;
                        }
                    }
                }

                if ($sent) {
                    $this->setMessage('message', Language::_('ClientLogin.!success.forgot_sent', true));
                } else {
                    $this->setMessage('error', Language::_('ClientLogin.!error.unknown_email', true));
                }
            } else {
                $this->setMessage('error', $errors);
                $this->set('vars', (object)$this->post);
            }
        }

        $this->set('captcha', ($captcha !== null ? $captcha->buildHtml() : ''));
    }

    /**
     * Finishes logging in the client and forwards the user off to the desired location
     */
    private function forwardPostAuth()
    {
        // Verify client can log in to this company and log
        if ($this->Session->read('blesta_id')) {
            $client = $this->Clients->getByUserId($this->Session->read('blesta_id'));

            if (!$client) {
                $this->Session->clear();
                $this->flashMessage('error', Language::_('Users.!error.username.auth', true));
                $this->redirect($this->base_uri . 'login');
            }

            $this->Session->write('blesta_company_id', Configure::get('Blesta.company_id'));
            $this->Session->write('blesta_client_id', $client->id);

            // Detect if we should forward after logging in and do so
            if (isset($this->post['forward_to'])) {
                $forward_to = $this->post['forward_to'];
            } else {
                // Only forward to the URL if it is in the logged-in interface
                $forward_to = $this->Session->read('blesta_forward_to');
                $forward_to = (
                    strtolower($forward_to) !== strtolower(str_ireplace($this->base_uri, '', $forward_to))
                        ? $forward_to
                        : null
                );
            }

            $this->Session->clear('blesta_forward_to');
            if (!$forward_to) {
                $forward_to = $this->base_uri;
            }

            $this->redirect($forward_to);
        } else {
            // Requires OTP auth
            $this->redirect($this->base_uri . 'login/otp');
        }
    }
}
