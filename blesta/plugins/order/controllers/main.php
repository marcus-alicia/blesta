<?php
/**
 * Order System main controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Main extends OrderFormController
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
     * List packages groups/packages
     */
    public function index()
    {
        $this->helpers(['TextParser']);
        $parser_syntax = 'markdown';

        // If pricing ID and group ID set, redirect to configure this item
        $this->post = array_merge($this->post, $this->SessionCart->getData('get_parameters') ?: []);
        if (array_key_exists('pricing_id', $this->post) && array_key_exists('group_id', $this->post)) {
            $this->redirect(
                $this->base_uri . 'order/config/index/' . $this->order_form->label
                . '/?' . http_build_query($this->post)
            );
        }

        // If the order type require pre config then redirect directly to preconfig
        if ($this->order_type->requiresPreConfig() && (!isset($this->get['skip']) || $this->get['skip'] == 'false')) {
            $this->redirect($this->base_uri . 'order/config/preconfig/' . $this->order_form->label);
        }

        $package_groups = [];
        $packages = [];
        $currency = $this->SessionCart->getData('currency');

        // If only one available group, redirect to package listing for that single group
        if (count($this->order_form->groups) == 1) {
            $this->redirect(
                $this->base_uri . 'order/main/packages/' . $this->order_form->label
                . '/?group_id=' . $this->order_form->groups[0]->package_group_id
            );
        }
        // If no package groups available, redirect to config or cart
        if (count($this->order_form->groups) == 0) {
            $redirect_uri = $this->base_uri . 'order/config/index/' . $this->order_form->label . '/';
            if ($this->SessionCart->isEmptyQueue()) {
                $redirect_uri = $this->base_uri . 'order/cart/index/' . $this->order_form->label . '/';
            }
            $this->redirect($redirect_uri);
        }

        foreach ($this->order_form->groups as $group) {
            // Fetch the package group details
            $package_groups[$group->package_group_id] = $this->PackageGroups->get($group->package_group_id);

            // Fetch all packages for this group
            $packages[$group->package_group_id] = $this->Packages->getAllPackagesByGroup(
                $group->package_group_id,
                'active',
                ['hidden' => true]
            );

            // Add restricted packages allowed for this user
            $client_packages = [];
            foreach ($this->Clients->getRestrictedPackages($this->Session->read('blesta_client_id')) as $package) {
                $client_packages[] = $package->package_id;
            }

            $restricted_packages = $this->Packages->getAllPackagesByGroup(
                $group->package_group_id,
                'restricted',
                ['hidden' => true]
            );
            foreach ($restricted_packages as $package) {
                if (in_array($package->id, $client_packages)) {
                    if (!isset($packages[$group->package_group_id])) {
                        $packages[$group->package_group_id] = [];
                    }
                    $packages[$group->package_group_id][] = $package;
                }
            }

            // Update package pricing for the selected currency
            $packages[$group->package_group_id] = $this->updatePackagePricing(
                $packages[$group->package_group_id],
                $currency
            );
        }

        $summary = $this->getSummary();
        $totals = $summary['totals'];
        $periods = $this->getPricingPeriods();
        $package_counts = $this->getClientPackageCounts();
        $cart_has_eligible_domain = $this->cartHasEligibleDomain();

        $this->set('cart', $summary['cart']);
        $this->set(
            'package_group_partial',
            $this->partial(
                'main_index' . ($this->order_form->template_style == 'list' ? '_list' : ''),
                array_merge(
                    compact(
                        'package_groups',
                        'packages',
                        'parser_syntax',
                        'currency',
                        'totals',
                        'periods',
                        'package_counts',
                        'cart_has_eligible_domain'
                    ),
                    ['order_form' => $this->order_form]
                )
            )
        );
    }

    /**
     * List packages for a specific group
     */
    public function packages()
    {
        $this->helpers(['TextParser']);
        $parser_syntax = 'markdown';

        // If pricing ID and group ID set, redirect to configure this item
        if (array_key_exists('pricing_id', $this->post) && array_key_exists('group_id', $this->post)) {
            $this->redirect(
                $this->base_uri . 'order/config/index/' . $this->order_form->label
                . '/?' . http_build_query($this->post)
            );
        }

        $this->get = array_merge($this->get, $this->SessionCart->getData('get_parameters') ?: []);
        if (!array_key_exists('group_id', $this->get)) {
            $this->redirect($this->base_uri . 'order/main/index/' . $this->order_form->label);
        }

        $pricing_id = null;
        $package_id = null;
        if (array_key_exists('pricing_id', $this->get)) {
            $pricing_id = $this->get['pricing_id'];
        } elseif (array_key_exists('package_id', $this->get)) {
            $package_id = $this->get['package_id'];
        }

        // Set default config options from the set of GET arguments
        $config_options = [];
        if (array_key_exists('configoptions', $this->get) && is_array($this->get['configoptions'])) {
            $config_options = (array)$this->get['configoptions'];
        }

        $package_group_id = $this->get['group_id'];
        $package_group = false;
        $packages = [];
        $currency = $this->SessionCart->getData('currency');

        foreach ($this->order_form->groups as $group) {
            if ($group->package_group_id == $package_group_id) {
                // Fetch the package group details
                $package_group = $this->PackageGroups->get($group->package_group_id);

                // Fetch all active packages for this group
                $packages = $this->Packages->getAllPackagesByGroup(
                    $group->package_group_id,
                    'active',
                    ['hidden' => true]
                );

                // Add restricted packages allowed for this user
                $client_packages = [];
                foreach ($this->Clients->getRestrictedPackages($this->Session->read('blesta_client_id')) as $package) {
                    $client_packages[] = $package->package_id;
                }

                $restricted_packages = $this->Packages->getAllPackagesByGroup(
                    $group->package_group_id,
                    'restricted',
                    ['hidden' => true]
                );
                foreach ($restricted_packages as $package) {
                    if (in_array($package->id, $client_packages)) {
                        $packages[] = $package;
                    }
                }

                // Update package pricing for the selected currency
                $packages = $this->updatePackagePricing($packages, $currency);
            }
        }

        $package_counts = $this->getClientPackageCounts();
        foreach ($packages as $i => $package) {
            $sold_out = $package->qty == 0 && $package->qty !== null;
            $limit_reached = $package->client_qty !== null
                && $package->client_qty <= (isset($package_counts[$package->id]) ? $package_counts[$package->id] : 0);
            if ($sold_out || $limit_reached) {
                // If this is a slider form, simply don't show packages with 0 quantity
                // remaining or where the client limit has been reached
                if ($this->order_form->template_style == 'slider') {
                    unset($packages[$i]);
                }

                // Deselect the selected package if it is sold out
                if ($package->id == $package_id) {
                    $package_id = null;
                }

                // Deselect the selected pricing if it is sold out
                foreach ($package->pricing as $package_pricing) {
                    if ($package_pricing->id == $pricing_id) {
                        $pricing_id = null;
                        break;
                    }
                }
            }
        }
        $packages = array_values($packages);

        // If no package/pricing is selected, or the selected package sold out, select the first available package
        if ($package_id === null) {
            foreach ($packages as $package) {
                $sold_out = $package->qty == 0 && $package->qty !== null;
                $limit_reached = $package->client_qty !== null
                    && $package->client_qty
                        <= (isset($package_counts[$package->id]) ? $package_counts[$package->id] : 0);

                if (!$sold_out && !$limit_reached && isset($package->pricing[0])) {
                    if ($pricing_id === null) {
                        $package_id = $package->id;
                        $pricing_id = $package->pricing[0]->id;
                        break;
                    } else {
                        foreach ($package->pricing as $pricing) {
                            if ($pricing_id === $pricing->id) {
                                $package_id = $package->id;
                            }
                        }
                    }
                }
            }
        }

        if (!$package_group) {
            $this->redirect($this->base_uri . 'order/main/index/' . $this->order_form->label);
        }

        // If there are still no package and pricing selected, it means that none are
        // available. Set an error message and redirect
        if ($pricing_id === null && $package_id === null && count($this->order_form->groups) > 1) {
            $this->flashMessage('error', Language::_('Main.!error.package_limits', true), null, false);
            $this->redirect($this->base_uri . 'order/main/index/' . $this->order_form->label);
        }

        $summary = $this->getSummary();
        $cart = $summary['cart'];
        $totals = $summary['totals'];
        $cart_has_eligible_domain = $this->cartHasEligibleDomain();

        $this->set('periods', $this->getPricingPeriods());
        $this->set(
            compact(
                'package_group',
                'packages',
                'parser_syntax',
                'currency',
                'cart',
                'totals',
                'config_options',
                'package_counts',
                'pricing_id',
                'package_id',
                'cart_has_eligible_domain'
            )
        );
    }
}
