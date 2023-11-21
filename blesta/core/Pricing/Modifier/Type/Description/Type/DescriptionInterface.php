<?php
namespace Blesta\Core\Pricing\Modifier\Type\Description\Type;

/**
 * Description interface
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Modifier.Type.Description.Type
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface DescriptionInterface
{
    /**
     * Constructs a description from the given meta item
     *
     * @param array $meta An array of meta information representing the item
     * @param array $oldMeta An array of old meta information representing the item (optional, only used
     *  to combine the two sets of meta data into a single description)
     * @return string The description
     */
    public function get(array $meta, array $oldMeta = null);
}
