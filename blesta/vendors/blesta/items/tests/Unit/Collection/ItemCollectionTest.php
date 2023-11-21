<?php
namespace Blesta\Items\Tests\Unit\Collection;

use Blesta\Items\Item\Item;
use Blesta\Items\Collection\ItemCollection;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass \Blesta\Items\Collection\ItemCollection
 */
class ItemCollectionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::append
     * @covers ::count
     * @uses \Blesta\Items\Item\Item::__construct
     */
    public function testAppend()
    {
        $item = $this->item();
        $item2 = $this->item();

        $collection = $this->collection();
        $this->assertEquals(0, $collection->count());

        // Add 1 item
        $collection->append($item);
        $this->assertEquals(1, $collection->count());

        // Add a second item
        $collection->append($item2);
        $this->assertEquals(2, $collection->count());
    }

    /**
     * @covers ::remove
     * @covers ::count
     * @covers ::append
     * @uses \Blesta\Items\Item\Item::__construct
     */
    public function testRemove()
    {
        $item = $this->item();
        $item2 = $this->item();

        $collection = $this->collection();
        $this->assertEquals(0, $collection->count());

        // Add items
        $collection->append($item)->append($item2);
        $this->assertEquals(2, $collection->count());

        // Remove an item
        $collection->remove($item);
        $this->assertEquals(1, $collection->count());
    }

    /**
     * @covers ::current
     * @covers ::valid
     * @covers ::append
     * @uses Blesta\Items\Item\Item::__construct
     * @uses Blesta\Items\Item\Item::setField
     */
    public function testCurrent()
    {
        $collection = $this->collection();
        $this->assertNull($collection->current());

        $item = $this->item();

        $collection->append($item);
        $this->assertSame($item, $collection->current());

        // First item is still the current item
        $collection->append($this->item());
        $this->assertSame($item, $collection->current());
    }

    /**
     * @covers ::key
     * @covers ::next
     */
    public function testKey()
    {
        $collection = $this->collection();

        // No items exist
        $this->assertEquals(0, $collection->key());

        // Key should point to next index
        $collection->next();
        $this->assertEquals(1, $collection->key());
    }

    /**
     * @covers ::next
     * @covers ::rewind
     * @covers ::current
     * @covers ::key
     * @covers ::valid
     * @covers ::append
     * @covers ::remove
     * @uses Blesta\Items\Item\Item::__construct
     * @uses Blesta\Items\Item\Item::setField
     */
    public function testNext()
    {
        $collection = $this->collection();

        // Position starts at 0, should increment each time
        $collection->next();
        $collection->next();
        $this->assertEquals(2, $collection->key());

        // Back to the beginning
        $collection->rewind();
        $this->assertEquals(0, $collection->key());

        // Ensure the collection iterates to the next item
        $item1 = $this->item();
        $item2 = $this->item();
        $item3 = $this->item();
        $collection->append($item1)->append($item2)->append($item3);
        $collection->remove($item2);
        $this->assertSame($item1, $collection->current());

        $collection->next();
        $this->assertSame($item3, $collection->current());

        // Remove the current item and get the next item
        $collection->rewind();
        $collection->remove($item1);
        $collection->next();
        $this->assertSame($item3, $collection->current());

        // The next item is outside of the collection
        $collection->next();
        $this->assertNull($collection->current());
    }

    /**
     * @covers ::valid
     * @covers ::next
     * @covers ::append
     * @uses Blesta\Items\Item\Item::__construct
     */
    public function testValid()
    {
        $collection = $this->collection();

        // No items
        $this->assertFalse($collection->valid());

        // One item in current position
        $collection->append($this->item());
        $this->assertTrue($collection->valid());

        // No item in the next position
        $collection->next();
        $this->assertFalse($collection->valid());
    }

    /**
     * Retrieves an instance of an Item
     *
     * @return Item
     */
    public function item()
    {
        return $this->getMock('Blesta\Items\Item\Item');
    }

    /**
     * Retrieves an instance of an ItemCollection
     *
     * @return ItemCollection
     */
    public function collection()
    {
        return new ItemCollection();
    }
}
