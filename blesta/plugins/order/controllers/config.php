<?php
use Blesta\Core\Util\Input\Fields\Html as FieldsHtml;
use Blesta\Core\Util\PackageOptions\Logic as OptionLogic;

/**
 * Order System configuration controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Config extends OrderFormController
{
    /**
     * Set temp coupon if given
     */
    public function preAction()
    {
        parent::preAction();

        // If a coupon was given, save it for use later
        if (!empty($this->get['coupon']) && $this->order_form->allow_coupons == '1') {
            $this->SessionCart->setData('temp_coupon', $this->get['coupon']);
        }
    }

    /**
     * Configure the service
     */
    public function index()
    {
        $this->uses(['ModuleManager', 'PackageOptionConditionSets', 'Packages']);
        $this->helpers(['DataStructure']);

        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Obtain the pricing ID and package group ID of the item to order
        $item = null;

        // Flag whether the item came from the queue
        $queue_index = null;

        // Check if the domain hasn't been already added to the cart
        if ($this->order_form->type == 'domain' && !isset($this->get['item'])) {
            $items = $this->SessionCart->getData('items');
            $cart_domains = [];

            foreach ($items as $item) {
                if (isset($item['domain'])) {
                    $cart_domains[] = $item['domain'];
                }
            }

            $queued_domains = $this->SessionCart->getData('queue');
            $queued_domains = !empty($queued_domains) ? $queued_domains : [];
            $domains = [];

            foreach ($queued_domains as $domain) {
                if (isset($domain['domain'])) {
                    $domains[$domain['domain']] = $domain['domain'];
                }
            }

            foreach ($domains as $domain => $package) {
                if (in_array($domain, $cart_domains)) {
                    foreach ($queued_domains as $index => $queued_domain) {
                        if ($queued_domain['domain'] == $domain) {
                            $this->SessionCart->dequeue($index);
                        }
                    }

                    $this->flashMessage('error', Language::_('Config.!error.domain_duplicated', true, $domain));
                    $next_uri = $this->base_uri . 'order/cart/index/' . $this->order_form->label;
                    $this->redirect($next_uri);
                }

                if (
                    method_exists($this->order_type, 'validateTerm')
                    && isset($this->post['pricing_id'][$domain])
                    && !$this->order_type->validateTerm(
                        $domain,
                        $this->post['pricing_id'][$domain],
                        $this->post['meta'][$domain]['transfer'] ?? 'false'
                    )
                    && in_array(
                        $domain,
                        $this->ArrayHelper->numericToKey($queued_domains, 'domain', 'domain')
                    )
                ) {
                    foreach ($queued_domains as $index => $queued_domain) {
                        if ($queued_domain['domain'] == $domain) {
                            $this->SessionCart->dequeue($index);
                        }
                    }

                    $this->flashMessage('error', Language::_('Config.!error.invalid_domain_term', true, $domain));
                    $next_uri = $this->base_uri . 'order/config/preconfig/' . $this->order_form->label;
                    $this->redirect($next_uri);
                }
            }
        }

        // Handle multiple items
        if (isset($this->post['pricing_id']) && is_array($this->post['pricing_id'])
            && isset($this->post['group_id']) && is_array($this->post['group_id'])
            && (!method_exists($this->order_type, 'validateItems') || $this->order_type->validateItems($this->post))
        ) {
            $vars = $this->post;
            unset($vars['pricing_id'], $vars['group_id']);

            foreach ($this->post['pricing_id'] as $key => $pricing_id) {
                $item = [
                    'pricing_id' => $pricing_id,
                    'group_id' => $this->post['group_id'][$key]
                ];

                if (isset($this->post['meta'][$key])) {
                    $item = array_merge($item, $this->post['meta'][$key]);
                }
                $index = $this->SessionCart->enqueue($item);

                if ($queue_index === null) {
                    $queue_index = $index;
                }
            }

            // Redirect to configure the first queued item
            if (!$this->isAjax()) {
                $this->redirect(
                    $this->base_uri . 'order/config/index/' . $this->order_form->label . '/?q_item=' . $queue_index
                );
            }
        } elseif (isset($this->get['item'])) {
            // Fetch the item from the cart if it already exists (allows editing existing item in cart)
            $item = $this->SessionCart->getItem($this->get['item']);
        } elseif (isset($this->post['pricing_id']) && isset($this->post['group_id']) && !isset($this->get['q_item'])) {
            // Handle single item
            $item = $this->SessionCart->prequeueItem($this->post);
        } elseif (isset($this->get['pricing_id']) && isset($this->get['group_id'])) {
            $item = $this->SessionCart->prequeueItem($this->get);
        } else {
            $queue_index = isset($this->get['q_item']) ? $this->get['q_item'] : 0;
        }

        // Fetch an item from the queue
        if ($queue_index !== null) {
            $item = $this->SessionCart->checkQueue($queue_index);
        }

        // Ensure we have an item
        if (!$item) {
            $this->handleError(
                Language::_('Config.!error.invalid_pricing_id', true),
                $this->base_uri . 'order/main/index/' . $this->order_form->label
            );
        }

        // If not a valid item, redirect away and set error
        if (!$this->isValidItem($item)) {
            if ($queue_index) {
                $this->SessionCart->dequeue($queue_index);
            }

            $this->handleError(
                Language::_('Config.!error.invalid_pricing_id', true),
                $this->base_uri . 'order/main/index/' . $this->order_form->label
            );
        }

        $currency = $this->SessionCart->getData('currency');

        $package = $this->updatePackagePricing($this->Packages->getByPricingId($item['pricing_id']), $currency);

        $module = $this->ModuleManager->initModule($package->module_id, $this->company_id);

        // Ensure a valid module
        if (!$module) {
            $this->handleError(
                Language::_('Config.!error.invalid_module', true),
                $this->base_uri . 'order/main/index/' . $this->order_form->label
            );
        } else {
            $module->base_uri = $this->base_uri;
        }

        $vars = (object)$item;

        // Get all add-on groups (child "addon" groups for this package group)
        // And all packages in the group
        $addon_groups = $this->Packages->getAllAddonGroups($item['group_id']);

        foreach ($addon_groups as &$addon_group) {
            // Fetch all active packages for this group
            $packages = $this->Packages->getAllPackagesByGroup($addon_group->id, 'active', ['hidden' => true]);

            // Add restricted packages allowed for this user
            $client_packages = [];
            foreach (
                $this->Clients->getRestrictedPackages($this->Session->read('blesta_client_id')) as $client_package
            ) {
                $client_packages[] = $client_package->package_id;
            }

            $restricted_packages = $this->Packages->getAllPackagesByGroup($addon_group->id, 'restricted', ['hidden' => true]);
            foreach ($restricted_packages as $restricted_package) {
                if (in_array($restricted_package->id, $client_packages)) {
                    $packages[] = $restricted_package;
                }
            }

            $addon_group->packages = $this->updatePackagePricing($packages, $currency);
        }

        // Get service fields and module row groups
        $service_fields = $module->getClientAddFields($package, $vars);

        // If the client can select the module group, show a dropdown with the available options
        if (($package->module_group_client ?? '0') == '1') {
            $module_groups = $this->Form->collapseObjectArray($package->module_groups, 'name', 'id');
            $module_group_id = $service_fields->label(
                $module->moduleGroupName() ?? Language::_('Config.index.field_module_group_id', true),
                'module_group_id'
            );
            $module_group_id->attach(
                $service_fields->fieldSelect(
                    'module_group_id',
                    $module_groups,
                    $this->post['module_group_id'] ?? $vars->module_group_id ?? null,
                    ['id' => 'module_group_id']
                )
            );
            $service_fields->setField($module_group_id);
        }

        $fields_html = new FieldsHtml($service_fields);
        $module_name = $module->getName();

        // Skip service configuration step if no service fields, config options, or addons
        $service_field_list = $service_fields->getFields();
        // Ignore the domain field
        if (($service_field_list[0]->params['name'] ?? null) == 'domain') {
            unset($service_field_list[0]);
        }
        $skip_config = (empty($service_field_list)
            && empty($addon_groups)
            && empty($package->option_groups)
            && !$this->isAjax());

        if ($skip_config && isset($item['group_id']) && isset($item['pricing_id'])) {
            $cart_fields = $item;
        } else {
            $cart_fields = $this->post;
        }

        // Attempt to add the item to the cart
        if (!empty($cart_fields)) {
            if (isset($cart_fields['qty'])) {
                $cart_fields['qty'] = (int)$cart_fields['qty'];
            }

            // Detect module refresh fields
            $refresh_fields = isset($cart_fields['refresh_fields']) && $cart_fields['refresh_fields'] == 'true';

            $option_logic = new OptionLogic();
            $option_logic->setPackageOptionConditionSets(
                $this->PackageOptionConditionSets->getAll(['package_id' => $package->id], ['option_id'])
            );

            // Verify fields look correct in order to proceed
            $this->Services->validateService($package, $cart_fields);
            $config_options = isset($cart_fields['configoptions']) ? $cart_fields['configoptions'] : [];
            if (!$refresh_fields
                && (($errors = $this->Services->errors()) || ($errors = $option_logic->validate($config_options)))
            ) {
                $this->handleError($errors);
            } elseif (!$refresh_fields) {
                // Add item to cart
                $item = array_merge($item, $cart_fields);
                unset($item['addon'], $item['submit']);

                if (isset($this->get['item'])) {
                    $item_index = $this->get['item'];
                    $this->SessionCart->updateItem($item_index, $item);
                } else {
                    $item_index = $this->SessionCart->addItem($item);

                    // If item came from the queue, dequeue
                    if ($queue_index !== null) {
                        $this->SessionCart->dequeue($queue_index);
                    }
                }

                if (isset($cart_fields['addon'])) {
                    // Remove any existing addons
                    $this->removeAddons($item);

                    $item = $this->SessionCart->getItem($item_index);

                    $addon_queue = [];
                    foreach ($cart_fields['addon'] as $addon_group_id => $addon) {
                        // Queue addon items for configuration
                        if (array_key_exists('pricing_id', $addon) && !empty($addon['pricing_id'])) {
                            $addon_item = [
                                'pricing_id' => $addon['pricing_id'],
                                'group_id' => $addon_group_id,
                                'uuid' => uniqid()
                            ];
                            $addon_queue[] = $addon_item['uuid'];
                            $this->SessionCart->enqueue($addon_item);
                        }
                    }
                    // Link the addons to this item
                    $item['addons'] = $addon_queue;
                    $this->SessionCart->updateItem($item_index, $item);
                }

                $next_uri = $this->base_uri . 'order/cart/index/' . $this->order_form->label;
                $empty_queue = $this->SessionCart->isEmptyQueue();

                // Process next queue item
                if (!$empty_queue) {
                    $next_uri = $this->base_uri . 'order/config/index/' . $this->order_form->label . '?q_item=0';
                } else {
                    // Custom redirect
                    $uri = $this->order_type->redirectRequest(
                        $this->controller . '.' . $this->action,
                        ['item_index' => $item_index]
                    );
                    $next_uri = $uri != '' ? $uri : $next_uri;
                }

                if ($this->isAjax()) {
                    $this->outputAsJson(['empty_queue' => $empty_queue, 'next_uri' => $next_uri]);
                    exit;
                } else {
                    $this->redirect($next_uri);
                }
            }

            $vars = (object)$cart_fields;
        }

        // Get service name
        $service_name = $this->ModuleManager->moduleRpc(
            $package->module_id,
            'getPackageServiceName',
            [$package, (array)$vars]
        );

        if (!empty($this->order_form->meta)) {
            $this->order_form->meta = $this->Form->collapseObjectArray(
                $this->order_form->meta,
                'value',
                'key'
            );
        }

        $bundle_eligible_domain = $this->isItemBundlableDomain($item);
        $bundle_eligible_package = $this->isItemBundlablePackage($item);
        $cart_has_eligible_domain = $this->cartHasEligibleDomain();

        $this->set('periods', $this->getPricingPeriods());
        $this->set(
            compact(
                'vars',
                'item',
                'package',
                'addon_groups',
                'service_fields',
                'fields_html',
                'module_name',
                'currency',
                'service_name',
                'bundle_eligible_domain',
                'bundle_eligible_package',
                'cart_has_eligible_domain'
            )
        );

        return $this->renderView();
    }

    /**
     * Preconfiguration of the service
     */
    public function preConfig()
    {

        // Only allow this step if the order type requires it
        if (!$this->order_type->requiresPreConfig()) {
            $this->redirect($this->base_uri . 'order/');
        }

        $this->get['base_uri'] = $this->base_uri;

        $content = $this->order_type->handleRequest($this->get, $this->post, $this->files);

        if (($errors = $this->order_type->errors())) {
            $this->handleError($errors);
        }

        $this->set('content', $content);
        $this->set('vars', (object)$this->post);

        // Render the view from the order type for this template
        $this->view->setView(
            null,
            'templates' . DS . $this->order_form->template . DS . 'types' . DS . $this->order_form->type
        );

        return $this->renderView();
    }

    /**
     * Fetch all packages options for the given pricing ID and optional service ID
     */
    public function packageOptions()
    {
        if (!$this->isAjax()) {
            $this->redirect($this->base_uri . 'order/');
        }

        $this->uses(['Packages', 'PackageOptions', 'PackageOptionConditionSets', 'Services']);

        $package = $this->Packages->getByPricingId($this->get[1]);

        $package_counts = $this->getClientPackageCounts();
        $limit_reached = $package->client_qty !== null
            && $package->client_qty <= (isset($package_counts[$package->id]) ? $package_counts[$package->id] : 0);

        if (!$package || $package->qty == '0' || $limit_reached) {
            return false;
        }

        $pricing = null;
        foreach ($package->pricing as $pricing) {
            if ($pricing->id == $this->get[1]) {
                break;
            }
        }

        $vars = (object)$this->get;
        $currency = $this->SessionCart->getData('currency');
        // Set the 'new' option to 1 to indicate these config options are for a new package being added
        // Set the 'new' option to 0 to indicate these config options are for changes to form fields
        // (e.g. unchecking a checkbox that we do not want to display a default option for)
        $options = [
            'new' => (isset($vars->configoptions) ? 0 : 1),
            'addable' => 1
        ];

        $package_options = $this->PackageOptions->getFields(
            $pricing->package_id,
            $pricing->term,
            $pricing->period,
            $pricing->currency,
            $vars,
            $currency,
            $options
        );

        $fields_html = new FieldsHtml($package_options);
        $option_logic = new OptionLogic();
        $option_logic->setPackageOptionConditionSets(
            $this->PackageOptionConditionSets->getAll(
                [
                    'package_id' => $pricing->package_id,
                    'opition_ids' => $this->Form->collapseObjectArray(
                        $this->PackageOptions->getAllByPackageId(
                            $pricing->package_id,
                            $pricing->term,
                            $pricing->period,
                            $pricing->currency,
                            $currency,
                            $options
                        ),
                        'id',
                        'id'
                    )
                ],
                ['option_id']
            )
        );
        $option_logic->setOptionContainerSelector($fields_html->getContainerSelector());
        $this->set('input_html', $fields_html);
        $this->set('option_logic_js', $option_logic->getJavascript());

        echo $this->outputAsJson($this->view->fetch('config_packageoptions'));
        return false;
    }

    /**
     * Queue management
     */
    public function queue()
    {
        if (isset($this->get[1])) {
            switch ($this->get[1]) {
                case 'add':
                    return $this->enqueueItem();
                case 'remove':
                    return $this->dequeueItem();
                case 'empty':
                    return $this->emptyQueue();
            }
        }
        return false;
    }

    /**
     * Enqueue an item
     */
    private function enqueueItem()
    {
        if (!empty($this->post)) {
            $index = $this->SessionCart->enqueue($this->post);
            $this->outputAsJson(['index' => $index, 'empty_queue' => $this->SessionCart->isEmptyQueue()]);
        }
        return false;
    }

    /**
     * Dequeue an item
     */
    private function dequeueItem()
    {
        if (!empty($this->post)) {
            $item = $this->SessionCart->dequeue($this->post['index']);
            $this->outputAsJson(['item' => $item, 'empty_queue' => $this->SessionCart->isEmptyQueue()]);
        }
        return false;
    }

    /**
     * Empty the queue
     */
    private function emptyQueue()
    {
        if (!empty($this->post)) {
            $this->SessionCart->setData('queue', null);
            $this->outputAsJson(['empty_queue' => $this->SessionCart->isEmptyQueue()]);
        }
        return false;
    }
}
