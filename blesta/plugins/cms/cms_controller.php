<?php
/**
 * CMS parent controller
 *
 * @package blesta
 * @subpackage blesta.plugins.cms
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class CmsController extends AppController
{
    public function preAction()
    {
        $this->structure->setDefaultView(APPDIR);
        parent::preAction();

        // Override default view directory
        $this->view->view = 'default';
        $this->structure->view = 'default';

        // Attempt to write this page to the cache
        try {
            //if (Configure::get("Caching.on"))
            //	$this->startCaching(Configure::get("Blesta.cache_length"));
        } catch (Exception $e) {
            // Cache not writable
        }
    }
}
