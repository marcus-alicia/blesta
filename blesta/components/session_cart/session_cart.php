<?php
use Blesta\Core\Util\Common\Traits\Container;

/**
 * Shopping cart session wrapper
 *
 * @package blesta
 * @subpackage blesta.components.session_cart
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SessionCart
{
    // Load traits
    use Container;

    /**
     * @var string The name of the cart to use in the session
     */
    private $cart_name;

    /**
     * @var array An array of callbacks in key/value pairs where each key is the name of the method to invoke
     *  the callback on
     */
    private $callbacks = [];

    /**
     * Initializes the session cart
     *
     * @param int $cart_name The name of the cart to use in the session
     * @param \Minphp\Session\Session The session object
     */
    public function __construct($cart_name, $session)
    {
        $this->Session = $session;
        $this->cart_name = $cart_name;
    }

    /**
     * Sets a callback to be invoked when the given $method is called.
     *
     * @param string $action The action to invoke the callback on (supported actions: addItem, updateItem, removeItem)
     * @param callback The callback to execute
     */
    public function setCallback($action, $callback)
    {
        $eventFactory = $this->getFromContainer('util.events');
        $eventListener = $eventFactory->listener();
        $eventListener->register('SessionCart.' . $this->cart_name . '.' . $action, $callback);
    }

    /**
     * Returns the cart
     *
     * @return array An array of cart info including 'items' and any thing else set for the cart
     */
    public function get()
    {
        $cart = $this->Session->read($this->cart_name);

        if ($cart == '') {
            $cart = [
                'items' => []
            ];
            $this->set($cart);
        }
        return $cart;
    }

    /**
     * Set the cart
     *
     * @param array An array of cart data to write to the session
     */
    private function set($cart)
    {
        $this->Session->write($this->cart_name, $cart);
    }

    /**
     * Empties the cart by removing all items from it
     */
    public function emptyCart()
    {
        $cart = [
            'items' => []
        ];
        $this->set($cart);
    }

    /**
     * Clears the cart entirely
     */
    public function clear()
    {
        $this->Session->clear($this->cart_name);
    }

    /**
     * Returns an item in the cart at the given index
     *
     * @param int $index The index of the item to return
     * @return mixed The item or null otherwise
     */
    public function getItem($index)
    {
        $cart = $this->get();
        if (array_key_exists($index, (array)$cart['items'])) {
            return $cart['items'][$index];
        }
        return null;
    }

    /**
     * Adds an item to the cart
     *
     * @param mixed $item The item to add to the cart
     * @return int The index the item was added to
     */
    public function addItem($item)
    {
        $cart = $this->get();
        $i = count($cart['items']);

        $cart['items'][$i] = $item;
        $this->setData('items', $cart['items']);

        // Trigger the event
        $eventFactory = $this->getFromContainer('util.events');
        $eventListener = $eventFactory->listener();
        $eventListener->register('SessionCart.' . $this->cart_name . '.addItem');
        $event = $eventFactory->event(
            'SessionCart.' . $this->cart_name . '.addItem',
            ['item' => $item, 'index' => $i]
        );
        $eventListener->trigger($event);

        return $i;
    }

    /**
     * Update an item in the cart
     *
     * @param int $index The index to update
     * @param mixed $item The item to update at the given index
     */
    public function updateItem($index, $item)
    {
        $cart = $this->get();
        $cart['items'][$index] = $item;
        $this->setData('items', $cart['items']);

        // Trigger the event
        $eventFactory = $this->getFromContainer('util.events');
        $eventListener = $eventFactory->listener();
        $eventListener->register('SessionCart.' . $this->cart_name . '.updateItem');
        $event = $eventFactory->event(
            'SessionCart.' . $this->cart_name . '.updateItem',
            ['item' => $item, 'index' => $index]
        );
        $eventListener->trigger($event);
    }

    /**
     * Removes a set of items from the cart
     *
     * @param array $indexes A list of indexes matching the cart item indexes for items to remove
     */
    public function removeItems(array $indexes)
    {
        $cart = $this->get();

        foreach ($indexes as $index) {
            if (isset($cart['items'][$index])) {
                $item = $cart['items'][$index];
                unset($cart['items'][$index]);
                $this->setData('items', array_values($cart['items']));

                // Trigger the event
                $eventFactory = $this->getFromContainer('util.events');
                $eventListener = $eventFactory->listener();
                $eventListener->register('SessionCart.' . $this->cart_name . '.removeItem');
                $event = $eventFactory->event(
                    'SessionCart.' . $this->cart_name . '.removeItem',
                    ['item' => $item, 'index' => $index]
                );
                $eventListener->trigger($event);
            }
        }
    }

    /**
     * Remove an item from the cart
     *
     * @param int $index The index of the item to remove from the cart
     * @return mixed The item removed or null otherwise
     */
    public function removeItem($index)
    {
        $cart = $this->get();

        if (isset($cart['items'][$index])) {
            $item = $cart['items'][$index];
            unset($cart['items'][$index]);
            $this->setData('items', array_values($cart['items']));

            // Trigger the event
            $eventFactory = $this->getFromContainer('util.events');
            $eventListener = $eventFactory->listener();
            $eventListener->register('SessionCart.' . $this->cart_name . '.removeItem');
            $event = $eventFactory->event(
                'SessionCart.' . $this->cart_name . '.removeItem',
                ['item' => $item, 'index' => $index]
            );
            $eventListener->trigger($event);

            return $item;
        }
        return null;
    }

    /**
     * Allows an item to be modified before it is added to the cart
     *
     * @param mixed $item An item to add to the queue
     * @return mixed Update $item content based on any triggered events
     */
    public function prequeueItem($item)
    {
        // Trigger the event
        $eventFactory = $this->getFromContainer('util.events');
        $eventListener = $eventFactory->listener();
        $eventListener->register('SessionCart.' . $this->cart_name . '.prequeueItem');
        $event = $eventFactory->event(
            'SessionCart.' . $this->cart_name . '.prequeueItem',
            ['item' => $item]
        );
        $eventListener->trigger($event);

        $return_item = $event->getReturnValue();

        if ($return_item !== null) {
            return $return_item;
        }
        return $item;
    }

    /**
     * Adds an item to the queue
     *
     * @param mixed $item An item to add to the queue
     * @return int The index of the queued item
     */
    public function enqueue($item)
    {
        $cart = $this->get();

        if (!isset($cart['queue'])) {
            $cart['queue'] = [];
        }

        $cart['queue'][] = $item;
        $this->setData('queue', $cart['queue']);
        return count($cart['queue']) - 1;
    }

    /**
     * Removes and returns the first item from the queue
     *
     * @param int $index The index to dequeue
     * @return mixed An item from the queue, null if no items exist
     */
    public function dequeue($index = 0)
    {
        $cart = $this->get();

        if (!isset($cart['queue']) || !array_key_exists($index, (array)$cart['queue'])) {
            return null;
        }

        $item = $cart['queue'][$index];
        unset($cart['queue'][$index]);
        $cart['queue'] = array_values($cart['queue']);
        $this->setData('queue', $cart['queue']);

        return $item;
    }

    /**
     * Returns the first item from the queue without removing it
     *
     * @param int $index The index to peek at
     * @return mixed An item from the queue, null if no items exist
     */
    public function checkQueue($index = 0)
    {
        $cart = $this->get();

        if (!isset($cart['queue']) || !array_key_exists($index, (array)$cart['queue'])) {
            return null;
        }

        return $cart['queue'][$index];
    }

    /**
     * Returns whether or not the queue is empty
     *
     * @return bool true if the queue is empty, false otherwise
     */
    public function isEmptyQueue()
    {
        $cart = $this->get();
        return !isset($cart['queue']) || empty($cart['queue']);
    }


    /**
     * Returns whether or not the cart is empty
     *
     * @return bool true if the cart is empty, false otherwise
     */
    public function isEmptyCart()
    {
        $cart = $this->get();

        return !(count($cart['items']) > 0);
    }

    /**
     * Sets a specified field into the cart with the given value
     *
     * @param string $field The field key to set to the session
     * @param mixed $value The value to save to the cart for the field
     */
    public function setData($field, $value)
    {
        $cart = $this->get();
        $cart[$field] = $value;
        $this->set($cart);
    }

    /**
     * Returns a specified field from the cart
     *
     * @param string $field The field name to fetch
     * @return mixed The value stored in the cart at $field
     */
    public function getData($field)
    {
        $cart = $this->get();
        return isset($cart[$field]) ? $cart[$field] : null;
    }
}
