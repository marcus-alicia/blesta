<?php
/**
 * Client Parent Controller
 *
 * @package blesta
 * @subpackage blesta.app
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientController extends AppController
{
    /**
     * Pre action
     */
    public function preAction()
    {
        parent::preAction();

        $class_name = get_class($this);
        Language::loadLang(Loader::fromCamelCase($class_name));

        // Allow states and dialog to be fetched without login
        if (($class_name == 'ClientMain' && (in_array(strtolower($this->action), ['getstates', 'setlanguage'])))
            || $class_name == 'ClientDialog'
        ) {
            return;
        }

        // Require login
        $this->requireLogin();

        // Attempt to set the page title language
        try {
            $language = Language::_(
                $class_name . '.' . Loader::fromCamelCase($this->action ? $this->action : 'index') . '.page_title',
                true,
                $this->client->id_code
            );
            $this->structure->set('page_title', $language);
        } catch (Exception $e) {
            // Attempting to set the page title language has failed, likely due to
            // the language definition requiring multiple parameters.
            // Fallback to index. Assume the specific page will set its own page title otherwise.
            $this->structure->set(
                'page_title',
                Language::_($class_name . '.index.page_title', true, $this->client->id_code)
            );
        }
    }

    /**
     * Checks whether the current user is a staff user and whether the user is
     * currently logged into the client portal.
     *
     * @return bool True if the user is a staff user logged in as a client, false otherwise
     */
    protected function isStaffAsClient()
    {
        return (isset($this->Session) && $this->Session->read('blesta_staff_id') > 0);
    }

    /**
     * Sets the primary and secondary navigation links. Performs authorization checks on each navigational element.
     * May cache nav results if possible for better performance.
     */
    protected function setNav()
    {
        $nav = [];

        $this->uses(['Navigation']);
        $this->Navigation->baseUri('public', $this->public_uri)
            ->baseUri('client', $this->client_uri)
            ->baseUri('admin', $this->admin_uri);

        $nav = $this->setNavActive($this->Navigation->getPrimaryClient($this->client_uri));

        $this->structure->set('nav', $nav);
    }

    /**
     * {@inheritdoc}
     */
    protected function requireLogin($redirect_to = null)
    {
        parent::requireLogin($redirect_to);

        $area = $this->plugin ? $this->plugin . '.*' : $this->controller;
        $this->requirePermission($area);
    }

    /**
     * Verifies permissions for the given generic $area
     *
     * @param string $area The generic area
     */
    protected function requirePermission($area)
    {
        $allowed = $this->hasPermission($area);

        if (!$allowed) {
            if ($this->isAjax()) {
                // If ajax, send 403 response, user not granted access
                header($this->server_protocol . ' 403 Forbidden');
                exit();
            }

            $this->setMessage(
                'error',
                Language::_('AppController.!error.unauthorized_access', true),
                false,
                null,
                false
            );
            $this->render('unauthorized', Configure::get('System.default_view'));
            exit();
        }
    }

    /**
     * Verifies if the current user has permission to the given area
     *
     * @param string $area The generic area
     * @return bool True if user has permission, false otherwise
     */
    protected function hasPermission($area)
    {
        $this->portal = 'client';

        return parent::hasPermission($area);
    }

    /**
     * Verifies that the currently logged in user is authorized for the given Controller
     * and Action (or current Controller/Action if none given).
     * Will first check whether the Controller and Action is a permission value, and if so, checks
     * to ensure the staff or client group user is authorized to access that resource
     *
     * @param string $controller The controller to check authorization on, null will default to the current controller
     * @param string $action The action to check authorization on, null will default to the current action
     * @param stdClass $group The staff or client group to check authorization on,
     *  null will fetch the group of the current user
     * @return bool Returns true if the user is authorized for that resource, false otherwise
     */
    protected function authorized($controller = null, $action = null, $group = null)
    {
        $prefix = null;
        // Alias for plugin controllers is plugin.controller
        if ($this->plugin && $controller === null) {
            $prefix = $this->plugin . '.';
        }
        $controller = $prefix . ($controller === null ? $this->controller : $controller);
        $action = ($action === null ? $this->action : $action);

        if ($this->Session->read('blesta_client_id') > 0) {
            if (!isset($this->client)) {
                if (!isset($this->Clients)) {
                    $this->uses(['Clients']);
                }

                // Staff as Client
                if ($this->Session->read('blesta_staff_id') || $this->Session->read('blesta_client_id')) {
                    $client = $this->Clients->get($this->Session->read('blesta_client_id'), true);
                } else {
                    // Contact/Client
                    $client = $this->Clients->getByUserId($this->Session->read('blesta_id'), true);
                }

                if (!$client || $client->status != 'active') {
                    $this->Session->clear();
                    return false;
                }
                $this->client = $client;
            }

            return $this->hasPermission($controller);
        }
        return false;
    }
}
