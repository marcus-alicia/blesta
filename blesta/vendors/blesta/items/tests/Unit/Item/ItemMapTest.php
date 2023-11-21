<?php
namespace Blesta\Items\Tests\Unit\Item;

use Blesta\Items\Item\Item;
use Blesta\Items\Item\ItemMap;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass \Blesta\Items\Item\ItemMap
 */
class ItemMapTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers ::reset
     * @uses Blesta\Items\Item\Item::__construct
     * @uses Blesta\Items\ItemFactory::item
     */
    public function testConstruct()
    {
        $this->assertInstanceOf('\Blesta\Items\Item\ItemMap', $this->map());
    }

    /**
     * @covers ::__construct
     * @covers ::combine
     * @covers ::add
     * @covers ::addFirstMatching
     * @covers ::reset
     * @uses Blesta\Items\Item\Item::__construct
     * @uses Blesta\Items\Item\Item::setFields
     * @uses Blesta\Items\Item\Item::setField
     * @uses Blesta\Items\Item\Item::getFields
     * @uses Blesta\Items\ItemFactory::item
     * @dataProvider combineProvider
     */
    public function testCombine($item1Fields, $item2Fields, $expectedItem)
    {
        $item1 = $this->item();
        $item2 = $this->item();
        $item1->setFields($item1Fields);
        $item2->setFields($item2Fields);

        $map = $this->map();
        $newItem = $map->combine($item1, $item2);
        $this->assertEquals($expectedItem, $newItem);
    }

    /**
     * Data provider for combining Items
     *
     * @return array
     */
    public function combineProvider()
    {
        $fields0 = [
            'value' => false,
            'type' => ['accept', 'deny']
        ];
        $item0 = $this->item();
        $item0->setFields($fields0);

        $fields1 = [
            'qty' => 3,
            'price' => 12.5000
        ];
        $item1 = $this->item();
        $item1->setFields($fields1);

        $fields2 = [
            'currency' => 'USD'
        ];
        $item2 = $this->item();
        $item2->setFields($fields2);

        $fields3 = ['USD', 'tasty'];
        $item3 = $this->item();
        $item3->setFields($fields3);

        return [
            [
                ['apple' => true, 'banana' => false, 'broccoli' => 'gross', 'type' => ['accept', 'deny']],
                ['value' => 'banana', 'type' => 'type'],
                $item0
            ],
            [
                ['quantity' => 3, 'price' => 15.0000, 'override_price' => 12.5000],
                ['qty' => 'quantity', 'type' => 'allow', 'price' => ['override_price', 'price']],
                $item1
            ],
            [
                ['iso4217' => 'USD', 'key' => 'value'],
                ['price' => ['override_price', 'price'], 'currency' => ['currency', 'iso4217']],
                $item2
            ],
            [
                ['currency' => 'USD', 'apple' => 'tasty', 'banana' => 'good'],
                ['currency', ['apple', 'banana']],
                $item3
            ],
            [
                ['apple' => true, 'banana' => false, 'broccoli' => 'gross', 'type' => ['accept', 'deny']],
                ['apple' => true, 'banana' => false, 'broccoli' => 'gross', 'type' => ['accept', 'deny']],
                $this->item()
            ]
        ];
    }

    /**
     * @return Item An instance of Item
     */
    public function item()
    {
        return new Item();
    }

    /**
     * @return ItemMap An instance of ItemMap
     */
    public function map()
    {
        return new ItemMap();
    }
}
