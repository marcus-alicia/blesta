<?php
namespace Minphp\Db\Tests\Unit;

use Minphp\Db\SqliteConnection;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass \Minphp\Db\SqliteConnection
 */
class SqliteConnectionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::makeDsn
     * @uses \Minphp\Db\PdoConnection
     */
    public function testMakeDsn()
    {
        $connection = new SqliteConnection(array());
        $this->assertEquals(
            'sqlite::memory:',
            $connection->makeDsn(array('driver' => 'sqlite', 'database' => ':memory:'))
        );
    }
}
