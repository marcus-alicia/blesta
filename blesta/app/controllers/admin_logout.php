<?php

/**
 * Administrative Logout
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminLogout extends AppController
{
    /**
     * The logout action
     */
    public function index()
    {
        $this->uses(['Users']);

        // log user out
        $this->Users->logout($this->Session);

        // Redirect to admin login
        $this->redirect($this->base_uri . 'login');
    }
}
