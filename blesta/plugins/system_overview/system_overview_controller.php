<?php
/**
 * System Overview parent controller for all System Overview child controllers to inherit from
 *
 * @package blesta
 * @subpackage blesta.plugins.system_overview
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SystemOverviewController extends AppController
{
    public function preAction()
    {
        parent::preAction();

        // Override default view directory
        $this->view->view = 'default';
        $this->structure->view = 'default';
    }
}
