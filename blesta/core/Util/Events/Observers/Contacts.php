<?php
namespace Blesta\Core\Util\Events\Observers;

use Blesta\Core\Util\Events\Observer;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The Contacts event observer
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Observers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Contacts extends Observer
{
    /**
     * Handle Contacts.addBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Contacts.addBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Contacts.addAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Contacts.addAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Contacts.add', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Contacts.editBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Contacts.editBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Contacts.editAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Contacts.editAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Contacts.edit', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Contacts.deleteBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Contacts.deleteBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function deleteBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Contacts.deleteAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Contacts.deleteAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function deleteAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Contacts.delete', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Contacts.add events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Contacts.add events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function add(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Contacts.edit events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Contacts.edit events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function edit(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Contacts.delete events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Contacts.delete events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function delete(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }
}
