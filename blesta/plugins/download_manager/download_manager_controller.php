<?php
/**
 * Download Manager parent controller
 *
 * @package blesta
 * @subpackage blesta.plugins.download_manager
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class DownloadManagerController extends AppController
{
    public function preAction()
    {
        $this->structure->setDefaultView(APPDIR);

        parent::preAction();

        // Set the portal type
        $this->portal = 'client';
        if (substr($this->controller, 0, 5) == 'admin') {
            $this->portal = 'admin';
        }

        // Require login if is an admin controller
        if ($this->portal == 'admin') {
            $this->requireLogin();
        }

        // Auto load language for the controller
        Language::loadLang(
            [Loader::fromCamelCase(get_class($this))],
            null,
            dirname(__FILE__) . DS . 'language' . DS
        );
        Language::loadLang(
            'download_manager_manage_plugin',
            null,
            dirname(__FILE__) . DS . 'language' . DS
        );

        // Set the company ID
        $this->company_id = Configure::get('Blesta.company_id');

        // Set the page title
        $this->structure->set(
            'page_title',
            Language::_(
                'DownloadManagerManagePlugin.' . Loader::fromCamelCase($this->action ?? 'index')
                . '.page_title',
                true
            )
        );

        // Override default view directory
        $this->view->view = 'default';
        $this->orig_structure_view = $this->structure->view;
        $this->structure->view = 'default';

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);
    }
}
