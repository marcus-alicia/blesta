<?php
namespace Blesta\Core\Util\Events\Observers;

use Blesta\Core\Util\Events\Observer;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The Services event observer
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Observers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Services extends Observer
{
    /**
     * Handle Services.addBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Services.addBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Services.addAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Services.addAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Services.add', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Services.editBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Services.editBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Services.editAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Services.editAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Services.edit', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Services.cancelBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Services.cancelBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function cancelBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Services.cancelAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Services.cancelAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function cancelAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Services.cancel', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Services.suspendBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Services.suspendBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function suspendBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Services.suspendAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Services.suspendAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function suspendAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Services.suspend', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Services.unsuspendBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Services.unsuspendBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function unsuspendBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Services.unsuspendAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Services.unsuspendAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function unsuspendAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Services.unsuspend', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Services.add events.
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Services.add events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function add(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Services.edit events.
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Services.edit events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function edit(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Services.cancel events.
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Services.cancel events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function cancel(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Services.suspend events.
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Services.suspend events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function suspend(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Services.unsuspend events.
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Services.unsuspend events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function unsuspend(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }
}
