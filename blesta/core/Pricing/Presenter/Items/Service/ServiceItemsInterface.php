<?php
namespace Blesta\Core\Pricing\Presenter\Items\Service;

use Blesta\Items\Item\ItemInterface;
use Blesta\Items\Collection\ItemCollection;

/**
 * Service item builder interface
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Items.Service
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface ServiceItemsInterface
{
    /**
     * Builds service information into an ItemPriceCollection
     *
     * @param ItemInterface $service An item representing the service
     * @param ItemInterface $package An item representing the package
     * @param ItemInterface $pricing An item representing the pricing
     * @param ItemCollection $options A collection representing the service options
     * @return Blesta\Pricing\Collection\ItemPriceCollection
     */
    public function build(
        ItemInterface $service,
        ItemInterface $package,
        ItemInterface $pricing,
        ItemCollection $options
    );
}
