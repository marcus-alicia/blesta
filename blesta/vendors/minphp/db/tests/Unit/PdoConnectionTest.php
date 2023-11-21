<?php
namespace Minphp\Db\Tests\Unit;

use Minphp\Db\PdoConnection;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass Minphp\Db\PdoConnection
 */
class PdoConnectionTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = new PdoConnection(array());
    }

    /**
     * @covers ::setFetchMode
     * @covers ::__construct
     */
    public function testSetFetchMode()
    {
        $this->connection->setFetchMode(1);
        $this->assertEquals(1, $this->connection->setFetchMode(2));
    }

    /**
     * @covers ::lastInsertId
     * @covers ::__construct
     * @covers ::setConnection
     * @covers ::getConnection
     * @covers ::connect
     */
    public function testLastInsertId()
    {
        $id = 1234;
        $this->connection->setConnection($this->mockConnection('lastInsertId', $id));
        $this->assertEquals($id, $this->connection->lastInsertId());
    }

    /**
     * @covers ::setAttribute
     * @covers ::__construct
     * @covers ::setConnection
     * @covers ::getConnection
     * @covers ::connect
     */
    public function testSetAttribute()
    {
        $attribute = 'attribute';
        $value = 'value';

        $mockConnection = $this->getMockBuilder('\Minphp\Db\Tests\MockablePdo')
            ->getMock();
        $mockConnection->expects($this->once())
            ->method('setAttribute')
            ->with($this->equalTo($attribute), $this->equalTo($value));

        $this->connection->setConnection($mockConnection);
        $this->connection->setAttribute($attribute, $value);
    }

    /**
     * @covers ::query
     * @covers ::__construct
     * @covers ::getConnection
     * @covers ::setConnection
     * @covers ::connect
     * @uses \Minphp\Db\PdoConnection::prepare
     */
    public function testQuery()
    {
        $sql = 'SELECT id FROM table_of_ids WHERE key1=? AND key2=?';
        $params = array('a', 'b');

        $mockStatement = $this->getMockBuilder('\PDOStatement')
            ->getMock();
        $mockStatement->expects($this->once())
            ->method('execute')
            ->with($this->equalTo($params));

        $mockConnection = $this->getMockBuilder('\Minphp\Db\Tests\MockablePdo')
            ->getMock();
        $mockConnection->expects($this->once())
            ->method('prepare')
            ->with($this->equalTo($sql))
            ->will($this->returnValue($mockStatement));

        $this->connection->setConnection($mockConnection);
        $this->assertInstanceOf('\PDOStatement', $this->connection->query($sql, $params));
    }

    /**
     * @covers ::prepare
     * @covers ::__construct
     * @covers ::getConnection
     * @covers ::setConnection
     * @covers ::connect
     */
    public function testPrepare()
    {
        $sql = 'SELECT id FROM table_of_ids';

        $mockStatement = $this->getMockBuilder('\PDOStatement')
            ->getMock();
        $mockStatement->expects($this->once())
            ->method('setFetchMode')
            ->with($this->anything());

        $mockConnection = $this->getMockBuilder('\Minphp\Db\Tests\MockablePdo')
            ->getMock();
        $mockConnection->expects($this->once())
            ->method('prepare')
            ->with($this->equalTo($sql))
            ->will($this->returnValue($mockStatement));

        $this->connection->setConnection($mockConnection);
        $this->assertInstanceOf('\PDOStatement', $this->connection->prepare($sql));
    }

    /**
     * @covers ::begin
     * @covers ::__construct
     * @covers ::getConnection
     * @covers ::setConnection
     * @covers ::connect
     */
    public function testBegin()
    {
        $this->connection->setConnection($this->mockConnection('beginTransaction', true));
        $this->assertTrue($this->connection->begin());
    }

    /**
     * @covers ::rollBack
     * @covers ::__construct
     * @covers ::getConnection
     * @covers ::setConnection
     * @covers ::connect
     */
    public function testRollBack()
    {
        $this->connection->setConnection($this->mockConnection('rollBack', true));
        $this->assertTrue($this->connection->rollBack());
    }

    /**
     * @covers ::commit
     * @covers ::__construct
     * @covers ::getConnection
     * @covers ::setConnection
     * @covers ::connect
     */
    public function testCommit()
    {
        $this->connection->setConnection($this->mockConnection('commit', true));
        $this->assertTrue($this->connection->commit());
    }

    /**
     * Mock a PDO method to return a specific value
     *
     * @param string $method
     * @param boolean|string|int|array $return
     */
    private function mockConnection($method, $return)
    {
        $mockConnection = $this->getMockBuilder('\Minphp\Db\Tests\MockablePdo')
            ->getMock();
        $mockConnection->expects($this->once())
            ->method($method)
            ->will($this->returnValue($return));
        return $mockConnection;
    }

    /**
     * @covers ::affectedRows
     * @covers ::__construct
     */
    public function testAffectedRows()
    {
        $count = 2;
        $mockStatement = $this->getMockBuilder('\PDOStatement')
            ->getMock();
        $mockStatement->expects($this->once())
            ->method('rowCount')
            ->will($this->returnValue($count));

        $this->assertEquals($count, $this->connection->affectedRows($mockStatement));
    }

    /**
     * @covers ::affectedRows
     * @covers ::__construct
     * @expectedException \RuntimeException
     */
    public function testAffectedRowsException()
    {
        $this->connection->affectedRows();
    }

    /**
     * @covers ::makeDsn
     * @covers ::__construct
     */
    public function testMakeDsn()
    {
        $info = array(
            'driver' => 'mysql',
            'database' => 'database',
            'host' => 'localhost',
            'port' => '8889'
        );
        $expected = 'mysql:host=localhost;dbname=database;port=8889';

        $this->assertEquals($expected, $this->connection->makeDsn($info));
    }

    /**
     * @covers ::makeDsn
     * @covers ::__construct
     * @expectedException \InvalidArgumentException
     */
    public function testMakeDsnException()
    {
        $this->connection->makeDsn(array());
    }

    /**
     * @covers ::reuseConnection
     * @covers ::__construct
     */
    public function testReuseConnection()
    {
        $this->assertSame($this->connection->reuseConnection(true), $this->connection);
    }
}
