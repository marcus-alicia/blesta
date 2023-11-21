<?php
namespace Blesta\Core\Automation\Tasks\Common;

/**
 * Simple interface for logging content
 *
 * @package blesta
 * @subpackage blesta.core.Automation.Tasks.Common
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface LoggableInterface
{
    /**
     * Logs the given content
     *
     * @param string $content The content to log
     */
    public function log($content);

    /**
     * Marks the log completed
     */
    public function logComplete();
}
