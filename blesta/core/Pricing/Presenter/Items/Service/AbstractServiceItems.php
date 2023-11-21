<?php
namespace Blesta\Core\Pricing\Presenter\Items\Service;

use Blesta\Items\Item\ItemInterface;
use Blesta\Items\ItemFactory;
use Blesta\Items\Collection\ItemCollection;
use Blesta\Core\Pricing\PricingFactory;
use Blesta\Core\Pricing\MetaItem\Meta;
use Configure;
use stdClass;

/**
 * Abstract builder for service items
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Items.Service
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractServiceItems implements ServiceItemsInterface
{
    // Include the Meta trait for meta methods
    use Meta;

    /**
     * @var PricingFactory Instance of PricingFactory
     */
    protected $pricingFactory;
    /**
     * @var ItemFactory Instance of ItemFactory
     */
    protected $itemFactory;
    /**
     * @var ItemCollection A set of discounts
     */
    protected $discounts;
    /**
     * @var ItemInterface Custom options
     */
    protected $options;
    /**
     * @var ItemInterface Settings
     */
    protected $settings;
    /**
     * @var ItemCollection A set of taxes
     */
    protected $taxes;
    /**
     * @var string The item price keys for a service
     */
    private $serviceKeys = [
        'item' => 'service-%1$s',
        'setup' => 'service-%1$s-setup',
        'cancel' => 'service-%1$s-cancel'
    ];
    /**
     * @var string The item price keys for a service option
     */
    private $serviceOptionKeys = [
        'item' => 'service-%1$s-option-%2$s',
        'setup' => 'service-%1$s-option-%2$s-setup',
        'cancel' => 'service-%1$s-option-%2$s-cancel'
    ];
    /**
     * @var string The item price keys for a package
     */
    private $packageKeys = [
        'item' => 'package-%1$s',
        'setup' => 'package-%1$s-setup',
        'cancel' => 'package-%1$s-cancel'
    ];
    /**
     * @var string The item price keys for a package option
     */
    private $packageOptionKeys = [
        'item' => 'package-%1$s-option-%2$s',
        'setup' => 'package-%1$s-option-%2$s-setup',
        'cancel' => 'package-%1$s-option-%2$s-cancel'
    ];

    /**
     * Init
     *
     * @param PricingFactory $pricingFactory An instance of the PricingFactory
     * @param ItemFactory $itemFactory An instance of the ItemFactory
     * @param ItemInterface $settings An item containing a set of settings
     * @param ItemCollection $taxes A collection of items representing taxes
     * @param ItemCollection $discounts A collection of items representing discounts
     * @param ItemInterface $options An item containing a set of custom options
     */
    public function __construct(
        PricingFactory $pricingFactory,
        ItemFactory $itemFactory,
        ItemInterface $settings,
        ItemCollection $taxes = null,
        ItemCollection $discounts = null,
        ItemInterface $options = null
    ) {
        $this->pricingFactory = $pricingFactory;
        $this->itemFactory = $itemFactory;
        $this->settings = $settings;

        // Default to empty collections/items if null
        $this->taxes = ($taxes === null ? $this->itemFactory->itemCollection() : $taxes);
        $this->discounts = ($discounts === null ? $this->itemFactory->itemCollection() : $discounts);
        $this->options = ($options === null ? $this->itemFactory->item() : $options);

        // Apply default options
        $this->setDefaultOptions();
    }

    /**
     * Combines the default options with the given options
     */
    private function setDefaultOptions()
    {
        $now = date('c');
        $defaultOptions = [
            // Whether to include setup fee items
            'includeSetupFees' => false,
            // Whether to include cancel fee items
            'includeCancelFees' => false,
            // The date the items are being applied
            'applyDate' => $now,
            // The date the service term begins
            'startDate' => $now,
            // Whether these items are recurring
            'recur' => false,
            // Whether to use or not the transfer price
            'transfer' => false,
            // Whether to use or not the renewal price
            'renewal' => false,
            // The amount of terms the recurring service will be billed
            'cycles' => 1
        ];

        $this->options->setFields(array_merge($defaultOptions, (array)$this->options->getFields()));
    }

    /**
     * Creates a key using the given name and type
     *
     * @param string $name The name of the key to retrieve ('service' or 'serviceoption')
     * @param string $type The type of key ('item', 'setup', 'cancel')
     * @param mixed $... Values to substitute in the language result. Uses sprintf().
     */
    protected function getKey($name, $type)
    {
        // Determine the keys to use
        switch ($name) {
            case 'package':
                $keys = $this->packageKeys;
                break;
            case 'packageoption':
                $keys = $this->packageOptionKeys;
                break;
            case 'serviceoption':
                $keys = $this->serviceOptionKeys;
                break;
            case 'service':
            case 'domain':
                // No break
            default:
                $keys = $this->serviceKeys;
                break;
        }

        $output = (array_key_exists($type, $keys) ? $keys[$type] : '');

        // Use variable replacement of additional arguments on the key
        $argc = func_num_args();
        if ($argc > 2) {
            $args = array_slice(func_get_args(), 2, $argc - 1);

            // If printf args are passed as an array use those instead.
            if (is_array($args[0])) {
                $args = $args[0];
            }
            array_unshift($args, $output);

            $output = call_user_func_array('sprintf', $args);
        }

        return $output;
    }

    /**
     * Retrieves the service date range
     *
     * @param stdClass $pricing An stdClass object representing pricing, including:
     *
     *  - term The pricing term
     *  - period The pricing period
     * @return array A key => value array containing:
     *
     *  - startDate The start date of the service
     *  - endDate The end date of the service
     */
    protected function getDateRange(stdClass $pricing)
    {
        // Determine the start and end dates
        $date = $this->pricingFactory->date();

        $options = $this->options->getFields();
        $startDate = $date->cast($options->startDate, 'c');
        $endDate = null;

        // Calculate the end date based on the pricing term/period from the start date
        if ($startDate && !empty($pricing->period) && $pricing->period !== 'onetime'
            && !empty($pricing->term) && (int)$pricing->term > 0
        ) {
            // Determine the end date based on the pricing term and period from the start date
            $options = $this->options->getFields();
            $timezone = Configure::get('Blesta.company_timezone');
            $endDate = $date->modify(
                $startDate,
                '+' . ((int)$pricing->term * ($options->cycles ?? 1)) . ' ' . $pricing->period,
                'c',
                $timezone ? $timezone : null
            );
        }

        return [
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
    }

    /**
     * Creates a list of MetaItemPrices from a set of items
     *
     * @param array $items An array of items that each must contain:
     *
     *  - price The price of the item
     *  - qty The quantity count of the item
     *  - key The item's key
     *  - meta Meta information about the item (optional)
     * @return An array of MetaItemPrice objects representing each item
     */
    protected function makeMetaItemPrices(array $items)
    {
        $itemPrices = [];

        $settings = $this->settings->getFields();
        foreach ($items as $item) {
            $itemPrice = $this->pricingFactory->metaItemPrice($item['price'], $item['qty'], $item['key']);

            // Set whether this ItemPrice should have discounts apply to its tax
            $itemPrice->setDiscountTaxes(!isset($settings->discount_taxes) || $settings->discount_taxes == 'true');

            // Attach a meta item to the item price if given data
            if (!empty($item['meta'])) {
                $metaItem = $this->itemFactory->item();
                $metaItem->setFields($item['meta']);
                $itemPrice->attach($metaItem);
            }

            $itemPrices[] = $itemPrice;
        }

        return $itemPrices;
    }

    /**
     * Retrieves a list of MetaTaxPrice objects that may be applied
     *
     * @return array A list of MetaTaxPrice objects
     */
    private function getTaxes()
    {
        $taxes = [];

        $settings = $this->settings->getFields();

        // Determine the current tax settings
        $taxOn = isset($settings->enable_tax) && $settings->enable_tax == 'true';
        $taxExempt = isset($settings->tax_exempt) && $settings->tax_exempt == 'true';

        // No tax is to be incurred
        if (!$taxOn) {
            return $taxes;
        }

        // Create new tax objects
        foreach ($this->taxes as $taxRule) {
            $data = $taxRule->getFields();

            // Create a new tax price for this tax rule
            $amount = (empty($data->tax) ? 0 : $data->tax);
            $type = (empty($data->type) ? null : $data->type);

            // inclusive_calculated taxes should be subtracted for tax exempt instead of being ignored
            $subtractedTax = ($taxExempt && $data->type == 'inclusive_calculated');

            // Skip tax rules amounting to nothing
            if ($amount == 0 || ($taxExempt && !$subtractedTax)) {
                continue;
            }

            // Create the tax price
            $taxTypes = ['exclusive', 'inclusive', 'inclusive_calculated'];
            $tax = $this->pricingFactory->metaTaxPrice(
                $amount,
                (in_array($type, $taxTypes) ? $type : 'exclusive'),
                $subtractedTax
            );

            // Create some meta information to more easily reference the coupon
            $meta = [
                '_data' => [
                    'item_type' => 'tax',
                    'type' => 'tax',
                    'tax_id' => (empty($data->id) ? null : $data->id),
                    'subtracted' => $subtractedTax
                ],
                'tax' => $data
            ];

            // Attach the meta info to the tax
            $metaItem = $this->itemFactory->item();
            $metaItem->setFields($meta);
            $tax->attach($metaItem);

            $taxes[] = $tax;
        }

        return $taxes;
    }

    /**
     * Updates the given MetaItemPrices to assign taxes
     *
     * @param array $itemPrices An array of MetaItemPrices to assign taxes for
     * @return array The MetaTaxPrices with taxes assigned
     */
    protected function setTaxes(array $itemPrices)
    {
        $taxes = $this->getTaxes();

        // No taxes to apply
        if (empty($taxes)) {
            return $itemPrices;
        }

        // Determine the current tax settings
        $settings = $this->settings->getFields();

        $taxSetup = isset($settings->setup_fee_tax) && $settings->setup_fee_tax == 'true';
        $taxCancel = isset($settings->cancelation_fee_tax) && $settings->cancelation_fee_tax == 'true';
        $taxCascade = isset($settings->cascade_tax) && $settings->cascade_tax == 'true';

        // Set taxes as appropriate for each item
        foreach ($itemPrices as $itemPrice) {
            // Retrieve the item price meta information to reference
            $meta = $this->getMeta($itemPrice);

            // The package must be taxable, otherwise skip it since taxes don't apply
            if (empty($meta['package']) || empty($meta['package']->taxable)) {
                continue;
            }

            // Determine whether tax applies to the item/setup fee/cancel fee
            $taxItem = false;
            switch ($meta['_data']['type']) {
                case 'service':
                case 'domain':
                    // No break
                case 'package':
                    // No break
                case 'option':
                    // Tax applies to the item itself
                    $taxItem = true;
                    break;
                case 'setup':
                    $taxItem = $taxSetup;
                    break;
                case 'cancel':
                    $taxItem = $taxCancel;
                    break;
            }

            // Skip items that are untaxable
            if (!$taxItem) {
                continue;
            }

            // Assign the tax prices to the item price
            if ($taxCascade) {
                // The taxes all cascade with one another
                call_user_func_array([$itemPrice, 'setTax'], $taxes);
            } else {
                // Set all taxes individually
                foreach ($taxes as $tax) {
                    $itemPrice->setTax($tax);
                }
            }
        }

        return $itemPrices;
    }

    /**
     * Retrieves a list of available discounts
     *
     * @param array $packageIds One of the following:
     *
     *   - An array of package IDs that the discount must apply to
     *   - An array of package IDs mapped to periods and terms [packageID => [period => [term, term]]] that the discount
     *      must apply to
     * @return array An array of MetaDiscountPrice objects that may be applied
     */
    private function getDiscounts(array $packageIds)
    {
        $discounts = [];

        // Set valid coupon types
        $couponTypes = ['amount', 'percent'];

        $settings = $this->options->getFields();

        foreach ($this->discounts as $discount) {
            $discountFields = $discount->getFields();
            $discountFields->discounts = (isset($discountFields->discounts) ? (array)$discountFields->discounts : []);

            // Create a coupon to represent the discount
            $coupon = $this->pricingFactory->coupon($discount, $settings->applyDate);

            // The coupon must be active and apply to the packages
            if (!$coupon->active() || !$coupon->applies($packageIds, false, $settings->recur)) {
                continue;
            }

            // Determine whether this coupon applies to package options
            $applyToOptions = $coupon->applies($packageIds, true, $settings->recur);

            // Create a separate discount for each coupon currency
            foreach ($discountFields->discounts as $amount) {
                // We must have a currency with a coupon amount
                if (empty($amount->currency) || !($couponAmount = $coupon->amount($amount->currency))) {
                    continue;
                }

                // Create some meta information to more easily reference the coupon
                $meta = [
                    '_data' => [
                        'item_type' => 'discount',
                        'type' => 'coupon',
                        'coupon_id' => (empty($discountFields->id) ? null : $discountFields->id)
                    ],
                    'coupon' => $discountFields,
                    'coupon_amount' => (object)[
                        'currency' => $amount->currency,
                        'amount' => (empty($couponAmount['amount']) ? 0 : $couponAmount['amount']),
                        'type' => (!in_array($couponAmount['type'], $couponTypes) ? 'percent' : $couponAmount['type']),
                        'packages' => $coupon->packages(),
                        'applyToOptions' => $applyToOptions
                    ]
                ];

                // Create the discount price
                $discount = $this->pricingFactory->metaDiscountPrice(
                    $meta['coupon_amount']->amount,
                    $meta['coupon_amount']->type
                );

                // Attach the meta info to the discount
                $metaItem = $this->itemFactory->item();
                $metaItem->setFields($meta);
                $discount->attach($metaItem);

                // Order the discounts by currency
                if (!array_key_exists($amount->currency, $discounts)) {
                    $discounts[$amount->currency] = [];
                }

                $discounts[$amount->currency][] = $discount;
            }
        }

        return $discounts;
    }

    /**
     * Updates the given MetaItemPrices to assign discounts
     *
     * @param array $itemPrices An array of MetaItemPrices to assign discounts for
     * @param array $packageIds One of the following:
     *
     *   - An array of package IDs that the discount must apply to
     *   - An array of package IDs mapped to periods and terms [packageID => [period => [term, term]]] that the discount
     *      must apply to
     * @return array The MetaItemPrices with discounts assigned
     */
    protected function setDiscounts(array $itemPrices, array $packageIds)
    {
        $allDiscounts = $this->getDiscounts($packageIds);

        // No discounts to apply
        if (empty($allDiscounts)) {
            return $itemPrices;
        }

        // Set discounts as appropriate for each item
        foreach ($itemPrices as $itemPrice) {
            // Retrieve the item price meta information to reference
            $meta = $this->getMeta($itemPrice);

            // Determine the currency from the item price
            $currency = null;
            switch ($meta['_data']['item_type']) {
                case 'service':
                case 'domain':
                    $currency = (isset($meta['pricing']->currency) ? $meta['pricing']->currency : null);
                    break;
                case 'option':
                    $currency = (isset($meta['option']->currency) ? $meta['option']->currency : null);
                    break;
            }

            // Determine whether the discount applies to the item/setup fee/cancel fee
            $discounts = ($currency && isset($allDiscounts[$currency]) ? $allDiscounts[$currency] : []);

            foreach ($discounts as $discount) {
                // Get the discount meta
                $discountMeta = $this->getMeta($discount);

                // Determine whether this item price can be discounted with this discount
                $discountItem = false;
                switch ($meta['_data']['item_type']) {
                    case 'service':
                    case 'domain':
                        // Discount always applies to a service item that applies
                        $discountItem = true;
                        break;
                    case 'option':
                        // Discount applies to config options conditionally
                        $discountItem = ($discountMeta['_data']['type'] == 'coupon'
                            && isset($discountMeta['coupon_amount']->applyToOptions)
                            && $discountMeta['coupon_amount']->applyToOptions
                        );
                        break;
                }

                // Apply the discount to the item
                if ($discountItem) {
                    $itemPrice->setDiscount($discount);
                }
            }
        }

        return $itemPrices;
    }
}
