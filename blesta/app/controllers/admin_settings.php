<?php

/**
 * Admin Settings
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminSettings extends AppController
{
    /**
     * Pre-action setup method that is called before the index method, or the set controller action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        Language::loadLang('admin_settings');
    }

    /**
     * Settings landing page
     */
    public function index()
    {
        $this->redirect($this->base_uri . 'settings/company/');
    }

    /**
     * Company Settings page
     */
    public function company()
    {
        return $this->renderAjaxWidgetIfAsync();
    }

    /**
     * System Settings page
     */
    public function system()
    {
        return $this->renderAjaxWidgetIfAsync();
    }
}
