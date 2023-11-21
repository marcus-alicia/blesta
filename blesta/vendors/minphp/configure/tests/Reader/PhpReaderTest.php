<?php
namespace Minphp\Configure\Tests\Reader;

use Minphp\Configure\Reader\PhpReader;
use PHPUnit_Framework_TestCase;
use SplTempFileObject;

/**
 * @coversDefaultClass \Minphp\Configure\Reader\PhpReader
 */
class PhpReaderTest extends PHPUnit_Framework_TestCase
{

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        $this->assertInstanceOf("\Minphp\Configure\Reader\ReaderInterface", new PhpReader(new SplTempFileObject()));
    }

    protected function getFixturePath()
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . "Fixtures" . DIRECTORY_SEPARATOR;
    }

    /**
     * @covers ::getIterator
     * @uses \Minphp\Configure\Reader\PhpReader
     * @dataProvider getIteratorDataProvider
     *
     * @param string $path The path to the file to mock
     * @param bool $empty True to verify the result is an empty array, or false that it contains a 'key' => 'value' pair
     */
    public function testGetIterator($path, $empty)
    {
        $file = $this->getFileMock($path);
        $reader = new PhpReader($file);

        // Ensure we can load the same file multiple times and get the same result
        for ($i = 0; $i < 2; $i++) {
            $result = $reader->getIterator($file);
            $this->assertInstanceOf('\ArrayIterator', $result);

            if ($empty === true) {
                $this->assertEmpty($result);
            } else {
                $this->assertEquals("value", $result['key']);
            }
        }
    }

    /**
     * Data Provider for ::testGetIterator
     */
    public function getIteratorDataProvider()
    {
        $path = $this->getFixturePath();
        
        return array(
            array($path . 'Config.php', false),
            array($path . 'ConfigObject.php', false),
            array($path . 'Empty.php', true),
            array($path . 'EmptyArray.php', true),
            array($path . 'EmptyNull.php', true),
            array($path . 'EmptyObject.php', true),
        );
    }

    /**
     * @covers ::getIterator
     * @uses \Minphp\Configure\Reader\PhpReader
     * @expectedException \Minphp\Configure\Reader\Exception\ReaderParseException
     */
    public function testGetIteratorException()
    {
        $file = $this->getFileMock(null);
        $reader = new PhpReader($file);
        $result = $reader->getIterator();
    }

    protected function getFileMock($filename)
    {
        $file = $this->getMockBuilder('\SplFileObject')
            ->setConstructorArgs(array("php://temp"))
            ->setMethods(array('isFile', 'getPathname'))
            ->getMock();

        $file->method('isFile')
            ->will($this->returnValue($filename != null));

        $file->method('getPathname')
            ->will($this->returnValue($filename));


        return $file;
    }
}
