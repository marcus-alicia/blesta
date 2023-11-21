<?php
namespace Blesta\Items\Item;

use Blesta\Items\ItemFactory;
use Blesta\Items\Item\ItemInterface;
use stdClass;

/**
 * ItemMap combines items
 */
class ItemMap
{
    /**
     * @var The new item
     */
    private $item;
    /**
     * @var A set of item fields
     */
    private $fields;
    /**
     * @var ItemFactory
     */
    private $itemFactory;

    /**
     * Init
     */
    public function __construct(ItemFactory $factory = null)
    {
        $this->itemFactory = ($factory ? $factory : new ItemFactory());
        $this->reset();
    }

    /**
     * Combine the values of one item with the keys of another item
     *
     * @param ItemInterface $item1 The item whose values to combine
     * @param ItemInterface $item2 The item whose keys to combine
     * @return Item An item containing only keys from $item2 that match values in $item1
     */
    public function combine(ItemInterface $item1, ItemInterface $item2)
    {
        // Reset the map
        $this->reset();

        $this->fields = $item1->getFields();

        foreach ($item2->getFields() as $key => $value) {
            // Add a single value
            if (is_scalar($value)) {
                $this->add($value, $key);
            } elseif (is_array($value)) {
                // Add the first matching value found
                $this->addFirstMatching($value, $key);
            }
        }

        return $this->item;
    }

    /**
     * Creates a new item field if one of the key matches a known item field
     *
     * @param array $keys An array of potential keys
     * @param string $new_key The new key to use for the field
     */
    private function addFirstMatching(array $keys, $new_key)
    {
        // Find the first matching key, and use that value
        foreach ($keys as $key) {
            if ($this->add($key, $new_key)) {
                break;
            }
        }
    }

    /**
     * Creates a new item field if the key matches a known item field
     *
     * @param string $key The key
     * @param string $new_key The new key to use for the field
     * @return bool True if a field was added, otherwise false
     */
    private function add($key, $new_key)
    {
        // Add the new field
        if (is_scalar($key) && property_exists($this->fields, $key)) {
            $this->item->setField($new_key, $this->fields->{$key});
            return true;
        }
        return false;
    }

    /**
     * Resets the combined item
     */
    protected function reset()
    {
        $this->item = $this->itemFactory->item();
        $this->fields = new stdClass();
    }
}
