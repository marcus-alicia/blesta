<?php

/**
 * Client email verification controller
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientVerify extends AppController
{
    /**
     * Pre-action setup method that is called before the index method, or the set controller action
     */
    public function preAction()
    {
        parent::preAction();

        $this->uses(['Contacts', 'Clients', 'EmailVerifications']);
        Language::loadLang(['client_verify']);
    }

    /**
     * Verify an email address by the given token
     */
    public function index()
    {
        // Ensure we have a valid token
        if (
            !($email_verification = $this->EmailVerifications->getByToken(
                (isset($this->get['token']) ? $this->get['token'] : null)
            ))
        ) {
            $this->flashMessage(
                'error',
                Language::_('ClientVerify.!error.invalid_token', true)
            );

            if ($this->isLoggedIn()) {
                $this->redirect($this->base_uri);
            } else {
                $this->redirect($this->base_uri . 'login/');
            }
        }

        if (isset($email_verification->verified) && $email_verification->verified == 0) {
            $this->EmailVerifications->verify($email_verification->id);
            $this->flashMessage(
                'message',
                Language::_('ClientVerify.!success.email_verified', true)
            );
        }

        $this->redirect($email_verification->redirect_url);
    }

    /**
     * Re-sends the email verification link
     */
    public function send()
    {
        // Verify parameters
        if (!isset($this->get['sid'])) {
            $this->redirect($this->base_uri);
        }

        $params = [];
        $temp = explode('|', $this->Clients->systemDecrypt($this->get['sid']));

        if (count($temp) <= 1) {
            $this->redirect($this->base_uri);
        }

        foreach ($temp as $field) {
            $field = explode('=', $field, 2);
            $params[$field[0]] = $field[1];
        }

        // Verify hash matches
        if ($params['h'] != substr($this->Clients->systemHash('c=' . $params['c'] . '|t=' . $params['t']), -16)) {
            $this->redirect($this->base_uri);
        }

        // Verify email address
        if (($email_verification = $this->EmailVerifications->getByContactId($params['c']))) {
            $this->EmailVerifications->send($email_verification->id);
            $this->flashMessage(
                'message',
                Language::_('ClientVerify.!success.email_sent', true, $email_verification->email)
            );
        }

        if (!empty($this->get['redirect'])) {
            $this->redirect($this->get['redirect']);
        } else {
            if ($this->isLoggedIn()) {
                $this->redirect($this->base_uri);
            } else {
                $this->redirect($this->base_uri . 'login/');
            }
        }
    }
}
