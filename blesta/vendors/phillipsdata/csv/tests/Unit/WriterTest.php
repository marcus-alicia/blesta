<?php
namespace PhillipsData\Csv\Tests\Unit;

use PhillipsData\Csv\Writer;
use PHPUnit_Framework_TestCase;
use SplTempFileObject;

/**
 * @coversDefaultClass \PhillipsData\Csv\Writer
 */
class WriterTest extends PHPUnit_Framework_TestCase
{
    /**
     * Fetches the SplFileObject
     *
     * @return SplTempFileObject
     */
    private function getSplFixtureFile()
    {
        $file = new SplTempFileObject();
        $file->setFlags(
            SplTempFileObject::READ_CSV
            | SplTempFileObject::READ_AHEAD
            | SplTempFileObject::SKIP_EMPTY
        );

        return $file;
    }

    /**
     * @covers ::output
     * @covers \PhillipsData\Csv\AbstractCsv
     */
    public function testOutput()
    {
        $this->assertInstanceOf(
            '\PhillipsData\Csv\Writer',
            Writer::output($this->getSplFixtureFile())
        );
    }

    /**
     * @covers ::write
     * @covers ::writeRow
     * @covers ::isWritable
     * @covers ::output
     * @covers \PhillipsData\Csv\AbstractCsv
     */
    public function testWrite()
    {
        $file = $this->getSplFixtureFile();
        $writer = Writer::output($file);
        $this->assertInstanceOf('\PhillipsData\Csv\Writer', $writer);

        $data = [
            ['a1', 'b1'],
            ['a2', 'b2'],
            ['a3', 'b3'],
            ['a4', 'b4']
        ];

        $writer->write($data);

        $actual = [];
        foreach ($file as $row) {
            $actual[] = $row;
        }

        $this->assertEquals($data, $actual);
    }

    /**
     * @covers ::write
     * @covers ::output
     * @covers \PhillipsData\Csv\AbstractCsv
     * @expectedException InvalidArgumentException
     */
    public function testWriteException()
    {
        $writer = Writer::output($this->getSplFixtureFile());

        // Exception, string is an invalid argument
        $writer->write('some data');
    }

    /**
     * @dataProvider filtersProvider
     * @covers ::isWritable
     * @covers ::write
     * @covers ::writeRow
     * @covers ::output
     * @covers \PhillipsData\Csv\AbstractCsv
     */
    public function testFilters($data, $expected)
    {
        $file = $this->getSplFixtureFile();
        $writer = Writer::output($file);
        $writer->filter(function ($row) {
            return in_array(substr($row[0], -1), ['1', '3']);
        });

        $writer->write($data);

        $actual = [];
        foreach ($file as $row) {
            $actual[] = $row;
        }

        $this->assertEquals($expected, $actual);
    }

    /**
     * Data provider for testFilters
     *
     * @return array
     */
    public function filtersProvider()
    {
        return [
            [
                [['a1', 'b1'],['a2', 'b2'],['a3', 'b3'],['a4', 'b4']],
                [['a1', 'b1'],['a3', 'b3']]
            ]
        ];
    }

    /**
     * @dataProvider formatterProvider
     * @covers ::isWritable
     * @covers ::write
     * @covers ::writeRow
     * @covers ::output
     * @covers \PhillipsData\Csv\AbstractCsv
     */
    public function testFormatters($data, $expected)
    {
        $file = $this->getSplFixtureFile();
        $writer = Writer::output($file);

        $writer->format(function ($row) {
            foreach ($row as $key => &$value) {
                $value = strtoupper($value);
            }
            return $row;
        });

        $writer->write($data);

        $actual = [];
        foreach ($file as $row) {
            $actual[] = $row;
        }

        $this->assertEquals($expected, $actual);
    }

    /**
     * Data provider for testFormatters
     *
     * @return array
     */
    public function formatterProvider()
    {
        return [
            [
                [['a1', 'b1'],['a2', 'b2']],
                [['A1', 'B1'],['A2', 'B2']]
            ]
        ];
    }
}
