<?php
/**
 * Admin Payment Methods controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order.controllers
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminPaymentMethods extends OrderAffiliateController
{
    /**
     * Pre Action
     */
    public function preAction()
    {
        parent::preAction();

        $this->requireLogin();

        $this->uses([
            'Order.OrderAffiliatePaymentMethods',
            'Languages'
        ]);

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);

        Language::loadLang('admin_payment_methods', null, PLUGINDIR . 'order' . DS . 'language' . DS);
    }

    /**
     * List payment methods
     */
    public function index()
    {
        $page = (isset($this->get[0]) ? (int)$this->get[0] : 1);

        // Get list of payment methods
        $payment_methods = $this->OrderAffiliatePaymentMethods->getList(
            $this->company_id,
            $page
        );
        $total_results = $this->OrderAffiliatePaymentMethods->getListCount($this->company_id);

        foreach ($payment_methods as $key => $payment_method) {
            $payment_method->name = '';

            foreach ($payment_method->names as $payment_method_name) {
                if ($payment_method_name->lang == Configure::get('Blesta.language')) {
                    $payment_method->name = $payment_method_name->name;
                }
            }

            $payment_methods[$key] = $payment_method;
        }

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'plugin/order/admin_affiliates/index/[p]/'
            ]
        );
        $this->setPagination($this->get, $settings);

        $this->set('payment_methods', $payment_methods);

        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]));
    }

    /**
     * Add payment method
     */
    public function add()
    {
        $vars = new stdClass();

        if (!empty($this->post)) {
            $params = [
                'names' => (isset($this->post['names']) ? $this->post['names'] : []),
                'company_id' => $this->company_id
            ];
            $this->OrderAffiliatePaymentMethods->add($params);

            if (($errors = $this->OrderAffiliatePaymentMethods->errors())) {
                // Error
                $this->setMessage('error', $errors, false, null, false);
                $vars = (object) $this->post;
            } else {
                // Success
                $this->flashMessage('message', Language::_('AdminPaymentMethods.!success.payment_method_added', true), null, false);
                $this->redirect($this->base_uri . 'plugin/order/admin_payment_methods/');
            }
        }

        $this->set('languages', $this->Languages->getAll($this->company_id));
        $this->set('vars', $vars);
    }

    /**
     * Edit a payment method
     */
    public function edit()
    {
        // If the payment method is given, make sure it exists
        if (!isset($this->get[0]) || !($payment_method = $this->OrderAffiliatePaymentMethods->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'plugin/order/admin_payment_methods/');
        }

        $vars = $payment_method;

        if (!empty($this->post)) {
            $params = [
                'names' => (isset($this->post['names']) ? $this->post['names'] : []),
                'company_id' => $this->company_id
            ];
            $this->OrderAffiliatePaymentMethods->edit($payment_method->id, $params);

            if (($errors = $this->OrderAffiliatePaymentMethods->errors())) {
                // Error
                $this->setMessage('error', $errors, false, null, false);
                $vars = (object) $this->post;
            } else {
                // Success
                $this->flashMessage('message', Language::_('AdminPaymentMethods.!success.payment_method_updated', true), null, false);
                $this->redirect($this->base_uri . 'plugin/order/admin_payment_methods/');
            }
        }

        $this->set('languages', $this->Languages->getAll($this->company_id));
        $this->set('vars', $vars);
    }

    /**
     * Edit a payment method
     */
    public function delete()
    {
        // If the payment method is given, make sure it exists
        $payment_method_id = isset($this->get[0]) ? $this->get[0] : (isset($this->post['id']) ? $this->post['id'] : null);
        if (!($payment_method = $this->OrderAffiliatePaymentMethods->get((int)$payment_method_id))) {
            $this->redirect($this->base_uri . 'plugin/order/admin_payment_methods/');
        }

        // Delete the payment method
        if (!empty($this->post)) {
            $this->OrderAffiliatePaymentMethods->delete($payment_method->id);

            $this->flashMessage('message', Language::_('AdminPaymentMethods.!success.payment_method_deleted', true));
        }

        $this->redirect($this->base_uri . 'plugin/order/admin_payment_methods/');
    }
}
