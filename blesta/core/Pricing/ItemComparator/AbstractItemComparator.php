<?php
namespace Blesta\Core\Pricing\ItemComparator;

use Blesta\Pricing\Modifier\ItemComparatorInterface;

/**
 * Abstract Item Comparator for MetaItemPrices
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.ItemComparator
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractItemComparator implements ItemComparatorInterface
{
    /**
     * @var A callback method for fetching a price
     */
    protected $price_callback;
    /**
     * @var A callback method for fetching a description
     */
    protected $description_callback;

    /**
     * Initializes a set of callbacks
     *
     * @param callable $price_callback The pricing callback that accepts four
     *  arguments for the old and new price, and the old and new ItemPrice
     *  meta data (each a Blesta\Items\Item\ItemCollection), and returns a float
     * @param callable $description_callback The description callback that
     *  accepts two arguments for the old and new ItemPrice meta data (each
     *  a Blesta\Items\Item\ItemCollection or null), and returns a string
     */
    public function __construct(callable $price_callback, callable $description_callback)
    {
        $this->setPriceCallback($price_callback);
        $this->setDescriptionCallback($description_callback);
    }

    /**
     * Sets the pricing callback that expects four arguments:
     *
     *  - the old price as a float
     *  - the new price as a float
     *  - a Blesta\Items\Collection\ItemCollection of the old ItemPrice meta data, or null
     *  - a Blesta\Items\Collection\ItemCollection of the new ItemPrice meta data, or null
     * and returns a float value representing a price.
     *
     * @param callable $callback The callback
     */
    public function setPriceCallback(callable $callback)
    {
        $this->price_callback = $callback;
    }

    /**
     * Sets the pricing callback that expects two arguments:
     *
     *  - a Blesta\Items\Collection\ItemCollection of the old ItemPrice meta data, or null
     *  - a Blesta\Items\Collection\ItemCollection of the new ItemPrice meta data, or null
     * and returns a string representing a description.
     *
     * @param callable $callback The callback
     */
    public function setDescriptionCallback(callable $callback)
    {
        $this->description_callback = $callback;
    }
}
