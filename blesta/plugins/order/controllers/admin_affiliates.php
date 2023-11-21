<?php
/**
 * Admin Affiliates controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order.controllers
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminAffiliates extends OrderAffiliateController
{
    /**
     * Pre Action
     */
    public function preAction()
    {
        parent::preAction();

        $this->requireLogin();

        $this->uses([
            'Order.OrderAffiliates',
            'Order.OrderAffiliateCompanySettings'
        ]);

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);

        Language::loadLang('admin_affiliates', null, PLUGINDIR . 'order' . DS . 'language' . DS);
    }

    /**
     * List order affiliates
     */
    public function index()
    {
        $status = (isset($this->get[0]) ? $this->get[0] : 'active');
        $page = (isset($this->get[1]) ? (int)$this->get[1] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'client_id');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'asc');

        // Set the number of affiliates of each type
        $status_count = [
            'active' => $this->OrderAffiliates->getStatusCount('active', $this->company_id),
            'inactive' => $this->OrderAffiliates->getStatusCount('inactive', $this->company_id)
        ];

        $order_affiliates = $this->OrderAffiliates->getList(
            $this->company_id,
            $status,
            $page,
            [(strpos($sort, '.') >= 0 ? '' : 'order_affiliates.') . $sort => $order]
        );
        $total_results = $this->OrderAffiliates->getListCount($this->company_id, $status);

        if (isset($this->post['enable'])) {
            // Enable affiliate actions
            $this->enableAffiliateActions();

            // Enable the affiliate system setting
            $this->OrderAffiliateCompanySettings->setSetting(Configure::get('Blesta.company_id'), 'enabled', 'true');

            // Set success message for enabling the affiliate system
            $this->flashMessage(
                'message',
                Language::_('AdminAffiliates.!success.affilates_enabled', true),
                null,
                false
            );

            // Redirect to the general settings page
            $this->redirect($this->base_uri . 'plugin/order/admin_settings/');
        } elseif (empty($order_affiliates) && $status == 'active') {
            // Set message on how to disable affiliate system and actions
            $this->setMessage(
                'notice',
                Language::_('AdminAffiliates.index.disable_affiliates', true),
                false,
                null,
                false
            );
        }

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'plugin/order/admin_affiliates/index/' . $status . '/[p]/'
            ]
        );
        $this->setPagination($this->get, $settings);

        // Include WYSIWYG
        $this->Javascript->setFile('blesta/ckeditor/build/ckeditor.js', 'head', VENDORWEBDIR);

        $enabled_setting = $this->OrderAffiliateCompanySettings->getSetting(
            Configure::get('Blesta.company_id'),
            'enabled'
        );
        $this->set('affiliates_enabled', $enabled_setting ? $enabled_setting->value : 'false');
        $this->set('order_affiliates', $order_affiliates);
        $this->set('status', $status);
        $this->set('status_count', $status_count);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        return $this->renderAjaxWidgetIfAsync(isset($this->get[1]) || isset($this->get['sort']));
    }

    /**
     * Enables all actions for the affiliate system
     */
    private function enableAffiliateActions()
    {
        $this->uses(['PluginManager', 'Actions', 'Navigation']);

        // Get all actions for this plugin
        $plugin = $this->PluginManager->getByDir('order', Configure::get('Blesta.company_id'));
        $actions = $this->Actions->getAll(['plugin_id' => $plugin[0]->id], false);

        foreach ($actions as $action) {
            // Enable only affiliate actions
            if (str_contains($action->url, 'affiliate')) {
                $vars = [
                    'name' => $action->name,
                    'url' => $action->url,
                    'options' => $action->options,
                    'enabled' => '1'
                ];

                $nav_vars = ['action_id' => $action->id];
                if ($action->location == 'nav_staff') {
                    continue;
                }

                $this->Actions->edit($action->id, $vars);
                $this->Navigation->add($nav_vars);
            }
        }
    }

    /**
     * Add an order affiliate
     */
    public function add()
    {
        $this->uses(['Clients']);

        // If the client is given, make sure it exists and is not already an affiliate
        if (isset($this->get[0])) {
            if (!($client = $this->Clients->get((int)$this->get[0]))) {
                $this->redirect($this->base_uri . 'clients/');
            } elseif (($affiliate = $this->OrderAffiliates->getByClientId($this->get[0]))) {
                $this->redirect($this->base_uri . 'plugin/order/admin_main/affiliates/' . $affiliate->client_id);
            } else {
                $this->set('client', $client);
            }
        }

        $vars = new stdClass();

        if (!empty($this->post)) {
            $affiliate_id = $this->addAffiliate(isset($this->post['client_id']) ? $this->post['client_id'] : 0);

            if ($affiliate_id) {
                $this->flashMessage(
                    'message',
                    Language::_('AdminAffiliates.!success.affiliate_added', true),
                    null,
                    false
                );
                $this->redirect($this->base_uri . 'plugin/order/admin_main/affiliates/' . $this->post['client_id']);
            }

            $vars = (object)$this->post;
        }

        $this->set('statuses', $this->OrderAffiliates->getStatuses());
        $this->set('vars', $vars);
    }

    /**
     * AJAX Fetch clients when searching
     * @see AdminAffiliates::add()
     */
    public function getClients()
    {
        // Ensure there is post data
        if (!$this->isAjax() || empty($this->post['search'])) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $this->uses(['Clients']);
        $search = $this->post['search'];
        $clients = $this->Form->collapseObjectArray(
            $this->Clients->search($search),
            ['id_code', 'first_name', 'last_name'],
            'id',
            ' '
        );

        echo json_encode(['clients' => $clients]);
        return false;
    }

    /**
     * Activate affiliate
     */
    public function activate()
    {
        // Get affiliate or redirect if not given
        $affiliate_id = isset($this->get[0]) ? $this->get[0] : (isset($this->post['id']) ? $this->post['id'] : null);
        if (!($affiliate = $this->OrderAffiliates->get($affiliate_id))) {
            $this->redirect($this->base_uri . 'plugin/order/admin_affiliates/');
        }

        // Set affiliate status to active
        if (!empty($this->post)) {
            $this->OrderAffiliates->edit(
                $affiliate->id,
                ['status' => 'active']
            );

            $this->flashMessage('message', Language::_('AdminAffiliates.!success.affiliate_activated', true));
        }

        $this->redirect($this->base_uri . 'plugin/order/admin_affiliates/');
    }

    /**
     * Deactivate affiliate
     */
    public function deactivate()
    {
        // Get affiliate or redirect if not given
        $affiliate_id = isset($this->get[0]) ? $this->get[0] : (isset($this->post['id']) ? $this->post['id'] : null);
        if (!($affiliate = $this->OrderAffiliates->get($affiliate_id))) {
            $this->redirect($this->base_uri . 'plugin/order/admin_affiliates/');
        }

        // Set affiliate status to inactive
        if (!empty($this->post)) {
            $this->OrderAffiliates->edit(
                $affiliate->id,
                ['status' => 'inactive']
            );

            $this->flashMessage('message', Language::_('AdminAffiliates.!success.affiliate_deactivated', true));
        }

        $this->redirect($this->base_uri . 'plugin/order/admin_affiliates/');
    }
}
