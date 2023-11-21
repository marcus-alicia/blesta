<?php
namespace Blesta\Core\Util\Events\Observers;

use Blesta\Core\Util\Events\Observer;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The Transactions event observer
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Observers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Transactions extends Observer
{
    /**
     * Handle Transactions.addBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Transactions.addBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Transactions.addAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Transactions.addAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Transactions.add', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Transactions.editBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Transactions.editBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Transactions.editAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Transactions.editAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('Transactions.edit', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle Transactions.add events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Transactions.add events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function add(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Transactions.edit events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Transactions.edit events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function edit(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Transactions.applyBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Transactions.applyBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function applyBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle Transactions.applyAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for Transactions.applyAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function applyAfter(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }
}
