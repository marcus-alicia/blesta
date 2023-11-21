<?php

/**
 * Admin Dialog modal boxes
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminDialog extends AppController
{
    /**
     * Pre-action setup method that is called before the index method, or the set controller action
     */
    public function preAction()
    {
        parent::preAction();

        Language::loadLang(['admin_dialog']);
    }

    /**
     * Show the confirmation dialog modal
     *
     * @return false
     */
    public function confirm()
    {
        $this->setMessage(
            'notice',
            isset($this->get['message']) ? $this->get['message'] : null,
            false,
            ['show_close' => false]
        );

        echo $this->view->fetch('admin_dialog_confirm');
        return false;
    }

    /**
     * Show the password generator dialog modal
     *
     * @return false
     */
    public function password()
    {
        echo $this->view->fetch('admin_dialog_password');
        return false;
    }
}
