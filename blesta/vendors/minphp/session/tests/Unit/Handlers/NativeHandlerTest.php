<?php
namespace Minphp\Session\Tests\Unit\Handlers;

use Minphp\Session\Handlers\NativeHandler;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass \Minphp\Session\Handlers\NativeHandler
 */
class NativeHandlerTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testInstanceOfSessionHandlerInterface()
    {
        $this->assertInstanceOf('\SessionHandlerInterface', new NativeHandler());
    }
}
