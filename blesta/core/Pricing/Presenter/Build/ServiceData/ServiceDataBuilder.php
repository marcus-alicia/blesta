<?php

namespace Blesta\Core\Pricing\Presenter\Build\ServiceData;

use Blesta\Core\Pricing\Presenter\Type\ServiceDataPresenter;
use stdClass;

/**
 * Service data builder
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Build.ServiceData
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ServiceDataBuilder extends AbstractServiceDataBuilder
{
    /**
     * Retrieve a ServiceDataPresenter
     *
     * @param array $vars An array of input data, including:
     *
     *  - pricing_id The ID of the selected package pricing
     *  - qty The service quantity (default 1)
     *  - override_price The new override price
     *  - override_currency The new override currency
     *  - configoptions An array of config options where each key is the option ID
     *      and the value is the selected option value
     * @param stdClass $package An stdClass object representing the package selected for use, including:
     *
     *  - id The package ID
     *  - name The package's name
     *  - taxable 1 or 0, whether the package is taxable
     *  - prorata_day The package pro rata day
     *  - prorata_cutoff The package pro rata cutoff day
     * @param stdClass $pricing An stdClass object representing the selected package pricing, including:
     *
     *  - id The package pricing ID
     *  - package_id The package ID
     *  - term The pricing term
     *  - period The pricing period
     *  - setup_fee The pricing setup fee
     *  - cancel_fee The pricing cancelation fee
     *  - currency The pricing currency
     * @param array $options An array of stdClass objects representing all package options, each including:
     *
     *  - id The option ID
     *  - label The option label
     *  - name The option name
     *  - type The option type
     *  - values An array of stdClass objects representing each option value, including:
     *      - id The option value ID
     *      - option_id The option ID
     *      - value The option value
     *      - min The minimum value
     *      - max The maximum value
     *      - step The step value
     *      - pricing An array whose first index contains an stdClass object
     *          representing the option value pricing, including:
     *          - id The option value pricing ID
     *          - pricing_id The pricing ID
     *          - option_value_id The option value ID
     *          - term The pricing term
     *          - period The pricing period
     *          - price The option value price
     *          - setup_fee The option value setup fee
     *          - cancel_fee The option value cancelation fee
     *          - currency The option value currency
     * @return ServiceDataPresenter An instance of the ServiceDataPresenter
     */
    public function build(array $vars, stdClass $package, stdClass $pricing, array $options)
    {
        // Set the builder settings to the pricing factory
        $pricingFactoryOptions = [];
        if (method_exists($this->pricingFactory, 'getOptions')) {
            $pricingFactoryOptions = $this->pricingFactory->getOptions();
        }
        $this->pricingFactory->options(array_merge((array) $this->options, (array) $pricingFactoryOptions));

        // Initialize the service items with settings, taxes, and discounts
        $settingsFormatter = $this->formatFactory->settings();
        $serviceDataItems = $this->serviceFactory->serviceData(
            $this->pricingFactory,
            $this->itemFactory,
            $this->formatSettings($settingsFormatter),
            $this->formatTaxes($this->formatFactory->tax(), $this->itemFactory->itemCollection()),
            $this->formatDiscounts($this->formatFactory->discount(), $this->itemFactory->itemCollection()),
            $this->formatOptions($settingsFormatter)
        );

        // Override price and currency
        if (isset($vars['override_price'])) {
            $pricing->price = $vars['override_price'];
            $pricing->price_renews = $vars['override_price'];
            $pricing->price_transfer = $vars['override_price'];
        }
        if (isset($vars['override_currency'])) {
            $pricing->currency = $vars['override_currency'];
        }

        // Format the service into consistent fields
        $data = $this->format($vars, $package, $pricing, $options);

        // Build and prorate the ItemPriceCollection from the service items, then set a description for each item
        $description = $this->pricingFactory->description();
        $proration = $this->pricingFactory->proration();
        $collection = $description->describe(
            $proration->prorate(
                $serviceDataItems->build($data->service, $data->package, $data->pricing, $data->options)
            )
        );

        return $this->presenterFactory->serviceData($collection);
    }

    /**
     * Formats the service data into items
     *
     * @param array $vars An array of service data representing the service
     * @param stdClass $package An stdClass object representing the package
     * @param stdClass $pricing An stdClass object representing the pricing
     * @param array An array of package option items
     * @return stdClass An stdClass of formatted items representing the service
     */
    private function format(array $vars, stdClass $package, stdClass $pricing, array $packageOptions)
    {
        // Format each given service option as its own item
        $options = $this->itemFactory->itemCollection();

        $optionFormatter = $this->formatFactory->option();
        foreach ($packageOptions as $option) {
            $options->append($optionFormatter->format((object)$option));
        }
        unset($option);

        // Set the service formatters
        $serviceFormatter = $this->formatFactory->service();
        $packageFormatter = $this->formatFactory->package();
        $pricingFormatter = $this->formatFactory->pricing();

        return (object)[
            'service' => $serviceFormatter->format((object)$vars),
            'domain' => $serviceFormatter->format((object)$vars),
            'package' => $packageFormatter->format($package),
            'pricing' => $pricingFormatter->format($pricing),
            'options' => $options
        ];
    }
}
