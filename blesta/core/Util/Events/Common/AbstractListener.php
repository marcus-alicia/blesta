<?php
namespace Blesta\Core\Util\Events\Common;

use Loader;

/**
 * Abstract listener for events
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Common
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractListener
{
    /**
     * @var array Holds all registered listeners
     */
    private static $listeners;

    /**
     * Notifies all registered listeners of the event (called in the order they were set).
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event The event object to pass to the registered listeners
     * @return Blesta\Core\Util\Events\Common\EventInterface The event processed
     */
    abstract public function trigger(EventInterface $event);

    /**
     * Notifies all registered listeners of the event (called in the order they were set),
     * until one returns true, then ceases notifying all remaining listeners.
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event The event object to pass to the registered listeners
     * @return Blesta\Core\Util\Events\Common\EventInterface The event processed
     */
    abstract public function triggerUntil(EventInterface $event);

    /**
     * Register a listener, to be notified when the event is triggered. Only permits
     * one registered event per callback.
     *
     * @param string $name The name of the event to register $callback under
     * @param callback $callback The Class/Method or Object/Method or function to execute when the event is triggered.
     * @param string $file The full path to the file that contains the callback, null will default to looking
     *  in the \Blesta\Core\Util\Events\Observers\Class namespace (where 'Class' is the CamelCase format of the callback
     *  class [e.g. ClassName])
     */
    public function register($name, $callback, $file = null)
    {
        if ($file !== null) {
            Loader::load($file);
        }

        if (empty(self::$listeners[$name]) || !in_array($callback, self::$listeners[$name])) {
            self::$listeners[$name][] = $callback;
        }
    }

    /**
     * Unregisters a listener if the event has been registered. Will remove all
     * copies of the registered event in the case that it was registered multiple times.
     *
     * @param string $name The name of the event to unregister $callback from
     * @param callback $callback The Class/Method or Object/Method or function to unregister for the event
     */
    public function unregister($name, $callback)
    {
        foreach ($this->getRegistered($name) as $i => $event_callback) {
            if ($callback == $event_callback) {
                unset(self::$listeners[$name][$i]);
            }
        }
    }

    /**
     * Returns all registered listeners for the given event name
     *
     * @param string $name The name of the event to fetch registered callbacks for
     * @return array An array of registered callbacks
     */
    public function getRegistered($name)
    {
        if (isset(self::$listeners[$name])) {
            return self::$listeners[$name];
        }
        return [];
    }
}
