<?php
/**
 * Client portal managers controller
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientManagers extends ClientController
{
    /**
     * Pre action
     */
    public function preAction()
    {
        parent::preAction();

        $this->uses(['ManagedAccounts', 'Contacts', 'Companies', 'Clients']);

        // Check if the current session is a manager or a primary contact
        $is_manager = !empty($this->Session->read('blesta_contact_id'));

        // Prevent access to the manager when managing another account
        if (($this->action ?? 'index') !== 'switch') {
            if ($is_manager) {
                // We deny access to all managers, even if they have the "_managed" permission
                // to avoid multi-level sessions
                $this->setMessage(
                    'error',
                    Language::_('AppController.!error.unauthorized_access', true),
                    false,
                    null,
                    false
                );
                $this->render('unauthorized', Configure::get('System.default_view'));
                exit();
            } else {
                $this->requirePermission('_managed');
            }
        }
    }

    /**
     * List managers
     */
    public function index()
    {
        // Set sort and order
        $page = (int) ($this->get[0] ?? 1);
        $order = ($this->get['order'] ?? 'asc');
        $sort = ($this->get['sort'] ?? 'id_value');

        // Fetch managers
        $managers = $this->ManagedAccounts->getManagersList($this->client->id, $page, [$sort => $order]);

        $this->set('managers', $managers);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination_client'),
            [
                'total_results' => $this->ManagedAccounts->getManagersListCount($this->client->id),
                'uri' => $this->base_uri . 'managers/index/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(isset($this->get['whole_widget']) ? null : isset($this->get['sort']));
        }
        $this->set('navigation', $this->partial('client_managers_navigation'));
    }

    /**
     * Invites a new manager
     */
    public function add()
    {
        $vars = new stdClass();

        // Get all possible permissions
        $permission_options = $this->ManagedAccounts->getPermissionOptions($this->company_id);

        // Filter out any to which this user does not have access
        foreach ($permission_options as $key => $permission_option) {
            if (!$this->hasPermission($key)) {
                unset($permission_options[$key]);
            }
        }

        // Invite new manager
        if (!empty($this->post)) {
            $email = $this->post['email'] ?? null;
            $permissions = [];
            if (isset($this->post['permissions'])) {
                $this->post['permissions']['area'] =  array_intersect(
                    array_keys($permission_options),
                    $this->post['permissions']['area']
                );
                $permissions = $this->post['permissions'];
            }

            $this->ManagedAccounts->begin();

            // Create the invitation
            $this->ManagedAccounts->invite($this->client->id, $email, $permissions);
            $errors = $this->ManagedAccounts->errors();

            if (!empty($errors)) {
                $this->ManagedAccounts->rollback();

                // Error, reset vars
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                $this->ManagedAccounts->commit();

                // Success
                $this->flashMessage('message', Language::_('ClientManagers.!success.manager_invited', true));
                $this->redirect($this->base_uri . 'managers/');
            }
        }

        $this->set('vars', $vars);
        $this->set('company', $this->Companies->get($this->company_id));
        $this->set('permissions', $permission_options);
        $this->set('navigation', $this->partial('client_managers_navigation'));
    }

    /**
     * Edits a manager
     */
    public function edit()
    {
        $vars = new stdClass();
        $contact_id = $this->get[0] ?? null;

        // Ensure a valid contact was given
        if (!($contact = $this->Contacts->get((int) $contact_id))
            || ($contact->client_id == $this->client->id)) {
            $this->redirect($this->base_uri . 'managers/');
        }

        // Get all possible permissions
        $permission_options = $this->ManagedAccounts->getPermissionOptions($this->company_id);

        // Filter out any to which this user does not have access
        foreach ($permission_options as $key => $permission_option) {
            if (!$this->hasPermission($key)) {
                unset($permission_options[$key]);
            }
        }

        // Get manager
        $vars = $this->ManagedAccounts->getManager($contact_id, $this->client->id);

        // Update manager
        if (!empty($this->post)) {
            $this->ManagedAccounts->begin();

            // Set permissions
            if (isset($this->post['permissions'])) {
                $this->post['permissions']['area'] = array_intersect(
                    array_keys($permission_options),
                    $this->post['permissions']['area']
                );
            }
            $this->ManagedAccounts->setPermissions($contact_id, $this->client->id, $this->post['permissions'] ?? []);
            $errors = $this->ManagedAccounts->errors();

            if (!empty($errors)) {
                $this->ManagedAccounts->rollback();

                // Error, reset vars
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                $this->ManagedAccounts->commit();

                // Success
                $this->flashMessage('message', Language::_('ClientManagers.!success.manager_updated', true));
                $this->redirect($this->base_uri . 'managers/');
            }
        }

        $this->set('vars', $vars);
        $this->set('permissions', $permission_options);
        $this->set('navigation', $this->partial('client_managers_navigation'));
    }

    /**
     * Revoke a manager
     */
    public function revoke()
    {
        // Ensure a valid contact or token was given
        if (empty($this->post['token'])) {
            $this->redirect($this->base_uri . 'managers/');
        }

        if (!is_numeric($this->post['token'])) {
            $invitation = $this->ManagedAccounts->getInvitationByToken($this->post['token']);
        } else {
            $contact = $this->Contacts->get((int) $this->post['token']);
        }

        if ((isset($invitation->client_id) && !($invitation->client_id == $this->client->id))
            || (isset($contact->client_id) && ($contact->client_id == $this->client->id))
        ) {
            $this->redirect($this->base_uri . 'managers/');
        }

        if (!isset($invitation) && !isset($contact)) {
            $this->redirect($this->base_uri . 'managers/');
        }

        // Attempt to revoke the manager or decline the invitation
        if (isset($contact)) {
            $this->ManagedAccounts->revoke($contact->id, $this->client->id);
        } else if (isset($invitation)) {
            $this->ManagedAccounts->decline($invitation->token);
        }

        if (($errors = $this->ManagedAccounts->errors())) {
            $this->flashMessage('error', $errors);
        } else {
            $this->flashMessage(
                'message',
                Language::_('ClientManagers.!success.manager_revoked', true)
            );
        }

        $this->redirect($this->base_uri . 'managers/');
    }

    /**
     * List accounts
     */
    public function accounts()
    {
        // Set sort and order
        $page = (int) ($this->get[0] ?? 1);
        $order = ($this->get['order'] ?? 'asc');
        $sort = ($this->get['sort'] ?? 'id_value');

        // Fetch managed accounts
        $accounts = $this->ManagedAccounts->getList($this->client->id, $page, [$sort => $order]);

        $this->set('accounts', $accounts);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination_client'),
            [
                'total_results' => $this->ManagedAccounts->getListCount($this->client->id),
                'uri' => $this->base_uri . 'managers/accounts/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(isset($this->get['whole_widget']) ? null : isset($this->get['sort']));
        }
        $this->set('navigation', $this->partial('client_managers_navigation'));
    }

    /**
     * Give up access for a managed account
     */
    public function give()
    {
        // Ensure a valid contact was given
        if (!isset($this->post['client_id'])
            || !($client = $this->Clients->get((int) $this->post['client_id']))
            || ($client->id == $this->client->id)) {
            $this->redirect($this->base_uri . 'managers/accounts/');
        }

        // Attempt to give up access
        $this->ManagedAccounts->revoke($this->client->contact_id, $client->id);

        if (($errors = $this->ManagedAccounts->errors())) {
            $this->flashMessage('error', $errors);
        } else {
            $this->flashMessage(
                'message',
                Language::_('ClientManagers.!success.account_gave_up', true, $client->email)
            );
        }

        $this->redirect($this->base_uri . 'managers/accounts/');
    }

    /**
     * Accept a management invitation
     */
    public function invite()
    {
        $token = ($this->get['token'] ?? $this->post['token'] ?? '');

        // Ensure a valid token was given
        if (empty($token)
            || !($invitation = $this->ManagedAccounts->getInvitationByToken($token))
            || ($invitation->client_id == $this->client->id)
            || (strtolower($this->client->email) !== strtolower($invitation->email))
        ) {
            $this->redirect($this->base_uri);
        }

        // Get client to manage
        $managed_client = $this->Clients->get($invitation->client_id);

        // Process invitation
        if (!empty($this->post)) {
            $action = $this->post['action'] ?? null;

            if ($action == 'accept') {
                $this->ManagedAccounts->accept($invitation->id);

                if (($errors = $this->ManagedAccounts->errors())) {
                    $this->setMessage('error', $errors);
                } else {
                    $this->flashMessage(
                        'message',
                        Language::_('ClientManagers.!success.invitation_accepted', true)
                    );
                    $this->redirect($this->base_uri . 'managers/accounts/');
                }
            } elseif ($action == 'decline') {
                $this->ManagedAccounts->decline($invitation->id);

                if (($errors = $this->ManagedAccounts->errors())) {
                    $this->setMessage('error', $errors);
                } else {
                    $this->flashMessage(
                        'message',
                        Language::_('ClientManagers.!success.invitation_declined', true)
                    );
                    $this->redirect($this->base_uri . 'managers/accounts/');
                }
            }
        }

        $this->set('managed_client', $managed_client);
        $this->set('invitation', $invitation);
    }

    /**
     * Switches session to a managed account
     */
    public function manage()
    {
        $managing_client_id = ($this->get[0] ?? $this->post['client_id'] ?? '');

        // Ensure a valid client id was given
        if (empty($managing_client_id)
            || !($managing_client = $this->Clients->get($managing_client_id))
            || ($managing_client_id == $this->client->id)
        ) {
            $this->redirect($this->base_uri);
        }

        // Get current client
        $client = $this->Clients->get($this->client->id);

        // Manage client
        $this->ManagedAccounts->manage($client->contact_id, $managing_client_id);

        $this->redirect($this->client_uri);
    }

    /**
     * Switches back the session to the manager account
     */
    public function switch()
    {
        $contact_id = $this->Session->read('blesta_contact_id');
        $client_id = $this->client->id;

        // Ensure a valid client id was given
        if (empty($contact_id)
            || !($contact = $this->Contacts->get($contact_id))
        ) {
            $this->redirect($this->base_uri);
        }

        // Manage client
        $this->ManagedAccounts->switchBack($contact_id, $client_id);

        $this->redirect($this->client_uri);
    }
}
