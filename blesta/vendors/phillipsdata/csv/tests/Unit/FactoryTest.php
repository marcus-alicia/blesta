<?php
namespace PhillipsData\Csv\Tests\Unit;

use PhillipsData\Csv\Factory;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass \PhillipsData\Csv\Factory
 */
class FactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::writer
     * @covers ::fileObject
     * @uses \PhillipsData\Csv\AbstractCsv
     * @uses \PhillipsData\Csv\Writer
     */
    public function testWriter()
    {
        $file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Fixtures'
            . DIRECTORY_SEPARATOR . 'non-existent-file.csv';

        $this->assertInstanceOf(
            '\PhillipsData\Csv\Writer',
            Factory::writer($file)
        );
        $this->assertFileExists($file);

        unlink($file);
    }

    /**
     * @covers ::reader
     * @covers ::fileObject
     * @uses \PhillipsData\Csv\AbstractCsv
     * @uses \PhillipsData\Csv\Reader
     */
    public function testReader()
    {
        $file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Fixtures'
            . DIRECTORY_SEPARATOR . 'with-header.csv';

        $this->assertInstanceOf(
            '\PhillipsData\Csv\Reader',
            Factory::reader($file)
        );
        $this->assertFileExists($file);
    }
}
