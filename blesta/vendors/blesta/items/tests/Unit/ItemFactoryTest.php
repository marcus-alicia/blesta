<?php
namespace Blesta\Items\Tests\Unit;

use Blesta\Items\ItemFactory;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass \Blesta\Items\ItemFactory
 */
class ItemFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::item
     * @uses Blesta\Items\Item\Item::__construct
     */
    public function testItem()
    {
        $this->assertInstanceOf(
            '\Blesta\Items\Item\Item',
            $this->factory()->item()
        );
    }

    /**
     * @covers ::itemMap
     * @uses Blesta\Items\Item\Item::__construct
     * @uses Blesta\Items\Item\ItemMap::__construct
     * @uses Blesta\Items\Item\ItemMap::reset
     * @uses Blesta\Items\ItemFactory::item
     */
    public function testItemMap()
    {
        $this->assertInstanceOf(
            '\Blesta\Items\Item\ItemMap',
            $this->factory()->itemMap()
        );
    }

    /**
     * @covers ::itemCollection
     */
    public function testItemCollection()
    {
        $this->assertInstanceOf(
            '\Blesta\Items\Collection\ItemCollection',
            $this->factory()->itemCollection()
        );
    }

    /**
     * @return ItemFactory An instance of the ItemFactory
     */
    public function factory()
    {
        return new ItemFactory();
    }
}
