<?php
namespace Blesta\Core\Util\Events;

use Blesta\Core\Util\Events\Common\AbstractListener;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * Event Handler
 *
 * Stores callbacks for particular events that may be executed when the event
 * is triggered. Events are static, so each instance may register events
 * triggered in this or other instances of the Event handler.
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Listener extends AbstractListener
{
    /**
     * {@inheritdoc}
     */
    public function trigger(EventInterface $event)
    {
        foreach ($this->getRegistered($event->getName()) as $callback) {
            if (is_array($callback)) {
                // If object not passed in and this callback class does not exist, skip
                if (!is_object($callback[0]) && !class_exists($callback[0])) {
                    continue;
                }
            }

            // Call the callback
            if (is_callable($callback)) {
                call_user_func($callback, $event);
            }
        }

        return $event;
    }

    /**
     * {@inheritdoc}
     */
    public function triggerUntil(EventInterface $event)
    {
        foreach ($this->getRegistered($event->getName()) as $callback) {
            if (is_array($callback)) {
                // If object not passed in and this callback class does not exist, skip
                if (!is_object($callback[0]) && !class_exists($callback[0])) {
                    continue;
                }
            }

            // Unable to call the callback
            if (!is_callable($callback)) {
                continue;
            }

            if (call_user_func($callback, $event) === true) {
                break;
            }
        }

        return $event;
    }

    /**
     * Register a listener, to be notified when the event is triggered. Only permits
     * one registered event per callback.
     *
     * @param string $name The name of the event to register $callback under
     * @param callback $callback The Class/Method or Object/Method or function to execute when the event is triggered;
     *  A null callback will default to a system observer matching the given $name
     * @param string $file The full path to the file that contains the callback, null will default to looking
     *  in the \Blesta\Core\Util\Events\Observers\Class namespace (where 'Class' is the CamelCase format of the callback
     *  class [e.g. ClassName])
     */
    public function register($name, $callback = null, $file = null)
    {
        // Default the callback to a core Observer based on the name
        if ($callback === null && $file === null) {
            $callback = $this->getCallback($name);
        }

        parent::register($name, $callback, $file);
    }

    /**
     * Unregisters a listener if the event has been registered. Will remove all
     * copies of the registered event in the case that it was registered multiple times.
     *
     * @param string $name The name of the event to unregister $callback from
     * @param callback $callback The Class/Method or Object/Method or function to unregister for the event;
     *  A null callback will default to a system observer matching the given $name
     */
    public function unregister($name, $callback = null)
    {
        // Default the callback to a core Observer based on the name
        if ($callback === null) {
            $callback = $this->getCallback($name);
        }

        parent::unregister($name, $callback);
    }

    /**
     * Creates a default callback from the given event name
     *
     * @param string $name The name of the event for this callback
     * @return array An array representing the callback
     */
    private function getCallback($name)
    {
        // Default the callback to a core Observer based on the name,
        // e.g. 'AppController.preAction' becomes ['Blesta\Core\Util\Events\Observers\AppController', 'preAction']
        $callback = explode('.', $name, 2);

        return [
            (isset($callback[0]) ? '\Blesta\Core\Util\Events\Observers\\' . $callback[0] : ''),
            (isset($callback[1]) ? $callback[1] : '')
        ];
    }
}
