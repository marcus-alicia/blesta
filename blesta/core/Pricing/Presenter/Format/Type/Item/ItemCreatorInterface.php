<?php
namespace Blesta\Core\Pricing\Presenter\Format\Type\Item;

use stdClass;

/**
 * Item creator interface
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Format.Type.Item
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface ItemCreatorInterface
{
    /**
     * Creates an item
     *
     * @param stdClass|array $fields A set of fields to include on the item
     */
    public function makeItem($fields);

    /**
     * Combines an item and fields
     *
     * @param string $method The method to call for matching fields
     * @param stdClass $fields An stdClass object representing the object data fields
     */
    public function make($method, stdClass $fields);
}
