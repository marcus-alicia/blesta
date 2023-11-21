<?php
namespace Blesta\Core\Pricing\Modifier\Type\Description;

use Blesta\Items\Collection\ItemCollection;
use Blesta\Pricing\Collection\ItemPriceCollection;
use Blesta\Core\Pricing\MetaItem\Meta;
use Blesta\Core\Pricing\MetaItem\MetaItemInterface;
use Minphp\Date\Date;

/**
 * A descriptor that updates all items in an ItemPriceCollection for MetaItemPrices to set
 * a description based on the item's meta information
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Modifier.Type.Description
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Description
{
    // Include the Meta trait for meta methods
    use Meta;

    /**
     * @var Minphp\Date\Date An instance of the Date object
     */
    private $date;

    /**
     * @var array An array of options used to construct the presenter
     */
    private $options;

    /**
     * @var DescriptionFactory An instance of the Description Factory
     */
    private $factory;

    /**
     * Init
     *
     * @param \Minphp\Date\Date An instance of the Date object
     * @param array An instance of the ItemCollection object
     */
    public function __construct(Date $date, array $options = [])
    {
        $this->date = $date;
        $this->options = $options;
        $this->factory = $this->getFactory();
    }

    /**
     * Creates a new instance of the DescriptionFactory
     *
     * @return Blesta\Core\Pricing\Modifier\Type\Description\DescriptionFactory
     */
    private function getFactory()
    {
        return new DescriptionFactory($this->date, $this->options);
    }

    /**
     * Sets a description on all items in the collection
     *
     * @param Blesta\Pricing\Collection\ItemPriceCollection $collection
     * @return Blesta\Pricing\Collection\ItemPriceCollection The updated collection
     */
    public function describe(ItemPriceCollection $collection)
    {
        // Update all items in the collection to set descriptions
        $this->setDescriptions($collection);
        $this->setDescriptions($collection->discounts());
        $this->setDescriptions($collection->taxes());

        return $collection;
    }

    /**
     * Updates all of the given MetaItems to set a description
     *
     * @param array|Iterator $items A list of items that may be iterated over
     */
    private function setDescriptions($items)
    {
        // Set descriptions for each item
        foreach ($items as $item) {
            // Skip item prices that have no meta data
            if (!($item instanceof MetaItemInterface)) {
                continue;
            }

            // Set the item's description
            $newDescription = $this->getDescription($this->getMeta($item));

            if (!empty($newDescription)) {
                $item->setDescription($newDescription);
            }
        }
    }

    /**
     * Builds a description from the given meta data of an item
     *
     * @param array $meta An array of meta data representing the item
     * @param array $oldMeta An array of old meta data representing the item (optional, only used
     *  to combine the two sets of meta data into a single description)
     * @return string The description
     */
    public function getDescription(array $meta, array $oldMeta = null)
    {
        $description = '';

        if (!isset($meta['_data']) || !isset($meta['_data']['item_type'])) {
            return $description;
        }

        // Build the descriptions
        switch ($meta['_data']['item_type']) {
            case 'service':
                $serviceDescription = $this->factory->service();
                $description = $serviceDescription->get($meta, $oldMeta);
                break;
            case 'domain':
                $domainDescription = $this->factory->domain();
                $description = $domainDescription->get($meta, $oldMeta);
                break;
            case 'option':
                $optionDescription = $this->factory->option();
                $description = $optionDescription->get($meta, $oldMeta);
                break;
            case 'discount':
                $discountDescription = $this->factory->discount();
                $description = $discountDescription->get($meta, $oldMeta);
                break;
            case 'tax':
                $taxDescription = $this->factory->tax();
                $description = $taxDescription->get($meta, $oldMeta);
                break;
        }

        return $description;
    }
}
