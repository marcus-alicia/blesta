<?php
namespace Blesta\Core\Pricing\Presenter\Format\Type\Discount;

use stdClass;

/**
 * Discount formatter
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Format.Type.Discount
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class DiscountFormatter extends AbstractDiscountFormatter
{
    /**
     * {@inheritdoc}
     *
     * @return \Blesta\Items\Item\Item An Item instance
     */
    public function format(stdClass $fields)
    {
        return $this->make('discount', $fields);
    }
}
