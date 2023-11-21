<?php
namespace Blesta\Core\Pricing\Presenter\Items\Invoice;

use Blesta\Items\Item\ItemInterface;
use Blesta\Items\Collection\ItemCollection;

/**
 * Build invoice data items
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Items.Invoice
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class InvoiceDataItems extends AbstractInvoiceItems
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
        // Determine the current tax settings
        $settings = $this->settings->getFields();
        $taxCascade = isset($settings->cascade_tax) && $settings->cascade_tax == 'true';
        $taxExempt = isset($settings->tax_exempt) && $settings->tax_exempt == 'true';

        // Create a set of line item taxes to set for each line item from those provided
        $taxes = [];
        foreach ($this->taxes as $taxRule) {
            // Set the tax rule, whether it cascades, and whether it should be subtracted
            $tax = $taxRule->getFields();
            $tax->cascade = ($taxCascade ? '1' : '0');
            $tax->subtract = ($taxExempt && $tax->type == 'inclusive_calculated' ? '1' : '0');
            $taxes[] = $tax;
        }

        $i = 0;
        $items = [];
        foreach ($lines as $item) {
            $line = $item->getFields();

            // Add taxes to the line item
            if (isset($line->tax) && ($line->tax === 'true' || $line->tax === true)) {
                $line->taxes = $taxes;
            }

            // Create the line item data
            $items[] = [
                'price' => empty($line->price) ? 0 : $line->price,
                'qty' => (empty($line->qty) ? 0 : $line->qty),
                'key' => 'line-' . $i,
                'description' => (empty($line->description) ? '' : $line->description),
                'meta' => [
                    '_data' => [
                        'type' => 'invoice',
                        'item_type' => 'line',
                        'service_id' => (empty($line->service_id) ? null : $line->service_id)
                    ],
                    'line' => $line
                ]
            ];

            $i++;
        }

        return $this->makeMetaItemPrices($items);
    }

    /**
     * {@inheritdoc}
     */
    protected function setTaxes(array $itemPrices)
    {
        // Determine the current tax settings
        $settings = $this->settings->getFields();
        $taxOn = isset($settings->enable_tax) && $settings->enable_tax == 'true';

        ##
        # TODO Replace the tax exempt process
        ##

        // No tax is to be incurred
        if (!$taxOn) {
            return $itemPrices;
        }

        // Set the taxes
        return parent::setTaxes($itemPrices);
    }
}
