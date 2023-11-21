<?php
namespace Minphp\Html\Tests;

use PHPUnit_Framework_TestCase;
use Minphp\Html\Html;

/**
 * @coversDefaultClass \Minphp\Html\Html
 */
class HtmlTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $this->assertInstanceOf('\Minphp\Html\Html', $this->getHtml());
    }

    /**
     * @covers ::isUtf8
     * @dataProvider isUtf8DataProvider
     */
    public function testIsUtf8($string, $result)
    {
        $html = $this->getHtml();

        $this->assertEquals($result, $html->isUtf8($string));
    }

    /**
     * Data Provider for ::testIsUtf8
     */
    public function isUtf8DataProvider()
    {
        return array(
            array('', true),
            array('abc', true),
            array('123', true),
            array('test string here', true),
            array(123, true),
            array(null, true),
            array('\xe2\x80\x80', true),
            array('áéóú', true),
            array(chr(0x00), true),
            array('�', true),
            array(pack('l', 1), true),
            array(pack('l', -1), false), // byte 1 starts 1xxxx.. instead of 0xxxx..
            array(utf8_encode(pack('l', -1)), true), // byte 1 starts 1xxxx.. instead of 0xxxx, but UTF-8 encoding works
            array(pack('L', 123456), false),
            array(pack('LLLL', 0, 1, 2, 3), true),
            array(pack('LLLL', 0, -1, 2, 3), false), // byte 1 is valid, but byte 2 starts 11xxxx.. instead of 10xxxx...
            array(pack('LLLL', 0, 1, -2, 3), false), // byte 1 and 2 are valid, byte 3 is not
            array(pack('LLLL', 0, 1, 2, -3), false), // bytes 1-3 are valid, byte 4 is not
            array(pack('LLLLLL', 1, 1, 1, 1, 1, 1), true), // 6 valid bytes
            array(pack('LLLLLL', 1, 1, 1, 1, -1, 1), false), // bytes 1-4 are valid, byte 5 is not
            array(pack('LLLLLL', 1, 1, 1, 1, 1, -1), false), // bytes 1-5 are valid, byte 6 is not
            array(utf8_encode(pack('LLLLLL', 1, 1, 1, 1, 1, -1)), true), // UTF-8 encoded invalid byte sequence is valid
        );
    }

    /**
     * @return Html
     */
    private function getHtml()
    {
        return new Html();
    }
}
