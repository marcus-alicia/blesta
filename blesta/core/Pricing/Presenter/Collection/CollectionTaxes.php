<?php
namespace Blesta\Core\Pricing\Presenter\Collection;

use Blesta\Pricing\Collection\ItemPriceCollection;
use Blesta\Core\Pricing\MetaItem\MetaItemInterface;

/**
 * Trait to fetch taxes from an ItemPriceCollection
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Collection
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
trait CollectionTaxes
{
    /**
     * Retrieves the taxes for all items in the collection
     *
     * @param ItemPriceCollection $collection The collection from which to fetch all taxes
     * @return array An array of stdClass objects representing each tax, including:
     *
     *  - description The tax description
     *  - amount The tax amount
     *  - type The tax type
     *  - total The total amount actually taxed
     */
    protected function getTaxes(ItemPriceCollection $collection)
    {
        $tax_items = [];

        foreach ($collection->taxes() as $tax) {
            // Fetch meta information from the item
            $meta = [];
            if ($tax instanceof MetaItemInterface) {
                // Merge each meta item by key
                foreach ($tax->meta() as $data) {
                    $meta = array_merge($meta, (array)$data->getFields());
                }
            }

            $tax_items[] = (object)[
                'id' => (isset($meta['_data']['tax_id']) ? $meta['_data']['tax_id'] : null),
                'description' => $tax->getDescription(),
                'amount' => $tax->amount(),
                'type' => $tax->type(),
                'total' => $collection->taxAmount($tax)
            ];
        }
        $collection->resetDiscounts();

        return $tax_items;
    }
}
