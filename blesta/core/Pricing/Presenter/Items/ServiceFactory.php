<?php
namespace Blesta\Core\Pricing\Presenter\Items;

use Blesta\Core\Pricing\Presenter\Items\Invoice\InvoiceItems;
use Blesta\Core\Pricing\Presenter\Items\Invoice\InvoiceDataItems;
use Blesta\Core\Pricing\Presenter\Items\Service\ServiceItems;
use Blesta\Core\Pricing\Presenter\Items\Service\ServiceDataItems;
use Blesta\Core\Pricing\PricingFactory;
use Blesta\Items\Item\ItemInterface;
use Blesta\Items\ItemFactory;
use Blesta\Items\Collection\ItemCollection;

/**
 * Instantiates service item objects
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Items
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ServiceFactory
{
    /**
     * Creates an instance of InvoiceItems
     *
     * @param Blesta\Core\Pricing\PricingFactory $pricingFactory An instance of the PricingFactory
     * @param Blesta\Items\ItemFactory $itemFactory An instance of the ItemFactory
     * @param Blesta\Items\Item\ItemInterface $settings An item containing a set of settings
     * @param Blesta\Items\Collection\ItemCollection $taxes A collection of items representing taxes
     * @param Blesta\Items\Item\ItemInterface $options An item containing a set of custom options
     * @return Blesta\Core\Pricing\Presenter\Items\Invoice\InvoiceItemsInterface
     */
    public function invoice(
        PricingFactory $pricingFactory,
        ItemFactory $itemFactory,
        ItemInterface $settings,
        ItemCollection $taxes = null,
        ItemInterface $options = null
    ) {
        return new InvoiceItems($pricingFactory, $itemFactory, $settings, $taxes, $options);
    }

    /**
     * Creates an instance of InvoiceDataItems
     *
     * @param Blesta\Core\Pricing\PricingFactory $pricingFactory An instance of the PricingFactory
     * @param Blesta\Items\ItemFactory $itemFactory An instance of the ItemFactory
     * @param Blesta\Items\Item\ItemInterface $settings An item containing a set of settings
     * @param Blesta\Items\Collection\ItemCollection $taxes A collection of items representing taxes
     * @param Blesta\Items\Item\ItemInterface $options An item containing a set of custom options
     * @return Blesta\Core\Pricing\Presenter\Items\Invoice\InvoiceItemsInterface
     */
    public function invoiceData(
        PricingFactory $pricingFactory,
        ItemFactory $itemFactory,
        ItemInterface $settings,
        ItemCollection $taxes = null,
        ItemInterface $options = null
    ) {
        return new InvoiceDataItems($pricingFactory, $itemFactory, $settings, $taxes, $options);
    }

    /**
     * Creates an instance of ServiceItems
     *
     * @param Blesta\Core\Pricing\PricingFactory $pricingFactory An instance of the PricingFactory
     * @param Blesta\Items\ItemFactory $itemFactory An instance of the ItemFactory
     * @param Blesta\Items\Item\ItemInterface $settings An item containing a set of settings
     * @param Blesta\Items\Collection\ItemCollection $taxes A collection of items representing taxes
     * @param Blesta\Items\Collection\ItemCollection $discounts A collection of items representing discounts
     * @param Blesta\Items\Item\ItemInterface $options An item containing a set of custom options
     * @return Blesta\Core\Pricing\Presenter\Items\Service\ServiceItemsInterface
     */
    public function service(
        PricingFactory $pricingFactory,
        ItemFactory $itemFactory,
        ItemInterface $settings,
        ItemCollection $taxes = null,
        ItemCollection $discounts = null,
        ItemInterface $options = null
    ) {
        return new ServiceItems($pricingFactory, $itemFactory, $settings, $taxes, $discounts, $options);
    }

    /**
     * Creates an instance of ServiceDataItems
     *
     * @param Blesta\Core\Pricing\PricingFactory $pricingFactory An instance of the PricingFactory
     * @param Blesta\Items\ItemFactory $itemFactory An instance of the ItemFactory
     * @param Blesta\Items\Item\ItemInterface $settings An item containing a set of settings
     * @param Blesta\Items\Collection\ItemCollection $taxes A collection of items representing taxes
     * @param Blesta\Items\Collection\ItemCollection $discounts A collection of items representing discounts
     * @param Blesta\Items\Item\ItemInterface $options An item containing a set of custom options
     * @return Blesta\Core\Pricing\Presenter\Items\Service\ServiceItemsInterface
     */
    public function serviceData(
        PricingFactory $pricingFactory,
        ItemFactory $itemFactory,
        ItemInterface $settings,
        ItemCollection $taxes = null,
        ItemCollection $discounts = null,
        ItemInterface $options = null
    ) {
        return new ServiceDataItems($pricingFactory, $itemFactory, $settings, $taxes, $discounts, $options);
    }
}
