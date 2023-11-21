<?php
namespace Blesta\Core\Pricing\Presenter\Build\ServiceData;

use stdClass;

/**
 * Service data builder interface
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Build.ServiceData
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface ServiceDataBuilderInterface
{
    /**
     * Builds a service
     *
     * @param array $vars An array of input data
     * @param stdClass $package An stdClass object for the package
     * @param stdClass $pricing An stdClass object for the pricing
     * @param array $options An array of options
     */
    public function build(array $vars, stdClass $package, stdClass $pricing, array $options);
}
