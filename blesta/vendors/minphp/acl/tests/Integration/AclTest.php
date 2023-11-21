<?php
namespace Minphp\Acl\Tests\Unit;

use Minphp\Acl\Acl;
use Minphp\Record\Record;
use PHPUnit_Framework_TestCase;
use PDO;
use Seedling\Fixture;
use Seedling\Drivers\Standard;
use Seedling\KeyGenerators\AutoIncrement;

/**
 * @coversDefaultClass \Minphp\Acl\Acl
 */
class AclTest extends PHPUnit_Framework_TestCase
{

    private $db;
    private $fixture;
    private $record;
    private $acl;

    public function __construct()
    {
        // Connect to database
        $fixtureDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'fixtures';
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

        // Bootstrap
        $this->record = new Record(array());
        $this->record->setConnection($this->db);
        $this->acl = new Acl($this->record);

        $this->record->query(file_get_contents($fixtureDir . DIRECTORY_SEPARATOR . 'acl_acl.sql'));
        $this->record->query(file_get_contents($fixtureDir . DIRECTORY_SEPARATOR . 'acl_aco.sql'));
        $this->record->query(file_get_contents($fixtureDir . DIRECTORY_SEPARATOR . 'acl_aro.sql'));

        // Load seeds
        $driver = new Standard($this->db, new AutoIncrement());
        $config = [
            'location' => $fixtureDir
        ];
        $this->fixture = Fixture::getInstance($config, $driver);
    }

    public function setUp()
    {
        $this->fixture->up();
    }

    public function tearDown()
    {
        $this->fixture->down();
    }

    /**
     * @covers ::addAco
     * @covers ::getAcoByAlias
     */
    public function testAddAco()
    {
        $this->acl->addAco('test');
        $this->assertNotEmpty($this->acl->getAcoByAlias('test'));
    }
}
