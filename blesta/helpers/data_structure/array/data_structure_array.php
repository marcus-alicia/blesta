<?php
/**
 * Array Data Structure helper
 *
 * Provides utility methods to assist in manipulating arrays.
 *
 * @package blesta
 * @subpackage blesta.helpers.data_structure.array
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class DataStructureArray
{
    /**
     * Converts a numerically indexed array to a key/value indexed array.
     *
     * @param array $array A numerically indexed array of key/value pairs (array or object)
     * @param string $key_index An index to convert from key/value if set, otherwise will invert the array
     * @param string $value_index The index from $array to use as the value in the key/value pair.
     *  By default will assign everything in $array[$i] to the key.
     * @return array A key/value paired array
     */
    public static function numericToKey(array $array, $key_index = null, $value_index = null)
    {
        $new_array = [];
        $keys = [];

        if ($key_index == null) {
            foreach ($array as $key => $value) {
                if (is_object($value)) {
                    $value = get_object_vars($value);
                }
                $keys += array_keys($value);
            }
        }

        $num_keys = count($keys);

        // Cycle through each element of the array
        foreach ($array as $key => $value) {
            // Set the key based upon the key index given
            if ($key_index !== null) {
                // Assign the index if an object
                if (is_object($value)) {
                    if (isset($value->$key_index)) {
                        $new_array[$value->$key_index] = ($value_index !== null ? $value->$value_index : $value);
                    }
                } else {
                    // Assign the index for arrays
                    if (isset($value[$key_index])) {
                        $new_array[$value[$key_index]] = ($value_index !== null ? $value[$value_index] : $value);
                    }
                }
            } else {
                // No key index given, so set all keys available
                for ($i = 0; $i < $num_keys; $i++) {
                    if (is_object($value)) {
                        if (isset($value->{$keys[$i]})) {
                            $new_array[$keys[$i]][$key] = $value->{$keys[$i]};
                        }
                    } else {
                        if (isset($value[$keys[$i]])) {
                            $new_array[$keys[$i]][$key] = $value[$keys[$i]];
                        }
                    }
                }
            }
        }

        return $new_array;
    }

    /**
     * Converts a key/value paired array to a numerically indexed array that contains
     * key/value pairs.
     *
     * @param mixed $vars A key/value array or object to convert to a numerically indexed array
     * @param boolean $match_indexes True will ensure that each key index contains the same number of elements.
     *  The default value for each index is null.
     */
    public static function keyToNumeric($vars, $match_indexes = true)
    {
        $new_array = [];

        // If $vars is an object, fetch all public member variables
        if (is_object($vars)) {
            $vars = get_object_vars($vars);
        }

        // Invert the array of key/value pairs
        foreach ($vars as $key => $value) {
            foreach ((array)$value as $j => $sub_value) {
                $new_array[$j][$key] = $sub_value;
            }
        }

        // If set to match indexes, fill all indexes to the appropriate value
        if ($match_indexes) {
            $keys = array_keys($vars);

            // Create a buffer to be filled with existing values
            $buffer = array_combine($keys, array_fill(0, count($keys), null));

            // Fill the new key with all of the given values plus those left over from the buffer
            foreach ($new_array as $key => $value) {
                $new_array[$key] = array_merge($buffer, $new_array[$key]);
            }
        }

        return $new_array;
    }

    /**
     * Flattens a multi-dimensional array into a single level array, using the provided
     * notation.
     *
     * @param array $array A key/value array to convert to a single level indexed array
     * @param string $prefix The prefix to be used in the notation
     * @param string $suffix The suffix to be used in the notation
     * @param string $parent_key The parent key from the current array (used for recursion)
     * @return array A single level array, flattened using the provided notation
     */
    public static function flatten(array $array, $prefix = '[', $suffix = ']', $parent_key = '')
    {
        $flat_array = [];

        foreach($array as $key => $value) {
            $flat_key = (empty($parent_key) ? '' : $prefix) . $key . (empty($parent_key) ? '' : $suffix);

            if (is_array($value)) {
                if (empty($value)) {
                    $flat_array[$parent_key . $flat_key] = $value;
                } else {
                    $flat_array = $flat_array + self::flatten(
                        $value,
                        $prefix,
                        $suffix,
                        $parent_key . $flat_key
                    );
                }
            } else {
                $flat_array[$parent_key . $flat_key] = $value;
            }
        }

        return $flat_array;
    }

    /**
     * Unflattens a single level array into a multi-dimensional array, using the provided
     * notation.
     *
     * @param array $array A key/value array to convert to a multi-dimensional indexed array
     * @param string $prefix The prefix used in the notation of the array
     * @param string $suffix The suffix used in the notation of the array
     * @return array A single level array, flattened using the provided notation
     */
    public static function unflatten($array, $prefix = '[', $suffix = ']')
    {
        $original_array = [];

        // Convert flattened array to dot notation
        $dot_array = [];
        foreach($array as $key => $value) {
            $key = str_replace($prefix, '.', str_replace($suffix, '', $key));
            $dot_array[$key] = $value;
        }

        // Unflatten the array
        foreach ($dot_array as $key => $value) {
            (function ($key, $value) use (&$original_array) {
                if (is_null($key)) {
                    return $original_array = $value;
                }

                $keys = explode('.', $key);
                while (count($keys) > 1) {
                    $key = array_shift($keys);
                    if (!isset($original_array[$key]) || !is_array($original_array[$key])) {
                        $original_array[$key] = [];
                    }
                    $original_array =& $original_array[$key];
                }
                $original_array[array_shift($keys)] = $value;

                return $original_array;
            })($key, $value);

            // Unflatten the value if is an array
            if (is_array($value) && !strpos($key, '.')) {
                $nested = self::unflatten($value);
                $original_array[$key] = $nested;
            }
        }

        return $original_array;
    }
}
