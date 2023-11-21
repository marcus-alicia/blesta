<?php
namespace Blesta\Core\Automation\Tasks\Common;

/**
 * Simple interface for running automation tasks
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Common
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface RunnableInterface
{
    /**
     * Executes the task
     */
    public function run();
}
