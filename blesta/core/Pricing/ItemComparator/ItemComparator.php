<?php
namespace Blesta\Core\Pricing\ItemComparator;

use Blesta\Core\Pricing\ItemComparator\AbstractItemComparator;
use Blesta\Core\Pricing\MetaItem\MetaItemPrice;
use Blesta\Pricing\Type\ItemPrice;

/**
 * Item Comparator for MetaItemPrices
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.ItemComparator
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ItemComparator extends AbstractItemComparator
{
    /**
     * {@inheritdoc}
     */
    public function merge(ItemPrice $item1, ItemPrice $item2)
    {
        // Create a new meta item price.
        // The new item has 1 quantity, re-uses the new key, and is set a new price
        $item = new MetaItemPrice($this->price($item1, $item2), 1, $item2->key());

        // The new item's description is determined by the caller's callback on the meta data
        $description = call_user_func(
            $this->description_callback,
            ($item1 instanceof MetaItemPrice ? $item1->meta() : null),
            ($item2 instanceof MetaItemPrice ? $item2->meta() : null)
        );

        // Default an empty description to the second item's descrption
        if (empty($description)) {
            $description = $item2->getDescription();
        }

        $item->setDescription($description);

        // Set the same discounts and taxes as the to item
        foreach ($item2->discounts() as $discount) {
            $item->setDiscount($discount);
        }

        // Set each group of taxes exactly as they are set on the to item
        foreach ($item2->taxes(false) as $tax_group) {
            call_user_func_array([$item, 'setTax'], $tax_group);
        }

        // Attach all the same meta items if this is a MetaItemPrice object
        if ($item2 instanceof MetaItemPrice) {
            foreach ($item2->meta() as $meta_item) {
                $item->attach($meta_item);
            }
        }

        return $item;
    }

    /**
     * Retrieves the combined price of the given items
     *
     * @param ItemPrice $item1 The from item
     * @param ItemPrice $item2 The to item
     * @return float The combined price
     */
    private function price(ItemPrice $item1, ItemPrice $item2)
    {
        // The from price (being removed) should be the combined quantity and price without
        // taxes or discounts, as those are applied to the item
        $from_price = $item1->qty() * $item1->price();

        // The to price (being added) should be the combined quantity and price without
        // taxes or discounts, as those are applied to the item
        $to_price = $item2->qty() * $item2->price();

        // Use the callback to determine the price based on the current prices and item meta
        return call_user_func(
            $this->price_callback,
            $from_price,
            $to_price,
            ($item1 instanceof MetaItemPrice ? $item1->meta() : null),
            ($item2 instanceof MetaItemPrice ? $item2->meta() : null)
        );
    }
}
