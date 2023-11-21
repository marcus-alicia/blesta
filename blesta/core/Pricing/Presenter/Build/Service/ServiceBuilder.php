<?php
namespace Blesta\Core\Pricing\Presenter\Build\Service;

use stdClass;

/**
 * Service builder
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Build.Service
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ServiceBuilder extends AbstractServiceBuilder
{
    /**
     * Retrieve a ServicePresenter
     *
     * @param stdClass $service An stdClass object representing the service, including:
     *
     *  - name The service's name
     *  - qty The service quantity
     *  - override_price The service's override price
     *  - override_currency The service's override currency
     *  - date_renews The service renew date
     *  - options An array of service options, each option an stdClass object containing:
     *      - id The option value ID
     *      - service_id The service ID
     *      - option_pricing_id The option's pricing ID
     *      - qty The option quantity
     *      - option_value The option value
     *      - option_value_name The option value name
     *      - option_id The option's ID
     *      - option_label The option's label
     *      - option_name The name of the option
     *      - option_type The type of option
     *      - option_pricing_term The option's pricing term
     *      - option_pricing_period The option's pricing period
     *      - option_pricing_price The option's pricing price
     *      - option_pricing_setup_fee The option's pricing setup fee
     *      - option_pricing_currency The option's pricing currency
     *  - package_pricing An stdClass object representing the service's package pricing, including:
     *      - id The package pricing ID
     *      - package_id The package ID
     *      - term The pricing term
     *      - period The pricing period
     *      - setup_fee The pricing setup fee
     *      - cancel_fee The pricing cancelation fee
     *      - currency The pricing currency
     *  - package An stdClass object representing the service's package, including:
     *      - name The package name
     *      - taxable 1 or 0, whether the package is taxable
     *      - prorata_day The package pro rata day
     *      - prorata_cutoff The package pro rata cutoff day
     * @return ServicePresenter An instance of the ServicePresenter
     */
    public function build(stdClass $service)
    {
        // Set the builder settings to the pricing factory
        $pricingFactoryOptions = [];
        if (method_exists($this->pricingFactory, 'getOptions')) {
            $pricingFactoryOptions = $this->pricingFactory->getOptions();
        }
        $this->pricingFactory->options(array_merge((array) $this->options, (array) $pricingFactoryOptions));

        // Initialize the service items with settings, taxes, and discounts
        $settingsFormatter = $this->formatFactory->settings();
        $serviceItems = $this->serviceFactory->service(
            $this->pricingFactory,
            $this->itemFactory,
            $this->formatSettings($settingsFormatter),
            $this->formatTaxes($this->formatFactory->tax(), $this->itemFactory->itemCollection()),
            $this->formatDiscounts($this->formatFactory->discount(), $this->itemFactory->itemCollection()),
            $this->formatOptions($settingsFormatter)
        );

        // Format the service into consistent fields
        $data = $this->format($service);

        // Build and prorate the ItemPriceCollection from the service items, then set a description for each item
        $description = $this->pricingFactory->description();
        $proration = $this->pricingFactory->proration();
        $collection = $description->describe(
            $proration->prorate(
                $serviceItems->build($data->service, $data->package, $data->pricing, $data->options)
            )
        );

        return $this->presenterFactory->service($collection);
    }

    /**
     * Formats the service data into consistent items
     *
     * @param stdClass $service The service
     * @return stdClass An stdClass of formatted items representing the service
     */
    private function format(stdClass $service)
    {
        // Format each given service option as its own item
        $options = $this->itemFactory->itemCollection();
        if (property_exists($service, 'options')) {
            $optionFormatter = $this->formatFactory->option();

            foreach ($service->options as $option) {
                $options->append($optionFormatter->formatService((object)$option));
            }
            unset($option);
        }

        // Set the service formatters
        $serviceFormatter = $this->formatFactory->service();
        $packageFormatter = $this->formatFactory->package();
        $pricingFormatter = $this->formatFactory->pricing();

        // Update the service to set a 'price' and 'currency' field
        // of the set pricing which will be used when there is no override
        $fields = ['price', 'price_renews', 'price_transfer', 'currency'];
        foreach ($fields as $field) {
            if (property_exists($service, 'package_pricing') && is_object($service->package_pricing)
                && property_exists($service->package_pricing, $field)
            ) {
                $service->{$field} = $service->package_pricing->{$field};
            }
        }

        // Remove the override price so it does not override the package price if no price exists
        if (empty($service->override_price)) {
            unset($service->override_price, $service->override_currency);
        }

        return (object)[
            'service' => $serviceFormatter->formatService($service),
            'domain' => $serviceFormatter->formatService($service),
            'package' => $packageFormatter->formatService(
                property_exists($service, 'package') ? (object)$service->package : new stdClass()
            ),
            'pricing' => $pricingFormatter->formatService(
                property_exists($service, 'package_pricing') ? (object)$service->package_pricing : new stdClass()
            ),
            'options' => $options
        ];
    }
}
