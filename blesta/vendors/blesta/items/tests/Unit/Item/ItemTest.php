<?php
namespace Blesta\Items\Tests\Unit\Item;

use Blesta\Items\Item\Item;
use PHPUnit_Framework_TestCase;
use stdClass;

/**
 * @coversDefaultClass \Blesta\Items\Item\Item
 */
class ItemTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        $this->assertInstanceOf('\Blesta\Items\Item\Item', $this->item());
    }

    /**
     * @covers ::getFields
     * @covers ::setFields
     * @uses Blesta\Items\Item\Item::__construct
     * @dataProvider getFieldsProvider
     */
    public function testGetFields($fields, $expected)
    {
        $item = $this->item();

        $this->assertInstanceOf('stdClass', $item->getFields());
        $item->setFields($fields);
        $this->assertInstanceOf('stdClass', $item->getFields());

        $this->assertEquals($expected, $item->getFields());
    }

    /**
     * Data provider for getting item fields
     *
     * @return array
     */
    public function getFieldsProvider()
    {
        return [
            [
                [],
                (object)[]
            ],
            [
                [['key' => 'value', 'key2' => 'value2']],
                (object)[['key' => 'value', 'key2' => 'value2']]
            ],
            [
                (object)['key' => 'value'],
                (object)['key' => 'value']
            ],
            // Setting invalid fields result in numerically properties
            [
                null,
                (object)[]
            ],
            [
                false,
                (object)[0 => false]
            ],
            [
                'string',
                (object)[0 => 'string']
            ]
        ];
    }

    /**
     * @covers ::setField
     * @covers ::getFields
     * @uses Blesta\Items\Item\Item::__construct
     * @dataProvider setFieldProvider
     */
    public function testSetField($key, $value, $expected)
    {
        $item = $this->item();

        $item->setField($key, $value);

        $this->assertEquals($expected, $item->getFields());
    }

    /**
     * Data Provider for setting a single field
     *
     * @return array
     */
    public function setFieldProvider()
    {
        return [
            [
                'key',
                'value',
                (object)['key' => 'value']
            ],
            [
                0,
                1,
                (object)[0 => 1]
            ],
            [
                true,
                false,
                (object)[1 => false]
            ],
            [
                'field',
                [0, 'abc', 2],
                (object)['field' => [0, 'abc', 2]]
            ],
            [
                'field',
                (object)['array' => [], 'object' => (object)[]],
                (object)['field' => (object)['array' => [], 'object' => (object)[]]]
            ],
            [
                '',
                1,
                (object)[]
            ],
        ];
    }

    /**
     * @covers ::removeField
     * @covers ::getFields
     * @uses Blesta\Items\Item\Item::__construct
     * @uses Blesta\Items\Item\Item::setFields
     * @dataProvider removeFieldProvider
     */
    public function testRemoveField($fields, $removeKey, $expected)
    {
        $item = $this->item();

        $item->setFields($fields);
        $item->removeField($removeKey);
        $this->assertEquals($expected, $item->getFields());
    }

    /**
     * Data Provider for removing a single field
     *
     * @return array
     */
    public function removeFieldProvider()
    {
        return [
            [
                ['key' => 'value'],
                'key',
                (object)[]
            ],
            [
                ['key' => 'value'],
                'value',
                (object)['key' => 'value']
            ],
            [
                ['key' => 'value', 'key2' => 'value2'],
                'key2',
                (object)['key' => 'value']
            ],
            [
                ['key' => 'value'],
                '',
                (object)['key' => 'value']
            ],
            [
                ['apple', 'banana', 'orange'],
                1,
                (object)[0 => 'apple', 2 => 'orange']
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
}
