<?php
namespace Blesta\Core\Pricing\Presenter\Format\Type\Item;

/**
 * Array formatter interface
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Format.Type.Item
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface ArrayFormatterInterface
{
    /**
     * Formats the given fields
     *
     * @param array $fields An array of fields to format
     */
    public function format(array $fields);
}
