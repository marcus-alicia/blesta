<?php
namespace Blesta\Core\Util\Events\Observers;

use Blesta\Core\Util\Events\Observer;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The ClientGroups event observer
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Observers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientGroups extends Observer
{
    /**
     * Handle ClientGroups.addBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for ClientGroups.addBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle ClientGroups.addAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for ClientGroups.addAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('ClientGroups.add', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle ClientGroups.editBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for ClientGroups.editBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle ClientGroups.editAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for ClientGroups.editAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('ClientGroups.edit', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle ClientGroups.deleteBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for ClientGroups.deleteBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function deleteBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle ClientGroups.deleteAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for ClientGroups.deleteAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function deleteAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('ClientGroups.delete', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle ClientGroups.add events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for ClientGroups.add events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function add(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle ClientGroups.edit events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for ClientGroups.edit events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function edit(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle ClientGroups.delete events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for ClientGroups.delete events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function delete(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }
}
