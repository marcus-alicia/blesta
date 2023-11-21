<?php
namespace Blesta\Core\Pricing\Presenter\Collection;

use Blesta\Pricing\Collection\ItemPriceCollection;
use Blesta\Core\Pricing\MetaItem\MetaItemInterface;

/**
 * Trait to fetch discounts from an ItemPriceCollection
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Collection
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
trait CollectionDiscounts
{
    /**
     * Retrieves the discounts for all items in the collection
     *
     * @param ItemPriceCollection $collection The collection from which to fetch all discounts
     * @return array An array of stdClass objects representing each discount, including:
     *
     *  - description The discount description
     *  - amount The discount amount
     *  - type The discount type
     *  - total The total amount actually discounted
     */
    protected function getDiscounts(ItemPriceCollection $collection)
    {
        $discount_items = [];

        foreach ($collection->discounts() as $discount) {
            // Fetch meta information from the item
            $meta = [];
            if ($discount instanceof MetaItemInterface) {
                // Merge each meta item by key
                foreach ($discount->meta() as $data) {
                    $meta = array_merge($meta, (array)$data->getFields());
                }
            }

            $discount_items[] = (object)[
                'id' => (isset($meta['_data']['coupon_id']) ? $meta['_data']['coupon_id'] : null),
                'description' => $discount->getDescription(),
                'amount' => $discount->amount(),
                'type' => $discount->type(),
                'total' => $collection->discountAmount($discount)
            ];
        }
        $collection->resetDiscounts();

        return $discount_items;
    }
}
