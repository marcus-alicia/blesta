<?php

/**
 * Admin System Company Settings
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminSystemCompanies extends AppController
{
    /**
     * Pre-action setup method that is called before the index method, or the set controller action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        $this->uses(['Companies', 'Navigation']);

        Language::loadLang('admin_system_companies');

        // Set the left nav for all settings pages to settings_leftnav
        $this->set(
            'left_nav',
            $this->partial('settings_leftnav', ['nav' => $this->Navigation->getSystem($this->base_uri)])
        );
    }

    /**
     * Manage companies
     */
    public function index()
    {
        // Set current page of results
        $page = (isset($this->get[0]) ? (int) $this->get[0] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'name');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        // Get all companies
        $companies = $this->Companies->getList($page, [$sort => $order]);
        $total_results = $this->Companies->getListCount();

        $this->set('companies', $companies);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'settings/system/companies/index/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        // Render the request if ajax
        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));
    }

    /**
     * Add a company
     */
    public function add()
    {
        $this->uses(['Staff', 'StaffGroups']);

        $vars = new stdClass();

        // Add a new company
        if (!empty($this->post)) {
            $company_id = $this->Companies->add($this->post);

            if (($errors = $this->Companies->errors())) {
                // Error, reset vars
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                // Success
                $company = $this->Companies->get($company_id);

                // Fetch the current staff member
                $staff = $this->Staff->get($this->Session->read('blesta_staff_id'), $this->company_id);

                // Clone the current staff member group as a new group for this new company
                $staff_group_id = $this->StaffGroups->cloneGroup($staff->group->id, $company_id);

                // Assign the staff member to the new group
                $this->Staff->assignGroup($staff->id, $staff_group_id);

                $this->flashMessage(
                    'message',
                    Language::_('AdminSystemCompanies.!success.company_added', true, $company->name)
                );
                $this->redirect($this->base_uri . 'settings/system/companies/');
            }
        }

        $this->set(
            'companies',
            $this->Form->collapseObjectArray(
                $this->Companies->getAllAvailable($this->Session->read('blesta_staff_id')),
                'name',
                'id'
            )
        );
        $this->set('vars', $vars);
    }

    /**
     * Edit a company
     */
    public function edit()
    {
        if (!isset($this->get[0]) || !($company = $this->Companies->get((int) $this->get[0]))) {
            $this->redirect($this->base_uri . 'settings/system/companies/');
        }

        $vars = [];

        if (!empty($this->post)) {
            // Edit the company
            $this->Companies->edit($company->id, $this->post);

            if (($errors = $this->Companies->errors())) {
                // Error, reset vars
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                // Success
                $company = $this->Companies->get($company->id);
                $this->flashMessage(
                    'message',
                    Language::_('AdminSystemCompanies.!success.company_edited', true, $company->name)
                );
                $this->redirect($this->base_uri . 'settings/system/companies/');
            }
        }

        // Set this company
        if (empty($vars)) {
            $vars = $company;
        }

        $this->set('vars', $vars);
    }

    /**
     * Delete a company
     */
    public function delete()
    {
        if (!isset($this->post['id']) || !($company = $this->Companies->get((int) $this->post['id']))) {
            $this->redirect($this->base_uri . 'settings/system/companies/');
        }

        if (!empty($this->post)) {
            // Edit the company
            $this->Companies->delete($company->id);

            if (($errors = $this->Companies->errors())) {
                $this->flashMessage('error', $errors);
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminSystemCompanies.!success.company_deleted', true, $company->name)
                );
            }
        }
        $this->redirect($this->base_uri . 'settings/system/companies/');
    }
}
