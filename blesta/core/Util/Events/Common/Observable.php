<?php
namespace Blesta\Core\Util\Events\Common;

/**
 * Observable interface
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Common
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface Observable
{
    /**
     * Reacts to a triggered event by performing an update
     *
     * @param \Blesta\Core\Util\Events\Common\EventInterface $event The event triggered
     * @return \Blesta\Core\Util\Events\Common\EventInterface $event The processed event object
     */
    public function update(EventInterface $event);
}
