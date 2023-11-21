<?php
namespace PhillipsData\Csv;

use SplFileObject;
use Iterator;
use CallbackFilterIterator;
use PhillipsData\Csv\Map\MapIterator;

/**
 * CSV Reader
 */
class Reader extends AbstractCsv
{
    /**
     * @var array<int, string> The header to use to format results
     */
    protected $header = null;

    /**
     * Set the input file
     *
     * @param SplFileObject $file
     * @param bool $withHeader True to parse the first row as the header
     * @return self
     */
    public static function input(SplFileObject $file, $withHeader = true)
    {
        $file->setFlags(
            SplFileObject::READ_CSV
            | SplFileObject::READ_AHEAD
            | SplFileObject::SKIP_EMPTY
        );
        $reader = new static($file);

        if ($withHeader) {
            $file->rewind();
            $reader->setHeader($file->current());
        }

        return $reader;
    }

    /**
     * Set the header to use when parsing the CSV
     *
     * @param array<int, string> $header
     */
    public function setHeader(array $header = null)
    {
        $this->header = $header;
    }

    /**
     * Fetch the iterator for the reader
     *
     * @return Iterator
     */
    public function fetch()
    {
        $header = $this->header;

        // Initial format iterator
        $iterator = $this->getIterator();
        $iterator = $this->applyFilter(
            $iterator,
            function ($row, $key) use ($header) {
                // Skip row if this is the header row
                return !$header || $key !== 0;
            }
        );
        $iterator = $this->getAssocIterator($iterator);
        $iterator = $this->getFilterIterator($iterator);
        $iterator = $this->getFormatIterator($iterator);

        return $iterator;
    }

    /**
     * Ensure rows are returned as associative arrays
     *
     * @param Iterator $iterator
     * @return MapIterator
     */
    private function getAssocIterator(Iterator $iterator)
    {
        // Initial format iterator
        $header = $this->header;
        $headerCount = count($header);
        return $this->applyFormat(
            $iterator,
            function ($row, $key, $iterator) use ($header, $headerCount) {
                if ($headerCount > 0) {
                    if ($headerCount != count($row)) {
                        $row = array_slice(
                            array_pad($row, $headerCount, null),
                            0,
                            $headerCount
                        );
                    }

                    $row = array_combine($header, $row);
                }

                return $row;
            }
        );
    }

    /**
     * Apply a filter iterator
     *
     * @param Iterator $iterator
     * @param callable $callback
     * @return CallbackFilterIterator
     */
    private function applyFilter(Iterator $iterator, callable $callback)
    {
        return new CallbackFilterIterator($iterator, $callback);
    }

    /**
     * Apply a format iterator
     *
     * @param Iterator $iterator
     * @param callable $callback
     * @return MapIterator
     */
    private function applyFormat(Iterator $iterator, callable $callback)
    {
        return new MapIterator($iterator, $callback);
    }

    /**
     * Add filter iterators
     *
     * @param Iterator $iterator
     * @return Iterator
     */
    private function getFilterIterator(Iterator $iterator)
    {
        foreach ($this->filters as $callback) {
            $iterator = $this->applyFilter($iterator, $callback);
        }
        return $iterator;
    }

    /**
     * Add format iterators
     *
     * @param Iterator $iterator
     * @return Iterator
     */
    private function getFormatIterator(Iterator $iterator)
    {
        foreach ($this->formatters as $callback) {
            $iterator = $this->applyFormat($iterator, $callback);
        }
        return $iterator;
    }
}
