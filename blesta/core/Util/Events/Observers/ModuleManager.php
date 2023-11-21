<?php
namespace Blesta\Core\Util\Events\Observers;

use Blesta\Core\Util\Events\Observer;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The ModuleManager event observer
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Observers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ModuleManager extends Observer
{
    /**
     * Handle ModuleManager.addBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for ModuleManager.addBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle ModuleManager.addAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for ModuleManager.addAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function addAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('ModuleManager.add', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle ModuleManager.deleteBefore events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for ModuleManager.deleteBefore events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function deleteBefore(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle ModuleManager.deleteAfter events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for ModuleManager.deleteAfter events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function deleteAfter(EventInterface $event)
    {
        parent::triggerDeprecatedEvent('ModuleManager.delete', $event->getParams());

        return parent::triggerEvent($event);
    }

    /**
     * Handle ModuleManager.add events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for ModuleManager.add events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function add(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle ModuleManager.delete events
     *
     * @deprecated since v5.3.0
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for ModuleManager.delete events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function delete(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }
}
