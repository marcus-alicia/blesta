<?php
namespace Blesta\Core\Pricing\Presenter\Format\Type\Discount;

use Blesta\Core\Pricing\Presenter\Format\Type\Item\AbstractItemCreator;
use stdClass;

/**
 * Abstract discount formatter
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Format.Type.Discount
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractDiscountFormatter extends AbstractItemCreator implements DiscountFormatterInterface
{
    /**
     * {@inheritdoc}
     */
    abstract public function format(stdClass $fields);
}
