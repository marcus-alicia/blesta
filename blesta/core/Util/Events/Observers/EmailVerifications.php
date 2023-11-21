<?php
namespace Blesta\Core\Util\Events\Observers;

use Blesta\Core\Util\Events\Observer;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The EmailVerifications event observer
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Observers
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class EmailVerifications extends Observer
{
    /**
     * Handle EmailVerifications.addBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for EmailVerifications.addBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle EmailVerifications.addAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for EmailVerifications.addAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addAfter(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle EmailVerifications.editBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for EmailVerifications.editBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle EmailVerifications.editAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for EmailVerifications.editAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function editAfter(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle EmailVerifications.deleteBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for EmailVerifications.deleteBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function deleteBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle EmailVerifications.deleteAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for EmailVerifications.deleteAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function deleteAfter(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle EmailVerifications.verifyBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for EmailVerifications.verifyBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function verifyBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle EmailVerifications.verifyAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for EmailVerifications.verifyAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function verifyAfter(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }
}
