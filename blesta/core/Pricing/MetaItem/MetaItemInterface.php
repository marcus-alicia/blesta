<?php
namespace Blesta\Core\Pricing\MetaItem;

use Blesta\Items\Item\ItemInterface;

/**
 * Meta item interface
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.MetaItem
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface MetaItemInterface
{
    /**
     * Attaches the given item
     *
     * @param Blesta\Items\Item\ItemInterface $item The Item to add
     */
    public function attach(ItemInterface $item);

    /**
     * Detaches the given item
     *
     * @param Blesta\Items\Item\ItemInterface $item The Item to remove
     */
    public function detach(ItemInterface $item);

    /**
     * Retrieves all attached meta items
     *
     * @return Blesta\Items\Collection\ItemCollection A collection containing the items
     */
    public function meta();
}
