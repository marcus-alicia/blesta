<?php
namespace Blesta\Core\Util\Events\Observers;

use Blesta\Core\Util\Events\Observer;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The GatewayManager event observer
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Observers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class GatewayManager extends Observer
{
    /**
     * Handle GatewayManager.addBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for GatewayManager.addBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle GatewayManager.addAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for GatewayManager.addAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('GatewayManager.add', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle GatewayManager.editBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for GatewayManager.editBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle GatewayManager.editAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for GatewayManager.editAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('GatewayManager.edit', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle GatewayManager.deleteBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for GatewayManager.deleteBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function deleteBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle GatewayManager.deleteAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for GatewayManager.deleteAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function deleteAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('GatewayManager.delete', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle GatewayManager.add events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for GatewayManager.add events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function add(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle GatewayManager.edit events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for GatewayManager.edit  events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function edit(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle GatewayManager.delete events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for GatewayManager.delete events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function delete(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }
}
