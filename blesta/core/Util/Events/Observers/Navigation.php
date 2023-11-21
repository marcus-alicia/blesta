<?php
namespace Blesta\Core\Util\Events\Observers;

use Blesta\Core\Util\Events\Observer;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The Navigation event observer
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Observers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Navigation extends Observer
{
    /**
     * Handle Navigation.getSearchOptions events
     *
     * @param Blesta\Core\Util\Events\Common\EventInterface $event An event object for
     *  Navigation.getSearchOptions events
     * @return Blesta\Core\Util\Events\Common\EventInterface The processed event object
     */
    public static function getSearchOptions(EventInterface $event)
    {
        return parent::triggerEvent($event);
    }
}
