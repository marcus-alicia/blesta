<?php
namespace Minphp\Session\Tests\Unit\Handlers;

use Minphp\Session\Handlers\PdoHandler;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass \Minphp\Session\Handlers\PdoHandler
 */
class PdoHandlerTest extends PHPUnit_Framework_TestCase
{
    private $handler;

    /**
     * Setup
     */
    public function setUp()
    {
        $mockPdo = $this->getPdoMock();
        $this->handler = new PdoHandler($mockPdo);
    }

    /**
     * @covers ::__construct
     */
    public function testInstanceOfSessionHandlerInterface()
    {
        $mockPdo = $this->getPdoMock();
        $this->assertInstanceOf('\SessionHandlerInterface', new PdoHandler($mockPdo));
    }

    /**
     * @covers ::close
     * @covers ::__construct
     */
    public function testClose()
    {
        $this->assertTrue($this->handler->close());
    }

    /**
     * @covers ::destroy
     * @covers ::__construct
     */
    public function testDestroy()
    {
        $sessionId = 'sessionId';

        $mockPdo = $this->getPdoMock($this->equalTo([':id' => $sessionId]));

        $handler = new PdoHandler($mockPdo);
        $handler->destroy($sessionId);
    }

    /**
     * @covers ::gc
     * @covers ::__construct
     */
    public function testGc()
    {
        $maxlifetime = 100;

        $mockPdo = $this->getPdoMock($this->callback(function ($data) use ($maxlifetime) {
            $exp = strtotime($data[':expire']);
            $t = time();
            return $exp < $t && ($exp + $maxlifetime <= $t);
        }));

        $handler = new PdoHandler($mockPdo);
        $handler->gc($maxlifetime);
    }

    /**
     * @covers ::open
     * @covers ::__construct
     */
    public function testOpen()
    {
        $savePath = '';
        $name = 'PHPSESSID';
        $this->assertTrue($this->handler->open($savePath, $name));
    }

    /**
     * @covers ::read
     * @covers ::__construct
     * @dataProvider readProvider
     *
     * @param string $sessionId The session ID
     * @param mixed $value The value returned from PDO
     * @param string $expected The read value
     */
    public function testRead($sessionId, $value, $expected)
    {
        $mockPdo = $this->getPdoMock(
            $this->callback(function ($data) use ($sessionId) {
                return $sessionId === $data[':id'];
            }),
            null,
            null,
            $this->returnValue($value)
        );

        $handler = new PdoHandler($mockPdo);
        $this->assertEquals($expected, $handler->read($sessionId));
    }

    /**
     * Provides data for ::testRead
     */
    public function readProvider()
    {
        return [
            ['sessionId', (object)['value' => 'value'], 'value'],
            ['sessionId', false, '']
        ];
    }

    /**
     * @covers ::write
     * @covers ::__construct
     */
    public function testWriteUpdate()
    {
        $sessionId = 'sessionId';
        $data = 'data';

        $mockPdo = $this->getPdoMock(
            $this->callback(function ($input) use ($sessionId, $data) {
                return $sessionId === $input[':id']
                    && $data === $input[':value'];
            }),
            null,
            1
        );

        $handler = new PdoHandler($mockPdo);
        $handler->write($sessionId, $data);
    }

    /**
     * @covers ::write
     * @covers ::__construct
     */
    public function testWriteInsert()
    {
        $sessionId = 'sessionId';
        $data = 'data';

        $mockPdo = $this->getPdoMock(
            $this->callback(function ($input) use ($sessionId, $data) {
                return $sessionId === $input[':id']
                    && $data === $input[':value'];
            }),
            $this->returnValue(null),
            0
        );

        $handler = new PdoHandler($mockPdo);
        $handler->write($sessionId, $data);
    }

    /**
     * Mock PDO and a request to PDO::prepare
     *
     * @return PDO
     */
    protected function getPdoMock($executeWith = null, $executeReturn = null, $rowCount = null, $fetchValue = null)
    {
        $mockPdo = $this->getMockBuilder('\Minphp\Session\Tests\MockablePdo')
            ->getMock();

        $mockStatement = $this->getMockBuilder('\PDOStatement')
            ->getMock();
        if (null !== $executeWith) {
            if (null !== $executeReturn) {
                $mockStatement->expects($this->any())
                    ->method('execute')
                    ->with($executeWith)
                    ->will($executeReturn);
            } else {
                $mockStatement->expects($this->once())
                    ->method('execute')
                    ->with($executeWith);
            }
        }

        if (null !== $rowCount) {
            $mockStatement->expects($this->once())
                ->method('rowCount')
                ->will($this->returnValue($rowCount));
        }

        if (null !== $fetchValue) {
            $mockStatement->expects($this->any())
                ->method('fetch')
                ->will($fetchValue);
        }

        $mockPdo->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($mockStatement));
        return $mockPdo;
    }
}
