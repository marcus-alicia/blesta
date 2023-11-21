<?php
namespace Blesta\Core\Util\Events\Observers;

use Blesta\Core\Util\Events\Observer;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The Invoices event observer
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Observers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Invoices extends Observer
{
    /**
     * Handle Invoices.addBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Invoices.addBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Invoices.addAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Invoices.addAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Invoices.add', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Invoices.editBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Invoices.editBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Invoices.editAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Invoices.editAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Invoices.edit', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Invoices.setClosedBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Invoices.setClosedBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function setClosedBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Invoices.setClosedAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Invoices.setClosedAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function setClosedAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Invoices.setClosed', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Invoices.createFromServicesBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Invoices.createFromServicesBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function createFromServicesBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Invoices.createFromServicesAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Invoices.createFromServicesAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function createFromServicesAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Invoices.createFromServices', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Invoices.add events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Invoices.add events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function add(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Invoices.edit events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Invoices.edit events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function edit(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Invoices.setClosed events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Invoices.setClosed events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function setClosed(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Invoices.createFromServices events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Invoices.createFromServices events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function createFromServices(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }
}
