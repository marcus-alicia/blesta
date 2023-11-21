<?php
/**
 * Feed Reader parent controller for all Feed Reader child controllers to inherit from
 *
 * @package blesta
 * @subpackage blesta.plugins.feed_reader
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class FeedReaderController extends AppController
{
    public function preAction()
    {
        parent::preAction();

        // Override default view directory
        $this->view->view = 'default';
        $this->structure->view = 'default';
        //$this->view->setDefaultViewPath("FeedReader");
    }
}
