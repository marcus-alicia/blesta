<?php
namespace Blesta\Core\Util\Events\Observers;

use Blesta\Core\Util\Events\Observer;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The Staff event observer
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Observers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Staff extends Observer
{
    /**
     * Handle Staff.addBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Staff.addBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Staff.addAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Staff.addAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Staff.add', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Staff.editBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Staff.editBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Staff.editAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Staff.editAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Staff.edit', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Staff.add events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Staff.add events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function add(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Staff.edit events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Staff.edit events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function edit(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }
}
