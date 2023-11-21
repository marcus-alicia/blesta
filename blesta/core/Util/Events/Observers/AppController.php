<?php
namespace Blesta\Core\Util\Events\Observers;

use Blesta\Core\Util\Events\Observer;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The AppController event observer
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Observers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AppController extends Observer
{
    /**
     * Handle AppController.preAction events.
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for AppController.preAction events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function preAction(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }

    /**
     * Handle AppController.structure events.
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for AppController.structure events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function structure(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }
}
