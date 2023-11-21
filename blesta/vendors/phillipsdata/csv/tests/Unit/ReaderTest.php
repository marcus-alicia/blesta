<?php
namespace PhillipsData\Csv\Tests\Unit;

use PHPUnit_Framework_TestCase;
use PhillipsData\Csv\Reader;
use SplFileObject;

/**
 * @coversDefaultClass \PhillipsData\Csv\Reader
 */
class ReaderTest extends PHPUnit_Framework_TestCase
{
    /**
     * Retrieves an instance of the Reader
     *
     * @return \PhillipsData\Csv\Reader
     */
    private function getReader($headers = false, $headerType = 'without')
    {
        $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Fixtures'
            . DIRECTORY_SEPARATOR . $headerType . '-header.csv';
        return Reader::input(new SplFileObject($filename), $headers);
    }

    /**
     * Data provider for attempting both with and without header CSVs
     */
    public function inputProvider()
    {
        return array(
            [false, 'without'],
            [true, 'with'],
            [true, 'with-broken']
        );
    }

    /**
     * @covers ::fetch
     * @covers ::applyFilter
     * @covers ::getAssocIterator
     * @covers ::getFilterIterator
     * @covers ::getFormatIterator
     * @covers ::applyFormat
     * @covers ::input
     * @covers \PhillipsData\Csv\AbstractCsv
     * @uses \PhillipsData\Csv\Map\MapIterator
     */
    public function testFetch()
    {
        $reader = $this->getReader();
        $this->assertInstanceOf(
            'Iterator',
            $reader->fetch()
        );
    }

    /**
     * @dataProvider inputProvider
     * @covers ::input
     * @covers ::fetch
     * @covers ::applyFilter
     * @covers ::getAssocIterator
     * @covers ::getFilterIterator
     * @covers ::getFormatIterator
     * @covers ::applyFormat
     * @covers ::setHeader
     * @covers \PhillipsData\Csv\AbstractCsv
     * @uses \PhillipsData\Csv\Map\MapIterator
     */
    public function testFormat($headers, $headerType)
    {
        $reader = $this->getReader($headers, $headerType);
        // Determine defaults for a line
        $defaultLines = [];
        foreach ($reader as $line) {
            $defaultLines[] = $line;
        }

        // Format each CSV line
        $reader->format(function ($line, $key, $iterator) {
            $values = [];
            foreach ($line as $cell) {
                $values[] = $this->format($cell);
            }

            return $values;
        });

        // Check each cell has been formatted
        foreach ($reader->fetch() as $i => $line) {
            foreach ($line as $j => $cell) {
                // The values should be different until formatted
                $this->assertNotEquals(
                    $defaultLines[$i][$j],
                    $cell
                );

                // The values should be identical once formatted
                $this->assertEquals(
                    $this->format($defaultLines[$i][$j]),
                    $cell
                );
            }
        }
    }

    /**
     * @dataProvider inputProvider
     * @covers ::input
     * @covers ::fetch
     * @covers ::applyFilter
     * @covers ::getAssocIterator
     * @covers ::getFilterIterator
     * @covers ::getFormatIterator
     * @covers ::applyFormat
     * @covers ::setHeader
     * @covers \PhillipsData\Csv\AbstractCsv
     * @uses \PhillipsData\Csv\Map\MapIterator
     */
    public function testFilter($headers, $headerType)
    {
        $reader = $this->getReader($headers, $headerType);

        $index = 1;
        if ($headers) {
            $index = 'Heading 2';
        }

        $reader->filter(function ($line) use ($index) {
            // Only return values where the second column contains even numbers
            return (preg_match('/[02468]+/', $line[$index]));
        });

        // Check that the CSV contains only the matching rows
        foreach ($reader->fetch() as $i => $line) {
            // Verify only rows where second column contains even numbers
            $this->assertTrue((boolean) preg_match('/[02468]+/', $line[$index]));
        }
    }

    /**
     * Checks formatting when the CSV has fewer headings than columns
     *
     * @covers ::getAssocIterator
     * @covers ::input
     * @covers ::fetch
     * @covers ::getFilterIterator
     * @covers ::getFormatIterator
     * @covers ::applyFormat
     * @covers ::applyfilter
     * @covers ::setHeader
     * @covers \PhillipsData\Csv\AbstractCsv
     * @uses \PhillipsData\Csv\Map\MapIterator
     */
    public function testFormatHeaders()
    {
        $reader = $this->getReader(true, 'with-broken');

        // Determine defaults for a line
        $defaultLines = [];
        foreach ($reader as $line) {
            $defaultLines[] = $line;
        }

        // Format each CSV line
        $reader->format(function ($line, $key, $iterator) {
            $values = [];
            foreach ($line as $cell) {
                $values[] = $this->format($cell);
            }

            return $values;
        });

        // Add another formatter
        $reader->format(function ($line, $key, $iterator) {
            $values = [];
            foreach ($line as $cell) {
                $values[] = $this->formatHyphens($cell);
            }

            return $values;
        });

        // Check each cell has been formatted
        foreach ($reader->fetch() as $i => $line) {
            foreach ($line as $j => $cell) {
                // The values should be different until formatted
                $this->assertNotEquals(
                    $defaultLines[$i][$j],
                    $cell
                );

                $formattedCell = $this->format($defaultLines[$i][$j]);
                $formattedCell = $this->formatHyphens($formattedCell);

                // The values should be identical once formatted
                $this->assertEquals(
                    $formattedCell,
                    $cell
                );
            }
        }
    }

    /**
     * @param string $text Text to format
     * @return string The formatted text
     */
    private function format($text)
    {
        return strtoupper($text);
    }

    /**
     *
     * @param string $text Text to format
     * @return string The formatted text;
     */
    private function formatHyphens($text)
    {
        return '-' . $text . '-';
    }
}
