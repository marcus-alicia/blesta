<?php
namespace Blesta\Core\Pricing\MetaItem;

use Blesta\Items\Collection\ItemCollection;
use Blesta\Items\Item\ItemInterface;
use Blesta\Pricing\Type\ItemPrice;

/**
 * ItemPrice supporting meta information
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.MetaItem
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class MetaItemPrice extends ItemPrice implements MetaItemInterface
{
    /**
     * @var ItemCollection List of attached items
     */
    private $item_collection;

    /**
     * Override the constructor
     *
     * {@inheritdoc}
     */
    public function __construct($price, $qty = 1, $key = null)
    {
        parent::__construct($price, $qty, $key);

        $this->item_collection = new ItemCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function attach(ItemInterface $item)
    {
        $this->item_collection->append($item);
    }

    /**
     * {@inheritdoc}
     */
    public function detach(ItemInterface $item)
    {
        $this->item_collection->remove($item);
    }

    /**
     * {@inheritdoc}
     */
    public function meta()
    {
        return $this->item_collection;
    }
}
