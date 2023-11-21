<?php
namespace Blesta\Core\Pricing\Presenter\Items\Service;

use Blesta\Items\Item\ItemInterface;
use Blesta\Items\Collection\ItemCollection;
use Configure;

/**
 * Build service items
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Items.Service
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ServiceItems extends AbstractServiceItems
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
        if (!isset($this->Currencies)) {
            \Loader::loadModels($this, ['Currencies']);
        }

        // Create a collection of all of the items
        $itemPriceCollection = $this->pricingFactory->itemPriceCollection();

        // Make item prices out of the service and service options
        $serviceItems = array_merge(
            $this->makeItems($service, $pricing, $package),
            $this->makeOptionItems($options, $package)
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
     * Creates a set of MetaItemPrices for the service
     *
     * @param ItemInterface $service An item representing the service
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

        // Determine item price info
        $price = (empty($serviceFields->price) ? 0 : $serviceFields->price);
        $overridePrice = (empty($serviceFields->override_price) ? 0 : $serviceFields->override_price);
        $priceRenews = (isset($serviceFields->price_renews) ? $serviceFields->price_renews : null);
        $priceTransfer = (isset($serviceFields->price_transfer) ? $serviceFields->price_transfer : null);
        $qty = (empty($serviceFields->qty) ? 0 : $serviceFields->qty);
        $currency = (empty($serviceFields->currency) ? null : $serviceFields->currency);
        $serviceId = (empty($serviceFields->id) ? null : $serviceFields->id);
        $packageId = (isset($packageFields->id) ? $packageFields->id : null);

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
                ['item_type' => ($settings->item_type ?? 'service'), 'type' => null, 'service_id' => $serviceId],
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
                'currency' => $settings->service_currency ?? $currency
            ]
        ];

        if (isset($settings->cycles) && $settings->cycles > 0) {
            $prorateFields['prorate']->term = $prorateFields['prorate']->term * $settings->cycles;
        }

        $itemPrice = empty($overridePrice)
            ? (isset($settings->recur) && $settings->recur && $priceRenews !== null ? $priceRenews : $price)
            : $overridePrice;
        $itemPrice = (
            isset($settings->transfer) && $settings->transfer && $priceTransfer !== null && empty($overridePrice)
                ? $priceTransfer
                : $itemPrice
        );

        if (isset($settings->cycles) && $settings->cycles > 0) {
            $itemPrice = $itemPrice * $settings->cycles;
        }

        // Convert the service price to the configured currency
        if ($currency
            && isset($settings->service_currency)
            && $currency != $settings->service_currency
        ) {
            $itemPrice = $this->Currencies->convert(
                $itemPrice,
                $currency,
                $settings->service_currency,
                Configure::get('Blesta.company_id')
            );
            $prorateFields['prorate']->currency = $settings->service_currency;
        }

        $items[] = [
            'price' => $itemPrice,
            'qty' => $qty,
            'key' => $this->getKey('service', 'item', $serviceId),
            'meta' => array_merge(
                $fields,
                $prorateFields,
                ['_data' => array_merge($fields['_data'], ['type' => 'service'])]
            )
        ];

        // Create an item price for the setup and cancel fees if there is any fee
        if ($settings->includeSetupFees && $setupFee != 0) {
            $items[] = [
                'price' => $setupFee,
                'qty' => 1,
                'key' => $this->getKey('service', 'setup', $serviceId),
                'meta' => array_merge($fields, ['_data' => array_merge($fields['_data'], ['type' => 'setup'])])
            ];
        }

        if ($settings->includeCancelFees && $cancelFee != 0) {
            $items[] = [
                'price' => $cancelFee,
                'qty' => 1,
                'key' => $this->getKey('service', 'cancel', $serviceId),
                'meta' => array_merge($fields, ['_data' => array_merge($fields['_data'], ['type' => 'cancel'])])
            ];
        }

        return $this->makeMetaItemPrices($items);
    }

    /**
     * Creates a set of MetaItemPrices for each valid service option
     *
     * @param ItemCollection $options A collection representing the service options
     * @param ItemInterface $package An item representing the package
     * @return array An array of MetaItemPrices
     */
    private function makeOptionItems(ItemCollection $options, ItemInterface $package)
    {
        $itemPrices = [];
        $settings = $this->options->getFields();
        $packageFields = $package->getFields();
        $systemSettings = $this->settings->getFields();
        $showDates = (isset($systemSettings->inv_lines_verbose_option_dates)
            && $systemSettings->inv_lines_verbose_option_dates == 'true'
        );

        // Create new item prices for each config option
        foreach ($options as $option) {
            // Determine the service price, setup fee, and cancel fee
            $optionFields = $option->getFields();

            $price = (empty($optionFields->price) ? 0 : $optionFields->price);
            $priceRenews = (isset($optionFields->price_renews) ? $optionFields->price_renews : null);
            $priceTransfer = (isset($optionFields->price_transfer) ? $optionFields->price_transfer : null);
            $qty = (empty($optionFields->qty) ? 0 : $optionFields->qty);
            $serviceId = (empty($optionFields->service_id) ? null : $optionFields->service_id);
            $optionId = (empty($optionFields->id) ? null : $optionFields->id);

            $setupFee = (empty($optionFields->setup_fee) ? 0 : $optionFields->setup_fee);
            $cancelFee = (empty($optionFields->cancel_fee) ? 0 : $optionFields->cancel_fee);

            // Items of no quantity are not included
            $items = [];
            if ($qty === 0) {
                continue;
            }

            // Create fields for a meta item to store with the item price
            $fields = [
                '_data' => array_merge(
                    [
                        'item_type' => 'option',
                        'type' => null,
                        'service_id' => $serviceId,
                        'option_id' => $optionId,
                        'show_dates' => $showDates
                    ],
                    $this->getDateRange($optionFields)
                ),
                'option' => $optionFields,
                'package' => $packageFields
            ];
            $prorateFields = [
                'prorate' => (object)[
                    'startDate' => (empty($settings->prorateStartDate) ? null : $settings->prorateStartDate),
                    'endDate' => (empty($settings->prorateEndDate) ? null : $settings->prorateEndDate),
                    'prorataDay' => (empty($packageFields->prorata_day) ? null : $packageFields->prorata_day),
                    'term' => (empty($optionFields->term) ? null : $optionFields->term),
                    'period' => (empty($optionFields->period) ? '' : $optionFields->period),
                    'currency' => (empty($optionFields->currency) ? '' : $optionFields->currency)
                ]
            ];

            if (isset($settings->cycles) && $settings->cycles > 0) {
                $prorateFields['prorate']->term = $prorateFields['prorate']->term * $settings->cycles;
            }

            $itemPrice = isset($settings->recur) && $settings->recur && $priceRenews !== null
                ? $priceRenews
                : $price;
            $itemPrice = isset($settings->transfer) && $settings->transfer && $priceTransfer !== null
                ? $priceTransfer
                : $itemPrice;

            if (isset($settings->cycles) && $settings->cycles > 0) {
                $itemPrice = $itemPrice * $settings->cycles;
            }

            // Convert the option price to the configured currency
            if (isset($settings->option_currency)
                && isset($optionFields->currency)
                && $settings->option_currency != $optionFields->currency
            ) {
                $itemPrice = $this->Currencies->convert(
                    $itemPrice,
                    $optionFields->currency,
                    $settings->option_currency,
                    Configure::get('Blesta.company_id')
                );
                $prorateFields['prorate']->currency = $settings->option_currency;
            }

            $items[] = [
                'price' => $itemPrice,
                'qty' => $qty,
                'key' => $this->getKey('serviceoption', 'item', $serviceId, $optionId),
                'meta' => array_merge(
                    $fields,
                    $prorateFields,
                    ['_data' => array_merge($fields['_data'], ['type' => 'option'])]
                )
            ];

            // Create an item price for the setup fee if there is any
            if ($settings->includeSetupFees && $setupFee != 0) {
                $items[] = [
                    'price' => $setupFee,
                    'qty' => 1,
                    'key' => $this->getKey('serviceoption', 'setup', $serviceId, $optionId),
                    'meta' => array_merge($fields, ['_data' => array_merge($fields['_data'], ['type' => 'setup'])])
                ];
            }

            if ($settings->includeCancelFees && $cancelFee != 0) {
                $items[] = [
                    'price' => $cancelFee,
                    'qty' => 1,
                    'key' => $this->getKey('serviceoption', 'cancel', $serviceId, $optionId),
                    'meta' => array_merge($fields, ['_data' => array_merge($fields['_data'], ['type' => 'cancel'])])
                ];
            }

            // Append each item price
            $itemPrices = array_merge($itemPrices, $this->makeMetaItemPrices($items));
        }

        return $itemPrices;
    }
}
