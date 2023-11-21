<?php
namespace Blesta\Core\Util\Events\Observers;

use Blesta\Core\Util\Events\Observer;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The Clients event observer
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Observers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Clients extends Observer
{
    /**
     * Handle Clients.createBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.createBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function createBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.createAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.createAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function createAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Clients.create', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.addBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.addBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.addAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.addAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Clients.add', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.editBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.editBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.editAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.editAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Clients.edit', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.deleteBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.deleteBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function deleteBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.deleteAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.deleteAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function deleteAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Clients.delete', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.addNoteBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.addNoteBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addNoteBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.addNoteAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.addNoteAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addNoteAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Clients.addNote', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.editNoteBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.editNoteBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editNoteBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.editNoteAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.editNoteAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editNoteAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Clients.editNote', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.deleteNoteBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.deleteNoteBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function deleteNoteBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.deleteNoteAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.deleteNoteAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function deleteNoteAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Clients.deleteNote', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.create events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.create events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function create(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.add events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.add events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function add(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.edit events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.edit events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function edit(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.delete events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.delete events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function delete(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.addNote events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.addNote events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addNote(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.editNote events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.editNote events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editNote(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Clients.deleteNote events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Clients.deleteNote events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function deleteNote(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }
}
