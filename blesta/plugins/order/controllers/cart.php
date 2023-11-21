<?php
/**
 * Order System cart controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Cart extends OrderFormController
{
    /**
     * Display cart
     */
    public function index()
    {
        $this->helpers(['TextParser']);

        // Remove any illegal items from the cart
        $this->cleanCart();

        $parser_syntax = 'markdown';
        $this->TextParser->create($parser_syntax);

        $summary = $this->getSummary();

        $cart = $summary['cart'];
        $totals = $summary['totals'];
        $totals_recurring = $summary['totals_recurring'];
        $currency = $this->SessionCart->getData('currency');
        $temp_coupon = $this->SessionCart->getData('temp_coupon');

        $display_items = [];
        if (isset($cart['display_items'])) {
            $display_items = $cart['display_items'];
        }

        $this->set('periods', $this->getPricingPeriods());
        $this->set(
            compact('cart', 'totals', 'totals_recurring', 'display_items', 'parser_syntax', 'currency', 'temp_coupon')
        );

        return $this->renderView();
    }

    /**
     * Remove an item from the cart
     */
    public function remove()
    {
        $item = null;
        if (isset($this->post['item'])) {
            $item = $this->SessionCart->removeItem($this->post['item']);
            $this->removeAddons($item);

            if (!$this->isAjax()) {
                $this->flashMessage('message', Language::_('Cart.!success.item_removed', true), null, false);
                $uri = $this->order_type->redirectRequest($this->action, ['item' => $item]);
                $this->redirect($uri != '' ? $uri : $this->base_uri . 'order/cart/index/' . $this->order_form->label);
            }
        }

        if (!$this->isAjax()) {
            $this->redirect($this->base_uri . 'order/cart/index/' . $this->order_form->label);
        }
        return false;
    }

    /**
     * Removes all items from the cart
     */
    public function removeAll()
    {
        if (!empty($this->post)) {
            $this->SessionCart->emptyCart();

            if (!$this->isAjax()) {
                $this->flashMessage('message', Language::_('Cart.!success.all_items_removed', true), null, false);
                if (isset($this->post['redirect_to'])) {
                    $this->redirect($this->post['redirect_to']);
                } else {
                    $this->redirect($this->base_uri . 'order/cart/index/' . $this->order_form->label);
                }
            }
        }

        if (!$this->isAjax()) {
            $this->redirect($this->base_uri . 'order/cart/index/' . $this->order_form->label);
        }

        return false;
    }

    /**
     * Applies a coupon
     */
    public function applyCoupon()
    {
        // Clear any temporary coupon
        $this->SessionCart->setData('temp_coupon', '');

        if ($this->order_form->allow_coupons != '1') {
            return false;
        }

        $this->uses(['Coupons', 'Order.OrderOrders']);

        $coupon = null;
        if (!empty($this->post['coupon'])) {
            $packages = $this->OrderOrders->getPackagesFromItems($this->SessionCart->getData('items'));
            $coupon = $this->Coupons->getForPackages($this->post['coupon'], null, $packages);

            // Disallow coupons that are for internal use only
            $coupon = ($coupon && !$coupon->internal_use_only ? $coupon : false);

            if ($coupon) {
                $this->SessionCart->setData('coupon', $coupon->code);
            }
        }

        if ($this->isAjax()) {
            $data = [];

            if ($coupon) {
                $data['coupon'] = $coupon;
                $data['success'] = $this->setMessage(
                    'message',
                    Language::_('Cart.!success.coupon_applied', true),
                    true,
                    null,
                    false
                );
            } else {
                $data['error'] = $this->setMessage(
                    'error',
                    Language::_('Cart.!error.coupon_applied', true),
                    true,
                    null,
                    false
                );
            }

            $this->outputAsJson($data);
        } else {
            if ($coupon && !$coupon->internal_use_only) {
                $this->flashMessage('message', Language::_('Cart.!success.coupon_applied', true), null, false);
            } else {
                $this->flashMessage('error', Language::_('Cart.!error.coupon_applied', true), null, false);
            }

            $uri = $this->order_type->redirectRequest($this->action, ['coupon' => $coupon]);
            $this->redirect($uri != '' ? $uri : $this->base_uri . 'order/cart/index/' . $this->order_form->label);
        }

        return false;
    }
}
