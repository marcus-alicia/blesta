<?php
namespace Blesta\Core\Pricing\Presenter\Items\Service;

use Blesta\Items\Item\ItemInterface;
use Blesta\Items\Collection\ItemCollection;

/**
 * Build service data items
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Items.Service
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ServiceDataItems extends AbstractServiceItems
{
    /**
     * {@inheritdoc}
     */
    public function build(
        ItemInterface $service,
        ItemInterface $package,
        ItemInterface $pricing,
        ItemCollection $options
    ) {
        // Create a collection of all of the items
        $itemPriceCollection = $this->pricingFactory->itemPriceCollection();

        // Make item prices out of the service and service options
        $packageOptions = $this->keyOptions($options);
        $serviceItems = array_merge(
            $this->makeItems($service, $pricing, $package),
            $this->makeOptionItems($packageOptions, $service, $package)
        );

        // Apply discounts to the items
        $packageFields = $package->getFields();
        $packageId = (isset($packageFields->id) ? $packageFields->id : null);

        $pricingFields = $pricing->getFields();
        $term = (isset($pricingFields->term) ? $pricingFields->term : null);
        $period = (isset($pricingFields->period) ? $pricingFields->period : null);

        $serviceItems = $this->setDiscounts($serviceItems, [$packageId => [$period => [$term]]]);

        // Apply taxes to the items
        $serviceItems = $this->setTaxes($serviceItems);

        // Add the service items to the collection
        foreach ($serviceItems as $item) {
            $itemPriceCollection->append($item);
        }

        return $itemPriceCollection;
    }

    /**
     * Creates a set of MetaItemPrices for the service data
     *
     * @param ItemInterface $service An item representing the service data
     * @param ItemInterface $pricing An item representing the pricing
     * @param ItemInterface $package An item representing the package
     * @return array An array of MetaItemPrices
     */
    private function makeItems(ItemInterface $service, ItemInterface $pricing, ItemInterface $package)
    {
        // Determine the service price, setup fee, and cancel fee
        $serviceFields = $service->getFields();
        $pricingFields = $pricing->getFields();
        $packageFields = $package->getFields();
        $settings = $this->options->getFields();

        // The price is the given price or the service-defined price
        $price = (empty($pricingFields->price) ? 0 : $pricingFields->price);
        $currency = (empty($pricingFields->currency) ? '' : $pricingFields->currency);

        // Set the renewal price
        if ((isset($settings->recur)
            && $settings->recur
            && isset($pricingFields->price_renews)
            && ($packageFields->upgrades_use_renewal || (isset($settings->upgrade) && !$settings->upgrade)))
            || ($settings->renewal ?? false)
        ) {
            $price = (empty($pricingFields->price_renews) ? 0 : $pricingFields->price_renews);
        }

        // Set the transfer price
        if (isset($settings->transfer)
            && $settings->transfer
            && isset($pricingFields->price_transfer)
        ) {
            $price = (empty($pricingFields->price_transfer) ? $price : $pricingFields->price_transfer);
        }

        // Set the override price
        if (isset($serviceFields->price)) {
            $price = (empty($serviceFields->price) ? 0 : $serviceFields->price);
            $currency = (empty($serviceFields->currency) ? $currency : $serviceFields->currency);
        }

        // Set the price term cycles
        if (isset($settings->cycles) && $settings->cycles > 0) {
            $price = $price * $settings->cycles;
        }

        $qty = (empty($serviceFields->qty) ? 0 : $serviceFields->qty);
        $packageId = (empty($packageFields->id) ? null : $packageFields->id);

        $setupFee = (empty($pricingFields->setup_fee) ? 0 : $pricingFields->setup_fee);
        $cancelFee = (empty($pricingFields->cancel_fee) ? 0 : $pricingFields->cancel_fee);

        // Items of no quantity are not included
        $items = [];
        if ($qty === 0) {
            return $items;
        }

        // Create fields for a meta item to store with the item price
        $fields = [
            '_data' => array_merge(
                ['item_type' => ($settings->item_type ?? 'service'), 'type' => null, 'package_id' => $packageId],
                $this->getDateRange($pricingFields)
            ),
            'service' => $serviceFields,
            'package' => $packageFields,
            'pricing' => $pricingFields
        ];
        $prorateFields = [
            'prorate' => (object)[
                'startDate' => (empty($settings->prorateStartDate) ? null : $settings->prorateStartDate),
                'endDate' => (empty($settings->prorateEndDate) ? null : $settings->prorateEndDate),
                'prorataDay' => (empty($packageFields->prorata_day) ? null : $packageFields->prorata_day),
                'term' => (empty($pricingFields->term) ? null : $pricingFields->term),
                'period' => (empty($pricingFields->period) ? null : $pricingFields->period),
                'currency' => $currency
            ]
        ];

        if (isset($settings->cycles) && $settings->cycles > 0) {
            $prorateFields['prorate']->term = $prorateFields['prorate']->term * $settings->cycles;
        }

        // Create the package item
        $items[] = [
            'price' => $price,
            'qty' => $qty,
            'key' => $this->getKey('package', 'item', $packageId),
            'meta' => array_merge(
                $fields,
                $prorateFields,
                ['_data' => array_merge($fields['_data'], ['type' => 'package'])]
            )
        ];

        // Create an item price for the setup and cancel fees if there is any fee
        if ($settings->includeSetupFees && $setupFee != 0) {
            $items[] = [
                'price' => $setupFee,
                'qty' => 1,
                'key' => $this->getKey('package', 'setup', $packageId),
                'meta' => array_merge($fields, ['_data' => array_merge($fields['_data'], ['type' => 'setup'])])
            ];
        }

        if ($settings->includeCancelFees && $cancelFee != 0) {
            $items[] = [
                'price' => $cancelFee,
                'qty' => 1,
                'key' => $this->getKey('package', 'cancel', $packageId),
                'meta' => array_merge($fields, ['_data' => array_merge($fields['_data'], ['type' => 'cancel'])])
            ];
        }

        return $this->makeMetaItemPrices($items);
    }

    /**
     * Creates a set of MetaItemPrices for each valid package option
     *
     * @param array $options An array of all options keyed by option ID
     * @param ItemInterface $service An item representing the service
     * @param ItemInterface $package An item representing the package
     * @return array An array of MetaItemPrices
     */
    private function makeOptionItems(array $options, ItemInterface $service, ItemInterface $package)
    {
        $itemPrices = [];

        // Ensure we have options
        $serviceFields = $service->getFields();
        if (empty($serviceFields->options) || !is_array($serviceFields->options) || empty($options)) {
            return $itemPrices;
        }

        $packageFields = $package->getFields();
        $packageId = (empty($packageFields->id) ? null : $packageFields->id);
        $settings = $this->options->getFields();
        $systemSettings = $this->settings->getFields();
        $showDates = (isset($systemSettings->inv_lines_verbose_option_dates)
            && $systemSettings->inv_lines_verbose_option_dates == 'true'
        );

        // Create new item prices for each chosen config option
        foreach ($serviceFields->options as $optionId => $value) {
            // Get the option that matches the selected option/value
            // It must have a non-zero quantity
            if (!($option = $this->fetchOption($options, $optionId, $value)) || $option->qty === 0) {
                continue;
            }

            // Create fields for a meta item to store with the item price
            $fields = [
                '_data' => array_merge(
                    ['item_type' => 'option', 'type' => null, 'option_id' => $optionId, 'show_dates' => $showDates],
                    $this->getDateRange($option)
                ),
                'option' => $option,
                'package' => $packageFields
            ];
            $prorateFields = [
                'prorate' => (object)[
                    'startDate' => (empty($settings->prorateStartDate) ? null : $settings->prorateStartDate),
                    'endDate' => (empty($settings->prorateEndDate) ? null : $settings->prorateEndDate),
                    'prorataDay' => (empty($packageFields->prorata_day) ? null : $packageFields->prorata_day),
                    'term' => $option->term,
                    'period' => $option->period,
                    'currency' => $option->currency
                ]
            ];

            if (isset($settings->cycles) && $settings->cycles > 0) {
                $prorateFields['prorate']->term = $prorateFields['prorate']->term * $settings->cycles;
            }

            $useRenewalPrice = (isset($settings->recur)
                && $settings->recur
                && $option->price_renews !== null
                && (
                    ($packageFields->upgrades_use_renewal && isset($settings->upgrade) && $settings->upgrade)
                    || (
                        (!isset($settings->upgrade) || !$settings->upgrade)
                        && isset($settings->config_options)
                        && array_key_exists($optionId, $settings->config_options)
                    )
                ))
                || ($settings->renewal ?? false);
            $useTransferPrice = isset($settings->transfer)
                && $settings->transfer
                && $option->price_transfer !== null;
            $items = [];
            $itemPrice = $useRenewalPrice ? $option->price_renews : $option->price;
            $itemPrice = $useTransferPrice ? $option->price_transfer : $itemPrice;

            if (isset($settings->cycles) && $settings->cycles > 0) {
                $itemPrice = $itemPrice * $settings->cycles;
            }

            $items[] = [
                'price' => $itemPrice,
                'qty' => $option->qty,
                'key' => $this->getKey('packageoption', 'item', $packageId, $optionId),
                'meta' => array_merge(
                    $fields,
                    $prorateFields,
                    ['_data' => array_merge($fields['_data'], ['type' => 'option'])]
                )
            ];

            // Create an item price for the setup fee and cancel fee if there is any
            if ($settings->includeSetupFees && $option->setup_fee != 0) {
                $items[] = [
                    'price' => $option->setup_fee,
                    'qty' => 1,
                    'key' => $this->getKey('packageoption', 'setup', $packageId, $optionId),
                    'meta' => array_merge($fields, ['_data' => array_merge($fields['_data'], ['type' => 'setup'])])
                ];
            }

            if ($settings->includeCancelFees && $option->cancel_fee != 0) {
                $items[] = [
                    'price' => $option->cancel_fee,
                    'qty' => 1,
                    'key' => $this->getKey('packageoption', 'cancel', $packageId, $optionId),
                    'meta' => array_merge($fields, ['_data' => array_merge($fields['_data'], ['type' => 'cancel'])])
                ];
            }

            // Append each item price
            $itemPrices = array_merge($itemPrices, $this->makeMetaItemPrices($items));
        }

        return $itemPrices;
    }

    /**
     * Retrieves the given option/value pair from the set of options
     *
     * @param array $options An array of package option Items keyed by option ID
     * @param int $optionId The ID of the option to retrieve
     * @param mixed $value The value of the option selected
     * @return stdClass|bool The stdClass object representing the selected option, otherwise false
     */
    private function fetchOption(array $options, $optionId, $value)
    {
        $optionFields = (array_key_exists($optionId, $options) ? $options[$optionId]->getFields() : null);

        // Invalid option. It must exist and have option values
        if (empty($optionFields) || empty($optionFields->options)) {
            return false;
        }

        $option = $optionFields;
        $chosenValue = null;

        // Determine whether the option is a quantity/text type or not
        // since this affects how we interpret the value
        $quantityOption = (isset($option->type) && $option->type == 'quantity');
        $textOption = (isset($option->type) && in_array($option->type, ['text', 'textarea', 'password']));

        foreach ($option->options as $optionValue) {
            // The value and pricing must exist
            if (!property_exists($optionValue, 'value')
                || empty($optionValue->pricing)
                || empty($optionValue->pricing[0])
            ) {
                continue;
            }

            // The option must be a quantity/text/textarea/password option, otherwise the value must match
            if (!$quantityOption && !$textOption && $value != $optionValue->value) {
                continue;
            }

            $chosenValue = $optionValue;
            break;
        }

        // Ensure we have a matching option value
        if (empty($chosenValue)) {
            return false;
        }

        $pricing = $chosenValue->pricing[0];

        // Set common option data
        $qty = ($quantityOption ? $value : 1);
        $option->qty = (!is_scalar($qty) || empty($qty) ? 0 : $qty);
        $option->pricing_id = (empty($pricing->id) ? null : $pricing->id);
        $option->value_id = (empty($chosenValue->id) ? null : $chosenValue->id);
        $option->value = $value;
        $option->value_name = (empty($chosenValue->name) ? '' : $chosenValue->name);
        $option->term = (empty($pricing->term) ? null : $pricing->term);
        $option->period = (empty($pricing->period) ? '' : $pricing->period);
        $option->setup_fee = (empty($pricing->setup_fee) ? 0 : $pricing->setup_fee);
        $option->cancel_fee = (empty($pricing->cancel_fee) ? 0 : $pricing->cancel_fee);
        $option->currency = (empty($pricing->currency) ? '' : $pricing->currency);
        $option->price = (empty($pricing->price) ? 0 : $pricing->price);
        $option->price_renews = (isset($pricing->price_renews) ? $pricing->price_renews : null);
        $option->price_transfer = (isset($pricing->price_transfer) ? $pricing->price_transfer : null);

        return $option;
    }

    /**
     * Retrieves a list of service options keyed by ID
     *
     * @param ItemCollection A collection of items representing package options
     * @return array An array of package options keyed by ID
     */
    private function keyOptions(ItemCollection $options)
    {
        $packageOptions = [];

        // Create a list of package options keyed by ID
        foreach ($options as $option) {
            $fields = $option->getFields();

            if (isset($fields->id)) {
                $packageOptions[$fields->id] = $option;
            }
        }

        return $packageOptions;
    }
}
