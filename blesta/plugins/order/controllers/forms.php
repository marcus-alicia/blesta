<?php
use \Minphp\Session\Session;
/**
 * Order forms listing
 *
 * @package blesta
 * @subpackage blesta.plugins.order
 * @copyright Copyright (c) 2016, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Forms extends OrderController
{
    /**
     * Setup
     */
    public function preAction()
    {
        parent::preAction();
        $this->structure->setView(null, 'client' . DS . $this->layout);

        $this->structure->set(
            'custom_head',
            '<link href="'
            . Router::makeURI(
                str_replace('index.php/', '', WEBDIR)
                . $this->view->view_path . 'views/' . $this->view->view
            )
            . '/css/styles.css" rel="stylesheet" type="text/css" />'
        );
    }
    
    /**
     * List available order forms
     */
    public function index()
    {
        $this->uses(['Order.OrderForms', 'Clients']);
        $this->helpers(['TextParser']);

        $parser_syntax = 'markdown';
        $visibility = ['public'];
        $client_id = $this->Session->read('blesta_client_id');

        if ($client_id > 0
            && ($client = $this->Clients->get($client_id, false))
        ) {
            $visibility[] = 'client';
        }

        $forms = $this->OrderForms->search(
            $this->company_id,
            [
                'status' => 'active',
                'visibility' => $visibility,
                'client_id' => $client_id
            ]
        );

        // If no forms, redirect away
        if (empty($forms)) {
            return $this->redirect();
        }
        // If only one form, show that form
        if (1 === count($forms)) {
            return $this->redirect(
                $this->base_uri . 'order/main/index/' . $forms[0]->label
            );
        }

        $this->set(compact('forms', 'parser_syntax'));
        $this->structure->set('title', Language::_('OrderPlugin.client.name', true));
    }

    /**
     * Process a referral link
     */
    public function a()
    {
        $code = isset($this->get[0]) ? $this->get[0] : null;

        return ($this->setAffiliateCode($code) ? $this->redirect($this->base_uri . 'order') : $this->redirect($this->base_uri));
    }
}
