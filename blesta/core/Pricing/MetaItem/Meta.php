<?php
namespace Blesta\Core\Pricing\MetaItem;

use Blesta\Core\Pricing\MetaItem\MetaItemInterface;
use Blesta\Items\Collection\ItemCollection;

/**
 * Trait for managing meta data
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.MetaItem
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
trait Meta
{
    /**
     * Retrieves all meta information set on a meta item combined together
     *
     * @param MetaItemInterface $item The item whose meta information to retrieve
     * @return array An array of all meta data
     */
    protected function getMeta(MetaItemInterface $item)
    {
        $collection = $item->meta();
        $meta = [];

        // Merge each meta item by key
        foreach ($collection as $data) {
            $meta = array_merge($meta, (array)$data->getFields());
        }

        return $meta;
    }

    /**
     * Retrieves all meta information set on an item collection
     *
     * @param Blesta\Items\Collection\ItemCollection\ItemCollection $collection An item collection
     * @return array An array of all meta data
     */
    protected function getMetaFromCollection(ItemCollection $collection)
    {
        $meta = [];

        // Merge each meta item by key
        foreach ($collection as $data) {
            $meta = array_merge($meta, (array)$data->getFields());
        }

        return $meta;
    }

    /**
     * Updates the given MetaItemInterface to set the first meta item with the given data
     *
     * @param Blesta\Core\Pricing\MetaItem\MetaItemInterface $item The item whose meta information to update
     * @param array $meta The meta information to update the item with
     * @return Blesta\Core\Pricing\MetaItem\MetaItemInterface The updated item
     */
    private function updateMeta(MetaItemInterface $item, array $meta)
    {
        // Update the first meta item in the list with the given meta information
        foreach ($item->meta() as $data) {
            $fields = (array)$data->getFields();

            // Determine whether to append to an existing field or to add a new one
            foreach ($meta as $field => $value) {
                // Field already exists, so merge with it
                if (array_key_exists($field, $fields)) {
                    $updatedField = array_merge((array)$fields[$field], (array)$value);

                    // Maintain the type if the new value is an object
                    if (is_object($value)) {
                        $updatedField = (object)$updatedField;
                    }

                    $data->setFields([$field => $updatedField]);
                } else {
                    // Add a new field
                    $data->setFields([$field => $value]);
                }
            }

            break;
        }

        return $item;
    }
}
