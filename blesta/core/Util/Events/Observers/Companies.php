<?php
namespace Blesta\Core\Util\Events\Observers;

use Blesta\Core\Util\Events\Observer;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The Companies event observer
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Observers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Companies extends Observer
{
    /**
     * Handle Companies.addBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Companies.addBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Companies.addAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Companies.addAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Companies.add', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Companies.editBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Companies.editBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Companies.editAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Companies.editAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Companies.edit', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Companies.deleteBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Companies.deleteBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function deleteBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Companies.deleteAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Companies.deleteAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function deleteAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Companies.delete', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Companies.add events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Companies.add events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function add(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Companies.edit events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Companies.edit events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function edit(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Companies.delete events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Companies.delete events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function delete(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }
}
