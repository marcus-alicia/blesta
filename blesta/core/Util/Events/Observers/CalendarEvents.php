<?php
namespace Blesta\Core\Util\Events\Observers;

use Blesta\Core\Util\Events\Observer;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The CalendarEvents event observer
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Observers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class CalendarEvents extends Observer
{
    /**
     * Handle CalendarEvents.addBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for CalendarEvents.addBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle CalendarEvents.addAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for CalendarEvents.addAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('CalendarEvents.add', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle CalendarEvents.editBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for CalendarEvents.editBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle CalendarEvents.editAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for CalendarEvents.editAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('CalendarEvents.edit', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle CalendarEvents.deleteBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for CalendarEvents.deleteBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function deleteBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle CalendarEvents.deleteAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for CalendarEvents.deleteAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function deleteAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('CalendarEvents.delete', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle CalendarEvents.add events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for CalendarEvents.add events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function add(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle CalendarEvents.edit events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for CalendarEvents.edit events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function edit(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle CalendarEvents.delete events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for CalendarEvents.delete events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function delete(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }
}
