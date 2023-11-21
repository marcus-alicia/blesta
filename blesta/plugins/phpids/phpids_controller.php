<?php
/**
 * PHPIDS parent controller for all PHPIDS child controllers to inherit from
 *
 * @package blesta
 * @subpackage blesta.plugins.phpids
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PhpidsController extends AppController
{
    public function preAction()
    {
        parent::preAction();

        // Override default view directory
        $this->view->view = 'default';
        $this->structure->view = 'default';
    }
}
