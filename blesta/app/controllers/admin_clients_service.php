<?php

use Blesta\Core\Pricing\Presenter\Type\PresenterInterface;

/**
 * Admin Client's Service Management
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2015, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminClientsService extends AdminController
{
    /**
     * Admin pre-action
     */
    public function preAction()
    {
        parent::preAction();

        $this->uses(['Clients', 'Services']);
        Language::loadLang(['admin_clients_service']);
    }

    /**
     * AJAX Retrieves a partial template of totals based on service changes
     */
    public function updateTotals()
    {
        // Ensure we have a valid AJAX request with the given client and service
        if (!$this->isAjax()
            || !isset($this->get[0])
            || !isset($this->get[1])
            || !($client = $this->Clients->get((int) $this->get[0]))
            || !($service = $this->Services->get((int) $this->get[1]))
            || $service->client_id != $client->id) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $this->uses(['Invoices', 'PackageOptions', 'ServiceChanges']);

        // Renew date totals differ from upgrade/downgrades to the service
        if (isset($this->post['date_renews'])) {
            // Set the service's current service options as config options along with the renew date selected
            $fields = ['date_renews', 'configoptions'];
            $this->post = array_merge($this->post, $this->PackageOptions->formatServiceOptions($service->options));
            $this->post['date_renews'] = $this->Services->dateToUtc($this->post['date_renews']) . 'Z';
        } else {
            // White-list only specific fields
            $fields = ['pricing_id', 'configoptions', 'coupon_code', 'qty'];
            if (isset($this->post['price_override']) && $this->post['price_override'] == 'true') {
                $fields = array_merge($fields, ['override_price', 'override_currency']);
            }
        }

        $vars = array_merge(
            array_intersect_key($this->post, array_flip($fields)),
            ['qty' => (!empty($this->post['qty']) ? $this->post['qty'] : 1)]
        );

        // Determine the pricing being used
        $pricing = null;
        if (isset($this->post['pricing_id'])) {
            $pricing = $this->getPricing($this->post['pricing_id']);
        }
        if (!$pricing) {
            $pricing = $service->package_pricing;
        }

        // Determine the items/totals
        $serviceChange = $this->ServiceChanges->getPresenter($service->id, $vars);

        // Only set the totals if we have a presenter to set them with
        if ($serviceChange) {
            echo $this->outputAsJson($this->totals(
                $serviceChange,
                $vars['override_currency'] ?? $pricing->currency,
                $client->settings
            ));
        }
        return false;
    }

    /**
     * Builds and returns the totals partial
     *
     * @param PresenterInterface $presenter An instance of the PresenterInterface
     * @param string $currency The ISO 4217 currency code
     * @param array $client_settings A list of client setting
     * @return string The totals partial template
     */
    private function totals(PresenterInterface $presenter, $currency, array $client_settings)
    {
        $pricingFactory = $this->getFromContainer('pricing');
        $arrayMerge = $pricingFactory->arrayMerge();

        return $this->partial(
            'admin_clients_service_totals',
            [
                'totals' => $presenter->totals(),
                'discounts' => $arrayMerge->combineSum($presenter->discounts(), 'id', 'total'),
                'taxes' => $arrayMerge->combineSum($presenter->taxes(), 'id', 'total'),
                'currency' => $currency,
                'settings' => $client_settings
            ]
        );
    }

    /**
     * Retrieves package pricing info from the given pricing ID
     *
     * @param int $pricing_id The ID of the pricing to fetch
     * @return mixed An stdClass representing the package pricing, or false if it is invalid
     */
    private function getPricing($pricing_id)
    {
        $this->uses(['Packages']);

        // Determine the matching pricing
        if (($package = $this->Packages->getByPricingId($pricing_id))) {
            foreach ($package->pricing as $price) {
                if ($price->id == $pricing_id) {
                    return $price;
                }
            }
        }

        return false;
    }
}
