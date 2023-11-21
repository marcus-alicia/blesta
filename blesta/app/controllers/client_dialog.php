<?php

/**
 * Client Dialog modal boxes
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientDialog extends ClientController
{
    /**
     * Pre-action setup method that is called before the index method, or the set controller action
     */
    public function preAction()
    {
        parent::preAction();

        Language::loadLang(['client_dialog']);
    }

    /**
     * Retrieves the confirmation dialog
     *
     * @return false
     */
    public function confirm()
    {
        $this->set($this->get);
        $this->setMessage(
            'notice',
            isset($this->get['message']) ? $this->get['message'] : null,
            false,
            ['show_close' => false]
        );
        echo $this->view->fetch('client_dialog_confirm');
        return false;
    }

    /**
     * Show the password generator dialog modal
     *
     * @return false
     */
    public function password()
    {
        echo $this->view->fetch('client_dialog_password');
        return false;
    }
}
