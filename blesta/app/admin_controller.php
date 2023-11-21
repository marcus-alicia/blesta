<?php
/**
 * Admin Parent Controller
 *
 * @package blesta
 * @subpackage blesta.app
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminController extends AppController
{
    /**
     * Admin pre-action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        Language::loadLang([Loader::fromCamelCase(get_class($this))]);
    }
}
