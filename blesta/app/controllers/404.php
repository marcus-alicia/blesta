<?php

/**
 * Handle 404 (File not found) Requests
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
// @codingStandardsIgnoreStart
class _404 extends AppController
{
    // @codingStandardsIgnoreEnd
    /**
     * Pre-action setup method that is called before the index method, or the set controller action
     */
    public function preAction()
    {
        parent::preAction();
        Language::loadLang(['_404']);

        // Set the base URI as the client URI in structure, simply to link the header logo to it
        $this->structure->base_uri = $this->client_uri;

        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    }
}
