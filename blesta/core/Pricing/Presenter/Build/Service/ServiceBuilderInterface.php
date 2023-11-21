<?php
namespace Blesta\Core\Pricing\Presenter\Build\Service;

use stdClass;

/**
 * Service builder interface
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Build.Service
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface ServiceBuilderInterface
{
    /**
     * Builds a service
     *
     * @param stdClass $service
     */
    public function build(stdClass $service);
}
