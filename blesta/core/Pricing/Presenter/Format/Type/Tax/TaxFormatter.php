<?php
namespace Blesta\Core\Pricing\Presenter\Format\Type\Tax;

use stdClass;

/**
 * Tax formatter
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Format.Type.Tax
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class TaxFormatter extends AbstractTaxFormatter
{
    /**
     * {@inheritdoc}
     *
     * @return \Blesta\Items\Item\Item An Item instance
     */
    public function format(stdClass $fields)
    {
        return $this->make('tax', $fields);
    }
}
