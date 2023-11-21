<?php
namespace Blesta\Core\Pricing\Presenter\Format\Type\Item;

use Blesta\Items\ItemFactory;

/**
 * Abstract array formatter
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Format.Type.Item
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractArrayFormatter implements ArrayFormatterInterface
{
    /**
     * @var ItemFactory An instance of the ItemFactory
     */
    protected $itemFactory;

    /**
     * Init
     *
     * @param \Blesta\Items\ItemFactory $itemFactory An instance of the ItemFactory
     */
    public function __construct(ItemFactory $itemFactory)
    {
        $this->itemFactory = $itemFactory;
    }

    /**
     * Creates an item
     *
     * @param stdClass|array $fields A set of fields to include on the item
     * @return \Blesta\Items\Item\Item An Item instance
     */
    protected function makeItem($fields)
    {
        $item = $this->itemFactory->item();
        $item->setFields($fields);
        return $item;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function format(array $fields);
}
