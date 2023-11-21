<?php
/**
 * Import Manager main controller
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Main extends ImportManagerController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        parent::preAction();

        $this->redirect();
    }
}
