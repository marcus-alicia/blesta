<?php
namespace PhillipsData\Csv;

use IteratorAggregate;
use SplFileObject;

/**
 * Modifier allows modifying the result set
 */
abstract class AbstractCsv implements IteratorAggregate
{
    protected $filters = [];
    protected $formatters = [];
    protected $iterator;

    /**
     * Inject the file
     *
     * @param SplFileObject $file
     */
    protected function __construct(SplFileObject $file)
    {
        $this->iterator = $file;
    }

    /**
     * Free the inner iterator
     */
    public function __destruct()
    {
        $this->iterator = null;
    }

    /**
     * Set a filter callback.
     *
     * @param callable $callback
     * @return self
     */
    public function filter(callable $callback)
    {
        $this->filters[] = $callback;
        return $this;
    }

    /**
     * Set the format callback. Executed for each iteration.
     *
     * @param callable $callback
     * @return self
     */
    public function format(callable $callback)
    {
        $this->formatters[] = $callback;
        return $this;
    }

    /**
     * Returns the SplFileObject representing the CSV
     *
     * @return Iterator
     */
    public function getIterator()
    {
        return $this->iterator;
    }
}
