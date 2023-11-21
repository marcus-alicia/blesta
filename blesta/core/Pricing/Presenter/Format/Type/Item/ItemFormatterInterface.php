<?php
namespace Blesta\Core\Pricing\Presenter\Format\Type\Item;

use stdClass;

/**
 * Item formatter interface
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Format.Type.Item
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface ItemFormatterInterface
{
    /**
     * Formats the given fields
     *
     * @param stdClass $fields An stdClass object representing the fields
     */
    public function format(stdClass $fields);

    /**
     * Formats the given service data
     *
     * @param stdClass $fields An stdClass object representing the object data fields
     */
    public function formatService(stdClass $fields);
}
