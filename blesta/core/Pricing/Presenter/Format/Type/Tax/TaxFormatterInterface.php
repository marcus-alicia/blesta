<?php
namespace Blesta\Core\Pricing\Presenter\Format\Type\Tax;

use stdClass;

/**
 * Tax formatter interface
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Format.Type.Tax
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface TaxFormatterInterface
{
    /**
     * Formats the given fields
     *
     * @param stdClass $fields An stdClass object representing the fields
     */
    public function format(stdClass $fields);
}
