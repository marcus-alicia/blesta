<?php
namespace Blesta\Core\Pricing\Presenter\Format\Type\Tax;

use Blesta\Core\Pricing\Presenter\Format\Type\Item\AbstractItemCreator;
use stdClass;

/**
 * Abstract tax formatter
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Format.Type.Tax
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractTaxFormatter extends AbstractItemCreator implements TaxFormatterInterface
{
    /**
     * {@inheritdoc}
     */
    abstract public function format(stdClass $fields);
}
