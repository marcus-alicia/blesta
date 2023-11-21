<?php
namespace Minphp\Javascript\Tests;

use PHPUnit_Framework_TestCase;
use Minphp\Javascript\Javascript;

/**
 * @coversDefaultClass \Minphp\Javascript\Javascript
 */
class JavascriptTest extends PHPUnit_Framework_TestCase
{
    private $javascript;

    public function setUp()
    {
        $this->javascript = new Javascript();
    }

    /**
     * @covers ::__construct
     * @uses \Minphp\Javascript\Javascript::setDefaultPath
     */
    public function testConstruct()
    {
        $this->assertInstanceOf('\Minphp\Javascript\Javascript', new Javascript('path/to/js'));
    }

    /**
     * @covers ::setDefaultPath
     */
    public function testDefaultPath()
    {
        $new_path = 'new/path/';
        $this->assertNull($this->javascript->setDefaultPath($new_path));
        $this->assertEquals($new_path, $this->javascript->setDefaultPath($new_path));
    }

    /**
     * @covers ::setFile
     * @covers ::getFiles
     */
    public function testSetFile()
    {
        $this->assertInstanceOf(
            '\Minphp\Javascript\Javascript',
            $this->javascript->setFile('something.js', 'head')
        );
        $this->assertNotEmpty($this->javascript->getFiles('head'));
    }

    /**
     * @covers ::setInline
     * @covers ::getInline
     */
    public function testSetInline()
    {
        $this->assertInstanceOf(
            '\Minphp\Javascript\Javascript',
            $this->javascript->setInline('var a = [];')
        );
        $this->assertNotEmpty($this->javascript->getInline());
    }

    /**
     * @covers ::unsetFiles
     * @uses \Minphp\Javascript\Javascript::setFile
     * @uses \Minphp\Javascript\Javascript::getFiles
     */
    public function testUnsetFiles()
    {
        $this->javascript->setFile('something.js', 'head');
        $this->assertNotEmpty($this->javascript->getFiles('head'));
        $this->assertInstanceOf('\Minphp\Javascript\Javascript', $this->javascript->unsetFiles());
        $this->assertEmpty($this->javascript->getFiles('head'));
    }

    /**
     * @covers ::unsetInline
     * @uses \Minphp\Javascript\Javascript::setInline
     * @uses \Minphp\Javascript\Javascript::getInline
     */
    public function testUnsetInline()
    {
        $this->javascript->setInline('var a = [];');
        $this->assertNotEmpty($this->javascript->getInline());
        $this->assertInstanceOf('\Minphp\Javascript\Javascript', $this->javascript->unsetInline());
        $this->assertEmpty($this->javascript->getInline());
    }
}
