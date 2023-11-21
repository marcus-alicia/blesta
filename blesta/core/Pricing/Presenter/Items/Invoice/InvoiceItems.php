<?php
namespace Blesta\Core\Pricing\Presenter\Items\Invoice;

use Blesta\Items\Item\ItemInterface;
use Blesta\Items\Collection\ItemCollection;

/**
 * Build invoice items
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Items.Invoice
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class InvoiceItems extends AbstractInvoiceItems
{
    /**
     * {@inheritdoc}
     */
    public function build(ItemInterface $invoice, ItemCollection $lines)
    {
        // Create a collection of all of the items
        $itemPriceCollection = $this->pricingFactory->itemPriceCollection();

        // Make item prices out of the invoice line items and apply taxes to them
        $items = $this->setTaxes($this->makeItems($invoice, $lines));

        // Add the line items to the collection
        foreach ($items as $item) {
            $itemPriceCollection->append($item);
        }

        return $itemPriceCollection;
    }

    /**
     * Creates a set of MetaItemPrices for the service
     *
     * @param ItemInterface $invoice An item representing the invoice
     * @param ItemCollection $lines A list of line items for the invoice
     * @return array An array of MetaItemPrices
     */
    private function makeItems(ItemInterface $invoice, ItemCollection $lines)
    {
        // Determine the invoice line items
        $invoiceFields = $invoice->getFields();

        // Determine line item ID
        $invoiceId = (empty($invoiceFields->id) ? null : $invoiceFields->id);

        $items = [];
        foreach ($lines as $item) {
            $line = $item->getFields();
            $lineId = (empty($line->id) ? '000' : $line->id);

            // Create the line item data
            $items[] = [
                'price' => empty($line->price) ? 0 : $line->price,
                'qty' => (empty($line->qty) ? 0 : $line->qty),
                'key' => 'invoice-' . $invoiceId . '-' . $lineId,
                'description' => (empty($line->description) ? '' : $line->description),
                'meta' => [
                    '_data' => [
                        'type' => 'invoice',
                        'item_type' => 'line',
                        'invoice_id' => $invoiceId,
                        'service_id' => (empty($line->service_id) ? null : $line->service_id),
                        'line_item_id' => $lineId
                    ],
                    'line' => $line
                ]
            ];
        }

        return $this->makeMetaItemPrices($items);
    }
}
