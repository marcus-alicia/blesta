<?php

/**
 * Admin System API Settings
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminSystemApi extends AppController
{
    /**
     * Initialize
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        $this->uses(['Navigation', 'ApiKeys']);

        // Create an array helper
        $this->ArrayHelper = $this->DataStructure->create('Array');

        Language::loadLang('admin_system_api');

        // Set the left nav for all settings pages to settings_leftnav
        $this->set(
            'left_nav',
            $this->partial('settings_leftnav', ['nav' => $this->Navigation->getSystem($this->base_uri)])
        );
    }

    /**
     * List API keys
     */
    public function index()
    {
        $page = isset($this->get[0]) ? $this->get[0] : 1;
        $total_results = $this->ApiKeys->getListCount();
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_created');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        $this->set('keys', $this->ApiKeys->getList($page, [$sort => $order]));
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'settings/system/api/index/[p]/',
                'params' => ['sort' => $sort, 'order' => $order],
            ]
        );
        $this->setPagination($this->get, $settings);

        return $this->renderAjaxWidgetIfAsync(isset($this->get[1]) || isset($this->get['sort']));
    }

    /**
     * Add an API key
     */
    public function add()
    {
        $this->uses(['Companies']);

        $this->setMessage('notice', Language::_('AdminSystemApi.!notice.api_not_restrictive', true));

        if (!empty($this->post)) {
            // Attempt to add the API key
            $this->ApiKeys->add($this->post);

            // Set error/success messages
            if (($errors = $this->ApiKeys->errors())) {
                $this->set('vars', (object) $this->post);
                $this->setMessage('error', $errors);
            } else {
                $this->flashMessage('message', Language::_('AdminSystemApi.!success.key_added', true));
                $this->redirect($this->base_uri . 'settings/system/api/');
            }
        }

        $this->set('companies', $this->ArrayHelper->numericToKey($this->Companies->getAll(), 'id', 'name'));
    }

    /**
     * Edit an API key
     */
    public function edit()
    {
        if (!isset($this->get[0]) || !($api_key = $this->ApiKeys->get($this->get[0]))) {
            $this->redirect($this->base_uri . 'settings/system/api/');
        }

        $this->uses(['Companies']);

        $this->setMessage('notice', Language::_('AdminSystemApi.!notice.api_not_restrictive', true));
        $this->set('vars', $api_key);

        if (!empty($this->post)) {
            // Attempt to add the API key
            $this->ApiKeys->edit($this->get[0], $this->post);

            // Set error/success messages
            if (($errors = $this->ApiKeys->errors())) {
                $this->set('vars', (object) $this->post);
                $this->setMessage('error', $errors);
            } else {
                $this->flashMessage('message', Language::_('AdminSystemApi.!success.key_updated', true));
                $this->redirect($this->base_uri . 'settings/system/api/');
            }
        }

        $this->set('companies', $this->ArrayHelper->numericToKey($this->Companies->getAll(), 'id', 'name'));
    }

    /**
     * Delete an API key
     */
    public function delete()
    {
        if (!isset($this->post['id'])) {
            $this->redirect($this->base_uri . 'settings/system/api/');
        }

        $this->ApiKeys->delete($this->post['id']);

        $this->flashMessage('message', Language::_('AdminSystemApi.!success.key_deleted', true));
        $this->redirect($this->base_uri . 'settings/system/api/');
    }
}
