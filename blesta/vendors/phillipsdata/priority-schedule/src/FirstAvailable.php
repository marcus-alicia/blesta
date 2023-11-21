<?php
namespace PhillipsData\PrioritySchedule;

use SplQueue;
use PhillipsData\PrioritySchedule\Exceptions\NoSuchElementException;

/**
 * First Available Priorirty Schedule implemented using a Queue
 */
class FirstAvailable extends SplQueue implements ScheduleInterface
{
    /**
     * @var callable
     */
    protected $callbackFilter;

    /**
     * Initialize the priority schedule
     */
    public function __construct()
    {
        $this->callbackFilter = function ($item) {
            return (bool) $item;
        };
        $this->setIteratorMode(
            SplQueue::IT_MODE_DELETE
        );
    }

    /**
     * {@inheritdoc}
     *
     * $callback Should accept a single parameter and return a bool
     * (true if valid, false otherwise)
     */
    public function setCallback(callable $callback)
    {
        $this->callbackFilter = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function insert($item)
    {
        if (call_user_func($this->callbackFilter, $item)) {
            $this->enqueue($item);
            $this->rewind();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function extract()
    {
        if (!$this->valid()) {
            throw new NoSuchElementException(
                'Can not extract from empty queue.'
            );
        }
        return $this->current();
    }
}
