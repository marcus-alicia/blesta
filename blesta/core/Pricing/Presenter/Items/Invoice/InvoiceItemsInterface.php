<?php
namespace Blesta\Core\Pricing\Presenter\Items\Invoice;

use Blesta\Items\Item\ItemInterface;
use Blesta\Items\Collection\ItemCollection;

/**
 * Invoice item builder interface
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Items.Invoice
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface InvoiceItemsInterface
{
    /**
     * Builds invoice information into an ItemPriceCollection
     *
     * @param ItemInterface $invoice An item representing the invoice
     * @param ItemCollection $lines A collection of items representing line items
     * @return Blesta\Pricing\Collection\ItemPriceCollection
     */
    public function build(ItemInterface $invoice, ItemCollection $lines);
}
