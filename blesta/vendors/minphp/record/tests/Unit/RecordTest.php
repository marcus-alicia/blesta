<?php
namespace Minphp\Record\Tests\Unit;

use Minphp\Record\Record;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass \Minphp\Record\Record
 */
class RecordTest extends PHPUnit_Framework_TestCase
{

    private $Record;

    public function setUp()
    {
        $this->Record = new Record(array());
    }

    private function instance()
    {
        return '\Minphp\Record\Record';
    }

    /**
     * @covers ::keywordValue
     */
    public function testKeywordValue()
    {
        $this->assertEquals("DEFAULT", $this->Record->keywordValue("DEFAULT")->keyword);
        $this->assertEquals("INDEX", $this->Record->keywordValue("INDEX")->keyword);
    }

    /**
     * @covers ::setField
     */
    public function testSetField()
    {
        $this->assertInstanceOf(
            $this->instance(),
            $this->Record->setField(
                "name",
                array('type' => "int", 'size' => 10, 'unsigned' => true),
                true
            )
        );
        $this->assertInstanceOf($this->instance(), $this->Record->setField("name", null, false));
    }

    /**
     * @covers ::setKey
     */
    public function testSetKey()
    {
        $this->assertNull($this->Record->setKey(array(), "index"));
        $this->assertInstanceOf($this->instance(), $this->Record->setKey(array("id"), "primary", true, "id", true));
        $this->assertInstanceOf($this->instance(), $this->Record->setKey(array("id"), "primary", true, null, false));
    }

    /**
     * @covers ::create
     * @covers ::buildQuery
     * @covers ::buildTables
     * @covers ::buildFields
     * @covers ::buildTableOptions
     * @covers ::escapeField
     * @covers ::escapeFieldMatches
     * @covers ::escapeTableField
     * @covers ::setField
     * @covers ::setKey
     */
    public function testCreate()
    {
        $pdo_statement = $this->getMockBuilder("\PDOStatement")
            ->getMock();

        $query = "CREATE TABLE `table_name` (`id` int(10) UNSIGNED  NOT NULL "
            . "AUTO_INCREMENT, `field1` varchar(32) NULL, PRIMARY KEY (`id`)) "
            . "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $record = $this->getQueryMock($query, $params = array(), $pdo_statement);
        $record
            ->setField("id", array('type' => "int", 'size' => 10, 'unsigned' => true, 'auto_increment' => true))
            ->setField("field1", array('type' => "varchar", 'size' => 32, 'default' => null, 'is_null' => true))
            ->setKey(array("id"), "primary")
            ->create("table_name");
    }

    /**
     * @covers ::alter
     * @covers ::buildQuery
     * @covers ::buildTables
     * @covers ::buildFields
     * @covers ::escapeField
     * @covers ::escapeFieldMatches
     * @covers ::escapeTableField
     * @covers ::setField
     * @covers ::setKey
     */
    public function testAlter()
    {
        $pdo_statement = $this->getMockBuilder("\PDOStatement")
            ->getMock();

        $query = "ALTER TABLE `table_name` DROP `id`, DROP `field1`, DROP PRIMARY KEY ";
        $record = $this->getQueryMock($query, $params = array(), $pdo_statement);
        $record
            ->setField("id", null, false)
            ->setField("field1", null, false)
            ->setKey(array("id"), "primary", null, false)
            ->alter("table_name");
    }

    /**
     * @covers ::truncate
     * @covers ::buildQuery
     * @covers ::buildTables
     * @covers ::escapeField
     * @covers ::escapeFieldMatches
     * @covers ::escapeTableField
     */
    public function testTruncate()
    {
        $pdo_statement = $this->getMockBuilder("\PDOStatement")
            ->getMock();

        $query = "TRUNCATE TABLE `table_name`";
        $record = $this->getQueryMock($query, null, $pdo_statement);
        $record->truncate("table_name");
    }

