<?php
namespace PhillipsData\PrioritySchedule;

use Iterator;
use Countable;
use PhillipsData\PrioritySchedule\Exceptions\NoSuchElementException;

interface ScheduleInterface extends Iterator, Countable
{
    /**
     * Insert an item into the schedule
     *
     * @param mixed $item
     */
    public function insert($item);

    /**
     * Extract an item from the schedule
     *
     * @return mixed
     * @throws NoSuchElementException Thrown when no element exists to be returned
     */
    public function extract();

    /**
     * Set the callback to use for controlling the order in which elements are returned
     *
     * @param callable $callback
     */
    public function setCallback(callable $callback);
}
