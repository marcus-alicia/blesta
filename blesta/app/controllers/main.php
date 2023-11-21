<?php

/**
 * Main controller
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Main extends AppController
{
    /**
     * Index
     */
    public function index()
    {
        // Redirect to client portal
        $this->redirect($this->client_uri);
    }
}
