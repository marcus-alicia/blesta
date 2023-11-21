<?php
namespace PhillipsData\Csv\Tests\Unit\Map;

use PHPUnit_Framework_TestCase;
use PhillipsData\Csv\Map\MapIterator;
use ArrayIterator;

/**
 * @coversDefaultClass \PhillipsData\Csv\Map\MapIterator
 */
class MapIteratorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers ::current
     */
    public function testCurrent()
    {
        $iterator = new ArrayIterator(['a', 'b', 'c']);

        $actual = 0;
        $map_iterator = new MapIterator(
            $iterator,
            function ($line) use (&$actual) {
                $actual++;
                return $line;
            }
        );

        $expected = 0;
        foreach ($map_iterator as $line) {
            $expected++;
        }
        $this->assertEquals($expected, $actual);
    }
}
