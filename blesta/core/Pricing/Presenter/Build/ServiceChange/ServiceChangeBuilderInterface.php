<?php
namespace Blesta\Core\Pricing\Presenter\Build\ServiceChange;

use stdClass;

/**
 * Service change builder interface
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Build.ServiceChange
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface ServiceChangeBuilderInterface
{
    /**
     * Builds a service
     *
     * @param stdClass $service An stdClass object for the service
     * @param array $vars An array of input data
     * @param stdClass $package An stdClass object for the package
     * @param stdClass $pricing An stdClass object for the pricing
     * @param array $options An array of options
     */
    public function build(stdClass $service, array $vars, stdClass $package, stdClass $pricing, array $options);
}
