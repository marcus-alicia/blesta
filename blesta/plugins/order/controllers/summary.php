<?php

use Blesta\Core\Util\Captcha\CaptchaFactory;
use Blesta\Core\Util\Captcha\Captcha;

/**
 * Order System summary controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Summary extends OrderFormController
{
    /**
     * Returns the order summary partial, or, if this is an AJAX request, outputs
     * the order summary partial.
     */
    public function index()
    {
        Loader::loadModels($this, ['SettingsCollection', 'Companies']);

        // Get company settings
        $company_settings = $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id);

        // Check if captcha is required for signups
        $catpcha_forms = unserialize($company_settings['captcha_enabled_forms']);
        $require_captcha = Captcha::enabled('client_login') && isset($company_settings['captcha']);

        $captcha = null;
        if ($require_captcha) {
            $options = Captcha::getOptions();
            $captcha = $this->getCaptcha($company_settings['captcha'], $options);
        }

        // Allow temporary items to appear in the summary
        $item = null;
        $items = [];
        if (!empty($this->post)) {
            $items = [$this->post];
            $items[0]['addons'] = [];
            if (isset($items[0]['addon'])) {
                foreach ($items[0]['addon'] as $addon_group_id => $addon) {
                    // Queue addon items for configuration
                    if (array_key_exists('pricing_id', $addon) && !empty($addon['pricing_id'])) {
                        $uuid = uniqid();
                        $items[] = [
                            'pricing_id' => $addon['pricing_id'],
                            'group_id' => $addon_group_id,
                            'uuid' => $uuid
                        ];
                        $items[0]['addons'][] = $uuid;
                    }
                }
            }
            unset($item['addon'], $item['submit']);
        }
        $summary = $this->getSummary($items, isset($this->get['item']) ? $this->get['item'] : null);

        $client = $this->client;
        $order_form = $this->order_form;
        $periods = $this->getPricingPeriods();
        extract($this->getPaymentOptions());
        $vars = (object)$this->post;
        $temp_coupon = $this->SessionCart->getData('temp_coupon');

        // Check if captcha is required for log in
        $login_captcha = null;
        if ($require_captcha && !is_null($captcha)) {
            $login_captcha = $captcha->buildHtml();
        }

        // Check if domains elegible for free has been added to the current order
        $free_domains = [];
        foreach ($summary['cart']['items'] as $item) {
            if ($item['package_group_id'] == ($this->order_form->meta['domain_group'] ?? null)) {
                $tld = strstr($item['domain'] ?? '', '.');
                if (in_array($tld, $this->order_form->meta['tlds'] ?? [])) {
                    $free_domains[] = $item['domain'];
                }
            }
        }

        $this->set(
            compact(
                'summary',
                'client',
                'order_form',
                'periods',
                'nonmerchant_gateways',
                'merchant_gateway',
                'payment_types',
                'vars',
                'temp_coupon',
                'login_captcha',
                'free_domains'
            )
        );

        return $this->renderView();
    }

    /**
     * Retrieve an instance of the captcha
     *
     * @param string $captcha The captcha to be used, can be "recaptcha" or "internalcaptcha"
     * @param array $options The options to be used with the captcha:
     *
     *  - site_key The reCaptcha site key
     *  - shared_key The reCaptcha shared key
     *  - lang The user's language
     *  - ip_address The user's IP address (optional)
     * @return Blesta\Core\Util\Captcha\Common\CaptchaInterface
     */
    private function getCaptcha($captcha, array $options)
    {
        $factory = new CaptchaFactory();
        $instance = $factory->{$captcha}($options);

        return $instance ?? null;
    }
}