    /**
     * @covers ::drop
     * @covers ::buildQuery
     * @covers ::buildTables
     * @covers ::escapeField
     * @covers ::escapeFieldMatches
     * @covers ::escapeTableField
     */
    public function testDrop()
    {
        $pdo_statement = $this->getMockBuilder("\PDOStatement")
            ->getMock();

        $query = "DROP TABLE `table_name`";
        $record = $this->getQueryMock($query, null, $pdo_statement);
        $record->drop("table_name");

        $query = "DROP TABLE IF EXISTS `table_name`";
        $record = $this->getQueryMock($query, null, $pdo_statement);
        $record->drop("table_name", true);
    }

    /**
     * @covers ::set
     * @covers ::buildQuery
     * @covers ::buildTables
     * @covers ::keywordValue
     */
    public function testSet()
    {
        $this->assertInstanceOf($this->instance(), $this->Record->set("field", "value"));
        $this->assertInstanceOf($this->instance(), $this->Record->set("field", $this->Record->keywordValue("DEFAULT")));
    }

    /**
     * @covers ::insert
     * @covers ::buildQuery
     * @covers ::buildTables
     * @covers ::buildValues
     * @covers ::buildWhere
     * @covers ::buildLimit
     * @covers ::set
     * @covers ::where
     * @covers ::setFields
     * @covers ::buildConditionals
     * @covers ::buildConditional
     * @covers ::escapeField
     * @covers ::escapeFieldMatches
     * @covers ::escapeTableField
     * @covers ::setConditional
     * @covers ::buildOnDuplicate
     */
    public function testInsert()
    {
        $pdo_statement = $this->getMockBuilder("\PDOStatement")
            ->getMock();

        $query = "INSERT INTO `table_name` (`field1`, `field2`) VALUES (?, ?)";
        $record = $this->getQueryMock($query, null, $pdo_statement);

        $record->set("field1", 1)
            ->set("field2", 2)
            ->insert("table_name");
    }

    /**
     * @covers ::update
     * @covers ::buildQuery
     * @covers ::buildTables
     * @covers ::buildValuePairs
     * @covers ::buildWhere
     * @covers ::buildLimit
     * @covers ::set
     * @covers ::where
     * @covers ::setFields
     * @covers ::buildConditionals
     * @covers ::buildConditional
     * @covers ::escapeField
     * @covers ::escapeFieldMatches
     * @covers ::escapeTableField
     * @covers ::setConditional
     */
    public function testUpdate()
    {
        $pdo_statement = $this->getMockBuilder("\PDOStatement")
            ->getMock();

        $query = "UPDATE `table_name` SET `field1`=?, `field2`=? WHERE `field1`=?";
        $record = $this->getQueryMock($query, null, $pdo_statement);

        $record->set("field1", 1)
            ->set("field2", 2)
            ->where("field1", "=", 3)
            ->update("table_name");
    }

    /**
     * @covers ::delete
     * @covers ::from
     * @covers ::buildQuery
     * @covers ::buildColumns
     * @covers ::buildTables
     * @covers ::buildWhere
     * @covers ::buildLimit
     * @covers ::setConditional
     * @covers ::on
     * @covers ::where
     * @covers ::innerJoin
     * @covers ::buildJoin
     * @covers ::buildConditionals
     * @covers ::buildConditional
     * @covers ::escapeField
     * @covers ::escapeFieldMatches
     * @covers ::escapeTableField
     */
    public function testDelete()
    {
        $pdo_statement = $this->getMockBuilder("\PDOStatement")
            ->getMock();

        $query = "DELETE  FROM `table_name` WHERE `field1`=?";
        $record = $this->getQueryMock($query, null, $pdo_statement);

        $record->from("table_name")
            ->where("field1", "=", 1)
            ->delete();

        $query = "DELETE `table_name`.* FROM `table_name` "
            . "INNER JOIN `other_table` ON `other_table`.`id`=`table_name`.`id`";
        $record = $this->getQueryMock($query, null, $pdo_statement);

        $record->from("table_name")
            ->innerJoin("other_table", "other_table.id", "=", "table_name.id", false)
            ->delete(array("table_name.*"));
    }

    /**
     * @covers ::select
     */
    public function testSelect()
    {
        $this->assertInstanceOf($this->instance(), $this->Record->select());
    }

    /**
     * @covers ::from
     */
    public function testFrom()
    {
        $this->assertInstanceOf($this->instance(), $this->Record->from("table"));
    }

