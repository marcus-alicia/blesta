<?php
namespace Blesta\Core\Util\Events;

use Blesta\Core\Util\Events\Common\EventInterface;
use Blesta\Core\Util\Events\EventFactory;
use Loader;
use stdClass;
use Configure;
use EventObject;

/**
 * Observer for invoking triggers for an event
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Observer
{
    /**
     * Handle event triggers
     *
     * @param \Blesta\Core\Util\Events\Common\EventInterface $event The event to trigger
     * @return \Blesta\Core\Util\Events\Common\EventInterface $event The event triggered
     */
    public static function triggerEvent(EventInterface $event)
    {
        return self::triggerPluginEvent(self::triggerSystemEvent($event));
    }

    /**
     * Triggers the event for any system listeners
     *
     * @param \Blesta\Core\Util\Events\Common\EventInterface $event The event to trigger
     * @return \Blesta\Core\Util\Events\Common\EventInterface $event The event triggered
     */
    private static function triggerSystemEvent(EventInterface $event)
    {
        $parent = new stdClass();
        Loader::loadModels($parent, ['SystemEvents']);

        $SystemEvents = new $parent->SystemEvents();

        $event = $SystemEvents->trigger($event);
        unset($parent, $SystemEvents);

        return $event;
    }

    /**
     * Triggers the event for any plugin listeners
     *
     * @param \Blesta\Core\Util\Events\Common\EventInterface $event The event to trigger
     * @return \Blesta\Core\Util\Events\Common\EventInterface $event The event triggered
     */
    private static function triggerPluginEvent(EventInterface $event)
    {
        $parent = new stdClass();
        Loader::loadModels($parent, ['PluginManager']);

        $PluginManager = new $parent->PluginManager();

        $event = $PluginManager->triggerEvents($event);
        unset($parent, $PluginManager);

        return $event;
    }

    /**
     * Triggers a deprecated event
     *
     * @param string $eventName The name of the deprecated event to trigger
     * @param array $params The parameters to pass to the event
     */
    protected static function triggerDeprecatedEvent($eventName, array $params)
    {
        $eventFactory = new EventFactory();
        $eventListener = $eventFactory->listener();
        $eventListener->register($eventName);
        $deprecatedEvent = $eventFactory->event($eventName, $params);
        $eventListener->trigger($deprecatedEvent);
    }
}
