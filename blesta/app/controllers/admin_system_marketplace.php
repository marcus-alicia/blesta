<?php

/**
 * Admin System Marketplace
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminSystemMarketplace extends AppController
{
    /**
     * Pre-action setup method that is called before the index method, or the set controller action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        $this->uses(['Navigation', 'Marketplace']);

        Language::loadLang('admin_system_marketplace');

        if (!$this->isAjax()) {
            // Set the left nav for all settings pages to settings_leftnav
            $this->set(
                'left_nav',
                $this->partial('settings_leftnav', ['nav' => $this->Navigation->getSystem($this->base_uri)])
            );
        }

        // Set jRating
        $this->Javascript->setFile('jquery-jrating.min.js');
        $this->structure->set('jrating_css', $this->structure->view_dir . 'css/jquery.jrating.css');
    }

    /**
     * Index
     */
    public function index()
    {
        // Set current page of results
        $page = (isset($this->get[0]) ? (int) $this->get[0] : 1);

        #
        # TODO: set category to current category
        #
        $this->set('category', 'all');

        // Get the extensions
        if (($marketplace = $this->Marketplace->getList(null, $page))) {
            // Set the extensions
            $this->set('marketplace', $marketplace->results);

            // Overwrite default pagination settings
            #
            # TODO: set the page that was selected, and any sort/order options
            #
            $settings = array_merge(
                Configure::get('Blesta.pagination'),
                [
                    'total_results' => $marketplace->results->total,
                    'uri' => Configure::get('Blesta.marketplace_url'),
                    'params' => []
                ]
            );
            $this->setPagination($this->get, $settings);
        }

        // Render the request if ajax
        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]));
    }

    /**
     * Details on a specific listing page
     */
    public function listing()
    {
        // Ensure an extension was given
        if (!isset($this->get[0]) || !($extension = $this->Marketplace->getExtension($this->get[0]))) {
            $this->redirect($this->base_uri . 'settings/system/marketplace/');
        }

        $this->set('extension', $extension);
    }
}
