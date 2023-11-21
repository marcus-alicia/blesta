<?php
namespace Blesta\Core\Pricing\Presenter\Type;

/**
 * Presenter interface
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Type
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface PresenterInterface
{
    /**
     * Retrieves the collection used by the presenter
     *
     * @return ItemPriceCollection The instance of the ItemPriceCollection
     */
    public function collection();

    /**
     * Retrieves the discounts for all items in the collection
     *
     * @return array An array of stdClass objects representing each discount, including:
     *
     *  - description The discount description
     *  - amount The discount amount
     *  - type The discount type
     *  - total The total amount actually discounted
     */
    public function discounts();

    /**
     * Retrieves a set of all items in the collection
     *
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
    public function items();

    /**
     * Retrieves the taxes for all items in the collection
     *
     * @return array An array of stdClass objects representing each tax, including:
     *
     *  - description The tax description
     *  - amount The tax amount
     *  - type The tax type
     *  - total The total amount actually taxed
     */
    public function taxes();

    /**
     * Retrieves totals for all items in the collection
     *
     * @return stdClass An stdClass of total information:
     *
     *  - subtotal The collection's subtotal
     *  - total The collection's total
     *  - total_after_tax The collection's total after tax
     *  - total_after_discount The collection's total after discount
     *  - tax_amount The collection's total tax
     *  - discount_amount The collection's total discount
     */
    public function totals();
}
