<?php
namespace Blesta\Core\Pricing\Presenter\Format\Type\Item;

use Blesta\Core\Pricing\Presenter\Format\Fields\FormatFieldsInterface;
use Blesta\Items\ItemFactory;
use InvalidArgumentException;
use stdClass;

/**
 * Abstract item creator formatter
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Format.Type.Item
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractItemCreator implements ItemCreatorInterface
{
    /**
     * @var ItemFactory An instance of the ItemFactory
     */
    protected $itemFactory;
    /**
     * @var FormatFields An instance of FormatFields
     */
    protected $formatFields;

    /**
     * Init
     *
     * @param \Blesta\Items\ItemFactory $itemFactory An instance of the ItemFactory
     * @param FormatFieldsInterface An instance of FormatFieldsInterface
     */
    public function __construct(ItemFactory $itemFactory, FormatFieldsInterface $fields)
    {
        $this->itemFactory = $itemFactory;
        $this->formatFields = $fields;
    }

    /**
     * Creates an item
     *
     * @param stdClass|array $fields A set of fields to include on the item
     * @return \Blesta\Items\Item\Item An Item instance
     */
    public function makeItem($fields)
    {
        $item = $this->itemFactory->item();
        $item->setFields($fields);
        return $item;
    }

    /**
     * Makes a formatted Item from the given fields and the
     * formatted fields available from the given method
     *
     * @param string $method The FormatFields method to call for matching fields
     * @param stdClass $fields An stdClass object representing the object data fields
     * @return \Blesta\Items\Item\Item A formatted Item
     */
    public function make($method, stdClass $fields)
    {
        // Invalid method
        if (!method_exists($this->formatFields, $method)) {
            throw new InvalidArgumentException(
                'Method "' . $method . '" is invalid for FormatFieldsInterface.'
            );
        }

        // Create an item of the given fields
        $item1 = $this->makeItem($fields);

        // Create an item of the the format fields
        $serviceFields = $this->formatFields->{$method}();
        $item2 = $this->makeItem($serviceFields);

        // Combine the two into a single item
        $itemMap = $this->itemFactory->itemMap();
        return $itemMap->combine($item1, $item2);
    }
}
