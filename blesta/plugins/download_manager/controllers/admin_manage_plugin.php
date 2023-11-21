<?php
/**
 * Download Manager manage plugin controller
 *
 * @package blesta
 * @subpackage blesta.plugins.download_manager
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminManagePlugin extends AppController
{
    /**
     * Returns the view to be rendered when managing this plugin
     */
    public function index()
    {
        $this->parent->requireLogin();
        $this->redirect($this->base_uri . 'plugin/download_manager/admin_main/files/');
    }
}
