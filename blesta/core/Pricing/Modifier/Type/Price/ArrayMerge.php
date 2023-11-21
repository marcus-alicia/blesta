<?php
namespace Blesta\Core\Pricing\Modifier\Type\Price;

/**
 * Merges an array of objects
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Modifier.Type.Price
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ArrayMerge
{
    /**
     * Merges all objects in the array with the given shared property $id
     * into a single object, and summing the value of the shared property $key
     *
     * @param array $data An array of objects to combine
     * @param string $id The name of an object's property to use as the unique identifier for duplicates.
     *  The value of this property must be valid keys (integer or string)
     * @param string $key The name of the object's property whose value to combine by summing duplicates.
     *  The value of this property should be a float
     * @return A subset array of objects with duplicate object $id's combined
     */
    public function combineSum(array $data, $id, $key)
    {
        $result = [];

        foreach ($data as $item) {
            $item = (array)$item;

            // The identifier doesn't exist, so it won't be included in the result
            if (!array_key_exists($id, $item)) {
                continue;
            }

            // Save unique values to the result set the first time we see them
            if (!array_key_exists($item[$id], $result)) {
                $result[$item[$id]] = (object)$item;
            } else {
                // Merge the item with the existing object in the result set
                $currentItem = $result[$item[$id]];
                $currentItem->{$key} = (isset($currentItem->{$key}) ? $currentItem->{$key} : 0);

                // Sum the value if it exists
                if (array_key_exists($key, $item)) {
                    $currentItem->{$key} += $item[$key];
                }

                // Merge the duplicate objects
                $result[$item[$id]] = (object)array_merge($item, (array)$currentItem);
            }
        }

        return array_values($result);
    }
}
