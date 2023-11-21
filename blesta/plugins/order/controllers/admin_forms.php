<?php
use Blesta\Core\Util\Input\Fields\Html as FieldsHtml;

/**
 * Order Forms controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminForms extends OrderController
{
    /**
     * @var string The base Order URL
     */
    private $base_order_url;

    /**
     * Pre Action
     */
    public function preAction()
    {
        parent::preAction();

        $this->requireLogin();

        $this->uses(['Order.OrderForms', 'ClientGroups', 'Companies', 'EmailGroups']);

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);

        $company = $this->Companies->get($this->company_id);

        $this->base_order_url = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '') .
            '://' . $company->hostname . WEBDIR . 'order/main/index/';

        Language::loadLang('admin_forms', null, PLUGINDIR . 'order' . DS . 'language' . DS);
    }

    /**
     * List order forms
     */
    public function index()
    {
        $page = (isset($this->get[0]) ? (int)$this->get[0] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'order');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'asc');

        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        $order_forms = $this->OrderForms->getList($this->company_id, $page, [$sort => $order]);
        $total_results = $this->OrderForms->getListCount($this->company_id);

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'plugin/order/admin_forms/index/[p]/'
            ]
        );
        $this->setPagination($this->get, $settings);

        $this->set('templates', $this->OrderForms->getTemplates());
        $this->set('types', $this->OrderForms->getTypes());
        $this->set('order_forms', $order_forms);
        $this->set('base_order_url', $this->base_order_url);

        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));
    }

    /**
     * Add an order form
     */
    public function add()
    {
        $vars = new stdClass();
        $vars->template = 'wizard';
        $vars->template_style = 'boxes';

        if (!empty($this->post)) {
            // Handle checkboxes that are unchecked
            $checkbox_fields = [
                'allow_coupons', 'manual_review', 'require_ssl',
                'require_captcha', 'require_tos', 'inactive_after_cancellation'
            ];
            foreach ($checkbox_fields as $checkbox_field) {
                if (!array_key_exists($checkbox_field, $this->post)) {
                    $this->post[$checkbox_field] = '0';
                }
            }

            // Add the order form
            $this->OrderForms->add($this->post);

            if (!($errors = $this->OrderForms->errors())) {
                $this->flashMessage('message', Language::_('AdminForms.!success.form_added', true), null, false);
                $this->redirect($this->base_uri . 'plugin/order/admin_forms/');
            }

            $vars = (object)$this->post;
            $this->setMessage('error', $errors, false, null, false);
        }

        $templates = $this->OrderForms->getTemplates();

        $this->set('types', $this->OrderForms->getTypes());
        $this->set('visibility', $this->OrderForms->getVisibilities());
        $this->set('client_groups', $this->ClientGroups->getAll($this->company_id));
        $this->set(
            'abandoned_cart_days',
            ['' => Language::_('AdminForms.select.never', true)] +
            $this->OrderForms->getNoticeIntervals(30)
        );
        $this->set('templates', $templates);
        $this->set('base_order_url', $this->base_order_url);
        $this->set('vars', $vars);
        $this->set('first_reminder_template', $this->EmailGroups->getByAction('Order.abandoned_cart_first'));
        $this->set('second_reminder_template', $this->EmailGroups->getByAction('Order.abandoned_cart_second'));
        $this->set('third_reminder_template', $this->EmailGroups->getByAction('Order.abandoned_cart_third'));
    }

    /**
     * Edit an order form
     */
    public function edit()
    {
        // Ensure order form exists
        if (!isset($this->get[0])
            || !($order_form = $this->OrderForms->get($this->get[0], ['restrict_groups' => false]))
            || $order_form->company_id != $this->company_id) {
            $this->redirect($this->base_uri . 'plugin/order/admin_forms/');
        }

        $vars = $order_form;

        if (!empty($this->post)) {
            // Handle checkboxes that are unchecked
            $checkbox_fields = [
                'allow_coupons', 'manual_review', 'require_ssl',
                'require_captcha', 'require_tos', 'inactive_after_cancellation'
            ];
            foreach ($checkbox_fields as $checkbox_field) {
                if (!array_key_exists($checkbox_field, $this->post)) {
                    $this->post[$checkbox_field] = '0';
                }
            }

            if (!array_key_exists('currencies', $this->post)) {
                $this->post['currencies'] = [];
            }
            if (!array_key_exists('gateways', $this->post)) {
                $this->post['gateways'] = [];
            }
            if (!array_key_exists('groups', $this->post)) {
                $this->post['groups'] = [];
            }
            if (!array_key_exists('meta', $this->post)) {
                $this->post['meta'] = [];
            }

            // Edit the order form
            $this->OrderForms->edit($order_form->id, $this->post);

            if (!($errors = $this->OrderForms->errors())) {
                $this->flashMessage('message', Language::_('AdminForms.!success.form_edited', true), null, false);
                $this->redirect($this->base_uri . 'plugin/order/admin_forms/edit/' . $order_form->id);
            }

            $vars = (object)$this->post;
            $this->setMessage('error', $errors, false, null, false);
        }

        $templates = $this->OrderForms->getTemplates();

        $this->set('types', $this->OrderForms->getTypes());
        $this->set('visibility', $this->OrderForms->getVisibilities());
        $this->set('client_groups', $this->ClientGroups->getAll($this->company_id));
        $this->set(
            'abandoned_cart_days',
            ['' => Language::_('AdminForms.select.never', true)] +
            $this->OrderForms->getNoticeIntervals(30)
        );
        $this->set('templates', $templates);
        $this->set('base_order_url', $this->base_order_url);
        $this->set('vars', $vars);
        $this->set('first_reminder_template', $this->EmailGroups->getByAction('Order.abandoned_cart_first'));
        $this->set('second_reminder_template', $this->EmailGroups->getByAction('Order.abandoned_cart_second'));
        $this->set('third_reminder_template', $this->EmailGroups->getByAction('Order.abandoned_cart_third'));
    }

    /**
     * Sets an order form to inactive
     */
    public function delete()
    {
        if (isset($this->post['id'])) {
            $this->OrderForms->delete($this->post['id']);

            if (($errors = $this->OrderForms->errors())) {
                $this->flashMessage('error', $errors, null, false);
            } else {
                $this->flashMessage('message', Language::_('AdminForms.!success.form_delete', true), null, false);
            }
        }

        $this->redirect($this->base_uri . 'plugin/order/admin_forms/');
    }

    /**
     * Returns the order form meta fields and package group sections in JSON format
     */
    public function meta()
    {
        $vars = (object)$this->post;

        $order_type = $this->OrderForms->loadOrderType(isset($this->post['type']) ? $this->post['type'] : 'general');
        $multi_group = $order_type->supportsMultipleGroups();
        $meta_fields = $order_type->getSettings($this->post);
        $gateways = null;
        $currencies = null;

        $package_groups = $this->availablePackageGroups($vars, $multi_group);

        if ($order_type->supportsPayments()) {
            $gateways = $this->getAvailableGateways();
            $currencies = $this->getAvailableCurrencies();
        }

        $this->outputAsJson(
            [
                'content' => $this->partial(
                    'admin_forms_meta',
                    compact('multi_group', 'meta_fields', 'vars', 'package_groups', 'gateways', 'currencies')
                )
            ]
        );
        return false;
    }

    /**
     * Fetch all available package groups
     *
     * @param array $var An array of input vars
     * @param bool $multi_group True if the order type supports multiple package groups, false otherwise
     */
    private function availablePackageGroups(&$vars, $multi_group = true)
    {
        $this->uses(['Packages']);

        // Fetch all available package groups
        $package_groups = $this->Form->collapseObjectArray(
            $this->Packages->getAllGroups($this->company_id, null, 'standard'),
            'name',
            'id'
        );

        // Set all selected package groups in assigned and unset all selected groups from available
        if (isset($vars->groups) && is_array($vars->groups)) {
            $selected = [];

            foreach ($vars->groups as $id) {
                if (array_key_exists($id, $package_groups)) {
                    $selected[$id] = $package_groups[$id];
                    if ($multi_group) {
                        unset($package_groups[$id]);
                    }
                }
            }

            $vars->groups = $selected;
        }

        return $package_groups;
    }

    /**
     * Gets all available currencies
     *
     * @return array An array of stdClass objects representing currencies
     */
    private function getAvailableCurrencies()
    {
        $this->uses(['Currencies']);

        return $this->Currencies->getAll($this->company_id);
    }

    /**
     * Gets all available gateways
     *
     * @return array An array of stdClass objects representing gateways
     */
    private function getAvailableGateways()
    {
        $this->uses(['GatewayManager']);

        return $this->GatewayManager->getAll($this->company_id);
    }

    /**
     * Set the order for order forms
     */
    public function sortorderforms()
    {
        if (!$this->isAjax()) {
            $this->redirect($this->base_uri . 'plugin/order/admin_forms/');
        }

        if (!empty($this->post)) {
            $this->OrderForms->sortOrderForms($this->post['order_forms']);
        }
        return false;
    }

    /**
     * Update order form settings
     */
    public function settings()
    {
        $this->uses(['Order.OrderSettings']);
        $this->helpers(['DataStructure']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        $vars = $this->ArrayHelper->numericToKey($this->OrderSettings->getSettings($this->company_id), 'key', 'value');

        if (!empty($this->post)) {
            // Set unchecked checkboxes
            $checkboxes = [
                'payments_allowed_cc', 'payments_allowed_ach', 'antifraud_after_validate',
                'hold_unverified_orders'
            ];
            foreach ($checkboxes as $checkbox) {
                if (!isset($this->post[$checkbox])) {
                    $this->post[$checkbox] = 'false';
                }
            }

            $this->OrderSettings->setSettings($this->company_id, $this->post);

            if (($errors = $this->OrderSettings->errors())) {
                $vars = $this->post;
                $this->setMessage('error', $errors, false, null, false);
            } else {
                $this->flashMessage('message', Language::_('AdminForms.!success.settings_saved', true), null, false);
                $this->redirect($this->base_uri . 'plugin/order/admin_forms/');
            }
        }

        $this->set('vars', $vars);
        $this->set('order_forms', $this->OrderForms->getAll($this->company_id, 'active', ['name' => 'asc']));
        $this->set('antifraud', $this->OrderSettings->getAntifraud());
        $this->set('tags', $this->getEmbedCodeTags());
        $this->setDefaultSettings($vars);
    }

    /**
     * Sets variables to the view for default company settings unless they are already available in $vars
     * @see AdminForms::settings
     *
     * @param array $vars An array of input vars
     */
    private function setDefaultSettings(array $vars)
    {
        $settings = ['payments_allowed_cc', 'payments_allowed_ach'];
        foreach ($settings as $setting) {
            // No need to query for the company default setting if we already have a value for it in vars
            if (isset($vars[$setting])) {
                continue;
            }

            $value = $this->Companies->getSetting($this->company_id, $setting);
            $this->set($setting, ($value && isset($value->value) ? $value->value : 'false'));
        }
    }

    /**
     * Return antifraud settings for the given antifraud type
     */
    public function antifraudSettings()
    {
        $this->uses(['Order.OrderSettings']);
        $this->helpers(['DataStructure']);
        $this->ArrayHelper = $this->DataStructure->create('Array');
        $this->components(['Order.Antifraud']);

        $settings = $this->ArrayHelper->numericToKey(
            $this->OrderSettings->getSettings($this->company_id),
            'key',
            'value'
        );

        try {
            if (!$this->isAjax() || !isset($this->get[0])
                || !($antifraud = $this->Antifraud->create($this->get[0], [$settings]))) {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }

        $fields_html = new FieldsHtml($antifraud->getSettingFields((object)array_merge($settings, $this->post)));
        echo $this->partial('admin_forms_antifraudsettings', compact('fields_html'));

        return false;
    }

    /**
     * Gets a list of tags for order form embed code
     *
     * @return array A list of tags available to order for embed code
     */
    private function getEmbedCodeTags()
    {
        return [
            '{{order_page}}',
            '{{currency}}',
            '{{products}}',
            '{{order.id}}',
            '{{order.order_number}}',
            '{{invoice.id_code}}',
            '{{invoice.total}}'
        ];
    }
}
