<?php
namespace Blesta\Core\Automation\Type\Common;

/**
 * Simple interface for defining automation type methods
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Type.Common
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface AutomationTypeInterface
{
    /**
     * Determines whether the automation type can be run given a date
     *
     * @param string $date A date timestamp representing the date the automation type may be run at
     * @return bool True if the automation type may be run, or false otherwise
     */
    public function canRun($date);

    /**
     * Retrieves the raw automation data
     *
     * @return mixed The raw automation data
     */
    public function raw();
}
