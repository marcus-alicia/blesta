<?php
namespace PhillipsData\Csv;

use SplFileObject;
use Iterator;
use Traversable;
use InvalidArgumentException;

/**
 * CSV Writer
 */
class Writer extends AbstractCsv
{
    /**
     * Set the output file
     *
     * @param SplFileObject $file
     * @return self
     */
    public static function output(SplFileObject $file)
    {
        return new static($file);
    }

    /**
     * Writes the data as a CSV file
     *
     * @param array|Traversable $data
     * @throws InvalidArgumentException
     */
    public function write($data)
    {
        if (!is_array($data) && !($data instanceof Traversable)) {
            throw new InvalidArgumentException(
                'Expected Traversable or array'
            );
        }
        foreach ($data as $row) {
            $this->writeRow($row);
        }
    }

    /**
     * Writes the array to the file
     *
     * @param mixed $row Anything that can be cast into an array
     */
    public function writeRow($row)
    {
        $row = (array) $row;
        $iterator = $this->getIterator();

        // Skip if not writable
        if (!$this->isWritable($row, $iterator)) {
            return;
        }

        // Format
        foreach ($this->formatters as $callback) {
            $row = call_user_func(
                $callback,
                $row,
                $iterator->key(),
                $iterator
            );
        }

        $iterator->fputcsv($row);
    }

    /**
     * Process each filter callback for writability
     *
     * @param array $row
     * @param Iterator $iterator
     * @return bool
     */
    private function isWritable(array $row, Iterator $iterator)
    {
        // Filter
        foreach ($this->filters as $callback) {
            $writable = call_user_func(
                $callback,
                $row,
                $iterator->key(),
                $iterator
            );

            if (!$writable) {
                return false;
            }
        }
        return true;
    }
}
