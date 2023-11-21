<?php
namespace Blesta\Core\Util\Common\Traits;

use Configure;
use Exception;
use Throwable;

/**
 * Trait for fetching services from the system container
 *
 * @package blesta
 * @subpackage blesta.core.Util.Common.Traits
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
trait Container
{
    /**
     * Fetch an item from the container
     *
     * @param string $service The name of the service from the container to fetch
     * @return mixed The service if found, otherwise null
     */
    protected function getFromContainer($service)
    {
        $object = null;
        $container = null;

        // Attempt to load the logger
        try {
            $container = Configure::get('container');
            $logger = $container->get('logger');
        } catch (Throwable $e) {
            // Nothing to do
        }

        // Attempt to load the requested service
        try {
            $object = $container->get($service);
        } catch (Throwable $e) {
            // A service was requested that could not be loaded
            if (isset($logger)) {
                $logger->critical($e);
            }
        }

        return $object;
    }
}
