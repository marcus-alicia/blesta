<?php
namespace Blesta\Core\Pricing\Presenter\Format\Type\Item;

use stdClass;

/**
 * Abstract item formatter
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Format.Type.Item
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractItemFormatter extends AbstractItemCreator implements ItemFormatterInterface
{
    /**
     * {@inheritdoc}
     */
    abstract public function format(stdClass $fields);

    /**
     * {@inheritdoc}
     */
    abstract public function formatService(stdClass $fields);
}
