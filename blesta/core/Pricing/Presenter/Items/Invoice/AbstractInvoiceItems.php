<?php
namespace Blesta\Core\Pricing\Presenter\Items\Invoice;

use Blesta\Items\Item\ItemInterface;
use Blesta\Items\ItemFactory;
use Blesta\Items\Collection\ItemCollection;
use Blesta\Core\Pricing\PricingFactory;
use Blesta\Core\Pricing\MetaItem\Meta;

/**
 * Abstract builder for invoice items
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Items.Invoice
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractInvoiceItems implements InvoiceItemsInterface
{
    // Include the Meta trait for meta methods
    use Meta;

    /**
     * @var Instance of PricingFactory
     */
    protected $pricingFactory;
    /**
     * @var Instance of ItemFactory
     */
    protected $itemFactory;
    /**
     * @var ItemCollection A set of discounts
     */
    protected $discounts;
    /**
     * @var ItemInterface Custom options
     */
    protected $options;
    /**
     * @var ItemInterface Settings
     */
    protected $settings;
    /**
     * @var ItemCollection A set of taxes
     */
    protected $taxes;

    /**
     * Init
     *
     * @param PricingFactory $pricingFactory An instance of the PricingFactory
     * @param ItemFactory $itemFactory An instance of the ItemFactory
     * @param ItemInterface $settings An item containing a set of settings
     * @param ItemCollection $taxes A collection of items representing taxes
     * @param ItemInterface $options An item containing a set of custom options
     */
    public function __construct(
        PricingFactory $pricingFactory,
        ItemFactory $itemFactory,
        ItemInterface $settings,
        ItemCollection $taxes = null,
        ItemInterface $options = null
    ) {
        $this->pricingFactory = $pricingFactory;
        $this->itemFactory = $itemFactory;
        $this->settings = $settings;

        // Default to empty collections/items if null
        $this->taxes = ($taxes === null ? $this->itemFactory->itemCollection() : $taxes);
        $this->options = ($options === null ? $this->itemFactory->item() : $options);
    }

    /**
     * Creates a list of MetaItemPrices from a set of items
     *
     * @param array $items An array of items that each must contain:
     *  - price The price of the item
     *  - qty The quantity count of the item
     *  - key The item's key
     *  - description The item's description
     *  - meta Meta information about the item (optional)
     * @return An array of MetaItemPrice objects representing each item
     */
    protected function makeMetaItemPrices(array $items)
    {
        $itemPrices = [];

        foreach ($items as $item) {
            $itemPrice = $this->pricingFactory->metaItemPrice($item['price'], $item['qty'], $item['key']);
            $itemPrice->setDescription($item['description']);

            // Attach a meta item to the item price if given data
            if (!empty($item['meta'])) {
                $metaItem = $this->itemFactory->item();
                $metaItem->setFields($item['meta']);
                $itemPrice->attach($metaItem);
            }

            $itemPrices[] = $itemPrice;
        }

        return $itemPrices;
    }

    /**
     * Retrieves a list of MetaTaxPrice objects that may be applied
     *
     * @return array A list of MetaTaxPrice objects
     */
    private function getTaxes()
    {
        $taxes = [];

        // Get settings
        $settings = $this->settings->getFields();
        $taxExempt = isset($settings->tax_exempt) && $settings->tax_exempt == 'true';

        // Create new tax objects
        foreach ($this->taxes as $taxRule) {
            $data = $taxRule->getFields();

            // Create a new tax price for this tax rule
            $amount = (empty($data->tax) ? 0 : $data->tax);
            $type = (empty($data->type) ? null : $data->type);

            // inclusive_calculated taxes should be subtracted for tax exempt instead of being ignored
            $subtractedTax = ($taxExempt && $data->type == 'inclusive_calculated');

            // Skip tax rules amounting to nothing
            if ($amount == 0 || ($taxExempt && !$subtractedTax)) {
                continue;
            }

            // Create the tax price
            $taxTypes = ['exclusive', 'inclusive', 'inclusive_calculated'];
            $tax = $this->pricingFactory->metaTaxPrice(
                $amount,
                (in_array($type, $taxTypes) ? $type : 'exclusive'),
                $subtractedTax
            );

            // Create some meta information to more easily reference the tax
            $meta = [
                '_data' => [
                    'item_type' => 'tax',
                    'type' => 'tax',
                    'tax_id' => (empty($data->id) ? null : $data->id),
                    'subtracted' => $subtractedTax
                ],
                'tax' => $data
            ];

            // Attach the meta info to the tax
            $metaItem = $this->itemFactory->item();
            $metaItem->setFields($meta);
            $tax->attach($metaItem);

            $taxes[] = $tax;
        }

        return $taxes;
    }

    /**
     * Updates the given MetaItemPrices to assign taxes
     *
     * @param array $itemPrices An array of MetaItemPrices to assign taxes for
     * @return array The MetaTaxPrices with taxes assigned
     */
    protected function setTaxes(array $itemPrices)
    {
        $taxes = $this->getTaxes();

        // No taxes to apply
        if (empty($taxes)) {
            return $itemPrices;
        }

        // Create a set of valid taxes keyed by tax ID
        $taxIds = [];
        foreach ($taxes as $tax) {
            foreach ($tax->meta() as $taxItem) {
                $taxFields = $taxItem->getFields();
                $taxIds[$taxFields->_data['tax_id']] = $tax;
            }
        }

        foreach ($itemPrices as $itemPrice) {
            // Retrieve the item price meta information to reference
            $meta = $this->getMeta($itemPrice);

            // The line item must have taxes associated with it that match at least
            // one of the given tax rules, otherwise skip it
            if (empty($meta['line']) || empty($meta['line']->taxes)) {
                continue;
            }

            // Determine which taxes to assign to the item based on the line item taxes
            $cascade = [];
            $noCascade = [];
            foreach ($meta['line']->taxes as $tax) {
                // We need a valid tax ID in order to assign tax
                if (!isset($tax->id) || !array_key_exists($tax->id, $taxIds)) {
                    continue;
                }

                // Set whether the tax should be subtracted
                $taxIds[$tax->id]->subtract = $tax->subtract ?? 0;

                // Create a list of taxes that can or cannot cascade with one another for this line item
                if (isset($tax->cascade) && $tax->cascade == '1') {
                    $cascade[] = $taxIds[$tax->id];
                } else {
                    $noCascade[] = $taxIds[$tax->id];
                }
            }

            // Assign the tax prices to the item price
            if (!empty($cascade)) {
                // These taxes all cascade with one another
                call_user_func_array([$itemPrice, 'setTax'], $cascade);
            }

            if (!empty($noCascade)) {
                // Set all taxes that don't cascade individually
                foreach ($noCascade as $tax) {
                    $itemPrice->setTax($tax);
                }
            }
        }

        return $itemPrices;
    }
}
