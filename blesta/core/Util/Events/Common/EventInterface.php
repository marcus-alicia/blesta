<?php
namespace Blesta\Core\Util\Events\Common;

/**
 * Event interface
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Common
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface EventInterface
{
    /**
     * Retrieves the name of the event
     *
     * @return string The name of the event
     */
    public function getName();

    /**
     * Retrieves the parameters set for this event, if any
     *
     * @return array|null An array of set parameters, otherwise null
     */
    public function getParams();

    /**
     * Sets parameters for this event
     *
     * @param array $params An array of parameters to be held by this event
     */
    public function setParams(array $params = null);

    /**
     * Returns the return value set for this event
     *
     * @return mixed The return value for the event
     */
    public function getReturnValue();

    /**
     * Sets the return value for this event
     *
     * @param mixed $value The return value for the event
     */
    public function setReturnValue($value);
}
