<?php
namespace Blesta\Core\Pricing\Presenter\Build\Invoice;

use stdClass;

/**
 * Invoice builder
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Build.Invoice
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class InvoiceBuilder extends AbstractInvoiceBuilder
{
    /**
     * Retrieve an InvoicePresenter
     *
     * @param stdClass $invoice An stdClass object representing the invoice, including:
     *  - id The invoice ID
     *  - id_code The invoice ID code
     *  - client_id The ID of the client the invoice is associated with
     *  - date_billed The date the invoice is billed for
     *  - date_due The date the invoice is due on
     *  - autodebit 1 or 0, whether the invoice can be autodebited
     *  - status The invoice status (e.g. 'active', 'proforma', etc.)
     *  - currency The ISO 4217 3-character currency code
     *  - line_items An array of stdClass objects representing each line item
     *      - id The line item ID
     *      - service_id The ID of the service this line item correlates to, if any
     *      - description The line item description
     *      - qty The line item quantity
     *      - amount The line item unit cost
     *      - taxes An array of stdClass objects representing each tax rules for this line item
     *          - id The tax rule ID
     *          - level The tax level (1 or 2)
     *          - name The name of the tax rule
     *          - amount The tax rule amount
     *          - type The tax type ('inclusive' or 'exclusive')
     *          - country The tax rule country the rule applies to
     *          - state The tax rule state/province the rule applies to
     *          - status The status of the tax rule (e.g. 'active')
     *          - cascade 1 or 0, whether or not tax rules cascade
     *          - subtract 1 or 0, whether or not to subtract the tax for a rule
     *  - meta An array of stdClass objects representing each meta key/value pair
     *      - key The meta key
     *      - value The meta value
     * @return InvoicePresenter An instance of the InvoicePresenter
     */
    public function build(stdClass $invoice)
    {
        // Set the builder settings to the pricing factory
        $pricingFactoryOptions = [];
        if (method_exists($this->pricingFactory, 'getOptions')) {
            $pricingFactoryOptions = $this->pricingFactory->getOptions();
        }
        $this->pricingFactory->options(array_merge((array) $this->options, (array) $pricingFactoryOptions));

        // Initialize the invoice items with settings, taxes, and discounts
        $settingsFormatter = $this->formatFactory->settings();
        $invoiceItems = $this->serviceFactory->invoice(
            $this->pricingFactory,
            $this->itemFactory,
            $this->formatSettings($settingsFormatter),
            $this->formatTaxes($this->formatFactory->tax(), $this->itemFactory->itemCollection()),
            $this->formatOptions($settingsFormatter)
        );

        // Format the invoice into consistent fields
        $data = $this->format($invoice);

        // Build the ItemPriceCollection from the invoice items
        $description = $this->pricingFactory->description();
        $collection = $description->describe($invoiceItems->build($data->invoice, $data->lines));

        return $this->presenterFactory->invoice($collection);
    }

    /**
     * Formats the invoice data into consistent items
     *
     * @param stdClass $invoice The invoice
     * @return stdClass An stdClass of formatted items representing the invoice
     */
    private function format(stdClass $invoice)
    {
        // Set the service formatters
        $invoiceFormatter = $this->formatFactory->invoice();

        // Format each line item as its own item
        $lines = $this->itemFactory->itemCollection();
        if (property_exists($invoice, 'line_items') && is_array($invoice->line_items)) {
            $lineFormatter = $this->formatFactory->invoiceLine();

            foreach ($invoice->line_items as $line) {
                $lines->append($lineFormatter->formatService((object)$line));
            }
        }

        return (object)[
            'invoice' => $invoiceFormatter->formatService($invoice),
            'lines' => $lines
        ];
    }
}