    /**
     * @covers ::on
     * @covers ::setConditional
     * @covers ::join
     * @covers ::buildJoin
     * @covers ::buildConditionals
     * @covers ::buildConditional
     * @covers ::escapeField
     * @covers ::escapeFieldMatches
     * @covers ::escapeTableField
     */
    public function testJoin()
    {
        $this->assertInstanceOf(
            $this->instance(),
            $this->Record->join("table2", "table1.field", "=", "table2.field")
        );
    }

    /**
     * @covers ::on
     * @covers ::setConditional
     * @covers ::leftJoin
     * @covers ::buildJoin
     * @covers ::buildConditionals
     * @covers ::buildConditional
     * @covers ::escapeField
     * @covers ::escapeFieldMatches
     * @covers ::escapeTableField
     */
    public function testLeftJoin()
    {
        $this->assertInstanceOf(
            $this->instance(),
            $this->Record->leftJoin("table2", "table1.field", "=", "table2.field")
        );
    }

    /**
     * @covers ::on
     * @covers ::setConditional
     * @covers ::rightJoin
     * @covers ::buildJoin
     * @covers ::buildConditionals
     * @covers ::buildConditional
     * @covers ::escapeField
     * @covers ::escapeFieldMatches
     * @covers ::escapeTableField
     */
    public function testRightJoin()
    {
        $this->assertInstanceOf(
            $this->instance(),
            $this->Record->rightJoin("table2", "table1.field", "=", "table2.field")
        );
    }

    /**
     * @covers ::on
     * @covers ::setConditional
     * @covers ::innerJoin
     * @covers ::buildJoin
     * @covers ::buildConditionals
     * @covers ::buildConditional
     * @covers ::escapeField
     * @covers ::escapeFieldMatches
     * @covers ::escapeTableField
     */
    public function testInnerJoin()
    {
        $this->assertInstanceOf(
            $this->instance(),
            $this->Record->innerJoin("table2", "table1.field", "=", "table2.field")
        );
    }

    /**
     * @covers ::on
     * @covers ::setConditional
     * @covers ::innerJoin
     * @covers ::buildJoin
     * @covers ::buildConditionals
     * @covers ::buildConditional
     * @covers ::escapeField
     * @covers ::escapeFieldMatches
     * @covers ::escapeTableField
     */
    public function testOn()
    {
        $this->assertInstanceOf(
            $this->instance(),
            $this->Record->on("table1.field", "=", "table2.field")->innerJoin("table2")
        );
    }


    /**
     * @covers ::orOn
     * @covers ::setConditional
     * @covers ::innerJoin
     * @covers ::buildJoin
     * @covers ::buildConditionals
     * @covers ::buildConditional
     * @covers ::escapeField
     * @covers ::escapeFieldMatches
     * @covers ::escapeTableField
     */
    public function testOrOn()
    {
        $this->assertInstanceOf(
            $this->instance(),
            $this->Record->orOn("table1.field", "=", "table2.field")->innerJoin("table2")
        );
    }

    /**
     * @covers ::where
     * @covers ::setConditional
     */
    public function testWhere()
    {
        $this->assertInstanceOf($this->instance(), $this->Record->where("table1.field", "=", "table2.field"));
    }

    /**
     * @covers ::orWhere
     * @covers ::setConditional
     */
    public function testOrWhere()
    {
        $this->assertInstanceOf($this->instance(), $this->Record->orWhere("table1.field", "=", "table2.field"));
    }

    /**
     * @covers ::duplicate
     * @covers ::setConditional
     */
    public function testDuplicate()
    {
        $this->assertInstanceOf($this->instance(), $this->Record->duplicate("table1.field", "=", "new value"));
    }

    /**
     * @covers ::like
     * @covers ::setConditional
     */
    public function testLike()
    {
        $this->assertInstanceOf($this->instance(), $this->Record->like("table1.field", "%value%"));
    }

    /**
     * @covers ::notLike
     * @covers ::setConditional
     */
    public function testNotLike()
    {
        $this->assertInstanceOf($this->instance(), $this->Record->notLike("table1.field", "%value%"));
    }


