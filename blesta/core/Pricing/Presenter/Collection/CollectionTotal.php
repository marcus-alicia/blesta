<?php
namespace Blesta\Core\Pricing\Presenter\Collection;

use Blesta\Pricing\Collection\ItemPriceCollection;
use Blesta\Pricing\Modifier\TaxPrice;

/**
 * Trait to fetch totals from an ItemPriceCollection
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Collection
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
trait CollectionTotal
{
    /**
     * Retrieves totals for all items in the collection
     *
     * @param ItemPriceCollection $collection The collection from which to fetch all totals
     * @return stdClass An stdClass of total information:
     *
     *  - subtotal The collection's subtotal
     *  - total The collection's total
     *  - total_without_exclusive_tax The collection's total without exclusive taxes
     *  - total_after_tax The collection's total after tax
     *  - total_after_discount The collection's total after discount
     *  - tax_amount The collection's total tax
     *  - discount_amount The collection's total discount
     */
    protected function getTotals(ItemPriceCollection $collection)
    {
        return (object)[
            'subtotal' => $collection->subtotal(),
            'total' => $collection->total(),
            'total_without_exclusive_tax' => $collection->excludeTax(TaxPrice::EXCLUSIVE)->total(),
            'total_after_tax' => $collection->totalAfterTax(),
            'total_after_discount' => $collection->totalAfterDiscount(),
            'tax_amount' => $collection->taxAmount(),
            'discount_amount' => $collection->discountAmount(),
        ];
    }
}
