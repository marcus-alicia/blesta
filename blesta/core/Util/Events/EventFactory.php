<?php
namespace Blesta\Core\Util\Events;

/**
 * Instantiates event objects
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class EventFactory
{
    /**
     * Creates a new instance of an Event
     *
     * @param string $name The name of the event
     * @param array $params An array of parameters to be held by this event (optional)
     * @return \Blesta\Core\Util\Events\Event
     */
    public function event($name, array $params = null)
    {
        return new Event($name, $params);
    }

    /**
     * Creates a new instance of an event Listener
     *
     * @return \Blesta\Core\Util\Events\Listener
     */
    public function listener()
    {
        return new Listener();
    }

    /**
     * Creates a new instance of an event Observer
     *
     * @return \Blesta\Core\Util\Events\Observer
     */
    public function observer()
    {
        return new Observer();
    }
}