    /**
     * @covers ::orLike
     * @covers ::setConditional
     */
    public function testOrLike()
    {
        $this->assertInstanceOf($this->instance(), $this->Record->orLike("table1.field", "%value%"));
    }

    /**
     * @covers ::orNotLike
     * @covers ::setConditional
     */
    public function testOrNotLike()
    {
        $this->assertInstanceOf($this->instance(), $this->Record->orNotLike("table1.field", "%value%"));
    }

    /**
     * @covers ::having
     * @covers ::setConditional
     */
    public function testHaving()
    {
        $this->assertInstanceOf($this->instance(), $this->Record->having("table1.field", "=", "table2.field"));
    }

    /**
     * @covers ::orHaving
     * @covers ::setConditional
     */
    public function testOrHaving()
    {
        $this->assertInstanceOf($this->instance(), $this->Record->orHaving("table1.field", "=", "table2.field"));
    }

    /**
     * @covers ::group
     */
    public function testGroup()
    {
        $this->assertInstanceOf($this->instance(), $this->Record->group("table1.field"));
        $this->assertInstanceOf($this->instance(), $this->Record->group(array("table1.field", "table1.field2")));
    }

    /**
     * @covers ::order
     */
    public function testOrder()
    {
        $this->assertInstanceOf($this->instance(), $this->Record->order(array('table1.field' => "asc")));
        $this->assertInstanceOf($this->instance(), $this->Record->order(array("table1.field", "table1.field2")));
    }

    /**
     * @covers ::limit
     */
    public function testLimit()
    {
        $this->assertInstanceOf($this->instance(), $this->Record->limit(30));
    }

    /**
     * @covers ::open
     */
    public function testOpen()
    {
        $this->assertInstanceOf($this->instance(), $this->Record->open());
    }

    /**
     * @covers ::close
     */
    public function testClose()
    {
        $this->assertInstanceOf($this->instance(), $this->Record->close());
    }

    /**
     * @covers ::appendValues
     */
    public function testAppendValues()
    {
        $values = array(1, 2, 3, 'x', 'y', 'z');
        $this->assertInstanceOf($this->instance(), $this->Record->appendValues($values));
        $this->assertEquals($values, $this->Record->values);

        $more_values = array('a', 'b', 'c');
        $this->Record->appendValues($more_values);
        $this->assertEquals(array_merge($values, $more_values), $this->Record->values);
    }

    /**
     * @covers ::reset
     * @covers ::where
     * @covers ::setConditional
     */
    public function testReset()
    {
        $record = clone $this->Record;
        $this->Record->where("table1.field", "=", "table2.field");
        $this->assertNotEquals($record, $this->Record);
        $this->Record->reset();
        $this->assertEquals($record, $this->Record);
    }

    /**
     * @covers ::quoteIdentifier
     * @dataProvider quoteIdentifierProvider
     */
    public function testQuoteIdentifier($identifier, $result)
    {
        $this->assertEquals($result, $this->Record->quoteIdentifier($identifier));
    }

    /**
     * Dataprovider for testQuoteIdentifier
     */
    public function quoteIdentifierProvider()
    {
        return array(
            array(array('table', 'field'), '`table`.`field`'),
            array("table.field", '`table`.`field`'),
            array("field", '`field`')
        );
    }

    public function testSetReturnRecordInstance()
    {
        $this->assertInstanceOf($this->instance(), $this->Record->set("field", "value"));
    }


    /**
     * Generates a Record mock with ::query and ::reset mocked
     *
     * @param string $query The SQL before substitution
     * @param array $params The parameters to substitute
     * @return object
     */
    protected function getQueryMock($query, $params = array(), $return = null)
    {
        $record = $this->getMockBuilder($this->instance())
            ->disableOriginalConstructor()
            ->setMethods(array("query", "reset"))
            ->getMock();

        if ($params !== null) {
            $record->expects($this->once())
                ->method("query")
                ->with(
                    $this->equalTo($query),
                    $this->equalTo($params)
                )
                ->will($this->returnValue($return));
        } else {
            $record->expects($this->once())
                ->method("query")
                ->with($this->equalTo($query))
                ->will($this->returnValue($return));
        }

        $record->expects($this->once())
            ->method("reset");

        return $record;
    }
}
