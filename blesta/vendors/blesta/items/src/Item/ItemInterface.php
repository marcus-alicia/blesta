<?php
namespace Blesta\Items\Item;

/**
 * Interface for building an item
 */
interface ItemInterface
{
    /**
     * Retrieves an object representing the fields
     *
     * @return stdClass The fields set on the item
     */
    public function getFields();

    /**
     * Adds the following fields to the item
     *
     * @param array|stdClass Merges the given fields with those that already exist
     */
    public function setFields($fields);

    /**
     * Adds a single field to the item
     *
     * @param string $key The name of the field
     * @param mixed $value The value for the field
     */
    public function setField($key, $value);

    /**
     * Removes a single field from the item
     *
     * @param string $key The name of the field
     */
    public function removeField($key);
}
