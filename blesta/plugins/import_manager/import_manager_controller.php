<?php
/**
 * Import Manager parent controller
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ImportManagerController extends AppController
{
    /**
     * Setup
     */
    public function preAction()
    {
        parent::preAction();

        // Auto load language for the controller
        Language::loadLang([Loader::fromCamelCase(get_class($this))], null, dirname(__FILE__) . DS . 'language' . DS);

        // Override default view directory
        $this->view->view = 'default';
        $this->orig_structure_view = $this->structure->view;
        $this->structure->view = 'default';
    }
}
