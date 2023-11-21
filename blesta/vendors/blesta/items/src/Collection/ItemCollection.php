<?php
namespace Blesta\Items\Collection;

use Blesta\Items\Item\ItemInterface;
use Iterator;

/**
 * ItemCollection
 */
class ItemCollection implements Iterator
{
    /**
     * @var array A collection of Item objects
     */
    private $collection = array();
    /**
     * @var int The current index position within the collection
     */
    private $position = 0;

    /**
     * Adds an Item to the collection
     *
     * @param ItemInterface $item An item to add to the collection
     * @return ItemCollection reference to this
     */
    #[\ReturnTypeWillChange]
    public function append(ItemInterface $item)
    {
        $this->collection[] = $item;
        return $this;
    }

    /**
     * Removes an Item from the collection
     *
     * @param ItemInterface $item An item to remove from the collection
     * @return ItemCollection reference to this
     */
    #[\ReturnTypeWillChange]
    public function remove(ItemInterface $item)
    {
        // Remove all instances of the item from the collection
        foreach ($this->collection as $index => $it) {
            if ($it === $item) {
                unset($this->collection[$index]);
            }
        }
        return $this;
    }

    /**
     * Retrieves the count of all Item objects in the collection
     *
     * @return int The number of Item objects in the collection
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->collection);
    }

    /**
     * Retrieves the item in the collection at the current pointer
     *
     * @return mixed The Item in the collection at the current position, otherwise null
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return (
            $this->valid()
            ? $this->collection[$this->position]
            : null
        );
    }

    /**
     * Retrieves the index currently being pointed at in the collection
     *
     * @return int The index of the position in the collection
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->position;
    }

    /**
     * Moves the pointer to the next item in the collection
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        // Set the next position to the position of the next item in the collection
        $position = $this->position;
        foreach ($this->collection as $index => $item) {
            if ($index > $position) {
                $this->position = $index;
                break;
            }
        }
        // If there is no next item in the collection, increment the position instead
        if ($position == $this->position) {
            ++$this->position;
        }
    }

    /**
     * Moves the pointer to the first item in the collection
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        // Reset the array pointer to the first entry in the collection
        reset($this->collection);

        // Set the position to the first entry in the collection if there is one
        $first_index = key($this->collection);
        $this->position = $first_index === null
            ? 0
            : $first_index;
    }

    /**
     * Determines whether the current pointer references a valid item in the collection
     *
     * @return boolean True if the pointer references a valid item in the collection, false otherwise
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return array_key_exists($this->position, $this->collection);
    }
}
