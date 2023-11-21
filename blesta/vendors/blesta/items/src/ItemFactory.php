<?php
namespace Blesta\Items;

use Blesta\Items\Item\Item;
use Blesta\Items\Item\ItemMap;
use Blesta\Items\Collection\ItemCollection;

/**
 * ItemFactory for fetching newly-instantiated item objects
 */
class ItemFactory
{
    /**
     * Retrieves a new instance of Item
     *
     * @return Item An instance of Item
     */
    public function item()
    {
        return new Item();
    }

    /**
     * Retrieves a new instance of ItemMap
     *
     * @return ItemMap An instance of ItemMap
     */
    public function itemMap()
    {
        return new ItemMap();
    }

    /**
     * Retrieves a new instance of ItemCollection
     *
     * @return ItemCollection An instance of ItemCollection
     */
    public function itemCollection()
    {
        return new ItemCollection();
    }
}
