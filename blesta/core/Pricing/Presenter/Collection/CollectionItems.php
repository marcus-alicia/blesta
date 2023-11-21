<?php
namespace Blesta\Core\Pricing\Presenter\Collection;

use Blesta\Pricing\Collection\ItemPriceCollection;
use Blesta\Pricing\Modifier\TaxPrice;

/**
 * Trait to fetch items from an ItemPriceCollection
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Collection
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
trait CollectionItems
{
    /**
     * Retrieves a list of all item prices in the collection
     *
     * @param ItemPriceCollection $collection The collection from which to fetch all items
     * @return array An array of stdClass objects representing each item, including:
     *
     *  - description The item description
     *  - price The item unit price
     *  - qty The item quantity
     *  - subtotal The item subtotal
     *  - total The item total
     *  - total_after_tax The item total including tax
     *  - total_after_discount The item total after discount
     *  - tax_amount The total item tax
     *  - discount_amount The total item discount
     */
    protected function getItems(ItemPriceCollection $collection)
    {
        // Set item information that does not require discounts to be reset
        $all_items = [];
        foreach ($collection as $key => $item) {
            $all_items[$key] = (object)[
                'description' => $item->getDescription(),
                'price' => $item->price(),
                'qty' => $item->qty(),
                'subtotal' => $item->excludeTax('inclusive_calculated')->subtotal()
            ];
        }
        $collection->resetTaxes();
        $collection->resetDiscounts();

        // Determine each total amount individually from the collection, as discounts
        // may apply to multiple items in the collection.
        // Thus, discounts must be reset at the collection level rather than at the item
        // level to avoid erroneously applying discount amounts
        foreach ($collection as $key => $item) {
            $all_items[$key]->total = $item->total();
        }
        $collection->resetTaxes();
        $collection->resetDiscounts();

        foreach ($collection as $key => $item) {
            $all_items[$key]->total_after_tax = $item->totalAfterTax();
        }
        $collection->resetTaxes();
        $collection->resetDiscounts();

        foreach ($collection as $key => $item) {
            $all_items[$key]->total_after_discount = $item->totalAfterDiscount();
        }
        $collection->resetDiscounts();

        foreach ($collection as $key => $item) {
            $all_items[$key]->tax_amount = $item->taxAmount();
        }
        $collection->resetDiscounts();

        foreach ($collection as $key => $item) {
            $all_items[$key]->discount_amount = $item->discountAmount();

            // Include fields for discounts and taxes per item
            $all_items[$key]->discounts = [];
            $all_items[$key]->taxes = [];
        }
        $collection->resetDiscounts();

        foreach ($collection->discounts() as $discount) {
            foreach ($collection as $key => $item) {
                if (in_array($discount, $item->discounts(), true)) {
                    // Discounts have a negative total
                    $all_items[$key]->discounts[] = (object)[
                        'description' => $discount->getDescription(),
                        'amount' => $discount->amount(),
                        'type' => $discount->type(),
                        'total' => -1 * $item->discountAmount($discount)
                    ];
                }
            }
        }
        $collection->resetDiscounts();

        foreach ($collection->taxes() as $tax) {
            foreach ($collection as $key => $item) {
                if (in_array($tax, $item->taxes(), true)) {
                    $all_items[$key]->taxes[] = (object)[
                        'description' => $tax->getDescription(),
                        'amount' => $tax->amount(),
                        'type' => $tax->type(),
                        'total' => $item->taxAmount($tax)
                    ];
                }
            }
        }
        $collection->resetDiscounts();

        return array_values($all_items);
    }
}
