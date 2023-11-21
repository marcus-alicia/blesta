<?php
namespace PhillipsData\Csv\Map;

use IteratorIterator;
use Iterator;

/**
 * Map Iterator
 */
class MapIterator extends IteratorIterator
{

    /**
     * Initialize the mapper
     *
     * @param Iterator $iterator
     * @param callable $callback The callback to execute for each iteration
     */
    public function __construct(Iterator $iterator, callable $callback)
    {
        parent::__construct($iterator);
        $this->callback = $callback;
    }

    /**
     * Fetch the current result using a callback
     *
     * @return array
     */
    public function current()
    {
        $iterator = $this->getInnerIterator();
        return call_user_func(
            $this->callback,
            $iterator->current(),
            $iterator->key(),
            $iterator
        );
    }
}
