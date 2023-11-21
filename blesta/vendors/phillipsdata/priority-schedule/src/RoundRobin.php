<?php
namespace PhillipsData\PrioritySchedule;

use SplHeap;
use RuntimeException;
use PhillipsData\PrioritySchedule\Exceptions\NoSuchElementException;

/**
 * Round Robin Priority Scehdule implemented using a Heap
 */
class RoundRobin extends SplHeap implements ScheduleInterface
{
    /**
     * @var callable The comparator to use to determine the order
     */
    protected $callback;

    /**
     * Initialize the priority schedule
     */
    public function __construct()
    {
        $this->callback = function ($a, $b) {
            if ($a === $b) {
                return 0;
            }
            return $a < $b
                ? 1
                : -1;
        };
    }

    /**
     * {@inheritdoc}
     *
     * $callback Should accept a two parameters and return an int
     * (0 if items are equal, 1 to put left on top, -1 to put right on top)
     */
    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function compare($value1, $value2)
    {
        return call_user_func($this->callback, $value1, $value2);
    }

    /**
     * {@inheritdoc}
     */
    public function extract()
    {
        try {
            return parent::extract();
        } catch (RuntimeException $e) {
            throw new NoSuchElementException(
                'Can not extract from empty heap.'
            );
        }
    }
}
