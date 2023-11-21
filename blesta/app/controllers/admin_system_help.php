<?php

/**
 * Admin System Help
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminSystemHelp extends AppController
{
    /**
     * Prepare the left navigation of this settings page
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        $this->uses(['Navigation']);

        Language::loadLang('admin_system_help');

        // Set the left nav for all settings pages to settings_leftnav
        $this->set(
            'left_nav',
            $this->partial('settings_leftnav', ['nav' => $this->Navigation->getSystem($this->base_uri)])
        );
    }

    /**
     * Lists all Help options
     */
    public function index()
    {
    }

    /**
     * Display development credits
     */
    public function credits()
    {
        if (!$this->isAjax()) {
            $this->redirect($this->base_uri . 'settings/system/');
        }

        echo $this->partial('admin_system_help_credits');
        return false;
    }
}
