<?php
/**
 * Support Manager Admin Default Responses controller
 *
 * @package blesta
 * @subpackage blesta.plugins.support_manager
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminResponses extends SupportManagerController
{
    /**
     * Setup
     */
    public function preAction()
    {
        parent::preAction();

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);
        $this->requireLogin();

        $this->uses(['SupportManager.SupportManagerResponses']);

        $this->staff_id = $this->Session->read('blesta_staff_id');
    }

    /**
     * List admin responses
     */
    public function index()
    {
        $category = (isset($this->get[0]) ? $this->SupportManagerResponses->getCategory($this->get[0]) : null);
        if ($category && $category->company_id != $this->company_id) {
            $this->redirect($this->base_uri . 'plugin/support_manager/admin_responses/');
        }

        // Build the partial for listing categories and responses
        $category_id = (isset($category->id) ? $category->id : null);
        $vars = [
            'categories' => $this->SupportManagerResponses->getAllCategories($this->company_id, $category_id),
            'category' => $category,
            'show_links' => true
        ];

        if ($category) {
            $vars['responses'] = $this->SupportManagerResponses->getAll($this->company_id, $category_id);
        }

        $this->set('response_list', $this->partial('admin_responses_response_list', $vars));
        $this->set('category', $category);
    }

    /**
     * Adds a new response category
     */
    public function add()
    {
        // Ensure a valid parent category was given
        if (!isset($this->get[0]) || !($parent_category = $this->SupportManagerResponses->getCategory($this->get[0])) ||
            $parent_category->company_id != $this->company_id) {
            $this->redirect($this->base_uri . 'plugin/support_manager/admin_responses/');
        }

        // Add response
        if (!empty($this->post)) {
            $data = $this->post;

            // Set the parent category
            $data['category_id'] = $parent_category->id;

            $response = $this->SupportManagerResponses->add($data);

            if (($errors = $this->SupportManagerResponses->errors())) {
                // Error, reset vars
                $vars = (object)$this->post;
                $this->setMessage('error', $errors, false, null, false);
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminResponses.!success.response_added', true, $response->name),
                    null,
                    false
                );
                $this->redirect(
                    $this->base_uri . 'plugin/support_manager/admin_responses/index/' . $response->category_id . '/'
                );
            }
        }

        $this->set('vars', (isset($vars) ? $vars : new stdClass()));
        $this->set('parent_category', $parent_category);
    }

    /**
     * Edits a predefined response
     */
    public function edit()
    {
        // Ensure a valid response was given
        if (!isset($this->get[0]) || !($response = $this->SupportManagerResponses->get($this->get[0])) ||
            $response->company_id != $this->company_id) {
            $this->redirect($this->base_uri . 'plugin/support_manager_admin_responses/');
        }

        // Update the response
        if (!empty($this->post)) {
            $data = $this->post;

            // Cannot update category
            unset($data['category_id']);

            $response = $this->SupportManagerResponses->edit($response->id, $data);

            if (($errors = $this->SupportManagerResponses->errors())) {
                // Error, reset vars
                $vars = (object)$this->post;
                $this->setMessage('error', $errors, false, null, false);
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminResponses.!success.response_updated', true, $response->name),
                    null,
                    false
                );
                $this->redirect(
                    $this->base_uri . 'plugin/support_manager/admin_responses/index/' . $response->category_id . '/'
                );
            }
        }

        // Set the default response
        if (!isset($vars)) {
            $vars = $response;
        }

        $this->set('vars', $vars);
    }

    /**
     * Deletes a response
     */
    public function delete()
    {
        // Ensure a valid response was given
        if (!isset($this->post['id']) || !($response = $this->SupportManagerResponses->get($this->post['id'])) ||
            $response->company_id != $this->company_id) {
            $this->redirect($this->base_uri . 'plugin/support_manager/admin_responses/');
        }

        // Delete the response
        $this->SupportManagerResponses->delete($response->id);

        $this->flashMessage(
            'message',
            Language::_('AdminResponses.!success.response_deleted', true, $response->name),
            null,
            false
        );
        $this->redirect(
            $this->base_uri . 'plugin/support_manager/admin_responses/index/'
            . (!empty($response->category_id) ? $response->category_id . '/' : '')
        );
    }

    /**
     * Adds a new response category
     */
    public function addCategory()
    {
        $parent_category = (isset($this->get[0]) ? $this->SupportManagerResponses->getCategory($this->get[0]) : null);

        // Add category
        if (!empty($this->post)) {
            $data = $this->post;

            // Set the parent category
            $data['parent_id'] = (!empty($parent_category) ? $parent_category->id : null);
            $data['company_id'] = $this->company_id;

            $category = $this->SupportManagerResponses->addCategory($data);

            if (($errors = $this->SupportManagerResponses->errors())) {
                // Error, reset vars
                $vars = (object)$this->post;
                $this->setMessage('error', $errors, false, null, false);
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminResponses.!success.category_added', true, $category->name),
                    null,
                    false
                );
                $this->redirect(
                    $this->base_uri . 'plugin/support_manager/admin_responses/index/'
                    . (!empty($category->parent_id) ? $category->parent_id . '/' : '')
                );
            }
        }

        $this->set('vars', (isset($vars) ? $vars : new stdClass()));
        $this->set('parent_category', $parent_category);
    }

    /**
     * Edits a response category
     */
    public function editCategory()
    {
        // Ensure a valid category was given
        if (!isset($this->get[0]) || !($category = $this->SupportManagerResponses->getCategory($this->get[0])) ||
            $category->company_id != $this->company_id) {
            $this->redirect($this->base_uri . 'plugin/support_manager/admin_responses/index/');
        }

        // Update category
        if (!empty($this->post)) {
            $data = $this->post;

            // Cannot update company/parent
            unset($data['company_id'], $data['parent_id']);

            $category = $this->SupportManagerResponses->editCategory($category->id, $data);

            if (($errors = $this->SupportManagerResponses->errors())) {
                // Error, reset vars
                $vars = (object)$this->post;
                $this->setMessage('error', $errors, false, null, false);
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminResponses.!success.category_updated', true, $category->name),
                    null,
                    false
                );
                $this->redirect(
                    $this->base_uri . 'plugin/support_manager/admin_responses/index/'
                    . (!empty($category->parent_id) ? $category->parent_id . '/' : '')
                );
            }
        }

        // Set default category
        if (!isset($vars)) {
            $vars = $category;
        }

        $this->set('vars', $vars);
    }

    /**
     * Deletes a response category
     */
    public function deleteCategory()
    {
        // Ensure a valid response was given
        if (!isset($this->post['id'])
            || !($category = $this->SupportManagerResponses->getCategory($this->post['id']))
            || $category->company_id != $this->company_id
        ) {
            $this->redirect($this->base_uri . 'plugin/support_manager/admin_responses/');
        }

        // Delete the response category
        $this->SupportManagerResponses->deleteCategory($category->id);

        // Set any messages
        if (($errors = $this->SupportManagerResponses->errors())) {
            $this->flashMessage('error', $errors, null, false);
        } else {
            $this->flashMessage(
                'message',
                Language::_('AdminResponses.!success.category_deleted', true, $category->name),
                null,
                false
            );
        }

        $this->redirect(
            $this->base_uri . 'plugin/support_manager/admin_responses/index/'
            . (!empty($category->parent_id) ? $category->parent_id . '/' : '')
        );
    }
}
