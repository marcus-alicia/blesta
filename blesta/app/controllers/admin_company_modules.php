<?php

/**
 * Admin Company Module Settings
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminCompanyModules extends AppController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        $this->uses(['ModuleManager', 'Navigation']);

        Language::loadLang('admin_company_modules');

        // Set the left nav for all settings pages to settings_leftnav
        $this->set(
            'left_nav',
            $this->partial('settings_leftnav', ['nav' => $this->Navigation->getCompany($this->base_uri)])
        );
    }

    /**
     * Module settings page
     */
    public function index()
    {
        $this->redirect($this->base_uri . 'settings/company/modules/installed/');
    }

    /**
     * Modules Installed page
     */
    public function installed()
    {
        $this->setTabs();
        $this->set('show_left_nav', !$this->isAjax());
        $this->set('modules', $this->ModuleManager->getAll($this->company_id));
        return $this->renderAjaxWidgetIfAsync();
    }

    /**
     * Modules Available page
     */
    public function available()
    {
        $this->setTabs();
        $this->set('show_left_nav', !$this->isAjax());
        $this->set('modules', $this->ModuleManager->getAvailable($this->company_id));
        return $this->renderAjaxWidgetIfAsync();
    }

    /**
     * Sets the installed/available tabs to the view
     */
    private function setTabs()
    {
        $this->set(
            'link_tabs',
            [
                [
                    'name' => Language::_('AdminCompanyModules.!tab.installed', true),
                    'uri' => 'installed'
                ],
                [
                    'name' => Language::_('AdminCompanyModules.!tab.available', true),
                    'uri' => 'available'
                ]
            ]
        );
    }

    /**
     * Manage a module, displays the manage interface for the requested module
     * and processes any necessary module meta data
     */
    public function manage()
    {
        $module_id = isset($this->get[0]) ? $this->get[0] : null;

        // Redirect if the module ID is invalid
        if (!($module_info = $this->ModuleManager->get($module_id))
            || ($module_info->company_id != $this->company_id)
        ) {
            $this->redirect($this->base_uri . 'settings/company/modules/installed/');
        }

        $this->components(['Modules']);

        $module = $this->Modules->create($module_info->class);
        $module->base_uri = $this->base_uri;

        $content = $module->manageModule($module_info, $this->post);

        if (!empty($this->post)) {
            if (($errors = $module->errors())) {
                $this->setMessage('error', $errors);
            } else {
                // Update the module meta data
                $this->ModuleManager->setMeta($module_id, $this->post);
                if (($errors = $this->ModuleManager->errors())) {
                    $this->setMessage('error', $errors);
                } else {
                    $this->setMessage('success', Language::_('AdminCompanyModules.!success.module_updated', true));
                }
            }
        }

        // Fetch the view to display for this module, pass in the post data (if any) so that
        // fields can be repopulated if necessary
        $this->set('content', $content);
        $this->structure->set(
            'page_title',
            Language::_('AdminCompanyModules.manage.page_title', true, $module_info->name)
        );
    }

    /**
     * Install a module for this company
     */
    public function install()
    {
        if (!isset($this->post['id'])) {
            $this->redirect($this->base_uri . 'settings/company/modules/available/');
        }

        $module_id = $this->ModuleManager->add(['class' => $this->post['id'], 'company_id' => $this->company_id]);

        if (($errors = $this->ModuleManager->errors())) {
            $this->flashMessage('error', $errors);
            $this->redirect($this->base_uri . 'settings/company/modules/available/');
        } else {
            $this->flashMessage('message', Language::_('AdminCompanyModules.!success.installed', true));
            $this->redirect($this->base_uri . 'settings/company/modules/manage/' . $module_id);
        }
    }

    /**
     * Uninstall a module for this company
     */
    public function uninstall()
    {
        if (!isset($this->post['id']) || !($module = $this->ModuleManager->get($this->post['id']))) {
            $this->redirect($this->base_uri . 'settings/company/modules/installed/');
        }

        $this->ModuleManager->delete($this->post['id']);

        if (($errors = $this->ModuleManager->errors())) {
            $this->flashMessage('error', $errors);
        } else {
            $this->flashMessage('message', Language::_('AdminCompanyModules.!success.uninstalled', true));
        }
        $this->redirect($this->base_uri . 'settings/company/modules/installed/');
    }

    /**
     * Upgrade a module
     */
    public function upgrade()
    {
        // Fetch the module to upgrade
        if (!isset($this->post['id']) || !($module = $this->ModuleManager->get($this->post['id']))) {
            $this->redirect($this->base_uri . 'settings/company/modules/installed/');
        }

        $this->ModuleManager->upgrade($this->post['id']);

        if (($errors = $this->ModuleManager->errors())) {
            $this->flashMessage('error', $errors);
        } else {
            $this->flashMessage('message', Language::_('AdminCompanyModules.!success.upgraded', true));
        }
        $this->redirect($this->base_uri . 'settings/company/modules/installed/');
    }

    /**
     * Adds the module group
     */
    public function addGroup()
    {
        $module_id = isset($this->get[0]) ? $this->get[0] : null;

        $module_info = $this->requireModule($module_id);

        // Ensure module exist
        if (!$module_info) {
            $this->redirect($this->base_uri . 'settings/company/modules/installed/');
        }

        $this->components(['Modules']);

        $module = $this->Modules->create($module_info->class);
        $module->base_uri = $this->base_uri;

        $vars = null;
        if (!empty($this->post)) {
            // Add a module group
            $this->ModuleManager->addGroup($module_id, $this->post);

            if (($errors = $this->ModuleManager->errors())) {
                $this->setMessage('error', $errors);
            } else {
                $this->flashMessage('message', Language::_('AdminCompanyModules.!success.group_added', true));
                $this->redirect($this->base_uri . 'settings/company/modules/manage/' . $module_id . '/');
            }

            $vars = (object) $this->post;
        }

        $module_rows = [];
        $module_row_key = $module->moduleRowMetaKey();
        for ($i = 0; $i < count($module_info->rows); $i++) {
            $module_rows[$module_info->rows[$i]->id] = $module_info->rows[$i]->meta->$module_row_key;
        }

        // Set the module to the view
        $this->set('module', $module);
        $this->set('module_rows', $module_rows);
        $this->set('module_add_order', $module->getGroupOrderOptions());
        $this->set('vars', $vars);
        $this->structure->set(
            'page_title',
            Language::_('AdminCompanyModules.addgroup.page_title', true, $module->getName(), $module->moduleRowName())
        );
    }

    /**
     * Updates the module group
     */
    public function editGroup()
    {
        $module_id = isset($this->get[0]) ? $this->get[0] : null;
        $module_group_id = isset($this->get[1]) ? $this->get[1] : null;

        $module_info = $this->requireModule($module_id);
        $module_group = $this->ModuleManager->getGroup($module_group_id);

        // Ensure module and module group exist
        if (!$module_info || !$module_group) {
            $this->redirect($this->base_uri . 'settings/company/modules/installed/');
        }

        $this->components(['Modules']);

        $module = $this->Modules->create($module_info->class);
        $module->base_uri = $this->base_uri;

        $vars = $module_group;
        $vars->module_rows = [];

        $module_row_key = $module->moduleRowMetaKey();

        // Set the existing module rows assigned to this group
        foreach ($vars->rows as $row) {
            $vars->module_rows[$row->id] = $row->meta->$module_row_key;
        }

        if (!empty($this->post)) {
            // Add a module group
            $this->ModuleManager->editGroup($module_group_id, $this->post);

            if (($errors = $this->ModuleManager->errors())) {
                $this->setMessage('error', $errors);
            } else {
                $this->flashMessage('message', Language::_('AdminCompanyModules.!success.group_updated', true));
                $this->redirect($this->base_uri . 'settings/company/modules/manage/' . $module_id . '/');
            }

            $vars = (object) $this->post;
        }

        $module_rows = [];
        for ($i = 0; $i < count($module_info->rows); $i++) {
            // Assign to right hand side if not assigned to left hand side
            if (!isset($vars->module_rows[$module_info->rows[$i]->id])) {
                $module_rows[$module_info->rows[$i]->id] = $module_info->rows[$i]->meta->$module_row_key;
            }
        }


        // Set the module to the view
        $this->set('module', $module);
        $this->set('module_rows', $module_rows);
        $this->set('module_add_order', $module->getGroupOrderOptions());
        $this->set('vars', $vars);
        $this->structure->set(
            'page_title',
            Language::_('AdminCompanyModules.editgroup.page_title', true, $module->getName(), $module->moduleRowName())
        );
    }

    /**
     * Permanently removes the module group
     */
    public function deleteGroup()
    {
        $module_id = isset($this->post['id']) ? $this->post['id'] : null;
        $module_group_id = isset($this->post['group_id']) ? $this->post['group_id'] : null;

        $module_info = $this->requireModule($module_id);
        $module_group = $this->ModuleManager->getGroup($module_group_id);

        // Ensure module and module group exist
        if (!$module_info || !$module_group) {
            $this->redirect($this->base_uri . 'settings/company/modules/installed/');
        }

        $this->ModuleManager->deleteGroup($module_group_id);

        if (($errors = $this->ModuleManager->errors())) {
            $this->flashMessage('error', $errors);
        } else {
            $this->flashMessage('message', Language::_('AdminCompanyModules.!success.group_deleted', true));
        }

        $this->redirect($this->base_uri . 'settings/company/modules/manage/' . $module_id . '/');
    }

    /**
     * Add a module row
     */
    public function addRow()
    {
        $module_id = isset($this->get[0]) ? $this->get[0] : null;

        $module_info = $this->requireModule($module_id);

        // Ensure the module exists
        if (!$module_info) {
            $this->redirect($this->base_uri . 'settings/company/modules/installed/');
        }

        $this->components(['Modules']);

        $module = $this->Modules->create($module_info->class);
        $module->base_uri = $this->base_uri;

        if (!empty($this->post)) {
            $this->ModuleManager->addRow($module_id, $this->post);

            if (($errors = $this->ModuleManager->errors())) {
                $this->setMessage('error', $errors);
            } else {
                $this->flashMessage(
                    'message',
                    Language::_('AdminCompanyModules.!success.row_added', true, $module->moduleRowName())
                );
                $this->redirect($this->base_uri . 'settings/company/modules/manage/' . $module_id);
            }
        }
        $this->set('content', $module->manageAddRow($this->post));
        $this->structure->set(
            'page_title',
            Language::_('AdminCompanyModules.addrow.page_title', true, $module->getName(), $module->moduleRowName())
        );
    }

    /**
     * Edit a module row
     */
    public function editRow()
    {
        $module_id = isset($this->get[0]) ? $this->get[0] : null;
        $module_row_id = isset($this->get[1]) ? $this->get[1] : null;
        $module_info = $this->requireModule($module_id);

        $module_row = $this->ModuleManager->getRow($module_row_id);

        // Ensure module and module row exist
        if (!$module_info || !$module_row) {
            $this->redirect($this->base_uri . 'settings/company/modules/installed/');
        }

        $this->components(['Modules']);

        $module = $this->Modules->create($module_info->class);
        $module->base_uri = $this->base_uri;


        if (!empty($this->post)) {
            $this->ModuleManager->editRow($module_row_id, $this->post);

            if (($errors = $this->ModuleManager->errors())) {
                $this->setMessage('error', $errors);
            } else {
                $this->flashMessage(
                    'message',
                    Language::_('AdminCompanyModules.!success.row_updated', true, $module->moduleRowName())
                );
                $this->redirect($this->base_uri . 'settings/company/modules/manage/' . $module_id . '/');
            }
        }

        // Set the module to the view
        $this->set('content', $module->manageEditRow($module_row, $this->post));
        $this->structure->set(
            'page_title',
            Language::_('AdminCompanyModules.editrow.page_title', true, $module->getName(), $module->moduleRowName())
        );
    }

    /**
     * Delete a module row
     */
    public function deleteRow()
    {
        $module_id = isset($this->post['id']) ? $this->post['id'] : null;
        $module_row_id = isset($this->post['row_id']) ? $this->post['row_id'] : null;
        $module_info = $this->requireModule($module_id);

        $module_row = $this->ModuleManager->getRow($module_row_id);

        // Ensure module and module row exist
        if (!$module_info || !$module_row || $module_id != $module_row->module_id) {
            $this->redirect($this->base_uri . 'settings/company/modules/installed/');
        }

        $this->components(['Modules']);

        $module = $this->Modules->create($module_info->class);
        $module->base_uri = $this->base_uri;

        $this->ModuleManager->deleteRow($module_row_id);

        if (($errors = $this->ModuleManager->errors())) {
            $this->flashMessage('error', $errors);
        } else {
            $this->flashMessage(
                'message',
                Language::_('AdminCompanyModules.!success.row_deleted', true, $module->moduleRowName())
            );
        }

        $this->redirect($this->base_uri . 'settings/company/modules/manage/' . $module_id . '/');
    }

    /**
     * Require the module, redirects on error
     *
     * @param int $module_id The ID of the module to require
     */
    private function requireModule($module_id)
    {
        // Redirect if the module ID is invalid
        if (!($module_info = $this->ModuleManager->get($module_id))
            || ($module_info->company_id != $this->company_id)
        ) {
            $this->redirect($this->base_uri . 'settings/company/modules/installed/');
        }

        return $module_info;
    }
}
